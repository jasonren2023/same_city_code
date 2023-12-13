<?php
defined('IN_IA') or exit('Access Denied');

class PayResult {
    /**
     * Comment: 支付回调公共处理
     * Author: zzw
     * Date: 2019/9/27 15:23
     * @param $params
     */
    public static function main($params){
        global $_W;
        $log           = pdo_get(PDO_NAME . 'paylogvfour' , array( 'tid' => $params['tid']));
        $_W['uniacid'] = $params['uniacid'] ? $params['uniacid'] : $log['uniacid'];
        $_W['acid']    = pdo_getcolumn('account_wechats' , array( 'uniacid' => $_W['uniacid'] ) , 'acid');
        $_W['source']  = $log['source'];
        $member        = pdo_get(PDO_NAME."member",['id'=>$log['mid']],['openid','mobile','realname','nickname']);
        $className     = $log['plugin'];
        $ret = [
            'weid'       => $log['uniacid'] ,
            'uniacid'    => $log['uniacid'] ,
            'result'     => 'success' ,
            'type'       => $params['type'] ,
            'tid'        => $log['tid'] ,
            'uniontid'   => $log['uniacid'] ,
            'user'       => $member['openid'] ,
            'fee'        => $log['fee'] ,
            'tag'        => $log['tag'] ,
            'is_usecard' => $log['is_usecard'] ,
            'card_type'  => $log['card_type'] ,
            'card_fee'   => $log['card_fee'] ,
            'card_id'    => $log['card_id'] ,
            'blendcredit'=> $log['blendcredit']
        ];
        Util::wl_log('rush_notify2' , PATH_DATA . "rush/data/" , $ret); //写入异步日志记录
        //混合支付扣除余额
        if($ret['blendcredit'] > 0){
            Member::credit_update_credit2($log['mid'],-$ret['blendcredit'],'混合支付['.$log['tid'].']订单支付余额');
        }
        //当订单中存在开卡信息时的操作
        $tid = $params['tid'];
        #1、获取订单信息
        if ($log['plugin'] == 'Rush') {
            $orderInfo = pdo_get(PDO_NAME . "rush_order" , array( 'orderno' => $tid ) , array( 'mid','sid','orderno','uniacid','aid','id','vip_card_id' ));
        }else if($log['payfor'] == 'Halfcard'){
            $orderInfo = pdo_get(PDO_NAME . "halfcard_record" , array( 'orderno' => $tid ) , array( 'mid','orderno','uniacid','aid'));
        } else {
            $orderInfo = pdo_get(PDO_NAME . "order" , array( 'orderno' => $tid ) , array( 'mid','sid','orderno','uniacid','aid', 'id','vip_card_id'));
        }
        $_W['aid']  = $orderInfo['aid'];
        if(empty($member['aid'])){  //给会员添加aid 修改用户所属代理
            pdo_update(PDO_NAME."member",array('aid' => $orderInfo['aid']),array('id' => $log['mid']));
        }
        if ($orderInfo['vip_card_id'] > 0 && !empty($orderInfo['vip_card_id'])) {
            $halftype = pdo_get(PDO_NAME . 'halfcard_type' , array( 'id' => $orderInfo['vip_card_id'] ));
            #2、获取用户信息
            $userInfo = pdo_get(PDO_NAME . "member" , array( 'id' => $orderInfo['mid'] ) , array( 'nickname' , 'mobile' ));
            $cardid   = $orderInfo['vip_card_id'];
            $username = $userInfo['nickname'];
            $mobile   = $userInfo['mobile'];
            #3、到期时间计算
            if ($cardid) {
                $mdata       = array( 'uniacid' => $_W['uniacid'] , 'mid' => $orderInfo['mid'] , 'id' => $cardid );
                $vipInfo     = Util::getSingelData('*' , PDO_NAME . "halfcardmember" , $mdata);
                $lastviptime = $vipInfo['expiretime'];
                if ($lastviptime && $lastviptime > time()) {
                    $limittime = $lastviptime + $halftype['days'] * 24 * 60 * 60;
                } else {
                    $limittime = time() + $halftype['days'] * 24 * 60 * 60;
                }
            } else {
                $limittime = time() + $halftype['days'] * 24 * 60 * 60;
            }
            #4、开卡信息记录    支付方式：1=余额；2=微信；3=支付宝；4=货到付款
            $data            = array(
                'aid'           => $orderInfo['aid'] ,
                'uniacid'       => $_W['uniacid'] ,
                'mid'           => $orderInfo['mid'] ,
                'orderno'       => createUniontid() ,
                'status'        => 1 ,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
                'createtime'    => TIMESTAMP ,
                'price'         => $halftype['price'] ,
                'limittime'     => $limittime ,
                'typeid'        => $halftype['id'] ,
                'howlong'       => $halftype['days'] ,
                'todistributor' => $halftype['todistributor'] ,
                'cardid'        => $cardid ,
                'username'      => $username ,
                'mobile'        => $mobile
            );
            $data['paytype'] = $params['type'];
            $data['paytime'] = time();
            pdo_insert(PDO_NAME . 'halfcard_record' , $data);
            $recordid = pdo_insertid();
            //分销
            if (p('distribution') && empty($halftype['isdistri'])) {
                $disorderid = Distribution::disCore($orderInfo['mid'] , $data['price'] , $halftype['onedismoney'] , $halftype['twodismoney'] , $halftype['threedismoney'] , $recordid , 'halfcard' , 1);
                pdo_update(PDO_NAME . 'halfcard_record' , array( 'disorderid' => $disorderid ) , array( 'id' => $recordid ));
            }
            #5、成功开通会员卡
            $halfcarddata = array(
                'uniacid'    => $_W['uniacid'] ,
                'aid'        => $data['aid'] ,
                'mid'        => $data['mid'] ,
                'expiretime' => $data['limittime'] ,
                'username'   => $data['username'] ,
                'levelid'    => $halftype['levelid'] ,
                'createtime' => time()
            );
            pdo_insert(PDO_NAME . 'halfcardmember' , $halfcarddata);
        }

        if (p('distribution')) {
            $disset = Setting::wlsetting_read('distribution');
            if($disset['appdis'] == 5){ //累计金额成为分销商
                $isDis = pdo_fetchcolumn("SELECT id FROM " . tablename(PDO_NAME . "distributor")
                    . " WHERE mid = {$orderInfo['mid']} AND disflag IN (1,-1) AND uniacid = {$_W['uniacid']}  ");
                if(empty($isDis)){
                    $nowmoney = Distribution::getNowMoney($orderInfo['mid']);
                    if($log['payfor'] != 'recharge'){
                        $nowmoney = $nowmoney + $ret['fee'];
                    }
                    if($nowmoney > $disset['totallmoney']){
                        $distributor = pdo_get(PDO_NAME . 'distributor', ['mid' => $orderInfo['mid'], 'uniacid' => $_W['uniacid']]);//分销商信息表的信息
                        $head_id = $distributor['leadid'];
                        if($distributor){
                            $disres = pdo_update(PDO_NAME . 'distributor', array('disflag' => 1,'updatetime' => time(), 'lockflag' => 0), array('id' => $distributor['id']));
                        }else{
                            $dosdata = array(
                                'uniacid'    => $_W['uniacid'],
                                'aid'        => $_W['aid'],
                                'mid'        => $orderInfo['mid'],
                                'realname'   => $member['realname'] ? $member['realname'] : $member['nickname'],
                                'mobile'     => $member['mobile'],
                                'createtime' => time(),
                                'disflag'    => 1
                            );
                            $disres = pdo_insert(PDO_NAME . 'distributor', $dosdata);//储存分销信息
                            $disid = pdo_insertid();
                            pdo_update(PDO_NAME . 'member', ['distributorid' => $disid], ['id' => $orderInfo['mid']]);//修改用户表信息
                        }
                    }
                    if ($disres) {
                        $url = h5_url('pages/subPages/dealer/index/index');
                        Distribution::distriNotice($orderInfo['mid'], $url, 1);//发送模板消息
                        if ($head_id > 0) Distribution::distriNotice($head_id, '', 2, $disid);//发送模板消息
                    }
                }
            }
        }

        if ($params['type'] == 2 && $params['bank_type'] != 'OTHERS'){  //银行卡返现
            $set = Setting::wlsetting_read('payback');
            if($set['status'] > 0){
                $rate = pdo_getcolumn(PDO_NAME.'payback_bank',array('uniacid'=>$_W['uniacid'],'bank'=>$params['bank_type']),'rate');
                if($rate > 0){
                    $price = sprintf("%.2f",$ret['fee'] * $rate / 100);
                    if($price>0){
                        Payback::payCore(0,$orderInfo['mid'],-2,'sys',$price,$orderInfo['orderno'],$orderInfo['id'],$orderInfo['uniacid'],$orderInfo['aid'],0,$params['bank_type']);
                    }
                }
            }
         }
        //开卡操作的结束
        pdo_update(PDO_NAME . 'paylogvfour' , [ 'status' => 1 , 'type' => $params['type'] ,'transaction_id'=>$params['transaction_id'],'pay_order_no'=>$params['pay_order_no']]
            , ['tid' => $params['tid'] , 'uniacid' => $_W['uniacid'] ]);
        //惠花卡接口
        if(agent_p('hhkapi') && $orderInfo['sid'] > 0){
            Hhkapi::coreApi($orderInfo['mid'],$orderInfo['sid']);
        }
        $ret['from']  = 'notify';
        $functionName = 'pay' . $log['payfor'] . 'Notify';
        $className::$functionName($ret);
    }
}
