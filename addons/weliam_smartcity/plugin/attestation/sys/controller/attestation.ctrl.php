<?php
defined('IN_IA') or exit('Access Denied');

class Attestation_WeliamController {

    public function attestationList() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $data = array();
        $data['uniacid'] = $_W['uniacid'];
        if($_W['aid']  > 0) {
            $data['aid'] = $_W['aid'];
            $data['type'] = 2;//代理商只能查看商户认证信息
        }else if(!empty($_GPC['type'])){
            $data['type'] = $_GPC['type'];
        }
        //状态
        if (!empty($_GPC['status'])) {
            if ($_GPC['status'] == 1) {
                $data['status'] = 1;
            } else if ($_GPC['status'] == 2) {
                $data['status'] = 0;
            }
        }
        //审核状态
        if (!empty($_GPC['checkstatus'])) {
            $data['checkstatus'] = intval($_GPC['checkstatus']);
        }
        //搜索
        if (!empty($_GPC['keyword'])) {
            $keyword = $_GPC['keyword'];
            if ($_GPC['keywordtype'] == 1) {
                $params[':nickname'] = "%{$keyword}%";
                $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname", $params);
                if ($merchants) {
                    $sids = "(";
                    foreach ($merchants as $key => $v) {
                        if ($key == 0) {
                            $sids .= $v['id'];
                        } else {
                            $sids .= "," . $v['id'];
                        }
                    }
                    $sids .= ")";
                    $data['mid#'] = $sids;
                } else {
                    $data['mid#'] = "(0)";
                }
            } else if ($_GPC['keywordtype'] == 2) {
                $data['mid@'] = intval($keyword);
            }
        }
        //时间
        if ($_GPC['time_limit'] && $_GPC['timetype']) {
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $data['createtime>'] = $starttime;
            $data['createtime<'] = $endtime + 86399;
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        if($_GPC['export']){
            $this -> exportList($data);
        }

        $list = Util::getNumData('*', PDO_NAME . 'attestation_list', $data, 'ID DESC', $pindex, $psize, 1);
        $pager = $list[1];
        $list = $list[0];
        if (!empty($list)) {
            foreach ($list as &$li) {
                $li['pic'] = unserialize($li['pic']);
                $member = pdo_get('wlmerchant_member', array('id' => $li['mid']), array('nickname', 'realname', 'mobile', 'avatar'));
                $li['nickname'] = $member['nickname'];
                $li['avatar'] = $member['avatar'];
                $li['mobile'] = $member['mobile'];
                if ($li['type'] == 1) {
                    $li['attestationname'] = $member['realname'];
                } else {
                    $storeInfo = pdo_get(PDO_NAME . 'merchantdata' , ['id' => $li['storeid']] , ['storename','tel']);
                    $li['attestationname'] = $storeInfo['storename'];
                    $li['mobile'] = $storeInfo['tel'];
                    $li['moreinfo'] = unserialize($li['moreinfo']);
                }
            }
        }

        include wl_template('attestation/attestationList');
    }

    public function changeStatus() {
        global $_W, $_GPC;
        $id = $_GPC['id'] OR Commons::sRenderError('缺少参数,请刷新重试');
        $status = $_GPC['status'] ?: 0;
        $res = pdo_update(PDO_NAME . "attestation_list", ['status' => $status], ['id' => $id]);
        if ($res) Commons::sRenderSuccess('修改成功');
        else Commons::sRenderError('修改失败，请刷新重试!');
    }

    public function rejectreason(){
        global $_W, $_GPC;
        $id = $_GPC['id'] OR Commons::sRenderError('缺少id,请刷新重试');
        $reason = $_GPC['reason'] OR Commons::sRenderError('请输入驳回原因');
        $res = pdo_update(PDO_NAME . "attestation_list", ['checkstatus' => 3,'remake'=>$reason], ['id' => $id]);
        $att = pdo_get(PDO_NAME . "attestation_list",array('id' => $id),array('type','subjectname','mid'));
        if($att['type'] == 1){
            $content = '用户['.$att['subjectname'].']认证';
            $url = h5_url('pages/subPages/attestationCenter/index',array('rzType'=>1));
        }else{
            $content = '商户['.$att['subjectname'].']认证';
            $url = h5_url('pages/subPages/attestationCenter/index',array('rzType'=>2));
        }
        if ($res){
            //通知用户
            $first = '您的认证审核已被驳回';
            $type = '认证申请';
            $status = '被驳回';
            $remark = '驳回原因:'.$reason;
            News::jobNotice($att['mid'],$first,$type,$content,$status,$remark,time(),$url);
            Commons::sRenderSuccess('驳回成功');
        } else{
            Commons::sRenderError('驳回失败，请刷新重试!');
        }
    }

