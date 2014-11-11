// declare namespace
var user_sql = user_sql || {};

/**
 * init admin settings view
 */
user_sql.adminSettingsUI = function() {

  if ($('#sql').length > 0) {
    // enable tabs on settings page
    $('#sql').tabs();

    $('#sqlSubmit').click(function(event) {
      event.preventDefault();

      var self = $(this);
      var post = $('#sqlForm').serialize();
      $('#sql_update_message').show();
      $('#sql_success_message').hide();
      $('#sql_error_message').hide();
      // Ajax foobar
      $.post(OC.filePath('user_sql', 'ajax', 'settings.php'), post, function(data) {
        $('#sql_update_message').hide();
        if (data.status == 'success') {
          $('#sql_success_message').html(data.data.message);
          $('#sql_success_message').show();
          window.setTimeout(function() {
              $('#sql_success_message').hide();
          }, 10000);
        } else {
          $('#sql_error_message').html(data.data.message);
          $('#sql_error_message').show();
        }
      }, 'json');
      return false;
    });
  }
};

$(document).ready(function() {
  if ($('#sql')) {
    user_sql.adminSettingsUI();
  }
});

