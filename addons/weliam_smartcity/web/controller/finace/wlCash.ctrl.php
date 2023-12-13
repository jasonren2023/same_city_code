<?php
defined('IN_IA') or exit('Access Denied');

class WlCash_WeliamController {
    public function cashSurvey() {
        global $_W, $_GPC;
        $refresh = $_GPC['refresh'] ? 1 : 0;

        $timetype = $_GPC['timetype'];
        $time_limit = $_GPC['time_limit'];
        $merchantid = $_GPC['merid'];
        if ($time_limit) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        $merchants = pdo_getall('wlmerchant_merchantdata', array('aid' => $_W['aid']));

        $data = Merchant::agentCashSurvey($refresh, $timetype, $starttime, $endtime, $merchantid);
        $agents = $data[0];
        $children = $data[1];
        $max = $data[2];
        $allMoney = $data[3];
        $time = $data[4];
        $newdata = $data[5];

        //		wl_debug($merchantid);
        include wl_template('finace/cashSurvey');
    }

    public function cashset() {
        global $_W, $_GPC;
        $cashset = Setting::agentsetting_read('cashset');
        if (checksubmit('submit')) {
            $set = $_GPC['cashset'];
            $res1 = Setting::agentsetting_save($set, 'cashset');
            if ($res1) {
                wl_message('保存设置成功！', referer(), 'success');
            } else {
                wl_message('保存设置失败！', referer(), 'error');
            }
        }

        include wl_template('finace/cashset');
    }

    public function cashApply() {
        global $_W, $_GPC;
        //提现申请
        if ($_GPC['type'] == 'submit' && !empty($_GPC['id'])) {
            pdo_update(PDO_NAME . 'settlement_record', array('status' => 2, 'updatetime' => TIMESTAMP), array('id' => $_GPC['id']));
            wl_message('提交成功！', web_url('finace/finaceWithdrawalApply/cashApply', array('status' => 2)), 'success');
        } else if ($_GPC['type'] == 'reject' && !empty($_GPC['id'])) {
            pdo_update(PDO_NAME . 'settlement_record', array('status' => '-2', 'updatetime' => TIMESTAMP), array('id' => $_GPC['id']));
            $record = pdo_get(PDO_NAME . 'settlement_record', array('id' => $_GPC['id']), array('type', 'sid', 'aid', 'sapplymoney', 'id'));
            //			wl_debug($record);
            if ($record['type'] == 1) {
                Store::settlement($record['id'], 0, $record['sid'], $record['sapplymoney'], 0, $record['sapplymoney'], 7, 0);
            } else if ($record['type'] == 2) {
                Store::settlement($record['id'], 0, $record['sid'], $record['sapplymoney'], 0, 0, 7, 0);
            }
            wl_message('驳回成功！', web_url('finace/finaceWithdrawalApply/cashApply', array('status' => 6)), 'success');
        } else {
            $where = array();
            $status = $_GPC['status'] ? $_GPC['status'] : 1;
            if ($status == 1)
                $where['status'] = 1;
            if ($status == 2)
                $where['status'] = 2;
            if ($status == 3)
                $where['status'] = 3;
            if ($status == 4)
                $where['status'] = 4;
            if ($status == 5)
                $where['status'] = 5;
            if ($status == 6)
                $where['#status#'] = '(-1,-2)';
            $where['type'] = 1;
            $where['aid'] = $_W['aid'];
            $list = Util::getNumData('*', PDO_NAME . 'settlement_record', $where);
            $list = $list[0];
            foreach ($list as $key => &$value) {
                $value['sName'] = Util::idSwitch('sid', 'sName', $value['sid']);
                $value['aName'] = Util::idSwitch('aid', 'aName', $value['aid']);
            }
        }
        include wl_template('finace/cashConfirm');
    }

