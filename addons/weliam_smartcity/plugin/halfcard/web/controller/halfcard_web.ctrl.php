<?php
defined('IN_IA') or exit('Access Denied');

class Halfcard_web_WeliamController {

	function halfcardList() {
		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$data = array();
		$data['uniacid'] = $_W['uniacid'];
		$data['aid'] = $_W['aid'];
        $data['type'] = 0;
		if($_GPC['keyword']){
			if ($_GPC['keywordtype'] == 1) {
				$flag1 = intval($_GPC['keyword']);
				$data['datestatus'] = 1;
				$data['week@'] = '"' . $flag1 . '"';
			}
			if ($_GPC['keywordtype'] == 2) {
				$keyword = trim($_GPC['keyword']);
				$data['title@'] = $keyword;
			}
			if ($_GPC['keywordtype'] == 3) {
				$keyword = intval($_GPC['keyword']);
				$data['datestatus'] = 2;
				$data['day@'] = '"' . $keyword . '"';
			}
		}
		if ($_GPC['daily']) {
			$daily = intval($_GPC['daily']);
			if ($daily == 1) {
				$data['daily'] = 1;
			} else {
				$data['daily'] = 0;
			}
		}
		
		if($_GPC['status']){
			if($_GPC['status'] == 4){
				$data['status'] = 0;
			}else{
				$data['status'] = $_GPC['status'];
			}
		}
		
		$halfcard = Halfcard::getNumActive('*', $data, 'ID DESC', $pindex, $psize, 1);
		$pager = $halfcard[1];
		$halfcard = $halfcard[0];
		foreach ($halfcard as $key => &$value) {
			$detail = pdo_fetch("select * from " . tablename('wlmerchant_merchantdata') . " where uniacid={$_W['uniacid']} and id={$value['merchantid']}");
			if ($value['datestatus'] == 1) {
				$value['week'] = unserialize($value['week']);
			} else {
				$value['day'] = unserialize($value['day']);
			}
			$halfcard[$key]['logo'] = $detail['logo'];
			$halfcard[$key]['storename'] = $detail['storename'];
			if($value['levelstatus'] > 0){
                $le_ac_array = unserialize($value['activearray']);
                $value['activediscount'] = min($le_ac_array).'~'.max($le_ac_array);
                $le_day_array = unserialize($value['dayactarray']);
                $value['discount'] = min($le_day_array).'~'.max($le_day_array);
            }
		}
		include  wl_template('halfcard/halfcard_list');
	}

	//修改状态
	function delete(){
		global $_W,$_GPC;
		$id = $_GPC['id'];
		$status = $_GPC['status'];
		if($status == 1){
			$res = pdo_update('wlmerchant_halfcardlist',array('status'=>0),array('id' => $id));
		}else{
			$res = pdo_update('wlmerchant_halfcardlist',array('status'=>1),array('id' => $id));
		}
		if($res){
			die(json_encode(array('errno'=>0)));
		}else {
			die(json_encode(array('errno'=>1)));
		}
	}

