<?php
defined('IN_IA') or exit('Access Denied');

class WlcouponModuleUniapp extends Uniapp {
    /**
     * Comment: 获取卡券信息列表
     * Author: zzw
     * Date: 2019/8/7 9:45
     */
    public function homeList(){
        global $_W,$_GPC;
        #1、参数获取
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * $page_index - $page_index;
        $lng = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度
        $lat = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度
        $is_total = $_GPC['is_total'] ? : 0;//0=不获取总页数；1=获取总页数
        $status   = $_GPC['status'] ? : '';//获取状态
        $is_vip   = $_GPC['is_vip'] ? : 0;//是否获取专属卡券

        $storeCateId = $_GPC['cate_id'] ?: 0;
        $time = $_GPC['time'] ?: time();//时间筛选
        $week = date("w", $time);//当前时间的星期
        if ($week == 0) $week = 7;//星期天时值的转换
        $toDay = date("j", $time);//当前时间的日期

        $set = Setting::agentsetting_read("pluginlist");
        $sort = $_GPC['sort'] ? : $set['kqsort'];

        #2、生成基本查询条件
        $where = " (a.aid = {$_W['aid']} or a.extflag = 1 ) AND a.uniacid = {$_W['uniacid']} AND a.is_show = 0";

        if ($storeCateId > 0) {
            $storeids = pdo_getall('wlmerchant_merchant_cate', ['onelevel' => $storeCateId], array('sid'), 'sid');
            if($sort != 2){
                $where .= " AND b.sid in (" . implode(',', array_keys($storeids)) . ") ";
            }else{
                $where .= " AND b.id in (" . implode(',', array_keys($storeids)) . ") ";
            }
        }

        $where .= " AND CASE a.usedatestatus
                        WHEN 1 THEN a.`week` LIKE '%\"{$week}\"%'
                        WHEN 2 THEN a.`day` LIKE '%\"{$toDay}\"%'
                        WHEN 0 THEN a.id > 0
                    END ";

        if (!empty($status)) {
            $ids = explode(',', $status);
            if (count($ids) > 1) {
                $where .= " AND a.status IN ({$status}) ";
            } else {
                $where .= " AND a.status = {$status}  ";
            }
        } else {
            $where .= " AND a.status = 2 ";
        }
        if($is_vip == 1) $where .= " AND (a.vipstatus IN (1,2) OR extflag = 1) ";
        if($sort == 8){
            //排序方式为 8 时。为获取免费卡券 排序方式改为 2 按照距离排序，并且添加条件为只获取免费卡券
            $sort = 2;
            $where .= " AND a.is_charge != 1 ";
        }
        #4、生成排序条件
        switch ($sort) {
            case 1:$order = " ORDER BY a.createtime DESC ";break;//创建时间
            case 2:break;//店铺距离
            case 3:$order = " ORDER BY a.indexorder DESC ";break;//默认排序
            case 4:$order = " ORDER BY a.pv DESC ";break;//浏览人气
            case 5:$order = " ORDER BY buy_num DESC ";break;//商品销量
            case 6:$order = " ORDER BY a.indexorder DESC,buy_num DESC ";break;//精选  推荐、销量排序
            case 7:$order = " ORDER BY a.pv DESC,buy_num DESC ";break;//最热  浏览量、销量排序
        }
        #5、获取商品列表
        if($sort != 2){
            //普通查询
            $sql = "SELECT a.id,a.id as goods_id,IFNULL(sum(b.num),0) as buy_num FROM "
                . tablename(PDO_NAME . "couponlist")
                . " as a LEFT JOIN ".tablename(PDO_NAME."order")
                . " as b ON a.id = b.fkid AND b.plugin = 'coupon' AND b.uniacid = {$_W['uniacid']} AND b.status IN (0,1,2,3,6,7,9,4,8) "
                ."WHERE {$where} GROUP BY a.id {$order} ";
            if($is_total == 1) $total = count(pdo_fetchall($sql));
            $info = pdo_fetchall($sql." LIMIT {$page_start},{$page_index} ");
        }else{
            //关联店铺查询
            $info = pdo_fetchall("SELECT a.id as goods_id,b.id,b.location FROM "
                                 . tablename(PDO_NAME."couponlist")
                                 ." as a LEFT JOIN "
                                 .tablename(PDO_NAME."merchantdata")
                                 ." as b ON a.merchantid = b.id WHERE {$where} ");
            $info = Store::getstores($info, $lng, $lat, 2);
            if($is_total == 1) $total = count($info);
            $info = array_slice($info,$page_start,$page_index);
        }
        #6、循环处理信息
        foreach ($info as $key => &$val) {
            //获取最新的商品信息
            $val = WeliamWeChat::getHomeGoods(5, $val['goods_id']);
            if($val['extflag'] == 1){
                $val['url'] = $val['extlink'];
                $val['exdetail'] = pdo_getcolumn(PDO_NAME.'couponlist',array('id'=>$val['id']),'description');
                $val['exdetail'] = htmlspecialchars_decode($val['exdetail']);
            } else{
                $val['url'] = h5_url('pages/subPages/goods/index',['type'=>5,'id'=>$val['id']]);
            }
            //当商品信息中带有sid时添加店铺链接
            if ($val['sid']) {
                $val['shop_url'] = h5_url('pages/mainPages/store/index',['sid'=>$val['sid']]);
                $val['distance'] = Store::shopLocation($val['sid'], $lng, $lat);
            }
            //处理金额
            if($is_vip > 0){
                $val['price'] = sprintf("%.2f",$val['price'] - $val['discount_price']);
            }
            //删除多余的信息
            unset($val['user_list']);
            unset($val['address']);
            unset($val['user_num']);
            unset($val['totalnum']);
            unset($val['status']);
            unset($val['surplus']);
            unset($val['extflag']);
            unset($val['extlink']);
            unset($val['extinfo']);
        }
        #7、获取总页数
        if($is_total == 1){
            $data['total'] = ceil($total / $page_index);
            $data['list']  = $info;
            $this->renderSuccess('卡券信息列表',$data);
        }

        $this->renderSuccess('卡券信息列表',$info);
    }
    /**
     * Comment: 获取已购买优惠券使用详细信息
     * Author: zzw
     * Date: 2019/8/14 11:45
     */
    public function detail(){
        global $_W,$_GPC;
        #1、获取参数信息
        $id = intval($_GPC['id']) OR $this->renderError('缺少参数：用户卡券id');
        $order_id = intval($_GPC['order_id']) OR $this->renderError('缺少参数：order_id');
        #2、获取卡券详细信息
        $info = pdo_fetch("SELECT b.logo,b.title,a.status,a.orderno,m.storename,b.merchantid,a.endtime,b.sub_title,b.description,a.usetimes,m.verkey FROM "
                          .tablename(PDO_NAME."member_coupons")
                          ." as a LEFT JOIN ".tablename(PDO_NAME."couponlist")
                          ." as b ON a.parentid = b.id LEFT JOIN " .tablename(PDO_NAME."merchantdata")
                          ." as m ON b.merchantid = m.id WHERE a.id = {$id} ") OR $this->renderError('卡券不存在!');
        $info['endtime'] = date("Y-m-d H:i:s",$info['endtime']);
        $info['logo'] = tomedia($info['logo']);
        if(empty($info['verkey'])){
            $info['is_pwd'] = 0;
        }else{
            $info['is_pwd'] = 1;
        }
        #3、获取核销码信息
        $smalls = pdo_getall('wlmerchant_smallorder',array('orderno' => $info['orderno'],'plugin'=>'coupon','status'=>1),array('checkcode','orderid'));
        $code_list = array();
        foreach ($smalls as $sm){
            $code_list[] = $sm['checkcode'];
        }
        $info['code_list'] = $code_list;//核销码列表
        #3、生成二维码
        if(empty($order_id)){
            $order_id = $smalls[0]['orderid'];
        }
        if($_W['source'] == 3){
            $showurl = 'pages/mainPages/orderWrite/orderWrite?id='.$order_id.'&plugin=coupon';
            $logo = tomedia(pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$info['merchantid']),'logo'));
            $info['url'] = tomedia(Store::getShopWxAppQrCode($info['merchantid'],$logo,$showurl));
        }else{
            $url = h5_url('pages/mainPages/orderWrite/orderWrite', ['id'=>$order_id,'plugin'=>'coupon']);
            $info['url'] = WeliamWeChat::getQrCode($url);
        }

        $this->renderSuccess('优惠券详细信息',$info);
    }

}
