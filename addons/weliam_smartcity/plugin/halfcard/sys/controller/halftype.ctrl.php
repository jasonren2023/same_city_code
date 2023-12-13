<?php
defined('IN_IA') or exit('Access Denied');

class Halftype_WeliamController {
    public function lists() {
        global $_W, $_GPC;
        $delevel = Setting::wlsetting_read('halflevel');
        $pindex = max(1, $_GPC['page']);
        $listData = Util::getNumData("*", PDO_NAME . 'halfcard_type', array(), 'sort desc,id desc', $pindex, 10, 1);
        $list = $listData[0];
        $pager = $listData[1];
        foreach ($list as $key => &$va) {
            if($va['levelid']){
                $va['levelname'] = pdo_getcolumn(PDO_NAME.'halflevel',array('id'=>$va['levelid']),'name');
            }else {
                $va['levelname'] = $delevel['name'];
            }
        }

        include  wl_template('halfcardsys/typelist');
    }

    function memberlist() {
        global $_W, $_GPC;
        $delevel = Setting::wlsetting_read('halflevel');
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $keywordtype = $_GPC['keywordtype'];
        $keyword = trim($_GPC['keyword']);
        $ac = 'halftype';
        if(!empty($keyword)){
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

        //	wl_debug($keywordtype);
        $where['uniacid'] = $_W['uniacid'];

        $usetype = $_GPC['usetype'];
        if ($usetype == 1) {
            $where['expiretime>'] = time();
        } else if($usetype == 2){
            $where['expiretime<'] = time();
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
            $v['agentuser'] = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$v['aid']),'agentname');
            if(empty($v['agentuser'])){
                $v['agentuser'] = '总后台';
            }
            //查询更多数据
//		    $moinfoarray = pdo_getall('wlmerchant_halfcard_record',array('mid'=>$v['mid'],'uniacid'=>$_W['uniacid']),array('id','moinfo'),'','id DESC','limit 1');
//          $moinfo = $moinfoarray[0]['moinfo'];
//			if(!empty($moinfo)){
//		        $v['moinfoflag'] = 1;
//                $v['orderid'] = $moinfoarray[0]['id'];
//           }
        }
        include  wl_template('halfcardsys/halfcardmemberlist');
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

    public function editmember(){
        global $_W, $_GPC;
        $delevel = Setting::wlsetting_read('halflevel');
        $ac = 1;
        $id = $_GPC['id'];
        if($id > 0){
            $halfmember = pdo_get('wlmerchant_halfcardmember',array('id' => $id));
        }else{
            $halfmember = [
                'expiretime' => time()+30*86400,
                'levelid'    => 0,
                'disable'    => 0
            ];
        }
        $levels = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_halflevel')."WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
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
        include  wl_template('halfcardsys/editmembermodel');
    }

    public function uselists(){
        global $_W, $_GPC;
        $ac = 'halftype';
        $keyword = trim($_GPC['keyword']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $data = array();
        $data['uniacid'] = $_W['uniacid'];
        if(!empty($_GPC['reid'])){
            $data['id'] = $_GPC['reid'];
        }
        $type = $_GPC['type']?$_GPC['type']:1;
        $data['type'] = $type;
        if($type == 2){
            if($_GPC['alflag']){
                $data['usetime >'] = 0;
            }
        }
        if ($_GPC['keywordtype'] == 1) {
            $where = " AND title LIKE :title";
            $params[':title'] = "%{$keyword}%";
            if($type == 2){
                $halfcard = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_package') . " where uniacid= {$_W['uniacid']} $where", $params);
            }else {
                $halfcard = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_halfcardlist') . " where uniacid= {$_W['uniacid']} $where", $params);
            }
            $data['activeid'] = $halfcard['id'];
        }
        if ($_GPC['keywordtype'] == 2) {
            $where = " AND storename LIKE :storename";
            $params[':storename'] = "%{$keyword}%";
            $halfcard = pdo_fetch("SELECT * FROM " . tablename('wlmerchant_merchantdata') . " where uniacid= {$_W['uniacid']}  $where", $params);
            $data['merchantid'] = $halfcard['id'];
        }
        if ($_GPC['keywordtype'] == 3) {
            $where = " AND username LIKE :username";
            $params[':username'] = "%{$keyword}%";
            $halfcard = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halfcardmember') . " where uniacid= {$_W['uniacid']} $where", $params);
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
                $data['cardid#'] = $mids;
            } else {
                $data['cardid#'] = "(0)";
            }
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
        if (!empty($_GPC['time_limit'])){
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']) ;
            if(!empty($_GPC['timetype'])){
                $data['createtime>'] = $starttime;
                $data['createtime<'] = $endtime+86400;
            }
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
        include  wl_template('halfcardsys/userHalfcardList');
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
            $v['aidname'] = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$v['aid']),'agentname');
            $v['creatcardtime'] = pdo_getcolumn(PDO_NAME.'halfcardmember',array('id'=>$v['cardid']),'createtime');

        }

