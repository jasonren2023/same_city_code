<?php
/**
 * Comment: 语音播报收款内容
 * Author: ZZW
 * Date: 2018/12/29
 * Time: 15:21
 */
defined('IN_IA') or exit('Access Denied');

class VoiceAnnouncements{
    static protected $hornID, //云喇叭的id
        $signature,//网关验证信息
        $uid,//用户uid
        $https = 'https://api.gateway.letoiot.com/',//智联博众请求的服务器地址
        $apiUrl = "https://speaker.17laimai.cn/",//零度请求的服务器地址
        $price,//播报的接收金额
        $vol,//音量设置
        $manfactorDeviceType,//硬件厂商设备类型
        $pt,//支付类型 0=通用(不播报前缀);1=支付宝;2=微信支付;3=云支付;4=余额支付;5=微信储值;6=微信买单;7=银联刷卡;25=哆啦宝
        $pwd;//用户密码
    /**
     * Comment: 向云喇叭服务器推送要播报的语音信息
     * Author: zzw
     * Date: 2020/6/2 9:58
     * @param int|string|float  $price      支付金额
     * @param int|string        $sid        商户id | 出租车id
     * @param int|string        $paytype    支付方式(本平台)
     * @param string            $plugin     支付模块
     * @return array|bool|mixed
     */
    public static function PushVoiceMessage($price,$sid,$paytype,$plugin = ''){
        #1、获取当前商户云喇叭配置配置信息
        if(strtolower($plugin) == 'taxipay'){
            //出租车语音播报  sid为出租车id
            $info = unserialize(pdo_getcolumn(PDO_NAME . "taxipay_master" , ['id' => $sid] , 'cloudspeaker'));
            $info['voice'] = 2;//出租车语音固定为零度
        }else{
            //其他支付语音播报
            $info = unserialize(pdo_getcolumn(PDO_NAME . "merchantdata" , ['id' => $sid] , 'cloudspeaker'));
        }
        #2、判断云喇叭是否开启   状态:0=关闭,1=开启
        if($info['state'] == 1){
            //开启
            self::$price = $price * 100;//支付金额处理
            //判断使用的喇叭类型  类型:1=智联博众,2=零度
            if($info['voice'] == 2){
                //使用零度
                return self::initLD($info,$paytype);
            }else{
                //使用智联博众
                return self::voiceZLBZ($paytype,$info);
            }
        }
        return false;
    }


/****** 智联博众云喇叭使用操作 ***************************************************************************************/
    /**
     * Comment: 智联博众云喇叭使用
     * Author: zzw
     * Date: 2020/6/1 16:46
     * @param $paytype
     * @return array|bool
     */
    protected static function voiceZLBZ($paytype,$info){
        $paymentType = self::getPayType($paytype);
        if(empty($info['id']) || empty($info['account']) || empty($info['password']) || $paymentType == 0){
            return false;
        }
        #2、获取基本要使用的信息
        self::$hornID = $info['id'];//喇叭id
        self::$uid    = $info['account'];//喇叭账号id:API_CMKJ0057
        self::$pwd    = $info['password'];//喇叭密码pwd:API_LTCMKJ0057
        self::$vol    = $info['volume'];//音量
        self::$pt     = $paymentType;
        self::$manfactorDeviceType = '03ea00020030';//$info['equipment_type'];

        self::$signature = self::getSignature();
        #3、云喇叭在线  推送收款信息
        $result = self::sendOutInfo();
        return $result;
    }
    /**
     * Comment: 获取网关签名
     * Author: zzw
     */
    protected static function getSignature(){
        //$signature = Util::getCookie("voiceSignature")[0];
        //if(empty($signature) || !$signature){
            $postData = json_encode(array('app_cust_id'=>self::$uid,'app_cust_pwd'=>self::$pwd));
            $url = self::$https."gateway/api/v2/getSignature";
            $header = array('application/json;charset=utf-8');
            $info = self::curlRequest($url,$postData,$header);
            $signature = $info['data']['signature'];//获取的签名详细内容
            //$time = $info['data']['remainTime'];//剩余的时间 (分钟)
            //Util::setCookie('voiceSignature',array($signature),$time*59);
        //}
        return $signature;
    }
    /**
     * Comment: 发送支付推送消息
     * Author: zzw
     * @return array
     */
    protected static function sendOutInfo(){
        $descs = urlencode('支付消息');//这里请求中不能带有中文 否则报错
        $codeInfo = '?id='.self::$hornID. '&uid='.self::$uid.
            '&vol='.self::$vol.'&price='.self::$price.'&pt='.self::$pt.'&seq=666&descs='.$descs;
        $url = self::$https."speaker/add.php".$codeInfo;
        $header = array(
            'Content-Type: application/x-www-form-urlencoded;charset=utf-8',
            'Authorization: '.self::$signature,
        );
        $info = self::curlRequest($url,'',$header);
        if($info['errcode'] == 0){
            return array('erron'=>1,'message'=>'收款消息推送成功');
        }else{
            return array('erron'=>0,'message'=>$info['errmsg']);
        }
    }
    /**
     * Comment: 通过本网站的付款方式获取云喇叭的付款方式的值
     * Author: zzw
     * @param $paytype
     * @return int
     */
    protected static function getPayType($paytype){
        //0=通用(不播报前缀);1=支付宝;2=微信支付;3=云支付;4=余额支付;5=微信储值;6=微信买单;7=银联刷卡;25=哆啦宝
        $val = 0;//默认 通用(不播报前缀)  为0暂不进行播报
        switch ($paytype){
            case 3:$val=1;break;//支付宝
            case 2:$val=2;break;//微信支付
            case 5:$val=2;break;//微信支付
            case 1:$val=4;break;//余额支付
        }
        return $val;
    }
    /**
     * Comment: 通过curl进行网络信息抓取
     * Author: zzw
     * @param $url
     * @param $postData
     * @param $header
     * @return mixed
     */
    protected static function curlRequest($url,$postData,$header){
        $curl = curl_init();//初始化
        curl_setopt($curl, CURLOPT_URL, $url);//设置抓取的url
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);//设置header信息
        curl_setopt($curl, CURLOPT_HEADER, 1);//设置头文件的信息作为数据流输出
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//设置获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt($curl, CURLOPT_POST, 1);//设置post方式提交
        curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
        $data = curl_exec($curl); //执行命令
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);// 获得响应结果里的：header头大小
        $info = substr($data, $headerSize);//通过截取 获取body信息
        curl_close($curl);//关闭URL请求


        return json_decode($info,true);//返回获取的信息数据
    }
