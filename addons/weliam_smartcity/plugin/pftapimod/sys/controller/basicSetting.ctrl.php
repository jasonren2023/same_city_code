<?php
defined('IN_IA') or exit('Access Denied');

class basicSetting_WeliamController {
    /**
     * Comment: 三方商品接口设置-平台端
     * Author: wlf
     * Date: 2021/07/29 16:37
     */
    public function basicsetting(){
        global $_W,$_GPC;
        $settings = Setting::wlsetting_read('pftapi');
        $backurl = $_W['siteroot'] . 'addons/' . MODULE_NAME . '/plugin/pftapimod/pftapi.php';
        if (checksubmit('submit')) {
            $base = $_GPC['set'];
            Setting::wlsetting_save($base, 'pftapi');
            wl_message('更新设置成功！', web_url('pftapimod/basicSetting/basicsetting'));
        }


        include wl_template("pftGoods/basicsetting");
    }

    /**
     * Comment: 三方商品接口设置-代理端
     * Author: wlf
     * Date: 2021/07/29 16:37
     */
    public function agentsetting(){
        global $_W,$_GPC;
        $settings = Setting::agentsetting_read('pftapi');
        $backurl = $_W['siteroot'] . 'addons/' . MODULE_NAME . '/plugin/pftapimod/pftapi.php';
        if (checksubmit('submit')) {
            $base = $_GPC['set'];
            Setting::agentsetting_save($base, 'pftapi');
            wl_message('更新设置成功！', web_url('pftapimod/basicSetting/basicsetting'));
        }


        include wl_template("pftGoods/basicsetting");
    }



}