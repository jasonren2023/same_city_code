<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 账号管理
 * Author: zzw
 * Date: 2021/1/8 10:04
 * Class Userset_WeliamController
 */
class AgentSetAccount_WeliamController{
    //代理商账号管理
    public function profile()
    {
        global $_W , $_GPC;
        $user = pdo_get(PDO_NAME . 'agentusers' , ['uniacid' => $_W['uniacid'] , 'id' => $_W['aid']]);
        if (checksubmit('submit')) {
            if (empty($_GPC['pw']) || empty($_GPC['pw2'])) {
                wl_message('密码不能为空，请重新填写！' , 'referer' , 'error');
            }
            if ($_GPC['pw'] == $_GPC['pw2']) {
                wl_message('新密码与原密码一致，请检查！' , 'referer' , 'error');
            }
            $password_old = Util::encryptedPassword($_GPC['pw'] , $user['salt']);
            if ($user['password'] != $password_old) {
                wl_message('原密码错误，请重新填写！' , 'referer' , 'error');
            }
            $result  = '';
            $members = ['password' => Util::encryptedPassword($_GPC['pw2'] , $user['salt'])];
            $result  = pdo_update(PDO_NAME . 'agentusers' , $members , ['id' => $_W['aid']]);
            wl_message('修改成功！' , 'referer' , 'success');
        }
        include wl_template('agentset/profile');
    }
    //代理商分享信息设置
    public function shareSet(){
        global $_W,$_GPC;
        if (checksubmit('submit')) {
            $share = $_GPC['share'];
            Setting::agentsetting_save($share, 'share_set');

            wl_message('更新设置成功！',referer());
        }
        //获取设置信息
        $settings = Setting::agentsetting_read('share_set');;

        include wl_template('agentset/shareSet');
    }



}
