<?php
defined('IN_IA') or exit('Access Denied');

class Category_WeliamController{
	/**
	 * 商户分类列表
	 */
	public function index(){
		global $_W,$_GPC;

		$pindex = max(1, intval($_GPC['page']));
		$psize = 100;	         
		$categoryes = Store::getAllCategory($pindex-1,$psize,0);
		$categorys = $categoryes['data'];
		
		$pager = wl_pagination($categoryes['count'], $pindex, $psize);
		if(!empty($categorys)){
			foreach($categorys as $key=>$value){
				$childrens = Store::getAllCategory(0,100,$value['id']);
				$categorys[$key]['children'] = $childrens['data'];
			}
		}
		include wl_template('store/categoryIndex');
	}
    /**
     * Comment: 编辑商户
     */
	public function Edit(){ 
		global $_W,$_GPC;
		if(checksubmit('submit')){
			$category = $_GPC['category'];
			if(!empty($_GPC['parentid'])){
				$category['parentid'] =intval($_GPC['parentid']);
				$category['visible_level']=2;
			}else{
				$category['parentid'] =0;
				$category['visible_level']=1;
			}
			$category['name'] = trim($category['name']);
			$category['displayorder'] = intval($category['displayorder']);
			$category['enabled'] = intval($_GPC['enabled']);
			//判断时候有值进行改状态
			if(!empty($category['abroad'])) {
                $category['abroad'] = $category['abroad'];
                $category['state'] = 1;
            } 
            else {
                $category['abroad'] = $category['abroad'];
                $category['state'] = 0;
            }

            if(Customized::init('storecate1520') > 0){
                $advlogo = $_GPC['advlogo'];
                $advlink = $_GPC['advlink'];
                $advarray = [];
                foreach($advlogo as $dkey => $dle){
                    $dlea['thumb'] = $advlogo[$dkey];
                    $dlea['link'] = $advlink[$dkey];
                    $advarray[] = $dlea;
                }
                $category['advs'] = serialize($advarray);
            }

			if(!empty($_GPC['id'])){
				if(Store::categoryEdit($category,$_GPC['id'])) wl_message('保存成功',web_url('store/category/index'),'success');
			}else{
				if(Store::categoryEdit($category)) wl_message('保存成功',web_url('store/category/index'),'success');
			}
			wl_message('保存失败',referer(),'error');
		}
		if(!empty($_GPC['id'])) 
		$category = Store::getSingleCategory($_GPC['id']);
        $category['adv'] = unserialize($category['advs']);

        include wl_template('store/categoryEdit');
	}
    /**
     * Comment: 删除商户
     */
	public function Delete(){
		global $_W,$_GPC;
		if(Store::categoryDelete($_GPC['id'])) wl_message('删除成功',web_url('store/category/index'),'success');
		wl_message('删除失败',referer(),'error');
		
	}
    /**
     * Comment: 商户分类获取
     */
	public function getCategory(){
		global $_W,$_GPC;
		if(!empty($_GPC['parentid'])){
			$categoryes = Store::getAllCategory(0,100,$_GPC['parentid']);
		}else{
			$categoryes = Store::getAllCategory();
		}
		$categorys = $categoryes['data'];
		die(json_encode(array('status'=>1,'data'=>$categorys,'msg'=>'')));
	}
    /**
     * Comment: 修改状态
     * Author: zzw
     * Date: 2019/9/18 16:44
     */
    public function changeStatus (){
        global $_W , $_GPC;
        #1、获取参数信息
        $id = $_GPC['id'] OR Commons::sRenderError('缺少参数：导航id');
        $status = $_GPC['status'] ? : 0;
        #1、修改操作
        $res = pdo_update(PDO_NAME."category_store",['enabled'=>$status],['id'=>$id]);
        if($res) Commons::sRenderSuccess('修改成功');
        else Commons::sRenderError('修改失败，请刷新重试!');
    }

    /**
     * Comment: 1520定制一级分类幻灯片页面
     * Author: wlf
     * Date: 2021/12/22 15:34
     */
    public function advinfo(){
        global $_W, $_GPC;
        $kw = $_GPC['kw'];
        include wl_template('store/advinfo');
    }




}
