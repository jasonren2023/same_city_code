<?php
defined('IN_IA') or exit('Access Denied');

class Mobilerecharge {
    /**
     * 支付回调
     */
    public function payRechargeOrderNotify($params){
        global $_W;
        Util::wl_log('mrecharge_notify', PATH_DATA . "mrecharge/data/", $params); //写入异步日志记录
        $order_out = pdo_fetch("select * from" . tablename(PDO_NAME . 'mrecharge_order') . "where orderno='{$params['tid']}'");
        $_W['uniacid'] = $order_out['uniacid'];
        $set = Setting::wlsetting_read('mobilerecharge');
        if ($order_out['status'] == 0 ){
            $data = array('status' => $params['result'] == 'success' ? 1 : 0);
            $data['paytype'] = $params['type'];
            $data['paytime'] = TIMESTAMP;
            $data['transid'] = pdo_getcolumn(PDO_NAME.'paylogvfour',array('tid'=>$params['tid']),'transaction_id');;
            //分销订单创建
            if($set['rechargedis'] > 0){
                if($set['rechargePlatform'] == 1){
                    if($order_out['type'] == 1){
                        $jing50one = $set['slowjing50one'];
                        $jing50two = $set['slowjing50two'];
                        $jing100one = $set['slowjing100one'];
                        $jing100two = $set['slowjing100two'];
                        $jing200one = $set['slowjing200one'];
                        $jing200two = $set['slowjing200two'];
                    }else if($order_out['type'] == 2){
                        $jing50one = $set['fastjing50one'];
                        $jing50two = $set['fastjing50two'];
                        $jing100one = $set['fastjing100one'];
                        $jing100two = $set['fastjing100two'];
                        $jing200one = $set['fastjing200one'];
                        $jing200two = $set['fastjing200two'];
                    }else if($order_out['type'] == 3){
                        $jing50one = $set['mostjing50one'];
                        $jing50two = $set['mostjing50two'];
                        $jing100one = $set['mostjing100one'];
                        $jing100two = $set['mostjing100two'];
                        $jing200one = $set['mostjing200one'];
                        $jing200two = $set['mostjing200two'];
                    }

                    if($order_out['money'] == 50){
                        $onemoney = $jing50one;
                        $twomoney = $jing50two;
                    }else if($order_out['money'] == 100){
                        $onemoney = $jing100one;
                        $twomoney = $jing100two;
                    }else if($order_out['money'] == 200){
                        $onemoney = $jing200one;
                        $twomoney = $jing200two;
                    }
                    $disorderid = Distribution::disCore($order_out['mid'],$order_out['price'],$onemoney,$twomoney,0,$order_out['id'],'mobilerecharge',1);
                    $data['disorderid'] = $disorderid;
                }
            }
            pdo_update(PDO_NAME . 'mrecharge_order', $data, array('orderno' => $params['tid'])); //更新订单状态
            //通知支付成功
            News::paySuccess($order_out['id'],'mobilerecharge',1);
            //36鲸下单
            if($order_out['channel'] == 1){
                $res = self::sljOrderSubmit($order_out);
            }
            //下单失败 加入计划任务
            if($res['error'] > 0){
                //退款
                //self::refund($order_out['orderno'],$res['msg']);
                //添加结算抢购订单到计划任务
                $rushtask = [
                    'type'    => 'mrecharge' ,
                    'orderid' => $order_out['id']
                ];
                $rushtask = serialize($rushtask);
                Queue::addTask(11 ,$rushtask,time(),$order_out['id']);
            }
        }
    }

