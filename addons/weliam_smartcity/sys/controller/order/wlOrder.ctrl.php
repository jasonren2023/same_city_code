<?php
defined('IN_IA') or exit('Access Denied');

class WlOrder_WeliamController {

    public function orderlist() {
        global $_W, $_GPC;
        #1、条件生成
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $pageStart = $pindex * $psize - $psize;
        $where = " where uniacid = {$_W['uniacid']} and orderno != 666666";
        $where2 = " where a.uniacid = {$_W['uniacid']} and a.orderno != 666666";
        $agentlist = pdo_getall('wlmerchant_agentusers',array('uniacid' => $_W['uniacid']),array('id','agentname'));
        //端口判断
        if(is_agent()){
            $where .= " and aid = {$_W['aid']}";
            $where2 .= " and a.aid = {$_W['aid']}";
        }
        if(is_store()){
            $where .= " and sid = {$_W['storeid']}";
            $where2 .= " and a.sid = {$_W['storeid']}";
        }
        //状态
        if($_GPC['status']){
            if($_GPC['status'] == 'zero'){
                $where .= " and status = 0";
                $where2 .= " and a.status = 0";
            }else if(intval($_GPC['status']) == 11){
                $where .= " and status IN (1,2,3,4,6,8,9)";
                $where2 .= " and a.status IN (1,2,3,4,6,8,9)";
            }else if(intval($_GPC['status']) == 12){
                $where .= " and status IN (2,3)";
                $where2 .= " and a.status IN (2,3)";
            }else if(intval($_GPC['status']) == 13){
                $where .= " and status IN  (0,1,2,3,4,6,8,9)";
                $where2 .= " and a.status IN  (0,1,2,3,4,6,8,9)";
            }else{
                $where .= " and status = {$_GPC['status']} ";
                $where2 .= " and a.status = {$_GPC['status']} ";
            }
        }
        //支付方式
        if($_GPC['paytype']){
            $where .= " and paytype = {$_GPC['paytype']} ";
            $where2 .= " and a.paytype = {$_GPC['paytype']} ";
        }
        //所属代理
        if($_GPC['agentid'] != 0){
            if($_GPC['agentid'] == -1){
                $where .= " and aid = 0 ";
                $where2 .= " and a.aid = 0 ";
            }else{
                $where .= " and aid = {$_GPC['agentid']} ";
                $where2 .= " and a.aid = {$_GPC['agentid']} ";
            }
        }
        if($_GPC['keyword']){
            $keyword = trim($_GPC['keyword']);
            if($_GPC['keywordtype'] == 1){
                $where .= " and orderno LIKE '%{$keyword}%'";
                $where2 .= " and a.orderno LIKE '%{$keyword}%'";
            }else if($_GPC['keywordtype'] == 3){
                $where .= " and mid = '{$keyword}'";
                $where2 .= " and a.mid = '{$keyword}'";
            }else if($_GPC['keywordtype'] == 4){
                $where .= " and sid = '{$keyword}'";
                $where2 .= " and a.sid = '{$keyword}'";
            }else if($_GPC['keywordtype'] == 5){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where .= " and mid IN {$mids}";
                    $where2 .= " and a.mid IN {$mids}";
                }
            }else if($_GPC['keywordtype'] == 6){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']} AND mobile LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";

                    $where .= " and (mid IN {$mids} OR mobile LIKE '%{$keyword}%') ";
                    $where2 .= " and  (a.mid IN {$mids} OR a.mobile LIKE '%{$keyword}%') ";
                }else{
                    //可以通过当前订单所使用的手机进行查询
                    $where .= " and mobile LIKE '%{$keyword}%' ";
                    $where2 .= " and a.mobile LIKE '%{$keyword}%' ";
                }

            }else if($_GPC['keywordtype'] == 7){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_merchantdata')." WHERE uniacid = {$_W['uniacid']} AND storename LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where .= " and sid IN {$mids}";
                    $where2 .= " and a.sid IN {$mids}";
                }
            }else if($_GPC['keywordtype'] == 8){
                //核销码
                $smorder = pdo_get(PDO_NAME.'smallorder',array('checkcode'=>$keyword),array('orderid','plugin'));
                if($smorder){
                    if($smorder['plugin'] == 'rush'){
                        $where .= " AND id = {$smorder['orderid']} ";
                        $where2 .= " AND a.id = 0";
                    }else{
                        $where .= " AND id = 0 ";
                        $where2 .= " AND a.id = {$smorder['orderid']}";
                    }
                }else{
                    $where .= " AND checkcode = '{$keyword}' ";
                    $where2 .= " AND (f.qrcode = {$keyword} OR g.qrcode = {$keyword} OR `c`.concode = {$keyword} OR b.qrcode = {$keyword}) ";
                }
            }else if($_GPC['keywordtype'] == 9){
                $keyword = "'%{$keyword}%'";
                $where .= " AND mobile like {$keyword} ";
                $where2 .= " AND a.mobile like {$keyword} ";

            }else if($_GPC['keywordtype'] == 10){
                $saleman = pdo_get('wlmerchant_merchantuser',array('id' => $keyword),array('createtime','storeid'));
                $where .= " and sid = {$saleman['storeid']} and createtime > {$saleman['createtime']}";
                $where2 .= " and a.sid = {$saleman['storeid']} and createtime > {$saleman['createtime']}";
            }else if($_GPC['keywordtype'] == 12 || $_GPC['keywordtype'] == 13){
                $params[':name'] = "%{$keyword}%";
                if($_GPC['keywordtype'] == 12){
                    $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_express')." WHERE uniacid = {$_W['uniacid']} AND tel LIKE :name",$params);
                }else{
                    $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_express')." WHERE uniacid = {$_W['uniacid']} AND name LIKE :name",$params);
                }
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where .= " and expressid IN {$mids} and expressid > 0 ";
                    $where2 .= " and a.expressid IN {$mids} and expressid > 0";
                }
            }else if($_GPC['keywordtype'] == 14){
                //快递单号
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_express')." WHERE uniacid = {$_W['uniacid']} AND expresssn LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where .= " and expressid IN {$mids} and expressid > 0 ";
                    $where2 .= " and a.expressid IN {$mids} and expressid > 0";
                }else{
                    $where .= " AND id = 0 ";
                    $where2 .= " AND a.id = 0";
                }
            }
        }
        //时间
        if (!empty($_GPC['time_limit']) && $_GPC['timetype'] ) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            if($_GPC['timetype'] == 1){
                $where .= " and createtime > {$starttime} and createtime < {$endtime}";
                $where2 .= " and a.createtime > {$starttime} and a.createtime < {$endtime}";
            }else{
                $where .= " and paytime > {$starttime} and paytime < {$endtime}";
                $where2 .= " and a.paytime > {$starttime} and a.paytime < {$endtime}";
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        //插件
        if($_GPC['plugin']){
            $plugin = $_GPC['plugin'];
            //关键字
            if($plugin == 'rush'){
                $where2 .= " AND  a.plugin IN ('null') ";
            }else{
                $where2 .= " AND  a.plugin = '{$plugin}' ";
                $where .= " and orderno = 00000 ";
            }
        }else{
            $where2 .= " AND  a.plugin IN ('wlfightgroup','groupon','coupon','activity','bargain','citydelivery','hotel') ";
        }
        if($_GPC['keywordtype'] == 2 && $_GPC['keyword'] && $_GPC['plugin'] != 'citydelivery'){
            $where .= " and activityid = {$keyword} ";
            $where2 .= " and  a.fkid = {$keyword} ";
        }
        if($_GPC['keywordtype'] == 2 && $_GPC['keyword'] && $_GPC['plugin'] == 'citydelivery'){
            $desmorder = pdo_getall(PDO_NAME.'delivery_order',array('gid'=>$keyword),array('tid'));
            if($desmorder){
                $ids = "(";
                foreach ($desmorder as $key => $v) {
                    $id = pdo_getcolumn(PDO_NAME.'order',array('orderno'=>$v['tid']),'id');
                    if($key == 0){
                        $ids.= $id;
                    }else{
                        $ids.= ",".$id;
                    }
                }
                $ids.= ")";
                $where2 .= " and a.id IN {$ids}";
            }else{
                $where2 .= " and a.id IN (0)";
            }
        }
        //商品名
        if($_GPC['keywordtype'] == 11){
            $rush =  pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_rush_activity')." WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND name LIKE '%{$keyword}%'  ");
            $rush = array_column($rush,'id');
			if(empty($rush)){
				$rush = '(0)';
			}else{
				$rush = '('.implode(',',$rush).')';
			}
            $where .= " and activityid IN {$rush} ";

            $groupon =  pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_groupon_activity')." WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND name LIKE '%{$keyword}%'  ");
            $coupon =  pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_couponlist')." WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND title LIKE '%{$keyword}%'  ");
            $fightgroup =  pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_fightgroup_goods')." WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND name LIKE '%{$keyword}%'  ");
            $bargain =  pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_bargain_activity')." WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND name LIKE '%{$keyword}%'  ");
            $activity =  pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_activitylist')." WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND title LIKE '%{$keyword}%'  ");
            $allgoods = array_merge($groupon,$coupon,$fightgroup,$bargain,$activity);
            $allgoods = array_column($allgoods,'id');
			if(empty($allgoods)){
				$allgoods = '(0)';
			}else{
				$allgoods = '('.implode(',',$allgoods).')';
			}
            $where2 .= " and a.fkid IN {$allgoods} ";
        }

        //拼团
        if($_GPC['fightgroupid']){
            $where2 .= " and  a.fightgroupid = {$_GPC['fightgroupid']} ";
        }
        //获取自定义表单信息
        if($_GPC['keywordtype'] == 2 && !empty($plugin) && !empty($keyword)){
            if($plugin == 'rush'){
                $diyformid = pdo_getcolumn(PDO_NAME.'rush_activity',array('uniacid'=>$_W['uniacid'],'id'=>$keyword),'diyformid');
            }else if($plugin == 'groupon'){
                $diyformid = pdo_getcolumn(PDO_NAME.'groupon_activity',array('uniacid'=>$_W['uniacid'],'id'=>$keyword),'diyformid');
            }else if($plugin == 'wlfightgroup'){
                $diyformid = pdo_getcolumn(PDO_NAME.'fightgroup_goods',array('uniacid'=>$_W['uniacid'],'id'=>$keyword),'diyformid');
            }else if($plugin == 'bargain'){
                $diyformid = pdo_getcolumn(PDO_NAME.'bargain_activity',array('uniacid'=>$_W['uniacid'],'id'=>$keyword),'diyformid');
            }else if($plugin == 'activity'){
                $diyformid = pdo_getcolumn(PDO_NAME.'activitylist',array('uniacid'=>$_W['uniacid'],'id'=>$keyword),'diyformid');
            }else if($plugin == 'coupon'){
                $diyformid = pdo_getcolumn(PDO_NAME.'couponlist',array('uniacid'=>$_W['uniacid'],'id'=>$keyword),'diyformid');
            }
        }
        if(empty($diyformid)){
            $diyformid = 0;
        }


        //导出
        if($_GPC['export']){
            $this -> export($where,$where2,$diyformid);
        }
        #2、列表获取
        $orderField = 'id,aid,mobile,createtime,sid,status,paidprid,mid,fullreducemoney,orderno,num,price,vip_card_id,paytype,vipbuyflag,paytime,changedispatchprice,changeprice,disorderid,applyrefund
        ,plugin,fkid as goods_id,blendcredit,redpackmoney,"a" as a';
        $rushOrderField = 'id,aid,mobile,createtime,sid,status,paidprid,mid,fullreducemoney,orderno,num,price,vip_card_id,paytype,vipbuyflag,paytime,changedispatchprice,changeprice,disorderid,applyrefund
        ,"rush" as plugin,activityid as goods_id,blendcredit,redpackmoney,"b" as a';
        $orderBy = ' ORDER BY createtime DESC ';
        $limit = " LIMIT {$pageStart},{$psize} ";
        $orderT = tablename(PDO_NAME."order");
        $rushOrderT = tablename(PDO_NAME."rush_order");
        $orderW = str_replace('a.','',$where2);
        $orderlist = pdo_fetchall("SELECT {$orderField} FROM ".$orderT.$orderW
            ." UNION ALL SELECT {$rushOrderField} FROM ".$rushOrderT.$where.$orderBy.$limit);
        #3、循环处理列表数据
        foreach ($orderlist as $key => &$v) {
            //商品信息获取
            switch ($v['plugin']) {
                case 'rush':
                    //商品信息获取
                    $goods = pdo_get(PDO_NAME . "rush_activity" , ['id' => $v['goods_id']] , ['name','pftid','price','vipprice','thumb' , 'id' , 'isdistri' , 'cutofftime' , 'unit']);
                    $v['unit']      = $goods['unit'];
                    $v['isdistri']  = $goods['isdistri'];
                    $v['mobile']    = $goods['mobile'];
                    $v['goodsimg']  = tomedia($goods['thumb']);
                    $v['goodsname'] = $goods['name'];
                    //其他信息处理
                    $v['goodsprice'] = sprintf("%.2f" , $v['price'] / $v['num']);
                    $v['plugintext'] = '抢购';
                    $v['plugincss']  = 'success';
                    $v['detailurl']  = web_url("order/wlOrder/orderdetail" , ['orderid' => $v['id'] , 'type' => 1]);
                    $rushorder = pdo_get(PDO_NAME.'rush_order', array('id'=>$v['id'],'uniacid'=>$_W['uniacid']),array('activityid','discount','username','mobile','optionid','neworderflag','dkmoney','actualprice','remark','adminremark','expressid'));
                    $v['actualprice'] = $rushorder['actualprice'];
                    if($rushorder['optionid']){
                        $v['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$rushorder['optionid']),'title');
                    }
                    $v['remark'] = $rushorder['adminremark'];
                    $v['buyremark'] = $rushorder['remark'];
                    $v['expressid'] = $rushorder['expressid'];
                    //积分抵扣
                    $v['dkmoney'] = $rushorder['dkmoney'];
                    //VIP折扣
                    if ($v['vipbuyflag']) {
                        if($rushorder['discount'] > 0){
                            $v['vipdiscount'] = $rushorder['discount'];
                        }else{
                            if ($rushorder['optionid']) {
                                $option = pdo_get('wlmerchant_goods_option',array('id' => $rushorder['optionid']),array('price','vipprice'));
                                $v['vipdiscount'] = sprintf("%.2f" , $option['price'] - $option['vipprice']);
                            }
                            else {
                                $v['vipdiscount'] = sprintf("%.2f" , $goods['price'] - $goods['vipprice']);
                            }
                        }
                    }
                    //用户信息
                    $v['username'] = $rushorder['username'];
                    $v['mobile'] = $rushorder['mobile'];
                    //校验
                    $v['pftflag'] = $goods['pftid'];
                    break;//抢购
                case 'groupon':
                    //商品信息获取
                    $goods          = pdo_get('wlmerchant_groupon_activity' , ['id' => $v['goods_id']] , ['name','price','vipprice' , 'isdistri' ,'pftid', 'thumb' , 'id' , 'unit']);
                    $v['goodsname'] = $goods['name'];
                    $v['isdistri']  = $goods['isdistri'];
                    $v['unit']      = $goods['unit'];
                    $v['goodsimg']  = tomedia($goods['thumb']);
                    //其他信息处理
                    $v['actualprice'] = $v['price'];
                    $v['plugintext'] = '团购';
                    $v['plugincss'] = 'info';
                    $v['detailurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$v['id'],'type'=>10));
                    $ndorder = pdo_get(PDO_NAME.'order', array('id'=>$v['id'],'uniacid'=>$_W['uniacid']),array('vipdiscount','cerditmoney','id','name','mobile','fightgroupid','payfor','plugin','fkid','specid','fightstatus','expressid','recordid','goodsprice','card_fee','remark','buyremark'));
                    $v['price'] = $ndorder['goodsprice'];
                    $v['goodsprice'] = sprintf("%.2f",$ndorder['goodsprice']/$v['num']);
                    if($ndorder['specid']){
                        $v['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$ndorder['specid']),'title');
                    }
                    //VIP折扣
                    if ($v['vipbuyflag']) {
                        if($ndorder['vipdiscount'] > 0){
                            $v['vipdiscount'] = $ndorder['vipdiscount'];
                        }else {
                            if ($ndorder['specid']) {
                                $option = pdo_get('wlmerchant_goods_option', array('id' => $ndorder['specid']), array('price', 'vipprice'));
                                $v['vipdiscount'] = sprintf("%.2f", $option['price'] - $option['vipprice']);
                            } else {
                                $v['vipdiscount'] = sprintf("%.2f", $goods['price'] - $goods['vipprice']);
                            }
                        }
                    }
                    $v['expressid'] = $ndorder['expressid'];
                    $v['buyremark'] = $ndorder['buyremark'];
                    $v['remark'] = $ndorder['remark'];
                    $v['dkmoney'] = $ndorder['cerditmoney'];
                    $v['pftflag'] = $goods['pftid'];
                    break;//团购
                case 'wlfightgroup':
                    $goods = Wlfightgroup::getSingleGood($v['goods_id'],'name,logo,id,unit,vipdiscount,isdistri');
                    $ndorder = pdo_get(PDO_NAME . 'order' , ['id' => $v['id'] , 'uniacid' => $_W['uniacid']] , ['cerditmoney','vipdiscount','id' , 'name' , 'mobile' , 'fightgroupid' , 'payfor' , 'plugin' , 'fkid' , 'specid' , 'fightstatus' , 'expressid' , 'recordid' , 'goodsprice' , 'card_fee' , 'remark' , 'buyremark']);
                    $group = pdo_get(PDO_NAME.'fightgroup_group',array('id' => $ndorder['fightgroupid']));
                    if($group['status'] == 1 && $v['status'] == 1){
                        $v['status'] = 10;
                    }
                    $v['expressid'] = $ndorder['expressid'];
                    $v['goodsname'] = $goods['name'];
                    $v['isdistri'] = $goods['isdistri'];
                    $v['unit'] = $goods['unit'];
                    $v['goodsimg'] = tomedia($goods['logo']);
                    $v['actualprice'] = $v['price'];
                    $v['price'] = $ndorder['goodsprice'] ;
                    $v['goodsprice'] = sprintf("%.2f",$ndorder['goodsprice']/$v['num']);
                    $v['plugintext'] = '拼团';
                    $v['plugincss'] = 'warning';
                    $v['detailurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$v['id'],'type'=>2));
                    if($ndorder['specid']){
                        $v['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$ndorder['specid']),'title');
                    }
                    //会员减免
                    if($v['vipbuyflag']){
                        if($ndorder['vipdiscount'] > 0){
                            $v['vipdiscount'] = $ndorder['vipdiscount'];
                        }else {
                            $v['vipdiscount'] = sprintf("%.2f", $goods['vipdiscount'] * $v['num']);
                        }
                    }
                    //积分抵扣
                    $v['dkmoney'] = $ndorder['cerditmoney'];
                    $v['buyremark'] = $ndorder['buyremark'];
                    $v['remark'] = $ndorder['remark'];
                    break;//拼团
                case 'coupon':
                    $goods = wlCoupon::getSingleCoupons($v['goods_id'],'title,vipprice,price,logo,id,isdistri');
                    $ndorder = pdo_get(PDO_NAME . 'order' , ['id' => $v['id'] , 'uniacid' => $_W['uniacid']] , ['cerditmoney','vipdiscount','id' , 'name' , 'mobile' , 'fightgroupid' , 'payfor' , 'plugin' , 'fkid' , 'specid' , 'fightstatus' , 'expressid' , 'recordid' , 'goodsprice' , 'card_fee' , 'remark' , 'buyremark']);
                    $v['goodsname'] = $goods['title'];
                    $v['isdistri'] = $goods['isdistri'];
                    $v['unit'] = '张';
                    $v['goodsimg'] = tomedia($goods['logo']);
                    $v['actualprice'] = $v['price'];
                    $v['price'] = $ndorder['goodsprice'] ;
                    $v['goodsprice'] = sprintf("%.2f",$ndorder['goodsprice']/$v['num']);
                    $v['plugintext'] = '卡券';
                    $v['plugincss'] = 'danger';
                    $v['detailurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$v['id'],'type'=>3));
                    $v['expressid'] = $ndorder['expressid'];
                    $v['buyremark'] = $ndorder['buyremark'];
                    $v['remark'] = $ndorder['remark'];
                    $v['dkmoney'] = $ndorder['cerditmoney'];
                    //VIP折扣
                    if ($v['vipbuyflag']) {
                        if($ndorder['vipdiscount'] > 0){
                            $v['vipdiscount'] = $ndorder['vipdiscount'];
                        }else {
                            $v['vipdiscount'] = sprintf("%.2f", $goods['price'] - $goods['vipprice']);
                        }
                    }
                    break;//卡卷
                case 'bargain':
                    $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $v['goods_id']),array('name','isdistri','thumb','id','unit'));
                    $ndorder = pdo_get(PDO_NAME . 'order' , ['id' => $v['id'] , 'uniacid' => $_W['uniacid']] , ['cerditmoney','id' , 'name' , 'mobile' , 'fightgroupid' , 'payfor' , 'plugin' , 'fkid' , 'specid' , 'fightstatus' , 'expressid' , 'recordid' , 'goodsprice' , 'card_fee' , 'remark' , 'buyremark']);
                    $v['goodsname'] = $goods['name'];
                    $v['isdistri'] = $goods['isdistri'];
                    $v['unit'] = $goods['unit'];
                    $v['goodsimg'] = tomedia($goods['thumb']);
                    $v['actualprice'] = $v['price'];
                    $v['price'] = $ndorder['goodsprice'] ;
                    $v['goodsprice'] = sprintf("%.2f",$ndorder['goodsprice']/$v['num']);
                    $v['plugintext'] = '砍价';
                    $v['plugincss'] = 'primary';
                    $v['detailurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$v['id'],'type'=>12));
                    $v['expressid'] = $ndorder['expressid'];
                    $v['buyremark'] = $ndorder['buyremark'];
                    $v['remark'] = $ndorder['remark'];
                    $v['dkmoney'] = $ndorder['cerditmoney'];
                    break;//砍价
                case 'citydelivery':
                    $store = pdo_get('wlmerchant_merchantdata',array('id' => $v['sid']),array('storename','logo','id'));
                    $ndorder = pdo_get(PDO_NAME . 'order' , ['id' => $v['id'] , 'uniacid' => $_W['uniacid']] , ['cerditmoney','id','name','mobile','plugin','expressid','goodsprice','remark','buyremark','redpackmoney','vipdiscount','expressprcie','packingmoney']);
                    $v['goodsname'] = '['.$store['storename'].']配送商品';
                    $v['goodsimg'] = tomedia($store['logo']);
                    $v['actualprice'] = $v['price'];
                    $v['price'] = $v['goodsprice'] = $ndorder['goodsprice'];
                    $v['plugintext'] = '同城配送';
                    $v['plugincss'] = 'success';
                    $v['detailurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$v['id'],'type'=>14));
                    $v['expressid'] = $ndorder['expressid'];
                    $v['buyremark'] = $ndorder['buyremark'];
                    $v['remark'] = $ndorder['remark'];
                    $v['vipdiscount'] = $ndorder['vipdiscount'];
                    $v['express']['expressprice'] = $ndorder['expressprcie'];
                    $v['packingmoney'] = $ndorder['packingmoney'];
                    $v['num'] = 1;
                    $v['dkmoney'] = $ndorder['cerditmoney'];
                    break;//同城配送
                case 'activity':
                    //商品信息获取
                    $goods          = pdo_get('wlmerchant_activitylist' , ['id' => $v['goods_id']] , ['title','price','vipprice' , 'isdistri' , 'thumb' , 'id']);
                    $v['goodsname'] = $goods['title'];
                    $v['isdistri']  = $goods['isdistri'];
                    $v['goodsimg']  = tomedia($goods['thumb']);
                    //其他信息处理
                    $v['actualprice'] = $v['price'];
                    $v['plugintext'] = '活动';
                    $v['plugincss'] = 'primary';
                    $v['detailurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$v['id'],'type'=>9));

                    $ndorder = pdo_get(PDO_NAME.'order', array('id'=>$v['id'],'uniacid'=>$_W['uniacid']),array('cerditmoney','vipdiscount','id','name','mobile','fightgroupid','payfor','plugin','fkid','specid','fightstatus','expressid','recordid','goodsprice','card_fee','remark','buyremark'));
                    $v['price'] = $ndorder['goodsprice'];
                    $v['goodsprice'] = sprintf("%.2f",$ndorder['goodsprice']/$v['num']);
                    if($ndorder['specid']>0){
                        $option = pdo_get(PDO_NAME.'activity_spec',array('id'=>$ndorder['specid']),['name','vipprice']);
                        $v['optiontitle'] = $option['name'];
                    }
                    //VIP折扣
                    if ($v['vipbuyflag']) {
                        if($ndorder['vipdiscount'] > 0){
                            $v['vipdiscount'] = $ndorder['vipdiscount'];
                        }else {
                            if($ndorder['specid']>0){
                                $v['vipdiscount'] = $goods['vipprice'];
                            }else{
                                $v['vipdiscount'] = $option['vipprice'];
                            }
                        }
                    }
                    $v['buyremark'] = $ndorder['buyremark'];
                    $v['remark'] = $ndorder['remark'];
                    $v['dkmoney'] = $ndorder['cerditmoney'];
                    break;//活动
                case 'hotel':

                    $goods          = pdo_get('wlmerchant_hotel_room' , ['id' => $v['goods_id']] , ['roomtype','name','price','isdistri' , 'thumb' , 'id']);
                    $v['goodsname'] = $goods['name'];
                    $v['isdistri']  = $goods['isdistri'];
                    $v['goodsimg']  = tomedia($goods['thumb']);
                    //其他信息处理
                    $v['actualprice'] = $v['price'];
                    $v['plugintext'] = '酒店';
                    $v['plugincss'] = 'primary';
                    $v['detailurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$v['id'],'type'=>17));

                    $ndorder = pdo_get(PDO_NAME.'order', array('id'=>$v['id'],'uniacid'=>$_W['uniacid']),array('cerditmoney','vipdiscount','id','name','mobile','fightgroupid','payfor','plugin','fkid','specid','fightstatus','starttime','recordid','goodsprice','endtime','deposit','remark','buyremark'));
                    $v['price'] = $ndorder['goodsprice'];
                    $v['goodsprice'] = sprintf("%.2f",$ndorder['goodsprice']/$v['num']);
                    if($goods['roomtype'] == 3){
                        $v['starttime'] = date('m/d H:i',$ndorder['starttime']);
                        $v['endtime'] = date('m/d H:i',$ndorder['endtime']);
                    }else{
                        $v['starttime'] = date('y/m/d',$ndorder['starttime']);
                        $v['endtime'] = date('y/m/d',$ndorder['endtime']);
                    }

                    $v['deposit'] = $ndorder['deposit'];

                    //VIP折扣
                    if ($v['vipbuyflag']) {
                        if($ndorder['vipdiscount'] > 0){
                            $v['vipdiscount'] = $ndorder['vipdiscount'];
                        }
                    }
                    $v['buyremark'] = '入住时间'.$ndorder['buyremark'];
                    $v['remark'] = $ndorder['remark'];
                    $v['dkmoney'] = $ndorder['cerditmoney'];
                    break;//酒店
            }
            //代理名称
            if($v['aid'] > 0 ){
                $v['agentname'] = pdo_getcolumn(PDO_NAME.'agentusers', array('id'=>$v['aid'],'uniacid'=>$_W['uniacid']),'agentname');
            }else{
                $v['agentname'] = '总后台';
            }
            //商户名称
            $v['merchantName'] = pdo_getcolumn(PDO_NAME.'merchantdata', array('id'=>$v['sid'],'uniacid'=>$_W['uniacid']),'storename');
            //用户数据
            $member = pdo_get(PDO_NAME.'member', array('id'=>$v['mid']),array('nickname','encodename','avatar','mobile','realname'));
            if(!empty($member['encodename'])){
                $member['nickname'] = base64_decode($member['encodename']);
            }
            if(!empty($v['username'])){
                $member['realname'] = $v['username'];
            }
            if(!empty($v['mobile'])){
                $member['mobile'] = $v['mobile'];
            }
            $v['member'] = $member;
            //快递信息获取
            if($v['expressid'] && $v['plugin'] != 'citydelivery'){
                $v['express'] = pdo_get(PDO_NAME.'express',array('id'=>$v['expressid']),array('id','expressprice','expressname','expresssn'));
            }
            $v['merchname'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$v['sid']),'storename');
            //分销信息获取
            if($v['disorderid']){
                $disorder = pdo_get('wlmerchant_disorder',array('id' => $v['disorderid']));
                $v['disorderstatus'] = $disorder['status'];
                $leadmoney = unserialize($disorder['leadmoney']);
                if($disorder['twoleadid']){
                    $v['level'] = '2';
                }else{
                    $v['level'] = '1';
                }
                $v['onecommission'] = $leadmoney['one'];
                $onecom = pdo_get(PDO_NAME.'distributor',array('id'=>$disorder['oneleadid']),array('nickname','mid'));
                $v['onecomname'] = $onecom['nickname'];
                $v['onecommid'] = $onecom['mid'];
                $v['twocommission'] = $leadmoney['two'];
                $twocom = pdo_get(PDO_NAME.'distributor',array('id'=>$disorder['twoleadid']),array('nickname','mid'));
                $v['twocomname'] = $twocom['nickname'];
                $v['twocommid'] = $twocom['mid'];
                $v['commission'] = sprintf("%.2f",$v['onecommission']+$v['twocommission']);
				if($disorder['onegroupid'] > 0){
					$onegroup = pdo_get(PDO_NAME.'distributor',array('mid'=>$disorder['onegroupid']),array('nickname','mid'));
					$v['onegroupname'] = $onegroup['nickname'];
                	$v['onegroupmid'] = $onegroup['mid'];
               		$v['onegroupmoney'] = $disorder['onegroupmoney'];
				}
				if($disorder['twogroupid'] > 0){
					$twogroup = pdo_get(PDO_NAME.'distributor',array('mid'=>$disorder['twogroupid']),array('nickname','mid'));
					$v['twogroupname'] = $twogroup['nickname'];
                	$v['twogroupmid'] = $twogroup['mid'];
               		$v['twogroupmoney'] = $disorder['twogroupmoney'];
				}
				$v['shareholdermoney'] = $disorder['shareholdermoney'];
            }
            //同时开通一卡通
            if($v['vip_card_id'] > 0){
                $v['vipCardPrice'] = pdo_getcolumn(PDO_NAME.'halfcard_type',array('id'=>$v['vip_card_id']),'price');
            }
            //售后
            $v['refundflag'] = pdo_getcolumn(PDO_NAME.'aftersale',array('uniacid'=>$_W['uniacid'],'status'=>1,'orderno'=>$v['orderno']),'id');
            //混合支付
            if($v['blendcredit'] > 0){
                $v['paytype'] = 7;
                $v['blendwx'] = sprintf("%.2f",$v['actualprice'] - $v['blendcredit']);
            }
            //状态
            switch ($v['status']){
                case '0':
                    $v['statuscss'] = 'defualt';
                    $v['statustext'] = '未支付';
                    break;
                case '1':
                    $v['statuscss'] = 'info';
                    $v['statustext'] = '已支付';
                    break;
                case '2':
                    $v['statuscss'] = 'success';
                    $v['statustext'] = '待评价';
                    break;
                case '3':
                    $v['statuscss'] = 'success';
                    $v['statustext'] = '已完成';
                    break;
                case '4':
                    $v['statuscss'] = 'success';
                    $v['statustext'] = '待收货';
                    break;
                case '5':
                    $v['statuscss'] = 'defualt';
                    $v['statustext'] = '已取消';
                    break;
                case '6':
                    $v['statuscss'] = 'warning';
                    $v['statustext'] = '待退款';
                    break;
                case '7':
                    $v['statuscss'] = 'defualt';
                    $v['statustext'] = '已退款';
                    break;
                case '8':
                    $v['statuscss'] = 'info';
                    $v['statustext'] = '待发货';
                    break;
                case '9':
                    $v['statuscss'] = 'danger';
                    $v['statustext'] = '已过期';
                    break;
                case '10':
                    $v['statuscss'] = 'info';
                    $v['statustext'] = '组团中';
                    break;
                default:
                    $v['statuscss'] = 'danger';
                    $v['statustext'] = '错误状态';
                    break;
            }
        }
        #4、获取总共的统计信息
        $orderStatistics     = pdo_fetch("SELECT COUNT(id) as order_total,SUM(num) as goods_total,SUM(price) as price_total FROM " . $orderT . $orderW );
        $rushOrderStatistics = pdo_fetch('SELECT COUNT(id) as order_total,SUM(num) as goods_total,SUM(actualprice) as price_total FROM ' . $rushOrderT . $where);
        $total               = $orderStatistics['order_total'] + $rushOrderStatistics['order_total'];
        $goodtotal           = $orderStatistics['goods_total'] + $rushOrderStatistics['goods_total'];
        $allprice            = sprintf("%.2f" , $orderStatistics['price_total'] + $rushOrderStatistics['price_total']);
        #5、分页
        $pager = wl_pagination($total, $pindex, $psize);


        /*#1、获取基本参数信息
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = " where uniacid = {$_W['uniacid']} and orderno != 666666";
        $where2 = " where a.uniacid = {$_W['uniacid']} and a.orderno != 666666";
        if(is_agent()){
            $where .= " and aid = {$_W['aid']}";
            $where2 .= " and a.aid = {$_W['aid']}";
        }
        if(is_store()){
            $where .= " and sid = {$_W['storeid']}";
            $where2 .= " and a.sid = {$_W['storeid']}";
        }

        //状态
        if($_GPC['status']){
            if($_GPC['status'] == 'zero'){
                $where .= " and status = 0";
                $where2 .= " and a.status = 0";
            }else if(intval($_GPC['status']) == 10){
                $where .= " and applyrefund = 1 and status IN (1,8)";
                $where2 .= " and a.applyrefund = 1 and a.status IN (1,8)";
            }else if(intval($_GPC['status']) == 11){
                $where .= " and status IN (1,2,3,4,6,8,9)";
                $where2 .= " and a.status IN (1,2,3,4,6,8,9)";
            }else if(intval($_GPC['status']) == 12){
                $where .= " and status IN (2,3)";
                $where2 .= " and a.status IN (2,3)";
            }else if(intval($_GPC['status']) == 13){
                $where .= " and status IN  (0,1,2,3,4,6,8,9)";
                $where2 .= " and a.status IN  (0,1,2,3,4,6,8,9)";
            }else{
                $where .= " and status = {$_GPC['status']} ";
                $where2 .= " and a.status = {$_GPC['status']} ";
            }
        }
        //支付方式
        if($_GPC['paytype']){
            $where .= " and paytype = {$_GPC['paytype']} ";
            $where2 .= " and a.paytype = {$_GPC['paytype']} ";
        }
        if($_GPC['keyword']){
            $keyword = $_GPC['keyword'];
            if($_GPC['keywordtype'] == 1){
                $where .= " and orderno LIKE '%{$keyword}%'";
                $where2 .= " and a.orderno LIKE '%{$keyword}%'";
            }else if($_GPC['keywordtype'] == 3){
                $where .= " and mid = '{$keyword}'";
                $where2 .= " and a.mid = '{$keyword}'";
            }else if($_GPC['keywordtype'] == 4){
                $where .= " and sid = '{$keyword}'";
                $where2 .= " and a.sid = '{$keyword}'";
            }else if($_GPC['keywordtype'] == 5){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where .= " and mid IN {$mids}";
                    $where2 .= " and a.mid IN {$mids}";
                }
            }else if($_GPC['keywordtype'] == 6){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']} AND mobile LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";

                    $where .= " and (mid IN {$mids} OR mobile LIKE '%{$keyword}%') ";
                    $where2 .= " and  (a.mid IN {$mids} OR a.mobile LIKE '%{$keyword}%') ";
                }else{
                    //可以通过当前订单所使用的手机进行查询
                    $where .= " and mobile LIKE '%{$keyword}%' ";
                    $where2 .= " and a.mobile LIKE '%{$keyword}%' ";
                }

            }else if($_GPC['keywordtype'] == 7){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_merchantdata')." WHERE uniacid = {$_W['uniacid']} AND storename LIKE :name",$params);
                    if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where .= " and sid IN {$mids}";
                    $where2 .= " and a.sid IN {$mids}";
                }
            }else if($_GPC['keywordtype'] == 8){
                //核销码
                $smorder = pdo_get(PDO_NAME.'smallorder',array('checkcode'=>$keyword),array('orderid','plugin'));
                if($smorder){
                    if($smorder['plugin'] == 'rush'){
                        $where .= " AND id = {$smorder['orderid']} ";
                        $where2 .= " AND a.id = 0";
                    }else{
                        $where .= " AND id = 0 ";
                        $where2 .= " AND a.id = {$smorder['orderid']}";
                    }
                }else{
                    $where .= " AND checkcode = {$keyword} ";
                    $where2 .= " AND (f.qrcode = {$keyword} OR g.qrcode = {$keyword} OR `c`.concode = {$keyword} OR b.qrcode = {$keyword}) ";
                }
            }else if($_GPC['keywordtype'] == 9){
                $keyword = "'%{$keyword}%'";
                $where .= " AND mobile like {$keyword} ";
                $where2 .= " AND a.mobile like {$keyword} ";

            }else if($_GPC['keywordtype'] == 10){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_merchantdata')." WHERE uniacid = {$_W['uniacid']} AND salesmid LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where .= " and sid IN {$mids}";
                    $where2 .= " and a.sid IN {$mids}";
                }
            }

        }

        //时间
        if (!empty($_GPC['time_limit']) && $_GPC['timetype'] ) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            if($_GPC['timetype'] == 1){
                $where .= " and createtime > {$starttime} and createtime < {$endtime}";
                $where2 .= " and a.createtime > {$starttime} and a.createtime < {$endtime}";
            }else{
                $where .= " and paytime > {$starttime} and paytime < {$endtime}";
                $where2 .= " and a.paytime > {$starttime} and a.paytime < {$endtime}";
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }


        //插件
        if($_GPC['plugin']){
            $plugin = $_GPC['plugin'];
            //关键字
            if($plugin == 'rush'){
                $where2 .= " AND  a.plugin IN ('null') ";
            }else{
                $where2 .= " AND  a.plugin = '{$plugin}' ";
                $where .= " and orderno = 00000 ";
            }
        }else{
            $where2 .= " AND  a.plugin IN ('wlfightgroup','groupon','coupon','bargain') ";
        }

        if($_GPC['keywordtype'] == 2 && $_GPC['keyword']){
            $where .= " and activityid = {$keyword} ";
            $where2 .= " and  a.fkid = {$keyword} ";
        }
        //拼团
        if($_GPC['fightgroupid']){
            $where2 .= " and  a.fightgroupid = {$_GPC['fightgroupid']} ";
        }
        //导出
        if($_GPC['export']){
            $this -> export($where,$where2);
        }
        //统计与分页
        $total1 = pdo_fetchcolumn("SELECT COUNT(a.id) FROM ".tablename(PDO_NAME."order")
            ." a LEFT JOIN ".tablename(PDO_NAME."fightgroup_userecord")." f ON a.id = f.orderid AND a.plugin = 'wlfightgroup'"
            ." LEFT JOIN ".tablename(PDO_NAME."groupon_userecord")." g ON a.id = g.orderid AND a.plugin = 'groupon'"
            ." LEFT JOIN ".tablename(PDO_NAME."member_coupons")." `c` ON a.orderno = `c`.orderno AND a.plugin = 'coupon'"
            ." LEFT JOIN ".tablename(PDO_NAME."bargain_userlist")." b ON a.id = b.orderid AND a.plugin = 'bargain' {$where2}");
        $total2 = pdo_fetchcolumn('SELECT COUNT(id) FROM '.tablename(PDO_NAME."rush_order")." {$where}");
        $total = $total1+$total2;

        $goodtotal1 = pdo_fetchcolumn("SELECT SUM(a.num) FROM ".tablename(PDO_NAME."order")
            ." a LEFT JOIN ".tablename(PDO_NAME."fightgroup_userecord")." f ON a.id = f.orderid AND a.plugin = 'wlfightgroup'"
            ." LEFT JOIN ".tablename(PDO_NAME."groupon_userecord")." g ON a.id = g.orderid AND a.plugin = 'groupon'"
            ." LEFT JOIN ".tablename(PDO_NAME."member_coupons")." `c` ON a.orderno = `c`.orderno AND a.plugin = 'coupon'"
            ." LEFT JOIN ".tablename(PDO_NAME."bargain_userlist")." b ON a.id = b.orderid AND a.plugin = 'bargain' {$where2}");
        $goodtotal2 = pdo_fetchcolumn('SELECT SUM(num) FROM '.tablename(PDO_NAME."rush_order")." {$where}");
        $goodtotal = $goodtotal1+$goodtotal2;

        //获取总营业额
        $allprice1 = pdo_fetchcolumn("SELECT SUM(a.price) FROM ".tablename(PDO_NAME."order")
            ." a LEFT JOIN ".tablename(PDO_NAME."fightgroup_userecord")." f ON a.id = f.orderid AND a.plugin = 'wlfightgroup'"
            ." LEFT JOIN ".tablename(PDO_NAME."groupon_userecord")." g ON a.id = g.orderid AND a.plugin = 'groupon'"
            ." LEFT JOIN ".tablename(PDO_NAME."member_coupons")." `c` ON a.orderno = `c`.orderno AND a.plugin = 'coupon'"
            ." LEFT JOIN ".tablename(PDO_NAME."bargain_userlist")." b ON a.id = b.orderid AND a.plugin = 'bargain' {$where2}");
        $allprice2 = pdo_fetchcolumn('SELECT SUM(actualprice) FROM '.tablename(PDO_NAME."rush_order")." {$where}");
        $allprice = sprintf("%.2f",$allprice1+$allprice2);
        //获取订单信息
        $limit = " LIMIT ".($pindex - 1) * $psize . ',' . $psize;
        $orderlist = self::getOrderInfo($where,$where2,$limit);
        foreach ($orderlist as $key => &$v) {
            if($v['a'] == 'a'){
                $ndorder = pdo_get(PDO_NAME.'order', array('id'=>$v['id'],'uniacid'=>$_W['uniacid']),array('id','name','mobile','fightgroupid','payfor','plugin','fkid','specid','fightstatus','expressid','recordid','goodsprice','card_fee','remark','buyremark'));
                //修改支付状态
                if(empty($v['paytype'])){
                     $paylog = pdo_get('wlmerchant_paylog',array('tid' => $v['orderno']),array('type'));
                    $v['paytype'] = $paylog['type'];
                    pdo_update('wlmerchant_order',array('paytype' => $paytype),array('id' => $v['id']));
                }
                if($ndorder['plugin'] == 'coupon'){ //卡券
                    $goods = wlCoupon::getSingleCoupons($ndorder['fkid'],'title,logo,id,isdistri');
                    $v['goodsname'] = $goods['title'];
                    $v['isdistri'] = $goods['isdistri'];
                    $v['unit'] = '张';
                    $v['goodsimg'] = tomedia($goods['logo']);
                    $v['actualprice'] = $v['price'];
                    $v['price'] = $ndorder['goodsprice'] ;
                    $v['goodsprice'] = sprintf("%.2f",$ndorder['goodsprice']/$v['num']);
                    $v['plugintext'] = '卡券';
                    $v['plugincss'] = 'danger';
                    $v['detailurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$v['id'],'type'=>3));
                }else if($ndorder['plugin'] == 'wlfightgroup') {  //拼团
                    $goods = Wlfightgroup::getSingleGood($ndorder['fkid'],'name,logo,id,unit,vipdiscount,isdistri');
                    $group = pdo_get('wlmerchant_fightgroup_group',array('id' => $ndorder['fightgroupid']));
                    if($group['status'] == 1 && $v['status'] == 1){
                        $v['status'] = 10;
                    }
                    $v['goodsname'] = $goods['name'];
                    $v['isdistri'] = $goods['isdistri'];
                    $v['unit'] = $goods['unit'];
                    $v['goodsimg'] = tomedia($goods['logo']);
                    $v['actualprice'] = $v['price'];
                    $v['price'] = $ndorder['goodsprice'] ;
                    $v['goodsprice'] = sprintf("%.2f",$ndorder['goodsprice']/$v['num']);
                    $v['plugintext'] = '拼团';
                    $v['plugincss'] = 'warning';
                    $v['detailurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$v['id'],'type'=>2));

                    if($ndorder['specid']){
                        $v['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$ndorder['specid']),'title');
                    }
                    //会员减免
                    if($v['vipbuyflag']){
                        $v['vipdiscount'] = sprintf("%.2f",$goods['vipdiscount']*$v['num']);
                    }
                    //积分抵扣
                    $v['dkmoney'] = sprintf("%.2f",$ndorder['card_fee']-$v['vipdiscount']);

                }else if($ndorder['plugin'] == 'groupon') { //团购
                    $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $ndorder['fkid']),array('name','isdistri','thumb','id','unit'));
                    $v['goodsname'] = $goods['name'];
                    $v['isdistri'] = $goods['isdistri'];
                    $v['unit'] = $goods['unit'];
                    $v['goodsimg'] = tomedia($goods['thumb']);
                    $v['actualprice'] = $v['price'];
                    $v['price'] = $ndorder['goodsprice'] ;
                    $v['goodsprice'] = sprintf("%.2f",$ndorder['goodsprice']/$v['num']);
                    $v['plugintext'] = '团购';
                    $v['plugincss'] = 'info';
                    $v['detailurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$v['id'],'type'=>10));
                    if($ndorder['specid']){
                        $v['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$ndorder['specid']),'title');
                    }
                }else if($ndorder['plugin'] == 'bargain') { //砍价
                    $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $ndorder['fkid']),array('name','isdistri','thumb','id','unit'));
                    $v['goodsname'] = $goods['name'];
                    $v['isdistri'] = $goods['isdistri'];
                    $v['unit'] = $goods['unit'];
                    $v['goodsimg'] = tomedia($goods['thumb']);
                    $v['actualprice'] = $v['price'];
                    $v['price'] = $ndorder['goodsprice'] ;
                    $v['goodsprice'] = sprintf("%.2f",$ndorder['goodsprice']/$v['num']);
                    $v['plugintext'] = '砍价';
                    $v['plugincss'] = 'primary';
                    $v['detailurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$v['id'],'type'=>12));
                }

                $v['expressid'] = $ndorder['expressid'];
                $v['remark'] = $ndorder['remark'];
                $v['buyremark'] = $ndorder['buyremark'];
                $v['username'] = $ndorder['name'];
                $v['mobile'] = $ndorder['mobile'];
                $v['goodsid'] = $ndorder['fkid'];
            }else{ //抢购
                $rushorder = pdo_get(PDO_NAME.'rush_order', array('id'=>$v['id'],'uniacid'=>$_W['uniacid']),array('activityid','username','mobile','optionid','dkmoney','actualprice','remark','adminremark','expressid'));
                //修改支付状态
                if(empty($v['paytype'])){
                    $paylog = pdo_get('wlmerchant_paylog',array('tid' => $v['orderno']),array('type'));
                    $v['paytype'] = $paylog['type'];
                    pdo_update('wlmerchant_rush_order',array('paytype' => $paytype),array('id' => $v['id']));
                }
                $v['activityid'] = pdo_getcolumn(PDO_NAME.'rush_order', array('id'=>$v['id'],'uniacid'=>$_W['uniacid']),'activityid');
                $goods = Rush::getSingleActive($v['activityid'],'name,thumb,id,isdistri,cutofftime,unit');
                $v['unit'] = $goods['unit'];
                $v['isdistri'] = $goods['isdistri'];
                $v['mobile'] = $goods['mobile'];
                $v['goodsimg'] = tomedia($goods['thumb']);
                $v['goodsname'] = $goods['name'];

                $v['goodsprice'] = sprintf("%.2f",$v['price']/$v['num']);
                $v['actualprice'] = $rushorder['actualprice'];
                $v['plugin'] = 'rush';
                $v['plugintext'] = '抢购';
                $v['plugincss'] = 'success';
                $v['detailurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$v['id'],'type'=>1));
                if($rushorder['optionid']){
                    $v['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$rushorder['optionid']),'title');
                }
                $v['remark'] = $rushorder['adminremark'];
                $v['buyremark'] = $rushorder['remark'];

                $v['expressid'] = $rushorder['expressid'];
                //积分抵扣
                $v['dkmoney'] = $rushorder['dkmoney'];
                //用户信息
                $v['username'] = $rushorder['username'];
                $v['mobile'] = $rushorder['mobile'];
                $v['goodsid'] = $rushorder['activityid'];
            }

            $v['merchantName'] = pdo_getcolumn(PDO_NAME.'merchantdata', array('id'=>$v['sid'],'uniacid'=>$_W['uniacid']),'storename');
            //用户数据
            $member = pdo_get(PDO_NAME.'member', array('id'=>$v['mid']),array('nickname','avatar','mobile','realname'));
            if(!empty($v['username'])){
                $member['realname'] = $v['username'];
            }
            if(!empty($v['mobile'])){
                $member['mobile'] = $v['mobile'];
            }

            $v['member'] = $member;
            if($v['expressid']){
                $v['express'] = pdo_get(PDO_NAME.'express',array('id'=>$v['expressid']),array('id','expressprice','expressname','expresssn'));
            }
            if($v['disorderid']){
                $v['merchname'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$v['sid']),'storename');
                $disorder = pdo_get('wlmerchant_disorder',array('id' => $v['disorderid']));
                $v['disorderstatus'] = $disorder['status'];
                $leadmoney = unserialize($disorder['leadmoney']);
                if($disorder['twoleadid']){
                    $v['level'] = '2';
                }else{
                    $v['level'] = '1';
                }
                $v['onecommission'] = $leadmoney['one'];
                $onecom = pdo_get(PDO_NAME.'distributor',array('id'=>$disorder['oneleadid']),array('nickname','mid'));
                $v['onecomname'] = $onecom['nickname'];
                $v['onecommid'] = $onecom['mid'];
                $v['twocommission'] = $leadmoney['two'];
                $twocom = pdo_get(PDO_NAME.'distributor',array('id'=>$disorder['twoleadid']),array('nickname','mid'));
                $v['twocomname'] = $twocom['nickname'];
                $v['twocommid'] = $twocom['mid'];
                $v['commission'] = sprintf("%.2f",$v['onecommission']+$v['twocommission']);
            }
            //售后
            $v['refundflag'] = pdo_getcolumn(PDO_NAME.'aftersale',array('uniacid'=>$_W['uniacid'],'status'=>1,'orderno'=>$v['orderno']),'id');


            //状态
            switch ($v['status']){
                case '0':
                    $v['statuscss'] = 'defualt';
                    $v['statustext'] = '未支付';
                    break;
                case '1':
                    $v['statuscss'] = 'info';
                    $v['statustext'] = '已支付';
                    break;
                case '2':
                    $v['statuscss'] = 'success';
                    $v['statustext'] = '待评价';
                    break;
                case '3':
                    $v['statuscss'] = 'success';
                    $v['statustext'] = '已完成';
                    break;
                case '4':
                    $v['statuscss'] = 'success';
                    $v['statustext'] = '待收货';
                    break;
                case '5':
                    $v['statuscss'] = 'defualt';
                    $v['statustext'] = '已取消';
                    break;
                case '6':
                    $v['statuscss'] = 'warning';
                    $v['statustext'] = '待退款';
                    break;
                case '7':
                    $v['statuscss'] = 'defualt';
                    $v['statustext'] = '已退款';
                    break;
                case '8':
                    $v['statuscss'] = 'info';
                    $v['statustext'] = '待发货';
                    break;
                case '9':
                    $v['statuscss'] = 'danger';
                    $v['statustext'] = '已过期';
                    break;
                case '10':
                    $v['statuscss'] = 'info';
                    $v['statustext'] = '组团中';
                    break;
                default:
                    $v['statuscss'] = 'danger';
                    $v['statustext'] = '错误状态';
                    break;
            }
        }
        $pager = wl_pagination($total, $pindex, $psize);*/
        include  wl_template('order/orderlist');
    }

    public function export($where,$where2,$diyformid = 0){
        global $_W, $_GPC;
        $limit = " LIMIT 20000";
        $orderlist = self::getOrderInfo($where,$where2,$limit);
        foreach ($orderlist as $key => &$va) {
            if($va['a'] == 'a'){
                $ndorder = pdo_get(PDO_NAME.'order', array('id'=>$va['id'],'uniacid'=>$_W['uniacid']),array('id','payfor','plugin','name','address','mobile','orderno','fkid','fightstatus','neworderflag','expressid','recordid','goodsprice','expressprcie','card_fee','remark','buyremark','specid'));
                if($ndorder['plugin'] == 'wlfightgroup'){
                    $va['plugin'] = '拼团';
                    $va['gname'] = pdo_getcolumn(PDO_NAME.'fightgroup_goods',array('id'=>$ndorder['fkid']),'name');
                    if($ndorder['specid']){
                        $va['option'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$ndorder['specid']),'title');
                    }
                    if($ndorder['recordid']){
                        $va['usedtime'] = pdo_getcolumn(PDO_NAME.'fightgroup_userecord',array('id'=>$ndorder['recordid']),'usedtime');
                        $va['checkcode'] = pdo_getcolumn(PDO_NAME.'fightgroup_userecord',array('id'=>$ndorder['recordid']),'qrcode');
                    }
                }else if($ndorder['plugin'] == 'groupon'){
                    $va['plugin'] = '团购';
                    $va['gname'] = pdo_getcolumn(PDO_NAME.'groupon_activity',array('id'=>$ndorder['fkid']),'name');
                    if($ndorder['specid']){
                        $va['option'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$ndorder['specid']),'title');
                    }
                    if($ndorder['recordid']){
                        $va['usedtime'] = pdo_getcolumn(PDO_NAME.'groupon_userecord',array('id'=>$ndorder['recordid']),'usedtime');
                        $va['checkcode'] = pdo_getcolumn(PDO_NAME.'groupon_userecord',array('id'=>$ndorder['recordid']),'qrcode');
                    }
                }else if($ndorder['plugin'] == 'activity'){
                    $va['plugin'] = '活动';
                    $va['gname'] = pdo_getcolumn(PDO_NAME.'activitylist',array('id'=>$ndorder['fkid']),'title');
                    if($ndorder['specid']){
                        $va['option'] = pdo_getcolumn(PDO_NAME.'activity_spec',array('id'=>$ndorder['specid']),'name');
                    }
                }else if($ndorder['plugin'] == 'coupon'){
                    $va['plugin'] = '卡券';
                    $va['gname'] = pdo_getcolumn(PDO_NAME.'couponlist',array('id'=>$ndorder['fkid']),'title');
                    if($ndorder['recordid']){
                        $va['usedtime'] = pdo_getcolumn(PDO_NAME.'member_coupons',array('id'=>$ndorder['recordid']),'usedtime');
                        $va['checkcode'] = pdo_getcolumn(PDO_NAME.'member_coupons',array('id'=>$ndorder['recordid']),'concode');
                    }
                }else if($ndorder['plugin'] == 'bargain'){
                    $va['plugin'] = '砍价';
                    $va['gname'] = pdo_getcolumn(PDO_NAME.'bargain_activity',array('id'=>$ndorder['fkid']),'name');
                    $va['usedtime'] = pdo_getcolumn(PDO_NAME.'bargain_userlist',array('id'=>$ndorder['specid']),'usedtime');
                    $va['checkcode'] = pdo_getcolumn(PDO_NAME.'bargain_userlist',array('id'=>$ndorder['specid']),'qrcode');
                }else if($ndorder['plugin'] == 'citydelivery'){
                    $va['plugin'] = '同城配送';
                    $smallorders = pdo_fetchall("SELECT gid,money,num,specid FROM ".tablename('wlmerchant_delivery_order')."WHERE tid = {$ndorder['orderno']} ORDER BY price DESC");
                    $va['gname'] = '';
                    foreach ($smallorders  as $ke => &$orr){
                        $goods = pdo_get('wlmerchant_delivery_activity',array('id' => $orr['gid']),array('name','thumb'));
                        $orr['name'] = $goods['name'];
                        if($orr['specid']>0){
                            $specname = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$orr['specid']),'name');
                            $orr['name'] .= '/'.$specname;
                        }
                        $va['gname'] .= '['.$orr['name'].'X'.$orr['num'].']';
                    }
                }
                $va['remark'] = $ndorder['buyremark'];
               
				if($ndorder['expressid']){
                    $va['expressid'] = $ndorder['expressid'];
                }else{
                    $va['peoplename'] = $ndorder['name'];
                    $va['address'] = $ndorder['address'];
                }
				
                $va['mobile'] = $ndorder['mobile'];
				$va['tel'] = $ndorder['mobile'];

                $va['remark'] = $ndorder['remark'];
                $va['buyremark'] = $ndorder['buyremark'];

                $va['small']  = pdo_getall('wlmerchant_smallorder',array('orderid' => $ndorder['id'],'plugin'=>$ndorder['plugin']),'','','status ASC,hexiaotime ASC');
                $va['neworderflag'] = $ndorder['neworderflag'];
                $va['expressprice'] = $ndorder['expressprcie'];
            }else{
                $rushorder = pdo_get(PDO_NAME.'rush_order', array('id'=>$va['id'],'uniacid'=>$_W['uniacid']),array('actualprice','expressid','neworderflag','remark','adminremark','usedtime','optionid','checkcode','adminremark','activityid','mobile','address','username'));
                $va['plugin'] = '抢购';
                $va['gname'] = pdo_getcolumn(PDO_NAME.'rush_activity',array('id'=>$rushorder['activityid']),'name');
                $va['usedtime'] = $rushorder['usedtime'];
                $va['checkcode'] = $rushorder['checkcode'];
                if($rushorder['optionid']){
                    $va['option'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$rushorder['optionid']),'title');
                }
                if($rushorder['expressid']){
                    $va['expressid'] = $rushorder['expressid'];
                }else{
                    $va['peoplename'] = $rushorder['username'];
                    $va['tel'] = $rushorder['mobile'];
                    $va['address'] = $rushorder['address'];
                    $va['mobile'] = $rushorder['mobile'];
                }
                $va['remark'] = $rushorder['adminremark'];
                $va['buyremark'] = $rushorder['remark'];
                $va['small'] = pdo_getall('wlmerchant_smallorder',array('orderid' => $va['id'],'plugin'=>'rush'),'','','status ASC,hexiaotime ASC');
                $va['neworderflag'] = $rushorder['neworderflag'];
            }
            if($va['blendcredit']>0){
                $va['paytype'] = 7;
            }
            $va['merchantName'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$va['sid']),'storename');
            $va['salesmid'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$va['sid']),'salesmid');
            $member = pdo_get('wlmerchant_member',array('id' => $va['mid']),array('nickname','realname','mobile'));
            $va['nickname'] = $member['nickname'];
            if(!empty($member['realname'])){
                $va['nickname'] = $va['nickname']."(".$member['realname'].")";
            }
            if(empty($va['mobile'])){
                $va['mobile'] = $member['mobile'];
            }
            if($va['expressid']){
                if($va['plugin'] == '同城配送'){
                    $express = pdo_get('wlmerchant_address',array('id' => $va['expressid']),array('name','tel','province','city','county','detailed_address'));
                    $va['peoplename'] = $express['name'];
                    $va['tel'] = $express['tel'];
                    $va['address'] = $express['province'].$express['city'].$express['county'].$express['detailed_address'];
                }else{
                    $express = pdo_get('wlmerchant_express',array('id' => $va['expressid']),array('name','tel','expressprice','address','expressname','expresssn'));
                    $va['peoplename'] = $express['name'];
                    $va['tel'] = $express['tel'];
                    $va['address'] = $express['address'];
                    $va['expressname'] = $express['expressname'];
                    $va['expresssn'] = $express['expresssn'];
                    if($va['expressprice'] < 0.01){
                        $va['expressprice'] = $express['expressprice'];
                    }
                    $va['expressprice'] = sprintf("%.2f",$va['expressprice'] + $va['changedispatchprice']);
                }

            }
            //分销
            if($va['disorderid']){
                $disorder = pdo_get('wlmerchant_disorder',array('id' => $va['disorderid']));
                $leadmoney = unserialize($disorder['leadmoney']);
                $va['onecommission'] = $leadmoney['one'];
                $onecom = pdo_get(PDO_NAME.'distributor',array('id'=>$disorder['oneleadid']),array('nickname','mid'));
                $va['onecomname'] = $onecom['nickname'];
                $va['onecommid'] = $onecom['mid'];
                $va['twocommission'] = $leadmoney['two'];
                $twocom = pdo_get(PDO_NAME.'distributor',array('id'=>$disorder['twoleadid']),array('nickname','mid'));
                $va['twocomname'] = $twocom['nickname'];
                $va['twocommid'] = $twocom['mid'];
                $va['commission'] = sprintf("%.2f",$va['onecommission']+$va['twocommission']);
            }

            $va['orderno'] = "\t".$va['orderno']."\t";
            if($diyformid > 0){
                $va['moinfo'] = unserialize($va['moinfo']);
                foreach ($va['moinfo'] as $zzw => $in){
                    if($in['id'] == 'checkbox' || $in['id'] == 'img'){
                        $va['zzw'.$zzw] = implode(",", $in['data']);
                    }else if($in['id'] == 'datetime' || $in['id'] == 'city'){
                        $va['zzw'.$zzw] = implode("-", $in['data']);
                    } else{
                        $va['zzw'.$zzw] = "\t".$in['data']."\t";
                    }
                }
            }

        }
        /* 输出表头 */
        $filter = array(
            'id'  => '订单id',//U
            'orderno' => '订单号',//A
            'plugin'  => '所属应用',//B
            'gname' => '商品名称',//C
            'option' => '规格名称',//D
            'num' => '数量',//E
            'merchantName' => '所属商家',//F
            'nickname' => '买家昵称',//G
            'mobile' => '买家电话',//H
            'status' => '订单状态',//I
            'paytype' => '支付方式',//J
            'createtime' => '下单时间',//K
            'paytime' => '支付时间',//L
            'price' => '实付金额',//M
            'buyremark' => '买家备注',//N
            'remark' => '卖家备注',//N
            'peoplename' => '收货人姓名',//O
            'tel' => '收货人电话',//P
            'address' => '收货人地址',//Q
            'expressname' => '物流公司',//R
            'expresssn' => '快递单号',//S
            'expressprice' => '快递运费',//S
            'checkcode'    => '核销码',
            'hexiaotime'  => '核销时间',//T
            'vermember'  => '核销员',//U
            'salesmid'   => '业务员',
            'commission' => '分销总佣金',
            'onecommission' => '一级分销佣金',
            'onecommid'     => '一级分销商MID',
            'onecomname' => '一级分销商昵称',
            'twocommission' => '二级分销佣金',
            'twocommid'     => '二级分销商MID',
            'twocomname' => '二级分销商昵称'
        );

        if($diyformid > 0){
            $diyforminfo = pdo_get('wlmerchant_diyform',array('id' => $diyformid),array('info'));
            $moinfo = json_decode(base64_decode($diyforminfo['info']) , true);
            $list = $moinfo['list'];
            $list = array_values($list);
            foreach ($list as $wlf => $li){
                $filter['zzw'.$wlf] = $li['data']['title'];
            }
        }

        $data = array();
        for ($i=0; $i < count($orderlist) ; $i++) {
            foreach ($filter as $key => $title) {
                $data[$i][$key] = $orderlist[$i]['id'];
                if ($key == 'createtime' || $key == 'paytime') {
                    $data[$i][$key] = date('Y-m-d H:i:s', $orderlist[$i][$key]);
                }else if($key == 'status') {
                    switch ($orderlist[$i][$key]) {
                        case '1':
                            $data[$i][$key] = '已支付';
                            break;
                        case '2':
                            $data[$i][$key] = '已消费';
                            break;
                        case '3':
                            $data[$i][$key]  = '已完成';
                            break;
                        case '4':
                            $data[$i][$key]  = '待收货';
                            break;
                        case '5':
                            $data[$i][$key]  = '已取消';
                            break;
                        case '6':
                            $data[$i][$key]  = '待退款';
                            break;
                        case '7':
                            $data[$i][$key]  = '已退款';
                            break;
                        case '8':
                            $data[$i][$key]  = '待发货';
                            break;
                        case '9':
                            $data[$i][$key]  = '已过期';
                            break;
                        default:
                            $data[$i][$key]  = '未支付';
                            break;
                    }
                }else if($key == 'paytype'){
                    switch ($orderlist[$i][$key]) {
                        case '1':
                            $data[$i][$key] = '余额支付';
                            break;
                        case '2':
                            $data[$i][$key] = '微信支付';
                            break;
                        case '3':
                            $data[$i][$key] = '支付宝支付';
                            break;
                        case '4':
                            $data[$i][$key] = '货到付款';
                            break;
                        case '5':
                            $data[$i][$key] = '小程序支付';
                            break;
                        case '6':
                            $data[$i][$key] = '0元购';
                            break;
                        case '7':
                            $data[$i][$key] = '混合支付';
                            break;
                        default:
                            $data[$i][$key]  = '其他或未支付';
                            break;
                    }
                }else if($key == 'checkcode'){
                    if($orderlist[$i]['neworderflag']){
                        $checkcode = '';
                        foreach ($orderlist[$i]['small'] as $kchcek => $sm){
                            if($kchcek != 0){
                                $checkcode .= '||'.$sm['checkcode'];
                            }else{
                                $checkcode .= $sm['checkcode'];
                            }
                        }
                        $data[$i][$key] = $checkcode;
                    }else{
                        $data[$i][$key] = $orderlist[$i][$key];
                    }

                }else if($key == 'hexiaotime'){
                    $usedrecord = '';
                    if($orderlist[$i]['neworderflag']) {
                        foreach ($orderlist[$i]['small'] as $ktime => $sm){
                            if($ktime != 0){
                                if($sm['status'] == 1){
                                    $usedrecord .=' || 未核销';
                                }else if($sm['status'] == 2){
                                    $usedrecord .=' || '.date('Y-m-d H:i:s',$sm['hexiaotime']);
                                }else if($sm['status'] == 3){
                                    $usedrecord .=' || '.date('Y-m-d H:i:s',$sm['refundtime']);
                                }
                            }else{
                                if($sm['status'] == 1){
                                    $usedrecord .='未核销';
                                }else if($sm['status'] == 2){
                                    $usedrecord .=date('Y-m-d H:i:s',$sm['hexiaotime']);
                                }else if($sm['status'] == 3){
                                    $usedrecord .=date('Y-m-d H:i:s',$sm['refundtime']);
                                }
                            }
                        }
                    }else{
                        $usedtime = unserialize($orderlist[$i]['usedtime']);
                        if($usedtime){
                            foreach ($usedtime as $kK => $used) {
                                if($kK != 0){
                                    $usedrecord .= ' || '.date('Y-m-d H:i:s',$used['time']);
                                }else {
                                    $usedrecord .= date('Y-m-d H:i:s',$used['time']);
                                }
                            }
                        }
                    }

                    $data[$i][$key] = $usedrecord;
                }else if($key == 'vermember'){
                    $vermembers = '';
                    if($orderlist[$i]['neworderflag']) {
                        foreach ($orderlist[$i]['small'] as $kuid => $sm){
                            if($sm['status'] == 1){
                                $verm = '未核销';
                            }else if($sm['status'] == 2){
                                $verm = pdo_getcolumn(PDO_NAME.'merchantuser',array('id'=>$sm['hxuid']),'name');
                            }else if($sm['status'] == 3){
                                $verm = '已退款';
                            }
                            if($kuid != 0){
                                $vermembers .= ' || '.$verm;
                            }else{
                                $vermembers .= $verm;
                            }
                        }
                    }else{
                        $usedtime = unserialize($orderlist[$i]['usedtime']);
                        if($usedtime){
                            foreach ($usedtime as $kKs => $user2) {
                                $user2['vername'] = pdo_getcolumn(PDO_NAME.'merchantuser',array('mid'=>$user2['ver']),'name');
                                if($user2['type'] == 3){
                                    $user2['vername'] = '后台核销';
                                }else if($user2['type'] == 4){
                                    $user2['vername'] = '密码核销';
                                }
                                if($kKs != 0){
                                    $vermembers .= ' || '.$user2['vername'];
                                }else {
                                    $vermembers .= $user2['vername'];
                                }
                            }
                        }
                    }

                    $data[$i][$key] = $vermembers;
                }else {
                    $data[$i][$key] = $orderlist[$i][$key];
                }

            }
        }
        util_csv::export_csv_2($data, $filter, '订单表.csv');
        exit();
    }

    public function orderdetail(){
        global $_W, $_GPC;
        $currentid = $_GPC['currentid'];
        $orderid  = $_GPC['orderid'];
        $type = $_GPC['type'];
        if($currentid){
            $current = pdo_get('wlmerchant_current',array('id' => $currentid),array('type','orderid'));
            $type = $current['type'];
            if($type == 140){
                $type = 14; //调整与礼包核销的参数冲突
            }
            if($type == 150){
                $type = 15; //调整与支付返现的参数冲突
            }
            $orderid = $current['orderid'];
        }

        if($type == 1){
            $order = pdo_get('wlmerchant_rush_order',array('id' => $orderid));
            $order['buyremark'] = $order['remark'];
            $order['remark'] = $order['adminremark'];
            $goodsprice = $order['price'];
            $order['ordera'] = 'b';
            if($order['optionid']){
                $order['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['optionid']),'title');
            }
            if($order['blendcredit'] > 0){
                $order['paytype'] = 7;
                $order['blendwx'] = sprintf("%.2f",$order['actualprice'] - $order['blendcredit']);
            }
        }else if($type == 4){
            $order = pdo_get('wlmerchant_halfcard_record',array('id' => $orderid));
            $goodsprice = $order['price'];
        }else{
            $order = pdo_get('wlmerchant_order',array('id' => $orderid));
            $goodsprice = $order['goodsprice'];
            $order['ordera'] = 'a';
            if($order['specid'] && $type != 12 && $type != 8){
                if($type == 9){
                    $order['optiontitle'] = pdo_getcolumn(PDO_NAME.'activity_spec',array('id'=>$order['specid']),'name');
                }else{
                    $order['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['specid']),'title');
                }
            }
            if($order['blendcredit'] > 0){
                $order['paytype'] = 7;
                $order['blendwx'] = sprintf("%.2f",$order['price'] - $order['blendcredit']);
            }
            $order['username'] = $order['name'];
            //核销码
            if($order['recordid']){
                if($order['plugin'] == 'groupon'){
                    $order['checkcode'] = pdo_getcolumn(PDO_NAME.'groupon_userecord',array('id'=>$order['recordid']),'qrcode');
                }else if($order['plugin'] == 'wlfightgroup'){
                    $order['checkcode'] = pdo_getcolumn(PDO_NAME.'fightgroup_userecord',array('id'=>$order['recordid']),'qrcode');
                }else if($order['plugin'] == 'bargain'){
                    $order['checkcode'] = pdo_getcolumn(PDO_NAME.'bargain_userlist',array('id'=>$order['recordid']),'qrcode');
                }
            }

        }
        if(empty($order['transid'])) {
            $order['transid'] = pdo_getcolumn(PDO_NAME . 'paylogvfour', array('tid' => $order['orderno']), 'transaction_id');
        }
        if(empty($order['transid'])){
            $order['transid'] = pdo_getcolumn(PDO_NAME.'paylog',array('tid'=>$order['orderno']),'transaction_id');
        }
        //        if($order['transid']){
        //            $order['wqorderno'] = pdo_getcolumn('core_paylog',array('uniacid'=>$_W['uniacid'],'tid'=>$order['orderno']),'uniontid');
        //        }

        //同步评价表
        switch ($type){
            case '1':
                $plugin = 'rush';
                break;
            case '2':
                $plugin = 'wlfightgroup';
                break;
            case '3':
                $plugin = 'coupon';
                break;
            case '9':
                $plugin = 'activity';
                break;
            case '10':
                $plugin = 'groupon';
                break;
            case '11':
                $plugin = 'halfcard';
                break;
            case '12':
                $plugin = 'bargain';
                break;
            case '14':
                $plugin = 'citydelivery';
                break;
            case '17':
                $plugin = 'hotel';
                break;
        }
        $member = pdo_get('wlmerchant_member',array('id' => $order['mid']),array('nickname','mobile','avatar','realname','id'));
        $order['avatar'] = $member['avatar'];
        switch ($order['status']){
            case '0':
                $order['statuscss'] = 'default';
                $order['statustext'] = '未支付';
                break;
            case '1':
                $order['statuscss'] = 'info';
                $order['statustext'] = '已支付';
                break;
            case '2':
                $order['statuscss'] = 'success';
                $order['statustext'] = '待评价';
                break;
            case '3':
                $order['statuscss'] = 'success';
                $order['statustext'] = '已完成';
                break;
            case '4':
                $order['statuscss'] = 'success';
                $order['statustext'] = '待收货';
                break;
            case '5':
                $order['statuscss'] = 'defualt';
                $order['statustext'] = '已取消';
                break;
            case '6':
                $order['statuscss'] = 'warning';
                $order['statustext'] = '待退款';
                break;
            case '7':
                $order['statuscss'] = 'defualt';
                $order['statustext'] = '已退款';
                break;
            case '8':
                $order['statuscss'] = 'info';
                $order['statustext'] = '待发货';
                break;
            case '9':
                $order['statuscss'] = 'danger';
                $order['statustext'] = '已过期';
                break;
            default:
                $order['statuscss'] = 'danger';
                $order['statustext'] = '错误状态';
                break;
        }

        switch ($order['paytype']){
            case '1':
                $order['paytypecss'] = 'info';
                $order['paytypetext'] = '余额支付';
                break;
            case '2':
                $order['paytypecss'] = 'success';
                $order['paytypetext'] = '微信支付';
                break;
            case '3':
                $order['paytypecss'] = 'danger';
                $order['paytypetext'] = '支付宝';
                break;
            case '4':
                $order['paytypecss'] = 'warning';
                $order['paytypetext'] = '货到付款';
                break;
            case '5':
                $order['paytypecss'] = 'warning';
                $order['paytypetext'] = '小程序支付';
                break;
            case '6':
                $order['paytypecss'] = 'danger';
                $order['paytypetext'] = '0元购';
                break;
            case '7':
                $order['paytypecss'] = 'danger';
                $order['paytypetext'] = '混合支付';
                break;
            default:
                $order['paytypecss'] = 'default';
                $order['paytypetext'] = '未知方式';
                break;
        }
        //额外信息
        if(!empty($order['moinfo'])){
            $moinfo = unserialize($order['moinfo']);

        }
        //订单日志
        $logs = array();
        $logs[] = array(   //下单
            'time'   =>  $order['createtime'],
            'title'  =>  '订单提交成功',
            'detail' =>  '单号:'.$order['orderno'].'，等待买家付款'
        );
        //抢购核销
        if($type == 1){
            $usedtime = unserialize($order['usedtime']);
            if($order['paytime']){
                $logs[] = array(   //支付
                    'time'   =>  $order['paytime'],
                    'title'  =>  '订单支付成功',
                    'detail' =>  '支付成功，等待买家前往商户核销订单或商家发货'
                );
            }

            if($order['expressid']){
                $express = pdo_get('wlmerchant_express',array('id' => $order['expressid']),array('expressname','expresssn','expressprice','sendtime','receivetime'));
                $expressprice = $express['expressprice'];
                if($express['sendtime']){
                    $logs[] = array(   //支付
                        'time'   =>  $express['sendtime'],
                        'title'  =>  '商品已发货',
                        'detail' =>  '快递公司:'.$express['expressname'].',快递单号:'.$express['expresssn'].',等待买家收货'
                    );
                }
                if($express['receivetime']){
                    $logs[] = array(   //支付
                        'time'   =>  $express['receivetime'],
                        'title'  =>  '商品已签收',
                        'detail' =>  '用户已签收商品，等待系统结算订单'
                    );
                }
            }


        }
        //拼团
        if($type == 2){
            //组团
            if($order['fightstatus'] == 1){
                if($order['paytime']){
                    $logs[] = array(   //支付
                        'time'   =>  $order['paytime'],
                        'title'  =>  '订单支付成功',
                        'detail' =>  '支付成功，等待组团成功'
                    );
                }


                $group = pdo_get('wlmerchant_fightgroup_group',array('id' => $order['fightgroupid']));
                if($group['status'] == 2){
                    $logs[] = array(   //支付
                        'time'   =>  $group['successtime'],
                        'title'  =>  '组团成功',
                        'detail' =>  '拼团组团成功，等待买家前往商户核销订单或商家发货'
                    );
                }else if($group['status'] == 3){
                    $logs[] = array(   //支付
                        'time'   =>  $group['failtime'],
                        'title'  =>  '组团失败',
                        'detail' =>  '拼团组团失败，即将给买家退款'
                    );
                }
            }else{
                if($order['paytime']){
                    $logs[] = array(   //支付
                        'time'   =>  $order['paytime'],
                        'title'  =>  '订单支付成功',
                        'detail' =>  '支付成功，等待买家前往商户核销订单或商家发货'
                    );
                }
            }

            if($order['recordid']){  //核销
                $record = pdo_get('wlmerchant_fightgroup_userecord',array('id' => $order['recordid']),array('usedtime'));
                $usedtime = unserialize($record['usedtime']);
            }else if($order['expressid']){
                $express = pdo_get('wlmerchant_express',array('id' => $order['expressid']),array('expressname','expresssn','expressprice','sendtime','receivetime'));
                $expressprice = $express['expressprice'];
                if($express['sendtime']){
                    $logs[] = array(   //支付
                        'time'   =>  $express['sendtime'],
                        'title'  =>  '商品已发货',
                        'detail' =>  '快递公司:'.$express['expressname'].',快递单号:'.$express['expresssn'].',等待买家收货'
                    );
                }
                if($express['receivetime']){
                    $logs[] = array(   //支付
                        'time'   =>  $express['receivetime'],
                        'title'  =>  '商品已签收',
                        'detail' =>  '用户已签收商品，等待系统结算订单'
                    );
                }

            }
        }

        //超级券
        if($type == 3){
            if($order['paytime']){
                $logs[] = array(   //支付
                    'time'   =>  $order['paytime'],
                    'title'  =>  '订单支付成功',
                    'detail' =>  '支付成功，等待买家前往商户核销订单'
                );
            }
            //核销
            $usedtime = pdo_getcolumn(PDO_NAME.'member_coupons',array('id'=>$order['recordid']),'usedtime');
            $usedtime = unserialize($usedtime);
        }
        //一卡通
        if($type == 4){
            if($order['paytime']){
                $logs[] = array(   //支付
                    'time'   =>  $order['paytime'],
                    'title'  =>  '订单支付成功',
                    'detail' =>  '支付成功，买家已成功开通会员，等待系统结算'
                );
            }
        }
        //付费发帖
        if($type == 5){
            if($order['paytime']){
                $logs[] = array(   //支付
                    'time'   =>  $order['paytime'],
                    'title'  =>  '订单支付成功',
                    'detail' =>  '支付成功，帖子已发布，等待系统结算'
                );
            }
        }
        //付费发帖
        if($type == 6){
            if($order['paytime']){
                $logs[] = array(   //支付
                    'time'   =>  $order['paytime'],
                    'title'  =>  '订单支付成功',
                    'detail' =>  '支付成功，商家已入住，等待审核，订单等待系统结算'
                );
            }
        }
        //付费成为分销商
        if($type == 8){
            if($order['paytime']){
                $logs[] = array(   //支付
                    'time'   =>  $order['paytime'],
                    'title'  =>  '订单支付成功',
                    'detail' =>  '支付成功，分销商申请成功，等待审核，订单等待系统结算'
                );
            }
        }
        //活动
        if($type == 9){
            if($order['paytime']){
                $logs[] = array(   //支付
                    'time'   =>  $order['paytime'],
                    'title'  =>  '订单支付成功',
                    'detail' =>  '支付成功，等待买家在活动期间前往指定地点参加活动'
                );
            }
        }

        //团购
        if($type == 10){
            if($order['paytime']){
                $logs[] = array(   //支付
                    'time'   =>  $order['paytime'],
                    'title'  =>  '订单支付成功',
                    'detail' =>  '支付成功，等待买家前往商户核销订单'
                );
            }

            if($order['expressid']){
                $express = pdo_get('wlmerchant_express',array('id' => $order['expressid']),array('expressname','expresssn','expressprice','sendtime','receivetime'));
                $expressprice = $express['expressprice'];
                if($express['sendtime']){
                    $logs[] = array(   //支付
                        'time'   =>  $express['sendtime'],
                        'title'  =>  '商品已发货',
                        'detail' =>  '快递公司:'.$express['expressname'].',快递单号:'.$express['expresssn'].',等待买家收货'
                    );
                }
                if($express['receivetime']){
                    $logs[] = array(   //支付
                        'time'   =>  $express['receivetime'],
                        'title'  =>  '商品已签收',
                        'detail' =>  '用户已签收商品，等待系统结算订单'
                    );
                }
            }else{
                //核销
                $usedtime = pdo_getcolumn(PDO_NAME.'groupon_userecord',array('id'=>$order['recordid']),'usedtime');
                $usedtime = unserialize($usedtime);
            }
        }

        //在线买单
        if($type == 11){
            if($order['paytime']){
                $logs[] = array(   //支付
                    'time'   =>  $order['paytime'],
                    'title'  =>  '订单支付成功',
                    'detail' =>  '在线买单支付成功，订单等待系统结算'
                );
            }
        }

        //砍价订单
        if($type == 12){
            if($order['expressid']){
                if($order['paytime']){
                    $logs[] = array(   //支付
                        'time'   =>  $order['paytime'],
                        'title'  =>  '订单支付成功',
                        'detail' =>  '订单支付成功,等待商家发货'
                    );
                }
                $express = pdo_get('wlmerchant_express',array('id' => $order['expressid']),array('expressname','expresssn','expressprice','sendtime','receivetime'));
                $expressprice = $express['expressprice'];
                if($express['sendtime']){
                    $logs[] = array(   //支付
                        'time'   =>  $express['sendtime'],
                        'title'  =>  '商品已发货',
                        'detail' =>  '快递公司:'.$express['expressname'].',快递单号:'.$express['expresssn'].',等待买家收货'
                    );
                }
                if($express['receivetime']){
                    $logs[] = array(   //支付
                        'time'   =>  $express['receivetime'],
                        'title'  =>  '商品已签收',
                        'detail' =>  '用户已签收商品，等待系统结算订单'
                    );
                }
            }else{
                if($order['paytime']){
                    $logs[] = array(   //支付
                        'time'   =>  $order['paytime'],
                        'title'  =>  '订单支付成功',
                        'detail' =>  '订单支付成功,等待买家到店核销'
                    );
                }
                $record = pdo_get('wlmerchant_bargain_userlist',array('id' => $order['specid']),array('usedtime'));
                $usedtime = unserialize($record['usedtime']);
            }
        }
        //同城配送
        if($type == 14){
            if($order['paytime']){
                if($order['expressid']){
                    $deliveryremark1 = '支付成功,等待商家配送';
                    $expressprice = $order['expressprcie'];
                }else{
                    $deliveryremark1 = '支付成功,等待买家前往商户自提';
                }
                $logs[] = array(   //支付
                    'time'   =>  $order['paytime'],
                    'title'  =>  '订单支付成功',
                    'detail' =>  $deliveryremark1
                );
            }

            if($order['status'] == 2 || $order['status'] == 3 ){
                if($order['deliverytype'] == 1){
                    $deliveryremark2 = '用户确认订单完成';
                }else if($order['deliverytype'] == 2){
                    $deliveryremark2 = '商户确认订单完成';
                }else{
                    $deliveryremark2 = '平台确认订单完成';
                }
                $logs[] = array(   //完成
                    'time'   =>  pdo_getcolumn(PDO_NAME.'delivery_order',array('tid'=>$order['orderno']),'dotime'),
                    'title'  =>  '订单已完成',
                    'detail' =>  $deliveryremark2
                );
            }

        }

        //黄页114 求职招聘
        if($type == 15 || $type == 16){
            if($order['paytime']){
                $logs[] = array(   //支付
                    'time'   =>  $order['paytime'],
                    'title'  =>  '订单支付成功',
                    'detail' =>  '支付成功，订单等待系统结算'
                );
            }
        }

        //酒店
        if($type == 17 ){
            if($order['paytime']){
                $logs[] = array(   //支付
                    'time'   =>  $order['paytime'],
                    'title'  =>  '订单支付成功',
                    'detail' =>  '支付成功，等待用户核销入住'
                );
            }
        }


        if($usedtime){
            foreach ($usedtime as $key => $used) {
                switch ($used['type']){
                    case '1':
                        $used['typename'] = '输码核销';
                        break;
                    case '2':
                        $used['typename'] = '扫码核销';
                        break;
                    case '3':
                        $used['typename'] = '后台核销';
                        break;
                    case '4':
                        $used['typename'] = '密码核销';
                        break;
                    default:
                        $used['typename'] = '未知方式';
                        break;
                }
                if($used['type'] == 1 || $used['type'] == 2){
                    $used['vername'] = pdo_getcolumn(PDO_NAME.'merchantuser',array('mid'=>$used['ver'],'storeid'=>$order['sid']),'name');
                }else {
                    $used['vername'] = '无';
                }
                $kk = $key+1;
                $logs[] = array(
                    'time'   =>  $used['time'],
                    'title'  =>  '第'.$kk.'次核销',
                    'detail' =>  '核销方式:'.$used['typename'].',核销员:'.$used['vername'].'。'
                );
                if(empty($order['usetimes']) && $kk == count($usedtime)){
                    $logs[] = array(
                        'time'   =>  $used['time'],
                        'title'  =>  '核销完成',
                        'detail' =>  '订单已全部核销，等待系统结算订单'
                    );
                }
            }
        }

        if($order['neworderflag'] || $plugin == 'hotel'){
            $hexiaotype = array(1=>'输码核销',2=>'扫码核销',3=>'后台核销','4'=>'密码核销','5'=>'系统自动核销');
            $smallorders = pdo_getall('wlmerchant_smallorder',array('orderid' => $order['id'],'plugin'=>$plugin));
            foreach ($smallorders as $key => &$smallasd){
                if($smallasd['status'] == 2){
                    $smalltype = $smallasd['hexiaotype'];
                    if($smallasd['hxuid']){
                        $vername = pdo_getcolumn(PDO_NAME.'merchantuser',array('id'=>$smallasd['hxuid']),'name');
                    }else{
                        $vername = '无';
                    }
                    $logs[] = array(
                        'time'   =>  $smallasd['hexiaotime'],
                        'title'  =>  '核销码：'.$smallasd['checkcode'],
                        'detail' =>  '核销方式:'.$hexiaotype[$smalltype].',核销员:'.$vername.'。'
                    );
                    $smallasd['douser'] = $hexiaotype[$smalltype].':'.$vername;
                    $smallasd['dotime'] = date('Y-m-d H:i:s',$smallasd['hexiaotime']);
                }else if($smallasd['status'] == 3){
                    $logs[] = array(
                        'time'   =>  $smallasd['refundtime'],
                        'title'  =>  '核销码['.$smallasd['checkcode'].']已退款',
                        'detail' =>  ''
                    );
                    $smallasd['douser'] = '订单退款';
                    $smallasd['dotime'] = date('Y-m-d H:i:s',$smallasd['refundtime']);
                }else{
                    $smallasd['douser'] = '无';
                    $smallasd['dotime'] = '未操作';
                }
            }
        }
        //退款
        if($order['status'] == 7){
            $logs[] = array(
                'time'   =>  $order['refundtime'],
                'title'  =>  '订单已退款',
                'detail' =>  '订单已退款，此订单已结束'
            );
        }
        //过期
        if($order['status'] == 9){
            $logs[] = array(
                'time'   =>  $order['overtime'],
                'title'  =>  '订单已过期',
                'detail' =>  '订单已过期，等待系统结算订单'
            );
        }
        //结算
        if($order['issettlement'] == 1){
            $settlement = pdo_fetch("SELECT * FROM ".tablename('wlmerchant_autosettlement_record')."WHERE orderid = {$order['id']} AND type = {$type} ORDER BY id DESC");
            $logs[] = array(
                'time'   =>  $settlement['createtime'],
                'title'  =>  '订单已结算',
                'detail' =>  '订单已结算，此订单已完成'
            );
        }
        //售后
        $afters = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_aftersale')."WHERE uniacid = {$_W['uniacid']} AND orderno = {$order['orderno']}  ORDER BY createtime ASC");
        if($afters){
            foreach ($afters as &$af){
                $journal = unserialize($af['journal']);
                foreach ($journal as $jo){
                    $jo = unserialize($jo);
                    $jo['thumbs'] = unserialize($jo['thumbs']);
                    if(!empty($jo['thumbs'])){
                        $jo['thumbs'] = explode(',',$jo['thumbs']);
                    }
                    $logs[] = $jo;
                }
                $af['checkcodes'] = unserialize($af['checkcodes']);
                $af['thumbs'] = unserialize($af['thumbs']);
                if(!empty($af['thumbs'])){
                    $af['thumbs'] = explode(',',$af['thumbs']);
                }
                if($order['ordera'] == 'a'){
                    $refunda = 'b';
                }else{
                    $refunda = 'a';
                }
                $af['refundurl'] = web_url('order/wlOrder/refund',array('id'=>$af['orderid'],'afterid'=>$af['id'],'type'=>$refunda));
            }
        }
        //评价
        if($order['status'] == 3){
            $commenttime = pdo_getcolumn(PDO_NAME.'comment',array('plugin'=>$plugin,'idoforder'=>$order['id']),'createtime');
            if($commenttime){
                $logs[] = array(
                    'time'   =>  $commenttime,
                    'title'  =>  '订单已评价',
                    'detail' =>  '用户就此订单对商户发表了评价'
                );
            }
        }
        $flag = array();
        foreach($logs as $v){
            $flag[] = $v['time'];
        }
        array_multisort($flag, SORT_ASC, $logs);
        //价格明细
        if($type == 1){ //抢购
            //商品价格
            $goods = pdo_get('wlmerchant_rush_activity',array('id' => $order['activityid']),array('id','price','aid','vipprice','name','thumb'));
            if($order['optionid']){
                $option = pdo_get('wlmerchant_goods_option',array('id' => $order['optionid']),array('price','vipprice'));
                $goods['price'] = $option['price'];
                $goods['vipprice'] = $option['vipprice'];
            }
            if ($order['vipbuyflag']) {
                if($order['discount'] > 0){
                    $vipdiscount = $order['discount'];
                }else {
                    if ($order['optionid']) {
                        $vipdiscount = sprintf("%.2f", $option['price'] - $option['vipprice']);
                    } else {
                        $vipdiscount = sprintf("%.2f", $goods['price'] - $goods['vipprice']);
                    }
                }
            }
            //积分抵扣
            $dkcredit = $order['dkcredit'];
            $dkmoney = $order['dkmoney'];
            $actualprice = $order['actualprice'];
            if(is_store()){
                $editurl = "./citystore.php?uniacid={$_W['uniacid']}&aid={$goods['aid']}&p=goodshouse&ac=goodshouse&do=createactive&id={$goods['id']}&plugin=rush";
            }else if(empty($goods['aid'])){
                $editurl = web_url('goodshouse/goodshouse/createactive',array('id'=>$goods['id'],'plugin'=>'rush'));
            }else {
                $editurl = "./cityagent.php?uniacid={$_W['uniacid']}&aid={$goods['aid']}&p=goodshouse&ac=goodshouse&do=createactive&id={$goods['id']}&plugin=rush";
            }
        }else if($type == 2){ //拼团
            $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $order['fkid']),array('id','price','aid','aloneprice','vipdiscount','name','logo'));
            //会员减免
            if($order['vipbuyflag']){
                if($order['vipdiscount'] > 0){
                    $vipdiscount = $order['vipdiscount'];
                }else {
                    $vipdiscount = sprintf("%.2f", $goods['vipdiscount'] * $order['num']);
                }
            }
            //积分抵扣
            $dkcredit = $order['usecredit'];
            $dkmoney = $order['cerditmoney'];

            $goods['thumb'] = $goods['logo'];
            $actualprice = $order['price'];
            if(is_store()){
                $editurl = "./citystore.php?uniacid={$_W['uniacid']}&aid={$goods['aid']}&p=goodshouse&ac=goodshouse&do=createactive&id={$goods['id']}&plugin=fightgroup";
            }else if(empty($goods['aid'])){
                $editurl = web_url('goodshouse/goodshouse/createactive',array('id'=>$goods['id'],'plugin'=>'fightgroup'));
            }else{
                $editurl = "./cityagent.php?uniacid={$_W['uniacid']}&aid={$goods['aid']}&p=goodshouse&ac=goodshouse&do=createactive&id={$goods['id']}&plugin=fightgroup";
            }
        }else if($type == 3){
            $goods = pdo_get('wlmerchant_couponlist',array('id' => $order['fkid']),array('id','price','aid','vipprice','title','logo'));
            $goods['name'] = $goods['title'];
            $goods['thumb'] = $goods['logo'];
            $actualprice = $order['price'];
            if ($order['vipbuyflag']) {
                if($order['vipdiscount'] > 0){
                    $vipdiscount = $order['vipdiscount'];
                }else {
                    $vipdiscount = sprintf("%.2f", $goods['price'] - $goods['vipprice']);
                }
            }
            //积分抵扣
            $dkcredit = $order['usecredit'];
            $dkmoney = $order['cerditmoney'];
            if(is_store()){
                $editurl = "./citystore.php?uniacid={$_W['uniacid']}&aid={$goods['aid']}&p=wlcoupon&ac=couponlist&do=editCoupons&id={$goods['id']}";
            }else if(empty($goods['aid'])){
                $editurl = web_url('wlcoupon/couponlist/editCoupons',array('id'=>$goods['id']));
            }else{
                $editurl = "./cityagent.php?uniacid={$_W['uniacid']}&aid={$goods['aid']}&p=wlcoupon&ac=couponlist&do=editCoupons&id={$goods['id']}";
            }
        }else if($type == 9){
            $goods = pdo_get('wlmerchant_activitylist',array('id' => $order['fkid']),array('id','aid','price','vipprice','title','thumb'));
            $goods['name'] = $goods['title'];
            if ($order['vipbuyflag']) {
                if($order['vipdiscount'] > 0){
                    $vipdiscount = $order['vipdiscount'];
                }else {
                    $vipdiscount = $goods['vipprice'];
                }
            }
            $actualprice = $order['price'];
            //积分抵扣
            $dkcredit = $order['usecredit'];
            $dkmoney = $order['cerditmoney'];
            if(is_store()){
                $editurl = "./citystore.php?uniacid={$_W['uniacid']}&aid={$goods['aid']}&p=activity&ac=activity_web&do=createactivity&id={$goods['id']}";
            }else if(empty($goods['aid'])){
                $editurl = web_url('activity/activity_web/createactivity',array('id'=>$goods['id']));
            }else{
                $editurl = "./cityagent.php?uniacid={$_W['uniacid']}&aid={$goods['aid']}&p=activity&ac=activity_web&do=createactivity&id={$goods['id']}";
            }
        }else if($type == 10){
            $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $order['fkid']),array('id','aid','price','vipprice','name','thumb'));
            if ($order['vipbuyflag']) {
                if($order['vipdiscount'] > 0){
                    $vipdiscount = $order['vipdiscount'];
                }else {
                    if ($order['specid']) {
                        $option = pdo_get('wlmerchant_goods_option', array('id' => $order['specid']), array('price', 'vipprice'));
                        $vipdiscount = sprintf("%.2f", $option['price'] - $option['vipprice']);
                    } else {
                        $vipdiscount = sprintf("%.2f", $goods['price'] - $goods['vipprice']);
                    }
                }
            }
            $actualprice = $order['price'];
            //积分抵扣
            $dkcredit = $order['usecredit'];
            $dkmoney = $order['cerditmoney'];
            if(is_store()){
                $editurl = "./citystore.php?uniacid={$_W['uniacid']}&aid={$goods['aid']}&p=goodshouse&ac=goodshouse&do=createactive&id={$goods['id']}&plugin=groupon";
            }else if(empty($goods['aid'])){
                $editurl = web_url('goodshouse/goodshouse/createactive',array('id'=>$goods['id'],'plugin'=>'groupon'));
            }else{
                $editurl = "./cityagent.php?uniacid={$_W['uniacid']}&aid={$goods['aid']}&p=goodshouse&ac=goodshouse&do=createactive&id={$goods['id']}&plugin=groupon";
            }
        }else if($type == 4){
            $goods = pdo_get('wlmerchant_halfcard_type',array('id' => $order['typeid']),array('id','price','name','logo'));
            $order['num'] = 1;
            $goods['thumb'] = $goods['logo'];
            $actualprice = $order['price'];
            $editurl = web_url('halfcard/halftype/add',array('id'=>$goods['id']));
        }else if($type == 5){
            $informations = pdo_get('wlmerchant_pocket_informations',array('id' => $order['fkid']),array('type'));
            $goods = pdo_get('wlmerchant_pocket_type',array('id' => $informations['type']),array('title','price','img'));
            $goods['thumb'] = $goods['img'];
            $goods['name'] = '发布'.$goods['title'].'信息';
            $actualprice = $order['price'];
            $editurl = web_url('pocket/Type/operating',array('eid'=>$goods['id']));
        }else if($type == 6){
            $goods = pdo_get('wlmerchant_chargelist',array('id' => $order['fkid']),array('id','price','name'));
            $goods['thumb'] = URL_MODULE.'web/resource/image/store.png';
            $goods['name'] = '付费入驻:'.$goods['name'];
            $actualprice = $order['price'];
            $order['num'] = 1;
            $editurl = web_url('setting/register/add',array('id'=>$goods['id']));
        }else if($type == 8){
            $goods['thumb'] = URL_MODULE.'web/resource/image/store.png';
            $goods['name'] = '付费申请成为分销商';
            $actualprice = $order['price'];
            $order['num'] = 1;
            $editurl = web_url('distribution/dissysbase/disbaseset');
        }else if($type == 11){
            $goods['thumb'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('uniacid'=>$_W['uniacid'],'id'=>$order['sid']),'logo');
            $goods['thumb'] = tomedia($goods['thumb']);
            $actualprice = $order['price'];
            //积分抵扣
            $dkcredit = $order['usecredit'];
            $dkmoney = $order['cerditmoney'];
            $order['num'] = 1;
            if($order['fkid']){
                $goods['name'] = pdo_getcolumn(PDO_NAME.'halfcardlist',array('uniacid'=>$_W['uniacid'],'id'=>$order['fkid']),'title');
                $editurl = web_url('halfcard/halfcard_web/editHalfcard',array('id'=>$order['fkid']));
            }else{
                $goods['name'] = '买家在线买单';
            }
            $vipdiscount = $order['card_fee'];
        }else if($type == 12){
            $actualprice = $order['price'];
            $goods = pdo_get(PDO_NAME.'bargain_activity',array('uniacid'=>$_W['uniacid'],'id'=>$order['fkid']),array('id','thumb','name'));
            $goods['thumb'] = tomedia($goods['thumb']);
            //积分抵扣
            $dkcredit = $order['usecredit'];
            $dkmoney = $order['cerditmoney'];
            if(is_store()){
                $editurl = "./citystore.php?uniacid={$_W['uniacid']}&aid={$goods['aid']}&p=goodshouse&ac=goodshouse&do=createactive&id={$goods['id']}&plugin=bargain";
            }else if(empty($goods['aid'])){
                $editurl = web_url('goodshouse/goodshouse/createactive',array('id'=>$goods['id'],'plugin'=>'bargain'));
            }else{
                $editurl = "./cityagent.php?uniacid={$_W['uniacid']}&aid={$goods['aid']}&p=goodshouse&ac=goodshouse&do=createactive&id={$goods['id']}&plugin=bargain";
            }
        }else if($type == 13){
            $actualprice = $order['price'];
            if($order['fightstatus'] == 1){
                $goods = pdo_get(PDO_NAME.'citycard_meals',array('uniacid'=>$_W['uniacid'],'id'=>$order['fkid']),array('name'));
            }else{
                $goods = pdo_get(PDO_NAME.'citycard_tops',array('uniacid'=>$_W['uniacid'],'id'=>$order['fkid']),array('name'));
            }
        }else if($type == 14){ //同城配送
            //商品价格
            $delivery_orders = pdo_getall('wlmerchant_delivery_order',array('tid' => $order['orderno']),array('gid','num','price','aid','specid'));
            foreach ($delivery_orders as &$delivery_or){
                $delivery_or['good'] = pdo_get('wlmerchant_delivery_activity',array('id' => $delivery_or['gid']),array('name','thumb'));
                $delivery_or['good']['thumb'] = tomedia($delivery_or['good']['thumb']);
                if($delivery_or['specid']){
                    $delivery_or['good']['specname'] = pdo_getcolumn(PDO_NAME.'delivery_spec',array('id'=>$delivery_or['specid']),'name');
                }
                if(is_store()){
                    $delivery_or['good']['editurl'] = "./citystore.php?uniacid={$_W['uniacid']}&aid={$delivery_or['aid']}&p=citydelivery&ac=active&do=createactive&id={$delivery_or['gid']}";
                }else if(empty($delivery_or['aid'])){
                    $delivery_or['good']['editurl'] = web_url('citydelivery/active/createactive',array('id'=>$delivery_or['gid']));
                }else {
                    $delivery_or['good']['editurl'] = "./cityagent.php?uniacid={$_W['uniacid']}&aid={$delivery_or['aid']}&p=citydelivery&ac=active&do=createactive&id={$delivery_or['gid']}";
                }
            }
            //积分抵扣
            $dkcredit = $order['usecredit'];
            $dkmoney = $order['cerditmoney'];
            $vipdiscount = $order['vipdiscount'];
            $actualprice = $order['price'];
        }else if($type == 15){
            $yellow = pdo_get('wlmerchant_yellowpage_lists' , ['id' => $order['fkid']],['name','logo']);
            switch ($order['fightstatus']) {
                case '1':
                    $goods['name'] = '页面['.$yellow['name'].']认领';
                    break;
                case '2':
                    $goods['name'] = '页面['.$yellow['name'].']查阅';
                    break;
                case '3':
                    $meal = pdo_get('wlmerchant_yellowpage_meals' , ['id' => $order['specid']],['name']);
                    $goods['name'] = '页面['.$yellow['name'].']入驻['.$meal['name'].']';
                    break;
                case '4':
                    $meal = pdo_get('wlmerchant_yellowpage_meals' , ['id' => $order['specid']],['name']);
                    $goods['name'] = '页面['.$yellow['name'].']续费['.$meal['name'].']';
                    break;
            }
            $goods['thumb'] = tomedia($yellow['logo']);
        }else if($type == 16){
            $recruit = pdo_get('wlmerchant_recruit_recruit' , ['id' => $order['fkid']],['title']);
            if($order['fightstatus'] == 1){
                $goods['name'] = '发布['.$recruit['title'].']岗位';
            }else if($order['fightstatus'] == 2){
                $goods['name'] = '置顶['.$recruit['title'].']岗位';
            }
        }else if($type == 17){
            $room = pdo_get('wlmerchant_hotel_room' , ['id' => $order['fkid']],['name','thumb','id','aid']);
            $goods['name'] = $room['name'];
            $goods['thumb'] = $room['thumb'];

            if($order['vipbuyflag']){
                $vipdiscount = $order['vipdiscount'];
            }

            //积分抵扣
            $dkcredit = $order['dkcredit'];
            $dkmoney = $order['dkmoney'];
            $actualprice = $order['actualprice'];
            if(is_store()){
                $editurl = "./citystore.php?uniacid={$_W['uniacid']}&aid={$room['aid']}&p=hotel&ac=hotel&do=roomEdit&id={$room['id']}";
            }else if(empty($room['aid'])){
                $editurl = web_url('hotel/hotel/roomEdit',array('id'=>$room['id']));
            }else {
                $editurl = "./cityagent.php?uniacid={$_W['uniacid']}&aid={$room['aid']}&p=hotel&ac=hotel&do=roomEdit&id={$room['id']}";
            }
        }
        if(empty($actualprice)){
            $actualprice = $order['price'];
        }

        $fullreducemoney = $order['fullreducemoney'];
        $redpackmoney = $order['redpackmoney'];
        if($redpackmoney > 0){
            $packid = pdo_getcolumn(PDO_NAME.'redpack_records',array('id'=>$order['redpackid']),'packid');
            $packname = pdo_getcolumn(PDO_NAME.'redpacks',array('id'=>$packid),'title');
        }

        //同时开通一卡通
        if($order['vip_card_id'] > 0){
            $vipCardPrice = pdo_getcolumn(PDO_NAME.'halfcard_type',array('id'=>$order['vip_card_id']),'price');
        }
        //结算金额
        if($order['issettlement'] == 1){
            $salesmoney = 0;
            $hexiaoprice = 0;
            if($order['neworderflag']){
                $merchantmoney = 0;
                $agentmoney = 0;
                $distrimoney = 0;
                $refundmoney = 0;
                $num = 0;
                foreach ($smallorders as $key => $small){
                    if($small['status'] == 2){
                        $orderset = pdo_fetch("SELECT * FROM ".tablename('wlmerchant_autosettlement_record')."WHERE orderid = {$order['id']} AND type = {$type} AND checkcode = '{$small['checkcode']}'");
                        $merchantmoney += $orderset['merchantmoney'];
                        $agentmoney += $orderset['agentmoney'];
                        $distrimoney += $orderset['distrimoney'];
                        $num += 1;
                    }
                    $hexiaoprice = $goodsprice / $order['num'] * $num;
                }
            }else{
                $agentmoney = $settlement['agentmoney'];  //代理金额
                $merchantmoney = $settlement['merchantmoney']; //商户金额
                $distrimoney = $settlement['distrimoney']; //分销金额
                $hexiaoprice = $goodsprice; //计算业务员金额的基础数据
            }
            if($distrimoney > 0){
                $disorder = pdo_get('wlmerchant_disorder',array('id' => $order['disorderid']),array('oneleadid','twoleadid','leadmoney'));
                $onename = pdo_getcolumn(PDO_NAME.'distributor',array('id'=>$disorder['oneleadid']),'nickname');
                $twoname = pdo_getcolumn(PDO_NAME.'distributor',array('id'=>$disorder['twoleadid']),'nickname');
                $leadmoney = unserialize($disorder['leadmoney']);
            }
            //计算业务员佣金
            if(!empty($order['salesarray'])){
                $salesarray = unserialize($order['salesarray']);
                foreach ($salesarray as &$sale){
                    $sale['salemoney'] = sprintf("%.2f",$hexiaoprice * $sale['scale'] / 100 );
                    $sale['nickname'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$sale['mid']),'nickname');
                    $salesmoney += $sale['salemoney'];
                }
            }

            if($order['shareid']){
                $shares = pdo_get('wlmerchant_sharegift_record',array('id' => $order['shareid']),array('price','type','mid'));
                $sharemoney = sprintf("%.2f",$shares['price']*$order['num']);
                if($shares['type'] == 2){
                    $sharename = pdo_getcolumn(PDO_NAME.'member',array('id'=>$shares['mid']),'nickname');
                }
            }
        }

        $refundmoney = pdo_getcolumn('wlmerchant_refund_record',array('plugin' => $plugin,'orderid'=>$orderid,'status'=>1),array("SUM(refundfee)"));
        if($order['status'] == 7 && $order['neworderflag']){
            $merchantmoney = 0.00;
        }

        //快递栏
        if($order['expressid']){
            if($order['plugin'] == 'citydelivery'){
                if($order['fightgroupid'] > 0){
                    $express = pdo_get('wlmerchant_express',array('id' => $order['fightgroupid']));
                }else{
                    $addressinfo = pdo_get('wlmerchant_address',array('id' => $order['expressid']));
                    $express['address'] = $addressinfo['province'].$addressinfo['city'].$addressinfo['county'].$addressinfo['detailed_address'];
                    $express['name'] = $addressinfo['name'];
                    $express['tel'] = $addressinfo['tel'];
                }
            }else{
                $express = pdo_get('wlmerchant_express',array('id' => $order['expressid']));
            }

        }

        include  wl_template('finace/newcashorder');
    }

    //驳回申请
    function delerefund(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        if($type == 'a'){
            $res = pdo_update('wlmerchant_order',array('applyrefund' => 0),array('id' => $id));
        }else{
            $res = pdo_update('wlmerchant_rush_order',array('applyrefund' => 0),array('id' => $id));
        }
        if($res){
            show_json(1);
        }else{
            show_json(0,'驳回失败，请重试');
        }
    }
    //发货
    function send(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        $settings = Setting::wlsetting_read('orderset');
        $edit_flag = $_GPC['edit_flag'];
        if($type == 'a'){
            $orderInfo = pdo_fetch("SELECT a.expressid,a.plugin,a.orderno,a.mid,
                            CASE a.`plugin` 
                                WHEN 'consumption' THEN (SELECT `title` FROM ".tablename(PDO_NAME.'consumption_goods')." WHERE `id` = a.`fkid`)
                                WHEN 'bargain' THEN (SELECT `name` FROM ".tablename(PDO_NAME.'bargain_activity')." WHERE `id` = a.`fkid` )
                                WHEN 'groupon' THEN (SELECT `name` FROM ".tablename(PDO_NAME.'groupon_activity')." WHERE `id` = a.`fkid` )
                                WHEN 'wlfightgroup' THEN (SELECT name FROM ".tablename(PDO_NAME.'fightgroup_goods')." WHERE `id` = a.`fkid`)
                            END as name FROM ".tablename(PDO_NAME.'order')." as a WHERE a.id = {$id} ");
        }else{
            $orderInfo = pdo_fetch("SELECT a.expressid,b.name,'rush' as plugin,a.orderno,a.mid FROM ".tablename(PDO_NAME."rush_order") ." as a LEFT JOIN "
                .tablename(PDO_NAME."rush_activity") ." as b ON a.activityid = b.id WHERE a.id = {$id} ");
        }
        $expressid = $orderInfo['expressid'];
        if(empty($expressid)){
            show_json(0, '无收货地址，无法发货！');
        }
        $express = pdo_get(PDO_NAME.'express',array('id' => $expressid));
        if ($_W['ispost']){
            if (empty($_GPC['expresssn']) &&  !empty($_GPC['express'])){
                show_json(0, '请输入快递单号！');
            }
            $expressname = $_GPC['express'];
            $expresssn = $_GPC['expresssn'];
            $res = pdo_update('wlmerchant_express', array('expressname' => $expressname,'expresssn'=>$expresssn,'orderid'=>$id,'sendtime'=>time()), array('id' => $expressid));
            if($res){
                if($type == 'a'){
                    $res = pdo_update('wlmerchant_order', array('status' => 4), array('id' => $id));
                    if($orderInfo['plugin'] == 'consumption'){
                        pdo_update(PDO_NAME."consumption_record", ['status'=>2], ['orderid'=>$id]);
                    }
                    if($settings['receipt']>0){
                        if($edit_flag){
                            pdo_delete('wlmerchant_waittask',array('important'=>$id,'key'=>6,'status'=>0));
                        }
                        $receipttime = time() + $settings['receipt']*24*3600;
                        $task = array(
                            'type'    => 'order',
                            'orderid' => $id
                        );
                        $task = serialize($task);
                        Queue::addTask(6, $task,$receipttime, $id);
                    }
                }else{
                    $res = pdo_update('wlmerchant_rush_order', array('status' => 4), array('id' => $id));
                    if($settings['receipt']>0){
                        if($edit_flag){
                            pdo_delete('wlmerchant_waittask',array('important'=>$id,'key'=>6,'status'=>0));
                        }
                        $receipttime = time() + $settings['receipt']*24*3600;
                        $task = array(
                            'type'    => 'rush',
                            'orderid' => $id
                        );
                        $task = serialize($task);
                        Queue::addTask(6, $task,$receipttime, $id);
                    }
                }
                /***模板通知***/
                $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails',['orderid'=>$id,'plugin'=>$orderInfo['plugin']]);
                $modelData = [
                    'first'             => '您购买的商品已发货，请注意查收!' ,
                    'order_no'          => $orderInfo['orderno'] ,//订单编号
                    'express_name'      => $expressname,//物流公司
                    'express_no'        => $expresssn ,//物流单号
                    'goods_name'        => $orderInfo['name']  ,//商品信息
                    'consignee'         => $express['name'] ,//收货人
                    'receiving_address' => $express['address']  ,//收货地址
                    'remark'            => '点击查看物流详细信息!'
                ];
                //渠道信息获取
                $source = pdo_getcolumn(PDO_NAME."paylogvfour",['tid'=>$orderInfo['orderno']],'source');
                $res = TempModel::sendInit('send',$orderInfo['mid'],$modelData,$source,$url);
                /***模板通知***/
                show_json(1);
            }else{
                show_json(0,'发货失败请重试');
            }
        }
        //快递
        $express_list = Logistics::codeComparisonTable('',0,true);


        include wl_template('order/send');
    }
    //改价
    function changeprice(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        if($type == 'a'){
            $order = pdo_get('wlmerchant_order',array('id' => $id),array('expressid','price','originalprice','changeprice','changedispatchprice'));
            if($order['originalprice']>0){
                $price = $order['originalprice'];
            }else{
                $price = $order['price'];
            }
        }else{
            $order = pdo_get('wlmerchant_rush_order',array('id' => $id),array('expressid','price','actualprice','originalprice','changeprice','changedispatchprice'));
            if($order['originalprice']>0){
                $price = $order['originalprice'];
            }else{
                $price = $order['actualprice'];
            }
        }
        if ($_W['ispost']){
            $price_type = $_GPC['price_type'];
            $price_value = trim($_GPC['price_value']);
            $price_value = sprintf("%.2f",$price_value);
            $express_type = $_GPC['express_type'];
            $express_value = $_GPC['express_value'];
            $express_value = sprintf("%.2f",$express_value);
            if($price_type == 2){
                $price_value = -$price_value;
            }
            if($express_type == 2){
                $express_value = -$express_value;
            }
            $newprice = $price + $price_value + $express_value;
            if($newprice<0 ||$newprice == 0 ){
                show_json(0,'改价失败，改价后订单金额不能小于或等于0');
            }
            if($type == 'a'){
                $res = pdo_update('wlmerchant_order',array('price' => $newprice,'changeprice' => $price_value,'changedispatchprice'=>$express_value,'originalprice'=>$price,'orderno'=>createUniontid()),array('id' => $id));
            }else{
                $res = pdo_update('wlmerchant_rush_order',array('actualprice' => $newprice,'changeprice' => $price_value,'changedispatchprice'=>$express_value,'originalprice'=>$price,'orderno'=>createUniontid()),array('id' => $id));
            }
            if($res){
                show_json(1);
            }else{
                show_json(0,'改价失败,请重试');
            }
        }
        include wl_template('order/changeprice');
    }
    //改分销佣金
    function changecommission(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $order = pdo_get('wlmerchant_disorder',array('id' => $id));
        $leadmoney = unserialize($order['leadmoney']);
        $order['onemember'] = pdo_getcolumn(PDO_NAME.'distributor',array('id'=>$order['oneleadid']),'nickname');
        if($order['twoleadid']){
            $order['twomember'] = pdo_getcolumn(PDO_NAME.'distributor',array('id'=>$order['twoleadid']),'nickname');
        }
        if ($_W['ispost']){
            $onemoney = $_GPC['onemoney'];
            $twomoney = $_GPC['twomoney'];
            if($onemoney < 0 || $twomoney < 0){
                show_json(0,'分销佣金不能为0,请重试');
            }
            if($order['neworderflag']){
                $num = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE disorderid = {$id}");
                $newone = sprintf("%.2f",$onemoney/$num);
                $newtow = sprintf("%.2f",$twomoney/$num);
                pdo_update('wlmerchant_smallorder',array('onedismoney' => $newone,'twodismoney'=>$newtow),array('disorderid' => $id,'status'=>1));
            }
            $newleadmoney['one'] = $onemoney;
            $newleadmoney['two'] = $twomoney;
            $newleadmoney = serialize($newleadmoney);
            $res = pdo_update('wlmerchant_disorder',array('leadmoney' => $newleadmoney),array('id' => $id));
            if($res){
                show_json(1);
            }else{
                show_json(0,'修改失败,请重试');
            }
        }


        include wl_template('order/changecommission');
    }

    //修改过期时间和状态
    function changetime(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        if($type == 'a'){
            $order = pdo_get('wlmerchant_order',array('id' => $id),array('id','aid','sid','plugin','estimatetime','recordid','fkid','issettlement','orderno'));
            if($order['plugin'] == 'wlfightgroup'){$plugin = 2;}
            if($order['plugin'] == 'coupon'){$plugin = 3;}
            if($order['plugin'] == 'groupon'){$plugin = 10;}
        }else{
            $order = pdo_get('wlmerchant_rush_order',array('id' => $id),array('id','aid','sid','estimatetime','activityid','issettlement','orderno'));
            $plugin = 1;
        }
        if ($_W['ispost']){
            $time = strtotime($_GPC['estimatetime']);
            if($type == 'a'){
                if($_GPC['classtype']){
                    if($time < time()){
                        $res2 = pdo_update('wlmerchant_order',array('estimatetime' => $time,'status'=>9),array('fkid' => $order['fkid'],'plugin' => $order['plugin'],'status'=>1));
                    }else{
                        $res2 = pdo_update('wlmerchant_order',array('estimatetime' => $time,'status'=>1),array('fkid' => $order['fkid'],'plugin' => $order['plugin'],'status'=>[1,9]));
                    }
                    if($order['plugin'] == 'coupon' ){
                        pdo_update('wlmerchant_member_coupons',array('endtime' => $time),array('parentid' => $order['fkid'],'status'=>1));
                    }
                }else{
                    if($time < time()){
                        $res1 = pdo_update('wlmerchant_order',array('estimatetime' => $time,'status'=>9),array('id' => $id,'status' => 1));
                    }else{
                        $res1 = pdo_update('wlmerchant_order',array('estimatetime' => $time,'status'=>1,'issettlement'=>0),array('id' => $id,'status' => [1,9]));
                    }
                    if($order['plugin'] == 'coupon'){
                        pdo_update('wlmerchant_member_coupons',array('endtime' => $time,'status'=>1),array('id' => $order['recordid']));
                    }
                }
            }else{
                if($_GPC['classtype']){
//                    if($time > time()){
//                        $orders = pdo_getall('wlmerchant_rush_order',array('activityid' => $order['activityid'],'issettlement' => 1,'status'=>9),array('id','aid','sid','issettlement','orderno'));
//                        foreach ($orders as $key => $aor){
//                            $settlement = pdo_get('wlmerchant_autosettlement_record',array('orderid' => $aor['id'],'type'=> $plugin));
//                            if($settlement['agentmoney'] > 0){
//                                pdo_fetch("update" . tablename('wlmerchant_agentusers') . "SET allmoney=allmoney-{$settlement['agentmoney']},nowmoney=nowmoney-{$settlement['agentmoney']} WHERE id = {$aor['aid']}");
//                                $changeagentnowmoney = pdo_getcolumn(PDO_NAME . 'agentusers', array('id' => $aor['aid']), 'nowmoney');
//                                Store::addcurrent(2,-1,$aor['aid'],-$settlement['agentmoney'],$changeagentnowmoney,'','后台修改['.$aor['orderno'].']订单时限扣除已结算金额');
//                            }
//                            if($settlement['merchantmoney'] > 0){
//                                pdo_fetch("update" . tablename('wlmerchant_merchantdata') . "SET allmoney=allmoney-{$settlement['merchantmoney']},nowmoney=nowmoney-{$settlement['merchantmoney']} WHERE id = {$aor['sid']}");
//                                $changemerchantnowmoney = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $aor['sid']), 'nowmoney');
//                                Store::addcurrent(1,-1,$aor['sid'],-$settlement['merchantmoney'],$changemerchantnowmoney,'','后台修改['.$aor['orderno'].']订单时限扣除已结算金额');
//                            }
//                            pdo_delete('wlmerchant_autosettlement_record',array('id'=>$settlement['id']));
//                        }
//                    }
                    $res2 = pdo_update('wlmerchant_rush_order',array('estimatetime' => $time),array('activityid' => $order['activityid'],'status'=>1));
                    $res1 = pdo_update('wlmerchant_rush_order',array('estimatetime' => $time,'status'=>1,'issettlement'=>0),array('activityid' => $order['activityid'],'status'=>9));
                }else{
//                    if($order['issettlement']){
//                        $settlement = pdo_get('wlmerchant_autosettlement_record',array('orderid' => $order['id'],'type'=> $plugin));
//                        if($settlement['agentmoney'] > 0){
//                            pdo_fetch("update" . tablename('wlmerchant_agentusers') . "SET allmoney=allmoney-{$settlement['agentmoney']},nowmoney=nowmoney-{$settlement['agentmoney']} WHERE id = {$order['aid']}");
//                            $changeagentnowmoney = pdo_getcolumn(PDO_NAME . 'agentusers', array('id' => $order['aid']), 'nowmoney');
//                            Store::addcurrent(2,-1,$order['aid'],-$settlement['agentmoney'],$changeagentnowmoney,'','后台修改['.$order['orderno'].']订单时限扣除已结算金额');
//                        }
//                        if($settlement['merchantmoney'] > 0){
//                            pdo_fetch("update" . tablename('wlmerchant_merchantdata') . "SET allmoney=allmoney-{$settlement['merchantmoney']},nowmoney=nowmoney-{$settlement['merchantmoney']} WHERE id = {$order['sid']}");
//                            $changemerchantnowmoney = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $order['sid']), 'nowmoney');
//                            Store::addcurrent(1,-1,$order['sid'],-$settlement['merchantmoney'],$changemerchantnowmoney,'','后台修改['.$order['orderno'].']订单时限扣除已结算金额');
//                        }
//                        pdo_delete('wlmerchant_autosettlement_record',array('id'=>$settlement['id']));
//                    }
                    $res1 = pdo_update('wlmerchant_rush_order',array('estimatetime' => $time,'status'=>1,'issettlement'=>0),array('id' => $id));
                }
            }
            if($res1 || $res2){
                show_json(1);
            }else{
                show_json(0,'修改失败,请重试');
            }
        }

        include wl_template('order/changetime');
    }
    //收货
    function collect(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        if($type == 'a'){
            $plugin = pdo_getcolumn(PDO_NAME.'order',array('id'=>$id),'plugin');
        }else if($type == 'consumption'){
            $plugin = 'consumption';
        }else{
            $plugin = 'rush';
        }
        $res = Order::sureReceive($id,$plugin);
        if($res){
            show_json(1);
        }else{
            show_json(0,'收货失败请重试');
        }
    }
    //取消发货
    function sendcancel(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        $settings = Setting::wlsetting_read('orderset');
        if($type == 'a'){
            $res = pdo_update('wlmerchant_order', array('status' => 8), array('id' => $id));
            $expressid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$id),'expressid');
        }else if($type == 'consumption'){
            $res = pdo_update('wlmerchant_consumption_record', array('status' => 1), array('id' => $id));
            $expressid = pdo_getcolumn(PDO_NAME.'consumption_record',array('id'=>$id),'expressid');
        }else{
            $res = pdo_update('wlmerchant_rush_order', array('status' => 8), array('id' => $id));
            $expressid = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$id),'expressid');
        }
        pdo_update('wlmerchant_express', array('expressname' => '','expresssn'=>'','sendtime'=>0), array('id' => $expressid));
        if($settings['receipt']>0){
            pdo_delete('wlmerchant_waittask',array('important'=>$id,'key'=>6,'status'=>0));
        }
        if($res){
            show_json(1);
        }else{
            show_json(0,'取消失败请重试');
        }
    }
    //物流查询
    function logisticsrecord(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $logisticsInfo = Logistics::orderLogisticsInfo($id);
        if ($logisticsInfo['Traces']) {
            $list = array_reverse($logisticsInfo['Traces']);
        }else{
            $list = [];
            $reason = $logisticsInfo['Reason'];
        }

        include wl_template('order/express');
    }
    //核销记录
    function hexiaorecord(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        if($type == 'a'){
            $order = pdo_get('wlmerchant_order',array('id' => $id),array('plugin','recordid','specid','neworderflag'));
            if($order['neworderflag'] > 0 || $order['plugin'] == 'hotel' ){
                $usetimes = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE plugin = '{$order['plugin']}' AND  orderid = {$id} AND status = 1");
                $smallorder = pdo_getall('wlmerchant_smallorder',array('orderid' => $id,'plugin'=>$order['plugin']),'','','status ASC,hexiaotime ASC');
            }else{
                if($order['plugin'] == 'wlfightgroup'){
                    $record = pdo_get('wlmerchant_fightgroup_userecord',array('id' => $order['recordid']),array('usetimes','usedtime'));
                    $usetimes = $record['usetimes'];
                    $usedtime = unserialize($record['usedtime']);
                }else if($order['plugin'] == 'coupon'){
                    $record = pdo_get('wlmerchant_member_coupons',array('id' => $order['recordid']),array('usetimes','usedtime'));
                    $usetimes = $record['usetimes'];
                    $usedtime = unserialize($record['usedtime']);
                }else if($order['plugin'] == 'groupon'){
                    $record = pdo_get('wlmerchant_groupon_userecord',array('id' => $order['recordid']),array('usetimes','usedtime'));
                    $usetimes = $record['usetimes'];
                    $usedtime = unserialize($record['usedtime']);
                }else if($order['plugin'] == 'bargain'){
                    $record = pdo_get('wlmerchant_bargain_userlist',array('id' => $order['specid']),array('usetimes','usedtime'));
                    $usetimes = $record['usetimes'];
                    $usedtime = unserialize($record['usedtime']);
                }
            }


        }else{
            $order = pdo_get('wlmerchant_rush_order',array('id' => $id),array('usetimes','usedtime','neworderflag'));
            if($order['neworderflag']){
                $usetimes = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE plugin = 'rush' AND  orderid = {$id} AND status = 1");
                $smallorder = pdo_getall('wlmerchant_smallorder',array('orderid' => $id,'plugin'=>'rush'),'','','status ASC,hexiaotime ASC');
            }else{
                $usetimes = $order['usetimes'];
                $usedtime = unserialize($order['usedtime']);
            }

        }
        if($smallorder){
            $usedtime = array();
            foreach ($smallorder as $key => $sm) {
                $va['status'] = $sm['status'];
                if($sm['status'] == 2){
                    $va['time'] =  date("Y-m-d H:i:s",$sm['hexiaotime']);
                }else if($sm['status'] == 3){
                    $va['time'] =  date("Y-m-d H:i:s",$sm['refundtime']);
                }
                if($sm['hxuid']){
                    $va['ver'] = pdo_getcolumn(PDO_NAME.'merchantuser',array('id'=>$sm['hxuid']),'name');
                }else{
                    $va['ver'] = '无';
                }
                switch ($sm['hexiaotype']) {
                    case '1':
                        $va['type'] = '输码核销';
                        break;
                    case '2':
                        $va['type'] = '扫码核销';
                        break;
                    case '3':
                        $va['type'] = '后台核销';
                        break;
                    case '4':
                        $va['type'] = '密码核销';
                        break;
                    case '5':
                        $va['type'] = '系统自动核销';
                        break;
                    default:
                        $va['type'] = '未知方式';
                        break;
                }
                $va['checkcode'] = $sm['checkcode'];
                $usedtime[] = $va;
            }
        }else if($usedtime){
            foreach ($usedtime as $key => &$va) {
                $va['status'] = 2;
                $va['time'] = date("Y-m-d H:i:s",$va['time']);
                $va['ver'] = pdo_getcolumn(PDO_NAME.'merchantuser',array('mid'=>$va['ver']),'name');
                if(empty($va['ver'])){
                    $va['ver'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$va['ver']),'nickname');
                    if(empty($va['ver'])){
                        $va['ver'] = '无';
                    }
                }
                switch ($va['type']) {
                    case '1':
                        $va['type'] = '输码核销';
                        break;
                    case '2':
                        $va['type'] = '扫码核销';
                        break;
                    case '3':
                        $va['type'] = '后台核销';
                        break;
                    case '4':
                        $va['type'] = '密码核销';
                        break;
                    default:
                        $va['type'] = '未知方式';
                        break;
                }
            }
        }
        include wl_template('order/hexiaorecord');
    }
    //修改收货人信息
    function changeexpress(){
        global $_W, $_GPC;
        $expressid = $_GPC['expressid'];
        $express = pdo_get(PDO_NAME.'express',array('id' => $expressid));

        if ($_W['ispost']){
            $data['name'] = trim($_GPC['name']);
            $data['tel'] = trim($_GPC['tel']);
            $data['address'] = $_GPC['address'];
            $res = pdo_update('wlmerchant_express',$data,array('id' => $expressid));
            if($res){
                show_json(1);
            }else{
                show_json(0,'修改失败请重试');
            }
        }

        include wl_template('order/changeexpress');
    }
    //详情页单独核销码核销
    function fetchcheck(){
        global $_W, $_GPC;
        $checkcode = $_GPC['checkcode'];
        $order = pdo_get(PDO_NAME . 'smallorder', array('uniacid' => $_W['uniacid'], 'checkcode' => $checkcode));
        if ($order) {
            if ($order['status'] == 1) {
                if ($order['plugin'] == 'rush') {
                    $res = Rush::hexiaoorder($order['orderid'], $_W['mid'], 1, 3, $order['checkcode']);
                } else if ($order['plugin'] == 'groupon') {
                    $res = Groupon::hexiaoorder($order['orderid'], $_W['mid'], 1, 3, $order['checkcode']);
                } else if ($order['plugin'] == 'wlfightgroup') {
                    $res = Wlfightgroup::hexiaoorder($order['orderid'], $_W['mid'], 1, 3, $order['checkcode']);
                } else if ($order['plugin'] == 'bargain') {
                    $res = Bargain::hexiaoorder($order['orderid'], $_W['mid'], 1, 3, $order['checkcode']);
                } else if ($order['plugin'] == 'coupon') {
                    $couponid = pdo_getcolumn(PDO_NAME . 'order', array('id' => $order['orderid']), 'recordid');
                    $res = wlCoupon::hexiaoorder($couponid, $_W['mid'], 1,3, $order['checkcode']);
                } else if ($order['plugin'] == 'activity') {
                    $res = Activity::hexiaoorder($order['orderid'], $_W['mid'], 1, 3, $order['checkcode']);
                }else if ($order['plugin'] == 'hotel') {
                    $res = Hotel::hexiaoorder($order['orderid'], $_W['mid'], 1, 3, $order['checkcode']);
                }
                if ($res) {
                    show_json(1);
                } else {
                    show_json(0,'使用失败，请刷新重试');
                }
            } else {
                show_json(0,'此核销码已核销完成');
            }
        } else {
            show_json(0,'核销码无效,请刷新重试');
        }
    }


    //后台核销
    function fetch(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        if($type == 'a'){
            $order = pdo_get('wlmerchant_order',array('id' => $id),array('neworderflag','plugin'));
            $plugin = $order['plugin'];
            if($order['neworderflag']){
                if($plugin == 'groupon'){
                    $num = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE plugin = 'groupon' AND  orderid = {$id} AND status = 1");
                    $res = Groupon::hexiaoorder($id,-1,$num,3);
                }else if($plugin == 'wlfightgroup'){
                    $num = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE plugin = 'wlfightgroup' AND  orderid = {$id} AND status = 1");
                    $res = Wlfightgroup::hexiaoorder($id,-1,$num,3);
                }else if($plugin == 'coupon'){
                    $recordid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$id),'recordid');
                    $num = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE plugin = 'coupon' AND  orderid = {$id} AND status = 1");
                    $res = wlCoupon::hexiaoorder($recordid,-1,$num,3);
                }else if($plugin == 'bargain'){
                    $res = Bargain::hexiaoorder($id,-1,1,3);
                }else if($plugin == 'activity'){
                    $res = Activity::hexiaoorder($id,-1,1,3);
                }else if($plugin == 'hotel'){
                    $res = Hotel::hexiaoorder($id,-1,1,3);
                }
            }else{
                if($plugin == 'wlfightgroup'){
                    $recordid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$id),'recordid');
                    $num = pdo_getcolumn(PDO_NAME.'fightgroup_userecord',array('id'=>$recordid),'usetimes');
                    $res = Wlfightgroup::hexiaoorder($id,-1,$num,3);
                }else if($plugin == 'coupon'){
                    $recordid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$id),'recordid');
                    $num = pdo_getcolumn(PDO_NAME.'member_coupons',array('id'=>$recordid),'usetimes');
                    $res = wlCoupon::hexiaoorder($recordid,-1,$num,3);
                }else if($plugin == 'groupon'){
                    $recordid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$id),'recordid');
                    $num = pdo_getcolumn(PDO_NAME.'groupon_userecord',array('id'=>$recordid),'usetimes');
                    $res = Groupon::hexiaoorder($id,-1,$num,3);
                }else if($plugin == 'bargain'){
                    $usetimes = pdo_getcolumn(PDO_NAME.'bargain_userlist',array('orderid'=>$id),'usetimes');
                    $res = Bargain::hexiaoorder($id,-1,$usetimes,3);
                }else if($plugin == 'citydelivery'){
                    $res = Citydelivery::hexiaoorder($id,3);
                }
            }
        }else{
            $item = Rush::getSingleOrder($id, '*');
            if($item['neworderflag']){
                $item['usetimes'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_smallorder')." WHERE plugin = 'rush' AND  orderid = {$id} AND status = 1");
            }
            $res = Rush::hexiaoorder($id,-1,$item['usetimes'],3);
        }
        if($res){
            show_json(1);
        }else{
            show_json(0,'使用失败，请刷新重试');
        }
    }
    //完成
    function finish(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        if($type == 'a'){
            $res = pdo_update('wlmerchant_order',array('status' => 3),array('id' => $id));
        }else{
            $res = pdo_update('wlmerchant_rush_order',array('status' => 3),array('id' => $id));
        }
        if($res){
            show_json(1);
        }else{
            show_json(0,'完成失败，请刷新重试');
        }
    }
    //退款
    function refund(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        $checkcode = $_GPC['checkcode'];
        $afterid = $_GPC['afterid'];
        if ($_W['ispost']) {
            $unline = $_GPC['refund_type'];
            $retype = $_GPC['price_type'];
            if($retype){
                $money = sprintf("%.2f",$_GPC['price_value']);
                if($money<0.01){
                    show_json(0, '退款金额不能为0');
                }
            }else{
                $money = 0;
            }
            if($afterid){
                $after = pdo_get('wlmerchant_aftersale',array('id' => $afterid),array('checkcodes','orderid','plugin'));
                $checkcodes = unserialize($after['checkcodes']);
            }
            if($afterid){  //售后处理
                if(empty($money)){
                    //获取订单信息
                    if($after['plugin'] == 'rush'){
                        $orderInfo = pdo_get(PDO_NAME."rush_order",['id'=>$after['orderid']],['expressid','actualprice']);
                        $orderInfo['price'] = $orderInfo['actualprice'];
                    }else{
                        $orderInfo = pdo_get(PDO_NAME."order",['id'=>$after['orderid']],['expressid','price']);
                    }
                    //判断是否快递订单还是核销订单
                    if(empty($checkcodes)){
                        $money = $orderInfo['price'];
                    }else{
                        $money = pdo_getcolumn('wlmerchant_smallorder',array('checkcode' => $checkcodes),array("SUM(orderprice)"));
                    }
                }
                if ($type != 'a') {
                    $plugin = pdo_getcolumn(PDO_NAME . 'order', array('id' => $id), 'plugin');
                    if ($plugin == 'wlfightgroup') {
                        $res = Wlfightgroup::refund($id,$money,$unline,0,$afterid);
                    } else if ($plugin == 'coupon') {
                        $res = wlCoupon::refund($id,$money,$unline,0,$afterid);
                    } else if ($plugin == 'groupon') {
                        $res = Groupon::refund($id,$money,$unline,0,$afterid);
                    } else if ($plugin == 'bargain') {
                        $res = Bargain::refund($id,$money,$unline);
                    } else if ($plugin == 'citydelivery'){
                        $res = Citydelivery::refund($id,$money,$unline);
                    } else if ($plugin == 'activity'){
                        $res = Activity::refundorder($id,$money,$unline,0,$afterid);
                    } else if ($plugin == 'housekeep'){
                        $res = Housekeep::refund($id,$money,$unline);
                    } else if ($plugin == 'hotel'){
                        $res = Hotel::refundorder($id,$money,$unline,0,$afterid);
                    }
                } else {
                    $res = Rush::refund($id, $money, $unline,0,$afterid);
                }
                //修改售后记录
                if($res['status']){
                    if($unline == 1){
                        $refundtype = '线下转款给用户';
                    }else if($unline == 1){
                        $refundtype = '退款到用户余额';
                    }else {
                        $refundtype = '根据支付方式原路退款';
                    }
                    $journal = array(
                        'time' => time(),
                        'title' => '到账成功',
                        'detail' => '商家已退款:'.$refundtype,
                    );
                    $journals = Order::addjournal($journal,$afterid);
                    pdo_update('wlmerchant_aftersale',array('dotime' => time(),'status'=>2,'journal'=>$journals),array('id' =>$afterid));
                    pdo_update('wlmerchant_smallorder',array('status' => 3, 'refundtime' => time()),array('checkcode' =>$checkcodes,'status'=> array(1,4)));
                }
            }else{
                //退款操作
                if ($type == 'a') {
                    $plugin = pdo_getcolumn(PDO_NAME . 'order', array('id' => $id), 'plugin');
                    if ($plugin == 'wlfightgroup') {
                        $res = Wlfightgroup::refund($id,$money,$unline,$checkcode);
                    } else if ($plugin == 'coupon') {
                        $res = wlCoupon::refund($id,$money,$unline,$checkcode);
                    } else if ($plugin == 'groupon') {
                        $res = Groupon::refund($id,$money,$unline,$checkcode);
                    } else if ($plugin == 'bargain') {
                        $res = Bargain::refund($id,$money,$unline);
                    } else if ($plugin == 'citydelivery'){
                        $res = Citydelivery::refund($id,$money,$unline);
                    } else if ($plugin == 'activity'){
                        $res = Activity::refundorder($id,$money,$unline);
                    } else if ($plugin == 'housekeep'){
                        $res = Housekeep::refund($id,$money,$unline);
                    }else if ($plugin == 'hotel'){
                        $res = Hotel::refundorder($id,$money,$unline,$checkcode);
                    }
                    //修改售后记录
                    if($res['status']){
                        $afters = pdo_getall('wlmerchant_aftersale',array('orderid' => $id,'status'=>1,'plugin'=>$plugin),array('id','checkcodes'));
                        if(!empty($afters)){
                            if($unline == 1){
                                $refundtype = '线下转款给用户';
                            }else if($unline == 1){
                                $refundtype = '退款到用户余额';
                            }else {
                                $refundtype = '根据支付方式原路退款';
                            }
                            $journal = array(
                                'time' => time(),
                                'title' => '到账成功',
                                'detail' => '商家已退款:'.$refundtype,
                            );
                            foreach ($afters as $af){
                                $journals = Order::addjournal($journal,$af['id']);
                                $af['checkcodes'] = unserialize($af['checkcodes']);
                                if(empty($checkcode) || in_array($checkcode,$af['checkcodes'])){
                                    pdo_update('wlmerchant_aftersale',array('dotime' => time(),'status'=>2,'journal'=>$journals),array('id' => $af['id']));
                                }
                            }
                        }
                    }
                } else {
                    $res = Rush::refund($id, $money, $unline,$checkcode);
                    if($res['status']){
                        $afters = pdo_getall('wlmerchant_aftersale',array('orderid' => $id,'status'=>1,'plugin'=>'rush'),array('id','checkcodes'));
                        if(!empty($afters)){
                            if($unline == 1){
                                $refundtype = '线下转款给用户';
                            }else if($unline == 1){
                                $refundtype = '退款到用户余额';
                            }else {
                                $refundtype = '根据支付方式原路退款';
                            }
                            $journal = array(
                                'time' => time(),
                                'title' => '到账成功',
                                'detail' => '商家已退款:'.$refundtype,
                            );
                            foreach ($afters as $af){
                                $journals = Order::addjournal($journal,$af['id']);
                                $af['checkcodes'] = unserialize($af['checkcodes']);
                                if(empty($checkcode) || in_array($checkcode,$af['checkcodes'])) {
                                    pdo_update('wlmerchant_aftersale', array('dotime' => time(), 'status' => 2, 'journal' => $journals), array('id' => $af['id']));
                                }
                            }
                        }
                    }
                }
            }
            if ($res['status']) {
                show_json(1);
            } else {
                show_json(0, '退款失败：' . $res['message']);
            }
        }

        include wl_template('order/refund');
    }
    //关闭
    function close(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        if($type == 'a'){
            $res = pdo_update('wlmerchant_order',array('status' => 5),array('id' => $id));
            if($res){
                $order = pdo_get('wlmerchant_order',array('id' => $id),array('num','specid','redpackid'));
                if($order['redpackid']){
                    pdo_update('wlmerchant_redpack_records',array('status' => 0,'usetime' =>0,'orderid' => 0,'plugin' =>''),array('id' => $order['redpackid']));
                }
                if($order['specid']){
                    pdo_fetch("update" . tablename('wlmerchant_goods_option') . "SET stock=stock+{$order['num']} WHERE id = {$order['specid']}");
                }
            }
        }else{
            $res = pdo_update('wlmerchant_rush_order',array('status' => 5),array('id' => $id));
            if($res){
                $order = pdo_get('wlmerchant_rush_order',array('id' => $id),array('num','optionid','redpackid'));
                if($order['optionid']){
                    pdo_fetch("update" . tablename('wlmerchant_goods_option') . "SET stock=stock+{$order['num']} WHERE id = {$order['optionid']}");
                }
                if($order['redpackid']){
                    pdo_update('wlmerchant_redpack_records',array('status' => 0,'usetime' =>0,'orderid' => 0,'plugin' =>''),array('id' => $order['redpackid']));
                }
            }
        }
        if($res){
            show_json(1);
        }else{
            show_json(0,'完成失败，请刷新重试');
        }
    }
    //修改商家备注
    function remarksaler(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        if($type == 'a'){
            $remark = pdo_getcolumn(PDO_NAME.'order',array('id'=>$id),'remark');
        }else{
            $remark = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$id),'adminremark');
        }
        if($_W['ispost']){
            $newremark = trim($_GPC['remark']);
            if($type == 'a'){
                $res = pdo_update('wlmerchant_order',array('remark' => $newremark),array('id' => $id));
            }else{
                $res = pdo_update('wlmerchant_rush_order',array('adminremark' => $newremark),array('id' => $id));
            }
            if($res){
                show_json(1);
            }else{
                show_json(0,'备注失败，请刷新重试');
            }
        }
        include wl_template('order/remarksaler');
    }

    //生成分销订单
    public function createdisorder(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        if($type == 'a'){
            $order = pdo_get('wlmerchant_order',array('id' => $id),array('vipdiscount','goodsprice','fkid','mid','sid','expressid','expressprcie','price','plugin','specid','num','vipbuyflag','cerditmoney'));
            if($order['cerditmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                show_json(0,'积分抵扣订单无法产生分销订单');
            }
            $disprice = sprintf("%.2f",$order['goodsprice'] - $order['vipdiscount']);
            if($order['plugin'] == 'groupon'){
                $plugin = 'groupon';
                $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $order['fkid']),array('disarray','dissettime','isdistristatus'));
                if ($order['specid']>0) {
                    $option = pdo_get('wlmerchant_goods_option', array('id' => $order['specid']), array('disarray'));
                    $goods['disarray'] = WeliamWeChat::mergeDisArray($option['disarray'],$goods['disarray']);
                }
                $disarray = unserialize($goods['disarray']);
            }else if($order['plugin'] == 'wlfightgroup'){
                $plugin = 'fightgroup';
                $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $order['fkid']),array('disarray','dissettime','isdistristatus'));
                if ($order['specid']>0) {
                    $option = pdo_get('wlmerchant_goods_option', array('id' => $order['specid']), array('disarray'));
                    $goods['disarray'] = WeliamWeChat::mergeDisArray($option['disarray'],$goods['disarray']);
                }
                $disarray = unserialize($goods['disarray']);
            }else if($order['plugin'] == 'coupon'){
                $plugin = 'coupon';
                $goods = pdo_get('wlmerchant_couponlist',array('id' => $order['fkid']),array('disarray','dissettime','isdistristatus'));
                $disarray = unserialize($goods['disarray']);
            }else if($order['plugin'] == 'bargain'){
                $plugin = 'bargain';
                $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $order['fkid']),array('disarray','dissettime','isdistristatus'));
                $disarray = unserialize($goods['disarray']);
            }else if($order['plugin'] == 'citydelivery'){
                $plugin = 'citydelivery';
                $sdisarray = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('deliverydisstatus','onescale','twoscale'));
                $disarray = 0;
                if($sdisarray['deliverydisstatus'] > 0 ){
                    if($sdisarray['onescale'] > 0){
                        $onemoney = sprintf("%.2f",$disprice * $sdisarray['onescale'] / 100);
                    }
                    if($sdisarray['twoscale'] > 0){
                        $twomoney = sprintf("%.2f",$disprice * $sdisarray['twoscale'] / 100);
                    }
                }
            }else if($order['plugin'] == 'activity'){
                $plugin = 'activity';
                $goods = pdo_get('wlmerchant_activitylist',array('id' => $order['fkid']),array('disarray','dissettime','isdistristatus'));
                if ($order['specid']>0) {
                    $option = pdo_get('wlmerchant_activity_spec', array('id' => $order['specid']), array('disarray'));
                    $goods['disarray'] = WeliamWeChat::mergeDisArray($option['disarray'],$goods['disarray']);
                }
                $disarray = unserialize($goods['disarray']);
            }else if($order['plugin'] == 'hotel'){
                $plugin = 'hotel';
                $goods = pdo_get('wlmerchant_hotel_room',array('id' => $order['fkid']),array('disarray','dissettime','isdistristatus'));
                $disarray = unserialize($goods['disarray']);
            }
        }else{
            //抢购
            $order = pdo_get('wlmerchant_rush_order',array('id' => $id),array('activityid','mid','price','expressid','discount','optionid','actualprice','num','vipbuyflag','dkmoney'));
            if($order['dkmoney'] > 0 && $_W['wlsetting']['creditset']['nodistribution'] > 0){
                show_json(0,'积分抵扣订单无法产生分销订单');
            }
            $plugin = 'rush';
        }
        //判断分销商资格
        $distributorid = pdo_getcolumn(PDO_NAME.'member',array('uniacid'=>$_W['uniacid'],'id'=>$order['mid']),'distributorid');
        if(empty($distributorid)){
            show_json(0,'该用户无分销上级，无法生成分销订单');
        }else{
            $distributor = pdo_get(PDO_NAME.'distributor',array('uniacid'=>$_W['uniacid'],'id'=>$distributorid),array('leadid','disflag','dislevel'));
            if($distributor['disflag']){
                $mleveid = $distributor['dislevel'];
                if(empty($mleveid)){
                    $mleveid = pdo_getcolumn('wlmerchant_dislevel',array('uniacid' =>$_W['uniacid'],'isdefault'=>1),'id');
                }
                $memberlevel = pdo_get(PDO_NAME.'dislevel',array('id'=>$mleveid),array('ownstatus'));
            }
            if(empty($distributor['leadid']) && empty($memberlevel['ownstatus'])){
                show_json(0,'该用户无分销上级，无法生成分销订单');
            }
        }
        if(empty($plugin)){
            show_json(0,'订单插件错误，请联系管理员');
        }
        if($plugin == 'rush' || $plugin == 'fightgroup' || $plugin == 'coupon' || $plugin == 'citydelivery'){
            $disorderid = Distribution::newDisCore($id,$plugin);
        }else{
            $disorderid = Distribution::disCore($order['mid'],$disprice,$disarray,$order['num'],0,$id,$plugin,$goods['dissettime'],$goods['isdistristatus']);
        }
        if($disorderid){
            if($type == 'a'){
                $res = pdo_update('wlmerchant_order',array('disorderid' => $disorderid),array('id' => $id));
            }else{
                $res = pdo_update('wlmerchant_rush_order',array('disorderid' => $disorderid),array('id' => $id));
            }
            if($res){
                show_json(1);
            }else{
                show_json(0,'关联分销订单失败，请联系管理员');
            }
        }else{
            show_json(0,'该订单无法生成分销订单。');
        }


    }


    /**
     * Comment: 根据条件获取订单信息
     * Author: zzw
     * @param $where   条件一 查询rush_order表使用
     * @param $where2  条件二 查询order表使用
     * @return array
     */
    static protected function getOrderInfo($where,$where2,$limit){
        $sql = 'SELECT a.id,a.moinfo,a.mobile,a.createtime,a.sid,a.status,a.paidprid,a.mid,a.orderno,a.num,a.price,a.paytype,a.vipbuyflag,a.paytime,a.changedispatchprice,a.changeprice,a.disorderid,a.applyrefund, "a",
            f.qrcode as fqrcode,
            g.qrcode as gqrcode,
            `c`.concode as cqrcode,
            b.qrcode as bqrcode FROM '.tablename(PDO_NAME."order")
            ." a LEFT JOIN ".tablename(PDO_NAME."fightgroup_userecord")." f ON a.id = f.orderid AND a.plugin = 'wlfightgroup'"
            ." LEFT JOIN ".tablename(PDO_NAME."groupon_userecord")." g ON a.id = g.orderid AND a.plugin = 'groupon'"
            ." LEFT JOIN ".tablename(PDO_NAME."member_coupons")." `c` ON a.orderno = `c`.orderno AND a.plugin = 'coupon'"
            ." LEFT JOIN ".tablename(PDO_NAME."bargain_userlist")." b ON a.id = b.orderid AND a.plugin = 'bargain' {$where2} "
            .' UNION ALL SELECT id,moinfo,mobile,createtime,sid,status,paidprid,mid,orderno,num,actualprice as price,paytype,vipbuyflag,paytime,changedispatchprice,changeprice,disorderid,applyrefund, "b","rush","rush","rush","rush" FROM '
            .tablename(PDO_NAME."rush_order")." {$where} ORDER BY createtime DESC {$limit}";
        $orderlist = pdo_fetchall($sql);

        return $orderlist;
    }

    /**
     * Comment: 储存批量发货的.cvs文件  然后返回文件名称
     * Author: zzw
     */
    public function bulkShipment(){
        global $_W, $_GPC;
        #1、将获取表格文件存放进入临时存储文件 resource/name
        $imageName = "excel" . time() . rand(1000, 9999) . '.csv';
        $imageName = "images/" . MODULE_NAME . "/" . $imageName;//文件储存路径
        $fullName = PATH_ATTACHMENT . $imageName;//文件在本地服务器暂存地址
        $res = move_uploaded_file($_FILES['file']['tmp_name'], $fullName);
        if (!$res) {
            wl_json(0, '操作失败，文件上传错误');
        }
        wl_json(1, '文件上传成功,正在处理信息...', $imageName);
    }
    /**
     * Comment: 批量发货 并且返回结果信息表
     * Author: zzw
     */
    public function batchSend(){
        global $_W,$_GPC;
        #1、将获取基本信息
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
            //3-3 订单状态不为待收货 不进行发货操作
            if (trim($v[9]) != '待发货') {
                $v['send_result'] = '不进行发货操作';
                continue;
            }
            //3-4 获取需要的信息
            $orderType = trim($v[2]);//订单类型
            $orderId = trim($v[0]);//订单id
            $expressName = trim($v[19]);//物流公司
            $expressNum = trim($v[20]);//物流快递号
            $sendResult = '发货成功';//当前订单发货结果
            //3-5 获取快递信息表的id
            if ($orderType == '抢购') {
                $expressId = pdo_getcolumn(PDO_NAME . "rush_order", array('id' => $orderId), 'expressid');
            } else if ($orderType == '拼团' || $orderType == '团购' || $orderType == '卡券' || $orderType == '砍价') {
                $expressId = pdo_getcolumn(PDO_NAME . "order", array('id' => $orderId), 'expressid');
            } else {
                $sendResult = '发货失败,仅支持抢购、拼团、团购、卡券、砍价商品的批量发货';
            }
            //3-6 获取发货物流公司的英文名称
            $expressEName = Logistics::codeComparisonTable($expressName,'name');
            $expressEName = $expressEName['alias'];
            if (!$expressEName) {
                $sendResult = '不支持使用当前快递公司发货!';
            }
            //3-7 进行发货操作
            if ($expressName && $expressId) {
                $expressDate['expressname'] = $expressEName;//物流名称
                $expressDate['expresssn'] = $expressNum;//物流单号
                $expressDate['orderid'] = $orderId;//订单id
                $expressDate['sendtime'] = time();//发货时间
                //修改物流信息表
                $updateRes = pdo_update(PDO_NAME."express",$expressDate,array('id'=>$expressId));
                //修改订单信息
                if ($orderType == '抢购') {
                    $UstateRes = pdo_update(PDO_NAME.'rush_order', array('status' => 4), array('id' => $orderId));
                    News::sendremind($orderId,'b'); //发货提醒
                } else if ($orderType == '拼团' || $orderType == '团购' || $orderType == '卡券' || $orderType == '砍价') {
                    $UstateRes = pdo_update(PDO_NAME.'order', array('status' => 4), array('id' => $orderId));
                    News::sendremind($orderId,'a'); //发货提醒
                }
                if ($updateRes && $UstateRes) {
                    $sendResult = '发货成功';
                } else {
                    $sendResult = '发货失败，请检查信息是否填写正确或是否已发货!';
                }
            }
            unset($v[22]);unset($v[23]);unset($v[24]);unset($v[25]);
            unset($v[26]);unset($v[27]);unset($v[28]);unset($v[29]);
            unset($v[30]);unset($v[31]);unset($v[32]);
            //3-8 进行发货结果的拼写
            $v['send_result'] = $sendResult;
        }
        #4、定义结果表格的标题
        $filter = array(
            0 => '订单id',
            1 => '订单号',
            2 => '所属应用',
            3 => '商品名称',
            4 => '规格名称',
            5 => '数量',
            6 => '所属商家',
            7 => '买家昵称',
            8 => '买家电话',
            9 => '订单状态',
            10 => '支付方式',
            11 => '下单时间',
            12 => '支付时间',
            13 => '实付金额',
            14 => '买家备注',
            15 => '卖家备注',
            16 => '收货人姓名',
            17 => '收货人电话',
            18 => '收货人地址',
            19 => '物流公司',
            20 => '快递单号',
            21 => '快递运费',
            'send_result' => '发货结果',
        );
        #5、返回批量发货的结果信息表
        util_csv::export_csv_2($info, $filter, '批量发货结果信息.csv');
    }

    /**
     * Comment: 完成配送订单
     * Author: wlf
     * Date: 2019/04/22 18:31
     */
    public function finishdelivery(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $order = pdo_get('wlmerchant_order',array('id' => $id),array('orderno','expressid'));
        $res = pdo_update('wlmerchant_order',array('status' => 2,'deliverytype' => 3),array('id' => $id));
        if($res){
            pdo_update('wlmerchant_delivery_order',array('status' => 2,'dotime' => time()),array('tid' => $order['orderno']));
            $setres = Store::ordersettlement($id);
            if($order['expressid']){
                pdo_update('wlmerchant_express',array('receivetime' => time()),array('id' => $order['expressid']));
            }
            show_json(1,'处理成功');
        }else{
            show_json(0,'修改状态失败，请刷新重试');
        }
    }

    /**
     * Comment: 核销码列表
     * Author: wlf
     * Date: 2019/07/22 09:43
     */
    public function checkcodeList(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $plugin = $_GPC['plugin'];
        $type = $_GPC['type'] ? $_GPC['type'] : 1;    // 1核销码 2其他订单
        $status = $_GPC['status'] ? $_GPC['status'] : 1; //1已下单 2已支付 3已完成
        $pindex = max(1,intval($_GPC['page']));
        switch ($plugin){
            case 'rush':
                $pluginno = 1;
                break;
            case 'wlfightgroup':
                $pluginno = 2;
                break;
            case 'groupon':
                $pluginno = 10;
                break;
            case 'bargain':
                $pluginno = 12;
                break;
            case 'coupon':
                $pluginno = 3;
                break;
            case 'activity':
                $pluginno = 9;
                break;
        }
        if(empty($id)){
            wl_message('无商品信息', referer(),'error');
        }
        if(empty($plugin)){
            wl_message('无商品类型', referer(),'error');
        }
        //商品信息
        if($plugin == 'rush'){
            $goodinfo = pdo_get('wlmerchant_rush_activity',array('id' => $id),array('name','thumb','sid','pftid','threestatus'));
            $goodinfo['thumb'] = tomedia($goodinfo['thumb']);
        }else if($plugin == 'groupon'){
            $goodinfo = pdo_get('wlmerchant_groupon_activity',array('id' => $id),array('name','thumb','sid','pftid','threestatus'));
            $goodinfo['thumb'] = tomedia($goodinfo['thumb']);
        }else if($plugin == 'wlfightgroup'){
            $goodinfo = pdo_get('wlmerchant_fightgroup_goods',array('id' => $id),array('name','logo','merchantid'));
            $goodinfo['sid'] = $goodinfo['merchantid'];
            $goodinfo['thumb'] = tomedia($goodinfo['logo']);
        }else if($plugin == 'bargain'){
            $goodinfo = pdo_get('wlmerchant_bargain_activity',array('id' => $id),array('name','thumb','sid'));
            $goodinfo['thumb'] = tomedia($goodinfo['thumb']);
        }else if($plugin == 'coupon'){
            $goodinfo = pdo_get('wlmerchant_couponlist',array('id' => $id),array('title','logo','merchantid'));
            $goodinfo['name'] = $goodinfo['title'];
            $goodinfo['sid'] = $goodinfo['merchantid'];
            $goodinfo['thumb'] = tomedia($goodinfo['logo']);
        }else if($plugin == 'activity'){
            $goodinfo = pdo_get('wlmerchant_activitylist',array('id' => $id),array('title','thumb','sid'));
            $goodinfo['name'] = $goodinfo['title'];
            $goodinfo['thumb'] = tomedia($goodinfo['thumb']);
        }
        //商户信息
        if($goodinfo['sid'] > 0){
            $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $goodinfo['sid']),array('storename','logo'));
            $merchant['logo'] = tomedia($merchant['logo']);
        }else if(empty($goodinfo['threestatus'])){
            $merchant['storename'] = '票付通平台商品';
            $merchant['logo'] = tomedia($_W['wlsetting']['base']['name']);
        }else if($goodinfo['threestatus'] == 1){
            $merchant['storename'] = '亿奇达平台商品';
            $merchant['logo'] = tomedia($_W['wlsetting']['base']['name']);
        }else{
            $merchant['storename'] = '其他平台商品';
            $merchant['logo'] = tomedia($_W['wlsetting']['base']['name']);
        }


        //核销码列表
        if($type == 1){
            $where = array(
                'uniacid' => $_W['uniacid'],
                'gid'     => $id,
                'plugin'  => $plugin
            );
            if($status == 1 || $status == 2){
                $where['status#'] = '(1,2,4)';
            }else if($status == 3){
                $where['status'] = 2;
            }else if($status == 4){
                $where['status'] = 3;
            }
            //条件筛选
            if(!empty($_GPC['keyword'])){
                $keyword = trim($_GPC['keyword']);
                $keywordtype = $_GPC['keywordtype'];
                switch ($keywordtype){
                    case '1':
                        $where['orderno@'] = $keyword;
                        break;
                    case '2':
                        $where['checkcode@'] = $keyword;
                        break;
                    case '3':
                        $params[':name'] = "%{$keyword}%";
                        $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :name",$params);
                        if($members){
                            $mids = "(";
                            foreach ($members as $key => $v) {
                                if($key == 0){
                                    $mids.= $v['id'];
                                }else{
                                    $mids.= ",".$v['id'];
                                }
                            }
                            $mids.= ")";
                            $where['mid#'] = "{$mids}";
                        }
                        break;
                    case '4':
                        $where['mid'] = $keyword;
                        break;
                    case '5':
                        $where['specid'] = $keyword;
                        break;
                }
            }
            //时间筛选

            if(!empty($_GPC['timetype'])){
                $timetype = $_GPC['timetype'];
                $starttime = strtotime($_GPC['time_limit']['start']);
                $endtime = strtotime($_GPC['time_limit']['end']);
                if($timetype == 1){
                    $where['createtime>'] = $starttime;
                    $where['createtime<'] = $endtime;
                }else if($timetype == 2){
                    $where['hexiaotime>'] = $starttime;
                    $where['hexiaotime<'] = $endtime;
                }
            }
            if (empty($starttime) || empty($endtime)) {
                $starttime = strtotime('-1 month');
                $endtime = time();
            }
            //导出
            if($_GPC['export']){
                $this -> exportCheckcodeList($where,1);
            }

            $lists = Util::getNumData('orderno,orderid,createtime,mid,status,specid,hexiaotime,hexiaotype,hxuid,checkcode,orderprice,plugin','wlmerchant_smallorder',$where,'id DESC',$pindex,20,1);
            $list = $lists[0];
            $pager = $lists[1];
            $tatal = $lists[2];
            foreach($list as $k => &$li){
                $member = pdo_get(PDO_NAME.'member',array('id'=>$li['mid']),array('nickname','avatar'));
                $li['nickname'] = $member['nickname'];
                $li['avatar'] = tomedia($member['avatar']);
                if($li['specid'] > 0){
                    if($li['plugin'] == 'activity'){
                        $li['specname'] = pdo_getcolumn(PDO_NAME.'activity_spec',array('uniacid'=>$_W['uniacid'],'id'=>$li['specid']),'name');
                    }else{
                        $li['specname'] = pdo_getcolumn(PDO_NAME.'goods_option',array('uniacid'=>$_W['uniacid'],'id'=>$li['specid']),'title');
                    }
                    $li['specname'] = $li['specname'] ."(spid:".$li['specid'].")";
                }else{
                    $li['specname'] = "- 无 -";
                }
                if($li['hexiaotime'] > 0){
                    $li['hexiaotime'] = date("Y-m-d H:i:s",$li['hexiaotime']);
                }
                $li['createtime'] = date("Y-m-d H:i:s",$li['createtime']);
                switch ($li['status']){
                    case '1':
                        $li['statuscss'] = 'warning';
                        $li['statustext'] = '待核销';
                        break;
                    case '2':
                        $li['statuscss'] = 'success';
                        $li['statustext'] = '已核销';
                        break;
                    case '3':
                        $li['statuscss'] = 'defualt';
                        $li['statustext'] = '已退款';
                        break;
                    case '4':
                        $li['statuscss'] = 'danger';
                        $li['statustext'] = '申请退款中';
                        break;
                }
                switch ($li['hexiaotype']){
                    case '1':
                        $li['hxtypecss'] = 'defualt';
                        $li['hxtypetext'] = '输码核销';
                        break;
                    case '2':
                        $li['hxtypecss'] = 'success';
                        $li['hxtypetext'] = '扫码核销';
                        break;
                    case '3':
                        $li['hxtypecss'] = 'warning';
                        $li['hxtypetext'] = '后台核销';
                        break;
                    case '4':
                        $li['hxtypecss'] = 'info';
                        $li['hxtypetext'] = '密码核销';
                        break;
                }
                $hxuser = pdo_get(PDO_NAME.'merchantuser',array('id'=>$li['hxuid']),array('name','mid'));
                $hxuserimg = pdo_getcolumn(PDO_NAME.'member',array('id'=>$hxuser['mid']),'avatar');
                $li['hxuser'] = $hxuser['name'];
                $li['hxuserimg'] = tomedia($hxuserimg);
                $li['orderurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$li['orderid'],'type'=>$pluginno));
            }
        }
        else if($type == 2){
            $pageStart = $pindex * 20 - 20 ;
            //条件筛选
            if($plugin == 'rush'){
                $where = " a.uniacid = {$_W['uniacid']} AND a.activityid = {$id} ";
            }else{
                $where = " a.uniacid = {$_W['uniacid']} AND a.fkid = {$id} ";
                if($plugin == 'group'){
                    $where .= " AND a.plugin = 'group' ";
                }else if($plugin == 'wlfightgroup'){
                    $where .= " AND a.plugin = 'wlfightgroup' ";
                }else if($plugin == 'bargain'){
                    $where .= " AND a.plugin = 'bargain' ";
                }
            }
            if(!empty($_GPC['keyword'])){
                $keyword = trim($_GPC['keyword']);
                $keywordtype = $_GPC['keywordtype'];
                switch ($keywordtype){
                    case '1':
                        $where .= " AND a.orderno LIKE '%{$keyword}%' ";
                        break;
                    case '3':
                        $where .= " AND a.mid = '{$keyword}' ";
                        break;
                    case '2':
                        $params[':name'] = "%{$keyword}%";
                        $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :name",$params);
                        if($members){
                            $mids = "(";
                            foreach ($members as $key => $v) {
                                if($key == 0){
                                    $mids.= $v['id'];
                                }else{
                                    $mids.= ",".$v['id'];
                                }
                            }
                            $mids.= ")";
                            $where .= " AND a.mid IN '{$mids}' ";
                        }
                        break;
                    case '4':
                        if($plugin == 'rush'){
                            $where .= " AND a.optionid = '{$keyword}' ";
                        }else{
                            $where .= " AND a.specid = '{$keyword}' ";
                        }
                        break;
                }
            }
            //时间筛选
            if(!empty($_GPC['timetype'])){
                $timetype = $_GPC['timetype'];
                $starttime = strtotime($_GPC['time_limit']['start']);
                $endtime = strtotime($_GPC['time_limit']['end']);
                if($timetype == 1){
                    $where .= " AND a.paytime > '{$starttime}' ";
                    $where .= " AND a.paytime < '{$endtime}' ";
                }else if($timetype == 3){
                    $where .= " AND b.sendtime > '{$starttime}' ";
                    $where .= " AND b.sendtime < '{$endtime}' ";
                }else if($timetype == 4){
                    $where .= " AND b.receivetime > '{$starttime}' ";
                    $where .= " AND b.receivetime < '{$endtime}' ";
                }
            }
            if (empty($starttime) || empty($endtime)) {
                $starttime = strtotime('-1 month');
                $endtime = time();
            }

            //查询全部
            if($status == 1){
                $where .= " AND (a.status IN (0,4,8) OR (a.status IN (2,3) ))";
            }else if($status == 2){
                $where .= " AND (a.status IN (4,8) OR (a.status IN (2,3) ))";
            }else if($status == 3){
                $where .= " AND a.status IN (2,3) ";
            }else if($status == 4){
                $where .= " AND a.status = 7 ";
            }
            if(empty($goodinfo['pftid'])){
                $where .= " AND a.expressid > 0";
            }
            if($plugin == 'rush'){
                //导出
                if($_GPC['export']){
                    $this -> exportCheckcodeList($where,2);
                }

                $tatolPage = pdo_fetchcolumn("SELECT count(a.id) FROM " . tablename(PDO_NAME . "rush_order")
                    . " as a LEFT JOIN " . tablename(PDO_NAME . "express")
                    . " as b ON a.expressid = b.id WHERE {$where}");

                $tatal = pdo_fetchcolumn("SELECT SUM(a.num) FROM " . tablename(PDO_NAME . "rush_order")
                    . " as a LEFT JOIN " . tablename(PDO_NAME . "express")
                    . " as b ON a.expressid = b.id WHERE {$where}");

                $tatal = !empty($tatal)?$tatal:0;
                $pager = wl_pagination($tatolPage,$pindex,20);

                $list = pdo_fetchall("SELECT a.mid,a.status,a.id,a.paytime,a.orderno,a.optionid,a.createtime,a.actualprice,a.num,b.sendtime,b.receivetime FROM " . tablename(PDO_NAME . "rush_order")
                    . " as a LEFT JOIN " . tablename(PDO_NAME . "express")
                    . " as b ON a.expressid = b.id WHERE {$where} ORDER BY a.id DESC LIMIT {$pageStart},20");

                foreach($list as $k => &$li){
                    $member = pdo_get(PDO_NAME.'member',array('id'=>$li['mid']),array('nickname','avatar'));
                    $li['nickname'] = $member['nickname'];
                    $li['avatar'] = tomedia($member['avatar']);
                    if($li['optionid'] > 0){
                        $li['specname'] = pdo_getcolumn(PDO_NAME.'goods_option',array('uniacid'=>$_W['uniacid'],'id'=>$li['optionid']),'title');
                        $li['specname'] = $li['specname'] ."(spid:".$li['optionid'].")";
                    }else{
                        $li['specname'] = "- 无 -";
                    }
                    if($li['sendtime'] > 0){
                        $li['sendtime'] = date("Y-m-d H:i:s",$li['sendtime']);
                    }
                    if($li['receivetime'] > 0){
                        $li['receivetime'] = date("Y-m-d H:i:s",$li['receivetime']);
                    }
                    if($li['paytime'] > 0){
                        $li['paytime'] = date("Y-m-d H:i:s",$li['paytime']);
                    }
                    $li['createtime'] = date("Y-m-d H:i:s",$li['createtime']);
                    $li['price'] = $li['actualprice'];
                    switch ($li['status']){
                        case '0':
                            $li['statuscss'] = 'defualt';
                            $li['statustext'] = '未支付';
                            break;
                        case '2':
                            $li['statuscss'] = 'success';
                            $li['statustext'] = '已收货';
                            break;
                        case '3':
                            $li['statuscss'] = 'success';
                            $li['statustext'] = '已收货';
                            break;
                        case '4':
                            $li['statuscss'] = 'warning';
                            $li['statustext'] = '待收货';
                            break;
                        case '7':
                            $li['statuscss'] = 'defualt';
                            $li['statustext'] = '已退款';
                            break;
                        case '8':
                            $li['statuscss'] = 'info';
                            $li['statustext'] = '待发货';
                            break;
                    }
                    $li['orderurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$li['id'],'type'=>$pluginno));
                }
            }else{
                //导出
                if($_GPC['export']){
                    $this -> exportCheckcodeList($where,3);
                }
                $tatolPage = pdo_fetchcolumn("SELECT count(a.id) FROM " . tablename(PDO_NAME . "order")
                    . " as a LEFT JOIN " . tablename(PDO_NAME . "express")
                    . " as b ON a.expressid = b.id WHERE {$where}");

                $tatal = pdo_fetchcolumn("SELECT SUM(a.num) FROM " . tablename(PDO_NAME . "order")
                    . " as a LEFT JOIN " . tablename(PDO_NAME . "express")
                    . " as b ON a.expressid = b.id WHERE {$where}");

                $tatal = !empty($tatal)?$tatal:0;
                $pager = wl_pagination($tatolPage,$pindex,20);

                $list = pdo_fetchall("SELECT a.mid,a.status,a.id,a.paytime,a.orderno,a.specid,a.createtime,a.price,a.num,b.sendtime,b.receivetime FROM " . tablename(PDO_NAME . "order")
                    . " as a LEFT JOIN " . tablename(PDO_NAME . "express")
                    . " as b ON a.expressid = b.id WHERE {$where} ORDER BY a.id DESC LIMIT {$pageStart},20");

                foreach($list as $k => &$li){
                    $member = pdo_get(PDO_NAME.'member',array('id'=>$li['mid']),array('nickname','avatar'));
                    $li['nickname'] = $member['nickname'];
                    $li['avatar'] = tomedia($member['avatar']);
                    if($li['specid'] > 0){
                        $li['specname'] = pdo_getcolumn(PDO_NAME.'goods_option',array('uniacid'=>$_W['uniacid'],'id'=>$li['specid']),'title');
                        $li['specname'] = $li['specname'] ."(spid:".$li['specid'].")";
                    }else{
                        $li['specname'] = "- 无 -";
                    }
                    if($li['sendtime'] > 0){
                        $li['sendtime'] = date("Y-m-d H:i:s",$li['sendtime']);
                    }
                    if($li['receivetime'] > 0){
                        $li['receivetime'] = date("Y-m-d H:i:s",$li['receivetime']);
                    }
                    if($li['paytime'] > 0){
                        $li['paytime'] = date("Y-m-d H:i:s",$li['paytime']);
                    }
                    $li['createtime'] = date("Y-m-d H:i:s",$li['createtime']);
                    switch ($li['status']){
                        case '0':
                            $li['statuscss'] = 'defualt';
                            $li['statustext'] = '未支付';
                            break;
                        case '2':
                            $li['statuscss'] = 'success';
                            $li['statustext'] = '已收货';
                            break;
                        case '3':
                            $li['statuscss'] = 'success';
                            $li['statustext'] = '已收货';
                            break;
                        case '4':
                            $li['statuscss'] = 'warning';
                            $li['statustext'] = '待收货';
                            break;
                        case '8':
                            $li['statuscss'] = 'info';
                            $li['statustext'] = '待发货';
                            break;
                    }
                    $li['orderurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$li['id'],'type'=>$pluginno));
                }
            }
        }
        else{
            $pageStart = $pindex * 20 - 20 ;
            //条件筛选
            $where = " a.uniacid = {$_W['uniacid']} AND a.fkid = {$id} AND a.status = 1 AND a.fightgroupid > 0 AND b.status = 1 AND plugin = 'wlfightgroup' ";
            if(!empty($_GPC['keyword'])){
                $keyword = trim($_GPC['keyword']);
                $keywordtype = $_GPC['keywordtype'];
                switch ($keywordtype){
                    case '1':
                        $where .= " AND a.orderno LIKE '%{$keyword}%' ";
                        break;
                    case '3':
                        $where .= " AND a.mid = '{$keyword}' ";
                        break;
                    case '2':
                        $params[':name'] = "%{$keyword}%";
                        $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :name",$params);
                        if($members){
                            $mids = "(";
                            foreach ($members as $key => $v) {
                                if($key == 0){
                                    $mids.= $v['id'];
                                }else{
                                    $mids.= ",".$v['id'];
                                }
                            }
                            $mids.= ")";
                            $where .= " AND a.mid IN '{$mids}' ";
                        }
                        break;
                    case '4':
                        $where .= " AND a.specid = '{$keyword}' ";
                        break;
                }
            }
            //时间筛选
            if(!empty($_GPC['timetype'])){
                $timetype = $_GPC['timetype'];
                $starttime = strtotime($_GPC['time_limit']['start']);
                $endtime = strtotime($_GPC['time_limit']['end']);
                if($timetype == 1){
                    $where .= " AND a.paytime > '{$starttime}' ";
                    $where .= " AND a.paytime < '{$endtime}' ";
                }else if($timetype == 5){
                    $where .= " AND b.failtime > '{$starttime}' ";
                    $where .= " AND b.failtime < '{$endtime}' ";
                }
            }
            if (empty($starttime) || empty($endtime)) {
                $starttime = strtotime('-1 month');
                $endtime = time();
            }

            //导出
            if($_GPC['export']){
                $this -> exportCheckcodeList($where,4);
            }

            $tatolPage = pdo_fetchcolumn("SELECT count(a.id) FROM " . tablename(PDO_NAME . "order")
                . " as a LEFT JOIN " . tablename(PDO_NAME . "fightgroup_group")
                . " as b ON a.fightgroupid = b.id WHERE {$where}");

            $tatal = pdo_fetchcolumn("SELECT SUM(a.num) FROM " . tablename(PDO_NAME . "order")
                . " as a LEFT JOIN " . tablename(PDO_NAME . "fightgroup_group")
                . " as b ON a.fightgroupid = b.id WHERE {$where}");

            $tatal = !empty($tatal)?$tatal:0;
            $pager = wl_pagination($tatolPage,$pindex,20);

            $list = pdo_fetchall("SELECT a.mid,a.status,a.id,a.paytime,a.orderno,a.specid,a.createtime,a.price,a.num,b.failtime FROM " . tablename(PDO_NAME . "order")
                . " as a LEFT JOIN " . tablename(PDO_NAME . "fightgroup_group")
                . " as b ON a.fightgroupid = b.id WHERE {$where} ORDER BY a.id DESC LIMIT {$pageStart},20");

            foreach($list as $k => &$li){
                $member = pdo_get(PDO_NAME.'member',array('id'=>$li['mid']),array('nickname','avatar'));
                $li['nickname'] = $member['nickname'];
                $li['avatar'] = tomedia($member['avatar']);
                if($li['specid'] > 0){
                    $li['specname'] = pdo_getcolumn(PDO_NAME.'goods_option',array('uniacid'=>$_W['uniacid'],'id'=>$li['specid']),'title');
                    $li['specname'] = $li['specname'] ."(spid:".$li['specid'].")";
                }else{
                    $li['specname'] = "- 无 -";
                }
                if($li['failtime'] > 0){
                    $li['failtime'] = date("Y-m-d H:i:s",$li['failtime']);
                }
                if($li['paytime'] > 0){
                    $li['paytime'] = date("Y-m-d H:i:s",$li['paytime']);
                }
                $li['createtime'] = date("Y-m-d H:i:s",$li['createtime']);
                $li['statuscss'] = 'warning';
                $li['statustext'] = '组团中';

                $li['orderurl'] = web_url("order/wlOrder/orderdetail",array('orderid'=>$li['id'],'type'=>$pluginno));
            }
        }
        include  wl_template('order/checkcodeList');
    }


    /**
     * Comment: 导出核销码列表
     * Author: wlf
     * Date: 2019/07/24 16:46
     */
    public function exportCheckcodeList($where,$type){
        global $_W,$_GPC;
        if($type == 1){
            $lists = Util::getNumData('orderno,orderid,createtime,mid,status,specid,hexiaotime,hexiaotype,hxuid,checkcode,orderprice,plugin','wlmerchant_smallorder',$where,'id DESC',0,0,0);
            $list = $lists[0];
            foreach($list as $k => &$li){
                $member = pdo_get(PDO_NAME.'member',array('id'=>$li['mid']),array('nickname','avatar'));
                $li['nickname'] = $member['nickname'];
                if($li['specid'] > 0){
                    if($li['plugin'] == 'activity'){
                        $li['specname'] = pdo_getcolumn(PDO_NAME.'activity_spec',array('uniacid'=>$_W['uniacid'],'id'=>$li['specid']),'name');
                    }else {
                        $li['specname'] = pdo_getcolumn(PDO_NAME . 'goods_option', array('uniacid' => $_W['uniacid'], 'id' => $li['specid']), 'title');
                    }
                    $li['specname'] = $li['specname'] ."(spid:".$li['specid'].")";
                }else{
                    $li['specname'] = "- 无 -";
                }
                if($li['hexiaotime'] > 0){
                    $li['hexiaotime'] = date("Y-m-d H:i:s",$li['hexiaotime']);
                }
                $li['createtime'] = date("Y-m-d H:i:s",$li['createtime']);
                switch ($li['status']){
                    case '1':
                        $li['statustext'] = '待核销';
                        break;
                    case '2':
                        $li['statustext'] = '已核销';
                        break;
                    case '3':
                        $li['statustext'] = '已退款';
                        break;
                    case '4':
                        $li['statustext'] = '申请退款中';
                        break;
                }
                switch ($li['hexiaotype']){
                    case '1':
                        $li['hxtypetext'] = '输码核销';
                        break;
                    case '2':
                        $li['hxtypetext'] = '扫码核销';
                        break;
                    case '3':
                        $li['hxtypetext'] = '后台核销';
                        break;
                    case '4':
                        $li['hxtypetext'] = '密码核销';
                        break;
                    default:
                        $li['hxtypetext'] = '未核销';
                        break;
                }
                $hxuser = pdo_get(PDO_NAME.'merchantuser',array('id'=>$li['hxuid']),array('name','mid'));
                $hxuserimg = pdo_getcolumn(PDO_NAME.'member',array('id'=>$hxuser['mid']),'avatar');
                $li['hxuser'] = $hxuser['name'];
                $li['orderno'] = "\t".$li['orderno']."\t";
            }
            /* 输出表头 */
            $filter = array(
                'orderno'    => '订单编号',
                'specname'   => '规格',
                'nickname'   => '用户昵称',
                'mid'        => '用户MID',
                'checkcode'  => '核销码',
                'orderprice' => '订单金额',
                'statustext' => '状态',
                'hexiaotime' => '核销时间',
                'hxtypetext' => '核销方式',
                'hxuser'     => '核销员',
                'createtime' => '创建时间',
            );
            $data = array();
            for ($i=0; $i < count($list) ; $i++) {
                foreach ($filter as $key => $title) {
                    if(!empty($list[$i][$key])){
                        $data[$i][$key] = $list[$i][$key];
                    }else{
                        $data[$i][$key] = "- 无 -";
                    }
                }
            }
            util_csv::export_csv_2($data,$filter, '核销码列表.csv');
            exit;
        }
        else if($type == 4){
            $list = pdo_fetchall("SELECT a.mid,a.status,a.id,a.paytime,a.orderno,a.specid,a.createtime,a.price,a.num,b.failtime FROM " . tablename(PDO_NAME . "order")
                . " as a LEFT JOIN " . tablename(PDO_NAME . "fightgroup_group")
                . " as b ON a.fightgroupid = b.id WHERE {$where} ");

            foreach($list as $k => &$li){
                $member = pdo_get(PDO_NAME.'member',array('id'=>$li['mid']),array('nickname','avatar'));
                $li['nickname'] = $member['nickname'];
                if($li['specid'] > 0){
                    $li['specname'] = pdo_getcolumn(PDO_NAME.'goods_option',array('uniacid'=>$_W['uniacid'],'id'=>$li['specid']),'title');
                    $li['specname'] = $li['specname'] ."(spid:".$li['specid'].")";
                }else{
                    $li['specname'] = "- 无 -";
                }
                if($li['failtime'] > 0){
                    $li['failtime'] = date("Y-m-d H:i:s",$li['failtime']);
                }
                if($li['paytime'] > 0){
                    $li['paytime'] = date("Y-m-d H:i:s",$li['paytime']);
                }
                $li['createtime'] = date("Y-m-d H:i:s",$li['createtime']);
                $li['statustext'] = '组团中';
                $li['orderno'] = "\t".$li['orderno']."\t";
            }
            /* 输出表头 */
            $filter = array(
                'orderno' => '订单编号',
                'specname' => '规格',
                'nickname'  => '用户昵称',
                'mid'        => '用户MID',
                'num' => '商品数量',
                'price' => '订单金额',
                'statustext' => '状态',
                'paytime' => '支付时间',
                'failtime' => '预计失败时间',
                'createtime' => '下单时间',
            );
            $data = array();
            for ($i=0; $i < count($list) ; $i++) {
                foreach ($filter as $key => $title) {
                    if(!empty($list[$i][$key])){
                        $data[$i][$key] = $list[$i][$key];
                    }else{
                        $data[$i][$key] = "- 无 -";
                    }
                }
            }
            util_csv::export_csv_2($data,$filter, '组团中订单列表.csv');
            exit;
        }
        else{
            if($type == 2){
                $list = pdo_fetchall("SELECT a.mid,a.status,a.id,a.paytime,a.orderno,a.optionid,a.createtime,a.actualprice,a.num,b.sendtime,b.receivetime FROM " . tablename(PDO_NAME . "rush_order")
                    . " as a LEFT JOIN " . tablename(PDO_NAME . "express")
                    . " as b ON a.expressid = b.id WHERE {$where}");

                foreach($list as $k => &$li){
                    $member = pdo_get(PDO_NAME.'member',array('id'=>$li['mid']),array('nickname','avatar'));
                    $li['nickname'] = $member['nickname'];
                    if($li['optionid'] > 0){
                        $li['specname'] = pdo_getcolumn(PDO_NAME.'goods_option',array('uniacid'=>$_W['uniacid'],'id'=>$li['optionid']),'title');
                        $li['specname'] = $li['specname'] ."(spid:".$li['optionid'].")";
                    }else{
                        $li['specname'] = "- 无 -";
                    }
                    if($li['sendtime'] > 0){
                        $li['sendtime'] = date("Y-m-d H:i:s",$li['sendtime']);
                    }
                    if($li['receivetime'] > 0){
                        $li['receivetime'] = date("Y-m-d H:i:s",$li['receivetime']);
                    }
                    if($li['paytime'] > 0){
                        $li['paytime'] = date("Y-m-d H:i:s",$li['paytime']);
                    }
                    $li['createtime'] = date("Y-m-d H:i:s",$li['createtime']);
                    $li['price'] = $li['actualprice'];
                    switch ($li['status']){
                        case '0':
                            $li['statustext'] = '未支付';
                            break;
                        case '2':
                            $li['statustext'] = '已收货';
                            break;
                        case '3':
                            $li['statustext'] = '已收货';
                            break;
                        case '4':
                            $li['statustext'] = '待收货';
                            break;
                        case '7':
                            $li['statustext'] = '已退款';
                            break;
                        case '8':
                            $li['statustext'] = '待发货';
                            break;
                    }
                    $li['orderno'] = "\t".$li['orderno']."\t";
                }
            }else if($type == 3){
                $list = pdo_fetchall("SELECT a.mid,a.status,a.id,a.paytime,a.orderno,a.specid,a.createtime,a.price,a.num,b.sendtime,b.receivetime FROM " . tablename(PDO_NAME . "order")
                    . " as a LEFT JOIN " . tablename(PDO_NAME . "express")
                    . " as b ON a.expressid = b.id WHERE {$where} ORDER BY a.id DESC");
                foreach($list as $k => &$li){
                    $member = pdo_get(PDO_NAME.'member',array('id'=>$li['mid']),array('nickname','avatar'));
                    $li['nickname'] = $member['nickname'];
                    if($li['specid'] > 0){
                        $li['specname'] = pdo_getcolumn(PDO_NAME.'goods_option',array('uniacid'=>$_W['uniacid'],'id'=>$li['specid']),'title');
                        $li['specname'] = $li['specname'] ."(spid:".$li['specid'].")";
                    }else{
                        $li['specname'] = "- 无 -";
                    }
                    if($li['sendtime'] > 0){
                        $li['sendtime'] = date("Y-m-d H:i:s",$li['sendtime']);
                    }
                    if($li['receivetime'] > 0){
                        $li['receivetime'] = date("Y-m-d H:i:s",$li['receivetime']);
                    }
                    if($li['paytime'] > 0){
                        $li['paytime'] = date("Y-m-d H:i:s",$li['paytime']);
                    }
                    $li['createtime'] = date("Y-m-d H:i:s",$li['createtime']);
                    switch ($li['status']){
                        case '0':
                            $li['statustext'] = '未支付';
                            break;
                        case '2':
                            $li['statustext'] = '已收货';
                            break;
                        case '3':
                            $li['statustext'] = '已收货';
                            break;
                        case '4':
                            $li['statustext'] = '待收货';
                            break;
                        case '8':
                            $li['statustext'] = '待发货';
                            break;
                    }
                    $li['orderno'] = "\t".$li['orderno']."\t";
                }
            }
            /* 输出表头 */
            $filter = array(
                'orderno' => '订单编号',
                'specname' => '规格',
                'nickname'  => '用户昵称',
                'mid'        => '用户MID',
                'num' => '商品数量',
                'price' => '订单金额',
                'statustext' => '状态',
                'paytime' => '支付时间',
                'sendtime' => '发货时间',
                'receivetime' => '收货时间',
                'createtime' => '下单时间',
            );
            $data = array();
            for ($i=0; $i < count($list) ; $i++) {
                foreach ($filter as $key => $title) {
                    if(!empty($list[$i][$key])){
                        $data[$i][$key] = $list[$i][$key];
                    }else{
                        $data[$i][$key] = "- 无 -";
                    }
                }
            }
            util_csv::export_csv_2($data,$filter, '其他订单列表.csv');
            exit;
        }

    }

    /**
     * Comment: 预约列表
     * Author: wlf
     * Date: 2021/01/07 10:45
     */
    public function appointlist(){
        global $_W,$_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where['uniacid'] = $_W['uniacid'];
        //条件查询
        if(is_agent()) {
            $where['aid'] = $_W['aid'];
        }
        if(is_store()) {
            $where['sid'] = $_W['storeid'];
        }
        if(!empty($_GPC['plugin'])){
            $where['type'] = $_GPC['plugin'];
        }
        if($_GPC['status'] != 10){
            $where['status'] = $_GPC['status'] ? : 0;
        }
        if(!empty($_GPC['keyword'])){
            $keyword = $_GPC['keyword'];
            if($_GPC['keywordtype'] == 1){
                $where['orderno@']= trim($keyword);
            }else if($_GPC['keywordtype'] == 2){
                $where['sid']= trim($keyword);
            }else if($_GPC['keywordtype'] == 3){
                $where['mid']= trim($keyword);
            }
        }
        //时间
        if (!empty($_GPC['time_limit']) && $_GPC['timetype'] ) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            if($_GPC['timetype'] == 1){
                $where['appointtime>'] = $starttime;
                $where['appointtime<'] = $endtime;
            }else{
                $where['starttimestamp>'] = $starttime;
                $where['endtimestamp<'] = $endtime;
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }


        $appointData = Util::getNumData("*", PDO_NAME . 'appointlist', $where, 'appointtime desc', $pindex, $psize, 1);
        $appointlist = $appointData[0];
        $pager = $appointData[1];
        foreach ($appointlist as &$appoint){
            $member = pdo_get('wlmerchant_member',array('id' => $appoint['mid']),array('mobile','nickname','realname'));
            if($appoint['type'] == '1'){
                $order = pdo_get('wlmerchant_rush_order',array('id' => $appoint['orderid']),array('sid','blendcredit','activityid','price','expressid','paytype','actualprice','num','optionid'));
                $goods = pdo_get('wlmerchant_rush_activity',array('id' => $order['activityid']),array('name','thumb'));
                $appoint['goodsprice'] = sprintf("%.2f",$order['price']/$order['num']);
                if($order['optionid']){
                    $appoint['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['optionid']),'title');
                }
                $appoint['plugintext'] = '抢购';
                $appoint['plugincss'] = 'success';
                $appoint['goodsid'] = $order['activityid'];
                $appoint['ordertype'] = 'a';
                $appoint['detailurl'] = web_url('order/wlOrder/orderdetail',array('orderid'=>$appoint['orderid'],'type'=>1));
            }else if($appoint['type'] == '2'){
                $order = pdo_get('wlmerchant_order',array('id' => $appoint['orderid']),array('sid','blendcredit','fkid','price','expressid','paytype','goodsprice','num','specid'));
                $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $order['fkid']),array('name','thumb'));
                $appoint['goodsprice'] = sprintf("%.2f",$order['goodsprice']/$order['num']);
                if($order['specid']){
                    $appoint['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['specid']),'title');
                }
                $appoint['plugintext'] = '团购';
                $appoint['plugincss'] = 'info';
                $appoint['goodsid'] = $order['fkid'];
                $appoint['ordertype'] = 'b';
                $appoint['detailurl'] = web_url('order/wlOrder/orderdetail',array('orderid'=>$appoint['orderid'],'type'=>10));
            }else if($appoint['type'] == '3'){
                $order = pdo_get('wlmerchant_order',array('id' => $appoint['orderid']),array('sid','blendcredit','fkid','price','expressid','paytype','goodsprice','num','specid'));
                $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $order['fkid']),array('name','logo'));
                $appoint['goodsprice'] = sprintf("%.2f",$order['goodsprice']/$order['num']);
                if($order['specid']){
                    $appoint['optiontitle'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['specid']),'title');
                }
                $appoint['plugintext'] = '拼团';
                $appoint['plugincss'] = 'warning';
                $appoint['goodsid'] = $order['fkid'];
                $appoint['ordertype'] = 'b';
                $appoint['detailurl'] = web_url('order/wlOrder/orderdetail',array('orderid'=>$appoint['orderid'],'type'=>2));
            }else if($appoint['type'] == '7'){
                $order = pdo_get('wlmerchant_order',array('id' => $appoint['orderid']),array('sid','blendcredit','fkid','price','expressid','paytype','goodsprice','num','specid'));
                $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $order['fkid']),array('name','thumb'));
                $appoint['goodsprice'] = sprintf("%.2f",$order['goodsprice']/$order['num']);
                $appoint['plugintext'] = '砍价';
                $appoint['plugincss'] = 'primary';
                $appoint['goodsid'] = $order['fkid'];
                $appoint['ordertype'] = 'b';
                $appoint['detailurl'] = web_url('order/wlOrder/orderdetail',array('orderid'=>$appoint['orderid'],'type'=>12));
            }
            $store = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('storename'));
            $appoint['paytype'] = $order['paytype'];
            $appoint['nickname'] = $member['nickname'];
            $appoint['mobile'] = $member['mobile'];
            $appoint['realname'] = $member['realname'];
            $appoint['goodsname'] = $goods['name']?$goods['name']:$goods['title'];
            $appoint['goodsimg'] = $goods['thumb']?$goods['thumb']:$goods['logo'];
            $appoint['storename'] = $store['storename'];
            $appoint['num'] = $order['num'];


        }

        include  wl_template('order/appointlist');
    }

    /**
     * Comment: 通过预约
     * Author: wlf
     * Date: 2021/01/08 14:15
     */
    public function examineAppoint(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        if(empty($id)){
            show_json(0, '操作失败，无必要参数请刷新重试');
        }
        $appinfo = pdo_get(PDO_NAME.'appointlist',array('id'=>$id),['sorderids','goodid','orderid','mid','type','orderid','num']);
        $goodsid = $appinfo['goodid'];
        $orderid = $appinfo['orderid'];
        $ids = unserialize($appinfo['sorderids']);
        switch($appinfo['type']){
            case 1:
                $goods = pdo_get('wlmerchant_rush_activity',array('id' => $goodsid),array('uniacid','aid','appointment','name','appointstatus','sid'));
                $plugin = 'rush';
                $pluginName = '抢购';
                $goodsName = $goods['name'];
                $orderNo = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$orderid),'orderno');
                break;
            case 2:
                $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $goodsid),array('uniacid','appointment','name','aid','appointstatus','sid'));
                $plugin = 'groupon';
                $pluginName = '团购';
                $goodsName = $goods['name'];
                $orderNo = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'orderno');
                break;
            case 3:
                $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $goodsid),array('uniacid','name','aid','appointment','appointstatus','sid'));
                $plugin = 'wlfightgroup';
                $pluginName = '拼团';
                $goodsName = $goods['name'];
                $orderNo = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'orderno');
                break;
            case 7:
                $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $goodsid),array('uniacid','name','aid','appointment','appointstatus','sid'));
                $plugin = 'bargain';
                $pluginName = '砍价';
                $goodsName = $goods['name'];
                $orderNo = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'orderno');
                break;
        }
        $res = pdo_update('wlmerchant_appointlist',array('status' => 1),array('id' => $id));
        if($res){
            $upSres = pdo_update('wlmerchant_smallorder',array('appointstatus' => 3),array('id' => $ids));
            if($upSres){
                //发送消息给客户
                $first = '您的'.$pluginName.'订单已经成功预约';
                $type = '订单消费预约';
                $content = '商品名:['.$goodsName.']';
                $newStatus = '预约成功';
                $remark = '订单号:['.$orderNo.'],预约数量:'.$appinfo['num'].'份,点击查看详情';
                $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails' , ['orderid' => $orderid , 'plugin'  => $plugin]);
                News::jobNotice($appinfo['mid'],$first,$type,$content,$newStatus,$remark,time(),$url);
            }
        }
        show_json(1);
    }

    /**
     * Comment: 驳回预约
     * Author: wlf
     * Date: 2021/01/08 14:25
     */
    public function rejectAppoint(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $reason = $_GPC['reason'];  //驳回原因
        if ($_W['ispost']) {
            if (empty($id)) {
                show_json(0, '操作失败，无必要参数请刷新重试');
            }
            if (empty($reason)) {
                show_json(0, '请输入驳回原因');
            }
            $appinfo = pdo_get(PDO_NAME . 'appointlist', array('id' => $id), ['sorderids','goodid','orderid','mid','type','orderid','num']);
            $goodsid = $appinfo['goodid'];
            $orderid = $appinfo['orderid'];
            $ids = unserialize($appinfo['sorderids']);
            switch ($appinfo['type']) {
                case 1:
                    $goods = pdo_get('wlmerchant_rush_activity', array('id' => $goodsid), array('uniacid', 'aid', 'appointment', 'name', 'appointstatus', 'sid'));
                    $plugin = 'rush';
                    $pluginName = '抢购';
                    $goodsName = $goods['name'];
                    $orderNo = pdo_getcolumn(PDO_NAME . 'rush_order', array('id' => $orderid), 'orderno');
                    break;
                case 2:
                    $goods = pdo_get('wlmerchant_groupon_activity', array('id' => $goodsid), array('uniacid', 'appointment', 'name', 'aid', 'appointstatus', 'sid'));
                    $plugin = 'groupon';
                    $pluginName = '团购';
                    $goodsName = $goods['name'];
                    $orderNo = pdo_getcolumn(PDO_NAME . 'order', array('id' => $orderid), 'orderno');
                    break;
                case 3:
                    $goods = pdo_get('wlmerchant_fightgroup_goods', array('id' => $goodsid), array('uniacid', 'name', 'aid', 'appointment', 'appointstatus', 'sid'));
                    $plugin = 'wlfightgroup';
                    $pluginName = '拼团';
                    $goodsName = $goods['name'];
                    $orderNo = pdo_getcolumn(PDO_NAME . 'order', array('id' => $orderid), 'orderno');
                    break;
                case 7:
                    $goods = pdo_get('wlmerchant_bargain_activity', array('id' => $goodsid), array('uniacid', 'name', 'aid', 'appointment', 'appointstatus', 'sid'));
                    $plugin = 'bargain';
                    $pluginName = '砍价';
                    $goodsName = $goods['name'];
                    $orderNo = pdo_getcolumn(PDO_NAME . 'order', array('id' => $orderid), 'orderno');
                    break;
            }
            $res = pdo_update('wlmerchant_appointlist', array('status' => 2, 'reason' => $reason), array('id' => $id));
            if ($res) {
                $upSres = pdo_update('wlmerchant_smallorder', array('appointstatus' => 1), array('id' => $ids));
                if ($upSres) {
                    //发送消息给客户
                    $first = '您的' . $pluginName . '订单预约失败';
                    $type = '订单消费预约';
                    $content = '商品名:[' . $goodsName . ']';
                    $newStatus = '预约失败';
                    $remark = '驳回原因:[' . $reason . '],点击重新预约';
                    $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails', ['orderid' => $orderid, 'plugin' => $plugin]);
                    News::jobNotice($appinfo['mid'], $first, $type, $content, $newStatus, $remark, time(), $url);
                }
            }
            show_json(1);
        }
        include wl_template('order/rejectAppoint');
    }

}