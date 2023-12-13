<?php
defined('IN_IA') or exit('Access Denied');

class Wlfightgroup {
    //保存分类
    static function saveCategory($category) {
        global $_W;
        if (!is_array($category))
            return FALSE;
        $category['uniacid'] = $_W['uniacid'];
        $category['aid'] = $_W['aid'];
        pdo_insert(PDO_NAME . 'fightgroup_category', $category);
        return pdo_insertid();
    }

    //获取分类列表
    static function getNumCategory($select, $where, $order, $pindex, $psize, $ifpage) {
        $goodsInfo = Util::getNumData($select, PDO_NAME . 'fightgroup_category', $where, $order, $pindex, $psize, $ifpage);
        return $goodsInfo;
    }

    //获取单个分类
    static function getCategory($id) {
        $res = pdo_get('wlmerchant_fightgroup_category', array('id' => $id));
        return $res;
    }

    //更新分类
    static function updateCategory($category, $id) {
        global $_W;
        if (!is_array($category))
            return FALSE;
        $res = pdo_update('wlmerchant_fightgroup_category', $category, array('id' => $id));
        return $res;
    }

    //删除分类
    static function deteleCategory($id) {
        $res = pdo_delete('wlmerchant_fightgroup_category', array('id' => $id));
        return $res;
    }

    //获取仓库商品
    static function getHouseGoods($id, $select, $where = array()) {
        $where['id'] = $id;
        $goodsInfo = Util::getSingelData($select, PDO_NAME . 'goodshouse', $where);
        return $goodsInfo;
    }

    //获取多条商户记录
    static function getNumMerchant($select, $where, $order, $pindex, $psize, $ifpage) {
        $merchantInfo = Util::getNumData($select, PDO_NAME . 'merchantdata', $where, $order, $pindex, $psize, $ifpage);
        return $merchantInfo;
    }

    //获取单条商户记录
    static function getSingleMerchant($id, $select, $where = array()) {
        $where['id'] = $id;
        return Util::getSingelData($select, PDO_NAME . 'merchantdata', $where);
    }

    //获取单条商品记录
    static function getSingleGood($id, $select, $where = array()) {
        $where['id'] = $id;
        $goodsInfo = Util::getSingelData($select, PDO_NAME . 'fightgroup_goods', $where);
        return $goodsInfo;
    }

    //保存商品
    static function saveGoods($goods) {
        global $_W;
        if (!is_array($goods))
            return FALSE;
        $goods['uniacid'] = $_W['uniacid'];
        pdo_insert(PDO_NAME . 'fightgroup_goods', $goods);
        return pdo_insertid();
    }

    //更新商品
    static function updateGoods($goods, $id) {
        global $_W;
        if (!is_array($goods))
            return FALSE;
        $res = pdo_update('wlmerchant_fightgroup_goods', $goods, array('id' => $id));
        return $res;
    }

    //获取商品列表
    static function getNumGoods($select, $where, $order, $pindex, $psize, $ifpage) {
        $goodsInfo = Util::getNumData($select, PDO_NAME . 'fightgroup_goods', $where, $order, $pindex, $psize, $ifpage);
        return $goodsInfo;
    }

    //删除商品
    static function deteleGoods($id) {
        $res = pdo_delete('wlmerchant_fightgroup_goods', array('id' => $id));
        return $res;
    }

    //恢复商品
    static function recoveryGoods($id) {
        $res = pdo_update('wlmerchant_fightgroup_goods', array('status' => 1), array('id' => $id));
        return $res;
    }

    //获取订单列表
    static function getNumOrder($select, $where, $order, $pindex, $psize, $ifpage) {
        $goodsInfo = Util::getNumData($select, PDO_NAME . 'order', $where, $order, $pindex, $psize, $ifpage);
        return $goodsInfo;
    }

    //获取单条订单记录
    static function getSingleOrder($id, $select, $where = array()) {
        $where['id'] = $id;
        return Util::getSingelData($select, PDO_NAME . 'order', $where);
    }

    //获取组团列表
    static function getNumGroup($select, $where, $order, $pindex, $psize, $ifpage) {
        $goodsInfo = Util::getNumData($select, PDO_NAME . 'fightgroup_group', $where, $order, $pindex, $psize, $ifpage);
        return $goodsInfo;
    }

