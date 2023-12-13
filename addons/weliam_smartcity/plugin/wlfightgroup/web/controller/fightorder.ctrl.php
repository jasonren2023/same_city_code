<?php
defined('IN_IA') or exit('Access Denied');

class Fightorder_WeliamController {

    //组团列表
    function grouplist() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $wheres = array();
        $wheres['uniacid'] = $_W['uniacid'];
        $wheres['aid'] = $_W['aid'];
        $status0 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_fightgroup_group') . " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ");
        $status1 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_fightgroup_group') . " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 1");
        $status2 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_fightgroup_group') . " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 2");
        $status3 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_fightgroup_group') . " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 3");

        $status = $_GPC['status'];
        if ($status) {
            $wheres['status'] = $status;
        }
        $keywordtype = $_GPC['keywordtype'];
        if ($keywordtype) {
            $keyword = $_GPC['keyword'];
            switch ($keywordtype) {
                case 1:
                    $wheres['@goodsid@'] = $keyword;
                    break;
                case 2:
                    $wheres['sid'] = $keyword;
                    break;
                case 3:
                    $params[':name'] = "%{$keyword}%";
                    $goods = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_fightgroup_goods') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND name LIKE :name", $params);
                    if ($goods) {
                        $goodids = "(";
                        foreach ($goods as $key => $v) {
                            if ($key == 0) {
                                $goodids .= $v['id'];
                            } else {
                                $goodids .= "," . $v['id'];
                            }
                        }
                        $goodids .= ")";
                        $wheres['goodsid#'] = $goodids;
                    } else {
                        $wheres['goodsid#'] = "(0)";
                    }
                    break;
                case 4:
                    $params[':storename'] = "%{$keyword}%";
                    $merchant = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_merchantdata') . "WHERE uniacid = {$_W['uniacid']}  AND storename LIKE :storename", $params);
                    if ($merchant) {
                        $sids = "(";
                        foreach ($merchant as $key => $v) {
                            if ($key == 0) {
                                $sids .= $v['id'];
                            } else {
                                $sids .= "," . $v['id'];
                            }
                        }
                        $sids .= ")";
                        $wheres['sid#'] = $sids;
                    } else {
                        $wheres['sid#'] = "(0)";
                    }
                    break;
            }
        }
        if ($_GPC['time_limit']) {
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $wheres['starttime>'] = $starttime;
            $wheres['starttime<'] = $endtime;
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $orderlist = Wlfightgroup::getNumGroup('*', $wheres, 'ID DESC', $pindex, $psize, 1);
        $pager = $orderlist[1];
        $list = $orderlist[0];
        foreach ($list as $key => &$v) {
            $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $v['sid']), array('storename'));
            $goods = pdo_get('wlmerchant_fightgroup_goods', array('id' => $v['goodsid']), array('name', 'logo'));
            $v['storename'] = $merchant['storename'];
            $v['name'] = $goods['name'];
            $v['logo'] = $goods['logo'];
        }
        include wl_template('ptorder/grouplist');
    }

    //手动成团
    function fishgroup() {
        global $_W, $_GPC;
        load()->library('phpexcel/PHPExcel');
        $groupid = $_GPC['id'];
        $group = Wlfightgroup::getSingleGroup($groupid, '*');
        $lacknum = $group['lacknum'];
        //获取虚拟用户信息
        $start = rand(1, 2580 - $lacknum);
        $memberxls = PATH_WEB . "resource/download/members.xlsx";
        $objPHPExcel = PHPExcel_IOFactory::load($memberxls);
        $sheet = $objPHPExcel->getSheet(0);
        $highestColumn = $sheet->getHighestColumn();
        for ($row = $start; $row < $lacknum + $start; $row++) {
            $members[] = $sheet->rangeToArray("A" . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
        }
        for ($i = 0; $i < $lacknum; $i++) {
            $data = array(
                'uniacid'      => $group['uniacid'],
                'mid'          => 0,
                'aid'          => $group['aid'],
                'fkid'         => $group['goodsid'],
                'sid'          => $group['sid'],
                'status'       => 3,
                'paytype'      => 2,
                'createtime'   => time(),
                'orderno'      => '666666',
                'price'        => 0,
                'num'          => 0,
                'plugin'       => 'wlfightgroup',
                'payfor'       => 'fightsharge',
                'spec'         => '',
                'fightstatus'  => 1,
                'fightgroupid' => $groupid,
                'expressid'    => '',
                'buyremark'    => '',
                'name'         => $members[$i][0][0],
                'buyremark'    => $members[$i][0][1]
            );
            Wlfightgroup::saveFightOrder($data);
        }
        $newdata['lacknum'] = 0;
        $newdata['status'] = 2;
        $newdata['successtime'] = time();
        $orders = pdo_getall('wlmerchant_order', array('fightgroupid' => $group['id'], 'uniacid' => $group['uniacid'], 'aid' => $group['aid'], 'status' => 1));
        $good = pdo_get('wlmerchant_fightgroup_goods',array('id' => $group['goodsid']),array('isdistri','luckynum','luckymoney','disarray','dissettime','isdistristatus'));

        if($group['is_lucky'] > 0){
            $allorderids = array_column($orders,'id');
            $luckykey = array_rand($allorderids,$good['luckynum']);
            if($good['luckynum']>1){
                foreach ($luckykey as $lid){
                    $luckyids[] =  $allorderids[$lid];
                }
            }else{
                $luckyids[] = $allorderids[$luckykey];
            }
            $newdata['luckyorderids'] = serialize($luckyids);
        }

        foreach ($orders as $key => $or) {
            if($or['orderno'] != '666666'){
                if(empty($luckyids) || in_array($or['id'],$luckyids)){
                    if ($or['expressid']) {
                        pdo_update(PDO_NAME . 'order', array('status' => 8), array('id' => $or['id']));
                    } else {
                        if ($or['neworderflag']) {
                            Order::createSmallorder($or['id'], 3);
                            pdo_update(PDO_NAME . 'order', array('status' => 1), array('id' => $or['id']));
                        } else {
                            $recordid = Wlfightgroup::createRecord($or['id'], $or['num']);
                            pdo_update(PDO_NAME . 'order', array('status' => 1, 'recordid' => $recordid), array('id' => $or['id']));
                        }
                    }
                    //处理分销
                    if($or['cerditmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                        $nodis = 1;
                    }else{
                        $nodis = 0;
                    }
                    if (p('distribution') && empty($good['isdistri']) && empty($or['drawid']) && empty($nodis)) {
                        $option = pdo_get('wlmerchant_goods_option', ['id' => $or['specid']]);
                        if ($or['specid']) {
                            $option = pdo_get('wlmerchant_goods_option', ['id' => $or['specid']]);
                            $good['disarray'] = WeliamWeChat::mergeDisArray($option['disarray'],$good['disarray']);
                        }
                        $disarray = unserialize($good['disarray']);
                        $dismoney = sprintf("%.2f", $or['goodsprice'] - $or['vipdiscount']);
                        $disorderid = Distribution::disCore($or['mid'], $dismoney, $disarray, $or['num'], 0, $or['id'], 'fightgroup', $good['dissettime'],$good['isdistristatus']);
                        pdo_update(PDO_NAME . 'order' , ['disorderid'   => $disorderid,] , ['id' => $or['id']]);
                    }
                }else{
                    //修改为待退款并且加入计划任务
                    if($good['luckymoney'] > 0) {
                        pdo_update(PDO_NAME . 'order', ['status' => 6, 'redpagstatus' => 1], ['id' => $or['id']]);
                    }else{
                        pdo_update(PDO_NAME . 'order', ['status' => 6], ['id' => $or['id']]);
                    }
                }
            }
        }
        $res = pdo_update(PDO_NAME . 'fightgroup_group', $newdata, array('id' => $groupid));
        if ($res) {
            News::groupresult($groupid);
            die(json_encode(array('errno' => 0, 'message' => 'ok')));
        } else {
            die(json_encode(array('errno' => 1, 'message' => '未知错误，请重试')));
        }

    }
}