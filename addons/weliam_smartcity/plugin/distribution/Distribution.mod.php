<?php
defined('IN_IA') or exit('Access Denied');

class Distribution
{
    //增加分销商下级
    static function addJunior($invitid = '', $mid = '', $disflag = '', $rank = 1,$laterflag = 0)
    {
        global $_W;
        $disset = $_W['wlsetting']['distribution'];
        if ($disset['switch'] != 1 || empty($invitid) || empty($mid) || ($invitid == $mid)) {
            return FALSE;
        }
        MysqlFunction::setTrans(4);
        MysqlFunction::startTrans();
        $distributorid = pdo_getcolumn('wlmerchant_member', array('id' => $invitid), 'distributorid');
        $distributor = pdo_get('wlmerchant_distributor', array('id' => $distributorid));
        if ($distributor['disflag'] > 0) {
			//判断团长信息
			if($distributor['groupflag'] > 0){
				$onegroupid = $invitid;
				$twogroupid = $distributor['onegroupid'];
			}else{
				$onegroupid = $distributor['onegroupid'];
				$twogroupid = $distributor['twogroupid'];
			}
            $member = Member::wl_member_get($mid, array('distributorid', 'mobile', 'nickname', 'realname'));
            $distor = pdo_get(PDO_NAME . 'distributor', ['id' => $member['distributorid']]);
            if (empty($distor)) {
                $distor2 = pdo_get(PDO_NAME . 'distributor', ['mid' => $mid]);
                if(empty($distor2)){
                    pdo_delete('wlmerchant_distributor',array('mid'=>$mid));
                    $member = pdo_get('wlmerchant_member', array('id' => $mid), array('mobile', 'nickname', 'realname'));
                    $data = array(
                        'uniacid'    => $_W['uniacid'],
                        'aid'        => $_W['aid'],
                        'mid'        => $mid,
                        'createtime' => time(),
                        'disflag'    => 0,
                        'nickname'   => $member['nickname'],
                        'mobile'     => $member['mobile'],
                        'realname'   => $member['realname'],
                        'leadid'     => $invitid,
                        'onegroupid' => $onegroupid,
                        'twogroupid' => $twogroupid
                    );
                    //不锁死上下级
                    if ($disset['lockstatus'] == 1 || $disset['lockstatus'] == 3) {
                        $data['lockflag'] = 1;
                    }
					
                    pdo_insert('wlmerchant_distributor', $data);
                    $disid = pdo_insertid();
                    $res = pdo_update('wlmerchant_member', array('distributorid' => $disid), array('id' => $mid));
                }else{
                    pdo_update('wlmerchant_distributor', array('leadid' => $invitid,'onegroupid'=> $onegroupid,'twogroupid'=> $twogroupid),array('id' => $distor2['id']));
                    $res = pdo_update('wlmerchant_member', array('distributorid' => $distor2['id']), array('id' => $mid));
                }
            } else {
                if (empty($distor['leadid']) || ($disset['lockstatus'] == 2 && empty($distor['disflag'])) || $disset['temporary_sub'] > 0) {    //没有上级 或 动态上级
                    $data = array('leadid' => $invitid,'onegroupid'=> $onegroupid,'twogroupid'=> $twogroupid);
                    if ($disset['lockstatus'] == 1) {   //支付后再锁定上下级关系
                        $data['lockflag'] = 1;
                    }
                    $res = pdo_update('wlmerchant_distributor', $data, array('id' => $member['distributorid']));
                } else if ($distor['lockflag'] && ($disset['lockstatus'] == 1 || $disset['lockstatus'] == 3)) {   //未锁定的上级 更换上级
                    $res = pdo_update('wlmerchant_distributor', array('leadid' => $invitid,'onegroupid'=> $onegroupid,'twogroupid'=> $twogroupid), array('id' => $member['distributorid']));
                }
                $disid = $distor['id'];
            }
            MysqlFunction::commit();
            if ($res && empty($disset['lockstatus'])) {
                $url = h5_url('pages/subPages/dealer/client/client');
                Distribution::checkup($distributorid);
                if($disid > 0){
                    if($laterflag > 0){
                        $disvalue = array(
                            'mid'    => $distributor['mid'],
                            'url'    => $url,
                            'disid'  => $disid
                        );
                        $disvalue = serialize($disvalue);
                        Queue::addTask(8, $disvalue, time(), $disid);
                    }else{
                        Distribution::distriNotice($distributor['mid'], $url, 2, $disid);
                    }
                }
                if ($disset['ranknum'] > 1) {
                    $twodisid = pdo_getcolumn(PDO_NAME . 'distributor', array('mid' => $distributor['leadid']), 'id');
                    Distribution::checkup($twodisid);
                    if ($disset['noticerank2'] > 0 && $distributor['leadid'] > 0) {
                        if($laterflag > 0){
                            $disvalue = array(
                                'mid'    => $distributor['leadid'],
                                'url'    => $url,
                                'disid'  => $disid
                            );
                            $disvalue = serialize($disvalue);
                            Queue::addTask(8, $disvalue, time(), $disid);
                        }else{
                            Distribution::distriNotice($distributor['leadid'], $url, 2, $disid);
                        }
                    }
                }
            }
            if ($disflag) {
                $distor = pdo_get('wlmerchant_distributor', array('id' => $disid), array('disflag'));
                if (empty($distor['disflag'])) {
                    header('location:' . h5_url('pages/subPages/dealer/apply/apply'));
                }
            }
        }else{
            MysqlFunction::commit();    
        }
    }

    //分销助手
    static function getdishelp($goods, $type)
    {
        global $_W;
        $disset = Setting::wlsetting_read('distribution');
        $distributor = pdo_get('wlmerchant_distributor', array('mid' => $_W['mid'], 'disflag' => 1), array('id', 'dislevel'));
        if ($distributor['id']) {
            $disflag = 1;
        }
        if (empty($goods['isdistri'])) {
            if ($disset['helpstatus'] == 2 || ($disset['helpstatus'] == 1 && $distributor['id'])) {
                $helpflag = 1;
                $fxtext = $disset['fxtext'] ? $disset['fxtext'] : '分销';
                $xxtext = $disset['xxtext'] ? $disset['xxtext'] : '客户';
                $sjtext = $disset['sjtext'] ? $disset['sjtext'] : '上级';
                $yjtext = $disset['yjtext'] ? $disset['yjtext'] : '佣金';
                $fxstext = $disset['fxstext'] ? $disset['fxstext'] : '分销商';
                if ($goods['optionstatus']) {
                    if ($type == 'rush') {
                        $option = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_goods_option') . "WHERE uniacid = {$_W['uniacid']} AND type = 1 AND goodsid = {$goods['id']} ORDER BY onedismoney DESC");
                    } else if ($type == 'wlfightgroup') {
                        $option = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_goods_option') . "WHERE uniacid = {$_W['uniacid']} AND type = 2 AND goodsid = {$goods['id']} ORDER BY onedismoney DESC");
                    } else if ($type == 'groupon') {
                        $option = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_goods_option') . "WHERE uniacid = {$_W['uniacid']} AND type = 3 AND goodsid = {$goods['id']} ORDER BY onedismoney DESC");
                    }
                    $goods['onedismoney'] = $option['onedismoney'];
                    $goods['twodismoney'] = $option['twodismoney'];
                    $goods['price'] = $option['price'];
                }

                if ($goods['onedismoney'] > 0) {
                    if ($disset['mode']) {
                        $alldismoney = $goods['onedismoney'] + $goods['twodismoney'];
                    } else {
                        $alldismoney = $goods['onedismoney'];
                    }
                } else {
                    $commission = pdo_get('wlmerchant_dislevel', array('isdefault' => 1, 'uniacid' => $_W['uniacid']), array('onecommission', 'twocommission'));
                    if ($distributor['id']) {
                        if ($distributor['dislevel']) {
                            $commission = pdo_get('wlmerchant_dislevel', array('id' => $distributor['dislevel']), array('onecommission', 'twocommission'));
                        }
                    }
                    if ($disset['mode']) {
                        $allmax = $commission['onecommission'] + $commission['twocommission'];
                    } else {
                        $allmax = $commission['onecommission'];
                    }
                    $alldismoney = sprintf("%.2f", $allmax * $goods['price'] / 100);
                }
                if ($type == 'rush') {
                    $copyurl = h5_url('pages/subPages/goods/index', ['id=' => $goods['id'], 'type' => 1]);
                } else if ($type == 'groupon') {
                    $copyurl = h5_url('pages/subPages/goods/index', ['type' => 2, 'id' => $goods['id']]);
                } else if ($type == 'wlfightgroup') {
                    $copyurl = h5_url('pages/subPages/goods/index', ['type' => 3, 'id' => $goods['id']]);
                } else if ($type == 'coupon') {
                    $copyurl = h5_url('pages/subPages/goods/index', ['type' => 5, 'id' => $goods['id']]);
                    if (empty($goods['is_charge'])) {
                        $helpflag = 0;
                    }
                }
                $result = Util::long2short($copyurl);
                if (!is_error($result) && $result['short_url'] != 'h') {
                    $copyurl = $result['short_url'];
                }
                if ($type == 'wlfightgroup') {
                    $type = 'fightgroup';
                }

                $h5type = array(
                    'rush'         => 3,
                    'groupon'      => 4,
                    'coupon'       => 5,
                    'wlfightgroup' => 6
                );

                $posterurl = h5_url('pages/mainPages/poster/poster', array('id' => $goods['id'], 'type' => $h5type[$type]));
                return array('disflag' => $disflag, 'helpimg' => $disset['helpimg'], 'helpflag' => $helpflag, 'alldismoney' => $alldismoney, 'copyurl' => $copyurl, 'posterurl' => $posterurl, 'fxtext' => $fxtext, 'xxtext' => $xxtext, 'sjtext' => $sjtext, 'yjtext' => $yjtext, 'fxstext' => $fxstext);
            }
        }
    }

    static function disnewsettlement($id)
    {
        global $_W;
        $disorder = pdo_get('wlmerchant_disorder', array('id' => $id));
        $smallorder = pdo_getall('wlmerchant_smallorder', array('disorderid' => $id, 'status' => 1));
        if(Customized::init('distributionText') > 0){
            $reason = '共享股东订单结算';
        }else{
            $reason = '分销订单结算';
        }
        if ($smallorder) {
            foreach ($smallorder as $order) {
                $nosetflag = pdo_getcolumn('wlmerchant_disdetail', array('checkcode' => $order['checkcode']), 'id');
                if (empty($nosetflag)) {
                    if ($order['onedismoney'] > 0) {
                        pdo_fetch("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney+{$order['onedismoney']},nowmoney=nowmoney+{$order['onedismoney']} WHERE id = {$order['oneleadid']}");
                        $leadid = pdo_getcolumn('wlmerchant_distributor', array('id' => $order['oneleadid']), 'mid');
                        $onenowmoney = pdo_getcolumn(PDO_NAME . 'distributor', array('id' => $order['oneleadid']), 'nowmoney');
                        Distribution::adddisdetail($order['disorderid'], $leadid, $disorder['buymid'], 1, $order['onedismoney'], $disorder['plugin'], 1, $reason, $onenowmoney, $order['checkcode']);
                    }
                    if ($order['twodismoney'] > 0) {
                        pdo_fetch("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney+{$order['twodismoney']},nowmoney=nowmoney+{$order['twodismoney']} WHERE id = {$order['twoleadid']}");
                        $leadid = pdo_getcolumn('wlmerchant_distributor', array('id' => $order['twoleadid']), 'mid');
                        $twonowmoney = pdo_getcolumn(PDO_NAME . 'distributor', array('id' => $order['twoleadid']), 'nowmoney');
                        Distribution::adddisdetail($order['disorderid'], $leadid, $disorder['buymid'], 1, $order['twodismoney'], $disorder['plugin'], 2, $reason, $twonowmoney, $order['checkcode']);
                    }
                    pdo_update('wlmerchant_smallorder', array('dissettletime' => time()), array('id' => $order['id']));
                    pdo_update('wlmerchant_disorder', array('status' => 2), array('id' => $id));
                    return true;
                }
            }
        }

    }

    //检查是否为分销商测试
    static function checkdisflag($mid)
    {
        global $_W;
        $distributorid = pdo_getcolumn(PDO_NAME . 'member', array('id' => $mid), 'distributorid');
        if ($distributorid) {
            $flag = pdo_getcolumn(PDO_NAME . 'distributor', array('id' => $distributorid), 'disflag');
            return $flag;
        }
        return 0;
    }

    //获取分销替换文字
    static function getDistText()
    {
        global $_W;
        $disset = Setting::wlsetting_read('distribution');
        $fxtext = $disset['fxtext'] ? $disset['fxtext'] : '分销';
        $xxtext = $disset['xxtext'] ? $disset['xxtext'] : '客户';
        $sjtext = $disset['sjtext'] ? $disset['sjtext'] : '上级';
        $yjtext = $disset['yjtext'] ? $disset['yjtext'] : '佣金';
        $fxstext = $disset['fxstext'] ? $disset['fxstext'] : '分销商';
        $myposter = $disset['myposter'] ? $disset['myposter'] : '我的海报';

        return array('fxtext' => $fxtext, 'xxtext' => $xxtext, 'sjtext' => $sjtext, 'yjtext' => $yjtext, 'fxstext' => $fxstext, 'myposter' => $myposter);
    }

    //获取分销商列表
    static function getNumDistributor($select, $where, $order, $pindex, $psize, $ifpage)
    {
        $distributorInfo = Util::getNumData($select, PDO_NAME . 'distributor', $where, $order, $pindex, $psize, $ifpage);
        return $distributorInfo;
    }


