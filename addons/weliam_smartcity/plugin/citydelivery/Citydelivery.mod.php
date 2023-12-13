<?php
defined('IN_IA') or exit('Access Denied');

class Citydelivery {

    /**
     * 获取商品进入购物车的信息
     * @param number $goodid 商品id
     * @param number $specid 规格id
     * @param number $halfflag 一卡通会员id
     * @return array
     */
    static function getGoodprice($goodid,$specid=0,$halfflag=0) {
        $goodinfo = pdo_get('wlmerchant_delivery_activity',array('id' => $goodid),array('name','allstock','daystock','optionstatus','aid','sid','status','cateid','deliveryprice','thumb','vipstatus','vipdiscount','price','oldprice'));
        //判断多规格
        if(!empty($specid)){
            $spec = pdo_get('wlmerchant_delivery_spec',array('id' => $specid),array('name','allstock','daystock','price','oldprice'));
            $goodinfo['specname'] = $spec['name'];
            $goodinfo['price'] = $spec['price'];
            $goodinfo['oldprice'] = $spec['oldprice'];
            $goodinfo['allstock'] = $spec['allstock'];
            $goodinfo['daystock'] = $spec['daystock'];
        }else{
            $goodinfo['specname'] = '';
        }
        $goodinfo['originalPrice'] = $goodinfo['price'];
        //判断会员折扣
        if($halfflag && $goodinfo['vipstatus'] == 1){
            $goodinfo['price'] = sprintf("%.2f",$goodinfo['price'] - $goodinfo['vipdiscount']);
            $goodinfo['price'] > 0 ? $goodinfo['price'] : 0;
        }else{
            $goodinfo['vipdiscount'] = 0;
        }
        $goodinfo['price'] = sprintf("%.2f",$goodinfo['price']);
        return $goodinfo;
    }

    /**
     * 获取用户购物车信息
     * @param number $mid 用户id
     * @param number $sid 商户id
     * @return array
     */
    static function getCartInfo($mid,$sid=0){
        global $_W;
        $where = "uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND mid = {$mid}";
        if($sid){
            $where .= " AND sid = {$sid}";
        }
        $cartgoods = pdo_fetchall("SELECT goodid,num,specid,id FROM ".tablename('wlmerchant_delivery_shopcart')."WHERE {$where} ORDER BY createtime DESC");
        if(!empty($cartgoods)){
            $halfflag = WeliamWeChat::VipVerification($mid,true);
            $totalnum = $totalmoney = $deliveryprice = 0;
            foreach ($cartgoods as &$goods){
                $goodinfo = Citydelivery::getGoodprice($goods['goodid'],$goods['specid'],$halfflag);
                $goods['name'] = $goodinfo['name'];
                $goods['specname'] = $goodinfo['specname'];
                $goods['price'] = $goodinfo['price'];
                $goods['oldprice'] = $goodinfo['oldprice'];
                $goods['thumb'] = tomedia($goodinfo['thumb']);
                $totalmoney += sprintf("%.2f",$goods['price']*$goods['num']);
                $deliveryprice += sprintf("%.2f",$goodinfo['deliveryprice']*$goods['num']);
                $totalnum += $goods['num'];
            }
            $cartinfo = ['totalnum'=>$totalnum,'totalmoney'=>$totalmoney,'deliveryprice'=>$deliveryprice,'goodslist'=>$cartgoods];
        }else{
            $cartinfo = ['totalnum'=>0,'totalmoney'=>0,'deliveryprice'=>0,'goodslist'=>[]];
        }
        return $cartinfo;
    }

