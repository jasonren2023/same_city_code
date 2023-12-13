<?php
defined('IN_IA') or exit('Access Denied');

class Consumption {

	static function creditshop_adv_get($status = -1) {
		global $_W;
		$condition = " where uniacid = :uniacid";
		$params = array(":uniacid" => $_W["uniacid"]);
		if ($status != -1) {
			$condition .= " and status = " . $status;
		}
		$data = pdo_fetchall("select * from" . tablename("wlmerchant_consumption_adv") . " " . $condition . " order by displayorder desc", $params);
		if (!empty($data)) {
			foreach ($data as &$value) {
				$value["thumb"] = tomedia($value["thumb"]);
			}
		}
		return $data;
	}

	static function creditshop_can_exchange_goods($idOrGoods, $uid = "") {
		global $_W;
		$goods = $idOrGoods;
		if (!is_array($goods)) {
			$goods = creditshop_goods_get($goods);
		}
		if (empty($goods)) {
			return error(-1, "商品不存在！");
		}
		if (empty($uid)) {
			$uid = $_W["member"]["uid"];
		}
		$records_num = pdo_fetchcolumn("select count(*) FROM " . tablename("wlmerchant_consumption_order") . " where uniacid = :uniacid and uid = :uid and goods_id = :goods_id ", array(":uniacid" => $_W["uniacid"], "uid" => $uid, ":goods_id" => $goods["id"]));
		if ($goods["chance"] <= $records_num) {
			return error(-2, "兑换已达最大次数！");
		}
		return error(0, "可以兑换！");
	}

	static function creditshop_category_get($status = -1) {
		global $_W;
		$condition = " where uniacid = :uniacid";
		$params = array(":uniacid" => $_W["uniacid"]);
		if ($status != -1) {
			$condition .= " and status = " . $status;
		}
		$data = pdo_fetchall("select * from " . tablename("wlmerchant_consumption_category") . " " . $condition . " order by displayorder desc", $params);
		if (!empty($data)) {
			foreach ($data as &$value) {
				$value["thumb"] = tomedia($value["thumb"]);
			}
		}
		return $data;
	}

	static function creditshop_goodsall_get($filter = array( )) {
		global $_W;
		global $_GPC;
		if (empty($filter)) {
			if (!empty($_GPC["type"])) {
				$filter["type"] = trim($_GPC["type"]);
			}
			if (!empty($_GPC["title"])) {
				$filter["title"] = trim($_GPC["title"]);
			}
			if (!empty($_GPC["category_id"])) {
				$filter["category_id"] = intval($_GPC["category_id"]);
			}
		}
		if (empty($filter["page"])) {
			$filter["page"] = max(1, $_GPC["page"]);
		}
		if (empty($filter["psize"])) {
			$filter["psize"] = (intval($_GPC["psize"]) ? intval($_GPC["psize"]) : 20);
		}
		$condition = " where uniacid = :uniacid and status = 1";
		$params = array(":uniacid" => $_W["uniacid"]);
		if (!empty($filter["type"])) {
			$condition .= " and type = :type";
			$params[":type"] = $filter["type"];
		}
		if (!empty($filter["title"])) {
			$condition .= " AND title LIKE '%" . $filter["title"] . "%'";
		}
		if (!empty($filter["category_id"])) {
			$condition .= " and category_id = :category_id";
			$params[":category_id"] = $filter["category_id"];
		}
		//判断会员
		$halfmember = Member::checkhalfmember();
		
		$data = pdo_fetchall("SELECT * FROM " . tablename("wlmerchant_consumption_goods") . " " . $condition . " ORDER BY displayorder DESC LIMIT " . ($filter["page"] - 1) * $filter["psize"] . ", " . $filter["psize"], $params);
		if (!empty($data)) {
			foreach ($data as &$value) {
				$value["thumb"] = tomedia($value["thumb"]);
				if ($value["type"] == "redpacket") {
					$value["redpacket"] = iunserializer($value["redpacket"]);
				}
				if($halfmember && $value['vipstatus'] == 1){
					$value['use_credit1'] = $value['vipcredit1'];
					$value['use_credit2'] = $value['vipcredit2'];
				}
			}
		}
		return $data;
	}

