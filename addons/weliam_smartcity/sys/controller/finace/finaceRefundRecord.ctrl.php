<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 退款记录
 * Author: zzw
 * Date: 2021/1/7 17:56
 * Class newCash_WeliamController
 */
class FinaceRefundRecord_WeliamController {
	//退款记录
	public function refundrecord(){
		global $_W,$_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$where['uniacid'] = $_W['uniacid'];
		if(is_agent()){
			$where['aid'] = $_W['aid'];
		}
		
		//支付方式
		if($_GPC['paytype']){
			$where['paytype'] = $_GPC['paytype'];
		}
		//退款方式
		if($_GPC['type']){
			$where['type'] = $_GPC['type'];
		}
		//插件
		if($_GPC['plugin'] && $_GPC['plugin'] != 'all'){
			$where['plugin'] = $_GPC['plugin'];
		}
		//订单筛选
		if (!empty($_GPC['keyword'])) {
			if (!empty($_GPC['keywordtype'])) {
				switch($_GPC['keywordtype']) {
					case 1 :
						$where['@orderno@'] = $_GPC['keyword'];
						break;
					case 2 :
						$where['@transid@'] = $_GPC['keyword'];
						break;
					default :
						break;
				}
				if ($_GPC['keywordtype'] == 3) {
					$keyword = $_GPC['keyword'];
					$params[':name'] = "%{$keyword}%";
					$stores = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :name", $params);
					if ($stores) {
						$storesids = "(";
						foreach ($stores as $key => $v) {
							if ($key == 0) {
								$storesids .= $v['id'];
							} else {
								$storesids .= "," . $v['id'];
							}
						}
						$storesids .= ")";
						$where['mid#'] = $storesids;
					} else {
						$where['mid#'] = "(0)";
					}
				}
				if ($_GPC['keywordtype'] == 4) {
					$keyword = $_GPC['keyword'];
					$params[':mobile'] = "%{$keyword}%";
					$stores = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND mobile LIKE :mobile", $params);
					if ($stores) {
						$storesids = "(";
						foreach ($stores as $key => $v) {
							if ($key == 0) {
								$storesids .= $v['id'];
							} else {
								$storesids .= "," . $v['id'];
							}
						}
						$storesids .= ")";
						$where['mid#'] = $storesids;
					} else {
						$where['mid#'] = "(0)";
					}
				}
				if ($_GPC['keywordtype'] == 5) {
					$keyword = $_GPC['keyword'];
					$params[':storename'] = "%{$keyword}%";
					$stores = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_merchantdata') . "WHERE uniacid = {$_W['uniacid']} AND storename LIKE :storename", $params);
					if ($stores) {
						$storesids = "(";
						foreach ($stores as $key => $v) {
							if ($key == 0) {
								$storesids .= $v['id'];
							} else {
								$storesids .= "," . $v['id'];
							}
						}
						$storesids .= ")";
						$where['sid#'] = $storesids;
					} else {
						$where['sid#'] = "(0)";
					}
				}
			}
		}
		
		
		//时间
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
        if ($_GPC['export'] != '') {
            $this -> exportrefund($where);
        }
		
		$records = Util::getNumData('*','wlmerchant_refund_record',$where,'createtime DESC',$pindex,$psize,1);
		$pager = $records[1];
		$records = $records[0];
		foreach ($records as $key => &$re) {
			$re['createtime'] = date('Y-m-d H:i:s',$re['createtime']);
			$re['storename'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$re['sid']),'storename');
			$member = pdo_get('wlmerchant_member',array('id' => $re['mid']),array('avatar','nickname'));
			$re['avatar'] = $member['avatar'];
			$re['nickname'] = $member['nickname'];
			if($re['plugin'] == 'rush'){
				$goodsid = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$re['orderid']),'activityid');
				$re['goodsname'] = pdo_getcolumn(PDO_NAME.'rush_activity',array('id'=>$goodsid),'name');
				$re['pluginname'] = '抢购';
			}else if($re['plugin'] == 'wlfightgroup'){
				$goodsid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$re['orderid']),'fkid');
				$re['goodsname'] = pdo_getcolumn(PDO_NAME.'fightgroup_goods',array('id'=>$goodsid),'name');
				$re['pluginname'] = '拼团';
			}else if($re['plugin'] == 'groupon'){
				$goodsid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$re['orderid']),'fkid');
				$re['goodsname'] = pdo_getcolumn(PDO_NAME.'groupon_activity',array('id'=>$goodsid),'name');
				$re['pluginname'] = '团购';
			}else if($re['plugin'] == 'bargain'){
				$goodsid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$re['orderid']),'fkid');
				$re['goodsname'] = pdo_getcolumn(PDO_NAME.'bargain_activity',array('id'=>$goodsid),'name');
				$re['pluginname'] = '砍价';
			}
			switch ($re['paytype']){
				case '2':
					$re['paytype'] = '微信支付';
					break;
				case '1':
					$re['paytype'] = '余额支付';
					break;
				case '3':
					$re['paytype'] = '支付宝';
					break;
				case '4':
					$re['paytype'] = '货到付款';
					break;
				case '5':
					$re['paytype'] = '小程序';
					break;		
				default:
					$re['paytype'] = '未知方式';
					break;
			}
			switch ($re['type']) {
				case '1':
					$re['type'] = '手机端退款';
					break;
				case '2':
					$re['type'] = '后台退款';
					break;
				case '3':
					$re['type'] = '自动退款';
					break;	
				default:
					$re['type'] = '其他';
					break;
			}
		}
		include  wl_template('finace/refundrecord');
	}
	//导出退款记录
    public function exportrefund($where){
        if (empty($where)){
            return FALSE;
        }
        set_time_limit(0);
        $records = Util::getNumData('*','wlmerchant_refund_record',$where,'createtime DESC',0,0,1);
        $records = $records[0];
        foreach ($records as $key => &$re) {
            $re['createtime'] = date('Y-m-d H:i:s',$re['createtime']);
            $re['storename'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$re['sid']),'storename');
            $member = pdo_get('wlmerchant_member',array('id' => $re['mid']),array('nickname'));
            $re['nickname'] = $member['nickname'];
            $re['status'] = $re['status'] > 0 ? '成功':'失败';
            $re['orderno'] = "\t".$re['orderno']."\t";
            if($re['plugin'] == 'rush'){
                $goodsid = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$re['orderid']),'activityid');
                $re['goodsname'] = pdo_getcolumn(PDO_NAME.'rush_activity',array('id'=>$goodsid),'name');
                $re['pluginname'] = '抢购';
            }else if($re['plugin'] == 'wlfightgroup'){
                $goodsid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$re['orderid']),'fkid');
                $re['goodsname'] = pdo_getcolumn(PDO_NAME.'fightgroup_goods',array('id'=>$goodsid),'name');
                $re['pluginname'] = '拼团';
            }else if($re['plugin'] == 'groupon'){
                $goodsid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$re['orderid']),'fkid');
                $re['goodsname'] = pdo_getcolumn(PDO_NAME.'groupon_activity',array('id'=>$goodsid),'name');
                $re['pluginname'] = '团购';
            }else if($re['plugin'] == 'bargain'){
                $goodsid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$re['orderid']),'fkid');
                $re['goodsname'] = pdo_getcolumn(PDO_NAME.'bargain_activity',array('id'=>$goodsid),'name');
                $re['pluginname'] = '砍价';
            }
            switch ($re['paytype']){
                case '2':
                    $re['paytype'] = '微信支付';
                    break;
                case '1':
                    $re['paytype'] = '余额支付';
                    break;
                case '3':
                    $re['paytype'] = '支付宝';
                    break;
                case '4':
                    $re['paytype'] = '货到付款';
                    break;
                case '5':
                    $re['paytype'] = '小程序';
                    break;
                default:
                    $re['paytype'] = '未知方式';
                    break;
            }
            switch ($re['type']) {
                case '1':
                    $re['type'] = '手机端退款';
                    break;
                case '2':
                    $re['type'] = '后台退款';
                    break;
                case '3':
                    $re['type'] = '自动退款';
                    break;
                default:
                    $re['type'] = '其他';
                    break;
            }
        }

        /* 输出表头 */
        $filter = array(
            'id'  => '记录id',//U
            'orderno' => '订单号',//A
            'pluginname'  => '所属应用',//B
            'goodsname' => '商品名称',//C
            'storename' => '所属商家',//F
            'nickname' => '买家昵称',//G
            'status' => '退款状态',//I
            'paytype' => '支付方式',//J
            'createtime' => '退款时间',//K
            'refundfee' => '退款金额',//M
            'remark' => '退款备注',//N
            'type' => '退款方式',//O
            'errmsg' => '退款描述'//P
        );
        $data = array();
        for ($i=0; $i < count($records) ; $i++) {
            foreach ($filter as $key => $title) {
                $data[$i][$key] = $records[$i][$key];
            }
        }
        util_csv::export_csv_2($data, $filter, '退款记录表.csv');
        exit();
    }

}
