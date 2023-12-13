<?php
defined('IN_IA') or exit('Access Denied');

class Realcard_WeliamController {
	
	public function cardlist() {
		global $_W, $_GPC;
		$wheresql = " WHERE uniacid = :uniacid ";
		$param = array(':uniacid' => $_W['uniacid']);
		$keyword = trim($_GPC['keyword']);
		if(!empty($keyword)) {
			$wheresql .= " AND (cardsn LIKE '%{$keyword}%' or remark LIKE '%{$keyword}%') ";
		}
		if($_GPC['startkeyword']){
            $startword = trim($_GPC['startkeyword']);
            $wheresql .= " AND cardsn >= '{$startword}'";
        }
        if($_GPC['endkeyword']){
            $endword = trim($_GPC['endkeyword']);
            $wheresql .= " AND cardsn <= '{$endword}'";
        }
		$starttime = empty($_GPC['time']['start']) ? TIMESTAMP -  86399 * 30 : strtotime($_GPC['time']['start']);
		$endtime = empty($_GPC['time']['end']) ? TIMESTAMP: strtotime($_GPC['time']['end']);
		if(!empty($_GPC['time']['start'])) {
			$wheresql .= " AND createtime >= '{$starttime}' AND createtime <= '{$endtime}'";
		}
		if (!empty($_GPC['status'])) {
			$wheresql .= " AND status = {$_GPC['status']}";
		}
		$pindex = max(1, intval($_GPC['page']));
		$psize = 15;
		$list = pdo_fetchall("SELECT * FROM ".tablename(PDO_NAME.'halfcard_realcard'). $wheresql . ' ORDER BY `id` DESC LIMIT '.($pindex - 1) * $psize.','. $psize, $param);
		if (!empty($list)) {
			foreach ($list as $index => &$qrcode) {
                $path = h5_url('pages/mainPages/realCard/realCard',['cardsn' => $qrcode['cardsn'],'salt' => $qrcode['salt']]);
				$qrcode['showurl'] = web_url('halfcard/realcard/qrcodeimg', array('url' => $path));
				if($qrcode['levelid']){
					$qrcode['levelname'] = pdo_getcolumn(PDO_NAME.'halflevel',array('id'=>$qrcode['levelid']),'name');
				}else {
					$qrcode['levelname'] = $_W['wlsetting']['halflevel']['name'];
				}
				if($qrcode['aid'] > 0){
                    $qrcode['agentname'] = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$qrcode['aid']),'agentname');
                }else{
                    $qrcode['agentname'] = '总后台';
                }
			}
		}
		$total = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME.'halfcard_realcard') . $wheresql, $param);
		$pager = wl_pagination($total, $pindex, $psize);
		
		if($_GPC['export'] != ''){
			$this->export($wheresql, $param);
		}
		include wl_template('realcard/qr-list');
	}

	public function addcard() {
		global $_W, $_GPC;
		$levels = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_halflevel')."WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC"); 
		$agentlist = pdo_fetchall("SELECT agentname,id FROM ".tablename('wlmerchant_agentusers')."WHERE uniacid = {$_W['uniacid']} AND status = 1");
        if(checksubmit('submit')){
			$days = intval($_GPC['days']);
			$allnum = intval($_GPC['qr_num']);
			$levelid = intval($_GPC['levelid']);
            $aid = intval($_GPC['aid']);
            include wl_template('realcard/qr-process');
			exit;
		}
		include wl_template('realcard/qr-post');
	}

    public function deletemodal(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $realcard = pdo_get('wlmerchant_halfcard_realcard',array('id' => $id));

        if($_W['ispost']){
            if($id){
                if($_GPC['range']){
                    $res = pdo_delete('wlmerchant_halfcard_realcard',array('remark'=>$realcard['remark']));
                }else {
                    $res = pdo_delete('wlmerchant_halfcard_realcard',array('id'=>$id));
                }
            }
            if($res){
                show_json(1,'删除成功');
            }else {
                show_json(0,'删除失败,请重试');
            }
        }
        include wl_template('realcard/deletemodel');
    }

    public function icerealcard(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $realcard = pdo_get('wlmerchant_halfcard_realcard',array('id' => $id));
        if($_W['ispost']){
            if($id){
                $data = array(
                    'icestatus'=> trim($_GPC['icestatus']),
                );
                if($_GPC['range']){
                    $res = pdo_update('wlmerchant_halfcard_realcard',$data,array('remark' => $realcard['remark']));
                }else {
                    $res = pdo_update('wlmerchant_halfcard_realcard',$data,array('id' => $id));
                }
            }
            if($res){
                show_json(1,'操作成功');
            }else {
                show_json(0,'操作失败,请重试');
            }
        }
        include wl_template('realcard/icemodel');
    }

	//删除实卡
    public function deletecard(){
        global $_W, $_GPC;
        $ids = $_GPC['ids'];
        if ($ids) {
            foreach ($ids as $key => $id) {
                pdo_delete('wlmerchant_halfcard_realcard',array('id'=>$id));
            }
            die(json_encode(array('errno' => 0, 'message' => '', 'id' => $ids)));
        }
    }

	public function editrealcard() {
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$realcard = pdo_get('wlmerchant_halfcard_realcard',array('id' => $id));
		$levels = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_halflevel')."WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
		if($_W['ispost']){
			if($id){
				$data = array(
					'days'   => trim($_GPC['days']),
					'levelid'   => trim($_GPC['levelid']),
					'remark'=> trim($_GPC['remark'])
				);
				if($_GPC['range']){
					$res = pdo_update('wlmerchant_halfcard_realcard',$data,array('remark' => $realcard['remark']));
				}else {
					$res = pdo_update('wlmerchant_halfcard_realcard',$data,array('id' => $id));
				}
			}
			if($res){
				show_json(1,'操作成功');
			}else {
				show_json(0,'操作失败,请重试');
			}
		}
		include wl_template('realcard/qr-model');
	}
	
	public function get(){
		global $_W,$_GPC;
        $settings = Setting::wlsetting_read('halfcard');
        $numbers = pdo_getcolumn(PDO_NAME.'halfcard_realcard', array('uniacid' => $_W['uniacid']), array('COUNT(id)'));
		$firstnum = 80000000;
		$qrinsert = array(
			'uniacid' => $_W['uniacid'],
			'days' => intval($_GPC['days']),
			'cardsn' => empty($numbers) ? $firstnum + 1 : $firstnum + 1 + $numbers,
			'salt' => random(8),
			'createtime' => TIMESTAMP,
			'status' => '1',
			'levelid'=> $_GPC['levelid'],
			'aid'    => $_GPC['aid'],
			'remark' => trim($_GPC['remark'])
		);
        $result = Util::long2short(h5_url('pages/mainPages/realCard/realCard', array('cardsn' => $qrinsert['cardsn'], 'salt' => $qrinsert['salt'])));
        if (!is_error($result) && $result['short_url'] != 'h') {
            $qrinsert['url'] = $result['short_url'];
        }
		pdo_insert(PDO_NAME.'halfcard_realcard', $qrinsert);
		die(json_encode(array('result' => 1)));
	}
	
	public function export($wheresql, $param) {
		global $_W,$_GPC;
		if (empty($wheresql) || empty($param)) {
			return FALSE;
		}
        $settings = Setting::wlsetting_read('halfcard');
        $list = pdo_fetchall("SELECT * FROM ".tablename(PDO_NAME.'halfcard_realcard'). $wheresql . ' ORDER BY `id` DESC', $param);
		/* 输出表头 */
		$filter = array(
			'showurl' => '二维码',
			'cardsn' => '实卡编号',
			'days' => '包含时长',
			'status' => '使用状态',
			'createtime' => '生成时间',
			'bindtime' => '绑定时间',
		);
		$data = array();
		foreach ($list as $k => $v) {
			$v['showurl'] = $v['url'] ? $v['url'] : h5_url('pages/mainPages/realCard/realCard',array('cardsn' => $v['cardsn'],'salt' => $v['salt'])) ;
			foreach ($filter as $key => $title) {
				if ($key == 'createtime') {
					$data[$k][$key] = date('Y-m-d H:i:s', $v[$key]);
				}elseif($key == 'status'){
					switch ($v[$key]) {
						case '1':
							$data[$k][$key] = '未绑定';
							break;
						case '2':
							$data[$k][$key] = '已绑定';
							break;
						default:
							$data[$k][$key] = '已失效';
							break;
					}
				}elseif($key == 'days'){
					$data[$k][$key] = $v[$key] . '天';
				}elseif($key == 'bindtime'){
					$data[$k][$key] = !empty($v[$key]) ? date('Y-m-d H:i:s', $v[$key]) : '未绑定';
				}else {
					$data[$k][$key] = $v[$key];
				}
			}
		}
		util_csv::export_csv_2($data, $filter, '全部数据.csv');
		exit;
	}
	
	public function qrcodeimg() {
		global $_W, $_GPC;
		$url = $_GPC['url'];
		m('qrcode/QRcode') -> png($url, false, QR_ECLEVEL_H, 5);
	}

	public function longToshort(){
        global $_W, $_GPC;
        $list = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_halfcard_realcard')."WHERE uniacid = {$_W['uniacid']} AND url = '' ORDER BY id DESC");
        foreach ($list as $li){
            $result = Util::long2short(h5_url('pages/mainPages/realCard/realCard',['cardsn' => $li['cardsn'],'salt' => $li['salt']]));
            if (!is_error($result) && $result['short_url'] != 'h') {
                pdo_update('wlmerchant_halfcard_realcard',array('url' => $result['short_url']),array('id' => $li['id']));
            }
        }
        show_json(1);
    }

}