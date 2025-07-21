jQuery(document).ready(function ($) {
  $("#paypay-form").on("submit", function (e) {
    e.preventDefault();
    var $form = $(this);
    var $message = $("#paypay-message");
    $message.html("<p>Processing...</p>");

    $.ajax({
      url: PayPayData.ajax_url,
      type: "POST",
      data: $form.serialize(),
      success: function (response) {
        if (response.success) {
          $message.html("<p>" + response.data.message + "</p>");
          if (response.data.redirect) {
            window.location.href = response.data.redirect;
          }
        } else {
          $message.html("<p>Error: " + response.data.message + "</p>");
        }
      },
      error: function () {
        $message.html("<p>Error: Unable to process payment.</p>");
      },
    });
  });
});
