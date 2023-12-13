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


file_put_contents(PATH_DATA . "sljnotify.log", var_export($_GPC, true) . PHP_EOL, FILE_APPEND);

//结果处理
$status = $_GPC['status'];
$finishtime = strtotime($_GPC['createdAt']);
$order = pdo_get('wlmerchant_mrecharge_order',array('orderno' => $_GPC['orderId']));
$_W['uniacid'] = $order['uniacid'];
if($status == 'SUCCESS'){
    pdo_update('wlmerchant_mrecharge_order',array('status' => 2,'finishtime' => $finishtime),array('orderno' => $_GPC['orderId']));

    $first = '您的话费充值已到账';
    $type = '话费充值';
    $content = '面额:['.$order['money'].']元';
    $newStatus = '充值成功';
    $remark = '充值手机号：'.$order['mobile'].',感谢您的使用';
    News::jobNotice($order['mid'],$first,$type,$content,$newStatus,$remark,time());
}else if($status == 'FAIL'){
    pdo_update('wlmerchant_mrecharge_order',array('status' => 3,'reason' => $_GPC['reason'],'finishtime' => time()),array('orderno' => $_GPC['orderId']));
    //退款
    Mobilerecharge::refund($_GPC['orderId'],$_GPC['reason']);
    //发消息
    $first = '您的话费充值失败';
    $type = '话费充值';
    $content = '面额:['.$order['money'].']元';
    $newStatus = '充值失败';
    $remark = '失败原因：'.$_GPC['reason'].',我们对此深感抱歉';
    News::jobNotice($order['mid'],$first,$type,$content,$newStatus,$remark,time());
}

exit('SUCCESS');