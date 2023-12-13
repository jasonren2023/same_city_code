<?php
/**
 * Comment: 上传文件(图片/视频/音频)的处理
 * Author: zzw
 * Class UploadFile
 */
defined('IN_IA') or exit('Access Denied');

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Processing\PersistentFop;
class UploadFile extends Commons {
    /**
     * Comment: 开始进行文件上传操作
     * Author: zzw
     * Date: 2019/7/22 17:52
     * @param array  文件信息
     * @param $type  1=普通上传；2=微信端上传
     * @param int   $id
     * @throws Exception
     */
    public static function uploadIndex($file = [],$type = 1,$id = 0,$params = []){
        global $_W,$_GPC;
        try {
            #1、根据内容进行操作  $type:1=普通上传；2=微信上传
            if($type == 1){
                //普通的上传操作
                if (count($file) > 0) {
                    //循环进行文件的处理
                    $path = self::uploadHandle($file);
                    //附件信息储存  只有存在params信息时才进行储存存在  目前仅后台存在
                    if($params) self::saveAttachmentInfo($params,$path);
                    //$videoInfo = UploadFile::videoInfoHandle($path['image']);
                    //$path['img'] = $videoInfo['link'];
                    //返回结果信息，返回图片链接信息
                    if($_GPC['is_base'] == 1){
                        self::sRenderSuccess('文件上传成功' , [
                            'image' => base64_encode($path['image']) ,
                            //'img'   => base64_encode($videoInfo['link']) ,
                            'img'   => base64_encode($path['img']) ,
                            'info'   => base64_encode($path) ,
                        ]);
                    }else {
                        self::sRenderSuccess('文件上传成功',$path);
                    }
                }else{
                    self::sRenderError('请上传文件');
                }
            }else{
                //文件上传在微信端口，需要进行下载操作。然后在上传
                $path = self::getWeChatImg($id);
                //$videoInfo = UploadFile::videoInfoHandle($path);
                //$imgPath['img'] = $videoInfo['link'];
                $imgPath['image'] = $path;
                $imgPath['img']   = tomedia($path);

                $imgPath['time'] = date('H:i:s',time());
                file_put_contents(PATH_DATA . "img_up.log", var_export($imgPath, true) . PHP_EOL, FILE_APPEND);
                self::sRenderSuccess('文件上传成功',$imgPath);
            }
        } catch (Exception $e) {
            self::sRenderError($e->getMessage());
        }
    }
    /**
     * Comment: 普通上传文件的信息处理
     * Author: zzw
     * Date: 2019/7/22 18:46
     * @param $file
     * @return array
     * @throws Exception
     */
    protected static function uploadHandle($file){
        #1、判断上传内容是否合格
        foreach($file as $key => $value){
            #1-1、获取文件类型
            $type = explode('/', ($value['type']))[0];//图片格式
            #1-2、根据类型调用判断方法
            switch ($type) {
                case 'image':
                    self::imageJudge($value);
                    break;//判断图片是否合格
                case 'text' :
                    $filetype = explode('.', ($value['name']))[1];
                    if($filetype != 'text' && $filetype != 'txt'){
                        self::sRenderError("类型错误，不支持的文件！");
                    }
                    break;//判断文件是否合格（暂无判断条件）
                case 'application' :
                    $filetype = explode('.', ($value['name']))[1];
                    if(!in_array($filetype,['crt','pem'])){
                        self::sRenderError("类型错误，不支持的文件！");
                    }
                    break;//判断文件是否合格（暂无判断条件）
                case 'video' :
                case 'audio' :
                    self::videoJudge($value);
                    break;//判断视频是否合格（暂无判断条件）
                default:
                    self::sRenderError("类型错误，不支持的文件！");
                    break;
            }
        }
        #2、上传图片合格，开始进行图片的上传
        $data = [];
        foreach($file as $index => $item){
            #2-1、获取文件类型
            $type = explode('/', ($value['type']))[0];//图片格式
            #2-2、根据类型调用判断方法
            switch ($type) {
                case 'image':
                    //$data[$index]['image'] = self::imageUpload($value);
                    //$data[$index]['img'] = tomedia($data[$index]['image']);
                    $data['image'] = self::imageUpload($value);
                    $data['img']   = tomedia($data['image']);
                    break;//图片上传
                case 'text':
                case 'application':
                    $data['image'] = self::fileUpload($value);
                    $data['img']   = tomedia($data['image']);
                    break;//文件上传
                case 'video':
                case 'audio' :
                    $data['image'] = self::videoUpload($value);
                    $data['img']   = tomedia($data['image']);
                    break;//文件上传
            }
        }

        return $data;
    }
    /**
     * Comment: 保存附件基本信息
     * Author: zzw
     * Date: 2020/9/1 16:55
     * @param $params
     * @param $path
     */
    protected static function saveAttachmentInfo($params,$path){
        global $_W;
        //基本信息获取
        list($width , $height , $type) = getimagesize($path['img']);//基本参数获取
        $nameArr = explode('.' , $path['img']);
        $suffix  = $nameArr[count($nameArr) - 1];
        //生成储存信息
        $data = [
            'uniacid'     => $params['uniacid'] ,
            'aid'         => $params['aid'] ,
            'shop_id'     => $params['shop_id'] ,
            'group_id'    => $params['group_id'] ,
            'url'         => $path['image'] ,
            'name'        => $params['name'] ,
            'imagewidth'  => $width ? : 0 ,
            'imageheight' => $height ? : 0 ,
            'suffix'      => $suffix ,
            'filesize'    => $params['filesize'] ,
            'mimetype'    => $params['mimetype'] ,
            'uploadtime'  => time() ,
            'storage'     => $_W['setting']['remote']['type'] ? : 0 ,
        ];

        pdo_insert(PDO_NAME."attachment",$data);
    }


