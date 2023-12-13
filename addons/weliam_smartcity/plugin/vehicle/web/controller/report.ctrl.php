<?php
defined('IN_IA') or exit('Access Denied');


class Report_WeliamController{
    /**
     * Comment: 举报信息列表
     * Author: zzw
     * Date: 2021/4/23 16:33
     */
    public function reportList(){
        global $_W,$_GPC;
        //参数获取
        $page          = max(1,intval($_GPC['page']));
        $pageIndex     = 10;
        $pageStart     = $page * $pageIndex - $pageIndex;
        $status        = $_GPC['status'] ? : '';
        $reportContent = $_GPC['report_content'] ? : '';
        //条件生成
        $where = " where a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        if($status > 0) $where .= " AND a.status = {$status} ";
        if($reportContent) $where.= " AND a.describe LIKE '%{$reportContent}%' ";
        //列表获取
        $field = "a.id,a.describe,a.create_time,a.status,b.start_address,b.end_address,b.contacts,b.contacts_phone,c.nickname,c.avatar";
        $order = ' ORDER BY a.create_time DESC,a.id DESC ';
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."vehicle_report")
            ." as a LEFT JOIN ".tablename(PDO_NAME."vehicle")
            ." as b ON a.vehicle_id = b.id LEFT JOIN ".tablename(PDO_NAME."member")
            ." as c ON a.mid = c.id ";
        $list = pdo_fetchall($sql.$where.$order.$limit);
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total    = pdo_fetchcolumn($totalSql.$where);
        $pager    = wl_pagination($total,$page,$pageIndex);

        include wl_template('report/index');
    }
    /**
     * Comment: 删除举报信息
     * Author: zzw
     * Date: 2021/4/23 16:24
     */
    public function reportDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."vehicle_report",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }
    /**
     * Comment: 修改举报信息状态
     * Author: zzw
     * Date: 2021/4/23 16:28
     */
    public function reportChangeStatus(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR show_json(0, "参数错误，请刷新重试!");
        $status = $_GPC['status'] ? : 3;//状态:状态:1=待处理,2=处理中,3=已处理
        //修改状态
        pdo_update(PDO_NAME."vehicle_report",['status'=>$status],['id'=>$id]);

        show_json(1, "操作成功");
    }





}