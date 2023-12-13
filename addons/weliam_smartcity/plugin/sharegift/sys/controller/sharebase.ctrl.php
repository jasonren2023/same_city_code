<?php
defined('IN_IA') or exit('Access Denied');

class Sharebase_WeliamController {
	
	
	public function sharerecord(){
		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$where = array('uniacid' => $_W['uniacid']);
		
		if (!empty($_GPC['keyword'])) {
			if ($_GPC['keywordtype'] == 1) {
				$keyword = $_GPC['keyword'];
				$params[':name'] = "%{$keyword}%";
				if($_GPC['plugin'] == 1 || empty($_GPC['plugin'])){
					$goods = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_rush_activity') . "WHERE uniacid = {$_W['uniacid']} AND name LIKE :name", $params);
				}else if($_GPC['plugin'] == 2){
					$goods = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_groupon_activity') . "WHERE uniacid = {$_W['uniacid']} AND name LIKE :name", $params);
				}
				if ($goods) {
					$goodsids = "(";
					foreach ($goods as $key => $v) {
						if ($key == 0) {
							$goodsids .= $v['id'];
						} else {
							$goodsids .= "," . $v['id'];
						}
					}
					$goodsids .= ")";
					$where['goodsid#'] = $goodsids;
				} else {
					$where['goodsid#'] = "(0)";
				}
			}
			if($_GPC['keywordtype'] == 2){
				$keyword = $_GPC['keyword'];
				$params[':nickname'] = "%{$keyword}%";
				$members = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname", $params);
				if ($members) {
					$memberids = "(";
					foreach ($members as $key => $v) {
						if ($key == 0) {
							$memberids .= $v['id'];
						} else {
							$memberids .= "," . $v['id'];
						}
					}
					$memberids .= ")";
					$where['mid#'] = $memberids;
				} else {
					$where['mid#'] = "(0)";
				}
			}
			if($_GPC['keywordtype'] == 3){
				$keyword = $_GPC['keyword'];
				$params[':nickname'] = "%{$keyword}%";
				$members = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname", $params);
				if ($members) {
					$memberids = "(";
					foreach ($members as $key => $v) {
						if ($key == 0) {
							$memberids .= $v['id'];
						} else {
							$memberids .= "," . $v['id'];
						}
					}
					$memberids .= ")";
					$where['buymid#'] = $memberids;
				} else {
					$where['buymid#'] = "(0)";
				}
			}
			
		}
		
		if($_GPC['type']){
			$where['type'] = intval($_GPC['type']);
		}
		if($_GPC['plugin']){
			$where['plugin'] = intval($_GPC['plugin']);
		}
		if($_GPC['status']){
			if($_GPC['status'] == 4){
				$where['status'] = 0;
			}else{
				$where['status'] = intval($_GPC['status']);
			}
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
		
		
		$records = Util::getNumData('*', 'wlmerchant_sharegift_record', $where,'ID DESC',$pindex, $psize, 1);

		$pager = $records[1];
		$records = $records[0];
		if($records){
			foreach ($records as $key => &$re) {
				//商品信息
				if($re['plugin'] == 1){
					$goods = pdo_get('wlmerchant_rush_activity',array('id' => $re['goodsid']),array('name','sid'));
				}else if($re['plugin'] == 2) {
					$goods = pdo_get('wlmerchant_groupon_activity',array('id' => $re['goodsid']),array('name','sid'));
				}
				$re['title'] = $goods['name'];
				$re['storename'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$goods['sid']),'storename');
				//分享人信息
				$sharemember = pdo_get('wlmerchant_member',array('id' => $re['mid']),array('nickname','avatar'));
				$re['sharename'] = $sharemember['nickname'];
				$re['shareavatar'] = tomedia($sharemember['avatar']);
				//购买人信息
				$buymember = pdo_get('wlmerchant_member',array('id' => $re['buymid']),array('nickname','avatar'));
				$re['buyname'] = $buymember['nickname'];
				$re['buyavatar'] = tomedia($buymember['avatar']);
				
			}
		}
		
		include wl_template('sharesys/sharerecord');
	}
	
	
	
	public function sharecurrent(){
		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$where['uniacid'] = $_W['uniacid'];
		
		if (!empty($_GPC['keyword'])){
			if(!empty($_GPC['keywordtype'])){
				switch($_GPC['keywordtype']){
					case 1: $where['mid'] = $_GPC['keyword'];break;
					case 3: $where['price>'] = $_GPC['keyword'];break;
					case 4: $where['price<'] = $_GPC['keyword'];break;
					default:break;
				}
			}
			if($_GPC['keywordtype'] == 2){
				$keyword = $_GPC['keyword'];
				$params[':nickname'] = "%{$keyword}%";
				$goods = pdo_fetchall("SELECT id,nickname FROM ".tablename('wlmerchant_member')."WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname",$params);
				if($goods){
					$goodids = "(";
					foreach ($goods as $key => $v) {
						if($key == 0){
							$goodids.= $v['id'];
						}else{
							$goodids.= ",".$v['id'];
						}	
					}
					$goodids.= ")";
					$where['mid#'] = $goodids;
				}else {
					$where['mid#'] = "(0)";
				}
			}
		}
		
		
		if($_GPC['type']){
			$where['type'] = $_GPC['type'];
		}
		if($_GPC['plugin']){
			$where['plugin'] = $_GPC['plugin'];
		}
		
		if($_GPC['time_limit']){
			$time_limit = $_GPC['time_limit'];
			$starttime = strtotime($_GPC['time_limit']['start']);
			$endtime = strtotime($_GPC['time_limit']['end']) ;
			$where['createtime>'] = $starttime;
			$where['createtime<'] = $endtime;
		}
		
		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time()+86400;
		}
		
		
		
