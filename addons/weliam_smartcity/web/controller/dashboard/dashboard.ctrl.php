<?php
defined('IN_IA') or exit('Access Denied');

class Dashboard_WeliamController {

	public function index() {
		global $_W, $_GPC;
		if(is_store()){
            $name = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $_W['storeid']), 'storename');
        }else{
            $name = pdo_getcolumn(PDO_NAME . 'agentusers', array('id' => $_W['aid']), 'agentname');
            //初始化总后台数据
            Area::initAgent();
        }


		if ($_W['isajax']) {
		    if(is_store()){
                $data = Merchant::newSurvey($_W['aid'],$_W['storeid']);
                $data2 = Cache::getCache('storeinfo' . $_W['storeid'], 'data');
                if ($data2['time'] < strtotime(date("Y-m-d"), time()) || $_GPC['refresh']) {
                    $data2 = Merchant::cacheSurvey($_W['aid'],$_W['storeid']);
                    Cache::setCache('storeinfo'.$_W['storeid'],'data',$data2);
                }
            }else{
                $data = Merchant::newSurvey($_W['aid']);
                $data2 = Cache::getCache('cachesur' . $_W['aid'], 'data');
                if ($data2['time'] < strtotime(date("Y-m-d"), time()) || $_GPC['refresh']) {
                    $data2 = Merchant::cacheSurvey($_W['aid']);
                    Cache::setCache('cachesur' . $_W['aid'], 'data', $data2);
                }
            }
			$data = array_merge($data, $data2);
			$li = array('year' => date('m-d', time()), '金额' => $data['allmoney']);
			$data['list'][] = $li;

			die(json_encode($data));
		}
		include  wl_template('dashboard/index');
	}

}