    public function changeCheckStatus() {
        global $_W, $_GPC;
        $id = $_GPC['id'] OR Commons::sRenderError('缺少参数,请刷新重试');
        $status = $_GPC['status'];
        $res = pdo_update(PDO_NAME . "attestation_list", ['checkstatus' => $status], ['id' => $id]);
        if ($res){
            if($status == 2){  //通过审核
                $att = pdo_get(PDO_NAME . "attestation_list",array('id' => $id),array('type','subjectname','mid'));
                if($att['type'] == 1){
                    $content = '用户['.$att['subjectname'].']认证';
                    $url = h5_url('pages/subPages/attestationCenter/index',array('rzType'=>1));
                }else{
                    $content = '商户['.$att['subjectname'].']认证';
                    $url = h5_url('pages/subPages/attestationCenter/index',array('rzType'=>2));
                }
            }
            //通知用户
            $first = '您的认证审核已通过';
            $type = '认证申请';
            $status = '已通过';
            $remark = '点击查看认证信息';
            News::jobNotice($att['mid'],$first,$type,$content,$status,$remark,time(),$url);
            show_json(1, '修改成功');
        }else{
            show_json(0, '修改失败');
        }
    }

    function allChange() {
        global $_W, $_GPC;
        $ids = $_GPC['ids'];
        $status = $_GPC['status'];
        foreach ($ids as $key => $id) {
            if ($status != 4) {
                pdo_update(PDO_NAME . "attestation_list", array('checkstatus' => $status), array('id' => $id));
            } else {
                pdo_delete(PDO_NAME . "attestation_list", array('id' => $id));
            }
        }
        die(json_encode(array('errno' => 0, 'message' => '')));
    }

    function attestationSet() {
        global $_W, $_GPC;
        $base = Setting::wlsetting_read('attestation');
        $base['type'] = unserialize($base['type']);
        $diyform = pdo_getall('wlmerchant_diyform',array('uniacid' => $_W['uniacid'],'aid' => 0,'sid' => 0),array('id','title'));
        if(Customized::init('distributionText') > 0){
            $membermoney = unserialize($base['membermoneyarray']);
            $storemoney = unserialize($base['storemoneyarray']);
        }

        if ($_W['ispost']) {
            $base = $_GPC['base'];
            $base['type'] = serialize($_GPC['type']);
            $base['agreement'] = htmlspecialchars_decode($base['agreement']);
            $base['bonddescription'] = htmlspecialchars_decode($base['bonddescription']);
            $base['bondagreement'] = htmlspecialchars_decode($base['bondagreement']);
            if(Customized::init('distributionText') > 0){
                $membermoney = $_GPC['membermoney'];
                if(empty($membermoney) && $base['memberstatus'] > 0){
                    show_json(0, '请设置个人保证金');
                }
                foreach ($membermoney as &$memm){
                    $memm = sprintf("%.2f",$memm);
                    $memm = $memm > 0 ? $memm : 1;
                }
                sort($membermoney);
                $base['membermoneyarray'] = serialize($membermoney);

                $storemoney = $_GPC['storemoney'];
                if(empty($storemoney) && $base['storestatus'] > 0){
                    show_json(0, '请设置商家保证金');
                }
                foreach ($storemoney as &$semm){
                    $semm = sprintf("%.2f",$semm);
                    $semm = $semm > 0 ? $semm : 1;
                }
                sort($storemoney);
                $base['storemoneyarray'] = serialize($storemoney);
            }


            $res = Setting::wlsetting_save($base, 'attestation');
            if ($res) {
                show_json(1);
            } else {
                show_json(0, '保存失败，请刷新重试');
            }
        }
        include wl_template('attestation/attestationSet');
    }

