<?php
defined('IN_IA') or exit('Access Denied');

class Pocket {
    /**
     * 从数据库获取幻灯片数据
     */
    static function getslides($uniacid) {
        global $_W, $_GPC;
        $psize = 25;
        $pindex = max(1, $_GPC['page']);
        $data = Util::getNumData("*", PDO_NAME . 'pocket_slide', array('uniacid' => $uniacid,'aid' => $_W['aid']), 'sort desc', $pindex, $psize);
//        if ($data) {
//            foreach ($data[0] as $k => $v) {
//                if ($v['aid']) {
//                    if ($v['aid'] != $_W['aid']) {
//                        unset($data[0][$k]);
//                    }
//                }
//            }
//            return $data;
//        }
        return $data;
    }
    //创建订单
    static function saveFightOrder($data, $param = array()) {
        global $_W;
        if (!is_array($data))
            return FALSE;
        if (empty($param)) {
            pdo_insert(PDO_NAME . 'order', $data);
            return pdo_insertid();
        }
        return FALSE;
    }
    /**
     * 获取分类
     */
    static function gettypes($flag = 0) {
        global $_W, $_GPC;
        $uniacid = $_W['uniacid'];
        if ($flag == 'all') {
            $data = Util::getNumData("*", PDO_NAME . 'pocket_type', array('uniacid' => $uniacid,'aid'=>$_W['aid'] ,'type' => 0), 'sort desc', 0, 0);
        } else {
            $data = Util::getNumData("*", PDO_NAME . 'pocket_type', array('uniacid' => $uniacid,'aid'=>$_W['aid'] ,'type' => 0, 'isnav' => 0), 'sort desc', 0, 0);
        }
        if (!empty($data)) {
            $data = $data[0];
            foreach ($data as $key => &$value) {
                //$value['httitle'] = urlencode($value['title']);
                $value['httitle'] = $value['title'];
                if ($value['aid']) {
                    if ($value['aid'] != $_W['aid']) {
                        unset($data[$key]);
                    } else {
                        $temp = Util::getNumData("*", PDO_NAME . 'pocket_type', array('type' => $value['id']), 'sort desc');
                        $temp = $temp[0];
                        foreach($temp as &$te){
                            //$te['httitle'] = urlencode($te['title']);
                            $te['httitle'] = $te['title'];
                        }
                        $data[$key]['children'] = $temp;
                    }
                } else {
                    $temp = Util::getNumData("*", PDO_NAME . 'pocket_type', array('type' => $value['id']), 'sort desc');
                    $data[$key]['children'] = $temp[0];
                }
            }
            return $data;
        }
        return null;
    }
    /**
     * 根据id检查某一级分类是否含有二级分类
     */
    static function checkFType($id) {
        global $_W, $_GPC;

        $uniacid = $_W['uniacid'];
        $data = null;
        if ($id) {
            $data = Util::getSingelData("*", PDO_NAME . 'pocket_type', array('uniacid' => $uniacid, 'type' => $id));
            if ($data) {
                return true;
            }
        }
        return false;
    }
    /**
     * 获取发帖信息
     */
    static function getInformations($id = 0) {
        global $_W, $_GPC;

        $uniacid = $_W['uniacid'];

        if (!$id) {
            $data = Util::getNumData("*", PDO_NAME . 'pocket_informations', array('uniacid' => $uniacid));
        } else {
            $data = Util::getSingelData("*", PDO_NAME . 'pocket_informations', array('uniacid' => $uniacid, 'id' => $id));
        }

        if ($data) {
            return $data;
        }

        return null;
    }
    /**
     * 根据帖子id获取评论
     */
    static function getcomments($id) {
        global $_W, $_GPC;

        $uniacid = $_W['uniacid'];

        if ($id) {
            $data = Util::getNumData("*", PDO_NAME . 'pocket_comment', array('uniacid' => $uniacid, 'tid' => $id));
        }

        if ($data) {
            return $data;
        }

        return null;
    }
    /**
     * 根据评论id获取回复
     */
    static function getreplys($id) {
        global $_W, $_GPC;

        $uniacid = $_W['uniacid'];

        if ($id) {
            $data = Util::getNumData("*", PDO_NAME . 'pocket_reply', array('uniacid' => $uniacid, 'cid' => $id));
        }

        if ($data) {
            return $data;
        }

        return null;
    }
    /**
     * 根据类型获得帖子
     */
    static function getInfoByType($id) {
        global $_W, $_GPC;

        $uniacid = $_W['uniacid'];
        $data = null;
        if ($id) {
            $data = Util::getNumData("*", PDO_NAME . 'pocket_informations', array('type' => $id));
        }
        if ($data) {
            return $data;
        }
        return null;
    }
    //异步支付结果回调 ，处理业务逻辑
    static function payPocketshargeNotify($params) {
        global $_W;
        Util::wl_log('payResult_notify', PATH_PLUGIN . "pocket/data/", $params); //写入异步日志记录
        $order = pdo_get('wlmerchant_order', array('orderno' => $params['tid']), array('id','fightstatus','mid', 'num', 'price', 'orderno', 'fkid', 'aid', 'status','uniacid'));
        $_W['uniacid'] = $order['uniacid'];
        if ($order['id']) {
            $inform = self::getInformations($order['fkid']);
            $type = pdo_get('wlmerchant_pocket_type',array('id' => $inform['type']),array('isdistri','onedismoney','twodismoney'));
            //处理分销
            if (p('distribution')) {
                $_W['aid'] = $order['aid'];
                if ($inform['redpack'] > 0) {
                    $disprice = sprintf("%.2f", $order['price'] - $inform['redpack']);
                } else {
                    $disprice = $order['price'];
                }
                if ($disprice > 0 && empty($type['isdistri'])) {
                    $disorderid = Distribution::disCore($order['mid'], $disprice, $type['onedismoney'], $type['twodismoney'], 0, $order['id'], 'pocket', 1);
                }
            }
            if(empty($disorderid)){
                $disorderid = 0;
            }
            $data['disorderid'] = $disorderid;
            $data['status'] = 3;
            $data['paytime'] = time();
            //$paytype = array('credit' => 1, 'wechat' => 2, 'alipay' => 3, 'delivery' => 4, 'wxapp' => 5);
            $data['paytype'] = $params['type'];
            if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
            if($order['fightstatus'] == 3){
                $data['issettlement'] = 1;
            }
            $res = pdo_update('wlmerchant_order', $data, array('id' => $order['id']));
            if ($res && $order['fightstatus'] != 3) {
                Store::ordersettlement($order['id']);
            }
            if ($inform['endtime'] > time()) {
                $endtime = $inform['endtime'] + $order['num'] * 24 * 3600;
            } else {
                $endtime = time() + $order['num'] * 24 * 3600;
            }
            $data = array(
                'top'     => 1,
                'endtime' => $endtime
            );
            if ($inform['redpack'] > 0) {
                $data['redpackstatus'] = 1;
            }
            pdo_update('wlmerchant_pocket_informations', $data, array('id' => $order['fkid']));


        }

    }
    //异步支付结果回调 处理用户界面
    static function payPocketshargeReturn($params) {
        $res = $params['result'] == 'success' ? 1 : 0;
        $order = pdo_get('wlmerchant_order', array('orderno' => $params['tid']), array('fkid'));
        $url = h5_url('pages/subPages/postDetails/postDetails',['id'=>$order['fkid']]);
        if ($res) {
            wl_message('支付成功', $url, 'success');
        } else {
            wl_message('您已支付该订单', $url, 'error');
        }

    }
    static function payPocketfabushargeNotify($params) {
        global $_W;
        Util::wl_log('payResult_notify', PATH_PLUGIN . "pocket/data/", $params); //写入异步日志记录
        $order = pdo_get('wlmerchant_order', array('orderno' => $params['tid']), array('id','uniacid','fightstatus','mid', 'num', 'price', 'orderno', 'fkid', 'aid', 'status'));
        $_W['uniacid'] = $order['uniacid'];
        $tiezi = pdo_get('wlmerchant_pocket_informations',array('id' => $order['fkid']),array('status','endtime','type','mid','aid'));
        if ($order['id']) {
            $inform = self::getInformations($order['fkid']);
            if (empty($_W['aid'])) {
                $_W['aid'] = $order['aid'];
            }
            $set = Setting::agentsetting_read('pocket');
            $inf = array();
            if($order['fightstatus'] == 1){
                if($inform['status'] == 5){
                    if ($set['passstatus']) {
                        $inf['status'] = 0;
                    } else {
                        $inf['status'] = 1;
                    }
                }
                $type = pdo_get('wlmerchant_pocket_type',array('id' => $inform['type']),array('isdistri','onedismoney','twodismoney'));
                $onedismoney = $type['onedismoney'];
                $twodismoney = $type['twodismoney'];
                if (p('distribution') && empty($type['isdistri'])) {
                    $disorderid = Distribution::disCore($order['mid'], $order['price'], $onedismoney, $twodismoney, 0, $order['id'], 'pocket', 1);
                }
            }else if($order['fightstatus'] == 2){
                $day = $order['num'];
                $inf['top'] = 1;
                if($tiezi['endtime']>time()){
                    $inf['endtime'] = $tiezi['endtime'] + $day * 24 * 3600;
                }else{
                    $inf['endtime'] = time() + $day * 24 * 3600;
                }
                $inf['refreshtime'] = time();
                $onedismoney = 0;
                $twodismoney = 0;
                if (p('distribution')) {
                    $disorderid = Distribution::disCore($order['mid'], $order['price'],$onedismoney,$twodismoney,0,$order['id'],'pocket',1);
                }
            }else if($order['fightstatus'] == 3){
                $inf['redpackstatus'] = 1;
                $inf['redpack'] = $inf['sredpack'] = $order['price'];
                $inf['package'] = $order['num'];
            }else if($order['fightstatus'] == 4){
                $inf['refreshtime'] = time();
                $onedismoney = 0;
                $twodismoney = 0;
                if (p('distribution')) {
                    $disorderid = Distribution::disCore($order['mid'], $order['price'],$onedismoney,$twodismoney,0,$order['id'],'pocket',1);
                }
            }else if($order['fightstatus'] == 5){
                $payinfo = [
                    'pocketid' => $order['fkid'],
                    'mid'      => $order['mid'],
                    'createtime' => time()
                ];
                pdo_insert(PDO_NAME . 'pocket_payvideo', $payinfo);
            }else if($order['fightstatus'] == 6){
                $inf['fullchnnel'] = 1;
            }else if($order['fightstatus'] == 7){
                $inf['transferstatus'] = 2;
                //转让金给用户
                Member::credit_update_credit2($inform['mid'],$inform['transfermoney'],'同城转让费',$order['orderno']);
                $data['issettlement'] = 1;
            }
            pdo_update('wlmerchant_pocket_informations', $inf, array('id' => $order['fkid']));
            if(empty($disorderid)){
                $disorderid = 0;
            }
            $data = array(
                'status'     => 3,
                'disorderid' => $disorderid,
                'paytime'    => time()
            );
            //$paytype = array('credit' => 1, 'wechat' => 2, 'alipay' => 3, 'delivery' => 4, 'wxapp' => 5);
            $data['paytype'] = $params['type'];
            if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
            if($order['fightstatus'] == 3){
                $data['issettlement'] = 1;
            }
            $res = pdo_update('wlmerchant_order', $data, array('id' => $order['id']));
            if ($res && $order['fightstatus'] != 3 && $order['fightstatus'] != 7) {
                Store::ordersettlement($order['id']);
            }
            if($order['fightstatus'] == 1){
                if ($inf['status']) {
                    self::examinenotice($order['fkid']);
                } else {
                    self::examinenotice($order['fkid'], 1);
                }
            }
        }
    }
    static function payPocketfabushargeReturn($params) {
        $res = $params['result'] == 'success' ? 1 : 0;
        $order = pdo_get('wlmerchant_order', array('orderno' => $params['tid']), array('fkid'));
        $url = h5_url('pages/subPages/postDetails/postDetails',['id'=>$order['fkid']]);
        if ($res) {
            wl_message('支付成功', $url, 'success');
        } else {
            wl_message('您已支付该订单', $url, 'error');
        }

    }
    static function payPocketredpackNotify($params) {
        global $_W;
        Util::wl_log('payResult_notify', PATH_PLUGIN . "pocket/data/", $params); //写入异步日志记录
        $order = pdo_get('wlmerchant_order', array('orderno' => $params['tid']), array('id', 'mid', 'num', 'price','fightstatus','orderno', 'fkid', 'aid', 'status', 'package','uniacid'));
        $_W['uniacid'] = $order['uniacid'];
        if ($order['status'] == 0) {
            $inform = self::getInformations($order['fkid']);
            if (empty($_W['aid'])) {
                $_W['aid'] = $order['aid'];
            }
            pdo_update('wlmerchant_pocket_informations', array('redpackstatus' => 1), array('id' => $order['fkid']));
            $data = array('status' => 3, 'paytime' => time());
            //$paytype = array('credit' => 1, 'wechat' => 2, 'alipay' => 3, 'delivery' => 4, 'wxapp' => 5);
            $data['paytype'] = $params['type'];
            if ($params['tag']['transaction_id']) {
                $data['transid'] = $params['tag']['transaction_id'];
            }
            if($order['fightstatus'] == 3){
                $data['issettlement'] = 1;
            }
            $res = pdo_update('wlmerchant_order', $data, array('id' => $order['id']));
            if ($res && $order['fightstatus'] != 3) {
                Store::ordersettlement($order['id']);
            }
        }
    }
    static function payPocketredpackReturn($params) {
        $res = $params['result'] == 'success' ? 1 : 0;
        $order = pdo_get('wlmerchant_order', array('orderno' => $params['tid']), array('fkid'));
        $url = h5_url('pages/subPages/postDetails/postDetails',['id'=>$order['fkid']]);
        if ($res) {
            wl_message('支付成功', $url, 'success');
        } else {
            wl_message('您已支付该订单', $url, 'error');
        }


    }
    //获取所有帖子
    static function getNumTiezi($select, $where, $order, $pindex, $psize, $ifpage) {
        $goodsInfo = Util::getNumData($select, PDO_NAME . 'pocket_informations', $where, $order, $pindex, $psize, $ifpage);
        return $goodsInfo;
    }
    //获取所有订单
    static function getNumOrder($select, $where, $order, $pindex, $psize, $ifpage) {
        $goodsInfo = Util::getNumData($select, PDO_NAME . 'order', $where, $order, $pindex, $psize, $ifpage);
        return $goodsInfo;
    }
    static function commentnotice($commentid, $comment, $mid) {
        global $_W;
        $amid = pdo_getcolumn(PDO_NAME . 'pocket_informations', array('id' => $commentid), 'mid');
        $openid = pdo_getcolumn(PDO_NAME . 'member', array('id' => $amid), 'openid');
        $smnane = pdo_getcolumn(PDO_NAME . 'member', array('id' => $mid), 'nickname');
        $msg = $smnane . '对您的帖子评论道:' . "\n";
        $msg .= $comment . "\n";
        $msg .= '快去看看吧！' . "\n";

        $time = pdo_getcolumn(PDO_NAME."pocket_comment",['id'=>$commentid],'createtime');
        $url = h5_url('pages/subPages/postDetails/postDetails',['id'=>$commentid]);
        $modelData = [
            'first'   => $smnane . '对您的帖子进行了评论',
            'type'    => '评论提醒' ,//业务类型
            'content' => $comment,//业务内容
            'status'  => '等待回复' ,//处理结果
            'time'    => date("Y-m-d H:i:s",$time) ,//操作时间
            'remark'  => '快去看看吧！'
        ];
        TempModel::sendInit('service',$amid,$modelData,$_W['source'],$url);
    }
    static function replaynotice($amid, $comment, $mid, $cid) {
        global $_W;
        $smnane = pdo_getcolumn(PDO_NAME . 'member', array('id' => $mid), 'nickname');
        $tid = pdo_getcolumn(PDO_NAME . 'pocket_comment', array('id' => $cid), 'tid');
        $replyData = [
            'first'   => '用户'.$smnane.'回复您的帖子',
            'type'    => '帖子回复' ,//业务类型
            'content' => $comment,//业务内容
            'status'  => '已回复',//处理结果
            'time'    => date('Y-m-d H:i:s', time()),//操作时间
            'remark'  => '点击查看帖子'
        ];
        $url = h5_url('pages/subPages/postDetails/postDetails',array('id'=>$tid));
        TempModel::sendInit('service',$amid,$replyData,$_W['source'],$url);
    }
    static function examinenotice($commentid, $flag = 0) {
        global $_W;
        $comment = pdo_get(PDO_NAME . 'pocket_informations', array('id' => $commentid), array('nickname', 'type', 'createtime'));
        $typename = pdo_getcolumn(PDO_NAME . 'pocket_type', array('id' => $comment['type']), 'title');
        //给代理商管理员发送模板消息通知
        $url = h5_url('pages/subPages/postDetails/postDetails',array('id'=>$commentid,'examine' => 1));
        if(empty($_W['areaname'])){
            $areaid = pdo_getcolumn(PDO_NAME.'oparea',array('aid'=>$_W['aid']),'areaid');
            $_W['areaname'] =  pdo_getcolumn(PDO_NAME.'area',array('id'=>$areaid),'name');
        }
        $first   = '您好，用户['.$comment['nickname'].']在[' .$_W['areaname'].']发布了一个同城信息';//消息头部
        $type    = "帖子发布";//业务类型
        $content = '帖子分类:'.$typename;//业务内容
        $status  = $flag ? '已发布' : '待审核';//处理结果
        $remark  = "请尽快处理!";//备注信息
        $time    = $comment['createtime'];//操作时间
        News::noticeAgent('pocketfabu',$_W['aid'],$first,$type,$content,$status,$remark,$time,$url);
    }

