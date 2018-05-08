(function($) {

    // document.ready()
    $(function() {

        var sizeClasses = {
          600: 'decred-pay__big',
          400: 'decred-pay__medium',
          0: 'decred-pay__small'
        };

        var updateComponentResponsiveClasses = function() {
          var $decredPay = $('.decred-pay');
          var decredClass = sizeClasses[0];
          var width = $decredPay.width();
          Object.keys(sizeClasses).forEach(function(size) {
            if (width >= size) {
              decredClass = sizeClasses[size];
            }
          });

            $decredPay
                .removeClass(sizeClasses[0])
                .removeClass(sizeClasses[589])
                .removeClass(sizeClasses[960])
                .addClass(decredClass);
        };

        /**
         * Add responsive classes to the component.
         */
        $(window).resize(updateComponentResponsiveClasses);

        new QRCode(document.getElementById('decred-qrcode'), {
            text: $('#decred-qrcode').attr('data-code'),
            width: 200,
            height: 200,
            correctLevel : QRCode.CorrectLevel.M
        });

        updateComponentResponsiveClasses();

        /**
         * Order status update
         */

        var orderId = $('#decred-order-id').val();
        var orderStatus = parseInt($('#decred-order-status').val());

        var updateOrderStatusRequest = function() {
            $.ajax({
                type: 'get',
                url: ajax_action.url,
                data: 'action=decred_order_status&order_id=' + orderId,
                success: function (response) {
                    var status = parseInt(response);

                    if (status > 0 && orderStatus !== status) {
                        orderStatus = status;

                        $('.decred-pay-status').hide();

                        if (orderStatus === 1) {
                            $('.decred-pay-status__pending').show();
                        }

                        if (orderStatus === 2) {
                            $('.decred-pay-status__processing').show();
                        }

                        if (orderStatus === 3) {
                            $('.decred-pay-status__paid').show();
                        }
                    }
                }
            });
        };

        var updateOrder = function() {
            if (orderStatus !== 3) {
                updateOrderStatusRequest();
                setTimeout(updateOrder, 5000);
            }
        };

        updateOrder();
    });

})(jQuery);
