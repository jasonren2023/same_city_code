<?php
defined('IN_IA') or exit('Access Denied');

class Storelogin_WeliamController {
    /**
     * Comment: 进入商户登录页面(扫码登录)
     * Author: zzw
     * Date: 2019/10/30 11:08
     */
    public function store_login() {
        global $_W, $_GPC;
        #1、建立储存字段信息 值为-1(-1=初始化;-2=取消登录;-3=二维码失效;1=扫描成功;2=确认登录)
        $onlyKey = md5(uniqid(microtime(true), true));
        $time = intval(180);//3分钟过期
        $endTime = time() + $time;//到期时间
        $data = [
            'status'   => -1,
            'end_time' => $endTime
        ];
        Cache::setCache('storeLoginInfo', $onlyKey, json_encode($data));
        #2、判断公众号是否存在
        if(p('wxplatform')) {
            $wechat = 1;
            //获取微信公众号二维码信息
            $path = h5_url('pages/subPages/merchant/storePcLogin/storePcLogin', ['only_key' => $onlyKey]);
        } else {
            $wechat = 0;
        }
        #3、判断小程序是否存在
        $weapp = 0;
        if(p('wxapp')) {
            //获取小程序二维码信息
            $weAppPath =  WeApp::getQrCode('pages/subPages/merchant/storePcLogin/storePcLogin?only_key='.$onlyKey,'store_login_'.$onlyKey.'.png');//保存名称));
            if(is_string($weAppPath) && $weAppPath){
                $weapp = 1;
                $weAppPath = tomedia($weAppPath);
            }
        }

        include wl_template('user/store_login');
    }
    /**
     * Comment: 更新二维码过期时间
     * Author: zzw
     * Date: 2019/11/7 14:57
     */
    public function changeEndTime(){
        global $_W,$_GPC;
        #1、参数获取
        $onlyKey = $_GPC['only_key'] OR Commons::sRenderError('请刷新重试');
        #2、修改过期时间
        $loginInfo = json_decode(Cache::getCache('storeLoginInfo', $onlyKey), true);
        #3、更新到期时间
        $time = intval(180);//3分钟过期
        $endTime = time() + $time;//到期时间
        $data = [
            'status'   => -1,
            'end_time' => $endTime
        ];
        Cache::setCache('storeLoginInfo', $onlyKey, json_encode($data));

        Commons::sRenderSuccess('新的过期信息',$data);
    }
    /**
     * Comment: 获取商户登录状态
     * Author: zzw
     * Date: 2019/10/30 17:08
     */
    public function getLoginStatus() {
        global $_W, $_GPC;
        #1、参数获取
        $onlyKey = $_GPC['only_key'] OR Commons::sRenderError('请刷新重试');
        #2、获取当前用户的扫描状态
        $loginInfo = json_decode(Cache::getCache('storeLoginInfo', $onlyKey), true);
        #3、判断储存内容是否正确
        if (!is_array($loginInfo)) Commons::sRenderSuccess('登录状态', ['status' => intval(-3)]);
        #4、判断是否过期
        if ($loginInfo['end_time'] <= time()) Commons::sRenderSuccess('登录状态', ['status' => intval(-3)]);

        if ($loginInfo['status'] == 2) {
            $cookie = array();
            $cookie['mid'] = $loginInfo['mid'];
            $cookie['uniacid'] = $_W['uniacid'];
            $session = base64_encode(json_encode($cookie));
            isetcookie('__wlstore_session', $session, 7 * 86400, true);
        }

        Commons::sRenderSuccess('登录状态', ['status' => intval($loginInfo['status']), 'mid' => intval($loginInfo['mid'])]);
    }
    /**
     * Comment: 通过用户mid获取用户名下的所有店铺
     * Author: zzw
     * Date: 2019/10/31 14:43
     */
    public function storeLogin() {
        global $_W, $_GPC;
        #1、获取当前用户的所有店铺列表
        $list = pdo_fetchall("SELECT a.name,b.id,b.storename,b.logo,b.endtime,a.ismain FROM "
            . tablename(PDO_NAME . "merchantuser") . " as a RIGHT JOIN " . tablename(PDO_NAME . "merchantdata")
            . " as b ON a.storeid = b.id WHERE a.mid = {$_W['mid']} AND a.enabled = 1 AND b.enabled = 1 AND a.ismain != 2 GROUP BY a.storeid ORDER BY a.createtime ASC ");
        #1、循环处理商户信息
        if (empty($list)) {
            wl_message("您没有可管理商户", web_url('user/storelogin/storeLogin', ['mid' => $_W['mid']]), 'fixed');
        }
        //检测认证功能
        if(p('attestation')){
            $attset = Setting::wlsetting_read('attestation');
        }else{
            $attset = [];
        }

        foreach ($list as $key => &$val) {
            //处理logo图
            $val['logo'] = tomedia($val['logo']);
            //处理店铺到期时间
            $val['endtime'] = $val['endtime'] > time() ? floor(($val['endtime']-time())/86400) . '天后到期' : '已到期';
            //处理店员类型
            if ($val['ismain'] == 1) {
                $val['ismain'] = '店长';
            } else if ($val['ismain'] == 3) {
                $val['ismain'] = '管理员';
            } else if ($val['ismain'] == 4) {
                $val['ismain'] = '业务员';
            }
            //认证
            if($attset['attmanage'] == 1){
                $attestation = Attestation::checkAttestation(2,$val['id']);
                if($attestation['attestation'] != 2){
                    $val['noatt'] = 1;
                }
            }

        }

        include wl_template('user/select_shop');
    }

    public function on_store() {
        global $_W, $_GPC;
        $this->select_store(intval($_GPC['sid']));
    }

    public function logout() {
        isetcookie('__wlstore_session', '', -10000);
        isetcookie('__wlstoreid_session', '', -10000);
        header('Location:' . web_url('user/storelogin/store_login'));
    }

    private function select_store($storeid) {
        isetcookie('__wlstoreid_session', $storeid, 7 * 86400, true);
        header('Location:' . web_url('dashboard/dashboard'));
    }
}
