<?php
/**
 * Created by PhpStorm.
 * User: 微连
 * Date: 2022/8/18
 * Time: 9:33
 */

defined('IN_IA') or exit('Access Denied');

class Hotel_WeliamController {


    //房间列表
    public function roomList() {
        global $_W, $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = " a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";

        if($_GPC['status']  >  0){
            $status = $_GPC['status'];
            if($status == 8){
                $where .= " AND a.status = 0";
            }else{
                $where .= " AND a.status = {$status}";
            }
        }
        if($_GPC['roomtype']  >  0){
            $roomtype = $_GPC['roomtype'];
            $where .= " AND a.roomtype = {$roomtype}";
        }
        $keywordtype = $_GPC['keywordtype'];
        $keyword = trim($_GPC['keyword']);
        if(!empty($keyword)){
            if($keywordtype == 1){
                $where .= " AND a.name LIKE '%{$keyword}%'";
            }else if($keywordtype == 2){
                $where .= " AND b.storename LIKE '%{$keyword}%'";
            }else if($keywordtype == 3){
                $where .= " AND a.id = {$keyword}";
            }else if($keywordtype == 4){
                $where .= " AND b.id = {$keyword}";
            }
        }

        $sql = "SELECT a.id,a.sid,b.storename,b.logo,a.name,a.roomtype,a.price,a.roomnum,a.thumb,a.status FROM ".tablename(PDO_NAME."hotel_room")
            ." as a LEFT JOIN".tablename(PDO_NAME."merchantdata")." as b ON a.sid = b.id";
        $sql .= " WHERE {$where} ORDER BY a.sort DESC,id DESC";
        $limit = " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list  = pdo_fetchall($sql.$limit);




        include wl_template('hotel/roomList');
    }

    //房间编辑

