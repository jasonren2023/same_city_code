<?php
defined('IN_IA') or exit('Access Denied');
class Member {

    /**
     * 根据openid获取用户信息
     * @param $openid
     * @return array
     */
    static function wl_fans_info($openid) {
        load()->model('mc');
        $fansinfo = mc_fansinfo($openid);
        if (empty($fansinfo)) {
            $fansinfo = mc_init_fans_info($openid, true);
        }
        $userinfo = array(
            'uid'      => $fansinfo['uid'],
            'openid'   => $fansinfo['openid'],
            'nickname' => $fansinfo['nickname'],
            'avatar'   => $fansinfo['avatar'],
            'unionid'  => $fansinfo['unionid']
        );
        return $userinfo;
    }

    /**
     * 获取用户信息
     * @param $mcinfo 用户id|用户openid|条件
     * @param array $fields 字段，留空返回全部信息
     * @param bool $credit 是否需要返回微擎用户积分余额
     * @return bool
     */
    static function wl_member_get($mcinfo, $fields = [], $credit = true) {
        global $_W;
        if (is_array($mcinfo)) {
            //$where = $mcinfo;
            $member = pdo_get(PDO_NAME . 'member', array_merge($mcinfo, array('uniacid' => $_W['uniacid'])), $fields);
        } else {
            $field = is_array($fields) && count($fields) > 0 ? implode(',',$fields) : '*';
            $member = pdo_fetch("SELECT {$field} FROM ".tablename(PDO_NAME."member")
                ." WHERE id = {$mcinfo} OR openid = '{$mcinfo}' ");
            //$where = intval($mcinfo) ? ['id' => $mcinfo] : ['openid' => $mcinfo];
        }
        //$member = pdo_get(PDO_NAME . 'member', array_merge($where, array('uniacid' => $_W['uniacid'])), $fields);
//        if(!empty($member['openid']) && $_W['source'] == 1){
//            $newinfo  = self::wl_fans_info($member['openid']);
//            $member['avatar'] = $newinfo['avatar'];
//            $member['nickname'] = $newinfo['nickname'];
//            pdo_update(PDO_NAME . 'member',array('avatar' => $newinfo['avatar'],'nickname' => $newinfo['nickname']),array('openid' => $member['openid']));
//        }
        if (!empty($member['uid']) && $credit) {
            load()->model('mc');
            $credits = pdo_get('mc_members', array('uid' => $member['uid']), array('credit1', 'credit2'));
            $member['credit1'] = $credits['credit1'];
            $member['credit2'] = $credits['credit2'];
        }
        if(!empty($member['encodename'])){
            $member['nickname'] = base64_decode($member['encodename']);
        }
        return $member;
    }

