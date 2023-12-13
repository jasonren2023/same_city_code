<?php
defined('IN_IA') or exit('Access Denied');

class Bargain_web_WeliamController {

    function activitylist() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $data = array();
        if (!empty($_GPC['status'])) {
            if ($_GPC['status'] == 4) {
                $data['#status'] = "(0,4)";
            } else {
                $data['status'] = intval($_GPC['status']);
            }
        }
        $data['aid'] = $_W['aid'];
        if (is_store()) {
            $data['sid'] = $_W['storeid'];
        }
        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 1:
                        $data['@name@'] = $_GPC['keyword'];
                        break;
                    case 2:
                        $data['id'] = $_GPC['keyword'];
                        break;
                    default:
                        break;
                }
                if ($_GPC['keywordtype'] == 3) {
                    $keyword = $_GPC['keyword'];
                    $params[':storename'] = "%{$keyword}%";
                    $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_merchantdata') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND storename LIKE :storename", $params);
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
                        $data['sid#'] = $sids;
                    } else {
                        $data['sid#'] = "(0)";
                    }
                }
            }
        }

        $activity = Bargain::getNumActive('*', $data, 'sort DESC,ID DESC', $pindex, $psize, 1);
        $pager = $activity[1];
        $activity = $activity[0];
        foreach ($activity as $key => &$act) {
            $act['storename'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $act['sid']), 'storename');
            $act['pv'] = $act['pv'] + $act['falselooknum'];

            $act['alreadypay'] = WeliamWeChat::getSalesNum(5,$act['id'],0,2,0);
            if (empty($act['alreadypay'])) {
                $act['alreadypay'] = 0;
            }
            $act['alreadyuse'] = WeliamWeChat::getSalesNum(5,$act['id'],0,3,0);
            if (empty($act['alreadyuse'])) {
                $act['alreadyuse'] = 0;
            }
            $act['bargaining'] = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_bargain_userlist') . " WHERE uniacid = {$_W['uniacid']} AND  activityid = {$act['id']} AND status = 1 ");
            if (empty($act['bargaining'])) {
                $act['bargaining'] = 0;
            }
        }

        //统计数量
        if (is_store()) {
            $status0 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status=1 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status=2 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status=3 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status IN (0,4) and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status=5 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status=6 and aid={$_W['aid']} and sid = {$_W['storeid']}");
            $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status=8 and aid={$_W['aid']} and sid = {$_W['storeid']}");
        } else {
            $status0 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and aid={$_W['aid']}");
            $status1 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status=1 and aid={$_W['aid']}");
            $status2 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status=2 and aid={$_W['aid']}");
            $status3 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status=3 and aid={$_W['aid']}");
            $status4 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status IN (0,4) and aid={$_W['aid']}");
            $status5 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status=5 and aid={$_W['aid']}");
            $status6 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status=6 and aid={$_W['aid']}");
            $status8 = pdo_fetchcolumn('SELECT COUNT(id) FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE uniacid={$_W['uniacid']} and status=8 and aid={$_W['aid']}");
        }
        include wl_template('bargain/activitylist');
    }

    function changepv() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        $newvalue = trim($_GPC['value']);
        if ($type == 1) {
            $pv = pdo_getcolumn('wlmerchant_bargain_activity', array('id' => $id), 'pv');
            $newvalue = intval($newvalue - $pv);
            $res = pdo_update('wlmerchant_bargain_activity', array('falselooknum' => $newvalue), array('id' => $id));
        } elseif ($type == 2) {
            $res = pdo_update('wlmerchant_bargain_activity', array('sort' => $newvalue), array('id' => $id));
        }
        if ($res) {
            show_json(1, '修改成功');
        } else {
            show_json(0, '修改失败，请重试');
        }
    }

    function changestatus() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'];
        if ($status) {
            $res = Bargain::updateActive(array('status' => 0), array('id' => $id));
        } else {
            $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $id),array('starttime','endtime','sid'));
            if(is_store()){
                $examine = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$goods['sid']),'audits');
                if(empty($examine)){
                    $changestatus = 5;
                }
            }
            if(empty($changestatus)){
                if ($goods['starttime'] > time()) {
                    $changestatus = 1;
                }
                else if ($goods['starttime'] < time() && time() < $goods['endtime']) {
                    $changestatus = 2;
                }
                else if ($goods['endtime'] < time()) {
                    $changestatus = 3;
                }
            }
            $res = Bargain::updateActive(array('status' => $changestatus), array('id' => $id));
        }
        if ($res) {
            die(json_encode(array('errno' => 0)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    function copygood() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $da = Bargain::getSingleActive($id, '*');
        unset($da['id']);
        $da['status'] = 0;
        $da['pv'] = 0;
        $da['sharenum'] = 0;
        $res = pdo_insert('wlmerchant_bargain_activity', $da);
        if ($res) {
            die(json_encode(array('errno' => 0)));
        } else {
            die(json_encode(array('errno' => 1)));
        }
    }

    function pass() {
        global $_W, $_GPC;
        $flag = $_GPC['flag'];
        $id = intval($_GPC['id']);
        if ($flag) {
            $res = pdo_update('wlmerchant_bargain_activity', array('status' => 1), array('id' => $id));
            News::goodsToExamine($id,'bargain');
        } else {
            $res = pdo_update('wlmerchant_bargain_activity', array('status' => 6), array('id' => $id));
            News::goodsToExamine($id,'bargain','未通过');
        }
        if ($res) {
            show_json(1, '活动审核成功');
        } else {
            show_json(0, '活动审核失败，请重试');
        }
    }

    function delall() {
        global $_W, $_GPC;
        $res = pdo_delete('wlmerchant_bargain_activity', array('id' => intval($_GPC['id'])));
        if ($res) {
            show_json(1, '活动删除成功');
        } else {
            show_json(0, '活动删除失败，请重试');
        }
    }

    function cutoff() {
        global $_W, $_GPC;
        $res = pdo_update('wlmerchant_bargain_activity', array('status' => 8), array('id' => intval($_GPC['id'])));
        if ($res) {
            show_json(1, '活动删除成功');
        } else {
            show_json(0, '活动删除失败，请重试');
        }
    }

    /**
     * Comment: 砍价分类列表
     * Author: zzw
     * Date: 2019/12/20 14:38
     */
    function categorylist() {
        global $_W, $_GPC;
        #1、参数获取
        $page = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        $keyword = $_GPC['keyword'] ? : '';
        #1、条件生成
        $where = ['aid'=>$_W['aid'],'uniacid'=>$_W['uniacid']];
        if(!empty($keyword)) $where['name LIKE'] = '%' . $keyword . '%';
        #1、列表获取
        $list = pdo_getslice(PDO_NAME . 'bargain_category',$where,[$page, $pageIndex],$total,['id','name','sort','thumb'],'','sort DESC');
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('goodshouse/cate_list');
    }



    public function bargainrecord() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = array('uniacid' => $_W['uniacid']);
        if (is_store()) {
            $members = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_bargain_activity') . "WHERE sid = {$_W['storeid']}");
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
                $where['activityid#'] = $mids;
            } else {
                $where['activityid#'] = "(0)";
            }
        }

        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 1:
                        $where['activityid'] = $_GPC['keyword'];
                        break;
                    case 2:
                        $where['userid'] = $_GPC['keyword'];
                        break;
                    case 3:
                        $where['authorid'] = $_GPC['keyword'];
                        break;
                    case 4:
                        $where['mid'] = $_GPC['keyword'];
                        break;
                    default:
                        break;
                }
                if ($_GPC['keywordtype'] == 5) {
                    $keyword = $_GPC['keyword'];
                    $params[':storename'] = "%{$keyword}%";
                    $members = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_bargain_activity') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND name LIKE :storename", $params);
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
                        $where['activityid#'] = $mids;
                    } else {
                        $where['activityid#'] = "(0)";
                    }
                }
                if ($_GPC['keywordtype'] == 6) {
                    $keyword = $_GPC['keyword'];
                    $params[':storename'] = "%{$keyword}%";
                    $members = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND nickname LIKE :storename", $params);
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
                        $where['authorid#'] = $mids;
                    } else {
                        $where['authorid#'] = "(0)";
                    }
                }
                if ($_GPC['keywordtype'] == 7) {
                    $keyword = $_GPC['keyword'];
                    $params[':storename'] = "%{$keyword}%";
                    $members = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_member') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND nickname LIKE :storename", $params);
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
                        $where['mid#'] = $mids;
                    } else {
                        $where['mid#'] = "(0)";
                    }
                }
            }
        }

        if (!empty($_GPC['time_limit'])) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $where['createtime>'] = $starttime;
            $where['createtime<'] = $endtime + 86400;
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        if ($_GPC['userlistid']) {
            $where['userid'] = $_GPC['userlistid'];
        }

        $records = Util::getNumData('*', PDO_NAME . 'bargain_helprecord', $where, 'createtime DESC', $pindex, $psize, 1);
        $pager = $records[1];
        $records = $records[0];
        if ($records) {
            foreach ($records as $key => &$re) {
                $goods = pdo_get('wlmerchant_bargain_activity', array('id' => $re['activityid']), array('name', 'thumb', 'sid'));
                $re['logo'] = $goods['thumb'];
                $re['gname'] = $goods['name'];
                $re['sid'] = $goods['sid'];
                $merchant = pdo_get('wlmerchant_merchantdata', array('id' => $goods['sid']), array('storename', 'logo'));
                $re['storename'] = $merchant['storename'];
                $re['merchantlogo'] = $merchant['logo'];
                $author = pdo_get('wlmerchant_member', array('id' => $re['authorid']), array('nickname', 'avatar'));
                $re['username'] = $author['nickname'];
                $re['useravatar'] = $author['avatar'];
                $member = pdo_get('wlmerchant_member', array('id' => $re['mid']), array('nickname', 'avatar'));
                $re['nickname'] = $member['nickname'];
                $re['avatar'] = $member['avatar'];

                $re['createtime'] = date("Y-m-d H:i:s", $re['createtime']);
            }
        }

        include wl_template('bargain/bargainrecord');
    }

    function hexiaotime() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $record = pdo_get('wlmerchant_bargain_userlist', array('orderid' => $id), array('usetimes', 'usedtime'));
        $record['usedtime'] = unserialize($record['usedtime']);
        foreach ($record['usedtime'] as $key => &$v) {
            $v['time'] = date('Y-m-d H:i:s', $v['time']);
            switch ($v['type']) {
                case '1':
                    $v['typename'] = '输码核销';
                    break;
                case '2':
                    $v['typename'] = '扫码核销';
                    break;
                case '3':
                    $v['typename'] = '后台核销';
                    break;
                case '4':
                    $v['typename'] = '密码核销';
                    break;
                default:
                    $v['typename'] = '未知方式';
                    break;
            }
            if ($v['type'] == 1 || $v['type'] == 2) {
                $v['vername'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $v['ver']), 'nickname');
            } else {
                $v['vername'] = '无';
            }
        }
        die(json_encode(array('errno' => 0, 'times' => $record['usetimes'], 'data' => $record['usedtime'])));
    }

    public function userlist() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = array('uniacid' => $_W['uniacid']);
        if (!empty($_GPC['status'])) {
            $where['status'] = intval($_GPC['status']);
        }
        if (is_store()) {
            $where['merchantid'] = $_W['storeid'];
        }
        if (!empty($_GPC['keyword'])) {
            if (!empty($_GPC['keywordtype'])) {
                switch ($_GPC['keywordtype']) {
                    case 1:
                        $where['activityid'] = $_GPC['keyword'];
                        break;
                    case 2:
                        $where['merchantid'] = $_GPC['keyword'];
                        break;
                    default:
                        break;
                }
                if ($_GPC['keywordtype'] == 3) {
                    $keyword = $_GPC['keyword'];
                    $params[':storename'] = "%{$keyword}%";
                    $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_bargain_activity') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND name LIKE :storename", $params);
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
                        $where['activityid#'] = $sids;
                    } else {
                        $where['activityid#'] = "(0)";
                    }
                }
                if ($_GPC['keywordtype'] == 4) {
                    $keyword = $_GPC['keyword'];
                    $params[':storename'] = "%{$keyword}%";
                    $merchants = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_merchantdata') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND storename LIKE :storename", $params);
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
                        $where['merchantid#'] = $sids;
                    } else {
                        $where['merchantid#'] = "(0)";
                    }
                }
            }
        }

        if (!empty($_GPC['time_limit']) && $_GPC['timetype']) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            if ($_GPC['timetype'] == 1) {
                $where['createtime>'] = $starttime;
                $where['createtime<'] = $endtime + 86400;
            } else {
                $where['updatetime>'] = $starttime;
                $where['updatetime<'] = $endtime;
            }

        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $users = Util::getNumData('*', PDO_NAME . 'bargain_userlist', $where, 'ID DESC', $pindex, $psize, 1);
        $pager = $users[1];
        $users = $users[0];
        foreach ($users as $key => &$user) {
            $goods = pdo_get('wlmerchant_bargain_activity', array('id' => $user['activityid']), array('name', 'thumb', 'oldprice', 'sid'));
            $user['logo'] = $goods['thumb'];
            $user['name'] = $goods['name'];
            $user['oldprice'] = $goods['oldprice'];
            $user['storename'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $goods['sid']), 'storename');
            $user['orderno'] = pdo_getcolumn(PDO_NAME . 'order', array('id' => $user['orderid']), 'orderno');
        }

        include wl_template('bargain/userlist');
    }

    /**
     * Comment: 砍价基本设置
     * Author: zzw
     */
    public function setting() {
        global $_W, $_GPC;
        if (checksubmit('submit')) {
            //处理数据值
            $data = $_GPC['base'];
            $data['playdetail'] = trim($data['playdetail']);
            $res1 = Setting::agentsetting_save($data, 'bargainset');
            wl_message('保存设置成功！', referer(), 'success');
        }
        $base = Setting::agentsetting_read('bargainset');

        $communitylist = pdo_getall('wlmerchant_community', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), array('id', 'communname'));

        include wl_template('bargain/set');
    }


    /**
     * Comment: 获取砍价商品信息列表
     * Author: zzw
     * Date: 2019/7/11 17:38
     */
    public function bargainList() {
        global $_W, $_GPC;
        #1、条件生成
        $where = " a.aid = {$_W['aid']} AND a.uniacid = {$_W['uniacid']}";//默认条件
        !empty($_GPC['name']) && $where .= " AND a.name LIKE '%{$_GPC['name']}%' ";//商品名称
        $_GPC['status'] > -1 && $where .= " AND a.status = {$_GPC['status']} ";//商品名称
        !empty($_GPC['goods_id']) && $where .= " AND a.id = {$_GPC['goods_id']} ";//商品id
        !empty($_GPC['shop_name']) && $where .= " AND m.storename LIKE '%{$_GPC['shop_name']}%' ";//商户名称
        $_GPC['cate_id'] > -1 && $where .= " AND a.cateid = {$_GPC['cate_id']} ";//商户名称
        !empty($_GPC['shop_id']) && $where .= " AND a.sid = {$_GPC['shop_id']} ";//商户id
        #2、排序操作
        $order = " a.sort DESC ,a.id DESC ";
        #3、分页操作
        $page = $_GPC['page'] ? $_GPC['page'] : 1;//当前页
        $index = $_GPC['index'] ? $_GPC['index'] : 10;//每页的数量
        $start = $page * $index - $index;//开始查询的点 = 当前页 * 每页的数量 - 每页的数量
        $limit = " LIMIT {$start},{$index}";
        #4、查询信息内容
        $field = 'a.id,a.thumb,a.name,a.starttime,a.endtime,a.status,(a.pv + a.falselooknum) as pv,a.stock,a.sort,m.storename,b.name as cate_name';
        $sql = "SELECT {$field} FROM " . tablename(PDO_NAME . 'bargain_activity')
            . " a LEFT JOIN " . tablename(PDO_NAME . "bargain_category")
            . " b ON a.cateid = b.id LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
            . " m ON a.sid = m.id";
        !empty($where) && $sql .= " WHERE {$where} ";
        $sql .= ' GROUP BY a.id ';
        !empty($order) && $sql .= " ORDER BY {$order} ";
        $total = count(pdo_fetchall(str_replace($field, "a.id", $sql)));//获取符合条件的总数量
        $data['page_num'] = ceil($total / $index);//获取一共有多少页
        !empty($limit) && $sql .= $limit;
        $data['list'] = pdo_fetchall($sql);//获取要查询的列表数据
        #5、处理相关信息
        $orderModel = new Order();
        foreach ($data['list'] as $k => &$v) {
            //图片信息转换
            $v['thumb'] = tomedia($v['thumb']);
            //获取销量信息
            $orderW = " uniacid = {$_W['uniacid']} AND  fkid = {$v['id']} AND plugin = 'bargain' AND status IN ";
            $v['bargaining'] = $orderModel->getPurchaseQuantity(" uniacid = {$_W['uniacid']} AND  activityid = {$v['id']} AND status = 1 ") ?: 0;//砍价中
            $v['order_payment'] = $orderModel->getPurchaseQuantity($orderW . " (1,2,3,4,8,6,7,9) ") ?: 0;//已支付
            $v['order_used'] = $orderModel->getPurchaseQuantity($orderW . " (2,3) ") ?: 0;//已完成
            //时间戳转时间
            $v['starttime'] = date("Y-m-d H:i:s", $v['starttime']);
            $v['endtime'] = date("Y-m-d H:i:s", $v['endtime']);
        }

        wl_json(1, '抢购商品列表', $data);
    }

    /**
     * Comment: 获取砍价商品分类列表
     * Author: zzw
     * Date: 2019/7/11 17:53
     */
    public function getClassList() {
        global $_W, $_GPC;
        $where = " uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        $list = pdo_fetchall("SELECT id,name FROM " . tablename(PDO_NAME . 'bargain_category') . " WHERE {$where} ORDER BY sort DESC ");

        wl_json(1, '砍价分类列表', $list);
    }

    /**
     * Comment: 修改砍价商品的某个单项数据信息
     * Author: zzw
     * Date: 2019/7/15 11:15
     */
    public function updateInfo() {
        global $_W, $_GPC;
        #1、参数接收
        if (empty($_GPC['field'])) show_json(0, "缺少参数：修改的字段名称");
        #2、偏移操作  如果是修改人气 则减去实际人气修改虚拟人气
        if ($_GPC['field'] == 'pv') {
            $pv = pdo_fetchcolumn('SELECT pv FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE id = {$_GPC['id']}") ?: 0;
            $_GPC['value'] = $_GPC['value'] - $pv;
            $_GPC['field'] = 'falselooknum';
            if ($_GPC['value'] < 0) {
                $total = pdo_fetchcolumn('SELECT (pv + falselooknum) as pv FROM ' . tablename(PDO_NAME . 'bargain_activity') . " WHERE id = {$_GPC['id']}") ?: 0;
                show_json(0, ['message' => "浏览量设置不能小于真实浏览量", 'data' => $total]);
            }
        }
        #3、修改内容
        $data[$_GPC['field']] = $_GPC['value'];
        $res = pdo_update(PDO_NAME . 'bargain_activity', $data, array('id' => $_GPC['id']));
        if ($res) {
            show_json(1, "修改成功");
        } else {
            show_json(0, "修改失败");
        }
    }

    /**
     * Comment: 批量修改商品信息
     * Author: wlf
     * Date: 2020/06/01 16:02
     */
    public function checkchangestatus(){
        global $_W, $_GPC;
        $ids = $_GPC['ids'];
        $type = $_GPC['type'];
        foreach ($ids as$k=>$v){
            $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $v),array('starttime','endtime','status','sid'));
            if($type == 1){
                $status = 0;
                if(is_store()){
                    $examine = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$goods['sid']),'audits');
                    if(empty($examine)){
                        $status = 5;
                    }
                }
                if(empty($status)){
                    if ($goods['starttime'] > time()) {
                        $status = 1;
                    }
                    else if ($goods['starttime'] < time() && time() < $goods['endtime']) {
                        $status = 2;
                    }
                    else if ($goods['endtime'] < time()) {
                        $status = 3;
                    }
                }
                pdo_update('wlmerchant_bargain_activity', array('status' => $status), array('id' => $v));
            }else if($type == 8 && $goods['status'] == 8){
                pdo_delete('wlmerchant_bargain_activity', array('id' => $v));
            }else{
                pdo_update('wlmerchant_bargain_activity', array('status' => $type), array('id' => $v));
            }
        }
        show_json(1, '操作成功');
    }


}