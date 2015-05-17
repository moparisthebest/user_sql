<?php $ocVersion = $_['ocVersion'];
$cfgClass = $ocVersion >= 7 ? 'section' : 'personalblock';
?>


<form id="sqlForm" action="#" method="post" class="<?php echo $cfgClass; ?>">

    <div id="sql" class="<?php echo $cfgClass; ?>">
        <legend><strong><?php echo $l -> t('SQL'); ?></strong></legend>
	<ul>
	  <li><a id="sqlBasicSettings" href="#sql-1"><?php echo $l -> t('Database Settings'); ?></a></li>
          <li><a id="sqlAdvSettings" href="#sql-2"><?php echo $l -> t('Column/Password Settings'); ?></a></li>
          <li><a id="sqlDomainSettings" href="#sql-3"><?php echo $l -> t('Domain Settings'); ?></a></li>
        </ul>

        <fieldset id="sql-1">
           <table>
           <tr><td><label for="sql_type"><?php echo $l -> t('SQL Driver'); ?></label></td>
                <?php $db_driver = array('mysql' => 'MySQL', 'pgsql' => 'PostgreSQL'); ?>
                <td><select id="sql_type" name="sql_type">
                    <?php 
                        foreach ($db_driver as $driver => $name):
                            echo $_['sql_type'];
                            if($_['sql_type'] == $driver): ?>
                                <option selected="selected" value="<?php echo $driver; ?>"><?php echo $name; ?></option>
                            <?php else: ?>
                                <option value="<?php echo $driver; ?>"><?php echo $name; ?></option>
                            <?php endif;
                                    endforeach;
 ?>
                </select></td>
            </tr>

            <tr><td><label for="sql_host"><?php echo $l -> t('Host'); ?></label></td><td><input type="text" id="sql_host" name="sql_host" value="<?php echo $_['sql_host']; ?>"></td></tr>
            <tr><td><label for="sql_user"><?php echo $l -> t('Username'); ?></label></td><td><input type="text" id="sql_user" name="sql_user" value="<?php echo $_['sql_user']; ?>" /></td></tr>
            <tr><td><label for="sql_database"><?php echo $l -> t('Database'); ?></label></td><td><input type="text" id="sql_database" name="sql_database" value="<?php echo $_['sql_database']; ?>" /></td></tr>
            <tr><td><label for="sql_password"><?php echo $l -> t('Password'); ?></label></td><td><input type="password" id="sql_password" name="sql_password" value="<?php echo $_['sql_password']; ?>" /></td></tr>
            <tr><td><label for="sql_table"><?php echo $l -> t('Table'); ?></label></td><td><input type="text" id="sql_table" name="sql_table" value="<?php echo $_['sql_table']; ?>" /></td></tr>
        </table>
        </fieldset>
        <fieldset id="sql-2">
        <table>
            <tr><td><label for="sql_column_username"><?php echo $l -> t('Username Column'); ?></label></td><td><input type="text" id="sql_column_username" name="sql_column_username" value="<?php echo $_['sql_column_username']; ?>" /></td></tr>
            <tr><td><label for="sql_column_password"><?php echo $l -> t('Password Column'); ?></label></td><td><input type="text" id="sql_column_password" name="sql_column_password" value="<?php echo $_['sql_column_password']; ?>" /></td></tr>
            <tr><td><label for="sql_allow_password_change"><?php echo $l -> t('Allow password changing (read README!)'); ?></label></td><td><input type="checkbox" id="allow_password_change" name="allow_password_change" value="1"<?php
            if($_['allow_password_change'])
                echo ' checked';
 ?> title="Allow changing passwords. Imposes a security risk as password salts are not recreated"></td></tr>
            <tr><td><label for="sql_column_displayname"><?php echo $l -> t('Real Name Column'); ?></label></td><td><input type="text" id="sql_column_displayname" name="sql_column_displayname" value="<?php echo $_['sql_column_displayname']; ?>" /></td></tr>
            <tr><td><label for="crypt_type"><?php echo $l -> t('Encryption Type'); ?></label></td>
                <?php $crypt_types = array('md5' => 'MD5', 'md5crypt' => 'MD5 Crypt', 'cleartext' => 'Cleartext', 'mysql_encrypt' => 'mySQL ENCRYPT()', 'system' => 'System (crypt)', 'mysql_password' => 'mySQL PASSWORD()', 'joomla' => 'Joomla MD5 Encryption', 'joomla2' => 'Joomla > 2.5.18 phpass', 'ssha256' => 'Salted SSHA256', 'redmine' => 'Redmine'); ?>
                <td><select id="crypt_type" name="crypt_type">
                    <?php 
                        foreach ($crypt_types as $driver => $name):
                            echo $_['crypt_type'];
                            if($_['crypt_type'] == $driver): ?>
                                <option selected="selected" value="<?php echo $driver; ?>"><?php echo $name; ?></option>
                            <?php else: ?>
                                <option value="<?php echo $driver; ?>"><?php echo $name; ?></option>
                            <?php endif;
                                    endforeach;
 ?>
                </select></td>
            </tr>
            <tr><td><label for="sql_column_active"><?php echo $l -> t('User Active Column'); ?></label></td><td><input type="text" id="sql_column_active" name="sql_column_active" value="<?php echo $_['sql_column_active']; ?>" /></td></tr>
        </table>
        </fieldset>
        <fieldset id="sql-3">
        	<table>
        		<tr><td><label for="domain_settings"><?php echo $l -> t('Domain Settings'); ?></label></td><td><table>
        			<tr><td><input type="radio" name="domain_settings" id="domain_none" value="none" <?php
                    if($_['domain_settings'] == "" || $_['domain_settings'] == "none")
                        echo 'checked="checked"';
 ?>><?php echo $l->t('No Mapping') ?></td></tr>
        			<tr><td><input type="radio" name="domain_settings" id="domain_server" value="server" <?php
                    if($_['domain_settings'] == "server")
                        echo 'checked="checked"';
 ?>><?php echo $l->t('Append Server Hostname') ?></td><td></td></tr>        			
        			<tr><td><input type="radio" name="domain_settings" id="domain_default" value="default" <?php
                    if($_['domain_settings'] == "default")
                        echo 'checked="checked"';
 ?>><?php echo $l->t('Append Default') ?></td><td><input type="text" id="default_domain" name="default_domain" value="<?php echo $_['default_domain']; ?>" /></td></tr>
        			<tr><td><input type="radio" name="domain_settings" id="domain_mapping" value="mapping" <?php
                    if($_['domain_settings'] == "mapping")
                        echo 'checked="checked"';
 ?>><?php echo $l->t('Map Domains') ?></td><td>
        					<table id="domain_map_entries" cellspacing="2" cellpadding="2">
    							<tbody>
    								<tr><th><input type="text" placeholder="Server Domain" id="inputServerDomain"></th><th><input type="text" placeholder="Map to Domain" id="inputMapDomain"></th><th><input id="domainAddMap" type="submit" value="<?php echo $l -> t('Add Entry'); ?>" /></th></tr>
    								<?php $domains = explode(",", $_['domain_array']);
                                        $maps = explode(",", $_['map_array']);
                                        for($i = 0; $i < count($domains); $i++)
                                        {
                                            if(trim($domains[$i]) != "" && trim($domains[$i]) != "")
                                                echo "<tr><td>" . htmlspecialchars($domains[$i]) . "</td><td>" . htmlspecialchars($maps[$i]) . "</td><td><a class=\"deleteLink\" href=\"#\" >delete</a></td></tr>";
                                        }
									?>
    							</tbody>
        					</table></td></tr>
        		</table></td></tr>
            <tr><td><label for="strip_domain"><?php echo $l -> t('Strip Domain Part from Username'); ?></label></td><td><input type="checkbox" id="strip_domain" name="strip_domain" value="1"<?php
            if($_['strip_domain'])
                echo ' checked';
 ?> title="Strip Domain Part from Username when logging in and retrieving username lists"></td></tr>
	
        	</table>
        </fieldset>
        <input type="hidden" name="requesttoken" value="<?php echo $_['requesttoken'] ?>" id="requesttoken" />
	<input type="hidden" name="appname" value="user_sql" />
        <input id="sqlSubmit" type="submit" value="<?php echo $l -> t('Save'); ?>" />
        <div id="sql_update_message" class="statusmessage"><?php echo $l -> t('Saving...'); ?></div>
        <div id="sql_error_message" class="errormessage"></div>
        <div id="sql_success_message" class="successmessage"></div>
    </div>
</form>