    /**
     * 创建新用户
     * @param $userinfo 用户信息
     * @param string $channel 渠道wechat(微信公众号)wxapp(微信小程序)webapp(打包APP)mobile(H5手机号)
     * @return array|bool
     */
    static function wl_member_create($userinfo, $channel = 'wechat') {
        global $_W;
        $userinfo['encodename'] = base64_encode($userinfo['nickname']);
        $channels = ['wechat' => 'openid', 'wxapp' => 'wechat_openid', 'webapp' => 'webapp_openid', 'mobile' => 'mobile'];
        if (!in_array($channel, array_keys($channels))) {
            return error(1, '渠道错误，请检查后重试');
        }

        $uidstr = $channels[$channel];
        if (empty($userinfo[$uidstr])) {
            return error(1, '缺少用户标识，请检查后重试');
        }
        $member = self::wl_member_get([$uidstr => $userinfo[$uidstr]]);
        if (empty($member) && !empty($userinfo['unionid'])) {
            $member = self::wl_member_get(['unionid' => $userinfo['unionid']]);
        }

        $newinfo = [];
        $fields = self::wl_member_update_fields();
        foreach ($fields as $field) {
            if (!empty($userinfo[$field])) {
                $newinfo[$field] = $userinfo[$field];
            }
        }

        if (empty($member)) {

			$str = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890';
	        $randStr = str_shuffle($str);//打乱字符串
	        $code = substr($randStr,0,6);//substr(string,start,length);返回字符串的一部分

            $newinfo['avatar'] = $newinfo['avatar'] ? str_replace('132132', '132', $newinfo['avatar']) : './addons/'.MODULE_NAME.'/h5/resource/image/default_avatar.png';
            $member = array(
                'uniacid'    => $_W['uniacid'],
                'tokey'      => strtoupper(MD5(sha1(time() . random(12)))),
                'createtime' => time(),
                'dotime'     => time(),
                'nickname'   => '用户'.$code,
                'avatar'     => 'https://thirdwx.qlogo.cn/mmopen/vi_32/POgEwh4mIHO4nibH0KlMECNjjGxQUq24ZEaGT4poC6icRiccVGKSyXwibcPq4BWmiaIGuG1icwxaQX6grC9VemZoJ8rg/132'
            );
            $member = array_merge($member, $newinfo);
            if (!empty($member['openid'])) {
                $member['uid'] = mc_openid2uid($member['openid']);
            }
            if(empty($member['uid'])){
                $member['uid'] = self::createUserInfo($member['nickname']);
            }
            pdo_insert(PDO_NAME . 'member', $member);
            $member['id'] = pdo_insertid();
        } else {
            //判断是否存在uid 不存在则添加uid
            if(empty($member['nickname'])){
            	$newinfo['nickname'] = '用户'.$code;
            }
            if(empty($member['avatar'])){
            	$newinfo['avatar'] = 'https://thirdwx.qlogo.cn/mmopen/vi_32/POgEwh4mIHO4nibH0KlMECNjjGxQUq24ZEaGT4poC6icRiccVGKSyXwibcPq4BWmiaIGuG1icwxaQX6grC9VemZoJ8rg/132';
            }
            $uid = pdo_getcolumn(PDO_NAME."member",['id'=>$member['id']],'uid');
            if($uid <= 0){
                $newinfo['uid'] = self::createUserInfo($member['nickname']);
            }
            $member = self::wl_member_update($newinfo, $member['id']);
        }
        return $member;
    }

