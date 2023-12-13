<?php
defined('IN_IA') or exit('Access Denied');
file_exists(__DIR__ . "/vendor/autoload.php") && require_once __DIR__ . "/vendor/autoload.php";
require_once __DIR__ . "/core/common/defines.php";
require_once PATH_CORE . "common/autoload.php";
Func_loader::core('global');

class Weliam_smartcityModuleSite extends WeModuleSite {

    public function __call($name, $arguments) {
        global $_W, $_GPC;
        $isWeb = stripos($name, 'doWeb') === 0;
        $isMobile = stripos($name, 'doMobile') === 0;
        $_W['catalog'] = $catalog = !empty($isWeb) ? 'sys' : 'app';
        $_W['plugin'] = $plugin = !empty($_GPC['p']) ? $_GPC['p'] : 'dashboard';
        $_W['controller'] = $controller = !empty($_GPC['ac']) ? $_GPC['ac'] : 'dashboard';
        $_W['method'] = $method = !empty($_GPC['do']) ? $_GPC['do'] : 'index';
        $_W['wlsetting'] = Setting::wlsetting_load();
        $_W['wlsetting']['trade']['credittext'] = $_W['wlsetting']['trade']['credittext'] ? $_W['wlsetting']['trade']['credittext'] : '积分';
        $_W['wlsetting']['trade']['moneytext'] = $_W['wlsetting']['trade']['moneytext'] ? $_W['wlsetting']['trade']['moneytext'] : '余额';
        if (!in_array($_W['method'], array('qrcodeimg', 'Notify', 'captcha')) && !in_array($_W['controller'], array('wxapp')) && $_GPC['r'] != 'api') {
            Func_loader::$catalog('cover');
        }
        if ($isWeb || $isMobile) {
            wl_new_method($plugin, $controller, $method, $_W['catalog']);
        }
        trigger_error("访问的模块 {$plugin} 不存在.", E_USER_WARNING);
        return null;
    }

    /**
     * Comment: 微信自动登录
     */
    public function doMobileWechatsign() {
        global $_W, $_GPC;
        $auth_userinfo = mc_oauth_userinfo();
        if (empty($auth_userinfo['openid'])) {
            die('授权信息获取失败，请退出重试');
        }
        $userinfo = array(
            'uid'      => intval(mc_openid2uid($auth_userinfo['openid'])),
            'openid'   => $auth_userinfo['openid'],
            'nickname' => $auth_userinfo['nickname'],
            'unionid'  => $auth_userinfo['unionid'],
            'avatar'   => $auth_userinfo['headimgurl']
        );
        $member = Member::wl_member_create($userinfo, 'wechat');
        $backurl = h5_url(urldecode($_GPC['vueurl']));
        $token = pdo_getcolumn(PDO_NAME.'login',array('token'=>$member['tokey'],'refresh_time >' => time()),'secret_key');
        if(empty($token)) {
            $res = Login::generateToken($member['tokey'], 'login');
            $token = $res['message'];
        }
        wl_setcookie('user_token',$token,86400);
        $url = $backurl ? $backurl: h5_url('pages/subPages/userCenter');

        echo "<script>window.location=\"" . $url . "\";</script>";
        exit;
    }

    /**
     * Comment: 通过分享链接进入平台时对链接的处理和跳转
     * Author: zzw
     * Date: 2019/9/23 15:23
     */
    public function doMobileReturnRequest() {
        global $_W, $_GPC;
        $link = $_GPC['link'] ?: h5_url('pages/mainPages/index/index');
        //链接转换操作
        $list = Links::getTransformationLink();
        foreach($list as $linkKey => $linkVal){
            $link = str_replace($linkKey,$linkVal,$link);
        }
        #1、判断link是否存在token信息，存在则删除
        $http = explode('?#', $link);
        $linkArr = explode('?', $http[1]);
        $paramsStr = $linkArr[1];
        $position = strpos($paramsStr, '&token=');//开始位置
        if ($position > 0) $linkArr[1] = substr($paramsStr, 0, $position)
            . substr($paramsStr, 39 + strlen(substr($paramsStr, 0, $position)), strlen($paramsStr));
        $link = $http[0] . '?#' . $linkArr[0] . '?' . $linkArr[1];
        #2、规避head_id=undefined的参数
        $link = str_replace('&head_id=undefined', '', $link);
        #3、跳转至分享链接

        header('Location:' . $link);
    }


}

class Weliam_smartcity1ModuleSite extends WeModuleSite {

