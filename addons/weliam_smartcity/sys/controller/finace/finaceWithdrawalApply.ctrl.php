<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 提现申请
 * Author: zzw
 * Date: 2021/1/7 18:18
 * Class FinaceWithdrawalApply_WeliamController
 */
class FinaceWithdrawalApply_WeliamController {
    //提现申请
    public function cashApply(){
        global $_W, $_GPC;
        $cashsets = Setting::wlsetting_read('cashset');
        //提现申请
        if ($_GPC['type'] == 'submit' && !empty($_GPC['id'])) {
            $trade_no = time().random(4, true);
            pdo_update(PDO_NAME . 'settlement_record', array('status' => 3, 'updatetime' => TIMESTAMP, 'trade_no' => $trade_no), array('id' => $_GPC['id']));
            //模板消息
            $record = pdo_get(PDO_NAME.'settlement_record',array('id'=> $_GPC['id']),array('id','type','payment_type','uniacid','sapplymoney','sopenid','sid','mid'));
            if($record['type'] == 3){
                $url = h5_url('pages/subPages/dealer/withdraw/withdrawrecord',['draw_id'=>$_GPC['id']]);
                Distribution::distriNotice($record['mid'],$url,4,0,$record['sapplymoney']);
            }else{
                if($record['type'] == 1){
                    $merchant = pdo_get('wlmerchant_merchantdata' , ['id' => $record['sid']] , ['autocash']);
                    if ($cashsets['autocash'] && $record['payment_type'] != 3 && $merchant['autocash']) {
                        Queue::addTask(4 , $record['id'] , time() , $record['id']);
                    }
                    $record['mid'] = pdo_getcolumn(PDO_NAME . 'merchantuser', array('storeid' => $record['sid'],'ismain' => 1), 'mid');
                }else if($record['type'] == 2){
                    if ($cashsets['agentautocash'] && $record['payment_type'] != 3) {
                        Queue::addTask(4 , $record['id'] , time() , $record['id']);
                    }
                    $record['mid'] = pdo_getcolumn(PDO_NAME . 'member', array('openid' => $record['sopenid']), 'id');
                }else if($record['type'] == 5){
                    $set        = Setting::wlsetting_read('dating_set');
                    if($set['automatic_payment'] == 1){
                        //开启自动打款  调用计划任务进行自动打款
                        Queue::addTask(4, $record['id'] , time(), $record['id']);
                    }
                }
                $first = '您的提现申请已通过审核';
                $type = '提现申请';
                $status = '已通过审核';
                $content = '申请金额:￥'.$record['sapplymoney'];
                if($record['sid']){
                    $remark = '系统管理员会尽快打款,点击查看申请记录';
                    $url = '';
                }else{
                    $remark = '系统管理员会尽快打款';
                    $url = '';
                }
                News::jobNotice($record['mid'],$first,$type,$content,$status,$remark,time(),$url);
                if($record['type'] == 1){
                    News::addSysNotice($record['uniacid'],3,$record['sid'],0,$_GPC['id'],1);
                }
            }
            show_json(1,'提交成功');
        } else if ($_GPC['type'] == 'reject' && !empty($_GPC['id'])) {
            //返回提现金额给提现申请人
            $record = pdo_get(PDO_NAME . 'settlement_record', array('id' => $_GPC['id']), array('uniacid','sopenid','type', 'sid', 'aid', 'sapplymoney', 'id','mid'));
            if ($record['type'] == 1) {
                $res = Store::settlement($record['id'], 0, $record['sid'], $record['sapplymoney'], 0, $record['sapplymoney'],7,0,0,$record['aid']);
                $status = -1;
            } else if ($record['type'] == 2) {
                $res = Store::settlement($record['id'], 0, 0, $record['sapplymoney'], 0, 0,7,0,0,$record['aid']);
                $status = -1;
            }else if($record['type'] == 3){
                $nowmoney = pdo_getcolumn(PDO_NAME."distributor",array('mid'=>$record['mid']),'nowmoney');
                $totalNowMonet = $nowmoney+$record['sapplymoney'];
                $res = pdo_update(PDO_NAME."distributor",array('nowmoney'=>$totalNowMonet),array('mid'=>$record['mid']));
                if(Customized::init('distributionText') > 0){
                    $Cremark = '共享股东佣金提现申请驳回';
                }else{
                    $Cremark = '分销佣金提现申请驳回';
                }
                Distribution::adddisdetail($record['id'],$record['mid'],$record['mid'], 1, $record['sapplymoney'], 'cash', 1, $Cremark, $totalNowMonet);
                $status = 11;
            } else if ($record['type'] == 4) {
                $res =  Member::credit_update_credit2($record['mid'],$record['sapplymoney'],'用户提现被驳回',$record['id']);
                $status = -1;
            }else if($record['type'] == 5){
                $commission = pdo_getcolumn(PDO_NAME."dating_matchmaker",['mid' => $record['mid'],'uniacid' => $_W['uniacid']],'commission');
                $totalCommission = sprintf("%.2f",$commission + $record['sapplymoney']);
                pdo_update(PDO_NAME."dating_matchmaker",['commission' => $totalCommission],['mid' => $record['mid']]);
                Dating::commissionChangeRecord($record['mid'],$record['sapplymoney'],'提现申请被驳回',1);
                $status = 17;
            }
            pdo_update(PDO_NAME . 'settlement_record', array('status' => $status, 'updatetime' => TIMESTAMP), array('id' => $_GPC['id']));
            //模板消息
            if($record['type'] == 3){
                $url = h5_url('pages/subPages/dealer/withdraw/withdrawrecord',['draw_id'=>$_GPC['id']]);
                Distribution::distriNotice($record['mid'],$url,5,0,$record['sapplymoney']);
            }else {
                $first = '您的提现申请已被驳回';
                $type = '提现申请';
                $status = '申请被驳回';
                $content = '申请金额:￥' . $record['sapplymoney'];
                if ($record['sid']) {
                    $remark = '您可以重新提交申请,点击查看申请记录';
                } else {
                    $remark = '您可以在后台重新提交申请';
                }
                News::jobNotice($record['mid'], $first, $type, $content, $status, $remark, time());
                if ($record['type'] == 1) {
                    News::addSysNotice($record['uniacid'], 3, $record['sid'], 0, $_GPC['id'], 2);
                }
            }
            show_json(1,'驳回成功');
        } else {
            $pindex = max(1, intval($_GPC['page']));
            $psize = 10;
            $where = array('uniacid' => $_W['uniacid']);
            if (!empty($_GPC['status'])) {
                switch ($_GPC['status']){
                    case 1:break;//不限
                    case 2:$where['status#'] = "(2,6,7,15)";break;//待审核
                    case 3:$where['status#'] = "(3,8,16)";break;//待打款
                    case 4:$where['status#'] = "(4,5,9,18)";break;//已完成
                    case 5:$where['status#'] = "(-1,10,11,17)";break;//未通过
                }
            }
            if (!empty($_GPC['type'])) {
                $where['type'] = intval($_GPC['type']);
            }
            if(!empty($_GPC['orderid'])){
                $where['id'] = intval($_GPC['orderid']);
            }
            if($_GPC['type'] == 3 && !empty($_GPC['disid'])){
                $where['disid'] = intval($_GPC['disid']);
            }

            //时间
            if (!empty($_GPC['time']) && $_GPC['timetype'] > 0){
                $starttime = strtotime($_GPC['time']['start']);
                $endtime = strtotime($_GPC['time']['end']);
                $where['applytime>'] = $starttime;
                $where['applytime<'] = $endtime+86400;
            }

            if (empty($starttime) || empty($endtime)) {
                $starttime = strtotime('-1 month');
                $endtime = time();
            }
            //条件筛选
            if(!empty(trim($_GPC['keyword']))){
                $keyword = trim($_GPC['keyword']);
                if($_GPC['type'] == 1){
                    if($_GPC['keywordtype'] == 1){
                        $params[':nickname'] = "%{$keyword}%";
                        $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_merchantdata') . "WHERE uniacid = {$_W['uniacid']} AND storename LIKE :nickname", $params);
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
                            $where['sid#'] = $sids;
                        } else {
                            $where['sid#'] = "(0)";
                        }
                    }else{
                        $where['sid'] = $keyword;
                    }
                }else if($_GPC['type'] == 2){
                    if($_GPC['keywordtype'] == 1){
                        $params[':nickname'] = "%{$keyword}%";
                        $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_agentusers') . "WHERE uniacid = {$_W['uniacid']} AND agentname LIKE :nickname", $params);
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
                            $where['aid#'] = $sids;
                        } else {
                            $where['aid#'] = "(0)";
                        }
                    }else{
                        $where['aid'] = $keyword;
                    }
                }else if($_GPC['type'] > 2){

                    if($_GPC['keywordtype'] == 1){
                        $params[':nickname'] = "%{$keyword}%";
                        $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname", $params);
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
                            $where['mid#'] = $sids;
                        } else {
                            $where['mid#'] = "(0)";
                        }
                    }else if($_GPC['keywordtype'] == 2){
                        $params[':nickname'] = "%{$keyword}%";
                        $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND realname LIKE :nickname", $params);
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
                            $where['mid#'] = $sids;
                        } else {
                            $where['mid#'] = "(0)";
                        }
                    }else{
                        $where['mid'] = $keyword;
                    }
                }
            }

            //如果是导出记录则查询所有信息
            if($_GPC['export']){
                $listdata = Util::getNumData('*',PDO_NAME . 'settlement_record', $where,'ID DESC',$pindex, $psize, 0);
            }else{
                //$list = pdo_getslice(PDO_NAME . 'settlement_record', $where, array($pindex, $psize), $total, array(), '', 'id DESC');
                $listdata = Util::getNumData('*',PDO_NAME . 'settlement_record', $where,'ID DESC',$pindex, $psize, 1);
            }
            $list = $listdata[0];
            $pager = $listdata[1];
            foreach ($list as $key => &$relue) {
                $relue['spercent'] = sprintf("%.2f", $relue['spercent']);
                if($relue['mid'] > 0){
                    $relue['realname'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$relue['mid']),'realname');
                }else{
                    $relue['realname'] = '';
                }
                //获取提现人基本信息
                if ($relue['type'] == 1) {
                    //获取商户收款账号信息
                    $accountInfo = Store::getShopOwnerInfo($relue['sid'],$relue['aid']);
                    $relue['name'] = Util::idSwitch('sid', 'sName', $relue['sid']);
                    $relue['currurl'] = web_url('finace/newCash/currentlist',array('type'=>'store','objid'=>$relue['sid']));
                    //获取店长实名
                    $memberid = pdo_getcolumn(PDO_NAME.'merchantuser',array('storeid'=>$relue['sid'],'ismain'=>1),'mid');
                    $relue['realname'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$memberid),'realname');
                } else if ($relue['type'] == 2) {
                    //获取代理商收款账号信息
                    $accountInfo = pdo_get(PDO_NAME."agentusers",array('id'=>$relue['aid'],'uniacid'=>$_W['uniacid']),array('alipay','bank_name','card_number','bank_username'));
                    $relue['name'] = Util::idSwitch('aid', 'aName', $relue['aid']);
                    $relue['currurl'] = web_url('finace/newCash/currentlist',array('type'=>'agent','objid'=>$relue['aid']));
                }else if($relue['type'] == 3){
                    //获取分销商收款账号信息
                    $accountInfo = pdo_get(PDO_NAME."member",array('id'=>$relue['mid'],'uniacid'=>$_W['uniacid']),array('alipay','bank_name','card_number','nickname','bank_username'));
                    $relue['name'] = $accountInfo['nickname'];
                    $relue['currurl'] = web_url('distribution/dissysbase/disdetail',array('keywordtype'=>'1','keyword'=>$relue['mid']));
                }else if($relue['type'] == 4){
                    //获取用户提现收款账号信息
                    $accountInfo = pdo_get(PDO_NAME."member",array('id'=>$relue['mid'],'uniacid'=>$_W['uniacid']),array('alipay','bank_name','card_number','nickname','bank_username'));
                    $relue['name'] = $accountInfo['nickname'];
                    $relue['currurl'] = web_url('member/memberFinancialDetails/balance',array('keywordtype'=>'1','keyword'=>$relue['mid']));
                }else if($relue['type'] == 5){
                    //获取用户提现收款账号信息
                    $accountInfo = pdo_get(PDO_NAME."member",['id'=> $relue['mid'],'uniacid' => $_W['uniacid']],['alipay','bank_name','card_number','nickname','bank_username']);
                    $relue['name'] = $accountInfo['nickname'];
                    $relue['currurl'] = 'javascript:;';
                }
                //获取商家的支付宝 银行卡信息
                if($relue['payment_type'] == 1 || $relue['payment_type'] == 3 || $relue['payment_type'] == 5) {
                    if($accountInfo){
                        $relue['alipay'] = $accountInfo['alipay'];
                        $relue['bank_name'] = $accountInfo['bank_name'];
                        $relue['card_number'] = $accountInfo['card_number'];
                        $relue['bank_username'] = $accountInfo['bank_username'];
                    }
                }
                //如果是导出操作 需要进行数据的重新组装
                if($_GPC['export']){
                    $data[$key]['name'] = $relue['name'];//提现人信息
                    $data[$key]['realname'] = $relue['realname'];//提现人实名
                    $data[$key]['sapplymoney']   = $relue['sapplymoney'];//申请提现金额
                    $data[$key]['spercentmoney'] = $relue['spercentmoney'];//手续费
                    $data[$key]['sgetmoney']     = $relue['sgetmoney'];//实际到账金额
                    $data[$key]['applytime']     = date("Y-m-d H:i:s",$relue['applytime']);//申请时间
                    $data[$key]['updatetime']    = $relue['updatetime']?date("Y-m-d H:i:s",$relue['updatetime']):'';//操作时间

                    //获取打款状态
                    switch ($relue['status']){
                        case 1:$statueRes = '审核中';break;
                        case 2:case 6:case 7:$statueRes = '待审核';break;
                        case 3:case 8:$statueRes = '待打款';break;
                        case 4:case 5:case 9:$statueRes = '提现成功';break;
                        case -1:case 10:case 11:$statueRes = '驳回申请';break;
                    }
                    $data[$key]['status'] = $statueRes;
                    //获取提现类型
                    switch ($relue['type']){
                        case 1:$typeRes = '商家提现';break;
                        case 2:$typeRes = '代理提现';break;
                        case 3:
                            if(Customized::init('distributionText') > 0){
                                $typeRes = '共享股东提现';
                            }else{
                                $typeRes = '分销提现';
                            }
                            break;
                    }
                    $data[$key]['type'] = $typeRes;//提现类型
                    //获取提现方式
                    switch ($relue['payment_type']){
                        case 1:$paymentTypeRes = '支付宝';break;
                        case 2:$paymentTypeRes = '微信';break;
                        case 3:$paymentTypeRes = '银行卡';break;
                        case 4:$paymentTypeRes = '余额';break;
                        case 5:$paymentTypeRes = '任意';break;
                        default:$paymentTypeRes = '微信';break;
                    }
                    $data[$key]['payment_type'] = $paymentTypeRes;//提现类型
                    //获取到账类型
                    switch ($relue['settletype']){
                        case 1:case 3:$settleTypeRes = '手动处理';break;
                        case 2:$settleTypeRes = '微信零钱';break;
                        case 4:$settleTypeRes = '余额到账';break;
                        case 5:$settleTypeRes = '微信红包';break;
                        case 6:$settleTypeRes = '支付宝打款';break;
                        default:$settleTypeRes = '未打款';break;
                    }
                    $data[$key]['settletype'] = $settleTypeRes;//到账类型
                    $data[$key]['alipay']        = $relue['alipay'];
                    $data[$key]['bank_name']     = $relue['bank_name'];
                    $data[$key]['card_number']   = "\t".$relue['card_number']."\t";
                    $data[$key]['bank_username'] = $relue['bank_username'];
                }
            }
            //进行导出操作
            if($_GPC['export']){
                //设置表格的标题信息
                $titleInfo = array(
                    'name'          => '提现人信息',
                    'realname'      => '实名',
                    'sapplymoney'   => '申请提现金额',
                    'spercentmoney' => '手续费',
                    'sgetmoney'     => '实际到账金额',
                    'applytime'     => '申请时间',
                    'updatetime'    => '操作时间',
                    'status'        => '打款状态',
                    'type'          => '提现类型',
                    'payment_type'  => '提现方式',
                    'settletype'    => '到账类型',
                    'alipay'        => '支付宝账号',
                    'bank_name'     => '开户行名字',
                    'card_number'   => '银行卡号',
                    'bank_username' => '开户人姓名'
                );
                //开始导出.cvs文件
                util_csv::export_csv_2($data, $titleInfo, '提现申请信息.csv');
                exit();
            }
        }

        include  wl_template('finace/cashConfirm');
    }
    //导出记录
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
                    $member = Member::wl_member_get($v['mid'], array('nickname', 'avatar', 'mobile'));
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
        $str = '未结算订单_' . time();
        header("Content-type:text/csv");
        header("Content-Disposition:attachment; filename={$str}.csv");
        echo $html;
        exit();

    }
    /**
     * Comment: 打款操作
     * Author: zzw
     * Date: 2019/10/9 17:42
     */
    public function settlementing(){
        global $_W, $_GPC;
        #1、获取基本参数
        $id   = $_GPC['id'] OR show_json(0,'id错误，请刷新重试！');
        $type = $_GPC['type'] OR wl_message(0,'打款类型错误，请刷新重试！');//1=手动处理；2=微信打款；3=分销商提现(手动处理)；4=分销商提现（余额到账）；5=红包打款；6=支付宝转账
        $phone = $_GPC['phone'];//支付宝提现手机号信息
        $info = pdo_get(PDO_NAME . "settlement_record" , [ 'id' => $id ]
            , [ 'type' , 'status' ,'uniacid','sgetmoney' , 'spercent' , 'sapplymoney','spercentmoney','sopenid','sid','aid','mid','trade_no','source']);
        #2、先判断是否已结算
        if($info['status'] != 3 && $info['status'] != 8 && $info['status'] != 16) show_json(0,'该申请未审核或已打款');
        #2、结算金额计算，判断提现申请金额是否合法
        $sgetmoney     = sprintf("%.2f" , $info['sgetmoney']);//实际结算给商户
        $spercent      = sprintf("%.2f" , $info['spercent']); //佣金百分比
        $spercentmoney = sprintf("%.2f" , $info['spercentmoney']);//佣金
        if($sgetmoney <= 0 || !is_numeric($sgetmoney)) show_json(0,'结算金额错误');
        #3、生成基本数据信息  1=商家提现申请;2=代理提现申请;3=分销商申请提现;4=用户余额提现
        $data = [
            'updatetime'    => TIMESTAMP ,//最后操作时间
            'sgetmoney'     => $sgetmoney ,//实际得到金额
            'spercent'      => $spercent ,//系统抽成比例
            'spercentmoney' => $spercentmoney ,//系统抽成金额
        ];
        switch ($info['type']){
            case 1:
                //$userName = pdo_getcolumn(PDO_NAME."merchantdata",['id'=>$info['sid']],'storename');
                $rem =  '商家提现(sid:'.$info['sid'].')';
                $data['status'] = 5;
                $info['mid'] = pdo_getcolumn(PDO_NAME . 'merchantuser', array('storeid' => $info['sid'],'ismain' => 1), 'mid');
                break;//商家提现申请
            case 2:
                //$userName = pdo_getcolumn(PDO_NAME."agentusers",['id'=>$info['aid']],'agentname');
                $rem = '代理商提现(aid:'.$info['aid'].')';
                $data['status'] = 4;
                $info['mid'] = pdo_getcolumn(PDO_NAME . 'member', array('openid' => $info['sopenid']), 'id');
                break;//代理提现申请
            case 3:
                $trade = Setting::wlsetting_read('trade');
                $fxstext = $trade['fxstext'] ? : '分销商';
                //$userName = pdo_getcolumn(PDO_NAME."distributor",['mid'=>$info['mid']],'nickname');
                if(Customized::init('distributionText') > 0){
                    $rem = '共享股东提现:mid'.$info['mid'];
                }else{
                    $rem = $fxstext.'提现:mid'.$info['mid'];
                }
                $data['status'] = 9;
                break;//分销商申请提现
            case 4:
                //$userName = pdo_getcolumn(PDO_NAME."member",['id'=>$info['mid']],'nickname');
                $rem = '用户提现:mid'.$info['mid'];
                $data['status'] = 5;
                break;//用户余额提现
            case 5:
                //$userName = pdo_getcolumn(PDO_NAME."member",['id'=>$info['mid']],'nickname');
                $rem = '红娘提现:mid'.$info['mid'];
                $data['status'] = 18;
                break;//红娘提现
        }
        #3、根据结算类型进行打款结算 微信提现/手动处理/微信红包
        switch ($type){
            case 1:case 3:
            $data['settletype'] = 1;
            $res = 1;//手动处理直接成功
            $cashtype = '手动处理';
            break;//手动处理
            case 2:
                if ($sgetmoney < 1) show_json(0,'实际到账金额需要大于1元');
                $cashtype = '微信零钱';
                if (empty($info['sopenid'])) {
                    if($info['type'] == 2){
                        $info['sopenid'] = pdo_getcolumn(PDO_NAME . 'agentusers', array('id' => $info['aid']), 'cashopenid');
                    }else if($info['type'] == 1){
                        $mid = pdo_getcolumn(PDO_NAME . 'merchantuser', array('storeid' => $info['sid'],'ismain' => 1), 'mid');
                        if($info['source'] == 3){
                            $info['sopenid'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $mid), 'wechat_openid');
                            if (empty($info['sopenid'])){
                                $info['sopenid'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $mid), 'openid');
                            }
                        }else{
                            $info['sopenid'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $mid), 'openid');
                            if (empty($info['sopenid'])){
                                $info['sopenid'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $mid), 'wechat_openid');
                            }
                        }
                    }else if($info['type'] == 3){
                        if($info['source'] == 3){
                            $info['sopenid'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $info['mid']), 'wechat_openid');
                            if (empty($info['sopenid'])){
                                $info['sopenid'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $info['mid']), 'openid');
                            }
                        }else{
                            $info['sopenid'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $info['mid']), 'openid');
                            if (empty($info['sopenid'])){
                                $info['sopenid'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $info['mid']), 'wechat_openid');
                            }
                        }
                    }else if($info['type'] == 4){
                        if($info['source'] == 3){
                            $info['sopenid'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $info['mid']), 'wechat_openid');
                            if (empty($info['sopenid'])){
                                $info['sopenid'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $info['mid']), 'openid');
                            }
                        }else{
                            $info['sopenid'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $info['mid']), 'openid');
                            if (empty($info['sopenid'])){
                                $info['sopenid'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $info['mid']), 'wechat_openid');
                            }
                        }
                    }
                    if (empty($info['sopenid'])) {
                        show_json(0,'该用户未绑定提现微信号');
                    }
                }
                $data['settletype'] = 2;
                //请求进行微信打款操作
                $params = [
                    'openid'   => $info['sopenid'] ,
                    'money'    => $sgetmoney ,
                    'rem'      => $rem ,
                    'name'     => 'weliam' ,
                    'order_no' => $info['trade_no'],
                    'source'   => $info['source'] ? : 1,
                    'mid'      => $info['mid']
                ];
                $res = Payment::presentationInit($params,1);
                if(!$res) $tips = '微信打款失败，请保证微信商户号余额充足并且开通企业付款功能';
                break;//微信打款
            case 4:
                //打款到余额 仅分销商可以申请打款到余额
                $data['settletype'] = 4;
                $cashtype = '余额到账';
                $res = Member::credit_update_credit2($info['mid'],$info['sgetmoney'],$rem);
                break;//余额到账
            case 5:
                if ($sgetmoney < 1) show_json(0,'实际到账金额需要大于1元');
                $data['settletype'] = 5;
                //请求进行微信打款操作
                $params = [
                    'openid'   => $info['sopenid'] ,
                    'money'    => $sgetmoney ,
                    'rem'      => $rem ,
                    'name'     => 'weliam' ,
                    'order_no' => $info['trade_no'],
                    'source'   => $info['source'] ? : 1,
                    'mid'      => $info['mid']
                ];
                $res = Payment::presentationInit($params,2);
                if(!$res) $tips = '红包打款失败，请保证微信商户号余额充足';
                $cashtype = '微信红包';
                break;//红包打款
            case 6:
                if ($sgetmoney < 1) show_json(0,'实际到账金额需要大于1元');
                if (empty($phone)) show_json(0,'未获取支付宝账号信息');
                $data['settletype'] = 6;
                $realname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$info['mid']),'realname');
                //请求进行微信打款操作
                $params = [
                    'money'    => $sgetmoney ,
                    'order_no' => $info['trade_no'],
                    'phone'    => $phone,
                    'source'   => $info['source'] ? : 1,
                    'mid'      => $info['mid'],
                    'realname' => $realname
                ];
                $res = Payment::presentationInit($params,3);
                if(!$res) $tips = '支付宝打款失败，请保证微信账户余额充足并且开通对应功能';
                $cashtype = '支付宝转账';
                break;//支付宝转账
        }
        #3、修改提现信息
        if($res){
            pdo_update(PDO_NAME . 'settlement_record' , $data , [ 'id' => $_GPC['id'] ]);
            //发送模板消息
            if($info['type'] == 3){
                $url = h5_url('pages/subPages/dealer/withdraw/withdrawrecord',['draw_id'=>$_GPC['id']]);
                Distribution::distriNotice($info['mid'],$url,6,0,$info['sapplymoney'],$cashtype);
            }else{
                $first = '您的提现申请打款';
                $type = '提现申请';
                $status = '已打款';
                $content = '到账金额:￥'.$info['sgetmoney'];
                $remark = '谢谢您对平台的支持';
                News::jobNotice($info['mid'],$first,$type,$content,$status,$remark,time());
                if($info['type'] == 1){
                    News::addSysNotice($info['uniacid'],3,$info['sid'],0,$_GPC['id'],1);
                }
            }

            show_json(1,'已结算给'.str_replace('提现:',':',$rem));
        }else{
            $tips = $tips ? : '结算失败，请刷新重试!';
            show_json(0,$tips);
        }
    }
}
