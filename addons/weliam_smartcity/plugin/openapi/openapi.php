<?php
defined('IN_IA') or exit('Access Denied');

class Weliam_smartcityModuleOpenapi extends Openapi {
	
	//添加分销商
	public function doPageAdddisor() {
		global $_W, $_GPC;
		$openid = $_GPC['openid'];
		$nickname = $_GPC['nickname'];
		$avatar = $_GPC['avatar'];
		if(empty($openid)){
			$this->result(1,'无有效openid');
		}
		$mid = pdo_getcolumn('wlmerchant_member',array('openid' => $openid),'id');
		if(empty($mid)){
			$memberdata = array(
				'uniacid' => $_W['uniacid'],
				'openid'  => $openid,
				'nickname' => $nickname,
				'avatar'   => $avatar,
				'createtime' => time()
			);
			pdo_insert(PDO_NAME.'member',$memberdata);
			$mid = pdo_insertid();
		}
		
		$member = pdo_get('wlmerchant_member',array('id' => $mid),array('mobile','nickname','realname'));
		$distributorid = pdo_getcolumn('wlmerchant_member',array('id'=>$mid),'distributorid');
		if($distributorid){
			$distributor = pdo_get('wlmerchant_distributor',array('id'=> $distributorid));
			if($distributor['disflag']){
				$this->result(1,'不能重复添加');
			}else {
				$res = pdo_update('wlmerchant_distributor',array('disflag' => 1),array('id' => $distributorid));
				if($res){
					$this->result(0, '添加成功', array('id' => $distributorid));
				}else {
					$this->result(1,'未知错误,请联系管理员');
				}
			}
		}else {
			$data = array(
				'uniacid' => $_W['uniacid'],
				'mid' => $mid,
				'createtime' => time(),
				'disflag' => 1,
				'nickname' => $member['nickname'],
				'mobile' => $member['mobile'],
				'realname' => $member['realname']
			);
			pdo_insert('wlmerchant_distributor',$data);
			$disid = pdo_insertid();
			$res = pdo_update('wlmerchant_member',array('distributorid' => $disid),array('id' => $mid));
			if($res){
				$this->result(0, '添加成功', array('id' => $disid));
			}else {
				$this->result(1,'未知错误,请联系管理员');
			}
		}
	}
	
	//修改分销商余额
	public function doPageChangediscash() {
		global $_W, $_GPC;
		$openid = $_GPC['openid'];
		$money = $_GPC['money']; //变更金额
		$reason = $_GPC['reason']; //修改原因
		$buymid = isset($_GPC['buymid'])?intval($_GPC['buymid']):-1;
		if(empty($openid)){
			$this->result(1,'无有效openid');
		}
		if(empty($money)){
			$this->result(1,'无有效变更金额');
		}
		$disid = pdo_getcolumn(PDO_NAME.'member',array('openid'=>$openid),'distributorid');
		if(empty($disid)){
			$this->result(1,'该用户不是分销商');
		}
		$distri = pdo_get('wlmerchant_distributor',array('id' => $disid),array('id','mid','dismoney','nowmoney'));
		if($money > 0){
			$type = 1;
		}else{
			$type = 2;
		}
		$money = abs($money);
		$money = sprintf("%.2f",$money);
		if(empty($reason)){
			$reason = '接口修改分销商金额';
		}
		if($type == 1){
			$onedismoney = $distri['dismoney'] + $money;
			$onenowmoney = $distri['nowmoney'] + $money;
		}else {
			$onedismoney = $distri['dismoney'] - $money;
			$onenowmoney = $distri['nowmoney'] - $money;
		}
		$changeflag = pdo_update('wlmerchant_distributor',array('dismoney' => $onedismoney,'nowmoney' => $onenowmoney),array('id' => $distri['id']));
		if($changeflag){
			Distribution::adddisdetail(0,$distri['mid'],$buymid,$type,$money,'system',1,$reason);
			$this->result(0,'修改成功');
		}
	}
}