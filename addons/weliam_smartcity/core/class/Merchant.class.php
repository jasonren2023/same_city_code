<?php
defined('IN_IA') or exit('Access Denied');

class Merchant {

   
    /**
     * 异步支付结果回调 ，处理业务逻辑
     *
     * @access public
     * @name
     * @param mixed  参数一的说明
     * @return array
     */
    static function payHalfcardNotify($params) {
        global $_W;
        Util::wl_log('vip_notify', PATH_DATA . "merchant/data/", $params); //写入异步日志记录
        $halfsettings = Setting::wlsetting_read('halfcard');

        $order_out = pdo_get(PDO_NAME . 'halfcard_record', array('orderno' => $params['tid']));
        $_W['aid'] = $order_out['aid'];
        $_W['uniacid'] = $order_out['uniacid'];
        $_W['wlsetting']['creditset'] = Setting::wlsetting_read('creditset');
        if ($order_out['status'] == 0) {
            $data = self::getVipPayData($params); //得到支付参数，处理代付
            $halftype = pdo_get('wlmerchant_halfcard_type', array('id' => $order_out['typeid']));
            //用户定制 激活码激活会员卡时赠送用户余额
            if (file_exists(IA_ROOT . '/addons/'.MODULE_NAME.'/pTLjC21GjCGj.log')) {
                if ($halftype['give_price'] > 0) {
                    Member::credit_update_credit2($order_out['mid'], $halftype['give_price'], '一卡通赠送金额');
                }
            }
            //电商联盟定制 会员卡同步到其他模块
            if (file_exists(PATH_MODULE . 'lsh.log')) {
                Halfcard::toHccardMode($order_out['mid'],$order_out['username'],$order_out['mobile'],$halftype['levelid']);
            }
            //处理分销
            if (p('distribution') && empty($halftype['isdistri'])) {
                $_W['aid'] = $order_out['aid'];
                $disorderid = Distribution::disCore($order_out['mid'], $order_out['price'], $halftype['onedismoney'], $halftype['twodismoney'], $halftype['threedismoney'], $order_out['id'], 'halfcard', 1);
                $data['disorderid'] = $disorderid;
            }
            //支付有礼
            if($halftype['paidid'] > 0){
                $data['paidprid'] = Paidpromotion::getpaidpr(5,$halftype['paidid'],$order_out['mid'],$order_out['id'],$data['paytype'],$order_out['price'],1,2);
            }
            if($halfsettings['settlement'] > 0){
                $data['issettlement'] = 1;
            }

            $res = pdo_update(PDO_NAME . 'halfcard_record', $data, array('orderno' => $params['tid'])); //更新订单状态
            if ($res && $halfsettings['settlement'] != 1) {
                Store::halfsettlement($order_out['id']);  //结算订单
            }
            $halfcarddata = array(
                'uniacid'     => $order_out['uniacid'],
                'aid'         => $order_out['aid'],
                'mid'         => $order_out['mid'],
                'expiretime'  => $order_out['limittime'],
                'username'    => $order_out['username'],
                'levelid'     => $halftype['levelid'],
                'createtime'  => time(),
             //   'mototype'    => $order_out['mototype'],
             //   'platenumber' => $order_out['platenumber'],
                'channel'      => 0
            );
            if ($order_out['cardid']) {
                pdo_update(PDO_NAME . 'halfcardmember', $halfcarddata, array('id' => $order_out['cardid']));
            } else {
                pdo_insert(PDO_NAME . 'halfcardmember', $halfcarddata);
            }
            $member = pdo_get('wlmerchant_member', array('id' => $halfcarddata['mid']), array('openid', 'mobile'));
            $openid = $member['openid'];
            $mobile = empty($member['mobile']) ? $order_out['mobile'] : $member['mobile'];
            if (empty($member['mobile']) || !empty($order_out['platenumber'])) {
                $memberdata['mobile'] = $order_out['mobile'];
                if(!empty($order_out['platenumber'])){
                    $memberdata['card_number'] = $order_out['platenumber'];
                }
                pdo_update('wlmerchant_member',$memberdata, array('id' => $halfcarddata['mid']));
            }
            $url = h5_url('pages/mainPages/memberCard/memberCard');
            $url = str_replace('payment/','',$url);
            $time = date('Y-m-d H:i:s', $halfcarddata['expiretime']);
            $halftext = $_W['wlsetting']['trade']['halfcardtext'] ? $_W['wlsetting']['trade']['halfcardtext'] : '一卡通';
            $tqtext = $_W['wlsetting']['trade']['privilege'] ? $_W['wlsetting']['trade']['privilege'] : '特权';
            //通知用户开卡成功
            $userModelData = [
                'first'   => '您已成功开通' .$halftext . $tqtext ,
                'type'    => '信息通知' ,//业务类型
                'content' => '开通账号：' . $mobile ,//业务内容
                'status'  => '开通商品：' . $halftype['name'] ,//处理结果
                'time'    => '到期时间：' . $time,//操作时间
                'remark'  => ''
            ];
            TempModel::sendInit('service',$halfcarddata['mid'],$userModelData,$_W['source'],$url);
            //通知管理员
            $first = '客户:[' . $halfcarddata['username'] . ']已成功开通' . $halftext . $tqtext;
            $type = '信息通知';
            $content = '开通账号：' . $mobile;
            $status =  '开通商品：' . $halftype['name'] ;
            $remark = '';
            News::noticeAgent('opencard',$order_out['aid'],$first,$type,$content,$status,$remark,time());
        }
        //成为分销商
        $base = Setting::wlsetting_read('distribution');
        if ($base['appdis'] == 2 && $base['switch'] && $base['together'] == 1) {
//			if(in_array($order_out['typeid'],$base['halfcardtype'])){
            //获取匹配的分销商等级
            $dislevel = $halftype['dislevelid'];
            if(empty($dislevel)){
                $dislevel = pdo_getcolumn(PDO_NAME.'wlmerchant_dislevel',array('uniacid'=>$_W['uniacid'],'isdefault'=> 1),'id');
            }
            $member = pdo_get('wlmerchant_member', array('id' => $order_out['mid']), array('mobile', 'nickname', 'realname', 'distributorid'));
            $distributor = pdo_get('wlmerchant_distributor', array('id' => $member['distributorid']));
            if (!empty($distributor)) {
                    pdo_update('wlmerchant_distributor', array('disflag' => 1, 'updatetime' => time(),'dislevel' => $dislevel), array('id' => $member['distributorid']));
            } else {
                $distributor = pdo_get('wlmerchant_distributor', array('mid' => $order_out['mid']));
                if(!empty($distributor)){
                    pdo_update('wlmerchant_distributor', array('disflag' => 1, 'updatetime' => time(),'dislevel' => $dislevel), array('id' => $distributor['id']));
                    $disid = $distributor['id'];
                }else{
                    $data = array(
                        'uniacid'    => $order_out['uniacid'],
                        'aid'        => $order_out['aid'],
                        'mid'        => $order_out['mid'],
                        'createtime' => time(),
                        'disflag'    => 1,
                        'nickname'   => $member['nickname'],
                        'mobile'     => $member['mobile'],
                        'realname'   => $member['realname'],
                        'leadid'     => 0,
                        'dislevel'   => $dislevel
                    );
                    pdo_insert('wlmerchant_distributor', $data);
                    $disid = pdo_insertid();
                }
                pdo_update('wlmerchant_member', array('distributorid' => $disid), array('id' => $order_out['mid']));
            }
//			}
        }
    }

