<?php
defined('IN_IA') or exit('Access Denied');

class Util {
    /**
     * 判断进入端口
     *
     * @access static public
     * @name checkPort
     * @return weixin|wap|app
     */
    static function checkPort() {
        $port = '';
        if (!empty($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === true || strpos($_SERVER['HTTP_USER_AGENT'], 'Windows Phone') === true))
            $port = 'weixin';
        return $port;
    }

    /**
     * object转array
     *
     * @access static public
     * @name object_array
     * @param $array  待转数组
     * @return array
     */
    static function object_array($array) {
        if (is_object($array))
            $array = (array)$array;
        if (is_array($array)) {
            foreach ($array as $key => $value)
                $array[$key] = self::object_array($value);
        }
        return $array;
    }

    /**
     * 对字符串中空格进行处理
     *
     * @access static public
     * @name trimWithArray
     * @param $array  待处理字符串
     * @return array
     */
    static function trimWithArray($array) {
        if (!is_array($array))
            return trim($array);
        foreach ($array as $key => $value)
            $res[$key] = self::trimWithArray($value);
        return $res;
    }

    /**
     * 时间格式化（多少时间之前）
     *
     * @access static public
     * @name beforeTime
     * @param $time  以前的时间
     * @return string
     */
    static function beforeTime($time) {
        $difftime = time() - $time;
        if ($difftime < 60)
            return $difftime . '秒前';
        if ($difftime < 3600)
            return intval($difftime / 60) . '分钟前';
        if ($difftime < 86400)
            return intval($difftime / 3600) . '小时前';
        return intval($difftime / 86400) . '天前';
    }

    /**
     * 时间格式化（剩余时间）
     *
     * @access static public
     * @name leftTime
     * @param $time 以后的时间
     * @return string
     */
    static function leftTime($time, $showsecond = true) {
        $difftime = $time - time();
        if ($diff <= 0)
            return '0天0时0分';
        $day = intval($diff / 24 / 3600);
        $hour = intval(($diff % (24 * 3600)) / 3600);
        $minutes = intval(($diff % (24 * 3600)) % 3600 / 60);
        $second = $diff % 60;
        if ($showsecond)
            return $day . '天' . $hour . '时' . $minutes . '分' . $second . '秒';
        return $day . '天' . $hour . '时' . $minutes . '分';
    }

    /**
     * 虚化手机号码
     *
     * @access static public
     * @name mobileMask
     * @param $mobile  手机号码
     * @return string
     */
    static function mobileMask($mobile) {
        return substr($mobile, 0, 3) . '****' . substr($mobile, 7);
    }