    static function passnotice($informationsid) {
        global $_W;
        $informations = pdo_get('wlmerchant_pocket_informations', array('id' => $informationsid), array('mid', 'type', 'nickname', 'status', 'reason', 'createtime'));
        $typename = pdo_getcolumn(PDO_NAME . 'pocket_type', array('id' => $informations['type']), 'title');
        if ($informations['status']) {
            $result = '已驳回';
            $remark = '您发布的信息已被驳回,驳回原因是:' . $informations['reason'] . ',请您重新发布';
            $url = h5_url('pages/subPages/myPost/myPost', array('status' => 2));
        } else {
            $result = '已通过';
            $remark = '您发布的信息已通过审核,点击查看帖子';
            $url = h5_url('pages/subPages/postDetails/postDetails',array('id'=>$informationsid));
        }
        $replyData = [
            'first'   => '您好，您发布的帖子已审核',
            'type'    => '帖子审核' ,//业务类型
            'content' => '帖子分类:'.$typename,//业务内容
            'status'  => $result,//处理结果
            'time'    => date('Y-m-d H:i:s', time()),//操作时间
            'remark'  => $remark
        ];
        TempModel::sendInit('service',$informations['mid'],$replyData,$_W['source'],$url);
    }
    //计划任务
    static function doTask() {
        global $_W;
        //置顶时间过期，自动下线
        $tops = pdo_getall('wlmerchant_pocket_informations', array('top' => 1));
        foreach ($tops as $key => $v) {
            if ($v['endtime'] < time()) {
                pdo_update('wlmerchant_pocket_informations', array('top' => 0), array('id' => $v['id']));
            }
        }
        //添加回复时间
        $noreplytime = pdo_fetchall("SELECT createtime,id FROM ".tablename('wlmerchant_pocket_informations')."WHERE replytime = 0 LIMIT 20 ");
        if(!empty($noreplytime)){
            foreach($noreplytime as $time){
                $colltime = pdo_getcolumn(PDO_NAME.'pocket_comment',array('tid'=>$time['id'],'status' => 1),'createtime');
                $retime = pdo_getcolumn(PDO_NAME.'pocket_reply',array('tid'=>$time['id'],'status' => 1),'createtime');
                $createtime = $time['createtime'];
                $newtime = max($colltime,$retime,$createtime);
                pdo_update('wlmerchant_pocket_informations',array('replytime' => $newtime),array('id' => $time['id']));
            }
        }
        //删除未支付订单
//        $config = Setting::agentsetting_read('pocket');
//        if ($config['delete'] > 0) {
//            $deletetime = time() - $config['delete'] * 3600;
//            $tiezis = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_pocket_informations') . "WHERE uniacid = {$_W['uniacid']} AND status = 5 AND createtime < {$deletetime}");
//            if ($tiezis) {
//                foreach ($tiezis as $key => &$tie) {
//                    $res = pdo_delete('wlmerchant_order', array('fkid' => $tie['id'], 'plugin' => 'pocket'));
//                    if ($res) {
//                        pdo_delete('wlmerchant_pocket_informations', array('id' => $tie['id']));
//                    }
//                }
//            }
//        }

        //重新统计回复帖子id
        $replys = pdo_fetchall("SELECT cid,id FROM " . tablename('wlmerchant_pocket_reply') . "WHERE tid = 0 ORDER BY id DESC LIMIT 20");
        if ($replys) {
            foreach ($replys as $key => $va) {
                $tid = pdo_getcolumn(PDO_NAME . 'pocket_comment', array('id' => $va['cid']), 'tid');
                pdo_update('wlmerchant_pocket_reply', array('tid' => $tid), array('id' => $va['id']));
            }
        }

    }


