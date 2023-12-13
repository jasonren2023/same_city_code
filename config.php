<?php
defined('IN_IA') or exit('Access Denied');

$config = array();

//$config['db']['master']['host'] = 'rdsetieexmuj2181rkq6dko.mysql.rds.aliyuncs.com';
//$config['db']['master']['username'] = 'demo';
//$config['db']['master']['password'] = 'ourteam123';
//$config['db']['master']['port'] = '3306';
//$config['db']['master']['database'] = 'demo';
//$config['db']['master']['charset'] = 'utf8';
//$config['db']['master']['pconnect'] = 0;
//$config['db']['master']['tablepre'] = 'ims_';

$config['db']['master']['host'] = '123.57.80.30';
$config['db']['master']['username'] = 'city';
$config['db']['master']['password'] = '444FNBBL8k68NZHn';
$config['db']['master']['port'] = '3306';
$config['db']['master']['database'] = 'city';
$config['db']['master']['charset'] = 'utf8';
$config['db']['master']['pconnect'] = 0;
$config['db']['master']['tablepre'] = 'ims_';

$config['db']['slave_status'] = false;
$config['db']['slave']['1']['host'] = '';
$config['db']['slave']['1']['username'] = '';
$config['db']['slave']['1']['password'] = '';
$config['db']['slave']['1']['port'] = '3307';
$config['db']['slave']['1']['database'] = '';
$config['db']['slave']['1']['charset'] = 'utf8';
$config['db']['slave']['1']['pconnect'] = 0;
$config['db']['slave']['1']['tablepre'] = 'ims_';
$config['db']['slave']['1']['weight'] = 0;

$config['db']['common']['slave_except_table'] = array('core_sessions');

// --------------------------  CONFIG COOKIE  --------------------------- //
$config['cookie']['pre'] = 'b906_';
$config['cookie']['domain'] = '';
$config['cookie']['path'] = '/';

// --------------------------  CONFIG SETTING  --------------------------- //
$config['setting']['charset'] = 'utf-8';
$config['setting']['cache'] = 'mysql';
$config['setting']['timezone'] = 'Asia/Shanghai';
$config['setting']['memory_limit'] = '256M';
$config['setting']['filemode'] = 0644;
$config['setting']['authkey'] = 'a132aeb1';
$config['setting']['founder'] = '1';
$config['setting']['development'] = 0;//是否开启debug模式  0=关闭；1=开启
$config['setting']['referrer'] = 0;
$config['setting']['https'] = 0;

// --------------------------  CONFIG UPLOAD  --------------------------- //
$config['upload']['image']['extentions'] = array('gif', 'jpg', 'jpeg', 'png');
$config['upload']['image']['limit'] = 5000;
$config['upload']['attachdir'] = 'attachment';
$config['upload']['audio']['extentions'] = array('mp3');
$config['upload']['audio']['limit'] = 5000;

// --------------------------  CONFIG MEMCACHE  --------------------------- //
$config['setting']['memcache']['server'] = '127.0.0.1';
$config['setting']['memcache']['port'] = 11211;
$config['setting']['memcache']['pconnect'] = 0;
$config['setting']['memcache']['timeout'] = 30;
$config['setting']['memcache']['session'] = 0;

// --------------------------  CONFIG REDIS  --------------------------- //
$config['setting']['redis']['server'] = '127.0.0.1';
$config['setting']['redis']['port'] = 6379;
$config['setting']['redis']['pconnect'] = 1;
$config['setting']['redis']['timeout'] = 30;
$config['setting']['redis']['session'] = 0;
$config['setting']['redis']['requirepass'] = '';

// --------------------------  CONFIG PROXY  --------------------------- //
$config['setting']['proxy']['host'] = '';
$config['setting']['proxy']['auth'] = '';
