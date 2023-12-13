<?php
defined('IN_IA') or exit('Access Denied');

class Nav_WeliamController{
	/**
	 * 导航栏查询
	 */
	public function index(){
		global $_W,$_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$naves = Dashboard::getAllNav($pindex-1,$psize);
		$navs = $naves['data'];
		$pager = wl_pagination($naves['count'], $pindex, $psize);
		include wl_template('dashboard/navIndex');
	}
	/**
	 * 编辑导航栏
	 */
	 public function edit(){
	 	global $_W,$_GPC;
		if(checksubmit('submit')){
			$nav = $_GPC['nav'];
			$nav['name'] = trim($nav['name']);
			$nav['displayorder'] = intval($nav['displayorder']);
			$nav['enabled'] = intval($_GPC['enabled']);
			$nav['link'] = $_GPC['link'];
			if(!empty($_GPC['id'])){
			
				if(Dashboard::editNav($nav,$_GPC['id'])) wl_message('保存成功',web_url('dashboard/nav/index'),'success');
			}else{
				if(Dashboard::editNav($nav)) wl_message('保存成功',web_url('dashboard/nav/index'),'success');
			}
			wl_message('保存失败',referer(),'error');
		}
		
		if(!empty($_GPC['id'])) $nav = Dashboard::getSingleNav($_GPC['id']);
		
		include wl_template('dashboard/navEdit');
	 }
    /**
     * Comment: 删除导航栏
     * Author: zzw
     * Date: 2019/9/19 18:33
     */
	 public function delete(){
         global $_W , $_GPC;
         #1、参数获取
         $id = $_GPC['id'] OR show_json(0 , '删除失败,缺少id!');
         #2、删除操作
         pdo_delete(PDO_NAME . "nav" , [ 'id' => $id ]) OR show_json(0 , '失败');
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
        $id = $_GPC['id'] OR Commons::sRenderError('缺少参数：导航id');
        $status = $_GPC['status'] ? : 0;
        #1、修改操作
        $res = pdo_update(PDO_NAME."nav",['enabled'=>$status],['id'=>$id]);
        if($res) Commons::sRenderSuccess('修改成功');
        else Commons::sRenderError('修改失败，请刷新重试!');
    }

}
