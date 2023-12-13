<?php
defined('IN_IA') or exit('Access Denied');

class Set_WeliamController {

    function base() {
        global $_W, $_GPC;
        if (checksubmit('submit')) {
            $base = $_GPC['base'];
            Setting::agentsetting_save($base, 'rush');
            wl_message('保存设置成功！', referer(), 'success');
        }
        $base = Setting::agentsetting_read('rush');
        //获取社群
        $communitylist = pdo_getall('wlmerchant_community', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), array('id', 'communname'));

        include wl_template('set/base');
    }
}