    /**
     * Comment: 编辑代理商分账信息
     */
    public function allidset(){
        global $_W, $_GPC;
        $cashset = Util::getSingelData('wxpaysetid,apppaysetid', PDO_NAME . 'agentusers', array('id' => $_W['aid']));
        //支付信息列表
        if($_W['wlsetting']['cashset']['allocationtype'] == 1){
            $paylist = pdo_getall(PDO_NAME."payment",['uniacid'=>$_W['uniacid'],'type'=>1],['id','name']);
        }
        if (checksubmit('submit')) {
            $set = $_GPC['cashset'];
//            if ($set['wxsysalltype'] == 1) {
//                if (empty($set['wxmerchantid'])) {
//                    show_json(0, '请设置公众号代理分账商户号');
//                }
//                if (empty($set['wxmerchantname'])) {
//                    show_json(0, '请设置公众号代理分账商户名称');
//                }
//            } else if ($set['wxsysalltype'] == 2) {
//                if (empty($set['wxallmid'])) {
//                    show_json(0, '请设置公众号代理分账个人微信号');
//                } else {
//                    $member = pdo_get('wlmerchant_member', array('id' => $set['wxallmid']), array('openid'));
//                    if (empty($member['openid'])) {
//                        show_json(0, '所选用户无微信公众号账户信息，请重选');
//                    }
//                }
//            }
//            if ($set['appsysalltype'] == 1) {
//                if (empty($set['appmerchantid'])) {
//                    show_json(0, '请设置小程序代理分账商户号');
//                }
//                if (empty($set['appmerchantname'])) {
//                    show_json(0, '请设置小程序代理分账商户名称');
//                }
//            } else if ($set['appsysalltype'] == 2) {
//                if (empty($set['appallmid'])) {
//                    show_json(0, '请设置小程序代理分账个人微信号');
//                } else {
//                    $member = pdo_get('wlmerchant_member', array('id' => $set['appallmid']), array('wechat_openid'));
//                    if (empty($member['wechat_openid'])) {
//                        show_json(0, '所选用户无微信小程序账户信息，请重选');
//                    }
//                }
//            }
            $res1 = pdo_update(PDO_NAME . 'agentusers', $set, array('id' => $_W['aid']));

            if ($res1) {
                show_json(1);
            } else {
                show_json(0,'保存设置失败,请重试');
            }
        }
        include wl_template('finace/allidset');
    }




