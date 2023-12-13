<?php
defined('IN_IA') or exit('Access Denied');

use EasyWeChat\Factory;

/**
 * Comment: 登录模型
 * Author: zzw
 * Class Login
 */
class Login {
    # 'loginSource1Mode2'=公众号微信登录；
    # 'loginSource2Mode1'=H5账号密码登录；
    # 'loginSource3Mode2'=小程序微信登录；

    /**
     * Comment: 公众号微信登录 —— 等待对接
     * Author: zzw
     * Date: 2019/10/22 11:32
     * @param $data
     * @return mixed
     */
    public function loginSource1Mode2($data) {
        global $_W;
        // $data['vue_url'] = 'https://citydev.weliam.com.cn/addons/weliam_smartcity/h5/#/pages/mainPages/userCenter/userCenter?i=26';
        #1、获取基本配置信息
        $set = Util::object_array($_W['account']);
        if ($data['request'] == 'get') {
            #2、生成返回地址并且进行对应的编码
            $vueUrl = urlencode($data['vue_url']);//将返回地址进行编码
            $backUrl = urlencode($_W['siteroot'] . "addons/".MODULE_NAME."/core/common/uniapp.php?i="
                . $_W['uniacid'] . "&areaid={$data['areaid']}&p=member&do=login&source=1&mode=2&back_url={$vueUrl}");
            #2、发送获取code的链接请求
            $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$set['key']}&redirect_uri={$backUrl}&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";

            header("Location:" . $url);
            exit;
        }
        #3、获取返回的信息 进行用户登录授权的操作
        $code = $data['code'];
        #4、判断当前code是否已被使用 已被使用则从新获取code
        if (Cache::getCache($code, $code)) {
            $data['request'] = 'get';
            self::loginSource1Mode2($data);
            die;
        }
        Cache::setCache($code, $code, $code);//储存code信息
        #5、获取用户token信息
        $tokenUrl = "https://api.weixin.qq.com/sns/oauth2/access_token";
        $tokenParams = [
            'appid'      => $set['key'],
            'secret'     => $set['secret'],
            'code'       => $code,
            'grant_type' => 'authorization_code',
        ];
        $tokenInfo = curlPostRequest($tokenUrl, $tokenParams);
        #6、获取用户信息
        $userUrl = "https://api.weixin.qq.com/sns/userinfo";
        $userParams = [
            'access_token' => $tokenInfo['access_token'],
            'openid'       => $tokenInfo['openid'],
            'lang'         => 'zh_CN',
        ];
        $userInfo = curlPostRequest($userUrl, $userParams);