    //获取单条团记录
    static function getSingleGroup($id, $select, $where = array()) {
        $where['id'] = $id;
        return Util::getSingelData($select, PDO_NAME . 'fightgroup_group', $where);
    }

    //创建订单
    static function saveFightOrder($data, $param = array()) {
        global $_W;
        if (!is_array($data))
            return FALSE;
        if (empty($param)) {
            pdo_insert(PDO_NAME . 'order', $data);
            return pdo_insertid();
        }
        return FALSE;
    }

    //新建团
    static function saveFightGroup($data, $param = array()) {
        global $_W;
        if (!is_array($data))
            return FALSE;
        if (empty($param)) {
            $data['uniacid'] = $_W['uniacid'];
            pdo_insert(PDO_NAME . 'fightgroup_group', $data);
            return pdo_insertid();
        }
        return FALSE;
    }

    //保存运费模板
    static function saveExpress($data) {
        global $_W;
        if (!is_array($data))
            return FALSE;
        $data['uniacid'] = $_W['uniacid'];
        $data['aid'] = $_W['aid'];
        pdo_insert(PDO_NAME . 'express_template', $data);
        return pdo_insertid();
    }

    //更新运费模板
    static function updateExpress($data, $id) {
        global $_W;
        if (!is_array($data))
            return FALSE;
        $res = pdo_update('wlmerchant_express_template', $data, array('id' => $id));
        return $res;
    }

    //获取运费模板列表
    static function getNumExpress($select, $where, $order, $pindex, $psize, $ifpage) {
        $goodsInfo = Util::getNumData($select, PDO_NAME . 'express_template', $where, $order, $pindex, $psize, $ifpage);
        return $goodsInfo;
    }

    //删除运费模板
    static function deteleExpress($id) {
        $res = pdo_delete('wlmerchant_express_template', array('id' => $id));
        return $res;
    }

    //创建到店消费记录
    static function createRecord($orderid, $num) {
        global $_W;
        $data['uniacid'] = $_W['uniacid'];
        $data['orderid'] = $orderid;
        $data['qrcode'] = Util::createConcode(5);
        $data['createtime'] = time();
        $data['usetimes'] = $num;
        pdo_insert(PDO_NAME . 'fightgroup_userecord', $data);
        return pdo_insertid();
    }

    //保存虚拟客户
    static function createfalsemember($imgs, $names) {
        global $_W;
        $success = $fail = 0;
        $member['uniacid'] = $_W['uniacid'];
        $member['aid'] = $_W['aid'];
        $len = count($imgs);
        for ($k = 0; $k < $len; $k++) {
            $member['avatar'] = $imgs[$k];
            $member['nickname'] = $names[$k];
            $member['createtime'] = time();
            $res = pdo_insert(PDO_NAME . 'fightgroup_falsemember', $member);
            if ($res) {
                $success++;
            } else {
                $fail++;
            }
        }
        $arr = array('success' => $success, 'fail' => $fail);
        return $arr;
    }

    //获取所有虚拟客户
    static function getNumfalsemember($select, $where, $order, $pindex, $psize, $ifpage) {
        $goodsInfo = Util::getNumData($select, PDO_NAME . 'fightgroup_falsemember', $where, $order, $pindex, $psize, $ifpage);
        return $goodsInfo;
    }

    //获取一个虚拟用户
    static function getSingleFalsemember($id, $select, $where = array()) {
        $where['id'] = $id;
        return Util::getSingelData($select, PDO_NAME . 'fightgroup_falsemember', $where);
    }


