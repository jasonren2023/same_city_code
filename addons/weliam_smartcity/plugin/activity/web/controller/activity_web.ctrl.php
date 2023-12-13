<?php
defined('IN_IA') or exit('Access Denied');

class Activity_web_WeliamController {

    function activitylist(){
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = array('uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']);
        if (is_store()) {
            $where['sid'] = $_W['storeid'];
        }
        $status = $_GPC['status'];
        if(empty($status) || $status == 'all'){
            $_GPC['status'] = 'all';
        }else if($status == 9){
            $where['status'] = 0;
        }else {
            $where['status'] = $status;
        }

        if (!empty($_GPC['keyword'])){
            if(!empty($_GPC['keywordtype'])){
                switch($_GPC['keywordtype']){
                    case 1: $where['@title@'] = $_GPC['keyword'];break;
                    case 2: $where['id'] = $_GPC['keyword'];break;
                    default:break;
                }
                if($_GPC['keywordtype'] == 3){
                    $keyword = $_GPC['keyword'];
                    $params[':storename'] = "%{$keyword}%";
                    $merchants = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_merchantdata')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND storename LIKE :storename",$params);
                    if($merchants){
                        $sids = "(";
                        foreach ($merchants as $key => $v) {
                            if($key == 0){
                                $sids.= $v['id'];
                            }else{
                                $sids.= ",".$v['id'];
                            }
                        }
                        $sids.= ")";
                        $where['sid#'] = $sids;
                    }else {
                        $where['sid#'] = "(0)";
                    }
                }
            }
        }


        $lists = Util::getNumData('*','wlmerchant_activitylist',$where,'sort DESC',$pindex,$psize,1);
        $pager = $lists[1];
        $lists = $lists[0];

