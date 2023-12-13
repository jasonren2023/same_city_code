<?php
defined('IN_IA') or exit('Access Denied');

class wlPay extends WeModuleSite {

    static function finance($openid = '', $money = 0, $desc = '', $realname = '', $trade_no) {
        global $_W;
        $pay = new WeixinPay;
        $arr = $pay->finance($openid, $money, $desc, $realname, $trade_no);
        return $arr;
    }
    /**
     * Comment: 退款操作
     * Author: zzw
     * Date: 2019/9/29 10:55
     * @param     $id       int         订单ID
     * @param     $money    float       退款金额
     * @param     $remark   string      退款原因
     * @param     $plugin   string      模块信息
     * @param int $type     int         1微信端2web端3计划任务退款
     * @param     $blendcredit  float   退款到余额
     * @return mixed
     * @throws \Yansongda\Pay\Exceptions\GatewayException
     * @throws \Yansongda\Pay\Exceptions\InvalidArgumentException
     * @throws \Yansongda\Pay\Exceptions\InvalidConfigException
     * @throws \Yansongda\Pay\Exceptions\InvalidSignException
     */
    static function refundMoney ($id , $money , $remark , $plugin , $type = 3,$blendcredit = 0){
        global $_W;
        #1、初始申明
        $refund = false;//默认退款失败
        #2、基本订单信息获取
        switch ($plugin) {
            case 'rush' :
                $order = pdo_get(PDO_NAME.'rush_order' ,[ 'id' => $id] ,['paytype','orderno','aid','sid']);
                break;
            case 'attestation' :
                $order = pdo_get(PDO_NAME.'attestation_money' ,[ 'id' => $id] ,['paytype','orderno']);
                break;
            case 'mobilerecharge' :
                $order = pdo_get(PDO_NAME.'mrecharge_order' ,[ 'id' => $id] ,['paytype','orderno']);
                break;
            default :
                $order = pdo_get(PDO_NAME.'order' , [ 'id' => $id] ,['paytype','orderno','aid','sid','paylogid']);
                break;
        }
        #3、获取支付信息
        if(empty($order['orderno'])){
            $res['status']  = false;
            $res['message'] = '订单不存在';
        }
        if($plugin == 'citydelivery'){
            $payInfo = pdo_get(PDO_NAME."paylogvfour",['tid'=>$order['orderno']] ,['tid','transaction_id','fee','blendcredit','mid','module','uniacid','type']);
            if(empty($payInfo)){
                $payInfo = pdo_get(PDO_NAME."paylogvfour",['plid'=>$order['paylogid']] ,['tid','transaction_id','fee','blendcredit','mid','module','uniacid','type']);
            }
            $order['orderno'] = $payInfo['tid'];
        }else{
            $payInfo = pdo_get(PDO_NAME."paylogvfour",['tid'=>$order['orderno']] ,['transaction_id','fee','mid','blendcredit','module','uniacid','type']);
            if(empty($payInfo)){
                $payInfo = pdo_get(PDO_NAME."paylog",['tid'=>$order['orderno']] ,['transaction_id','fee','mid','module','uniacid','type']);
            }
        }
        if(empty($payInfo)){
            $payInfo = ['fee' => 0];
        }
        $orderInfo = array_merge($order,$payInfo);//合并两个数组的信息
        #4、退款金额判断
        if ($money > $orderInfo['fee']) {
            $errMsg = '退款金额大于实际支付金额，无法退款';
        } else if ($money > 0) {
            $orderInfo['refund_money'] = $money;//记录退款金额
        } else if ($blendcredit < 0.01){
            $money = $orderInfo['fee'];//退款金额为支付金额（全额退款）
            if($orderInfo['blendcredit'] > 0){
                $money = sprintf("%.2f",$money - $orderInfo['blendcredit']);
                $blendcredit = $orderInfo['blendcredit'];
            }
        }
        //支付返现判断
        if(p('cashback')){
            //判断当前订单是否存在返现订单
            $cashOrderInfo = pdo_get(PDO_NAME."cashback",['order_id'=>$id,'plugin'=>$plugin,'status'=>1]);
            if($cashOrderInfo){
                //退款比例计算
                $proportion = sprintf("%.2f",$money / $orderInfo['fee']);//比例
                $cashRefund = sprintf("%.2f",$cashOrderInfo['money'] * $proportion);//取消的返现金额
                //数据判断 余额是否大于当前取消返现金额
                $member = Member::wl_member_get($cashOrderInfo['mid'],['id','uid','cash_back_money']);//用户当前余额获取
                if($member['credit2'] < $cashRefund) return ['status'=>false,'message'=>'退款失败;余额不足，不能取消返现金额！'];
            }
        }
        #5、插入退款记录信息
        $refundRecord = [
            'sid'        => $orderInfo['sid'] ,
            'orderno'    => $orderInfo['orderno'] ,
            'mid'        => $orderInfo['mid'] ,
            'aid'        => $orderInfo['aid'] ,
            'paytype'    => $orderInfo['type'] ,
            'transid'    => $orderInfo['transaction_id'] ,
            'type'       => $type ,
            'orderid'    => $id ,
            'payfee'     => $orderInfo['fee'] ,
            'refundfee'  => sprintf("%.2f",$money + $blendcredit),
            'uniacid'    => $orderInfo['uniacid'] ,
            'remark'     => $remark ,
            'plugin'     => $plugin
        ];
        $refundid = pdo_getcolumn(PDO_NAME.'refund_record',$refundRecord,'id');
        if(empty($refundid)){
            $refundRecord['createtime'] = TIMESTAMP;
            $refundRecord['status']     = 0;
            pdo_insert(PDO_NAME . 'refund_record' , $refundRecord);
            $refundid = pdo_insertid();
        }
        #6、判断退款成功失败信息
        $pluginArray = ['hotel', 'rush','housekeep' , 'vip' ,'mobilerecharge', 'citydelivery','yellowpage','coupon' ,'attestation','merchant' , 'wlfightgroup' , 'activity' , 'groupon' , 'bargain' ];
        if (!in_array($plugin , $pluginArray)) {
            pdo_update(PDO_NAME . 'refund_record' , [ 'errmsg' => '退款订单插件' . $plugin . '错误' ] , [ 'id' => $refundid]);
            $errMsg = '退款订单插件' . $plugin . '错误';
        }
        if ($money <= 0 && $blendcredit <= 0 && $orderInfo['paytype'] != 6 ) {
            pdo_update(PDO_NAME . 'refund_record' , ['errmsg' => '退款金额小于0'] , ['id' => $refundid]);
            $errMsg = '退款金额小于0';
        }
        if (empty($orderInfo['transaction_id']) && $orderInfo['type'] == 'wechat') {
            pdo_update(PDO_NAME . 'refund_record' ,['errmsg' => '无微信订单号'] , ['id' => $refundid]);
            $errMsg = '微信订单无微信订单号';
        }
        if($orderInfo['module'] == 'weliam_merchant'){
            $errMsg = '此订单为旧版系统支付订单，无法在V4版本进行退款，请前往旧版系统退款';
            pdo_update(PDO_NAME . 'refund_record' ,['errmsg' => $errMsg] , ['id' => $refundid]);
        }
        #7、符合退款要求，开始退款操作
        if (empty($errMsg)){
            if($orderInfo['paytype'] == 6){
                $result = ['error' => 1];
            }else{
                $result = Refund::refundInit($orderInfo['orderno'],$money,$blendcredit);        //调用方法进行退款操作
            }
        }
        #8、返回退款结果
        if ($result['error']) {
            pdo_update(PDO_NAME . 'refund_record' , [ 'status' => 1 ] , [ 'id' => $refundid ]);
            $res['status']  = true;
            $res['message'] = '退款成功';
            //支付返现退款操作
            if(p('cashback') && $cashOrderInfo){
                //修改用户返现金额
                pdo_update(PDO_NAME."member",['cash_back_money'=>sprintf("%.2f",$member['cash_back_money'] - $cashRefund)],['id'=>$cashOrderInfo['mid']]);
                //修改用户余额信息
                Member::credit_update_credit2($cashOrderInfo['mid'],-$cashRefund,'订单['.$order['orderno'].']退款取消返现金额');
                //修改返现记录信息
                pdo_update(PDO_NAME."cashback",['refund_money'=>sprintf("%.2f",$cashOrderInfo['refund_money'] + $cashRefund)],['id'=>$cashOrderInfo['id']]);
            }
        } else {
            $errMsg = $result['msg'] ? : $errMsg;
            if (empty($errMsg)) $errMsg = '未知错误，请联系管理员';
            pdo_update(PDO_NAME . 'refund_record' ,['errmsg' => $errMsg] , ['id' => $refundid]);
            $res['status']  = false;
            $res['message'] = $errMsg;
        }
        return $res;
    }



}
