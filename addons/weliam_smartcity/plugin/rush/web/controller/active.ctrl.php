<?php
defined('IN_IA') or exit('Access Denied');

class Active_WeliamController {

    public function activelist() {
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

        $activity = Rush::getNumActive('*', $data, 'sort DESC,ID DESC', $pindex, $psize, 1);
        $pager = $activity[1];
        $activity = $activity[0];
        foreach ($activity as $key => &$value) {
            if($value['sid'] > 0){
                $value['storename'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $value['sid']), 'storename');
            }else if(empty($value['threestatus'])){
                $value['storename'] = '票付通平台商品';
            }else if($value['threestatus'] == 1){
                $value['storename'] = '亿奇达平台商品';
            }else{
                $value['storename'] = '其他平台商品';
            }
            Rush::changeActivestatus($value);
            $value['placeorder'] = WeliamWeChat::getSalesNum(1,$value['id'],0,1,0,0,0,$value['pftid']);   //已下单
            if (empty($value['placeorder'])) {
                $value['placeorder'] = 0;
            }
            $value['alreadypay'] = WeliamWeChat::getSalesNum(1,$value['id'],0,2,0,0,0,$value['pftid']);  //已支付
            if (empty($value['alreadypay'])) {
                $value['alreadypay'] = 0;
            }
            $value['alreadyuse'] = WeliamWeChat::getSalesNum(1,$value['id'],0,3,0,0,0,$value['pftid']);;  //已使用
            if (empty($value['alreadyuse'])) {
                $value['alreadyuse'] = 0;
            }
            //视频号判断
            if(p('wxchannels') && $value['usestatus'] == 1){
                $alchannels =  pdo_get(PDO_NAME.'channels_goods',array('type'=>1,'goodsid'=>$value['id']),['product_id','third_cat_id']);
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

        if (is_store()) {
            $status0 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=1 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=2 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=3 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status IN (0,4) and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=5 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=6 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=8 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status7 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=7 and aid={$_W['aid']} and sid = {$_W['storeid']}");
        } else {
            $status0 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']}");
            $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=1 and aid={$_W['aid']}");
            $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=2 and aid={$_W['aid']}");
            $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=3 and aid={$_W['aid']}");
            $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status IN (0,4) and aid={$_W['aid']}");
            $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=5 and aid={$_W['aid']}");
            $status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=6 and aid={$_W['aid']}");
            $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=8 and aid={$_W['aid']}");
            $status7 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_activity') . " WHERE uniacid={$_W['uniacid']} and status=7 and aid={$_W['aid']}");
        }
        include wl_template('active/active_list');
    }

    public function ajax() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $da = Rush::getSingleGoods($id, '*');
        die(json_encode($da));
    }

