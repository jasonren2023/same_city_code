<?php
class KeepWeb_WeliamController {

    public function serviceList() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $data = array();
        $data['uniacid'] = $_W['uniacid'];
        $data['aid'] = $_W['aid'];
        if(is_store()){
            $data['type'] = 1;
            $data['objid'] = $_W['storeid'];
        }
        if($_GPC['status'] > 0){
            $data['status'] = $_GPC['status'] == 8 ? 0 : $_GPC['status'];
        }
        if($_GPC['type'] > 0){
            $data['type'] = $_GPC['type'];
        }
        if($_GPC['pricetype'] > 0){
            $data['pricetype'] = $_GPC['pricetype'] == 4 ? 0 : $_GPC['pricetype'];
        }
        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 1:
                        $data['@title@'] = $_GPC['keyword'];
                        break;
                    default:
                        break;
                }
            }
            if ($_GPC['keywordtype'] == 2) {
                $keyword = $_GPC['keyword'];
                $catearray = pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_housekeep_type')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND onelevelid > 0 AND title LIKE '%{$keyword}%' ORDER BY id DESC");
                $cateids = array_column($catearray, 'id');
                $objids = pdo_getall('wlmerchant_housekeep_relation',array('twolevelid' => $cateids,'type'=>1),array('objid'));
                $objids = array_column($objids, 'objid');
                $objids = array_unique($objids);
                if ($objids) {
                    $ids = "(";
                    foreach ($objids as $key => $v) {
                        if ($key == 0) {
                            $ids .= $v['id'];
                        } else {
                            $ids .= "," . $v['id'];
                        }
                    }
                    $ids .= ")";
                    $data['id#'] = $ids;
                } else {
                    $data['id#'] = "(0)";
                }
            }
        }

        $info = Util::getNumData('*', PDO_NAME . 'housekeep_service', $data, 'sort DESC,ID DESC', $pindex, $psize, 1);
        $list = $info[0];
        $pager = $info[1];

        foreach($list as &$st){
            if($st['type'] == 1){
                $store = pdo_get('wlmerchant_merchantdata',array('id' => $st['objid']),array('logo','storename'));
                $st['artiftitle'] = $store['storename'];
                $st['artifthumb'] = $store['logo'];
            }else if($st['type'] == 2){
                $artif = pdo_get('wlmerchant_housekeep_artificer',array('id' => $st['objid']),array('name','thumb'));
                $st['artiftitle'] = $artif['name'];
                $st['artifthumb'] = $artif['thumb'];
            }

            $st['levelarray'] = pdo_fetchall("SELECT b.title FROM ".tablename('wlmerchant_housekeep_relation')." a LEFT JOIN".tablename('wlmerchant_housekeep_type')." b ON a.twolevelid = b.id WHERE a.objid = {$st['id']} AND a.type = 1 ORDER BY b.sort DESC,b.id DESC");
        }

        include wl_template('keepweb/serviceList');
    }

    public function editService() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if($id > 0){
            $service = pdo_get('wlmerchant_housekeep_service',array('id' => $id));
            $service['adv'] = unserialize($service['adv']);
            $levelids = pdo_getall('wlmerchant_housekeep_relation', array('objid' => $id,'type' => 1), 'twolevelid');
            if (!empty($levelids)) {
                $cateids = array_column($levelids, 'twolevelid');
            }
            if(!empty($service['appointarray'])){
                $service['appointarray'] = unserialize($service['appointarray']);
            }
        }
        $stores = pdo_getall('wlmerchant_merchantdata',array('uniacid' => $_W['uniacid'],'aid'=>$_W['aid'],'status' => 2,'enabled' =>1,'housekeepstatus' => 1),array('id','storename'));
        $artificers = pdo_getall('wlmerchant_housekeep_artificer',array('uniacid' => $_W['uniacid'],'aid'=>$_W['aid'],'status' => 1),array('id','name'));

        $houseflag = 1;
        //获取类目
        $categoryes = Housekeep::getCategory();
        //获取海报
        if (p('diyposter')) {
            $posters = pdo_getall(PDO_NAME . 'poster', array('uniacid' => $_W['uniacid'], 'type' => 16), array('id', 'title'));
        }

        if ($_W['ispost']) {
            $service = $_GPC['service'];
            $service['adv'] = serialize($service['adv']);
            $service['detail'] = htmlspecialchars_decode($service['detail']);
            $category = $_GPC['category'];
            if(empty($service['title'])){
                wl_message('请输入服务项目标题');
            }
            if(is_store()){
                $service['type'] = 1;
                $service['sobjid'] = $_W['storeid'];
            }else{
                if(empty($service['aobjid']) && empty($service['sobjid'])){
                    wl_message('选择服务商户或服务者');
                }
            }
            if(empty($service['thumb'])){
                wl_message('请设置服务项目缩略图');
            }
            if(empty($category)){
                wl_message('请选择服务类目');
            }
            if($service['pricetype'] > 0){
                if(empty($service['unit'])){
                    wl_message('请设置服务方式单位');
                }
                $service['price'] = sprintf("%.2f",$service['price']);
                if($service['price'] < 0.01){
                    wl_message('请设置服务费金额');
                }
            }
            //获取经纬度
            if($service['type'] == 1){
                $service['objid'] = $service['sobjid'];
                $storeinfo = pdo_get('wlmerchant_merchantdata',array('id' => $service['objid']),array('lat','lng'));
                $service['lat'] = $storeinfo['lat'];
                $service['lng'] = $storeinfo['lng'];
            }else if($service['type'] == 2){
                $service['objid'] = $service['aobjid'];
                $artificerinfo = pdo_get('wlmerchant_housekeep_artificer',array('id' => $service['objid']),array('lat','lng'));
                $service['lat'] = $artificerinfo['lat'];
                $service['lng'] = $artificerinfo['lng'];
            }
            unset($service['sobjid']);
            unset($service['aobjid']);
            //预约数组
            if($service['appointstatus']>0){
                $appointarray = [];
                $appstartTime = $_GPC['appstartTime'];
                $appendTime = $_GPC['appendTime'];
                $peoplenums = $_GPC['peoplenums'];
                if(!is_array($appstartTime)){
                    wl_message('请设置预约时间段信息!');
                }
                foreach($appstartTime as $appkey => $appoint){
                    $appinta['startTime'] = $appstartTime[$appkey];
                    $appinta['endTime'] = $appendTime[$appkey];
                    $appointarray[] = $appinta;
                }
                $service['appointarray'] = serialize($appointarray);
            }
            //判断status
            if(is_store() && $service['status'] == 1){
                $audits =  pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$data['release_sid']),'audits');
                if(empty($audits)){
                    $service['status'] = 5;
                }
            }
            if($id > 0){
                $res = pdo_update('wlmerchant_housekeep_service',$service,array('id' => $id));
            }else{
                $service['uniacid'] = $_W['uniacid'];
                $service['aid'] = $_W['aid'];
                $service['createtime'] = time();
                $res = pdo_insert('wlmerchant_housekeep_service',$service);
                $id = pdo_insertid();
            }
            //处理分类
            pdo_delete('wlmerchant_housekeep_relation', array('type' => 1,'objid' => $id));
            if($id > 0){
                foreach ($category as $item) {
                    $scate = pdo_get(PDO_NAME . 'housekeep_type', array('id' => $item), array('onelevelid'));
                    $res = pdo_insert('wlmerchant_housekeep_relation', ['type' => 1,'objid' => $id, 'onelevelid' => $scate['onelevelid'], 'twolevelid' => $item]);
                }
            }

            if($res){
                wl_message('信息编辑成功',web_url('housekeep/KeepWeb/serviceList'), 'success');
            }else{
                wl_message('信息保存失败，请重试');
            }

        }

        include wl_template('keepweb/editService');
    }

    public function artificerList() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $data = array();
        $data['uniacid'] = $_W['uniacid'];
        $data['aid'] = $_W['aid'];
        if ($_GPC['status'] == 3) {
            $data['status'] = 0;
        } else if (!empty($_GPC['status'])) {
            $data['status'] = intval($_GPC['status']);
        }


        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 1:
                        $data['@name@'] = $_GPC['keyword'];
                        break;
                    case 3:
                        $data['@mid@'] = $_GPC['keyword'];
                        break;
                    default:
                        break;
                }
            }
            if ($_GPC['keywordtype'] == 2) {
                $keyword = $_GPC['keyword'];
                $params[':nickname'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND nickname LIKE :nickname", $params);
                if ($members) {
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if ($key == 0) {
                            $mids .= $v['id'];
                        } else {
                            $mids .= "," . $v['id'];
                        }
                    }
                    $mids .= ")";
                    $data['mid#'] = $mids;
                } else {
                    $data['mid#'] = "(0)";
                }
            }else if ($_GPC['keywordtype'] == 4) {
                $keyword = $_GPC['keyword'];
                $catearray = pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_housekeep_type')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND onelevelid > 0 AND title LIKE '%{$keyword}%' ORDER BY id DESC");
                $cateids = array_column($catearray, 'id');
                $objids = pdo_getall('wlmerchant_housekeep_relation',array('twolevelid' => $cateids,'type'=>2),array('objid'));
                $objids = array_column($objids, 'objid');
                $objids = array_unique($objids);
                if ($objids) {
                    $ids = "(";
                    foreach ($objids as $key => $v) {
                        if ($key == 0) {
                            $ids .= $v['id'];
                        } else {
                            $ids .= "," . $v['id'];
                        }
                    }
                    $ids .= ")";
                    $data['id#'] = $ids;
                } else {
                    $data['id#'] = "(0)";
                }
            }
        }

        $info = Util::getNumData('*', PDO_NAME . 'housekeep_artificer', $data, 'sort DESC,ID DESC', $pindex, $psize, 1);
        $list = $info[0];
        $pager = $info[1];

        foreach($list as &$st){
            $st['memberinfo'] = pdo_get('wlmerchant_member',array('id' => $st['mid']),array('nickname','id','avatar'));
            $st['levelarray'] = pdo_fetchall("SELECT b.title FROM ".tablename('wlmerchant_housekeep_relation')." a LEFT JOIN".tablename('wlmerchant_housekeep_type')." b ON a.twolevelid = b.id WHERE a.objid = {$st['id']} AND a.type = 2 ORDER BY b.sort DESC,b.id DESC");
            if(p('attestation')){ //认证查询
                $st['attestation'] = Attestation::checkAttestation(1,$st['mid']);
            }
        }

        include wl_template('keepweb/artificerList');
    }

    public function editArtificer() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if($id > 0){
            $artificer = pdo_get('wlmerchant_housekeep_artificer',array('id' => $id));
            $artificer['thumbs'] = unserialize($artificer['thumbs']);
            $artificer['casethumbs'] = unserialize($artificer['casethumbs']);
            $levelids = pdo_getall('wlmerchant_housekeep_relation', array('objid' => $id,'type' => 2), 'twolevelid');
            if (!empty($levelids)) {
                $cateids = array_column($levelids, 'twolevelid');
            }
        }

        //获取类目
        $categoryes = Housekeep::getCategory();

        if ($_W['ispost']) {
            $artificer = $_GPC['artificer'];
            $artificer['mid'] = $_GPC['aMid'];
            //校验是否已绑定
            if($artificer['mid'] > 0){
                $flagid = pdo_getcolumn('wlmerchant_housekeep_artificer',array('mid' => $artificer['mid']),'id');
                if($flagid > 0 && $flagid != $id){
                    wl_message('此用户已绑定了服务者,无法重复绑定');
                }
            }
            $artificer['thumbs'] = serialize($artificer['thumbs']);
            $artificer['casethumbs'] = serialize($artificer['casethumbs']);
            $artificer['detail'] = htmlspecialchars_decode($artificer['detail']);
            $category = $_GPC['category'];
            if(empty($artificer['name'])){
                wl_message('请输入服务者姓名');
            }
//            if(empty($artificer['mid'])){
//                wl_message('请设置绑定用户');
//            }
            if(empty($artificer['mobile'])){
                wl_message('请设置联系方式');
            }
            if(empty($artificer['thumb'])){
                wl_message('请设置头像照片');
            }
            if(empty($category)){
                wl_message('请选择服务类目');
            }
            if(empty($artificer['address']) || empty($artificer['lat']) || empty($artificer['lng'])){
                wl_message('请设置联系地址');
            }

            if($id > 0){
                $res = pdo_update('wlmerchant_housekeep_artificer',$artificer,array('id' => $id));
            }else{
                $artificer['uniacid'] = $_W['uniacid'];
                $artificer['aid'] = $_W['aid'];
                $res = pdo_insert('wlmerchant_housekeep_artificer',$artificer);
                $id = pdo_insertid();
            }
            //处理分类
            pdo_delete('wlmerchant_housekeep_relation', array('type' => 2,'objid' => $id));
            foreach ($category as $item) {
                $scate = pdo_get(PDO_NAME . 'housekeep_type', array('id' => $item), array('onelevelid'));
                $res = pdo_insert('wlmerchant_housekeep_relation', ['type' => 2,'objid' => $id, 'onelevelid' => $scate['onelevelid'], 'twolevelid' => $item]);
            }
            if($res){
                wl_message('信息编辑成功',web_url('housekeep/KeepWeb/artificerList'), 'success');
            }else{
                wl_message('信息保存失败，请重试');
            }
        }


        include wl_template('keepweb/editArtificer');
    }

    public function changeArtificer(){
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $status = $_GPC['status'];
        if($status == 7){
            $res = pdo_delete('wlmerchant_housekeep_artificer',array('id'=>$id));;
        }else if($status == 6){
            $res = pdo_update('wlmerchant_housekeep_artificer',array('status' => $status,'reason' => trim($_GPC['reason'])),array('id' => $id));
            if($res){
                $mid = pdo_getcolumn('wlmerchant_housekeep_artificer',array('id'=>$id),'mid');
                $first = '您申请的家政服务者入驻已被审核人员驳回';
                $type = '家政服务';
                $content = '服务人员入驻';
                $status = '被驳回';
                $remark = '驳回原因:'.trim($_GPC['reason']).';点击重新编辑入驻信息。';
                $url = '';
                News::jobNotice($mid,$first,$type,$content,$status,$remark,time(),$url);
            }
        }else{
            $res = pdo_update('wlmerchant_housekeep_artificer',array('status' => $status),array('id' => $id));
            if($res && $_GPC['nflag'] > 0){
                $mid = pdo_getcolumn('wlmerchant_housekeep_artificer',array('id'=>$id),'mid');
                $first = '您申请的家政服务者入驻已经通过审核';
                $type = '家政服务';
                $content = '服务人员入驻';
                $status = '已审核';
                $remark = '点击进入个人服务中心';
                $url = '';
                News::jobNotice($mid,$first,$type,$content,$status,$remark,time(),$url);
            }
        }
        if ($res) {
            show_json(1, '操作成功');
        } else {
            show_json(0, '操作失败，请重试');
        }
    }

    public function changeService(){
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $status = $_GPC['status'];
        if($status == 7){
            $res = pdo_delete('wlmerchant_housekeep_service',array('id'=>$id));;
        }else if($status == 6){
            $res = pdo_update('wlmerchant_housekeep_service',array('status' => $status,'reason' => trim($_GPC['reason'])),array('id' => $id));
            if($res){
                $info = pdo_get('wlmerchant_housekeep_service',array('id'=>$id),array('objid','type'));
                if($info['type'] == 2){
                    $mid = pdo_getcolumn('wlmerchant_housekeep_artificer',array('id'=>$info['objid']),'mid');
                    $first = '您发布的服务项目已被审核人员驳回';
                    $type = '家政服务';
                    $content = '服务项目发布';
                    $status = '被驳回';
                    $remark = '驳回原因:'.trim($_GPC['reason']).';点击重新编辑服务信息。';
                    $url = '';
                    News::jobNotice($mid,$first,$type,$content,$status,$remark,time(),$url);
                }else if($info['type'] == 1){
                    $first = '您所属商户发布的服务项目已被审核人员驳回';
                    $type = '家政服务';
                    $content = '服务项目发布';
                    $status = '被驳回';
                    $remark = '驳回原因:'.trim($_GPC['reason']).';请重新编辑发布服务项目。';
                    $url = '';
                    News::noticeShopAdmin($info['objid'],$first,$type,$content,$status,$remark,time(),$url);
                }
            }
        }else{
            $res = pdo_update('wlmerchant_housekeep_service',array('status' => $status),array('id' => $id));
            if($res && $_GPC['nflag'] > 0){
                $info = pdo_get('wlmerchant_housekeep_service',array('id'=>$id),array('objid','type'));
                if($info['type'] == 2){
                    $mid = pdo_getcolumn('wlmerchant_housekeep_artificer',array('id'=>$info['objid']),'mid');
                    $first = '您发布的服务项目已通过审核';
                    $type = '家政服务';
                    $content = '服务项目发布';
                    $status = '已审核';
                    $remark = '点击查看服务项目详情';
                    $url = '';
                    News::jobNotice($mid,$first,$type,$content,$status,$remark,time(),$url);
                }else if($info['type'] == 2){
                    $first = '您所属商户发布的服务项目已通过审核';
                    $type = '家政服务';
                    $content = '服务项目发布';
                    $status = '已审核';
                    $remark = '点击查看服务项目详情';
                    $url = '';
                    News::noticeShopAdmin($info['objid'],$first,$type,$content,$status,$remark,time(),$url);
                }
            }
        }
        if ($res) {
            show_json(1, '操作成功');
        } else {
            show_json(0, '操作失败，请重试');
        }
    }

    public function allchangestatus(){
        global $_W, $_GPC;
        $ids = $_GPC['ids'];
        $status = $_GPC['status'];
        foreach($ids as $v){
            if($status == 4){
                $astatus = pdo_getcolumn('wlmerchant_housekeep_artificer',array('id'=>$v),'status');
                if($astatus == 4){
                    $res = pdo_delete('wlmerchant_housekeep_artificer',array('id'=>$v));;
                }else{
                    pdo_update('wlmerchant_housekeep_artificer',array('status' => 4),array('id' => $v));
                }
            }else{
                pdo_update('wlmerchant_housekeep_artificer',array('status' => $status),array('id' => $v));
            }
        }
        show_json(1, '操作成功');
    }

    public function allChangeSerStatus(){
        global $_W, $_GPC;
        $ids = $_GPC['ids'];
        $status = $_GPC['status'];
        foreach($ids as $v){
            if($status == 4){
                $astatus = pdo_getcolumn('wlmerchant_housekeep_service',array('id'=>$v),'status');
                if($astatus == 4){
                    $res = pdo_delete('wlmerchant_housekeep_service',array('id'=>$v));;
                }else{
                    pdo_update('wlmerchant_housekeep_service',array('status' => 4),array('id' => $v));
                }
            }else{
                pdo_update('wlmerchant_housekeep_service',array('status' => $status),array('id' => $v));
            }
        }
        show_json(1, '操作成功');
    }

    public function basicsetting(){
        global $_W, $_GPC;
        $data = Setting::agentsetting_read('housekeep');
        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
        $viparray = unserialize($data['viparray']);
        $refarray = unserialize($data['refarray']);
        $data['attestationRight'] = unserialize($data['attestationRight']);
        $data['halfRight'] = unserialize($data['halfRight']);

        $data['topprice'] = unserialize($data['topprice']);

        if ($_W['ispost']) {
            $data = $_GPC['data'];
            $vipleid = $_GPC['vipleid'];
            $data['refhour'] = sprintf("%.0f",$data['refhour']);
            $data['topnumber'] = sprintf("%.0f",$data['topnumber']);
            $data['attestationRight'] = serialize($data['attestationRight']);
            $data['halfRight'] = serialize($data['halfRight']);

            if($data['paystatus'] > 0){ //付费发布需求
                $vipprice = $_GPC['vipprice'];
                $viparray = [];
                foreach($vipleid as $key => $vle){
                    $vipa = sprintf("%.2f",$vipprice[$key]);
                    $viparray[$vle] = $vipa;
                }
                $data['viparray'] = serialize($viparray);
            }
            if($data['paytopstatus'] > 0){ //付费置顶
                $day = $_GPC['day'];
                $topprice = $_GPC['topprice'];
                $topvipprice = $_GPC['topvipprice'];
                $paramids = array();
                $len = count($day);
                for ($k = 0; $k < $len; $k++) {
                    if(empty($day[$k])){
                        wl_message('置顶天数不能为0');
                    }
                    if($day[$k]>0){
                        if(empty($topprice[$k]) || empty($topvipprice[$k])){
                            wl_message('置顶金额不能为0');
                        }
                        $paramids[$k]['day'] = sprintf("%.0f",$day[$k]);
                        $paramids[$k]['topprice'] = sprintf("%.2f",$topprice[$k]);
                        $paramids[$k]['topvipprice'] = sprintf("%.2f",$topvipprice[$k]);
                    }
                }
                $data['topprice'] = serialize($paramids);
            }
            if($data['payrefstatus'] > 0){
                $refprice = $_GPC['refprice'];
                $refarray = [];
                foreach($vipleid as $key => $vle){
                    $refa = sprintf("%.2f",$refprice[$key]);
                    $refarray[$vle] = $refa;
                }
                $data['refarray'] = serialize($refarray);
            }

            Setting::agentsetting_save($data, 'housekeep');
            wl_message('设置成功', web_url('housekeep/KeepWeb/basicsetting'));
        }

        include wl_template('keepweb/basicsetting');

    }

    public function demandList(){
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $data = array();
        $data['uniacid'] = $_W['uniacid'];
        $data['aid'] = $_W['aid'];

        if ($_GPC['status'] == 3) {
            $data['status'] = 0;
        } else if (!empty($_GPC['status'])) {
            $data['status'] = intval($_GPC['status']);
        }

        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 2:
                        $data['@mid@'] = $_GPC['keyword'];
                        break;
                    default:
                        break;
                }
            }
            if ($_GPC['keywordtype'] == 1) {
                $keyword = $_GPC['keyword'];
                $params[':nickname'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname", $params);
                if ($members) {
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if ($key == 0) {
                            $mids .= $v['id'];
                        } else {
                            $mids .= "," . $v['id'];
                        }
                    }
                    $mids .= ")";
                    $data['mid#'] = $mids;
                } else {
                    $data['mid#'] = "(0)";
                }
            }else if ($_GPC['keywordtype'] == 3) {
                $keyword = $_GPC['keyword'];
                $params[':title'] = "%{$keyword}%";
                $types = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_housekeep_type') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND title LIKE :title", $params);
                if ($types) {
                    $typeids = "(";
                    foreach ($types as $key => $v) {
                        if ($key == 0) {
                            $typeids .= $v['id'];
                        } else {
                            $typeids .= "," . $v['id'];
                        }
                    }
                    $typeids .= ")";
                    $data['type#'] = $typeids;
                } else {
                    $data['type#'] = "(0)";
                }
            }
        }
        //时间
        if (!empty($_GPC['time_limit']) && $_GPC['timetype'] ) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            if($_GPC['timetype'] == 1){
                $data['visitingtime>'] = $starttime;
                $data['visitingtime<'] = $endtime;
            }else{
                $data['createtime>'] = $starttime;
                $data['createtime<'] = $endtime;
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $info = Util::getNumData('*', PDO_NAME . 'housekeep_demand', $data, 'createtime DESC', $pindex, $psize, 1);
        $list = $info[0];
        $pager = $info[1];
        foreach($list as &$st){
            $member = pdo_get('wlmerchant_member',array('id' => $st['mid']),array('nickname','mobile','avatar'));
            $st['nickname'] = $member['nickname'];
            $st['avatar'] = $member['avatar'];
            $st['mobile'] = $member['mobile'];
            $st['typetitle'] = pdo_getcolumn(PDO_NAME.'housekeep_type',array('id'=>$st['type']),'title');
        }


        include wl_template('keepweb/demandList');
    }

    public function editDemand(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        if(empty($id)){
            wl_message('缺少必要参数:id',web_url('housekeep/KeepWeb/demandList'), 'error');
        }
        $demand = pdo_get('wlmerchant_housekeep_demand',array('id' => $id));
        if(!empty($demand['thumbs'])){
            $demand['thumbs'] = unserialize($demand['thumbs']);
        }
        $demand['nickname'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$demand['mid']),'nickname');
        //获取类目
        $categoryes = Housekeep::getCategory();

        if ($_W['ispost']) {
            $demand = $_GPC['demand'];
            $demand['onetype'] = pdo_getcolumn(PDO_NAME.'housekeep_type',array('id'=>$demand['type']),'onelevelid');
            if(!empty($demand['thumbs'])){
                $demand['thumbs'] = serialize($demand['thumbs']);
            }
            $demand['visitingtime'] = strtotime($demand['visitingtime']);
            $res = pdo_update('wlmerchant_housekeep_demand',$demand,array('id' => $id));
            if($res){
                wl_message('需求编辑成功',web_url('housekeep/KeepWeb/demandList'), 'success');
            }else{
                wl_message('需求保存失败，请重试');
            }
        }
        include wl_template('keepweb/editDemand');
    }

    public function changeDemand(){
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $status = $_GPC['status'];
        if($status == 7){
            $res = pdo_delete('wlmerchant_housekeep_demand',array('id'=>$id));;
        }else if($status == 6){
            $res = pdo_update('wlmerchant_housekeep_demand',array('status' => $status,'reason' => trim($_GPC['reason'])),array('id' => $id));
            if($res){
                $mid = pdo_getcolumn('wlmerchant_housekeep_demand',array('id'=>$id),'mid');
                $first = '您发布的一个家政服务需求已被审核人员驳回';
                $type = '家政服务';
                $content = '需求发布';
                $status = '被驳回';
                $remark = '驳回原因:'.trim($_GPC['reason']).';点击重新编辑需求。';
                $url = '';
                News::jobNotice($mid,$first,$type,$content,$status,$remark,time(),$url);
            }
        }else{
            $res = pdo_update('wlmerchant_housekeep_demand',array('status' => $status),array('id' => $id));
            if($res && $_GPC['nflag'] > 0){
                $mid = pdo_getcolumn('wlmerchant_housekeep_demand',array('id'=>$id),'mid');
                $first = '您发布的一个家政服务需求已经通过审核';
                $type = '家政服务';
                $content = '需求发布';
                $status = '已审核';
                $remark = '点击查看需求详情';
                $url = '';
                News::jobNotice($mid,$first,$type,$content,$status,$remark,time(),$url);
            }
        }
        if ($res) {
            show_json(1, '操作成功');
        } else {
            show_json(0, '操作失败，请重试');
        }
    }

    public function allchangeDemstatus(){
        global $_W, $_GPC;
        $ids = $_GPC['ids'];
        $status = $_GPC['status'];
        foreach($ids as $v){
            if($status == 4){
                $astatus = pdo_getcolumn('wlmerchant_housekeep_demand',array('id'=>$v),'status');
                if($astatus == 4){
                    $res = pdo_delete('wlmerchant_housekeep_demand',array('id'=>$v));;
                }else{
                    pdo_update('wlmerchant_housekeep_demand',array('status' => 4),array('id' => $v));
                }
            }else{
                pdo_update('wlmerchant_housekeep_demand',array('status' => $status),array('id' => $v));
            }
        }
        show_json(1, '操作成功');
    }

    public function storelist(){
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $data = array();
        $data['uniacid'] = $_W['uniacid'];
        $data['aid'] = $_W['aid'];
        $data['housekeepstatus'] = 1;

        if (!empty($_GPC['keyword'])){
            if ($_GPC['keywordtype'] == 1) {
                $data['@storename@'] = $_GPC['keyword'];
            }
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 1:
                        $data['@storename@'] = $_GPC['keyword'];
                        break;
                    case 2:
                        $data['@mobile@'] = $_GPC['keyword'];
                        break;
                    case 3:
                        $data['@realname@'] = $_GPC['keyword'];
                        break;
                    case 4:
                        $data['@tel@'] = $_GPC['keyword'];
                        break;
                    default:
                        break;
                }
            }
            if($_GPC['keywordtype'] == 5){
                $keyword = $_GPC['keyword'];
                $catearray = pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_housekeep_type')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND onelevelid > 0 AND title LIKE '%{$keyword}%' ORDER BY id DESC");
                $cateids = array_column($catearray, 'id');
                $objids = pdo_getall('wlmerchant_housekeep_relation',array('twolevelid' => $cateids,'type'=>3),array('objid'));
                $objids = array_column($objids, 'objid');
                $objids = array_unique($objids);
                if ($objids) {
                    $ids = "(";
                    foreach ($objids as $key => $v) {
                        if ($key == 0) {
                            $ids .= $v;
                        } else {
                            $ids .= "," . $v;
                        }
                    }
                    $ids .= ")";
                    $data['id#'] = $ids;
                } else {
                    $data['id#'] = "(0)";
                }
            }
        }

        $info = Util::getNumData('id,logo,storename,mobile,realname,tel,createtime,endtime,enabled,nowmoney,groupid,housekeepstatus', PDO_NAME . 'merchantdata', $data, 'listorder desc,id desc', $pindex, $psize, 1);
        $list = $info[0];
        $pager = $info[1];

        foreach ($list as $key => &$value) {
            //查询店员
            $value['groupname'] = $value['groupid'] ? pdo_getcolumn('wlmerchant_chargelist', array('id' => $value['groupid']), 'name') : '';
            //服务泪目数据
            $value['levelarray'] = pdo_fetchall("SELECT b.title FROM ".tablename('wlmerchant_housekeep_relation')." a LEFT JOIN".tablename('wlmerchant_housekeep_type')." b ON a.twolevelid = b.id WHERE a.objid = {$value['id']} AND a.type = 3 ORDER BY b.sort DESC,b.id DESC");
            if(p('attestation')){ //认证查询
                $value['attestation'] = Attestation::checkAttestation(2,$value['id']);
            }

        }
        include wl_template('keepweb/storelist');
    }

    public function dayandprice(){
        include wl_template('keepweb/dayandprice');
    }

    public function closeStore(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $res = pdo_update('wlmerchant_merchantdata',array('housekeepstatus' => 0),array('id' => $id));
        if ($res) {
            show_json(1, '操作成功');
        } else {
            show_json(0, '操作失败，请重试');
        }
    }

}
