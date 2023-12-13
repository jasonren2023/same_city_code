<?php
defined('IN_IA') or exit('Access Denied');

class RedpackModuleUniapp extends Uniapp {
    /**
     * Comment: 普通红包列表信息获取
     * Author: zzw
     * Date: 2020/2/21 11:09
     */
    public function redPackList(){
        global $_W,$_GPC;
        #1、参数获取
        $page = $_GPC['page'] ? :1;
        $pageIndex = $_GPC['page_index'] ? :10;
        #2、条件生成
        $where = [
            'uniacid' => $_W['uniacid'] ,
            'aid'     => $_W['aid'],
            'scene'   => 0 ,
            'status'  => 1 ,
        ];
        #3、列表信息获取
        $field = ['id','title','full_money','cut_money','use_start_time','use_end_time','usetime_day1','usetime_day2'
            ,'usegoods_type','usetime_type','limit_count','use_aids','use_sids'];
        $list = pdo_getslice(PDO_NAME.'redpacks' , $where , [$page , $pageIndex] , $total , $field , '' , "sort DESC,id DESC");
        foreach ($list as $key => &$val) {
            //当前用户剩余可以领取的数量  删除用户不能领取的红包信息  开启后 则只显示用户可以领取的红包
            if (!empty($val['limit_count'])) {
                $mycounts = Redpack::getReceiveTotal($val['id'],$_W['mid'],1,0);
                if ($mycounts >= $val['limit_count']){
                    $val['is_over'] = 1;
                }
            }
            //价格处理
            $val['full_money'] = sprintf("%0.2f",$val['full_money']);
            $val['cut_money'] = sprintf("%0.2f",$val['cut_money']);
            //有效期处理
            $usetimes            = [
                date('Y-m-d' , $val['use_start_time']) . ' ~ ' . date('Y-m-d' , $val['use_end_time']) ,
                '领取当日起' . $val['usetime_day1'] . '天内有效' ,
                '领取次日起' . $val['usetime_day2'] . '天内有效'
            ];
            $val['usetime_text'] = $usetimes[$val['usetime_type']];
            //使用条件处理  0全平台1指定代理2指定商家
            if ($val['usegoods_type'] == 1) {
                //代理商可用  查询可用代理商信息
                $aids = unserialize($val['use_aids']);
                $agents = pdo_getall(PDO_NAME."oparea",['aid'=>$aids],'areaid');
                if($agents) $areaInfo = pdo_getall(PDO_NAME."area",['id'=>array_values(array_column($agents,'areaid'))],'name');
                if($areaInfo){
                    $areaName = implode(',',array_column($areaInfo,'name'));
                    $val['use_where'] = "仅限{$areaName}可用";
                }else{
                    $val['use_where'] = "仅限指定地区可用";
                }
            }else if ($val['usegoods_type'] == 2) {
                //商家可用  查询可用商家信息
                $sids = unserialize($val['use_sids']);
                $storeName = pdo_getall(PDO_NAME."merchantdata",['id'=>$sids],'storename');
                if($storeName){
                    $storeName = implode(',',array_column($storeName,'storename'));
                    $val['use_where'] = "仅限{$storeName}商家可用";
                }else{
                    $val['use_where'] = "仅限指定商家可用";
                }
            }else if ($val['usegoods_type'] == 3) {
                //指定商品可用  商品过多,直接显示固定内容
                $val['use_where'] = "仅限指定商品可用";
            }else {
                $val['use_where'] = '全平台可用';
            }
            //删除多余的信息
            unset($val['usegoods_type']);
            unset($val['use_start_time']);
            unset($val['use_end_time']);
            unset($val['usetime_day1']);
            unset($val['usetime_day2']);
            unset($val['usetime_type']);
        }
        #4、信息拼装
        $data = [
            'total' => ceil($total / $pageIndex),
            'list'  => $list ? : [],
        ];

        $this->renderSuccess('普通红包列表',$data);
    }
    /**
     * Comment: 红包领取
     * Author: zzw
     * Date: 2020/2/24 9:34
     */
    public function getRedPack(){
        global $_W,$_GPC;
        #1、参数获取
        $id  = $_GPC['id'] OR $this->renderError('领取失败，不存在的红包！');//红包id
        $source = $_GPC['red_pack_source'] ? : 1;//红包领取渠道（1=普通红包领取；2=新人红包领取；3=节日红包领取）
        $festivalId = $_GPC['festival_id'] ? : 0;//节日红包id
        //新人红包校验
        if($source == 2){
            $info = Setting::agentsetting_read('red_pack_new');
            $whereRes = pdo_getcolumn(PDO_NAME . "redpack_records" , ['mid' => $_W['mid'] , 'source' => 2] , 'id');//新人红包领取信息
            if ($info['wheres'] == 1) {
                //条件为未领取且未下单用户
                $orderRes     = pdo_getcolumn(PDO_NAME . "order" , ['mid' => $_W['mid']] , 'id');
                $rushOrderRes = pdo_getcolumn(PDO_NAME . "rush_order" , ['mid' => $_W['mid']] , 'id');
                if ($whereRes > 0 || $orderRes > 0 || $rushOrderRes > 0){
                    $this->renderError('您非新人,无法领取');
                }
            }else {
                //未领取用户
                if ($whereRes > 0){
                    $this->renderError('您非新人,无法领取');
                }
            }
        }
        #2、红包领取操作
        if(strpos($id,',')){
            $ids = explode(',',trim($id,','));
            foreach($ids as $pack_id){
                Redpack::pack_send($_W['mid'] , $pack_id , 'get',$source,$festivalId);
            }
            $this->renderSuccess('领取成功');
        }else{
            $res = Redpack::pack_send($_W['mid'] , $id , 'get',$source,$festivalId);
            if($res['errno'] == 1){
                $this->renderError($res['message']);
            }else{
                $this->renderSuccess('领取成功');
            }
        }
    }
    /**
     * Comment: 新人红包信息
     * Author: zzw
     * Date: 2020/2/21 14:44
     */
    public function newRedPackInfo(){
        global $_W,$_GPC;
        #1、设置信息获取
        $info = Setting::agentsetting_read('red_pack_new');
        //获取红包总开关设置信息
        $set = Setting::wlsetting_read('red_pack_set');
        $info['switch'] = intval($set['switch'] ? : 0);//0=关闭；1=开启
        $info['intervalmin'] = $set['intervalmin'] ? : 180;
        if($info['status'] == 1) {
            #2、领取条件判断  0=未领取用户,1=未领取且未下单用户
            $ifWhere  = true;//默认符合条件
            $whereRes = pdo_getcolumn(PDO_NAME . "redpack_records" , ['mid' => $_W['mid'] , 'source' => 2] , 'id');//新人红包领取信息
            if ($info['wheres'] == 1) {
                //条件为未领取且未下单用户
                $orderRes     = pdo_getcolumn(PDO_NAME . "order" , ['mid' => $_W['mid']] , 'id');
                $rushOrderRes = pdo_getcolumn(PDO_NAME . "rush_order" , ['mid' => $_W['mid']] , 'id');
                if ($whereRes > 0 || $orderRes > 0 || $rushOrderRes > 0) $ifWhere = false;//不符合条件
            }else {
                //未领取用户
                if ($whereRes > 0) $ifWhere = false;//不符合条件
            }
            #3、判断是否符合领取条件  并且循环处理信息
            if (is_array($info['list']) && count($info['list']) > 0 && $ifWhere) {
                //图片处理
                $info['color'] = unserialize($info['color']);
                $info['image'] = tomedia($info['image']);
                //删除多余字段
                unset($info['image_url']);
                unset($info['wheres']);
                //红包列表获取
                $field = ['id' , 'title' , 'full_money' , 'cut_money' , 'use_start_time' , 'use_end_time' , 'usetime_day1' ,
                    'usetime_day2' , 'usegoods_type' , 'usetime_type' , 'use_aids' , 'use_sids'];
                foreach ($info['list'] as $key => &$val) {
                    //获取红包详细信息
                    $limit = $val['limit'] ? : 0;
                    $val   = pdo_get(PDO_NAME . "redpacks" , [
                        'id'      => $val['id'] ,
                        'status'  => 1 ,
                        'uniacid' => $_W['uniacid']
                    ] , $field);
                    if (!$val) {
                        unset($info['list'][$key]);
                        continue;
                    }
                    //当前用户剩余可以领取的数量  删除用户不能领取的红包信息
                    if ($_W['mid']) {
                        $userGetTotal = Redpack:: getReceiveTotal($val['id'] , $_W['mid'] , 2);
                        $surplusGet   = sprintf("%.0f" , $limit - sprintf("%.0f" , $userGetTotal));
                    }
                    else {
                        $surplusGet = sprintf("%.0f" , $limit);
                    }
                    if ($surplusGet <= 0) {
                        unset($info['list'][$key]);
                        continue;
                    }
                    //价格处理
                    $val['full_money'] = sprintf("%0.2f" , $val['full_money']);
                    $val['cut_money']  = sprintf("%0.2f" , $val['cut_money']);
                    //有效期处理
                    $usetimes            = [
                        date('Y-m-d' , $val['use_start_time']) . ' ~ ' . date('Y-m-d' , $val['use_end_time']) ,
                        '领取当日起' . $val['usetime_day1'] . '天内有效' ,
                        '领取次日起' . $val['usetime_day2'] . '天内有效'
                    ];
                    $val['usetime_text'] = $usetimes[$val['usetime_type']];
                    //使用条件处理  0全平台1指定代理2指定商家
                    if ($val['usegoods_type'] == 1) {
                        //代理商可用  查询可用代理商信息
                        $aids   = unserialize($val['use_aids']);
                        $agents = pdo_getall(PDO_NAME . "oparea" , ['aid' => $aids] , 'areaid');
                        if ($agents) $areaInfo = pdo_getall(PDO_NAME . "area" , ['id' => array_values(array_column($agents , 'areaid'))] , 'name');
                        if ($areaInfo) {
                            $areaName         = implode(',' , array_column($areaInfo , 'name'));
                            $val['use_where'] = "仅限{$areaName}代理可用";
                        }
                        else {
                            $val['use_where'] = "仅限指定地区可用";
                        }
                    }else if ($val['usegoods_type'] == 2) {
                        //商家可用  查询可用商家信息
                        $sids      = unserialize($val['use_sids']);
                        $storeName = pdo_getall(PDO_NAME . "merchantdata" , ['id' => $sids] , 'storename');
                        if ($storeName) {
                            $areaName         = implode(',' , array_column($storeName , 'storename'));
                            $val['use_where'] = "仅限{$areaName}商家可用";
                        }
                        else {
                            $val['use_where'] = "仅限指定商家可用";
                        }
                    }else if ($val['usegoods_type'] == 3) {
                        //指定商品可用  商品过多,直接显示固定内容
                        $val['use_where'] = "仅限指定商品可用";
                    }else {
                        $val['use_where'] = '全平台可用';
                    }
                    //删除多余的信息
                    unset($val['usegoods_type']);
                    unset($val['use_start_time']);
                    unset($val['use_end_time']);
                    unset($val['usetime_day1']);
                    unset($val['usetime_day2']);
                    unset($val['usetime_type']);
                }

                $info['list'] = array_values($info['list']);
                $info['get_status'] = intval(0);//0=未领取，1=已领取
                $this->renderSuccess('新人红包信息' , $info);
            }
        }
        #4、不符合领取条件
        $info['list'] = [];
        $info['get_status'] = intval(1);//0=未领取，1=已领取
        $this->renderSuccess('不符合领取条件',$info);
    }
    /**
     * Comment: 获取最近一条或者指定某一条节日礼包的信息
     * Author: zzw
     * Date: 2020/3/10 13:55
     */
    public function festivalRedPackDesc(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'] ? : '';
        #1、获取节日礼包顺序信息
        $idInfo = Redpack::getDayFestivalRedPack($id);
        #1、获取节日礼包基本信息
        $field           = ['id' ,  'images' , 'color'];
        $info            = pdo_get(PDO_NAME . 'redpack_festival' , ['id' => $idInfo['current']] , $field);
        $info['color']   = unserialize($info['color']);
        $info['image']  = tomedia($info['images']);
        $info['current'] = $idInfo['current'];
        $info['next']    = $idInfo['next'];
        #2、获取当前节日红包中的所有普通红包
        $field = 'a.limit as limit_count,b.id,b.title,b.full_money,b.cut_money,b.use_start_time,b.use_end_time,
        b.usetime_day1,b.usetime_day2,b.usetime_type,b.usegoods_type,b.use_aids,b.use_sids';
        $list = Redpack::getRedPackFestivalJoin($idInfo['current'],$field);
        foreach($list as $key => &$val){
            //当前用户剩余可以领取的数量  删除用户不能领取的红包信息
            if($_W['mid']){
                $userGetTotal = Redpack::getReceiveTotal($val['id'],$_W['mid'],3,$idInfo['current']);
                $surplusGet = sprintf("%.0f",$val['limit_count']- sprintf("%.0f",$userGetTotal));
            }else{
                $surplusGet = sprintf("%.0f",$val['limit_count']);
            }
            if($surplusGet <= 0){
                unset($list[$key]);
                continue;
            }
            //价格处理
            $val['full_money'] = sprintf("%0.2f",$val['full_money']);
            $val['cut_money'] = sprintf("%0.2f",$val['cut_money']);
            //有效期处理
            $usetimes            = [
                date('Y-m-d' , $val['use_start_time']) . ' ~ ' . date('Y-m-d' , $val['use_end_time']) ,
                '领取当日起' . $val['usetime_day1'] . '天内有效' ,
                '领取次日起' . $val['usetime_day2'] . '天内有效'
            ];
            $val['usetime_text'] = $usetimes[$val['usetime_type']];
            //使用条件处理  0全平台1指定代理2指定商家
            if ($val['usegoods_type'] == 1) {
                //代理商可用  查询可用代理商信息
                $aids = unserialize($val['use_aids']);
                $agents = pdo_getall(PDO_NAME."oparea",['aid'=>$aids],'areaid');
                if($agents) $areaInfo = pdo_getall(PDO_NAME."area",['id'=>array_values(array_column($agents,'areaid'))],'name');
                if($areaInfo){
                    $areaName = implode(',',array_column($areaInfo,'name'));
                    $val['use_where'] = "仅限{$areaName}代理可用";
                }else{
                    $val['use_where'] = "仅限指定地区可用";
                }
            }else if ($val['usegoods_type'] == 2) {
                //商家可用  查询可用商家信息
                $sids = unserialize($val['use_sids']);
                $storeName = pdo_getall(PDO_NAME."merchantdata",['id'=>$sids],'storename');
                if($storeName){
                    $areaName = implode(',',array_column($storeName,'storename'));
                    $val['use_where'] = "仅限{$areaName}商家可用";
                }else{
                    $val['use_where'] = "仅限指定商家可用";
                }
            }else if ($val['usegoods_type'] == 3) {
                //指定商品可用  商品过多,直接显示固定内容
                $val['use_where'] = "仅限指定商品可用";
            }else {
                $val['use_where'] = '全平台可用';
            }
            //删除多余的信息
            unset($val['limit_count']);
            unset($val['usegoods_type']);
            unset($val['use_start_time']);
            unset($val['use_end_time']);
            unset($val['usetime_day1']);
            unset($val['usetime_day2']);
            unset($val['usetime_type']);
        }


        $info['list'] = $list ? array_values($list) : [];
        //$info['get_status'] = intval(0);//0=未领取，1=已领取
        //if(!$info['list']) $info['get_status'] = intval(1);//0=未领取，1=已领取
        //获取红包开关设置信息
        $set = Setting::wlsetting_read('red_pack_set');
        $info['switch'] = intval($set['switch'] ? : 0);//0=关闭；1=开启
        if(!$info['list']) $info['switch'] = intval(0);//无红包信息时关闭红包接口
        if($info['next'] > 0) $info['switch'] = intval(1);//如果存在其他节日红包  开启

        $this->renderSuccess('节日红包详细信息',$info);
    }
    /**
     * Comment: 我的红包列表
     * Author: zzw
     * Date: 2020/2/27 14:06
     */
    public function userRedPackList(){
        global $_W,$_GPC;
        #1、参数获取
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $status    = $_GPC['status'] ? : 0;//0=未使用;1=已使用;2=已过期
        $time      = time();
        #2、条件生成
        $publicWhere = $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.mid = {$_W['mid']} ";
        if($status == 2){
            $where .= " AND a.status = {$status} ";//已过期
        }else if($status == 1){
            $where .= " AND a.status = {$status} ";//已使用   前提条件：未过期
        } else{
            $where .= " AND a.status IN (0,6) ";//未使用   前提条件：未过期
        }
        #3、列表获取
        $field = " a.status,a.start_time,a.end_time,a.source,a.festival_id,b.full_money,b.cut_money,b.title
        ,b.usegoods_type,b.use_aids,b.use_sids,a.id,b.transferstatus,b.transfermore,b.transferlevel,a.transferflag";
        $limit = " limit {$pageStart},{$pageIndex} ";
        $list = Redpack::getUserRedPackInfo($field,$where,$limit);
        $total = Redpack::getUserRedPackInfo("count(*) as total",$where);
        #4、信息处理
        foreach($list as $key => &$val){
            $val['transferflag'] = $val['transferflag'] ? : 0;
            //金额处理
            $val['full_money'] = sprintf("%0.2f",$val['full_money']);
            $val['cut_money'] = sprintf("%0.2f",$val['cut_money']);
            //判断是否已过期
            if($val['end_time'] <= $time) $val['status'] = 2;
            //时间处理
            $val['time'] = date("Y-m-d",$val['start_time'])."至".date("Y-m-d",$val['end_time']);
            //使用条件处理  0全平台1指定代理2指定商家
            if ($val['usegoods_type'] == 1) {
                //代理商可用  查询可用代理商信息
                $val['use_where'] = "仅限指定地区可用";
            }else if ($val['usegoods_type'] == 2) {
                //商家可用  查询可用商家信息
                $val['use_where'] = "仅限指定商家可用";
            }else if ($val['usegoods_type'] == 3) {
                //指定商品可用  商品过多,直接显示固定内容
                $val['use_where'] = "仅限指定商品可用";
            }else {
                $val['use_where'] = '全平台可用';
                $val['link'] = h5_url('pages/mainPages/index/index');
            }
            //判断是否为节日红包  是则获取红包标签
            $val['label'] = '';
            if($val['source'] == 3) $val['label'] = pdo_getcolumn(PDO_NAME."redpack_festival",['id'=>$val['festival_id']],'label');
            //判断是否可以转赠
            if($val['transferstatus'] > 0){
                $val['transferstatus'] = 0;
                if(empty($val['transferflag']) || $val['transfermore'] > 0){
                    $transferlevel = unserialize($val['transferlevel']);
                    if(empty($transferlevel)){
                        $val['transferstatus'] = 1;
                    }else{
                        $halfflag = WeliamWeChat::VipVerification($_W['mid']);
                        if(empty($halfflag)){
                            $levelid = -1;
                        }else{
                            $levelid = $halfflag['levelid'];
                        }
                        if(in_array($levelid,$transferlevel)){
                            $val['transferstatus'] = 1;
                        }
                    }
                }
            }
            $val['transferstatus'] = $val['transferstatus'] ? : 0;
            //转赠卡券信息获取
            if($val['transferflag'] > 0){
                $record = pdo_getall('wlmerchant_transferRecord',array('type' => 2,'objid' => $val['id'],'nmid' => $_W['mid']),array('omid','gettime'),'','ID DESC');
                $record = $record[0];
                $oldmember = pdo_get('wlmerchant_member',array('id' => $record['omid']),array('nickname','mobile'));
                $val['oldnickname'] = $oldmember['nickname'];
                $val['oldmobile'] = substr($oldmember['mobile'],0,3).'****'.substr($oldmember['mobile'], -4);
                $val['gettime'] = date('Y/m/d H:i:s',$record['gettime']);
            }
            //删除多余的信息
            unset($val['start_time']);
            unset($val['end_time']);
            unset($val['source']);
            unset($val['start_time']);
            unset($val['festival_id']);
        }
        #5、统计信息获取
        //未使用红包  未过期且未使用
        $notWhere = $publicWhere." AND a.status = 0 ";//未使用条件
        $notUsed = Redpack::getUserRedPackInfo("count(*) as total" , $notWhere);//未使用
        //已使用红包  已使用
        $useWhere = $publicWhere." AND a.status = 1 AND a.end_time > ".$time;//已使用条件
        $used    = Redpack::getUserRedPackInfo("count(*) as total" , $useWhere);//已使用
        //已过期红包  未使用且已过期
        $expiredWhere = $publicWhere." AND a.status = 2 ";//已过期条件
        $expired = Redpack::getUserRedPackInfo("count(*) as total" , $expiredWhere);//已过期
        //快过期红包  未过期且未使用，同时过期时间为1天内
        $expireSoonWhere = $publicWhere." AND a.status = 0 AND a.end_time > ".$time." AND a.end_time < ".($time + 86400);//未使用条件
        $expireSoon = Redpack::getUserRedPackInfo("count(*) as total" , $expireSoonWhere);//未使用
        #6、信息拼装
        $info['list'] = $list;
        $info['redpacketData'] = [
            'total'      => ceil(array_column($total , 'total')[0] / $pageIndex) ,
            'not_used'   => array_column($notUsed , 'total')[0] ,
            'used'       => array_column($used , 'total')[0] ,
            'expired'    => array_column($expired , 'total')[0] ,
            'expireSoon' => array_column($expireSoon , 'total')[0] ,
        ];

        if(Customized::init('transfergift') > 0){
            $info['transfer'] = 1;
            //统计数量
            $trwhere = " WHERE uniacid = {$_W['uniacid']} AND type = 2 AND omid = {$_W['mid']} AND status = 1";
            $alllist = pdo_fetchall("select distinct objid from " . tablename(PDO_NAME.'transferRecord').$trwhere);
            $allnum = count($alllist);
            $info['redpacketData']['alltr'] = $allnum;
        }else{
            $info['transfer'] = 0;
            //统计数量
            $info['redpacketData']['alltr'] = 0;
        }

        $this->renderSuccess('我的红包列表',$info);
    }


