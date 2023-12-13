<?php
defined('IN_IA') or exit('Access Denied');

class Payactive_WeliamController {

	public function activelist() {
		global $_W,$_GPC;
		$pindex = $_GPC['page']?$_GPC['page']:1;
		$psize = 10;
		$where = array();
		$where['uniacid'] = $_W['uniacid'];
		$where['aid'] = $_W['aid'];
		
		$lists = Util::getNumData('*','wlmerchant_payactive',$where,'id DESC',$pindex,$psize,1);
		$pager = $lists[1];
		$lists = $lists[0];
		
		include wl_template('payactive/active_list');
	}
	
	
	function delate() {
		global $_W,$_GPC;
		$id = $_GPC['id'];
		$res = pdo_delete('wlmerchant_payactive',array('id'=>$id));
		if($res){
			show_json(1,'活动删除成功');
		}else {
			show_json(0,'活动删除失败，请重试');
		}
	}

	public function	recodelist(){
		global $_W,$_GPC;
		$pindex = $_GPC['page']?$_GPC['page']:1;
		$psize = 10;
		$where = array();
		$where['uniacid'] = $_W['uniacid'];
		$where['aid'] = $_W['aid'];
		
		if (!empty($_GPC['type'])) {
			$where['type'] = $_GPC['type'];
		}
		if ($_GPC['time_limit']) {
			$time_limit = $_GPC['time_limit'];
			$starttime = strtotime($_GPC['time_limit']['start']);
			$endtime = strtotime($_GPC['time_limit']['end']);
			$where['createtime>'] = $starttime;
			$where['createtime<'] = $endtime;
		}
		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time()+86400;
		}
		
		
		$lists = Util::getNumData('*','wlmerchant_paidrecord',$where,'createtime DESC',$pindex,$psize,1);
		$pager = $lists[1];
		$lists = $lists[0];
		if($lists){
			foreach ($lists as $key => &$va) {
				if($va['type'] == 1){
					$order = pdo_get(PDO_NAME.'rush_order',array('id'=>$va['orderid']),array('mid','activityid'));
					$mid = $order['mid'];
					$good = pdo_get(PDO_NAME.'rush_activity',array('id'=>$order['activityid']),array('name','thumb'));
					$va['goodname'] = $good['name'];
					$va['logo'] = tomedia($good['thumb']);
				}else if($va['type'] == 2) {
					$order = pdo_get(PDO_NAME.'order',array('id'=>$va['orderid']),array('mid','fkid'));
					$mid = $order['mid'];
                    $good = pdo_get(PDO_NAME.'fightgroup_goods',array('id'=>$order['fkid']),['name','logo']);
                    $va['goodname'] = $good['name'];
                    $va['logo'] = tomedia($good['logo']);
				}else if($va['type'] == 3) {
					$order = pdo_get(PDO_NAME.'order',array('id'=>$va['orderid']),array('mid','fkid'));
					$mid = $order['mid'];
                    $good = pdo_get(PDO_NAME.'couponlist',array('id'=>$order['fkid']),['title','logo']);
                    $va['goodname'] = $good['title'];
                    $va['logo'] = tomedia($good['logo']);
				}else if($va['type'] == 4) {
					$order = pdo_get(PDO_NAME.'order',array('id'=>$va['orderid']),array('mid','fkid'));
					$mid = $order['mid'];
                    $good = pdo_get(PDO_NAME.'groupon_activity',array('id'=>$order['fkid']),['name','thumb']);
                    $va['goodname'] = $good['name'];
                    $va['logo'] = tomedia($good['thumb']);
				}else if($va['type'] == 5) {
					$order = pdo_get(PDO_NAME.'halfcard_record',array('id'=>$va['orderid']),array('mid','typeid'));
					$mid = $order['mid'];
					$va['goodname'] = pdo_getcolumn(PDO_NAME.'halfcard_type',array('id'=>$order['typeid']),'name');
                    $va['logo'] = tomedia($_W['wlsetting']['base']['logo']);
				}else if($va['type'] == 6) {
					$order = pdo_get(PDO_NAME.'order',array('id'=>$va['orderid']),array('mid','sid','fkid'));
					$mid = $order['mid'];
					$va['goodname'] = pdo_getcolumn(PDO_NAME.'chargelist',array('id'=>$order['fkid']),'name');
                    $va['logo'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$order['sid']),'logo');
                    $va['logo'] = tomedia($va['logo']);
				}else if($va['type'] == 7) {
                    $order = pdo_get(PDO_NAME.'order',array('id'=>$va['orderid']),array('mid','fkid','sid'));
                    $mid = $order['mid'];
                    $va['goodname'] = pdo_getcolumn(PDO_NAME.'halfcardlist',array('id'=>$order['fkid']),'title');
                    $store = pdo_get(PDO_NAME.'merchantdata',array('id'=>$order['sid']),['storename','logo']);
                    if(empty($va['goodname'])){
                        $va['goodname'] = $store['storename'].'在线买单';
                    }
                    $va['logo'] = tomedia($store['logo']);
                }else if($va['type'] == 9) {
                    $order = pdo_get(PDO_NAME.'order',array('id'=>$va['orderid']),array('mid','fkid'));
                    $mid = $order['mid'];
                    $good = pdo_get(PDO_NAME.'bargain_activity',array('id'=>$order['fkid']),['name','thumb']);
                    $va['goodname'] = $good['name'];
                    $va['logo'] = tomedia($good['thumb']);
                }else if($va['type'] == 8) {
                    $order = pdo_get(PDO_NAME.'order',array('id'=>$va['orderid']),array('mid','sid','fkid'));
                    $mid = $order['mid'];
                    $store = pdo_get(PDO_NAME.'merchantdata',array('id'=>$order['sid']),['storename','logo']);
                    $va['goodname'] =$store['storename'].'同城配送';
                    $va['logo'] = tomedia($store['logo']);
                }
				
				$member = pdo_get(PDO_NAME.'member',array('id'=>$mid),array('nickname','avatar','mobile'));
				$va['nickname'] = $member['nickname'];
				$va['headimg'] = $member['avatar'];
				$va['mobile'] = $member['mobile'];
				$va['activename'] = pdo_getcolumn(PDO_NAME.'payactive',array('id'=>$va['activeid']),'title');
				if($va['couponid']){
                    $couponIdList = explode(',',$va['couponid']);
                    foreach ($couponIdList as $k => $v) {
                        $va['couponname'][] = pdo_getcolumn(PDO_NAME.'couponlist',array('id'=>$v),'title');
                    }
				}
                if($va['redpackid']){
                    $redpackList = explode(',',$va['redpackid']);
                    foreach ($redpackList as $k => $v) {
                        $va['redpackname'][] = pdo_getcolumn(PDO_NAME.'redpacks',array('id'=>$v),'title');
                    }
                }
				if($va['codeid']){
					$va['code'] = pdo_getcolumn(PDO_NAME.'token',array('id'=>$va['codeid']),'number');
				}
				
			}
		}
		
		include wl_template('payactive/recodelist');
	}

	public function createactive(){
		global $_W,$_GPC;
		//编辑
        $setInfo = Setting::wlsetting_read('payment_set');

        $id = $_GPC['id']?$_GPC['id']:0;
		$active = pdo_get('wlmerchant_payactive',array('id' => $id));
		if($active['integral'] < 0.01){
            $active['integral'] = 1;
        }
        if($active['balance'] < 0.01){
            $active['balance'] = 1;
        }
        if($active['giftcouponid']){
		    $active['giftcouponid'] = explode(',',$active['giftcouponid']);
		}
        if($active['giftredpack']){
            $active['giftredpack'] = explode(',',$active['giftredpack']);
        }
        if(!empty($active['paytypearray'])){
            $active['paytypearray'] = unserialize($active['paytypearray']);
        }
		//初始化商品列表
        $giftcoupons = pdo_getall('wlmerchant_couponlist',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status'=>2),array('id','title'));
		$giftcode = pdo_fetchall("SELECT distinct remark FROM ".tablename('wlmerchant_token')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ORDER BY id DESC");
		if($giftcode){
			foreach ($giftcode as $key => $cas) {
				if(empty($cas['remark'])){
					unset($giftcode[$key]);
				}
			}
		}
		$giftredlist = pdo_getall('wlmerchant_redpacks',array('uniacid' => $_W['uniacid'] , 'aid' => $_W['aid'] , 'scene'=>1 , 'status' => 1),array('id','title'));
		//初始化时间
		if (empty($active['starttime']) || empty($active['endtime'])) {
			$active['starttime'] = time();
			$active['endtime'] = strtotime('+1 month');
		}

		if (checksubmit('submit')){
			$activedata = $_GPC['active'];
            if(is_array($activedata['giftcouponid'])){$activedata['giftcouponid'] = implode(',',$activedata['giftcouponid']);}
            if(is_array($activedata['giftredpack'])){$activedata['giftredpack'] = implode(',',$activedata['giftredpack']);}
            if(is_array($_GPC['paytype'])){
                $activedata['paytypearray'] = serialize($_GPC['paytype']);
            }else{
                $activedata['paytypearray'] = '';
            }
            //保存
			if($id){
				$res = pdo_update('wlmerchant_payactive',$activedata,array('id' => $id));				
			}else {
				$activedata['uniacid'] = $_W['uniacid'];
				$activedata['aid'] = $_W['aid'];
				$activedata['createtime'] = time();
				$res = pdo_insert(PDO_NAME.'payactive',$activedata);			
			}
			if($res){
				wl_message('保存成功！',web_url('paidpromotion/payactive/activelist'),'success');
			}else {
				wl_message('保存失败,请重试',referer(),'error');
			}
		}

		include wl_template('payactive/createactive');
	}
    /**
     * Comment: 修改状态
     * Author: zzw
     * Date: 2019/9/18 15:41
     */
    public function changeStatus (){
        global $_W , $_GPC;
        #1、获取参数信息
        $id = $_GPC['id'] OR Commons::sRenderError('缺少参数：导航id');
        $status = $_GPC['status'] ? : 0;
        #1、修改操作
        $res = pdo_update(PDO_NAME."payactive",['status'=>$status],['id'=>$id]);
        if($res) Commons::sRenderSuccess('修改成功');
        else Commons::sRenderError('修改失败，请刷新重试!');
    }
}
