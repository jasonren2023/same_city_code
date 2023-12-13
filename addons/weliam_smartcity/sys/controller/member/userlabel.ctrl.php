<?php
defined('IN_IA') or exit('Access Denied');

class Userlabel_WeliamController {

    public function labellist() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $where['uniacid'] = $_W['uniacid'];
        if ($_GPC['keyword']) {
            $where['name@'] = trim($_GPC['keyword']);
        }
        $list = Util::getNumData('*', 'wlmerchant_userlabel', $where, 'sort DESC', $pindex, $psize, 1);
        $pager = $list[1];
        $list = $list[0];
        foreach ($list as &$li){
            $li['usernum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_userlabel_record')." WHERE labelid = {$li['id']}");
            $li['timesnum'] = pdo_getcolumn('wlmerchant_userlabel_record',array('labelid' => $li['id']),array("SUM(times)"));
            if(empty($li['timesnum'])){
                $li['timesnum'] = 0;
            }
        }
        include wl_template('member/labellist');
    }

    public function add() {
        global $_W, $_GPC;
        $data['name'] = trim($_GPC['name']);
        $data['sort'] = trim($_GPC['sort']);
        $data['status'] = trim($_GPC['status']);
        $data['uniacid'] = $_W['uniacid'];
        $data['createtime'] = time();
        if (empty($data['name'])) {
            show_json(0, '标签名称为空！');
        }
        $res = pdo_insert('wlmerchant_userlabel', $data);
        if ($res) {
            show_json(1);
        } else {
            show_json(0, '保存失败，请重试');
        }
    }

    public function changelabel() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        $newvalue = trim($_GPC['value']);
        if ($type == 1) {
            $res = pdo_update('wlmerchant_userlabel', array('name' => $newvalue), array('id' => $id));
        } elseif ($type == 2) {
            $res = pdo_update('wlmerchant_userlabel', array('sort' => $newvalue), array('id' => $id));
        } elseif ($type == 3) {
            $res = pdo_update('wlmerchant_userlabel', array('status' => $newvalue), array('id' => $id));
        } else if ($type == 4) {
            $res = pdo_delete('wlmerchant_userlabel', array('id' => $id));
            pdo_delete('wlmerchant_userlabel_record', array('labelid' => $id));
        }
        if ($res) {
            show_json(1, '修改成功');
        } else {
            show_json(0, '修改失败，请重试');
        }
    }




    public function sendModal(){
        global $_W, $_GPC;
        $id = $_GPC['id'];

        if($_W['ispost']){
            $firsttext = $_GPC['headinfo'];
            $remark =  $_GPC['tailinfo'];
            $content = $_GPC['conteninfo'];
            if(empty($firsttext) || empty($remark) || empty($content)){
                wl_message('请完善推送信息');
            }
            if(strstr($_GPC['linkinfo'],'http')){
                $url = trim($_GPC['linkinfo']);
            }else{
                $url = h5_url($_GPC['linkinfo']);
            }
            $source = $_GPC['source'] ? : 1;
            if($source == 1){
                if($id > 0){
                    $sql = "SELECT b.id as mid  FROM ("
                        . tablename(PDO_NAME . "userlabel_record")
                        . " as a LEFT JOIN ".tablename(PDO_NAME."member")
                        . " as b ON a.mid = b.id ) "
                        . " LEFT JOIN ".tablename("mc_mapping_fans")
                        . " as c ON b.openid = c.openid"
                        . " WHERE a.labelid = {$id} AND c.follow = 1";
                }else{
                    $sql = "SELECT a.id as mid   FROM "
                        . tablename(PDO_NAME . "member")
                        . " as a LEFT JOIN ".tablename("mc_mapping_fans")
                        . " as b ON a.openid = b.openid "
                        . " WHERE a.uniacid = {$_W['uniacid']} AND b.follow = 1";
                }
            }else{
                if($id > 0){
                    $sql = "SELECT b.id as mid  FROM "
                        . tablename(PDO_NAME . "userlabel_record")
                        . " as a LEFT JOIN ".tablename(PDO_NAME."member")
                        . " as b ON a.mid = b.id  "
                        . " WHERE a.labelid = {$id} AND b.wechat_openid <> ''";
                }else{
                    $sql = "SELECT id as mid FROM "
                        . tablename(PDO_NAME . "member")
                        . "WHERE uniacid = {$_W['uniacid']} AND wechat_openid <> ''";
                }
            }
            $fannum = count(pdo_fetchall($sql));
            if(empty($fannum)){
                wl_message('没有可推送用户');

            }
            include wl_template('member/sendprocess');
            exit;
        }

        include wl_template('member/sendModal');

    }


    public function senddyning(){
        global $_W,$_GPC;
        $id = intval($_GPC['id']);
        $firsttext = $_GPC['firsttext'];
        $remark =  $_GPC['remark'];
        $content = $_GPC['content'];
        $source = $_GPC['source'] ? : 1;
        $url = $_GPC['url'];
        $psize = 50;
        $request_body = file_get_contents('php://input');
        $data = json_decode($request_body, true);
        $pindex = $data['pindex'];
        $success = $data['success'];

        if($source == 1){
            if($id > 0){
                $sql = "SELECT b.id as mid  FROM ("
                    . tablename(PDO_NAME . "userlabel_record")
                    . " as a LEFT JOIN ".tablename(PDO_NAME."member")
                    . " as b ON a.mid = b.id ) "
                    . " LEFT JOIN ".tablename("mc_mapping_fans")
                    . " as c ON b.openid = c.openid"
                    . " WHERE a.labelid = {$id} AND c.follow = 1 LIMIT ".$pindex * $psize . ',' . $psize;
            }else{
                $sql = "SELECT a.id as mid   FROM "
                    . tablename(PDO_NAME . "member")
                    . " as a LEFT JOIN ".tablename("mc_mapping_fans")
                    . " as b ON a.openid = b.openid "
                    . " WHERE a.uniacid = {$_W['uniacid']} AND b.follow = 1 LIMIT ".$pindex * $psize . ',' . $psize;
            }
        }else{
            if($id > 0){
                $sql = "SELECT b.id as mid  FROM "
                    . tablename(PDO_NAME . "userlabel_record")
                    . " as a LEFT JOIN ".tablename(PDO_NAME."member")
                    . " as b ON a.mid = b.id  "
                    . " WHERE a.labelid = {$id} AND b.wechat_openid <> '' LIMIT ".$pindex * $psize . ',' . $psize;
            }else{
                $sql = "SELECT id as mid FROM "
                    . tablename(PDO_NAME . "member")
                    . "WHERE uniacid = {$_W['uniacid']} AND wechat_openid <> '' LIMIT ".$pindex * $psize . ',' . $psize;
            }
        }
        $fans = pdo_fetchall($sql);
        if($fans){
            foreach($fans as $key => $fan) {
                //模板消息
                News::jobNotice($fan['mid'],$firsttext,'平台通知',$content,'已完成',$remark,time(),$url,$source);
                $success++;
            }
            $return = array('result' => 1,'success' => $success);
        }else{
            $return = array('result' => 3,'success' => $success);
        }
        die(json_encode($return));
    }



}