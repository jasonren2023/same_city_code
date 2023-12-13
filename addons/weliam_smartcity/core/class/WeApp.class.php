<?php
/**
 * Comment: 小程序独立方法类
 * Author: zzw
 * Date: 2019/11/7 15:29
 */
defined('IN_IA') or exit('Access Denied');

use EasyWeChat\Factory;
use EasyWeChat\Kernel\Messages\Raw;

class WeApp {
    /**
     * Comment: 小程序二维码生成
     * Author: zzw
     * Date: 2019/11/7 16:13
     * @param string $path
     * @param string $name
     * @param array $optional
     * @return array|string
     */
    public static function getQrCode($path, $name = '', $optional = []) {
        global $_W;
        #1、获取小程序配置信息
        $app = self::getFactoryConfig();
        #2、生成小程序太阳码
        try {
            //其中 $optional 为以下可选参数：
            //width Int - 默认 430 二维码的宽度
            //auto_color 默认 false 自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调
            //line_color 数组，auto_color 为 false 时生效，使用 rgb 设置颜色 例如 ，示例：["r" => 0,"g" => 0,"b" => 0]。
            $qrCode = $app->app_code->get($path, $optional);
            //判断返回内容为数组 则为错误抛出   正常返回内容应该为对象
            if(is_array($qrCode)) throw new Exception($qrCode['message']);
            //储存太阳码信息
            $filePath = 'addons/' . MODULE_NAME . '/data/qrcode/'.$_W['uniacid'].'/'.date("Y-m-d", time()) . '/';//保存路径
            $savePath = IA_ROOT . '/' . $filePath;//保存完整路径
            if (!file_exists($savePath . $name)) {
                if (empty($name)) $name = md5(uniqid(microtime(true), true));
                $qrCode->saveAs($savePath, $name);
            }
            return $filePath . $name;
        } catch (Exception $e) {
            //错误抛出
            $error = $e->getMessage();
            return error(0, $error);
        }
    }
    /**
     * Comment: 发送客服信息
     * Author: zzw
     * Date: 2019/11/18 18:55
     * @param $input
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function CustomerService($input) {
        global $_W;
        #1、获取参数信息
        $openid = $input['FromUserName'];//发送者的openid
        $content = $input['Content'];//用户发送的内容
        #1、获取社群信息
        $communityInfo = pdo_get(PDO_NAME . "community", ['id' => $content,'uniacid' => $_W['uniacid']], ['communqrcode', 'media_id', 'reply', 'media_endtime']);
        #1、获取小程序配置信息
        $app = self::getFactoryConfig();
        #2、小程序客服操作
        try {
            #2、判断图片id是否存在  不存在则上传图片换取图片id
            if (empty($communityInfo['media_id']) || ($communityInfo['media_endtime'] < time())) {
                #1、保证图片存在本地
                $imgPath = PATH_ATTACHMENT.'/' . $communityInfo['communqrcode'];//文件在本地服务器暂存地址
                wl_uploadImages($imgPath, tomedia($communityInfo['communqrcode']));
                #1、保证图片存在本地
                $updateImg = $app->media->uploadImage($imgPath); // $path 为本地文件路径
                if ($updateImg['media_id']) {
                    $communityInfo['media_id'] = $updateImg['media_id'];
                    pdo_update(PDO_NAME . "community", ['media_id' => $updateImg['media_id'], 'media_endtime' => time() + 48*3600], ['id' => $content]);
                }
            }
            #2、发送二维码图片
            $message = new Raw(self::dataEncoding([
                "touser"  => $openid,
                "msgtype" => 'image',
                "image"   => [
                    "media_id" => $communityInfo['media_id']
                ]
            ]));
            $app->customer_service->message($message)->to($openid)->send();
            #2、发送回复内容
            $messagess = new Raw(self::dataEncoding([
                "touser"  => $openid,
                "msgtype" => 'text',
                "text"    => [
                    "content" => $communityInfo['reply']
                ]
            ]));
            $app->customer_service->message($messagess)->to($openid)->send();
        } catch (Exception $e) {
            //错误抛出
            $error = $e->getMessage();
            Util::wl_log('customerService', PATH_MODULE . "log/", ['error' => $error, 'input' => $input], '微信小程序客服 —— 错误信息', false); //写入日志记录
        }
    }
    /**
     * Comment: 微信小程序客服接口请求验证（配置接口时微信官方验证接口是否可用）
     * Author: zzw
     * Date: 2019/11/18 11:01
     * @param $info
     * @return bool|mixed
     */
    public static function pleaseVerification($info) {
        $signature = $info["signature"];
        $timestamp = $info["timestamp"];
        $nonce = $info["nonce"];
        $echostr = $info["echostr"];
        if ($signature && $timestamp && $nonce && $echostr) {
            $set = Setting::wlsetting_read('wxapp_config');
            $token = $set['token'];
            $tmpArr = array($token, $timestamp, $nonce);
            sort($tmpArr, SORT_STRING);
            $tmpStr = implode($tmpArr);
            $tmpStr = sha1($tmpStr);
            if ($tmpStr == $signature) {
                return $echostr;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
    /**
     * Comment: 数据编码（json）
     * Author: zzw
     * Date: 2019/11/18 11:29
     * @param $array
     * @return false|mixed|string|string[]|null
     */
    public static function dataEncoding($array) {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            $str = json_encode($array);
            $str = preg_replace_callback("#\\\u([0-9a-f]{4})#i", function ($matchs) {
                return iconv('UCS-2BE', 'UTF-8', pack('H4', $matchs[1]));
            }, $str);
            return $str;
        } else {
            return json_encode($array, JSON_UNESCAPED_UNICODE);
        }
    }
    /**
     * Comment: 获取 EasyWeChat 的配置信息
     * Author: zzw
     * Date: 2019/11/7 15:41
     * @return \EasyWeChat\MiniProgram\Application
     */
    protected static function getFactoryConfig() {
        #1、生成配置信息
        $set = Setting::wlsetting_read('wxapp_config');
        $config = [
            'app_id'        => trim($set['appid']),
            'secret'        => trim($set['secret']),
            'response_type' => 'array',
        ];
        $object = Factory::miniProgram($config);
        return $object;
    }
    /**
     * Comment: 小程序手机号解密
     * Author: zzw
     * Date: 2020/9/8 9:22
     * @param $session_key
     * @param $iv
     * @param $data
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\DecryptException
     */
    public static function decryptedMobile($session_key , $iv , $data){
        $app = self::getFactoryConfig();
        return  $app->encryptor->decryptData($session_key, $iv, $data);
    }







}



