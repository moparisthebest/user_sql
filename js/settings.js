// declare namespace
var user_sql = user_sql ||
{
};

user_sql.adminSettingsCheckRadio = function()
{
    if($('#domain_none').attr("checked") == "checked")
    {
        $('#default_domain').attr("disabled", true);
        $('#inputServerDomain').attr("disabled", true);
        $('#inputMapDomain').attr("disabled", true);
        $('#domainAddMap').attr("disabled", true);
    } else if($('#domain_server').attr("checked") == "checked")
    {
        $('#default_domain').attr("disabled", true);
        $('#inputServerDomain').attr("disabled", true);
        $('#inputMapDomain').attr("disabled", true);
        $('#domainAddMap').attr("disabled", true);
    } else if($('#domain_mapping').attr("checked") == "checked")
    {
        $('#default_domain').attr("disabled", true);
        $('#inputServerDomain').removeAttr("disabled");
        $('#inputMapDomain').removeAttr("disabled");
        $('#domainAddMap').removeAttr("disabled");
    } else if($('#domain_default').attr("checked") == "checked")
    {
        $('#default_domain').removeAttr("disabled");
        $('#inputServerDomain').attr("disabled", true);
        $('#inputMapDomain').attr("disabled", true);
        $('#domainAddMap').attr("disabled", true);
    }
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
            var domainArr = new Array();
            var mapArr = new Array();
            $('#domain_map_entries tr').each(function()
            {
                var d = $(this).find("td:first").html();
                var m = $(this).find("td").eq(1).html();
                if(d != undefined && m != undefined)
                {
                    mapArr.push(m);
                    domainArr.push(d);
                }
            });
            post.push(
            {
                name : 'map_array',
                value : mapArr
            });
            post.push(
            {
                name : 'domain_array',
                value : domainArr
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

        $('#domain_none').click(function(event)
        {
            user_sql.adminSettingsCheckRadio();
        });

        $('#domain_server').click(function(event)
        {
            user_sql.adminSettingsCheckRadio();
        });

        $('#domain_mapping').click(function(event)
        {
            user_sql.adminSettingsCheckRadio();
        });

        $('#domain_default').click(function(event)
        {
            user_sql.adminSettingsCheckRadio();
        });

        $('#domainAddMap').click(function(event)
        {
            event.preventDefault();
            var newDomain = $('#inputServerDomain').val();
            var newMap = $('#inputMapDomain').val();
            $('#domain_map_entries > tbody:last').append('<tr><td>' + newDomain + '</td><td>' + newMap + '</td><td><a class="deleteLink" href="#" >delete</a></td></tr>');
            $('#inputServerDomain').val("");
            $('#inputMapDomain').val("");
            $("#domain_map_entries .deleteLink").on("click", function()
            {
                var tr = $(this).closest('tr');
                tr.css("background-color", "#FF3700");
                tr.fadeOut(400, function()
                {
                    tr.remove();
                });
                return false;
            });
        });
    }
};

$(document).ready(function()
{
    if($('#sql'))
    {
        user_sql.adminSettingsUI();
        user_sql.adminSettingsCheckRadio();

        $("#domain_map_entries .deleteLink").on("click", function()
        {
            var tr = $(this).closest('tr');
            tr.css("background-color", "#FF3700");
            tr.fadeOut(400, function()
            {
                tr.remove();
            });
            return false;
        });
    }
});

