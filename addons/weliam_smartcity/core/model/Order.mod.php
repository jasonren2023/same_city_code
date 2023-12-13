<?php
defined('IN_IA') or exit('Access Denied');

class Order {
    protected static $name = 'order';//表名称
    protected static $rush = 'rush_order';//表名称
    protected static $tableName;//完整的表名称表名称
    protected static $rushTableName;//完整的表名称表名称
    /**
     * 构造方法
     * Order constructor.
     */
    public function __construct () {
        self::$tableName = tablename(PDO_NAME.self::$name);
        self::$rushTableName = tablename(PDO_NAME.self::$rush);
    }
    /**
     * Comment: 根据条件获取商品的销量
     * Author: zzw
     * Date: 2019/7/12 18:38
     * @param string $params
     * @param bool   $isRush
     * @return mixed
     */
    public function getPurchaseQuantity($params = '',$isRush = false){
        if($isRush){
            $sql = 'SELECT SUM(num) FROM '.self::$rushTableName;
        }else{
            $sql = 'SELECT SUM(num) FROM '.self::$tableName;
        }
        !empty($params) && $sql .= " WHERE {$params} ";
        return pdo_fetchcolumn($sql);
    }
    public function createSmallorder($orderid,$type){
        if($type == 1){  //抢购订单
            $order = pdo_get(PDO_NAME.self::$rush,array('id' => $orderid),array('orderno','activityid','optionid','username','actualprice','uniacid','mid','mobile','sid','aid','num','settlementmoney','blendcredit','vip_card_id'));
            $blendcredit = sprintf("%.2f",$order['blendcredit']/$order['num']);
            $settmoney = sprintf("%.2f",$order['settlementmoney']/$order['num']);
            if($order['vip_card_id'] > 0){
                $vipCardPrice = pdo_getcolumn(PDO_NAME.'halfcard_type',array('id'=>$order['vip_card_id']),'price');
                $order['actualprice'] = $order['actualprice'] - $vipCardPrice;
            }
            $orderprice = sprintf("%.2f",$order['actualprice']/$order['num']);
            $plugin = 'rush';
            $gid = $order['activityid'];
            $specid = $order['optionid'];
            $gInfo = pdo_get(PDO_NAME.'rush_activity',array('id'=>$order['activityid']),['cutoffstatus','cutoffday','cutofftime','appointstatus','name','checkcodeflag']);
            $appointstatus = $gInfo['appointstatus'];
            $checkcodeflag = $gInfo['checkcodeflag'];
            $goodsname = $gInfo['name'];
            if($appointstatus > 0){
                $appointstatus = 1;
            }
            $order['name'] = $order['username'];
            if ($gInfo['cutoffstatus']) {
                $estimatetime = time() + $gInfo['cutoffday'] * 86400;
            } else {
                $estimatetime = $gInfo['cutofftime'];
            }
        }else if($type == 2){ //团购订单
            $order = pdo_get(PDO_NAME.self::$name,array('id' => $orderid),array('orderno','fkid','specid','name','price','uniacid','mid','sid','aid','mobile','num','settlementmoney','blendcredit','vip_card_id'));
            $blendcredit = sprintf("%.2f",$order['blendcredit']/$order['num']);
            $settmoney = sprintf("%.2f",$order['settlementmoney']/$order['num']);
            if($order['vip_card_id'] > 0){
                $vipCardPrice = pdo_getcolumn(PDO_NAME.'halfcard_type',array('id'=>$order['vip_card_id']),'price');
                $order['price'] = $order['price'] - $vipCardPrice;
            }
            $orderprice = sprintf("%.2f",$order['price']/$order['num']);
            $plugin = 'groupon';
            $gid = $order['fkid'];
            $specid = $order['specid'];
            $gInfo = pdo_get(PDO_NAME.'groupon_activity',array('id'=>$order['fkid']),['cutoffstatus','cutoffday','cutofftime','appointstatus','name','checkcodeflag']);
            $appointstatus = $gInfo['appointstatus'];
            $checkcodeflag = $gInfo['checkcodeflag'];
            $goodsname = $gInfo['name'];
            if($appointstatus > 0){
                $appointstatus = 1;
            }
            if ($gInfo['cutoffstatus']) {
                $estimatetime = time() + $gInfo['cutoffday'] * 86400;
            } else {
                $estimatetime = $gInfo['cutofftime'];
            }
        }else if($type == 3){ //拼团订单
            $order = pdo_get(PDO_NAME.self::$name,array('id' => $orderid),array('orderno','fkid','specid','name','price','uniacid','mid','sid','aid','mobile','num','settlementmoney','blendcredit','vip_card_id'));
            $blendcredit = sprintf("%.2f",$order['blendcredit']/$order['num']);
            $settmoney = sprintf("%.2f",$order['settlementmoney']/$order['num']);
            if($order['vip_card_id'] > 0){
                $vipCardPrice = pdo_getcolumn(PDO_NAME.'halfcard_type',array('id'=>$order['vip_card_id']),'price');
                $order['price'] = $order['price'] - $vipCardPrice;
            }
            $orderprice = sprintf("%.2f",$order['price']/$order['num']);
            $plugin = 'wlfightgroup';
            $gid = $order['fkid'];
            $specid = $order['specid'];
            $gInfo = pdo_get(PDO_NAME.'fightgroup_goods',array('id'=>$order['fkid']),['cutoffstatus','cutoffday','cutofftime','appointstatus','name','checkcodeflag']);
            $appointstatus = $gInfo['appointstatus'];
            $checkcodeflag = $gInfo['checkcodeflag'];
            $goodsname = $gInfo['name'];
            if($appointstatus > 0){
                $appointstatus = 1;
            }
            if ($gInfo['cutoffstatus']) {
                $estimatetime = time() + $gInfo['cutoffday'] * 86400;
            } else {
                $estimatetime = $gInfo['cutofftime'];
            }
        }else if($type == 4){ //卡券订单
            $order = pdo_get(PDO_NAME.self::$name,array('id' => $orderid),array('orderno','fkid','price','name','fkid','uniacid','mid','sid','aid','mobile','num','settlementmoney','blendcredit','vip_card_id'));
            if($order['vip_card_id'] > 0){
                $vipCardPrice = pdo_getcolumn(PDO_NAME.'halfcard_type',array('id'=>$order['vip_card_id']),'price');
                $order['price'] = $order['price'] - $vipCardPrice;
            }
            $usetimes = pdo_getcolumn(PDO_NAME.'couponlist',array('id'=>$order['fkid']),'usetimes');
            $order['num'] = $order['num'] * $usetimes;
            $blendcredit = sprintf("%.2f",$order['blendcredit']/$order['num']);
            $settmoney = sprintf("%.2f",$order['settlementmoney']/$order['num']);
            $orderprice = sprintf("%.2f",$order['price']/$order['num']);
            $plugin = 'coupon';
            $gid = $order['fkid'];
            $specid = 0;
            $gInfo = pdo_get(PDO_NAME.'couponlist',array('id'=>$order['fkid']),['time_type','endtime','deadline','title','checkcodeflag']);
            $appointstatus = 0;
            $checkcodeflag = $gInfo['checkcodeflag'];
            $goodsname = $gInfo['title'];
            if ($gInfo['time_type']) {
                $estimatetime = $gInfo['endtime'];
            } else {
                $estimatetime = time() + $gInfo['deadline'] * 86400;
            }
        }else if($type == 5){ //砍价订单
            $order = pdo_get(PDO_NAME.self::$name,array('id' => $orderid),array('orderno','fkid','price','name','uniacid','mid','sid','aid','mobile','num','settlementmoney','blendcredit'));
            $blendcredit = sprintf("%.2f",$order['blendcredit']/$order['num']);
            $settmoney = sprintf("%.2f",$order['settlementmoney']/$order['num']);
            $orderprice = sprintf("%.2f",$order['price']/$order['num']);
            $plugin = 'bargain';
            $gid = $order['fkid'];
            $specid = 0;
            $gInfo = pdo_get(PDO_NAME.'bargain_activity',array('id'=>$order['fkid']),['cutoffstatus','cutoffday','cutofftime','name','appointstatus','checkcodeflag']);
            $appointstatus = $gInfo['appointstatus'];
            $checkcodeflag = $gInfo['checkcodeflag'];
            if($appointstatus > 0){
                $appointstatus = 1;
            }
            $goodsname = $gInfo['name'];
            if ($gInfo['cutoffstatus']) {
                $estimatetime = time() + $gInfo['cutoffday'] * 86400;
            } else {
                $estimatetime = $gInfo['cutofftime'];
            }
        }else if($type == 6){ //活动订单
            $order = pdo_get(PDO_NAME.self::$name,array('id' => $orderid),array('orderno','fkid','specid','name','price','uniacid','mid','mobile','sid','aid','num','settlementmoney','blendcredit'));
            $blendcredit = sprintf("%.2f",$order['blendcredit']/$order['num']);
            $settmoney = sprintf("%.2f",$order['settlementmoney']/$order['num']);
            $orderprice = sprintf("%.2f",$order['price']/$order['num']);
            $plugin = 'activity';
            $gid = $order['fkid'];
            $specid = $order['specid'];
            $gInfo = pdo_get(PDO_NAME.'activitylist',array('id'=>$order['fkid']),['activeendtime','title','checkcodeflag']);
            $appointstatus = 0;
            $checkcodeflag = $gInfo['checkcodeflag'];
            $goodsname = $gInfo['title'];
            $estimatetime = $gInfo['activeendtime'];
        }else if($type == 7){  //酒店订单
            $order = pdo_get(PDO_NAME.self::$name,array('id' => $orderid),array('endtime','orderno','fkid','specid','name','price','uniacid','mid','mobile','sid','aid','num','settlementmoney'));
            $order['num'] = 1;
            $blendcredit = 0;
            $settmoney = $order['settlementmoney'];
            $orderprice = $order['price'];
            $plugin = 'hotel';
            $gid = $order['fkid'];
            $specid = 0;
            $gInfo = pdo_get(PDO_NAME.'hotel_room',array('id'=>$order['fkid']),['name']);
            $appointstatus = 0;
            $checkcodeflag = 0;
            $goodsname = $gInfo['name'];
            $estimatetime = $order['endtime'];
        }
        if($checkcodeflag > 0){
            $checkcodelist = pdo_fetchall("SELECT id,checkcode FROM ".tablename('wlmerchant_checkcodelist')."WHERE goodsid = {$gid} AND plugin = '{$plugin}' AND status = 0 ORDER BY id ASC limit {$order['num']} ");
            if(count($checkcodelist) > 0){
                foreach ($checkcodelist as $check){
                    $sdata = array(
                        'uniacid'     => $order['uniacid'],
                        'mid'         => $order['mid'],
                        'aid'         => $order['aid'],
                        'sid'         => $order['sid'],
                        'gid'         => $gid,
                        'specid'      => $specid,
                        'status'      => 1,
                        'plugin'      => $plugin,
                        'orderid'     => $orderid,
                        'orderno'     => $order['orderno'],
                        'createtime'  => time(),
                        'checkcode'   => $check['checkcode'],
                        'orderprice'  => $orderprice,
                        'settlemoney' => $settmoney,
                        'blendcredit' => $blendcredit,
                        'appointstatus' => $appointstatus
                    );
                    pdo_insert(PDO_NAME .'smallorder',$sdata);
                    pdo_update('wlmerchant_checkcodelist',array('status' => 1,'orderid' => $orderid),array('id' => $check['id']));
                }
                $code[] = $check['checkcode'];
            }
        }else{
            $orderset = Setting::wlsetting_read('orderset');

            for($i=0;$i<$order['num'];$i++){
                if($orderset['codetype'] > 0 ){
                    $newcode = Util::createConcode(7,8);
                }else{
                    $newcode = Util::createNewConcode(7,8);
                }
                $sdata = array(
                    'uniacid'     => $order['uniacid'],
                    'mid'         => $order['mid'],
                    'aid'         => $order['aid'],
                    'sid'         => $order['sid'],
                    'gid'         => $gid,
                    'specid'      => $specid,
                    'status'      => 1,
                    'plugin'      => $plugin,
                    'orderid'     => $orderid,
                    'orderno'     => $order['orderno'],
                    'createtime'  => time(),
                    'checkcode'   => $newcode,
                    'orderprice'  => $orderprice,
                    'settlemoney' => $settmoney,
                    'blendcredit' => $blendcredit,
                    'appointstatus' => $appointstatus
                );
                pdo_insert(PDO_NAME .'smallorder',$sdata);
                $code[] = $sdata['checkcode'];
            }
            //生成码校验
            $smordernum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE orderno = '{$order['orderno']}' AND plugin = '{$plugin}' ");
            if($smordernum > $order['num']){
                $more = $smordernum - $order['num'];
                $res = pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_smallorder')."WHERE orderno = '{$order['orderno']}' AND plugin = '{$plugin}' ORDER BY id DESC LIMIT {$more}");
                if(!empty($res)){
                    foreach ($res as $ree){
                        pdo_delete('wlmerchant_smallorder',array('id'=>$ree['id']));
                    }
                }
            }
        }
        //发送短信
        if(!empty($code)){
            try {
            $codestr = '';
//            if(count($code) > 1){
//                $codestr = $code[0].'等';
//            }else{
                $codestr = $code[0];
//            }
            $time = date('Y-m-d H:i:s',$estimatetime);
            $storename = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$order['sid']),'storename');
            WeliamWeChat::smsHXM($codestr,$goodsname,$order['num'],$time,$order['mobile'],$order['mid'],$order['name'],$storename);
        }catch (\Exception $e) {
                file_put_contents(PATH_DATA . "sms_error.log", var_export($e, true) . PHP_EOL, FILE_APPEND);
            }
        }

    }
    public function finishSmallorder($orderid,$uid = 0,$type=1){
        $order = pdo_get(PDO_NAME .'smallorder',array('id' => $orderid));
        //核销
        $sdata['status'] = 2;
        $sdata['hxuid'] = $uid;
        $sdata['hexiaotime'] = time();
        $sdata['settletime'] = time();
        $sdata['hexiaotype'] = $type;
        //结算给商户
        //判断是否已结算
        MysqlFunction::setTrans(4);
        MysqlFunction::startTrans();
        $flag = pdo_get(PDO_NAME.'autosettlement_record',array('checkcode' => $order['checkcode'],'orderno' => $order['orderno']),array('id'));
        if(empty($flag)){
            if($order['plugin'] == 'rush'){ //抢购订单
                $type = 1;
                $parentorder = pdo_get(PDO_NAME.'rush_order',array('id'=>$order['orderid']),array('actualprice','mid','id','activityid','price','num','allocationtype','salesarray','paytype'));
                $goodsid = $parentorder['activityid'];
                $salesarray = $parentorder['salesarray'];
                $parentorder['goodsprice'] = $parentorder['price'];
                $parentorder['price'] = $parentorder['actualprice'];
                $goodsinfo = pdo_get(PDO_NAME.'rush_activity',array('id' => $goodsid),array('name','drawid','paidid'));
                $paidtype = 1;
            }else if($order['plugin'] == 'groupon'){ //团购订单
                $type = 10;
                $parentorder = pdo_get(PDO_NAME.'order',array('id'=>$order['orderid']),array('mid','id','fkid','goodsprice','num','allocationtype','salesarray','paytype'));
                $goodsid = $parentorder['fkid'];
                $salesarray = $parentorder['salesarray'];

                $goodsinfo = pdo_get(PDO_NAME.'groupon_activity',array('id'=>$goodsid),['name','drawid','paidid']);
                $paidtype = 4;


            }else if($order['plugin'] == 'wlfightgroup'){ //拼团订单
                $type = 2;
                $parentorder = pdo_get(PDO_NAME.'order',array('id'=>$order['orderid']),array('mid','id','fkid','goodsprice','num','allocationtype','salesarray','paytype'));
                $goodsid = $parentorder['fkid'];
                $salesarray = $parentorder['salesarray'];

                $goodsinfo = pdo_get(PDO_NAME.'fightgroup_goods',array('id'=>$goodsid),['name','drawid','paidid']);
                $paidtype = 2;


            }else if($order['plugin'] == 'bargain'){ //砍价订单
                $type = 12;
                $parentorder = pdo_get(PDO_NAME.'order',array('id'=>$order['orderid']),array('mid','id','fkid','goodsprice','num','allocationtype','salesarray','paytype'));
                $goodsid = $parentorder['fkid'];
                $salesarray = $parentorder['salesarray'];

                $goodsinfo = pdo_get(PDO_NAME.'bargain_activity',array('id'=>$goodsid),['name','drawid','paidid']);
                $paidtype = 9;

            }else if($order['plugin'] == 'coupon'){ //超级券订单
                $type = 3;
                $parentorder = pdo_get(PDO_NAME.'order',array('id'=>$order['orderid']),array('mid','id','fkid','goodsprice','num','allocationtype','salesarray','paytype'));
                $goodsid = $parentorder['fkid'];
                $salesarray = $parentorder['salesarray'];

                $goodsinfo = pdo_get(PDO_NAME.'couponlist',array('id'=>$goodsid),['title','drawid','paidid']);
                $paidtype = 3;

            }else if($order['plugin'] == 'activity'){ //活动订单
                $type = 9;
                $parentorder = pdo_get(PDO_NAME.'order',array('id'=>$order['orderid']),array('mid','id','fkid','goodsprice','num','allocationtype','salesarray','paytype'));
                $goodsid = $parentorder['fkid'];
                $salesarray = $parentorder['salesarray'];
                $goodsinfo = pdo_get(PDO_NAME.'activitylist',array('id'=>$goodsid),['title','drawid']);
            }else if($order['plugin'] == 'hotel'){ //酒店订单
                $type = 19;
                $parentorder = pdo_get(PDO_NAME.'order',array('id'=>$order['orderid']),array('mid','id','fkid','goodsprice','num','allocationtype','salesarray','paytype'));
                $goodsid = $parentorder['fkid'];
                $salesarray = $parentorder['salesarray'];
                $goodsinfo = pdo_get(PDO_NAME.'hotel_room',array('id'=>$goodsid),['name','drawid','paidid']);
                $paidtype = 7;
            }
            //业务员佣金
            $salesmoney = 0;
            if(!empty($salesarray)){
                $goodsprice = sprintf("%.2f",$parentorder['goodsprice'] / $parentorder['num']);
                $salesarray = unserialize($salesarray);
                foreach ($salesarray as &$sale){
                    $sale['reportmoney'] = sprintf("%.2f",$goodsprice * $sale['scale'] /100 );
                    $salesmoney += $sale['reportmoney'];
                }
            }

            $marketstatus = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$order['sid']),'marketstatus');
            if($marketstatus > 0){
                $agentmoney = round($order['orderprice'] - $order['settlemoney'], 2);
                $merchantmoney = round($order['settlemoney'] - $order['onedismoney'] - $order['twodismoney'] - $salesmoney - $order['onegroupmoney'] - $order['twogroupmoney'] - $order['shareholdermoney'], 2);
            }else{
                $agentmoney = round($order['orderprice'] - $order['settlemoney'] - $order['onedismoney'] - $order['twodismoney'] - $salesmoney - $order['onegroupmoney'] - $order['twogroupmoney'] - $order['shareholdermoney'], 2);
                $merchantmoney = $order['settlemoney'];
            }

            $data = array(
                'uniacid' => $order['uniacid'],
                'aid' => $order['aid'],
                'type' => $type,
                'merchantid' => $order['sid'],
                'orderid' => $order['orderid'],
                'orderno' => $order['orderno'],
                'goodsid' => $goodsid,
                'orderprice' => $order['orderprice'],
                'agentmoney' => $agentmoney,
                'merchantmoney' => $merchantmoney,
                'distrimoney' => round($order['onedismoney'] + $order['twodismoney'], 2),
                'groupmoney' => round($order['onegroupmoney'] + $order['twogroupmoney'], 2),
                'shareholdermoney' => $order['shareholdermoney'],
                'salesmoney'  => $salesmoney,
                'sharemoney' => 0,
                'createtime' => time(),
                'checkcode'  => $order['checkcode']
            );
            $res = pdo_insert(PDO_NAME . 'autosettlement_record', $data);

            $settlementid = pdo_insertid();
        }

        if ($settlementid > 0) {
            if($parentorder['allocationtype'] == 1){
                $sdata['allocationstatus'] = 1;
            }else{
                //结算给商户
                if(abs($data['merchantmoney']) > 0){
                    $changestoreres = pdo_update('wlmerchant_merchantdata',array('allmoney +=' => $data['merchantmoney'],'nowmoney +='=>$data['merchantmoney']),array('id' => $data['merchantid']));
                    if($changestoreres){
                        $change['merchantnowmoney'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $data['merchantid']), 'nowmoney');
                        Store::addcurrent(1,$type,$data['merchantid'],$data['merchantmoney'],$change['merchantnowmoney'],$order['orderid'],$data['checkcode'],$order['uniacid'],$order['aid']);
                    }else{
                        MysqlFunction::rollback();
                        return false;
                    }
                }
                //结算给代理
                if (abs($data['agentmoney']) > 0 && $data['aid'] > 0){
                    $changeagentres = pdo_update('wlmerchant_agentusers',array('allmoney +=' => $data['agentmoney'],'nowmoney +='=>$data['agentmoney']),array('id' => $data['aid']));
                    if($changeagentres) {
                        $change['agentnowmoney'] = pdo_getcolumn(PDO_NAME . 'agentusers', array('id' => $data['aid']), 'nowmoney');
                        Store::addcurrent(2,$type,$data['aid'],$data['agentmoney'],$change['agentnowmoney'],$order['orderid'],$data['checkcode'],$order['uniacid'],$order['aid']);
                    }else{
                        MysqlFunction::rollback();
                        return false;
                    }
                }
                pdo_update('wlmerchant_autosettlement_record', $change, array('id' => $settlementid));
                //结算给分销商
                //判断分销订单状态
                if($order['disorderid']){
                    $disorder = pdo_get('wlmerchant_disorder',array('id' => $order['disorderid']));
                    if(empty($disorder['status'])){
                        $nosetflag = pdo_getcolumn('wlmerchant_disdetail',array('checkcode' => $order['checkcode'],'status'=>0),'id');
                        if(empty($nosetflag)){
                            if($order['onedismoney'] > 0){
                               $oneres = pdo_query("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney+{$order['onedismoney']},nowmoney=nowmoney+{$order['onedismoney']} WHERE id = {$order['oneleadid']}");
                               if($oneres){
                                   $leadid = pdo_getcolumn('wlmerchant_distributor',array('id'=> $order['oneleadid']),'mid');
                                   $onenowmoney = pdo_getcolumn(PDO_NAME.'distributor',array('id'=> $order['oneleadid']),'nowmoney');
                                   Distribution::adddisdetail($order['disorderid'],$leadid,$disorder['buymid'],1,$order['onedismoney'],$disorder['plugin'],1,'分销订单结算',$onenowmoney,$data['checkcode'],0,$order['aid']);
                               }
                            }
                            if($order['twodismoney'] > 0){
                                $twores = pdo_query("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney+{$order['twodismoney']},nowmoney=nowmoney+{$order['twodismoney']} WHERE id = {$order['twoleadid']}");
                                if($twores){
                                    $leadid = pdo_getcolumn('wlmerchant_distributor',array('id'=> $order['twoleadid']),'mid');
                                    $twonowmoney = pdo_getcolumn(PDO_NAME.'distributor',array('id'=> $order['twoleadid']),'nowmoney');
                                    Distribution::adddisdetail($order['disorderid'],$leadid,$disorder['buymid'],1,$order['twodismoney'],$disorder['plugin'],2,'分销订单结算',$twonowmoney,$data['checkcode'],0,$order['aid']);
                                }
                            }
                            if($order['onegroupmoney'] > 0){
                                $onegrres = pdo_query("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney+{$order['onegroupmoney']},nowmoney=nowmoney+{$order['onegroupmoney']} WHERE mid = {$order['onegroupid']}");
                                if($onegrres){
                                    $onegrmoney = pdo_getcolumn(PDO_NAME.'distributor',array('mid'=> $order['onegroupid']),'nowmoney');
                                    Distribution::adddisdetail($order['disorderid'],$order['onegroupid'],$disorder['buymid'],1,$order['onegroupmoney'],$disorder['plugin'],1,'分销订单团长分红',$onegrmoney,$data['checkcode'],2,$order['aid']);
                                }
                            }
                            if($order['twogroupmoney'] > 0){
                                $twogrres = pdo_query("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney+{$order['twogroupmoney']},nowmoney=nowmoney+{$order['twogroupmoney']} WHERE mid = {$order['twogroupid']}");
                                if($twogrres){
                                    $twogrmoney = pdo_getcolumn(PDO_NAME.'distributor',array('mid'=> $order['twogroupid']),'nowmoney');
                                    Distribution::adddisdetail($order['disorderid'],$order['twogroupid'],$disorder['buymid'],1,$order['twogroupmoney'],$disorder['plugin'],2,'分销订单团长分红',$twogrmoney,$data['checkcode'],2,$order['aid']);
                                }
                            }
                            if($order['shareholdermoney'] > 0){
                                $allshareholder = pdo_getall('wlmerchant_distributor',array('uniacid' => $order['uniacid'],'shareholder' => 1),array('id','mid'));
                                if(!empty($allshareholder)){
                                    $sharenum = count($allshareholder);
                                    $sharemoney = sprintf("%.2f",$order['shareholdermoney']/$sharenum);
                                    foreach ($allshareholder as $share){
                                        $shareres = pdo_query("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney+{$sharemoney},nowmoney=nowmoney+{$sharemoney} WHERE id = {$share['id']}");
                                        if($shareres){
                                            $onegrmoney = pdo_getcolumn(PDO_NAME.'distributor',array('id'=> $share['id']),'nowmoney');
                                            Distribution::adddisdetail($order['disorderid'],$share['mid'],$disorder['buymid'],1,$sharemoney,$disorder['plugin'],0,'订单股东分红',$onegrmoney,$data['checkcode'],3,$order['aid']);
                                        }
                                    }
                                }
                            }

                            $sdata['dissettletime'] = time();
                        }
                    }
                }
                //结算给业务员
                if(!empty($salesarray)){
                    $nosetflag = pdo_getcolumn('wlmerchant_disdetail',array('checkcode' => $order['checkcode'],'status'=>1),'id');
                    if(empty($nosetflag)){
                        foreach ($salesarray as $sale2){
                            $reportmoney = $sale2['reportmoney'];
                            if($reportmoney>0) {
                                pdo_fetch("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney+{$reportmoney},nowmoney=nowmoney+{$reportmoney} WHERE mid = {$sale2['mid']}");
                                $onenowmoney = pdo_getcolumn(PDO_NAME . 'distributor', array('mid' => $sale2['mid']), 'nowmoney');
                                if($order['plugin'] == 'wlfightgroup'){
                                    $orderplugin = 'fightgroup';
                                }else{
                                    $orderplugin = $order['plugin'];
                                }
                                Distribution::adddisdetail($order['orderid'], $sale2['mid'], $order['sid'], 1, $reportmoney, $orderplugin, 0, '业务员佣金结算', $onenowmoney, $order['checkcode'], 1, $order['aid']);
                            }
                        }
                    }
                }
            }
            //支付返现
            if($parentorder['paytype'] != 1 && p('payback')){
                Payback::payCore($order['sid'],$order['mid'],$uid,$order['plugin'],$order['orderprice'],$order['orderno'],$order['orderid'],$order['uniacid'],$order['aid'],$order['checkcode']);
            }
            //余额返现
            self::yueCashBack($order['mid'],$order['plugin'],$goodsid);
            //赠送抽奖码
            if($goodsinfo['drawid'] > 0 ){
                Luckydraw::getDrawCode($goodsinfo['drawid'],$order['mid'],$order['orderid'],$order['plugin'],$order['id']);
            }
        }
        if($settlementid > 0 || !empty($flag)){
            //修改小订单状态
            $chengeOrderRes = pdo_update('wlmerchant_smallorder',$sdata,array('id' => $orderid));
            if($chengeOrderRes){
                //写入验证记录
                if($order['plugin'] == 'rush'){
                    $goodsname = $goodsinfo['name'];
                }else if($order['plugin'] == 'groupon'){
                    $goodsname = $goodsinfo['name'];
                }else if($order['plugin'] == 'wlfightgroup'){ //拼团订单
                    $goodsname = $goodsinfo['name'];
                }else if($order['plugin'] == 'bargain'){ //砍价订单
                    $goodsname = $goodsinfo['name'];
                }else if($order['plugin'] == 'coupon'){ //超级券订单
                    $goodsname = $goodsinfo['title'];
                }else if($order['plugin'] == 'activity'){ //活动订单
                    $goodsname = $goodsinfo['title'];
                }else if($order['plugin'] == 'hotel'){ //酒店订单
                    $goodsname = $goodsinfo['name'];
                }
                $hexiaomid = pdo_getcolumn(PDO_NAME.'merchantuser',array('id'=>$uid),'mid');
                SingleMerchant::verifRecordAdd($order['aid'], $order['sid'], $order['mid'],$order['plugin'], $order['orderid'], $order['checkcode'], $goodsname, $sdata['hexiaotype'],1,$hexiaomid);
                //判断大订单是否结算完
                $plugin = $order['plugin'];
                $finishflag = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE plugin = '{$plugin}' AND  orderid = {$order['orderid']} AND status = 1");
                if(empty($finishflag)){
                    //支付有礼
                    if($goodsinfo['paidid'] > 0){
                        $paidprid = Paidpromotion::getpaidpr($paidtype,$goodsinfo['paidid'],$parentorder['mid'],$parentorder['id'],$parentorder['paytype'],$parentorder['price'],$parentorder['num'],1);
                    }
                    if($order['plugin'] == 'rush'){ //抢购订单
                        pdo_update('wlmerchant_rush_order',array('issettlement'=>1,'status'=>2,'settletime'=> time(),'paidprid' => $paidprid),array('id' => $order['orderid']));
                    }else{
                        $aa = pdo_update('wlmerchant_order',array('issettlement'=>1,'status'=>2,'settletime'=> time(),'paidprid' => $paidprid),array('id' => $order['orderid']));
                        if($order['plugin'] == 'coupon'){
                            $recordid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$order['orderid']),'recordid');
                            pdo_update('wlmerchant_member_coupons',array('status'=>2,'usetimes'=>0),array('id' => $recordid));
                        }
                    }
                    if($order['disorderid']){
                        $disres = pdo_update('wlmerchant_disorder',array('status'=>2),array('id' => $order['disorderid']));
                        if($disres){
                            if($order['onedismoney'] > 0){
                                Distribution::moneyNotice($disorder['buymid'], $disorder['plugin'], $disorder['orderid'], $disorder['oneleadid'], $disorder['id'], 2);
                                Distribution::checkup($order['oneleadid']);
                            }
                            if($order['twodismoney'] > 0){
                                Distribution::moneyNotice($disorder['buymid'],$disorder['plugin'],$disorder['orderid'],$disorder['twoleadid'],$disorder['id'],2);
                                Distribution::checkup($order['twoleadid']);
                            }
                        }
                    }
                }
                MysqlFunction::commit();
                //开始分账
                if($sdata['allocationstatus'] == 1){
                    $source = pdo_getcolumn(PDO_NAME.'paylogvfour',array('tid'=>$order['orderno']),'source');
                    $weixin = NEW WeixinPay();
                    $allres = $weixin->allocationMulti($orderid,$source,$salesarray,$salesmoney);
                    if(is_array($allres)){
                        pdo_update('wlmerchant_smallorder',['allocationstatus'=>2,'dissettletime'=>time()],array('id' => $orderid));
                        pdo_update('wlmerchant_autosettlement_record', ['sysmoney'=> $allres['sysmoney'] ,'agentmoney'=> $allres['agentmoney'],'allocationtype' => 1 ], array('id' => $settlementid));
                        if(empty($finishflag)){
                            //分账完毕
                            $weixin->allocationFinish($orderid);
                        }
                    }
                }
                return true;
            }
        }
        MysqlFunction::rollback();
        return false;
    }

    public function remindTime($estimatetime){
        global $_W;
        $orderset = Setting::wlsetting_read('orderset');
        if(empty($orderset['remind'])){
            $orderset['remind'] = 48;
        }
        $remindtime = $estimatetime - $orderset['remind']*3600;
        return $remindtime;
    }

    /**
     * Comment: 添加日志函数
     * Author: wlf
     * Date: 2019/11/21 16:13
     */
    public function addjournal($journal,$afterid = 0){
        global $_W;
        if(!empty($afterid)){
            $journals = pdo_getcolumn(PDO_NAME.'aftersale',array('id'=>$afterid),'journal');
            $journals = unserialize($journals);
        }else{
            $journals = [];
        }
        $journals[] = serialize($journal);
        $journals = serialize($journals);
        return $journals;
    }

    /**
     * Comment: 订单打印推送
     * Author: wlf
     * Date: 2020/07/02 15:34
     */
    function sendPrinting($orderid,$plugin){
        $content = '';
        if($plugin == 'rush'){
            $order = pdo_get('wlmerchant_rush_order',array('id' => $orderid),array('sid','mid','orderno','optionid','actualprice','activityid','expressid','num','paytime','remark','username','mobile','price'));
            $store = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('printing','storename'));
            $printing = unserialize($store['printing']);
        }else{
            $order = pdo_get('wlmerchant_order',array('id' => $orderid),array('sid','orderno','mid','specid','price','fkid','fightstatus','expressid','num','paytime','buyremark','name','mobile','goodsprice'));
            $store = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('printing','storename'));
            $printing = unserialize($store['printing']);
        }

        if($printing['type'] == 4 || empty($printing['type'])){  //568定制小票打印
            $isAuth = Customized::init('printing');
            if($isAuth && $order['sid'] > 0) Printing::init($order['orderno']);
        }else{
            if($plugin == 'rush'){
                $goodstype = '抢购商品';
                $goods = pdo_get('wlmerchant_rush_activity',array('id' => $order['activityid']),array('name'));
                $goodsname = $goods['name'];
                if($order['optionid']>0){
                    $specname = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['optionid']),'title');
                }
                $price = $order['actualprice'];
                $goodsprice = $order['price'];
                if($order['expressid']>0){
                    $express = pdo_get('wlmerchant_express',array('id' => $order['expressid']),array('name','tel','address'));
                    $deliverytype = '快递发货';
                }else{
                    $deliverytype = '买家自提';
                    $username = $order['username'];
                }
                $username = $order['username'];
                $remark = $order['remark'];
            }else{
                if($plugin == 'groupon'){
                    $goodstype = '团购商品';
                    $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $order['fkid']),array('name'));
                    $goodsname = $goods['name'];
                    if($order['specid']>0){
                        $specname = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['specid']),'title');
                    }
                }else if($plugin == 'wlfightgroup'){
                    $goodstype = '拼团商品';
                    $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $order['fkid']),array('name'));
                    $goodsname = $goods['name'];
                    if($order['specid']>0){
                        $specname = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['specid']),'title');
                    }
                }else if($plugin == 'bargain'){
                    $goodstype = '砍价商品';
                    $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $order['fkid']),array('name'));
                    $goodsname = $goods['name'];
                }else if($plugin == 'citydelivery'){
                    $goodsname = [];
                    $goodstype = '同城配送';
                    $smallorders = pdo_fetchall("SELECT gid,money,num,specid FROM ".tablename('wlmerchant_delivery_order')."WHERE tid = {$order['orderno']} AND mid = {$order['mid']} AND orderid = {$orderid} ORDER BY price DESC");
                    foreach ($smallorders  as $ke => &$orr){
                        $goods = pdo_get('wlmerchant_delivery_activity',array('id' => $orr['gid']),array('name'));
                        $orr['name'] = $goods['name'];
                        if($orr['specid']>0){
                            $specnameV = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                            $orr['name'] .= '/'.$specnameV;
                        }
                        $goodsname[] = $orr['name'].' X'.$orr['num'];
                    }
                }else if($plugin == 'payonline'){
                    $goodstype = '在线买单';
                    $goods = $store['storename'].'在线买单';
                }else if($plugin == 'activity'){
                    $goodstype = '活动报名';
                    $goods = pdo_get('wlmerchant_activitylist',array('id' => $order['fkid']),array('title'));
                    $goodsname = $goods['title'];
                    if($order['specid']>0){
                        $specname = pdo_getcolumn(PDO_NAME.'activity_spec',array('id'=>$order['specid']),'name');
                    }
                }

                if($plugin != 'citydelivery'){
                    if($order['expressid']>0){
                        $express = pdo_get('wlmerchant_express',array('id' => $order['expressid']),array('name','tel','address'));
                        $deliverytype = '快递发货';
                    }else if($plugin == 'payonline'){
                        $deliverytype = '在线买单';
                    }else{
                        $deliverytype = '买家自提';
                    }
                }else{
                    if($order['fightstatus'] == 0){
                        $deliverytype = '买家自提';
                    }else if($order['fightstatus'] == 1){
                        $deliverytype = '商家配送';
                        $express = pdo_get('wlmerchant_address',array('id' => $order['expressid']),array('name','tel','province','city','county','detailed_address'));
                        $express['address'] = $express['province'].$express['city'].$express['county'].$express['detailed_address'];
                    }else if($order['fightstatus'] == 2){
                        $deliverytype = '平台配送';
                        $express = pdo_get('wlmerchant_address',array('id' => $order['expressid']),array('name','tel','province','city','county','detailed_address'));
                        $express['address'] = $express['province'].$express['city'].$express['county'].$express['detailed_address'];
                    }
                }
                $username = $order['name'];
                $price = $order['price'];
                $goodsprice = $order['goodsprice'];
                $remark = $order['buyremark'];
            }
            $nickname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$order['mid']),'nickname');
            $paytime = date('Y-m-d H:i:s',$order['paytime']);
            if($printing['type'] == 1){   //365小票云打印
                $content .= "    店铺：".$store['storename']."\n";
                $content .= "支付时间：".$paytime."\n";
                $content .= "  订单号：".$order['orderno']."\n";
                $content .= "订单类型：".$goodstype."\n";
                if(is_array($goodsname)){
                    foreach ($goodsname as $ke => $dname){
                        if($ke == 0){
                            $content .= "购买商品：".$dname."\n";
                        }else{
                            $content .= "          ".$dname."\n";
                        }
                    }
                }else if($plugin == 'activity'){
                    $content .= "活动名称：".$goodsname."\n";
                }else{
                    $content .= "商品名称：".$goodsname."\n";
                }
                if(!empty($specname)){
                    $content .= "规格名称：".$specname."\n";
                }
                if($plugin == 'activity'){
                    $content .= "报名人数：".$order['num']."\n";
                }else if($plugin != 'citydelivery'){
                    $content .= "商品数量：".$order['num']."\n";
                }
                if($plugin != 'activity') {
                    $content .= "配送方式：" . $deliverytype . "\n";
                }
                $content .= "买家昵称：".$nickname."\n";
                if(!empty($express)){
                    $content .= "<B>  收货人：".$express['name']."</B>\n";
                    $content .= "<B>联系电话：".$express['tel']."</B>\n";
                    $content .= "<B>配送地址：".$express['address']."</B>\n";
                }else if($plugin == 'activity'){
                    $content .= "<B>  联系人：".$username."</B>\n";
                    $content .= "<B>联系电话：".$order['mobile']."</B>\n";
                }else{
                    $content .= "<B>  提货人：".$username."</B>\n";
                    $content .= "<B>联系电话：".$order['mobile']."</B>\n";
                }
                if($goodsprice > 0){
                    if($plugin == 'activity'){
                        $content .= "报名费：".$goodsprice."\n";
                    }else{
                        $content .= "商品金额：".$goodsprice."\n";
                    }
                }
                $content .= "实付金额：".$price."\n";
                $content .= "买家备注：".$remark."\n";
                $content .= "--------------------------------";
                self::send365FormatOrderInfo($printing['device_no'],$printing['key'],$printing['times'],$content);
            }else if($printing['type'] == 3){
                $orderInfo = '<CB>'.$store['storename'].'</CB><BR>';
                $orderInfo .= '支付时间：'.$paytime.'<BR>';
                $orderInfo .= '订单号：'.$order['orderno'].'<BR>';
                $orderInfo .= '订单类型：'.$goodstype.'<BR>';
                if(is_array($goodsname)){
                    foreach ($goodsname as $ke => $dname){
                        if($ke == 0){
                            $orderInfo .= "购买商品：".$dname."<BR>";
                        }else{
                            $orderInfo .= "          ".$dname."<BR>";
                        }
                    }
                }else if($plugin == 'activity'){
                    $orderInfo .= "活动名称：".$goodsname."<BR>";
                }else{
                    $orderInfo .= "商品名称：".$goodsname."<BR>";
                }
                if(!empty($specname)){
                    $orderInfo .= "规格名称：".$specname."<BR>";
                }
                if($plugin == 'activity'){
                    $orderInfo .= "报名人数：".$order['num']."<BR>";
                }else if($plugin != 'citydelivery'){
                    $orderInfo .= "商品数量：".$order['num']."<BR>";
                }
                if($plugin != 'activity') {
                    $orderInfo .= "配送方式：" . $deliverytype . "<BR>";
                }
                $orderInfo .= "买家昵称：".$nickname."<BR>";
                if(!empty($express)){
                    $orderInfo .= "<B>  收货人：".$express['name']."</B><BR>";
                    $orderInfo .= "<B>联系电话：".$express['tel']."</B><BR>";
                    $orderInfo .= "<B>配送地址：".$express['address']."</B><BR>";
                }else if($plugin == 'activity'){
                    $orderInfo .= "<B>  联系人：".$username."</B><BR>";
                    $orderInfo .= "<B>联系电话：".$order['mobile']."</B><BR>";
                }else{
                    $orderInfo .= "<B>  提货人：".$username."</B><BR>";
                    $orderInfo .= "<B>联系电话：".$order['mobile']."</B><BR>";
                }
                if($goodsprice > 0){
                    if($plugin == 'activity'){
                        $orderInfo .= "报名费：".$goodsprice."<BR>";
                    }else {
                        $orderInfo .= "商品金额：" . $goodsprice . "<BR>";
                    }
                }
                $orderInfo .= "实付金额：".$price."<BR>";
                $orderInfo .= "买家备注：".$remark."<BR>";
                $orderInfo .= '--------------------------------<BR>';
                self::sendFeieFormatOrderInfo($printing['feie_user'],$printing['feie_key'],$printing['feie_no'],$printing['times2'],$orderInfo);
            }
        }

    }


    /**
     * Comment: 发送365打印数据
     * Author: wlf
     * Date: 2020/07/02 14:52
     */
    function send365FormatOrderInfo($device_no,$key,$times = 1,$orderInfo){ // $times打印次数
        $selfMessage = array(
            'deviceNo'=>$device_no,
            'printContent'=>$orderInfo,
            'key'=>$key,
            'times'=>$times
        );
        $url = "http://open.printcenter.cn:8080/addOrder";
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded ",
                'method'  => 'POST',
                'content' => http_build_query($selfMessage),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result,true);
        if(!empty($result['responseCode'])){
            file_put_contents(PATH_DATA . "365Printing.log", var_export($result,true) . PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * Comment: 发送飞鹅打印数据
     * Author: wlf
     * Date: 2020/07/02 14:52
     */
    function sendFeieFormatOrderInfo($user,$ukey,$feieNo,$times = 1,$orderInfo){ // $times打印次数

        $url = 'http://api.feieyun.cn/Api/Open/';
        $time = time();
        $sig = sha1($user.$ukey.$time);
        $data = [
            'user' =>  $user,
            'stime' => $time,
            'sig'  => $sig,
            'apiname' => 'Open_printMsg',
            'debug' => 0,
            'sn' => $feieNo,
            'content' => $orderInfo,
            'times' => $times
        ];
        $result = curlPostRequest($url,$data);
        if(empty($result['data'])){
            file_put_contents(PATH_DATA . "feiePrinting.log", var_export($result,true) . PHP_EOL, FILE_APPEND);
        }
    }


    //查看打印机状态
    function send365queryPrinterStatus($device_no,$key){ // $times打印次数
        $selfMessage = array(
            'deviceNo'=>$device_no,
            'key'=>$key,
        );
        $url = "http://open.printcenter.cn:8080/queryPrinterStatus";
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded ",
                'method'  => 'POST',
                'content' => http_build_query($selfMessage),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result,true);
        if(!empty($result['responseCode'])){
            file_put_contents(PATH_DATA . "365Printing.log", var_export($result,true) . PHP_EOL, FILE_APPEND);
        }
    }

    //查询订单打印状态
    function send365queryOrder($device_no,$key,$orderindex){ // $times打印次数
        $selfMessage = array(
            'deviceNo'=>$device_no,
            'key'=>$key,
            'orderindex' => $orderindex
        );
        $url = "http://open.printcenter.cn:8080/queryOrder";
        $options = array(
            'http' => array(
                'header' => "Content-type: application/x-www-form-urlencoded ",
                'method'  => 'POST',
                'content' => http_build_query($selfMessage),
            ),
        );
        $context  = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        $result = json_decode($result,true);
        if(!empty($result['responseCode'])){
            file_put_contents(PATH_DATA . "365Printing.log", var_export($result,true) . PHP_EOL, FILE_APPEND);
        }
    }


    /**
     * Comment: 余额返现(一般商品)
     * Author: wlf
     * Date: 2021/06/30 15:26
     */
    function yueCashBack($mid,$plugin,$gid,$num = 1){
        switch ($plugin) {
            case 'rush':
                $goods = pdo_get('wlmerchant_rush_activity',array('id' => $gid),array('name','aid','yuecashback','vipyuecashback'));
                $remark = '购买抢购商品['.$goods['name'].']返现';
                break;//抢购商品
            case 'groupon':
                $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $gid),array('name','aid','yuecashback','vipyuecashback'));
                $remark = '购买团购商品['.$goods['name'].']返现';
                break;//团购商品
            case 'wlfightgroup':
                $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $gid),array('name','aid','yuecashback','vipyuecashback'));
                $remark = '购买拼团商品['.$goods['name'].']返现';
                break;//拼团商品
            case 'coupon':
                $goods = pdo_get('wlmerchant_couponlist',array('id' => $gid),array('title','aid','yuecashback','vipyuecashback','usetimes'));
                $goods['yuecashback'] = sprintf("%.2f",$goods['yuecashback']/$goods['usetimes']);
                $goods['vipyuecashback'] = sprintf("%.2f",$goods['vipyuecashback']/$goods['usetimes']);
                $remark = '购买卡券['.$goods['title'].']返现';
                break;//优惠券
            case 'bargain':
                $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $gid),array('name','aid','yuecashback','vipyuecashback'));
                $remark = '购买砍价商品['.$goods['name'].']返现';
                break;//砍价商品
        }
        //判断用户身份
        $vip = WeliamWeChat::VipVerification($mid,true);
        if($vip > 0){
            $money = sprintf("%.2f",$goods['vipyuecashback']*$num);
        }else{
            $money = sprintf("%.2f",$goods['yuecashback']*$num);
        }
        if($money > 0){
            //扣除代理金额
            if($goods['aid'] > 0){
                $remark2 = '用户'.$remark;
                $ares = self::deductAgencyAmount($goods['aid'],$money,$remark2);
            }else{
                $ares = 1;
            }
            if($ares > 0){
                Member::credit_update_credit2($mid,$money,$remark);
            }
        }
    }

    /**
     * Comment: 余额返现(同城配送与在线买单)
     * Author: wlf
     * Date: 2021/06/30 17:34
     */
    function yueCityCashBack($mid,$sid,$price,$plugin = 0){   // plugin 0 配送 1买单
        $vip = WeliamWeChat::VipVerification($mid,true);
        if($plugin > 0){
            $storeinfo = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('online_yuecashback','aid','online_vipyuecashback','storename'));
            if($vip > 0){
                $money = sprintf("%.2f",$storeinfo['online_vipyuecashback']*$price/100);
            }else{
                $money = sprintf("%.2f",$storeinfo['online_yuecashback']*$price/100);
            }
            $remark = '支付['.$storeinfo['storename'].']在线买单返现';
        }else{
            $storeinfo = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('yuecashback','aid','vipyuecashback','storename'));
            if($vip > 0){
                $money = sprintf("%.2f",$storeinfo['vipyuecashback']*$price/100);
            }else{
                $money = sprintf("%.2f",$storeinfo['yuecashback']*$price/100);
            }
            $remark = '购买['.$storeinfo['storename'].']商品返现';
        }
        if($money > 0){
            //扣除代理金额
            if($storeinfo['aid'] > 0){
                $remark2 = '用户'.$remark;
                $ares = self::deductAgencyAmount($storeinfo['aid'],$money,$remark2);
            }else{
                $ares = 1;
            }
            if($ares > 0){
                Member::credit_update_credit2($mid,$money,$remark);
            }
        }
    }


    /**
     * Comment: 确认收货(一般商品)
     * Author: wlf
     * Date: 2021/06/30 15:56
     */

    function sureReceive($id,$type){
        if ($type == 'rush') {
            $res = pdo_update('wlmerchant_rush_order' , ['status' => 2] , ['id' => $id , 'status' => 4]);
            //添加结算抢购订单到计划任务
            $rushtask = [
                'type'    => 'rush' ,
                'orderid' => $id
            ];
            $rushtask = serialize($rushtask);
            Queue::addTask(1 , $rushtask , time() , $id);
            if ($res) {
                $order =  pdo_get(PDO_NAME . 'rush_order' , ['id' => $id] , ['paytype','actualprice','expressid','disorderid','id','mid','num','activityid']);
                $expressid =$order['expressid'];
                pdo_update('wlmerchant_express' , ['receivetime' => time()] , ['id' => $expressid]);
                $disorderid = $order['disorderid'];
                if (!empty($disorderid)) {
                    $res = pdo_update('wlmerchant_disorder' , ['status' => 1] , ['status' => 0 , 'id' => $disorderid]);
                    if ($res) {
                        //添加结算分销订单到计划任务
                        $distask = [
                            'type'    => 'rush' ,
                            'orderid' => $disorderid
                        ];
                        $distask = serialize($distask);
                        Queue::addTask(3 , $distask , time() , $disorderid);
                    }
                }
                self::yueCashBack($order['mid'],$type,$order['activityid'],$order['num']);
                //抽奖码
                $goodsinfo = pdo_get(PDO_NAME.'rush_activity',array('id' => $order['activityid']),array('name','drawid','paidid'));
                if($goodsinfo['drawid'] > 0 ){
                    Luckydraw::getDrawCode($goodsinfo['drawid'],$order['mid'],$order['id'],'rush');
                }
                //支付有礼
                if($goodsinfo['paidid'] > 0){
                    $paidprid = Paidpromotion::getpaidpr(1,$goodsinfo['paidid'],$order['mid'],$order['id'],$order['paytype'],$order['actualprice'],$order['num'],1);
                    if($paidprid > 0 ){
                        pdo_update('wlmerchant_rush_order' , ['paidprid' => $paidprid] , ['id' => $id]);
                    }
                }
                return 1;
            }else {
                return 0;
            }
        } else {
            $res = pdo_update('wlmerchant_order' , ['status' => 2] , ['id' => $id , 'status' => 4]);
            if($type != 'consumption'){
                //添加结算通用订单到计划任务
                $ordertask = [
                    'type'    => $type ,
                    'orderid' => $id
                ];
                $ordertask = serialize($ordertask);
                Queue::addTask(2 , $ordertask , time() , $id);
            }

            if ($res) {
                if($type == 'consumption'){
                    pdo_update('wlmerchant_consumption_record' , ['status' => 3] , ['orderid' => $id]);
                }
                $order =  pdo_get(PDO_NAME . 'order' , ['id' => $id] , ['expressid','id','disorderid','mid','num','fkid','paytype','price']);
                $expressid = $order['expressid'];
                pdo_update('wlmerchant_express' , ['receivetime' => time()] , ['id' => $expressid]);
                $disorderid = $order['disorderid'];
                if (!empty($disorderid)) {
                    $res = pdo_update('wlmerchant_disorder' , ['status' => 1] , ['status' => 0 , 'id' => $disorderid]);
                    if ($res) {
                        //添加结算分销订单到计划任务
                        $distask = [
                            'type'    => $type ,
                            'orderid' => $disorderid
                        ];
                        $distask = serialize($distask);
                        Queue::addTask(3 , $distask , time() , $disorderid);
                    }
                }
                self::yueCashBack($order['mid'],$type,$order['fkid'],$order['num']);
                //抽奖码
                switch ($type) {
                    case 'groupon':
                        $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $order['fkid']),array('drawid','paidid'));
                        $paidtype = 4;
                        break;//团购商品
                    case 'wlfightgroup':
                        $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $order['fkid']),array('drawid','paidid'));
                        $paidtype = 2;
                        break;//拼团商品
                    case 'bargain':
                        $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $order['fkid']),array('drawid','paidid'));
                        $paidtype = 9;
                        break;//砍价商品
                }
                if($goods['drawid'] > 0 ){
                    Luckydraw::getDrawCode($goods['drawid'],$order['mid'],$order['id'],$type);
                }
                //支付有礼
                if($goods['paidid'] > 0){
                    $paidprid = Paidpromotion::getpaidpr($paidtype,$goods['paidid'],$order['mid'],$order['id'],$order['paytype'],$order['price'],$order['num'],1);
                    if($paidprid > 0 ){
                        pdo_update('wlmerchant_order' , ['paidprid' => $paidprid] , ['id' => $id]);
                    }
                }
                return 1;
            }
            else {
                return 0;
            }
        }
    }

    /**
     * Comment: 扣除代理金额
     * Author: wlf
     * Date: 2021/09/18 11:26
     */
    function deductAgencyAmount($aid,$money,$remark){
        $supminey = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$aid),'nowmoney');
        if($supminey > $money){
            $nowmoney = sprintf("%.2f",$supminey - $money);
            $res = pdo_update(PDO_NAME.'agentusers',array('nowmoney' => $nowmoney),array('id' => $aid));
            Store::addcurrent(2,15,$aid,-$money,$nowmoney,0,$remark);
            return $res;
        }else{
            return 0;
        }
    }
}
