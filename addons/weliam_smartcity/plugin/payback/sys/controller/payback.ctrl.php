<?php
defined('IN_IA') or exit('Access Denied');

class Payback_WeliamController {


    public function cashBackRecord(){
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $start = $pindex * $psize - $psize;
        $keyword = $_GPC['keyword'];//关键字
        $keywordtype = $_GPC['keywordtype'];//关键字类型

        $where = " a.uniacid = {$_W['uniacid']} ";
        if(!empty($_GPC['plugin'])){
            $plugin = $_GPC['plugin'];
            $where .= " AND a.plugin = '{$plugin}'";
        }

        if ($keyword) {
            if ($keywordtype == 1) {
                $where .= " AND d.storename LIKE '%{$keyword}%'";
            } else if ($keywordtype == 2) {
                $where .= " AND a.sid = '{$keyword}'";
            } else if ($keywordtype == 3) {
                $where .= " AND a.doid = '{$keyword}'";
            } else if ($keywordtype == 4) {
                $where .= " AND a.mid = '{$keyword}'";
            } else if ($keywordtype == 5) {
                $where .= " AND b.nickname LIKE '%{$keyword}%'";
            } else if ($keywordtype == 6) {
                $where .= " AND b.mobile LIKE '%{$keyword}%'";
            }
        }

        if (!empty($_GPC['time_limit'])) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $where .= " AND a.createtime >= {$starttime} ";
            $where .= " AND a.createtime <= {$endtime} ";
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        //导出
        if($_GPC['export']){
            $this -> export($where);
        }

        $limit = " LIMIT {$start},{$psize}";
        $sql = "SELECT a.sid,a.mid,a.doid,a.backmoney,a.remark,a.plugin,b.mobile,a.createtime,b.nickname,b.avatar,d.storename FROM "
            . tablename("wlmerchant_payback_record")
            . " as a LEFT JOIN " . tablename(PDO_NAME . "member") . " as b ON a.mid = b.id "
            . " LEFT JOIN " . tablename(PDO_NAME . "merchantdata") . " as d ON a.sid = d.id WHERE {$where} ORDER BY a.createtime DESC";

        $total = pdo_fetchcolumn("SELECT count(a.id) FROM ".tablename("wlmerchant_payback_record") . " as a LEFT JOIN " . tablename(PDO_NAME . "member") . " as b ON a.mid = b.id " . " LEFT JOIN " . tablename(PDO_NAME . "merchantdata") . " as d ON a.sid = d.id WHERE {$where}");

        $pager = wl_pagination($total, $pindex, $psize);
        $records = pdo_fetchall($sql . $limit);
        foreach ($records as &$record){
            $record['createtime'] = date("Y-m-d H:i:s",$record['createtime']);
            $record['avatar'] = tomedia($record['avatar']);
            $record['username'] = pdo_getcolumn(PDO_NAME.'merchantuser',array('id'=>$record['doid']),'name');
            if ($record['plugin'] == 'rush') {
                $record['typename'] = '抢购订单';
                $record['css'] = 'success';
            }else if($record['plugin'] == 'groupon') {
                $record['typename'] = '团购订单';
                $record['css'] = 'info';
            }else if ($record['plugin'] == 'wlfightgroup') {
                $record['typename'] = '拼团订单';
                $record['css'] = 'warning';
            } else if ($record['plugin'] == 'coupon') {
                $record['typename'] = '卡券订单';
                $record['css'] = 'success';
            } else if ($record['plugin'] == 'bargain') {
                $record['typename'] = '砍价订单';
                $record['css'] = 'info';
            } else if ($record['plugin'] == 'halfcard') {
                $record['typename'] = '在线买单';
                $record['css'] = 'success';
            } else if ($record['plugin'] == 'store') {
                $record['typename'] = '店员修改';
                $record['css'] = 'info';
            } else if ($record['plugin'] == 'sys') {
                $record['typename'] = '银行卡返现';
                $record['css'] = 'warning';
                $record['username'] = '- 无 -';
                $record['storename'] = '系统银行卡返现';
            }
        }



        include wl_template('paybackweb/cashBackRecord');
    }

