<?php
/**
 * 订单支付模型
 */
defined('IN_IA') or exit('Access Denied');
use Yansongda\Pay\Pay as YanSongDa;
//use wechatpay\Builder as Builder;
//use \Wechatpay\Util\PemUtil as PemUtil;

class Payment{
    protected static $orderInfo,//订单信息
        $setting,//支付设置信息
        $payConfig,//支付配置信息
        $payNotify,//支付异步通知地址
        $payReturn,//支付回调跳转地址
        $payLogPath = PATH_MODULE."payment/",//支付异步通知日志地址
        $goodsName,//商品名称
        $source,//支付渠道 1=公众号（默认）；2=h5；3=小程序
        $requestPaymentInfo,
        $blendflag,//混合支付
        $userInfo;//用户信息
    /**
     * Comment: 支付初始化
     * Author: zzw
     * Date: 2019/8/28 16:30
     * @param $order_no string  订单号
     * @param $payType  int     支付方式 1=余额；2=微信；3=支付宝；4=货到付款 5=云支付
     * @param $name     string  商品名称
     * @param $source   int     渠道信息 1=公众号（默认）；2=h5；3=小程序
     * @return array
     */
    public static function init($order_no,$payType,$name,$source,$blendflag = 0){
        global $_W;
        self::$source = $source;
        self::$blendflag = $blendflag;
        self::$requestPaymentInfo = ['order_no'=>$order_no,'pay_type'=>$payType,'name'=>$name,'source'=>$source];
        #1、获取订单信息
        self::$goodsName = self::goodsNameHandle($name);//商品名称
        self::getOrder($order_no);
        #2、获取用户信息
        self::getUser();
        #3、支付设置信息获取
        self::setInfo($payType);
        //判断  下单用户和支付用户不是同一个用户
//        $result = self::isUserOrder($_W['wlmember'],(self::$orderInfo+self::$userInfo));
//        if($result) Commons::sRenderError('系统繁忙，请刷新页面重新发起支付!');
        //订单校验
        if(self::$requestPaymentInfo['order_no'] != self::$orderInfo['orderno']){
            Commons::sRenderError('系统繁忙，请刷新页面重新发起支付');
        }
        #4、获取支付配置信息,并且调取支付方式。
        #支付方式：1=余额；2=微信；3=支付宝；4=货到付款
        #渠道信息：1=公众号（默认）；2=h5；3=小程序
        $payKey = self::$source.'_'.$payType;//渠道_支付方式 = 调用的支付方式
        switch ($payKey) {
            case '1_1':case '2_1':case '3_1':
            $res = self::balancePayment();
            break;//余额支付（所有渠道的余额支付）
            case '1_2':
                self::weChatConfig();
                $res = self::WeChat_WeChat();
                break;//微信公众号 - 微信支付
            case '1_3':case '2_3':
            self::aliPayConfig();
            $res = self::WeChat_aliPay();
            break;//微信公众号/H5 - 支付宝支付
            case '1_5':
                self::yunPayConfig();
                $res = self::Yun_WeChat_WeChat();
                break;//微信公众号 - 银联云收单
            case '2_2':
                self::weChatConfig();
                $res = self::H5_WeChat();
                break;//H5 - 微信支付
            case '3_2':
                self::weChatConfig();
                if(self::$setting['channel'] == 1){
                    $amount = sprintf("%.0f",(self::$orderInfo['price'] * 100));
                    $sub_orders = [
                        'mchid' => self::$setting['mchid'],
                        'amount' => intval($amount),
                        'trade_no' => self::$orderInfo['orderno'],
                        'description' => self::$goodsName ? : '小程序 — 微信支付'
                    ];
                    $orderinfo = [
                        'openid' => self::$userInfo['wechat_openid'],
                        'combine_trade_no' => self::$orderInfo['orderno'],
                        'expire_time' => time() + 86400*30,
                        'sub_orders' => [$sub_orders]
                    ];
                    $res = self::shopCreateorder($orderinfo);
                }else{
                    $res = self::wxApp_WeChat();
                }
                break;//微信小程序 - 微信支付
        }
        #5、返回内容
        return $res;
    }
    /**
     * Comment: 获取订单信息
     * Author: zzw
     * Date: 2019/8/29 11:24
     * @param $order_id
     * @param $tableType
     */
    protected static function getOrder($order_no){
        global $_W;
        #1、获取查询条件   AND uniacid = {$_W['uniacid']} AND module = 'weliam_smartcity'
        $where = " tid = {$order_no} ";
        #2、获取订单信息
        self::$orderInfo = pdo_fetch("SELECT plid,mid,tid as orderno,fee as price,source,plugin,uniacid,blendcredit,batchNo,traceNo FROM"
            .tablename(PDO_NAME.'paylogvfour') ." WHERE {$where} ");
        #3、处理获取的信息
        self::$orderInfo['mid'] = self::$orderInfo['mid'] > 0 ? self::$orderInfo['mid'] : $_W['mid'];
        if(empty(self::$source)) self::$source = self::$orderInfo['source'] ? self::$orderInfo['source'] : 1;
        pdo_update(PDO_NAME."paylogvfour",['source'=>self::$source],['tid'=>$order_no]);
        #3、获取商户id
        if(self::$orderInfo['plugin'] == 'Rush'){
            $storeinfo = pdo_get(PDO_NAME."rush_order",['orderno'=>self::$orderInfo['orderno']],array('sid','paysetid','activityid'));
            self::$orderInfo['sid'] = $storeinfo['sid'];
            self::$orderInfo['paysetid'] = $storeinfo['paysetid'];
            $goods_tag = pdo_getcolumn(PDO_NAME.'rush_activity',array('id'=>$storeinfo['activityid']),'goods_tag');

        }else if(self::$orderInfo['plugin'] ==  'Taxipay'){
            self::$orderInfo['sid'] = pdo_getcolumn(PDO_NAME."order",['orderno'=>self::$orderInfo['orderno']],'fkid');
        }else{
            $storeinfo = pdo_get(PDO_NAME."order",['orderno'=>self::$orderInfo['orderno']],array('sid','paysetid','payfor','fkid'));
            self::$orderInfo['sid'] = $storeinfo['sid'];
            self::$orderInfo['paysetid'] = $storeinfo['paysetid'];
            if($storeinfo['payfor'] == 'couponsharge'){
                $goods_tag = pdo_getcolumn(PDO_NAME.'couponlist',array('id'=>$storeinfo['fkid']),'goods_tag');
            }else if($storeinfo['payfor'] == 'fightsharge'){
                $goods_tag = pdo_getcolumn(PDO_NAME.'fightgroup_goods',array('id'=>$storeinfo['fkid']),'goods_tag');
            }else if($storeinfo['payfor'] == 'grouponOrder'){
                $goods_tag = pdo_getcolumn(PDO_NAME.'groupon_activity',array('id'=>$storeinfo['fkid']),'goods_tag');
            }else if($storeinfo['payfor'] == 'bargainOrder'){
                $goods_tag = pdo_getcolumn(PDO_NAME.'bargain_activity',array('id'=>$storeinfo['fkid']),'goods_tag');
            }else if($storeinfo['payfor'] == 'deliveryOrder'){
                $goods_tag = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$storeinfo['sid']),'delive_goods_tag');
            }else if($storeinfo['payfor'] == 'halfcardOrder'){
                $goods_tag = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$storeinfo['sid']),'online_goods_tag');
            }
        }
        //支付立减信息
        if(!empty($goods_tag)){
            self::$orderInfo['goods_tag'] = $goods_tag;
        }

    }
    /**
     * Comment: 获取用户信息
     * Author: zzw
     * Date: 2019/9/2 15:56
     */
    protected static function getUser(){
        self::$userInfo = pdo_get(PDO_NAME."member",['id'=>self::$orderInfo['mid']]
            ,['openid','wechat_openid']);
    }
    /**
     * Comment: 获取支付设置信息
     * Author: zzw
     * Date: 2019/8/29 10:54
     * @param $payType
     */
    protected static function setInfo($payType){
        global $_W;
        #1、支付设置信息id获取。支付方式：1=余额；2=微信；3=支付宝；4=货到付款
        $set = Setting::wlsetting_read("payment_set");
        #支付方式：1=余额；2=微信；3=支付宝；4=货到付款
        #渠道信息：1=公众号（默认）；2=h5；3=小程序
        $payKey = self::$source.'_'.$payType;//渠道_支付方式 = 调用的支付方式
        switch ($payKey) {
            case '1_1':
                self::$setting = $set['wechat'];
                break;
            case '2_1':
                self::$setting = $set['h5'];
                break;
            case '3_1':
                self::$setting = $set['wxapp'];
                break;//余额支付（所有渠道的余额支付）
            case '1_2':
                $id = self::$orderInfo['paysetid'] ? : $set['wechat']['wechat'];
                //https://citydev.weliam.com.cn/addons/weliam_smartcity/payment/WeChatCallback.php
                self::$payNotify = PAY_PATH."WeChatCallback.php";
                break;//微信公众号 - 微信支付
            case '1_3':
                $id = $set['wechat']['alipay']; //支付设置信息id
                self::$payNotify = PAY_PATH."AlipayCallback.php";//支付回调地址
                self::$payReturn = h5_url('pages/subPages/paySuccess/paySuccess',['tid'=>self::$orderInfo['orderno']]);
                break;//微信公众号 - 支付宝支付
            case '2_2':
                $id = self::$orderInfo['paysetid'] ? : $set['h5']['wechat'];
                self::$payNotify = PAY_PATH."WeChatCallback.php";
                self::$payReturn = h5_url('pages/subPages/paySuccess/paySuccess',['tid'=>self::$orderInfo['orderno']]);
                break;//H5 - 微信支付
            case '2_3':
                $id = $set['h5']['alipay']; //支付设置信息id
                self::$payNotify = PAY_PATH."AlipayCallback.php";//支付回调地址
                self::$payReturn = h5_url('pages/subPages/paySuccess/paySuccess',['tid'=>self::$orderInfo['orderno']]);
                break;//H5 - 支付宝支付
            case '3_2':
                if($set['wxapp']['channel'] == 1){
                    self::$setting = $set['wxapp'];
                }else{
                    $id = self::$orderInfo['paysetid'] ? : $set['wxapp']['wechat'];
                    self::$payNotify = PAY_PATH."WeChatCallback.php";
                    self::$payReturn = h5_url('pages/subPages/paySuccess/paySuccess',['tid'=>self::$orderInfo['orderno']]);
                }
                break;//微信小程序 - 微信支付
            case '1_5':
                $id = self::$orderInfo['paysetid'] ? : $set['wechat']['yunpay'];
                break;
        }
        if($payType != 1 && $id > 0){
            #2、支付设置信息获取
            $info = pdo_get(PDO_NAME."payment",['id'=>$id]);
            #3、处理支付方式
            self::$setting = json_decode($info['param'],true);
            #3、获取对应渠道的appid
            switch ($payType){
                case 2:case 5:
                if(self::$source == 1 || self::$source == 2){
                    self::$setting['app_id'] = Util::object_array($_W['account'])['key'];//公众号、H5
                }else{
                    self::$setting['app_id'] = Setting::wlsetting_read('wxapp_config')['appid'];//小程序
                }
                break;//微信
            }
        }
    }
    /**
     * Comment: 处理商品名称长度（微信支付限制不能超过128字节）
     * Author: zzw
     * Date: 2019/10/11 17:36
     * @param $name
     * @return string
     */
    protected static function goodsNameHandle($name){
        //微信支付body字节数量不能大于128   这里判断120字节   余下8字节用作字节保留数
        $byteLength = strlen($name);//获取商品名称的字节长度  单位：字节数
        $byteRestriction = 120;
        #1、判断当前名称是否大于120字节
        if($byteLength >= $byteRestriction){
            #2、大于：进行裁剪。urf-8编码中：中文 = 3字节   初始截取位置为120/3=40开始
            $start = 40;
            $newName = substr($name,0,$start);
            for($i=40;strlen($newName)<$byteRestriction;$i++){
                $newName = substr($name,0,$i);
            }
            #3、防止最后一个字符乱码 进行截取
            $newName = mb_substr($newName,0,(mb_strlen($newName) - 1));
            $newName .= '...';
        }

        return !empty($newName) ? $newName : $name;
    }

    /****** 支付配置信息获取 ***********************************************************************************************/
    /**
     * Comment: 支付宝配置信息获取
     * Author: zzw
     * Date: 2019/8/29 10:30
     * @return array
     */
    protected static function aliPayConfig(){
        //$filePath        = PATH_ATTACHMENT . "public_file/" . MODULE_NAME . "/";
        self::$payConfig = [
            'app_id' => trim(self::$setting['ali_app_id']),//app_id
            'notify_url' => trim(self::$payNotify),
            'return_url' => trim(self::$payReturn),
            'ali_public_key' => trim(self::$setting['ali_public_key']),
            'private_key' => trim(self::$setting['app_private_key']),
            'apiversion' => self::$setting['apiversion'],
            'rootCert' => tomedia(self::$setting['rootCert']),
            'publicKey' => tomedia(self::$setting['publicKey']),
        ];
    }
    /**
     * Comment: 微信配置信息获取
     * Author: zzw
     * Date: 2019/9/2 15:00
     */
    protected static function weChatConfig(){
        $filePath        = PATH_ATTACHMENT . "public_file/" . MODULE_NAME . "/";
        if(self::$setting['shop_type'] == 2){
            //子商户支付
            self::$payConfig = [
                'appid'          => trim(self::$setting['sub_up_app_id']) , // APP APPID
                'app_id'         => trim(self::$setting['sub_up_app_id']) , // 公众号 APPID
                'miniapp_id'     => trim(self::$setting['sub_up_app_id']) , // 小程序 APPID
                'sub_appid'      => trim(self::$setting['app_id']) , // 子商户 APP APPID
                'sub_app_id'     => trim(self::$setting['app_id']) , // 子商户 公众号 APPID
                'sub_miniapp_id' => trim(self::$setting['app_id']) , // 子商户 小程序 APPID
                'mch_id'         => trim(self::$setting['shop_number']) ,
                'sub_mch_id'     => trim(self::$setting['sub_shop_number']) , // 子商户商户号
                'key'            => trim(self::$setting['secret_key']) ,//secret_key     sub_secret_key
                'notify_url'     => trim(self::$payNotify) ,
                'cert_client'    => trim($filePath . self::$setting['cert_certificate']) , // optional, 退款，红包等情况时需要用到
                'cert_key'       => trim($filePath . self::$setting['key_certificate']) ,// optional, 退款，红包等情况时需要用到
                'mode'           => 'service' ,
            ];
            //是否开启子商户企业付款
            /*if (self::$setting['sub_enterprise_payment'] == 2) {
                self::$payConfig['key']         = trim(self::$setting['sub_secret_key']);
                self::$payConfig['cert_client'] = trim(self::$setting['sub_cert_certificate']);
                self::$payConfig['cert_key']    = trim(self::$setting['sub_key_certificate']);
            }*/
        }else{
            //一般支付
            self::$payConfig = [
                'appid'          => trim(self::$setting['app_id']) , // APP APPID
                'app_id'         => trim(self::$setting['app_id']) , // 公众号 APPID
                'miniapp_id'     => trim(self::$setting['app_id']) , // 小程序 APPID
                'mch_id'         => trim(self::$setting['shop_number']) ,
                'key'            => trim(self::$setting['secret_key']) ,
                'notify_url'     => trim(self::$payNotify) ,
                'cert_client'    => trim($filePath . self::$setting['cert_certificate']) , // optional, 退款，红包等情况时需要用到
                'cert_key'       => trim($filePath . self::$setting['key_certificate']) ,// optional, 退款，红包等情况时需要用到
                'goods_tag'      => trim(self::$setting['shop_goods_tag'])
            ];
        }
    }

    /**
     * Comment: 云收单配置信息获取
     * Author: wlf
     * Date: 2021/07/26 09:48
     */
    protected static function yunPayConfig(){
        global $_W;
        self::$payConfig = [
            'merchantNo' => self::$setting['yun_merchantNo'],
            'terminalNo' => self::$setting['yun_terminalNo'],
        ];
        if(empty(self::$orderInfo['batchNo']) || empty(self::$orderInfo['traceNo'])){
            $yun_signIn = self::yunSignIn(self::$setting['yun_merchantNo'],self::$setting['yun_terminalNo']);
            pdo_update('wlmerchant_paylogvfour',$yun_signIn,array('plid' => self::$orderInfo['plid']));
            self::$orderInfo['batchNo'] = $yun_signIn['batchNo'];
            self::$orderInfo['traceNo'] = $yun_signIn['traceNo'];
        }
        self::$payConfig['batchNo'] = self::$orderInfo['batchNo'];
        self::$payConfig['traceNo'] = self::$orderInfo['traceNo'];
        self::$payConfig['outTradeNo'] = self::$orderInfo['orderno'];
        self::$payConfig['transAmount'] = self::$orderInfo['price'] * 100;
        self::$payConfig['appid'] = trim(self::$setting['app_id']);
        self::$payConfig['openId'] = self::$userInfo['openid'];
        self::$payConfig['tradeChannel'] = '01';
        self::$payConfig['nonceStr'] = random(16);
        $notifyUrl = rtrim($_W['siteroot'],'/').':19080/addons/' . MODULE_NAME.'/core/common/yunAsyNotify.php';
        self::$payConfig['notifyUrl'] = str_replace('https','http',$notifyUrl);
    }


    /****** 进行支付 ***********************************************************************************************/
    /**
     * Comment: 余额支付(所有渠道)
     * Author: zzw
     * Date: 2019/9/26 9:28
     */
    protected static function balancePayment(){
        global $_W;
        #1、获取当前用户的余额信息 判断用户余额是否充足
        $balance = $_W['wlmember']['credit2'];
        if($balance < self::$orderInfo['price']){
            if(self::$setting['blend'] > 0){ //混合支付
                pdo_update('wlmerchant_paylogvfour',array('blendcredit' => $balance),array('tid' => self::$orderInfo['orderno'],'plugin' => self::$orderInfo['plugin']));
                $surplus = sprintf("%.2f",self::$orderInfo['price'] - $balance);
                return ['status'=>2,'surplus' => $surplus];
            }else{
                if($_W['wlsetting']['recharge']['status'] > 0){
                    Commons::sRenderError('余额不足',['jump' => 1]);
                }else{
                    Commons::sRenderError('余额不足',['jump' => 0]);
                }
            }
        }
        #2、进行订单的支付
        $res = Member::credit_update_credit2($_W['mid'],-self::$orderInfo['price'],'购买['.self::$goodsName.']支付余额');
        if($res){
            //是否支付成功status:0=失败；1=成功
            VoiceAnnouncements::PushVoiceMessage(self::$orderInfo['price'], self::$orderInfo['sid'], 1,self::$orderInfo['plugin']); //调用云喇叭进行商户收款播报
            Util::wl_log('request_payment', self::$payLogPath,[self::$requestPaymentInfo,self::$orderInfo],'支付请求 —— 余额'); //写入日志记录
            $info = [
                'type'           => 1 ,//支付方式
                'tid'            => self::$orderInfo['orderno'] ,//订单号
                'transaction_id' => self::$orderInfo['trade_no'] ,
                'time'           => time(),
            ];
            PayResult::main($info);//调用方法处理订单
            return ['status'=>1];
        }
        return ['status'=>0];
    }
    /**
     * Comment: 微信公众号/H5 —— 支付宝支付
     * Author: zzw
     * Date: 2019/9/2 10:47
     */
    protected static function WeChat_aliPay(){
        #1、配置订单信息
        $order = [
            'out_trade_no' => self::$source . '_' . self::$orderInfo['orderno'] ,
            'total_amount' => self::$orderInfo['price'] ,
            'subject'      => self::$goodsName ? : '微信公众号/H5 —— 支付宝支付' ,
            'http_method'  => 'GET' ,
        ];
        #2、获取支付宝支付跳转地址
        Util::wl_log('request_payment' , self::$payLogPath , self::$requestPaymentInfo , '支付请求 —— 支付宝'); //写入日志记录
        $res = YanSongDa::alipay(self::$payConfig)->wap($order);
        $url = $res->getTargetUrl();

        return $url;
    }
    /**
     * Comment: 微信公众号 —— 微信支付
     * Author: zzw
     * Date: 2019/9/2 16:08
     * @return array
     */
    protected static function WeChat_WeChat(){
        if(self::$orderInfo['blendcredit'] > 0){ //混合支付
            if(self::$blendflag > 0){
                self::$orderInfo['price'] = sprintf("%.2f",self::$orderInfo['price'] - self::$orderInfo['blendcredit']);
            }else{
                pdo_update('wlmerchant_paylogvfour',array('blendcredit' => 0),array('tid' => self::$orderInfo['orderno'],'plugin' => self::$orderInfo['plugin']));
            }
        }
        #1、配置订单信息
        $order = [
            'out_trade_no' => self::$source . '_' . self::$orderInfo['orderno'] ,
            'body'         => self::$goodsName ? : '公众号支付 —— 微信支付' ,
            'total_fee'    => sprintf("%.0f" , (self::$orderInfo['price'] * 100)) ,
        ];
        if(self::$orderInfo['paysetid'] > 0){
            $order['profit_sharing'] = 'Y';
        }
        if (self::$setting['shop_type'] == 2) {
            $order['sub_openid'] = self::$userInfo['openid'];
        } else {
            $order['openid'] = self::$userInfo['openid'];
        }
        //支付立减
        if(!empty(self::$payConfig['goods_tag'])){
            $order['goods_tag'] = self::$payConfig['goods_tag'];
        }
        if(!empty(self::$orderInfo['goods_tag'])){
            $order['goods_tag'] = self::$orderInfo['goods_tag'];
        }
        try {
            Util::wl_log('request_payment', self::$payLogPath,self::$requestPaymentInfo,'支付请求 —— 微信（公众号）'); //写入日志记录
            #3、调用支付接口 获取信息参数
            $params = yanSongDa::wechat(self::$payConfig)->mp($order);

            #4、返回支付信息
            $payParams = [
                'appId' => $params->appId,
                'timeStamp' => $params->timeStamp,
                'nonceStr' => $params->nonceStr,
                'package' => $params->package,
                'signType' => $params->signType,
                'paySign' => $params->paySign
            ];
            return $payParams;
        } catch (Exception $e) {
            $emg = $e->getMessage();
//            if(strstr($emg,'201')){ //处理订单号重复
//                $neworderno = createUniontid();
//                pdo_update('wlmerchant_paylogvfour',array('tid' => $neworderno),array('tid'=>self::$orderInfo['orderno']));
//                if(self::$orderInfo['plugin'] == 'Rush'){
//                    pdo_update('wlmerchant_rush_order',array('orderno' => $neworderno),array('orderno' => self::$orderInfo['orderno']));
//                }else{
//                    pdo_update('wlmerchant_order',array('orderno' => $neworderno),array('orderno' => self::$orderInfo['orderno']));
//                }
//                $emg = '微信支付通信繁忙，请重试';
//            }
            Commons::sRenderError($emg);
        }
    }
    /**
     * Comment: h5 - 微信支付
     * Author: zzw
     * Date: 2019/5/27 14:34
     * @return string
     */
    protected static function H5_WeChat(){
        if(self::$orderInfo['blendcredit'] > 0){ //混合支付
            if(self::$blendflag > 0){
                self::$orderInfo['price'] = sprintf("%.2f",self::$orderInfo['price'] - self::$orderInfo['blendcredit']);
            }else{
                pdo_update('wlmerchant_paylogvfour',array('blendcredit' => 0),array('tid' => self::$orderInfo['orderno'],'plugin' => self::$orderInfo['plugin']));
            }
        }
        #1、配置订单信息
        $order = [
            'out_trade_no'   => self::$source . '_' . self::$orderInfo['orderno'] ,
            'body'           => self::$goodsName ? : 'H5 —— 微信支付' ,
            'total_fee'      => sprintf("%.0f",(self::$orderInfo['price'] * 100)) ,
        ];
        if(self::$orderInfo['paysetid'] > 0){
            $order['profit_sharing'] = 'Y';
        }
        #2、调用支付接口 获取信息参数
        try {
            Util::wl_log('request_payment', self::$payLogPath,self::$requestPaymentInfo,'支付请求 —— 微信（H5）'); //写入日志记录
            $WeChat = yanSongDa::wechat(self::$payConfig)->wap($order);
            $url = $WeChat->getTargetUrl();
            $url = $url . '&notify_url=' . urlencode(self::$payNotify) . '&redirect_url=' . urlencode(self::$payReturn);

            return $url;
        } catch (Exception $e) {
            $emg = $e->getMessage();
            Commons::sRenderError($emg);
        }
    }
    /**
     * Comment: 微信小程序支付
     * Author: zzw
     * Date: 2019/11/14 17:44
     * @return array
     */
    protected static function wxApp_WeChat(){
        if(self::$orderInfo['blendcredit'] > 0){ //混合支付
            if(self::$blendflag > 0){
                self::$orderInfo['price'] = sprintf("%.2f",self::$orderInfo['price'] - self::$orderInfo['blendcredit']);
            }else{
                pdo_update('wlmerchant_paylogvfour',array('blendcredit' => 0),array('tid' => self::$orderInfo['orderno'],'plugin' => self::$orderInfo['plugin']));
            }
        }
        #1、配置订单信息
        $order = [
            'out_trade_no' => self::$source. '_' . self::$orderInfo['orderno'] ,
            'body'         => self::$goodsName ? : '小程序 — 微信支付' ,
            'total_fee'    => sprintf("%.0f",(self::$orderInfo['price'] * 100)) ,
        ];
        if(self::$orderInfo['paysetid'] > 0){
            $order['profit_sharing'] = 'Y';
        }
        if (self::$setting['shop_type'] == 2) {
            $order['sub_openid'] = self::$userInfo['wechat_openid'];
        } else {
            $order['openid'] = self::$userInfo['wechat_openid'];
        }
        //支付立减
        if(!empty(self::$payConfig['goods_tag'])){
            $order['goods_tag'] = self::$payConfig['goods_tag'];
        }
        if(!empty(self::$orderInfo['goods_tag'])){
            $order['goods_tag'] = self::$orderInfo['goods_tag'];
        }
        #2、调用支付接口 获取信息参数
        try {
            Util::wl_log('request_payment', self::$payLogPath,self::$requestPaymentInfo,'支付请求 —— 微信（小程序）'); //写入日志记录
            $WeChat = yanSongDa::wechat(self::$payConfig)->miniapp($order);
            return [
                'appId'     => $WeChat->appId ,
                'timeStamp' => $WeChat->timeStamp ,
                'nonceStr'  => $WeChat->nonceStr ,
                'package'   => $WeChat->package ,
                'signType'  => $WeChat->signType ,
                'paySign'   => $WeChat->paySign ,
            ];
        } catch (Exception $e) {
            $emg = $e->getMessage();
            Commons::sRenderError($emg);
        }
    }

    /****** 支付回调 ***********************************************************************************************/
    /**
     * Comment: 微信公众号 —— 支付宝支付回调
     * Author: zzw
     * Date: 2019/9/2 14:28
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public static function AliPay_notify(){
        #1、参数获取
        $data = $_POST;
        //判断是否为 支付回调
        if($data['trade_status'] == 'TRADE_SUCCESS') {
            Util::wl_log('alipay_notify' , self::$payLogPath , $data); //写入日志记录
            #2、获取订单号
            $orderNo = explode('_' , $data['out_trade_no'])[1];
            #3、获取通过订单号获取订单信息
            self::getOrder($orderNo);
            #4、初始化支付配置信息
            self::setInfo(3);
            self::aliPayConfig();
            #5、验签
            $Alipay = yanSongDa::alipay(self::$payConfig);
            #6、改变订单信息 支付方式：1=余额；2=微信；3=支付宝；4=货到付款
            $info = [
                'type'           => 3 ,//支付方式
                'tid'            => $orderNo ,//订单号
                'transaction_id' => $data['trade_no'] ,
                'time'           => strtotime($data['gmt_create']) ,
                'pay_order_no'   => $data['out_trade_no']
            ];
            PayResult::main($info);//调用方法处理订单
            #7、返回成功支付信息
            VoiceAnnouncements::PushVoiceMessage(self::$orderInfo['price'] , self::$orderInfo['sid'] , 3,self::$orderInfo['plugin']); //调用云喇叭进行商户收款播报


            return $Alipay->success()->send();
        }
    }
    /**
     * Comment: 微信公众号 —— 微信支付回调
     * Author: zzw
     * Date: 2019/9/27 9:30
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Yansongda\Pay\Exceptions\InvalidArgumentException
     */
    public static function WeChat_notify(){
        global $_W;
        #1、参数获取
        $xml = file_get_contents('php://input');
        $data = self::fromXml($xml);
        Util::wl_log('wechat_notify', self::$payLogPath, $data); //写入日志记录
        #2、获取订单号
        $orderNo = explode('_',$data['out_trade_no'])[1];
        $flag = pdo_getcolumn(PDO_NAME.'temporary_orderlist',array('orderno'=>$orderNo),'id');
        if(empty($flag)){
            $deteletime = time() + 30;
            pdo_insert(PDO_NAME . 'temporary_orderlist', ['orderno'=>$orderNo,'deteletime'=>$deteletime]);
            #3、获取通过订单号获取订单信息
            self::getOrder($orderNo);
            #4、初始化支付配置信息
            self::setInfo(2);
            self::weChatConfig();
            #5、验签
            $weChat = yanSongDa::wechat(self::$payConfig);
            //支付信息错误  下单用户和支付用户不是同一个用户
            $where = " where (openid = '{$data['openid']}' OR wechat_openid = '{$data['openid']}') ";
            $payMid = pdo_fetchcolumn("SELECT id FROM ".tablename(PDO_NAME."member").$where);
            if($payMid != self::$orderInfo['mid']){
                Util::wl_log('error', self::$payLogPath,[
                    'data'=>$data,
                    'paymid'=>$payMid,
                    'mid'=>self::$orderInfo
                ],'支付失败 —— 下的用户和支付用户不是同一账号'); //写入日志记录
            }
            #6、改变订单信息  支付方式：1=余额；2=微信；3=支付宝；4=货到付款
            $info = [
                'type'           => 2 ,//支付方式
                'tid'            => $orderNo ,//订单号
                'transaction_id' => $data['transaction_id'] ,
                'time'           => self::timeProcessing($data['time_end']),
                'pay_order_no'   => $data['out_trade_no'],
                'bank_type'      => $data['bank_type'],
            ];
            PayResult::main($info);//调用方法处理订单
            #7、返回成功支付信息
            VoiceAnnouncements::PushVoiceMessage(self::$orderInfo['price'], self::$orderInfo['sid'], 2,self::$orderInfo['plugin']); //调用云喇叭进行商户收款播报
        }


        #9、返回成功支付信息
        return $weChat->success()->send();
    }
    /**
     * Comment: 将xml转为array
     * Author: zzw
     * Date: 2019/5/10 9:06
     * @param $xml
     * @return mixed
     */
    protected static function fromXml($xml){
        // 禁止引用外部xml实体
        libxml_disable_entity_loader(true);
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
    /**
     * Comment: 时间解析 将没有间隔符的时间字符串转换为时间戳
     * Author: zzw
     * Date: 2019/9/27 9:28
     * @param $string
     * @return false|int
     */
    protected static function timeProcessing($string){
        global $_W,$_GPC;
        #1、字符串截取 分别获取时间信息
        $y = substr($string,0,4);
        $m = substr($string,4,2);
        $d = substr($string,6,2);
        $h = substr($string,8,2);
        $i = substr($string,10,2);
        $s = substr($string,12,2);
        #2、拼接字符串 获取信息
        return strtotime($y.'-'.$m.'-'.$d.' '.$h.":".$i.":".$s);
    }


    /****** 提现打款操作 ****************************************************************************************/
    /**
     * Comment: 提现打款操作
     * Author: zzw
     * Date: 2019/10/9 15:17
     * @param $info
     * @param $type int 1=微信打款，2=微信红包，3=支付宝转账
     * @return array|string
     */
    public static function presentationInit($info,$type){
        global $_W;
        self::$source = $info['source'] ? : 1;
        #1、获取打款设置信息
        self::$setting = Setting::wlsetting_read('cashset');
        $wechatapitype = self::$setting['wechatapitype'];

        #2、获取支付配置信息,并且调取打款方法。  1=微信打款，2=微信红包，3=支付宝转账
        switch ($type) {
            case 1:
                //支付设置信息获取
                if(self::$setting['wechat_payment'] <= 0 && $info['return'] != 1) show_json(0,'请先设置微信打款账号！');
                $paymentSet = pdo_get(PDO_NAME."payment",['id'=>self::$setting['wechat_payment']]);
                self::$setting = json_decode($paymentSet['param'],true);
                if(self::$source == 1 || self::$source == 2){
                    self::$setting['app_id'] = Util::object_array($_W['account'])['key'];//公众号、H5
                }else{
                    self::$setting['app_id'] = Setting::wlsetting_read('wxapp_config')['appid'];//小程序
                }
                self::weChatConfig();
                //调用方法进行打款
                if($wechatapitype > 0){
                    return self::NewWeChatPayment($info,$info['return']);
                }else{
                    return self::weChatPayment($info);
                }
                break;//微信打款
            case 2:
                //支付设置信息获取
                if(self::$setting['wechat_payment'] <= 0 && $info['return'] != 1) show_json(0,'请先设置微信打款账号！');
                $paymentSet = pdo_get(PDO_NAME."payment",['id'=>self::$setting['wechat_payment']]);
                self::$setting = json_decode($paymentSet['param'],true);
                if(self::$source == 1 || self::$source == 2){
                    self::$setting['app_id'] = Util::object_array($_W['account'])['key'];//公众号、H5
                }else{
                    self::$setting['app_id'] = Setting::wlsetting_read('wxapp_config')['appid'];//小程序
                }
                self::weChatConfig();
                //调用方法进行打款
                return self::weChatRedPack($info);
                break;//微信红包
            case 3:
                //支付设置信息获取
                if(self::$setting['alipay_payment'] <= 0 && $info['return'] != 1){
                    show_json(0,'请先设置支付宝打款账号！');
                }
                $paymentSet = pdo_get(PDO_NAME."payment",['id'=>self::$setting['alipay_payment']]);
                self::$setting = json_decode($paymentSet['param'],true);
                self::aliPayConfig();
                //调用方法进行打款
                return self::aliPayPayment($info);
                break;//支付宝转账
        }
    }

    private function getEncrypt($str,$public_key_path) {
        //$str是待加密字符串
        $public_key = file_get_contents($public_key_path);
        $encrypted = '';
        if (openssl_public_encrypt($str, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING)) {
            //base64编码
            $sign = base64_encode($encrypted);
        } else {
            throw new Exception('encrypt failed');
        }
        return $sign;
    }
    /**
     * Comment: 商户转账到零钱接口打款
     * Author: wlf
     * Date: 2022/06/06 10:05
     * @param $info
     * @return int
     */
    protected static function NewWeChatPayment($info,$reflag = 0){
        //数据处理
        $newmoney = sprintf("%.0f",($info['money'] * 100));
        $newmoney = intval($newmoney);


        //判断是否为子商户打款
        $filePath        = PATH_ATTACHMENT . "public_file/" . MODULE_NAME . "/";
        $cashset = Setting::wlsetting_read('cashset')['wechat_payment'];
        $cashset = 'ac'.$cashset;
        if (self::$payConfig['mode'] == 'service') {
            $payConfig                           = self::$payConfig;
            self::$payConfig['appid']            = $payConfig['sub_appid'];
            self::$payConfig['app_id']           = $payConfig['sub_app_id'];
            self::$payConfig['miniapp_id']       = $payConfig['sub_miniapp_id'];
            self::$payConfig['sub_appid']        = $payConfig['appid'];
            self::$payConfig['sub_app_id']       = $payConfig['app_id'];
            self::$payConfig['sub_miniapp_id']   = $payConfig['miniapp_id'];
            self::$payConfig['mch_id']           = $payConfig['sub_mch_id'];
            self::$payConfig['sub_mch_id']       = $payConfig['mch_id'];
            self::$payConfig['key']        = trim(self::$setting['sub_secret_key']);//打款时 修改支付密钥为子商户支付密钥
            self::$payConfig['cert_client'] = trim($filePath . self::$setting['sub_cert_certificate']);//打款时 修改支付密钥为子商户支付密钥
            self::$payConfig['cert_key']  = trim($filePath . self::$setting['sub_key_certificate']);//打款时 修改支付密钥为子商户支付密钥
        }

        $newinfo = [
            'appid'        => self::$payConfig['appid'],
            'out_batch_no' => $info['order_no'],
            'batch_name'   => $info['rem'],
            'batch_remark' => $info['rem'],
            'total_amount' => $newmoney,
            'total_num'    => 1,
            'transfer_detail_list' => []
        ];

        $detail_list =  [
            'out_detail_no' => time().random(4, true),
            'transfer_amount' => $newmoney,
            'transfer_remark' => '提现',
            'openid'          => $info['openid']
        ];

        // 商户号，假定为`1000100`
        $merchantId = self::$payConfig['mch_id'];
        // 商户私钥，文件路径假定为 `/path/to/merchant/apiclient_key.pem`
        $merchantPrivateKeyFilePath = self::$payConfig['cert_key'];
        // 加载商户私钥`

        try{
            $PemUtil = new \WeChatPay\Util\PemUtil();
            $merchantPrivateKeyInstance = $PemUtil::loadPrivateKey($merchantPrivateKeyFilePath);

            // // 商户证书，文件路径假定为 `/path/to/merchant/apiclient_cert.pem`
            $merchantCertificateFilePath = self::$payConfig['cert_client'];
            // // 加载商户证书\
            $merchantCertificateInstance = $PemUtil::loadCertificate($merchantCertificateFilePath);
            // // 解析商户证书序列号
            $merchantCertificateSerial = $PemUtil::parseCertificateSerialNo($merchantCertificateInstance);

            // 平台证书，可由下载器 `./bin/CertificateDownloader.php` 生成并假定保存为 `/path/to/wechatpay/cert.pem`
            $apiV3key = self::$payConfig['key'];
            $mchId = $merchantId;
            $mchPrivateKeyFilePath = $merchantPrivateKeyFilePath;
            $mchSerialNo = $merchantCertificateSerial;
            $outputFilePath = $filePath.$cashset;

            //$res = FilesHandle::file_mkdirs(dirname($outputFilePath));
            mkdir($outputFilePath);
            $CertificateDownloader = PATH_MODULE."vendor/wechatpay/wechatpay/bin/CertificateDownloader.php";

            $execstring = "php -f {$CertificateDownloader} -- -k {$apiV3key} -m {$mchId} -f {$mchPrivateKeyFilePath} -s {$mchSerialNo} -o {$outputFilePath}";
            exec($execstring);
            $certsfiles = scandir($outputFilePath);
            $platformCertificateFilePath = $outputFilePath.'/'.$certsfiles[2];

            if($newmoney > 199999){
                $realname =  pdo_getcolumn(PDO_NAME.'member',array('id'=>$info['mid']),'realname');
                if(!empty($realname)){
                    $detail_list['user_name'] = self::getEncrypt($realname,$platformCertificateFilePath);
                }
            }
            $newinfo['transfer_detail_list'][] = $detail_list;

            // 加载平台证书
            $platformCertificateInstance = $PemUtil::loadCertificate($platformCertificateFilePath);
            // 解析平台证书序列号
            $platformCertificateSerial = $PemUtil::parseCertificateSerialNo($platformCertificateInstance);
        }catch (Exception $e){
            $emg = $e->getMessage();
            if($reflag != 1){
                show_json(0,$emg);
            }else{
                file_put_contents(PATH_DATA . "autoCashError.log", var_export($emg, true) . PHP_EOL, FILE_APPEND);
            }
        }

        // 工厂方法构造一个实例
        $Builder = new \WeChatPay\Builder();

        $instance = $Builder::factory([
            'mchid'      => $merchantId,
            'serial'     => $merchantCertificateSerial,
            'privateKey' => $merchantPrivateKeyInstance,
            'certs'      => [
                $platformCertificateSerial => $platformCertificateInstance,
            ],
        ]);
        try{
            $res = $instance->chain('v3/transfer/batches')->post([
                'json'=>$newinfo,
                'headers' => ['Wechatpay-Serial' => $platformCertificateSerial]
            ]);
            return 1;
        }catch (Exception $e) {
            //var_dump($e->getResponse()->getBody()->getContents());
            $emg = $e->getResponse()->getBody()->getContents();
            if($reflag != 1){
                show_json(0,$emg);
            }else{
                file_put_contents(PATH_DATA . "autoCashError.log", var_export($emg, true) . PHP_EOL, FILE_APPEND);
            }
        }

    }


    /**
     * Comment: 微信打款
     * Author: zzw
     * Date: 2019/10/9 16:45
     * @param $info
     * @return int
     */
    protected static function weChatPayment($info){
        $data = [
            'partner_trade_no' => $info['order_no'] ,//商户订单号
            'openid'           => $info['openid'] ,//收款人的openid
            'check_name'       => 'NO_CHECK' ,//NO_CHECK：不校验真实姓名\FORCE_CHECK：强校验真实姓名
            //'re_user_name'     => $info['name'] ,//check_name为 FORCE_CHECK 校验实名的时候必须提交
            'amount'           => sprintf("%.0f",($info['money'] * 100)) ,//企业付款金额，单位为分
            'desc'             => $info['rem'] ,//付款说明
        ];
        //判断是否为小程序打款
        if($info['source'] == 3){
            if(empty($data['openid'])) $data['openid'] = pdo_getcolumn(PDO_NAME."member",['mid'=>$info['mid']],'wechat_openid');
            $data['type']   = 'miniapp';
        }
        unset(self::$payConfig['notify_url']);
        try {
            //判断是否为子商户打款
            if (self::$payConfig['mode'] == 'service') {
                $filePath        = PATH_ATTACHMENT . "public_file/" . MODULE_NAME . "/";
                $payConfig                           = self::$payConfig;
                self::$payConfig['appid']            = $payConfig['sub_appid'];
                self::$payConfig['app_id']           = $payConfig['sub_app_id'];
                self::$payConfig['miniapp_id']       = $payConfig['sub_miniapp_id'];
                self::$payConfig['sub_appid']        = $payConfig['appid'];
                self::$payConfig['sub_app_id']       = $payConfig['app_id'];
                self::$payConfig['sub_miniapp_id']   = $payConfig['miniapp_id'];
                self::$payConfig['mch_id']           = $payConfig['sub_mch_id'];
                self::$payConfig['sub_mch_id']       = $payConfig['mch_id'];
                self::$payConfig['key']        = trim(self::$setting['sub_secret_key']);//打款时 修改支付密钥为子商户支付密钥
                self::$payConfig['cert_client'] = trim($filePath . self::$setting['sub_cert_certificate']);//打款时 修改支付密钥为子商户支付密钥
                self::$payConfig['cert_key']  = trim($filePath . self::$setting['sub_key_certificate']);//打款时 修改支付密钥为子商户支付密钥
            }

            $weChat = YanSongDa::wechat(self::$payConfig);
            $res = $weChat->transfer($data);
            if($res->return_code == 'SUCCESS') return 1;
            else return 0;
        } catch (Exception $e) {
            $emg = $e->getMessage();
            if($info['return'] != 1){
                show_json(0,$emg);
            }else{
                file_put_contents(PATH_DATA . "autoCashError.log", var_export($emg, true) . PHP_EOL, FILE_APPEND);
            }
            //Commons::sRenderError($emg);
        }
    }
    /**
     * Comment: 微信红包
     * Author: zzw
     * Date: 2019/10/9 17:18
     * @param $info
     * @return int
     */
    protected static function weChatRedPack($info){
        global $_W;
        //参数配置
        $data = [
            'mch_billno'   => $info['order_no'] ,
            'send_name'    => $_W['wlsetting']['base']['name'] ,
            'total_amount' => sprintf("%.0f",($info['money'] * 100)) ,
            're_openid'    => $info['openid']  ,
            'total_num'    => '1' ,
            'wishing'      => '恭喜发财,大吉大利' ,
            'act_name'     => '红包提现' ,
            'remark'       => $info['rem'] ,
        ];
        if(empty($data['send_name'])){
            $base = Setting::wlsetting_read('base');
            $data['send_name'] = $base['name'];
        }
        //判断是否为小程序红包
        if($info['source'] == 3){
            $data['type'] = 'miniapp';
        }
        try {
            //判断是否为子商户红包
            if (self::$payConfig['mode'] == 'service') {
                $filePath        = PATH_ATTACHMENT . "public_file/" . MODULE_NAME . "/";
                $payConfig                           = self::$payConfig;
                self::$payConfig = [
                    'appid'          => trim($payConfig['sub_app_id']) , // APP APPID
                    'app_id'         => trim($payConfig['sub_app_id']) , // 公众号 APPID
                    'miniapp_id'     => trim($payConfig['sub_miniapp_id']) , // 小程序 APPID
                    'mch_id'         => trim($payConfig['sub_mch_id']) ,
                    'key'            => trim(self::$setting['sub_secret_key']) ,
                    'cert_client'    => trim($filePath . self::$setting['sub_cert_certificate']) , // optional, 退款，红包等情况时需要用到
                    'cert_key'       => trim($filePath . self::$setting['sub_key_certificate']) ,// optional, 退款，红包等情况时需要用到
                ];
            }
            //调用接口  开始发送红包
            $res = YanSongDa::wechat(self::$payConfig)->redpack($data);
            if($res->return_code == 'SUCCESS') return 1;
            else return 0;
        } catch (Exception $e) {
            $emg = $e->getMessage();
            if($info['return'] != 1){
                show_json(0,$emg);
            }
        }
    }
    /**
     * Comment: 支付宝打款
     * Author: zzw
     * Date: 2019/10/9 17:37
     * @param $info
     * @return int
     */
    protected static function aliPayPayment($info){
        if(self::$payConfig['apiversion'] > 0){
            $data = [
                'out_biz_no'    => $info['order_no'] ,
                'product_code'  => 'TRANS_ACCOUNT_NO_PWD' ,
                'trans_amount'  => $info['money'] ,
                'biz_scene'     => 'DIRECT_TRANSFER',
                'payee_info' => [
                    'identity' => $info['phone'],
                    'identity_type' => 'ALIPAY_LOGON_ID',
                    'name'     => $info['realname']
                ],
            ];
            $aop = new AopCertClient();
            self::$payConfig['app_cert_sn'] = $aop->getCertSN(self::$payConfig['publicKey']);
            self::$payConfig['alipay_root_cert_sn'] = $aop->getRootCertSN(self::$payConfig['rootCert']);
            $data['version'] = 1;
        }else{
            $data = [
                'out_biz_no'    => $info['order_no'] ,
                'payee_type'    => 'ALIPAY_LOGONID' ,
                'payee_account' => $info['phone'] ,
                'amount'        => $info['money'] ,
            ];
            if(!empty($info['realname'])){
                $data['payee_real_name'] = $info['realname'];
            }
            $data['version'] = 0;
        }
        unset(self::$payConfig['notify_url']);
        unset(self::$payConfig['return_url']);
        unset(self::$payConfig['publicKey']);
        unset(self::$payConfig['rootCert']);
        unset(self::$payConfig['apiversion']);
        try {
            @$res = YanSongDa::alipay(self::$payConfig)->transfer($data);
            if($res) return 1;
            else return 0;
        } catch (Exception $e) {
            $emg = $e->getMessage();
            //忽略签名错误
            if($emg == 'INVALID_SIGN: Alipay Sign Verify FAILED'){
                return 1;
            }else{
                if($info['return'] != 1){
                    show_json(0,$emg);
                }else{
                    file_put_contents(PATH_DATA . "autoCashError.log", var_export($emg, true) . PHP_EOL, FILE_APPEND);
                }
            }
        }
    }


    /****** 支付相关的公共方法 ****************************************************************************************/
    /**
     * Comment: 获取余额支付 允许限制的模块信息
     * Author: zzw
     * Date: 2020/4/9 15:23
     * @return array
     */
    public static function getBalanceModel(){
        $info = [
            'Rush'         => '抢购商品' ,
            'wlCoupon'     => '卡券购买' ,
            'Charge'       => '商户入驻' ,
        ];
        if(uniacid_p('attestation')){
            $info['Attestation'] = '认证支付';
        }
        if(uniacid_p('bargain')){
            $info['Bargain'] = '砍价商品';
        }
        if(uniacid_p('citycard')){
            $info['Citycard'] = '名片支付';
        }
        if(uniacid_p('consumption')){
            $info['Consumption'] = '积分商品';
        }
        if(uniacid_p('distribution')){
            $info['Distribution'] = '分销支付';
        }
        if(uniacid_p('groupon')){
            $info['Groupon'] = '团购商品';
        }
        if(uniacid_p('pocket')){
            $info['Pocket'] = '掌上信息';
        }
        if(uniacid_p('wlfightgroup')){
            $info['Wlfightgroup'] = '拼团商品';
        }
        if(uniacid_p('halfcard')){
            $info['Halfcard'] = '一卡通支付';
        }
        if(uniacid_p('citydelivery')){
            $info['Citydelivery'] = '同城配送';
        }
        if(uniacid_p('yellowpage')){
            $info['Yellowpage'] = '黄页114';
        }
        if(uniacid_p('housekeep')){
            $info['Housekeep'] = '家政服务';
        }
        if(uniacid_p('dating')){
            $info['Dating'] = '相亲交友';
        }

        return $info;
    }
    /**
     * Comment: 判断下单用户和支付用户是否为同一个用户
     * Author: zzw
     * Date: 2020/4/14 17:10
     * @param $member
     * @param $info
     * @return int
     */
    public static function isUserOrder($member,$info){
        //支付信息错误  下单用户和支付用户不是同一个用户
        $where = " where (openid = '{$member['openid']}' OR wechat_openid = '{$member['openid']}') ";
        $payMid = pdo_fetchcolumn("SELECT id FROM ".tablename(PDO_NAME."member").$where);
        if($payMid != self::$orderInfo['mid']){
            Util::wl_log('error', self::$payLogPath,$info,'支付失败 —— 下的用户和支付用户不是同一账号'); //写入日志记录
            return true;
        }
        return false;
    }


    /****** 抽奖后现金红包打款 ****************************************************************************************/
    /**
     * Comment: 现金红包打款操作
     * Author: zzw
     * Date: 2020/9/21 11:27
     * @param $info
     * @return array
     * @throws Exception
     */
    public function cashRedPack($info){
        global $_W;
        self::$source = $info['source'] ? : 1;
        //获取打款设置信息
        self::$setting = Setting::wlsetting_read('cashset');
        //获取支付配置信息,并且调取打款方法
        if (self::$setting['wechat_payment'] <= 0) throw new Exception('未设置打款账号');
        $paymentSet = pdo_get(PDO_NAME."payment",['id'=>self::$setting['wechat_payment']]);
        self::$setting = json_decode($paymentSet['param'],true);
        if(self::$source == 1 || self::$source == 2) self::$setting['app_id'] = Util::object_array($_W['account'])['key'];//公众号、H5
        else self::$setting['app_id'] = Setting::wlsetting_read('wxapp_config')['appid'];//小程序
        self::weChatConfig();
        if($info['actype'] == 1){
            $actext = '分享有礼';
        }else if($info['actype'] == 2){
        	$actext = '商户红包';
        }else{
            $actext = '抽奖';
        }
        //进行打款
        $member = $_W['wlmember'];
        $data = [
            'mch_billno'   => $info['order_no'] ,
            'send_name'    => $_W['wlsetting']['base']['name'] ,
            'total_amount' => sprintf("%.0f",($info['prize_number'] * 100)) ,
            're_openid'    => self::$source == 3 ? $member['wechat_openid'] : $member['openid']  ,
            'total_num'    => '1' ,
            'wishing'      => '恭喜发财,大吉大利' ,
            'act_name'     => '现金红包' ,
            'remark'       => $actext."活动【{$info['draw_title']}】奖品【{$info['title']}】" ,
            'scene_id'     => 'PRODUCT_1'
        ];
        //判断是否为小程序红包
        if($info['source'] == 3) $data['type'] = 'miniapp';
        unset(self::$payConfig['notify_url']);
        unset(self::$payConfig['goods_tag']);
        try {
            //判断是否为子商户红包
            if (self::$payConfig['mode'] == 'service') {
                $filePath        = PATH_ATTACHMENT . "public_file/" . MODULE_NAME . "/";
                $payConfig                           = self::$payConfig;
                self::$payConfig = [
                    'appid'          => trim($payConfig['sub_app_id']) , // APP APPID
                    'app_id'         => trim($payConfig['sub_app_id']) , // 公众号 APPID
                    'miniapp_id'     => trim($payConfig['sub_miniapp_id']) , // 小程序 APPID
                    'mch_id'         => trim($payConfig['sub_mch_id']) ,
                    'key'            => trim(self::$setting['sub_secret_key']) ,
                    'cert_client'    => trim($filePath . self::$setting['sub_cert_certificate']) , // optional, 退款，红包等情况时需要用到
                    'cert_key'       => trim($filePath . self::$setting['sub_key_certificate']) ,// optional, 退款，红包等情况时需要用到
                ];
            }
            $res = YanSongDa::wechat(self::$payConfig)->redpack($data);
            if($res->return_code == 'SUCCESS') return error(1,'打款成功');
            else throw new Exception('打款失败');
        } catch (Exception $e) {
            $error = $e->getMessage();
            throw new Exception($error);
        }
    }

    /**
     * Comment: 微信公众号 —— 云收单微信支付
     * Author: wlf
     * Date: 2021/07/22 16:09
     * @return array
     */
    protected static function Yun_WeChat_WeChat(){
        $wechatUrl = 'https://epos.ahrcu.com:3443/cposp/pay/unifiedorder';
        $wechatData = self::$payConfig;
        $wechatData['sign'] = self::getYunSign();
        $wechatData = json_encode($wechatData);
        $requestPay = curlPostRequest($wechatUrl,$wechatData,["Content-type: application/json;charset='utf-8'"]);
        if($requestPay['resultCode'] != '00'){ //报错写日志
            Util::wl_log('160PayApi.log',PATH_DATA,$requestPay); //写入异步日志记录
            show_json(0,$requestPay['resultMessage']);
        }else{
            pdo_update('wlmerchant_paylogvfour',array('transaction_id' => $requestPay['itpOrderId']),array('plid' => self::$orderInfo['plid']));
            $newRequestPay = [];
            $payInfo = explode(';',$requestPay['payInfo']);
            foreach($payInfo as $sinfo){
                $newar = [];
                $start = strpos($sinfo,'=');
                $newar[] = substr($sinfo,0,$start);
                $newar[] = substr($sinfo,$start+1);
                if($newar[0] == 'appid'){
                    $newRequestPay['appId'] = $newar[1];
                }else if($newar[0] == 'body'){
                    $newRequestPay['package'] = $newar[1];
                }else{
                    $newRequestPay[$newar[0]] = $newar[1];
                }
            }
        }
        return $newRequestPay;
    }

    /**
     * Comment: 云收单签到接口
     * Author: wlf
     * Date: 2021/07/22 15:15
     * @return array
     */
    public function yunSignIn($yun_merchantNo,$yun_terminalNo){
        //查询当天数据
        $today = strtotime(date("Y-m-d"),time());
        $signInfo = pdo_get('wlmerchant_yunsigninlist',array('merchantno' => $yun_merchantNo,'createtime >' =>$today));
        if(empty($signInfo)){
            //签到
            $signurl = 'https://epos.ahrcu.com:3443/cposp/pay/signIn';
            $signdata['merchantNo'] = $yun_merchantNo;
            $signdata['terminalNo'] = $yun_terminalNo;
            $signdata = json_encode($signdata);
            $getSignInfo = curlPostRequest($signurl,$signdata,["Content-type: application/json;charset='utf-8'"]);
            if($getSignInfo['resultCode'] != '00'){ //报错写日志
                Util::wl_log('160PayApi.log',PATH_DATA,$getSignInfo); //写入异步日志记录
                show_json(0,$getSignInfo['resultMessage']);
            }else{
                $serinfo = serialize($getSignInfo);
                $inserData = [
                    'merchantno' => $yun_merchantNo,
                    'batchNo' => $getSignInfo['batchNo'],
                    'traceNo' => '000001',
                    'createtime' => time(),
                    'serinfo' => $serinfo
                ];
                pdo_insert(PDO_NAME . 'yunsigninlist', $inserData);
            }
            $nweSignInfo = [
                'batchNo' => $getSignInfo['batchNo'],
                'traceNo' => '000001',
            ];
        }else{
            $nweSignInfo['batchNo'] = $signInfo['batchNo'];
            $traceNo = $signInfo['traceNo'];
            $traceNo = ltrim($traceNo,'0');
            $traceNo = $traceNo + rand(1,3);
            $traceNo = sprintf("%06d",$traceNo);
            pdo_update(PDO_NAME . 'yunsigninlist',array('traceNo' => $traceNo),array('id' => $signInfo['id']));
            $nweSignInfo['traceNo'] = $traceNo;
        }
        return $nweSignInfo;
    }

    /**
     * Comment: 云收单签名接口
     * Author: wlf
     * Date: 2021/07/22 16:14
     * @return array
     */
    public function getYunSign($data = '',$yunkey = ''){
        if(empty($data)){
            $data = self::$payConfig;
        }
        if(empty($yunkey)){
            $yunkey = self::$setting['yun_KEY'];
        }
        ksort($data);
        $signatureSt = '';
        foreach($data as $key => $dd){
            $signatureSt .= $key.'='.$dd.'&';
        }
        $signatureSt = $signatureSt.'key='.$yunkey;

        $signature = md5($signatureSt);
        $signature = strtoupper($signature);
        return $signature;
    }

    /**
     * Comment: 小程序支付管理创建订单
     * Author: wlf
     * Date: 2022/08/23 10:58
     * @return array
     */
    public function shopCreateorder($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/pay/createorder?access_token='.$access_token;
        $goods = json_encode($data,256);
        $res = curlPostRequest($url,$goods);
        return $res;
    }





}
