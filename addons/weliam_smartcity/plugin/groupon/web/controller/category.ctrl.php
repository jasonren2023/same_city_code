<?php
defined('IN_IA') or exit('Access Denied');

class Category_WeliamController {
    /**
     * Comment: 团购分类列表
     * Author: zzw
     * Date: 2019/12/20 11:53
     */
    public function index() {
        global $_W, $_GPC;
        #1、参数获取
        $page = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        $keyword = $_GPC['keyword'] ? : '';
        #1、条件生成
        $where = ['aid'=>$_W['aid'],'uniacid'=>$_W['uniacid']];
        if(!empty($keyword)) $where['name LIKE'] = '%' . $keyword . '%';
        #1、列表获取
        $list = pdo_getslice(PDO_NAME . 'groupon_category',$where,[$page, $pageIndex],$total,['id','name','sort','thumb'],'','sort DESC');
        $pager = wl_pagination($total, $page, $pageIndex);


        include wl_template('goodshouse/cate_list');
    }
}