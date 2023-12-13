<?php
defined('IN_IA') or exit('Access Denied');

class Apilist_WeliamController {
	
	public function apimanage(){
		global $_W, $_GPC;
		
		$disapis = array(
			array(
				'name' => '添加分销商',
				'url' => Openapi::apiurl('adddisor'),
				'request_data' => array('openid|String|是|公众号粉丝openid', 'nickname|String|是|公众号粉丝昵称', 'avatar|String|是|公众号粉丝头像'),
				'response_data' => array('id|Number|否|分销商ID'),
				'response' => array('errno' => 0, 'message' => '添加成功', 'data' => array('id' => 1)),
				'error_response' => array('errno' => -1, 'message' => '无效Token，请检查后再试', 'data' => array())
			),
			array(
				'name' => '修改分销商余额',
				'url' => Openapi::apiurl('changediscash'),
				'request_data' => array('openid|String|是|公众号粉丝openid', 'money|Number|是|变更金额，负数为减少，正数为增加', 'reason|String|是|余额变更说明'),
				'response_data' => array(),
				'response' => array('errno' => 0, 'message' => '修改成功', 'data' => array()),
				'error_response' => array('errno' => -1, 'message' => '无效Token，请检查后再试', 'data' => array())
			),
		);
		
		include  wl_template('apilist/apimanage');
	}
	
	public function apiset(){
		global $_W, $_GPC;
		
		$settings = Setting::wlsetting_read('apiset');
		if (checksubmit('submit')) {
			Setting::wlsetting_save(array('token' => trim($_GPC['apitoken'])), 'apiset');
			wl_message('更新设置成功！', web_url('openapi/apilist/apiset'));
		}
		
		include  wl_template('apilist/apiset');
	}
	
}