    /**
     * 更新用户信息
     * @param $userinfo 用户信息
     * @param $mid 用户ID
     * @return array|bool
     */
    static function wl_member_update($userinfo, $mid) {
        global $_W;
        load()->model('mc');
        $member = self::wl_member_get($mid, [], false);
        if (empty($member)) {
            return error(1, '用户不存在，请检查后重试');
        }
        if(empty($userinfo['uid'])){
            $userinfo['uid'] = $member['uid'];
        }
        $upgrade = array('dotime' => time());
        //同步用户积分信息
        $uid = 0;
        if (!empty($member['openid'])) {
            $uid = mc_openid2uid($member['openid']);
        }
        if (!empty($uid)) {
            if($userinfo['uid'] != $uid){
                $creditarray = pdo_get('mc_members',array('uid'=>$userinfo['uid']),array('credit1','credit2'));
                $member['credit1'] = $creditarray['credit1'];
                $member['credit2'] = $creditarray['credit2'];
                $zoreres = pdo_update('mc_members',array('credit1' => 0,'credit2' => 0),array('uid' => $userinfo['uid']));
                if($zoreres){
                    pdo_insert(PDO_NAME . 'credit2zero', ['uid'=>$userinfo['uid'],'uniacid'=>$_W['uniacid'],'createtime'=>time()]);
                }
            }
            $flaguid = $userinfo['uid'];
            $userinfo['uid'] = $uid;
            if (0 < $member['credit1']) {
                mc_credit_update($uid, 'credit1', $member['credit1'],array($uid, '合并用户数据修改积分,uid='.$flaguid, MODULE_NAME));
                $upgrade['credit1'] = 0;
                $userinfo['credit1'] = 0;
            }
            if (0 < $member['credit2']) {
                mc_credit_update($uid, 'credit2', $member['credit2'],array($uid, '合并用户数据修改余额,uid='.$flaguid, MODULE_NAME));
                $upgrade['credit2'] = 0;
                $userinfo['credit2'] = 0;
            }
        }else if(!empty($member['uid'])){
            //公众号同步到小程序数据
            if($userinfo['uid'] != $member['uid']){
                $creditarray = pdo_get('mc_members',array('uid'=>$member['uid']),array('credit1','credit2'));
                $member['credit1'] = $creditarray['credit1'];
                $member['credit2'] = $creditarray['credit2'];
                $zoreres = pdo_update('mc_members',array('credit1' => 0,'credit2' => 0),array('uid' => $member['uid']));
                if($zoreres){
                    pdo_insert(PDO_NAME . 'credit2zero', ['uid'=>$member['uid'],'uniacid'=>$_W['uniacid'],'createtime'=>time()]);
                }
            }
            $flaguid = $member['uid'];
            if (0 < $member['credit1']) {
                mc_credit_update($userinfo['uid'], 'credit1', $member['credit1'],array($userinfo['uid'], '合并用户数据修改积分,uid='.$flaguid, MODULE_NAME));
                $upgrade['credit1'] = 0;
                $userinfo['credit1'] = 0;
            }
            if (0 < $member['credit2']) {
                mc_credit_update($userinfo['uid'], 'credit2', $member['credit2'],array($userinfo['uid'], '合并用户数据修改余额,uid='.$flaguid, MODULE_NAME));
                $upgrade['credit2'] = 0;
                $userinfo['credit2'] = 0;
            }
        }

        //对比用户信息，不同则更新
        if (empty($member['tokey'])) {
            $upgrade['tokey'] = strtoupper(MD5(sha1(time() . random(12))));
        }
        $fields = self::wl_member_update_fields();
        foreach ($fields as $field) {
            if (!empty($userinfo[$field]) && $userinfo[$field] != $member[$field]) {
                $upgrade[$field] = $userinfo[$field];
            }
        }
        pdo_update(PDO_NAME . 'member', $upgrade, array('id' => $member['id']));
        $member = array_merge($member, $upgrade);

        if ($member['uid']) {
            $credit = pdo_get('mc_members', array('uid' => $member['uid']), array('credit1', 'credit2'));
            $member['credit1'] = $credit['credit1'] + $member['credit1'];
            $member['credit2'] = $credit['credit2'] + $member['credit2'];
        }

        //同步分销商表
        if ($member['distributorid']) {
            pdo_update('wlmerchant_distributor', array('mobile' => $member['mobile'], 'nickname' => $member['nickname'], 'realname' => $member['realname']), array('mid' => $member['id']));
        }

        return $member;
    }

    /**
     * 用户更新字段
     * @return array
     */
    private static function wl_member_update_fields() {
        return ['uid', 'avatar', 'nickname', 'encodename','openid', 'wechat_openid', 'webapp_openid', 'unionid', 'mobile', 'password', 'salt', 'session_key', 'credit1', 'credit2'];
    }

