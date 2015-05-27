<?php

/**
 * ownCloud - user_sql
 *
 * @author Andreas Böhler
 * @copyright 2012 Andreas Böhler <andreas (at) aboehler (dot) at>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */
$params = array('sql_host', 'sql_user', 'sql_database', 'sql_password', 'sql_table', 
        'sql_column_username', 'sql_column_password', 'sql_type', 'sql_column_active', 
        'strip_domain', 'default_domain', 'crypt_type', 'sql_column_displayname', 
        'domain_map', 'domain_settings', 'sql_column_active_invert');

OCP\Util::addStyle('user_sql', 'settings');
OCP\Util::addScript('user_sql', 'settings');
OCP\User::checkAdminUser();

// fill template
$tmpl = new OCP\Template('user_sql', 'settings');
foreach($params as $param)
{
    $value = htmlentities(OCP\Config::getAppValue('user_sql', $param, ''));
    $tmpl -> assign($param, $value);
}

// settings with default values
$tmpl -> assign('sql_host', OCP\Config::getAppValue('user_sql', 'sql_host', OC_USER_BACKEND_SQL_DEFAULT_HOST));
$tmpl -> assign('sql_user', OCP\Config::getAppValue('user_sql', 'sql_user', OC_USER_BACKEND_SQL_DEFAULT_USER));
$tmpl -> assign('sql_database', OCP\Config::getAppValue('user_sql', 'sql_database', OC_USER_BACKEND_SQL_DEFAULT_DB));
$tmpl -> assign('sql_password', OCP\Config::getAppValue('user_sql', 'sql_password', OC_USER_BACKEND_SQL_DEFAULT_PASSWORD));
$tmpl -> assign('sql_table', OCP\Config::getAppValue('user_sql', 'sql_table', OC_USER_BACKEND_SQL_DEFAULT_TABLE));
$tmpl -> assign('sql_column_password', OCP\Config::getAppValue('user_sql', 'sql_column_password', OC_USER_BACKEND_SQL_DEFAULT_PW_COLUMN));
$tmpl -> assign('sql_column_username', OCP\Config::getAppValue('user_sql', 'sql_column_username', OC_USER_BACKEND_SQL_DEFAULT_USER_COLUMN));
$tmpl -> assign('sql_type', OCP\Config::getAppValue('user_sql', 'sql_type', OC_USER_BACKEND_SQL_DEFAULT_DRIVER));
$tmpl -> assign('sql_column_active', OCP\Config::getAppValue('user_sql', 'sql_column_active', ''));
$tmpl -> assign('strip_domain', OCP\Config::getAppValue('user_sql', 'strip_domain', 0));
$tmpl -> assign('default_domain', OCP\Config::getAppValue('user_sql', 'default_domain', ''));
$tmpl -> assign('crypt_type', OCP\Config::getAppValue('user_sql', 'crypt_type', 'mysql_encrypt'));
$tmpl -> assign('sql_column_displayname', OCP\Config::getAppValue('user_sql', 'sql_column_displayname', ''));
$tmpl -> assign('map_array', OCP\Config::getAppValue('user_sql', 'map_array', ''));
$tmpl -> assign('domain_array', OCP\Config::getAppValue('user_sql', 'domain_array', ''));
$tmpl -> assign('domain_settings', OCP\Config::getAppValue('user_sql', 'domain_settings', ''));
$tmpl -> assign('allow_password_change', OCP\Config::getAppValue('user_sql', 'allow_password_change', 0));
$tmpl -> assign('sql_column_active_invert', OCP\Config::getAppValue('user_sql', 'sql_column_active_invert', 0));
// workaround to detect OC version
$ocVersion = @reset(OCP\Util::getVersion());
$tmpl -> assign('ocVersion', $ocVersion);

return $tmpl -> fetchPage();
