<?php

/**
 * ownCloud - user_sql
 *
 * @author Andreas Böhler and contributors
 * @copyright 2012-2015 Andreas Böhler <dev (at) aboehler (dot) at>
 *
 * credits go to Ed W for several SQL injection fixes and caching support
 * credits go to Frédéric France for providing Joomla support
 * credits go to Mark Jansenn for providing Joomla 2.5.18+ / 3.2.1+ support
 * credits go to Dominik Grothaus for providing SSHA256 support and fixing a few bugs
 * credits go to Sören Eberhardt-Biermann for providing multi-host support
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

namespace OCA\user_sql;

use \OCA\user_sql\lib\Helper;
 
class OC_USER_SQL extends \OC_User_Backend implements \OCP\IUserBackend, \OCP\UserInterface
{
    protected $cache;
    // cached settings
    protected $settings;
    protected $helper;
    protected $session_cache_name;

    public function __construct()
    {
		$memcache = \OC::$server->getMemCacheFactory();
		if ( $memcache -> isAvailable())
		{
			$this -> cache = $memcache -> create();
		}
        $this -> helper = new \OCA\user_sql\lib\Helper();
        $domain = \OC::$server->getRequest()->getServerHost();
        $this -> settings = $this -> helper -> loadSettingsForDomain($domain);
        $this -> helper -> connectToDb($this -> settings);        
        $this -> session_cache_name = 'USER_SQL_CACHE';
        return false;
    }

    private function doEmailSync($uid)
    {
        \OCP\Util::writeLog('OC_USER_SQL', "Entering doEmailSync for UID: $uid", \OCP\Util::DEBUG);
        if($this -> settings['col_email'] === '')
            return false;
        
        if($this -> settings['set_mail_sync_mode'] === 'none')
            return false;
            
        $ocUid = $uid;
        $uid = $this -> doUserDomainMapping($uid);

        $row = $this -> helper -> runQuery('getMail', array('uid' => $uid));
        if($row === false)
        {
            return false;
        }
        $newMail = $row[$this -> settings['col_email']];
        $currMail = \OCP\Config::getUserValue($ocUid, 'settings', 'email', '');
        
        switch($this -> settings['set_mail_sync_mode'])
        {
            case 'initial':
                if($currMail === '')
                    \OCP\Config::setUserValue($ocUid, 'settings', 'email', $newMail);
                break;
            case 'forcesql':
                if($currMail !== $newMail)
                    \OCP\Config::setUserValue($ocUid, 'settings', 'email', $newMail);
                break;
            case 'forceoc':
                if(($currMail !== '') && ($currMail !== $newMail))
                {
                    $row = $this -> helper -> runQuery('setMail', array('uid' => $uid, 'currMail' => $currMail), true);
                    
                    if($row === false)
                    {
                        \OCP\Util::writeLog('OC_USER_SQL', "Could not update E-Mail address in SQL database!", \OCP\Util::ERROR);
                    }
                }
                break;
        }
        
        return true;
    }

    private function doUserDomainMapping($uid)
    {
        $uid = trim($uid);
        
        if($this -> settings['set_default_domain'] !== '')
        {
            \OCP\Util::writeLog('OC_USER_SQL', "Append default domain: ".$this -> settings['set_default_domain'], \OCP\Util::DEBUG);
            if(strpos($uid, '@') === false)
            {
                $uid .= "@" . $this -> settings['set_default_domain'];
            }
        }

        $uid = strtolower($uid);
        \OCP\Util::writeLog('OC_USER_SQL', 'Returning mapped UID: ' . $uid, \OCP\Util::DEBUG);
        return $uid;
    }

    public function implementsAction($actions)
    {
        return (bool)((\OC_User_Backend::CHECK_PASSWORD
			| \OC_User_Backend::GET_DISPLAYNAME
			| \OC_User_Backend::COUNT_USERS
			) & $actions);
    }

    public function hasUserListings()
    {
        return true;
    }

    public function createUser()
    {
        // Can't create user
        \OCP\Util::writeLog('OC_USER_SQL', 'Not possible to create local users from web frontend using SQL user backend', \OCP\Util::ERROR);
        return false;
    }

    public function deleteUser($uid)
    {
        // Can't delete user
        \OCP\Util::writeLog('OC_USER_SQL', 'Not possible to delete local users from web frontend using SQL user backend', \OCP\Util::ERROR);
        return false;
    }

    public function setPassword($uid, $password)
    {
        // Update the user's password - this might affect other services, that
        // use the same database, as well
        \OCP\Util::writeLog('OC_USER_SQL', "Entering setPassword for UID: $uid", \OCP\Util::DEBUG);

        if($this -> settings['set_allow_pwchange'] !== 'true')
            return false;

        $uid = $this -> doUserDomainMapping($uid);

        $row = $this -> helper -> runQuery('getPass', array('uid' => $uid));
        if($row === false)
        {
            return false;
        }
        $old_password = $row[$this -> settings['col_password']];
        if($this -> settings['set_crypt_type'] === 'joomla2')
        {
            if(!class_exists('PasswordHash'))
                require_once('PasswordHash.php');
            $hasher = new PasswordHash(10, true);
            $enc_password = $hasher -> HashPassword($password);
        }         
        // Redmine stores the salt separatedly, this doesn't play nice with the way
        // we check passwords
        elseif($this -> settings['set_crypt_type'] === 'redmine')
        {
        	$salt = $this -> helper -> runQuery('getRedmineSalt', array('uid' => $uid));
			if(!$salt)
				return false;
			$enc_password = sha1($salt['salt'].sha1($password));
        } else
        {
            $enc_password = $this -> pacrypt($password, $old_password);
        }
        $res = $this -> helper -> runQuery('setPass', array('uid' => $uid, 'enc_password' => $enc_password), true);
        if($res === false)
        {
            \OCP\Util::writeLog('OC_USER_SQL', "Could not update password!", \OCP\Util::ERROR);
            return false;
        }
        \OCP\Util::writeLog('OC_USER_SQL', "Updated password successfully, return true", \OCP\Util::DEBUG);
        return true;
    }

    /**
     * @brief Check if the password is correct
     * @param $uid The username
     * @param $password The password
     * @returns true/false
     *
     * Check if the password is correct without logging in the user
     */
    public function checkPassword($uid, $password)
    {
        \OCP\Util::writeLog('OC_USER_SQL', "Entering checkPassword() for UID: $uid", \OCP\Util::DEBUG);
        
        $uid = $this -> doUserDomainMapping($uid);

        $row = $this -> helper -> runQuery('getPass', array('uid' => $uid));
        if($row === false)
        {
            \OCP\Util::writeLog('OC_USER_SQL', "Got no row, return false", \OCP\Util::DEBUG);
            return false;
        }
        $db_pass = $row[$this -> settings['col_password']];
        \OCP\Util::writeLog('OC_USER_SQL', "Encrypting and checking password", \OCP\Util::DEBUG);
        // Joomla 2.5.18 switched to phPass, which doesn't play nice with the way
        // we check passwords
        if($this -> settings['set_crypt_type'] === 'joomla2')
        {
            if(!class_exists('PasswordHash'))
                require_once('PasswordHash.php');
            $hasher = new PasswordHash(10, true);
            $ret = $hasher -> CheckPassword($password, $db_pass);
        } 
        // Redmine stores the salt separatedly, this doesn't play nice with the way
        // we check passwords
        elseif($this -> settings['set_crypt_type'] === 'redmine')
        {
			$salt = $this -> helper -> runQuery('getRedmineSalt', array('uid' => $uid));
			if(!$salt)
				return false;
			$ret = sha1($salt['salt'].sha1($password)) === $db_pass;
        } else
        {
            $ret = $this -> pacrypt($password, $db_pass) === $db_pass;
        }
        if($ret)
        {
            \OCP\Util::writeLog('OC_USER_SQL', "Passwords matching, return true", \OCP\Util::DEBUG);
            if($this -> settings['set_strip_domain'] === 'true')
            {
                $uid = explode("@", $uid);
                $uid = $uid[0];
            }
            return $uid;
        } else
        {
            \OCP\Util::writeLog('OC_USER_SQL', "Passwords do not match, return false", \OCP\Util::DEBUG);
            return false;
        }
    }

	public function countUsers()
	{
        \OCP\Util::writeLog('OC_USER_SQL', "Entering countUsers()", \OCP\Util::DEBUG);

        $userCount = $this -> helper -> runQuery('countUsers', array());
        if($userCount === false)
        {
            $userCount = 0;
        }
        else {
            $userCount = reset($userCount);
        }

        \OCP\Util::writeLog('OC_USER_SQL', "Return usercount", \OCP\Util::DEBUG);
        return $userCount;
	}

    /**
     * @brief Get a list of all users
     * @returns array with all uids
     *
     * Get a list of all users.
     */

    public function getUsers($search = '', $limit = null, $offset = null)
    {
        \OCP\Util::writeLog('OC_USER_SQL', "Entering getUsers() with Search: $search, Limit: $limit, Offset: $offset", \OCP\Util::DEBUG);
        $users = array();

        if($search !== '')
        {
            $search = "%".$this -> doUserDomainMapping($search."%")."%";
        }
        else 
        {
	       $search = "%".$this -> doUserDomainMapping("")."%";   
        }
        
        $rows = $this -> helper -> runQuery('getUsers', array('search' => $search), false, true, array('limit' => $limit, 'offset' => $offset));
        if($rows === false)
            return array();

        foreach($rows as $row)
        {
            $uid = $row[$this -> settings['col_username']];
            if($this -> settings['set_strip_domain'] === 'true')
            {
                $uid = explode("@", $uid);
                $uid = $uid[0];
            }
            $users[] = strtolower($uid);
        }
        \OCP\Util::writeLog('OC_USER_SQL', "Return list of results", \OCP\Util::DEBUG);
        return $users;
    }

    /**
     * @brief check if a user exists
     * @param string $uid the username
     * @return boolean
     */

    public function userExists($uid)
    {

        $cacheKey = 'sql_user_exists_' . $uid;
        $cacheVal = $this -> getCache ($cacheKey);
        \OCP\Util::writeLog('OC_USER_SQL', "userExists() for UID: $uid cacheVal: $cacheVal", \OCP\Util::DEBUG);
        if(!is_null($cacheVal))
            return (bool)$cacheVal;

        \OCP\Util::writeLog('OC_USER_SQL', "Entering userExists() for UID: $uid", \OCP\Util::DEBUG);

        $uid = $this -> doUserDomainMapping($uid);

        $exists = (bool)$this -> helper -> runQuery('userExists', array('uid' => $uid));;
        $this -> setCache ($cacheKey, $exists, 60);

        if(!$exists)
        {
            \OCP\Util::writeLog('OC_USER_SQL', "Empty row, user does not exists, return false", \OCP\Util::DEBUG);
            return false;
        } else
        {
            \OCP\Util::writeLog('OC_USER_SQL', "User exists, return true", \OCP\Util::DEBUG);
            return true;
        }

    }

    public function getDisplayName($uid)
    {
        \OCP\Util::writeLog('OC_USER_SQL', "Entering getDisplayName() for UID: $uid", \OCP\Util::DEBUG);

        $this -> doEmailSync($uid);
        $uid = $this -> doUserDomainMapping($uid);

        if(!$this -> userExists($uid))
        {
            return false;
        }

        $row = $this -> helper -> runQuery('getDisplayName', array('uid' => $uid));

        if(!$row)
        {
            \OCP\Util::writeLog('OC_USER_SQL', "Empty row, user has no display name or does not exist, return false", \OCP\Util::DEBUG);
            return false;
        } else
        {
            \OCP\Util::writeLog('OC_USER_SQL', "User exists, return true", \OCP\Util::DEBUG);
            $displayName = $row[$this -> settings['col_displayname']];
            return $displayName; ;
        }
        return false;
    }

    public function getDisplayNames($search = '', $limit = null, $offset = null)
    {
        $uids = $this -> getUsers($search, $limit, $offset);
        $displayNames = array();
        foreach($uids as $uid)
        {
            $displayNames[$uid] = $this -> getDisplayName($uid);
        }
        return $displayNames;
    }

	/**
	 * Returns the backend name
	 * @return string
	 */
	public function getBackendName()
	{
		return 'SQL';
	}

    /**
     * The following functions were directly taken from PostfixAdmin and just
     * slightly modified
     * to suit our needs.
     * Encrypt a password, using the apparopriate hashing mechanism as defined in
     * config.inc.php ($this->crypt_type).
     * When wanting to compare one pw to another, it's necessary to provide the
     * salt used - hence
     * the second parameter ($pw_db), which is the existing hash from the DB.
     *
     * @param string $pw
     * @param string $encrypted password
     * @return string encrypted password.
     */
    private function pacrypt($pw, $pw_db = "")
    {
        \OCP\Util::writeLog('OC_USER_SQL', "Entering private pacrypt()", \OCP\Util::DEBUG);
        $pw = stripslashes($pw);
        $password = "";
        $salt = "";

        if($this -> settings['set_crypt_type'] === 'md5crypt')
        {
            $split_salt = preg_split('/\$/', $pw_db);
            if(isset($split_salt[2]))
            {
                $salt = $split_salt[2];
            }
            $password = $this -> md5crypt($pw, $salt);
        } elseif($this -> settings['set_crypt_type'] === 'md5')
        {
            $password = md5($pw);
        } elseif($this -> settings['set_crypt_type'] === 'system')
        {
            // We never generate salts, as user creation is not allowed here
            $password = crypt($pw, $pw_db);
        } elseif($this -> settings['set_crypt_type'] === 'cleartext')
        {
            $password = $pw;
        }

        // See
        // https://sourceforge.net/tracker/?func=detail&atid=937966&aid=1793352&group_id=191583
        // this is apparently useful for pam_mysql etc.
        elseif($this -> settings['set_crypt_type'] === 'mysql_encrypt')
        {
            if($pw_db !== "")
            {
                $salt = substr($pw_db, 0, 2);
                
                $row = $this -> helper -> runQuery('mysqlEncryptSalt', array('pw' => $pw, 'salt' => $salt));
            } else
            {
                $row = $this -> helper -> runQuery('mysqlEncrypt', array('pw' => $pw));
            }

            if($row === false)
            {
                return false;
            }
            $password = $row[0];
        } elseif($this -> settings['set_crypt_type'] === 'mysql_password')
        {
            $this -> helper -> runQuery('mysqlPassword', array('pw' => $pw));

            if($row === false)
            {
                return false;
            }
            $password = $row[0];
        }

        // The following is by Frédéric France
        elseif($this -> settings['set_crypt_type'] === 'joomla')
        {
            $split_salt = preg_split('/:/', $pw_db);
            if(isset($split_salt[1]))
            {
                $salt = $split_salt[1];
            }
            $password = ($salt) ? md5($pw . $salt) : md5($pw);
            $password .= ':' . $salt;
		}

		elseif($this-> settings['set_crypt_type'] === 'ssha256')
		{
			$salted_password = base64_decode(preg_replace('/{SSHA256}/i','',$pw_db));
			$salt = substr($salted_password,-(strlen($salted_password)-32));
			$password = $this->ssha256($pw,$salt);
        } else
        {
            \OCP\Util::writeLog('OC_USER_SQL', "unknown/invalid crypt_type settings: ".$this->settings['set_crypt_type'], \OCP\Util::ERROR);
            die('unknown/invalid Encryption type setting: ' . $this -> settings['set_crypt_type']);
        }
        \OCP\Util::writeLog('OC_USER_SQL', "pacrypt() done, return", \OCP\Util::DEBUG);
        return $password;
    }

    //
    // md5crypt
    // Action: Creates MD5 encrypted password
    // Call: md5crypt (string cleartextpassword)
    //

    private function md5crypt($pw, $salt = "", $magic = "")
    {
        $MAGIC = "$1$";

        if($magic === "")
            $magic = $MAGIC;
        if($salt === "")
            $salt = $this -> create_salt();
        $slist = explode("$", $salt);
        if($slist[0] === "1")
            $salt = $slist[1];

        $salt = substr($salt, 0, 8);
        $ctx = $pw . $magic . $salt;
        $final = $this -> pahex2bin(md5($pw . $salt . $pw));

        for($i = strlen($pw); $i > 0; $i -= 16)
        {
            if($i > 16)
            {
                $ctx .= substr($final, 0, 16);
            } else
            {
                $ctx .= substr($final, 0, $i);
            }
        }
        $i = strlen($pw);

        while($i > 0)
        {
            if($i & 1)
                $ctx .= chr(0);
            else
                $ctx .= $pw[0];
            $i = $i>>1;
        }
        $final = $this -> pahex2bin(md5($ctx));

        for($i = 0; $i < 1000; $i++)
        {
            $ctx1 = "";
            if($i & 1)
            {
                $ctx1 .= $pw;
            } else
            {
                $ctx1 .= substr($final, 0, 16);
            }
            if($i % 3)
                $ctx1 .= $salt;
            if($i % 7)
                $ctx1 .= $pw;
            if($i & 1)
            {
                $ctx1 .= substr($final, 0, 16);
            } else
            {
                $ctx1 .= $pw;
            }
            $final = $this -> pahex2bin(md5($ctx1));
        }
        $passwd = "";
        $passwd .= $this -> to64(((ord($final[0])<<16) | (ord($final[6])<<8) | (ord($final[12]))), 4);
        $passwd .= $this -> to64(((ord($final[1])<<16) | (ord($final[7])<<8) | (ord($final[13]))), 4);
        $passwd .= $this -> to64(((ord($final[2])<<16) | (ord($final[8])<<8) | (ord($final[14]))), 4);
        $passwd .= $this -> to64(((ord($final[3])<<16) | (ord($final[9])<<8) | (ord($final[15]))), 4);
        $passwd .= $this -> to64(((ord($final[4])<<16) | (ord($final[10])<<8) | (ord($final[5]))), 4);
        $passwd .= $this -> to64(ord($final[11]), 2);
        return "$magic$salt\$$passwd";
    }

    private function create_salt()
    {
        srand((double) microtime() * 1000000);
        $salt = substr(md5(rand(0, 9999999)), 0, 8);
        return $salt;
    }

    private function ssha256($pw, $salt)
	{
	    return '{SSHA256}'.base64_encode(hash('sha256',$pw.$salt,true).$salt);
	}

    private function pahex2bin($str)
    {
        if(function_exists('hex2bin'))
        {
            return hex2bin($str);
        } else
        {
            $len = strlen($str);
            $nstr = "";
            for($i = 0; $i < $len; $i += 2)
            {
                $num = sscanf(substr($str, $i, 2), "%x");
                $nstr .= chr($num[0]);
            }
            return $nstr;
        }
    }

    private function to64($v, $n)
    {
        $ITOA64 = "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        $ret = "";
        while(($n - 1) >= 0)
        {
            $n--;
            $ret .= $ITOA64[$v & 0x3f];
            $v = $v>>6;
        }
        return $ret;
    }

	/**
	 * Store a value in memcache or the session, if no memcache is available
	 * @param string $key
	 * @param mixed $value
	 * @param int $ttl (optional) defaults to 3600 seconds.
	 */
	private function setCache($key,$value,$ttl=3600)
	{
		if ($this -> cache === NULL)
		{
			$_SESSION[$this -> session_cache_name][$key] = array(
				'value' => $value,
				'time' => time(),
				'ttl' => $ttl,
			);
		} else
		{
			$this -> cache -> set($key,$value,$ttl);
		}
	}

	/**
	 * Fetch a value from memcache or session, if memcache is not available.
	 * Returns NULL if there's no value stored or the value expired.
	 * @param string $key
	 * @return mixed|NULL
	 */
	private function getCache($key)
	{
		$retVal = NULL;
		if ($this -> cache === NULL)
		{
			if (isset($_SESSION[$this -> session_cache_name],$_SESSION[$this -> session_cache_name][$key]))
			{
				$value = $_SESSION[$this -> session_cache_name][$key];
				if (time() < $value['time'] + $value['ttl'])
				{
					$retVal = $value['value'];
				}
			}
		} else
		{
			$retVal = $this -> cache -> get ($key);
		}
		return $retVal;
	}

}
?>