/****** 零度云喇叭【商户】使用操作 ***********************************************************************************/
    /**
     * Comment: 零度云喇叭使用操作
     * Author: zzw
     * Date: 2020/6/1 18:26
     * @param array         $info       操作时传递的参数信息
     * @param string        $type       操作类型：voice=支付语音播报,bind=商户绑定/解除绑定
     * @param string|int    $payType    本平台的支付方式：1=余额,2=微信,3=支付宝；
     * @return bool|mixed
     */
    public static function initLD($info,$payType = ''){
        //判断是否绑定
        $bindData = [
            'id'    => $info['ld_id'],//云喇叭id
            'm'     => 1 ,//0为解绑，1为绑定,4强制解绑（不需提供原 USERID）
            'token' => '105827801114' ,//代理商token
            'uid'   => 'u'.$info['ld_id']
        ];
        $bindInfo = self::getBindInfo($bindData);//获取喇叭绑定信息
        $records  = json_decode($bindInfo['records'],true);
        $tokenAgentId = substr($bindData['token'],0,4);//云喇叭代理商id  为token前四位数
        $userIdAgentId = substr($records['userid'],-4);//云喇叭代理商id  为userid后四位
        if(!$userIdAgentId || $tokenAgentId == $userIdAgentId) {
            //未绑定  自动绑定
            $bindRes = self::bind($bindData);
        }
        //语音播报
        $info['ld_token'] = '105827801114';
        //根据设置调用语音播报
        if($info['ld_temp']){
            //使用自定义模板
            return self::sendDiyVoice($info,$payType);
        }else{
            //使用默认模板
            return self::sendVoice($info,$payType);
        }
    }
    /**
     * Comment: 请求获取当前云喇叭绑定信息
     * Author: zzw
     * Date: 2020/6/1 17:15
     * @param array $info  参数配置信息
     * @return mixed
     */
    protected static function getBindInfo($info){
        //参数信息配置
        $url = self::$apiUrl . 'list_bind.php';
        $data = [
            'id'    => $info['id'] ,//云喇叭id
            'token' => $info['token'] ,//代理商token
        ];
        //返回请求结果
        return curlPostRequest($url, $data);
    }
    /**
     * Comment: 进行绑定 | 解除绑定
     * Author: zzw
     * Date: 2020/6/1 17:25
     * @param $info
     * @return mixed
     */
    protected static function bind($info){
        global $_W;
        //参数信息配置
        $url = self::$apiUrl . 'bind.php';
        $data = [
            'id'    => $info['id'] ,//云喇叭id
            'm'     => $info['m'] ,//0为解绑，1为绑定,4强制解绑（不需提供原 USERID）
            'token' => $info['token'] ,//代理商token
            //用户帐号ID，自定义
            //商户命名规则：uid + 公众号id + 下划线 + sid + 商户id
            //出租车命名规则：uid + 公众号id + 下划线 + lid + 商户id
            'uid'   => $info['uid'],
        ];
        //返回请求结果
        return curlPostRequest($url, $data);
    }
    /**
     * Comment: 支付方式转换 —— 代码
     * Author: zzw
     * Date: 2020/6/1 18:25
     * @param $payType
     * @return int
     */
    protected static function getVoicePayType($payType){
        #本平台支付方式(payType):1=余额；2=微信；3=支付宝
        #云喇叭支付方式(返回值):1=支付宝,2=微信支付,3=云支付,4=余额支付,5=微信储值,6=微信买单,7=银联刷卡,8=会员卡消费,9=会员卡充值
        #10=翼支付,11=退款,12=支付宝退款,13=微信退款,14=银行卡退款,15=银联退款,16=工行e支付,18=QQ钱包到账,19=京东支付,20=用户取消支付,22=西银惠支付
        $val = 0;//默认不进行播报
        switch ($payType){
            case 3:$val=1;break;//支付宝
            case 2:$val=2;break;//微信支付
            case 1:$val=4;break;//余额支付
        }
        return $val;
    }
    /**
     * Comment: 支付方式转换 —— 汉字
     * Author: zzw
     * Date: 2020/6/10 17:24
     * @param $payType
     * @return int
     */
    protected static function getVoicePayTypeText($payType){
        #本平台支付方式(payType):1=余额；2=微信；3=支付宝
        #云喇叭支付方式(返回值):1=支付宝,2=微信支付,3=云支付,4=余额支付,5=微信储值,6=微信买单,7=银联刷卡,8=会员卡消费,9=会员卡充值
        #10=翼支付,11=退款,12=支付宝退款,13=微信退款,14=银行卡退款,15=银联退款,16=工行e支付,18=QQ钱包到账,19=京东支付,20=用户取消支付,22=西银惠支付
        $val = 0;//默认不进行播报
        switch ($payType){
            case 3:$val='支付宝';break;//支付宝
            case 2:$val='微信';break;//微信支付
            case 1:$val='余额';break;//余额支付
        }
        return $val;
    }


    /**
     * Comment: 推送消息进行语音播报 —— 默认方式
     * Author: zzw
     * Date: 2020/6/1 18:26
     * @param $info
     * @param $payType
     * @return mixed
     */
    protected static function sendVoice($info,$payType){
        //参数信息配置
        $url = self::$apiUrl . 'add.php';
        $data = [
            'id'    => $info['ld_id'] ,//云喇叭id
            'price' => self::$price ,//支付金额
            'pt'    => self::getVoicePayType($payType) ,//云喇叭平台的支付方式
            'token' => $info['ld_token'] ,//代理商token
            'version' => $info['version'] ? : 1,
            'speed' => $info['speed'] ? : 50
        ];
        //返回请求结果
        return curlPostRequest($url, $data);
    }
    /**
     * Comment: 自定义语音播报信息
     * Author: zzw
     * Date: 2020/6/10 17:31
     * @param $info
     * @param $payType
     * @return mixed
     */
    protected static function sendDiyVoice($info,$payType){
        $payTypeText = self::getVoicePayTypeText($payType);
        $price = sprintf("%.2f",(self::$price / 100));
        //模板变量替换   [金额] [支付方式]
        $info['ld_temp']        = str_replace('[金额]' , $price , $info['ld_temp']);
        $info['ld_temp']         = str_replace('[支付方式]' , $payTypeText , $info['ld_temp']);
        //参数信息配置
        $url = self::$apiUrl . 'notify.php';
        $data = [
            'id'      => $info['ld_id'] ,//云喇叭id
            'token'   => $info['ld_token'] ,//代理商token
            'message' => $info['ld_temp'] ,//播报内容
            'speed'   => $info['speed'] ? : 50,//播报语速
            'version'   => $info['version'] ? : 1,//接口版本
        ];
        //返回请求结果
        return curlPostRequest($url, $data);
    }







}