    /**
     * 合并用户的账号
     * @param $keyval 合并关键内容的值  手机号 || unionid
     * @param string $type mobile或unionid为关键
     * @return bool
     */
    static function wl_member_merge($keyval, $type = 'mobile') {
        global $_W;
        if (empty($keyval)) {
            return error(1, '合并内容值不得为空');
        }
        if (!in_array($type, ['mobile', 'unionid'])) {
            return error(1, '合并类型错误，检查后重试');
        }
        #1、获取符号条件的所账号信息
        $fields = self::wl_member_update_fields();
        $info = pdo_getall(PDO_NAME . "member", array('uniacid' => $_W['uniacid'], $type => $keyval), array_merge($fields, array('id', 'distributorid', 'tokey')), '', 'id asc');

        #2、判断账号信息是否大于等于 2 条，是通过循环合并账号信息  否则不管
        if (count($info) < 2) {
            return error(1, '不存在重复信息，无需合并');
        }

        #3、获取最早建立的账号 为主账号
        $earliest = $info[0];

        #4、通过循环合并账号信息
        WeliamWeChat::startTrans();//开启事务处理
        foreach ($info as $key => $val) {
            //修改的账号不包括最早建立的账号
            if ($val['id'] != $earliest['id']) {
                //---4-1: 合并第一项内容 账号信息  合并内容：member表中 不同的openid,mobile,unionid
                $userinfo = $val;
                unset($userinfo['id'], $userinfo['distributorid'], $userinfo['tokey']);
                self::wl_member_update($userinfo, $earliest['id']);

                //---4-2: 合并第二项内容 订单信息  合并内容：order订单表,rush_order订单表中的mid 全部改为最早账号的mid
                //修改 order 订单表
                pdo_update(PDO_NAME . "order", array('mid' => $earliest['id']), array('mid' => $val['id']));
                //修改 rush_order  订单表
                pdo_update(PDO_NAME . "rush_order", array('mid' => $earliest['id']), array('mid' => $val['id']));
                //修改disorder 表
                pdo_update(PDO_NAME . "disorder", array('buymid' => $earliest['id']), array('buymid' => $val['id']));

                //---4-3: 合并第三项内容 分销商信息
                if (empty($earliest['distributorid'])) {
                    $earliest['distributorid'] = $val['distributorid'];
                } else if ($val['distributorid'] > 0) {
                    //修改distributor表 金额 下级
                    $eardis = pdo_get('wlmerchant_distributor', array('id' => $earliest['distributorid']), array('dismoney', 'nowmoney', 'mid'));
                    $valdis = pdo_get('wlmerchant_distributor', array('id' => $val['distributorid']), array('dismoney', 'nowmoney', 'mid'));
                    if ($valdis['dismoney'] > 0) {
                        $newdismoney = sprintf("%.2f", $eardis['dismoney'] + $valdis['dismoney']);
                        pdo_update('wlmerchant_distributor', array('dismoney' => $newdismoney), array('id' => $earliest['distributorid']));
                    }
                    if ($valdis['nowmoney'] > 0) {
                        $newnowmoney = sprintf("%.2f", $eardis['nowmoney'] + $valdis['nowmoney']);
                        pdo_update('wlmerchant_distributor', array('nowmoney' => $newnowmoney), array('id' => $earliest['distributorid']));
                    }
                    pdo_update('wlmerchant_distributor', array('leadid' => $earliest['id']), array('leadid' => $val['id']));
                    pdo_delete('wlmerchant_distributor', array('id' => $val['distributorid']));
                    //修改disorder 分销订单表
                    pdo_update('wlmerchant_disorder', array('oneleadid' => $earliest['distributorid']), array('oneleadid' => $val['distributorid'], 'status' => 0));
                    pdo_update('wlmerchant_disorder', array('twoleadid' => $earliest['distributorid']), array('twoleadid' => $val['distributorid'], 'status' => 0));
                    //修改disapply 分销商提现表
                    pdo_update('wlmerchant_disapply', array('mid' => $earliest['id'], 'disid' => $earliest['distributorid']), array('mid' => $val['id']));
                }

                //---4-4: 合并第四项内容 商户信息
                //修改 merchantuser 将当前用户mid修改为最早账号的mid
                pdo_update(PDO_NAME . "merchantuser", ['mid' => $earliest['id']], ['mid' => $val['id']]);

                #5、账号合并完成  当前账号的信息已经全部合并给最早建立的账号  删除当前账号
                pdo_delete(PDO_NAME . "member", array('id' => $val['id']));
            }
        }
        WeliamWeChat::commit();//提交事务信息

        return $earliest;
    }

