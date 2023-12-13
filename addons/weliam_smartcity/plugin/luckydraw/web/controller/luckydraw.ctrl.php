<?php
defined('IN_IA') or exit('Access Denied');
/**
 * Comment: 抽奖活动管理
 * Author: wlf
 * Class luckydraw_WeliamController
 */
class Luckydraw_WeliamController {
    /**
     * Comment: 获取抽奖活动信息列表
     * Author: wlf
     * Date: 2021/12/01 11:33
     */
    public function index(){
        global $_W,$_GPC;
        //参数获取
        $page = max(1, intval($_GPC['page']));
        $pageIndex = 10;
        $title = $_GPC['title'] ? : '';//活动名称
        $type = intval($_GPC['type']) ? : 0;//奖品类型:1=现金红包,2=线上红包,3=积分,4=激活码,5=商品
        $status = intval($_GPC['status']) ? : 0;//状态:1=开启，2=关闭
        //条件生成
        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $where['aid'] = $_W['aid'];
        if($title) $where['title@'] = "%{$title}%";
        if($type > 0){
            if($type == 2){
                $where['drawstatus'] = 0;
            }else{
                $where['drawstatus'] = $type;
            }
        }
        if($status > 0){
            if($status == 2){
                $where['status'] = 0;
            }else{
                $where['status'] = $type;
            }
        }
        //查询
        $goodslist = Util::getNumData('*','wlmerchant_luckydraw',$where,'id DESC',$page,$pageIndex,1);
        $pager = $goodslist[1];
        $list = $goodslist[0];
        //信息处理
        foreach ($list as &$li){
            $li['starttime'] = date('Y-m-d H:i',$li['starttime']);
            $li['endtime'] = date('Y-m-d H:i',$li['endtime']);
            $li['createtime'] = date('Y-m-d H:i',$li['createtime']);

        }




        include  wl_template('luckydraw/index');
    }

    /**
     * Comment: 编辑抽奖活动
     * Author: wlf
     * Date: 2021/12/02 11:36
     */
    public function edit(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        //获取奖品信息
        $prizelist = pdo_getall('wlmerchant_luckydraw_goods',array('status' => 1,'uniacid' => $_W['uniacid'],'aid' => $_W['aid']),array('id','title'));

        if($id > 0){
            $draw = pdo_get('wlmerchant_luckydraw',array('id' => $id));
            $draw['detail'] = htmlspecialchars_decode(base64_decode($draw['detail']));
            $draw['adv'] = unserialize($draw['advarray']);
        }else{
            $draw = [
              'starttime' => time(),
              'endtime' => time() + 86400*30,
            ];
        }
        if($_W['ispost']) {
            $data = $_GPC['draw'];
            $advlogo = $_GPC['advlogo'];
            $advlink = $_GPC['advlink'];
            $advarray = [];
            foreach($advlogo as $dkey => $dle){
                $dlea['thumb'] = $advlogo[$dkey];
                $dlea['link'] = $advlink[$dkey];
                $advarray[] = $dlea;
            }
            $data['advarray'] = serialize($advarray);
            $data['detail'] = base64_encode(htmlspecialchars_decode($data['detail']));
            $time = $_GPC['time'];
            $data['starttime'] = strtotime($time['start']);
            $data['endtime'] = strtotime($time['end']);
            //一堆的校验和数据规范 （组团数 中奖数 数量规范）
            if(empty($data['oneprizeid'])){
                wl_message('请设置一等奖奖品！' , referer() , 'error');
            }
            if(empty($data['oneprizenum'])){
                wl_message('请设置一等奖中奖人数！' , referer() , 'error');
            }
            if(empty($data['twoprizenum']) && !empty($data['twoprizeid'])){
                wl_message('请设置二等奖中奖人数！' , referer() , 'error');
            }
            if(empty($data['threeprizenum']) && !empty($data['threeprizeid'])){
                wl_message('请设置三等奖中奖人数！' , referer() , 'error');
            }

            if($data['drawstatus'] > 0){
                if(empty($data['drawsucnum'])){
                    $data['drawsucnum'] = 1;
                }
                $allprizenum = $data['oneprizenum'];
                if(!empty($data['twoprizeid'])){
                    $allprizenum += $data['twoprizenum'];
                }
                if(!empty($data['threeprizeid'])){
                    $allprizenum += $data['threeprizenum'];
                }
                if($data['drawcodenum'] < $allprizenum){
                    wl_message('组团抽奖码个数不能少于中奖数！' , referer() , 'error');
                }
            }

            if($id > 0){
                $res = pdo_update('wlmerchant_luckydraw',$data,array('id' => $id));
            }else{
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                $data['createtime'] = time();

                $res = pdo_insert(PDO_NAME . 'luckydraw', $data);
            }
            if ($res){
                wl_message('操作成功' , web_url('luckydraw/luckydraw/index') , 'success');
            } else{
                wl_message('操作失败，请刷新重试！' , referer() , 'error');
            }
        }
        include  wl_template('luckydraw/edit');
    }

