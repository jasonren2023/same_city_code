<?php
defined('IN_IA') or exit('Access Denied');

class Cashback_WeliamController {
    protected static $setName = 'cash_back';
    /**
     * Comment: 返现记录列表
     * Author: zzw
     * Date: 2020/1/14 9:15
     */
    public function cashBackRecord(){
        global $_W,$_GPC;
        #1、参数获取
        $page       = $_GPC['page'] ? : 1;
        $pageIndex  = $_GPC['page_index'] ? : 10;
        $pageStart  = $page * $pageIndex - $pageIndex;
        $status     = $_GPC['status'] ? : '';
        $plugin     = $_GPC['plugin'] ? : '';
        $searchType = $_GPC['search_type'] ? : '';//0=全部;1=商品id;2=买家ID;3=买家昵称
        $search     = $_GPC['search'] ? : '';
        #2、条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} ";
        //状态判断
        if($status) {
            if($status == 'zero') $status = 0;
            $where .= " AND a.status = {$status} ";
        }
        //模块判断
        switch ($plugin){
            case 'rush': $where .= " AND a.plugin = '{$plugin}' ";break;
            case 'groupon': $where .= " AND a.plugin = '{$plugin}' ";break;
        }
        //搜索信息
        switch ($searchType) {
            case 1:
                $where .= " AND a.goods_id = {$search} ";
                break;//商品id
            case 2:
                $where .= " AND a.mid = {$search} ";
                break;//买家ID
            case 3:
                $where .= " AND m.nickname LIKE '%{$search}%' ";
                break;//买家昵称
        }
        $order = " ORDER BY a.create_time DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        #3、信息获取
        $field = "a.id,a.mid,a.goods_id,a.order_id,a.plugin,a.status,a.money,a.create_time,m.nickname,m.avatar";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."cashback")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as m ON a.mid = m.id ".$where;
        //总数获取
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql);
        //列表获取
        $list = pdo_fetchall($sql.$order.$limit);
        foreach($list as $index => &$item){
            //商品名称/订单号 信息获取
            switch ($item['plugin']){
                case 'rush':
                    $orderInfo = pdo_get(PDO_NAME."rush_order",['id'=>$item['order_id']],['orderno','actualprice']);
                    $item['order_no'] = $orderInfo['orderno'];
                    $item['price'] = $orderInfo['actualprice'];
                    $item['goods_name'] = pdo_getcolumn(PDO_NAME."rush_activity",['id'=>$item['goods_id']],'name');
                    break;//抢购
                case 'groupon':
                    $orderInfo = pdo_get(PDO_NAME."order",['id'=>$item['order_id']],['orderno','price']);
                    $item['order_no'] = $orderInfo['orderno'];
                    $item['price'] = $orderInfo['price'];
                    $item['goods_name'] = pdo_getcolumn(PDO_NAME."groupon_activity",['id'=>$item['goods_id']],'name');
                    break;//团购
            }
        }
        #4、分页操作
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template("cashback/cashBackRecord");
    }
    /**
     * Comment: 设置信息获取/编辑
     * Author: zzw
     * Date: 2020/1/13 14:15
     */
    public function setting(){
        global $_W,$_GPC;
        #1、记录信息
        if(checksubmit('submit')){
            $set = $_GPC['set'];
            Setting::wlsetting_save($set,self::$setName);
            show_json(1);
        }
        #2、获取信息
        $set = Setting::wlsetting_read(self::$setName);


        include wl_template("cashback/setting");
    }
    /**
     * Comment: 返现审核操作
     * Author: zzw
     * Date: 2020/1/14 10:40
     */
    public function cashBackToExamine(){
        global $_W,$_GPC;
        #1、参数接收
        $id     = $_GPC['id'];
        $ids    = $_GPC['ids'];
        $status = $_GPC['status'];//0=重新审核；1=通过；2=不通过
        $table  = PDO_NAME."cashback";
        $data   = ['status'=>$status];
        if(is_array($ids) && count($ids) > 0){
            $where = ['id'=>$ids];
        }else{
            $where = ['id'=>$id];
        }
        #3、根据状态值进行对应的操作
        $res = pdo_update($table,$data,$where);
        if($res){
            //审核通过，直接返现给用户
            if($status == 1) {
                $info = pdo_getall($table , $where , ['order_id' , 'plugin' , 'money' , 'mid']);
                foreach ($info as $index => $item){
                    switch ($item['plugin']) {
                        case 'rush':
                            $orderNo = pdo_getcolumn(PDO_NAME . "rush_order" , ['id' => $item['order_id']] , 'orderno');
                            break;//抢购
                        case 'groupon':
                            $orderNo = pdo_getcolumn(PDO_NAME . "order" , ['id' => $item['order_id']] , 'orderno');
                            break;//团购
                    }
                    if ($orderNo) Cashback::moneyBack($item['mid'] , $item['money'] , $orderNo);
                }
            }
            show_json(1,'操作成功');
        }else{
            show_json(0,'操作失败，请重试');
        }
    }

}