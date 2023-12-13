<?php
defined('IN_IA') or exit('Access Denied');

class Rush {
    /**
     * 获取单条仓库商品数据
     *
     * @access static
     * @name getSingleGoods
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getSingleGoods($id, $select, $where = array()) {
        $where['id'] = $id;
        $goodsInfo = Cache::getDateByCacheFirst('goods', $id, array('Util', 'getSingelData'), array($select, PDO_NAME . 'goodshouse', $where));
        if (empty($goodsInfo)) return array();
        return self::initSingleGoods($goodsInfo);
        //需删除缓存
    }

    /**
     * 获取多条仓库商品数据
     *
     * @access static
     * @name getNumGoods
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getNumGoods($select, $where, $order, $pindex, $psize, $ifpage) {
        $goodsInfo = Util::getNumData($select, PDO_NAME . 'goodshouse', $where, $order, $pindex, $psize, $ifpage);
        foreach ($goodsInfo[0] as $k => $v) {
            $newGoodInfo[$k] = self::initSingleGoods($v);
        }
        $newGoodInfo = $newGoodInfo ? $newGoodInfo : array();
        return array($newGoodInfo, $goodsInfo[1], $goodsInfo[2]) ? array($newGoodInfo, $goodsInfo[1], $goodsInfo[2]) : array();
    }


    /**
     * 初始化商品数据
     *
     * @access static
     * @name  initSingleGoods
     * @param $goodsInfo  商品数据
     * @return $goodsInfo
     */
    static function initSingleGoods($goodsInfo) {
        $goodsInfo['plugin'] = 'rush';
        $goodsInfo['a'] = h5_url('pages/subPages/goods/index',['id'=>$goodsInfo['id'],'type'=>1]);
        return $goodsInfo;
    }


    /**
     * 获取所有商户
     *
     * @access static
     * @name getNumMerchant
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getNumMerchant($select, $where, $order, $pindex, $psize, $ifpage) {
        $merchantInfo = Util::getNumData($select, PDO_NAME . 'merchantdata', $where, $order, $pindex, $psize, $ifpage);
        return $merchantInfo;
    }

    /**
     * 获取单条商户数据
     *
     * @access static
     * @name getSingleMerchant
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getSingleMerchant($id, $select, $where = array()) {
        $where['id'] = $id;
        return Util::getSingelData($select, PDO_NAME . 'merchantdata', $where);
    }


    /*******************************************************以下为活动方法*************************************************************************/


    /**
     * 保存抢购活动
     *
     * @access static
     * @name saveRushActive
     * @param mixed  参数一的说明
     * @return array
     */
    static function saveRushActive($active, $param = array()) {
        global $_W;
        if (!is_array($active)) return FALSE;
        $active['uniacid'] = $_W['uniacid'];
        if (empty($param)) {
            pdo_insert(PDO_NAME . 'rush_activity', $active);
            return pdo_insertid();
        }
        return FALSE;
    }