    /**************  图片上传的操作 ****************************************************************************************/
    /**
     * Comment: 判断图片信息是否合格
     * Author: zzw
     * Date: 2019/7/22 18:29
     * @param $info
     */
    protected static function imageJudge($info){
        global $_W;
        #1、获取基本设置信息
        $setting = $_W['setting']['upload']['image'];
        $imageType = $setting['extentions'];//允许上传的图片格式
        if(IMS_FAMILY != 'wl'){
            $imageSize = $setting['limit'] * 1024;
        }else{
            if(Customized::init('upfile1512') > 0){
                $imageSize = 51200 * 1024;
            }else{
                $imageSize = 5120 * 1024;
            }
        }
        #2、图片格式生成
        $type = explode('/', ($info['type']))[1];//图片格式
        //判断图片格式是否允许上传
        if (!in_array(strtolower($type), $imageType) && IMS_FAMILY != 'wl' ) {
            $typeStr = implode(',', $imageType);
            self::sRenderError("格式错误,只能上传{$typeStr}格式的图片,当前格式：{$type}");
        }
        //判断图片的大小是否合格
        if ($imageSize < $info['size']) {
            $size = $imageSize / (1024 * 1024);
            self::sRenderError("图片不能超过{$size}M");
        }
    }
    /**
     * Comment: 图片上传
     * Author: zzw
     * Date: 2019/7/22 18:44
     * @param $info
     * @return string
     * @throws Exception
     */
    protected static function imageUpload($info){
        global $_W;
        #1、获取基本设置信息
        $setting = $_W['setting']['upload']['image'];
        $setting['folder'] = "images/". MODULE_NAME."/".$_W['uniacid'];
        $storageSet = $_W['wlsetting']['enclosure']['service'] ? : $_W['setting']['remote']['type'];//0=关闭;1=FTP服务器;2=阿里云;3=七牛云存储;4=腾讯云存储
        #2、图片格式后缀获取
        $type = explode('/', ($info['type']))[1];//图片格式
        $imgSuffix = '.' . strtolower($type);
        #3、配置图片储存路径
        $fileName = time() . rand(10000, 99999) . $imgSuffix;//时间戳+随机数生成不重复文件名称
        $pathName = $setting['folder'] . "/" . $fileName;//文件储存路径
        $fullName = PATH_ATTACHMENT . $pathName;//文件在本地服务器暂存地址
        #4、将图片上传至本地服务器
        $imgSaveFile = PATH_ATTACHMENT.$setting['folder'];
        if(!is_readable($imgSaveFile)) (is_file($imgSaveFile) || is_dir($imgSaveFile)) or mkdir($imgSaveFile,0700,true);
        $res = move_uploaded_file($info['tmp_name'], $fullName);
        //缩放
        if (empty($_W['setting']['upload'])) {
            $upload = $_W['config']['upload'];
        } else {
            $upload = $_W['setting']['upload'];
        }
        if($upload['image']['thumb'] > 0){
            $newName = file_image_thumb($fullName);
            if($newName){
                WeliamWeChat::file_delete($fullName);
                $newName = explode('/',$newName);
                $fileName = end($newName);
                $pathName = $setting['folder'] . "/" . $fileName;//文件储存路径
            }
        }
        if($res){
            //判断图片是否合格$_W['source']
//            if(!array_key_exists('group_id',$_REQUEST)) {
//                $isQualified = Filter::init($fullName,$_W['source'],2);
//            }else{
                $isQualified['errno'] = 1;
            //}
            if($isQualified['errno'] == 1){
                if($storageSet > 0){
                    //将储存在本地的图片上传到远程服务器
                    $remotestatus = WeliamWeChat::file_remote_upload($pathName);
                    if ($remotestatus) {
                        //WeliamWeChat::file_delete($pathName);
                        $tips = $remotestatus ? : '远程附件上传失败，请检查配置并重新上传';
                        self::sRenderError($tips, $remotestatus);
                    }
                }
                return $pathName;
            }else{
                self::sRenderError($isQualified['message']);
            }
        }else{
            self::sRenderError('图片上传失败，请重新上传');
        }
    }
    /**
     * Comment: 获取微信上传的图片，储存在并且/上传到图片服务器
     * Author: zzw
     * Date: 2019/7/23 16:46
     * @param $id
     * @return string
     * @throws Exception
     */
    protected static function getWeChatImg($id){
        global $_W;
        #1、获取token信息
        //$uniacccount = WeAccount::create($_W['acid']);
        //$access_token = $uniacccount->fetch_available_token();
        $access_token = WeliamWeChat::getAccessToken(true);
        #2、生成请求地址
        //$url = 'http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=' . trim($access_token) . '&media_id=' . trim($id);
        $url = "https://file.api.weixin.qq.com/cgi-bin/media/get?access_token={$access_token}&media_id=" . trim($id);
        #3、请求获取内容
        $res = ihttp_get($url);
        #4、判断内容是否符合条件
        $setting = $_W['setting']['upload']['image'];
        if(IMS_FAMILY == 'wl'){
            if(Customized::init('upfile1512') > 0){
                $setting['limit'] = 51200;
            }else{
                $setting['limit'] = 5120;
            }
        }
        $setting['folder'] = "images/".MODULE_NAME."/" .$_W['uniacid']. '/' . date('Y/m/d', time());
        //判断提取是否成功
        if (is_error($res)) self::sRenderError('提取文件失败, 错误信息: ' . $res['message']);
        if (intval($res['code']) != 200) self::sRenderError('提取文件失败: 未找到该资源文件');
        //判断储存目录是否存在
        $path = PATH_ATTACHMENT . $setting['folder'];
        if (!is_dir($path)) mkdir($path, 0777, true);
        //判断文件是否过大
        if (intval($res['headers']['Content-Length']) > $setting['limit'] * 1024) self::sRenderError('上传的媒体文件过大,不能大于'. $setting['limit'] .'KB');
        if($res['content']){
            //文件内容处理
            $content = $res['content'];//条件内容
            //判断是否成功获取文件
            if(is_array(json_decode($content,true)) && json_decode($content,true)['errcode']){
                self::sRenderError(json_decode($content,true)['errmsg']);
            }
            #4、将图片上传至本地服务器
            $fileName = time() . rand(10000, 99999).'.png';//时间戳+随机数生成不重复文件名称
            $pathName = $setting['folder'] . "/" . $fileName;
            $fullName = PATH_ATTACHMENT . $pathName;
            $result = file_put_contents($fullName, $content);
            if($result){
                //缩放
                if (empty($_W['setting']['upload'])) {
                    $upload = $_W['config']['upload'];
                } else {
                    $upload = $_W['setting']['upload'];
                }
                if($upload['image']['thumb'] > 0){
                    $newName = file_image_thumb($fullName);
                    if($newName){
                        WeliamWeChat::file_delete($fullName);
                        $newName = explode('/',$newName);
                        $fileName = end($newName);
                        $pathName = $setting['folder'] . "/" . $fileName;//文件储存路径
                        $fullName = PATH_ATTACHMENT . $pathName;
                    }
                }
                //判断图片是否合格$_W['source']
                $isQualified = Filter::init($fullName,$_W['source'],2);
                if($isQualified['errno'] == 1){
                    $storageSet = $_W['wlsetting']['enclosure']['service'] ? : $_W['setting']['remote']['type'];//0=关闭;1=FTP服务器;2=阿里云;3=七牛云存储;4=腾讯云存储
                    if($storageSet > 0){
                        //将储存在本地的图片上传到远程服务器
                        $remotestatus = WeliamWeChat::file_remote_upload($pathName);
                        if ($remotestatus) {
                            $tips = $remotestatus ? : '远程附件上传失败，请检查配置并重新上传';
                            self::sRenderError($tips, $remotestatus);
                        }
                    }
                    return $pathName;
                }else{
                    self::sRenderError($isQualified['message']);
                }
            }else{
                self::sRenderError("提取失败，请稍后重试");
            }
        }else{
            self::sRenderError("获取失败，请稍后重试");
        }
    }
    /**************  文件上传的操作 ****************************************************************************************/
    /**
     * Comment: 储存文件(本地储存、密钥文件)
     * Author: zzw
     * Date: 2019/8/28 11:10
     * @param $info
     * @return string
     * @throws Exception
     */
    protected static function fileUpload($info){
        global $_W;
        #1、公共文件储存路径
        $type = explode('.', ($info['name']));//图片格式
        $suffix = $type[count($type) - 1];
        $name =  rand(1000,9999).time().".".$suffix;
        $path = PATH_ATTACHMENT."public_file/".MODULE_NAME."/". $_W['uniacid']."/".date("Y-m-d",time())."/";//路径
        $link = $path.$name;//储存文件目录路径
        #2、判断路径目录是否存在 不存在建立
        if(!file_exists($path)) mkdir(iconv("UTF-8", "GBK", $path),0777,true);
        #3、储存文件到本地
        $res = move_uploaded_file($info['tmp_name'], $link);
        if($suffix == 'pem'){
            //密钥文件，只储存在本地
            if($res) return $_W['uniacid'].'/'.date("Y-m-d",time()).'/'.$name;
            else self::sRenderError('文件上传失败，请重新上传');
        }else{
            //其他文件，传递到远程
            $fileName = "public_file/".MODULE_NAME."/" . $_W['uniacid']."/".date("Y-m-d",time())."/".$name;
            if($res){
                $storageSet = $_W['wlsetting']['enclosure']['service'] ? : $_W['setting']['remote']['type'];//0=关闭;1=FTP服务器;2=阿里云;3=七牛云存储;4=腾讯云存储
                if($storageSet > 0){
                    //将储存在本地的图片上传到远程服务器
                    $remotestatus = WeliamWeChat::file_remote_upload($fileName);
                    if ($remotestatus) {
                        self::sRenderError('远程附件上传失败，请检查配置并重新上传', $remotestatus);
                    }
                }
                return $fileName;
            }else{
                self::sRenderError("提取失败，请稍后重试");
            }
        }
    }
    /**
     * Comment: 判断视频音频是否合格
     * Author: zzw
     * Date: 2020/1/8 16:02
     * @param $info
     */
    protected static function videoJudge($info){
        global $_W;
        #1、获取基本设置信息
        $setting = $_W['setting']['upload']['audio'];
        $imageType = $setting['extentions'];//允许上传的格式
        if(IMS_FAMILY != 'wl'){
            $imageSize = $setting['limit'] * 1024;
        }else{
            if(Customized::init('upfile1512') > 0){
                $imageSize = 51200 * 10240;
            }else{
                $imageSize = 51200 * 1024;
            }
        }
        #2、格式生成
        $type = explode('/', ($info['type']))[1];//格式
        if($type == 'quicktime') $type = 'mp4';//苹果兼容
        if($type == 'mpeg') $type = 'mp3';//苹果兼容
        //判断格式是否允许上传     独立版,不判断上传类型
        if (!in_array(strtolower($type), $imageType) && IMS_FAMILY != 'wl') {
            $typeStr = implode(',', $imageType);
            self::sRenderError("格式错误,只能上传{$typeStr}格式的视频音频,当前格式：{$type}");
        }
        //判断大小是否合格
        if ($imageSize < $info['size']) {
            $size = $imageSize / (1024 * 1024);
            self::sRenderError("视频不能超过{$size}M");
        }
    }
    /**
     * Comment: 视频文件上传
     * Author: zzw
     * Date: 2020/1/8 9:32
     * @param $info
     * @return string
     * @throws Exception
     */
    protected static function videoUpload($info){
        global $_W;
        #1、公共文件储存路径
        Util::wl_log('videoErrorInfo' , PATH_MODULE."log/" , $info , '视频远程储存日志',false); //写入日志记录


        $suffix = explode('/', ($info['type']))[1];//格式
        if($suffix == 'quicktime') $suffix = 'mp4';//苹果兼容
        if($suffix == 'mpeg') $suffix = 'mp3';//苹果兼容
        $name =  rand(1000,9999).time().".".$suffix;
        $path = PATH_ATTACHMENT . "public_file/".MODULE_NAME."/" . $_W['uniacid'] . "/" . date("Y-m-d" , time()) . "/";//路径
        $link = $path.$name;//储存文件目录路径
        #2、判断路径目录是否存在 不存在建立
        if(!file_exists($path)) mkdir(iconv("UTF-8", "GBK", $path),0777,true);
        #3、储存文件到本地
        $res = move_uploaded_file($info['tmp_name'], $link);
        #4、传递到远程
        $fileName = "public_file/" .MODULE_NAME."/" . $_W['uniacid'] . "/" . date("Y-m-d" , time()) . "/" . $name;
        if($res){
            //判断是否开启内部远程储存
            $uploadSet = Setting::wlsetting_read("api")['upload'];
            $uploadSet['type'] = 0;//强制关闭七牛云远程配置
            if($uploadSet['type'] == 1){
                //开启远程储存  使用内部远程储存
                $res = self::remoteStorage($uploadSet,$fileName);
                if($res['errno'] == 0){
                    //视频上传远程服务器并且截取第一帧操作失败 记录日志信息
                    Util::wl_log('videoImagePath' , PATH_MODULE."log/" , $res , '视频远程储存并且截图失败日志',false); //写入日志记录
                }
            }else{
                //未开启内部远程 使用微擎远程储存
                $storageSet = $_W['wlsetting']['enclosure']['service'] ? : $_W['setting']['remote']['type'];//0=关闭;1=FTP服务器;2=阿里云;3=七牛云存储;4=腾讯云存储
                if($storageSet > 0){
                    //将储存在本地的图片上传到远程服务器
                    $remotestatus = WeliamWeChat::file_remote_upload($fileName);
                    if ($remotestatus) {
                        self::sRenderError('远程附件上传失败，请检查配置并重新上传', $remotestatus);
                    }
                }
            }
            return $fileName;
        }else{
            self::sRenderError("提取失败，请稍后重试");
        }
    }

