<?php
defined('IN_IA') or exit('Access Denied');

class Wxplatform{
    /**
     * Comment: 删除自动回复
     * Author: wlf
     * Date: 2020/12/18 15:17
     * @param $id
     * @return bool
     */
    public static function deteleOneReply($id,$own = 0){
        if($own > 0){
            pdo_delete('rule',array('id'=>$id));
        }
        pdo_delete('rule_keyword',array('rid'=>$id));
        pdo_delete('basic_reply',array('rid'=>$id));
        pdo_delete('images_reply',array('rid'=>$id));
    }

    public function uploadTemporaryMaterial($img,$type = 'images'){
        global $_W;
        //基本参数信息获取
        $account_api = WeAccount::createByUniacid();
        if (!empty($_W['setting']['remote']['type'])) {
            $remote_file_url = tomedia($img);
            $filepath = file_remote_attach_fetch($remote_file_url,0,'');
            if(is_error($filepath)) {
                return $filepath;
            }
            $filepath = ATTACHMENT_ROOT . $filepath;
        } else {
            if (strexists(parse_url($img, PHP_URL_PATH), '/attachment/')) {
                $img = substr(parse_url($img, PHP_URL_PATH), strpos(parse_url($img, PHP_URL_PATH), '/attachment/') + strlen('/attachment/'));
            }
            $filepath = ATTACHMENT_ROOT . $img;
        }
        $filesize = filesize($filepath);
        $filesize = sizecount($filesize, true);
        if ($filesize > 10 && $type == 'videos') {
            return error(-1, '要转换的微信素材视频不能超过10M');
        }
        $res = $account_api->uploadMediaFixed($filepath, $type);
        $mediaId = $res['media_id'];
        return $mediaId;
    }

}