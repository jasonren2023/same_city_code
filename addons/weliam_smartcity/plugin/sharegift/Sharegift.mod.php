 <?php 
defined('IN_IA') or exit('Access Denied');

class Sharegift{
	
	static function addrecord($id,$mid,$buymid,$sharestatus,$sharemoney,$plugin){
		global $_W;
		$data = array(
			'uniacid'     =>  $_W['uniacid'],
			'aid'         =>  $_W['aid'],
			'status'      =>  0,
			'type'        =>  $sharestatus,
			'plugin'      =>  $plugin,
			'goodsid'     =>  $id,
			'mid'      	  =>  $mid,
			'buymid'      =>  $buymid,
			'price'       =>  $sharemoney,
			'createtime'  =>  time()
		);
		$res = pdo_insert(PDO_NAME.'sharegift_record',$data);
		return $res;
	}
	
	//添加明细
	static function addcurrent($shareid,$price,$type,$reason='',$nowmoney,$sys=0){
	    global $_W;
		if($sys){
			$sharerecore = pdo_get('wlmerchant_shareapply',array('id' => $shareid));
		}else{
			$sharerecore = pdo_get('wlmerchant_sharegift_record',array('id' => $shareid));
		}
		$data = array(
			'uniacid'     => $_W['uniacid'],
			'aid'         => $_W['aid'],
			'shareid'     => $shareid,
			'mid'         => $sharerecore['mid'],
			'type'        => $type,
			'price'       => $price,
			'createtime'  => time(),
			'plugin'      => $sharerecore['plugin'],
			'reason'      => $reason,
			'nowmoney'    => $nowmoney
		);
		pdo_insert(PDO_NAME.'sharecurrent',$data);
	}
	
	static function doTask(){
		global $_W;
		//结算分享分佣
		$records = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_sharegift_record')."WHERE uniacid = {$_W['uniacid']} AND status = 1 AND type = 2 ORDER BY createtime ASC limit 20");
		if($records){
			foreach ($records as $key => &$reco){
				if($reco['plugin'] == 1){
					$num = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$reco['orderid']),'num');
				}else if($reco['plugin'] == 2){
					$num = pdo_getcolumn(PDO_NAME.'order',array('id'=>$reco['orderid']),'num');
				}
				$price = sprintf("%.2f",$reco['price']*$num);
				pdo_fetch("update" . tablename('wlmerchant_member') . "SET sharemoney=sharemoney+{$price},sharenowmoney=sharenowmoney+{$price} WHERE id = {$reco['mid']}");
				pdo_update('wlmerchant_sharegift_record',array('status' => 2),array('id' => $reco['id']));
				$nowmoney = pdo_get('wlmerchant_member',array('id' => $reco['mid']),array('sharenowmoney'));
				self::addcurrent($reco['id'],$price,1,'分享分佣结算',$nowmoney['sharenowmoney']);
			}
		}
		
		
	}	
	
}
?>