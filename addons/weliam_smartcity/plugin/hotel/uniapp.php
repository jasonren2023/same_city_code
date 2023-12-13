<?php
/**
 * Created by PhpStorm.
 * User: wlf
 * Date: 2022/8/24
 * Time: 11:15
 */

defined('IN_IA') or exit('Access Denied');
class HotelModuleUniapp extends Uniapp{


    /**
     * Comment: 获取首页信息
     * Author: wlf
     * Date: 2022/08/24 11:19
     */

    public function hotelIndex(){
        global $_W, $_GPC;
        $set = Setting::agentsetting_read('hotel');
        if(empty($set['switch'])){
            $this->renderError('酒店组件已关闭');
        }
        $text = [
            'tjjdtext' => $set['tjjdtext'] ? : '推荐酒店',
            'tjjddesc' => $set['tjjddesc'] ? : '精心挑选，经济舒适',
            'zxzxtext' => $set['zxzxtext'] ? : '最新资讯',
            'zxzxdesc' => $set['zxzxdesc'] ? : '酒店资讯，活动信息，尽在这里',
            'fjjdtext' => $set['fjjdtext'] ? : '附近酒店',
            'fjjddesc' => $set['fjjddesc'] ? : '附近酒店，一览无余',
        ];
        //幻灯片
        $advs =  pdo_fetchall("SELECT link,thumb FROM ".tablename('wlmerchant_adv')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND enabled = 1 AND  type = 22 ORDER BY displayorder DESC,id DESC");
        if(!empty($advs)){
            foreach ($advs as &$add){
                $add['thumb'] = tomedia($add['thumb']);
            }
        }else{
            $advs = [];
        }
        //推荐酒店
        $recommendHotel = pdo_fetchall("SELECT storename,logo,score,address,id,hotelprice FROM ".tablename('wlmerchant_merchantdata')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND enabled = 1 AND  status = 2 AND hotelstatus = 1 AND hotelindex = 1 ORDER BY listorder DESC,id DESC LIMIT 5");
        if(!empty($recommendHotel)){
            foreach ($recommendHotel as &$hotel){
                $hotel['logo'] = tomedia($hotel['logo']);
            }
        }else{
            $recommendHotel = [];
        }
        //资讯信息
        if($set['headid'] > 0){
            $headline = pdo_fetchall("SELECT id,one_id,two_id,title,display_img,release_time FROM ".tablename('wlmerchant_headline_content')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND one_id ={$set['headid']} ORDER BY release_time DESC LIMIT 5");
            if(!empty($headline)){
                foreach ($headline as &$line){
                    $line['display_img'] = tomedia($line['display_img']);
                    if(empty($line['two_id'])){
                        $line['two_id'] = $line['one_id'];
                    }
                    $line['classname'] = pdo_getcolumn(PDO_NAME.'headline_class',array('id'=>$line['two_id']),'name');
                    $uptime = time() - $line['release_time'];
                    if($uptime > 86400){
                        $day = floor($uptime/86400);
                        $line['uptime'] = $day.'天前';
                    }else if($uptime > 3600){
                        $hour = floor($uptime/3600);
                        $line['uptime'] = $hour.'小时前';
                    }else{
                        $min = ceil($uptime/60);
                        $line['uptime'] = $min.'分钟前';
                    }
                    unset($line['one_id']);unset($line['two_id']);unset($line['release_time']);
                }
            }else{
                $headline = [];
            }
        }else{
            $headline = [];
        }
        $data = [
            'text' => $text,
            'adv' => $advs,
            'recommend' => $recommendHotel,
            'headline' => $headline
        ];
        $this->renderSuccess('酒店首页',$data);
    }


    /**
     * Comment: 酒店列表
     * Author: wlf
     * Date: 2022/08/24 15:53
     */
    public function hotelList(){
        global $_W, $_GPC;
        $page       = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * $page_index - $page_index;
        $lng        = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度 104.0091133118 经度
        $lat        = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度 30.5681964123  纬度
        $sort      = $_GPC['sort'] ? $_GPC['sort'] : 0;//排序方式  0智能 1价格 2评分 3距离 4最新
        $regionId   = $_GPC['region_id'] ? : 0; //筛选 地区id
        $search     = trim($_GPC['search']); //搜索
        $minprice   = $_GPC['minprice'] ? $_GPC['minprice'] : 0;  //筛选 最小金额
        $maxprice   = $_GPC['maxprice'] ? $_GPC['maxprice'] : 0;  //筛选 最大金额

        //生成查询条件
        $where = " WHERE  uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND enabled = 1 AND status = 2 AND hotelstatus = 1";
        if($regionId > 0){
            $where .= " AND  ( provinceid = {$regionId} OR areaid = {$regionId} OR distid = {$regionId} )";
        }
        if(!empty($search)){
            $where .= " AND  ( storename LIKE '%{$search}%' OR `describe` LIKE '%{$search}%' OR `address` LIKE '%{$search}%' )";
        }
        if($minprice > 0) {
            $where .= " AND hotelprice > {$minprice}";
        }
        if($maxprice > 0) {
            $where .= " AND hotelprice < {$maxprice}";
        }
        //生成排序条件 0智能 1价格 2评分 3距离 4最新
        switch ($sort) {
            case 1:
                $order = " ORDER BY hotelprice ASC ";
                break;//1价格
            case 2:
                $order = " ORDER BY score DESC,id DESC ";
                break;//2评分
            case 3:
                $order = " ORDER BY distance ASC,id DESC ";
                break;//3距离
            case 4:
                $order = " ORDER BY createtime DESC,id DESC ";
                break;//4最新
            default :
                $order = " ORDER BY listorder DESC,id DESC ";
                break;//距离排序
        }
        //距离计算
        $distances = "ROUND(CASE 
                            WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.138 * 2 * ASIN(
                                    SQRT(
                                        POW(SIN(({$lat} * PI() / 180 - lat * PI() / 180) / 2),2) +
                                            COS({$lat} * PI() / 180) * COS(lat * PI() / 180) *
                                            POW(SIN(({$lng} * PI() / 180 - lng * PI() / 180) / 2),2)
                                        )
                                    ) * 1000
                                ) 
                            ELSE ''
                        END)";
        //sql语句生成
        $limit     = " LIMIT {$page_start},{$page_index} ";
        $field     = "{$distances} as distance,id,logo,storename,score,hotellabel,hotelprice";
        $sql       = "SELECT {$field} FROM ".tablename(PDO_NAME."merchantdata");
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        if(!empty($list)){
            foreach ($list as &$li){
                $li['logo'] = tomedia($li['logo']);
                //标签
                $li['hotellabel'] = unserialize($li['hotellabel']);
                if(!empty($list)){
                    foreach ($li['hotellabel'] as &$label){
                        $label = pdo_getcolumn(PDO_NAME.'hotel_label',array('id'=>$label),'title');
                    }
                }else{
                    $li['hotellabel'] = [];
                }
                //消费人数
                $li['buynum'] = 5;
                //距离
                if($li['distance'] > 0){
                    if($li['distance'] < 1000){
                        $li['distance'] = $li['distance'].'m';
                    } else{
                        $li['distance'] = sprintf("%.2f",$li['distance'] / 1000).'km';
                    }
                }
            }
        }else{
            $list = [];
        }

        $data['list'] = $list;
        //总页数
        if($page == 1){
            $total  = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename(PDO_NAME . "merchantdata") . $where);
            $data['total'] = ceil($total / $page_index);
        }
        $this->renderSuccess('酒店列表',$data);
    }

