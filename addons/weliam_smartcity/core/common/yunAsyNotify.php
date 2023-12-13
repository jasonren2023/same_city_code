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
$yunorderinfo = json_decode(html_entity_decode($_GPC['order']),true);
Util::wl_log('yun_notify', PATH_DATA . "merchant/data/", $yunorderinfo); //写入异步日志记录
$transaction_id = $yunorderinfo['itpOrderId'];
$paylog = pdo_get('wlmerchant_paylogvfour' , ['transaction_id' => $transaction_id] , ['status','tid','uniacid','plugin' ,'plid','fee','payfor']);
$_W['uniacid'] = $paylog['uniacid'];
$tid = $paylog['tid'];
if($paylog['status'] == 0){
    $successInfo = [
        'type'           => 2 ,//支付方式
        'tid'            => $paylog['tid'],//订单号
        'transaction_id' => $transaction_id,
        'time'           => $yunorderinfo['paidTime'],
    ];
    PayResult::main($successInfo);//调用方法处理订单
    exit('SUCCESS');
}else{
    exit('SUCCESS');
}
