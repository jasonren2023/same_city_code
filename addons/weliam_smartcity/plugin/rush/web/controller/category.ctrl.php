<?php
defined('IN_IA') or exit('Access Denied');

class Category_WeliamController {
    /**
     * Comment: 抢购分类列表
     * Author: zzw
     * Date: 2019/12/20 11:41
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
        $list = pdo_getslice(PDO_NAME . 'rush_category',$where,[$page, $pageIndex],$total,['id','name','sort','thumb'],'','sort DESC');
        $pager = wl_pagination($total, $page, $pageIndex);


        include wl_template('goodshouse/cate_list');
    }
    /**
     * Comment: 异步修改抢购商品排序功能
     * Author: zzw
     */
    public function editSort() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $sort = trim($_GPC['value']);
        $item = pdo_fetch('SELECT id FROM ' . tablename(PDO_NAME . 'rush_category') . " WHERE id = {$id} and aid = {$_W['aid']} and uniacid = {$_W['uniacid']} ");
        if (!empty($item)) {
            pdo_update(PDO_NAME . 'rush_category', array('sort' => $sort), array('id' => $id, 'aid' => intval($_W['aid'])));
            show_json(1, '分类修改成功');
        } else {
            show_json(0, '分类不存在,请刷新页面重试！');
        }
    }


    public function specialindex() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $condition = ' and aid=:aid and uniacid=:uniacid ';
        $keyword = trim($_GPC['keyword']);

        if (!empty($keyword)) {
            $condition .= ' and title like \'%' . $keyword . '%\' ';
        }

        $list = pdo_fetchall('select id,`title` from ' . tablename('wlmerchant_rush_special') . ' where 1 ' . $condition . ' order by id desc limit ' . (($pindex - 1) * $psize) . ',' . $psize, array(':aid' => intval($_W['aid']), ':uniacid' => $_W['uniacid']));
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('wlmerchant_rush_special') . ' where aid=:aid and uniacid=:uniacid ', array(':aid' => intval($_W['aid']), ':uniacid' => $_W['uniacid']));
        $pager = wl_pagination($total, $pindex, $psize);


        include wl_template('cate/specialindex');
    }

    public function sptitleedit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $title = trim($_GPC['value']);
        $res = pdo_update('wlmerchant_rush_special', array('title' => $title), array('id' => $id));
        if ($res) {
            show_json(1, '修改成功');
        } else {
            show_json(0, '修改失败,请刷新页面重试！');
        }
    }

    public function specialdelete() {
        global $_W, $_GPC;
        if ($_W['ispost']) {
            $id = intval($_GPC['id']);
            if (empty($id)) {
                show_json(0, '参数错误，请刷新重试！');
            } else {
                $res = pdo_delete('wlmerchant_rush_special', array('id' => $id, 'aid' => intval($_W['aid'])));
            }
            if ($res) {
                show_json(1);
            } else {
                show_json(0, '删除失败,请刷新页面重试！');
            }
        }
    }

    public function specialedit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if ($id) {
            $special = pdo_get('wlmerchant_rush_special', array('id' => $id));
        }
        if (checksubmit('submit')) {
            $special = $_GPC['special'];
            if (empty($special['title'])) wl_message('请填写专题标题');
            if (empty($special['share_title'])) wl_message('请填写分享标题');
            if (empty($special['share_desc'])) wl_message('请填写分享描述');
            if (empty($special['thumb'])) wl_message('请上传专题图片');
            $special['rule'] = htmlspecialchars_decode($special['rule']);

            if ($id) {
                pdo_update('wlmerchant_rush_special', $special, array('id' => $id));
            } else {
                $special['uniacid'] = $_W['uniacid'];
                $special['aid'] = $_W['aid'];
                $special['createtime'] = time();
                pdo_insert('wlmerchant_rush_special', $special);
            }
            wl_message('保存成功！', web_url('rush/category/specialindex'), 'success');
        }
        include wl_template('cate/specialedit');
    }

}