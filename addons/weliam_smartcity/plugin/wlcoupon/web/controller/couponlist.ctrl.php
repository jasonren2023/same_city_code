<?php
defined('IN_IA') or exit('Access Denied');

class Couponlist_WeliamController {
    /*
     * 入口函数
     */
    function couponsList() {
        global $_W, $_GPC;
        $set = Setting::agentsetting_read('coupon');
        $diy = unserialize($set['coupon']);
        $isExport = $_GPC['export'] ? : 0;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $wheres = array();
        $wheres['uniacid'] = $_W['uniacid'];
        $wheres['aid'] = $_W['aid'];
        if (is_store()) {
            $wheres['merchantid'] = $_W['storeid'];
        }
        $status = $_GPC['status'];
        if ($status) {
            $wheres['status'] = $status;
        }
        if ($_GPC['type']) {
            $wheres['type'] = $_GPC['type'];
        }
        if ($_GPC['keywordtype'] == 1) {
            $wheres['@title@'] = trim($_GPC['keyword']);
        }
        if ($_GPC['keywordtype'] == 2) {
            $keyword = $_GPC['keyword'];
            $params[':storename'] = "%{$keyword}%";
            $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_merchantdata') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND storename LIKE :storename", $params);
            if ($merchants) {
                $sids = "(";
                foreach ($merchants as $key => $v) {
                    if ($key == 0) {
                        $sids .= $v['id'];
                    } else {
                        $sids .= "," . $v['id'];
                    }
                }
                $sids .= ")";
                $wheres['merchantid#'] = $sids;
            } else {
                $wheres['merchantid#'] = "(0)";
            }
        }
        if ($_GPC['time_limit'] && $_GPC['timetype']) {
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $wheres['starttime>'] = $starttime;
            $wheres['endtime<'] = $endtime;
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        //判断是否为导出操作
        if($isExport == 1){
            $whereData = Util::createStandardWhereString($wheres);
            foreach($whereData[1] as $key => $icon){
                $whereData[0] = str_replace($key,"'{$icon}'",$whereData[0]);
            }
            wlCoupon::exportCoupons($whereData[0]);
            die;
        }

        $coupons = wlCoupon::getNumCoupons('*', $wheres, 'indexorder DESC,id DESC', $pindex, $psize, 1);
        $pager = $coupons[1];
        $coupons = $coupons[0];
        foreach ($coupons as $key => &$value) {
            $coupons[$key]['discount'] = $coupons[$key]['discount'] / 10;
            $detail = pdo_get('wlmerchant_merchantdata', array('aid' => $_W['aid'], 'id' => $value['merchantid']));
            $coupons[$key]['storename'] = $detail['storename'];
            $value['surplus'] = WeliamWeChat::getSalesNum(4,$value['id']);
        }

        $statistics = " uniacid = {$_W['uniacid']} and aid={$_W['aid']}";
        if (is_store()) {
            $statistics .= " and merchantid = {$_W['storeid']}";
        }

        $status0 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'couponlist') . " WHERE {$statistics}");
        $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'couponlist') . " WHERE status=1 and {$statistics}");
        $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'couponlist') . " WHERE status=2 and {$statistics}");
        $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'couponlist') . " WHERE status=3 and {$statistics}");
        $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'couponlist') . " WHERE status=4 and {$statistics}");
        $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'couponlist') . " WHERE status=5 and {$statistics}");
        $status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'couponlist') . " WHERE status=6 and {$statistics}");
        $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'couponlist') . " WHERE status=8 and {$statistics}");

        include wl_template('coupon/coupon_list');
    }

    function delete() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'];
        if ($status == 1) {
            $res = pdo_update('wlmerchant_couponlist', array('status' => 4), array('id' => $id));
        } else {
            $rush = pdo_get('wlmerchant_couponlist',array('id' => $id),array('starttime','endtime','merchantid','status'));
            if(is_store()){
                $examine = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$rush['merchantid']),'audits');
                if(empty($examine)){
                    $changestatus = 5;
                }
            }
            if(empty($changestatus)){
                if ($rush['starttime'] > time()) {
                    $changestatus = 1;
                }
                else if ($rush['starttime'] < time() && time() < $rush['endtime']) {
                    $changestatus = 2;
                }
                else if ($rush['endtime'] < time()) {
                    $changestatus = 3;
                }
            }
            $res = pdo_update('wlmerchant_couponlist', array('status' => $changestatus), array('id' => $id));
        }
        if ($res) {
            die(json_encode(array('errno' => 0)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    function createcoupons() {
        global $_W, $_GPC;
        $set = Setting::agentsetting_read('coupon');
        $diy = unserialize($set['coupon']);
        if (p('userlabel')) {
            $labels = pdo_getall('wlmerchant_userlabel', array('uniacid' => $_W['uniacid']), array('id', 'name'), '', 'sort DESC');
        }
        //满减活动
        if(p('fullreduce')){
            $fullreducelist = pdo_getall('wlmerchant_fullreduce_list',array('aid' => $_W['aid'],'uniacid' => $_W['uniacid']),array('id','title'));
        }
        //支付有礼
        if(p('paidpromotion')){
            $paidlist = pdo_getall('wlmerchant_payactive',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));
        }
        //锦鲤抽奖
        if(agent_p('luckydraw')){
            $drawlist = pdo_getall('wlmerchant_luckydraw',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));
        }
        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
        $coupontype = $_GPC['coupontype'];
        if ($coupontype == 1 || $coupontype == '') {
            $coupontype = 1;
            $coupon_title = $diy['zkname'] ? $diy['zkname'] : '超级券';
        } elseif ($coupontype == 2) {
            $coupon_title = $diy['djname'] ? $diy['djname'] : '代金券';
        } elseif ($coupontype == 3) {
            $coupon_title = $diy['tcname'] ? $diy['tcname'] : '套餐券';
        } elseif ($coupontype == 4) {
            $coupon_title = $diy['tgname'] ? $diy['tgname'] : '团购券';
        } elseif ($coupontype == 5) {
            $coupon_title = $diy['yhname'] ? $diy['yhname'] : '超级券';
        }
        $url = h5_url('pages/mainPages/index/diypage?type=5');
        $location_store = pdo_getall('wlmerchant_merchantdata', array('uniacid' => $_W['uniacid']));
        foreach ($location_store as $key => &$v) {
            $asd = substr($v['logo'], 0, 4);
            if ($asd != 'http') {
                $v['logo'] = tomedia($v['logo']);
            }
        }
        if (p('distribution')) {
            $distriset = Setting::wlsetting_read('distribution');
        } else {
            $distriset = 0;
        }



        if (checksubmit('submit')) {
            $time = $_GPC['time_limit'];
            $starttime = strtotime($time['start']);
            $endtime = strtotime($time['end']);
            $group = array();
            $data = array(
                'status'             => $_GPC['status'],
                'type'               => $coupontype,
                'logo'               => !empty($_GPC['logo_url']) ? $_GPC['logo_url'] : wl_message('请上传卡券logo', referer(), 'error'),
                'indeximg'           => $_GPC['indeximg'],
                'is_charge'          => $_GPC['is_charge'],
                'price'              => $_GPC['price'],
                'viponedismoney'     => $_GPC['viponedismoney'],
                'viptwodismoney'     => $_GPC['viptwodismoney'],
                'vipthreedismoney'   => $_GPC['vipthreedismoney'],
                'is_show'            => $_GPC['is_show'],
                'color'              => $_GPC['color'],
                'title'              => !empty($_GPC['title']) ? $_GPC['title'] : wl_message('请输入卡券标题', referer(), 'error'),
                'sub_title'          => !empty($_GPC['sub_title']) ? $_GPC['sub_title'] : wl_message('请输入卡券简介', referer(), 'error'),
                'goodsdetail'        => !empty($_GPC['goodsdetail']) ? htmlspecialchars_decode($_GPC['goodsdetail']) : wl_message('请输入卡券详情', referer(), 'error'),
                'time_type'          => $_GPC['time_type'],
                'starttime'          => $starttime,
                'endtime'            => $endtime,
                'createtime'         => time(),
                'deadline'           => $_GPC['deadline'],
                'quantity'           => $_GPC['quantity'],
                'surplus'            => 0,
                'get_limit'          => $_GPC['get_limit'],
                'description'        => !empty($_GPC['description']) ? htmlspecialchars_decode($_GPC['description']) : wl_message('请输入购买须知', referer(), 'error'),
                'usetimes'           => $_GPC['usetimes']?$_GPC['usetimes']:1,
                'vipstatus'          => $_GPC['vipstatus'],
                'nostoreshow'        => $_GPC['nostoreshow'],
                'vipsettlementmoney' => $_GPC['vipsettlementmoney'],
                'overrefund'         => $_GPC['overrefund'],
                'allowapplyre'       => $_GPC['allowapplyre'],
                'fullreduceid'       => $_GPC['fullreduceid'],
                'paidid'             => $_GPC['paidid']
            );
            if (!is_store()) {
                $data['independent'] = $_GPC['independent'];
                $data['settlementmoney'] = $_GPC['settlementmoney'];
                $data['isdistri'] = $_GPC['isdistri'];
                $data['onedismoney'] = $_GPC['onedismoney'];
                $data['twodismoney'] = $_GPC['twodismoney'];
                $data['threedismoney'] = $_GPC['threedismoney'];
                $data['dissettime'] = $_GPC['dissettime'];
                //$data['is_indexshow'] = $_GPC['is_indexshow'];
                $data['indexorder'] = $_GPC['indexorder'];
                $data['pv'] = $_GPC['pv'];
                $data['merchantid'] = $_GPC['merchantid'];
            } else {
                $data['merchantid'] = $_W['storeid'];
            }

            //用户标签
            $userlabel = $_GPC['userlabel'];
            $data['userlabel'] = serialize($userlabel);
            //会员等级
            $level = $_GPC['level'];
            $data['level'] = serialize($level);
            //判断状态和权限
            if ($data['status'] == 1) {
                if (is_store()) {
                    $audits = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $_W['storeid']), 'audits');
                    if (empty($audits)) {
                        $data['status'] = 5;
                    } else if (($data['starttime'] < time() && $data['time_type'] == 1) || $data['time_type'] == 2) {
                        $data['status'] = 2;
                    } else if ($data['endtime'] < time() && $data['time_type'] == 1) {
                        $data['status'] = 3;
                    }
                } else if (($data['starttime'] < time() && $data['time_type'] == 1) || $data['time_type'] == 2) {
                    $data['status'] = 2;
                } else if ($data['endtime'] < time() && $data['time_type'] == 1) {
                    $data['status'] = 3;
                }
            }

            $goods = $_GPC['goods'] ? : [];
            if ($goods['usedatestatus'] == 1) {
                $goods['week'] = serialize($goods['week']);
                $goods['day'] = '';
            }
            if ($goods['usedatestatus'] == 2) {
                $goods['day'] = serialize($goods['day']);
                $goods['week'] = '';
            }
            $data = array_merge($data,$goods);

            $res = wlCoupon::saveCoupons($data);
            if ($res) {
                if ($data['status'] == 5) {  //发送审核消息
                    $storename = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $_W['storeid']), 'storename');
                    $first = '您好，您有一个待审核任务需要处理';
                    $type = '审核商品';
                    $content = '卡券名:' . $data['title'];
                    $status = '待审核';
                    $remark = '商户[' . $storename . ']上传了一个卡券待审核,请管理员尽快前往后台审核';
                    News::noticeAgent('storegood',$_W['aid'],$first,$type,$content,$status,$remark,time(),'');
                }
                wl_message('创建卡券成功', web_url('wlcoupon/couponlist/couponsList'), 'success');
            } else {
                wl_message('创建卡券失败', referer(), 'success');
            }
        }


        include wl_template('coupon/createcoupons');
    }

    function qrcodeimg() {
        global $_W, $_GPC;
        $url = $_GPC['url'];
        m('qrcode/QRcode')->png($url, false, QR_ECLEVEL_H, 4);
    }

    //删除
    function deleteCoupons() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        if ($id) {
            $res = wlCoupon::deleteCoupons(array('id' => $id));
            if ($res) {
                show_json(1, '彻底删除成功');
            } else {
                show_json(0, '彻底删除失败，请重试');
            }
        }
    }

    function cutoff() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        if ($id) {
            $res = pdo_update('wlmerchant_couponlist', array('status' => 8), array('id' => $id));
            if ($res) {
                show_json(1, '删除成功');
            } else {
                show_json(0, '删除失败，请重试');
            }
        }
    }

    //删除用户记录
    function deleteCoupon() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $ids = $_GPC['ids'];
        if ($id) {
            $res = wlCoupon::deleteCoupon(array('id' => $id));
            if ($res) {
                show_json(1);
            } else {
                show_json(0, '删除失败，请刷新重试');
            }
        }
        if ($ids) {
            foreach ($ids as $key => $id) {
                wlCoupon::deleteCoupon(array('id' => $id));
            }
            die(json_encode(array('errno' => 0, 'message' => '', 'id' => '')));
        }
    }

    //失效
    function disable() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $res = wlCoupon::updateCoupons(array('status' => 3), array('id' => $id));
        if ($res) {
            die(json_encode(array('errno' => 0, 'message' => '超级券已失效')));
        } else {
            die(json_encode(array('errno' => 1, 'message' => '设置超级券失效失败')));
        }
    }

    //审核
    function pass() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $flag = $_GPC['flag'];
        if ($flag) {
            $res = wlCoupon::updateCoupons(array('status' => 1), array('id' => $id));
        } else {
            $res = wlCoupon::updateCoupons(array('status' => 4), array('id' => $id));
        }
        if ($res) {
            die(json_encode(array('errno' => 0, 'message' => '')));
        } else {
            die(json_encode(array('errno' => 1, 'message' => '')));
        }
    }

    //编辑

    function editCoupons() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        if (p('distribution')) {
            $distriset = Setting::wlsetting_read('distribution');
        } else {
            $distriset = 0;
        }
        if($distriset['switch'] > 0){
            $dislevel = pdo_getall('wlmerchant_dislevel', array('uniacid' => $_W['uniacid']),['id','name']);
            $grouplevel = pdo_getall('wlmerchant_grouplevel', array('uniacid' => $_W['uniacid']),['id','name']);
        }
        $labels = pdo_getall('wlmerchant_userlabel', array('uniacid' => $_W['uniacid']), array('id', 'name'), '', 'sort DESC');
        //满减活动
        if(p('fullreduce')){
            $fullreducelist = pdo_getall('wlmerchant_fullreduce_list',array('aid' => $_W['aid'],'uniacid' => $_W['uniacid']),array('id','title'));
        }
        //支付有礼
        if(p('paidpromotion')){
            $paidlist = pdo_getall('wlmerchant_payactive',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));
        }
        //锦鲤抽奖
        if(agent_p('luckydraw')){
            $drawlist = pdo_getall('wlmerchant_luckydraw',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));
        }
        //获取自定义表单信息
        $formWhere = ['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']];
        if(is_store()) $formWhere['sid'] = $_W['storeid'];
        $diyFormList = pdo_getall(PDO_NAME."diyform",$formWhere,['id','title'],'','create_time DESC,id DESC');



        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
        $set = Setting::agentsetting_read('coupon');
        $diy = unserialize($set['coupon']);

        if($id > 0){
            $coupons = wlCoupon::getSingleCoupons($_GPC['id'], '*');
            //wl_debug($coupons);
            $storename = pdo_get('wlmerchant_merchantdata', array('id' => $coupons['merchantid']));
            $userlabel = unserialize($coupons['userlabel']);
            $coupons['level'] = unserialize($coupons['level']);
            $coupons['trtlevel'] = unserialize($coupons['transferlevel']);
            $coupons['adv'] = unserialize($coupons['adv']);
            foreach ($coupons as $key => $value) {
                $coupons['storename'] = $storename['storename'];
                $coupons['logolist'] = $storename['logo'];
            }
            $coupontype = $coupons['type'];
            $url = h5_url('pages/subPages/goods/index', ['type' => 5, 'id' => $id]);
            if ($coupontype == 1 || $coupontype == '') {
                $coupontype = 1;
                $coupon_title = $diy['zkname'] ? $diy['zkname'] : '超级券';
            } elseif ($coupontype == 2) {
                $coupon_title = $diy['djname'] ? $diy['djname'] : '代金券';
            } elseif ($coupontype == 3) {
                $coupon_title = $diy['tcname'] ? $diy['tcname'] : '套餐券';
            } elseif ($coupontype == 4) {
                $coupon_title = $diy['tgname'] ? $diy['tgname'] : '团购券';
            } elseif ($coupontype == 5) {
                $coupon_title = $diy['yhname'] ? $diy['yhname'] : '超级券';
            }

            if ($coupons['usedatestatus'] == 1) {
                $coupons['week'] = unserialize($coupons['week']);
            }
            if ($coupons['usedatestatus'] == 2) {
                $coupons['day'] = unserialize($coupons['day']);
            }
            //会员减免
            if($coupons['vipstatus'] == 1){
                $viparray = unserialize($coupons['viparray']);
            }
            if(empty($coupons['isdistri'])){
                $disarray = unserialize($coupons['disarray']);
            }
            if(!empty($coupons['grouparray'])){
                $grouparray = unserialize($coupons['grouparray']);
            }


            //支付方式设置
            $coupons['pay_type'] = unserialize($coupons['pay_type']);
        }else{
            $coupontype = 1;
            $coupon_title = $diy['zkname'] ? $diy['zkname'] : '超级券';
            $url = h5_url('pages/mainPages/index/diypage?type=5');
        }

        $location_store = pdo_getall('wlmerchant_merchantdata', array('uniacid' => $_W['uniacid']));
        foreach ($location_store as $key => &$v) {
            $asd = substr($v['logo'], 0, 4);
            if ($asd != 'http') {
                $v['logo'] = tomedia($v['logo']);
                $v['indeximg'] = tomedia($v['indeximg']);
            }
        }


        //添加信息
        if (checksubmit('submit')) {
            if ($_GPC['quantity'] < $coupons['surplus']) {
                wl_message('更新卡券失败,库存不能小于已售数量', referer(), 'error');
            }
            $time = $_GPC['time_limit'];
            $starttime = strtotime($time['start']);
            $endtime = strtotime($time['end']);
            $group = array();
            $data = array(
                'status'             => $_GPC['status'],
                'type'               => $coupontype,
                'logo'               => $_GPC['logo_url'],
                'wxapp_shareimg'     => $_GPC['wxapp_shareimg'],
                'indeximg'           => $_GPC['indeximg'],
                'is_charge'          => $_GPC['is_charge'],
                'is_show'            => $_GPC['is_show'],
                'price'              => $_GPC['price'],
                'viponedismoney'     => $_GPC['viponedismoney'],
                'viptwodismoney'     => $_GPC['viptwodismoney'],
                'vipthreedismoney'   => $_GPC['vipthreedismoney'],
                'color'              => $_GPC['color'],
                'title'              => $_GPC['title'],
                'sub_title'          => $_GPC['sub_title'],
                'goodsdetail'        => htmlspecialchars_decode($_GPC['goodsdetail']),
                'time_type'          => $_GPC['time_type'],
                'starttime'          => $starttime,
                'endtime'            => $endtime,
                'createtime'         => time(),
                'deadline'           => $_GPC['deadline'],
                'quantity'           => $_GPC['quantity'],
                'get_limit'          => $_GPC['get_limit'],
                'description'        => htmlspecialchars_decode($_GPC['description']),
                'usetimes'           => $_GPC['usetimes']?$_GPC['usetimes']:1,
                'vipstatus'          => $_GPC['vipstatus'],
                'nostoreshow'        => $_GPC['nostoreshow'],
                'vipsettlementmoney' => $_GPC['vipsettlementmoney'],
                'overrefund'         => $_GPC['overrefund'],
                'allowapplyre'       => $_GPC['allowapplyre'],
                'fullreduceid'       => $_GPC['fullreduceid'],
                'paidid'             => $_GPC['paidid'],
                'adv'                => serialize($_GPC['adv']) ? : serialize([]),
                'is_describe_tip'    => $_GPC['is_describe_tip'],
                'transferstatus'     => $_GPC['transferstatus'],
                'transfermore'       => $_GPC['transfermore']
            );
            if (!is_store()) {
                $data['independent'] = $_GPC['independent'];
                $data['settlementmoney'] = $_GPC['settlementmoney'];
                $data['isdistri'] = $_GPC['isdistri'];
                $data['isdistristatus'] = $_GPC['isdistristatus'];
                $data['onedismoney'] = $_GPC['onedismoney'];
                $data['twodismoney'] = $_GPC['twodismoney'];
                $data['threedismoney'] = $_GPC['threedismoney'];
                $data['dissettime'] = $_GPC['dissettime'];
                //$data['is_indexshow'] = $_GPC['is_indexshow'];
                $data['indexorder'] = $_GPC['indexorder'];
                $data['pv'] = $_GPC['pv'];
                $data['merchantid'] = $_GPC['merchantid'];
            }else {
                $data['merchantid'] = $_W['storeid'];
            }
            if(empty($data['merchantid'])){
                wl_message('请绑定卡券所属商户',referer(), 'error');
            }
            $goods = $_GPC['goods'] ? : [];
            if ($goods['usedatestatus'] == 1) {
                $goods['week'] = serialize($goods['week']);
                $goods['day'] = '';
            }else if ($goods['usedatestatus'] == 2) {
                $goods['day'] = serialize($goods['day']);
                $goods['week'] = '';
            }else{
                $goods['usedatestatus'] = 0;
                $goods['day'] = '';
                $goods['week'] = '';
            }
            $goods['yuecashback'] = sprintf("%.2f",$goods['yuecashback']);
            $goods['vipyuecashback'] = sprintf("%.2f",$goods['vipyuecashback']);
            //判断每次金额
            if($data['usetimes'] > 1){
                $goods['yuecashback'] = sprintf("%.2f",$goods['yuecashback']/$data['usetimes'])*$data['usetimes'];
                $goods['vipyuecashback'] = sprintf("%.2f",$goods['vipyuecashback']/$data['usetimes'])*$data['usetimes'];
            }
            $data = array_merge($data,$goods);

            $userlabel = $_GPC['userlabel'];
            $data['userlabel'] = serialize($userlabel);
            $data['pay_type'] = serialize($_GPC['pay_type']);
            //会员减免
            if($data['vipstatus'] == 1){
                $viparray = [];
                $vipleid = $_GPC['vipleid'];
                $vipprice = $_GPC['vipprice'];
                $storeset = $_GPC['storeset'];
                foreach($vipleid as $key => $vle){
                    $vipa['vipprice'] = sprintf("%.2f",$vipprice[$key]);
                    $vipa['storeset'] = sprintf("%.2f",$storeset[$key]);
                    $viparray[$vle] = $vipa;
                }
                $data['viparray'] = serialize($viparray);
            }
            //分销数组
            if(empty($data['isdistri'])){
                $disarray = [];
                $disleid = $_GPC['disleid'];
                $oneratio = $_GPC['oneratio'];
                $tworatio = $_GPC['tworatio'];
                foreach($disleid as $dkey => $dle){
                    $dlea['onedismoney'] = sprintf("%.2f",$oneratio[$dkey]);
                    $dlea['twodismoney'] = sprintf("%.2f",$tworatio[$dkey]);
                    $disarray[$dle] = $dlea;
                }
                $data['disarray'] = serialize($disarray);
            }

            //团长分红数组
            if($goods['disgroup'] > 0){
                $grouparray = [];
                $groupleid = $_GPC['groupleid'];
                $onegroupmoney = $_GPC['onegroupmoney'];
                $twogroupmoney = $_GPC['twogroupmoney'];
                foreach($groupleid as $gkey => $gle){
                    $glea['onegroupmoney'] = sprintf("%.2f",$onegroupmoney[$gkey]);
                    $glea['twogroupmoney'] = sprintf("%.2f",$twogroupmoney[$gkey]);
                    $grouparray[$gle] = $glea;
                }
                $data['grouparray'] = serialize($grouparray);
            }

            //会员等级
            $level = $_GPC['level'];
            $data['level'] = serialize($level);
            //会员等级
            $trtlevel = $_GPC['trtlevel'];
            $data['transferlevel'] = serialize($trtlevel);
            //判断状态和权限
            if ($data['status'] == 1) {
                if (is_store()) {
                    $audits = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $_W['storeid']), 'audits');
                    if (empty($audits)) {
                        $data['status'] = 5;
                    } else if (($data['starttime'] < time() && $data['time_type'] == 1) || $data['time_type'] == 2) {
                        $data['status'] = 2;
                    } else if ($data['endtime'] < time() && $data['time_type'] == 1) {
                        $data['status'] = 3;
                    }
                } else if (($data['starttime'] < time() && $data['time_type'] == 1) || $data['time_type'] == 2) {
                    $data['status'] = 2;
                } else if ($data['endtime'] < time() && $data['time_type'] == 1) {
                    $data['status'] = 3;
                }
            }
            if($id > 0){
                $res = wlCoupon::updateCoupons($data, array('id' => $id));
            }else{
                $res = wlCoupon::saveCoupons($data);
            }
            if ($res) {
                Tools::clearposter();
                if ($data['status'] == 5) {  //发送审核消息
                    $openids = pdo_getall('wlmerchant_agentadmin', array('aid' => $coupons['aid'], 'notice' => 1), array('mid'));
                    if ($openids) {
                        $storename = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $_W['storeid']), 'storename');
                        $type = '审核商品';
                        $content = '卡券名:' . $coupons['title'];
                        $status = '待审核';
                        $remark = '商户[' . $storename . ']上传了一个卡券待审核,请管理员尽快前往后台审核';
                        foreach ($openids as $key => $mem) {
                            News::jobNotice($mem['mid'], '', $type, $content, $status, $remark, time());
                        }
                    }
                }
                wl_message('更新卡券成功', web_url('wlcoupon/couponlist/couponsList',array('page'=>$_GPC['page'])), 'success');
            } else {
                wl_message('更新卡券失败', referer(), 'error');
            }
        }


        include wl_template('coupon/createcoupons');
    }


    function merbercoupon() {
        global $_W, $_GPC;

        $set = Setting::agentsetting_read('coupon');
        $diy = unserialize($set['coupon']);

        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $wheres = array();
        $wheres['uniacid'] = $_W['uniacid'];
        $wheres['aid'] = $_W['aid'];


        if (!empty($_GPC['type'])) {
            $wheres['type'] = $_GPC['type'];
        }
        if ($_GPC['status']) {
            if ($_GPC['status'] == 1) {
                $wheres['usetimes>'] = 1;
            } else {
                $wheres['usetimes'] = 0;
            }
        }

        if ($_GPC['keyword']) {
            if ($_GPC['keywordtype'] == 2) {
                $keyword = $_GPC['keyword'];
                $params[':nickname'] = "%{$keyword}%";
                $member = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND nickname LIKE :nickname", $params);
                if ($member) {
                    $mids = "(";
                    foreach ($member as $key => $v) {
                        if ($key == 0) {
                            $mids .= $v['id'];
                        } else {
                            $mids .= "," . $v['id'];
                        }
                    }
                    $mids .= ")";
                    $wheres['mid#'] = $mids;
                } else {
                    $wheres['mid#'] = "(0)";
                }
            }
            if ($_GPC['keywordtype'] == 1) {
                $keyword = $_GPC['keyword'];
                $params[':title'] = "%{$keyword}%";
                $coupons = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_couponlist') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND title LIKE :title", $params);
                if ($coupons) {
                    $parentids = "(";
                    foreach ($coupons as $key => $v) {
                        if ($key == 0) {
                            $parentids .= $v['id'];
                        } else {
                            $parentids .= "," . $v['id'];
                        }
                    }
                    $parentids .= ")";
                    $wheres['parentid#'] = $parentids;
                } else {
                    $wheres['parentid#'] = "(0)";
                }
            }
        }

        if (!empty($_GPC['time_limit']) && $_GPC['timetype']) {

            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);

            $wheres['createtime>'] = $starttime;
            $wheres['createtime<'] = $endtime + (3600 *24);
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        if (!empty($_GPC['parentid'])) {
            $wheres['parentid'] = $_GPC['parentid'];
            $_GPC['keywordtype'] = 1;
            $_GPC['keyword'] = pdo_getcolumn(PDO_NAME.'couponlist',array('id'=>$_GPC['parentid']),'title');
        }

        //导出
        if ($_GPC['export']) {
            $this->exportMemberCoupon($wheres);
        }

        $merber_coupon = wlCoupon::getNumCoupon('*', $wheres, 'ID DESC', $pindex, $psize, 1);
        $pager = $merber_coupon[1];
        $merber_coupon = $merber_coupon[0];
        foreach ($merber_coupon as $key => &$v) {
            $member = pdo_get('wlmerchant_member', array('id' => $v['mid']), array('avatar', 'nickname', 'mobile'));
            $coupon = pdo_get('wlmerchant_couponlist', array('id' => $v['parentid']), array('logo', 'title', 'merchantid'));
            $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $coupon['merchantid']), array('storename'));
            $v['avatar'] = $member['avatar'];
            $v['nickname'] = $member['nickname'];
            $v['mobile'] = $member['mobile'];
            $v['logo'] = $coupon['logo'];
            $v['title'] = $coupon['title'];
            $v['storename'] = $merchant['storename'];
            //判断是否使用过
            $order = pdo_get(PDO_NAME . 'order', array('plugin' => 'coupon', 'recordid' => $v['id']), array('neworderflag', 'id'));
            if ($order['neworderflag']) {
                $smallorder = pdo_getall('wlmerchant_smallorder', array('status' => 2, 'orderid' => $order['id'], 'plugin' => 'coupon'), array('hexiaotime'));
                if (!empty($smallorder)) {
                    $v['useflag'] = 1;
                }
            } else {
                $coupon['usedtime'] = unserialize($coupon['usedtime']);
                if (!empty($coupon['usedtime'])) {
                    $v['useflag'] = 1;
                }
            }
            //判断是否有转赠记录
            $v['trflag'] = pdo_getcolumn(PDO_NAME.'transferRecord',array('type'=>1,'objid'=>$v['id']),'id');
        }

        //统计信息获取
        $all_num = wlCoupon::getNumCoupon('id', array_merge($wheres, ['aid'=>$_W['aid'],'uniacid' => $_W['uniacid']]), 'ID DESC', 0, 0, 1)[2];

        $use_num = wlCoupon::getNumCoupon('id', array_merge($wheres, ['aid'=>$_W['aid'],'uniacid' => $_W['uniacid'] , 'status'  => 2]), 'ID DESC', 0, 0, 1)[2];
        $end_num = wlCoupon::getNumCoupon('id', array_merge($wheres, ['aid'=>$_W['aid'],'uniacid' => $_W['uniacid'] , 'usetimes>' => 0 ,'endtime<' => time()]), 'ID DESC', 0, 0, 1)[2];
        if($use_num > 0) $use_rate = sprintf("%0.2f",$use_num/$all_num*100);
        else $use_rate = 0;
        $transfer_num = wlCoupon::getNumCoupon('id', array_merge($wheres, ['aid'=>$_W['aid'],'uniacid' => $_W['uniacid'],'transferflag'=>1]), 'ID DESC', 0, 0, 1)[2];

        //	wl_debug($merber_coupon);
        include wl_template('coupon/merber_coupons');
    }


    //导出用户记录
    function exportMemberCoupon($wheres) {
        global $_W, $_GPC;
        $merber_coupon = wlCoupon::getNumCoupon('*', $wheres, 'ID DESC', 0, 0, 0);
        $merber_coupon = $merber_coupon[0];
        foreach ($merber_coupon as &$v) {
            $smallorders = pdo_getall('wlmerchant_smallorder',array('plugin' => 'coupon','orderno' => $v['orderno']),array('checkcode','status','hexiaotime','hxuid'));
            $member = pdo_get('wlmerchant_member', array('id' => $v['mid']), array('nickname', 'mobile'));
            $coupon = pdo_get('wlmerchant_couponlist', array('id' => $v['parentid']), array('title', 'merchantid'));
            $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $coupon['merchantid']), array('storename'));
            $v['nickname'] = $member['nickname'];
            $v['mobile'] = $member['mobile'];
            $v['title'] = $coupon['title'];
            $v['storename'] = $merchant['storename'];
            $v['orderno'] = "\t".$v['orderno']."\t";
            $v['concode'] = '';
            $v['hexiaotime'] = '';
            $v['vermember'] = '';
            foreach($smallorders as $sk => $sma){
                //核销码
                if($sk > 0){
                    $v['concode'] .= '/';
                }
                $v['concode'] .= $sma['checkcode'];
                //核销时间和核销员
                if($sk > 0){
                    $v['hexiaotime'] .= '/';
                    $v['vermember'] .= '/';
                }
                if($sma['hexiaotime'] > 0){
                    $v['hexiaotime'] .= date('Y-m-d H:i:s',$sma['hexiaotime']);
                    if($sma['hxuid']>0){
                        $hxname = pdo_getcolumn(PDO_NAME.'merchantuser',array('id'=>$sma['hxuid']),'name');
                        $v['vermember'] .= $hxname;
                    }else{
                        $v['vermember'] .= '后台核销';
                    }
                }else{
                    $v['hexiaotime'] .= '未核销';
                    $v['vermember'] .= '未核销';
                }
            }
        }
        /* 输出表头 */
        $filter = array(
            'orderno'    => '订单编号',
            'title'      => '卡券名称',
            'storename'  => '所属商家',
            'nickname'   => '买家昵称',
            'mobile'     => '买家电话',
            'price'      => '卡券价格',
            'concode'    => '核销码',
            'endtime'    => '过期时间',
            'usetimes'   => '剩余使用次数',
            'hexiaotime' => '核销时间',
            'vermember'  => '核销员'
        );
        $orderlist = $merber_coupon;
        $data = array();
        for ($i = 0; $i < count($orderlist); $i++) {
            foreach ($filter as $key => $title) {
                if ($key == 'endtime') {
                    $data[$i][$key] = date('Y-m-d H:i:s', $orderlist[$i][$key]);
                } else if ($key == 'price') {
                    if (empty($orderlist[$i][$key])) {
                        $data[$i][$key] = '免费';
                    } else {
                        $data[$i][$key] = sprintf("%.2f", $orderlist[$i][$key]);
                    }
                }else {
                    $data[$i][$key] = $orderlist[$i][$key];
                }

            }
        }
        util_csv::export_csv_2($data, $filter, '用户卡券记录表.csv');
        exit();

    }

    function base() {
        global $_W, $_GPC;
        $base = Setting::agentsetting_read('coupon');
        $coupon = unserialize($base['coupon']);
        if (checksubmit('submit')) {
            $data = $_GPC['base'];
            Setting::agentsetting_save($data, 'coupon');
            wl_message('保存设置成功！', referer(), 'success');
        }
        $communitylist = pdo_getall('wlmerchant_community', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), array('id', 'communname'));

        include wl_template('coupon/base');
    }

    function writroff() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $num = pdo_getcolumn(PDO_NAME . 'member_coupons', array('id' => $id), 'usetimes');
        $res = wlCoupon::hexiaoorder($id, -1, $num, 3);
        if ($res) {
            show_json(1);
        } else {
            show_json(0, '核销失败，请刷新重试');
        }
    }


    function hexiaotime() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $coupon = pdo_get('wlmerchant_member_coupons', array('id' => $id), array('usetimes', 'usedtime'));
        $order = pdo_get(PDO_NAME . 'order', array('plugin' => 'coupon', 'recordid' => $id), array('neworderflag', 'id'));
        if ($order['neworderflag']) {
            $smallorder = pdo_getall('wlmerchant_smallorder', array('status' => 2, 'orderid' => $order['id'], 'plugin' => 'coupon'), array('hexiaotime'));
            $coupon['usedtime'] = array();
            foreach ($smallorder as $small) {
                $coupon['usedtime'][] = date('Y-m-d H:i:s', $small['hexiaotime']);
            }
            $coupon['usetimes'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_smallorder') . " WHERE status = 1 AND orderid = {$order['id']} AND plugin = 'coupon'");
        } else {
            $coupon['usedtime'] = unserialize($coupon['usedtime']);
            foreach ($coupon['usedtime'] as $key => &$v) {
                $v = date('Y-m-d H:i:s', $v['time']);
            }
        }
        die(json_encode(array('errno' => 0, 'times' => $coupon['usetimes'], 'data' => $coupon['usedtime'])));
    }

    function todetail() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $coupon = pdo_get('wlmerchant_couponlist', array('id' => $id), array('goodsdetail', 'description'));
        $data = $coupon['goodsdetail'];
        $data2 = $coupon['description'];
        die(json_encode(array('errno' => 0, 'data' => $data, 'data2' => $data2)));
    }

    function orderlist() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $wheres = array();
        $wheres['uniacid'] = $_W['uniacid'];
        $wheres['aid'] = $_W['aid'];
        $wheres['plugin'] = 'coupon';
        $sel1 = array(
            array('id' => 1, 'name' => '超级券名称'),
            array('id' => 2, 'name' => '用户昵称'),
            array('id' => 3, 'name' => '按支付时间'),
        );
        if ($_GPC['sel']['parentid'] == 1) {
            $keyword = $_GPC['keyword'];
            $params[':title'] = "%{$keyword}%";
            $coupons = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_couponlist') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND title LIKE :title", $params);
            if ($coupons) {
                $parentids = "(";
                foreach ($coupons as $key => $v) {
                    if ($key == 0) {
                        $parentids .= $v['id'];
                    } else {
                        $parentids .= "," . $v['id'];
                    }
                }
                $parentids .= ")";
                $wheres['fkid#'] = $parentids;
            } else {
                $wheres['fkid#'] = "(0)";
            }
        }
        if ($_GPC['sel']['parentid'] == 2) {
            $keyword = $_GPC['keyword'];
            $params[':nickname'] = "%{$keyword}%";
            $member = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND nickname LIKE :nickname", $params);
            if ($member) {
                $mids = "(";
                foreach ($member as $key => $v) {
                    if ($key == 0) {
                        $mids .= $v['id'];
                    } else {
                        $mids .= "," . $v['id'];
                    }
                }
                $mids .= ")";
                $wheres['mid#'] = $mids;
            } else {
                $wheres['mid#'] = "(0)";
            }
        }
        if ($_GPC['sel']['parentid'] == 3) {
            if (!empty($_GPC['time_limit'])) {
                $starttime = strtotime($_GPC['time_limit']['start']);
                $endtime = strtotime($_GPC['time_limit']['end']);
                $wheres['paytime>'] = $starttime;
                $wheres['paytime<'] = $endtime;
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        if ($_GPC['orderid']) {
            $wheres['id'] = $_GPC['orderid'];
        }
        $orders = wlCoupon::getNumCouponOrder('*', $wheres, 'ID DESC', $pindex, $psize, 1);
        $pager = $orders[1];
        $orders = $orders[0];
        foreach ($orders as $key => &$v) {
            $coupon = pdo_get('wlmerchant_couponlist', array('id' => $v['fkid']), array('title', 'logo'));
            $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $v['sid']), array('storename'));
            $member = pdo_get('wlmerchant_member', array('id' => $v['mid']), array('nickname', 'avatar', 'mobile'));
            $v['title'] = $coupon['title'];
            $v['logo'] = tomedia($coupon['logo']);
            $v['storename'] = $merchant['storename'];
            $v['nickname'] = $member['nickname'];
            $v['avatar'] = $member['avatar'];
            $v['mobile'] = $member['mobile'];
        }

        if ($_GPC['export'] != '') {
            $this->export($wheres);
        }
        include wl_template('coupon/orderlist');
    }

    public function export($wheres) {
        if (empty($wheres)) return FALSE;
        set_time_limit(0);
        $list = wlCoupon::getNumCouponOrder('*', $wheres, 'ID DESC', 0, 0, 0);
        $list = $list[0];
        foreach ($list as $key => &$v) {
            $coupon = pdo_get('wlmerchant_couponlist', array('id' => $v['fkid']), array('title', 'logo'));
            $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $v['sid']), array('storename'));
            $member = pdo_get('wlmerchant_member', array('id' => $v['mid']), array('nickname', 'avatar', 'mobile'));
            $v['title'] = $coupon['title'];
            $v['logo'] = tomedia($coupon['logo']);
            $v['storename'] = $merchant['storename'];
            $v['nickname'] = $member['nickname'];
            $v['avatar'] = $member['avatar'];
            $v['mobile'] = $member['mobile'];
            $v['paytime'] = date('Y-m-d H:i:s', $v['paytime']);
        }

        /* 输入到CSV文件 */
        $html = "\xEF\xBB\xBF";
        /* 输出表头 */
        $filter = array(
            'orderno'     => '订单号',
            'title'       => '卡券名称',
            'num'         => '数量',
            'storename'   => '所属商家',
            'nickname'    => '买家昵称',
            'mobile'      => '买家电话',
            'status'      => '订单状态',
            'paytype'     => '支付方式',
            'paytime'     => '支付时间',
            'price'       => '支付金额',
            'adminremark' => '备注'
        );
        foreach ($filter as $key => $title) {
            $html .= $title . "\t,";
        }
        $html .= "\n";
        foreach ($list as $k => $v) {
            foreach ($filter as $key => $title) {
                if ($key == 'status') {
                    switch ($v[$key]) {
                        case '1':
                            $html .= '已支付' . "\t, ";
                            break;
                        case '2':
                            $html .= '待评价' . "\t, ";
                            break;
                        default:
                            $html .= '未支付' . "\t, ";
                            break;
                    }
                } elseif ($key == 'paytype') {
                    switch ($v[$key]) {
                        case '1':
                            $html .= '余额支付' . "\t, ";
                            break;
                        case '2':
                            $html .= '微信支付' . "\t, ";
                            break;
                        case '3':
                            $html .= '支付宝支付' . "\t, ";
                            break;
                        case '4':
                            $html .= '货到付款' . "\t, ";
                            break;
                        default:
                            $html .= '其他或未支付' . "\t, ";
                            break;
                    }
                } else {
                    $html .= $v[$key] . "\t, ";
                }
            }
            $html .= "\n";
        }
        /* 输出CSV文件 */
        header("Content-type:text/csv");
        header("Content-Disposition:attachment; filename=卡券订单.csv");
        echo $html;
        exit();
    }

    function remark() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $remark = $_GPC['remark'];
        $res = pdo_update('wlmerchant_order', array('remark' => $remark), array('id' => $id));
        if ($res) {
            die(json_encode(array('errno' => 0, 'message' => $res, 'id' => $id)));
        } else {
            die(json_encode(array('errno' => 2, 'message' => $res, 'id' => $id)));
        }
    }

    function deleteOrder() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $res = pdo_delete('wlmerchant_order', array('id' => $id));
        if ($res) {
            die(json_encode(array('errno' => 0, 'message' => $res, 'id' => $id)));
        } else {
            die(json_encode(array('errno' => 2, 'message' => $res, 'id' => $id)));
        }
    }

    function copycoupon() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $coupon = wlCoupon::getSingleCoupons($id, '*');
        unset($coupon['id']);
        $coupon['status'] = 4;
        $coupon['surplus'] = 0;
        $res = wlCoupon::saveCoupons($coupon);
        if ($res) {
            die(json_encode(array('errno' => 0, 'message' => '', 'id' => $id)));
        } else {
            die(json_encode(array('errno' => 2, 'message' => '', 'id' => $id)));
        }
    }

    /**
     * Comment: 批量修改商品信息
     * Author: wlf
     * Date: 2020/06/01 14:38
     */
    public function changestatus(){
        global $_W, $_GPC;
        $ids = $_GPC['ids'];
        $type = $_GPC['type'];
        foreach ($ids as$k=>$v){
            $rush = pdo_get('wlmerchant_couponlist',array('id' => $v),array('starttime','endtime','merchantid','status'));
            if($type == 1){
                $status = 0;
                if(is_store()){
                    $examine = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$rush['merchantid']),'audits');
                    if(empty($examine)){
                        $status = 5;
                    }
                }
                if(empty($status)){
                    if ($rush['starttime'] > time()) {
                        $status = 1;
                    }
                    else if ($rush['starttime'] < time() && time() < $rush['endtime']) {
                        $status = 2;
                    }
                    else if ($rush['endtime'] < time()) {
                        $status = 3;
                    }
                }
                pdo_update('wlmerchant_couponlist', array('status' => $status), array('id' => $v));
            }else if($type == 8 && $rush['status'] == 8){
                wlCoupon::deleteCoupon(array('id' => $v));
            }else{
                pdo_update('wlmerchant_couponlist', array('status' => $type), array('id' => $v));
            }
        }
        show_json(1, '操作成功');
    }

    public function couponSend(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $coupon = wlCoupon::getSingleCoupons($id, '*');

        if ($_W['ispost']) {
            $users = $_GPC['mids'];
            if (!empty($users)) {
                foreach ($users as $user) {
                    wlCoupon::coupon_send($user,$coupon);
                }
            }
            show_json(1 , ['url' => referer()]);
        }

        include wl_template('coupon/list_send');
    }

    public function couponSendCsv(){
        global $_W, $_GPC;
        //$id = $_GPC['couponsid'];
        $name = $_GPC['name'];//文件储存路径
        $fullName = PATH_ATTACHMENT . $name;//文件在本地服务器暂存地址
        #2、读取excel中的内容
        $info = util_csv::read_csv_lines($fullName, 999, 0);
        unlink($fullName);//获取文件信息后将.cvs文件删除
        #3、对读取到的信息进行处理
        foreach ($info as $k => &$v) {
            //3-1 判断是否存在数据 不存在是空行，不进行任何操作
            if (!is_array($v)) {
                unset($info[$k]);
                continue;
            }
            //3-2 编码转换  由gbk转为urf-8
            $separator = '*separator*';//分割符 写成长字符串 防止出错
            $encodres = mb_detect_encoding(implode($separator, $v), array("ASCII","GB2312","GBK","UTF-8"));
            if($encodres != 'UTF-8'){
                $v = explode($separator, iconv('gbk', 'utf-8', implode($separator, $v)));
            }
            $id = $v[0];
            $getMember = pdo_get('wlmerchant_member',array('mobile' => $v[8],'uniacid' => $_W['uniacid']),array('id','nickname'));
            if(empty($getMember)){
                $v['send_result'] = '手机号不存在，无法发放';
                continue;
            }
            $coupon = wlCoupon::getSingleCoupons($id, '*');
            $res = wlCoupon::coupon_send($getMember['id'],$coupon);
            if(is_error($res)){
                $v['send_result'] = $res['message'];
            }else{
                $v['send_result'] = '发放成功';
            }
        }
        #4、定义结果表格的标题
        $filter = array(
            0  => 'ID',
            1  => '标题' ,
            2  => '店铺' ,
            3  => '价格' ,
            4  => '总数量/已售数量' ,
            5  => '每人限量' ,
            6  => '使用期限' ,
            7  => '状态' ,
            8  => '发放手机号',
            'send_result' => '发放结果',
        );
        #5、返回批量发货的结果信息表
        util_csv::save_csv($info, $filter, $_W['uniacid'].'/'.date('Y-m-d',time()).'/'.'批量发放结果信息'.date('Y-m-d',time()).'.csv');
        util_csv::export_csv_2($info, $filter, '批量发放结果信息'.date('Y-m-d',time()).'.csv');
    }

    /**
     * Comment: 转赠记录
     */
    public function record_transfer(){
        global $_W , $_GPC;
        $id = $_GPC['id'];
        $record = pdo_getall('wlmerchant_transferRecord',array('objid' => $id,'type' =>1),'','','createtime DESC');
        if(!empty($record)){
            foreach ($record as &$re){
                $omember = pdo_get(PDO_NAME.'member',array('id'=>$re['omid']),['nickname','avatar']);
                $re['nickname'] = $omember['nickname'];
                $re['avatar'] = tomedia($omember['avatar']);
                if($re['nmid'] > 0){
                    $nmember = pdo_get(PDO_NAME.'member',array('id'=>$re['nmid']),['nickname','avatar']);
                    $re['getnickname'] = $nmember['nickname'];
                    $re['getavatar'] = $nmember['avatar'];
                }
            }
        }
        include wl_template('coupon/record_transfer');
    }

}