    /**
     * Comment: 获取某个红包的详细使用信息
     * Author: zzw
     * Date: 2020/4/29 10:34
     */
    public function getRedPackUserWhere(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'] OR $this->renderError('不存在的红包，请刷新重试!');
        #2、红包信息获取
        $packid = pdo_getcolumn(PDO_NAME."redpack_records",['id'=>$id],'packid');
        #2、信息获取
        $field = ['id','title' ,'usegoods_type','usetime_type','use_aids','use_sids' ,'rush_ids','group_ids','fight_ids','bargain_ids'];
        $info = pdo_get(PDO_NAME.'redpacks' , ['id' => $packid],$field);
        if(!$info) $this->renderError('不存在的红包，请刷新重试!');
        //使用条件处理  0全平台1指定代理2指定商家
        if ($info['usegoods_type'] == 1) {
            //代理商可用  查询可用代理商信息
            $aids = unserialize($info['use_aids']);
            //获取可用地区
            if($aids){
                foreach ($aids as $aid){
                    $agents = pdo_get(PDO_NAME."oparea",['aid'=>$aid],['areaid','id']);
                    $age['name'] = pdo_getcolumn(PDO_NAME.'area',array('id'=>$agents['areaid']),'name');
                    $age['link'] = h5_url('pages/mainPages/index/index','','',$agents['id']);
                }
                $info['use_where'][] = $age;
            }else{
                $info['use_where'] = [];
            }
        }else if ($info['usegoods_type'] == 2) {
            //商家可用  查询可用商家信息
            $sids = unserialize($info['use_sids']);
            if($sids){
                foreach ($sids as $sid){
                    $store = pdo_get(PDO_NAME."merchantdata",['id'=>$sid],['storename','aid']);
                    $age['name'] = $store['storename'];
                    $age['link'] = h5_url('pages/mainPages/store/index',['sid' => $sid],'',$store['aid']);
                    $info['use_where'][] = $age;
                }
            }else{
                $info['use_where'] = [];
            }
        }else if ($info['usegoods_type'] == 3) {
            //仅限指定商品可用
            $rushIds = unserialize($info['rush_ids']);//抢购
            $groupIds = unserialize($info['group_ids']);//团购
            $fightIds = unserialize($info['fight_ids']);//拼团
            $bargainIds = unserialize($info['bargain_ids']);//砍价
            if($rushIds){
                foreach ($rushIds as $rgid){
                    $goods = pdo_get(PDO_NAME."rush_activity",['id'=>$rgid],['name','aid','id']);
                    $age['name'] = '(抢购)'.$goods['name'];
                    $age['link'] = h5_url('pages/subPages/goods/index',['type'=>1,'id' =>$goods['id']],'',$goods['aid']);
                    $rushList[] = $age;
                }
            }else{
                $rushList = [];
            }
            if($groupIds){
                foreach ($groupIds as $ggid){
                    $goods = pdo_get(PDO_NAME."groupon_activity",['id'=>$ggid],['name','aid','id']);
                    $age['name'] = '(团购)'.$goods['name'];
                    $age['link'] = h5_url('pages/subPages/goods/index',['type'=>2,'id' =>$goods['id']],'',$goods['aid']);
                    $groupList[] = $age;
                }
            }else{
                $groupList = [];
            }
            if($fightIds){
                foreach ($fightIds as $fgid){
                    $goods = pdo_get(PDO_NAME."fightgroup_goods",['id'=>$fgid],['name','aid','id']);
                    $age['name'] = '(拼团)'.$goods['name'];
                    $age['link'] = h5_url('pages/subPages/goods/index',['type'=>3,'id' =>$goods['id']],'',$goods['aid']);
                    $fightList[] = $age;
                }
            }else{
                $fightList = [];
            }
            if($bargainIds){
                foreach ($bargainIds as $bgid){
                    $goods = pdo_get(PDO_NAME."bargain_activity",['id'=>$bgid],['name','aid','id']);
                    $age['name'] = '(砍价)'.$goods['name'];
                    $age['link'] = h5_url('pages/subPages/goods/index',['type'=>7,'id' =>$goods['id']],'',$goods['aid']);
                    $bargainList[] = $age;
                }
            }else{
                $bargainList = [];
            }
            //数组合并
            $info['use_where'] = array_merge($rushList,$groupList,$fightList,$bargainList);
        }else {
            $info['use_where'] = '全平台可用';
        }
        //删除多余的信息
        unset($info['usegoods_type']);
        unset($info['use_start_time']);
        unset($info['use_end_time']);
        unset($info['usetime_day1']);
        unset($info['usetime_day2']);
        unset($info['usetime_type']);
        unset($info['use_aids']);
        unset($info['use_sids']);
        unset($info['rush_ids']);
        unset($info['group_ids']);
        unset($info['fight_ids']);
        unset($info['bargain_ids']);

        $info['id'] = $id;


        $this->renderSuccess('红包的详细使用信息',$info);
    }



}