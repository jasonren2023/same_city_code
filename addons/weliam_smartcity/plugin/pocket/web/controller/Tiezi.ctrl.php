<?php
defined('IN_IA') or exit('Access Denied');

class Tiezi_WeliamController {
    /**
     * Comment: 进入帖子列表
     */
    function lists() {
        global $_W, $_GPC;
        $set = Setting::agentsetting_read('pocket');

        $status0 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_pocket_informations') . " where uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}");
        $status1 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_pocket_informations') . " where uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 1");
        $status3 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_pocket_informations') . " where uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 0");
        $status4 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_pocket_informations') . " where uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 0 AND top > 0");
        $status2 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_pocket_informations') . " where uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 2");
        $status5 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_pocket_informations') . " where uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 3");

        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $where['aid'] = $_W['aid'];
        $status = $_GPC['status'];
        if (empty($status)) {
            $where['status'] = 0;
        } else if ($status == 1) {
            $where['status'] = 1;
        } else if ($status == 2) {
            $where['status'] = 2;
        } else if ($status == 4) {
            $where['status'] = 0;
            $where['top>'] = 1;
        } else if ($status == 3) {
            $where['status'] = 3;
        }
        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 1:
                        $where['@content@'] = $_GPC['keyword'];
                        break;
                    case 3:
                        $where['@nickname@'] = $_GPC['keyword'];
                        break;
                    case 4:
                        $where['@phone@'] = $_GPC['keyword'];
                        break;
                    default:
                        break;
                }
                if ($_GPC['keywordtype'] == 2) {
                    $keyword = $_GPC['keyword'];
                    $params[':title'] = "%{$keyword}%";
                    $goods = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_pocket_type') . "WHERE uniacid = {$_W['uniacid']} AND title LIKE :title", $params);
                    if ($goods) {
                        $goodids = "(";
                        foreach ($goods as $key => $v) {
                            if ($key == 0) {
                                $goodids .= $v['id'];
                            } else {
                                $goodids .= "," . $v['id'];
                            }
                        }
                        $goodids .= ")";
                        $where['type#'] = $goodids;
                    } else {
                        $where['type#'] = "(0)";
                    }
                }
            }
        }
        if ($_GPC['time_limit']) {
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $where['createtime>'] = $starttime;
            $where['createtime<'] = $endtime + 24 * 3600;
        }

        //导出
        if($_GPC['export']){
            $this -> exportTieziList($where);
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $orderlist = Pocket::getNumTiezi('*', $where, 'ID DESC', $pindex, $psize, 1);
        $pager = $orderlist[1];
        $list = $orderlist[0];
        foreach ($list as $key => &$v) {
            $v['content'] = nl2br($v['content']);
            if ($v['type']) {
                $v['logo'] = pdo_getcolumn(PDO_NAME . 'pocket_type', array('uniacid' => $_W['uniacid'], 'id' => $v['type']), 'img');
                $v['type'] = pdo_getcolumn(PDO_NAME . 'pocket_type', array('uniacid' => $_W['uniacid'], 'id' => $v['type']), 'title');
            } else {
                $v['logo'] = $set['kefu_avatar'];
                $v['type'] = '全局置顶';
            }
            if (empty($v['avatar'])) {
                if ($v['mid']) {
                    $v['avatar'] = pdo_getcolumn(PDO_NAME . 'member', array('uniacid' => $_W['uniacid'], 'id' => $v['mid']), 'avatar');
                } else {
                    $v['avatar'] = $set['kefu_avatar'];
                }
            }
        }

        include wl_template('pocket/infolist');
    }

    /**
     * Comment: 掌上信息导出
     */
    public function exportTieziList($where){
        global $_W, $_GPC;

        $orderlist = Pocket::getNumTiezi('*', $where, 'ID DESC', 0, 0, 1);
        $list = $orderlist[0];
        foreach ($list as $key => &$v) {
            $v['content'] = nl2br($v['content']);
            if ($v['type']) {
                $v['type'] = pdo_getcolumn(PDO_NAME . 'pocket_type', array('uniacid' => $_W['uniacid'], 'id' => $v['type']), 'title');
            } else {
                $v['type'] = '全局置顶';
            }
            $v['createtime'] = date("Y-m-d H:i:s",$v['createtime']);
            //状态
            if($v['status'] == 0){
                $v['status'] = '显示中';
            }else if($v['status'] == 1){
                $v['status'] = '审核中';
            }else if($v['status'] == 2){
                $v['status'] = '未通过';
            }else if($v['status'] == 3){
                $v['status'] = '已删除';
            }else if($v['status'] == 3){
                $v['status'] = '未付款';
            }
            //置顶
            if($v['top'] == 1){
                $v['top'] = '置顶中';
            }else if($v['top'] == 2){
                $v['top'] = '分类置顶';
            }else if($v['top'] == 3){
                $v['top'] = '全局置顶';
            }
            //红包
            if($v['redpackstatus'] > 0){
                $v['redtext'] = '红包剩余'.$v['sredpack'].'元';
            }else{
                $v['redtext'] = '无红包';
            }
        }

        $filter = array(
            'id'  => '帖子id',//U
            'nickname' => '发帖人',//A
            'phone'  => '联系电话',//B
            'type' => '信息分类',//C
            'status' => '帖子状态',//D
            'top' => '置顶状态',//E
            'redtext' => '红包状态',//F
            'look' => '浏览量',//G
            'likenum' => '点赞量',//H
            'share' => '分享量',//I
            'address' => '发帖地址',//J
            'createtime' => '发帖时间',//K
            'content' => '帖子内容',//L
        );

        $data = array();
        for ($i=0; $i < count($list) ; $i++) {
            foreach ($filter as $key => $title) {
                $data[$i][$key] = $list[$i][$key];
            }
        }
        util_csv::export_csv_2($data, $filter, '掌上信息帖子表.csv');
        exit();

    }

    /**
     * Comment: 进入帖子详情
     */
    function details() {
        global $_W, $_GPC;
        $set = Setting::agentsetting_read('pocket');
        $id = $_GPC['id'];
        if (checksubmit('submit')) {
            $data = $_GPC['data'];
            $data['createtime'] = strtotime($data['createtime']);
            $data['content'] = htmlspecialchars_decode($data['content']);
            if ($_GPC['keyword']) {
                $data['keyword'] = serialize($_GPC['keyword']);
            } else {
                $data['keyword'] = '';
            }
            $endtime = $_GPC['endtime'];
            $category = $_GPC['category'];
            $data['onetype'] = $category['parentid'];
            $data['type'] = $category['childid'];
            if (empty($data['type'])) {
                $data['type'] = $data['onetype'];
            }
            if ($data['top']) {
                $data['endtime'] = strtotime($endtime);
            } else {
                $data['endtime'] = 0;
            }
            $data['img'] = serialize($data['img']);
            //关联商户
            $storeids = $_GPC['storeids'];
            if(!empty($storeids)){
                $data['storeid'] = serialize($storeids);
            }

            $viparray = [];
            $vipleid = $_GPC['vipleid'];
            $vipprice = $_GPC['vipprice'];
            foreach($vipleid as $key => $vle){
                $vipa = sprintf("%.2f",$vipprice[$key]);
                $viparray[$vle] = $vipa;
            }
            $data['videoprice'] = serialize($viparray);


            $res = pdo_update('wlmerchant_pocket_informations', $data, array('id' => $id));
            if ($res) {
                wl_message('保存成功！', web_url('pocket/Tiezi/lists'), 'success');
            } else {
                wl_message('保存失败！', web_url('pocket/Tiezi/details', array('id' => $id)), 'error');
            }
        }
        if ($id) {
            $tiezi = Pocket::getInformations($id);
            //过期时间
            if (empty($tiezi['endtime'])) {
                $tiezi['endtime'] = time() + 259200;
            }
            if ($tiezi['mid']) {
                $bmin = pdo_getcolumn('wlmerchant_pocket_blacklist', array('mid' => $tiezi['mid'], 'aid' => $_W['aid'], 'uniacid' => $_W['uniacid']), 'id');
                if ($bmin) {
                    $bflag = 2;
                } else {
                    $bflag = 1;
                }
            }
            $inmid = $tiezi['mid'];
            if (empty($tiezi['avatar'])) {
                if ($tiezi['mid']) {
                    $member['avatar'] = pdo_getcolumn(PDO_NAME . 'member', array('uniacid' => $_W['uniacid'], 'id' => $tiezi['mid']), 'avatar');
                } else {
                    $member['avatar'] = $set['kefu_avatar'];
                }
            } else {
                $member['avatar'] = $tiezi['avatar'];
            }
            $member['nickname'] = $tiezi['nickname'];
            $tiezi['mid'] = $member;
            $tiezi['storeid'] = unserialize($tiezi['storeid']);
            //分类
            $categoryes = pdo_getall(PDO_NAME . 'pocket_type', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid'], 'isnav' => 0)); //商家分类
            if (!empty($categoryes)) {
                $parent = $children = array();
                foreach ($categoryes as $cid => $cate) {
                    $cate['name'] = $cate['title'];
                    if (!empty($cate['type'])) {
                        $children[$cate['type']][] = $cate;
                    } else {
                        $parent[$cate['id']] = $cate;
                    }
                }
            }
//			wl_debug($children);
            $tiezi['img'] = unserialize($tiezi['img']);
            if(!empty($tiezi['img'])){
                foreach($tiezi['img'] as $key => &$img){
                    if(empty($img)){
                        unset($tiezi['img'][$key]);
                    }
                }
            }

            $tiezi['video_link'] = tomedia($tiezi['video_link']);
            $comments = Pocket::getcomments($id);
            $comments = $comments[0];
            foreach ($comments as $key => &$value) {
                $mid = $value['mid'];
                $member = Util::getSingelData("*", wlmerchant_member, array('id' => $mid));
                $value['mid'] = $member;
                $bmin = pdo_getcolumn('wlmerchant_pocket_blacklist', array('mid' => $mid, 'aid' => $_W['aid'], 'uniacid' => $_W['uniacid']), 'id');
                if ($bmin) {
                    $value['mid']['bflag'] = 1;
                }
                $replys = Pocket::getreplys($value['id']);
                $replys = $replys[0];
                if ($replys) {
                    foreach ($replys as &$reply) {
                        $mid = $reply['smid'];
                        $member = Util::getSingelData("*", wlmerchant_member, array('id' => $mid));
                        $reply['smid'] = $member;
                        $bmin = pdo_getcolumn('wlmerchant_pocket_blacklist', array('mid' => $mid, 'aid' => $_W['aid'], 'uniacid' => $_W['uniacid']), 'id');
                        if ($bmin) {
                            $reply['smid']['bflag'] = 1;
                        }
                        $mid = $reply['amid'];
                        $member = Util::getSingelData("*", wlmerchant_member, array('id' => $mid));
                        $reply['amid'] = $member;
                    }
                    $comments[$key]['replys'] = $replys;
                }
            }
            $info = pdo_getcolumn(PDO_NAME . "pocket_type", array('id' => $tiezi['type']), 'keyword');
            $keyword = explode(',', trim($info, ','));
            $tiezi['keyword'] = unserialize($tiezi['keyword']);

            $viparray1 = unserialize($tiezi['videoprice']);


        }

        //定制查询商户
        if(Customized::init('pocket1500') > 0){
            $storelist = pdo_getall('wlmerchant_merchantdata',array('uniacid' => $_W['uniacid'],'aid' =>  $_W['aid'],'status' => 2,'enabled' => 1),array('id','storename'));
            $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");

        }

        include wl_template('pocket/infodetails');
    }

    /**
     * Comment: 帖子删除
     */
    function delete() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $ids = $_GPC['ids'];
        $flag = $_GPC['flag'];
        if ($ids) {
            foreach ($ids as $key => $id) {
                if ($flag == 1) {
                    pdo_update('wlmerchant_pocket_informations', array('status' => 3), array('id' => $id));
                } else if ($flag == 2) {
                    pdo_delete('wlmerchant_pocket_informations', array('id' => $id));
                }
            }
            die(json_encode(array('errno' => 0, 'message' => '', 'id' => '')));
        }
    }

    /**
     * Comment: 帖子通过
     */
    function pass() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $ids = $_GPC['ids'];
        if ($id) {
            $status = pdo_getcolumn(PDO_NAME . 'pocket_informations', array('uniacid' => $_W['uniacid'], 'id' => $id), 'status');
            if ($status == 3) {
                die(json_encode(array('errno' => 2, 'message' => '已删除帖子不能通过', 'id' => $id)));
            } else {
                $res = pdo_update('wlmerchant_pocket_informations', array('status' => 0), array('id' => $id));
                if ($res) {
                    Pocket::passnotice($id);
                    die(json_encode(array('errno' => 0, 'message' => $res, 'id' => $id)));
                } else {
                    die(json_encode(array('errno' => 2, 'message' => '通过失败，请重试', 'id' => $id)));
                }
            }
        }
        if ($ids) {
            foreach ($ids as $key => $id) {
                $status = pdo_getcolumn(PDO_NAME . 'pocket_informations', array('uniacid' => $_W['uniacid'], 'id' => $id), 'status');
                if ($status != 3) {
                    pdo_update('wlmerchant_pocket_informations', array('status' => 0), array('id' => $id));
                    Pocket::passnotice($id);
                }
            }
            die(json_encode(array('errno' => 0, 'message' => '', 'id' => '')));
        }
    }

    /**
     * Comment: 帖子关闭
     */
    function nopass() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $ids = $_GPC['ids'];
        if ($id) {
            $status = pdo_getcolumn(PDO_NAME . 'pocket_informations', array('uniacid' => $_W['uniacid'], 'id' => $id), 'status');
            if ($status == 3) {
                die(json_encode(array('errno' => 2, 'message' => '已删除帖子不能操作', 'id' => $id)));
            } else {
                $res = pdo_update('wlmerchant_pocket_informations', array('status' => 2), array('id' => $id));
                if ($res) {
                    die(json_encode(array('errno' => 0, 'message' => $res, 'id' => $id)));
                } else {
                    die(json_encode(array('errno' => 2, 'message' => '通过失败，请重试', 'id' => $id)));
                }
            }

        }
        if ($ids) {
            foreach ($ids as $key => $id) {
                $status = pdo_getcolumn(PDO_NAME . 'pocket_informations', array('uniacid' => $_W['uniacid'], 'id' => $id), 'status');
                if ($status != 3) {
                    pdo_update('wlmerchant_pocket_informations', array('status' => 2), array('id' => $id));
                }
            }
            die(json_encode(array('errno' => 0, 'message' => '', 'id' => '')));
        }
    }

    /**
     * Comment: 帖子订单列表
     */
    function orders() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $where['aid'] = $_W['aid'];
        $where['plugin'] = 'pocket';
        $where['status'] = 3;
        $fightstatus = $_GPC['fightstatus'];
        if($fightstatus > 0 ){
            $where['fightstatus'] = $fightstatus;
        }

        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 1:
                        $where['@id@'] = $_GPC['keyword'];
                        break;
                    case 2:
                        $where['@orderno@'] = $_GPC['keyword'];
                        break;
                    default:
                        break;
                }
                if ($_GPC['keywordtype'] == 3) {
                    $keyword = $_GPC['keyword'];
                    $params[':content'] = "%{$keyword}%";
                    $goods = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_pocket_informations') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}  AND content LIKE :content", $params);
                    if ($goods) {
                        $goodids = "(";
                        foreach ($goods as $key => $v) {
                            if ($key == 0) {
                                $goodids .= $v['id'];
                            } else {
                                $goodids .= "," . $v['id'];
                            }
                        }
                        $goodids .= ")";
                        $where['fkid#'] = $goodids;
                    } else {
                        $where['fkid#'] = "(0)";
                    }
                }
                if ($_GPC['keywordtype'] == 5) {
                    $keyword = $_GPC['keyword'];
                    $params[':nickname'] = "%{$keyword}%";
                    $goods = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_pocket_informations') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}  AND nickname LIKE :nickname", $params);
                    if ($goods) {
                        $goodids = "(";
                        foreach ($goods as $key => $v) {
                            if ($key == 0) {
                                $goodids .= $v['id'];
                            } else {
                                $goodids .= "," . $v['id'];
                            }
                        }
                        $goodids .= ")";
                        $where['fkid#'] = $goodids;
                    } else {
                        $where['fkid#'] = "(0)";
                    }
                }
                if ($_GPC['keywordtype'] == 6) {
                    $keyword = $_GPC['keyword'];
                    $params[':phone'] = "%{$keyword}%";
                    $goods = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_pocket_informations') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}  AND :phone LIKE :phone", $params);
                    if ($goods) {
                        $goodids = "(";
                        foreach ($goods as $key => $v) {
                            if ($key == 0) {
                                $goodids .= $v['id'];
                            } else {
                                $goodids .= "," . $v['id'];
                            }
                        }
                        $goodids .= ")";
                        $where['fkid#'] = $goodids;
                    } else {
                        $where['fkid#'] = "(0)";
                    }
                }
            }
        }
        if ($_GPC['time_limit']) {
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $where['createtime>'] = $starttime;
            $where['createtime<'] = $endtime + 24 * 3600;
        }

        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $orderlist = Pocket::getNumOrder('*', $where, 'ID DESC', $pindex, $psize, 1);
        $pager = $orderlist[1];
        $list = $orderlist[0];
        foreach ($list as $key => &$v) {
            $tiezi = Pocket::getInformations($v['fkid']);
            $v['content'] = $tiezi['content'];
            $v['logo'] = pdo_getcolumn(PDO_NAME . 'pocket_type', array('uniacid' => $_W['uniacid'], 'id' => $tiezi['type']), 'img');
            $v['type'] = pdo_getcolumn(PDO_NAME . 'pocket_type', array('uniacid' => $_W['uniacid'], 'id' => $tiezi['type']), 'title');
            $v['avatar'] = pdo_getcolumn(PDO_NAME . 'member', array('uniacid' => $_W['uniacid'], 'id' => $tiezi['mid']), 'avatar');
            $v['nickname'] = $tiezi['nickname'];
            $v['phone'] = $tiezi['phone'];
            $v['endtime'] = $tiezi['endtime'];
        }

        include wl_template('pocket/orderlist');
    }

    /**
     * Comment: 发布帖子
     */
    function createtop() {
        global $_W, $_GPC;
        $type = $_GPC['type'];
        $types = pdo_getall('wlmerchant_pocket_type', array('isnav' => 0, 'aid' => $_W['aid'], 'type' => 0,'uniacid'=>$_W['uniacid']));
        $set = Setting::agentsetting_read('pocket');
        if ($_W['ispost']) {
            $data = $_GPC['data'];
            $data['keyword'] = serialize($_GPC['keyword']);
            $data['img'] = serialize($data['img']);
            $data['mid'] = 0;
            $data['uniacid'] = $_W['uniacid'];
            $data['aid'] = $_W['aid'];
            $data['status'] = 0;

            if (empty($type)) {
                $data['nickname'] = $set['kefu_name'];
                $data['phone'] = $set['kefu_phone'];
                $data['avatar'] = $set['kefu_avatar'];
                if ($data['onetype']) {
                    $data['top'] = 2;
                } else {
                    $data['top'] = 3;
                }
            }
            if ($data['top'] == 1) {
                $data['endtime'] = time() + 3 * 24 * 3600;
            }
            $data['createtime'] = time();
            $data['likenum'] = 0;
            $data['look'] = 0;
            $data['share'] = 0;
            if (empty($data['type'])) {
                $data['type'] = $data['onetype'];
            }

            $storeids = $_GPC['storeids'];
            if(!empty($storeids)){
                $data['storeid'] = serialize($storeids);
            }

            $viparray = [];
            $vipleid = $_GPC['vipleid'];
            $vipprice = $_GPC['vipprice'];
            foreach($vipleid as $key => $vle){
                $vipa = sprintf("%.2f",$vipprice[$key]);
                $viparray[$vle] = $vipa;
            }
            $data['videoprice'] = serialize($viparray);

            $res = pdo_insert('wlmerchant_pocket_informations', $data);
            if ($res) {
                wl_message('保存成功！', web_url('pocket/Tiezi/lists'), 'success');
            } else {
                wl_message('保存失败！', web_url('pocket/Tiezi/createtop'), 'error');
            }
        }
        //定制查询商户
        if(Customized::init('pocket1500') > 0){
            $storelist = pdo_getall('wlmerchant_merchantdata',array('uniacid' => $_W['uniacid'],'aid' =>  $_W['aid'],'status' => 2,'enabled' => 1),array('id','storename'));
            $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
        }
        include wl_template('pocket/createtop');
    }

    /**
     * Comment: 获取帖子分类
     */
    function seconds() {
        global $_W, $_GPC;
        $onetype = $_GPC['onetype'];
        $seconds = pdo_getall('wlmerchant_pocket_type', array('aid' => $_W['aid'], 'uniacid' => $_W['uniacid'], 'type' => $onetype));
        if ($seconds) {
            die(json_encode(array('errno' => 0, 'twotype' => $seconds)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    /**
     * Comment: 删除帖子详情
     * Author: zzw
     */
    function deletecomment() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $res = pdo_delete('wlmerchant_pocket_comment', array('id' => $id));
        if ($res) {
            die(json_encode(array('errno' => 0)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    /**
     * Comment: 删除帖子的回复信息
     * Author: zzw
     */
    function deletereply() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $res = pdo_delete('wlmerchant_pocket_reply', array('id' => $id));
        if ($res) {
            die(json_encode(array('errno' => 0)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    /**
     * Comment: 添加掌上信息黑名单
     * Author: zzw
     */
    function addblack() {
        global $_W, $_GPC;
        $data['uniacid'] = $_W['uniacid'];
        $data['aid'] = $_W['aid'];
        $data['mid'] = $_GPC['mid'];
        $data['inid'] = $_GPC['inid'];
        $data['createtime'] = time();
        if ($data['inid'] && $data['mid']) {
            $res = pdo_insert(PDO_NAME . 'pocket_blacklist', $data);
            if ($res) {
                die(json_encode(array('errno' => 0)));
            } else {
                die(json_encode(array('errno' => 1)));
            }
        } else {
            die(json_encode(array('errno' => 1, 'msg' => '添加失败，参数错误')));
        }
    }

    /**
     * Comment: 取消掌上信息黑名单
     * Author: zzw
     */
    function deteblack() {
        global $_W, $_GPC;
        $data['uniacid'] = $_W['uniacid'];
        $data['aid'] = $_W['aid'];
        $data['mid'] = $_GPC['mid'];
        if ($data['mid']) {
            $res = pdo_delete('wlmerchant_pocket_blacklist', $data);
            if ($res) {
                die(json_encode(array('errno' => 0)));
            } else {
                die(json_encode(array('errno' => 1)));
            }
        } else {
            die(json_encode(array('errno' => 1, 'msg' => '取消失败，参数错误')));
        }
    }

    /**
     * Comment: 获取某个分类的所有标签
     * Author: zzw
     */
    public function getKeyword() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $info = pdo_getcolumn(PDO_NAME . "pocket_type", array('id' => $id), 'keyword');
        if ($info) {
            $keyword = explode(',', trim($info, ','));
            wl_json(1, '标签内容', $keyword);
        } else {
            wl_json(0, '没有标签');
        }
    }

    /**
     * Comment: 驳回帖子
     * Author: zzw
     */
    public function reject() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $text = $_GPC['text'];
        if (empty($text)) {
            $reason = '时间：' . date('Y-m-d H:i:s', time()) . '在后台驳回';
            $res = pdo_update(PDO_NAME . 'pocket_informations', array('status' => 2, 'reason' => $reason), array('id' => $id));
        } else {
            $res = pdo_update(PDO_NAME . 'pocket_informations', array('status' => 2, 'reason' => $text), array('id' => $id));
        }
        if ($res) {
            Pocket::passnotice($id);
            wl_json(1);
        } else {
            wl_json(0);
        }
    }

    public function blacklist() {
        global $_W, $_GPC;
        $blacklist = pdo_getall('wlmerchant_pocket_blacklist', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']));
        foreach ($blacklist as $key => &$black) {
            $black['avatar'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $black['mid']), 'avatar');
            $black['nickname'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $black['mid']), 'nickname');
        }
        include wl_template('pocket/blacklist');
    }
}