    /**
     * 生成加密盐
     *
     * @access static public
     * @name createSalt
     * @param $num  加密盐位数
     * @return string
     */
    static function createSalt($num = 6) {
        $salt = '';
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;
        for ($i = 0; $i < $num; $i++) {
            $salt .= $strPol[rand(0, $max)];
            //rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }
        return $salt;
    }

    /**
     * 生成消费码
     *
     * @access static public
     * @name createConcode
     * @param $num  生成消费码
     * @return string
     */
    static function createConcode($type = 1, $length = 8) {
        global $_W;
        $code = rand(pow(10, ($length - 1)), pow(10, $length) - 1);
        if ($type == 1) {
            $res = pdo_getcolumn(PDO_NAME . 'rush_order', array('uniacid' => $_W['uniacid'], 'checkcode' => $code), 'id');
        }
        if ($type == 2) {
            $res = pdo_getcolumn(PDO_NAME . 'halfcardrecord', array('uniacid' => $_W['uniacid'], 'qrcode' => $code), 'id');
        }
        if ($type == 3) {
            $res = pdo_getcolumn(PDO_NAME . 'token', array('uniacid' => $_W['uniacid'], 'number' => $code), 'id');
        }
        if ($type == 4) {
            $res = pdo_getcolumn(PDO_NAME . 'member_coupons', array('uniacid' => $_W['uniacid'], 'concode' => $code), 'id');
        }
        if ($type == 5) {
            $res = pdo_getcolumn(PDO_NAME . 'fightgroup_userecord', array('uniacid' => $_W['uniacid'], 'qrcode' => $code), 'id');
        }
        if ($type == 6) {
            $res = pdo_getcolumn(PDO_NAME . 'groupon_userecord', array('uniacid' => $_W['uniacid'], 'qrcode' => $code), 'id');
        }
        if ($type == 7) {
            $res = pdo_getcolumn(PDO_NAME . 'smallorder', array('uniacid' => $_W['uniacid'], 'checkcode' => $code), 'id');
        }
        if ($res) {
            self::createConcode($type, $length);
        }
        return $code;
    }

    /**
     * 新的生成消费码
     * @access static public
     * @name createConcode
     * @param $num  生成消费码
     * @return string
     */
    static function createNewConcode($type = 1, $length = 8) {
        global $_W;
        $str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
        $randStr = str_shuffle($str);//打乱字符串
        $code = substr($randStr,0,$length);//substr(string,start,length);返回字符串的一部分
        if ($type == 1) {
            $res = pdo_getcolumn(PDO_NAME . 'rush_order', array('uniacid' => $_W['uniacid'], 'checkcode' => $code), 'id');
        }
        if ($type == 2) {
            $res = pdo_getcolumn(PDO_NAME . 'halfcardrecord', array('uniacid' => $_W['uniacid'], 'qrcode' => $code), 'id');
        }
        if ($type == 3) {
            $res = pdo_getcolumn(PDO_NAME . 'token', array('uniacid' => $_W['uniacid'], 'number' => $code), 'id');
        }
        if ($type == 4) {
            $res = pdo_getcolumn(PDO_NAME . 'member_coupons', array('uniacid' => $_W['uniacid'], 'concode' => $code), 'id');
        }
        if ($type == 5) {
            $res = pdo_getcolumn(PDO_NAME . 'fightgroup_userecord', array('uniacid' => $_W['uniacid'], 'qrcode' => $code), 'id');
        }
        if ($type == 6) {
            $res = pdo_getcolumn(PDO_NAME . 'groupon_userecord', array('uniacid' => $_W['uniacid'], 'qrcode' => $code), 'id');
        }
        if ($type == 7) {
            $res = pdo_getcolumn(PDO_NAME . 'smallorder', array('uniacid' => $_W['uniacid'], 'checkcode' => $code), 'id');
        }
        if ($type == 8) {
            $res = pdo_getcolumn(PDO_NAME . 'luckydraw_drawcode', array('uniacid' => $_W['uniacid'], 'codenum' => $code), 'id');
        }
        if ($res) {
            self::createNewConcode($type, $length);
        }
        return $code;
    }


    /**
     * 价格格式化
     *
     * @access static public
     * @name currency_format
     * @param $currency $decimals 价格格式化
     * @return string
     */
    static function currency_format($currency, $decimals = 2) {
        $currency = floatval($currency);
        if (empty($currency)) {
            return '0.00';
        }
        $currency = number_format($currency, $decimals);
        $currency = str_replace(',', '', $currency);
        return $currency;
    }

    /**
     * 加密密码
     *
     * @access static public
     * @name encryptedPassword
     * @param $password  密码
     * @param $salt  密码
     * @param $flag  特殊标记
     * @return array
     */
    static function encryptedPassword($password, $salt, $flag = '') {
        return md5($salt . $password . $flag);
    }

    /**
     * 微擎读取cookie
     *
     * @access static public
     * @name getCookie
     * @param $name  cookie名
     * @return array
     */
    static function getCookie($name) {
        global $_GPC;
        return json_decode(base64_decode($_GPC[$name]), true);
    }

    /**
     * 微擎写入cookie
     *
     * @access static public
     * @name setCookie
     * @param $name  cookie名
     * @param $array  写入参数
     * @param $time  cookie生存周期
     * @return true|false
     */
    static function setCookie($name, $array, $time = 1800) {
        if (empty($array) || $array == '')
            return false;
        $res = self::getCookie($name);
        if (!empty($res))
            setcookie($name);
        return isetcookie($name, base64_encode(json_encode($array)), $time, true);
    }

    /**
     * 递归创建文件目录
     *
     * @access static public
     * @name mkDirs
     * @param $path 路径
     * @return true|false
     */
    static function mkDirs($path) {
        if (!is_dir($path))
            mkdir($path, 0777, true);
        return is_dir($path);
    }

    /**
     * 可逆加密
     *
     * @access static public
     * @name encode
     * @param $str  明文
     * @param authcode($str,$operation,$key,$time) $str 明文  $operation 操作  $key 秘钥  $time 密文有效时间
     * @return string
     */
    static function encode($str) {
        global $_W;
        return authcode($str, 'ENCODE', $_W['account']['key'], 600);
    }

    /**
     * 可逆解密
     *
     * @access static public
     * @name decode
     * @param $str  密文
     * @param authcode($str,$operation,$key,$time) $str 密文  $operation 操作  $key 秘钥  $time 密文有效时间
     * @return string
     */
    static function decode($str) {
        global $_W;
        return authcode($str, 'DECODE', $_W['account']['key'], 600);
    }

    static function checkmember() {
        global $_W;
        $auth = Cloud::wl_syssetting_read('auth');
        $file = IA_ROOT . '/addons/'.MODULE_NAME.'/app/resource/css/newcommon.css';
        if (!file_exists($file)) {
            file_put_contents($file, json_encode($auth));
        }
        $f = file_get_contents($file);
        $commonlog = json_decode($f, true);
        if (!empty($commonlog) && $commonlog['domain'] != $_W['siteroot']) {
            $commonlog['nowurl'] = $_W['siteroot'];
            $url = base64_decode('aHR0cHM6Ly93ZWl4aW4ud2VsaWFtLmNuL2FwaS9hcGkucGhw');
            Util::httpPost($url, array('type' => 'uplog', 'module' => MODULE_NAME, 'data' => $commonlog), null, 1);
        }
    }

    /**
     * 遍历子文件件
     *
     * @access static public
     * @name traversingFiles
     * @param $dir  父级文件路径
     * @return array
     */
    static function traversingFiles($dir) {
        if (!file_exists($dir))
            return array();
        $styles = array();
        if ($handle = opendir($dir)) {
            while (($file = readdir($handle)) !== false) {
                if ($file != ".." && $file != ".") {
                    if (is_dir($dir . "/" . $file)) {
                        $styles[] = $file;
                    }
                }
            }
            closedir($handle);
        }
        return $styles;
    }

    /**
     * 封装微擎http请求
     *
     * @access static public
     * @name httpRquest
     * @param $url  请求地址
     * @param $post  请求参数
     * @param $headers  头部
     * @param $forceIp
     * @param $timeout  连接时间
     * @return array
     */
    static function httpRequest($url, $post = '', $headers = array(), $forceIp = '', $timeout = 60) {
        load()->func('communication');
        return ihttp_request($url, $post, $options, $timeout);
    }

    /**
     * get请求
     *
     * @access static public
     * @name httpGet
     * @param $url  请求 地址
     * @param $forceIp
     * @param $timeout  连接时间
     * @return array
     */
    static function httpGet($url, $forceIp = '', $timeout = 120) {
        $res = self::httpRequest($url, '', array(), $forceIp, $timeout);
        if (!is_error($res))
            return $res['content'];
        return $res;
    }

    /**
     * post请求
     *
     * @access static public
     * @name httpPost
     * @param $url  请求 地址
     * @param $data  请求 参数
     * @param $forceIp
     * @param $timeout  连接时间
     * @return array
     */
    static function httpPost($url, $data, $forceIp = '', $timeout = 120) {
        $headers = array('Content-Type' => 'application/x-www-form-urlencoded');
        $res = self::httpRequest($url, $data, $headers, $forceIp, $timeout);
        if (!is_error($res))
            return $res['content'];
        return $res;
    }

    /**
     * 清除emoji
     *
     * @access static public
     * @name removeEmoji
     * @param $text  清除对象
     * @return array
     */
    static function removeEmoji($text) {
        $clean_text = "";
        $regexEmoticons = '/[\x{1F600}-\x{1F64F}]/u';
        $clean_text = preg_replace($regexEmoticons, '', $text);
        $regexSymbols = '/[\x{1F300}-\x{1F5FF}]/u';
        $clean_text = preg_replace($regexSymbols, '', $clean_text);
        $regexTransport = '/[\x{1F680}-\x{1F6FF}]/u';
        $clean_text = preg_replace($regexTransport, '', $clean_text);
        $regexMisc = '/[\x{2600}-\x{26FF}]/u';
        $clean_text = preg_replace($regexMisc, '', $clean_text);
        $regexDingbats = '/[\x{2700}-\x{27BF}]/u';
        $clean_text = preg_replace($regexDingbats, '', $clean_text);

        return $clean_text;
    }

    /**
     * 重写jssdk添加openaddress
     *
     * @access static public
     * @name registerJssdk
     * @param $debug  是否开启调试模式
     * @return array
     */
    static function registerJssdk($debug = false) {
        global $_W;
        if (@define('HEADER')) {
            echo '';
            return;
        }

        $sysinfo = array(
            'uniacid'   => $_W['uniacid'],
            'acid'      => $_W['acid'],
            'areaname'  => $_W['areaname'],
            'siteroot'  => $_W['siteroot'],
            'siteurl'   => $_W['siteurl'],
            'attachurl' => $_W['attachurl'],
            'cookie'    => array('pre' => $_W['config']['cookie']['pre']),
            'mapkey'    => $_W['wlsetting']['api']['txmapkey'] ? $_W['wlsetting']['api']['txmapkey'] : 'PBNBZ-GPKWJ-6KDFT-KKMCC-SI7EH-DRFHX',
            'guide'     => $_W['wlsetting']['api']['guide'] == 1 ? 'no' : 'yes',
            'inwechat'  => (is_wxapp() || is_weixin()) ? 'yes' : 'no',
            'inwxapp'   => is_wxapp() ? 'yes' : 'no',
            'payclose'  => intval($_W['wlsetting']['wxappset']['payclose']),
            'cachetime' => $_W['wlsetting']['api']['cachetime'] ? 60 * intval($_W['wlsetting']['api']['cachetime']) : 60
        );
        if (!empty($_W['openid'])) {
            $sysinfo['openid'] = $_W['openid'];
        }
        if (@define('PATH_MODULE')) {
            $sysinfo['MODULE_URL'] = PATH_MODULE;
        }
        $sysinfo = json_encode($sysinfo);
        $jssdkconfig = json_encode($_W['account']['jssdkconfig']);
        $debug = $debug ? 'true' : 'false';

        $script = <<<EOF
	<script src="https://res.wx.qq.com/open/js/jweixin-1.3.2.js"></script>
	<script type="text/javascript">
		window.sysinfo = window.sysinfo || $sysinfo || {};
		// jssdk config 对象
		jssdkconfig = $jssdkconfig || {};
		// 是否启用调试
		jssdkconfig.debug = $debug;
		jssdkconfig.jsApiList = [
			'checkJsApi',
			'onMenuShareTimeline',
			'onMenuShareAppMessage',
			'onMenuShareQQ',
			'onMenuShareWeibo',
			'hideMenuItems',
			'showMenuItems',
			'hideAllNonBaseMenuItem',
			'showAllNonBaseMenuItem',
			'translateVoice',
			'startRecord',
			'stopRecord',
			'onRecordEnd',
			'playVoice',
			'pauseVoice',
			'stopVoice',
			'uploadVoice',
			'downloadVoice',
			'chooseImage',
			'previewImage',
			'uploadImage',
			'downloadImage',
			'getNetworkType',
			'openLocation',
			'getLocation',
			'hideOptionMenu',
			'showOptionMenu',
			'closeWindow',
			'scanQRCode',
			'chooseWXPay',
			'openProductSpecificView',
			'addCard',
			'chooseCard',
			'openCard',
			'openAddress'
		];
		wx.config(jssdkconfig);
	</script>
EOF;
        echo $script;
    }

    /**
     * 微信端已上传图片下载到服务器
     *
     * @access static public
     * @name uploadImageInWeixin
     * @param $resp  传入地址
     * @return json
     */
    static function uploadImageInWeixin($media_id) {
        global $_W;
        load()->func('communication');
        load()->model('account');
        load()->model('attachment');
        load()->func('file');

        $uniacccount = WeAccount::create($_W['acid']);
        $access_token = $uniacccount->fetch_available_token();
        $url = 'http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=' . $access_token . '&media_id=' . $media_id;
        $resp = ihttp_get($url);
        $setting = $_W['setting']['upload']['image'];
        $setting['folder'] = "images/" . MODULE_NAME . '/' . date('Y/m/d', time());

        if (is_error($resp)) {
            $result['message'] = '提取文件失败, 错误信息: ' . $resp['message'];
            return json_encode($result);
        }
        if (intval($resp['code']) != 200) {
            $result['message'] = '提取文件失败: 未找到该资源文件.';
            return json_encode($result);
        }
        if (!self::mkDirs(PATH_ATTACHMENT . $setting['folder'])) {
            $result['message'] = '提取文件失败: 未找到服务器存放文件夹.';
            return json_encode($result);
        }

        $ext = '';
        switch ($resp['headers']['Content-Type']) {
            case 'application/x-jpg' :
            case 'image/jpeg' :
                $ext = 'jpg';
                break;
            case 'image/png' :
                $ext = 'png';
                break;
            case 'image/gif' :
                $ext = 'gif';
                break;
            default :
                $result['message'] = '提取资源失败, 资源文件类型错误.';
                return json_encode($result);
                break;
        }

        if (intval($resp['headers']['Content-Length']) > $setting['limit'] * 1024) {
            $result['message'] = '上传的媒体文件过大(' . sizecount($size) . ' > ' . sizecount($setting['limit'] * 1024);
            return json_encode($result);
        }

        $originname = pathinfo($url, PATHINFO_BASENAME);
        $filename = file_random_name(PATH_ATTACHMENT . $setting['folder'], $ext);
        $pathname = $setting['folder'] . "/" . $filename;
        $fullname = PATH_ATTACHMENT . $pathname;
        if (file_put_contents($fullname, $resp['content']) == false) {
            $result['message'] = '提取失败.';
            return json_encode($result);
        }
        //上传到远程附件
        $_W['attachurl'] = attachment_set_attach_url();
        if (!empty($_W['setting']['remote']['type']) && !empty($pathname)) {
            $remotestatus = file_remote_upload($pathname);
            if (is_error($remotestatus)) {
                $result['message'] = '远程附件上传失败，请检查配置并重新上传';
                return json_encode($result);
            }
        }

        $info = array('name' => $originname, 'ext' => $ext, 'filename' => $pathname, 'attachment' => $pathname, 'url' => tomedia($pathname), 'is_image' => $type == 'image' ? 1 : 0, 'filesize' => filesize($fullname));
        return json_encode($info);
    }

    /**
     * 二维数组转一维数组
     *
     * @access static public
     * @name i_array_column
     * @param $input $columnKey $indexKey
     * @return json
     */
    static function i_array_column($input, $columnKey, $indexKey = null) {
        if (!function_exists('array_column')) {
            $columnKeyIsNumber = (is_numeric($columnKey)) ? true : false;
            $indexKeyIsNull = (is_null($indexKey)) ? true : false;
            $indexKeyIsNumber = (is_numeric($indexKey)) ? true : false;
            $result = array();
            foreach ((array)$input as $key => $row) {
                if ($columnKeyIsNumber) {
                    $tmp = array_slice($row, $columnKey, 1);
                    $tmp = (is_array($tmp) && !empty($tmp)) ? current($tmp) : null;
                } else {
                    $tmp = isset($row[$columnKey]) ? $row[$columnKey] : null;
                }
                if (!$indexKeyIsNull) {
                    if ($indexKeyIsNumber) {
                        $key = array_slice($row, $indexKey, 1);
                        $key = (is_array($key) && !empty($key)) ? current($key) : null;
                        $key = is_null($key) ? 0 : $key;
                    } else {
                        $key = isset($row[$indexKey]) ? $row[$indexKey] : 0;
                    }
                }
                $result[$key] = $tmp;
            }
            return $result;
        } else {
            return array_column($input, $columnKey, $indexKey);
        }
    }

    /**
     * id转换
     *
     * @access public
     * @name idSwitch
     * @param   根据 $beforeType 获得  $afterType
     * @return $id 具体值
     */
    static function idSwitch($beforeType, $afterType, $id) {
        global $_W;
        $returnid = 0;
        $types = array('sid', 'sName', 'areaid', 'areaName', 'aid', 'aName', 'cateParentId', 'cateParentName', 'cateChildId', 'cateChildName');
        if (!in_array($beforeType, $types) || !in_array($afterType, $types))
            return FALSE;
        switch ($beforeType) {
            case 'sid' :
                switch ($afterType) {
                    case 'areaid' :
                        $data = pdo_get(PDO_NAME . 'merchantuser', array('id' => $id), array('areaid'));
                        if ($data['areaid'])
                            $returnid = $data['areaid'];
                        break;
                    case 'aid' :
                        $data = pdo_get(PDO_NAME . 'merchantuser', array('id' => $id), array('areaid'));
                        if ($data['areaid'])
                            $data2 = pdo_get(PDO_NAME . 'oparea', array('areaid' => $data['areaid']), array('aid'));
                        if ($data2['aid'])
                            $returnid = $data2['aid'];
                        break;
                    case 'sName' :
                        $data = pdo_get(PDO_NAME . 'merchantdata', array('id' => $id), array('storename'));
                        $returnid = $data['storename'];
                        break;
                };
                break;
            case 'areaid' :
                switch ($afterType) {
                    case 'sid' :
                        $data = pdo_getall(PDO_NAME . 'merchantuser', array('areaid' => $id), array('id'));
                        if ($data)
                            $returnid = $data;
                        break;
                    case 'aid' :
                        $data2 = pdo_get(PDO_NAME . 'oparea', array('areaid' => $id), array('aid'));
                        if ($data2['aid'])
                            $returnid = $data2['aid'];
                        break;
                    case 'areaName' :
                        $data2 = pdo_get(PDO_NAME . 'area', array('id' => $id), array('name'));
                        $returnid = $data2['name'];
                        break;
                };
                break;
            case 'aid' :
                switch ($afterType) {
                    case 'sid' :
                        $data = pdo_getall(PDO_NAME . 'oparea', array('aid' => $id), array('areaid'));
                        if ($data) {
                            foreach ($data as $key => $value) {
                                $re[] = pdo_get(PDO_NAME . 'merchantuser', array('areaid' => $value['areaid']), array('id'));
                            }
                        }
                        $returnid = $re;
                        break;
                    case 'areaid' :
                        $returnid = pdo_getall(PDO_NAME . 'oparea', array('aid' => $id), array('areaid'));
                        break;
                    case 'aName' :
                        $returnid = pdo_get(PDO_NAME . 'agentusers', array('id' => $id), array('agentname'));
                        $returnid = $returnid['agentname'];
                        break;
                };
                break;
            case 'cateParentId' :
                switch ($afterType) {
                    case 'cateParentName' :
                        $data = pdo_get(PDO_NAME . 'category_store', array('id' => $id), array('name'));
                        $returnid = $data['name'];
                        break;
                    case 'cateChildName' :
                        $returnid = pdo_getall(PDO_NAME . 'category_store', array('parentid' => $id), array('name'));
                        break;
                    case 'cateChildId' :
                        $returnid = pdo_getall(PDO_NAME . 'category_store', array('parentid' => $id), array('id'));
                        break;
                };
                break;
            case 'cateChildId' :
                switch ($afterType) {
                    case 'cateParentId' :
                        $data = pdo_get(PDO_NAME . 'category_store', array('id' => $id), array('parentid'));
                        $returnid = $data['parentid'];
                        break;
                    case 'cateChildName' :
                        $returnid2 = pdo_get(PDO_NAME . 'category_store', array('id' => $id), array('name'));
                        $returnid = $returnid2['name'];
                        break;
                    case 'cateParentName' :
                        $returnid2 = pdo_get(PDO_NAME . 'category_store', array('id' => $id), array('parentid'));
                        $returnid1 = pdo_get(PDO_NAME . 'category_store', array('id' => $returnid2['parentid']), array('name'));
                        $returnid = $returnid1['name'];
                        break;
                };
                break;
        }
        return $returnid;
    }

    /**
     * 查询单条数据
     *
     * @access static
     * @name  getSingelData($tablename,$array,$select='*')
     * @param $tablename  表名         'tg_member'
     * @param $where      查询条件  array('name'=>'qidada')
     * @param $select     查询字段  " id,name "
     * @return array
     */
    static function getSingelData($select, $tablename, $where) {
        $data = self::createStandardWhereString($where);
        return pdo_fetch("SELECT $select FROM " . tablename($tablename) . " WHERE $data[0] ", $data[1]);
    }

    /**
     * 查询多条数据
     *
     * @access static
     * @name  getNumData($tablename,$where,$page,$num,$order='id DESC',$isNeadPager = true,$select = '*')
     * @param $tablename  表名         'tg_member'
     * @param $where      查询条件  array('name'=>'qidada')
     * @param $select     查询字段  " id,name "
     * @param $pindex     分页查询页码
     * @param $psize      分页查询每页数量
     * @param $order      排序查询
     * @return $res array($data,$pager,$total) $data:查询的数据 $pager:分页结果 $total :数据总条数
     */
    static function getNumData($select, $tablename, $where, $order = 'id DESC', $pindex = 0, $psize = 0, $ifpage = 0) {
        global $_W;
        $data = self::createStandardWhereString($where);
        $countStr = "SELECT COUNT(*) FROM " . tablename($tablename) . " WHERE $data[0] ";
        $selectStr = "SELECT $select FROM " . tablename($tablename) . " WHERE $data[0] ";
        $res = self::getDataIfPage($countStr, $selectStr, $data[1], $pindex, $psize, $order, $ifpage);
        return $res;
    }

    /**
     * 查询数据共用方法
     *
     * @access static
     * @name  getDataIfPage
     * @param $tablename  表名         'tg_member'
     * @param $where      查询条件  array('name'=>'qidada')
     * @param $select     查询字段  " id,name "
     * @param $pindex     分页查询页码
     * @param $psize      分页查询每页数量
     * @param $order      排序查询
     * @return $res array($data,$pager,$total) $data:查询的数据 $pager:分页结果 $total :数据总条数
     */
    static function getDataIfPage($countStr, $selectStr, $params, $pindex = 0, $psize = 0, $order = 'id DESC', $ifpage = 0) {
        $pindex = max(1, intval($pindex));
        $total = $ifpage ? pdo_fetchcolumn($countStr, $params) : '';
        if ($psize > 0 && $ifpage) {
            $data = pdo_fetchall($selectStr . " ORDER BY $order " . " LIMIT " . ($pindex - 1) * $psize . ',' . $psize, $params);
        } else {
            $data = pdo_fetchall($selectStr . " ORDER BY $order", $params);
        }
        $pager = wl_pagination($total, $pindex, $psize);
        return array($data, $pager, $total);
    }

    /**
     * 创建标准查询条件字符串
     *
     * @access static
     * @name  createStandardWhereString($where)
     * @param $where      查询条件  array('name'=>'qidada')
     *        注：= ,>= ,<= <,>,@(模糊查询),#(in),^(or)必须紧挨字符 例：
     * $where = array('id'=>1,'createtime<'=>time(),'@name'=>'qidada','#status'=>(1,2,3),'name^mobile^address'=>'15756361007');
     * @return array
     */
    static function createStandardWhereString($where = array()) {
        global $_W;
        if (!is_array($where))
            return false;
        $where['uniacid'] = $where['uniacid'] > 0 ? $where['uniacid'] : $_W['uniacid'];
        $sql = '';
        foreach ($where as $k => $v) {
            $i = 0;
            if (isset($k) && $v === '')
                wl_message('存在异常参数' . $k);
            if (strpos($k, '>') !== false) {
                $k = trim(trim($k), '>');
                $eq = ' >= ';
            } elseif (strpos($k, '<') !== false) {
                $k = trim(trim($k), '<');
                $eq = ' <= ';
            } elseif (strpos($k, '@') !== false) {
                $eq = ' LIKE ';
                $k = trim(trim($k), '@');
                $v = "%" . $v . "%";
            } elseif (strpos($k, '#') !== false) {
                $i = 1;
                $eq = ' IN ';
                $k = trim(trim($k), '#');
            } elseif (strpos($k, '!=') !== false) {
                $i = 1;
                $eq = ' != ';
                $k = trim(trim($k), '!=');
            } elseif (strpos($k, '^') !== false) {
                $i = 2;
                $arr = explode("^", $k);
                $num = count($arr);
                $str = '(';
                for ($j = 0; $j < $num; $j++) {
                    if ($num - $j == 1) {
                        $str .= $arr[$j] . " LIKE  '%" . $v . "%'";
                    } else {
                        $str .= $arr[$j] . " LIKE  '%" . $v . "%'" . " or ";
                    }
                }
                $str .= ')';
            } elseif (strpos($k, '*') !== false) {
                $i = 2;
                $str = $v;
            } else {
                $eq = ' = ';
            }
            if ($i == 1) {
                $sql .= 'AND `' . $k . '`' . $eq . $v . ' ';
            } elseif ($i == 2) {
                $sql .= 'AND ' . $str;
            } else {
                if ($params[':' . $k]) {
                    $sql .= 'AND `' . $k . '`' . $eq . ':2' . $k . ' ';
                    $params[':2' . $k] = $v;
                } else {
                    $sql .= 'AND `' . $k . '`' . $eq . ':' . $k . ' ';
                    $params[':' . $k] = $v;
                }
            }

        }
        $sql = trim($sql, 'AND');
        return array($sql, $params);
    }

    /**
     * 将地区数组转化为标准地区数组
     *
     * @access static
     * @name changeAreaArray
     * @param $arr  带转化数组
     * @return array
     */
    static function changeAreaArray($arr) {
        $newarr = array();
        foreach ($arr as $key => $value) {
            $newarr[$value['id']]['title'] = $value['name'];
            $newarr[$value['id']]['cities'] = array();
            foreach ($value['children'] as $k => $v) {
                $newarr[$value['id']]['cities'][$v['id']]['title'] = $v['name'];
            }
        }
        return $newarr;
    }

    static function multi_array_sort($arrays, $sort_key, $sort_order = SORT_ASC, $sort_type = SORT_NUMERIC) {
        if (is_array($arrays)) {
            foreach ($arrays as $array) {
                if (is_array($array)) {
                    $key_arrays[] = $array[$sort_key];
                } else {
                    return false;
                }
            }
        } else {
            return false;
        }
        array_multisort($key_arrays, $sort_order, $sort_type, $arrays);
        return $arrays;
    }

    /**
     * Comment: 写入日志
     * @param string            $filename   文件名称
     * @param string            $path       日志路径
     * @param string|array|int  $filedata   日志内容
     * @param string            $name       日志标题名称
     * @param bool              $state      是否使用默认名称
     * @return string
     */
    static function wl_log($filename, $path, $filedata,$name = '',$state = true) {
        if($state){
            $url_log = $path . "log/" . date('Y-m-d', time()) . "/" . $filename . ".log";
            $url_dir = $path . "log/" . date('Y-m-d', time());
        }else{
            $url_log = $path.$filename . "/" . date('Y-m-d', time()) . "/" . $filename . ".log";
            $url_dir = $path.$filename . "/" . date('Y-m-d', time());
        }
        if (!is_dir($url_dir)) {
            mkdir($url_dir, 0777, true);
        }
        //写入日志
        if($name){
            $title = "/======= {$name} =====================================================================";
        }else{
            $title = '/=====================================================================================';
        }
        file_put_contents($url_log, var_export($title. date('Y-m-d H:i:s', time()) . '/', true) . PHP_EOL, FILE_APPEND);
        file_put_contents($url_log, var_export($filedata, true) . PHP_EOL, FILE_APPEND);
        return 'log_success';
    }

    /// 中国正常坐标系GCJ02协议的坐标，转到 百度地图对应的 BD09 协议坐标
    /// <param name="lat">维度</param>
    /// <param name="lng">经度</param>
    static function Convert_GCJ02_To_BD09($lat, $lng) {
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lng;
        $y = $lat;
        $z = sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) + 0.000003 * cos($x * $x_pi);
        $lng = $z * cos($theta) + 0.0065;
        $lat = $z * sin($theta) + 0.006;
        return array('lat' => $lat, 'lng' => $lng);
    }

