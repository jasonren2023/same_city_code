<?php
defined('IN_IA') or exit('Access Denied');
class PayModuleUniapp extends Uniapp
{
    /**
     * Comment: 获取订单信息
     * Author: wlf
     * Date: 2019/8/9 15:30
     */
    public function getOrderInfo()
    {
        global $_W , $_GPC;
        $trade   = Setting::wlsetting_read('trade');
        $orderid = $_GPC['orderid'];
        $type    = $_GPC['plugin'];
        $deliverystring = $_GPC['deliverystring'];
        $data    = [];
        if ($type == 'rush') {  //抢购
            $order            = pdo_get('wlmerchant_rush_order' , ['id' => $orderid]);
            $data['goodname'] = pdo_getcolumn(PDO_NAME . 'rush_activity' , ['id' => $order['activityid']] , 'name');
            $data['price']    = $order['actualprice'];
            $plugin           = 'Rush';
            $payfor           = 'RushOrder';
        }
        else if ($type == 'opencard') {  //一卡通
            $order            = pdo_get('wlmerchant_halfcard_record' , ['id' => $orderid]);
            $data['price']    = $order['price'];
            $data['goodname'] = pdo_getcolumn(PDO_NAME . 'halfcard_type' , ['id' => $order['typeid']] , 'name');
            $plugin           = 'Merchant';
            $payfor           = 'Halfcard';
            $checkpayP        = 'Halfcard';
        }
        else if ($type == 'attestation') {
            $order            = pdo_get('wlmerchant_attestation_money' , ['id' => $orderid]);
            $data['price']    = $order['money'];
            $data['goodname'] = '认证保证金';
            $plugin           = 'Attestation';
            $payfor           = 'Bond';
        }
        else if ($type == 'mobilerecharge') {
            $order            = pdo_get('wlmerchant_mrecharge_order' , ['id' => $orderid]);
            $data['price']    = $order['price'];
            $data['goodname'] = '充值'.$order['money'].'元';
            $plugin           = 'Mobilerecharge';
            $payfor           = 'RechargeOrder';
        }
        else if ($type == 'citydelivery') {
            if(!empty($orderid)){
                $order = pdo_get('wlmerchant_order' , ['id' => $orderid]);
                $data['price']    = $order['price'];
                $smallorders = pdo_getall('wlmerchant_delivery_order',array('tid' => $order['orderno']),array('gid'));
                $data['goodname'] = pdo_getcolumn(PDO_NAME.'delivery_activity',array('id'=>$smallorders[0]['gid']),'name');
                if(count($smallorders)>1){
                    $data['goodname'] .= ' 等';
                }
            }else{
                $orderids = json_decode(base64_decode($deliverystring),true);
                foreach ($orderids as $orid){
                    $order = pdo_get('wlmerchant_order' , ['id' => $orid]);
                    $data['price'] += $order['price'];
                    $smallorders = pdo_getall('wlmerchant_delivery_order',array('tid' => $order['orderno']),array('gid'));
                    $data['goodname'] = pdo_getcolumn(PDO_NAME.'delivery_activity',array('id'=>$smallorders[0]['gid']),'name');
                    if(count($smallorders) > 1 || count($orderids) > 1){
                        $data['goodname'] .= ' 等';
                    }
                }
                $data['price'] = sprintf("%.2f",$data['price']);
            }
            $plugin           = 'Citydelivery';
            $payfor           = 'DeliveryOrder';
        }
        else {
            //其他
            $order         = pdo_get('wlmerchant_order' , ['id' => $orderid]);
            $data['price'] = $order['price'];
            if ($order['plugin'] == 'groupon') {
                $data['goodname'] = pdo_getcolumn(PDO_NAME . 'groupon_activity' , ['id' => $order['fkid']] , 'name');
                $plugin           = 'Groupon';
                $payfor           = 'GrouponOrder';
            }
            else if ($order['plugin'] == 'wlfightgroup') {
                $data['goodname'] = pdo_getcolumn(PDO_NAME . 'fightgroup_goods' , ['id' => $order['fkid']] , 'name');
                $plugin           = 'Wlfightgroup';
                $payfor           = 'Fightsharge';
            }
            else if ($order['plugin'] == 'distribution') {
                $data['goodname'] = '开通'.$_W['wlsetting']['trade']['fxstext'];
                $plugin           = 'Distribution';
                $payfor           = 'Applydis';
            }
            else if ($order['plugin'] == 'store') {
                $data['goodname'] = '商户付费入驻';
                $plugin           = 'Merchant';
                $payfor           = 'Charge';
                $checkpayP        = 'Charge';
            }
            else if ($order['plugin'] == 'halfcard') {
                if ($order['fkid']) {
                    $data['goodname'] = pdo_getcolumn(PDO_NAME . 'halfcardlist' , ['id' => $order['fkid']] , 'title');
                }
                else {
                    $data['goodname'] = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $order['sid']] , 'storename');
                }
                $data['goodname'] = $data['goodname'] . '在线买单';
                $plugin           = 'Merchant';
                $payfor           = 'payonline';
                $checkpayP        = 'Payonline';
            }
            else if ($order['plugin'] == 'coupon') {
                $data['goodname'] = pdo_getcolumn(PDO_NAME . 'couponlist' , ['id' => $order['fkid']] , 'title');
                $plugin           = 'wlCoupon';
                $payfor           = 'Couponsharge';
            }
            else if ($order['plugin'] == 'bargain') {
                $data['goodname'] = pdo_getcolumn(PDO_NAME . 'bargain_activity' , ['id' => $order['fkid']] , 'name');
                $plugin           = 'Bargain';
                $payfor           = 'BargainOrder';
            }
            else if ($order['plugin'] == 'pocket') {
                $data['goodname'] = '帖子付费项目';
                $plugin           = 'Pocket';
                $payfor           = 'pocketfabusharge';
            }
            else if ($order['plugin'] == 'consumption') {
                $data['goodname'] = pdo_getcolumn(PDO_NAME . 'consumption_goods' , ['id' => $order['fkid']] , 'title');
                $plugin           = 'Consumption';
                $payfor           = 'consumOrder';
            }
            else if ($order['plugin'] == 'member') {
                $data['goodname'] = $trade['moneytext'] . '充值';
                $plugin           = 'Member';
                $payfor           = 'Charge';
            }
            else if ($order['plugin'] == 'taxipay') {
                $data['goodname'] = '出租车在线买单';
                $plugin           = 'Taxipay';
                $payfor           = 'TaxipayOrder';
            }
            else if ($order['plugin'] == 'citycard') {
                $data['goodname'] = '同城名片支付项目';
                $plugin           = 'Citycard';
                $payfor           = 'CitycardOrder';
            }
            else if ($order['plugin'] == 'yellowpage') {
                if($order['fightstatus'] == 1){
                    $data['goodname'] = '114页面认领';
                }else if($order['fightstatus'] == 2){
                    $data['goodname'] = '114页面查看';
                }else if($order['fightstatus'] == 3){
                    $data['goodname'] = '114页面入驻';
                }
                $plugin           = 'Yellowpage';
                $payfor           = 'pageOrder';
            }
            else if($order['plugin'] == 'activity'){
                $data['goodname'] = pdo_getcolumn(PDO_NAME . 'activitylist' , ['id' => $order['fkid']] , 'title');
                $plugin           = 'Activity';
                $payfor           = 'Activitysharge';
            }
            else if($order['plugin'] == 'recruit'){
                $data['goodname'] = '招聘发布';
                $plugin           = 'Recruit';
                $payfor           = 'RecruitOrder';
            }
            else if($order['plugin'] == 'dating'){
                $data['goodname'] = '相亲交友';
                $plugin           = 'Dating';
                $payfor           = $order['payfor'];
            }
            else if($order['plugin'] == 'vehicle'){
                $data['goodname'] = '顺风车';
                $plugin           = 'Vehicle';
                $payfor           = $order['payfor'];
            }
            else if ($order['plugin'] == 'housekeep') {
                if($order['fightstatus'] == 1){
                    $data['goodname'] = '家政服务订单';
                }else if($order['fightstatus'] == 2){
                    $data['goodname'] = '家政入驻订单';
                }else if($order['fightstatus'] == 3){
                    $data['goodname'] = '家政需求付费发布';
                }else if($order['fightstatus'] == 3){
                    $data['goodname'] = '家政需求付费置顶';
                }else if($order['fightstatus'] == 3){
                    $data['goodname'] = '家政需求付费刷新';
                }
                $plugin           = 'Housekeep';
                $payfor           = 'HousekeepOrder';
            }else if ($order['plugin'] == 'house') {
                if($order['fightstatus'] == 1){
                    $data['goodname'] = '新房发布订单';
                }else if($order['fightstatus'] == 2){
                    $data['goodname'] = '二手房发布订单';
                }else if($order['fightstatus'] == 3){
                    $data['goodname'] = '租房发布订单';
                }
                $plugin           = 'House';
                $payfor           = 'HouseOrder';
            }else if ($order['plugin'] == 'hotel') {
                $data['goodname'] = pdo_getcolumn(PDO_NAME . 'hotel_room' , ['id' => $order['fkid']] , 'name');
                $plugin           = 'Hotel';
                $payfor           = 'HotelOrder';
            }
        }
        $data['sytime'] = $order['canceltime'] - time();
        if ($data['sytime'] < 0) {
            $data['sytime'] = 0;
        }
        if (empty($order)) {
            $this->renderError('订单信息错误,请返回订单列表重新发起支付');
        }
        //判断mid和uniacid是否一致
        if ($order['uniacid'] != $_W['uniacid']) {
            $this->renderError('订单公众号错误，请返回订单列表重新进入支付页面');
        }
        if ($order['mid'] != $_W['mid']) {
            $this->renderError('订单用户错误，请返回订单列表重新进入支付页面');
        }
        $data['balance'] = sprintf("%.2f" , $_W['wlmember']['credit2']);
        //智慧城市
        if($type == 'citydelivery' && !empty($orderids)){
            $merchantlog = [
                'uniacid'    => $order['uniacid'] ,
                'acid'       => $_W['acid'] ,
                'mid'        => $order['mid'] ,
                'module'     => 'weliam_smartcity',
                'plugin'     => $plugin ,
                'payfor'     => $payfor ,
                'tid'        => createUniontid(),
                'fee'        => $data['price'] ,
                'card_fee'   => $data['price'] ,
                'status'     => '0' ,
                'is_usecard' => '0' ,
            ];
            pdo_insert(PDO_NAME . 'paylogvfour' , $merchantlog);
            $merchantlogid = pdo_insertid();
            foreach ($orderids as $orid2){
                pdo_update('wlmerchant_order',array('paylogid' => $merchantlogid),array('id' => $orid2));
            }
            $order['orderno'] = $merchantlog['tid'];
        }else{
            $merchantlog = pdo_get(PDO_NAME . 'paylogvfour' , [
                'uniacid' => $_W['uniacid'] ,
                'plugin'  => $plugin ,
                'tid'     => $order['orderno']
            ]);
            if (empty($merchantlog)) {
                $merchantlog = [
                    'uniacid'    => $order['uniacid'] ,
                    'acid'       => $_W['acid'] ,
                    'mid'        => $order['mid'] ,
                    'module'     => 'weliam_smartcity' ,
                    'plugin'     => $plugin ,
                    'payfor'     => $payfor ,
                    'tid'        => $order['orderno'] ,
                    'fee'        => $data['price'] ,
                    'card_fee'   => $data['price'] ,
                    'status'     => '0' ,
                    'is_usecard' => '0' ,
                ];
                pdo_insert(PDO_NAME . 'paylogvfour' , $merchantlog);
            }
        }

        if ($merchantlog['status'] == '1') {
            $this->renderError('这个订单已经支付成功, 不需要重复支付' , $data);
        }
        //判断商品独立支付设置信息
        if (in_array($plugin , ['Rush' , 'Groupon' , 'Wlfightgroup' , 'Bargain' , 'wlCoupon'])) {
            //获取当前商品的独立支付设置
            switch ($plugin) {
                case 'Rush':
                    $goodsPayType = pdo_getcolumn(PDO_NAME . 'rush_activity' , ['id' => $order['activityid']] , 'pay_type');
                    break;
                case 'Groupon':
                    $goodsPayType = pdo_getcolumn(PDO_NAME . 'groupon_activity' , ['id' => $order['fkid']] , 'pay_type');
                    break;
                case 'Wlfightgroup':
                    $goodsPayType = pdo_getcolumn(PDO_NAME . 'fightgroup_goods' , ['id' => $order['fkid']] , 'pay_type');
                    break;
                case 'Bargain':
                    $goodsPayType = pdo_getcolumn(PDO_NAME . 'bargain_activity' , ['id' => $order['fkid']] , 'pay_type');
                    break;
                case 'wlCoupon':
                    $goodsPayType = pdo_getcolumn(PDO_NAME . 'couponlist' , ['id' => $order['fkid']] , 'pay_type');
                    break;
            }
            $goodsPayType = unserialize($goodsPayType);
        }
        $goodsPayType = is_array($goodsPayType) && count($goodsPayType) > 0 ? $goodsPayType : ['wechat' , 'alipay' , 'balance','yunpay'];
        //获取开启的支付方式  支付渠道 1=公众号（默认）；2=h5；3=小程序
        $paymentSet = Setting::wlsetting_read('payment_set');
        switch ($_W['source']) {
            case 1:
                $wechat_payset = $paymentSet['wechat'];
                break;//公众号
            case 2:
                $wechat_payset = $paymentSet['h5'];
                break;//h5
            case 3:
                $wechat_payset = $paymentSet['wxapp'];
                break;//小程序
        }
        if (!empty($wechat_payset['balance']) && $order['payfor'] != 'recharge' && in_array('balance' , $goodsPayType)) {
            //开启余额支付的情况下  判断当前模块是否支持余额支付
            $balanceModel = is_array($wechat_payset['balance_model']) ? $wechat_payset['balance_model'] : [];//限制模块信息
            $allowList = array_keys(Payment::getBalanceModel());//允许限制的模块列表
            if(empty($checkpayP)){
                $checkpayP = $plugin;
            }
            //判断：存在限制模块&&当前模块在允许限制列表中&&不允许当前模块使用余额支付
            if(count($balanceModel) > 0 && in_array($checkpayP,$allowList) && !in_array($checkpayP,$balanceModel)){
                $data['credit'] = 0;
            }else{
                $data['credit'] = 1;
            }
        }else {
            $data['credit'] = 0;
        }
        if ($wechat_payset['wechat'] > 0 && in_array('wechat' , $goodsPayType)) {
            $data['wechat'] = 1;
        }else {
            $data['wechat'] = 0;
        }
        if ($wechat_payset['alipay'] > 0 && in_array('alipay' , $goodsPayType)) {
            $data['alipay'] = 1;
        }else {
            $data['alipay'] = 0;
        }
        if ($wechat_payset['yunpay'] > 0 && in_array('yunpay' , $goodsPayType)) {
            $data['yunpay'] = 1;
            $data['yunset']['logo'] = $_W['wlsetting']['base']['yun_logo'] ? tomedia($_W['wlsetting']['base']['yun_logo']) : '';
            $data['yunset']['title'] = $_W['wlsetting']['base']['yun_title'] ?  : '银联云收单';
            $data['yunset']['desc'] = $_W['wlsetting']['base']['yun_desc'] ?  : '支付有优惠';
        }else {
            $data['yunpay'] = 0;
        }
        $data['tid'] = $order['orderno'];

        //881定制内容
        $isAuth = Customized::init('diy_userInfo');
        if($isAuth){
            $data['diy_userInfo']['dkprice'] = $data['balance'] > $data['price'] ? $data['price'] : $data['balance'];
            $data['diy_userInfo']['dhurl'] = $_W['wlsetting']['recharge']['dhurl'];
            $data['diy_userInfo']['dhtip2'] = $_W['wlsetting']['recharge']['dhtip2']?$_W['wlsetting']['recharge']['dhtip2']:'中国移动/中国银行积分可兑换乐豆';
        }

        $this->renderSuccess('订单支付信息' , $data);
    }
    /**
     * Comment: 用户对订单进行评论
     * Author: zzw
     * Date: 2019/8/15 13:43
     */
    public function orderComment(){
        global $_W , $_GPC;
        //检查锁
        if(lockTool($_W['mid'],'comment')){
            $this->renderError('请稍后');
        }
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array('private',$mastmobile)){
            $this->renderError('未绑定手机号');
        }
        $storeSet = Setting::wlsetting_read('agentsStoreSet');
        #1、参数接收
        $mid = $_W['mid'];//用户id
        $id = intval($_GPC['id']) OR $this->renderError('缺少id信息');//订单id
        if(!empty($_GPC['pic'])){
            $pic = explode(',' , $_GPC['pic']);//评论图片地址
            $pic = serialize($pic);
        }else {
            $pic = '';
        }
        $data['ispic'] = $_GPC['pic'] ? 1 : 0;
        $text = $_GPC['text'] OR $this->renderError('请输入评论内容!');//文本评论内容
        $star      = $_GPC['star'];//评论星级 1-5
        $tableType = $_GPC['table'];//表的类型 a = order   b = rush_order  为空是核销内容的评论
        //判断文本内容是否非法
        $textRes = Filter::init($text , $_W['source'] , 1);
        if ($textRes['errno'] == 0) {
            $this->renderError($textRes['message']);
        }
        #2、数据拼装
        $data = [
            'uniacid'    => $_W['uniacid'] ,
            'mid'        => $mid ,
            'status'     => 1 ,  //默认显示
            'pic'        => $pic ,
            'idoforder'  => $id ,
            'text'       => $text ,
            'star'       => intval($star) ,
            'createtime' => time() ,
            'headimg'    => $_W['wlmember']['avatar'] ,
            'nickname'   => $_W['wlmember']['nickname']
        ];
        if($storeSet['comexamine'] > 0){
            $data['checkone'] = 2;
            if($_W['wlsetting']['creditset']['commentcredit'] > 0){
                Member::credit_update_credit1($data['mid'], $_W['wlsetting']['creditset']['commentcredit'], '评价赠送积分');
            }
        }else{
            $data['checkone'] = 1;
        }
        #3、获取店铺id  商品类型  并且修改订单状态为已完成
        WeliamWeChat::startTrans();//开启事务
        switch ($tableType) {
            case 'a':
                $table     = PDO_NAME . 'order';
                $orderInfo = pdo_get($table , ['id' => $id] , ['sid' ,'fkid','aid' , 'plugin']);
                $res       = pdo_update($table , ['status' => 3] , ['id' => $id]);
                $gid       = $orderInfo['fkid'];
                break;//其他订单
            case 'b':
                $table               = PDO_NAME . 'rush_order';
                $orderInfo           = pdo_get($table , ['id' => $id] , ['sid' ,'activityid', 'aid']);
                $orderInfo['plugin'] = 'rush';
                $res                 = pdo_update($table , ['status' => 3] , ['id' => $id]);
                $gid                 = $orderInfo['activityid'];
                break;//抢购订单
            default:
                $table               = PDO_NAME . 'timecardrecord';
                $orderInfo           = pdo_get($table , ['id' => $id] ,['aid','merchantid','activeid']);
                $orderInfo['plugin'] = 'usehalf';
                $res                 = pdo_update($table , ['commentflag' => 1] , ['id' => $id]);
                $gid                 = $orderInfo['activeid'];
                break;//核销内容的评论
        }
        $data['aid']    = $orderInfo['aid'];
        $data['gid']    = $gid ? : 0;
        $data['sid']    = $orderInfo['sid'];
        $data['plugin'] = $orderInfo['plugin'];
        if($data['plugin'] == 'housekeep'){
            $serviceType = pdo_getcolumn(PDO_NAME.'housekeep_service',array('id'=>$gid),'type');
            if($serviceType == 2){
                $data['housekeepflag'] = 1;
            }
        }
        if (!$res) {
            WeliamWeChat::rollback();//回滚
            $this->renderError('订单信息修改失败');
        }
        else if (empty($data['plugin'])) {
            WeliamWeChat::rollback();//回滚
            $this->renderError('订单不存在');
        }
        #4、判断评分等级
        if ($data['star'] > 3) $data['level'] = 1;
        else if ($data['star'] == 3) $data['level'] = 2;
        else $data['level'] = 3;
        #4、储存评论信息
        $result = pdo_insert(PDO_NAME . 'comment' , $data);
        if ($result) {
            WeliamWeChat::commit();//提交事务
            if($data['checkone'] == 1){
                //评论成功 给管理员发送模板消息
                $first   = "用户【{$data['nickname']}】发布了一条评论";//消息头部
                $type    = "评论审核通知";//业务类型
                $content = $data['text'];//业务内容
                $status  = "待审核";//处理结果
                $remark  = "请尽快审核！";//备注信息
                $time    = $data['createtime'];//操作时间
                News::noticeAgent('storecomment' , $_W['aid'] , $first , $type , $content , $status , $remark , $time);
            }
            $this->renderSuccess('评论成功' , ['id' => pdo_insertid()]);
        }
        else {
            WeliamWeChat::rollback();//回滚
            $this->renderError('评论失败');
        }
    }
    /**
     * Comment: 转换plugin参数
     * Author: wlf
     * Date: 2019/8/21 10:00
     */
    public function conversion($type)
    {
        $types = [
            1  => 'rush' ,   //抢购
            2  => 'groupon' , //团购
            3  => 'wlfightgroup' , //拼团
            5  => 'coupon' , //超级券
            6  => 'payonline' , //在线买单
            7  => 'bargain' , //砍价
            8  => 'consumption' , //兑换
            9  => 'halfcard' , //一卡通
            10 => 'store',     //商户
            11 => 'activity'   //同城活动
        ];
        return $types[$type];
    }
    /**
     * Comment: 订单提交页面
     * Author: wlf
     * Date: 2019/8/16 09:38
     */
    public function orderSubmit()
    {
        global $_W , $_GPC;
        $type      = $this->conversion($_GPC['plugin']);  //插件类型
        $specid    = $_GPC['specid'];  //规格id
        $id        = $_GPC['id'];  //商品id
        $num       = $_GPC['num'] ? intval($_GPC['num']) : 1;  //购买数量
        $buystatus = $_GPC['buystatus'];  //拼团单购 团购
        $addressid = $_GPC['addressid'];

        $getDraw = intval($_GPC['drawid']); //领取抽奖奖品
        if(!empty($_GPC['luckydrawid'])){
            $getDraw = intval($_GPC['luckydrawid']); //领取锦鲤抽奖奖品
        }
        if(!empty($_GPC['callid'])){
            $getDraw = intval($_GPC['callid']); //领取锦鲤抽奖奖品
        }

        if($getDraw > 0){
            $type = $_GPC['plugin'];
        }
        //验证会员
        if ($_W['mid']) {
            $userhalfcard = WeliamWeChat::VipVerification($_W['mid']);
            if($userhalfcard['id'] > 0){
                $halfcardflag = $userhalfcard['id'];
                $halfcardlevel = $userhalfcard['levelid'];
            }
        }
        $allcredit            = sprintf("%.2f" , $_W['wlmember']['credit1']);
        $data                 = [];
        $data['plugin']       = $type;
        $data['halfcardflag'] = $halfcardflag;
        $data['allcredit']    = $allcredit;
        $data['realname']     = $_W['wlmember']['realname'] ? $_W['wlmember']['realname'] : $_W['wlmember']['nickname'];
        $data['mobile']       = $_W['wlmember']['mobile'];
        if ($type == 'bargain') {
            $goods                = pdo_get('wlmerchant_bargain_activity' , ['id' => $id]);
            $user                 = pdo_get('wlmerchant_bargain_userlist' , ['id' => $specid] , ['price']);
            $price                = $goodsprice = $goods['price'] = $user['price'];
            $storename            = pdo_getcolumn('wlmerchant_merchantdata' , ['id' => $goods['sid']] , 'storename');
            $data['goodsname']    = $goods['name'];   //商品名
            $data['goodsimg']     = tomedia($goods['thumb']);   //商品图案
            $data['goodsprice']   = $goodsprice;  //商品单价
            $data['usestatus']    = $goods['usestatus'];  //使用方式
            $data['optionname']   = '';  //规格名
            $data['vipdiscount']  = 0;  //会员减免
            $data['carddiscount'] = 0;  //开卡减免
            $sid = $goods['sid'];
        }
        if ($type == 'consumption') {
            $goods = Consumption::creditshop_goods_get($id);
            //会员价格
            if ($goods['vipstatus'] == 1) {
                $data['carddiscount'] = sprintf("%.2f" , ($goods['use_credit2'] - $goods['vipcredit2']) * $num);  //会员减免
                if ($halfcardflag) {
                    $goods['use_credit1'] = $goods['vipcredit1'];
                    $data['vipdiscount']  = $data['carddiscount'];
                }
                else {
                    $data['vipdiscount'] = 0;
                }
            }
            else {
                $data['vipdiscount']  = 0;
                $data['carddiscount'] = 0;  //开卡减免
            }
            if ($goods['type'] == 'credit2' || $goods['type'] == 'halfcard') {
                $conflag            = 1;
                $goods['usestatus'] = 0;
            }  //虚拟兑换
            if ($goods['type'] == 'goods') {
                $goods['usestatus'] = 1;
            }
            $goodsprice = sprintf("%.2f",$goods['use_credit2']);
            $data['goodsname']  = $goods['title'];   //商品名
            $data['goodsimg']   = tomedia($goods['thumb']);   //商品图案
            $data['goodsprice'] = $goodsprice;  //商品单价
            $data['usestatus']  = $goods['usestatus'];  //使用方式
            $data['optionname'] = '';  //规格名
            if ($halfcardflag && $goods['vipstatus'] == 1) {
                $data['credit'] = $goods['vipcredit1'] * $num;
            }
            else {
                $data['credit'] = $goods['use_credit1'] * $num;
            }
            $data['creditdiscount'] = 0; //积分抵扣
            $sid = 0;
        }
        if ($type == 'coupon') {
            $goods              = pdo_get('wlmerchant_couponlist' , ['id' => $id]);
            $goodsprice         = $goods['price'];
            $data['goodsname']  = $goods['title'];   //商品名
            $data['goodsimg']   = tomedia($goods['logo']);   //商品图案
            $data['goodsprice'] = $goodsprice;  //商品单价
            $data['usestatus']  = 0;  //使用方式
            $data['optionname'] = $goods['sub_title'];  //规格名
            if ($goods['vipstatus'] == 1) {
                $goods['vipdiscount'] = WeliamWeChat::getVipDiscount($goods['viparray'],$halfcardlevel);
                $vipReduce = sprintf("%.2f" , $goods['vipdiscount']  * $num);
                $data['carddiscount'] = unserialize($goods['viparray']);
                if ($halfcardflag) {
                    $data['vipdiscount'] = $vipReduce;
                }
                else {
                    $data['vipdiscount'] = 0;
                }
            }
            else {
                $data['vipdiscount']  = 0;
                $data['carddiscount'] = [];
            }
            $data['credit']         = 0;
            $data['creditdiscount'] = 0; //积分抵扣
            $sid = $goods['merchantid'];
        }
        if ($type == 'wlfightgroup') {
            $goods = pdo_get('wlmerchant_fightgroup_goods' , ['id' => $id]);
            if ($specid) {
                $option = pdo_get('wlmerchant_goods_option' , ['id' => $specid] , ['title' , 'price','vipprice','uuid' , 'viparray']);
                if ($buystatus == 1) {
                    $goods['price'] = $option['price'];
                }else {
                    $goods['price'] = $option['vipprice'];
                }
                $goods['viparray'] = WeliamWeChat::mergeVipArray($option['viparray'],$goods['viparray']);
            }
            else if ($buystatus == 2) {
                $goods['price']  = $goods['aloneprice'];
                $option['title'] = '';
            }
            else {
                $option['title'] = '';
            }
            $goodsprice = sprintf("%.2f" , $goods['price'] * $num);
            //会员抵扣
            if ($goods['vipstatus'] == 1) {
                $goods['vipdiscount'] = WeliamWeChat::getVipDiscount($goods['viparray'],$halfcardlevel);
                $vipReduce = sprintf("%.2f" , $goods['vipdiscount']  * $num);
                $data['carddiscount'] = unserialize($goods['viparray']);
                if ($halfcardflag) {
                    $vipdiscount = $vipReduce;
                }
                else {
                    $vipdiscount = 0;
                }
            }
            else {
                $vipdiscount          = 0;
                $data['carddiscount'] = [];
            }
            $data['goodsname']   = $goods['name'];   //商品名
            $data['goodsimg']    = tomedia($goods['logo']);   //商品图案
            $data['goodsprice']  = $goods['price'];  //商品单价
            $data['usestatus']   = $goods['usestatus'];  //使用方式
            $data['optionname']  = $option['title'];  //规格名
            $data['vipdiscount'] = $vipdiscount;  //会员减免
            $price               = sprintf("%.2f" , $goods['price'] - $goods['vipdiscount']);
            //拼团 - 团长优惠
            $data['is_com_dis'] = intval(0);
            if ($buystatus == 1 && $goods['is_com_dis'] == 1) {
                $data['is_com_dis']    = intval($goods['is_com_dis'] ? : 0);
                $data['com_dis_price'] = sprintf("%.2f" , ($goods['com_dis_price'] ? : 0));
            }
            $sid = $goods['merchantid'];
        }
        if ($type == 'rush') {
            $goods = pdo_get('wlmerchant_rush_activity' , ['id' => $id]);
            if ($specid) {
                $option            = pdo_get('wlmerchant_goods_option' , ['id' => $specid] , ['title' , 'price' , 'viparray', 'uuid','pftotherinfo']);
                $goods['price']    = $option['price'];
                $goods['vipprice'] = $option['vipprice'];
                $goods['viparray'] = WeliamWeChat::mergeVipArray($option['viparray'],$goods['viparray']);
                $goods['ticketid']    = $option['uuid'];
                $goods['pftotherinfo']    = $option['pftotherinfo'];
            }
            else {
                $option['title'] = '';
            }
            #判断是否开启阶梯价（目前仅抢购存在）  是否开启阶梯价(0=关闭 1=开启)
            if ($goods['lp_status'] == 1) {
                $goods['lp_set'] = is_array(unserialize($goods['lp_set'])) ? unserialize($goods['lp_set']) : [];
                if (count($goods['lp_set']) > 0) {
                    $info       = WeliamWeChat::getHomeGoods(1 , $id);
                    $allsalenum = intval($info['allsalenum']);
                    foreach ($goods['lp_set'] as $lpKey => $lpVal) {
                        $lpVal['max'] = intval($lpVal['max']) + $allsalenum;
                        if ($info['buy_num'] < $lpVal['max']) {
                            $goods['price']    = $lpVal['price'];
                            $goods['vipprice'] = $lpVal['vip_price'];
                            break;
                        }
                    }
                }
            }
            //其他信息计算
            if ($goods['vipstatus'] == 1) {
                $goods['vipdiscount'] = WeliamWeChat::getVipDiscount($goods['viparray'],$halfcardlevel);
                $vipReduce = sprintf("%.2f" , $goods['vipdiscount']  * $num);
                $data['carddiscount'] = unserialize($goods['viparray']);
                if ($halfcardflag) {
                    $price               = $goods['price'];
                    $data['vipdiscount'] = $vipReduce;  //会员减免
                }
                else {
                    $data['vipdiscount'] = 0;
                }
            }
            else {
                $price                = $goods['price'];
                $data['vipdiscount']  = 0;
                $data['carddiscount'] = [];
            }
            $goodsprice         = sprintf("%.2f" , $price * $num);
            $data['goodsname']  = $goods['name'];   //商品名
            $data['goodsimg']   = tomedia($goods['thumb']);   //商品图案
            $data['goodsprice'] = $goods['price'];  //商品单价
            $data['usestatus']  = $goods['usestatus'];  //使用方式
            $data['optionname'] = $option['title'];  //规格名
            $sid = $goods['sid'];
        }
        if ($type == 'groupon') {
            $goods = pdo_get('wlmerchant_groupon_activity' , ['id' => $id]);
            if ($specid) {
                $option            = pdo_get('wlmerchant_goods_option' , ['id' => $specid] , ['title' , 'price' , 'viparray', 'uuid','pftotherinfo']);
                $goods['price']    = $option['price'];
                $goods['viparray'] = WeliamWeChat::mergeVipArray($option['viparray'],$goods['viparray']);
                $goods['ticketid']    = $option['uuid'];
                $goods['pftotherinfo']    = $option['pftotherinfo'];
            }
            else {
                $option['title'] = '';
            }
            //计算会员特价
            if ($goods['vipstatus'] == 1) {
                $goods['vipdiscount'] = WeliamWeChat::getVipDiscount($goods['viparray'],$halfcardlevel);
                $vipReduce = sprintf("%.2f" , $goods['vipdiscount']  * $num);
                $data['carddiscount'] = unserialize($goods['viparray']);
                if ($halfcardflag) {
                    $data['vipdiscount'] = $vipReduce;
                }
                else {
                    $data['vipdiscount'] = 0;
                }
            }
            else {
                $data['vipdiscount']  = 0;
                $data['carddiscount'] = [];
            }
            $data['goodsname']  = $goods['name'];   //商品名
            $data['goodsimg']   = tomedia($goods['thumb']);   //商品图案
            $data['goodsprice'] = $goods['price'];  //商品单价
            $data['usestatus']  = $goods['usestatus'];  //使用方式
            $data['optionname'] = $option['title'];  //规格名
            $sid = $goods['sid'];
        }
        if ($type == 'activity'){
            $goods = pdo_get('wlmerchant_activitylist' , ['id' => $id]);
            if($specid > 0){
                $spec = pdo_get('wlmerchant_activity_spec' , ['id' => $specid]);
                $goods['price'] = $spec['price'];
                $data['optionname'] = $spec['name'];
                $goods['viparray'] = WeliamWeChat::mergeVipArray($spec['viparray'],$goods['viparray']);
            }
            $price = $goodsprice = $goods['price'];
            $storename = pdo_getcolumn('wlmerchant_merchantdata',['id' => $goods['sid']] , 'storename');
            $data['goodsname']    = $goods['title'];   //商品名
            $data['goodsimg']     = tomedia($goods['thumb']);   //商品图案
            $data['goodsprice']   = $goodsprice;  //商品单价
            $data['usestatus']    = 0;  //使用方式
            if($goods['vipstatus'] == 1){
                $goods['vipdiscount'] = WeliamWeChat::getVipDiscount($goods['viparray'],$halfcardlevel);
                $data['vipdiscount']  = sprintf("%.2f" , $goods['vipdiscount']*$num);  //会员减免
                $data['carddiscount'] = unserialize($goods['viparray']);
            }else{
                $data['vipdiscount'] = 0;
                $data['carddiscount'] = [];
            }
            $sid = $goods['sid'];
        }
        //红包系统
        if(p('redpack') && empty($getDraw)){
            $data['redpacklist'] = Redpack::getNotUseList($data['goodsprice'] * $num,$sid,$goods['aid'],$id,$type);
        }else{
            $data['redpacklist'] = ['list'=>[],'total'=>0];
        }
        //满减优惠
        if($goods['fullreduceid']>0 && empty($getDraw)){
            $fullreduce = pdo_get('wlmerchant_fullreduce_list',array('id' => $goods['fullreduceid'],'status' => 1),array('rules','title'));
            if(!empty($fullreduce)){
                $data['fullreducelist']['title'] = $fullreduce['title'];
                $data['fullreducelist']['list'] = unserialize($fullreduce['rules']);
            }
        }
        //积分抵扣
        if ($goods['creditmoney']>0 && $_W['wlsetting']['creditset']['dkstatus'] && empty($getDraw)) {
            //每一份可以使用的积分
            $onedkcredit = sprintf("%.2f" , $goods['creditmoney'] * $_W['wlsetting']['creditset']['proportion']);
            //总共需要积分
            $dkcredit = sprintf("%.2f" , $onedkcredit * $num);
            //用户所有积分
            if ($allcredit < $dkcredit) {
                $dkcredit = $allcredit;
            }
            //抵扣金额
            $dkmoney                = sprintf("%.2f" , $dkcredit / $_W['wlsetting']['creditset']['proportion']);
            $data['credit']         = $dkcredit;  //可使用积分
            $data['creditdiscount'] = $dkmoney; //积分抵扣金额
        }
        else {
            if (empty($data['credit'])) {
                $data['credit'] = 0; //可使用积分
            }
            $data['creditdiscount'] = 0; //积分抵扣金额
        }
        //计算运费
        if ($data['usestatus'] > 0) {
            if ($addressid) {
                $address = pdo_get('wlmerchant_address' , ['id' => $addressid] , ['id','name' ,'status','tel','province','city','county','detailed_address']);
            }else{
                $address = pdo_get('wlmerchant_address' , ['mid'     => $_W['mid'] , 'uniacid' => $_W['uniacid'] , 'status'  => 1] , ['id' , 'status' , 'name' , 'tel' , 'province' , 'city' , 'county' , 'detailed_address']);
            }

            if (empty($address)){
                $address = pdo_get('wlmerchant_address' , ['mid' => $_W['mid'] , 'uniacid' => $_W['uniacid']] , ['id' , 'name' , 'status' , 'tel' , 'province' , 'city' , 'county' , 'detailed_address']);
                if ($address) {
                    pdo_update('wlmerchant_address' , ['status' => 1] , ['id' => $address['id']]);
                }
            }
            if (empty($address)) {
                $data['expressprice'] = 0;
                $data['address']      = '';
            }else{
                $expressarray = $this->freight($address['id'],$num,$goods);
                $data['expressprice'] = $expressarray['price'];
                $data['address'] = $address;
            }
        }
        //支付开卡功能
        if(empty($getDraw)){
            $data['is_openvip'] = $_W['wlsetting']['halfcard']['is_openvip'];
        }else{
            $data['is_openvip'] = 0;
        }
        //额外表单内容
        if($goods['diyformid'] > 0){
            $diyFromInfo       = pdo_getcolumn(PDO_NAME . 'diyform' , ['id' => $goods['diyformid']] , 'info');
            $data['diyform']   = json_decode(base64_decode($diyFromInfo) , true);//页面的配置信息
            $data['diyformid'] = $goods['diyformid'];
        }
        //票付通
        $data['pftid'] = $goods['pftid'] ? : 0;
        $data['pftuid'] = $goods['ticketid'] ? : 0;
        $data['threestatus'] = $goods['threestatus'] ? : 0;
        if($data['pftid'] > 0){
            $pftotherinfo = unserialize($goods['pftotherinfo']);
            if(empty($data['threestatus'])){
                $data['UUtourist_info'] = $pftotherinfo['UUtourist_info'];
                $data['UUdelaytype'] = $pftotherinfo['UUdelaytype'] ? 0 : 1;
                if($data['UUdelaytype'] > 0){
                    $data['UUorder_start'] = date('Y-m-d', time());
                    $end = strtotime($pftotherinfo['UUorder_end']);
                    if($end > 0){
                        $data['UUorder_end'] = date('Y-m-d',$end);
                    }else{
                        $data['UUorder_end'] = date('Y-m-d',$goods['endtime']);
                    }
                }
            }else if($data['threestatus'] == 1){
                $data['template'] = json_decode($pftotherinfo['template']);
            }
        }
        $this->renderSuccess('订单确认页面' , $data);
    }
    /**
     * Comment: 积分抵扣函数
     * Author: wlf
     * Date: 2019/09/11 14:29
     */
    public function creditDeduction($creditmoney , $num , $remark)
    {
        global $_W , $_GPC;
        $onecreditmoney = 1 / $_W['wlsetting']['creditset']['proportion'];
        $allcredit      = sprintf("%.2f" , $_W['wlmember']['credit1']);
        $dkmoney        = sprintf("%.2f" , $creditmoney * $num);
        $dkcredit       = sprintf("%.2f" , $dkmoney / $onecreditmoney);
        if ($dkcredit > $allcredit) {
            $dkcredit = $allcredit;
            $dkmoney  = sprintf("%.2f" , $onecreditmoney * $dkcredit);
        }
        if($dkcredit > 0){
            Member::credit_update_credit1($_W['mid'] , -$dkcredit , $remark);
        }else{
            $dkcredit = 0;
            $dkmoney = 0;
        }
        return ['dkcredit' => $dkcredit , 'dkmoney' => $dkmoney];
    }
    /**
     * Comment: 生成订单接口
     * Author: wlf
     * Date: 2019/8/16 14:55
     */
    public function createOrder()
    {
        global $_W , $_GPC;
        $id           = $_GPC['id'];   //商品id
        $num          = $_GPC['num'] ? : 1;  //商品数量
        $usestatus    = $_GPC['usestatus'];  //使用方式
        $plugin       = $_GPC['plugin'];  //商品插件
        $optionid     = $_GPC['specid'];  //规格id
        $creditstatus = $_GPC['creditstatus']; //积分抵扣
        $username     = trim($_GPC['thname']);  //提货人姓名
        $mobile       = trim($_GPC['thmobile']);  //提货人电话
        $addressid    = $_GPC['addressid'];  //获取地址信息
        $remark       = trim($_GPC['remark']);  //买家备注
        $buystatus    = $_GPC['buystatus'];  //拼团特殊值，2单购 1团购
        $groupid      = intval($_GPC['groupid']); //拼团特殊值，团id
        $cardId       = intval($_GPC['vip_card_id']); //同时开启一卡通
        $redpackid    = intval($_GPC['redpackid']); //使用的红包
        $drawid       = intval($_GPC['drawid']) ? : 0; //领取抽奖奖品
        $luckydrawid  = intval($_GPC['luckydrawid']) ? : 0; //领取锦鲤抽奖奖品
        $callid       = intval($_GPC['callid']) ? : 0; //领取分享有礼奖品

        $diyformid    = intval($_GPC['diyformid']) ? : 0; //自定义表单id
        $settings = Setting::wlsetting_read('orderset'); //获取设置参数
        if (empty($settings['cancel'])) {
            $settings['cancel'] = 10;
        }
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array($plugin , $mastmobile)) {
            $this->renderError('未绑定手机号');
        }
        if ((empty($addressid) || $addressid == 'undefined') && !empty($usestatus)) {
            $this->renderError('请设置收货地址');
        }
        //校验手机号
        if($addressid > 0){
            $tel =  pdo_getcolumn(PDO_NAME.'address',array('id'=>$addressid),'tel');
            if(!preg_match("/^1[345789]\d{9}$/",$tel)){
                $this->renderError('收货地址手机号格式有误，请检查');
            }
        }

        if(!empty($mobile)){
            if(!preg_match("/^1[345789]\d{9}$/",$mobile)){
                $this->renderError('提货人手机号格式有误，请检查');
            }
        }

        //一卡通信息
        if (!empty($cardId)) {
            $buycard = pdo_get(PDO_NAME . 'halfcard_type' , ['id' => $cardId] , ['price','levelid']);
        }
        $userhalfcard = WeliamWeChat::VipVerification($_W['mid']);
        $halfcardflag = $userhalfcard['id'];
        if($halfcardflag > 0){
            $uhlevel = $userhalfcard['levelid'];
        }else if(!empty($cardId)){
            $uhlevel = $buycard['levelid'];
        }
        $nodis = 0;
        //红包优惠
        if(p('redpack') && $redpackid > 0){
            $redpack = pdo_fetch("SELECT b.cut_money FROM".tablename(PDO_NAME . "redpack_records")
                ." as a LEFT JOIN " . tablename(PDO_NAME . "redpacks")
                ." as b ON a.packid = b.id WHERE a.id = {$redpackid}");
            $redpackmoney = $redpack['cut_money'];
        }else{
            $redpackmoney = 0;
        }
        //额外表单
        $diyFormInfo = [];
        if($diyformid > 0){
            //额外表单
            $diyFormInfo = array_values(json_decode(html_entity_decode($_GPC['datas']),true));
//            $diyFormSet  = pdo_getcolumn(PDO_NAME."diyform",['id'=>$diyformid],'info');
//            $diyFormSet  = array_values(json_decode(base64_decode($diyFormSet), true)['list']);//页面的配置信息
//            foreach($diyFormInfo as $formKey => &$formVal){
//                $formVal['title'] = $diyFormSet[$formKey]['data']['title'];
//            }
        }
        $pftid = $_GPC['pftid'];
        if($pftid > 0){
            $pftInfo = json_decode(base64_decode($_GPC['pftdatas']) , true);
            if(empty($pftInfo['ordername'])){
                $pftInfo['ordername'] = 'mid_'.$_W['mid'];
            }
        }else{
            $pftInfo = '';
        }
        //亿奇达
        $template = $_GPC['template'];
        if(!empty($template)){
            $template = explode(',',$template);
            foreach ($template as &$tem){
                $tem = urlencode($tem);
            }
            $pftInfo = $template;
        }

        if ($plugin == 'rush') {
            MysqlFunction::setTrans(4);
            MysqlFunction::startTrans();
            $activity = Rush::getSingleActive($id , '*');
            //判断活动状态
            if ($activity['status'] != 2) {
                if ($activity['status'] == 1) {
                    MysqlFunction::rollback();
                    $this->renderError('活动未开始');
                }
                else if ($activity['status'] == 3) {
                    MysqlFunction::rollback();
                    $this->renderError('活动已结束');
                }
                else if ($activity['status'] == 4) {
                    MysqlFunction::rollback();
                    $this->renderError('商品已下架');
                }
                else if ($activity['status'] == 7) {
                    MysqlFunction::rollback();
                    $this->renderError('商品已抢完');
                }else if ($activity['status'] == 8) {
                    MysqlFunction::rollback();
                    $this->renderError('商品已删除');
                }else{
                    MysqlFunction::rollback();
                    $this->renderError('商品未在售卖中');
                }
            }
            if($activity['usedatestatus'] > 0){
                $check = WeliamWeChat::checkUseDateStatus($activity['usedatestatus'],$activity['week'],$activity['day']);
                if(empty($check)){
                    $this->renderError('今日商品未在售卖中');
                }
            }
            if($activity['daylimit'] > 0){
                $today = strtotime(date('Y-m-d'));
                $daysalenum = WeliamWeChat::getSalesNum(1,$id,0,1,$_W['mid'],$today);
                $sup = $activity['daylimit'] - intval($daysalenum);
                if($num > $sup){
                    $this->renderError('您今日还可以购买'.$sup.'份');
                }
            }
            if($activity['monthlimit'] > 0){
                $tomonth = strtotime(date('Y-m'));
                $monthsalenum = WeliamWeChat::getSalesNum(1,$id,0,1,$_W['mid'],$tomonth);
                $sup = $activity['monthlimit'] - intval($monthsalenum);
                if($num > $sup){
                    $this->renderError('您这个月还可以购买'.$sup.'份');
                }
            }
            if($activity['alldaylimit'] > 0){
                $today = strtotime(date('Y-m-d'));
                $alldaysalenum = WeliamWeChat::getSalesNum(1,$id,0,1,0,$today);
                $sup = $activity['alldaylimit'] - intval($alldaysalenum);
                if($num > $sup){
                    $this->renderError('商品今日份额还剩'.$sup.'份');
                }
            }

            //判断规格
            if ($activity['optionstatus']) {
                if ($optionid) {
                    $option               = pdo_get('wlmerchant_goods_option' , ['id' => $optionid] , [
                        'stock' ,
                        'price' ,
                        'title' ,
                        'viparray',
                        'disarray'
                    ]);
                    $activity['price']    = $option['price'];
                    $total = $option['stock'];
                    $activity['viparray'] = WeliamWeChat::mergeVipArray($option['viparray'],$activity['viparray']);
                }
                else {
                    MysqlFunction::rollback();
                    $this->renderError('规格参数错误，请重新选择');
                }
            }
            else {
                $optionid = 0;
                $total = $activity['num'];
            }
            #判断是否开启阶梯价（目前仅抢购存在）  是否开启阶梯价(0=关闭 1=开启)
            if ($activity['lp_status'] == 1) {
                $activity['lp_set'] = is_array(unserialize($activity['lp_set'])) ? unserialize($activity['lp_set']) : [];
                if (count($activity['lp_set']) > 0) {
                    $info       = WeliamWeChat::getHomeGoods(1 , $id);
                    $allsalenum = intval($info['allsalenum']);
                    foreach ($activity['lp_set'] as $lpKey => $lpVal) {
                        $lpVal['max'] = intval($lpVal['max']) + $allsalenum;
                        if ($info['buy_num'] < $lpVal['max']) {
                            $activity['price']    = $lpVal['price'];
                            $nowStk               = $lpVal['max'] - $info['buy_num'];//当前区间上限 - 已售数量 = 当前区间库存
                            break;
                        }
                    }
                    //判断是否超出当前区间上限
                    if ($nowStk < 0) {
                        MysqlFunction::rollback();
                        $this->renderError("当前区间还可购买{$nowStk}件，您已超出" . ($info['buy_num'] - $lpVal['max']) . "件");
                    }
                }
            }
            //判断剩余数量
            $salesVolume = WeliamWeChat::getSalesNum(1,$id,$optionid,1);
            $levelnum = $total - intval($salesVolume);
            if($levelnum < $num){
                MysqlFunction::rollback();
                $this->renderError('商品库存不足');
            }
            if ($levelnum < 1) {
                MysqlFunction::rollback();
                $this->renderError('该商品已被抢完。');
            }
            /*判断会员*/
            $price = $activity['price'];
            $level = unserialize($activity['level']);
            if ($activity['vipstatus'] == 1) {
                if ($halfcardflag || !empty($cardId)) {
                    $vipdiscount = WeliamWeChat::getVipDiscount($activity['viparray'],$uhlevel);
                    if($vipdiscount>0){
                        $vipbuyflag = 1;
                    }
                }
            }
            else if ($activity['vipstatus'] == 2) {
                if (empty($halfcardflag) && empty($cardId)) {
                    MysqlFunction::rollback();
                    $this->renderError('该商品会员特供，请先成为会员');
                }
                else if ($level) {
                    //判断等级
                    $flag = Halfcard::checklevel($_W['mid'] , $level);
                    if (empty($flag)) {
                        MysqlFunction::rollback();
                        $this->renderError('您所在的会员等级无权购买该商品');
                    }
                }
            }
            if (empty($vipbuyflag)) {
                $vipbuyflag = 0;
            }
            /*判断已购买数量*/
            if ($activity['op_one_limit']) {
                $alreadyBuyNum = WeliamWeChat::getSalesNum(1,$id,0,1,$_W['mid']);
                $levelnum      = $activity['op_one_limit'] - intval($alreadyBuyNum);
                if ($levelnum < 0) {
                    $levelnum = 0;
                }
                if (!$levelnum) {
                    MysqlFunction::rollback();
                    $this->renderError('限购商品!您已全部购买');
                }
                else if ($num > $levelnum) {
                    MysqlFunction::rollback();
                    $this->renderError('限购商品!您还能购买' . $levelnum . $activity['unit']);
                }
            }
            //积分抵扣
            if ($creditstatus) {
                if ($activity['creditmoney'] > $price) {
                    $activity['creditmoney'] = $price;
                }
                $creditremark = '抢购[' . $activity['name'] . ']抵扣积分';
                $creditindo   = self::creditDeduction($activity['creditmoney'] , $num , $creditremark);
                $dkcredit     = $creditindo['dkcredit'];
                $dkmoney      = $creditindo['dkmoney'];
            }
            else {
                $dkcredit = 0;
                $dkmoney  = 0;
            }
            //结算金额
            if($activity['lp_status']>0){
                if($vipbuyflag>0){
                    $useprice = $activity['price'] - $vipdiscount;
                }else{
                    $useprice = $activity['price'];
                }
            }else{
                $useprice = 0;
            }
            $settlementmoney = Store::getsettlementmoney(1 , $id , $num , $activity['sid'] , $vipbuyflag , $optionid,0,$useprice,$uhlevel);
            if($settlementmoney < 0.01){
                $settlementmoney = Store::getsettlementmoney(1 , $id , $num , $activity['sid'] , $vipbuyflag , $optionid,0,$useprice,$uhlevel);
                if($settlementmoney < 0.01){
                    $settlementmoney = Store::getsettlementmoney(1 , $id , $num , $activity['sid'] , $vipbuyflag , $optionid,0,$useprice,$uhlevel);
                }
            }

            if($vipdiscount > 0){
                $vipdiscount = $vipdiscount * $num;
            }else{
                $vipdiscount = 0;
            }
            //快递订单
            if ($usestatus) {
                $express         = $this->freight($addressid , $num , $activity);
                $expressprice    = $express['price'];
                $expressid       = $express['expressid'];
                $settlementmoney = sprintf("%.2f" , $settlementmoney + $expressprice);
                $neworderflag    = 0;
            }else {
                $expressprice = 0;
                $neworderflag = 1;
            }
            //创建订单
            $prices = sprintf("%.2f" , $price * $num);
            //满减活动
            if($activity['fullreduceid']>0){
                $fulldkmoney = Fullreduce::getFullreduceMoney(sprintf("%.2f" , $prices - $vipdiscount),$activity['fullreduceid']);
            }else{
                $fulldkmoney = 0;
            }
            if (!empty($cardId)) {
                $cardprice = pdo_getcolumn(PDO_NAME . 'halfcard_type' , ['id' => $cardId] , 'price');
            }
            else {
                $cardprice = 0;
            }
            if($drawid > 0){
                $flag = pdo_getcolumn(PDO_NAME.'rush_order',array('drawid'=>$drawid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $prices = 0;
            }

            if($luckydrawid > 0){
                $flag = pdo_getcolumn(PDO_NAME.'rush_order',array('luckydrawid'=>$luckydrawid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $prices = 0;
            }

            if($callid > 0){
                $flag = pdo_getcolumn(PDO_NAME.'rush_order',array('callid'=>$callid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $prices = 0;
            }

            $marketstatus = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$activity['sid']),'marketstatus');
            if($marketstatus > 0){
                $settlementmoney = sprintf("%.2f",$settlementmoney - $vipdiscount - $redpackmoney - $dkmoney - $fulldkmoney);
            }
            $actualprice = sprintf("%.2f" , $prices - $vipdiscount - $redpackmoney - $dkmoney - $fulldkmoney + $expressprice + $cardprice);
            $data        = [
                'uniacid'         => $activity['uniacid'] ,
                'unionid'         => $_W['unionid'] ,
                'mid'             => $_W['mid'] ,
                'openid'          => $_W['openid'] ,
                'sid'             => $activity['sid'] ,
                'aid'             => $activity['aid'] ,
                'activityid'      => $activity['id'] ,
                'orderno'         => createUniontid() ,
                'status'          => 0 ,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
                'createtime'      => TIMESTAMP ,
                'price'           => $activity['price'] * $num,//$prices
                'actualprice'     => $actualprice > 0 ? $actualprice : 0,
                'num'             => $num ,
                'username'        => $username ,
                'mobile'          => $mobile ,
                'vipbuyflag'      => $vipbuyflag ,
                'optionid'        => $optionid ,
                'dkcredit'        => $dkcredit ,
                'dkmoney'         => $dkmoney ,
                'expressid'       => $expressid ,
                'remark'          => $remark ,
                'settlementmoney' => $settlementmoney ,
                'neworderflag'    => $neworderflag ,
                'vip_card_id'     => $cardId ,//会员卡的id
                'canceltime'      => time() + $settings['cancel'] * 60,
                'redpackid'       => $redpackid,
                'redpackmoney'    => $redpackmoney,
                'fullreduceid'    => $activity['fullreduceid'],
                'fullreducemoney' => $fulldkmoney,
                'drawid'          => $drawid,
                'luckydrawid'     => $luckydrawid,
                'callid'          => $callid,
                'discount'        => $vipdiscount,
                'moinfo'          => serialize($diyFormInfo),
                'pftinfo'         => serialize($pftInfo)
            ];
            pdo_insert(PDO_NAME . 'rush_order' , $data);
            $orderid = pdo_insertid();
            if (empty($orderid)) {
                MysqlFunction::rollback();
                $this->renderError('创建订单失败，请刷新重试');
            }
            if($redpackid){
                pdo_update('wlmerchant_redpack_records',array('status' => 1,'usetime' =>time(),'orderid' => $orderid,'plugin' => $plugin),array('id' => $redpackid));
            }
            //修改商品数量
            $buynum = WeliamWeChat::getSalesNum(1,$id,0,1);
            $total = $activity['num'];
            $total = $total - intval($buynum);
            if($total == 0){
                Rush::updateActive(['status'=>7] , ['id' => $data['activityid']]);//修改商品状态为已售罄
            }
            MysqlFunction::commit();
            if ($data['actualprice'] > 0) {
                $unidata['status']  = 1;
                $unidata['orderid'] = $orderid;
                $this->renderSuccess('下单成功' , $unidata);
            }else if ($data['actualprice'] == 0) {
                //0元购   购买商品的同时开通会员卡是不允许进行0元购的 必须进入支付流程
                $newdata = [
                    'status'   => 1 ,
                    'paytime'  => time() ,
                    'usetimes' => $num ,
                    'paytype'  => 6 ,
                ];

                if ($expressid) {
                    $newdata['status'] = 8;
                } else {
                    Order::createSmallorder($orderid , 1);
                    //计算过期时间
                    if ($activity['cutoffstatus']) {
                        $newdata['estimatetime'] = time() + $activity['cutoffday'] * 86400;
                    }
                    else {
                        $newdata['estimatetime'] = $activity['cutofftime'];
                    }
                    $newdata['remindtime'] = Order::remindTime($newdata['estimatetime']);
                }
                //处理分销
                if($data['dkmoney']>0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                    $nodis = 1;
                }
                if (p('distribution') && empty($activity['isdistri']) && empty($data['drawid']) && empty($data['luckydrawid']) && empty($data['callid']) && empty($nodis)) {
                    if ($optionid > 0) {
                        $activity['disarray'] = WeliamWeChat::mergeDisArray($option['disarray'],$activity['disarray']);
                    }
                    $disarray = unserialize($activity['disarray']);
                    $disprice = sprintf("%.2f",$data['price'] - $data['discount']);
                    $disorderid = Distribution::disCore($data['mid'], $disprice, $disarray, $data['num'], 0,$orderid, 'rush', $activity['dissettime'],$activity['isdistristatus']);
                    $newdata['disorderid'] = $disorderid;
                }
                pdo_update(PDO_NAME . 'rush_order' , $newdata , ['orderno' => $data['orderno']]); //更新订单状态
                //抽奖状态
                if($data['drawid'] > 0){
                    pdo_update('wlmerchant_draw_record',array('is_get' => 2),array('id' => $data['drawid']));
                }
                if($data['luckydrawid'] > 0){
                    pdo_update('wlmerchant_luckydraw_drawcode',array('is_get' => 1,'gettime' => time()),array('id' => $data['luckydrawid']));
                }
                if($data['callid'] > 0){
                    pdo_update('wlmerchant_call_receive',array('orderid' => $orderid),array('id' => $data['callid']));
                }
                //处理营销
                if ($activity['integral']) {
                    $remark = '抢购:[' . $activity['name'] . ']赠送积分';
                    Member::credit_update_credit1($_W['mid'] , $activity['integral'] * $num , $remark);
                }
                /***模板通知***/
                Store::addFans($activity['sid'] , $_W['mid']);
                News::addSysNotice($data['uniacid'],1,$data['sid'],0,$orderid);
                News::paySuccess($orderid, 'rush');
                //小票打印
                Order::sendPrinting($orderid,'rush');

                $unidata['status']  = 0;
                $unidata['orderid'] = $orderid;
                $unidata['tid']     = $data['orderno'];
                $unidata['plugin']  = 'rush';
                $this->renderSuccess('购买成功' , $unidata);
            }
        }
        //团购
        if ($plugin == 'groupon') {
            $activity = Groupon::getSingleActive($id , '*');
            if ($activity['status'] != 2) {
                if ($activity['status'] == 1) {
                    $this->renderError('活动未开始');
                }
                else if ($activity['status'] == 3) {
                    $this->renderError('活动已结束');
                }
                else if ($activity['status'] == 4) {
                    $this->renderError('商品已下架');
                }
                else if ($activity['status'] == 7) {
                    $this->renderError('商品已抢完');
                }
                else {
                    $this->renderError('商品已停售');
                }
            }

            if($activity['usedatestatus'] > 0){
                $check = WeliamWeChat::checkUseDateStatus($activity['usedatestatus'],$activity['week'],$activity['day']);
                if(empty($check)){
                    $this->renderError('今日商品未在售卖中');
                }
            }
            if($activity['alldaylimit'] > 0){
                $today = strtotime(date('Y-m-d'));
                $daysalenum = WeliamWeChat::getSalesNum(2,$id,0,1,0,$today);
                $sup = $activity['alldaylimit'] - intval($daysalenum);
                if($num > $sup){
                    $this->renderError('商品今日份额剩余'.$sup.'份');
                }
            }
            if($activity['daylimit'] > 0){
                $today = strtotime(date('Y-m-d'));
                $daysalenum = WeliamWeChat::getSalesNum(2,$id,0,1,$_W['mid'],$today);
                $sup = $activity['daylimit'] - intval($daysalenum);
                if($num > $sup){
                    $this->renderError('您的今日还能购买'.$sup.'份');
                }
            }


            //判断规格
            if ($activity['optionstatus']) {
                if ($optionid) {
                    $option               = pdo_get('wlmerchant_goods_option' , ['id' => $optionid] , [
                        'stock' ,
                        'price' ,
                        'title' ,
                        'viparray'
                    ]);
                    $activity['price']    = $option['price'];
                    $total = $option['stock'];
                    $activity['viparray'] = WeliamWeChat::mergeVipArray($option['viparray'],$activity['viparray']);
                }
                else {
                    $this->renderError('规格参数错误，请重新选择');
                }
            }
            else {
                $optionid = 0;
            }
            /*判断会员*/
            $price = $activity['price'];
            $level = unserialize($activity['level']);
            if ($activity['vipstatus'] == 1) {
                if ($halfcardflag || $cardId) {
                    $vipdiscount = WeliamWeChat::getVipDiscount($activity['viparray'],$uhlevel);
                    if($vipdiscount>0){
                        $vipbuyflag = 1;
                    }
                }
            }
            else if ($activity['vipstatus'] == 2) {
                if (empty($halfcardflag) && empty($cardId)) {
                    $this->renderError('该商品会员特供，请先成为会员');
                }
                else if ($level && empty($cardId)) {
                    //判断等级
                    $flag = Halfcard::checklevel($_W['mid'] , $level);
                    if (empty($flag)) {
                        $this->renderError('您所在的会员等级无权购买该商品');
                    }
                }
                else if (!empty($cardId)) {
                    //购买商品的同时开通会员卡
                    $levelId = pdo_getcolumn(PDO_NAME . "halfcard_type" , ['id' => $cardId] , 'levelid');
                    if (!in_array($levelId , $level)) {
                        $this->renderError('您所开通的会员卡无权购买该商品');
                    }
                }
            }
            if (empty($vipbuyflag)) {
                $vipbuyflag = 0;
            }
            /*判断已购买数量*/
            if ($activity['op_one_limit']) {
                $alreadyBuyNum = WeliamWeChat::getSalesNum(2,$id,0,1,$_W['mid']);
                if (empty($alreadyBuyNum)) {
                    $alreadyBuyNum = 0;
                }
                $levelnum = $activity['op_one_limit'] - $alreadyBuyNum;
                if (!$levelnum) {
                    $this->renderError('限购商品!您已全部购买');
                }
                else if ($num > $levelnum) {
                    if ($levelnum < 0) {
                        $levelnum = 0;
                    }
                    $this->renderError("限购商品!您还能购买" . $levelnum . $activity['unit']);
                }
            }
            //判断库存
            if($optionid > 0){
                $salenum              = WeliamWeChat::getSalesNum(2,$id,$optionid,1,0);
                $activity['levelnum'] = sprintf("%.0f" ,$total - intval($salenum));
                if ($activity['levelnum'] < 1) {
                    $this->renderError('此规格已售罄');
                }
            }else{
                $salenum              = WeliamWeChat::getSalesNum(2,$id,0,1,0);
                $activity['levelnum'] = sprintf("%.0f" , $activity['num'] - intval($salenum));
                if ($activity['levelnum'] < 1) {
                    $this->renderError('此商品已售罄');
                }
            }
            if ($num > $activity['levelnum']) {
                $this->renderError('库存不足,请减少购买数量');
            }


            //积分抵扣
            if ($creditstatus) {
                if ($activity['creditmoney'] > $price) {
                    $activity['creditmoney'] = $price;
                }
                $creditremark = '团购[' . $activity['name'] . ']抵扣积分';
                $creditindo   = self::creditDeduction($activity['creditmoney'] , $num , $creditremark);
                $dkcredit     = $creditindo['dkcredit'];
                $dkmoney      = $creditindo['dkmoney'];
            }
            else {
                $dkcredit = 0;
                $dkmoney  = 0;
            }
            //结算金额
            $settlementmoney = Store::getsettlementmoney(3 , $id , $num , $activity['sid'] , $vipbuyflag , $optionid,0,0,$uhlevel);
            if($settlementmoney < 0.01){
                $settlementmoney = Store::getsettlementmoney(3 , $id , $num , $activity['sid'] , $vipbuyflag , $optionid,0,0,$uhlevel);
            }
            //快递订单
            if ($usestatus) {
                $express         = $this->freight($addressid , $num , $activity);
                $expressprice    = $express['price'];
                $expressid       = $express['expressid'];
                $settlementmoney = sprintf("%.2f" , $settlementmoney + $expressprice);
                $neworderflag    = 0;
            }
            else {
                $username     = trim($_GPC['thname']);
                $mobile       = trim($_GPC['thmobile']);
                $expressprice = 0;
                $neworderflag = 1;
            }
            //创建订单
            if($vipdiscount > 0){
                $vipdiscount = $vipdiscount * $num;
            }else{
                $vipdiscount = 0;
            }
            if (!empty($cardId)) {
                $cardprice = pdo_getcolumn(PDO_NAME . 'halfcard_type' , ['id' => $cardId] , 'price');
            }
            else {
                $cardprice = 0;
            }
            //满减活动
            if($activity['fullreduceid']>0){
                $fulldkmoney = Fullreduce::getFullreduceMoney(sprintf("%.2f" , $price * $num - $vipdiscount),$activity['fullreduceid']);
            }else{
                $fulldkmoney = 0;
            }
            if($drawid > 0){
                $flag = pdo_getcolumn(PDO_NAME.'order',array('drawid'=>$drawid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $price = 0;
            }
            if($luckydrawid > 0){
                $flag = pdo_getcolumn(PDO_NAME.'order',array('luckydrawid'=>$luckydrawid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $price = 0;
            }
            if($callid > 0){
                $flag = pdo_getcolumn(PDO_NAME.'order',array('callid'=>$callid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $price = 0;
            }
            $prices     = sprintf("%.2f" , $price * $num - $vipdiscount - $redpackmoney + $expressprice - $dkmoney + $cardprice - $fulldkmoney);
            $goodsprice = sprintf("%.2f" , $activity['price'] * $num);
            $data = [
                'uniacid'         => $activity['uniacid'] ,
                'mid'             => $_W['mid'] ,
                'sid'             => $activity['sid'] ,
                'aid'             => $activity['aid'] ,
                'fkid'            => $activity['id'] ,
                'plugin'          => 'groupon' ,
                'payfor'          => 'grouponOrder' ,
                'orderno'         => createUniontid() ,
                'status'          => 0 ,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
                'createtime'      => TIMESTAMP ,
                'price'           => $prices > 0 ? $prices : 0,
                'num'             => $num ,
                'vipbuyflag'      => $vipbuyflag ,
                'specid'          => $optionid ,
                'name'            => $username ,
                'mobile'          => $mobile ,
                'goodsprice'      => $goodsprice ,
                'expressid'       => $expressid ,
                'buyremark'       => $remark ,
                'settlementmoney' => $settlementmoney ,
                'vip_card_id'     => $cardId ,//会员卡的id
                'neworderflag'    => $neworderflag ,
                'usecredit'       => $dkcredit ,
                'cerditmoney'     => $dkmoney ,
                'canceltime'      => time() + $settings['cancel'] * 60,
                'redpackid'       => $redpackid,
                'redpackmoney'    => $redpackmoney,
                'fullreduceid'    => $activity['fullreduceid'],
                'fullreducemoney' => $fulldkmoney,
                'drawid'          => $drawid,
                'luckydrawid'     => $luckydrawid,
                'callid'          => $callid,
                'vipdiscount'     => $vipdiscount,
                'moinfo'          => serialize($diyFormInfo),
                'pftinfo'         => serialize($pftInfo)

            ];
            pdo_insert(PDO_NAME . 'order' , $data);
            $orderid = pdo_insertid();
            if($redpackid){
                pdo_update('wlmerchant_redpack_records',array('status' => 1,'usetime' =>time(),'orderid' => $orderid,'plugin' => $plugin),array('id' => $redpackid));
            }
            //修改商品数量
            $salenum = WeliamWeChat::getSalesNum(2,$id,0,1,0);
            $total = $activity['num'];
            $total = $total - intval($salenum);
            if($total <= 0) Groupon::updateActive(['status'=>7],['id' => $data['fkid']]);//修改商品状态为已售罄
            if ($data['price'] > 0) {
                $unidata['status']  = 1;
                $unidata['orderid'] = $orderid;
                $this->renderSuccess('购买成功' , $unidata);
            }
            else {
                //0元购   购买商品的同时开通会员卡是不允许进行0元购的 必须进入支付流程
                $newdata = [
                    'status'  => 1 ,
                    'paytime' => time() ,
                    'paytype' => 6 ,
                ];


                if ($expressid) {
                    $newdata['status'] = 8;
                }
                else {
                    Order::createSmallorder($orderid , 2);
                    //计算过期时间
                    if ($activity['cutoffstatus']) {
                        $newdata['estimatetime'] = time() + $activity['cutoffday'] * 86400;
                    }
                    else {
                        $newdata['estimatetime'] = $activity['cutofftime'];
                    }
                    $newdata['remindtime'] = Order::remindTime($newdata['estimatetime']);
                }

                //处理分销
                if($dkmoney>0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                    $nodis = 1;
                }
                if (p('distribution') && empty($activity['isdistri']) && empty($data['drawid']) && empty($nodis)) {
                    if ($data['specid']) {
                        $option = pdo_get('wlmerchant_goods_option', array('id' => $data['specid']), array('onedismoney', 'twodismoney', 'threedismoney'));
                        $activity['disarray'] = WeliamWeChat::mergeDisArray($option['disarray'],$activity['disarray']);
                    }
                    $disarray = unserialize($activity['disarray']);
                    $disprice = sprintf("%.2f",$data['goodsprice'] - $data['vipdiscount']);
                    $disorderid = Distribution::disCore($data['mid'], $disprice, $disarray, $data['num'], 0, $orderid, 'groupon', $activity['dissettime'],$activity['isdistristatus']);
                    $newdata['disorderid'] = $disorderid;
                }

                pdo_update(PDO_NAME . 'order' , $newdata , ['orderno' => $data['orderno']]); //更新订单状态
                //抽奖状态
                if($data['drawid'] > 0){
                    pdo_update('wlmerchant_draw_record',array('is_get' => 2),array('id' => $data['drawid']));
                }
                if($data['luckydrawid'] > 0){
                    pdo_update('wlmerchant_luckydraw_drawcode',array('is_get' => 1,'gettime' => time()),array('id' => $data['luckydrawid']));
                }
                if($data['callid'] > 0){
                    pdo_update('wlmerchant_call_receive',array('orderid' => $orderid),array('id' => $data['callid']));
                }
                //处理营销
                if ($activity['integral']) {
                    $remark = '团购:[' . $activity['name'] . ']赠送积分';
                    Member::credit_update_credit1($_W['mid'] , $activity['integral'] * $num , $remark);
                }
                /***模板通知***/
                Store::addFans($activity['sid'] , $_W['mid']);
                News::addSysNotice($data['uniacid'],2,$data['sid'],0,$orderid);
                News::paySuccess($orderid, 'groupon');
                //小票打印
                Order::sendPrinting($orderid,'groupon');

                $unidata['status']  = 0;
                $unidata['orderid'] = $orderid;
                $unidata['tid']     = $data['orderno'];
                $unidata['plugin']  = 'groupon';
                $this->renderSuccess('购买成功' , $unidata);
            }
        }
        //卡券
        if ($plugin == 'coupon') {
            $coupons = wlCoupon::getSingleCoupons($id , '*');
            $coupons['surplus'] = WeliamWeChat::getSalesNum(4,$id,0,1,0);
            //判断当前卡卷是否为会员特供
            if ($coupons['status'] != 2) {
                $this->renderError('卡券出售已结束或未开始，无法购买');
            }
            if($coupons['usedatestatus'] > 0){
                $check = WeliamWeChat::checkUseDateStatus($coupons['usedatestatus'],$coupons['week'],$coupons['day']);
                if(empty($check)){
                    $this->renderError('今日卡券未在售卖中');
                }
            }
            if($coupons['alldaylimit'] > 0){
                $today = strtotime(date('Y-m-d'));
                $alldaysalenum = WeliamWeChat::getSalesNum(4,$id,0,1,0,$today);
                $sup = $coupons['alldaylimit'] - intval($alldaysalenum);
                if($num > $sup){
                    $this->renderError('商品今日份额仅剩'.$sup.'份');
                }
            }
            if($coupons['daylimit'] > 0){
                $today = strtotime(date('Y-m-d'));
                $daysalenum = WeliamWeChat::getSalesNum(4,$id,0,1,$_W['mid'],$today);
                $sup = $coupons['daylimit'] - intval($daysalenum);
                if($num > $sup){
                    $this->renderError('您今日还能买'.$sup.'份');
                }
            }

            $alnum  = WeliamWeChat::getSalesNum(4,$id,0,1,$_W['mid']);
            $allnum = $alnum + $num;
            if ($coupons['time_type'] == 1 && $coupons['endtime'] < time()) {
                $this->renderError('抱歉，超级券已停止发放');
            }
            if (($coupons['quantity'] - intval($coupons['surplus'])) < $num) {
                $this->renderError('抱歉，超级券库存不足');
            }
            if ($allnum > $coupons['get_limit'] && $coupons['get_limit'] > 0) {
                $this->renderError('抱歉，一个用户只能获取' . $coupons['get_limit'] . '张,您已下单' . $alnum . '张。');
            }
            if ($coupons['vipstatus'] == 1 && ($halfcardflag || $cardId)) {
                $vipdiscount = WeliamWeChat::getVipDiscount($coupons['viparray'],$uhlevel);
                if($vipdiscount > 0){
                    $vipbuyflag = 1;
                }
            }
            else if ($coupons['vipstatus'] == 2) {
                $goodsLv = unserialize($coupons['level']);
                if ($halfcardflag <= 0) {
                    $this->renderError('当前商品为会员限定，请先成为会员!');
                }
                else if (count($goodsLv) > 0) {
                    $lv = pdo_getcolumn(PDO_NAME . "halfcardmember" , ['id' => $halfcardflag] , 'levelid');
                    if (!in_array($lv , $goodsLv)) {
                        $this->renderError('您所在的会员等级无权购买该商品！');
                    }
                }
            }
            if (empty($vipbuyflag)) {
                $vipbuyflag = 0;
            }
            //免费卡券
            if (empty($coupons['is_charge'])) {
                $coupons['price'] = 0;
                $settlementmoney  = 0;
            }else {
                //结算金额
                $settlementmoney = Store::getsettlementmoney(4 , $id , $num , $coupons['merchantid'] , $vipbuyflag,0,0,0,$uhlevel);
                if($settlementmoney < 0.01){
                    $settlementmoney = Store::getsettlementmoney(4 , $id , $num , $coupons['merchantid'] , $vipbuyflag,0,0,0,$uhlevel);
                }
            }
            if($vipdiscount > 0){
                $vipdiscount = $vipdiscount * $num;
            }else{
                $vipdiscount = 0;
            }
            if (!empty($cardId)) {
                $cardprice = pdo_getcolumn(PDO_NAME . 'halfcard_type' , ['id' => $cardId] , 'price');
            }
            else {
                $cardprice = 0;
            }
            $price = $coupons['price'] * $num;
            #6、积分抵扣
            if ($creditstatus) {
                if ($coupons['creditmoney'] > $price) {
                    $coupons['creditmoney'] = $price;
                }
                $creditremark = '超级券[' . $coupons['title'] . ']抵扣积分';
                $creditindo   = self::creditDeduction($coupons['creditmoney'] , $num , $creditremark);
                $dkcredit     = $creditindo['dkcredit'];
                $dkmoney      = $creditindo['dkmoney'];
            }
            else {
                $dkcredit = 0;
                $dkmoney  = 0;
            }

            //满减活动
            if($coupons['fullreduceid']>0){
                $fulldkmoney = Fullreduce::getFullreduceMoney(sprintf("%.2f" ,$price - $vipdiscount),$coupons['fullreduceid']);
            }else{
                $fulldkmoney = 0;
            }
            if($drawid > 0){
                $flag = pdo_getcolumn(PDO_NAME.'order',array('drawid'=>$drawid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $price = 0;
            }
            if($luckydrawid > 0){
                $flag = pdo_getcolumn(PDO_NAME.'order',array('luckydrawid'=>$luckydrawid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $price = 0;
            }
            if($callid > 0){
                $flag = pdo_getcolumn(PDO_NAME.'order',array('callid'=>$callid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $price = 0;
            }
            $prices =  sprintf("%.2f" , $price + $cardprice - $vipdiscount - $redpackmoney - $fulldkmoney - $dkmoney);
            $data    = [
                'uniacid'         => $coupons['uniacid'] ,
                'mid'             => $_W['mid'] ,
                'aid'             => $coupons['aid'] ,
                'fkid'            => $id ,
                'sid'             => $coupons['merchantid'] ,
                'status'          => 0 ,
                'paytype'         => 2 ,
                'createtime'      => time() ,
                'orderno'         => createUniontid() ,
                'price'           => $prices > 0 ? $prices : 0,
                'num'             => $num ,
                'plugin'          => 'coupon' ,
                'payfor'          => 'couponsharge' ,
                'vipbuyflag'      => $vipbuyflag ,
                'goodsprice'      => $coupons['price'] * $num ,
                'settlementmoney' => $settlementmoney ,
                'neworderflag'    => 1 ,
                'buyremark'       => $remark ,
                'canceltime'      => time() + $settings['cancel'] * 60 ,
                'vip_card_id'     => $cardId,
                'redpackid'       => $redpackid,
                'redpackmoney'    => $redpackmoney,
                'fullreduceid'    => $coupons['fullreduceid'],
                'fullreducemoney' => $fulldkmoney,
                'drawid'          => $drawid,
                'luckydrawid'     => $luckydrawid,
                'callid'          => $callid,
                'vipdiscount'     => $vipdiscount,
                'usecredit'       => $dkcredit ,
                'cerditmoney'     => $dkmoney,
                'mobile'          => $mobile,
                'moinfo'          => serialize($diyFormInfo),
            ];
            $orderid = wlCoupon::saveCouponOrder($data);
            if($redpackid){
                pdo_update('wlmerchant_redpack_records',array('status' => 1,'usetime' =>time(),'orderid' => $orderid,'plugin' => $plugin),array('id' => $redpackid));
            }
            if ($data['price'] > 0) {
                $unidata['status']  = 1;
                $unidata['orderid'] = $orderid;
                $this->renderSuccess('下单成功' , $unidata);
            }
            else {
                //领取免费卡券
                $newdata = [
                    'status'  => 1 ,
                    'paytime' => time() ,
                    'paytype' => 6 ,
                ];
                //创建小订单
                Order::createSmallorder($orderid , 4);
                //处理分销
                if($dkmoney>0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                    $nodis = 1;
                }
                if (p('distribution') && empty($coupons['isdistri']) && empty($data['drawid']) && empty($data['luckydrawid']) && empty($data['callid']) && empty($nodis) ) {
                    $disarray = unserialize($coupons['disarray']);
                    $dismoney = sprintf("%.2f", $data['goodsprice'] - $data['vipdiscount']);
                    $disorderid = Distribution::disCore($data['mid'], $dismoney, $disarray, $data['num'], 0, $orderid, 'coupon', $coupons['dissettime'],$coupons['isdistristatus']);
                    $newdata['disorderid'] = $disorderid;
                }
                //抽奖领取
                if($data['drawid'] > 0){
                    pdo_update('wlmerchant_draw_record',array('is_get' => 2),array('id' => $data['drawid']));
                }
                if($data['luckydrawid'] > 0){
                    pdo_update('wlmerchant_luckydraw_drawcode',array('is_get' => 1,'gettime' => time()),array('id' => $data['luckydrawid']));
                }
                if($data['callid'] > 0){
                    pdo_update('wlmerchant_call_receive',array('orderid' => $orderid),array('id' => $data['callid']));
                }
                if ($coupons['time_type'] == 1) {
                    $starttime = $coupons['starttime'];
                    $endtime   = $coupons['endtime'];
                }
                else {
                    $starttime = time();
                    $endtime   = time() + ($coupons['deadline'] * 24 * 3600);
                }
                $data2                   = [
                    'mid'         => $data['mid'] ,
                    'aid'         => $data['aid'] ,
                    'parentid'    => $coupons['id'] ,
                    'status'      => 1 ,
                    'type'        => $coupons['type'] ,
                    'title'       => $coupons['title'] ,
                    'sub_title'   => $coupons['sub_title'] ,
                    'content'     => $coupons['goodsdetail'] ,
                    'description' => $coupons['description'] ,
                    'color'       => $coupons['color'] ,
                    'starttime'   => $starttime ,
                    'endtime'     => $endtime ,
                    'createtime'  => time() ,
                    'orderno'     => $data['orderno'] ,
                    'price'       => 0 ,
                    'usetimes'    => $num * $coupons['usetimes'] ,
                ];
                $newdata['recordid']     = wlCoupon::saveMemberCoupons($data2);
                $newdata['estimatetime'] = $data2['endtime'];
                //计算过期提醒时间
                $newdata['remindtime'] = Order::remindTime($newdata['estimatetime']);
                pdo_update(PDO_NAME . 'order' , $newdata , ['id' => $orderid]); //更新订单状态
                /***模板通知***/
                News::addSysNotice($data['uniacid'],2,$data['sid'],0,$orderid);
                Store::addFans($data['sid'], $data['mid']);
                News::paySuccess($orderid,'coupon');

                $unidata['status']   = 0;
                $unidata['orderid']  = $orderid;
                $unidata['recordid'] = $newdata['recordid'];
                $unidata['tid']      = $data['orderno'];
                $unidata['plugin']   = 'coupon';
                $this->renderSuccess('购买成功' , $unidata);
            }
        }
        //拼团
        if ($plugin == 'wlfightgroup') {
            $good = Wlfightgroup::getSingleGood($id , '*');
            #1、判断组团情况
            if ($groupid) {
                $group = pdo_get('wlmerchant_fightgroup_group' , ['id' => $groupid]);
                if ($group['status'] == 2 || $group['lacknum'] == 0) {
                    $this->renderError('该团已经组团成功，请您新开一团。');
                }
                else if ($group['status'] == 3 || $group['failtime'] < time()) {
                    $this->renderError('该团已经组团失败，请您新开一团。');
                }
            }
            #2、判断商品发售时间
            if ($good['islimittime']) {
                if ($good['limitstarttime'] > time()) {
                    $this->renderError('该商品未到发售时间');
                }
                if ($good['limitendtime'] < time()) {
                    $this->renderError('该商品已停止发售');
                }
            }
            #3、判断商品是否下架
            if ($good['status'] != 2) {
                $this->renderError('抱歉，商品未在售卖中');
            }
            //判断时间
            if($good['usedatestatus'] > 0){
                $check = WeliamWeChat::checkUseDateStatus($good['usedatestatus'],$good['week'],$good['day']);
                if(empty($check)){
                    $this->renderError('今日商品未在售卖中');
                }
            }
            if($good['daylimit'] > 0){
                $today = strtotime(date('Y-m-d'));
                $daysalenum = WeliamWeChat::getSalesNum(3,$id,0,1,$_W['mid'],$today);
                $sup = $good['daylimit'] - intval($daysalenum);
                if($num > $sup){
                    $this->renderError('您今日还可以购买'.$sup.'份');
                }
            }
            if($good['alldaylimit'] > 0){
                $today = strtotime(date('Y-m-d'));
                $alldaysalenum = WeliamWeChat::getSalesNum(3,$id,0,1,0,$today);
                $sup = $good['alldaylimit'] - intval($alldaysalenum);
                if($num > $sup){
                    $this->renderError('商品今日份额还剩'.$sup.'份');
                }
            }
            #4、判断购买数量限制
            if ($good['op_one_limit']) {//0未支付 1已支付 2已消费 3已完成 4待收货 待消费 5已取消 6待退款 7已退款  8待发货
                $arbuy = WeliamWeChat::getSalesNum(3,$id,0,1,$_W['mid']);
                if ($arbuy + $num > $good['op_one_limit'] && $arbuy < $good['op_one_limit']) {
                    $morenum = $good['op_one_limit'] - intval($arbuy);
                    $this->renderError('限购商品，您还能购买' . $morenum . '件');
                }
                else if ($arbuy >= $good['op_one_limit']) {
                    $this->renderError('抱歉，您已达到商品购买数量上限');
                }
                else {
                    $good['op_one_limit'] = $good['op_one_limit'] - intval($arbuy);
                }
            }
            #5、判断商品规格 获取对应的价格
            if ($good['specstatus']) {
                if ($optionid) {
                    $option = pdo_get('wlmerchant_goods_option' , ['id' => $optionid]);
                    if ($buystatus == 1) {
                        $price = $option['price'];
                    }
                    else {
                        $price = $option['vipprice'];
                    }
                    $allarbuy = WeliamWeChat::getSalesNum(3,$id,$optionid,1,0);
                    if($allarbuy + $num > $option['stock']){
                        $this->renderError('该规格库存不足，请选择其他规格');
                    }
                    $good['viparray'] = WeliamWeChat::mergeVipArray($option['viparray'],$good['viparray']);
                }
                else {
                    $this->renderError('商品规格错误，请返回重新选择');
                }
            }else {
                if ($buystatus == 1) {
                    $price = $good['price'];
                }
                else {
                    $price = $good['aloneprice'];
                }
                //判断库存
                $allarbuy = WeliamWeChat::getSalesNum(3,$id,0,1,0);
                if($allarbuy + $num > $good['stock']){
                    $this->renderError('商品库存不足，无法下单');
                }
            }
            $goodsprice = $price * $num;
            #6、积分抵扣
            if ($creditstatus) {
                if ($good['creditmoney'] > $price) {
                    $good['creditmoney'] = $price;
                }
                $creditremark = '拼团商品[' . $good['name'] . ']抵扣积分';
                $creditindo   = self::creditDeduction($good['creditmoney'] , $num , $creditremark);
                $dkcredit     = $creditindo['dkcredit'];
                $dkmoney      = $creditindo['dkmoney'];
            }
            else {
                $dkcredit = 0;
                $dkmoney  = 0;
            }
            #7、判断会员优惠
            if (($halfcardflag || $cardId) && $good['vipstatus'] == 1) {
                $vipdiscount = WeliamWeChat::getVipDiscount($good['viparray'],$uhlevel);
                if($vipdiscount > 0){
                    $vipbuyflag  = 1;
                }
            }
            else if ($good['vipstatus'] == 2) {
                $goodsLv = unserialize($good['level']);
                if ($halfcardflag <= 0) {
                    $this->renderError('当前商品为会员限定，请先成为会员!');
                }
                else if (count($goodsLv) > 0) {
                    $lv = pdo_getcolumn(PDO_NAME . "halfcardmember" , ['id' => $halfcardflag] , 'levelid');
                    if (!in_array($lv , $goodsLv)) {
                        $this->renderError('您所在的会员等级无权购买该商品！');
                    }
                }
            }
            if (empty($vipbuyflag)) {
                $vipbuyflag = 0;
            }
            #8、判断是否开启单团功能
            $fightSet = Setting::agentsetting_read('fightgroup'); //获取设置参数
            if ($fightSet['onlyone'] == 1) {
                $groupNumber = pdo_fetchcolumn("SELECT COUNT(b.id) FROM " . tablename(PDO_NAME . "order") . " as a RIGHT JOIN " . tablename(PDO_NAME . "fightgroup_group") . " as b ON a.fightgroupid = b.id WHERE a.mid = {$_W['mid']} AND a.fkid = {$id} AND plugin = 'wlfightgroup' AND b.status = 1 ");
                if ($groupNumber > 0) $this->renderError('您已经加入一个当前商品的团，请勿重复加入!');
            }
            //购买方式
            if ($usestatus) {
                $express      = $this->freight($addressid , $num , $good);
                $expressprice = $express['price'];
                $expressid    = $express['expressid'];
                $neworderflag = 0;
            }
            else {
                $username     = trim($_GPC['thname']);
                $mobile       = trim($_GPC['thmobile']);
                $expressprice = 0;
                $neworderflag = 1;
            }
            //结算金额
            $com_dis_price = intval(0);
            if ($buystatus == 1) {
                $buyflah = 0;//团购
                //拼团 - 团长优惠
                if ($good['is_com_dis'] == 1 && empty($groupid)) {
                    $com_dis_price = sprintf("%.2f" , ($good['com_dis_price'] ? : 0));
                }
            }
            else {
                $buyflah = 1;
            }
            $settlementmoney = Store::getsettlementmoney(2 , $id , $num , $good['merchantid'] , $vipbuyflag , $optionid , $buyflah,0,$uhlevel);
            if($settlementmoney < 0.01){
                $settlementmoney = Store::getsettlementmoney(2 , $id , $num , $good['merchantid'] , $vipbuyflag , $optionid , $buyflah,0,$uhlevel);
            }
            $settlementmoney = sprintf("%.2f" , $settlementmoney + $expressprice);
            if($vipdiscount > 0){
                $vipdiscount = $vipdiscount * $num;
            }else{
                $vipdiscount = 0;
            }
            if (!empty($cardId)) {
                $cardprice = pdo_getcolumn(PDO_NAME . 'halfcard_type' , ['id' => $cardId] , 'price');
            }
            else {
                $cardprice = 0;
            }
            //满减活动
            if($good['fullreduceid']>0){
                $fulldkmoney = Fullreduce::getFullreduceMoney(sprintf("%.2f" , $price * $num - $vipdiscount - $com_dis_price),$good['fullreduceid']);
            }else{
                $fulldkmoney = 0;
            }
            if($drawid){
                $flag = pdo_getcolumn(PDO_NAME.'order',array('drawid'=>$drawid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $price = 0;
                $buystatus = 2;
            }

            if($luckydrawid){
                $flag = pdo_getcolumn(PDO_NAME.'order',array('luckydrawid'=>$luckydrawid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $price = 0;
                $buystatus = 2;
            }
            if($callid > 0){
                $flag = pdo_getcolumn(PDO_NAME.'order',array('callid'=>$callid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $price = 0;
                $buystatus = 2;
            }
            $orderprice = sprintf("%.2f" , $price * $num + $expressprice - $vipdiscount - $fulldkmoney - $dkmoney + $cardprice - $com_dis_price - $redpackmoney);
            $data    = [
                'uniacid'         => $good['uniacid'] ,
                'mid'             => $_W['mid'] ,
                'aid'             => $good['aid'] ,
                'fkid'            => $id ,
                'sid'             => $good['merchantid'] ,
                'status'          => 0 ,
                'paytype'         => 0 ,
                'createtime'      => time() ,
                'orderno'         => createUniontid() ,
                'price'           => $orderprice > 0 ? $orderprice : 0,
                'num'             => $num ,
                'plugin'          => 'wlfightgroup' ,
                'payfor'          => 'fightsharge' ,
                'spec'            => $option['title'] ,
                'specid'          => $optionid ,
                'name'            => $username ,
                'mobile'          => $mobile ,
                'fightstatus'     => $buystatus ,
                'fightgroupid'    => $groupid ,
                'expressid'       => $expressid ,
                'buyremark'       => $remark ,
                'vipbuyflag'      => $vipbuyflag ,
                'goodsprice'      => $goodsprice ,
                'settlementmoney' => $settlementmoney ,
                'vip_card_id'     => $cardId ,//会员卡的id
                'neworderflag'    => $neworderflag ,
                'usecredit'       => $dkcredit ,
                'cerditmoney'     => $dkmoney ,
                'canceltime'      => time() + $settings['cancel'] * 60 ,
                'com_dis_price'   => $com_dis_price,
                'redpackid'       => $redpackid,
                'redpackmoney'    => $redpackmoney,
                'fullreduceid'    => $good['fullreduceid'],
                'fullreducemoney' => $fulldkmoney,
                'drawid'          => $drawid,
                'luckydrawid'     => $luckydrawid,
                'callid'          => $callid,
                'vipdiscount'     => $vipdiscount,
                'moinfo'          => serialize($diyFormInfo),
                'pftinfo'         => serialize($pftInfo)
            ];
            $orderid = Wlfightgroup::saveFightOrder($data);
            if($redpackid){
                pdo_update('wlmerchant_redpack_records',array('status' => 1,'usetime' =>time(),'orderid' => $orderid,'plugin' => $plugin),array('id' => $redpackid));
            }
            if ($data['price'] > 0) {
                $unidata['status']  = 1;
                $unidata['orderid'] = $orderid;
                $this->renderSuccess('下单成功' , $unidata);
            }
            else {
                //0元购   购买商品的同时开通会员卡是不允许进行0元购的 必须进入支付流程
                $newdata = [
                    'status'  => 1 ,
                    'paytime' => time() ,
                    'paytype' => 6 ,
                ];
                pdo_update(PDO_NAME . 'order' , $newdata , ['orderno' => $data['orderno']]); //更新订单状态
                //处理营销
                if ($good['integral']) {
                    $remark = '团购:[' . $good['name'] . ']赠送积分';
                    Member::credit_update_credit1($_W['mid'] , $good['integral'] * $num , $remark);
                }
                //处理组团或者单购
                $order       = $data;
                $order['id'] = $orderid;
                if ($order['fightstatus'] == 1) {
                    if ($order['fightgroupid']) {
                        $group = pdo_get('wlmerchant_fightgroup_group' , ['id' => $order['fightgroupid']]);
                        $newdata = [];
                        if ($group['status'] == 1) {
                            $newlack = $group['lacknum'] - 1;
                            if ($newlack > 0) {
                                $newdata['lacknum'] = $newlack;
                            }else {
                                $newdata['lacknum']     = $newlack;
                                $newdata['status']      = 2;
                                $newdata['successtime'] = time();
                                $orders                 = pdo_getall('wlmerchant_order' , [
                                    'fightgroupid' => $group['id'] ,
                                    'uniacid'      => $group['uniacid'] ,
                                    'aid'          => $group['aid'] ,
                                    'status'       => 1
                                ]);
                                //幸运团
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
                                            $res    = pdo_update(PDO_NAME . 'order' , ['status' => 8] , ['id' => $or['id']]);
                                            $member = pdo_get('wlmerchant_member' , ['id' => $or['mid']] , ['openid']);
                                        }
                                        else {
                                            if ($or['neworderflag']) {
                                                Order::createSmallorder($or['id'] , 3);
                                                //计算过期时间
                                                if ($good['cutoffstatus']) {
                                                    $estimatetime = time() + $good['cutoffday'] * 86400;
                                                }
                                                else {
                                                    $estimatetime = $good['cutofftime'];
                                                }
                                                $remindtime = Order::remindTime($estimatetime);
                                                pdo_update(PDO_NAME . 'order' , [
                                                    'status'       => 1 ,
                                                    'estimatetime' => $estimatetime,
                                                    'remindtime'  => $remindtime
                                                ] , ['id' => $or['id']]);
                                            }
                                            else {
                                                $recordid = Wlfightgroup::createRecord($or['id'] , $or['num']);
                                                $res      = pdo_update(PDO_NAME . 'order' , [
                                                    'status'   => 1 ,
                                                    'recordid' => $recordid
                                                ] , ['id' => $or['id']]);
                                            }
                                            $member = pdo_get('wlmerchant_member' , ['id' => $or['mid']] , ['openid']);
                                        }
                                        //处理分销
                                        if($or['cerditmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                                            $nodis = 1;
                                        }
                                        if (p('distribution') && empty($good['isdistri']) && empty($or['drawid']) && empty($or['luckydrawid']) && empty($nodis)) {
                                            if ($or['specid']) {
                                                $option = pdo_get('wlmerchant_goods_option', ['id' => $or['specid']]);
                                                $good['disarray'] = WeliamWeChat::mergeDisArray($option['disarray'],$good['disarray']);
                                            }
                                            $disarray = unserialize($good['disarray']);
                                            $dismoney = sprintf("%.2f", $or['goodsprice'] - $or['vipdiscount']);
                                            $disorderid = Distribution::disCore($or['mid'], $dismoney, $disarray, $or['num'], 0, $or['id'], 'fightgroup', $good['dissettime'],$good['isdistristatus']);
                                            $res      = pdo_update(PDO_NAME . 'order' , [
                                                'disorderid'   => $disorderid,
                                            ] , ['id' => $or['id']]);
                                        }
                                        //小票打印
                                        Order::sendPrinting($or['id'],'wlfightgroup');
                                    }else{
                                        Wlfightgroup::refund($or['id']);
                                        //在线返红包
                                        if($good['luckymoney'] > 0){
                                            $orsource = pdo_getcolumn(PDO_NAME.'paylogvfour',array('tid'=>$or['orderno'],'mid'=>$or['mid']),'source');
                                            $nlUser = pdo_get('wlmerchant_member',array('id' => $or['mid']),array('openid','wechat_openid'));
                                            if($_W['source'] == 1){
                                                $sopenid = $nlUser['openid'];
                                            }else if($_W['source'] == 3){
                                                $sopenid = $nlUser['wechat_openid'];
                                            }
                                            if(empty($sopenid)){
                                                if(!empty($nlUser['openid'])){
                                                    $sopenid = $nlUser['openid'];
                                                    $orsource = 1;
                                                }
                                                if(!empty($nlUser['wechat_openid'])){
                                                    $sopenid = $nlUser['wechat_openid'];
                                                    $orsource = 3;
                                                }
                                            }
                                            if(!empty($sopenid)){
                                                $params = [
                                                    'openid'   => $sopenid ,
                                                    'money'    => $good['luckymoney'] ,
                                                    'rem'      => '幸运团返现' ,
                                                    'name'     => 'weliam' ,
                                                    'order_no' => $or['orderno'],
                                                    'source'   => $orsource ,
                                                    'mid'      => $or['mid']
                                                ];
                                                $res = Payment::presentationInit($params,2);
                                                file_put_contents(PATH_DATA . "luckygroup.log", var_export($res, true) . PHP_EOL, FILE_APPEND);
                                            }
                                        }
                                    }
                                }
                            }
                            pdo_update(PDO_NAME . 'fightgroup_group' , $newdata , ['id' => $order['fightgroupid']]);
                            News::groupresult($order['fightgroupid']);
                        }
                        else {
                            $newgroupflag = 1;
                        }
                    }
                    else {
                        $newgroupflag = 1;
                    }
                    if ($newgroupflag) {
                        $group        = [
                            'status'    => 1 ,
                            'goodsid'   => $order['fkid'] ,
                            'aid'       => $good['aid'] ,
                            'sid'       => $good['merchantid'] ,
                            'neednum'   => $good['peoplenum'] ,
                            'lacknum'   => $good['peoplenum'] - 1 ,
                            'starttime' => time() ,
                            'failtime'  => time() + $good['grouptime'] * 3600 ,
                            'is_lucky'  => $good['is_lucky']
                        ];
                        $fightgroupid = Wlfightgroup::saveFightGroup($group);
                        pdo_update(PDO_NAME . 'order' , ['fightgroupid' => $fightgroupid] , ['id' => $order['id']]);
                    }
                }
                else {
                    if ($order['expressid']) {
                        pdo_update(PDO_NAME . 'order' , ['status' => 8] , ['id' => $order['id']]);
                    }
                    else {
                        if ($order['neworderflag']) {
                            Order::createSmallorder($order['id'] , 3);
                            //计算过期时间
                            if ($good['cutoffstatus']) {
                                $estimatetime = time() + $good['cutoffday'] * 86400;
                            }
                            else {
                                $estimatetime = $good['cutofftime'];
                            }
                            $remindtime = Order::remindTime($estimatetime);
                            pdo_update(PDO_NAME . 'order' , [
                                'status'       => 1 ,
                                'estimatetime' => $estimatetime,
                                'remindtime'  => $remindtime
                            ] , ['id' => $order['id']]);
                        }
                        else {
                            $recordid = Wlfightgroup::createRecord($order['id'] , $order['num']);
                            pdo_update(PDO_NAME . 'order' , [
                                'status'   => 1 ,
                                'recordid' => $recordid
                            ] , ['id' => $order['id']]);
                        }
                    }
                    //抽奖领取
                    if($data['drawid'] > 0){
                        pdo_update('wlmerchant_draw_record',array('is_get' => 2),array('id' => $data['drawid']));
                    }
                    if($data['luckydrawid'] > 0){
                        pdo_update('wlmerchant_luckydraw_drawcode',array('is_get' => 1,'gettime' => time()),array('id' => $data['luckydrawid']));
                    }
                    if($data['callid'] > 0){
                        pdo_update('wlmerchant_call_receive',array('orderid' => $orderid),array('id' => $data['callid']));
                    }
                    //处理分销
                    if($dkmoney > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                        $nodis = 1;
                    }
                    if (p('distribution') && empty($good['isdistri']) && empty($data['drawid']) && empty($data['luckydrawid']) && empty($data['callid']) && empty($nodis)) {
                        if ($order['specid']) {
                            $good['disarray'] = WeliamWeChat::mergeDisArray($option['disarray'],$good['disarray']);
                        }
                        $disarray = unserialize($good['disarray']);
                        $dismoney = sprintf("%.2f", $order['goodsprice'] - $order['vipdiscount']);
                        $disorderid = Distribution::disCore($order['mid'], $dismoney, $disarray, $order['num'], 0, $order['id'], 'fightgroup', $good['dissettime'],$good['isdistristatus']);
                        $res      = pdo_update(PDO_NAME . 'order' , [
                            'disorderid'   => $disorderid,
                        ] , ['id' => $order['id']]);
                    }
                    //小票打印
                    Order::sendPrinting($order['id'],'wlfightgroup');
                }
                $data2['realsalenum'] = $good['realsalenum'] + $order['num'];
                $res2                 = Wlfightgroup::updateGoods($data2 , $order['fkid']);
                //发送通知
                /***模板通知***/
                News::addSysNotice($order['uniacid'],2,$order['sid'],0,$order['id']);
                Store::addFans($order['sid'], $order['mid']);
                News::paySuccess($order['id'], 'wlfightgroup');

                $unidata['status']  = 0;
                $unidata['orderid'] = $orderid;
                $unidata['tid']     = $data['orderno'];
                $unidata['plugin']  = 'wlfightgroup';
                $this->renderSuccess('购买成功' , $unidata);
            }
        }
        //砍价
        if ($plugin == 'bargain') {
            MysqlFunction::setTrans(4);
            MysqlFunction::startTrans();
            $activity  = Bargain::getSingleActive($id , '*');
            $userorder = pdo_get('wlmerchant_bargain_userlist' , ['id' => $optionid]);
            if ($userorder['orderid']) {
                MysqlFunction::rollback();
                $this->renderError('此砍价活动已经生成订单，无法重复生成');
            }
            if ($activity['status'] != 2) {
                MysqlFunction::rollback();
                $this->renderError('活动未开始或已结束,无法下单');
            }
            //判断已有订单
            $nopayorder = pdo_getcolumn('wlmerchant_order' , [
                'mid'    => $_W['mid'] ,
                'status' => 0 ,
                'fkid'   => $activity['id'] ,
                'specid' => $optionid ,
                'plugin' => 'bargain'
            ] , 'id');
            if (!empty($nopayorder)) {
                MysqlFunction::rollback();
                $this->renderError('请先支付或取消未支付的订单');
            }
            //剩余数量
            $alreadynum = WeliamWeChat::getSalesNum(5,$id,0,1,0);
            $levelnum   = $activity['stock'] - intval($alreadynum);
            if ($levelnum == 0 || $levelnum < 1) {
                MysqlFunction::rollback();
                $this->renderError('该商品已被抢完，请下次手快些哦');
            }
            //判断会员
            if ($activity['vipstatus'] == 1) {
                if ($halfcardflag) {
                    $vipbuyflag = 1;
                }
                else {
                    $vipbuyflag = 0;
                }
            }
            if ($usestatus) {
                $express      = $this->freight($addressid , $num , $activity);
                $expressprice = $express['price'];
                $expressid    = $express['expressid'];
                $neworderflag = 0;
            }
            else {
                $username     = trim($_GPC['thname']);
                $mobile       = trim($_GPC['thmobile']);
                $neworderflag = 1;
                $expressprice = 0;
            }
            //积分抵扣
            if ($creditstatus) {
                if ($activity['creditmoney'] > sprintf("%.2f" , $userorder['price'])) {
                    $activity['creditmoney'] = sprintf("%.2f" , $userorder['price']);
                }
                $creditremark = '砍价[' . $activity['name'] . ']抵扣积分';
                $creditindo   = self::creditDeduction($activity['creditmoney'] , $num , $creditremark);
                $dkcredit     = $creditindo['dkcredit'];
                $dkmoney      = $creditindo['dkmoney'];
            }
            else {
                $dkcredit = 0;
                $dkmoney  = 0;
            }
            //满减活动
            if($activity['fullreduceid']>0){
                $fulldkmoney = Fullreduce::getFullreduceMoney($userorder['price'],$activity['fullreduceid']);
            }else{
                $fulldkmoney = 0;
            }
            $price = $userorder['price'];
            if($drawid > 0){
                $flag = pdo_getcolumn(PDO_NAME.'order',array('drawid'=>$drawid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $price = 0;
            }

            if($luckydrawid > 0){
                $flag = pdo_getcolumn(PDO_NAME.'order',array('luckydrawid'=>$luckydrawid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $price = 0;
            }
            if($callid > 0){
                $flag = pdo_getcolumn(PDO_NAME.'order',array('callid'=>$callid),'id');
                if($flag > 0){
                    $this->renderError('此奖品已被领取，无法重复领取');
                }
                $price = 0;
            }
            $prices = sprintf("%.2f" , $price + $expressprice - $dkmoney - $redpackmoney - $fulldkmoney);
            //结算金额
            $settlementmoney = Store::getsettlementmoney(5 , $optionid , $num , $activity['sid'] , $vipbuyflag,0,0,0,$uhlevel);
            if ($expressprice) {
                $settlementmoney = sprintf("%.2f" , $expressprice + $settlementmoney);
            }
            //创建订单
            $data = [
                'uniacid'         => $activity['uniacid'] ,
                'mid'             => $_W['mid'] ,
                'sid'             => $activity['sid'] ,
                'aid'             => $activity['aid'] ,
                'fkid'            => $activity['id'] ,
                'plugin'          => 'bargain' ,
                'payfor'          => 'bargainOrder' ,
                'orderno'         => createUniontid() ,
                'status'          => 0 ,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
                'createtime'      => TIMESTAMP ,
                'price'           => $prices > 0 ? $prices : 0,
                'num'             => 1 ,
                'vipbuyflag'      => $vipbuyflag ,
                'specid'          => $optionid ,
                'name'            => $username ,
                'mobile'          => $mobile ,
                'fightstatus'     => $usestatus ,
                'expressid'       => $expressid ,
                'buyremark'       => $remark ,
                'goodsprice'      => $userorder['price'] ,
                'settlementmoney' => $settlementmoney ,
                'neworderflag'    => $neworderflag ,
                'usecredit'       => $dkcredit ,
                'cerditmoney'     => $dkmoney ,
                'canceltime'      => time() + $settings['cancel'] * 60,
                'redpackid'       => $redpackid,
                'redpackmoney'    => $redpackmoney,
                'fullreduceid'    => $activity['fullreduceid'],
                'fullreducemoney' => $fulldkmoney,
                'drawid'          => $drawid,
                'luckydrawid'     => $luckydrawid,
                'callid'          => $callid,
                'moinfo'          => serialize($diyFormInfo)
            ];
            $res = pdo_insert(PDO_NAME . 'order' , $data);
            $errid = pdo_insertid();
            if($res){
                $orderid = pdo_getcolumn(PDO_NAME.'order',array('orderno'=>$data['orderno']),'id');
            }
            if($orderid != $errid){
                file_put_contents(PATH_DATA . "barOrderError.log", var_export($orderid.'||'.$errid, true) . PHP_EOL, FILE_APPEND);
            }
            if($redpackid){
                pdo_update('wlmerchant_redpack_records',array('status' => 1,'usetime' =>time(),'orderid' => $orderid,'plugin' => $plugin),array('id' => $redpackid));
            }
            if ($orderid) {
                pdo_update('wlmerchant_bargain_userlist' , ['orderid' => $orderid] , ['id' => $optionid]);
                MysqlFunction::commit();
                if ($data['price'] > 0) {
                    $unidata['status']  = 1;
                    $unidata['orderid'] = $orderid;
                    $this->renderSuccess('购买成功' , $unidata);
                }
                else {
                    //0元购   购买商品的同时开通会员卡是不允许进行0元购的 必须进入支付流程
                    $newdata = [
                        'status'  => 1 ,
                        'paytime' => time() ,
                        'paytype' => 6 ,
                    ];

                    if ($expressid) {
                        $newdata['status'] = 8;
                        Bargain::createRecord($orderid , 1 , $optionid , 1);
                    }
                    else {
                        Order::createSmallorder($orderid , 5);
                        if ($activity['cutoffstatus']) {
                            $newdata['estimatetime'] = time() + $activity['cutoffday'] * 86400;
                        }
                        else {
                            $newdata['estimatetime'] = $activity['cutofftime'];
                        }
                        $newdata['remindtime'] = Order::remindTime($newdata['estimatetime']);
                        pdo_update(PDO_NAME . 'bargain_userlist' , ['status' => 2] , ['id' => $optionid]);
                    }

                    if($dkmoney > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                        $nodis = 1;
                    }
                    if (p('distribution') && empty($activity['isdistri']) && empty($nodis)) {
                        $disarray = unserialize($activity['disarray']);
                        $disorderid = Distribution::disCore($data['mid'], $data['goodsprice'], $disarray, 1, 0, $orderid, 'bargain', $activity['dissettime'],$activity['isdistristatus']);
                        $newdata['disorderid'] = $disorderid;
                    }

                    pdo_update(PDO_NAME . 'order' , $newdata , ['orderno' => $data['orderno']]); //更新订单状态
                    //抽奖领取
                    if($data['drawid'] > 0){
                        pdo_update('wlmerchant_draw_record',array('is_get' => 2),array('id' => $data['drawid']));
                    }
                    if($data['drawid'] > 0){
                        pdo_update('wlmerchant_luckydraw_drawcode',array('is_get' => 1,'gettime' => time()),array('id' => $data['drawid']));
                    }
                    //处理营销
                    if ($activity['integral']) {
                        $remark = '砍价:[' . $activity['name'] . ']赠送积分';
                        Member::credit_update_credit1($_W['mid'] , $activity['integral'] , $remark);
                    }
                    /***模板通知***/
                    News::addSysNotice($data['uniacid'],2,$data['sid'],0,$orderid);
                    Store::addFans($data['sid'], $data['mid']);
                    News::paySuccess($orderid,'bargain');
                    //小票打印
                    Order::sendPrinting($orderid,'bargain');

                    $unidata['status']  = 0;
                    $unidata['orderid'] = $orderid;
                    $unidata['tid']     = $data['orderno'];
                    $unidata['plugin']  = 'bargain';
                    $this->renderSuccess('购买成功' , $unidata);
                }
            }
            MysqlFunction::rollback();
        }
        //积分兑换
        if ($plugin == 'consumption') {
            $goods = Consumption::creditshop_goods_get($id);
            $level = unserialize($goods['level']);
            if($goods['usedatestatus'] > 0){
                $check = WeliamWeChat::checkUseDateStatus($goods['usedatestatus'],$goods['week'],$goods['day']);
                if(empty($check)){
                    $this->renderError('今日商品无法兑换');
                }
            }
            if ($goods['type'] == 'halfcard' && $halfcardflag) {
                $this->renderError('您现在已是会员，无法再次兑换会员资格');
            }
            if ((!empty($cardId) || $halfcardflag) && $goods['vipstatus'] == 1) {
                //判断会员特价
                $goods['use_credit1'] = $goods['vipcredit1'];
                $goods['use_credit2'] = $goods['vipcredit2'];
            }
            else if ($goods['vipstatus'] == 2) {
                //判断会员特供
                if (empty($halfcardflag) && empty($cardId)) {
                    MysqlFunction::rollback();
                    $this->renderError('该商品会员特供，请先成为会员');
                }else if ($level) {
                    //判断等级
                    $flag = Halfcard::checklevel($_W['mid'] , $level);
                    if (empty($flag)) {
                        MysqlFunction::rollback();
                        $this->renderError('您所在的会员等级无权购买该商品');
                    }
                }

            }
            //判断数量
            if ($goods['chance'] > 0) {
                $times = pdo_fetchcolumn('SELECT SUM(num) FROM ' . tablename('wlmerchant_consumption_record') . " WHERE uniacid = {$_W['uniacid']} AND  goodsid = {$id} AND mid = {$_W['mid']} ");
                if ($times == $goods['chance'] || $times > $goods['chance']) {
                    $this->renderError('该商品最多兑换' . $goods['chance'] . '次');
                }
            }
            //判断库存
            #6、获取销量
            $total = pdo_fetchcolumn("SELECT SUM(num) FROM " . tablename(PDO_NAME . "order") . " WHERE plugin = 'consumption' AND fkid = {$id} AND status != 5 AND status != 7");
            if($total >= $goods['stock']) $this->renderError('该商品已售罄！');
            //判断积分
            if ($_W['wlmember']['credit1'] < $goods['use_credit1'] * $num) {
                $jftext = $_W['wlsetting']['trade']['credittext']?$_W['wlsetting']['trade']['credittext']:'积分';
                $this->renderError($jftext.'不足，无法兑换');
            }
            if ($goods['type'] == 'goods') {
                $express      = $this->freight($addressid , $num , $goods);
                $expressprice = $express['price'];
                $expressid    = $express['expressid'];
            }
            if (!empty($cardId)) {
                $cardprice = pdo_getcolumn(PDO_NAME . 'halfcard_type' , ['id' => $cardId] , 'price');
            }
            else {
                $cardprice = 0;
            }
            $prices = sprintf("%.2f" , $goods['use_credit2'] * $num + $expressprice + $cardprice - $redpackmoney);
            //支付商品
            $usecredit = sprintf("%.2f",$goods['use_credit1'] * $num );
            if($usecredit < 0){
                $this->renderError($jftext.'信息错误，无法兑换');
            }
            //创建订单
            $data = [
                'uniacid'         => $_W['uniacid'] ,
                'mid'             => $_W['mid'] ,
                'sid'             => 0 ,
                'aid'             => $_W['aid'] ,
                'fkid'            => $goods['id'] ,
                'plugin'          => 'consumption' ,
                'payfor'          => 'consumOrder' ,
                'orderno'         => createUniontid() ,
                'status'          => 0 ,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
                'createtime'      => TIMESTAMP ,
                'price'           => $prices > 0 ? $prices : 0,
                'num'             => 1 ,
                'expressid'       => $expressid ,
                'buyremark'       => $remark ,
                'goodsprice'      => $goods['old_price'] ,
                'settlementmoney' => 0 ,
                'settlementmoney' => 0 ,
                'vip_card_id'     => $cardId ,//会员卡的id
                'name'            => $_GPC['thname'] ,
                'usecredit'       => $usecredit,
                'canceltime'      => time() + $settings['cancel'] * 60,
                'redpackid'       => $redpackid,
                'redpackmoney'    => $redpackmoney
            ];
            pdo_insert(PDO_NAME . 'order' , $data);
            $orderid = pdo_insertid();
            if($redpackid){
                pdo_update('wlmerchant_redpack_records',array('status' => 1,'usetime' =>time(),'orderid' => $orderid,'plugin' => $plugin),array('id' => $redpackid));
            }
            if ($expressid) {
                pdo_update('wlmerchant_express' , ['orderid' => $orderid] , ['id' => $expressid]);
            }
            $res = Member::credit_update_credit1($_W['mid'] , -$data['usecredit'] , '兑换[' . $goods['title'] . ']消耗');
            //不支付的商品  存在开卡存在时不能进行0元购
            if ($goods['use_credit2'] < 0.01 && empty($cardId) && $expressprice < 0.01) {
                $trade = Setting::wlsetting_read('trade');
                if ($res) {
                    if ($goods['type'] == 'credit2') {
                        $res2 = Member::credit_update_credit2($_W['mid'] , $goods['credit2'] , '兑换[' . $goods['title'] . ']获得' . $trade['moneytext']);
                    }
                    else if ($goods['type'] == 'halfcard') {
                        $res2 = Consumption::conhalfcard($_W['mid'] , $goods['halfcardid'] , $_GPC['thname']);
                    }
                    else {
                        $res2 = 1;
                    }
                    if ($res2) {
                        $recordstatus = $goods['type'] == 'goods' ? 1 : 3;
                        $data2 = [
                            'uniacid'    => $_W['uniacid'] ,
                            'mid'        => $_W['mid'] ,
                            'goodsid'    => $id ,
                            'status'     => $recordstatus ,
                            'createtime' => time() ,
                            'integral'   => $data['usecredit'],
                            'expressid'  => $expressid ,
                            'orderid'    => $orderid,
                            'num'        => $num
                        ];
                        if ($expressid) {
                            $data2['status'] = 1;
                        }
                        $res3 = pdo_insert(PDO_NAME . 'consumption_record' , $data2);
                        if ($res3) {
                            $changestatus = $goods['type'] == 'goods' ? 8 : 3;
                            pdo_update('wlmerchant_order' , [
                                'status'  => $changestatus ,
                                'paytype' => 6 ,
                                'paytime' => time(),
                                'issettlement' => 1
                            ] , ['id' => $orderid]);
                            /***模板通知***/
                            $url       = h5_url('pages/subPages/coupon/coupon');
                            $modelData = [
                                'first'   => '恭喜您,一个商品兑换成功' ,
                                'type'    => '积分商品兑换' ,//业务类型
                                'content' => $goods['title'] ,//业务内容
                                'status'  => '兑换成功' ,//处理结果
                                'time'    => date("Y-m-d H:i:s" , $data['createtime']) ,//操作时间
                                'remark'  => '点击查看兑换记录，如有问题请联系管理员'
                            ];
                            TempModel::sendInit('service' , $_W['mid'] , $modelData , $_W['source'] , $url);
                            /***模板通知***/
                            $unidata['status']  = 0;
                            $unidata['orderid'] = $orderid;
                            $unidata['tid']     = $data['orderno'];
                            $unidata['plugin']  = 'consumption';
                            $this->renderSuccess('购买成功' , $unidata);
                        }
                    }
                }
            }
            else {
                if ($orderid) {
                    $unidata['status']  = 1;
                    $unidata['orderid'] = $orderid;
                    $this->renderSuccess('购买成功' , $unidata);
                }
            }
        }
        //活动报名
        if ($plugin == 'activity'){
            MysqlFunction::setTrans(4);
            MysqlFunction::startTrans();
            $activity = pdo_get('wlmerchant_activitylist',array('id' => $id));
            //判断活动状态
            if ($activity['status'] != 2) {
                if ($activity['status'] == 1) {
                    MysqlFunction::rollback();
                    $this->renderError('活动报名尚未开始');
                }
                else if ($activity['status'] == 3) {
                    MysqlFunction::rollback();
                    $this->renderError('活动报名已结束');
                } else{
                    MysqlFunction::rollback();
                    $this->renderError('活动已关闭');
                }
            }
            //判断规格
            if ($activity['optionstatus']>0) {
                if ($optionid) {
                    $option               = pdo_get('wlmerchant_activity_spec' , ['id' => $optionid] , ['viparray','disarray','name','price','maxnum']);
                    $activity['price']    = $option['price'];
                    $total = $option['maxnum'];
                    $activity['viparray'] = WeliamWeChat::mergeVipArray($option['viparray'],$activity['viparray']);
                }else {
                    MysqlFunction::rollback();
                    $this->renderError('规格参数错误，请重新选择');
                }
            }
            else {
                $optionid = 0;
                $total = $activity['maxpeoplenum'];
            }
            //判断剩余数量
            if($total > 0){
                $salesVolume = WeliamWeChat::getSalesNum(6,$id,$optionid,1);
                $levelnum = $total - intval($salesVolume);
                if($levelnum < $num){
                    MysqlFunction::rollback();
                    $this->renderError('报名名额已满');
                }
            }
            if($activity['onelimit']>0){
                $salesVolume = WeliamWeChat::getSalesNum(6,$id,0,1,$_W['mid']);
                $levelnum = $activity['onelimit'] - intval($salesVolume);
                if($levelnum < $num){
                    MysqlFunction::rollback();
                    if($levelnum>0){
                        $this->renderError('您还能报名'.$levelnum.'人次');
                    }else{
                        $this->renderError('您已经报名过了');
                    }
                }
            }
            /*判断会员*/
            $price = $activity['price'];
            if ($activity['vipstatus'] == 1) {
                if ($halfcardflag || !empty($cardId)) {
                    $vipdiscount = WeliamWeChat::getVipDiscount($activity['viparray'],$uhlevel);
                    if($vipdiscount > 0){
                        $vipbuyflag  = 1;
                    }
                }
            }else if ($activity['vipstatus'] == 2) {
                if (empty($halfcardflag) && empty($cardId)) {
                    MysqlFunction::rollback();
                    $this->renderError('该商品会员特供，请先成为会员');
                }
            }

            if (empty($vipbuyflag)) {
                $vipbuyflag = 0;
            }
            //创建订单
            $prices = sprintf("%.2f" , $price * $num);
            $goodsprice = sprintf("%.2f" , $activity['price'] * $num);
            if (!empty($cardId)) {
                $cardprice = pdo_getcolumn(PDO_NAME . 'halfcard_type' , ['id' => $cardId] , 'price');
            }
            else {
                $cardprice = 0;
            }
            if($vipdiscount > 0){
                $vipdiscount = $vipdiscount * $num;
            }else{
                $vipdiscount = 0;
            }
            $prices = sprintf("%.2f" , $prices + $cardprice - $vipdiscount - $redpackmoney);
            //结算金额
            $useprices = sprintf("%.2f" , $prices / $num);
            $settlementmoney = Store::getsettlementmoney(6,$id,$num,$activity['sid'],$vipbuyflag ,$optionid,0,$useprices,$uhlevel);
            if($settlementmoney < 0.01){
                $settlementmoney = Store::getsettlementmoney(6,$id,$num,$activity['sid'],$vipbuyflag ,$optionid,0,$useprices,$uhlevel);
            }
            $data = [
                'uniacid'         => $activity['uniacid'] ,
                'mid'             => $_W['mid'] ,
                'sid'             => $activity['sid'] ,
                'aid'             => $activity['aid'] ,
                'fkid'            => $activity['id'] ,
                'plugin'          => 'activity' ,
                'payfor'          => 'Activitysharge' ,
                'orderno'         => createUniontid() ,
                'status'          => 0 ,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
                'createtime'      => TIMESTAMP ,
                'price'           => $prices > 0 ? $prices : 0,
                'num'             => $num ,
                'vipbuyflag'      => $vipbuyflag ,
                'specid'          => $optionid ,
                'name'            => $username ,
                'mobile'          => $mobile ,
                'goodsprice'      => $goodsprice ,
                'buyremark'       => $remark ,
                'settlementmoney' => $settlementmoney ,
                'vip_card_id'     => $cardId ,//会员卡的id
                'canceltime'      => time() + $settings['cancel'] * 60,
                'redpackid'       => $redpackid,
                'redpackmoney'    => $redpackmoney,
                'neworderflag'    => 1,
                'vipdiscount'     => $vipdiscount,
                'moinfo'          => serialize($diyFormInfo)
            ];
            pdo_insert(PDO_NAME . 'order' , $data);
            $orderid = pdo_insertid();
            if (empty($orderid)) {
                MysqlFunction::rollback();
                $this->renderError('创建订单失败，请刷新重试');
            }
            MysqlFunction::commit();
            if ($data['price'] > 0) {
                $unidata['status']  = 1;
                $unidata['orderid'] = $orderid;
                $this->renderSuccess('购买成功' , $unidata);
            }else{
                //0元购   购买商品的同时开通会员卡是不允许进行0元购的 必须进入支付流程
                $newdata = [
                    'status'  => 1 ,
                    'paytime' => time() ,
                    'paytype' => 6 ,
                ];
                Order::createSmallorder($orderid , 6);
                //计算过期时间
                $newdata['estimatetime'] = $activity['activeendtime'];
                $newdata['remindtime'] = Order::remindTime($newdata['activestarttime']);
                //处理分销
                if($dkmoney>0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                    $nodis = 1;
                }
                if (p('distribution') && empty($activity['isdistri']) && empty($data['drawid']) && empty($data['luckydrawid']) && empty($data['callid']) && empty($nodis)) {
                    if ($data['specid']) {
                        $activity['disarray'] = WeliamWeChat::mergeDisArray($option['disarray'],$activity['disarray']);
                    }
                    $disarray = unserialize($activity['disarray']);
                    $dismoney = sprintf("%.2f",$data['goodsprice'] - $data['vipdiscount']);
                    $disorderid = Distribution::disCore($data['mid'],$dismoney,$disarray, $data['num'], 0, $orderid, 'activity', $activity['dissettime'],$activity['isdistristatus']);
                    $newdata['disorderid'] = $disorderid;
                }
                pdo_update(PDO_NAME . 'order' , $newdata , ['orderno' => $data['orderno']]); //更新订单状态
                /***模板通知***/
                Store::addFans($activity['sid'] , $_W['mid']);
                News::addSysNotice($data['uniacid'],2,$data['sid'],0,$orderid);
                News::paySuccess($orderid, 'activity');
                //小票打印
                //Order::sendPrinting($orderid,'activity');

                $unidata['status']  = 0;
                $unidata['orderid'] = $orderid;
                $unidata['tid']     = $data['orderno'];
                $unidata['plugin']  = 'activity';
                $this->renderSuccess('购买成功' , $unidata);
            }


        }

    }
    /**
     * Comment: 计算运费
     * Author: wlf
     * Date: 2019/8/19 09:15
     */
    public function freight($addressid , $num , $good,$expressprice = 0)
    {
        global $_W;
        //设置默认
        pdo_update('wlmerchant_address' , ['status' => 0] , ['mid' => $_W['mid'] , 'uniacid' => $_W['uniacid']]);
        pdo_update('wlmerchant_address' , ['status' => 1] , ['id' => $addressid]);
        $address            = pdo_get('wlmerchant_address' , ['id' => $addressid]);
        $data['uniacid']    = $_W['uniacid'];
        $data['mid']        = $_W['mid'];
        $data['goodsid']    = $good['id'];
        $data['merchantid'] = $good['merchantid'];
        $data['address']    = $addre = $address['province'] . $address['city'] . $address['county'] . $address['detailed_address'];
        $data['name']       = $username = $address['name'];
        $data['tel']        = $mobile = $address['tel'];
        if ($good['expressid']>0) {
            $express = pdo_get('wlmerchant_express_template' , ['id' => $good['expressid']]);
            //添加设置错误项校验
            if(empty($express['defaultnum'])){
                $express['defaultnum'] = 99999;
            }
            if(empty($express['defaultnumex'])){
                $express['defaultnumex'] = 1;
            }
            if(!empty($express)){
                $areaflag = 0;
                if (mb_substr($address['province'] , -1 , 1 , 'utf-8') == '省') {
                    $address['province'] = mb_substr($address['province'] , 0 , mb_strlen($address['province']) - 1 , 'utf-8');
                }
                if ($express['expressarray']) {
                    $expressarray = unserialize($express['expressarray']);
                    foreach ($expressarray as $key => &$v) {
                        $v['area'] = rtrim($v['area'] , ",");
                        $v['area'] = explode(',' , $v['area']);
                        $v['numex'] = $v['numex'] ? : 999;
                        if (in_array($address['province'] , $v['area'])) {
                            if($num >= $v['freenumber'] && $v['freenumber'] > 0){
                                $expressprice = 0; //包邮
                            }else if ($num > $v['num']) {
                                $expressprice = $v['money'] + ceil(($num - $v['num']) / $v['numex']) * $v['moneyex'];
                            }
                            else {
                                $expressprice = $v['money'];
                            }
                            $areaflag = 1;
                        }
                    }
                }
                if (empty($areaflag)) {
                    if($num >= $express['freenumber'] && $express['freenumber'] > 0){
                        $expressprice = 0; //包邮
                    }else if ($num > $express['defaultnum']) {
                        $expressprice = $express['defaultmoney'] + ceil(($num - $express['defaultnum']) / $express['defaultnumex']) * $express['defaultmoneyex'];
                    }
                    else {
                        $expressprice = $express['defaultmoney'];
                    }
                }
            }
            $expressprice = $expressprice < 0 ? 0 : $expressprice;
        }
        $data['expressprice'] = $expressprice;
        pdo_insert(PDO_NAME . 'express' , $data);
        $expressid = pdo_insertid();
        $express   = [
            'price'     => $expressprice ,
            'expressid' => $expressid
        ];
        return $express;
    }
    /**
     * Comment: 订单详情页面
     * Author: wlf
     * Date: 2019/8/19 10:35
     */
    public function orderDetail()
    {
        global $_W , $_GPC;
        $id         = $_GPC['orderid'];
        $plugin     = $_GPC['plugin'];
        $lat        = $_GPC['lat'];
        $lng        = $_GPC['lng'];
        $data       = [];
        $data['id'] = $id;
        if ($plugin == 'rush') {
            $order = pdo_get('wlmerchant_rush_order' , ['id' => $id]);
            if($order['blendcredit'] > 0){
                $order['paytype'] = 7;
                $data['blendcredit'] = $order['blendcredit'];
                $data['blendwx'] = sprintf("%.2f",$order['actualprice'] - $order['blendcredit']);
            }
            $goods = pdo_get('wlmerchant_rush_activity' , ['id' => $order['activityid']]);
            $data['goodName']  = $goods['name'];
            $data['goodLogo']  = tomedia($goods['thumb']);
            $data['unitPrice'] = sprintf("%.2f" , $order['price'] / $order['num']);
            if ($order['optionid']) {
                $option             = pdo_get('wlmerchant_goods_option' , ['id' => $order['optionid']] , [
                    'title' ,
                    'price' ,
                    'vipprice'
                ]);
                $data['optionName'] = $option['title'];
            }
            if ($order['vipbuyflag']) {
                if ($order['discount'] > 0) {
                    //会员优惠存在 并且不是多规格商品
                    $data['vipdiscount'] = $order['discount'];
                }else {
                    //没有储存会员优惠 按照以前的规则进行查询
                    if ($order['optionid']) {
                        $data['vipdiscount'] = sprintf("%.2f" , $option['price'] - $option['vipprice']);
                    }
                    else {
                        $data['vipdiscount'] = sprintf("%.2f" , $goods['price'] - $goods['vipprice']);
                    }
                }
            }
            else {
                $data['vipdiscount'] = 0;
            }
            $data['thname']      = $order['username'] ? $order['username'] : $_W['wlmember']['nickname'];
            $data['thmobile']    = $order['mobile'] ? $order['mobile'] : $_W['wlmember']['mobile'];
            $data['goodsprice']  = $order['price'];
            $data['actualprice'] = $order['actualprice'];
            $data['usecredit'] = $order['dkcredit'];
            $data['creditmoney'] = $order['dkmoney'];
            $data['a']           = 'b';
            $data['goodsid']     = $order['activityid'];
            $data['pluginno']    = 1;
            $data['buyremark']   = $order['remark'];  //买家备注
            $data['adminremark'] = $order['adminremark']; //卖家备注
        }
        else {
            $order = pdo_get('wlmerchant_order' , ['id' => $id]);
            if($order['blendcredit'] > 0){
                $order['paytype'] = 7;
                $data['blendcredit'] = $order['blendcredit'];
                $data['blendwx'] = sprintf("%.2f",$order['price'] - $order['blendcredit']);
            }
            if ($plugin == 'groupon') {
                $goods            = pdo_get('wlmerchant_groupon_activity' , ['id' => $order['fkid']]);
                $data['goodName'] = $goods['name'];
                $data['goodLogo'] = tomedia($goods['thumb']);
                if ($order['specid']) {
                    $option             = pdo_get('wlmerchant_goods_option' , ['id' => $order['specid']] , [
                        'title' ,
                        'price' ,
                        'vipprice'
                    ]);
                    $data['optionName'] = $option['title'];
                }
                if ($order['vipbuyflag']) {
                    if($order['vipdiscount']>0){
                        $data['vipdiscount'] = $order['vipdiscount'];
                    }else{
                        if ($order['specid']) {
                            $data['vipdiscount'] = sprintf("%.2f" , $option['price'] - $option['vipprice']);
                        }else {
                            $data['vipdiscount'] = sprintf("%.2f" , $goods['price'] - $goods['vipprice']);
                        }
                    }

                }
                else {
                    $data['vipdiscount'] = 0;
                }
                $data['goodsid']  = $order['fkid'];
                $data['pluginno'] = 2;
            }
            else if ($plugin == 'wlfightgroup') {
                $goods            = pdo_get('wlmerchant_fightgroup_goods' , ['id' => $order['fkid']]);
                $data['goodName'] = $goods['name'];
                $data['goodLogo'] = tomedia($goods['logo']);
                if ($order['specid']) {
                    $data['optionName'] = pdo_getcolumn(PDO_NAME . 'goods_option' , ['id' => $order['specid']] , 'title');
                }
                if ($order['vipbuyflag']) {
                    $data['vipdiscount'] = $goods['vipdiscount'];
                }
                $order['goodsprice'] = $order['goodsprice'] - $data['vipdiscount'];
                $data['goodsid']     = $order['fkid'];
                $data['pluginno']    = 3;
                if ($order['fightstatus'] == 1) {
                    $group = pdo_get('wlmerchant_fightgroup_group' , ['id' => $order['fightgroupid']] , ['status']);
                }
                $data['fightstatus']  = $order['fightstatus'];
                $data['groupstatus']  = $group['status'];
                $data['fightgroupid'] = $order['fightgroupid'];
            }
            else if ($plugin == 'bargain') {
                $goods            = pdo_get('wlmerchant_bargain_activity' , ['id' => $order['fkid']]);
                $data['goodName'] = $goods['name'];
                $data['goodLogo'] = tomedia($goods['thumb']);
                $data['goodsid']  = $order['fkid'];
                $data['pluginno'] = 7;
            }
            else if ($plugin == 'consumption') {
                $goods            = pdo_get('wlmerchant_consumption_goods' , ['id' => $order['fkid']]);
                $data['goodName'] = $goods['title'];
                $data['goodLogo'] = tomedia($goods['thumb']);
                $data['goodsid']  = $order['fkid'];
                $data['pluginno'] = 'integral';//8
            }
            else if ($plugin == 'coupon') {
                $goods = pdo_get('wlmerchant_couponlist' , ['id' => $order['fkid']]);
                if ($order['vipbuyflag']) {
                    $data['vipdiscount'] = sprintf("%.2f" , $goods['price'] - $goods['vipprice']);
                    $order['goodsprice'] = sprintf("%.2f" , ($order['goodsprice'] + ($data['vipdiscount'] * $order['num'])));
                }
                else {
                    $data['vipdiscount'] = 0;
                }
                $data['goodName']   = $goods['title'];
                $data['goodLogo']   = tomedia($goods['logo']);
                $data['optionName'] = $goods['sub_title'];
                $data['goodsid']    = $order['fkid'];
                $data['pluginno']   = 5;
            }else if($plugin == 'yellowpage'){
                $yellow = pdo_get('wlmerchant_yellowpage_lists' , ['id' => $order['fkid']]);
                switch ($order['fightstatus']) {
                    case '1':
                        $data['goodName'] = '页面['.$yellow['name'].']认领';
                        break;
                    case '2':
                        $data['goodName'] = '页面['.$yellow['name'].']查阅';
                        break;
                    case '3':
                        $data['goodName'] = '页面['.$yellow['name'].']入驻';
                        break;
                    case '4':
                        $data['goodName'] = '页面['.$yellow['name'].']续费';
                        break;
                }
                $data['goodLogo'] = tomedia($yellow['logo']);
                $data['goodsurl'] = h5_url('pages/subPages2/phoneBook/logistics/logistics',array('id'=>$order['fkid']));
            }else if($plugin == 'activity'){
                $goods            = pdo_get('wlmerchant_activitylist' , ['id' => $order['fkid']]);
                $data['goodName'] = $goods['title'];
                $data['goodLogo'] = tomedia($goods['thumb']);
                if ($order['specid']) {
                    $option             = pdo_get('wlmerchant_activity_spec' , ['id' => $order['specid']] , ['name','price']);
                    $data['optionName'] = $option['name'];
                }
                if ($order['vipbuyflag']) {
                    $data['vipdiscount'] = sprintf("%.2f",$goods['vipprice']);
                }
                else {
                    $data['vipdiscount'] = 0;
                }
                $data['goodsid']  = $order['fkid'];
                $data['pluginno'] = 'activity';
                $data['addresstype'] = $goods['addresstype'];
                if($data['addresstype'] > 0){
                    $data['activityAddress'] = $goods['address'];
                    $data['activityLng'] = $goods['lng'];
                    $data['activityLat'] = $goods['lat'];
                }
            }else if($plugin == 'hotel'){
                $room = pdo_get('wlmerchant_hotel_room',array('id' => $order['fkid']),array('name','thumb','roomtype'));
                $data['goodsname'] = $room['name'];
                $data['goodsimg'] = tomedia($room['thumb']);
                $data['roomtype'] = $room['roomtype'];

                $data['time'] = date('y/m/d',$order['starttime']).'-'.date('y/m/d',$order['endtime']);
                $data['deposit'] = $order['deposit'];

                if($order['vipbuyflag']>0){
                    $data['vipdiscount'] = $order['vipdiscount'];
                }
            }
            $data['unitPrice']  = sprintf("%.2f" , $order['goodsprice'] / $order['num']);
            $data['thname']     = $order['name'] ? $order['name'] : $_W['wlmember']['nickname'];
            $data['thmobile']   = $order['mobile'] ? $order['mobile'] : $_W['wlmember']['mobile'];
            $data['goodsprice'] = $order['goodsprice'];
            $data['usecredit'] = $order['usecredit'];
            $data['creditmoney'] = $order['cerditmoney'];
            if ($data['vipdiscount'] > 0) {
                $data['vipdiscount'] = sprintf("%.2f" , $data['vipdiscount'] * $order['num']);
            }
            $data['actualprice'] = $order['price'];
            $data['a']           = 'a';
            $data['buyremark']   = $order['buyremark'];  //买家备注
            $data['adminremark'] = $order['remark']; //卖家备注
        }
        if($order['redpackid']){
            $data['redpackcount'] = $order['redpackmoney'];
        }
        //判断用户
        if ($_W['mid'] != $order['mid']) {
            $this->renderError('当前mid['.$_W['mid'].']订单mid['.$order['mid'].'],用户错误,无法访问');
        }
        //同时开通会员卡
        if ($order['vip_card_id'] > 0) {
            $data['cardprice'] = pdo_getcolumn(PDO_NAME . 'halfcard_type' , ['id' => $order['vip_card_id']] , 'price');
        }
        //基础信息
        $data['sid']        = $order['sid'];
        $data['num']        = $order['num'];
        $data['orderno']    = $order['orderno'];
        $data['status']     = $order['status'];
        $data['createtime'] = date('Y-m-d H:i:s' , $order['createtime']);
        if ($data['status'] != 0 && $data['status'] != 5) {
            $paytype         = [1 => '余额支付' , 2 => '微信支付' , 3 => '支付宝支付' , 4 => '货到付款' , 5 => '小程序支付' , 6 => '0元购',7 => '混合支付'];
            $data['paytype'] = $paytype[$order['paytype']];
            $data['paytime'] = date('Y-m-d H:i:s' , $order['paytime']);
        }
        //是否可退款
        if (empty($goods['allowapplyre']) && ($order['status'] == 1 || $order['status'] == 8 || $order['status'] == 9) && $plugin != 'consumption') {
            if ($order['status'] == 8) {
                $canre = pdo_getcolumn(PDO_NAME . 'aftersale' , [
                    'orderid' => $id ,
                    'plugin'  => $plugin ,
                    'status'  => [1 , 2]
                ] , 'id');
                if ($canre) {
                    $data['surerefund'] = 0;
                    $data['status']     = 10;
                }
                else {
                    $data['surerefund'] = 1;
                }
            }
            else {
                $data['surerefund'] = 1;
            }
        }
        else {
            $data['surerefund'] = 0;
        }
        if ($order['status'] == 1) {  //待使用
            $smallorder           = pdo_getall('wlmerchant_smallorder' , ['orderid' => $id , 'plugin' => $plugin] , [
                'checkcode' ,
                'status',
                'appointstatus'
            ]);
            if(empty($smallorder) && $plugin == 'rush' && empty($goods['pftid'])){
                Order::createSmallorder($id,1);
            }
            $data['checkcodes']   = $smallorder;  //核销码
            if($goods['appointstatus'] > 0){
                $data['codeNum'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_smallorder') . " WHERE plugin = '{$plugin}' AND  orderid = {$id} AND status = 1 AND appointstatus = 3");  //剩余数量
            }else{
                $data['codeNum'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_smallorder') . " WHERE plugin = '{$plugin}' AND  orderid = {$id} AND status = 1");  //剩余数量
            }
            $data['estimatetime'] = date('Y-m-d H:i' , $order['estimatetime']);
            $canuse               = pdo_getcolumn(PDO_NAME . 'smallorder' , [
                'orderid' => $id ,
                'plugin'  => $plugin ,
                'status'  => 1
            ] , 'id');
            if (empty($canuse) && empty($goods['pftid'])) {
                $data['status'] = 10;
            }
        }
        else if ($order['status'] == 0) {  //待付款
            $data['sytime'] = $order['canceltime'];
            if (empty($data['sytime'])) {
                $data['sytime'] = time() - 1;
            }
            //云收单校验
            if(Customized::init('yunmis160') > 0){
                $paylog = pdo_get('wlmerchant_paylogvfour' , ['tid' => $order['orderno']] , ['batchNo','traceNo']);
                if(!empty($paylog['batchNo']) && !empty($paylog['traceNo'])){
                    $set = Setting::wlsetting_read("payment_set");
                    $payid = $set['wechat']['wechat'];
                    $paySetInfo = json_decode(pdo_getcolumn(PDO_NAME."payment",['id'=>$id],'param'),true);
                    $geturl = 'https://epos.ahrcu.com:3443/cposp/pay/nativeOrderQuery';
                    $wechatData = [
                        'merchantNo' => $paySetInfo['yun_merchantNo'],
                        'terminalNo' => $paySetInfo['yun_terminalNo'],
                        'batchNo'    => $paylog['batchNo'],
                        'traceNo'    => $paylog['traceNo'],
                        'nonceStr'   => random(16)
                    ];
                    #3、处理支付方式
                    $wechatData['sign'] = Payment::getYunSign($wechatData,$paySetInfo['yun_KEY']);
                    $wechatData = json_encode($wechatData);
                    $queryInfo = curlPostRequest($geturl,$wechatData,["Content-type: application/json;charset='utf-8'"]);
                    if($queryInfo['resultCode'] != '00'){
                        Util::wl_log('160PayApi.log',PATH_DATA,$queryInfo); //写入异步日志记录
                    }else{
                        if($queryInfo['orderStatus'] == 3){
                            $successInfo = [
                                'type'           => 2 ,//支付方式
                                'tid'            => $order['orderno'],//订单号
                                'transaction_id' => $queryInfo['chnOrderId'] ,
                                'time'           => $queryInfo['transTime'],
                            ];
                            PayResult::main($successInfo);//调用方法处理订单
                        }
                    }
                }
            }
        }
        else if ($order['status'] == 7) {
            $data['refundtime'] = date('Y-m-d H:i' , $order['refundtime']);
        }
        //使用方式
        //卡密商品
        if( $goods['usestatus'] == 3){
            $data['usestatus'] = 3;
            if(empty($smallorder)){
                $smallorder           = pdo_getall('wlmerchant_smallorder' , ['orderid' => $id , 'plugin' => $plugin] , [
                    'checkcode' ,
                    'status',
                    'appointstatus'
                ]);
                $data['checkcodes']   = $smallorder;  //核销码
            }
            $merchant          = pdo_get('wlmerchant_merchantdata' , ['id' => $order['sid']] , [
                'mobile' ,
                'storename' ,
                'address' ,
                'location' ,
                'verkey'
            ]);
            $data['storename'] = $merchant['storename'];
            $data['mobile']    = $merchant['mobile'];
            $data['address']   = $merchant['address'];
            $location          = unserialize($merchant['location']);
            $data['location']  = $location;
            //计算距离
            if (!empty($lat) && !empty($lng)) {
                $data['distance'] = Store::getdistance($location['lng'] , $location['lat'] , $lng , $lat);
                if ($data['distance'] > 1000) {
                    $data['distance'] = (floor(($data['distance'] / 1000) * 10) / 10) . "km";
                }
                else {
                    $data['distance'] = round($data['distance']) . "m";
                }
            }
        }else if (empty($order['expressid'])) {
            $data['usestatus'] = 1; //使用方式 核销
            $merchant          = pdo_get('wlmerchant_merchantdata' , ['id' => $order['sid']] , [
                'mobile' ,
                'storename' ,
                'address' ,
                'location' ,
                'verkey'
            ]);
            $data['storename'] = $merchant['storename'];
            $data['mobile']    = $merchant['mobile'];
            $data['address']   = $merchant['address'];
            $location          = unserialize($merchant['location']);
            $data['location']  = $location;
            //计算距离
            if (!empty($lat) && !empty($lng)) {
                $data['distance'] = Store::getdistance($location['lng'] , $location['lat'] , $lng , $lat);
                if ($data['distance'] > 1000) {
                    $data['distance'] = (floor(($data['distance'] / 1000) * 10) / 10) . "km";
                }
                else {
                    $data['distance'] = round($data['distance']) . "m";
                }
            }
            //密码核销
            if (!empty($merchant['verkey'])) {
                $data['verkeystatus'] = 1;
            }
            else {
                $data['verkeystatus'] = 0;
            }
            //扫码核销
            if ($_W['source'] == 3) {
                $showurl        = 'pages/mainPages/orderWrite/orderWrite?id=' . $id . '&plugin=' . $plugin;
                $logo           = tomedia(pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $order['sid']] , 'logo'));
                $data['qrcode'] = tomedia(Store::getShopWxAppQrCode(0 , $logo , $showurl , true));
            }
            else {
                $data['qrcode'] = WeliamWeChat::getQrCode(h5_url('pages/mainPages/orderWrite/orderWrite' , [
                    'id'     => $id ,
                    'plugin' => $plugin
                ]));
            }
            //预约
            if($goods['appointstatus'] > 0){
                $data['appointstatus'] = 1;
                $data['appointNum'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_smallorder') . " WHERE plugin = '{$plugin}' AND  orderid = {$id} AND status = 1 AND appointstatus = 1");
                $data['alAppointFlag'] = pdo_getcolumn(PDO_NAME.'appointlist',array('orderid'=>$id,'type'=>$data['pluginno']),'id');
            }
        }
        else {
            $data['usestatus']  = 2; //使用方式 快递
            $express            = pdo_get('wlmerchant_express' , ['id' => $order['expressid']] , [
                'name' ,
                'tel' ,
                'address' ,
                'expressprice' ,
                'expressname' ,
                'expresssn'
            ]);
            $data['expName']    = $express['name'];
            $data['expTel']     = $express['tel'];
            $data['expAddress'] = $express['address'];
            //快递
            $data['expressname']  = Logistics::codeComparisonTable($express['expressname'] , 'alias')['name'];
            $data['expresssn']    = $express['expresssn'];
            $data['expressprice'] = $express['expressprice'];  //运费
        }
        //处理返回值
        if (empty($data['expressprice'])) {
            $data['expressprice'] = 0;
        }
        if (empty($data['vipdiscount'])) {
            $data['vipdiscount'] = 0;
        }
        if (empty($data['creditmoney'])) {
            $data['creditmoney'] = 0;
        }
        $data['com_dis_price'] = $order['com_dis_price'] > 0 ? $order['com_dis_price'] : 0;
        $data['fullreducemoney'] = $order['fullreducemoney'] > 0 ? $order['fullreducemoney'] : 0;
        //自定义表单信息处理
        $moinfo = unserialize($order['moinfo']);
        if($moinfo){
            foreach($moinfo as &$moinfoItem){
                if($moinfoItem['id'] == 'img'){
                    foreach($moinfoItem['data'] as $imgKey => $imgLink){
                        $moinfoItem['data'][$imgKey] = tomedia($imgLink);
                    }
                }
            }
            $data['moinfo'] = $moinfo;
        }
        //票付通处理
        if($goods['pftid'] > 0 && $order['status'] == 1 && empty($goods['threestatus'])){
            $data['pftinfo'] = unserialize($order['pftorderinfo']);
            $data['pftflag'] = 1;
            if(empty($data['pftinfo']['UUcode'])){
                $moreinfo = Pftapimod::pftOrderQuery($data['pftinfo']['UUordernum']);
                $data['pftinfo']['UUcode'] = $moreinfo['UUcode'];
                $newinfo = serialize($data['pftinfo']);
                if ($plugin == 'rush') {
                    pdo_update('wlmerchant_rush_order',array('pftorderinfo' => $newinfo),array('id' => $id));
                }else{
                    pdo_update('wlmerchant_order',array('pftorderinfo' => $newinfo),array('id' => $id));
                }
            }
        }
        //退款提示
        if($data['status'] == 10){
            $ordersettings = Setting::wlsetting_read('orderset');
            $data['tiptext'] = $ordersettings['refundtip'] ? : '请等待系统退款';
        }

        $this->renderSuccess('订单详情' , $data);
    }
    /**
     * Comment: 订单支付成功
     * Author: wlf
     * Date: 2019/8/19 16:23
     */
    public function payOver()
    {
        global $_W , $_GPC;
        $tid    = $_GPC['tid'];
        $paylog = pdo_get('wlmerchant_paylogvfour' , ['tid' => $tid] , ['plugin' ,'plid','fee','payfor']);
        if ($paylog) {
            $type           = strtolower($paylog['plugin']);
            $payfor         = strtolower($paylog['payfor']);
            $data           = [];
            $data['plugin'] = $type;
            if ($type == 'rush') {
                $order         = pdo_get('wlmerchant_rush_order' , ['orderno' => $tid] , ['id' , 'paytype' , 'paidprid' , 'actualprice' , 'vip_card_id' , 'expressid']);
                $data['price'] = $order['actualprice'];
            }
            else if ($type == 'merchant' && $payfor == 'halfcard') {
                $order         = pdo_get('wlmerchant_halfcard_record' , ['orderno' => $tid] , ['id' , 'paytype' , 'paidprid' , 'price']);
                $data['price'] = $order['price'];
            }
            else if ($type == 'attestation') {
                $order         = pdo_get('wlmerchant_attestation_money' , ['orderno' => $tid] , ['id' , 'paytype' , 'money' , 'type']);
                $data['price'] = $order['money'];
                $data['type']  = $order['type'];
            }
            else {
                $order         = pdo_get('wlmerchant_order' , ['orderno' => $tid] , ['id' , 'recordid' , 'fkid' , 'plugin' , 'fightstatus' , 'paytype' , 'paidprid' , 'price' , 'vip_card_id' , 'expressid']);

                $data['price'] = $order['price'];

                if ($order['plugin'] == 'house') {
                    $data['house_id'] = $order['fkid'];
                    $data['type'] = $order['fightstatus'];
                }

                if ($order['plugin'] == 'wlfightgroup') {
                    $data['fightgoodsid'] = $order['fkid'];
                }
                if ($order['plugin'] == 'coupon') {
                    $data['couponid'] = $order['recordid'];
                }
                if ($order['plugin'] == 'taxipay') {
                    $data['masid'] = $order['fkid'];
                    $info = DiyPage::defaultinfo('options');
                    $data['tabinfo'] = $info;
                    $areaid = pdo_getcolumn(PDO_NAME.'oparea',array('uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']),'areaid');
                    $data['tabtitle'] = pdo_getcolumn(PDO_NAME.'area',array('id'=>$areaid),'name');
                    $data['tabtitle'] .= '精选';
                    $data['noticeurl'] = $_W['siteroot'].'addons/weliam_smartcity/plugin/taxipay/sys/resource/taxinotice.mp3';
                }
                if(empty($order) && $data['plugin'] == 'citydelivery'){
                    $data['price'] = $paylog['fee'];
                    $order = pdo_get('wlmerchant_order' , ['paylogid' => $paylog['plid']] , ['id' , 'recordid' , 'fkid' , 'plugin' , 'fightstatus' , 'paytype' , 'paidprid' , 'price' , 'vip_card_id' , 'expressid']);
                }
            }
        }else {
            $order = pdo_get('wlmerchant_rush_order' , ['orderno' => $tid] , ['id' , 'paytype' , 'paidprid' , 'actualprice' , 'vip_card_id', 'expressid']);
            if (empty($order)) {
                $order          = pdo_get('wlmerchant_order' , ['orderno' => $tid] , ['id' , 'recordid' , 'fkid' , 'plugin' , 'fightstatus' , 'paytype' , 'paidprid' , 'price' , 'vip_card_id', 'expressid']);
                $data['price']  = $order['price'];
                $data['plugin'] = $order['plugin'];
                if ($order['plugin'] == 'wlfightgroup') {
                    $data['fightgoodsid'] = $order['fkid'];
                }
                if ($order['plugin'] == 'coupon') {
                    $data['couponid'] = $order['recordid'];
                }
                if ($order['plugin'] == 'halfcard') {
                    $data['plugin'] = 'payonline';
                }
            }else {
                $data['price']  = $order['actualprice'];
                $data['plugin'] = 'rush';
            }
        }
        if ($type == 'merchant') {
            $data['plugin'] = $payfor;
        }
        $paytype         = [1 => '余额支付' , 2 => '微信支付' , 3 => '支付宝支付' , 4 => '货到付款' , 5 => '小程序支付' , 6 => '0元购'];
        $data['paytype'] = $paytype[$order['paytype']];
        //支付有礼
        if ($order['paidprid']) {
            $paid      = pdo_get('wlmerchant_paidrecord' , ['id' => $order['paidprid']]);
            $pactivity = pdo_get('wlmerchant_payactive' , ['id' => $paid['activeid']]);
            if ($pactivity['giftstatus'] == 1) {
                $couponIdList = explode(',' , $pactivity['giftcouponid']);
                if (is_array($couponIdList)) {
                    foreach ($couponIdList as $key => $val) {
                        $couponNameList[$key] = pdo_getcolumn(PDO_NAME . 'couponlist' , ['id' => $val] , 'title');
                    }
                }
                $data['couponlist'] = $couponNameList;
            }
            if ($paid['codeid'] && $pactivity['giftstatus'] == 2) {
                $code         = pdo_get(PDO_NAME . 'token' , ['id' => $paid['codeid']] , ['number' , 'status']);
                $data['code'] = $code['number'];
            }
            if ($pactivity['giftstatus'] == 3) {
                $redPackList = [];
                $redPackIds = explode(',' , $pactivity['giftredpack']);
                if (is_array($redPackIds)) {
                    $field = ['id','title','full_money','cut_money','use_start_time','use_end_time','usetime_day1','usetime_day2','usegoods_type','usetime_type','limit_count','use_aids','use_sids'];
                    foreach ($redPackIds as $key => $val) {
                        $redpack = pdo_get(PDO_NAME."redpacks",['id'=>$val],$field);
                        if(!$redpack) continue;
                        //当前用户剩余可以领取的数量  删除用户不能领取的红包信息  开启后 则只显示用户可以领取的红包
                        if (!empty($redpack['limit_count'])) {
                            $mycounts = Redpack::getReceiveTotal($redpack['id'],$_W['mid'],1,0);
                            if ($mycounts >= $redpack['limit_count']){
                                $redpack['is_over'] = 1;
                            }
                        }
                        //价格处理
                        $redpack['full_money'] = sprintf("%0.2f",$redpack['full_money']);
                        $redpack['cut_money'] = sprintf("%0.2f",$redpack['cut_money']);
                        //有效期处理
                        $usetimes            = [
                            date('Y-m-d' , $redpack['use_start_time']) . ' ~ ' . date('Y-m-d' , $redpack['use_end_time']) ,
                            '领取当日起' . $redpack['usetime_day1'] . '天内有效' ,
                            '领取次日起' . $redpack['usetime_day2'] . '天内有效'
                        ];
                        $redpack['usetime_text'] = $usetimes[$redpack['usetime_type']];
                        //使用条件处理  0全平台1指定代理2指定商家
                        if ($redpack['usegoods_type'] == 1) {
                            //代理商可用  查询可用代理商信息
                            $aids = unserialize($redpack['use_aids']);
                            $agents = pdo_getall(PDO_NAME."oparea",['aid'=>$aids],'areaid');
                            if($agents) $areaInfo = pdo_getall(PDO_NAME."area",['id'=>array_values(array_column($agents,'areaid'))],'name');
                            if($areaInfo){
                                $areaName = implode(',',array_column($areaInfo,'name'));
                                $redpack['use_where'] = "仅限{$areaName}代理可用";
                            }else{
                                $redpack['use_where'] = "仅限指定地区可用";
                            }
                        }else if ($redpack['usegoods_type'] == 2) {
                            //商家可用  查询可用商家信息
                            $sids = unserialize($redpack['use_sids']);
                            $storeName = pdo_getall(PDO_NAME."merchantdata",['id'=>$sids],'storename');
                            if($storeName){
                                $storeName = implode(',',array_column($storeName,'storename'));
                                $redpack['use_where'] = "仅限{$storeName}商家可用";
                            }else{
                                $redpack['use_where'] = "仅限指定商家可用";
                            }
                        }else if ($redpack['usegoods_type'] == 3) {
                            //指定商品可用  商品过多,直接显示固定内容
                            $redpack['use_where'] = "仅限指定商品可用";
                        }else {
                            $redpack['use_where'] = '全平台可用';
                        }
                        //删除多余的信息
                        unset($redpack['usegoods_type']);
                        unset($redpack['use_start_time']);
                        unset($redpack['use_end_time']);
                        unset($redpack['usetime_day1']);
                        unset($redpack['usetime_day2']);
                        unset($redpack['usetime_type']);

                        $redPackList[$key] = $redpack;
                    }
                }
                $data['redpacklist'] = $redPackList;
            }
            $data['getcouflag'] = $paid['getcouflag'];
            $data['integral']   = $paid['integral'];
            $data['balance']    = $paid['balance'];
            $data['thumb']      = tomedia($paid['img']);
            $data['thumburl']   = $pactivity['advurl'];
            $data['paidprid']   = $order['paidprid'];
        }else{
            $thumbs = Dashboard::getAllAdv(0,10,1,21);
            if(!empty($thumbs['data'])){
                foreach($thumbs['data'] as $adv){
                    $advs['thumburl'] = $adv['link'];
                    $advs['thumb'] = tomedia($adv['thumb']);
                    $data['advs'][] = $advs;
                }
            }
        }
        if($data['plugin'] == 'citydelivery' && !empty($paylog['plid'])){
            $data['orderid'] = $paylog['plid'];
        }else{
            $data['orderid'] = $order['id'];
        }
        if ($data['plugin'] == 'pocket') {
            $data['tieziid'] = $order['fkid'];
            if($order['fightstatus'] == 3 || $order['fightstatus'] == 5 || $order['fightstatus'] == 7){
                $data['gopocket'] = 1;
            }else{
                $data['gopocket'] = 0;
            }
        }
        if ($data['plugin'] == 'wlfightgroup') {
            $data['fightstatus'] = $order['fightstatus'];
        }
        //求职招聘
        if ($data['plugin'] == 'recruit') {
            $data['recruit_id'] = $order['fkid'];
            $recruit = pdo_get(PDO_NAME."recruit_recruit",['id'=>$order['fkid']]);
            $data['release_sid'] = $recruit['release_sid'] ? : 0;
        }
        //支付成功页面 小程序订阅消息type获取
        if ($_W['source'] == 3) {
            //2=回到首页、查看订单按钮 - 拼团商品 - 发货订单【拼团进度通知/订单发货通知】
            //3=回到首页、查看订单按钮 - 拼团商品 - 核销、自提订单【拼团进度通知/核销成功通知】
            //4=回到首页、查看订单按钮 - 非拼团商品 - 发货订单【订单发货通知】
            //5=回到首页、查看订单按钮 - 非拼团商品 - 核销、自提订单【核销成功通知】
            if ($data['plugin'] == 'wlfightgroup') {
                if ($order['expressid'] > 0) $data['temp_type'] = intval(2);
                else $data['temp_type'] = intval(3);
            }else if (in_array($data['plugin'] , ['rush' , 'groupon' , 'bargain' , 'coupon' , 'consumption'])) {
                if ($order['expressid'] > 0) $data['temp_type'] = intval(4);
                else $data['temp_type'] = intval(5);
            }
        }

        $this->renderSuccess('支付完成' , $data);
    }
    /**
     * Comment: 领取支付有礼卡券
     * Author: wlf
     * Date: 2019/8/19 17:00
     */
    public function getCoupon()
    {
        global $_W , $_GPC;
        $id   = $_GPC['id'];
        $paid = pdo_get(PDO_NAME . 'paidrecord' , ['id' => $id]);
        if ($paid['getcouflag']) {
            $this->renderError('您已成功领取,无法重复领取');
        }
        $pactivity    = pdo_get(PDO_NAME . 'payactive' , ['id' => $paid['activeid']]);
        $couponIdList = explode(',' , $pactivity['giftcouponid']);
        #3、通过循环判断信息
        if (is_array($couponIdList)) {
            $acresult = '';//优惠券领取状态
            foreach ($couponIdList as $k => $v) {
                $coupons = wlCoupon::getSingleCoupons($v , '*');
                $num     = wlCoupon::getCouponNum($v , 1);
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
                        $acresult = '[失败]只能领取' . $coupons['get_limit'] . '张';
                    }
                }
                //领取状态为空  无异常 开始正常的领取操作
                if (empty($acresult)) {
                    //用户领取卡券的操作
                    if ($coupons['time_type'] == 1) {
                        $starttime = $coupons['starttime'];
                        $endtime   = $coupons['endtime'];
                    }
                    else {
                        $starttime = time();
                        $endtime   = time() + ($coupons['deadline'] * 24 * 3600);
                    }
                    if (empty($coupons['is_charge'])) {
                        $coupons['price'] = 0;
                        $settlementmoney  = 0;
                    }
                    else {
                        //结算金额
                        $settlementmoney = Store::getsettlementmoney(4 , $coupons['id'] , 1 ,$coupons['merchantid'] , 0);
                    }
                    //生成领取订单
                    $orderdata = [
                        'uniacid'         => $coupons['uniacid'] ,
                        'mid'             => $_W['mid'] ,
                        'aid'             => $coupons['aid'] ,
                        'fkid'            => $coupons['id'] ,
                        'sid'             => $coupons['merchantid'] ,
                        'status'          => 1 ,
                        'paytype'         => 6 ,
                        'createtime'      => time() ,
                        'orderno'         => createUniontid() ,
                        'price'           => 0 ,
                        'num'             => 1 ,
                        'plugin'          => 'coupon' ,
                        'payfor'          => 'couponsharge' ,
                        'vipbuyflag'      => 0 ,
                        'goodsprice'      => $coupons['price'],
                        'settlementmoney' => $settlementmoney ,
                        'neworderflag'    => 1 ,
                        'buyremark'       => '支付有礼赠送卡券,记录号:' . $id ,
                        'paytime'         => time()
                    ];
                    $orderid   = wlCoupon::saveCouponOrder($orderdata);
                    Order::createSmallorder($orderid , 4);
                    //生成卡券
                    $data                    = [
                        'mid'         => $_W['mid'] ,
                        'aid'         => $_W['aid'] ,
                        'parentid'    => $coupons['id'] ,
                        'status'      => 1 ,
                        'type'        => $coupons['type'] ,
                        'title'       => $coupons['title'] ,
                        'sub_title'   => $coupons['sub_title'] ,
                        'content'     => $coupons['goodsdetail'] ,
                        'description' => $coupons['description'] ,
                        'color'       => $coupons['color'] ,
                        'starttime'   => $starttime ,
                        'endtime'     => $endtime ,
                        'createtime'  => time() ,
                        'usetimes'    => $coupons['usetimes'] ,
                        'concode'     => 0 ,
                        'uniacid'     => $_W['uniacid'] ,
                        'orderno'     => $orderdata['orderno']
                    ];
                    $res                     = pdo_insert(PDO_NAME . 'member_coupons' , $data);
                    $couponUserId            = pdo_insertid();
                    $newdata['recordid']     = $couponUserId;
                    $newdata['estimatetime'] = $data['endtime'];
                    pdo_update(PDO_NAME . 'order' , $newdata , ['id' => $orderid]); //更新订单状态
                    if ($res) {
                        //修改卡券的已售数量
                        $newsurplus = $coupons['surplus'] + 1;
                        wlCoupon::updateCoupons(['surplus' => $newsurplus] , ['id' => $v]);
                        $url      = h5_url('pages/subPages/coupon/coupon' , [
                            'id'       => $couponUserId ,
                            'order_id' => $orderid
                        ]);
                        $acresult = '[成功]领取成功';
                    }
                    else {
                        $acresult = '[失败]领取失败';
                    }
                }
                $messagedata = [
                    'first'   => '“' . $coupons['title'] . '”领取结果通知' ,
                    'type'    => '支付有礼-卡券领取' ,//业务类型
                    'content' => '领取人:' . $_W['wlmember']['nickname'] ,//业务内容
                    'status'  => $acresult ,//处理结果
                    'time'    => date('Y-m-d H:i:s' , time()) ,//操作时间
                    'remark'  => '点击查看我的卡券'
                ];
                TempModel::sendInit('service' , $_W['mid'] , $messagedata , $_W['source'] , $url);
                $acresult = '';//清除领取状态
            }
        }
        pdo_update(PDO_NAME . 'paidrecord' , ['getcouflag' => 1 , 'getcoutime' => time()] , ['id' => $id]);
        $this->renderSuccess('领取成功');
    }
    /**
     * Comment: 取消订单
     * Author: wlf
     * Date: 2019/8/19 17:27
     */
    public function cancelOrder()
    {
        global $_W , $_GPC;
        $id     = $_GPC['id'];
        $plugin = $_GPC['plugin'];
        if ($plugin != 'rush') {
            $order = pdo_get(PDO_NAME . 'order' , ['id' => $id] ,['mid','plugin','redpackid','usecredit']);
            $plugin = $order['plugin'];
            if ($plugin == 'groupon') {
                $res = Groupon::cancelorder($id);
            }
            else if ($plugin == 'bargain') {
                $res = Bargain::cancelorder($id);
            }else if($plugin == 'wlfightgroup'){
                $res = Wlfightgroup::cancelorder($id);
            }else if($plugin == 'coupon'){
                $res = wlCoupon::cancelorder($id);
            }
            else {
                $res = pdo_update('wlmerchant_order',['status' => 5],['id' => $id]);
                if($order['redpackid']){
                    pdo_update('wlmerchant_redpack_records',['status' => 0],['id' => $order['redpackid']]);
                }
                if($order['usecredit'] > 0){
                    if($plugin == 'halfcard'){
                        $pluginname = '在线买单';
                    }
                    Member::credit_update_credit1($order['mid'], $order['usecredit'], '取消'.$pluginname.'订单返还积分');
                }
            }
        }
        else {
            $res = Rush::cancelorder($id);
        }
        if ($res) {
            $this->renderSuccess('取消成功');
        }
        else {
            $this->renderError('取消失败');
        }
    }
    /**
     * Comment: 核销订单页面
     * Author: wlf
     * Date: 2019/8/20 14:27
     */
    public function selfUse()
    {
        global $_W , $_GPC;
        $id     = $_GPC['id'];
        $plugin = $_GPC['plugin'];
        //获取商品
        if ($plugin == 'rush') {
            $order = pdo_get('wlmerchant_rush_order' , ['id' => $id] , ['activityid' , 'optionid' , 'mid' , 'sid']);
            $goods = pdo_get('wlmerchant_rush_activity' , ['id' => $order['activityid']] , ['name','appointstatus', 'thumb']);
            if ($order['optionid']) {
                $optionname = pdo_getcolumn(PDO_NAME . 'goods_option' , ['id' => $order['optionid']] , 'title');
            }
            $data['goodsName']  = $goods['name'];
            $data['goodsLogo']  = tomedia($goods['thumb']);
            $data['optionName'] = $optionname ? : '';
        }
        else {
            $order = pdo_get('wlmerchant_order' , ['id' => $id] , ['fkid' , 'specid' , 'mid' , 'sid']);
            if ($plugin == 'groupon') {
                $goods             = pdo_get('wlmerchant_groupon_activity' , ['id' => $order['fkid']] , ['name','appointstatus' , 'thumb']);
                $data['goodsName'] = $goods['name'];
                $data['goodsLogo'] = tomedia($goods['thumb']);
            }
            else if ($plugin == 'coupon') {
                $goods             = pdo_get('wlmerchant_couponlist' , ['id' => $order['fkid']] , [
                    'title' ,
                    'logo' ,
                    'sub_title'
                ]);
                $data['goodsName'] = $goods['title'];
                $data['goodsLogo'] = tomedia($goods['logo']);
                $optionname        = $goods['sub_title'];
            }
            else if ($plugin == 'wlfightgroup') {
                $goods             = pdo_get('wlmerchant_fightgroup_goods' , ['id' => $order['fkid']] , ['name','appointstatus' , 'logo']);
                $data['goodsName'] = $goods['name'];
                $data['goodsLogo'] = tomedia($goods['logo']);
            }
            else if ($plugin == 'bargain') {
                $goods             = pdo_get('wlmerchant_bargain_activity' , ['id' => $order['fkid']] , ['name','appointstatus','thumb']);
                $data['goodsName'] = $goods['name'];
                $data['goodsLogo'] = tomedia($goods['thumb']);
            }
            else if ($plugin == 'activity') {
                $goods             = pdo_get('wlmerchant_activitylist' , ['id' => $order['fkid']] , [
                    'title' ,
                    'thumb'
                ]);
                $data['goodsName'] = $goods['title'];
                $data['goodsLogo'] = tomedia($goods['thumb']);
            }
            else if ($plugin == 'hotel') {
                $goods             = pdo_get('wlmerchant_hotel_room' , ['id' => $order['fkid']] , [
                    'name' ,
                    'thumb'
                ]);
                $data['goodsName'] = $goods['name'];
                $data['goodsLogo'] = tomedia($goods['thumb']);
            }
            if ($order['specid'] && $plugin != 'bargain') {
                if($plugin == 'activity'){
                    $optionname = pdo_getcolumn(PDO_NAME . 'activity_spec' , ['id' => $order['specid']] , 'name');
                }else{
                    $optionname = pdo_getcolumn(PDO_NAME . 'goods_option' , ['id' => $order['specid']] , 'title');
                }
            }
            $data['optionName'] = $optionname ? : '';
        }
        $verkey = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $order['sid']] , 'verkey');
        //密码还是扫码核销
        if ($_W['mid'] == $order['mid'] && !empty($verkey)) {  //密码核销
            $data['useStatua'] = 1;
        }
        if (empty($data['useStatua'])) {
            $verifier = SingleMerchant::verifier($order['sid'] , $_W['mid']);
            if (!$verifier) {
                $this->renderError('非核销员:mid['.$_W['mid'].']'.'sid['.$order['sid'].']' , ['url' => h5_url('pages/mainPages/index/index')]);
            }
            else {
                $data['useStatua'] = 2;
            }
        }
        //获取核销码
        if($goods['appointstatus'] > 0){
            $smalls = pdo_getall('wlmerchant_smallorder' , ['orderid' => $id , 'plugin' => $plugin,'status' => 1,'appointstatus' => 3,'appstarttime <' => time(),'appendtime >' => time()] , ['checkcode','status','id']);
            if(empty($smalls)){
                $this->renderError('订单未预约或未到预约时间' , ['url' => h5_url('pages/mainPages/index/index')]);
            }
        }else{
            $smalls = pdo_getall('wlmerchant_smallorder' , ['orderid' => $id , 'plugin' => $plugin,'status' => 1] , ['checkcode','status','id']);
        }
        $data['checkcodes'] = $smalls;
        $this->renderSuccess('核销详情' , $data);
    }
    /**
     * Comment: 使用核销码
     * Author: wlf
     * Date: 2019/8/20 16:27
     */
    public function useCheckcode()
    {
        global $_W , $_GPC;
        $ids       = $_GPC['ids'];
        $ids       = explode(',' , $ids);
        $usestatus = $_GPC['usestatus'];
        $verkey    = trim($_GPC['verkey']);
        if ($ids[0] > 0) {
            $ordertem = pdo_get(PDO_NAME . 'smallorder' , ['id' => $ids[0]]);
            if ($usestatus == 1) {  //密码核销
                $storeverkey = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $ordertem['sid']] , 'verkey');
                if ($verkey != $storeverkey || empty($storeverkey)) {
                    $this->renderError('核销密码错误请重试');
                }
                $type = 4;
            }
            else if ($usestatus == 2) {  //扫码核销
                $verifier = SingleMerchant::verifier($ordertem['sid'] , $_W['mid']);
                if (!$verifier) {
                    $this->renderError('非核销员,无法核销');
                }
                $type = 2;
            }
            else {
                $this->renderError('无效的核销方式');
            }
            foreach ($ids as $id) {
                $order = pdo_get(PDO_NAME . 'smallorder' , ['id' => $id]);
                if ($order) {
                    if ($order['status'] == 1) {
                        if ($order['plugin'] == 'rush') {
                            $orderres = Rush::hexiaoorder($order['orderid'] , $_W['mid'] , 1 , $type , $order['checkcode']);
                        }
                        else if ($order['plugin'] == 'groupon') {
                            $orderres = Groupon::hexiaoorder($order['orderid'] , $_W['mid'] , 1 , $type , $order['checkcode']);
                        }
                        else if ($order['plugin'] == 'wlfightgroup') {
                            $orderres = Wlfightgroup::hexiaoorder($order['orderid'] , $_W['mid'] , 1 , $type , $order['checkcode']);
                        }
                        else if ($order['plugin'] == 'bargain') {
                            $orderres = Bargain::hexiaoorder($order['orderid'] , $_W['mid'] , 1 , $type , $order['checkcode']);
                        }
                        else if ($order['plugin'] == 'coupon') {
                            $couponid = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $order['orderid']] , 'recordid');
                            $orderres = wlCoupon::hexiaoorder($couponid , $_W['mid'] , 1 , $type , $order['checkcode']);
                        }else if ($order['plugin'] == 'activity') {
                            $orderres = Activity::hexiaoorder($order['orderid'] , $_W['mid'] , 1 , $type , $order['checkcode']);
                        }else if ($order['plugin'] == 'hotel') {
                            $orderres = Hotel::hexiaoorder($order['orderid'] , $_W['mid'] , 1 , $type , $order['checkcode']);
                        }
                    }
                }
            }
            if($orderres > 0){
                $this->renderSuccess('核销成功');
            }else{
                $this->renderError('核销失败或部分订单异常，请刷新重试');
            }
        }
        else {
            $this->renderError('请勾选核销码' , $ids);
        }
    }
    /**
     * Comment: 确认收货
     * Author: wlf
     * Date: 2019/8/20 18:05
     */
    public function sureReceive()
    {
        global $_W , $_GPC;
        $id   = $_GPC['id'];
        $type = $_GPC['plugin'];
        if (empty($id) || empty($type)) {
            $this->renderError('缺少重要参数');
        }
        $res = Order::sureReceive($id,$type);
        if($res){
            $this->renderSuccess('收货成功');
        }else{
            $this->renderError('收货失败,请重试');
        }
    }
    /**
     * Comment: 售后服务
     * Author: wlf
     * Date: 2019/8/21 17:49
     */
    public function afterSale()
    {
        global $_W , $_GPC;
        $id      = $_GPC['id'];
        $plugin  = $_GPC['plugin'];
        $afterid = $_GPC['afterid'];
        $data    = [];
        if ($plugin == 'rush') {
            $order            = pdo_get('wlmerchant_rush_order' , ['id' => $id] , [
                'activityid' ,
                'expressid' ,
                'num' ,
                'optionid' ,
                'actualprice',
                'pftorderinfo'
            ]);
            $goods            = pdo_get('wlmerchant_rush_activity' , ['id' => $order['activityid']] , [
                'name' ,
                'thumb',
                'pftid'
            ]);
            $data['goodName'] = $goods['name'];
            $data['goodLogo'] = tomedia($goods['thumb']);
            $data['type'][]   = 1;
            if ($order['optionid']) {
                $data['optionName'] = pdo_getcolumn(PDO_NAME . 'goods_option' , ['id' => $order['optionid']] , 'title');
            }
            $data['allprice'] = $order['actualprice'];
        }
        else {
            $order = pdo_get('wlmerchant_order' , ['id' => $id] , ['fkid' ,'orderno','num' , 'expressid' , 'specid' , 'price','pftorderinfo']);
            if ($plugin == 'groupon') {
                $goods            = pdo_get('wlmerchant_groupon_activity' , ['id' => $order['fkid']] , [
                    'name' ,
                    'thumb',
                    'pftid'
                ]);
                $data['goodName'] = $goods['name'];
                $data['goodLogo'] = tomedia($goods['thumb']);
                $data['type'][]   = 1;
            }
            else if ($plugin == 'wlfightgroup') {
                $goods            = pdo_get('wlmerchant_fightgroup_goods' , ['id' => $order['fkid']] , [
                    'name' ,
                    'logo'
                ]);
                $data['goodName'] = $goods['name'];
                $data['goodLogo'] = tomedia($goods['logo']);
                $data['type'][]   = 1;
            }
            else if ($plugin == 'coupon') {
                $goods              = pdo_get('wlmerchant_couponlist' , ['id' => $order['fkid']] , [
                    'title' ,
                    'logo' ,
                    'sub_title'
                ]);
                $data['goodName']   = $goods['title'];
                $data['goodLogo']   = tomedia($goods['logo']);
                $data['type'][]     = 1;
                $data['optionName'] = $goods['sub_title'];
            }
            else if ($plugin == 'bargain') {
                $goods            = pdo_get('wlmerchant_bargain_activity' , ['id' => $order['fkid']] , [
                    'name' ,
                    'thumb'
                ]);
                $data['goodName'] = $goods['name'];
                $data['goodLogo'] = tomedia($goods['thumb']);
                $data['type'][]   = 1;
            }else if ($plugin == 'activity') {
                $goods            = pdo_get('wlmerchant_activitylist' , ['id' => $order['fkid']] , [
                    'title' ,
                    'thumb'
                ]);
                $data['goodName'] = $goods['title'];
                $data['goodLogo'] = tomedia($goods['thumb']);
                $data['type'][]   = 1;
            }else if($plugin == 'citydelivery'){
                $smallorders = pdo_fetchall("SELECT gid,num,specid FROM ".tablename('wlmerchant_delivery_order')."WHERE orderid = {$id} ORDER BY price DESC");
                foreach ($smallorders  as $ke => $orr){
                    $goods = pdo_get('wlmerchant_delivery_activity',array('id' => $orr['gid']),array('name','thumb'));
                    if(empty($ke)){
                        $data['goodLogo'] = tomedia($goods['thumb']);
                    }
                    if($ke>0){
                        if($orr['specid']>0){
                            $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                            $data['goodName'] .=  ' + ['.$goods['name'].'/'.$specname.'] X'.$orr['num'];
                        }else{
                            $data['goodName'] .=  ' + ['.$goods['name'].'] X'.$orr['num'];
                        }
                    }else{
                        if($orr['specid']>0){
                            $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                            $data['goodName'] .=  '['.$goods['name'].'/'.$specname.'] X'.$orr['num'];
                        }else{
                            $data['goodName'] .= '['.$goods['name'].'] X'.$orr['num'];
                        }
                    }
                }

            }
            else if ($plugin == 'housekeep') {
                $goods            = pdo_get('wlmerchant_housekeep_service' , ['id' => $order['fkid']] , [
                    'title' ,
                    'thumb'
                ]);
                $data['goodName'] = $goods['title'];
                $data['goodLogo'] = tomedia($goods['thumb']);
                $data['type'][]   = 1;
                $data['usestatus'] = 2;
            }else if($plugin == 'hotel'){
                $goods            = pdo_get('wlmerchant_hotel_room' , ['id' => $order['fkid']] , [
                    'name' ,
                    'thumb'
                ]);
                $data['goodName'] = $goods['name'];
                $data['goodLogo'] = tomedia($goods['thumb']);
                $data['type'][]   = 1;
                $data['usestatus'] = 2;
            }
            if ($order['specid'] && $plugin != 'bargain' && $plugin != 'housekeep') {
                if($plugin == 'activity'){
                    $data['optionName'] = pdo_getcolumn(PDO_NAME . 'activity_spec' , ['id' => $order['specid']] , 'name');
                }else{
                    $data['optionName'] = pdo_getcolumn(PDO_NAME . 'goods_option' , ['id' => $order['specid']] , 'title');
                }
            }
            $data['allprice'] = $order['price'];
        }
        $data['num']        = $order['num'];
        $data['checkcodes'] = pdo_fetchall("SELECT checkcode,orderprice,status,id FROM ".tablename('wlmerchant_smallorder')."WHERE orderid = {$id} AND plugin = '{$plugin}' AND status = 1 AND appointstatus IN (0,1) ORDER BY id DESC");
        if (!empty($data['checkcodes'])) {
            $data['usestatus'] = 1;
        }
        else if(!empty($order['expressid'])) {
            $data['usestatus'] = 2;
        }
        if($plugin == 'citydelivery'){
            $data['usestatus'] = 2;
            $data['num'] = 0;
        }
        if(!empty($goods['pftid'])){
            $data['usestatus'] = 3;
            $pftinfo = unserialize($order['pftorderinfo']);
            $pftOrderInfo = Pftapimod::pftOrderQuery($pftinfo['UUordernum']);
            if(empty($pftOrderInfo['UUerrorcode'])){
                $data['surplusnum'] = $pftOrderInfo['UUorigin_num'] - $pftOrderInfo['UUverified_num'] - $pftOrderInfo['UUrefund_num'];
            }else{
                $this->renderError('票务平台通信错误:'.$pftOrderInfo['UUerrorinfo']);
            }
        }
        //退款提示
        $ordersettings = Setting::wlsetting_read('orderset');
        if(!empty($ordersettings['refundtip'])){
            $data['refundtip'] = 1;
            $data['tiptext'] = $ordersettings['refundtip'];
        }else{
            $data['refundtip'] = 0;
        }


        if(empty($data['usestatus'])){
            $this->renderError('订单使用类型错误，请返回重试');
        }
        //重新申请
        if (!empty($afterid)) {
            $after          = pdo_get('wlmerchant_aftersale' , ['id' => $afterid] , ['reason' , 'detail' , 'thumbs']);
            $data['reason'] = $after['reason'];
            $data['detail'] = $after['detail'];
            $data['thumbs'] = unserialize($after['thumbs']);
        }
        $this->renderSuccess('订单信息' , $data);
    }
    /**
     * Comment: 提交售后申请
     * Author: wlf
     * Date: 2019/8/22 09:41
     */
    public function afterSaleApply()
    {
        global $_W , $_GPC;
        $id        = $_GPC['id'];
        $plugin    = $_GPC['plugin'];
        $type      = $_GPC['type'];
        $afterid   = $_GPC['afterid'] ? $_GPC['afterid'] : 0;
        $usestatus = $_GPC['usestatus'];
        $num       = $_GPC['num'] ? : 0;
        if($usestatus == 3 && empty($num)){
            $this->renderSuccess('数量错误，请刷新重试');
        }
        if($plugin == 'citydelivery'){
            $canre      = pdo_getcolumn(PDO_NAME . 'aftersale' , [
                'orderid' => $id ,
                'plugin'  => $plugin ,
                'status'  => 1
            ] , 'id');
            $smallorder = pdo_get('wlmerchant_order' , ['id' => $id] , ['mid','fightstatus','makeorderno', 'sid' , 'orderno' , 'aid']);
            if($smallorder['fightstatus'] == 2){
                Citydelivery::cancelOrder($smallorder['makeorderno']);
            }else if($smallorder['fightstatus'] == 3){
                $body = ['order_id' => $smallorder['orderno'],'cancel_reason_id' => 4];
                Citydelivery::postDadaApi($body,5);
            }else if($smallorder['fightstatus'] == 4){
                $body = ['origin_id' => $smallorder['orderno'],'reason' => $_GPC['reason']];
                Citydelivery::postUUApi($body,5);
            }
        } else if ($usestatus == 1) {
            $canre      = pdo_getcolumn(PDO_NAME . 'aftersale' , [
                'orderid' => $id ,
                'plugin'  => $plugin ,
                'status'  => 1
            ] , 'id');
            $checkcodes = explode(',' , $_GPC['checkcodes']);
            $smallorder = pdo_get('wlmerchant_smallorder' , ['checkcode' => $checkcodes[0]] , [
                'mid' ,
                'sid' ,
                'orderno' ,
                'aid'
            ]);
            $checkcode  = serialize($checkcodes);
        }else {
            $canre = pdo_getcolumn(PDO_NAME . 'aftersale' , [
                'orderid' => $id ,
                'plugin'  => $plugin ,
                'status'  => [1 , 2]
            ] , 'id');
            if ($plugin == 'rush') {
                $smallorder = pdo_get('wlmerchant_rush_order' , ['id' => $id] , ['mid' , 'sid' , 'orderno' , 'aid']);
            }
            else {
                $smallorder = pdo_get('wlmerchant_order' , ['id' => $id] , ['mid' , 'sid' , 'orderno' , 'aid','specid']);
            }
        }
        if ($canre) $this->renderError('此订单已经申请了售后，请完成上一次售后申请后再提交');
        MysqlFunction::setTrans(4);
        MysqlFunction::startTrans();
        $reason = $_GPC['reason'];
        $detail = $_GPC['detail'];
        $thumbs = serialize($_GPC['thumbs']);
        $data = [
            'uniacid'    => $_W['uniacid'] ,
            'status'     => 1 ,
            'mid'        => $smallorder['mid'] ,
            'sid'        => $smallorder['sid'] ,
            'aid'        => $smallorder['aid'] ,
            'orderno'    => $smallorder['orderno'] ,
            'orderid'    => $id ,
            'plugin'     => $plugin ,
            'type'       => $type ,
            'checkcodes' => $checkcode ,
            'reason'     => $reason ,
            'detail'     => $detail ,
            'thumbs'     => $thumbs ,
            'createtime' => time(),
            'num'        => $num
        ];
        //写入日志
        $journal         = [
            'time'   => $data['createtime'] ,
            'title'  => '买家发起退款申请' ,
            'detail' => '申请原因:' . $data['reason'] ,
            'thumbs' => $data['thumbs']
        ];
        $data['journal'] = Order::addjournal($journal , $afterid);
        if (!empty($afterid)) {
            $res = pdo_update('wlmerchant_aftersale' , $data , ['id' => $afterid]);
        }
        else {
            $res     = pdo_insert('wlmerchant_aftersale' , $data);
            $afterid = pdo_insertid();
        }
        if ($res) {
            if ($usestatus == 1 && !empty($checkcodes)){
                foreach ($checkcodes as $code) {
                    //校验核销码状态
                    $checkorderstatus = pdo_getcolumn(PDO_NAME.'smallorder',array('checkcode'=>$code),'status');
                    if($checkorderstatus != 1){
                        MysqlFunction::rollback();
                        $this->renderError('核销码['.$code.']状态错误,请刷新重试');
                    }
                    pdo_update('wlmerchant_smallorder' , ['status' => 4] , ['checkcode' => $code]);
                }
            }
            MysqlFunction::commit();
            $orderSet = Setting::wlsetting_read('orderset');
            if ($orderSet['autoapplyre'] != 1) {
                //未开启  自动退款申请订单   给代理商管理员发送模板消息
                $first   = "用户【{$_W['wlmember']['nickname']}】提交了单号为[{$smallorder['orderno']}]订单的退款申请";//消息头部
                $type    = "退款申请";//业务类型
                $content = !empty($detail) ? $detail : $reason;//业务内容
                $status  = "待审核";//处理结果
                $remark  = "请尽快处理!";//备注信息
                $time    = $data['createtime'];//操作时间
                //给代理商管理员发送模板消息
                News::noticeAgent('refundorder' , $_W['aid'] , $first , $type , $content , $status , $remark , $time);
                //给商户管理员发送模板消息
                if($plugin != 'housekeep'){
                    News::noticeShopAdmin( $smallorder['sid'], $first , $type , $content , $status , $remark , $time);
                }else{
                    if($smallorder['specid'] == 1){
                        News::noticeShopAdmin( $smallorder['sid'], $first , $type , $content , $status , $remark , $time);
                    }else{
                        $mid = pdo_getcolumn(PDO_NAME.'housekeep_artificer',array('id'=>$smallorder['sid']),'mid');
                        News::jobNotice($mid,$first,$type,$content,$status,$remark,$time);
                    }
                }
            }
            else {
                //添加计划任务自动退款
                Queue::addTask(5 , $afterid , time() , $afterid);
            }
            $this->renderSuccess('提交成功');
        }
        else {
            MysqlFunction::rollback();
            $this->renderError('提交失败，请刷新重试');
        }
    }
    /**
     * Comment: 支付申请
     * Author: zzw
     * Date: 2019/9/25 14:18
     */
    public function requestPay()
    {
        global $_W , $_GPC;
        #1、参数获取
        $order_no = $_GPC['order_no'] OR $this->renderError('缺少订单号');
        $payType = $_GPC['pay_type'] OR $this->renderError('缺少支付方式');
        $name = $_GPC['name'];//商品名称
        $blendflag = $_GPC['blendflag'];//混合支付标识
        //修改支付配置信息与分账信息
        if($_W['wlsetting']['cashset']['allocationtype'] == 1 && $payType == 2){
            $paylog = pdo_fetch("SELECT plugin,payfor FROM".tablename(PDO_NAME.'paylogvfour') ." WHERE tid = {$order_no} ");
            if($paylog['plugin'] == 'Rush'){
                $sid = pdo_getcolumn(PDO_NAME."rush_order",['orderno'=>$order_no],'sid');
                $storeinfo = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('wxallid','appallid'));
                if($_W['source'] == 1){
                    $paysetid = $storeinfo['wxallid'];
                }else if($_W['source'] == 3){
                    $paysetid = $storeinfo['appallid'];
                }
                if($paysetid > 0){
                    pdo_update(PDO_NAME.'rush_order',array('paysetid' => $paysetid,'allocationtype' => 1),array('orderno' => $order_no));
                }
            }else if($paylog['plugin'] ==  'Halfcard' && $paylog['payfor'] == 'Halfcard'){
                $aid = pdo_getcolumn(PDO_NAME."halfcard_record",['orderno'=>$order_no],'aid');
                $agentinfo = pdo_get('wlmerchant_agentusers',array('id' => $aid),array('wxpaysetid','apppaysetid'));
                if($_W['source'] == 1){
                    $paysetid = $agentinfo['wxpaysetid'];
                }else if($_W['source'] == 3){
                    $paysetid = $agentinfo['apppaysetid'];
                }
                if($paysetid > 0){
                    pdo_update(PDO_NAME.'rush_order',array('paysetid' => $paysetid,'allocationtype' => 1),array('orderno' => $order_no));
                }

            }elseif($paylog['payfor'] == 'Charge'){
                $paysetid = 0;  //余额充值订单不分账
            }else{
                $paysetinfo = pdo_get(PDO_NAME."order",['orderno' => $order_no],array('sid','aid'));
                $sid = $paysetinfo['sid'];
                $aid = $paysetinfo['aid'];
                if(empty($sid)){
                    $agentinfo = pdo_get('wlmerchant_agentusers',array('id' => $aid),array('wxpaysetid','apppaysetid'));
                    if($_W['source'] == 1){
                        $paysetid = $agentinfo['wxpaysetid'];
                    }else if($_W['source'] == 3){
                        $paysetid = $agentinfo['apppaysetid'];
                    }
                    if($paysetid > 0){
                        pdo_update(PDO_NAME.'rush_order',array('paysetid' => $paysetid,'allocationtype' => 1),array('orderno' => $order_no));
                    }
                }else{
                    $storeinfo = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('wxallid','appallid'));
                    if($_W['source'] == 1 || $_W['source'] == 2){
                        $paysetid = $storeinfo['wxallid'];
                    }else if($_W['source'] == 3){
                        $paysetid = $storeinfo['appallid'];
                    }
                }
                if($paysetid > 0){
                    pdo_update(PDO_NAME.'order',array('paysetid' => $paysetid,'allocationtype' => 1),array('orderno' => $order_no));
                }
            }
        }
        #2、请求获取支付的信息
        $res = Payment::init($order_no , $payType , $name , $_W['source'],$blendflag);
        #3、返回支付需要的信息
        $paylog = pdo_get('wlmerchant_paylogvfour' , ['tid' => $order_no,'module' => 'weliam_smartcity'] , ['plugin' , 'uniacid' , 'mid']);
        if($payType != 1){
            if ($paylog['uniacid'] != $_W['uniacid'] || empty($paylog['uniacid']) ) {
                $this->renderError('订单公众号错误，请返回订单列表重新进入支付页面');
            }
            if ($paylog['mid'] != $_W['mid'] || empty($paylog['mid'])  ) {
                $this->renderError('订单用户错误，请返回订单列表重新进入支付页面');
            }
        }
        $type = strtolower($paylog['plugin']);
        if ($type == 'pocket' && $payType != 3) {
            $res['tieziid'] = pdo_getcolumn(PDO_NAME . 'order' , ['orderno' => $order_no] , 'fkid');
        }
        $this->renderSuccess('支付信息' , $res);
    }
    /**
     * Comment: 获取订单物流信息
     * Author: zzw
     * Date: 2019/10/8 14:34
     */
    public function getLogisticsInfo()
    {
        global $_W , $_GPC;
        #1、参数获取
        $id = intval($_GPC['id']) OR $this->renderError("缺少参数：订单id");
        $tableType = $_GPC['table'] OR $this->renderError('缺少参数：表类型');//a=order   b=rush_order
        #2、获取订单信息
        if ($tableType == 'a') {
            $info = pdo_fetch("SELECT '0' as is_have,expressid,plugin,fkid as gid,specid,num FROM " . tablename(PDO_NAME . 'order') . " WHERE id = {$id} ");
        }
        else {
            $info = pdo_fetch("SELECT '0' as is_have,expressid,activityid as gid,'rush' as plugin,optionid as specid,num FROM " . tablename(PDO_NAME . "rush_order") . " WHERE id = {$id} ");
        }
        if ($info['expressid'] <= 0) $this->renderError('当前订单暂无物流信息');
        #3、获取商品信息
        switch ($info['plugin']) {
            case 'rush':
                $goods              = pdo_get(PDO_NAME . "rush_activity" , ['id' => $info['gid']] , ['name' , 'thumb']);
                $info['goods_name'] = $goods['name'];
                $info['goods_logo'] = tomedia($goods['thumb']);
                break;//抢购
            case 'consumption':
                $goods              = pdo_get(PDO_NAME . "consumption_goods" , ['id' => $info['gid']] , [
                    'title' ,
                    'thumb'
                ]);
                $info['goods_name'] = $goods['title'];
                $info['goods_logo'] = tomedia($goods['thumb']);
                break;//积分
            case 'bargain':
                $goods              = pdo_get(PDO_NAME . "bargain_activity" , ['id' => $info['gid']] , [
                    'name' ,
                    'thumb'
                ]);
                $info['goods_name'] = $goods['name'];
                $info['goods_logo'] = tomedia($goods['thumb']);
                break;//砍价
            case 'groupon':
                $goods              = pdo_get(PDO_NAME . "groupon_activity" , ['id' => $info['gid']] , [
                    'name' ,
                    'thumb'
                ]);
                $info['goods_name'] = $goods['name'];
                $info['goods_logo'] = tomedia($goods['thumb']);
                break;//团购
            case 'wlfightgroup':
                $goods              = pdo_get(PDO_NAME . "fightgroup_goods" , ['id' => $info['gid']] , [
                    'name' ,
                    'logo'
                ]);
                $info['goods_name'] = $goods['name'];
                $info['goods_logo'] = tomedia($goods['logo']);
                break;//拼团
        }
        #4、获取商品规格信息
        if ($info['specid'] > 0) $specname = pdo_getcolumn(PDO_NAME . "goods_option" , ['id' => $info['specid']] , 'title');
        $info['spec'] = $specname ? : '';
        #5、物流信息获取
        if ($info['expressid'] > 0) {
            //物流跟踪信息
            $logisticsInfo = Logistics::orderLogisticsInfo($info['expressid']);
            if ($logisticsInfo['Traces']) {
                $info['is_have'] = 1;
                //将物流信息倒叙
                $info['list'] = array_reverse($logisticsInfo['Traces']);
            }
            //物流公司、物流单号信息
            $express              = pdo_get(PDO_NAME . "express" , ['id' => $info['expressid']] , [
                'expressname' ,
                'expresssn'
            ]);
            $info['express_name'] = Logistics::codeComparisonTable($express['expressname'] , 'alias')['name'];
            $info['express_no']   = $express['expresssn'];
        }
        unset($info['specid']);
        unset($info['plugin']);
        unset($info['gid']);
        unset($info['expressid']);
        $this->renderSuccess('物流信息' , $info);
    }
    /**
     * Comment: 获取快递公司列表
     * Author: wlf
     * Date: 2019/10/10 17:07
     */
    public function getComparisonTable()
    {
        global $_W;
        $express_list = Logistics::codeComparisonTable('' , 0 , true);
        $this->renderSuccess('快递公司列表' , $express_list);
    }
    /**
     * Comment: 手机端发货接口
     * Author: wlf
     * Date: 2019/10/10 17:11
     */
    public function sendGoods()
    {
        global $_W , $_GPC;
        $id          = $_GPC['orderid'];
        $alias       = $_GPC['alias'];
        $expresssn   = $_GPC['expresssn'];
        $ordertype   = $_GPC['ordertype'];
        $edit_flag   = $_GPC['edit_flag'];
        $expressname = $_GPC['expressname'];
        if (empty($alias) && !empty($expresssn)) {
            $this->renderError('请填写物流单号');
        }
        $settings = Setting::wlsetting_read('orderset');
        if ($ordertype == 'a') {
            $order = pdo_fetch("SELECT a.expressid,a.plugin,a.orderno,a.mid,
                            CASE a.`plugin` 
                                WHEN 'consumption' THEN (SELECT `title` FROM " . tablename(PDO_NAME . 'consumption_goods') . " WHERE `id` = a.`fkid`)
                                WHEN 'bargain' THEN (SELECT `name` FROM " . tablename(PDO_NAME . 'bargain_activity') . " WHERE `id` = a.`fkid` )
                                WHEN 'groupon' THEN (SELECT `name` FROM " . tablename(PDO_NAME . 'groupon_activity') . " WHERE `id` = a.`fkid` )
                                WHEN 'wlfightgroup' THEN (SELECT name FROM " . tablename(PDO_NAME . 'fightgroup_goods') . " WHERE `id` = a.`fkid`)
                            END as name FROM " . tablename(PDO_NAME . 'order') . " as a WHERE a.id = {$id} ");
        }
        else {
            $order = pdo_fetch("SELECT a.expressid,b.name,'rush' as plugin,a.orderno,a.mid FROM " . tablename(PDO_NAME . "rush_order") . " as a LEFT JOIN " . tablename(PDO_NAME . "rush_activity") . " as b ON a.activityid = b.id WHERE a.id = {$id} ");
        }
        $expressid = $order['expressid'];
        if (empty($expressid)) {
            $this->renderError('无收货地址，无法发货！');
        }
        $express = pdo_get(PDO_NAME . 'express' , ['id' => $expressid]);
        $res     = pdo_update('wlmerchant_express' , [
            'expressname' => $alias ,
            'expresssn'   => $expresssn ,
            'orderid'     => $id ,
            'sendtime'    => time()
        ] , ['id' => $expressid]);
        if ($res) {
            if ($ordertype == 'a') {
                $res = pdo_update('wlmerchant_order' , ['status' => 4] , ['id' => $id]);
                if ($settings['receipt'] > 0) {
                    if ($edit_flag) {
                        pdo_delete('wlmerchant_waittask' , ['important' => $id , 'key' => 6 , 'status' => 0]);
                    }
                    $receipttime = time() + $settings['receipt'] * 24 * 3600;
                    $task        = [
                        'type'    => 'order' ,
                        'orderid' => $id
                    ];
                    $task        = serialize($task);
                    Queue::addTask(6 , $task , $receipttime , $id);
                }
            }
            else {
                $res = pdo_update('wlmerchant_rush_order' , ['status' => 4] , ['id' => $id]);
                if ($settings['receipt'] > 0) {
                    if ($edit_flag) {
                        pdo_delete('wlmerchant_waittask' , ['important' => $id , 'key' => 6 , 'status' => 0]);
                    }
                    $receipttime = time() + $settings['receipt'] * 24 * 3600;
                    $task        = [
                        'type'    => 'rush' ,
                        'orderid' => $id
                    ];
                    $task        = serialize($task);
                    Queue::addTask(6 , $task , $receipttime , $id);
                }
            }
            /***模板通知***/
            $url       = h5_url('pages/subPages/orderList/orderDetails/orderDetails' , [
                'orderid' => $id ,
                'plugin'  => $order['plugin']
            ]);
            $modelData = [
                'first'             => '您购买的商品已发货，请注意查收!' ,
                'order_no'          => $order['orderno'] ,//订单编号
                'express_name'      => $expressname ,//物流公司
                'express_no'        => $expresssn ,//物流单号
                'goods_name'        => $order['name'] ,//商品信息
                'consignee'         => $express['name'] ,//收货人
                'receiving_address' => $express['address'] ,//收货地址
                'remark'            => '点击查看物流详细信息!'
            ];
            TempModel::sendInit('send' , $order['mid'] , $modelData , $_W['source'] , $url);
            $this->renderSuccess('发货成功');
        }
        else {
            $this->renderError('发货失败请重试');
        }
    }
    /**
     * Comment: 售后列表接口
     * Author: wlf
     * Date: 2019/11/21 09:47
     */
    public function saleAfterList()
    {
        global $_W , $_GPC;
        $pindex    = $_GPC['pindex'] ? $_GPC['pindex'] : 1;
        $pageStart = $pindex * 10 - 10;
        $status    = $_GPC['status'];
        $where     = " WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} ";
        if ($status == 1) {
            $where .= " AND status = 1 ";
        }
        else if ($status == 2) {
            $where .= " AND status IN (2,3) ";
        }
        $list  = pdo_fetchall("SELECT id,orderno,orderid,plugin,status FROM " . tablename(PDO_NAME . "aftersale") . $where . " ORDER BY createtime DESC LIMIT {$pageStart},10 ");
        $total = pdo_fetchcolumn("SELECT count(id) FROM " . tablename(PDO_NAME . "aftersale") . $where);
        if ($list) {
            foreach ($list as &$li) {
                if ($li['plugin'] == 'rush') {
                    $order       = pdo_get('wlmerchant_rush_order' , ['id' => $li['orderid']] , [
                        'activityid' ,
                        'num' ,
                        'optionid' ,
                        'price'
                    ]);
                    $goods       = pdo_get('wlmerchant_rush_activity' , ['id' => $order['activityid']] , [
                        'name' ,
                        'thumb'
                    ]);
                    $li['price'] = sprintf("%.2f" , $order['price'] / $order['num']);
                }
                else if ($li['plugin'] == 'groupon') {
                    $order             = pdo_get('wlmerchant_order' , ['id' => $li['orderid']] , [
                        'fkid' ,
                        'num' ,
                        'specid' ,
                        'goodsprice'
                    ]);
                    $goods             = pdo_get('wlmerchant_groupon_activity' , ['id' => $order['fkid']] , [
                        'name' ,
                        'thumb'
                    ]);
                    $li['price']       = sprintf("%.2f" , $order['goodsprice'] / $order['num']);
                    $order['optionid'] = $order['specid'];
                }
                else if ($li['plugin'] == 'wlfightgroup') {
                    $order             = pdo_get('wlmerchant_order' , ['id' => $li['orderid']] , [
                        'fkid' ,
                        'num' ,
                        'specid' ,
                        'goodsprice'
                    ]);
                    $goods             = pdo_get('wlmerchant_fightgroup_goods' , ['id' => $order['fkid']] , [
                        'name' ,
                        'logo'
                    ]);
                    $li['price']       = sprintf("%.2f" , $order['goodsprice'] / $order['num']);
                    $order['optionid'] = $order['specid'];
                    $goods['thumb']    = $goods['logo'];
                }
                else if ($li['plugin'] == 'activity') {
                    $order             = pdo_get('wlmerchant_order' , ['id' => $li['orderid']] , [
                        'fkid' ,
                        'num' ,
                        'specid' ,
                        'goodsprice'
                    ]);
                    $goods             = pdo_get('wlmerchant_activitylist' , ['id' => $order['fkid']] , [
                        'title' ,
                        'thumb'
                    ]);
                    $li['price']       = sprintf("%.2f" , $order['goodsprice'] / $order['num']);
                    $goods['name']  = $goods['title'];
                    $order['optionid'] = $order['specid'];
                    $goods['thumb']    = $goods['thumb'];
                }
                else if ($li['plugin'] == 'bargain') {
                    $order       = pdo_get('wlmerchant_order' , ['id' => $li['orderid']] , [
                        'fkid' ,
                        'num' ,
                        'specid' ,
                        'goodsprice'
                    ]);
                    $goods       = pdo_get('wlmerchant_bargain_activity' , ['id' => $order['fkid']] , [
                        'name' ,
                        'thumb'
                    ]);
                    $li['price'] = sprintf("%.2f" , $order['goodsprice'] / $order['num']);
                }
                else if ($li['plugin'] == 'coupon' || $li['plugin'] == 'wlcoupon') {
                    $order          = pdo_get('wlmerchant_order' , ['id' => $li['orderid']] , [
                        'fkid' ,
                        'num' ,
                        'specid' ,
                        'goodsprice'
                    ]);
                    $goods          = pdo_get('wlmerchant_couponlist' , ['id' => $order['fkid']] , ['title' , 'logo']);
                    $goods['name']  = $goods['title'];
                    $goods['thumb'] = $goods['logo'];
                    $li['price']    = sprintf("%.2f" , $order['goodsprice'] / $order['num']);
                }else if($li['plugin'] == 'citydelivery'){
                    $order = pdo_get('wlmerchant_order' , ['id' => $li['orderid']] , ['price']);
                    $order['num'] = 0;
                    $smallorders = pdo_fetchall("SELECT gid,num,specid FROM ".tablename('wlmerchant_delivery_order')." WHERE orderid = {$li['orderid']} ORDER BY price DESC");
                    foreach ($smallorders  as $ke => $orr){
                        $good = pdo_get('wlmerchant_delivery_activity',array('id' => $orr['gid']),array('name','thumb'));
                        if(empty($ke)){
                            $goods['thumb'] = tomedia($good['thumb']);
                        }
                        if($ke>0){
                            if($orr['specid']>0){
                                $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                                $goods['name'] .=  ' + ['.$good['name'].'/'.$specname.'] X'.$orr['num'];
                            }else{
                                $goods['name'] .=  ' + ['.$good['name'].'] X'.$orr['num'];
                            }
                        }else{
                            if($orr['specid']>0){
                                $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                                $goods['name'] .=  '['.$good['name'].'/'.$specname.'] X'.$orr['num'];
                            }else{
                                $goods['name'] .= '['.$good['name'].'] X'.$orr['num'];
                            }
                        }
                    }
                    $li['price'] = $order['price'];
                }
                $li['goodsname']  = $goods['name'];
                $li['goodsthumb'] = tomedia($goods['thumb']);
                $li['num']        = $order['num'];
                if ($order['optionid']) {
                    if($li['plugin'] == 'activity'){
                        $li['optionname'] = pdo_getcolumn(PDO_NAME . 'activity_spec' , ['id' => $order['optionid']] , 'name');
                    }else{
                        $li['optionname'] = pdo_getcolumn(PDO_NAME . 'goods_option' , ['id' => $order['optionid']] , 'title');
                    }
                }
                else {
                    $li['optionname'] = '';
                }
            }
            $data['total'] = ceil($total / 10);
            $data['list']  = $list;
            $this->renderSuccess('售后列表' , $data);
        }
        else {
            $data['total'] = 0;
            $data['list']  = [];
            $this->renderSuccess('无数据' , $data);
        }
    }
    /**
     * Comment: 撤销申请接口
     * Author: wlf
     * Date: 2019/11/21 11:11
     */
    public function revokeSaleAfter()
    {
        global $_W , $_GPC;
        $id         = $_GPC['id'];
        $after      = pdo_get('wlmerchant_aftersale' , ['id' => $id] , ['type' , 'checkcodes']);
        $checkcodes = unserialize($after['checkcodes']);
        if ($after['type'] == 1) { //退款
            if (!empty($checkcodes[0])) {
                $res = pdo_update('wlmerchant_smallorder' , ['status' => 1] , ['checkcode' => $checkcodes]);
            }
            else {
                $res = 1;
            }
        }
        if ($res) {
            pdo_delete('wlmerchant_aftersale' , ['id' => $id]);
            $this->renderSuccess('撤销成功');
        }
        else {
            $this->renderError('撤销失败，请刷新重试');
        }
    }
    /**
     * Comment: 售后详情页面
     * Author: wlf
     * Date: 2019/11/21 11:45
     */
    public function saleAfterDetail()
    {
        global $_W , $_GPC;
        $id = $_GPC['id'];
        if (empty($id)) {
            $this->renderError('无关键参数:id,请返回重新进入');
        }
        $after = pdo_get('wlmerchant_aftersale' , ['id' => $id]);
        if (empty($after)) {
            $this->renderError('此申请已撤销，无法查看');
        }
        $data           = [];
        $data['status'] = $after['status'];
        $data['type']   = $after['type'];
        if ($after['plugin'] == 'rush') {
            $order          = pdo_get('wlmerchant_rush_order' , ['id' => $after['orderid']] , [
                'activityid' ,
                'actualprice' ,
                'num' ,
                'optionid' ,
                'price'
            ]);
            $goods          = pdo_get('wlmerchant_rush_activity' , ['id' => $order['activityid']] , ['name' , 'thumb']);
            $data['price']  = sprintf("%.2f" , $order['price'] / $order['num']);
            $order['price'] = $order['actualprice'];
        }
        else if ($after['plugin'] == 'groupon') {
            $order             = pdo_get('wlmerchant_order' , ['id' => $after['orderid']] , [
                'fkid' ,
                'num' ,
                'specid' ,
                'price' ,
                'goodsprice'
            ]);
            $goods             = pdo_get('wlmerchant_groupon_activity' , ['id' => $order['fkid']] , ['name' , 'thumb']);
            $data['price']     = sprintf("%.2f" , $order['goodsprice'] / $order['num']);
            $order['optionid'] = $order['specid'];
        }
        else if ($after['plugin'] == 'wlfightgroup') {
            $order             = pdo_get('wlmerchant_order' , ['id' => $after['orderid']] , [
                'fkid' ,
                'num' ,
                'price' ,
                'specid' ,
                'goodsprice'
            ]);
            $goods             = pdo_get('wlmerchant_fightgroup_goods' , ['id' => $order['fkid']] , ['name' , 'logo']);
            $data['price']     = sprintf("%.2f" , $order['goodsprice'] / $order['num']);
            $order['optionid'] = $order['specid'];
            $goods['thumb']    = $goods['logo'];
        }
        else if ($after['plugin'] == 'activity') {
            $order             = pdo_get('wlmerchant_order' , ['id' => $after['orderid']] , [
                'fkid' ,
                'num' ,
                'price' ,
                'specid' ,
                'goodsprice'
            ]);
            $goods             = pdo_get('wlmerchant_activitylist' , ['id' => $order['fkid']] , ['title' , 'thumb']);
            $goods['name']     = $goods['title'];
            $data['price']     = sprintf("%.2f" , $order['goodsprice'] / $order['num']);
            $order['optionid'] = $order['specid'];
        }
        else if ($after['plugin'] == 'bargain') {
            $order         = pdo_get('wlmerchant_order' , ['id' => $after['orderid']] , [
                'fkid' ,
                'num' ,
                'price' ,
                'specid' ,
                'goodsprice'
            ]);
            $goods         = pdo_get('wlmerchant_bargain_activity' , ['id' => $order['fkid']] , ['name' , 'thumb']);
            $data['price'] = sprintf("%.2f" , $order['goodsprice'] / $order['num']);
        }else if ($after['plugin'] == 'citydelivery'){
            $order         = pdo_get('wlmerchant_order' , ['id' => $after['orderid']] , [
                'num' ,
                'price' ,
                'specid' ,
                'goodsprice',
                'fightstatus'
            ]);
            if($order['fightstatus'] == 2 || $order['fightstatus'] == 3){
                $data['hiderevoke'] = 1;
            }else{
                $data['hiderevoke'] = 0;
            }
            $smallorders = pdo_fetchall("SELECT id,gid,num,specid FROM ".tablename('wlmerchant_delivery_order')." WHERE orderid = {$after['orderid']} ORDER BY price DESC");
            foreach ($smallorders  as $ke => $orr){
                $good = pdo_get('wlmerchant_delivery_activity',array('id' => $orr['gid']),array('name','thumb'));
                if(empty($ke)){
                    $goods['thumb'] = tomedia($good['thumb']);
                }
                if($ke>0){
                    if($orr['specid']>0){
                        $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                        $goods['name'] .=  ' + ['.$good['name'].'/'.$specname.'] X'.$orr['num'];
                    }else{
                        $goods['name'] .=  ' + ['.$good['name'].'] X'.$orr['num'];
                    }
                }else{
                    if($orr['specid']>0){
                        $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                        $goods['name'] .=  '['.$good['name'].'/'.$specname.'] X'.$orr['num'];
                    }else{
                        $goods['name'] .= '['.$good['name'].'] X'.$orr['num'];
                    }
                }
            }
            $order['num'] = 0;
            $data['price'] = $order['price'];
        }
        $data['goodsname']  = $goods['name'];
        $data['goodsthumb'] = tomedia($goods['thumb']);
        $data['num']        = $order['num'];
        if ($order['optionid']) {
            if($after['plugin'] == 'activity'){
                $data['optionname'] = pdo_getcolumn(PDO_NAME . 'activity_spec' , ['id' => $order['optionid']] , 'name');
            }else {
                $data['optionname'] = pdo_getcolumn(PDO_NAME . 'goods_option', ['id' => $order['optionid']], 'title');
            }
        } else {
            $data['optionname'] = '';
        }
        if ($after['type'] == 1) {
            $checks = unserialize($after['checkcodes']);
            if (!empty($checks[0])) {
                $data['refundmoney'] = pdo_getcolumn('wlmerchant_smallorder' , ['checkcode' => $checks] , ["SUM(orderprice)"]);
            }
            else {
                $data['refundmoney'] = $order['price'];
            }
        }
        //处理日志
        $journal = unserialize($after['journal']);
        if (!empty($journal)) {
            foreach ($journal as &$jour) {
                $jour           = unserialize($jour);
                $jour['thumbs'] = unserialize($jour['thumbs']);
                if (!empty($jour['thumbs'])) {
                    $jour['thumbs'] = explode(',' , $jour['thumbs']);
                }
                else {
                    $jour['thumbs'] = [];
                }
                $jour['ascflag'] = $jour['time'];
                $jour['time']    = date('Y-m-d H:i:s' , $jour['time']);
                if(!empty($jour['thumbs'])){
                    foreach ($jour['thumbs'] as &$th){
                        $th = tomedia($th);
                    }
                }
            }
            $flag = [];
            foreach ($journal as $v) {
                $flag[] = $v['ascflag'];
            }
            array_multisort($flag , SORT_DESC , $journal);
        }
        $data['journal'] = $journal;
        $this->renderSuccess('售后详情' , $data);
    }

    /**
     * Comment: 计算提现手续费与实获金额
     * Author: wlf
     * Date: 2020/04/09 17:02
     */
    public function calculationCash(){
        global $_W , $_GPC;
        $applymoney = $_GPC['money'];
        $spercent = $_GPC['percent'];
        $spercentmoney = sprintf("%.2f", $applymoney * $spercent / 100);
        $money = sprintf("%.2f", $applymoney - $spercentmoney);
        $data['realmoney'] = $money;
        $this->renderSuccess('实获金额' ,$data);
    }

    /**
     * Comment: 同城配送的订单获取
     * Author: wlf
     * Date: 2020/04/13 10:24
     */
    public function deliveryOrderSubmit(){
        global $_W , $_GPC;
        //参数获取
        $data = [];
        $goodsinfo = json_decode(base64_decode($_GPC['goodsinfo']) , true);
        $addressid = $_GPC['addressid'];
        $usestatus = $_GPC['type'];  //配送方式 0到店自提 1商家配送 2平台配送
        $sid = $_GPC['sid'];
        //快递信息
        if ($addressid>0) {
            $address = pdo_get('wlmerchant_address' , ['id' => $addressid] , ['id','name' ,'status','tel','province','city','county','detailed_address','lng','lat']);
        }else{
            $address = pdo_get('wlmerchant_address' , ['mid' => $_W['mid'] , 'uniacid' => $_W['uniacid'] , 'status'  => 1] , ['id' , 'status' , 'name' , 'tel' , 'province' , 'city' , 'county' , 'detailed_address','lng','lat']);
            $addressid = $address['id'];
        }
        //商品信息获取
        if((empty($goodsinfo) || !is_array($goodsinfo)) && empty($sid) ){
            $this->renderError('商品信息错误，请返回重试');
        }else{
            if(empty($goodsinfo) || !is_array($goodsinfo)){
                $storeinfo['sid'] = $sid;
                $storeinfo['cartid'] = pdo_getall('wlmerchant_delivery_shopcart',array('mid' => $_W['mid'],'sid' => $sid),array('id'));
                foreach ($storeinfo['cartid'] as $cart){
                    $infoarray[] = $cart['id'];
                }
                $storeinfo['cartid'] = $infoarray;
                $goodsinfo[] = $storeinfo;
            }
            $halfflag = WeliamWeChat::VipVerification($_W['mid'],true);
            $goodallmoney = $deliveryallmoney = $alldiscount = $fulldiscount = $packingmoney = 0;
            foreach ($goodsinfo as $ke => $store){
                $storearray = pdo_get(PDO_NAME.'merchantdata',array('id'=>$store['sid']),array('lng','lat','deliveryfullid','makebiguser','deliverytype','storename','expresspricestatus','deliverymoney','deliverydistance','lowdeliverymoney'));
                //判断支付方式
                if($ke == 0){
                    $deliverytype = unserialize($storearray['deliverytype']);
                    $data['statistics']['use_store'] = in_array('store',$deliverytype) ? 1 : 0;
                    $data['statistics']['use_make'] = in_array('make',$deliverytype) ? 1 : 0;
                    $data['statistics']['use_own'] = in_array('own',$deliverytype) ? 1 : 0;
                }
                unset($storearray['deliverytype']);
                $storearray['allmoney'] = 0;
                $storearray['vipdiscount'] = 0;
                $storearray['packingmoney'] = 0;
                $storearray['sid'] = $store['sid'];
                if($usestatus == 0 || ($usestatus == 2 && empty($storearray['expresspricestatus']) )){
                    $storearray['deliverymoney'] = 0;
                }else{
                    $storearray['distance'] = Store::getdistance($address['lng'],$address['lat'],$storearray['lng'],$storearray['lat'],true);
                    if($storearray['distance'] > 9999998){
                        $storearray['distance'] = 0;
                    }
                }
                foreach ($store['cartid'] as $good){
                    $good = pdo_get('wlmerchant_delivery_shopcart',array('id' => $good),array('goodid','num','specid'));
                    $goodinfo = pdo_get('wlmerchant_delivery_activity',array('id' => $good['goodid']),array('name','price','deliveryprice','thumb','vipstatus','vipdiscount','optionstatus'));
                    $goodarray['name'] = $goodinfo['name'];
                    $goodarray['thumb'] = tomedia($goodinfo['thumb']);
                    $goodarray['price'] = $goodinfo['price'];
                    $goodarray['deliveryprice'] = $goodinfo['deliveryprice'];
                    $goodarray['num'] = $good['num'];
                    //规格
                    if($goodinfo['optionstatus']){
                        $specarray = pdo_get('wlmerchant_delivery_spec',array('id' => $good['specid']),array('name','price','oldprice'));
                        $goodarray['price'] = $specarray['price'];
                        $goodarray['specname'] = $specarray['name'];
                    }
                    //会员
                    if($goodinfo['vipstatus'] == 1 && $halfflag){
                        $goodarray['vipdiscount'] = $goodinfo['vipdiscount'];
                    }else{
                        $goodarray['vipdiscount'] = 0;
                    }
                    //计算包装费
                    if($usestatus>0){
                        $storearray['packingmoney'] = sprintf("%.2f",$storearray['packingmoney'] + $goodinfo['deliveryprice']*$goodarray['num']);
                    }else{
                        $storearray['packingmoney'] = 0;
                    }
                    //计算小计信息
                    $storearray['vipdiscount'] = sprintf("%.2f",$storearray['vipdiscount'] + $goodarray['vipdiscount'] * $goodarray['num']);
                    $storearray['allmoney'] = sprintf("%.2f",$storearray['allmoney'] + $goodarray['price'] * $goodarray['num']);

                    $goodallmoney += sprintf("%.2f",$goodarray['price'] * $goodarray['num']);
                    $storearray['goodlist'][] = $goodarray;
                }
                //平台配送
                if($usestatus == 2 && empty($storearray['expresspricestatus'])){
                    $big = $storearray['makebiguser'] > 0 ? $store['sid'] : 0;
                    $makePrice = Citydelivery::getMakePrice($store['sid'],$addressid,$big);
                    $storearray['deliverymoney'] = $makePrice['total_price'];
                    $storearray['distance'] = $makePrice['distance'].'km';
                    $storearray['init'] = $makePrice['init'];
                    $storearray['premium'] = $makePrice['premium'];
                    $storearray['night_price'] = $makePrice['night_price'];
                    $storearray['mileage_price'] = $makePrice['mileage_price'];
                    unset($storearray['makebiguser']);
                }
                if($storearray['deliveryfullid']>0){
                    $storearray['fulldkmoney'] = Fullreduce::getFullreduceMoney(sprintf("%.2f" , $storearray['allmoney'] - $storearray['vipdiscount']),$storearray['deliveryfullid']);
                }else{
                    $storearray['fulldkmoney'] = 0;
                }
                $storearray['allmoney'] = sprintf("%.2f",$storearray['allmoney'] + $storearray['packingmoney'] + $storearray['deliverymoney'] - $storearray['vipdiscount'] - $storearray['fulldkmoney']);
                //计算总计信息
                $deliveryallmoney += $storearray['deliverymoney'];
                $alldiscount += $storearray['vipdiscount'];
                $fulldiscount += $storearray['fulldkmoney'];
                $packingmoney += $storearray['packingmoney'];
                $list[] = $storearray;
            }
        }
        $data['list'] = $list;
        $data['statistics']['goodallmoney'] = sprintf("%.2f",$goodallmoney);
        $data['statistics']['deliveryprice'] = sprintf("%.2f",$deliveryallmoney);
        $data['statistics']['vipdiscount'] = sprintf("%.2f",$alldiscount);
        $data['statistics']['fulldiscount'] = sprintf("%.2f",$fulldiscount);
        $data['statistics']['packingmoney'] = sprintf("%.2f",$packingmoney);
        $data['statistics']['toatlprice'] = sprintf("%.2f",$data['statistics']['goodallmoney'] + $data['statistics']['packingmoney'] + $data['statistics']['deliveryprice'] - $data['statistics']['vipdiscount'] - $data['statistics']['fulldiscount']);
        if (empty($address)){
            $address = pdo_get('wlmerchant_address' , ['mid' => $_W['mid'] , 'uniacid' => $_W['uniacid']] , ['id' , 'name' , 'status' , 'tel' , 'province' , 'city' , 'county' , 'detailed_address']);
            if ($address) {
                pdo_update('wlmerchant_address' , ['status' => 1] , ['id' => $address['id']]);
            }else{
                $address = [];
            }
        }
        $data['address'] = $address;
        //提货信息
        $member = pdo_get('wlmerchant_member',array('id' => $_W['mid']),array('realname','nickname','mobile'));
        $data['thinfo']['thname'] = $member['realname']?$member['realname']:$member['nickname'];
        $data['thinfo']['thmobile'] = $member['mobile'];

        $this->renderSuccess('提交订单',$data);
    }

    /**
     * Comment: 同城配送创建订单
     * Author: wlf
     * Date: 2020/04/13 14:53
     */
    public function createDeliveryOrder(){
        global $_W , $_GPC;
        //参数获取
        $goodsinfo = json_decode(base64_decode($_GPC['goodsinfo']),true);
        $addressid = $_GPC['addressid'];  //地址信息
        $username     = trim($_GPC['thname']);  //提货人姓名
        $mobile       = trim($_GPC['thmobile']);  //提货人电话
        $type         = $_GPC['buytype'];   //配送方式 0到店自提 1商家配送 2平台配送
        $sid          = $_GPC['sid'];
        $remark       = trim($_GPC['remark']);
        //获取位置信息
        if($type > 0){
            $address = pdo_get('wlmerchant_address' , ['id' => $addressid] , ['lng','lat']);
            if(empty($address['lng']) || empty($address['lat'])){
                $this->renderError('配送地址无定位信息，请添加');
            }
        }
        //商品信息获取
        if((empty($goodsinfo) || !is_array($goodsinfo)) && empty($sid)){
            $this->renderError('订单信息错误，请返回重试');
        }else{
            MysqlFunction::setTrans(4);
            MysqlFunction::startTrans();
            if(empty($goodsinfo) || !is_array($goodsinfo)){
                $storeinfo['sid'] = $sid;
                $storeinfo['cartid'] = pdo_getall('wlmerchant_delivery_shopcart',array('mid' => $_W['mid'],'sid' => $sid),array('id'));
                foreach ($storeinfo['cartid'] as $cart){
                    $infoarray[] = $cart['id'];
                }
                $storeinfo['cartid'] = $infoarray;
                $storeinfo['remark'] = $remark;
                $goodsinfo[] = $storeinfo;
            }
            $halfflag = WeliamWeChat::VipVerification($_W['mid'],true);
            $settings = Setting::wlsetting_read('orderset'); //获取设置参数
            $ordersid = [];
            foreach ($goodsinfo as $store){
                $num = 0;
                $goodallmoney = $alldiscount = $packingmoney = $prices = 0;
                $storearray = pdo_get(PDO_NAME.'merchantdata',array('id'=>$store['sid']),array('deliverymoney','deliveryfullid','expresspricestatus','deliverytype','makebiguser','lat','lng','storename','deliverydistance','lowdeliverymoney'));
                $deliveryallmoney = $storearray['deliverymoney'];
                $storearray['packingmoney'] = 0;
                $smallorderid = [];
                //判断是否能使用选择的配送方式
                $deliverytype = unserialize($storearray['deliverytype']);
                if($type == 0 && !in_array('own',$deliverytype)){
                    MysqlFunction::rollback();
                    $this->renderError('商户['.$storearray['storename'].']不支持到店自提,请单独结算支付');
                }
                if($type == 1 && !in_array('store',$deliverytype)){
                    MysqlFunction::rollback();
                    $this->renderError('商户['.$storearray['storename'].']不支持商户配送,请单独结算支付');
                }
                if($type == 2 && !in_array('make',$deliverytype)){
                    MysqlFunction::rollback();
                    $this->renderError('商户['.$storearray['storename'].']不支持平台配送,请单独结算支付');
                }
                unset($storearray['deliverytype']);
                foreach ($store['cartid'] as $goodid){
                    $good = pdo_get('wlmerchant_delivery_shopcart',array('id' => $goodid),array('goodid','num','specid'));
                    $goodinfo = pdo_get('wlmerchant_delivery_activity',array('id' => $good['goodid']),array('name','sid','allstock','daystock','uniacid','aid','price','deliveryprice','vipstatus','vipdiscount','optionstatus'));
                    $goodarray['price'] = $goodinfo['price'];
                    //规格
                    if($goodinfo['optionstatus']){
                        $specarray = pdo_get('wlmerchant_delivery_spec',array('id' => $good['specid']),array('name','allstock','daystock','price','oldprice'));
                        $goodarray['price'] = $specarray['price'];
                        $goodarray['specname'] = $specarray['name'];
                        $goodinfo['allstock'] = $specarray['allstock'];
                        $goodinfo['daystock'] = $specarray['daystock'];
                        $goodinfo['name'] .= '/'.$specarray['name'];
                    }
                    //判断库存
                    if($goodinfo['allstock'] > 0){
                        $allsalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goodinfo['sid'],'gid' => $good['goodid'],'specid' => $good['specid'],'status >' => 0),array("SUM(num)"));
                        if($allsalenum + $good['num'] > $goodinfo['allstock']){
                            $tipinfo = '商品['.$goodinfo['name'].']已售罄，无法下单';
                            $this->renderError($tipinfo);
                        }
                    }
                    if($goodinfo['daystock'] > 0){
                        $nowtime = strtotime(date('Y-m-d',time()));
                        $daysalenum = pdo_getcolumn('wlmerchant_delivery_order',array('sid' => $goodinfo['sid'],'gid' => $good['goodid'],'specid' => $good['specid'],'status >' => 0,'createtime' => $nowtime),array("SUM(num)"));
                        if($daysalenum + $good['num'] > $goodinfo['daystock']){
                            $tipinfo = '商品['.$goodinfo['name'].']已售罄，无法下单';
                            $this->renderError($tipinfo);
                        }
                    }
                    //会员
                    if($goodinfo['vipstatus'] == 1 && $halfflag){
                        $goodarray['vipdiscount'] = $goodinfo['vipdiscount'];
                    }else{
                        $goodarray['vipdiscount'] = 0;
                    }
                    //计算配送费
                    if($type == 0){
                        $goodinfo['deliveryprice'] = 0;
                    }
                    if($goodinfo['deliveryprice']>0){
                        $storearray['packingmoney'] = sprintf("%.2f",$storearray['packingmoney'] + $goodinfo['deliveryprice']);
                    }
                    //创建小订单
                    $smallorder = array(
                        'uniacid'     => $goodinfo['uniacid'],
                        'aid'         => $goodinfo['aid'],
                        'sid'         => $goodinfo['sid'],
                        'gid'         => $good['goodid'],
                        'mid'         => $_W['mid'],
                        'specid'      => $good['specid'],
                        'money'       => sprintf("%.2f",($goodarray['price'] - $goodarray['vipdiscount'] + $goodinfo['deliveryprice']) * $good['num']),
                        'status'      => 0,
                        'num'         => $good['num'],
                        'price'       => sprintf("%.2f",$goodarray['price'] * $good['num']),
                        'vipdiscount' => sprintf("%.2f",$goodarray['vipdiscount'] * $good['num']),
                        'deliverymoney' => sprintf("%.2f",$goodinfo['deliveryprice'] * $good['num']),
                        'createtime'  => time()
                    );
                    $res = pdo_insert(PDO_NAME . 'delivery_order',$smallorder);
                    $smallorderid[] = pdo_insertid();
                    //累计金额
                    $goodallmoney = sprintf("%.2f",$goodallmoney + $smallorder['price']);
                    $alldiscount = sprintf("%.2f",$alldiscount + $smallorder['vipdiscount']);
                    $packingmoney = sprintf("%.2f",$packingmoney + $smallorder['deliverymoney']);
                    if($res){
                        pdo_delete('wlmerchant_delivery_shopcart',array('id'=>$goodid));
                    }else{
                        MysqlFunction::rollback();
                        $this->renderError('生成订单失败，请返回重试');
                    }
                    $num += $good['num'];
                }
                if($type == 0){
                    $deliveryallmoney = $packingmoney = $setdeliveryallmoney = $fightgroupid = $addressid = 0;
                }else if($type == 1 || ($type == 2 && !empty($storearray['expresspricestatus']))){
                    $express = $this->freight($addressid ,0,array('id'=>0,'merchantid'=>$goodinfo['sid']),$deliveryallmoney);
                    $expressid = $addressid;
                    $fightgroupid = $express['expressid'];
                    $setdeliveryallmoney = sprintf("%.2f",$deliveryallmoney + $packingmoney);
                }else{
                    $big = $storearray['makebiguser']>0 ? $store['sid'] : 0;
                    $express = Citydelivery::getMakePrice($store['sid'],$addressid,$big);
                    $deliveryallmoney = $express['total_price'];
                    $setdeliveryallmoney = $storearray['makebiguser']>0 ? sprintf("%.2f",$express['total_price'] + $packingmoney) : $packingmoney;
                    $expressid = $addressid;
                }
                $prices = sprintf("%.2f",$goodallmoney - $alldiscount);
                $settlementmoney = Citydelivery::getsettlementmoney($prices,$store['sid'],$setdeliveryallmoney);
                if($settlementmoney < 0.01){
                    $settlementmoney = Citydelivery::getsettlementmoney($prices,$store['sid'],$setdeliveryallmoney);
                }
                //满减活动
                if($storearray['deliveryfullid']>0){
                    $storearray['fulldkmoney'] = Fullreduce::getFullreduceMoney(sprintf("%.2f" ,$prices),$storearray['deliveryfullid']);
                }else{
                    $storearray['fulldkmoney'] = 0;
                }
                $prices = sprintf("%.2f",$prices + $deliveryallmoney + $packingmoney - $storearray['fulldkmoney']);
                //校验距离和起送金额
                if($type == 1){
                    if($storearray['deliverydistance']>0){
                        $distance = Store::getdistance($address['lng'],$address['lat'],$storearray['lng'],$storearray['lat']);
                        if($distance > $storearray['deliverydistance']*1000){
                            MysqlFunction::rollback();
                            $this->renderError('['.$storearray['storename'].']的配送距离为'.$storearray['deliverydistance'].'km,请更换配送位置或选择其他配送方式');
                        }
                    }
                    if($storearray['lowdeliverymoney']>0){
                        if($prices < $storearray['lowdeliverymoney']){
                            MysqlFunction::rollback();
                            $this->renderError('['.$storearray['storename'].']的起送金额为'.$storearray['lowdeliverymoney'].'元,请增购商品或选择其他配送方式');
                        }
                    }
                }
                //创建商户订单
                $orderData = [
                    'uniacid'         => $goodinfo['uniacid'] ,
                    'mid'             => $_W['mid'] ,
                    'sid'             => $goodinfo['sid'] ,
                    'aid'             => $goodinfo['aid'] ,
                    'plugin'          => 'citydelivery' ,
                    'payfor'          => 'deliveryOrder' ,
                    'orderno'         => createUniontid() ,
                    'status'          => 0 ,//订单状态：0未支,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
                    'createtime'      => TIMESTAMP ,
                    'price'           => $prices > 0 ? $prices : 0,
                    'vipbuyflag'      => $halfflag ,
                    'name'            => $username ,
                    'mobile'          => $mobile ,
                    'goodsprice'      => $goodallmoney ,
                    'expressid'       => $expressid ,
                    'buyremark'       => $store['remark'],
                    'settlementmoney' => $settlementmoney ,
                    'vipdiscount'     => $alldiscount,
                    'expressprcie'    => $deliveryallmoney,
                    'canceltime'      => time() + $settings['cancel'] * 60,
                    'num'             => $num,
                    'fightstatus'     => $type,
                    'fullreduceid'    => $storearray['deliveryfullid'],
                    'fullreducemoney' => $storearray['fulldkmoney'],
                    'fightgroupid'    => $fightgroupid,
                    'packingmoney'    => $packingmoney
                ];
                pdo_insert(PDO_NAME . 'order' , $orderData);
                $orderid = pdo_insertid();
                if($orderid){
                    $ordersid[] = $orderid;
                    foreach ($smallorderid as $smallid){
                        pdo_update('wlmerchant_delivery_order',array('tid' => $orderData['orderno'],'orderid' => $orderid),array('id' => $smallid));
                    }
                }else{
                    MysqlFunction::rollback();
                    $this->renderError('生成订单失败，请返回重试');
                }
            }
            MysqlFunction::commit();
            $data = base64_encode(json_encode($ordersid));
            $this->renderSuccess('订单信息',$data);

        }

    }



}