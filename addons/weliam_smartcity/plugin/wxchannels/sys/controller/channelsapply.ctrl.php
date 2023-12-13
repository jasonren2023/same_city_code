<?php
defined('IN_IA') or exit('Access Denied');
set_time_limit(0);

class Channelsapply_WeliamController
{

    /**
     * Comment: 视频号接入设置
     * Author: wlf
     * Date: 2022/05/26 16:00
     */
    public function applyset()
    {
        global $_W, $_GPC;
        $wxAppSet = Setting::wlsetting_read('wxapp_config');
        if(empty($wxAppSet['appid']) || empty($wxAppSet['secret'])){
            wl_message("请先配置小程序相关参数", web_url('wxapp/wxappset/wxapp_info_edit'), 'error');
        }
        //查询接入状态
        $AccessInfo = Wxchannels::getAccessInfo();
        if(empty($AccessInfo['errcode'])){
            $accessinfo = '申请成功,组件已开通';
        }else{
            $accessinfo = $AccessInfo['errmsg'];
            $pos1 = stripos($accessinfo, 'rid');
            if($pos1){
                $accessinfo = substr($accessinfo,0,$pos1);
            }
        }
        //查询商品接入状态
        $goodsAccessInfo = Wxchannels::getfinishInfo(6);
        if(empty($goodsAccessInfo['errcode'])){
            $goodsAccessinfo = '商品接入完成';
        }else{
            $goodsAccessinfo = $goodsAccessinfo['errmsg'];
            $pos1 = stripos($goodsAccessinfo, 'rid');
            if($pos1){
                $goodsAccessinfo = substr($goodsAccessinfo,0,$pos1);
            }
        }
        //查询订单接入状态
        $orderAccessInfo = Wxchannels::getfinishInfo(19);
        if(empty($orderAccessInfo['errcode'])){
            $orderAccessInfo = '订单接口接入完成';
        }else{
            $orderAccessInfo = $orderAccessInfo['errmsg'];
            $pos1 = stripos($orderAccessInfo, 'rid');
            if($pos1){
                $orderAccessInfo = substr($orderAccessInfo,0,$pos1);
            }
        }
        //查询物流接入状态
        $deliveryAccessInfo =  Wxchannels::getfinishInfo(8);
        if(empty($deliveryAccessInfo['errcode'])){
            $deliveryAccessInfo = '物流接口接入完成';
        }else{
            $deliveryAccessInfo = $deliveryAccessInfo['errmsg'];
            $pos1 = stripos($deliveryAccessInfo, 'rid');
            if($pos1){
                $deliveryAccessInfo = substr($deliveryAccessInfo,0,$pos1);
            }
        }
        //查询售后接入状态
        $afterAccessInfo =  Wxchannels::getfinishInfo(20);
        if(empty($afterAccessInfo['errcode'])){
            $afterAccessInfo = '售后接口接入完成';
        }else{
            $afterAccessInfo = $afterAccessInfo['errmsg'];
            $pos1 = stripos($afterAccessInfo, 'rid');
            if($pos1){
                $afterAccessInfo = substr($afterAccessInfo,0,$pos1);
            }
        }
        //测试完成
        $textAccessInfo =  Wxchannels::getfinishInfo(10);
        if(empty($textAccessInfo['errcode'])){
            $textAccessInfo = '测试完成';
        }else{
            $textAccessInfo = $textAccessInfo['errmsg'];
            $pos1 = stripos($textAccessInfo, 'rid');
            if($pos1){
                $textAccessInfo = substr($textAccessInfo,0,$pos1);
            }
        }
        //小程序发布
        $wxappAccessInfo =  Wxchannels::getfinishInfo(11);
        if(empty($wxappAccessInfo['errcode'])){
            $wxappAccessInfo = '发布完成';
        }else{
            $wxappAccessInfo = $wxappAccessInfo['errmsg'];
            $pos1 = stripos($wxappAccessInfo, 'rid');
            if($pos1){
                $wxappAccessInfo = substr($wxappAccessInfo,0,$pos1);
            }
        }


        include wl_template('wxchannels/applyset');
    }

    /**
     * Comment: 视频号接入申请
     * Author: wlf
     * Date: 2022/05/27 10:12
     */
    public function applyAccess(){
        global $_W, $_GPC;
        //申请接入
        $AccessInfo = Wxchannels::applyAccess();
        if(empty($AccessInfo['errcode'])){
            show_json(1,'申请成功');
        }else{
            show_json(0,$AccessInfo['errmsg']);
        }
    }

    /**
     * Comment: 更新AccessToken
     * Author: wlf
     * Date: 2022/06/10 10:12
     */
    public function updateAccess(){
        global $_W, $_GPC;
        WeliamWeChat::getAccessToken(1,3);
        show_json(1,'更新成功');
    }

