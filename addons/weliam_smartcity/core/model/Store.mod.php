<?php
defined('IN_IA') or exit('Access Denied');
class Store
{
    ////////////////////////////////////////////////////////商户用户管理//////////////////////////////////////////////////////////
    /**
     * 商户用户信息查询
     */
    static function getAllRegister($page = 0 , $pagenum = 10 , $status = 1)
    {
        global $_W;
        $re['data']  = pdo_fetchall("select * from" . tablename(PDO_NAME . 'merchantuser') . "where uniacid=:uniacid and aid=:aid and status=:status order by createtime desc limit " . $page * $pagenum . "," . $pagenum , [
            ':uniacid' => $_W['uniacid'] ,
            ':aid'     => $_W['aid'] ,
            ':status'  => $status
        ]);
        $re['count'] = pdo_fetchcolumn("select count(*) from" . tablename(PDO_NAME . 'merchantuser') . "where uniacid=:uniacid and aid=:aid and status=:status order by createtime desc limit " . $page * $pagenum . "," . $pagenum , [
            ':uniacid' => $_W['uniacid'] ,
            ':aid'     => $_W['aid'] ,
            ':status'  => $status
        ]);
        return $re;
    }
    /**
     * 根据mid获取单个商户用户信息
     */
    static function getSingleRegister($mid)
    {
        global $_W;
        if (empty($mid)) return '';
        if (is_array($mid)) {
            return pdo_get(PDO_NAME . 'merchantuser' , $mid);
        }
        $res = pdo_get(PDO_NAME . 'merchantuser' , ['mid' => $mid , 'uniacid' => $_W['uniacid'] , 'status' => 2]);
        if (empty($res)) {
            $res2 = pdo_get(PDO_NAME . 'merchantuser' , ['mid' => $mid , 'uniacid' => $_W['uniacid']]);
            return $res2;
        }
        else {
            return $res;
        }
    }
    /**
     * 保存商户用户信息
     */
    static function saveSingleRegister($arr , $mid = '')
    {
        global $_W;
        if (!empty($mid)) return pdo_update(PDO_NAME . 'merchantuser' , $arr , [
            'uniacid' => $_W['uniacid'] ,
            'aid'     => $_W['aid'] ,
            'mid'     => $mid
        ]);
        $arr['mid'] = $_W['mid'];
        //    $user = self::getSingleRegister($arr['mid']);
        //    if (!empty($user)) return false;
        return pdo_insert(PDO_NAME . 'merchantuser' , $arr);
    }
    /**
     * 修改商户用户
     */
    static function editSingleRegister($id , $arr)
    {
        global $_W;
        if (empty($id)) return false;
        return pdo_update(PDO_NAME . 'merchantuser' , $arr , ['id' => $id , 'uniacid' => $_W['uniacid']]);
    }
    ////////////////////////////////////////////////////////商户分组//////////////////////////////////////////////////////////
    /**
     * 获取单个商户分组信息
     */
    static function getSingleGroup($id)
    {
        global $_W;
        if (empty($id)) return false;
        return pdo_get(PDO_NAME . 'chargelist' , ['id' => $id , 'uniacid' => $_W['uniacid']]);
    }
    ////////////////////////////////////////////////////////商户分类//////////////////////////////////////////////////////////
    /**
     * 单个商户分类查询
     */
    static function getSingleCategory($id)
    {
        global $_W;
        if (empty($id)) return false;
        return pdo_get(PDO_NAME . 'category_store' , ['id' => $id , 'uniacid' => $_W['uniacid']]);
    }
    /**
     * 查询所有商户分类
     */
    static function getAllCategory($page = 0 , $pagenum = 10 , $parentid = 0)
    {
        global $_W;
        $re['data']  = pdo_fetchall("select * from" . tablename(PDO_NAME . 'category_store') . "where uniacid=:uniacid and aid=:aid and parentid=:parentid order by displayorder desc limit " . $page * $pagenum . "," . $pagenum , [
            ':uniacid'  => $_W['uniacid'] ,
            ':aid'      => $_W['aid'] ,
            ':parentid' => $parentid
        ]);
        $re['count'] = pdo_fetchcolumn("select count(*) from" . tablename(PDO_NAME . 'category_store') . "where uniacid=:uniacid and aid=:aid and parentid=:parentid" , [
            ':uniacid'  => $_W['uniacid'] ,
            ':aid'      => $_W['aid'] ,
            ':parentid' => $parentid
        ]);
        return $re;
    }
    /**
     * 编辑商户分类
     */
    static function categoryEdit($arr , $id = '')
    {
        global $_W;
        if (empty($arr)) return false;
        if (!empty($id) && $id != '') return pdo_update(PDO_NAME . 'category_store' , $arr , [
            'id'      => $id ,
            'uniacid' => $_W['uniacid'] ,
            'aid'     => $_W['aid']
        ]);
        $arr['aid']     = $_W['aid'];
        $arr['uniacid'] = $_W['uniacid'];
        return pdo_insert(PDO_NAME . 'category_store' , $arr);
    }
    /**
     * 递归删除商户分类
     */
    static function categoryDelete($id)
    {
        global $_W;
        if (empty($id)) return false;
        $arr = pdo_getall(PDO_NAME . 'category_store' , ['uniacid' => $_W['uniacid'] , 'parentid' => $id]);
        if (empty($arr)) return pdo_delete(PDO_NAME . 'category_store' , ['uniacid' => $_W['uniacid'] , 'id' => $id]);
        foreach ($arr as $key => $value) {
            if (!self::categoryDelete($value['id'])) return false;
        }
        return pdo_delete(PDO_NAME . 'category_store' , ['uniacid' => $_W['uniacid'] , 'id' => $id]);
    }

    ////////////////////////////////////////////////////////添加商户//////////////////////////////////////////////////////////
    /*
     * 添加商户
     */
    static function registerEdit($arr)
    {
        global $_W;
        if (empty($arr)) return false;
        $arr['uniacid'] = $_W['uniacid'];
        return pdo_insert(PDO_NAME . 'merchantuser' , $arr);
    }
    /*
     * 组别选择
     */
    static function registerCheck()
    {
        global $_W;
        return pdo_fetchall("select * from" . tablename(PDO_NAME . 'chargelist'));
    }
    /*
     * 微信ID选择
     */
    static function registerNickname($arr)
    {
        global $_W;
        $con = $arr;
        return pdo_fetchall("select * from" . tablename(PDO_NAME . 'member') . "where $con");
    }

