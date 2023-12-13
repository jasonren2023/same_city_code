<?php
defined('IN_IA') or exit('Access Denied');

class Adv_WeliamController{
	/**
	 * 幻灯片查询
	 */
	public function index(){
		global $_W,$_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$adves = Dashboard::getAllAdv($pindex-1,$psize,'',$_GPC['type'],$_GPC['keyname']);
		$advs = $adves['data'];
		foreach ($advs as $k => &$val) {
			if (!empty($val['cateid'])) {
				$val['catename'] = pdo_getcolumn('wlmerchant_groupon_category', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid'], 'id' => $val['cateid']), 'name');
			}
		}
		$pager = wl_pagination($adves['count'], $pindex, $psize);
		include wl_template('dashboard/advIndex');
	}
	/**
	 * 编辑幻灯片
	 */
	 public function edit(){
	 	global $_W,$_GPC;
		$cateid = intval($_GPC['cateid']);
		if(checksubmit('submit')){
			$adv = $_GPC['adv'];
			$adv['advname'] = trim($adv['advname']);
			$adv['displayorder'] = intval($adv['displayorder']);
			$adv['enabled'] = intval($_GPC['enabled']);
			if(!empty($_GPC['id'])){
				if(Dashboard::editAdv($adv,$_GPC['id'])) wl_message('保存成功',web_url('dashboard/adv/index'),'success');
			}else{
				$adv['cateid'] = $cateid;
				if(Dashboard::editAdv($adv)) wl_message('保存成功',web_url('dashboard/adv/index'),'success');
			}
			wl_message('保存失败',referer(),'error');
		}
		if(!empty($_GPC['id'])) $adv = Dashboard::getSingleAdv($_GPC['id']);
		if (!empty($cateid) && p('groupon')) {
			$catename = pdo_getcolumn('wlmerchant_groupon_category', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid'], 'id' => $cateid), 'name');
		}
		
		include wl_template('dashboard/advEdit');
	 }
    /**
     * Comment: 删除幻灯片
     * Author: zzw
     * Date: 2019/9/19 18:26
     */
    public function delete (){
        global $_W , $_GPC;
        #1、参数获取
        $id = $_GPC['id'] OR show_json(0 , '删除失败,缺少id!');
        #2、删除操作
        pdo_delete(PDO_NAME . "adv" , [ 'id' => $id ]) OR show_json(0 , '失败');
        show_json(1 , '成功');
    }
    /**
     * Comment: 修改状态
     * Author: zzw
     * Date: 2019/9/18 15:41
     */
    public function changeStatus (){
        global $_W , $_GPC;
        #1、获取参数信息
        $id = $_GPC['id'] OR Commons::sRenderError('缺少参数：幻灯片id');
        $status = $_GPC['status'] ? : 0;
        #1、修改操作
        $res = pdo_update(PDO_NAME."adv",['enabled'=>$status],['id'=>$id]);
        if($res) Commons::sRenderSuccess('修改成功');
        else Commons::sRenderError('修改失败，请刷新重试!');
    }

}