    public function advinfo(){
        global $_W,$_GPC;
        $kw = $_GPC['kw'];
        include  wl_template('luckydraw/advinfo');
    }


    /**
     * Comment: 删除抽奖活动
     * Author: wlf
     * Date: 2021/12/06 11:24
     */
    public function delete(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR show_json(0, '参数错误，请刷新重试!');
        $mysqlFun = new MysqlFunction();
        //删除内容
        $mysqlFun->startTrans();
        $res = pdo_delete(PDO_NAME."luckydraw",['id'=>$id]);
        //if($res && pdo_count(PDO_NAME."draw_join",['draw_id'=>$id]) > 0) $res = pdo_delete(PDO_NAME."draw_join",['draw_id'=>$id]);
        if ($res) {
            $mysqlFun->commit();
            show_json(1 , '删除成功');
        }else {
            $mysqlFun->rollback();
            show_json(0 , '删除失败，请刷新重试');
        }
    }

    /**
     * Comment: 基础设置
     * Author: wlf
     * Date: 2021/12/09 16:11
     */
    public function set(){
        global $_W,$_GPC;
        $settings = Setting::agentsetting_read('luckydraw');
        if (checksubmit('submit')) {
            $base = $_GPC['set'];


            Setting::agentsetting_save($base, 'luckydraw');
            wl_message('更新设置成功！', web_url('luckydraw/luckydraw/set'));
        }

        include wl_template("luckydraw/set");


    }
    /**
     * Comment: 抽奖码列表
     * Author: wlf
     * Date: 2021/12/09 21:34
     */
    public function prizeIndex(){
        global $_W,$_GPC;
        //参数获取
        $page = max(1, intval($_GPC['page']));
        $pageIndex = 10;
        //条件筛选
        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $where['aid'] = $_W['aid'];
        $status = $_GPC['status'];
        if($status > 0){
            if($status == 5){
                $where['status'] = 0;
            }else{
                $where['status'] = $status;
            }
        }
        $recordid = $_GPC['recordid'];
        if($_GPC['keyword']){
            $keyword = $_GPC['keyword'];
            if($_GPC['keywordtype'] == 1){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where['mid#'] = $mids;
                }else{
                    $where['mid'] = 0;
                }
            }else if($_GPC['keywordtype'] == 2){
                $params[':mobile'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND mobile LIKE :mobile",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where['mid#'] = $mids;
                }else{
                    $where['mid'] = 0;
                }
            }else if($_GPC['keywordtype'] == 3){
                $where['codenum@'] = "%{$keyword}%";
            }else if($_GPC['keywordtype'] == 4){
                $where['draw_reid'] = $keyword;
            }
        }
        if($_GPC['time_limit']){
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            if($_GPC['timetype'] == 1){
                $where['drawtime>'] = $starttime;
                $where['drawtime<'] = $endtime+86400;
            }else if($_GPC['timetype'] == 2){
                $where['gettime>'] = $starttime;
                $where['gettime<'] = $endtime+86400;
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $codelist = Util::getNumData('*','wlmerchant_luckydraw_drawcode',$where,'createtime DESC',$page,$pageIndex,1);
        $pager = $codelist[1];
        $list = $codelist[0];
        foreach ($list as $key => &$li) {
            $member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('avatar','nickname','mobile'));
            $li['avatar'] = tomedia($member['avatar']);
            $li['nickname'] = $member['nickname'];
            $li['mobile'] = $member['mobile'];
            if($li['draw_goods_id'] > 0){
                $goods = pdo_get('wlmerchant_luckydraw_goods',array('id' => $li['draw_goods_id']),array('title','image'));
                $li['goodslogo'] = tomedia($goods['image']);
                $li['goodsname'] = $goods['title'];
            }

            if($li['createtime'] > 0){
                $li['createtime'] = date('Y-m-d H:i:s',$li['createtime']);
            }
            if($li['gettime'] > 0){
                $li['gettime'] = date('Y-m-d H:i:s',$li['gettime']);
            }
            if($li['drawtime'] > 0){
                $li['drawtime'] = date('Y-m-d H:i:s',$li['drawtime']);
            }

            switch ($li['plugin']){
                case 'rush':
                    $type = 1;
                    $li['pluginname'] = '抢购';
                    break;
                case 'groupon':
                    $type = 10;
                    $li['pluginname'] = '团购';
                    break;
                case 'wlfightgroup':
                    $type = 2;
                    $li['pluginname'] = '拼团';
                    break;
                case 'coupon':
                    $type = 3;
                    $li['pluginname'] = '超级券';
                    break;
                case 'bargain':
                    $type = 12;
                    $li['pluginname'] = '砍价';
                    break;
                case 'activity':
                    $type = 9;
                    $li['pluginname'] = '同城活动';
                    break;
                case 'citydelivery':
                    $type = 14;
                    $li['pluginname'] = '同城配送';
                    break;
                case 'payonline':
                    $type = 11;
                    $li['pluginname'] = '在线买单';
                    break;
                case 'drawvideo':
                    $type = 0;
                    $li['pluginname'] = '激励视频';
                    break;
                case 'drawshare':
                    $type = 0;
                    $li['pluginname'] = '分享好友';
                    break;
            }
            if($li['orderid'] > 0 && $type > 0){
                $li['url'] = web_url('order/wlOrder/orderdetail',['orderid' => $li['orderid'],'type' => $type]);
            }
            $draw_activity = pdo_get(PDO_NAME.'luckydraw',array('id'=>$li['draw_acid']),['title','twoprizenum','threeprizenum']);

            $li['drawtitle'] = $draw_activity['title'];
            $li['twoprizenum'] = $draw_activity['twoprizenum'];
            $li['threeprizenum'] = $draw_activity['threeprizenum'];

        }

        include wl_template("luckydraw/prizeIndex");
    }

