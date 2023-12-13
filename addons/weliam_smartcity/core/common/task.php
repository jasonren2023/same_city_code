<?php
if(is_file('../../../../wlversion.txt')){
    $version = file_get_contents('../../../../wlversion.txt');
    define("MODULE_NAME",$version);
}else{
    define("MODULE_NAME",'weliam_smartcity');
}
require '../../../../framework/bootstrap.inc.php';
require '../../../../addons/'.MODULE_NAME.'/core/common/defines.php';
require '../../../../addons/'.MODULE_NAME.'/core/common/autoload.php';
require '../../../../addons/'.MODULE_NAME.'/vendor/autoload.php';
require '../../../../addons/'.MODULE_NAME.'/core/function/global.func.php';
global $_W,$_GPC;
ignore_user_abort();
set_time_limit(0);

$input['time'] = date('Y-m-d H:i:s',time());
$input['siteroot'] = $_W['siteroot'];
Util::wl_log('sinaTask',PATH_DATA."tasklog", $input);
$on = $_GPC['on'] ? intval($_GPC['on']) : 0;

$queue = new Queue;
$queue -> queueMain($on);