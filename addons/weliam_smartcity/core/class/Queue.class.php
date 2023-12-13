<?php
defined('IN_IA') or exit('Access Denied');

class Queue {
    private $islock = array('value' => 0, 'expire' => 0);
    private $expiretime = 900; //锁过期时间，秒

    //初始赋值
    public function __construct() {
        $lock = cache_read('queuelockfirstv4');
        if (!empty($lock)) $this->islock = $lock;
    }

    //加锁
    private function setLock() {
        $array = array('value' => 1, 'expire' => time());
        cache_write('queuelockfirstv4', $array);
        cache_write(MODULE_NAME . ':task:status', $array);
        $this->islock = $array;
    }

    //删除锁
    public function deleteLock() {
        cache_delete('queuelockfirstv4');
        $this->islock = array('value' => 0, 'expire' => time());
        return true;
    }

    //检查是否锁定
    public function checkLock() {
        $lock = $this->islock;
        if ($lock['value'] == 1 && $lock['expire'] < (time() - $this->expiretime)) { //过期了，删除锁
            $this->deleteLock();
            return false;
        }
        if (empty($lock['value'])) {
            return false;
        } else {
            return true;
        }
    }

    public function queueMain($on = '', $ex = '') {
        global $_W;
        if ($this->checkLock()) {
            die('LOCK'); //锁定的时候直接返回
            //$this->deleteLock();
        } else {
            $this->setLock(); //没锁的话锁定
        }
        $this->doTask();
        $plugins = App::getPlugins(3);
        foreach ($plugins as $plu) {
            if ($plu['setting']['task'] == 'true') {
                $class_name = ucfirst($plu['ident']);
                if ($class_name == 'Wlcoupon') {
                    wlCoupon::doTask();
                } else {
                    if (method_exists($class_name, 'doTask')) {
                        @$class_name::doTask();
                    }
                }
            }
        }
        //执行完删除锁
        $this->deleteLock();
        die('TRUE');
    }

    //增加待发消息
    public function addTask($key, $value, $dotime, $important) {
        global $_W;
        $flag = pdo_get('wlmerchant_waittask', array('key' => $key,'value' => $value,'important' => $important), array('id'));
        if (empty($flag)) {
            if (empty($_W['uniacid'])) {
                if ($key == 1) {
                    $_W['uniacid'] = pdo_getcolumn(PDO_NAME . 'rush_order', array('id' => $important), 'uniacid');
                } else if ($key == 2) {
                    $_W['uniacid'] = pdo_getcolumn(PDO_NAME . 'order', array('id' => $important), 'uniacid');
                } else if ($key == 3) {
                    $_W['uniacid'] = pdo_getcolumn(PDO_NAME . 'disorder', array('id' => $important), 'uniacid');
                }
            }
            $data = array(
                'uniacid'    => $_W['uniacid'],
                'key'        => $key,
                'value'      => $value,
                'status'     => 0,
                'createtime' => time(),
                'dotime'     => $dotime,
                'important'  => $important
            );
            $res = pdo_insert('wlmerchant_waittask', $data);
            return $res;
        }
    }

    //删除消息队列
    public function finishTask($id) {
        global $_W;
        pdo_update('wlmerchant_waittask', array('status' => 1, 'finishtime' => time()), array('id' => $id));
    }

    public function laterTask($id) {
        $time = time() + 600;
        pdo_update('wlmerchant_waittask', array('dotime' => $time), array('id' => $id));
    }

    public function getTaskWhere($flag = 0){
        $sets = Cloud::wl_syssetting_read('taskcover');
        if($flag){
            if($sets['passiveid']){
                $passiveid = unserialize($sets['passiveid']);
                $where = $passiveid;
            }else{
                $where = [];
            }
        }else{
            if($sets['passiveid']){
                $passiveid = unserialize($sets['passiveid']);
                $where = ' AND uniacid IN (';
                foreach ($passiveid as $key => $v) {
                    if ($key == 0) {
                        $where .= $v;
                    } else {
                        $where .= "," . $v;
                    }
                }
                $where .= ')';
            }else{
                $where = '';
            }
        }
        return $where;
    }

    //查询需要发消息的记录
    public function getNeedTaskItem() {
        global $_W;
        $nowtime = time();
        $where = self::getTaskWhere(0);
        return pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_waittask') . " WHERE status = 0 AND dotime < {$nowtime}  {$where}  ORDER BY `dotime` ASC  LIMIT 10");
    }


