<?php
defined('IN_IA') or exit('Access Denied');

class WeixinPay {

    //退款
    public function refund($arr) {
        global $_W;
        $setting = uni_setting($_W['uniacid'], array('payment'));

        $data['appid'] = $_W['account']['key'];
        $data['mch_id'] = $setting['payment']['wechat']['mchid'];

        $data['transaction_id'] = $arr['transid'];
        $data['out_refund_no'] = $arr['transid'] . rand(1000, 9999);

        $data['total_fee'] = $arr['totalmoney'] * 100;
        $data['refund_fee'] = $arr['refundmoney'] * 100;
        $data['op_user_id'] = $setting['payment']['wechat']['mchid'];
        $data['nonce_str'] = $this->createNoncestr();

        $data['sign'] = $this->getSign($data);

        if (empty($data['appid']) || empty($data['mch_id'])) {
            $rearr['return_msg'] = '请先在微擎的功能选项-支付参数内设置微信商户号和秘钥';
            return $rearr;
        }
        if ($data['total_fee'] > $data['refund_fee']) {
            $rearr['return_msg'] = '退款金额不能大于实际支付金额';
            return $rearr;
        }
        $xml = $this->arrayToXml($data);
        $url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
        $re = $this->wxHttpsRequestPem($xml, $url);
        $rearr = $this->xmlToArray($re);

        return $rearr;
    }

    //查询退款
    public function checkRefund($transid) {
        global $_W;
        $setting = uni_setting($_W['uniacid'], array('payment'));
        $data['appid'] = $_W['account']['key'];
        $data['mch_id'] = $setting['payment']['wechat']['mchid'];
        $data['transaction_id'] = $transid;
        $data['nonce_str'] = $this->createNoncestr();
        $data['sign'] = $this->getSign($data);

        if (empty($data['appid']) || empty($data['mch_id'])) {
            $rearr['return_msg'] = '请先在微擎的功能选项-支付参数内设置微信商户号和秘钥';
            return $rearr;
        }
        $xml = $this->arrayToXml($data);
        $url = "https://api.mch.weixin.qq.com/pay/refundquery";
        $re = $this->wxHttpsRequestPem($xml, $url);
        $rearr = $this->xmlToArray($re);

        return $rearr;
    }

