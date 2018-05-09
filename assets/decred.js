(function($) {

    function copyTextToClipboard(text) {
      var textArea = document.createElement("textarea");

      // Place in top-left corner of screen regardless of scroll position.
      textArea.style.position = 'fixed';
      textArea.style.top = 0;
      textArea.style.left = 0;

      // Ensure it has a small width and height. Setting to 1px / 1em
      // doesn't work as this gives a negative w/h on some browsers.
      textArea.style.width = '2em';
      textArea.style.height = '2em';

      // We don't need padding, reducing the size if it does flash render.
      textArea.style.padding = 0;

      // Clean up any borders.
      textArea.style.border = 'none';
      textArea.style.outline = 'none';
      textArea.style.boxShadow = 'none';

      // Avoid flash of white box if rendered for any reason.
      textArea.style.background = 'transparent';


      textArea.value = text;

      document.body.appendChild(textArea);
      textArea.select();
      document.execCommand('copy');
      document.body.removeChild(textArea);
    }

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
            width: 250,
            height: 250,
            correctLevel : QRCode.CorrectLevel.M
        });

        updateComponentResponsiveClasses();

        /**
         * Clipboard copy
         */
        $('.decred-icon_copy').click(function() {
            copyTextToClipboard($(this).attr('data-text'));
        });

        /**
         * Order status update
         */

        var orderId = $('#decred-order-id').val();
        var orderStatus = $('#decred-order-status').val();
        var orderTxid = $('#decred-order-txid').val();

        var updateOrderStatusRequest = function() {
            $.ajax({
                type: 'get',
                url: ajax_action.url,
                dataType: 'json',
                data: 'action=decred_order_status&order_id=' + orderId,
                success: function (response) {
                    if (response.status && orderStatus !== response.status) {
                        orderStatus = response.status;
                        $('.decred-pay-status__pending').toggle(orderStatus === 'pending');
                        $('.decred-pay-status__on-hold').toggle(orderStatus === 'on-hold');
                        $('.decred-pay-status__processing').toggle(orderStatus === 'processing');
                    }

                    if (response.txid && response.txid !== orderTxid) {
                        orderTxid = response.txid;

                        $('.decred-pay-row__txid')
                            .find('.decred-pay-info-field span').text(orderTxid).end()
                            .find('.decred-pay-info-field i').attr('data-text', orderTxid).end()
                            .show();

                    }

                }
            });
        };

        var updateOrder = function() {
            if (orderStatus !== 'processing') {
                updateOrderStatusRequest();
                setTimeout(updateOrder, 10000);
            }
        };

        updateOrder();
    });

})(jQuery);