	static function creditshop_goods_get($goods_id) {
		global $_W;
		if (empty($goods_id)) {
			return error(-1, "请输入商品编号");
		}
		$data = pdo_get("wlmerchant_consumption_goods", array("uniacid" => $_W["uniacid"], "id" => $goods_id));
		$data["records_num"] = pdo_fetchcolumn("select count(*) FROM " . tablename("wlmerchant_consumption_order") . " where uniacid = :uniacid and goods_id = :goods_id ", array(":uniacid" => $_W["uniacid"], ":goods_id" => $goods_id));
		if (!empty($data)) {
			$data["thumb"] = tomedia($data["thumb"]);
		}
		return $data;
	}

	static function creditshop_record_get($filter = array( )) {
		global $_W;
		global $_GPC;
		if (empty($filter)) {
			if (!empty($_GPC["id"])) {
				$filter["goods_id"] = intval($_GPC["id"]);
			} else {
				return error(-1, "请输入商品编号");
			}
		}
		if (empty($filter["page"])) {
			$filter["page"] = max(1, $_GPC["page"]);
		}
		if (empty($filter["psize"])) {
			$filter["psize"] = (intval($_GPC["psize"]) ? intval($_GPC["psize"]) : 15);
		}
		$data = pdo_fetchall("select a.addtime, b.avatar, b.nickname FROM " . tablename("wlmerchant_consumption_order") . " as a left join " . tablename("tiny_wmall_members") . " as b on a.uid = b.uid where a.uniacid = :uniacid and a.goods_id = :goods_id limit " . ($filter["page"] - 1) * $filter["psize"] . ", " . $filter["psize"], array(":uniacid" => $_W["uniacid"], ":goods_id" => $filter["goods_id"]));
		if (!empty($data)) {
			foreach ($data as &$value) {
				$value["addtime"] = date("Y/m/d H:i", $value["addtime"]);
			}
		}
		return $data;
	}

	static function creditshop_order_get($id) {
		global $_W;
		$data = pdo_get("wlmerchant_consumption_order", array("uniacid" => $_W["uniacid"], "id" => $id));
		if (!empty($data)) {
			$data["data"] = iunserializer($data["data"]);
			$data["addtime"] = date("Y/m/d H:i", $data["addtime"]);
			$goods = creditshop_goods_get($data["goods_id"]);
			$data["goods_info"] = $goods;
		}
		return $data;
	}

	static function creditshop_orderall_get($filter = array( )) {
		global $_W;
		global $_GPC;
		if (empty($filter["page"])) {
			$filter["page"] = max(1, $_GPC["page"]);
		}
		if (empty($filter["psize"])) {
			$filter["psize"] = (intval($_GPC["psize"]) ? intval($_GPC["psize"]) : 6);
		}
		$condition = " where a.uniacid = :uniacid and a.uid = :uid";
		$params = array(":uniacid" => $_W["uniacid"], ":uid" => $_W["member"]["uid"]);
		$data = pdo_fetchall("select a.*, c.title, c.thumb from " . tablename("wlmerchant_consumption_order") . " as a left join " . tablename("wlmerchant_consumption_goods") . " as c on a.goods_id = c.id " . $condition . " order by a.id desc limit " . ($filter["page"] - 1) * $filter["psize"] . ", " . $filter["psize"], $params);
		if (!empty($data)) {
			foreach ($data as &$value) {
				$value["addtime"] = date("Y/m/d H:i", $value["addtime"]);
				$value["data"] = iunserializer($value["data"]);
				$value["thumb"] = tomedia($value["thumb"]);
			}
		}
		return $data;
	}

