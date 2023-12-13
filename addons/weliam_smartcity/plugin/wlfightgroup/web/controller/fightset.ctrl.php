<?php
defined('IN_IA') or exit('Access Denied');

class Fightset_WeliamController {

    function fightgroupset() {
        global $_W, $_GPC;
        if (checksubmit('submit')) {
            $set = $_GPC['set'];
            Setting::agentsetting_save($set, 'fightgroup');
            wl_message('保存设置成功！', referer(), 'success');
        }
        $set = Setting::agentsetting_read('fightgroup');
        $communitylist = pdo_getall('wlmerchant_community', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), array('id', 'communname'));

        include wl_template('fightset/fightgroupset');
    }
}
