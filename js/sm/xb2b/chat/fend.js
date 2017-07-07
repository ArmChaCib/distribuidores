jQuery.noConflict();

function isNotEmpty(value) {
    return value !== undefined && value !== '' && value !== null;
}

function isString(value) {
    return typeof value === 'string';
}

function getDayOfWeek(date) {
    var weekday = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
    return weekday[date.getDay()];
}

function getMonthOfYear(date) {
    var monthNames = [ "January", "February", "March", "April", "May", "June",
        "July", "August", "September", "October", "November", "December" ];

    return monthNames[date.getMonth()];
}

window.chat_debug = false;
window.xchat_separator = '#%#!@6';

(function($,W,D) {
    W.onload = function() {
        startApp();
    };

    var startApp = function() {
        var Chatbox = {
            statusBar: null, mainBox: null, screen: null, inputMessage: null,
            focusSupporter: null, supporterList: null, statusQueue: null,

            getStatusBar: function() { return $('#xb2b_status_bar'); },

            getMainBox: function() { return $('#xb2b_main_box'); },

            getScreen: function() { return $('#xb2b_main_box .screen'); },

            getSupporterList: function() { return $('#xb2b_main_box .supporter-list'); },

            getInputMessage: function() { return $('#input_message'); },

            saveGlobalUnread: function(actorId, count) {
                var globalUnread = $.jStorage.get('cusglobalunread');
                if(!isNotEmpty(globalUnread)) globalUnread = {};
                globalUnread[actorId] = count;
                $.jStorage.set('cusglobalunread', globalUnread);
            },

            log: function(msg) {
                if(window.chat_debug) console.warn(msg); },

            _formatAMPM: function(date) {
                var hours = date.getHours();
                var minutes = date.getMinutes();
                var ampm = hours >= 12 ? 'PM' : 'AM';
                hours = hours % 12;
                hours = hours ? hours : 12; // the hour '0' should be '12'
                minutes = minutes < 10 ? '0'+minutes : minutes;
                var strTime = hours + ':' + minutes + ' ' + ampm;
                return strTime;
            },

            scrollListMessageToBottom: function(list) {
                list.scrollTop(list[0].scrollHeight);
            },

            scrollListMessageToTop: function(list) {
                list.scrollTop(0);
            },

            getMsgList: function(sid) {
                return this.screen.find('.msg-list-'+sid);
            },

            getMessageItemHtml: function(msg, showName) {
                var date = new Date();
                date.setTime(msg.time);
                var timeStr = this._formatAMPM(date);
                var html = '<li class="msg-item actor-'+msg.actorType+'" data-actor-id="'+msg.actorId+'">';

                if(showName) html += '<span class="actor-name">'+msg.actorName+'</span>';
                else html += '<span class="actor-name"></span>';

                html += '<span class="msg-content">'+msg.content+'</span><span class="msg-meta">'+timeStr+'</span></li>';
                return html;
            },

            chatBox: function(action) {
                var $this = this;
                if(action === 'open') {
                    $this.mainBox.fadeIn('fast');
                    $.jStorage.set('chatboxopen', 1);
                } else if(action === 'close') {
                    $this.mainBox.fadeOut('fast');
                    $.jStorage.set('chatboxopen', 0);
                }
            },

            showGlobalUnread: function() {
                if(this.log) this.log('showGlobalUnread');
                var globalUnread = $.jStorage.get('cusglobalunread');
                if(isNotEmpty(globalUnread)) {
                    for(var actorId in globalUnread) {
                        var value = globalUnread[actorId];
                        if(value > 0) {
                            var unreadElement = this.supporterList.find('.supporter[data-id="'+actorId+'"] .unread');
                            unreadElement.text(value).addClass('active');
                        }
                    }
                }
            },

            getSelectDate: function(id) {
                return $('.date-navigator[data-id="'+id+'"] .select-date');
            },

            saveHistory: function(id, history) {
                $.jStorage.set('csdate'+id, history);
            },

            getLastHistory: function(id) {
                this.log('getLastHistory');
                var history = $.jStorage.get('csdate'+id);
                if(history.length <= 0) {
                    return false;
                } else {
                    var focus   = history[history.length - 1];
                    var pass    = [];
                    history.each(function(o, i) {
                        if(focus !== o) {
                            pass.push(o);
                        }
                    });
                    this.saveHistory(id, pass);
                    return focus;
                }
            },

            getTheNextMessages: function() {
                var $this = this;
                var orgDate = $('.date-navigator[data-id="'+$this.focusSupporter+'"] .select-date').data('org');
                var supporterId = $this.focusSupporter;
                if(isNotEmpty(orgDate)) {
                    $.post(APP_URL + 'xb2b/xchat/r?isAjax=true',
                        {support_id : supporterId, form_key: THEFORMKEY, day: orgDate}, function(rs) {
                            if(!rs.error) {
                                var collection = [], tmp = rs.data.data;
                                for(var i=0; i<tmp.length;i++) {
                                    if(tmp[i] === '' || tmp[i] === null) continue;
                                    var items = tmp[i].split(W.xchat_separator);
                                    collection.push({
                                        actorType: items[0],
                                        actorId: items[1],
                                        actorName: items[2],
                                        time: items[3] * 1000,
                                        content: items[4]
                                    });
                                }

                                var listMessage = $this.getMsgList(supporterId);
                                var itemHtml = '';
                                var prevActorId = 0;
                                var date = new Date();
                                var dateTmp = orgDate.split('_');
                                date.setDate(dateTmp[1]);
                                date.setMonth(dateTmp[0] - 1);
                                date.setFullYear(dateTmp[2]);
                                var displayDate = getDayOfWeek(date) + ', ' +
                                    getMonthOfYear(date) + ' ' + date.getDate() + ', ' + date.getFullYear();

                                itemHtml += '<li class="section-date"><span class="hrdate">'+displayDate+'</span></li>';

                                $(collection).each(function(i,o) {
                                    var showName = true;
                                    if(parseInt(o.actorId) === prevActorId) showName = false;
                                    prevActorId = parseInt(o.actorId);
                                    itemHtml += $this.getMessageItemHtml(o, showName);
                                });

                                $(itemHtml).insertBefore(listMessage.children().first());
                                listMessage.children().fadeIn('fast');
                                $this.scrollListMessageToTop($this.getMsgList(supporterId));

                                var lastDate = $this.getLastHistory(supporterId);
                                if(lastDate === false) {
                                    $this.getSelectDate(supporterId).parent().hide();
                                } else {
                                    var displayDate = lastDate.split('_').join('/');
                                    $this.getSelectDate(supporterId).text(displayDate);
                                    $this.getSelectDate(supporterId).data('org', lastDate);
                                }
                            }
                        }, 'json');
                }
            },

            getMessagesBySupporter: function(suppoterId, day) {
                var $this = this;
                if($this.log) $this.log('getMessagesBySupporter | ' + suppoterId + '|' + day);
                $.post(APP_URL + 'xb2b/xchat/r?isAjax=true',
                    {support_id : suppoterId, form_key: THEFORMKEY, day: day}, function(rs) {
                        if(!rs.error) {
                            var collection = [], tmp = rs.data.data;
                            for(var i=0; i<tmp.length;i++) {
                                if(tmp[i] === '' || tmp[i] === null) continue;
                                var items = tmp[i].split(W.xchat_separator);
                                collection.push({
                                    actorType: items[0],
                                    actorId: items[1],
                                    actorName: items[2],
                                    time: items[3] * 1000,
                                    content: items[4]
                                });
                            }

                            var loop = 0;
                            var historyList = [];
                            rs.data.date.each(function(o, i){
                                if(loop === (rs.data.date.length - 1)) {
                                    var date = o.split('_').join('/');
                                    $this.getSelectDate(suppoterId).text(date);
                                    $this.getSelectDate(suppoterId).attr('data-org', o);
                                } else {
                                    historyList.push(o);
                                }
                                loop++;
                            });
                            
                            if(rs.data.date.length === 0) {
                                $('.date-navigator').hide();
                            }
                            
                            $this.saveHistory(suppoterId, historyList);

                            var listMessage = $this.getMsgList(suppoterId);
                            listMessage.children().remove();

                            var prevActorId = 0;

                            var date = new Date();

                            var displayDate = getDayOfWeek(date) + ', ' + getMonthOfYear(date) + ' ' + date.getDate() + ', ' + date.getFullYear();
                            $('<li class="section-date"><span class="hrdate">'+displayDate+'</span></li>').appendTo(listMessage);

                            $(collection).each(function(i,o) {
                                var showName = true;
                                if(parseInt(o.actorId) === prevActorId) showName = false;
                                prevActorId = parseInt(o.actorId);
                                var item = $($this.getMessageItemHtml(o, showName));
                                item.appendTo(listMessage);
                                item.fadeIn(100);
                            });

                            $this.scrollListMessageToBottom($this.getMsgList(suppoterId));
                        }
                    }, 'json');
            },

            sendMessage: function(callback) {
                var $this = this;
                if($this.log) $this.log('sendMessage');
                var msg = $this.inputMessage.val();
                var focussupporter = $.jStorage.get('focussupporter');

                if(!focussupporter || msg === '') {
                    return false;
                }

                var message = {
                    actorType: 2,
                    actorId: CUSTOMER_ID,
                    actorName: CUSTOMER_NAME,
                    time: new Date().getTime(),
                    content: msg
                };

                //Send message
                $.post(APP_URL + 'xb2b/xchat/s',
                    { msg: msg, supporter_id: focussupporter, form_key: THEFORMKEY },
                    function(rs) {
                        if(callback !== undefined)
                            callback(rs);
                    });
                    
                $.jStorage.set('cusoutgoing', message);
                setTimeout(function() {
                    $.jStorage.set('cusoutgoing', 0);
                }, 500);
                
                //Disable input in 1000ms
                $this.inputMessage.attr({placeholder: 'sending...', disabled  : 'disabled'});
                
                setTimeout(function() {
                    $this.inputMessage.val('').removeAttr('disabled');
                    $this.inputMessage.val('').attr('placeholder', '');
                    $this.inputMessage.focus();
                }, 1000);
            },

            /* defFocusSupporter */
            setFocusSupporter: function(id) {
                var $this = this;
                if($this.log) $this.log('setFocusSupporter');
                $this.focusSupporter = id;
                $.jStorage.set('focussupporter', id);

                var tab = $this.getMsgList(id);

                var oldLoad = false;
                if(!tab.hasClass('loaded')) {
                    $this.getMessagesBySupporter(id);
                    tab.addClass('loaded');
                } else {
                    oldLoad = true;
                }

                var name = $this.supporterList.find('li[data-id="'+id+'"] .sname').text();
                $('.focus-name').text(name);
                $('.current-user').show();
                $('.date-navigator').hide();
                $('.date-navigator[data-id="'+id+'"]').show();

                $this.supporterList.children().removeClass('active');
                $this.supporterList.find('li[data-id="'+id+'"]').addClass('active');
                $this.supporterList.find('li[data-id="'+id+'"] .unread').removeClass('active').text('0');
                $this.saveGlobalUnread(id, 0);

                $this.screen.find('.msg-list').hide();
                tab.show();
                if(oldLoad) $this.scrollListMessageToBottom(tab);
            },

            updateMessages: function(time) {
                var $this = this;
                if($this.log) $this.log('updateMessages | ' + time);

                if($this.statusQueue === null) {
                    var supporters = [];
                    $this.supporterList.find('.supporter').each(function(i,o){
                        supporters.push($(o).data('id'));
                    });
                    $this.statusQueue = supporters;
                } else {
                    var supporters = $this.statusQueue;
                }

                $.post(APP_URL + 'xb2b/xchat/n?isAjax=true',
                    {form_key: THEFORMKEY, slist: supporters}, function(rs) {
                        if(!rs.error) {
                            if(Object.keys(rs.data.message).length > 0) {
                                $.jStorage.set('cusunread', rs.data.message);
                                setTimeout(function() {
                                    $.jStorage.set('cusunread', 0);
                                }, 500);
                            }
                            
                            $this.supporterList.find('.supporter').each(function(i,o) {
                                var uid = $(o).data('id');
                                if(rs.data.online && rs.data.online[uid]) {
                                    $(o).addClass('online');
                                } else {
                                    $(o).removeClass('online');
                                }
                            });
                        }
                    }, 'json');

                $this.firstRefresh = false;
                setTimeout(function() {
                    $this.updateMessages(time);
                }, time);
            },
            
            refreshMessageView: function() {
                var $this = this;
                $this.log('refreshMessageView');
                var data = $.jStorage.get('cusunread');
                if(data == 0) return;
                var newMessages = data;
                for(var sId in newMessages) {
                    var messages = newMessages[sId].data;
                    if(messages) {
                        if(parseInt($this.focusSupporter) !== parseInt(sId)
                                || !$.jStorage.get('chatboxopen')) {
                            var unreadElement = $this.supporterList
                                .find('.supporter[data-id="'+sId+'"] .unread');
                            var old = parseInt(unreadElement.text());
                            if(isNaN(old)) old = 0;
                            old += messages.length;
                            unreadElement.text(old);
                            $this.saveGlobalUnread(sId, old);
                            if(old > 0) unreadElement.addClass('active');
                        }

                        var listMessage = $this.getMsgList(sId);
                        var prevActorId = listMessage.children().last().data('actor-id');
                            prevActorId = parseInt(prevActorId);

                        messages.each(function(line, index) {
                            if(isNotEmpty(line) && isString(line)) {
                                var items = line.split(W.xchat_separator);
                                var message = {
                                    actorType: items[0],
                                    actorId: items[1],
                                    actorName: items[2],
                                    time: items[3] * 1000,
                                    content: items[4]
                                };

                                var showName = true;

                                if(prevActorId === parseInt(message.actorId)) {
                                    showName = false;
                                }

                                var item = $($this.getMessageItemHtml(message, showName));

                                item.appendTo(listMessage);
                                item.fadeIn(100);
                            }
                        });
                        $this.scrollListMessageToBottom(listMessage);

                    }
                }
            },
            
            refreshOutgoing: function() {
                var $this = this;
                var message = $.jStorage.get('cusoutgoing');
                if(message == 0) return;
                var listMessage = $this.getMsgList($this.focusSupporter);
                var prevActorId = listMessage.children().last().data('actor-id');
                prevActorId = parseInt(prevActorId);

                var showName = true;

                if(prevActorId === parseInt(message.actorId)) {
                    showName = false;
                }

                var item = $($this.getMessageItemHtml(message, showName));
                item.appendTo(listMessage);
                item.fadeIn(100);
                $this.inputMessage.val('');

                $this.scrollListMessageToBottom(listMessage);
            },
            
            refreshStatusBarView: function(time) {
                var $this = this;
                var globalunread = $.jStorage.get('cusglobalunread');
                var total = 0;
                
                for(var i in globalunread) {
                    total += globalunread[i];
                }
                
                if(total > 0) {
                    $('#xb2b_status_bar .default-text').removeClass('active');
                    $('#xb2b_status_bar .notification-text').addClass('active');
                    $('#xb2b_status_bar .notification-text strong').text(total);
                } else {
                    $('#xb2b_status_bar .notification-text').removeClass('active');
                    $('#xb2b_status_bar .default-text').addClass('active');
                }
                
                setTimeout(function() {
                    $this.refreshStatusBarView(time);
                }, time);
            },
            
            filterCustomer: function(filter) {
                var $this = this;
                var supporters = $this.supporterList.find('.sname');

                if(filter === '') {
                    $this.supporterList.find('.supporter').each(function() {
                        $(this).removeClass('filtered');
                    });
                    return;
                } else {
                    supporters.each(function(i, o) {
                        var focus = $(o);
                        var contain = focus.text().toLowerCase().indexOf(filter);
                        if(contain === -1) {
                            focus.parent().addClass('filtered');
                        } else {
                            focus.parent().removeClass('filtered');
                        }
                    });
                }
            },

            /* ======================== INITIAL ALL EVENTS ======================== */
            init: function() {
                var $this           = this;
                $this.statusBar     = $this.getStatusBar();
                $this.mainBox       = $this.getMainBox();
                $this.screen        = $this.getScreen();
                $this.msg_list      = $this.getMsgList();
                $this.inputMessage  = $this.getInputMessage();
                $this.supporterList = $this.getSupporterList();

                $('body').append($this.statusBar);
                $('body').append($this.mainBox);


                /* This event fires when user click on Send button. It will send current message and clear text box */
                $('#xb2b_main_box .btn-send').click(function(e) {
                    e.preventDefault();
                    $this.sendMessage();
                });

                $this.inputMessage.keydown(function (e) {
                    var dummy = "\xAD";
                    if (!e.ctrlKey && e.keyCode === 13) {
                        var regex = new RegExp(dummy,"g");
                        var newval = $(this).val().replace(regex, '');
                        $(this).val(newval);
                        $this.sendMessage();
                        return false;
                    } else if(e.keyCode === 13){
                        $(this).val($(this).val() + "\n" + dummy);
                    }
                    return true;
                });

                /* This event fire when user click on close button. It will close chat box */
                $('.btn-close-chatbox').click(function(e) {
                    e.preventDefault(); $this.chatBox('close'); });

                /* This event fires when user click on status bar. It will show chat box */
                $('#xb2b_status_bar').click(function(e) {
                    e.preventDefault(); $this.chatBox('open'); });

                /* This event fires when user click on supporter in the contact list.
                 It returns the conversation between admin and supporter */
                $('.supporter-list').on('click', '.supporter', function() {
                    var sId = $(this).data('id');
                    $this.setFocusSupporter(sId);
                });

                $('.select-date').click(function(e) {
                    e.preventDefault();
                    $(this).text('');
                    $this.getTheNextMessages();
                });
                
                $('.partner-filter').keyup(function() {
                    var filter = $(this).val().toLowerCase();
                    $this.filterCustomer(filter);
                });

                /* Recover chatbox status */
                var chatboxopen = $.jStorage.get('chatboxopen');
                if(chatboxopen) {
                    $this.chatBox('open');
                }

                setTimeout(function() {
                    $this.showGlobalUnread();

                    // Refresh status bar
                    $this.refreshStatusBarView(3000);

                    /* Recover conversation */
                    var focussupporter = $.jStorage.get('focussupporter');
                    if(focussupporter) {
                        $this.setFocusSupporter(focussupporter);
                    } else {
                        var firstSupporter = $this.supporterList.find('.supporter').first().data('id');
                        $this.setFocusSupporter(firstSupporter);
                    }

                    $this.updateMessages(TIME_REFRESH);
                }, 1000);
                
                //Fire when recieve new message package
                $.jStorage.listenKeyChange("cusunread", function(key, action){
                    $this.refreshMessageView();
                });
                
                //Fire when user send new message
                $.jStorage.listenKeyChange("cusoutgoing", function(key, action){
                    $this.refreshOutgoing();
                });
            }
        };

        Chatbox.init();
    };
})(jQuery,window,document);