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
            console.log(width);
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

    });

})(jQuery);
