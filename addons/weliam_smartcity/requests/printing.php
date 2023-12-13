<?php
/**
 * Comment: 打印请求
 * Author: zzw
 */

//前置操作
define('IN_UNIAPP', true);
header('Access-Control-Allow-Origin:*');
require '../../../framework/bootstrap.inc.php';
require '../../../addons/'.MODULE_NAME.'/core/common/defines.php';
require PATH_MODULE . "/vendor/autoload.php";
require PATH_MODULE . "core/common/autoload.php";
Func_loader::core('global');
global $_W, $_GPC;
load()->model('attachment');
$_W['siteroot'] = str_replace(array('/addons/'.MODULE_NAME.'/payment','/addons/weliam_smartcity/payment'), '', $_W['siteroot']);
//获取打印数据
Util::wl_log('printing_request', PATH_MODULE."log/",$_REQUEST,'请求打印 —— 接收信息'); //写入日志记录
$tid = $_REQUEST['tid'];
$data =  Printing::getPrintingData($tid);
$jsonInfo =  json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
Util::wl_log('printing_request', PATH_MODULE."log/",$jsonInfo,'请求打印 —— 输出打印信息'); //写入日志记录
header('Content-type: rjson');
echo $jsonInfo;

