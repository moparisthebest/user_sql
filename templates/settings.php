<?php
$ocVersion = $_['ocVersion'];
$cfgClass = $ocVersion >= 7 ? 'section' : 'personalblock';

?>


<form id="sqlForm" action="#" method="post" class="<?php echo $cfgClass; ?>">

    <div id="sql" class="<?php echo $cfgClass; ?>">
        <legend><strong><?php echo $l->t('SQL'); ?></strong></legend>
	<ul>
	  <li><a id="sqlBasicSettings" href="#sql-1"><?php echo $l -> t('Database Settings'); ?></a></li>
          <li><a id="sqlAdvSettings" href="#sql-2"><?php echo $l->t('Advanced Settings'); ?></a></li>
        </ul>

        <fieldset id="sql-1">
           <table>
           <tr><td><label for="sql_type"><?php echo $l->t('SQL Driver');?></label></td>
                <?php $db_driver = array('mysql' => 'MySQL', 'pgsql' => 'PostgreSQL');?>
                <td><select id="sql_type" name="sql_type">
                    <?php 
                        foreach ($db_driver as $driver => $name):
                            echo $_['sql_type'];
                            if($_['sql_type'] == $driver): ?>
                                <option selected="selected" value="<?php echo $driver; ?>"><?php echo $name; ?></option>
                            <?php else: ?>
                                <option value="<?php echo $driver; ?>"><?php echo $name; ?></option>
                            <?php endif;
                        endforeach; ?>
                </select></td>
            </tr>

            <tr><td><label for="sql_host"><?php echo $l->t('Host');?></label></td><td><input type="text" id="sql_host" name="sql_host" value="<?php echo $_['sql_host']; ?>"></td></tr>
            <tr><td><label for="sql_user"><?php echo $l->t('Username');?></label></td><td><input type="text" id="sql_user" name="sql_user" value="<?php echo $_['sql_user']; ?>" /></td></tr>
            <tr><td><label for="sql_database"><?php echo $l->t('Database');?></label></td><td><input type="text" id="sql_database" name="sql_database" value="<?php echo $_['sql_database']; ?>" /></td></tr>
            <tr><td><label for="sql_password"><?php echo $l->t('Password');?></label></td><td><input type="password" id="sql_password" name="sql_password" value="<?php echo $_['sql_password']; ?>" /></td></tr>
            <tr><td><label for="sql_table"><?php echo $l->t('Table');?></label></td><td><input type="text" id="sql_table" name="sql_table" value="<?php echo $_['sql_table']; ?>" /></td></tr>
        </table>
        </fieldset>
        <fieldset id="sql-2">
        <table>
            <tr><td><label for="sql_column_username"><?php echo $l->t('Username Column');?></label></td><td><input type="text" id="sql_column_username" name="sql_column_username" value="<?php echo $_['sql_column_username']; ?>" /></td></tr>
            <tr><td><label for="sql_column_password"><?php echo $l->t('Password Column');?></label></td><td><input type="text" id="sql_column_password" name="sql_column_password" value="<?php echo $_['sql_column_password']; ?>" /></td></tr>
            <tr><td><label for="sql_column_displayname"><?php echo $l->t('Real Name Column');?></label></td><td><input type="text" id="sql_column_displayname" name="sql_column_displayname" value="<?php echo $_['sql_column_displayname']; ?>" /></td></tr>
            <tr><td><label for="crypt_type"><?php echo $l->t('Encryption Type');?></label></td>
                <?php $crypt_types = array('md5' => 'MD5', 'md5crypt' => 'MD5 Crypt', 'cleartext' => 'Cleartext', 'mysql_encrypt' => 'mySQL ENCRYPT()', 'system' => 'System (crypt)', 'mysql_password' => 'mySQL PASSWORD()', 'joomla' => 'Joomla MD5 Encryption');?>
                <td><select id="crypt_type" name="crypt_type">
                    <?php 
                        foreach ($crypt_types as $driver => $name):
                            echo $_['crypt_type'];
                            if($_['crypt_type'] == $driver): ?>
                                <option selected="selected" value="<?php echo $driver; ?>"><?php echo $name; ?></option>
                            <?php else: ?>
                                <option value="<?php echo $driver; ?>"><?php echo $name; ?></option>
                            <?php endif;
                        endforeach; ?>
                </select></td>
            </tr>
            <tr><td><label for="sql_column_active"><?php echo $l->t('User Active Column');?></label></td><td><input type="text" id="sql_column_active" name="sql_column_active" value="<?php echo $_['sql_column_active']; ?>" /></td></tr>
            <tr><td><label for="strip_domain"><?php echo $l->t('Strip Domain Part from Username');?></label></td><td><input type="checkbox" id="strip_domain" name="strip_domain" value="1"<?php if($_['strip_domain']) echo ' checked'; ?> title="Strip Domain Part from Username when logging in and retrieving username lists"></td></tr>
            <tr><td><label for="default_domain"><?php echo $l->t('Add default domain to Usernames');?></label></td><td><input type="text" id="default_domain" name="default_domain" value="<?php echo $_['default_domain']; ?>" /></td></tr>
        </table>
        </fieldset>
        <input type="hidden" name="requesttoken" value="<?php echo $_['requesttoken'] ?>" id="requesttoken" />
	<input type="hidden" name="appname" value="user_sql" />
        <input id="sqlSubmit" type="submit" value="<?php echo $l->t('Save'); ?>" />
        <div id="sql_update_message" class="statusmessage"><?php echo $l->t('Saving...'); ?></div>
        <div id="sql_error_message" class="errormessage"></div>
        <div id="sql_success_message" class="successmessage"></div>
    </div>
</form>