	function packagelist(){
		global $_W, $_GPC;
		$sel1 = array(array('id' => 1, 'name' => '礼包标题'));
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$data['uniacid'] = $_W['uniacid'];
		$data['aid'] = $_W['aid'];
        $data['type'] = 0;
		if ($_GPC['keyword']){
			$keyword = trim($_GPC['keyword']);
			$data['title@'] = $keyword;
		}
		
		if($_GPC['status']){
			if($_GPC['status'] == 4){
				$data['status'] = 0;
			}else{
				$data['status'] = $_GPC['status'];
			}
		}
		if(is_store()){
            $data['merchantid'] = $_W['storeid'];
        }
		
		$packagelist = Halfcard::getNumPackActive('*',$data,'sort DESC,id DESC', $pindex, $psize, 1);
		$pager = $packagelist[1];
		$packagelist = $packagelist[0];
		foreach($packagelist as $key => &$package){
			$merchant = pdo_get('wlmerchant_merchantdata',array('id' => $package['merchantid']),array('storename','logo'));
			$package['storename'] = $merchant['storename'];
			$package['logo'] = $merchant['logo'];
			$package['datestarttime'] = date('Y-m-d H:i',$package['datestarttime']);
			$package['dateendtime'] = date('Y-m-d H:i',$package['dateendtime']);
			$package['givenum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_timecardrecord')." WHERE type = 2 AND activeid = {$package['id']}");
		}
//		wl_debug($packagelist);
		include  wl_template('halfcard/packagelist');
	}

	function createpackage(){
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$levels = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_halflevel')."WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC"); 
		if($id){
			$package = pdo_get('wlmerchant_package',array('id' => $id));
			$storen = pdo_get('wlmerchant_merchantdata', array('id' => $package['merchantid']));
			$package['storename'] = $storen['storename'];
			$package['logo'] = $storen['logo'];
			$starttime = $package['starttime'];
			$endtime = $package['endtime'];
			$datestarttime = $package['datestarttime'];
			$dateendtime = $package['dateendtime'];
			$package['level'] = unserialize($package['level']);

            if ($package['usedatestatus'] == 1) {
                $package['week'] = unserialize($package['week']);
            }
            if ($package['usedatestatus'] == 2) {
                $package['day'] = unserialize($package['day']);
            }

		}
		if(empty($starttime) || empty($endtime)){
			$starttime = time();
			$endtime = strtotime('+1 month');
		}

		if(empty($datestarttime) || empty($dateendtime)){
			$datestarttime = time();
			$dateendtime = strtotime('+1 month');
		}



		
		if (checksubmit('submit')) {

			$package = $_GPC['package'];
			$time = $_GPC['time'];
			if(empty($package['merchantid']) && !is_store() ) wl_message('请选择商户');
			if(empty($package['title'])) wl_message('请填写活动名称');
			if(empty($package['price'])) wl_message('请填写礼包价值');
			if(empty($package['usetimes'])) wl_message('请填写使用次数');
			if(empty($package['describe'])) wl_message('请填写使用说明 ');

			//973扩展
            if($package['usedatestatus']=='1' && empty($package['week'])){
                wl_message('请选择每周提供礼包的时间');
            }
            if($package['usedatestatus']=='2' && empty($package['day'])){
                wl_message('请选择每月提供礼包的时间');
            }
            if ($package['usedatestatus'] == 1) {
                $package['week'] = serialize($package['week']);
                $package['day'] = '';
            }
            if ($package['usedatestatus'] == 2) {
                $package['day'] = serialize($package['day']);
                $package['week'] = '';
            }

			$datetime = $_GPC['datetime'];
			$package['starttime'] = strtotime($time['start']);
			$package['endtime'] = strtotime($time['end']);
			$package['datestarttime'] = strtotime($datetime['start']);
			$package['dateendtime'] = strtotime($datetime['end']);
			$package['datestatus'] = $_GPC['datestatus'];
			$package['timestatus'] = $_GPC['timestatus'];
			$package['packtimestatus'] = $_GPC['packtimestatus'];
			$package['resetswitch'] = $_GPC['resetswitch'];
			if ($package['status'] == 'on') {
                if(is_store()){
                    $audits = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $_W['storeid']), 'audits');
                    if (empty($audits)) {
                        $package['status'] = 2;
                    }else{
                        $package['status'] = 1;
                    }
                }else{
                    $package['status'] = 1;
                }
			} else {
				$package['status'] = 0;
			}
			$package['describe'] = htmlspecialchars_decode($package['describe']);
			$package['level'] = serialize($package['level']);
			if(empty($id)){
				$package['uniacid'] = $_W['uniacid'];
				$package['aid'] = $_W['aid'];
				$package['createtime'] = time();
				if(is_store()){
                    $package['merchantid'] = $_W['storeid'];
                }
				$res = pdo_insert(PDO_NAME.'package',$package);
			}else {
				$res = pdo_update(PDO_NAME.'package',$package,array('id' => $id));
			}
			if ($res){
                if($package['status'] == 2){ //发送审核消息
                    $storename = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $_W['storeid']), 'storename');
                    $first = '您好，您有一个待审核任务需要处理';
                    $type = '审核商品';
                    $content = '礼包名称:' . $package['title'];
                    $status = '待审核';
                    $remark = '商户[' . $storename . ']上传了特权大礼包待审核,请管理员尽快前往后台审核';
                    News::noticeAgent('storegood',$_W['aid'],$first,$type,$content,$status,$remark,time(),'');
                }
				wl_message('操作成功',web_url('halfcard/halfcard_web/packagelist'),'success');
			} else {
				wl_message('操作失败', referer(), 'error');
			}
		}	
		include  wl_template('halfcard/createpackage');
	}

	function createHalfcard() {
		global $_W, $_GPC;
		$levels = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_halflevel')."WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC"); 
		if (checksubmit('submit')) {
			$halfcard = $_GPC['halfcard'];
			$halfcard['datestatus'] = $_GPC['datestatus'];
			if(empty($halfcard['merchantid'])) wl_message('请选择商户');
			if($halfcard['datestatus']=='1')
			{
				if(empty($halfcard['week'])) wl_message('请选择每周特权活动时间');
			}
			else if($halfcard['datestatus']=='2')
			{
				if(empty($halfcard['day'])) wl_message('请选择每月特权活动时间');
			}
			if(empty($halfcard['limit'])) wl_message('请填写使用限制，无限制请填无');
			if(empty($halfcard['describe'])) wl_message('请填写使用说明');
			if($halfcard['daily']=='1')
			{
				if(empty($halfcard['discount'])) wl_message('请填写规范的平日折扣额度');
			}
			$halfcard['createtime'] = time();
			if ($halfcard['status'] == 'on') {
			    if(is_store()){
                    $audits = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $_W['storeid']), 'audits');
                    if (empty($audits)) {
                        $halfcard['status'] = 2;
                    }else{
                        $halfcard['status'] = 1;
                    }
                }else{
                    $halfcard['status'] = 1;
                }
			} else {
				$halfcard['status'] = 0;
			}
			if ($halfcard['daily'] == 'on') {
				$halfcard['daily'] = 1;
			} else {
				$halfcard['daily'] = 0;
			}
			if ($halfcard['datestatus'] == 1) {
				$halfcard['week'] = serialize($halfcard['week']);
				$halfcard['day'] = '';
			}
			if ($halfcard['datestatus'] == 2) {
				$halfcard['day'] = serialize($halfcard['day']);
				$halfcard['week'] = '';
			}
			if($halfcard['datestatus'] == 3){
				$halfcard['day'] = '';
				$halfcard['week'] = '';
				$halfcard['activediscount'] = 10;
			}
			if($halfcard['datestatus'] == 3 && empty($halfcard['daily'])){
				$halfcard['status'] = 0;
			}
			$halfcard['level'] = serialize($halfcard['level']);
			$halfcard['discount'] = round($halfcard['discount'], 1);
			$halfcard['describe'] = htmlspecialchars_decode($halfcard['describe']);
			$res = Halfcard::saveHalfcard($halfcard);
			if ($res) {
			    if($halfcard['status'] == 2){ //发送审核消息
                    $storename = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $_W['storeid']), 'storename');
                    $first = '您好，您有一个待审核任务需要处理';
                    $type = '审核商品';
                    $content = '商户特权:' . $halfcard['title'];
                    $status = '待审核';
                    $remark = '商户[' . $storename . ']上传了特权折扣待审核,请管理员尽快前往后台审核';
                    News::noticeAgent('storegood',$_W['aid'],$first,$type,$content,$status,$remark,time(),'');
                }
				wl_message('创建特权优惠成功', web_url('halfcard/halfcard_web/halfcardList'), 'success');
			} else {
				wl_message('创建特权优惠失败', referer(), 'success');
			}
		}
		include  wl_template('halfcard/create_halfcard');
	}
	//禁用用户
	function disablemember(){
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$flag = $_GPC['flag'];
		if($flag == 1){
			$res = pdo_update('wlmerchant_halfcardmember',array('disable' => 1),array('id' => $id));
			if ($res) {
				die(json_encode(array('errno' => 0, 'message' => $res, 'id' => $id)));
			} else {
				die(json_encode(array('errno' => 2, 'message' => $res, 'id' => $id)));
			}
		}
		if($flag == 2){
			$res = pdo_update('wlmerchant_halfcardmember',array('disable' => 0),array('id' => $id));
			if ($res) {
				die(json_encode(array('errno' => 0, 'message' => $res, 'id' => $id)));
			} else {
				die(json_encode(array('errno' => 2, 'message' => $res, 'id' => $id)));
			}
		}
		
	}
	//删除用户
	function deletemember(){
		global $_W, $_GPC;
		$id = $_GPC['id'];
		if(is_array($id)){
		    foreach($id as $iii){
                $res = pdo_delete('wlmerchant_halfcardmember',array('id' => $iii));
            }
            $res = 1;
        }else{
            $res = pdo_delete('wlmerchant_halfcardmember',array('id' => $id));
        }
		if ($res) {
			die(json_encode(array('errno' => 0, 'message' => $res, 'id' => $id)));
		} else {
			die(json_encode(array('errno' => 2, 'message' => $res, 'id' => $id)));
		}
	}
	
	
	//删除
	function deleteHalfcard() {
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$ids = $_GPC['ids'];
		if ($id) {
			$res = Halfcard::deleteHalfcard(array('id' => $id));
			if ($res) {
				die(json_encode(array('errno' => 0, 'message' => $res, 'id' => $id)));
			} else {
				die(json_encode(array('errno' => 2, 'message' => $res, 'id' => $id)));
			}
		}
		if ($ids) {
			foreach ($ids as $key => $id) {
				Halfcard::deleteHalfcard(array('id' => $id));
			}
			die(json_encode(array('errno' => 0, 'message' => '', 'id' => '')));
		}
	}

	function passHalfcard() {
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$flag = $_GPC['flag'];
		$type = $_GPC['type'];
		if($type == 1){
			$table = 'wlmerchant_halfcardlist';
		}else if($type == 2){
			$table = 'wlmerchant_package';
		}
		if ($id) {
			if($flag){
				$res = pdo_update($table,array('status' => 1),array('id' => $id));
			}else {
				$res = pdo_update($table,array('status' => 3),array('id' => $id));
			}
			if ($res) {
				die(json_encode(array('errno' => 0, 'message' => $res, 'id' => $id)));
			} else {
				die(json_encode(array('errno' => 2, 'message' => $res, 'id' => $id)));
			}
		}
	}
	//删除
	function deletejihuoqr() {
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$ids = $_GPC['ids'];
		if ($id) {
			$res = pdo_delete('wlmerchant_token',array('id'=>$id));
			if ($res) {
				die(json_encode(array('errno' => 0, 'message' => $res, 'id' => $id)));
			} else {
				die(json_encode(array('errno' => 2, 'message' => $res, 'id' => $id)));
			}
		}
		if ($ids) {
			foreach ($ids as $key => $id) {
				pdo_delete('wlmerchant_token',array('id'=>$id));
			}
			die(json_encode(array('errno' => 0, 'message' => '', 'id' => '')));
		}
	}

	//删除用户记录
	function deleteHalfcardRecord() {
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$ids = $_GPC['ids'];
		if ($id) {
			$res = Halfcard::deleteHalfcardRecord(array('id' => $id));
			if ($res) {
				die(json_encode(array('errno' => 0, 'message' => $res, 'id' => $id)));
			} else {
				die(json_encode(array('errno' => 2, 'message' => $res, 'id' => $id)));
			}
		}
		if ($ids) {
			foreach ($ids as $key => $id) {
				Halfcard::deleteHalfcardRecord(array('id' => $id));
			}
			die(json_encode(array('errno' => 0, 'message' => '', 'id' => $ids)));
		}
	}

	function inspect() {
		global $_W, $_GPC;
		$id = $_GPC['id'];
		$type = $_GPC['type'];
		if($type == 1){
			$res = pdo_get('wlmerchant_halfcardlist', array('merchantid' => $id), array('id'));
		}
		if ($res) {
			die(json_encode(array('errno' => 1, 'message' => $id)));
		} else {
			die(json_encode(array('errno' => 0, 'message' => $id)));
		}
	}

	//编辑
	function editHalfcard() {
		global $_W, $_GPC;
		if(is_store()){
            $storeid = $_W['storeid'];
            $id = pdo_getcolumn(PDO_NAME.'halfcardlist',array('merchantid'=>$storeid),'id');
        }else{
            $id = $_GPC['id'];
        }
		$levels = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_halflevel')."WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
		$halfcard = Halfcard::getSingleHalfcard($id, '*');
		$storename = pdo_get('wlmerchant_merchantdata', array('id' => $halfcard['merchantid']));
		$halfcard['storename'] = $storename['storename'];
		$halfcard['logo'] = $storename['logo'];
		$halfcard['level'] = unserialize($halfcard['level']);
        $le_ac_array = unserialize($halfcard['activearray']);
        $le_day_array = unserialize($halfcard['dayactarray']);
        if ($halfcard['datestatus'] == 1) {
			$halfcard['week'] = unserialize($halfcard['week']);
		}
		if ($halfcard['datestatus'] == 2) {
			$halfcard['day'] = unserialize($halfcard['day']);
		}
		
		if (checksubmit('submit')) {
			$halfcard = $_GPC['halfcard'];
			if ($halfcard['status'] == 'on') {
                if(is_store()){
                    $audits = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $_W['storeid']), 'audits');
                    if (empty($audits)) {
                        $halfcard['status'] = 2;
                    }else{
                        $halfcard['status'] = 1;
                    }
                }else{
                    $halfcard['status'] = 1;
                }
			} else {
				$halfcard['status'] = 0;
			}
			if ($halfcard['daily'] == 'on') {
				$halfcard['daily'] = 1;
			} else {
				$halfcard['daily'] = 0;
			}
			$halfcard['datestatus'] = $_GPC['datestatus'];
			if ($halfcard['datestatus'] == 1) {
				$halfcard['week'] = serialize($halfcard['week']);
				$halfcard['day'] = '';
			}
			if ($halfcard['datestatus'] == 2) {
				$halfcard['day'] = serialize($halfcard['day']);
				$halfcard['week'] = '';
			}
			if($halfcard['datestatus'] == 3){
				$halfcard['day'] = '';
				$halfcard['week'] = '';
				$halfcard['activediscount'] = 10;
			}
			if($halfcard['datestatus'] == 3 && empty($halfcard['daily'])){
				$halfcard['status'] = 0;
			}
			$halfcard['level'] = serialize($halfcard['level']);
			$halfcard['discount'] = round($halfcard['discount'], 1);
			$halfcard['describe'] = htmlspecialchars_decode($halfcard['describe']);
			//分级折扣
            if($halfcard['levelstatus'] > 0){
                $le_ac_array = [];
                $le_ac_id = $_GPC['le_ac_id'];
                $le_ac_pr = $_GPC['le_ac_pr'];
                foreach($le_ac_id as $key => $vle){
                    $vipac = $le_ac_pr[$key] > 0 ? sprintf("%.1f",$le_ac_pr[$key]) : 10;
                    $le_ac_array[$vle] = $vipac;
                }
                $halfcard['activearray'] = serialize($le_ac_array);

                $le_day_array = [];
                $le_day_id = $_GPC['le_day_id'];
                $le_day_pr = $_GPC['le_day_pr'];
                foreach($le_day_id as $ke => $dle){
                    $vipday = $le_day_pr[$ke] > 0 ? sprintf("%.1f",$le_day_pr[$ke]) : 10;
                    $le_day_array[$dle] = $vipday;
                }
                $halfcard['dayactarray'] = serialize($le_day_array);
            }


			if($id){
                $res = Halfcard::updateHalfcard($halfcard,array('id' => $id));
            }else{
                $halfcard['merchantid'] = $storeid;
                $res = Halfcard::saveHalfcard($halfcard);
            }
			if ($res) {
			    if(is_store()){
                    if($halfcard['status'] == 2){ //发送审核消息
                        $storename = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $_W['storeid']), 'storename');
                        $first = '您好，您有一个待审核任务需要处理';
                        $type = '审核商品';
                        $content = '商户特权:' . $halfcard['title'];
                        $status = '待审核';
                        $remark = '商户[' . $storename . ']上传了特权折扣待审核,请管理员尽快前往后台审核';
                        News::noticeAgent('storegood',$_W['aid'],$first,$type,$content,$status,$remark,time(),'');
                    }
                    wl_message('更新一卡通成功', web_url('halfcard/halfcard_web/editHalfcard'), 'success');
                }else{
                    wl_message('更新一卡通成功', web_url('halfcard/halfcard_web/halfcardList'), 'success');
                }
			} else {
				wl_message('更新一卡通失败', referer(), 'error');
			}
		}
		include  wl_template('halfcard/create_halfcard');
	}

	function userhalfcardlist() {
		global $_W, $_GPC;
		$keyword = trim($_GPC['keyword']);
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$data = array();
		$data['uniacid'] = $_W['uniacid'];
        if(!empty($_GPC['reid'])){
            $data['id'] = $_GPC['reid'];
        }
		$data['aid'] = $_W['aid'];
		$type = $_GPC['type']?$_GPC['type']:1;
		$data['type'] = $type;
		if(is_store()){
            $data['merchantid'] = $_W['storeid'];
        }
		if($type == 2){
			if($_GPC['alflag']){
				$data['usetime >'] = 0;
			}
		}
		if ($_GPC['keywordtype'] == 1) {
			$where = " AND title LIKE :title";
			$params[':title'] = "%{$keyword}%";
			if($type == 2){
				$halfcard = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_package') . " where uniacid= {$_W['uniacid']} and aid={$_W['aid']} $where", $params);
			}else {
				$halfcard = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_halfcardlist') . " where uniacid= {$_W['uniacid']} and aid={$_W['aid']} $where", $params);
			}
			$data['activeid'] = $halfcard['id'];
		}
		if ($_GPC['keywordtype'] == 2) {
			$where = " AND storename LIKE :storename";
			$params[':storename'] = "%{$keyword}%";
			$halfcard = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_merchantdata') . " where uniacid= {$_W['uniacid']} and aid={$_W['aid']} $where", $params);
			$data['merchantid'] = $halfcard['id'];
		}
		if ($_GPC['keywordtype'] == 3) {
			$where = " AND username LIKE :username";
			$params[':username'] = "%{$keyword}%";
			$halfcard = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_halfcardmember') . " where uniacid= {$_W['uniacid']} $where", $params);
			$data['cardid'] = $halfcard['id'];
		}
		if ($_GPC['keywordtype'] == 4) {
            $where = " AND mobile LIKE :mobile";
            $params[':mobile'] = "%{$keyword}%";
            $halfcard = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . " where uniacid= {$_W['uniacid']} $where", $params);
            if ($halfcard) {
                $mids = "(";
                foreach ($halfcard as $key => $mer) {
                    if ($key == 0) {
                        $mids .= $mer['id'];
                    } else {
                        $mids .= "," . $mer['id'];
                    }
                }
                $mids .= ")";
                $data['mid#'] = $mids;
            } else {
                $data['mid#'] = "(0)";
            }
		}
		if ($_GPC['keywordtype'] == 5) {
			$params[':nickname'] = "%{$keyword}%";
			$member = pdo_fetchall("SELECT mid,storeid FROM " . tablename('wlmerchant_merchantuser') . "WHERE uniacid = {$_W['uniacid']} AND name LIKE :nickname",$params);
			if ($member) {
				$mids = "(";
				$storeids = "(";
				foreach ($member as $key => $mer) {
					if ($key == 0) {
						$mids .= $mer['mid'];
						$storeids .= $mer['storeid'];
					} else {
						$mids .= "," . $mer['mid'];
						$storeids .= "," . $mer['storeid'];
					}
				}
				$mids .= ")";
				$storeids .= ")";
				$data['verfmid#'] = $mids;
				$data['merchantid#'] = $storeids;
			} else {
				$data['verfmid#'] = "(0)";
				$data['merchantid#'] = "(0)";
			}
		}
		
		
		if (!empty($_GPC['time_limit'])) {
			$starttime = strtotime($_GPC['time_limit']['start']);
			$endtime = strtotime($_GPC['time_limit']['end']) ;
			$data['createtime>'] = $starttime;
			$data['createtime<'] = $endtime;
		}
		if (empty($starttime) || empty($endtime)) {
			$starttime = strtotime('-1 month');
			$endtime = time();
		}
		
		
		if (!empty($_GPC['id'])) {
			$data['activeid'] = $_GPC['id'];
		}
		
		if($_GPC['export']){
			$this->export($data);
		}
		$halfcard = Halfcard::getNumActive2('*', $data, 'ID DESC', $pindex, $psize, 1);
		$pager = $halfcard[1];
		$halfcard = $halfcard[0];
		foreach ($halfcard as $key => &$v) {
			if($type !=2 ){
				$active = pdo_get('wlmerchant_halfcardlist', array('id' => $v['activeid']));
			}else{
				$active = pdo_get('wlmerchant_package', array('id' => $v['activeid']));
			}
			$v['title'] = $active['title'];
			$merchant = pdo_get('wlmerchant_merchantdata', array('id' => $v['merchantid']));
			$v['storename'] = $merchant['storename'];
			$v['logo'] = $merchant['logo'];
			$member = pdo_get('wlmerchant_member', array('id' => $v['mid'], 'uniacid' => $_W['uniacid']));
			$v['avatar'] = $member['avatar'];
			$v['mobile'] = $member['mobile'];
			$v['username'] = pdo_getcolumn(PDO_NAME.'halfcardmember',array('id'=>$v['cardid']),'username');
			if(empty($v['username'])){
				$v['username'] = $member['nickname'];
			}
			$v['vername'] = pdo_getcolumn(PDO_NAME.'merchantuser',array('mid'=>$v['verfmid'],'storeid'=>$v['merchantid']),'name');
			if(empty($v['vername'])){
				$v['vername'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$v['verfmid']),'nickname');
			}
		}
		
		include  wl_template('halfcard/userHalfcardList');
	}

	function export($where){
		global $_W, $_GPC;
		set_time_limit(0);
		$halfcard = Halfcard::getNumActive2('*',$where,'ID DESC',0,0,1);
		$halfcard = $halfcard[0];
		foreach ($halfcard as $key => &$v) {
			if($where['type'] !=2 ){
				$active = pdo_get('wlmerchant_halfcardlist', array('id' => $v['activeid']));
			}else{
				$active = pdo_get('wlmerchant_package', array('id' => $v['activeid']));
			}
			
			$v['title'] = $active['title'];
			$merchant = pdo_get('wlmerchant_merchantdata', array('id' => $v['merchantid']),array('storename'));
			$v['storename'] = $merchant['storename'];
			$member = pdo_get('wlmerchant_member', array('id' => $v['mid'], 'uniacid' => $_W['uniacid']),array('mobile','nickname'));
			$v['mobile'] = $member['mobile'];
			$v['username'] = pdo_getcolumn(PDO_NAME.'halfcardmember',array('id'=>$v['cardid']),'username');
			if(empty($v['username'])){
				$v['username'] = $member['nickname'];
			}
			$v['varname'] = pdo_getcolumn(PDO_NAME.'merchantuser',array('mid'=>$v['verfmid'],'storeid'=>$v['merchantid']),'name');
			$v['creatcardtime'] = pdo_getcolumn(PDO_NAME.'halfcardmember',array('id'=>$v['cardid']),'createtime');
		}
		if($where['type'] == 1){
			$filter = array(
				'id' => '记录id',
				'title' => '特权名称',
				'storename' => '所属商家',
				'username' => '买家昵称',
				'mobile' => '买家电话',
				'ordermoney' => '订单金额',
				'realmoney' => '支付金额',
				'varname' => '核销人',
				'usetime' => '使用时间',
				'creatcardtime' => '开卡时间'
			);
			
			$data = array();
			for ($i=0; $i < count($halfcard) ; $i++) {
				foreach ($filter as $key => $title) {
					if($key == 'usetime' || $key == 'creatcardtime') {
						$data[$i][$key] = date('Y-m-d H:i:s',$halfcard[$i][$key]);
					}else {
						$data[$i][$key] = $halfcard[$i][$key];
					}
				}
			}
			util_csv::export_csv_2($data,$filter, '一卡通特权使用记录.csv');
			exit;
		}else {
			$filter = array(
				'id' => '记录id',
				'title' => '特权名称',
				'storename' => '所属商家',
				'username' => '买家昵称',
				'mobile' => '买家电话',
				'ordermoney' => '礼包价值',
				'varname' => '核销人',
				'usetime' => '使用时间',
				'creatcardtime' => '开卡时间'
			);
			
			$data = array();
			for ($i=0; $i < count($halfcard) ; $i++) {
				foreach ($filter as $key => $title) {
					if($key == 'usetime' || $key == 'creatcardtime') {
						$data[$i][$key] = date('Y-m-d H:i:s',$halfcard[$i][$key]);
					}else{
						$data[$i][$key] = $halfcard[$i][$key];
					}
				}
			}
			util_csv::export_csv_2($data,$filter, '一卡通大礼包使用记录.csv');
			exit;
		}
		
	}

	function qrcodeimg() {
		global $_W, $_GPC;
		$url = $_GPC['url'];
		m('qrcode/QRcode') -> png($url, false, QR_ECLEVEL_H, 4);
	}

	function base() {
		global $_W, $_GPC;
		$allow = 0;
		$_GPC['postType'] = $_GPC['postType'] ? $_GPC['postType'] : 'setting';
		$base = Setting::agentsetting_read('halfcard');
		$base['qa'] = unserialize($base['qanda']);
		$base['hcprice'] = unserialize($base['hcprice']);
		$settings = Setting::wlsetting_read('halfcard');
		if ($settings['halfcardtype'] == 1 && $settings['halfstatus'] == 1)
			$allow = 1;
		if ($_GPC['postType'] == 'setting') {
			$distri = Setting::wlsetting_read('distribution');
			if (checksubmit('submit')) {
				$question = $_GPC['question'];
				$answer = $_GPC['answer'];
				$len = count($question);
				$tag = array();
				for ($k = 0; $k < $len; $k++) {
					$tag[$k]['question'] = $question[$k];
					$tag[$k]['answer'] = $answer[$k];
				}
				$base = $_GPC['base'];
				if(empty($base['daily'])){
					$base['dailyshow'] = 0;
				}
				$base['qanda'] = serialize($tag);

				$hcname = $_GPC['hcname'];
				$howlong = $_GPC['howlong'];
				$hcprice = $_GPC['hcprice'];
				$len2 = count($hcname);
				$tag2 = array();
				for ($k = 0; $k < $len2; $k++) {
					$tag2[$k]['hcname'] = $hcname[$k];
					$tag2[$k]['howlong'] = $howlong[$k];
					$tag2[$k]['hcprice'] = $hcprice[$k];
				}
				$base['hcprice'] = serialize($tag2);
				$res1 = Setting::agentsetting_save($base, 'halfcard');
				if ($res1) {
					wl_message('保存设置成功！', referer(), 'success');
				} else {
					wl_message('保存设置失败！', referer(), 'error');
				}
			}
		}
		if ($_GPC['postType'] == 'list') {
			$listData = Util::getNumData("*", PDO_NAME . 'halfcard_type', array('aid' => $_W['aid']));
			$types = $listData[0];
			$where = array('tokentype' => 2, 'aid' => $_W['aid']);
			if ($_GPC['vipType'])
				$where['type'] = $_GPC['vipType'];
			if ($_GPC['status']) {
				if ($_GPC['status'] == 1)
					$where['status'] = 1;
				if ($_GPC['status'] == 2)
					$where['status'] = 0;
			}
			if (!empty($_GPC['keyword'])) {
				if (!empty($_GPC['keywordtype'])) {
					switch($_GPC['keywordtype']) {
						case 1 :
							$where['days'] = $_GPC['keyword'];
							break;
						case 2 :
							$member = Util::getSingelData("id", PDO_NAME . 'member', array('@nickname@' => $_GPC['keyword']));
							if ($member)
								$where['mid'] = $member['id'];
						case 3 :
							$where['@remark@'] = $_GPC['keyword'];
							break;
						default :
							break;
					}
				}
			}
			$pindex = max(1, $_GPC['page']);
			$listData = Util::getNumData("*", PDO_NAME . 'token', $where, 'type desc', $pindex, 20, 1);
			$list = $listData[0];
			$pager = $listData[1];

			foreach ($list as $key => &$value) {
				if (!empty($value['mid']))
					$value['member'] = Member::wl_member_get($value['mid']);
				if (!empty($value['aid']))
					$value['aName'] = Util::idSwitch('aid', 'aName', $value['aid']);
			}
		}
		if ($_GPC['postType'] == 'add') {
			$where = array();
			if ($_GPC['applyid']) {
				$apply = Util::getSingelData("*", PDO_NAME . 'token_apply', array('id' => $_GPC['applyid']));
				$apply['token'] = Util::getSingelData("*", PDO_NAME . 'halfcard_type', array('id' => $apply['type']));
			}
			$listData = Util::getNumData("*", PDO_NAME . 'halfcard_type', $where);
			$types = $listData[0];
			if (checksubmit()) {
				$secretkey_type = $_GPC['secretkey_type'];
				$type = Util::getSingelData("*", PDO_NAME . 'halfcard_type', array('id' => $secretkey_type));
				$num = !empty($_GPC['num']) ? intval($_GPC['num']) : 1;
				if ($num > 0) {
					if ($allow) {
						for ($k = 0; $k < $num; $k++) {
							$data['uniacid'] = $_W['uniacid'];
							$data['days'] = $type['days'];
							$data['tokentype'] = 2;
							$data['type'] = $type['id'];
							$data['typename'] = $type['name'];
							$data['price'] = $type['price'];
							$data['number'] = Util::createConcode(3);
							$data['aid'] = $_W['aid'];
							$data['createtime'] = TIMESTAMP;
							$data['remark'] = $_GPC['remark'];
							pdo_insert(PDO_NAME . 'token', $data);
						}
						wl_message("创建成功!需要创建" . $num . "个，成功创建" . $k . "个。", web_url('halfcard/halfcard_web/base', array('postType' => 'list')), 'success');
					} else {
						$data = array('aid' => $_W['aid'], 'uniacid' => $_W['uniacid'], 'type' => $secretkey_type, 'tokentype' => 2, 'createtime' => TIMESTAMP, 'status' => 1, 'num' => $num);
						pdo_insert(PDO_NAME . 'token_apply', $data);
						wl_message("申请成功", web_url('halfcard/halfcard_web/base', array('postType' => 'apply')), 'success');
					}
				} else {
					wl_message("数量填写不正确!", web_url('halfcard/halfcard_web/base', array('postType' => 'add')), 'error');
				}
			}
		}
		if ($_GPC['postType'] == 'del') {
			pdo_delete(PDO_NAME . 'token', array('id' => $_GPC['id']));
			header("Location:" . web_url('member/token/vipToken', array('postType' => 'list')));
		}
		if ($_GPC['postType'] == 'remark') {
			pdo_update(PDO_NAME . 'token', array('remark' => $_GPC['remark']), array('id' => $_GPC['id']));
			die(json_encode(array('message' => '备注成功')));
		}
		if ($_GPC['postType'] == 'apply') {
			$listData = Util::getNumData("*", PDO_NAME . 'token_apply', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid'], 'tokentype' => 2), 'type desc,status asc', 0, 0, 0);
			$applys = $listData[0];
			foreach ($applys as &$apply) {
				$apply['token'] = Util::getSingelData("*", PDO_NAME . 'halfcard_type', array('id' => $apply['type']));
				$apply['aName'] = Util::idSwitch('aid', 'aName', $apply['aid']);
			}
		}
		if ($_GPC['postType'] == 'type') {
			$where = array();
			if ($settings['halfcardtype'] == 2)
				$where['aid'] = $_W['aid'];
			if ($settings['halfcardtype'] == 1)
				$where['aid'] = 0;
			$pindex = max(1, $_GPC['page']);
			$listData = Util::getNumData("*", PDO_NAME . 'halfcard_type', $where, 'id desc', $pindex, 10, 1);
			$list = $listData[0];
		}
		if ($_GPC['postType'] == 'addType') {
			$memberType = $_GPC['data'];
			if ($_GPC['id'])
				$data = Util::getSingelData("*", PDO_NAME . 'halfcard_type', array('id' => $_GPC['id']));
			if ($_GPC['data']) {
				$memberType['uniacid'] = $_W['uniacid'];
				$memberType['aid'] = $_W['aid'];
				if ($_GPC['id'])
					pdo_update(PDO_NAME . 'halfcard_type', $memberType, array('id' => $_GPC['id']));
				else
					pdo_insert(PDO_NAME . 'halfcard_type', $memberType);
				wl_message('操作成功！', web_url('halfcard/halfcard_web/base', array('postType' => 'type')), 'success');
			}
		}
		if ($_GPC['postType'] == 'delType') {
			pdo_delete(PDO_NAME . 'halfcard_type', array('id' => $_GPC['id']));
			wl_message('操作成功！', web_url('halfcard/halfcard_web/base', array('postType' => 'type')), 'success');
		}
		if ($_GPC['postType'] == 'output') {
			$where = array('tokentype' => 2);
			if ($_GPC['status']) {
				if ($_GPC['status'] == 1)
					$where['status'] = 1;
				if ($_GPC['status'] == 2)
					$where['status'] = 0;
			}
			if (!empty($_GPC['keyword'])) {
				if (!empty($_GPC['keywordtype'])) {
					switch($_GPC['keywordtype']) {
						case 1 :
							$where['days'] = $_GPC['keyword'];
							break;
						case 2 :
							$member = Util::getSingelData("id", PDO_NAME . 'member', array('@nickname@' => $_GPC['keyword']));
							if ($member)
								$where['mid'] = $member['id'];
						case 3 :
							$where['@remark@'] = $_GPC['keyword'];
							break;
						default :
							break;
					}
				}
			}
			$listData = Util::getNumData("*", PDO_NAME . 'token', $where);
			$list = $listData[0];
			foreach ($list as $key => &$value) {
				if (!empty($value['mid']))
					$value['member'] = Member::wl_member_get($value['mid']);
			}
			$html = "\xEF\xBB\xBF";
			$filter = array('aa' => '类型', 'bb' => '时长(天)', 'cc' => '激活码', 'dd' => '备注', 'ee' => '使用详情', 'ff' => '生成时间');
			foreach ($filter as $key => $title) {
				$html .= $title . "\t,";
			}
			$html .= "\n";
			foreach ($list as $k => $v) {
				$list[$k]['aa'] = $v['typename'];
				$list[$k]['bb'] = $v['days'];
				$list[$k]['cc'] = $v['number'];
				$list[$k]['dd'] = $v['remark'];
				$list[$k]['ee'] = $v['member']['nickname'];
				$list[$k]['ff'] = date('Y-m-d H:i:s', $v['createtime']);

				foreach ($filter as $key => $title) {
					$html .= $list[$k][$key] . "\t,";
				}
				$html .= "\n";
			}
			header("Content-type:text/csv");
			header("Content-Disposition:attachment; filename=VIP激活码.csv");
			echo $html;
			exit();
		}
		if ($_GPC['postType'] == 'delete') {
			$id = $_GPC['id'];
			$res = pdo_delete(PDO_NAME.'token_apply',array('id'=>$id));
			if($res){die(json_encode(array('errno' => 0)));}
		}
		include  wl_template('halfcard/base');
	}

	public function editmember(){
		global $_W, $_GPC;
		$delevel = Setting::wlsetting_read('halflevel');
        $id = $_GPC['id'];
        $levels = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_halflevel')."WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
        if($id > 0){
            $halfmember = pdo_get('wlmerchant_halfcardmember',array('id' => $id));
        }else{
            $halfmember = [
                'expiretime' => time()+30*86400,
                'levelid'    => 0,
                'disable'    => 0
            ];
        }
        if($_W['ispost']){
            $data = array(
                'disable'   => trim($_GPC['disable']),
                'levelid'   => trim($_GPC['levelid']),
                'expiretime'=> strtotime($_GPC['expiretime']),
                'mid'       => $_GPC['memberid']
            );
            if($id){
                $res = pdo_update('wlmerchant_halfcardmember',$data,array('id' => $id));
            }else{
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                $data['createtime'] = time();
                $data['username'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$data['mid']),'nickname');
                $data['channel'] = 5;
                $res = pdo_insert('wlmerchant_halfcardmember',$data);
            }
            if($res){
                show_json(1,'操作成功');
            }else {
                show_json(0,'操作失败,请重试');
            }
        }
		include  wl_template('halfcard/editmembermodel');
	}

	function memberlist() {
		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$delevel = Setting::wlsetting_read('halflevel');
		$keywordtype = $_GPC['keywordtype'];
        $usetype = $_GPC['status'];

        if(!empty($keyword)) {
            if ($keywordtype == 1) {
                $params[':nickname'] = "%{$keyword}%";
                $member = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :nickname", $params);
                $ids = array_column($member,'id');
                if(is_array($ids) && count($ids) > 1){
                    $idStr = implode(',',$ids);
                    $where['#mid'] = "({$idStr})";
                }else if($ids[0] > 0){
                    $where['mid'] = $ids[0];
                }
            } else if ($keywordtype == 2) {
                $params[':mobile'] = "%{$keyword}%";
                $member = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']}  AND mobile LIKE :mobile", $params);
                if ($member) {
                    $mids = "(";
                    foreach ($member as $key => $v) {
                        if ($key == 0) {
                            $mids .= $v['id'];
                        } else {
                            $mids .= "," . $v['id'];
                        }
                    }
                    $mids .= ")";
                    $where['mid#'] = $mids;
                } else {
                    $where['mid#'] = "(0)";
                }
            }else if($keywordtype == 3){
                $where['username@'] = $keyword;
            }else if($keywordtype == 4){
                $params[':cardsn'] = "%{$keyword}%";
                $member = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halfcard_realcard') . "WHERE uniacid = {$_W['uniacid']}  AND cardsn LIKE :cardsn", $params);
                if ($member) {
                    $mids = "(";
                    foreach ($member as $key => $v) {
                        if ($key == 0) {
                            $mids .= $v['cardid'];
                        } else {
                            $mids .= "," . $v['cardid'];
                        }
                    }
                    $mids .= ")";
                    $where['id#'] = $mids;
                } else {
                    $where['id#'] = "(0)";
                }
            }
        }
		if ($_GPC['status']) {
			if ($_GPC['status'] == 1) {
				$where['expiretime>'] = time();
			} else {
				$where['expiretime<'] = time();
			}
		}
        //时间筛选
        if (!empty($_GPC['time_limit']) && $_GPC['timetype'] ) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            if($_GPC['timetype'] == 1){  //开通时间
                $where['createtime>'] = $starttime;
                $where['createtime<'] = $endtime;
            }else{
                if($usetype == 1){
                    $Cstarttime = max([time(),$starttime]);
                    $Cendtime = $endtime;
                }else if($usetype == 2){
                    $Cstarttime = $starttime;
                    $Cendtime = min([time(),$endtime]);
                }else{
                    $Cstarttime = $starttime;
                    $Cendtime = $endtime;
                }
                $where['expiretime>'] = $Cstarttime;
                $where['expiretime<'] = $Cendtime;
            }
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
		
		//	wl_debug($keywordtype);
		$where['uniacid'] = $_W['uniacid'];
		$where['aid'] = $_W['aid'];
		
		if($_GPC['outflag']){
			$this -> outmemberlist($where);
		}
		
		$member = Halfcard::getNumhalfcardmember('*', $where, 'ID DESC', $pindex, $psize, 1);
		$pager = $member[1];
		$member = $member[0];
		foreach ($member as $key => &$v) {
			$user = pdo_get('wlmerchant_member', array('id' => $v['mid']));
            $v['nickname'] = $user['encodename'] ? base64_decode($user['encodename']) : $user['nickname'];
			if(empty($v['username'])){
				$v['username'] = $user['nickname'];
			}
			$v['avatar'] = $user['avatar'];
			$v['mobile'] = $user['mobile'];
			if ($v['expiretime'] > time()) {
				if($v['disable']){
					$v['status'] = 3;
				}else{
					$v['status'] = 1;
				}
				
			} else {
				$v['status'] = 2;
			}
			$v['cardsn'] = pdo_getcolumn(PDO_NAME.'halfcard_realcard',array('cardid'=>$v['id']),'cardsn');
			if($v['levelid']){
				$v['levelname'] = pdo_getcolumn(PDO_NAME.'halflevel',array('id'=>$v['levelid']),'name');
			}else {
				$v['levelname'] = $delevel['name'];
			}
            $v['banknum'] = $user['card_number'];
        }
		
		include  wl_template('halfcard/halfcardmemberlist');
	}

	public function outmemberlist($where){
		global $_W, $_GPC;
        $delevel = Setting::wlsetting_read('halflevel');
        $set = Setting::wlsetting_read('halfcard');
        $member = Halfcard::getNumhalfcardmember('*', $where, 'ID DESC',0,0,1);
		$member = $member[0];
		foreach ($member as $key => &$v) {
			$user = pdo_get('wlmerchant_member', array('id' => $v['mid']));
            $v['nickname'] = $user['nickname'];
            if(empty($v['username'])){
				$v['username'] = $user['nickname'];
			}
			$v['mobile'] = $user['mobile'];
            $v['realname'] = $user['realname'];
            if ($v['expiretime'] > time()) {
				if($v['disable']){
					$v['status'] = 3;
				}else{
					$v['status'] = 1;
				}
				
			} else {
				$v['status'] = 2;
			}
			$v['cardsn'] = pdo_getcolumn(PDO_NAME.'halfcard_realcard',array('cardid'=>$v['id']),'cardsn');
            if (file_exists(PATH_MODULE . 'N814.log')) {
                $v['banknum'] = intval($user['card_number']);
            }
            $v['agentuser'] = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$v['aid']),'agentname');
            if(empty($v['agentuser'])){
                $v['agentuser'] = '总后台';
            }
            //会员等级
            if($v['levelid']){
                $v['levelname'] = pdo_getcolumn(PDO_NAME.'halflevel',array('id'=>$v['levelid']),'name');
            }else {
                $v['levelname'] = $delevel['name'];
            }
            //获取开卡额外记录
            $moreinfo = pdo_fetchall("SELECT moinfo FROM ".tablename('wlmerchant_halfcard_record')."WHERE uniacid = {$_W['uniacid']} AND mid = {$v['mid']} AND status = 1 ORDER BY id DESC limit 1");
            $v['moinfo'] = unserialize($moreinfo[0]['moinfo']);
            foreach ($v['moinfo'] as $zzw => $in){
                if($in['id'] == 'checkbox' || $in['id'] == 'img'){
                    $v['zzw'.$zzw] = implode(",", $in['data']);
                }else if($in['id'] == 'datetime' || $in['id'] == 'city'){
                    $v['zzw'.$zzw] = implode("-", $in['data']);
                } else{
                    $v['zzw'.$zzw] = $in['data'];
                }
            }
		}
		
		/* 输出表头 */
        $filter = array(
            'id' => '一卡通id',
            'agentuser' => '所属代理',
            'nickname'  => '用户昵称',
            'username' => '持有人姓名',
            'realname' => '真实姓名',
            'mobile' => '持有人手机',
            'levelname' => '会员等级',
            'status' => '使用状态',
            'createtime' => '生成时间',
            'expiretime' => '过期时间',
            'cardsn' => '实卡编号',
        );

        if (file_exists(PATH_MODULE . 'N814.log')) {
            $filter['banknum'] = '银行卡号';
        }

        if($set['diyformid'] > 0){
            $diyforminfo = pdo_get('wlmerchant_diyform',array('id' => $set['diyformid']),array('info'));
            $moinfo = json_decode(base64_decode($diyforminfo['info']) , true);
            $list = $moinfo['list'];
            $list = array_values($list);
            foreach ($list as $wlf => $li){
                $filter['zzw'.$wlf] = $li['data']['title'];
            }
        }
		$data = array();
		for ($i=0; $i < count($member) ; $i++) {
			foreach ($filter as $key => $title) {
				if($key == 'status'){
					switch ($member[$i][$key]) {
						case '1':
							$data[$i][$key] = '使用中';
							break;
						case '2':
							$data[$i][$key] = '已过期';
							break;
						default:
							$data[$i][$key] = '被禁用';
							break;
					}
				}else if($key == 'createtime') {
					$data[$i][$key] = date('Y-m-d H:i:s', $member[$i][$key]);
				}else if($key == 'expiretime'){
					$data[$i][$key] = date('Y-m-d H:i:s', $member[$i][$key]);
				}else {
					$data[$i][$key] = $member[$i][$key];
				}
			}
		}
		util_csv::export_csv_2($data,$filter, '一卡通用户列表.csv');
		exit;

	}

	function outpaylist($where){
        global $_W, $_GPC;
        $delevel = Setting::wlsetting_read('halflevel');
        $set = Setting::wlsetting_read('halfcard');
        $pay = Halfcard::getNumhalfcardpay('*', $where, 'ID DESC',0,0,1);
        $pay = $pay[0];


        foreach($pay as &$v){
            //所属代理
            $v['agentuser'] = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$v['aid']),'agentname');
            if(empty($v['agentuser'])){
                $v['agentuser'] = '总后台';
            }
            //用户信息
            $user = pdo_get('wlmerchant_member', array('id' => $v['mid']));
            $v['nickname'] = $user['nickname'];
            if(empty($v['username'])){
                $v['username'] = $user['nickname'];
            }
            if(empty($v['username'])){
                $v['mobile'] = $user['mobile'];
            }
            //会员等级
            $levelid = pdo_getcolumn(PDO_NAME.'halfcard_type',array('id'=>$v['typeid']),'levelid');
            if($levelid>0){
                $v['levelname'] = pdo_getcolumn(PDO_NAME.'halflevel',array('id'=>$levelid),'name');
            }else {
                $v['levelname'] = $delevel['name'];
            }
            //开通时长
            $v['howlong'] = $v['howlong'].'天';
            //支付方式
            switch ($v['paytype']) {
                case 1:
                    $v['paytype'] = "余额支付";
                    break;
                case 2:
                    $v['paytype'] = "微信支付";
                    break;
                case 3:
                    $v['paytype'] = "支付宝支付";
                    break;
                case 4:
                    $v['paytype'] = "货到付款";
                    break;
                case 5:
                    $v['paytype'] = "小程序支付";
                    break;
                case 6:
                    $v['paytype'] = "0元购";
                    break;
                default:
                    $v['paytype'] = "未知方式";
                    break;
            }
            //支付时间
            $v['paytime'] = date('Y-m-d H:i:s',$v['paytime']);
            $v['orderno'] = "\t".$v['orderno']."\t";
            //更多信息
            $v['moinfo'] = unserialize($v['moinfo']);
            foreach ($v['moinfo'] as $zzw => $in){
                if($in['id'] == 'checkbox' || $in['id'] == 'img'){
                    $v['zzw'.$zzw] = implode(",", $in['data']);
                }else if($in['id'] == 'datetime' || $in['id'] == 'city'){
                    $v['zzw'.$zzw] = implode("-", $in['data']);
                } else{
                    $v['zzw'.$zzw] = $in['data'];
                }
            }
        }
        /* 输出表头 */
        $filter = array(
            'orderno' => '订单编号',
            'agentuser' => '所属代理',
            'nickname'  => '用户昵称',
            'username' => '持有人姓名',
            'mobile' => '持有人手机',
            'levelname' => '会员等级',
            'price' => '订单金额',
            'howlong' => '开通时长',
            'paytype' => '支付方式',
            'paytime' => '支付时间'
        );

        if($set['diyformid'] > 0){
            $diyforminfo = pdo_get('wlmerchant_diyform',array('id' => $set['diyformid']),array('info'));
            $moinfo = json_decode(base64_decode($diyforminfo['info']) , true);
            $list = $moinfo['list'];
            $list = array_values($list);
            foreach ($list as $wlf => $li){
                $filter['zzw'.$wlf] = $li['data']['title'];
            }
        }
        $data = array();
        for ($i=0; $i < count($pay) ; $i++) {
            foreach ($filter as $key => $title) {
                $data[$i][$key] = $pay[$i][$key];
            }
        }
        util_csv::export_csv_2($data,$filter, '一卡通开通记录.csv');
        exit;

    }

	function payhalfcardlist() {
		global $_W, $_GPC;
		$pindex = max(1, intval($_GPC['page']));
		$psize = 10;
		$keywordtype = $_GPC['keywordtype'];
		$keyword = trim($_GPC['keyword']);
		if($keyword){
			if($keywordtype == 1){
				$params[':nickname'] = "%{$keyword}%";
				$member = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname", $params);
				if ($member) {
					$mids = "(";
					foreach ($member as $key => $v) {
						if ($key == 0) {
							$mids .= $v['id'];
						} else {
							$mids .= "," . $v['id'];
						}
					}
					$mids .= ")";
					$where['mid#'] = $mids;
				} else {
					$where['mid#'] = "(0)";
				}
			} else if ($keywordtype == 2) {
				$params[':mobile'] = "%{$keyword}%";
				$member = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND mobile LIKE :mobile", $params);
				if ($member) {
					$mids = "(";
					foreach ($member as $key => $v) {
						if ($key == 0) {
							$mids .= $v['id'];
						} else {
							$mids .= "," . $v['id'];
						}
					}
					$mids .= ")";
					$where['mid#'] = $mids;
				} else {
					$where['mid#'] = "(0)";
				}
			} else if($keywordtype == 3){
                $where['orderno@'] = $keyword;
            }
		}

        //时间筛选
        if (!empty($_GPC['time_limit']) && $_GPC['timetype'] > 0) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $where['paytime>'] = $starttime;
            $where['paytime<'] = $endtime;
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
		
		$where['uniacid'] = $_W['uniacid'];
		if($_W['aid'] > 0) $where['aid'] = $_W['aid'];//总后台则获取所有的开通记录
		$where['status'] = 1;

		if($_GPC['orderid']){
			$where['id'] = $_GPC['orderid'];
		}
        if($_GPC['outflag']){
            $this -> outpaylist($where);
        }
		
		$pay = Halfcard::getNumhalfcardpay('*', $where, 'ID DESC', $pindex, $psize, 1);
		$pager = $pay[1];
		$pay = $pay[0];
		foreach ($pay as $key => &$v) {
			$user = pdo_get('wlmerchant_member', array('id' => $v['mid']));
			$v['nickname'] = $user['nickname'];
			$v['avatar'] = $user['avatar'];
			$v['mobile'] = $user['mobile'];
            $v['agentuser'] = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$v['aid']),'agentname');
            if(empty($v['agentuser'])){
                $v['agentuser'] = '总后台';
            }
		}
		include  wl_template('halfcard/payhalfcardlist');
	}

	function QandA() {
		include  wl_template('halfcard/QandA');
	}

	function halfcardprice() {
		include  wl_template('halfcard/pricepage');
	}

	function selectMerchant() {
		global $_W, $_GPC;
		$where = array();
		$where['uniacid'] = $_W['uniacid'];
		$where['aid'] = $_W['aid'];
		$where['enabled'] = 1;
		$type = $_GPC['type'];
		if ($_GPC['keyword'])
			$where['@storename@'] = $_GPC['keyword'];
		$merchants = Rush::getNumMerchant('id,storename,logo', $where, 'ID DESC', 0, 0, 0);
		$merchants = $merchants[0];
		foreach ($merchants as $key => &$va) {
			$va['logo'] = tomedia($va['logo']);
			if($type == 1){
				$res = pdo_get('wlmerchant_halfcardlist', array('merchantid' => $va['id']), array('id'));
			}
			if ($res) {
				unset($merchants[$key]);
			}
		}
		include  wl_template('goodshouse/selectMerchant');
	}
	
	function tovip(){
		global $_W,$_GPC;
		$halfmember = pdo_fetchall("SELECT mid,expiretime FROM ".tablename('wlmerchant_halfcardmember')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND expiretime >".time()." ORDER BY id DESC");
		if($halfmember){
			foreach($halfmember as $key => $v){
				$member = pdo_get('wlmerchant_member',array('id' => $v['mid']),array('lastviptime','vipstatus'));
				if(empty($member['lastviptime']) || $member['lastviptime'] < $v['expiretime']){
					$data['lastviptime'] = $v['expiretime'];
					$data['vipleveldays'] = floor(($v['expiretime'] - time())/86400);
					$data['vipstatus'] = 1;
					$data['aid'] = $_W['aid'];
					$areaid = pdo_getcolumn(PDO_NAME.'oparea',array('uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']),'areaid');
					$data['areaid'] = $areaid;
					pdo_update('wlmerchant_member',$data,array('id' => $v['mid']));
				}
			}
		}
		die(json_encode(array('errno'=>0,'mess'=>$halfmember)));
	}
	
	function changeinfo(){
		global $_W,$_GPC;
		$id = $_GPC['id'];
		$type = $_GPC['type'];
		$newvalue = trim($_GPC['value']);
		if($type == 1){
			$res = pdo_update('wlmerchant_package',array('title'=>$newvalue),array('id' => $id));
		}elseif ($type == 2) {
			$res = pdo_update('wlmerchant_package',array('pv'=>$newvalue),array('id' => $id));
		}elseif ($type == 3) {
			$res = pdo_update('wlmerchant_package',array('sort'=>$newvalue),array('id' => $id));
		}elseif ($type == 4) {
			$res = pdo_delete('wlmerchant_package',array('id'=>$id));
		}elseif ($type == 5) {
			$res = pdo_update('wlmerchant_package',array('limit'=>$newvalue),array('id' => $id));
		}
		if($res){
			show_json(1,'修改成功');
		}else {
			show_json(0,'修改失败，请重试');
		}
	}

	//说明设置
    function explainSet(){
        global $_W, $_GPC;
        $set = Setting::agentsetting_read('halfcard');

        if ($_W['ispost']) {
            $data = [
                'describe'   => htmlspecialchars_decode($_GPC['describe']),
                'nodescribe' => htmlspecialchars_decode($_GPC['nodescribe'])
            ];

            Setting::agentsetting_save($data, 'halfcard');
            wl_message('设置成功', web_url('halfcard/halfcard_web/explainSet'));
        }


        include wl_template('halfcard/explainSet');
    }
}