<?php
defined('IN_IA') or exit('Access Denied');

class Bargain {
    //保存商品活动
    static function saveActive($active, $param = array()) {
        global $_W;
        if (!is_array($active)) return FALSE;
        $active['uniacid'] = $_W['uniacid'];
        $active['createtime'] = time();
        if (empty($param)) {
            pdo_insert(PDO_NAME . 'bargain_activity', $active);
            return pdo_insertid();
        }
        return FALSE;
    }

    //更新商品活动
    static function updateActive($params, $where) {
        $res = pdo_update(PDO_NAME . 'bargain_activity', $params, $where);
        if ($where['id']) Cache::deleteCache('active', $where['id']);
        if ($res) {
            return 1;
        } else {
            return 0;
        }
    }

    //获取商品活动
    static function getSingleActive($id, $select, $where = array()) {
        $where['id'] = $id;
        $goodsInfo = Util::getSingelData($select, PDO_NAME . 'bargain_activity', $where);
        if (empty($goodsInfo)) {
            return array();
        } else {
            return $goodsInfo;
        }
    }

    //获取活动列表
    static function getNumActive($select, $where, $order, $pindex, $psize, $ifpage) {
        $activeInfo = Util::getNumData($select, PDO_NAME . 'bargain_activity', $where, $order, $pindex, $psize, $ifpage);
        return $activeInfo;
    }

    //创建userlist记录
    static function createuserlist($mid, $activityid) {
        global $_W;
        if (empty($mid) || empty($activityid)) {
            return FALSE;
        }
        $goods = pdo_get(PDO_NAME . 'bargain_activity', array('id' => $activityid), array('oldprice', 'sid'));
        $data = array(
            'uniacid'    => $_W['uniacid'],
            'aid'        => $_W['aid'],
            'activityid' => $activityid,
            'merchantid' => $goods['sid'],
            'mid'        => $mid,
            'status'     => 1,
            'price'      => $goods['oldprice'],
            'createtime' => time(),
            'updatetime' => time(),
        );
        pdo_insert(PDO_NAME . 'bargain_userlist', $data);
        $res = pdo_insertid();
        return $res;
    }

    //砍价
    static function bargaining($mid, $activityid, $userid) {
        global $_W;
        $activity = self::getSingleActive($activityid, '*');
        $userlist = pdo_get('wlmerchant_bargain_userlist', array('id' => $userid));
        //判断资格  先略
        $helpflag = pdo_getcolumn('wlmerchant_bargain_helprecord', array('uniacid' => $_W['uniacid'], 'userid' => $userid, 'mid' => $_W['mid']), 'id');
        if ($helpflag) {
            wl_json(1, '您已砍过价了');
        }

        //获取砍价金额
        if ($activity['vipstatus'] == 1) {  //判断vip
            $now = time();
            if ($_W['wlsetting']['halfcard']['halfcardtype'] == 2) {
                $halfcardflag = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_halfcardmember') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$userlist['mid']} AND aid = {$_W['aid']} AND expiretime > {$now} AND disable != 1");
            } else {
                $halfcardflag = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_halfcardmember') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$userlist['mid']} AND expiretime > {$now} AND disable != 1");
            }
            if ($halfcardflag) {
                $lowprice = $activity['vipprice'];
            }
        }
        $lowprice = $lowprice ? $lowprice : $activity['price'];

        if ($userlist['price'] <= $lowprice) {
            wl_json(1, '已砍至底价，无法继续砍价');
        }

        $price = self::getBargainPrice($activity, $userlist['price'], $lowprice);
        if ($price) {
            $afterprice = sprintf("%.2f", $userlist['price'] - $price);
            $data = array(
                'uniacid'      => $_W['uniacid'],
                'aid'          => $_W['aid'],
                'activityid'   => $activityid,
                'authorid'     => $userlist['mid'],
                'mid'          => $mid,
                'userid'       => $userid,
                'bargainprice' => $price,
                'afterprice'   => $afterprice,
                'createtime'   => time(),
            );
            $res = pdo_insert(PDO_NAME . 'bargain_helprecord', $data);
            $barid = pdo_insertid();
            if ($res) {
                $res2 = pdo_update('wlmerchant_bargain_userlist', array('price' => $afterprice, 'updatetime' => time()), array('id' => $userid));
            }
            if ($res2) {
                return $barid;
            } else {
                return false;
            }
        }
    }