    public function __call($name, $arguments) {
        global $_W, $_GPC;
        $isWeb = stripos($name, 'doWeb') === 0;
        $isMobile = stripos($name, 'doMobile') === 0;
        $_W['catalog'] = $catalog = !empty($isWeb) ? 'sys' : 'app';
        $_W['plugin'] = $plugin = !empty($_GPC['p']) ? $_GPC['p'] : 'dashboard';
        $_W['controller'] = $controller = !empty($_GPC['ac']) ? $_GPC['ac'] : 'dashboard';
        $_W['method'] = $method = !empty($_GPC['do']) ? $_GPC['do'] : 'index';
        $_W['wlsetting'] = Setting::wlsetting_load();
        $_W['wlsetting']['trade']['credittext'] = $_W['wlsetting']['trade']['credittext'] ? $_W['wlsetting']['trade']['credittext'] : '积分';
        $_W['wlsetting']['trade']['moneytext'] = $_W['wlsetting']['trade']['moneytext'] ? $_W['wlsetting']['trade']['moneytext'] : '余额';
        if (!in_array($_W['method'], array('qrcodeimg', 'Notify', 'captcha')) && !in_array($_W['controller'], array('wxapp')) && $_GPC['r'] != 'api') {
            Func_loader::$catalog('cover');
        }
        if ($isWeb || $isMobile) {
            wl_new_method($plugin, $controller, $method, $_W['catalog']);
        }
        trigger_error("访问的模块 {$plugin} 不存在.", E_USER_WARNING);
        return null;
    }

    /**
     * Comment: 微信自动登录
     */
    public function doMobileWechatsign() {
        global $_W, $_GPC;
        $auth_userinfo = mc_oauth_userinfo();
        if (empty($auth_userinfo['openid'])) {
            die('授权信息获取失败，请退出重试');
        }
        $userinfo = array(
            'uid'      => intval(mc_openid2uid($auth_userinfo['openid'])),
            'openid'   => $auth_userinfo['openid'],
            'nickname' => $auth_userinfo['nickname'],
            'unionid'  => $auth_userinfo['unionid'],
            'avatar'   => $auth_userinfo['headimgurl']
        );
        $member = Member::wl_member_create($userinfo, 'wechat');
        $backurl = h5_url(urldecode($_GPC['vueurl']));
        $token = pdo_getcolumn(PDO_NAME.'login',array('token'=>$member['tokey'],'refresh_time >' => time()),'secret_key');
        if(empty($token)) {
            $res = Login::generateToken($member['tokey'], 'login');
            $token = $res['message'];
        }
        wl_setcookie('user_token',$token,86400);
        $url = $backurl ? $backurl: h5_url('pages/subPages/userCenter');

        echo "<script>window.location=\"" . $url . "\";</script>";
        exit;
    }

    /**
     * Comment: 通过分享链接进入平台时对链接的处理和跳转
     * Author: zzw
     * Date: 2019/9/23 15:23
     */
    public function doMobileReturnRequest() {
        global $_W, $_GPC;
        $link = $_GPC['link'] ?: h5_url('pages/mainPages/index/index');
        //链接转换操作
        $list = Links::getTransformationLink();
        foreach($list as $linkKey => $linkVal){
            $link = str_replace($linkKey,$linkVal,$link);
        }
        #1、判断link是否存在token信息，存在则删除
        $http = explode('?#', $link);
        $linkArr = explode('?', $http[1]);
        $paramsStr = $linkArr[1];
        $position = strpos($paramsStr, '&token=');//开始位置
        if ($position > 0) $linkArr[1] = substr($paramsStr, 0, $position)
            . substr($paramsStr, 39 + strlen(substr($paramsStr, 0, $position)), strlen($paramsStr));
        $link = $http[0] . '?#' . $linkArr[0] . '?' . $linkArr[1];
        #2、规避head_id=undefined的参数
        $link = str_replace('&head_id=undefined', '', $link);
        #3、跳转至分享链接

        header('Location:' . $link);
    }

    public function doWebAdmin()
    {
        if (version_compare(PHP_VERSION, '7.2.5', '<')) {
            die('<br>检测到您到PHP版本过低, 系统无法使用<br><br>系统运行环境要求PHP版本不能低于 7.2.5<br><br>当前系统使用的PHP版本为: ' . PHP_VERSION);
        }
        //这个操作被定义用来呈现 管理中心导航菜单
        require __DIR__ . '/public/admin.php';
    }

}