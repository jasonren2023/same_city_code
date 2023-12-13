<?php
defined('IN_IA') or exit('Access Denied');

class ConsumptionModuleUniapp extends Uniapp {
    /**
     * Comment: 积分商品信息列表
     * Author: zzw
     * Date: 2019/8/7 14:28
     */
    public function homeList(){
        global $_W,$_GPC;
        #1、参数获取
        $page       = $_GPC['page'] ? $_GPC['page'] : 1;
        $type       = $_GPC['type'] ? trim($_GPC['type']) : '';//goods=普通积分商品；credit2=余额积分商品；halfcard=会员卡积分商品
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * $page_index - $page_index;
        $cate_id    = $_GPC['cate_id'] ? : '';
        $is_vip     = $_GPC['is_vip'] ? : 0;//是否获取专属商品

        $time = $_GPC['time'] ?: time();//时间筛选
        $week = date("w", $time);//当前时间的星期
        if ($week == 0) $week = 7;//星期天时值的转换
        $toDay = date("j", $time);//当前时间的日期

        $search = trim($_GPC['search']);

        $set = Setting::agentsetting_read("pluginlist");
        $sort = $_GPC['sort'] ? : $set['jfsort'];

        #2、生成基本查询条件
        $where = " uniacid = {$_W['uniacid']} AND status = 1 ";

        $where .= " AND CASE usedatestatus
                        WHEN 1 THEN `week` LIKE '%\"{$week}\"%'
                        WHEN 2 THEN `day` LIKE '%\"{$toDay}\"%'
                        WHEN 0 THEN id > 0
                    END ";

        if (!empty($type)) $where .= " AND type = '{$type}' ";
        if ($cate_id > 0) $where .= " AND category_id = {$cate_id} ";
        if ($is_vip == 1) $where .= " AND vipstatus IN (1,2) ";
        if (!empty($search))  $where.= " AND title LIKE '%{$search}%'";
        #4、生成排序条件
        switch ($sort) {
            case 1:$order = " ORDER BY id DESC ";break;//创建时间
            case 3:$order = " ORDER BY displayorder DESC ";break;//默认排序
            case 4:$order = " ORDER BY pv DESC ";break;//浏览人气
        }
        #5、获取总页数
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "consumption_goods") . " WHERE {$where}");
        $info['total'] = ceil($total / $page_index);
        #2、判断用户是否为会员
        $cardid = WeliamWeChat::VipVerification($_W['mid'],true);
        #5、获取商品列表
        $sql = "SELECT id,thumb,title,old_price,
                CASE WHEN {$cardid} > 0 AND vipstatus = 1 THEN vipcredit1 
                     ELSE  use_credit1
                END as use_credit1,
                CASE WHEN {$cardid} > 0 AND vipstatus = 1 THEN vipcredit2 
                     ELSE use_credit2
                END as use_credit2 FROM " .
            tablename(PDO_NAME . "consumption_goods") .
            " WHERE {$where}{$order}"." LIMIT {$page_start},{$page_index} ";
        $info['list'] = pdo_fetchall($sql);
        #6、循环处理信息
        foreach ($info['list'] as $key => &$val) {
            $val['thumb'] = tomedia($val['thumb']);
            $val['url'] = h5_url('pages/subPages/goods/index',array('goods_id' => $val['id'],'goodsType'=>'integral'));
        }

        $this->renderSuccess('积分商品信息列表',$info);
    }
    /**
     * Comment: 积分商城首页基本信息获取
     * Author: zzw
     * Date: 2019/8/22 18:26
     */
    public function homeInfo(){
        global $_W,$_GPC;
        if($_W['wlsetting']['consumption']['status'] != 1) $this->renderError('未开启积分商城',['url'=>h5_url('pages/mainPages/userCenter/userCenter')]);
        #1、获取幻灯片信息
        $adv = pdo_getall(PDO_NAME.'consumption_adv' ,['uniacid'=>$_W['uniacid'],'status'=>1]
            ,['link','thumb'],'',' displayorder DESC ','');
        foreach($adv as $imgK => &$imgV){
            $imgV['thumb'] = tomedia($imgV['thumb']);
        }
        #2、获取分类信息列表
        $classList = pdo_getall(PDO_NAME."consumption_category" ,['uniacid'=>$_W['uniacid'],'status'=>1]
            ,['id','name','thumb'],'',' displayorder DESC ','');
        foreach($classList as $classK => &$classV){
            $classV['thumb'] = tomedia($classV['thumb']);
        }
        #3、获取基本信息
        $info['integral']    = $_W['wlmember']['credit1'];//积分
        if($_W['wlsetting']['consumption']['community'] > 0){
            $info['community'] = Commons::getCommunity($_W['wlsetting']['consumption']['community']);
        }
        #4、信息拼装
        $info['adv'] = $adv;
        $info['class'] = $classList;

        $this->renderSuccess('积分商城首页基本信息',$info);
    }
    /**
     * Comment: 获取积分商品详情
     * Author: zzw
     * Date: 2019/8/23 9:36
     */
    public function detail(){
        global $_W,$_GPC;
        #1、参数接收
        $id = $_GPC['id'] OR $this->renderError('缺少参数：id');
        $info = WeliamWeChat::getHomeGoods(8,$id);
        $info['postertype'] = intval(10);
        if($_W['wlsetting']['halfcard']['status'] > 0 && $info['vipstatus'] > 0){
            $info['is_open_vip'] = 1;
        }else{
            $info['is_open_vip'] = 0;
        }
        $this->renderSuccess('积分商品详情',$info);
    }

    /**
     * Comment: 获取积分商品分类列表
     * Author: wlf
     * Date: 2020/09/21 14:39
     */
    public function cateList(){
        global $_W , $_GPC;
        $list = pdo_getall('wlmerchant_consumption_category',array('uniacid' => $_W['uniacid'],'status' => 1),array('id','name'), '' , 'displayorder DESC');
        $this->renderSuccess('积分分类',$list);
    }

}