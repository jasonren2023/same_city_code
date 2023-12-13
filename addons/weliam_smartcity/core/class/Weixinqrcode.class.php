<?php
defined('IN_IA') or exit('Access Denied');

class Weixinqrcode
{

    /**
     * 生成关注二维码
     * @param $name
     * @param $keyword
     * @param int $ptype 0商户1分销2商品
     * @param int $qrctype
     * @param int $agentid
     * @param string $remark
     * @return array
     */
    static function createqrcode($name, $keyword, $ptype = 0, $qrctype = 2, $agentid = -1, $remark = '自动获取')
    {
        global $_W, $_GPC;
        if (empty($name) || empty($keyword)) {
            return error('-1', '二维码关键字和名称不能为空');
        }
        load()->func('communication');
        $barcode = array(
            'expire_seconds' => '',
            'action_name' => '',
            'action_info' => array(
                'scene' => array(),
            ),
        );

        $scene_str = date('YmdHis') . rand(1000, 9999);
        $uniacccount = WeAccount::create($_W['acid']);

        if ($qrctype == 1) {
            $qrcid = pdo_fetchcolumn("SELECT qrcid FROM " . tablename('qrcode') . " WHERE acid = :acid AND model = '1' AND type = 'scene' ORDER BY qrcid DESC LIMIT 1", array(':acid' => $_W['acid']));
            $barcode['action_info']['scene']['scene_id'] = !empty($qrcid) ? ($qrcid + 1) : 100001;
            $barcode['expire_seconds'] = 2592000;
            $barcode['action_name'] = 'QR_SCENE';
            $result = $uniacccount->barCodeCreateDisposable($barcode);
        } else if ($qrctype == 2) {
            $is_exist = pdo_fetchcolumn('SELECT id FROM ' . tablename('qrcode') . ' WHERE uniacid = :uniacid AND scene_str = :scene_str AND model = 2', array(':uniacid' => $_W['uniacid'], ':scene_str' => $scene_str));
            if (!empty($is_exist)) {
                $scene_str = date('YmdHis') . rand(1000, 9999);
            }
            $barcode['action_info']['scene']['scene_str'] = $scene_str;
            $barcode['action_name'] = 'QR_LIMIT_STR_SCENE';
            $result = $uniacccount->barCodeCreateFixed($barcode);
        }

        if (!is_error($result)) {
            $insert = array(
                'uniacid' => $_W['uniacid'],
                'acid' => $_W['acid'],
                'qrcid' => $barcode['action_info']['scene']['scene_id'],
                'scene_str' => $barcode['action_info']['scene']['scene_str'],
                'keyword' => $keyword,
                'name' => $name,
                'model' => $qrctype,
                'ticket' => $result['ticket'],
                'url' => $result['url'],
                'expire' => $result['expire_seconds'],
                'createtime' => TIMESTAMP,
                'status' => '1',
                'type' => 'scene',
            );
            pdo_insert('qrcode', $insert);
            $qrid = pdo_insertid();
            $qrinsert = array(
                'uniacid' => $_W['uniacid'],
                'aid' => $agentid,
                'qrid' => $qrid,
                'type' => $ptype,
                'model' => $qrctype,
                'cardsn' => $scene_str,
                'salt' => random(8),
                'createtime' => TIMESTAMP,
                'status' => '1',
                'remark' => $remark
            );
            pdo_insert(PDO_NAME . 'qrcode', $qrinsert);
            return $qrid;
        }
        return $result;
    }

    static function get_qrid($message)
    {
        global $_W;
        if (!empty($message['ticket'])) {
            if (is_numeric($message['scene']) && mb_strlen($message['scene']) != 18) {
                $qrid = pdo_fetchcolumn('select id from ' . tablename('qrcode') . ' where uniacid=:uniacid and qrcid=:qrcid', array(':uniacid' => $_W['uniacid'], ':qrcid' => $message['scene']));
            } else {
                $qrid = pdo_fetchcolumn('select id from ' . tablename('qrcode') . ' where uniacid=:uniacid and scene_str=:scene_str', array(':uniacid' => $_W['uniacid'], ':scene_str' => $message['scene']));
            }
            if ($message['event'] == 'subscribe') {
                self::qr_log($qrid, $message['from'], 1);
            } else {
                self::qr_log($qrid, $message['from'], 2);
            }
        } else {
            self::send_text('欢迎关注我们!', $message);
        }
        return $qrid;
    }

