<?php
defined('IN_IA') or exit('Access Denied');

class WlCash_WeliamController {
    //财务概况
	public function cashSurvey() {
		global $_W, $_GPC;
		$refresh = $_GPC['refresh'] ? 1 : 0;
		$timetype = $_GPC['timetype'];
		$time_limit = $_GPC['time_limit'];
		if ($time_limit) {
			$starttime = strtotime($_GPC['time_limit']['start']);
			$endtime = strtotime($_GPC['time_limit']['end']);
		}

		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}

		$data = Merchant::sysCashSurvey(1, $timetype, $starttime, $endtime);
		$agents = $data[0];
		$children = $data[1];
		$max = $data[2];
		$allMoney = $data[3];
		$time = $data[4];
		$newdata = $data[5];
		//		wl_debug($newdata);
		include  wl_template('finace/cashSurvey');
	}
    //财务设置
    public function cashset() {
        global $_W, $_GPC;
        if ($_W['ispost']) {
            $set = $_GPC['cashset'];
            if($set['allocationtype'] > 0){ //分账
                //			    if($set['wxsysalltype'] == 1){
                //			        if(empty($set['wxmerchantid'])){
                //                        show_json(0,'请设置公众号平台分账商户号');
                //                    }
                //                    if(empty($set['wxmerchantname'])){
                //                        show_json(0,'请设置公众号平台分账商户名称');
                //                    }
                //                }else if($set['wxsysalltype'] == 2){
                //                    if(empty($set['wxallmid'])){
                //                        show_json(0,'请设置公众号平台分账个人微信号');
                //                    }else{
                //                        $member = pdo_get('wlmerchant_member',array('id' => $set['wxallmid']),array('openid'));
                //                        if(empty($member['openid'])){
                //                            show_json(0,'所选用户无微信公众号账户信息，请重选');
                //                        }
                //                    }
                //                }
                //                if($set['appsysalltype'] == 1){
                //                    if(empty($set['appmerchantid'])){
                //                        show_json(0,'请设置小程序平台分账商户号');
                //                    }
                //                    if(empty($set['appmerchantname'])){
                //                        show_json(0,'请设置小程序平台分账商户名称');
                //                    }
                //                }else if($set['appsysalltype'] == 2){
                //                    if(empty($set['appallmid'])){
                //                        show_json(0,'请设置小程序平台分账个人微信号');
                //                    }else{
                //                        $member = pdo_get('wlmerchant_member',array('id' => $set['appallmid']),array('wechat_openid'));
                //                        if(empty($member['wechat_openid'])){
                //                            show_json(0,'所选用户无微信小程序账户信息，请重选');
                //                        }
                //                    }
                //                }
            }else{
                if($set['maxsetmoney'] > 0 && $set['maxsetmoney'] < $set['lowsetmoney']){
                    show_json(0,'最大提现金额必须大于最小提现金额');
                }
            }

            $res1 = Setting::wlsetting_save($set, 'cashset');
            if ($res1) {
                show_json(1);
            } else {
                show_json(0,'保存设置失败,请重试');
            }
        }
        //获取设置信息
        $cashset = Setting::wlsetting_read('cashset');
        #1、获取微信支付方式列表
        $weChat = pdo_getall(PDO_NAME."payment",['uniacid'=>$_W['uniacid'],'type'=>1],['id','name']);
        #2、获取支付宝支付方式列表
        $aliPay = pdo_getall(PDO_NAME."payment",['uniacid'=>$_W['uniacid'],'type'=>2],['id','name']);
        //校验权限
        $isAuth = Customized::init('allocation');

        include  wl_template('finace/cashset');
    }
}
