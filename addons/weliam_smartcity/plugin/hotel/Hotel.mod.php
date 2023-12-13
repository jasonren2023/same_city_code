<?php
defined('IN_IA') or exit('Access Denied');


class Hotel{



    /**
     * Comment: 默认标签
     * Author: wlf
     * Date: 2022/08/18 11:42
     * @return array
     */
    public static function defaultLabel(){
        global $_W;
        return [
            [
                'title' => 'wifi',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/wifi.png',
                'type'  => 1
            ],
            [
                'title' => '烧水壶',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/reshuihu.png',
                'type'  => 1
            ],
            [
                'title' => '淋浴',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/linyu.png',
                'type'  => 1
            ],
            [
                'title' => '电视',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/dianshi.png',
                'type'  => 1
            ],
            [
                'title' => '空调',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/kongtiao.png',
                'type'  => 1
            ],
            [
                'title' => '电吹风',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/chuifeng.png',
                'type'  => 1
            ],
            [
                'title' => '洗漱用品',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/yaju.png',
                'type'  => 1
            ],
            [
                'title' => '电脑',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/diannao.png',
                'type'  => 1
            ],
            [
                'title' => '浴缸',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/yugang.png',
                'type'  => 1
            ],
            [
                'title' => '热水器',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/reshuiqi.png',
                'type'  => 1
            ],
            [
                'title' => '智能门锁',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/mensuo.png',
                'type'  => 1
            ],
            [
                'title' => '暖气',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/nuanqi.png',
                'type'  => 1
            ],
            [
                'title' => '冰箱',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/bingxiang.png',
                'type'  => 1
            ],
            [
                'title' => '沐浴露',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/muyulu.png',
                'type'  => 1
            ],
            [
                'title' => '拖鞋',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/tuoxie.png',
                'type'  => 1
            ],
            [
                'title' => '毛巾',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/yujin.png',
                'type'  => 1
            ],
            [
                'title' => '免费早餐',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/canyin.png',
                'type'  => 2
            ],
            [
                'title' => '叫醒服务',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/shizhong.png',
                'type'  => 2
            ],
            [
                'title' => '自助餐厅',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/canju.png',
                'type'  => 3
            ],
            [
                'title' => '停车场',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/tingchechang.png',
                'type'  => 3
            ],
            [
                'title' => '咖啡厅',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/kafeiji.png',
                'type'  => 3
            ],
            [
                'title' => '行李寄存',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/xinglijicun.png',
                'type'  => 3
            ],
            [
                'title' => '专车接送',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/zhuanche.png',
                'type'  => 3
            ],
            [
                'title' => '免费电话',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/dianhua.png',
                'type'  => 3
            ],
            [
                'title' => '电梯',
                'image' => $_W['siteroot'].'addons/'.MODULE_NAME.'/h5/resource/wxapp/hotel/dianti.png',
                'type'  => 3
            ],

        ];




    }



