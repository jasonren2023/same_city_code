<?php

namespace We7\Table\Users;

class Permission extends \We7Table {
	protected $tableName = 'users_permission';
	protected $primaryKey = 'id';
	protected $field = array(
		'uniacid',
		'uid',
		'type',
		'permission',
		'url',
	);
	protected $default = array(
		'uniacid' => '',
		'uid' => '',
		'type' => '',
		'permission' => '',
		'url' => '',
	);

	public $notModuleTypes = array(
		PERMISSION_ACCOUNT,
		PERMISSION_WXAPP,
		PERMISSION_WEBAPP,
		PERMISSION_PHONEAPP,
		PERMISSION_ALIAPP,
		PERMISSION_BAIDUAPP,
		PERMISSION_TOUTIAOAPP,
		PERMISSION_SYSTEM,
		PERMISSION_MODULES
	);

	public function getUserPermissionByType($uid, $uniacid, $type = '') {
		$this->query->where('uid', $uid)->where('uniacid', $uniacid);
		if (!empty($type)) {
			$this->query->where('type', $type);
		}
		$result = $this->query->get();
		if (!empty($result['permission'])) {
			$result['permission'] = explode('|', $result['permission']);
		}
		return $result;
	}

	public function getAllUserPermission($uid, $uniacid) {
		return $this->query->where('uid', $uid)
					->where('uniacid', $uniacid)->getall('type');
	}
	public function getAllUserModulePermission($uid, $uniacid = 0) {
		if (!empty($uniacid)) {
			$this->query->where('uniacid', $uniacid);
		}
		return $this->query->where('uid', $uid)
			->where('type !=', array(PERMISSION_ACCOUNT, PERMISSION_WXAPP, PERMISSION_WEBAPP, PERMISSION_PHONEAPP, PERMISSION_ALIAPP, PERMISSION_BAIDUAPP, PERMISSION_TOUTIAOAPP, PERMISSION_SYSTEM))->getall('type');
	}

	public function getClerkPermission($module) {
		global $_W;
		return $this->query->from('users_permission', 'p')->leftjoin('uni_account_users', 'u')->on(array('u.uid' => 'p.uid', 'u.uniacid' => 'p.uniacid'))->where('u.role', 'clerk')->where('p.type', $module)->where('u.uniacid', $_W['uniacid'])->getall('uid');
	}

	public function getClerkPermissionList($uniacid = 0, $uid = 0, $username = '') {
		$this->query->from('users_permission', 'p')
			->select('p.*')
			->leftjoin('uni_account_users', 'u')
			->on(array('u.uid' => 'p.uid', 'u.uniacid' => 'p.uniacid'))
			->where('u.role', 'clerk');

		if (!empty($uniacid)) {
			$this->query->where('u.uniacid', $uniacid);
		}
		if (!empty($uid)) {
			$this->query->where('u.uid', $uid);
		}
		if (!empty($username)) {
			$this->query->leftjoin('users', 's')
				->on(array('s.uid' => 'p.uid'))
				->where('s.username like', "%$username%");
		}
		if (empty($module)) {
			$this->query->where('p.type !=', $this->notModuleTypes);
		} else {
			$this->query->where('p.type', $module);
		}
		return $this->query->getall();
	}

	public function searchWithUniAccountUsers() {
		return $this->query->from($this->tableName, 'p')
			->leftjoin('uni_account_users', 'u')
			->on(array(
				'p.uid' => 'u.uid',
				'p.uniacid' => 'u.uniacid'
			));
	}
}