    static function qr_log($qrid, $openid, $type)
    {
        global $_W;
        if (empty($qrid) || empty($openid)) {
            return;
        }
        $qrcode = pdo_get('qrcode', array('id' => $qrid), array('scene_str', 'name'));
        $log = array('uniacid' => $_W['uniacid'], 'acid' => $_W['acid'], 'qid' => $qrid, 'openid' => $openid, 'type' => $type, 'scene_str' => $qrcode['scene_str'], 'name' => $qrcode['name'], 'createtime' => time());
        pdo_insert('qrcode_stat', $log);
    }

    static function createkeywords($name, $keyword)
    {
        global $_W;
        if (empty($name) || empty($keyword)) {
            return error('-1', '二维码关键字和名称不能为空');
        }
        $rid = pdo_fetchcolumn("select id from " . tablename('rule') . 'where uniacid=:uniacid and module=:module and name=:name', array(
            ':uniacid' => $_W['uniacid'],
            ':module' => 'weliam_smartcity',
            ':name' => $name
        ));
        if (empty($rid)) {
            $rule_data = array(
                'uniacid' => $_W['uniacid'],
                'name' => $name,
                'module' => 'weliam_smartcity',
                'displayorder' => 0,
                'status' => 1
            );
            pdo_insert('rule', $rule_data);
            $rid = pdo_insertid();
        }

        $content = pdo_getcolumn('rule_keyword', array('rid' => $rid, 'module' => 'weliam_smartcity', 'content' => $keyword), 'content');
        if (empty($content)) {
            $keyword_data = array(
                'uniacid' => $_W['uniacid'],
                'rid' => $rid,
                'module' => 'weliam_smartcity',
                'content' => $keyword,
                'type' => 1,
                'displayorder' => 0,
                'status' => 1
            );
            pdo_insert('rule_keyword', $keyword_data);
        }

        return $rid;
    }

    static function send_news($returnmess, $message, $end = 1)
    {
        global $_W;
        if (count($returnmess) > 1) {
            $returnmess = array_slice($returnmess, 0, 1);
        }
        $send['touser'] = $message['from'];
        $send['msgtype'] = 'news';
        $send['news']['articles'] = $returnmess;
        $acc = WeAccount::create($_W['acid']);
        $data = $acc->sendCustomNotice($send);
        if (is_error($data)) {
            self::salerEmpty();
        } else {
            if ($end == 1) {
                self::salerEmpty();
            }
        }
    }

    static function send_image($image, $message, $end = 1)
    {
        global $_W;
        $media = self::new_image_to_media($image, $message);
        $send['touser'] = $message['from'];
        $send['msgtype'] = 'image';
        $send['image'] = array('media_id' => $media['media_id']);

        $acc = WeAccount::create($_W['acid']);
        $data = $acc->sendCustomNotice($send);
        if (is_error($data)) {
            self::salerEmpty();
        } else {
            if ($end == 1) {
                self::salerEmpty();
            }
        }
    }
    
