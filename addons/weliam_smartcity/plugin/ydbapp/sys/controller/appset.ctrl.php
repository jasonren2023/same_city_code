<?php
defined('IN_IA') or exit('Access Denied');

class Appset_WeliamController {
    /**
     * Comment: APP设置
     */
	public function setting() {
		global $_W, $_GPC;
		$settings = Setting::wlsetting_read('wbappset');
		if (checksubmit('submit')) {
			$base = $_GPC['set'];
			$res1 = Setting::wlsetting_save($base, 'wbappset');
			wl_message('保存设置成功！', referer(), 'success');
		}
		include  wl_template('ydbapp/setting');
	}
}