    /**
     * 获取订单结算金额
     * @param decimal $money 订单金额
     * @param number $sid 商户id
     * @return decimal
     */
    static function getsettlementmoney($money,$sid,$deliveryallmoney){
        $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $sid), array('groupid','deliveryrate','deliverymoney'));
        if($merchant['deliveryrate']>0){
            $rate = $merchant['deliveryrate'];
        }else{
            $rate = pdo_getcolumn(PDO_NAME . 'chargelist', array('id' => $merchant['groupid']), 'defaultrate');
        }
        $settlementmoney = sprintf("%.2f", $money * $rate / 100 + $deliveryallmoney);

        return $settlementmoney > 0 ? $settlementmoney : 0;
    }

    /**
     * 支付成功回调
     * @param array $params 回调信息
     * @return decimal
     */
    static function payDeliveryOrderNotify($params){
        global $_W;
        Util::wl_log('delivery_notify', PATH_DATA . "delivery/data/", $params); //写入异步日志记录
        //回调信息
        $data = array('status' => $params['result'] == 'success' ? 1 : 0);
        $data['paytype'] = $params['type'];
        if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
        $data['paytime'] = TIMESTAMP;
        //订单信息
        $order_out = pdo_fetch("select id,uniacid from" . tablename(PDO_NAME . 'order') . "where orderno='{$params['tid']}'");
        if(empty($order_out)){
            $paylogid = pdo_getcolumn(PDO_NAME.'paylogvfour',array('tid'=>$params['tid']),'plid');
            if($paylogid>0){
                $orders = pdo_getall('wlmerchant_order',array('paylogid' => $paylogid),array('id','uniacid'));
                $num = count($orders);
                $data['blendcredit'] = sprintf("%.2f",$params['blendcredit'] / $num);
                foreach ($orders as $or){
                    self::updeteOrder($data,$or['id']);
                }
            }
        }else{
            $data['blendcredit'] = $params['blendcredit'];
            self::updeteOrder($data,$order_out['id']);
        }
    }

    /**
     * 处理支付的订单
     * @param decimal $money 订单金额
     * @param number $sid 商户id
     * @return decimal
     */
    static function updeteOrder($data,$orderid){
        global $_W;
        $order = pdo_get('wlmerchant_order',array('id' => $orderid),array('goodsprice','aid','vipdiscount','expressid','makeorderno','uniacid','paytime','fightstatus','price','expressprcie','uuaexpressprice','status','sid','orderno','id','mid'));
        $_W['uniacid'] = $order['uniacid'];
        $_W['aid'] = $order['aid'];
        $_W['wlsetting']['creditset'] = Setting::wlsetting_read('creditset');
        $disarray = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('expresspricestatus','mobile','acceptstatus','third_shop_no','third_city_code','deliverypaidid','makebiguser','deliverydisstatus','onescale','twoscale'));
        if($order['status'] == 0 || $order['status'] == 5){
            $disprice = sprintf("%.2f",$order['goodsprice'] - $order['vipdiscount']);
            if($order['fightstatus'] > 0){
                if($disarray['acceptstatus'] > 0){
                    $data['status'] = 8;
                }else{
                    $data['status'] = 4;
                }
            }
            if($order['cerditmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                $nodis = 1;
            }else{
                $nodis = 0;
            }
            if(p('distribution') && empty($nodis)){
                $disorderid = Distribution::newDisCore($order['id'],'citydelivery');
                $data['disorderid'] = $disorderid;
            }
            //推送骑手端订单
            if(empty($disarray['acceptstatus'])){
                $makeorderno = self::acceptOrder($order,$disarray);
                if(!empty($makeorderno)){
                    $data['makeorderno'] = $makeorderno;
                }
            }
            //支付有礼
            if($disarray['deliverypaidid'] > 0){
                $data['paidprid'] = Paidpromotion::getpaidpr(8,$disarray['deliverypaidid'],$order['mid'],$order['id'],$data['paytype'],$order['price']);
            }
            //业务员
            if(uniacid_p('salesman')){
                $data['salesarray'] = Salesman::saleCore($order['sid'],'citydelivery');
            }
            pdo_update('wlmerchant_order',$data,array('id' => $orderid));
            pdo_update('wlmerchant_delivery_order',array('status'=>1),array('tid' => $order['orderno']));

            //云喇叭的小票打印机
            #7、返回成功支付信息
            VoiceAnnouncements::PushVoiceMessage($order['price'],$order['sid'],2); //调用云喇叭进行商户收款播报
            #8、调用打印推送信息
            Order::sendPrinting($order['id'],'citydelivery');

            //支付成功通知
            Store::addFans($order['sid'], $order['mid']);
            News::paySuccess($order['id'], 'citydelivery');
        }
    }

    //接单发送订单给配送端
    static function acceptOrder($order,$disarray){
        global $_W;
        //码科跑腿
        if($order['fightstatus'] == 2){
            $smallorders = pdo_fetchall("SELECT gid,num,specid FROM ".tablename('wlmerchant_delivery_order')."WHERE orderid = {$order['id']} ORDER BY price DESC");
            $goodsname = '';
            foreach ($smallorders  as $ke => $orr){
                $good = pdo_get('wlmerchant_delivery_activity',array('id' => $orr['gid']),array('name','thumb'));
                if($ke>0){
                    if($orr['specid']>0){
                        $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                        $goodsname .=  ' + ['.$good['name'].'/'.$specname.'] X'.$orr['num'];
                    }else{
                        $goodsname .=  ' + ['.$good['name'].'] X'.$orr['num'];
                    }
                }else{
                    if($orr['specid']>0){
                        $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                        $goodsname .=  '['.$good['name'].'/'.$specname.'] X'.$orr['num'];
                    }else{
                        $goodsname .= '['.$good['name'].'] X'.$orr['num'];
                    }
                }
            }
            $picktime = date('Y-m-d H:i:s',time());
            $makebiguser = $disarray['makebiguser'];
            $big = $makebiguser > 0 ? $order['sid'] : 0;
            $makeorderno = self::addMakeOrder($order['id'],$goodsname,$picktime,$order['buyremark'],$order['sid'],$big,$order['expressid'],$order['expressprcie']);
        }else if($order['fightstatus'] == 3){  //达达
            $body = ['deliveryNo' => $order['makeorderno']];
            $dadare = Citydelivery::postDadaApi($body,2);
            if(empty($dadare)){  //错误 直接发布新的
                $smallorders = pdo_fetchall("SELECT gid,num,specid FROM ".tablename('wlmerchant_delivery_order')."WHERE orderid = {$order['id']} ORDER BY price DESC");
                $product_list = [];
                foreach ($smallorders  as $ke => $orr){
                    $good = pdo_get('wlmerchant_delivery_activity',array('id' => $orr['gid']),array('name','thumb'));
                    if($orr['specid']>0){
                        $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                        $name = $good['name'].'['.$specname.']';
                    }else{
                        $name = $good['name'];
                    }
                    $stginfo = [
                        'sku_name' => $name,
                        'src_product_no' => '0',
                        'count' => $orr['num'],
                    ];
                    $product_list[] = $stginfo;
                }
                $address = pdo_get('wlmerchant_address',array('id' => $order['expressid']),array('name','detailed_address','lat','lng','tel'));
                $callback = $_W['siteroot']."addons/weliam_smartcity/core/common/uniapp.php?i=".$_W['uniacid']."&p=citydelivery&do=finishOrder&id=".$order['id']."&type=5";
                $body = [
                    'shop_no' => $disarray['third_shop_no'],
                    'origin_id' => $order['orderno'],
                    'city_code' => $disarray['third_city_code'],
                    'cargo_price' => $order['price'],
                    'is_prepay' => 0,
                    'receiver_name' => $address['name'],
                    'receiver_address' => $address['detailed_address'],
                    'receiver_lat' => $address['lat'],
                    'receiver_lng' => $address['lng'],
                    'receiver_phone' => $address['tel'],
                    'cargo_weight' => 1,
                    'callback' => $callback
                ];
                $dadaInfo = Citydelivery::postDadaApi($body,4);
            }
        }else if($order['fightstatus'] == 4){ //UU
            $address = pdo_get('wlmerchant_address',array('id' => $order['expressid']),array('name','detailed_address','lat','lng','tel'));
            $callback = $_W['siteroot']."addons/weliam_smartcity/core/common/uniapp.php?i=".$_W['uniacid']."&p=citydelivery&do=finishOrder&id=".$order['id']."&type=6";
            $apiset = Setting::wlsetting_read('api');
            $deliveset = Setting::agentsetting_read('citydelivery');
	        if($deliveset['type'] > 0){
	        	$apiset = $deliveset['UUpt'];
	        }else{
	        	$apiset = $apiset['uu'];
	        }
            $body = [
                'price_token'      => $order['makeorderno'],
                'order_price'      => $order['expressprcie'],
                'balance_paymoney' => $order['uuaexpressprice'],
                'receiver'         => $address['name'],
                'receiver_phone'   => $address['tel'],
                'pubusermobile'    => $disarray['mobile'],
                'callback_url'     => $callback,
                'push_type'        => 0,
                'special_type'     => 0,
                'callme_withtake'  => 0,
                'openid'           => $apiset['openid']
            ];
            file_put_contents(PATH_DATA . "UU_error.log", var_export('我是提交信息',true) . PHP_EOL, FILE_APPEND);
            file_put_contents(PATH_DATA . "UU_error.log", var_export($body,true) . PHP_EOL, FILE_APPEND);
            $UUInfo = Citydelivery::postUUApi($body,2);
            $makeorderno = $UUInfo['ordercode'];
        }
        if(!empty($makeorderno)){
            return $makeorderno;
        }else{
            return 0;
        }
    }


    //退款函数
    static function refund($id, $money = '', $unline = '',$checkcode = '',$afterid = 0) {
        global $_W;
        $item = pdo_get('wlmerchant_order',array('id' => $id));
        if(empty($money)){
            $money = $item['price'];
            if($item['blendcredit']){
                $money = sprintf("%.2f",$item['price'] - $item['blendcredit']);
            }
        }else if($money < $item['blendcredit']){
            $blendcredit = $money;
            $money = 0;
        }else if($item['blendcredit'] > 0){
            $blendcredit = $item['blendcredit'];
            $money = sprintf("%.2f",$money - $blendcredit);
        }
        if ($unline) {
            $res['status'] = 1;
        } else {
            $res = wlPay::refundMoney($id, $money, '同城配送订单退款', 'citydelivery', 2,$blendcredit);
        }
        if ($res['status']) {
            if($item['fightstatus'] == 2){
                Citydelivery::cancelOrder($item['makeorderno']);
            }else if($item['fightstatus'] == 3){
                $body = ['order_id' => $item['orderno'],'cancel_reason_id' => 36];
                Citydelivery::postDadaApi($body,5);
            }else if($item['fightstatus'] == 4){
                $body = ['origin_id' => $item['orderno'],'reason' => '平台系统退款'];
                Citydelivery::postUUApi($body,5);
            }
            if ($item['applyrefund']) {
                pdo_update('wlmerchant_order', array('status' => 7, 'refundtime' => time(), 'applyrefund' => 2), array('id' => $item['id']));
                $reason = '买家申请退款。';
            } else {
                pdo_update('wlmerchant_order', array('status' => 7, 'refundtime' => time()), array('id' => $item['id']));
                $reason = '系统退款。';
            }
            $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['orderid'=>$id,'plugin'=>'citydelivery']);
            if ($item['disorderid']) {
                Distribution::refunddis($item['disorderid']);
            }
            News::refundNotice($id,'citydelivery',$money,$reason);
            if ($item['dkcredit']) {
                $refundcredit = sprintf("%.2f",$item['dkcredit']);
                Member::credit_update_credit1($item['mid'], $refundcredit, '退款同城配送订单:[' . $item['orderno'] . ']返还积分');
            }
            if($item['redpackid'] > 0){
                pdo_update('wlmerchant_redpack_records',array('status' => 0,'usetime' =>0,'orderid' => 0,'plugin' =>''),array('id' => $item['redpackid']));
            }
        } else {
            pdo_fetch("update" . tablename('wlmerchant_rush_order') . "SET failtimes = failtimes+1 WHERE id = {$id}");
        }
        return $res;
    }

    /**
     * 获取码科外卖的令牌
     */
    static function getMakeToken(){
        global $_W;
        $deliveset = Setting::agentsetting_read('citydelivery');
		$apiset = Setting::wlsetting_read('api');
        
        $appid = $deliveset['type'] > 0 ? $deliveset['make']['appid'] : $apiset['make']['appid'];
        $token = $deliveset['type'] > 0 ? $deliveset['make']['token'] : $apiset['make']['token'];
        $getUrl = $deliveset['type'] > 0 ? $deliveset['make']['domain'] : $apiset['make']['domain'];
        $getUrl = $getUrl.'addons/make_speed/core/public/index.php/apis/v2/get_token';
        $data = ['token' => $token,'appid' => $appid ];
        $res = curlPostRequest($getUrl,$data);
        if(!empty($res['error_code'])){
            Util::wl_log('MakeApi.log',PATH_DATA,$res); //写入异步日志记录
        }else{
            return $res['token'];
        }
    }

    /**
     * 获取订单的配送价格
     */
    static function getMakePrice($sid,$addressid,$big=0){
        global $_W;
        $deliveset = Setting::agentsetting_read('citydelivery');
		$apiset = Setting::wlsetting_read('api');
		
        $token = self::getMakeToken();
        $store = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('lng','lat'));
        $address = pdo_get('wlmerchant_address',array('id' => $addressid),array('lng','lat'));
        $getUrl = $deliveset['type'] > 0 ? $deliveset['make']['domain'] : $apiset['make']['domain'];
        $getUrl = $getUrl.'addons/make_speed/core/public/index.php/apis/v2/get_delivery_price?token='.$token.'&fromcoord='.$store['lat'].','.$store['lng'].'&tocoord='.$address['lat'].','.$address['lng'].'&shop_id='.$big;
        $res = curlGetRequest($getUrl);
        if(!empty($res['error_code'])){
            Util::wl_log('MakeApi.log',PATH_DATA,$res); //写入异步日志记录
        }else{
            return $res['data'];
        }
    }

    /**
     * 取消订单
     */
    static function cancelOrder($orderno){
        global $_W;
        $deliveset = Setting::agentsetting_read('citydelivery');
		$apiset = Setting::wlsetting_read('api');
		
        $token = self::getMakeToken();
        $data['token'] = $token;
        $data['order_num'] = $orderno;
        $getUrl = $deliveset['type'] > 0 ? $deliveset['make']['domain'] : $apiset['make']['domain'];
        $getUrl = $getUrl."addons/make_speed/core/public/index.php/apis/v2/cancel_order";
        $res = curlPostRequest($getUrl,$data);
        if(!empty($res['error_code'])){
            Util::wl_log('MakeApi.log',PATH_DATA,$res); //写入异步日志记录
        }else{
            return $res['data'];
        }
    }

    /**
     * 添加码科订单
     */
    static function addMakeOrder($orderid,$goods_name,$pick_time,$remark,$sid,$big = 0,$addressid,$pay_price){
        global $_W;
        $deliveset = Setting::agentsetting_read('citydelivery');
		$apiset = Setting::wlsetting_read('api');
		$getUrl = $deliveset['type'] > 0 ? $deliveset['make']['domain'] : $apiset['make']['domain'];
        $token = self::getMakeToken();
        $data['token'] = $token;
        $data['goods_name'] = $goods_name;
        $data['pick_time'] = $pick_time;
        $data['remark'] = $remark;
        $data['pay_price'] = $pay_price;
        $data['total_price'] = $pay_price;
        $data['shop_id'] = $big;
        $data['notify_url'] = $_W['siteroot']."addons/weliam_smartcity/core/common/uniapp.php?i=".$_W['uniacid']."&p=citydelivery&do=finishOrder&id=".$orderid."&type=4";
        $store = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('lng','lat','address','storename','mobile'));
        $meminfo = pdo_get('wlmerchant_address',array('id' => $addressid),array('lng','lat','name','detailed_address','tel'));
        $address = [
            'begin_detail'   => '',
            'begin_address'  => $store['address'],
            'begin_lat'      => $store['lat'],
            'begin_lng'      => $store['lng'],
            'begin_username' => $store['storename'],
            'begin_phone'    => $store['mobile'],
            'end_detail'     => '',
            'end_address'    => $meminfo['detailed_address'],
            'end_lat'        => $meminfo['lat'],
            'end_lng'        => $meminfo['lng'],
            'end_username'   => $meminfo['name'],
            'end_phone'      => $meminfo['tel']
        ];
        $data['address'] = json_encode($address);
        $getUrl = $getUrl."addons/make_speed/core/public/index.php/apis/v2/create_order";
        $res = curlPostRequest($getUrl,$data);
        if(!empty($res['error_code'])){
            Util::wl_log('MakeApi.log',PATH_DATA,$res); //写入异步日志记录
        }else{
            return $res['data']['order_number'];
        }
    }

    /**
     * 获取配送订单详情
     */
    static function getMakeOrderDetail($orderno){
        global $_W;
        $deliveset = Setting::agentsetting_read('citydelivery');
		$apiset = Setting::wlsetting_read('api');
		$getUrl = $deliveset['type'] > 0 ? $deliveset['make']['domain'] : $apiset['make']['domain'];
        
        $token = self::getMakeToken();
        $data['token'] = $token;
        $data['order_num'] = $orderno;
        $getUrl = $getUrl."addons/make_speed/core/public/index.php/apis/v2/get_order_detail";
        $res = curlPostRequest($getUrl,$data);
        if(!empty($res['error_code'])){
            Util::wl_log('MakeApi.log',PATH_DATA,$res); //写入异步日志记录
        }else{
            return $res['data'];
        }
    }

    //后台核销
    static function hexiaoorder($id,$type){
        $res = pdo_update('wlmerchant_order',array('status' => 2,'deliverytype' => $type),array('id' => $id));
        if($res){
            $order = pdo_get('wlmerchant_order',array('id' => $id),array('orderno','expressid','disorderid'));
            pdo_update('wlmerchant_delivery_order',array('status' => 2,'dotime' => time()),array('tid' => $order['orderno']));
            $setres = Store::ordersettlement($id);
            if($order['expressid']){
                pdo_update('wlmerchant_express',array('receivetime' => time()),array('id' => $order['expressid']));
            }
            if($order['disorderid']){
                pdo_update('wlmerchant_disorder',array('status' => 1),array('id' => $order['disorderid'],'status' => 0));
            }
            if($type == 4){
                die('success');
            }
            return 1;
        }else{
            return 0;
        }
    }

    /**
     * 哒哒跑腿获取签名接口
     */
    static function getSignatureApi($data,$app_secret){
        global $_W;
        if(!is_array($data)){
            file_put_contents(PATH_DATA . "dada_error.log", var_export('获取签名失败：无请求信息或信息错误', true) . PHP_EOL, FILE_APPEND);
        }
        $signatureSt = '';
        foreach($data as $key => $dd){
            $signatureSt .= $key.$dd;
        }
        $signatureSt = $app_secret.$signatureSt.$app_secret;
        $signature = md5($signatureSt);
        $signature = strtoupper($signature);

        return $signature;
    }

    /**
     * 哒哒跑腿API接口
     */
    static function postDadaApi($body,$type){     //  1=订单预发布 2=发布已预发布订单 3=查询订单详情 4=新增订单 5=取消订单 8=查询城市列表
        global $_W;
        $apiset = Setting::wlsetting_read('api');
        $base = Setting::agentsetting_read('citydelivery');
        $body = json_encode($body);
        $data = [
            'app_key'   => $base['type'] > 0 ? $base['dada']['appKey']  : $apiset['dada']['appKey'],
            'body'      => $body,
            'format'    => 'json',
            'source_id' => $base['dada_source_id'],
            'timestamp' => time(),
            'v'         => '1.0',
        ];
        $app_secret = $base['type'] > 0 ? $base['dada']['appSecret'] : $apiset['dada']['appSecret'];
        $data['signature'] = self::getSignatureApi($data,$app_secret);
        $data['app_secret'] = $app_secret;

        $data = json_encode($data);
        if($type == 1){
            $getUrl = "newopen.imdada.cn/api/order/queryDeliverFee";
        } else if($type == 2){
            $getUrl = "newopen.imdada.cn/api/order/addAfterQuery";
        } else if($type == 3){
            $getUrl = "newopen.imdada.cn/api/order/status/query";
        } else if($type == 4){
            $getUrl = "newopen.imdada.cn/api/order/addOrder";
        } else if($type == 5){
            $getUrl = "newopen.imdada.cn/api/order/formalCancel";
        } else if($type == 8){
            $getUrl = "newopen.imdada.cn/api/cityCode/list";
        }
        $dadaInfo = curlPostRequest($getUrl,$data,["Content-type: application/json;charset='utf-8'"]);
        if($dadaInfo['status'] == 'success'){
            return $dadaInfo;
        }else{
            file_put_contents(PATH_DATA . "dada_error.log", var_export($dadaInfo,true) . PHP_EOL, FILE_APPEND);
            return $dadaInfo;
        }
    }


    /**
     * UU跑腿获取签名接口
     */
    static function getUUSignatureApi($data,$appKey){
        global $_W;
        if(!is_array($data)){
            file_put_contents(PATH_DATA . "UU_error.log", var_export('获取签名失败：无请求信息或信息错误', true) . PHP_EOL, FILE_APPEND);
        }
        ksort($data);
        $arr = [];
        foreach ($data as $key => $value) {
            $arr[] = $key.'='.$value;
        }

        $arr[] = 'key='.$appKey;
        $str = strtoupper(implode('&', $arr));
        return strtoupper(md5($str));
    }

    /**
     * UU跑腿生成随机字符串
     */
    function guid(){
        mt_srand((double)microtime()*10000); //optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12);

        $uuid = str_replace('-','',$uuid);
        $uuid = strtolower($uuid);
        return $uuid;
    }

    /**
     * UU跑腿API接口
     */
    static function postUUApi($data,$type){    //type = 1 计算订单价格 2 发布订单 3 取消订单 4订单详情 5订单取消 8 获取所在城市
        global $_W;
        $apiset = Setting::wlsetting_read('api');
        $deliveset = Setting::agentsetting_read('citydelivery');
        if($deliveset['type'] > 0){
        	$apiset = $deliveset['UUpt'];
        }else{
        	$apiset = $apiset['uu'];
        }

        if($type == 1){
            $getUrl = "http://openapi.uupt.com/v2_0/getorderprice.ashx";
        }else if($type == 2){
            $getUrl = "http://openapi.uupt.com/v2_0/addorder.ashx";
        }else if($type == 4){
            $getUrl = "http://openapi.uupt.com/v2_0/getorderdetail.ashx";
            $data['openid'] = $apiset['openid'];
        }else if($type == 5){
            $getUrl = "http://openapi.uupt.com/v2_0/cancelorder.ashx";
            $data['openid'] = $apiset['openid'];
        }else if($type == 8){
            $getUrl = "http://openapi.uupt.com/v2_0/getcitylist.ashx";
        }

        $data['nonce_str'] = self::guid();
        $data['timestamp'] = time();
        $data['appid'] = $apiset['appid'];
        $data['sign'] = self::getUUSignatureApi($data,$apiset['appkey']);
        if($data['callback_url']){
            $data['callback_url'] = urlencode($data['callback_url']);
        }
        $UUInfo = curlPostRequest($getUrl,$data);
        if($UUInfo['return_code'] == 'ok'){
            return $UUInfo;
        }else{
            file_put_contents(PATH_DATA . "UU_error.log", var_export($UUInfo,true) . PHP_EOL, FILE_APPEND);
            return $UUInfo;
        }
    }

    /**
     * 计算订单佣金
     */
    static public function getDisMoney($orderid,$levelid,$rank = 1){
        global $_W;
        $money = 0;
        $allsmallorder = pdo_getall('wlmerchant_delivery_order',array('orderid' => $orderid),array('price','gid','num'));
        foreach ($allsmallorder as $small){
            $addmoney = 0;
            $goodinfo = pdo_get('wlmerchant_delivery_activity',array('id' => $small['gid']),array('isdistri','disarray','isdistristatus'));
            if($goodinfo['isdistri'] > 0){
                $disarray = unserialize($goodinfo['disarray']);
                if($rank == 1){
                    if($goodinfo['isdistristatus'] > 0){
                        $addmoney = sprintf("%.2f",$disarray[$levelid]['onedismoney'] * $small['num']);
                    }else{
                        $addmoney = sprintf("%.2f",$disarray[$levelid]['onedismoney'] * $small['price'] / 100);
                    }
                }else{
                    if($goodinfo['isdistristatus'] > 0){
                        $addmoney = sprintf("%.2f",$disarray[$levelid]['twodismoney'] * $small['num']);
                    }else{
                        $addmoney = sprintf("%.2f",$disarray[$levelid]['twodismoney'] * $small['price'] / 100);
                    }
                }
            }
            $money += $addmoney;
        }
        return $money;
    }

    /**
     * 计算订单团长分红
     */
    static public function getGroupMoney($orderid,$levelid,$rank = 1){
        global $_W;
        $money = 0;
        $allsmallorder = pdo_getall('wlmerchant_delivery_order',array('orderid' => $orderid),array('price','gid','num'));
        foreach ($allsmallorder as $small){
            $addmoney = 0;
            $goodinfo = pdo_get('wlmerchant_delivery_activity',array('id' => $small['gid']),array('disgroup','disgroupstatus','grouparray'));
            if($goodinfo['disgroup'] > 0){
                $addmoney = Distribution::getGroupMoney($goodinfo['grouparray'],$levelid,$small['price'],$small['num'],$goodinfo['disgroupstatus'],$rank);
            }
            $money += $addmoney;
        }
        return $money;
    }

    /**
     * 计算订单股东分红
     */
    static public function getShareMoney($orderid){
        global $_W;
        $money = 0;
        $allsmallorder = pdo_getall('wlmerchant_delivery_order',array('orderid' => $orderid),array('price','gid','num'));
        foreach ($allsmallorder as $small){
            $goodinfo = pdo_get('wlmerchant_delivery_activity',array('id' => $small['gid']),array('shareholdermoney'));
            if($goodinfo['shareholdermoney'] > 0){
                $money += $goodinfo['shareholdermoney'];
            }
        }
        return $money;
    }

}




