<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 代理商提现管理
 * Author: zzw
 * Date: 2021/1/7 18:06
 * Class FinaceWithdrawal_WeliamController
 */
class FinaceWithdrawal_WeliamController {
    //余额提现
    public function cashApplyAgent() {
        global $_W, $_GPC;
        $a = Util::getSingelData('percent,cashopenid,allmoney,nowmoney,payment_type,alipay,card_number', PDO_NAME . 'agentusers', array('id' => $_W['aid']));
        //提现账号
        if(!empty($a['cashopenid'])){
            if(intval($a['cashopenid']) > 0){
                $user = pdo_get(PDO_NAME.'member', ['id' => $a['cashopenid']], ['avatar', 'id', 'realname', 'nickname','openid','wechat_openid']);
            }else{
                $user = pdo_get(PDO_NAME.'member', ['openid' => $a['cashopenid']], ['avatar', 'id', 'realname', 'nickname','openid','wechat_openid']);
            }
        }else{
            $user = [];
        }
        //提现申请
        $p = unserialize($a['percent']);
        $cashsets = Setting::wlsetting_read('cashset');
        $agentpercent = $p['agentpercent'] ? $p['agentpercent'] : $cashsets['agentpercent'];
        $cashsets['lowsetmoney'] = $cashsets['lowsetmoney'] ? $cashsets['lowsetmoney'] : 0;
        $money = $num = 0;

        /*提现申请*/
        if ($_GPC['money'] > 0) {
            $money = $_GPC['money'];
            $num = $_GPC['num'];
            if ($num < $cashsets['lowsetmoney']) {
                Commons::sRenderError('申请金额小于最低结算金额！');
            } else if ($num > $a['nowmoney']) {
                Commons::sRenderError('申请金额大于账户现有金额！');
            } else {
                $data = array(
                    'uniacid'       => $_W['uniacid'] ,
                    'sid'           => 0 , 'aid' => $_W['aid'] ,
                    'status'        => 2 , 'type' => 2 ,
                    'sapplymoney'   => $num ,
                    'sgetmoney'     => sprintf("%.2f" , $num - $agentpercent * $num / 100) ,
                    'spercentmoney' => sprintf("%.2f" , $agentpercent * $num / 100) ,
                    'spercent'      => $agentpercent ,
                    'applytime'     => TIMESTAMP ,
                    'updatetime'    => TIMESTAMP ,
                    'sopenid'       => $user['openid']?$user['openid']:$user['wechat_openid'],
                    'source'        => $user['openid']?1:3
                );
                if ($a['payment_type'] == 1) {
                    //支付宝
                    if ($a['alipay']) {
                        $data['payment_type'] = 1;
                    } else {
                        Commons::sRenderError('请填写支付宝账号信息！');
                    }
                } else if ($a['payment_type'] == 2) {
                    //微信
                    $data['payment_type'] = 2;
                    if(empty($user['openid']) && empty($user['wechat_openid'])){
                        Commons::sRenderError('打款账号无微信账户信息，无法提现');
                    }
                } else if ($a['payment_type'] == 3) {
                    //银行卡
                    if ($a['card_number']) {
                        $data['payment_type'] = 3;
                    } else {
                        Commons::sRenderError('请填写银行卡账号信息！');
                    }
                } else {
                    Commons::sRenderError('请选择收款方式！');
                }
                if ($cashsets['noaudit']) {
                    $data['status'] = 3;
                    $trade_no = time() . random(4, true);
                    $data['trade_no'] = $trade_no;
                    $data['updatetime'] = time();
                }
                if (pdo_insert(PDO_NAME . 'settlement_record', $data)) {
                    $settid = pdo_insertid();
                    $res = Store::settlement($settid, 0, 0, -$num, 0, 0, 7);
                    if ($cashsets['agentautocash']) {
                        Queue::addTask(4, $settid, time(), $settid);
                    }
                    if ($res) {
                        if (!empty($_W['wlsetting']['adminmid'])) {
                            $storename = pdo_getcolumn(PDO_NAME . 'agentusers', array('id' => $_W['aid']), 'agentname');
                            $first = '您好，有一个代理提现申请待审核。';
                            $type = '代理[' . $storename . ']申请提现';
                            $status = '待审核';
                            $remark = '请尽快前往系统后台审核';
                            $content = '申请金额:￥' . $num;
                            News::jobNotice($_W['wlsetting']['adminmid'], $first, $type, $content, $status, $remark,$data['applytime']);
                        }
                        Commons::sRenderSuccess('申请成功！');
                    } else {
                        Commons::sRenderError('申请失败！');
                    }
                }
            }
            Commons::sRenderError('申请失败！');
        }
        if ($_GPC['num']) {
            $num = $_GPC['num'];
            if ($num > $a['nowmoney']) {
                Commons::sRenderError('申请金额大于账户现有金额！');
            }
            $percentMoney = sprintf("%.2f", $agentpercent * $num / 100);
            $money = sprintf("%.2f", $num - $agentpercent * $num / 100);

            Commons::sRenderSuccess('申请成功！',['num' => $num, 'percentMoney' => $percentMoney, 'money' => $money]);
        }
        include wl_template('finace/agentApply');
    }
    //代理商提现账户设置
    public function account() {
        global $_W, $_GPC;
        $a = Util::getSingelData('cashopenid,bank_username,payment_type,bank_name,card_number,alipay', PDO_NAME . 'agentusers', array('id' => $_W['aid']));
        //提现账号
        if ($a['cashopenid']) {
            $user = Member::wl_member_get($a['cashopenid']);
        }
        if (checksubmit('submit')) {
            $cashmid = $_GPC['openid'];
            $realname = $_GPC['realname'];
            $data = $_GPC['data'];
            $data['cashopenid'] = $cashmid;
            pdo_update(PDO_NAME . 'agentusers', $data, array('id' => $_W['aid']));
            $upwhere = empty(intval($cashmid)) ? array('openid' => $cashmid) : array('id' => $cashmid);
            pdo_update(PDO_NAME . 'member', array('realname' => $realname), $upwhere);
            wl_message('保存成功！', referer(), 'success');
        }
        //获取平台开通的打款方式
        $cashset = Setting::wlsetting_read('cashset')['payment_type'];

        include wl_template('finace/useraccount');
    }
    //代理商提现记录
    public function cashApplyAgentRecord() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $where = array('uniacid' => $_W['uniacid'], 'type' => array(1, 2), 'aid' => $_W['aid']);
        if ($_GPC['orderid']) {
            $where['id'] = $_GPC['orderid'];
        }

        $list = pdo_getslice(PDO_NAME . 'settlement_record', $where, array($pindex, $psize), $total, array(), '', 'id DESC');
        foreach ($list as $key => &$value) {
            if ($value['type'] == 1) {
                $value['aName'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $value['sid']), 'storename');
            } else {
                $value['aName'] = Util::idSwitch('aid', 'aName', $value['aid']);
                $value['spercent'] = sprintf("%.2f", $value['spercent']);
            }

        }
        $pager = wl_pagination($total, $pindex, $psize);

        include wl_template('finace/cashApplyAgentRecord');
    }

}
