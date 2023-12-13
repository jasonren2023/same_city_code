<?php
defined('IN_IA') or exit('Access Denied');

class newCash_WeliamController {
    //账户列表
	public function currentlist(){
		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$where = is_agent() ? array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']) : array('uniacid' => $_W['uniacid']);
		
		$type = trim($_GPC['type']);
		$where['status'] = ($type == 'store') ? 1 : 2;
		if ($type == 'store') {
			if(is_agent()){
				$stores = pdo_getall('wlmerchant_merchantdata',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid']),array('storename','id'));
			}else{
				$stores = pdo_getall('wlmerchant_merchantdata',array('uniacid' => $_W['uniacid']),array('storename','id'));
			}
		} else {
			$agents = pdo_getall('wlmerchant_agentusers',array('uniacid' => $_W['uniacid']),array('agentname','id'));
		}

		if(is_store()){
            $where['objid'] = $_W['storeid'];
        }

		$objid = intval($_GPC['objid']);
		if($objid){
			$where['objid'] = $objid;
		} elseif($type == 'agent' && is_agent()) {
			$where['objid'] = $_W['aid'];
		}
		
		$trade_type = intval($_GPC['trade_type']);
		if($trade_type){
			$where['type'] = $trade_type;
		}
		
		$days = (isset($_GPC["days"]) ? intval($_GPC["days"]) : -2);
	    $todaytime = strtotime(date("Y-m-d"));
	    $starttime = $todaytime;
	    $endtime = $starttime + 86399;
	    if($days > -2){
	        if($days == -1 ){
	        	if(empty($_GPC["addtime"])){
	        		$days = -2;
	        	}else{
		        	$starttime = strtotime($_GPC["addtime"]["start"]);
		            $endtime = strtotime($_GPC["addtime"]["end"]);
					$where['createtime>'] = $starttime;
					$where['createtime<'] = $endtime;
	        	}
	        }else{
	            $starttime = strtotime("-" . $days . " days",$todaytime);
				$where['createtime>'] = $starttime;
	        }
	    }

        if($_GPC['outflag']){
            $this -> outCurrent($where);
        }
		
		$records = Util::getNumData('*','wlmerchant_current', $where, 'ID DESC', $pindex, $psize, 1);
		$pager = $records[1];
		$records = $records[0];
		foreach ($records as $key => &$re) {
			if($re['status'] == 1){
				$re['objname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $re['objid']), 'storename');
			}else if($re['status'] == 2){
				if(empty($re['objid'])){
                    $re['objname'] = '总后台';
                }else{
                    $re['objname'] = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$re['objid']),'agentname');
                }
			}
			if ($re['type'] == 1) {
				$re['typename'] = '抢购订单结算';
				$re['css'] = 'success';
			}else if($re['type'] == 10) {
				$re['typename'] = '团购订单结算';
				$re['css'] = 'info';
			} else if ($re['type'] == 2) {
				$re['typename'] = '拼团订单结算';
				$re['css'] = 'warning';
			} else if ($re['type'] == 3) {
				$re['typename'] = '卡券订单结算';
				$re['css'] = 'success';
			} else if ($re['type'] == 4) {
				$re['typename'] = '一卡通订单结算';
				$re['css'] = 'info';
			} else if ($re['type'] == 5) {
				$re['typename'] = '掌上信息订单结算';
				$re['css'] = 'success';
			} else if ($re['type'] == 6) {
				$re['typename'] = '付费入驻订单结算';
				$re['css'] = 'info';
			} else if ($re['type'] == 7) {
				if ($re['fee'] < 0) {
					$re['typename'] = '提现申请';
					$re['css'] = 'default';
				} else {
					$re['typename'] = '提现申请驳回';
					$re['css'] = 'danger';
				}
			}else if ($re['type'] == 8) {
			    if(Customized::init('distributionText') > 0){
                    $re['typename'] = '共享股东订单结算';
                }else{
                    $re['typename'] = '分销合伙人订单结算';
                }
				$re['css'] = 'warning';
			}else if ($re['type'] == 9) {
				$re['typename'] = '商户活动订单结算';
				$re['css'] = 'warning';
			}else if ($re['type'] == -1) {
				$re['typename'] = '后台修改';
				$re['css'] = 'default';
			}else if ($re['type'] == 11) {
				$re['typename'] = '在线买单';
				$re['css'] = 'warning';
			}else if ($re['type'] == 12) {
				$re['typename'] = '砍价订单结算';
				$re['css'] = 'success';
			}else if ($re['type'] == 13) {
                $re['typename'] = '同城名片订单结算';
                $re['css'] = 'warning';
            }else if ($re['type'] == 14) {
                $re['typename'] = '礼包核销结算';
                $re['css'] = 'warning';
            }else if ($re['type'] == 140) {
                $re['typename'] = '同城配送订单结算';
                $re['css'] = 'info';
            }else if ($re['type'] == 15) {
                $re['typename'] = '支付返现修改金额';
                $re['css'] = 'warning';
                $re['remark'] = pdo_getcolumn(PDO_NAME.'payback_record',array('id'=>$re['orderid']),'remark');
            }else if ($re['type'] == 150) {
                $re['typename'] = '黄页114';
                $re['css'] = 'warning';
            }else if ($re['type'] == 18) {
                $re['typename'] = '房产信息';
                $re['css'] = 'warning';
            }
		}
		
		include  wl_template('finace/currentlist');
	}
	//导出账户明细
	public function outCurrent($where){
        global $_W;
        $records = Util::getNumData('*','wlmerchant_current', $where, 'ID DESC', 0, 0, 1);
        $records = $records[0];
        foreach ($records as $key => &$re) {
            $re['createtime'] = "\t".date('Y-m-d H:i:s',$re['createtime'])."\t";
            if($re['status'] == 1){
                $re['objname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $re['objid']), 'storename');
            }else if($re['status'] == 2){
                if(empty($re['objid'])){
                    $re['objname'] = '总后台';
                }else{
                    $re['objname'] = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$re['objid']),'agentname');
                }
            }
            if ($re['type'] == 1) {
                $re['typename'] = '抢购订单结算';
            }else if($re['type'] == 10) {
                $re['typename'] = '团购订单结算';
            } else if ($re['type'] == 2) {
                $re['typename'] = '拼团订单结算';
            } else if ($re['type'] == 3) {
                $re['typename'] = '卡券订单结算';
            } else if ($re['type'] == 4) {
                $re['typename'] = '一卡通订单结算';
            } else if ($re['type'] == 5) {
                $re['typename'] = '掌上信息订单结算';
            } else if ($re['type'] == 6) {
                $re['typename'] = '付费入驻订单结算';
            } else if ($re['type'] == 7) {
                if ($re['fee'] < 0) {
                    $re['typename'] = '提现申请';
                } else {
                    $re['typename'] = '提现申请驳回';
                }
            }else if ($re['type'] == 8) {
                if(Customized::init('distributionText') > 0){
                    $re['typename'] = '共享股东订单结算';
                }else{
                    $re['typename'] = '分销合伙人订单结算';
                }
            }else if ($re['type'] == 9) {
                $re['typename'] = '商户活动订单结算';
            }else if ($re['type'] == -1) {
                $re['typename'] = '后台修改';
            }else if ($re['type'] == 11) {
                $re['typename'] = '在线买单';
            }else if ($re['type'] == 12) {
                $re['typename'] = '砍价订单结算';
            }else if ($re['type'] == 13) {
                $re['typename'] = '同城名片订单结算';
            }else if ($re['type'] == 14) {
                $re['typename'] = '礼包核销结算';
            }else if ($re['type'] == 140) {
                $re['typename'] = '同城配送订单结算';
            }else if ($re['type'] == 15) {
                $re['typename'] = '支付返现修改金额';
                $re['remark'] = pdo_getcolumn(PDO_NAME.'payback_record',array('id'=>$re['orderid']),'remark');
            }else if ($re['type'] == 150) {
                $re['typename'] = '黄页114';
            }else if ($re['type'] == 18) {
                $re['typename'] = '房产信息';
            }
        }
        /* 输出表头 */
        $filter = array(
            'createtime' => '记录时间',
            'objname' => '账户名称',
            'typename'  => '类型',
            'fee' => '收入|支出(元)',
            'nowmoney' => '账户余额',
            'remark' => '核销码/备注',
        );

        $data = array();
        foreach ($records as $k => $v) {
            foreach ($filter as $key => $title) {
                $data[$k][$key] = $v[$key];
            }
        }
        util_csv::export_csv_2($data,$filter, '金额变更记录表.csv');
        exit;

    }

	//修改备注
	public function editremark(){
		global $_W,$_GPC;
		$id = intval($_GPC['id']);
		$remark = trim($_GPC['value']);
		$type = $_GPC['type'];
		if($type == 1){
			$res = pdo_update('wlmerchant_rush_order',array('adminremark' => $remark),array('id' => $id));
		}else{
			$res = pdo_update('wlmerchant_order',array('remark' => $remark),array('id' => $id));
		}		
		if($res){
			show_json(1, '备注修改成功');
		}else{
			show_json(0, '备注修改失败,请刷新页面重试！');
		}
	}

}
