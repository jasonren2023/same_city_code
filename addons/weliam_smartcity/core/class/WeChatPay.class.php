<?php
/**
 * Comment: 小程序微信支付功能
 * Author: ZZW
 * Date: 2018/9/25
 * Time: 11:43
 */
defined('IN_IA') or exit('Access Denied');

class WeChatPay {
    protected $appid;//小程序appid
    protected $openid;//用户openid
    protected $mch_id;//商户id
    protected $key;//商户支付key
    protected $out_trade_no;//订单号
    protected $body;//商品简单描述
    protected $total_fee;//订单总金额（单位：分）

    /**
     * Comment: 统一下单接口
     * Author: zzw
     * @param $mid              用户id
     * @param $orderNum         订单号
     * @param $goodDescribe     商品描述
     * @param $fee              实际支付金额
     * @return array
     */
    public static function pay($mid, $orderNum, $goodDescribe, $fee) {
        $pay = new WeChatPay();
        //更新受保护的数据
        $pay->getCode($mid, $orderNum, $goodDescribe, $fee);
        //开始支付数据的操作
        $return = $pay->weixinapp();

        return $return;
    }

    /**
     * Comment: 更新数据
     * Author: zzw
     * @param $mid              用户id
     * @param $orderNum         订单号
     * @param $goodDescribe     商品描述
     * @param $fee              实际支付金额
     */
    private function getCode($mid, $orderNum, $goodDescribe, $fee) {
        $set = unserialize(pdo_getcolumn(PDO_NAME . "setting", array('key' => 'city_selection_set'), 'value'));
        $this->appid = $set['appid'];//appid
        $this->openid = pdo_getcolumn(PDO_NAME . "member", array('id' => $mid), array('wechat_openid')); //openid
        $this->mch_id = $set['mch_id'];//mch_id  商户id
        $this->key = $set['pay_key'];//key  支付key
        $this->out_trade_no = $orderNum; //out_trade_no  订单号
        $this->body = $goodDescribe; //body  商品描述
        $this->total_fee = $fee;  //total_fee  实际支付总金额
    }

    /**
     * Comment: 微信小程序接口
     * Author: zzw
     * @return array
     */
    private function weixinapp() {
        //统一下单接口
        $unifiedorder = $this->unifiedorder();
        $parameters = array(
            'appId'     => $this->appid, //小程序ID
            'timeStamp' => '' . time() . '', //时间戳
            'nonceStr'  => $this->createNoncestr(), //随机串
            'package'   => 'prepay_id=' . $unifiedorder['prepay_id'], //数据包
            'signType'  => 'MD5'//签名方式
        );
        //签名
        $parameters['paySign'] = $this->getSign($parameters);
        return $parameters;
    }

    /**
     * Comment: 统一下单接口
     * Author: zzw
     * @return mixed
     */
    private function unifiedorder() {
        $url = 'https://api.mch.weixin.qq.com/pay/unifiedorder';
        $parameters = array(
            'appid'        => $this->appid, //小程序ID
            'mch_id'       => $this->mch_id, //商户号
            'nonce_str'    => $this->createNoncestr(), //随机字符串
            'body'         => $this->body,//商品描述
            'out_trade_no' => $this->out_trade_no,//商户订单号
            'total_fee'    => $this->total_fee * 100,//总金额 单位 分
            'notify_url'   => 'http://www.weixin.qq.com/wxpay/pay.php',//通知地址  确保外网能正常访问
            'openid'       => $this->openid, //用户id
            'trade_type'   => 'JSAPI'//交易类型
        );

        //统一下单签名
        $parameters['sign'] = $this->getSign($parameters);
        $xmlData = $this->arrayToXml($parameters);
        $return = $this->xmlToArray($this->postXmlCurl($xmlData, $url, 60));

        return $return;
    }

    /**
     * Comment: 请求获取支付数据
     * Author: zzw
     * @param $xml
     * @param $url
     * @param int $second
     * @return mixed
     */
    private static function postXmlCurl($xml, $url, $second = 30) {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE); //严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        set_time_limit(0);

        //运行curl
        $data = curl_exec($ch);
        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            return "curl出错，错误码:$error";
        }
    }

    /**
     * Comment: 数组转换成xml
     * Author: zzw
     * @param $arr
     * @return string
     */
    private function arrayToXml($arr) {
        $xml = "<root>";
        foreach ($arr as $key => $val) {
            if (is_array($val)) {
                $xml .= "<" . $key . ">" . arrayToXml($val) . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            }
        }
        $xml .= "</root>";
        return $xml;
    }

    /**
     * Comment: xml转换成数组
     * Author: zzw
     * @param $xml
     * @return mixed
     */
    private function xmlToArray($xml) {


        //禁止引用外部xml实体


        libxml_disable_entity_loader(true);


        $xmlstring = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);


        $val = json_decode(json_encode($xmlstring), true);


        return $val;
    }

    /**
     * Comment: 产生随机字符串，不长于32位
     * Author: zzw
     * @param int $length
     * @return string
     */
    private function createNoncestr($length = 32) {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * Comment: 生成签名
     * Author: zzw
     * @param $Obj
     * @return string
     */
    private function getSign($Obj) {
        foreach ($Obj as $k => $v) {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        //签名步骤二：在string后加入KEY
        $String = $String . "&key=" . $this->key;
        //签名步骤三：MD5加密
        $String = md5($String);
        //签名步骤四：所有字符转为大写
        $result_ = strtoupper($String);
        return $result_;
    }

    /**
     * Comment: 格式化参数，签名过程需要使用
     * Author: zzw
     * @param $paraMap
     * @param $urlencode
     * @return bool|string
     */
    private function formatBizQueryParaMap($paraMap, $urlencode) {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if ($urlencode) {
                $v = urlencode($v);
            }
            $buff .= $k . "=" . $v . "&";
        }
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }

}