    function bondList() {
        global $_W, $_GPC;
        $base = Setting::wlsetting_read('attestation');
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $data = array();
        $data['uniacid'] = $_W['uniacid'];

        //状态
        if (!empty($_GPC['status'])) {
            if ($_GPC['status'] == 1) {
                $data['status'] = 1;
            } else if ($_GPC['status'] == 2) {
                $data['status'] = 0;
            }
        }
        //搜索
        if (!empty($_GPC['keyword'])) {
            $keyword = $_GPC['keyword'];
            if ($_GPC['keywordtype'] == 1) {
                $params[':nickname'] = "%{$keyword}%";
                $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND nickname LIKE :nickname", $params);
                if ($merchants) {
                    $sids = "(";
                    foreach ($merchants as $key => $v) {
                        if ($key == 0) {
                            $sids .= $v['id'];
                        } else {
                            $sids .= "," . $v['id'];
                        }
                    }
                    $sids .= ")";
                    $data['mid#'] = $sids;
                } else {
                    $data['mid#'] = "(0)";
                }
            } else if ($_GPC['keywordtype'] == 2) {
                $data['mid@'] = intval($keyword);
            }
        }
        //时间
        if ($_GPC['time_limit'] && $_GPC['timetype']) {
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $data['createtime>'] = $starttime;
            $data['createtime<'] = $endtime + 86399;
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $list = Util::getNumData('*', PDO_NAME . 'attestation_money', $data, 'ID DESC', $pindex, $psize, 1);
        $pager = $list[1];
        $list = $list[0];
        if (!empty($list)) {
            foreach ($list as &$li) {
                $li['pic'] = unserialize($li['pic']);
                $member = pdo_get('wlmerchant_member', array('id' => $li['mid']), array('nickname', 'realname', 'mobile', 'avatar'));
                $li['nickname'] = $member['nickname'];
                $li['avatar'] = $member['avatar'];
                $li['mobile'] = $member['mobile'];
                if ($li['type'] == 1) {
                    $li['attestationname'] = $li['realname'];
                } else {
                    $li['attestationname'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $li['storeid']), 'storename');
                }
                if ($base['refundstatus'] && $li['status'] == 1 && empty($li['refundflag'])) {
                    $day = intval($base['refundday']) ? intval($base['refundday']) : 0;
                    if ($li['paytime'] + $day * 86400 < time()) {
                        $li['refund'] = 1;
                    }
                }
            }
        }

        include wl_template('attestation/bondList');
    }