    /**
     * Comment: 活动记录表
     * Author: wlf
     * Date: 2021/12/09 23:28
     */
    public function recordIndex(){
        global $_W,$_GPC;
        //参数获取
        $page = max(1, intval($_GPC['page']));
        $pageIndex = 10;
        //条件筛选
        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $where['aid'] = $_W['aid'];
        $drawid = $_GPC['activityid'];

        $where['drawid'] = $drawid;
        $status = $_GPC['status'];
        if($status > 0){
            if($status == 3){
                $where['status'] = 0;
            }else{
                $where['status'] = $status;
            }
        }
        $draw = pdo_get('wlmerchant_luckydraw',array('id' => $drawid),array('title'));
        if(empty($draw)){
            wl_message('活动id错误！', web_url('luckydraw/luckydraw/index'));
        }
        $recordlist = Util::getNumData('*','wlmerchant_luckydraw_record',$where,'createtime DESC',$page,$pageIndex,1);
        $pager = $recordlist[1];
        $list = $recordlist[0];


        include wl_template("luckydraw/recordIndex");

    }

    /**
     * Comment: 活动开奖
     * Author: wlf
     * Date: 2021/12/09 23:28
     */
    public function drawing(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $mysqlFun = new MysqlFunction();
        //删除内容
        $mysqlFun->startTrans();
        $res = Luckydraw::drawIng($id);
        if ($res) {
            $mysqlFun->commit();
            show_json(1 , '操作成功');
        }else {
            $mysqlFun->rollback();
            show_json(0 , '操作失败，请刷新重试');
        }
    }

    /**
     * Comment: 内定奖励
     * Author: wlf
     * Date: 2022/01/10 17:45
     */
    public function setPreset(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $preset = $_GPC['preset'];
        $acid = $_GPC['acid'];
        $reid = $_GPC['reid'];
        $activity = pdo_get(PDO_NAME.'luckydraw',array('id'=>$acid),['oneprizenum','twoprizenum','threeprizenum']);

        if($preset == 1){
            $maxnum = $activity['oneprizenum'];
            $tiptext = '一等奖';
        }else if($preset == 2){
            $maxnum = $activity['twoprizenum'];
            $tiptext = '二等奖';
        }else if($preset == 3){
            $maxnum = $activity['threeprizenum'];
            $tiptext = '三等奖';
        }
        //查询已内定人数
        $anum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_luckydraw_drawcode')." WHERE draw_reid = {$reid} AND preset = {$preset}");
        if($anum < $maxnum){
            $res = pdo_update('wlmerchant_luckydraw_drawcode',array('preset' => $preset),array('id' => $id));
        }else{
            show_json(0,'已内定全部'.$maxnum.'位'.$tiptext.'人数');
        }
        if($res){
            show_json(1 , '操作成功');
        }else{
            show_json(0 , '操作失败，请刷新重试');
        }
    }

    /**
     * Comment: 取消内定
     * Author: wlf
     * Date: 2022/01/10 17:56
     */
    public function cancelPreset(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $res = pdo_update('wlmerchant_luckydraw_drawcode',array('preset' => 0),array('id' => $id));
        if($res){
            show_json(1 , '操作成功');
        }else{
            show_json(0 , '操作失败，请刷新重试');
        }
    }


}