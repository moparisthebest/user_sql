<?php $ocVersion = $_['ocVersion'];
$cfgClass = $ocVersion >= 7 ? 'section' : 'personalblock';
?>

<div class="<?php p($cfgClass); ?>">
    <h2><?php p($l->t('SQL')); ?></h2>

<form id="sqlForm" action="#" method="post" class="<?php p($cfgClass); ?>">

    <div id="sql" class="<?php p($cfgClass); ?>">
    <label for="sql_domain_chooser"><?php p($l -> t('Settings for Domain')) ?></label>
    <select id="sql_domain_chooser" name="sql_domain_chooser">
        <?php foreach ($_['allowed_domains'] as $domain): ?>
            <option value="<?php p($domain); ?>"><?php p($domain); ?></option>
        <?php endforeach ?>
    </select>
    <ul>
      <li><a id="sqlBasicSettings" href="#sql-1"><?php p($l -> t('Basic Settings')); ?></a></li>
      <li><a id="sqlAdvSettings" href="#sql-2"><?php p($l -> t('Advanced Settings')); ?></a></li>
    </ul>

        <fieldset id="sql-1">
           <table>
           <tr><td><label for="sql_driver"><?php p($l -> t('SQL Driver')); ?></label></td>
                <?php $db_driver = array('mysql' => 'MySQL', 'pgsql' => 'PostgreSQL'); ?>
                <td><select id="sql_driver" name="sql_driver">
                    <?php 
                        foreach ($db_driver as $driver => $name):
                            //echo $_['sql_driver'];
                            if($_['sql_driver'] === $driver): ?>
                                <option selected="selected" value="<?php p($driver); ?>"><?php p($name); ?></option>
                            <?php else: ?>
                                <option value="<?php p($driver); ?>"><?php p($name); ?></option>
                            <?php endif;
                        endforeach;
                    ?>
                </select></td>
            </tr>

            <tr><td><label for="sql_hostname"><?php p($l -> t('Host')); ?></label></td><td><input type="text" id="sql_hostname" name="sql_hostname" value="<?php p($_['sql_hostname']); ?>"></td></tr>
            <tr><td><label for="sql_username"><?php p($l -> t('Username')); ?></label></td><td><input type="text" id="sql_username" name="sql_username" value="<?php p($_['sql_username']); ?>" /></td></tr>
            <tr><td><label for="sql_database"><?php p($l -> t('Database')); ?></label></td><td><input type="text" id="sql_database" name="sql_database" value="<?php p($_['sql_database']); ?>" /></td></tr>
            <tr><td><label for="sql_password"><?php p($l -> t('Password')); ?></label></td><td><input type="password" id="sql_password" name="sql_password" value="<?php p($_['sql_password']); ?>" /></td></tr>
            <tr><td><label for="sql_table"><?php p($l -> t('Table')); ?></label></td><td><input type="text" id="sql_table" name="sql_table" value="<?php p($_['sql_table']); ?>" /></td></tr>
        </table>
        </fieldset>
        <fieldset id="sql-2">
        <table>
            <tr><td><label for="col_username"><?php p($l -> t('Username Column')); ?></label></td><td><input type="text" id="col_username" name="col_username" value="<?php p($_['col_username']); ?>" /></td></tr>
            <tr><td><label for="col_password"><?php p($l -> t('Password Column')); ?></label></td><td><input type="text" id="col_password" name="col_password" value="<?php p($_['col_password']); ?>" /></td></tr>
            <tr><td><label for="set_allow_pwchange"><?php p($l -> t('Allow password changing (read README!)')); ?></label></td><td><input type="checkbox" id="set_allow_pwchange" name="set_allow_pwchange" value="1"<?php
            if($_['set_allow_pwchange'])
                p(' checked');
 ?> title="Allow changing passwords. Imposes a security risk as password salts are not recreated"></td></tr>
            <tr><td><label for="col_displayname"><?php p($l -> t('Real Name Column')); ?></label></td><td><input type="text" id="col_displayname" name="col_displayname" value="<?php p($_['col_displayname']); ?>" /></td></tr>
            <tr><td><label for="set_crypt_type"><?php p($l -> t('Encryption Type')); ?></label></td>
                <?php $crypt_types = array('md5' => 'MD5', 'md5crypt' => 'MD5 Crypt', 'cleartext' => 'Cleartext', 'mysql_encrypt' => 'mySQL ENCRYPT()', 'system' => 'System (crypt)', 'mysql_password' => 'mySQL PASSWORD()', 'joomla' => 'Joomla MD5 Encryption', 'joomla2' => 'Joomla > 2.5.18 phpass', 'ssha256' => 'Salted SSHA256', 'redmine' => 'Redmine'); ?>
                <td><select id="set_crypt_type" name="set_crypt_type">
                    <?php 
                        foreach ($crypt_types as $driver => $name):
                            //echo $_['set_crypt_type'];
                            if($_['set_crypt_type'] === $driver): ?>
                                <option selected="selected" value="<?php p($driver); ?>"><?php p($name); ?></option>
                            <?php else: ?>
                                <option value="<?php p($driver); ?>"><?php p($name); ?></option>
                            <?php endif;
                        endforeach;
                    ?>
                </select></td>
            </tr>
            <tr><td><label for="col_active"><?php p($l -> t('User Active Column')); ?></label></td><td><input type="text" id="col_active" name="col_active" value="<?php p($_['col_active']); ?>" /></td></tr>
            <tr><td><label for="set_active_invert"><?php p($l -> t('Invert Active Value')); ?></label></td><td><input type="checkbox" id="set_active_invert" name="set_active_invert" value="1"<?php
            if($_['set_active_invert'])
                p(' checked');
            ?> title="Invert the logic of the active column (for blocked users in the SQL DB)" /></td></tr>
            <tr><td><label for="col_email"><?php p($l -> t('E-Mail Column')); ?></label></td><td><input type="text" id="col_email" name="col_email" value="<?php p($_['col_email']); ?>" /></td></tr>
            <tr><td><label for="set_mail_sync_mode"><?php p($l -> t('E-Mail address sync mode')); ?></label></td>
                <?php $mail_modes = array('none' => 'No Synchronisation', 'initial' => 'Synchronise only once', 'forceoc' => 'ownCloud always wins', 'forcesql' => 'SQL always wins'); ?>
                <td><select id="set_mail_sync_mode" name="set_mail_sync_mode">
                    <?php
                    foreach ($mail_modes as $mode => $name):
                        //echo $_['set_mail_sync_mode'];
                        if($_['set_mail_sync_mode'] === $mode): ?>
                            <option selected="selected" value="<?php p($mode); ?>"><?php p($name); ?></option>
                        <?php else: ?>
                            <option value="<?php p($mode); ?>"><?php p($name); ?></option>
                        <?php endif;
                    endforeach;
                    ?>
                </select>
            </td></tr>
            <tr><td><label for="set_default_domain"><?php p($l -> t('Append Default Domain')); ?></label></td><td><input type="text" id="set_default_domain", name="set_default_domain" value="<?php p($_['set_default_domain']); ?>" /></td></tr>
            <tr><td><label for="set_strip_domain"><?php p($l -> t('Strip Domain Part from Username')); ?></label></td><td><input type="checkbox" id="set_strip_domain" name="set_strip_domain" value="1"<?php
            if($_['set_strip_domain'])
                p(' checked');
            ?> title="Strip Domain Part from Username when logging in and retrieving username lists"></td></tr>            
        </table>
        </fieldset>
        <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']); ?>" id="requesttoken" />
    <input type="hidden" name="appname" value="user_sql" />
        <input id="sqlSubmit" type="submit" value="<?php p($l -> t('Save')); ?>" />
        <div id="sql_update_message" class="statusmessage"><?php p($l -> t('Saving...')); ?></div>
        <div id="sql_loading_message" class="statusmessage"><?php p($l -> t('Loading...')); ?></div>
        <div id="sql_error_message" class="errormessage"></div>
        <div id="sql_success_message" class="successmessage"></div>
    </div>
</form>
</div>