        return $userInfo;
    }
    /**
     * Comment: H5账号密码登录 —— 等待对接
     * Author: zzw
     * Date: 2019/10/22 9:38
     * @param $data
     */
    public function loginSource2Mode1($data) {
        global $_W;
        #1、参数获取
        $type = $data['type'] ? $data['type'] : 1;//1=账号密码登录 2=短信验证码登录
        $phone = $data['phone'];//手机号
        $pwd = $data['password'];// 登录密码/短信验证码
        $backUrl = urldecode($data['backurl']);
        if (!$phone) Commons::sRenderError('请输入登录账号');
        if (!$pwd) Commons::sRenderError($type == 1 ? '请输入密码' : '请输入验证码');
        #2、根据登录方式进行判断是否登录成功
        $member = pdo_get(PDO_NAME . "member", ['mobile' => $phone], ['password', 'id', 'salt', 'tokey', 'openid']);
        if (!$member) Commons::sRenderError('账号不存在，请先注册!');
        if ($type == 1) {
            //账号密码登录  判断密码是否正确
            if ($member['password'] != md5($pwd . $member['salt'])) Commons::sRenderError('密码错误!');
        } else {
            //短信验证码登录  判断验证码是否输入正确
            $pin_info = pdo_get('wlmerchant_pincode',array('mobile' => $phone));
            if(empty($pin_info)){
                $this->renderError('验证码错误');
            }
            if($pin_info['time'] < time() - 300){
                $this->renderError('验证码已过期，请重新获取',array('code'=>1));
            }
            if ($pwd != $pin_info['code']) Commons::sRenderError('验证码错误!');
        }
        #3、密码输出正确 成功登录
        $userInfo = [
            'mobile' => $phone,
            'pwd'    => $pwd,
            'openid' => $member['openid'] ? $member['openid'] : $_W['wlmember']['openid'],
            'tokey'  => $member['tokey'] ? $member['tokey'] : $_W['wlmember']['tokey'],
        ];
        wl_setcookie('usersign', $userInfo, 3600 * 24 * 30);
        wl_setcookie('user_token', $userInfo['tokey'], 3600 * 24 * 30);
        wl_setcookie('exitlogin_code', [], 100);
        #3、登录成功 返回跳转地址
        $link = $backUrl ? $backUrl : '';


        Commons::sRenderSuccess('登录成功', ['back_url' => $link, 'token' => $userInfo['tokey']]);
    }
    /**
     * Comment: 小程序微信登录
     * Author: zzw
     * Date: 2019/10/29 16:33
     * @param $data
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    static function loginSource3Mode2($data) {
        global $_W;
        if (!$data['code']) {
            return error(1, '错误的code信息!');
        }
        #1、获取微信小程序设置项 并且配置基本信息
        if (is_array($_W['wlsetting']['wxapp_config']) && count($_W['wlsetting']['wxapp_config']) > 0) {
            $set = $_W['wlsetting']['wxapp_config'];
        } else {
            $set = Setting::wlsetting_read('wxapp_config');
        }
        $config = [
            'app_id' => $set['appid'],
            'secret' => $set['secret'],
        ];
        #3、请求获取用户信息
        try {
            $app = Factory::miniProgram($config);
            $userInfo = $app->auth->session($data['code']);
            return $userInfo;
        } catch (Exception $e) {
            //错误抛出
            return error(1, $e->getMessage());
        }
    }




    public static function generateToken($token,$type = ''){
        //200：成功          登陆成功/访问成功
        //401：未授权        重新登陆
        //205：刷新token     访问成功，但是刷新秘钥


        #token  为用户储存在数据中的token  唯一不变
        #secretKey  为用户登陆秘钥，需要返回给移动端



        #1、基本信息设置
        $keyName = 'jwt_info';
        if($type === 'login'){
            $secretKey = self::createToken($token);
            if($secretKey['errno'] == 401){
                return error(401);
            }

            return error(200,$secretKey);
        }else{
            #1、访问请求操作  获取秘钥信息  根据状态返回信息
            $loginDescInfo = pdo_get('wlmerchant_login',array('secret_key' => $token));
            //授权信息不存在  请重新登陆
            if(is_array($loginDescInfo)){
                if(empty($loginDescInfo['token'])){
                    file_put_contents(PATH_DATA . "login_error.log", var_export($loginDescInfo, true) . PHP_EOL, FILE_APPEND);
                    return error(401);
                }
                if($loginDescInfo['refresh_time'] > time()){
                    //未到刷新信息 访问请求有效 继续下一步操作
                    return error(200,$loginDescInfo['token']);
                }else if($loginDescInfo['refresh_time'] <= time() && $loginDescInfo['end_time'] > time()){
                    //超过刷新时间  但是未过期  刷新访问秘钥 然后返回新的秘钥
                    $secretKey = self::createToken($loginDescInfo['token']);
                    if($secretKey['errno'] == 401){
                        file_put_contents(PATH_DATA . "login_error.log", var_export('刷新失败', true) . PHP_EOL, FILE_APPEND);
                        return error(401);
                    }
                    return error(205,$secretKey);
                }else{
                    //其他情况 统一视为登陆过期  请重新登陆
                    file_put_contents(PATH_DATA . "login_error.log", var_export('其他情况', true) . PHP_EOL, FILE_APPEND);

                    return error(401);
                }
            }
        }
        file_put_contents(PATH_DATA . "login_error.log", var_export('更多其他情况', true) . PHP_EOL, FILE_APPEND);
        return error(401);
    }


    protected static function createToken($token){
        //登陆操作  生成登陆秘钥
        if(empty($token)){
            return error(401);
        }
        $data = [
            'token'        => $token ,//用户token  唯一
            'secret_key'   => md5($token . time()) ,//用户秘钥
            'refresh_time' => time() + (86400 * 30) ,//秘钥刷新时间
            'end_time'     => time() + (86400 * 60) ,//秘钥过期时间
        ];
        pdo_delete('wlmerchant_login',array('token'=>$token));
        pdo_insert(PDO_NAME . 'login', $data);
        return $data['secret_key'];
    }









}

