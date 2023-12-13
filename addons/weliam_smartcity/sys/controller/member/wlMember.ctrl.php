<?php
defined('IN_IA') or exit('Access Denied');

class wlMember_WeliamController {
    //客户概况
    public function index() {
        global $_W, $_GPC;
        //清理过期登录数据
        pdo_delete('wlmerchant_login',array('end_time <'=>time()));
//        $allmembers = pdo_fetchall("select id,openid from `ims_wlmerchant_member` WHERE `openid` in ( select `openid` from  `ims_wlmerchant_member` group by `openid` having count(`openid`)>1);");
//        $newdata = [];
//        foreach ($allmembers as $key => $allmember) {
//            if (!empty($allmember['openid'])) {
//                $delmember = pdo_getall('wlmerchant_member', array('openid' => $allmember['openid'], 'uniacid' => $_W['uniacid']), ['id', 'openid', 'uniacid']);
//                if (count($delmember) >= 1) {
//                    pdo_delete('wlmerchant_member', ['id' => $delmember[1]['id']]);
//                    $newdata[] = $delmember;
//                }
//            }
//        }
        $start = ($_GPC["start"] ? strtotime($_GPC["start"]) : strtotime(date("Y-m")));
        $end = ($_GPC["end"] ? strtotime($_GPC["end"]) + 86399 : strtotime(date("Y-m-d")) + 86399);
        $day_num = ($end - $start) / 86400;

        if ($_W['isajax']) {
            $days = array();
            for ($i = 0; $i < $day_num; $i++) {
                $key = date("m-d", $start + 86400 * $i);
                $days[$key] = 0;
            }
            $where = $_W['aid'] ? " AND aid = " . $_W['aid'] : '';

            $data = pdo_fetchall("SELECT createtime FROM " . tablename("wlmerchant_member") . "WHERE uniacid = :uniacid AND createtime >= :starttime and createtime <= :endtime" . $where, array(":uniacid" => $_W["uniacid"], ":starttime" => $start, ":endtime" => $end));
            foreach ($data as $da) {
                $key = date("m-d", $da["createtime"]);
                if (in_array($key, array_keys($days))) {
                    $days[$key]++;
                }
            }

            $newdata = array();
            foreach ($days as $k => $val) {
                $newdata[] = array('day' => $k, '新增客户' => $val);
            }

            die(json_encode($newdata));
        }

        $stat = Merchant::sysMemberSurvey();

        include wl_template('member/summary');
    }
    //客户列表
    public function memberIndex() {
        global $_W, $_GPC;
        $where = $_W['aid'] ? ['aid' => $_W['aid']] : [];
        static $account_api;
        if (empty($account_api)) {
            $account_api = WeAccount::create();
        }
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        if ($_GPC['keyword'] && $_GPC['type']) {
            if (!empty($_GPC['keyword'])) {
                switch ($_GPC['type']) {
                    case 2 :
                        $where['@mobile@'] = $_GPC['keyword'];
                        break;
                    case 3 :
                        $where['@realname@'] = $_GPC['keyword'];
                        break;
                    case 5 :
                        $where['id'] = $_GPC['keyword'];
                        break;
                    default :
                        $where['@nickname@'] = $_GPC['keyword'];
                }
            }
        }

        //时间
        if (!empty($_GPC['time_limit']) && $_GPC['timetype'] > 0) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            $where['createtime>'] = $starttime;
            $where['createtime<'] = $endtime;
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        if ($_GPC['mid']) {
            $where['id'] = $_GPC['mid'];
        }
        //黑名单
        if ($_GPC['blackflag']) {
            if ($_GPC['blackflag'] == 1) {
                $where['blackflag'] = 1;
            } else {
                $where['blackflag'] = 0;
            }
        }

        //用户列表导出
        if($_GPC['export']){
            $this -> exportMmberList($where);
        }

        $memberData = Util::getNumData("*", PDO_NAME . 'member', $where, 'id desc', $pindex, $psize, 1);
        $list = $memberData[0];
        $pager = $memberData[1];

        load()->model('mc');
        foreach ($list as $key => &$value) {
            if (empty($value['openid']) && $value['uid']) {
                $mfans = pdo_get('mc_mapping_fans', array('uid' => $value['uid'], 'uniacid' => $_W['uniacid']), array('openid'));
                if ($mfans['openid']) {
                    $value['openid'] = $mfans['openid'];
                    pdo_update('wlmerchant_member', array('openid' => $mfans['openid']), array('id' => $value['id']));
                }
            }
            $result = mc_fansinfo($value['openid']);
            $credit = pdo_get('mc_members', array('uid' => $value['uid']), array('credit1', 'credit2'));
            $value['follow'] = $result['follow'];
            $value['unfollowtime'] = $result['unfollowtime'];
            $value['credit1'] = $credit['credit1'];
            $value['credit2'] = $credit['credit2'];
            $fans = pdo_get('mc_mapping_fans', array('openid' => $value['openid']), array('tag'));
            $fans = base64_decode($fans['tag']);
            $fans = unserialize($fans);
            if(!empty($fans['headimgurl'])){
                $value['avatar'] = $fans['headimgurl'];
            }
            if(!empty($fans['nickname'])) {
                $value['nickname'] = $fans['nickname'];
            }
            //pdo_update('wlmerchant_member', array('avatar' => $value['avatar'], 'nickname' => $value['nickname']), array('id' => $value['id']));
            if(!empty($value['encodename'])){
                $value['nickname'] = base64_decode($value['encodename']);
            }
            //推荐人
            if(empty($value['distributorid'])){
                $value['distributorid'] = pdo_getcolumn(PDO_NAME.'distributor',array('mid'=>$value['id']),'id');
                if( $value['distributorid'] > 0 ){
                    pdo_update(PDO_NAME.'member',array('distributorid' => $value['distributorid']),array('id' => $value['id']));
                }
            }
            if ($value['distributorid']) {
                $tjmid = pdo_getcolumn(PDO_NAME . 'distributor', array('id' => $value['distributorid']), 'leadid');
                $value['tjmid'] = $tjmid;
                if ($tjmid > 0) {
                    $value['tjname'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $tjmid), 'nickname');
                }
            }
            //统计订单和金额
            $dealnum1 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_rush_order') . " WHERE mid = {$value['id']} AND uniacid = {$_W['uniacid']} AND status IN (1,2,3,4,6,8,9)");
            $dealnum2 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_order') . " WHERE mid = {$value['id']} AND uniacid = {$_W['uniacid']} AND status IN (1,2,3,4,6,8,9)");
            $dealnum = $dealnum2 + $dealnum1;

            $dealmoney1 = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename('wlmerchant_rush_order') . " WHERE mid = {$value['id']} AND uniacid = {$_W['uniacid']} AND status IN (1,2,3,4,6,8,9)");
            $dealmoney2 = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_order') . " WHERE mid = {$value['id']} AND uniacid = {$_W['uniacid']} AND status IN (1,2,3,4,6,8,9)");
            $dealmoney = $dealmoney1 + $dealmoney2;
            $value['dealnum'] = $dealnum;
            $value['dealmoney'] = $dealmoney;
            pdo_update('wlmerchant_member', array('dealnum' => $dealnum, 'dealmoney' => $dealmoney), array('id' => $value['id']));
        }
        include wl_template('member/listIndex');
    }
    //导出用户信息
    public function exportMmberList($where) {
        global $_W;
        $memberData = Util::getNumData("*", PDO_NAME . 'member', $where, 'id desc', 0, 0, 1);
        $list = $memberData[0];
        load()->model('mc');
        foreach ($list as $key => &$value) {
            if (empty($value['openid']) && $value['uid']) {
                $mfans = pdo_get('mc_mapping_fans', array('uid' => $value['uid'], 'uniacid' => $_W['uniacid']), array('openid'));
                if ($mfans['openid']) {
                    $value['openid'] = $mfans['openid'];
                    pdo_update('wlmerchant_member', array('openid' => $mfans['openid']), array('id' => $value['id']));
                }
            }
            $result = mc_fansinfo($value['openid']);
            $credit = pdo_get('mc_members', array('uid' => $value['uid']), array('credit1', 'credit2'));
            $value['follow'] = $result['follow'];
            $value['unfollowtime'] = $result['unfollowtime'];
            $value['credit1'] = $credit['credit1'];
            $value['credit2'] = $credit['credit2'];
            if($value['follow'] > 0){
                $value['followst'] = '已关注';
            }else if($value['unfollowtime'] > 0){
                $value['followst'] = '取消关注';
            }else{
                $value['followst'] = '未关注';
            }
            if(!empty($value['openid']) && !empty($value['wechat_openid'])){
                $value['source'] = '双渠道';
            }else if(!empty($value['openid'])){
                $value['source'] = '公众号';
            }else if(!empty($value['openid'])){
                $value['source'] = '小程序';
            }
            if (empty($value['avatar']) || empty($value['nickname'])) {
                $fans = pdo_get('mc_mapping_fans', array('openid' => $value['openid']), array('tag'));
                $fans = base64_decode($fans['tag']);
                $fans = unserialize($fans);
                $value['avatar'] = $fans['headimgurl'];
                $value['nickname'] = $fans['nickname'];
                pdo_update('wlmerchant_member', array('avatar' => $value['avatar'], 'nickname' => $value['nickname']), array('id' => $value['id']));
            }
            if ($value['distributorid']>0) {
                $tjmid = pdo_getcolumn(PDO_NAME . 'distributor', array('id' => $value['distributorid']), 'leadid');
                if ($tjmid > 0) {
                    $value['tjname'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $tjmid), 'nickname');
                }else{
                    $value['tjname'] = "- 无 -";
                }
            }
            //统计订单和金额
            $dealnum1 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_rush_order') . " WHERE mid = {$value['id']} AND uniacid = {$_W['uniacid']} AND status IN (1,2,3,4,6,8,9)");
            $dealnum2 = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_order') . " WHERE mid = {$value['id']} AND uniacid = {$_W['uniacid']} AND status IN (1,2,3,4,6,8,9)");
            $dealnum = $dealnum2 + $dealnum1;

            $dealmoney1 = pdo_fetchcolumn('SELECT SUM(actualprice) FROM ' . tablename('wlmerchant_rush_order') . " WHERE mid = {$value['id']} AND uniacid = {$_W['uniacid']} AND status IN (1,2,3,4,6,8,9)");
            $dealmoney2 = pdo_fetchcolumn('SELECT SUM(price) FROM ' . tablename('wlmerchant_order') . " WHERE mid = {$value['id']} AND uniacid = {$_W['uniacid']} AND status IN (1,2,3,4,6,8,9)");
            $dealmoney = $dealmoney1 + $dealmoney2;
            $value['dealnum'] = $dealnum;
            $value['dealmoney'] = $dealmoney;
            pdo_update('wlmerchant_member', array('dealnum' => $dealnum, 'dealmoney' => $dealmoney), array('id' => $value['id']));
        }

        /* 输出表头 */
        $filter = array(
            'id'  => '用户MID',//U
            'nickname' => '用户昵称',//A
            'realname'  => '真实姓名',//B
            'mobile' => '手机号',//E
            'source' => '渠道',
            'credit1' => '积分',//C
            'credit2' => '余额',//D
            'createtime' => '注册时间',//F
            'dealnum' => '商品订单量',//G
            'dealmoney' => '商品订单金额',//H
            'followst' => '关注状态',
            'card_number' => '银行卡账号',
            'bank_name' => '开户行',
            'alipay'    => '支付宝账号',
            'tjname'    => '推荐人'
        );

        $data = array();
        for ($i=0; $i < count($list) ; $i++) {
            foreach ($filter as $key => $title) {
                $data[$i][$key] = $list[$i]['id'];
                if ($key == 'createtime') {
                    $data[$i][$key] = "\t".date('Y-m-d H:i:s', $list[$i][$key])."\t";
                }else {
                    $data[$i][$key] = $list[$i][$key];
                }
            }
        }
        util_csv::export_csv_2($data, $filter, '用户表.csv');
        exit();
    }
    //用户详细信息
    public function memberDetail() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if ($_W['ispost']) {
            $data = is_array($_GPC['data']) ? $_GPC['data'] : array();

            if (!empty($data['mobile'])) {
                $m = pdo_fetch('select id from ' . tablename('wlmerchant_member') . ' where mobile=:mobile and uniacid=:uniaicd limit 1 ', array(':mobile' => $data['mobile'], ':uniaicd' => $_W['uniacid']));
                if (!empty($m) && $m['id'] != $id) {
                    show_json(0, '此手机号已绑定其他用户!(mid:' . $m['id'] . ')');
                }
            }

            $data['password'] = trim($data['password']);
            if (!empty($data['password'])) {
                $salt = pdo_getcolumn(PDO_NAME . 'member', array('id' => $id, 'uniacid' => $_W['uniacid']), 'salt');
                if (empty($salt)) {
                    $salt = random(8);
                }
                $data['password'] = md5($data['password'] . $salt);
                $data['salt'] = $salt;
            } else {
                unset($data['password']);
                unset($data['salt']);
            }

            pdo_update('wlmerchant_member', $data, array('id' => $id, 'uniacid' => $_W['uniacid']));
            show_json(1);
        }
        $member = Member::wl_member_get($id);
        $result = mc_fansinfo($member['openid']);
        if (!empty($result)) {
            $member['follow'] = $result['follow'];
            $member['unfollowtime'] = $result['unfollowtime'];
        }
        if($member['distributorid']>0){
            $member['leadid'] = pdo_getcolumn(PDO_NAME.'distributor',array('id'=>$member['distributorid']),'leadid');
            $member['leadname'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$member['leadid']),'nickname');
        }
        $labels = pdo_getall('wlmerchant_userlabel_record',array('mid' => $id,'uniacid' => $_W['uniacid']),array('mobile'));
        //用户标签
        $sql = "SELECT a.times,b.name  FROM "
            . tablename(PDO_NAME . "userlabel_record")
            . " as a LEFT JOIN ".tablename(PDO_NAME."userlabel")
            . " as b ON a.labelid = b.id  "
            ."WHERE a.mid = {$id} AND a.uniacid = {$_W['uniacid']}";
        $labels = pdo_fetchall($sql);

        include wl_template('member/listDetail');
    }
    //拉黑用户
    public function toblack() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $flag = intval($_GPC['flag']);
        pdo_update('wlmerchant_member', array('blackflag' => $flag), array('id' => $id, 'uniacid' => $_W['uniacid']));
        //同时删除所有帖子
        if ($flag) {
            pdo_update('wlmerchant_pocket_informations', array('status' => 3), array('mid' => $id, 'uniacid' => $_W['uniacid']));
        }
        show_json(1, array('url' => referer()));
    }
    //删除用户
    public function memberDelete() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        pdo_delete('wlmerchant_distributor', array('mid' => $id, 'uniacid' => $_W['uniacid']));
        pdo_delete('wlmerchant_member', array('id' => $id, 'uniacid' => $_W['uniacid']));
        //同步删除相关招聘信息
        pdo_delete(PDO_NAME."recruit_recruit",['recruitment_type'=>1,'release_mid'=>$id]);

        show_json(1, array('url' => referer()));
    }
    //用户账户
    public function memberRecharge() {
        global $_W, $_GPC;
        $type = trim($_GPC['type']);
        $id = intval($_GPC['id']);
        $profile = Member::wl_member_get($id);

        if ($_W['ispost']) {
            $typestr = $type == 'credit1' ? '积分' : '余额';
            $num = floatval($_GPC['num']);
            $remark = trim($_GPC['remark']) ? trim($_GPC['remark']) : '后台手动操作';

            if ($num <= 0) {
                show_json(0, array('message' => '请填写大于0的数字!'));
            }

            $changetype = intval($_GPC['changetype']);
            if ($changetype == 0) {
                $changenum = $num;
            } else {
                $changenum = 0 - $num;
            }

            $data = ($type == 'credit1') ? Member::credit_update_credit1($id, $changenum, $remark) : Member::credit_update_credit2($id, $changenum, $remark);
            if (is_error($data)) {
                show_json(0, array('message' => $data['message']));
            }

            show_json(1, array('url' => referer()));
        }

        include wl_template('member/listRecharge');
    }
    //合并用户信息
    public function membermerge() {
        global $_W, $_GPC;
        if ($_W['ispost']) {
            $cache_data = Cache::getCache('member', 'mergedata');
            if ($cache_data['total'] == $cache_data['success']) {
                Cache::deleteCache('member', 'mergedata');
                wl_json(1);
            }

            $datas = ['mobile', 'unionid'];
            foreach ($datas as $data) {
                if (!empty($cache_data['data'][$data])) {
                    Member::wl_member_merge($cache_data['data'][$data][0], $data);
                    unset($cache_data['data'][$data][0]);
                    $cache_data['data'][$data] = array_values($cache_data['data'][$data]);
                    $cache_data['success'] = $cache_data['success'] + 1;
                    break;
                }
            }

            Cache::setCache('member', 'mergedata', $cache_data);
            wl_json(0, '', array('total' => $cache_data['total'], 'success' => $cache_data['success']));
        }
        $mobiles = $this->membermerge_sql('mobile');
        $nomobile = pdo_getcolumn('wlmerchant_member', array('uniacid' => $_W['uniacid'], 'mobile' => ''), 'COUNT(id)');
        $unionids = $this->membermerge_sql('unionid');
        $nounionid = pdo_getcolumn('wlmerchant_member', array('uniacid' => $_W['uniacid'], 'unionid' => ''), 'COUNT(id)');

        $cache_data = ['total' => count($mobiles) + count($unionids),  'success' => 0, 'data' => ['mobile' => $mobiles, 'unionid' => $unionids]];
        Cache::setCache('member', 'mergedata', $cache_data);

        include wl_template('member/membermerge');
    }
    //执行用户合并操作
    private function membermerge_sql($field) {
        global $_W;
//        $members = pdo_fetchall("select `id`,`{$field}` from " . tablename('wlmerchant_member') . " WHERE `uniacid` = {$_W['uniacid']} AND `{$field}` in ( select `{$field}` from " . tablename('wlmerchant_member') . " WHERE `uniacid` = {$_W['uniacid']} AND `{$field}` <> '' group by `{$field}` having count(`mobile`) > 1);");
//        if (empty($members)) {
//            return [];
//        }
       $merge_data = [];
//        foreach ($members as $member) {
//            if (!in_array($member[$field], $merge_data)) {
//                array_push($merge_data, $member[$field]);
//            }
//        }
        $members = pdo_fetchall("select `{$field}`,count(*) as count from".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']} group by `{$field}` having count>1 ");
        foreach ($members as $mem){
            if(strlen($mem[$field])>1){
                $merge_data[] = $mem[$field];
            }
        }
        return $merge_data;
    }
    //同步用户信息
    public function membersync() {
        global $_W, $_GPC;
        if ($_W['ispost']) {
            $pindex = max(1, intval($_GPC['page']));
            $sync_fans = pdo_getslice('wlmerchant_member', array('uniacid' => $_W['uniacid']), array($pindex, 50), $total, array('id', 'openid'));
            $total = ceil($total / 50);
            foreach ($sync_fans as $sync_fan) {
                if (!empty($sync_fan['openid'])) {
                    $userinfo = Member::wl_fans_info($sync_fan['openid']);
                    Member::wl_member_update($userinfo, $sync_fan['id']);
                }
            }
            wl_json(0, '', array('pindex' => $pindex, 'total' => $total));
        }
        include wl_template('member/membersync');
    }
    //导入系统粉丝
    public function memberImport() {
        global $_W, $_GPC;
        if ($_W['ispost']) {
            $pindex = max(1, intval($_GPC['page']));
            $sync_fans = pdo_getslice('mc_mapping_fans', array('uniacid' => $_W['uniacid']), array($pindex, 50), $total, array('openid'));
            $total = ceil($total / 50);
            foreach ($sync_fans as $sync_fan) {
                if (!empty($sync_fan['openid'])) {
                    $userinfo = Member::wl_fans_info($sync_fan['openid']);
                    Member::wl_member_create($userinfo);
                }
            }
            wl_json(0, '', array('pindex' => $pindex, 'total' => $total));
        }
        include wl_template('member/memberImport');
    }
    //选择用户
    public function selectMember() {
        global $_W, $_GPC;
        $where = array();
        $keyword = trim($_GPC['keyword']);
        if ($keyword != '')
            $where['nickname^openid^uid'] = $keyword;
        $dsData = Util::getNumData('nickname,avatar,openid', PDO_NAME . 'member', $where, 'id desc', 0, 0, 0);
        $ds = $dsData[0];
        include wl_template('member/selectMember');
    }


    //用户设置
    public function userset() {
        global $_W, $_GPC;
        $settings = Setting::wlsetting_read('userset');
        $settings['plugin'] = unserialize($settings['plugin']);
        if (checksubmit('submit')) {
            $base = Util::trimWithArray($_GPC['userset']);
            $base['plugin'] = serialize($_GPC['plugin']);
            Setting::wlsetting_save($base, 'userset');
            show_json(1);
        }

        include wl_template('member/userset');
    }



    //转赠列表
    public function transferlist(){
        global $_W, $_GPC;
        #1、获取基本参数
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $start = $pindex * $psize - $psize;
        $keyword = $_GPC['keyword'];//关键字
        $keywordtype = $_GPC['keywordtype'];//关键字类型
        $timeLimit = $_GPC['time_limit'];//时间段
        $where = " a.uniacid = {$_W['uniacid']} ";

        if ($keyword) {
            if ($keywordtype == 1) {
                $where .= " AND b.id LIKE '%{$keyword}%'";
            } else if ($keywordtype == 2) {
                $where .= " AND b.nickname LIKE '%{$keyword}%'";
            } else if ($keywordtype == 3) {
                $where .= " AND b.mobile LIKE '%{$keyword}%'";
            } else if ($keywordtype == 4) {
                $where .= " AND a.allmoney > '{$keyword}'";
            } else if ($keywordtype == 5) {
                $where .= " AND a.allmoney < '{$keyword}'";
            } else if ($keywordtype == 6) {
                $where .= " AND a.id LIKE '%{$keyword}%'";
            } else if ($keywordtype == 7) {
                $where .= " AND a.title LIKE '%{$keyword}%'";
            }
        }

        if ($timeLimit) {
            $starttime = strtotime($timeLimit['start']);
            $endtime = strtotime($timeLimit['end']);
            $where .= " AND a.createtime >= {$starttime} ";
            $where .= " AND a.createtime <= {$endtime} ";
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        $limit = " LIMIT {$start},{$psize}";
        $sql = "SELECT a.id,a.mid,a.allnum,a.title,a.surplus,a.allmoney,a.money,a.createtime,b.nickname,b.avatar FROM "
            . tablename("wlmerchant_transfer_list")
            . " a LEFT JOIN " . tablename(PDO_NAME . "member") . " b ON a.mid = b.id WHERE {$where} ORDER BY a.createtime DESC";

        $total = pdo_fetchcolumn('SELECT count(a.id) FROM ' . tablename('wlmerchant_transfer_list') . " a LEFT JOIN " . tablename(PDO_NAME . "member") . " b ON a.mid = b.id WHERE {$where}");
        $pager = wl_pagination($total, $pindex, $psize);
        $lists = pdo_fetchall($sql . $limit);


        include wl_template('member/transferlist');
    }
    //转赠记录
    public function transferrecord(){
        global $_W, $_GPC;
        #1、获取基本参数
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $start = $pindex * $psize - $psize;
        $keyword = $_GPC['keyword'];//关键字
        $keywordtype = $_GPC['keywordtype'];//关键字类型
        $timeLimit = $_GPC['time_limit'];//时间段
        $where = " a.uniacid = {$_W['uniacid']} ";

        if ($keyword) {
            if ($keywordtype == 1) {
                $where .= " AND b.id LIKE '%{$keyword}%'";
            } else if ($keywordtype == 2) {
                $where .= " AND b.nickname LIKE '%{$keyword}%'";
            } else if ($keywordtype == 3) {
                $where .= " AND b.mobile LIKE '%{$keyword}%'";
            } else if ($keywordtype == 4) {
                $where .= " AND d.id LIKE '%{$keyword}%'";
            } else if ($keywordtype == 5) {
                $where .= " AND d.title LIKE '%{$keyword}%'";
            }
        }

        if ($timeLimit) {
            $starttime = strtotime($timeLimit['start']);
            $endtime = strtotime($timeLimit['end']);
            $where .= " AND a.createtime >= {$starttime} ";
            $where .= " AND a.createtime <= {$endtime} ";
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        $limit = " LIMIT {$start},{$psize}";
        $sql = "SELECT a.tid,a.mid,a.money,d.title,a.realname,a.mobile,a.createtime,b.nickname,b.avatar FROM "
            . tablename("wlmerchant_transfer_record")
            . " as a LEFT JOIN " . tablename(PDO_NAME . "member") . " as b ON a.mid = b.id "
            . " LEFT JOIN " . tablename(PDO_NAME . "transfer_list") . " as d ON a.tid = d.id WHERE {$where} ORDER BY a.createtime DESC";

        $total = pdo_fetchcolumn('SELECT count(a.id) FROM ' . tablename('wlmerchant_transfer_record') . " as a LEFT JOIN " . tablename(PDO_NAME . "member") . " as b ON a.mid = b.id LEFT JOIN " . tablename(PDO_NAME . "transfer_list") . " as d ON a.tid = d.id WHERE {$where}");


        $pager = wl_pagination($total, $pindex, $psize);
        $lists = pdo_fetchall($sql . $limit);


        include wl_template('member/transferrecord');
    }


    //导出可转赠记录

    public function exportTransfer(){
        global $_W, $_GPC;
        $mid = $_GPC['mid'];
        $nickname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$mid),'nickname');
        $transfer = [];
        //获取卡券列表
        $coupons = pdo_getall('wlmerchant_member_coupons',array('status' => 1,'uniacid' => $_W['uniacid'],'mid' => $mid),array('transferflag','id','parentid','title','sub_title'),'','ID DESC');
        if(!empty($coupons)){
            foreach ($coupons as $cou){
                $parentcoupons = pdo_get('wlmerchant_couponlist',array('id' => $cou['parentid']),array('transferstatus','transfermore'));
                if($parentcoupons['transferstatus'] > 0){
                    if(empty($cou['transferflag']) || $parentcoupons['transfermore'] > 0) {
                        $obj = [
                            'id' =>  $cou['id'],
                            'type'  => 1,
                            'title' => $cou['title'],
                            'desc'  => $cou['sub_title'],
                            'mobile' => ''
                        ];
                        $transfer[] = $obj;
                    }
                }
            }
        }
        //获取红包列表
        $redpacks = pdo_getall('wlmerchant_redpack_records',array('status' => 0,'uniacid' => $_W['uniacid'],'mid' => $mid),array('transferflag','id','packid'),'','ID DESC');
        if(!empty($redpacks)){
            foreach ($redpacks as $red){
                $parent = pdo_get('wlmerchant_redpacks',array('id' => $red['packid']),array('title','transferstatus','transfermore','full_money','cut_money'));
                if($parent['transferstatus'] > 0){
                    if(empty($red['transferflag']) || $parent['transfermore'] > 0) {
                        if($parent['full_money'] > 0){
                            $desc = '满'.$parent['full_money'].'元减'.$parent['cut_money'].'元';
                        }else{
                            $desc = '立减'.$parent['cut_money'].'元';
                        }
                        $obj = [
                            'id' =>  $red['id'],
                            'type'  => 2,
                            'title' => $parent['title'],
                            'desc'  => $desc,
                            'mobile' => ''
                        ];
                        $transfer[] = $obj;
                    }
                }
            }
        }
        if(empty($transfer)){
            wl_message('用户无可转赠卡券与红包');
        }
        //导出
        /* 输出表头 */
        $filter = array(
            'id'  => '对象id',//U
            'type' => '对象类型(1卡券2红包)',//A
            'title'  => '名称',//B
            'desc' => '描述',//E
            'mobile' => '被转赠手机号'
        );
        util_csv::export_csv_2($transfer, $filter, $nickname.'可转增列表.csv');
        exit();
    }


    public function transferSend(){
        global $_W, $_GPC;
        #1、将获取基本信息
        $name = $_GPC['name'];//文件储存路径
        $fullName = PATH_ATTACHMENT . $name;//文件在本地服务器暂存地址
        #2、读取excel中的内容
        $info = util_csv::read_csv_lines($fullName, 999, 0);
        unlink($fullName);//获取文件信息后将.cvs文件删除
        #3、对读取到的信息进行处理
        foreach ($info as $k => &$v) {
            //3-1 判断是否存在数据 不存在是空行，不进行任何操作
            if (!is_array($v)) {
                unset($info[$k]);
                continue;
            }
            //3-2 编码转换  由gbk转为urf-8
            $separator = '*separator*';//分割符 写成长字符串 防止出错
            $encodres = mb_detect_encoding(implode($separator, $v), array("ASCII","GB2312","GBK","UTF-8"));
            if($encodres != 'UTF-8'){
                $v = explode($separator, iconv('gbk', 'utf-8', implode($separator, $v)));
            }
            //处理转赠送
            $inRes = 0;
            if (empty($v[4])){
                $v['send_result'] = '无手机号，不转赠';
                continue;
            }
            $getMember = pdo_get('wlmerchant_member',array('mobile' => $v[4],'uniacid' => $_W['uniacid']),array('id','nickname'));
            if(empty($getMember)){
                $v['send_result'] = '手机号不存在，无法转赠';
                continue;
            }
            if($v[1] == 1){
                $coupon = pdo_get('wlmerchant_member_coupons',array('id' => $v[0]),array('mid','parentid','status','title','orderno','transferflag'));
                $omid = $coupon['mid'];
                if($getMember['id'] == $coupon['mid']){
                    $v['send_result'] = '不能转赠给自己';
                    continue;
                }
                if($coupon['status'] != 1){
                    $v['send_result'] = '卡券状态错误,无法转赠';
                    continue;
                }
                if(empty($coupon['transferflag'])){
                    pdo_update('wlmerchant_order',array('status' => 2),array('orderno' => $coupon['orderno'],'status' => 1));  //修改订单状态
                }
                $res1 = pdo_update('wlmerchant_member_coupons',array('mid' => $getMember['id'],'transferflag' => 1),array('id' => $v[0]));  //修改卡券所属用户
                $res3 = pdo_update('wlmerchant_smallorder',array('mid' => $getMember['id']),array('orderno' => $coupon['orderno']));  //修改子订单状态
                if(empty($res1) || empty($res3)){
                    $v['send_result'] = '转赠失败，请联系管理员';
                    continue;
                }
            }else{
                $redapck = pdo_get('wlmerchant_redpack_records',array('id' => $v[0]),array('mid','packid','status','transferflag'));
                $omid = $redapck['mid'];
                if($getMember['id'] == $redapck['mid']){
                    $v['send_result'] = '不能转赠给自己';
                    continue;
                }
                if($redapck['status'] != 0){
                    $v['send_result'] = '红包状态错误,无法转赠';
                    continue;
                }
                $res1 = pdo_update('wlmerchant_redpack_records',array('mid' => $getMember['id'],'transferflag' => 1),array('id' => $v[0]));  //修改红包所属用户
                if(empty($res1)){
                    $v['send_result'] = '转赠失败，请联系管理员';
                    continue;
                }
            }
            //生成转赠记录
            $data = [
                'uniacid' => $_W['uniacid'],
                'type'    => $v[1],
                'objid'   => $v[0],
                'omid'    => $omid,
                'nmid'    => $getMember['id'],
                'status'  => 1,
                'createtime' => time(),
                'gettime' => time(),
                'transfermode' => 3,
                'mobile'  => $v[4]
            ];
            $inRes = pdo_insert(PDO_NAME . 'transferRecord', $data);
            if($inRes > 0){
                $v['send_result'] = '转赠成功';
            }else{
                $v['send_result'] = '转赠失败，请联系管理员';
            }
        }

        #4、定义结果表格的标题
        $filter = array(
            0 => '对象id',
            1 => '对象类型',
            2 => '名称',
            3 => '描述',
            4 => '转赠人手机号',
            'send_result' => '转赠结果',
        );
        #5、返回批量发货的结果信息表
        util_csv::save_csv($info, $filter, $_W['uniacid'].'/'.date('Y-m-d',time()).'/'.'批量转赠结果信息'.date('Y-m-d',time()).'.csv');
        util_csv::export_csv_2($info, $filter, '批量转赠结果信息'.date('Y-m-d',time()).'.csv');
    }






}
