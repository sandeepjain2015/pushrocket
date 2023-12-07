(function ($) {
  'use strict';

  $(function () {
    var pushrocket_nonce = pushrocket_vars.nonce;
    $('.push_rocket_send_notification').on('click', function (e) {
      e.preventDefault();
      var data = {
        action: 'push_rocket_send_notification',
        pushrocket_nonce: pushrocket_nonce, // Pass the nonce in the data.
        post_id: $(this).data('post-id'),
      };
      var parent = $(this).parent();
      parent.text('Sending...');
      $.post(ajaxurl, data, function (response) {
        parent.text(response.data.message);
      });
    });
  });
})(jQuery);