    /// 百度地图对应的 BD09 协议坐标，转到 中国正常坐标系GCJ02协议的坐标
    /// <param name="lat">维度</param>
    /// <param name="lng">经度</param>
    static function Convert_BD09_To_GCJ02($lat, $lng) {
        $x_pi = 3.14159265358979324 * 3000.0 / 180.0;
        $x = $lng - 0.0065;
        $y = $lat - 0.006;
        $z = sqrt($x * $x + $y * $y) - 0.00002 * sin($y * $x_pi);
        $theta = atan2($y, $x) - 0.000003 * cos($x * $x_pi);
        $lng = $z * cos($theta);
        $lat = $z * sin($theta);
        return array('lat' => sprintf("%.6f", $lat), 'lng' => sprintf("%.6f", $lng));
    }

    //删除文件夹下所有文件
    static function deleteAll($path, $delall = '') {
        $op = dir($path);
        while (false != ($item = $op->read())) {
            if ($item == '.' || $item == '..') {
                continue;
            }
            if (is_dir($op->path . '/' . $item)) {
                self::deleteAll($op->path . '/' . $item);
                rmdir($op->path . '/' . $item);
            } else {
                unlink($op->path . '/' . $item);
            }
        }
        if ($delall == 1) {
            rmdir($path);
        }
    }

