<?php
defined('IN_IA') or exit('Access Denied');

class Citycard_WeliamController {

    public function card_lists() {
        global $_W, $_GPC;
        $where = array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;
        if ($_GPC['name']) {
            $where['name LIKE'] = '%' . $_GPC['name'] . '%';
        }

        $lists = pdo_getslice('wlmerchant_citycard_lists', $where, array($pindex, $psize), $total, array(), '', "id DESC");
        foreach ($lists as $key => &$val) {
            $val['member'] = $val['mid'] ? Member::wl_member_get($val['mid'], ['nickname', 'mobile','avatar']) : [];
            $val['meal'] = pdo_get('wlmerchant_citycard_meals', array('id' => $val['meal_id']));
            $val['collect'] = pdo_getcolumn('wlmerchant_citycard_collect', array('cardid' => $val['id']), 'COUNT(cardid)');
            $val['one_class_name'] = pdo_getcolumn('wlmerchant_citycard_cates', array('id' => $val['one_class']), 'name');
            $val['two_class_name'] = pdo_getcolumn('wlmerchant_citycard_cates', array('id' => $val['two_class']), 'name');
            //如果名片没有logo 则使用 用户头条代替
            $val['logo'] = !empty($val['logo']) ? $val['logo'] : $val['member']['avatar'];

        }
        $pager = wl_pagination($total, $pindex, $psize);
        $checkstatus = array(['class' => 'btn-warning', 'text' => '待审核'], ['class' => 'btn-primary', 'text' => '已通过'], ['class' => 'btn-danger', 'text' => '已驳回']);

        include wl_template('citycard/card_lists');
    }

    public function card_edit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if (checksubmit('submit')) {
            $location_info = Citycard::lnglat_to_adinfo($_GPC['location']);

            $data = $_GPC['item'];
            if(empty($data['mid'])){
                wl_message('请选择关联用户','', 'error');
            }
            $data['lng'] = $location_info['lng'] OR wl_message('请选择坐标',referer(),'error');
            $data['lat'] = $location_info['lat'] OR wl_message('请选择坐标',referer(),'error');
            $data['pro_code'] = $location_info['pro_code'];
            $data['city_code'] = $location_info['city_code'];
            $data['area_code'] = $location_info['area_code'];
            $data['one_class'] = intval($_GPC['category']['parentid'])  OR wl_message('请选择一级分类',referer(),'error');;
            $data['two_class'] = intval($_GPC['category']['childid']) OR wl_message('请选择二级分类',referer(),'error');;
            $data['meal_endtime'] = strtotime($data['meal_endtime']);
            $data['top_endtime'] = strtotime($data['top_endtime']);

            if (!empty($id)) {
                pdo_update('wlmerchant_citycard_lists', $data, array('id' => $id));
            } else {
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                $data['paystatus'] = 1;
                $data['checkstatus'] = 1;
                $data['createtime'] = time();
                pdo_insert('wlmerchant_citycard_lists', $data);
                $id = pdo_insertid();
            }
            wl_message('编辑名片成功', web_url('citycard/citycard/card_edit', array('id' => $id)), 'success');
        }

        if (!empty($id)) {
            $item = pdo_get('wlmerchant_citycard_lists', array('uniacid' => $_W['uniacid'], 'id' => $id));
        } else {
            $item = ['sort' => 100, 'show_addr' => 1, 'show_mobile' => 1, 'show_wechat' => 1, 'status' => 1, 'meal_endtime' => time() + 365 * 24 * 3600, 'top_endtime' => time() + 30 * 24 * 3600];
        }
        $meals = pdo_getall('wlmerchant_citycard_meals', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']));
        $categorys = Citycard::get_cates();

        //如果名片没有logo 则使用 用户头条代替
        $avatar = pdo_getcolumn(PDO_NAME."member",['id'=>$item['mid']],'avatar');
        $item['logo'] = !empty($item['logo']) ? $item['logo'] : $avatar;

        include wl_template('citycard/card_edit');
    }