    //获取砍价金额
    public function getBargainPrice($activity, $userNowPrice, $lowprice) {
        if ($userNowPrice <= $lowprice) {
            return 0;
        }
        $rules = unserialize($activity['rules']);
        $price = 0;
        $inRule = false;
        foreach ($rules as $rule) {
            if ($userNowPrice >= $rule['rule_pice']) {
                $price = rand($rule['rule_start'] * 100, $rule['rule_end'] * 100) / 100;
                $inRule = true;
                break;
            }
        }
        if (!$inRule) {
            $price = rand(0.5 * 100, 1 * 100) / 100;
        }
        if ($userNowPrice - $price < $lowprice) {
            $price = $userNowPrice - $lowprice;
        }
        $price = sprintf("%.2f", $price);
        return $price;
    }

    //核销订单流程
    static function hexiaoorder($id, $mid, $num = 1, $type = 1,$checkcode='') {  //1输码 2扫码 3后台 4密码
        global $_W;
        $order = pdo_get('wlmerchant_order', array('id' => $id));
        if($order['neworderflag']){
            if($checkcode){
                $smallorders = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_smallorder')."WHERE plugin = 'bargain' AND  orderid = {$id} AND status = 1 AND checkcode = '{$checkcode}'");
            }else{
                $smallorders = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_smallorder')."WHERE plugin = 'bargain' AND  orderid = {$id} AND status = 1 ORDER BY id ASC LIMIT {$num}");
            }
            if($smallorders){
                if($mid){
                    $uid = pdo_getcolumn(PDO_NAME.'merchantuser',array('storeid'=>$order['sid'],'mid'=>$mid),'id');
                }else{
                    $uid = 0;
                }
                foreach ($smallorders as $k => $small){
                    $res = Order::finishSmallorder($small['id'],$uid,$type);
                }
            }else{
                if (is_mobile()) {
                    die(json_encode(array('errno' => 1, 'message' => '无可用核销码','data'=>'')));
                } else {
                    show_json(0, '无可用核销码');
                }
            }
        }else {
            $record = pdo_get('wlmerchant_bargain_userlist', array('id' => $order['specid']));
            //添加更新
            $arr = array();
            if ($record['usedtime']) {
                $a = unserialize($record['usedtime']);
                for ($i = 0; $i < $num; $i++) {
                    $arr['time'] = time();
                    $arr['type'] = $type;
                    $arr['ver'] = $mid;
                    $a[] = $arr;
                }
                $record['usedtime'] = serialize($a);
            } else {
                $a = array();
                for ($i = 0; $i < $num; $i++) {
                    $arr['time'] = time();
                    $arr['type'] = $type;
                    $arr['ver'] = $mid;
                    $a[] = $arr;
                }
                $record['usedtime'] = serialize($a);
            }
            $params['usetimes'] = $record['usetimes'] - $num;
            $params['usedtime'] = $record['usedtime'];
            if ($params['usetimes'] < 1) {
                pdo_update('wlmerchant_order', array('status' => 2), array('id' => $order['id']));
                //添加结算抢购订单到计划任务
                $ordertask = array(
                    'type'    => 'bargain',
                    'orderid' => $order['id']
                );
                $ordertask = serialize($ordertask);
                Queue::addTask(2, $ordertask, time(), $order['id']);
                if ($order['disorderid']) {
                    $res = pdo_update('wlmerchant_disorder', array('status' => 1), array('id' => $order['disorderid'], 'status' => 0));
                    if ($res) {
                        $distask = array(
                            'type'    => 'bargain',
                            'orderid' => $order['disorderid']
                        );
                        $distask = serialize($distask);
                        Queue::addTask(3, $distask, time(), $order['disorderid']);
                    }
                }
            }
            $res = pdo_update('wlmerchant_bargain_userlist', $params, array('id' => $record['id']));
        }
        if ($res) {
            $active = pdo_get('wlmerchant_bargain_activity', array('id' => $order['fkid']), array('name'));
            $order['checkcode'] = pdo_getcolumn(PDO_NAME . 'bargain_userlist', array('id' => $order['specid']), 'qrcode');
            SingleMerchant::verifRecordAdd($order['aid'], $order['sid'], $order['mid'], 'bargain', $order['id'], $order['checkcode'], $active['name'], $type);
            $member = pdo_get('wlmerchant_member', array('id' => $order['mid']), array('openid'));
            //发送核销成功通知
            $info = array(
                'first'      => '您好，您的商品已经成功核销' ,
                'goods_name' => $active['name'],//商品名称
                'goods_num'  => $num,//商品数量
                'time'       => date('Y-m-d H:i:s',time()),//核销时间
                'order_no'   => $order['orderno'],//订单编号
                'remark'     => '如有疑问请联系客服'
            );
            TempModel::sendInit('write_off',$order['mid'],$info,$_W['source']);
            if ($type == 2) {
                $info2 = array(
                    'first'      => '核销操作成功' ,
                    'goods_name' => $active['name'],//商品名称
                    'goods_num'  => $num,//商品数量
                    'time'       => date('Y-m-d H:i:s',time()),//核销时间
                    'order_no'   => $order['orderno'],//订单编号
                    'remark'     => '订单编号:['.$order['orderno'].']',
                );
                TempModel::sendInit('write_off',$_W['mid'],$info2,$_W['source']);
            }
            return 1;
        } else {
            return 0;
        }
    }


