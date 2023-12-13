<?php
defined('IN_IA') or exit('Access Denied');

class Activity{
	
	static function createrecord($orderid){
		global $_W;
		$order = pdo_get('wlmerchant_order',array('id' => $orderid));
		$random = Util::createConcode(1);
		$data = array(
			'uniacid' 	=> $_W['uniacid'],
			'aid' 		=> $_W['aid'],
			'status' 	=> 1,
			'sid'    	=> $order['sid'],
			'mid'       => $order['mid'],
			'activityid'=> $order['fkid'],
			'checkcode' => $random,
			'usetimes'  => $order['num'],
			'orderid'   => $orderid,
			'createtime'=> time()
		);
		$res = pdo_insert(PDO_NAME.'activity_record',$data);
		$recordid = pdo_insertid();
		return $recordid;
	}

    /**
     * 修改活动浏览量
     * @param number $id 黄页id
     * @return bool
     */
    static function changepv($id,$minup,$maxup) {
        global $_W;
        if($minup > 0 && $maxup > 0){
            $up = rand($minup,$maxup);
        }else{
            $up = 1;
        }
        pdo_query('UPDATE ' . tablename(PDO_NAME . 'activitylist') . " SET `pv` = `pv` + {$up} WHERE id = {$id}");
    }
	
	
	
	
	//报名成功模板消息
	static function SuccessNotice($orderid){
	    global $_W;
		$order = pdo_get('wlmerchant_order',array('id' => $orderid));
		$activity = pdo_get('wlmerchant_activitylist',array('id' => $order['fkid']),array('title'));
		$merchantName = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$order['sid']),'storename');
		$buyer = pdo_get(PDO_NAME.'member',array('id'=>$order['mid']),array('nickname','openid'));
		$nickname = $buyer['nickname'];
		$buyopenid = $buyer['openid'];
		//发送给商家
		$storeadmin = pdo_getcolumn(PDO_NAME.'merchantuser',array('storeid'=>$order['sid'],'ismain'=> 1),'mid');
		$storeadminopenid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$storeadmin),'openid');
		$first = '您好,用户['.$nickname.']报名的活动['.$activity['title'].']已支付';
		$keyword1 = '商户活动';
		$keyword2 = '已报名成功';
		$remark = '订单金额:'.$order['price'].'元,报名人数:'.$order['num'].',请商家注意准备';
		$url = app_url('store/supervise/switchstore', array('storeid' => $order['sid'],'url' => urlencode(app_url('store/supervise/order',array('status'=> 1)))));
		Message::jobNotice($storeadminopenid,$first,$keyword1,$keyword2,$remark,$url);
		//发送给管理员
		$openids = pdo_getall('wlmerchant_agentadmin',array('aid' => $order['aid'],'notice'=> 1),array('openid'));
		$url = app_url('activity/activity_app/activitydetail',array('id'=>$order['fkid']));
		$remark = '所属商家:'.$merchantName;
		if($openids){
			foreach ($openids as $key => $member){
				Message::jobNotice($member['openid'],$first,$keyword1,$keyword2,$remark,$url);
			}
		}
		//发送给买家
		$first = '亲爱的用户['.$nickname.']您好,您报名['.$activity['title'].']活动已成功';
		$remark = '请在规定时间前往['.$merchantName.']参加活动';
		$url = h5_url('pages/subPages/orderList/orderList');
		Message::jobNotice($buyopenid,$first,$keyword1,$keyword2,$remark,$url);
	}

	static function payActivityshargeNotify($params){
		global $_W;
		Util::wl_log('payResult_notify',PATH_PLUGIN."activity/data/",$params); //写入异步日志记录
		$order_out = pdo_get('wlmerchant_order',array('orderno' => $params['tid']));
        $activity = pdo_get('wlmerchant_activitylist',array('id' => $order_out['fkid']));
        $_W['uniacid'] = $order_out['uniacid'];
        $_W['wlsetting']['creditset'] = Setting::wlsetting_read('creditset');
        $data = self::getOrderPayData($params, $order_out); //得到支付参数，处理代付
		if($order_out['status'] == 0 || $order_out['status'] == 5){
		    //生成核销码
            Order::createSmallorder($order_out['id'] , 6);
            //计算过期时间
            $data['estimatetime'] = $activity['activeendtime'];
            $data['remindtime'] = Order::remindTime($activity['activestarttime']);
			//处理分销
			if($order_out['cerditmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                $nodis = 1;
            }else{
                $nodis = 0;
            }
            if (p('distribution') && empty($activity['isdistri']) && empty($order_out['drawid']) && empty($nodis)) {
                if ($order_out['specid']) {
                    $option = pdo_get('wlmerchant_activity_spec', array('id' => $order_out['specid']), array('disarray'));
                    $activity['disarray'] = WeliamWeChat::mergeDisArray($option['disarray'],$activity['disarray']);
                }
                $disarray = unserialize($activity['disarray']);
                $dismoney = sprintf("%.2f",$order_out['goodsprice'] - $order_out['vipdiscount']);
                $disorderid = Distribution::disCore($order_out['mid'], $dismoney, $disarray, $order_out['num'], 0, $order_out['id'], 'activity', $activity['dissettime'],$activity['isdistristatus']);
                $data['disorderid'] = $disorderid;
            }
			pdo_update(PDO_NAME.'order',$data, array('orderno' => $params['tid'])); //更新订单状态
            Store::addFans($activity['sid'] , $_W['mid']);
            News::addSysNotice($order_out['uniacid'],2,$order_out['sid'],0,$order_out['id']);
            News::paySuccess($order_out['id'], 'activity');
            //打印推送
            Order::sendPrinting($order_out['id'],'activity');
        }
	}

	static function payActivityshargeReturn($params){
		wl_message('报名成功',h5_url('pages/subPages/orderList/orderList'),'success');
	}

    static function getOrderPayData($params, $order_out) {
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
        $data['createtime'] = TIMESTAMP;
        $data['blendcredit'] = $params['blendcredit'];
        return $data;
    }

	static function hexiaoorder($id,$mid,$num=1,$type=1,$checkcode=''){  //1输码 2扫码 3后台 4密码
        global $_W;
        $order = pdo_get('wlmerchant_order', array('id' => $id));
        $cutofftime = $order['estimatetime'];
        if ($cutofftime < time() && $type != 3) {
            if (is_mobile()) {
                die(json_encode(array("errno" => 1, 'message' => '已超过截止日期，无法核销','data'=>'')));
            } else {
                show_json(0, '已超过截止日期，无法核销');
            }
        }
        if ($order['status'] != 1 && $type != 3) {
            if (is_mobile()) {
                die(json_encode(array('errno' => 1, 'message' => '订单已核销','data'=>'')));
            } else {
                show_json(0, '订单已核销');
            }
        }
        if($checkcode){
            $smallorders = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_smallorder')."WHERE plugin = 'activity' AND  orderid = {$id} AND status = 1 AND checkcode = '{$checkcode}'");
        }else{
            $smallorders = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_smallorder')."WHERE plugin = 'activity' AND  orderid = {$id} AND status = 1 ORDER BY id ASC LIMIT {$num}");
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
                die(json_encode(array('errno' => 1, 'message' => '无可用核销码')));
            } else {
                show_json(0, '无可用核销码');
            }
        }
        if ($res) {
            $active = pdo_get('wlmerchant_activitylist', array('id' => $order['fkid']), array('title'));
            //发送核销成功通知
            $info = array(
                'first'      => '您好，您的商品已经成功核销' ,
                'goods_name' => $active['title'],//商品名称
                'goods_num'  => $num,//商品数量
                'time'       => date('Y-m-d H:i:s',time()),//核销时间
                'order_no'   => $order['orderno'],//订单编号
                'remark'     => '如有疑问请联系客服'
            );
            TempModel::sendInit('write_off',$order['mid'],$info,$_W['source']);
            if ($type == 2) {
                $info2 = array(
                    'first'      => '核销操作成功' ,
                    'goods_name' => $active['title'],//商品名称
                    'goods_num'  => $num,//商品数量
                    'time'       => date('Y-m-d H:i:s',time()),//核销时间
                    'order_no'   => $order['orderno'],//订单编号
                    'remark'     => '订单编号:['.$order['orderno'].']',
                );
                TempModel::sendInit('write_off',$_W['mid'],$info2,$_W['source']);
            }
            SingleMerchant::verifRecordAdd($order['aid'], $order['sid'], $order['mid'], 'activity', $order['id'], $order['checkcode'], $active['title'], $type, $num);
            return 1;
        } else {
            return 0;
        }


		
	}
	
	static function refundorder($id, $money = 0, $unline = '',$checkcode = '',$afterid = 0){
		global $_W,$_GPC;
		$order = pdo_get('wlmerchant_order',array('id' => $id));
		if($order['status'] == 7){
            $res['status'] = 0;
            $res['message'] = '订单已退款';
            return $res;
		}
        if($checkcode){
            if($money<0.01){
                $smallorder = pdo_fetch("SELECT orderprice,blendcredit FROM ".tablename(PDO_NAME . "smallorder")." WHERE plugin = 'activity' AND orderid = {$id} AND status IN (1,4) AND checkcode = '{$checkcode}'");
                $money = sprintf("%.2f",$smallorder['orderprice'] - $smallorder['blendcredit']);
                $blendcredit = $smallorder['blendcredit'];
            }
            $refundnum = 1;
        }else if(empty($money)){
            $money =  pdo_fetchcolumn('SELECT SUM(orderprice) FROM ' . tablename(PDO_NAME . "smallorder") . " WHERE plugin = 'activity' AND orderid = {$id} AND status IN (1,4)");
            $blendcredit = pdo_fetchcolumn('SELECT SUM(blendcredit) FROM ' . tablename(PDO_NAME . "smallorder") . " WHERE plugin = 'activity' AND orderid = {$id} AND status IN (1,4)");
            $money = sprintf("%.2f",$money - $blendcredit);
            $refundnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME . "smallorder")." WHERE plugin = 'activity' AND orderid = {$id} AND status IN (1,4)");
        }else{
            if($money < $order['blendcredit']){
                $blendcredit = $money;
                $money = 0;
            }else if($order['blendcredit'] > 0){
                $blendcredit = $order['blendcredit'];
                $money = sprintf("%.2f",$money - $blendcredit);
            }
            $refundnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME . "smallorder")." WHERE plugin = 'activity' AND orderid = {$id} AND status IN (1,4)");
        }
        if ($unline) {
            $res['status'] = 1;
        } else {
            $res = wlPay::refundMoney($id, $money, '活动报名订单退款', 'activity', 2,$blendcredit);
        }
        if ($res['status']) {
            if($checkcode){
                pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'activity','orderid'=>$id,'status'=> array(1,4),'checkcode'=>$checkcode));
            }else if(empty($afterid)){
                pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'activity','orderid'=>$id,'status'=> array(1,4)));
            }else if($afterid > 0){
                $afterCheckcode = pdo_getcolumn(PDO_NAME.'aftersale',array('id'=>$afterid),'checkcodes');
                $afterCheckcode = unserialize($afterCheckcode);
                pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'activity','orderid'=>$id,'checkcode' => $afterCheckcode));
            }
            if ($order['applyrefund']) {
                $reason = '买家申请退款。';
                $orderdata['applyrefund'] = 2;
            } else {
                $reason = '抢购系统退款。';
            }
            $overflag = pdo_get('wlmerchant_smallorder',array('orderid' => $id,'plugin'=>'activity','status'=>1),array('id'));
            if(empty($overflag)){
                $hexiao = pdo_get('wlmerchant_smallorder',array('orderid' => $id,'plugin'=>'activity','status'=>2),array('id'));
                if($hexiao){
                    $orderdata['status'] = 2;
                    $orderdata['issettlement'] = 1;
                    $orderdata['settletime'] = time();
                }else{
                    $orderdata['status'] = 7;
                    $orderdata['refundtime'] = time();
                }
                pdo_update('wlmerchant_order',$orderdata, array('id' => $order['id']));
            }
            $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['orderid'=>$id,'plugin'=>'activity']);
            if ($order['disorderid']) {
                Distribution::refunddis($order['disorderid'],$checkcode);
            }
            News::refundNotice($id,'activity',$money,$reason);
            //退回适用积分
            if ($order['dkcredit']) {
                $refundcredit = sprintf("%.2f",$order['dkcredit']/$order['num']*$refundnum);
                $goodname = pdo_getcolumn(PDO_NAME . 'activitylist', array('id' => $order['activityid']), 'title');
                Member::credit_update_credit1($order['mid'], $refundcredit, '退款活动报名:[' . $goodname . ']订单返还积分');
            }
            if($order['redpackid'] && $orderdata['status'] == 7){
                pdo_update('wlmerchant_redpack_records',array('status' => 0,'usetime' =>0,'orderid' => 0,'plugin' =>''),array('id' => $order['redpackid']));
            }
        }else{
            pdo_fetch("update" . tablename('wlmerchant_order') . "SET failtimes = failtimes+1 WHERE id = {$id}");
        }
		return $res;
	}
	
	static function doTask(){
		global $_W,$_GPC;
		$now = time();
		//修改未开始到报名中
        pdo_update('wlmerchant_activitylist',array('status' => 2),array('status' => 1,'enrollstarttime <'=>$now));
		//修改已截止报名的活动状态
		$activity2 = pdo_fetchall("SELECT id,minpeoplenum FROM ".tablename('wlmerchant_activitylist')."WHERE status = 2 AND enrollendtime < {$now}");
		if($activity2){
			foreach ($activity2 as $key => $ac) {
				pdo_update('wlmerchant_activitylist',array('status' => 3),array('id' => $ac['id']));
                $ac['trueenrollnum'] = WeliamWeChat::getSalesNum(6,$ac['id'],0,2);
				if($ac['minpeoplenum'] > 0 && $ac['trueenrollnum'] < $ac['minpeoplenum']){
					//退款所有报名订单
					$refundorders = pdo_getall('wlmerchant_order',array('fkid' => $ac['id'],'plugin' => 'activity','status' => 1),array('price','id'));
					if($refundorders){
						foreach ($refundorders as $key => &$re) {
							if($re['price'] > 0){
								pdo_update('wlmerchant_order',array('status' => 6),array('id' => $re['id']));
							}else {
								pdo_update('wlmerchant_order',array('status' => 5),array('id' => $re['id']));
							}
						}
					}
				}
			}
		}
        //过期流程
        $where = Queue::getTaskWhere(0);
        $actorder3 = pdo_fetchall("SELECT id,fkid,uniacid,aid FROM " . tablename('wlmerchant_order') . "WHERE plugin = 'activity' {$where} AND status = 1 AND estimatetime < {$nowtime} AND estimatetime > 0 ORDER BY id DESC LIMIT 10");
        if (!empty($actorder3)) {
            foreach ($actorder3 as $key => $actor3) {
                pdo_update('wlmerchant_order', array('status' => 9, 'overtime' => time()), array('id' => $actor3['id']));
                //自动退款
                $_W['uniacid'] = $actor3['uniacid'];
                $_W['aid'] = $actor3['aid'];
                $orderset = Setting::wlsetting_read('orderset');
                if ($orderset['reovertime']){
                    pdo_update('wlmerchant_order', array('status' => 6), array('id' => $actor3['id']));
                    self::refundorder($actor3['id']);
                }
            }
        }

	}
	
}
?>