    public function detail() {
        global $_W, $_GPC;
        //提现申请详情
        $id = $_GPC['id'];
        $where = array();
        $where['id'] = $id;
        $settlementRecord = Util::getSingelData('*', PDO_NAME . 'settlement_record', $where);
        $settlementRecord['sName'] = Util::idSwitch('sid', 'sName', $settlementRecord['sid']);
        $settlementRecord['aName'] = Util::idSwitch('aid', 'aName', $settlementRecord['aid']);
        $orders = unserialize($settlementRecord['ids']);
        $list = array();
        foreach ($orders as $id) {
            if ($settlementRecord['type'] == 1) {
                if ($settlementRecord['type2'] == 1) {
                    $v = Util::getSingelData('*', PDO_NAME . 'order', array('id' => $id));
                    $coupon = pdo_get('wlmerchant_couponlist', array('id' => $v['fkid']), array('title', 'logo'));
                    $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $v['sid']), array('storename'));
                    $member = pdo_get('wlmerchant_member', array('id' => $v['mid']), array('nickname', 'avatar', 'mobile'));
                    $v['gname'] = $coupon['title'];
                    $v['gimg'] = tomedia($coupon['logo']);
                    $v['storename'] = $merchant['storename'];
                    $v['nickname'] = $member['nickname'];
                    $v['headimg'] = $member['avatar'];
                    $v['mobile'] = $member['mobile'];
                    $list[] = $v;
                } else if ($settlementRecord['type2'] == 2) {
                    $v = Util::getSingelData('*', PDO_NAME . 'order', array('id' => $id));
                    $fightgoods = pdo_get('wlmerchant_fightgroup_goods', array('id' => $v['fkid']), array('name', 'logo'));
                    $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $v['sid']), array('storename'));
                    $member = pdo_get('wlmerchant_member', array('id' => $v['mid']), array('nickname', 'avatar', 'mobile'));
                    $v['gname'] = $fightgoods['name'];
                    $v['gimg'] = tomedia($fightgoods['logo']);
                    $v['storename'] = $merchant['storename'];
                    $v['nickname'] = $member['nickname'];
                    $v['headimg'] = $member['avatar'];
                    $v['mobile'] = $member['mobile'];
                    $list[] = $v;
                } else {
                    $list[] = Rush::getSingleOrder($id, "*");
                }
            }

            if ($settlementRecord['type'] == 2)
                $list[] = Util::getSingelData("*", PDO_NAME . 'vip_record', array('id' => $id));
            if ($settlementRecord['type'] == 3)
                $list[] = Util::getSingelData("*", PDO_NAME . 'halfcard_record', array('id' => $id));
        }
        if ($settlementRecord['type'] == 2) {
            foreach ($list as $key => &$value) {
                $value['areaName'] = Util::idSwitch('areaid', 'areaName', $value['areaid']);
                $value['member'] = Member::wl_member_get($value['mid']);
            }
        }
        if ($settlementRecord['type'] == 3) {
            foreach ($list as $key => &$v) {
                $user = pdo_get('wlmerchant_member', array('id' => $v['mid']));
                $v['nickname'] = $user['nickname'];
                $v['avatar'] = $user['avatar'];
                $v['mobile'] = $user['mobile'];
            }
        }
        include wl_template('finace/cashDetail');
    }

    public function settlement() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $where = array();
        $where['id'] = $id;
        $settlementRecord = Util::getSingelData('*', PDO_NAME . 'settlement_record', $where);
        $settlementRecord['sName'] = Util::idSwitch('sid', 'sName', $settlementRecord['sid']);
        $settlementRecord['aName'] = Util::idSwitch('aid', 'aName', $settlementRecord['aid']);
        $_GPC['type'] = $_GPC['type'] ? $_GPC['type'] : 'settlement';
        if ($_GPC['type'] == 'money_record') {
            $pindex = max(1, intval($_GPC['page']));
            $psize = 15;
            $moneyRecordData = SingleMerchant::getMoneyRecord($id, $pindex, $psize, 1);
            $moneyRecord = $moneyRecordData[0];

            $pager = $moneyRecordData[1];
            foreach ($moneyRecord as &$item) {
                if ($item['orderid'])
                    $item['order'] = Rush::getSingleOrder($item['orderid'], '*');
            }
        }
        if ($_GPC['type'] == 'settlement_record') {
            //结算记录
            $id = $_GPC['id'];
            $merchant = Store::getSingleStore($id);
            $account = pdo_fetch("SELECT * FROM " . tablename('tg_merchant_account') . " WHERE uniacid = {$_W['uniacid']} and merchantid={$id}");
            $merchant['amount'] = $account['amount'];
            $merchant['no_money'] = $account['no_money'];
            $merchant['no_money_doing'] = $account['no_money_doing'];
            $list = pdo_fetchall("select * from" . tablename('tg_merchant_record') . "where merchantid='{$id}' and uniacid={$_W['uniacid']} ");

        }
        if ($_GPC['type'] == 'settlement') {
            //结算操作
            $merchant = Store::getSingleStore($settlementRecord['sid']);
            $_GPC['accountType'] = $_GPC['accountType'] ? $_GPC['accountType'] : 'f2f';
            if (checksubmit('submit') && $_GPC['accountType'] == 'weixin') {
            }
            if (checksubmit('submit') && $_GPC['accountType'] == 'f2f') {
                if ($settlementRecord['status'] != 4)
                    wl_message('结算状态错误！', web_url('finace/finaceWithdrawalApply/cashApply'), 'error');
                $money = $_GPC['money'];
                //实际结算给商家
                $spercent = $_GPC['spercent'] ? $_GPC['spercent'] : $settlementRecord['spercent'];
                //佣金百分比
                $spercentMoney = $_GPC['spercentMoney'];
                //佣金
                if (is_numeric($money)) {
                    if (pdo_update(PDO_NAME . 'settlement_record', array('status' => 5, 'updatetime' => TIMESTAMP, 'spercentmoney' => $spercentMoney, 'spercent' => $spercent, 'sgetmoney' => $money), array('id' => $_GPC['id']))) {
                        $orders = unserialize($settlementRecord['ids']);
                        foreach ($orders as $iid) {
                            if ($settlementRecord['type2']) {
                                pdo_update(PDO_NAME . 'order', array('issettlement' => 2), array('id' => $iid));
                            } else {
                                pdo_update(PDO_NAME . 'rush_order', array('issettlement' => 2), array('id' => $iid));
                            }
                        }
                    }
                    wl_message('已结算给商家！', web_url('finace/finaceWithdrawalApply/cashApply', array('status' => 5)), 'success');
                } else {
                    wl_message('结算金额输入错误！', referer(), 'error');
                }
                wl_message('操作成功！', referer(), 'success');
            }
        }
        include wl_template('finace/account');
    }

    public function output() {
        global $_W, $_GPC;

        $where['id'] = $_GPC['id'];
        $settlementRecord = Util::getSingelData('*', PDO_NAME . 'settlement_record', $where);
        $orders = unserialize($settlementRecord['ids']);
        $list = array();
        if ($settlementRecord['type'] == 1) {
            foreach ($orders as $id) {
                if ($settlementRecord['type2'] == 1) {
                    $v = Util::getSingelData('*', PDO_NAME . 'order', array('id' => $id));
                    $coupon = pdo_get('wlmerchant_couponlist', array('id' => $v['fkid']), array('title', 'logo'));
                    $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $v['sid']), array('storename'));
                    $member = pdo_get('wlmerchant_member', array('id' => $v['mid']), array('nickname', 'avatar', 'mobile'));
                    $v['title'] = $coupon['title'];
                    $v['gimg'] = tomedia($coupon['logo']);
                    $v['storename'] = $merchant['storename'];
                    $v['nickname'] = $member['nickname'];
                    $v['headimg'] = $member['avatar'];
                    $v['mobile'] = $member['mobile'];
                    $v['actualprice'] = $v['price'];
                    $v['gname'] = $v['title'];
                    $list[] = $v;
                } else {
                    $list[] = Rush::getSingleOrder($id, "*");
                }
            }
        }
        if ($settlementRecord['type'] == 2) {
            foreach ($orders as $id) {
                $order = Util::getSingelData("*", PDO_NAME . 'vip_record', array('id' => $id));
                $member = Member::wl_member_get($order['mid']);
                $order['nickname'] = $member['nickname'];
                $order['actualprice'] = $order['price'];
                $order['mobile'] = $member['mobile'];
                $order['gname'] = 'VIP充值';
                $list[] = $order;
            }
        }
        if ($settlementRecord['type'] == 3) {
            foreach ($orders as $id) {
                $order = Util::getSingelData("*", PDO_NAME . 'halfcard_record', array('id' => $id));
                $member = Member::wl_member_get($order['mid']);
                $order['nickname'] = $member['nickname'];
                $order['actualprice'] = $order['price'];
                $order['mobile'] = $member['mobile'];
                $order['gname'] = '一卡通充值';
                $list[] = $order;
            }
        }
        $orders = $list;
        if ($settlementRecord['status'] == 1)
            $settleStatus = '代理审核中';
        if ($settlementRecord['status'] == 2)
            $settleStatus = '系统审核中';
        if ($settlementRecord['status'] == 3)
            $settleStatus = '系统审核通过，待结算';
        if ($settlementRecord['status'] == 4)
            $settleStatus = '已结算到代理';
        if ($settlementRecord['status'] == 5)
            $settleStatus = '已结算到商家';
        if ($settlementRecord['status'] == -1)
            $settleStatus = '系统审核不通过';
        if ($settlementRecord['status'] == -2)
            $settleStatus = '代理审核不通过';
        $html = "\xEF\xBB\xBF";
        $filter = array('aa' => '商户单号', 'bb' => '昵称', 'cc' => '电话', 'dd' => '支付金额', 'ee' => '订单状态', 'jj' => '结算状态', 'ff' => '支付时间', 'gg' => '商品名称', 'hh' => '微信订单号');
        foreach ($filter as $key => $title) {
            $html .= $title . "\t,";
        }
        $html .= "\n";
        foreach ($orders as $k => $v) {
            if ($v['status'] == '0')
                $thisstatus = '未支付';
            if ($v['status'] == '1')
                $thisstatus = '已支付';
            if ($v['status'] == '2')
                $thisstatus = '已消费';
            $time = date('Y-m-d H:i:s', $v['paytime']);
            $orders[$k]['aa'] = $v['orderno'];
            $orders[$k]['bb'] = $v['nickname'];
            $orders[$k]['cc'] = $v['mobile'];
            $orders[$k]['dd'] = $v['actualprice'];
            $orders[$k]['ee'] = $thisstatus;
            $orders[$k]['jj'] = $settleStatus;
            $orders[$k]['ff'] = $time;
            $orders[$k]['gg'] = $v['gname'];
            $orders[$k]['hh'] = $v['transid'];
            foreach ($filter as $key => $title) {
                $html .= $orders[$k][$key] . "\t,";
            }
            $html .= "\n";
        }
        header("Content-type:text/csv");
        header("Content-Disposition:attachment; filename=未结算订单.csv");
        echo $html;
        exit();

    }




}
