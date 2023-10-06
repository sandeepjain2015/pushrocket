(function ($) {
  'use strict';

  $(function () {
    $('.push_rocket_send_notification').on('click', function (e) {
      e.preventDefault();
      var data = {
        action: 'push_rocket_send_notification',
        post_id: $(this).data('post-id'),
      };

      var parent = $(this).parent();

      parent.text('Sending...');

      $.post(ajaxurl, data, function (response) {
        console.log(JSON.parse(response));
        parent.text('Sent');
      });
    });
  });
})(jQuery);
