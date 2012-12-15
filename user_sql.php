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

class OC_USER_SQL extends OC_User_Backend implements OC_User_Interface {

    // cached settings
    protected $sql_host;
    protected $sql_username;
    protected $sql_database;
    protected $sql_password;
    protected $sql_table;
    protected $sql_column_username;
    protected $sql_column_password;
    protected $sql_column_active;
    protected $sql_type;
    protected $db_conn;
    protected $db;
    protected $default_domain;
    protected $strip_domain;
    protected $crypt_type;

    public function __construct() 
    {
        $this->db_conn = false;
        $this->sql_host = OCP\Config::getAppValue('user_sql', 'sql_host', '');
        $this->sql_username = OCP\Config::getAppValue('user_sql', 'sql_user', '');
        $this->sql_database = OCP\Config::getAppValue('user_sql', 'sql_database', '');
        $this->sql_password = OCP\Config::getAppValue('user_sql', 'sql_password', '');
        $this->sql_table = OCP\Config::getAppValue('user_sql', 'sql_table', '');
        $this->sql_column_username = OCP\Config::getAppValue('user_sql', 'sql_column_username', '');
        $this->sql_column_password = OCP\Config::getAppValue('user_sql', 'sql_column_password', '');
        $this->sql_column_active = OCP\Config::getAppValue('user_sql', 'sql_column_active', '');
        $this->sql_type = OCP\Config::getAppValue('user_sql', 'sql_type', '');
        $this->default_domain = OCP\Config::getAppValue('user_sql', 'default_domain', '');
        $this->strip_domain = OCP\Config::getAppValue('user_sql', 'strip_domain', 0);
        $this->crypt_type = OCP\Config::getAppValue('user_sql', 'crypt_type', 'md5crypt');
        $dsn = $this->sql_type.":host=".$this->sql_host.";dbname=".$this->sql_database;
        try 
        {
            $this->db = new PDO($dsn, $this->sql_username, $this->sql_password);
            $this->db_conn = true;
        }
        catch (PDOException $e) 
        {
            OC_Log::write('OC_USER_SQL', 'OC_USER_SQL, Failed to connect to the database: ' . $e->getMessage(), OC_Log::ERROR);
        }
        return false;
    }

    public function implementsAction($actions) 
    {
        return (bool)((OC_USER_BACKEND_CHECK_PASSWORD) & $actions);
    }

    public function createUser() {
        // Can't create user
        OC_Log::write('OC_USER_SQL', 'Not possible to create local users from web frontend using SQL user backend', OC_Log::ERROR);
        return false;
    }

    public function deleteUser( $uid ) 
    {
        // Can't delete user
        OC_Log::write('OC_USER_SQL', 'Not possible to delete local users from web frontend using SQL user backend', OC_Log::ERROR);
        return false;
    }

