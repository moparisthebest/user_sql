// declare namespace
var user_sql = user_sql ||
{
};

/**
 * init admin settings view
 */
user_sql.adminSettingsUI = function()
{

    if($('#sql').length > 0)
    {
        // enable tabs on settings page
        $('#sql').tabs();

        $('#sqlSubmit').click(function(event)
        {
            event.preventDefault();

            var self = $(this);
            var post = $('#sqlForm').serializeArray();
            var domain = $('#sql_domain_chooser option:selected').val();
            
            post.push({
                name: 'function',
                value: 'saveSettings'
            });
            
            post.push({
                name: 'domain',
                value: domain
            });

            $('#sql_update_message').show();
            $('#sql_success_message').hide();
            $('#sql_error_message').hide();
            // Ajax foobar
            $.post(OC.filePath('user_sql', 'ajax', 'settings.php'), post, function(data)
            {
                $('#sql_update_message').hide();
                if(data.status == 'success')
                {
                    $('#sql_success_message').html(data.data.message);
                    $('#sql_success_message').show();
                    window.setTimeout(function()
                    {
                        $('#sql_success_message').hide();
                    }, 10000);
                } else
                {
                    $('#sql_error_message').html(data.data.message);
                    $('#sql_error_message').show();
                }
            }, 'json');
            return false;
        });
        
        $('#sql_domain_chooser').change(function() {
           user_sql.loadDomainSettings($('#sql_domain_chooser option:selected').val());
        });

        
    }
};

user_sql.loadDomainSettings = function(domain)
{
    $('#sql_loading_message').show();
    var post = [
        {
            name: 'appname',
            value: 'user_sql'
        },
        {
            name: 'function',
            value: 'loadSettingsForDomain'
        },
        {
            name: 'domain',
            value: domain
        }
    ];
    $.post(OC.filePath('user_sql', 'ajax', 'settings.php'), post, function(data)
        {
            $('#sql_loading_message').hide();
            if(data.status == 'success')
            {
                for(key in data.settings)
                {
                    if(key == 'set_strip_domain')
                    {
                        if(data.settings[key] == 'true')
                            $('#' + key).prop('checked', true);
                        else
                            $('#' + key).prop('checked', false);
                    }
                    else if(key == 'set_allow_pwchange')
                    {
                        if(data.settings[key] == 'true')
                            $('#' + key).prop('checked', true);
                        else
                            $('#' + key).prop('checked', false);
                    }
                    else if(key == 'set_active_invert')
                    {
                        if(data.settings[key] == 'true')
                            $('#' + key).prop('checked', true);
                        else
                            $('#' + key).prop('checked', false);
                    }
                    else
                    {
                        $('#' + key).val(data.settings[key]);
                    }
                }
            }
            else
            {
                $('#sql_error_message').html(data.data.message);
                $('#sql_error_message').show();
            }
        }
    );
};

$(document).ready(function()
{
    if($('#sql'))
    {
        user_sql.adminSettingsUI();
        user_sql.loadDomainSettings($('#sql_domain_chooser option:selected').val());
    }
});

