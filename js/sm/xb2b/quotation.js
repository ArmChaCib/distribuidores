(function($,W,D){
    var Page = {
        initEvents: function() {
            $('.btn-quote-detail').click(function(e) {
                e.preventDefault();
                var quoteId = $(this).data('quote-id');
                $('#dialogQuoteDetails').modal('show');

                // Get quote data
                $.getJSON(APP_URL + 'xb2b/quotation/getQuoteDetailJson',
                    { quote_id: quoteId },
                    function(rs) {
                        if(rs.error == 1) return;

                        var lineItems = '<table class="tbl-quote-details">' +
                            '<thead>' +
                            '<tr>' +
                                '<td>Image</td>'+
                                '<td>Sku</td>'+
                                '<td>Name</td>'+
                                '<td class="number">Price</td>'+
                                '<td class="number">QTY</td>'+
                            '</tr>' +
                            '</thead>' +
                            '<tbody>';

                        $(rs.data.items).each(function(i, o) {
                            lineItems += '<tr>' +
                                            '<td><img class="prod-thumb" src="'+ o.small+'" /></td>'+
                                            '<td>'+ o.sku+'</td>'+
                                            '<td><a target="_blank" href="'+ o.url+'">'+
                                                o.name+'</a></td>'+
                                            '<td class="number">'+ o.price+'</td>'+
                                            '<td class="number">'+ o.ord_qty+'</td>'+
                                        '</tr>';
                        });

                        lineItems += '</tbody>' +
                                '</table>';

                        lineItems += '<table class="tbl-price">' +
                                        '<tr><td><strong>Subtotal</strong></td><td class="right">'+
                                                                rs.data.price.subtotal+'</td></tr>'+
                                        '<tr><td><strong>Grand total</strong></td><td class="right">'+
                                                                rs.data.price.grand_total+'</td></tr>'+
                                    '</table>';

                        if(rs.data.comments.length > 0) {
                            lineItems += '<ul class="list-comments">';
                            $(rs.data.comments).each(function(i, o) {
                                lineItems += '<li><strong>' + o.name + ': </strong>' + o.msg + '</li>';
                            });
                            lineItems += '</uL>';
                        }

                        $('#dialogQuoteDetails .loading-box').hide();
                        $('#dialogQuoteDetails .modal-title').text('Quote #'+quoteId);
                        $('#dialogQuoteDetails .data-holder').html(lineItems);
                    }
                );
            });

            $('#dialogQuoteDetails .btn-close-modal').click(function() {
                $('#dialogQuoteDetails .data-holder').html('');
                $('#dialogQuoteDetails .modal-title').text('Quote');
                $('#dialogQuoteDetails .loading-box').show();
            });

            $('.btn-quote-accept').click(function(e) {
                e.preventDefault();
                $('.checkout-option').hide();
                $('.deny-option').hide();
                $(this).parent().find('.checkout-option').fadeIn(100);
            });

            $('.btn-quote-deny').click(function(e) {
                e.preventDefault();
                $('.checkout-option').hide();
                $('.deny-option').hide();
                $(this).parent().find('.deny-option').fadeIn(100);
            });

            $('.checkout-option .btn-close').click(function(e) {
                e.preventDefault();
                $(this).parent().hide();
            });

            $('.tbl-list-quote .deny-option .btn-close').click(function(e) {
                e.preventDefault();
                $(this).parent().hide();
            });

            $('.tbl-list-quote .deny-option .btn-deny-no').click(function(e) {
                e.preventDefault();
                $(this).parent().hide();
            });

            $('.btn-checkout-quick').click(function(e) {
                e.preventDefault();
                var $this = $(this);
                $this.addClass('processing');
                $this.parent().find('button').attr('disabled', 'disabled');
                $this.parent().find('a').hide();
                $.post(APP_URL + 'xb2b/quotation/gotoCheckoutWithQuote',
                    {quote_id: $this.data('quote-id')},
                    function(rs) {
                        rs = JSON.parse(rs);
                        if(!rs.error) {
                            var modal = $('#dialogCreateOrder');
                            modal.modal('show');
                            $this.parent().find('button').removeAttr('disabled');
                            $this.parent().find('button').removeClass('processing');
                            $this.parent().find('a').show();
                            $this.parent().hide();

                            $.post(QuickOrderURL+'/onepage/placeOrder',
                                { form_key: THEFORMKEY, quote_id: $this.data('quote-id') }
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

                                        $.post(APP_URL + 'xb2b/quotation/setQuoteAccepted',
                                            { quotation_id: $this.data('quotation-id') },
                                            function() {} );
                                    } else {
                                        $('.order-error').html(rs.msg).show();
                                        $('#dialogCreateOrder .btn-close-modal').show();
                                    }
                                });

                        }
                    });
            });

            $('.btn-checkout-normal').click(function(e) {
                e.preventDefault();
                var $this = $(this);
                $this.addClass('processing');
                $this.parent().find('button').attr('disabled', 'disabled');
                $this.parent().find('a').hide();
                $.post(APP_URL + 'xb2b/quotation/gotoCheckoutWithQuote',
                    {quote_id: $this.data('quote-id'), quotation_id: $this.data('quotation-id')},
                    function(rs) {
                        rs = JSON.parse(rs);
                        if(!rs.error) {
                            W.location.href = APP_URL + 'checkout/onepage/';
                        }
                    });
            });

            $('.btn-deny-yes').click(function(e) {
                e.preventDefault();
                var quotationId = $(this).data('quotation-id');
                $(this).addClass('processing');
                $(this).parent().find('button').attr('disabled', 'disabled');
                $(this).parent().find('a').hide();
                $.post(APP_URL + 'xb2b/quotation/denyQuote',
                    { quotation_id: quotationId },
                    function() {
                        W.location.reload();
                    });
            });

            $('#dialogCreateOrder .btn-close-modal').click(function(e) {
                e.preventDefault();
                W.location.reload();
            });

            //Load init quotation
            if(window.location.href.indexOf('#qtid') !== -1) {
                var qtidParam = window.location.href.split('#qtid=');
                var qtid = qtidParam[1];
                $('.btn-quote-detail[data-quotation-id="'+qtid+'"]').trigger('click');
                setTimeout(function() {
                    document.location.hash = '';
                }, 2000);
            }
        }
    };
    $(D).ready(function() {
        Page.initEvents();
    });
})(jQuery, window, document);