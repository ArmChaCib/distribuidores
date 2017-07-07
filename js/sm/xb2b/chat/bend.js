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
            statusBar: null, mainBox: null, screen: null, customerList: null, inputMessage: null,
            focusCustomer: null, statusQueue: null, historyList: {},

            getStatusBar: function() { return $('#xb2b_status_bar'); },

            getMainBox: function() { return $('#xb2b_main_box'); },

            getScreen: function() { return $('#xb2b_main_box .screen'); },

            getCustomerList: function() { return $('#xb2b_main_box .customer-list'); },

            getInputMessage: function() { return $('#input_message'); },

            saveGlobalUnread: function(actorId, count) {
                var globalUnread = $.jStorage.get('adglobalunread');
                if(!isNotEmpty(globalUnread)) globalUnread = {};
                globalUnread[actorId] = count;
                $.jStorage.set('adglobalunread', globalUnread);
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
                var globalUnread = $.jStorage.get('adglobalunread');
                if(isNotEmpty(globalUnread)) {
                    for(var actorId in globalUnread) {
                        var value = globalUnread[actorId];
                        if(value > 0) {
                            var unreadElement = this.customerList.find('.customer[data-id="'+actorId+'"] .unread');
                            unreadElement.text(value).addClass('active');
                        }
                    }
                }
            },

            getMsgList: function(customerId) {
                return this.screen.find('.msg-list-'+customerId);
            },

            updateMessages: function(time) {
                var $this = this;
                $this.log('updateMessages | ' + time);

                if($this.statusQueue === null) {
                    var customers = [];
                    $this.customerList.find('.customer').each(function(i,o){
                        customers.push($(o).data('id'));
                    });
                    $this.statusQueue = customers;
                } else {
                    var customers = $this.statusQueue;
                }

                $.post(BASE_URL + 'xchat/n?isAjax=true',
                    { form_key: FORM_KEY, clist: customers }, function(rs) {
                        if(!rs.error) {
                            if(Object.keys(rs.data.message).length > 0) {
                                $.jStorage.set('adunread', rs.data.message);
                                setTimeout(function() {
                                    $.jStorage.set('adunread', 0);
                                }, 500);
                            }
                            
                            $this.customerList.find('.customer').each(function(i,o) {
                                var uid = $(o).data('id');
                                if(rs.data.online && rs.data.online[uid]) {
                                    $(o).addClass('online');
                                } else {
                                    $(o).removeClass('online');
                                }
                            });  
                        }
                    }, 'json');

                setTimeout(function() {
                    $this.updateMessages(time);
                }, time);
            },

            getSelectDate: function(id) {
                return $('.date-navigator[data-id="'+id+'"] .select-date');
            },

            saveHistory: function(id, history) {
                $.jStorage.set('csdate'+id, history);
            },

            getLastHistory: function(id) {
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
                $this.log('getTheNextMessages');
                var orgDate = $('.date-navigator[data-id="'+$this.focusCustomer+'"] .select-date').data('org');
                var customerId = $this.focusCustomer;
                if(isNotEmpty(orgDate)) {
                    $.post(BASE_URL + 'xchat/r?isAjax=true',
                        {cid : customerId, form_key: FORM_KEY, day: orgDate}, function(rs) {
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

                                var listMessage = $this.getMsgList(customerId);
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
                                $this.scrollListMessageToTop($this.getMsgList(customerId));

                                var lastDate = $this.getLastHistory(customerId);
                                if(lastDate === false) {
                                    $this.getSelectDate(customerId).parent().hide();
                                } else {
                                    var displayDate = lastDate.split('_').join('/');
                                    $this.getSelectDate(customerId).text(displayDate);
                                    $this.getSelectDate(customerId).data('org', lastDate);
                                }
                            }
                        }, 'json');
                }
            },

            getMessagesByCustomer: function(customerId, day) {
                var $this = this;
                $this.log('getMessagesByCustomer | ' + customerId + '|' + day);
                $.post(BASE_URL + 'xchat/r?isAjax=true',
                    {cid : customerId, form_key: FORM_KEY, day: day}, function(rs) {
                        if(!rs.error) {
                            var collection = [], tmp = rs.data.data;
                            for(var i=0; i<tmp.length;i++) {
                                if(tmp[i] ==='' || tmp[i] === null) continue;
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
                                    $this.getSelectDate(customerId).text(date);
                                    $this.getSelectDate(customerId).attr('data-org', o);
                                } else {
                                    historyList.push(o);
                                }
                                loop++;
                            });
                            
                            if(rs.data.date.length === 0) {
                                $('.date-navigator').hide();
                            }
                            
                            $this.saveHistory(customerId, historyList);

                            var listMessage = $this.getMsgList(customerId);
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

                            $this.scrollListMessageToBottom($this.getMsgList(customerId));
                        }
                    }, 'json');
            },

            setFocusCustomer: function(id) {
                var $this = this;
                $this.log('setFocusCustomer');
                $this.focusCustomer = id;
                $.jStorage.set('focuscustomer', id);

                var tab = $this.getMsgList(id);

                var oldLoad = false;
                if(!tab.hasClass('loaded')) {
                    $this.getMessagesByCustomer(id);
                    tab.addClass('loaded');
                } else {
                    oldLoad = true;
                }

                var name = $this.customerList.find('li[data-id="'+id+'"] .cname').text();
                $('.focus-name').text(name);
                $('.current-user').show();
                $('.date-navigator').hide();
                $('.date-navigator[data-id="'+id+'"]').show();

                $this.customerList.children().removeClass('active');
                $this.customerList.find('.customer[data-id="'+id+'"]').addClass('active');
                $this.customerList.find('.customer[data-id="'+id+'"] .unread').removeClass('active').text('0');
                $this.saveGlobalUnread(id, 0);

                $this.screen.find('.msg-list').hide();
                tab.show();
                if(oldLoad) $this.scrollListMessageToBottom(tab);
            },

            sendMessage: function(callback) {
                var $this = this;
                $this.log('sendMessage');
                var msg = $this.inputMessage.val();
                var focuscustomer = $this.focusCustomer;

                if(!focuscustomer || msg === '') {
                    return false;
                }

                var message = {
                    actorType: 1,
                    actorId: ADMIN_ID,
                    actorName: ADMIN_NAME,
                    time: new Date().getTime(),
                    content: msg
                };
                
                //Send message
                $.post(BASE_URL + 'xchat/s',
                    { msg: msg, customer_id: focuscustomer, form_key: FORM_KEY },
                    function(rs) {
                        if(callback !== undefined)
                            callback(rs);
                    });

                $.jStorage.set('adminoutgoing', message);
                setTimeout(function() {
                    $.jStorage.set('adminoutgoing', 0);
                }, 500);
                
                //Disable input in 1000ms
                $this.inputMessage.attr({placeholder: 'sending...', disabled  : 'disabled'});
                
                setTimeout(function() {
                    $this.inputMessage.val('').removeAttr('disabled');
                    $this.inputMessage.val('').attr('placeholder', '');
                    $this.inputMessage.focus();
                }, 1000);
            },
            
            refreshMessageView: function() {
                var $this = this;
                $this.log('refreshMessageView');
                var data = $.jStorage.get('adunread');
                if(data == 0) return;
                var newMessages = data;
                if(isNotEmpty(newMessages)) {
                    for(var custId in newMessages) {
                        var messages = newMessages[custId].data;
                        if(messages) {
                            if(parseInt($this.focusCustomer) !== parseInt(custId)
                                    || !$.jStorage.get('chatboxopen')) {
                                var unreadElement = $this.customerList
                                    .find('.customer[data-id="'+custId+'"] .unread');
                                var old = parseInt(unreadElement.text());
                                if(isNaN(old)) old = 0;
                                old += newMessages[custId].data.length;
                                unreadElement.text(old);
                                $this.saveGlobalUnread(custId, old);
                                if(old > 0) unreadElement.addClass('active');
                            }

                            var listMessage = $this.getMsgList(custId);
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
                }
                
            },
            
            refreshOutgoing: function() {
                var $this = this;
                var message = $.jStorage.get('adminoutgoing');
                if(message == 0) return;
                var listMessage = $this.getMsgList($this.focusCustomer);
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
                var globalunread = $.jStorage.get('adglobalunread');
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
                var customers = $this.customerList.find('.cname');

                if(filter === '') {
                    $this.customerList.find('.customer').each(function() {
                        $(this).removeClass('filtered');
                    });
                    return;
                } else {
                    customers.each(function(i, o) {
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
//            $this.msg_list      = $this.getMsgList();
                $this.customerList  = $this.getCustomerList();
                $this.inputMessage  = $this.getInputMessage();

                $('body').append($this.statusBar);
                $('body').append($this.mainBox);


                /* This event fires when user click on Send button. It will send current message and clear text box */
                $('#xb2b_main_box .btn-send').click(function(e) {
                    e.preventDefault();
                    $this.sendMessage();
                });

                $('#input_message').keydown(function (e) {
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
                $('body').on('click', '.btn-close-chatbox', function(e) {
                    e.preventDefault(); $this.chatBox('close'); });

                /* This event fires when user click on status bar. It will show chat box */
                $('#xb2b_status_bar').click(function(e) {
                    e.preventDefault(); $this.chatBox('open'); });

                /* This event fires when user click on customer in the contact list.
                 It returns the conversation between admin and customer */
                $('.customer-list').on('click', '.customer', function() {
                    var custId = $(this).data('id');
                    $this.setFocusCustomer(custId);
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
                    var focuscustomer = $.jStorage.get('focuscustomer');
                    if(focuscustomer) {
                        $this.setFocusCustomer(focuscustomer);
                    } else {
                        var firstCustomer = $this.customerList.find('.customer').first().data('id');
                        $this.setFocusCustomer(firstCustomer);
                    }

                    $this.updateMessages(TIME_REFRESH);
                }, 1000);
                
                //Fire when recieve new message package
                $.jStorage.listenKeyChange("adunread", function(key, action){
                    $this.refreshMessageView();
                });
                
                //Fire when user send new message
                $.jStorage.listenKeyChange("adminoutgoing", function(key, action){
                    $this.refreshOutgoing();
                });
            }
        };

        /* Setup environment */
        Chatbox.init();

    };
})(jQuery, window, document);