    //异步支付结果回调 ，处理业务逻辑
    static function payFightshargeNotify($params) {
        global $_W;
        Util::wl_log('payResult_notify', PATH_PLUGIN . "wlfightgroup/data/", $params); //写入异步日志记录
        $order = pdo_get('wlmerchant_order', array('orderno' => $params['tid']));
        $_W['uniacid'] = $order['uniacid'];
        $_W['aid'] = $order['aid'];
        $_W['wlsetting']['creditset'] = Setting::wlsetting_read('creditset');
        if ($order['status'] == 0 || $order['status'] == 5) {
            if ($order['specid']) {
                $option = pdo_get('wlmerchant_goods_option', array('id' => $order['specid']), array('stock', 'onedismoney','disarray'));
            }
            $good = pdo_get('wlmerchant_fightgroup_goods', array('id' => $order['fkid']));
            $data1 = self::getFightshargePayData($params, $order);
            //处理业务员佣金
            if(p('salesman')){
                $data1['salesarray'] = Salesman::saleCore($order['sid'],'fightgroup');
            }
            //抽奖领取
            if($order['drawid'] > 0){
                pdo_update('wlmerchant_draw_record',array('is_get' => 2),array('id' => $order['drawid']));
            }
            //支付有礼
            if($good['paidid'] > 0){
                $data1['paidprid'] = Paidpromotion::getpaidpr(2,$good['paidid'],$order['mid'],$order['id'],$data1['paytype'],$order['price'],$order['num']);
            }
            //过期时间
            if ($order['expressid']) {
                $data1['estimatetime'] = 2147483647;
            } else {
                if ($good['cutoffstatus']) {
                    $data1['estimatetime'] = time() + $good['cutoffday'] * 86400;
                } else {
                    $data1['estimatetime'] = $good['cutofftime'];
                }
            }
            //计算通知时间
            $data1['remindtime'] = Order::remindTime($data1['estimatetime']);
            pdo_update(PDO_NAME . 'order', $data1, array('id' => $order['id']));
            if ($order['fightstatus'] == 1) {
                if ($order['fightgroupid']) {
                    $group = pdo_get('wlmerchant_fightgroup_group', array('id' => $order['fightgroupid']));
                    if ($group['status'] == 1) {
                        $havenum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_order')." WHERE fightgroupid = {$group['id']} AND uniacid = {$_W['uniacid']} AND paytime > 1");
                        $newlack = $group['neednum'] - $havenum;
                        if ($newlack > 0) {
                            $newdata['lacknum'] = $newlack;
                        } else {
                            $newdata['lacknum'] = $newlack;
                            $newdata['status'] = 2;
                            $newdata['successtime'] = time();
                            $orders = pdo_getall('wlmerchant_order', array('fightgroupid' => $group['id'], 'uniacid' => $group['uniacid'], 'aid' => $group['aid'], 'status' => 1));

                            if($group['is_lucky'] > 0){
                                $allorderids = array_column($orders,'id');
                                $luckykey = array_rand($allorderids,$good['luckynum']);
                                if($good['luckynum']>1){
                                    foreach ($luckykey as $lid){
                                        $luckyids[] =  $allorderids[$lid];
                                    }
                                }else{
                                    $luckyids[] = $allorderids[$luckykey];
                                }
                                $newdata['luckyorderids'] = serialize($luckyids);
                            }


                            foreach ($orders as $key => $or) {
                                if(empty($luckyids) || in_array($or['id'],$luckyids)){
                                    if ($or['expressid']) {
                                        $res = pdo_update(PDO_NAME . 'order', array('status' => 8), array('id' => $or['id']));
                                        $member = pdo_get('wlmerchant_member', array('id' => $or['mid']), array('openid'));
                                    } else {
                                        if($or['neworderflag']){
                                            Order::createSmallorder($or['id'],3);
                                            pdo_update(PDO_NAME . 'order', array('status' => 1), array('id' => $or['id']));
                                            //卡密商品
                                            if($good['usestatus'] == 3){
                                                $rushtask = array(
                                                    'plugin'  => 'wlfightgroup',
                                                    'orderid' => $or['id']
                                                );
                                                $rushtask = serialize($rushtask);
                                                Queue::addTask(10, $rushtask, time(), $or['id']);
                                            }
                                        }else{
                                            $recordid = self::createRecord($or['id'], $or['num']);
                                            $res = pdo_update(PDO_NAME . 'order', array('status' => 1, 'recordid' => $recordid), array('id' => $or['id']));
                                        }
                                        $member = pdo_get('wlmerchant_member', array('id' => $or['mid']), array('openid'));
                                    }
                                    //处理分销
                                    if($or['cerditmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                                        $nodis = 1;
                                    }else{
                                        $nodis = 0;
                                    }
                                    if (p('distribution') && empty($good['isdistri']) && empty($or['drawid']) && empty($nodis)) {
                                        $disorderid = Distribution::newDisCore($or['id'],'fightgroup');
                                        $res      = pdo_update(PDO_NAME . 'order' , [
                                            'disorderid'   => $disorderid,
                                        ] , ['id' => $or['id']]);
                                    }
                                    //小票打印
                                    Order::sendPrinting($or['id'],'wlfightgroup');
                                }else{
                                    //修改为待退款并且加入计划任务
                                    if($good['luckymoney'] > 0) {
                                        pdo_update(PDO_NAME . 'order', ['status' => 6, 'redpagstatus' => 1], ['id' => $or['id']]);
                                    }else{
                                        pdo_update(PDO_NAME . 'order', ['status' => 6], ['id' => $or['id']]);
                                    }
                                }
                            }
                        }
                        pdo_update(PDO_NAME . 'fightgroup_group', $newdata, array('id' => $order['fightgroupid']));
                        if($newdata['status'] == 2){
                            News::groupresult($order['fightgroupid']);
                        }
                    } else {
                        $newgroupflag = 1;
                    }
                } else {
                    $newgroupflag = 1;
                }
                if ($newgroupflag) {
                    $group = array(
                        'status'    => 1,
                        'goodsid'   => $order['fkid'],
                        'aid'       => $good['aid'],
                        'sid'       => $good['merchantid'],
                        'neednum'   => $good['peoplenum'],
                        'lacknum'   => $good['peoplenum'] - 1,
                        'starttime' => time(),
                        'failtime'  => time() + $good['grouptime'] * 3600,
                        'is_lucky'  => $good['is_lucky']
                    );
                    $fightgroupid = self::saveFightGroup($group);
                    pdo_update(PDO_NAME . 'order', array('fightgroupid' => $fightgroupid), array('id' => $order['id']));
                }
            } else {
                if ($order['expressid']) {
                    pdo_update(PDO_NAME . 'order', array('status' => 8), array('id' => $order['id']));
                } else {
                    if($order['neworderflag']){
                        Order::createSmallorder($order['id'],3);
                        pdo_update(PDO_NAME . 'order', array('status' => 1), array('id' => $order['id']));
                        //卡密商品
                        if($good['usestatus'] == 3){
                            $rushtask = array(
                                'plugin'  => 'wlfightgroup',
                                'orderid' => $order['id']
                            );
                            $rushtask = serialize($rushtask);
                            Queue::addTask(10, $rushtask, time(), $order['id']);
                        }
                    }else{
                        $recordid = self::createRecord($order['id'], $order['num']);
                        pdo_update(PDO_NAME . 'order', array('status' => 1, 'recordid' => $recordid), array('id' => $order['id']));
                    }
                }

                //处理分销
                if($order['cerditmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                    $nodis = 1;
                }else{
                    $nodis = 0;
                }
                if (p('distribution') && empty($good['isdistri']) && empty($order['drawid']) && empty($nodis)) {
                    $disorderid = Distribution::newDisCore($order['id'],'fightgroup');
                    $res      = pdo_update(PDO_NAME . 'order' , [
                        'disorderid'   => $disorderid,
                    ] , ['id' => $order['id']]);
                }
                //小票打印
                Order::sendPrinting($order['id'],'wlfightgroup');
            }

            //添加用户标签
            $userlable = unserialize($good['userlabel']);
            if(!empty($userlable)){
                Member::addUserlable($userlable,$order['mid']);
            }

            //通知商户
            News::addSysNotice($order['uniacid'],2,$order['sid'],0,$order['id']);
            Store::addFans($order['sid'], $order['mid']);
            News::paySuccess($order['id'], 'wlfightgroup');
        }
    }

    //异步支付结果回调 更新商家可结算金额
    static function getFightshargePayData($params, $order_out) {
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
        SingleMerchant::updateAmount($fee, $order_out['sid'], $order_out['id'], 1, '拼团订单支付成功');
        return $data;
    }

    //异步支付结果回调 处理用户界面
    static function payFightshargeReturn($params) {
        $res = $params['result'] == 'success' ? 1 : 0;
        $order = pdo_get('wlmerchant_order', array('orderno' => $params['tid']), array('id'));
        wl_message('支付成功', h5_url('pages/mainPages/paySuccess/paySuccess',['id'=>$order['id'],'type'=>2]) , 'success');
    }

    static function hexiaoorder($id, $mid, $num = 1, $type = 1,$checkcode='') {//1输码 2扫码 3后台 4密码
        global $_W;
        $item = pdo_get('wlmerchant_order', array('id' => $id), array('orderno','estimatetime', 'recordid', 'mid', 'neworderflag','fkid', 'id', 'aid', 'sid', 'disorderid'));
        if($item['neworderflag']){
            $record['usetimes'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE plugin = 'wlfightgroup' AND  orderid = {$id} AND status = 1");
        }else{
            $record = pdo_get('wlmerchant_fightgroup_userecord', array('id' => $item['recordid']));
        }
        if ($item['estimatetime'] < time()) {
            if (is_mobile()) {
                die(json_encode(array("errno" => 1, 'msg' => '订单已过期，无法核销','data'=>'')));
            } else {
                show_json(0, '订单已过期，无法核销');
            }
        }
        if ($record['usetimes'] < $num) {
            if (is_mobile()) {
                die(json_encode(array("errno" => 1, 'msg' => '使用次数不足，无法核销','data'=>'')));
            } else {
                show_json(0, '使用次数不足，无法核销');
            }
        }
        if($item['neworderflag']){
            if($checkcode){
                $smallorders = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_smallorder')."WHERE plugin = 'wlfightgroup' AND  orderid = {$id} AND status = 1 AND checkcode = '{$checkcode}'");
            }else{
                $smallorders = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_smallorder')."WHERE plugin = 'wlfightgroup' AND  orderid = {$id} AND status = 1 ORDER BY id ASC LIMIT {$num}");
            }
            if($smallorders){
                if($mid){
                    $uid = pdo_getcolumn(PDO_NAME.'merchantuser',array('storeid'=>$item['sid'],'mid'=>$mid),'id');
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
            $arr = array();
            if ($record['usedtime']) {
                $record['usedtime'] = unserialize($record['usedtime']);
                $a = $record['usedtime'];
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
            $data['usetimes'] = $record['usetimes'] - $num;
            $data['usedtime'] = $record['usedtime'];
            $res = pdo_update('wlmerchant_fightgroup_userecord', $data, array('id' => $item['recordid']));
        }
        if ($res) {
            $goods = pdo_get('wlmerchant_fightgroup_goods', array('id' => $item['fkid']), array('name'));
            News::writeOffSuccess($item['mid'],$goods['name'],$num,$item['orderno']);
            if ($type == 2) {
                News::writeOffSuccess($_W['mid'],$goods['name'],$num,$item['orderno']);
            }
            SingleMerchant::verifRecordAdd($item['aid'], $item['sid'], $item['mid'], 'wlfightgroup', $item['id'], $record['qrcode'], $goods['name'], $type, $num);
            if ($data['usetimes'] == 0 || $data['usetimes'] < 0) {
                $surplus = pdo_fetchcolumn("SELECT count(*) FROM " . tablename('wlmerchant_smallorder') ."WHERE plugin = 'wlfightgroup' AND  orderid = {$id} AND status = 1 ");
                if($surplus <= 0){
                    pdo_update('wlmerchant_order' , ['status' => 2] , ['id' => $id]);
                }
                //添加结算抢购订单到计划任务
                $ordertask = array(
                    'type'    => 'wlfightgroup',
                    'orderid' => $id
                );
                $ordertask = serialize($ordertask);
                Queue::addTask(2, $ordertask, time(), $id);
                if ($item['disorderid']) {
                    $res = pdo_update('wlmerchant_disorder', array('status' => 1), array('id' => $item['disorderid'], 'status' => 0));
                    if ($res) {
                        $distask = array(
                            'type'    => 'wlfightgroup',
                            'orderid' => $item['disorderid']
                        );
                        $distask = serialize($distask);
                        Queue::addTask(3, $distask, time(), $item['disorderid']);
                    }
                }
            }
            return 1;
        } else {
            return 0;
        }
    }

    //退款
    static function refund($id, $money=0, $unline = '',$checkcode = '',$afterid = 0) {
        global $_W;
        $order = pdo_get('wlmerchant_order', array('id' => $id));
        if($checkcode){
            if($money<0.01) {
                $smallorder = pdo_fetch("SELECT orderprice,blendcredit FROM ".tablename(PDO_NAME . "smallorder")." WHERE plugin = 'wlfightgroup' AND orderid = {$id} AND status IN (1,4) AND checkcode = '{$checkcode}'");
                $money = sprintf("%.2f",$smallorder['orderprice'] - $smallorder['blendcredit']);
                $blendcredit = $smallorder['blendcredit'];
            }
            $refundnum = 1;
        }else if(empty($money) && $order['neworderflag']){
            $money = pdo_fetchcolumn('SELECT SUM(orderprice) FROM ' . tablename(PDO_NAME . "smallorder") . " WHERE plugin = 'wlfightgroup' AND orderid = {$id} AND status IN (1,4)");
            $blendcredit = pdo_fetchcolumn('SELECT SUM(blendcredit) FROM ' . tablename(PDO_NAME . "smallorder") . " WHERE plugin = 'wlfightgroup' AND orderid = {$id} AND status IN (1,4)");
            $money = sprintf("%.2f",$money - $blendcredit);
            $refundnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME . "smallorder")." WHERE plugin = 'wlfightgroup' AND orderid = {$id} AND status IN (1,4)");
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
            $res = wlPay::refundMoney($order['id'], $money, '代理后台退款', 'wlfightgroup', 2,$blendcredit);
        }
        if ($res['status']) {
            if($order['neworderflag']){
                if($checkcode){
                    pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'wlfightgroup','orderid'=>$id,'status'=> array(1,4),'checkcode'=>$checkcode));
                }else if(empty($afterid)){
                    pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'wlfightgroup','orderid'=>$id,'status'=> array(1,4)));
                }else if($afterid > 0){
                    $afterCheckcode = pdo_getcolumn(PDO_NAME.'aftersale',array('id'=>$afterid),'checkcodes');
                    $afterCheckcode = unserialize($afterCheckcode);
                    pdo_update('wlmerchant_smallorder', array('status' => 3, 'refundtime' => time()),array('plugin' => 'wlfightgroup','orderid'=>$id,'checkcode' => $afterCheckcode));
                }
                if ($order['applyrefund']) {
                    $reason = '买家申请退款。';
                    $orderdata['applyrefund'] = 2;
                } else {
                    $reason = '拼团系统退款。';
                }
                $overflag = pdo_get('wlmerchant_smallorder',array('orderid' => $id,'plugin'=>'wlfightgroup','status'=>1),array('id'));
                if(empty($overflag)){
                    $hexiao = pdo_get('wlmerchant_smallorder',array('orderid' => $id,'plugin'=>'wlfightgroup','status'=>2),array('id'));
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
            }else{
                if ($order['applyrefund']) {
                    pdo_update('wlmerchant_order', array('status' => 7, 'refundtime' => time(), 'applyrefund' => 2), array('id' => $order['id']));
                    $reason = '买家申请退款。';
                } else {
                    pdo_update('wlmerchant_order', array('status' => 7, 'refundtime' => time()), array('id' => $order['id']));
                    $reason = '拼团系统退款。';
                }
            }


            $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['id'=>$id,'plugin'=>'wlfightgroup']);
            $money = $money ? $money : $order['price'];
            $member = pdo_get('wlmerchant_member', array('id' => $order['mid']), array('openid', 'uid'));
            $openid = $member['openid'];
            if ($order['card_id']) {
                $remark = '订单退款恢复抵扣积分';
                Member::credit_update_credit1($order['mid'], $order['card_id'], $remark);
            }
            //分销订单退款
            if ($order['disorderid']) {
                Distribution::refunddis($order['disorderid'],$checkcode);
            }
            //回复库存
            if($order['redpackid']){
                pdo_update('wlmerchant_redpack_records',array('status' => 0,'usetime' =>0,'orderid' => 0,'plugin' =>''),array('id' => $order['redpackid']));
            }
            News::refundNotice($id,'wlfightgroup',$money,$reason);
        } else {
            pdo_fetch("update" . tablename('wlmerchant_order') . "SET failtimes = failtimes+1 WHERE id = {$id}");
        }
        return $res;
    }

