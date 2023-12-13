<?php

defined('IN_IA') or exit('Access Denied');
class MemberModuleUniapp extends Uniapp
{
    /**
     * Comment: 获取用户注册设置信息
     * Author: zzw
     * Date: 2019/8/8 9:26
     */
    public function getRegisterSet()
    {
        global $_W , $_GPC;
        #1、获取基本设置
        $set                = Setting::wlsetting_read("userset");
        $data['sms_switch'] = $set['smsver'] ? $set['smsver'] : 0;//0=启用 1=禁用
        $data['img_switch'] = $set['verifycode'] ? $set['verifycode'] : 0;//1=启用 0=禁用
        #2、获取用户协议
        $userSet          = Setting::wlsetting_read('userset');
        $data['describe'] = htmlspecialchars_decode($userSet['describe']);//用户协议
        $data['privacy'] = htmlspecialchars_decode($userSet['privacy']);//用户协议
        $this->renderSuccess('获取用户注册设置信息' , $data);
    }
    /**
     * Comment: 用户申请注册账号
     * Author: zzw
     * Date: 2019/8/8 11:14
     */
    public function register()
    {
        global $_W , $_GPC;
        #1、参数接收
        $mobile   = trim($_GPC['phone']);//手机号
        $password = trim($_GPC['password']);//密码
        $code     = trim($_GPC['code']);//验证码
        if (!$mobile) {
            $this->renderError('请输入手机号');
        }
        if (!$password) {
            $this->renderError('请输入登录密码');
        }
        //短信验证码登录  判断验证码是否输入正确
        if (empty($_W['wlsetting']['userset']['smsver'])) {
            $pin_info = pdo_get('wlmerchant_pincode' , ['mobile' => $mobile]);
            if (empty($pin_info)) {
                $this->renderError('验证码错误');
            }
            if ($pin_info['time'] < time() - 300) {
                $this->renderError('验证码已过期，请重新获取' , ['code' => 1]);
            }
            if ($code != $pin_info['code']) {
                $this->renderError('验证码错误');
            }
        }
        #2、判断是否已经注册
        $have = Member::wl_member_get(['mobile' => $mobile] , ['id'] , false);
        if ($have) {
            $this->renderError('当前手机已被注册，请直接登录');
        }
        #3、生成用户信息数据
        $salt     = random(8);
        $nickname = substr($mobile , 0 , 3) . '****' . substr($mobile , -4 , 4);
        $data2    = [
            'nickname' => $nickname ,
            'mobile'   => $mobile ,
            'password' => md5($password . $salt) ,
            'salt'     => $salt ,
        ];
        $member   = Member::wl_member_create($data2 , 'mobile');
        if (!is_error($member)) {
            $userinfo['mobile'] = $mobile;
            $userinfo['pwd']    = $member['password'];
            $userinfo['tokey']  = $member['tokey'];
            wl_setcookie('usersign' , $userinfo , 3600 * 24 * 30);
            $token = pdo_getcolumn(PDO_NAME . 'login' , ['token' => $userinfo['tokey']] , 'secret_key');
            if (empty($token)) {
                $res   = Login::generateToken($userinfo['tokey'] , 'login');
                $token = $res['message'];
            }
            wl_setcookie('user_token' , $token , 3600 * 24 * 30);
            $this->renderSuccess('注册成功');
        }
        else {
            $this->renderError('注册失败');
        }
    }
    /**
     * Comment: 用户登录
     * Author: zzw
     * Date: 2019/8/8 13:40
     */
    public function userLogin()
    {
        global $_W , $_GPC;
        #1、参数获取
        $type    = $_GPC['type'] ? $_GPC['type'] : 1;//1=账号密码登录 2=短信验证码登录
        $phone   = $_GPC['phone'];//手机号
        $pwd     = $_GPC['password'];// 登录密码/短信验证码
        $backUrl = urldecode($_GPC['backurl']);
        if (!$phone) $this->renderError('请输入登录账号');
        if (!$pwd) $this->renderError($type == 1 ? '请输入密码' : '请输入验证码');
        #2、根据登录方式进行判断是否登录成功
        $member = Member::wl_member_get(['mobile' => $phone] , [
            'password' ,
            'id' ,
            'salt' ,
            'tokey' ,
            'openid' ,
            'blackflag'
        ]);
        if (!$member) $this->renderError('账号不存在，请先注册!');
        if ($type == 1) {
            //账号密码登录  判断密码是否正确
            if ($member['password'] != md5($pwd . $member['salt'])) $this->renderError('密码错误!');
        }
        else {
            //短信验证码登录  判断验证码是否输入正确
            $pin_info = pdo_get('wlmerchant_pincode' , ['mobile' => $phone]);
            if (empty($pin_info)) {
                $this->renderError('验证码错误');
            }
            if ($pin_info['time'] < time() - 300) {
                $this->renderError('验证码已过期，请重新获取' , ['code' => 1]);
            }
            if ($pwd != $pin_info['code']) $this->renderError('验证码错误');
        }
        if ($member['blackflag'] > 0) {
            $tips = $_W['wlsetting']['userset']['black_desc'] ? $_W['wlsetting']['userset']['black_desc'] : '您被禁止访问,如有疑问请联系客服';
            $this->renderError($tips);
        }
        #3、密码输出正确 成功登录
        $userInfo = [
            'mobile' => $phone ,
            'pwd'    => $pwd ,
            'openid' => $member['openid'] ? $member['openid'] : $_W['wlmember']['openid'] ,
            'tokey'  => $member['tokey'] ? $member['tokey'] : $_W['wlmember']['tokey'] ,
        ];
        wl_setcookie('usersign' , $userInfo , 3600 * 24 * 30);
        $res   = Login::generateToken($userInfo['tokey'] , 'login');
        $token = $res['message'];
        wl_setcookie('user_token' , $token , 3600 * 24 * 30);
        wl_setcookie('exitlogin_code' , [] , 100);
        #3、登录成功 返回跳转地址
        $link = $backUrl ? $backUrl : '';
        $this->renderSuccess('登录成功' , ['back_url' => $link , 'token' => $token]);
    }
    /**
     * Comment: 验证短信验证码是否正确
     * Author: wlf
     * Date: 2019/03/31 10:20
     */
    public function checkCode()
    {
        global $_W , $_GPC;
        $phone    = $_GPC['phone'];
        $code     = $_GPC['code'];
        $pin_info = pdo_get('wlmerchant_pincode' , ['mobile' => $phone]);
        if (empty($pin_info)) {
            $this->renderError('验证码错误');
        }
        if ($pin_info['time'] < time() - 300) {
            $this->renderError('验证码已过期，请重新获取');
        }
        if ($code != $pin_info['code']) $this->renderError('验证码错误');
        $this->renderSuccess('正确');
    }
    /**
     * Comment: 重置登录密码
     * Author: zzw
     * Date: 2019/8/8 14:13
     */
    public function resetPassword()
    {
        global $_W , $_GPC;
        #1、参数获取
        $phone = $_GPC['phone'];
        $pwd   = $_GPC['password'];
        if (!$phone) $this->renderError('请输入账号！');
        if (!$pwd) $this->renderError('请输入新的密码！');
        #2、获取用户信息
        $member = Member::wl_member_get(['mobile' => $phone] , ['password' , 'id' , 'salt' , 'tokey' , 'openid']);
        if (!$member) $this->renderError('用户信息不存在，请先注册!');
        #3、判断密码是否修改
        $salt     = random(8);
        $password = md5($pwd . $salt);
        if ($member['password'] == $password) $this->renderError('密码不能与原密码一致!');
        #4、修改密码的操作
        $res = Member::wl_member_update(['password' => $password , 'salt' => $salt] , $member['id']);
        if (!is_error($res)) {
            $this->renderSuccess('修改成功');
        }
        else {
            $this->renderError('修改失败');
        }
    }
    /**
     * Comment: 获取用户订单列表信息
     * Author: wlf
     * Date: 2019/8/10 10:58
     */
    public function orderList()
    {
        global $_W , $_GPC;
        $trade    = Setting::wlsetting_read('trade');
        $authinfo = Cloud::wl_syssetting_read('authinfo');
        if ($authinfo['code'] == '116495A4C76AB8B1BBC387C941FE7424' || $authinfo['code'] == 'E9FEC02FCF843080D04DF170CBA8EF7A') {
            $data['changeflag'] = 1;
        }
        $page       = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = 10;
        $page_start = $page * $page_index - $page_index;
        $status     = $_GPC['status'] ? $_GPC['status'] : 0;
        $where      = " uniacid = {$_W['uniacid']} and mid = {$_W['mid']}";
        if ($status != 10) {
            if($data['changeflag'] > 0 && $status == 1 ){
                $where .= " and status IN (1,8)";
            }else{
                $where .= " and status = {intval($status)}";
            }
        }
        $myorder = pdo_fetchall('SELECT id,createtime,sid,num,status,price,paidprid,applyrefund,expressid,paytype,orderno as tid,pftorderinfo, "a" FROM '
            . tablename(PDO_NAME . "order")
            . " where {$where} and orderno != 666666 AND plugin != 'distribution' AND plugin != 'housekeep' "
            . ' UNION ALL SELECT id,createtime,sid,num,status,price,paidprid,applyrefund,expressid,paytype,orderno as tid,pftorderinfo, "b" FROM '
            . tablename(PDO_NAME . "rush_order")
            . " where {$where} ORDER BY createtime DESC LIMIT {$page_start},{$page_index}");
        //计算页码
        $total1            = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "order") . " where {$where} and orderno != 666666 AND plugin != 'distribution' AND plugin != 'housekeep' ");
        $total2            = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename(PDO_NAME . "rush_order") . " where {$where}");
        $total             = $total1 + $total2;
        $data['pagetotal'] = ceil($total / 10);
        if (empty($data['pagetotal'])) {
            $data['pagetotal'] = 1;
        }
        //处理订单
        foreach ($myorder as $k => &$v) {
            if ($v['a'] == 'a') {
                $ndorder = pdo_get(PDO_NAME . 'order' , ['id' => $v['id'] , 'uniacid' => $_W['uniacid']] , [
                    'id' ,
                    'payfor' ,
                    'num' ,
                    'plugin' ,
                    'fkid' ,
                    'goodsprice' ,
                    'specid' ,
                    'orderno' ,
                    'fightstatus' ,
                    'fightgroupid' ,
                    'recordid'
                ]);
                if ($ndorder['specid'] && $ndorder['plugin'] != 'bargain' && $ndorder['plugin'] != 'yellowpage') {
                    if($ndorder['plugin'] == 'activity'){
                        $v['optionname'] = pdo_getcolumn(PDO_NAME . 'activity_spec' , ['id' => $ndorder['specid']] , 'name');
                    }else{
                        $v['optionname'] = pdo_getcolumn(PDO_NAME . 'goods_option' , ['id' => $ndorder['specid']] , 'title');
                    }
                }
                $v['activityid'] = $ndorder['fkid'];
                if ($ndorder['plugin'] == 'coupon') {
                    $goods           = wlCoupon::getSingleCoupons($ndorder['fkid'] , 'title,sub_title,logo,id,allowapplyre');
                    $v['goodsname']  = $goods['title'];
                    $v['goodsimg']   = tomedia($goods['logo']);
                    $v['optionname'] = $goods['sub_title'];
                    $coupon          = pdo_get(PDO_NAME . 'member_coupons' , [
                        'uniacid' => $_W['uniacid'] ,
                        'orderno' => $ndorder['orderno']
                    ] , ['status' , 'id' , 'usetimes' , 'endtime' , 'usedtime']);
                    $this->checkcoupon($coupon , $ndorder);
                    $v['typeid']   = 5;
                    $v['recordid'] = $ndorder['recordid'];
                }
                else if ($ndorder['plugin'] == 'wlfightgroup') {
                    $goods            = Wlfightgroup::getSingleGood($ndorder['fkid'] , 'name,logo,id,allowapplyre');
                    $v['goodsname']   = $goods['name'];
                    $v['goodsimg']    = tomedia($goods['logo']);
                    $v['fightstatus'] = $ndorder['fightstatus'];
                    $v['recordid']    = $ndorder['recordid'];
                    if ($v['fightstatus'] == 1) {
                        $v['buystatus'] = '拼团';
                    }
                    else {
                        $v['buystatus'] = '单购';
                    }
                    switch ($v['status']) {
                        case '1':
                            if ($ndorder['fightgroupid']) {
                                $groupstatus      = pdo_getcolumn(PDO_NAME . 'fightgroup_group' , ['id' => $ndorder['fightgroupid']] , 'status');
                                $v['groupstatus'] = $groupstatus;
                                if ($groupstatus == 1) {
                                    $v['statusName'] = '组团中';
                                }
                                else if ($groupstatus == 2) {
                                    $v['statusName'] = '待使用';
                                }
                            }
                            else {
                                $v['statusName'] = '待使用';
                            }
                            break;
                        case '2':
                            $v['statusName'] = '已消费';
                            break;
                        case '3':
                            $v['statusName'] = '已完成';
                            break;
                        case '8':
                            $v['statusName'] = '待发货';
                            break;
                        case '4':
                            $v['statusName'] = '待收货';
                            break;
                        case '5':
                            $v['statusName'] = '已取消';
                            break;
                        case '6':
                            $v['statusName'] = '待退款';
                            break;
                        case '7':
                            $v['statusName'] = '已退款';
                            break;
                        case '9':
                            $v['statusName'] = '已过期';
                            break;
                        default:
                            $v['statusName'] = '待付款';
                            break;
                    }
                    $v['typeid'] = 3;
                }
                else if ($ndorder['plugin'] == 'groupon') {
                    $goods = pdo_get('wlmerchant_groupon_activity' , ['id' => $ndorder['fkid']] , [
                        'name' ,
                        'subtitle' ,
                        'thumb' ,
                        'id' ,
                        'allowapplyre',
                        'pftid'
                    ]);
                    if (empty($v['optionname'])) {
                        $v['optionname'] = $goods['subtitle'];
                    }
                    $v['goodsname'] = $goods['name'];
                    $v['goodsimg']  = tomedia($goods['thumb']);
                    $v['typeid']    = 2;
                    if($goods['pftid'] > 0){
                        $v['storename'] = '平台商品';
                    }
                }
                else if ($ndorder['plugin'] == 'pocket') {
                    $infrom   = Pocket::getInformations($ndorder['fkid']);
                    $typename = pdo_getcolumn(PDO_NAME . 'pocket_type' , [
                        'uniacid' => $_W['uniacid'] ,
                        'id'      => $infrom['type']
                    ] , 'title');
                    $typeimg  = pdo_getcolumn(PDO_NAME . 'pocket_type' , [
                        'uniacid' => $_W['uniacid'] ,
                        'id'      => $infrom['type']
                    ] , 'img');
                    if ($ndorder['fightstatus'] == 1) {
                        $v['goodsname'] = '发布' . $typename . '信息';
                    }
                    else if ($ndorder['fightstatus'] == 2) {
                        $v['goodsname'] = '置顶' . $typename . '信息';
                    }else if ($ndorder['fightstatus'] == 7) {
                        $v['goodsname'] = '支付' . $typename . '信息转让费';
                    }
                    else {
                        $v['goodsname'] = '支付' . $typename . '信息红包';
                    }
                    $v['goodsimg'] = tomedia($typeimg);
                    $v['typeid']   = 11;
                    $v['num'] = 1;
                }
                else if ($ndorder['plugin'] == 'store') {
                    $chargetype     = pdo_get('wlmerchant_chargelist' , ['id' => $ndorder['fkid']] , ['name']);
                    $v['goodsname'] = '商户入驻' . $chargetype['name'];
                    $typeimg        = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $v['sid']] , 'logo');
                    $v['goodsimg']  = tomedia($typeimg);
                    $v['typeid']    = 10;
                    $v['num'] = 1;
                }
                else if ($ndorder['plugin'] == 'activity') {
                    $activity       = pdo_get('wlmerchant_activitylist' , ['id' => $ndorder['fkid']] , [
                        'title' ,
                        'thumb'
                    ]);
                    $v['goodsname'] = $activity['title'];
                    $typeimg        = $activity['thumb'];
                    $v['goodsimg']  = tomedia($typeimg);
                }
                else if ($ndorder['plugin'] == 'taxipay') {
                    $master            = pdo_get('wlmerchant_taxipay_master' , ['id' => $ndorder['fkid']]);
                    $v['goodsname']    = '支付给[' . $master['name'] . ']师傅';
                    $v['optionname']   = '车牌号:' . $master['plate1'] . $master['plate2'] . $master['plate_number'];
                    $v['goodsimg']     = tomedia(pdo_getcolumn(PDO_NAME . 'member' , ['id' => $master['mid']] , 'avatar'));
                    $v['storename']    = '出租车买单';
                    $v['typeid']       = 12;
                    $v['mastermobile'] = $master['mobile'];
                }
                else if ($ndorder['plugin'] == 'consumption') {
                    $activity       = pdo_get('wlmerchant_consumption_goods' , ['id' => $ndorder['fkid']] , [
                        'title' ,
                        'thumb'
                    ]);
                    $v['goodsname'] = $activity['title'];
                    $v['goodsimg']  = $activity['thumb'];
                    $v['storename'] = '系统平台';
                    $v['typeid']    = 8;
                    $v['num'] = 1;
                }
                else if ($ndorder['plugin'] == 'member') {
                    $v['goodsname'] = $trade['moneytext'] . '充值';
                    $v['goodsimg']  = tomedia($_W['wlmember']['avatar']);
                    $v['storename'] = '系统平台';
                }
                else if ($ndorder['plugin'] == 'halfcard') {
                    $v['goodsname'] = '在线买单';
                    $v['goodsimg']  = pdo_getcolumn(PDO_NAME . 'merchantdata' , [
                        'id'      => $v['sid'] ,
                        'uniacid' => $_W['uniacid']
                    ] , 'logo');
                    $v['goodsimg']  = tomedia($v['goodsimg']);
                    $v['typeid']    = 6;
                    $v['num'] = 1;
                }
                else if ($ndorder['plugin'] == 'bargain') {
                    $goods            = pdo_get('wlmerchant_bargain_activity' , ['id' => $ndorder['fkid']] , [
                        'name' ,
                        'thumb' ,
                        'allowapplyre'
                    ]);
                    $v['goodsname']   = $goods['name'];
                    $v['goodsimg']    = tomedia($goods['thumb']);
                    $v['fightstatus'] = $ndorder['fightstatus'];
                    if ($v['expressid'] && $v['status'] == 8) {
                        $v['statusName'] = '待发货';
                    }
                    $v['typeid'] = 7;
                }
                else if ($ndorder['plugin'] == 'citycard') {
                    if ($ndorder['fightstatus'] == 1) {
                        $v['goodsname']  = '同城名片套餐付费';
                        $v['optionname'] = pdo_getcolumn(PDO_NAME . "citycard_meals" , ['id' => $ndorder['fkid']] , 'name');
                    }
                    else {
                        $v['goodsname']  = '同城名片置顶付费';
                        $v['optionname'] = pdo_getcolumn(PDO_NAME . "citycard_tops" , ['id' => $ndorder['fkid']] , 'name');
                    }
                    $v['storename'] = '同城名片';
                    $userLogo       = pdo_getcolumn(PDO_NAME . "citycard_lists" , ['id' => $ndorder['specid']] , 'logo');
                    $v['goodsimg']  = !empty($userLogo) ? tomedia($userLogo) : $_W['wlmember']['avatar'];
                    $v['num'] = 1;
                }
                else if ($ndorder['plugin'] == 'citydelivery') {
                    $smallorders = pdo_fetchall("SELECT gid,num,specid FROM " . tablename('wlmerchant_delivery_order') . "WHERE orderid = {$ndorder['id']} ORDER BY price DESC");
                    foreach ($smallorders as $ke => $orr) {
                        $good = pdo_get('wlmerchant_delivery_activity' , ['id' => $orr['gid']] , ['name' , 'thumb']);
                        if (empty($ke)) {
                            $v['goodsimg'] = tomedia($good['thumb']);
                        }
                        if ($ke > 0) {
                            if ($orr['specid'] > 0) {
                                $specname       = pdo_getcolumn(PDO_NAME . 'delivery_spec' , ['id' => $orr['specid']] , 'name');
                                $v['goodsname'] .= ' + [' . $good['name'] . '/' . $specname . '] X' . $orr['num'];
                            }
                            else {
                                $v['goodsname'] .= ' + [' . $good['name'] . '] X' . $orr['num'];
                            }
                        }
                        else {
                            if ($orr['specid'] > 0) {
                                $specname       = pdo_getcolumn(PDO_NAME . 'delivery_spec' , ['id' => $orr['specid']] , 'name');
                                $v['goodsname'] .= '[' . $good['name'] . '/' . $specname . '] X' . $orr['num'];
                            }
                            else {
                                $v['goodsname'] .= '[' . $good['name'] . '] X' . $orr['num'];
                            }
                        }
                    }
                    if ($v['status'] == 1) {
                        $v['statusName'] = '待取用';
                    }
                    else if ($v['status'] == 8) {
                        $v['statusName'] = '待接单';
                    }
                    else if ($v['status'] == 4) {
                        $v['statusName'] = '配送中';
                    }
                    else if ($v['status'] == 7) {
                        $v['statusName'] = '已退款';
                    }
                    $v['num'] = 1;
                    //判断退款
                    if ($v['status'] != 7) {
                        $canre = pdo_getcolumn(PDO_NAME . 'aftersale' , [
                            'orderid' => $v['id'] ,
                            'plugin'  => 'citydelivery' ,
                            'status'  => [1 , 2]
                        ] , 'id');
                        if ($canre) {
                            $v['status']     = 10;
                            $v['statusName'] = '退款处理中';
                        }
                    }
                }
                else if ($ndorder['plugin'] == 'yellowpage') {
                    $page = pdo_get('wlmerchant_yellowpage_lists',array('id' => $ndorder['fkid']),array('name','logo'));
                    if ($ndorder['fightstatus'] == 1) {
                        $v['goodsname'] = '认领[' . $page['name'] . ']114信息';
                    }
                    else if ($ndorder['fightstatus'] == 2) {
                        $v['goodsname'] = '查阅[' . $page['name'] . ']114信息';
                    }
                    else if ($ndorder['fightstatus'] == 3) {
                        $v['goodsname'] = '入驻[' . $page['name'] . ']114信息';
                    }
                    else if ($ndorder['fightstatus'] == 4) {
                        $v['goodsname'] = '续费[' . $page['name'] . ']114信息';
                    }
                    $v['goodsimg'] = tomedia($page['logo']);
                    $v['typeid']   = 15;
                    $v['num'] = 1;
                    $v['storename'] = '黄页114';
                    if($ndorder['specid']){
                        $mealname =  pdo_getcolumn(PDO_NAME.'yellowpage_meals',array('id'=>$ndorder['specid']),'name');
                        $v['goodsname'] .= ",套餐[".$mealname."]";
                    }
                }
                else if ($ndorder['plugin'] == 'recruit') {
                    //订单类型为求职招聘时  获取对应的信息
                    $recruit = pdo_get(PDO_NAME."recruit_recruit",['id'=>$ndorder['fkid']]);
                    //判断发布方    招聘类型:1=个人招聘,2=企业招聘
                    if($recruit['recruitment_type'] == 1){
                        $release = pdo_get(PDO_NAME."member",['id'=>$recruit['release_mid']],['nickname','avatar']);
                        $v['goodsname'] = '个人招聘：'.$release['nickname'];
                        $v['goodsimg'] = tomedia($release['avatar']);
                    }else{
                        $release = pdo_get(PDO_NAME."merchantdata",['id'=>$recruit['release_sid']],['storename','logo']);
                        $v['goodsname'] = '企业招聘：'.$release['storename'];
                        $v['goodsimg'] = tomedia($release['logo']);
                    }
                    $v['storename'] = '求职招聘';
                    $v['typeid'] = 16;
                    $v['num'] = 1;
                }
                else if ($ndorder['plugin'] == 'dating') {
                    //订单类型为相亲交友时  获取对应的信息
                    if($ndorder['payfor'] == 'datingTop'){
                        //信息置顶
                        [$nickname,$avatar] = Dating::handleUserInfo($_W['mid']);
                        $v['goodsname'] = '信息置顶：'.$nickname;
                        $v['goodsimg'] = tomedia($avatar);
                    }else if($ndorder['payfor'] == 'datingVip'){
                        //会员卡开通
                        [$nickname,$avatar] = Dating::handleUserInfo($_W['mid']);
                        $v['goodsname'] = '会员卡开通：'.$nickname;
                        $v['goodsimg'] = tomedia($avatar);
                    }else if($ndorder['payfor'] == 'datingMatchmaker'){
                        //红娘申请
                        $matchmaker = pdo_get(PDO_NAME."dating_matchmaker",['id'=>$ndorder['fkid']],['nickname','avatar']);
                        $v['goodsname'] = '红娘申请：'.$matchmaker['nickname'];
                        $v['goodsimg'] = tomedia($matchmaker['avatar']);
                    }
                    $v['storename'] = '相亲交友';
                    $v['typeid'] = 17;
                    $v['num'] = 1;
                }
                else if ($ndorder['plugin'] == 'vehicle') {
                    //订单类型为顺风车时  获取对应的信息
                    $v['goodsname'] = '顺风车';
                    $v['goodsimg'] = tomedia($_W['wlmember']['avatar']);
                    $v['storename'] = '顺风车';
                    $v['typeid'] = 18;
                    $v['num'] = 1;
                }
                else if ($ndorder['plugin'] == 'house') {

                    //订单类型为房源发布时  获取对应的信息
                    if($ndorder['fightstatus'] == 1){
                        $v['storename'] = '新房信息发布';
                        $house = pdo_get('wlmerchant_new_house',array('id' => $ndorder['fkid']),array('title','cover_image'));
                    }else if($ndorder['fightstatus'] == 2){
                        $v['storename'] = '二手房信息发布';
                        $house = pdo_get('wlmerchant_old_house',array('id' => $ndorder['fkid']),array('title','cover_image'));
                    }else if($ndorder['fightstatus'] == 3){
                        $v['storename'] = '租房信息发布';
                        $house = pdo_get('wlmerchant_renting',array('id' => $ndorder['fkid']),array('title','cover_image'));
                    }
                    $v['goodsname'] = $house['title'];
                    $v['goodsimg'] = tomedia($house['cover_image']);
                    $v['typeid'] = 20;
                    $v['num'] = 1;
                }
                else if ($ndorder['plugin'] == 'hotel') {
                    $room = pdo_get('wlmerchant_hotel_room',array('id' => $ndorder['fkid']),array('name','thumb'));
                    $v['goodsname'] = $room['name'];
                    $v['goodsimg'] = tomedia($room['thumb']);
                    $v['typeid'] = 21;
                }
                $v['plugin'] = $ndorder['plugin'];
                if ($ndorder['goodsprice'] < 0.01) {
                    $ndorder['goodsprice'] = $v['price'];
                }
                if (empty($v['num'])) {
                    $v['num'] = 1;
                }
                $v['goodsprice'] = sprintf("%.2f" , $ndorder['goodsprice'] / $v['num']);
            }
            if ($v['a'] == 'b') {
                $v['activityid'] = pdo_getcolumn(PDO_NAME . 'rush_order' , [
                    'id'      => $v['id'] ,
                    'uniacid' => $_W['uniacid']
                ] , 'activityid');
                $goods           = Rush::getSingleActive($v['activityid'] , 'name,isdistri,thumb,id,cutofftime,viponedismoney,viptwodismoney,vipthreedismoney,dissettime,onedismoney,twodismoney,threedismoney,allowapplyre,pftid');
                if ($v['status'] == 0 || $v['status'] == 5) {  //校验支付
                    $orderno = pdo_getcolumn(PDO_NAME . 'rush_order' , [
                        'uniacid' => $_W['uniacid'] ,
                        'id'      => $v['id']
                    ] , 'orderno');
                    $paylog  = pdo_get('core_paylogvfour' , [
                        'uniacid' => $_W['uniacid'] ,
                        'tid'     => $orderno
                    ] , ['status' , 'uniontid' , 'type']);
                    if ($paylog['status'] == 1) {
                        $paytype         = [
                            'credit'   => 1 ,
                            'wechat'   => 2 ,
                            'alipay'   => 3 ,
                            'delivery' => 4 ,
                            'wxapp'    => 5
                        ];
                        $data['paytype'] = $paytype[$paylog['type']];
                        if ($v['expressid']) {
                            $data['status'] = 8;
                        }
                        else {
                            $data['status'] = 1;
                        }
                        $data['transid'] = $paylog['uniontid'];
                        pdo_update(PDO_NAME . 'rush_order' , $data , ['id' => $v['id']]); //更新订单状态
                        $v = pdo_get('wlmerchant_rush_order' , ['id' => $v['id']]);
                    }
                }
                $actualprice     = pdo_getcolumn(PDO_NAME . 'rush_order' , [
                    'id'      => $v['id'] ,
                    'uniacid' => $_W['uniacid']
                ] , 'actualprice');
                $v['goodsname']  = $goods['name'];
                $v['goodsimg']   = tomedia($goods['thumb']);
                $v['plugin']     = 'rush';
                $v['goodsprice'] = sprintf("%.2f" , $v['price'] / $v['num']);
                $v['price']      = $actualprice;
                $v['typeid']     = 1;
                if($goods['pftid'] > 0){
                    $v['storename'] = '平台商品';
                }
            }
            if (!$v['storename']) {
                $v['storename'] = pdo_getcolumn(PDO_NAME . 'merchantdata' , [
                    'id'      => $v['sid'] ,
                    'uniacid' => $_W['uniacid']
                ] , 'storename');
                if (!$v['storename']) {
                    $v['storename'] = '掌上信息';
                }
            }
            if(empty($v['shop_img'])){
                $v['shop_img'] = tomedia($_W['wlsetting']['base']['logo']);
            }
            if (($v['status'] == 8 || $v['status'] == 1) && empty($goods['allowapplyre']) && $v['plugin'] != 'citydelivery' && $v['plugin'] != 'consumption' && $v['paytype'] != 6 && $v['statusName'] != '组团中' && empty($v['pftorderinfo'])) {
                if ($v['status'] == 1) {
                    $canre = pdo_getcolumn(PDO_NAME . 'smallorder' , [
                        'orderid' => $v['id'] ,
                        'plugin'  => $v['plugin'] ,
                        'status'  => 1
                    ] , 'id');
                }
                else {
                    $canre = pdo_getcolumn(PDO_NAME . 'aftersale' , [
                        'orderid' => $v['id'] ,
                        'plugin'  => $v['plugin'] ,
                        'status'  => [1 , 2]
                    ] , 'id');
                    $canre = !$canre;
                }
                if ($canre) {
                    $v['surerefund'] = 1;
                }
                else {
                    $v['status']     = 10;
                    $v['surerefund'] = 0;
                }
            }
            else {
                $v['surerefund'] = 0;
            }
            $v['createtime'] = date('Y-m-d H:i' , $v['createtime']);
            //获取商户信息
            if ($v['sid'] > 0) {
                $v['shop_img'] = tomedia(pdo_getcolumn(PDO_NAME . "merchantdata" , ['id' => $v['sid']] , 'logo'));
            }
            if (empty($v['groupstatus'])) {
                $v['groupstatus'] = 0;
            }
        }
        $data['myorder'] = is_array($myorder) ? array_values($myorder) : [];
        $this->renderSuccess('用户订单' , $data);
    }
    /**
     * Comment: 检测卡券订单是否已被使用
     * Date: 2019/8/30 16:29
     * @param $coupon
     * @param $ndorder
     * @return bool
     */
    private function checkcoupon($coupon , $ndorder)
    {
        global $_W , $_GPC;
        if (empty($coupon) || empty($ndorder)) return false;
        if (($coupon['usetimes'] < 1 || $coupon['endtime'] < time()) && $ndorder['status'] == 1) {
            pdo_update(PDO_NAME . 'order' , ['status' => 2] , ['orderno' => $ndorder['orderno']]);
        }
    }
    /**
     * Comment: 用户个人中心信息
     * Author: wlf
     * Date: 2019/8/12 09:09
     */
    public function memberInfo()
    {
        global $_W;
        $data             = [];
        $data['nickname'] = $_W['wlmember']['nickname'] ? : '';  //用户昵称
        $data['bgimg']    = $_W['wlsetting']['userset']['userbg'] ? tomedia($_W['wlsetting']['userset']['userbg']) : URL_MODULE . 'h5/resource/image/userCenterImg.png';//背景图
        $data['avatar']   = tomedia($_W['wlmember']['avatar']);  //用户头像
        if ($_W['wlmember']['mobile'] > 0) {
            $data['truemobile'] = $_W['wlmember']['mobile'];
            $data['mobile'] = substr($_W['wlmember']['mobile'] , 0 , 3) . '****' . substr($_W['wlmember']['mobile'] , -4 , 4); //用户手机号
        }else if(Customized::init('integral074') > 0  && empty($_W['wlsetting']['wxappset']['examineing']) && !empty($_W['mid']) ){
            $this->renderError('请先绑定手机号');
        }
        $data['credit1']    = sprintf("%.2f" , $_W['wlmember']['credit1']);  //用户积分
        $data['credit2']    = sprintf("%.2f" , $_W['wlmember']['credit2']);  //用户余额
        $collectnum         = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'storefans') . " WHERE uniacid={$_W['uniacid']} and mid={$_W['mid']}");
        $pocketcollectnum   = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'pocket_collection') . " WHERE uniacid={$_W['uniacid']} and mid={$_W['mid']}");
        $citycardcollectnum = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'citycard_collect') . " WHERE mid={$_W['mid']}");
        $data['collectnum'] = $collectnum + $pocketcollectnum + $citycardcollectnum ? $collectnum + $pocketcollectnum + $citycardcollectnum : 0; //收藏店铺数量
        //统计用户的未读信息数量
        $data['news_total'] = pdo_getcolumn(PDO_NAME . "im" , [
            'receive_type' => 1 ,
            'receive_id'   => $_W['mid'] ,
            'is_read'      => 0
        ] , 'count(*)');
        $num_1              = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'order') . " WHERE uniacid={$_W['uniacid']} and mid={$_W['mid']} and status = 0  and plugin <> 'distribution' AND plugin != 'housekeep'");
        $num_2              = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'order') . " WHERE uniacid={$_W['uniacid']} and mid={$_W['mid']} and status = 1 and plugin <> 'distribution' AND plugin != 'housekeep'");
        $num_3              = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'order') . " WHERE uniacid={$_W['uniacid']} and mid={$_W['mid']} and status = 2 and plugin <> 'distribution' AND plugin != 'housekeep'");
        $num_4              = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'order') . " WHERE uniacid={$_W['uniacid']} and mid={$_W['mid']} and status = 4 and plugin <> 'distribution' AND plugin != 'housekeep'");
        $rnum_1             = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_order') . " WHERE uniacid={$_W['uniacid']} and mid={$_W['mid']} and status = 0 ");
        $rnum_2             = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_order') . " WHERE uniacid={$_W['uniacid']} and mid={$_W['mid']} and status = 1 ");
        $rnum_3             = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_order') . " WHERE uniacid={$_W['uniacid']} and mid={$_W['mid']} and status = 2 ");
        $rnum_4             = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'rush_order') . " WHERE uniacid={$_W['uniacid']} and mid={$_W['mid']} and status = 4 ");
        $num1               = $num_1 + $rnum_1;
        $num2               = $num_2 + $rnum_2;
        $num3               = $num_3 + $rnum_3;
        $num4               = $num_4 + $rnum_4;
        $data['ordernum1']  = $num1; //待付款
        $data['ordernum2']  = $num2; //待使用
        $data['ordernum3']  = $num3; //待评价
        $data['ordernum4']  = $num4; //待退款
        //判断商户
        $storever   = pdo_get('wlmerchant_merchantuser' , ['mid' => $_W['mid'] , 'uniacid' => $_W['uniacid']] , ['id']);
        $storemain  = pdo_get('wlmerchant_merchantuser' , [
            'mid'     => $_W['mid'] ,
            'uniacid' => $_W['uniacid'] ,
            'ismain'  => [1 , 3 , 4]
        ] , ['id']);
        $storeadmin = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_merchantuser') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} AND ismain IN (1,3)");
        if (!empty($storeadmin)) {
            $data['storeTextStatus'] = 1;
        }
        //判断会员
        $data['halfcardflag'] = WeliamWeChat::VipVerification($_W['mid'] , true); //会员状态
        //节省
        $swhere['mid']         = $_W['mid'];
        $swhere['uniacid']     = $_W['uniacid'];
        $allorderprice         = pdo_getcolumn('wlmerchant_timecardrecord' , $swhere , ["SUM(ordermoney)"]);
        $realprice             = pdo_getcolumn('wlmerchant_timecardrecord' , $swhere , ["SUM(realmoney)"]);
        $ellipsismoney         = $allorderprice - $realprice;
        $data['ellipsismoney'] = sprintf("%.2f" , $ellipsismoney);  //节约金额
        //积分签到
        $data['signstatus'] = $_W['wlsetting']['wlsign']['signstatus'];
        //判断分销
        $distributionbase = Setting::wlsetting_read('distribution');
        if ($distributionbase['switch'] && p('distribution')) {
            if ($_W['wlmember']['distributorid']) {
                $distributor = pdo_get('wlmerchant_distributor' , ['id' => $_W['wlmember']['distributorid']]);
                if ($distributor['disflag']) {
                    $disflag = 1;
                }
                else {
                    if ($distributionbase['appdis']) {
                        $disflag = 2;
                    }
                    else {
                        $disflag = 0;
                    }
                }
            }
            else {
                if ($distributionbase['appdis']) {
                    $disflag = 2;
                }
                else {
                    $disflag = 0;
                }
            }
        }
        else {
            $disflag = 0;
        }
        //电商联盟定制 分销同步到其他模块
        if (file_exists(PATH_MODULE . 'lsh.log') && !empty($data['halfcardflag'])) {
            if (!empty($_W['wlmember']['openid'])) {
                $myuser = Distribution::initUserInfo($_W['wlmember']);
                if (empty($distributor)) {
                    $distributor = pdo_get('wlmerchant_distributor' , ['mid' => $_W['wlmember']['id']]);
                }
            }
            // 其他模块上级ID不存在，并且我有上级才同步
            if (empty($myuser['pid']) && $distributor['leadid'] > 0) {
                $leaderinfo = pdo_get('wlmerchant_member' , ['id' => $distributor['leadid']]);
                $leader     = Distribution::initUserInfo($leaderinfo);
                pdo_update('hccard_user' , ['pid' => $leader['id']] , ['id' => $myuser['id']]);
            }
        }
        //判断手机号是否显示
        $mustmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($mustmobile) || !empty($_W['wlmember']['mobile'])) {
            $data['mobilediv'] = 0;
        }
        else {
            $data['mobilediv'] = 1;
        }
        //隐藏积分或余额
        $userset = Setting::wlsetting_read('userindexset');
        $data['hideyue'] = $userset['hideyue'] ? : 0;
        $data['hidejifen'] = $userset['hidejifen'] ? : 0;
        //我的工具 开关显示 （1=开启；0=关闭）
        $data['disflag'] = $disflag; //分销商状态
        $pocketset       = Setting::agentsetting_read('pocket');

        $userSet                    = User::userSet();
        $userSet['ykthy']['switch'] = $_W['wlsetting']['halfcard']['status'] == 1 ? $userSet['ykthy']['switch'] == 1 ? 1 : 0 : 0;//一卡通会员（ykthy）
        $userSet['xfjl']['switch']  = $_W['wlsetting']['halfcard']['status'] == 1 ? $userSet['xfjl']['switch'] == 1 ? 1 : 0 : 0;//消费记录（xfjl）
        $userSet['wdkj']['switch']  = p('bargain') ? $userSet['wdkj']['switch'] == 1 ? 1 : 0 : 0;//我的砍价（wdkj）
        $userSet['shzx']['switch']  = $userSet['shzx']['switch'] == 1 ? 1 : 0 ;//商户中心（shzx）
        $userSet['hxjl']['switch']  = !empty($storever) ? $userSet['hxjl']['switch'] == 1 ? 1 : 0 : 0;//核销记录（hxjl）
        $userSet['jfsc']['switch']  = $_W['wlsetting']['consumption']['status'] == 1 ? $userSet['jfsc']['switch'] == 1 ? 1 : 0 : 0;//积分商城（jfsc）
        $userSet['wdtz']['switch']  = $pocketset['status'] == 1 ? $userSet['wdtz']['switch'] == 1 ? 1 : 0 : 0;//我的帖子（wdtz）
        $userSet['fxzx']['switch']  = !empty($disflag) ? $userSet['fxzx']['switch'] == 1 ? 1 : 0 : 0;//分销中心（fxzx）
        $userSet['rzzx']['switch']  = $_W['wlsetting']['attestation']['switch'] == 1 ? $userSet['rzzx']['switch'] == 1 ? 1 : 0 : 0;//认证中心（rzzx）
        $userSet['kefu']['switch']  = $_W['wlsetting']['customer']['status'] == 1 ? $userSet['kefu']['switch'] == 1 ? 1 : 0 : 0;//客服（kefu）
        $userSet['wdhb']['switch']  = p('redpack') ? $userSet['wdhb']['switch'] == 1 ? 1 : 0 : 0;//我的红包（kefu）
        $userSet['hbgc']['switch']  = p('redpack') ? $userSet['hbgc']['switch'] == 1 ? 1 : 0 : 0;//红包广场（kefu）
        //文本替换
        $trade                     = $_W['wlsetting']['trade'];
        $halfcardtext              = $trade['halfcardtext'] ? : '一卡通';
        $userSet['ykthy']['title'] = str_replace('一卡通' , $halfcardtext , $userSet['ykthy']['title']);
        $fxtext                    = $trade['fxtext'] ? : '分销';
        $userSet['fxzx']['title']  = str_replace('分销' , $fxtext , $userSet['fxzx']['title']);
        //循环处理信息
        foreach ($userSet as $key => &$val) {
            //判断是否开启
            if ($val['switch'] != 1) {
                unset($userSet[$key]);
                continue;
            }
            //处理图片路径
            $val['icon'] = tomedia($val['icon']);
            //mid参数替换
            $paramsMid   = "mid={$_W['mid']}&";
            $val['link'] = str_replace('mid=&' , $paramsMid , $val['link']);
            //自定义名称
            $val['title'] = $val['diy_title'] ? : $val['title'];
            //客服按钮
            if($key == 'kefu' && $val['link'] == $val['default']){
                if($_W['aid']>0){
                    $set = Setting::agentsetting_read('agentcustomer');
                }else{
                    $set = Setting::wlsetting_read("customer");
                }
                if($set['wxapptype'] > 0){
                    $val['kefuflag'] = $set['wxapptype'];
                    $val['customerurl'] = $set['customerurl'];
                    $val['enterpriseid'] = $set['enterpriseid'];
                }
            }
            //商户中心
            if($key == 'shzx' && $val['link'] == $val['default'] && empty($storemain)){
                $storeSet = Setting::wlsetting_read('agentsStoreSet');
                if($storeSet['storeSetted'] > 0){
                    unset($userSet[$key]);
                }else{
                    $val['title'] = $val['diy_title'] ? : '商户入驻';
                    $val['link'] = 'pages/mainPages/Settled/Settled';
                }
            }
        }
        $data['user_set'] = $userSet ? array_values($userSet) : [];
        $data['halfcardstatus'] = $_W['wlsetting']['halfcard']['status'];
        $data['mid']            = $_W['mid'] ? : '';
        //定制
        $authinfo = Cloud::wl_syssetting_read('authinfo');
        if ($authinfo['code'] == '116495A4C76AB8B1BBC387C941FE7424' || $authinfo['code'] == 'E9FEC02FCF843080D04DF170CBA8EF7A') {
            $data['changeflag'] = 1;
        }
        //881定制
        $isAuth = Customized::init('diy_userInfo');
        if($isAuth){
            $data['diy_userInfo']['dhurl'] = $_W['wlsetting']['recharge']['dhurl'];
        }

        $this->renderSuccess('用户信息' , $data);
    }
    /**
     * Comment: 用户数据
     * Author: wlf
     * Date: 2019/8/12 09:50
     */
    public function userData()
    {
        global $_W;
        $data             = [];
        $data['nickname'] = $_W['wlmember']['nickname'];
        $data['realname'] = $_W['wlmember']['realname'];
        $data['mobile']   = $_W['wlmember']['mobile'];
        if ($_W['wlsetting']['cashset']['payment_type']['bank_card'] == 1) {
            $data['bankstatus'] = 1;
        }
        else {
            $data['bankstatus'] = 0;
        }
        $data['bank_name']     = $_W['wlmember']['bank_name'];
        $data['bank_username'] = $_W['wlmember']['bank_username'];
        $data['card_number']   = $_W['wlmember']['card_number'];
        if ($_W['wlsetting']['cashset']['payment_type']['alipay'] == 1) {
            $data['alipaystatus'] = 1;
        }
        else {
            $data['alipaystatus'] = 0;
        }
        $data['alipay'] = $_W['wlmember']['alipay'];
        $this->renderSuccess('用户数据' , $data);
    }
    /**
     * Comment: 修改用户数据
     * Author: wlf
     * Date: 2019/8/12 10:17
     */
    public function changeUserData()
    {
        global $_W , $_GPC;
        $data             = [];
        $data['nickname'] = $_GPC['nickname'];
        $data['realname'] = $_GPC['realname'];
        if ($_GPC['bank_name']) {
            $data['bank_name'] = $_GPC['bank_name'];
        }
        if ($_GPC['bank_username']) {
            $data['bank_username'] = $_GPC['bank_username'];
        }
        if ($_GPC['card_number']) {
            $data['card_number'] = $_GPC['card_number'];
        }
        if ($_GPC['alipay']) {
            $data['alipay'] = $_GPC['alipay'];
        }
        $res = pdo_update('wlmerchant_member' , $data , ['id' => $_W['mid']]);
        if ($res) {
            $this->renderSuccess('修改成功');
        }
        else {
            $this->renderError('修改失败，请刷新重试');
        }
    }
    /**
     * Comment: 用户地址列表
     * Author: wlf
     * Date: 2019/8/12 11:07
     */
    public function addressList()
    {
        global $_W;
        $data = pdo_getall('wlmerchant_address' , ['mid' => $_W['mid'] , 'uniacid' => $_W['uniacid']] , [
            'id' ,
            'name' ,
            'status' ,
            'tel' ,
            'province' ,
            'city' ,
            'county' ,
            'detailed_address'
        ]);
        $this->renderSuccess('用户地址列表' , is_array($data) ? $data : []);
    }
    /**
     * Comment: 创建或编辑地址
     * Author: wlf
     * Date: 2019/8/12 11:22
     */
    public function addAddress()
    {
        global $_W , $_GPC;
        $id   = $_GPC['id'];
        $data = [];
        if ($id) {
            $address                  = pdo_get('wlmerchant_address' , ['id' => $id] , ['name','status','lng','lat','tel','province','city','county','detailed_address','housenumber']);
            $data['name']             = $address['name'];
            $data['status']           = $address['status'];
            $data['tel']              = $address['tel'];
            $data['lng']              = $address['lng'];
            $data['lat']              = $address['lat'];
            $provinceid               = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_area') . "WHERE `name` like '%{$address['province']}%' ");
            $cityid                   = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_area') . "WHERE `name` like '%{$address['city']}%' ");
            $countyid                 = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_area') . "WHERE `name` like '%{$address['county']}%' ");
            $data['provinceid']       = $provinceid['id'];
            $data['cityid']           = $cityid['id'];
            $data['countyid']         = $countyid['id'];
            $data['detailed_address'] = $address['detailed_address'];
            $data['houseNumber']      = $address['housenumber'];
        }
        //切割门牌号
        if(!empty($data['houseNumber'])){
            $data['detailed_address'] = strstr($data['detailed_address'],$data['houseNumber'],true);
        }
        $this->renderSuccess('地址数据' , $data);
    }
    /**
     * Comment: 保存地址数据
     * Author: wlf
     * Date: 2019/8/12 15:59
     */
    public function saveAddress()
    {
        global $_W , $_GPC;
        $id             = $_GPC['id'];
        $data           = [];
        $data['name']   = $_GPC['name'];
        $data['status'] = $_GPC['status'];
        $data['lat']    = $_GPC['lat'];
        $data['lng']    = $_GPC['lng'];
        if (empty($data['lat']) || empty($data['lng'])) {
            $data['lat'] = 0;
            $data['lng'] = 0;
        }
        if ($data['status']) {
            pdo_update('wlmerchant_address' , ['status' => 0] , ['uniacid' => $_W['uniacid'] , 'mid' => $_W['mid']]);
        }
        $data['tel']              = $_GPC['tel'];

        if(!preg_match("/^1[345789]\d{9}$/",$data['tel'])){
            $this->renderError('手机号格式有误，请检查');
        }
        $data['detailed_address'] = $_GPC['detailed_address'].$_GPC['houseNumber'];
        $data['housenumber']      = $_GPC['houseNumber'];
        $provinceid               = $_GPC['provinceid'];
        $data['province']         = $_GPC['provinceidName'] ? : pdo_getcolumn(PDO_NAME . 'area' , ['id' => $provinceid] , 'name');
        $cityid                   = $_GPC['cityid'];
        $data['city']             = $_GPC['areaidName'] ? : pdo_getcolumn(PDO_NAME . 'area' , ['id' => $cityid] , 'name');
        $countyid                 = $_GPC['countyid'] ? $_GPC['countyid'] : $_GPC['cityid'];
        $data['county']           = $_GPC['distidName'] ? : pdo_getcolumn(PDO_NAME . 'area' , ['id' => $countyid] , 'name');
        $data['addtime']          = time();
        if ($id) {
            $res = pdo_update('wlmerchant_address' , $data , ['id' => $id]);
        }
        else {
            $data['uniacid'] = $_W['uniacid'];
            $data['aid']     = $_W['aid'];
            $data['mid']     = $_W['mid'];
            $res             = pdo_insert(PDO_NAME . 'address' , $data);
        }
        if ($res) {
            $this->renderSuccess('修改成功');
        }
        else {
            $this->renderError('修改失败，请刷新重试');
        }
    }
    /**
     * Comment: 修改默认地址
     * Author: wlf
     * Date: 2019/8/12 16:21
     */
    public function changeAddressStatus()
    {
        global $_W , $_GPC;
        $id = $_GPC['id'];
        if (empty($id)) {
            $this->renderError('缺少地址参数');
        }
        pdo_update('wlmerchant_address' , ['status' => 0] , ['uniacid' => $_W['uniacid'] , 'mid' => $_W['mid']]);
        $res = pdo_update('wlmerchant_address' , ['status' => 1] , ['id' => $id]);
        if ($res) {
            $this->renderSuccess('修改成功');
        }
        else {
            $this->renderError('修改失败，请刷新重试');
        }
    }
    /**
     * Comment: 删除地址
     * Author: wlf
     * Date: 2019/8/12 17:40
     */
    public function deleteAddress()
    {
        global $_W , $_GPC;
        $id = $_GPC['id'];
        if (empty($id)) {
            $this->renderError('缺少地址参数');
        }
        $res = pdo_delete('wlmerchant_address' , ['id' => $id]);
        if ($res) {
            $this->renderSuccess('删除成功');
        }
        else {
            $this->renderError('删除失败，请刷新重试');
        }
    }
    /**
     * Comment: 改绑手机
     * Author: wlf
     * Date: 2019/8/12 16:40
     */
    public function changeMobile()
    {
        global $_W , $_GPC;
        $mobile    = $_GPC['mobile'];
        $mergeflag = $_GPC['mergeflag'];
        $code      = $_GPC['code'];
        if (empty($mobile)) {
            $this->renderError('手机号码错误');
        }
        if (!empty($code)) {
            $pin_info = pdo_get('wlmerchant_pincode' , ['mobile' => $mobile]);
            if (empty($pin_info)) {
                $this->renderError('验证码错误' , ['code' => 1]);
            }
            if ($pin_info['time'] < time() - 300) {
                $this->renderError('验证码已过期，请重新获取' , ['code' => 1]);
            }
            if ($code != $pin_info['code']) $this->renderError('验证码错误' , ['code' => 1]);
        }
        //判断是否绑定
        $flag = Member::wl_member_get(['mobile' => $mobile] , ['id']);
        if ($flag && empty($mergeflag)) {
            if($_W['wlsetting']['userset']['onemobiletype'] == 1){
                $this->renderError('手机号已存在,绑定该手机号会取消之前用户的绑定手机' , ['mergeflag' => 1]);
            }else if($_W['wlsetting']['userset']['onemobiletype'] == 2){
                $this->renderError('手机号已存在,无法重复绑定');
            }else{
                $this->renderError('手机号已存在,绑定该手机号会自动同步用户数据' , ['mergeflag' => 1]);
            }
        }
        //解绑之前手机
        if($flag && !empty($mergeflag) && $_W['wlsetting']['userset']['onemobiletype'] == 1){
            pdo_update('wlmerchant_member',array('mobile' => ''),array('mobile' => $mobile));
        }
        //绑定用户手机号
        $bind = Member::wl_member_update(['mobile' => $mobile] , $_W['mid']);
        if (is_error($bind)) {
            $this->renderError($bind['message']);
        }
        //合并重复手机号
        if ($flag && !empty($mergeflag) && empty($_W['wlsetting']['userset']['onemobiletype'])) {
            $res = Member::wl_member_merge($mobile , 'mobile');
            if (is_error($res)) {
                $this->renderError($res['message']);
            }
            $this->renderSuccess('合并数据成功，请重新登录' , ['token' => $res['tokey']]);
        }
        $this->renderSuccess('修改成功' , ['token' => $bind['tokey']]);
    }
    /**
     * Comment: 积分余额变更记录
     * Author: wlf
     * Date: 2019/8/28 14:42
     */
    public function creditRecord()
    {
        global $_W , $_GPC;
        $page       = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_start = $page * 50 - 50;
        $type       = $_GPC['type'] ? $_GPC['type'] : 1;
        $type       = $type == 1 ? 'credit1' : 'credit2';
        $uid        = $_W['wlmember']['uid'];
        $records    = pdo_fetchall("SELECT id,num,createtime,remark FROM " . tablename("mc_credits_record") . " WHERE uid = {$uid} AND uniacid = {$_W['uniacid']} AND credittype = '{$type}' ORDER BY createtime DESC LIMIT {$page_start},50");
        $allnum     = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('mc_credits_record') . " WHERE uid = {$uid} AND uniacid = {$_W['uniacid']} AND credittype = '{$type}'");
        if ($records) {
            foreach ($records as $key => &$va) {
                $va['createtime'] = date('Y-m-d H:i:s' , $va['createtime']);
            }
            $records = array_values($records);
        }
        $data['list']      = $records;
        $data['pagetotal'] = ceil($allnum / 50);
        $data['mycredit']  = sprintf("%.2f" , $_W['wlmember']['credit1']);
        $data['flag074'] = Customized::init('integral074') > 0 ? 1 : 0;
        //开关功能
        $wlsign = Setting::wlsetting_read('wlsign');
        $consumption = Setting::wlsetting_read('consumption');
        $data['set'] = [
            'wlsign' => $wlsign['signstatus'] ? : 0,
            'consumption' => $consumption['status'] ? : 0,
        ];
        //文字替换  积分 换成自定义文字
        $textData   = Setting::wlsetting_read('trade');
        $credittext = $textData['credittext'] ? $textData['credittext'] : '积分';
        $jsonData   = json_encode($data , JSON_UNESCAPED_UNICODE);
        $jsonData   = str_replace('积分' , $credittext , $jsonData);
        $data       = json_decode($jsonData , true);
        $this->renderSuccess('积分余额记录' , $data);
    }
    /**
     * Comment: 收藏店铺列表
     * Author: wlf
     * Date: 2019/8/12 16:20
     */
    public function storeCollect()
    {
        global $_W , $_GPC;
        $page       = $_GPC['page'] ? $_GPC['page'] : 1;  //显示的页数
        $lat        = $_GPC['lat'];
        $lng        = $_GPC['lng'];
        $pagestatrt = $page * 10 - 10;
        $collects = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_storefans') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} ORDER BY createtime DESC LIMIT {$pagestatrt},10");
        $allnum   = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_storefans') . " WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']}");
        if ($collects) {
            foreach ($collects as $key => $value) {
                $merchant = pdo_get('wlmerchant_merchantdata' , [
                    'uniacid' => $_W['uniacid'] ,
                    'id'      => $value['sid']
                ] , ['mobile' , 'id' , 'storename' , 'location' , 'logo' , 'storehours' , 'score' , 'tag']);
                if ($merchant) {
                    //计算距离
                    if (!empty($lat) && !empty($lng)) {
                        $location             = unserialize($merchant['location']);
                        $merchant['distance'] = Store::getdistance($location['lng'] , $location['lat'] , $lng , $lat);
                        if ($merchant['distance'] > 1000) {
                            $merchant['distance'] = (floor(($merchant['distance'] / 1000) * 10) / 10) . "km";
                        }
                        else {
                            $merchant['distance'] = round($merchant['distance']) . "m";
                        }
                    }
                    $merchant['logo'] = tomedia($merchant['logo']);
                    $merchant['tags'] = [];
                    $tagids           = unserialize($merchant['tag']);
                    if (!empty($tagids)) {
                        $tags             = pdo_getall('wlmerchant_tags' , ['id' => $tagids] , ['title']);
                        $merchant['tags'] = $tags ? array_column($tags , 'title') : [];
                    }
                    unset($merchant['tag']);
                    //营业时间
                    $storehours            = unserialize($merchant['storehours']);
                    if(!empty($storehours['startTime'])){
                        $merchant['storehours'] = $storehours['startTime'] . ' - ' . $storehours['endTime']. "  营业";
                    }else{
                        $merchant['storehours'] = '';
                        foreach($storehours as $hk => $hour){
                            if($hk > 0){
                                $merchant['storehours'] .= ','.$hour['startTime'] . ' - ' . $hour['endTime'];
                            }else{
                                $merchant['storehours'] .= $hour['startTime'] . ' - ' . $hour['endTime'];
                            }
                        }
                        $merchant['storehours'] .= "  营业";
                    }
                    $merchant['hourstatus'] = Store::getShopBusinessStatus($storehours,$merchant['id']);
                    //查询认证和保证金
                    if (p('attestation')) {
                        $merchant['attestation'] = Attestation::checkAttestation(2 , $merchant['id']);
                    }
                    else {
                        $merchant['attestation'] = 0;
                    }
                    $colls[$key]['store'] = $merchant;
                }
                else {
                    pdo_delete('wlmerchant_storefans' , ['sid' => $value['sid']]);
                }
            }
            $data['list']      = $colls;
            $data['pagetotal'] = ceil($allnum / 10);
            $this->renderSuccess('店铺列表' , $data);
        }
        else {
            $data['list']      = [];
            $data['pagetotal'] = 1;
            $this->renderSuccess('无数据' , $data);
        }
    }
    /**
     * Comment: 我的卡券列表
     * Author: wlf
     * Date: 2019/8/12 16:32
     */
    public function memberCoupon()
    {
        global $_W , $_GPC;
        if(Customized::init('transfergift') > 0){
            $data['transfer'] = 1;
            $trwhere = " WHERE uniacid = {$_W['uniacid']} AND type = 1 AND omid = {$_W['mid']} AND status = 1";
            $alllist = pdo_fetchall("select distinct objid from " . tablename(PDO_NAME.'transferRecord').$trwhere);
            $alltrnum = count($alllist);
            $data['alltrnum'] = $alltrnum;
        }else{
            $data['transfer'] = 0;
        }
        $page         = $_GPC['page'] ? $_GPC['page'] : 1;
        $status       = $_GPC['status'] ? trim($_GPC['status']) : 1;
        $where['mid'] = $_W['mid'];
        if ($status == 1) {
            $where['usetimes>'] = 1;
            $where['endtime>']  = time();
        }
        else if ($status == 2) {
            $where['usetimes'] = 0;
        }
        else {
            $where['usetimes>'] = 1;
            $where['endtime<']  = time();
        }
        $couponlist = wlCoupon::getNumCoupon('id,title,sub_title,endtime,starttime,parentid,orderno,status,transferflag' , $where , 'ID DESC' , $page , 20 , 1);
        $allnum     = $couponlist[2];
        $couponlist = $couponlist[0];
        if ($couponlist) {
            foreach ($couponlist as $key => &$v) {
                $parent         = pdo_get('wlmerchant_couponlist' , ['id' => $v['parentid']] , [
                    'logo' ,
                    'merchantid' ,
                    'title' ,
                    'sub_title',
                    'transferstatus',
                    'transfermore',
                    'transferlevel'
                ]);
                $store          = pdo_get('wlmerchant_merchantdata' , ['id' => $parent['merchantid']] , [
                    'storename' ,
                    'logo'
                ]);
                $v['title']     = $parent['title'];
                $v['sub_title'] = $parent['sub_title'];
                $v['storename'] = $store['storename'];
                $v['storeid']   = $parent['merchantid'];
                $v['transferflag'] = $v['transferflag'] ? : 0;
                $v['endtime']   = date('Y-m-d' , $v['endtime']);
                $v['starttime'] = date('Y-m-d' , $v['starttime']);
                $v['storelogo'] = tomedia($store['logo']);
                $v['orderid']   = pdo_getcolumn(PDO_NAME . 'order' , ['orderno' => $v['orderno']] , 'id');
                if (empty($v['orderid'])) {
                    $v['orderid'] = pdo_getcolumn(PDO_NAME . 'order' , [
                        'recordid' => $v['id'] ,
                        'plugin'   => 'coupon'
                    ] , 'id');
                }
                //判断是否能够转赠
                if($parent['transferstatus'] > 0){
                    if(empty($v['transferflag']) || $parent['transfermore'] > 0){
                        $transferlevel = unserialize($parent['transferlevel']);
                        if(empty($transferlevel)){
                            $v['transferstatus'] = 1;
                        }else{
                            $halfflag = WeliamWeChat::VipVerification($_W['mid']);
                            if(empty($halfflag)){
                                $levelid = -1;
                            }else{
                                $levelid = $halfflag['levelid'];
                            }
                            if(in_array($levelid,$transferlevel)){
                                $v['transferstatus'] = 1;
                            }
                        }
                    }
                }
                $v['transferstatus'] = $v['transferstatus'] ? : 0;
                //转赠卡券信息获取
                if($v['transferflag'] > 0){
                    $record = pdo_getall('wlmerchant_transferRecord',array('type' => 1,'objid' => $v['id'],'nmid' => $_W['mid']),array('omid','gettime'),'','ID DESC');
                    $record = $record[0];
                    $oldmember = pdo_get('wlmerchant_member',array('id' => $record['omid']),array('nickname','mobile'));
                    $v['oldnickname'] = $oldmember['nickname'];
                    $v['oldmobile'] = substr($oldmember['mobile'],0,3).'****'.substr($oldmember['mobile'], -4);
                    $v['gettime'] = date('Y/m/d H:i:s',$record['gettime']);
                }

                //未获取订单id 删除当前卡券信息
                if (empty($v['orderid'])) {
                    unset($couponlist[$key]);
                    continue;
                }
            }
            $data['list']      = array_values($couponlist);
            $data['pagetotal'] = ceil($allnum / 20);
            $data['allnum'] = $allnum;
            $this->renderSuccess('卡券列表' , $data);
        }
        else {
            $data['list']      = [];
            $data['allnum'] = 0;
            $data['pagetotal'] = 0;
            $this->renderSuccess('无数据' , $data);
        }
    }
    /**
     * Comment: 我的砍价列表
     * Author: wlf
     * Date: 2019/8/13 09:28
     */
    public function memberBargain()
    {
        global $_W , $_GPC;
        $where   = " uniacid = {$_W['uniacid']} AND mid = {$_W['mid']}";
        $myorder = pdo_fetchall("SELECT activityid,id,createtime,price,status FROM " . tablename('wlmerchant_bargain_userlist') . "WHERE {$where} ORDER BY createtime DESC");
        if ($myorder) {
            foreach ($myorder as $k => &$v) {
                $goods          = pdo_get('wlmerchant_bargain_activity' , ['id' => $v['activityid']] , [
                    'name' ,
                    'endtime' ,
                    'id' ,
                    'vipprice' ,
                    'vipstatus' ,
                    'price' ,
                    'oldprice' ,
                    'sid' ,
                    'thumb'
                ]);
                $v['goodsname'] = $goods['name'];
                $v['goodsimg']  = tomedia($goods['thumb']);
                $v['oldprice']  = $goods['oldprice'];
                $v['endtime']   = $goods['endtime'];
                if ($goods['vipstatus'] == 1) {
                    $halfflag = WeliamWeChat::VipVerification($_W['mid'] , true);
                    if ($halfflag) {
                        $goods['price'] = $goods['vipprice'];
                    }
                }
                //已砍金额
                $v['alprice'] = sprintf("%.2f" , $v['oldprice'] - $v['price']);
                //还需砍价金额
                $v['needprice'] = sprintf("%.2f" , $v['price'] - $goods['price']);
                //比例
                $canprice        = $goods['oldprice'] - $goods['price'];
                $v['redrate']    = sprintf("%.2f" , ($v['alprice'] / $canprice) * 100);
                $v['successnum'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_bargain_userlist') . " WHERE status = 2 AND activityid = {$v['activityid']} ");
            }
            $this->renderSuccess('砍价列表' , $myorder);
        }
        else {
            $this->renderSuccess('无数据' , []);
        }
    }
    /**
     * Comment: 我的核销列表
     * Author: wlf
     * Date: 2019/8/13 10:44
     */
    public function memberVerifList()
    {
        global $_W , $_GPC;
        $data = [];
        if ($_GPC['getlistflag']) {
            //所有店铺
            $alladmin = pdo_getall(PDO_NAME . 'merchantuser' , [
                'uniacid' => $_W['uniacid'] ,
                'mid'     => $_W['mid']
            ] , ['storeid']);
            if ($alladmin) {
                foreach ($alladmin as $ak => &$av) {
                    $av['storename'] = pdo_getcolumn(PDO_NAME . 'merchantdata' , [
                        'uniacid' => $_W['uniacid'] ,
                        'id'      => $av['storeid']
                    ] , 'storename');
                }
            }
            //所有分类
            $halfcardtext      = !empty($_W['wlsetting']['trade']['halfcardtext']) ? $_W['wlsetting']['trade']['halfcardtext'] : '一卡通';
            $alltype           = [];
            $alltype[]         = ['name' => $halfcardtext , 'type' => 'halfcard1'];
            $alltype[]         = ['name' => '大礼包' , 'type' => 'halfcard2'];
            $alltype[]         = ['name' => '抢购' , 'type' => 'rush'];
            $alltype[]         = ['name' => '卡券' , 'type' => 'coupon'];
            $alltype[]         = ['name' => '拼团' , 'type' => 'wlfightgroup'];
            $alltype[]         = ['name' => '团购' , 'type' => 'groupon'];
            $alltype[]         = ['name' => '砍价' , 'type' => 'bargain'];
            $data['typelist']  = $alltype;
            $data['storelist'] = $alladmin;
        }
        $page    = $_GPC['page'] ? $_GPC['page'] : 1;
        $type    = trim($_GPC['type']);
        $storeid = intval($_GPC['storeid']);
        $time    = trim($_GPC['time']);
        $where   = ['uniacid' => $_W['uniacid'] , 'verifmid' => $_W['mid']];
        if (!empty($type)) {
            $where['plugin'] = $type;
        }
        if (!empty($storeid)) {
            $where['storeid'] = $storeid;
        }
        if (!empty($time)) {
            if ($time == 'today') {
                $starttime = strtotime(date('Y-m-d'));
                $endtime   = $starttime + 86399;
            }
            else if ($time == 'week') {
                $starttime = strtotime("previous monday");
                $endtime   = time();
            }
            else if ($time == 'month') {
                $starttime = mktime(0 , 0 , 0 , date('m') , 1 , date('Y'));
                $endtime   = time();
            }
            else {
                $times     = explode(',' , $time);
                $starttime = $times[0];
                $endtime   = ($starttime > $times[1]) ? $starttime + 86399 : $times[1] + 86399;
            }
            $where['createtime>'] = $starttime;
            $where['createtime<'] = $endtime;
        }
        $data1   = Util::createStandardWhereString($where);
        $allfen  = pdo_fetchcolumn('SELECT SUM(num) FROM ' . tablename(PDO_NAME . "verifrecord") . " WHERE $data1[0] " , $data1[1]);
        $myorder = Util::getNumData('mid,storeid,createtime,verifrcode,verifnickname,orderid,plugin,verifmid,remark,type,num' , PDO_NAME . 'verifrecord' , $where , 'ID DESC' , $page , 20 , 1);
        $allnum  = $myorder[2];
        $myorder = $myorder[0];
        foreach ($myorder as $key => &$value) {
            $member            = Member::wl_member_get($value['mid'] , ['nickname' , 'avatar' , 'mobile']);
            $value['nickname'] = $member['nickname'];
            $value['mobile']   = $member['mobile'];
            $value['avatar']   = tomedia($member['avatar']);
            if (empty($value['verifnickname'])) {
                $verifnickname          = pdo_getcolumn(PDO_NAME . 'merchantuser' , [
                    'uniacid' => $_W['uniacid'] ,
                    'mid'     => $value['verifmid'] ,
                    'storeid' => $value['storeid']
                ] , 'name');
                $value['verifnickname'] = $verifnickname;
            }
            $value['createtime'] = date('Y-m-d H:i:s' , $value['createtime']);
            //处理数据
            switch ($value['plugin']) {
                case 'rush':
                    $order               = pdo_get(PDO_NAME . 'rush_order' , ['id' => $value['orderid']] , [
                        'optionid' ,
                        'activityid'
                    ]);
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'rush_activity' , ['id' => $order['activityid']] , 'thumb');
                    $optionid            = $order['optionid'];
                    $value['pluginname'] = '抢购';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'smallorder' , ['checkcode' => $value['verifrcode'],'plugin' => $value['plugin'],'orderid' => $value['orderid']] , 'orderprice');
                    break;//抢购
                case 'groupon':
                    $order               = pdo_get(PDO_NAME . 'order' , ['id' => $value['orderid']] , [
                        'specid' ,
                        'fkid'
                    ]);
                    $optionid            = $order['specid'];
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'groupon_activity' , ['id' => $order['fkid']] , 'thumb');
                    $value['pluginname'] = '团购';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'smallorder' , ['checkcode' => $value['verifrcode'],'plugin' => $value['plugin'],'orderid' => $value['orderid']] , 'orderprice');
                    break;//团购
                case 'wlfightgroup':
                    $order               = pdo_get(PDO_NAME . 'order' , ['id' => $value['orderid']] , [
                        'specid' ,
                        'fkid'
                    ]);
                    $optionid            = $order['specid'];
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'fightgroup_goods' , ['id' => $order['fkid']] , 'logo');
                    $value['pluginname'] = '拼团';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'smallorder' , ['checkcode' => $value['verifrcode'],'plugin' => $value['plugin'],'orderid' => $value['orderid']] , 'orderprice');
                    break;//拼团
                case 'coupon':
                    $order               = pdo_get(PDO_NAME . 'order' , ['id' => $value['orderid']] , ['fkid']);
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'couponlist' , ['id' => $order['fkid']] , 'logo');
                    $value['pluginname'] = '卡券';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'smallorder' , ['checkcode' => $value['verifrcode'],'plugin' => $value['plugin'],'orderid' => $value['orderid']] , 'orderprice');
                    break;//卡券
                case 'wlcoupon':
                    $order               = pdo_get(PDO_NAME . 'order' , ['id' => $value['orderid']] , ['fkid']);
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'couponlist' , ['id' => $order['fkid']] , 'logo');
                    $value['pluginname'] = '卡券';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'smallorder' , ['checkcode' => $value['verifrcode'],'plugin' => $value['plugin'],'orderid' => $value['orderid']] , 'orderprice');
                    break;//卡券
                case 'bargain':
                    $order               = pdo_get(PDO_NAME . 'order' , ['id' => $value['orderid']] , ['fkid']);
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'bargain_activity' , ['id' => $order['fkid']] , 'thumb');
                    $value['pluginname'] = '砍价';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'smallorder' , ['checkcode' => $value['verifrcode'],'plugin' => $value['plugin'],'orderid' => $value['orderid']] , 'orderprice');
                    break;//砍价
                case 'halfcard1':
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $value['storeid']] , 'logo');
                    $value['pluginname'] = '会员特权';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'timecardrecord' , ['id' => $value['verifrcode']] , 'realmoney');
                    break;//特权
                case 'halfcard2':
                    $value['goodimg']    = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $value['storeid']] , 'logo');
                    $value['pluginname'] = '大礼包';
                    $value['orderprice'] = pdo_getcolumn(PDO_NAME . 'timecardrecord' , ['id' => $value['verifrcode']] , 'realmoney');
                    break;//大礼包
            }
            //查找规格
            if ($value['plugin'] == 'rush') {
                $optionid = pdo_getcolumn(PDO_NAME . 'rush_order' , ['id' => $value['orderid']] , 'optionid');
            }
            else if ($value['plugin'] == 'groupon' || $value['plugin'] == 'wlfightgroup') {
                $optionid = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $value['orderid']] , 'specid');
            }
            if ($optionid) {
                $value['optionname'] = pdo_getcolumn(PDO_NAME . 'goods_option' , ['id' => $optionid] , 'title');
            }
            else {
                $value['optionname'] = '';
            }
            $value['storename'] = pdo_getcolumn(PDO_NAME . 'merchantdata' , [
                'uniacid' => $_W['uniacid'] ,
                'id'      => $value['storeid']
            ] , 'storename');
            $value['goodimg']   = tomedia($value['goodimg']);
            //兼容处理
            if (empty($value['goodimg'])) $value['goodimg'] = $_W['siteroot'] . 'web/resource/images/nopic-107.png';
        }
        $data['list']      = $myorder;
        $data['allnum']    = intval($allnum);
        $data['allfen']    = intval($allfen);
        $data['totalpage'] = ceil($allnum / 20);
        $this->renderSuccess('核销记录' , $data);
    }
    /**
     * Comment: 余额充值页面
     * Author: wlf
     * Date: 2019/8/26 14:18
     */
    public function rechargePage()
    {
        global $_W , $_GPC;
        $data     = [];
        $settings = Setting::wlsetting_read('recharge');
        $cashsets = Setting::wlsetting_read('cashset');
        if (empty($cashsets['withdrawals'])) {
            $data['nowithdrawals'] = 1;
        }
        if ($settings['status']) {
            $count = count($settings['kilometre']);
            for ($i = 0 ; $i < $count ; $i++) {
                $array[$i]['kilometre'] = $settings['kilometre'][$i];
                $array[$i]['kilmoney']  = $settings['kilmoney'][$i];
            }
        }
        else {
            $data['norecharge'] = 1;
        }
        $data['diymoney'] = $settings['diymoney'] ? : 0;
        foreach ($array as $key => $val) {
            $dos[$key] = $val['kilometre'];
        }
        array_multisort($dos , SORT_ASC , $array);
        $data['activity'] = $array;
        $data['credit']   = sprintf("%.2f" , $_W['wlmember']['credit2']);
        //N561定制
        if (file_exists(PATH_MODULE . 'N561.log')) {
            $data['transfer'] = 1;
        }
        $this->renderSuccess('充值页面数据' , $data);
    }
    /**
     * Comment: 余额充值订单生成
     * Author: wlf
     * Date: 2019/8/26 14:31
     */
    public function rechargeOrder()
    {
        global $_W , $_GPC;
        $data     = [];
        $money    = sprintf("%.2f" , trim($_GPC['money']));
        $settings = Setting::wlsetting_read('recharge');
        if (!$settings['status']) {
            $this->renderError('余额充值未开启');
        }
        if ($money < 0.01) {
            $this->renderError('充值金额错误');
        }
        $member     = pdo_get('wlmerchant_member' , ['id' => $_W['mid']] , ['uid']);
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array('recharge' , $mastmobile)) {
            $this->renderError('未绑定手机号，无法支付');
        }
        //创建订单
        $data = [
            'uniacid'      => $_W['uniacid'] ,
            'mid'          => $_W['mid'] ,
            'aid'          => $_W['aid'] ,
            'fkid'         => 0 ,
            'sid'          => 0 ,
            'status'       => 0 ,
            'paytype'      => 0 ,
            'createtime'   => time() ,
            'orderno'      => createUniontid() ,
            'price'        => $money ,
            'goodsprice'   => $money ,
            'num'          => 1 ,
            'plugin'       => 'member' ,
            'payfor'       => 'recharge' ,
            'issettlement' => 3
        ];
        pdo_insert(PDO_NAME . 'order' , $data);
        $orderid = pdo_insertid();
        if ($orderid) {
            $data['orderid'] = $orderid;
            $this->renderSuccess('生成订单成功' , $data);
        }
        else {
            $this->renderError('订单创建失败，请重试');
        }
    }
    /**
     * Comment: 余额提现页面
     * Author: wlf
     * Date: 2019/8/28 15:05
     */
    public function creditCashPage()
    {
        global $_W , $_GPC;
        $cashsets = Setting::wlsetting_read('cashset');
        if (empty($cashsets['withdrawals'])) {
            $this->renderError('余额提现已关闭');
        }
        $data             = [];
        $memberInfo       = pdo_get('wlmerchant_member' , ['id' => $_W['mid']]);
        $data['nowmoney'] = pdo_getcolumn('mc_members' , [
            'uniacid' => $_W['uniacid'] ,
            'uid'     => $memberInfo['uid']
        ] , 'credit2');
        //可提现金额 - 支付返现金额 = 实际可提现金额
        $data['nowmoney'] = sprintf("%.2f" , $data['nowmoney'] - $memberInfo['cash_back_money']);
        if (!$data['nowmoney']) {
            $data['nowmoney'] = 0.00;
        }
        $data['alcredit'] = pdo_getcolumn('wlmerchant_settlement_record' , [
            'mid'    => $memberInfo['id'] ,
            'type'   => 4 ,
            'status' => 5
        ] , ["SUM(sapplymoney)"]);
        //提现方式
        $payment_type = $cashsets['payment_type'];
        if ($payment_type['alipay']) {
            $data['alipaystatus'] = 1;
            $data['alipay']       = $memberInfo['alipay'];
            $data['realname']     = $memberInfo['realname'];
        }
        else {
            $data['alipaystatus'] = 0;
        }
        if ($payment_type['we_chat']) {
            $data['wechatstatus'] = 1;
        }
        else {
            $data['wechatstatus'] = 0;
        }
        if ($payment_type['bank_card']) {
            $data['bankcardstatus'] = 1;
            $data['bank_name']      = $memberInfo['bank_name'];
            $data['card_number']    = $memberInfo['card_number'];
            $data['bank_username']  = $memberInfo['bank_username'];
        }
        else {
            $data['bankcardstatus'] = 0;
        }
        $data['syssalepercent'] = sprintf("%.2f" , $cashsets['memberpercent']);
        $data['lowsetmoney']    = $cashsets['lowsetmoney'] ? : 0;
        $data['maxsetmoney']    = $cashsets['maxsetmoney'] ? : 0;
        $this->renderSuccess('余额提现页面' , $data);
    }
    /**
     * Comment: 余额提现申请
     * Author: wlf
     * Date: 2019/8/28 15:36
     */
    public function creditCashing()
    {
        global $_W , $_GPC;
        $cashsets        = Setting::wlsetting_read('cashset');
        //判断临时申请内容
        $flag = pdo_get('wlmerchant_settlement_temporary',array('mid' => $_W['mid'],'uniacid'=>$_W['uniacid'],'type' => 1),array('id'));
        if(!empty($flag)){
            $this->renderError('您有处理中的提现申请,请稍后再试');
        }
        $settlementmoney = $_GPC['settlementmoney'];
        $cashtype        = $_GPC['cashtype'];
        //判断提现时间限制
        $memberInfo       = pdo_get('wlmerchant_member' , ['id' => $_W['mid']]);
        $shopIntervalTime = $cashsets['shopIntervalTime'];
        if ($shopIntervalTime > 0) {
            //获取上次提现申请时间
            $startTime   = pdo_fetchcolumn("SELECT applytime FROM " . tablename(PDO_NAME . "settlement_record") . " WHERE mid = {$_W['mid']} AND type = 4 AND uniacid = {$_W['uniacid']} ORDER BY applytime DESC ");
            $interval    = time() - $startTime;
            $intervalDay = $interval / 3600 / 24;
            //判断间隔时间
            $intercalRes = ceil($shopIntervalTime - $intervalDay);
            if ($intercalRes > 0) {
                die(json_encode(['errno' => 1 , 'message' => '请等' . $intercalRes . '天后再申请！']));
                $this->renderError('请等' . $intercalRes . '天后再申请！');
            }
        }
        //判断提现金额限制
        if ($_GPC['settlementmoney'] < $cashsets['lowsetmoney']) {
            $this->renderError('申请失败，最低提现金额为' . $cashsets['lowsetmoney'] . '元。');
        }
        if ($_GPC['settlementmoney'] > $cashsets['maxsetmoney'] && $cashsets['maxsetmoney'] > 0) {
            $this->renderError('申请失败，单次最大提现金额为' . $cashsets['maxsetmoney'] . '元。');
        }
        //提现方式
        if ($cashtype == 1) {
            $alipay = $_GPC['alipay'];
            $realname = $_GPC['realname'];
            if (empty($alipay)) {
                $this->renderError('请填写支付宝账号');
            }
            if (empty($realname)) {
                $this->renderError('请填写支付宝账号真实姓名');
            }
            pdo_update('wlmerchant_member' , ['alipay' => $alipay,'realname'=>$realname] , ['id' => $_W['mid']]);
        }
        if ($cashtype == 2) {
            if ($_W['source'] == 1) {
                if (empty($memberInfo['openid'])) {
                    $this->renderError('您无微信账号数据，无法微信提现');
                }
                else {
                    $sopenid = $memberInfo['openid'];
                }
            }
            else if ($_W['source'] == 3) {
                if (empty($memberInfo['wechat_openid'])) {
                    $this->renderError('您无微信账号数据，无法微信提现');
                }
                else {
                    $sopenid = $memberInfo['wechat_openid'];
                }
            }
        }
        else {
            $sopenid = '';
        }
        if ($cashtype == 3) {
            $bank_name     = $_GPC['bank_name'];
            $card_number   = $_GPC['card_number'];
            $bank_username = $_GPC['bank_username'];
            if (empty($bank_name) || empty($card_number) || empty($bank_username)) {
                $this->renderError('请填写银行卡各项参数');
            }
            pdo_update('wlmerchant_member' , [
                'bank_name'     => $bank_name ,
                'card_number'   => $card_number ,
                'bank_username' => $bank_username
            ] , ['id' => $_W['mid']]);
        }
        //判断一下还有没有余额
        $nowmoney = pdo_getcolumn('mc_members' , [
            'uniacid' => $_W['uniacid'] ,
            'uid'     => $memberInfo['uid']
        ] , 'credit2');
        if ($settlementmoney > $nowmoney) {
            $this->renderError('可提现余额不足');
        }
        $syssalepercent = sprintf("%.2f" , $cashsets['memberpercent']);
        $spercentmoney = sprintf("%.2f" , $syssalepercent * $settlementmoney / 100);
        $sgetmoney     = sprintf("%.2f" , $settlementmoney - $spercentmoney);
        $data          = [
            'uniacid'       => $_W['uniacid'] ,
            'mid'           => $memberInfo['id'] ,
            'aid'           => $_W['aid'] ,
            'status'        => 2 ,
            'type'          => 4 ,
            'sapplymoney'   => $settlementmoney ,
            'sgetmoney'     => $sgetmoney ,
            'spercentmoney' => $spercentmoney ,
            'spercent'      => $syssalepercent ,
            'applytime'     => TIMESTAMP ,
            'updatetime'    => TIMESTAMP ,
            'sopenid'       => $sopenid ,
            'payment_type'  => $cashtype ,
            'source'        => $_W['source']
        ];
        $value = serialize($data);
        $temporary = array(
            'info' => $value,
            'type' => 1,
            'uniacid' => $_W['uniacid'],
            'mid'  => $data['mid']
        );
        $res = pdo_insert(PDO_NAME . 'settlement_temporary' , $temporary);
        if($res){
            $this->renderSuccess('申请成功');
        }else{
            $this->renderError('申请失败，请重试');
        }
    }
    /**
     * Comment: 余额提现记录
     * Author: wlf
     * Date: 2019/8/28 14:16
     */
    public function creditCashRecord()
    {
        global $_W , $_GPC;
        $status     = $_GPC['status'];
        $page       = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_start = $page * 10 - 10;
        $where      = " WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} AND type = 4";
        if ($status == 1) {
            $where .= " AND status IN (1,2,3)";
        }
        else if ($status == 2) {
            $where .= " AND status = 5 ";
        }
        else if ($status == 3) {
            $where .= " AND status IN (-1,-2)";
        }
        $record = pdo_fetchall("SELECT applytime,sapplymoney,payment_type,spercentmoney,status FROM " . tablename('wlmerchant_settlement_record') . "{$where} ORDER BY id DESC LIMIT {$page_start},10");
        $allnum = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_settlement_record') . " {$where}");
        if ($record) {
            foreach ($record as $re) {
                $re['applytime'] = date('Y-m-d H:i:s' , $re['applytime']);
            }
        }
        $data['list']      = $record;
        $data['pagetotal'] = ceil($allnum / 10);
        $this->renderSuccess('提现列表' , $data);
    }
    /**
     * Comment: 用户主页信息获取
     * Author: zzw
     * Date: 2019/8/30 17:18
     */
    public function mainPageInfo()
    {
        global $_W , $_GPC;
        #1、参数获取
        $_GPC['mid'] ? $mid = $_GPC['mid'] : $this->renderError("缺少参数：mid");
        $set = Setting::wlsetting_read('userset');
        #2、获取用户信息
        $info               = pdo_get(PDO_NAME . "member" , ['id' => $mid] , [
            'nickname' ,
            'avatar' ,
            'createtime' ,
            'main_browse'
        ]);
        $browse             = $info['main_browse'] ? : intval(0);
        $info['createtime'] = date("Y-m-d H:i:s" , $info['createtime']);
        if ($info['main_browse'] >= 10000) $info['main_browse'] = sprintf("%.1f" , ($info['main_browse'] / 10000)) . '万';
        #3、获取用户访客总数
        $info['visitor'] = pdo_getcolumn(PDO_NAME . "member_visitor_record" , ['mid' => $mid] , 'count(*)');
        #4、获取用户发帖总数
        $info['release'] = pdo_getcolumn(PDO_NAME . "pocket_informations" , [
            'mid'    => $mid ,
            'status' => 0
        ] , 'count(*)');
        #5、用户访问操作
        if ($_W['mid'] != $mid) {
            #6、当前用户的浏览量自增一
            pdo_update(PDO_NAME . "member" , ['main_browse' => ($browse + 1)] , ['id' => $mid]);
            #7、判断当前用户是否为第一次访问
            $data       = ['visitor_id' => $_W['mid'] , 'mid' => $mid];
            $visitor_id = pdo_getcolumn(PDO_NAME . "member_visitor_record" , $data , 'id');
            if ($visitor_id) {
                pdo_update(PDO_NAME . "member_visitor_record" , ['update_time' => time()] , ['id' => $visitor_id]);
            }
            else {
                $data['create_time'] = time();
                $data['update_time'] = time();
                pdo_insert(PDO_NAME . "member_visitor_record" , $data);
            }
        }
        //获取背景图
        if ($_W['mid'] != $mid) {
            $info['useself'] = 0;
        }
        else {
            $info['useself'] = 1;
        }
        $main_bgimg = pdo_getcolumn(PDO_NAME . 'member' , ['id' => $mid] , 'main_bgimg');
        if (!empty($main_bgimg)) {
            $info['thumb']     = tomedia($main_bgimg);
            $info['isdefault'] = 0;
        }
        else {
            $info['thumb']     = !empty($set['usermainbg']) ? tomedia($set['usermainbg']) : URL_MODULE . 'h5/resource/image/mainbgimg.png';
            $info['isdefault'] = 1;
        }
        //获取认证信息
        if (p('attestation')) {
            $info['attestation'] = Attestation::checkAttestation(1 , $mid);
        }
        else {
            $info['attestation'] = 0;
        }
        //判断当前平台是否存在掌上信息插件
        $info['is_pocket'] = intval(0);//0=不存在  1=存在
        if (uniacid_p('pocket') && $info['release'] > 0) {
            $info['is_pocket'] = intval(1);
        }
        //判断是否有商户
        $storeinfo = pdo_fetchall("SELECT b.id FROM " . tablename(PDO_NAME . "merchantuser") . " as a RIGHT JOIN " . tablename(PDO_NAME . "merchantdata") . " as b ON a.storeid = b.id WHERE a.mid = {$mid} AND a.enabled = 1 AND b.enabled = 1 GROUP BY a.storeid ");
        $storenum = count($storeinfo);
        $info['is_store'] = $storenum > 0 ? 1 : 0;
        //判断用户是否是家政服务者
        $info['is_artificer'] = intval(0);//0=不存在  1=存在
        if (p('housekeep')) {
            $artificer = pdo_get(PDO_NAME.'housekeep_artificer',array('uniacid'=>$_W['uniacid'],'status' => 1,'aid'=>$_W['aid'],'mid' => $mid),['id','detail','thumbs','casethumbs','tagarray']);
            if($artificer['id'] > 0){
                $info['is_artificer'] = intval(1);
                $info['housekeep_type'] = Housekeep::getRelation($artificer['id'],2);
                $info['thumbs'] = Housekeep::beautifyImgInfo($artificer['thumbs']);
                $info['casethumbs'] = Housekeep::beautifyImgInfo($artificer['casethumbs']);
                $info['detail'] = $artificer['detail'];
                $info['tagarray'] = unserialize($artificer['tagarray']);
            }
        }
        $this->renderSuccess("用户主页信息" , $info);
    }
    /**
     * Comment: 获取用户访客列表
     * Author: zzw
     * Date: 2019/8/30 17:47
     */
    public function visitorList()
    {
        global $_W , $_GPC;
        #1、参数获取
        $_GPC['mid'] ? $mid = $_GPC['mid'] : $this->renderError("缺少参数：mid");
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        #2、获取总数
        $total = pdo_getcolumn(PDO_NAME . "member_visitor_record" , ['mid' => $mid] , 'count(*)');
        #3、获取访客信息列表
        $list = pdo_fetchall("SELECT b.nickname,b.avatar,FROM_UNIXTIME(a.update_time,'%Y-%m-%d %H:%i:%S') as update_time FROM " . tablename(PDO_NAME . "member_visitor_record") . " as a LEFT JOIN " . tablename(PDO_NAME . "member") . " as b ON a.visitor_id = b.id WHERE a.mid = {$mid} ORDER BY a.update_time DESC LIMIT {$pageStart},{$pageIndex}");
        #2、数据拼装
        $data['total'] = ceil($total / $pageIndex);
        $data['list']  = $list;
        $this->renderSuccess('访客列表' , $data);
    }
    /**
     * Comment: 买家提醒商家发货
     * Author: wlf
     * Date: 2019/9/19 11:43
     */
    public function remindSend()
    {
        global $_W , $_GPC;
        $orderid   = $_GPC['orderid'];
        $plugin    = $_GPC['plugin'];
        $goodsname = $_GPC['goodsname'];
        if ($orderid == $_GPC['remind_Send']) {
            $this->renderError('此订单已经提醒发货');
        }
        //获取订单数据
        if ($plugin == 'rush') {
            $order = pdo_get('wlmerchant_rush_order' , ['id' => $orderid] , ['sid' , 'num' , 'orderno']);
        }
        else {
            $order = pdo_get('wlmerchant_order' , ['id' => $orderid] , ['sid' , 'num' , 'orderno']);
        }
        //获取商户店员
        $merchantusers = pdo_fetchall("SELECT mid FROM " . tablename('wlmerchant_merchantuser') . "WHERE storeid = {$order['sid']} AND ismain IN (1,3) ORDER BY id DESC");
        if ($merchantusers) {
            foreach ($merchantusers as $user) {
                $data = [
                    'first'   => '订单编号[' . $order['orderno'] . ']' ,
                    'type'    => '买家提醒发货' ,//业务类型
                    'content' => '商品名称:[' . $goodsname . ']' ,//业务内容
                    'status'  => '待发货' ,//处理结果
                    'time'    => date('Y-m-d H:i:s' , time()) ,//操作时间
                    'remark'  => '请商家负责人尽快发货'
                ];
                TempModel::sendInit('service' , $user['mid'] , $data , $_W['source'] , '');
            }
        }
        isetcookie("remind_Send" , $orderid , 86400);
        $this->renderSuccess('提醒成功');
    }
    /**
     * Comment: 登录接口（所有登录方式） —— 开发中(小程序完成)
     * Author: zzw
     * Date: 2019/11/18 15:09
     */
    public function login()
    {
        global $_W , $_GPC;
        #1、参数获取
        $source = $_GPC['source1'] ? $_GPC['source1'] : $_W['source'];
        if(empty($source)){
            $this->renderError('访问渠道错误!');//1=公众号（默认）；2=H5；3=小程序 4=app
        }
        /*        $type = 'mode' . $_GPC['mode'] OR $this->renderError('非法登录，请使用正确的登录方式');//1-账号密码登录 2=微信登录
                #2、生成对应的方法名称
                # 'loginSource1Mode2'=公众号微信登录；
                # 'loginSource2Mode1'=H5账号密码登录；
                # 'loginSource3Mode2'=小程序微信登录；
                $name = 'login' . ucfirst($source) . ucfirst($type);
                #3、判断方法是否存在
                $instance = new Login();
                if (method_exists($instance, $name)) $result = $instance->$name($_GPC);
                else $this->renderError('非法登录，请使用正确的登录方式!');
                #4、根据登录结果进行对应的操作
                switch ($name) {
                    case 'loginSource1Mode2':
                        //拼装用户信息
                        $userInfo = [
                            'openid'   => $result['openid'] ?: '',//用户openid
                            'nickname' => $result['nickname'] ?: '',//用户昵称
                            'avatar'   => $result['headimgurl'] ?: '',//用户头像
                            'unionid'  => $result['unionid'] ?: '',//用户unionid
                        ];
                        //判断是否获取用户信息
                        if (empty($userInfo['openid'])) $this->renderError('登录信息获取失败!');
                        //获取用户数据
                        $sql = "SELECT id,tokey FROM " . tablename(PDO_NAME . "member") . " WHERE openid = '{$userInfo['openid']}' ";
                        break;//公众号微信登录
                    case 'loginSource2Mode1':
                        break;//H5账号密码登录
                    case 'loginSource3Mode2':
                        //解密获取用户基本信息
                        $encryptedData = $_GPC['encryptedData'] OR $this->renderError('授权失败，缺少加密信息!');
                        $iv = $_GPC['iv'] OR $this->renderError('授权失败，缺少加密信息');
                        $basicInfo = openssl_decrypt(base64_decode($encryptedData), "AES-128-CBC", base64_decode($result['session_key']), 1, base64_decode($iv));
                        $basicInfo = json_decode($basicInfo, true);
                        //拼装用户数据信息
                        $userInfo = [
                            'wechat_openid' => $result['openid'] ?: '',//用户openid
                            'nickname'      => $basicInfo['nickName'] ?: '',//用户昵称
                            'avatar'        => $basicInfo['avatarUrl'] ?: '',//用户头像
                            'unionid'       => $result['unionid'] ?: '' //用户unionid
                        ];
                        $sessionKey = $result['session_key'] ?: '';
                        //判断是否获取用户信息
                        if (empty($userInfo['wechat_openid'])) $this->renderError('登录信息获取失败!');

                        //获取用户数据
                        $sql = "SELECT id,tokey FROM " . tablename(PDO_NAME . "member") . " WHERE wechat_openid = '{$userInfo['wechat_openid']}'";
                        break;//小程序微信登录
                }
                #4、判断用户是否存在
                $userInfo2 = pdo_fetch($sql);

                if (!$userInfo2) {
                    //生成微擎用户信息数据
                    $group_id = pdo_getcolumn('mc_groups', ['uniacid' => $_W['uniacid'], 'isdefault' => 1], 'groupid');
                    $salt = random(8);
                    $data = [
                        'uniacid'    => $_W['uniacid'],
                        'salt'       => $salt,
                        'groupid'    => $group_id,
                        'createtime' => TIMESTAMP,
                        'nickname'   => $userInfo['nickname'],
                    ];
                    //储存微擎用户数据信息
                    pdo_insert('mc_members', $data);
                    $uid = pdo_insertid();
                    //生成平台用户数据信息
                    $data2 = [
                        'uid'           => $uid,
                        'uniacid'       => $_W['uniacid'],
                        'openid'        => $userInfo['openid'],
                        'wechat_openid' => $userInfo['wechat_openid'],
                        'nickname'      => $userInfo['nickname'],
                        'salt'          => $salt,
                        'avatar'        => $userInfo['avatar'] ?: './addons/weliam_smartcity/app/resource/image/touxiang.png',
                        'registerflag'  => 1,
                        'tokey'         => strtoupper(MD5(sha1(time() . random(12)))),
                        'createtime'    => time()
                    ];
                    if (!empty($sessionKey)) $data2['session_key'] = $sessionKey;
                    pdo_insert(PDO_NAME . 'member', $data2);
                } else if (!empty($sessionKey)) {
                    $data2 = [
                        'nickname' => $userInfo['nickname'],
                        'avatar'   => $userInfo['avatar'] ?: './addons/weliam_smartcity/app/resource/image/touxiang.png',
                    ];
                    if (!empty($sessionKey)) $data2['session_key'] = $sessionKey;
                    pdo_update(PDO_NAME . "member", $data2, ['id' => $userInfo2['id']]);
                }
                $userInfo['tokey'] = $userInfo2['tokey'] ? $userInfo2['tokey'] : $data2['tokey'];
                wl_setcookie('usersign', $userInfo, 3600 * 24 * 30);
                wl_setcookie('user_token', $userInfo['tokey'], 3600 * 24 * 30);*/
        if($source == 1){
            $result = Login::loginSource1Mode2($_GPC);
            if (is_error($result)) {
                $this->renderError($result['message']);
            }
        }else if($source == 3){
            $result = Login::loginSource3Mode2($_GPC);
            if (is_error($result)) {
                $this->renderError($result['message']);
            }
            //解密获取用户基本信息
            $encryptedData = $_GPC['encryptedData'] OR $this->renderError('授权失败，缺少加密信息!');
            $iv = $_GPC['iv'] OR $this->renderError('授权失败，缺少加密信息');
            $basicInfo = openssl_decrypt(base64_decode($encryptedData) , "AES-128-CBC" , base64_decode($result['session_key']) , 1 , base64_decode($iv));
            if(empty($basicInfo)){
                $this->renderError('用户信息解密失败，请返回重新登录');
            }
            $basicInfo = json_decode($basicInfo , true);
            if (empty($result['openid'])) {
                $this->renderError('登录信息获取失败!');
            }
            //拼装用户数据信息
            $userInfo = [
                'wechat_openid' => $result['openid'] ? : '' ,//用户openid
                'unionid'       => $basicInfo['unionId'] ? $basicInfo['unionId'] : $result['unionid'] ,//用户unionid
                'session_key'   => $result['session_key'] ? : ''//用户session_key
            ];
            $member   = Member::wl_member_create($userInfo , 'wxapp');
            $head_id  = intval($_GPC['head_id']);
            if ($_W['source'] == 3 && $head_id > 0 && p('distribution') && $head_id != $member['id']) {
                Distribution::addJunior($head_id , $member['id']);
            }
            $token = pdo_getcolumn(PDO_NAME . 'login' , [
                'token'          => $member['tokey'] ,
                'refresh_time >' => time()
            ] , 'secret_key');
            if (empty($token)) {
                $res   = Login::generateToken($member['tokey'] , 'login');
                $token = $res['message'];
            }
        }else if($source == 4){
            file_put_contents(PATH_DATA . "appinfo.log", var_export($_GPC, true) . PHP_EOL, FILE_APPEND);

            $userInfo = [
                'webapp_openid' => $_GPC['openid'] ? : '' ,//用户openid
                'nickname'      => $_GPC['nickName'] ? : '' ,//用户昵称
                'avatar'        => $_GPC['avatarUrl'] ? : '' ,//用户头像
                'unionid'       => $_GPC['unionid'],//用户unionid
            ];

            if (empty($userInfo['webapp_openid'])) {
                $this->renderError('无openid无法登陆');
            }
            $member   = Member::wl_member_create($userInfo , 'webapp');
            $token = pdo_getcolumn(PDO_NAME . 'login' , [
                'token'          => $member['tokey'] ,
                'refresh_time >' => time()
            ] , 'secret_key');
            if (empty($token)) {
                $res   = Login::generateToken($member['tokey'] , 'login');
                $token = $res['message'];
            }
        }

        $this->renderSuccess('登录成功' , ['token' => $token]);
    }
    /**
     * Comment: 修改个人信息
     * Author: zzw
     * Date: 2019/11/5 14:05
     */
    public function setUserInfo()
    {
        global $_W , $_GPC;
        #1、参数获取
        $type = $_GPC['type'] ? : 'get';//get=获取信息；set=设置信息
        if(!empty($_GPC['realname'])){
            $info['realname'] = $_GPC['realname'];
        }
        if(!empty($_GPC['bank_name'])){
            $info['bank_name'] = $_GPC['bank_name'];
        }
        if(!empty($_GPC['card_number'])){
            $info['card_number'] = $_GPC['card_number'];
        }
        if(!empty($_GPC['bank_username'])){
            $info['bank_username'] = $_GPC['bank_username'];
        }
        if(!empty($_GPC['alipay'])){
            $info['alipay'] = $_GPC['alipay'];
        }
        if(!empty($_GPC['nickname'])){
        	//判断文本内容是否非法
	        $textRes = Filter::init($_GPC['nickname'],$_W['source'],1);
	        if($textRes['errno'] == 0){
	            $this->renderError($textRes['message']);
	        }
            $info['nickname'] = $_GPC['nickname'];
            $info['encodename'] = base64_encode($_GPC['nickname']);
        }
        if(!empty($_GPC['avatar'])){
            $info['avatar'] = $_GPC['avatar'];
        }
        #2、获取用户信息
        if ($type == 'get') {
            $getInfo = pdo_get(PDO_NAME . "member" , ['id' => $_W['mid']] , [
                'realname' ,
                'bank_name' ,
                'card_number' ,
                'bank_username' ,
                'alipay',
                'nickname',
                'avatar',
                'encodename'
            ]);
            //判断设置项目
            $getInfo['bankstatus'] = $_W['wlsetting']['cashset']['payment_type']['bank_card'] ? : 0;
            $getInfo['alipaystatus'] = $_W['wlsetting']['cashset']['payment_type']['alipay'] ? : 0;
            $getInfo['nickname'] = !empty($getInfo['encodename']) ? base64_decode($getInfo['encodename']) : $getInfo['nickname'];
            if ($getInfo){
                $this->renderSuccess('用户信息' , $getInfo);
            }else{
                $this->renderError('用户信息不存在');
            }
        }
        #3、设置用户信息
        if ($type == 'set' && is_array($info) && count($info) > 0) {
            //查询是否修改内容
//            $isHave = pdo_get(PDO_NAME . "member" , $info);
//            if ($isHave) $this->renderError('请修改信息后提交!');
            //修改内容 提交修改内容的数据信息
            $setInfo = pdo_update(PDO_NAME . "member" , $info , ['id' => $_W['mid']]);
            if ($setInfo) $this->renderSuccess('修改成功');
            else $this->renderError('修改失败，请确认有内容改变');
        }
    }
    /**
     * Comment: 上传个人主页图片
     * Author: wlf
     * Date: 2019/11/15 16:23
     */
    public function setMainBgImg()
    {
        global $_W , $_GPC;
        $img = $_GPC['img'];
        pdo_update('wlmerchant_member' , ['main_bgimg' => $img] , ['id' => $_W['mid']]);
        $this->renderSuccess('设置成功');
    }
    /**
     * Comment: 小程序用户的手机加密信息解密操作
     * Author: zzw
     * Date: 2019/11/18 15:37
     */
    public function phoneDecrypt(){
        global $_W , $_GPC;
        //基本参数信息获取
        $data = $_GPC['data'] OR $this->renderError('缺少加密数据！');
        $iv = $_GPC['iv'] OR $this->renderError('缺少加密算法的初始向量！');
        $reslus = Login::loginSource3Mode2($_GPC);
        $session_key = $reslus['session_key'];
        if(!$session_key) $this->reLogin('session_key为空，请重新登录');
        //数据解密
        try {
            $content = WeApp::decryptedMobile($session_key , $iv , $data);
            $this->renderSuccess('用户手机号' , ['phone' => $content['phoneNumber']]);
        }catch (Exception $e){
            $this->renderError("绑定失败，请重试！");
        }
    }
    /**
     * Comment: 通过经纬度获取当前城市参数
     * Author: wlf
     * Date: 2020/05/18 13:55
     */
    public function lng2areaid()
    {
        global $_W , $_GPC;
        $lng = $_GPC['lng'];
        $lat = $_GPC['lat'];
        if (empty($lng) || empty($lat)) {
            $this->renderError('参数错误');
        }
        $area = MapService::guide_gcoder($lat . ',' . $lng , 0);
        if (!is_error($area)) {
            $areaid = $area['result']['ad_info']['adcode'];
        }
        else {
            $this->renderError($area['message']);
        }
        $area = pdo_get('wlmerchant_area' , ['id' => $areaid] , ['pid' , 'level']);
        if ($area['level'] == 3) {
            $data['countyid']   = $areaid;
            $data['cityid']     = $area['pid'];
            $data['provinceid'] = pdo_getcolumn(PDO_NAME . 'area' , ['id' => $area['pid']] , 'pid');
        }
        else if ($area['level'] == 2) {
            $data['cityid']     = $areaid;
            $data['countyid']   = pdo_getcolumn(PDO_NAME . 'area' , ['pid' => $areaid] , 'id');
            $data['provinceid'] = $area['pid'];
        }
        else if ($area['level'] == 1) {
            $data['provinceid'] = $areaid;
            $data['cityid']     = pdo_getcolumn(PDO_NAME . 'area' , ['pid' => $areaid] , 'id');
            $data['countyid']   = pdo_getcolumn(PDO_NAME . 'area' , ['pid' => $data['cityid']] , 'id');
        }
        else {
            $this->renderError('无信息' , ['provinceid' => 0 , 'cityid' => 0 , 'countyid' => 0]);
        }
        $this->renderSuccess('城市信息' , $data);
    }
    /**
     * Comment: 创建余额转赠活动(N561定制)
     * Author: wlf
     * Date: 2020/06/01 18:14
     */
    public function createTransfer()
    {
        global $_W , $_GPC;
        if (!file_exists(PATH_MODULE . 'N561.log')) {
            $this->renderError('暂无权限');
        }
        $title    = $_GPC['title'];
        $allnum   = $_GPC['allnum'];
        $money    = sprintf("%.2f" , $_GPC['money']);
        $allmoney = sprintf("%.2f" , $money * $allnum);
        MysqlFunction::setTrans(4);
        MysqlFunction::startTrans();
        $res = Member::credit_update_credit2($_W['mid'] , -$allmoney , '创建转赠活动[' . $title . ']活动扣除余额');
        if (!is_error($res)) {
            $transfer = [
                'uniacid'    => $_W['uniacid'] ,
                'mid'        => $_W['mid'] ,
                'title'      => $title ,
                'allnum'     => $allnum ,
                'surplus'    => $allnum ,
                'money'      => $money ,
                'allmoney'   => $allmoney ,
                'createtime' => time()
            ];
            $res      = pdo_insert(PDO_NAME . 'transfer_list' , $transfer);
            $transid  = pdo_insertid();
            if ($res) {
                //生成二维码
                //使用默认二维码
                $path = 'pages/subPages/balance/collectMoney/collectMoney?transfer_id=' . $transid . '&head_id=' . $_W['mid'];//基本路径，也是小程序路径  页面站位中
                #3、二维码生成
                //公众号
                $path1        = h5_url($path);
                $filename     = md5('transfer_id' . $transid . 'source1path' . $path1);
                $qrCodeLink   = Poster::qrcodeimg($path1 , $filename);
                $wechatqrlink = tomedia($qrCodeLink);
                //小程序
                $qrCodeLink3 = WeApp::getQrCode($path , 'qrcode_' . $filename . '.png');
                if (is_array($qrCodeLink3)) $qrCodeLink3 = Poster::qrcodeimg($path , $filename);
                $wxappqrlink = tomedia($qrCodeLink3);
                //生成短连接
                $url    = h5_url('pages/subPages/balance/collectMoney/collectMoney' , [
                    'transfer_id' => $transid ,
                    'head_id'     => $_W['mid']
                ]);
                $result = Util::long2short($url);
                file_put_contents(PATH_DATA . "long2short.log" , var_export($result , true) . PHP_EOL , FILE_APPEND);
                if (!is_error($result) && $result['short_url'] != 'h') {
                    $url = $result['short_url'];
                }
                pdo_update(PDO_NAME . 'transfer_list' , [
                    'wechatqrlink' => $wechatqrlink ,
                    'wxappqrlink'  => $wxappqrlink ,
                    'pageurl'      => $url
                ] , ['id' => $transid]);
                MysqlFunction::commit();
                $this->renderSuccess('创建成功' , ['transid' => $transid]);
            }
            else {
                MysqlFunction::rollback();
                $this->renderError('活动创建失败,请刷新重试');
            }
        }
        else {
            MysqlFunction::rollback();
            $this->renderError($res['message']);
        }
    }
    /**
     * Comment: 初始化余额转赠活动页面(N561定制)
     * Author: wlf
     * Date: 2020/06/02 14:10
     */
    public function transferpage()
    {
        global $_W , $_GPC;
        if (!file_exists(PATH_MODULE . 'N561.log')) {
            $this->renderError('暂无权限');
        }
        if (empty($_W['mid'])) {
            $this->renderError('请先登录');
        }
        $id = $_GPC['id'];
        if (empty($id)) {
            $this->renderError('缺少关键参数:id');
        }
        $transfer = pdo_get('wlmerchant_transfer_list' , ['id' => $id] , ['title' , 'surplus' , 'mid' , 'money']);
        $member   = pdo_get('wlmerchant_member' , ['id' => $transfer['mid']] , ['nickname' , 'avatar']);
        $data     = [
            'nickname' => $member['nickname'] ,
            'avatar'   => tomedia($member['avatar']) ,
            'title'    => $transfer['title'] ,
            'money'    => $transfer['money']
        ];
        $this->renderSuccess('初始化信息' , $data);
    }
    /**
     * Comment: 用户获取转赠余额(N561定制)
     * Author: wlf
     * Date: 2020/06/02 14:57
     */
    public function getTransfer()
    {
        global $_W , $_GPC;
        if (!file_exists(PATH_MODULE . 'N561.log')) {
            $this->renderError('暂无权限');
        }
        if (empty($_W['mid'])) {
            $this->renderError('请先登录');
        }
        $id = $_GPC['id'];
        if (empty($id)) {
            $this->renderError('缺少关键参数:id');
        }
        $realname = $_GPC['realname'];
        if (empty($realname)) {
            $this->renderError('请填写您的姓名');
        }
        $mobile = $_GPC['mobile'];
        if (empty($mobile)) {
            $this->renderError('请填写您的手机号');
        }
        $flag = pdo_get('wlmerchant_transfer_record' , ['mid' => $_W['mid'] , 'tid' => $id] , ['id']);
        if (empty($flag)) {
            pdo_update('wlmerchant_member' , ['mobile' => $mobile , 'realname' => $realname] , ['id' => $_W['mid']]);
            $transfer = pdo_get('wlmerchant_transfer_list' , ['id' => $id] , [
                'title' ,
                'surplus' ,
                'money' ,
                'is_over'
            ]);
            if (!empty($transfer['is_over'])) {
                $this->renderError('转赠活动已过期');
            }
            if ($transfer['surplus'] < 1) {
                $this->renderError('转赠活动已结束');
            }
            MysqlFunction::setTrans(4);
            MysqlFunction::startTrans();
            pdo_update('wlmerchant_transfer_list' , ['surplus -=' => 1] , ['id' => $id]);
            $record = [
                'uniacid'    => $_W['uniacid'] ,
                'tid'        => $id ,
                'mid'        => $_W['mid'] ,
                'money'      => $transfer['money'] ,
                'realname'   => $realname ,
                'mobile'     => $mobile ,
                'createtime' => time()
            ];
            $res    = pdo_insert(PDO_NAME . 'transfer_record' , $record);
            if ($res) {
                $changeres = Member::credit_update_credit2($_W['mid'] , $record['money'] , '参与转赠活动[' . $transfer['title'] . ']活动获得余额');
                if ($changeres) {
                    MysqlFunction::commit();
                    $this->renderSuccess('活动参与成功');
                }
                else {
                    MysqlFunction::rollback();
                    $this->renderError('余额获取失败，请刷新重试');
                }
            }
            else {
                MysqlFunction::rollback();
                $this->renderError('活动参与失败，请刷新重试');
            }
        }
        else {
            $this->renderError('您已参加过此活动，无法重复参加');
        }
    }
    /**
     * Comment: 转赠活动详情(N561定制)
     * Author: wlf
     * Date: 2020/06/02 15:57
     */
    public function transferDetail()
    {
        global $_W , $_GPC;
        if (!file_exists(PATH_MODULE . 'N561.log')) {
            $this->renderError('暂无权限');
        }
        $id = $_GPC['id'];
        if (empty($id)) {
            $this->renderError('缺少关键参数:id');
        }
        $transfer = pdo_get('wlmerchant_transfer_list' , ['id' => $id] , [
            'title' ,
            'surplus' ,
            'mid' ,
            'allmoney' ,
            'wechatqrlink' ,
            'wxappqrlink' ,
            'pageurl'
        ]);
        $member   = pdo_get('wlmerchant_member' , ['id' => $transfer['mid']] , ['nickname' , 'avatar']);
        if ($_W['source'] == 3) {
            $qrCodeLink = $transfer['wxappqrlink'];
        }
        else {
            $qrCodeLink = $transfer['wechatqrlink'];
        }
        $data = [
            'nickname' => $member['nickname'] ,
            'avatar'   => tomedia($member['avatar']) ,
            'title'    => $transfer['title'] ,
            'money'    => $transfer['allmoney'] ,
            'qrlink'   => $qrCodeLink ,
            'pageurl'  => $transfer['pageurl']
        ];
        $this->renderSuccess('活动详情' , $data,1);
    }
    /**
     * Comment: 余额转赠往期活动列表(N561定制)
     * Author: wlf
     * Date: 2020/06/02 14:57
     */
    public function oldTransferList()
    {
        global $_W , $_GPC;
        if (!file_exists(PATH_MODULE . 'N561.log')) {
            $this->renderError('暂无权限');
        }
        if (empty($_W['mid'])) {
            $this->renderError('请先登录');
        }
        $data              = [];
        $pindex            = $_GPC['pindex'] ? $_GPC['pindex'] : 1;
        $pageStart         = $pindex * 10 - 10;
        $list              = pdo_fetchall("SELECT id,allnum,title,surplus,allmoney,createtime,is_over FROM " . tablename('wlmerchant_transfer_list') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} ORDER BY createtime DESC LIMIT {$pageStart},10");
        $allnum            = pdo_fetchcolumn("SELECT count(id) FROM " . tablename('wlmerchant_transfer_list') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']}");
        $data['pagetotal'] = ceil($allnum / 10);
        if (!empty($list)) {
            foreach ($list as &$li) {
                $li['createtime'] = date('Y-m-d H:i:s' , $li['createtime']);
            }
        }
        $data['list'] = $list;
        $this->renderSuccess('往期活动' , $data);
    }
    /**
     * Comment: 余额转赠活动领取记录(N561定制)
     * Author: wlf
     * Date: 2020/06/05 16:16
     */
    public function getTransferRecord()
    {
        global $_W , $_GPC;
        if (!file_exists(PATH_MODULE . 'N561.log')) {
            $this->renderError('暂无权限');
        }
        if (empty($_W['mid'])) {
            $this->renderError('请先登录');
        }
        $id     = $_GPC['id'];
        $pindex = max(1 , intval($_GPC['page']));
        $psize  = 20;
        $start  = $pindex * $psize - $psize;
        if (empty($id)) {
            $this->renderError('缺少关键参数:id');
        }
        if ($pindex == 1) {
            $transfer               = pdo_get('wlmerchant_transfer_list' , ['id' => $id] , [
                'title' ,
                'surplus' ,
                'allnum' ,
                'allmoney' ,
                'createtime'
            ]);
            $transfer['createtime'] = date("Y-m-d H:i:s" , $transfer['createtime']);
            $data['transfer']       = $transfer;
        }
        $where = " a.uniacid = {$_W['uniacid']} AND a.tid = {$id} ";
        $limit = " LIMIT {$start},{$psize}";
        $sql   = "SELECT a.tid,a.mid,a.money,a.realname,a.mobile,a.createtime,b.nickname,b.avatar FROM " . tablename("wlmerchant_transfer_record") . " as a LEFT JOIN " . tablename(PDO_NAME . "member") . " as b ON a.mid = b.id WHERE {$where} ORDER BY a.createtime DESC";
        $total           = pdo_fetchcolumn('SELECT count(a.id) FROM ' . tablename('wlmerchant_transfer_record') . " as a LEFT JOIN " . tablename(PDO_NAME . "member") . " as b ON a.mid = b.id WHERE {$where}");
        $data['allpage'] = ceil($total / 20);
        $lists           = pdo_fetchall($sql . $limit);
        foreach ($lists as &$li) {
            $li['createtime'] = date('Y-m-d H:i:s' , $li['createtime']);
            $li['avatar']     = tomedia($li['avatar']);
        }
        $data['record'] = $lists;
        $this->renderSuccess('领取记录' , $data);
    }

    /**
     * Comment: 根据手机号筛选用户
     * Author: wlf
     * Date: 2021/08/19 16:16
     */
    public function mobileToMmeber(){
        global $_W , $_GPC;
        $mobile = trim($_GPC['mobile']);
        if(empty($mobile)){
            $this->renderError('请输入查询手机号');
        }else{
            $member = pdo_get('wlmerchant_member',array('mobile' => $mobile),array('avatar','nickname','mobile'));
            if(empty($member)){
                $member = [];
            }else{
                $member['avatar'] = tomedia($member['avatar']);
            }
        }
        $this->renderSuccess('查询成功' , $member);
    }

    /**
     * Comment: 074定制-积分转赠页面初始化
     * Author: wlf
     * Date: 2022/01/06 10:10
     */
    public function integralPage(){
        global $_W, $_GPC;
        $data['mycredit'] = sprintf("%.2f" , $_W['wlmember']['credit1']);
        $data['integralsxf'] = $_W['wlsetting']['creditset']['integralsxf'];
        $this->renderSuccess('页面初始化信息',$data);
    }

    /**
     * Comment: 074定制-积分转赠接口
     * Author: wlf
     * Date: 2022/01/06 10:17
     */
    public function integralApi(){
        global $_W, $_GPC;
        $integra = trim($_GPC['credit']);
        $pwd = trim($_GPC['pwd']);
        $mobile = trim($_GPC['mobile']);
        $member = pdo_get('wlmerchant_member',array('id' => $_W['mid']),array('integralpwd','nickname'));
        if($pwd != $member['integralpwd']){
            $this->renderError('支付密码错误，请重新输入');
        }
        $getmember = pdo_get(PDO_NAME.'member',array('uniacid'=>$_W['uniacid'],'mobile'=> $mobile),['id','nickname']);
        if(empty($getmember)){
            $this->renderError('查无此用户');
        }
        if($getmember['id'] == $_W['mid']){
            $this->renderError('请勿转赠给自己');
        }
        if($integra > 0){
            //计算手续费
            $serviceCharge = sprintf("%.2f" ,$integra*$_W['wlsetting']['creditset']['integralsxf']/100);
            $allcredit = $serviceCharge + $integra;
            if($allcredit > $_W['wlmember']['credit1']){
                $this->renderError('剩余积分不足，请重新输入转赠数量');
            }else{
                Member::credit_update_credit1($_W['mid'] , -$integra , '转赠积分给用户[' . $getmember['nickname'].']');
                if($serviceCharge > 0){
                    Member::credit_update_credit1($_W['mid'] , -$serviceCharge , '转赠积分手续费扣除');
                }
                Member::credit_update_credit1($getmember['id'] , $integra , '用户[' . $member['nickname'].']给您赠送积分');
                $this->renderSuccess('操作成功');
            }
        }else{
            $this->renderError('转赠积分数量错误，请重新输入');
        }
    }


    /**
     * Comment: 074定制-支付密码修改接口
     * Author: wlf
     * Date: 2022/01/24 10:28
     */
    public function integralpwd(){
        global $_W, $_GPC;
        $mobile = pdo_getcolumn(PDO_NAME.'member',array('id'=>$_W['mid']),'mobile');
        $pwd = $_GPC['pwd'];
        $code = $_GPC['code'];
        $pin_info = pdo_get('wlmerchant_pincode' , ['mobile' => $mobile]);
        if (empty($pin_info)) {
            $this->renderError('验证码错误');
        }
        if ($pin_info['time'] < time() - 300) {
            $this->renderError('验证码已过期，请重新获取' , ['code' => 1]);
        }
        if ($code != $pin_info['code']) {
            $this->renderError('验证码错误');
        }
        $res = pdo_update('wlmerchant_member',array('integralpwd' => $pwd),array('id' => $_W['mid']));
        $this->renderSuccess('修改成功');
    }

    /**
     * Comment: 用户注销接口
     * Author: wlf
     * Date: 2022/04/08 16:00
     */
    public function cancellation(){
        global $_W, $_GPC;
        if(empty($_W['mid'])){
            $this->renderError('用户错误，无法注销');
        }
        $res = pdo_delete('wlmerchant_member',array('id'=>$_W['mid']));
        if($res){
            $this->renderSuccess('注销成功');
        }else{
            $this->renderError('注销失败，请刷新重试');
        }

    }

}