    /**
     * 36鲸渠道检测
     */
    public function sljOrderStatus($mobile,$type,$money){
        global $_W;
        $set = Setting::wlsetting_read('mobilerecharge');
        $apiurl = 'v1/mobile/status';
        $posturl = $set['domainname'].$apiurl;

        if($type == 1 ){
            $retype = 1;
        }else if($type == 2){
            $retype = 0;
        }else if($type == 3){
            $retype = 2;
        }
        $data = [
            'appKey' => $set['account'],
            'mobile' => $mobile,
            'time' => time(),
        ];
        if($retype > 0 ){
            $data['type'] = $retype;
        }
        $data['sign'] = self::sljGetSign($data,$set['secretkey']);
        $info = curlPostRequest($posturl,$data);
        if($info['result_code'] == 'SUCCESS'){
            if($info['data']['status'] == 1){
                if(in_array($money,$info['data']['quota'])){
                    return ['error' => 0];
                }else{
                    return ['error' => 1,'msg' => $money.'元面额充值升级中，请稍后再试'];
                }
            }else{
                return ['error' => 1,'msg' => $info['data']['tip']];
            }
        }else{
            Util::wl_log('mrecharge_error', PATH_DATA . "mrecharge/data/", $info); //写入异步日志记录
            return ['error' => 1,'msg' => '通信错误，请刷新重试'];
        }
    }

        /**
     * 36鲸下单
     */
    public function sljOrderSubmit($order_out){
        global $_W;
        $set = Setting::wlsetting_read('mobilerecharge');
        //获取接口地址
        if($order_out['type'] == 1 ){
            $apiurl = 'v1/mobile/sloworder';
        }else if($order_out['type'] == 2){
            $apiurl = 'v1/mobile/order';
        }else if($order_out['type'] == 3){
            $apiurl = 'v1/mobile/express';
        }
        $posturl = $set['domainname'].$apiurl;
        //构建数据
        $data = [
            'appKey' => $set['account'],
            'orderId' => $order_out['orderno'],
            'mobile' => $order_out['mobile'],
            'amount' => sprintf("%.2f",$order_out['money']),
            'notifyUrl' => $_W['siteroot'].'addons/'.MODULE_NAME.'/plugin/mobilerecharge/sljAsyNotify.php'
        ];
        //获取签名
        $data['sign'] = self::sljGetSign($data,$set['secretkey']);
        Util::wl_log('mrecharge_order', PATH_DATA . "mrecharge/data/", $data); //写入异步日志记录
        $info = curlPostRequest($posturl,$data);
        if($info['result_code'] == 'SUCCESS'){
            pdo_update(PDO_NAME . 'mrecharge_order',['otherorderno' => $info['data']['number']],array('id' => $order_out['id'])); //更新订单状态
            Util::wl_log('mrecharge_success', PATH_DATA . "mrecharge/data/", $info); //写入异步日志记录
            return ['error' => 0];
        }else{
            Util::wl_log('mrecharge_error', PATH_DATA . "mrecharge/data/", $info); //写入异步日志记录
            return ['error' => 1,'msg' => $info['return_msg']];
        }
    }
    /**
     * 36鲸签名生成
     */
    public function sljGetSign($data,$seckey){
        ksort($data);
        $arr = [];
        foreach ($data as $key => $value) {
            $arr[] = $key.'='.$value;
        }
        $arr[] = 'key='.$seckey;
        $str = implode('&', $arr);
        $str = strtoupper(md5($str));

        return $str;
    }
    /**
     * 订单退款
     */
    static function refund($orderno,$reason = '下单失败',$money = 0, $unline = ''){
        global $_W;
        $order = pdo_get('wlmerchant_mrecharge_order',array('orderno' => $orderno));

        if ($unline) {
            $res['status'] = 1;
        } else {
            $res = wlPay::refundMoney($order['id'], $money, '充值订单退款', 'mobilerecharge',2);
        }
        if ($res['status']) {
            pdo_update('wlmerchant_mrecharge_order',array('status' => 3,'reason' => $reason,'finishtime' => time()),array('id' => $order['id']));
        }
        if ($order['disorderid']) {
            Distribution::refunddis($order['disorderid']);
        }
        News::refundNotice($order['id'],'mobilerecharge',$money,$reason);


        return $res;
    }