    /**
     * Comment:  新的分销订单生成
     * Author: wlf
     * Date: 2022/10/24 09:18
     * @param $status
     * @return bool
     */
    static function newDisCore($orderid,$plugin){
        global $_W;
        //订单商品信息获取
        if($plugin == 'rush'){
            $order = pdo_get('wlmerchant_rush_order',array('id' => $orderid),array('mid','activityid','optionid','price','num','discount','neworderflag'));
            $goods = pdo_get('wlmerchant_rush_activity',array('id' => $order['activityid']),array('isdistri','disarray','isdistristatus','dissettime','disgroup','disgroupstatus','grouparray','shareholdermoney'));
            if ($order['optionid'] > 0) {
                $option = pdo_get('wlmerchant_goods_option', array('id' => $order['optionid']), array('disarray','grouparray','shareholdermoney'));
                $goods['disarray'] = WeliamWeChat::mergeDisArray($option['disarray'],$goods['disarray']);
                $goods['grouparray'] = WeliamWeChat::mergeGroupArray($option['grouparray'],$goods['grouparray']);
                $goods['shareholdermoney'] = $option['shareholdermoney'] > 0 ? $option['shareholdermoney'] : $goods['shareholdermoney'];
            }
            $disprice = sprintf("%.2f",$order['price'] - $order['discount']);
        }else{
            $order = pdo_get('wlmerchant_order',array('id' => $orderid),array('allocationtype','price','mid','sid','fkid','specid','goodsprice','num','vipdiscount','neworderflag'));
            if($plugin == 'fightgroup'){
                $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $order['fkid']),array('isdistri','disarray','isdistristatus','dissettime','disgroup','disgroupstatus','grouparray','shareholdermoney'));
            }else if($plugin == 'coupon'){
                $goods = pdo_get('wlmerchant_couponlist',array('id' => $order['fkid']),array('isdistri','disarray','isdistristatus','dissettime','disgroup','disgroupstatus','grouparray','shareholdermoney','usetimes'));
            }else if($plugin == 'payonline'){
                $goods = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('payonlinedisstatus','onepayonlinescale','twopayonlinescale','disgroup','grouparray','shareholdermoney'));
                $cureone = $order['price'] * $goods['onepayonlinescale'] / 100;
                $curetwo = $order['price'] * $goods['twopayonlinescale'] / 100;
                if(empty($order['allocationtype'])){
                    $goods['dissettime'] = 1;
                }else{
                    $goods['dissettime'] = 0;
                }
                $goods['disgroupstatus'] = 0;
                $goods['shareholdermoney'] =  sprintf("%.2f",$order['price'] * $goods['shareholdermoney'] / 100);
                $disprice = $order['price'];
            }else if($plugin == 'charge'){
                $goods = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('isdistri','onedismoney','twodismoney','disgroup','grouparray','shareholdermoney'));
                $goods['dissettime'] = 1;
                $goods['disgroupstatus'] = 1;
                $cureone = $goods['onedismoney'];
                $curetwo = $goods['twodismoney'];
            }else if($plugin == 'citydelivery'){
                $goods['shareholdermoney'] = Citydelivery::getShareMoney($orderid);
            }
            if ($order['specid'] > 0) {
                $option = pdo_get('wlmerchant_goods_option', array('id' => $order['specid']), array('disarray','grouparray','shareholdermoney'));
                $goods['disarray'] = WeliamWeChat::mergeDisArray($option['disarray'],$goods['disarray']);
                $goods['grouparray'] = WeliamWeChat::mergeGroupArray($option['grouparray'],$goods['grouparray']);
                $goods['shareholdermoney'] = $option['shareholdermoney'] > 0 ? $option['shareholdermoney'] : $goods['shareholdermoney'];
            }
            if(empty($disprice)){
                $disprice = sprintf("%.2f",$order['goodsprice'] - $order['vipdiscount']);
            }
        }
        $disarray = unserialize($goods['disarray']);
        $grouparray = $goods['grouparray'];
        $isdistristatus = $goods['isdistristatus'];
        $disgroupstatus = $goods['disgroupstatus'];
        if($plugin == 'citydelivery'){
        	$shareholdermoney =  $goods['shareholdermoney'];
        }else{
        	$shareholdermoney =  $goods['shareholdermoney'] * $order['num'];
        }
        $neworderflag = $order['neworderflag'];
        //分销开始
        $disset = Setting::wlsetting_read('distribution');
        $disid = pdo_getcolumn('wlmerchant_member', array('id' => $order['mid']), 'distributorid');
        if ($disset['switch'] && $disid) {
            $member = pdo_get('wlmerchant_distributor', array('id' => $disid), array('id', 'dislevel', 'leadid', 'disflag', 'lockflag','onegroupid','twogroupid'));   //购买者
            //自购返佣
            if ($member['disflag'] > 0 && $plugin != 'distribution') {
                $mleveid = $member['dislevel'];
                $memberlevel = pdo_get(PDO_NAME . 'dislevel', array('id' => $mleveid), array('ownstatus'));
                if (empty($memberlevel)) {
                    $memberlevel = pdo_get(PDO_NAME . 'dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), array('ownstatus'));
                }
                if ($memberlevel['ownstatus']) {
                    $one = pdo_get('wlmerchant_distributor', array('uniacid' => $_W['uniacid'], 'mid' => $order['mid'], 'disflag' => 1), array('id', 'leadid', 'dislevel', 'mid', 'lockflag', 'subnum')); //上级
                }
            }
            if (empty($one) && $member['leadid'] > 0) {
                $one = pdo_get('wlmerchant_distributor', array('uniacid' => $_W['uniacid'], 'mid' => $member['leadid'], 'disflag' => 1), array('id', 'leadid', 'dislevel', 'mid', 'lockflag', 'subnum')); //上级
            }
            //计算分销佣金
            if($one > 0){
                $leadid['one'] = $one['id'];
                $leveid = $one['dislevel'];
                $onelevel = pdo_get(PDO_NAME . 'dislevel', array('id' => $leveid), array('id','onecommission', 'plugin'));
                if (empty($onelevel)) {
                    $onelevel = pdo_get(PDO_NAME . 'dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), array('id','onecommission', 'plugin'));
                    $leveid = $onelevel['id'];
                }
                $oneplugin = unserialize($onelevel['plugin']);
                if(is_array($disarray)){
                    if($isdistristatus > 0){
                        $onemoney = sprintf("%.2f",$disarray[$leveid]['onedismoney'] * $order['num']);
                    }else{
                        $onemoney = sprintf("%.2f",$disarray[$leveid]['onedismoney'] * $disprice / 100);
                    }
                }else if($plugin == 'citydelivery'){
                    $onemoney = Citydelivery::getDisMoney($orderid,$leveid,1);
                } else{
                    $onemoney = $cureone;
                }
                if (in_array($plugin, $oneplugin)) {
                    if ($onemoney > 0) {
                        $leadmoney['one'] = sprintf("%.2f",$onemoney);
                    } else {
                        $leadmoney['one'] = sprintf("%.2f", $disprice * $onelevel['onecommission'] / 100);
                    }
                } else {
                    $leadmoney['one'] = 0;
                }
                if ($disset['ranknum'] > 1) {
                    if ($disset['mode'] && $one['leadid'] < 0) {
                        $one['leadid'] = $one['mid'];
                    }
                    if ($one['leadid'] > 0 && empty($one['lockflag'])) {
                        $two = pdo_get('wlmerchant_distributor', array('uniacid' => $_W['uniacid'], 'mid' => $one['leadid'], 'disflag' => 1), array('id', 'leadid', 'dislevel'));
                        if ($two) {
                            $leadid['two'] = $two['id'];
                            $leveid = $two['dislevel'];
                            $twolevel = pdo_get(PDO_NAME . 'dislevel', array('id' => $leveid), array('id','twocommission', 'plugin','twostatus'));
                            if (empty($twolevel)) {
                                $twolevel = pdo_get('wlmerchant_dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), array('id','twocommission', 'plugin','twostatus'));
                                $leveid = $twolevel['id'];
                            }
                            if(empty($twolevel['twostatus'])){
	                            $twoplugin = unserialize($twolevel['plugin']);
	                            if(is_array($disarray)) {
	                                if ($isdistristatus > 0) {
	                                    $twomoney = sprintf("%.2f", $disarray[$leveid]['twodismoney'] * $order['num']);
	                                } else {
	                                    $twomoney = sprintf("%.2f", $disarray[$leveid]['twodismoney'] * $disprice / 100);
	                                }
	                            }else if($plugin == 'citydelivery'){
	                                $twomoney = Citydelivery::getDisMoney($orderid,$leveid,2);
	                            } else if($onemoney > 0){
	                                $twomoney = $curetwo;
	                            }
	                            if (in_array($plugin, $twoplugin)) {
	                                if ($twomoney > 0 || $disarray[$leveid]['onedismoney'] > 0) {
	                                    $leadmoney['two'] = sprintf("%.2f",$twomoney);
	                                } else {
	                                    $leadmoney['two'] = sprintf("%.2f", $disprice * $twolevel['twocommission'] / 100);
	                                }
	                            } else {
	                                $leadmoney['two'] = 0;
	                            }
	                            if($leadmoney['two'] < 0.01){
	                                $leadid['two'] = 0;
	                            }
                            }else{
                            	$leadmoney['two'] = 0;
                            	$leadid['two'] = 0;
                            }
                        }
                    }
                }

            }
            //计算团长分红
            if($member['onegroupid'] > 0){
                $groupone = pdo_get('wlmerchant_distributor', array('uniacid' => $_W['uniacid'], 'mid' => $member['onegroupid'], 'groupflag' => 1), array('id', 'grouplevel')); //团长
                if(!empty($groupone)){
                    if($plugin == 'citydelivery'){
                        $onegroupmoney = Citydelivery::getGroupMoney($orderid,$groupone['grouplevel'],1);
                    }else{
                        $onegroupmoney = self::getGroupMoney($grouparray,$groupone['grouplevel'],$disprice,$order['num'],$disgroupstatus,1);
                    }
                }
                if($onegroupmoney < 0.01){
                    $member['onegroupid'] = 0;
                }
            }
            if($member['twogroupid'] > 0){
                $grouptwo = pdo_get('wlmerchant_distributor', array('uniacid' => $_W['uniacid'], 'mid' => $member['twogroupid'], 'groupflag' => 1), array('id', 'grouplevel')); //团长
                if(!empty($grouptwo)){
                	$twocalsslevel = pdo_getcolumn(PDO_NAME.'grouplevel',array('id'=>$grouptwo['grouplevel']),'levelclass');
                	$onecalsslevel = pdo_getcolumn(PDO_NAME.'grouplevel',array('id'=>$groupone['grouplevel']),'levelclass');
                	if($twocalsslevel > $onecalsslevel){
	                	if($plugin == 'citydelivery'){
	                        $twogroupmoney = Citydelivery::getGroupMoney($orderid,$grouptwo['grouplevel'],2);
	                    }else{
	                        $twogroupmoney = self::getGroupMoney($grouparray,$grouptwo['grouplevel'],$disprice,$order['num'],$disgroupstatus,2);
	                    }
                	}else{
                		$twogroupmoney = 0;
                	}
                }
                if($twogroupmoney < 0.01){
                    $member['twogroupid'] = 0;
                }
            }
            if ($leadmoney['one'] > 0 || $leadmoney['two'] > 0 || $onegroupmoney > 0 || $twogroupmoney > 0  || $shareholdermoney > 0) {
                //校验是否已经有了分销订单
                $flag = pdo_get('wlmerchant_disorder', array('plugin' => $plugin, 'orderid' => $orderid), array('id'));
                if (!empty($flag)) {
                    $disorderid = $flag['id'];
                } else {
                    $data = array(
                        'uniacid'    => $_W['uniacid'],
                        'aid'        => $_W['aid'],
                        'status'     => 0,
                        'plugin'     => $plugin,
                        'orderid'    => $orderid,
                        'orderprice' => $disprice,
                        'buymid'     => $order['mid'],
                        'oneleadid'  => $leadid['one'],
                        'twoleadid'  => $leadid['two'],
                        'leadmoney'  => serialize($leadmoney),
                        'createtime' => time(),
                        'onegroupid' => $member['onegroupid'],
                        'twogroupid' => $member['twogroupid'],
                        'onegroupmoney' => $onegroupmoney,
                        'twogroupmoney' => $twogroupmoney,
                        'shareholdermoney' => $shareholdermoney,
                        'neworderflag' => $neworderflag
                    );
                    //判断分销订单结算状态
                    if ($goods['dissettime']) {
                        $data['status'] = 1;
                    }
                    pdo_insert('wlmerchant_disorder', $data);
                    $disorderid = pdo_insertid();
                    //判断分销订单结算状态
                    if ($goods['dissettime']) {
                        $disvalue = array(
                            'type'    => $plugin,
                            'orderid' => $disorderid
                        );
                        $disvalue = serialize($disvalue);
                        Queue::addTask(3, $disvalue, time(), $disorderid);
                    }
                    //添加小订单的分销信息
                    if (!empty($neworderflag)) {
                        if ($plugin == 'coupon') {
                            $order['num'] = $order['num'] * $goods['usetimes'];
                        }
                        $sdata = array(
                            'disorderid'  => $disorderid,
                            'oneleadid'   => $data['oneleadid'],
                            'twoleadid'   => $data['twoleadid'],
                            'onedismoney' => sprintf("%.2f", $leadmoney['one'] / $order['num']),
                            'twodismoney' => sprintf("%.2f", $leadmoney['two'] / $order['num']),
                            'onegroupid' => $member['onegroupid'],
                            'twogroupid' => $member['twogroupid'],
                            'onegroupmoney' => sprintf("%.2f", $onegroupmoney / $order['num']),
                            'twogroupmoney' => sprintf("%.2f", $twogroupmoney / $order['num']),
                            'shareholdermoney' => sprintf("%.2f", $shareholdermoney / $order['num']),
                        );
                        if($plugin == 'fightgroup'){
                            $splugin = 'wlfightgroup';
                        }else{
                            $splugin = $plugin;
                        }
                        pdo_update('wlmerchant_smallorder', $sdata, array('plugin' => $splugin, 'orderid' => $orderid));
                    }
                    self::moneyNotice($order['mid'], $plugin, $orderid, $one['id'], $disorderid, 1);
                    if ($disset['noticerank3'] > 0 && $two && $two['id'] != $one['id']) {
                        self::moneyNotice($order['mid'], $plugin, $orderid, $two['id'], $disorderid, 1);
                    }
                    if ((($disset['lockstatus'] == 1) || ($disset['lockstatus'] == 3 && $plugin = 'halfcard')) && $member['lockflag']) { //锁死上下级
                        $res = pdo_update('wlmerchant_distributor', array('lockflag' => 0), array('id' => $member['id']));
                        if ($res) {
                            $url = h5_url('pages/subPages/dealer/client/client');
                            Distribution::checkup($one['id']);
                            Distribution::distriNotice($one['mid'], $url, 2, $member['id']);
                            if ($disset['ranknum'] > 1) {
                                $twodisid = pdo_getcolumn(PDO_NAME . 'distributor', array('mid' => $one['leadid']), 'id');
                                Distribution::checkup($twodisid);
                                if ($disset['noticerank2'] > 0 && empty($one['lockflag'])) {
                                    Distribution::distriNotice($one['leadid'], $url, 2, $member['id']);
                                }
                            }
                        }
                    }
                }
            }
        }
        if(empty($disorderid)){
            $disorderid = 0;
        }
        //336定制 临时下级   所有下级不在固定绑定某个分销商，会在支付后取消绑定关系
        if(Customized::init('customized336') && $disset['temporary_sub'] == 1 && $disorderid > 0){
            //取消当前分销商绑定关系
            pdo_update(PDO_NAME."distributor",['leadid'=>0,'updatetime'=>time(),'onegroupid' => 0,'twogroupid' => 0],['id'=>$disid]);
        }
        return $disorderid;
    }

    /**
     * Comment:  获取团长分红金额
     * Author: wlf
     * Date: 2022/10/24 11:40
     * @param $status
     * @return bool
     */
    static function getGroupMoney($grouparray,$levelid,$dismoney,$num,$isdistristatus,$rank){
        global $_W;
        $grouparray = unserialize($grouparray);
        $grouplevel = pdo_get('wlmerchant_grouplevel',array('id' => $levelid),array('onecommission','twocommission'));
        if($rank == 1){
            if ($isdistristatus > 0) {
                $money = sprintf("%.2f", $grouparray[$levelid]['onegroupmoney'] * $num);
            } else {
                $money = sprintf("%.2f", $grouparray[$levelid]['onegroupmoney'] * $dismoney / 100);
            }
            if(empty($money)){
                $money =  sprintf("%.2f", $dismoney * $grouplevel['onecommission'] / 100);
            }
        }else{
            if ($isdistristatus > 0) {
                $money = sprintf("%.2f", $grouparray[$levelid]['twogroupmoney'] * $num);
            } else {
                $money = sprintf("%.2f", $grouparray[$levelid]['twogroupmoney'] * $dismoney / 100);
            }
            if(empty($money)){
                $money = sprintf("%.2f", $dismoney * $grouplevel['twocommission'] / 100);
            }
        }
        return $money;
    }



    //查询上级分销商与计算订单佣金
    static function disCore($mid, $price, $disarray, $num, $threemoney, $orderid, $plugin, $settflag = 0,$isdistristatus = 0)
    {
        global $_W;
        $disset = Setting::wlsetting_read('distribution');
        $disid = pdo_getcolumn('wlmerchant_member', array('id' => $mid), 'distributorid');
        if ($disset['switch'] && $disid) {
            $member = pdo_get('wlmerchant_distributor', array('uniacid' => $_W['uniacid'], 'mid' => $mid), array('id', 'dislevel', 'leadid', 'disflag', 'lockflag'));   //购买者
            //自购返佣
            if ($member['disflag'] > 0 && $plugin != 'distribution') {
                $mleveid = $member['dislevel'];
                $memberlevel = pdo_get(PDO_NAME . 'dislevel', array('id' => $mleveid), array('ownstatus'));
                if (empty($memberlevel)) {
                    $memberlevel = pdo_get(PDO_NAME . 'dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), array('ownstatus'));
                }
                if ($memberlevel['ownstatus']) {
                    $one = pdo_get('wlmerchant_distributor', array('uniacid' => $_W['uniacid'], 'mid' => $mid, 'disflag' => 1), array('id', 'leadid', 'dislevel', 'mid', 'lockflag', 'subnum')); //上级
                }
            }
            if (empty($one) && $member['leadid'] > 0) {
                $one = pdo_get('wlmerchant_distributor', array('uniacid' => $_W['uniacid'], 'mid' => $member['leadid'], 'disflag' => 1), array('id', 'leadid', 'dislevel', 'mid', 'lockflag', 'subnum')); //上级
            }
            if ($one) {
                $leadid['one'] = $one['id'];
                $leveid = $one['dislevel'];
                $onelevel = pdo_get(PDO_NAME . 'dislevel', array('id' => $leveid), array('id','onecommission', 'plugin'));
                if (empty($onelevel)) {
                    $onelevel = pdo_get(PDO_NAME . 'dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), array('id','onecommission', 'plugin'));
                    $leveid = $onelevel['id'];
                }
                $oneplugin = unserialize($onelevel['plugin']);
                if(empty($disarray) && $plugin == 'rush'){  //校验分销数组
                    $orderinfo = pdo_get('wlmerchant_rush_order',array('id' => $orderid),array('activityid','optionid'));
                    $disarray = pdo_getcolumn(PDO_NAME.'rush_activity',array('id'=>$orderinfo['activityid']),'disarray');
                    if($orderinfo['optionid'] > 0){
                        $option = pdo_get('wlmerchant_goods_option', array('id' => $orderinfo['optionid']), array('disarray'));
                        $disarray = WeliamWeChat::mergeDisArray($option['disarray'],$disarray);
                    }
                    $disarray = unserialize($disarray);
                }
                if(is_array($disarray)){
                    if($isdistristatus > 0){
                        $onemoney = sprintf("%.2f",$disarray[$leveid]['onedismoney'] * $num);
                    }else{
                        $onemoney = sprintf("%.2f",$disarray[$leveid]['onedismoney'] * $price / 100);
                    }
                }else if($plugin == 'citydelivery'){
                    $onemoney = Citydelivery::getDisMoney($orderid,$leveid,1);
                } else{
                    $onemoney = $disarray;
                }
                if (in_array($plugin, $oneplugin)) {
                    if ($onemoney > 0) {
                        $leadmoney['one'] = sprintf("%.2f",$onemoney);
                    } else {
                        $leadmoney['one'] = sprintf("%.2f", $price * $onelevel['onecommission'] / 100);
                    }
                } else {
                    $leadmoney['one'] = 0;
                }
                if ($disset['ranknum'] > 1) {
                    if ($disset['mode'] && $one['leadid'] < 0) {
                        $one['leadid'] = $one['mid'];
                    }
                    if ($one['leadid'] > 0 && empty($one['lockflag'])) {
                        $two = pdo_get('wlmerchant_distributor', array('uniacid' => $_W['uniacid'], 'mid' => $one['leadid'], 'disflag' => 1), array('id', 'leadid', 'dislevel'));
                        if ($two) {
                            $leadid['two'] = $two['id'];
                            $leveid = $two['dislevel'];
                            $twolevel = pdo_get(PDO_NAME . 'dislevel', array('id' => $leveid), array('id','twocommission', 'plugin','twostatus'));
                            if (empty($twolevel)) {
                                $twolevel = pdo_get('wlmerchant_dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), array('id','twocommission', 'plugin','twostatus'));
                                $leveid = $twolevel['id'];
                            }
                            if(empty($twolevel['twostatus'])){
	                            $twoplugin = unserialize($twolevel['plugin']);
	                            if(is_array($disarray)) {
	                                if ($isdistristatus > 0) {
	                                    $twomoney = sprintf("%.2f", $disarray[$leveid]['twodismoney'] * $num);
	                                } else {
	                                    $twomoney = sprintf("%.2f", $disarray[$leveid]['twodismoney'] * $price / 100);
	                                }
	                            }else if($plugin == 'citydelivery'){
	                                $twomoney = Citydelivery::getDisMoney($orderid,$leveid,2);
	                            } else if($disarray > 0){
	                                $twomoney = $num;
	                            }
	                            if (in_array($plugin, $twoplugin)) {
	                                if ($twomoney > 0 || $disarray[$leveid]['onedismoney'] > 0) {
	                                    $leadmoney['two'] = sprintf("%.2f",$twomoney);
	                                } else {
	                                    $leadmoney['two'] = sprintf("%.2f", $price * $twolevel['twocommission'] / 100);
	                                }
	                            } else {
	                                $leadmoney['two'] = 0;
	                            }
	                            if($leadmoney['two'] < 0.01){
	                                $leadid['two'] = 0;
	                            }
                            }else{
                            	$leadmoney['two'] = 0;
                            	$leadid['two'] = 0;
                            }
                        }
                    }
//					if($disset['ranknum']>2 && $two['leadid']>0){
//						$three = pdo_get('wlmerchant_distributor',array('uniacid'=>$_W['uniacid'],'mid'=>$two['leadid']),array('id','leadid','dislevel'));
//						$leadid['three'] = $three['id'];
//						$leveid = $three['dislevel'];
//						if(empty($leveid)){
//							$leveid = pdo_getcolumn('wlmerchant_dislevel',array('uniacid' =>$_W['uniacid'],'isdefault'=>1),'id');
//						}
//						$threelevel = pdo_get(PDO_NAME.'dislevel',array('id'=>$leveid),array('threecommission','plugin'));
//						$threeplugin = unserialize($threelevel['plugin']);
//						if(in_array($plugin,$threeplugin)){
//							if($threemoney>0){
//								$leadmoney['three'] = $threemoney;
//							}else {
//								$leadmoney['three'] = number_format($price*$threelevel['threecommission']/100,2);
//							}
//						}else {
//							$leadmoney['three'] = 0;
//						}
//					}
                }
                if ($leadmoney['one'] > 0 || $leadmoney['two'] > 0) {
                    //校验是否已经有了分销订单
                    $flag = pdo_get('wlmerchant_disorder', array('plugin' => $plugin, 'orderid' => $orderid), array('id'));
                    if (!empty($flag)) {
                        $disorderid = $flag['id'];
                    } else {
                        $data = array(
                            'uniacid'    => $_W['uniacid'],
                            'aid'        => $_W['aid'],
                            'status'     => 0,
                            'plugin'     => $plugin,
                            'orderid'    => $orderid,
                            'orderprice' => $price,
                            'buymid'     => $mid,
                            'oneleadid'  => $leadid['one'],
                            'twoleadid'  => $leadid['two'],
                            'leadmoney'  => serialize($leadmoney),
                            'createtime' => time()
                        );
                        //判断分销订单结算状态
                        if ($settflag) {
                            $data['status'] = 1;
                        }
                        //小订单代码
                        if ($plugin == 'rush') {
                            $order = pdo_get('wlmerchant_rush_order', array('id' => $orderid), array('neworderflag', 'num'));
                            $neworderflag = $order['neworderflag'];
                        } else {
                            $order = pdo_get('wlmerchant_order', array('id' => $orderid), array('neworderflag', 'fkid', 'num'));
                            if ($plugin == 'coupon') {
                                $usetimes = pdo_getcolumn(PDO_NAME . 'couponlist', array('id' => $order['fkid']), 'usetimes');
                                $order['num'] = $order['num'] * $usetimes;
                            }
                            $neworderflag = $order['neworderflag'];
                        }

                        if (!empty($neworderflag)) {
                            $data['neworderflag'] = $neworderflag;
                        }
                        pdo_insert('wlmerchant_disorder', $data);
                        $disorderid = pdo_insertid();
                        //判断分销订单结算状态
                        if ($settflag) {
                            $disvalue = array(
                                'type'    => $plugin,
                                'orderid' => $disorderid
                            );
                            $disvalue = serialize($disvalue);
                            Queue::addTask(3, $disvalue, time(), $disorderid);
                        }
                        //添加小订单的分销信息
                        if (!empty($neworderflag)) {
                            $sdata = array(
                                'disorderid'  => $disorderid,
                                'oneleadid'   => $data['oneleadid'],
                                'twoleadid'   => $data['twoleadid'],
                                'onedismoney' => sprintf("%.2f", $leadmoney['one'] / $order['num']),
                                'twodismoney' => sprintf("%.2f", $leadmoney['two'] / $order['num']),
                            );
                            if($plugin == 'fightgroup'){
                                $splugin = 'wlfightgroup';
                            }else{
                                $splugin = $plugin;
                            }
                            pdo_update('wlmerchant_smallorder', $sdata, array('plugin' => $splugin, 'orderid' => $orderid));
                        }
                        self::moneyNotice($mid, $plugin, $orderid, $one['id'], $disorderid, 1);
                        if ($disset['noticerank3'] > 0 && $two && $two['id'] != $one['id']) {
                            self::moneyNotice($mid, $plugin, $orderid, $two['id'], $disorderid, 1);
                        }
//                    if ($disset['noticerank3'] > 1 && $three) {
//                        self::moneyNotice($mid, $plugin, $orderid, $three['id'], $disorderid, 1);
//                    }
                        if ((($disset['lockstatus'] == 1) || ($disset['lockstatus'] == 3 && $plugin = 'halfcard')) && $member['lockflag']) { //锁死上下级
                            $res = pdo_update('wlmerchant_distributor', array('lockflag' => 0), array('id' => $member['id']));
                            if ($res) {
                                $url = h5_url('pages/subPages/dealer/client/client');
                                $openid = pdo_getcolumn(PDO_NAME . 'member', array('id' => $one['mid']), 'openid');

                                //客户定制渠道分销二级自动升一级
                                if (!empty($disset['twoupone']) && $disset['mode'] == 1 && !empty($one['leadid'])) {
                                    $one_leadid = $one['leadid'];
                                    $onenum = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_distributor') . " WHERE leadid = {$one['mid']} AND lockflag = 0 ");
                                    if (($onenum + $one['subnum']) >= $disset['twoupone']) {
                                        pdo_update('wlmerchant_distributor', array('leadid' => -1), array('id' => $one['id']));
                                        //$one_leadid = $one['mid'];
                                        //把二级的客户变为一级的客户
                                        pdo_update('wlmerchant_distributor', array('leadid' => $one['leadid']), array('leadid' => $one['mid']));
                                    }
                                    //修改下级数量
                                    pdo_update('wlmerchant_distributor', array('subnum' => $one['subnum'] + 1), array('id' => $one['id']));
                                    //修改购买用户的上级
                                    if ($one_leadid != -1) {
                                        pdo_update('wlmerchant_distributor', array('leadid' => $one_leadid), array('id' => $member['id']));
                                    }
                                    //成为分销商
                                    pdo_update('wlmerchant_distributor', array('disflag' => 1), array('id' => $member['id']));
                                }

                                Distribution::checkup($one['id']);
                                Distribution::distriNotice($one['mid'], $url, 2, $member['id']);
                                if ($disset['ranknum'] > 1) {
                                    $twodisid = pdo_getcolumn(PDO_NAME . 'distributor', array('mid' => $one['leadid']), 'id');
                                    Distribution::checkup($twodisid);
                                    if ($disset['noticerank2'] > 0 && empty($one['lockflag'])) {
                                        $openid = pdo_getcolumn(PDO_NAME . 'member', array('id' => $one['leadid']), 'openid');
                                        Distribution::distriNotice($one['leadid'], $url, 2, $member['id']);
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $disorderid = 0;
                }
            } else {
                $disorderid = 0;
            }
        } else {
            $disorderid = 0;
        }
        //336定制 临时下级   所有下级不在固定绑定某个分销商，会在支付后取消绑定关系
        if(Customized::init('customized336') && $disset['temporary_sub'] == 1 && $disorderid > 0){
            //取消当前分销商绑定关系
            pdo_update(PDO_NAME."distributor",['leadid'=>0,'updatetime'=>time()],['id'=>$disid]);
        }

        return $disorderid;
    }

    //升级判断
    static function checkup($distributorid)
    {
        global $_W;
        $distributor = pdo_get('wlmerchant_distributor', array('id' => $distributorid));
        $_W['uniacid'] = $distributor['uniacid'];
        $settings = Setting::wlsetting_read('distribution');
        $levelupstatusArray = unserialize($settings['levelupstatus']);
        $nowdislevel = $distributor['dislevel'];
        if (empty($nowdislevel)) {
            $nowdislevel = pdo_getcolumn(PDO_NAME . 'dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), 'id');
            pdo_update('wlmerchant_distributor',array('dislevel' => $nowdislevel),array('id' => $distributorid));
        }
        $nowdislevelInfo = pdo_get(PDO_NAME . 'dislevel', array('id' => $nowdislevel), ['levelclass','isdefault']);
        $nowlevelclass = $nowdislevelInfo['levelclass'];
        $highlevel = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_dislevel') . "WHERE uniacid = {$_W['uniacid']} AND levelclass > {$nowlevelclass} ORDER BY levelclass ASC");
        $canUp = 0;


        if (in_array(0,$levelupstatusArray) && $highlevel['upstandard'] > 0 && empty($canUp) ) {
            $disupstandard = $distributor['dismoney'];
            if($disupstandard >= $highlevel['upstandard']){
                $canUp = 1;
            }
        }

        if (in_array(1,$levelupstatusArray) && $highlevel['upstandard1'] > 0 && empty($canUp) ) {
            $onelows = pdo_getall('wlmerchant_distributor', array('leadid' => $distributor['mid'], 'lockflag' => 0), array('mid'));
            $onenum = count($onelows);
            $twonum = 0;
            $threenum = 0;
            if ($settings['ranknum'] > 1 && $onelows) {
                foreach ($onelows as $key => $one) {
                    $twolows = pdo_getall('wlmerchant_distributor', array('leadid' => $one['mid'], 'lockflag' => 0), array('mid'));
                    $twonum += count($twolows);
                    if ($settings['ranknum'] > 2 && $twolows) {
                        foreach ($twolows as $key => $two) {
                            $threelows = pdo_getall('wlmerchant_distributor', array('leadid' => $two['mid'], 'lockflag' => 0), array('mid'));
                            $threenum += count($threelows);
                        }
                    }
                }
            }
            $disupstandard = $onenum + $twonum + $threenum;
            if($disupstandard >= $highlevel['upstandard1']){
                $canUp = 1;
            }
        }

        if (in_array(2,$levelupstatusArray) && $highlevel['upstandard2'] > 0 && empty($canUp)) {
            $onelows = pdo_getall('wlmerchant_distributor', array('leadid' => $distributor['mid'], 'lockflag' => 0), array('mid'));
            $disupstandard = count($onelows);
            if($disupstandard >= $highlevel['upstandard2']){
                $canUp = 1;
            }
        }
        if (in_array(3,$levelupstatusArray) && $highlevel['upstandard3'] > 0 && empty($canUp)) {
            $onelows = pdo_getall('wlmerchant_distributor', array('leadid' => $distributor['mid'], 'lockflag' => 0), array('mid', 'disflag'));
            $onenum = 0;
            $twonum = 0;
            $threenum = 0;
            if ($onelows) {
                foreach ($onelows as $key => $one) {
                    if ($one['disflag']) {
                        $onenum += 1;
                    }
                    if ($settings['ranknum'] > 1) {
                        $twolows = pdo_getall('wlmerchant_distributor', array('leadid' => $one['mid']), array('mid', 'disflag'));
                        if ($twolows) {
                            foreach ($twolows as $key => $two) {
                                if ($two['disflag']) {
                                    $twonum += 1;
                                }
                                if ($settings['ranknum'] > 2) {
                                    $threelows = pdo_getall('wlmerchant_distributor', array('leadid' => $two['mid'], 'disflag' => 1), array('mid'));
                                    $threenum += count($threelows);
                                }
                            }
                        }
                    }
                }
            }
            $disupstandard = $onenum + $twonum + $threenum;
            if($disupstandard >= $highlevel['upstandard3']){
                $canUp = 1;
            }
        }
        if (in_array(4,$levelupstatusArray) && $highlevel['upstandard4'] > 0 && empty($canUp)) {
            $onelows = pdo_getall('wlmerchant_distributor', array('leadid' => $distributor['mid'], 'disflag' => 1, 'lockflag' => 0), array('mid'));
            $disupstandard = count($onelows);
            if($disupstandard >= $highlevel['upstandard4']){
                $canUp = 1;
            }
        }
        if (in_array(5,$levelupstatusArray) && $highlevel['upstandard5'] > 0 && empty($canUp)) {
            $disupstandard = pdo_getcolumn('wlmerchant_disorder',array('buymid' => $distributor['mid'],'oneleadid'=>$distributor['id']),array("SUM(orderprice)"));
            if($disupstandard >= $highlevel['upstandard5']){
                $canUp = 1;
            }
        }
        if ($canUp > 0) {
            $res = pdo_update('wlmerchant_distributor', array('dislevel' => $highlevel['id']), array('id' => $distributorid));
            if ($res) {
                //赠送积分
                if($highlevel['giftintegral'] > 0){
                    Member::credit_update_credit1($distributor['mid'], $highlevel['giftintegral'], '等级提升赠送积分');
                }
                self::levelupNotice($distributorid, $nowdislevel, $highlevel['id']);
            }
        }
		self::checkUpGroup($distributorid);
    }


	//团长升级
	static function checkUpGroup($distributorid){
        global $_W;
		$distributor = pdo_get('wlmerchant_distributor', array('id' => $distributorid));
        $_W['uniacid'] = $distributor['uniacid'];
		if($distributor['groupflag'] > 0){
			//团长升级
			$nowdislevel = $distributor['grouplevel'];
			$nowdislevelInfo = pdo_get(PDO_NAME . 'grouplevel', array('id' => $nowdislevel), ['levelclass']);
	        $nowlevelclass = $nowdislevelInfo['levelclass'];
	        $highlevel = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_grouplevel') . "WHERE uniacid = {$_W['uniacid']} AND levelclass > {$nowlevelclass} ORDER BY levelclass ASC");
		
			if($highlevel['upstandard1'] > 0 || $highlevel['upstandard2'] > 0  || $highlevel['upstandard3'] > 0  || $highlevel['upstandard4'] > 0  || $highlevel['upstandard5'] > 0  || $highlevel['upstandard6'] > 0){
				$canUp = 1;
				//高级分销商人数
				if($canUp > 0 && $highlevel['upstandard1'] > 0){  
					$maxlevel = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_dislevel') . "WHERE uniacid = {$_W['uniacid']} ORDER BY levelclass DESC");
					$maxlevel = $maxlevel['id'];
					$upstandard1 = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_distributor')." WHERE onegroupid = {$distributor['mid']} AND disflag > 0 AND dislevel = {$maxlevel}");
					if($upstandard1 < $highlevel['upstandard1']){
						$canUp = 0;
					}
				}
				//自然月内结算佣金额度
				if($canUp > 0 && $highlevel['upstandard2'] > 0){
					$beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
					$upstandard2 = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename('wlmerchant_disdetail')." WHERE leadid = {$distributor['mid']} AND createtime > {$beginThismonth} AND type = 1 AND status != 1 AND plugin != 'cash' ");
					if($upstandard2 < $highlevel['upstandard2']){
						$canUp = 0;
					}
				}
				//自购订单的金额
				if($canUp > 0 && $highlevel['upstandard3'] > 0){
					$rushmonry = pdo_fetchcolumn('SELECT SUM(actualprice) FROM '.tablename('wlmerchant_rush_order')." WHERE mid = {$distributor['mid']} AND status IN (1,2,3,4,8,9)  ");
					$ordermonry = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename('wlmerchant_order')." WHERE mid = {$distributor['mid']} AND status IN (1,2,3,4,8,9) AND plugin != 'member'  ");
					$upstandard3 = $rushmonry + $ordermonry;
					if($upstandard3 < $highlevel['upstandard3']){
						$canUp = 0;
					}
				}
				//团队总人数
				if($canUp > 0 && $highlevel['upstandard4'] > 0){
					$upstandard4 = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_distributor')." WHERE onegroupid = {$distributor['mid']}");
					if($upstandard4 < $highlevel['upstandard4']){
						$canUp = 0;
					}
				}
				//自然月内订单数量
				if($canUp > 0 && $highlevel['upstandard5'] > 0){
					$beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
					$upstandard5 = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_disorder')." WHERE onegroupid = {$distributor['mid']} AND createtime > {$beginThismonth}");
					if($upstandard5 < $highlevel['upstandard5']){
						$canUp = 0;
					}
				}
				//团队团长人数
				if($canUp > 0 && $highlevel['upstandard6'] > 0){
					if($highlevel['shareholder'] > 0){
						//查找最高级
						$maxglevel = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_grouplevel') . "WHERE uniacid = {$_W['uniacid']} AND shareholder = 0 ORDER BY levelclass DESC");
						$maxglevel = $maxglevel['id'];
						$upstandard6 = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_distributor')." WHERE onegroupid = {$distributor['mid']} AND groupflag > 0 AND grouplevel = {$maxglevel}");
					}else{
						$upstandard6 = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_distributor')." WHERE onegroupid = {$distributor['mid']} AND groupflag > 0");
					}
					if($upstandard6 < $highlevel['upstandard6']){
						$canUp = 0;
					}
				}
				//升级成高级团长!
				if($canUp > 0){
					$upinfo = [
						'grouplevel' => $highlevel['id'],
						'shareholder' => $highlevel['shareholder']
					];
					if($highlevel['shareholder'] > 0 && empty($distributor['sharetime'])){
						$upinfo['sharetime'] = time();
					}
					pdo_update('wlmerchant_distributor',$upinfo,array('id' => $distributorid));
				}
			}	
		
		}else{
			//分销商升级团长
			$highlevel = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_grouplevel') . "WHERE uniacid = {$_W['uniacid']} AND isdefault = 1 ");
			//高级分销商人数
			if($highlevel['upstandard1'] > 0 || $highlevel['upstandard2'] > 0  || $highlevel['upstandard3'] > 0  || $highlevel['upstandard4'] > 0  || $highlevel['upstandard5'] > 0  || $highlevel['upstandard6'] > 0){
				$canUp = 1;
				if($canUp > 0 && $highlevel['upstandard1'] > 0){  //高级分销商人数
					$maxlevel = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_dislevel') . "WHERE uniacid = {$_W['uniacid']} ORDER BY levelclass DESC");
					$maxlevel = $maxlevel['id'];
					$upstandard1 = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_distributor')." WHERE leadid = {$distributor['mid']} AND disflag > 0 AND dislevel = {$maxlevel}");
					if($upstandard1 < $highlevel['upstandard1']){
						$canUp = 0;
					}
				}
				//自然月内结算佣金额度
				if($canUp > 0 && $highlevel['upstandard2'] > 0){
					$beginThismonth = mktime(0,0,0,date('m'),1,date('Y'));
					$upstandard2 = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename('wlmerchant_disdetail')." WHERE leadid = {$distributor['mid']} AND type = 1 AND status != 1 AND plugin != 'cash' ");
					if($upstandard2 < $highlevel['upstandard2']){
						$canUp = 0;
					}
				}
				//自购订单的金额
				if($canUp > 0 && $highlevel['upstandard3'] > 0){
					$rushmonry = pdo_fetchcolumn('SELECT SUM(actualprice) FROM '.tablename('wlmerchant_rush_order')." WHERE mid = {$distributor['mid']} AND status IN (1,2,3,4,8,9)  ");
					$ordermonry = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename('wlmerchant_order')." WHERE mid = {$distributor['mid']} AND status IN (1,2,3,4,8,9) AND plugin != 'member'  ");
					$upstandard3 = $rushmonry + $ordermonry;
					if($upstandard3 < $highlevel['upstandard3']){
						$canUp = 0;
					}
				}
				//团队总人数
				if($canUp > 0 && $highlevel['upstandard4'] > 0){
					$upstandard4 = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_distributor')." WHERE leadid = {$distributor['mid']}");
					if($upstandard4 < $highlevel['upstandard4']){
						$canUp = 0;
					}
				}
				//自然月内订单数量
				if($canUp > 0 && $highlevel['upstandard5'] > 0){
					$upstandard5 = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_disorder')." WHERE oneleadid = {$distributor['id']} AND createtime > {$beginThismonth}");
					if($upstandard5 < $highlevel['upstandard5']){
						$canUp = 0;
					}
				}
				//分销商升级成团长!
				if($canUp > 0){
					$upinfo = [
						'grouplevel' => $highlevel['id'],
						'shareholder' => $highlevel['shareholder'],
						'groupflag' => 1
					];
					if($highlevel['shareholder'] > 0){
						$upinfo['sharetime'] = time();
					}
					pdo_update('wlmerchant_distributor',$upinfo,array('id' => $distributorid));
					self::changeGroupId($distributor['mid'], $distributor['mid'],$distributor['onegroupid']);
				}
			}
		
		}
	}


    //处理支付
    static function payApplydisNotify($params)
    {
        global $_W;
        Util::wl_log('payResult_notify', PATH_DATA . "merchant/data/", $params); //写入异步日志记录
        $order_out = pdo_get(PDO_NAME . 'order', array('orderno' => $params['tid']));
        if ($order_out['status'] == 0) {
            $distributor = pdo_get('wlmerchant_distributor', array('mid' => $order_out['mid']));
            $settings = Setting::wlsetting_read('distribution');

            if ($order_out['specid'] == 2) {   //二级
                $examine = $settings['twoexamine'];
                $onegetmoney = $settings['onegetmoney'];
                $twogetmoney = 0;
            } else {     //一级
                $examine = $settings['examine'];
                if (empty($settings['mode'])) {
                    $onegetmoney = $settings['modeonemoney'];
                    $twogetmoney = $settings['modetwomoney'];
                }
            }

            $member = pdo_get('wlmerchant_member', array('id' => $order_out['mid']), array('mobile', 'nickname', 'realname'));
            $data = self::getPayData($params); //得到支付参数，处理代付
            if ($data['status'] == 1) {
                $data['status'] = 3;
            }

            pdo_update(PDO_NAME . 'order', $data, array('orderno' => $params['tid'])); //更新订单状态
            if ($examine == 1) {
                $data = array(
                    'uniacid'    => $_W['uniacid'],
                    'aid'        => $order_out['aid'],
                    'mid'        => $order_out['mid'],
                    'status'     => 0,
                    'realname'   => $member['realname'] ? $member['realname'] : $member['nickname'],
                    'mobile'     => $member['mobile'],
                    'createtime' => time(),
                    'rank'       => $order_out['specid']
                );
                $res = pdo_insert('wlmerchant_applydistributor', $data);
                self::toadmin($data['realname']);
            } else {
                if ($distributor) {
                    if (empty($distributor['disflag'])) {
                        pdo_update('wlmerchant_distributor', array('disflag' => 1, 'leadid' => $order_out['recordid'], 'updatetime' => time(), 'lockflag' => 0), array('mid' => $order_out['mid']));
                    }
                    $head_id = $distributor['leadid'];
                    $disid = $distributor['id'];
                } else {
                    $data = array(
                        'uniacid'    => $_W['uniacid'],
                        'aid'        => $order_out['aid'],
                        'mid'        => $order_out['mid'],
                        'createtime' => time(),
                        'disflag'    => 1,
                        'nickname'   => $member['nickname'],
                        'mobile'     => $member['mobile'],
                        'realname'   => $member['realname'],
                        'leadid'     => $order_out['recordid']
                    );
                    pdo_insert('wlmerchant_distributor', $data);
                    $disid = pdo_insertid();
                    $head_id = $order_out['recordid'];
                    pdo_update('wlmerchant_member', array('distributorid' => $disid), array('id' => $order_out['mid']));
                }
                $openid = pdo_getcolumn(PDO_NAME . 'member', array('id' => $order_out['mid']), 'openid');
                Distribution::distriNotice($order_out['mid'], '', 1);
                if($head_id > 0) Distribution::distriNotice($head_id, '', 2,$disid);//发送模板消息
            }
            //处理分销
            $disorderid = self::disCore($order_out['mid'], $order_out['price'], $onegetmoney, $twogetmoney, 0, $order_out['id'], 'distribution', 1);
            pdo_update(PDO_NAME . 'order',['disorderid' => $disorderid], array('orderno' => $params['tid'])); //更新订单状态
        }
    }

    static function payApplydisReturn($params)
    {
        wl_message('支付成功', h5_url('pages/subPages/dealer/index/index'), 'success');
    }

    static function getPayData($params)
    {
        global $_W;
        $data = array('status' => $params['result'] == 'success' ? 1 : 0);
        if ($params['is_usecard'] == 1) {
            $fee = $params['card_fee'];
            $data['is_usecard'] = 1;
        } else {
            $fee = $params['fee'];
        }
        //$paytype = array('credit' => 1, 'wechat' => 2, 'alipay' => 3, 'delivery' => 4,'wxapp' => 5);
        $data['paytype'] = $params['type'];
        if ($params['type'] == 'wechat') $data['transid'] = $params['tag']['transaction_id'];
        $data['paytime'] = TIMESTAMP;
        return $data;
    }

    //申请通知
    static function toadmin($name){
        global $_W;
        $openids = pdo_getall('wlmerchant_agentadmin', array('uniacid' => $_W['uniacid'],'aid' => 0, 'notice' => 1), array('mid','noticeauthority'));
        if ($openids) {
            foreach ($openids as $key => $member) {
                $noticeauthority = unserialize($member['noticeauthority']);
                if(empty($noticeauthority)) $noticeauthority = [];
                if ($member['mid'] > 0 && (in_array('disapply',$noticeauthority) || empty($noticeauthority))) {
                    $fxstext =  $_W['wlsetting']['trade']['fxstext'] ? $_W['wlsetting']['trade']['fxstext'] : "分销商";
                    $data = array(
                        'first' => '一个成为'.$fxstext.'的申请待审核',
                        'type' => $fxstext.'申请',//业务类型
                        'content' => '申请人:[' . $name . ']',//业务内容
                        'status' => '待审核',//处理结果
                        'time' => date('Y-m-d H:i:s', time()),//操作时间
                        'remark' => '请管理员尽快审核'
                    );
                    if(Customized::init('distributionText') > 0) {
                        $data['first'] = '一个成为共享股东的申请待审核';
                        $data['type'] = '共享股东申请';
                    }
                    TempModel::sendInit('service',$member['mid'], $data, $_W['source'], '');
                }
            }
        }
    }

    //业务通知
    static function distriNotice($mid, $url, $flag, $lowdisid = '', $txmoney = '', $cashtype = '')
    {
        global $_W;
        $settings = Setting::wlsetting_read('distribution');
        $nickname = pdo_getcolumn(PDO_NAME . 'member', array('id' => $mid), 'nickname');
        if ($flag == 1) {    //成为分销商通知
            $keyword1 = $settings['noticetitle1'] ? $settings['noticetitle1'] : '成为分销商通知';
            $keyword2 = '已完成';
            $remark = $settings['noticecontent1'];
            $remark = str_replace('[昵称]', $nickname, $remark);
            $remark = str_replace('[时间]', date('Y-m-d H:i:s', time()), $remark);

            $keyword1 = str_replace('[昵称]', $nickname, $keyword1);
            $keyword1 = str_replace('[时间]', date('Y-m-d H:i:s', time()), $keyword1);
        } else if ($flag == 2) {   //新增下级通知
            $keyword1 = $settings['noticetitle2'] ? $settings['noticetitle2'] : '新增下级通知';
            $keyword2 = '已完成';
            $remark = $settings['noticecontent2'];
            $lowdistri = pdo_get('wlmerchant_distributor', array('id' => $lowdisid), array('nickname', 'leadid'));
            if (!$nickname) {
                $nickname = pdo_getcolumn(PDO_NAME . "member", ['id' => $lowdistri['leadid']], 'nickname');
            }
            $lowname = '[' . $lowdistri['nickname'] . ']';
            $remark = str_replace('[昵称]', $nickname, $remark);
            $remark = str_replace('[下级昵称]', $lowname, $remark);
            $remark = str_replace('[时间]', date('Y-m-d H:i:s', time()), $remark);
            $keyword1 = str_replace('[昵称]', $nickname, $keyword1);
            $keyword1 = str_replace('[下级昵称]', $lowname, $keyword1);
            $keyword1 = str_replace('[时间]', date('Y-m-d H:i:s', time()), $keyword1);
            if ($lowdistri['leadid'] == $mid) {
                $lowrank = '一级';
            } else {
                $lowrank = '二级';
            }
            $remark = str_replace('[下线层级]', $lowrank, $remark);
            $keyword1 = str_replace('[下线层级]', $lowrank, $keyword1);
        } else if ($flag == 3) { //提现申请提交通知
            $keyword1 = $settings['noticetitle5'] ? $settings['noticetitle5'] : '提现申请提交通知';
            $keyword2 = '申请已提交,待审核';
            $remark = $settings['noticecontent5'];
            $remark = str_replace('[昵称]', $nickname, $remark);
            $remark = str_replace('[时间]', date('Y-m-d H:i:s', time()), $remark);
            $remark = str_replace('[金额]', $txmoney, $remark);
            $keyword1 = str_replace('[昵称]', $nickname, $keyword1);
            $keyword1 = str_replace('[时间]', date('Y-m-d H:i:s', time()), $keyword1);
            $keyword1 = str_replace('[金额]', $txmoney, $keyword1);
        } else if ($flag == 4 || $flag == 5) { //提现申请提交通知
            $keyword1 = $settings['noticetitle6'] ? $settings['noticetitle6'] : '提现申请审核完成通知';
            if ($flag == 4) {
                $keyword2 = '申请已通过,待打款';
            } else {
                $keyword2 = '申请被驳回,请重试';
            }
            $remark = $settings['noticecontent6'];
            $remark = str_replace('[昵称]', $nickname, $remark);
            $remark = str_replace('[时间]', date('Y-m-d H:i:s', time()), $remark);
            $remark = str_replace('[金额]', $txmoney, $remark);

            $keyword1 = str_replace('[昵称]', $nickname, $keyword1);
            $keyword1 = str_replace('[时间]', date('Y-m-d H:i:s', time()), $keyword1);
            $keyword1 = str_replace('[金额]', $txmoney, $keyword1);
        } else if ($flag == 6) { //佣金打款通知
            $keyword1 = $settings['noticetitle7'] ? $settings['noticetitle7'] : '提现申请审核完成通知';
            $keyword2 = '提现佣金已打款';
            $remark = $settings['noticecontent7'];
            $remark = str_replace('[昵称]', $nickname, $remark);
            $remark = str_replace('[时间]', date('Y-m-d H:i:s', time()), $remark);
            $remark = str_replace('[金额]', $txmoney, $remark);
            $remark = str_replace('[打款方式]', $cashtype, $remark);

            $keyword1 = str_replace('[昵称]', $nickname, $keyword1);
            $keyword1 = str_replace('[时间]', date('Y-m-d H:i:s', time()), $keyword1);
            $keyword1 = str_replace('[金额]', $txmoney, $keyword1);
            $keyword1 = str_replace('[打款方式]', $cashtype, $keyword1);
        }
        $first = '您有一个新的业务通知';
        $fxstext = $_W['wlsetting']['trade']['fxstext'] ? $_W['wlsetting']['trade']['fxstext'] : "分销商";
        $data = array(
            'first'   => $first,
            'type'    => $keyword1,//业务类型
            'content' => $fxstext . ':[' . $nickname . ']',//业务内容
            'status'  => $keyword2,//处理结果
            'time'    => date('Y-m-d H:i:s', time()),//操作时间
            'remark'  => $remark
        );
        Util::wl_log('tempError', PATH_MODULE."log/",['mid'=>$mid,'data'=>$data],'分销商相关模板消息'); //写入日志记录
        if ($remark != '') {
            return TempModel::sendInit('service', $mid, $data, $_W['source'] ?: 1, $url);
        }
    }

    //1下级付款通知  2佣金提醒
    static function moneyNotice($buymid, $plugin, $orderid, $senddisid, $disorderid, $flag)
    {
        global $_W;
        $settings = Setting::wlsetting_read('distribution');
        $url = h5_url('pages/subPages/dealer/gener/gener');
        $time = date('Y-m-d H:i:s', time());
        $buyname = pdo_getcolumn('wlmerchant_member', array('id' => $buymid), 'nickname');
        $sendmid = pdo_getcolumn('wlmerchant_distributor', array('id' => $senddisid), 'mid');
        $disorder = pdo_get('wlmerchant_disorder', array('id' => $disorderid));
        $money = unserialize($disorder['leadmoney']);
        if ($plugin == 'fightgroup') {
            $order = pdo_get('wlmerchant_order', array('id' => $orderid), array('fkid', 'orderno'));
            $goodsid = $order['fkid'];
            $orderno = $order['orderno'];
            $goodsname = pdo_getcolumn('wlmerchant_fightgroup_goods', array('id' => $goodsid), 'name');
            $orderstatus = '拼团商品';
        } else if ($plugin == 'rush') {
            $order = pdo_get('wlmerchant_rush_order', array('id' => $orderid), array('activityid', 'orderno'));
            $goodsid = $order['activityid'];
            $orderno = $order['orderno'];
            $goodsname = pdo_getcolumn('wlmerchant_rush_activity', array('id' => $goodsid), 'name');
            $orderstatus = '抢购商品';
        } else if ($plugin == 'halfcard') {
            $order = pdo_get('wlmerchant_halfcard_record', array('id' => $orderid), array('typeid', 'orderno'));
            $goodsid = $order['typeid'];
            $orderno = $order['orderno'];
            $goodsname = pdo_getcolumn('wlmerchant_halfcard_type', array('id' => $goodsid), 'name');
            $orderstatus = '一卡通';
        } else if ($plugin == 'pocket') {
            $order = pdo_get('wlmerchant_order', array('id' => $orderid), array('fkid', 'orderno'));
            $goodsid = $order['fkid'];
            $orderno = $order['orderno'];
            $typeid = pdo_getcolumn('wlmerchant_pocket_informations', array('id' => $goodsid), 'type');
            $goodsname = pdo_getcolumn('wlmerchant_pocket_type', array('id' => $typeid), 'title');
            $orderstatus = '掌上信息';
        } else if ($plugin == 'charge') {
            $order = pdo_get('wlmerchant_order', array('id' => $orderid), array('fkid', 'orderno'));
            $goodsid = $order['fkid'];
            $orderno = $order['orderno'];
            $goodsname = pdo_getcolumn('wlmerchant_chargelist', array('id' => $goodsid), 'name');
            $orderstatus = '付费入驻';
        } else if ($plugin == 'coupon') {
            $order = pdo_get('wlmerchant_order', array('id' => $orderid), array('fkid', 'orderno'));
            $goodsid = $order['fkid'];
            $orderno = $order['orderno'];
            $goodsname = pdo_getcolumn('wlmerchant_couponlist', array('id' => $goodsid), 'title');
            $orderstatus = '卡券';
        } else if ($plugin == 'groupon') {
            $order = pdo_get('wlmerchant_order', array('id' => $orderid), array('fkid', 'orderno'));
            $goodsid = $order['fkid'];
            $orderno = $order['orderno'];
            $goodsname = pdo_getcolumn('wlmerchant_groupon_activity', array('id' => $goodsid), 'name');
            $orderstatus = '团购商品';
        } else if ($plugin == 'distribution') {
            $order = pdo_get('wlmerchant_order', array('id' => $orderid), array('fkid', 'orderno'));
            $goodsid = $order['fkid'];
            $orderno = $order['orderno'];
            $goodsname = '付费申请';
            $orderstatus = '付费申请';
        } else if ($plugin == 'consumption') {
            $order = pdo_get('wlmerchant_order', array('id' => $orderid), array('fkid', 'orderno'));
            $goodsid = $order['fkid'];
            $orderno = $order['orderno'];
            $goodsname = pdo_getcolumn('wlmerchant_consumption_goods', array('id' => $goodsid), 'title');
            $orderstatus = '积分商城';
        } else if ($plugin == 'taxipay') {
            $order = pdo_get('wlmerchant_order', array('id' => $orderid), array('price', 'orderno'));
            $orderno = $order['orderno'];
            $goodsname = '买单支付';
            $orderstatus = '出租车买单';
            $url = h5_url('pages/subPages/dealer/earnings/earnings');
        } else if ($plugin == 'citycard') {
            $order = pdo_get(PDO_NAME . 'order', ['id' => $orderid], ['price', 'orderno', 'specid']);
            $goodsname = pdo_getcolumn(PDO_NAME . "citycard_lists", ['id' => $order['specid']], 'name');
            $orderno = $order['orderno'];
            $orderstatus = '开通名片';
            $url = h5_url('pages/subPages/businesscard/carddetail/renewcarddetail');
        } else if ($plugin == 'mobilerecharge') {
            $order = pdo_get(PDO_NAME . 'mrecharge_order', ['id' => $orderid], ['price','money' ,'orderno']);
            $goodsname = '充值'.$order['money'].'元';
            $orderno = $order['orderno'];
            $orderstatus = '话费充值';
        }
        $nickname = pdo_getcolumn(PDO_NAME . 'member', array('id' => $sendmid), 'nickname');
        if ($settings['mode']) {
            if ($senddisid == $disorder['oneleadid']) {
                $lowrank = '一级';
                if ($senddisid == $disorder['twoleadid']) {
                    $leadmoney = $money['two'] + $money['one'];
                } else {
                    $leadmoney = $money['one'];
                }
            } else if ($senddisid == $disorder['twoleadid']) {
                $lowrank = '二级';
                $leadmoney = $money['two'];
            }
        } else {
            if ($senddisid == $disorder['oneleadid']) {
                $lowrank = '一级';
                $leadmoney = $money['one'];
            } else if ($senddisid == $disorder['twoleadid']) {
                $lowrank = '二级';
                $leadmoney = $money['two'];
            } else if ($senddisid == $disorder['threeleadid']) {
                $lowrank = '三级';
                $leadmoney = $money['three'];
            }
        }
        if ($plugin == 'taxipay') {
            $leadmoney = $order['price'];
            $lowrank = '乘客';
        }
        if ($flag == 1) {
            $keyword1 = $settings['noticetitle3'] ? $settings['noticetitle3'] : '下级付款通知';
            $keyword2 = '下级已付款';
            $remark = $settings['noticecontent3'];
        } else {
            $keyword1 = $settings['noticetitle4'] ? $settings['noticetitle4'] : '佣金到账通知';
            $keyword2 = '已完成';
            $remark = $settings['noticecontent4'];
        }

        $keyword1 = str_replace('[昵称]', $nickname, $keyword1);
        $keyword1 = str_replace('[下级昵称]', $buyname, $keyword1);
        $keyword1 = str_replace('[时间]', date('Y-m-d H:i:s', time()), $keyword1);
        $keyword1 = str_replace('[下线层级]', $lowrank, $keyword1);
        $keyword1 = str_replace('[佣金金额]', $leadmoney, $keyword1);
        $keyword1 = str_replace('[订单金额]', $disorder['orderprice'], $keyword1);
        $keyword1 = str_replace('[订单编号]', $orderno, $keyword1);
        $keyword1 = str_replace('[订单类型]', $orderstatus, $keyword1);
        $keyword1 = str_replace('[商品名称]', $goodsname, $keyword1);

        $remark = str_replace('[昵称]', $nickname, $remark);
        $remark = str_replace('[下级昵称]', $buyname, $remark);
        $remark = str_replace('[时间]', date('Y-m-d H:i:s', time()), $remark);
        $remark = str_replace('[下线层级]', $lowrank, $remark);
        $remark = str_replace('[佣金金额]', $leadmoney, $remark);
        $remark = str_replace('[订单金额]', $disorder['orderprice'], $remark);
        $remark = str_replace('[订单编号]', $orderno, $remark);
        $remark = str_replace('[订单类型]', $orderstatus, $remark);
        $remark = str_replace('[商品名称]', $goodsname, $remark);
        if ($flag == 2) {
            $distormoney = pdo_get(PDO_NAME . 'distributor', array('id' => $senddisid), array('nowmoney', 'dismoney'));
            $remark = str_replace('[可提现佣金]', $distormoney['nowmoney'], $remark);
            $remark = str_replace('[总获得佣金]', $distormoney['dismoney'], $remark);
            $keyword1 = str_replace('[可提现佣金]', $distormoney['nowmoney'], $keyword1);
            $keyword1 = str_replace('[总获得佣金]', $distormoney['dismoney'], $keyword1);
        }
        $first = '您有一个新的业务通知';
        $fxstext = !empty($_W['wlsetting']['trade']['fxstext']) ? $_W['wlsetting']['trade']['fxstext'] : '分销商';
        $data = array(
            'first'   => $first,
            'type'    => $keyword1,//业务类型
            'content' => $fxstext . ':[' . $nickname . ']',//业务内容
            'status'  => $keyword2,//处理结果
            'time'    => date('Y-m-d H:i:s', time()),//操作时间
            'remark'  => $remark
        );

        if ($remark != '') {
            TempModel::sendInit('service', $sendmid, $data, $_W['source'], $url);
        }
    }

    //添加明细
    static function adddisdetail($disorderid, $leadid, $buymid, $type, $price, $plugin, $rank, $reason = '', $nowmoney, $checkcode = '', $status = 0, $aid = 0)
    {
        global $_W;
        $data = array(
            'uniacid'    => $_W['uniacid'],
            'disorderid' => $disorderid,
            'leadid'     => $leadid,
            'buymid'     => $buymid,
            'type'       => $type,
            'price'      => $price,
            'createtime' => time(),
            'plugin'     => $plugin,
            'rank'       => $rank,
            'reason'     => $reason,
            'nowmoney'   => $nowmoney,
            'checkcode'  => $checkcode,
            'status'     => $status,
            'aid'        => $aid
        );
        pdo_insert(PDO_NAME . 'disdetail', $data);
    }

    //等级变更提示
    static function levelupNotice($disid, $oldlevelid, $newlevelid)
    {
        global $_W;
        $settings = Setting::wlsetting_read('distribution');
        $url = h5_url('pages/subPages/dealer/index/index');
        $time = date('Y-m-d H:i:s', time());
        $mid = pdo_getcolumn('wlmerchant_distributor', array('id' => $disid), 'mid');
        $member = pdo_get('wlmerchant_member', array('id' => $mid), array('openid', 'nickname'));
        $nickname = $member['nickname'];
        $oldlevel = pdo_get('wlmerchant_dislevel', array('id' => $oldlevelid), array('name', 'onecommission', 'twocommission', 'threecommission'));
        $newlevel = pdo_get('wlmerchant_dislevel', array('id' => $newlevelid), array('name', 'onecommission', 'twocommission', 'threecommission'));
        $keyword1 = $settings['noticetitle8'] ? $settings['noticetitle8'] : '分销商等级升级通知';
        $keyword2 = '已升级';
        $remark = $settings['noticecontent8'];

        $keyword1 = str_replace('[昵称]', $member['nickname'], $keyword1);
        $keyword1 = str_replace('[旧等级]', $oldlevel['name'], $keyword1);
        $keyword1 = str_replace('[旧一级分销比例]', $oldlevel['onecommission'], $keyword1);
        $keyword1 = str_replace('[旧二级分销比例]', $oldlevel['twocommission'], $keyword1);
        $keyword1 = str_replace('[旧三级分销比例]', $oldlevel['threecommission'], $keyword1);
        $keyword1 = str_replace('[新等级]', $newlevel['name'], $keyword1);
        $keyword1 = str_replace('[新一级分销比例]', $newlevel['onecommission'], $keyword1);
        $keyword1 = str_replace('[新二级分销比例]', $newlevel['twocommission'], $keyword1);
        $keyword1 = str_replace('[新三级分销比例]', $newlevel['threecommission'], $keyword1);
        $keyword1 = str_replace('[时间]', $time, $keyword1);

        $remark = str_replace('[昵称]', $member['nickname'], $remark);
        $remark = str_replace('[旧等级]', $oldlevel['name'], $remark);
        $remark = str_replace('[旧一级分销比例]', $oldlevel['onecommission'], $remark);
        $remark = str_replace('[旧二级分销比例]', $oldlevel['twocommission'], $remark);
        $remark = str_replace('[旧三级分销比例]', $oldlevel['threecommission'], $remark);
        $remark = str_replace('[新等级]', $newlevel['name'], $remark);
        $remark = str_replace('[新一级分销比例]', $newlevel['onecommission'], $remark);
        $remark = str_replace('[新二级分销比例]', $newlevel['twocommission'], $remark);
        $remark = str_replace('[新三级分销比例]', $newlevel['threecommission'], $remark);
        $remark = str_replace('[时间]', $time, $remark);

        $first = '您有一个新的业务通知';
        $fxstext = $_W['wlsetting']['trade']['fxstext'] ? $_W['wlsetting']['trade']['fxstext'] : "分销商";
        $data = array(
            'first'   => $first,
            'type'    => $keyword1,//业务类型
            'content' => $fxstext . ':[' . $nickname . ']',//业务内容
            'status'  => $keyword2,//处理结果
            'time'    => date('Y-m-d H:i:s', time()),//操作时间
            'remark'  => $remark
        );
        if ($remark != '') {
            TempModel::sendInit('service', $mid, $data, $_W['source'], $url);
        }
    }

    //退款分销订单
    static function refunddis($id, $checkcode = '')
    {
        global $_W;
        $order = pdo_get('wlmerchant_disorder', array('id' => $id));
        if ($order['neworderflag']) {
            $onemoney = 0;
            $twomoney = 0;
            if ($checkcode) {
                $smallorder = pdo_getall('wlmerchant_smallorder', array('disorderid' => $id, 'status' => 3, 'checkcode' => $checkcode));
            } else {
                $smallorder = pdo_getall('wlmerchant_smallorder', array('disorderid' => $id, 'status' => 3));
            }
            foreach ($smallorder as $key => $small) {
                if ($small['dissettletime'] > 0) {
                    if ($small['onedismoney'] > 0) {
                        $one = pdo_get('wlmerchant_distributor', array('id' => $small['oneleadid']));
                        $onedismoney = $one['dismoney'] - $small['onedismoney'];
                        $onenowmoney = $one['nowmoney'] - $small['onedismoney'];
                        pdo_update('wlmerchant_distributor', array('dismoney' => $onedismoney, 'nowmoney' => $onenowmoney), array('id' => $one['id']));
                        $leadid = pdo_getcolumn('wlmerchant_distributor', array('id' => $order['oneleadid']), 'mid');
                        self::adddisdetail($order['id'], $leadid, $order['buymid'], 2, $small['onedismoney'], $order['plugin'], 1, '订单退款减佣', $onenowmoney, $small['checkcode']);
                    }
                    if ($small['twodismoney'] > 0) {
                        $two = pdo_get('wlmerchant_distributor', array('id' => $small['twoleadid']));
                        $twodismoney = $two['dismoney'] - $small['twodismoney'];
                        $twonowmoney = $two['nowmoney'] - $small['twodismoney'];
                        pdo_update('wlmerchant_distributor', array('dismoney' => $twodismoney, 'nowmoney' => $twonowmoney), array('id' => $two['id']));
                        $leadid = pdo_getcolumn('wlmerchant_distributor', array('id' => $order['twoleadid']), 'mid');
                        self::adddisdetail($order['id'], $leadid, $order['buymid'], 2, $small['twodismoney'], $order['plugin'], 1, '订单退款减佣', $twonowmoney, $small['checkcode']);
                    }
                }
                $onemoney += $small['onedismoney'];
                $twomoney += $small['twodismoney'];
            }
            $leadmoneys = unserialize($order['leadmoney']);
            $leadmoneys['one'] = sprintf("%.2f", $leadmoneys['one'] - $onemoney);
            if($leadmoneys['one']<0){$leadmoneys['one'] = 0;}
            $leadmoneys['two'] = sprintf("%.2f", $leadmoneys['two'] - $twomoney);
            if($leadmoneys['two']<0){$leadmoneys['two'] = 0;}
            $orderdata['leadmoney'] = serialize($leadmoneys);
            //判断状态
            $overflag = pdo_get('wlmerchant_smallorder', array('disorderid' => $id, 'status' => 1), array('id'));
            if (empty($overflag)) {
                $hexiao = pdo_get('wlmerchant_smallorder', array('disorderid' => $id, 'status' => 2), array('id'));
                if ($hexiao) {
                    $orderdata['status'] = 2;
                } else {
                    $orderdata['status'] = 3;
                }
            }
            pdo_update('wlmerchant_disorder', $orderdata, array('id' => $id));
        } else {
            if ($order['status'] == 2) {
                $leadmoneys = unserialize($order['leadmoney']);
                $one = pdo_get('wlmerchant_distributor', array('id' => $order['oneleadid']));
                if ($leadmoneys['one'] > 0) {
                    $onedismoney = $one['dismoney'] - $leadmoneys['one'];
                    $onenowmoney = $one['nowmoney'] - $leadmoneys['one'];
                    pdo_update('wlmerchant_distributor', array('dismoney' => $onedismoney, 'nowmoney' => $onenowmoney), array('id' => $one['id']));
                    $leadid = pdo_getcolumn('wlmerchant_distributor', array('id' => $order['oneleadid']), 'mid');
                    self::adddisdetail($order['id'], $leadid, $order['buymid'], 2, $leadmoneys['one'], $order['plugin'], 1, '订单退款', $onenowmoney);
                }
                if ($order['twoleadid'] && $leadmoneys['two'] > 0) {
                    $two = pdo_get('wlmerchant_distributor', array('id' => $order['twoleadid']));
                    $twodismoney = $two['dismoney'] - $leadmoneys['two'];
                    $twonowmoney = $two['nowmoney'] - $leadmoneys['two'];
                    pdo_update('wlmerchant_distributor', array('dismoney' => $twodismoney, 'nowmoney' => $twonowmoney), array('id' => $two['id']));
                    $leadid = pdo_getcolumn('wlmerchant_distributor', array('id' => $order['twoleadid']), 'mid');
                    self::adddisdetail($order['id'], $leadid, $order['buymid'], 2, $leadmoneys['two'], $order['plugin'], 2, '订单退款', $twonowmoney);
                }
            }
        }


    }

    static function dissettlement($id)
    {
        global $_W;
        $order = pdo_get('wlmerchant_disorder', array('id' => $id));
        $nosetflag = pdo_getcolumn('wlmerchant_disdetail', array('disorderid' => $order['id'], 'plugin' => $order['plugin'], 'status' => 0), 'id');
        if (empty($nosetflag)) {
            $leadmoneys = unserialize($order['leadmoney']);
            $one = pdo_get('wlmerchant_distributor', array('id' => $order['oneleadid']));
            if ($leadmoneys['one'] > 0) {
                $onedismoney = $one['dismoney'] + $leadmoneys['one'];
                $onenowmoney = $one['nowmoney'] + $leadmoneys['one'];
                $res1 = pdo_update('wlmerchant_distributor', array('dismoney' => $onedismoney, 'nowmoney' => $onenowmoney), array('id' => $one['id']));
                self::checkup($one['id']);
                $leadid = pdo_getcolumn('wlmerchant_distributor', array('id' => $order['oneleadid']), 'mid');
                self::adddisdetail($order['id'], $leadid, $order['buymid'], 1, $leadmoneys['one'], $order['plugin'], 1, '订单结算', $onenowmoney);
            }
            if ($order['twoleadid'] && $leadmoneys['two'] > 0) {
                $two = pdo_get('wlmerchant_distributor', array('id' => $order['twoleadid']));
                $twodismoney = $two['dismoney'] + $leadmoneys['two'];
                $twonowmoney = $two['nowmoney'] + $leadmoneys['two'];
                $res2 = pdo_update('wlmerchant_distributor', array('dismoney' => $twodismoney, 'nowmoney' => $twonowmoney), array('id' => $two['id']));
                self::checkup($two['id']);

                $leadid = pdo_getcolumn('wlmerchant_distributor', array('id' => $order['twoleadid']), 'mid');
                self::adddisdetail($order['id'], $leadid, $order['buymid'], 1, $leadmoneys['two'], $order['plugin'], 2, '订单结算', $twonowmoney);
            }
            //团长分红
            if ($order['onegroupid'] && $order['onegroupmoney'] > 0) {
                $onegroup = pdo_get('wlmerchant_distributor', array('mid' => $order['onegroupid']));
                $onegroupdismoney = $onegroup['dismoney'] + $order['onegroupmoney'];
                $onegroupnowmoney = $onegroup['nowmoney'] + $order['onegroupmoney'];
                $res3 = pdo_update('wlmerchant_distributor', array('dismoney' => $onegroupdismoney, 'nowmoney' => $onegroupnowmoney), array('id' => $onegroup['id']));
                self::adddisdetail($order['id'], $order['onegroupid'], $order['buymid'], 1, $order['onegroupmoney'], $order['plugin'], 1, '团长分红结算', $onegroupnowmoney,0,2);
            }
            if ($order['twogroupid'] && $order['twogroupmoney'] > 0) {
                $twogroup = pdo_get('wlmerchant_distributor', array('mid' => $order['twogroupid']));
                $twogroupdismoney = $twogroup['dismoney'] + $order['twogroupmoney'];
                $twogroupnowmoney = $twogroup['nowmoney'] + $order['twogroupmoney'];
                $res4 = pdo_update('wlmerchant_distributor', array('dismoney' => $twogroupdismoney, 'nowmoney' => $twogroupnowmoney), array('id' => $twogroup['id']));
                self::adddisdetail($order['id'], $order['twogroupid'], $order['buymid'], 1, $order['twogroupmoney'], $order['plugin'], 2, '团长分红结算', $twogroupnowmoney,0,2);
            }

            if($order['shareholdermoney'] > 0){
                $allshareholder = pdo_getall('wlmerchant_distributor',array('uniacid' => $order['uniacid'],'shareholder' => 1),array('id','mid'));
                if(!empty($allshareholder)){
                    $sharenum = count($allshareholder);
                    $sharemoney = sprintf("%.2f",$order['shareholdermoney']/$sharenum);
                    foreach ($allshareholder as $share){
                        $res5 = pdo_query("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney+{$sharemoney},nowmoney=nowmoney+{$sharemoney} WHERE id = {$share['id']}");
                        if($res5){
                            $onegrmoney = pdo_getcolumn(PDO_NAME.'distributor',array('id'=> $share['id']),'nowmoney');
                            Distribution::adddisdetail($order['id'],$share['mid'],$order['buymid'],1,$sharemoney,$order['plugin'],0,'订单股东分红',$onegrmoney,0,3);
                        }
                    }
                }
            }


            if ($res1 || $res2 || $res3 || $res4 || $res5) {
                pdo_update('wlmerchant_disorder', array('status' => 2), array('id' => $order['id']));
                if (!empty($order['neworderflag'])) {
                    pdo_update('wlmerchant_smallorder', array('dissettletime' => time()), array('disorderid' => $id));
                }
            } else if ($leadmoneys['one'] < 0.01 && $leadmoneys['two'] < 0.01) {
                pdo_update('wlmerchant_disorder', array('status' => 2), array('id' => $order['id']));
                return true;
            }
            //发消息
            if ($leadmoneys['one'] > 0) {
                self::moneyNotice($order['buymid'], $order['plugin'], $order['orderid'], $order['oneleadid'], $order['id'], 2);
            }
            if ($order['twoleadid'] != $order['oneleadid'] && $order['twoleadid'] && $leadmoneys['two'] > 0) {
                self::moneyNotice($order['buymid'], $order['plugin'], $order['orderid'], $order['twoleadid'], $order['id'], 2);
            }
//            if ($order['threeleadid'] && $leadmoneys['three'] > 0) {
//                $three = pdo_get('wlmerchant_distributor', array('id' => $order['threeleadid']));
//                $threedismoney = $three['dismoney'] + $leadmoneys['three'];
//                $threenowmoney = $three['nowmoney'] + $leadmoneys['three'];
//                pdo_update('wlmerchant_distributor', array('dismoney' => $threedismoney, 'nowmoney' => $threenowmoney), array('id' => $three['id']));
//                self::checkup($three['id']);
//                self::moneyNotice($order['buymid'], $order['plugin'], $order['orderid'], $order['threeleadid'], $order['id'], 2);
//                $leadid = pdo_getcolumn('wlmerchant_distributor', array('id' => $order['threeleadid']), 'mid');
//                self::adddisdetail($order['id'], $leadid, $order['buymid'], 1, $leadmoneys['three'], $order['plugin'], 3, '订单结算', $threenowmoney);
//            }
            if ($res1 || $res2 || $res3 || $res4 || $res5) {
                return true;
            }
        } else {
            return true;
        }
    }

    //计划任务
    static function doTask()
    {
        global $_W;
        //删除未支付的过期订单
        $overduetime = time() - 6000;
        $overdueorders = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_order') . "WHERE uniacid = {$_W['uniacid']} AND status = 0 AND plugin = 'distribution' AND createtime < {$overduetime} ORDER BY id DESC");
        if ($overdueorders) {
            foreach ($overdueorders as $key => $over) {
                pdo_delete('wlmerchant_order', array('id' => $over['id']));
            }
        }
        //过期会员禁用分销功能
        $uniacids = pdo_fetchall('select distinct uniacid from ' . tablename(PDO_NAME . 'setting') . " WHERE uniacid != -1");
        foreach ($uniacids as $unia) {
            $_W['uniacid'] = $unia['uniacid'];
            $settings = Setting::wlsetting_read('distribution');
            if ($settings['bindvip']) {
                $now = time();
                //标志过期
                if ($settings['bindvip'] == 2) {
                    $overdis = pdo_fetchall("SELECT id,mid FROM " . tablename('wlmerchant_distributor') . "WHERE uniacid = {$_W['uniacid']} AND disflag = 1 AND expiretime < {$now} AND source = 0");
                } else {
                    $overdis = pdo_fetchall("SELECT id,mid FROM " . tablename('wlmerchant_distributor') . "WHERE uniacid = {$_W['uniacid']} AND disflag = 1 AND expiretime < {$now} ");
                }
                if ($overdis) {
                    foreach ($overdis as $key => &$dis) {
                        $halfmember = pdo_fetch("SELECT expiretime FROM " . tablename('wlmerchant_halfcardmember') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$dis['mid']} ORDER BY expiretime DESC");
                        if ($halfmember['expiretime'] < $now) {
                            pdo_update('wlmerchant_distributor', array('disflag' => -1, 'expiretime' => $halfmember['expiretime']), array('id' => $dis['id']));
                        } else {
                            pdo_update('wlmerchant_distributor', array('expiretime' => $halfmember['expiretime']), array('id' => $dis['id']));
                        }
                    }
                }
                //重新启用
                $nodis = pdo_fetchall("SELECT id,mid FROM " . tablename('wlmerchant_distributor') . "WHERE uniacid = {$_W['uniacid']} AND disflag = -1 ");
                if ($nodis) {
                    foreach ($nodis as $key => &$nod) {
                        $half = pdo_fetch("SELECT expiretime FROM " . tablename('wlmerchant_halfcardmember') . "WHERE uniacid = {$_W['uniacid']} AND mid = {$nod['mid']} ORDER BY expiretime DESC");
                        if ($half['expiretime'] > $now) {
                            pdo_update('wlmerchant_distributor', array('disflag' => 1, 'expiretime' => $half['expiretime']), array('id' => $nod['id']));
                        }
                    }
                }
                //启用后台添加的
                if ($settings['bindvip'] == 2) {
                    $nodis2 = pdo_fetchall("SELECT id,mid FROM " . tablename('wlmerchant_distributor') . "WHERE uniacid = {$_W['uniacid']} AND disflag = -1 AND source = 1");
                    if ($nodis2) {
                        foreach ($nodis2 as $key => &$nod2) {
                            pdo_update('wlmerchant_distributor', array('disflag' => 1), array('id' => $nod2['id']));
                        }
                    }
                }
            } else {
                pdo_update('wlmerchant_distributor', array('disflag' => 1), array('uniacid' => $_W['uniacid'], 'disflag' => -1));
            }
            //336定制 自动提现申请
            if(Customized::init('customized336') && intval($settings['auth_withdraw']) == 1 && $settings['auth_withdraw_money'] >= 1) {
                $list = pdo_getall(PDO_NAME."distributor",
                                   ['uniacid'=>$_W['uniacid'],'disflag'=>1,'nowmoney >='=>$settings['auth_withdraw_money']],
                                   ['id','mid','aid','nowmoney']);
                if(is_array($list) && count($list) > 0){
                    foreach($list as $disKey => $disVal){
                        //用户信息获取    渠道：1=公众号（默认）；2=h5；3=小程序
                        $user = pdo_get(PDO_NAME."member",['id'=>$disVal['mid']],['openid','wechat_openid']);
                        if($user['openid']){
                            //公众号openid存在  使用公众号账号进行收款操作
                           $openid = $user['openid'];
                           $source = 1;
                        }else if($user['wechat_openid']){
                            //公众号账号不存在  小程序账号信息存在  使用小程序进行收款操作
                            $openid = $user['wechat_openid'];
                            $source = 3;
                        }else{
                            //公众号和小程序账号信息都不存在  取消自动提现操作
                            continue;
                        }
                        //提现金额计算
                        $appmoney = sprintf("%.2f", $disVal['nowmoney']);//提现金额为所有佣金
                        $spercentmoney = sprintf("%.2f", $appmoney * $settings['withdrawcharge'] / 100);//提现手续费获取
                        $money = sprintf("%.2f", $appmoney - $spercentmoney);//实际提现金额
                        $nowmoney = sprintf("%.2f", $disVal['nowmoney'] - $appmoney);//提现后剩余金额
                        //生成提现申请操作数据信息
                        $data = [
                            'uniacid'       => $_W['uniacid'] ,
                            'aid'           => $disVal['aid'] ,
                            'status'        => 7 ,
                            'type'          => 3 ,
                            'mid'           => $disVal['mid'],
                            'sopenid'       => $openid ,
                            'disid'         => $disVal['id'] ,
                            'sgetmoney'     => $money ,
                            'sapplymoney'   => $appmoney ,
                            'spercentmoney' => $spercentmoney ,
                            'spercent'      => sprintf("%.4f" , ($appmoney - $money) / $appmoney * 100) ,
                            'applytime'     => time() ,
                            'payment_type'  => 2 ,
                            'source'        => $source
                        ];
                        $cashsets = Setting::wlsetting_read('cashset');
                        if ($cashsets['disnoaudit']) {
                            $data['status'] = 3;
                            $trade_no = time() . random(4, true);
                            $data['trade_no'] = $trade_no;
                            $data['updatetime'] = time();
                        }
                        $res = pdo_insert(PDO_NAME . "settlement_record", $data);
                        $disorderid = pdo_insertid();
                        if($res){
                            //判断上花覅开启自动打款
                            if ($cashsets['disautocash']) Queue::addTask(4, $disorderid, time(), $disorderid);
                            //记录金额变更
                            Distribution::adddisdetail($disorderid, $disVal['mid'], $disVal['mid'], 2, $appmoney, 'cash', 1, '佣金自动提现', $nowmoney);
                            //修改当前分销商的可提现金额
                            pdo_update(PDO_NAME."distributor",['nowmoney'=>$nowmoney],['id'=>$disVal['id']]);
                        }
                    }
                }
            }
        }
        //修改退款分销订单状态
        $rushrefund = pdo_fetchall("SELECT disorderid,id FROM " . tablename('wlmerchant_rush_order') . "WHERE status = 7 AND disorderid > 0 AND redisstatus = 0 ORDER BY id DESC LIMIT 0,20 ");
        if ($rushrefund) {
            foreach ($rushrefund as $key => $rush) {
                pdo_update('wlmerchant_disorder', array('status' => 3), array('id' => $rush['disorderid']));
                pdo_update('wlmerchant_rush_order', array('redisstatus' => 1), array('id' => $rush['id']));
            }
        }

        $orderrefund = pdo_fetchall("SELECT disorderid,id FROM " . tablename('wlmerchant_order') . "WHERE status = 7 AND disorderid > 0 AND redisstatus = 0 ORDER BY id DESC LIMIT 0,20 ");
        if ($orderrefund) {
            foreach ($orderrefund as $key => $order) {
                pdo_update('wlmerchant_disorder', array('status' => 3), array('id' => $order['disorderid']));
                pdo_update('wlmerchant_order', array('redisstatus' => 1), array('id' => $order['id']));
            }
        }

    }

    static function getgzqrcode($mid)
    {
        global $_W;
        $qrid = pdo_getcolumn(PDO_NAME . 'qrcode', array('uniacid' => $_W['uniacid'], 'sid' => $mid, 'type' => 1, 'status' => 1, 'remark' => '分销关注二维码:weliam_smartcity'), 'qrid');
        $qrcode = pdo_get('qrcode', array('uniacid' => $_W['uniacid'], 'status' => 1, 'id' => $qrid, 'keyword' => 'weliam_smartcity_distribution'));
        //判断是否存在二维码或者二维码是否已经过期
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
            Weixinqrcode::createkeywords('分销关注二维码:Distribution', 'weliam_smartcity_distribution');
            //判断是生成普通二维码 还是生成永久二维码
            $posterType = Setting::wlsetting_read('distribution');
            $posterType = $posterType['posterType'];
            if ($posterType == 1) {
                $qrctype = 1;//普通二维码
            } else {
                $qrctype = 2;//永久二维码
            }
            $result = Weixinqrcode::createqrcode('分销关注二维码:Distribution', 'weliam_smartcity_distribution', 1, $qrctype, -1, '分销关注二维码:weliam_smartcity');
            if (!is_error($result)) {
                $qrid = $result;
                pdo_update(PDO_NAME . 'qrcode', array('sid' => $mid), array('uniacid' => $_W['uniacid'], 'qrid' => $qrid));
            }
        }
        $qrurl = pdo_get('qrcode', array('id' => $qrid, 'uniacid' => $_W['uniacid']), array('url', 'ticket'));
        return $qrurl;
    }

    static function Processor($message)
    {
        global $_W;
        if (strtolower($message['msgtype']) == 'event') {
            //获取数据
            $returnmess = array();
            $qrid = Weixinqrcode::get_qrid($message);

            $mid = pdo_getcolumn(PDO_NAME . 'qrcode', array('uniacid' => $_W['uniacid'], 'qrid' => $qrid), 'sid');

            $base = Setting::wlsetting_read('distribution');
            $pagepath = ($base['qrcodeurlstatus'] == 1) ? 'pages/mainPages/index/index?head_id=' . $mid : 'pages/subPages/dealer/index/index?head_id=' . $mid;

            if(empty($_W['attachurl_remote'])){
                $uni_remote_setting = uni_setting_load('remote');
                $_W['attachurl_remote'] = $uni_remote_setting['remote']['alioss']['url'].'/';
            }
            if ($base['replytype'] == 1) {
                $returnmess = array('title' => urlencode($base['gztitle']), 'appid' => $_W['wlsetting']['wxapp_config']['appid'], 'path' => tomedia($base['gzthumb']), 'pagepath' => $pagepath);
                Weixinqrcode::send_wxapp($returnmess, $message);
            } else {
                $returnmess[] = array('title' => urlencode($base['gztitle']), 'description' => urlencode($base['gzdesc']), 'picurl' => tomedia($base['gzthumb']), 'url' => h5_url($pagepath));
                Weixinqrcode::send_news($returnmess, $message);
            }
            if($message['event'] == 'subscribe'){
                $laterflag = 1;
            }else{
                $laterflag = 0;
            }
            Distribution::addJunior($mid, $_W['wlmember']['id'],'',1,$laterflag);
        }
    }


    /**
     * Comment: 根据条件获取分销结算收益信息
     * Author: zzw
     * Date: 2019/7/16 13:55
     * @param $where
     * @return mixed
     */
    public static function getDisOrder($where = '', $field = '*')
    {
        global $_W, $_GPC;
        #1、条件生成
        !empty($where) && $where .= " AND ";
        $where .= " a.uniacid = {$_W['uniacid']} ";//a.aid = {$_W['aid']} AND
        #2、查询语句生成
        $sql = "SELECT {$field} FROM " . tablename(PDO_NAME . "disorder")
            . " a LEFT JOIN "
            . tablename(PDO_NAME . "disdetail")
            . " b ON a.id = b.disorderid ";
        !empty($where) && $sql .= " WHERE {$where} ";
        #3、获取信息数据
        $result = pdo_fetchall($sql);

        return $result;
    }

    /**
     * Comment: 获取分销商提现申请信息状态列表
     * Author: zzw
     * Date: 2019/7/24 9:21
     * @return array
     */
    public static function getCashWithdrawalStateList()
    {
        $list = [
            [
                'title'  => '待审核',
                'status' => [2, 6, 7]
            ],//待审核
            [
                'title'  => '待打款',
                'status' => [3, 8]
            ],//待打款
            [
                'title'  => '已完成',
                'status' => [4, 5, 9]
            ],//已完成
            [
                'title'  => '未通过',
                'status' => [-1, 10, 11]
            ],//未通过

        ];

        return $list;
    }

    /**
     * Comment: 根据状态值 获取当前状态的详细信息
     * Author: zzw
     * Date: 2019/7/24 9:29
     * @param $status
     * @return bool
     */
    public static function getStatusDetailInfo($status)
    {
        $list = self::getCashWithdrawalStateList();
        $info = [
            'title'  => '状态错误',
            'status' => $status
        ];
        foreach ($list as $k => $v) {
            if (in_array($status, $v['status'])) {
                $info = $v;
            }
        }
        return $info;
    }

    /**
     * 电商联盟定制分销同步到其他模块
     * @param $member
     * @return mixed
     */
    public static function initUserInfo($member)
    {
        $hcmember = pdo_get('hccard_user', array('uniacid' => $member['uniacid'], 'openid' => $member['openid']));
        if (empty($hcmember)) {
            $gender = pdo_getcolumn('mc_members', array('uid' => $member['uid']), 'gender');
            $hcmember = [
                'uniacid'    => $member['uniacid'],
                'openid'     => $member['openid'],
                'nickname'   => $member['nickname'],
                'headimgurl' => tomedia($member['avatar']),
                'username'   => $member['nickname'],
                'tel'        => $member['mobile'],
                'createtime' => time(),
                'level'      => 2,
                'gender'     => $gender
            ];
            pdo_insert('hccard_user', $hcmember);
            $hcmember['id'] = pdo_insertid();
        }
        return $hcmember;
    }


    /**
     * Comment: 858定制 获取用户已经消费金额
     * Author: wlf
     * Date: 2022/08/30 15:42
     * @param $status
     * @return bool
     */
    public static function getNowMoney($mid){
        $rush = pdo_fetchcolumn('SELECT SUM(actualprice) FROM '.tablename('wlmerchant_rush_order')." WHERE mid = {$mid} AND status IN (1,2,3,4,5,6,8,9)");
        $half = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename('wlmerchant_halfcard_record')." WHERE mid = {$mid} AND status = 1");
        $order = pdo_fetchcolumn('SELECT SUM(price) FROM '.tablename('wlmerchant_order')." WHERE mid = {$mid} AND status IN (1,2,3,4,5,6,8,9) AND payfor != 'recharge' ");
        $nowmoney = sprintf("%.2f",$rush + $half + $order);
        return $nowmoney;
    }

    /**
     * Comment:  修改团员的团长信息
     * Author: wlf
     * Date: 2022/10/19 17:35
     * @param $status
     * @return bool
     */
    public static function changeGroupId($mid,$groupid,$twogroupid = 0){
        $lowpeople = pdo_getall('wlmerchant_distributor',array('leadid' => $mid,'groupflag' => 0),array('id','mid','onegroupid'));
        if(!empty($lowpeople)){
            foreach ($lowpeople as &$peo){
                if(empty($twogroupid)){
                    $twogroupid = $peo['onegroupid'];
                }
                pdo_update('wlmerchant_distributor',array('onegroupid' => $groupid,'twogroupid' => $twogroupid),array('id' => $peo['id']));
                self::changeGroupId($peo['mid'],$groupid,$twogroupid);
            }
        }
        $lowgroup = pdo_getall('wlmerchant_distributor',array('leadid' => $mid,'groupflag' => 1),array('id','mid','onegroupid'));
        if(!empty($lowgroup)){
        	foreach ($lowgroup as &$gro){
        		if(empty($twogroupid)){
                    $twogroupid = $gro['onegroupid'];
                }
        		pdo_update('wlmerchant_distributor',array('onegroupid' => $groupid,'twogroupid' => $twogroupid),array('id' => $gro['id']));
        		pdo_update('wlmerchant_distributor',array('twogroupid' => $groupid),array('onegroupid' => $gro['mid']));
        	}
        }
    }

}
