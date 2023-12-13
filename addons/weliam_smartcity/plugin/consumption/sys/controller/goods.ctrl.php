<?php
defined('IN_IA') or exit('Access Denied');

class Goods_WeliamController {
	
	public function goods_list() {
		global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $data = array();
        $data['uniacid'] = $_W["uniacid"];
        $type = trim($_GPC["type"]);
        if (!empty($type)) {
            $data['type'] = $type;
        }
        if (!empty($_GPC["keyword"])) {
            $data['@title@'] = $_GPC['keyword'];
        }
        $lists = Util::getNumData('*','wlmerchant_consumption_goods',$data,'displayorder DESC,ID DESC', $pindex, $psize, 1);
        $pager = $lists[1];
        $lists = $lists[0];
        foreach($lists as &$li){
            $li['salenum'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "order") . " WHERE plugin = 'consumption' AND fkid = {$li['id']} ");
        }

		include wl_template('consumption/goods');
	}

	public function goods_post() {
		global $_W, $_GPC;
		$id = intval($_GPC["id"]);
		if(p('distribution')){
			$distriset = Setting::wlsetting_read('distribution');
		}else{
			$distriset = 0;
		}
		if (0 < $id) {
			$item = Consumption::creditshop_goods_get($id);
			$item["redpacket"] = iunserializer($item["redpacket"]);
            $advs = unserialize($item['advs']);

            if ($item['usedatestatus'] == 1) {
                $item['week'] = unserialize($item['week']);
            }
            if ($item['usedatestatus'] == 2) {
                $item['day'] = unserialize($item['day']);
            }
            $item['level']  = unserialize($item['level']);

        }




		if ($_W["ispost"]) {
            $data = array(
                "uniacid"        => $_W["uniacid"] ,
                "title"          => trim($_GPC["title"]) ,
                "category_id"    => intval($_GPC["creditshop_category_id"]) ,
                "type"           => trim($_GPC["type"]) ,
                "credit2"        => trim($_GPC["credit2"]) ,
                "old_price"      => trim($_GPC["old_price"]) ,
                "status"         => intval($_GPC["status"]) ,
                "thumb"          => trim($_GPC["thumb"]) ,
                "chance"         => intval($_GPC["chance"]) ,
                "use_credit1"    => trim($_GPC["use_credit1"]) ,
                "use_credit2"    => trim($_GPC["use_credit2"]) ,
                "description"    => htmlspecialchars_decode($_GPC["description"]) ,
                "displayorder"   => intval($_GPC["displayorder"]) ,
                "vipstatus"      => intval($_GPC["vipstatus"]) ,
                "stock"          => intval($_GPC["stock"]) ,
                "vipcredit1"     => intval($_GPC["vipcredit1"]) ,
                "vipcredit2"     => trim($_GPC["vipcredit2"]) ,
                "dissettime"     => trim($_GPC["dissettime"]) ,
                "halfcardid"     => trim($_GPC["halfcardid"]) ,
                "advs"           => serialize($_GPC["advs"]) ,
                'community_id'   => $_GPC['community_id'] ,
                'describe'       => $_GPC['describe'] ,
            );

			if($data['vipstatus'] == 1 && $data['vipcredit1'] < 1){
				show_json(0, array('message' => '会员消耗积分必须大于0'));
			}
			if($data['type'] == 'halfcard' && empty($data['halfcardid'])){
				show_json(0, array('message' => '请选择一卡通会员类型'));
			}
			//一卡通等级
            $level                  = $_GPC['level'];
            $data['level']          = serialize($level);
			$hour = array();
			$category = array();
			if (!empty($_GPC["category_id"])) {
				foreach ($_GPC["category_id"] as $key => $value) {
					if (empty($value)) {
						continue;
					}
					$category[] = array("id" => intval($value), "title" => trim($_GPC["category_title"][$key]), "src" => trim($_GPC["category_src"][$key]));
				}
			}
			$redpacket = array("name" => trim($_GPC["name"]), "discount" => trim($_GPC["discount"]), "condition" => trim($_GPC["condition"]), "grant_days_effect" => intval($_GPC["grant_days_effect"]), "use_days_limit" => intval($_GPC["use_days_limit"]), "hour" => $hour, "category" => $category);
			$data["redpacket"] = iserializer($redpacket);
			//运费模板
			$data['expressid'] = $_GPC['expressid'];
			//分销
			$data['isdistri'] = $_GPC['isdistri'];
			if($data['isdistri']){
				$data['onedismoney'] = $_GPC['onedismoney'];
				$data['twodismoney'] = $_GPC['twodismoney'];
			}
            //定时兑换
            $goods = $_GPC['goods'] ? : [];
            if ($goods['usedatestatus'] == 1) {
                $goods['week'] = serialize($goods['week']);
                $goods['day'] = '';
            }else if ($goods['usedatestatus'] == 2) {
                $goods['day'] = serialize($goods['day']);
                $goods['week'] = '';
            }else{
                $goods['usedatestatus'] = 0;
                $goods['day'] = '';
                $goods['week'] = '';
            }
            $data = array_merge($data,$goods);

			if (0 < $id) {
				pdo_update(PDO_NAME."consumption_goods", $data, ["uniacid" => $_W["uniacid"], "id" => $id]);
			} else {
				pdo_insert(PDO_NAME."consumption_goods", $data);
			}


			show_json(1, array('message' => '编辑商品成功', 'url' => web_url('consumption/goods/goods_list')));
		}
		//分类信息
        $categorys = Consumption::creditshop_category_get();
        //获取运费模板
        $express = Store::getNumExpress('*',array('uniacid'=>$_W['uniacid']),'ID DESC',0,0,0);
        $express = $express[0];
        //获取一卡通类型
        $halfcardlist = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_halfcard_type')."WHERE uniacid = {$_W['uniacid']} ORDER BY sort DESC");
		//获取社群信息
        $communitylist = pdo_getall('wlmerchant_community',['uniacid' => $_W['uniacid'],'aid'=>$_W['aid']],['id','communname']);
        //一卡通等级
        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");



		include wl_template('consumption/goods');
	}

	public function goods_del() {
		global $_W, $_GPC;
		$ids = $_GPC["id"];
		if (!is_array($ids)) {
			$ids = array($ids);
		}
		foreach ($ids as $v) {
			pdo_delete("wlmerchant_consumption_goods", array("uniacid" => $_W["uniacid"], "id" => $v));
		}
		show_json(1, "删除商品成功");
	}

    /**
     * Comment: 商品快捷上下架
     * Author: zzw
     */
	public function isUpperShelf(){
	    global $_W,$_GPC;
	    //获取信息内容
	    $id = $_GPC['id'];
	    $status = $_GPC['status'];//1=上架中要修改为下架。0=下架中要修改为上架
        if($status == 1){
            $updata['status'] = 0;
        }else{
            $updata['status'] = 1;
        }
        //修改商品的状态
        pdo_update(PDO_NAME."consumption_goods",$updata,array('id'=>$id));
	    wl_json(1,'请求成功',$updata);
    }

}