    /**
     * Comment: 处理房间信息
     * Author: wlf
     * Date: 2022/08/31 10:23
     * @return array
     */
    public static function getRoomInfo($room,$halfmember){
        global $_W;
        $room['images'] = beautifyImgInfo($room['images']);
        if($room['roomtype'] == 2){
            $room['suite'] = unserialize($room['suite']);
        }else{
        	$room['suite'] = '';
        }
        $room['thumb'] = tomedia($room['thumb']);
        //标签获取
        $room['facilities'] ='('.implode(',',unserialize($room['facilities'])).')';
        $room['facilities'] = pdo_fetchall("SELECT title,image FROM ".tablename('wlmerchant_hotel_label')."WHERE id IN  {$room['facilities']} ORDER BY sort DESC,id DESC");
        if(!empty($room['facilities'])){
            foreach ($room['facilities'] as &$label2){
                $label2['image'] = tomedia($label2['image']);
            }
        }
        $room['service'] ='('.implode(',',unserialize($room['service'])).')';
        $room['service'] = pdo_fetchall("SELECT title,image FROM ".tablename('wlmerchant_hotel_label')."WHERE id IN  {$room['service']} ORDER BY sort DESC,id DESC");
        if(!empty($room['service'])){
            foreach ($room['service'] as &$label3){
                $label3['image'] = tomedia($label3['image']);
            }
        }
        //额外信息 会员特权
        if($room['vipstatus'] == 1){
            $room['vipdiscount'] = WeliamWeChat::getVipDiscount($room['viparray'],$halfmember['levelid']);
            if($room['vipdiscount'] > 0){
                $room['extrainfo'][] = array('title'=> '会员折扣','content'=> '会员下单立减'.$room['vipdiscount'].'元');
            }
        }else if($room['vipstatus'] == 2){
            $room['extrainfo'][] = array('title'=> '会员特权','content'=> '此房间仅会员能下单预约');
        }
        unset($room['viparray']);
        unset($room['vipstatus']);
        //其他信息
        if($room['drawid'] > 0){
            $draw = pdo_get('wlmerchant_luckydraw',array('id' => $room['drawid'],'status' => 1),array('title'));
            if(!empty($draw)){
                $room['extrainfo'][] = array('title'=> '抽奖活动','content'=> '入住房间后可以参与【'.$draw['title'].'】抽奖活动');
            }
        }
        if($room['fullreduceid'] > 0){
            $fullreduce = pdo_get('wlmerchant_fullreduce_list',array('id' => $room['fullreduceid'],'status' => 1),array('title'));
            if(!empty($draw)){
                $room['extrainfo'][] = array('title'=> '满减活动','content'=> '下单预约时享受参与【'.$fullreduce['title'].'】满减优惠');
            }
        }
        if($room['paidid'] > 0){
            $paid = pdo_get('wlmerchant_payactive',array('id' => $room['paidid'],'status' => 1),array('title'));
            if(!empty($paid)){
                $room['extrainfo'][] = array('title'=> '支付有礼','content'=> '下单预约支付以后可以获得【'.$paid['title'].'】活动奖励');
            }
        }
        if($room['creditmoney'] > 0 && $_W['wlsetting']['creditset']['dkstatus'] > 0){
            $jifentext = $_W['wlsetting']['trade']['credittext'] ? : '积分';
            $room['extrainfo'][] = array('title'=> $jifentext.'抵扣','content'=> '每晚最多可以用'.$jifentext.'抵扣'.$room['creditmoney'].'元');
        }
        return $room;
    }

    /**
     * Comment: 计算明细与优惠
     * Author: wlf
     * Date: 2022/08/31 11:01
     * @return array
     */
    public static function getRoomDetailed($room,$halfmember,$starttime,$endtime,$num,$infopage = 0,$creditstatus = 0){
        global $_W;
        $jifentext = $_W['wlsetting']['trade']['credittext'] ? : '积分';
        //会员优惠
        $vipdiscount = 0;
        if ($room['vipstatus'] == 1 && !empty($halfmember)) {
            $vipdiscount = WeliamWeChat::getVipDiscount($room['viparray'],$halfmember['levelid']);
        }


        //明细
        //计算天数
        $alltime = $endtime - $starttime;
        if($room['roomtype'] == 3){
            $day = ceil($alltime/3600);
        }else{
            $day = intval($alltime/86400);
        }
        $price = $room['price'] - $vipdiscount;
        $detailed = [];
        //房费
        for ($i=0; $i < $day;$i++){
            $deta = [];
            if($room['roomtype'] == 3){
                $daytime = $i * 3600 + $starttime;
                $deta['title'] = date('m-d H:i',$daytime);
            }else{
                $daytime = $i * 86400 + $starttime;
                $deta['title'] = date('Y-m-d',$daytime);
            }
            $deta['num'] = $num;
            if($vipdiscount > 0){
                $deta['money'] = $price.'('.$room['price'].'-'.$vipdiscount.')';
            }else{
                $deta['money'] = $price;
            }
            $deta['symbol'] = 0;
            $detailed[] = $deta;
        }

        $allnum = $day*$num;

        $allmoney = $price * $allnum;

        //押金
        $fjyj['title'] = '房间押金(间)';
        $fjyj['num'] = $num;
        $fjyj['money'] = $room['deposit'];
        $fjyj['symbol'] = 0;
        $detailed[] = $fjyj;



        //积分抵扣
        $credit = 0; //可使用积分
        $creditdiscount = 0;
        $allcredit = sprintf("%.2f" , $_W['wlmember']['credit1']);
        if ($room['creditmoney']>0 && $_W['wlsetting']['creditset']['dkstatus'] ) {
            //每一份可以使用的积分
            $onedkcredit = sprintf("%.2f" , $room['creditmoney'] * $_W['wlsetting']['creditset']['proportion']);
            //总共需要积分
            $dkcredit = sprintf("%.2f" , $onedkcredit * $allnum);
            //用户所有积分
            if ($allcredit < $dkcredit) {
                $dkcredit = $allcredit;
            }
            //抵扣金额
            $dkmoney  = sprintf("%.2f" , $dkcredit / $_W['wlsetting']['creditset']['proportion']);
            $credit = $dkcredit;  //可使用积分
            $creditdiscount = $dkmoney; //积分抵扣金额

            if($creditstatus > 0){
                $jfdk['title'] = $jifentext.'抵扣';
                $jfdk['num'] = 0;
                $jfdk['money'] = $creditdiscount;
                $jfdk['symbol'] = 1;
                $detailed[] = $jfdk;
            }
        }

        if($creditstatus > 0){
            $allmoney = $allmoney - $creditdiscount;
        }

        //满减优惠
        if($room['fullreduceid']>0){
            $fullreduce = pdo_get('wlmerchant_fullreduce_list',array('id' => $room['fullreduceid'],'status' => 1),array('rules','title'));
            if(!empty($fullreduce)){

                $mjyh['title'] = $fullreduce['title'];
                $mjyh['num'] = 0;
                $mjyh['money'] = Fullreduce::getFullreduceMoney($allmoney,$room['fullreduceid']);
                $mjyh['symbol'] = 1;
                $detailed[] = $mjyh;

                $fullreducelist['title'] = $fullreduce['title'];
                $fullreducelist['detail'] = unserialize($fullreduce['rules']);

                $allmoney = $allmoney - $mjyh['money'];

            }
        }
        $allmoney = $room['deposit'] * $num + $allmoney;

        $discountInfo = [
            'vipdiscount' => $vipdiscount,
            'credit'      => $credit,
            'creditdiscount' => $creditdiscount,
            'allcredit'   => $allcredit,
            'fullreduce'  => $fullreducelist
        ];
        $data = [
            'discountInfo' => $discountInfo,
            'detailed'     => $detailed,
            'allmoney'     => $allmoney
        ];

        return $data;
    }