    /**************  内部远程附件管理（七牛云） **************************************************************************/
    protected static $auth ;//初始化签权对象
    /**
     * Comment: 视频储存到远程服务器并且获取第一帧图片作为封面图
     * Author: zzw
     * Date: 2020/1/17 14:46
     * @param array $uploadSet api设置信息
     * @param string $fileName 视频文件名称（在本地的储存路径）
     * @return array
     * @throws Exception
     */
    public static function remoteStorage($uploadSet,$fileName){
        $accessKey  = trim($uploadSet['access_key']);
        $secretKey  = trim($uploadSet['secret_key']);
        $buckey     = trim($uploadSet['bucket']);//储存空间
        $domainName = trim($uploadSet['domain_name']);//服务器域名
        $queueName  = trim($uploadSet['queue_name']);//队列名称
        #1、图片名称生成
        $nameArr = explode(MODULE_NAME,$fileName);
        $pathValue = explode('.',$nameArr[1]);
        $imagePath = $pathValue[0].".jpg";
        /*   if($pathValue[1] != 'mp4'){
                   $imagePath = $pathValue[0].".jpg";
           }else{
                  $imagePath = $pathValue[0];
           }*/
        $videoImagePath = 'videoImagePath'.$imagePath;//图片在远程服务器上的储存位置
        $videoImgLink = $domainName.$videoImagePath;//视频图片的链接
        $videoLink = $domainName . $fileName;//视频在服务器上面的位置

        #2、判断视频图片是否已经存在
        if(!remoteFileExists($videoImgLink)) {
            self::$auth = new Auth($accessKey , $secretKey);// 初始化签权对象
            $token      = self::getRemoteStorageToken($buckey);//生成上传Token
            #3、视频图片不存在 判断视频文件是否存在服务器
            if (!remoteFileExists($videoLink)) {
                #1、不存在 判断视频文件是否存在本地
                $link = PATH_ATTACHMENT . $fileName;
                if (!file_exists($link)) {
                    //文件不存在当前服务器
                    return error(0 , '文件不存在当前服务器');
                }
                #1、文件上传远程服务器
                $uploadMgr = new UploadManager();//构建 UploadManager 对象
                list($ret , $err) = $uploadMgr->putFile($token , $fileName , $link);//调用 UploadManager 的 putFile 方法进行文件的上传。
                unlink($link);
                if ($err !== null) return error(0 , $err);
            }
            #4、通过远程服务器的视频  生成视频第一帧图片信息
            $notifyUrl = '';//转码完成后通知到你的业务服务器。
            $config    = new \Qiniu\Config();
            //$config->useHTTPS=true;
            $pfop = new PersistentFop(self::$auth , $config);
            //要进行转码的转码操作。 http://developer.qiniu.com/docs/v6/api/reference/fop/av/avthumb.html
            $fops = "vframe/jpg/offset/1/w/480/h/360|saveas/" . \Qiniu\base64_urlSafeEncode($buckey . ":" . $videoImagePath); //取视频第1秒的截图，图片格式为jpg，宽度为480px，高度为360px：
            list($id , $err) = $pfop->execute($buckey , $fileName , $fops , $queueName , $notifyUrl , false);
            if ($err != null) return error(0 , $err);
            list($ret, $err) = $pfop->status($id);
            //查询转码的进度和状态   http://smartcity.weliam.cn/videoImagePath/2020-03-31/60411585633284.jpg
            /*echo $id,'<br />';
            list($ret, $err) = $pfop->status($id);
            echo "\n====> pfop avthumb status: \n";
            if ($err != null) {
                wl_debug($err);
            } else {
                wl_debug([$videoImgLink,$ret]);
            }*/
        }
        #5、输出视频上传结果
        /*$imgPath['video_img'] = $videoImgLink;
        $imgPath['image'] = $videoLink;
        $imgPath['img'] = $videoLink;
        self::sRenderSuccess('文件上传成功',$imgPath);*/
        return error(1,$videoImgLink);
    }
    /**
     * Comment: 获取视频上传token
     * Author: zzw
     * Date: 2020/1/16 18:00
     * @param $bucket
     * @return mixed
     */
    protected static function getRemoteStorageToken($bucket){
        global $_W;
        $name = md5('remoteStorageToken'.$bucket);
        $caCheInfo = json_decode(Cache::getCache('remoteStorageToken',$name),true);
        if($caCheInfo && time() < $caCheInfo['end_time']){
            $token = $caCheInfo['token'];//生成上传Token
        }else{
            //获取token
            $expires = 7200;//有效时间
            $token = self::$auth->uploadToken($bucket, null, $expires, null, true);//生成上传Token
            //记录进入session中
            $endTime = time() + ($expires - 200);
            Cache::setCache('remoteStorageToken',$name,json_encode(['token'=>$token,'end_time'=>$endTime]));
        }
        #2、返回token
        return $token;
    }
    /**
     * Comment: 根据视频路径 获取该视频的远程连接及第一帧图片
     * Author: zzw
     * Date: 2020/1/17 17:22
     * @param $fileName
     * @return mixed
     */
    public static function videoInfoHandle($fileName){
        if($fileName) {
            #1、判断默认路径是否存在视频文件  不存在获取远程服务器路径 并且判断是否存在第一帧图片信息
            $newLink = tomedia($fileName);
//            if (!remoteFileExists($newLink)) {
//                $uploadSet            = Setting::wlsetting_read("api")['upload'];
//                $domainName           = trim($uploadSet['domain_name']);//服务器域名
//                $nameArr              = explode(MODULE_NAME , $fileName);
//                $imagePath            = explode('.' , $nameArr[1])[0] . ".jpg";
//                $videoImagePath       = 'videoImagePath' . $imagePath;//图片在远程服务器上的储存位置
//                $videoImgLink         = $domainName . $videoImagePath;//视频图片的链接
//                $newLink              = $domainName . $fileName;//视频在服务器上面的位置
//            }
        }
        #2、信息拼装
        $data['link'] = $newLink ? $newLink : '';
        $data['img_link'] = '';
        //$data['img_link'] = $videoImgLink ? $videoImgLink : '';

        return $data;
    }

}