    /**
     * 获取关注二维码路径地址
     */
    static function getgzqrcode($mid){
        global $_W;
        $qrid = pdo_getcolumn(PDO_NAME . 'qrcode', array('uniacid' => $_W['uniacid'], 'sid' => $mid, 'type' => 1, 'status' => 1, 'remark' => 'mobilerecharge'), 'qrid');
        $qrcode = pdo_get('qrcode', array('uniacid' => $_W['uniacid'], 'status' => 1, 'id' => $qrid, 'keyword' => 'weliam_smartcity_mobilerecharge'));
        if ($qrcode['expire'] > 0) {
            $createTime = $qrcode['createtime'];//建立时间  秒
            $expireTime = $qrcode['expire'];//有效时间  秒
            $endTime = ($createTime + $expireTime) - time();//距离结束时间还有多少时间  小于1则已经过期
        } else {
            $endTime = 1;
        }
        if (empty($qrid) || $endTime < 1 || empty($qrcode)) {
            //删除旧的二维码信息
            if ($qrid) {
                pdo_update('qrcode', array('status' => 2), array('id' => $qrid));
                pdo_update(PDO_NAME . 'qrcode', array('status' => 2), array('qrid' => $qrid));
            }
            //申请新的二维码信息
            Weixinqrcode::createkeywords('话费充值二维码:Mobilerecharge', 'weliam_smartcity_mobilerecharge');
            //判断是生成普通二维码 还是生成永久二维码
            $result = Weixinqrcode::createqrcode('话费充值二维码:Mobilerecharge', 'weliam_smartcity_mobilerecharge', 1, 1, -1, '话费充值二维码:weliam_smartcity_mobilerecharge');
            if (!is_error($result)) {
                $qrid = $result;
                pdo_update(PDO_NAME . 'qrcode', array('sid' => $mid), array('uniacid' => $_W['uniacid'], 'qrid' => $qrid));
            }
        }
        $qrurl = pdo_get('qrcode', array('id' => $qrid, 'uniacid' => $_W['uniacid']), array('url', 'ticket'));
        return $qrurl;
    }
    /**
     * 消息推送
     */
    static function Processor($message)
    {
        global $_W;
        if (strtolower($message['msgtype']) == 'event') {
            //获取数据
            $returnmess = array();
            $qrid = Weixinqrcode::get_qrid($message);

            $mid = pdo_getcolumn(PDO_NAME . 'qrcode', array('uniacid' => $_W['uniacid'], 'qrid' => $qrid), 'sid');

            $set = Setting::wlsetting_read('mobilerecharge');

            $pagepath = 'pages/subPages2/voucherCenter/voucherCenter?head_id='.$mid;

            if(empty($_W['attachurl_remote'])){
                $uni_remote_setting = uni_setting_load('remote');
                $_W['attachurl_remote'] = $uni_remote_setting['remote']['alioss']['url'].'/';
            }

            $share_title = $set['share_title'];
            $share_desc = $set['share_desc'];
            //文本替换
            $nickname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$mid),'nickname');;
            $time     = date("Y-m-d H:i:s" , time());
            $sysname  = $_W['wlsetting']['base']['name'];
            $settings = Setting::wlsetting_read('share');//不存在代理商分享信息时获取平台分享信息
            if (empty($share_title)) {
                $share_title = $settings['share_title'];
            } else {
                $share_title = str_replace('[昵称]' , $nickname , $share_title);
                $share_title = str_replace('[时间]' , $time , $share_title);
                $share_title = str_replace('[系统名称]' , $sysname , $share_title);
            }
            if (empty($share_desc)) {
                $share_desc = $settings['share_desc'];
            } else {
                $share_desc = str_replace('[昵称]' , $nickname ,$share_desc);
                $share_desc = str_replace('[时间]' , $time , $share_desc);
                $share_desc = str_replace('[系统名称]' , $sysname , $share_desc);
            }
            $returnmess[] = array('title' => urlencode($share_title), 'description' => urlencode($share_desc), 'picurl' => tomedia($set['share_image']), 'url' => h5_url($pagepath));

            Weixinqrcode::send_news($returnmess, $message);

            if($message['event'] == 'subscribe'){
                $laterflag = 1;
            }else{
                $laterflag = 0;
            }
            Distribution::addJunior($mid, $_W['wlmember']['id'],'',1,$laterflag);
        }
    }




}