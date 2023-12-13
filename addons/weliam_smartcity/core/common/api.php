<?php
require '../../../../framework/bootstrap.inc.php';
require '../../../../addons/'.MODULE_NAME.'/core/common/defines.php';
require '../../../../addons/'.MODULE_NAME.'/core/common/autoload.php';
require '../../../../addons/'.MODULE_NAME.'/core/function/global.func.php';
global $_W,$_GPC;
ignore_user_abort();
set_time_limit(0);
$flag = $_GPC['flag'];
$_W['uniacid'] = $_GPC['uniacid'];
if($flag == 'consumption'){
	Consumption::consumptions();
}
if($flag == 'notice'){
	Consumption::notice();
}
