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
require '../../../../framework/model/attachment.mod.php';
global $_W,$_GPC;
$transaction_id = $_GPC['out_trade_no'];
$paylog = pdo_get('wlmerchant_paylogvfour' , ['transaction_id' => $transaction_id] , ['status','tid','uniacid','plugin' ,'plid','fee','payfor']);
$_W['uniacid'] = $paylog['uniacid'];
$tid = $paylog['tid'];
//订单信息查询
$type           = strtolower($paylog['plugin']);
$payfor         = strtolower($paylog['payfor']);
$data           = [];
$data['plugin'] = $type;
if ($type == 'rush') {
    $order         = pdo_get('wlmerchant_rush_order' , ['orderno' => $tid] , ['id' ,'aid', 'num' , 'activityid' , 'actualprice']);
    $data['price'] = $order['actualprice'];
    $data['goodsname'] = pdo_getcolumn(PDO_NAME.'rush_activity',array('id'=>$order['activityid']),'name');
    $detail_url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['orderid'=>$order['id'],'plugin'=>'rush']);
}
else if ($type == 'merchant' && $payfor == 'halfcard') {
    $order         = pdo_get('wlmerchant_halfcard_record' , ['orderno' => $tid] , ['id','aid', 'num'  , 'price']);
    $data['price'] = $order['price'];
    $data['goodsname'] = '会员开通/续费';
}
else if ($type == 'attestation') {
    $order         = pdo_get('wlmerchant_attestation_money' , ['orderno' => $tid] , ['id' , 'num' , 'money' , 'type']);
    $data['price'] = $order['money'];
    $data['type']  = $order['type'];
    $data['goodsname'] = '认证保证金缴纳';
}else {
    $order         = pdo_get('wlmerchant_order' , ['orderno' => $tid] , ['id','aid' , 'recordid' , 'num' , 'fkid' , 'plugin' , 'fightstatus' , 'paidprid' , 'price' , 'vip_card_id' , 'expressid']);
    $data['price'] = $order['price'];
    if ($order['plugin'] == 'wlfightgroup') {
        $data['goodsname'] = pdo_getcolumn(PDO_NAME.'fightgroup_goods',array('id'=>$order['fkid']),'name');
        $detail_url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['orderid'=>$order['id'],'plugin'=>'wlfightgroup']);
    }else if ($order['plugin'] == 'coupon') {
        $data['goodsname'] = pdo_getcolumn(PDO_NAME.'couponlist',array('id'=>$order['fkid']),'title');
        $detail_url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['orderid'=>$order['id'],'plugin'=>'coupon']);
    }else if($data['plugin'] == 'citydelivery'){
        $data['price'] = $paylog['fee'];
        $order = pdo_get('wlmerchant_order' , ['paylogid' => $paylog['plid']] , ['id' , 'recordid' , 'num' , 'fkid' , 'plugin' , 'fightstatus' ,  'paidprid' , 'price' , 'vip_card_id' , 'expressid']);
        $detail_url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['orderid'=>$order['id'],'plugin'=>'citydelivery']);
        $data['goodsname'] = '同城配送商品';
    }else if ($order['plugin'] == 'groupon') {
        $data['goodsname'] = pdo_getcolumn(PDO_NAME.'groupon_activity',array('id'=>$order['fkid']),'name');
        $detail_url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['orderid'=>$order['id'],'plugin'=>'groupon']);
    }else if ($order['plugin'] == 'bargain') {
        $data['goodsname'] = pdo_getcolumn(PDO_NAME.'bargain_activity',array('id'=>$order['fkid']),'name');
        $detail_url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['orderid'=>$order['id'],'plugin'=>'bargain']);
    }else if ($order['plugin'] == 'pocket') {
        $data['goodsname'] = '掌上信息付费';
    }else if ($order['plugin'] == 'store') {
        $data['goodsname'] = '商户入驻';
    }else if ($order['plugin'] == 'distribution') {
        $data['goodsname'] = '分销商申请';
    }else if ($order['plugin'] == 'consumption') {
        $data['goodsname'] = pdo_getcolumn(PDO_NAME.'consumption_goods',array('id'=>$order['fkid']),'title');
    }else if ($order['plugin'] == 'member') {
        $data['goodsname'] = '余额充值';
    }else if ($order['plugin'] == 'halfcard') {
        $data['goodsname'] = '在线买单';
    }else if ($order['plugin'] == 'halfcard') {
        $data['goodsname'] = '在线买单';
    }else if ($order['plugin'] == 'citycard') {
        $data['goodsname'] = '同城名片付费';
    }else if ($order['plugin'] == 'yellowpage') {
        $data['goodsname'] = '黄页114付费';
    }else if ($order['plugin'] == 'recruit') {
        $data['goodsname'] = '招聘求职付费';
    }else if ($order['plugin'] == 'dating') {
        $data['goodsname'] = '相亲交友付费';
    }else if ($order['plugin'] == 'vehicle') {
        $data['goodsname'] = '顺风车付费';
    }else if ($order['plugin'] == 'housekeep') {
        $data['goodsname'] = '家政服务付费';
    }
}
$data['num'] = $order['num'] ? : 1;
if(empty($data['goodsname'])){
    $data['goodsname'] = '其他付费项目';
}
$_W['aid'] = $order['aid'] ? : 0;
//系统信息查询
$_W['attachurl_remote'] = attachment_set_attach_url();
$base = Setting::wlsetting_read('base');
$base['logo'] = tomedia($base['logo']);
$home_url = h5_url('pages/mainPages/index/index');
if(empty($detail_url)){
    $detail_url = h5_url('pages/subPages/orderList/orderList',['type'=>10]);
}
//处理订单回调
if($paylog['status'] == 0){
    $successInfo = [
        'type'           => 2 ,//支付方式
        'tid'            => $paylog['tid'],//订单号
        'transaction_id' => $transaction_id,
        'time'           => $_GPC['t'],
    ];
    PayResult::main($successInfo);//调用方法处理订单
}


include wl_template('utility/ahrcu');