    //执行等待中任务
    public function doTask() {
        global $_W;
        set_time_limit(0); //解除超时限制
        $nowtime = time();
        $message = self::getNeedTaskItem();
        if ($message) {
            foreach ($message as $k => $v) {
                $_W['uniacid'] = $v['uniacid'];
                $data = unserialize($v['value']);
                $res = 0;
                if ($v['key'] == 1) {  //结算抢购订单
                    $res = Store::rushsettlement($v['important']);
                }
                if ($v['key'] == 2) {  //结算通用订单
                    $res = Store::ordersettlement($v['important']);
                }
                if ($v['key'] == 3) {  //结算分销订单
                    $res = Distribution::dissettlement($v['important']);
                }
                if ($v['key'] == 4) {  //自动打款
                    $cash = pdo_get(PDO_NAME . 'settlement_record', array('id' => $v['important']));
                    if($cash['status'] != 3){
                        $res = 1;
                        file_put_contents(PATH_DATA . "autoCashError.log", var_export($cash, true) . PHP_EOL, FILE_APPEND);
                    }else{
                        if ($cash['payment_type'] == 2) {
                            if ($cash['sopenid']) {
                                $_W['account'] = uni_fetch($_W['uniacid']);
                                $realname = pdo_getcolumn(PDO_NAME . 'member', array('openid' => $cash['sopenid']), 'realname');
                                //$result2 = wlPay::finance($cash['sopenid'], $cash['sgetmoney'], '提现自动打款', $realname, $cash['trade_no']);
                                switch ($cash['type']){
                                    case 1:
                                        //$userName = pdo_getcolumn(PDO_NAME."merchantdata",['id'=>$cash['sid']],'storename');
                                        $cash['mid'] = pdo_getcolumn(PDO_NAME . 'merchantuser', array('storeid' => $cash['sid'],'ismain' => 1), 'mid');
                                        $rem = '自动打款-商家提现(sid:'.$cash['sid'].')';
                                        break;//商家提现申请
                                    case 2:
                                        //$userName = pdo_getcolumn(PDO_NAME."agentusers",['id'=>$cash['aid']],'agentname');
                                        $rem = '自动打款-代理商提现(aid:'.$cash['aid'].')';
                                        $cash['mid'] = pdo_getcolumn(PDO_NAME . 'member', array('openid' => $cash['sopenid']), 'id');
                                        break;//代理提现申请
                                    case 3:
                                        //$userName = pdo_getcolumn(PDO_NAME."distributor",['mid'=>$cash['mid']],'nickname');
                                        $trade = Setting::wlsetting_read('trade');
                                        $fxstext = $trade['fxstext'] ? : '分销商';
                                        $rem = '自动打款-'.$fxstext.'提现:mid'.$cash['mid'];
                                        break;//分销商申请提现
                                    case 4:
                                        //$userName = pdo_getcolumn(PDO_NAME."member",['id'=>$cash['mid']],'nickname');
                                        $rem = '自动打款-用户提现:mid'.$cash['mid'];
                                        break;//用户余额提现
                                }
                                //请求进行微信打款操作
                                $params = [
                                    'openid'   => $cash['sopenid'] ,
                                    'money'    => $cash['sgetmoney'] ,
                                    'rem'      => $rem ,
                                    'name'     => $realname ,
                                    'order_no' => $cash['trade_no'],
                                    'source'   => $cash['source'] ? : 1,
                                    'mid'      => $cash['mid'],
                                    'return'   => 1,//代表不返回任何信息
                                ];
                                $result2 = Payment::presentationInit($params,1);
                                //结算操作
                                if ($result2) {
                                    if($cash['type'] == 1){
                                        $data['status'] = 5;
                                    }else if($cash['type'] == 2){
                                        $data['status'] = 4;
                                    }else if($cash['type'] == 3){
                                        $data['status'] = 9;
                                    }
                                    $data['updatetime'] = TIMESTAMP;
                                    $data['settletype'] = 2;
                                    $res = pdo_update(PDO_NAME.'settlement_record',$data,array('id' => $cash['id']));
                                    if($res){
                                        if($cash['type'] == 3){
                                            $url = h5_url('pages/subPages/dealer/withdraw/withdrawrecord',['draw_id'=>$cash['id']]);
                                            Distribution::distriNotice($cash['mid'],$url,6,0,$cash['sapplymoney'],'微信零钱自动打款');
                                        }else{
                                            $first = '您的提现申请打款';
                                            $type = '提现申请';
                                            $status = '已打款';
                                            $content = '到账金额:￥'.$cash['sgetmoney'];
                                            $remark = '谢谢您对平台的支持';
                                            News::jobNotice($cash['mid'],$first,$type,$content,$status,$remark,time());
                                            if($cash['type'] == 1){
                                                News::addSysNotice($cash['uniacid'],3,$cash['sid'],0,$cash['id'],1);
                                            }
                                        }
                                    }
                                }
                            }
                        } else if ($cash['payment_type'] == 4) {
                            if ($cash['mid']) {
                                $result = Member::credit_update_credit2($cash['mid'], $cash['sgetmoney'], '分销商余额提现自动打款', 0);
                                //结算操作
                                if ($result) {
                                    $res = pdo_update(PDO_NAME . 'settlement_record', array('status' => 9, 'updatetime' => TIMESTAMP, 'settletype' => 4), array('id' => $cash['id']));
                                    if($res){
                                        $url = h5_url('pages/subPages/dealer/withdraw/withdrawrecord',['draw_id'=>$cash['id']]);
                                        Distribution::distriNotice($cash['mid'],$url,6,0,$cash['sapplymoney'],'用户余额自动打款');
                                    }
                                }
                            }
                        } else if ($cash['payment_type'] == 1){
                            if($cash['mid'] > 0){
                                $cashmamber = pdo_get(PDO_NAME.'member',array('id'=>$cash['mid']),['realname','alipay']);
                            }else if($cash['sid'] > 0){
                                $cashmamber = Store::getShopOwnerInfo($cash['sid'],$cash['aid']);
                                $cash['mid'] = $cashmamber['mid'];
                            }else if($cash['aid'] > 0){
                                $agentinfo = pdo_get(PDO_NAME.'agentusers',array('id'=>$cash['aid']),['cashopenid','alipay']);
                                $cashmamber['alipay'] = $agentinfo['alipay'];
                                $cashmamber['realname'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$agentinfo['cashopenid']),'realname');
                                $cash['mid'] = pdo_getcolumn(PDO_NAME . 'member', array('openid' => $cash['sopenid']), 'id');
                            }
                            //请求进行微信打款操作
                            $params = [
                                'money'    => $cash['sgetmoney'] ,
                                'order_no' => $cash['trade_no'],
                                'phone'    => $cashmamber['alipay'],
                                'source'   => $cash['source'] ? : 1,
                                'mid'      => $cash['mid'],
                                'realname' => $cashmamber['realname'],
                                'return'   => 1,//代表不返回任何信息
                            ];
                            $alires = Payment::presentationInit($params,3);
                            Util::wl_log('cash_ali_record', PATH_DATA . "cash/data/", $alires); //写入异步日志记录
                            //结算操作
                            if ($alires) {
                                if($cash['type'] == 1){
                                    $data['status'] = 5;
                                }else if($cash['type'] == 2){
                                    $data['status'] = 4;
                                }else if($cash['type'] == 3){
                                    $data['status'] = 9;
                                }
                                $data['updatetime'] = TIMESTAMP;
                                $data['settletype'] = 6;
                                $res = pdo_update(PDO_NAME.'settlement_record',$data,array('id' => $cash['id']));
                                if($res){
                                    if($cash['type'] == 3){
                                        $url = h5_url('pages/subPages/dealer/withdraw/withdrawrecord',['draw_id'=>$cash['id']]);
                                        Distribution::distriNotice($cash['mid'],$url,6,0,$cash['sapplymoney'],'支付宝自动转账');
                                    }else{
                                        $first = '您的提现申请打款';
                                        $type = '提现申请';
                                        $status = '已打款';
                                        $content = '到账金额:￥'.$cash['sgetmoney'];
                                        $remark = '谢谢您对平台的支持';
                                        News::jobNotice($cash['mid'],$first,$type,$content,$status,$remark,time());
                                        if($cash['type'] == 1){
                                            News::addSysNotice($cash['uniacid'],3,$cash['sid'],0,$cash['id'],1);
                                        }
                                    }
                                }
                            }

                        }
                    }
                }
                if ($v['key'] == 5) {  //自动退款
                    $after = pdo_get('wlmerchant_aftersale',array('id' => $v['important']),array('id','orderid','uniacid','status','plugin','checkcodes'));
                    $_W['uniacid'] = $after['uniacid'];
                    if(!empty($after)){
                        if($after['status'] == 1){
                            $checkcodes = unserialize($after['checkcodes']);
                            if(!empty($checkcodes[0])){
                                $money = pdo_getcolumn('wlmerchant_smallorder',array('checkcode' => $checkcodes),array("SUM(orderprice)"));
                                if ($after['plugin'] == 'wlfightgroup') {
                                    $res = Wlfightgroup::refund($after['orderid'],$money,0,0,$after['id']);
                                } else if ($after['plugin'] == 'coupon') {
                                    $res = wlCoupon::refund($after['orderid'],$money, 0, 0, $after['id']);
                                } else if ($after['plugin'] == 'groupon') {
                                    $res = Groupon::refund($after['orderid'], $money,0, 0, $after['id']);
                                } else if ($after['plugin'] == 'bargain') {
                                    $res = Bargain::refund($after['orderid'], $money,0);
                                } else if ($after['plugin'] == 'rush') {
                                    $res = Rush::refund($after['orderid'], $money,0, 0, $after['id']);
                                }
                            }else{
                                if ($after['plugin'] == 'wlfightgroup') {
                                    $res = Wlfightgroup::refund($after['orderid'], 0, 0);
                                } else if ($after['plugin'] == 'coupon') {
                                    $res = wlCoupon::refund($after['orderid'], 0, 0);
                                } else if ($after['plugin'] == 'groupon') {
                                    $res = Groupon::refund($after['orderid'], 0, 0);
                                } else if ($after['plugin'] == 'bargain') {
                                    $res = Bargain::refund($after['orderid'], 0, 0);
                                } else if ($after['plugin'] == 'rush') {
                                    $res = Rush::refund($after['orderid'], 0, 0);
                                } else if ($after['plugin'] == 'housekeep') {
                                    $res = Housekeep::refund($after['orderid'], 0, 0);
                                }
                            }
                            //修改售后记录
                            if($res['status']){
                                $refundtype = '自动根据支付方式原路退款';
                                $journal = array(
                                    'time' => time(),
                                    'title' => '到账成功',
                                    'detail' => '商家已退款:'.$refundtype,
                                );
                                $journals = Order::addjournal($journal,$v['important']);
                                pdo_update('wlmerchant_aftersale',array('dotime' => time(),'status'=>2,'journal'=>$journals),array('id' =>$v['important']));
                                pdo_update('wlmerchant_smallorder',array('status' => 3, 'refundtime' => time()),array('checkcode' =>$checkcodes,'status'=> array(1,4)));
                            }
                            $res = $res['status'];
                        }else{
                            $res = 1;
                        }
                    }else{
                        $res = 1;
                    }
                }
                if ($v['key'] == 6) {  //自动收货
                    if ($data['type'] == 'order') {
                        $order = pdo_get('wlmerchant_order', array('id' => $v['important']));
                        if ($order['expressid'] && $order['status'] == 4) {
                            $res = Order::sureReceive($order['id'],$order['plugin']);
                        } else {
                            $res = 1;
                        }
                    } else if ($data['type'] == 'rush') {
                        $order = pdo_get('wlmerchant_rush_order', array('id' => $v['important']));
                        if ($order['expressid'] && $order['status'] == 4) {
                            $res = Order::sureReceive($order['id'],'rush');
                        } else {
                            $res = 1;
                        }
                    } else if ($data['type'] == 'consumption') {
                        $order = pdo_get('wlmerchant_consumption_record', array('id' => $v['important']));
                        if ($order['expressid'] && $order['status'] == 2) {
                            pdo_update('wlmerchant_consumption_record', array('status' => 3), array('id' => $order['id']));
                            pdo_update('wlmerchant_express', array('receivetime' => time()), array('id' => $order['expressid']));
                            $order['disorderid'] = pdo_getcolumn(PDO_NAME . 'order', array('id' => $order['orderid']), 'disorderid');
                            if ($order['disorderid']) {
                                $disres = pdo_update('wlmerchant_disorder', array('status' => 1), array('status' => 0, 'id' => $order['disorderid']));
                                if ($disres) {
                                    //添加结算分销订单到计划任务
                                    $distask = array(
                                        'type'    => 'consumption',
                                        'orderid' => $order['disorderid']
                                    );
                                    $distask = serialize($distask);
                                    Queue::addTask(3, $distask, time(), $order['disorderid']);
                                }
                            }
                        } else {
                            $res = 1;
                        }
                    }
                }
                if($v['key'] == 7){  //分账
                    $task = unserialize($v['value']);
                    $weixin = NEW WeixinPay();
                    $allres = $weixin->allocationPro($v['important'] , $task['type'] , $task['source'] ,unserialize($task['salesarray']), $task['salesmoney']);
                    if (is_array($allres)) {
                        pdo_update('wlmerchant_autosettlement_record' , [
                            'sysmoney'       => $allres['sysmoney'] ,
                            'agentmoney'     => $allres['agentmoney'] ,
                            'allocationtype' => 1
                        ] , ['id' => $task['settlementid']]);
                        if($task['disorderid'] > 0){
                            pdo_update('wlmerchant_disorder',array('status' => 2),array('id' => $task['disorderid']));
                        }
                        $res = 1;
                    }
                }
                if($v['key'] == 8){ //延迟发送模板消息
                    Distribution::distriNotice($data['mid'], $data['url'], 2, $data['disid']);
                    $res = 1;
                }
                if($v['key'] == 9){ //票付通提交订单
                    if($data['type'] == 'rush'){
                        $orderinfo = pdo_get('wlmerchant_rush_order',array('id' => $data['orderid']),['pftinfo','orderno','remark']);
                        $pftinfo = unserialize($orderinfo['pftinfo']);
                        if(empty($pftinfo['ordername'])){
                            $pftinfo['ordername'] = '系统代购';
                        }
                        $pftinfo['remotenum'] = $orderinfo['orderno'];
                        $pftinfo['memo'] = $orderinfo['remark'];
                        $pftorderinfo = Pftapimod::pftOrderSubmit($pftinfo);
                        if(empty($pftorderinfo['UUerrorcode']) || $pftorderinfo['UUerrorcode'] == '1075'){
                            $pftorderinfo = serialize($pftorderinfo);
                            $res = pdo_update(PDO_NAME . 'rush_order', ['pftorderinfo' => $pftorderinfo], array('id' => $data['orderid'])); //更新订单状态
                            //订单信息查询
                            $moreinfo = Pftapimod::pftOrderQuery($pftorderinfo['UUordernum']);
                            if(empty($moreinfo['UUerrorcode'])){
                                $pftchangeinfo = [
                                    'estimatetime' => strtotime($moreinfo['UUendtime']),
                                ];
                                pdo_update(PDO_NAME . 'rush_order',$pftchangeinfo, array('id' => $data['orderid'])); //更新订单状态
                            }
                        }else{
                            Util::wl_log('pftNewError.log',PATH_DATA,$pftorderinfo); //写入异步日志记录
                        }
                    }else{
                        $orderinfo = pdo_get('wlmerchant_order',array('id' => $data['orderid']),['pftinfo','orderno','buyremark']);
                        $pftinfo = unserialize($orderinfo['pftinfo']);
                        if(empty($pftinfo['ordername'])){
                            $pftinfo['ordername'] = '系统代购';
                        }
                        $pftinfo['remotenum'] = $orderinfo['orderno'];
                        $pftinfo['memo'] = $orderinfo['buyremark'];
                        $pftorderinfo = Pftapimod::pftOrderSubmit($pftinfo);
                        if(empty($pftorderinfo['UUerrorcode']) || $pftorderinfo['UUerrorcode'] == '1075'){
                            $pftorderinfo = serialize($pftorderinfo);
                            $res = pdo_update(PDO_NAME . 'order', ['pftorderinfo' => $pftorderinfo], array('id' => $data['orderid'])); //更新订单状态
                            //订单信息查询
                            $moreinfo = Pftapimod::pftOrderQuery($pftorderinfo['UUordernum']);
                            if(empty($moreinfo['UUerrorcode'])){
                                $pftchangeinfo = [
                                    'estimatetime' => strtotime($moreinfo['UUendtime']),
                                ];
                                pdo_update(PDO_NAME . 'order',$pftchangeinfo, array('id' => $data['orderid'])); //更新订单状态
                            }
                        }else{
                            Util::wl_log('pftNewError.log',PATH_DATA,$pftorderinfo); //写入异步日志记录
                        }
                    }
                }
                if($v['key'] == 10){ //卡密结算
                    $smallorders = pdo_getall('wlmerchant_smallorder',array('status' => 1,'plugin' => $data['plugin'],'orderid' => $v['important']),array('id'));
                    if(!empty($smallorders)){
                        foreach ($smallorders as $sor){
                            $res = Order::finishSmallorder($sor['id'],0,5);
                        }
                    }
                }
                if($v['key'] == 11){ //话费充值提交订单
                    $order_out = pdo_fetch("select * from" . tablename(PDO_NAME . 'mrecharge_order') . "where id='{$v['important']}'");
                    if($order_out['channel'] == 1){
                        $res = Mobilerecharge::sljOrderSubmit($order_out);
                        $res = $res['error'] ? 0 : 1;
                    }
                }
                if ($res) {
                    self::finishTask($v['id']); //完成已发的
                } else {
                    self::laterTask($v['id']); //推迟失败的
                }
            }
        }