        if($where['type'] == 1){
            $filter = array(
                'id' => '记录id',
                'aidname' => '所属代理',
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
            foreach ($halfcard as $k => $v) {
                foreach ($filter as $key => $title) {
                    if($key == 'usetime' || $key == 'creatcardtime') {
                        $data[$k][$key] = date('Y-m-d H:i:s', $v[$key]);
                    }else {
                        $data[$k][$key] = $v[$key];
                    }
                }
            }
            util_csv::export_csv_2($data,$filter, '一卡通特权使用记录.csv');
            exit;
        }else {
            $filter = array(
                'id' => '记录id',
                'title' => '特权名称',
                'aidname' => '所属代理',
                'storename' => '所属商家',
                'username' => '买家昵称',
                'mobile' => '买家电话',
                'ordermoney' => '礼包价值',
                'varname' => '核销人',
                'usetime' => '使用时间',
                'creatcardtime' => '开卡时间'
            );

            $data = array();
            foreach ($halfcard as $k => $v) {
                foreach ($filter as $key => $title) {
                    if($key == 'usetime' || $key == 'creatcardtime') {
                        $data[$k][$key] = date('Y-m-d H:i:s', $v[$key]);
                    }else {
                        $data[$k][$key] = $v[$key];
                    }
                }
            }
            util_csv::export_csv_2($data,$filter, '一卡通大礼包使用记录.csv');
            exit;
        }

    }