    //导出
    public function export($where){
        global $_W;
        $sql = "SELECT a.sid,a.mid,a.doid,a.backmoney,a.remark,a.plugin,b.mobile,a.createtime,b.nickname,d.storename FROM "
            . tablename("wlmerchant_payback_record")
            . " as a LEFT JOIN " . tablename(PDO_NAME . "member") . " as b ON a.mid = b.id "
            . " LEFT JOIN " . tablename(PDO_NAME . "merchantdata") . " as d ON a.sid = d.id WHERE {$where} ORDER BY a.createtime DESC";
        $records = pdo_fetchall($sql);
        $data = [];
        foreach ($records as &$record){
            $record['createtime'] = date("Y-m-d H:i:s",$record['createtime']);
            if($record['doid'] > 0){
                $record['username'] = pdo_getcolumn(PDO_NAME.'merchantuser',array('id'=>$record['doid']),'name');
            }else{
                $record['username'] =  '- 无 -';
            }
            if ($record['plugin'] == 'rush') {
                $record['typename'] = '抢购订单';
            }else if($record['plugin'] == 'groupon') {
                $record['typename'] = '团购订单';
            }else if ($record['plugin'] == 'wlfightgroup') {
                $record['typename'] = '拼团订单';
            } else if ($record['plugin'] == 'coupon') {
                $record['typename'] = '卡券订单';
            } else if ($record['plugin'] == 'bargain') {
                $record['typename'] = '砍价订单';
            } else if ($record['plugin'] == 'halfcard') {
                $record['typename'] = '在线买单';
            } else if ($record['plugin'] == 'store') {
                $record['typename'] = '店员修改';
            } else if ($record['plugin'] == 'sys') {
                $record['typename'] = '银行卡返现';
                $record['username'] = '- 无 -';
                $record['storename'] = '系统银行卡返现';
            }
            $da = [
                'storename' => $record['storename'].'(SID:'.$record['sid'].')',
                'username' => $record['username'].'(UID:'.$record['doid'].')',
                'typename' => $record['typename'],
                'nickname' => $record['nickname'].'(MID:'.$record['mid'].')',
                'mobile' => $record['mobile'],
                'backmoney' => $record['backmoney'],
                'createtime' => $record['createtime'],
                'remark' => $record['remark'],
            ];
            $data[] = $da;
        }
        /* 输出表头 */
        $filter = array(
            'storename'  => '店铺',
            'username' => '操作店员',
            'typename'  => '操作类型',
            'nickname' => '用户',
            'mobile' => '手机号',
            'backmoney' => '金额',
            'createtime' => '时间',
            'remark' => '备注',
        );

        util_csv::export_csv_2($data, $filter, '支付返现记录表.csv');
        exit();
    }



    public function setting(){
        global $_W, $_GPC;
        $set = Setting::wlsetting_read('payback');
        $banklist = pdo_getall('wlmerchant_payback_bank',array('uniacid' => $_W['uniacid']));

        if (checksubmit('submit')) {
            $data = $_GPC['data'];
            Setting::wlsetting_save($data, 'payback');
            $names = $_GPC['name'];
            $ids = $_GPC['ids'];
            $rates = $_GPC['rate'];
            if($data['status']>0){
                foreach($names as $k => $na){
                    if(empty($ids[$k])){
                        $newbank = [
                            'uniacid' => $_W['uniacid'],
                            'bank'    => $names[$k],
                            'rate'    => $rates[$k]
                        ];
                        pdo_insert(PDO_NAME . 'payback_bank', $newbank);
                    }else{
                        $newbank = [
                            'bank'    => $names[$k],
                            'rate'    => $rates[$k]
                        ];
                        pdo_update(PDO_NAME . 'payback_bank',$newbank,array('id' => $ids[$k]));
                    }
                }
            }
            wl_message('设置成功', web_url('payback/payback/setting'));
        }




        include wl_template('paybackweb/setting');
    }
    public function bankback() {
        include wl_template('paybackweb/bankback');
    }

}