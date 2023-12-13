<?php
defined('IN_IA') or exit('Access Denied');

class GrouponModuleUniapp extends Uniapp {
    /**
     * Comment: 获取团购商品信息列表
     * Author: zzw
     * Date: 2019/8/7 14:00
     */
    public function homeList(){
        global $_W,$_GPC;
        #1、参数获取
        $page       = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * $page_index - $page_index;
        $lng        = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度
        $lat        = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度
        $status     = $_GPC['status'] ? $_GPC['status'] : 0;
        $is_total   = $_GPC['is_total'] ? : 0;//0=不获取总页数；1=获取总页数
        $cate_id    = $_GPC['cate_id'] ? : 0;//商品分类id
        $is_vip     = $_GPC['is_vip'] ? : 0;//是否获取专属商品

        $time = $_GPC['time'] ?: time();//时间筛选
        $week = date("w", $time);//当前时间的星期
        if ($week == 0) $week = 7;//星期天时值的转换
        $toDay = date("j", $time);//当前时间的日期

        $set = Setting::agentsetting_read("pluginlist");
        $sort = $_GPC['sort'] ? : $set['tgsort'];
        #2、生成基本查询条件
        $where = " a.aid = {$_W['aid']} AND a.uniacid = {$_W['uniacid']} ";

        $where .= " AND CASE a.usedatestatus
                        WHEN 1 THEN a.`week` LIKE '%\"{$week}\"%'
                        WHEN 2 THEN a.`day` LIKE '%\"{$toDay}\"%'
                        WHEN 0 THEN a.id > 0
                    END ";

        if ($status > 0) {
            $ids = explode(',' , $status);
            if (count($ids) > 1) {
                $where .= " AND a.status IN ({$status}) ";
            } else {
                $where .= " AND a.status = {$status}  ";
            }
        } else {
            $where .= " AND a.status IN (1,2)  ";
        }
        if ($cate_id > 0) {
            $where .= " AND a.cateid = {$cate_id} ";
        }
        if ($is_vip == 1) $where .= " AND a.vipstatus IN (1,2) ";
        #4、生成排序条件
        switch ($sort) {
            case 1:$order = " ORDER BY a.createtime DESC ";break;//创建时间
            case 2:break;//店铺距离
            case 3:$order = " ORDER BY a.sort DESC ";break;//默认排序
            case 4:$order = " ORDER BY a.pv DESC ";break;//浏览人气
            case 5:$order = " ORDER BY buy_num DESC ";break;//商品销量
            case 6:$order = " ORDER BY a.sort DESC,buy_num DESC ";break;//精选  推荐、销量排序
            case 7:$order = " ORDER BY a.pv DESC,buy_num DESC ";break;//最热  浏览量、销量排序
        }
        #5、获取商品列表
        if($sort != 2){
            //普通查询
            $sql = "SELECT a.id,a.id as goods_id,(IFNULL(sum(b.num),0) + a.falsesalenum)as buy_num FROM "
                . tablename(PDO_NAME . "groupon_activity")
                . " as a LEFT JOIN ".tablename(PDO_NAME."order")
                . " as b ON a.id = b.fkid AND b.plugin = 'groupon' AND b.uniacid = {$_W['uniacid']} AND b.status IN (0,1,2,3,6,7,9,4,8) AND b.aid = {$_W['aid']} "
                ."WHERE {$where} GROUP BY a.id {$order}"." LIMIT {$page_start},{$page_index} ";
            $info = pdo_fetchall($sql);
        }else{
            //关联店铺查询
            $info = pdo_fetchall("SELECT a.id as goods_id,b.id,b.location FROM "
                                 . tablename(PDO_NAME."groupon_activity")
                                 ." as a LEFT JOIN "
                                 .tablename(PDO_NAME."merchantdata")
                                 ." as b ON a.sid = b.id WHERE {$where} ");
            $info = Store::getstores($info, $lng, $lat, 2);
            $info = array_slice($info,$page_start,$page_index);
        }
        #6、循环处理信息
        foreach ($info as $key => &$val) {
            //获取最新的商品信息
            $val = WeliamWeChat::getHomeGoods(2, $val['goods_id']);
            $val['url'] = h5_url('pages/subPages/goods/index',['type'=>2,'id'=>$val['id']]);
            //添加店铺链接
            if ($val['sid'] > 0) {
                $val['shop_url'] = h5_url('pages/mainPages/store/index', ['sid' => $val['sid']]);
                $val['distance'] = Store::shopLocation($val['sid'], $lng, $lat);
            }else{
                $val['storename'] = '平台商品';
            }
            if($is_vip > 0){
                $val['price'] = sprintf("%.2f",$val['price'] - $val['discount_price']);
            }
            //删除多余的信息
            unset($val['user_list']);
            unset($val['address']);
            unset($val['user_num']);
            unset($val['totalnum']);
            unset($val['allsalenum']);
            unset($val['sid']);
        }
        #7、获取总页数
        if($is_total == 1){
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "groupon_activity") . " as a WHERE {$where}");
            $data['total'] = ceil($total / $page_index);
            $data['list']  = $info;

            $this->renderSuccess('团购商品信息列表',$data);
        }

        $this->renderSuccess('团购商品信息列表',$info);
    }


    /**
     * Comment: 获取团购分类列表
     * Author: wlf
     * Date: 2020/09/21 14:30
     */
    public function cateList(){
        global $_W , $_GPC;
        $list = pdo_getall('wlmerchant_groupon_category',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'is_show' => 0),array('id','name'), '' , 'sort DESC');
        $this->renderSuccess('团购分类',$list);
    }



}