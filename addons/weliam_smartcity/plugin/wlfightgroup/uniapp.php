<?php
defined('IN_IA') or exit('Access Denied');

class WlfightgroupModuleUniapp extends Uniapp {
    /**
     * Comment: 拼团商品列表
     * Author: zzw
     * Date: 2019/8/7 11:24
     */
    public function homeList(){
        global $_W,$_GPC;
        #1、参数获取
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * $page_index - $page_index;
        $lng = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度
        $lat = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度
        $status = $_GPC['status'] > 0 ? ' = '.$_GPC['status'] : ' IN (1,2) ';
        $is_total   = $_GPC['is_total'] ? : 0;//0=不获取总页数；1=获取总页数
        $cate_id    = $_GPC['cate_id'] ? : 0;//商品分类id
        $is_vip     = $_GPC['is_vip'] ? : 0;//是否获取会员专属商品

        $time = $_GPC['time'] ?: time();//时间筛选
        $week = date("w", $time);//当前时间的星期
        if ($week == 0) $week = 7;//星期天时值的转换
        $toDay = date("j", $time);//当前时间的日期

        $set = Setting::agentsetting_read("pluginlist");
        $sort = $_GPC['sort'] ? : $set['ptsort'];
        #2、生成基本查询条件
        $where = " a.aid = {$_W['aid']} AND a.uniacid = {$_W['uniacid']} AND a.status {$status} ";
        if($cate_id > 0){
            $where .= " AND a.categoryid = {$cate_id} ";
        }
        if ($is_vip > 0) $where .= " AND a.vipstatus IN (1,2) ";
        $where .= " AND CASE a.usedatestatus
                        WHEN 1 THEN a.`week` LIKE '%\"{$week}\"%'
                        WHEN 2 THEN a.`day` LIKE '%\"{$toDay}\"%'
                        WHEN 0 THEN a.id > 0
                    END ";

        #3、生成排序条件
        switch ($sort) {
            case 1:$order = " ORDER BY a.id DESC ";break;//创建时间
            case 2:break;//店铺距离
            case 3:$order = " ORDER BY a.listorder DESC ";break;//默认排序
            case 4:$order = " ORDER BY a.pv DESC ";break;//浏览人气
            case 5:$order = " ORDER BY buy_num DESC ";break;//商品销量
            case 6:$order = " ORDER BY a.listorder DESC,buy_num DESC ";break;//精选  推荐、销量排序
            case 7:$order = " ORDER BY a.pv DESC,buy_num DESC ";break;//最热  浏览量、销量排序
        }
        #4、获取商品列表
        if($sort != 2){
            //普通查询
            $sql = "SELECT a.id,a.id as goods_id,(IFNULL(sum(b.num),0) + a.falsesalenum) as buy_num FROM "
                . tablename(PDO_NAME . "fightgroup_goods")
                . " as a LEFT JOIN ".tablename(PDO_NAME."order")
                . " as b ON a.id = b.fkid AND b.plugin = 'wlfightgroup' AND b.uniacid = {$_W['uniacid']} AND b.status IN (0,1,2,3,6,7,9,4,8) AND b.aid = {$_W['aid']} "
                ."WHERE {$where} GROUP BY a.id {$order} ";
            if($is_total == 1) $total = count(pdo_fetchall($sql));
            $info = pdo_fetchall($sql." LIMIT {$page_start},{$page_index} ");
        }else{
            //关联店铺查询
            $info = pdo_fetchall("SELECT a.id as goods_id,b.id,b.location FROM "
                                 . tablename(PDO_NAME."fightgroup_goods")
                                 ." as a RIGHT JOIN "
                                 .tablename(PDO_NAME."merchantdata")
                                 ." as b ON a.merchantid = b.id WHERE {$where} ");
            $info = Store::getstores($info, $lng, $lat, 2);
            if($is_total == 1) $total = count($info);
            $info = array_slice($info,$page_start,$page_index);
        }
        #5、循环处理信息
        foreach ($info as $key => &$val) {
            //获取最新的商品信息
            $val = WeliamWeChat::getHomeGoods(3, $val['goods_id']);
            $val['url'] = h5_url('pages/subPages/goods/index',['type'=>3,'id'=>$val['id']]);
            //当商品信息中带有sid时添加店铺链接
            if ($val['sid']) {
                $val['shop_url'] = h5_url('pages/mainPages/store/index',['sid'=>$val['sid']]);
                $val['distance'] = Store::shopLocation($val['sid'], $lng, $lat);
            }
            if($is_vip > 0){
                $val['price'] = sprintf("%.2f",$val['price'] - $val['discount_price']);
            }
            //删除多余的信息
            unset($val['user_list']);
            unset($val['address']);
            unset($val['user_num']);
            unset($val['totalnum']);
            unset($val['status']);
            unset($val['realsalenum']);
            unset($val['realsalenum']);
        }
        #6、信息拼装
        if($is_total == 1){
            $data['total'] = ceil($total / $page_index);
            $data['list'] = $info;

            $this->renderSuccess('拼团商品信息列表', $data);
        }

        $this->renderSuccess('拼团商品信息列表',$info);
    }
    /**
     * Comment: 获取组团详细信息
     * Author: zzw
     * Date: 2019/8/14 11:02
     */
    public function groupDetail(){
        global $_W,$_GPC;
        #1、参数获取
        $order_id = $_GPC['order_id'];
        $group_id = $_GPC['group_id'];
        if(!$order_id && !$group_id) $this->renderError('缺少参数：订单id或拼团id');
        #2、通过订单获取商品信息，获取拼团id信息
        if(!$group_id && $order_id) {
            $group_id = pdo_getcolumn(PDO_NAME . "order" , [ 'id' => $order_id ] , 'fightgroupid');
            if (!$group_id) $this->renderError('订单不存在');
        }
        #3、获取组团信息
        $info = pdo_get(PDO_NAME."fightgroup_group",['id'=>$group_id],
                        ['status','neednum','lacknum','starttime','failtime','successtime','goodsid','luckyorderids']);
        #4、获取商品信息
        $goods = pdo_get(PDO_NAME."fightgroup_goods",['id'=>$info['goodsid']],
                            ['id','logo','name','price','aloneprice','peoplenum','realsalenum']);
        $goods['logo'] = tomedia($goods['logo']);
        $goods['realsalenum'] = WeliamWeChat::getSalesNum(3,$info['goodsid'],0,2);
        #5、获取参团的用户信息
        $user = pdo_fetchall("SELECT a.id as orderid,b.id,b.avatar,b.nickname,a.orderno,a.name,a.buyremark,FROM_UNIXTIME(a.createtime,'%Y-%m-%d %H:%i:%S') as createtime FROM ".tablename(PDO_NAME."order")
                             ." as a LEFT JOIN ".tablename(PDO_NAME."member")
                             ." as b ON a.mid = b.id WHERE a.fightgroupid = {$group_id} AND a.status != 5 AND a.status != 0 ORDER BY a.createtime ASC ");
        $luckyorderids = unserialize($info['luckyorderids']);
        foreach ($user as &$us){
            if($us['orderno']== '666666'){
                $us['nickname'] = $us['name'];
                $us['avatar'] = $us['buyremark'];
            }
            if(in_array($us['orderid'],$luckyorderids)){
                $us['kingimg'] = 1;
            }
        }
        #6、判断当前用户是否参与拼团 0=为参与；1=已参与
        $info['is_participate'] = 0;
        $ids = array_column($user,'id');
        if(is_array($ids) && count($ids) > 0){
            if(in_array($_W['mid'],$ids)){
                $info['is_participate'] = 1;
            }
        }
        #7、数据拼装
        $info['goods'] = $goods;
        $info['user'] = $user;
        $info['group_id'] = $group_id;

        $this->renderSuccess('组团详细信息',$info);
    }
    /**
     * Comment: 获取某个拼团商品的团队列表
     * Author: zzw
     * Date: 2019/9/16 16:46
     */
    public function groupList(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'] OR $this->renderError('缺少参数：id');//商品id
        $page = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        #2、获取总页数
        $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."fightgroup_group")
                              ." WHERE goodsid = {$id} AND aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND status = 1 ");
        $data['total'] = ceil($total / $pageIndex);
        #3、获取列表信息
        $data['list'] = pdo_fetchall("SELECT a.id,a.failtime,a.neednum,a.lacknum,m.nickname,m.avatar FROM " .tablename(PDO_NAME."fightgroup_group")
                                           ." as a RIGHT JOIN " .tablename(PDO_NAME."order")
                                           ." as b ON a.id = b.fightgroupid RIGHT JOIN ".tablename(PDO_NAME."member")
                                           ." as m ON b.mid = m.id "
                                           ." WHERE a.goodsid = {$id} AND a.aid = {$_W['aid']} AND a.uniacid = {$_W['uniacid']} AND a.status = 1 "
                                           ."  GROUP BY b.fightgroupid ORDER BY a.starttime ASC LIMIT {$pageStart},{$pageIndex} ");

        $this->renderSuccess('拼团商品团队列表',$data);
    }

    /**
     * Comment: 获取拼团分类列表
     * Author: wlf
     * Date: 2020/09/24 14:06
     */
    public function cateList(){
        global $_W , $_GPC;
        $list = pdo_getall('wlmerchant_fightgroup_category',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'is_show' => 0),array('id','name'), '' , 'listorder DESC');
        $this->renderSuccess('拼团分类',$list);
    }


}