    /**
     * Comment: 判断是否为黑名单用户
     * Author: zzw
     * Date: 2019/8/22 10:30
     * @param $id int 帖子id
     * @return bool
     */
    public static function is_black($id){
        global $_W;
        $is_black = pdo_get(PDO_NAME."pocket_blacklist" ,['mid'=>$_W['mid'],'inid'=>$id]);
        if($is_black) return true;//是黑名单用户
        else return false;//不是黑名单用户
    }
    /**
     * Comment: 获取掌上信息评论信息
     * Author: zzw
     * Date: 2019/8/29 14:38
     * @param $page
     * @param $pageIndex
     * @param $id
     * @return array|bool
     */
    public static function getComment($page,$pageIndex,$id){
        global $_W;
        if(empty($_W['mid'])){
            $_W['mid'] = 0;
        }
        $pageStart = $page * $pageIndex - $pageIndex;
        #2、获取评论信息列表
        $total = pdo_fetchcolumn("SELECT count(*) FROM "
            .tablename(PDO_NAME."pocket_comment") ." as a RIGHT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id WHERE a.tid = {$id} AND a.status = 1 ");
        $list = pdo_fetchall("SELECT a.id,a.content,a.mid,FROM_UNIXTIME(a.createtime,'%Y-%m-%d %H-%i') as createtime,b.nickname,b.avatar FROM "
            .tablename(PDO_NAME."pocket_comment") ." as a RIGHT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id WHERE a.tid = {$id} AND (a.status = 1 OR a.mid = {$_W['mid']}) ORDER BY a.createtime DESC LIMIT {$pageStart},{$pageIndex}");
        #3、获取回复信息
        if(is_array($list) && count($list) > 0){
            foreach($list as $key => &$val){
                $val['list'] = pdo_fetchall("SELECT a.id,a.smid,b.nickname as reply_name,m.nickname as name,a.content FROM ".tablename(PDO_NAME."pocket_reply")
                    ." as a RIGHT JOIN ".tablename(PDO_NAME."member")
                    ." as b ON a.smid = b.id RIGHT JOIN ".tablename(PDO_NAME."member")
                    ." as m ON a.amid = m.id WHERE a.tid = {$id} AND (a.status = 1 OR a.smid = {$_W['mid']}) AND a.cid = {$val['id']} ORDER BY a.createtime ASC ");
            }
        }
        return ['total'=>ceil($total / $pageIndex),'list'=>$list];
    }

