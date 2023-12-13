<?php
defined('IN_IA') or exit('Access Denied');
/**
 * Comment: 参与抽奖用户管理
 * Author: zzw
 * Class User_WeliamController
 */
class User_WeliamController {
    /**
     * Comment: 获取参与抽奖的用户信息
     * Author: zzw
     * Date: 2020/9/22 14:36
     */
    public function userIndex(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $nickname  = $_GPC['nickname'] ? : '';//用户昵称
        $export    = $_GPC['export'] ? : 0 ;//是否导出
        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        if($nickname) $where .= " AND b.nickname LIKE '%{$nickname}%' ";
        //sql语句生成
        $field = "a.mid,b.uid,b.nickname,b.avatar,count(*) as total_draw,b.mobile";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."draw_record")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id ";
        $order = " ORDER BY a.create_time DESC,a.id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $group = " GROUP BY a.mid ";
        //判断是否导出  如果是导出则执行导出操作
        if($export) Draw::exportUserList($sql.$where.$group.$order);
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$group.$order.$limit);
        foreach($list as &$value){
            $value['avatar']      = tomedia($value['avatar']);
            //获取中奖次数
            $value['total_prize'] = Draw::selectCount(['mid'=>$value['mid'],'draw_goods_id >' => 0]);
            //获取用户积分、余额
            $credit = pdo_get('mc_members' , ['uid' => $value['uid']] , ['credit1' , 'credit2']);
            $value['credit1'] = $credit['credit1'];
            $value['credit2'] = $credit['credit2'];
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchall($totalSql.$where.$group);
        $pager = wl_pagination(count($total), $page, $pageIndex);

        include  wl_template('user/index');
    }


}
