<?php

/**
 * ownCloud - user_sql
 *
 * @author Andreas Böhler and contributors
 * @copyright 2012-2015 Andreas Böhler <dev (at) aboehler (dot) at>
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
 
namespace OCA\user_sql\lib;

class Helper {

    protected $db;
    protected $db_conn;
    protected $settings;
    
    public function __construct()
    {
        $this->db_conn = false;
    }

    public function getParameterArray()
    {
        $params = array(
            'sql_hostname',
            'sql_username',
            'sql_password',
            'sql_database',
            'sql_table',
            'sql_driver',
            'col_username',
            'col_password',
            'col_active',
            'col_displayname',
            'col_email',
            'set_active_invert',
            'set_allow_pwchange',
            'set_default_domain',
            'set_strip_domain',
            'set_crypt_type',
            'set_mail_sync_mode'
        );

        return $params;
    }
    
    public function loadSettingsForDomain($domain)
    {
        \OCP\Util::writeLog('OC_USER_SQL', "Trying to load settings for domain: " . $domain, \OCP\Util::DEBUG);
        $settings = array();
        $sql_host = \OC::$server->getConfig()->getAppValue('user_sql', 'sql_hostname_'.$domain, '');
        if($sql_host === '')
        {
            $domain = 'default';
        }
        $params = $this -> getParameterArray();
        foreach($params as $param)
        {
            $settings[$param] = \OC::$server->getConfig()->getAppValue('user_sql', $param.'_'.$domain, '');
        }
        \OCP\Util::writeLog('OC_USER_SQL', "Loaded settings for domain: " . $domain, \OCP\Util::DEBUG);
        return $settings;
    }    
    
    public function runQuery($type, $params, $execOnly = false, $fetchArray = false, $limits = array())
    {
        \OCP\Util::writeLog('OC_USER_SQL', "Entering runQuery for type: " . $type, \OCP\Util::DEBUG);        
        if(!$this -> db_conn)
            return false;
        
        switch($type)
        {
            case 'getMail':
                $query = "SELECT ".$this->settings['col_email']." FROM ".$this->settings['sql_table']." WHERE ".$this->settings['col_username']." = :uid";
            break;

            case 'setMail':
                $query = "UPDATE ".$this->settings['sql_table']." SET ".$this->settings['col_email']." = :currMail WHERE ".$this->settings['col_username']." = :uid";
            break;

            case 'getPass':
                $query = "SELECT ".$this->settings['col_password']." FROM ".$this->settings['sql_table']." WHERE ".$this->settings['col_username']." = :uid";
                if($this -> settings['col_active'] !== '')
                    $query .= " AND " .($this -> settings['set_active_invert'] === 'true' ? "NOT " : "" ) . $this -> settings['col_active'];
            break;

            case 'setPass':
                $query = "UPDATE ".$this->settings['sql_table']." SET ".$this->settings['col_password']." = :enc_password WHERE ".$this->settings['col_username'] ." = :uid";
            break;

            case 'getRedmineSalt':
                $query = "SELECT salt FROM ".$this->settings['sql_table']." WHERE ".$this->settings['col_username'] ." = :uid;";
            break;

            case 'countUsers':
                $query = "SELECT COUNT(*) FROM ".$this->settings['sql_table'];
                if($this -> settings['col_active'] !== '')
                    $query .= " WHERE " .($this -> settings['set_active_invert'] === 'true' ? "NOT " : "" ) . $this -> settings['col_active'];
            break;

            case 'getUsers':
                $query = "SELECT ".$this->settings['col_username']." FROM ".$this->settings['sql_table'];
                $query .= " WHERE ".$this->settings['col_username']." LIKE :search";
                if($this -> settings['col_active'] !== '')
                    $query .= " AND " .($this -> settings['set_active_invert'] === 'true' ? "NOT " : "" ) . $this -> settings['col_active'];
                $query .= " ORDER BY ".$this->settings['col_username'];             
            break;

            case 'userExists':
                $query = "SELECT ".$this->settings['col_username']." FROM ".$this->settings['sql_table']." WHERE ".$this->settings['col_username']." = :uid";
                if($this -> settings['col_active'] !== '')
                    $query .= " AND " .($this -> settings['set_active_invert'] === 'true' ? "NOT " : "" ) . $this -> settings['col_active'];
            break;

            case 'getDisplayName':
                $query = "SELECT ".$this->settings['col_displayname']." FROM ".$this->settings['sql_table']." WHERE ".$this->settings['col_username']." = :uid";
                if($this -> settings['col_active'] !== '')
                    $query .= " AND " .($this -> settings['set_active_invert'] === 'true' ? "NOT " : "" ) . $this -> settings['col_active'];
            break;

            case 'mysqlEncryptSalt':
                $query = "SELECT ENCRYPT(:pw, :salt);";
            break;

            case 'mysqlEncrypt':
                $query = "SELECT ENCRYPT(:pw);";
            break;

            case 'mysqlPassword':
                $query = "SELECT PASSWORD(:pw);";
            break;
        }

        if(isset($limits['limit']) && $limits['limit'] !== null)
        {
            $limit = intval($limits['limit']);
            $query .= " LIMIT ".$limit;
        }

        if(isset($limits['offset']) && $limits['offset'] !== null)
        {
            $offset = intval($limits['offset']);
            $query .= " OFFSET ".$offset; 
        }

        \OCP\Util::writeLog('OC_USER_SQL', "Preparing query: $query", \OCP\Util::DEBUG);
        $result = $this -> db -> prepare($query);
        foreach($params as $param => $value)
        {
            $result -> bindParam(":".$param, $value);
        }
        \OCP\Util::writeLog('OC_USER_SQL', "Executing query...", \OCP\Util::DEBUG);
        if(!$result -> execute())
        {
            $err = $result -> errorInfo();
            \OCP\Util::writeLog('OC_USER_SQL', "Query failed: " . $err[2], \OCP\Util::DEBUG);            
            return false;
        }
        if($execOnly === true)
        {
            return true;
        }
        \OCP\Util::writeLog('OC_USER_SQL', "Fetching result...", \OCP\Util::DEBUG);
        if($fetchArray === true)
            $row = $result -> fetchAll();
        else
            $row = $result -> fetch();

        if(!$row)
        {
            return false;
        }
        return $row;
    }

    public function connectToDb($settings)
    {
        $this -> settings = $settings;
        $dsn = $this -> settings['sql_driver'] . ":host=" . $this -> settings['sql_hostname'] . ";dbname=" . $this -> settings['sql_database'];
        try
        {
            $this -> db = new \PDO($dsn, $this -> settings['sql_username'], $this -> settings['sql_password']);
            $this -> db -> query("SET NAMES 'UTF8'");
            $this -> db_conn = true;
            return true;
        } 
        catch (\PDOException $e)
        {
            \OCP\Util::writeLog('OC_USER_SQL', 'Failed to connect to the database: ' . $e -> getMessage(), \OCP\Util::ERROR);
            $this -> db_conn = false;
            return false;
        }
    }


}