    static function new_image_to_media($image,$message){
    	global $_W;
    	$patharray = explode('/',$image);
    	$filename = end($patharray);
    	$newpath = IA_ROOT;
    	foreach($patharray as $key => $arr ){
    		if($key > 2){
    			$newpath .= '/'.$arr;
    		}
    	}
    	$token = WeliamWeChat::getAccessToken(1,1);
    	$url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$token}&type=image";
		$data = array(
			'media' => '@' . $newpath,
		);
		$response = ihttp_request($url, $data);
		$result = @json_decode($response['content'], true);
		return $result;
    }

    static function send_text($mess, $message, $end = 1)
    {
        global $_W;
        $send['touser'] = $message['from'];
        $send['msgtype'] = 'text';
        $send['text'] = array('content' => urlencode($mess));
        $acc = WeAccount::create($_W['acid']);
        $data = $acc->sendCustomNotice($send);
        if (is_error($data)) {
            self::salerEmpty();
        } else {
            if ($end == 1) {
                self::salerEmpty();
            }
        }
    }

    static function send_wxapp($mess, $message, $end = 1)
    {
        global $_W;
        if(empty($mess['path'])){
            $mess['path'] = tomedia($_W['wlsetting']['base']['logo']);
        }
        if(!empty($mess['path'])){
            $acc = WeAccount::create($_W['acid']);
            $media = self::image_to_media($mess['path'], $message);
            $mess['thumb_media_id'] = $media['media_id'];
            unset($mess['path']);
            $send['touser'] = $message['from'];
            $send['msgtype'] = 'miniprogrampage';
            $send['miniprogrampage'] = $mess;
            $data = $acc->sendCustomNotice($send);
            if (is_error($data)) {
                self::salerEmpty();
            } else {
                if ($end == 1) {
                    self::salerEmpty();
                }
            }
        }
    }

    private static function image_to_media($image, $message)
    {
        global $_W;
        $caCheName = md5($image);//当前图片缓存信息 防止同一张图片多次提交
        //获取缓存信息
        $media =  Cache::getCache('wxappSend',$caCheName);
        if(empty($media)){
            $acc = WeAccount::create($_W['acid']);
            //文件在远程需要下载到本地
            $path = "images" . DIRECTORY_SEPARATOR . $_W['uniacid'] . DIRECTORY_SEPARATOR . "media.upload" . DIRECTORY_SEPARATOR . md5($image) . substr($image, -4);
            $allpath = ATTACHMENT_ROOT . $path;
            if (!file_exists($allpath)) {
                $imgcontent = self::getImage($image);
                $res = FilesHandle::file_mkdirs(dirname($allpath));
                if($res){
                    //保存图片信息
                    $res = file_put_contents($allpath, $imgcontent);
                    if(!$res){
                        //获取图片类型
                        $imageInfo = getimagesize($image);
                        $mime = explode('/',$imageInfo['mime'])['1'];
                        $imageType = $mime ? '.'.$mime : '.jpg';
                        //再次写入图片信息
                        ob_start();//打开输出
                        readfile($image);//输出图片文件
                        $img = ob_get_contents();//得到浏览器输出
                        ob_end_clean();//清除输出并关闭
                        $fp2 = @fopen($allpath.$imageType, "a");
                        $res = fwrite($fp2, $img);//向当前目录写入图片文件，并重新命名
                        fclose($fp2);
                        //从新定义path
                        $path = $path.$imageType;
                    }
                }else{
                    self::send_text('目录创建失败', $message);
                    return false;
                }
            }
            $media = $acc->uploadMedia($path);
            if (is_error($media)) {
                self::send_text($media['message'], $message);
            } else {
                Cache::setCache('wxappSend',$caCheName,$media);
                return $media;
            }
        }
        return $media;
    }

    private static function salerEmpty()
    {
//        ob_clean();
//        ob_start();
//        echo '';
//        ob_flush();
//        ob_end_flush();
//        exit(0);
        return true;
    }


    protected static function getImage($imgurl){
        load()->func('communication');
        $resp = ihttp_request($imgurl);

        if ($resp['code'] == 200 && !empty($resp['content'])) {
            return imagecreatefromstring($resp['content']);
        }
        if ($resp['errno'] == 35) {
            $imgurl = str_replace(array('https://'), 'http://', $imgurl);
        }

        $i = 0;
        while ($i < 3) {
            $resp = ihttp_request($imgurl);
            if ($resp['code'] == 200 && !empty($resp['content'])) {
                return imagecreatefromstring($resp['content']);
            }
            ++$i;
        }

        //以上方法都未获取图片资源
        $resp = file_get_contents($imgurl);
        return imagecreatefromstring($resp);
    }




}