    /**
     * 函数的含义说明
     *
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    static function payHalfcardReturn($params) {
        global $_W;
        Util::wl_log('Vip_return', PATH_DATA . "merchant/data/", $params);//写入日志记录
        $order_out = pdo_get(PDO_NAME . 'halfcard_record', array('orderno' => $params['tid']), array('id'));
        wl_message('支付成功',h5_url('pages/mainPages/paySuccess/paySuccess',['id'=>$order_out['id'],'type'=>5]), 'success');
    }


    static function payChargeNotify($params) {
        global $_W;
        Util::wl_log('payResult_notify', PATH_DATA . "merchant/data/", $params); //写入异步日志记录
        $order_out = pdo_get(PDO_NAME . 'order', array('orderno' => $params['tid']));
        $_W['aid'] = $order_out['aid'];
        $_W['uniacid'] = $order_out['uniacid'];
        if ($order_out['status'] == 0) {
            $data = self::getVipPayData($params); //得到支付参数，处理代付
            if ($data['status'] == 1) {
                $data['status'] = 3;
            }
            $chargetype = pdo_get('wlmerchant_chargelist', array('id' => $order_out['fkid']));
            //处理分销
            if (p('distribution') && empty($chargetype['isdistri'])) {
                $disorderid = Distribution::newDisCore($order_out['id'],'charge');
                $data['disorderid'] = $disorderid;
            }
            $res = pdo_update(PDO_NAME . 'order', $data, array('orderno' => $params['tid'])); //更新订单状态
            if ($res) {
                Store::ordersettlement($order_out['id']);
            }
            $merchant = pdo_get(PDO_NAME . 'merchantdata', array('id' => $order_out['sid']), array('id','storename','aid','endtime', 'status', 'enabled'));
            $endtime = $merchant['endtime'];
            $merstatus = $merchant['status'];
            if ($endtime > time()) {
                $newendtime = $order_out['num'] * 24 * 3600 + $endtime;
            } else {
                $newendtime = $order_out['num'] * 24 * 3600 + time();
            }
            //权限
            $audits = pdo_getcolumn(PDO_NAME . 'chargelist', array('id' => $order_out['fkid']), 'audits');
            //商户组
            $groupid = $order_out['fkid'];
            if (empty($groupid)) {
                $groupid = 0;
            }
            if ($merstatus == 2) {
                $merdata['endtime'] = $newendtime;
                $merdata['groupid'] = $groupid;
                if ($merchant['enabled'] == 3) {
                    $merdata['enabled'] = 1;
                }
                pdo_update(PDO_NAME . 'merchantdata', $merdata, array('id' => $order_out['sid'])); //更新商户
            } else {
                if ($audits) {
                    pdo_update(PDO_NAME . 'merchantdata', array('status' => 2, 'endtime' => $newendtime, 'enabled' => 1, 'audits' => 1, 'groupid' => $groupid), array('id' => $order_out['sid'])); //更新商户
                    pdo_update(PDO_NAME . 'merchantuser', array('status' => 2), array('storeid' => $order_out['sid'])); //更新管理员
                } else {
                    pdo_update(PDO_NAME . 'merchantdata', array('status' => 1, 'endtime' => $newendtime, 'groupid' => $groupid), array('id' => $order_out['sid'])); //更新商户
                    $appname = pdo_getcolumn(PDO_NAME . 'merchantuser', array('storeid' => $merchant['id'], 'ismain' => 1), 'name');
					$agentname = pdo_getcolumn(PDO_NAME . 'agentusers' , ['id' => $merchant['aid']] , 'agentname');
	                $first = '您好,'. $appname . '申请了新商家入驻';
	          		$type = '店铺申请入驻';
	                $content = '店铺名:['.$merchant['storename'] .']';
	                $status = '待审核';
	                $time = time();
	                $remark = '入驻代理:['.$agentname.'],请尽快前往系统后台审核商家资料';
                	News::noticeAgent('storesettled',$order_out['aid'],$first,$type,$content,$status,$remark,$time);
                }
            }
        }
    }

    static function payChargeReturn($params) {
        $order_out = pdo_get(PDO_NAME . 'order', array('orderno' => $params['tid']), array('id'));
        wl_message('支付成功',h5_url('pages/mainPages/paySuccess/paySuccess',['id'=>$order_out['id'],'type'=>6]) , 'success');
    }


    static function payPayonlineNotify($params) {
        global $_W;
        $order_out = pdo_get(PDO_NAME . 'order', array('orderno' => $params['tid']));
        $_W['uniacid'] = $order_out['uniacid'];
        $_W['aid'] = $order_out['aid'];
        $_W['wlsetting']['creditset'] = Setting::wlsetting_read('creditset');
        Util::wl_log('payResult_notify', PATH_DATA . "merchant/data/", $params); //写入异步日志记录
        if ($order_out['status'] == 0 || $order_out['status'] == 5) {
            $data = self::getVipPayData($params); //得到支付参数，处理代付
            if ($data['status'] == 1) {
                $data['status'] = 2;
            }
            $store = pdo_get(PDO_NAME . 'merchantdata', array('uniacid' => $order_out['uniacid'], 'id' => $order_out['sid']),array('payonlinelabel','storename','paypaidid','onepayonlinescale','twopayonlinescale','payonlinedisstatus','pay_drawid','pay_draw_money'));
            //分销
            if($order_out['cerditmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                $nodis = 1;
            }else{
                $nodis = 0;
            }
            if (p('distribution') && !empty($store['payonlinedisstatus']) && empty($nodis)) {
                $disorderid = Distribution::newDisCore($order_out['id'],'payonline');
                $data['disorderid'] = $disorderid;
            }
            //处理业务员佣金
            if(p('salesman') && $order_out['sid'] > 0){
                $data['salesarray'] = Salesman::saleCore($order_out['sid'],'payonline');
            }
            //支付有礼
            if($store['paypaidid'] > 0){
                $data['paidprid'] = Paidpromotion::getpaidpr(7,$store['paypaidid'],$order_out['mid'],$order_out['id'],$data['paytype'],$order_out['price'],1,2);
            }
            $res = pdo_update(PDO_NAME . 'order', $data, array('orderno' => $params['tid'])); //更新订单状态
            //结算在线买单
            if ($res) {
                Store::ordersettlement($order_out['id']);
                //返现
                Order::yueCityCashBack($order_out['mid'],$order_out['sid'],$order_out['price'],1);
                //抽奖码
                if($store['pay_drawid'] > 0 && $order_out['price'] > $store['pay_draw_money'] ){
                    Luckydraw::getDrawCode($store['pay_drawid'],$order_out['mid'],$order_out['id'],'payonline');
                }
            }

            $record = array(
                'uniacid'     => $order_out['uniacid'],
                'aid'         => $order_out['aid'],
                'mid'         => $order_out['mid'],
                'type'        => 1,
                'cardid'      => $order_out['card_id'],
                'activeid'    => $order_out['fkid'],
                'merchantid'  => $order_out['sid'],
                'freeflag'    => $order_out['card_type'],
                'ordermoney'  => $order_out['goodsprice'],
                'realmoney'   => $order_out['price'],
                'verfmid'     => $order_out['mid'],
                'usetime'     => time(),
                'createtime'  => time(),
                'commentflag' => 1,
                'discount'    => $order_out['spec'],
                'undismoney'  => $order_out['oprice'],
                'orderid'     => $order_out['id']
            );
            $flagtime = time() - 5;
            $flag = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_timecardrecord') . "WHERE cardid = {$order_out['card_id']} AND activeid = {$order_out['fkid']} AND type = 1 AND createtime > {$flagtime} ");
            if (empty($flag)) {
                pdo_insert(PDO_NAME . 'timecardrecord', $record);
            }

            if (empty($disorderid)) {
                $disorderid = 0;
            }

            //添加用户标签
            $userlable = unserialize($store['payonlinelabel']);
            if(!empty($userlable)){
                Member::addUserlable($userlable,$order_out['mid']);
            }
            //收藏店铺
            News::addSysNotice($order_out['uniacid'],2,$order_out['sid'],0,$order_out['id']);
            Store::addFans($order_out['sid'],$order_out['mid']);
            //发消息给买家
            $openid = pdo_getcolumn(PDO_NAME . 'member', array('uniacid' => $order_out['uniacid'], 'id' => $order_out['mid']), 'openid');
            $nickname = pdo_getcolumn(PDO_NAME . 'member', array('uniacid' => $order_out['uniacid'], 'id' => $order_out['mid']), 'nickname');
            $storename = $store['storename'];
            if ($order_out['fkid']) {
                $goodsname = pdo_getcolumn(PDO_NAME . 'halfcardlist', array('uniacid' => $order_out['uniacid'], 'id' => $order_out['fkid']), 'title');
            } else {
                $goodsname = $storename . '在线买单';
            }
            $payinfo = array(
                'first'      => '您的在线支付订单已经成功付款' ,
                'order_no'   => $order_out['orderno'],//订单编号
                'time'       => date('Y-m-d H:i:s', time()),//支付时间
                'money'      => $order_out['price'],//支付金额
                'goods_name' => $goodsname,//商品名称
                'remark'     => '点击可查看订单详情，如有疑问请联系客服'
            );
            $url = h5_url('pages/subPages/orderList/orderList',['status'=>1]);
            TempModel::sendInit('pay',$order_out['mid'],$payinfo,$_W['source'],$url);
            //发送给商家
            $admins = pdo_fetchall("SELECT mid FROM " . tablename('wlmerchant_merchantuser') . "WHERE uniacid = {$order_out['uniacid']} AND storeid = {$order_out['sid']} AND ismain IN (1,3) ORDER BY id DESC");
            if ($admins) {
                foreach ($admins as $key => $ad) {
                    $userModelData = [
                        'first'   => '用户:[' . $nickname . ']在线买单付费成功',
                        'type'    => '在线买单' ,//业务类型
                        'content' => '买单商户：' .$storename ,//业务内容
                        'status'  => '已付款' . $order_out['price'] . '元',//处理结果
                        'time'    => date('Y-m-d H:i:s', time()),//操作时间
                        'remark'  => '点击查看订单'
                    ];
                    $url = h5_url('pages/subPages/merchant/merchantOrderList/merchantOrderList',array('aid'=>$order_out['aid'],'storeid'=>$order_out['sid']));
                    $url = str_replace('payment/','',$url);
                    TempModel::sendInit('service',$ad['mid'],$userModelData,$_W['source'],$url);
                }
            }
            //打印
            Order::sendPrinting($order_out['id'],'payonline');
        }

    }

    static function payPayonlineReturn($params) {
        $order_out = pdo_get(PDO_NAME . 'order', array('orderno' => $params['tid']), array('id'));
        wl_message('支付成功',h5_url('pages/mainPages/paySuccess/paySuccess',['id'=>$order_out['id'],'type'=>7]) , 'success');
    }

    /**
     * 函数的含义说明
     *
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    static function getVipPayData($params) {
        global $_W;
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
        return $data;
    }

    /**
     * 获取系统运营概况（包括代理，会员等）
     *
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    static function sysSurvey($refresh = 0) {
        global $_W;
//		$data = Cache::getCache('sysSurvey', 'allData');
//		if($data && !$refresh) return $data;

        /*代理概况*/

        $agentUsers = Util::getNumData('id', PDO_NAME . 'agentusers', array());

        $members = Util::getNumData("*", PDO_NAME . 'member', array('vipstatus' => 1));
        //*地图*/
        $time = date("Y-m-d H:i:s", time());
        $merchantNumData = Util::getNumData("*", PDO_NAME . 'member', array(), 'id desc', 0, 0, 1);

