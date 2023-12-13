<?php
defined('IN_IA') or exit('Access Denied');

class PayBuild {
    static function wechat_proxy_build($params, $wechat) {
        global $_W;
        $uniacid = !empty($wechat['service']) ? $wechat['service'] : $wechat['borrow'];
        $oauth_account = uni_setting($uniacid, array('payment'));
        if (intval($wechat['switch']) == '2') {
            $_W['uniacid'] = $uniacid;
            $wechat['signkey'] = $oauth_account['payment']['wechat']['signkey'];
            $wechat['mchid'] = $oauth_account['payment']['wechat']['mchid'];
            unset($wechat['sub_mch_id']);
        } else {
            $wechat['signkey'] = $oauth_account['payment']['wechat_facilitator']['signkey'];
            $wechat['mchid'] = $oauth_account['payment']['wechat_facilitator']['mchid'];
        }
        $acid = pdo_getcolumn('uni_account', array('uniacid' => $uniacid), 'default_acid');
        $wechat['appid'] = pdo_getcolumn('account_wechats', array('acid' => $acid), 'key');
        $wechat['version'] = 2;
        return wechat_build($params, $wechat);
    }

    static function wechat_build($params, $wechat) {
        global $_W;
        load()->func('communication');
        if (empty($wechat['version']) && !empty($wechat['signkey'])) {
            $wechat['version'] = 1;
        }
        $wOpt = array();
        if ($wechat['version'] == 1) {
            $wOpt['appId'] = $wechat['appid'];
            $wOpt['timeStamp'] = strval(TIMESTAMP);
            $wOpt['nonceStr'] = random(8);
            $package = array();
            $package['bank_type'] = 'WX';
            $package['body'] = $params['title'];
            $package['attach'] = $_W['uniacid'];
            $package['partner'] = $wechat['partner'];
            $package['out_trade_no'] = $params['uniontid'];
            $package['total_fee'] = $params['fee'] * 100;
            $package['fee_type'] = '1';
            $package['notify_url'] = MODULE_URL . 'payment/wechat/weixin_notify.php';
            $package['spbill_create_ip'] = CLIENT_IP;
            $package['time_start'] = date('YmdHis', TIMESTAMP);
            $package['time_expire'] = date('YmdHis', TIMESTAMP + 600);
            $package['input_charset'] = 'UTF-8';
            if (!empty($wechat['sub_appid'])) {
                $package['sub_appid'] = $wechat['sub_appid'];
            }
            if (!empty($wechat['sub_mch_id'])) {
                $package['sub_mch_id'] = $wechat['sub_mch_id'];
            }
            ksort($package);
            $string1 = '';
            foreach ($package as $key => $v) {
                if (empty($v)) {
                    continue;
                }
                $string1 .= "{$key}={$v}&";
            }
            $string1 .= "key={$wechat['key']}";
            $sign = strtoupper(md5($string1));

            $string2 = '';
            foreach ($package as $key => $v) {
                $v = urlencode($v);
                $string2 .= "{$key}={$v}&";
            }
            $string2 .= "sign={$sign}";
            $wOpt['package'] = $string2;

            $string = '';
            $keys = array('appId', 'timeStamp', 'nonceStr', 'package', 'appKey');
            sort($keys);
            foreach ($keys as $key) {
                $v = $wOpt[$key];
                if ($key == 'appKey') {
                    $v = $wechat['signkey'];
                }
                $key = strtolower($key);
                $string .= "{$key}={$v}&";
            }
            $string = rtrim($string, '&');
            $wOpt['signType'] = 'SHA1';
            $wOpt['paySign'] = sha1($string);
            return $wOpt;
        } else {
            $package = array();
            $package['appid'] = $wechat['appid'];
            $package['mch_id'] = $wechat['mchid'];
            $package['nonce_str'] = random(8);
            $package['body'] = cutstr($params['title'], 26);
            $package['attach'] = $_W['uniacid'];
            $package['out_trade_no'] = $params['uniontid'];
            $package['total_fee'] = $params['fee'] * 100;
            $package['spbill_create_ip'] = CLIENT_IP;
            $package['time_start'] = date('YmdHis', TIMESTAMP);
            $package['time_expire'] = date('YmdHis', TIMESTAMP + 600);
            $package['notify_url'] = MODULE_URL . 'payment/wechat/weixin_notify.php';
            $package['trade_type'] = 'JSAPI';
            $package['openid'] = empty($wechat['openid']) ? $_W['fans']['from_user'] : $wechat['openid'];
            if (!empty($wechat['sub_appid'])) {
                $package['sub_appid'] = $wechat['sub_appid'];
            }
            if (!empty($wechat['sub_mch_id'])) {
                $package['sub_mch_id'] = $wechat['sub_mch_id'];
            }
            ksort($package, SORT_STRING);
            $string1 = '';
            foreach ($package as $key => $v) {
                if (empty($v)) {
                    continue;
                }
                $string1 .= "{$key}={$v}&";
            }
            $string1 .= "key={$wechat['signkey']}";
            $package['sign'] = strtoupper(md5($string1));
            $dat = array2xml($package);
            $response = ihttp_request('https://api.mch.weixin.qq.com/pay/unifiedorder', $dat);
            if (is_error($response)) {
                return $response;
            }
            $xml = @isimplexml_load_string($response['content'], 'SimpleXMLElement', LIBXML_NOCDATA);
            if (strval($xml->return_code) == 'FAIL') {
                return error(-1, strval($xml->return_msg));
            }
            if (strval($xml->result_code) == 'FAIL') {
                return error(-1, strval($xml->err_code) . ': ' . strval($xml->err_code_des));
            }
            $prepayid = $xml->prepay_id;
            $wOpt['appId'] = $wechat['appid'];
            $wOpt['timeStamp'] = strval(TIMESTAMP);
            $wOpt['nonceStr'] = random(8);
            $wOpt['package'] = 'prepay_id=' . $prepayid;
            $wOpt['signType'] = 'MD5';
            ksort($wOpt, SORT_STRING);
            foreach ($wOpt as $key => $v) {
                $string .= "{$key}={$v}&";
            }
            $string .= "key={$wechat['signkey']}";
            $wOpt['paySign'] = strtoupper(md5($string));
            return $wOpt;
        }
    }

    /**
     * Comment: 生成支付信息时判断当前订单是否存在开通会员卡的操作,并进行相关操作
     * Author: zzw
     * @param $data  订单信息，必须存在vip_card_id & price字段
     * @return mixed
     */
    static function isOpenCard($data, $field = 'price') {
        if ($data['vip_card_id'] > 0) {
            //会员卡id存在  代表用户在购买商品时开通了vip卡
            $cardPrice = pdo_getcolumn(PDO_NAME . "halfcard_type", array('id' => $data['vip_card_id']), 'price');
            $data[$field] = sprintf("%.2f", $data[$field] + $cardPrice);
        }
        return $data;
    }
}