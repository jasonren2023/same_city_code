<?php
defined('IN_IA') or exit('Access Denied');

class Fightgoods_WeliamController {
	//商品列表	
	function ptgoodslist(){
		global $_W, $_GPC;

		if(is_store()){
            $status10 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} and merchantid = {$_W['storeid']}");
            $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status=1 and aid={$_W['aid']} and merchantid = {$_W['storeid']}");
            $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status=2 and aid={$_W['aid']} and merchantid = {$_W['storeid']}");
            $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status=3 and aid={$_W['aid']} and merchantid = {$_W['storeid']}");
            $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status IN (0,4) and aid={$_W['aid']} and merchantid = {$_W['storeid']}");
            $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status=5 and aid={$_W['aid']} and merchantid = {$_W['storeid']}");
            $status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status=6 and aid={$_W['aid']} and merchantid = {$_W['storeid']}");
            $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status=8 and aid={$_W['aid']} and merchantid = {$_W['storeid']}");
        }else{
            $status10 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']}");
            $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status=1 and aid={$_W['aid']}");
            $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status=2 and aid={$_W['aid']}");
            $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status=3 and aid={$_W['aid']}");
            $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status IN (0,4) and aid={$_W['aid']}");
            $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status=5 and aid={$_W['aid']}");
            $status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status=6 and aid={$_W['aid']}");
            $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'fightgroup_goods') . " WHERE uniacid={$_W['uniacid']} and status=8 and aid={$_W['aid']}");
        }

		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$wheres = array();
		$wheres['uniacid'] = $_W['uniacid'];
		$wheres['aid'] = $_W['aid'];
		$status = $_GPC['status'];
        if ($status == 4) {
            $wheres['status#'] = "(0,4)";
        } else if (!empty($status)) {
            $wheres['status'] = intval($status);
        }
		if(is_store()){
            $wheres['merchantid'] = $_W['storeid'];
        }
		if (!empty($_GPC['keyword'])) {
			if(!empty($_GPC['keywordtype'])){
				switch($_GPC['keywordtype']){
					case 1: $wheres['@id@'] = $_GPC['keyword'];break;
					case 2: $wheres['@merchantid@'] = $_GPC['keyword'];break;
					default:break;
				}
				if($_GPC['keywordtype'] == 3){
					$keyword = $_GPC['keyword'];
					$params[':name'] = "%{$keyword}%";
					$goods = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_fightgroup_goods')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND name LIKE :name",$params);
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
						$wheres['id#'] = $goodids;
					}else {
						$wheres['id#'] = "(0)";
					}
				}
				if($_GPC['keywordtype'] == 4){
					$keyword = $_GPC['keyword'];
					$params[':storename'] = "%{$keyword}%";
					$merchant = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_merchantdata')."WHERE uniacid = {$_W['uniacid']}  AND storename LIKE :storename",$params);
					if($merchant){
						$sids = "(";
						foreach ($merchant as $key => $v) {
							if($key == 0){
								$sids.= $v['id'];
							}else{
								$sids.= ",".$v['id'];
							}	
						}
						$sids.= ")";
						$wheres['merchantid#'] = $sids;
					}else {
						$wheres['merchantid#'] = "(0)";
					}
				}
			}
		}
		
		$goodslist = Wlfightgroup::getNumGoods('*',$wheres,'listorder DESC,id DESC',$pindex, $psize, 1);
		$pager = $goodslist[1];
		$list = $goodslist[0];
		foreach ($list as $key => &$v) {
			$merchant = pdo_get('wlmerchant_merchantdata',array('id' => $v['merchantid']),array('storename'));
			$v['storename'] = $merchant['storename'];
			$v['groupnum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_fightgroup_group')." WHERE goodsid = {$v['id']} AND status = 2");
			$v['placeorder'] = WeliamWeChat::getSalesNum(3,$v['id'],0,1,0);  //已下单
			if(empty($v['placeorder'])){$v['placeorder'] = 0;}
			$v['alreadypay'] = WeliamWeChat::getSalesNum(3,$v['id'],0,2,0);  //已支付
			if(empty($v['alreadypay'])){$v['alreadypay'] = 0;}
			$v['alreadyuse'] = WeliamWeChat::getSalesNum(3,$v['id'],0,3,0);  //已使用
			if(empty($v['alreadyuse'])){$v['alreadyuse'] = 0;}
		}
		include wl_template('ptgoods/ptgoodslist');
	}
	function delete(){
		global $_W,$_GPC;
		$id = $_GPC['id'];
		$status = $_GPC['status'];
		if($status == 1){
			$res = pdo_update('wlmerchant_fightgroup_goods',array('status'=>0),array('id' => $id));
		}else{
            $rush = pdo_get('wlmerchant_fightgroup_goods',array('id' => $id),array('limitstarttime','limitendtime','merchantid','status'));
            if(is_store()){
                $examine = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$rush['sid']),'audits');
                if(empty($examine)){
                    $changestatus = 5;
                }
            }
            if(empty($changestatus)){
                if ($rush['limitstarttime'] > time()) {
                    $changestatus = 1;
                }
                else if ($rush['limitstarttime'] < time() && time() < $rush['limitendtime']) {
                    $changestatus = 2;
                }
                else if ($rush['limitendtime'] < time()) {
                    $changestatus = 3;
                }
            }
            $res = pdo_update('wlmerchant_fightgroup_goods',array('status'=>$changestatus),array('id' => $id));
		}
		if($res){
			die(json_encode(array('errno'=>0)));
		}else {
			die(json_encode(array('errno'=>1)));
		}
	}
	//新建商品
	function creategood(){
		global $_W, $_GPC;
		$id = intval($_GPC['id']);
        $set = Setting::agentsetting_read('fightgroup');
		if(p('distribution')){
			$distriset = Setting::wlsetting_read('distribution');
		}else{
			$distriset = 0;
		}
		if(p('userlabel')){
			$labels = pdo_getall('wlmerchant_userlabel',array('uniacid' => $_W['uniacid']),array('id','name'),'','sort DESC');
		}
        if (p('diyposter')) {
            $posters = pdo_getall(PDO_NAME . 'poster', array('uniacid' => $_W['uniacid'], 'type' => 6), array('id', 'title'));
        }

		$presettags = pdo_getall('wlmerchant_tags',array('uniacid' => $_W['uniacid'],'aid'=>$_W['aid'],'type'=>2),array('id','title'),'','sort DESC');
		if($id){
			$goods = Wlfightgroup::getSingleGood($id,'*');
			$goods['adv'] = unserialize($goods['adv']);
			$goods['specdetail'] = unserialize($goods['specdetail']);
			$userlabel = unserialize($goods['userlabel']);
			$merchant = Wlfightgroup::getSingleMerchant($goods['merchantid'],'id,storename,logo,groupid');
			
			$nwespecs = pdo_fetchall('select * from ' . tablename('wlmerchant_goods_spec') . ' where goodsid=:id AND type = 2 order by displayorder asc', array(':id' => $id));
			foreach ($nwespecs as &$s){
			    $s['items'] = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_goods_spec_item')."WHERE uniacid = {$_W['uniacid']} AND specid = {$s['id']} ORDER BY displayorder ASC");
			}
			unset($s);
			$html = '';
			$options = pdo_fetchall('select * from ' . tablename('wlmerchant_goods_option') . ' where goodsid=:id and type = 2 order by id asc', array(':id' => $id));
			$specs = array();
			if (0 < count($options)){
				$specitemids = explode('_', $options[0]['specs']);
				foreach ($specitemids as $itemid ){
					foreach ($nwespecs as $ss ){
						$items = $ss['items'];
						foreach ($items as $it ){
							while ($it['id'] == $itemid){
								$specs[] = $ss;
								break;
							}
						}
					}
				}
				$html = '';
				$html .= '<table class="table table-bordered table-condensed">';
				$html .= '<thead>';
				$html .= '<tr class="active">';
				$len = count($specs);
				$newlen = 1;
				$h = array();
				$rowspans = array();
				$i = 0;
				while ($i < $len) 
				{
					$html .= '<th>' . $specs[$i]['title'] . '</th>';
					$itemlen = count($specs[$i]['items']);
					if ($itemlen <= 0) 
					{
						$itemlen = 1;
					}
					$newlen *= $itemlen;
					$h = array();
					$j = 0;
					while ($j < $newlen) 
					{
						$h[$i][$j] = array();
						++$j;
					}
					$l = count($specs[$i]['items']);
					$rowspans[$i] = 1;
					$j = $i + 1;
					while ($j < $len) 
					{
						$rowspans[$i] *= count($specs[$j]['items']);
						++$j;
					}
					++$i;
				}
				
				$html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">库存</div><div class="input-group"><input type="text" class="form-control input-sm option_stock_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_stock\');"></a></span></div></div></th>';
				$html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">拼团价</div><div class="input-group"><input type="text" class="form-control input-sm option_price_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_price\');"></a></span></div></div></th>';
				$html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">单购价</div><div class="input-group"><input type="text" class="form-control input-sm option_vipprice_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_vipprice\');"></a></span></div></div></th>';
				$html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">结算价</div><div class="input-group"><input type="text" class="form-control input-sm option_settlementmoney_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_settlementmoney\');"></a></span></div></div></th>';
				$html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">一级分销</div><div class="input-group"><input type="text" class="form-control input-sm option_onedismoney_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_onedismoney\');"></a></span></div></div></th>';
				if($distriset['ranknum']>1){
					$html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">二级分销</div><div class="input-group"><input type="text" class="form-control input-sm option_twodismoney_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_twodismoney\');"></a></span></div></div></th>';
				}
				if($distriset['ranknum']>2){
					$html .= '<th><div class=""><div style="padding-bottom:10px;text-align:center;">三级分销</div><div class="input-group"><input type="text" class="form-control input-sm option_threedismoney_all"  VALUE=""/><span class="input-group-addon" ><a href="javascript:;" class="fa fa-angle-double-down" title="批量设置" onclick="setCol(\'option_threedismoney\');"></a></span></div></div></th>';
				}
				$html .= '</tr></thead>';
				$m = 0;
				while ($m < $len){
					$k = 0;
					$kid = 0;
					$n = 0;
					$j = 0;
					while ($j < $newlen){
						$rowspan = $rowspans[$m];
						if (($j % $rowspan) == 0){
							$h[$m][$j] = array('html' => '<td class=\'full\' rowspan=\'' . $rowspan . '\'>' . $specs[$m]['items'][$kid]['title'] . '</td>', 'id' => $specs[$m]['items'][$kid]['id']);
						}
						else{
							$h[$m][$j] = array('html' => '', 'id' => $specs[$m]['items'][$kid]['id']);
						}
						++$n;
						if ($n == $rowspan){
							++$kid;
							if ((count($specs[$m]['items']) - 1) < $kid){
								$kid = 0;
							}
							$n = 0;
						}
						++$j;
					}
					++$m;
				}
				$hh = '';
				$i = 0;
				while ($i < $newlen){
					$hh .= '<tr>';
					$ids = array();
					$j = 0;
					while ($j < $len){
						$hh .= $h[$j][$i]['html'];
						
						$ids[] = $h[$j][$i]['id'];
						++$j;
					}
					$ids = implode('_', $ids);
					$val = array('id' => '', 'title' => '', 'stock' => '', 'price' => '', 'vipprice' => '', 'settlementmoney' => '', 'onedismoney' => '', 'twodismoney' => '', 'threedismoney' => '');
					foreach ($options as $o){
						while ($ids === $o['specs']){
							$val = array('id' => $o['id'], 'title' => $o['title'], 'stock' => $o['stock'], 'price' => $o['price'], 'vipprice' => $o['vipprice'], 'settlementmoney' => $o['settlementmoney'], 'onedismoney' => $o['onedismoney'], 'twodismoney' => $o['twodismoney'], 'threedismoney' => $o['threedismoney']);
							break;
						}
					}
					
					
					$hh .= '<td>';
					$hh .= '<input data-name="option_stock_' . $ids . '"  type="text" class="form-control option_stock option_stock_' . $ids . '" value="' . $val['stock'] . '"/>';
					$hh .= '</td>';
					$hh .= '<input data-name="option_id_' . $ids . '"  type="hidden" class="form-control option_id option_id_' . $ids . '" value="' . $val['id'] . '"/>';
					$hh .= '<input data-name="option_ids"  type="hidden" class="form-control option_ids option_ids_' . $ids . '" value="' . $ids . '"/>';
					$hh .= '<input data-name="option_title_' . $ids . '"  type="hidden" class="form-control option_title option_title_' . $ids . '" value="' . $val['title'] . '"/>';
					$hh .= '<td><input data-name="option_price_' . $ids . '" type="text" class="form-control option_price option_price_' . $ids . '" value="' . $val['price'] . '"/></td>';
					$hh .= '<td><input data-name="option_vipprice_' . $ids . '" type="text" class="form-control option_vipprice option_vipprice_' . $ids . '" value="' . $val['vipprice'] . '"/></td>';
					$hh .= '<td><input data-name="option_settlementmoney_' . $ids . '" type="text" class="form-control option_settlementmoney option_settlementmoney_' . $ids . '" " value="' . $val['settlementmoney'] . '"/></td>';
					$hh .= '<td><input data-name="option_onedismoney_' . $ids . '" type="text" class="form-control option_onedismoney option_onedismoney_' . $ids . '" " value="' . $val['onedismoney'] . '"/></td>';
					if($distriset['ranknum']>1){
					$hh .= '<td><input data-name="option_twodismoney_' . $ids . '" type="text" class="form-control option_twodismoney option_twodismoney_' . $ids . '" " value="' . $val['twodismoney'] . '"/></td>';
					}	
					if($distriset['ranknum']>2){
					$hh .= '<td><input data-name="option_threedismoney_' . $ids . '" type="text" class="form-control option_threedismoney option_threedismoney_' . $ids . '" " value="' . $val['threedismoney'] . '"/></td>';
					}
					$hh .= '</tr>';
					++$i;
				}
				$html .= $hh;
				$html .= '</table>';
			}
		}
		$goods['tag'] = unserialize($goods['tag']);
		$sale = $goods['num'] - $goods['levelnum'];
		//获取营销
		if($goods['markid']){
			$markid = $goods['markid'];
			$mark = pdo_get('wlmerchant_marking',array('id' => $markid));
		}
		//获取分类
		$wheres = array();
		$wheres['uniacid'] = $_W['uniacid'];
		$wheres['aid'] = $_W['aid'];
		$categorys = Wlfightgroup::getNumCategory('*',$wheres,'ID DESC',0,0,0);
		$categorys = $categorys[0];
		//获取运费模板
		$express = Wlfightgroup::getNumExpress('*',$wheres,'ID DESC',0,0,0);
		$express = $express[0];
		//限制时间
		if($goods['islimittime']){
			$limitstarttime = $goods['limitstarttime'];
			$limitendtime = $goods['limitendtime'];
		}else {
			$limitstarttime = time();
			$limitendtime = strtotime("+1 month");
		}		
		//提交 
		if ($_W['ispost']) {
			$goods = $_GPC['goods'];
			
			if(empty($goods['merchantid'])) wl_message('请选择商户');
			if(empty($goods['price'])) wl_message('请填写团购价格');
			if(empty($goods['oldprice'])) wl_message('请填写市场价格');
			if(empty($goods['name'])) wl_message('请填写商品名称');
			if($goods['peoplenum']<2) wl_message('组团人数最小为2');

			$goods['isdistri'] = $_GPC['isdistri'];
			$goods['independent'] = $_GPC['independent'];
			$goods['adv'] = serialize($goods['adv']);
			$goods['detail'] = htmlspecialchars_decode($goods['detail']);
			$goods['cutofftime'] = strtotime($goods['cutofftime']);
			if($goods['specstatus'] == 1){
				$equalname = $_GPC['equalname'];
				$attribute = $_GPC['attribute'];
				foreach($equalname as $k=>$v){
					if($v != ''){
						$goods['specdetail'][$k]['equalname'] = $v;
						$goods['specdetail'][$k]['attribute'] = $attribute[$k];					
					}
				}
				$goods['specdetail'] = serialize($goods['specdetail']);
			}elseif ($goods['specstatus'] == 2) {
				$diffname = $_GPC['diffname'];
				$diffprice = $_GPC['diffprice'];
				$diffalprice = $_GPC['diffalprice'];
				$settprice = $_GPC['settprice'];
				foreach($diffname as $k=>$v){
					if($v != ''){
						$goods['specdetail'][$k]['diffname'] = $v;
						$goods['specdetail'][$k]['diffprice'] = sprintf('%.2f',$diffprice[$k]);
						$goods['specdetail'][$k]['diffalprice'] = sprintf('%.2f',$diffalprice[$k]);	
						$goods['specdetail'][$k]['settprice'] = sprintf('%.2f',$settprice[$k]);			
					}
				}
				$goods['specdetail'] = serialize($goods['specdetail']);
			}
			$tags = $_GPC['tags'];
			$goods['tag'] = serialize($tags);
			//定时
			if($goods['islimittime']){
				$goods['limitstarttime'] = strtotime($_GPC['limittime']['start']);
				$goods['limitendtime'] = strtotime($_GPC['limittime']['end']); 
			}
			//营销内容
			$markdata = $_GPC['mark'];
			if($markid){
				$res2 = pdo_update('wlmerchant_marking',$markdata,array('id' => $markid));
			}else{
				$markdata['uniacid'] = $_W['uniacid'];
				$markdata['aid'] = $_W['aid'];
				pdo_insert('wlmerchant_marking',$markdata);
				$goods['markid'] = pdo_insertid();
			}
			//用户标签
			$userlabel = $_GPC['userlabel'];
			$goods['userlabel'] = serialize($userlabel);
			//规格
			if($goods['specstatus']){
				$totalstocks = 0;
				$spec_ids = $_POST['spec_id'];
				$spec_titles = $_POST['spec_title'];
				$specids = array();
				$len = count($spec_ids);
				$specids = array();
				$spec_items = array();
				$k = 0;
				$pricearray = array();
				
				while ($k < $len){
					$spec_id = '';
					$get_spec_id = $spec_ids[$k];
					$a = array(
						'uniacid' => $_W['uniacid'],
						'goodsid' => $id,
						'displayorder' => $k,
						'title' => $spec_titles[$get_spec_id]
					);
					if(is_numeric($get_spec_id)){  //判断是否是数字或字符串
						pdo_update('wlmerchant_goods_spec', $a, array('id' => $get_spec_id));
						$spec_id = $get_spec_id;
					}else{
						$a['type'] = 2;
						pdo_insert('wlmerchant_goods_spec', $a);
						$spec_id = pdo_insertid();
					}
					$spec_item_ids = $_POST['spec_item_id_' . $get_spec_id];
					$spec_item_titles = $_POST['spec_item_title_' . $get_spec_id];
					$spec_item_shows = $_POST['spec_item_show_' . $get_spec_id];
					$spec_item_thumbs = $_POST['spec_item_thumb_' . $get_spec_id];
					$spec_item_oldthumbs = $_POST['spec_item_oldthumb_' . $get_spec_id];
					$spec_item_virtuals = $_POST['spec_item_virtual_' . $get_spec_id];
					$itemlen = count($spec_item_ids);
					$itemids = array();
					$n = 0;
					while ($n < $itemlen){
						$item_id = '';
						$get_item_id = $spec_item_ids[$n];
						$d = array(
							'uniacid' => $_W['uniacid'],
							'specid' => $spec_id,
							'displayorder' => $n,
							'title' => $spec_item_titles[$n],
							'show' => $spec_item_shows[$n],
							'thumb' => $spec_item_thumbs[$n],
						);
						if (is_numeric($get_item_id)){
							pdo_update('wlmerchant_goods_spec_item', $d, array('id' => $get_item_id));
							$item_id = $get_item_id;
						}else{
							pdo_insert('wlmerchant_goods_spec_item', $d);
							$item_id = pdo_insertid();
						}
						$itemids[] = $item_id;
						$d['get_id'] = $get_item_id;
						$d['id'] = $item_id;
						$spec_items[] = $d;
						++$n;
					}
					if (0 < count($itemids)){
						pdo_query('delete from ' . tablename('wlmerchant_goods_spec_item') . ' where  specid=' . $spec_id . ' and id not in (' . implode(',', $itemids) . ')');
					}else{
						pdo_query('delete from ' . tablename('wlmerchant_goods_spec_item') . ' where  specid=' . $spec_id);
					}
					pdo_update('wlmerchant_goods_spec', array('content' => serialize($itemids)), array('id' => $spec_id));
					$specids[] = $spec_id;
					++$k;
				}
				if (0 < count($specids)){
					pdo_query('delete from ' . tablename('wlmerchant_goods_spec') . ' where type = 2 and goodsid=' . $id . ' and id not in (' . implode(',', $specids) . ')');
				}else{
					pdo_query('delete from ' . tablename('wlmerchant_goods_spec') . ' where type = 2 and goodsid=' . $id);
				}
				
				$optionArray = json_decode($_POST['optionArray'],true);
				$option_idss = $optionArray['option_ids'];
				$len = count($option_idss);
				$optionids = array();
				$k = 0;
				while ($k < $len){
					$option_id = '';
					$ids = $option_idss[$k];
					$get_option_id = $optionArray['option_id'][$k];
					$idsarr = explode('_', $ids);
					$newids = array();
					foreach ($idsarr as $key => $ida ){
						foreach ($spec_items as $it ){
							while ($it['get_id'] == $ida){
								$newids[] = $it['id'];
								break;
							}
						}
					}
					$newids = implode('_',$newids);
					$a = array(
						'uniacid' => $_W['uniacid'], 
						'stock' => $optionArray['option_stock'][$k], 
						'title' => $optionArray['option_title'][$k], 
						'price' => $optionArray['option_price'][$k], 
						'vipprice' => $optionArray['option_vipprice'][$k], 
						'settlementmoney' => $optionArray['option_settlementmoney'][$k], 
						'onedismoney' => $optionArray['option_onedismoney'][$k], 
						'twodismoney' => $optionArray['option_twodismoney'][$k], 
						'threedismoney' => $optionArray['option_threedismoney'][$k], 
						'goodsid' => $id, 
						'specs' => $newids,
						'type'  => 2
					);
					$totalstocks += $a['stock'];
					$pricearray[] = $a['price'];
					if (empty($get_option_id)){
						pdo_insert('wlmerchant_goods_option',$a);
						$option_id = pdo_insertid();
					}else{
						pdo_update('wlmerchant_goods_option',$a, array('id' => $get_option_id));
						$option_id = $get_option_id;
					}
					$optionids[] = $option_id;
					++$k;
				}
					
				if (0 < count($optionids)){
					pdo_query('delete from ' . tablename('wlmerchant_goods_option') . ' where type = 2 AND goodsid=' . $id . ' and id not in ( ' . implode(',', $optionids) . ')');
				}else{
					pdo_query('delete from ' . tablename('wlmerchant_goods_option') . ' where type = 2 AND goodsid=' . $id);
				}
				$res3 = 1;
				$goods['stock'] = $totalstocks;
			}
			
			//判断积分抵扣
			if($markdata['deduct']>0){
				if($goods['specstatus']){
					$price = min($pricearray);
				}else{
					$price = $goods['price'];
				}
				if($price < $markdata['deduct'] || $price == $markdata['deduct'] ){
					wl_message('积分不能全额抵扣', referer(),'error');
				}
			}
			
			if($id){
				$res = Wlfightgroup::updateGoods($goods,$id);
				if ($res || $res2 || $res3) {
					wl_message('更新商品成功', web_url('wlfightgroup/fightgoods/ptgoodslist'), 'success');
				} else {
					wl_message('更新商品失败', referer(),'error');
				}
			}else {
				$res = Wlfightgroup::saveGoods($goods);
				if ($res) {
					wl_message('创建商品成功', web_url('wlfightgroup/fightgoods/ptgoodslist'), 'success');
				} else {
					wl_message('创建商品失败', referer(), 'error');
				}
			}
		}
		
		include wl_template('goodshouse/createactive');
	}
	//删除商品
	function deleteGoods(){
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$res = Wlfightgroup::deteleGoods($id);
		if($res){
			show_json(1,'拼团删除成功');
		}else {
			show_json(0,'拼团删除失败，请重试');
		}
	}

    //删除商品
    function cutoffGoods(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $res = pdo_update('wlmerchant_fightgroup_goods',array('status' => 8),array('id' => $id));
        if($res){
            show_json(1,'拼团删除成功');
        }else {
            show_json(0,'拼团删除失败，请重试');
        }
    }

	//恢复商品
	function recoveryGoods(){
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$res = Wlfightgroup::recoveryGoods($id);
		if($res){
			die(json_encode(array('errno'=>0)));
		}else {
			die(json_encode(array('errno'=>1)));
		}
	}
	//通过审核
	function passGoods(){
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$goods['status'] = 1;
		$res = Wlfightgroup::updateGoods($goods,$id);
		if($res){
			die(json_encode(array('errno'=>0)));
		}else {
			die(json_encode(array('errno'=>1)));
		}
	}
	//不通过审核
	function nopassGoods(){
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$goods['status'] = 6;
		$res = Wlfightgroup::updateGoods($goods,$id);
		if($res){
			die(json_encode(array('errno'=>0)));
		}else {
			die(json_encode(array('errno'=>1)));
		}
	}
	
	//不同规格不同价格页面
	function diffprice(){
		include wl_template('ptgoods/diffprice');
	}
	//不同规格相同价格页面
	function equalprice(){
		include wl_template('ptgoods/equalprice');
	}
	//获取仓库商品
	function selectGoods(){
		global $_W,$_GPC;
		$where =array('aid'=>$_W['aid'],'uniacid'=>$_W['uniacid']);
		if($_GPC['keyword']) $where['@name@'] = $_GPC['keyword'];
		$goodsData = Rush::getNumGoods("id,name,thumb", $where, 'id desc', 0, 0, 0);
		$ds = $goodsData[0];
		include wl_template('ptgoods/selectGoods');
	}
    /**
     * Comment: 拼团分类列表
     * Author: zzw
     * Date: 2019/12/20 14:38
     */
	function categorylist(){
        global $_W, $_GPC;
        #1、参数获取
        $page = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        $keyword = $_GPC['keyword'] ? : '';
        #2、条件生成
        $where = ['aid'=>$_W['aid'],'uniacid'=>$_W['uniacid']];
        if(!empty($keyword)) $where['name LIKE'] = '%' . $keyword . '%';
        #3、列表获取
        $list = pdo_getslice(PDO_NAME . 'fightgroup_category',$where,[$page, $pageIndex],$total,['id','name','listorder','logo'],'','listorder DESC');
        $pager = wl_pagination($total, $page, $pageIndex);
        #4、键值替换
        foreach($list as &$value){
            $value['sort'] = $value['listorder'];
            $value['thumb'] = $value['logo'];
            unset($value['listorder']);
            unset($value['logo']);
        }

        include wl_template('goodshouse/cate_list');
	}
	//复制商品
	function copygood(){
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$goods = Wlfightgroup::getSingleGood($id,'*');
		unset($goods['id']);
		$goods['stock'] = $goods['stock'] + $goods['realsalenum'];
		$goods['realsalenum'] = 0;
		$goods['status'] = 5;
		$goods['markid'] = 0;
		
		$res = Wlfightgroup::saveGoods($goods);
		if($res){
			die(json_encode(array('errno'=>0)));
		}else {
			die(json_encode(array('errno'=>1)));
		}
	}


	/**
	 * Comment: 获取拼团商品信息列表
	 * Author: zzw
	 * Date: 2019/7/11 18:16
	 */
	public function fightList(){
		global $_W , $_GPC;
		#1、条件生成
		$where = " a.aid = {$_W['aid']} AND a.uniacid = {$_W['uniacid']}";//默认条件
		!empty($_GPC['name']) && $where .= " AND a.name LIKE '%{$_GPC['name']}%' ";//商品名称
		$_GPC['status'] > -1 && $where .= " AND a.status = {$_GPC['status']} ";//商品名称
		!empty($_GPC['goods_id']) && $where .= " AND a.id = {$_GPC['goods_id']} ";//商品id
		!empty($_GPC['shop_name']) && $where .= " AND m.storename LIKE '%{$_GPC['shop_name']}%' ";//商户名称
		$_GPC['cate_id'] > -1 && $where .= " AND a.categoryid = {$_GPC['cate_id']} ";//商户名称
		!empty($_GPC['shop_id']) && $where .= " AND a.merchantid = {$_GPC['shop_id']} ";//商户id
		#2、排序操作
		$order = " a.listorder DESC ,a.id DESC ";
		#3、分页操作
		$page  = $_GPC['page'] ? $_GPC['page'] : 1;//当前页
		$index = $_GPC['index'] ? $_GPC['index'] : 10;//每页的数量
		$start = $page * $index - $index;//开始查询的点 = 当前页 * 每页的数量 - 每页的数量
		$limit = " LIMIT {$start},{$index}";
		#4、查询信息内容
        $field = 'a.id,a.logo,a.name,a.price,a.aloneprice,a.oldprice,a.status,a.pv,a.stock,a.listorder,b.name as cate_name,m.storename';
        $sql = "SELECT {$field} FROM " . tablename(PDO_NAME . 'fightgroup_goods')
            . " a LEFT JOIN " . tablename(PDO_NAME . "fightgroup_category")
            . " b ON a.categoryid = b.id LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
            . " m ON a.merchantid = m.id";
        !empty($where) && $sql .= " WHERE {$where} ";
        $sql .= ' GROUP BY a.id ';
        !empty($order) && $sql .= " ORDER BY {$order} ";
        $total = count(pdo_fetchall(str_replace($field,"a.id",$sql)));//获取符合条件的总数量
        $data['page_num'] = ceil($total / $index);//获取一共有多少页
        !empty($limit) && $sql .= $limit;
        $data['list'] = pdo_fetchall($sql);//获取要查询的列表数据
		#5、处理相关信息
		$orderModel = new Order();
		foreach ($data['list'] as $k => &$v) {
			//图片信息转换
			$v['logo'] = tomedia($v['logo']);
            //成团数
            $v['groupnum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename(PDO_NAME.'fightgroup_group')." WHERE goodsid = {$v['id']} AND status = 2");
            //获取销量信息
            $orderW = " fkid = {$v['id']} AND plugin = 'wlfightgroup' AND status IN ";
            $v['order_purchase']  = $orderModel->getPurchaseQuantity($orderW . " (0,1,2,3,4,6,8,9) ") ? : 0;//已下单
            $v['order_payment']  = $orderModel->getPurchaseQuantity($orderW . " (1,2,3,4,6,8,9) ") ? : 0;//已支付
            $v['order_used']  = $orderModel->getPurchaseQuantity($orderW . " (2, 3) ") ? : 0;//已使用
		}

		wl_json(1,'抢购商品列表',$data);
	}
	/**
	 * Comment: 获取砍价商品分类列表
	 * Author: zzw
	 * Date: 2019/7/11 17:53
	 */
	public function getClassList(){
		global $_W , $_GPC;
        $where = " uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
		$list = pdo_fetchall("SELECT id,name FROM " . tablename(PDO_NAME . 'fightgroup_category')." WHERE {$where} ORDER BY listorder DESC ");

		wl_json(1,'砍价分类列表',$list);
	}
    /**
     * Comment: 审核拼团商品是否通过审核
     * Author: zzw
     * Date: 2019/7/12 11:50
     */
	public function toExamine(){
        global $_W , $_GPC;
        $flag = $_GPC['flag'];
        $id   = intval($_GPC['id']);
        if ($flag) {
            $res = Wlfightgroup::updateGoods([ 'status' => 1 ] , $id);
            News::goodsToExamine($id,'fight');
        } else {
            $res = Wlfightgroup::updateGoods([ 'status' => 6 ] , $id);
            News::goodsToExamine($id,'fight','未通过');
        }
        if ($res) {
            show_json(1 , '活动审核成功');
        } else {
            show_json(0 , '活动审核失败，请重试');
        }
    }
    /**
     * Comment: 修改砍价商品的某个单项数据信息
     * Author: zzw
     * Date: 2019/7/15 11:15
     */
    public function updateInfo(){
        global $_W , $_GPC;
        #1、参数接收
        if(empty($_GPC['field']))  show_json(0,"缺少参数：修改的字段名称");
        #2、修改内容
        $data[$_GPC['field']] = $_GPC['value'];
        $res = pdo_update(PDO_NAME.'fightgroup_goods',$data,array('id' => $_GPC['id']));
        if($res){
            if (in_array($_GPC['field'] , [ 'price' , 'aloneprice' , 'oldprice' ])) {
                show_json(1,['message'=>"修改成功",'data'=>sprintf("%.2f",$_GPC['value'])]);
            }
            show_json(1,"修改成功");
        }else{
            show_json(0,"修改失败");
        }
    }

    /**
     * Comment: 批量修改商品信息
     * Author: wlf
     * Date: 2020/06/01 15:16
     */
    public function changestatus(){
        global $_W, $_GPC;
        $ids = $_GPC['ids'];
        $type = $_GPC['type'];
        foreach ($ids as$k=>$v){
            $rush = pdo_get('wlmerchant_fightgroup_goods',array('id' => $v),array('limitstarttime','limitendtime','merchantid','status'));
            if($type == 1){
                $status = 0;
                if(is_store()){
                    $examine = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$rush['merchantid']),'audits');
                    if(empty($examine)){
                        $status = 5;
                    }
                }
                if(empty($status)){
                    if ($rush['limitstarttime'] > time()) {
                        $status = 1;
                    }
                    else if ($rush['limitstarttime'] < time() && time() < $rush['limitendtime']) {
                        $status = 2;
                    }
                    else if ($rush['limitendtime'] < time()) {
                        $status = 3;
                    }
                }
                pdo_update('wlmerchant_fightgroup_goods', array('status' => $status), array('id' => $v));
            }else if($type == 8 && $rush['status'] == 8){
                Wlfightgroup::deteleGoods($v);
            }else{
                pdo_update('wlmerchant_fightgroup_goods', array('status' => $type), array('id' => $v));
            }
        }
        show_json(1, '操作成功');
    }


}		

	