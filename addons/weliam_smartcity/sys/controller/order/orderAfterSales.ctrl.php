<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 售后信息处理
 * Author: zzw
 * Date: 2021/1/7 17:28
 * Class OrderAfterSales_WeliamController
 */
class OrderAfterSales_WeliamController {
    /**
     * Comment: 售后记录表
     * Author: wlf
     */
    public function afterlist(){
        global $_W,$_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where['uniacid'] = $_W['uniacid'];
        if(is_agent()) {
            $where['aid'] = $_W['aid'];
        }
        if(is_store()) {
            $where['sid'] = $_W['storeid'];
        }
        if(!empty($_GPC['plugin'])){
            $where['plugin'] = $_GPC['plugin'];
        }
        if(!empty($_GPC['status'])){
            $where['status'] = $_GPC['status'];
        }
        if(!empty($_GPC['keyword'])){
            $keyword = $_GPC['keyword'];
            if($_GPC['keywordtype'] == 1){
                $where['orderno@']= trim($keyword);
            }else if($_GPC['keywordtype'] == 2){
                $where['sid']= trim($keyword);
            }else if($_GPC['keywordtype'] == 3){
                $where['mid']= trim($keyword);
            }
        }
        //时间
        if (!empty($_GPC['time_limit']) && $_GPC['timetype'] ) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            if($_GPC['timetype'] == 1){
                $where['createtime>'] = $starttime;
                $where['createtime<'] = $endtime;
            }else{
                $where['dotime>'] = $starttime;
                $where['dotime<'] = $endtime;
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }


        $afterData = Util::getNumData("*", PDO_NAME . 'aftersale', $where, 'createtime desc', $pindex, $psize, 1);
        $afterlist = $afterData[0];
        $pager = $afterData[1];
        foreach ($afterlist as &$after){
            $member = pdo_get('wlmerchant_member',array('id' => $after['mid']),array('mobile','nickname','realname'));
            $store = '';
            if($after['plugin'] == 'rush'){
                $order = pdo_get('wlmerchant_rush_order',array('id' => $after['orderid']),array('sid','blendcredit','activityid','price','expressid','paytype','actualprice','num','optionid'));
                $goods = pdo_get('wlmerchant_rush_activity',array('id' => $order['activityid']),array('name','thumb'));
//                if($order['expressid']){
//                    $expressprice = pdo_getcolumn(PDO_NAME.'express',array('id'=>$order['expressid']),'expressprice');
//                    $rushprice = $order['price'] - $expressprice;
//                }else{
//                    $rushprice = $order['price'];
//                }
                $after['goodsprice'] = sprintf("%.2f",$order['price']/$order['num']);
                if($order['optionid']){
                    $after['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['optionid']),'title');
                }
                $after['orderprice'] = $order['actualprice'];
                $after['plugintext'] = '抢购';
                $after['plugincss'] = 'success';
                $after['goodsid'] = $order['activityid'];
                $after['ordertype'] = 'a';
                $after['detailurl'] = web_url('order/wlOrder/orderdetail',array('orderid'=>$after['orderid'],'type'=>1,'is_jurisdiction'=>1));
            }else if($after['plugin'] == 'groupon'){
                $order = pdo_get('wlmerchant_order',array('id' => $after['orderid']),array('sid','blendcredit','fkid','price','expressid','paytype','goodsprice','num','specid'));
                $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $order['fkid']),array('name','thumb'));
                $after['goodsprice'] = sprintf("%.2f",$order['goodsprice']/$order['num']);
                if($order['specid']){
                    $after['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['specid']),'title');
                }
                $after['orderprice'] = $order['price'];
                $after['plugintext'] = '团购';
                $after['plugincss'] = 'info';
                $after['goodsid'] = $order['fkid'];
                $after['ordertype'] = 'b';
                $after['detailurl'] = web_url('order/wlOrder/orderdetail',array('orderid'=>$after['orderid'],'type'=>10,'is_jurisdiction'=>1));
            }else if($after['plugin'] == 'activity'){
                $order = pdo_get('wlmerchant_order',array('id' => $after['orderid']),array('sid','blendcredit','fkid','price','expressid','paytype','goodsprice','num','specid'));
                $goods = pdo_get('wlmerchant_activitylist',array('id' => $order['fkid']),array('title','thumb'));
                $goods['name'] = $goods['title'];
                $after['goodsprice'] = sprintf("%.2f",$order['goodsprice']/$order['num']);
                if($order['specid']){
                    $after['optiontitle'] = pdo_getcolumn(PDO_NAME.'activity_spec',array('id'=>$order['specid']),'name');
                }
                $after['orderprice'] = $order['price'];
                $after['plugintext'] = '活动';
                $after['plugincss'] = 'info';
                $after['goodsid'] = $order['fkid'];
                $after['ordertype'] = 'b';
                $after['detailurl'] = web_url('order/wlOrder/orderdetail',array('orderid'=>$after['orderid'],'type'=>9,'is_jurisdiction'=>1));
            }else if($after['plugin'] == 'wlfightgroup'){
                $order = pdo_get('wlmerchant_order',array('id' => $after['orderid']),array('sid','blendcredit','fkid','price','expressid','paytype','goodsprice','num','specid'));
                $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $order['fkid']),array('name','logo'));
                $after['goodsprice'] = sprintf("%.2f",$order['goodsprice']/$order['num']);
                if($order['specid']){
                    $after['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['specid']),'title');
                }
                $after['orderprice'] = $order['price'];
                $after['plugintext'] = '拼团';
                $after['plugincss'] = 'warning';
                $after['goodsid'] = $order['fkid'];
                $after['ordertype'] = 'b';
                $after['detailurl'] = web_url('order/wlOrder/orderdetail',array('orderid'=>$after['orderid'],'type'=>2,'is_jurisdiction'=>1));
            }else if($after['plugin'] == 'coupon'){
                $order = pdo_get('wlmerchant_order',array('id' => $after['orderid']),array('sid','blendcredit','fkid','price','expressid','paytype','goodsprice','num','specid'));
                $goods = pdo_get('wlmerchant_couponlist',array('id' => $order['fkid']),array('title','logo'));
                $after['goodsprice'] = sprintf("%.2f",$order['goodsprice']/$order['num']);
                $after['orderprice'] = $order['price'];
                $after['plugintext'] = '卡券';
                $after['plugincss'] = 'danger';
                $after['goodsid'] = $order['fkid'];
                $after['ordertype'] = 'b';
                $after['detailurl'] = web_url('order/wlOrder/orderdetail',array('orderid'=>$after['orderid'],'type'=>3,'is_jurisdiction'=>1));
            }else if($after['plugin'] == 'bargain'){
                $order = pdo_get('wlmerchant_order',array('id' => $after['orderid']),array('sid','blendcredit','fkid','price','expressid','paytype','goodsprice','num','specid'));
                $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $order['fkid']),array('name','thumb'));
                $after['goodsprice'] = sprintf("%.2f",$order['goodsprice']/$order['num']);
                $after['orderprice'] = $order['price'];
                $after['plugintext'] = '砍价';
                $after['plugincss'] = 'primary';
                $after['goodsid'] = $order['fkid'];
                $after['ordertype'] = 'b';
                $after['detailurl'] = web_url('order/wlOrder/orderdetail',array('orderid'=>$after['orderid'],'type'=>12,'is_jurisdiction'=>1));
            }else if($after['plugin'] == 'citydelivery'){
                $order = pdo_get('wlmerchant_order',array('id' => $after['orderid']),array('id','blendcredit','sid','fkid','price','expressid','paytype','goodsprice','num','specid'));
                $order['num'] = 1;
                $store = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('storename','logo','id'));
                $goods['name'] = '['.$store['storename'].']配送商品';
                $goods['thumb'] = tomedia($store['logo']);
                $after['goodsprice'] = sprintf("%.2f",$order['goodsprice']);
                $after['orderprice'] = $order['price'];
                $after['plugintext'] = '同城配送';
                $after['plugincss'] = 'primary';
                $after['ordertype'] = 'b';
                $after['detailurl'] = web_url('order/wlOrder/orderdetail',array('orderid'=>$after['orderid'],'type'=>14,'is_jurisdiction'=>1));
            }else if($after['plugin'] == 'housekeep'){
                $order = pdo_get('wlmerchant_order',array('id' => $after['orderid']),array('id','blendcredit','sid','fkid','price','expressid','paytype','goodsprice','num','specid'));
                $order['num'] = 1;
                if($order['specid'] == 1){
                    $store = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('storename','logo','id'));
                }else{
                    $store = pdo_get('wlmerchant_housekeep_artificer',array('id' => $order['sid']),array('name','mid','id'));
                    $store['storename'] = $store['name'];
                    $store['logo'] =  pdo_getcolumn(PDO_NAME.'member',array('uniacid'=>$store['mid']),'avatar');
                }
                $service = pdo_get('wlmerchant_housekeep_service',array('id' => $order['fkid']),array('title','thumb'));
                $goods['name'] = $service['title'];
                $goods['thumb'] = tomedia($service['thumb']);
                $after['goodsprice'] = sprintf("%.2f",$order['price']);
                $after['orderprice'] = $order['price'];
                $after['plugintext'] = '家政服务';
                $after['plugincss'] = 'primary';
                $after['ordertype'] = 'b';
                //$after['detailurl'] = web_url('order/wlOrder/orderdetail',array('orderid'=>$after['orderid'],'type'=>14,'is_jurisdiction'=>1));
            }else if($after['plugin'] == 'hotel'){
                $order = pdo_get('wlmerchant_order',array('id' => $after['orderid']),array('id','blendcredit','sid','fkid','price','expressid','paytype','goodsprice','num','specid'));
                $order['num'] = 1;

                $store = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('storename','logo','id'));

                $room = pdo_get('wlmerchant_hotel_room',array('id' => $order['fkid']),array('name','thumb'));
                $goods['name'] = $room['name'];
                $goods['thumb'] = tomedia($room['thumb']);
                $after['goodsprice'] = sprintf("%.2f",$order['price']);
                $after['orderprice'] = $order['price'];
                $after['plugintext'] = '酒店预约';
                $after['plugincss'] = 'primary';
                $after['ordertype'] = 'b';
                $after['detailurl'] = web_url('order/wlOrder/orderdetail',array('orderid'=>$after['orderid'],'type'=>17));
            }
            if(empty($store)){
                $store = pdo_get('wlmerchant_merchantdata',array('id' => $after['sid']),array('storename'));
            }
            $after['paytype'] = $order['paytype'];
            $after['nickname'] = $member['nickname'];
            $after['mobile'] = $member['mobile'];
            $after['realname'] = $member['realname'];
            $after['goodsname'] = $goods['name']?$goods['name']:$goods['title'];
            $after['goodsimg'] = $goods['thumb']?$goods['thumb']:$goods['logo'];
            $after['storename'] = $store['storename'];
            $after['num'] = $order['num'];
            //混合支付
            if($order['blendcredit'] > 0){
                $after['paytype'] = 7;
            }

        }

        include  wl_template('order/afterlist');
    }
    /**
     * Comment: 售后申请驳回
     * Author: wlf
     */
    public function rejectafter(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        if($_W['ispost']) {
            $after = pdo_get('wlmerchant_aftersale' , ['id' => $id] , ['checkcodes','mid']);
            $checkcodes = unserialize($after['checkcodes']);
            foreach ($checkcodes as $code){
                pdo_update('wlmerchant_smallorder',array('status' => 1),array('checkcode' => $code));
            }
            $data = array(
                'reply' => $_GPC['reply'],
                'status' => 3,
                'dotime' => time()
            );
            $journal = array(
                'time' => time(),
                'title' => '申请被驳回',
                'detail' => '驳回原因:'.$data['reply'],
            );
            $journals = Order::addjournal($journal,$id);
            $data['journal'] = $journals;
            $res = pdo_update('wlmerchant_aftersale',$data,array('id' => $id));
            if($res){
                //发送模板消息 通知用户
                $modelData = [
                    'first'   => '' ,
                    'type'    => '售后结果通知' ,//业务类型
                    'content' => $journal['detail'] ,//业务内容
                    'status'  => '驳回' ,//处理结果
                    'time'    => date("Y-m-d H:i:s" , $journal['time']) ,//操作时间$store['createtime']
                    'remark'  => ''
                ];
                TempModel::sendInit('service' , $after['mid'] , $modelData , 1);

                show_json(1,'驳回成功');
            }else {
                show_json(0,'驳回失败,请重试');
            }
        }


        include  wl_template('order/rejectafter');
    }


}