	static function creditshop_order_update($orderOrId, $type, $extra = array( )) {
		global $_W;
		$order = $orderOrId;
		if (!is_array($order)) {
			$order = creditshop_order_get($order);
		}
		if (empty($order)) {
			return error(-1, "商品不存在！");
		}
		if ($type == "pay") {
			$update = array("is_pay" => 1, "pay_type" => $extra["type"], "paytime" => TIMESTAMP);
			pdo_update("wlmerchant_consumption_order", $update, array("id" => $order["id"]));
			if ($order["goods_type"] == "redpacket") {
				mload() -> model("redPacket");
				$redpacket = $order["data"]["redpacket"];
				$data = array("title" => $redpacket["name"], "channel" => "creditShop", "type" => "grant", "discount" => $redpacket["discount"], "days_limit" => $redpacket["use_days_limit"], "grant_days_effect" => $redpacket["grant_days_effect"], "condition" => $redpacket["condition"], "uid" => $order["uid"]);
				$res = redPacket_grant($data);
				if ($res) {
					pdo_update("wlmerchant_consumption_order", array("grant_status" => 1), array("id" => $order["id"]));
					return NULL;
				}
			} else {
				if ($order["goods_type"] == "credit2") {
					$res = member_credit_update($order["uid"], "credit2", $order["data"]["credit2"]);
					if ($res) {
						pdo_update("wlmerchant_consumption_order", array("grant_status" => 1), array("id" => $order["id"]));
						return NULL;
					}
				}
			}
		} else {
			if ($type == "handle") {
				if ($order["status"] == 1) {
					pdo_update("wlmerchant_consumption_order", array("status" => 2), array("id" => $order["id"]));
					return NULL;
				}
			} else {
				if ($type == "cancel" && $order["is_pay"] == 0 && $order["status"] == 1 && 0 < $order["use_credit1"] && $order["use_credit1_status"] == 1 && 0 < $order["use_credit2"]) {
					$status = member_credit_update($order["uid"], "credit1", $order["use_credit1"]);
					if (is_error($status)) {
						imessage(-1, $status["message"], "", "ajax");
					}
					pdo_update("wlmerchant_consumption_order", array("status" => 3), array("id" => $order["id"]));
				}
			}
		}
	}


