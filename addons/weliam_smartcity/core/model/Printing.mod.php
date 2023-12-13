<?php
/**
 * Comment: 定制小票打印接口
 * Author: zzw
 * Date: 2020/4/2
 * Time: 10:31
 */
defined('IN_IA') or exit('Access Denied');

class Printing{
    protected static $orderInfo,//订单信息
        $tid,//订单号
        $storeInfo,//商户信息
        $timeStamp,//当前时间  时间戳
        $userInfo;//用户信息
    /**
     * Comment: 打印推送消息接口
     * Author: zzw
     * Date: 2020/4/3 11:13
     * @param string|int $tid
     * @return array|bool|mixed
     */
    public static function init($tid){
        global $_W;
        #1、信息获取
        self::$tid = $tid;
        self::$timeStamp = time();
        self::getOrder();//订单信息获取
        if(!self::$orderInfo['sid']) {
            Util::wl_log('printing' , PATH_MODULE . "log/" , [
                'error'     => '商户id不存在' ,
                'tid'       => $tid
            ] , '打印推送消息 —— 错误记录'); //写入日志记录
            return error(0 , '商户id不存在');
        }
        self::getStore();//商户信息获取
        //self::getUser();//用户信息获取
        //判断是否开启打印推送
        if(self::$storeInfo['state'] != 1) {
            Util::wl_log('printing' , PATH_MODULE . "log/" , [
                'error'     => '未开启打印推送' ,
                'tid'       => $tid,
                'sid'       => self::$orderInfo['sid'] ,
                'storename' => self::$storeInfo['storename']
            ] , '打印推送消息 —— 错误记录'); //写入日志记录
            return error(0 , '未开启打印推送');
        }
        //获取打印设置信息
        $printUrl    = $_W['siteroot'] . 'addons/' . MODULE_NAME . '/requests/printing.php?tid='.self::$orderInfo['orderno'];

        $openid    = self::$storeInfo['openid'];
        $secret    = self::$storeInfo['secret'];
        #2、信息配置
        $payType = self::getPayType(self::$orderInfo['paytype']);//支付方式获取
        //https://citydev.weliam.com.cn/addons/weliam_smartcity/requests/printing.php?tid=2020040818053400013924445846
        $platform = unserialize(pdo_getcolumn(PDO_NAME."setting",['key'=>'base'],'value'));//平台设置信息获取
        $name = $platform['name'] ? : '乌卡拉';
        $data = [
            'userAccount' => self::$storeInfo['userAccount'] ,//操作员
            'title'       => $payType ,//消息标题
            'click'       => h5_url('pages/subPages/orderList/orderList'),//点击消息打开的链接
            'broadcast'   => $name.'订单'.self::$orderInfo['price']."元" ,//语音播报内容（此项为空时，播报title）
            'printUrl'    => $printUrl,//打印url
            'action'      => 'PlatformBusiness' ,//事件处理(如qd11云打印：Printer,区分大小写)
        ];
        $first = strpos($data['click'],'&aid=');
        $data['click'] = substr($data['click'],0,$first);
        try {
            #3、发送请求
            $controllerName = 'OpenApi';
            $json_data = json_encode ( $data );
            $Signature = strtoupper ( md5 ( $openid . $secret . self::$timeStamp . $json_data ) );
            $url = "http://openapi.1card1.cn/" . $controllerName . "/PushMessage?openId=" . $openid . "&signature=" . $Signature . "&timestamp=" . self::$timeStamp;
            $postData = "data=" . $json_data;
            $result_data = self::postData ( $url, $postData );
            $result = json_decode ( $result_data, true );
            #4、记录日志
            $result['time'] = date("Y-m-d H:i:s",time());
            $result['orderno'] = self::$orderInfo['orderno'];
            $result['data'] = $data;
            Util::wl_log('printing', PATH_MODULE."log/",$result,'打印推送消息 —— 操作结果'); //写入日志记录

            return $result;
        } catch (\Throwable $e) {
            $result['msg'] = $e->getMessage();
            $result['orderno'] = self::$orderInfo['orderno'];
            Util::wl_log('printing', PATH_MODULE."log/",$result,'打印推送消息 —— 错误记录'); //写入日志记录
        }
    }
    /**
     * Comment: 订单信息获取
     * Author: zzw
     * Date: 2020/4/3 9:09
     * @param $order_no
     * @return bool
     */
    protected static function getOrder(){
        global $_W;
        #1、获取查询条件
        $where = " tid = ".self::$tid;

        #2、获取订单信息
        $orderInfo = pdo_fetch("SELECT mid,tid as orderno,fee as price,source,plugin,payfor FROM" .tablename(PDO_NAME.'paylogvfour') ." WHERE {$where} ");
        if(empty($orderInfo)){
            $paylogid = pdo_getcolumn(PDO_NAME.'order',array('orderno' => self::$tid),'paylogid');
            $orderInfo = pdo_fetch("SELECT mid,tid as orderno,fee as price,source,plugin,payfor FROM" .tablename(PDO_NAME.'paylogvfour') ." WHERE plid = {$paylogid} ");
        }
        if($orderInfo['plugin']=='Citydelivery'){
            $orderInfo = [];
            $orderInfo['orderno'] = self::$tid;
        }
        #3、处理获取的信息
        $orderInfo['mid'] = $orderInfo['mid'] > 0 ? $orderInfo['mid'] : $_W['mid'];
        #3、获取商户id
        $infoWhere = " WHERE orderno = {$orderInfo['orderno']} ";
        if($orderInfo['plugin'] == 'Rush'){
            $field = " sid,paytype,paytime,mid,price as goodsprice,activityid as goods_id ";
            $table = tablename(PDO_NAME."rush_order");
        }else{
            $field = " sid,paytype,payfor,paytime,mid,price,goodsprice,fkid as goods_id ";
            $table = tablename(PDO_NAME."order");
        }
        $order = pdo_fetch("SELECT {$field} FROM ".$table.$infoWhere);
        //计算优惠金额
        $order['discount'] = sprintf("%0.2f",$order['goodsprice'] - $orderInfo['price']);
        if($order['payfor'] == 'deliveryOrder'){
            $order['payfor'] = 'DeliveryOrder';
            $order['discount'] = 0;
        }
        self::$orderInfo = ($order + $orderInfo);
    }
    /**
     * Comment: 获取商户信息
     * Author: zzw
     * Date: 2020/4/3 9:54
     */
    protected static function getStore(){
        //获取商户信息
        $store = pdo_get(PDO_NAME."merchantdata",['id'=>self::$orderInfo['sid']],['storename','printing']);
        //处理推送信息
        $printing = unserialize($store['printing']) ? :[];
        unset($store['printing']);
        //合并生成新的商户设置信息
        $store = ($store + $printing);
        $store['state'] = $store['state'] ? $store['state'] : 0;

        self::$storeInfo = $store;
    }
    /**
     * Comment: 获取用户信息
     * Author: zzw
     * Date: 2020/4/3 10:38
     */
    protected static function getUser(){
        //用户信息获取
        $user = pdo_get(PDO_NAME."member",['id'=>self::$orderInfo['mid']] ,['nickname']);
        //会员等级获取
        $default = Setting::wlsetting_read('halflevel')['name'] ? : '普通会员' ;
        $lvId = pdo_getcolumn(PDO_NAME . "halfcardmember" , [
            'mid'     => self::$orderInfo['mid'] ,
            'disable' => 0 ,
        ] , 'levelid');
        if($lvId) $lvTitle = pdo_getcolumn(PDO_NAME."halflevel",['id'=>$lvId],'name');
        $user['lv'] = $lvTitle ? $lvTitle : $default;

        self::$userInfo = $user;
    }
    /**
     * Comment: 配置参数信息获取
     * Author: zzw
     * Date: 2020/4/2 15:48
     * @return false|mixed|string
     */
    protected static function getData(){
        #1、基本信息获取
        $payType = self::getPayType();//支付方式获取
        $payProject = self::getPayProject();//支付项目获取
        $platform = unserialize(pdo_getcolumn(PDO_NAME."setting",['key'=>'base'],'value'));//平台设置信息获取
        #2、信息配置 //'Content2'=>null
        if(self::$storeInfo['header']){
            //图片处理
            $images = tomedia(self::$storeInfo['header']);
            $images = str_replace('requests/','',$images);
            $Type = 'Image';
            $Content = $images;
        }else {
            $Type = 'String';
            $Content = '            '.$platform['name'];
        }
        //处理同城配送订单信息
        if(self::$orderInfo['payfor']== 'DeliveryOrder'){
            $neworderinfo = pdo_get('wlmerchant_order',array('orderno' => self::$orderInfo['orderno']),array('buyremark','status','goodsprice','vipdiscount','expressprcie','expressid'));
            self::$orderInfo['goodsprice'] = $neworderinfo['goodsprice'];
            self::$orderInfo['discount'] = $neworderinfo['vipdiscount'];
        }

        $data1 = [
            ['Type' => $Type , 'Content' =>$Content],
            ['Type' => 'String' , 'Content' => '    店面：' . self::$storeInfo['storename']] ,
            ['Type' => 'String' , 'Content' => '支付时间：' . date("Y-m-d H:i:s" , self::$orderInfo['paytime'])] ,
            ['Type' => 'String' , 'Content' => '  订单号：' . self::$orderInfo['orderno']] ,
            ['Type' => 'String' , 'Content' => '  操作员：' . self::$storeInfo['userAccount']] ,
            ['Type' => 'String' , 'Content' => '--------------------------------'] ,
            ['Type' => 'String' , 'Content' => '业务类型：在线支付'] ,
            ['Type' => 'String' , 'Content' => '支付方式：' . $payType] ,
        ];

        if(!is_array($payProject)){
            $data1[] = ['Type' => 'String' , 'Content' => '支付项目：' . $payProject];
        }else{
            foreach ($payProject as $ke => $ject){
                if($ke == 0){
                    $data1[] = ['Type' => 'String' , 'Content' => '支付项目：' . $ject];
                }else{
                    $data1[] = ['Type' => 'String' , 'Content' => '          ' . $ject];
                }
            }
        }
        if(!empty($neworderinfo['buyremark'])){
            $data1[] = ['Type' => 'String' , 'Content' => '买家备注：'.$neworderinfo['buyremark']];
        }
        if($neworderinfo['status'] == 1){
            $data1[] = ['Type' => 'String' , 'Content' => '配送方式：到店自提'];
        }else if($neworderinfo['status'] == 4){
            $data1[] = ['Type' => 'String' , 'Content' => '配送方式：商家配送'];
        }
        if(!empty($neworderinfo['expressid'])){
            $express = pdo_get('wlmerchant_express',array('id' => $neworderinfo['expressid']),array('name','tel','address'));
            $data1[] = ['Type' => 'String' , 'Content' => '收货人：'.$express['name']];
            $data1[] = ['Type' => 'String' , 'Content' => '联系电话：'.$express['tel']];
            $data1[] = ['Type' => 'String' , 'Content' => '配送地址：'.$express['address']];
        }

        $data1[] = ['Type' => 'String' , 'Content' => '商品金额：' . self::$orderInfo['goodsprice']];
        $data1[] = ['Type' => 'String' , 'Content' => '折扣优惠：' . self::$orderInfo['discount']];
        if($neworderinfo['expressprcie']>0){
            $data1[] = ['Type' => 'String' , 'Content' => '配送费用：' . $neworderinfo['expressprcie']];
        }
        $data2 = [
            ['Type' => 'String' , 'Content' => '实付金额：' . self::$orderInfo['price']] ,
            ['Type' => 'String' , 'Content' => '--------------------------------'] ,
            ['Type' => 'String' , 'Content' => '会员级别：' . self::$userInfo['lv']] ,
            ['Type' => 'String' , 'Content' => '会员姓名：' . self::$userInfo['nickname']] ,
            ['Type' => 'String' , 'Content' => '打印时间：' . date("Y-m-d H:i:s" , self::$timeStamp)] ,
            ['Type' => 'String' , 'Content' => '--------------------------------'] ,
            ['Type' => 'String' , 'Content' => '        请妥善保管好购物凭证      '] ,
            ['Type' => 'String' , 'Content' => '            谢谢惠顾            '] ,
        ];
        $data = array_merge_recursive($data1,$data2);
        return $data;
    }
    /**
     * Comment: 支付方式
     * Author: zzw
     * Date: 2020/4/3 9:41
     * @param $type
     * @return string
     */
    protected static function getPayType(){
        switch (self::$orderInfo['paytype']){
            case 1:$typeName = '余额';break;//余额
            case 2:$typeName = '微信';break;//微信
            case 3:$typeName = '支付宝';break;//支付宝
        }

        return $typeName.'支付';
    }
    /**
     * Comment: 获取支付项目
     * Author: zzw
     * Date: 2020/4/8 18:22
     * @param $type
     * @return string
     */
    protected static function getPayProject(){
        Util::wl_log('printing222' , PATH_MODULE . "log/" ,self::$orderInfo, '打印推送消息 —— 错误记录'); //写入日志记录
        switch (self::$orderInfo['payfor']) {
            case 'Applydis':
                $title = '开通分销商';
                break;//开通分销商
            case 'BargainOrder':
                $title = pdo_getcolumn(PDO_NAME."bargain_activity",['id'=>self::$orderInfo['goods_id']],'name');
                break;//砍价商品购买
            case 'Bond':
                $title = '认证保证金缴纳';
                break;//认证保证金缴纳
            case 'Charge':
                $title = '付费入驻';
                break;//付费入驻
            case 'CitycardOrder':
                $title = '同城名片支付';
                break;//同城名片支付
            case 'consumOrder':
                $title = pdo_getcolumn(PDO_NAME."consumption_goods",['id'=>self::$orderInfo['goods_id']],'title');
                break;//积分商品购买
            case 'Couponsharge':
                $title = pdo_getcolumn(PDO_NAME."couponlist",['id'=>self::$orderInfo['goods_id']],'title');;
                break;//卡卷购买
            case 'Fightsharge':
                $title = pdo_getcolumn(PDO_NAME."fightgroup_goods",['id'=>self::$orderInfo['goods_id']],'name');;
                break;//拼团商品购买
            case 'GrouponOrder':
                $title = pdo_getcolumn(PDO_NAME."groupon_activity",['id'=>self::$orderInfo['goods_id']],'name');;
                break;//团购商品购买
            case 'halfcard':
                $title = '一卡通开通/续费';
                break;//一卡通开通/续费
            case 'payonline':
                $title = '在线买单';
                break;//在线买单
            case 'pocketfabusharge':
                $title = '帖子付费项目';
                break;//帖子付费项目
            case 'RushOrder':
                $title = pdo_getcolumn(PDO_NAME."rush_activity",['id'=>self::$orderInfo['goods_id']],'name');;
                break;//抢购商品购买
            case 'TaxipayOrder':
                $title = '出租车在线买单';
                break;//出租车在线买单
            case 'DeliveryOrder':
                $orderno = self::$orderInfo['orderno'];
                $smallorders = pdo_fetchall("SELECT gid,money,num,specid FROM ".tablename('wlmerchant_delivery_order')."WHERE tid = {$orderno} ORDER BY price DESC");
                foreach ($smallorders  as $ke => &$orr){
                    $goods = pdo_get('wlmerchant_delivery_activity',array('id' => $orr['gid']),array('name'));
                    $orr['name'] = $goods['name'];
                    if($orr['specid']>0){
                        $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                        $orr['name'] .= '/'.$specname;
                    }
                    $title[] = $orr['name'].' X'.$orr['num'];
                }
                break;//同城配送
        }
        return $title;
    }
    /**
     * Comment: 发送请求
     * Author: zzw
     * Date: 2020/4/2 18:23
     * @param $url
     * @param $data
     * @return bool|string
     */
    protected static function postData($url, $data) {
        $ch = curl_init ();
        $timeout = 300; // 设定超时时间
        curl_setopt ( $ch, CURLOPT_URL, $url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $data );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
        curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
        $handles = curl_exec ( $ch );

        $httpCode = curl_getinfo ( $ch, CURLINFO_HTTP_CODE );
        if ($httpCode != 200) {
            if ($httpCode == 0) {
                return "{\"status\":-1,\"message\":\"网络连接失败！\"}";
            } else {
                return "{\"status\":-1,\"message\":\"网络或者服务器出现异常，HTTP返回状态码" . $httpCode . "\"}";
            }
        }
        curl_close ( $ch );
        return $handles;
    }



    /**
     * Comment: 获取打印配置信息
     * Author: zzw
     * Date: 2020/4/3 14:02
     * @param $tid
     * @return false|mixed|string
     */
    public static function getPrintingData($tid){
        global $_W;
        #1、信息获取
        self::$tid = $tid;
        self::$timeStamp = time();
        self::getOrder();//订单信息获取
        self::getStore();//商户信息获取
        self::getUser();//用户信息获取
        $data = self::getData();//获取打印设置信息
        return $data;
    }

}
