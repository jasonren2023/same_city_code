<?php
defined('IN_IA') or exit('Access Denied');

class Consumptionset_WeliamController {

	public function consumptionapi() {
		global $_W, $_GPC;
		if (checksubmit('submit')) {
			$base = $_GPC['set'];
			Setting::wlsetting_save($base, 'consumption');
			wl_message('保存设置成功！', referer(), 'success');
		}
		//获取设置信息
        $settings = Setting::wlsetting_read('consumption');
		//获取社群信息
        $comList = pdo_getall(PDO_NAME."community",['uniacid'=>$_W['uniacid'],'aid'=>0]);

		include  wl_template('consumption/consumptionapi');
	}
	
}