    public function roomEdit() {
        global $_W, $_GPC;

        $id = $_GPC['id'];
        if($id > 0){

            $info = pdo_get('wlmerchant_hotel_room',array('id' => $id));
            //商户信息
            $merchantdata = pdo_get('wlmerchant_merchantdata',array('id' => $info['sid']),array('storename','logo'));
            $info['storename'] = $merchantdata['storename'];
            $info['storelogo'] = $merchantdata['logo'];
            //图集
            $info['images'] = unserialize($info['images']);
            //标签
            $info['facilities'] = unserialize($info['facilities']);
            $info['service'] = unserialize($info['service']);
            $info['level'] = unserialize($info['level']);
            //数组处理
            $viparray = unserialize($info['viparray']);
            $disarray = unserialize($info['disarray']);
            //套房
            if($info['roomtype'] == 2){
                $suite = unserialize($info['suite']);
            }
        }else{
            $info = [
                'bednum' => 1,
            ];
            $suite = ['room' => 1,'office' => 1,'wei' => 1,'kitchen' => 1];
        }



        $facilities = pdo_getall('wlmerchant_hotel_label',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'type' => 1),array('id','title'));
        $service = pdo_getall('wlmerchant_hotel_label',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'type' => 2),array('id','title'));
        //会员等级
        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");
        //满减活动
        if(p('fullreduce')){
            $fullreducelist = pdo_getall('wlmerchant_fullreduce_list',array('aid' => $_W['aid'],'uniacid' => $_W['uniacid']),array('id','title'));
        }
        //支付有礼
        if(p('paidpromotion')){
            $paidlist = pdo_getall('wlmerchant_payactive',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));
        }
        //锦鲤抽奖
        if(agent_p('luckydraw')){
            $drawlist = pdo_getall('wlmerchant_luckydraw',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));
        }
        //分销设置
        $distriset = p('distribution') ? Setting::wlsetting_read('distribution') : [];
        //分销商等级
        if($distriset['switch'] > 0){
            $dislevel = pdo_getall('wlmerchant_dislevel', array('uniacid' => $_W['uniacid']),['id','name']);
        }

        if ($_W['ispost']) {
            $room = $_GPC['info'];
            if(empty($room['sid'])){
                wl_message('商户错误,请选择商户');
            }
            if(empty(trim($room['name']))){
                wl_message('请输入房间名');
            }
            if(empty($room['thumb'])){
                wl_message('请设置房间缩略图');
            }
            if(empty($room['images'])){
                wl_message('请设置房间图集');
            }
            if(empty($room['price'])){
                wl_message('请设置房间价格');
            }
            if(empty($room['size'])){
                wl_message('请设置房间面积');
            }
            if(empty($room['roomnum']) && $room['roomtype'] != 3 ){
                wl_message('请设置房间数量');
            }
            $room['images'] = serialize($room['images']);
            if($room['roomtype'] == 2){
                $suite = $_GPC['suite'];
                $room['suite'] = serialize($suite);
            }else{
                if(empty($room['bednum'])){
                    wl_message('房间床位数量');
                }
            }
            //房间设施
            $facilities = $_GPC['facilities'];
            if(!empty($facilities)){
                $room['facilities'] = serialize($facilities);
            }
            //房间服务
            $service = $_GPC['service'];
            if(!empty($service)){
                $room['service'] = serialize($service);
            }
            //会员等级
            $level = $_GPC['level'];
            if(!empty($level)){
                $room['level'] = serialize($level);
            }

            //会员减免
            if($room['vipstatus'] == 1){
                $viparray = [];
                $vipleid = $_GPC['vipleid'];
                $vipprice = $_GPC['vipprice'];
                $storeset = $_GPC['storeset'];
                foreach($vipleid as $key => $vle){
                    $vipa['vipprice'] = sprintf("%.2f",$vipprice[$key]);
                    if(is_store()){
                        $vipa['storeset'] = $vipa['vipprice'];
                    }else{
                        $vipa['storeset'] = sprintf("%.2f",$storeset[$key]);
                    }
                    $viparray[$vle] = $vipa;
                }
                $room['viparray'] = serialize($viparray);
            }
            //分销商分佣数组
            if(empty($room['isdistri'])){
                $disarray = [];
                $disleid = $_GPC['disleid'];
                $onedismoney = $_GPC['onedismoney'];
                $twodismoney = $_GPC['twodismoney'];
                foreach($disleid as $dkey => $dle){
                    $dlea['onedismoney'] = sprintf("%.2f",$onedismoney[$dkey]);
                    $dlea['twodismoney'] = sprintf("%.2f",$twodismoney[$dkey]);
                    $disarray[$dle] = $dlea;
                }
                $room['disarray'] = serialize($disarray);
            }

            if($id > 0){
                $res = pdo_update('wlmerchant_hotel_room',$room,array('id' => $id));
            }else{
                $store = pdo_get('wlmerchant_merchantdata',array('id' => $room['sid']),array('uniacid','aid'));
                $room['uniacid'] = $store['uniacid'];
                $room['aid'] = $store['aid'];
                $room['createtime'] = time();
                $res = pdo_insert(PDO_NAME . 'hotel_room', $room);
            }


            if($res){
                //修改商户最低金额
                if($room['roomtype'] == 1){
                    $hoteprice = pdo_getcolumn(PDO_NAME.'hotel_room',array('sid'=>$room['sid'],'roomtype'=>1,'status'=> 1),'MIN(price)');
                    pdo_update('wlmerchant_merchantdata',array('hotelprice' => $hoteprice),array('id' => $room['sid']));
                }

                wl_message('房间保存成功',web_url('hotel/hotel/roomList'), 'success');
            }else{
                wl_message('房间保存失败，请重试');
            }
        }


        include wl_template('hotel/roomEdit');
    }

    /**
     * Comment: 搜索商户
     * Author: wlf
     * Date: 2022/08/04 15:17
     */
    function selectMerchant() {
        global $_W, $_GPC;
        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $where['aid'] = $_W['aid'];
        $where['enabled'] = 1;
        $where['hotelstatus'] = 1;
        if ($_GPC['keyword'])
            $where['@storename@'] = $_GPC['keyword'];
        $merchants = Rush::getNumMerchant('id,storename,logo', $where, 'ID DESC', 0, 0, 0);
        $merchants = $merchants[0];
        if(!empty($merchants)){
            foreach ($merchants as & $chant){
                $chant['logo'] = tomedia($chant['logo']);
            }
        }

        include  wl_template('goodshouse/selectMerchant');
    }

    //标签列表
    public function labelList() {
        global $_W, $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $data = array();
        $data['uniacid'] = $_W['uniacid'];
        if($_W['aid'] > 0) $data['aid'] = $_W['aid'];

        if($_GPC['name']) $data['title'] = $_GPC['name'];
        if($_GPC['type']) $data['type'] = $_GPC['type'];

        $info = Util::getNumData('*', PDO_NAME . 'hotel_label', $data, 'ID DESC', $pindex, $psize, 1);
        $list = $info[0];
        foreach($list as $key => &$val){
            $val['image'] = tomedia($val['image']);
        }
        $pager = $info[1];

        include wl_template('hotel/labelList');
    }

    //标签编辑
    public function labelEdit() {
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] ?? 0;
        //获取信息
        $info = pdo_get(PDO_NAME."hotel_label",['id'=>$id]);

        if ($_W['ispost']) {
            $service = $_GPC['info'];
            if(empty($service['title'])){
                wl_message('请输入标题');
            }
            if($id > 0){
                $res = pdo_update(PDO_NAME."hotel_label",$service,array('id' => $id));

            }else{
                $service['uniacid'] = $_W['uniacid'];
                $service['aid'] = $_W['aid'];
                $res = pdo_insert(PDO_NAME."hotel_label",$service);
                $id = pdo_insertid();
            }

            if($res){
                wl_message('信息保存成功',web_url('hotel/hotel/labelList'), 'success');
            }else{
                wl_message('信息保存失败，请重试');
            }

        }


        include wl_template('hotel/labelEdit');
    }



    //导入默认标签
    public function labelImport(){
        global $_W, $_GPC;
        $array = Hotel::defaultLabel();
        foreach ($array as $ar){
            $ar['uniacid'] = $_W['uniacid'];
            $ar['aid'] = $_W['aid'];
            $ar['sort'] = 0;
            pdo_insert(PDO_NAME . 'hotel_label', $ar);
        }
        show_json(1, "操作成功");
    }

    //清理所有标签
    public function labelEmpty(){
        global $_W, $_GPC;
        pdo_delete(PDO_NAME . 'hotel_label',array('uniacid'=> $_W['uniacid'],'aid' => $_W['aid']));
        show_json(1, "操作成功");
    }

    //标签删除
    public function labelDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."hotel_label",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }

    //设置项目
    public function hotelset(){
        global $_W,$_GPC;
        $headlist = pdo_getall('wlmerchant_headline_class',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'head_id' => 0,'state' => 1),array('id','name'));

        if (checksubmit('submit')) {
            $set = $_GPC['set'];
            $set['agreement'] = htmlspecialchars_decode($set['agreement']);
            Setting::agentsetting_save($set, 'hotel');
            wl_message('保存设置成功！', referer(), 'success');
        }
        $set = Setting::agentsetting_read('hotel');


        include wl_template('hotel/baseSet');
    }


}