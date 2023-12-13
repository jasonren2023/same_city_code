<?php 
defined('IN_IA') or exit('Access Denied');

class Openapi {
	
	static function apiurl($do) {
		global $_W, $_GPC;
		$settings = Setting::wlsetting_read('apiset');
		return $_W['siteroot'] . 'addons/'.MODULE_NAME.'/core/common/openapi.php?i=' . $_W['uniacid'] . "&do=" . $do . "&token=" . $settings['token'];
	}
}