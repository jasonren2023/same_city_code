<?php
defined('IN_IA') or exit('Access Denied');

class Register_WeliamController {
    /*
     * 添加商户
     */
    public function add() {
        global $_W, $_GPC;
        if ($_GPC['op'] == 'selectnickname') {
            //搜索会员
            $con = "uniacid='{$_W['uniacid']}' ";
            $keyword = $_GPC['keyword'];
            if ($keyword != '') {
                $con .= " and (openid LIKE '%{$keyword}%' or id LIKE '%{$keyword}%' or nickname LIKE '%{$keyword}%') ";
            }
            $ds = Store::registerNickname($con);
            if ($ds) {
                foreach ($ds as $key => &$v) {
                    $v['nickname'] = str_replace("'", '', $v['nickname']);
                }
            }
            include wl_template('store/registerQueryNickname');
            exit;
        }
    }
    /*
     * 入驻设置
     */
    public function set() {
        global $_W, $_GPC;
        if ($_GPC['set']) {
            $_GPC['set']['detail'] = htmlspecialchars_decode($_GPC['set']['detail']);
            $res1 = Setting::agentsetting_save($_GPC['set'], 'register');
            wl_message('保存成功！', referer(), 'succuss');
        }
        $set = Setting::agentsetting_read('register');
        include wl_template('store/registerSet');
        exit;
    }
    //付费记录
    public function chargerecode() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = array(
            'uniacid' => $_W['uniacid'],
            'status'  => 3,
            'plugin'  => 'store'
        );

        if (is_agent()) {
            $where['aid'] = $_W['aid'];
        }

        if ($_GPC['orderid']) {
            $where['id'] = $_GPC['orderid'];
        }

        $records = pdo_getslice('wlmerchant_order', $where, array($pindex, $psize), $total, array(), '', "id DESC");
        $pager = wl_pagination($total, $pindex, $psize);

        foreach ($records as $key => &$re) {
            $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $re['sid']), array('logo', 'storename'));
            $re['logo'] = $merchant['logo'];
            $re['storename'] = $merchant['storename'];
            $re['typename'] = pdo_getcolumn(PDO_NAME . 'chargelist', array('id' => $re['fkid']), 'name');
        }


        include wl_template('store/chargerecode');
        exit;
    }
    //搜索用户
    public function searchmember() {
        global $_W, $_GPC;
        $con = $con2 = "uniacid='{$_W['uniacid']}' ";
        $keyword = $_GPC['keyword'];
        if ($keyword != '') {
            $con .= " and nickname LIKE '%{$keyword}%' or uid  LIKE '%{$keyword}%' or openid LIKE '%{$keyword}%'";
            $con2 .= " and nickname LIKE '%{$keyword}%' or uid  LIKE '%{$keyword}%'";
        }
        $ds = pdo_fetchall("select * from" . tablename('wlmerchant_member') . "where $con");

        include wl_template('store/searchmember');
    }

}