        //删除未支付的积分兑换订单
        pdo_delete(PDO_NAME . "order", array('createtime <' => strtotime(date('Ymd')), 'plugin' => 'consumption', 'status' => 0));
        //过期商户
        $overmerchants = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_merchantdata') . "WHERE endtime < {$nowtime} AND enabled = 1 ORDER BY id DESC");
        if ($overmerchants) {
            foreach ($overmerchants as $key => $over) {
                $res = pdo_update(PDO_NAME . 'merchantdata', array('enabled' => 3), array('id' => $over['id']));
                if ($res) {  //下架商品
                    //抢购商品
                    pdo_update('wlmerchant_rush_activity', array('status' => 4), array('sid' => $over['id']));
                    //拼团商品
                    pdo_update('wlmerchant_fightgroup_goods', array('status' => 0), array('merchantid' => $over['id']));
                    //卡券
                    pdo_update('wlmerchant_couponlist', array('status' => 0), array('merchantid' => $over['id']));
                    //特权
                    pdo_update('wlmerchant_halfcardlist', array('status' => 0), array('merchantid' => $over['id']));
                    //礼包
                    pdo_update('wlmerchant_package', array('status' => 0), array('merchantid' => $over['id']));
                    //砍价
                    pdo_update('wlmerchant_bargain_activity', array('status' => 0), array('sid' => $over['id']));
                    //同城配送
                    pdo_update('wlmerchant_delivery_activity', array('status' => 4), array('sid' => $over['id']));
                }
            }
        }
        //兼容之前的未结算订单
        //抢购
        $rushorder = pdo_fetchall("SELECT id,uniacid FROM " . tablename('wlmerchant_rush_order') . "WHERE status IN (2,3) AND issettlement = 0 AND neworderflag = 0 ORDER BY id ASC limit 10");
        if ($rushorder) {
            foreach ($rushorder as $key => $rush) {
                $flag = pdo_get('wlmerchant_waittask', array('key' => 1, 'important' => $rush['id']), array('id'));
                if (empty($flag)) {
                    $rushtask = array(
                        'type'    => 'rush',
                        'orderid' => $rush['id']
                    );
                    $rushtask = serialize($rushtask);
                    $_W['uniacid'] = $rush['uniacid'];
                    Queue::addTask(1, $rushtask, time(), $rush['id']);
                }
            }
        }
        //其他
        $otherorder = pdo_fetchall("SELECT id,plugin,uniacid FROM " . tablename('wlmerchant_order') . "WHERE status IN (2,3) AND issettlement = 0 AND neworderflag = 0 AND plugin != 'consumption' ORDER BY id ASC limit 10");
        if ($otherorder) {
            foreach ($otherorder as $key => $order) {
                $flag = pdo_get('wlmerchant_waittask', array('key' => 2, 'important' => $order['id']), array('id'));
                if (empty($flag)) {
                    $rushtask = array(
                        'type'    => $order['plugin'],
                        'orderid' => $order['id']
                    );
                    $rushtask = serialize($rushtask);
                    $_W['uniacid'] = $order['uniacid'];
                    Queue::addTask(2, $rushtask, time(), $order['id']);
                }
            }
        }
        //分销订单
        $disorders = pdo_fetchall("SELECT id,plugin,uniacid FROM " . tablename('wlmerchant_disorder') . "WHERE status = 1 AND neworderflag = 0 ORDER BY id ASC limit 10");
        if ($disorders) {
            foreach ($disorders as $key => $disorder) {
                $flag = pdo_get('wlmerchant_waittask', array('key' => 3, 'important' => $disorder['id']), array('id'));
                if (empty($flag)) {
                    $rushtask = array(
                        'type'    => $disorder['plugin'],
                        'orderid' => $disorder['id']
                    );
                    $rushtask = serialize($rushtask);
                    $_W['uniacid'] = $disorder['uniacid'];
                    Queue::addTask(3, $rushtask, time(), $disorder['id']);
                } else {
                    $detail = pdo_get('wlmerchant_disdetail', array('disorderid' => $disorder['id']), array('id'));
                    if (!empty($detail)) {
                        pdo_update('wlmerchant_disorder', array('status' => 2), array('id' => $disorder['id']));
                    }
                }
            }
        }
        //修改明细表uniacid数据
        $details = pdo_getall('wlmerchant_disdetail', array('uniacid' => 0), array('id', 'disorderid'));
        if ($details) {
            foreach ($details as $key => $va) {
                $uniacid = pdo_getcolumn('wlmerchant_disorder', array('id' => $va['disorderid']), 'uniacid');
                pdo_update('wlmerchant_disdetail', array('uniacid' => $uniacid), array('id' => $va['id']));
            }
        }
        //自动取消订单与删除已取消订单
        //删除一个月之前所有已取消的订单
        $montime = time() - 365 * 24 * 3600;
        pdo_delete(PDO_NAME . "rush_order", array('createtime <' => $montime, 'status' => 5));
        pdo_delete(PDO_NAME . "order", array('createtime <' => $montime, 'status' => 5));
        pdo_delete(PDO_NAME . "halfcard_record", array('createtime <' => $montime, 'status' => 0));