    public function setPassword ( $uid, $password ) {
        // Update the user's password - this might affect other services, that user the same database, as well
        if(!$this->db_conn)
        {
            return false;
        }
        if($this->strip_domain)
        {
            $uid .= "@".$this->default_domain;
        }
        $query = "SELECT $this->sql_column_password FROM $this->sql_table WHERE $this->sql_column_username = '$uid'";
        $result = $this->db->prepare($query);
        if(!$result->execute())
        {
            return false;
        }
        $row = $result->fetch();
        if(!$row)
        {
            return false;
        }
        $old_password = $row[$this->sql_column_password];
        $enc_password = pacrypt($password, $old_password);
        $query = "UPDATE $this->sql_table SET $this->sql_column_password = '$enc_password' WHERE $this->sql_column_username = '$uid'";
        $result = $this->db->prepare($query);
        if(!$result->execute())
        {
            return false;
        }
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
        if(!$this->db_conn)
        {
            return false;
        }
        $suid = $uid;
        if($this->strip_domain)
        {
            $suid = $uid."@".$this->default_domain;
        }
        
        $query = "SELECT $this->sql_column_username, $this->sql_column_password FROM $this->sql_table WHERE $this->sql_column_username = '$suid'";
        if($this->sql_column_active != '')
            $query .= " AND $this->sql_column_active = 1";
        $result = $this->db->prepare($query);
        if(!$result->execute())
        {
            return false;
        }
        $row = $result->fetch();
        if(!$row)
        {
            return false;
        }
        if($this->pacrypt($password, $row[$this->sql_column_password]) == $row[$this->sql_column_password])
        {
            return $uid;
        }
        else
        {
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
       $users = array();
       if(!$this->db_conn)
       {
        return false;
       }
       $query = "SELECT $this->sql_column_username FROM $this->sql_table";
       if($search != '')
          $query .= " WHERE $this->sql_column_username LIKE '%$search%'";
        if($this->sql_column_active != '')
        {
            if($search != '')
                $query .= " AND";
            else
                $query .= " WHERE";
            $query .= " $this->sql_column_active = 1";
        }
       if($limit != null)
          $query .= " LIMIT $limit";
       if($offset != null)
          $query .= " OFFSET $offset";
       $result = $this->db->prepare($query);
       if(!$result->execute())
       {
        return array();
       }
       while($row = $result->fetch())
       {
           $uid = $row[$this->sql_column_username];
           if($this->strip_domain)
           {
               $uid = explode("@", $uid);
               $uid = $uid[0];
           }
           $users[] = $uid;
       }
       return $users;
    }

    /**
    * @brief check if a user exists
    * @param string $uid the username
    * @return boolean
    */

    public function userExists($uid)
    {
        if(!$this->db_conn)
        {
            return false;
        }
        
        if($this->strip_domain)
        {
            $uid .= "@".$this->default_domain;
        }            
        
        $query = "SELECT $this->sql_column_username FROM $this->sql_table WHERE $this->sql_column_username = '$uid'";
        if($this->sql_column_active != '')
            $query .= " AND $this->sql_column_active = 1";
        $result = $this->db->prepare($query);
        if(!$result->execute())
        {
            return false;
        }
        $row = $result->fetch();
        if(!$row)
        {
            return false;
        }
        else
        {
            return true;
        }

    }
       
     /**
     * The following functions were directly taken from PostfixAdmin and just slightly modified
     * to suit our needs.
     * Encrypt a password, using the apparopriate hashing mechanism as defined in 
     * config.inc.php ($this->crypt_type). 
     * When wanting to compare one pw to another, it's necessary to provide the salt used - hence
     * the second parameter ($pw_db), which is the existing hash from the DB.
     *
     * @param string $pw
     * @param string $encrypted password
     * @return string encrypted password.
     */
    private function pacrypt ($pw, $pw_db="")
    {
        $pw = stripslashes($pw);
        $password = "";
        $salt = "";

        if ($this->crypt_type == 'md5crypt') {
            $split_salt = preg_split ('/\$/', $pw_db);
            if (isset ($split_salt[2])) {
                $salt = $split_salt[2];
            }
            $password = $this->md5crypt ($pw, $salt);
        }

        elseif ($this->crypt_type == 'md5') {
            $password = md5($pw);
        }

        elseif ($this->crypt_type == 'system') {
            if (preg_match("/\\$1\\$/", $pw_db)) {
                $split_salt = preg_split ('/\$/', $pw_db);
                $salt = "\$1\$${split_salt[2]}\$";
            }
            else {
                if (strlen($pw_db) == 0) {
                    $salt = substr (md5 (mt_rand ()), 0, 2);
                }
                else {
                    $salt = substr ($pw_db, 0, 2);
                }
            }
            $password = crypt ($pw, $salt);
        }

        elseif ($this->crypt_type == 'cleartext') {
            $password = $pw;
        }

        // See https://sourceforge.net/tracker/?func=detail&atid=937966&aid=1793352&group_id=191583
        // this is apparently useful for pam_mysql etc.
        elseif ($this->crypt_type == 'mysql_encrypt')
        {
            if ($pw_db!="") {
                $salt=substr($pw_db,0,2);
                $query = "SELECT ENCRYPT('".$pw."','".$salt."');";
            } else {
                $query = "SELECT ENCRYPT('".$pw."');";
            }

            if(!$this->db_conn)
            {
                return false;
            }
            $result = $this->db->prepare($query);
            if(!$result->execute())
            {
                return false;
            }
            $row = $result->fetch();
            if(!$row)
            {
                return false;
            }
            $password = $row[0];
        }

        else {
            die ('unknown/invalid $CONF["encrypt"] setting: ' . $this->crypt_type);
        }

        return $password;
    }

    //
    // md5crypt
    // Action: Creates MD5 encrypted password
    // Call: md5crypt (string cleartextpassword)
    //

    function md5crypt ($pw, $salt="", $magic="")
    {
        $MAGIC = "$1$";

        if ($magic == "") $magic = $MAGIC;
        if ($salt == "") $salt = $this->create_salt ();
        $slist = explode ("$", $salt);
        if ($slist[0] == "1") $salt = $slist[1];

        $salt = substr ($salt, 0, 8);
        $ctx = $pw . $magic . $salt;
        $final = $this->hex2bin (md5 ($pw . $salt . $pw));

        for ($i=strlen ($pw); $i>0; $i-=16)
        {
            if ($i > 16)
            {
                $ctx .= substr ($final,0,16);
            }
            else
            {
                $ctx .= substr ($final,0,$i);
            }
        }
        $i = strlen ($pw);

        while ($i > 0)
        {
            if ($i & 1) $ctx .= chr (0);
            else $ctx .= $pw[0];
            $i = $i >> 1;
        }
        $final = $this->hex2bin (md5 ($ctx));

        for ($i=0;$i<1000;$i++)
        {
            $ctx1 = "";
            if ($i & 1)
            {
                $ctx1 .= $pw;
            }
            else
            {
                $ctx1 .= substr ($final,0,16);
            }
            if ($i % 3) $ctx1 .= $salt;
            if ($i % 7) $ctx1 .= $pw;
            if ($i & 1)
            {
                $ctx1 .= substr ($final,0,16);
            }
            else
            {
                $ctx1 .= $pw;
            }
            $final = $this->hex2bin (md5 ($ctx1));
        }
        $passwd = "";
        $passwd .= $this->to64 (((ord ($final[0]) << 16) | (ord ($final[6]) << 8) | (ord ($final[12]))), 4);
        $passwd .= $this->to64 (((ord ($final[1]) << 16) | (ord ($final[7]) << 8) | (ord ($final[13]))), 4);
        $passwd .= $this->to64 (((ord ($final[2]) << 16) | (ord ($final[8]) << 8) | (ord ($final[14]))), 4);
        $passwd .= $this->to64 (((ord ($final[3]) << 16) | (ord ($final[9]) << 8) | (ord ($final[15]))), 4);
        $passwd .= $this->to64 (((ord ($final[4]) << 16) | (ord ($final[10]) << 8) | (ord ($final[5]))), 4);
        $passwd .= $this->to64 (ord ($final[11]), 2);
        return "$magic$salt\$$passwd";
    }

    function create_salt ()
    {
        srand ((double) microtime ()*1000000);
        $salt = substr (md5 (rand (0,9999999)), 0, 8);
        return $salt;
    }

    function to64 ($v, $n)
    {
        $ITOA64 = "./0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        $ret = "";
        while (($n - 1) >= 0)
        {
            $n--;
            $ret .= $ITOA64[$v & 0x3f];
            $v = $v >> 6;
        }
        return $ret;
    }

}

?>
