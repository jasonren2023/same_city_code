<?php
/**
 * 订单退款模型/商户提现打款模型/代理商提现打款模型
 */
defined('IN_IA') or exit('Access Denied');
use Yansongda\Pay\Pay as YanSongDa;
class Refund{
    protected static $orderNo ,//订单号
        $orderInfo ,//订单信息
        $source ,//渠道信息：1=公众号（默认）；2=h5；3=小程序
        $type ,//支付方式：1=余额；2=微信；3=支付宝；4=货到付款
        $key ,//由渠道信息和支付方式拼接获得
        $settingInfo ,//支付设置信息
        $configInfo ,//支付配置信息
        $payNotify ,//异步通知地址
        $payReturn ,//跳转地址
        $refundPrice ,//退款的金额
        $blendcredit ,//退款的余额
        $payLogPath = PATH_MODULE."payment/",//支付异步通知日志地址
        $dd;

    /**
     * Comment: 申请退款并且进行信息的初始化
     * Author: zzw
     * Date: 2019/9/28 17:36
     * @param     $order_no
     * @param int $price
     * @return array|bool|string
     */
    public static function refundInit($order_no,$price = -1,$blendcredit = 0){
        self::$orderNo = $order_no;
        self::$refundPrice = $price;
        self::$blendcredit = $blendcredit;
        #1、订单信息获取
        $res = self::getOrderInfo();
        if ($res) return $res;
        #1、拼接渠道信息和支付方式  获得key信息   1_credit
        //渠道信息：1=公众号（默认）；2=h5；3=小程序
        //支付方式：1=余额；2=微信；3=支付宝；4=货到付款 5=云收单
        if($price < 0.01 && $blendcredit > 0){
            self::$refundPrice = $blendcredit;
            self::$orderInfo['type'] = 1;
        }
        self::$key = self::$orderInfo['source'].'_'.self::$orderInfo['type'];
        #1、支付设置信息获取
        self::getSettingInfo();
        #1、调用对应的退款接口
        switch (self::$key){
            case '1_1': case '2_1': case '3_1':
                $res = self::balance();
                break;//余额退款（所有渠道）
            case '1_2': case '2_2':case '3_2':
                //self::$payNotify = PAY_PATH."WeChatRefund.php";
                //self::$payReturn = '';
                self::weChatConfig();
                $res = self::weChat_weChat();
                if(self::$blendcredit > 0 && $res['error'] > 0){
                    Member::credit_update_credit2(self::$orderInfo['mid'],self::$blendcredit,'订单['.self::$orderNo.']退款');
                }
                break;//公众号 - 微信退款 || H5 - 微信退款
            case '1_3': case '2_3':
                self::$payNotify = PAY_PATH."AlipayRefund.php";
                self::$payReturn = '';
                self::aliPayConfig();
                $res = self::weChat_aliPay();
                break;//公众号 - 支付宝退款 || H5 - 支付宝退款
            case '1_5':
                self::yunWeChatConfig();
                $res = self::Yun_WeChat_WeChat();
                break;//公众号 - 云收单
            case '3_5':
                $openid = pdo_getcolumn(PDO_NAME.'member',array('id'=>self::$orderInfo['mid']),'wechat_openid');
                $data = [
                    'openid' => $openid,
                    'mchid' => self::$settingInfo['mchid'],
                    'trade_no' => self::$orderInfo['tid'],
                    'transaction_id' => self::$orderInfo['transaction_id'],
                    'refund_no' => 'R'.self::$orderInfo['tid'],
                    'total_amount' => intval(self::$orderInfo['fee'] * 100),
                    'refund_amount' => intval(self::$refundPrice * 100)
                ];
                $res = self::shopRefundorder($data);
                break;//小程序支付管理
        }

        return $res;
    }

    /****** 退款接口 ******************************************************************************************************/
    /**
     * Comment: 余额退款
     * Author: zzw
     * Date: 2019/9/28 16:07
     * @return array|bool
     */
    protected static function balance(){
        #2、余额退款，直接为用户添加余额即可
        Member::credit_update_credit2(self::$orderInfo['mid'],self::$refundPrice,'订单['.self::$orderNo.']退款');
        return ['error'=>1];
    }
    /**
     * Comment: 微信公众号|H5 - 微信退款
     * Author: zzw
     * Date: 2019/9/28 17:25
     * @return array
     */
    protected static function weChat_weChat(){
        #1、配置订单信息
        $order = [
            'out_trade_no'  => self::$orderInfo['pay_order_no'] ,
            'out_refund_no' => 'R'.rand(0,9).self::$orderNo ,
            'total_fee'     => sprintf("%.0f",self::$orderInfo['fee'] * 100),
            'refund_fee'    => sprintf("%.0f",self::$refundPrice * 100),
            'refund_desc'   => '订单['.self::$orderNo.']退款' ,
        ];
        #2、调用接口进行退款操作
        try {
            yanSongDa::wechat(self::$configInfo)->refund($order);
            return ['error'=>1];
        } catch (Exception $e) {
            $emg = $e->getMessage();
            return ['error'=>0,'msg'=>$emg];
        }
    }
    /**
     * Comment: 微信公众号|H5 - 支付宝退款
     * Author: zzw
     * Date: 2019/9/28 17:35
     * @return array
     */
    protected static function weChat_aliPay(){
        #1、获取支付宝订单信息
        $order = [
            'out_trade_no'   => self::$orderInfo['pay_order_no'],
            'refund_amount'  => self::$refundPrice,
            'out_request_no' => rand(100,999).time(),
        ];
        #2、调用接口进行退款操作
        try {
            yanSongDa::alipay(self::$configInfo)->refund($order);
            return ['error'=>1];
        } catch (Exception $e) {
            $emg = $e->getMessage();
            //忽略签名错误
            if($emg == 'INVALID_SIGN: Alipay Sign Verify FAILED'){
                return ['error'=>1];
            }else{
                return ['error'=>0,'msg'=>$emg];
            }
        }

    }