	/** 
	* 异步支付结果回调 ，处理业务逻辑
	* 
	* @access public
	* @name  
	* @param mixed  参数一的说明 
	* @return array 
	*/  
	static function payconsumOrderNotify($params){
		global $_W;
		Util::wl_log('payResult_notify',PATH_PLUGIN."bargain/data/", $params); //写入异步日志记录
		$order_out = pdo_fetch("select * from".tablename(PDO_NAME.'order')."where orderno='{$params['tid']}'");
		$activeInfo = self::creditshop_goods_get($order_out['fkid']);
		$data = self::getPayData($params,$order_out); //得到支付参数，处理代付
		if($order_out['status'] == 0){
			$_W['aid'] = $order_out['aid'];
			if($activeInfo['type'] == 'credit2' || $activeInfo['type'] == 'halfcard'){
				$data['status'] = 3;
				$status = 3;
			}else{
				$data['status'] = 8;
				$status = 1;
			}
			//分销 分销
			if(p('distribution') && $activeInfo['isdistri']){
				$_W['aid'] = $order_out['aid'];
				if($order_out['expressid']){
					$expressprice = pdo_getcolumn(PDO_NAME.'express',array('id'=>$order_out['expressid']),'expressprice');
				}else {
					$expressprice = 0;
				}
				$dismoney = sprintf("%.2f",$order_out['price'] - $expressprice);
				$disorderid = Distribution::disCore($order_out['mid'],$dismoney,$activeInfo['onedismoney'],$activeInfo['twodismoney'],0,$order_out['id'],'consumption',$activeInfo['dissettime']);
				$data['disorderid'] = $disorderid;
			}
			//创建记录
            if($activeInfo['type'] == 'credit2'){
                $res = Member::credit_update_credit2($order_out['mid'],$activeInfo['credit2'],'兑换['.$activeInfo['title'].']获得余额');
            }else if($activeInfo['type'] == 'halfcard'){
                $res = self::conhalfcard($order_out['mid'],$activeInfo['halfcardid'],$order_out['name']);
            }else{
                $res = 1;
            }
            if($res){
                $recorddata = array(
                    'uniacid'    => $order_out['uniacid'],
                    'mid'        => $order_out['mid'],
                    'goodsid'    => $activeInfo['id'],
                    'orderid'    => $order_out['id'],
                    'createtime' => time(),
                    'status'     => $status,
                    'integral'   => $activeInfo['use_credit1'],
                    'money'      => $activeInfo['use_credit2'],
                    'expressid'  => $order_out['expressid']
                );
                $res3 =  pdo_insert(PDO_NAME.'consumption_record',$recorddata);
            }
			pdo_update(PDO_NAME.'order',$data, array('orderno' => $params['tid'])); //更新订单状态
            $url = h5_url('pages/subPages/orderList/orderList');
            $jftext = $_W['wlsetting']['trade']['credittext']?$_W['wlsetting']['trade']['credittext']:'积分';
            $messagedata = array(
                'first'   => '恭喜您,一个商品兑换成功',
                'type'    => $jftext.'兑换',//业务类型
                'content' => '商品名:['.$activeInfo['title'].']' ,//业务内容
                'status'  => '已完成' ,//处理结果
                'time'    => date('Y-m-d H:i:s',time()),//操作时间
                'remark'  => '点击查看兑换记录，如有问题请联系管理员'
            );
            TempModel::sendInit('service',$order_out['mid'],$messagedata,$_W['source'],$url);
		}
		
	}
	
	
	static function getPayData($params,$order_out){
		$data = array('status' => $params['result'] == 'success' ? 1 : 0);
		if($params['is_usecard']==1){
			$fee = $params['card_fee'];
			$data['is_usecard'] = 1;
		}else{
			$fee = $params['fee'];
		}
		//$paytype = array('credit' => 1, 'wechat' => 2, 'alipay' => 3, 'delivery' => 4, 'wxapp' => 5);
		$data['paytype'] = $params['type'];
		if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
		$data['paytime'] = TIMESTAMP;
		$data['price'] = $fee;
		$data['createtime'] = TIMESTAMP;
		SingleMerchant::updateAmount($fee, $order_out['sid'],$order_out['id'],1,'订单支付成功');
		return $data;
	}
	
	static function payconsumOrderReturn($params,$backurl=false){
		Util::wl_log('payResult_return',PATH_PLUGIN."bargain/data/", $params);//写入日志记录
		$order_out = pdo_get(PDO_NAME.'order',array('orderno'=>$params['tid']),array('id'));
		wl_message('支付成功',h5_url('pages/mainPages/paySuccess/paySuccess',['id'=>$order_out['id'],'type'=>9]),'success');
	}
	
	static function conhalfcard($mid,$halfcardid,$username){
		global $_W;
		$halfcard = pdo_get('wlmerchant_halfcard_type',array('id' => $halfcardid));
		$limittime = time() + $halfcard['days']*24*3600;
		if($halfcard){
		    if(empty($halfcard['aid'])){
                $halfcard['aid'] = $_W['aid'];
            }
			$halfcarddata = array(
				'uniacid'    => $_W['uniacid'],
				'aid'        => $halfcard['aid'],
				'mid'        => $mid,
				'expiretime' => $limittime,
				'username'   => $username,
				'levelid'    => $halfcard['levelid'],
				'createtime' => time()
			);
			$res = pdo_insert(PDO_NAME.'halfcardmember',$halfcarddata);
		}
		return $res;
	}
	
}