    /**
     * 异步支付结果回调 ，处理业务逻辑
     *
     * @access public
     * @name
     * @param mixed  参数一的说明
     * @return array
     */
    static function paybargainOrderNotify($params) {
        global $_W;
        Util::wl_log('payResult_notify', PATH_PLUGIN . "bargain/data/", $params); //写入异步日志记录
        $order_out = pdo_fetch("select * from" . tablename(PDO_NAME . 'order') . "where orderno='{$params['tid']}'");
        $_W['aid'] = $order_out['aid'];
        $_W['uniacid'] = $order_out['uniacid'];
        $_W['wlsetting']['creditset'] = Setting::wlsetting_read('creditset');
        $activeInfo = self::getSingleActive($order_out['fkid'], '*');
        $data = self::getbargainOrderPayData($params, $order_out); //得到支付参数，处理代付
        pdo_update(PDO_NAME . 'order', $data, array('orderno' => $params['tid'])); //更新订单状态
        if ($order_out) {
            if ($order_out['expressid']) {
                $data['status'] = 8;
            }else{
                //创建记录
                Order::createSmallorder($order_out['id'],5);
                //计算过期时间
                if ($activeInfo['cutoffstatus']) {
                    $data['estimatetime'] = time() + $activeInfo['cutoffday'] * 86400;
                } else {
                    $data['estimatetime'] = $activeInfo['cutofftime'];
                }
                //计算通知时间
                $data['remindtime'] = Order::remindTime($data['estimatetime']);
            }
            $record = array(
                'status' => 2,
                'updatetime' => time(),
                'expressid'  => $order_out['expressid']
            );
            pdo_update(PDO_NAME . 'bargain_userlist', $record, array('id' => $order_out['specid']));
            //抽奖领取
            if($order_out['drawid'] > 0){
                pdo_update('wlmerchant_draw_record',array('is_get' => 2),array('id' => $order_out['drawid']));
            }
            //处理分销
            if($order_out['cerditmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                $nodis = 1;
            }else{
                $nodis = 0;
            }
            if (p('distribution') && empty($activeInfo['isdistri']) && empty($order_out['drawid']) && empty($nodis) ) {
                $disarray = unserialize($activeInfo['disarray']);
                $disorderid = Distribution::disCore($order_out['mid'], $order_out['goodsprice'], $disarray, 1, 0, $order_out['id'], 'bargain', $activeInfo['dissettime'],$activeInfo['isdistristatus']);
                $data['disorderid'] = $disorderid;
            }
            //支付有礼
            if($activeInfo['paidid'] > 0){
                $data['paidprid'] = Paidpromotion::getpaidpr(9,$activeInfo['paidid'],$order_out['mid'],$order_out['id'],$data['paytype'],$order_out['price'],$order_out['num']);
            }
            //处理业务员佣金
            if(p('salesman')){
                $data['salesarray'] = Salesman::saleCore($order_out['sid'],'bargain');
            }
            pdo_update(PDO_NAME . 'order', $data, array('orderno' => $params['tid'])); //更新订单状态
            //卡密商品
            if($activeInfo['usestatus'] == 3){
                $rushtask = array(
                    'plugin'  => 'bargain',
                    'orderid' => $order_out['id']
                );
                $rushtask = serialize($rushtask);
                Queue::addTask(10, $rushtask, time(), $order_out['id']);
            }
            //添加用户标签
            $userlable = unserialize($activeInfo['userlabel']);
            if(!empty($userlable)){
                Member::addUserlable($userlable,$order_out['mid']);
            }
            //通知商户
            News::addSysNotice($order_out['uniacid'],2,$order_out['sid'],0,$order_out['id']);
            Store::addFans($order_out['sid'], $order_out['mid']);
            News::paySuccess($order_out['id'],'bargain');
            //小票打印
            Order::sendPrinting($order_out['id'],'bargain');
        }
    }

    static function getbargainOrderPayData($params, $order_out) {
        $data = array('status' => $params['result'] == 'success' ? 1 : 0);
        if ($params['is_usecard'] == 1) {
            $fee = $params['card_fee'];
            $data['is_usecard'] = 1;
        } else {
            $fee = $params['fee'];
        }
        //$paytype = array('credit' => 1, 'wechat' => 2, 'alipay' => 3, 'delivery' => 4, 'wxapp' => 5);
        $data['paytype'] = $params['type'];
        if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
        $data['paytime'] = TIMESTAMP;
        $data['price'] = $fee;
        $data['blendcredit'] = $params['blendcredit'];
        return $data;
    }

    static function paybargainOrderReturn($params, $backurl = false) {
        Util::wl_log('payResult_return', PATH_PLUGIN . "bargain/data/", $params);//写入日志记录
        $order_out = pdo_get(PDO_NAME . 'order', array('orderno' => $params['tid']), array('id'));
        wl_message('购买成功',h5_url('pages/mainPages/paySuccess/paySuccess',['id'=>$order_out['id'],'type'=>8]), 'success');
    }

    static function refund($id, $money = 0, $unline = '') {
        $order = pdo_get(PDO_NAME . 'order', array('id' => $id));
        if($money < $order['blendcredit']){
            $blendcredit = $money;
            $money = 0;
        }else if($order['blendcredit'] > 0){
            $blendcredit = $order['blendcredit'];
            $money = sprintf("%.2f",$money - $blendcredit);
        }
        if ($unline) {
            $res['status'] = 1;
        } else {
            $res = wlPay::refundMoney($id, $money, '砍价订单退款', 'bargain', 2,$blendcredit);
        }
        if ($res['status']) {
            pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'bargain','orderid'=>$id,'status'=> array(1,4)));
            if ($order['applyrefund']) {
                pdo_update('wlmerchant_order', array('status' => 7, 'refundtime' => time(), 'applyrefund' => 2), array('id' => $order['id']));
                $reason = '买家申请退款。';
            } else {
                pdo_update('wlmerchant_order', array('status' => 7, 'refundtime' => time()), array('id' => $order['id']));
                $reason = '砍价系统退款。';
            }
            if($order['redpackid']){
                pdo_update('wlmerchant_redpack_records',array('status' => 0,'usetime' =>0,'orderid' => 0,'plugin' =>''),array('id' => $order['redpackid']));
            }
            //分销订单退款
            if ($order['disorderid']) {
                Distribution::refunddis($order['disorderid']);
            }
            News::refundNotice($id,'bargain',$money,$reason);
        } else {
            pdo_fetch("update" . tablename('wlmerchant_order') . "SET failtimes = failtimes+1 WHERE id = {$id}");
        }
        return $res;
    }

    //取消订单
    static function cancelorder($id){
        global $_W;
        $order = pdo_get('wlmerchant_order',array('id' => $id),array('specid','mid','uniacid','usecredit','fkid','redpackid'));
        $_W['uniacid'] = $order['uniacid'];
        $res1 = pdo_update('wlmerchant_bargain_userlist',array('orderid' => 0),array('id' => $order['specid']));
        if($res1){
            $res = pdo_update('wlmerchant_order',array('status' => 5),array('id' => $id));
            if($order['redpackid']){
                pdo_update('wlmerchant_redpack_records',['status' => 0],['id' => $order['redpackid']]);
            }
            if ($order['usecredit'] > 0) {
                $goodname = pdo_getcolumn(PDO_NAME . 'bargain_activity', array('id' => $order['fkid']), 'name');
                Member::credit_update_credit1($order['mid'], $order['usecredit'], '取消砍价商品:[' . $goodname . ']订单返还积分');
            }
        }else{
            $res = 0;
        }
        return $res;
    }


    static function doTask() {
        global $_W;
        //修改砍价活动状态
        $activitys1 = pdo_getall(PDO_NAME . "bargain_activity", array('starttime <' => time(), 'status' => 1), array('id'));
        if (!empty($activitys1)) {
            foreach ($activitys1 as $k => $v) {
                pdo_update(PDO_NAME . "bargain_activity", array('status' => 2), array('id' => $v['id']));
            }
        }

        $activitys2 = pdo_getall(PDO_NAME . "bargain_activity", array('endtime <' => time(), 'status' => 2), array('id'));
        if (!empty($activitys2)) {
            foreach ($activitys2 as $k => $v2) {
                pdo_update(PDO_NAME . "bargain_activity", array('status' => 4), array('id' => $v2['id']));
                $bargainuser = pdo_getall('wlmerchant_bargain_userlist', array('activityid' => $v2['id'], 'status' => 1), array('id'));
                if (!empty($bargainuser)) {
                    foreach ($bargainuser as $k => $user) {
                        pdo_update(PDO_NAME . "bargain_userlist", array('status' => 3), array('id' => $user['id']));
                    }
                }
            }
        }
        //自动过期订单
        $nowtime = time();
        $overorders = pdo_fetchall("SELECT id,fkid,uniacid,aid FROM " . tablename('wlmerchant_order') . "WHERE status = 1 AND estimatetime < {$nowtime} AND estimatetime > 0 AND plugin = 'bargain' ORDER BY id DESC LIMIT 10");
        if ($overorders) {
            foreach ($overorders as $key => $over) {
                pdo_update('wlmerchant_order', array('status' => 9, 'overtime' => time()), array('id'=>$over['id']));
                //自动退款
                $_W['uniacid'] = $over['uniacid'];
                $_W['aid'] = $over['aid'];
                $orderset = Setting::wlsetting_read('orderset');
                $goods = pdo_get('wlmerchant_bargain_activity', array('id' => $over['fkid']), array('overrefund'));
                if ($orderset['reovertime'] && $goods['overrefund'] ) {
                    self::refund($over['id'],0,0);
                }
            }
        }

    }

}

?>