    //企业付款
    public function finance($openid = '', $money = 0, $desc = '', $realname, $trade_no) {
        global $_W;
        $setting = uni_setting($_W['uniacid'], array('payment'));

        $refund_setting = $setting['payment']['wechat_refund'];
        if ($refund_setting['switch'] != 1) {
            return error(1, '未开启微信退款功能！');
        }
        if (empty($refund_setting['key']) || empty($refund_setting['cert'])) {
            return error(1, '缺少微信证书！');
        }
        $cert = authcode($refund_setting['cert'], 'DECODE');
        $key = authcode($refund_setting['key'], 'DECODE');
        file_put_contents(ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem', $cert . $key);

        $data = array();
        $data['mch_appid'] = $_W['account']['key'];
        $data['mchid'] = $setting['payment']['wechat']['mchid'];
        $data['nonce_str'] = $this->createNoncestr();;
        $data['partner_trade_no'] = $trade_no;
        $data['openid'] = $openid;
        if (!empty($realname)) {
            $data['re_user_name'] = $realname;
        }
        $data['check_name'] = 'NO_CHECK';
        $data['amount'] = $money * 100;
        $data['desc'] = empty($desc) ? '商家佣金提现' : $desc;
        $data['spbill_create_ip'] = gethostbyname($_SERVER["HTTP_HOST"]);
        $data['sign'] = $this->getSign($data);
        if (empty($data['mch_appid'])) {
            $rearr['return_msg'] = '请先在微擎的功能选项-支付参数内设置微信商户号和秘钥';
            return $rearr;
        }
        $xml = $this->arrayToXml($data);
        $url = "https://api.mch.weixin.qq.com/mmpaymkttransfers/promotion/transfers";
        $re = $this->wxHttpsRequestPem($xml, $url);
        $rearr = $this->xmlToArray($re);
        return $rearr;
    }

    public function createNoncestr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    function formatBizQueryParaMap($paraMap, $urlencode) {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar;
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

    public function getSign($Obj) {
        global $_W;
        $setting = uni_setting($_W['uniacid'], array('payment'));
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        $String = $String . "&key=" . $setting['payment']['wechat']['apikey'];
        $String = md5($String);
        $result_ = strtoupper($String);
        return $result_;
    }

    public function arrayToXml($arr) {
        $xml = "<xml>";
        foreach ($arr as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    public function xmlToArray($xml) {
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $array_data;
    }


    public function wxHttpsRequestPem($vars, $url, $second = 30, $aHeader = array()) {
        global $_W;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
        curl_setopt($ch, CURLOPT_SSLCERT, ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem');
        //	curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        //	curl_setopt($ch,CURLOPT_SSLKEY, PATH_DATA."cert/".$_W['uniacid']."/wechat/apiclient_key.pem");
//		curl_setopt($ch,CURLOPT_CAINFO,'PEM');
//		curl_setopt($ch,CURLOPT_CAINFO,IA_ROOT . '/attachment/feng_fightgroups/cert/' . $_W['uniacid'] . '/rootca.pem');
        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        $data = curl_exec($ch);
        unlink(ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem');
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }

    /**
     * Comment: 发送微信红包
     * Author: zzw
     */
    static public function sendingRedPackets($info) {
        global $_W;
        #1、验证签名证书
        $setting = uni_setting($_W['uniacid'], array('payment'));
        $refund_setting = $setting['payment']['wechat_refund'];
        if ($refund_setting['switch'] != 1) {
            return error(1, '未开启微信付款功能！');
        }
        if (empty($refund_setting['key']) || empty($refund_setting['cert'])) {
            return error(1, '缺少微信证书！');
        }
        $cert = authcode($refund_setting['cert'], 'DECODE');
        $key = authcode($refund_setting['key'], 'DECODE');
        file_put_contents(ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem', $cert . $key);
        #2、基本信息
        $payment = $setting['payment'];
        $cloud = Cloud::wl_syssetting_read('auth');
        $info = array(
            'nonce_str'    => random(32),//随机码
            'mch_billno'   => $info['mch_billno'],//订单号
            'mch_id'       => $payment['wechat']['mchid'],//商户id
            'wxappid'      => $_W['account']['key'],//appid
            'send_name'    => $_W['wlsetting']['base']['name'],//平台名称
            're_openid'    => $info['re_openid'],//收款人openid
            'total_amount' => $info['total_amount'],//发送金额 单位：分
            'total_num'    => 1,//发送数量
            'wishing'      => '恭喜发财,大吉大利',//留言
            'client_ip'    => $cloud['ip'],//发送主机ip
            'act_name'     => '红包提现',//$info['act_name'],//活动名称/商品名称
            'remark'       => $info['remark'],//备注信息
            'scene_id'     => 'PRODUCT_5'//使用场景 这里是渠道分润
        );
        #3、获取签名信息
        ksort($info);
        $sign = '';
        foreach ($info as $k => $v) {
            if (!empty($v)) {
                $sign .= $k . '=' . $v . '&';
            }
        }
        $sign .= "key=" . $setting['payment']['wechat']['apikey'];
        $info['sign'] = strtoupper(md5($sign));
        $pay = new WeixinPay();
        $xml = $pay->arrayToXml($info);
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/sendredpack';
        $re = $pay->wxHttpsRequestPem($xml, $url);
        $rearr = $pay->xmlToArray($re);
        return $rearr;
    }

    /**
     * Comment: 红包打款 查询接口
     * Author: zzw
     * @return array|mixed
     */
    static public function getRedPacketsInfo($info) {
        global $_W;
        #1、验证签名证书
        $setting = uni_setting($_W['uniacid'], array('payment'));
        $refund_setting = $setting['payment']['wechat_refund'];
        if ($refund_setting['switch'] != 1) {
            return error(1, '未开启微信付款功能！');
        }
        if (empty($refund_setting['key']) || empty($refund_setting['cert'])) {
            return error(1, '缺少微信证书！');
        }
        $cert = authcode($refund_setting['cert'], 'DECODE');
        $key = authcode($refund_setting['key'], 'DECODE');
        file_put_contents(ATTACHMENT_ROOT . $_W['uniacid'] . '_wechat_refund_all.pem', $cert . $key);
        #2、基本信息
        $payment = $setting['payment'];
        $info = array(
            'nonce_str'  => random(32),
            'mch_billno' => $info['mch_billno'],
            'mch_id'     => $payment['wechat']['mchid'],
            'appid'      => $_W['account']['key'],
            'bill_type'  => 'MCHT',
        );
        #3、获取签名信息
        ksort($info);
        $sign = '';
        foreach ($info as $k => $v) {
            if (!empty($v)) {
                $sign .= $k . '=' . $v . '&';
            }
        }
        $sign .= "key=" . $setting['payment']['wechat']['apikey'];
        $info['sign'] = strtoupper(md5($sign));
        $pay = new WeixinPay();
        $xml = $pay->arrayToXml($info);
        $url = 'https://api.mch.weixin.qq.com/mmpaymkttransfers/gethbinfo';
        $re = $pay->wxHttpsRequestPem($xml, $url);
        $rearr = $pay->xmlToArray($re);
        return $rearr;
    }

    /**
     * Comment: 获取平台分账信息
     * Author: wlf
     * Date: 2020/09/04 11:24
     */
    public function getSysAllInfo($price,$source,$set,$uniacid){
        global $_W;
        $_W['uniacid'] = $uniacid;
        $cashset = Setting::wlsetting_read('cashset');
        $payment_set = Setting::wlsetting_read('payment_set');
        if($source != 3 && $cashset['wxsyspercent'] > 0){
            $sysmoney = $price * $cashset['wxsyspercent'];
            if($sysmoney > 0){
                $paysetid = $payment_set['wechat']['wechat'];
//                if($_W['wlsetting']['cashset']['wxsysalltype'] == 1){
//                    //添加关系
//                    $res = self::addReceiver('MERCHANT_ID',$_W['wlsetting']['cashset']['wxmerchantid'],'SERVICE_PROVIDER',$_W['wlsetting']['cashset']['wxmerchantname'],$set);
//                    if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
//                        $data = [
//                            'type' => 'MERCHANT_ID',
//                            'account' => $_W['wlsetting']['cashset']['wxmerchantid'],
//                            'amount'  => $sysmoney,
//                            'description' => '平台抽佣',
//                        ];
//                    }else{
//                        file_put_contents(PATH_DATA . "allocation_error.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
//                    }
//                }else if($_W['wlsetting']['cashset']['wxsysalltype'] == 2){
//                    $cashopenid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$_W['wlsetting']['cashset']['wxallmid']),'openid');
//                    $res = self::addReceiver('PERSONAL_OPENID',$cashopenid,'SERVICE_PROVIDER','',$set);
//                    if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS') {
//                        $data = [
//                            'type' => 'PERSONAL_OPENID',
//                            'account' => $cashopenid,
//                            'amount'  => $sysmoney,
//                            'description' => '平台抽佣',
//                        ];
//                    }else{
//                        file_put_contents(PATH_DATA . "allocation_error.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
//                    }
//                }
            }
        }else if($source == 3 && $cashset['appsyspercent'] > 0){
            $sysmoney = $price * $cashset['appsyspercent'];
            if($sysmoney > 0){
                $paysetid = $payment_set['wxapp']['wechat'];
//                if($_W['wlsetting']['cashset']['appsysalltype'] == 1){
//                    $res = self::addReceiver('MERCHANT_ID',$_W['wlsetting']['cashset']['appmerchantid'],'SERVICE_PROVIDER',$_W['wlsetting']['cashset']['appmerchantname'],$set);
//                    if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS') {
//                        $data = [
//                            'type' => 'MERCHANT_ID',
//                            'account' => $_W['wlsetting']['cashset']['appmerchantid'],
//                            'amount'  => $sysmoney,
//                            'description' => '平台抽佣',
//                        ];
//                    }else{
//                        file_put_contents(PATH_DATA . "allocation_error.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
//                    }
//                }else if($_W['wlsetting']['cashset']['appsysalltype'] == 2){
//                    $cashopenid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$_W['wlsetting']['cashset']['appallmid']),'wechat_openid');
//                    $res = self::addReceiver('PERSONAL_OPENID',$cashopenid,'SERVICE_PROVIDER','',$set);
//                    if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS') {
//                        $data = [
//                            'type' => 'PERSONAL_OPENID',
//                            'account' => $cashopenid,
//                            'amount'  => $sysmoney,
//                            'description' => '平台抽佣',
//                        ];
//                    }else{
//                        file_put_contents(PATH_DATA . "allocation_error.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
//                    }
//                }
            }
        }
        if($paysetid > 0){
            $info = pdo_get(PDO_NAME."payment",['id'=>$paysetid]);
            $Setinfo = json_decode($info['param'],true);
            if($Setinfo['shop_type'] == 1){
                $shop_number = $Setinfo['shop_number'];
            }else{
                $shop_number = $Setinfo['sub_shop_number'];
            }
            $res = self::addReceiver('MERCHANT_ID',$shop_number,'PLATFORM',$Setinfo['merchantname'],$set);
            if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
                $data = [
                    'type' => 'MERCHANT_ID',
                    'account' => $shop_number,
                    'amount'  => intval($sysmoney),
                    'description' => '平台抽佣',
                ];
            }else{
                file_put_contents(PATH_DATA . "allocation_error.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
            }
        }
        return !empty($data) ? $data : 0;
    }

    /**
     * Comment: 获取分销分账信息
     * Author: wlf
     * Date: 2020/09/04 14:15
     */
    public function getDisAllInfo($disid,$disprice,$source,$set){
        global $_W;
        if($source == 1){
            $openid = pdo_getcolumn(PDO_NAME.'member',array('distributorid'=>$disid),'openid');
        }else{
            $openid = pdo_getcolumn(PDO_NAME.'member',array('distributorid'=>$disid),'wechat_openid');
        }
        if(!empty($openid)){
            $res = self::addReceiver('PERSONAL_OPENID',$openid,'DISTRIBUTOR','',$set);
            if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS') {
                $data = [
                    'type' => 'PERSONAL_OPENID',
                    'account' => $openid,
                    'amount'  => intval($disprice * 100),
                    'description' => '分佣',
                ];
            }else{
                file_put_contents(PATH_DATA . "allocation_error.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
            }
        }
        return !empty($data) ? $data : 0;
    }

    /**
     * Comment: 获取业务员分账信息
     * Author: wlf
     * Date: 2020/09/04 15:53
     */
    public function getSaleAllInfo($mid,$salesprice,$source,$set){
        global $_W;
        if($source == 1){
            $openid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$mid),'openid');
        }else{
            $openid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$mid),'wechat_openid');
        }
        if(!empty($openid)){
            $res = self::addReceiver('PERSONAL_OPENID',$openid,'STAFF','',$set);
            if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS') {
                $data = [
                    'type' => 'PERSONAL_OPENID',
                    'account' => $openid,
                    'amount'  => intval($salesprice * 100),
                    'description' => '业务员',
                ];
            }else{
                file_put_contents(PATH_DATA . "allocation_error.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
            }
        }
        return !empty($data) ? $data : 0;
    }

    /**
     * Comment: 获取代理分账信息
     * Author: wlf
     * Date: 2020/09/04 16:09
     */
    public function getAgentAllInfo($aid,$agentprice,$source,$set){
        global $_W;
        $agentset = pdo_get('wlmerchant_agentusers',array('id' => $aid),array('wxpaysetid','apppaysetid'));
        if($source == 1 || $source == 2){
            $paysetid = $agentset['wxpaysetid'];
//            if($agentset['wxsysalltype'] == 1){
//                //添加关系
//                $res = self::addReceiver('MERCHANT_ID',$agentset['wxmerchantid'],'PARTNER',$agentset['wxmerchantname'],$set);
//                if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
//                    $data = [
//                        'type' => 'MERCHANT_ID',
//                        'account' => $agentset['wxmerchantid'],
//                        'amount'  => $agentprice * 100,
//                        'description' => '代理抽佣',
//                    ];
//                }else{
//                    file_put_contents(PATH_DATA . "allocation_error.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
//                }
//            }else{
//                //添加关系
//                $cashopenid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$agentset['wxallmid']),'openid');
//                $res = self::addReceiver('PERSONAL_OPENID',$cashopenid,'PARTNER','',$set);
//                if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
//                    $data = [
//                        'type' => 'PERSONAL_OPENID',
//                        'account' => $cashopenid,
//                        'amount'  => $agentprice * 100,
//                        'description' => '代理抽佣',
//                    ];
//                }else{
//                    file_put_contents(PATH_DATA . "allocation_error.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
//                }
//            }
        }else if($source == 3){
            $paysetid = $agentset['apppaysetid'];
//            if($agentset['appsysalltype'] == 1){
//                //添加关系
//                $res = self::addReceiver('MERCHANT_ID',$agentset['appmerchantid'],'PARTNER',$agentset['appmerchantname'],$set);
//                if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
//                    $data = [
//                        'type' => 'MERCHANT_ID',
//                        'account' => $agentset['appmerchantid'],
//                        'amount'  => $agentprice * 100,
//                        'description' => '代理抽佣',
//                    ];
//                }else{
//                    file_put_contents(PATH_DATA . "allocation_error.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
//                }
//            }else{
//                //添加关系
//                $cashopenid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$agentset['appallmid']),'wechat_openid');
//                $res = self::addReceiver('PERSONAL_OPENID',$cashopenid,'PARTNER','',$set);
//                if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
//                    $data = [
//                        'type' => 'PERSONAL_OPENID',
//                        'account' => $cashopenid,
//                        'amount'  => $agentprice * 100,
//                        'description' => '代理抽佣',
//                    ];
//                }else{
//                    file_put_contents(PATH_DATA . "allocation_error.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
//                }
//            }
        }
        if($paysetid > 0){
            $info = pdo_get(PDO_NAME."payment",['id'=>$paysetid]);
            $Setinfo = json_decode($info['param'],true);
            if($Setinfo['shop_type'] == 1){
                $shop_number = $Setinfo['shop_number'];
            }else{
                $shop_number = $Setinfo['sub_shop_number'];
            }
            $res = self::addReceiver('MERCHANT_ID',$shop_number,'SERVICE_PROVIDER',$Setinfo['merchantname'],$set);
            if($res['return_code'] == 'SUCCESS' && $res['result_code'] == 'SUCCESS'){
                $data = [
                    'type' => 'MERCHANT_ID',
                    'account' => $shop_number,
                    'amount'  => intval($agentprice * 100),
                    'description' => '代理抽佣',
                ];
            }else{
                file_put_contents(PATH_DATA . "allocation_error.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
            }
        }
        return !empty($data) ? $data : 0;
    }

    /**
     * Comment: 服务商订单结算分账借口
     * Author: wlf
     * Date: 2020/09/08 14:10
     */
    public function allocationPro($orderid,$type,$source,$salesinfo = [],$salesmoney = 0){
        global $_W;
        $receivers = [];
        $sysmoney = $disonemoney = $distwomoney = $storemoney = 0;
        //获取订单数据
        switch ($type){
            case '1':
                $order = pdo_get(PDO_NAME.'rush_order',array('id'=>$orderid),array('sid','uniacid','settlementmoney','transid','aid','paysetid','orderno','actualprice','disorderid'));
                $price = $order['actualprice'];
                $storemoney = $order['settlementmoney'];
                break;
            case '4':
                $order = pdo_get(PDO_NAME.'halfcard_record',array('id'=>$orderid),array('transid','uniacid','aid','paysetid','orderno','price','disorderid'));
                $price = $order['price'];
                break;
            default :
                $order = pdo_get(PDO_NAME.'order',array('id'=>$orderid),array('sid','settlementmoney','transid','uniacid','plugin','aid','paysetid','orderno','price','disorderid'));
                $price = $order['price'];
                $plugin = $order['plugin'];
                $storemoney = $order['settlementmoney'];
                break;
        }
        if(empty($order['transid'])) {
            $order['transid'] = pdo_getcolumn(PDO_NAME . 'paylogvfour', array('tid' => $order['orderno']), 'transaction_id');
        }
        //基础信息
        $getUrl = "https://api.mch.weixin.qq.com/secapi/pay/profitsharing";
        $filePath        = PATH_ATTACHMENT . "public_file/" . MODULE_NAME . "/";
        $id = $order['paysetid'];
        $info = pdo_get(PDO_NAME."payment",['id'=>$id]);
        $setting = json_decode($info['param'],true);
        //获取平台分账信息
        $sysinfo = self::getSysAllInfo($price,$source,$setting,$order['uniacid']);
        if(!empty($sysinfo)){
            $receivers[] = $sysinfo;
            $sysmoney = sprintf("%.2f",$sysinfo['amount'] / 100);
        }
        //获取分销分账信息
        if($order['disorderid'] > 0){
            $disorder = pdo_get('wlmerchant_disorder',array('id' => $order['disorderid']),array('oneleadid','twoleadid','leadmoney'));
            $leadmoney = unserialize($disorder['leadmoney']);
            if($disorder['oneleadid'] > 0 && $leadmoney['one'] > 0){
                $onedisinfo = self::getDisAllInfo($disorder['oneleadid'],$leadmoney['one'],$source,$setting);
                if(!empty($sysinfo)){
                    $receivers[] = $onedisinfo;
                }
                $disonemoney = $leadmoney['one'];
            }
            if($disorder['twoleadid'] > 0 && $leadmoney['two'] > 0){
                $onedisinfo = self::getDisAllInfo($disorder['twoleadid'],$leadmoney['two'],$source,$setting);
                if(!empty($sysinfo)){
                    $receivers[] = $onedisinfo;
                }
                $distwomoney = $leadmoney['two'];
            }
        }
        //获取业务员信息
        if(!empty($salesinfo) && $salesmoney > 0){
            foreach($salesinfo as $sinfo){
                $saleallinfo = self::getSaleAllInfo($sinfo['mid'],$sinfo['reportmoney'],$source,$setting);
                if(!empty($saleallinfo)){
                    $receivers[] = $saleallinfo;
                }
            }
        }
        //获取代理分账信息
        if(!empty($order['sid']) || $plugin == 'store'){
            $marketstatus = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$order['sid']),'marketstatus');
            if($marketstatus > 0){
                $agentmoney = sprintf("%.2f",$price - $sysmoney);
            }else{
                $agentmoney = sprintf("%.2f",$price - $sysmoney - $disonemoney - $storemoney - $distwomoney - $salesmoney);
            }
            if($agentmoney > 0 && $order['aid'] > 0){
                $agentallinfo = self::getAgentAllInfo($order['aid'],$agentmoney,$source,$setting);
                if(!empty($agentallinfo)){
                    $receivers[] = $agentallinfo;
                }
            }
        }
        //生成分账方面
        if(count($receivers)>0){
            $receivers = json_encode($receivers);
            $data = [
                'mch_id' => $setting['shop_number'],
                'sub_mch_id' => $setting['sub_shop_number'],
                'appid' => $setting['sub_up_app_id'],
                'nonce_str' => $this -> createNoncestr(),
                'transaction_id' => $order['transid'],
                'out_order_no' => 'PP'.date('YmdHis').random(4,1),
                'receivers' => $receivers,
            ];
            $data['sign'] = $this->getWlfSign($data,$setting['secret_key']);

            $cert = trim($filePath . $setting['cert_certificate']);
            $key = trim($filePath . $setting['key_certificate']);

            $xml = $this->arrayToXml($data);
            $re = $this->wxWlfHttpsRequestPem($xml,$getUrl,30,[],$cert,$key);
            $rearr = $this->xmlToArray($re);
            if($rearr['return_code'] == 'SUCCESS' && $rearr['result_code'] == 'SUCCESS'){
                $result = [
                    'status' => 1,
                    'agentmoney' => $agentmoney > 0 ? $agentmoney : 0,
                    'sysmoney' => $sysmoney
                ];
                return $result;
            }else{
                file_put_contents(PATH_DATA . "allocation_error.log", var_export($rearr, true) . PHP_EOL, FILE_APPEND);
                return 0;
            }
        }else{
            //完成结算
            $data = [
                'mch_id' => $setting['shop_number'],
                'sub_mch_id' => $setting['sub_shop_number'],
                'appid' => $setting['sub_up_app_id'],
                'nonce_str' => $this -> createNoncestr(),
                'transaction_id' => $order['transid'],
                'out_order_no' => 'PP'.date('YmdHis').random(4,1),
                'description' => '分账已完成',
            ];
            $data['sign'] = $this->getWlfSign($data,$setting['secret_key']);
            $cert = trim($filePath . $setting['cert_certificate']);
            $key = trim($filePath . $setting['key_certificate']);

            $getUrl = 'https://api.mch.weixin.qq.com/secapi/pay/profitsharingfinish';
            $xml = $this->arrayToXml($data);
            $re = $this->wxWlfHttpsRequestPem($xml,$getUrl,30,[],$cert,$key);
            $rearr = $this->xmlToArray($re);
            if($rearr['return_code'] == 'SUCCESS' && $rearr['result_code'] == 'SUCCESS'){
                $result = [
                    'status' => 1,
                    'agentmoney' => $agentmoney > 0 ? $agentmoney : 0,
                    'sysmoney' => $sysmoney > 0 ? : 0
                ];
                return $result;
            }else{
                file_put_contents(PATH_DATA . "allocation_error.log", var_export($rearr, true) . PHP_EOL, FILE_APPEND);
                return 0;
            }
        }
    }

    /**
     * Comment: 服务商核销码分账借口
     * Author: wlf
     * Date: 2020/08/31 10:46
     */
    public function allocationMulti($orderid,$source,$salesinfo = [],$salesmoney = 0){   // $source = 1公众号 3小程序
        global $_W;
        //获取订单数据
        $receivers = [];
        $sysmoney = 0;
        $smallorder = pdo_get('wlmerchant_smallorder',array('id' => $orderid),array('aid','orderprice','plugin','orderid','settlemoney','sid','oneleadid','twoleadid','onedismoney','twodismoney'));
        $price = $smallorder['orderprice'];
        if($smallorder['plugin'] == 'rush'){
            $order = pdo_get('wlmerchant_rush_order',array('id' => $smallorder['orderid']),array('transid','uniacid','paysetid','orderno','allocationtype'));
        }else{
            $order = pdo_get('wlmerchant_order',array('id' => $smallorder['orderid']),array('transid','uniacid','paysetid','orderno','allocationtype'));
        }
        if(empty($order['transid'])) {
            $order['transid'] = pdo_getcolumn(PDO_NAME . 'paylogvfour', array('tid' => $order['orderno']), 'transaction_id');
        }
        //基础信息
        $getUrl = "https://api.mch.weixin.qq.com/secapi/pay/multiprofitsharing";
        $filePath        = PATH_ATTACHMENT . "public_file/" . MODULE_NAME . "/";
        $id = $order['paysetid'];
        $info = pdo_get(PDO_NAME."payment",['id'=>$id]);
        $setting = json_decode($info['param'],true);

        //获取平台分账信息
        $sysinfo = self::getSysAllInfo($price,$source,$setting,$order['uniacid']);

        if(!empty($sysinfo)){
            $receivers[] = $sysinfo;
            $sysmoney = sprintf("%.2f",$sysinfo['amount'] / 100);
        }
        //获取分销分账信息
        if($smallorder['oneleadid'] > 0 && $smallorder['onedismoney'] > 0){
            $onedisinfo = self::getDisAllInfo($smallorder['oneleadid'],$smallorder['onedismoney'],$source,$setting);
            if(!empty($sysinfo)){
                $receivers[] = $onedisinfo;
            }
        }
        if($smallorder['twoleadid'] > 0 && $smallorder['twodismoney'] > 0){
            $onedisinfo = self::getDisAllInfo($smallorder['twoleadid'],$smallorder['twodismoney'],$source,$setting);
            if(!empty($sysinfo)){
                $receivers[] = $onedisinfo;
            }
        }
        //获取业务员信息
        if(!empty($salesinfo) && $salesmoney > 0){
            foreach($salesinfo as $sinfo){
                $saleallinfo = self::getSaleAllInfo($sinfo['mid'],$sinfo['reportmoney'],$source,$setting);
                if(!empty($saleallinfo)){
                    $receivers[] = $saleallinfo;
                }
            }
        }
        //获取代理分账信息
        $marketstatus = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$smallorder['sid']),'marketstatus');
        if($marketstatus > 0){
            $agentmoney = sprintf("%.2f",$price - $sysmoney - $smallorder['settlemoney']);
        }else{
            $agentmoney = sprintf("%.2f",$price - $sysmoney - $smallorder['settlemoney'] - $smallorder['onedismoney'] - $smallorder['twodismoney'] - $salesmoney);
        }


        if($agentmoney > 0 && $smallorder['aid'] > 0){
            $agentallinfo = self::getAgentAllInfo($smallorder['aid'],$agentmoney,$source,$setting);
            if(!empty($agentallinfo)){
                $receivers[] = $agentallinfo;
            }
        }
        //生成分账方面
        if(count($receivers)>0){
            $receivers = json_encode($receivers);
            $data = [
                'mch_id' => $setting['shop_number'],
                'sub_mch_id' => $setting['sub_shop_number'],
                'appid' => $setting['sub_up_app_id'],
                'nonce_str' => $this -> createNoncestr(),
                'transaction_id' => $order['transid'],
                'out_order_no' => 'PP'.date('YmdHis').random(4,1),
                'receivers' => $receivers,
            ];
            $data['sign'] = $this->getWlfSign($data,$setting['secret_key']);

            $cert = trim($filePath . $setting['cert_certificate']);
            $key = trim($filePath . $setting['key_certificate']);

            $xml = $this->arrayToXml($data);
            $re = $this->wxWlfHttpsRequestPem($xml,$getUrl,30,[],$cert,$key);
            $rearr = $this->xmlToArray($re);
            if($rearr['return_code'] == 'SUCCESS' && $rearr['result_code'] == 'SUCCESS'){
                $result = [
                    'status' => 1,
                    'agentmoney' => $agentmoney > 0 ? $agentmoney : 0,
                    'sysmoney' => $sysmoney
                ];
                return $result;
            }else{
                file_put_contents(PATH_DATA . "allocation_error.log", var_export($rearr, true) . PHP_EOL, FILE_APPEND);
                return 0;
            }
        }else{
            $result = [
                'status' => 1,
                'agentmoney' => $agentmoney > 0 ? $agentmoney : 0,
                'sysmoney' => $sysmoney > 0 ? : 0
            ];
            return $result;
        }
    }