    /**
     * 获取多条活动数据
     *
     * @access static
     * @name getNumActive
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getNumActive($select, $where, $order, $pindex, $psize, $ifpage) {
        $activeInfo = Util::getNumData($select, PDO_NAME . 'rush_activity', $where, $order, $pindex, $psize, $ifpage);
        return $activeInfo;

    }

    /**
     * 获取单条活动数据
     *
     * @access static
     * @name getSingleActive
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getSingleActive($id, $select, $where = array()) {
        $where['id'] = $id;
        $goodsInfo = Util::getSingelData($select, PDO_NAME . 'rush_activity', $where);
        if (empty($goodsInfo)) return array();
        return self::initSingleGoods($goodsInfo);
        //需删除缓存
    }

    /**
     * 更新活动
     *
     * @access static
     * @name updateActive
     * @param $params  修改参数
     * @param $where   修改条件
     * @return array
     */
    static function updateActive($params, $where) {
        $res = pdo_update(PDO_NAME . 'rush_activity', $params, $where);
        if ($where['id']) Cache::deleteCache('active', $where['id']);
        if ($res) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 删除活动
     *
     * @access static
     * @name deleteActive
     * @param $params  修改参数
     * @param $where   修改条件
     * @return array
     */
    static function deleteActive($where) {
        $res = pdo_delete(PDO_NAME . 'rush_activity', $where);
        if ($where['id']) {
            Cache::deleteCache('active', $where['id']);
        }
        if ($res) {
            return 1;
        } else {
            return 0;
        }
    }


    /**
     * 活动状态判断
     *
     * @access static
     * @name deleteActive
     * @param $params  修改参数
     * @param $arr   修改条件
     * @return array
     */
    static function changeActivestatus($arr) {
        if (empty($arr)) return FALSE;
        if (is_numeric($arr)) {
            $arr = self::getSingleActive($arr, 'id,starttime,endtime,levelnum');
        }
        if (!is_array($arr) || empty($arr)) {
            return false;
        }
        if ($arr['status'] == 1 || $arr['status'] == 2 || $arr['status'] == 3 || $arr['status'] == 7) {
            if ($arr['starttime'] > time()) {
                $goods['status'] = 1;
            } elseif ($arr['starttime'] < time() && time() < $arr['endtime'] && $arr['levelnum'] > 0) {
                $goods['status'] = 2;
            } elseif ($arr['endtime'] < time()) {
                $goods['status'] = 3;
            }
            self::updateActive($goods, array('id' => $arr['id']));
        }
    }


    /*******************************************************以下为抢购订单方法*************************************************************************/
    /**
     * 获取单条订单数据
     *
     * @access static
     * @name getSingleOrder
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getSingleOrder($id, $select, $where = array()) {
        $where['id'] = $id;
        $data = Util::getSingelData($select, PDO_NAME . 'rush_order', $where);
        return self::initSingleOrder($data);
    }


    /**
     * 获取多条订单数据
     *
     * @access static
     * @name getNumOrder
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getNumOrder($select, $where, $order, $pindex, $psize, $ifpage) {
        $orderInfo = Util::getNumData($select, PDO_NAME . 'rush_order', $where, $order, $pindex, $psize, $ifpage);
        $newOrderInfo = array();
        foreach ($orderInfo[0] as $k => $v) {
            $newOrderInfo[$k] = self::initSingleOrder($v);
        }
        return array($newOrderInfo, $orderInfo[1], $orderInfo[2]) ? array($newOrderInfo, $orderInfo[1], $orderInfo[2]) : array();

    }

    /**
     * 初始化商品数据
     *
     * @access static
     * @name  initSingleGoods
     * @param $goodsInfo  商品数据
     * @return $goodsInfo
     */
    static function initSingleOrder($orderInfo) {
        $active = self::getSingleActive($orderInfo['activityid'], '*');
        $member = self::getSingleMember($orderInfo['mid'], '*');
        $orderInfo['gimg'] = $active['thumb'];
        $orderInfo['unit'] = $active['unit'];
        $orderInfo['gname'] = $active['name'];
        $orderInfo['nickname'] = $orderInfo['username'] ? $orderInfo['username'] : $member['nickname'];
        $orderInfo['headimg'] = $member['avatar'];
        $orderInfo['mobile'] = $orderInfo['mobile'] ? $orderInfo['mobile'] : $member['mobile'];
        $orderInfo['addname'] = $orderInfo['address'];
        $merchant = SingleMerchant::getSingleMerchant($orderInfo['sid'], "*");
        $orderInfo['merchantName'] = $merchant['storename'];
        $orderInfo['merchantId'] = $merchant['id'];
        $orderInfo['merchantLogo'] = tomedia($merchant['logo']);
        $orderInfo['plugin'] = 'rush';
        $orderInfo['goodsprice'] = sprintf("%.2f", $orderInfo['price'] / $orderInfo['num']);
        if ($orderInfo['optionid']) {
            $orderInfo['optiontitle'] = pdo_getcolumn(PDO_NAME . 'goods_option', array('id' => $orderInfo['optionid']), 'title');
        }
        return $orderInfo;
    }

    /**
     * 更新订单
     *
     * @access static
     * @name updateOrder
     * @param $params  修改参数
     * @param $where   修改条件
     * @return array
     */
    static function updateOrder($params, $where) {
        $res = pdo_update(PDO_NAME . 'rush_order', $params, $where);
        if ($res) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * 删除订单
     *
     * @access static
     * @name deleteOrder
     * @param $params  修改参数
     * @param $where   修改条件
     * @return array
     */
    static function deleteOrder($where) {
        $res = pdo_delete(PDO_NAME . 'rush_order', $where);
        if ($res) {
            return 1;
        } else {
            return 0;
        }
    }






    /*******************************************************以下用户信息方法*************************************************************************/


    /**
     * 获取单条用户数据
     *
     * @access static
     * @name getSingleMember
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getSingleMember($id, $select, $where = array()) {
        $where['id'] = $id;
        return Util::getSingelData($select, PDO_NAME . 'member', $where);
    }





    /*******************************************************以下为支付方法*************************************************************************/


    /**
     * 异步支付结果回调 ，处理业务逻辑
     *
     * @access public
     * @name
     * @param mixed  参数一的说明
     * @return array
     */
    static function payRushOrderNotify($params) {
        global $_W;
        Util::wl_log('rush_notify', PATH_DATA . "rush/data/", $params); //写入异步日志记录
        $order_out = pdo_fetch("select * from" . tablename(PDO_NAME . 'rush_order') . "where orderno='{$params['tid']}'");
        $_W['uniacid'] = $order_out['uniacid'];
        $_W['wlsetting']['creditset'] = Setting::wlsetting_read('creditset');
        $activeInfo = self::getSingleActive($order_out['activityid'], '*');
        $data = self::getRushOrderPayData($params, $order_out, $activeInfo); //得到支付参数，处理代付
        if ($order_out['status'] == 0 || $order_out['status'] == 5) {
            pdo_update(PDO_NAME . 'rush_order', $data, array('orderno' => $params['tid'])); //更新订单状态
            //支付返现的信息处理
            if(p('cashback')){
                Cashback::record($order_out['id'],'rush');
            }
            $orderid = $order_out['id'];
            if($order_out['neworderflag']  && empty($activeInfo['pftid'])){
                Order::createSmallorder($order_out['id'],1);
            }
            //处理分销
            if($order_out['dkmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                $nodis = 1;
            }else{
                $nodis = 0;
            }
            $_W['aid'] = $order_out['aid'];
            if (p('distribution') && empty($activeInfo['isdistri']) && empty($order_out['drawid']) && empty($nodis)) {
                $disorderid = Distribution::newDisCore($order_out['id'],'rush');
                $data['disorderid'] = $disorderid;
            }
            //处理业务员佣金
            if(p('salesman') && $order_out['sid'] > 0){
                $data['salesarray'] = Salesman::saleCore($order_out['sid'],'rush');
            }
            //支付有礼
            if($activeInfo['paidid'] > 0){
                $data['paidprid'] = Paidpromotion::getpaidpr(1,$activeInfo['paidid'],$order_out['mid'],$order_out['id'],$data['paytype'],$order_out['actualprice'],$order_out['num']);
            }
            //分享有礼
            if ($activeInfo['sharestatus']) {
                pdo_update('wlmerchant_sharegift_record', array('status' => 1), array('id' => $order_out['shareid']));
            }
            //计算过期时间
            if ($activeInfo['cutoffstatus']) {
                $data['estimatetime'] = time() + $activeInfo['cutoffday'] * 86400;
            } else {
                $data['estimatetime'] = $activeInfo['cutofftime'];
            }
            //抽奖领取
            if($order_out['drawid'] > 0){
                pdo_update('wlmerchant_draw_record',array('is_get' => 2),array('id' => $order_out['drawid']));
            }
            //计算通知时间
            $data['remindtime'] = Order::remindTime($data['estimatetime']);
            //快递订单
            if ($order_out['expressid']) {
                $data['status'] = 8;
            }
            pdo_update(PDO_NAME . 'rush_order', $data, array('orderno' => $params['tid'])); //更新订单状态
            //卡密商品
            if($activeInfo['usestatus'] == 3){
                $rushtask = array(
                    'plugin'  => 'rush',
                    'orderid' => $order_out['id']
                );
                $rushtask = serialize($rushtask);
                Queue::addTask(10, $rushtask, time(), $order_out['id']);
            }
            //票付通
            if($activeInfo['pftid'] > 0){
                $pftinfo = unserialize($order_out['pftinfo']);
                if(empty($activeInfo['threestatus'])){
                    $pftinfo['remotenum'] = $order_out['orderno'];
                    $pftinfo['memo'] = $order_out['remark'];
                    $pftorderinfo = Pftapimod::pftOrderSubmit($pftinfo);
                    if(empty($pftorderinfo['UUerrorcode'])){
                        $pftorderinfo = serialize($pftorderinfo);
                        pdo_update(PDO_NAME . 'rush_order', ['pftorderinfo' => $pftorderinfo], array('orderno' => $params['tid'])); //更新订单状态
                        //订单信息查询
                        $moreinfo = Pftapimod::pftOrderQuery($pftorderinfo['UUordernum']);
                        if(empty($moreinfo['UUerrorcode'])){
                            $pftchangeinfo = [
                                'estimatetime' => strtotime($moreinfo['UUendtime']),
                            ];
                            pdo_update(PDO_NAME . 'rush_order',$pftchangeinfo, array('orderno' => $params['tid'])); //更新订单状态
                        }
                    }else{
                        Util::wl_log('pftNewError.log',PATH_DATA,$pftorderinfo); //写入异步日志记录
                        //添加提交订单到计划任务
                        $rushtask = array(
                            'type'    => 'rush',
                            'orderid' => $order_out['id']
                        );
                        $rushtask = serialize($rushtask);
                        Queue::addTask(9, $rushtask, time(), $order_out['id']);
                    }
                }else if($activeInfo['threestatus'] == 1){
                    $yqdorderno = 'wl_'.$order_out['orderno'];
                    $callurl = $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/pftapimod/yqdAsyNotify.php';
                    $yqddata =  [
                        'commodityId' => $activeInfo['pftid'],
                        'external_orderno' => $yqdorderno,
                        'buyCount' => $order_out['num'],
                        'remark' => $order_out['remark'],
                        'callbackUrl' => $callurl,
                        'externalSellPrice' => $order_out['price'],
                        'template' => $pftinfo
                    ];
                    $yqdInfo = Pftapimod::yqdOrderSubmit($yqddata);
                    if($yqdInfo['code'] != '200'){
                        //充值失败，通知用户
                        pdo_update(PDO_NAME . 'rush_order',['status' => 6], array('orderno' => $params['tid'])); //更新订单状态
                        $first = '很抱歉，您购买的商品['.$activeInfo['name'].']下单失败';
                        $type = '下单失败通知';
                        $status = '退款中';
                        $remark = '失败原因:'.$yqdInfo['msg'];
                        $content = '很抱歉，请您下单重试';
                        News::jobNotice($order_out['mid'],$first,$type,$content,$status,$remark,time());
                    }else{
                        pdo_update(PDO_NAME . 'rush_order',['status' => 3], array('orderno' => $params['tid'])); //更新订单状态
                    }
                }
            }
            //添加用户标签
            $userlable = unserialize($activeInfo['userlabel']);
            if(!empty($userlable)){
                Member::addUserlable($userlable,$order_out['mid']);
            }
            //通知商户
            News::addSysNotice($order_out['uniacid'],1,$order_out['sid'],0,$order_out['id']);
            Store::addFans($order_out['sid'], $order_out['mid']);
            News::paySuccess($order_out['id'], 'rush');
            //打印推送
            Order::sendPrinting($order_out['id'],'rush');
        }
//        else if ($data['status']) {
//            $data['status'] = 6; //修改订单状态为待退款
//            pdo_update(PDO_NAME . 'rush_order', $data, array('orderno' => $params['tid'])); //更新订单状态
//        }
    }

    /**
     * 函数的含义说明
     *
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    static function payRushOrderReturn($params, $backurl = false) {
        Util::wl_log('payResult_return', PATH_PLUGIN . "rush/data/", $params);//写入日志记录
        $order_out = pdo_get(PDO_NAME . 'rush_order', array('orderno' => $params['tid']), array('id','v4flag'));
        wl_message('支付成功', h5_url('pages/mainPages/paySuccess/paySuccess', array('id' => $order_out['id'], 'type' => 1)), 'success');
    }

    /**
     * 函数的含义说明
     *
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    static function getRushOrderPayData($params, $order_out, $goodsInfo) {
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
        $data['blendcredit'] = $params['blendcredit'];
        SingleMerchant::updateAmount($fee, $order_out['sid'], $order_out['id'], 1, '订单支付成功');
        return $data;
    }

    //取消订单
    static function cancelorder($id) {
        global $_W;
        $res = pdo_update(PDO_NAME . "rush_order", array('status' => 5), array('id' => $id));//更新为已取消
        if ($res) {
            $order = pdo_get('wlmerchant_rush_order', array('id' => $id), array('num','redpackid','uniacid','activityid','optionid','dkcredit','mid'));
            $_W['uniacid'] = $order['uniacid'];
            if($order['redpackid']){
                pdo_update('wlmerchant_redpack_records',['status' => 0],['id' => $order['redpackid']]);
            }
            if ($order['dkcredit'] > 0 ) {
                $goodname = pdo_getcolumn(PDO_NAME . 'rush_activity', array('id' => $order['activityid']), 'name');
                Member::credit_update_credit1($order['mid'], $order['dkcredit'], '取消抢购商品:[' . $goodname . ']订单返还积分');
            }
            //修改已抢完状态
            $goods = pdo_get('wlmerchant_rush_activity',array('id' => $order['activityid']),array('status','starttime','endtime'));
            if($goods['status'] == 7){
                if ($goods['starttime'] > time()) {
                    $changestatus = 1;
                }
                else if ($goods['starttime'] < time() && time() < $goods['endtime']) {
                    $changestatus = 2;
                }
                else if ($goods['endtime'] < time()) {
                    $changestatus = 3;
                }
                pdo_update('wlmerchant_rush_activity', array('status' => $changestatus), array('id' => $order['activityid']));
            }
            return TRUE;
        }
    }

    //核销订单流程
    static function hexiaoorder($id, $mid, $num = 1, $type = 1,$checkcode='',$afterid = 0) {  //1输码 2扫码 3后台 4密码
        global $_W;
        $order = pdo_get('wlmerchant_rush_order', array('id' => $id));
        if($order['neworderflag']){
            $order['usetimes'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE plugin = 'rush' AND  orderid = {$id} AND status = 1");
        }
        if ($order['usetimes'] < $num) {
            if (is_mobile()) {
                die(json_encode(array("errno" => 1, 'message' => '使用次数不足，无法核销','data'=>'')));
            } else {
                show_json(0, '使用次数不足，无法核销');
            }
        }
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
        if($order['neworderflag']){
            if($checkcode){
                $smallorders = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_smallorder')."WHERE plugin = 'rush' AND  orderid = {$id} AND status = 1 AND checkcode = '{$checkcode}'");
            }else if(empty($afterid)){
                $smallorders = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_smallorder')."WHERE plugin = 'rush' AND  orderid = {$id} AND status = 1 ORDER BY id ASC LIMIT {$num}");
            }
            if($smallorders){
                if($mid){
                    $uid = pdo_getcolumn(PDO_NAME.'merchantuser',array('storeid'=>$order['sid'],'mid'=>$mid),'id');
                }else{
                    $uid = 0;
                }
                foreach ($smallorders as $k => $small){
                    $rushoverres = Order::finishSmallorder($small['id'],$uid,$type);
                }
            }else{
                if (is_mobile()) {
                    die(json_encode(array('errno' => 1, 'message' => '无可用核销码')));
                } else {
                    show_json(0, '无可用核销码');
                }
            }
        }else{
            //添加更新
            $arr = array();
            if ($order['usedtime']) {
                $a = unserialize($order['usedtime']);
                for ($i = 0; $i < $num; $i++) {
                    $arr['time'] = time();
                    $arr['type'] = $type;
                    $arr['ver'] = $mid;
                    $a[] = $arr;
                }
                $order['usedtime'] = serialize($a);
            } else {
                $a = array();
                for ($i = 0; $i < $num; $i++) {
                    $arr['time'] = time();
                    $arr['type'] = $type;
                    $arr['ver'] = $mid;
                    $a[] = $arr;
                }
                $order['usedtime'] = serialize($a);
            }
            $params['usetimes'] = $order['usetimes'] - $num;
            $params['usedtime'] = $order['usedtime'];
            if ($params['usetimes'] < 1) {
                $params['status'] = 2;
    //			添加结算抢购订单到计划任务
                $rushtask = array(
                    'type'    => 'rush',
                    'orderid' => $id
                );
                $rushtask = serialize($rushtask);
                Queue::addTask(1, $rushtask, time(), $id);
                if (!empty($order['disorderid'])) {
                    //添加结算分销订单到计划任务
                    $res = pdo_update('wlmerchant_disorder', array('status' => 1), array('id' => $order['disorderid'], 'status' => 0));
                    if ($res) {
                        $distask = array(
                            'type'    => 'rush',
                            'orderid' => $order['disorderid']
                        );
                        $distask = serialize($distask);
                        Queue::addTask(3, $distask, time(), $order['disorderid']);
                    }
                }
            }
            $where['id'] = $id;
            $rushoverres = Rush::updateOrder($params, $where);
            $checkflag = 1;
        }
        //校验
        if(!empty($smallorders)){
            $checkflag = 1;
            foreach ($smallorders as $checksmall){
                $checksmallstatus = pdo_getcolumn(PDO_NAME.'smallorder',array('id'=>$checksmall['id']),'status');
                if($checksmallstatus != 2){
                    $checkflag = 0;
                }
            }
        }

        if ($rushoverres  && $checkflag > 0) {
            $active = pdo_get('wlmerchant_rush_activity', array('id' => $order['activityid']), array('name'));
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
            SingleMerchant::verifRecordAdd($order['aid'], $order['sid'], $order['mid'], 'rush', $order['id'], $order['checkcode'], $active['name'], $type, $num);
            return 1;
        } else {
            return 0;
        }
    }

    //退款函数
    static function refund($id, $money = 0, $unline = '',$checkcode = '',$afterid = 0) {
        global $_W;
        $item = Rush::getSingleOrder($id, '*');
        //票付通
        if(!empty($item['pftorderinfo'])){
            $pftinfo = unserialize($item['pftorderinfo']);
            $pftorderinfo = Pftapimod::pftOrderQuery($pftinfo['UUordernum']);
            if($afterid > 0){
                $renum = pdo_getcolumn(PDO_NAME.'aftersale',array('id'=>$afterid),'num');
                if(empty($pftorderinfo['UUerrorcode'])){
                    $pftnum = $pftorderinfo['UUorigin_num'] - $pftorderinfo['UUrefund_num'] - $renum;
                }else{
                    $res['status']  = false;
                    $res['message'] = "票务平台退款错误:".$pftorderinfo['UUerrorinfo'];
                    return $res;
                }
            }else{
                $renum = $pftorderinfo['UUorigin_num'] - $pftorderinfo['UUverified_num'] - $pftorderinfo['UUrefund_num'] - $pftorderinfo['UUtnum_cancel'] - - $pftorderinfo['UUtnum_used'];
                $pftnum = 0;
            }
            if($renum > 0){
                $pftrefund = Pftapimod::pftOrderRefund(['ordern' => $pftinfo['UUordernum'],'num' => $pftnum]);
                if(!empty($pftrefund['UUerrorcode'])){
                    $res['status']  = false;
                    $res['message'] = "票务平台退款错误:".$pftrefund['UUerrorinfo'];
                    return $res;
                }
            }
        }

        if($checkcode){
            if($money<0.01){
                $smallorder = pdo_fetch("SELECT orderprice,blendcredit FROM ".tablename(PDO_NAME . "smallorder")." WHERE plugin = 'rush' AND orderid = {$id} AND status IN (1,4) AND checkcode = '{$checkcode}'");
                $money = sprintf("%.2f",$smallorder['orderprice'] - $smallorder['blendcredit']);
                $blendcredit = $smallorder['blendcredit'];
            }
            $refundnum = 1;
        }else if(empty($money)){
            $money =  pdo_fetchcolumn('SELECT SUM(orderprice) FROM ' . tablename(PDO_NAME . "smallorder") . " WHERE plugin = 'rush' AND orderid = {$id} AND status IN (1,4)");
            $blendcredit = pdo_fetchcolumn('SELECT SUM(blendcredit) FROM ' . tablename(PDO_NAME . "smallorder") . " WHERE plugin = 'rush' AND orderid = {$id} AND status IN (1,4)");
            $money = sprintf("%.2f",$money - $blendcredit);
            $refundnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME . "smallorder")." WHERE plugin = 'rush' AND orderid = {$id} AND status IN (1,4)");
        }else if(!empty($item['pftorderinfo'])){
            $money = sprintf("%.2f",$item['actualprice']/$item['num']*$renum);
            $refundnum = $renum;
        }else{
            if($money < $item['blendcredit']){
                $blendcredit = $money;
                $money = 0;
            }else if($item['blendcredit'] > 0){
                $blendcredit = $item['blendcredit'];
                $money = sprintf("%.2f",$money - $blendcredit);
            }
            $refundnum = $item['usetimes'];
        }
        if ($unline) {
            $res['status'] = 1;
        } else {
            $res = wlPay::refundMoney($id, $money, '抢购订单退款', 'rush', 2,$blendcredit);
        }
        if ($res['status']) {
            if($item['neworderflag'] && empty($item['pftorderinfo'])){
                if($checkcode){
                    pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'rush','orderid'=>$id,'status'=> array(1,4),'checkcode'=>$checkcode));
                }else if(empty($afterid)){
                    pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'rush','orderid'=>$id,'status'=> array(1,4)));
                }else if($afterid > 0){
                    $afterCheckcode = pdo_getcolumn(PDO_NAME.'aftersale',array('id'=>$afterid),'checkcodes');
                    $afterCheckcode = unserialize($afterCheckcode);
                    pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'rush','orderid'=>$id,'checkcode' => $afterCheckcode));
                }
                if ($item['applyrefund']) {
                    $reason = '买家申请退款。';
                    $orderdata['applyrefund'] = 2;
                } else {
                    $reason = '抢购系统退款。';
                }
                $overflag = pdo_get('wlmerchant_smallorder',array('orderid' => $id,'plugin'=>'rush','status'=>1),array('id'));
                if(empty($overflag)){
                    $hexiao = pdo_get('wlmerchant_smallorder',array('orderid' => $id,'plugin'=>'rush','status'=>2),array('id'));
                    if($hexiao){
                        $orderdata['status'] = 2;
                        $orderdata['issettlement'] = 1;
                        $orderdata['settletime'] = time();
                    }else{
                        $orderdata['status'] = 7;
                        $orderdata['refundtime'] = time();
                    }
                    pdo_update('wlmerchant_rush_order',$orderdata, array('id' => $item['id']));
                }
            }else if(!empty($item['pftorderinfo'])){
                //查看订单状态
                //$Npftorderinfo = Pftapimod::pftOrderQuery($pftinfo['UUordernum']);
                //Util::wl_log('pfttwoError.log',PATH_DATA,$Npftorderinfo); //写入异步日志记录
//                if($Npftorderinfo['UUstatus'] == 1){
//                    $orderdata['status'] = 2;
//                    $orderdata['issettlement'] = 1;
//                    $orderdata['settletime'] = time();
//                    //Store::rushsettlement($item['id']);
//                }else{
                    $orderdata['status'] = 7;
                    $orderdata['refundtime'] = time();
//                }
                if ($item['applyrefund']) {
                    $reason = '买家申请退款。';
                    $orderdata['applyrefund'] = 2;
                } else {
                    $reason = '抢购系统退款。';
                }
                pdo_update('wlmerchant_rush_order',$orderdata, array('id' => $item['id']));
            }else{
                if ($item['applyrefund']) {
                    pdo_update('wlmerchant_rush_order', array('status' => 7, 'refundtime' => time(), 'applyrefund' => 2), array('id' => $item['id']));
                    $reason = '买家申请退款。';
                } else {
                    pdo_update('wlmerchant_rush_order', array('status' => 7, 'refundtime' => time()), array('id' => $item['id']));
                    $reason = '抢购系统退款。';
                }
            }
            $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['orderid'=>$id,'plugin'=>'rush']);
            if ($item['disorderid']) {
                Distribution::refunddis($item['disorderid'],$checkcode);
            }
            News::refundNotice($id,'rush',$money,$reason);
            //退回适用积分
            if ($item['dkcredit']) {
                $refundcredit = sprintf("%.2f",$item['dkcredit']/$item['num']*$refundnum);
                $goodname = pdo_getcolumn(PDO_NAME . 'rush_activity', array('id' => $item['activityid']), 'name');
                Member::credit_update_credit1($item['mid'], $refundcredit, '退款抢购商品:[' . $goodname . ']订单返还积分');
            }
            if($item['redpackid'] && $orderdata['status'] == 7){
                pdo_update('wlmerchant_redpack_records',array('status' => 0,'usetime' =>0,'orderid' => 0,'plugin' =>''),array('id' => $item['redpackid']));
            }
            //修改已抢完状态
            $goods = pdo_get('wlmerchant_rush_activity',array('id' => $item['activityid']),array('status','starttime','endtime'));
            if($goods['status'] == 7){
                if ($goods['starttime'] > time()) {
                    $changestatus = 1;
                }
                else if ($goods['starttime'] < time() && time() < $goods['endtime']) {
                    $changestatus = 2;
                }
                else if ($goods['endtime'] < time()) {
                    $changestatus = 3;
                }
                pdo_update('wlmerchant_rush_activity', array('status' => $changestatus), array('id' => $item['activityid']));
            }
        } else {
            pdo_fetch("update" . tablename('wlmerchant_rush_order') . "SET failtimes = failtimes+1 WHERE id = {$id}");
        }
        return $res;
    }

    //结算过期订单
    static function settover($over, $overstatus) {
        global $_W;
        //判断是否已结算
        $flag = pdo_get(PDO_NAME . 'autosettlement_record', array('orderno' => $over['orderno']), array('id'));
        $orderset = Setting::wlsetting_read('orderset');
        if (empty($flag)) {
            if($orderset['overmoney'] == 2){
                $settnum = $over['num'];
            }else{
                $settnum = intval($over['num'] - $over['usetimes']);
            }
            if ($over['disorderid']) {
                if ($overstatus) {
                    $dissettnum = $over['num'];
                }else{
                    $dissettnum = $settnum;
                }
                if ($dissettnum) {
                    $distrimoney = 0;
                    $disorder = pdo_get('wlmerchant_disorder', array('id' => $over['disorderid']), array('leadmoney'));
                    $leadmoneys = unserialize($disorder['leadmoney']);
                    foreach ($leadmoneys as $key => &$money) {
                        $money = sprintf("%.2f", $money * $dissettnum / $over['num']);
                        $distrimoney += $money;
                    }
                    $newleadmoneys = serialize($leadmoneys);
                    pdo_update('wlmerchant_disorder', array('status' => 1, 'leadmoney' => $newleadmoneys), array('id' => $over['disorderid'], 'status' => 0));
                }
            }
            if (empty($distrimoney)) {
                $distrimoney = 0;
            }
            if ($settnum) {
                $price = round($over['actualprice'] * $settnum / $over['num'], 2);
                $settlementmoney = round($over['settlementmoney'] * $settnum / $over['num'], 2);
                $agentmoney = round($price - $settlementmoney - $distrimoney, 2);
            } else {
                $agentmoney = 0;
                $settlementmoney = 0;
            }
            //处理资金流向
            if($orderset['overmoney'] == 1){
                $agentmoney = round($over['actualprice'] - $settlementmoney - $distrimoney, 2);
            }
            $data = array(
                'uniacid'       => $_W['uniacid'],
                'aid'           => $over['aid'],
                'type'          => 1,
                'merchantid'    => $over['sid'],
                'orderid'       => $over['id'],
                'orderno'       => $over['orderno'],
                'goodsid'       => $over['activityid'],
                'orderprice'    => $over['price'],
                'agentmoney'    => $agentmoney,
                'merchantmoney' => $settlementmoney,
                'distrimoney'   => $distrimoney,
                'createtime'    => time(),
                'specialstatus' => 1
            );
            $res = pdo_insert(PDO_NAME . 'autosettlement_record', $data);
            $settlementid = pdo_insertid();
            if ($res) {
                if ($settlementmoney) {
                    pdo_fetch("update" . tablename('wlmerchant_merchantdata') . "SET allmoney=allmoney+{$settlementmoney},nowmoney=nowmoney+{$settlementmoney} WHERE id = {$data['merchantid']}");
                    $change['merchantnowmoney'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $data['merchantid']), 'nowmoney');
                    Store::addcurrent(1, 1, $over['sid'], $settlementmoney, $change['merchantnowmoney'], $over['id']);
                }
                if ($data['agentmoney']) {
                    pdo_fetch("update" . tablename('wlmerchant_agentusers') . "SET allmoney=allmoney+{$data['agentmoney']},nowmoney=nowmoney+{$data['agentmoney']} WHERE id = {$data['aid']}");
                    $change['agentnowmoney'] = pdo_getcolumn(PDO_NAME . 'agentusers', array('id' => $data['aid']), 'nowmoney');
                    Store::addcurrent(2, 1, $over['aid'], $data['agentmoney'], $change['agentnowmoney'], $over['id']);
                }
                pdo_update('wlmerchant_autosettlement_record', $change, array('id' => $settlementid));
                pdo_update('wlmerchant_rush_order', array('issettlement' => 1), array('id' => $over['id']));
                //写入结算日志记录
            }
        }else{
            pdo_update('wlmerchant_rush_order', array('issettlement' => 1), array('id' => $over['id']));
        }

    }


    static function doTask() {
        global $_W, $_GPC;
        /*自动退款*/
        $where = Queue::getTaskWhere(0);
        $where2 = Queue::getTaskWhere(1);
        $refundOrders = pdo_fetchall("select id,price,activityid,optionid,num,activityid,dkcredit,mid,aid,uniacid from" . tablename(PDO_NAME . "rush_order") . "where status = 6 {$where} and actualprice > 0 and failtimes < 4 limit 0,10");
        if (!empty($refundOrders)) {
            foreach ($refundOrders as $k => $v) {
                $_W['aid'] = $v['aid'];
                $_W['uniacid'] = $v['uniacid'];
                self::refund($v['id']);
            }
        }
        /*抢购状态*/
        if(!empty($where2)) {
            $activitys1 = pdo_getall(PDO_NAME . "rush_activity", array('starttime <' => time(), 'status' => 1,'uniacid'=>$where2), array('id'));
        }else{
            $activitys1 = pdo_getall(PDO_NAME . "rush_activity", array('starttime <' => time(), 'status' => 1), array('id'));
        }
        if (!empty($activitys1)) {
            foreach ($activitys1 as $k => $v) {
                pdo_update(PDO_NAME . "rush_activity", array('status' => 2), array('id' => $v['id']));
            }
        }
        if(!empty($where2)) {
            $activitys2 = pdo_getall(PDO_NAME . "rush_activity", array('endtime <' => time(), 'status' => 2,'uniacid'=>$where2), array('id'));
        }else{
            $activitys2 = pdo_getall(PDO_NAME . "rush_activity", array('endtime <' => time(), 'status' => 2), array('id'));
        }
        if (!empty($activitys2)) {
            foreach ($activitys2 as $k => $v) {
                pdo_update(PDO_NAME . "rush_activity", array('status' => 3), array('id' => $v['id']));
            }
        }

        /*发送抢购关注*/
        if(!empty($where2)) {
            $follows = pdo_getall(PDO_NAME . 'rush_follows', array('sendtime <=' => time(),'uniacid' => $where2), array('actid', 'mid', 'id','uniacid'), '', 'id', array(1, 50));
        }else{
            $follows = pdo_getall(PDO_NAME . 'rush_follows', array('sendtime <=' => time()), array('actid', 'mid', 'id','uniacid'), '', 'id', array(1, 50));
        }
        if (!empty($follows)) {
            foreach ($follows as $k => $v) {
                $_W['uniacid'] = $v['uniacid'];
                $goodsname = pdo_getcolumn(PDO_NAME . 'rush_activity', array('id' => $v['actid']), 'name');
                $url = h5_url('pages/subPages/goods/index',['type'=>1,'id'=>$v['actid']]);
                $userModelData = [
                    'first'   => '您好,您关注的抢购即将开始',
                    'type'    => '[' . $goodsname . ']抢购开始通知',//业务类型
                    'content' => '商品名：' .$goodsname ,//业务内容
                    'status'  => '已通知,活动即将开始',//处理结果
                    'time'    => date('Y-m-d H:i:s', time()),//操作时间
                    'remark'  => '点击立即参加抢购活动，赶快行动吧'
                ];
                TempModel::sendInit('service',$v['mid'],$userModelData,1,$url);
                pdo_delete(PDO_NAME . 'rush_follows', array('id' => $v['id']));
            }
        }

        //新过期预计时间的兼容
        $noestorder = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_rush_order') . "WHERE status = 1 {$where} AND estimatetime = 0 ORDER BY id DESC limit 20");
        if ($noestorder) {
            foreach ($noestorder as $key => $noest) {
                $activity = pdo_get('wlmerchant_rush_activity', array('id' => $noest['activityid']), array('cutoffstatus', 'cutofftime', 'cutoffday'));
                if ($activity['cutoffstatus']) {
                    $estimatetime = $noest['paytime'] + $activity['cutoffday'] * 86400;
                } else {
                    $estimatetime = $activity['cutofftime'];
                }
                pdo_update('wlmerchant_rush_order', array('estimatetime' => $estimatetime), array('id' => $noest['id']));
            }
        }

        //新的过期流程
        $nowtime = time();
        $actorder3 = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_rush_order') . "WHERE status = 1 {$where} AND estimatetime < {$nowtime} AND estimatetime > 0 ORDER BY id DESC LIMIT 20");
        if (!empty($actorder3)) {
            foreach ($actorder3 as $key => $actor3) {
                pdo_update('wlmerchant_rush_order', array('status' => 9, 'overtime' => time()), array('id' => $actor3['id']));
                //过期订单结算或退款
                $_W['uniacid'] = $actor3['uniacid'];
                $_W['aid'] = $actor3['aid'];
                $base = Setting::wlsetting_read('distribution');
                $orderset = Setting::wlsetting_read('orderset');
                $goods = pdo_get('wlmerchant_rush_activity', array('id' => $actor3['activityid']), array('overrefund'));
                if ($orderset['reovertime'] && $goods['overrefund'] ) {
                    pdo_update('wlmerchant_rush_order', array('status' => 6), array('id' => $actor3['id']));
                    self::refund($actor3['id'],0,0);
                }else if(!empty($base['overstatus']) && !empty($actor3['disorderid'])){
                    $res = Distribution::disnewsettlement($actor3['disorderid']);
                    if($res){
                        pdo_update('wlmerchant_rush_order',array('issettlement' => 1),array('id' => $actor3['id']));
                    }
                }
            }
        }
    }
}