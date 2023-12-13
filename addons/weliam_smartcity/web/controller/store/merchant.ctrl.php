<?php
defined('IN_IA') or exit('Access Denied');

class Merchant_WeliamController {
    public function index() {
        global $_W, $_GPC;
        //校验商户入口文件
        $citystore = IA_ROOT . '/web/citystore.php';
        $mcitystore = PATH_MODULE . '/web/citystore.php';
        if (!file_exists($citystore) || md5_file($citystore) != md5_file($mcitystore)) {
            copy($mcitystore, $citystore);
        }

        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = array('uniacid' => $_W['uniacid'], 'status' => 2, 'aid' => $_W['aid']);
        $deliverystatus = $_GPC['deliverystatus'] ? : 0;
        $isExport = $_GPC['export'] ? : 0;
        if($_W['aid'] > 0){
            $groups = pdo_fetchall("SELECT id,name FROM " . tablename('wlmerchant_chargelist') . "WHERE uniacid = {$_W['uniacid']} AND aid IN (0,{$_W['aid']}) AND status = 1 ORDER BY sort DESC,id DESC", [], 'id');
        }else{
            $groups = pdo_fetchall("SELECT id,name FROM " . tablename('wlmerchant_chargelist') . "WHERE uniacid = {$_W['uniacid']} AND aid = 0 AND status = 1 ORDER BY sort DESC,id DESC", [], 'id');
        }
        if ($_GPC['keyword']) {
            if ($_GPC['keywordtype'] == 1) {
                $where['id'] = trim($_GPC['keyword']);
            } else if ($_GPC['keywordtype'] == 2) {
                $where['@storename@'] = trim($_GPC['keyword']);
            } else if ($_GPC['keywordtype'] == 3) {
                $where['@realname@'] = trim($_GPC['keyword']);
            } else if ($_GPC['keywordtype'] == 4) {
                $where['mobile^tel'] = trim($_GPC['keyword']);
            } else if ($_GPC['keywordtype'] == 5) {
                $sid = pdo_getcolumn(PDO_NAME.'merchantuser',array('id'=>trim($_GPC['keyword'])),'storeid');
                $where['id'] = trim($sid);
            }
        }
        if ($_GPC['groupid']) {
            $where['groupid'] = $_GPC['groupid'];
        }
        if ($_GPC['enabled'] == 5) {
            $where['enabled'] = 0;
            $where['status'] = 1;
        } elseif ($_GPC['enabled'] == 6) {
            $where['enabled'] = 0;
        } else {
            $where['enabled'] = $_GPC['enabled'] = $_GPC['enabled'] != '' ? $_GPC['enabled'] : 1;
        }

        if (!empty($_GPC['time_limit']) && $_GPC['timetype'] ) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            if($_GPC['timetype'] == 1){
                $where['createtime>'] = $starttime;
                $where['createtime<'] = $endtime + 86400;
            }else{
                $where['endtime>'] = $starttime;
                $where['endtime<'] = $endtime + 86400;
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        if($deliverystatus > 0) $where['deliverystatus'] = $deliverystatus == 2 ? 0 : $deliverystatus;
        //判断是否为导出操作
        if($isExport == 1){
            $whereData = Util::createStandardWhereString($where);
            foreach($whereData[1] as $key => $icon){
                $whereData[0] = str_replace($key,"'{$icon}'",$whereData[0]);
            }
            Store::exportShop($whereData[0]);
            die;
        }
        $field = "id,logo,storename,mobile,nowmoney,realname,tel,createtime,endtime,enabled,nowmoney,groupid";
        $storesData = Util::getNumData($field, PDO_NAME . 'merchantdata', $where, 'listorder desc,id desc', $pindex, $psize, 1);
        $stores = $storesData[0];
        foreach ($stores as $key => &$value) {
            $value['logo'] = tomedia($value['logo']);
            //店铺过期操作
            if ($_GPC['enabled'] != 4) {
                if ($value['endtime'] < time() && !empty($value['endtime'])) {
                    $res = pdo_update(PDO_NAME . 'merchantdata', array('enabled' => 3), array('uniacid' => $_W['uniacid'], 'id' => $value['id']));
                    if ($res) {  //下架商品
                        //抢购商品
                        pdo_update('wlmerchant_rush_activity', array('status' => 4), array('uniacid' => $_W['uniacid'], 'sid' => $value['id']));
                        //拼团商品
                        pdo_update('wlmerchant_fightgroup_goods', array('status' => 0), array('uniacid' => $_W['uniacid'], 'merchantid' => $value['id']));
                        //卡券
                        pdo_update('wlmerchant_couponlist', array('status' => 0), array('uniacid' => $_W['uniacid'], 'merchantid' => $value['id']));
                        //特权
                        pdo_update('wlmerchant_halfcardlist', array('status' => 0), array('uniacid' => $_W['uniacid'], 'merchantid' => $value['id']));
                        //礼包
                        pdo_update('wlmerchant_package', array('status' => 0), array('uniacid' => $_W['uniacid'], 'merchantid' => $value['id']));
                    }
                }
            }
            //查询店员
            $value['clerknum'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_merchantuser') . " WHERE storeid = {$value['id']} AND ismain != 1");
            $value['groupname'] = $value['groupid'] ? pdo_getcolumn('wlmerchant_chargelist', array('id' => $value['groupid']), 'name') : '';
        }
        $pager = $storesData[1];
        $status0 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'merchantdata') . " WHERE enabled = 0 and uniacid={$_W['uniacid']} and status=2 and aid={$_W['aid']}");
        $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'merchantdata') . " WHERE enabled = 1 and uniacid={$_W['uniacid']} and status=2 and aid={$_W['aid']}");
        $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'merchantdata') . " WHERE enabled = 2 and uniacid={$_W['uniacid']} and status=2 and aid={$_W['aid']}");
        $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'merchantdata') . " WHERE enabled = 3 and uniacid={$_W['uniacid']} and status=2 and aid={$_W['aid']}");
        $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'merchantdata') . " WHERE enabled = 4 and uniacid={$_W['uniacid']} and status=2 and aid={$_W['aid']}");
        $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'merchantdata') . " WHERE enabled = 0 and uniacid={$_W['uniacid']} and status=1 and aid={$_W['aid']}");

        include wl_template('store/userIndex');
    }

    public function edit() {
        global $_W, $_GPC;
        $isAuth = Customized::init('printing');
        $id = intval($_GPC['id']);
        if (is_store()) {
            $id = $_W['storeid'];
        }
        $page = $_GPC['page'];
        $allgroup = pdo_fetchall("SELECT name,id FROM " . tablename('wlmerchant_chargelist') . "WHERE uniacid = {$_W['uniacid']} AND aid IN (0,{$_W['aid']}) AND status = 1 ORDER BY sort DESC");
        //海报获取
        if(p('diyposter')){
            $posters = pdo_getall(PDO_NAME . 'poster',array('uniacid' => $_W['uniacid'],'type' => 1),array('id','title'));
        }
        //社群获取
        $community =  pdo_fetchall("SELECT 	communname,id FROM " . tablename('wlmerchant_community') . "WHERE uniacid = {$_W['uniacid']} AND storeid IN (0,{$id}) AND aid = {$_W['aid']} ORDER BY id DESC");
        //团长等级
        $grouplevel = pdo_getall('wlmerchant_grouplevel', array('uniacid' => $_W['uniacid']),['id','name']);
		//商户红包
		$storeRedList = pdo_getall('wlmerchant_store_redpack', array('uniacid' => $_W['uniacid'],'status' => 1),['id','title']);

//        $categoryes = pdo_getall(PDO_NAME . 'category_store', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid'], 'state' => '0')); //商家分类
//        if (empty($categoryes)) wl_message("请先添加商家类别!", web_url('store/category/Edit'));
//        $moduels = uni_modules();
//        if (!empty($categoryes)) {
//            $parents = $childrens = array();
//            foreach ($categoryes as $cid => $cate) {
//                if (!empty($cate['parentid'])) {
//                    $childrens[$cate['parentid']][] = $cate;
//                } else {
//                    $parents[$cate['id']] = $cate;
//                }
//            }
//        }
        //一级分类获取
        $categoryes = pdo_getall(PDO_NAME . 'category_store' , [
            'uniacid' => $_W['uniacid'] ,
            'aid'     => $_W['aid'] ,
            'enabled' => 1,
            'parentid'=> 0,
            'state'   => '0'
        ],['id','name'],'','displayorder DESC');
        //二级分类信息获取
        foreach($categoryes as $key => &$val){
            $val['list'] = pdo_getall(PDO_NAME . 'category_store' , [
                'uniacid' => $_W['uniacid'] ,
                'aid'     => $_W['aid'] ,
                'enabled' => 1,
                'parentid'=> $val['id'],
                'state'   => '0'
            ],['id','name'],'','displayorder DESC');
            //兼容 删除没有二级分类的一级分类信息
            if(!$val['list']){
                unset($categoryes[$key]);
            }
        }
        //商户分类
        $levelids = pdo_getall('wlmerchant_merchant_cate', array('sid' => $id), 'twolevel');
        if (!empty($levelids)) {
            $cateids = array_column($levelids, 'twolevel');
        }
        //家政服务分类
        $keeplevelids = pdo_getall('wlmerchant_housekeep_relation', array('objid' => $id,'type' => 3), 'twolevelid');
        if (!empty($keeplevelids)) {
            $keepcateids = array_column($keeplevelids, 'twolevelid');
        }
        if ($id) {
            $register = Store::getSingleStore($id);
            $proportion = unserialize($register['proportion']);

            if(!empty($register['grouparray'])){
                $grouparray = unserialize($register['grouparray']);
            }

        }else{
            $register['aid'] = $_W['aid'];
        }
        //获取云喇叭设置信息
        if ($register['cloudspeaker']) {
            $cloudspeaker = unserialize($register['cloudspeaker']);
        }
        //获取推送设置信息
        if ($register['printing']) {
            $printing = unserialize($register['printing']);
        }

        if ($id) {
            $register['member'] = Store::getSingleRegister(array('uniacid' => $_W['uniacid'], 'storeid' => $id, 'ismain' => 1));
        }
        $member = Member::wl_member_get($register['member']['mid'], array('id', 'nickname', 'openid'));
        $register['location'] = unserialize($register['location']);
        //$register['location'] = Util::Convert_GCJ02_To_BD09($register['location']['lat'], $register['location']['lng']);
        $register['endtime'] = $register['endtime'] ? $register['endtime'] : time() + 31536000;
        $register['adv'] = unserialize($register['adv']);
        $register['recruit_adv'] = unserialize($register['recruit_adv']);
        $register['delivery_adv'] = unserialize($register['delivery_adv']);
        $register['album'] = unserialize($register['album']);
        $register['examineimg'] = unserialize($register['examineimg']);
        $register['videourl'] = tomedia($register['videourl']);
        $deliverytype = unserialize($register['deliverytype']);
        $allArea = json_encode(Area::get_all_address());
        $sett = unserialize($register['settlementtext']);
        $payback = unserialize($register['payback']);
        //活动时间处理
        $storehours = unserialize($register['storehours']);
        if(!empty($storehours['startTime'])){
            $newstorehours = [];
            $houreinfo['startTime'] = $storehours['startTime'];
            $houreinfo['endTime'] = $storehours['endTime'];
            $newstorehours[] = $houreinfo;
            $storehours = $newstorehours;
        }

        //评分等级
        $on_array = array();
        $off_array = array();
        if (empty($register['score'])) $register['score'] = 5;
        if ($register['score'] == 1) {
            $html = '<span class="label label-default">非常差</span>';
        } else if ($register['score'] == 2) {
            $html = '<span class="label label-warning">不太好</span>';
        } else if ($register['score'] == 3) {
            $html = '<span class="label label-info">一般</span>';
        } else if ($register['score'] == 4) {
            $html = '<span class="label label-success">很好!</span>';
        } else if ($register['score'] == 5) {
            $html = '<span class="label label-danger">非常棒!!</span>';
        }
        for ($i = 1; $i <= $register['score']; $i++) {
            $on_array[$i] = $i;
        }
        for ($j = $register['score']; $j < 5; $j++) {
            $off_array[$j] = $j + 1;
        }
        //标签
        $presettags = pdo_getall('wlmerchant_tags', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid'], 'type' => 0), array('id', 'title'), '', 'sort DESC');
        $tags = unserialize($register['tag']);
        $userlabels = pdo_getall('wlmerchant_userlabel', array('uniacid' => $_W['uniacid']), array('id', 'name'), '', 'sort DESC');
        $storeuserlabels = unserialize($register['payonlinelabel']);
        //酒店标签
        $hotellabel = pdo_getall('wlmerchant_hotel_label', array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'type' => 3), array('id', 'title'), '', 'sort DESC,id DESC');
        $sthotellabel = unserialize($register['hotellabel']);
        //店员
        if ($id) {
            $mains = pdo_getall('wlmerchant_merchantuser', array('uniacid' => $_W['uniacid'], 'storeid' => $id));
            foreach ($mains as $key => &$main) {
                $main['avatar'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $main['mid']), 'avatar');
            }
        }
        //满减活动
        if(p('fullreduce')){
            $fullreducelist = pdo_getall('wlmerchant_fullreduce_list',array('aid' => $_W['aid'],'uniacid' => $_W['uniacid'],'status' => 1),array('id','title'));
        }
        //支付有礼
        if(p('paidpromotion')){
            $paidlist = pdo_getall('wlmerchant_payactive',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));
        }
        if(agent_p('luckydraw')){
            $drawlist = pdo_getall('wlmerchant_luckydraw',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));
        }
        //支付信息列表
        if($_W['wlsetting']['cashset']['allocationtype'] == 1){
            $paylist = pdo_getall(PDO_NAME."payment",['uniacid'=>$_W['uniacid'],'type'=>1],['id','name']);
        }
        //配送设置
        $deliveset = Setting::agentsetting_read('citydelivery');
        $apiset = Setting::wlsetting_read('api');
        $apiset = $apiset['citydelivery'];
        if($deliveset['type'] > 0){
        	if($deliveset['type'] == 3){
        		$apiset['type'] = 0;
        	}else{
        		$apiset['type'] = $deliveset['type'];
        	}
        }

        $logo = $register['logo'];
        if (checksubmit('submit')) {
            //表单提交
            //判断是否修改了店长
            if ($member['id'] != $_GPC['storemid']) {
                $noticeflag = 1;
            }
            $uid = intval($_GPC['uid']);
            if (!empty($_GPC['storemid'])) {
                $member['id'] = $_GPC['storemid'];
            }
            $register = $_GPC['register'];
            if(empty($register['groupid']) && !is_store()){
                wl_message('请设置商户所属套餐');
            }
            $register['wxappswitch'] = intval($_GPC['wxappswitch']);
            $register['iscommon'] = intval($_GPC['iscommon']);
            //$register['location'] = Util::Convert_BD09_To_GCJ02($register['location']['lat'], $register['location']['lng']);
            $register['lng'] = $register['location']['lng'];
            $register['lat'] = $register['location']['lat'];
            $register['location'] = serialize($register['location']);
            $register['adv'] = serialize($register['adv']);
            $register['recruit_adv'] = serialize($register['recruit_adv']);
            $register['delivery_adv'] = serialize($register['delivery_adv']);
            $register['album'] = serialize($register['album']);
            $register['tag'] = serialize($register['tag']);
            $register['storename'] = trim($register['storename']);
            if (!empty($register['endtime'])) {
                $register['endtime'] = strtotime($register['endtime']);
            }
            $register['introduction'] = htmlspecialchars_decode($register['introduction']);
            $register['uniacid'] = $_W['uniacid'];
            if(empty($register['aid']) && is_store()){
                $register['aid'] = $_W['aid'];
            }
            $user['name'] = $register['realname'];
            $user['mobile'] = $register['tel'];
            if (!empty($_GPC['storemid'])) {
                $user['mid'] = $member['id'];
            }
            $user['enabled'] = 1;
            $user['uniacid'] = $_W['uniacid'];
            $user['aid'] = $register['aid'];
            //轮播图大小数组
            $register['proportion'] = serialize($_GPC['proportion']);
            //营业时间段
            $startTime = $_GPC['startTime'];
            $endTime = $_GPC['endTime'];
            if(empty($startTime)){
                wl_message('请设置营业时间');
            }
            foreach($startTime as $tk => $stime){
                $sdate = [];
                $sdate['startTime'] = $startTime[$tk];
                $sdate['endTime'] = $endTime[$tk];
                $registerdate[] = $sdate;
            }
            $register['storehours'] = serialize($registerdate);
            //达达和UU城市列表
            if($apiset['type'] == 1){
                //达达设置
                $third_city_info = $_GPC['third_city_info'];
                $third_city_info = explode( ",",$third_city_info);
                $register['third_city_code'] = $third_city_info[0];
                $register['third_city_name'] = $third_city_info[1];
            }else if($apiset['type'] == 2){
                //UU设置
                $register['third_city_name'] = $_GPC['third_city_name'];
            }
            //结算设置
            $sett = $_GPC['sett'];
            foreach ($sett as $key => &$se) {
                if ($se > 100) {
                    $se = 100;
                }
                if ($se > 0.01) {
                    $se = sprintf("%.2f", $se);
                }
            }
            $register['settlementtext'] = serialize($sett);
            //支付返现(L304定制)
            if($register['paybackstatus'] > 0){
                $payback = $_GPC['payback'];
                foreach ($payback as $key => &$pa) {
                    if ($pa > 100) {
                        $pa = 100;
                    }
                    if ($pa > 0.01) {
                        $pa = sprintf("%.2f", $pa);
                    }
                }
                $register['payback'] = serialize($payback);
            }
            $register['yuecashback'] = $register['yuecashback'] > 100 ? 100 : sprintf("%.2f", $register['yuecashback']);
            $register['vipyuecashback'] = $register['vipyuecashback'] > 100 ? 100 : sprintf("%.2f", $register['vipyuecashback']);
            //同城配送
            if(empty($register['deliverytype'])){
                $register['deliverytype'][] = 'own';
            }
            $register['deliverytype'] = serialize($register['deliverytype']);
            //云喇叭设置信息
            $cloudspeaker = $_GPC['cloudspeaker'];
            if ($cloudspeaker) {
                $cloudspeaker['volume'] = $cloudspeaker['volume'] ? $cloudspeaker['volume'] : 50;
            }
            $register['cloudspeaker'] = serialize($cloudspeaker);
            //消息推送设置
            $register['printing'] = serialize($_GPC['printing']);
            //用户标签
            $userlabel = $_GPC['userlabel'];
            $register['payonlinelabel'] = serialize($userlabel);
            //酒店标签
            $sshotellabel = $_GPC['register']['hotellabel'];
            $register['hotellabel'] = serialize($sshotellabel);

            if($register['disgroup'] > 0){
                $grouparray = [];
                $groupleid = $_GPC['groupleid'];
                $onegroupmoney = $_GPC['onegroupmoney'];
                $twogroupmoney = $_GPC['twogroupmoney'];
                foreach($groupleid as $gkey => $gle){
                    $glea['onegroupmoney'] = sprintf("%.2f",$onegroupmoney[$gkey]);
                    $glea['twogroupmoney'] = sprintf("%.2f",$twogroupmoney[$gkey]);
                    $grouparray[$gle] = $glea;
                }
                $register['grouparray'] = serialize($grouparray);
            }

            if ($id) {
                //修改店铺信息
                $result = Store::registerEditData($register, $id);
                if (empty($uid)) {
                    $user['storeid'] = $id;
                    $user['ismain'] = 1;
                    $user['status'] = 2;
                    $user['createtime'] = time();
                }
                $result2 = Store::registerEditUser($user, $uid);
                //修改店铺信息  判断是否修改aid 并且进行对应的操作
                self::updateGoodsAid($id,$register['aid']);//aid被改变  修改对应信息
            } else {
                $register['aid'] = $_W['aid'];
                $register['status'] = 2;
                $register['createtime'] = time();
                $uid = Store::registerEditData($register);
                $user['storeid'] = $uid;
                $user['ismain'] = 1;
                $user['status'] = 2;
                $user['createtime'] = time();
                $result = Store::registerEditUser($user);
                $id = $uid;
            }
            if ($result) {
                if (!empty($id)) {
                    //处理分类
                    pdo_delete('wlmerchant_merchant_cate', array('sid' => $id));
                    foreach ($_GPC['category'] as $item) {
                        $scate = pdo_get(PDO_NAME . 'category_store', array('id' => $item), array('parentid'));
                        pdo_insert('wlmerchant_merchant_cate', ['sid' => $id, 'onelevel' => $scate['parentid'], 'twolevel' => $item]);
                    }
                    //处理家政服务分类
                    if($register['housekeepstatus'] > 0){
                        $housekeepcate = $_GPC['housekeepcate'];
                        pdo_delete('wlmerchant_housekeep_relation', array('type' => 3,'objid' => $id));
                        foreach ($housekeepcate as $keepitem) {
                            $keepscate = pdo_get(PDO_NAME . 'housekeep_type', array('id' => $keepitem), array('onelevelid'));
                            pdo_insert('wlmerchant_housekeep_relation', ['type' => 3,'objid' => $id, 'onelevelid' => $keepscate['onelevelid'], 'twolevelid' => $keepitem]);
                        }
                    }

                    $enabled = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $id), 'enabled');
                    if ($enabled != 1) {
                        //抢购商品
                        pdo_update('wlmerchant_rush_activity', array('status' => 4), array('uniacid' => $_W['uniacid'], 'sid' => $id));
                        //拼团商品
                        pdo_update('wlmerchant_fightgroup_goods', array('status' => 5), array('uniacid' => $_W['uniacid'], 'merchantid' => $id));
                        //卡券
                        pdo_update('wlmerchant_couponlist', array('status' => 3), array('uniacid' => $_W['uniacid'], 'merchantid' => $id));
                        //特权
                        pdo_update('wlmerchant_halfcardlist', array('status' => 0), array('uniacid' => $_W['uniacid'], 'merchantid' => $id));
                        //礼包
                        pdo_update('wlmerchant_package', array('status' => 0), array('uniacid' => $_W['uniacid'], 'merchantid' => $id));
                        //砍价
                        pdo_update('wlmerchant_bargain_activity', array('status' => 0), array('sid' => $id));
                        //同城配送
                        pdo_update('wlmerchant_delivery_activity', array('status' => 4), array('sid' => $id));
                    }
                }
                if ($noticeflag) {
                    $usermid = $_GPC['storemid'];
                    $firse = '您已被管理员设置为[' . $register['storename'] . ']的店长';
                    $content = '商户名:' . $register['storename'];
                    $link = h5_url('pages/subPages/merchant/merchantChangeShop/merchantChangeShop');
                    News::jobNotice($usermid, $firse, '绑定店长通知', $content, '已绑定', '点击链接进入商户管理页面', time(),$link);
                }
                if ($register['logo'] != $logo) {
                    Tools::clearwxapp();
                    Tools::clearposter();
                }
                if (is_store()) {
                    wl_message('商家信息保存成功', web_url('store/merchant/edit'), 'success');
                } else {
                    if($_GPC['houseflag'] > 0){
                        wl_message('商家信息保存成功', web_url('housekeep/KeepWeb/storelist'), 'success');
                    }else{
                        wl_message('商家信息保存成功', web_url('store/merchant/index', array('enabled' => $register['enabled'], 'page' => $page)), 'success');
                    }
                }
            } else {
                if (is_store()) {
                    wl_message('商家信息保存失败，请重试', web_url('store/merchant/edit'), 'error');
                } else {
                    wl_message('商家信息保存失败，请重试', web_url('store/merchant/edit', array('id' => $id)), 'error');
                }
            }
        }
        //获取代理商列表
        $default[] = ['id' => 0,'agentname' => '总后台'];
        $agentList = pdo_getall(PDO_NAME."agentusers",['uniacid'=>$_W['uniacid']],['id','agentname']);
        $agentList = array_merge($default,$agentList);
        //同城配送权限判断
        if(p('citydelivery')){
            $apps = App::get_account_perm();
            if(!empty($apps['plugins'])){
                if(in_array('citydelivery',$apps['plugins'])){
                    $citydefl = 1;
                }
            }else{
                $citydefl = 1;
            }
        }
        //求职招聘相关信息
        if(p('recruit')){
            $nature = Recruit::getLabelList(5);//企业性质标签
            $scale = Recruit::getLabelList(4);//企业规模标签
            $industry = Recruit::getIndustryList(['pid'=>0],['id','title']);//上级行业列表
        }
        //达达和UU城市列表
        if($apiset['type'] == 1){
            $city_code_list = Citydelivery::postDadaApi('',8);
            $city_code_list = $city_code_list['result'];
        }else if($apiset['type'] == 2){
            $city_code_list = Citydelivery::postUUApi([],8);
            $city_code_list = $city_code_list['CityList'];
        }
        //家政服务相关信息
        if(p('housekeep')){
            $housekeepcate = Housekeep::getCategory();
        }

        include wl_template('store/userEdit');
    }

    public function detelemain() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $ismain = pdo_getcolumn(PDO_NAME . 'merchantuser', array('id' => $id), 'ismain');
        if ($ismain == 1) {
            show_json(0, '不能删除店长');
        } else {
            $res = pdo_delete('wlmerchant_merchantuser', array('id' => $id));
        }
        if ($res) {
            show_json(1, '删除成功');
        } else {
            show_json(0, '删除失败，请重试');
        }
    }

    public function urlindex() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $register = Store::getSingleStore($id);
        $naves = Dashboard::getAllNav($pindex - 1, $psize, '', $id);
        $navs = $naves['data'];
        $pager = wl_pagination($naves['count'], $pindex, $psize);

        include wl_template('store/userEdit');
    }

    public function addurl() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $register = Store::getSingleStore($id);

        if (!empty($_GPC['navid'])) $nav = Dashboard::getSingleNav($_GPC['navid']);
        if (checksubmit('submit')) {
            $nav = $_GPC['nav'];
            $nav['name'] = trim($nav['name']);
            $nav['displayorder'] = intval($nav['displayorder']);
            $nav['enabled'] = intval($_GPC['enabled']);
            if (!empty($_GPC['navid'])) {
                if (Dashboard::editNav($nav, $_GPC['navid'])) wl_message('保存成功', web_url('store/merchant/urlindex', array('id' => $nav['merchantid'])), 'success');
            } else {
                if (Dashboard::editNav($nav)) wl_message('保存成功', web_url('store/merchant/urlindex', array('id' => $nav['merchantid'])), 'success');
            }
            wl_message('保存失败', referer(), 'error');
        }

        include wl_template('store/userEdit');
    }

    public function delete() {
        global $_W, $_GPC;
        if (pdo_update(PDO_NAME . 'merchantdata', array('enabled' => 4), array('id' => $_GPC['id']))) {
            pdo_delete('wlmerchant_storefans', array('sid' => $_GPC['id']));
            //抢购商品
            pdo_update('wlmerchant_rush_activity', array('status' => 4), array('uniacid' => $_W['uniacid'], 'sid' => $_GPC['id']));
            //拼团商品
            pdo_update('wlmerchant_fightgroup_goods', array('status' => 0), array('uniacid' => $_W['uniacid'], 'merchantid' => $_GPC['id']));
            //卡券
            pdo_update('wlmerchant_couponlist', array('status' => 0), array('uniacid' => $_W['uniacid'], 'merchantid' => $_GPC['id']));
            //特权
            pdo_update('wlmerchant_halfcardlist', array('status' => 0), array('uniacid' => $_W['uniacid'], 'merchantid' => $_GPC['id']));
            //礼包
            pdo_update('wlmerchant_package', array('status' => 0), array('uniacid' => $_W['uniacid'], 'merchantid' => $_GPC['id']));
            show_json(1, '删除成功');
        } else {
            show_json(0, '删除失败，请重试');
        }
    }

    function deletes() {
        global $_W, $_GPC;
        $ids = $_GPC['ids'];
        $type = $_GPC['type'];
        if ($type == 1) {
            foreach ($ids as $key => $id) {
                pdo_update(PDO_NAME . 'merchantdata', array('enabled' => 4), array('id' => $id));
                pdo_delete('wlmerchant_storefans', array('sid' => $id));
            }
        } else {
            foreach ($ids as $key => $id) {
                pdo_delete(PDO_NAME . 'merchantdata', array('id' => $id));
                pdo_delete(PDO_NAME . 'merchantuser', array('uniacid' => $_W['uniacid'], 'storeid' => $id));
            }
        }

        die(json_encode(array('errno' => 0, 'message' => '', 'id' => '')));
    }

    public function sureDelete() {
        global $_W, $_GPC;
        if (pdo_delete(PDO_NAME . 'merchantdata', array('id' => $_GPC['id']))) {
            pdo_delete(PDO_NAME . 'merchantuser', array('uniacid' => $_W['uniacid'], 'storeid' => $_GPC['id']));
            show_json(1, '删除成功');
        }
        show_json(0, '删除失败，请重试');
    }

    /**
     * 函数的含义说明
     *
     * @access public
     * @name 方法名称
     * @param mixed  参数一的说明
     * @return array
     */
    function keeper() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $register = Store::getSingleStore($id);
        $register['onelevel'] = Util::idSwitch('cateParentId', 'cateParentName', $register['onelevel']);
        $register['twolevel'] = Util::idSwitch('cateChildId', 'cateChildName', $register['twolevel']);

        $where['storeid'] = $id;
        $keeperData = Util::getNumData("*", PDO_NAME . 'merchantuser', $where, 'ismain asc');
        $keeper = $keeperData[0];
        foreach ($keeper as $key => &$value) {
            $value['member'] = Member::wl_member_get($value['mid']);
        }
        include wl_template('store/userEdit');
    }

    /**
     * Comment: 代理后台进行店铺余额提现申请的操作
     * Author: zzw
     */
    public function cash() {
        global $_W, $_GPC;
        #1、获取基本参数信息
        $sid = $_GPC['sid'];//商户id
        $money = $_GPC['money'];//提现金额
        $cashsets = Setting::wlsetting_read('cashset');//提现设置
        $agent = Area::getSingleAgent($_W['aid']);
        $syssalepercent = $agent['percent']['syssalepercent'];//系统抽成比例
        if (empty($syssalepercent)) {
            $syssalepercent = sprintf("%.2f" , $_W['wlsetting']['cashset']['syssalepercent']);
        }else {
            $syssalepercent = sprintf("%.2f" , $syssalepercent);
        }
        $userInfo = Store::getShopOwnerInfo($sid, $_W['aid']);//收款人信息
        if(!empty($userInfo['openid'])){
            $sopenid = $userInfo['openid'];
            $source = 1;
        }else if(!empty($userInfo['wechat_openid'])){
            $sopenid = $userInfo['wechat_openid'];
            $source = 3;
        }else{
            wl_json(0, '店长无微信打款信息，无法结算');
        }
        #2、判断商家的提现时间限制
        $shopIntervalTime = $cashsets['shopIntervalTime'];
        if ($shopIntervalTime > 0) {
            //获取上次提现申请时间
            $startTime = pdo_fetchcolumn("SELECT applytime FROM "
                . tablename(PDO_NAME . "settlement_record")
                . " WHERE sid = {$sid} AND uniacid = {$_W['uniacid']} ORDER BY applytime DESC ");
            $interval = time() - $startTime;
            $intervalDay = $interval / 3600 / 24;
            //判断间隔时间
            $intercalRes = ceil($shopIntervalTime - $intervalDay);
            if ($intercalRes > 0) {
                wl_json(0, '请等' . $intercalRes . '天后再申请！');
            }
        }
        #3、判断提现金额限制
        if ($money < $cashsets['lowsetmoney']) {
            wl_json(0, '申请失败，最低提现金额为' . $cashsets['lowsetmoney'] . '元。');
        }
        #4、拼装提现信息
        $data = array(
            'uniacid'       => $_W['uniacid'],
            'sid'           => $sid,
            'aid'           => $_W['aid'],
            'status'        => 2,
            'type'          => 1,
            'sapplymoney'   => $money,
            'sgetmoney'     => sprintf("%.2f", $money - $syssalepercent * $money / 100),
            'spercentmoney' => sprintf("%.2f", $syssalepercent * $money / 100),
            'spercent'      => $syssalepercent,
            'applytime'     => TIMESTAMP,
            'updatetime'    => TIMESTAMP,
            'sopenid'       => $sopenid,
            'payment_type'  => 5,
            'source'        => $source
        );
        #5、保存提现信息
        if (pdo_insert(PDO_NAME . 'settlement_record', $data)) {
            $orderid = pdo_insertid();
            $res = Store::settlement($orderid, 0, $data['sid'], -$money, 0, -$money, 7, 0, 0, $_W['aid']);
            if ($res) {
                if (!empty($_W['wlsetting']['adminmid'])) {
                    $storename = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $sid), 'storename');
                    $first = '您好，有一个商户提现申请待审核。';
                    $content = '商户[' . $storename . ']申请提现' . $money . '元';
                    News::jobNotice($_W['wlsetting']['adminmid'], $first, '商户提现审核通知', $content, '待审核', '请尽快前往系统后台审核', time());
                }
                wl_json(1, '申请成功');
            } else {
                wl_json(0, '申请失败，请重试');
            }
        } else {
            wl_json(0, '申请失败，请重试');
        }
    }

    public function moneychange() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $balance = trim($_GPC['balance']);

        if ($_W['ispost']) {
            $moneychange = trim($_GPC['moneychange']);
            $moneynum = sprintf("%.2f", trim($_GPC['moneynum']));
            $remark = trim($_GPC['remark']);
            if ($moneynum < 0) {
                show_json(0, '变更金额错误');
            }
            if ($moneychange == 1) {
                $settlementmoney = $moneynum;
            } else {
                $settlementmoney = -$moneynum;
            }
            pdo_fetch("update" . tablename('wlmerchant_merchantdata') . "SET nowmoney = nowmoney + {$settlementmoney},allmoney = allmoney + {$settlementmoney} WHERE id = {$id}");
            $merchantnowmoney = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $id), 'nowmoney');
            Store::addcurrent(1, -1, $id, $settlementmoney, $merchantnowmoney, 0, $remark);
            show_json(1, '商户余额变更成功');
        }

        include wl_template('store/moneychange');
    }

    /**
     * Comment: 商户二维码生成
     * Author: zzw
     * Date: 2019/11/28 11:51
     */
    public function storeQrCode() {
        global $_W, $_GPC;
        #1、参数获取
        $id = $_GPC['id'] ? $_GPC['id'] : 0;
        $merchantuserid = pdo_getcolumn(PDO_NAME.'merchantuser',array('storeid'=>$id,'ismain'=>1),'mid');

        #2、基本链接生成
        $shopLink = h5_url('pages/mainPages/store/index', ['sid' => $id,'head_id' => $merchantuserid]);
        if(Customized::init('customized336')){
            $buyLink = h5_url('pages/mainPages/store/newBuyOrder/newBuyOrder', ['sid' => $id,'head_id' => $merchantuserid]);
        }else{
            $buyLink = h5_url('pages/subPages2/newBuyOrder/buyOrder', ['sid' => $id,'head_id' => $merchantuserid]);
        }
        $followLink = Storeqr::get_storeqr($id);//获取关注二维码
        #3、获取小程序码信息
        if (p('wxapp')) {
            $logo = pdo_getcolumn(PDO_NAME . "merchantdata", ['id' => $id], 'logo');
            $shopLink2 = "pages/mainPages/store/index?sid={$id}&head_id={$merchantuserid}";
            $wxappShopLink = Store::getShopWxAppQrCode('', tomedia($logo),$shopLink2);
            if(Customized::init('customized336')){
                $BuyLink = "pages/mainPages/store/newBuyOrder/newBuyOrder?sid={$id}&head_id={$merchantuserid}";
                $wxappBuyLink = Store::getShopWxAppQrCode('', tomedia($logo), $BuyLink);
            }else{
                $BuyLink = "pages/subPages2/newBuyOrder/buyOrder?sid={$id}&head_id={$merchantuserid}";
                $wxappBuyLink = Store::getShopWxAppQrCode('', tomedia($logo), $BuyLink);
            }
            //同城配送二维码
            $link = "pages/subPages2/businessCenter/foodList/foodList?id={$id}&storeid={$id}&head_id={$merchantuserid}";
            $wxappDistributionLink = Store::getShopWxAppQrCode('', tomedia($logo),$link);
        }
        //同城配送信息
        $distributionLink =  h5_url('pages/subPages2/businessCenter/foodList/foodList' , ['id' => $id,'storeid' => $id,'head_id' => $merchantuserid]);


        include wl_template('store/qrcode_store');
    }
    /**
     * Comment: 修改店铺商品信息
     * Author: zzw
     * Date: 2020/3/9 11:22
     * @param $sid
     * @param $aid
     */
    public static function updateGoodsAid($sid,$aid){
        #1、商品信息修改
        pdo_update(PDO_NAME."rush_activity",['aid'=>$aid],['sid'=>$sid]);//修改抢购商品
        pdo_update(PDO_NAME."groupon_activity",['aid'=>$aid],['sid'=>$sid]);//修改团购商品
        pdo_update(PDO_NAME."bargain_activity",['aid'=>$aid],['sid'=>$sid]);//修改砍价商品
        pdo_update(PDO_NAME."fightgroup_goods",['aid'=>$aid],['merchantid'=>$sid]);//修改拼团商品
        pdo_update(PDO_NAME."couponlist",['aid'=>$aid],['merchantid'=>$sid]);//修改卡卷商品
        pdo_update(PDO_NAME."activitylist",['aid'=>$aid],['sid'=>$sid]);//修改活动商品
        pdo_update(PDO_NAME."delivery_activity",['aid'=>$aid],['sid'=>$sid]);//修改活动商品
        #1、商品信息修改
        pdo_update(PDO_NAME."merchantuser",['aid'=>$aid],['storeid'=>$sid]);//修改店员信息
    }

    //店员列表
    public function clerkindex() {
        global $_W, $_GPC;
        $storeid = $_GPC['storeid'];
        if (is_store()) {
            $storeid = $_W['storeid'];
        }
        $storename = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $storeid), 'storename');
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = array('storeid' => $storeid, 'ismain!=' => 1);
        if (!empty($_GPC['keyword'])) {
            if ($_GPC['keywordtype'] == 1) {
                $where['name@'] .= trim($_GPC['keyword']);
            } else if ($_GPC['keywordtype'] == 2) {
                $where['mobile@'] .= trim($_GPC['keyword']);
            }
        }

        $clerkData = Util::getNumData("*", PDO_NAME . 'merchantuser', $where, 'id desc', $pindex, $psize, 1);
        $clerklist = $clerkData[0];
        $pager = $clerkData[1];

        foreach ($clerklist as &$li) {
            $member = pdo_get('wlmerchant_member', array('id' => $li['mid']), array('nickname', 'avatar'));
            $li['nickname'] = $member['nickname'];
            $li['avatar'] = tomedia($member['avatar']);
        }
        include wl_template('store/clerklist');
    }
    //店员编辑
    public function editclerk() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $storeid = $_GPC['storeid'];
        $store = pdo_get('wlmerchant_merchantdata',array('id' => $storeid),array('aid'));
        if ($id) {
            $clerk = pdo_get('wlmerchant_merchantuser', array('id' => $id));
            $member = pdo_get('wlmerchant_member', array('id' => $clerk['mid']), array('nickname', 'avatar'));
            $clerk['nickname'] = $member['nickname'];
            $clerk['avatar'] = $member['avatar'];
        }

        if ($_W['ispost']) {
            $data = array(
                'mid'        => $_GPC['memberid'],
                'name'       => $_GPC['name'],
                'mobile'     => $_GPC['mobile'],
                'ismain'     => $_GPC['ismain'],
                'orderwrite' => $_GPC['orderwrite'],
                'viewdata'   => $_GPC['viewdata'],
                'enabled'    => $_GPC['enabled']
            );
            if ($id) {
                $res = pdo_update('wlmerchant_merchantuser', $data, array('id' => $id));
            } else {
                $data['uniacid'] = $_W['uniacid'];
                $data['storeid'] = $_GPC['storeid'];
                $data['aid'] = $store['aid'];
                $data['createtime'] = time();
                //$data['enabled'] = $store['enabled'];
                $res = pdo_insert(PDO_NAME . 'merchantuser', $data);
            }
            if ($res) {
                show_json(1, '操作成功');
            } else {
                show_json(0, '操作失败,请重试');
            }
        }


        include wl_template('store/clerkedit');
    }
    //删除店员
    public function deleteclerk() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $ids = $_GPC['ids'];
        if (!empty($id)) {
            $res = pdo_delete('wlmerchant_merchantuser', array('id' => $id));
        }
        if (!empty($ids)) {
            foreach ($ids as $key => $id) {
                $res = pdo_delete('wlmerchant_merchantuser', array('id' => $id));
            }
        }
        if ($res) {
            show_json(1, '操作成功');
        } else {
            show_json(0, '操作失败,请重试');
        }
    }

    //时间段引入方法
    public function storehours() {
        include wl_template('store/storehours');
    }

    //获取可用店铺列表
    public function getstorelist(){
        global $_W, $_GPC;
        $where = "uniacid = {$_W['uniacid']} AND status = 2 AND enabled = 1 AND aid = {$_W['aid']}";
        $data = [];
        if (!empty($_GPC['search'])) {
            $where .= " AND (storename LIKE '%".trim($_GPC['search'])."%' or id  LIKE '%".trim($_GPC['search'])."%')";
        }else{
            $data[] = ['id' => 0, 'text' => '无关联商户'];
        }
        $members = pdo_fetchall("SELECT id,storename FROM ".tablename('wlmerchant_merchantdata')."WHERE {$where} ORDER BY id DESC LIMIT 100");

        foreach ($members as &$member) {
            $data[] = ['id' => $member['id'], 'text' => $member['storename'].'(SID:'.$member['id'].')'];
        }
        die(json_encode($data));
    }

}

