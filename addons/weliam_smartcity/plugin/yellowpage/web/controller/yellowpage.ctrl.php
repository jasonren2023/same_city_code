<?php
defined('IN_IA') or exit('Access Denied');

class Yellowpage_WeliamController {

    //黄页列表
    public function page_lists() {
        global $_W, $_GPC;
        $where = array('uniacid' => $_W['uniacid'],'aid' => $_W['aid']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;
        if ($_GPC['name']) {
            $where['name LIKE'] = '%' . $_GPC['name'] . '%';
        }
        if($_GPC['status'] > 0){
            $status = $_GPC['status'];
            if($status == 3){
                $where['paystatus'] = 0;
            }else if($status == 4){
                $where['paystatus'] = 1;
                $where['checkstatus'] = 0;
            }else{
                $where['paystatus'] = 1;
                $where['checkstatus'] = $status;
            }
        }
        if($_GPC['tStatus'] > 0){
            if($_GPC['tStatus'] == 1){
                $where['status'] = 1;
            }else{
                $where['status'] = 0;
            }
        }
        $lists = pdo_getslice('wlmerchant_yellowpage_lists', $where, array($pindex, $psize), $total, array(), '', "id DESC");
        foreach ($lists as $key => &$val) {
            $val['member'] = $val['mid'] ? Member::wl_member_get($val['mid'], ['nickname','avatar','mobile']) : [];
            $val['meal'] = pdo_get('wlmerchant_yellowpage_meals', array('id' => $val['meal_id']));
            $val['collect'] = pdo_getcolumn('wlmerchant_yellowpage_collect', array('pageid' => $val['id']), 'COUNT(id)');
            $val['one_class_name'] = pdo_getcolumn('wlmerchant_yellowpage_cates', array('id' => $val['one_class']), 'name');
            $val['two_class_name'] = pdo_getcolumn('wlmerchant_yellowpage_cates', array('id' => $val['two_class']), 'name');
        }
        $pager = wl_pagination($total, $pindex, $psize);
        $checkstatus = array(['class' => 'btn-warning', 'text' => '待审核'], ['class' => 'btn-primary', 'text' => '已通过'], ['class' => 'btn-danger', 'text' => '已驳回']);

        include wl_template('yellow/page_lists');

    }

    //黄页编辑
    public function page_edit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if (checksubmit('submit')) {
            $location_info = Yellowpage::lnglat_to_adinfo($_GPC['location'], true);

            $data = $_GPC['item'];
            if(!$data['mobile'] && !$data['wechat_number'] && !$data['wechat_qrcode']) wl_message('联系电话&微信号&微信二维码请至少完善一个');

            if($data['storeid'] > 0){
                $flag = pdo_getcolumn(PDO_NAME.'yellowpage_lists',array('storeid'=>$data['storeid'],'id !='=>$id),'id');
                if($flag){
                    wl_message('关联店铺已有黄页，无法重复创建');
                }
            }
            $data['lng'] = $location_info['lng'];
            $data['lat'] = $location_info['lat'];
            $data['pro_code'] = $location_info['pro_code'];
            $data['city_code'] = $location_info['city_code'];
            $data['area_code'] = $location_info['area_code'];
            $data['one_class'] = intval($_GPC['category']['parentid']);
            $data['two_class'] = intval($_GPC['category']['childid']);
            $data['meal_endtime'] = strtotime($data['meal_endtime']);
            $data['thumbs'] = serialize($data['thumbs']);
            $data['detail'] = base64_encode(htmlspecialchars_decode($data['detail']));

            //没有添加logo时使用关联店铺的logo
            if(empty($data['logo']) && $data['storeid']){
                $data['logo'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$data['storeid']),'logo');
            }
            if (!empty($id)) {
                pdo_update('wlmerchant_yellowpage_lists', $data, array('id' => $id));
            } else {
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                $data['createtime'] = time();
                $data['checkstatus'] = 1;
                $data['paystatus'] = 1;
                pdo_insert('wlmerchant_yellowpage_lists', $data);
                $id = pdo_insertid();
            }
            wl_message('编辑黄页成功', web_url('yellowpage/yellowpage/page_lists', array('id' => $id)), 'success');
        }