    /**
     * Comment: 切个信息 只保留几条
     * Author: wlf
     * Date: 2022/07/08 10:37
     */
    public static function cutComment($commebts,$num){
        global $_W;
        $now = 0;
        foreach($commebts as $key => &$cm){
            $now = $now + 1;
            if($now > $num){
                unset($commebts[$key]);
            }else{
                if(!empty($cm['list'])){
                    foreach ($cm['list'] as $kk => $li){
                        $now = $now + 1;
                        if($now > $num){
                            unset($cm['list'][$kk]);
                        }
                    }
                }
            }
        }
        return $commebts;
    }

    /**
     * Comment: 红包算法
     * Author: zzw
     * Date: 2019/8/29 17:03
     * @param $balance  float   红包余额
     * @param $surplus  float   剩余的包
     * @return string   float   返回一个红包中拥有的金额
     */
    public static function redEnvelopeAlgorithm($balance, $surplus,$redpagestatus = 0) {
        if ($surplus > 1) {
            if($redpagestatus > 0){
                //普通红包平均分
                $money = round($balance/$surplus,2);
            }else{
                //根据红包算法获取当前用户应该领取的红包
                $total = 0;//循环获取的随机数的总和
                $frequency = 5;//循环的次数
                $maxMoney = 0;//随机数的最大值
                //通过循环获取平均值 让红包的金额不会太大也不会太小
                $meanValue = $balance / $surplus * 2;//在每个包的平均值浮动两倍中取随机值  保证每个红包的金额差距不大
                for ($i = 0; $i < $frequency; $i++) {
                    $rand = mt_rand() / mt_getrandmax();
                    $value = sprintf("%0.2f", $rand * $meanValue);
                    $total += $value;
                    if ($value > $maxMoney) {
                        $maxMoney = $value;
                    }
                }
                //去掉最大值 让数字比较平均 出现领取红包的金额数量依次递减的情况减少
                $total = sprintf("%0.2f", $total - $maxMoney);
                //通过平均数 获取红包的具体金额
                $money = sprintf("%0.2f", $total / 4);
                //判断：当红包金额等于小于0或者等于大于余额时 从新获取红包金额
                $currentBalance = $balance - $money;
                $minBalance = 0.01 * ($surplus - 1);
                if($money <= 0){
                    $money = 0.01;
                }
                if ($money > $balance || $currentBalance < $minBalance) {
                    self::redEnvelopeAlgorithm($balance, $surplus);
                }
            }
        } else {
            //只剩下一个包  则当前用户获得剩下的所有金额
            $money = $balance;
        }
        return $money;
    }
    /**
     * Comment: 红包领取列表
     * Author: zzw
     * Date: 2019/8/29 18:32
     * @param     $id
     * @param int $page
     * @param int $pageIndex
     * @return mixed
     */
    public static function getGetList($id,$page = 1,$pageIndex = 10){
        global $_W,$_GPC;
        $data['total'] = 0;
        $data['list'] = [];
        #1、获取开始查询的位置
        $pageStart = $page * $pageIndex - $pageIndex;
        #2、条件生成
        $where =  " WHERE a.uniacid = {$_W['uniacid']} AND a.pid = {$id} ORDER BY a.gettime DESC ";
        #3、获取手气最佳领取的金额
        $max = pdo_fetchcolumn( "SELECT MAX(money) FROM ".tablename(PDO_NAME."red_envelope")." as a ".$where);
        if($max > 0){
            #4、获取总数量
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename(PDO_NAME."red_envelope")
                ." as a LEFT JOIN ".tablename(PDO_NAME."member")
                ." as b ON a.mid = b.id ".$where);
            $data['total'] = ceil($total / $pageIndex);//总页数
            #5、获取红包基本信息
            $info = pdo_get(PDO_NAME."pocket_informations",['id'=>$id],['package']);
            $info['surplus'] = ($info['package'] - $total) > 0 ? ($info['package'] - $total) : 0;
            $data['info'] = $info;//基本信息
            #6、获取领取列表
            $sql = "SELECT a.money,b.nickname,b.avatar,FROM_UNIXTIME(a.gettime,'%Y-%m-%d %H:%i:%S') as gettime,
                CASE WHEN a.money = {$max} THEN 1
                     ELSE 0
                END as is_optimum FROM ".tablename(PDO_NAME."red_envelope")
                ." as a LEFT JOIN ".tablename(PDO_NAME."member")
                ." as b ON a.mid = b.id ".$where." LIMIT {$pageStart},{$pageIndex}";
            $data['list'] = pdo_fetchall($sql);//信息列表

            foreach ($data['list'] as &$mem){
                $mem['nickname'] = mb_substr($mem['nickname'],0,1).'***'.mb_substr($mem['nickname'],-1);
            }

        }