    ////////////////////////////////////////////////////////用户入住//////////////////////////////////////////////////////////
    /*
     * 查询所有用户信息
     */
    static function getAllUser($page = 0 , $pagenum = 10 , $enabled = 0)
    {
        global $_W;
        $re['data'] = pdo_fetchall("select * from" . tablename(PDO_NAME . 'merchantuser') . "where uniacid=:uniacid and status=:status and enabled=:enabled order by createtime desc limit " . $page * $pagenum . "," . $pagenum , [
            ':uniacid' => $_W['uniacid'] ,
            ':status'  => 2 ,
            ':enabled' => $enabled
        ]);
        foreach ($re['data'] as $key => $value) {
            if (strtotime($re['data'][$key]['endtime']) < time()) {
                $re['data'][$key]['enabled'] = 3;
                pdo_update(PDO_NAME . 'merchantuser' , $re['data'][$key] , [
                    'id'      => $re['data'][$key]['id'] ,
                    'uniacid' => $_W['uniacid']
                ]);
            }
        }
        $re['count'] = pdo_fetchcolumn("select count(*) from" . tablename(PDO_NAME . 'merchantuser') . "where uniacid=:uniacid and status=:status and enabled=:enabled order by createtime desc limit " . $page * $pagenum . "," . $pagenum , [
            ':uniacid' => $_W['uniacid'] ,
            ':status'  => 2 ,
            ':enabled' => $enabled
        ]);
        return $re;
    }
    /*
     * 添加商户user表
     */
    static function registerEditUser($arr , $id = '')
    {
        global $_W;
        if (empty($arr)) return false;
        if (!empty($id) && $id != '') {
            pdo_update(PDO_NAME . 'merchantuser' , $arr , ['id' => $id , 'uniacid' => $_W['uniacid']]);
            return $id;
        }
        else {
            $arr['uniacid'] = $_W['uniacid'];
            $arr['aid']     = $_W['aid'];
            pdo_insert(PDO_NAME . 'merchantuser' , $arr);
            $uid = pdo_insertid();
            return $uid;
        }
    }
    /*
     * 添加商户data表
     */
    static function registerEditData($arr , $id = '')
    {
        global $_W;
        if (empty($arr)) return false;
        if (!empty($id) && $id != '') {
            pdo_update(PDO_NAME . 'merchantdata' , $arr , ['id' => $id , 'uniacid' => $_W['uniacid']]);
            return $id;
        }
        else {
            $arr['uniacid'] = $_W['uniacid'];
            if (empty($arr['aid'])) {
                $arr['aid'] = $_W['aid'];
            }
            pdo_insert(PDO_NAME . 'merchantdata' , $arr);
            $uid = pdo_insertid();
            return $uid;
        }
    }
    /*
     * 通过ID删除用户及其商户
     */
    static function deleteUser($id)
    {
        global $_W;
        if (empty($id)) return false;
        $arr = pdo_get(PDO_NAME . 'merchantuser' , ['uniacid' => $_W['uniacid'] , 'id' => $id]);
        if ($arr['storeid'] != 0) {
            pdo_delete(PDO_NAME . 'merchantdata' , ['id' => $arr['storeid'] , 'uniacid' => $_W['uniacid']]);
        }
        return pdo_delete(PDO_NAME . 'merchantuser' , ['id' => $id , 'uniacid' => $_W['uniacid']]);
    }
    /*
     * 查询单个商户
     */
    static function getSingleStore($id)
    {
        global $_W;
        if (empty($id)) return '';
        return pdo_get(PDO_NAME . 'merchantdata' , ['id' => $id , 'uniacid' => $_W['uniacid']]);
    }
    /*
     * 通过MID删除用户及其商户
     */
    static function deleteStoreByMid($userid)
    {
        global $_W;
        if (empty($userid)) return false;
        $arr = pdo_get(PDO_NAME . 'merchantuser' , ['uniacid' => $_W['uniacid'] , 'id' => $userid]);
        if (!empty($arr['storeid'])) {
            pdo_delete(PDO_NAME . 'merchantdata' , ['id' => $arr['storeid'] , 'uniacid' => $_W['uniacid']]);
        }
        return pdo_delete(PDO_NAME . 'merchantuser' , ['id' => $userid , 'uniacid' => $_W['uniacid']]);
    }
    static function getstores($locations , $lng , $lat , $nearid)
    {
        global $_W;
        foreach ($locations as $key => $val) {
            $loca                        = unserialize($val['location']);
            $storehours                  = unserialize($val['storehours']);
            $locations[$key]['distance'] = self::getdistance($loca['lng'] , $loca['lat'] , $lng , $lat);
            if (empty($locations[$key]['distance'])) {
                $locations[$key]['distance'] = 99999999;
            }
            $locations[$key]['logo']       = tomedia($val['logo']);
            $locations[$key]['url']        = h5_url('pages/mainPages/store/index' , ['sid' => $val['id']]);
            //营业时间
            $storehours        = unserialize($locations[$key]['storehours']);
            if(!empty($storehours['startTime'])){
                $locations[$key]['storehours'] = $storehours['startTime'] . ' - ' . $storehours['endTime'].' 营业';
            }else{
                $locations[$key]['storehours'] = '';
                foreach($storehours as $hk => $hour){
                    if($hk > 0){
                        $locations[$key]['storehours'] .= ','.$hour['startTime'] . ' - ' . $hour['endTime'];
                    }else{
                        $locations[$key]['storehours'] .= $hour['startTime'] . ' - ' . $hour['endTime'];
                    }
                }
                $locations[$key]['storehours'] .= '营业';
            }
        }
        //排序
        if ($nearid == 2) {
            $sort_key   = 'distance';
            $sort_order = SORT_ASC;
        }
        else if ($nearid == 1) {
            $sort_key   = 'createtime';
            $sort_order = SORT_DESC;
        }
        else if ($nearid == 4) {
            $sort_key   = 'pv';
            $sort_order = SORT_DESC;
        }
        else {
            $sort_key   = 'listorder';
            $sort_order = SORT_DESC;
        }
        //等于5则不进行排序
        if ($nearid != 5) {
            $locations = self::wl_sort($locations , $sort_key , $sort_order , SORT_NUMERIC);
        }
        foreach ($locations as $key => $value) {
            if (!empty($value['distance'])) {
                if ($value['distance'] > 9999998) {
                    $locations[$key]['distance'] = " ";
                }
                else if ($value['distance'] > 1000) {
                    $locations[$key]['distance'] = (floor(($value['distance'] / 1000) * 10) / 10) . "km";
                }
                else {
                    $locations[$key]['distance'] = round($value['distance']) . "m";
                }
            }
        }
        return $locations;
    }
    static function getdistance($lng1 , $lat1 , $lng2 , $lat2 , $sortglag = false)
    {
        if (empty($lng1) || empty($lat1) || empty($lng2) || empty($lat2)) {
            return '9999999';
        }
        //将角度转为狐度
        $radLat1 = @deg2rad($lat1);
        //deg2rad()函数将角度转换为弧度
        $radLat2 = @deg2rad($lat2);
        $radLng1 = @deg2rad($lng1);
        $radLng2 = @deg2rad($lng2);
        $a       = $radLat1 - $radLat2;
        $b       = $radLng1 - $radLng2;
        $s       = 2 * asin(sqrt(pow(sin($a / 2) , 2) + cos($radLat1) * cos($radLat2) * pow(sin($b / 2) , 2))) * 6378.137 * 1000;
        if ($sortglag) {
            if ($s > 1000) {
                $s = (floor(($s / 1000) * 10) / 10) . "km";
            }
            else {
                $s = round($s) . "m";
            }
        }
        return $s;
    }
    static function wl_sort($arrays , $sort_key , $sort_order = SORT_ASC , $sort_type = SORT_NUMERIC)
    {
        if (is_array($arrays)) {
            foreach ($arrays as $array) {
                if (is_array($array)) {
                    $key_arrays[] = $array[$sort_key];
                }
                else {
                    return false;
                }
            }
        }
        else {
            return false;
        }
        array_multisort($key_arrays , $sort_order , $sort_type , $arrays);
        return $arrays;
    }
    static function settlement($orderid , $goodsid , $merchantid , $price , $orderno , $settlementmoney , $type , $disorderid = 0 , $sharemoney = 0 , $aid = 0 , $salesarray = '' , $allocationtype = 0)
    {
        global $_W;
        if (empty($aid)) {
            $aid = $_W['aid'];
        }
        if ($orderno) {  //判断是否已结算
            $flag = pdo_get(PDO_NAME . 'autosettlement_record' , ['orderno' => $orderno] , ['id']);
        }
        else {
            $flag = pdo_get(PDO_NAME . 'autosettlement_record' , [
                'type'       => 7 ,
                'orderid'    => $orderid ,
                'orderprice' => $price
            ] , ['id']);
        }
        if ($flag) {
            return 1;
        }
        MysqlFunction::setTrans(4);
        MysqlFunction::startTrans();
        //分销
        $distrimoney = 0;
        if ($disorderid) {
            $disorder   = pdo_get('wlmerchant_disorder' , ['id' => $disorderid] , ['leadmoney','onegroupmoney','twogroupmoney','shareholdermoney']);
            $leadmoneys = unserialize($disorder['leadmoney']);
            foreach ($leadmoneys as $key => $money) {
                $distrimoney += $money;
            }
            $distrimoney = $distrimoney + $disorder['onegroupmoney'] + $disorder['twogroupmoney'] + $disorder['shareholdermoney'];
        }
        //业务员
        $salesmoney = 0;
        if (!empty($salesarray)) {
            switch ($type) {
                case '1':
                    $plugin    = 'rush';
                    $saleprice = pdo_getcolumn(PDO_NAME . 'rush_order' , ['id' => $orderid] , 'price');
                    break;
                case '2':
                    $plugin    = 'fightgroup';
                    $saleprice = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $orderid] , 'goodsprice');
                    break;
                case '3':
                    $plugin    = 'coupon';
                    $saleprice = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $orderid] , 'goodsprice');
                    break;
                case '10':
                    $plugin    = 'groupon';
                    $saleprice = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $orderid] , 'goodsprice');
                    break;
                case '12':
                    $plugin    = 'bargain';
                    $saleprice = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $orderid] , 'goodsprice');
                    break;
                case '11':
                    $plugin    = 'payonline';
                    $saleprice = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $orderid] , 'price');
                    break;
                case '14':
                    $plugin    = 'citydelivery';
                    $saleprice = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $orderid] , 'goodsprice');
                    break;
            }
            $salesarray = unserialize($salesarray);
            foreach ($salesarray as &$sale) {
                $sale['reportmoney'] = sprintf("%.2f" , $saleprice * $sale['scale'] / 100);
                $reportmoney         = $sale['reportmoney'];
                if ($reportmoney > 0 && empty($allocationtype)) {
                    pdo_fetch("update" . tablename('wlmerchant_distributor') . "SET dismoney=dismoney+{$reportmoney},nowmoney=nowmoney+{$reportmoney} WHERE mid = {$sale['mid']}");
                    $onenowmoney = pdo_getcolumn(PDO_NAME . 'distributor' , ['mid' => $sale['mid']] , 'nowmoney');
                    Distribution::adddisdetail($orderid , $sale['mid'] , $merchantid , 1 , $reportmoney , $plugin , 0 , '业务员佣金结算' , $onenowmoney , 0 , 1 , $aid);
                }
                $salesmoney += $reportmoney;
            }
        }
        //平台商品
        $marketstatus = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$merchantid),'marketstatus');
        if($marketstatus > 0){
            $agentmoney = sprintf("%.2f",$price - $settlementmoney);
            $settlementmoney = sprintf("%.2f",$settlementmoney - $distrimoney - $sharemoney - $salesmoney);
        }else{
            $agentmoney = sprintf("%.2f",$price - $settlementmoney - $distrimoney - $sharemoney - $salesmoney);
        }
        if($type == '14'){
            $orderinfo = pdo_get(PDO_NAME.'order',array('id'=>$orderid),['fightstatus','expressprcie']);
            if($orderinfo['fightstatus'] == 4){
                $agentmoney = sprintf("%.2f",$agentmoney - $orderinfo['expressprcie']);
            }
        }else if($type == '1'){
            $threeinfo = pdo_get('wlmerchant_rush_order',array('id' => $goodsid),array('pftid','threestatus'));
            if($threeinfo['pftid'] > 0){
                $pftset = Setting::agentsetting_read('pftapi',$aid);
                if(empty($pftset['pftswitch']) && empty($threeinfo['threestatus'])){
                    $agentmoney = 0;
                }
                if(empty($pftset['yqdswitch']) && $threeinfo['threestatus'] == 1){
                    $agentmoney = 0;
                }
            }
        }else if($type == '10' ){
            $threeinfo = pdo_get('wlmerchant_groupon_order',array('id' => $goodsid),array('pftid','threestatus'));
            if($threeinfo['pftid'] > 0){
                $pftset = Setting::agentsetting_read('pftapi',$aid);
                if(empty($pftset['pftswitch']) && empty($threeinfo['threestatus'])){
                    $agentmoney = 0;
                }
                if(empty($pftset['yqdswitch']) && $threeinfo['threestatus'] == 1){
                    $agentmoney = 0;
                }
            }
        }
        $uniacid = $_W['uniacid'];
        if(empty($uniacid)){
            if($aid > 0){
                $uniacid = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$aid),'uniacid');
            }else{
                if($type == 1){
                    $uniacid = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$orderid),'uniacid');
                }else if($type == 4){
                    $uniacid = pdo_getcolumn(PDO_NAME.'halfcard_record',array('id'=>$orderid),'uniacid');
                }else if($type == 7){
                    $uniacid = pdo_getcolumn(PDO_NAME.'settlement_record',array('id'=>$orderid),'uniacid');
                }else{
                    $uniacid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'uniacid');
                }
            }
        }


        $data = [
            'uniacid'       => $uniacid,
            'aid'           => $aid ,
            'type'          => $type ,
            'merchantid'    => $merchantid ,
            'orderid'       => $orderid ,
            'orderno'       => $orderno ,
            'goodsid'       => $goodsid ,
            'orderprice'    => $price ,
            'agentmoney'    => $agentmoney,
            'merchantmoney' => $settlementmoney ,
            'distrimoney'   => $distrimoney ,
            'sharemoney'    => $sharemoney ,
            'salesmoney'    => $salesmoney ,
            'createtime'    => time()
        ];
        if ($type == 8) {
            $settings = Setting::wlsetting_read('distribution');
            if ($settings['seetstatus']) {
                $data['agentmoney'] = 0;
            }
        }
        $res          = pdo_insert(PDO_NAME . 'autosettlement_record' , $data);
        $settlementid = pdo_insertid();
        if ($res) {
            if (empty($allocationtype)) {
                if ($type == 14) {
                    $type = 140;  //防止和大礼包类型参数冲突
                }
                if ($type == 15) {
                    $type = 150;  //防止和支付返现记录冲突
                }
                if (abs($settlementmoney) > 0) {
                    if ($type == 7) {
                        pdo_fetch("update" . tablename('wlmerchant_merchantdata') . "SET nowmoney=nowmoney+{$settlementmoney} WHERE id = {$merchantid}");
                    }
                    else {
                        pdo_fetch("update" . tablename('wlmerchant_merchantdata') . "SET allmoney=allmoney+{$settlementmoney},nowmoney=nowmoney+{$settlementmoney} WHERE id = {$merchantid}");
                    }
                    $change['merchantnowmoney'] = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $data['merchantid']] , 'nowmoney');
                    self::addcurrent(1 , $type , $merchantid , $settlementmoney , $change['merchantnowmoney'] , $orderid , '' , $_W['uniacid'] , $aid);
                }
                if (abs($data['agentmoney']) > 0) {
                    if ($type == 7) {
                        pdo_fetch("update" . tablename('wlmerchant_agentusers') . "SET nowmoney=nowmoney+{$data['agentmoney']} WHERE id = {$data['aid']}");
                    }
                    else {
                        pdo_fetch("update" . tablename('wlmerchant_agentusers') . "SET allmoney=allmoney+{$data['agentmoney']},nowmoney=nowmoney+{$data['agentmoney']} WHERE id = {$data['aid']}");
                    }
                    $change['agentnowmoney'] = pdo_getcolumn(PDO_NAME . 'agentusers' , ['id' => $data['aid']] , 'nowmoney');
                    self::addcurrent(2 , $type , $data['aid'] , $data['agentmoney'] , $change['agentnowmoney'] , $orderid , '' , $_W['uniacid'] , $aid);
                }
                pdo_update('wlmerchant_autosettlement_record' , $change , ['id' => $settlementid]);
                MysqlFunction::commit();
                return true;
            }
            else {
                MysqlFunction::commit();
                //分账
                if ($allocationtype == 1) {
                    $source = pdo_getcolumn(PDO_NAME . 'paylogvfour' , ['tid' => $orderno] , 'source');
                    $task = array(
                        'source'  => $source,
                        'type'    => $type,
                        'orderid' => $orderid,
                        'salesarray' => serialize($salesarray),
                        'salesmoney' => $salesmoney,
                        'settlementid' => $settlementid,
                        'disorderid' => $disorderid
                    );
                    $task = serialize($task);
                    Queue::addTask(7, $task, time(), $orderid);
                }
                return true;
            }
        }
        MysqlFunction::rollback();
        return false;
    }
    //写入明细表
    static function addcurrent($status , $type , $objid , $fee , $nowmoney , $orderid , $remark = '' , $uniacid = '' , $aid = '')
    {
        global $_W;
        $data = [
            'uniacid'    => $uniacid ? $uniacid : $_W['uniacid'] ,
            'status'     => $status ,
            'type'       => $type ,
            'objid'      => $objid ,
            'fee'        => $fee ,
            'nowmoney'   => $nowmoney ,
            'orderid'    => $orderid ,
            'remark'     => $remark ,
            'createtime' => time() ,
            'aid'        => $aid ? $aid : $_W['aid']
        ];
        pdo_insert(PDO_NAME . 'current' , $data);
    }
    //保存运费模板
    static function saveExpress($data)
    {
        global $_W;
        if (!is_array($data)) return false;
        $data['uniacid'] = $_W['uniacid'];
        pdo_insert(PDO_NAME . 'express_template' , $data);
        return pdo_insertid();
    }
    //更新运费模板
    static function updateExpress($data , $id)
    {
        global $_W;
        if (!is_array($data)) return false;
        $res = pdo_update('wlmerchant_express_template' , $data , ['id' => $id]);
        return $res;
    }
    //获取运费模板列表
    static function getNumExpress($select , $where , $order , $pindex , $psize , $ifpage)
    {
        $goodsInfo = Util::getNumData($select , PDO_NAME . 'express_template' , $where , $order , $pindex , $psize , $ifpage);
        return $goodsInfo;
    }
    //删除运费模板
    static function deteleExpress($id)
    {
        $res = pdo_delete('wlmerchant_express_template' , ['id' => $id]);
        return $res;
    }
    //计算在线买单的结算金额
    static function gethalfsettlementmoney($money , $sid , $vipbuyflag)
    {
        global $_W;
        $merchant = pdo_get('wlmerchant_merchantdata' , ['id' => $sid] , ['groupid' , 'settlementtext']);
        $sett     = unserialize($merchant['settlementtext']);
        if ($sett['paysett'] > 0 && empty($vipbuyflag)) {  //按商户默认的结算
            $settlementmoney = sprintf("%.2f" , $money * $sett['paysett'] / 100);
        }
        else if ($sett['payvip'] > 0 && $vipbuyflag) {
            $settlementmoney = sprintf("%.2f" , $money * $sett['payvip'] / 100);
        }
        else {  //按商户分组默认结算
            $grouprete       = pdo_getcolumn(PDO_NAME . 'chargelist' , ['id' => $merchant['groupid']] , 'defaultrate');
            $settlementmoney = sprintf("%.2f" , $money * $grouprete / 100);
        }
        return $settlementmoney;
    }
    //计算结算金额
    static function getsettlementmoney($type , $goodsid , $num , $sid , $vipbuyflag = 0 , $optionid = 0 , $fightflah = 0 , $useprice = 0,$uhlevel = 0)
    {
        global $_W;
        if ($type == 1) { //抢购
            $goods = pdo_get(PDO_NAME . 'rush_activity' , ['id' => $goodsid] , [
                'price' ,
                'settlementmoney' ,
                'viparray' ,
                'independent'
            ]);
            $viparray = unserialize($goods['viparray']);
            $storeset = $viparray[$uhlevel]['storeset'];
            $vipdiscount = $viparray[$uhlevel]['vipprice'];
            $goods['vipsettlementmoney'] = sprintf("%.2f",$goods['settlementmoney'] - $storeset);
            $goods['vipprice'] = $goods['price'] - $vipdiscount;

        }
        else if ($type == 2) { //拼团
            $goods       = pdo_get(PDO_NAME . 'fightgroup_goods' , ['id' => $goodsid] , [
                'price' ,
                'vipdiscount' ,
                'aloneprice' ,
                'viparray' ,
                'settlementmoney' ,
                'independent'
            ]);
            $viparray = unserialize($goods['viparray']);
            $storeset = $viparray[$uhlevel]['storeset'];
            $goods['vipsettlementmoney'] = sprintf("%.2f",$goods['settlementmoney'] - $storeset);
            $vipdiscount = $viparray[$uhlevel]['vipprice'];
            if ($fightflah) {
                $goods['price']    = $goods['aloneprice'];
                $goods['vipprice'] = $goods['aloneprice'] - $vipdiscount;
            }
            else {
                $goods['vipprice'] = $goods['price'] - $vipdiscount;
            }
        }
        else if ($type == 3) {  //团购
            $goods = pdo_get(PDO_NAME . 'groupon_activity' , ['id' => $goodsid] , [
                'price' ,
                'settlementmoney' ,
                'viparray' ,
                'independent'
            ]);
            $viparray = unserialize($goods['viparray']);
            $vipdiscount = $viparray[$uhlevel]['vipprice'];
            $storeset = $viparray[$uhlevel]['storeset'];
            $goods['vipsettlementmoney'] = sprintf("%.2f",$goods['settlementmoney'] - $storeset);
            $goods['vipprice'] = $goods['price'] - $vipdiscount;
        }
        else if ($type == 4) {  //卡券
            $goods = pdo_get(PDO_NAME . 'couponlist' , ['id' => $goodsid] , [
                'price' ,
                'settlementmoney' ,
                'viparray' ,
                'independent'
            ]);
            $viparray = unserialize($goods['viparray']);
            $vipdiscount = $viparray[$uhlevel]['vipprice'];
            $storeset = $viparray[$uhlevel]['storeset'];
            $goods['vipsettlementmoney'] = sprintf("%.2f",$goods['settlementmoney'] - $storeset);
            $goods['vipprice'] = $goods['price'] - $vipdiscount;
        }
        else if ($type == 5) {  //砍价
            $goods                       = pdo_get(PDO_NAME . 'bargain_userlist' , ['id' => $goodsid] , [
                'price' ,
                'activityid'
            ]);
            $activity                    = pdo_get(PDO_NAME . 'bargain_activity' , ['id' => $goods['activityid']] , [
                'settlementmoney' ,
                'independent' ,
                'viparray'
            ]);
            $goods['settlementmoney']    = $activity['settlementmoney'];
            $goods['independent']        = $activity['independent'];
            $goods['viparray']           = $activity['viparray'];
            $viparray = unserialize($goods['viparray']);
            $vipdiscount = $viparray[$uhlevel]['vipprice'];
            $storeset = $viparray[$uhlevel]['storeset'];
            $goods['vipsettlementmoney'] = sprintf("%.2f",$goods['settlementmoney'] - $storeset);
            $goods['vipprice'] = $goods['price'] - $vipdiscount;
        }
        else if ($type == 6) {  //活动
            $goods             = pdo_get(PDO_NAME . 'activitylist' , ['id' => $goodsid] , [
                'price' ,
                'settlementmoney' ,
                'viparray' ,
                'independent'
            ]);
            $viparray = unserialize($goods['viparray']);
            $vipdiscount = $viparray[$uhlevel]['vipprice'];
            $storeset = $viparray[$uhlevel]['storeset'];
            $goods['vipsettlementmoney'] = sprintf("%.2f",$goods['settlementmoney'] - $storeset);
            $goods['vipprice'] = $goods['price'] - $vipdiscount;
        }

        //多规格
        if ($optionid > 0) {
            if ($type == 6) {
                $option            = pdo_get(PDO_NAME . 'activity_spec' , ['id' => $optionid] , [
                    'price' ,
                    'settlementmoney',
                    'viparray'
                ]);
                $viparray = unserialize($option['viparray']);
                $goods['price']    = $option['price'];
                $vipdiscount = $viparray[$uhlevel]['vipprice'];
                $storeset = $viparray[$uhlevel]['storeset'];
                $goods['vipsettlementmoney'] = sprintf("%.2f",$goods['settlementmoney'] - $storeset);
                $goods['vipprice'] = $goods['price'] - $vipdiscount;
            }
            else {
                $option = pdo_get(PDO_NAME . 'goods_option' , ['id' => $optionid] , [
                    'price' ,
                    'viparray',
                    'settlementmoney'
                ]);
                $viparray = unserialize($option['viparray']);
                $vipdiscount = $viparray[$uhlevel]['vipprice'] > 0 ? $viparray[$uhlevel]['vipprice'] : $vipdiscount;
                $storeset = $viparray[$uhlevel]['storeset'] > 0 ? $viparray[$uhlevel]['storeset'] : $storeset;
                if ($type == 2) {
                    if ($fightflah) {
                        $goods['price']    = $option['vipprice'];
                        $goods['vipprice'] = sprintf("%.2f" , $option['vipprice'] - $vipdiscount);
                    }
                    else {
                        $goods['price']    = $option['price'];
                        $goods['vipprice'] = sprintf("%.2f" , $option['price'] - $vipdiscount);
                    }
                }
                else {
                    $goods['vipprice'] = sprintf("%.2f" , $option['price'] - $vipdiscount);;
                    $goods['price']    = $option['price'];
                }
            }
            $goods['settlementmoney']    = $option['settlementmoney'];
            $goods['vipsettlementmoney'] = sprintf("%.2f" , $option['settlementmoney'] - $storeset);
        }
        //会员购买
        if ($vipbuyflag) {
            $settlementmoney = $goods['vipsettlementmoney'];
        }
        else {
            $settlementmoney = $goods['settlementmoney'];
        }
        if (empty($goods['independent'])) {  //结算金额
            $settlementmoney = $settlementmoney * $num;   //有结算金额
        }else {
            if ($useprice > 0) {
                $goods['price'] = $goods['vipprice'] = $useprice;
            }
            $merchant = pdo_get('wlmerchant_merchantdata' , ['id' => $sid] , ['groupid' , 'settlementtext']);
            $sett     = unserialize($merchant['settlementtext']);
            if ($type == 1) {
                $settlementrate    = $sett['rushsett'];
                $vipsettlementrate = $sett['rushvip'];
            }
            else if ($type == 2) {
                $settlementrate    = $sett['fightsett'];
                $vipsettlementrate = $sett['fightvip'];
            }
            else if ($type == 3) {
                $settlementrate    = $sett['grouponsett'];
                $vipsettlementrate = $sett['grouponvip'];
            }
            else if ($type == 4) {
                $settlementrate    = $sett['couponsett'];
                $vipsettlementrate = $sett['couponvip'];
            }
            else if ($type == 5) {
                $settlementrate    = $sett['bargainsett'];
                $vipsettlementrate = $sett['bargainvip'];
            }
            else if ($type == 6) {
                $settlementrate    = $sett['activitysett'];
                $vipsettlementrate = $sett['activityvip'];
            }

            if ($settlementrate > 0 && empty($vipbuyflag)) {  //按商户默认的结算
                $settlementmoney = sprintf("%.2f" , $goods['price'] * $settlementrate / 100 * $num);
            }
            else if ($vipsettlementrate > 0 && $vipbuyflag) {
                $settlementmoney = sprintf("%.2f" , $goods['vipprice'] * $vipsettlementrate / 100 * $num);
            }
            else {  //按商户分组默认结算
                $grouprete = pdo_getcolumn(PDO_NAME . 'chargelist' , ['id' => $merchant['groupid']] , 'defaultrate');
                if (empty($vipbuyflag)) {
                    $settlementmoney = sprintf("%.2f" , $goods['price'] * $grouprete / 100 * $num);
                }
                else {
                    $settlementmoney = sprintf("%.2f" , $goods['vipprice'] * $grouprete / 100 * $num);
                }
            }
        }
        if (empty($settlementmoney)) {
            $settlementmoney = 0;
        }
        return $settlementmoney;
    }
    //结算抢购订单
    static function rushsettlement($id)
    {
        global $_W;
        $rush = pdo_get('wlmerchant_rush_order' , ['id' => $id]);
        if (empty($rush['issettlement'])) {
            if ($rush['shareid']) {
                $sharemoney = pdo_getcolumn(PDO_NAME . 'sharecurrent' , ['shareid' => $rush['shareid']] , 'price');
            }
            if (empty($sharemoney)) {
                $sharemoney = 0;
            }
            $res = self::settlement($rush['id'] , $rush['activityid'] , $rush['sid'] , $rush['actualprice'] , $rush['orderno'] , $rush['settlementmoney'] , 1 , $rush['disorderid'] , $sharemoney , $rush['aid'] , $rush['salesarray'] , $rush['allocationtype']);
            if ($res) {
                pdo_update('wlmerchant_rush_order' , [
                    'issettlement' => 1 ,
                    'settletime'   => time()
                ] , ['id' => $rush['id']]);
            }
            if ($rush['paytype'] != 1 && p('payback')) {
                Payback::payCore($rush['sid'] , $rush['mid'] , -1 , 'rush' , $rush['actualprice'] , $rush['orderno'] , $rush['orderid'] , $rush['uniacid'] , $rush['aid'] , 0);
            }
        }
        else {
            $res = 1;
        }
        return $res;
    }
    //结算一卡通开通订单
    static function halfsettlement($id)
    {
        global $_W;
        $order = pdo_get('wlmerchant_halfcard_record' , ['id' => $id]);
        if (empty($order['issettlement'])) {
            $res = self::settlement($order['id'] , $order['typeid'] , 0 , $order['price'] , $order['orderno'] , 0 , 4 , $order['disorderid'] , 0 , $order['aid'] , '' , $order['allocationtype']);
            if ($res) {
                pdo_update('wlmerchant_halfcard_record' , ['issettlement' => 1] , ['id' => $order['id']]);
            }
        }
        else {
            $res = 1;
        }
        return $res;
    }
    //结算其他订单
    static function ordersettlement($id)
    {
        global $_W;
        $order = pdo_get('wlmerchant_order' , ['id' => $id]);
        if (empty($order['issettlement'])) {
            if ($order['plugin'] == 'wlfightgroup') {  //拼团
                $type = 2;
            }
            else if ($order['plugin'] == 'coupon') {  //卡券
                $type = 3;
            }
            else if ($order['plugin'] == 'pocket') {  //掌上信息
                $type = 5;
            }
            else if ($order['plugin'] == 'store') {  //商户入驻
                $type = 6;
            }
            else if ($order['plugin'] == 'distribution') {  //分销申请
                $type = 8;
            }
            else if ($order['plugin'] == 'activity') {  //商户活动
                $type = 9;
            }
            else if ($order['plugin'] == 'groupon') {  //团购
                $type = 10;
            }
            else if ($order['plugin'] == 'halfcard') {  //在线买单
                $type = 11;
            }
            else if ($order['plugin'] == 'bargain') {  //砍价
                $type = 12;
            }
            else if ($order['plugin'] == 'citycard') {  //同城名片
                $type = 13;
            }
            else if ($order['plugin'] == 'citydelivery') {  //同城配送
                $type = 14;
            }
            else if ($order['plugin'] == 'yellowpage') {  //黄页114
                $type = 15;
            }
            else if ($order['plugin'] == 'recruit') {  //招聘
                $type = 16;
            }
            else if ($order['plugin'] == 'housekeep') {  //家政
                $type = 17;
            }else if ($order['plugin'] == 'house') {  //房产
                $type = 18;
            }
            if ($order['shareid']) {
                $sharemoney = pdo_getcolumn(PDO_NAME . 'sharecurrent' , ['shareid' => $order['shareid']] , 'price');
            }
            if (empty($sharemoney)) {
                $sharemoney = 0;
            }
            $res = self::settlement($order['id'] , $order['fkid'] , $order['sid'] , $order['price'] , $order['orderno'] , $order['settlementmoney'] , $type , $order['disorderid'] , $sharemoney , $order['aid'] , $order['salesarray'] , $order['allocationtype']);
            if ($res) {
                pdo_update('wlmerchant_order' , [
                    'issettlement' => 1 ,
                    'settletime'   => time()
                ] , ['id' => $order['id']]);
            }
            if ($order['paytype'] != 1 && p('payback')) {
                Payback::payCore($order['sid'] , $order['mid'] , -1 , $order['plugin'] , $order['price'] , $order['orderno'] , $order['orderid'] , $order['uniacid'] , $order['aid'] , 0);
            }
        }
        else {
            $res = 1;
        }
        return $res;
    }
    static function doTask()
    {
        global $_W;
        pdo_delete(PDO_NAME . "order" , [
            'createtime <' => strtotime(date('Ymd')) ,
            'plugin'       => 'consumption' ,
            'status'       => 0 ,
            'uniacid'      => $_W['uniacid']
        ]);
        $sets = pdo_fetchall('select distinct aid from ' . tablename(PDO_NAME . 'oparea') . "where uniacid = {$_W['uniacid']} and status = 1");
        foreach ($sets as $set) {
            $_W['aid'] = $set['aid'];
            if (empty($_W['aid']) || $_W['aid'] == -1) {
                continue;
            }
            $cashset  = Setting::agentsetting_read('cashset');
            $cashsets = Setting::wlsetting_read('cashset');
            //自动结算已完成的抢购订单
            $rushorder = pdo_fetchall("SELECT id,sid,actualprice,activityid,orderno,disorderid,num,vipbuyflag,optionid,shareid,settlementmoney,allocationtype FROM " . tablename('wlmerchant_rush_order') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status IN (2,3) AND issettlement = 0 ORDER BY id ASC limit 10");
            if ($rushorder) {
                foreach ($rushorder as $key => $rush) {
                    if ($rush['shareid']) {
                        $sharemoney = pdo_getcolumn(PDO_NAME . 'sharecurrent' , ['shareid' => $rush['shareid']] , 'price');
                    }
                    if (empty($sharemoney)) {
                        $sharemoney = 0;
                    }
                    $res = self::settlement($rush['id'] , $rush['activityid'] , $rush['sid'] , $rush['actualprice'] , $rush['orderno'] , $rush['settlementmoney'] , 1 , $rush['disorderid'] , $sharemoney , $_W['aid'] , '' , $rush['allocationtype']);
                    if ($res) {
                        pdo_update('wlmerchant_rush_order' , ['issettlement' => 1] , ['id' => $rush['id']]);
                    }
                }
            }
            //自动结算其他订单
            $orders = pdo_fetchall("SELECT id,sid,price,fkid,orderno,plugin,num,disorderid,vipbuyflag,specid,expressid,shareid,settlementmoney,allocationtype FROM " . tablename('wlmerchant_order') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status IN (2,3) AND issettlement = 0 AND orderno != 666666 ORDER BY id ASC limit 20");
            if ($orders) {
                foreach ($orders as $key => $order) {
                    if ($order['plugin'] == 'store') {
                        $type = 6;
                    }
                    else if ($order['plugin'] == 'distribution') {
                        $type = 8;
                    }
                    else if ($order['plugin'] == 'pocket') {
                        $type = 5;
                    }
                    else if ($order['plugin'] == 'wlfightgroup') {
                        $type = 2;
                    }
                    else if ($order['plugin'] == 'groupon') {
                        $type = 10;
                    }
                    else if ($order['plugin'] == 'coupon') {
                        $type = 3;
                    }
                    else if ($order['plugin'] == 'activity') {
                        $type = 9;
                    }
                    else if ($order['plugin'] == 'halfcard') {
                        $type = 11;
                    }
                    else if ($order['plugin'] == 'bargain') {
                        $type = 12;
                    }
                    if ($order['shareid']) {
                        $sharemoney = pdo_getcolumn(PDO_NAME . 'sharecurrent' , ['shareid' => $order['shareid']] , 'price');
                    }
                    if (empty($sharemoney)) {
                        $sharemoney = 0;
                    }
                    $res = self::settlement($order['id'] , $order['fkid'] , $order['sid'] , $order['price'] , $order['orderno'] , $order['settlementmoney'] , $type , $order['disorderid'] , $sharemoney , $_W['aid'] , '' , $order['allocationtype']);
                    if ($res) {
                        pdo_update('wlmerchant_order' , ['issettlement' => 1] , ['id' => $order['id']]);
                    }
                }
            }
            //结算一卡通开通订单
            $halfcardorders = pdo_fetchall("SELECT id,typeid,price,orderno,disorderid,allocationtype FROM " . tablename('wlmerchant_halfcard_record') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 1 AND issettlement = 0 ORDER BY id ASC limit 10");
            if ($halfcardorders) {
                foreach ($halfcardorders as $key => $half) {
                    $settlementmoney = 0;
                    $type            = 4;
                    $res             = self::settlement($half['id'] , $half['typeid'] , 0 , $half['price'] , $half['orderno'] , $settlementmoney , $type , $half['disorderid'] , 0 , $_W['aid'] , '' , $half['allocationtype']);
                    if ($res) {
                        pdo_update('wlmerchant_halfcard_record' , ['issettlement' => 1] , ['id' => $half['id']]);
                    }
                }
            }
            //自动通过平台审核
            if ($cashsets['noaudit']) {
                $allrecords = pdo_getall(PDO_NAME . 'settlement_record' , [
                    'status'  => 2 ,
                    'uniacid' => $_W['uniacid']
                ] , ['id']);
                foreach ($allrecords as $key => $allrec) {
                    $trade_no = time() . random(4 , true);
                    pdo_update(PDO_NAME . 'settlement_record' , [
                        'status'     => 3 ,
                        'updatetime' => time() ,
                        'trade_no'   => $trade_no
                    ] , ['id' => $allrec['id']]);
                }
                //分销
                $allrecords2 = pdo_getall(PDO_NAME . 'settlement_record' , [
                    'status'  => 7 ,
                    'uniacid' => $_W['uniacid']
                ] , ['id']);
                if ($allrecords2) {
                    foreach ($allrecords2 as $key => $allrec2) {
                        $trade_no = time() . random(4 , true);
                        pdo_update(PDO_NAME . 'settlement_record' , [
                            'status'     => 3 ,
                            'updatetime' => time() ,
                            'trade_no'   => $trade_no
                        ] , ['id' => $allrec2['id']]);
                    }
                }
            }
            //自动打款
            if ($cashsets['autocash'] > 0) {
                $cashrecords = pdo_getall(PDO_NAME . 'settlement_record' , [
                    'status'       => 3 ,
                    'type'         => 1 ,
                    'uniacid'      => $_W['uniacid'] ,
                    'payment_type' => 2
                ]);
                foreach ($cashrecords as $key => $cash) {
                    if ($cash['sopenid']) {
                        $autocash = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $cash['sid']] , 'autocash');
                        if ($autocash) {
                            $realname = pdo_getcolumn(PDO_NAME . 'member' , ['openid' => $cash['sopenid']] , 'realname');
                            $result1  = wlPay::finance($cash['sopenid'] , $cash['sgetmoney'] , '结算给商家' , $realname , $cash['trade_no']);
                            //结算操作
                            if ($result1['return_code'] == 'SUCCESS' && $result1['result_code'] == 'SUCCESS') {
                                pdo_update(PDO_NAME . 'settlement_record' , [
                                    'status'     => 5 ,
                                    'updatetime' => TIMESTAMP ,
                                    'settletype' => 2
                                ] , ['id' => $cash['id']]);
                            }
                        }
                    }
                }
            }
            if ($cashsets['agentautocash'] > 0) {
                $cashrecords = pdo_getall(PDO_NAME . 'settlement_record' , [
                    'status'       => 3 ,
                    'type'         => 2 ,
                    'uniacid'      => $_W['uniacid'] ,
                    'payment_type' => 2
                ]);
                foreach ($cashrecords as $key => $cash) {
                    if ($cash['sopenid']) {
                        $realname = pdo_getcolumn(PDO_NAME . 'member' , ['openid' => $cash['sopenid']] , 'realname');
                        $result2  = wlPay::finance($cash['sopenid'] , $cash['sgetmoney'] , '结算给代理' , $realname , $cash['trade_no']);
                        //结算操作
                        if ($result2['return_code'] == 'SUCCESS' && $result2['result_code'] == 'SUCCESS') {
                            pdo_update(PDO_NAME . 'settlement_record' , [
                                'status'     => 4 ,
                                'updatetime' => TIMESTAMP ,
                                'settletype' => 2
                            ] , ['id' => $cash['id']]);
                        }
                    }
                }
            }
            if ($cashsets['disautocash'] > 0) {
                $cashrecords = pdo_getall(PDO_NAME . 'settlement_record' , [
                    'status'       => 3 ,
                    'type'         => 3 ,
                    'uniacid'      => $_W['uniacid'] ,
                    'payment_type' => 2
                ]);
                if ($cashrecords) {
                    foreach ($cashrecords as $key => $cash) {
                        if ($cash['sopenid']) {
                            $realname = pdo_getcolumn(PDO_NAME . 'member' , ['openid' => $cash['sopenid']] , 'realname');
                            $result2  = wlPay::finance($cash['sopenid'] , $cash['sgetmoney'] , '结算给分销商' , $realname , $cash['trade_no']);
                            //结算操作
                            if ($result2['return_code'] == 'SUCCESS' && $result2['result_code'] == 'SUCCESS') {
                                pdo_update(PDO_NAME . 'settlement_record' , [
                                    'status'     => 9 ,
                                    'updatetime' => TIMESTAMP ,
                                    'settletype' => 2
                                ] , ['id' => $cash['id']]);
                            }
                        }
                    }
                }
                $cashrecords = pdo_getall(PDO_NAME . 'settlement_record' , [
                    'status'       => 3 ,
                    'type'         => 3 ,
                    'uniacid'      => $_W['uniacid'] ,
                    'payment_type' => 4
                ]);
                if ($cashrecords) {
                    foreach ($cashrecords as $key => $cash) {
                        if ($cash['mid']) {
                            $result = Member::credit_update_credit2($cash['mid'] , $cash['sgetmoney'] , '分销商余额提现');
                            //结算操作
                            if ($result) {
                                pdo_update(PDO_NAME . 'settlement_record' , [
                                    'status'     => 9 ,
                                    'updatetime' => TIMESTAMP ,
                                    'settletype' => 4
                                ] , ['id' => $cash['id']]);
                            }
                        }
                    }
                }
            }
            //删除错误结算记录
            $errorcu = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_current') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 AND type IN (4,5,6,8) ORDER BY id DESC");
            if ($errorcu) {
                foreach ($errorcu as $key => &$cu) {
                    pdo_delete('wlmerchant_current' , ['id' => $cu['id']]);
                }
            }
            //结算金额0的补丁
            $nosettorder = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_rush_order') . "WHERE uniacid = {$_W['uniacid']} AND settlementmoney = 0 AND issettlement = 0 ORDER BY id DESC limit 10");
            if ($nosettorder) {
                foreach ($nosettorder as $key => $noset) {
                    $settlementmoney = self::getsettlementmoney(1 , $noset['activityid'] , $noset['num'] , $noset['sid'] , $noset['vipbuyflag'] , $noset['optionid']);
                    if ($settlementmoney > 0) {
                        pdo_update('wlmerchant_rush_order' , ['settlementmoney' => $settlementmoney] , ['id' => $noset['id']]);
                    }
                }
            }
            //过期商户
            $nowtime       = time();
            $overmerchants = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_merchantdata') . "WHERE uniacid = {$_W['uniacid']} AND endtime < {$nowtime} AND enabled = 1 ORDER BY id DESC");
            if ($overmerchants) {
                foreach ($overmerchants as $key => $over) {
                    $res = pdo_update(PDO_NAME . 'merchantdata' , ['enabled' => 3] , [
                        'uniacid' => $_W['uniacid'] ,
                        'id'      => $over['id']
                    ]);
                    if ($res) {  //下架商品
                        //抢购商品
                        pdo_update('wlmerchant_rush_activity' , ['status' => 4] , [
                            'uniacid' => $_W['uniacid'] ,
                            'sid'     => $over['id']
                        ]);
                        //拼团商品
                        pdo_update('wlmerchant_fightgroup_goods' , ['status' => 0] , [
                            'uniacid'    => $_W['uniacid'] ,
                            'merchantid' => $over['id']
                        ]);
                        //卡券
                        pdo_update('wlmerchant_couponlist' , ['status' => 0] , [
                            'uniacid'    => $_W['uniacid'] ,
                            'merchantid' => $over['id']
                        ]);
                        //特权
                        pdo_update('wlmerchant_halfcardlist' , ['status' => 0] , [
                            'uniacid'    => $_W['uniacid'] ,
                            'merchantid' => $over['id']
                        ]);
                        //礼包
                        pdo_update('wlmerchant_package' , ['status' => 0] , [
                            'uniacid'    => $_W['uniacid'] ,
                            'merchantid' => $over['id']
                        ]);
                    }
                }
            }
            //自动通过退款申请
            $settings = Setting::wlsetting_read('orderset');
            if ($settings['autoapplyre'] > 0) {
                pdo_update('wlmerchant_order' , ['status' => 6 , 'applyrefund' => 2] , [
                    'status'      => 1 ,
                    'applyrefund' => 1 ,
                    'uniacid'     => $_W['uniacid']
                ]);
                pdo_update('wlmerchant_order' , ['status' => 6 , 'applyrefund' => 2] , [
                    'status'      => 8 ,
                    'applyrefund' => 1 ,
                    'uniacid'     => $_W['uniacid']
                ]);
                pdo_update('wlmerchant_rush_order' , ['status' => 6 , 'applyrefund' => 2] , [
                    'status'      => 1 ,
                    'applyrefund' => 1 ,
                    'uniacid'     => $_W['uniacid']
                ]);
                pdo_update('wlmerchant_rush_order' , ['status' => 6 , 'applyrefund' => 2] , [
                    'status'      => 8 ,
                    'applyrefund' => 1 ,
                    'uniacid'     => $_W['uniacid']
                ]);
            }
            //过期订单自动退款
            if ($settings['reovertime'] > 0) {
                $overorders = pdo_fetchall("SELECT id,recordid,plugin,fkid FROM " . tablename('wlmerchant_order') . "WHERE uniacid = {$_W['uniacid']} AND status = 6 AND failtimes < 3 ORDER BY id DESC");
                if ($overorders) {
                    foreach ($overorders as $key => $over) {
                        if ($over['plugin'] == 'wlfightgroup') {
                            $usedtime   = pdo_getcolumn(PDO_NAME . 'fightgroup_userecord' , ['id' => $over['recordid']] , 'usedtime');
                            $overrefund = pdo_getcolumn(PDO_NAME . 'fightgroup_goods' , ['id' => $over['fkid']] , 'overrefund');
                            if (empty($usedtime) && $overrefund) {
                                Wlfightgroup::refund($over['id']);
                            }
                        }
                        else if ($over['plugin'] == 'groupon') {
                            $usedtime   = pdo_getcolumn(PDO_NAME . 'groupon_userecord' , ['id' => $over['recordid']] , 'usedtime');
                            $overrefund = pdo_getcolumn(PDO_NAME . 'groupon_activity' , ['id' => $over['fkid']] , 'overrefund');
                            if (empty($usedtime) && $overrefund) {
                                Groupon::refund($over['id']);
                            }
                        }
                        else if ($over['plugin'] == 'coupon') {
                            $usedtime   = pdo_getcolumn(PDO_NAME . 'member_coupons' , ['id' => $over['recordid']] , 'usedtime');
                            $overrefund = pdo_getcolumn(PDO_NAME . 'couponlist' , ['id' => $over['fkid']] , 'overrefund');
                            if (empty($usedtime) && $overrefund) {
                                wlCoupon::refund($over['id']);
                            }
                        }
                    }
                }
            }
            //自动收货
            if ($settings['receipt']) {
                $receipttime = time() - $settings['receipt'] * 24 * 3600;
                //order表
                $receiptorders = pdo_getall('wlmerchant_order' , ['status' => 4 , 'uniacid' => $_W['uniacid']] , [
                    'id' ,
                    'expressid' ,
                    'disorderid'
                ]);
                foreach ($receiptorders as $key => $order) {
                    if ($order['expressid']) {
                        $express = pdo_get('wlmerchant_express' , ['id' => $order['expressid']] , ['sendtime']);
                        if ($express['sendtime'] < $receipttime) {
                            pdo_update('wlmerchant_order' , ['status' => 2] , ['id' => $order['id']]);
                            pdo_update('wlmerchant_express' , ['receivetime' => time()] , ['id' => $order['expressid']]);
                            if ($order['disorderid']) {
                                pdo_update('wlmerchant_disorder' , ['status' => 1] , [
                                    'status' => 0 ,
                                    'id'     => $order['disorderid']
                                ]);
                            }
                        }
                    }
                }
                //抢购表
                $receiptrushorders = pdo_getall('wlmerchant_rush_order' , [
                    'status'  => 4 ,
                    'uniacid' => $_W['uniacid']
                ] , ['id' , 'expressid' , 'disorderid']);
                foreach ($receiptrushorders as $key => $rushorder) {
                    if ($rushorder['expressid']) {
                        $express = pdo_get('wlmerchant_express' , ['id' => $rushorder['expressid']] , ['sendtime']);
                        if ($express['sendtime'] < $receipttime) {
                            pdo_update('wlmerchant_rush_order' , ['status' => 2] , ['id' => $rushorder['id']]);
                            pdo_update('wlmerchant_express' , ['receivetime' => time()] , ['id' => $rushorder['expressid']]);
                            if ($rushorder['disorderid']) {
                                pdo_update('wlmerchant_disorder' , ['status' => 1] , [
                                    'status' => 0 ,
                                    'id'     => $rushorder['disorderid']
                                ]);
                            }
                        }
                    }
                }
                //积分兑换
                $receiptconsumptions = pdo_getall('wlmerchant_consumption_record' , [
                    'status'  => 2 ,
                    'uniacid' => $_W['uniacid']
                ] , ['id' , 'orderid' , 'expressid']);
                if ($receiptconsumptions) {
                    foreach ($receiptconsumptions as $key => $consum) {
                        if ($consum['expressid']) {
                            $express = pdo_get('wlmerchant_express' , ['id' => $consum['expressid']] , ['sendtime']);
                            if ($express['sendtime'] < $receipttime) {
                                pdo_update('wlmerchant_consumption_record' , ['status' => 3] , ['id' => $consum['id']]);
                                pdo_update('wlmerchant_express' , ['receivetime' => time()] , ['id' => $consum['expressid']]);
                                $consum['disorderid'] = pdo_getcolumn(PDO_NAME . 'order' , ['id' => $consum['orderid']] , 'disorderid');
                                if ($consum['disorderid']) {
                                    pdo_update('wlmerchant_disorder' , ['status' => 1] , [
                                        'status' => 0 ,
                                        'id'     => $consum['disorderid']
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
            //修改核销次数为0的验证记录
            pdo_update('wlmerchant_verifrecord' , ['num' => 1] , ['num' => 0]);
        }
    }
    //店铺客户
    static function addFans($sid = '' , $mid = '' , $source = 1)
    {
        global $_W;
        $mid = $mid ? $mid : $_W['mid'];
        if (empty($sid) || empty($mid)) {
            return false;
        }
        $fansst = pdo_getcolumn(PDO_NAME . 'storefans' , [
            'uniacid' => $_W['uniacid'] ,
            'sid'     => $sid ,
            'mid'     => $mid
        ] , 'id');
        if (empty($fansst)) {
            $res   = pdo_insert(PDO_NAME . 'storefans' , [
                'uniacid'    => $_W['uniacid'] ,
                'sid'        => $sid ,
                'mid'        => $mid ,
                'createtime' => time() ,
                'source'     => $source
            ]);
            if(Customized::init('pocket1500')){
                $set = Setting::agentsetting_read('pocket');
                Pocket::giveCredit($sid,4,$mid,$set);
            }
            $admin = pdo_get(PDO_NAME . 'merchantuser' , [
                'uniacid' => $_W['uniacid'] ,
                'storeid' => $sid ,
                'ismain'  => 1
            ]);
            if (!empty($admin)) {
                $nickname    = pdo_getcolumn(PDO_NAME . "member" , ['id' => $mid] , 'nickname');
                $storename   = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $sid] , 'storename');
                $meaasgedata = [
                    'first'   => '您好，' . $nickname . '刚刚关注了您的店铺，成为了您的客户！' ,
                    'type'    => '客户关注' ,//业务类型
                    'content' => '店铺名称:[' . $storename . ']' ,//业务内容
                    'status'  => '关注成功' ,//处理结果
                    'time'    => date('Y-m-d H:i:s' , time()) ,//操作时间
                    'remark'  => '祝您财源广进~~'
                ];
                TempModel::sendInit('service' , $admin['mid'] , $meaasgedata , $_W['source'] , '');
            }
            News::addSysNotice($_W['uniacid'] , 4 , $sid , 0 , $mid);
        }
        return $res;
    }
    //发消息给管理员
    static function toadmin($goodsname , $storeid , $pluginname)
    {
        global $_W;
        $storename = pdo_getcolumn(PDO_NAME . 'merchantdata' , ['id' => $storeid] , 'storename');
        if ($pluginname == 'rush') {
            $first = '一个抢购商品待审核';
        }
        else if ($pluginname == 'fightgroup') {
            $first = '一个拼团商品待审核';
        }
        else if ($pluginname == 'coupon') {
            $first = '一个超级券待审核';
        }
        else if ($pluginname == 'halfcard') {
            $first = '一个特权折扣待审核';
        }
        else if ($pluginname == 'package') {
            $first = '一个大礼包待审核';
        }
        else if ($pluginname == 'bargain') {
            $first = '一个砍价商品待审核';
        }
        $openids = pdo_getall('wlmerchant_agentadmin' , [
            'uniacid' => $_W['uniacid'] ,
            'aid'     => $_W['aid'] ,
            'notice'  => 1
        ] , ['mid']);
        if ($openids) {
            foreach ($openids as $key => $member) {
                if ($member['mid'] > 0) {
                    $meaasgedata = [
                        'first'   => $first ,
                        'type'    => '产品:' . $goodsname ,//业务类型
                        'content' => '店铺名称:[' . $storename . ']' ,//业务内容
                        'status'  => '待审核' ,//处理结果
                        'time'    => date('Y-m-d H:i:s' , time()) ,//操作时间
                        'remark'  => '请尽快前往后台审核~~'
                    ];
                    TempModel::sendInit('service' , $member['mid'] , $meaasgedata , $_W['source'] , '');
                }
            }
        }
    }
    /**
     * Comment: 获取当前店铺店长的信息
     * Author: zzw
     * @param $sid   店铺id
     * @return bool  店长信息
     */
    static public function getShopOwnerInfo($sid , $aid)
    {
        global $_W;
        $aid           = $aid ? $aid : $_W['aid'];
        $shopownerInfo = pdo_fetch("SELECT * FROM " . tablename(PDO_NAME . "merchantdata") . " a LEFT JOIN  " . tablename(PDO_NAME . "merchantuser") . " b  ON a.id = b.storeid LEFT JOIN " . tablename(PDO_NAME . "member") . " m ON b.mid = m.id WHERE a.id = {$sid} AND b.uniacid = {$_W['uniacid']} AND b.aid = {$aid} AND b.ismain = 1");
        return $shopownerInfo;
    }
    /**
     * Comment: 获取一定时间段内的店铺销量
     * Author: zzw
     * @param $id     店铺id
     * @param $month  月时间，不能与天时间同时存在
     * @param $day    天时间，不能与月时间同时存在
     * @return bool   返回时间内的总销量
     */
    static public function getShopSales($id , $month , $day)
    {
        //获取条件
        $where = " WHERE status IN (1,2,3,4) AND sid = {$id} ";
        if ($month) $where .= " AND paytime > ".strtotime('-'.$month.' month');
        else if ($day) $where .= " AND paytime > ".strtotime('-'.$day.' day');
        //获取实际销量
        $rushSum = pdo_fetchcolumn("SELECT sum(num) FROM " . tablename(PDO_NAME . "rush_order") . $where);//查询抢购订单表的销量
        $orderSum = pdo_fetchcolumn("SELECT sum(num) FROM " . tablename(PDO_NAME . "order") . $where." AND plugin IN ('groupon','wlfightgroup','coupon','bargain','citydelivery','housekeep')");//查询综合订单表的销量
        //获取虚拟销量
        $virtualSales = pdo_getcolumn(PDO_NAME."merchantdata",['id'=>$id],'virtual_sales');
        $virtualSales = $virtualSales ? : 0;

        //获取总的销量
        return $rushSum + $orderSum + $virtualSales;
    }
    /**
     * Comment: 获取店铺定位并且计算距离
     * Author: zzw
     * @param int   店铺id
     * @param float   经度
     * @param float   纬度
     * @param array $location
     * @return float|int|string 返回当前位置到店铺的距离
     */
    static public function shopLocation($sid , $lng , $lat , $location = [])
    {
        if ($sid > 0) $location = unserialize(pdo_getcolumn(PDO_NAME . "merchantdata" , ['id' => $sid] , 'location'));
        $distance = self::getdistance($location['lng'] , $location['lat'] , $lng , $lat);
        if (empty($distance)) {
            $distance = 99999999;
        }
        if (!empty($distance)) {
            if ($distance > 9999998) {
                $distance = "";
            }
            else if ($distance > 1000) {
                $distance = (floor(($distance / 1000) * 10) / 10) . "km";
            }
            else {
                $distance = round($distance) . "m";
            }
        }
        return $distance;
    }
    /**
     * Comment: 判断商户是否为营业中
     * Date: 2019/8/29 16:57
     * @param $storehours
     * @return int
     */
    public static function getShopBusinessStatus($storehours,$sid)
    {
        global $_W;
        $temclose = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$sid),'temclose');
        if($temclose > 0 ){
            return 0;
        }else{
            $date                  = date('Y-m-d');
            if(!empty($storehours['startTime'])){
                $startdate             = $date . " " . $storehours['startTime'];
                $startime              = strtotime($startdate);
                $enddate               = $date . " " . $storehours['endTime'];
                $endtime               = strtotime($enddate);
                if ($endtime < $startime) {
                    if (time() > $startime || time() < $endtime) {
                        return 1;
                    }
                    else {
                        return 0;
                    }
                }else if ($endtime == $startime) {
                    return 1;
                }else {
                    if (time() > $startime && time() < $endtime) {
                        return 1;
                    }
                    else {
                        return 0;
                    }
                }
            }else{
                $flag = 0;
                foreach($storehours as $hour){
                    if(empty($flag)){
                        $startdate             = $date . " " . $hour['startTime'];
                        $startime              = strtotime($startdate);
                        $enddate               = $date . " " . $hour['endTime'];
                        $endtime               = strtotime($enddate);
                        if ($endtime < $startime) {
                            if (time() > $startime || time() < $endtime) {
                                $flag = 1;
                            }
                        }else if ($endtime == $startime) {
                            $flag = 1;
                        }else {
                            if (time() > $startime && time() < $endtime) {
                                $flag = 1;
                            }
                        }
                    }
                }
                return $flag;
            }
        }
    }
    /**
     * Comment: 根据用户id获取商户列表
     * Author: zzw
     * Date: 2019/8/30 16:17
     * @param $mid
     * @return array|bool|mixed
     */
    public static function getUserShopList($mid , $lng , $lat , $getActive = true)
    {
        #1、获取列表信息
        $list = pdo_fetchall("SELECT b.id,b.storename,b.logo,b.address,b.location,b.storehours,b.pv,b.score,b.tag FROM " . tablename(PDO_NAME . "merchantuser") . " as a RIGHT JOIN " . tablename(PDO_NAME . "merchantdata") . " as b ON a.storeid = b.id WHERE a.mid = {$mid} AND a.enabled = 1 AND b.enabled = 1 GROUP BY a.storeid ORDER BY a.createtime ASC ");
        #2、循环处理商户信息
        foreach ($list as $key => &$val) {
            //图片处理
            $val['logo'] = tomedia($val['logo']);
            //店铺标签
            $val['tags'] = [];
            $tagids      = unserialize($val['tag']);
            if (!empty($tagids)) {
                $tags        = pdo_getall('wlmerchant_tags' , ['id' => $tagids] , ['title']);
                $val['tags'] = $tags ? array_column($tags , 'title') : [];
            }
            unset($val['tag']);
            //定位处理
            $val['distance'] = self::shopLocation(0 , $lng , $lat , unserialize($val['location']));
            //营业时间处理
            $storehours            = unserialize($val['storehours']);
            if(!empty($storehours['startTime'])){
                $val['storehours'] = $storehours['startTime'] . ' - ' . $storehours['endTime']. "  营业";
            }else{
                $val['storehours'] = '';
                foreach($storehours as $hk => $hour){
                    if($hk > 0){
                        $val['storehours'] .= ','.$hour['startTime'] . ' - ' . $hour['endTime'];
                    }else{
                        $val['storehours'] .= $hour['startTime'] . ' - ' . $hour['endTime'];
                    }
                }
                $val['storehours'] .= "  营业";
            }
            $val['score']      = intval($val['score']);
            //查询认证和保证金
            if (p('attestation')) {
                $val['attestation'] = Attestation::checkAttestation(2 , $val['id']);
            }
            else {
                $val['attestation'] = 0;
            }
        }
        #2、获取店铺商品活动信息
        if ($getActive) {
            $list = WeliamWeChat::getStoreList($list);
        }
        return $list;
    }
    /**
     * Comment: 生成商户专属二维码
     * Author: zzw
     * Date: 2019/11/27 14:01
     * @param int    $id    商户id
     * @param string $logo  商户logo
     * @param string $link  链接
     * @param bool   $state 是否将商户logo绘制到二维码上
     * @return array|bool|string
     */
    public static function getShopWxAppQrCode($id , $logo , $link = 'pages/mainPages/store/index' , $state = false)
    {
        global $_W;
        #1、基本信息生成
        if ($id > 0) $url = h5_url($link , ['sid' => $id]);
        else $url = h5_url($link);
        $path = explode('#/' , $url)[1];
        //商户小程序码名称（带logo）
        $finalPath     = 'addons/' . MODULE_NAME . '/data/qrcode/' . $_W['uniacid'] . '/' . date("Y-m-d" , time()) . '/' . 'shop_wxapp_' . md5($id . $path . $logo) . '.png';
        $finalSavePath = IA_ROOT . '/' . $finalPath;
        if (!file_exists($finalSavePath)) {
            //商户临时小程序码名称（无logo）
            $temporaryName = md5('temporary_shop_wxapp_' . $id . $path . $logo);
            #2、生成小程序二维码
            $TqrCode = WeApp::getQrCode($path , $temporaryName . '.png');
            if ($TqrCode['errno'] == 0 && is_array($TqrCode)) {
                return $TqrCode;
            }
            #2、判断是否将商户logo绘制到二维码上
            if ($state) {
                return tomedia($TqrCode);
            }
            #3、根据是否超过生成二维码进行对应的操作
            $TPath = IA_ROOT . '/' . $TqrCode;//完整绝对路径(无logo)
            if (file_exists($TPath)) {
                //将商户logo图片 绘制进小程序码中  形成商户专属小程序码  并且储存商户专属小程序码
                $target = imagecreatetruecolor(430 , 430);
                imagecopy($target , Tools::createImage(tomedia($TqrCode)) , 0 , 0 , 0 , 0 , 430 , 430);
                $logobg = imagecreatetruecolor(200 , 200);
                $img    = Tools::createImage($logo);
                $w      = imagesx($img);
                $h      = imagesy($img);
                $needw  = ($w >= $h) ? $h : $w;
                imagecopyresized($logobg , $img , 0 , 0 , 0 , 0 , 200 , 200 , $needw , $needw);
                imagecopyresized($target , Tools::imageRadius($logobg , true) , 115 , 115 , 0 , 0 , 200 , 200 , 200 , 200);
                imagepng($target , $finalSavePath);
                imagedestroy($target);
                //删除临时小程序码
                unlink($TPath);
            }
        }
        return !empty($finalPath) ? tomedia($finalPath) : '';
    }
    /**
     * Comment: 校验商户权限
     * Author: wlf
     * Date: 2019/10/24 09:41
     */
    public function checkAuthority($operation , $storeid)
    {
        $groupid = pdo_getcolumn('wlmerchant_merchantdata' , ['id' => $storeid] , 'groupid');
        if (empty($groupid)) {
            $authority = 0;
        }
        else {
            $authority = pdo_getcolumn('wlmerchant_chargelist' , ['id' => $groupid] , 'authority');
        }
        $authority = unserialize($authority);
        if (!empty($authority)) {
            if (!in_array($operation , $authority)) {
                return 1;
            }
        }
        return 0;
    }
    /**
     * Comment: 商户信息列表导出
     * Author: zzw
     * Date: 2020/11/9 17:26
     * @param $where
     */
    public static function exportShop($where){
        //列表信息获取
        $field = ['id','storename','mobile','address','realname','tel','enabled','groupid','createtime','allmoney','nowmoney'];
        $list = pdo_getall(PDO_NAME."merchantdata",$where,$field,'','listorder desc,id desc');
        $newList = [];
        //循环处理信息
        foreach($list as $key => &$value){
            //处理分类信息
            $cateList = pdo_fetchall("SELECT name FROM ".tablename(PDO_NAME."merchant_cate")
                ." as a  LEFT JOIN ".tablename(PDO_NAME."category_store")
                ." as b ON a.twolevel = b.id WHERE a.sid = {$value['id']}");
            $value['cate'] = implode('/',array_column($cateList,'name'));
            //状态信息处理
            switch ($value['enabled']){
                case 0: $value['enabled'] = '待入驻';break;
                case 1: $value['enabled'] = '入驻中';break;
                case 2: $value['enabled'] = '暂停中';break;
                case 3: $value['enabled'] = '已到期';break;
                case 4: $value['enabled'] = '回收站';break;
            }
            //组信息获取
            $value['group_name'] = pdo_getcolumn(PDO_NAME . 'chargelist' , ['id' => $value['groupid']] , 'name');
            //入驻时间
            $value['createtime'] = date("Y-m-d H:i:s",$value['createtime']);
            //删除多余的信息
            unset($value['id'],$value['groupid']);
            //由于排序问题 需要重新生成新的数组  否则顺序不能进行自定义
            $newList[] = [
                'storename'  => $value['storename'] ,
                'mobile'     => $value['mobile'] ,
                'address'    => $value['address'] ,
                'cate'       => $value['cate'] ,
                'createtime' => $value['createtime'] ,
                'enabled'    => $value['enabled'] ,
                'group_name' => $value['group_name'] ,
                'realname'   => $value['realname'] ,
                'tel'        => $value['tel'] ,
                'allmoney'   => $value['allmoney'] ,
                'nowmoney'   => $value['nowmoney'] ,
            ];
        }
        //标题内容
        $filter = [
            'storename'  => '店铺名称' ,
            'mobile'     => '店铺电话' ,
            'address'    => '店铺地址' ,
            'cate'       => '分类信息' ,
            'createtime' => '入驻时间' ,
            'enabled'    => '状态' ,
            'group_name' => '分组' ,
            'realname'   => '负责人' ,
            'tel'        => '负责人电话' ,
            'allmoney'   => '累计金额' ,
            'nowmoney'   => '现有金额' ,
        ];
        util_csv::export_csv_2($newList, $filter, '商户列表.csv');
        exit();
    }

}
















