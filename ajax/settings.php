<?php

namespace OCA\user_sql;

// Init owncloud

// Check if we are a user
\OCP\User::checkAdminUser();
\OCP\JSON::checkAppEnabled('user_sql');

// CSRF checks
\OCP\JSON::callCheck();


$helper = new \OCA\user_sql\lib\Helper;

$l = \OC::$server->getL10N('user_sql');

$params = $helper -> getParameterArray();

if(isset($_POST['appname']) && ($_POST['appname'] === 'user_sql') && isset($_POST['function']) && isset($_POST['domain']))
{
    $domain = $_POST['domain'];
    switch($_POST['function'])
    {
        case 'saveSettings':
                foreach($params as $param)
                {
                    if(isset($_POST[$param]))
                    {
                        if($param === 'set_strip_domain')
                        {
                            \OC::$server->getConfig()->setAppValue('user_sql', 'set_strip_domain_'.$domain, 'true');
                        } 
                        elseif($param === 'set_allow_pwchange')
                        {
                            \OC::$server->getConfig()->setAppValue('user_sql', 'set_allow_pwchange_'.$domain, 'true');
                        }
                        elseif($param === 'set_active_invert')
                        {
                            \OC::$server->getConfig()->setAppValue('user_sql', 'set_active_invert_'.$domain, 'true');
                        }
                        else
                        {
                            \OC::$server->getConfig()->setAppValue('user_sql', $param.'_'.$domain, $_POST[$param]);
                        }
                    } else
                    {
                        if($param === 'set_strip_domain')
                        {
                            \OC::$server->getConfig()->setAppValue('user_sql', 'set_strip_domain_'.$domain, 'false');
                        }
                        elseif($param === 'set_allow_pwchange')
                        {
                            \OC::$server->getConfig()->setAppValue('user_sql', 'set_allow_pwchange_'.$domain, 'false');
                        }
                        elseif($param === 'set_active_invert')
                        {
                            \OC::$server->getConfig()->setAppValue('user_sql', 'set_active_invert_'.$domain, 'false');
                        }
                    }
                }
        break;

        case 'loadSettingsForDomain':
            $retArr = array();
            foreach($params as $param)
            {
                $retArr[$param] = \OC::$server->getConfig()->getAppValue('user_sql', $param.'_'.$domain, '');     
            }
            \OCP\JSON::success(array('settings' => $retArr));
            return true;
        break;
    }

} else
{
    \OCP\JSON::error(array('data' => array('message' => $l -> t('Not submitted for us.'))));
    return false;
}

\OCP\JSON::success(array('data' => array('message' => $l -> t('Application settings successfully stored.'))));
return true;