    public function outmemberlist($where){
        global $_W, $_GPC;
        $delevel = Setting::wlsetting_read('halflevel');
        $set = Setting::wlsetting_read('halfcard');
        $member = Halfcard::getNumhalfcardmember('*', $where, 'ID DESC', 0, 0, 1);
        $member = $member[0];
        foreach ($member as $key => &$v) {
            $user = pdo_get('wlmerchant_member', array('id' => $v['mid']));
            $v['nickname'] = $user['nickname'];
            if(empty($v['username'])){
                $v['username'] = $user['nickname'];
            }
            $v['realname'] = $user['realname'];
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

    public function add() {
        global $_W, $_GPC;
        $delevel = Setting::wlsetting_read('halflevel');
        $levels = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_halflevel')."WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
        $halfset = Setting::wlsetting_read('halfcard');
        if($halfset['halfcardtype'] == 2){
            $agents = pdo_getall('wlmerchant_agentusers', array('uniacid' => $_W['uniacid']), array('id', 'agentname'));
        }
        //支付有礼
        if(p('paidpromotion')){
            $paidlist = pdo_getall('wlmerchant_payactive',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));
        }
        $memberType = $_GPC['data'];
        if ($_GPC['id']) {
            $data = Util::getSingelData("*", PDO_NAME . 'halfcard_type', array('id' => $_GPC['id']));
        }
        if ($_GPC['data']) {
            $memberType['uniacid'] = $_W['uniacid'];
            if($halfset['halfcardtype'] == 1){
                $memberType['aid'] = 0;
            }
            if ($_GPC['id']) {
                pdo_update(PDO_NAME . 'halfcard_type', $memberType, array('id' => $_GPC['id']));
            } else {
                pdo_insert(PDO_NAME . 'halfcard_type', $memberType);
            }
            wl_message('操作成功！', web_url('halfcard/halftype/lists'), 'success');
        }
        //分销商设置
        $disset = Setting::wlsetting_read('distribution');
        $dislevels = pdo_getall('wlmerchant_dislevel',array('uniacid' => $_W['uniacid']),array('id','name'));


        include  wl_template('halfcardsys/typeadd');
    }

    public function delType() {
        global $_W, $_GPC;
        $res = pdo_delete(PDO_NAME . 'halfcard_type', array('id' => $_GPC['id']));
        if($res){
            show_json(1,'操作成功');
        }else {
            show_json(0,'网络错误，请刷新重试!');
        }
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
        $res = pdo_delete('wlmerchant_halfcardmember',array('id' => $id));
        if ($res) {
            die(json_encode(array('errno' => 0, 'message' => $res, 'id' => $id)));
        } else {
            die(json_encode(array('errno' => 2, 'message' => $res, 'id' => $id)));
        }
    }

    function halflevel(){
        global $_W, $_GPC;
        $base = Setting::wlsetting_read('halflevel');
        if(empty($base)){
            $base['name'] = '默认等级';
            Setting::wlsetting_save($base,'halflevel');
        }
        $levels = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_halflevel')."WHERE uniacid = {$_W['uniacid']} ORDER BY sort DESC");

        include wl_template('halfcardsys/halflevel');
    }

    public function editlevel(){
        global $_W, $_GPC;
        $base = Setting::wlsetting_read('halflevel');
        $id = $_GPC['id'];
        if($id == 'default'){
            $level['name'] = $base['name']?$base['name']:'默认等级';
            $level['id'] = 'default';
            $level['cardimg'] = tomedia($base['cardimg']);
            $level['hhklevel'] = $base['hhklevel'];
        }else if($id){
            $level=pdo_get('wlmerchant_halflevel',array('id' => $id));
            $level['cardimg'] = tomedia($level['cardimg']);
        }
        if($_W['ispost']){
            if($id == 'default'){
                $data['name'] = trim($_GPC['name']);
                $data['cardimg'] = $_GPC['cardimg'];
                $data['hhklevel'] = $_GPC['hhklevel'];
                $res = Setting::wlsetting_save($data,'halflevel');
            }else if($id){
                $data = array(
                    'name'   => trim($_GPC['name']),
                    'sort'   => trim($_GPC['sort']),
                    'cardimg'=> $_GPC['cardimg'],
                    'status' => $_GPC['status'],
                    'army'   => $_GPC['army'],
                    'hhklevel' => $_GPC['hhklevel'],
                );
                $res = pdo_update('wlmerchant_halflevel',$data,array('id' => $id));
            }else {
                $data=array(
                    'uniacid'=> $_W['uniacid'],
                    'name'   => trim($_GPC['name']),
                    'sort'   => trim($_GPC['sort']),
                    'status' => $_GPC['status'],
                    'cardimg'=> $_GPC['cardimg'],
                    'createtime'=>time(),
                    'army'   => $_GPC['army'],
                    'hhklevel' => $_GPC['hhklevel'],
                );
                $res = pdo_insert('wlmerchant_halflevel',$data);
            }
            if($res){
                show_json(1, '操作成功！');
            }
        }
        include  wl_template('halfcardsys/halflevelmodel');
    }

    function changelevel(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        $newvalue = trim($_GPC['value']);
        if($id == 'default'){
            $data['name'] = trim($_GPC['name']);
            $res = Setting::wlsetting_save($data,'halflevel');
        }else {
            if($type == 1){
                $res = pdo_update('wlmerchant_halflevel',array('name'=>$newvalue),array('id' => $id));
            }elseif ($type == 2) {
                $res = pdo_update('wlmerchant_halflevel',array('sort'=>$newvalue),array('id' => $id));
            }else if ($type == 3) {
                $res = pdo_update('wlmerchant_halflevel',array('status'=>$newvalue),array('id' => $id));
            }else if ($type == 4) {
                $res = pdo_delete('wlmerchant_halflevel',array('id'=>$id));
            }
        }
        if($res){
            show_json(1,'修改成功');
        }else {
            show_json(0,'修改失败，请重试');
        }
    }

    function moreinfo(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $info = pdo_getcolumn(PDO_NAME.'halfcard_record',array('id'=>$id),'moinfo');
        $info = unserialize($info);
        foreach ($info as &$ll) {
            if(!empty($ll['type'])){
                if($ll['type'] == 'pics'){
                    $ll['value'] = unserialize($ll['value']);
                    foreach($ll['value'] as &$pic){
                        $pic = tomedia($pic);
                    }
                }else if($ll['type'] == 'pic'){
                    $ll['value'] = tomedia($ll['value']);
                }
            }
        }
        include wl_template('halfcardsys/moreinfo');
    }


}
