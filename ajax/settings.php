<?php

// Init owncloud

// Check if we are a user
OCP\User::checkAdminUser();
OCP\JSON::checkAppEnabled('user_sql');

// CSRF checks
OCP\JSON::callCheck();

$l = new OC_L10N('user_sql');

$params = array('sql_host', 'sql_user', 'sql_database', 'sql_password', 
        'sql_table', 'sql_column_username', 'sql_column_password', 'sql_type', 
        'sql_column_active', 'strip_domain', 'default_domain', 'crypt_type', 
        'sql_column_displayname', 'domain_settings', 'map_array', 'domain_array', 
        'allow_password_change', 'sql_column_active_invert', 'sql_column_email',
        'mail_sync_mode');

if(isset($_POST['appname']) && $_POST['appname'] == "user_sql")
{
    foreach($params as $param)
    {
        if(isset($_POST[$param]))
        {
            if($param === 'strip_domain')
            {
                OCP\Config::setAppValue('user_sql', 'strip_domain', true);
            } 
            elseif($param === 'allow_password_change')
            {
                OCP\Config::setAppValue('user_sql', 'allow_password_change', true);
            }
            elseif($param === 'sql_column_active_invert')
            {
                OCP\Config::setAppValue('user_sql', 'sql_column_active_invert', true);
            }
            else
            {
                OCP\Config::setAppValue('user_sql', $param, $_POST[$param]);
            }
        } else
        {
            if($param === 'strip_domain')
            {
                OCP\Config::setAppValue('user_sql', 'strip_domain', false);
            }
            elseif($param === 'allow_password_change')
            {
                OCP\Config::setAppValue('user_sql', 'allow_password_change', false);
            }
            elseif($param === 'sql_column_active_invert')
            {
                OCP\Config::setAppValue('user_sql', 'sql_column_active_invert', false);
            }
        }
    }
} else
{
    OC_JSON::error(array("data" => array("message" => $l -> t("Not submitted for us."))));
    return false;
}

OCP\JSON::success(array('data' => array('message' => $l -> t('Application settings successfully stored.'))));
return true;
