(function($){
    var BulkOrder = {
        _getItems: function() {
            var ords = $.jStorage.get('bulk_orders');
            if(!ords) ords = {};
            return ords;
        },
        _saveItems: function(ords) {
            if(!ords) ords = {};
            $.jStorage.set('bulk_orders', ords);
            return ords;
        },
        _clearItems: function() {
            this._saveItems({});
            this.updateBtnAddAllState();
            this.updateBtnClearAllState();
        },
        saveItemQty: function(pid, qty) {
            var ords = this._getItems();
//            if(!isNaN(ords[pid])) {
//                ords[pid] = parseInt(ords[pid]) + qty;
//            } else {
                ords[pid] = qty;
//            }
            this._saveItems(ords);
        },
        updateBtnAddAllState: function() {
            var ords = BulkOrder._getItems();
            var total = 0;
            for(var i in ords) {
                total += parseInt(ords[i]);
            }
            if(total > 0) {
                $('.btn-add-products-to-cart').removeAttr('disabled');
            } else {
                $('.btn-add-products-to-cart').attr('disabled', 'disabled');
            }
        },
        updateBtnClearAllState: function() {
            var ords = BulkOrder._getItems();
            var total = 0;
            for(var i in ords) {
                total += parseInt(ords[i]);
            }
            if(total > 0) {
                $('.btn-clear-products').removeAttr('disabled');
            } else {
                $('.btn-clear-products').attr('disabled', 'disabled');
            }
        },
        makeBtnLoading: function(btn_selector) {
            $(btn_selector).attr('disabled', 'disabled');
            $(btn_selector + ' .add-all').html('Processing');
            /*$(btn_selector + ' .loading').addClass('active');*/
        },
        removeBtnLoading: function(btn_selector) {
            $(btn_selector + ' .add-all').html('Add all');
            /*$(btn_selector + ' .loading').addClass('deactivate');*/
        },
        mouse: { x: 0, y: 0}

    };

    $.fn.onQtyEntered = function () {
        this.each(function () {
            $(this).keyup(function (e) {
                var qtyInStock = parseInt($(this).data('inventory-qty'));
                var pid = $(this).data('pid');
                var enteredQty  = parseInt($(this).val());
                var backorder   = parseInt($(this).data('backorder'));
                var maxQtySale  = parseInt($(this).data('maxsaleqty'));

                if(enteredQty > qtyInStock && backorder == 0) {
                    var setQty = 0;
                    if(qtyInStock <= 0) {
                        setQty = 0;
                    } else {
                        setQty = qtyInStock;
                    }
                    $(this).val(setQty);
                    BulkOrder.saveItemQty(pid, setQty);
                } else if(backorder > 0 && !isNaN(maxQtySale) && enteredQty > maxQtySale) {
                    $(this).val(maxQtySale);
                    BulkOrder.saveItemQty(pid, maxQtySale);
                } else {
                    var qty = $(this).val() != '' ? $(this).val() : '0';
                    BulkOrder.saveItemQty(pid, parseInt(qty));
                }
                BulkOrder.updateBtnAddAllState();
                BulkOrder.updateBtnClearAllState();
            });
        });
        return this;

    };

    $(document).ready(function(){
        $(window).mousemove(function(e) {
            BulkOrder.mouse.x = e.pageX;
            BulkOrder.mouse.y = e.pageY;
        });
        //Add order quantity handles
        $('.ord-quantity').enterNumberOnly().onQtyEntered();

        //Toolbar actions
        $('.btn-add-products-to-cart').click(function() {
            var form_key = $(this).data('form-key');
            var items = BulkOrder._getItems();
            BulkOrder.makeBtnLoading('.btn-add-products-to-cart');
            $.post(
                APP_URL + 'xb2b/cart/add',
                {form_key: form_key, init_orders: items}
                , function() {
                    BulkOrder._clearItems();
                    location.href = APP_URL + 'checkout/cart';
                }
            );
        });

        $('.btn-clear-products').click(function(e) {
            e.preventDefault();
            $('.ord-quantity').each(function(){
                $(this).val('');
                BulkOrder._clearItems();
                BulkOrder.updateBtnClearAllState();
            });
        });

        $('.child-item-url').mouseover(function() {
            var imgSrc = $(this).data('thumb');
            var thumbElement = $('<div class="thumb-box"><img src="'+imgSrc+'" /></div>');
            thumbElement.appendTo('body');
            thumbElement.css({
                top: BulkOrder.mouse.y - 50 + 'px',
                left: BulkOrder.mouse.x - 120 + 'px'
            });
        })
        .mouseout(function() {
            $('.thumb-box').fadeOut(100, function() {
                $(this).remove();
            });
        });

        //Pre-load order quantity
        var ords = BulkOrder._getItems();
        $('.ord-quantity').each(function(){
            var pid = $(this).data('pid');
            $(this).val(ords[pid]);
        });

        BulkOrder.updateBtnAddAllState();
        BulkOrder.updateBtnClearAllState();

    });
})(jQuery);