        if (!empty($id)) {
            $item = pdo_get('wlmerchant_yellowpage_lists', array('uniacid' => $_W['uniacid'], 'id' => $id));
            $item['thumbs'] = unserialize($item['thumbs']);
            if($item['storeid']){
                $store = pdo_get('wlmerchant_merchantdata',array('id' => $item['storeid']),array('storename','logo'));
                $item['storename'] = $store['storename'];
                $item['storelogo'] = $store['logo'];
            }
            if(is_base64($item['detail'])) $item['detail'] = htmlspecialchars_decode(base64_decode($item['detail']));
        } else {
            $item = ['sort' => 100, 'status' => 1,'checkstatus' => 1,'paystatus' => 1,'meal_endtime' => time() + 365 * 24 * 3600];
        }
        $meals = pdo_getall('wlmerchant_yellowpage_meals', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']));
        $categorys = Yellowpage::get_cates();

        include wl_template('yellow/page_edit');
    }
    //删除黄页
    public function page_del() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_yellowpage_lists', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_yellowpage_lists', array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }

    //修改黄页状态
    public function page_status() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'] ?: 0;
        $res = pdo_update("wlmerchant_yellowpage_lists", ['status' => $status], ['id' => $id]);
        if ($res) {
            Commons::sRenderSuccess('修改成功');
        } else {
            Commons::sRenderError('修改失败，请刷新重试!');
        }
    }
    //修改黄页审核状态
    public function page_check_status() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];
        if(strpos($id,',') > 0){
            $id = explode(',',$id);
        }
        $checkstatus = intval($_GPC['status']);
        $update = array('checkstatus' => $checkstatus);
        if($checkstatus == 1){
            $update['status'] = 1;
        }else{
            $update['status'] = 0;
        }
        $reason = !empty($_GPC['reason']) ? trim($_GPC['reason']) : '';
        $update['rejectreason'] = $reason;
        $items = pdo_getall('wlmerchant_yellowpage_lists', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id','mid'));
        foreach ($items as $item) {
            pdo_update('wlmerchant_yellowpage_lists',$update,array('id' => $item['id']));
            Yellowpage::Notice($item['mid'],1,$checkstatus,$item['id'],$reason);
        }
        show_json(1, array('url' => referer()));
    }

    //分类列表
    public function cate_lists(){
        global $_W, $_GPC;
        $categorys = Yellowpage::get_cates(true,0,'',true);
        include wl_template('yellow/cate_lists');
    }
    
    //分类编辑
    public function cate_edit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if (checksubmit('submit')) {
            $data = $_GPC['item'];
            if (!empty($id)) {
                pdo_update('wlmerchant_yellowpage_cates', $data, array('id' => $id));
            } else {
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                pdo_insert('wlmerchant_yellowpage_cates', $data);
            }
            wl_message('编辑分类成功', web_url('yellowpage/yellowpage/cate_lists'), 'success');
        }

        $item = $id ? pdo_get('wlmerchant_yellowpage_cates', array('uniacid' => $_W['uniacid'], 'id' => $id)) : ['sort' => 100,'querymoney'=>0.00,'claimmoney'=>0.00,'enabled' => 1, 'parentid' => intval($_GPC['parentid'])];
        if (!empty($item['parentid'])) {
            $item['parentname'] = pdo_getcolumn('wlmerchant_yellowpage_cates', ['id' => $item['parentid']], 'name');
        }
        if (!empty($item['logo'])) {
            $item['logo'] = tomedia($item['logo']);
        }
        include wl_template('yellow/cate_edit');
    }

    //修改分类状态
    public function cate_enabled() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'] ?: 0;
        $res = pdo_update("wlmerchant_yellowpage_cates", ['enabled' => $status], ['id' => $id]);
        if ($res) {
            Commons::sRenderSuccess('修改成功');
        } else {
            Commons::sRenderError('修改失败，请刷新重试!');
        }
    }
    
    //删除分类
    public function cate_del() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];
        $items = pdo_getall('wlmerchant_yellowpage_cates', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_yellowpage_cates', array('id' => $item['id']));
            if (empty($item['parentid'])) {
                pdo_delete('wlmerchant_yellowpage_cates', array('parentid' => $item['id']));
            }
        }
        show_json(1, array('url' => referer()));
    }

    //套餐列表
    public function meal_lists() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;
        $lists = pdo_getslice('wlmerchant_yellowpage_meals', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), array($pindex, $psize), $total, array(), '', "sort DESC");
        foreach ($lists as $key => &$val) {
            $val['usenum'] = intval(pdo_getcolumn('wlmerchant_yellowpage_lists', array('uniacid' => $_W['uniacid'], 'meal_id' => $val['id']), array('COUNT(id)')));
        }
        $pager = wl_pagination($total, $pindex, $psize);
        include wl_template('yellow/meal_lists');
    }
    //套餐编辑
    public function meal_edit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if (checksubmit('submit')) {
            $data = $_GPC['item'];
            if (!empty($id)) {
                pdo_update('wlmerchant_yellowpage_meals', $data, array('id' => $id));
            } else {
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                pdo_insert('wlmerchant_yellowpage_meals', $data);
            }
            wl_message('编辑套餐成功', web_url('yellowpage/yellowpage/meal_lists'), 'success');
        }

        if (!empty($id)) {
            $item = pdo_get('wlmerchant_yellowpage_meals', array('uniacid' => $_W['uniacid'], 'id' => $id));
        } else {
            $item = ['sort' => 100, 'status' => 1];
        }
        include wl_template('yellow/meal_edit');
    }
    //套餐删除
    public function meal_del() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_yellowpage_meals', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_yellowpage_meals', array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }

    //修改套餐状态
    public function meal_status() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'] ?: 0;
        $res = pdo_update("wlmerchant_yellowpage_meals", ['status' => $status], ['id' => $id]);
        if ($res) {
            Commons::sRenderSuccess('修改成功');
        } else {
            Commons::sRenderError('修改失败，请刷新重试!');
        }
    }

    //认领记录
    public function claim_lists(){
        global $_W, $_GPC;
        $where = array('uniacid' => $_W['uniacid'],'aid'=>$_W['aid']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;

        if($_GPC['keyword']){
            $keyword = $_GPC['keyword'];
            if($_GPC['keywordtype'] == 1){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :name",$params);
                $mids = [];
                if($members){
                    foreach ($members as $key => $v) {
                        $mids[] = $v['id'];
                    }
                }
                $where['mid'] = $mids;
            }else if($_GPC['keywordtype'] == 2){
                $where['name LIKE'] = '%' . $keyword . '%';
            }else if($_GPC['keywordtype'] == 3){
                $where['mobile LIKE'] = '%' . $keyword . '%';
            }else if($_GPC['keywordtype'] == 4){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_yellowpage_lists')." WHERE uniacid = {$_W['uniacid']}  AND `name` LIKE :name",$params);
                $mids = [];
                if($members){
                    foreach ($members as $key => $v) {
                        $mids[] = $v['id'];
                    }
                }
                $where['pageid'] = $mids;
            }
        }
        if($_GPC['time_limit']){
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']) ;
            $where['createtime >'] = $starttime;
            $where['createtime <'] = $endtime+86400;
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        $lists = pdo_getslice('wlmerchant_yellowpage_claim_lists', $where, array($pindex, $psize), $total, array(), '', "id DESC");
        foreach ($lists as $key => &$val) {
              $val['member'] = $val['mid'] ? Member::wl_member_get($val['mid'],['nickname','avatar','mobile']):[];
              $val['pageinfo'] = pdo_get('wlmerchant_yellowpage_lists',array('id' => $val['pageid']),array('one_class','two_class','logo','name','mobile'));
              $val['pageinfo']['one_class_name'] = pdo_getcolumn('wlmerchant_yellowpage_cates', array('id' => $val['pageinfo']['one_class']), 'name');
              $val['pageinfo']['two_class_name'] = pdo_getcolumn('wlmerchant_yellowpage_cates', array('id' => $val['pageinfo']['two_class']), 'name');
        }
        $pager = wl_pagination($total, $pindex, $psize);
        $checkstatus = array(['class' => 'btn-warning', 'text' => '待审核'], ['class' => 'btn-primary', 'text' => '已通过'], ['class' => 'btn-danger', 'text' => '已驳回']);



        include wl_template('yellow/claim_lists');
    }

    //删除认领记录
    public function claim_del(){
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_yellowpage_claim_lists', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_yellowpage_claim_lists', array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }

    //修改认领记录审核状态
    public function claim_check_status(){
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];
        if(strpos($id,',') > 0){
            $id = explode(',',$id);
        }
        $status = intval($_GPC['status']);
        $update = array('status' => $status);
        $reason = !empty($_GPC['reason']) ? trim($_GPC['reason']) : '';
        $update['rejectreason'] = $reason;
        $items = pdo_getall('wlmerchant_yellowpage_claim_lists', array('id' => $id, 'uniacid' => $_W['uniacid']), array('orderid','id','mid','pageid'));
        foreach ($items as $item) {
            pdo_update('wlmerchant_yellowpage_claim_lists',$update,array('id' => $item['id']));
            if($status == 1){
                pdo_update('wlmerchant_yellowpage_lists',array('mid'=>$item['mid']),array('id' => $item['pageid']));
            }
            if($status == 2 && !empty($item['orderid'])){
                //退款
                Yellowpage::refundOrder($item['orderid']);
            }
            Yellowpage::Notice($item['mid'],2,$status,$item['pageid'],$reason);
        }
        show_json(1, array('url' => referer()));
    }

    //纠错记录
    public function correction_lists(){
        global $_W, $_GPC;
        $where = array('uniacid' => $_W['uniacid'],'aid' => $_W['aid']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;

        if($_GPC['keyword']){
            $keyword = $_GPC['keyword'];
            if($_GPC['keywordtype'] == 1){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :name",$params);
                $mids = [];
                if($members){
                    foreach ($members as $key => $v) {
                        $mids[] = $v['id'];
                    }
                }
                $where['mid'] = $mids;
            }else if($_GPC['keywordtype'] == 2){
                $where['name LIKE'] = '%' . $keyword . '%';
            }else if($_GPC['keywordtype'] == 3){
                $where['mobile LIKE'] = '%' . $keyword . '%';
            }else if($_GPC['keywordtype'] == 4){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_yellowpage_lists')." WHERE uniacid = {$_W['uniacid']}  AND `name` LIKE :name",$params);
                $mids = [];
                if($members){
                    foreach ($members as $key => $v) {
                        $mids[] = $v['id'];
                    }
                }
                $where['pageid'] = $mids;
            }
        }
        if($_GPC['time_limit']){
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']) ;
            $where['createtime >'] = $starttime;
            $where['createtime <'] = $endtime+86400;
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        $lists = pdo_getslice('wlmerchant_yellowpage_correction_lists', $where, array($pindex, $psize), $total, array(), '', "id DESC");
        foreach ($lists as $key => &$val) {
            $val['member'] = $val['mid'] ? Member::wl_member_get($val['mid'],['nickname','avatar','mobile']):[];
            $val['pageinfo'] = pdo_get('wlmerchant_yellowpage_lists',array('id' => $val['pageid']),array('one_class','two_class','logo','name','mobile'));
            $val['pageinfo']['one_class_name'] = pdo_getcolumn('wlmerchant_yellowpage_cates', array('id' => $val['pageinfo']['one_class']), 'name');
            $val['pageinfo']['two_class_name'] = pdo_getcolumn('wlmerchant_yellowpage_cates', array('id' => $val['pageinfo']['two_class']), 'name');
        }
        $pager = wl_pagination($total, $pindex, $psize);
        $checkstatus = array(['class' => 'btn-warning', 'text' => '待查看'], ['class' => 'btn-primary', 'text' => '已查看']);

        include wl_template('yellow/correction_lists');
    }

    //删除纠错记录
    public function correction_del(){
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_yellowpage_correction_lists', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_yellowpage_correction_lists', array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }

    //修改纠错记录状态
    public function correction_check_status(){
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];
        $status = intval($_GPC['status']);
        $update = array('status' => $status);
        $items = pdo_getall('wlmerchant_yellowpage_correction_lists', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id','mid','pageid'));
        foreach ($items as $item) {
            pdo_update('wlmerchant_yellowpage_correction_lists',$update,array('id' => $item['id']));
        }
        show_json(1, array('url' => referer()));
    }

    //举报记录
    public function report_lists(){
        global $_W, $_GPC;
        $where = array('uniacid' => $_W['uniacid'],'aid' => $_W['aid']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;

        if($_GPC['keyword']){
            $keyword = $_GPC['keyword'];
            if($_GPC['keywordtype'] == 1){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :name",$params);
                $mids = [];
                if($members){
                    foreach ($members as $key => $v) {
                        $mids[] = $v['id'];
                    }
                }
                $where['mid'] = $mids;
            }else if($_GPC['keywordtype'] == 2){
                $where['name LIKE'] = '%' . $keyword . '%';
            }else if($_GPC['keywordtype'] == 3){
                $where['mobile LIKE'] = '%' . $keyword . '%';
            }else if($_GPC['keywordtype'] == 4){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_yellowpage_lists')." WHERE uniacid = {$_W['uniacid']}  AND `name` LIKE :name",$params);
                $mids = [];
                if($members){
                    foreach ($members as $key => $v) {
                        $mids[] = $v['id'];
                    }
                }
                $where['pageid'] = $mids;
            }
        }
        if($_GPC['time_limit']){
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']) ;
            $where['createtime >'] = $starttime;
            $where['createtime <'] = $endtime+86400;
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        $lists = pdo_getslice('wlmerchant_yellowpage_report_lists', $where, array($pindex, $psize), $total, array(), '', "id DESC");
        foreach ($lists as $key => &$val) {
            $val['member'] = $val['mid'] ? Member::wl_member_get($val['mid'],['nickname','avatar','mobile']):[];
            $val['pageinfo'] = pdo_get('wlmerchant_yellowpage_lists',array('id' => $val['pageid']),array('one_class','two_class','logo','name','mobile'));
            $val['pageinfo']['one_class_name'] = pdo_getcolumn('wlmerchant_yellowpage_cates', array('id' => $val['pageinfo']['one_class']), 'name');
            $val['pageinfo']['two_class_name'] = pdo_getcolumn('wlmerchant_yellowpage_cates', array('id' => $val['pageinfo']['two_class']), 'name');
        }
        $pager = wl_pagination($total, $pindex, $psize);
        $checkstatus = array(['class' => 'btn-warning', 'text' => '待查看'], ['class' => 'btn-primary', 'text' => '已查看']);

        include wl_template('yellow/report_lists');
    }

    //删除举报记录
    public function report_del(){
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_yellowpage_report_lists', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_yellowpage_report_lists', array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }

    //修改纠错记录状态
    public function report_check_status(){
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];
        $status = intval($_GPC['status']);
        $update = array('status' => $status);
        $items = pdo_getall('wlmerchant_yellowpage_report_lists', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id','mid','pageid'));
        foreach ($items as $item) {
            pdo_update('wlmerchant_yellowpage_report_lists',$update,array('id' => $item['id']));
        }
        show_json(1, array('url' => referer()));
    }

    //付费记录


    //黄页设置项
    public function setting() {
        global $_W, $_GPC;
        $settings = Setting::agentsetting_read('yellowpage');
        if (checksubmit('submit')) {
            $data = $_GPC['settings'];
            $data['agreement'] = htmlspecialchars_decode($data['agreement']);
            Setting::agentsetting_save($data, 'yellowpage');
            wl_message('更新设置成功！', web_url('yellowpage/yellowpage/setting'));
        }
        $communitylist = pdo_getall('wlmerchant_community', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), array('id', 'communname'));

        include wl_template('yellow/setting');
    }

    public function order_lists(){
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $where['aid'] = $_W['aid'];
        $where['plugin'] = 'yellowpage';
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
            $li['goodsname'] = pdo_getcolumn(PDO_NAME.'yellowpage_lists',array('id'=>$li['fkid']),'name');
            $li['goodsname'] = '['.$li['goodsname'].']';
            if($li['fightstatus'] == 3 || $li['fightstatus'] == 4){
                $mealname = pdo_getcolumn(PDO_NAME.'yellowpage_meals',array('id'=>$li['specid']),'name');
                $li['goodsname'] .= ',套餐:'.$mealname;
            }

            $li['paytime'] = date('Y-m-d H:i:s',$li['paytime']);
        }


        include  wl_template('yellow/order_lists');

    }

}