    /**
     * Comment: 计算订单金额
     * Author: wlf
     * Date: 2022/09/02 10:33
     * @return array
     */
    public static function calculationMoney($room,$halfmember,$starttime,$endtime,$num,$creditstatus = 0){
        global $_W;
        //计算天数
        $alltime = $endtime - $starttime;
        if($room['roomtype'] == 3){
            $day = ceil($alltime/3600);
        }else{
            $day = intval($alltime/86400);
        }
        $allnum = $day*$num;
        //原价
        $price = $room['price'];
        $allmoney = $allgoodsmoney = sprintf("%.2f",$price * $allnum);
        //押金
        $deposit = $room['deposit'] * $num;
        //会员优惠
        $allvipdiscount = 0;
        $vipbuy = 0;
        if ($room['vipstatus'] == 1 && !empty($halfmember)) {
            $vipdiscount = WeliamWeChat::getVipDiscount($room['viparray'],$halfmember['levelid']);
            $allvipdiscount = sprintf("%.2f",$vipdiscount * $allnum);
            $vipbuy = 1;
            $vipprice = $price - $vipdiscount;
        }

        //积分抵扣
        $credit = 0; //可使用积分
        $creditdiscount = 0;
        $allcredit = sprintf("%.2f" , $_W['wlmember']['credit1']);
        if ($creditstatus > 0) {
            //每一份可以使用的积分
            $onedkcredit = sprintf("%.2f" , $room['creditmoney'] * $_W['wlsetting']['creditset']['proportion']);
            //总共需要积分
            $dkcredit = sprintf("%.2f" , $onedkcredit * $allnum);
            //用户所有积分
            if ($allcredit < $dkcredit) {
                $dkcredit = $allcredit;
            }
            //抵扣金额
            $dkmoney  = sprintf("%.2f" , $dkcredit / $_W['wlsetting']['creditset']['proportion']);
            $credit = $dkcredit;  //可使用积分
            $creditdiscount = $dkmoney; //积分抵扣金额
        }

        //满减优惠
        $fullreducemoney = 0;
        if($room['fullreduceid']>0){
            $fullreduce = pdo_get('wlmerchant_fullreduce_list',array('id' => $room['fullreduceid'],'status' => 1),array('rules','title'));
            if(!empty($fullreduce)){
                $fullreducemoney = Fullreduce::getFullreduceMoney($allmoney,$room['fullreduceid']);
            }
        }

        //在线红包 未添加
        $redpackmoney = 0;

        //计算实际支付金额
        $allmoney = $allmoney + $deposit;
        $nowmoney = sprintf("%.2f",$allmoney - $allvipdiscount - $creditdiscount - $fullreducemoney);

        //计算结算金额
        $merchant = pdo_get('wlmerchant_merchantdata' , ['id' => $room['sid']] , ['groupid','marketstatus' ,'settlementtext']);
        $marketstatus = $merchant['marketstatus'];
        if($room['independent'] > 0){
            $settmoney = $room['settlementmoney'];
            if(empty($marketstatus) && $vipbuy > 0 ){
                $viparray = unserialize($room['viparray']);
                $storeset = $viparray[$halfmember['levelid']]['storeset'];
                $settmoney = $settmoney - $storeset;
            }
            $allsettmoney = $settmoney * $allnum;
        }else{
            $sett     = unserialize($merchant['settlementtext']);
            $settlementrate    = $sett['hotelsett'];
            $vipsettlementrate = $sett['hotelvip'];

            if ($settlementrate > 0 && empty($vipbuyflag)) {  //按商户默认的结算
                $allsettmoney = sprintf("%.2f" , $price * $settlementrate / 100 * $allnum);
            }
            else if ($vipsettlementrate > 0 && $vipbuyflag) {
                $allsettmoney = sprintf("%.2f" , $vipprice * $vipsettlementrate / 100 * $allnum);
            }
            else {  //按商户分组默认结算
                $grouprete = pdo_getcolumn(PDO_NAME . 'chargelist' , ['id' => $merchant['groupid']] , 'defaultrate');
                if (empty($vipbuyflag)) {
                    $allsettmoney = sprintf("%.2f" , $price * $grouprete / 100 * $allnum);
                }
                else {
                    $allsettmoney = sprintf("%.2f" , $vipprice * $grouprete / 100 * $allnum);
                }
            }
        }
        if($marketstatus > 0){
            $allsettmoney = sprintf("%.2f" ,$allsettmoney - $allvipdiscount - $creditdiscount - $fullreducemoney);
        }
        $allsettmoney = $allsettmoney + $deposit;

        $data = [
            'allmoney' => $allmoney,
            'deposit'  => $deposit,
            'allvipdiscount' => $allvipdiscount,
            'vipbuy' => $vipbuy,
            'credit' => $credit,
            'creditdiscount' => $creditdiscount,
            'fullreducemoney' => $fullreducemoney,
            'redpackmoney' => $redpackmoney,
            'nowmoney' => $nowmoney,
            'allsettmoney' => $allsettmoney,
            'allgoodsmoney' => $allgoodsmoney
        ];

        return $data;
    }



