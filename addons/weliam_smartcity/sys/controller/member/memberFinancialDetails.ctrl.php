<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 用户财务明细
 * Author: zzw
 * Date: 2021/1/8 10:59
 * Class MemberFinancialDetails_WeliamController
 */
class MemberFinancialDetails_WeliamController {
    //充值明细
    public function recharge() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where['uniacid'] = $_W['uniacid'];
        $where['status'] = 3;
        $where['plugin'] = 'member';

        if ($_GPC['paytype']) {
            $where['paytype'] = $_GPC['paytype'];
        }
        if ($_GPC['time_limit']) {
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $where['createtime>'] = $starttime;
            $where['createtime<'] = $endtime;
        }

        if($_GPC['export']){
            $this -> exportRecharge($where);
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $details = Util::getNumData('orderno,paytype,mid,paytime,price', PDO_NAME . 'order', $where, 'ID DESC', $pindex, $psize, 1);
        $pager = $details[1];
        $details = $details[0];
        if ($details) {
            foreach ($details as $key => &$det) {
                $member = Member::wl_member_get($det['mid'], array('nickname', 'avatar'));
                $det['nickname'] = $member['nickname'];
                $det['avatar'] = tomedia($member['avatar']);
                $det['paytime'] = date('Y-m-d H:i:s', $det['paytime']);
            }
        }

        include wl_template('member/rechargelist');
    }
    //积分明细
    public function integral() {
        global $_W, $_GPC;
        #1、获取基本参数
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $keyword = $_GPC['keyword'];//关键字
        $keywordtype = $_GPC['keywordtype'];//关键字类型
        $type = $_GPC['type'];//类型
        $timeLimit = $_GPC['time_limit'];//时间段
        #2、获取条件信息
        $where = " a.credittype = 'credit1' AND a.uniacid = {$_W['uniacid']} ";
        if ($type) {
            if ($type == 1) {
                $where .= " AND a.num >= 0 ";
            } else {
                $where .= " AND a.num <= 0 ";
            }
        }
        if ($timeLimit) {
            $starttime = strtotime($timeLimit['start']);
            $endtime = strtotime($timeLimit['end']);
            $where .= " AND a.createtime >= {$starttime} ";
            $where .= " AND a.createtime <= {$endtime} ";
        }
        if ($keyword) {
            if ($keywordtype == 1) {
                $where .= " AND b.id LIKE '%{$keyword}%'";
            } else if ($keywordtype == 2) {
                $where .= " AND b.nickname LIKE '%{$keyword}%'";
            } else if ($keywordtype == 3) {
                $where .= " AND b.mobile LIKE '%{$keyword}%'";
            }
        }

        if($_GPC['export']){
            $this -> exportIntegral($where);
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        $start = $pindex * $psize - $psize;
        $limit = " LIMIT {$start},{$psize} ";
        #3、获取信息数据
        $sql = "SELECT a.id,a.num,a.createtime,a.uid,a.remark,b.nickname,b.avatar FROM "
            . tablename("mc_credits_record")
            . " a LEFT JOIN " . tablename(PDO_NAME . "member") . " b ON a.uid = b.uid WHERE {$where} AND b.id > 0 ORDER BY createtime DESC";
        $total = pdo_fetchcolumn('SELECT count(*) FROM '.tablename('mc_credits_record') . " a LEFT JOIN " . tablename(PDO_NAME . "member") . " b ON a.uid = b.uid WHERE {$where} AND b.id > 0");
        //wl_debug($total);
        $details = pdo_fetchall($sql . $limit);
        if ($details) {
            foreach ($details as $key => &$det) {
                $det['avatar'] = tomedia($det['avatar']);
                $det['createtime'] = date('Y-m-d H:i:s', $det['createtime']);
            }
        }
        $pager = wl_pagination($total, $pindex, $psize);
        include wl_template('member/integrallist');
    }
    //余额明细
    public function balance() {
        global $_W, $_GPC;
        #1、获取基本参数
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $keyword = $_GPC['keyword'];//关键字
        $keywordtype = $_GPC['keywordtype'];//关键字类型
        $type = $_GPC['type'];//类型
        $timeLimit = $_GPC['time_limit'];//时间段
        #2、获取条件信息
        $where = " a.credittype = 'credit2' AND a.uniacid = {$_W['uniacid']} AND b.uniacid = {$_W['uniacid']} ";
        if ($type) {
            if ($type == 1) {
                $where .= " AND a.num >= 0 ";
            } else {
                $where .= " AND a.num <= 0 ";
            }
        }
        if ($timeLimit) {
            $starttime = strtotime($timeLimit['start']);
            $endtime = strtotime($timeLimit['end']);
            $where .= " AND a.createtime >= {$starttime} ";
            $where .= " AND a.createtime <= {$endtime} ";
        }
        if ($keyword) {
            if ($keywordtype == 1) {
                $where .= " AND b.id LIKE '%{$keyword}%'";
            } else if ($keywordtype == 2) {
                $where .= " AND b.nickname LIKE '%{$keyword}%'";
            } else if ($keywordtype == 3) {
                $where .= " AND b.mobile LIKE '%{$keyword}%'";
            }
        }
        if($_GPC['export']){
            $this -> exportBalance($where);
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        $start = $pindex * $psize - $psize;
        $limit = " LIMIT {$start},{$psize} ";

        #3、获取信息数据
        $sql = "SELECT a.id,a.num,a.createtime,a.uid,a.remark,b.nickname,b.avatar FROM "
            . tablename("mc_credits_record")
            . " a LEFT JOIN " . tablename(PDO_NAME . "member") . " b ON a.uid = b.uid WHERE {$where} AND b.id > 0  ORDER BY createtime DESC";
        $total = pdo_fetchcolumn('SELECT count(a.id) FROM ' . tablename('mc_credits_record') . " a LEFT JOIN " . tablename(PDO_NAME . "member") . " b ON a.uid = b.uid WHERE {$where} AND b.id > 0 ");
        $details = pdo_fetchall($sql . $limit);
        if ($details) {
            foreach ($details as $key => &$det) {
                $det['avatar'] = tomedia($det['avatar']);
                $det['createtime'] = date('Y-m-d H:i:s', $det['createtime']);
            }
        }
        $pager = wl_pagination($total, $pindex, $psize);
        include wl_template('member/balancelist');
    }

    public function exportBalance($where){
        global $_W, $_GPC;
        $limit = " LIMIT 20000";
        $sql = "SELECT a.id,a.num,a.createtime,a.uid,a.remark,b.nickname,b.id as mid FROM "
            . tablename("mc_credits_record")
            . " a LEFT JOIN " . tablename(PDO_NAME . "member") . " b ON a.uid = b.uid WHERE {$where} AND b.id > 0  ORDER BY createtime DESC";
        $total = pdo_fetchcolumn('SELECT count(a.id) FROM ' . tablename('mc_credits_record') . " a LEFT JOIN " . tablename(PDO_NAME . "member") . " b ON a.uid = b.uid WHERE {$where} AND b.id > 0 ");
        $details = pdo_fetchall($sql . $limit);
        if ($details) {
            foreach ($details as $key => &$det) {
                $det['createtime'] = date('Y-m-d H:i:s', $det['createtime']);
            }
        }

        /* 输出表头 */
        $filter = array(
            'mid'  => '用户mid',//U
            'nickname' => '用户昵称',//A
            'num'  => '变更数量',//B
            'remark' => '变更详情',//C
            'createtime' => '变更时间',//D
        );

        $data = array();
        for ($i=0; $i < count($details) ; $i++) {
            foreach ($filter as $key => $title) {
                $data[$i][$key] = $details[$i][$key];
            }
        }
        util_csv::export_csv_2($data, $filter, '余额变更记录表.csv');
        exit();

    }

    public function exportIntegral($where){
        global $_W, $_GPC;
        $limit = " LIMIT 20000";
        $sql = "SELECT a.id,a.num,a.createtime,a.uid,a.remark,b.nickname,b.id as mid FROM "
            . tablename("mc_credits_record")
            . " a LEFT JOIN " . tablename(PDO_NAME . "member") . " b ON a.uid = b.uid WHERE {$where} AND b.id > 0 ORDER BY createtime DESC";
        $total = count(pdo_fetchall($sql));
        $details = pdo_fetchall($sql . $limit);
        if ($details) {
            foreach ($details as $key => &$det) {
                $det['createtime'] = date('Y-m-d H:i:s', $det['createtime']);
            }
        }
        /* 输出表头 */
        $filter = array(
            'mid'  => '用户mid',//U
            'nickname' => '用户昵称',//A
            'num'  => '变更数量',//B
            'remark' => '变更详情',//C
            'createtime' => '变更时间',//D
        );
        $data = array();
        for ($i=0; $i < count($details) ; $i++) {
            foreach ($filter as $key => $title) {
                $data[$i][$key] = $details[$i][$key];
            }
        }
        util_csv::export_csv_2($data, $filter, '积分变更记录表.csv');
        exit();
    }

    public function exportRecharge($where){
        global $_W, $_GPC;
        $details = Util::getNumData('orderno,paytype,mid,paytime,price', PDO_NAME . 'order', $where, 'ID DESC', 0, 0, 1);
        $details = $details[0];
        if ($details) {
            foreach ($details as $key => &$det) {
                $member = Member::wl_member_get($det['mid'], array('nickname', 'avatar'));
                $det['nickname'] = $member['nickname'];
                $det['avatar'] = tomedia($member['avatar']);
                $det['paytime'] = date('Y-m-d H:i:s', $det['paytime']);
                $det['orderno'] = "\t".$det['orderno']."\t";
            }
        }
        /* 输出表头 */
        $filter = array(
            'orderno' => '充值单号',
            'mid'  => '用户mid',//U
            'nickname' => '用户昵称',//A
            'price'  => '充值金额',//B
            'paytype' => '支付方式',//C
            'paytime' => '充值时间',//D
        );

        $data = array();
        for ($i=0; $i < count($details) ; $i++) {
            foreach ($filter as $key => $title) {
                if($key == 'paytype'){
                    switch ($details[$i][$key]) {
                        case '2':
                            $data[$i][$key] = '微信支付';
                            break;
                        case '3':
                            $data[$i][$key] = '支付宝';
                            break;
                        default:
                            $data[$i][$key]  = '其他方式';
                            break;
                    }
                }else{
                    $data[$i][$key] = $details[$i][$key];
                }
            }
        }
        util_csv::export_csv_2($data, $filter, '充值记录表.csv');
        exit();
    }


}