        /*自动取消订单*/
        $onwtime = time();
        $rushorderdata = pdo_fetchall("select id from" . tablename(PDO_NAME . "rush_order") . "where status = 0 and canceltime > 0 and canceltime < {$onwtime}");
        if (!empty($rushorderdata)) {
            foreach ($rushorderdata as $k => $v) {
                Rush::cancelorder($v['id']);
            }
        }

        $orderdata = pdo_fetchall("select id,plugin,redpackid,usecredit,mid from" . tablename(PDO_NAME . "order") . "where status = 0 and canceltime < '{$onwtime}' and canceltime > 0");
        if (!empty($orderdata)) {
            foreach ($orderdata as $k => $v) {
                if($v['plugin'] == 'groupon'){
                    Groupon::cancelorder($v['id']);
                }else if($v['plugin'] == 'bargain'){
                    Bargain::cancelorder($v['id']);
                }else if($v['plugin'] == 'wlfightgroup'){
                    Wlfightgroup::cancelorder($v['id']);
                }else if($v['plugin'] == 'coupon'){
                    wlCoupon::cancelorder($v['id']);
                }else{
                    pdo_query('UPDATE ' . tablename(PDO_NAME . 'order') . " SET `status` = 5 WHERE id = {$v['id']}");
                    if($v['redpackid']){
                        pdo_update('wlmerchant_redpack_records',['status' => 0],['id' => $v['redpackid']]);
                    }
                    if($v['usecredit'] > 0){
                        if($v['plugin'] == 'halfcard'){
                            $pluginname = '在线买单';
                        }
                        Member::credit_update_credit1($v['mid'], $v['usecredit'], '取消'.$pluginname.'订单返还积分');
                    }
                }
            }
        }

