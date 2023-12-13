<?php
defined('IN_IA') or exit('Access Denied');

class Rank_WeliamController {

	public function rank_list() {
		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 15;
		$lists = pdo_getslice(PDO_NAME . 'rank', array('uniacid' => $_W['uniacid']), array($pindex, $psize), $total, array(), '', "id DESC");
		$pager = wl_pagination($total, $pindex, $psize);

		include    wl_template('ranklist/rank_list');
	}

	public function rank_edit() {
		global $_W, $_GPC;
		if (checksubmit()) {
			if (empty($_GPC['name'])) {
				wl_message('请填写排行榜名称');
			}
			$data = array(
				'uniacid' => $_W['uniacid'],
				'name' => $_GPC['name'],
				'type' => $_GPC['type'],
				'orderby' => $_GPC['orderby'],
				'status' => $_GPC['status'] == 'on' ? 1 : 0,
                'number' => $_GPC['number']?$_GPC['number']:10,
                'createtime' => time()
			);

			if (empty($_GPC['id'])) {
				pdo_insert(PDO_NAME . 'rank', $data);
			} else {
				pdo_update(PDO_NAME . 'rank', $data, array('id' => $_GPC['id']));
			}
			wl_message('编辑排行榜成功', web_url('ranklist/rank/rank_list'), 'success');
		}
		$rank = pdo_get(PDO_NAME . 'rank', array('uniacid' => $_W['uniacid'], 'id' => $_GPC['id']));
		include  wl_template('ranklist/rank_edit');
	}

	public function rank_del() {
		global $_W, $_GPC;
		if ($_GPC['id']) {
			pdo_delete(PDO_NAME . 'rank', array('uniacid' => $_W['uniacid'], 'id' => $_GPC['id']));
            show_json(1);
		}else{
            show_json(0,'删除失败');
        }
	}

}
