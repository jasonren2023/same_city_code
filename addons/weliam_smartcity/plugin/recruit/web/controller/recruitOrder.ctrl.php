<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 求职招聘订单管理
 * Author: zzw
 * Date: 2021/1/13 10:02
 * Class RecruitOrder_WeliamController
 */
class RecruitOrder_WeliamController{
    /**
     * Comment: 求职订单列表
     * Author: zzw
     * Date: 2021/1/13 11:19
     */
    public function index(){
        global $_W,$_GPC;
        //参数信息获取
        $page            = $_GPC['page'] ? : 1;
        $pageIndex       = $_GPC['page_index'] ? : 10;
        $startPage       = $page * $pageIndex - $pageIndex;
        $title           = $_GPC['title'] ? : '';//名称
        $industryPid     = $_GPC['industry_pid'] ? : 0;//上级行业id
        $industryId      = $_GPC['industry_id'] ? : 0;//子行业id
        $positionId      = $_GPC['position_id'] ? : 0;//职位id
        $recruitmentType = $_GPC['recruitment_type'] ? : 0;//招聘类型:1=个人招聘,2=企业招聘
        //条件删除
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} AND a.plugin = 'recruit' ";
        if($title) $where .= " AND b.title LIKE '%{$title}%' ";
        if($industryPid > 0) $where .= " AND b.industry_pid = {$industryPid} ";
        if($industryId > 0) $where .= " AND b.industry_id = {$industryId} ";
        if($positionId > 0) $where .= " AND b.position_id = {$positionId} ";
        if($recruitmentType > 0) $where .= " AND b.recruitment_type = {$recruitmentType} ";
        //sql语句生成
        $field = "a.id,a.orderno,a.status,a.paytype,a.paytime,a.price,a.fightstatus,b.title,b.recruitment_type,b.release_mid,b.release_sid";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."order")
            ." as a RIGHT JOIN ".tablename(PDO_NAME."recruit_recruit")
            ." as b ON a.fkid = b.id ";
        $order = " ORDER BY a.createtime DESC,a.id DESC ";
        $limit = " LIMIT {$startPage},{$pageIndex} ";
        //获取并且处理信息
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as $index => &$item){
            //判断发布方    招聘类型:1=个人招聘,2=企业招聘
            if($item['recruitment_type'] == 1){
                $release = pdo_get(PDO_NAME."member",['id'=>$item['release_mid']],['nickname','avatar']);
                $item['release_name'] = $release['nickname'];
                $item['release_logo'] = tomedia($release['avatar']);
            }else{
                $release = pdo_get(PDO_NAME."merchantdata",['id'=>$item['release_sid']],['storename','logo']);
                $item['release_name'] = $release['storename'];
                $item['release_logo'] = tomedia($release['logo']);
            }
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);
        //获取行业职位信息
        $industry = Recruit::getIndustryList(['pid'=>0],['id','title']);//上级行业列表
        if ($industryPid) $subIndustry = Recruit::getIndustryList(['pid'=>$industryPid],['id','title']);//子行业列表
        if ($industryId) $position = Recruit::getPositionList(['industry_id'=>$industryId],['id','title']);//子行业列表



        include wl_template('order/index');
    }


}