		$details = Util::getNumData('*',PDO_NAME.'sharecurrent',$where,'ID DESC',$pindex,$psize,1);
		$pager = $details[1];
		$details = $details[0];
		if($details){
			foreach ($details as $key => &$vde) {
				$vde['nickname'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$vde['mid']),'nickname');
				$buymid = pdo_getcolumn(PDO_NAME.'sharegift_record',array('id'=>$vde['shareid']),'buymid');
				$vde['buyname'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$buymid),'nickname');
			}
		}
		
		
		include  wl_template('sharesys/sharecurrent');
	}
	
	
	public function shareapply(){
		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$where = array('uniacid' => $_W['uniacid']);
		
		$list = Util::getNumData('*', 'wlmerchant_shareapply', $where,'createtime DESC',$pindex, $psize, 1);
		$pager = $list[1];
		$list = $list[0];
		if($list){
			foreach ($list as $key => &$vaa) {
				$vaa['nickname'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$vaa['mid']),'nickname');
				$vaa['avatar'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$vaa['mid']),'avatar');
				$vaa['avatar'] = tomedia($vaa['avatar']);
			}
		}
		
		
		include  wl_template('sharesys/shareapply');
	}
	
	public function settlementing(){
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$type = $_GPC['type'];
		
		$settlementRecord = pdo_get('wlmerchant_shareapply',array('id' => $id));
		if($settlementRecord['status'] != 1){
			show_json(0,'该申请已完成或已驳回');
		}
		if($type == 'reject'){
			$res = pdo_update('wlmerchant_shareapply',array('status' => 3,'dotime'=>time()),array('id' => $id));
			if($res){
				pdo_fetch("update".tablename('wlmerchant_member') . "SET sharenowmoney=sharenowmoney+{$settlementRecord['applymoney']} WHERE id = {$settlementRecord['mid']}");
				$nowmoney = pdo_getcolumn(PDO_NAME.'member',array('id'=>$settlementRecord['mid']),'sharenowmoney');
				Sharegift::addcurrent($id,$settlementRecord['applymoney'],1,'驳回提现申请',$nowmoney,1);
				show_json(1,'已成功驳回');
			}else {
				show_json(0,'驳回失败，请重试');
			}
		}else if($type == 'wechat'){
			$openid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$settlementRecord['mid']),'openid');
			if(empty($openid)){
				show_json(0,'该用户没有openid，无法微信打款');
			}
			
			if (is_numeric($settlementRecord['money'])) {
				if ($settlementRecord['money'] < 1){
					show_json(0,'到账金额需要大于1元');
				}
				$rem = '分享佣金提现';
				$realname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$settlementRecord['mid']),'realname');
				$result1 = wlPay::finance($openid,$settlementRecord['money'],$rem,$realname);
				//结算操作
				if ($result1['return_code'] == 'SUCCESS' && $result1['result_code'] == 'SUCCESS') {
					$res = pdo_update('wlmerchant_shareapply',array('status' => 2,'cashstatus'=>1,'dotime'=>time()),array('id' => $id));
					if ($res) {
						show_json(1,'已结算给用户');
					} else {
						show_json(0,'结算失败,请重试');
					}
				} else {
					if(empty($result1['err_code_des'])){
						$result1['err_code_des'] = $result1['message'];
					}
					// 结算失败
					show_json(0,'微信钱包提现失败: '.$result1['err_code_des']);
				}
			} else {
				show_json(0,'结算金额错误');
			}
			show_json(1,'操作成功');
		}else if($type == 'f2f'){
				/*先判断是否有已结算*/
				if ($settlementRecord['status'] != 1){
					show_json(0,'该申请已完成或已驳回');
				}
				if (is_numeric($settlementRecord['money'])) {
					$res = pdo_update('wlmerchant_shareapply',array('status' => 2,'cashstatus'=>2,'dotime'=>time()),array('id' => $id));
					if ($res) {
						show_json(1,'已结算给用户');
					} else {
						show_json(0,'结算失败,请重试');
					}
				} else {
					show_json(0,'结算金额错误');
				}
			show_json(1,'操作成功');
		}
	}
	
	
	
	public function baseset(){
		global $_W, $_GPC;
		$base = Setting::wlsetting_read('sharegift');
		if (checksubmit('submit')){
			$data = $_GPC['base'];
			$data['describe'] = htmlspecialchars_decode($data['describe']);
			$res1 = Setting::wlsetting_save($data,'sharegift');
			if ($res1) {
				wl_message('设置保存成功！', referer(), 'success');
			} else {
				wl_message('设置保存失败！', referer(), 'error');
			}	
		}
		
		
		
		include wl_template('sharesys/baseset');
	}
}
?>