        //即将过期订单通知
        $remindrushorder = pdo_fetchall("select id,activityid,mid,uniacid,aid,estimatetime from" . tablename(PDO_NAME . "rush_order") . "where status = 1 and remindtime < {$onwtime} and remindtime > 0 and cutoffnotice = 0 ");
        if (!empty($remindrushorder)) {
            foreach ($remindrushorder as $k => $v) {
                $goodsname = pdo_getcolumn(PDO_NAME . 'rush_activity', array('id' => $v['activityid']), 'name');
                $first = '您有一个抢购订单即将过期';
                $type = '订单即将过期提醒';
                $content = '商品名称:[' . $goodsname . ']';
                $status = '即将过期';
                $remark = '过期时间:' . date('Y-m-d H:i:s', $v['estimatetime']) . '点击去使用';
                $_W['uniacid'] = $v['uniacid'];
                $_W['source'] = 1;
                $_W['account'] = uni_fetch($_W['uniacid']);
                $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails', ['orderid' => $v['id'], 'plugin' => 'rush'],'h5',$v['aid']);
                News::jobNotice($v['mid'], $first, $type, $content, $status, $remark, time(), $url);
                pdo_update('wlmerchant_rush_order', array('cutoffnotice' => 1), array('id' => $v['id']));
            }
        }

        $remindorder = pdo_fetchall("select id,fkid,mid,plugin,recordid,uniacid,estimatetime from" . tablename(PDO_NAME . "order") . "where status = 1 and remindtime < {$onwtime} and remindtime > 0 and cutoffnotice = 0 ");
        if (!empty($remindorder)) {
            foreach ($remindorder as $k => $v) {
                if(intval($_W['uniacid']) <= 0) $_W['uniacid'] = $v['uniacid'];//从新定义uniacid  兼容计划任务没有uniacid的问题
                if($v['plugin'] == 'activity'){
                    $activity = pdo_get('wlmerchant_activitylist',array('id' => $v['fkid']),array('title','activestarttime','activeendtime'));
                    $goodsname = $activity['title'];
                    $first = '您报名的一个活动即将开始';
                    $type = '活动即将开始提醒';
                    $content = '活动名称:[' . $goodsname . ']';
                    $status = '即将开始';
                    $remark = '活动时间:' . date('Y-m-d H:i', $activity['activestarttime']).'-'.date('Y-m-d H:i', $activity['activeendtime']).',点击去使用';
                }else{
                    switch ($v['plugin']) {
                        case 'groupon':
                            $goodsname = pdo_getcolumn(PDO_NAME . 'groupon_activity', array('id' => $v['fkid']), 'name');
                            $plugin = '团购';
                            break;
                        case 'wlfightgroup':
                            $goodsname = pdo_getcolumn(PDO_NAME . 'fightgroup_goods', array('id' => $v['fkid']), 'name');
                            $plugin = '拼团';
                            break;
                        case 'bargain':
                            $goodsname = pdo_getcolumn(PDO_NAME . 'bargain_activity', array('id' => $v['fkid']), 'name');
                            $plugin = '砍价';
                            break;
                        case 'coupon':
                            $goodsname = pdo_getcolumn(PDO_NAME . 'couponlist', array('id' => $v['fkid']), 'title');
                            $plugin = '卡券';
                            break;
                    }
                    $first = '您有一个' . $plugin . '订单即将过期';
                    $type = '订单即将过期提醒';
                    $content = '商品名称:[' . $goodsname . ']';
                    $status = '即将过期';
                    $remark = '过期时间:' . date('Y-m-d H:i:s', $v['estimatetime']) . '点击去使用';
                }
                $_W['uniacid'] = $v['uniacid'];
                $_W['source'] = 1;
                $_W['account'] = uni_fetch($_W['uniacid']);
                if ($v['plugin'] == 'coupon') {
                    $url = h5_url('pages/subPages/coupon/couponDetails/couponDetails', ['order_id' => $v['id'], 'id' => $v['recordid']]);
                } else {
                    $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails', ['orderid' => $v['id'], 'plugin' => $v['plugin']]);
                }
                News::jobNotice($v['mid'], $first, $type, $content, $status, $remark, time(), $url);
                pdo_update('wlmerchant_order', array('cutoffnotice' => 1), array('id' => $v['id']));
            }
        }

        //处理当日重复结算
        $todaytime = strtotime(date("Y-m-d"), time());
        $commentSql = "select checkcode,count(*) as count from" . tablename(PDO_NAME . "autosettlement_record") . "group by checkcode having count > 1 AND checkcode > 0 AND createtime > {$todaytime}";
        $comment = pdo_fetchall($commentSql);
        if (!empty($comment)) {
            foreach ($comment as $com) {
                $list = pdo_fetchall("SELECT * FROM " . tablename(PDO_NAME . 'autosettlement_record') . "WHERE checkcode = '{$com['checkcode']}' ORDER BY id DESC");
                $num = count($list) - 1;
                for ($i = 0; $i < $num; $i++) {
                    if ($list[$i]['merchantmoney'] > 0) {
                        pdo_fetch("update" . tablename('wlmerchant_merchantdata') . "SET allmoney=allmoney-{$list[$i]['merchantmoney']},nowmoney=nowmoney-{$list[$i]['merchantmoney']} WHERE id = {$list[$i]['merchantid']}");
                    }
                    if ($list[$i]['agentmoney'] > 0) {
                        pdo_fetch("update" . tablename('wlmerchant_agentusers') . "SET allmoney=allmoney+{$list[$i]['agentmoney']},nowmoney=nowmoney+{$list[$i]['agentmoney']} WHERE id = {$list[$i]['aid']}");
                    }
                    pdo_delete('wlmerchant_autosettlement_record', array('id' => $list[$i]['id']));
                }
            }
        }

        //处理用户头像
        $members = pdo_fetchall("SELECT id,avatar FROM ".tablename('wlmerchant_member')."WHERE avatar LIKE '%http://%' ORDER BY id DESC LIMIT 100");
        foreach ($members as &$mem){
            $newavatar = str_replace('http://','https://',$mem['avatar']);
            pdo_update('wlmerchant_member',array('avatar' => $newavatar),array('id' => $mem['id']));
        }

