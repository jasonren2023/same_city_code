<?php
/**
 * Comment: 掌上信息 评论/回复审核操作
 * Author: zzw
 */
defined('IN_IA') or exit('Access Denied');

class ToExamine_WeliamController{
    /**
     * Comment: 获取评论列表
     * Author: zzw
     * Date: 2020/3/13 15:51
     */
    public function comment(){
        global $_W,$_GPC;
        #1、参数获取
        $page = $_GPC['page'] ? :1;
        $pageIndex = $_GPC['page_index'] ? :10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $status = $_GPC['status'] ? : 0;
        #2、条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        if($status > 0) $where .= $status == 3 ? " AND a.status = 0 " : " AND a.status = {$status} ";
        $order = " ORDER BY a.createtime DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $field = "a.id,a.content,FROM_UNIXTIME(a.createtime,'%Y-%m-%d %H:%i:%S') as createtime,a.tid,u.nickname,a.status";
        $sql   = "SELECT {$field} FROM ".tablename(PDO_NAME."pocket_comment")
            ." as a RIGHT JOIN ".tablename(PDO_NAME."pocket_informations")
            ." as b ON a.tid = b.id RIGHT JOIN ".tablename(PDO_NAME."member")
            ." as u ON a.mid = u.id {$where}";
        #3、列表信息获取
        $list = pdo_fetchall($sql.$order.$limit);
        #3、总数获取
        $totalSql = str_replace($field , 'count(*)' , $sql);
        $total    = pdo_fetchcolumn($totalSql);
        $pager    = wl_pagination($total , $page , $pageIndex);

        include   wl_template('examine/comment');
    }
    /**
     * Comment: 评论审核操作
     * Author: zzw
     * Date: 2020/3/13 16:05
     */
    public function commentToExamine(){
        global $_W,$_GPC;
        #1、参数获取
        $status = $_GPC['status'] ? : 1;
        $ids = $_GPC['ids'];
        #2、条件生成
        $where['status'] = 0;
        if(is_array($ids)) $where['id IN '] = $ids;
            else $where['id'] = $ids;
        #3、操作判断
        if (pdo_update(PDO_NAME . "pocket_comment" , ['status' => $status] , $where)){
            //发送模板消息通知
            if($status == 1){
                unset($where['status']);
                $list = pdo_getall(PDO_NAME."pocket_comment",$where,['id','tid']);
                foreach($list as $key => $val){
                    pdo_update('wlmerchant_pocket_informations',array('replytime' => time()),array('id' => $val['tid']));
                    Pocket::setModelInfo($val['tid'],$val['id']);
                }
            }

            show_json(1 , '审核成功');
        }else {
            show_json(0 , '审核失败，请刷新重试!');
        }
    }
    /**
     * Comment: 删除评论信息
     * Author: zzw
     * Date: 2020/3/13 16:12
     */
    public function delComment(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'];
        #1、条件生成
        if(is_array($ids)) $where['id IN '] = $ids;
        else $where['id'] = $ids;
        #3、操作判断
        if (pdo_delete(PDO_NAME."pocket_comment",$where)) show_json(1 , '删除成功');
        else show_json(0 , '删除失败，请刷新重试!');
    }



    /**
     * Comment: 获取回复列表
     * Author: zzw
     * Date: 2020/3/13 17:07
     */
    public function reply(){
        global $_W,$_GPC;
        #1、参数获取
        $page = $_GPC['page'] ? :1;
        $pageIndex = $_GPC['page_index'] ? :10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $status = $_GPC['status'] ? : 0;
        #2、条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        if($status > 0) $where .= $status == 3 ? " AND a.status = 0 " : " AND a.status = {$status} ";
        $order = " ORDER BY a.createtime DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $field = "a.id,a.content,FROM_UNIXTIME(a.createtime,'%Y-%m-%d %H:%i:%S') as createtime,a.tid,u.nickname,a.status";
        $sql   = "SELECT {$field} FROM ".tablename(PDO_NAME."pocket_reply")
            ." as a RIGHT JOIN ".tablename(PDO_NAME."pocket_informations")
            ." as b ON a.tid = b.id RIGHT JOIN ".tablename(PDO_NAME."member")
            ." as u ON a.smid = u.id {$where}";
        #3、列表信息获取
        $list = pdo_fetchall($sql.$order.$limit);
        #3、总数获取
        $totalSql = str_replace($field , 'count(*)' , $sql);
        $total    = pdo_fetchcolumn($totalSql);
        $pager    = wl_pagination($total , $page , $pageIndex);

        include   wl_template('examine/reply');
    }
    /**
     * Comment: 评论审核操作
     * Author: zzw
     * Date: 2020/3/13 16:05
     */
    public function replyToExamine(){
        global $_W,$_GPC;
        #1、参数获取
        $status = $_GPC['status'] ? : 1;
        $ids = $_GPC['ids'];
        #2、条件生成
        $where['status'] = 0;
        if(is_array($ids)) $where['id IN '] = $ids;
        else $where['id'] = $ids;
        #3、操作判断
        if (pdo_update(PDO_NAME . "pocket_reply" , ['status' => $status] , $where)) {
            //发送模板消息通知
            if($status == 1){
                unset($where['status']);
                $list = pdo_getall(PDO_NAME."pocket_reply",$where,['id','tid','smid','amid']);
                foreach($list as $key => $val){
                    pdo_update('wlmerchant_pocket_informations',array('replytime' => time()),array('id' => $val['tid']));
                    Pocket::setReplyModelInfo($val['tid'],$val['id'],$val['smid'],$val['amid']);
                }
            }

            show_json(1 , '审核成功');
        }else {
            show_json(0 , '审核失败，请刷新重试!');
        }
    }
    /**
     * Comment: 删除评论信息
     * Author: zzw
     * Date: 2020/3/13 16:12
     */
    public function delReply(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'];
        #1、条件生成
        if(is_array($ids)) $where['id IN '] = $ids;
        else $where['id'] = $ids;
        #3、操作判断
        if (pdo_delete(PDO_NAME."pocket_reply",$where)) show_json(1 , '删除成功');
        else show_json(0 , '删除失败，请刷新重试!');
    }

}