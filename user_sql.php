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

class OC_USER_SQL extends OC_User_Backend implements \OCP\IUserBackend, \OCP\UserInterface
{
    protected $cache;
    // cached settings
    protected $sql_host;
    protected $sql_username;
    protected $sql_database;
    protected $sql_password;
    protected $sql_table;
    protected $sql_column_username;
    protected $sql_column_password;
    protected $sql_column_active;
    protected $sql_column_active_invert;
    protected $sql_column_displayname;
    protected $sql_column_email;
    protected $mail_sync_mode;
    protected $sql_type;
    protected $db_conn;
    protected $db;
    protected $default_domain;
    protected $strip_domain;
    protected $crypt_type;
    protected $domain_settings;
    protected $domain_array;
    protected $map_array;
    protected $allow_password_change;
    protected $session_cache_name;

    public function __construct()
    {
        $this -> db_conn = false;
		$memcache = \OC::$server->getMemCacheFactory();
		if ( $memcache -> isAvailable())
		{
			$this -> cache = $memcache -> create();
		}
        $this -> sql_host = OCP\Config::getAppValue('user_sql', 'sql_host', '');
        $this -> sql_username = OCP\Config::getAppValue('user_sql', 'sql_user', '');
        $this -> sql_database = OCP\Config::getAppValue('user_sql', 'sql_database', '');
        $this -> sql_password = OCP\Config::getAppValue('user_sql', 'sql_password', '');
        $this -> sql_table = OCP\Config::getAppValue('user_sql', 'sql_table', '');
        $this -> sql_column_username = OCP\Config::getAppValue('user_sql', 'sql_column_username', '');
        $this -> sql_column_password = OCP\Config::getAppValue('user_sql', 'sql_column_password', '');
        $this -> sql_column_displayname = OCP\Config::getAppValue('user_sql', 'sql_column_displayname', $this->sql_column_username);
        $this -> sql_column_email = OCP\Config::getAppValue('user_sql', 'sql_column_email', '');
        $this -> sql_column_active = OCP\Config::getAppValue('user_sql', 'sql_column_active', '');
        $this -> sql_column_active_invert = OCP\Config::getAppValue('user_sql', 'sql_column_active_invert', 0);
        $this -> sql_type = OCP\Config::getAppValue('user_sql', 'sql_type', '');
        $this -> default_domain = OCP\Config::getAppValue('user_sql', 'default_domain', '');
        $this -> strip_domain = OCP\Config::getAppValue('user_sql', 'strip_domain', 0);
        $this -> allow_password_change = OCP\Config::getAppValue('user_sql', 'allow_password_change', 0);        
        $this -> crypt_type = OCP\Config::getAppValue('user_sql', 'crypt_type', 'md5crypt');
        $this -> domain_settings = OCP\Config::getAppValue('user_sql', 'domain_settings', 'none');
        $this -> domain_array = explode(",", OCP\Config::getAppValue('user_sql', 'domain_array', ''));
        $this -> map_array = explode(",", OCP\Config::getAppValue('user_sql', 'map_array', ''));
        $this -> mail_sync_mode = OCP\Config::getAppValue('user_sql', 'mail_sync_mode', 'none');
        $this -> session_cache_name = 'USER_SQL_CACHE';
        $dsn = $this -> sql_type . ":host=" . $this -> sql_host . ";dbname=" . $this -> sql_database;
        try
        {
            $this -> db = new PDO($dsn, $this -> sql_username, $this -> sql_password);
            $this -> db -> query("SET NAMES 'UTF8'");
            $this -> db_conn = true;
        } catch (PDOException $e)
        {
            \OCP\Util::writeLog('OC_USER_SQL', 'Failed to connect to the database: ' . $e -> getMessage(), \OCP\Util::ERROR);
        }
        return false;
    }

