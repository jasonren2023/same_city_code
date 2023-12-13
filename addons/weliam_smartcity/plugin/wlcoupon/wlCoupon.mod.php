<?php
defined('IN_IA') or exit('Access Denied');
class wlCoupon
{
    /**
     * 保存超级券母类
     * @access static
     * @name saveCoupon
     * @param mixed  参数一的说明
     * @return array
     */
    static function saveCoupons($coupon , $param = [])
    {
        global $_W;
        if (!is_array($coupon)) return false;
        $coupon['uniacid'] = $_W['uniacid'];
        $coupon['aid']     = $_W['aid'];
        //	$coupon['sid'] = $_W['sid']?$_W['sid']:$coupon['sid'];
        if (empty($param)) {
            pdo_insert(PDO_NAME . 'couponlist' , $coupon);
            return pdo_insertid();
        }
        return false;
    }
    /**
     * 更新超级券母类
     * @access static
     * @name updateCoupons
     * @param $params  修改参数
     * @param $where   修改条件
     * @return array
     */
    static function updateCoupons($params , $where)
    {
        $res = pdo_update(PDO_NAME . 'couponlist' , $params , $where);
        if ($res) {
            return 1;
        }
        else {
            return 0;
        }
    }
    /**
     * 删除超级券母类
     * @access static
     * @name deleteOrder
     * @param $params  修改参数
     * @param $where   修改条件
     * @return array
     */
    static function deleteCoupons($where)
    {
        $res = pdo_delete(PDO_NAME . 'couponlist' , $where);
        if ($res) {
            return 1;
        }
        else {
            return 0;
        }
    }
    /**
     * 删除超级券用户记录
     * @access static
     * @name deleteOrder
     * @param $params  修改参数
     * @param $where   修改条件
     * @return array
     */
    static function deleteCoupon($where)
    {
        $res = pdo_delete(PDO_NAME . 'member_coupons' , $where);
        if ($res) {
            return 1;
        }
        else {
            return 0;
        }
    }
    /**
     * 获取多条超级券母类记录
     * @access static
     * @name getNumGoods
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getNumCoupons($select , $where , $order , $pindex , $psize , $ifpage)
    {
        $goodsInfo = Util::getNumData($select , PDO_NAME . 'couponlist' , $where , $order , $pindex , $psize , $ifpage);
        return $goodsInfo;
    }
    /**
     * 获取单条超级券母类
     * @access static
     * @name getSingleOrder
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getSingleCoupons($id , $select , $where = [])
    {
        $where['id'] = $id;
        return Util::getSingelData($select , PDO_NAME . 'couponlist' , $where);
    }
    /**
     * 储存单条超级券
     * @access static
     * @name getSingleOrder
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function saveMemberCoupons($coupon , $param = [])
    {
        global $_W;
        if (!is_array($coupon)) return false;
        $coupon['uniacid'] = $_W['uniacid'];
        if (empty($param)) {
            pdo_insert(PDO_NAME . 'member_coupons' , $coupon);
            return pdo_insertid();
        }
        return false;
    }
    /**
     * 获取单条超级券数量
     * @access static
     * @name getCouponNum
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getCouponNum($parentid , $type = 1,$mid = 0)
    {
        global $_W;
        if(empty($mid)){
            $mid = $_W['mid'];
        }
        if ($type == 1) {
            $num = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_member_coupons') . " WHERE mid = '{$mid}' AND parentid = {$parentid} AND status in (1,2)");
        } else {
            $num = pdo_fetchcolumn(" SELECT SUM(num) FROM " . tablename(PDO_NAME . "order") . " WHERE mid = {$mid} AND fkid = {$parentid} AND plugin = 'coupon' AND status != 5 AND status != 7 ");
        }
        return $num;
    }
    /**
     * 获取多条超级券
     * @access static
     * @name getNumCoupon
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getNumCoupon($select , $where , $order , $pindex , $psize , $ifpage)
    {
        $orderInfo = Util::getNumData($select , PDO_NAME . 'member_coupons' , $where , $order , $pindex , $psize , $ifpage);
        return $orderInfo;
    }
    /**
     * 获取单条超级券
     * @access static
     * @name getSingleOrder
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getSingleCoupon($id , $select , $where = [])
    {
        $where['id'] = $id;
        return Util::getSingelData($select , PDO_NAME . 'member_coupons' , $where);
    }
    /**
     * 更新超级券
     * @access static
     * @name updateCoupons
     * @param $params  修改参数
     * @param $where   修改条件
     * @return array
     */
    static function updateCoupon($params , $where)
    {
        $res = pdo_update(PDO_NAME . 'member_coupons' , $params , $where);
        if ($res) {
            return 1;
        }
        else {
            return 0;
        }
    }
    /**
     * 保存超级券母类
     * @access static
     * @name saveCoupon
     * @param mixed  参数一的说明
     * @return array
     */
    static function saveCouponOrder($data , $param = [])
    {
        global $_W;
        if (!is_array($data)) return false;
        if (empty($param)) {
            pdo_insert(PDO_NAME . 'order' , $data);
            return pdo_insertid();
        }
        return false;
    }
    /**
     * 获取多条订单记录
     * @access static
     * @name getNumCouponOrder
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getNumCouponOrder($select , $where , $order , $pindex , $psize , $ifpage)
    {
        $goodsInfo = Util::getNumData($select , PDO_NAME . 'order' , $where , $order , $pindex , $psize , $ifpage);
        return $goodsInfo;
    }
    /**
     * 获取多条店铺记录
     * @access static
     * @name getNumCouponOrder
     * @param $where   查询条件
     * @param $select  查询参数
     * @return array
     */
    static function getNumstore($select , $where , $order , $pindex , $psize , $ifpage)
    {
        $goodsInfo = Util::getNumData($select , PDO_NAME . 'merchantdata' , $where , $order , $pindex , $psize , $ifpage);
        return $goodsInfo;
    }
    static function getstores($locations , $lng , $lat)
    {
        global $_W;
        foreach ($locations as $key => $val) {
            $loca                        = unserialize($val['location']);
            $locations[$key]['distance'] = Store::getdistance($loca['lng'] , $loca['lat'] , $lng , $lat);
        }
        //排序
        for ($i = 0 ; $i < count($locations) - 1 ; $i++) {
            for ($j = 0 ; $j < count($locations) - 1 - $i ; $j++) {
                if ($locations[$j]['distance'] > $locations[$j + 1]['distance']) {
                    $temp              = $locations[$j + 1];
                    $locations[$j + 1] = $locations[$j];
                    $locations[$j]     = $temp;
                }
            }
        }
        foreach ($locations as $key => $value) {
            if (!empty($value['distance'])) {
                if ($value['distance'] > 1000) {
                    $locations[$key]['distance'] = (floor(($value['distance'] / 1000) * 10) / 10) . "km";
                }
                else {
                    $locations[$key]['distance'] = round($value['distance']) . "m";
                }
            }
        }
        return $locations;
    }
    /**
     * 异步支付结果回调 ，处理业务逻辑
     * @access public
     * @name
     * @param mixed  参数一的说明
     * @return array
     */
    static function payCouponshargeNotify($params)
    {
        global $_W;
        $order   = pdo_get('wlmerchant_order' , ['orderno' => $params['tid']]);
        $coupons = pdo_get('wlmerchant_couponlist' , ['id' => $order['fkid']]);
        $_W['aid'] = $order['aid'];
        $_W['uniacid'] = $order['uniacid'];
        if ($order['status'] == 0 || $order['status'] == 5) {
            $data1 = self::getCouponshargePayData($params , $order);
            pdo_update(PDO_NAME . 'order' , $data1 , ['id' => $order['id']]);
            Order::createSmallorder($order['id'] , 4);
            //处理分销
            if (p('distribution') && empty($coupons['isdistri'])) {
                $disorderid = Distribution::newDisCore($order['id'],'coupon');
                $data1['disorderid'] = $disorderid;
            }
            //处理业务员佣金
            if (p('salesman')) {
                $data1['salesarray'] = Salesman::saleCore($order['sid'] , 'coupon');
            }
            //支付有礼
            if ($coupons['paidid'] > 0) {
                $data1['paidprid'] = Paidpromotion::getpaidpr(3 , $coupons['paidid'] , $order['mid'],$order['id'],$data1['paytype'] , $order['price'] , $order['num']);
            }
            //添加标签
            if (p('userlabel')) {
                Userlabel::addlabel($order['mid'] , $order['fkid'] , 'coupon');
            }
            if ($coupons['time_type'] == 1) {
                $starttime = $coupons['starttime'];
                $endtime   = $coupons['endtime'];
            }
            else {
                $starttime = time();
                $endtime   = time() + ($coupons['deadline'] * 24 * 3600);
            }
            $data                  = [
                'mid'         => $order['mid'] ,
                'aid'         => $order['aid'] ,
                'parentid'    => $coupons['id'] ,
                'status'      => 1 ,
                'type'        => $coupons['type'] ,
                'title'       => $coupons['title'] ,
                'sub_title'   => $coupons['sub_title'] ,
                'content'     => $coupons['goodsdetail'] ,
                'description' => $coupons['description'] ,
                'color'       => $coupons['color'] ,
                'starttime'   => $starttime ,
                'endtime'     => $endtime ,
                'createtime'  => time() ,
                'orderno'     => $params['tid'] ,
                'price'       => $order['price'] / $order['num'] ,
                'usetimes'    => $coupons['usetimes'] * $order['num'] ,
                'concode'     => 0
            ];
            $data1['recordid']     = self::saveMemberCoupons($data);
            $data1['estimatetime'] = $data['endtime'];
            //计算通知时间
            $data1['remindtime'] = Order::remindTime($data1['estimatetime']);
            pdo_update(PDO_NAME . 'order' , $data1 , ['id' => $order['id']]);
            $newsurplus = $coupons['surplus'] + $order['num'];
            self::updateCoupons(['surplus' => $newsurplus] , ['id' => $coupons['id']]);
            $member = pdo_get('wlmerchant_member' , ['id' => $order['mid']] , ['openid']);
            $openid = $member['openid'];
            //添加用户标签
            $userlable = unserialize($coupons['payonlinelabel']);
            if(!empty($userlable)){
                Member::addUserlable($userlable,$order['mid']);
            }
            //通知商户
            News::addSysNotice($order['uniacid'] , 2 , $order['sid'] , 0 , $order['id']);
            Store::addFans($order['sid'] , $order['mid']);
            News::paySuccess($order['id'] , 'coupon');
        }
    }
    /**
     * 函数的含义说明
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    static function getCouponshargePayData($params , $order_out)
    {
        $data = ['status' => $params['result'] == 'success' ? 1 : 0];
        if ($params['is_usecard'] == 1) {
            $fee                = $params['card_fee'];
            $data['is_usecard'] = 1;
        }
        else {
            $fee = $params['fee'];
        }
        //$paytype = array('credit' => 1, 'wechat' => 2, 'alipay' => 3, 'delivery' => 4, 'wxapp' => 5);
        $data['paytype'] = $params['type'];
        if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
        $data['paytime']     = TIMESTAMP;
        $data['price']       = $fee;
        $data['blendcredit'] = $params['blendcredit'];
        SingleMerchant::updateAmount($fee , $order_out['sid'] , $order_out['id'] , 1 , '卡券订单支付成功');
        return $data;
    }
    /**
     * 函数的含义说明
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    static function payCouponshargeReturn($params)
    {
        $res       = $params['result'] == 'success' ? 1 : 0;
        $order_out = pdo_get(PDO_NAME . 'order' , ['orderno' => $params['tid']] , ['id']);
        wl_message('支付成功' , h5_url('pages/mainPages/paySuccess/paySuccess' , [
            'id'   => $order_out['id'] ,
            'type' => 3
        ]) , 'success');
    }
    //核销记录
    static function hexiaoorder($id , $mid , $num = 1 , $type = 1 , $checkcode = '')
    { //1输码 2扫码 3后台 4密码
        global $_W;
        $coupon = pdo_get(PDO_NAME . 'member_coupons' , ['id' => $id]);
        if ($coupon['orderno']) {
            $order = pdo_get('wlmerchant_order' , ['orderno' => $coupon['orderno']] , ['id' , 'neworderflag' , 'sid']);
        }
        $coupon['usetimes'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_smallorder') . " WHERE plugin = 'coupon' AND  orderid = {$order['id']} AND status = 1");
        if ($coupon['usetimes'] < 1) {
            if (is_mobile()) {
                die(json_encode(['errno' => 1 , 'message' => '该超级券已失效' , 'data' => '']));
            }
            else {
                show_json(0 , '该超级券已失效');
            }
        }
        if ($coupon['usetimes'] < $num) {
            if (is_mobile()) {
                die(json_encode(['errno' => 1 , 'message' => '可核销次数不足' , 'data' => '']));
            }
            else {
                show_json(0 , '可核销次数不足');
            }
        }
        if ($coupon['starttime'] > time()) {
            $starttime = date("Y-m-d H:i:s" , $coupon['starttime']);
            if (is_mobile()) {
                die(json_encode(['errno' => 1 , 'message' => '该超级券在' . $starttime . '后方可使用' , 'data' => '']));
            }
            else {
                show_json(0 , '该超级券在' . $starttime . '后方可使用');
            }
        }
        if ($coupon['endtime'] < time()) {
            if (is_mobile()) {
                die(json_encode(['errno' => 1 , 'message' => '该超级券已过期' , 'data' => '']));
            }
            else {
                show_json(0 , '该超级券已过期');
            }
        }
        if ($order['neworderflag']) {
            if ($checkcode) {
                $smallorders = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_smallorder') . "WHERE plugin = 'coupon' AND  orderid = {$order['id']} AND status = 1 AND checkcode = '{$checkcode}'");
            }
            else {
                $smallorders = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_smallorder') . "WHERE plugin = 'coupon' AND  orderid = {$order['id']} AND status = 1 ORDER BY id ASC LIMIT {$num}");
            }
            if ($smallorders) {
                if ($mid) {
                    $uid = pdo_getcolumn(PDO_NAME . 'merchantuser' , [
                        'storeid' => $order['sid'] ,
                        'mid'     => $mid
                    ] , 'id');
                }
                else {
                    $uid = 0;
                }
                foreach ($smallorders as $k => $small) {
                    $res = Order::finishSmallorder($small['id'] , $uid , $type);
                }
            }
            else {
                if (is_mobile()) {
                    die(json_encode(['errno' => 1 , 'message' => '无可用核销码' , 'data' => '']));
                }
                else {
                    show_json(0 , '无可用核销码');
                }
            }
        }
        else {
            //添加更新
            $arr = [];
            if ($coupon['usedtime']) {
                $a = unserialize($coupon['usedtime']);
                for ($i = 0 ; $i < $num ; $i++) {
                    $arr['time'] = time();
                    $arr['type'] = $type;
                    $arr['ver']  = $mid;
                    $a[]         = $arr;
                }
                $coupon['usedtime'] = serialize($a);
            }
            else {
                $a = [];
                for ($i = 0 ; $i < $num ; $i++) {
                    $arr['time'] = time();
                    $arr['type'] = $type;
                    $arr['ver']  = $mid;
                    $a[]         = $arr;
                }
                $coupon['usedtime'] = serialize($a);
            }
            $params['usetimes'] = $coupon['usetimes'] - $num;
            $params['usedtime'] = $coupon['usedtime'];
            $res                = self::updateCoupon($params , ['id' => $id]);
        }
        if ($res) {
            //发送核销成功通知
            $info = [
                'first'      => '您好，您的卡券已经成功核销' ,
                'goods_name' => $coupon['title'] ,//商品名称
                'goods_num'  => $num ,//商品数量
                'time'       => date('Y-m-d H:i:s' , time()) ,//核销时间
                'order_no'   => $coupon['orderno'] ,//订单编号
                'remark'     => '如有疑问请联系客服'
            ];
            TempModel::sendInit('write_off' , $coupon['mid'] , $info , $_W['source']);
            if ($type == 2) {
                $info2 = [
                    'first'      => '核销操作成功' ,
                    'goods_name' => $coupon['title'] ,//商品名称
                    'goods_num'  => $num ,//商品数量
                    'time'       => date('Y-m-d H:i:s' , time()) ,//核销时间
                    'order_no'   => $coupon['orderno'] ,//订单编号
                    'remark'     => '订单编号:[' . $coupon['orderno'] . ']' ,
                ];
                TempModel::sendInit('write_off' , $_W['mid'] , $info2 , $_W['source']);
            }
            if (empty($order['neworderflag'])) {
                $order = pdo_get('wlmerchant_order' , ['orderno' => $coupon['orderno']] , ['id']);
                $goods = pdo_get('wlmerchant_couponlist' , ['id' => $coupon['parentid']] , ['merchantid' , 'title']);
                SingleMerchant::verifRecordAdd($coupon['aid'] , $goods['merchantid'] , $coupon['mid'] , 'coupon' , $order['id'] , $coupon['concode'] , $goods['title'] , $type , $num);
                if ($params['usetimes'] < 1) {
                    $res2    = pdo_update('wlmerchant_order' , ['status' => 2] , ['orderno' => $coupon['orderno']]);
                    $orderid = pdo_getcolumn(PDO_NAME . 'order' , ['orderno' => $coupon['orderno']] , 'id');
                    //添加结算抢购订单到计划任务
                    $ordertask = [
                        'type'    => 'wlcoupon' ,
                        'orderid' => $orderid
                    ];
                    $ordertask = serialize($ordertask);
                    Queue::addTask(2 , $ordertask , time() , $orderid);
                    $disorderid = pdo_getcolumn('wlmerchant_order' , ['orderno' => $coupon['orderno']] , 'disorderid');
                    if ($disorderid) {
                        $res = pdo_update('wlmerchant_disorder' , ['status' => 1] , [
                            'id'     => $disorderid ,
                            'status' => 0
                        ]);
                        if ($res) {
                            $distask = [
                                'type'    => 'wlcoupon' ,
                                'orderid' => $disorderid
                            ];
                            $distask = serialize($distask);
                            Queue::addTask(3 , $distask , time() , $disorderid);
                        }
                    }
                }
            }
            return 1;
        }
        else {
            return 0;
        }
    }
    //消费截止时间到来提醒
    static function cutoffFollow($id , $mid , $title , $sid , $cutofftime)
    {
        global $_W;
        $settings     = Setting::wlsetting_read('noticeMessage');
        $notice       = unserialize($settings['notice']);
        $where2['id'] = $mid;
        $member       = Util::getSingelData('nickname,openid' , 'wlmerchant_member' , $where2);
        $storename    = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $sid] , 'storename');
        $aid          = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $sid] , 'aid');
        $orderno      = pdo_getcolumn(PDO_NAME . 'member_coupons' , ['id' => $sid] , 'orderno');
        $num          = pdo_getcolumn(PDO_NAME . 'order' , ['orderno' => $orderno] , 'num');
        $order_id     = pdo_getcolumn(PDO_NAME . 'order' , ['orderno' => $orderno] , 'id');
        if (empty($orderno)) {
            $orderno = '免费卡券';
        }
        if (empty($num)) {
            $num = 1;
        }
        $url       = h5_url('pages/subPages/coupon/couponDetails/couponDetails' , [
            'id'       => $id ,
            'order_id' => $order_id
        ]);
        $modelData = [
            'first'   => '您好，您有即将过期的卡券。' ,
            'type'    => '消费提醒' ,//业务类型
            'content' => '您的卡券' . $title . '即将过期，请尽快使用!' ,//业务内容
            'status'  => '截止时间：' . date('Y年m月d日 H:i:s' , $cutofftime) ,//处理结果
            'time'    => date("Y-m-d H:i:s" , time()) ,//操作时间
            'remark'  => '点击立即去消费，赶快行动吧。'
        ];
        TempModel::sendInit('service' , $mid , $modelData , $_W['source'] , $url);
    }
    //退款函数
    static function refund($id , $money = 0 , $unline = '' , $checkcode = '' , $afterid = 0)
    {
        global $_W;
        $item = pdo_get(PDO_NAME . 'order' , ['id' => $id]);
        if ($checkcode) {
            if ($money < 0.01) {
                $smallorder  = pdo_fetch("SELECT orderprice,blendcredit FROM " . tablename(PDO_NAME . "smallorder") . " WHERE plugin = 'coupon' AND orderid = {$id} AND status IN (1,4) AND checkcode = '{$checkcode}'");
                $money       = sprintf("%.2f" , $smallorder['orderprice'] - $smallorder['blendcredit']);
                $blendcredit = $smallorder['blendcredit'];
            }
            $refundnum = 1;
        }
        else if (empty($money)) {
            $money       = pdo_fetchcolumn('SELECT SUM(orderprice) FROM ' . tablename(PDO_NAME . "smallorder") . " WHERE plugin = 'coupon' AND orderid = {$id} AND status IN (1,4)");
            $blendcredit = pdo_fetchcolumn('SELECT SUM(blendcredit) FROM ' . tablename(PDO_NAME . "smallorder") . " WHERE plugin = 'coupon' AND orderid = {$id} AND status IN (1,4)");
            $money       = sprintf("%.2f" , $money - $blendcredit);
            $refundnum   = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "smallorder") . " WHERE plugin = 'coupon' AND orderid = {$id} AND status IN (1,4)");
        }
        else {
            if ($money < $item['blendcredit']) {
                $blendcredit = $money;
                $money       = 0;
            }
            else if ($item['blendcredit'] > 0) {
                $blendcredit = $item['blendcredit'];
                $money       = sprintf("%.2f" , $money - $blendcredit);
            }
            $refundnum = $item['usetimes'];
        }
        if ($money > $item['price']) {
            $money = $item['price'];
        }
        if ($unline) {
            $res['status'] = 1;
        }
        else {
            $res = wlPay::refundMoney($id , $money , '卡券订单退款' , 'coupon' , 2 , $blendcredit);
        }
        if ($res['status']) {
            if ($item['neworderflag']) {
                if ($checkcode) {
                    pdo_update('wlmerchant_smallorder' , [
                        'status'     => 3 ,
                        'refundtime' => time()
                    ] , ['plugin' => 'coupon' , 'orderid' => $id , 'status' => [1 , 4] , 'checkcode' => $checkcode]);
                }
                else if (empty($afterid)) {
                    pdo_update('wlmerchant_smallorder' , [
                        'status'     => 3 ,
                        'refundtime' => time()
                    ] , ['plugin' => 'coupon' , 'orderid' => $id , 'status' => [1 , 4]]);
                }else if($afterid > 0){
                    $afterCheckcode = pdo_getcolumn(PDO_NAME.'aftersale',array('id'=>$afterid),'checkcodes');
                    $afterCheckcode = unserialize($afterCheckcode);
                    pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'coupon','orderid'=>$id,'checkcode' => $afterCheckcode));
                }
                if ($item['applyrefund']) {
                    $reason                   = '买家申请退款。';
                    $orderdata['applyrefund'] = 2;
                }
                else {
                    $reason = '卡券系统退款。';
                }
                $overflag = pdo_get('wlmerchant_smallorder' , [
                    'orderid' => $id ,
                    'plugin'  => 'coupon' ,
                    'status'  => 1
                ] , ['id']);
                if (empty($overflag)) {
                    $hexiao = pdo_get('wlmerchant_smallorder' , [
                        'orderid' => $id ,
                        'plugin'  => 'coupon' ,
                        'status'  => 2
                    ] , ['id']);
                    if ($hexiao) {
                        $orderdata['status']       = 2;
                        $orderdata['issettlement'] = 1;
                        $orderdata['settletime']   = time();
                    }
                    else {
                        $orderdata['status']     = 7;
                        $orderdata['refundtime'] = time();
                    }
                    pdo_update('wlmerchant_order' , $orderdata , ['id' => $item['id']]);
                }
            }
            else {
                if ($item['applyrefund']) {
                    pdo_update('wlmerchant_order' , [
                        'status'      => 7 ,
                        'refundtime'  => time() ,
                        'applyrefund' => 2
                    ] , ['id' => $item['id']]);
                    $reason = '买家申请退款。';
                }
                else {
                    pdo_update('wlmerchant_order' , ['status' => 7 , 'refundtime' => time()] , ['id' => $item['id']]);
                    $reason = '卡券系统退款。';
                }
            }
            pdo_update('wlmerchant_member_coupons' , ['status' => 2 , 'usetimes' => 0] , ['id' => $item['recordid']]);
            $url = h5_url('pages/mainPages/index/diypage?type=5');
            //分销订单退款
            if ($item['disorderid']) {
                Distribution::refunddis($item['disorderid'] , $checkcode);
            }
            if ($item['usecredit']) {
                $refundcredit = sprintf("%.2f",$item['usecredit']/$item['num']*$refundnum);
                $goodname = pdo_getcolumn(PDO_NAME . 'couponlist' , ['id' => $item['fkid']] , 'title');
                Member::credit_update_credit1($item['mid'], $refundcredit, '退款超级券:[' .$goodname. ']订单返还积分');
            }
            News::refundNotice($id , 'coupon' , $money , $reason);
            //恢复商品库存
            pdo_fetch("update" . tablename('wlmerchant_order') . "SET surplus = surplus-{$refundnum} WHERE id = {$id}");
            if ($item['redpackid']) {
                pdo_update('wlmerchant_redpack_records' , [
                    'status'  => 0 ,
                    'usetime' => 0 ,
                    'orderid' => 0 ,
                    'plugin'  => ''
                ] , ['id' => $item['redpackid']]);
            }
        }
        else {
            pdo_fetch("update" . tablename('wlmerchant_order') . "SET failtimes = failtimes+1 WHERE id = {$id}");
        }
        return $res;
    }
    //取消订单
    static function cancelorder($id)
    {
        $order         = pdo_get(PDO_NAME . "order" , ['id' => $id] , [
            'id' ,
            'mid' ,
            'uniacid' ,
            'fkid' ,
            'usecredit' ,
            'redpackid'
        ]);
        $_W['uniacid'] = $order['uniacid'];
        $res           = pdo_update(PDO_NAME . "order" , ['status' => 5] , ['id' => $order['id']]);//更新为已取消
        if ($res) {
            if ($order['redpackid']) {
                pdo_update('wlmerchant_redpack_records' , ['status' => 0] , ['id' => $order['redpackid']]);
            }
            if ($order['usecredit'] > 0) {
                $goodname = pdo_getcolumn(PDO_NAME . 'couponlist' , ['id' => $order['fkid']] , 'title');
                Member::credit_update_credit1($order['mid'] , $order['usecredit'] , '取消超级券:[' . $goodname . ']订单返还积分');
            }
            return true;
        }
    }
    static function doTask()
    {
        global $_W;
        $where  = Queue::getTaskWhere(0);
        $where2 = Queue::getTaskWhere(1);
        //修改超级券的状态
        if (!empty($where2)) {
            pdo_update(PDO_NAME . "couponlist" , ['status' => 2] , [
                'status'      => 1 ,
                'starttime <' => time() ,
                'time_type'   => 1 ,
                'uniacid'     => $where2
            ]);
            pdo_update(PDO_NAME . "couponlist" , ['status' => 2] , [
                'status'    => 1 ,
                'time_type' => 2 ,
                'uniacid'   => $where2
            ]);
            pdo_update(PDO_NAME . "couponlist" , ['status' => 3] , [
                'status'    => 2 ,
                'endtime <' => time() ,
                'time_type' => 1 ,
                'uniacid'   => $where2
            ]);
        }
        else {
            pdo_update(PDO_NAME . "couponlist" , ['status' => 2] , [
                'status'      => 1 ,
                'starttime <' => time() ,
                'time_type'   => 1
            ]);
            pdo_update(PDO_NAME . "couponlist" , ['status' => 2] , ['status' => 1 , 'time_type' => 2]);
            pdo_update(PDO_NAME . "couponlist" , ['status' => 3] , [
                'status'    => 2 ,
                'endtime <' => time() ,
                'time_type' => 1
            ]);
        }
        /*发送即将过期通知*/ //        $cutoffcoupon = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member_coupons')."WHERE usetimes > 1 {$where} AND cutoffnotice = 0 ORDER BY id ASC limit 0,20 ");
        //        if (!empty($cutoffcoupon)) {
        //            foreach ($cutoffcoupon as $k => $v) {
        //                $_W['uniacid'] = $v['uniacid'];
        //                $_W['aid'] = $v['aid'];
        //                $sid = pdo_getcolumn(PDO_NAME . 'couponlist', array('id' => $v['parentid']), 'merchantid');
        //                $cutofftime = $v['endtime'];
        //                $config = Setting::agentsetting_read('coupon');
        //                $cutoff_time = $config['cutoff_time'] ? intval($config['cutoff_time']) : 7;
        //
        //                if ($cutofftime > time() && $cutofftime < (time() + $cutoff_time * 24 * 3600)) {
        //                    self::cutoffFollow($v['id'], $v['mid'], $v['title'], $sid, $cutofftime);
        //                    pdo_update(PDO_NAME . 'member_coupons', array('cutoffnotice' => 1), array('id' => $v['id']));
        //                }
        //            }
        //        }
        //新过期流程兼容
        $noestorder = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_order') . "WHERE plugin = 'coupon' {$where} AND status = 1 AND estimatetime = 0 ORDER BY id DESC limit 20");
        if ($noestorder) {
            foreach ($noestorder as $key => $noest) {
                $estimatetime = pdo_getcolumn(PDO_NAME . 'member_coupons' , ['id' => $noest['recordid']] , 'endtime');
                pdo_update('wlmerchant_order' , ['estimatetime' => $estimatetime] , ['id' => $noest['id']]);
            }
        }
        /*标记过期订单*/
        $nowtime   = time();
        $cutoffcou = pdo_fetchall("SELECT orderno,parentid,uniacid,aid FROM " . tablename('wlmerchant_member_coupons') . "WHERE status = 1 {$where} AND usetimes > 0 AND orderno > 0 AND endtime < {$nowtime} ORDER BY id DESC LIMIT 10");
        if (!empty($cutoffcou)) {
            foreach ($cutoffcou as $key => $cutcou) {
                pdo_update('wlmerchant_member_coupons' , ['status' => 3] , ['orderno' => $cutcou['orderno']]);
                pdo_update('wlmerchant_order' , ['status' => 9] , ['orderno' => $cutcou['orderno']]);
                //自动退款
                $_W['uniacid'] = $cutcou['uniacid'];
                $_W['aid']     = $cutcou['aid'];
                $orderset      = Setting::wlsetting_read('orderset');
                $goods         = pdo_get('wlmerchant_couponlist' , ['id' => $cutcou['parentid']] , ['overrefund']);
                $orderid       = pdo_getcolumn(PDO_NAME . 'order' , ['orderno' => $cutcou['orderno']] , 'id');
                if ($orderset['reovertime'] && $goods['overrefund']) {
                    pdo_update('wlmerchant_order' , ['status' => 6] , ['id' => $orderid]);
                    self::refund($orderid , 0 , 0);
                }
            }
        }
        pdo_fetch("update" . tablename('wlmerchant_couponlist') . "SET name=title WHERE name = 0");
    }

    /**
     * Comment: 卡券信息列表导出
     * Author: zzw
     * Date: 2020/11/9 18:03
     * @param $where
     */
    public static function exportCoupons($where){
        //列表信息获取
        $field = ['status','is_charge','id','merchantid','title','createtime','price','surplus','quantity','get_limit' ,'time_type','starttime','endtime','deadline'];
        $list = pdo_getall(PDO_NAME."couponlist",$where,$field,'','indexorder DESC');
        $newList = [];
        //循环处理信息
        foreach($list as $key => &$value){
            //状态信息
            switch ($value['status']){
                case 0: $value['status'] = '已下架';break;
                case 1: $value['status'] = '未开始';break;
                case 2: $value['status'] = '活动中';break;
                case 3: $value['status'] = '已结束';break;
                case 4: $value['status'] = '已下架';break;
                case 5: $value['status'] = '审核中';break;
                case 6: $value['status'] = '被驳回';break;
                case 8: $value['status'] = '回收站';break;
            }
            //判断是否免费
            if($value['is_charge'] == 0) $value['price'] = '免费';
            //获取商户信息
            $value['storename'] = pdo_getcolumn(PDO_NAME."merchantdata",['id'=>$value['merchantid']],'storename');
            //添加时间
            $value['createtime'] = date("Y-m-d H:i:s",$value['createtime']);
            //有效时间
            if($value['time_type'] == 1) $value['time'] = date("Y-m-d",$value['starttime']) .' ~ '. date("Y-m-d",$value['endtime']);
            else $value['time'] = "领取后{$value['deadline']}天内";
            //库存信息
            $value['stk'] = "{$value['quantity']}/{$value['surplus']}";
            //限购信息
            if($value['get_limit']> 0){
                $value['get_limit'] = $value['get_limit'].'张';
            } else{
                $value['get_limit'] = '不限量';
            }
            //删除多余的信息
            unset($value['is_charge'],$value['merchantid'],$value['time_type'],$value['starttime'],$value['endtime'],$value['deadline'] ,$value['surplus'] ,$value['quantity']);
            //由于排序问题 需要重新生成新的数组  否则顺序不能进行自定义
            $newList[] = [
                'id'         => $value['id'],
                'title'      => $value['title'],
                'storename'  => $value['storename'],
                'price'      => $value['price'] ,
                'stk'        => $value['stk'] ,
                'limit'      => $value['get_limit'],
                'time'       => $value['time'] ,
                'status'     => $value['status'] ,
                'mobile'     => ''
            ];
        }
        //标题内容
        $filter = [
            'id'         => 'ID',
            'title'      => '标题' ,
            'storename'  => '店铺' ,
            'price'      => '价格' ,
            'stk'        => '总数量/已售数量' ,
            'limit'      => '每人限量' ,
            'time'       => '使用期限' ,
            'status'     => '状态' ,
            'mobile'     => '发放手机号'
        ];

        util_csv::export_csv_2($newList, $filter, '卡券列表.csv');
        exit();
    }


    /**
     * Comment: 卡券发放
     * Author: wlf
     * Date: 2021/08/31 18:00
     * @param $where
     */
    public static function coupon_send($user,$coupons){
        global $_W;
        if($coupons['get_limit'] > 0){
            $num = wlCoupon::getCouponNum($coupons['id'],1,$user);
            if($num > $coupons['get_limit'] || $num == $coupons['get_limit']){
                return array(
                    'errno' => 1,
                    'message' => '获取数量超过限制',
                );
            }
        }
        if($coupons['status'] != 1 && $coupons['status'] != 2){
            return array(
                'errno' => 1,
                'message' => '卡券所在状态无法发放',
            );
        }

        if ($coupons['time_type'] == 1) {
            $starttime = $coupons['starttime'];
            $endtime = $coupons['endtime'];
        } else {
            $starttime = time();
            $endtime = time() + ($coupons['deadline'] * 24 * 3600);
        }

        if(empty($coupons['is_charge'])){
            $coupons['price'] = 0;
            $settlementmoney = 0;
        }else{
            //结算金额
            $settlementmoney = Store::getsettlementmoney(4,$coupons['id'],1,$coupons['merchantid'],0);
        }
        //生成领取订单
        $orderdata = array(
            'uniacid' => $coupons['uniacid'],
            'mid' => $user,
            'aid' => $coupons['aid'],
            'fkid' => $coupons['id'],
            'sid' => $coupons['merchantid'],
            'status' => 1,
            'paytype' => 6,
            'createtime' => time(),
            'orderno' => createUniontid(),
            'price' => 0,
            'num' => 1,
            'plugin' => 'coupon',
            'payfor' => 'couponsharge',
            'vipbuyflag' => 0,
            'goodsprice' => $coupons['price'],
            'settlementmoney' => $settlementmoney,
            'neworderflag' => 1,
            'buyremark'  => '后台卡券发放',
            'paytime'    => time()
        );
        $orderid = wlCoupon::saveCouponOrder($orderdata);
        Order::createSmallorder($orderid,4);
        //生成卡券
        $data = array(
            'mid' => $user,
            'aid' => $coupons['aid'],
            'parentid' => $coupons['id'],
            'status' => 1,
            'type' => $coupons['type'],
            'title' => $coupons['title'],
            'sub_title' => $coupons['sub_title'],
            'content' => $coupons['goodsdetail'],
            'description' => $coupons['description'],
            'color' => $coupons['color'],
            'starttime' => $starttime,
            'endtime' => $endtime,
            'createtime' => time(),
            'usetimes' => $coupons['usetimes'],
            'concode' => 0,
            'uniacid' => $coupons['uniacid'],
            'orderno' => $orderdata['orderno']
        );
        $res = pdo_insert(PDO_NAME . 'member_coupons', $data);
        $couponUserId = pdo_insertid();
        $newdata['recordid'] = $couponUserId;
        $newdata['estimatetime'] = $data['endtime'];
        pdo_update(PDO_NAME.'order',$newdata, array('id' => $orderid)); //更新订单状态
        if($res){
            //修改卡券的已售数量
            $newsurplus = $coupons['surplus'] + 1;
            wlCoupon::updateCoupons(array('surplus' => $newsurplus), array('id' => $coupons['id']));
            //发送模板消息
            $first = '一个卡券已经发放';
            $type = '卡券发放通知';
            $content = '卡券名:['.$coupons['title'].']';
            $newStatus = '已到账';
            $remark = '点击查看卡券';
            $url = h5_url('pages/subPages/coupon/coupon');
            News::jobNotice($user,$first,$type,$content,$newStatus,$remark,time(),$url);
            return array(
                'errno' => 0,
                'message' => '发放成功',
            );
        }else{
            return array(
                'errno' => 1,
                'message' => '发放失败',
            );
        }

    }










}
