<?php
defined('IN_IA') or exit('Access Denied');

class Areaset_WeliamController {
    /**
     * Comment: 代理商设置
     * Author: zzw
     * Date: 2021/3/1 10:22
     */
    public function setting() {
        global $_W, $_GPC;
        $arealist = pdo_getall('wlmerchant_agentusers',array('uniacid' => $_W['uniacid'],'status' => 1),array('id','agentname'));
        if (checksubmit('submit')) {
            $data = $_GPC['data'];

            Setting::wlsetting_save($data,'areaset');
            wl_message('更新设置成功！',web_url('area/areaset/setting'));
        }
        //获取已经存在的设置信息
        $settings = Setting::wlsetting_read('areaset');
        include wl_template('area/areasetting');
    }

}