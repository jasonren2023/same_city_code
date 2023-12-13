<?php
defined('IN_IA') or exit('Access Denied');

class Dissysbase_WeliamController {
    //分销商列表
    public function distributorlist() {
        global $_W, $_GPC;
        $todo = $_GPC['todo'] ? $_GPC['todo'] : 'dislist';
        $base = Setting::wlsetting_read('distribution');
        $dislevels = pdo_fetchall("SELECT id,name FROM " . tablename('wlmerchant_dislevel') . "WHERE uniacid = {$_W['uniacid']} ORDER BY isdefault DESC,id ASC");
        if ($todo == 'dislist') {
            $pindex = max(1, intval($_GPC['page']));
            $psize = 10;
            $wheres = array();
            $wheres['uniacid'] = $_W['uniacid'];
            if(empty($_GPC['disflag'])){
                $wheres['disflag#'] = "(1,-1)";
            }else{
                $wheres['disflag'] = $_GPC['disflag'];
            }
            $type = intval($_GPC['type']);
            $keyword = trim($_GPC['keyword']);
            if (!empty($keyword)) {
                switch ($type) {
                    case 2 :
                        $wheres['mobile@'] .= $keyword;
                        break;
                    case 3 :
                        $wheres['nickname@'] .= $keyword;
                        break;
                    case 4:
                        $wheres['realname@'] .= $keyword;
                        break;
                    case 5:
                        $wheres['mid@'] .= $keyword;
                        break;
                }
            }

            if ($_GPC['time_limit']) {
                $time_limit = $_GPC['time_limit'];
                $starttime = strtotime($_GPC['time_limit']['start']);
                $endtime = strtotime($_GPC['time_limit']['end']);
                if ($_GPC['timetype']) {
                    $wheres['createtime>'] = $starttime;
                    $wheres['createtime<'] = $endtime;
                }
            }
            if (empty($starttime) || empty($endtime)) {
                $starttime = strtotime('-1 month');
                $endtime = time();
            }

            //分销商等级
            if ($_GPC['levelid']) {
                $default = pdo_getcolumn(PDO_NAME . 'dislevel', array('id' => $_GPC['levelid']), 'isdefault');
                if ($default) {
                    $wheres['dislevel#'] = "(0," . intval($_GPC['levelid']) . ")";
                } else {
                    $wheres['dislevel'] = intval($_GPC['levelid']);
                }
            }

            if ($_GPC['export'] != '') {
                $this->exportlist($wheres);
            }
            $list = Distribution::getNumDistributor('*', $wheres, 'updatetime DESC,createtime DESC', $pindex, $psize, 1);
            $pager = $list[1];
            $list = $list[0];
            foreach ($list as $key => &$v) {
                if(empty($v['updatetime'])){
                    $v['updatetime'] = $v['createtime'];
                }
                $mem = pdo_get('wlmerchant_member', array('id' => $v['mid']), array('mobile', 'realname', 'nickname', 'avatar'));
                if (empty($v['mobile']) && $mem['mobile']) {
                    $v['mobile'] = $mem['mobile'];
                    pdo_update('wlmerchant_distributor', array('mobile' => $mem['mobile']), array('id' => $v['id']));
                }
                if (empty($v['realname']) && $mem['realname']) {
                    $v['realname'] = $mem['realname'];
                    pdo_update('wlmerchant_distributor', array('realname' => $mem['realname']), array('id' => $v['id']));
                }
                if (empty($v['nickname']) && $mem['nickname']) {
                    $v['nickname'] = $mem['nickname'];
                    pdo_update('wlmerchant_distributor', array('nickname' => $mem['nickname']), array('id' => $v['id']));
                }
                $v['avatar'] = $mem['avatar'];
                if($base['showlock']){
                    $v['lownum'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_distributor') . " WHERE leadid = {$v['mid']}");
                }else{
                    $v['lownum'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_distributor') . " WHERE leadid = {$v['mid']} AND lockflag = 0 ");
                }

                if ($v['leadid']) {
                    $topname = pdo_get('wlmerchant_member', array('id' => $v['leadid']), array('nickname','mobile','realname'));
                    if ($topname['nickname']) {
                        $v['topname'] = $topname['nickname'];
                    } else {
                        $v['topname'] = $topname['realname'];
                    }
                    $v['topmobile'] = $topname['mobile'];
                }
                if ($v['dislevel']) {
                    $v['rankname'] = pdo_getcolumn(PDO_NAME . 'dislevel', array('id' => $v['dislevel']), 'name');
                } else {
                    $v['rankname'] = pdo_getcolumn(PDO_NAME . 'dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), 'name');
                }
                $v['lowdisnum'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_distributor') . " WHERE leadid = {$v['mid']} AND lockflag = 0 AND disflag = 1");
                if (p('wxplatform')) {
                    $disqrcode = Distribution::getgzqrcode($v['mid']);
                    $v['qrcode'] = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=' . urlencode($disqrcode['ticket']);
                }
            }
        }

        if ($todo == 'adddis') {
            if (checksubmit()) {
                $memberid = $_GPC['memberid'];
                $member = pdo_get('wlmerchant_member', array('id' => $memberid), array('mobile', 'nickname', 'realname'));
                $distributorid = pdo_getcolumn('wlmerchant_member', array('id' => $memberid), 'distributorid');
                if ($distributorid) {
                    $distributor = pdo_get('wlmerchant_distributor', array('id' => $distributorid));
                    if ($distributor['disflag']) {
                        wl_message('不能重复添加', referer(), 'error');
                    } else {
                        $res = pdo_update('wlmerchant_distributor', array('disflag' => 1, 'leadid' => trim($_GPC['leadid']), 'source' => 1, 'lockflag' => 0), array('id' => $distributorid));
                        if ($res) {
                            wl_message('添加成功', web_url('distribution/dissysbase/distributorlist'), 'success');
                        } else {
                            wl_message('添加失败', referer(), 'error');
                        }
                    }
                } else {
                    $data = array(
                        'uniacid'    => $_W['uniacid'],
                        'mid'        => $memberid,
                        'createtime' => time(),
                        'disflag'    => 1,
                        'nickname'   => $member['nickname'],
                        'mobile'     => $member['mobile'],
                        'realname'   => $member['realname'],
                        'leadid'     => trim($_GPC['leadid']),
                        'source'     => 1
                    );
                    if ($data['mid'] > 0) {
                        pdo_insert('wlmerchant_distributor', $data);
                        $disid = pdo_insertid();
                        $res = pdo_update('wlmerchant_member', array('distributorid' => $disid), array('id' => $memberid));
                    } else {
                        $res = 0;
                    }

                    if ($res) {
                        wl_message('添加成功', web_url('distribution/dissysbase/distributorlist'), 'success');
                    } else {
                        wl_message('添加失败', referer(), 'error');
                    }
                }
            }
            if ($_W['wlsetting']['distribution']['mode']) {
                $leadlists = pdo_fetchall("SELECT nickname,mid FROM " . tablename('wlmerchant_distributor') . "WHERE uniacid = {$_W['uniacid']} AND disflag = 1 AND leadid < 0 ORDER BY createtime ASC");
            } else {
                $leadlists = pdo_fetchall("SELECT nickname,mid FROM " . tablename('wlmerchant_distributor') . "WHERE uniacid = {$_W['uniacid']} AND disflag = 1 ORDER BY createtime ASC");
            }
        }

        include wl_template('disysbase/distributorlist');
    }

    public function loworder() {
        global $_W, $_GPC;
        $memid = $_GPC['memid'];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $loworderwhere = array();
        $loworderwhere['uniacid'] = $_W['uniacid'];
        $disid = pdo_getcolumn('wlmerchant_distributor', array('uniacid' => $_W['uniacid'], 'mid' => $memid), 'id');

        $agentlist = pdo_getall('wlmerchant_agentusers',array('uniacid' => $_W['uniacid']),array('id','agentname'));

        //所属代理
        if($_GPC['agentid'] != 0){
            if($_GPC['agentid'] == -1){
                $loworderwhere['aid'] = 0;
            }else{
                $loworderwhere['aid'] =  $_GPC['agentid'];
            }
        }

        if ($_GPC['time'] && $_GPC['timetype'] > 0) {
            $time = $_GPC['time'];
            $starttime = strtotime($time['start']);
            $endtime = strtotime($time['end']);
            $loworderwhere['createtime>'] = $starttime;
            $loworderwhere['createtime<'] = $endtime;
        }

        if ($_GPC['ordertype']) {
            $loworderwhere['plugin'] = $_GPC['ordertype'];
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        if ($_GPC['buymid']) {
            $buymid = $_GPC['buymid'];
            $loworderwhere['buymid'] = $buymid;
        }

        if ($_GPC['disorder']) {
            $loworderwhere['id'] = $_GPC['disorder'];
        }

        $loworderwhere['no*'] = "(oneleadid = $disid or twoleadid = $disid or threeleadid = $disid )";

        if ($_GPC['export'] != '') {
            $this->exportloworder($loworderwhere, $disid);
        }
        $loworder = Util::getNumData('*', PDO_NAME . 'disorder', $loworderwhere, 'ID DESC', $pindex, $psize, 1);
        $pager = $loworder[1];
        $loworder = $loworder[0];
        foreach ($loworder as $key => &$order) {
            if ($order['plugin'] == 'rush') {
                $rush = pdo_get('wlmerchant_rush_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $rush['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                $goods = Rush::getSingleActive($rush['activityid'], 'name,thumb,unit');

                $order['gnum'] = $rush['num'];
                $order['goodsprice'] = $rush['price'] / $rush['num'];
                $order['paytype'] = $rush['paytype'];
                $order['merchantname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $rush['sid']), 'storename');
                $order['orderno'] = $rush['orderno'];
                $order['gname'] = $goods['name'];
                $order['gimg'] = $goods['thumb'];
                $order['unit'] = $goods['unit'];
                $order['orderstatus'] = $rush['status'];
                $order['ordertype'] = 1;
            } else if ($order['plugin'] == 'fightgroup') {
                $fightgroup = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $fightgroup['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                $goods = Wlfightgroup::getSingleGood($fightgroup['fkid'], 'name,logo,unit');

                $order['gnum'] = $fightgroup['num'];
                $order['paytype'] = $fightgroup['paytype'];
                $order['goodsprice'] = $order['orderprice'] / $fightgroup['num'];
                $order['merchantname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $fightgroup['sid']), 'storename');
                $order['orderno'] = $fightgroup['orderno'];
                $order['gname'] = $goods['name'];
                $order['gimg'] = $goods['logo'];
                $order['unit'] = $goods['unit'];
                $order['orderstatus'] = $fightgroup['status'];
                $order['ordertype'] = 2;
            } else if ($order['plugin'] == 'coupon') {
                $coupon = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $coupon['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                $goods = wlCoupon::getSingleCoupons($coupon['fkid'], 'title,logo');

                $order['gnum'] = $coupon['num'];
                $order['paytype'] = $coupon['paytype'];
                $order['goodsprice'] = $order['orderprice'] / $coupon['num'];
                $order['merchantname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $coupon['sid']), 'storename');
                $order['orderno'] = $coupon['orderno'];
                $order['gname'] = $goods['title'];
                $order['gimg'] = $goods['logo'];
                $order['unit'] = '张';
                $order['orderstatus'] = $coupon['status'];
                $order['ordertype'] = 3;

            } else if ($order['plugin'] == 'pocket') {
                $pocket = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $pocket['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                $goods = pdo_get('wlmerchant_pocket_informations', array('id' => $pocket['fkid']), array('share_title', 'type'));
                $type = pdo_get('wlmerchant_pocket_type', array('id' => $goods['type']), array('title', 'img'));

                $order['gnum'] = 1;
                $order['goodsprice'] = $order['orderprice'];
                $order['paytype'] = $pocket['paytype'];
                $order['merchantname'] = '掌上信息';
                $order['orderno'] = $pocket['orderno'];
                if ($goods['share_title']) {
                    $order['gname'] = $goods['share_title'];
                } else {
                    $order['gname'] = $type['title'];
                }

                $order['gimg'] = $type['img'];
                $order['unit'] = '次';
                $order['orderstatus'] = 3;

            } else if ($order['plugin'] == 'halfcard') {
                $halforder = pdo_get('wlmerchant_halfcard_record', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $halforder['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                $goods = pdo_get('wlmerchant_halfcard_type', array('id' => $halforder['typeid']), array('name', 'days', 'logo'));

                $order['paytype'] = $halforder['paytype'];
                $order['gnum'] = $goods['days'];
                $order['goodsprice'] = $order['orderprice'];
                $order['merchantname'] = '一卡通充值';
                $order['orderno'] = $halforder['orderno'];
                $order['gname'] = $goods['name'];

                $order['gimg'] = $member['avatar'];
                $order['unit'] = '天';
                $order['orderstatus'] = 3;

            } else if ($order['plugin'] == 'charge') {
                $chargeorder = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $chargeorder['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                $goods = pdo_get('wlmerchant_chargelist', array('id' => $chargeorder['fkid']), array('name', 'days'));
                $merchantdata = pdo_get('wlmerchant_merchantdata', array('id' => $chargeorder['sid']), array('storename', 'logo'));

                $order['gnum'] = $goods['days'];
                $order['goodsprice'] = $order['orderprice'];
                $order['paytype'] = $chargeorder['paytype'];
                $order['merchantname'] = $merchantdata['storename'];
                $order['orderno'] = $chargeorder['orderno'];
                $order['gname'] = $goods['name'];

                $order['gimg'] = $merchantdata['logo'];
                $order['unit'] = '天';
                $order['orderstatus'] = 3;

            } else if ($order['plugin'] == 'distribution') {
                $chargeorder = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $chargeorder['mid']), array('mobile', 'nickname', 'avatar','encodename'));

                $order['paytype'] = $chargeorder['paytype'];
                $order['gnum'] = 1;
                $order['goodsprice'] = $order['orderprice'];
                $order['merchantname'] = '平台业务';
                $order['orderno'] = $chargeorder['orderno'];
                if(Customized::init('distributionText') > 0){
                    $order['gname'] = '付费申请共享股东';
                }else{
                    $order['gname'] = '付费申请分销商';
                }
                $order['gimg'] = $member['avatar'];
                $order['unit'] = '';
                $order['orderstatus'] = 3;

            } else if ($order['plugin'] == 'groupon') {
                $groupon = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $groupon['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                $goods = Groupon::getSingleActive($groupon['fkid'], 'name,thumb,unit');

                $order['paytype'] = $groupon['paytype'];
                $order['gnum'] = $groupon['num'];
                $order['goodsprice'] = $groupon['price'] / $groupon['num'];
                $order['merchantname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $groupon['sid']), 'storename');
                $order['orderno'] = $groupon['orderno'];
                $order['gname'] = $goods['name'];
                $order['gimg'] = $goods['thumb'];
                $order['unit'] = $goods['unit'];
                $order['orderstatus'] = $groupon['status'];
                $order['ordertype'] = 10;

            } else if ($order['plugin'] == 'activity') {
                $groupon = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $groupon['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                $goods = pdo_get('wlmerchant_activitylist',array('id' => $groupon['fkid']),array('title','thumb'));

                $order['paytype'] = $groupon['paytype'];
                $order['gnum'] = $groupon['num'];
                $order['goodsprice'] = $groupon['price'] / $groupon['num'];
                $order['merchantname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $groupon['sid']), 'storename');
                $order['orderno'] = $groupon['orderno'];
                $order['gname'] = $goods['title'];
                $order['gimg'] = $goods['thumb'];
                $order['unit'] = '人';
                $order['orderstatus'] = $groupon['status'];
                $order['ordertype'] = 9;

            } else if ($order['plugin'] == 'consumption') {
                $groupon = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $groupon['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                $goods = pdo_get(PDO_NAME . 'consumption_goods', array('id' => $groupon['fkid']), array('thumb', 'title'));

                $order['paytype'] = $groupon['paytype'];
                $order['gnum'] = 1;
                $epxressprice = pdo_getcolumn(PDO_NAME . 'express', array('id' => $groupon['expressid']), 'expressprice');

                $order['goodsprice'] = sprintf("%.2f", $groupon['price'] - $epxressprice);
                $order['merchantname'] = '积分商城';
                $order['orderno'] = $groupon['orderno'];
                $order['gname'] = $goods['title'];
                $order['gimg'] = $goods['thumb'];
                $order['unit'] = '份';
                $order['orderstatus'] = $groupon['status'];

            } else if ($order['plugin'] == 'payonline') {
                $payorder = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $payorder['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                $goods = pdo_get('wlmerchant_halfcardlist', array('id' => $payorder['fkid']), array('title'));
                $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $payorder['sid']), array('storename', 'logo'));
                if (empty($goods['title'])) {
                    $goods['title'] = $merchant['storename'];
                }
                $order['gnum'] = 1;
                $order['paytype'] = $payorder['paytype'];
                $order['goodsprice'] = $order['orderprice'];
                $order['merchantname'] = $merchant['storename'];
                $order['orderno'] = $payorder['orderno'];
                $order['gname'] = $goods['title'] . '在线买单';

                $order['gimg'] = $merchant['logo'];
                $order['unit'] = '次';
                $order['orderstatus'] = 3;

            } else if ($order['plugin'] == 'bargain') {
                $payorder = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $payorder['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                $goods = pdo_get('wlmerchant_bargain_activity', array('id' => $payorder['fkid']), array('name', 'unit', 'thumb'));
                $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $payorder['sid']), array('storename'));

                $order['gnum'] = 1;
                $order['goodsprice'] = $order['orderprice'];
                $order['merchantname'] = $merchant['storename'];
                $order['orderno'] = $payorder['orderno'];
                $order['gname'] = $goods['name'];
                $order['paytype'] = $payorder['paytype'];

                $order['gimg'] = $goods['thumb'];
                $order['unit'] = $goods['unit'];
                $order['orderstatus'] = $payorder['status'];
                $order['ordertype'] = 12;

            } else if ($order['plugin'] == 'citycard') {
                $payorder = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $payorder['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                if($payorder['fightstatus'] == 1){
                    $goods = pdo_get('wlmerchant_citycard_meals', array('id' => $payorder['fkid']), array('name'));
                }else{
                    $goods = pdo_get('wlmerchant_citycard_tops', array('id' => $payorder['fkid']), array('name'));
                }
                $merchant['storename'] = '同城名片';
                $order['gnum'] = 1;
                $order['goodsprice'] = $order['orderprice'];
                $order['merchantname'] = $merchant['storename'];
                $order['orderno'] = $payorder['orderno'];
                $order['gname'] = $goods['name'];
                $order['paytype'] = $payorder['paytype'];

                $order['gimg'] = $member['avatar'];
                $order['unit'] = '次';
                $order['orderstatus'] = $payorder['status'];
            } else if ($order['plugin'] == 'citydelivery') {
                $payorder = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $payorder['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $payorder['sid']), array('storename','logo'));
                $goods['name'] = "[".$merchant['storename']."]配送商品";

                $order['gnum'] = 1;
                $order['goodsprice'] = $payorder['goodsprice'];
                $order['merchantname'] = $merchant['storename'];
                $order['orderno'] = $payorder['orderno'];
                $order['gname'] = $goods['name'];
                $order['paytype'] = $payorder['paytype'];

                $order['gimg'] = $merchant['logo'];
                $order['unit'] = '份';
                $order['orderstatus'] = $payorder['status'];
                $order['ordertype'] = 14;
            }else if ($order['plugin'] == 'hotel') {
                $payorder = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $payorder['mid']), array('mobile', 'nickname', 'avatar','encodename'));
                $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $payorder['sid']), array('storename','logo'));
                $goods = pdo_get('wlmerchant_hotel_room', array('id' => $payorder['fkid']), array('name','thumb'));

                $order['gnum'] = 1;
                $order['goodsprice'] = $payorder['goodsprice'];
                $order['merchantname'] = $merchant['storename'];
                $order['orderno'] = $payorder['orderno'];
                $order['gname'] = $goods['name'];
                $order['paytype'] = $payorder['paytype'];

                $order['gimg'] = $goods['thumb'];
                $order['unit'] = '次';
                $order['orderstatus'] = $payorder['status'];
                $order['ordertype'] = 19;
            }

            $order['nickname'] = !empty($member['encodename']) ? base64_decode($member['encodename']) : $member['nickname'];
            $order['mobile'] = $member['mobile'];
            $order['avatar'] = $member['avatar'];
            if($order['aid'] > 0 ){
                $order['agentname'] = pdo_getcolumn(PDO_NAME.'agentusers', array('id'=>$order['aid'],'uniacid'=>$_W['uniacid']),'agentname');
            }else{
                $order['agentname'] = '总后台';
            }
            if ($order['orderstatus'] == 1) {
                $order['statusCss'] = 'default';
                $order['statusName'] = '待核销';
            } else if ($order['orderstatus'] == 2) {
                $order['statusCss'] = 'success';
                $order['statusName'] = '待评价';
            } else if ($order['orderstatus'] == 3) {
                $order['statusCss'] = 'success';
                $order['statusName'] = '已完成';
            } else if ($order['orderstatus'] == 4) {
                $order['statusCss'] = 'info';
                $order['statusName'] = '待收货';
            } else if ($order['orderstatus'] == 6) {
                $order['statusCss'] = 'danger';
                $order['statusName'] = '待退款';
            } else if ($order['orderstatus'] == 7) {
                $order['statusCss'] = 'danger';
                $order['statusName'] = '已退款';
            } else if ($order['orderstatus'] == 8) {
                $order['statusCss'] = 'info';
                $order['statusName'] = '待发货';
            } else if ($order['orderstatus'] == 9) {
                $order['statusCss'] = 'danger';
                $order['statusName'] = '已过期';
            }
            //分销商数据
            $leadmoney = unserialize($order['leadmoney']);
            $order['onename'] = pdo_getcolumn(PDO_NAME . 'member', array('distributorid' => $order['oneleadid']), 'nickname');
            $order['onemoney'] = '￥' . $leadmoney['one'];
            if ($order['twoleadid'] > 0) {
                $order['twoname'] = pdo_getcolumn(PDO_NAME . 'member', array('distributorid' => $order['twoleadid']), 'nickname');
                $order['twomoney'] = '￥' . $leadmoney['two'];
            } else {
                $order['twoname'] = '-';
                $order['twomoney'] = '-';
            }
            $order['twomoney'] = $leadmoney['two'];
            //结算时间
            $order['setttime'] = pdo_getcolumn(PDO_NAME . 'disdetail', array('disorderid' => $order['id'], 'plugin' => $order['plugin'],'status' =>0), 'createtime');
        }
        include wl_template('disysbase/loworder');
    }


    public function lowpeople() {
        global $_W, $_GPC;
        $memid = $_GPC['memid'];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $messagesaler = pdo_get('wlmerchant_member', array('id' => $memid), array('mobile', 'realname', 'nickname', 'avatar', 'distributorid'));
        $where['leadid'] = $memid;
        if(empty($_W['wlsetting']['distribution']['showlock'])){
            $where['lockflag'] = 0;
        }
        if ($_GPC['disflag']) {
            $where['disflag'] = 1;
        }
        $type = intval($_GPC['type']);
        $keyword = trim($_GPC['keyword']);
        if (!empty($keyword)) {
            switch ($type) {
                case 2 :
                    $where['mobile@'] .= $keyword;
                    break;
                case 3 :
                    $where['nickname@'] .= $keyword;
                    break;
                case 4:
                    $where['realname@'] .= $keyword;
                    break;
                case 5:
                    $where['mid@'] .= $keyword;
                    break;
            }
        }
        $lowpeople = Util::getNumData('mid,id', PDO_NAME . 'distributor', $where, 'ID DESC', $pindex, $psize, 1);
        $pager = $lowpeople[1];
        $lowpeople = $lowpeople[0];
        foreach ($lowpeople as $key => &$peo) {
            $member = pdo_get('wlmerchant_member', array('id' => $peo['mid']), array('mobile', 'realname', 'nickname', 'avatar'));
            $peo['nickname'] = $member['nickname'];
            $peo['realname'] = $member['realname'];
            $peo['mobile'] = $member['mobile'];
            $peo['avatar'] = $member['avatar'];
            $peo['leadmid'] = $memid;
            $peo['leadname'] = $messagesaler['nickname'];
        }
        include wl_template('disysbase/lowpeople');
    }

    public function exportlist($where) {
        global $_W, $_GPC;
        if (empty($where)) {
            return FALSE;
        }
        $list = Distribution::getNumDistributor('*', $where, 'ID DESC', 0, 0, 1);
        $list = $list[0];
        foreach ($list as $key => &$v) {
            $mem = pdo_get('wlmerchant_member', array('id' => $v['mid']), array('mobile', 'realname', 'nickname'));
            $v['mobile'] = $mem['mobile'];
            $v['realname'] = $mem['realname'];
            $v['nickname'] = $mem['nickname'];
            $v['lowdis'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_distributor') . " WHERE leadid = {$v['mid']} AND disflag = 1");
            $v['lownum'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_distributor') . " WHERE leadid = {$v['mid']}");
            if ($v['leadid']>0) {
                $topname = pdo_get('wlmerchant_member', array('id' => $v['leadid']), array('nickname','mobile','realname'));
                if ($topname['nickname']) {
                    $v['topname'] = $topname['nickname'];
                } else {
                    $v['topname'] = $topname['realname'];
                }
                $v['topmobile'] = $topname['mobile'];
            }else if($v['leadid'] == -1){
                $v['topname'] = '系统直属';
            }else{
                $v['topname'] = '暂无上级';
            }
            if ($v['dislevel']) {
                $v['rankname'] = pdo_getcolumn(PDO_NAME . 'dislevel', array('id' => $v['dislevel']), 'name');
            } else {
                $v['rankname'] = pdo_getcolumn(PDO_NAME . 'dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), 'name');
            }
            if($v['disflag'] == -1){
                $v['rankname'] = $v['rankname'].'(已过期)';
            }
        }
        /* 输出表头 */
        $filter = array(
            'id'         => '分销商ID',
            'nickname'   => '昵称',
            'realname'   => '真实姓名',
            'mobile'     => '电话',
            'dismoney'   => '累计佣金',
            'nowmoney'   => '未结算佣金',
            'rankname'   => '等级',
            'topname'    => '上级名称',
            'leadid'     => '上级MID',
            'topmobile'  => '上级手机号',
            'lowdis'     => '下级分销商数量',
            'lownum'     => '下级人数',
            'createtime' => '创建时间',
            'updatetime' => '最后修改时间'
        );
        if(Customized::init('distributionText') > 0){
            $filter['id'] = '共享股东ID';
            $filter['lowdis'] = '下级股东数量';
        }

        $data = array();
        for ($i = 0; $i < count($list); $i++) {
            foreach ($filter as $key => $title) {
                if ($key == 'createtime' || $key == 'updatetime') {
                    $data[$i][$key] = date('Y-m-d H:i:s', $list[$i][$key]);
                } else {
                    $data[$i][$key] = $list[$i][$key];
                }
            }
        }
        util_csv::export_csv_2($data, $filter, '分销商列表.csv');
        exit;
    }

    public function distributordetail() {
        global $_W, $_GPC;
        $memid = $_GPC['memid'];
        $todo = $_GPC['todo'] ? $_GPC['todo'] : 'base';
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $disid = pdo_getcolumn('wlmerchant_distributor', array('uniacid' => $_W['uniacid'], 'mid' => $memid), 'id');

        if ($todo == 'base') {
            $messagesaler = pdo_get('wlmerchant_member', array('id' => $memid), array('mobile', 'realname', 'nickname', 'avatar', 'distributorid'));
            $distributor = pdo_get('wlmerchant_distributor', array('mid' => $memid), array('nowmoney', 'dismoney'));
            $messagesaler['nowmoney'] = $distributor['nowmoney'];
            $messagesaler['dismoney'] = $distributor['dismoney'];

            $applymoney = $cashmoney = $successmoney = 0;
            $apply = pdo_getall(PDO_NAME . 'settlement_record', array('mid' => $memid, 'type' => 3), array('status', 'sapplymoney'));
            if ($apply) {
                foreach ($apply as $key => $app) {
                    if ($app['status'] == 6 || $app['status'] == 7) {
                        $applymoney += $app['sapplymoney'];
                    } elseif ($app['status'] == 8) {
                        $cashmoney += $app['sapplymoney'];
                    } elseif ($app['status'] == 9) {
                        $successmoney += $app['sapplymoney'];
                    }
                }
            }
            $applymoney = sprintf("%.2f",$applymoney);
            $cashmoney = sprintf("%.2f",$cashmoney);
            $successmoney = sprintf("%.2f",$successmoney);
        } else if ($todo == 'lowpeople') {
            $where['leadid'] = $memid;
            $where['lockflag'] = 0;
            $type = intval($_GPC['type']);
            $keyword = trim($_GPC['keyword']);
            if (!empty($keyword)) {
                switch ($type) {
                    case 2 :
                        $where['mobile@'] .= $keyword;
                        break;
                    case 3 :
                        $where['nickname@'] .= $keyword;
                        break;
                    case 4:
                        $where['realname@'] .= $keyword;
                        break;
                    case 5:
                        $where['mid@'] .= $keyword;
                        break;
                }
            }

            $lowpeople = Util::getNumData('mid,id', PDO_NAME . 'distributor', $where, 'ID DESC', $pindex, $psize, 1);
            $pager = $lowpeople[1];
            $lowpeople = $lowpeople[0];
            foreach ($lowpeople as $key => &$peo) {
                $member = pdo_get('wlmerchant_member', array('id' => $peo['mid']), array('mobile', 'realname', 'nickname', 'avatar'));
                $peo['nickname'] = $member['nickname'];
                $peo['realname'] = $member['realname'];
                $peo['mobile'] = $member['mobile'];
                $peo['avatar'] = $member['avatar'];
            }
        } elseif ($todo == 'loworder') {
            //下级订单
            $loworderwhere['uniacid'] = $_W['uniacid'];

            if ($_GPC['time']) {
                $time = $_GPC['time'];
                $starttime = strtotime($time['start']);
                $endtime = strtotime($time['end']);
                $loworderwhere['createtime>'] = $starttime;
                $loworderwhere['createtime<'] = $endtime;
            }

            if ($_GPC['ordertype']) {
                $loworderwhere['plugin'] = $_GPC['ordertype'];
            }

            if (empty($starttime) || empty($endtime)) {
                $starttime = strtotime('-1 month');
                $endtime = time();
            }

            if ($_GPC['buymid']) {
                $buymid = $_GPC['buymid'];
                $loworderwhere['buymid'] = $buymid;
            }
            $loworderwhere['no*'] = "(oneleadid = $disid or twoleadid = $disid or threeleadid = $disid )";
            $loworder = Util::getNumData('*', PDO_NAME . 'disorder', $loworderwhere, 'ID DESC', $pindex, $psize, 1);
            $pager = $loworder[1];
            $loworder = $loworder[0];

            foreach ($loworder as $key => &$order) {
                if ($order['plugin'] == 'rush') {
                    $rush = pdo_get('wlmerchant_rush_order', array('id' => $order['orderid']));
                    $member = pdo_get('wlmerchant_member', array('id' => $rush['mid']), array('mobile', 'nickname', 'avatar'));
                    $goods = Rush::getSingleActive($rush['activityid'], 'name,thumb,unit');

                    $order['gnum'] = $rush['num'];
                    $order['goodsprice'] = $rush['price'] / $rush['num'];
                    $order['merchantname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $rush['sid']), 'storename');
                    $order['orderno'] = $rush['orderno'];
                    $order['gname'] = $goods['name'];
                    $order['gimg'] = $goods['thumb'];
                    $order['unit'] = $goods['unit'];
                } else if ($order['plugin'] == 'fightgroup') {
                    $fightgroup = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                    $member = pdo_get('wlmerchant_member', array('id' => $fightgroup['mid']), array('mobile', 'nickname', 'avatar'));
                    $goods = Wlfightgroup::getSingleGood($fightgroup['fkid'], 'name,logo,unit');

                    $order['gnum'] = $fightgroup['num'];
                    $order['goodsprice'] = $order['orderprice'] / $fightgroup['num'];
                    $order['merchantname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $fightgroup['sid']), 'storename');
                    $order['orderno'] = $fightgroup['orderno'];
                    $order['gname'] = $goods['name'];
                    $order['gimg'] = $goods['logo'];
                    $order['unit'] = $goods['unit'];
                } else if ($order['plugin'] == 'coupon') {
                    $coupon = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                    $member = pdo_get('wlmerchant_member', array('id' => $coupon['mid']), array('mobile', 'nickname', 'avatar'));
                    $goods = wlCoupon::getSingleCoupons($coupon['fkid'], 'title,logo');

                    $order['gnum'] = $coupon['num'];
                    $order['goodsprice'] = $order['orderprice'] / $coupon['num'];
                    $order['merchantname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $coupon['sid']), 'storename');
                    $order['orderno'] = $coupon['orderno'];
                    $order['gname'] = $goods['title'];
                    $order['gimg'] = $goods['logo'];
                    $order['unit'] = '张';
                } else if ($order['plugin'] == 'pocket') {
                    $pocket = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                    $member = pdo_get('wlmerchant_member', array('id' => $pocket['mid']), array('mobile', 'nickname', 'avatar'));
                    $goods = pdo_get('wlmerchant_pocket_informations', array('id' => $pocket['fkid']), array('share_title', 'type'));
                    $type = pdo_get('wlmerchant_pocket_type', array('id' => $goods['type']), array('title', 'img'));

                    $order['gnum'] = 1;
                    $order['goodsprice'] = $order['orderprice'];
                    $order['merchantname'] = '掌上信息';
                    $order['orderno'] = $pocket['orderno'];
                    if ($goods['share_title']) {
                        $order['gname'] = $goods['share_title'];
                    } else {
                        $order['gname'] = $type['title'];
                    }

                    $order['gimg'] = $type['img'];
                    $order['unit'] = '次';
                } else if ($order['plugin'] == 'halfcard') {
                    $halforder = pdo_get('wlmerchant_halfcard_record', array('id' => $order['orderid']));
                    $member = pdo_get('wlmerchant_member', array('id' => $halforder['mid']), array('mobile', 'nickname', 'avatar'));
                    $goods = pdo_get('wlmerchant_halfcard_type', array('id' => $halforder['typeid']), array('name', 'days', 'logo'));

                    $order['gnum'] = $goods['days'];
                    $order['goodsprice'] = $order['orderprice'];
                    $order['merchantname'] = '一卡通充值';
                    $order['orderno'] = $halforder['orderno'];
                    $order['gname'] = $goods['name'];

                    $order['gimg'] = $goods['logo'];
                    $order['unit'] = '天';
                } else if ($order['plugin'] == 'payonline') {
                    $payorder = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                    $member = pdo_get('wlmerchant_member', array('id' => $payorder['mid']), array('mobile', 'nickname', 'avatar'));
                    $goods = pdo_get('wlmerchant_halfcardlist', array('id' => $payorder['fkid']), array('title'));
                    $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $payorder['fkid']), array('storename', 'logo'));

                    $order['gnum'] = 1;
                    $order['goodsprice'] = $order['orderprice'];
                    $order['merchantname'] = $merchant['storename'];
                    $order['orderno'] = $payorder['orderno'];
                    $order['gname'] = $goods['title'];

                    $order['gimg'] = $merchant['logo'];
                    $order['unit'] = '次';
                }

                $order['nickname'] = $member['nickname'];
                $order['mobile'] = $member['mobile'];
                $order['avatar'] = $member['avatar'];

                if ($order['status'] == 0) {
                    $order['statusCss'] = 'default';
                    $order['statusName'] = '不可结算';
                }
                if ($order['status'] == 1) {
                    $order['statusCss'] = 'info';
                    $order['statusName'] = '可结算';
                } else if ($order['status'] == 2) {
                    $order['statusCss'] = 'success';
                    $order['statusName'] = '已结算';
                }
                $leadmoney = unserialize($order['leadmoney']);
                if ($order['oneleadid'] == $disid) {
                    $order['leadmoney'] = $leadmoney['one'];
                    $order['rank'] = 1;
                } else if ($order['twoleadid'] == $disid) {
                    $order['leadmoney'] = $leadmoney['two'];
                    $order['rank'] = 2;
                } else if ($order['threeleadid'] == $disid) {
                    $order['leadmoney'] = $leadmoney['three'];
                    $order['rank'] = 3;
                }
            }
        } else if ($todo == 'applylist') {
            $where['mid'] = $memid;
            $applylist = Util::getNumData('*', PDO_NAME . 'settlement_record', $where, 'ID DESC', $pindex, $psize, 1);
            $pager = $applylist[1];
            $applylist = $applylist[0];
            if ($applylist) {
                foreach ($applylist as $key => &$apply) {
                    $member = pdo_get('wlmerchant_member', array('id' => $memid), array('mobile', 'avatar', 'nickname'));
                    $apply['avatar'] = $member['avatar'];
                    $apply['mobile'] = $member['mobile'];
                    $apply['nickname'] = $member['nickname'];
                }
            }

        }

        include wl_template('disysbase/adddistributor');
    }

    public function applist() {
        global $_W, $_GPC;
        header('Location:' . web_url('finace/finaceWithdrawalApply/cashApply', array('type' => 3)));
    }

    public function export($status) {
        if (empty($status)) return FALSE;
        set_time_limit(0);
        if ($status == 1) {
            $where['status'] = 7;
            $name = '审核中提现记录';
        } else if ($status == 2) {
            $where['status'] = 8;
            $name = '已审核提现记录';
        } else if ($status == 3) {
            $where['status'] = 11;
            $name = '已驳回提现记录';
        } else if ($status == 4) {
            $where['status'] = 9;
            $name = '已打款提现记录';
        }
        $applylist = Util::getNumData('*', PDO_NAME . 'settlement_record', $where, 'ID DESC', 0, 0, 1);

        $list = $applylist[0];
        if ($list) {
            foreach ($list as $key => &$apply) {
                $member = pdo_get('wlmerchant_member', array('id' => $apply['mid']), array('mobile', 'avatar', 'nickname'));
                $apply['avatar'] = $member['avatar'];
                $apply['mobile'] = $member['mobile'];
                $apply['nickname'] = $member['nickname'];
                $apply['applytime'] = date('Y-m-d H:i:s', $apply['applytime']);
                if ($apply['updatetime']) {
                    $apply['updatetime'] = date('Y-m-d H:i:s', $apply['updatetime']);
                } else {
                    $apply['updatetime'] = '未操作';
                }
            }
        }
        /* 输入到CSV文件 */
        $html = "\xEF\xBB\xBF";
        /* 输出表头 */
        $filter = array(
            'nickname'   => '用户名',
            'mobile'     => '用户手机',
            'sgetmoney'  => '金额',
            'status'     => '申请状态',
            'settletype' => '打款方式',
            'applytime'  => '申请时间',
            'updatetime' => '处理时间'
        );
        foreach ($filter as $key => $title) {
            $html .= $title . "\t,";
        }
        $html .= "\n";
        foreach ($list as $k => $v) {
            foreach ($filter as $key => $title) {
                if ($key == 'status') {
                    switch ($v[$key]) {
                        case '6':
                            $html .= '审核中' . "\t,";
                            break;
                        case '7':
                            $html .= '审核中' . "\t,";
                            break;
                        case '8':
                            $html .= '已审核' . "\t,";
                            break;
                        case '9':
                            $html .= '已打款' . "\t,";
                            break;
                        case '10':
                            $html .= '已驳回' . "\t, ";
                            break;
                        case '11':
                            $html .= '已驳回' . "\t, ";
                            break;
                        default:
                            $html .= 'null' . "\t, ";
                            break;
                    }
                } else if ($key == 'settletype') {
                    switch ($v[$key]) {
                        case '1':
                            $html .= '手动完成' . "\t, ";
                            break;
                        case '2':
                            $html .= '微信打款' . "\t, ";
                            break;
                        case '3':
                            $html .= '微信打款' . "\t, ";
                            break;
                        default:
                            $html .= '未打款' . "\t, ";
                            break;
                    }
                } else {
                    $html .= $v[$key] . trim("\t,");
                }
            }
            $html .= "\n";
        }
        /* 输出CSV文件 */
        header("Content-type:text/csv");
        header("Content-Disposition:attachment; filename={$name}.csv");
        echo $html;
        exit();
    }

    public function disbaseset() {
        global $_W, $_GPC;
        $base = Setting::wlsetting_read('distribution');
        //一卡通会员类型
        $halfcardtypes = pdo_getall('wlmerchant_halfcard_type', array('uniacid' => $_W['uniacid']), array('name', 'id'));
        $community = pdo_getall('wlmerchant_community', array('uniacid' => $_W['uniacid'], 'aid' => 0), array('id', 'communname'));
        $levelupstatusArray = unserialize($base['levelupstatus']);

        if ($_W['ispost']) {
            $data = $_GPC['base'];
            if ($data['lowestmoney'] < 0) {
                show_json(0, '最低提现金额必须为正数');
            }
            if ($data['maxmoney'] < $data['lowestmoney'] && $data['maxmoney'] > 0) {
                show_json(0, '最大提现金额必须大于最小提现金额');
            }
            if ($data['withdrawcharge'] < 0) {
                show_json(0, '提现手续费必须为正数');
            }
            $data['moneynptice'] = $_GPC['moneynptice'];
            $data['noticeSwitch'] = $_GPC['noticeSwitch'];
            if (empty($data['lowestmoney'])) {
                $data['lowestmoney'] = 1;
            }
            $data['appdetail'] = htmlspecialchars_decode($data['appdetail']);
            $data['distriqa'] = htmlspecialchars_decode($data['distriqa']);

            $data['levelupstatus'] = serialize($data['levelupstatus']);
            if ($data['lockstatus'] != 1 && $data['lockstatus'] != 3) {
                pdo_update('wlmerchant_distributor', array('lockflag' => 0, 'uniacid' => $_W['uniacid']), array('lockflag' => 1));
            }
            if($data['appdis'] == 5){
                $data['totallmoney'] = intval($data['totallmoney']);
            }
            $res1 = Setting::wlsetting_save($data, 'distribution');
            if ($res1) {
                Tools::clearposter();
                show_json(1);
            } else {
                show_json(0, '设置保存失败');
            }
        }
        include wl_template('disysbase/disbaseset');
    }

    public function reject() {
        global $_W, $_GPC;
        $appid = $_GPC['id'];
        $res = pdo_update(PDO_NAME . 'settlement_record', array('status' => 11, 'updatetime' => time()), array('id' => $appid));
        if ($res) {
            $apply = pdo_get(PDO_NAME . 'settlement_record', array('id' => $appid), array('sgetmoney', 'disid', 'mid', 'sapplymoney'));
            $nowmoney = pdo_getcolumn('wlmerchant_distributor', array('id' => $apply['disid']), 'nowmoney');
            $newmoney = $apply['sapplymoney'] + $nowmoney;
            $res2 = pdo_update('wlmerchant_distributor', array('nowmoney' => $newmoney), array('id' => $apply['disid']));
            $openid = pdo_getcolumn(PDO_NAME . 'member', array('id' => $apply['mid']), 'openid');
            $url = h5_url('pages/subPages/dealer/withdraw/withdrawrecord', ['draw_id' => $appid]);
            Distribution::distriNotice($apply['mid'], $url, 5, 0, $apply['sapplymoney']);
            Distribution::adddisdetail($appid, $apply['mid'], '-1', 1, $apply['sapplymoney'], 'cash', 1);
            wl_message('驳回申请成功！', referer(), 'success');
        } else {
            wl_message('驳回申请失败！', referer(), 'error');
        }
    }

    public function pass() {
        global $_W, $_GPC;
        $appid = $_GPC['id'];
        $trade_no = time() . random(4, true);
        $res = pdo_update(PDO_NAME . 'settlement_record', array('status' => 8, 'updatetime' => time(), 'trade_no' => $trade_no), array('id' => $appid));
        if ($res) {
            $apply = pdo_get(PDO_NAME . 'settlement_record', array('id' => $appid), array('mid', 'sapplymoney'));
            $openid = pdo_getcolumn(PDO_NAME . 'member', array('id' => $apply['mid']), 'openid');
            $url = h5_url('pages/subPages/dealer/withdraw/withdrawrecord', ['draw_id' => $appid]);
            Distribution::distriNotice($apply['mid'], $url, 4, 0, $apply['sapplymoney']);
            wl_message('审核通过成功！', referer(), 'success');
        } else {
            wl_message('审核通过失败！', referer(), 'error');
        }
    }

    public function tocash() {
        global $_W, $_GPC;
        $appid = $_GPC['id'];
        $apply = pdo_get(PDO_NAME . 'settlement_record', array('id' => $appid));
        if ($apply['status'] != 8) {
            wl_message('申请状态异常，请刷新重试', referer(), 'error');
        }
        if (is_numeric($apply['sgetmoney'])) {
            if ($apply['sgetmoney'] < 1) wl_message('到账金额需要大于1元！', referer(), 'error');
            $applyopenid = pdo_getcolumn('wlmerchant_member', array('id' => $apply['mid']), 'openid');
            $realname = pdo_getcolumn(PDO_NAME . 'member', array('id' => $apply['mid']), 'realname');
            $result = wlPay::finance($applyopenid, $apply['sgetmoney'], '结算给分销商', $realname, $apply['trade_no']);  //结算操作
            if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
                $res = pdo_update(PDO_NAME . 'settlement_record', array('status' => 9, 'updatetime' => time(), 'settletype' => 3), array('id' => $appid));
                if ($res) {
                    $openid = pdo_getcolumn(PDO_NAME . 'member', array('id' => $apply['mid']), 'openid');
                    $url = h5_url('pages/subPages/dealer/withdraw/withdrawrecord', ['draw_id' => $appid]);
                    Distribution::distriNotice($apply['mid'], $url, 6, 0, $apply['sapplymoney'], '微信零钱');
                    wl_message('微信钱包打款成功！', referer(), 'success');
                } else {
                    wl_message('微信钱包打款失败！', referer(), 'error');
                }
            } else {
                if (empty($result['err_code_des'])) {
                    $result['err_code_des'] = $result['message'];
                }
                wl_message('微信钱包打款失败: ' . $result['err_code_des'], '', 'error'); // 结算失败
            }
        } else {
            wl_message('申请金额错误！', referer(), 'error');
        }
    }

    public function tofinish() {
        global $_W, $_GPC;
        $appid = $_GPC['id'];
        $res = pdo_update(PDO_NAME . 'settlement_record', array('status' => 9, 'updatetime' => time(), 'settletype' => 3), array('id' => $appid));
        $apply = pdo_get(PDO_NAME . 'settlement_record', array('id' => $appid));
        if ($res) {
            $openid = pdo_getcolumn(PDO_NAME . 'member', array('id' => $apply['mid']), 'openid');
            $url = h5_url('pages/subPages/dealer/withdraw/withdrawrecord', ['draw_id' => $appid]);
            Distribution::distriNotice($apply['mid'], $url, 6, 0, $apply['sapplymoney'], '线下打款');
            wl_message('标记打款成功！', referer(), 'success');
        } else {
            wl_message('标记打款失败！', referer(), 'error');
        }
    }

    function unbind() {
        global $_W, $_GPC;
        $res = pdo_update('wlmerchant_distributor', array('leadid' => 0), array('id' => $_GPC['id']));
        if ($res) {
            show_json(1, '解除绑定成功');
        } else {
            show_json(0, '解除绑定失败，请重试');
        }
    }

    public function passdis() {
        global $_W, $_GPC;
        $appid = $_GPC['id'];
        $base = Setting::wlsetting_read('distribution');
        $res = pdo_update('wlmerchant_applydistributor', array('status' => 1), array('id' => $appid));
        if ($res) {
            $appdis = pdo_get('wlmerchant_applydistributor', array('id' => $appid), array('mobile', 'realname', 'mid', 'rank', 'leadid'));
            $distributor = pdo_get('wlmerchant_distributor', array('mid' => $appdis['mid'], 'uniacid' => $_W['uniacid']), array('id', 'leadid'));
            if ($distributor) {
                if ($appdis['rank'] == 1 && $base['mode']) {
                    $data['leadid'] = -1;
                }
                $data['disflag'] = 1;
                $data['lockflag'] = 0;
                $data['updatetime'] = time();
                $res2 = pdo_update('wlmerchant_distributor', $data, array('mid' => $appdis['mid']));
                $disid = $distributor['id'];
            } else {
                $nickname = pdo_getcolumn('wlmerchant_member', array('id' => $appdis['mid']), 'nickname');
                $data2 = array(
                    'uniacid'    => $_W['uniacid'],
                    'mid'        => $appdis['mid'],
                    'disflag'    => 1,
                    'leadid'     => $appdis['leadid'],
                    'dismoney'   => 0,
                    'nowmoney'   => 0,
                    'nickname'   => $nickname,
                    'realname'   => $appdis['realname'],
                    'mobile'     => $appdis['mobile'],
                    'createtime' => time(),
                    'updatetime' => time()
                );
                if ($data2['mid'] > 0) {
                    pdo_insert('wlmerchant_distributor', $data2);
                    $disid = $res2 = pdo_insertid();
                    pdo_update('wlmerchant_member', array('distributorid' => $res2), array('id' => $appdis['mid']));
                } else {
                    $res2 = 0;
                }
            }
            if ($res2) {
                $url = h5_url('pages/subPages/dealer/index/index');
                $mid = pdo_getcolumn(PDO_NAME . 'applydistributor', array('id' => $appid), 'mid');
                //$openid = pdo_getcolumn(PDO_NAME . 'member', array('id' => $mid), 'openid');
                Distribution::distriNotice($mid, $url, 1);

                if($appdis['leadid'] > 0) Distribution::distriNotice($appdis['leadid'], '', 2,$disid);//发送模板消息
                wl_message('审核通过成功！', referer(), 'success');
            } else {
                wl_message('审核通过失败！', referer(), 'error');
            }
        } else {
            wl_message('审核通过失败！请联系管理员', referer(), 'error');
        }
    }

    public function rejectreason() {
        global $_W, $_GPC;
        $appid = $_GPC['id'];
        $reason = $_GPC['reason'];
        $res = pdo_update('wlmerchant_applydistributor', array('status' => 2, 'reason' => $reason), array('id' => $appid));
        if ($res) {
            $fxstext = $_W['wlsetting']['trade']['fxstext'] ? : '分销商';
            $mid = pdo_getcolumn(PDO_NAME.'applydistributor',array('id'=>$appid),'mid');
            $first = '您的申请已被驳回';
            $type = '申请成为'.$fxstext;
            $status = '已驳回';
            $content = '驳回原因:['.$reason.']';
            $remark = '点击前往申请页面重新发起申请';
            $url = h5_url('pages/subPages/dealer/index/index');
            News::jobNotice($mid,$first,$type,$content,$status,$remark,time(),$url);
            die(json_encode(array('errno' => 0)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    public function statistics() {
        global $_W, $_GPC;
        $disid = $_GPC['disid'];
        $allmoney = 0;
        $orders = pdo_fetchall("SELECT orderprice FROM " . tablename('wlmerchant_disorder') . "WHERE uniacid = {$_W['uniacid']} AND status > 0 AND (oneleadid = $disid or twoleadid = $disid or threeleadid = $disid ) ORDER BY id DESC");
        if ($orders) {
            foreach ($orders as $key => $order) {
                $allmoney += $order['orderprice'];
            }
        }
        $allmoney = sprintf("%.2f",$allmoney);
        die(json_encode(array('errno' => 0, 'message' => $allmoney)));
    }

    public function canceldis() {
        global $_W, $_GPC;
        $disid = $_GPC['id'];
        $mid = pdo_getcolumn('wlmerchant_member', array('distributorid' => $disid), 'id');
        pdo_update('wlmerchant_member', array('distributorid' => 0), array('id' => $mid));
        $res = pdo_delete('wlmerchant_distributor', array('id' => $disid));
        if ($res) {
            pdo_update('wlmerchant_distributor', array('leadid' => 0), array('leadid' => $mid));
            pdo_delete('wlmerchant_applydistributor', array('mid' => $mid));
        }
        if ($res) {
            //同步删除当前分销商关联的业务员信息
            pdo_delete(PDO_NAME."merchantuser",['mid'=>$mid,'ismain'=>4]);

            die(json_encode(array('errno' => 0)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    public function adddistributor() {
        global $_W, $_GPC;
        $base = Setting::wlsetting_read('distribution');
        $memid = $_GPC['memid'];
        $todo = $_GPC['todo'] ? $_GPC['todo'] : 'appdislist';
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $disid = pdo_getcolumn('wlmerchant_distributor', array('uniacid' => $_W['uniacid'], 'mid' => $memid), 'id');
        $messagesaler = pdo_get('wlmerchant_member', array('id' => $memid), array('mobile', 'realname', 'nickname', 'avatar', 'distributorid'));
        if ($todo == 'base') {
            $distributor = pdo_get('wlmerchant_distributor', array('mid' => $memid), array('nowmoney', 'dismoney'));
            $messagesaler['nowmoney'] = $distributor['nowmoney'];
            $messagesaler['dismoney'] = $distributor['dismoney'];

            $applymoney = $cashmoney = $successmoney = 0;
            $apply = pdo_getall(PDO_NAME . 'settlement_record', array('mid' => $memid, 'type' => 3), array('sgetmoney', 'status'));
            if ($apply) {
                foreach ($apply as $key => $app) {
                    if ($app['status'] == 6 || $app['status'] == 7) {
                        $applymoney += $app['sgetmoney'];
                    } elseif ($app['status'] == 8) {
                        $cashmoney += $app['sgetmoney'];
                    } elseif ($app['status'] == 9) {
                        $successmoney += $app['sgetmoney'];
                    }
                }
            }

            $applymoney = sprintf("%.2f",$applymoney);
            $cashmoney = sprintf("%.2f",$cashmoney);
            $successmoney = sprintf("%.2f",$successmoney);
        } else if ($todo == 'applylist') {
            $where['mid'] = $memid;
            $applylist = Util::getNumData('*', PDO_NAME . 'settlement_record', $where, 'ID DESC', $pindex, $psize, 1);
            $pager = $applylist[1];
            $applylist = $applylist[0];
            if ($applylist) {
                foreach ($applylist as $key => &$apply) {
                    $member = pdo_get('wlmerchant_member', array('id' => $memid), array('mobile', 'avatar', 'nickname'));
                    $apply['avatar'] = $member['avatar'];
                    $apply['mobile'] = $member['mobile'];
                    $apply['nickname'] = $member['nickname'];
                }
            }
        } else if ($todo == 'appdislist') {
            $where['status'] = 0;
            $applydislist = Util::getNumData('*', PDO_NAME . 'applydistributor', $where, 'ID DESC', $pindex, $psize, 1);
            $pager = $applydislist[1];
            $dislist = $applydislist[0];
            if ($dislist) {
                foreach ($dislist as $key => &$appdis) {
                    $mem = pdo_get('wlmerchant_member', array('id' => $appdis['mid']), array('avatar', 'nickname'));
                    $appdis['avatar'] = $mem['avatar'];
                    $appdis['nickname'] = $mem['nickname'];
                }
            }
        } else if ($todo == 'payrecord') {
            $payrecord = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_order') . "WHERE uniacid = {$_W['uniacid']} AND status = 3 AND plugin = 'distribution' ORDER BY paytime DESC");
            foreach ($payrecord as $key => &$reco) {
                $member = pdo_get('wlmerchant_member', array('id' => $reco['mid']), array('avatar', 'nickname'));
                $reco['avatar'] = $member['avatar'];
                $reco['nickname'] = $member['nickname'];
            }
        }


        include wl_template('disysbase/adddistributor');
    }

    public function searchmember() {
        global $_W, $_GPC;
        $con = $con2 = "uniacid='{$_W['uniacid']}' ";
        $keyword = $_GPC['keyword'];

        if($_GPC['disflag'] > 0){
                $con .= " AND groupflag = 0";
            if ($keyword != '') {
                $con .= " and ( nickname LIKE '%{$keyword}%' or mid  LIKE '%{$keyword}%' or mobile LIKE '%{$keyword}%' ) ";
            }
            $ds = pdo_fetchall("select * from" . tablename('wlmerchant_distributor') . "where $con");
            if(!empty($ds)){
                foreach ($ds as &$dsinfo){
                    $member = pdo_get('wlmerchant_member',array('id' => $dsinfo['mid']),array('avatar'));
                    $dsinfo['avatar'] = $member['avatar'];
                }
            }
        }else{
            if ($keyword != '') {
                $con .= " and ( nickname LIKE '%{$keyword}%' or id  LIKE '%{$keyword}%' or mobile LIKE '%{$keyword}%' ) ";
            }
            $ds = pdo_fetchall("select * from" . tablename('wlmerchant_member') . "where $con");
        }

        include wl_template('disysbase/searchmember');
    }

    public function exportloworder($where, $disid) {
        global $_W, $_GPC;
        $loworder = Util::getNumData('*', PDO_NAME . 'disorder', $where, 'ID DESC', 0, 0, 1);
        $loworder = $loworder[0];
        foreach ($loworder as $key => &$order) {
            if ($order['plugin'] == 'rush') {
                $rush = pdo_get('wlmerchant_rush_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $rush['mid']), array('mobile','realname', 'nickname', 'avatar'));
                $goods = Rush::getSingleActive($rush['activityid'], 'name,thumb,unit');

                $order['gnum'] = $rush['num'];
                $order['goodsprice'] = $rush['price'] / $rush['num'];
                $order['merchantname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $rush['sid']), 'storename');
                $order['orderno'] = $rush['orderno'];
                $order['gname'] = $goods['name'];
                $order['gimg'] = $goods['thumb'];
                $order['unit'] = $goods['unit'];
                $order['plugin'] = '抢购订单';
            } else if ($order['plugin'] == 'fightgroup') {
                $fightgroup = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $fightgroup['mid']), array('mobile','realname', 'nickname', 'avatar'));
                $goods = Wlfightgroup::getSingleGood($fightgroup['fkid'], 'name,logo,unit');

                $order['gnum'] = $fightgroup['num'];
                $order['goodsprice'] = $order['orderprice'] / $fightgroup['num'];
                $order['merchantname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $fightgroup['sid']), 'storename');
                $order['orderno'] = $fightgroup['orderno'];
                $order['gname'] = $goods['name'];
                $order['gimg'] = $goods['logo'];
                $order['unit'] = $goods['unit'];
                $order['plugin'] = '拼团订单';
            } else if ($order['plugin'] == 'coupon') {
                $coupon = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $coupon['mid']), array('mobile','realname', 'nickname', 'avatar'));
                $goods = wlCoupon::getSingleCoupons($coupon['fkid'], 'title,logo');

                $order['gnum'] = $coupon['num'];
                $order['goodsprice'] = $order['orderprice'] / $coupon['num'];
                $order['merchantname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $coupon['sid']), 'storename');
                $order['orderno'] = $coupon['orderno'];
                $order['gname'] = $goods['title'];
                $order['gimg'] = $goods['logo'];
                $order['unit'] = '张';
                $order['plugin'] = '超级券订单';
            } else if ($order['plugin'] == 'pocket') {
                $pocket = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $pocket['mid']), array('mobile','realname', 'nickname', 'avatar'));
                $goods = pdo_get('wlmerchant_pocket_informations', array('id' => $pocket['fkid']), array('share_title', 'type'));
                $type = pdo_get('wlmerchant_pocket_type', array('id' => $goods['type']), array('title', 'img'));

                $order['gnum'] = 1;
                $order['goodsprice'] = $order['orderprice'];
                $order['merchantname'] = '掌上信息';
                $order['orderno'] = $pocket['orderno'];
                if ($goods['share_title']) {
                    $order['gname'] = $goods['share_title'];
                } else {
                    $order['gname'] = $type['title'];
                }

                $order['gimg'] = $type['img'];
                $order['unit'] = '次';
                $order['plugin'] = '掌上信息';
            } else if ($order['plugin'] == 'halfcard') {
                $halforder = pdo_get('wlmerchant_halfcard_record', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $halforder['mid']), array('mobile','realname', 'nickname', 'avatar'));
                $goods = pdo_get('wlmerchant_halfcard_type', array('id' => $halforder['typeid']), array('name', 'days', 'logo'));

                $member['realname'] = $halforder['username'];
                $order['gnum'] = $goods['days'];
                $order['goodsprice'] = $order['orderprice'];
                $order['merchantname'] = '一卡通充值';
                $order['orderno'] = $halforder['orderno'];
                $order['gname'] = $goods['name'];

                $order['gimg'] = $goods['logo'];
                $order['unit'] = '天';
                $order['plugin'] = '一卡通充值';
            } else if ($order['plugin'] == 'charge') {
                $chargeorder = pdo_get('wlmerchant_order', array('id' => $order['orderid']));
                $member = pdo_get('wlmerchant_member', array('id' => $chargeorder['mid']), array('mobile','realname', 'nickname', 'avatar'));
                $goods = pdo_get('wlmerchant_chargelist', array('id' => $chargeorder['fkid']), array('name', 'days'));
                $merchantdata = pdo_get('wlmerchant_merchantdata', array('id' => $chargeorder['sid']), array('storename', 'logo'));

                $order['gnum'] = $goods['days'];
                $order['goodsprice'] = $order['orderprice'];
                $order['merchantname'] = $merchantdata['storename'];
                $order['orderno'] = $chargeorder['orderno'];
                $order['gname'] = $goods['name'];

                $order['gimg'] = $merchantdata['logo'];
                $order['unit'] = '天';
                $order['plugin'] = '付费入驻';
            }

            $order['nickname'] = $member['nickname'];
            $order['realname'] = $member['realname'];
            $order['mobile'] = $member['mobile'];
            $order['avatar'] = $member['avatar'];

            if ($order['status'] == 0) {
                $order['statusCss'] = 'default';
                $order['statusName'] = '不可结算';
            }
            if ($order['status'] == 1) {
                $order['statusCss'] = 'info';
                $order['statusName'] = '可结算';
            } else if ($order['status'] == 2) {
                $order['statusCss'] = 'success';
                $order['statusName'] = '已结算';
            }
            $leadmoney = unserialize($order['leadmoney']);
            if ($order['oneleadid'] == $disid) {
                $order['leadmoney'] = $leadmoney['one'];
                $order['rank'] = '一级订单';
            } else if ($order['twoleadid'] == $disid) {
                $order['leadmoney'] = $leadmoney['two'];
                $order['rank'] = '二级订单';
            } else if ($order['threeleadid'] == $disid) {
                $order['leadmoney'] = $leadmoney['three'];
                $order['rank'] = '三级订单';
            }
        }
        /* 输出表头 */
        $filter = array(
            'id'           => '订单ID',
            'orderno'      => '订单编号',
            'plugin'       => '订单类型',
            'gname'        => '商品名称',
            'merchantname' => '商户名称',
            'nickname'     => '买家姓名',
            'realname'     => '真实姓名',
            'mobile'       => '买家电话',
            'orderprice'   => '订单金额',
            'leadmoney'    => '提成金额',
            'rank'         => '订单等级',
            'statusName'   => '订单状态',
            'createtime'   => '创建时间'
        );
        $data = array();
        for ($i = 0; $i < count($loworder); $i++) {
            foreach ($filter as $key => $title) {
                if ($key == 'createtime') {
                    $data[$i][$key] = date('Y-m-d H:i:s', $loworder[$i][$key]);
                } else {
                    $data[$i][$key] = $loworder[$i][$key];
                }
            }
        }
        util_csv::export_csv_2($data, $filter, '下级订单列表.csv');
        exit;
    }

    public function cansett() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $order = pdo_get('wlmerchant_disorder', array('id' => $id), array('id','uniacid','plugin','status'));
        if ($order['status']) {
            show_json(0, '状态错误');
        } else {
            $res = pdo_update('wlmerchant_disorder', array('status' => 1), array('id' => $id, 'status' => 0));
            if($res){
                $flag = pdo_get('wlmerchant_waittask', array('key' => 3, 'important' => $order['id']), array('id'));
                if (empty($flag)) {
                    $rushtask = array(
                        'type'    => $order['plugin'],
                        'orderid' => $order['id']
                    );
                    $rushtask = serialize($rushtask);
                    $_W['uniacid'] = $order['uniacid'];
                    Queue::addTask(3, $rushtask, time(), $order['id']);
                }
            }
        }
        if ($res) {
            show_json(1);
        } else {
            show_json(0, '修改失败，请重试');
        }
    }

    public function dislevel() {
        global $_W, $_GPC;
        $base = Setting::wlsetting_read('distribution');
        $default = pdo_getcolumn('wlmerchant_dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), 'id');
        if (empty($default)) {
            $default = array(
                'uniacid'    => $_W['uniacid'],
                'name'       => '默认',
                'createtime' => time(),
                'isdefault'  => 1
            );
            $res = pdo_insert(PDO_NAME . 'dislevel', $default);
            if (!$res) {
                wl_message('初始化失败！请重试', referer(), 'error');
            }
        }
        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_dislevel') . "WHERE uniacid = {$_W['uniacid']} ORDER BY levelclass ASC");

        include wl_template('disysbase/dislevel');
    }

    public function deletelevel() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        if ($id) {
            $res = pdo_delete('wlmerchant_dislevel', array('id' => $id));
        }
        if ($res) {
            show_json(1);
        }
    }

    public function editdistributor() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $distri = pdo_get('wlmerchant_distributor', array('id' => $id));
        if (empty($distri['dislevel'])) {
            $distri['dislevel'] = pdo_getcolumn(PDO_NAME . 'dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), 'id');;
        }
        $mid = pdo_getcolumn('wlmerchant_distributor', array('id' => $id), 'mid');
        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_dislevel') . "WHERE uniacid = {$_W['uniacid']} ORDER BY createtime ASC");
        if ($distri['leadid'] > 0) {
            $distri['leadname'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $distri['leadid']), 'nickname');
        } else if ($distri['leadid'] == -1) {
            $distri['leadname'] = '系统直属';
        } else {
            $distri['leadname'] = '暂无上级';
        }
        if ($_W['ispost']) {
            $data = array(
                'nickname' => trim($_GPC['nickname']),
                'realname' => trim($_GPC['realname']),
                'mobile'   => trim($_GPC['mobile']),
                'dislevel' => trim($_GPC['dislevel']),
                'leadid'   => trim($_GPC['leadid']),
                'source'   => trim($_GPC['source']),
            );
            //修改团队信息
            $leadinfo = pdo_get('wlmerchant_distributor',array('mid' => $data['leadid']),array('groupflag','onegroupid','twogroupid'));
            if($leadinfo['groupflag'] > 0){
                $data['onegroupid'] = $data['leadid'];
                $data['twogroupid'] = $leadinfo['onegroupid'];
            }else{
                $data['onegroupid'] = $leadinfo['onegroupid'];
                $data['twogroupid'] = $leadinfo['twogroupid'];
            }
            $res = pdo_update('wlmerchant_distributor', $data, array('id' => $id));
            //修改下级信息
            if($data['onegroupid'] > 0){
                if($distri['groupflag'] > 0){
                    pdo_update('wlmerchant_distributor', ['twogroupid' => $data['onegroupid']], array('onegroupid' => $distri['mid']));
                }else{
                    Distribution::changeGroupId($distri['mid'],$data['onegroupid'],$data['twogroupid']);
                }
            }
            //修改金额
            $money = trim($_GPC['money']);
            if (is_numeric($money) && $money > 0) {
                $money = sprintf("%.2f", $money);
                $type = $_GPC['moneytype'];
                $reason = $_GPC['reason'];
                if ($type == 1) {
                    $onedismoney = $distri['dismoney'] + $money;
                    $onenowmoney = $distri['nowmoney'] + $money;
                } else {
                    $onedismoney = $distri['dismoney'] - $money;
                    $onenowmoney = $distri['nowmoney'] - $money;
                }
                $changeflag = pdo_update('wlmerchant_distributor', array('dismoney' => $onedismoney, 'nowmoney' => $onenowmoney), array('id' => $distri['id']));
                if ($changeflag) {
                    Distribution::adddisdetail(0, $distri['mid'], -1, $type, $money, 'system', 1, $reason, $onenowmoney);
                }
            }

            if ($res || $changeflag) {
                $memid = pdo_getcolumn(PDO_NAME . 'distributor', array('id' => $id), 'mid');
                pdo_update('wlmerchant_member', array('nickname' => $data['nickname'], 'realname' => $data['realname'], 'mobile' => $data['mobile']), array('id' => $memid));
                show_json(1, '操作成功');
            } else {
                show_json(0, '操作失败,请重试');
            }

        }
        include wl_template('disysbase/distrilmodel');
    }

    public function editlevel() {
        global $_W, $_GPC;
        $base = Setting::wlsetting_read('distribution');
        $levelupstatusArray = unserialize($base['levelupstatus']);

        $id = $_GPC['id'];
        if ($id) {
            $level = pdo_get('wlmerchant_dislevel', array('id' => $id));
            $level['plugin'] = unserialize($level['plugin']);
        }
        if ($_W['ispost']) {
            if ($id) {
                $data = array(
                    'name'            => trim($_GPC['name']),
                    'twostatus'       => $_GPC['twostatus'],
                    'onecommission'   => $_GPC['onecommission'],
                    'twocommission'   => $_GPC['twocommission'],
                    'threecommission' => $_GPC['threecommission'],
                    'giftintegral'    => $_GPC['giftintegral'],
                    'upstandard'      => trim($_GPC['upstandard']),
                    'ownstatus'       => $_GPC['ownstatus'],
                    'plugin'          => serialize($_GPC['plugin']),
                    'upstandard1'     => trim($_GPC['upstandard1']),
                    'upstandard2'     => trim($_GPC['upstandard2']),
                    'upstandard3'     => trim($_GPC['upstandard3']),
                    'upstandard4'     => trim($_GPC['upstandard4']),
                    'upstandard5'     => trim($_GPC['upstandard5']),
                    'levelclass'      => trim($_GPC['levelclass']),
                );
                //判断层级问题
                if($data['levelclass'] <= 0 && $level['isdefault'] != 1){
                    show_json(0, '等级层级参数必须大于0');
                }
                $flag = pdo_getcolumn(PDO_NAME.'dislevel',array('uniacid'=>$_W['uniacid'],'levelclass'=>$data['levelclass']),'id');
                if($flag > 0 && $flag != $id){
                    show_json(0, '此等级层级参数已经存在，请修改');
                }
                $res = pdo_update('wlmerchant_dislevel', $data, array('id' => $id));
            } else {
                $data = array(
                    'uniacid'         => $_W['uniacid'],
                    'name'            => trim($_GPC['name']),
                    'onecommission'   => $_GPC['onecommission'],
                    'twocommission'   => $_GPC['twocommission'],
                    'threecommission' => $_GPC['threecommission'],
                    'giftintegral'    => $_GPC['giftintegral'],
                    'upstandard'      => trim($_GPC['upstandard']),
                    'ownstatus'       => $_GPC['ownstatus'],
                    'plugin'          => serialize($_GPC['plugin']),
                    'upstandard1'     => trim($_GPC['upstandard1']),
                    'upstandard2'     => trim($_GPC['upstandard2']),
                    'upstandard3'     => trim($_GPC['upstandard3']),
                    'upstandard4'     => trim($_GPC['upstandard4']),
                    'upstandard5'     => trim($_GPC['upstandard5']),
                    'levelclass'      => trim($_GPC['levelclass']),
                    'createtime'      => time()
                );
                if($data['levelclass'] <= 0){
                    show_json(0, '等级层级参数必须大于0');
                }
                $flag = pdo_getcolumn(PDO_NAME.'dislevel',array('uniacid'=>$_W['uniacid'],'levelclass'=>$data['levelclass']),'id');
                if($flag > 0){
                    show_json(0, '此等级层级参数已经存在，请修改');
                }
                $res = pdo_insert('wlmerchant_dislevel', $data);
            }
            if ($res) {
                show_json(1, '操作成功');
            } else {
                show_json(0, '操作失败,请重试');
            }
        }
        include wl_template('disysbase/dislevelmodel');
    }


    public function disdetail() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where['uniacid'] = $_W['uniacid'];
        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 1:
                        $where['leadid'] = $_GPC['keyword'];
                        break;
                    case 5:
                        $where['buymid'] = $_GPC['keyword'];
                        break;
                    case 3:
                        $where['price>'] = $_GPC['keyword'];
                        break;
                    case 4:
                        $where['price<'] = $_GPC['keyword'];
                        break;
                    default:
                        break;
                }
                if ($_GPC['keywordtype'] == 2) {
                    $keyword = $_GPC['keyword'];
                    $params[':nickname'] = "%{$keyword}%";
                    $goods = pdo_fetchall("SELECT id,nickname FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname", $params);
                    if ($goods) {
                        $goodids = "(";
                        foreach ($goods as $key => $v) {
                            if ($key == 0) {
                                $goodids .= $v['id'];
                            } else {
                                $goodids .= "," . $v['id'];
                            }
                        }
                        $goodids .= ")";
                        $where['leadid#'] = $goodids;
                    } else {
                        $where['leadid#'] = "(0)";
                    }
                }
                if ($_GPC['keywordtype'] == 6) {
                    $keyword = $_GPC['keyword'];
                    $params[':nickname'] = "%{$keyword}%";
                    $goods = pdo_fetchall("SELECT id,nickname FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname", $params);
                    if ($goods) {
                        $goodids = "(";
                        foreach ($goods as $key => $v) {
                            if ($key == 0) {
                                $goodids .= $v['id'];
                            } else {
                                $goodids .= "," . $v['id'];
                            }
                        }
                        $goodids .= ")";
                        $where['buymid#'] = $goodids;
                    } else {
                        $where['buymid#'] = "(0)";
                    }
                }
            }
        }
        if ($_GPC['orderstatus']) {
            $where['type'] = $_GPC['orderstatus'];
        }
        if ($_GPC['ordertype']) {
            $where['plugin'] = $_GPC['ordertype'];
        }

        if ($_GPC['time_limit'] && $_GPC['timetype'] > 0) {
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $where['createtime>'] = $starttime;
            $where['createtime<'] = $endtime;
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time() + 86400;
        }
        if ($_GPC['exportflag']) {
            $this->exportdetail($where);
        }
        $details = Util::getNumData('*', PDO_NAME . 'disdetail', $where, 'ID DESC', $pindex, $psize, 1);
        $pager = $details[1];
        $details = $details[0];
        foreach ($details as $key => &$detail) {
            $leadmember = pdo_get(PDO_NAME . 'member', array('id' => $detail['leadid']),['nickname','realname','encodename']);
            $detail['leadname'] = !empty($leadmember['encodename'])  ? base64_decode($leadmember['encodename']) : $leadmember['nickname'];
            $detail['leadrealname'] = $leadmember['realname'];

            if ($detail['buymid'] < 0) {
                $detail['buyname'] = '系统';
            } else if($detail['status'] == 1){
                $detail['buyname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $detail['buymid']), 'storename');
            }else{
                $buyname = pdo_get(PDO_NAME . 'member', array('id' => $detail['buymid']),['nickname','encodename']);
                $detail['buyname'] = !empty($buyname['encodename'])  ? base64_decode($buyname['encodename']) : $buyname['nickname'];
            }
            $detail['typetext'] = $detail['type'] == 1 ? '收入' : "支出";
            switch ($detail['plugin']) {
                case 'rush':
                    $detail['pluginname'] = '抢购订单';
                    $detail['pluginno'] = 1;
                    break;
                case 'groupon':
                    $detail['pluginname'] = '团购订单';
                    $detail['pluginno'] = 10;
                    break;
                case 'fightgroup':
                    $detail['pluginname'] = '拼团订单';
                    $detail['pluginno'] = 2;
                    break;
                case 'coupon':
                    $detail['pluginname'] = '卡券订单';
                    $detail['pluginno'] = 3;
                    break;
                case 'pocket':
                    $detail['pluginname'] = '掌上信息';
                    break;
                case 'halfcard':
                    $detail['pluginname'] = '一卡通';
                    break;
                case 'charge':
                    $detail['pluginname'] = '付费入驻';
                    break;
                case 'distribution':
                    if(Customized::init('distributionText') > 0){
                        $detail['pluginname'] = '付费申请共享股东';
                    }else{
                        $detail['pluginname'] = '付费申请分销商';
                    }
                    break;
                case 'cash':
                    if(Customized::init('distributionText') > 0){
                        $detail['pluginname'] = $detail['type'] == 1 ? '共享股东申请提现被驳回' : '共享股东申请提现';
                    }else {
                        $detail['pluginname'] = $detail['type'] == 1 ? '分销申请提现被驳回' : '分销申请提现';
                    }
                    break;
                case 'system':
                    $detail['pluginname'] = '后台修改:';
                    break;
                case 'bargain':
                    $detail['pluginname'] = '砍价活动:';
                    $detail['pluginno'] = 12;
                    break;
                case 'payonline':
                    $detail['pluginname'] = '在线买单:';
                    break;
                case 'consumption':
                    $detail['pluginname'] = '积分商城:';
                    break;
                case 'citycard':
                    $detail['pluginname'] = '同城名片:';
                    break;
                case 'citydelivery':
                    $detail['pluginname'] = '同城配送';
                    break;
                case 'activity':
                    $detail['pluginname'] = '同城活动';
                    break;
                case 'hotel':
                    $detail['pluginname'] = '酒店预约';
                    break;
                default:
                    $detail['pluginname'] = '未知插件';
                    break;
            }
            if($detail['status'] == 1 ){
                $detail['orderurl'] = web_url("order/wlOrder/orderdetail", array('orderid' => $detail['disorderid'], 'type' => $detail['pluginno']));
            } else  if ($detail['plugin'] != 'cash' && $detail['plugin'] != 'system') {
                $detail['orderurl'] = web_url("distribution/dissysbase/loworder", array('memid' => $detail['leadid'], 'disorder' => $detail['disorderid']));
            }

            if ($detail['plugin'] != 'cash' && $detail['plugin'] != 'distribution' && $detail['plugin'] != 'system') {
                if($detail['status'] == 1){
                    $detail['pluginname'] = $detail['pluginname'] . '业务员佣金';
                }else if($detail['status'] == 2){
                    if ($detail['rank'] == 1) {
                        $detail['pluginname'] = $detail['pluginname'] . '团长分红';
                    } else if ($detail['rank'] == 2) {
                        $detail['pluginname'] = $detail['pluginname'] . '子团队分红';
                    }
                }else if($detail['status'] == 3){
                    $detail['pluginname'] = $detail['pluginname'] . '股东分红';
                }else{
                    if ($detail['rank'] == 1) {
                        $detail['pluginname'] = $detail['pluginname'] . '一级分销';
                    } else if ($detail['rank'] == 2) {
                        $detail['pluginname'] = $detail['pluginname'] . '二级分销';
                    } else if ($detail['rank'] == 3) {
                        $detail['pluginname'] = $detail['pluginname'] . '三级分销';
                    }
                }

            }

            if ($detail['plugin'] == 'system') {
                $detail['pluginname'] = $detail['pluginname'] . $detail['reason'];
            }

            $detail['createtime'] = date('Y-m-d H:i:s', $detail['createtime']);
        }

//		wl_debug($details);
        include wl_template('disysbase/disdetail');
    }

    function exportdetail($where) {
        global $_W, $_GPC;

        $details = Util::getNumData('*', PDO_NAME . 'disdetail', $where, 'ID DESC', 0, 0, 1);
        $details = $details[0];
        foreach ($details as $key => &$detail) {
            $detail['leadname'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $detail['leadid']), 'nickname');
            if ($detail['buymid'] < 0) {
                $detail['buyname'] = '系统';
            } else {
                $detail['buyname'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $detail['buymid']), 'nickname');
            }
            $detail['typetext'] = $detail['type'] == 1 ? '收入' : "支出";
            switch ($detail['plugin']) {
                case 'rush':
                    $detail['pluginname'] = '抢购订单';
                    $detail['pluginno'] = 1;
                    break;
                case 'groupon':
                    $detail['pluginname'] = '团购订单';
                    $detail['pluginno'] = 10;
                    break;
                case 'fightgroup':
                    $detail['pluginname'] = '拼团订单';
                    $detail['pluginno'] = 2;
                    break;
                case 'coupon':
                    $detail['pluginname'] = '卡券订单';
                    $detail['pluginno'] = 3;
                    break;
                case 'pocket':
                    $detail['pluginname'] = '掌上信息';
                    break;
                case 'halfcard':
                    $detail['pluginname'] = '一卡通';
                    break;
                case 'charge':
                    $detail['pluginname'] = '付费入驻';
                    break;
                case 'distribution':
                    if(Customized::init('distributionText') > 0){
                        $detail['pluginname'] = '付费申请共享股东';
                    }else{
                        $detail['pluginname'] = '付费申请分销商';
                    }
                    break;
                case 'cash':
                    if(Customized::init('distributionText') > 0){
                        $detail['pluginname'] = '共享股东申请提现';
                    }else {
                        $detail['pluginname'] = '分销申请提现';
                    }
                    break;
                case 'system':
                    $detail['pluginname'] = '后台修改:';
                    break;
                case 'bargain':
                    $detail['pluginname'] = '砍价活动:';
                    $detail['pluginno'] = 12;
                    break;
                case 'payonline':
                    $detail['pluginname'] = '在线买单:';
                    break;
                case 'consumption':
                    $detail['pluginname'] = '积分商城:';
                    break;
                case 'citycard':
                    $detail['pluginname'] = '同城名片:';
                    break;
                case 'citydelivery':
                    $detail['pluginname'] = '同城配送';
                    break;
                case 'activity':
                    $detail['pluginname'] = '同城活动';
                    break;
                default:
                    $detail['pluginname'] = '未知插件';
                    break;
            }

            if ($detail['plugin'] != 'cash' && $detail['plugin'] != 'distribution' && $detail['plugin'] != 'system') {
                if ($detail['rank'] == 1) {
                    $detail['pluginname'] = $detail['pluginname'] . '一级分销';
                } else if ($detail['rank'] == 2) {
                    $detail['pluginname'] = $detail['pluginname'] . '二级分销';
                } else if ($detail['rank'] == 3) {
                    $detail['pluginname'] = $detail['pluginname'] . '三级分销';
                }else{
                    $detail['pluginname'] = $detail['pluginname'] . '业务员佣金';
                }
            }

            if ($detail['plugin'] == 'system') {
                $detail['pluginname'] = $detail['pluginname'] . $detail['reason'];
            }
            $detail['createtime'] = date('Y-m-d H:i:s', $detail['createtime']);
            //查询订单编号
            if ($detail['plugin'] == 'rush') {
                $orderid = pdo_getcolumn(PDO_NAME . 'disorder', array('id' => $detail['disorderid']), 'orderid');
                $detail['orderno'] = pdo_getcolumn(PDO_NAME . 'rush_order', array('id' => $orderid), 'orderno');
            } else if ($detail['plugin'] != 'cash' && $detail['plugin'] != 'system') {
                $orderid = pdo_getcolumn(PDO_NAME . 'disorder', array('id' => $detail['disorderid']), 'orderid');
                $detail['orderno'] = pdo_getcolumn(PDO_NAME . 'order', array('id' => $orderid), 'orderno');
            }
            $detail['orderno'] = $detail['orderno'] . "\t";
        }


        /* 输出表头 */
        $filter = array(
            'id'         => '记录id',
            'leadid'     => '分销商MID',
            'leadname'   => '分销商姓名',
            'orderno'    => '订单编号',
            'checkcode'  => '核销码',
            'typetext'   => '收支',
            'price'      => '金额',
            'buyname'    => '来源',
            'pluginname' => '描述',
            'createtime' => '时间',
        );
        if(Customized::init('distributionText') > 0) {
            $filter['leadid'] = '共享股东MID';
            $filter['leadname'] = '共享股东姓名';
        }
        $data = array();
        foreach ($details as $k => $v) {
            foreach ($filter as $key => $title) {
                $data[$k][$key] = $v[$key];
            }
        }
        util_csv::export_csv_2($data, $filter, '导出明细.csv');
        exit;
    }

    //编辑框筛选上级
    public function getuser() {
        global $_W, $_GPC;
        $where = "uniacid = {$_W['uniacid']} AND disflag = 1";
        $data = [];
        if (!empty($_GPC['search'])) {
            $where .= " AND (nickname LIKE '%".trim($_GPC['search'])."%' or mid  LIKE '%".trim($_GPC['search'])."%')";
        }else{
            $data[] = ['id' => -1, 'text' => '系统直属'];
            $data[] = ['id' => 0, 'text' => '暂无上级'];
        }
        $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_distributor')."WHERE {$where} ORDER BY id DESC LIMIT 100");

        foreach ($members as &$member) {
            $data[] = ['id' => $member['mid'], 'text' => $member['nickname'].'(MID:'.$member['mid'].')'];
        }
        die(json_encode($data));
    }
    /**
     * Comment: 禁用代理商
     * Author: zzw
     * Date: 2020/4/10 17:44
     */
    public function prohibit(){
        global $_GPC;
        $id  = $_GPC['id'];
        pdo_update(PDO_NAME."distributor",['disflag'=>-2],['id'=>$id]);
        show_json(1);
    }

    /**
     * Comment: 修改普通用户的分销商上级
     * Author: wlf
     * Date: 2020/09/07 10:14
     */
    public function changeleadid(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $distri = pdo_get('wlmerchant_distributor', array('mid' => $id));
        if(!empty($distri)){
            $distri['leadname'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$distri['leadid']),'nickname');
        }
        if ($_W['ispost']) {
            $leadid = trim($_GPC['leadid']);

            //修改团队信息
            $leadinfo = pdo_get('wlmerchant_distributor',array('mid' => $leadid),array('groupflag','onegroupid','twogroupid'));
            if($leadinfo['groupflag'] > 0){
                $onegroupid = $leadid;
                $twogroupid = $leadinfo['onegroupid'];
            }else{
                $onegroupid = $leadinfo['onegroupid'];
                $twogroupid = $leadinfo['twogroupid'];
            }
            if(empty($distri)){
                $indata = [
                    'uniacid' => $_W['uniacid'],
                    'mid'     => $id, 
                    'disflag' =>0,
                    'leadid'  =>$leadid,
                    'createtime'=>time(),
                    'updatetime'=>time(),
                    'onegroupid'=>$onegroupid,
                    'twogroupid'=>$twogroupid
                ];
                $res = pdo_insert(PDO_NAME . 'distributor', $indata);
                $distributorid = pdo_insertid();
                pdo_update('wlmerchant_member',array('distributorid' => $distributorid),array('id' => $id));
            }else{
                $data = array(
                    'leadid'   => $leadid,
                    'onegroupid'=>$onegroupid,
                    'twogroupid'=>$twogroupid
                );
                $res = pdo_update('wlmerchant_distributor', $data, array('id' => $distri['id']));
                //修改下级信息
                if($data['onegroupid'] > 0){
                    if($distri['groupflag'] > 0){
                        pdo_update('wlmerchant_distributor', ['twogroupid' => $data['onegroupid']], array('onegroupid' => $distri['mid']));
                    }else{
                        Distribution::changeGroupId($distri['mid'],$data['onegroupid'],$data['twogroupid']);
                    }
                }
            }
            if ($res) {
                show_json(1, '操作成功');
            } else {
                show_json(0, '操作失败,请重试');
            }

        }



        include wl_template('disysbase/lowpeoplemodel');
    }


    /**
     * Comment: 团长等级列表
     * Author: wlf
     * Date: 2022/10/19 10:19
     */
    public function grouplevel(){
        global $_W, $_GPC;
        $base = Setting::wlsetting_read('distribution');
        $default = pdo_getcolumn('wlmerchant_grouplevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), 'id');
        if (empty($default)) {
            $default = array(
                'uniacid'    => $_W['uniacid'],
                'name'       => '默认',
                'createtime' => time(),
                'isdefault'  => 1
            );
            $res = pdo_insert(PDO_NAME . 'grouplevel', $default);
            if (!$res) {
                wl_message('初始化失败！请重试', referer(), 'error');
            }
        }
        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_grouplevel') . "WHERE uniacid = {$_W['uniacid']} ORDER BY levelclass ASC");
        include wl_template('disysbase/grouplevel');

    }



    /**
     * Comment: 团长等级编辑
     * Author: wlf
     * Date: 2022/10/19 11:23
     */
    public function editgrouplevel(){
        global $_W, $_GPC;

        $id = $_GPC['id'];
        if ($id) {
            $level = pdo_get('wlmerchant_grouplevel', array('id' => $id));
        }
        if ($_W['ispost']) {
            if ($id) {
                $data = $_GPC['data'];
                //判断层级问题
                if($data['levelclass'] <= 0 && $level['isdefault'] != 1){
                    show_json(0, '等级层级参数必须大于0');
                }
                $flag = pdo_getcolumn(PDO_NAME.'grouplevel',array('uniacid'=>$_W['uniacid'],'levelclass'=>$data['levelclass']),'id');
                if($flag > 0 && $flag != $id){
                    show_json(0, '此等级层级参数已经存在，请修改');
                }
                $res = pdo_update('wlmerchant_grouplevel', $data, array('id' => $id));
            } else {
                $data = $_GPC['data'];
                $data['uniacid'] = $_W['uniacid'];
                $data['createtime'] = time();
                if($data['levelclass'] <= 0){
                    show_json(0, '等级层级参数必须大于0');
                }
                $flag = pdo_getcolumn(PDO_NAME.'grouplevel',array('uniacid'=>$_W['uniacid'],'levelclass'=>$data['levelclass']),'id');
                if($flag > 0){
                    show_json(0, '此等级层级参数已经存在，请修改');
                }
                $res = pdo_insert('wlmerchant_grouplevel', $data);
            }
            if ($res) {
                show_json(1, '操作成功');
            } else {
                show_json(0, '操作失败,请重试');
            }
        }
        include wl_template('disysbase/grouplevelmodel');

    }


    /**
     * Comment: 团长等级删除
     * Author: wlf
     * Date: 2022/10/19 15:30
     */
    public function deletegrouplevel(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        if ($id) {
            $res = pdo_delete('wlmerchant_grouplevel', array('id' => $id));
        }
        if ($res) {
            show_json(1);
        }
    }

    /**
     * Comment: 团长列表
     * Author: wlf
     * Date: 2022/10/19 15:58
     */

    public function grouplist(){
        global $_W, $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $wheres = array();
        $wheres['uniacid'] = $_W['uniacid'];
        $wheres['disflag'] = 1;
        $wheres['groupflag'] = 1;
        $dislevels = pdo_fetchall("SELECT id,name FROM " . tablename('wlmerchant_grouplevel') . "WHERE uniacid = {$_W['uniacid']} ORDER BY isdefault DESC,levelclass ASC");


        if($_GPC['levelid'] > 0){
            $wheres['grouplevel'] = $_GPC['levelid'];
        }

        $type = intval($_GPC['type']);
        $keyword = trim($_GPC['keyword']);
        if (!empty($keyword)) {
            switch ($type) {
                case 2 :
                    $wheres['mobile@'] .= $keyword;
                    break;
                case 3 :
                    $wheres['nickname@'] .= $keyword;
                    break;
                case 4:
                    $wheres['realname@'] .= $keyword;
                    break;
                case 5:
                    $wheres['mid@'] .= $keyword;
                    break;
            }
        }

        if ($_GPC['time_limit']) {
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            if ($_GPC['timetype']) {
                $wheres['updatetime>'] = $starttime;
                $wheres['updatetime<'] = $endtime;
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $list = Distribution::getNumDistributor('id,mid,grouplevel,shareholder,updatetime,dismoney,nowmoney', $wheres, 'updatetime DESC,createtime DESC', $pindex, $psize, 1);
        $pager = $list[1];
        $list = $list[0];
        if(!empty($list)){
            foreach ($list as &$lii){
                $member = pdo_get('wlmerchant_member',array('id' => $lii['mid']),array('mobile','nickname','encodename','avatar','realname'));
                $lii['nickname'] = !empty($member['encodename'])  ? base64_decode($member['encodename']) : $member['nickname'];
                $lii['avatar'] = $member['avatar'];
                $lii['mobile'] = $member['mobile'];
                $lii['realname'] = $member['realname'];
                $lii['lownum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_distributor')." WHERE onegroupid = {$lii['mid']}");
                $lii['lowdisnum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_distributor')." WHERE onegroupid = {$lii['mid']} AND groupflag = 1");
                $lii['rankname'] = pdo_getcolumn(PDO_NAME.'grouplevel',array('id'=>$lii['grouplevel']),'name');
            }
        }
        include wl_template('disysbase/grouplist');
    }


    /**
     * Comment: 添加团长
     * Author: wlf
     * Date: 2022/10/19 16:37
     */
    public function addgroup(){
        global $_W, $_GPC;
        $dislevels = pdo_fetchall("SELECT id,name FROM " . tablename('wlmerchant_grouplevel') . "WHERE uniacid = {$_W['uniacid']} ORDER BY isdefault DESC,levelclass ASC");
        if(empty($dislevels)){
            wl_message('请先添加团长等级', web_url('distribution/dissysbase/grouplevel'), 'error');
        }
        if($_W['ispost']){
            $disid = $_GPC['memberid'];
            $levelid = $_GPC['levelid'];
            $grouplevel = pdo_get('wlmerchant_grouplevel',array('id' => $levelid),array('shareholder'));
            
            $changeinfo = [
				'groupflag' => 1,
				'shareholder' => $grouplevel['shareholder'],
				'grouplevel' => $levelid,
				'updatetime' => time()
            ];
            if($grouplevel['shareholder'] > 0){
            	$changeinfo['sharetime'] = time();
            }
            $res = pdo_update('wlmerchant_distributor',$changeinfo,array('id' => $disid));
            if($res){
                //修改下级的团长信息
                $disinfo = pdo_get('wlmerchant_distributor',array('id' => $disid),array('mid'));
                Distribution::changeGroupId($disinfo['mid'],$disinfo['mid']);
                wl_message('添加成功', web_url('distribution/dissysbase/grouplist'), 'success');
            }else{
                wl_message('添加失败', referer(), 'error');
            }
        }

        include wl_template('disysbase/addgroup');

    }


    /**
     * Comment: 修改团长等级
     * Author: wlf
     * Date: 2022/10/19 18:25
     */
    public function editgroup(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $distri = pdo_get('wlmerchant_distributor', array('id' => $id),['grouplevel','id','sharetime']);
        $dislevels = pdo_fetchall("SELECT id,name FROM " . tablename('wlmerchant_grouplevel') . "WHERE uniacid = {$_W['uniacid']} ORDER BY isdefault DESC,levelclass ASC");

        if ($_W['ispost']) {
            $grouplevelid = $_GPC['grouplevel'];
            $grouplevel = pdo_get('wlmerchant_grouplevel',array('id' => $grouplevelid),array('shareholder'));
            
            $changeinfo = [
				'groupflag' => 1,
				'shareholder' => $grouplevel['shareholder'],
				'grouplevel' => $grouplevelid,
				'updatetime' => time()
            ];
            if($grouplevel['shareholder'] > 0 && empty($distri['sharetime'])){
            	$changeinfo['sharetime'] = time();
            }
            $res = pdo_update('wlmerchant_distributor',$changeinfo,array('id' => $id));
            if ($res) {
                show_json(1, '操作成功');
            } else {
                show_json(0, '操作失败,请重试');
            }
        }

        include wl_template('disysbase/groupmodel');

    }

    /**
     * Comment: 删除团长信息
     * Author: wlf
     * Date: 2022/10/19 18:46
     */
    public function deletegroup(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        //查询上级分销商
        $disinfo = pdo_get('wlmerchant_distributor',array('id' => $id),array('leadid','mid'));
        $updisinfo = pdo_get('wlmerchant_distributor',array('mid' => $disinfo['leadid']),array('mid','groupflag'));
        $res = pdo_update('wlmerchant_distributor',array('groupflag' => 0,'shareholder' => 0,'grouplevel' => 0,'updatetime' => time()),array('id' => $id));
        if($updisinfo['groupflag'] > 0){
            pdo_update('wlmerchant_distributor',array('onegroupid' => $updisinfo['mid'],'twogroupid' => 0),array('onegroupid' => $disinfo['mid']));
            pdo_update('wlmerchant_distributor',array('twogroupid' => $updisinfo['mid']),array('twogroupid' => $disinfo['mid']));
        }else{
            pdo_update('wlmerchant_distributor',array('onegroupid' =>0 ,'twogroupid' => 0),array('onegroupid' => $disinfo['mid']));
            pdo_update('wlmerchant_distributor',array('twogroupid' =>0),array('twogroupid' => $disinfo['mid']));
        }
        if ($res) {
            show_json(1, '操作成功');
        } else {
            show_json(0, '操作失败,请重试');
        }
    }


    public function grouponpeople(){
        global $_W, $_GPC;
        $mid = $_GPC['memid'];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $pageStart = $pindex * $psize - $psize;

        $where = " WHERE uniacid = {$_W['uniacid']} AND onegroupid = {$mid}";

        $type = intval($_GPC['type']);
        $keyword = trim($_GPC['keyword']);
        if (!empty($keyword)) {
            switch ($type) {
                case 2 :
                    $where .= " and mobile LIKE '%{$keyword}%'";
                    break;
                case 3 :
                    $where .= " and nickname LIKE '%{$keyword}%'";
                    break;
                case 4:
                    $where .= " and realname LIKE '%{$keyword}%'";
                    break;
                case 5:
                    $where .= " and mid LIKE '%{$keyword}%'";
                    break;
            }
        }
        $list = pdo_fetchall("SELECT mid,groupflag,disflag,grouplevel,dislevel FROM ".tablename('wlmerchant_distributor').$where." ORDER BY updatetime DESC LIMIT {$pageStart},{$psize} ");
        if(!empty($list)){
            foreach ($list as &$lii){
                $member = pdo_get('wlmerchant_member',array('id' => $lii['mid']),array('mobile','nickname','encodename','avatar','realname'));
                $lii['nickname'] = !empty($member['encodename'])  ? base64_decode($member['encodename']) : $member['nickname'];
                $lii['avatar'] = $member['avatar'];
                $lii['mobile'] = $member['mobile'];
                $lii['realname'] = $member['realname'];
                if($lii['disflag'] > 0){
                    if($lii['dislevel'] > 0){
                        $lii['dislevelname'] = pdo_getcolumn(PDO_NAME.'dislevel',array('id'=>$lii['dislevel']),'name');
                    }else{
                        $lii['dislevelname'] = pdo_getcolumn(PDO_NAME.'dislevel',array('uniacid'=>$_W['uniacid'],'isdefault' => 1),'name');
                    }
                }
                if($lii['groupflag'] > 0){
                    $lii['grouplevelname'] = pdo_getcolumn(PDO_NAME.'grouplevel',array('id'=>$lii['grouplevel']),'name');
                }

            }
        }
        $total = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_distributor').$where);
        $pager = wl_pagination($total, $pindex, $psize);


        include wl_template('disysbase/grouponpeople');

    }


}