    //取消订单
    static function cancelorder($id){
        $order = pdo_get(PDO_NAME . "order", array('id' => $id), array('id','mid','uniacid','fkid','usecredit','redpackid'));
        $_W['uniacid'] = $order['uniacid'];
        $res = pdo_update(PDO_NAME . "order", array('status' => 5), array('id' => $order['id']));//更新为已取消
        if ($res) {
            if($order['redpackid']){
                pdo_update('wlmerchant_redpack_records',['status' => 0],['id' => $order['redpackid']]);
            }
            if ($order['usecredit'] > 0) {
                $goodname = pdo_getcolumn(PDO_NAME . 'fightgroup_goods', array('id' => $order['fkid']),'name');
                Member::credit_update_credit1($order['mid'], $order['usecredit'], '取消拼团商品:[' . $goodname . ']订单返还积分');
            }
            return TRUE;
        }
    }

    //计划任务
    static function doTask() {
        global $_W;
        $where = Queue::getTaskWhere(0);
        $where2 = Queue::getTaskWhere(1);
        load()->library('phpexcel/PHPExcel');
        //组团失败，自动修改订单状态，发送组团失败消息
        if(!empty($where2)){
            $groups = pdo_getall('wlmerchant_fightgroup_group', array('status' => 1, 'failtime <' => time(),'uniacid' => $where2));
        }else{
            $groups = pdo_getall('wlmerchant_fightgroup_group', array('status' => 1, 'failtime <' => time()));
        }
        foreach ($groups as $key => $v) {
            $_W['uniacid'] = $v['uniacid'];
            $goods = self::getSingleGood($v['goodsid'], 'stock,luckymoney,luckynum,usestatus,realsalenum,is_imitate,isdistri,disarray,dissettime,isdistristatus');
            if($goods['is_imitate']){   //模拟成团
                $lacknum = $v['lacknum'];
                //获取虚拟用户信息
                $start = rand(1, 2580 - $lacknum);
                $memberxls = PATH_WEB . "resource/download/members.xlsx";
                $objPHPExcel = PHPExcel_IOFactory::load($memberxls);
                $sheet = $objPHPExcel->getSheet(0);
                $highestColumn = $sheet->getHighestColumn();
                for ($row = $start; $row < $lacknum + $start; $row++) {
                    $members[] = $sheet->rangeToArray("A" . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
                }
                for ($i = 0; $i < $lacknum; $i++) {
                    $data = array(
                        'uniacid'      => $v['uniacid'],
                        'mid'          => 0,
                        'aid'          => $v['aid'],
                        'fkid'         => $v['goodsid'],
                        'sid'          => $v['sid'],
                        'status'       => 3,
                        'paytype'      => 2,
                        'createtime'   => time(),
                        'orderno'      => '666666',
                        'price'        => 0,
                        'num'          => 0,
                        'plugin'       => 'wlfightgroup',
                        'payfor'       => 'fightsharge',
                        'spec'         => '',
                        'fightstatus'  => 1,
                        'fightgroupid' => $v['id'],
                        'expressid'    => '',
                        'buyremark'    => '',
                        'name'         => $members[$i][0][0],
                        'buyremark'    => $members[$i][0][1]
                    );
                    Wlfightgroup::saveFightOrder($data);
                }
                $newdata['lacknum'] = 0;
                $newdata['status'] = 2;
                $newdata['successtime'] = time();
                $orders = pdo_getall('wlmerchant_order', array('fightgroupid' => $v['id'], 'uniacid' => $v['uniacid'], 'aid' => $v['aid'], 'status' => 1));
                //幸运团
                if($v['is_lucky'] > 0){
                    $allorderids = array_column($orders,'id');
                    $luckykey = array_rand($allorderids,$goods['luckynum']);
                    if($goods['luckynum']>1){
                        foreach ($luckykey as $lid){
                            $luckyids[] =  $allorderids[$lid];
                        }
                    }else{
                        $luckyids[] = $allorderids[$luckykey];
                    }
                    $newdata['luckyorderids'] = serialize($luckyids);
                }

                foreach ($orders as $key => $or) {
                    if($or['orderno'] != '666666'){
                        if(empty($luckyids) || in_array($or['id'],$luckyids)){
                            if ($or['expressid']) {
                                pdo_update(PDO_NAME . 'order', array('status' => 8), array('id' => $or['id']));
                            } else {
                                Order::createSmallorder($or['id'], 3);
                                pdo_update(PDO_NAME . 'order', array('status' => 1), array('id' => $or['id']));
                                //卡密商品
                                if($goods['usestatus'] == 3){
                                    $rushtask = array(
                                        'plugin'  => 'wlfightgroup',
                                        'orderid' => $or['id']
                                    );
                                    $rushtask = serialize($rushtask);
                                    Queue::addTask(10, $rushtask, time(), $or['id']);
                                }
                            }
                            //处理分销
                            if($or['cerditmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                                $nodis = 1;
                            }else{
                                $nodis = 0;
                            }
                            if (p('distribution') && empty($goods['isdistri']) && empty($or['drawid']) && empty($nodis)) {
                                $disorderid = Distribution::newDisCore($or['id'],'fightgroup');
                                pdo_update(PDO_NAME . 'order' , ['disorderid'   => $disorderid,] , ['id' => $or['id']]);
                            }
                        }else{
                            //修改为待退款并且加入计划任务
                            if($goods['luckymoney'] > 0) {
                                pdo_update(PDO_NAME . 'order', ['status' => 6, 'redpagstatus' => 1], ['id' => $or['id']]);
                            }else{
                                pdo_update(PDO_NAME . 'order', ['status' => 6], ['id' => $or['id']]);
                            }
                        }
                    }
                }
                $res = pdo_update(PDO_NAME . 'fightgroup_group', $newdata, array('id' => $v['id']));
                if ($res) {
                    News::groupresult($v['id']);
                }
            }else{
                pdo_update('wlmerchant_fightgroup_group', array('status' => 3), array('id' => $v['id']));
                $orders = pdo_getall('wlmerchant_order', array('fightgroupid' => $v['id'], 'status' => 1));
                $num = 0;
                foreach ($orders as $key => $order) {
                    pdo_update('wlmerchant_order', array('status' => 6), array('id' => $order['id']));
                    $num = $num + $order['num'];
                    if ($orders['specid']) {
                        $optionstock = pdo_getcolumn(PDO_NAME . 'goods_option', array('id' => $orders['specid']), 'stock');
                        $newstock = $optionstock + $num;
                        pdo_update('wlmerchant_goods_option', array('stock' => $newstock), array('id' => $orders['specid']));
                    }
                }
                if (empty($goods['specstatus'])) {
                    $updata['stock'] = $num + $goods['stock'];
                }
                News::groupresult($v['id']);
                $updata['realsalenum'] = $goods['realsalenum'] - $num;
                pdo_update('wlmerchant_fightgroup_goods', $updata, array('id' => $v['goodsid']));
            }
        }

        //组团失败，自动退款
        $remoneyorders = pdo_fetchall("SELECT mid,id,price,card_id,failtimes,aid,uniacid FROM " . tablename('wlmerchant_order') . "WHERE status = 6 {$where} AND plugin = 'wlfightgroup' AND failtimes < 3 ORDER BY id DESC");
        foreach ($remoneyorders as $key => $or) {
            $_W['uniacid'] = $or['uniacid'];
            $_W['aid'] = $v['aid'];
            self::refund($or['id']);
        }

        //自动上下架商品
        $nowtime = time();
        $startgoods = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_fightgroup_goods') . "WHERE status = 1 {$where} AND limitstarttime < {$nowtime} AND limitendtime > {$nowtime} ORDER BY id DESC");
        if ($startgoods) {
            foreach ($startgoods as $key => $start) {
                pdo_update('wlmerchant_fightgroup_goods', array('status' => 2), array('id' => $start['id']));
            }
        }
        $endgoods = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_fightgroup_goods') . "WHERE status = 2 {$where} AND limitendtime < {$nowtime} ORDER BY id DESC");
        if ($endgoods) {
            foreach ($endgoods as $key => $end) {
                pdo_update('wlmerchant_fightgroup_goods', array('status' => 4), array('id' => $end['id']));
            }
        }

        //订单过期
        $nowtime = time();
        $overorders = pdo_fetchall("SELECT id,fkid,uniacid,aid FROM " . tablename('wlmerchant_order') . "WHERE status = 1 {$where} AND estimatetime < {$nowtime} AND estimatetime > 0 AND plugin = 'wlfightgroup' ORDER BY id DESC LIMIT 10");
        if ($overorders) {
            foreach ($overorders as $key => $over) {
                pdo_update('wlmerchant_order', array('status' => 9, 'overtime' => time()), array('id'=>$over['id']));
                $_W['uniacid'] = $over['uniacid'];
                $_W['aid'] = $over['aid'];
                $orderset = Setting::wlsetting_read('orderset');
                $goods = pdo_get('wlmerchant_fightgroup_goods', array('id' => $over['fkid']), array('overrefund'));
                if ($orderset['reovertime'] && $goods['overrefund'] ) {
                    pdo_update('wlmerchant_order', array('status' => 6), array('id' => $over['id']));
                    self::refund($over['id'],0,0);
                }
            }
        }

    }
}

?> 