    /**
     * Comment: 批量修改商品信息
     * Author: wlf
     * Date: 2020/06/01 14:38
     */
    public function changestatus(){
        global $_W, $_GPC;
        $ids = $_GPC['ids'];
        $type = $_GPC['type'];
        foreach ($ids as$k=>$v){
            $rush = pdo_get('wlmerchant_rush_activity',array('id' => $v),array('starttime','endtime','sid','status'));
            if($type == 1){
                $status = 0;
                if(is_store()){
                    $examine = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$rush['sid']),'audits');
                    if(empty($examine)){
                        $status = 5;
                    }
                }
                if(empty($status)){
                    if ($rush['starttime'] > time()) {
                        $status = 1;
                    }
                    else if ($rush['starttime'] < time() && time() < $rush['endtime']) {
                        $status = 2;
                    }
                    else if ($rush['endtime'] < time()) {
                        $status = 3;
                    }
                }
                pdo_update('wlmerchant_rush_activity', array('status' => $status), array('id' => $v));
            }else if($type == 8 && $rush['status'] == 8){
                Rush::deleteActive(array('id' => $v, 'uniacid' => $_W['uniacid']));
            }else{
                pdo_update('wlmerchant_rush_activity', array('status' => $type), array('id' => $v));
            }
        }
        show_json(1, '操作成功');
    }

    public function delete() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'];
        if ($status == 4) {
            $rush = pdo_get('wlmerchant_rush_activity',array('id' => $id),array('starttime','endtime','sid','status'));
            if(is_store()){
                $examine = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$rush['sid']),'audits');
                if(empty($examine)){
                    $changestatus = 5;
                }
            }
            if(empty($changestatus)){
                if ($rush['starttime'] > time()) {
                    $changestatus = 1;
                }
                else if ($rush['starttime'] < time() && time() < $rush['endtime']) {
                    $changestatus = 2;
                }
                else if ($rush['endtime'] < time()) {
                    $changestatus = 3;
                }
            }
            $res = pdo_update('wlmerchant_rush_activity', array('status' => $changestatus), array('id' => $id));
        } else {
            $res = Rush::updateActive(array('status' => 4), array('id' => $id));
        }
        if ($res) {
            die(json_encode(array('errno' => 0)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    public function delall() {
        global $_W, $_GPC;
        $res = Rush::deleteActive(array('id' => intval($_GPC['id']), 'uniacid' => $_W['uniacid']));
        if ($res) {
            show_json(1, '彻底删除成功');
        } else {
            show_json(0, '彻底删除失败，请重试');
        }
    }

    public function cutoff() {
        global $_W, $_GPC;
        $res = pdo_update('wlmerchant_rush_activity', array('status' => 8), array('id' => intval($_GPC['id'])));
        if ($res) {
            show_json(1, '删除成功');
        } else {
            show_json(0, '删除失败，请重试');
        }
    }

    public function examine() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $flag = $_GPC['flag'];
        if ($flag == 1) {
            $res = Rush::updateActive(array('status' => 1), array('id' => $id));
            News::goodsToExamine($id,'rush');
        } else {
            $res = Rush::updateActive(array('status' => 6), array('id' => $id));
            News::goodsToExamine($id,'rush','未通过');
        }
        if ($res) {
            die(json_encode(array('errno' => 0)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    public function copygood() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $da = Rush::getSingleActive($id, '*');
        unset($da['id']);
        unset($da['a']);
        unset($da['plugin']);
        $da['levelnum'] = $da['num'];
        $da['status'] = 4;
        $res = pdo_insert('wlmerchant_rush_activity', $da);
        if ($res) {
            die(json_encode(array('errno' => 0)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    public function changepv() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        $newvalue = trim($_GPC['value']);
        if ($type == 1) {
            $res = pdo_update('wlmerchant_rush_activity', array('pv' => $newvalue), array('id' => $id));
        } elseif ($type == 2) {
            $res = pdo_update('wlmerchant_rush_activity', array('sort' => $newvalue), array('id' => $id));
        } elseif ($type == 3) {
            $res = pdo_update('wlmerchant_rush_activity', array('num' => $newvalue), array('id' => $id));
        }
        if ($res) {
            show_json(1, '修改成功');
        } else {
            show_json(0, '修改失败，请重试');
        }
    }


    /**
     * Comment: 获取抢购商品信息列表
     * Author: zzw
     * Date: 2019/7/11 14:52
     */
    public function rushList() {
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
        $field = 'a.id,a.thumb,a.name,a.starttime,a.endtime,a.status,a.pv,a.sort,a.num,m.storename,b.name as cate_name';
        $sql = "SELECT {$field} FROM " . tablename(PDO_NAME . 'rush_activity')
            . " a LEFT JOIN " . tablename(PDO_NAME . "rush_category")
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
            $orderW = " activityid = {$v['id']} AND status in ";
            $v['order_purchase'] = $orderModel->getPurchaseQuantity($orderW . " (0,1,2,3,4,6,8,9) ", true) ?: 0;//已下单
            $v['order_payment'] = $orderModel->getPurchaseQuantity($orderW . " (1,2,3,4,6,8,9) ", true) ?: 0;//已支付
            $v['order_used'] = $orderModel->getPurchaseQuantity($orderW . " (2,3) ", true) ?: 0;//已完成
            //时间戳转时间
            $v['starttime'] = date("Y-m-d H:i:s", $v['starttime']);
            $v['endtime'] = date("Y-m-d H:i:s", $v['endtime']);
        }

        wl_json(1, '抢购商品列表', $data);
    }

    /**
     * Comment: 获取抢购商品分类列表
     * Author: zzw
     * Date: 2019/7/11 14:59
     */
    public function getClassList() {
        global $_W, $_GPC;
        $where = " uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        $list = pdo_fetchall("SELECT id,name FROM " . tablename(PDO_NAME . 'rush_category') . " WHERE {$where} ORDER BY sort DESC ");

        wl_json(1, '抢购分类列表', $list);
    }

    /**
     * Comment: 修改抢购商品的某个单项数据信息
     * Author: zzw
     * Date: 2019/7/12 18:16
     */
    public function updateInfo() {
        global $_W, $_GPC;
        #1、参数接收
        if (empty($_GPC['field'])) show_json(0, "缺少参数：修改的字段名称");
        #2、修改内容
        $data[$_GPC['field']] = $_GPC['value'];
        $res = pdo_update(PDO_NAME . 'rush_activity', $data, array('id' => $_GPC['id']));
        if ($res) {
            show_json(1, "修改成功");
        } else {
            show_json(0, "修改失败");
        }
    }

}