    /**
     * Comment: 添加分账方接口
     * Author: wlf
     * Date: 2020/08/31 15:27
     */
    public function addReceiver($type,$account,$relation_type,$name = '',$setting){
        global $_W;
        $getUrl = "https://api.mch.weixin.qq.com/pay/profitsharingaddreceiver";
        //生成分账方面
        $receiver = [
            'type'          => $type,
            'account'       => $account,
            'relation_type' => $relation_type,
        ];
        if($type == 'MERCHANT_ID'){
            $receiver['name'] = $name;
        }
        $receiver = json_encode($receiver);
        $data = [
            'mch_id'        => $setting['shop_number'],
            'sub_mch_id'    => $setting['sub_shop_number'],
            'appid'         => $setting['sub_up_app_id'],
            'nonce_str'     => $this -> createNoncestr(),
            'receiver'      => $receiver,
        ];
        $data['sign'] = $this->getWlfSign($data,$setting['secret_key']);
        $xml = $this->arrayToXml($data);
        $re = $this->wxWlfHttpsRequestPem($xml,$getUrl);
        $rearr = $this->xmlToArray($re);

        return $rearr;
    }

    /**
     * Comment: 完结分账接口
     * Author: wlf
     * Date: 2020/09/04 18:10
     */
    public function allocationFinish($orderid){
        global $_W;
        $smallorder = pdo_get('wlmerchant_smallorder',array('id' => $orderid),array('aid','orderprice','plugin','orderid','settlemoney','sid','oneleadid','twoleadid','onedismoney','twodismoney'));
        $price = $smallorder['orderprice'];
        if($smallorder['plugin'] == 'rush'){
            $order = pdo_get('wlmerchant_rush_order',array('id' => $smallorder['orderid']),array('transid','paysetid','orderno','allocationtype'));
        }else{
            $order = pdo_get('wlmerchant_order',array('id' => $smallorder['orderid']),array('transid','paysetid','orderno','allocationtype'));
        }
        if(empty($order['transid'])) {
            $order['transid'] = pdo_getcolumn(PDO_NAME . 'paylogvfour', array('tid' => $order['orderno']), 'transaction_id');
        }
        //基础信息
        $getUrl = "https://api.mch.weixin.qq.com/secapi/pay/profitsharingfinish";
        $filePath        = PATH_ATTACHMENT . "public_file/" . MODULE_NAME . "/";
        $id = $order['paysetid'];
        $info = pdo_get(PDO_NAME."payment",['id'=>$id]);
        $setting = json_decode($info['param'],true);

        $data = [
            'mch_id' => $setting['shop_number'],
            'sub_mch_id' => $setting['sub_shop_number'],
            'appid' => $setting['sub_up_app_id'],
            'nonce_str' => $this -> createNoncestr(),
            'transaction_id' => $order['transid'],
            'out_order_no' => 'PP'.date('YmdHis').random(4,1),
            'description' => '订单已完成',
        ];
        $data['sign'] = $this->getWlfSign($data,$setting['secret_key']);

        $cert = trim($filePath . $setting['cert_certificate']);
        $key = trim($filePath . $setting['key_certificate']);

        $xml = $this->arrayToXml($data);
        $re = $this->wxWlfHttpsRequestPem($xml,$getUrl,30,[],$cert,$key);
        $rearr = $this->xmlToArray($re);
        if($rearr['return_code'] == 'SUCCESS' && $rearr['result_code'] == 'SUCCESS'){
            return 1;
        }else{
            file_put_contents(PATH_DATA . "allocation_error.log", var_export($rearr, true) . PHP_EOL, FILE_APPEND);
            return 0;
        }

    }


    public function wxWlfHttpsRequestPem($vars, $url, $second = 30, $aHeader = array(),$cert = '',$key = '') {
        global $_W;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if(!empty($cert)){
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT,$cert);
        }
        if(!empty($key)){
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY,$key);
        }
        //	curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
        //	curl_setopt($ch,CURLOPT_SSLKEY, PATH_DATA."cert/".$_W['uniacid']."/wechat/apiclient_key.pem");
//		curl_setopt($ch,CURLOPT_CAINFO,'PEM');
//		curl_setopt($ch,CURLOPT_CAINFO,IA_ROOT . '/attachment/feng_fightgroups/cert/' . $_W['uniacid'] . '/rootca.pem');
        if (count($aHeader) >= 1) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $aHeader);
        }
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $vars);
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            echo "call faild, errorCode:$error\n";
            curl_close($ch);
            return false;
        }
    }

    public function getWlfSign($Obj,$key) {
        global $_W;
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        $String = $String . "&key=" . $key;
        $String = hash_hmac("sha256",$String,$key);
        $result_ = strtoupper($String);
        return $result_;
    }


}

?>