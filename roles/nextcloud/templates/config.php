<?php
$CONFIG = array (
  'instanceid' => '{{vault_nextcloud_instance_id}}',
  'passwordsalt' => '{{vault_nextcloud_password_salt}}',
  'trusted_domains' => 
  array (
    0 => '{{server_ip}}',
    1 => '{{server_domain}}',
    2 => 'nextcloud.{{server_domain}}',
    3 => 'fa-nextcloud.{{server_domain}}',
    4 => 'ba-nextcloud.{{server_domain}}'
  ),
  'auth.bruteforce.protection.enabled' => false,
  'overwrite.cli.url' => '/',
  'datadirectory' => '{{nextcloud_data_path}}',
  'dbtype' => 'mysql',
  'version' => '{{current_nextcloud_version}}',
  'dbname' => 'nextcloud',
  'dbhost' => '127.0.0.1',
  'dbtableprefix' => 'oc_',
  'dbuser' => '{{vault_nextcloud_mysql_username}}',
  'dbpassword' => '{{vault_nextcloud_mysql_password}}',
  'installed' => true,
  'forcessl' => true,
  'syslog_tag' => 'nextcloud',
  'log_type' => 'file',
  'logfile' => '{{global_log}}nextcloud/nextcloud.log',
  'loglevel' => 2,
  'theme' => '',
  'maintenance' => false,
  'mail_smtpmode' => 'sendmail',
  'mail_domain' => '{{server_domain}}',
  'mail_from_address' => 'root',
  'appcodechecker' => false,
  'secret' => '{{vault_nextcloud_secret}}',
  'trashbin_retention_obligation' => 'auto',
  'updatechecker' => false,
  'appstore.experimental.enabled' => true,
  'enabledPreviewProviders' => 
  array (
    0 => 'OC\\Preview\\PNG',
    1 => 'OC\\Preview\\JPEG',
    2 => 'OC\\Preview\\GIF',
    3 => 'OC\\Preview\\BMP',
    4 => 'OC\\Preview\\XBitmap',
    5 => 'OC\\Preview\\MP3',
    6 => 'OC\\Preview\\TXT',
    7 => 'OC\\Preview\\MarkDown',
    8 => 'OC\\Preview\\PDF',
  ),
  'memcache.local' => '\\OC\\Memcache\\APCu',
  'filelocking.enabled' => true,
  'memcache.locking' => '\\OC\\Memcache\\Redis',
  'redis' => 
  array (
    'host' => '127.0.0.1',
    'port' => 6379,
    'timeout' => 0,
    'dbindex' => 0,
  ),
  'mysql.utf8mb4' => true,
  'mail_smtpauthtype' => 'LOGIN',
  'theme' => 'smartserver'
);
