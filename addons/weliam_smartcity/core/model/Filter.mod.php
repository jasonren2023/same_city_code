<?php
defined('IN_IA') or exit('Access Denied');

/**
 * Comment: 信息过滤
 * Author: zzw
 * Class Filter.mod
 */
class Filter {
    protected static $content,
        $source,
        $ar;//信息内容

    /**
     * Comment: 信息过滤初始化
     * Author: zzw
     * Date: 2019/12/31 18:24
     * @param string|array $content
     * @param int $source 1=公众号（默认）；2=h5；3=小程序
     * @param int $type 1=文本信息，2=图片信息
     * @return array
     */
    public static function init($content,$source = 1,$type = 1){
        global $_W;
        $userset = Setting::wlsetting_read("userset");
        $sensitive = explode(',',trim($userset['sensitive'],','));
        if(is_array($sensitive) && $type == 1){
            $content = 'test'.$content;
            foreach ($sensitive as $sens){
                if(mb_stripos($content,$sens)){
                    return error(0,'内容含敏感词，请检查');
                }
            }
        }
        #source：1=公众号（默认）；2=h5；3=小程序
        #type：1=文本信息，2=图片信息
        self::$content = $content;
        self::$source  = $source;
        #1、端口信息转换
        if (self::$source == 3) $sourceName = 'wxApp';//小程序
        else $sourceName = 'weChat';//公众号、H5
        #2、过滤信息类型转换
        if ($type == 1) $typeName = 'Text';//文本信息
        else if ($type == 2) $typeName = 'Image';//图片信息
        else return error(0,'信息类型错误');
        #3、调用方法进行信息过滤
        $method = $sourceName.$typeName;
        return self::$method();
    }

    /****** 小程序信息过滤 ***********************************************************************************************/
    /**
     * Comment: 小程序 - 文本信息过滤
     * Author: zzw
     * Date: 2020/1/3 9:57
     * @param bool $state
     * @return array
     */
    protected static function wxAppText($state = false){
        #1、基本参数配置
        $accessToken = WeliamWeChat::getAccessToken($state , self::$source);
        $url         = "https://api.weixin.qq.com/wxa/msg_sec_check?access_token={$accessToken}";
        #2、调用接口进行信息判断
        $result      = curlPostRequest($url , json_encode(['content' => self::$content] , JSON_UNESCAPED_UNICODE));

        if($result['errcode'] == 40001) self::wxAppText(true);
        elseif ($result['errcode'] == 0 || $result['errcode'] == 48001) return error(1,$result['errmsg']);
        elseif ($result['errcode'] == 87014) return error(0,'含有敏感或非法内容，请修改后提交!');
        else return error(0,$result['errmsg']);
    }
    /**
     * Comment: 小程序 - 图片信息过滤
     * Author: zzw
     * Date: 2020/1/3 10:53
     * @param bool $state
     * @return array
     */
    protected static function wxAppImage($state = false){
        global $_W;
        #1、基本参数配置
        $accessToken = WeliamWeChat::getAccessToken($state , self::$source);
        $url         = "https://api.weixin.qq.com/wxa/img_sec_check?access_token={$accessToken}";
        #2、图片资源转换
        $obj = new CURLFile(realpath(self::$content));
        $obj->setMimeType("image/jpeg");
        $file['media'] = $obj;
        $result        = curlPostRequest($url , $file);
        if($result['errcode'] == 40001) self::wxAppText(true);
        elseif ($result['errcode'] == 0 || $result['errcode'] == 48001) return error(1,'成功');
        elseif ($result['errcode'] == 87014) return error(0,'非法图片');
        else return error(0,$result['errmsg']);
    }


    /****** 公众号信息过滤 ***********************************************************************************************/
    //公众号 - 文本信息过滤 - 开发中
    protected static function weChatText(){
        #1、判断如果存在 小程序并且进行了信息配置 则使用小程序过滤信息
        $set = Setting::wlsetting_read('wxapp_config');
        if(p('wxapp') && is_array($set) && $set['appid']){
            self::$source  = 3;
            return self::wxAppText(true);
        }


        return error(1,'成功');
    }
    //公众号 - 图片信息过滤 - 开发中
    protected static function weChatImage(){
        #1、判断如果存在 小程序并且进行了信息配置 则使用小程序过滤信息
        $set = Setting::wlsetting_read('wxapp_config');
        if(p('wxapp') && is_array($set) && $set['appid']){
            self::$source  = 3;
            return self::wxAppImage(true);
        }

        return error(1,'成功');
    }

}
