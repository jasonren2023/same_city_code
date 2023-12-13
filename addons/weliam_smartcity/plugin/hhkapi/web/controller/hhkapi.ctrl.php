<?php
defined('IN_IA') or exit('Access Denied');

class Hhkapi_WeliamController {

    public function hhkapiset() {
        global $_W, $_GPC;

        if (checksubmit('submit')) {
            $base = $_GPC['base'];
            Setting::agentsetting_save($base, 'hhkapi');
            wl_message('保存设置成功！', referer(), 'success');
        }
        $base = Setting::agentsetting_read('hhkapi');
        $viptypelist = pdo_getall('wlmerchant_halfcard_type',array('uniacid' => $_W['uniacid']),array('id','name'));

        include wl_template('hhkapi/hhkapiset');
    }
}