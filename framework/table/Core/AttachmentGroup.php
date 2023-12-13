<?php

namespace We7\Table\Core;

class AttachmentGroup extends \We7Table {
	protected $tableName = 'attachment_group';
	protected $primaryKey = 'id';
	protected $field = array(
		'pid',
		'name',
		'uniacid',
		'uid',
		'type',
	);
	protected $default = array(
		'pid' => '0',
		'name' => '',
		'uniacid' => '0',
		'uid' => '0',
		'type' => '0',
	);

	public function searchWithUniacidOrUid($uniacid, $uid) {
		if (empty($uniacid)) {
			$this->query->where('uniacid', 0)->where('uid', $uid);
		} else {
			$this->query->where('uniacid', $uniacid);
		}
		return $this;
	}

}