    private function doEmailSync($uid)
    {
        \OCP\Util::writeLog('OC_USER_SQL', "Entering doEmailSync for UID: $uid", \OCP\Util::DEBUG);
        if($this -> sql_column_email === '')
            return false;
        
        if($this -> mail_sync_mode === 'none')
            return false;
            
        $ocUid = $uid;
        $uid = $this -> doUserDomainMapping($uid);

        $query = "SELECT $this->sql_column_email FROM $this->sql_table WHERE $this->sql_column_username = :uid";
        \OCP\Util::writeLog('OC_USER_SQL', "Preparing query: $query", \OCP\Util::DEBUG);
        $result = $this -> db -> prepare($query);
        $result -> bindParam(":uid", $uid);
        \OCP\Util::writeLog('OC_USER_SQL', "Executing query...", \OCP\Util::DEBUG);
        if(!$result -> execute())
        {
            return false;
        }
        \OCP\Util::writeLog('OC_USER_SQL', "Fetching result...", \OCP\Util::DEBUG);
        $row = $result -> fetch();
        if(!$row)
        {
            return false;
        }
        $newMail = $row[$this -> sql_column_email];
        $currMail = OCP\Config::getUserValue($ocUid, 'settings', 'email', '');
        
        switch($this -> mail_sync_mode)
        {
            case 'initial':
                if($currMail === '')
                    OCP\Config::setUserValue($ocUid, 'settings', 'email', $newMail);
                break;
            case 'forcesql':
                if($currMail !== $newMail)
                    OCP\Config::setUserValue($ocUid, 'settings', 'email', $newMail);
                break;
            case 'forceoc':
                if(($currMail !== '') && ($currMail !== $newMail))
                {
                    $query = "UPDATE $this->sql_table SET $this->sql_column_email = :currMail WHERE $this->sql_column_username = :uid";
                    \OCP\Util::writeLog('OC_USER_SQL', "Preapring query: $query", \OCP\Util::DEBUG);
                    $result = $this -> db -> prepare($query);
                    $result -> bindParam(":currMail", $currMail);
                    $result -> bindParam(":uid", $uid);
                    \OCP\Util::writeLog('OC_USER_SQL', "Executing query...", \OCP\Util::DEBUG);
                    if(!$result -> execute())
                    {
                        $err = $result -> errorInfo();
                        \OCP\Util::writeLog('OC_USER_SQL', "Query failed: " . $err[2], \OCP\Util::DEBUG);
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

        switch($this->domain_settings)
        {
            case "default" :
                \OCP\Util::writeLog('OC_USER_SQL', "Default mapping", \OCP\Util::DEBUG);
                if($this -> default_domain && (strpos($uid, '@') === false))
                    $uid .= "@" . $this -> default_domain;
                break;
            case "server" :
                \OCP\Util::writeLog('OC_USER_SQL', "Server based mapping", \OCP\Util::DEBUG);
                if(strpos($uid, '@') === false)
                    $uid .= "@" . $_SERVER['SERVER_NAME'];
                break;
            case "mapping" :
                \OCP\Util::writeLog('OC_USER_SQL', 'Domain mapping selected', \OCP\Util::DEBUG);
                if(strpos($uid, '@') === false)
                {
                    for($i = 0; $i < count($this -> domain_array); $i++)
                    {
                        \OCP\Util::writeLog('OC_USER_SQL', 'Checking domain in mapping: ' . $this -> domain_array[$i], \OCP\Util::DEBUG);
                        if($_SERVER['SERVER_NAME'] === trim($this -> domain_array[$i]))
                        {
                            \OCP\Util::writeLog('OC_USER_SQL', 'Found domain in mapping: ' . $this -> domain_array[$i], \OCP\Util::DEBUG);
                            $uid .= "@" . trim($this -> map_array[$i]);
                            break;
                        }
                    }
                }
                break;
            case "none" :
            default :
                \OCP\Util::writeLog('OC_USER_SQL', "No mapping", \OCP\Util::DEBUG);
                break;
        }

        $uid = strtolower($uid);
        \OCP\Util::writeLog('OC_USER_SQL', 'Returning mapped UID: ' . $uid, \OCP\Util::DEBUG);
        return $uid;
    }

    public function implementsAction($actions)
    {
        return (bool)((OC_USER_BACKEND_CHECK_PASSWORD | OC_USER_BACKEND_GET_DISPLAYNAME) & $actions);
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
        if(!$this -> db_conn || !$this->allow_password_change)
        {
            return false;
        }
        $uid = $this -> doUserDomainMapping($uid);

        $query = "SELECT $this->sql_column_password FROM $this->sql_table WHERE $this->sql_column_username = :uid";
        \OCP\Util::writeLog('OC_USER_SQL', "Preparing query: $query", \OCP\Util::DEBUG);
        $result = $this -> db -> prepare($query);
        $result -> bindParam(":uid", $uid);
        \OCP\Util::writeLog('OC_USER_SQL', "Executing query...", \OCP\Util::DEBUG);
        if(!$result -> execute())
        {
            return false;
        }
        \OCP\Util::writeLog('OC_USER_SQL', "Fetching result...", \OCP\Util::DEBUG);
        $row = $result -> fetch();
        if(!$row)
        {
            return false;
        }
        $old_password = $row[$this -> sql_column_password];
        if($this -> crypt_type === 'joomla2')
        {
            if(!class_exists('PasswordHash'))
                require_once('PasswordHash.php');
            $hasher = new PasswordHash(10, true);
            $enc_password = $hasher->HashPassword($password);
        }         
        // Redmine stores the salt separatedly, this doesn't play nice with the way
        // we check passwords
        elseif($this -> crypt_type === 'redmine')
        {
        	$query = "SELECT salt FROM $this->sql_table WHERE $this->sql_column_username =:uid;";
        	$res = $this->db->prepare($query);
			$res->bindparam(":uid", $uid);
			if(!$res->execute())
				return false;
			$salt = $res->fetch();
			if(!$salt)
				return false;
			$enc_password = sha1($salt['salt'].sha1($password));
        } else
        {
            $enc_password = $this -> pacrypt($password, $old_password);
        }
        $query = "UPDATE $this->sql_table SET $this->sql_column_password = :enc_password WHERE $this->sql_column_username = :uid";
        \OCP\Util::writeLog('OC_USER_SQL', "Preapring query: $query", \OCP\Util::DEBUG);
        $result = $this -> db -> prepare($query);
        $result -> bindParam(":enc_password", $enc_password);
        $result -> bindParam(":uid", $uid);
        \OCP\Util::writeLog('OC_USER_SQL', "Executing query...", \OCP\Util::DEBUG);
        if(!$result -> execute())
        {
            $err = $result -> errorInfo();
            \OCP\Util::writeLog('OC_USER_SQL', "Query failed: " . $err[2], \OCP\Util::DEBUG);
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
        if(!$this -> db_conn)
        {
            return false;
        }
        $uid = $this -> doUserDomainMapping($uid);

        $query = "SELECT $this->sql_column_username, $this->sql_column_password FROM $this->sql_table WHERE $this->sql_column_username = :uid";
        if($this -> sql_column_active !== '')
            $query .= " AND " .($this->sql_column_active_invert ? "NOT " : "" ).$this->sql_column_active;
        \OCP\Util::writeLog('OC_USER_SQL', "Preparing query: $query", \OCP\Util::DEBUG);
        $result = $this -> db -> prepare($query);
        $result -> bindParam(":uid", $uid);
        \OCP\Util::writeLog('OC_USER_SQL', "Executing query...", \OCP\Util::DEBUG);
        if(!$result -> execute())
        {
            $err = $result -> errorInfo();
            \OCP\Util::writeLog('OC_USER_SQL', "Query failed: " . $err[2], \OCP\Util::DEBUG);
            return false;
        }
        \OCP\Util::writeLog('OC_USER_SQL', "Fetching row...", \OCP\Util::DEBUG);
        $row = $result -> fetch();
        if(!$row)
        {
            \OCP\Util::writeLog('OC_USER_SQL', "Got no row, return false", \OCP\Util::DEBUG);
            return false;
        }
        \OCP\Util::writeLog('OC_USER_SQL', "Encrypting and checking password", \OCP\Util::DEBUG);
        // Joomla 2.5.18 switched to phPass, which doesn't play nice with the way
        // we check passwords
        if($this -> crypt_type === 'joomla2')
        {
            if(!class_exists('PasswordHash'))
                require_once('PasswordHash.php');
            $hasher = new PasswordHash(10, true);
            $ret = $hasher -> CheckPassword($password, $row[$this -> sql_column_password]);
        } 
        // Redmine stores the salt separatedly, this doesn't play nice with the way
        // we check passwords
        elseif($this -> crypt_type === 'redmine')
        {
        	$query = "SELECT salt FROM $this->sql_table WHERE $this->sql_column_username =:uid;";
        	$res = $this->db->prepare($query);
			$res->bindparam(":uid", $uid);
			if(!$res->execute())
				return false;
			$salt = $res->fetch();
			if(!$salt)
				return false;
			$ret = sha1($salt['salt'].sha1($password)) === $row[$this->sql_column_password];
        } else
        {
            $ret = $this -> pacrypt($password, $row[$this -> sql_column_password]) === $row[$this -> sql_column_password];
        }
        if($ret)
        {
            \OCP\Util::writeLog('OC_USER_SQL', "Passwords matching, return true", \OCP\Util::DEBUG);
            if($this -> strip_domain)
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
        if(!$this -> db_conn)
        {
            return false;
        }
        $query = "SELECT $this->sql_column_username FROM $this->sql_table";
        $query .= " WHERE $this->sql_column_username LIKE :search";
        if($this -> sql_column_active !== '')
            $query .= " AND " .($this->sql_column_active_invert ? "NOT " : "" ).$this->sql_column_active;
        $query .= " ORDER BY $this->sql_column_username";
        if($limit !== null)
        {
            $limit = intval($limit);
            $query .= " LIMIT $limit";
        }
        if($offset !== null)
        {
            $offset = intval($offset);
            $query .= " OFFSET $offset";
        }
        \OCP\Util::writeLog('OC_USER_SQL', "Preparing query: $query", \OCP\Util::DEBUG);
        $result = $this -> db -> prepare($query);
        if($search !== '')
        {
            $search = "%".$this -> doUserDomainMapping($search."%")."%";
        }
        else 
        {
	       $search = "%".$this -> doUserDomainMapping("")."%";   
        }
        $result -> bindParam(":search", $search);
        
        \OCP\Util::writeLog('OC_USER_SQL', "Executing query...", \OCP\Util::DEBUG);
        if(!$result -> execute())
        {
            $err = $result -> errorInfo();
            \OCP\Util::writeLog('OC_USER_SQL', "Query failed: " . $err[2], \OCP\Util::DEBUG);
            return array();
        }
        \OCP\Util::writeLog('OC_USER_SQL', "Fetching results...", \OCP\Util::DEBUG);
        while($row = $result -> fetch())
        {
            $uid = $row[$this -> sql_column_username];
            if($this -> strip_domain)
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
        if(!$this -> db_conn)
        {
            return false;
        }
        $uid = $this -> doUserDomainMapping($uid);
        $query = "SELECT $this->sql_column_username FROM $this->sql_table WHERE $this->sql_column_username = :uid";
        if($this -> sql_column_active !== '')
            $query .= " AND " .($this->sql_column_active_invert ? "NOT " : "" ).$this->sql_column_active;
        \OCP\Util::writeLog('OC_USER_SQL', "Preparing query: $query", \OCP\Util::DEBUG);
        $result = $this -> db -> prepare($query);
        $result -> bindParam(":uid", $uid);
        \OCP\Util::writeLog('OC_USER_SQL', "Executing query...", \OCP\Util::DEBUG);
        if(!$result -> execute())
        {
            $err = $result -> errorInfo();
            \OCP\Util::writeLog('OC_USER_SQL', "Query failed: " . $err[2], \OCP\Util::DEBUG);
            return false;
        }
        \OCP\Util::writeLog('OC_USER_SQL', "Fetching results...", \OCP\Util::DEBUG);

        $exists = (bool)$result -> fetch();
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
        if(!$this -> db_conn)
        {
            return false;
        }
        $this -> doEmailSync($uid);
        $uid = $this -> doUserDomainMapping($uid);

        if(!$this -> userExists($uid))
        {
            return false;
        }

        $query = "SELECT $this->sql_column_displayname FROM $this->sql_table WHERE $this->sql_column_username = :uid";
        if($this -> sql_column_active !== '')
            $query .= " AND " .($this->sql_column_active_invert ? "NOT " : "" ).$this->sql_column_active;
        \OCP\Util::writeLog('OC_USER_SQL', "Preparing query: $query", \OCP\Util::DEBUG);
        $result = $this -> db -> prepare($query);
        $result -> bindParam(":uid", $uid);
        \OCP\Util::writeLog('OC_USER_SQL', "Executing query...", \OCP\Util::DEBUG);
        if(!$result -> execute())
        {
            $err = $result -> errorInfo();
            \OCP\Util::writeLog('OC_USER_SQL', "Query failed: " . $err[2], \OCP\Util::DEBUG);
            return false;
        }
        \OCP\Util::writeLog('OC_USER_SQL', "Fetching results...", \OCP\Util::DEBUG);
        $row = $result -> fetch();
        if(!$row)
        {
            \OCP\Util::writeLog('OC_USER_SQL', "Empty row, user has no display name or does not exist, return false", \OCP\Util::DEBUG);
            return false;
        } else
        {
            \OCP\Util::writeLog('OC_USER_SQL', "User exists, return true", \OCP\Util::DEBUG);
            $displayName = $row[$this -> sql_column_displayname];
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

        if($this -> crypt_type === 'md5crypt')
        {
            $split_salt = preg_split('/\$/', $pw_db);
            if(isset($split_salt[2]))
            {
                $salt = $split_salt[2];
            }
            $password = $this -> md5crypt($pw, $salt);
        } elseif($this -> crypt_type === 'md5')
        {
            $password = md5($pw);
        } elseif($this -> crypt_type === 'system')
        {
            // We never generate salts, as user creation is not allowed here
            $password = crypt($pw, $pw_db);
        } elseif($this -> crypt_type === 'cleartext')
        {
            $password = $pw;
        }

        // See
        // https://sourceforge.net/tracker/?func=detail&atid=937966&aid=1793352&group_id=191583
        // this is apparently useful for pam_mysql etc.
        elseif($this -> crypt_type === 'mysql_encrypt')
        {
            if(!$this -> db_conn)
            {
                return false;
            }
            if($pw_db !== "")
            {
                $salt = substr($pw_db, 0, 2);
                $query = "SELECT ENCRYPT(:pw, :salt);";
            } else
            {
                $query = "SELECT ENCRYPT(:pw);";
            }

            $result = $this -> db -> prepare($query);
            $result -> bindParam(":pw", $pw);
            if($pw_db !== "")
                $result -> bindParam(":salt", $salt);
            if(!$result -> execute())
            {
                return false;
            }
            $row = $result -> fetch();
            if(!$row)
            {
                return false;
            }
            $password = $row[0];
        } elseif($this -> crypt_type === 'mysql_password')
        {
            if(!$this -> db_conn)
            {
                return false;
            }
            $query = "SELECT PASSWORD(:pw);";

            $result = $this -> db -> prepare($query);
            $result -> bindParam(":pw", $pw);
            if(!$result -> execute())
            {
                return false;
            }
            $row = $result -> fetch();
            if(!$row)
            {
                return false;
            }
            $password = $row[0];
        }

        // The following is by Frédéric France
        elseif($this -> crypt_type === 'joomla')
        {
            $split_salt = preg_split('/:/', $pw_db);
            if(isset($split_salt[1]))
            {
                $salt = $split_salt[1];
            }
            $password = ($salt) ? md5($pw . $salt) : md5($pw);
            $password .= ':' . $salt;
		}

		elseif($this-> crypt_type === 'ssha256')
		{
			$salted_password = base64_decode(preg_replace('/{SSHA256}/i','',$pw_db));
			$salt = substr($salted_password,-(strlen($salted_password)-32));
			$password = $this->ssha256($pw,$salt);
        } else
        {
            \OCP\Util::writeLog('OC_USER_SQL', "unknown/invalid crypt_type settings: $this->crypt_type", \OCP\Util::ERROR);
            die('unknown/invalid Encryption type setting: ' . $this -> crypt_type);
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