    /**
     * Comment: 支付回调
     * Author: wlf
     * Date: 2022/09/02 14:05
     * @return array
     */
    static function payhotelOrderNotify($params) {
        global $_W;
        Util::wl_log('payResult_notify', PATH_PLUGIN . "hotel/data/", $params); //写入异步日志记录
        $order_out = pdo_fetch("select * from" . tablename(PDO_NAME . 'order') . "where orderno='{$params['tid']}'");
        $room = pdo_get('wlmerchant_hotel_room',array('id' => $order_out['fkid']),array('price','deposit','name','id','uniacid','aid','isdistri','disarray','status','sid','isdistristatus','dissettime'));
        $_W['uniacid']   = $order_out['uniacid'];
        $_W['aid']       = $order_out['aid'];
        $_W['wlsetting']['creditset'] = Setting::wlsetting_read('creditset');

        $data = ['status'  => 1,'paytime' => time(),'paytype' =>  $params['type']];
        if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
        if ($order_out['status'] == 0 || $order_out['status'] == 5){
            //创建核销码
            Order::createSmallorder($order_out['id'],7);


            //处理分销
            if($order_out['cerditmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                $nodis = 1;
            }else{
                $nodis = 0;
            }
            if (p('distribution') && empty($room['isdistri'])  && empty($nodis)) {

                $disarray = unserialize($room['disarray']);
                $disprice = sprintf("%.2f",$order_out['goodsprice'] - $order_out['vipdiscount']);
                $disorderid = Distribution::disCore($order_out['mid'], $disprice, $disarray, $order_out['num'], 0, $order_out['id'], 'hotel', $room['dissettime'],$room['isdistristatus']);
                $data['disorderid'] = $disorderid;
            }

            //支付有礼
            if($room['paidid'] > 0){
                $data['paidprid'] = Paidpromotion::getpaidpr(7,$room['paidid'],$order_out['mid'],$order_out['id'],$data['paytype'],$order_out['price'],$order_out['num']);
            }
            //过期时间
            $data['estimatetime'] = $order_out['endtime'];
            //计算通知时间
            $data['remindtime'] = Order::remindTime($data['estimatetime']);
            pdo_update(PDO_NAME . 'order', $data, array('orderno' => $params['tid'])); //更新订单状态

            //通知商户
            News::addSysNotice($order_out['uniacid'],2,$order_out['sid'],0,$order_out['id']);
            Store::addFans($order_out['sid'], $order_out['mid']);
            News::paySuccess($order_out['id'], 'hotel');
            //小票打印
            //Order::sendPrinting($order_out['id'],'hotel');
        }


    }



    /**
     * Comment: 退款订单
     * Author: wlf
     * Date: 2022/09/21 11:13
     * @return array
     */
    static function refundorder($id, $money = 0, $unline = '',$checkcode = '',$afterid = 0) {
        global $_W;
        $order = pdo_get(PDO_NAME . 'order', array('id' => $id));
        if ($unline) {
            $res['status'] = 1;
        } else {
            $res = wlPay::refundMoney($id, $money, '酒店预约退款', 'hotel', 2);
        }
        if ($res['status']) {
            //修改退款申请记录
            pdo_update('wlmerchant_aftersale',array('status' => 2),array('orderid' => $id,'plugin' => 'hotel'));
            if ($order['applyrefund']) {
                pdo_update('wlmerchant_order', array('status' => 7, 'refundtime' => time(), 'applyrefund' => 2), array('id' => $order['id']));
                $reason = '买家申请退款。';
            } else {
                pdo_update('wlmerchant_order', array('status' => 7, 'refundtime' => time()), array('id' => $order['id']));
                $reason = '酒店系统退款。';
            }
            News::refundNotice($id,'hotel',$money,$reason);
        } else {
            pdo_fetch("update" . tablename('wlmerchant_order') . "SET failtimes = failtimes+1 WHERE id = {$id}");
        }
        return $res;
    }

    /**
     * Comment: 核销订单
     * Author: wlf
     * Date: 2022/09/21 17:52
     * @return array
     */
    static function hexiaoorder($id, $mid, $num = 1, $type = 1,$checkcode='',$afterid = 0){  //1输码 2扫码 3后台 4密码
        global $_W;
        $order = pdo_get('wlmerchant_order', array('id' => $id));
        $order['usetimes'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE plugin = 'hotel' AND  orderid = {$id} AND status = 1");
        if ($order['usetimes'] < $num) {
            if (is_mobile()) {
                die(json_encode(array("errno" => 1, 'message' => '此订单已核销','data'=>'')));
            } else {
                show_json(0, '此订单已核销');
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

        $smallorder = pdo_fetch("SELECT * FROM ".tablename('wlmerchant_smallorder')."WHERE plugin = 'hotel' AND  orderid = {$id} AND status = 1");
        if($smallorder){
            if($mid){
                $uid = pdo_getcolumn(PDO_NAME.'merchantuser',array('storeid'=>$order['sid'],'mid'=>$mid),'id');
            }else{
                $uid = 0;
            }
            $rushoverres = Order::finishSmallorder($smallorder['id'],$uid,$type);
        }else{
            if (is_mobile()) {
                die(json_encode(array('errno' => 1, 'message' => '无可用核销码')));
            } else {
                show_json(0, '无可用核销码');
            }
        }

        $checksmallstatus = pdo_getcolumn(PDO_NAME.'smallorder',array('id'=>$smallorder['id']),'status');
        if($checksmallstatus != 2){
            $checkflag = 0;
        }else{
            $checkflag = 1;
        }
        if ($rushoverres  && $checkflag > 0) {
            $room = pdo_get('wlmerchant_hotel_room', array('id' => $order['fkid']), array('name'));
            //发送核销成功通知
            $info = array(
                'first'      => '您好，您的商品已经成功核销' ,
                'goods_name' => $room['name'],//商品名称
                'goods_num'  => $num,//商品数量
                'time'       => date('Y-m-d H:i:s',time()),//核销时间
                'order_no'   => $order['orderno'],//订单编号
                'remark'     => '如有疑问请联系客服'
            );
            TempModel::sendInit('write_off',$order['mid'],$info,$_W['source']);
            if ($type == 2) {
                $info2 = array(
                    'first'      => '核销操作成功' ,
                    'goods_name' => $room['name'],//商品名称
                    'goods_num'  => $num,//商品数量
                    'time'       => date('Y-m-d H:i:s',time()),//核销时间
                    'order_no'   => $order['orderno'],//订单编号
                    'remark'     => '订单编号:['.$order['orderno'].']',
                );
                TempModel::sendInit('write_off',$_W['mid'],$info2,$_W['source']);
            }
            SingleMerchant::verifRecordAdd($order['aid'], $order['sid'], $order['mid'], 'rush', $order['id'], $order['checkcode'], $room['name'], $type, $num);
            return 1;
        } else {
            return 0;
        }



    }

}