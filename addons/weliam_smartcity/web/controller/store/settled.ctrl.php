<?php
defined('IN_IA') or exit('Access Denied');

class Settled_WeliamController{
    //入驻设置
    public function baseset(){
        global $_W,$_GPC;
        $register = Setting::wlsetting_read('register');
        if (checksubmit('submit')){
            $base = $_GPC['base'];
            $base['detail'] = htmlspecialchars_decode($base['detail']);
            $base['description'] = htmlspecialchars_decode($base['description']);
            Setting::wlsetting_save($base,'register');
            wl_message('设置成功', 'referer', 'success');
        }
        include wl_template('store/register');
    }
}