    /**
     * Comment: 酒店详情
     * Author: wlf
     * Date: 2022/08/25 09:53
     */
    public function hotelDetail(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $lng = $_GPC['lng'];
        $lat = $_GPC['lat'];

        if(empty($id)){
            $this->renderError('参数错误，请返回重试');
        }
        $set = Setting::agentsetting_read('hotel');
        if(empty($set['switch'])){
            $this->renderError('酒店组件已关闭');
        }
        $store = pdo_get('wlmerchant_merchantdata',array('id' => $id),array('proportion','payonline','communityid','storename','mobile','address','enabled','status','panorama','videourl','adv','score','lat','lng','introduction','hotellabel','deliverystatus'));
        if($store['status'] != 2 || $store['enabled'] != 1 ){
            $this->renderError('酒店已暂停营业，请返回');
        }
        unset($store['status']);
        unset($store['enabled']);
        //图片 幻灯片 视频
        $store['adv'] = beautifyImgInfo($store['adv']);
        $store['videourl'] = tomedia($store['videourl']);
        //直线距离
        if (!empty($lat) && !empty($lng)) {
            $store['distance'] = Store::getdistance($store['lng'] , $store['lat'] , $lng , $lat);
            if ($store['distance'] > 1000) {
                $store['distance'] = (floor(($store['distance'] / 1000) * 10) / 10) . "km";
            }
            else {
                $store['distance'] = round($store['distance']) . "m";
            }
        }
        //政策与设施
        $store['introduction'] = htmlspecialchars_decode($store['introduction']);
        $store['hotellabel'] ='('.implode(',',unserialize($store['hotellabel'])).')';
        $store['hotellabel'] = pdo_fetchall("SELECT title,image FROM ".tablename('wlmerchant_hotel_label')."WHERE id IN  {$store['hotellabel']} ORDER BY sort DESC,id DESC");
        if(!empty($store['hotellabel'])){
            foreach ($store['hotellabel'] as &$label){
                $label['image'] = tomedia($label['image']);
            }
        }
        //酒店超市
        if($store['deliverystatus'] > 0){
	        $citydelivery =  pdo_fetchall("SELECT name,thumb,price,id FROM ".tablename('wlmerchant_delivery_activity')." WHERE sid =  {$id} ORDER BY sort DESC,id DESC LIMIT 10");
	        if(!empty($citydelivery)){
	            foreach ($citydelivery as &$delive){
	                $delive['thumb'] = tomedia($delive['thumb']);
	            }
	        }
        }else{
        	$citydelivery = [];
        }
        
        //房间列表
        $roomlist = pdo_fetchall("SELECT name,thumb,images,price,id,roomtype,size,suite,bednum,roomnum,breakfast,balcony,facilities,service,roomdesc,remind,vipstatus,viparray,fullreduceid,paidid,drawid,creditmoney FROM ".tablename('wlmerchant_hotel_room')." WHERE sid =  {$id} AND status = 1 ORDER BY sort DESC,id DESC");
        $singleroom = [];
        $suiteroom = [];
        $hourroom = [];
        if(!empty($roomlist)){
            $halfmember = WeliamWeChat::VipVerification($_W['mid']); //会员状态
            if(empty($halfmember)){
                $halfmember['levelid'] = -1;
            }
            foreach ($roomlist as &$room){
                $room = Hotel::getRoomInfo($room,$halfmember);
                if($room['roomtype'] == 1){
                    $singleroom[] = $room;
                }else if($room['roomtype'] == 2){
                    $suiteroom[] = $room;
                }else{
                    $hourroom[] = $room;
                }
            }
        }
        //客户评论
        $commentlist = pdo_fetchall("SELECT mid,pic,idoforder,createtime,star FROM ".tablename('wlmerchant_comment')."WHERE uniacid = {$_W['uniacid']} AND sid = {$id} AND status = 1 AND checkone = 2 AND plugin = 'hotel' ORDER BY createtime DESC LIMIT 5");
        $allnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_comment')." WHERE  uniacid = {$_W['uniacid']} AND sid = {$id} AND status = 1 AND checkone = 2 AND plugin = 'hotel' ");
        if(!empty($commentlist)){
            foreach ($commentlist as &$comment){
                $member = pdo_get('wlmerchant_member',array('id' => $comment['mid']),array('nickname','encodename','avatar'));
                $comment['nickname'] = !empty($member['encodename']) ? base64_decode($member['encodename']) : $member['nickname'];
                $comment['avatar'] = tomedia($member['avatar']);
                //入住时间 和 房间名 暂无
                $comment['settletime'] = date('Y-m-d',$comment['createtime']);
                $comment['roomname'] = '大床房';
                $comment['pic'] = beautifyImgInfo($comment['pic']);
                $comment['createtime'] = date('Y-m-d',$comment['createtime']);

                unset($comment['idoforder']);
            }
        }
        $comments = [
            'allnum' => $allnum,
            'list' => $commentlist
        ];

        //获取社群信息
        if($store['communityid'] > 0){
            $community = Commons::getCommunity($store['communityid']);
        }
        //获取图片高度
        $proportion = unserialize($store['proportion']);
        $store['imgstyle']['width'] = $proportion['shopwidth'];
        $store['imgstyle']['height'] = $proportion['shopheight'];
        if(empty($store['imgstyle']['width'])){
            $store['imgstyle']['width'] = !empty(trim($_W['wlsetting']['base']['width'])) ? trim($_W['wlsetting']['base']['width']) : 750;
        }
        if(empty($store['imgstyle']['height'])){
            $store['imgstyle']['height'] = !empty(trim($_W['wlsetting']['base']['height'])) ? trim($_W['wlsetting']['base']['height']) :  560;
        }

        $data = [
            'storeinfo' => $store,  //商户基础信息
            'singleroom' => $singleroom,  //标间列表
            'suiteroom' => $suiteroom, //套房列表
            'hourroom' => $hourroom, //钟点房列表
            'citydelivery' => $citydelivery, //酒店超市
            'comment' => $comments, //评论
            'community' => $community //社群
        ];

        $this->renderSuccess('商户详情页',$data);
    }