    //长链转短链
    static function long2short($url = '') {
        global $_W;
        if (empty($url)) {
            return FALSE;
        }
        if (!empty($_W['acid'])) {
            $account = WeAccount::create($_W['acid']);
        } else {
            $acid = pdo_fetchcolumn("SELECT acid FROM " . tablename('account_wechats') . " WHERE `uniacid`=:uniacid LIMIT 1", array(':uniacid' => $_W['uniacid']));
            $account = WeAccount::create($acid);
        }
        $result = $account->long2short($url);
        return $result;
    }

    /**
     *  表连接 分页
     * @param $tatal     int   总页数
     * @param $pageIndex int   当前页
     * @param $pageSize  int   一页多少条数据
     * */

    static function pagination($total, $pageIndex, $pageSize = 15, $url = '', $context = array('before' => 5, 'after' => 4, 'ajaxcallback' => '', 'callbackfuncname' => '')) {
        return 23;
//		$pdata = array('tcount' => 0, 'tpage' => 0, 'cindex' => 0, 'findex' => 0, 'pindex' => 0, 'nindex' => 0, 'lindex' => 0, 'options' => '');
//		if ($context['ajaxcallback']) {
//			$context['isajax'] = true;
//		}
//
//		if ($context['callbackfuncname']) {
//			$callbackfunc = $context['callbackfuncname'];
//		}
//
//		$pdata['tcount'] = $total;
//		$pdata['tpage'] = (empty($pageSize) || $pageSize < 0) ? 1 : ceil($total / $pageSize);
//		if ($pdata['tpage'] <= 1) {
//			return '';
//		}
//		$cindex = $pageIndex;
//		$cindex = min($cindex, $pdata['tpage']);
//		$cindex = max($cindex, 1);
//		$pdata['cindex'] = $cindex;
//		$pdata['findex'] = 1;
//		$pdata['pindex'] = $cindex > 1 ? $cindex - 1 : 1;
//		$pdata['nindex'] = $cindex < $pdata['tpage'] ? $cindex + 1 : $pdata['tpage'];
//		$pdata['lindex'] = $pdata['tpage'];
//
//		if ($context['isajax']) {
//			
//			$pdata['faa'] = 'href="javascript:;" page="' . $pdata['findex'] . '" ' . ($callbackfunc ? 'onclick="' . $callbackfunc . '(\'' . $url . '\', \'' . $pdata['findex'] . '\', this);return false;"' : '');
//			$pdata['paa'] = 'href="javascript:;" page="' . $pdata['pindex'] . '" ' . ($callbackfunc ? 'onclick="' . $callbackfunc . '(\'' . $url . '\', \'' . $pdata['pindex'] . '\', this);return false;"' : '');
//			$pdata['naa'] = 'href="javascript:;" page="' . $pdata['nindex'] . '" ' . ($callbackfunc ? 'onclick="' . $callbackfunc . '(\'' . $url . '\', \'' . $pdata['nindex'] . '\', this);return false;"' : '');
//			$pdata['laa'] = 'href="javascript:;" page="' . $pdata['lindex'] . '" ' . ($callbackfunc ? 'onclick="' . $callbackfunc . '(\'' . $url . '\', \'' . $pdata['lindex'] . '\', this);return false;"' : '');
//		} else {
//			if ($url) {
//				$pdata['faa'] = 'href="?' . str_replace('*', $pdata['findex'], $url) . '"';
//				$pdata['paa'] = 'href="?' . str_replace('*', $pdata['pindex'], $url) . '"';
//				$pdata['naa'] = 'href="?' . str_replace('*', $pdata['nindex'], $url) . '"';
//				$pdata['laa'] = 'href="?' . str_replace('*', $pdata['lindex'], $url) . '"';
//			} 
//		}
//
//		$html = '<div><ul class="pagination pagination-centered">';
//		if ($pdata['cindex'] > 1) {
//			$html .= "<li><a {$pdata['faa']} class=\"pager-nav\">首页</a></li>";
//			$html .= "<li><a {$pdata['paa']} class=\"pager-nav\">&laquo;上一页</a></li>";
//		}
//		if (!$context['before'] && $context['before'] != 0) {
//			$context['before'] = 5;
//		}
//		if (!$context['after'] && $context['after'] != 0) {
//			$context['after'] = 4;
//		}
//
//		if ($context['after'] != 0 && $context['before'] != 0) {
//			$range = array();
//			$range['start'] = max(1, $pdata['cindex'] - $context['before']);
//			$range['end'] = min($pdata['tpage'], $pdata['cindex'] + $context['after']);
//			if ($range['end'] - $range['start'] < $context['before'] + $context['after']) {
//				$range['end'] = min($pdata['tpage'], $range['start'] + $context['before'] + $context['after']);
//				$range['start'] = max(1, $range['end'] - $context['before'] - $context['after']);
//			}
//			for ($i = $range['start']; $i <= $range['end']; $i++) {
//				if ($context['isajax']) {
//					$aa = 'href="javascript:;" page="' . $i . '" ' . ($callbackfunc ? 'onclick="' . $callbackfunc . '(\'' . $url . '\', \'' . $i . '\', this);return false;"' : '');
//				} else {
//					if ($url) {
//						$aa = 'href="?' . str_replace('*', $i, $url) . '"';
//					} else {
//						$_GET['page'] = $i;
//						$aa = 'href="?' . http_build_query($_GET) . '"';
//					}
//				}
//				$html .= ($i == $pdata['cindex'] ? '<li class="active"><a href="javascript:;">' . $i . '</a></li>' : "<li><a {$aa}>" . $i . '</a></li>');
//			}
//		}
//
//		if ($pdata['cindex'] < $pdata['tpage']) {
//			$html .= "<li><a {$pdata['naa']} class=\"pager-nav\">下一页&raquo;</a></li>";
//			$html .= "<li><a {$pdata['laa']} class=\"pager-nav\">尾页</a></li>";
//		}
//		$html .= '</ul></div>';
//		return $html;
    }