        $today = strtotime(date('Ymd'));
        $firstday = strtotime(date('Y-m-01'));
        $yestoday = $today - 86400;
        //浏览量
        $d = date('Ymd');
        $uv = pdo_fetchall("select distinct mid from" . tablename(PDO_NAME . 'puvrecord') . "where uniacid = {$_W['uniacid']} and date='{$d}'");
        $todaypuv = pdo_get(PDO_NAME . 'puv', array('uniacid' => $_W['uniacid'], 'date' => date('Ymd')), array('pv', 'uv'));
        $allpuv = pdo_getall(PDO_NAME . 'puv', array('uniacid' => $_W['uniacid']), array('pv', 'uv'));
        $numPv = 0;
        $numUv = 0;
        foreach ($allpuv as $k => $v) {
            $numPv += $v['pv'];
            $numUv += $v['uv'];
        }
        $newfans = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename(PDO_NAME . 'member') . " WHERE uniacid = '{$_W['uniacid']}' and createtime >= {$firstday}");

        /*进账总金额*/
        $totalInMoney = $totalOutMoney = $rushMoney = $vipMoney = $halfMoney = $orderMoney = $refundMoney = $settlementMoney = $waitSettlementMoney = $spercentMoney = $halfPercentMoney = $vipPercentMoney = 0;
        //抢购订单金额
        $rushOrders = Util::getNumData('actualprice,status,issettlement', PDO_NAME . 'rush_order', array('#status#' => '(1,2,3,4,6,7)'));
        foreach ($rushOrders[0] as $item) {
            $rushMoney += $item['actualprice'];
            if ($item['status'] == 7) $refundMoney += $item['actualprice'];
            if ($item['issettlement'] == 0) $waitSettlementMoney += $item['actualprice'];
            if ($item['issettlement'] == 1) $settlementMoney += $item['actualprice'];
        }
        //一卡通订单
        $halfOrders = Util::getNumData('price,issettlement', PDO_NAME . 'halfcard_record', array('status' => 1));
        foreach ($halfOrders[0] as $item) {
            $halfMoney += $item['price'];
            if ($item['issettlement'] == 0) $waitSettlementMoney += $item['price'];
            if ($item['issettlement'] == 1) $settlementMoney += $item['price'];
        }
        //order表订单
        $orderOrders = Util::getNumData('price,status,issettlement', PDO_NAME . 'order', array('#status#' => '(1,2,3,4,6,7,8)'));
        foreach ($orderOrders[0] as $item) {
            $orderMoney += $item['price'];
            if ($item['status'] == 7) $refundMoney += $item['price'];
            if ($item['issettlement'] == 0) $waitSettlementMoney += $item['price'];
            if ($item['issettlement'] == 1) $settlementMoney += $item['price'];
        }
        //结算
        $settlementOrders = Util::getNumData('*', PDO_NAME . 'settlement_record', array('#status#' => '(1,2,3,4,5)'));
        foreach ($settlementOrders[0] as $item) {
//			if($item['status']==4 || $item['status']==5){
//				$settlementMoney += $item['agetmoney'];
//			}else{
//				$waitSettlementMoney += $item['aapplymoney'];
//			}
            if ($item['type'] == 1) $spercentMoney += $item['apercentmoney'];
            if ($item['type'] == 2) $halfPercentMoney += $item['apercentmoney'];
            if ($item['type'] == 3) $vipPercentMoney += $item['apercentmoney'];
        }
        $totalInMoney = sprintf("%.2f", $rushMoney + $vipMoney + $halfMoney + $orderMoney);
        $totalOutMoney = sprintf("%.2f", $refundMoney + $settlementMoney);
        $spercentMoney = sprintf("%.2f", $spercentMoney);
        $halfPercentMoney = sprintf("%.2f", $halfPercentMoney);
        $vipPercentMoney = sprintf("%.2f", $vipPercentMoney);
        $settlementMoney = sprintf("%.2f", $settlementMoney);
        $waitSettlementMoney = sprintf("%.2f", $waitSettlementMoney);
        $data = array(
            'agentNum'            => count($agentUsers[0]),
            'vipNum'              => count($members[0]),
            'todayUv'             => $todaypuv['uv'],
            'todayPv'             => $todaypuv['pv'],
            'totalPv'             => $numPv,
            'totalUv'             => $numUv,
            'ThisMonthNewFans'    => $newfans,
            'totalInMoney'        => $totalInMoney,
            'totalOutMoney'       => $totalOutMoney,
            'spercentMoney'       => $spercentMoney,
            'halfPercentMoney'    => $halfPercentMoney,
            'vipPercentMoney'     => $vipPercentMoney,
            'settlementMoney'     => $settlementMoney,
            'waitSettlementMoney' => $waitSettlementMoney

        );
        Cache::setCache('sysSurvey', 'allData', $data);
        return $data;
    }

    static function sysCashSurvey($refresh = 0, $timetype = 0, $starttime, $endtime) {
        global $_W;
//		$data = Cache::getCache('sysCashSurvey', 'allData');
//		if($data && !$refresh) return $data;
        $where = array();
        $agentsData = Util::getNumData("id,agentname", PDO_NAME . 'agentusers', $where);
        $agents = $agentsData[0];
        $children = array();
        if (!empty($agents)) {
            //总览
            $allMoney = $allorder = $fishorder = $ingorder = $fishordermoney = $ingordermoney = $fishSettlement = $ingSettlement = $refund = $sysincome = 0;
            //抢购
            $rushallmoney = $rushallorder = $rushfishorder = $rushingorder = $rushfishordermoney = $rushingordermoney = 0;
            //团购
            $grouponallmoney = $grouponallorder = $grouponfishorder = $grouponingorder = $grouponfishordermoney = $grouponingordermoney = 0;
            //拼团
            $fightallmoney = $fightallorder = $fightfishorder = $fightingorder = $fightfishordermoney = $fightingordermoney = 0;
            //卡券
            $couponallmoney = $couponallorder = $couponfishorder = $couponingorder = $couponfishordermoney = 0;
            //特权
            $halfcardallmoney = $halfcardallorder = $halfcardfishordermoney = 0;
            //掌上信息
            $pocketallmoney = $pocketallorder = $pocketfishordermoney = 0;

            //计算系统收入
            $sysincome = pdo_getcolumn('wlmerchant_settlement_record', array('uniacid' => $_W['uniacid'], 'status >=' => 4), array("SUM(spercentmoney)"));

            foreach ($agents as $index => &$row) {
                $aMoney = 0;
                $where2['aid'] = $row['id'];
                $data = Util::getNumData("id,storename,logo", PDO_NAME . 'merchantdata', $where2);
                $set = Area::getSingleAgent($row['id']);
                $percent = $set['percent'];
                foreach ($data[0] as $k => &$v) {
                    $sMoney = 0;
                    $where3['#status#'] = '(1,2,3,4,6,7,8)';
                    $where3['sid'] = $v['id'];
                    $where3['num>'] = 0;
                    if ($timetype == 1) {
                        $where3['createtime>'] = strtotime(date('Ymd'));
                    } elseif ($timetype == 2) {
                        $where3['createtime>'] = strtotime('-7 days');
                    } else if ($timetype == 3) {
                        $where3['createtime>'] = strtotime('-30 days');
                    } else if ($timetype == 5) {
                        $where3['createtime>'] = $starttime;
                        $where3['createtime<'] = $endtime;
                    }
                    $rush_orders = Util::getNumData("actualprice,status,issettlement", PDO_NAME . 'rush_order', $where3);
                    if ($rush_orders[0]) {
                        foreach ($rush_orders[0] as $order) {
                            $sMoney += $order['actualprice'];
                            $rushallmoney += $order['actualprice'];
                            $allorder += 1;
                            $rushallorder += 1;
                            if ($order['status'] == 2 || $order['status'] == 3) {
                                $fishorder += 1;
                                $rushfishorder += 1;
                                $fishordermoney += $order['actualprice'];
                                $rushfishordermoney += $order['actualprice'];
                                if ($order['issettlement'] == 1) {
                                    $fishSettlement += $order['actualprice'];
                                } else {
                                    $ingSettlement += $order['actualprice'];
                                }
                            } else {
                                $ingorder += 1;
                                $rushingorder += 1;
                                $ingordermoney += $order['actualprice'];
                                $rushingordermoney += $order['actualprice'];
                                if ($order['status'] == 7) {
                                    $refund += $order['actualprice'];
                                }
                            }
                        }
                    }
                    $orders = Util::getNumData("price,status,num,issettlement,plugin", PDO_NAME . 'order', $where3);
                    if ($orders[0]) {
                        foreach ($orders[0] as $order) {
                            $sMoney += $order['price'];
                            $allorder += 1;

                            if ($order['plugin'] == 'wlfightgroup') {
                                $fightallmoney += $order['price'];
                                $fightallorder += 1;
                            } elseif ($order['plugin'] == 'coupon') {
                                $couponallorder += 1;
                            } elseif ($order['plugin'] == 'groupon') {
                                $grouponallmoney += $order['price'];
                                $grouponallorder += 1;
                            }

                            if ($order['status'] == 2 || $order['status'] == 3) {
                                $fishordermoney += $order['price'];
                                $fishorder += 1;

                                if ($order['plugin'] == 'wlfightgroup') {
                                    $fightfishordermoney += $order['price'];
                                    $fightfishorder += 1;
                                } elseif ($order['plugin'] == 'coupon') {
                                    $couponallmoney += $order['price'];
                                    $couponfishorder += 1;
                                } elseif ($order['plugin'] == 'groupon') {
                                    $grouponfishordermoney += $order['price'];
                                    $grouponfishorder += 1;
                                }

                                if ($order['issettlement'] == 1) {
                                    $fishSettlement += $order['price'];
                                    if ($order['plugin'] == 'coupon') {
                                        $couponfishordermoney += $order['price'];
                                    }
                                } else {
                                    $ingSettlement += $order['price'];
                                }
                            } else {
                                $ingorder += 1;
                                $ingordermoney += $order['price'];
                                if ($order['status'] == 7) {
                                    $refund += $order['price'];
                                }

                                if ($order['plugin'] == 'wlfightgroup') {
                                    $fightingordermoney += $order['price'];
                                    $fightingorder += 1;
                                } elseif ($order['plugin'] == 'coupon') {
                                    $couponingorder += 1;
                                } elseif ($order['plugin'] == 'groupon') {
                                    $grouponingordermoney += $order['price'];
                                    $grouponingorder += 1;
                                }

                            }
                        }
                    }
                    $v['sMoney'] = $sMoney;
                    $aMoney += $sMoney;
                }
                foreach ($data[0] as &$money) {
                    $money['forpercent'] = @sprintf('%.2f', ($money['sMoney'] / $aMoney) * 100);
                }

                //计算vip与一卡通
                $where4['aid'] = $row['id'];
                $where4['status'] = 1;
                if ($timetype == 1) {
                    $where4['createtime>'] = strtotime(date('Ymd'));
                } elseif ($timetype == 2) {
                    $where4['createtime>'] = strtotime('-7 days');
                } else if ($timetype == 3) {
                    $where4['createtime>'] = strtotime('-30 days');
                } else if ($timetype == 5) {
                    $where4['createtime>'] = $starttime;
                    $where4['createtime<'] = $endtime;
                }
                $halforder = Util::getNumData("price", PDO_NAME . 'halfcard_record', $where4);
                if ($halforder[0]) {
                    foreach ($halforder[0] as $order) {
                        $aMoney += $order['price'];
                        $allorder += 1;
                        $fishorder += 1;
                        $fishordermoney += $order['price'];
                        $halfcardallmoney += $order['price'];
                        $halfcardallorder += 1;
                        if ($order['issettlement'] == 1) {
                            $fishSettlement += $order['price'];
                            $halfcardfishordermoney += $order['price'];
                        } else {
                            $ingSettlement += $order['price'];
                        }
                    }
                }
                //计算掌上信息
                $where5['aid'] = $row['id'];
                $where5['status'] = 3;
                $where5['sid'] = 0;
                if ($timetype == 1) {
                    $where5['createtime>'] = strtotime(date('Ymd'));
                } elseif ($timetype == 2) {
                    $where5['createtime>'] = strtotime('-7 days');
                } else if ($timetype == 3) {
                    $where5['createtime>'] = strtotime('-30 days');
                } else if ($timetype == 5) {
                    $where5['createtime>'] = $starttime;
                    $where5['createtime<'] = $endtime;
                }
                $pocketorder = Util::getNumData("price", PDO_NAME . 'order', $where5);
                if ($pocketorder[0]) {
                    foreach ($pocketorder[0] as $order) {
                        $aMoney += $order['price'];
                        $allorder += 1;
                        $fishorder += 1;
                        $fishordermoney += $order['price'];
                        $pocketallmoney += $order['price'];
                        $pocketallorder += 1;
                        if ($order['issettlement'] == 1) {
                            $fishSettlement += $order['price'];
                            $pocketfishordermoney += $order['price'];
                        } else {
                            $ingSettlement += $order['price'];
                        }
                    }
                }
                $pocketnum = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_pocket_informations') . "WHERE mid > 0 ORDER BY id DESC");
                $pocketnum = count($pocketnum);


                $children[$row['id']] = $data[0];
                $row['aMoney'] = $aMoney;
                $allMoney += $aMoney;
            }
        }
        $max = 0;
        foreach ($agents as $index => &$percent) {
            $percent['forpercent'] = @sprintf('%.2f', ($percent['aMoney'] / $allMoney) * 100);
            $allMoney = sprintf('%.2f', $allMoney);
            $max = $percent['aMoney'] > $max ? $max = $percent['aMoney'] : $max;
            $max = sprintf('%.2f', $max);
        }
        $time = date('Y-m-d H:i:s', time());
        //新数据统计
        $newdata['all']['allmoney'] = $allMoney;
        $newdata['all']['allorder'] = $allorder;
        $newdata['all']['fishorder'] = $fishorder;
        $newdata['all']['fishordermoney'] = $fishordermoney;
        $newdata['all']['ingorder'] = $ingorder;
        $newdata['all']['ingordermoney'] = $ingordermoney;
        $newdata['all']['fishSettlement'] = @sprintf('%.2f', $fishSettlement);
        $newdata['all']['ingSettlement'] = $ingSettlement;
        $newdata['all']['refund'] = $refund;
        $newdata['all']['sysincome'] = @sprintf('%.2f', $sysincome);

        $newdata['rush']['rushallmoney'] = $rushallmoney;
        $newdata['rush']['rushallorder'] = $rushallorder;
        $newdata['rush']['rushfishorder'] = $rushfishorder;
        $newdata['rush']['rushfishordermoney'] = $rushfishordermoney;
        $newdata['rush']['rushingorder'] = $rushingorder;
        $newdata['rush']['rushingordermoney'] = $rushingordermoney;

        $newdata['groupon']['grouponallmoney'] = $grouponallmoney;
        $newdata['groupon']['grouponallorder'] = $grouponallorder;
        $newdata['groupon']['grouponfishorder'] = $grouponfishorder;
        $newdata['groupon']['grouponfishordermoney'] = $grouponfishordermoney;
        $newdata['groupon']['grouponingorder'] = $grouponingorder;
        $newdata['groupon']['grouponingordermoney'] = $grouponingordermoney;

        $newdata['fight']['fightallmoney'] = $fightallmoney;
        $newdata['fight']['fightallorder'] = $fightallorder;
        $newdata['fight']['fightfishorder'] = $fightfishorder;
        $newdata['fight']['fightfishordermoney'] = $fightfishordermoney;
        $newdata['fight']['fightingorder'] = $fightingorder;
        $newdata['fight']['fightingordermoney'] = $fightingordermoney;

        $fightwhere['status'] = 2;
        if ($timetype == 1) {
            $fightwhere['successtime>'] = strtotime(date('Ymd'));
        } elseif ($timetype == 2) {
            $fightwhere['successtime>'] = strtotime('-7 days');
        } else if ($timetype == 3) {
            $fightwhere['successtime>'] = strtotime('-30 days');
        } else if ($timetype == 5) {
            $fightwhere['successtime>'] = $starttime;
            $fightwhere['successtime<'] = $endtime;
        }
        $fightsuccess = Util::getNumData("id", PDO_NAME . 'fightgroup_group', $fightwhere);
        $newdata['fight']['successnum'] = count($fightsuccess[0]);

        $newdata['coupon']['couponallmoney'] = $couponallmoney;
        $newdata['coupon']['couponallorder'] = $couponallorder;
        $newdata['coupon']['couponfishorder'] = $couponfishorder;
        $newdata['coupon']['couponingorder'] = $couponingorder;
        $newdata['coupon']['couponfishordermoney'] = $couponfishordermoney;

        $couponwhere['usetimes'] = 0;
        if ($timetype == 1) {
            $couponwhere['createtime>'] = strtotime(date('Ymd'));
        } elseif ($timetype == 2) {
            $couponwhere['createtime>'] = strtotime('-7 days');
        } else if ($timetype == 3) {
            $couponwhere['createtime>'] = strtotime('-30 days');
        } else if ($timetype == 5) {
            $couponwhere['createtime>'] = $starttime;
            $couponwhere['createtime<'] = $endtime;
        }
        $coupons = Util::getNumData("id", PDO_NAME . 'member_coupons', $couponwhere);
        $newdata['coupon']['couponnum'] = count($coupons[0]);

        $newdata['halfcard']['halfcardallmoney'] = $halfcardallmoney;
        $newdata['halfcard']['halfcardallorder'] = $halfcardallorder;
        $newdata['halfcard']['halfcardfishordermoney'] = $halfcardfishordermoney;
        $halfcardnum = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_halfcardmember') . "WHERE expiretime > " . time() . " ORDER BY id DESC");
        $halfcardnum = count($halfcardnum);
        $newdata['halfcard']['halfcardnum'] = $halfcardnum;

        $newdata['vip']['vipallmoney'] = $vipallmoney;
        $newdata['vip']['vipallorder'] = $vipallorder;
        $newdata['vip']['vipfishordermoney'] = $vipfishordermoney;
        $vipnum = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_member') . "WHERE lastviptime > " . time() . " ORDER BY id DESC");
        $vipnum = count($vipnum);
        $newdata['vip']['vipnum'] = $vipnum;

        $newdata['pocket']['pocketallmoney'] = $pocketallmoney;
        $newdata['pocket']['pocketallorder'] = $pocketallorder;
        $newdata['pocket']['pocketfishordermoney'] = $pocketfishordermoney;
        $newdata['pocket']['pocketnum'] = $pocketnum;


        $data = array($agents, $children, $max, $allMoney, $time, $newdata);
        Cache::setCache('sysCashSurvey', 'allData', $data);
        return $data;
    }

    static function agentCashSurvey($refresh = 0, $timetype = 0, $starttime, $endtime, $merchantid = 0) {
        global $_W;
//		$data = Cache::getCache('agentCashSurvey', 'allData');
//		if($data && !$refresh) return $data;
        $where = array();
        $where['id'] = $_W['agent']['id'];
        $agentsData = Util::getNumData("id,agentname", PDO_NAME . 'agentusers', $where);
        $agents = $agentsData[0];
        $children = array();
        if (!empty($agents)) {
            //总览
            $allMoney = $allorder = $fishorder = $ingorder = $fishordermoney = $ingordermoney = $fishSettlement = $ingSettlement = $refund = $sysincome = 0;
            //抢购
            $rushallmoney = $rushallorder = $rushfishorder = $rushingorder = $rushfishordermoney = $rushingordermoney = 0;
            //团购
            $grouponallmoney = $grouponallorder = $grouponfishorder = $grouponingorder = $grouponfishordermoney = $grouponingordermoney = 0;
            //拼团
            $fightallmoney = $fightallorder = $fightfishorder = $fightingorder = $fightfishordermoney = $fightingordermoney = 0;
            //卡券
            $couponallmoney = $couponallorder = $couponfishorder = $couponingorder = $couponfishordermoney = 0;
            //特权
            $halfcardallmoney = $halfcardallorder = $halfcardfishordermoney = 0;
            //掌上信息
            $pocketallmoney = $pocketallorder = $pocketfishordermoney = 0;

            //代理商佣金信息
            $agentAmount = Util::getSingelData('allmoney,nowmoney', PDO_NAME . 'agentusers', array('uniacid' => $_W['uniacid'], 'id' => $_W['aid']));
            $sysincome = $agentAmount['allmoney'];//代理佣金收入
            $fishSettlement = sprintf('%.2f', ($agentAmount['allmoney'] - $agentAmount['nowmoney']));//已结算金额
            $ingSettlement = sprintf('%.2f', $agentAmount['nowmoney']);//未结算金额

            //计算系统收入
            $aMoney = 0;
            $where2['aid'] = $agents[0]['id'];
            if ($merchantid) {
                $where2['id'] = $merchantid;
            }
            $data = Util::getNumData("id,storename,logo", PDO_NAME . 'merchantdata', $where2);

            $max = 0;
            foreach ($data[0] as $k => &$v) {

                $sMoney = 0;
                $where3['#status#'] = '(1,2,3,4,6,7,8)';
                $where3['sid'] = $v['id'];
                if ($timetype == 1) {
                    $where3['createtime>'] = strtotime(date('Ymd'));
                } elseif ($timetype == 2) {
                    $where3['createtime>'] = strtotime('-7 days');
                } else if ($timetype == 3) {
                    $where3['createtime>'] = strtotime('-30 days');
                } else if ($timetype == 5) {
                    $where3['createtime>'] = $starttime;
                    $where3['createtime<'] = $endtime;
                }

                $rush_orders = Util::getNumData("actualprice,status,issettlement,sid", PDO_NAME . 'rush_order', $where3);
                foreach ($rush_orders[0] as $order) {
                    $sMoney += $order['actualprice'];
                    $allMoney += $order['actualprice'];
                    $rushallmoney += $order['actualprice'];
                    $allorder += 1;
                    $rushallorder += 1;
                    if ($order['status'] == 2 || $order['status'] == 3) {
                        $fishorder += 1;
                        $rushfishorder += 1;
                        $fishordermoney += $order['actualprice'];
                        $rushfishordermoney += $order['actualprice'];
                        if ($order['issettlement'] == 1) {
                            //$fishSettlement += $order['actualprice'];
                            $store = Store::getSingleStore($order['sid']);
                        } else {
                            //$ingSettlement += $order['actualprice'];
                        }
                    } else {
                        $ingorder += 1;
                        $rushingorder += 1;
                        $ingordermoney += $order['actualprice'];
                        $rushingordermoney += $order['actualprice'];
                        if ($order['status'] == 7) {
                            $refund += $order['actualprice'];
                        }
                    }

                }
                $orders = Util::getNumData("price,status,num,issettlement,plugin,sid", PDO_NAME . 'order', $where3);
                foreach ($orders[0] as $order) {
                    $sMoney += $order['price'];
                    $allMoney += $order['price'];
                    $allorder += 1;

                    if ($order['plugin'] == 'wlfightgroup') {
                        $fightallmoney += $order['price'];
                        $fightallorder += 1;
                    } elseif ($order['plugin'] == 'coupon') {
                        $couponallorder += 1;
                    } elseif ($order['plugin'] == 'groupon') {
                        $grouponallmoney += $order['price'];
                        $grouponallorder += 1;
                    }

                    if ($order['status'] == 2 || $order['status'] == 3) {
                        $fishordermoney += $order['price'];
                        $fishorder += 1;

                        if ($order['plugin'] == 'wlfightgroup') {
                            $fightfishordermoney += $order['price'];
                            $fightfishorder += 1;
                        } elseif ($order['plugin'] == 'coupon') {
                            $couponallmoney += $order['price'];
                            $couponfishorder += 1;
                        } elseif ($order['plugin'] == 'groupon') {
                            $grouponfishordermoney += $order['price'];
                            $grouponfishorder += 1;
                        }

                        if ($order['issettlement'] == 1) {
                            //$fishSettlement += $order['price'];
                            $store = Store::getSingleStore($order['sid']);
                            if ($order['plugin'] == 'coupon') {
                                $couponfishordermoney += $order['price'];
                            }
                        } else {
                            //$ingSettlement += $order['price'];
                        }
                    } else {
                        $ingorder += 1;
                        $ingordermoney += $order['price'];
                        if ($order['status'] == 7) {
                            $refund += $order['price'];
                        }

                        if ($order['plugin'] == 'wlfightgroup') {
                            $fightingordermoney += $order['price'];
                            $fightingorder += 1;
                        } elseif ($order['plugin'] == 'coupon') {
                            $couponingorder += 1;
                        } elseif ($order['plugin'] == 'groupon') {
                            $grouponingordermoney += $order['price'];
                            $grouponingorder += 1;
                        }
                    }
                }
                $v['sMoney'] = $sMoney;
                $aMoney += $sMoney;
            }
            foreach ($data[0] as &$money) {
                $money['forpercent'] = @sprintf('%.2f', ($money['sMoney'] / $aMoney) * 100);
                $max = $money['sMoney'] > $max ? $max = $money['sMoney'] : $max;
                $max = sprintf('%.2f', $max);
            }
            if (empty($merchantid)) {
                //计算vip与一卡通
                $where4['aid'] = $agents[0]['id'];
                $where4['status'] = 1;
                if ($timetype == 1) {
                    $where4['createtime>'] = strtotime(date('Ymd'));
                } elseif ($timetype == 2) {
                    $where4['createtime>'] = strtotime('-7 days');
                } else if ($timetype == 3) {
                    $where4['createtime>'] = strtotime('-30 days');
                } else if ($timetype == 5) {
                    $where4['createtime>'] = $starttime;
                    $where4['createtime<'] = $endtime;
                }
                $halforder = Util::getNumData("price", PDO_NAME . 'halfcard_record', $where4);
                if ($halforder[0]) {
                    foreach ($halforder[0] as $order) {
                        $aMoney += $order['price'];
                        $allMoney += $order['price'];
                        $allorder += 1;
                        $fishorder += 1;
                        $fishordermoney += $order['price'];
                        $halfcardallmoney += $order['price'];
                        $halfcardallorder += 1;
                        if ($order['issettlement'] == 1) {
                            // $fishSettlement += $order['price'];
                            $halfcardfishordermoney += $order['price'];
                        } else {
                            //$ingSettlement += $order['price'];
                        }
                    }
                }
                //计算掌上信息
                $where5['aid'] = $agents[0]['id'];
                $where5['status'] = 3;
                $where5['sid'] = 0;
                if ($timetype == 1) {
                    $where5['createtime>'] = strtotime(date('Ymd'));
                } elseif ($timetype == 2) {
                    $where5['createtime>'] = strtotime('-7 days');
                } else if ($timetype == 3) {
                    $where5['createtime>'] = strtotime('-30 days');
                } else if ($timetype == 5) {
                    $where5['createtime>'] = $starttime;
                    $where5['createtime<'] = $endtime;
                }
                $pocketorder = Util::getNumData("price", PDO_NAME . 'order', $where5);
                if ($pocketorder[0]) {
                    foreach ($pocketorder[0] as $order) {
                        $aMoney += $order['price'];
                        $allMoney += $order['price'];
                        $allorder += 1;
                        $fishorder += 1;
                        $fishordermoney += $order['price'];
                        $pocketallmoney += $order['price'];
                        $pocketallorder += 1;
                        if ($order['issettlement'] == 1) {
                            //$fishSettlement += $order['price'];
                            $pocketfishordermoney += $order['price'];
                        } else {
                            // $ingSettlement += $order['price'];
                        }
                    }
                }
                $pocketnum = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_pocket_informations') . "WHERE mid > 0 ORDER BY id DESC");
                $pocketnum = count($pocketnum);
            }
            $children[$agents[0]['id']] = $data[0];
            $row['aMoney'] = $aMoney;
            //$allMoney +=$aMoney;
        }
        foreach ($agents as $index => &$percent) {
            $percent['forpercent'] = @sprintf('%.2f', ($percent['aMoney'] / $allMoney) * 100);
            $allMoney = @sprintf('%.2f', $allMoney);
        }
        $time = date('Y-m-d H:i:s', time());

        //新数据统计
        $newdata['all']['allmoney'] = $allMoney;
        $newdata['all']['allorder'] = $allorder;
        $newdata['all']['fishorder'] = $fishorder;
        $newdata['all']['fishordermoney'] = $fishordermoney;
        $newdata['all']['ingorder'] = $ingorder;
        $newdata['all']['ingordermoney'] = $ingordermoney;
        $newdata['all']['fishSettlement'] = $fishSettlement;
        $newdata['all']['ingSettlement'] = $ingSettlement;
        $newdata['all']['refund'] = $refund;
        $newdata['all']['sysincome'] = @sprintf('%.2f', $sysincome);

        $newdata['rush']['rushallmoney'] = $rushallmoney;
        $newdata['rush']['rushallorder'] = $rushallorder;
        $newdata['rush']['rushfishorder'] = $rushfishorder;
        $newdata['rush']['rushfishordermoney'] = $rushfishordermoney;
        $newdata['rush']['rushingorder'] = $rushingorder;
        $newdata['rush']['rushingordermoney'] = $rushingordermoney;

        $newdata['groupon']['grouponallmoney'] = $grouponallmoney;
        $newdata['groupon']['grouponallorder'] = $grouponallorder;
        $newdata['groupon']['grouponfishorder'] = $grouponfishorder;
        $newdata['groupon']['grouponfishordermoney'] = $grouponfishordermoney;
        $newdata['groupon']['grouponingorder'] = $grouponingorder;
        $newdata['groupon']['grouponingordermoney'] = $grouponingordermoney;

        $newdata['fight']['fightallmoney'] = $fightallmoney;
        $newdata['fight']['fightallorder'] = $fightallorder;
        $newdata['fight']['fightfishorder'] = $fightfishorder;
        $newdata['fight']['fightfishordermoney'] = $fightfishordermoney;
        $newdata['fight']['fightingorder'] = $fightingorder;
        $newdata['fight']['fightingordermoney'] = $fightingordermoney;

        $fightwhere['status'] = 2;
        if ($timetype == 1) {
            $fightwhere['successtime>'] = strtotime(date('Ymd'));
        } elseif ($timetype == 2) {
            $fightwhere['successtime>'] = strtotime('-7 days');
        } else if ($timetype == 3) {
            $fightwhere['successtime>'] = strtotime('-30 days');
        } else if ($timetype == 5) {
            $fightwhere['successtime>'] = $starttime;
            $fightwhere['successtime<'] = $endtime;
        }
        if ($merchantid) {
            $fightwhere['sid'] = $merchantid;
        }
        $fightsuccess = Util::getNumData("id", PDO_NAME . 'fightgroup_group', $fightwhere);
        $newdata['fight']['successnum'] = count($fightsuccess[0]);

        $newdata['coupon']['couponallmoney'] = $couponallmoney;
        $newdata['coupon']['couponallorder'] = $couponallorder;
        $newdata['coupon']['couponfishorder'] = $couponfishorder;
        $newdata['coupon']['couponingorder'] = $couponingorder;
        $newdata['coupon']['couponfishordermoney'] = $couponfishordermoney;

        $couponwhere['usetimes'] = 0;
        if ($timetype == 1) {
            $couponwhere['createtime>'] = strtotime(date('Ymd'));
        } elseif ($timetype == 2) {
            $couponwhere['createtime>'] = strtotime('-7 days');
        } else if ($timetype == 3) {
            $couponwhere['createtime>'] = strtotime('-30 days');
        } else if ($timetype == 5) {
            $couponwhere['createtime>'] = $starttime;
            $couponwhere['createtime<'] = $endtime;
        }
        if ($merchantid) {
            $coupons = pdo_getall('wlmerchant_couponlist', array('merchantid' => $merchantid), array('id'));
            if ($coupons) {
                $goodids = "(";
//				foreach ($coupons as $k => $v) {
//					if($k == 0){
//						$goodids.= $v['id'];
//					}else{
//						$goodids.= ",".$v['id'];
//					}
//				}
                for ($i = 0; $i < count($coupons); $i++) {
                    if ($i == 0) {
                        $goodids .= $coupons[$i]['id'];
                    } else {
                        $goodids .= "," . $coupons[$i]['id'];
                    }
                }
                $goodids .= ")";
                $couponwhere['parentid#'] = $goodids;
            } else {
                $couponwhere['parentid#'] = "(0)";
            }
        }
        $coupons = Util::getNumData("id", PDO_NAME . 'member_coupons', $couponwhere);
        $newdata['coupon']['couponnum'] = count($coupons[0]);

        $newdata['halfcard']['halfcardallmoney'] = $halfcardallmoney;
        $newdata['halfcard']['halfcardallorder'] = $halfcardallorder;
        $newdata['halfcard']['halfcardfishordermoney'] = $halfcardfishordermoney;
        $halfcardnum = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_halfcardmember') . "WHERE expiretime > " . time() . " ORDER BY id DESC");
        $halfcardnum = count($halfcardnum);
        $newdata['halfcard']['halfcardnum'] = $halfcardnum;

        $newdata['vip']['vipallmoney'] = $vipallmoney;
        $newdata['vip']['vipallorder'] = $vipallorder;
        $newdata['vip']['vipfishordermoney'] = $vipfishordermoney;
        $vipnum = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_member') . "WHERE lastviptime > " . time() . " ORDER BY id DESC");
        $vipnum = count($vipnum);
        $newdata['vip']['vipnum'] = $vipnum;

        $newdata['pocket']['pocketallmoney'] = $pocketallmoney;
        $newdata['pocket']['pocketallorder'] = $pocketallorder;
        $newdata['pocket']['pocketfishordermoney'] = $pocketfishordermoney;
        $newdata['pocket']['pocketnum'] = $pocketnum;

        $data = array($agents, $children, $max, $allMoney, $time, $newdata);
        Cache::setCache('agentCashSurvey', 'allData', $data);
        return $data;
    }

    /**
     * 获取系统运营概况（包括代理，会员等）
     *
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    static function sysMemberSurvey() {
        global $_W, $_GPC;
        $stat = array();
        $today_starttime = strtotime(date('Y-m-d'));
        $yesterday_starttime = $today_starttime - 86400;
        $month_starttime = strtotime(date('Y-m'));
        $where = $_W['aid'] ? " AND aid = " . $_W['aid'] : '';

        $stat['yesterday_num'] = intval(pdo_fetchcolumn('select count(*) from ' . tablename('wlmerchant_member') . ' where uniacid = :uniacid and createtime >= :starttime and createtime <= :endtime' . $where, array(':uniacid' => $_W['uniacid'], ':starttime' => $yesterday_starttime, ':endtime' => $today_starttime)));
        $stat['today_num'] = intval(pdo_fetchcolumn('select count(*) from ' . tablename('wlmerchant_member') . ' where uniacid = :uniacid and createtime >= :starttime' . $where, array(':uniacid' => $_W['uniacid'], ':starttime' => $today_starttime)));
        $stat['month_num'] = intval(pdo_fetchcolumn('select count(*) from ' . tablename('wlmerchant_member') . ' where uniacid = :uniacid and createtime >= :starttime' . $where, array(':uniacid' => $_W['uniacid'], ':starttime' => $month_starttime)));
        $stat['total_num'] = intval(pdo_fetchcolumn('select count(*) from ' . tablename('wlmerchant_member') . ' where uniacid = :uniacid' . $where, array(':uniacid' => $_W['uniacid'])));

        return $stat;
    }

    /**
     * 获取系统运营概况（包括代理，会员等）
     *
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    static function agentSurvey($refresh = 0) {
        global $_W;
//		$data = Cache::getCache('agentSurvey', 'allData');
//		if($data && !$refresh) return $data;
        /*会员概况*/
        $members = Util::getNumData("*", PDO_NAME . 'member', array('vipstatus' => 1, 'aid' => $_W['agent']['id']));
        $time = date("Y-m-d H:i:s", time());
        $merchants = Util::getNumData('id', PDO_NAME . 'merchantdata', array('aid' => $_W['agent']['id']), 'id desc', 0, 0, 1);
        $areaids = Util::idSwitch('aid', 'areaid', $_W['agent']['id']);
        $s = "(0";
        foreach ($areaids as $k => $v) {
            $s .= "," . "'" . $v['areaid'] . "'";
        }
        $s .= ")";
        $today = strtotime(date('Ymd'));
        $firstday = strtotime(date('Y-m-01'));
        $yestoday = $today - 86400;
        //浏览量
        $where = array();
        $where['date'] = date('Ymd');
        $where['#areaid#'] = $s;
        $todaypuv = Util::getSingelData('pv,uv', PDO_NAME . 'puv', $where);
        if (empty($todaypuv)) $todaypuv['pv'] = $todaypuv['uv'] = 0;
        unset($where['date']);
        $allpuv = Util::getNumData('pv,uv', PDO_NAME . 'puv', $where);
        $numPv = 0;
        $numUv = 0;
        foreach ($allpuv[0] as $k => $v) {
            $numPv += $v['pv'];
            $numUv += $v['uv'];
        }
        $newfans = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename(PDO_NAME . 'member') . " WHERE uniacid = '{$_W['uniacid']}' and createtime >= {$firstday}");


        /*进账总金额*/
        $totalInMoney = $totalOutMoney = $rushMoney = $vipMoney = $halfMoney = $orderMoney = $refundMoney = $settlementMoney = $waitSettlementMoney = $spercentMoney = $halfPercentMoney = $vipPercentMoney = 0;
        //抢购订单金额
        $rushOrders = Util::getNumData('actualprice,status,issettlement', PDO_NAME . 'rush_order', array('#status#' => '(1,2,3,4,6,7)', 'aid' => $_W['agent']['id']));
        foreach ($rushOrders[0] as $item) {
            $rushMoney += $item['actualprice'];
            if ($item['issettlement'] == 1) $waitSettlementMoney += $item['actualprice'];
            if ($item['issettlement'] == 2) $settlementMoney += $item['actualprice'];
            if ($item['status'] == 7) $refundMoney += $item['actualprice'];
        }
        //VIP订单
        $vipOrders = Util::getNumData('price,issettlement', PDO_NAME . 'vip_record', array('#status#' => '(1)', 'aid' => $_W['agent']['id']));
        foreach ($vipOrders[0] as $item) {
            $vipMoney += $item['price'];
            if ($item['issettlement'] == 1) $waitSettlementMoney += $item['price'];
            if ($item['issettlement'] == 2) $settlementMoney += $item['price'];
        }
        //一卡通订单
        $halfOrders = Util::getNumData('price,issettlement', PDO_NAME . 'halfcard_record', array('#status#' => '(1)', 'aid' => $_W['agent']['id']));
        foreach ($vipOrders[0] as $item) {
            $halfMoney += $item['price'];
            if ($item['issettlement'] == 1) $waitSettlementMoney += $item['price'];
            if ($item['issettlement'] == 2) $settlementMoney += $item['price'];
        }
        //order表订单
        $orderOrders = Util::getNumData('price,status,issettlement', PDO_NAME . 'order', array('#status#' => '(1,2,3,4,6,7,8)', 'aid' => $_W['agent']['id']));
        foreach ($orderOrders[0] as $item) {
            $orderMoney += $item['price'];
            if ($item['status'] == 7) $refundMoney += $item['price'];
            if ($item['issettlement'] == 1) $waitSettlementMoney += $item['price'];
            if ($item['issettlement'] == 2) $settlementMoney += $item['price'];
        }
        //结算
        $settlementOrders = Util::getNumData('*', PDO_NAME . 'settlement_record', array('#status#' => '(1,2,3,4,5)', 'aid' => $_W['agent']['id']));
        foreach ($settlementOrders[0] as $item) {
//			if($item['status']==5){
//				$settlementMoney += $item['sgetmoney'];
//			}else{
//				$waitSettlementMoney += $item['sapplymoney'];
//			}
            if ($item['type'] == 1) $spercentMoney += $item['spercentmoney'];
            if ($item['type'] == 2) $halfPercentMoney += $item['agetmoney'];
            if ($item['type'] == 3) $vipPercentMoney += $item['agetmoney'];
        }
        $totalInMoney = sprintf("%.2f", $rushMoney + $vipMoney + $halfMoney + $orderMoney);
        $totalOutMoney = sprintf("%.2f", $refundMoney + $settlementMoney);
        $spercentMoney = sprintf("%.2f", $spercentMoney);
        $halfPercentMoney = sprintf("%.2f", $halfPercentMoney);
        $vipPercentMoney = sprintf("%.2f", $vipPercentMoney);
        $settlementMoney = sprintf("%.2f", $settlementMoney);
        $waitSettlementMoney = sprintf("%.2f", $waitSettlementMoney);
        $data = array(
            'merchantNum'         => count($merchants[0]),
            'vipNum'              => count($members[0]),
            'updateTime'          => $time,
            'todayPv'             => $todaypuv['pv'],
            'todayUv'             => $todaypuv['uv'],
            'totalPv'             => $numPv,
            'totalUv'             => $numUv,
            'ThisMouthNewFans'    => $newfans,
            'totalInMoney'        => $totalInMoney,
            'totalOutMoney'       => $totalOutMoney,
            'spercentMoney'       => $spercentMoney,
            'halfPercentMoney'    => $halfPercentMoney,
            'vipPercentMoney'     => $vipPercentMoney,
            'settlementMoney'     => $settlementMoney,
            'waitSettlementMoney' => $waitSettlementMoney
        );
        Cache::setCache('agentSurvey', 'allData', $data);
        return $data;
    }

    /**
     * 获取系统运营概况（包括代理，会员等）
     *
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    static function agentMemberSurvey($refresh = 0) {
        global $_W;
        $data = Cache::getCache('memberSurvey', 'allData');
        if ($data && !$refresh) return $data;

        $members = Util::getNumData("*", PDO_NAME . 'member', array('vipstatus' => 1, 'aid' => $_W['agent']['id']));
        //*地图*/
        $address_arr['beijing'] = 0;
        $address_arr['tianjing'] = 0;
        $address_arr['shanghai'] = 0;
        $address_arr['chongqing'] = 0;
        $address_arr['hebei'] = 0;
        $address_arr['yunnan'] = 0;
        $address_arr['liaoning'] = 0;
        $address_arr['heilongjiang'] = 0;
        $address_arr['hunan'] = 0;
        $address_arr['anhui'] = 0;
        $address_arr['shandong'] = 0;
        $address_arr['xingjiang'] = 0;
        $address_arr['jiangshu'] = 0;
        $address_arr['zhejiang'] = 0;
        $address_arr['jiangxi'] = 0;
        $address_arr['hubei'] = 0;
        $address_arr['guangxi'] = 0;
        $address_arr['ganshu'] = 0;
        $address_arr['shanxi'] = 0;
        $address_arr['neimenggu'] = 0;
        $address_arr['sanxi'] = 0;
        $address_arr['jiling'] = 0;
        $address_arr['fujian'] = 0;
        $address_arr['guizhou'] = 0;
        $address_arr['guangdong'] = 0;
        $address_arr['qinghai'] = 0;
        $address_arr['xizhang'] = 0;
        $address_arr['shichuan'] = 0;
        $address_arr['ningxia'] = 0;
        $address_arr['hainan'] = 0;
        foreach ($members[0] as $key => $value) {
            $thisArea = pdo_get(PDO_NAME . 'area', array('id' => $value['areaid']));
            $name = pdo_get(PDO_NAME . 'area', array('id' => $thisArea['pid']));
            $address_name = mb_strcut($name['name'], 0, 6, 'utf-8');
            switch ($address_name) {
                case '北京':
                    $address_arr['beijing'] += 1;
                    break;
                case '天津':
                    $address_arr['tianjing'] += 1;
                    break;
                case '上海':
                    $address_arr['shanghai'] += 1;
                    break;
                case '重庆':
                    $address_arr['chongqing'] += 1;
                    break;
                case '河北':
                    $address_arr['hebei'] += 1;
                    break;
                case '河南':
                    $address_arr['henan'] += 1;
                    break;
                case '云南':
                    $address_arr['yunnan'] += 1;
                    break;
                case '辽宁':
                    $address_arr['liaoning'] += 1;
                    break;
                case '黑龙':
                    $address_arr['heilongjiang'] += 1;
                    break;
                case '湖南':
                    $address_arr['hunan'] += 1;
                    break;
                case '安徽':
                    $address_arr['anhui'] += 1;
                    break;
                case '山东':
                    $address_arr['shandong'] += 1;
                    break;
                case '新疆':
                    $address_arr['xingjiang'] += 1;
                    break;
                case '江苏':
                    $address_arr['jiangshu'] += 1;
                    break;
                case '浙江':
                    $address_arr['zhejiang'] += 1;
                    break;
                case '江西':
                    $address_arr['jiangxi'] += 1;
                    break;
                case '湖北':
                    $address_arr['hubei'] += 1;
                    break;
                case '广西':
                    $address_arr['guangxi'] += 1;
                    break;
                case '甘肃':
                    $address_arr['ganshu'] += 1;
                    break;
                case '山西':
                    $address_arr['shanxi'] += 1;
                    break;
                case '内蒙':
                    $address_arr['neimenggu'] += 1;
                    break;
                case '陕西':
                    $address_arr['sanxi'] += 1;
                    break;
                case '吉林':
                    $address_arr['jiling'] += 1;
                    break;
                case '福建':
                    $address_arr['fujian'] += 1;
                    break;
                case '贵州':
                    $address_arr['guizhou'] += 1;
                    break;
                case '广东':
                    $address_arr['guangdong'] += 1;
                    break;
                case '青海':
                    $address_arr['qinghai'] += 1;
                    break;
                case '西藏':
                    $address_arr['xizhang'] += 1;
                    break;
                case '四川':
                    $address_arr['shichuan'] += 1;
                    break;
                case '宁夏':
                    $address_arr['ningxia'] += 1;
                    break;
                case '海南':
                    $address_arr['hainan'] += 1;
                    break;
            }
        }
        $where = array();
        $stime = strtotime(date('Y-m-d')) - 86400;
        $etime = strtotime(date('Y-m-d'));
        $where['paytime>'] = $stime;
        $where['paytime<'] = $etime;
        $where['status'] = 1;
        $where['aid'] = $_W['agent']['id'];
        $yesterdayVip = Util::getNumData("*", PDO_NAME . 'vip_record', $where, 'id desc', 0, 0, 1);

        $stime = strtotime(date('Y-m-d'));
        $etime = strtotime(date('Y-m-d')) + 86400;
        $where['paytime>'] = $stime;
        $where['paytime<'] = $etime;
        $where['status'] = 1;
        $where['aid'] = $_W['agent']['id'];
        $todayVip = Util::getNumData("*", PDO_NAME . 'vip_record', $where, 'id desc', 0, 0, 1);

        $stime = strtotime(date('Y-m-d')) - 6 * 86400;
        $etime = strtotime(date('Y-m-d')) + 86400;
        $where['paytime>'] = $stime;
        $where['paytime<'] = $etime;
        $where['status'] = 1;
        $where['aid'] = $_W['agent']['id'];
        $weekVip = Util::getNumData("*", PDO_NAME . 'vip_record', $where, 'id desc', 0, 0, 1);

        $data = array(count($members), $address_arr, $yesterdayVip, $todayVip, $weekVip);
        Cache::setCache('agentMemberSurvey', 'allData', $data);
        return $data;
    }

    static function cacheSurvey($aid,$storeid = 0) {
        global $_W;
        $condition = "WHERE uniacid = {$_W['uniacid']}";
        if($storeid){
            $condition .= " AND sid = {$storeid}";
        }
        if ($aid) {
            $condition .= " AND aid = {$aid}";
            if ($_W['wlsetting']['distribution']['seetstatus']) {
                $condition2 = $condition . " AND plugin != 'distribution' ";
            }
        }
        if (empty($condition2)) {
            $condition2 = $condition;
        }
        if($storeid){
            $condition2 .= " AND plugin != 'store'";
        }

        $yesterday = date('d') - 1;
        $yessta = mktime(0, 0, 0, date('m'), $yesterday, date('Y'));
        $yesend = mktime(23, 59, 59, date('m'), $yesterday, date('Y'));
        //昨日支付金额
        $rushyesmoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename('wlmerchant_rush_order') . $condition . " AND paytime > {$yessta} AND paytime < {$yesend} ");
        if($storeid){
            $halfyesmoney = 0;
        }else {
            $halfyesmoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_halfcard_record') . $condition . " AND paytime > {$yessta} AND paytime < {$yesend} ");
        }
        $otheryesmoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_order') . $condition2 . " AND paytime > {$yessta} AND paytime < {$yesend} ");
        $data['yesmoney'] = sprintf("%.2f", $rushyesmoney + $halfyesmoney + $otheryesmoney);
        //昨日退款金额
        $rushrefyesmoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename('wlmerchant_rush_order') . $condition . " AND paytime > {$yessta} AND paytime < {$yesend} AND status = 7");
        if($storeid){
            $halfrefyesmoney = 0;
        }else {
            $halfrefyesmoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_halfcard_record') . $condition . " AND paytime > {$yessta} AND paytime < {$yesend} AND status = 7");
        }
        $otherrefyesmoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_order') . $condition2 . " AND paytime > {$yessta} AND paytime < {$yesend} AND status = 7");
        $data['refyesmoney'] = sprintf("%.2f", $rushrefyesmoney + $halfrefyesmoney + $otherrefyesmoney);
        if(!is_store()) {
            //昨日新增客户
            $data['yesnewmember'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_member') . $condition . " AND createtime > {$yessta} AND createtime < {$yesend}");
            //昨日支付客户
            $rushyespaymember = pdo_fetchall('select distinct mid from ' . tablename(PDO_NAME . 'rush_order') . $condition . " AND paytime > {$yessta} AND paytime < {$yesend}");
            $rushyespaymember = count($rushyespaymember);
            $halfyespaymember = pdo_fetchall('select distinct mid from ' . tablename(PDO_NAME . 'halfcard_record') . $condition . " AND paytime > {$yessta} AND paytime < {$yesend}");
            $halfyespaymember = count($halfyespaymember);
            $otheryespaymember = pdo_fetchall('select distinct mid from ' . tablename(PDO_NAME . 'order') . $condition2 . " AND paytime > {$yessta} AND paytime < {$yesend}");
            $otheryespaymember = count($otheryespaymember);
            $data['yespaymember'] = $rushyespaymember + $halfyespaymember + $otheryespaymember;
            //昨日新增商户
            $data['yesnewmerchant'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_merchantdata') . $condition . " AND createtime > {$yessta} AND createtime < {$yesend} AND status = 2");
            //昨日付费入驻
            $data['yesnewcharge'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_order') . $condition2 . " AND paytime > {$yessta} AND paytime < {$yesend} AND plugin = 'store'");
        }
        //昨日新增订单
        $yesnewrushorder = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_rush_order') . $condition . " AND createtime > {$yessta} AND createtime < {$yesend}");
        if($storeid){
            $yesnewhalforder = 0;
        }else {
            $yesnewhalforder = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_halfcard_record') . $condition . " AND createtime > {$yessta} AND createtime < {$yesend}");
        }
        $yesnewotherorder = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_order') . $condition2 . " AND createtime > {$yessta} AND createtime < {$yesend}");
        $data['yesneworder'] = $yesnewrushorder + $yesnewhalforder + $yesnewotherorder;
        //昨日新增支付订单
        $yespaynewrushorder = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_rush_order') . $condition . " AND paytime > {$yessta} AND paytime < {$yesend}");
        if($storeid){
            $yespaynewhalforder = 0;
        }else {
            $yespaynewhalforder = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_halfcard_record') . $condition . " AND paytime > {$yessta} AND paytime < {$yesend}");
        }
        $yespaynewotherorder = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_order') . $condition2 . " AND paytime > {$yessta} AND paytime < {$yesend}");
        $data['yesnewpayorder'] = $yespaynewrushorder + $yespaynewhalforder + $yespaynewotherorder;
        foreach ($data as $key => &$va) {
            if (empty($va)) {
                $va = 0;
            }
        }
        for ($i = 29; $i > 0; $i--) {
            $testday = date('d') - $i;
            $teststa = mktime(0, 0, 0, date('m'), $testday, date('Y'));
            $testend = mktime(23, 59, 59, date('m'), $testday, date('Y'));
            $rushyesmoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename('wlmerchant_rush_order') . $condition . " AND paytime > {$teststa} AND paytime < {$testend} ");
            if($storeid){
                $halfyesmoney = 0;
            }else {
                $halfyesmoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_halfcard_record') . $condition . " AND paytime > {$teststa} AND paytime < {$testend} ");
            }
            $otheryesmoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_order') . $condition2 . " AND paytime > {$teststa} AND paytime < {$testend} ");
            $date = date('m-d', $testend);
            $sales = sprintf("%.2f", $rushyesmoney + $halfyesmoney + $otheryesmoney);
            $li = array(
                'year' => $date,
                '金额'   => (float)$sales
            );
            $list[] = $li;
        }
        $data['list'] = $list;
        $data['time'] = time();
        return $data;
    }


    static function newSurvey($aid,$storeid = 0) {
        global $_W;
        $condition = "  WHERE uniacid = {$_W['uniacid']}";
        if($storeid){
            $condition .= " AND sid = {$storeid}";
        }
        if ($aid) {
            $condition .= " AND aid = {$aid}";
            if ($_W['wlsetting']['distribution']['seetstatus']) {
                $condition2 = $condition . " AND plugin != 'distribution' ";
            }
        }
        if (empty($condition2)) {
            $condition2 = $condition;
        }
        if($storeid){
            $condition2 .= " AND plugin != 'store'";
        }
        $todaytime = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        //总支付金额
        //抢购
        $rushallmoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename('wlmerchant_rush_order') . $condition . " AND paytime > {$todaytime}");
        //一卡通
        if($storeid){
            $halfallmoney = 0;
        }else{
            $halfallmoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_halfcard_record') . $condition . " AND paytime > {$todaytime}");
        }
        //其他
        $otherallmoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_order') . $condition2 . " AND paytime > {$todaytime}");
        $data['allmoney'] = sprintf("%.2f", $otherallmoney + $halfallmoney + $rushallmoney);
        //退款总金额
        $rushrefmoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename('wlmerchant_rush_order') . $condition . " AND paytime > {$todaytime} AND status = 7");
        if($storeid){
            $halfrefmoney = 0;
        }else {
            $halfrefmoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_halfcard_record') . $condition . " AND paytime > {$todaytime} AND status = 7");
        }
        $otherrefmoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_order') . $condition2 . " AND paytime > {$todaytime} AND status = 7");
        $data['refmoney'] = sprintf("%.2f", $rushrefmoney + $halfrefmoney + $otherrefmoney);
        //新增客户
        $data['newmember'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_member') . $condition . " AND createtime > {$todaytime}");
        //支付客户
        $rushpaymember = pdo_fetchall('select distinct mid from ' . tablename(PDO_NAME . 'rush_order') . $condition . " AND paytime > {$todaytime}");
        $rushpaymember = count($rushpaymember);
        if($storeid){
            $halfpaymember = 0;
        }else {
            $halfpaymember = pdo_fetchall('select distinct mid from ' . tablename(PDO_NAME . 'halfcard_record') . $condition . " AND paytime > {$todaytime}");
            $halfpaymember = count($halfpaymember);
        }
        $otherpaymember = pdo_fetchall('select distinct mid from ' . tablename(PDO_NAME . 'order') . $condition2 . " AND paytime > {$todaytime}");
        $otherpaymember = count($otherpaymember);
        $data['paymember'] = $rushpaymember + $halfpaymember + $otherpaymember;
        if(empty($storeid)){
            //新增商户数
            $data['newmerchant'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_merchantdata') . $condition . " AND createtime > {$todaytime} AND status = 2");
            //付费入驻
            $data['newcharge'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_order') . $condition2 . " AND paytime > {$todaytime} AND plugin = 'store'");
        }
        //新增订单
        $newrushorder = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_rush_order') . $condition . " AND createtime > {$todaytime}");
        if($storeid){
            $newhalforder = 0;
        }else {
            $newhalforder = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_halfcard_record') . $condition . " AND createtime > {$todaytime}");
        }
        $newotherorder = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_order') . $condition2 . " AND createtime > {$todaytime}");
        $data['neworder'] = $newrushorder + $newhalforder + $newotherorder;
        //新增支付订单
        $newpayrushorder = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_rush_order') . $condition . " AND paytime > {$todaytime}");
        if($storeid){
            $newpayhalforder = 0;
        }else {
            $newpayhalforder = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_halfcard_record') . $condition . " AND paytime > {$todaytime}");
        }
        $newpayotherorder = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_order') . $condition2 . " AND paytime > {$todaytime}");
        $data['newpayorder'] = $newpayrushorder + $newpayhalforder + $newpayotherorder;
        //重要提醒
        if(empty($storeid)){
            //待发货订单数
            $dfhtotal1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "order") . $condition . " AND plugin != 'consumption' AND status = 8");
            $dfhtotal2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "rush_order") . $condition . " AND status = 8");
            $data['dfhorder'] = $dfhtotal1 + $dfhtotal2;
            //待退款订单数
            $dtktotal1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "order") . $condition . " AND status = 6");
            $dtktotal2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "rush_order") . $condition . " AND status = 6");
            $data['dtkorder'] = $dtktotal2 + $dtktotal1;
            //申请退款订单数
            $sqtktotal = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "aftersale") . $condition . " AND status = 1");
            $data['sqtkorder'] = $sqtktotal;
            //待审核商户 动态 评论
            $getStatus = " SELECT status FROM ".tablename(PDO_NAME."merchantdata")." as b where b.id = a.storeid ";
            $data['merchantnum'] = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename(PDO_NAME . "merchantuser")
                                                   ." as a ". $condition
                                                   . " AND status = 1 AND ({$getStatus}) = 1 ");
            $data['dynamicnum'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "store_dynamic") . $condition . " AND status = 0");
            $data['commentnum'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "comment") . $condition . " AND checkone = 1");
            //待审核的提现 商户 代理 分销
            $data['storeapply'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "settlement_record") . $condition . " AND status = 2 AND type = 1");
            $data['agentapply'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "settlement_record") . $condition . " AND status = 2 AND type = 2");
            //$data['disapply'] = $condition;//pdo_fetchcolumn('SELECT COUNT(id) FROM '.tablename(PDO_NAME."disapply").$condition." AND status = 2");
            $data['disapply'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "settlement_record") . $condition . " AND status IN (6,7) AND type = 3");
            //其他待审核信息
            $data['pocketnum'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "pocket_informations") . $condition . " AND status = 1");
            $data['disnum'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "applydistributor") . $condition . " AND status = 0");
            $data['aattnum'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "attestation_list") . $condition . " AND checkstatus = 1 AND type = 2");
            $data['uattnum'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "attestation_list") . $condition . " AND checkstatus = 1");

            $data['rushnum'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "rush_activity") . $condition . " AND status = 5");
            $data['grouponnum'] = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . "groupon_activity") . $condition . " AND status = 5");
        }


        foreach ($data as $key => &$va) {
            if (empty($va)) {
                $va = 0;
            }
        }
        //统计图
        //最近七天
        $sevenday = date('d') - 6;
        $sevensta = mktime(0, 0, 0, date('m'), $sevenday, date('Y'));
        $sevenend = time();
        $rushsevenmoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename('wlmerchant_rush_order') . $condition . " AND paytime > {$sevensta} AND paytime < {$sevenend} ");
        if($storeid){
            $halfsevenmoney = 0;
        }else {
            $halfsevenmoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_halfcard_record') . $condition . " AND paytime > {$sevensta} AND paytime < {$sevenend} ");
        }
        $othersevenmoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_order') . $condition2 . " AND paytime > {$sevensta} AND paytime < {$sevenend} ");
        $data['sevenmoney'] = sprintf("%.2f", $rushsevenmoney + $halfsevenmoney + $othersevenmoney);

        //最近30天
        $threeday = date('d') - 29;
        $threesta = mktime(0, 0, 0, date('m'), $threeday, date('Y'));
        $threeend = time();
        $rushthreemoney = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename('wlmerchant_rush_order') . $condition . " AND paytime > {$threesta} AND paytime < {$threeend} ");
        if($storeid){
            $halfthreemoney = 0;
        }else {
            $halfthreemoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_halfcard_record') . $condition . " AND paytime > {$threesta} AND paytime < {$threeend} ");
        }
        $otherthreemoney = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_order') . $condition2 . " AND paytime > {$threesta} AND paytime < {$threeend} ");
        $data['threemoney'] = sprintf("%.2f", $rushthreemoney + $halfthreemoney + $otherthreemoney);
        return $data;
    }
}