    /**
     * Comment: 获取支付订单二维码
     * Author: wlf
     * Date: 2022/06/21 14:32
     */
    public function getOrderQr(){
        global $_W, $_GPC;
        $path = 'pages/subPages2/testorder/testorder';
        #1、二维码生成
        $imageUrl = tomedia(WeApp::getQrCode($path,'system_url'.md5($path).'.png'));

        include wl_template('wxchannels/OrderQr');
    }

    /**
     * Comment: 提交售后申请
     * Author: wlf
     * Date: 2022/06/21 14:32
     */
    public function afterModal(){
        global $_W, $_GPC;

        include wl_template('wxchannels/afterModal');
    }

    /**
     * Comment: 提交售后申请api
     * Author: wlf
     * Date: 2022/06/21 15:42
     */
    public function addafterapi(){
        global $_W, $_GPC;
        $id = trim($_GPC['wxorderno']);
        $openid = trim($_GPC['wxappopenid']);
        //获取订单数据
        $orderdata = [
            'order_id' => $id,
            'openid' => $openid
        ];
        $channelsinfo = Wxchannels::getOrderInfo($orderdata);
        if($channelsinfo['errcode'] > 0){
            $this->renderError($channelsinfo['errmsg']);
        }else{
            $order = $channelsinfo['order'];
        }
        $data = [
            'order_id' => $id,
            'openid' => $openid,
            'out_aftersale_id' => Util::createSalt(12),
            'type' => 1,
            'product_info' => [
                'out_product_id' => $order['order_detail']['product_infos'][0]['out_product_id'],
                'out_sku_id' => $order['order_detail']['product_infos'][0]['out_sku_id'],
                'product_cnt' => 1
            ],
            'orderamt' => 10,
            'refund_reason' => '测试退款',
            'refund_reason_type' => 1
        ];
        $orderAccessInfo = Wxchannels::addEcaftersale($data);
        if($orderAccessInfo['errcode'] != 0){
            show_json(0,$orderAccessInfo['errmsg']);
        }else{
            show_json(1,'申请成功');
        }
    }

    /**
     * Comment: 更新商户modal
     * Author: wlf
     * Date: 2022/06/21 16:17
     */
    public function StoreModal(){
        global $_W, $_GPC;
        $AreaTab = tablename(PDO_NAME . "area");
        $orderBy = " ORDER BY id ASC ";






        $storeInfo = Wxchannels::getStoreInfo();
        if($storeInfo['errcode'] != 0){
            show_json(0,$storeInfo['errmsg']);
        }else{
            $storeInfo = $storeInfo['data'];
        }

        $province = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 1 AND (displayorder = 0 OR displayorder = {$_W['uniacid']}) " . $orderBy);
        if(!empty($storeInfo['default_receiving_address']['province'])){
            $city_id = pdo_getcolumn(PDO_NAME.'area',array('name'=> $storeInfo['default_receiving_address']['city'] ),'id');
            $district = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 3 AND pid = {$city_id}  AND (displayorder = 0 OR displayorder = {$_W['uniacid']}) " . $orderBy);

            $province_id = pdo_getcolumn(PDO_NAME.'area',array('name'=> $storeInfo['default_receiving_address']['province'] ),'id');
            $city = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 2 AND pid = {$province_id} AND (displayorder = 0 OR displayorder = {$_W['uniacid']}) " . $orderBy);

        }else{
            $city = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 2 AND pid = {$province[0]['id']} AND (displayorder = 0 OR displayorder = {$_W['uniacid']}) " . $orderBy);
            $district = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 3 AND pid = {$city[0]['id']} AND (displayorder = 0 OR displayorder = {$_W['uniacid']}) " . $orderBy);
        }




        include wl_template('wxchannels/StoreModal');
    }

    /**
     * Comment: 更新商户信息api
     * Author: wlf
     * Date: 2022/06/21 17:55
     */
    public function addstoreapi(){
        global $_W, $_GPC;

        $service_agent_phone = trim($_GPC['service_agent_phone']);
        $receiver_name = trim($_GPC['receiver_name']);
        $detailed_address = trim($_GPC['detailed_address']);
        $tel_number = trim($_GPC['tel_number']);

        $districts = $_GPC['districts'];

        $province = pdo_getcolumn(PDO_NAME.'area',array('id'=>$districts['province']),'name');
        $city = pdo_getcolumn(PDO_NAME.'area',array('id'=>$districts['city']),'name');
        $town = pdo_getcolumn(PDO_NAME.'area',array('id'=>$districts['district']),'name');

        $data = [
            'service_agent_phone' => $service_agent_phone,
            'service_agent_type' => [0,2],
            'default_receiving_address' => [
                'receiver_name' => $receiver_name,
                'detailed_address' => $detailed_address,
                'tel_number' => $tel_number,
                'country' => '中国',
                'province' => $province,
                'city' => $city,
                'town' => $town,
            ]
        ];
        $storeInfo = Wxchannels::updateStoreInfo($data);
        if($storeInfo['errcode'] != 0){
            show_json(0,$storeInfo['errmsg']);
        }else{
            show_json(1,'修改成功');
        }

    }



}