    /*
     * 更新积分信息
     */
    static function credit_update_credit1($mid, $credit1 = 0, $remark = '', $orderno = '') {
        global $_W;
        $member = self::wl_member_get($mid);
        $settings = Setting::wlsetting_read('base');
        if (empty($member)) {
            return error(-1, '用户不存在');
        }
        if (($member['credit1'] + $credit1) < 0) {
            return error(-1, '用户积分不足');
        }
        if(abs($credit1) > 0.01){
            //会员不存在uid时，更新模块本身的积分余额
            if (empty($member['uid'])) {
                return error(-1, 'UID不存在，请重新登录');
                //$res = pdo_update(PDO_NAME . "member", array('credit1' => $member['credit1'] + $credit1), array('id' => $member['id']));
            } else {
                load()->model('mc');
                if (empty($remark)) {
                    $remark = $settings['name'] ? $settings['name'] . '积分操作' : '智慧城市积分操作';
                }
                $res = mc_credit_update($member['uid'], 'credit1', $credit1, array($member['uid'], $remark, MODULE_NAME));
            }
            if(is_error($res)){
                return $res;
            }else{
                $data = array('uid' => $member['uid'], 'uniacid' => $_W['uniacid'], 'mid' => $member['id'], 'num' => $credit1, 'createtime' => TIMESTAMP, 'type' => 1, 'remark' => $remark, 'ordersn' => $orderno);
                pdo_insert(PDO_NAME . "creditrecord", $data);
                $newCredit = pdo_getcolumn('mc_members',['uid' => $member['uid']],'credit1');
                //积分变更通知
                $trade   = Setting::wlsetting_read('trade');
                $integralText = $trade['credittext'] ? : '积分';
                if(Customized::init('customized530') && $credit1 > 0){
                    $payinfo = [
                        'first'         => "收益到账通知：",
                        'profit_money'  => "{$credit1}{$integralText}",//收益金额
                        'profit_source' => $remark,//收益来源
                        'time'          => date("Y年m月d日 Y:i:s",time()),//变更日期
                        'remark'        => '',
                    ];
                    TempModel::sendInit('profit',$member['id'],$payinfo,$_W['source']);
                }else if(!Customized::init('customized530')){
                    $integral = trim($credit1,'-');
                    $payinfo = [
                        'first'          => "您的{$integralText}已发生变化",
                        'old_number'     => "原有{$integralText}：{$member['credit1']},{$remark}:{$integral}",//原有数量
                        'current_number' => "现有{$integralText}：{$newCredit}",//变更结果
                        'time'           => date("Y-m-d H:i:s",time()),//变更日期
                        'remark'         => "点击查看{$integralText}变更记录",
                        'change_num'     => $credit1,
                        'balance'        => $newCredit,
                        'change_remark'  => $remark,
                    ];
                    $url = h5_url('pages/subPages/IntegralRecord/IntegralRecord');
                    TempModel::sendInit('change',$member['id'],$payinfo,$_W['source'],$url);
                }
            }
            return TRUE;
        }else{
            return error(-1, '修改积分数额不正确');
        }
    }

    /*
     * 更新余额信息
     *
     */
    static function credit_update_credit2($mid, $credit2 = 0, $remark = '', $orderno = '') {//余额
        global $_W;
        $member = self::wl_member_get($mid);
        $settings = Setting::wlsetting_read('base');
        if (empty($member)) {
            return error(-1, '用户不存在');
        }
        if (($member['credit2'] + $credit2) < 0) {
            return error(-1, '用户余额不足');
        }
        if(abs($credit2)>0){
            //会员不存在uid时，更新模块本身的积分余额
            if (empty($member['uid'])) {
                return error(-1, 'UID不存在，请重新登录');
                //pdo_update(PDO_NAME . "member", array('credit2' => $member['credit2'] + $credit2), array('id' => $member['id']));
            } else {
                load()->model('mc');
                $header = $remark ? $settings['name'] . ':' : '智慧城市余额操作';
                $res = mc_credit_update($member['uid'], 'credit2', $credit2, array($member['uid'], $header . $remark, MODULE_NAME));
            }
            if(is_error($res)){
                return $res;
            }else {
                $data = array('uid' => $member['uid'], 'uniacid' => $_W['uniacid'], 'mid' => $member['id'], 'num' => $credit2, 'createtime' => TIMESTAMP, 'type' => 2, 'remark' => $remark, 'ordersn' => $orderno);
                pdo_insert(PDO_NAME . "creditrecord", $data);
            }
        }
        return TRUE;
    }

    //验证一卡通会员
    static function checkhalfmember($url = '') {
        global $_W;
        if (empty($_W['mid'])) {
            Uniapp::renderError('未登录');
        }
        $now = time();
        if ($_W['wlsetting']['halfcard']['halfcardtype'] == 2) {
            $halfcardflag = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_halfcardmember') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} AND aid = {$_W['aid']} AND expiretime > {$now} AND disable != 1");
        } else {
            $halfcardflag = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_halfcardmember') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} AND expiretime > {$now} AND disable != 1");
        }
        return $halfcardflag;
    }

