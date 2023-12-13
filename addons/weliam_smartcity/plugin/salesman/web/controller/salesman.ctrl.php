<?php
defined('IN_IA') or exit('Access Denied');

class Salesman_WeliamController {

    public function lists() {
        global $_W, $_GPC;
        $settings = Setting::agentsetting_read('salesman');
        $where = array('uniacid' => $_W['uniacid'], 'ismain' => 4,'aid'=>$_W['aid']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;
        if ($_GPC['name']) {
            $where['name LIKE'] = '%' . $_GPC['name'] . '%';
        }

        $lists = pdo_getslice('wlmerchant_merchantuser', $where, array($pindex, $psize), $total, array(), '', "id DESC");
        foreach ($lists as &$list) {
            $list['member'] = pdo_get('wlmerchant_member', array('id' => $list['mid']), array('nickname', 'avatar'));
            $list['store'] = pdo_get('wlmerchant_merchantdata', array('id' => $list['storeid']), array('storename', 'logo'));
        }
        $pager = wl_pagination($total, $pindex, $psize);

        include wl_template('salesman/lists');
    }

    public function edit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if (checksubmit('submit')) {
            $data = array(
                'mid'          => intval($_GPC['mid']),
                'storeid'      => intval($_GPC['storeid']),
                'name'         => trim($_GPC['name']),
                'mobile'       => trim($_GPC['mobile']),
                'alone'        => intval($_GPC['alone']),
                'scale'        => sprintf("%.2f", trim($_GPC['scale'])),
                'manage_store' => intval($_GPC['manage_store']),
                'hasmanage'    => intval($_GPC['hasmanage']),
                'enabled'      => intval($_GPC['enabled']),
                'alone_plugin'  => intval($_GPC['alone_plugin']),
                'sales_plugin'  => serialize($_GPC['plugin'])
            );

            if (!empty($id)) {
                pdo_update('wlmerchant_merchantuser', $data, array('id' => $id));
            } else {
                $has = pdo_getcolumn('wlmerchant_merchantuser', array('mid' => $data['mid'], 'storeid' => $data['storeid'],'ismain'=>4), 'id');
                if (!empty($has)) {
                    wl_message('当前商家已存在此业务员，请勿重复添加', referer(), 'error');
                }
                $aid = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$data['storeid']),'aid');
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $aid;
                $data['ismain'] = 4;
                $data['createtime'] = time();
                pdo_insert('wlmerchant_merchantuser', $data);
                $id = pdo_insertid();
            }
            wl_message('编辑业务员成功', web_url('salesman/salesman/lists'), 'success');
        }

        if (!empty($id)) {
            $item = pdo_get('wlmerchant_merchantuser', array('uniacid' => $_W['uniacid'], 'id' => $id));
            if (!empty($item['mid'])) {
                $member = pdo_get(PDO_NAME.'member' , ['id' => $item['mid']] , ['nickname','avatar']);
                $item['nickname'] = $member['nickname'];
                $item['avatar'] = tomedia($member['avatar']);
            }
            if (!empty($item['storeid'])) {
                $store = pdo_get(PDO_NAME.'merchantdata', ['id' => $item['storeid']] , ['storename','logo']);
                $item['storename'] = $store['storename'];
                $item['logo'] = tomedia($store['logo']);
            }
            $plugin = unserialize($item['sales_plugin']);
        }


        include wl_template('salesman/edit');
    }

    public function details() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where['uniacid'] = $_W['uniacid'];
        $where['status'] = 1;
        $where['aid'] = $_W['aid'];
        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 1:
                        $where['leadid'] = $_GPC['keyword'];
                        break;
                    case 5:
                        $where['buymid'] = $_GPC['keyword'];
                        break;
                    case 3:
                        $where['price>'] = $_GPC['keyword'];
                        break;
                    case 4:
                        $where['price<'] = $_GPC['keyword'];
                        break;
                    default:
                        break;
                }
                if ($_GPC['keywordtype'] == 2) {
                    $keyword = $_GPC['keyword'];
                    $params[':nickname'] = "%{$keyword}%";
                    $goods = pdo_fetchall("SELECT id,nickname FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname", $params);
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
                        $where['leadid#'] = $goodids;
                    } else {
                        $where['leadid#'] = "(0)";
                    }
                }
                if ($_GPC['keywordtype'] == 6) {
                    $keyword = $_GPC['keyword'];
                    $params[':nickname'] = "%{$keyword}%";
                    $goods = pdo_fetchall("SELECT id,nickname FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname", $params);
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
                        $where['buymid#'] = $goodids;
                    } else {
                        $where['buymid#'] = "(0)";
                    }
                }
            }
        }
        if ($_GPC['orderstatus']) {
            $where['type'] = $_GPC['orderstatus'];
        }
        if ($_GPC['ordertype']) {
            $where['plugin'] = $_GPC['ordertype'];
        }

        if ($_GPC['time_limit']) {
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $where['createtime>'] = $starttime;
            $where['createtime<'] = $endtime;
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time() + 86400;
        }
        if ($_GPC['exportflag']) {
            $this->exportdetail($where);
        }
        $details = Util::getNumData('*', PDO_NAME . 'disdetail', $where, 'ID DESC', $pindex, $psize, 1);
        $pager = $details[1];
        $details = $details[0];
        foreach ($details as $key => &$detail) {
            $detail['leadname'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $detail['leadid']), 'nickname');
            if ($detail['buymid'] < 0) {
                $detail['buyname'] = '系统';
            } else if($detail['status'] == 1){
                $detail['buyname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $detail['buymid']), 'storename');
            }else{
                $detail['buyname'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $detail['buymid']), 'nickname');
            }
            $detail['typetext'] = $detail['type'] == 1 ? '收入' : "支出";
            switch ($detail['plugin']) {
                case 'rush':
                    $detail['pluginname'] = '抢购订单';
                    $detail['pluginno'] = 1;
                    break;
                case 'groupon':
                    $detail['pluginname'] = '团购订单';
                    $detail['pluginno'] = 10;
                    break;
                case 'fightgroup':
                    $detail['pluginname'] = '拼团订单';
                    $detail['pluginno'] = 2;
                    break;
                case 'coupon':
                    $detail['pluginname'] = '卡券订单';
                    $detail['pluginno'] = 3;
                    break;
                case 'bargain':
                    $detail['pluginname'] = '砍价活动';
                    $detail['pluginno'] = 12;
                    break;
                case 'payonline':
                    $detail['pluginname'] = '在线买单';
                    $detail['orderurl'] = web_url("order/orderPayOnline/payonlinelist", array('orderid' => $detail['disorderid']));
                    break;
                case 'citydelivery':
                    $detail['pluginname'] = '同城配送';
                    $detail['pluginno'] = 14;
                    break;
                default:
                    $detail['pluginname'] = '未知插件';
                    break;
            }
            if(empty($detail['orderurl'])){
                $detail['orderurl'] = web_url("order/wlOrder/orderdetail", array('orderid' => $detail['disorderid'], 'type' => $detail['pluginno']));
            }
            $detail['pluginname'] = $detail['pluginname'] . '业务员佣金';
            $detail['createtime'] = date('Y-m-d H:i:s', $detail['createtime']);
        }

        include wl_template('salesman/disdetail');
    }

    function exportdetail($where) {
        global $_W, $_GPC;

        $details = Util::getNumData('*', PDO_NAME . 'disdetail', $where, 'ID DESC', 0, 0, 1);
        $details = $details[0];
        foreach ($details as $key => &$detail) {
            $detail['leadname'] = pdo_getcolumn(PDO_NAME . 'merchantuser', array('mid' => $detail['leadid'],'storeid'=>$detail['buymid']), 'name');
            if ($detail['buymid'] < 0) {
                $detail['buyname'] = '系统';
            } else if($detail['status'] == 1){
                $detail['buyname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $detail['buymid']), 'storename');
            }else{
                $detail['buyname'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $detail['buymid']), 'nickname');
            }
            $detail['typetext'] = $detail['type'] == 1 ? '收入' : "支出";
            switch ($detail['plugin']) {
                case 'rush':
                    $detail['pluginname'] = '抢购订单';
                    $detail['pluginno'] = 1;
                    break;
                case 'groupon':
                    $detail['pluginname'] = '团购订单';
                    $detail['pluginno'] = 10;
                    break;
                case 'fightgroup':
                    $detail['pluginname'] = '拼团订单';
                    $detail['pluginno'] = 2;
                    break;
                case 'coupon':
                    $detail['pluginname'] = '卡券订单';
                    $detail['pluginno'] = 3;
                    break;
                case 'bargain':
                    $detail['pluginname'] = '砍价活动:';
                    $detail['pluginno'] = 12;
                    break;
                default:
                    $detail['pluginname'] = '未知插件';
                    break;
            }
            $detail['pluginname'] = $detail['pluginname'] . '业务员佣金';
            $detail['createtime'] = date('Y-m-d H:i:s', $detail['createtime']);
            //查询订单编号
            if ($detail['plugin'] == 'rush') {
                $detail['orderno'] = pdo_getcolumn(PDO_NAME . 'rush_order', array('id' => $detail['disorderid']), 'orderno');
            } else if ($detail['plugin'] != 'cash' && $detail['plugin'] != 'system') {
                $detail['orderno'] = pdo_getcolumn(PDO_NAME . 'order', array('id' => $detail['disorderid']), 'orderno');
            }
            $detail['orderno'] = $detail['orderno'] . "\t";
        }
        /* 输出表头 */
        $filter = array(
            'id'         => '记录id',
            'leadid'     => '业务员MID',
            'leadname'   => '业务员姓名',
            'orderno'    => '订单编号',
            'typetext'   => '收支',
            'price'      => '金额',
            'buyname'    => '来源',
            'pluginname' => '描述',
            'createtime' => '时间',
        );
        $data = array();
        foreach ($details as $k => $v) {
            foreach ($filter as $key => $title) {
                $data[$k][$key] = $v[$key];
            }
        }
        util_csv::export_csv_2($data, $filter, '业务员明细.csv');
        exit;
    }


    public function del() {
        global $_W, $_GPC;
        if ($_GPC['id']) {
            pdo_delete('wlmerchant_merchantuser', array('uniacid' => $_W['uniacid'], 'id' => $_GPC['id']));
            show_json(1);
        } else {
            show_json(0, '删除失败');
        }
    }

    public function getmember() {
        global $_W, $_GPC;
        $where = array('uniacid' => $_W['uniacid'], 'disflag' => 1);
        if (!empty($_GPC['search'])) {
            $where['nickname LIKE'] = "%" . trim($_GPC['search']) . "%";
        }

        $members = pdo_getall('wlmerchant_distributor', $where, array('mid', 'nickname'), '', '', 10);
        $data = [];
        foreach ($members as &$member) {
            $data[] = ['id' => $member['mid'], 'text' => $member['nickname']];
        }

        die(json_encode($data));
    }

    public function getstore() {
        global $_W, $_GPC;
        $where = array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'enabled' => 1);
        if (!empty($_GPC['search'])) {
            $where['storename LIKE'] = "%" . trim($_GPC['search']) . "%";
        }

        $members = pdo_getall('wlmerchant_merchantdata', $where, array('id', 'storename'), '', '', 100);
        $data = [];
        foreach ($members as &$member) {
            $data[] = ['id' => $member['id'], 'text' => $member['storename']];
        }

        die(json_encode($data));
    }

    public function setting() {
        global $_W, $_GPC;
        $settings = Setting::agentsetting_read('salesman');
        $plugin = unserialize($settings['plugin']);
        if (checksubmit('submit')) {
            $base = array(
                'scale'     => sprintf("%.2f", trim($_GPC['scale'])),
                'hasmanage' => intval($_GPC['hasmanage']),
                'ismanager' => intval($_GPC['ismanager']),
                'isopen'    => intval($_GPC['isopen']),
                'plugin'    => serialize($_GPC['plugin']),
            );
            Setting::agentsetting_save($base, 'salesman');
            wl_message('更新设置成功！', web_url('salesman/salesman/setting'));
        }
        include wl_template('salesman/setting');
    }

}








