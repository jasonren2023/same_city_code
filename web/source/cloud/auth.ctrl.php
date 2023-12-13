<?php
defined('IN_IA') or exit('Access Denied');
if(is_file('../../../wlversion.txt')){
    $version = file_get_contents('../../../wlversion.txt');
    define("MODULE_NAME",$version);
}else{
    define("MODULE_NAME",'weliam_smartcity');
}
$dos = array(
    'auth_info'        => ['auth', 'auth'],
    'auth_upgrade'     => ['upgrade', 'auth'],
    'auth_upgrade_log' => ['upgrade_log', 'auth'],
    'app_info'         => ['index', 'plugin'],
    'app_pem'          => ['account_list', 'plugin'],
    'db_m'             => ['datemana', 'database'],
    'db_sql'           => ['run', 'database'],
    'set_info'         => ['base', 'wlsysset'],
    'set_que'          => ['taskcover', 'wlsysset'],
    'set_url'          => ['jumpadmin', 'wlsysset'],
);
$do = in_array($do, array_keys($dos)) ? $do : 'auth_info';

$url = url('site/entry/' . $dos[$do][0], ['m' => MODULE_NAME, 'p' => 'cloud', 'ac' => $dos[$do][1], 'lct' => 'wl'], true);

template('cloud/index');