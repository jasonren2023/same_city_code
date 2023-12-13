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


file_put_contents(PATH_DATA . "yqdnotify.log", var_export($_GPC, true) . PHP_EOL, FILE_APPEND);
//核销
$asyinfo = $_GPC['__input'];
$status = $asyinfo['status'];
if(empty($status)){
    exit('error:状态码错误');
}
$orderno = ltrim($asyinfo['externalOrderno'],'wl_');

$paylog = pdo_get('wlmerchant_paylogvfour',array('tid' => $orderno),array('plugin','uniacid'));
$_W['uniacid'] = $paylog['uniacid'];
if(empty($paylog)){
    exit('error:订单不存在');
}
if($status == 4){
    if($paylog['plugin'] == 'Groupon'){
        $orderinfo = pdo_get(PDO_NAME.'order',array('orderno'=>$orderno),['num','id','fkid','mid']);
        $goodsname = pdo_getcolumn(PDO_NAME.'groupon_activity',array('id'=>$orderinfo['fkid']),'name');
        Store::ordersettlement($orderinfo['id']);
    }else{
        $orderinfo = pdo_get(PDO_NAME.'rush_order',array('orderno'=>$orderno),['num','id','activityid','mid']);
        $goodsname = pdo_getcolumn(PDO_NAME.'rush_activity',array('id'=>$orderinfo['activityid']),'name');
        Store::rushsettlement($orderinfo['id']);
    }
    //通知到账
    $first = '您购买的商品['.$goodsname.']订单已完成';
    $type = '订单完成通知';
    $status = '已完成';
    $remark = $asyinfo['msg'];
    $content = '感谢您的使用，欢迎您再次使用';
    News::jobNotice($orderinfo['mid'],$first,$type,$content,$status,$remark,time());
}else if($status == 5){
    if($paylog['plugin'] == 'Groupon'){
        $orderinfo = pdo_get(PDO_NAME.'order',array('orderno'=>$orderno),['num','id','fkid','mid']);
        $goodsname = pdo_getcolumn(PDO_NAME.'groupon_activity',array('id'=>$orderinfo['fkid']),'name');
        pdo_update(PDO_NAME . 'order',['status' => 6], array('orderno' => $orderno)); //更新订单状态
    }else{
        $orderinfo = pdo_get(PDO_NAME.'rush_order',array('orderno'=>$orderno),['num','id','activityid','mid']);
        $goodsname = pdo_getcolumn(PDO_NAME.'rush_activity',array('id'=>$orderinfo['activityid']),'name');
        pdo_update(PDO_NAME . 'rush_order',['status' => 6], array('orderno' => $orderno)); //更新订单状态
    }
    $first = '很抱歉，您购买的商品['.$goodsname.']下单失败';
    $type = '下单失败通知';
    $status = '退款中';
    $remark = '失败原因:'.$asyinfo['msg'];
    $content = '很抱歉，请您下单重试';
    News::jobNotice($orderinfo['mid'],$first,$type,$content,$status,$remark,time());

}

exit('ok');