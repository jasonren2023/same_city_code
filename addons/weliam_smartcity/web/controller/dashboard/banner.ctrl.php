<?php
defined('IN_IA') or exit('Access Denied');

class Banner_WeliamController{
	/**
	 * 广告位查询
	 */
	public function index(){
		global $_W,$_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$banneres = Dashboard::getAllBanner($pindex-1,$psize);
		$banners = $banneres['data'];
		$pager = wl_pagination($banneres['count'], $pindex, $psize);
		include wl_template('dashboard/bannerIndex');
	}
	/**
	 * 编辑广告位
	 */
	 public function edit(){
	 	global $_W,$_GPC;
		if(checksubmit('submit')){
			$banner = $_GPC['banner'];
			$banner['name'] = trim($banner['name']);
			$banner['displayorder'] = intval($banner['displayorder']);
			$banner['enabled'] = intval($_GPC['enabled']);
			if(!empty($_GPC['id'])){
				if(Dashboard::editBanner($banner,$_GPC['id'])) wl_message('保存成功',web_url('dashboard/banner/index'),'success');
			}else{
				if(Dashboard::editBanner($banner)) wl_message('保存成功',web_url('dashboard/banner/index'),'success');
			}
			wl_message('保存失败',referer(),'error');
		}
		if(!empty($_GPC['id'])) $banner = Dashboard::getSingleBanner($_GPC['id']);
		include wl_template('dashboard/bannerEdit');
	 }
    /**
     * Comment: 删除广告位
     * Author: zzw
     * Date: 2019/9/20 9:10
     */
	 public function delete(){
         global $_W , $_GPC;
         #1、参数获取
         $id = $_GPC['id'] OR show_json(0 , '删除失败,缺少id!');
         #2、删除操作
         pdo_delete(PDO_NAME . "banner" , [ 'id' => $id ]) OR show_json(0 , '失败');
         show_json(1 , '成功');
	 }
    /**
     * Comment: 修改状态
     * Author: zzw
     * Date: 2019/9/18 15:56
     */
    public function changeStatus (){
        global $_W , $_GPC;
        #1、获取参数信息
        $id = $_GPC['id'] OR Commons::sRenderError('缺少参数：导航id');
        $status = $_GPC['status'] ? : 0;
        #1、修改操作
        $res = pdo_update(PDO_NAME."banner",['enabled'=>$status],['id'=>$id]);
        if($res) Commons::sRenderSuccess('修改成功');
        else Commons::sRenderError('修改失败，请刷新重试!');
    }





}