    /****** 配置信息获取 **************************************************************************************************/
    /**
     * Comment: 支付宝配置信息获取
     * Author: zzw
     * Date: 2019/8/29 10:30
     * @return array
     */
    protected static function aliPayConfig(){
        self::$configInfo = [
            'app_id' => trim(self::$settingInfo['ali_app_id']),
            'notify_url' => trim(self::$payNotify),
            'return_url' => trim(self::$payReturn),
            'ali_public_key' => trim(self::$settingInfo['ali_public_key']),
            'private_key' => trim(self::$settingInfo['app_private_key']),
        ];
    }
    /**
     * Comment: 微信配置信息获取
     * Author: zzw
     * Date: 2019/9/2 15:00
     */
    protected static function weChatConfig(){
        $filePath        = PATH_ATTACHMENT . "public_file/" . MODULE_NAME . "/";
        if(self::$settingInfo['shop_type'] == 2){
            //子商户支付
            self::$configInfo = [
                'appid'          => trim(self::$settingInfo['sub_up_app_id']) , // APP APPID
                'app_id'         => trim(self::$settingInfo['sub_up_app_id']) , // 公众号 APPID
                'miniapp_id'     => trim(self::$settingInfo['sub_up_app_id']) , // 小程序 APPID
                'sub_appid'      => trim(self::$settingInfo['app_id']) , // 子商户 APP APPID
                'sub_app_id'     => trim(self::$settingInfo['app_id']) , // 子商户 公众号 APPID
                'sub_miniapp_id' => trim(self::$settingInfo['app_id']) , // 子商户 小程序 APPID
                'mch_id'         => trim(self::$settingInfo['shop_number']) ,
                'sub_mch_id'     => trim(self::$settingInfo['sub_shop_number']) , // 子商户商户号
                'key'            => trim(self::$settingInfo['secret_key']) ,//secret_key     sub_secret_key
                'notify_url'     => trim(self::$payNotify) ,
                'cert_client'    => trim($filePath . self::$settingInfo['cert_certificate']) , // optional, 退款，红包等情况时需要用到
                'cert_key'       => trim($filePath . self::$settingInfo['key_certificate']) ,// optional, 退款，红包等情况时需要用到
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
            self::$configInfo = [
                'appid'          => trim(self::$settingInfo['app_id']) , // APP APPID
                'app_id'         => trim(self::$settingInfo['app_id']) , // 公众号 APPID
                'miniapp_id'     => trim(self::$settingInfo['app_id']) , // 小程序 APPID
                'mch_id'         => trim(self::$settingInfo['shop_number']) ,
                'key'            => trim(self::$settingInfo['secret_key']) ,
                'notify_url'     => trim(self::$payNotify) ,
                'cert_client'    => trim($filePath . self::$settingInfo['cert_certificate']) , // optional, 退款，红包等情况时需要用到
                'cert_key'       => trim($filePath . self::$settingInfo['key_certificate']) ,// optional, 退款，红包等情况时需要用到
            ];
        }
    }

    protected static function yunWeChatConfig(){
        //云收单
        $yun_signIn = Payment::yunSignIn(self::$settingInfo['yun_merchantNo'],self::$settingInfo['yun_terminalNo']);
        self::$configInfo = [
            'itpOrderId' => self::$orderInfo['transaction_id'],
            'merchantNo' => self::$settingInfo['yun_merchantNo'],
            'terminalNo' => self::$settingInfo['yun_terminalNo'],
            'batchNo' => $yun_signIn['batchNo'],
            'traceNo' => $yun_signIn['traceNo'],
            'mchtRefundNo' => 'R'.rand(0,9).self::$orderNo,
            'refundAmount' => sprintf("%.0f",self::$refundPrice * 100),
            'nonceStr' => random(16)
        ];
    }



    /****** 公共方法 ******************************************************************************************************/
    /**
     * Comment: 获取订单信息
     * Author: zzw
     * Date: 2019/9/28 15:06
     */
    protected static function getOrderInfo(){
        #1、获取订单信息
        self::$orderInfo = pdo_get(PDO_NAME."paylogvfour",['tid'=>self::$orderNo]
            ,['transaction_id','source','mid','tid','fee','status','plugin','type','pay_order_no','blendcredit','batchNo','traceNo']);
        #2、判断当前订单是否符合退款操作
        if(!self::$orderInfo){
            self::$orderInfo = pdo_get(PDO_NAME."paylog",['tid'=>self::$orderNo],['transaction_id','source','mid','tid','fee','status','plugin','type','pay_order_no']);
        }
        if(!self::$orderInfo) return array('error'=>0,'msg'=>'订单不存在');
        if(self::$orderInfo['status'] == 0) return array('error'=>0,'msg'=>'订单未支付，不可退款');
        if(self::$orderInfo['plugin'] == 'Rush'){
            self::$orderInfo['sid'] = pdo_getcolumn(PDO_NAME.'rush_order',array('orderno' => self::$orderNo ),'sid');
        }else {
            self::$orderInfo['sid'] = pdo_getcolumn(PDO_NAME.'order',array('orderno' => self::$orderNo ),'sid');
        }
        #3、同订单信息获取其他基本信息
        self::$source = self::$orderInfo['source'];
        self::$type   = self::$orderInfo['type'];
        if(self::$orderInfo['blendcredit'] > 0){
            self::$orderInfo['fee'] = sprintf("%.2f",self::$orderInfo['fee'] - self::$orderInfo['blendcredit']);
        }
        if(self::$refundPrice <= 0) self::$refundPrice = self::$orderInfo['fee'];
        //判断云收单
        if(!empty(self::$orderInfo['batchNo'])){
            self::$orderInfo['type'] = 5;
        }
    }
    /**
     * Comment: 获取当前订单支付方式对应的支付设置信息
     * Author: zzw
     * Date: 2019/9/28 15:40
     */
    protected static function getSettingInfo(){
        global $_W;
        #1、通过key值获取
        $set = Setting::wlsetting_read("payment_set");
        $cashset = Setting::wlsetting_read("cashset");
        switch (self::$key){
            case '1_2':
                if($cashset['allocationtype'] == 1){
                    $id = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>self::$orderInfo['sid']),'wxallid');
                }
                if(empty($id)){
                    $id = $set['wechat']['wechat'];
                }
                break;//公众号 - 微信支付配置信息
            case '1_3':
                $id = $set['wechat']['alipay'];
                break;//公众号 - 支付宝配置信息
            case '2_2':
                $id = $set['h5']['wechat'];
                break;//H5 - 微信支付配置信息
            case '2_3':
                $id = $set['h5']['alipay'];
                break;//H5 - 支付宝配置信息
            case '3_2':
                if($cashset['allocationtype'] == 1){
                    $id = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>self::$orderInfo['sid']),'appallid');
                }
                if(empty($id)){
                    $id = $set['wxapp']['wechat'];
                }
                break;//小程序 - 微信支付
            case '1_5':
                $id = $set['wechat']['yunpay'];
                break;//公众号 - 云收单
            case '3_5':
                self::$settingInfo = $set['wxapp'];
                break;//小程序支付管理
        }
        #2、获取设置信息数据
        if($id > 0){
            self::$settingInfo = json_decode(pdo_getcolumn(PDO_NAME."payment",['id'=>$id],'param'),true);
        }
        #3、获取对应渠道的appid
        switch (self::$type){
            case 2:
                if(self::$source == 1){
                    $account = $_W['account'] ? : uni_fetch($_W['uniacid']);
                    self::$settingInfo['app_id'] = Util::object_array($account)['key'];//公众号、H5
                }else{
                    self::$settingInfo['app_id'] = Setting::wlsetting_read('wxapp_config')['appid'];//小程序
                }
                break;//微信
        }
    }


    /**
     * Comment: 微信公众号 —— 云收单微信支付
     * Author: wlf
     * Date: 2021/07/22 16:09
     * @return array
     */
    protected static function Yun_WeChat_WeChat(){
        $wechatUrl = 'https://epos.ahrcu.com:3443/cposp/pay/refund';
        $wechatData = self::$configInfo;
        $wechatData['sign'] = Payment::getYunSign($wechatData,self::$settingInfo['yun_KEY']);
        $wechatData = json_encode($wechatData);
        $refundInfo = curlPostRequest($wechatUrl,$wechatData,["Content-type: application/json;charset='utf-8'"]);
        if($refundInfo['refundStatus'] != '01'){
            Util::wl_log('160PayApi.log',PATH_DATA,$refundInfo); //写入异步日志记录
            return ['error'=>0,'msg'=>$refundInfo['errorDesc']];
        }else{
            return ['error'=>1];
        }
    }

    /**
     * Comment: 小程序支付管理退款订单
     * Author: wlf
     * Date: 2022/08/25 16:41
     * @return array
     */
    public function shopRefundorder($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/pay/refundorder?access_token='.$access_token;
        $goods = json_encode($data);
        $res = curlPostRequest($url,$goods);
        if($res['errcode'] != 0){
            Util::wl_log('160PayApi.log',PATH_DATA,$res); //写入异步日志记录
            return ['error'=>0,'msg'=>$res['errmsg']];
        }else{
            return ['error'=>1];
        }

    }

}
