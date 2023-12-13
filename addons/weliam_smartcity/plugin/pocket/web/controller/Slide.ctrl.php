<?php
defined('IN_IA') or exit('Access Denied');

class Slide_WeliamController {

	function lists() {
		global $_W, $_GPC;

		$uniacid = $_W['uniacid'];
		$list1 = Pocket::getslides($uniacid);
		
		$list = $list1[0];
		$pager = $list1[1];
		include  wl_template('pocket/slidelist');

	}

	function operating() {
		global $_W, $_GPC;
		$did = $_GPC['did'];
		$eid = $_GPC['id'];
		$temp = $_GPC['data'];

		if (!empty($did)) {
			pdo_delete(PDO_NAME . 'pocket_slide', array('id' => $did));
			wl_message('删除数据成功!', web_url('pocket/Slide/lists'), 'success');
		}
		if (!empty($eid)) {
			$data = Util::getSingelData("*", PDO_NAME . 'pocket_slide', array('id' => $eid));
		}
		
		if($temp){
			$temp['uniacid'] = $_W['uniacid'];
			$temp['aid'] = $_W['aid'];
			if($temp['id']){
				pdo_update(PDO_NAME.'pocket_slide',$temp,array('id'=>$temp['id']));
			}else{
				pdo_insert(PDO_NAME.'pocket_slide',$temp);
			}
			wl_message('操作成功!',web_url('pocket/Slide/lists'),'success');
		}
		include  wl_template('pocket/slideadd');
	}

    /**
     * Comment: 修改状态
     * Author: zzw
     * Date: 2019/9/18 17:07
     */
    public function changeStatus (){
        global $_W , $_GPC;
        #1、获取参数信息
        $id = $_GPC['id'] OR Commons::sRenderError('缺少参数：id');
        $status = $_GPC['status'] ? : 0;
        #1、修改操作
        $res = pdo_update(PDO_NAME."pocket_slide",['status'=>$status],['id'=>$id]);
        if($res) Commons::sRenderSuccess('修改成功');
        else Commons::sRenderError('修改失败，请刷新重试!');
    }


}