<?php
defined('IN_IA') or exit('Access Denied');
/**
 * Comment: 中奖管理
 * Author: zzw
 * Class Prize_WeliamController
 */
class Prize_WeliamController {
    /**
     * Comment: 获取用户抽奖信息列表
     * Author: zzw
     * Date: 2020/9/22 10:30
     */
    public function prizeIndex(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $title     = $_GPC['title'] ? : '';//奖品名称
        $type      = intval($_GPC['type']) ? : 0;//0=全部；奖品类型:1=现金红包,2=线上红包,3=积分,4=激活码,5=商品
        $nickname  = $_GPC['nickname'] ? : '';//用户昵称
        $isGet     = intval($_GPC['is_get']) ? : 0;//0=全部；是否领取 是否领取奖品:1=未领取，2=已领取
        $name      = $_GPC['name'] ? : '';//活动名称
        $times     = $_GPC['draw_times'] ? : '';//抽奖时间
        $mid       = $_GPC['mid'] ? : 0 ;//用户id
        $export    = $_GPC['export'] ? : 0 ;//是否导出

        $recodeid  = $_GPC['recodeid'] ? : 0 ;//精确查找
        if($times){
            $startTime = strtotime($times['start']);
            $endTime   = strtotime($times['end']);
        }else{
            $startTime = strtotime(date("Y-m-1 00:00:00"));
            $endTime   = strtotime(" +1 month ",$startTime);
        }

        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        if ($title) $where .= " AND c.title LIKE '%{$title}%' ";
        if ($type > 0) $where .= " AND c.type = {$type} ";
        if ($nickname) $where .= " AND b.nickname LIKE '%{$nickname}%' ";
        if ($isGet > 0) $where .= " AND a.is_get = {$isGet} ";
        if($name) $where .= " AND d.title LIKE '%{$name}%' ";
        if($startTime && $endTime){
            $where .= " AND a.create_time >= {$startTime} AND a.create_time < {$endTime} ";
        }
        if($mid > 0) $where .= " AND b.id = {$mid} ";

        if($recodeid > 0){
            $where .= " AND a.id = {$recodeid} ";
        }
        //sql语句生成
        $field = "a.id,d.title as draw_title,b.nickname,b.avatar,a.create_time,c.title,c.image,a.is_get,a.draw_goods_id";
        $sql = "SELECT {$field} FROM "
            .tablename(PDO_NAME."draw_record")//抽奖记录表
            ." as a LEFT JOIN "
            .tablename(PDO_NAME."member")//用户信息表
            ." as b ON a.mid = b.id LEFT JOIN "
            .tablename(PDO_NAME."draw_goods")//奖品信息表
            ." as c ON a.draw_goods_id = c.id and a.draw_goods_id > 0 LEFT JOIN "
            .tablename(PDO_NAME."draw")//抽奖活动表
            ." as d ON a.draw_id = d.id";
        $order = " ORDER BY a.create_time DESC,a.id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        //判断是否导出  如果是导出则执行导出操作
        if($export) Draw::exportInfoList($sql.$where.$order);
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as &$value){
            $value['avatar']      = tomedia($value['avatar']);
            $value['image']       = tomedia($value['image']);
            $value['create_time'] = date("Y-m-d H:i:s" , $value['create_time']);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);
        //统计信息获取
        $statisticsInfo = Draw::getStatistics($startTime,$endTime);

        include  wl_template('prize/index');
    }
    /**
     * Comment: 删除指定的抽奖记录
     * Author: zzw
     * Date: 2020/9/22 13:52
     */
    public function delete(){
        global $_W,$_GPC;
        //参数信息获取
        $ids = $_GPC['ids'] OR show_json(0, '参数错误，请刷新重试!');
        //删除记录
        $res = pdo_delete(PDO_NAME."draw_record",['id IN'=>$ids]);
        if ($res) show_json(1 , '删除成功');
        else show_json(0 , '删除失败，请刷新重试');
    }

}