        foreach ($lists as $key => &$list) {
            $list['storename'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$list['sid']),'storename');
            $list['alreadypay'] = WeliamWeChat::getSalesNum(6,$list['id'],0,2);  //已报名
            if(empty($list['alreadypay'])){$list['alreadypay'] = 0;}
            $list['alreadyuse'] = WeliamWeChat::getSalesNum(6,$list['id'],0,3);   //已完成
            if(empty($list['alreadyuse'])){$list['alreadyuse'] = 0;}
            if($list['optionstatus'] > 0){
                $options = pdo_getall('wlmerchant_activity_spec',array('activityid' => $list['id']),array('price'));
                $prices = array_column($options,'price');
                $list['minprice'] = min($prices);
                $list['maxprice'] = max($prices);
            }
        }

        if (is_store()) {
            $statusall = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status9 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} and sid = {$_W['storeid']} AND status = 0");
            $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} and sid = {$_W['storeid']} AND status = 5");
            $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} and sid = {$_W['storeid']} AND status = 1");
            $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} and sid = {$_W['storeid']} AND status = 2");
            $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} and sid = {$_W['storeid']} AND status = 3");
            $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} and sid = {$_W['storeid']} AND status = 4");
        }else{
            $statusall = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']}");
            $status9 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} AND status = 0");
            $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} AND status = 5");
            $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} AND status = 1");
            $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} AND status = 2");
            $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} AND status = 3");
            $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'activitylist') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} AND status = 4");
        }

        include  wl_template('activity/activitylist');
    }


    function createactivity(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        if(p('distribution')){
            $distriset = Setting::wlsetting_read('distribution');
        }else{
            $distriset = 0;
        }
        if($distriset['switch'] > 0){
            $dislevel = pdo_getall('wlmerchant_dislevel', array('uniacid' => $_W['uniacid']),['id','name']);
        }
        //分类
        $cate = pdo_getall('wlmerchant_activity_category',array('status' => 1,'uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']),array('name','id'),'','sort DESC');
        //自定义表单
        $formWhere = ['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']];
        if(is_store()) $formWhere['sid'] = $_W['storeid'];
        $diyform = pdo_getall(PDO_NAME."diyform",$formWhere,['id','title'],'','create_time DESC,id DESC');

        //自定义海报
        if(p('diyposter')){
            $posterlist = pdo_getall(PDO_NAME . 'poster' , ['uniacid' => $_W['uniacid'] , 'type' => 14] , ['id' , 'title']);
        }
        //锦鲤抽奖
        if(agent_p('luckydraw')){
            $drawlist = pdo_getall('wlmerchant_luckydraw',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));
        }
        //会员等级
        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
        if($id){
            $active = pdo_get('wlmerchant_activitylist',array('id' => $id));
            $merchant = Rush::getSingleMerchant($active['sid'],'id,storename,logo');
            $active['thumbs'] = unserialize($active['thumbs']);
            $active['advs'] = unserialize($active['advs']);
            if($active['optionstatus']>0){
                $specs = pdo_getall('wlmerchant_activity_spec',array('uniacid' => $_W['uniacid'],'activityid' =>$id));
                foreach($specs as &$spsp){
                    $spsp['viparray'] = unserialize($spsp['viparray']);
                    $spsp['disarray'] = unserialize($spsp['disarray']);
                }
            }
            //会员减免
            if($active['vipstatus'] == 1){
                $viparray = unserialize($active['viparray']);
            }
            //分销数组
            if(empty($active['isdistri'])){
                $disarray = unserialize($active['disarray']);
            }
        }else{
            $active['lat'] = '39.90960456049752';
            $active['lng'] = '116.3972282409668';
            $active['independent'] = 1;
            $active['isdistri'] = 1;
        }
        if (empty($active['activestarttime']) || empty($active['activeendtime'])) {//初始化时间
            $active['activestarttime'] = time();
            $active['activeendtime'] = strtotime('+1 month');
        }
        if (empty($active['enrollstarttime']) || empty($active['enrollendtime'])) {//初始化时间
            $active['enrollstarttime'] = time();
            $active['enrollendtime'] = strtotime('+1 month');
        }
        if ($_W['ispost']){
            $active = $_GPC['active'];
            if(is_store()){
                $active['sid'] = $_W['storeid'];
            }
            if(empty($active['sid'])){
                wl_message('请选择活动所属商户!');
            }
            if(empty($active['title'])){
                wl_message('请输出活动标题');
            }
            if(empty($active['cateid'])){
                wl_message('请选择活动分类');
            }
            if($active['minpeoplenum'] - $active['maxpeoplenum'] > 0){
                wl_message('报名最大人数不能小于最小人数!');
            }
            //开关
            $active['status'] = $_GPC['status'];
            $active['vipstatus'] = $_GPC['vipstatus'];
            $active['isdistri'] = $_GPC['isdistri'];
            $active['isdistristatus'] = $_GPC['isdistristatus'];
            if(empty($active['addresstype'])){
                $merchantdata = pdo_get('wlmerchant_merchantdata',array('id' => $active['sid']),array('address','lng','lat'));
                $active['address'] = $merchantdata['address'];
                $active['lng'] = $merchantdata['lng'];
                $active['lat'] = $merchantdata['lat'];
            }
            //详情
            $active['detail'] = htmlspecialchars_decode($active['detail']);
            $active['enrolldetail'] = htmlspecialchars_decode($active['enrolldetail']);
            $active['thumbs'] = serialize($active['thumbs']);
            $active['advs'] = serialize($active['advs']);
            $active['pv'] = sprintf("%.0f",$active['pv']);
            $active['sort'] = sprintf("%.0f",$active['sort']);
            $active['enrollnum'] = sprintf("%.0f",$active['enrollnum']);
            //会员减免
            if($active['vipstatus'] == 1){
                $vipleid = $_GPC['vipleid'];
                $vipprice = $_GPC['vipprice'];
                $storeset = $_GPC['storeset'];
                foreach($vipleid as $key => $vle){
                    $vipa['vipprice'] = sprintf("%.2f",$vipprice[$key]);
                    $vipa['storeset'] = sprintf("%.2f",$storeset[$key]);
                    $viparray[$vle] = $vipa;
                }
                $active['viparray'] = serialize($viparray);
            }
            //分销商分佣数组
            if(empty($active['isdistri'])){
                $disleid = $_GPC['disleid'];
                $onedismoney = $_GPC['onedismoney'];
                $twodismoney = $_GPC['twodismoney'];
                foreach($disleid as $dkey => $dle){
                    $dlea['onedismoney'] = sprintf("%.2f",$onedismoney[$dkey]);
                    $dlea['twodismoney'] = sprintf("%.2f",$twodismoney[$dkey]);
                    $disarray[$dle] = $dlea;
                }
                $active['disarray'] = serialize($disarray);
            }
            //时间
            $activetime = $_GPC['activetime'];
            $active['activestarttime'] = strtotime($activetime['start']);
            $active['activeendtime'] = strtotime($activetime['end']);
            $time = $_GPC['time'];
            $active['enrollstarttime'] = strtotime($time['start']);
            $active['enrollendtime'] = strtotime($time['end']);
            if($active['status'] == 1){
                if(is_store()){
                    $examine = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$active['sid']),'audits');
                    if(empty($examine)){
                        $active['status'] = 5;
                    }else{
                        if($active['enrollstarttime'] < time() &&  $active['enrollendtime'] > time()){
                            $active['status'] = 2;
                        }else if($active['enrollendtime'] < time()){
                            $active['status'] = 3;
                        }
                    }
                }else{
                    if($active['enrollstarttime'] < time() &&  $active['enrollendtime'] > time()){
                        $active['status'] = 2;
                    }else if($active['enrollendtime'] < time()){
                        $active['status'] = 3;
                    }
                }
            }
            if($id){
                $res = pdo_update('wlmerchant_activitylist',$active,array('id' => $id));
            }else {
                $active['uniacid'] = $_W['uniacid'];
                $active['aid'] = $_W['aid'];
                $active['createtime'] = time();
                $res = pdo_insert(PDO_NAME.'activitylist',$active);
                $id = pdo_insertid();
            }
            //获取规格
            if(!empty($active['optionstatus'])){
                $specname = $_GPC['specname'];
                $specprice = $_GPC['specprice'];
                $specmax = $_GPC['specmax'];
                $specmin = $_GPC['specmin'];
                $onedismoney = $_GPC['onedismoney'];
                $twodismoney = $_GPC['twodismoney'];
                $settlementmoney = $_GPC['settlementmoney'];
                $specids = $_GPC['specids'];
                if(empty($specname)){
                    wl_message('请添加规格项或关闭多规格设置');
                }
                foreach ($specname as $key => $name){
                    $spec = array(
                        'name'      => $specname[$key],
                        'price'     => $specprice[$key],
                        'minnum'    => $specmin[$key],
                        'maxnum'    => $specmax[$key],
                        'onedismoney'=> $onedismoney[$key],
                        'twodismoney'=> $twodismoney[$key],
                        'settlementmoney' => $settlementmoney[$key]
                    );

                    //会员减免
                    $viparray = [];
                    $vipleidkword = 'vipleid'.$specids[$key];
                    $vippricekword = 'vipprice'.$specids[$key];
                    $storesetkword = 'storeset'.$specids[$key];
                    $vipleid = $_GPC[$vipleidkword];
                    $vipprice = $_GPC[$vippricekword];
                    $storeset = $_GPC[$storesetkword];
                    foreach($vipleid as $vkey => $vle){
                        $vipa['vipprice'] = sprintf("%.2f",$vipprice[$vkey]);
                        $vipa['storeset'] = sprintf("%.2f",$storeset[$vkey]);
                        $viparray[$vle] = $vipa;
                    }
                    $spec['viparray'] = serialize($viparray);
                    //分销佣金
                    $disarray = [];
                    $disleidkword = 'disleid'.$specids[$key];
                    $onedismoneykword = 'onedismoney'.$specids[$key];
                    $twodismoneykword = 'twodismoney'.$specids[$key];
                    $disleid = $_GPC[$disleidkword];
                    $onedismoney = $_GPC[$onedismoneykword];
                    $twodismoney = $_GPC[$twodismoneykword];
                    foreach($disleid as $keyy => $dddle){
                        $dddleaa['onedismoney'] = sprintf("%.2f",$onedismoney[$keyy]);
                        $dddleaa['twodismoney'] = sprintf("%.2f",$twodismoney[$keyy]);
                        $disarray[$dddle] = $dddleaa;
                    }
                    $spec['disarray'] = serialize($disarray);

                    if(empty($specids[$key])){
                        $spec['uniacid'] = $_W['uniacid'];
                        $spec['activityid'] = $id;
                        $res3 = pdo_insert(PDO_NAME . 'activity_spec',$spec);
                        $specid[] = pdo_insertid();
                    }else{
                        $specid[] = $specids[$key];
                        $res3 = pdo_update('wlmerchant_activity_spec',$spec,array('id' => $specids[$key]));
                    }
                    $res2 = $res3 ? : $res2;
                }
                $res4 = pdo_query('delete from ' . tablename('wlmerchant_activity_spec') . ' where activityid = '.$id.' AND id not in ('.implode(',' , $specid).')');
            }
            if($res || $res2 || $res4){
                wl_message('保存成功！',web_url('activity/activity_web/activitylist'),'success');
            }else {
                wl_message('保存失败或无内容修改',referer(),'error');
            }
        }

        include  wl_template('activity/createactivity');
    }

    function delateactivity() {
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $res =  pdo_delete('wlmerchant_activitylist',array('id'=>$id));
        if($res){
            show_json(1,'活动删除成功');
        }else {
            show_json(0,'活动删除成功，请重试');
        }
    }

    function pass(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $flag = $_GPC['flag'];
        if($flag){
            $active = pdo_get('wlmerchant_activitylist',array('id' => $id),array('sid','enrollstarttime','enrollendtime'));
            if($active['enrollstarttime'] < time() && $active['enrollendtime'] > time()){
                $status = 2;
            }else if($active['enrollendtime'] < time()){
                $status = 3;
            }else {
                $status = 1;
            }
        }else{
            $status = 4;
        }
        $res = pdo_update('wlmerchant_activitylist',array('status' => $status),array('id' => $id));
        if($res){
            die(json_encode(array('errno'=>0)));
        }else {
            die(json_encode(array('errno'=>1)));
        }
    }

    function changeacstatus(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'];
        if($status == 0 || $status == 4){
            $active = pdo_get('wlmerchant_activitylist',array('id' => $id),array('sid','enrollstarttime','enrollendtime'));
            if(is_store()){
                $examine = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$active['sid']),'audits');
                if(empty($examine)){
                    $status = 5;
                }else{
                    if($active['enrollstarttime'] < time() && $active['enrollendtime'] > time()){
                        $status = 2;
                    }else if($active['enrollendtime'] < time()){
                        $status = 3;
                    }else {
                        $status = 1;
                    }
                }
            }else{
                if($active['enrollstarttime'] < time() && $active['enrollendtime'] > time()){
                    $status = 2;
                }else if($active['enrollendtime'] < time()){
                    $status = 3;
                }else {
                    $status = 1;
                }
            }
            $res = pdo_update('wlmerchant_activitylist',array('status' => $status),array('id' => $id));
        }else{
            $res = pdo_update('wlmerchant_activitylist',array('status' => 0),array('id' => $id));
        }
        if($res){
            die(json_encode(array('errno'=>0)));
        }else {
            die(json_encode(array('errno'=>1)));
        }
    }

    function export($where){
        global $_W,$_GPC;
        $orders = Util::getNumData("*",'wlmerchant_order',$where,'ID DESC',0,0,1);
        $orders = $orders[0];

        foreach ($orders as $key => &$order){
            $active = pdo_get('wlmerchant_activitylist',array('id' => $order['fkid']),array('title'));
            $member = pdo_get('wlmerchant_member',array('id' => $order['mid']),array('nickname','mobile'));
            $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('storename'));

            $order['gname'] = $active['title'];
            $order['merchantName'] = $merchant['storename'];
            $order['nickname'] = $member['nickname'];
            $order['mobile'] = $member['mobile'];
        }

        $filter = array(
            'orderno' => '订单号',
            'gname' => '活动名称',
            'merchantName' => '所属商家',
            'num' => '报名数量',
            'nickname' => '买家昵称',
            'mobile' => '买家电话',
            'status' => '订单状态',
            'paytype' => '支付方式',
            'createtime' => '下单时间',
            'paytime' => '支付时间',
            'price' => '实付金额',
            'remark' => '备注'
        );

        $data = array();
        foreach ($orders as $k => $v) {
            foreach ($filter as $key => $title) {
                if($key == 'createtime' || $key == 'paytime'){
                    $data[$k][$key] = date('Y-m-d H:i:s',$v[$key]);
                }else if($key == 'status') {
                    switch ($v[$key]) {
                        case '1':
                            $data[$k][$key] = '已支付';
                            break;
                        case '2':
                            $data[$k][$key] = '已核销';
                            break;
                        case '3':
                            $data[$k][$key] = '已完成';
                            break;
                        case '5':
                            $data[$k][$key] = '已取消';
                            break;
                        case '6':
                            $data[$k][$key] = '待退款';
                            break;
                        case '7':
                            $data[$k][$key] = '已退款';
                            break;
                        case '9':
                            $data[$k][$key] = '已过期';
                            break;
                        default:
                            $data[$k][$key] = '未支付';
                            break;
                    }
                }else if($key == 'paytype') {
                    switch ($v[$key]) {
                        case '1':
                            $data[$k][$key] = '余额支付';
                            break;
                        case '2':
                            $data[$k][$key] = '微信支付';
                            break;
                        case '3':
                            $data[$k][$key] = '支付宝';
                            break;
                        case '4':
                            $data[$k][$key] = '货到付款';
                            break;
                        default:
                            $data[$k][$key] = '未知方式';
                            break;
                    }
                }else {
                    $data[$k][$key] = $v[$key];
                }
            }
        }
        util_csv::export_csv_2($data,$filter,'商户活动报名记录.csv');
        exit;
    }

    function hexiaotime(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $record = pdo_get('wlmerchant_activity_record',array('orderid' => $id),array('usetimes','usedtime'));
        $record['usedtime'] = unserialize($record['usedtime']);
        foreach ($record['usedtime'] as $key => &$v) {
            $v['time'] = date('Y-m-d H:i:s',$v['time']);
            switch ($v['type']){
                case '1':
                    $v['typename'] = '输码核销';
                    break;
                case '2':
                    $v['typename'] = '扫码核销';
                    break;
                case '3':
                    $v['typename'] = '后台核销';
                    break;
                case '4':
                    $v['typename'] = '密码核销';
                    break;
                default:
                    $v['typename'] = '未知方式';
                    break;
            }
            if($v['type'] == 1 || $v['type'] == 2){
                $v['vername'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$v['ver']),'nickname');
            }else {
                $v['vername'] = '无';
            }
        }
        die(json_encode(array('errno'=>0,'times'=>$record['usetimes'],'data'=>$record['usedtime'])));
    }

    function cancleHexiao(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $num = pdo_getcolumn(PDO_NAME.'order',array('id'=>$id),'num');
        $res1 = pdo_update('wlmerchant_order',array('status' => 1),array('id' => $id));
        $res2 = pdo_update('wlmerchant_activity_record',array('status' => 1,'usetimes'=>$num),array('orderid' => $id));
        if($res1 && $res2){
            die(json_encode(array('errno'=>0,'message'=>'取消成功','id'=>$id)));
        }else {
            die(json_encode(array('errno'=>2,'message'=>'error','id'=>$id)));
        }
    }

    function confirmHexiao(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $num = pdo_getcolumn(PDO_NAME.'activity_record',array('orderid'=>$id),'usetimes');
        $res = Activity::hexiaoorder($id,0,$num,3);
        if($res){
            die(json_encode(array('errno'=>0,'message'=>'核销成功','id'=>$id)));
        }else {
            die(json_encode(array('errno'=>2,'message'=>'error','id'=>$id)));
        }
    }

    function refundOrder(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $res = Activity::refundorder($id,2);
        if($res['status']){
            die(json_encode(array('errno'=>0,'message'=>$res['message'],'id'=>$id)));
        }else {
            die(json_encode(array('errno'=>2,'message'=>$res['message'],'id'=>$id)));
        }
    }

    function remark(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $remark = $_GPC['remark'];
        $res = pdo_update('wlmerchant_order',array('remark' => $remark),array('id' => $id));
        if($res){
            die(json_encode(array('errno'=>0,'message'=>$res,'id'=>$id)));
        }else {
            die(json_encode(array('errno'=>2,'message'=>$res,'id'=>$id)));
        }
    }

    function changeinfo(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        $newvalue = trim($_GPC['value']);
        if($type == 1){
            $res = pdo_update('wlmerchant_activitylist',array('pv'=>$newvalue),array('id' => $id));
        }elseif ($type == 2) {
            $res = pdo_update('wlmerchant_activitylist',array('sort'=>$newvalue),array('id' => $id));
        }elseif ($type == 3) {
            $res = pdo_update('wlmerchant_activitylist',array('maxpeoplenum'=>$newvalue),array('id' => $id));
        }elseif ($type == 4) {
            $res = pdo_update('wlmerchant_activitylist',array('minpeoplenum'=>$newvalue),array('id' => $id));
        }
        if($res){
            show_json(1,'修改成功');
        }else {
            show_json(0,'修改失败，请重试');
        }
    }

    function categorylist(){
        global $_W, $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $condition = ' and aid=:aid and uniacid=:uniacid ';
        $keyword = trim($_GPC['keyword']);

        if (!empty($keyword)) {
            $condition .= ' and name like \'%' . $keyword . '%\' ';
        }

        $list = pdo_fetchall('select id,logo,status,sort,`name` from ' . tablename('wlmerchant_activity_category') . ' where 1 ' . $condition . ' order by sort desc limit ' . (($pindex - 1) * $psize) . ',' . $psize, array(':aid' => intval($_W['aid']),':uniacid' => $_W['uniacid']));
        $total = pdo_fetchcolumn('SELECT COUNT(*) FROM ' . tablename('wlmerchant_activity_category') . ' where aid=:aid and uniacid=:uniacid ', array(':aid' => intval($_W['aid']), ':uniacid' => $_W['uniacid']));
        $pager = wl_pagination($total, $pindex, $psize);

        include  wl_template('activity/categorylist');
    }

    public function editcategoryname(){
        global $_W,$_GPC;
        $id = intval($_GPC['id']);
        $name = trim($_GPC['value']);
        $res = pdo_update('wlmerchant_activity_category',array('name'=>$name),array('id' => $id));
        if($res){
            show_json(1, '修改成功');
        }else {
            show_json(0, '修改失败,请刷新页面重试！');
        }
    }

    function categoryedit(){
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if($id){
            $category = pdo_get('wlmerchant_activity_category',array('id' => $id));
        }
        if (checksubmit('submit')){
            $category = $_GPC['category'];
            if(empty($category['name'])) wl_message('请填写分类名称');
            if(empty($category['logo'])) wl_message('请上传分类图标');
            $category['status'] = $_GPC['status'];

            if($id){
                $res = pdo_update('wlmerchant_activity_category',$category,array('id' => $id));
            }else{
                $category['uniacid'] = $_W['uniacid'];
                $category['aid'] = $_W['aid'];
                $category['createtime'] = time();
                $res = pdo_insert('wlmerchant_activity_category',$category);
            }
            if($res){
                wl_message('保存成功！',web_url('activity/activity_web/categorylist'),'success');
            }else{
                wl_message('保存失败，请重试');
            }
        }

        include  wl_template('activity/categoryedit');
    }

    function categorydelete(){
        global $_W,$_GPC;
        if ($_W['ispost']) {
            $id = intval($_GPC['id']);
            if (empty($id)) {
                show_json(0, '参数错误，请刷新重试！');
            }else {
                $res = pdo_delete('wlmerchant_activity_category', array('id' => $id,'aid' => intval($_W['aid'])));
            }
            if($res){
                show_json(1);
            }else {
                show_json(0, '删除失败,请刷新页面重试！');
            }
        }
    }

    function changestatus(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $newvalue = trim($_GPC['value']);
        $res = pdo_update('wlmerchant_activity_category',array('status'=>$newvalue),array('id' => $id));
        if($res){
            show_json(1,'修改成功');
        }else {
            show_json(0,'修改失败，请重试');
        }
    }

    function qrcodeimg() {
        global $_W, $_GPC;
        $url = $_GPC['url'];
        m('qrcode/QRcode') -> png($url, false, QR_ECLEVEL_H, 4);
    }

    function open() {
        global $_W, $_GPC;
        $url1 = h5_url('pages/mainPages/index/diypage',['type'=>3]);

        include  wl_template('activity/entry');
    }

    /**
     * Comment: 活动规格页面
     * Author: wlf
     * Date: 2020/10/15 10:30
     */
    public function specpage(){
        global $_W;
        include wl_template('activity/specpage');
    }

    /**
     * Comment: 基础设置
     * Author: wlf
     * Date: 2020/10/21 09:31
     */
    public function baseset(){
        global $_W, $_GPC;
        $settings = Setting::agentsetting_read('activity');
        if (checksubmit('submit')) {
            $data = $_GPC['settings'];
            Setting::agentsetting_save($data, 'activity');
            wl_message('更新设置成功！', web_url('activity/activity_web/baseset'));
        }
        $communitylist = pdo_getall('wlmerchant_community', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), array('id', 'communname'));

        include wl_template('activity/baseset');

    }

}