    /**
     * Comment: 下单页面接口
     * Author: wlf
     * Date: 2022/08/25 17:19
     */

    public function submitOrder(){
        global $_W, $_GPC;
        $id = $_GPC['id']; //房间id
        $starttime = $_GPC['starttime'];
        $endtime = $_GPC['endtime'];
        if(empty($id)){
            $this->renderError('参数错误，请返回重试');
        }
        $set = Setting::agentsetting_read('hotel');
        if(empty($set['switch'])){
            $this->renderError('酒店组件已关闭');
        }
        $room = pdo_get('wlmerchant_hotel_room',array('id' => $id),array('name','thumb','images','price','sid','roomtype','size','suite','bednum','roomnum','breakfast','balcony','facilities','service','roomdesc','remind','vipstatus','viparray','fullreduceid','paidid','drawid','deposit','status','creditmoney'));
        if($room['status'] != 1){
            $this->renderError('房间已停止预定');
        }
        $store = pdo_get('wlmerchant_merchantdata',array('id' => $room['sid']),array('status','enabled','hotelstatus'));
        if($store['status'] != 2 || $store['enabled'] != 1 || $store['hotelstatus'] != 1 ){
            $this->renderError('酒店已暂停营业，请返回');
        }
        $halfmember = WeliamWeChat::VipVerification($_W['mid']); //会员状态

        //设置信息
        $setinfo = [
            'cancel_desc' => $set['cancel_desc'],
            'tip_desc'    => $set['tip_desc'],
            'agreement'   => $set['agreement'],
        ];
        //优惠信息
        $disInfo = Hotel::getRoomDetailed($room,$halfmember,$starttime,$endtime,1,1,0);
        $room = Hotel::getRoomInfo($room,$halfmember);
        $room['storename'] =  pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$room['sid']),'storename');

        //用户信息
        $member = pdo_get('wlmerchant_member',array('id' => $_W['mid']),array('mobile','nickname','encodename','realname'));
        if(!empty($member['realname'])){
            $user['name'] =  $member['realname'];
        }else{
            $user['name'] =  !empty($member['encodename']) ?  base64_decode($member['encodename']) : $member['nickname'] ;
        }
        $user['mobile'] = $member['mobile'];

        $data = [
            'roominfo' => $room,
            'disInfo'  => $disInfo,
            'setinfo'  => $setinfo,
            'member'   => $user
        ];

        $this->renderSuccess('订单提交页面',$data);
    }

    /**
     * Comment: 计算金额明细
     * Author: wlf
     * Date: 2022/08/31 11:30
     */

    public function calculationDetails(){
        global $_W, $_GPC;
        $id = $_GPC['id']; //房间id
        $starttime = $_GPC['starttime'];
        $endtime = $_GPC['endtime'];
        $num = $_GPC['num'];
        $creditstatus = $_GPC['creditstatus'];

        $room = pdo_get('wlmerchant_hotel_room',array('id' => $id),array('name','thumb','images','price','sid','roomtype','size','suite','bednum','roomnum','breakfast','balcony','facilities','service','roomdesc','remind','vipstatus','viparray','fullreduceid','paidid','drawid','deposit','creditmoney'));
        $halfmember = WeliamWeChat::VipVerification($_W['mid']); //会员状态

        $disInfo = Hotel::getRoomDetailed($room,$halfmember,$starttime,$endtime,$num,0,$creditstatus);
        $this->renderSuccess('明细计算结果',$disInfo);
    }

    /**
     * Comment: 创建订房订单
     * Author: wlf
     * Date: 2022/09/02 10:12
     */
    public function createOrder(){
        global $_W, $_GPC;
        $id = $_GPC['id']; //房间id
        $starttime = $_GPC['starttime']; //预约开始时间
        $endtime = $_GPC['endtime']; //预约结束时间
        $num = $_GPC['num'];  //房间数量
        $creditstatus = $_GPC['creditstatus']; //积分抵扣
        $thname = trim($_GPC['thname']);  //住客姓名
        $thmobile = trim($_GPC['thmobile']);  //联系电话
        $remark = trim($_GPC['remark']);  //到店时间
        $redpackid = intval($_GPC['redpackid']); //使用的红包
        $mobile = $_GPC['mobile']; //绑定手机

        if(empty($id) || empty($starttime) || empty($endtime) || empty($num)){
            $this->renderError('参数错误，请刷新重试');
        }

        $settings = Setting::wlsetting_read('orderset'); //获取设置参数
        if (empty($settings['cancel'])) {
            $settings['cancel'] = 10;
        }
        //手机号校验
        if(empty($thmobile)){
            $this->renderError('请填写住客手机号');
        }else{
            if(!preg_match("/^1[345789]\d{9}$/",$thmobile)){
                $this->renderError('住客手机号格式有误，请检查');
            }
        }
        if(empty($mobile)){
            $this->renderError('请填写绑定手机号');
        }else{
            if(!preg_match("/^1[345789]\d{9}$/",$mobile)){
                $this->renderError('绑定手机号格式有误，请检查');
            }else{
                pdo_update('wlmerchant_member',array('mobile' => $mobile),array('id' => $_W['mid']));
            }
        }
        //会员信息
        $halfmember = WeliamWeChat::VipVerification($_W['mid']);

        $room = pdo_get('wlmerchant_hotel_room',array('id' => $id),array('paidid','roomtype','price','deposit','name','id','uniacid','aid','independent','settlementmoney','status','sid','vipstatus','viparray','level','creditmoney','fullreduceid'));
        if($room['status'] != 1){
            $this->renderError('房间已停止预定');
        }

        if ($room['vipstatus'] == 2) {
            $level = unserialize($room['level']);
            if (empty($halfmember)) {
                $this->renderError('该商品会员特供，请先成为会员');
            }else{
                //判断等级
                $flag = Halfcard::checklevel($_W['mid'] , $level);
                if (empty($flag)) {
                    $this->renderError('您所在的会员等级无权购买该商品');
                }
            }
        }

        //计算金额
        $momeyinfo =  Hotel::calculationMoney($room,$halfmember,$starttime,$endtime,$num,$creditstatus);
        if($momeyinfo['creditdiscount'] > 0){
            $jifentext = $_W['wlsetting']['trade']['credittext'] ? : '积分';
            $creditremark = '酒店预定[' . $room['name'] . ']抵扣'.$jifentext;
            Member::credit_update_credit1($_W['mid'],-$momeyinfo['credit'],$creditremark);
        }

        //创建订单
        $data = [
            'uniacid'         => $room['uniacid'] ,
            'mid'             => $_W['mid'] ,
            'sid'             => $room['sid'] ,
            'aid'             => $room['aid'] ,
            'fkid'            => $room['id'] ,
            'plugin'          => 'hotel' ,
            'payfor'          => 'hotelOrder' ,
            'orderno'         => createUniontid() ,
            'status'          => 0 ,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
            'createtime'      => TIMESTAMP ,
            'price'           => $momeyinfo['nowmoney'] > 0 ? $momeyinfo['nowmoney'] : 0,
            'num'             => $num ,
            'vipbuyflag'      => $momeyinfo['vipbuy'] ,
            'name'            => $thname ,
            'mobile'          => $thmobile ,
            'goodsprice'      => $momeyinfo['allgoodsmoney'] ,
            'buyremark'       => $remark ,
            'settlementmoney' => $momeyinfo['allsettmoney'] ,
            'usecredit'       => $momeyinfo['credit'] ,
            'cerditmoney'     => $momeyinfo['creditdiscount'] ,
            'canceltime'      => time() + $settings['cancel'] * 60,
            'redpackid'       => $redpackid,
            'redpackmoney'    => $momeyinfo['redpackmoney'],
            'fullreduceid'    => $room['fullreduceid'],
            'fullreducemoney' => $momeyinfo['fullreducemoney'],
            'vipdiscount'     => $momeyinfo['allvipdiscount'],
            'starttime'       => $starttime,
            'endtime'         => $endtime,
            'deposit'         => $momeyinfo['deposit'],
            'paidprid'        => $room['paidid'],
            'neworderflag'    => 1
        ];

        pdo_insert(PDO_NAME . 'order' , $data);
        $orderid = pdo_insertid();
        if($orderid > 0){
            if($redpackid){
                pdo_update('wlmerchant_redpack_records',array('status' => 1,'usetime' =>time(),'orderid' => $orderid,'plugin' => 'hotel'),array('id' => $redpackid));
            }
            $unidata['status']  = 1;
            $unidata['orderid'] = $orderid;
            $this->renderSuccess('购买成功' , $unidata);
        }else{
            $this->renderError('下单失败，请刷新重试');
        }

    }


    /**
     * Comment: 获取房间信息接口
     * Author: wlf
     * Date: 2022/09/07 09:55
     */
    public function getRoomApi(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        //房间信息
        if($id > 0){
            $room = pdo_get('wlmerchant_hotel_room',array('id' => $id),array('name','thumb','images','roomtype','price','size','roomnum','suite','bednum','breakfast','balcony','facilities','service','roomdesc','remind','deposit','status'));
            $room['thumb'] = tomedia($room['thumb']);
            $room['images'] = beautifyImgInfo($room['images']);
            $room['facilities'] = unserialize($room['facilities']);
            $room['service'] = unserialize($room['service']);
            $room['suite'] = unserialize($room['suite']);
        }else{
            $room = [];
        }
        //设施 服务信息
        $facilities = pdo_getall('wlmerchant_hotel_label',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'type' => 1),array('id','title'));
        $service = pdo_getall('wlmerchant_hotel_label',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'type' => 2),array('id','title'));
        $data = [
            'room' => $room,
            'service' => $service,
            'facilities' => $facilities
        ];
        $this->renderSuccess('房间信息' , $data);
    }

    /**
     * Comment: 编辑房间数据接口
     * Author: wlf
     * Date: 2022/09/07 10:46
     */
    public function editRoomApi(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        //获取参数
        $data = [
            'name' => trim($_GPC['name']),
            'thumb' => $_GPC['thumb'],
            'images' => explode(',',$_GPC['images']),
            'roomtype' => $_GPC['roomtype'],
            'price' => sprintf("%.2f",$_GPC['price']),
            'size'  => $_GPC['size'],
            'roomnum'  => $_GPC['roomnum'],
            'bednum' => $_GPC['bednum'],
            'breakfast' => $_GPC['breakfast'],
            'balcony' => $_GPC['balcony'],
            'facilities' => explode(',',$_GPC['facilities']),
            'service' => explode(',',$_GPC['service']),
            'remind' => $_GPC['remind'],
            'roomdesc' => $_GPC['roomdesc'],
            'status' => $_GPC['status'],
            'deposit' => sprintf("%.2f",$_GPC['deposit']),
        ];

        if(empty($data['name'])){
            $this->renderError('请输入房间名');
        }
        if(empty($data['thumb'])){
            $this->renderError('请选择房间缩略图');
        }

        if(!empty($data['images'])){
            $data['images'] = serialize($data['images']);
        }
        if(!empty($data['facilities'])){
            $data['facilities'] = serialize($data['facilities']);
        }
        if(!empty($data['service'])){
            $data['service'] = serialize($data['service']);
        }

        if($data['roomtype'] == 2){
            $suite = [
                'room' => $_GPC['room'],
                'office' => $_GPC['office'],
                'wei' => $_GPC['wei'],
                'kitchen' => $_GPC['kitchen']
            ];
            $data['suite'] = serialize($suite);
        }

        if($id > 0){
            $res = pdo_update(PDO_NAME."hotel_room",$data,array('id' => $id));
        }else{
            $data['uniacid'] = $_W['uniacid'];
            $data['aid'] = $_W['aid'];
            $data['sid'] = $_GPC['storeid'];
            if(empty($data['sid']) || $data['sid'] == 'undefined'){
                $this->renderError('商户信息有误，请刷新重试');
            }
            $data['createtime'] = time();
            $res = pdo_insert(PDO_NAME."hotel_room",$data);
        }
        if($res){
            if($data['roomtype'] == 1){
                $hoteprice = pdo_getcolumn(PDO_NAME.'hotel_room',array('sid'=>$_GPC['storeid'],'roomtype'=>1,'status'=> 1),'MIN(price)');
                pdo_update('wlmerchant_merchantdata',array('hotelprice' => $hoteprice),array('id' => $_GPC['storeid']));
            }
            $this->renderSuccess('房间发布成功');
        }else{
            $this->renderError('房间发布失败，请重试');
        }
    }

    /**
     * Comment: 房间上下架删除操作接口
     * Author: wlf
     * Date: 2022/09/07 11:31
     */
    public function changeRoomApi(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'];  //1上架 0下架 2删除
        if(empty($id)){
            $this->renderError('参数错误，请重试');
        }
        if($status == 2){
            $res = pdo_delete(PDO_NAME."hotel_room",array('id'=>$id));
        }else{
            $res = pdo_update(PDO_NAME."hotel_room",array('status' => $status),array('id' => $id));
        }
        if($res){
            $this->renderSuccess('房间操作成功');
        }else{
            $this->renderError('房间操作失败，请重试');
        }
    }

    /**
     * Comment: 酒店房间列表
     * Author: wlf
     * Date: 2022/09/07 11:39
     */
    public function storeRoomList(){
        global $_W, $_GPC;

        $page       = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $sid = $_GPC['storeid'];
        $page_start = $page * $page_index - $page_index;
        $search     = trim($_GPC['search']); //搜索

        //生成查询条件
        $where = " WHERE  uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND sid = {$sid}";
        if(!empty($search)){
            $where .= " AND  ( name LIKE '%{$search}%' OR `roomdesc` LIKE '%{$search}%' OR `remind` LIKE '%{$search}%' )";
        }

        //sql语句生成
        $order     = " ORDER BY sort DESC,id DESC ";
        $limit     = " LIMIT {$page_start},{$page_index} ";
        $field     = "id,thumb,name,roomtype,price,status,size,suite,bednum,breakfast,balcony,deposit";
        $sql       = "SELECT {$field} FROM ".tablename(PDO_NAME."hotel_room");
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        if(!empty($list)){
            foreach ($list as &$li){
                $li['thumb'] = tomedia($li['thumb']);
                if($li['roomtype'] == 2){
                    $li['suite'] = unserialize($li['suite']);
                }
            }
        }else{
            $list = [];
        }
        $data['list'] = $list;
        //总页数
        if($page == 1){
            $total  = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename(PDO_NAME . "hotel_room") . $where);
            $data['total'] = ceil($total / $page_index);
        }

        $this->renderSuccess('房间列表',$data);
    }



}