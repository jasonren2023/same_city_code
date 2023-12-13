<?php
defined('IN_IA') or exit('Access Denied');

class Groupon {
    /**
     * 初始化商品数据
     *
     * @access static
     * @name  initSingleGoods
     * @param $goodsInfo  商品数据
     * @return $goodsInfo
     */
    static function initSingleGoods($goodsInfo) {
        $goodsInfo['plugin'] = 'groupon';
        $goodsInfo['a'] = h5_url('pages/subPages/goods/index',['id'=>$goodsInfo['id'],'type'=>2]);
        return $goodsInfo;
    }

    /**
     * 删除商品
     *
     * @access static
     * @name deleteActive
     * @param $params  修改参数
     * @param $where   修改条件
     * @return array
     */
    static function deleteGoods($where) {
        $res = pdo_delete(PDO_NAME . 'goodshouse', $where);
        if ($where['id']) Cache::deleteCache('goods', $where['id']);
        if ($res) {
            return 1;
        } else {
            return 0;
        }
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
     * 保存团购活动
     *
     * @access static
     * @name savegrouponActive
     * @param mixed  参数一的说明
     * @return array
     */
    static function savegrouponActive($active, $param = array()) {
        global $_W;
        if (!is_array($active)) return FALSE;
        $active['uniacid'] = $_W['uniacid'];
        $active['createtime'] = time();
        if (empty($param)) {
            pdo_insert(PDO_NAME . 'groupon_activity', $active);
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
        $activeInfo = Util::getNumData($select, PDO_NAME . 'groupon_activity', $where, $order, $pindex, $psize, $ifpage);
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
        $goodsInfo = Util::getSingelData($select, PDO_NAME . 'groupon_activity', $where);
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
        $res = pdo_update(PDO_NAME . 'groupon_activity', $params, $where);
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
        $res = pdo_delete(PDO_NAME . 'groupon_activity', $where);
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


    /*******************************************************以下为团购订单方法*************************************************************************/
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
        $data = Util::getSingelData($select, PDO_NAME . 'order', $where);
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
        $where['plugin'] = 'groupon';
        $orderInfo = Util::getNumData($select, PDO_NAME . 'order', $where, $order, $pindex, $psize, $ifpage);
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
        $active = self::getSingleActive($orderInfo['fkid'], '*');
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
        $orderInfo['plugin'] = 'groupon';
        $orderInfo['goodsprice'] = sprintf("%.2f", $orderInfo['price'] / $orderInfo['num']);
        if ($orderInfo['specid']) {
            $orderInfo['optiontitle'] = pdo_getcolumn(PDO_NAME . 'goods_option', array('id' => $orderInfo['specid']), 'title');
        }
        $record = pdo_get(PDO_NAME . 'groupon_userecord', array('orderid' => $orderInfo['id']), array('usedtime', 'qrcode'));
        $orderInfo['checkcode'] = $record['qrcode'];
        $orderInfo['usedtime'] = $record['usedtime'];
        //文本化状态


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
        $res = pdo_update(PDO_NAME . 'order', $params, $where);
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
        $res = pdo_delete(PDO_NAME . 'order', $where);
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
    static function paygrouponOrderNotify($params) {
        global $_W;
        Util::wl_log('payResult_notify', PATH_PLUGIN . "groupon/data/", $params); //写入异步日志记录
        $order_out = pdo_fetch("select * from" . tablename(PDO_NAME . 'order') . "where orderno='{$params['tid']}'");
        $activeInfo = self::getSingleActive($order_out['fkid'], '*');
        $_W['uniacid'] = $order_out['uniacid'];
        $_W['wlsetting']['creditset'] = Setting::wlsetting_read('creditset');
        $data = self::getgrouponOrderPayData($params, $order_out); //得到支付参数，处理代付
        if ($order_out['status'] == 0 || $order_out['status'] == 5){
            pdo_update(PDO_NAME . 'order', $data, array('orderno' => $params['tid'])); //更新订单状态
            //支付返现的信息处理
            if(p('cashback')){
                Cashback::record($order_out['id'],'groupon');
            }
            $orderid = $order_out['id'];
            $_W['aid'] = $order_out['aid'];
            //创建记录
            if (empty($order_out['expressid'])) {
                if($order_out['neworderflag'] && empty($activeInfo['pftid'])){
                    Order::createSmallorder($order_out['id'],2);
                }
            } else {
                $data['status'] = 8;
            }
            //处理分销
            if($order_out['cerditmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                $nodis = 1;
            }else{
                $nodis = 0;
            }
            if (p('distribution') && empty($activeInfo['isdistri']) && empty($order_out['drawid']) && empty($nodis)) {
                if ($order_out['specid']) {
                    $option = pdo_get('wlmerchant_goods_option', array('id' => $order_out['specid']), array('disarray'));
                    $activeInfo['disarray'] = WeliamWeChat::mergeDisArray($option['disarray'],$activeInfo['disarray']);
                }
                $disarray = unserialize($activeInfo['disarray']);
                $disprice = sprintf("%.2f",$order_out['goodsprice'] - $order_out['vipdiscount']);
                $disorderid = Distribution::disCore($order_out['mid'], $disprice, $disarray, $order_out['num'], $threemoney, $order_out['id'], 'groupon', $activeInfo['dissettime'],$activeInfo['isdistristatus']);
                $data['disorderid'] = $disorderid;
            }
            //抽奖领取
            if($order_out['drawid'] > 0){
                pdo_update('wlmerchant_draw_record',array('is_get' => 2),array('id' => $order_out['drawid']));
            }

            //支付有礼
            if($activeInfo['paidid'] > 0){
                $data['paidprid'] = Paidpromotion::getpaidpr(4,$activeInfo['paidid'],$order_out['mid'],$order_out['id'],$data['paytype'],$order_out['price'],$order_out['num']);
            }
            //处理业务员佣金
            if(p('salesman')){
                $data['salesarray'] = Salesman::saleCore($order_out['sid'],'groupon');
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
            //计算通知时间
            $data['remindtime'] = Order::remindTime($data['estimatetime']);
            pdo_update(PDO_NAME . 'order', $data, array('orderno' => $params['tid'])); //更新订单状态
            //卡密商品
            if($activeInfo['usestatus'] == 3){
                $rushtask = array(
                    'plugin'  => 'groupon',
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
                    $pftinfo['memo'] = $order_out['buyremark'];
                    $pftorderinfo = Pftapimod::pftOrderSubmit($pftinfo);
                    if(empty($pftorderinfo['UUerrorcode'])){
                        $pftorderinfo = serialize($pftorderinfo);
                        pdo_update(PDO_NAME . 'order', ['pftorderinfo' => $pftorderinfo], array('orderno' => $params['tid'])); //更新订单状态
                        //订单信息查询
                        $moreinfo = Pftapimod::pftOrderQuery($pftorderinfo['UUordernum']);
                        if(empty($moreinfo['UUerrorcode'])){
                            $pftchangeinfo = [
                                'estimatetime' => strtotime($moreinfo['UUendtime']),
                            ];
                            pdo_update(PDO_NAME . 'order',$pftchangeinfo, array('orderno' => $params['tid'])); //更新订单状态
                        }
                    }else{
                        Util::wl_log('pftNewError.log',PATH_DATA,$pftorderinfo); //写入异步日志记录
                        //添加提交订单到计划任务
                        $rushtask = array(
                            'type'    => 'groupon',
                            'orderid' => $order_out['id']
                        );
                        $rushtask = serialize($rushtask);
                        Queue::addTask(9, $rushtask, time(), $order_out['id']);
                    }
                }else if ($activeInfo['threestatus'] == 1){
                    $yqdorderno = 'wl_'.$order_out['orderno'];
                    $callurl = $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/pftapimod/yqdAsyNotify.php';
                    $yqddata =  [
                        'commodityId' => $activeInfo['pftid'],
                        'external_orderno' => $yqdorderno,
                        'buyCount' => $order_out['num'],
                        'remark' => $order_out['buyremark'],
                        'callbackUrl' => $callurl,
                        'externalSellPrice' => $order_out['goodsprice'],
                        'template' => $pftinfo
                    ];
                    $yqdInfo = Pftapimod::yqdOrderSubmit($yqddata);
                    if($yqdInfo['code'] != '200'){
                        //充值失败，通知用户
                        pdo_update(PDO_NAME . 'order',['status' => 6], array('orderno' => $params['tid'])); //更新订单状态
                        $first = '很抱歉，您购买的商品['.$activeInfo['name'].']下单失败';
                        $type = '下单失败通知';
                        $status = '退款中';
                        $remark = '失败原因:'.$yqdInfo['msg'];
                        $content = '很抱歉，请您下单重试';
                        News::jobNotice($order_out['mid'],$first,$type,$content,$status,$remark,time());
                    }else{
                        pdo_update(PDO_NAME . 'order',['status' => 3], array('orderno' => $params['tid'])); //更新订单状态
                    }
                }
            }
            //添加用户标签
            $userlable = unserialize($activeInfo['userlabel']);
            if(!empty($userlable)){
                Member::addUserlable($userlable,$order_out['mid']);
            }
            //通知商户
            News::addSysNotice($order_out['uniacid'],2,$order_out['sid'],0,$order_out['id']);
            Store::addFans($order_out['sid'], $order_out['mid']);
            News::paySuccess($order_out['id'], 'groupon');
            //小票打印
            Order::sendPrinting($order_out['id'],'groupon');
        }
    }

    //创建到店消费记录
    static function createRecord($orderid, $num) {
        global $_W;
        $record['uniacid'] = $_W['uniacid'];
        $record['aid'] = $_W['aid'];
        $record['orderid'] = $orderid;
        $record['qrcode'] = Util::createConcode(5);
        $record['createtime'] = time();
        $record['usetimes'] = $num;
        pdo_insert(PDO_NAME . 'groupon_userecord', $record);
        return pdo_insertid();
    }

    /**
     * 函数的含义说明
     *
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    static function paygrouponOrderReturn($params, $backurl = false) {
        Util::wl_log('payResult_return', PATH_PLUGIN . "groupon/data/", $params);//写入日志记录
        $order_out = pdo_get(PDO_NAME . 'order', array('orderno' => $params['tid']), array('id'));
        wl_message('团购成功',h5_url('pages/mainPages/paySuccess/paySuccess',['id'=>$order_out['id'],'type'=>2]), 'success');
    }

    /**
     * 函数的含义说明
     *
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    static function getgrouponOrderPayData($params, $order_out) {
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
        SingleMerchant::updateAmount($fee, $order_out['sid'], $order_out['id'], 1, '订单支付成功');
        return $data;
    }

    static function cutoffFollow($id, $mid, $orderid, $sid) {
        global $_W;
        $settings = Setting::wlsetting_read('noticeMessage');
        $notice = unserialize($settings['notice']);
        $where2['id'] = $mid;
        $member = Util::getSingelData('nickname,openid', 'wlmerchant_member', $where2);
        $goods = groupon::getSingleActive($id, "name,cutofftime,cutoffstatus,cutoffday");
        $storename = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $sid), 'storename');
        $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['id'=>$orderid,'plugin'=>'groupon']);
        $order = pdo_get(PDO_NAME . 'order', array('id' => $orderid), array('orderno', 'num', 'paytime'));
        if ($goods['cutoffstatus']) {
            $cutofftime = $order['paytime'] + $goods['cutoffday'] * 24 * 3600;
        } else {
            $cutofftime = $goods['cutofftime'];
        }
        $modelData = [
            'first'   => '您好，您有即将过期的待消费订单。' ,
            'type'    => '消费提醒' ,//业务类型
            'content' => '您的商品'.$goods['name'].'即将过期，请尽快使用!' ,//业务内容
            'status'  => '截止时间：' . date('Y年m月d日 H:i:s', $cutofftime) ,//处理结果
            'time'    => date("Y-m-d H:i:s",time()) ,//操作时间
            'remark'  => '点击立即去消费，赶快行动吧。'
        ];
        TempModel::sendInit('service',$mid,$modelData,$_W['source'],$url);

    }


    //核销订单流程
    static function hexiaoorder($id, $mid, $num = 1, $type = 1,$checkcode='') {  //1输码 2扫码 3后台 4密码
        global $_W;
        $order = pdo_get('wlmerchant_order', array('id' => $id));
        $cutoff = pdo_get(PDO_NAME . 'groupon_activity', array('id' => $order['fkid']), array('cutofftime', 'cutoffstatus', 'cutoffday'));
        if($order['neworderflag']){
            $order['usetimes'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE plugin = 'groupon' AND  orderid = {$id} AND status = 1");
        }else{
            $record = pdo_get('wlmerchant_groupon_userecord', array('id' => $order['recordid']));
        }
        if ($order['estimatetime'] < time()) {
            if (is_mobile()) {
                die(json_encode(array("errno" => 1, 'message' => '已超过截止日期，无法核销','data'=>'')));
            } else {
                show_json(0, '已超过截止日期，无法核销');
            }
        }
        if ($order['status'] != 1) {
            if (is_mobile()) {
                die(json_encode(array('errno' => 1, 'message' => '核销失败,订单已核销','data'=>'')));
            } else {
                show_json(0, '核销失败,订单已核销');
            }
        }
        if($order['neworderflag']){
            if($checkcode){
                $smallorders = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_smallorder')."WHERE plugin = 'groupon' AND  orderid = {$id} AND status = 1 AND checkcode = '{$checkcode}'");
            }else{
                $smallorders = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_smallorder')."WHERE plugin = 'groupon' AND  orderid = {$id} AND status = 1 ORDER BY id ASC LIMIT {$num}");
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
                    'type' => 'groupon',
                    'orderid' => $order['id']
                );
                $ordertask = serialize($ordertask);
                Queue::addTask(2, $ordertask, time(), $order['id']);
                if ($order['disorderid']) {
                    $res = pdo_update('wlmerchant_disorder', array('status' => 1), array('id' => $order['disorderid'], 'status' => 0));
                    if ($res) {
                        $distask = array(
                            'type' => 'groupon',
                            'orderid' => $order['disorderid']
                        );
                        $distask = serialize($distask);
                        Queue::addTask(3, $distask, time(), $order['disorderid']);
                    }
                }
            }
            $res = pdo_update('wlmerchant_groupon_userecord', $params, array('id' => $record['id']));
        }
        if ($res) {
            $active = pdo_get('wlmerchant_groupon_activity', array('id' => $order['fkid']), array('name'));
            $order['checkcode'] = pdo_getcolumn(PDO_NAME . 'groupon_userecord', array('id' => $order['recordid']), 'qrcode');
            SingleMerchant::verifRecordAdd($order['aid'], $order['sid'], $order['mid'], 'groupon', $order['id'], $order['checkcode'], $active['name'], $type, $num);
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

    //退款订单
    static function refund($id, $money = 0, $unline = '',$checkcode = '',$afterid = 0) {
        global $_W;
        $order = pdo_get(PDO_NAME . 'order', array('id' => $id));

        //票付通
        if(!empty($order['pftorderinfo'])){
            $pftinfo = unserialize($order['pftorderinfo']);
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
                $renum = $pftorderinfo['UUorigin_num'] - $pftorderinfo['UUverified_num'] - $pftorderinfo['UUrefund_num'];
                $pftnum = 0;
            }
            $pftrefund = Pftapimod::pftOrderRefund(['ordern' => $pftinfo['UUordernum'],'num' => $pftnum]);
            if(!empty($pftrefund['UUerrorcode'])){
                $res['status']  = false;
                $res['message'] = "票务平台退款错误:".$pftrefund['UUerrorinfo'];
                return $res;
            }
        }

        if($checkcode){
            if($money<0.01) {
                $smallorder = pdo_fetch("SELECT orderprice,blendcredit FROM ".tablename(PDO_NAME . "smallorder")." WHERE plugin = 'groupon' AND orderid = {$id} AND status IN (1,4) AND checkcode = '{$checkcode}'");
                $money = sprintf("%.2f",$smallorder['orderprice'] - $smallorder['blendcredit']);
                $blendcredit = $smallorder['blendcredit'];
            }
            $refundnum = 1;
        }else if(empty($money)){
            $money =  pdo_fetchcolumn('SELECT SUM(orderprice) FROM ' . tablename(PDO_NAME . "smallorder") . " WHERE plugin = 'groupon' AND orderid = {$id} AND status NOT IN (1,4)");
            $blendcredit = pdo_fetchcolumn('SELECT SUM(blendcredit) FROM ' . tablename(PDO_NAME . "smallorder") . " WHERE plugin = 'groupon' AND orderid = {$id} AND status IN (1,4)");
            $money = sprintf("%.2f",$money - $blendcredit);
            $refundnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME . "smallorder")." WHERE plugin = 'groupon' AND orderid = {$id} AND status NOT IN (2,3)");
        }else if(!empty($order['pftorderinfo'])){
            $money = sprintf("%.2f",$order['actualprice']/$order['num']*$renum);
            $refundnum = $renum;
        }else{
            if($money < $order['blendcredit']){
                $blendcredit = $money;
                $money = 0;
            }else if($order['blendcredit'] > 0){
                $blendcredit = $order['blendcredit'];
                $money = sprintf("%.2f",$money - $blendcredit);
            }
            $refundnum = $order['usetimes'];
        }
        if ($unline) {
            $res['status'] = 1;
        } else {
            $res = wlPay::refundMoney($id, $money, '团购订单退款', 'groupon', 2,$blendcredit);
        }
        if ($res['status']) {
            if($order['neworderflag']){
                if($checkcode){
                    pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'groupon','orderid'=>$id,'status'=> array(1,4),'checkcode'=>$checkcode));
                }else if(empty($afterid)){
                    pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'groupon','orderid'=>$id,'status'=> array(1,4)));
                }else if($afterid > 0){
                    $afterCheckcode = pdo_getcolumn(PDO_NAME.'aftersale',array('id'=>$afterid),'checkcodes');
                    $afterCheckcode = unserialize($afterCheckcode);
                    pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'groupon','orderid'=>$id,'checkcode' => $afterCheckcode));
                }
                if ($order['applyrefund']) {
                    $reason = '买家申请退款。';
                    $orderdata['applyrefund'] = 2;
                } else {
                    $reason = '团购系统退款。';
                }
                $overflag = pdo_get('wlmerchant_smallorder',array('orderid' => $id,'plugin'=>'groupon','status'=>1),array('id'));
                if(empty($overflag)){
                    $hexiao = pdo_get('wlmerchant_smallorder',array('orderid' => $id,'plugin'=>'groupon','status'=>2),array('id'));
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
            }else if(!empty($order['pftorderinfo'])){
                //查看订单状态
                $Npftorderinfo = Pftapimod::pftOrderQuery($pftinfo['UUordernum']);
                if($Npftorderinfo['UUstatus'] == 1){
                    $orderdata['status'] = 2;
                    $orderdata['issettlement'] = 1;
                    $orderdata['settletime'] = time();
                    //Store::ordersettlement($order['id']);
                }else{
                    $orderdata['status'] = 7;
                    $orderdata['refundtime'] = time();
                }
                if ($order['applyrefund']) {
                    $reason = '买家申请退款。';
                    $orderdata['applyrefund'] = 2;
                } else {
                    $reason = '抢购系统退款。';
                }
                pdo_update('wlmerchant_rush_order',$orderdata, array('id' => $order['id']));
            }else{
                if ($order['applyrefund']) {
                    pdo_update('wlmerchant_order', array('status' => 7, 'refundtime' => time(), 'applyrefund' => 2), array('id' => $order['id']));
                    $reason = '买家申请退款。';
                } else {
                    pdo_update('wlmerchant_order', array('status' => 7, 'refundtime' => time()), array('id' => $order['id']));
                    $reason = '团购系统退款。';
                }
            }
            $url = h5_url('pages/subPages/orderList/orderList',['staus'=>7]);
            //分销订单退款
            if ($order['disorderid']) {
                Distribution::refunddis($order['disorderid'],$checkcode);
            }
            //修改已抢完状态
            $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $order['fkid']),array('status','name','starttime','endtime'));
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
                pdo_update('wlmerchant_groupon_activity', array('status' => $changestatus), array('id' => $order['fkid']));
            }
            News::refundNotice($id,'groupon',$money,$reason);
            if ($order['usecredit']) {
                $refundcredit = sprintf("%.2f",$order['usecredit']/$order['num']*$refundnum);
                Member::credit_update_credit1($order['mid'], $refundcredit, '退款团购商品:[' . $goods['name'] . ']订单返还积分');
            }
            if($order['redpackid'] && $orderdata['status'] == 7){
                pdo_update('wlmerchant_redpack_records',array('status' => 0,'usetime' =>0,'orderid' => 0,'plugin' =>''),array('id' => $order['redpackid']));
            }
        } else {
            pdo_fetch("update" . tablename('wlmerchant_order') . "SET failtimes = failtimes+1 WHERE id = {$id}");
        }
        return $res;
    }

    //取消订单
    static function cancelorder($id) {
        global $_W;
        $order = pdo_get(PDO_NAME . "order", array('id' => $id), array('id','mid','uniacid','num', 'fkid','usecredit','specid','redpackid'));
        $_W['uniacid'] = $order['uniacid'];
        $res = pdo_update(PDO_NAME . "order", array('status' => 5), array('id' => $order['id']));//更新为已取消
        if ($res) {
            if($order['redpackid']){
                pdo_update('wlmerchant_redpack_records',['status' => 0],['id' => $order['redpackid']]);
            }
            if ($order['usecredit'] > 0) {
                $goodname = pdo_getcolumn(PDO_NAME . 'groupon_activity', array('id' => $order['fkid']), 'name');
                Member::credit_update_credit1($order['mid'], $order['usecredit'], '取消团购商品:[' . $goodname . ']订单返还积分');
            }
            //修改已抢完状态
            $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $order['fkid']),array('status','starttime','endtime'));
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
                pdo_update('wlmerchant_groupon_activity', array('status' => $changestatus), array('id' => $order['fkid']));
            }
            return TRUE;
        }

    }

    static function doTask() {
        global $_W, $_GPC;
        $nowtime = time();
        $where = Queue::getTaskWhere(0);
        $where2 = Queue::getTaskWhere(1);
        /*自动退款*/
        $refundOrders = pdo_fetchall("select id,price,aid,uniacid from" . tablename(PDO_NAME . "order") . "where plugin = 'groupon' {$where} and status = 6 and price > 0 and failtimes < 4 limit 0,10");
        if (!empty($refundOrders)) {
            foreach ($refundOrders as $k => $v) {
                $_W['aid'] = $v['aid'];
                $_W['uniacid'] = $v['uniacid'];
                self::refund($v['id']);
            }
        }
        /*团购状态*/
        if(!empty($where2)){
            $activitys1 = pdo_getall(PDO_NAME . "groupon_activity", array('starttime <' => time(), 'status' => 1,'uniacid'=>$where2), array('id'));
        }else{
            $activitys1 = pdo_getall(PDO_NAME . "groupon_activity", array('starttime <' => time(), 'status' => 1), array('id'));
        }
        if (!empty($activitys1)) {
            foreach ($activitys1 as $k => $v) {
                pdo_update(PDO_NAME . "groupon_activity", array('status' => 2), array('id' => $v['id']));
            }
        }
        if(!empty($where2)) {
            $activitys2 = pdo_getall(PDO_NAME . "groupon_activity", array('endtime <' => time(), 'status' => 2,'uniacid'=>$where2), array('id'));
        }else{
            $activitys2 = pdo_getall(PDO_NAME . "groupon_activity", array('endtime <' => time(), 'status' => 2), array('id'));
        }
        if (!empty($activitys2)) {
            foreach ($activitys2 as $k => $v) {
                pdo_update(PDO_NAME . "groupon_activity", array('status' => 4), array('id' => $v['id']));
            }
        }

        //新过期预计时间的兼容
        $noestorder = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_order') . "WHERE plugin = 'groupon' {$where} AND status = 1 AND estimatetime = 0 ORDER BY id DESC limit 20");
        if ($noestorder) {
            foreach ($noestorder as $key => $noest) {
                $activity = pdo_get('wlmerchant_groupon_activity', array('id' => $noest['activityid']), array('cutoffstatus', 'cutofftime', 'cutoffday'));
                if ($activity['cutoffstatus']) {
                    $estimatetime = $noest['paytime'] + $activity['cutoffday'] * 86400;
                } else {
                    $estimatetime = $activity['cutofftime'];
                }
                pdo_update('wlmerchant_order', array('estimatetime' => $estimatetime), array('id' => $noest['id']));
            }
        }

        //新的过期流程
        $actorder3 = pdo_fetchall("SELECT id,fkid,uniacid,aid FROM " . tablename('wlmerchant_order') . "WHERE plugin = 'groupon' {$where} AND status = 1 AND estimatetime < {$nowtime} AND estimatetime > 0 ORDER BY id DESC LIMIT 10");
        if (!empty($actorder3)) {
            foreach ($actorder3 as $key => $actor3) {
                pdo_update('wlmerchant_order', array('status' => 9, 'overtime' => time()), array('id' => $actor3['id']));
                //自动退款
                $_W['uniacid'] = $actor3['uniacid'];
                $_W['aid'] = $actor3['aid'];
                $orderset = Setting::wlsetting_read('orderset');
                $goods = pdo_get('wlmerchant_groupon_activity', array('id' => $actor3['fkid']), array('overrefund'));
                if ($orderset['reovertime'] && $goods['overrefund'] ) {
                    pdo_update('wlmerchant_order', array('status' => 6), array('id' => $actor3['id']));
                    self::refund($actor3['id'],0,0);
                }
            }
        }

    }

    //结算过期订单
    static function settover($over, $overstatus) {
        global $_W;
        //判断是否已结算
        $flag = pdo_get(PDO_NAME . 'autosettlement_record', array('orderno' => $over['orderno']), array('id'));
        $orderset = Setting::wlsetting_read('orderset');
        if (empty($flag)) {
            $record = pdo_get('wlmerchant_groupon_userecord', array('id' => $over['recordid']));
            if($orderset['overmoney'] == 2){
                $settnum = $over['num'];
            }else{
                $settnum = intval($over['num'] - $record['usetimes']);
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
                $price = round($over['price'] * $settnum / $over['num'], 2);
                $settlementmoney = round($over['settlementmoney'] * $settnum / $over['num'], 2);
                $agentmoney = round($price - $settlementmoney - $distrimoney, 2);
            } else {
                $agentmoney = 0;
                $settlementmoney = 0;
            }
            //处理资金流向
            if($orderset['overmoney'] == 1){
                $agentmoney = round($over['price'] - $settlementmoney - $distrimoney, 2);
            }
            $data = array(
                'uniacid'       => $_W['uniacid'],
                'aid'           => $over['aid'],
                'type'          => 10,
                'merchantid'    => $over['sid'],
                'orderid'       => $over['id'],
                'orderno'       => $over['orderno'],
                'goodsid'       => $over['fkid'],
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
                    Store::addcurrent(1, 10, $over['sid'], $settlementmoney, $change['merchantnowmoney'], $over['id']);
                }
                if ($data['agentmoney']) {
                    pdo_fetch("update" . tablename('wlmerchant_agentusers') . "SET allmoney=allmoney+{$data['agentmoney']},nowmoney=nowmoney+{$data['agentmoney']} WHERE id = {$data['aid']}");
                    $change['agentnowmoney'] = pdo_getcolumn(PDO_NAME . 'agentusers', array('id' => $data['aid']), 'nowmoney');
                    Store::addcurrent(2, 10, $over['aid'], $data['agentmoney'], $change['agentnowmoney'], $over['id']);
                }
                pdo_update('wlmerchant_autosettlement_record', $change, array('id' => $settlementid));
                pdo_update('wlmerchant_order', array('issettlement' => 1), array('id' => $over['id']));
                //写入结算日志记录
            }
        }

    }

}