    /**
     * Comment: 数据分页
     * Author: zzw
     * @param String $table 表的名称
     * @param number $page 当前页数 默认1
     * @param number $pageNum 每页的数量 默认10
     * @param array $where 查询条件
     * @param array $field 要查询的字段 默认全部字段
     * @param array $orderBy 要进行排序的字段
     * @return array  返回当前页的所有内容
     */
    static function paging($table, $page, $pageNum, $where = array(), $field = array(), $orderBy = array()) {
        global $_W;
        $count = implode(pdo_get(PDO_NAME . $table, $where, array('count(id)')));
        $pindex = max(1, intval($page));//页数
        $psize = $pageNum ? $pageNum : 10;//每页的数量
        $list = pdo_getslice(PDO_NAME . $table,
            $where, array(($pindex - 1) * $psize, $psize), $count, $field, '', $orderBy);
        return $list;
    }

    /**
     * Comment: 将本地图片提交到远程服务器端
     * Author: zzw
     */
    static function uploadImgServer($images) {
        global $_W, $_GPC;
        if ($_W['setting']['remote']['type'] > 0) {
            $attachurl = attachment_set_attach_url();
            foreach ($images as $k => $v) {
                WeliamWeChat::file_remote_upload($v);
                $images[$k] = $attachurl . $v;
            }
        }
    }

    /**
     * Comment: 将url地址转为path路径  格式：model/controller/function
     * Author: zzw
     * @param $url
     * @return string
     */
    static function urlRestore($url) {
        $p = self::cut("p=", '&ac', $url);
        $ac = self::cut("&ac=", '&do', $url);
        $doStr = explode('&do', $url);
        $do = self::cut("=", '&', $doStr[1]);
        //$urlRestore = $p . '/' . $ac . '/' . $do;
        $urlRestore = $p . '/' . $ac;
        return $urlRestore;
    }

    /**
     * Comment: 截取两个字符之间的字符
     * Author: zzw
     * @param $begin
     * @param $end
     * @param $str
     * @return string
     */
    static public function cut($begin, $end, $str) {
        $b = mb_strpos($str, $begin) + mb_strlen($begin);
        $e = mb_strpos($str, $end) - $b;
        return mb_substr($str, $b, $e);
    }


}
