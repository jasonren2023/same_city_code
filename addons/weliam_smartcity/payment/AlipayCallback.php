<?php
/**
 * Comment: 支付宝支付异步通知地址
 * Author: zzw
 */

//前置操作
define('IN_UNIAPP', true);
header('Access-Control-Allow-Origin:*');
if(is_file('../../../wlversion.txt')){
    $version = file_get_contents('../../../wlversion.txt');
    define("MODULE_NAME",$version);
}else{
    define("MODULE_NAME",'weliam_smartcity');
}
require '../../../framework/bootstrap.inc.php';
require '../../../addons/'.MODULE_NAME.'/core/common/defines.php';
require PATH_MODULE . "/vendor/autoload.php";
require PATH_MODULE . "core/common/autoload.php";
Func_loader::core('global');
global $_W, $_GPC;
load()->model('attachment');
$_W['siteroot'] = str_replace(array('/addons/'.MODULE_NAME.'/payment','/addons/weliam_smartcity/payment'), '', $_W['siteroot']);
//调用处理方法
Payment::AliPay_notify();

