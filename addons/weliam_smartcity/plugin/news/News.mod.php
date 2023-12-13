<?php
defined('IN_IA') or exit('Access Denied');

class News{
    //商品发货提醒
    static function sendremind($orderid, $plugin) {
        global $_W;
        if ($plugin == 'b') {
            //抢购
            $order = pdo_get(PDO_NAME . 'rush_order', array('id' => $orderid));
            $type = 'rush';
            $goodsname = pdo_getcolumn(PDO_NAME.'rush_activity',array('id'=>$order['activityid']),'name');
        } else {
            //团购  拼团  砍价  积分兑换
            $order = pdo_get(PDO_NAME . 'order', array('id' => $orderid));
            $type = $order['plugin'];
            if($type == 'groupon'){
                $goodsname = pdo_getcolumn(PDO_NAME.'groupon_activity',array('id'=>$order['fkid']),'name');
            }else if($type == 'wlfightgroup'){
                $goodsname = pdo_getcolumn(PDO_NAME.'fightgroup_goods',array('id'=>$order['fkid']),'name');
            }else if($type == 'bargain'){
                $goodsname = pdo_getcolumn(PDO_NAME.'bargain_activity',array('id'=>$order['fkid']),'name');
            }else if($type == 'consumption'){
                $goodsname = pdo_getcolumn(PDO_NAME.'consumption_goods',array('id'=>$order['fkid']),'title');
            }
        }
        $express = pdo_get('wlmerchant_express', array('id' => $order['expressid']));
        $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['orderid'=>$orderid,'plugin'=>$type]);
        $modelData = [
            'first'             => '您的订单商品已发货，请注意查收!' ,
            'order_no'          => $order['orderno'] ,//订单编号
            'express_name'      => $express['expressname'],//物流公司
            'express_no'        => $express['expresssn'],//物流单号
            'goods_name'        => $goodsname,//商品信息
            'consignee'         => $express['name'] ,//收货人
            'receiving_address' => $express['address']  ,//收货地址
            'remark'            => '点击查看物流详细信息!'
        ];
        TempModel::sendInit('send',$order['mid'],$modelData,$_W['source'],$url);
    }

    //组团成功提醒
    static function groupresult($groupid){
        global $_W;
        $group = pdo_get('wlmerchant_fightgroup_group',array('id' => $groupid));
        $goodname = pdo_getcolumn(PDO_NAME.'fightgroup_goods',array('id'=>$group['goodsid']),'name');
        if($group['status'] == 2){
            $first = '恭喜您，您参加的拼团已组团成功';
            $result = '组团成功';
            $time = '成团时间：'.date('Y-m-d H:i:s',time());
        }else{
            $first = '很遗憾，您参加的拼团已组团失败';
            $result = '组团失败';
            $time = '失败时间：'.date('Y-m-d H:i:s',time());
        }
        $data = array(
            'first'       => $first,
            'result'      => $result,//组团结果
            'goods_name'  => $goodname,//商品名称
            'detail'      => $time,//详细信息
            'user_number' => sprintf("%.0f",$group['neednum'] - $group['lacknum']),//组团人数
            'remark'  => '点击查看组团详情'
        );
        $url = h5_url('pages/subPages/group/assemble/assemble',array('id'=>$group['goodsid'],'group_id'=>$group['id']));
        $orders = pdo_getall('wlmerchant_order' , ['status' => [1 , 8] , 'fightgroupid' => $groupid] , ['mid','price']);
        //获取拼团成员昵称
        $mids = array_column($orders,'mid');
        $names = pdo_getall(PDO_NAME."member",['id'=>$mids],['nickname']);
        $data['nickname_string'] = implode(array_column($names,'nickname'),',');
        foreach ($orders as $order){
            $data['money'] = $order['price'];
            TempModel::sendInit('fight',$order['mid'],$data,$_W['source'],$url);
        }
    }
    //支付成功通知
    static function paySuccess($orderid,$plugin,$noticeagent = 0){
        global $_W;
        if ($plugin == 'rush') {
            //抢购
            $order = pdo_get(PDO_NAME . 'rush_order', array('id' => $orderid));
            $goodsname = pdo_getcolumn(PDO_NAME.'rush_activity',array('id'=>$order['activityid']),'name');
            $pluginname = '抢购';
            $order['price'] = $order['actualprice'];
            $remark = $order['remark'] ? : '';//买家备注
        }else if($plugin == 'mobilerecharge'){
            //话费充值
            $order = pdo_get(PDO_NAME . 'mrecharge_order', array('id' => $orderid));
            $goodsname = $order['money'].'元';
            $pluginname = '话费充值';
            $order['price'] = $order['price'];
        } else {
            //团购  拼团  砍价  积分兑换
            $order = pdo_get(PDO_NAME . 'order', array('id' => $orderid));
            if($plugin == 'groupon'){
                $goodsname = pdo_getcolumn(PDO_NAME.'groupon_activity',array('id'=>$order['fkid']),'name');
                $pluginname = '团购';
            }else if($plugin == 'wlfightgroup'){
                $goodsname = pdo_getcolumn(PDO_NAME.'fightgroup_goods',array('id'=>$order['fkid']),'name');
                $pluginname = '拼团';
                if($order['fightstatus'] == 1){
                    $group = pdo_get(PDO_NAME . 'fightgroup_group', array('id' => $order['fightgroupid']),array('status'));
                    if($group['status'] == 1){
                        $url = h5_url('pages/subPages/group/assemble/assemble',['group_id'=>$order['fightgroupid'],'id'=>$order['fkid']]);
                    }
                }
            }else if($plugin == 'bargain'){
                $goodsname = pdo_getcolumn(PDO_NAME.'bargain_activity',array('id'=>$order['fkid']),'name');
                $pluginname = '砍价';
            }else if($plugin == 'consumption'){
                $goodsname = pdo_getcolumn(PDO_NAME.'consumption_goods',array('id'=>$order['fkid']),'title');
                $pluginname = $_W['wlsetting']['trade']['credittext'].'兑换';
            }else if($plugin == 'citydelivery'){
                $pluginname = '同城配送';
                $smallorders = pdo_fetchall("SELECT gid,money,num,specid FROM ".tablename('wlmerchant_delivery_order')."WHERE tid = {$order['orderno']} ORDER BY price DESC");
                $cityremark = '';
                foreach ($smallorders  as $ke => &$orr){
                    $goods = pdo_get('wlmerchant_delivery_activity',array('id' => $orr['gid']),array('name','thumb'));
                    $orr['name'] = $goods['name'];
                    if($orr['specid']>0){
                        $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                        $orr['name'] .= '/'.$specname;
                    }
                    $cityremark .= '['.$orr['name'].'X'.$orr['num'].']';
                    if($ke == 0){
                        $goodsname = $orr['name'];
                    }
                }
                if(count($smallorders)>1){
                    $goodsname .= ' 等商品';
                }
                $url = h5_url('pages/subPages/orderList/orderTakeout/orderTakeout',['orderid'=>$orderid,'aid'=>$order['aid'],'plugin'=>$plugin]);
            }else if($plugin == 'coupon'){
                $goodsname = pdo_getcolumn('wlmerchant_couponlist' , ['id' => $order['fkid']],'title');
                $pluginname = '卡券';
                $url = h5_url('pages/subPages/coupon/couponDetails/couponDetails',['id'=>$order['recordid'],'order_id'=>$order['id']]);
            }else if($plugin == 'yellowpage'){
                if($order['fightstatus'] == 1){
                    $goodsname = '114页面认领';
                }else if($order['fightstatus'] == 2){
                    $goodsname = '114页面查看';
                }else if($order['fightstatus'] == 3){
                    $goodsname = '114页面入驻';
                }
                $pluginname = '黄页114';
                //$url = h5_url('pages/subPages/coupon/couponDetails/couponDetails',['id'=>$order['recordid'],'order_id'=>$order['id']]);
            }else if($plugin == 'activity'){
                $goodsname = pdo_getcolumn(PDO_NAME.'activitylist',array('id'=>$order['fkid']),'title');
                if($order['specid']>0){
                    $specname = pdo_getcolumn(PDO_NAME.'activity_spec',array('id'=>$order['specid']),'name');
                    $goodsname .= '/'.$specname;
                }
                $pluginname = '活动报名';
            }else if($plugin == 'hotel'){
                $room = pdo_get('wlmerchant_hotel_room',array('id' => $order['fkid']),array('name','id','sid'));
                $roomname = $room['name'];
                $storename = pdo_getcolumn(PDO_NAME.'wlmerchant_merchantdata',array('id'=>$room['sid']),'storename');
                $goodsname = $storename.':'.$roomname;
                $pluginname = '酒店预订';
            }
            $remark = $order['buyremark'] ? : '';//买家备注
        }
        //给用户发送模板信息
        $payinfo = array(
            'first'      => '您的'.$pluginname.'订单已经成功付款' ,
            'order_no'   => $order['orderno'],//订单编号
            'time'       => date('Y-m-d H:i:s', time()),//支付时间
            'money'      => $order['price'],//支付金额
            'goods_name' => $goodsname,//商品名称
            'remark'     => '点击可查看订单详情，如有疑问请联系客服'
        );
        if($order['paylogid']){
            $source = pdo_getcolumn(PDO_NAME.'paylogvfour',array('plid'=>$order['paylogid']),'source');
        }else{
            $source = pdo_getcolumn(PDO_NAME.'paylogvfour',array('tid'=>$order['orderno']),'source');
        }
        $source = $source ? : $_W['source'];
        if(empty($url)){
            $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['orderid'=>$orderid,'aid'=>$order['aid'],'plugin'=>$plugin]);
        }
        $url = str_replace('payment/','',$url);
        if($plugin == 'mobilerecharge'){
            $url = '';
        }
        TempModel::sendInit('pay',$order['mid'],$payinfo,$source,$url);
        //配置模板信息
        $userInfo = pdo_get(PDO_NAME . "member" , array( 'id' => $order['mid'] ) , array( 'nickname' , 'mobile' ));
        //给商户发送用户购买商品的模板消息
        if($order['sid']){
            //获取当前店铺的超级管理员和管理员
            $list = pdo_fetchall("SELECT id,mid FROM ".tablename(PDO_NAME."merchantuser")." WHERE storeid = {$order['sid']} AND ismain IN (1,3) AND enabled = 1 ");
            if(is_array($list) && count($list) > 0){
                //详细内容 判断是否存在买家备注
                if(!empty($remark)){
                    $content =  '订单金额:' . $order['price'] . '元,购买数量:' . $order['num'] . ',请商家注意备货' . "。买家备注:" . $remark;
                }else{
                    $content =  '订单金额:' . $order['price'] . '元,购买数量:' . $order['num'] . ',请商家注意备货';
                }

                $modelData = [
                    'first'   => '您好,用户[' . $userInfo['nickname'] . ']购买的[' . $goodsname . ']已支付',
                    'type'    => $pluginname . '商品订单支付',//业务类型
                    'content' =>  $content,//业务内容
                    'status'  => '已付款' ,//处理结果
                    'time'    => date("Y-m-d H:i:s",$order['createtime']) ,//操作时间
                    'remark'  => $cityremark?$cityremark:''
                ];
                $url = h5_url('pages/subPages/merchant/merchantOrderList/merchantOrderList',array('aid'=>$order['aid'],'storeid'=>$order['sid']));
                $url = str_replace('payment/','',$url);
                foreach($list as $index => $item){
                    TempModel::sendInit('service',$item['mid'],$modelData,$source,$url);
                }
            }
        }
        if(empty($noticeagent)){
            //给管理员发送消息
            if($order['sid'] > 0){
                $storename = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$order['sid']),'storename');
                $content = '订单所属商户:['.$storename.']';
                $remark = '请联系商户处理订单';
            }else{
                $content = '代理订单';
                $remark = '请及时处理订单';
            }
            $first = '您好,用户['.$userInfo['nickname']. ']购买的['.$goodsname.']已支付';
            $type = $pluginname . '商品订单支付';
            $status = '已支付';
            News::noticeAgent('pay',$order['aid'],$first,$type,$content,$status,$remark,time());
        }
    }

    //核销成功通知
    static function writeOffSuccess($mid,$goodname,$num = 1,$orderNo = ''){
        global $_W;
        $info = array(
            'first'      => '您好，您的商品已经成功核销' ,
            'goods_name' => $goodname,//商品名称
            'goods_num'  => $num,//商品数量
            'time'       => date('Y-m-d H:i:s',time()),//核销时间
            'order_no'   => $orderNo,//订单编号
            'remark'     => '如有疑问请联系客服'
        );
        TempModel::sendInit('write_off',$mid,$info,$_W['source']);
    }

    //订单退款通知
    static function refundNotice($orderid,$plugin,$money,$reason){
        global $_W;
        if ($plugin == 'rush') {
            //抢购
            $order = pdo_get(PDO_NAME . 'rush_order', array('id' => $orderid));
            $goodsname = pdo_getcolumn(PDO_NAME.'rush_activity',array('id'=>$order['activityid']),'name');
            $pluginname = '抢购';
            $order['price'] = $order['actualprice'];
        }else if($plugin == 'mobilerecharge'){
            //话费充值
            $order = pdo_get(PDO_NAME . 'mrecharge_order', array('id' => $orderid));
            $goodsname = '充值'.$order['money'].'元';
            $pluginname = '话费充值';
            $order['price'] = $order['price'];
        } else {
            //团购  拼团  砍价  积分兑换
            $order = pdo_get(PDO_NAME . 'order', array('id' => $orderid));
            if($plugin == 'groupon'){
                $goodsname = pdo_getcolumn(PDO_NAME.'groupon_activity',array('id'=>$order['fkid']),'name');
                $pluginname = '团购';
            }else if($plugin == 'wlfightgroup'){
                $goodsname = pdo_getcolumn(PDO_NAME.'fightgroup_goods',array('id'=>$order['fkid']),'name');
                $pluginname = '拼团';
            }else if($plugin == 'bargain'){
                $goodsname = pdo_getcolumn(PDO_NAME.'bargain_activity',array('id'=>$order['fkid']),'name');
                $pluginname = '砍价';
            }else if($plugin == 'wlcoupon'){
                $goodsname = pdo_getcolumn(PDO_NAME.'couponlist',array('id'=>$order['fkid']),'title');
                $pluginname = '卡券';
            }else if($plugin == 'consumption'){
                $goodsname = pdo_getcolumn(PDO_NAME.'consumption_goods',array('id'=>$order['fkid']),'title');
                $pluginname = $_W['wlsetting']['trade']['credittext'].'兑换';
            }else if($plugin == 'activity'){
                $goodsname = pdo_getcolumn(PDO_NAME.'activitylist',array('id'=>$order['fkid']),'title');
                $pluginname = '活动报名';
            }else if($plugin == 'housekeep'){
                $goodsname = pdo_getcolumn(PDO_NAME.'housekeep_service',array('id'=>$order['fkid']),'title');
                $pluginname = '家政服务';
            }else if($plugin == 'hotel'){
                $goodsname = pdo_getcolumn(PDO_NAME.'hotel_room',array('id'=>$order['fkid']),'name');
                $pluginname = '酒店预约';
            }
        }
        if($money < 0.01){$money = $order['price'];}
        $refundinfo = array(
            'first'       => '您好，您的'.$pluginname.'订单已经退款' ,
            'money'       => '￥' .$money,//退款金额
            'goods_name'  => $goodsname ,//商品名称
            'order_no'    => $order['orderno'],//订单编号
            'total_money' =>  '￥'.$money,//订单总金额
            'remark'      => $reason.'如有疑问请联系客服'
        );
        TempModel::sendInit('refund',$order['mid'],$refundinfo,$_W['source']);
    }

    //业务处理通知
    static function jobNotice($mid,$first,$type,$content,$status,$remark,$time,$url='',$sourceDefault = 1){
        global $_W;
        $source = $_W['source'] ? $_W['source'] : $sourceDefault;
        $modelData = [
            'first'   => $first,
            'type'    => $type,//业务类型
            'content' => $content,//业务内容
            'status'  => $status,//处理结果
            'time'    => date("Y-m-d H:i:s",$time) ,//操作时间
            'remark'  => $remark
        ];
        $res = TempModel::sendInit('service',$mid,$modelData,$source,$url);
        file_put_contents(PATH_DATA . "new_error.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
    }

    //添加系统通知
    static function addSysNotice($uniacid,$type,$sid=0,$mid=0,$objid,$status=0){
        $data = array(
            'uniacid' => $uniacid,
            'type'    => $type,
            'sid'     => $sid,
            'mid'     => $mid,
            'objid'   => $objid,
            'status'  => $status,
            'isread'  => 0,
            'createtime' => time()
        );
        pdo_insert(PDO_NAME .'systemnotice',$data);
    }

    /**
     * Comment: 给代理商管理员发送模板消息
     * Date: 2019/11/29 10:30
     * @param string $authority     storesettled=商户入驻审核;storegood=商品动态审核;storecomment=用户评论审核;refundorder=用户申请退款;pocketfabu=掌上信息审核;recruit_examine=招聘发布审核通知;
     * @param int    $aid           代理id
     * @param string $first         消息头部
     * @param string $type          业务类型
     * @param string $content       业务内容
     * @param string $status        处理结果
     * @param string $remark        备注信息
     * @param int    $time          操作时间(时间戳)
     * @param string $url           跳转地址
     */
    static function noticeAgent($authority,$aid,$first,$type,$content,$status,$remark,$time,$url=''){
        global $_W;
        $aid = $aid ? : $_W['aid'];
        if(empty($aid) || $aid == -1){
            $aid = 0;
        }
        //查询管理员
        $openids = pdo_getall('wlmerchant_agentadmin', array('uniacid' => $_W['uniacid'],'aid' => $aid, 'notice' => 1), array('mid','noticeauthority'));
        if(!empty($openids)){
            foreach ($openids as $user){
                $noticeauthority = unserialize($user['noticeauthority']);
				if(empty($noticeauthority)) $noticeauthority = [];
                if(in_array($authority,$noticeauthority) || empty($noticeauthority)) self::jobNotice($user['mid'],$first,$type,$content,$status,$remark,$time,$url);
            }
        }
    }
    /**
     * Comment: 商品审核结果模板消息发送
     * Author: zzw
     * Date: 2020/3/4 14:05
     * @param int    $id        商品id
     * @param string $type      商品类型(rush=抢购；groupon=团购；fight=拼团；bargain=砍价)
     * @param string $result
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function goodsToExamine($id,$type,$result = '通过',$remark = ''){
        global $_W;
        #1、获取商品信息
        switch ($type) {
            case 'rush':
                $goods = pdo_get(PDO_NAME . "rush_activity" , ['id' => $id] , ['sid' , 'name']);
                break;//抢购商品
            case  'groupon':
                $goods = pdo_get(PDO_NAME . "groupon_activity" , ['id' => $id] , ['sid' , 'name']);
                break;//团购商品
            case  'fight':
                $goods = pdo_fetch("select merchantid as sid,name from " . tablename(PDO_NAME . "fightgroup_goods") . " where id = {$id}");
                break;//拼团商品
            case  'bargain':
                $goods = pdo_get(PDO_NAME . "bargain_activity" , ['id' => $id] , ['sid' , 'name']);
                break;//砍价商品
            case  'citydelivery':
                $goods = pdo_get(PDO_NAME . "delivery_activity" , ['id' => $id] , ['sid' , 'name']);
                break;//配送商品
        }
        if(!$goods) return error(0,'商品不存在!');
        #2、获取店铺信息
        $storename = pdo_getcolumn(PDO_NAME."merchantdata",['id'=>$goods['sid']],'storename');
        #3、模板消息配置
        $modelData = [
            'first'   => '商品审核通知',
            'type'    => '商品审核通知',//业务类型
            'content' =>  "[{$storename}]的商品[{$goods['name']}]已审核",//业务内容
            'status'  => $result ,//处理结果
            'time'    => date("Y-m-d H:i:s",time()) ,//操作时间
            'remark'  => $remark
        ];
        #3、获取店铺的管理员列表
        $userList = pdo_getall(PDO_NAME."merchantuser",['storeid'=>$goods['sid']],['mid']);
        foreach($userList as $item){
            TempModel::sendInit('service',$item['mid'],$modelData,$_W['source']);
        }
    }
    /**
     * Comment: 给商户管理员发送模板消息
     * Author: zzw
     * Date: 2020/3/9 17:11
     * @param int    $sid       商户id
     * @param string $first     头部信息
     * @param string $type      类型
     * @param string $content   内容
     * @param string $status    结果
     * @param string $remark    备注信息
     * @param string $time      操作时间
     * @param string $url       跳转链接
     */
    public static function noticeShopAdmin($sid,$first,$type,$content,$status,$remark,$time,$url=''){
        #1、获取当前店铺下面的管理员信息
        $list = pdo_fetchall("SELECT mid FROM ".tablename(PDO_NAME."merchantuser")
                             ." WHERE storeid = {$sid} AND ismain IN (1,3) AND enabled = 1");
        if(is_array($list) && count($list) > 0){
            foreach($list as $key => $val){
                self::jobNotice($val['mid'],$first,$type,$content,$status,$remark,$time,$url);
            }
        }
    }






}