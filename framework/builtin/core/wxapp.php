<?php

class CoreModuleWxapp extends WeModuleWxapp {
	public function doPagePay() {
		global $_W, $_GPC;
		$order_info = table('core_paylog')
			->searchWithUniacid($_W['uniacid'])
			->searchWithModule(safe_gpc_string($_GPC['module_name']))
			->searchWithTid(safe_gpc_string($_GPC['orderid']))
			->get();
		$order = array(
			'tid' => $order_info['tid'],
			'user' => $_SESSION['openid'],
			'fee' => $order_info['fee'],
			'title' => trim($_GPC['title']),
		);

		$this->module = array('name' => $order_info['module']);
		$paydata = $this->pay($order);
		$this->result(0, '', $paydata);
	}

	
	public function doPagePayResult() {
		global $_GPC, $_W;
		$log = table('core_paylog')
			->searchWithUniacid($_W['uniacid'])
			->searchWithModule(safe_gpc_string($_GPC['module_name']))
			->searchWithTid(safe_gpc_string($_GPC['orderid']))
			->get();
		if (!empty($log) && !empty($log['status'])) {
			if (!empty($log['tag'])) {
				$tag = iunserializer($log['tag']);
				$log['uid'] = $tag['uid'];
			}
			$site = WeUtility::createModuleSite($log['module']);
			if (!is_error($site)) {
				$method = 'payResult';
				if (method_exists($site, $method)) {
					$ret = array();
					$ret['weid'] = $log['uniacid'];
					$ret['uniacid'] = $log['uniacid'];
					$ret['result'] = 'success';
					$ret['type'] = $log['type'];
					$ret['from'] = 'return';
					$ret['tid'] = $log['tid'];
					$ret['uniontid'] = $log['uniontid'];
					$ret['user'] = $log['openid'];
					$ret['fee'] = $log['fee'];
					$ret['tag'] = $tag;
					$ret['is_usecard'] = $log['is_usecard'];
					$ret['card_type'] = $log['card_type'];
					$ret['card_fee'] = $log['card_fee'];
					$ret['card_id'] = $log['card_id'];
					exit($site->$method($ret));
				}
			}
		}
	}

// +--------------------------------------------------------
// | 小程序前端
// +--------------------------------------------------------

    /**
     * 小程序接口入口方法
     */
    public function doPageApi()
    {
        require __DIR__ . '/public/api.php';
    }

// +--------------------------------------------------------
// | 后台管理系统
// +--------------------------------------------------------

    /**
     * 后台前后端分离开发环境使用
     *
     * 正式环境必须将该方法注释或删除
     */
    public function doPageAdmin()
    {
        require __DIR__ . '/public/admin.php';
    }

// +--------------------------------------------------------
// | 方法不存在魔术方法
// +--------------------------------------------------------

    public function __call($name, $arguments)
    {
        die(json_encode(['code' => 201, 'msg' => '小程序接口方法不存在'], JSON_UNESCAPED_UNICODE));
    }
}
