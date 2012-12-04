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

        public function __construct() {
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

	    public function implementsAction($actions) {
		    return (bool)((OC_USER_BACKEND_CHECK_PASSWORD) & $actions);
	    }

	    public function createUser() {
            // Can't create user
            OC_Log::write('OC_USER_SQL', 'Not possible to create local users from web frontend using SQL user backend', OC_Log::ERROR);
            return false;
        }

        public function deleteUser( $uid ) {
            // Can't delete user
            OC_Log::write('OC_USER_SQL', 'Not possible to delete local users from web frontend using SQL user backend', OC_Log::ERROR);
            return false;
        }

        public function setPassword ( $uid, $password ) {
            // We can't change user password
            OC_Log::write('OC_USER_SQL', 'Not possible to change password for local users from web frontend using SQL user backend', OC_Log::ERROR);
            return false;
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
            
		    $query = "SELECT $this->sql_column_username, $this->sql_column_password FROM $this->sql_table WHERE $this->sql_column_username = '$uid'";
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
		    if(crypt($password, $row[$this->sql_column_password]) == $row[$this->sql_column_password])
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

       public function getUsers($search = '', $limit = null, $offset = null){
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
		       $users[] = $row[$this->sql_column_username];
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

}

?>