    static function payChargeNotify($params) {
        global $_W;
        Util::wl_log('payResult_notify', PATH_DATA . "merchant/data/", $params); //写入异步日志记录
        $order_out = pdo_get(PDO_NAME . 'order', array('orderno' => $params['tid']));
        $_W['uniacid'] = $order_out['uniacid'];
        if ($order_out['status'] == 0 || $order_out['status'] == 5) {
            $data = self::getVipPayData($params); //得到支付参数，处理代付
            if ($data['status'] == 1) {
                $data['status'] = 3;
            }
            pdo_update(PDO_NAME . 'order', $data, array('orderno' => $params['tid']));
            $res1 = self::credit_update_credit2($order_out['mid'], $order_out['price'], '余额充值', $order_out['orderno']);
            $settings = Setting::wlsetting_read('recharge');
            $count = count($settings['kilometre']);
            for ($i = 0; $i < $count; $i++) {
                $array[$i]['kilometre'] = $settings['kilometre'][$i];
                $array[$i]['kilmoney'] = $settings['kilmoney'][$i];
            }
            $give = 0;
            foreach ($array as $key => $val) {
                $dos[$key] = $val['kilometre'];
            }
            array_multisort($dos, SORT_ASC, $array);
            foreach ($array as $key => $ar) {
                if ($order_out['price'] > $ar['kilometre'] || $order_out['price'] == $ar['kilometre']) {
                    $give = $ar['kilmoney'];
                }
            }
            if ($give > 0) {
                $res2 = self::credit_update_credit2($order_out['mid'], $give, '余额充值赠送', $order_out['orderno']);
            }
        }
    }

    static function payChargeReturn($params) {
        wl_message('充值成功', h5_url('pages/mainPages/userCenter/userCenter'));
    }

    static function getVipPayData($params) {
        global $_W;
        $data = array('status' => $params['result'] == 'success' ? 1 : 0);
        if ($params['is_usecard'] == 1) {
            $fee = $params['card_fee'];
            $data['is_usecard'] = 1;
        } else {
            $fee = $params['fee'];
        }
        //$paytype = array('credit' => 1, 'wechat' => 2, 'alipay' => 3, 'delivery' => 4, 'wxapp' => 5);
        $data['paytype'] = $params['type'];
        if ($params['type'] == 'wechat') $data['transid'] = $params['tag']['transaction_id'];
        $data['paytime'] = TIMESTAMP;
        return $data;
    }
    /**
     * Comment: 生成微信用户信息
     * Author: zzw
     * Date: 2020/3/9 10:32
     * @param $nickname
     * @return mixed
     */
    public static function createUserInfo($nickname){
        global $_W;
        $group_id = pdo_getcolumn('mc_groups', ['uniacid' => $_W['uniacid'], 'isdefault' => 1], 'groupid');
        $salt = random(8);
        $data = [
            'uniacid'    => $_W['uniacid'],
            'salt'       => $salt,
            'groupid'    => $group_id,
            'createtime' => TIMESTAMP,
            'nickname'   => $nickname,
        ];
        //储存微擎用户数据信息
        pdo_insert('mc_members', $data);
        $uid = pdo_insertid();
        return $uid;
    }

    /**
     * Comment: 添加用户标签关联
     * Author: wlf
     * Date: 2022/07/15 10:21
     * @param $nickname
     * @return mixed
     */

    public static function addUserlable($userlable,$mid){
        global $_W;
        foreach ($userlable as $lable){
            $record = pdo_get(PDO_NAME.'userlabel_record',array('mid'=>$mid,'labelid'=>$lable),['id','times']);
            if(empty($record)){
                $data = [
                    'uniacid' => $_W['uniacid'],
                    'aid'     => $_W['aid'],
                    'labelid' => $lable,
                    'mid'     => $mid,
                    'times'   => 1,
                    'createtime' => time(),
                    'dotime'  => time()
                ];
                pdo_insert(PDO_NAME.'userlabel_record', $data);
            }else{
                $times = $record['times'] + 1;
                pdo_update(PDO_NAME.'userlabel_record',array('times' => $times,'dotime' => time()),array('id' => $record['id']));
            }
        }
    }






}
