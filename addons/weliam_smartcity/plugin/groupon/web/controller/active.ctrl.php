<?php
defined('IN_IA') or exit('Access Denied');

class Active_WeliamController {
    /*
     * 入口函数
     */
    function activelist() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $data = array();
        if ($_GPC['status'] == 4) {
            $data['status#'] = "(0,4)";
        } else if (!empty($_GPC['status'])) {
            $data['status'] = intval($_GPC['status']);
        }
        $data['aid'] = $_W['aid'];
        if (is_store()) {
            $data['sid'] = $_W['storeid'];
        }
        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 1:
                        $data['@name@'] = $_GPC['keyword'];
                        break;
                    case 2:
                        $data['@id@'] = $_GPC['keyword'];
                        break;
                    default:
                        break;
                }
                if ($_GPC['keywordtype'] == 3) {
                    $keyword = $_GPC['keyword'];
                    $params[':storename'] = "%{$keyword}%";
                    $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_merchantdata') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND storename LIKE :storename", $params);
                    if ($merchants) {
                        $sids = "(";
                        foreach ($merchants as $key => $v) {
                            if ($key == 0) {
                                $sids .= $v['id'];
                            } else {
                                $sids .= "," . $v['id'];
                            }
                        }
                        $sids .= ")";
                        $data['sid#'] = $sids;
                    } else {
                        $data['sid#'] = "(0)";
                    }
                }
            }
        }
        $activity = Groupon::getNumActive('*', $data, 'sort DESC,ID DESC', $pindex, $psize, 1);
        $pager = $activity[1];
        $activity = $activity[0];
        foreach ($activity as $key => &$value) {
            if($value['sid'] > 0){
                $value['storename'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $value['sid']), 'storename');
            }else if(empty($value['threestatus'])){
                $value['storename'] = '票付通平台商品';
            }else if($value['threestatus'] == 1){
                $value['storename'] = '亿奇达平台商品';
            }
            Groupon::changeActivestatus($value);
            $value['placeorder'] = WeliamWeChat::getSalesNum(2,$value['id'],0,1,0,0,0,$value['pftid']);   //已下单
            if (empty($value['placeorder'])) {
                $value['placeorder'] = 0;
            }
            $value['alreadypay'] = WeliamWeChat::getSalesNum(2,$value['id'],0,2,0,0,0,$value['pftid']);  //已支付
            if (empty($value['alreadypay'])) {
                $value['alreadypay'] = 0;
            }
            $value['alreadyuse'] = WeliamWeChat::getSalesNum(2,$value['id'],0,3,0,0,0,$value['pftid']);  //已使用
            if (empty($value['alreadyuse'])) {
                $value['alreadyuse'] = 0;
            }

            //视频号判断
            if(p('wxchannels') && $value['usestatus'] == 1){
                $alchannels =  pdo_get(PDO_NAME.'channels_goods',array('type'=>2,'goodsid'=>$value['id']),['product_id','third_cat_id']);
                if(!empty($alchannels)){
                    $value['channelsflag'] = 2;
                    $value['product_id'] = $alchannels['product_id'];
                    $value['third_cat_id'] = $alchannels['third_cat_id'];
                }else{
                    $value['channelsflag'] = 1;
                }
            }else{
                $value['channelsflag'] = 0;
            }

        }
        include wl_template('grouponactive/active_list');
    }

    function ajax() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $da = Groupon::getSingleGoods($id, '*');
        die(json_encode($da));
    }

    function delete() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'];
        if ($status == 4) {
            $group = pdo_get('wlmerchant_groupon_activity',array('id' => $id),array('starttime','endtime','sid','status'));
            if(is_store()){
                $examine = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$group['sid']),'audits');
                if(empty($examine)){
                    $changestatus = 5;
                }
            }
            if(empty($changestatus)){
                if ($group['starttime'] > time()) {
                    $changestatus = 1;
                }
                else if ($group['starttime'] < time() && time() < $group['endtime']) {
                    $changestatus = 2;
                }
                else if ($group['endtime'] < time()) {
                    $changestatus = 3;
                }
            }
            $res = Groupon::updateActive(array('status' => $changestatus), array('id' => $id));
        } else {
            $res = Groupon::updateActive(array('status' => 4), array('id' => $id));
        }
        if ($res) {
            die(json_encode(array('errno' => 0)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    function delall() {
        global $_W, $_GPC;
        $res = Groupon::deleteActive(array('id' => intval($_GPC['id']), 'uniacid' => $_W['uniacid']));
        if ($res) {
            show_json(1, '团购删除成功');
        } else {
            show_json(0, '团购删除失败，请重试');
        }
    }

    /**
     * Comment: 批量修改商品信息
     * Author: wlf
     * Date: 2020/06/01 15:07
     */
    public function changestatus(){
        global $_W, $_GPC;
        $ids = $_GPC['ids'];
        $type = $_GPC['type'];
        foreach ($ids as $k => $v){
            $groupon = pdo_get('wlmerchant_groupon_activity',array('id' => $v),array('starttime','endtime','status','sid'));
            if($type == 1){
                $status = 0;
                if(is_store()){
                    $examine = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$groupon['sid']),'audits');
                    if(empty($examine)){
                        $status = 5;
                    }
                }
                if(empty($status)){
                    if ($groupon['starttime'] > time()) {
                        $status = 1;
                    }
                    else if ($groupon['starttime'] < time() && time() < $groupon['endtime']) {
                        $status = 2;
                    }
                    else if ($groupon['endtime'] < time()) {
                        $status = 3;
                    }
                }
                pdo_update('wlmerchant_groupon_activity', array('status' => $status), array('id' => $v));
            }else if($type == 8 && $groupon['status'] == 8){
                Groupon::deleteActive(array('id' => $v, 'uniacid' => $_W['uniacid']));
            }else{
                pdo_update('wlmerchant_groupon_activity', array('status' => $type), array('id' => $v));
            }
        }
        show_json(1, '操作成功');
    }

    function cutoff() {
        global $_W, $_GPC;
        $res = pdo_update('wlmerchant_groupon_activity', array('status' => 8), array('id' => intval($_GPC['id'])));
        if ($res) {
            show_json(1, '删除成功');
        } else {
            show_json(0, '删除失败，请重试');
        }
    }

    function examine() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $flag = $_GPC['flag'];
        if ($flag == 1) {
            $res = Groupon::updateActive(array('status' => 1), array('id' => $id));
            News::goodsToExamine($id,'groupon');
        } else {
            $res = Groupon::updateActive(array('status' => 6), array('id' => $id));
            News::goodsToExamine($id,'groupon','未通过');
        }
        if ($res) {
            die(json_encode(array('errno' => 0)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    function tag() {
        include wl_template('grouponactive/listtag');
    }

    function copygood() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $da = Groupon::getSingleActive($id, '*');
        unset($da['id']);
        unset($da['a']);
        unset($da['plugin']);
        $da['levelnum'] = $da['num'];
        $da['status'] = 4;
        $res = pdo_insert('wlmerchant_groupon_activity', $da);
        if ($res) {
            die(json_encode(array('errno' => 0)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    function changepv() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        $newvalue = trim($_GPC['value']);
        if ($type == 1) {
            $res = pdo_update('wlmerchant_groupon_activity', array('pv' => $newvalue), array('id' => $id));
        } elseif ($type == 2) {
            $res = pdo_update('wlmerchant_groupon_activity', array('sort' => $newvalue), array('id' => $id));
        } elseif ($type == 3) {
            $res = pdo_update('wlmerchant_groupon_activity', array('num' => $newvalue), array('id' => $id));
        }
        if ($res) {
            show_json(1, '修改成功');
        } else {
            show_json(0, '修改失败，请重试');
        }
    }


    /**
     * Comment: 根据条件获取团购商品
     * Author: zzw
     * Date: 2019/7/11 13:50
     */
    public function groupList() {
        global $_W, $_GPC;
        #1、条件生成
        $where = " a.aid = {$_W['aid']} AND a.uniacid = {$_W['uniacid']}";//默认条件
        !empty($_GPC['name']) && $where .= " AND a.name LIKE '%{$_GPC['name']}%' ";//商品名称
        $_GPC['status'] > -1 && $where .= " AND a.status = {$_GPC['status']} ";//商品名称
        !empty($_GPC['goods_id']) && $where .= " AND a.id = {$_GPC['goods_id']} ";//商品id
        !empty($_GPC['shop_name']) && $where .= " AND m.storename LIKE '%{$_GPC['shop_name']}%' ";//商户名称
        $_GPC['cate_id'] > -1 && $where .= " AND a.cateid = {$_GPC['cate_id']} ";//商户名称
        !empty($_GPC['shop_id']) && $where .= " AND a.sid = {$_GPC['shop_id']} ";//商户id
        #2、排序操作
        $order = " a.sort DESC ,a.id DESC ";
        #3、分页操作
        $page = $_GPC['page'] ? $_GPC['page'] : 1;//当前页
        $index = $_GPC['index'] ? $_GPC['index'] : 10;//每页的数量
        $start = $page * $index - $index;//开始查询的点 = 当前页 * 每页的数量 - 每页的数量
        $limit = " LIMIT {$start},{$index}";
        #4、查询信息内容
        $field = 'a.id,a.thumb,a.name,a.starttime,a.endtime,a.status,a.pv,a.sort,a.num,a.recommend,m.storename,b.name as cate_name';
        $sql = "SELECT {$field} FROM " . tablename(PDO_NAME . 'groupon_activity')
            . " a LEFT JOIN " . tablename(PDO_NAME . "groupon_category")
            . " b ON a.cateid = b.id LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
            . " m ON a.sid = m.id";
        !empty($where) && $sql .= " WHERE {$where} ";
        $sql .= ' GROUP BY a.id ';
        !empty($order) && $sql .= " ORDER BY {$order} ";
        $total = count(pdo_fetchall(str_replace($field, "a.id", $sql)));//获取符合条件的总数量
        $data['page_num'] = ceil($total / $index);//获取一共有多少页
        !empty($limit) && $sql .= $limit;
        $data['list'] = pdo_fetchall($sql);//获取要查询的列表数据
        #5、处理相关信息
        $orderModel = new Order();
        foreach ($data['list'] as $k => &$v) {
            //图片信息转换
            $v['thumb'] = tomedia($v['thumb']);
            //获取销量信息
            $orderW = " fkid = {$v['id']} AND plugin = 'groupon' AND status in ";
            $v['order_purchase'] = $orderModel->getPurchaseQuantity($orderW . " (0,1,2,3,4,6,8,9) ") ?: 0;//已下单
            $v['order_payment'] = $orderModel->getPurchaseQuantity($orderW . " (1,2,3,4,6,8,9) ") ?: 0;//已支付
            $v['order_used'] = $orderModel->getPurchaseQuantity($orderW . " (2,3) ") ?: 0;//已完成
            //时间戳转时间
            $v['starttime'] = date("Y-m-d H:i:s", $v['starttime']);
            $v['endtime'] = date("Y-m-d H:i:s", $v['endtime']);
        }

        wl_json(1, '团购商品列表', $data);
    }

    /**
     * Comment: 获取团购分类列表
     * Author: zzw
     * Date: 2019/7/11 13:57
     */
    public function getClassList() {
        global $_W, $_GPC;
        $where = " uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        $list = pdo_fetchall("SELECT id,name FROM " . tablename(PDO_NAME . 'groupon_category') . " WHERE {$where} ORDER BY sort DESC");

        wl_json(1, '团购分类列表', $list);
    }

    /**
     * Comment: 修改团购商品的某个单项数据信息
     * Author: zzw
     * Date: 2019/7/12 15:20
     */
    public function updateInfo() {
        global $_W, $_GPC;
        #1、参数接收
        if (empty($_GPC['field'])) show_json(0, "缺少参数：修改的字段名称");
        #2、修改内容
        $data[$_GPC['field']] = $_GPC['value'];
        $res = pdo_update(PDO_NAME . 'groupon_activity', $data, array('id' => $_GPC['id']));
        if ($res) {
            show_json(1, "修改成功");
        } else {
            show_json(0, "修改失败");
        }
    }


}