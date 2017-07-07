var jQuery = jQuery.noConflict();

String.prototype.replaceAll = function (find, replace) {
    var str = this;
    return str.replace(new RegExp(find, 'g'), replace);
};

function nl2br(e,t){var n=t||typeof t==="undefined"?"<br "+"/>":"<br>";return(e+"").replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g,"$1"+n+"$2")}

var QuickOrder = null;

(function($){
    QuickOrder = {
        onSearching : false,
        getItemHtml: function(product) {
            var preQty = product.ord_qty != undefined ? product.ord_qty : 1;
            var newItem = '<tr class="prod-'+product.id+' item" data-item-id="'+product.quote_item_id+'" data-prod-id="'+product.id+'">'+
                '<td><img class="prod-thumb" src="'+product.small+'"></td>'+
                '<td><a target="_blank" href="'+product.url+'">'+product.name+'</a></td>'+
                '<td>'+
                '<div class="qty-box">'+
                '<input type="text" placeholder="0" value="'+preQty+'" data-id="'+product.id+'" data-inventory-qty="'+product.stock_qty+'" class="init-qty clear-fix qty-'+product.id+'">'+
                '<button class="btn btn-primary btn-sm btn-update-qty" data-pid="'+product.id+'" disabled data-item-id="'+product.quote_item_id+'">Update</button>'+
                '</div>'+
                '</td>'+
                '<td>'+
                '<span class="price-'+product.id+' unit-price">'+product.price+'</span>'+
                '</td>'+
                '<td>'+
                '<span class="total-price-'+product.quote_item_id+' total-price">'+product.t_price+'</span>'+
                '</td>'+
                '<td><a class="btn-remove btn-remove2 btn-remove-item" data-item-id="'+product.quote_item_id+'" data-id="" title="Remove Item" href="">Remove Item</a></td>'+
                '</tr>';
            return newItem;
        },
        appendNewItem: function(item) {
            var product = item.product,
                quote = item.quote;
            var itemExist = $('tr[data-prod-id='+product.id+']').length > 0;
            if(itemExist) {
                var currQty = $('.qty-'+product.id).val();
                $('.qty-'+product.id).val(parseInt(currQty) + 1);
                $('.total-price-'+product.quote_item_id).text(product.t_price);
            } else {
                var newItem = this.getItemHtml(product);
                $('.tbl-selected-products tbody').append(newItem);
            }
            $('.top-grand-total').text(quote.grand_total);
            $('.top-subtotal').text(quote.subtotal);
            $('.bot-subtotal').text(quote.subtotal);
            $('.pre-text').hide();
            $('.btn-delete-all-rows').removeAttr('disabled');
        },
        appendMultiItem: function(item) {
            var products = item.products,
                quote = item.quote;
            for(var i=0; i<products.length;i++) {
                var itemExist = $('tr[data-prod-id='+products[i].id+']').length > 0;
                if(itemExist) {
                    var currQty = $('.qty-'+products[i].id).val();
                    $('.qty-'+products[i].id).val(parseInt(currQty) + products[i].ord_qty);
                    $('.total-price-'+products[i].quote_item_id).text(products[i].t_price);
                } else {
                    var newItem = this.getItemHtml(products[i]);
                    $('.tbl-selected-products tbody').append(newItem);
                }
            }
            $('.top-grand-total').text(quote.grand_total);
            $('.top-subtotal').text(quote.subtotal);
            $('.bot-subtotal').text(quote.subtotal);
            $('.pre-text').hide();
            $('.btn-delete-all-rows').removeAttr('disabled');
        },
        getComment: function() {
            var prevComment = $.jStorage.get('order_comment');
            return prevComment;
        },
        setComment: function(msg) {
            $.jStorage.set('order_comment', msg);
        },
        clearComment: function() {
            $.jStorage.set('order_comment', '');
        }
    };

    $(document).ready(function(){
        $('.tbl-selected-products').on('keyup', '.init-qty', function() {
            var qtyInStock = parseInt($(this).data('inventory-qty'));
            var pid = $(this).data('pid');
            var enteredQty = parseInt($(this).val());
            if(enteredQty > qtyInStock && XB2B.enable_backorder == 0) {
                $(this).val(qtyInStock);
            } else if(XB2B.enable_backorder > 0 && !isNaN(XB2B.max_sale_qty) &&
                      XB2B.max_sale_qty > 0 && enteredQty > XB2B.max_sale_qty) {
                $(this).val(XB2B.max_sale_qty);
            } else if(enteredQty <= 0) {
                $(this).val(1);
            }
            $(this).next().removeAttr('disabled');
            $('.btn-update-all-rows').removeAttr('disabled');
        });

        $('.tbl-selected-products').on('keydown', '.init-qty', function(e) {
            var key = e.charCode || e.keyCode || 0;
            return (
                key == 8 || key == 9 || key == 13 || key == 46 || key == 110 ||
                key == 190 || (key >= 35 && key <= 40) || (key >= 48 && key <= 57) ||
                (key >= 96 && key <= 105));
        });

        $('.tbl-selected-products').on('click', '.btn-update-qty', function(e) {
            e.preventDefault();
            var that = this;
            var newQty = $(this).prev().val();
            var itemId = $(this).data('item-id');
            var pId    = $(this).data('pid');
            var cartData = {};
                cartData[itemId] = {qty: newQty, pid: pId};

            $(this).prev().attr('disabled', 'disabled');
            $(this).text('Updating...').attr('disabled', 'disabled');
            $.post(QuickOrderURL+'/QuickOrder/updateQuantity',
                {
                    update_cart_action  : 'update_qty',
                    cart: cartData,
                    form_key: $('#the_formkey').val()
                },
                function(rs) {
                    rs = JSON.parse(rs);
                    if(!rs.error) {
                        var quote = rs.quote;
                        $('.top-grand-total').text(quote.grand_total);
                        $('.top-subtotal').text(quote.subtotal);
                        $('.bot-subtotal').text(quote.subtotal);
                        $('.total-price-'+itemId).text(rs.product.t_price);
                    }
                    $(that).text('Update');
                    $(that).prev().removeAttr('disabled');
                }
            );
        });

        $('.tbl-selected-products').on('click', '.btn-remove-item', function(e){
            e.preventDefault();
            var that = this;
            var itemId = $(this).data('item-id');
            var cartData = {};
                cartData[itemId] = {qty: 0};
            $.post(QuickOrderURL+'/QuickOrder/deleteOne',
                {
                    item_id     : $(that).data('item-id'),
                    form_key    : $('#the_formkey').val()
                },
                function(rs) {
                    rs = JSON.parse(rs);
                    if(!rs.error) {
                        var quote = rs.quote;
                        $('.top-grand-total').text(quote.grand_total);
                        $('.top-subtotal').text(quote.subtotal);
                        $('.bot-subtotal').text(quote.subtotal);
                    }
                }
            );
            $(that).closest('tr').find('td').fadeOut('slow', function() {
                $(this).parent().remove();
            });
        });

        $('.btn-open-import-csv').click(function(e) {
            e.preventDefault();
        });

        $('#dropzone .browse').click(function(e){
            e.preventDefault();
            $(this).parent().find('input').click();
        });

        $('#frm_upload_csv').fileupload( {
            dropZone: $('#dropzone'),
            dataType: 'json',
            add: function (e, data) {
                $('#dropzone .browse').attr('disabled', 'disabled')
                    .children('span').text('Importing').next().removeClass('deactivate');
                var jqXHR = data.submit();
            },

            progress: function(e, data) {
//                var progress = parseInt(data.loaded / data.total * 100, 10);
//                if(progress == 100){
//                    $('#dropzone .browse').removeAttr('disabled')
//                                          .children('span').text('Browse')
//                                          .next().addClass('deactivate');
//                }
            },
            done: function (e, data) {
                var result = data.result;
                if(data.result.error == 1) {
                    $('#dialogImportError .error-list').children().remove();
                    $(result.msg).each(function(i, o) {
                        $('#dialogImportError .error-list').append('<li>'+o+'</li>');
                    });
                    $('#dialogImportError').modal('show');
                }
                QuickOrder.appendMultiItem(data.result);
                $('#dropzone .browse').removeAttr('disabled')
                    .children('span').text('Browse')
                    .next().addClass('deactivate');
            },
            fail:function(e, data){}
        });

        $('.btn-open-import-csv').click(function(e) {
            e.preventDefault();
            $('#frm_upload_csv').fadeIn(100);
        });

        var enterSuggestion = function() {
            var search = $('#txt_search').val(), by = $('.slb-search-type').val();
            if(search == '') return;

            var strToHighlight = function(inStr) {
                return inStr.replaceAll(search, '<span class="highlight">'+search+'</span>');
            };

            $.getJSON(QuickOrderURL+'/QuickOrder/searchProduct',
                { b: by , s: search, form_key: $('#the_formkey').val() },
                function(rs) {
                    var htmlResult = '';
                    if(rs.products.length > 0) {
                        rs.products.each(function(o, i) {
                            var product = o;
                            var newItem = '<li class="clearfix" data-id="'+ o.id+'">' +
                                '<span class="prod-img"><img src="'+product.image+'"/></span>' +
                                '<div class="ids clearfix"><span class="prod-id">'+strToHighlight(product.id)+'</span>' +
                                '<span class="prod-sku">'+strToHighlight(product.sku)+'</span></div>' +
                                '<span class="prod-name">'+strToHighlight(product.name)+'</span></li>';
                            htmlResult  += newItem;
                        });
                        $('.suggestion .result').html(htmlResult);
                        QuickOrder.onSearching = true;
                        if(rs.products.length == 1 && XB2B.lucky_search == 1) {
                            $('.suggestion .result li').first().addClass('active');
                            $('.suggestion .result li.active').trigger('click');
                        }
                    } else {
                        $('.suggestion .result').html('<li>No result</li>');
                    }
                    if(rs.products.length == 1 && XB2B.lucky_search == 1) {
                        $('.suggestion').slideUp(100);
                    } else {
                        $('.suggestion').slideDown(100);
                    }
                });
        };

        $('#txt_search').keyup($.debounce(function(e) {
            if($.inArray(e.keyCode, [37,38,39,40]) > -1) return false;
            if(e.keyCode == 13) {
                return false;
            }
            enterSuggestion();
        }, 500));

        $('#search-form').submit(function(e) {
            e.preventDefault();
            var activeItem = $('.suggestion .result li.active');
            if(activeItem.length > 0) {
                $('.suggestion .result li.active').trigger('click');
            } else {
                enterSuggestion();
            }
        });

        $('html').click(function() {
            $('.suggestion').hide();
            QuickOrder.onSearching = false;
        });

        $('.suggestion').click(function(e){
            e.stopPropagation();
        });

        $('body').keyup(function(e) {
            if(QuickOrder.onSearching) {
                if(e.keyCode != 38 && e.keyCode != 40) return;

                var active = $('.suggestion .result li.active'),
                    hasActive = active.length > 0, next = null, prev = null;

                if(!hasActive) {
                    active = $('.suggestion .result li').last();
                }

                next = active.next();
                if(next.length == 0) {
                    next = $('.suggestion .result li').first();
                }

                prev = active.prev();
                if(prev.length == 0) {
                    prev = $('.suggestion .result li').last();
                }

                $('.suggestion .result li.active').removeClass('active');
                if(e.keyCode == 38) {
                    prev.addClass('active');
                } else if(e.keyCode == 40) {
                    next.addClass('active');
                }
            }
        });

        $('.suggestion .result').on('mouseenter', 'li', function() {
            $('.suggestion .result li.active').removeClass('active');
            $(this).addClass('active');
        });

        $('.suggestion .result').on('click', 'li.active', function() {
            var pid = $(this).data('id');
            $('.btn-search-product').val('Searching...').attr('disabled', 'disabled');
            $('.suggestion').slideUp(100);
            $('#txt_search').attr('disabled', 'disabled');
            $.post(QuickOrderURL+'/QuickOrder/selectProduct',
                { by: 'id', s: pid, form_key: $('#the_formkey').val() },
                function(rs) {
                    rs = JSON.parse(rs);
                    if(!rs.error) {
                        var quote = rs.quote;
                        QuickOrder.appendNewItem(rs);
                        $('.top-grand-total').text(quote.grand_total);
                        $('.top-subtotal').text(quote.subtotal);
                        $('.bot-subtotal').text(quote.subtotal);
                    }
                    $('.suggestion').find('li').remove();
                    QuickOrder.onSearching = false;
                    $('.btn-search-product').val('Search').removeAttr('disabled');
                    $('#txt_search').val('').removeAttr('disabled');
                }
            );
        });

        $('.btn-place-order').click(function(e) {
            e.preventDefault();
            if($('.tbl-selected-products tbody tr').length == 0) {
                return false;
            }
            var modal = $('#dialogCreateOrder');
                modal.modal('show');
            $('#order_loading').fadeIn('fast');
            var ordComment = QuickOrder.getComment();
            if(ordComment == undefined || ordComment == null) ordComment = '';
            ordComment = nl2br(ordComment);

            $.post(QuickOrderURL+'/onepage/placeOrder',
                {
                    form_key: $('#the_formkey').val(),
                    ord_comment: ordComment
                }
                , function(rs) {
                    rs = JSON.parse(rs);
                    $('#order_loading').hide();
                    if(rs.error == 0) {
                        modal.find('.modal-title').text('YOUR ORDER HAS BEEN RECEIVED.');
                        var modalBody = '<div>' +
                            '<h2>THANK YOU FOR YOUR PURCHASE!</h2>'+
                            'Your order # is: <a target="_blank" href="'+TheMageURL+'sales/order/view/order_id/'+rs.data.order_id+'/">'+rs.data.order_code+'</a>.'+
                            '<br />You will receive an order confirmation email with details of your order and a link to track its progress.'+
                            '<br />Click <a target="_blank" href="'+TheMageURL+'sales/order/print/order_id/'+rs.data.order_id+'/">here</a> to print a copy of your order confirmation.</div>';
                        modal.find('.modal-body').html(modalBody);
                        $('#dialogCreateOrder .btn-close-modal').show();
                        $('#dialogCreateOrder .btn-continue-shop').show();
                        QuickOrder.clearComment();
                    } else {
                        $('.order-error').html(rs.msg).show();
                        $('#dialogCreateOrder .btn-close-modal').show();
                    }
            });
        });

        $('.btn-continue-shop').click(function(e) {
            e.preventDefault();
            window.location.href = TheMageURL;
        });

        $('.btn-close-modal').click(function(e) {
            e.preventDefault();
            window.location.reload();
        });

        $('.btn-update-all-rows').click(function(e) {
            e.preventDefault();
            var that = this;
            var update_queue = {};
            $('.btn-update-qty').each(function(i,o) {
                var qtyInput = $(o).prev();
                update_queue[$(o).data('item-id')] = {qty: qtyInput.val(), pid: qtyInput.data('id')};
            });
            $(that).attr('disabled', 'disabled').text('Updating...');
            $('.btn-update-qty').attr('disabled', 'disabled');
            $.post(QuickOrderURL+'/QuickOrder/updateManyItem', {
                    form_key: $('#the_formkey').val(),
                    update_cart_action  : 'update_qty',
                    cart    : update_queue
                }
                , function(rs) {
                    rs = JSON.parse(rs);
                    if(!rs.error) {
                        var quote = rs.quote;
                        var products = rs.products;
                        $('.top-grand-total').text(quote.grand_total);
                        $('.top-subtotal').text(quote.subtotal);
                        $('.bot-subtotal').text(quote.subtotal);
                        for(var i in products) {
                            var product = products[i];
                            $('.total-price-'+product.qid).text(product.t_price);
                        }
                    }
                    $(that).text('Update all');
                    $(that).attr('disabled', 'disabled');
                });
        });

        $('.btn-delete-all-rows').click(function(e) {
            e.preventDefault();
            var that = this;
            $(this).attr('disabled', 'disabled');
            $(this).text('Deleting...');
            $.post(QuickOrderURL+'/QuickOrder/deleteAll', {
                    form_key: $('#the_formkey').val()
                }
                , function(rs) {
                    window.location.reload();
                });
        });

        $('.txt-order-comment').keyup(function() {
            QuickOrder.setComment($(this).val());
        });

        $('.btn-request-a-quote').click(function(e) {
            e.preventDefault();
            var comment = $('.txt-order-comment').val();
            if(comment == '') {
                alert('Please enter comment before send the quote');
                $('.txt-order-comment').focus();
                return false;
            }

            if($('.tbl-selected-products tbody tr').length === 0) {
                return false;
            }

            $('#dialogRequestQuote').modal('show');

            $.post(TheMageURL+'/xb2b/quotation/requestCustomerQuote', {
                    form_key: $('#the_formkey').val(),
                    comment: nl2br(comment)
                }
                , function(rs) {
                    rs = JSON.parse(rs);

                    $('#dialogRequestQuote .request-message').remove();
                    $('#dialogRequestQuote .modal-body').append('<h3 class="quote-requested-success">Your quote width id <a target="_blank" href="'+TheMageURL+'xb2b/quotation#qtid='+rs.quotation_id+'">'+rs.quotation_id+'</a> has been created successfully</h3>');
                    $('#dialogRequestQuote .btn-send-quote-request').hide();
                    $('#dialogRequestQuote .btn-close-request').text('Close').show();
                    $('#dialogRequestQuote .btn-close-request').attr('qtid', rs.quotation_id);
//                    window.location.reload();
                });
        });

//        $('#dialogRequestQuote .btn-send-quote-request').click(function(e) {
//            e.preventDefault();
//            var $this = $(this);
//            $.post(TheMageURL+'/xb2b/quotation/requestCustomerQuote', {
//                form_key: $('#the_formkey').val(),
//                comment: nl2br($('.request-message').val())
//                }
//                , function(rs) {
//                    $('#dialogRequestQuote .request-message').remove();
//                    $('#dialogRequestQuote .modal-body').append('<h3 class="quote-requested-success">Your quote has been created successfully</h3>');
//                    $('#dialogRequestQuote .btn-send-quote-request').hide();
//                    $('#dialogRequestQuote .btn-close-request').text('Close');
////                    window.location.reload();
//                });
//
//        });

        $('#dialogRequestQuote .btn-close-request').click(function(e) {
            e.preventDefault();
            QuickOrder.clearComment();
            var qtid = $(this).attr('qtid');
            window.location.href = TheMageURL+'xb2b/quotation#qtid='+qtid;
        });

        //Load order comment
        var prevComment = QuickOrder.getComment();
        if(prevComment != undefined && prevComment != null) {
            $('.txt-order-comment').text(prevComment);
        }

        //Focus to search box
        $('#txt_search').focus();
    });
})(jQuery);