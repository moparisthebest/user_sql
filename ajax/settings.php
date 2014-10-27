<?php

// Init owncloud

// Check if we are a user
OCP\User::checkAdminUser();
OCP\JSON::checkAppEnabled('user_sql');

// CSRF checks
OCP\JSON::callCheck();

$l = new OC_L10N('use_sql');

$params = array('sql_host', 'sql_user', 'sql_database', 'sql_password',
                'sql_table', 'sql_column_username', 'sql_column_password',
                'sql_type', 'sql_column_active', 'strip_domain', 'default_domain',
                'crypt_type', 'sql_column_displayname');

if (isset($_POST['appname']) && $_POST['appname'] == "user_sql") {
  foreach ($params as $param) {
    if (isset($_POST[$param])) {
      if ($param === 'strip_domain') {
        OCP\Config::setAppValue('user_sql', 'strip_domain', true);
      } else {
          OCP\Config::setAppValue('user_sql', $param, $_POST[$param]);
      }
    } else {
      if ($param === 'strip_domain') {
        OCP\Config::setAppValue('user_sql', 'strip_domain', false);
      }
    }
  }
} else {
  OC_JSON::error(array("data" => array( "message" => $l->t("Not submitted for us.") )));
  return false;
}

OCP\JSON::success(array('data' => array( 'message' => $l->t('Application settings successfully stored.') )));
return true;