        return $data;
    }
    /**
     * Comment: 将数转为带单位的字符串
     * Author: zzw
     * Date: 2019/10/29 14:35
     * @param int $num      要转换的数
     * @param int $limit    转换限制，当数大于该限制时才会进行转换
     * @param int $hierarchy    返回的层级（例如转换111111，限制10000。返回层级1时返回10万，2时返回10万1千，3时返回10万1千1百）
     * @return string
     */
    public static function rounding($num,$limit,$hierarchy = 1){
        //定义基本信息
        $content = [];
        $lv_1 = 100000000;//亿
        $lv_2 = 10000000;//千万
        $lv_3 = 10000;//万
        $lv_4 = 1000;//千
        $lv_5 = 100;//百
        $lv_6 = 10;//十
        if($num > $limit){
            # 亿
            if($num > $lv_1 && $hierarchy > count($content)){
                $content[] = floor($num / $lv_1).'亿';
                $num  = intval($num % $lv_1);
            }
            # 千万
            if($num > $lv_2 && $hierarchy > count($content)){
                $content[] = floor($num / $lv_2).'千万';
                $num  = intval($num % $lv_2);
            }
            # 万
            if($num > $lv_3 && $hierarchy > count($content)){
                $content[] = floor($num / $lv_3).'万';
                $num  = intval($num % $lv_3);
            }
            # 千
            if($num > $lv_4 && $hierarchy > count($content)){
                $content[] = floor($num / $lv_4).'千';
                $num  = intval($num % $lv_4);
            }
            # 百
            if($num > $lv_5 && $hierarchy > count($content)){
                $content[] = floor($num / $lv_5).'百';
                $num  = intval($num % $lv_5);
            }
            # 十
            if($num > $lv_6 && $hierarchy > count($content)){
                $content[] = floor($num / $lv_6).'十';
                $num  = intval($num % $lv_6);
            }
            # 个
            if($num > 0 && $hierarchy > count($content)){
                $content[] = $num;
            }
        }

        return implode($content);
    }
    /**
     * Comment: 发送评论信息模板消息通知
     * Author: zzw
     * Date: 2020/3/16 14:09
     * @param int $id       帖子id
     * @param int $cid      评论id
     * @param int $source   渠道信息
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function setModelInfo($id,$cid,$source = 1){
        #1、基本信息获取
        $sendMid = pdo_getcolumn(PDO_NAME."pocket_informations",['id'=>$id],'mid');//获取发帖人id
        $comment = pdo_get(PDO_NAME."pocket_comment",['id'=>$cid],['mid','createtime']);//获取评论人id
        $nickname = pdo_getcolumn(PDO_NAME."member",['id'=>$comment['mid']],'nickname');//获取评论人昵称
        #2、发送模板消息通知用户
        $modelData = [
            'first'   => '' ,
            'type'    => '评论提醒' ,//业务类型
            'content' => '用户['.$nickname.']对您的帖子进行了评论!' ,//业务内容
            'status'  => '待回复' ,//处理结果
            'time'    => date("Y-m-d H:i:s",$comment['createtime']) ,//操作时间
            'remark'  => ''
        ];
        $link = h5_url('pages/subPages/postDetails/postDetails',['id'=>$id]);
        TempModel::sendInit('service',$sendMid,$modelData,$source,$link);
    }
    /**
     * Comment: 发送回复信息模板消息通知
     * Author: zzw
     * Date: 2020/3/16 14:41
     * @param int $tid      帖子id
     * @param int $rid      回复信息id
     * @param int $smid     回复人id（发布回复信息的用户的id）
     * @param int $amid     被回复人id（接收回复信息的用户的id）
     * @param int $source   渠道信息
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function setReplyModelInfo($tid,$rid,$smid,$amid,$source = 1){
        #1、基本信息获取
        $nickname = pdo_getcolumn(PDO_NAME."member",['id'=>$smid],'nickname');//获取回复人昵称
        $time = pdo_getcolumn(PDO_NAME."pocket_reply",['id'=>$rid],'createtime');//回复时间
        #2、发送模板消息通知用户
        $modelData = [
            'first'   => '您的评论被回复了' ,
            'type'    => '评论回复提醒' ,//业务类型
            'content' => '用户['.$nickname.']对您的评论进行了回复!' ,//业务内容
            'status'  => '待回复' ,//处理结果
            'time'    => date("Y-m-d H:i:s",$time) ,//操作时间
            'remark'  => ''
        ];
        $link = h5_url('pages/subPages/postDetails/postDetails',['id'=>$tid]);
        TempModel::sendInit('service',$amid,$modelData,$source,$link);
    }


    public static function getLinkGoods($linkid,$linkplugin){
        switch ($linkplugin) {
            case '1':
                $goods =  pdo_fetch("SELECT name,thumb,price FROM ".tablename('wlmerchant_rush_activity')."WHERE id = {$linkid} ");
                break;
            case '2':
                $goods =  pdo_fetch("SELECT name,thumb,price FROM ".tablename('wlmerchant_groupon_activity')."WHERE id = {$linkid} ");
                break;
            case '3':
                $goods =  pdo_fetch("SELECT name,logo as thumb,price FROM ".tablename('wlmerchant_fightgroup_goods')."WHERE id = {$linkid} ");
                break;
            case '4':
                $goods =  pdo_fetch("SELECT name,thumb,price FROM ".tablename('wlmerchant_bargain_activity')."WHERE id = {$linkid} ");
                break;
            case '5':
                $goods =  pdo_fetch("SELECT title as name,logo as thumb,price FROM ".tablename('wlmerchant_couponlist')."WHERE id = {$linkid} ");
                break;
            case '6':
                $goods =  pdo_fetch("SELECT storename as name,logo as thumb,0 as price FROM ".tablename('wlmerchant_merchantdata')."WHERE id = {$linkid} ");
                break;
            case '7':
                $goods =  pdo_fetch("SELECT title as name,thumb,price FROM ".tablename('wlmerchant_activitylist')."WHERE id = {$linkid} ");
                break;
        }
        $data['linkname'] = $goods['name'];
        $data['thumb'] = tomedia($goods['thumb']);
        return $data;
    }

    /**
     * 1500定制-根据用户会员等级获取查阅金额
     */
    public static function getShowMoney($userhalfcard,$videoprice){
        if(empty($userhalfcard)){
            $levelid = 'no';
        }else{
            $levelid = $userhalfcard['levelid'];
        }
        $videoprice = unserialize($videoprice);
        if(empty($userhalfcard)){
            $money = $videoprice['no'];
        }else{
            $money = $videoprice[$levelid];
        }
        $money = $money ? : 0;
        return $money;
    }
    /**
     * 1500定制-根据用户操作赠送积分
     */
    public static function giveCredit($id,$type,$mid,$set,$minute = 0,$credit = 0){
        //获取应得积分
        global $_W;
        $getflag =  pdo_getcolumn(PDO_NAME.'pocket_credit',array('mid'=>$mid,'pocketid' => $id,'type' => $type ,'minute' => $minute),'id');
        if(empty($getflag)){
            if($type == 2){
                $credit = $set['credit_like'];
                $remark = '点赞帖子获赠';
            }else if($type == 3){
                $credit = $set['credit_comment'];
                $remark = '评论帖子获赠';
            }else if($type == 4){
                $credit = $set['credit_follow'];
                $remark = '关注商户获赠';
            }else{
                $remark = '观看视频获赠';
            }
            //查看本日已获得积分
            $today = strtotime(date('Y-m-d'));
            $daycredit = pdo_fetchcolumn('SELECT SUM(number) FROM '.tablename('wlmerchant_pocket_credit')." WHERE mid = {$mid} AND uniacid = {$_W['uniacid']} AND createtime > {$today}");
            $supcredit = $set['credit_day'] - $daycredit;
            $credit = $credit > $supcredit ? $supcredit : $credit;
            if($credit > 0){
                $recode = [
                    'uniacid' => $_W['uniacid'],
                    'mid'     => $mid,
                    'type'    => $type,
                    'minute'  => $minute,
                    'number'  => $credit,
                    'createtime	' => time(),
                    'pocketid' => $id
                ];
                pdo_insert(PDO_NAME . 'pocket_credit', $recode);
                Member::credit_update_credit1($mid,$credit,$remark);
            }else{
                $credit = 0;
            }
        }else{
            $credit = 0;
        }
        return $credit;
    }

    /**
     * 1500定制-邀请用户进入页面
     */
    public static function invitationRecord($mid,$invmid,$set){
        //获取应得积分
        global $_W;
        if($mid > 0 && $invmid > 0 && $invmid != $mid){
            $getflag =  pdo_getcolumn(PDO_NAME.'pocket_invitation',array('mid'=>$mid,'invmid' => $invmid),'id');
            if(empty($getflag)){
                $data = [
                    'uniacid' => $_W['uniacid'],
                    'mid'     => $mid,
                    'invmid'  => $invmid,
                    'createtime' => time()
                ];
                pdo_insert(PDO_NAME . 'pocket_invitation', $data);
                if($set['credit_invitation'] > 0){
                    $remark = '邀请好友进入掌上信息赠送';
                    Member::credit_update_credit1($invmid,$set['credit_invitation'],$remark);
                }
            }
        }
    }
    
    /**
     * Comment: 获取装修组件单条帖子信息
     * Author: wlf
     * Date: 2022/11/08 17:09
     */
    public static function getsingleInfo($id){
    	global $_W;
    	$set = Setting::agentsetting_read('pocket');
    	 //判断会员权限
        $userhalfcard = WeliamWeChat::VipVerification($_W['mid']);
        if($set['vip_show'] > 0){
            $vip_level = unserialize($set['vip_level']);
            if(empty($userhalfcard)){
                $noVip = 1;
            }else if(!empty($vip_level) && !in_array($userhalfcard['levelid'],$vip_level)){
                $noVip = 1;
            }
        }

    	
    	$info = pdo_fetch("SELECT id,avatar,mid,nickname,top,FROM_UNIXTIME(createtime,'%Y-%m-%d %H:%i') as createtime,img,phone,content,video_link,look,share,package,likeids,onetype,`type`,keyword,videoprice,linkplugin,linkid,transferstatus,transfermoney,refreshtime FROM ".tablename(PDO_NAME."pocket_informations") ." WHERE id = {$id}");
            
        $fabulous = unserialize($info['likeids']);
        if(!empty($info['refreshtime'])){
            $info['createtime'] = date("Y-m-d H:i",$info['refreshtime']);
        }
        $videoInfo = UploadFile::videoInfoHandle($info['video_link']);
        $info['video_link'] = $videoInfo['link'];
        $info['video_img_link'] = $videoInfo['img_link'];

        //获取点赞数量
        $info['fabulous_num'] = is_array($fabulous) ? count($fabulous) : 0;
        //判断当前用户是否已经点赞   0=未点赞  1=已点赞
        if(is_array($fabulous) && in_array($_W['mid'],$fabulous)) $info['is_fabulous'] = 1;
        else $info['is_fabulous'] = 0;
        //获取点赞用户的头像
        $info['fabulous_avatar'] = [];
        if($info['fabulous_num'] > 0){
            foreach($fabulous as $item){
                $info['fabulous_avatar'][] = pdo_getcolumn(PDO_NAME."member",['id'=>$item],'avatar');
            }
        }
        //从新定义头像链接
        if($info['mid'] > 0){
            $meminfo = pdo_get('wlmerchant_member',array('id' => $info['mid']),array('nickname','avatar','encodename'));
            if(!empty($meminfo)){
                $info['nickname'] = !empty($meminfo['encodename']) ? base64_decode($meminfo['encodename']) : $meminfo['nickname'];
                $info['avatar'] = $meminfo['avatar'];
            }
        }

        //图片处理
        $info['img'] = is_array(unserialize($info['img'])) ? unserialize($info['img']) : [];
        if(is_array($info['img']) && count($info['img']) > 0){
            foreach ($info['img'] as $imgK => &$imgV){
                if(empty($imgV)){
                    unset($info['img'][$imgK]);
                }else{
                    $imgV = tomedia($imgV);
                }
            }
        }
        //处理标签
        $info['keyword'] = unserialize($info['keyword']);
        //分类获取
        if($info['onetype'] > 0) $info['onetype'] = pdo_getcolumn(PDO_NAME."pocket_type",['id'=>$info['onetype']],'title');
        else $info['onetype'] = '';
        if($info['type'] > 0){
            $info['type'] = pdo_getcolumn(PDO_NAME."pocket_type",['id'=>$info['type']],'title');
        } else{
            $info['type'] = '官方公告';
            $info['onetype'] = '官方公告';
            $info['nickname'] = $set['kefu_name'];
            $info['avatar'] = $set['kefu_avatar'];
        }
        $info['avatar'] = tomedia($info['avatar']);
        //评论获取 获取三条
        $info['comment_list'] = Pocket::getComment(1,3,$info['id']);
        $info['comment_list']['list'] = Pocket::cutComment($info['comment_list']['list'],3);
        unset($info['likeids']);
        //修改首页加载时的浏览量
        if(empty($set['listadd'])){
            $addLook = rand(intval($set['minup']),intval($set['maxup']));
            if($addLook<1){
                $addLook = 1;
            }
            pdo_update(PDO_NAME."pocket_informations",['look'=>$info['look']+$addLook],['id'=>$info['id']]);
        }
        //查询认证和保证金
        if(p('attestation')){
            $info['attestation'] = Attestation::checkAttestation(1,$info['mid']);
        }else{
            $info['attestation'] = 0;
        }
        //查阅权限
        if($noVip > 0 || empty($info['phone'])){
            unset($info['phone']);
        }
        //关联信息
        if($info['linkid'] > 0){
            $linkinfo = Pocket::getLinkGoods($info['linkid'],$info['linkplugin']);
            $info['linkname'] = $linkinfo['linkname'];
            $info['linkthumb'] = tomedia($linkinfo['thumb']);
        }
        //1500定制信息处理
        $showmoney = Pocket::getShowMoney($userhalfcard,$info['videoprice']);
        if($showmoney != 0){
            $videoflag = pdo_getcolumn(PDO_NAME.'pocket_payvideo',array('pocketid'=>$info['id'],'mid'=>$_W['mid']),'id');
            if(empty($videoflag)){
                $info['video_link'] = '';
                $info['video_img_link'] = '';
                $info['img'] = array_splice($info['img'],0,4);
            }
        }  
        return $info;
    }
    
    
    /**
     * Comment: 获取装修组件单条帖子信息
     * Author: wlf
     * Date: 2022/11/08 18:39
     */
    public static function getNumberInfo($num,$sort,$type){
    	global $_W;
    	$set = Setting::agentsetting_read('pocket');
    	 //判断会员权限
        $userhalfcard = WeliamWeChat::VipVerification($_W['mid']);
        if($set['vip_show'] > 0){
            $vip_level = unserialize($set['vip_level']);
            if(empty($userhalfcard)){
                $noVip = 1;
            }else if(!empty($vip_level) && !in_array($userhalfcard['levelid'],$vip_level)){
                $noVip = 1;
            }
        }
        #2、生成查询条件
        $where = " WHERE (aid = {$_W['aid']} OR fullchnnel > 0)  AND uniacid = {$_W['uniacid']} AND status = 0 ";
        if($type > 0 ) $where .= " AND onetype IN (0,{$type}) ";
        switch ($sort){
            case 1:
                $sort = 0;
                break;
            case 2:
                $sort = 1;
                break;
            case 3:
                $sort = 2;
                break;
            case 4:
                $sort = 3;
                break;
            case 5:
                $sort = 4;
                break;
            default:
                $sort = 0;
                break;
        }
	     switch ($sort){
	        case 0:$order = " ORDER BY top DESC,refreshtime DESC ";break;//发帖时间
	        case 1:$order = " ORDER BY top DESC,look DESC ";break;//浏览数量
	        case 2:$order = " ORDER BY top DESC,share DESC ";break;//分享数量
	        case 3:$order = " ORDER BY top DESC,likenum DESC ";break;//点赞数量
	        case 4:$order = " ORDER BY top DESC,replytime DESC ";break;//回复时间
	    }
	    #4、查询符合条件的信息列表
	    $list = pdo_fetchall("SELECT id,avatar,mid,nickname,top,FROM_UNIXTIME(createtime,'%Y-%m-%d %H:%i') as createtime,img,
                                     phone,content,video_link,look,share,package,likeids,onetype,`type`,keyword,videoprice,linkplugin,linkid,transferstatus,transfermoney,refreshtime FROM "
            .tablename(PDO_NAME."pocket_informations") .$where.$order." LIMIT {$num} ");
            
         #4、循环进行信息的处理
        if(is_array($list) && count($list) > 0){
            foreach($list as $key => &$val){
                $fabulous = unserialize($val['likeids']);
                if(!empty($val['refreshtime'])){
                    $val['createtime'] = date("Y-m-d H:i",$val['refreshtime']);
                }
                $videoInfo = UploadFile::videoInfoHandle($val['video_link']);
                $val['video_link'] = $videoInfo['link'];
                $val['video_img_link'] = $videoInfo['img_link'];

                //获取点赞数量
                $val['fabulous_num'] = is_array($fabulous) ? count($fabulous) : 0;
                //判断当前用户是否已经点赞   0=未点赞  1=已点赞
                if(is_array($fabulous) && in_array($_W['mid'],$fabulous)) $val['is_fabulous'] = 1;
                else $val['is_fabulous'] = 0;
                //获取点赞用户的头像
                $val['fabulous_avatar'] = [];
                if($val['fabulous_num'] > 0){
                    foreach($fabulous as $item){
                        $val['fabulous_avatar'][] = pdo_getcolumn(PDO_NAME."member",['id'=>$item],'avatar');
                    }
                }
                //从新定义头像链接
                if($val['mid'] > 0){
                    $meminfo = pdo_get('wlmerchant_member',array('id' => $val['mid']),array('nickname','avatar','encodename'));
                    if(!empty($meminfo)){
                        $val['nickname'] = !empty($meminfo['encodename']) ? base64_decode($meminfo['encodename']) : $meminfo['nickname'];
                        $val['avatar'] = $meminfo['avatar'];
                    }
                }

                //图片处理
                $val['img'] = is_array(unserialize($val['img'])) ? unserialize($val['img']) : [];
                if(is_array($val['img']) && count($val['img']) > 0){
                    foreach ($val['img'] as $imgK => &$imgV){
                        if(empty($imgV)){
                            unset($val['img'][$imgK]);
                        }else{
                            $imgV = tomedia($imgV);
                        }
                    }
                }
                //处理标签
                $val['keyword'] = unserialize($val['keyword']);
                //分类获取
                if($val['onetype'] > 0) $val['onetype'] = pdo_getcolumn(PDO_NAME."pocket_type",['id'=>$val['onetype']],'title');
                else $val['onetype'] = '';
                if($val['type'] > 0){
                    $val['type'] = pdo_getcolumn(PDO_NAME."pocket_type",['id'=>$val['type']],'title');
                } else{
                    $val['type'] = '官方公告';
                    $val['onetype'] = '官方公告';
                    $val['nickname'] = $set['kefu_name'];
                    $val['avatar'] = $set['kefu_avatar'];
                }
                $val['avatar'] = tomedia($val['avatar']);
                //评论获取 获取三条
                $val['comment_list'] = Pocket::getComment(1,3,$val['id']);
                $val['comment_list']['list'] = Pocket::cutComment($val['comment_list']['list'],3);
                unset($val['likeids']);
                //修改首页加载时的浏览量
                if(empty($set['listadd'])){
                    $addLook = rand(intval($set['minup']),intval($set['maxup']));
                    if($addLook<1){
                        $addLook = 1;
                    }
                    pdo_update(PDO_NAME."pocket_informations",['look'=>$val['look']+$addLook],['id'=>$val['id']]);
                }
                //查询认证和保证金
                if(p('attestation')){
                    $val['attestation'] = Attestation::checkAttestation(1,$val['mid']);
                }else{
                    $val['attestation'] = 0;
                }
                //查阅权限
                if($noVip > 0 || empty($val['phone'])){
                    unset($val['phone']);
                }
                //关联信息
                if($val['linkid'] > 0){
                    $linkinfo = Pocket::getLinkGoods($val['linkid'],$val['linkplugin']);
                    $val['linkname'] = $linkinfo['linkname'];
                    $val['linkthumb'] = tomedia($linkinfo['thumb']);
                }
                //1500定制信息处理
                $showmoney = Pocket::getShowMoney($userhalfcard,$val['videoprice']);
                if($showmoney != 0){
                    $videoflag = pdo_getcolumn(PDO_NAME.'pocket_payvideo',array('pocketid'=>$val['id'],'mid'=>$_W['mid']),'id');
                    if(empty($videoflag)){
                        $val['video_link'] = '';
                        $val['video_img_link'] = '';
                        $val['img'] = array_splice($val['img'],0,4);
                    }

                }
            }
        }    
        return $list;
    }
    
    /**
     * Comment: 优化样式
     * Author: wlf
     * Date: 2022/11/08 19:06
     */
    
    public static function befutInfo($info){
    	foreach($info as &$val){
    		if($val > 10000){
    			$val = sprintf("%.1f",$val/10000).'万';
    		}
    	}
    	return $info;
    }
    
}