    function refundorder() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $order = pdo_get('wlmerchant_attestation_money', array('id' => $id));
        $res = wlPay::refundMoney($id, 0, '认证保证金退款', 'attestation', 2);
        if ($res['status']) {
            $first = '您的保证金已经退还';
            $type = '保证金退还';
            $content = '认证保证金已退款：' . $order['money'] . '元';
            $status = '已退还';
            $remark = '有问题请联系管理员';
            $time = time();
            News::jobNotice($order['mid'], $first, $type, $content, $status, $remark, $time);
            pdo_update('wlmerchant_attestation_money', array('refundflag' => 1), array('id' => $id));
            show_json(1);
        } else {
            show_json(0, '退款失败：' . $res['message']);
        }

    }

    function deleteorder(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $res = pdo_delete('wlmerchant_attestation_money',array('id'=>$id));
        if($res){
            show_json(1);
        }else{
            show_json(0, '操作失败，请刷新重试');
        }
    }

    function moreinfo(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $attest = pdo_get(PDO_NAME.'attestation_list',array('id'=>$id),['type','moreinfo','pic']);
        $cardpic = unserialize($attest['pic']);
        if(!empty($cardpic)){
            foreach($cardpic as &$cpi){
                $cpi = tomedia($cpi);
            }
        }
        $info = unserialize($attest['moreinfo']);
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
        include wl_template('attestation/moreinfo');
    }

    function moInfoEdit(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        //认证信息
        $attest = pdo_get(PDO_NAME.'attestation_list',array('id'=>$id),['type','subjectname','atttel','cardnum','moreinfo','pic']);
        $cardpic = unserialize($attest['pic']);
        $moreinfo = unserialize($attest['moreinfo']);
        //查询自定义表单
        $set = Setting::wlsetting_read('attestation');
        if($attest['type'] == 1){
            $diyformid = $set['personmoreformid'];
        }else{
            $diyformid = $set['storemoreformid'];
        }
        $diyforminfo = pdo_get('wlmerchant_diyform',array('id' => $diyformid),array('info'));
        $moinfo = json_decode(base64_decode($diyforminfo['info']) , true);
        $list = $moinfo['list'];
        $list = array_values($list);
        $newinfo = [];
        foreach ($moreinfo as $mminfo){
            $newinfo[$mminfo['title']] = $mminfo;
        }

        foreach ($list as &$lis){
            if(empty($newinfo[$lis['data']['title']]['key'])){
                $moreinfo[] = [
                    'id' =>   $lis['id'],
                    'key' =>  $lis['key'],
                    'data' => '',
                    'title' => $lis['data']['title'],
                    'att_show' => $lis['data']['att_show']
                ];

                if($lis['id'] == 'city'){
                    $cityflag = 1;
                    $city_name = $lis['val'][1];
                    $area_name = $lis['val'][2];
                }
                $lis['keyinfo'] = 'newmoreinfo['.$lis['key'].']';

            }else{
                $lis['val'] = $newinfo[$lis['data']['title']]['data'];
                $lis['key'] = $newinfo[$lis['data']['title']]['key'];
                if($lis['id'] == 'city'){
                    $cityflag = 1;
                    $city_name = $lis['val'][1];
                    $area_name = $lis['val'][2];
                }
                $lis['keyinfo'] = 'newmoreinfo['.$lis['key'].']';
            }
        }

        //查询地区
        if($cityflag > 0 ){
            $AreaTab = tablename(PDO_NAME . "area");
            $orderBy = " ORDER BY id ASC ";
            $province = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 1 " . $orderBy);

            $province_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 2 AND name = '{$city_name}'");
            $city = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 2 AND pid = {$province_id}" . $orderBy);

            $city_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 3 AND name = '{$area_name}'");
            $district = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 3 AND pid = {$city_id}" . $orderBy);
        }
        //提交
        if ($_W['ispost']) {
            $data = $_GPC['data'];
            $data['pic'] = serialize($data['pic']);
            $newmoreinfo = $_GPC['newmoreinfo'];

            $subnewinfo = [];
            foreach ($moreinfo as $mminfo){
                $subnewinfo[$mminfo['key']] = $mminfo;
            }


            foreach ($subnewinfo as $subkey => &$new){
                if($new['id'] == 'datetime'){
                    $new['data'] = [];
                    $new['data'][0] = $newmoreinfo[$subkey]['start'];
                    $new['data'][1] = $newmoreinfo[$subkey]['end'];
                }else if($new['id'] == 'city'){
                    $new['data'] = [];
                    $new['data'][0] = pdo_getcolumn(PDO_NAME.'area',array('id'=>$newmoreinfo[$subkey]['provinceid']),'name');
                    $new['data'][1] = pdo_getcolumn(PDO_NAME.'area',array('id'=>$newmoreinfo[$subkey]['areaid']),'name');
                    $new['data'][2] = pdo_getcolumn(PDO_NAME.'area',array('id'=>$newmoreinfo[$subkey]['distid']),'name');
                }else{
                    $new['data'] = $newmoreinfo[$subkey];
                }
                if(empty($new['data'])){
                    unset($subnewinfo[$subkey]);
                }
            }



            $subnewinfo = array_values($subnewinfo);
            $subnewinfo = serialize($subnewinfo);
            $data['moreinfo'] = $subnewinfo;

            $res = pdo_update('wlmerchant_attestation_list',$data,array('id' => $id));
            if($res){
                wl_message('保存成功！',web_url('attestation/attestation/attestationList'),'success');
            }else{
                wl_message('保存失败，请刷新重试');
            }
        }


        include wl_template('attestation/moInfoEdit');
    }

    /**
     * Comment: 认证记录导出
     * Author: wlf
     * Date: 2022/04/20 17:43
     */
    public function exportList($where){
        global $_W,$_GPC;
        $list = Util::getNumData('*', PDO_NAME . 'attestation_list', $where, 'ID DESC', 0, 0, 1);
        $base = Setting::wlsetting_read('attestation');
        if($where['type'] == 1){
            $diyformid = $base['personmoreformid'];
        }else if($where['type'] == 2){
            $diyformid = $base['storemoreformid'];
        }


        $listdata = $list[0];
        foreach ($listdata as &$li) {
            $member = pdo_get('wlmerchant_member', array('id' => $li['mid']), array('nickname', 'realname', 'mobile'));
            $li['nickname'] = $member['nickname'];
            $li['mobile'] = $li['atttel'] ? : $member['mobile'];
            if ($li['type'] == 1) {
                $li['attestationname'] = $member['realname'];
            } else {
                $storeInfo = pdo_get(PDO_NAME . 'merchantdata' , ['id' => $li['storeid']] , ['storename','tel']);
                $li['attestationname'] = $storeInfo['storename'];
                $li['mobile'] = $storeInfo['tel'];
                $li['moreinfo'] = unserialize($li['moreinfo']);
            }
            $li['createtime'] = date('Y-m-d H:i:s',$li['createtime']);
            $li['updatetime'] = date('Y-m-d H:i:s',$li['updatetime']);
            $li['typetext'] = $li['type'] == 1 ? '个人认证' : '商户认证';
            $li['cardnum'] = "\t".$li['cardnum']."\t";
            $li['pic'] = unserialize($li['pic']);
            $li['pic'] = implode(',',$li['pic']);
            $li['statustext'] = $li['status'] ? '开启' : '关闭';
            if($li['checkstatus'] == 1){
                $li['checkstatustext'] = '待审核';
            }else if($li['checkstatus'] == 2){
                $li['checkstatustext'] = '已通过';
            }else{
                $li['checkstatustext'] = '被驳回';
            }

            if($diyformid > 0){
                foreach ($li['moreinfo'] as $zzw => $in){
                    if($in['id'] == 'checkbox' || $in['id'] == 'img'){
                        $li['zzw'.$zzw] = implode(",", $in['data']);
                    }else if($in['id'] == 'datetime' || $in['id'] == 'city'){
                        $li['zzw'.$zzw] = implode("-", $in['data']);
                    } else{
                        $li['zzw'.$zzw] = "\t".$in['data']."\t";
                    }
                }
            }


        }


        $filter = array(
            'id' => '记录id',
            'typetext'   => '认证类型',
            'nickname' => '提交人昵称',
            'mid' => '提交人mid',
            'mobile' => '认证电话',
            'subjectname' => '认证主体名称'
        );

        if(empty($where['type'])){
            $filter['attestationname'] = '商户名/用户实名';
            $filter['cardnum'] = '身份证号/营业执照号';
            $filter['pic'] = '认证素材';
        }else{
            if($where['type'] == 1){
                $filter['attestationname'] = '用户实名';
                $filter['cardnum'] = '身份证号';
                $filter['pic'] = '身份证照片';
            }else{
                $filter['attestationname'] = '商户名';
                $filter['cardnum'] = '营业执照号';
                $filter['pic'] = '营业执照照片';
            }
            if($diyformid > 0){
                $diyforminfo = pdo_get('wlmerchant_diyform',array('id' => $diyformid),array('info'));
                $moinfo = json_decode(base64_decode($diyforminfo['info']) , true);
                $list = $moinfo['list'];
                $list = array_values($list);
                foreach ($list as $wlf => $li){
                    $filter['zzw'.$wlf] = $li['data']['title'];
                }
            }
        }
        $filter['statustext'] = '显示状态';
        $filter['checkstatustext'] = '审核状态';
        $filter['createtime'] = '认证时间';
        $filter['updatetime'] = '最近修改时间';



        $data = array();
        for ($i=0; $i < count($listdata) ; $i++) {
            foreach ($filter as $key => $title) {
                $data[$i][$key] = $listdata[$i][$key];
            }
        }
        util_csv::export_csv_2($data, $filter,'认证记录.csv');
        exit();
    }




}