        //重置店铺经纬度
        $stores = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_merchantdata')."WHERE lng = 0  ORDER BY id DESC LIMIT 50");
        if(!empty($stores)){
            foreach ($stores as $st){
                $location = unserialize($st['location']);
                pdo_update('wlmerchant_merchantdata',array('lng' => $location['lng'],'lat' => $location['lat']),array('id' => $st['id']));
            }
        }
        //为评论添加gid信息
        $comments = pdo_fetchall("SELECT idoforder,id,plugin FROM ".tablename('wlmerchant_comment')."WHERE gid = 0  ORDER BY id DESC LIMIT 10");
        if(!empty($comments)){
            foreach ($comments as $com){
                if($com['plugin'] == 'rush'){
                    $gid = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$com['idoforder']),'activityid');
                }else if($com['plugin'] == 'noorder'){
                    $gid = -1;
                }else if($com['plugin'] == 'usehalf'){
                    $gid = pdo_getcolumn(PDO_NAME.'timecardrecord',array('id'=>$com['idoforder']),'activeid');
                }else{
                    $gid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$com['idoforder']),'fkid');
                }
                pdo_update('wlmerchant_comment',array('gid' => $gid),array('id' => $com['id']));
            }
        }

        //处理未结算的一卡通订单
        $halfpaylist = pdo_getall('wlmerchant_halfcard_record',array('status' => 1,'issettlement' => 0),array('id'));
        if(!empty($halfpaylist)){
            foreach ($halfpaylist as $half){
                Store::halfsettlement($half['id']);
            }
        }
        //转赠活动过期
        $overTransfer = pdo_get('wlmerchant_transfer_list',array('is_over' => 0 , 'createtime <' => time() - 86400,'surplus >' => 0),array('mid','surplus','money','id','uniacid'));
        if(!empty($overTransfer)){
            $_W['uniacid'] = $overTransfer['uniacid'];
            $price = sprintf("%.2f",$overTransfer['surplus'] * $overTransfer['money']);
            $res = Member::credit_update_credit2($overTransfer['mid'],$price,'转赠活动过期退回余额,活动编号:'.$overTransfer['id'],$overTransfer['id']);
            if(!is_error($res)){
                pdo_update('wlmerchant_transfer_list',array('is_over' => 1),array('id' => $overTransfer['id']));
            }
        }

        //同步没有商品数据的小订单
        $nogidsmallorders = pdo_fetchall("SELECT id,plugin,orderid FROM ".tablename('wlmerchant_smallorder')."WHERE gid = 0 ORDER BY id DESC LIMIT 500");
        if(!empty($nogidsmallorders)){
            foreach ($nogidsmallorders as $smallor){
                if($smallor['plugin'] == 'rush'){
                    $parentOrder = pdo_get('wlmerchant_rush_order',array('id' => $smallor['orderid']),array('activityid','optionid'));
                    if(empty($parentOrder)){
                        $gid = -1;
                    }else{
                        $gid = $parentOrder['activityid'];
                        $specid = $parentOrder['optionid'];
                    }
                }else{
                    $parentOrder = pdo_get('wlmerchant_order',array('id' => $smallor['orderid']),array('fkid','specid'));
                    if(empty($parentOrder)){
                        $gid = -1;
                    }else{
                        $gid = $parentOrder['fkid'];
                        $specid = $parentOrder['specid'];
                    }
                }
                pdo_update('wlmerchant_smallorder',array('gid' => $gid,'specid' => $specid),array('id' => $smallor['id']));
            }
        }
        //修正公众号id不正确的分销商
        $commentSql =  "SELECT a.id,a.mid,a.uniacid FROM ". tablename(PDO_NAME."distributor")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id WHERE
        CASE
             WHEN a.uniacid != b.uniacid THEN 1
             ELSE 0
         END = 1 ORDER BY id DESC";
        $comment = pdo_fetchall($commentSql);
        if(!empty($comment)){
            foreach ($comment as $com){
                $uniacid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$com['mid']),'uniacid');
                pdo_update(PDO_NAME."distributor",array('uniacid' => $uniacid),array('id' => $com['id']));
            }
        }

        //处理重复的分销商数据
        $commentSql2 = "select mid,count(*) as count from" . tablename(PDO_NAME . "distributor") . "group by mid having count > 1 AND mid > 0 LIMIT 20";
        $comment2 = pdo_fetchall($commentSql2);
        foreach ($comment2 as $com2){
            $member = pdo_get('wlmerchant_member',array('id' => $com2['mid']),array('distributorid'));
            pdo_delete('wlmerchant_distributor',array('mid'=>$com2['mid'],'id !=' => $member['distributorid'],'nowmoney <'=> '0.01'));
        }

        //添加无创建时间的快递订单的创建时间
        $notimecityorder = pdo_fetchall("SELECT id,orderid FROM ".tablename('wlmerchant_delivery_order')."WHERE createtime = 0 ORDER BY id DESC LIMIT 10");
        if(!empty($notimecityorder)){
            foreach($notimecityorder as $cityorder){
                $createtime = pdo_getcolumn(PDO_NAME.'order',array('id'=>$cityorder['orderid']),'createtime');
                pdo_update('wlmerchant_order',array('createtime' => $createtime),array('id' => $cityorder['id']));
            }
        }

        //自动退款哦
        $where = self::getTaskWhere(0);
        $overorders = pdo_fetchall("SELECT id,recordid,plugin,fkid,uniacid FROM " . tablename('wlmerchant_order') . "WHERE  status = 6 {$where} AND failtimes < 3 limit 10");
        if ($overorders) {
            foreach ($overorders as $key => $over) {
                $_W['uniacid'] = $over['uniacid'];
                if ($over['plugin'] == 'wlfightgroup') {
                    $usedtime = pdo_getcolumn(PDO_NAME . 'fightgroup_userecord', array('id' => $over['recordid']), 'usedtime');
                    $overrefund = pdo_getcolumn(PDO_NAME . 'fightgroup_goods', array('id' => $over['fkid']), 'overrefund');
                    if (empty($usedtime) && $overrefund) {
                        Wlfightgroup::refund($over['id']);
                    }
                } else if ($over['plugin'] == 'groupon') {
                    $usedtime = pdo_getcolumn(PDO_NAME . 'groupon_userecord', array('id' => $over['recordid']), 'usedtime');
                    $overrefund = pdo_getcolumn(PDO_NAME . 'groupon_activity', array('id' => $over['fkid']), 'overrefund');
                    if (empty($usedtime) && $overrefund) {
                        Groupon::refund($over['id']);
                    }
                } else if ($over['plugin'] == 'coupon') {
                    $usedtime = pdo_getcolumn(PDO_NAME . 'member_coupons', array('id' => $over['recordid']), 'usedtime');
                    $overrefund = pdo_getcolumn(PDO_NAME . 'couponlist', array('id' => $over['fkid']), 'overrefund');
                    if (empty($usedtime) && $overrefund) {
                        wlCoupon::refund($over['id']);
                    }
                } else if ($over['plugin'] == 'activity') {
                    Activity::refundorder($over['id']);
                }
            }
        }
        pdo_update('wlmerchant_member',array('card_number' => ''),array('card_number' => 'undefined'));

        //删除锁定订单
        pdo_delete('wlmerchant_temporary_orderlist',array('deteletime <'=>time()));
        //提现写入
        $settlement_temporary = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_settlement_temporary')."WHERE uniacid > 0 ORDER BY id DESC");
        if(!empty($settlement_temporary)){
            foreach($settlement_temporary as $settlement){
                $_W['uniacid'] = $settlement['uniacid'];
                $data = unserialize($settlement['info']);
                if($settlement['type'] == 1){
                    $res = Member::credit_update_credit2($data['mid'] , -$data['sapplymoney'],'用户余额提现');
                    if (!is_error($res)) {
                        if (pdo_insert(PDO_NAME . 'settlement_record' , $data)) {
                            $orderid = pdo_insertid();
                            //管理员模板消息发送
                            $adminmid = Setting::wlsetting_read('adminmid');
                            if (!empty($adminmid)) {
                                $nickname    = pdo_getcolumn(PDO_NAME.'member',array('id'=>$data['mid']),'nickname');
                                $messagedata = [
                                    'first'   => '您好，有一个用户余额提现申请待审核。' ,
                                    'type'    => '用户[' . $nickname . ']申请提现' . $data['sapplymoney'] . '元' ,//业务类型
                                    'content' => $_W['wlsetting']['trade']['fxstext'] . ':[' . $nickname . ']' ,//业务内容
                                    'status'  => '待审核' ,//处理结果
                                    'time'    => date('Y-m-d H:i:s' , time()) ,//操作时间
                                    'remark'  => '请尽快前往系统后台审核'
                                ];
                                TempModel::sendInit('service' , $adminmid , $messagedata , $data['source'] , '');
                            }
                            //用户模板消息发送
                            $messagedata2 = [
                                'first'   => '您的提现申请已成功提交' ,
                                'type'    => '申请提现' . $data['sapplymoney'] . '元' ,//业务类型
                                'content' => '申请用户：[' . $nickname . ']' ,//业务内容
                                'status'  => '待审核' ,//处理结果
                                'time'    => date('Y-m-d H:i:s' , time()) ,//操作时间
                            ];
                            TempModel::sendInit('service' , $data['mid'] , $messagedata2 , $data['source'] , '');
                        }
                    }
                }   //用户余额提现
                else if($settlement['type'] == 2){
                    //可提现金额校验
                    $merchant = pdo_get('wlmerchant_merchantdata' , ['id' => $data['sid']] , ['allmoney' , 'nowmoney' , 'reservestatus' , 'reservemoney', 'autocash']);
                    $cashsets = Setting::wlsetting_read('cashset');
                    //预留金额设置
                    if ($merchant['reservestatus']) {
                        $reservemoney = $merchant['reservemoney'];
                    } else {
                        $reservemoney = sprintf("%.2f" , $cashsets['reservemoney']);
                    }
                    $usemoney = sprintf("%.2f" , $merchant['nowmoney'] - $reservemoney);
                    if ($usemoney < 0) {
                        $usemoney = 0;
                    }
                    if ($data['sapplymoney'] < $usemoney || $data['sapplymoney'] == $usemoney) {
                        if (pdo_insert(PDO_NAME . 'settlement_record' , $data)) {
                            $orderid = pdo_insertid();
                            $res     = Store::settlement($orderid , 0 , $data['sid'] , -$data['sapplymoney'] , 0 , -$data['sapplymoney'] , 7 , 0 , 0 , $data['aid']);
                            if ($res) {
                                if ($cashsets['noaudit'] && $cashsets['autocash'] && $data['payment_type'] != 3 && $merchant['autocash']) {
                                    Queue::addTask(4 , $orderid , time() , $orderid);
                                }else if($data['status'] == 2){
                                    $storename = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $data['sid']] , 'storename');
                                    $modelData = [
                                        'first'   => '您好，有一个商户提现申请待审核。' ,
                                        'type'    => '商户提现申请' ,//业务类型
                                        'content' => '商户[' . $storename . ']申请提现' . $data['sapplymoney'] . '元' ,//业务内容
                                        'status'  => '待审核' ,//处理结果
                                        'time'    => date("Y-m-d H:i:s" , time()) ,//操作时间$store['createtime']
                                        'remark'  => '请尽快前往系统后台审核!'
                                    ];
                                    TempModel::sendInit('service' , -1 , $modelData , $data['source']);
                                }else if($data['status'] == 3){
                                    $storename = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $data['sid']] , 'storename');
                                    $modelData = [
                                        'first'   => '您好，有一个商户提现申请待打款。' ,
                                        'type'    => '商户提现申请' ,//业务类型
                                        'content' => '商户[' . $storename . ']申请提现' . $data['sapplymoney'] . '元' ,//业务内容
                                        'status'  => '待打款' ,//处理结果
                                        'time'    => date("Y-m-d H:i:s" , time()) ,//操作时间$store['createtime']
                                        'remark'  => '请尽快前往系统后台审核!'
                                    ];
                                    TempModel::sendInit('service' , -1 , $modelData , $data['source']);
                                }
                            }
                        }
                    }
                }//商户提现
                else if($settlement['type'] == 3){
                    $dismember = pdo_get(PDO_NAME.'member',array('id'=>$data['mid']),['distributorid','nickname']);
                    $nickname = $dismember['nickname'];
                    $disid = $dismember['distributorid'];
                    $distributor = pdo_get(PDO_NAME . 'distributor', array('id' => $disid), array('nowmoney', 'id'));
                    if ($data['sapplymoney'] < $distributor['nowmoney'] || $data['sapplymoney'] == $distributor['nowmoney'] ){
                        $nowmoney = $distributor['nowmoney'] - $data['sapplymoney'];
                        pdo_update(PDO_NAME . 'distributor', array('nowmoney' => $nowmoney), array('id' => $distributor['id']));
                        $res = pdo_insert(PDO_NAME . "settlement_record", $data);
                        $disorderid = pdo_insertid();
                        if($res){
                            $cashsets = Setting::wlsetting_read('cashset');
                            if ($cashsets['disautocash'] && $data['payment_type'] != 3) {
                                Queue::addTask(4, $disorderid, time(), $disorderid);
                            }
                            $url = h5_url('pages/subPages/dealer/withdraw/withdrawrecord', ['draw_id' => $disorderid]);
                            if ($cashsets['disnoaudit']) {
                                Distribution::distriNotice($_W['mid'], $url, 4, 0, $data['sapplymoney']);
                            } else {
                                Distribution::distriNotice($_W['mid'], $url, 3, 0, $data['sapplymoney']);
                            }
                            Distribution::adddisdetail($disorderid, $data['mid'], $data['mid'], 2, $data['sapplymoney'], 'cash', 1, '分销佣金提现', $nowmoney);
                            //给管理员发送通知信息
                            $textsets = Setting::wlsetting_read('trade');
                            $meaasgedata = array(
                                'first'   => '您好，有一个'.$textsets['fxtext'].'提现申请待审核。',
                                'type'    => $textsets['fxstext'] . '申请提现',//业务类型
                                'content' => '申请人:[' . $nickname . ']',//业务内容
                                'status'  => '待审核',//处理结果
                                'time'    => date('Y-m-d H:i:s', time()),//操作时间
                                'remark'  => '请尽快前往系统后台处理'
                            );
                            TempModel::sendInit('service', -1, $meaasgedata, $data['source'], '');
                        }
                    }
                }//分销商提现
                else if($settlement['type'] == 5){
                    //获取红娘信息
                    $matchmaker = pdo_get(PDO_NAME."dating_matchmaker",['mid' => $settlement['mid'],'uniacid' => $_W['uniacid']]);
                    $set        = Setting::wlsetting_read('dating_set');
                    $member     = pdo_get(PDO_NAME."member",['id'=>$settlement['mid']],['nickname']);
                    //判断当前操作是否合法  提现金额必须小于或者等于可提现金额
                    if($data['sapplymoney'] <= $matchmaker['commission']){
                        //修改红娘的可提现金额
                        $surplusMoney = sprintf("%.2f",$matchmaker['commission'] - $data['sapplymoney']);
                        pdo_update(PDO_NAME."dating_matchmaker",['commission'=>$surplusMoney],['id'=>$matchmaker['id']]);
                        //记录变更
                        Dating::commissionChangeRecord($settlement['mid'],$data['sapplymoney'],'红娘申请提现',2);
                        //将当前信息记录到提现表
                        $res = pdo_insert(PDO_NAME . "settlement_record", $data);
                        if($res){
                            $recordId = pdo_insertid();
                            //15=红娘提现审核中,16=红娘提现审核通过(待打款),17=红娘提现驳回,18=红娘提现已完成(打款完成)
                            if($set['automatic_payment'] == 1 && $data['status'] == 16){
                                //开启自动打款  状态为已审核 调用计划任务进行自动打款
                                Queue::addTask(4, $recordId, time(), $recordId);
                            }else{
                                //未开启自动打款 | 状态不为已审核  发送模板消息提醒管理员进行处理
                                $meaasgedata = [
                                    'first'   => '您好，有一个红娘提现申请待处理',
                                    'type'    => '红娘申请提现',//业务类型
                                    'content' => "申请人:[{$member['nickname']}]",//业务内容
                                    'status'  => $data['status'] == 16 ? '待打款' : '待审核',//处理结果
                                    'time'    => date('Y-m-d H:i:s',time()),//操作时间
                                    'remark'  => '请尽快前往系统后台处理'
                                ];
                                TempModel::sendInit('service',-1,$meaasgedata,$data['source']);
                            }
                        }
                    }
                }//红娘提现
                pdo_delete('wlmerchant_settlement_temporary',array('id'=>$settlement['id']));
            }
        }

        //定制内容 幸运团自动返现
        $orders385 = pdo_fetchall("SELECT id,mid,orderno,fkid,uniacid FROM " . tablename('wlmerchant_order') . "WHERE redpagstatus = 1 {$where} limit 3");
        if(!empty($orders385)){
            foreach ($orders385 as $or385){
                $_W['uniacid'] = $or385['uniacid'];
                $_W['account'] = uni_fetch($_W['uniacid']);
                $orsource = pdo_getcolumn(PDO_NAME.'paylogvfour',array('tid'=>$or385['orderno'],'mid'=>$or385['mid']),'source');
                $nlUser = pdo_get('wlmerchant_member',array('id' => $or385['mid']),array('openid','wechat_openid'));
                $luckymoney = pdo_getcolumn(PDO_NAME.'fightgroup_goods',array('id'=>$or385['fkid']),'luckymoney');
                if($orsource == 1){
                    $sopenid = $nlUser['openid'];
                }else if($orsource == 3){
                    $sopenid = $nlUser['wechat_openid'];
                }
                if(empty($sopenid)){
                    if(!empty($nlUser['openid'])){
                        $sopenid = $nlUser['openid'];
                        $orsource = 1;
                    }
                    if(!empty($nlUser['wechat_openid'])){
                        $sopenid = $nlUser['wechat_openid'];
                        $orsource = 3;
                    }
                }
                if(!empty($sopenid)){
                    $params = [
                        'openid'   => $sopenid ,
                        'money'    => $luckymoney ,
                        'rem'      => '幸运团返现' ,
                        'name'     => 'weliam' ,
                        'order_no' => $or385['orderno'],
                        'source'   => $orsource ,
                        'mid'      => $or385['mid'],
                        'return'   => 1,//代表不返回任何信息
                    ];
                    $res = Payment::presentationInit($params,2);
                    if($res){
                        pdo_update(PDO_NAME . 'order', ['redpagstatus' => 2], ['id' => $or385['id']]);
                    }
                }
            }
        }

        //挪车卡开通一卡通会员
        $now = time();
        $vipmembers = pdo_fetchall("SELECT id,lastviptime,aid,uniacid,nickname FROM " . tablename('wlmerchant_member') . "WHERE lastviptime > {$now} ORDER BY id DESC limit 10");
        if ($vipmembers) {
            foreach ($vipmembers as $key => $v) {
                $halfmember = pdo_fetch("SELECT expiretime,id FROM " . tablename('wlmerchant_halfcardmember') . "WHERE mid = {$v['id']} ORDER BY expiretime DESC");
                if ($halfmember) {
                    if ($halfmember['expiretime'] < $v['lastviptime']) {
                        $res = pdo_update('wlmerchant_halfcardmember', array('expiretime' => $v['lastviptime']), array('id' => $halfmember['id']));
                    }
                } else {
                    $data = array(
                        'mid'        => $v['id'],
                        'uniacid'    => $v['uniacid'],
                        'aid'        => $v['aid'],
                        'expiretime' => $v['lastviptime'],
                        'username'   => $v['nickname'],
                        'createtime' => time()
                    );
                    $res = pdo_insert(PDO_NAME . 'halfcardmember', $data);
                }
                if ($res) {
                    pdo_update('wlmerchant_member', array('lastviptime' => 999), array('id' => $v['id']));
                }
            }
        }

        //红包过期状态
        pdo_update('wlmerchant_redpacks',array('status' => 2),array('status' => 1,'usetime_type' => 0,'use_end_time <' => time()));
        pdo_update('wlmerchant_redpack_records',array('status' => 2),array('status' => 0,'end_time <' => time()));

        //修改帖子刷新时间
        pdo_query("UPDATE ".tablename('wlmerchant_pocket_informations')." SET refreshtime = createtime WHERE refreshtime = 0");
        //修改分销商修改时间
        pdo_query("UPDATE ".tablename('wlmerchant_distributor')." SET updatetime = createtime WHERE updatetime = 0");
        //修改uniacid=0的账单明细
        $unerror = pdo_getall('wlmerchant_autosettlement_record',array('uniacid' => 0),array('id','aid','type','orderid'));
        if(!empty($unerror)){
            foreach($unerror as $un){
                if($un['aid'] > 0){
                    $uniacid = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$un['aid']),'uniacid');
                }else{
                    if($un['type'] == 1){
                        $uniacid = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$un['orderid']),'uniacid');
                    }else if($un['type'] == 4){
                        $uniacid = pdo_getcolumn(PDO_NAME.'halfcard_record',array('id'=>$un['orderid']),'uniacid');
                    }else if($un['type'] == 7){
                        $uniacid = pdo_getcolumn(PDO_NAME.'settlement_record',array('id'=>$un['orderid']),'uniacid');
                    }else{
                        $uniacid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$un['orderid']),'uniacid');
                    }
                }
                pdo_update('wlmerchant_autosettlement_record',array('uniacid' => $uniacid),array('id' => $un['id']));
            }
        }


    }
}