    public function card_del() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_citycard_lists', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_citycard_lists', array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }

    public function card_check_status() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];
        $checkstatus = intval($_GPC['status']);

        $items = pdo_getall('wlmerchant_citycard_lists', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        $update = array('checkstatus' => $checkstatus);
        if($checkstatus == 1){
            $update['status'] = 1;
        }
        foreach ($items as $item) {
            pdo_update('wlmerchant_citycard_lists',$update,array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }

    public function cate_lists() {
        global $_W, $_GPC;
        $categorys = Citycard::get_cates();
        include wl_template('citycard/cate_lists');
    }

    public function cate_edit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if (checksubmit('submit')) {
            $data = $_GPC['item'];
            if (!empty($id)) {
                pdo_update('wlmerchant_citycard_cates', $data, array('id' => $id));
            } else {
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                pdo_insert('wlmerchant_citycard_cates', $data);
            }
            wl_message('编辑分类成功', web_url('citycard/citycard/cate_lists'), 'success');
        }

        $item = $id ? pdo_get('wlmerchant_citycard_cates', array('uniacid' => $_W['uniacid'], 'id' => $id)) : ['sort' => 100, 'enabled' => 1, 'parentid' => intval($_GPC['parentid'])];
        if (!empty($item['parentid'])) {
            $item['parentname'] = pdo_getcolumn('wlmerchant_citycard_cates', ['id' => $item['parentid']], 'name');
        }

        include wl_template('citycard/cate_edit');
    }

    public function cate_del() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_citycard_cates', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_citycard_cates', array('id' => $item['id']));
            if (empty($item['parentid'])) {
                pdo_delete('wlmerchant_citycard_cates', array('parentid' => $item['id']));
            }
        }

        show_json(1, array('url' => referer()));
    }

    public function cate_enabled() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'] ?: 0;
        $res = pdo_update("wlmerchant_citycard_cates", ['enabled' => $status], ['id' => $id]);
        if ($res) {
            Commons::sRenderSuccess('修改成功');
        } else {
            Commons::sRenderError('修改失败，请刷新重试!');
        }
    }

    public function meal_lists() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;
        $lists = pdo_getslice('wlmerchant_citycard_meals', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), array($pindex, $psize), $total, array(), '', "sort DESC");

        foreach ($lists as $key => &$val) {
            $val['usenum'] = intval(pdo_getcolumn('wlmerchant_citycard_lists', array('uniacid' => $_W['uniacid'], 'meal_id' => $val['id']), array('COUNT(id)')));
        }
        $pager = wl_pagination($total, $pindex, $psize);
        include wl_template('citycard/meal_lists');
    }

    public function meal_edit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if (checksubmit('submit')) {
            $data = $_GPC['item'];
            if($data['price'] < 0.01){
                $data['is_free'] = 1;
            }
            if($data['is_free'] > 0 && $data['vipstatus'] == 1){
                $data['vipstatus'] = 0;
            }
            if (!empty($id)) {
                pdo_update('wlmerchant_citycard_meals', $data, array('id' => $id));
            } else {
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                pdo_insert('wlmerchant_citycard_meals', $data);
            }
            wl_message('编辑套餐成功', web_url('citycard/citycard/meal_lists'), 'success');
        }

        if (!empty($id)) {
            $item = pdo_get('wlmerchant_citycard_meals', array('uniacid' => $_W['uniacid'], 'id' => $id));
        } else {
            $item = ['sort' => 100, 'status' => 1];
        }
        include wl_template('citycard/meal_edit');
    }

    public function meal_del() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_citycard_meals', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_citycard_meals', array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }

    public function top_lists() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;
        $lists = pdo_getslice('wlmerchant_citycard_tops', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), array($pindex, $psize), $total, array(), '', "sort DESC");
        $pager = wl_pagination($total, $pindex, $psize);

        include wl_template('citycard/top_lists');
    }

    public function top_edit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if (checksubmit('submit')) {
            $data = $_GPC['item'];
            if($data['price'] < 0.01){
                wl_message('置顶金额不能为0','', 'error');
            }
            if($data['vipprice'] < 0.01 && $data['vipstatus'] == 1){
                wl_message('会员优惠置顶金额不能为0','', 'error');
            }
            if (!empty($id)) {
                pdo_update('wlmerchant_citycard_tops', $data, array('id' => $id));
            } else {
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                pdo_insert('wlmerchant_citycard_tops', $data);
            }
            wl_message('编辑置顶套餐成功', web_url('citycard/citycard/top_lists'), 'success');
        }

        if (!empty($id)) {
            $item = pdo_get('wlmerchant_citycard_tops', array('uniacid' => $_W['uniacid'], 'id' => $id));
        } else {
            $item = ['sort' => 100, 'status' => 1];
        }
        include wl_template('citycard/top_edit');
    }

    public function top_del() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_citycard_tops', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_citycard_tops', array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }

    public function setting() {
        global $_W, $_GPC;
        $settings = Setting::agentsetting_read('citycard');
        if (checksubmit('submit')) {
            $data = $_GPC['settings'];
            $data['agreement'] = htmlspecialchars_decode($data['agreement']);
            Setting::agentsetting_save($data, 'citycard');
            wl_message('更新设置成功！', web_url('citycard/citycard/setting'));
        }
        $communitylist = pdo_getall('wlmerchant_community', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), array('id', 'communname'));

        include wl_template('citycard/setting');
    }

    public function order_lists(){
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $where['plugin'] = 'citycard';
        $where['status>'] = 1;
        $where['status!='] = 5;

        if($_GPC['fightstatus']){
            $where['fightstatus'] = $_GPC['fightstatus'];
        }
        if($_GPC['keyword']){
            $keyword = $_GPC['keyword'];
            if($_GPC['keywordtype'] == 1){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where['mid#'] = $mids;
                }
            }else if($_GPC['keywordtype'] == 2){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']} AND mobile LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where['mid#'] = $mids;
                }
            }else if($_GPC['keywordtype'] == 3){
            	$where['orderno'] = $keyword;
            }
        }

        if($_GPC['time_limit']){
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']) ;
            $where['paytime>'] = $starttime;
            $where['paytime<'] = $endtime+86400;
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $payonlinelist = Util::getNumData('*','wlmerchant_order',$where,'paytime DESC',$pindex,$psize,1);
        $pager = $payonlinelist[1];
        $list = $payonlinelist[0];
        foreach ($list as $key => &$li) {
            $member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('avatar','nickname'));
            $li['avatar'] = tomedia($member['avatar']);
            $li['nickname'] = $member['nickname'];
            if($li['fightstatus'] == 1){
                $li['goodsname'] = pdo_getcolumn(PDO_NAME.'citycard_meals',array('id'=>$li['fkid']),'name');
            }else{
                $li['goodsname'] = pdo_getcolumn(PDO_NAME.'citycard_tops',array('id'=>$li['fkid']),'name');
            }
            $li['paytime'] = date('Y-m-d H:i:s',$li['paytime']);
        }


        include  wl_template('citycard/order_lists');

    }

}