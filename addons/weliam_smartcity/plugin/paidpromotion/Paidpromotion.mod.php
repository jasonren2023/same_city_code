<?php
defined('IN_IA') or exit('Access Denied');

class Paidpromotion{

    static function getpaidpr($type,$id,$mid,$orderid,$paytype,$price,$num = 1,$timetype = 0){
        global $_W;
        //判断是否已赠送
        $flag = pdo_getcolumn(PDO_NAME.'paidrecord',array('type'=>$type,'orderid'=>$orderid),'id');
        if($flag > 0){
            return 0;
        }
        //判断是否存在mid
        if(!$mid){
            if ($type == 1) $mid = pdo_getcolumn(PDO_NAME.'rush_order',['id' => $orderid],'mid');
            else if ($type != 5) $mid = pdo_getcolumn(PDO_NAME.'order',['id' => $orderid],'mid');
        }
        $activity = pdo_get('wlmerchant_payactive',array('id' => $id));
        if($activity['timetype'] != $timetype && $timetype != 2){
            $activity['status'] = 0;
        }
        if($activity['status']>0){
            $_W['mid'] = $mid;
            //判断资格
            if($activity['userstatus']){  //判断会员资格
                $halfflag = Member::checkhalfmember();
                if(empty($halfflag)){
                    return 0;
                }
            }
            if($price < $activity['orderprice']){  //判断订单  订单价格
                return 0;
            }
            //判断支付方式
            if(!empty($activity['paytypearray'])){
                $paytypearray = unserialize($activity['paytypearray']);
                if(!in_array($paytype,$paytypearray)){
                    return 0;
                }
            }
            //开始计算礼物
            $nointegral = 0;
            if($_W['wlsetting']['creditset']['nointegral'] > 0){
                if($type == 1){
                    $dkmoney = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$orderid),'dkmoney');
                }else if($type != 5){
                    $dkmoney = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'cerditmoney');
                }
                if($dkmoney > 0){
                    $nointegral = 1;
                }
            } //不赠送积分
            if($activity['integralrate'] > 0 && empty($nointegral)){   //积分
                if($activity['integral'] < 0.01){
                    $activity['integral'] = 1;
                }
                if($activity['ratestatus'] > 0){
                    $integral = sprintf("%.2f",floor($price/$activity['integralrate'])*$activity['integral']);
                }else{
                    $integral = sprintf("%.2f",$price/$activity['integralrate']*$activity['integral']);
                }
            }
            if($activity['balancerate'] > 0){   //余额
                if($activity['balance'] < 0.01){
                    $activity['balance'] = 1;
                }
                if($activity['ratestatus'] > 0){
                    $balance = sprintf("%.2f",floor($price/$activity['balancerate'])*$activity['balance']);
                }else{
                    $balance = sprintf("%.2f",$price/$activity['balancerate']*$activity['balance']);
                }
            }

            if($activity['giftstatus'] == 1){  //赠券
                $couponid = $activity['giftcouponid'];
            }else if($activity['giftstatus'] == 2){  //赠码
                $codeid = pdo_getcolumn('wlmerchant_token',array('uniacid' => $_W['uniacid'],'remark' => $activity['codereamrk'],'status'=>0),'id');
                pdo_update('wlmerchant_token',array('status' => 2),array('id' => $codeid));
            }else if($activity['giftstatus'] == 3){  //赠送红包
                $redpackid = $activity['giftredpack'];
            }
            //创建记录
            $data = array(
                'uniacid'     => $_W['uniacid'],
                'aid'         => $_W['aid'],
                'activeid'    => $activity['id'],
                'integral'    => $integral,
                'balance'     => $balance,
                'couponid'    => $couponid,
                'getcouflag'  => 0,
                'codeid'  	  => $codeid,
                'paytype'     => $paytype,
                'price'       => $price,
                'createtime'  => time(),
                'img'         => $activity['img'],
                'type'        => $type,
                'orderid'     => $orderid,
                'redpackid'   => $redpackid
            );
            pdo_insert(PDO_NAME.'paidrecord',$data);
            $paidprid = pdo_insertid();
            //赠送积分
            if($paidprid && $integral>0){
                $integralChangeRes = Member::credit_update_credit1($mid, $integral, '支付有礼赠送积分');
                if(is_array($integralChangeRes)){
                    file_put_contents(PATH_DATA . "integralChangeError.log", var_export(['info'=>$integralChangeRes,'paidprid'=>$paidprid,'mid'=>$mid], true) . PHP_EOL, FILE_APPEND);
                }
            }
            if($paidprid && $balance>0){
                if($_W['aid'] > 0){
                    $agenttemark = '支付有利赠送余额';
                    $ares = Order::deductAgencyAmount($_W['aid'],$balance,$agenttemark);
                }else{
                    $ares = 1;
                }
                if($ares){
                    $balanceChangeRes = Member::credit_update_credit2($mid, $balance, '支付有礼赠送余额');
                    if(is_array($balanceChangeRes)){
                        file_put_contents(PATH_DATA . "integralChangeError.log", var_export(['info'=>$balanceChangeRes,'paidprid'=>$paidprid,'mid'=>$mid], true) . PHP_EOL, FILE_APPEND);
                    }
                }else{
                    pdo_update(PDO_NAME.'paidrecord',array('balance' => 0),array('id' => $paidprid));
                }
            }
            //自动发放卡券
            if($data['couponid'] && $activity['giftstatus'] == 1 && $activity['getstatus'] && $paidprid){
                $couponIdList = explode(',',$activity['giftcouponid']);
                #3、通过循环判断信息
                if(is_array($couponIdList)){
                    foreach ($couponIdList as $k => $v){
                        $acresult = '';//优惠券领取状态
                        $coupons = wlCoupon::getSingleCoupons($v, '*');
                        $num = wlCoupon::getCouponNum($v, 1);
                        //判断卡券是否能够被领取
                        if ($coupons['time_type'] == 1 && $coupons['endtime'] < time()) {
                            $acresult = '[失败]已停止发放';
                        }
                        if ($coupons['status'] == 0) {
                            $acresult = '[失败]已被禁用';
                        }
                        if ($coupons['status'] == 3) {
                            $acresult = '[失败]已失效';
                        }
                        if ($coupons['surplus'] > ($coupons['quantity'] - 1)) {
                            $acresult = '[失败]已被领光';
                        }
                        if ($num) {
                            if (($num > $coupons['get_limit'] || $num == $coupons['get_limit']) && $coupons['get_limit'] > 0) {
                                $acresult = '[失败]只能领取'.$coupons['get_limit'].'张';
                            }
                        }
                        //领取状态为空  无异常 开始正常的领取操作
                        if(empty($acresult)){
                            //用户领取卡券的操作
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
                                'mid' => $_W['mid'],
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
                                'buyremark'  => '支付有礼赠送卡券,支付有礼编号:'.$paidprid,
                                'paytime'    => time()
                            );
                            $orderid = wlCoupon::saveCouponOrder($orderdata);
                            Order::createSmallorder($orderid,4);
                            //生成卡券
                            $data = array(
                                'mid' => $_W['mid'],
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
                                wlCoupon::updateCoupons(array('surplus' => $newsurplus), array('id' => $v));
                                $url = h5_url('pages/subPages/coupon/couponDetails/couponDetails',['id'=>$couponUserId,'order_id'=>$orderid]);
                                $acresult = '[成功]领取成功';
                            }else{
                                $acresult = '[失败]领取失败';
                            }
                        }
                        //发送当前卡券领取结果的通知
                        $messagedata = array(
                            'first'   => '“'.$coupons['title'].'”领取结果通知',
                            'type'    => '支付有礼-卡券领取',//业务类型
                            'content' => '领取人:'.$_W['wlmember']['nickname'],//业务内容
                            'status'  => $acresult ,//处理结果
                            'time'    => date('Y-m-d H:i:s',time()),//操作时间
                            'remark'  => '点击查看我的卡券'
                        );
                        TempModel::sendInit('service',$_W['mid'],$messagedata,$_W['source'],$url);
                        $acresult = '';//清除领取状态
                    }
                }
                pdo_update(PDO_NAME.'paidrecord',array('getcouflag' => 1,'getcoutime'=>time()),array('id' => $paidprid));
            }
            //发放红包
            if($data['redpackid'] && $activity['giftstatus'] == 3 && $paidprid){
                $redList = explode(',',$activity['giftredpack']);
                foreach ($redList as $k => $v){
                    Redpack::pack_send($mid,$v,'send');
                }
            }
            return $paidprid;
        }else{
            return 0;
        }
    }

}
