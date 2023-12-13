<?php
defined('IN_IA') or exit('Access Denied');

class Fullreduce_WeliamController {
    /**
     * Comment: 满减活动列表页
     */
    public function activelist() {
        global $_W, $_GPC;
        //参数获取
        $pindex = max(1 , intval($_GPC['page']));
        $psize  = 10;
        $name   = trim($_GPC['name']);
        //条件生成
        $where  = ['uniacid' => $_W['uniacid'],'aid'=>$_W['aid']];
        if ($name) $where['title LIKE'] = "%".$name."%";
        //信息获取
        $lists = pdo_getslice(PDO_NAME.'fullreduce_list' , $where , [$pindex , $psize] , $total , [] , '' , "sort DESC,id DESC");
        foreach ($lists as &$act){
            $act['createtime'] = date('Y-m-d H:i:s',$act['createtime']);
            $act['rules'] =  unserialize($act['rules']);
        }
        $pager       = wl_pagination($total , $pindex , $psize);
        include wl_template('fullreduce/activelist');
    }

    /**
     * Comment: 满减活动编辑页
     */
    public function activeedit(){
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if(!empty($id)){
            $item = pdo_get(PDO_NAME . 'fullreduce_list',array('id' => $id));
            $item['rules'] = unserialize($item['rules']);
        }
        if ($_W['ispost']) {
            $active = $_GPC['item'];
            $full_money = $_GPC['full_money'];
            $cut_money = $_GPC['cut_money'];
            if(empty($full_money) || empty($cut_money)){
                wl_message('规则设置错误,请重试' , referer(),'error');
            }
            $len         = count($full_money);
            $rule = [];
            for ($k = 0 ; $k < $len ; $k++) {
                $rule[$k]['full_money']  = sprintf("%.2f",$full_money[$k]);
                $rule[$k]['cut_money'] = sprintf("%.2f",$cut_money[$k]);
            }
            $timeKey =  array_column($rule, 'full_money');
            array_multisort($timeKey,SORT_DESC,$rule);
            $rule    = serialize($rule);
            $active['rules'] = $rule;
            if(empty($id)){
                $active['uniacid'] = $_W['uniacid'];
                $active['aid'] = $_W['aid'];
                $active['createtime'] = time();
                $res = pdo_insert(PDO_NAME . 'fullreduce_list', $active);
                if($res){
                    wl_message('新建满减活动成功' , web_url('fullreduce/fullreduce/activelist' , ['id' => $id]) , 'success');
                }else{
                    wl_message('新建满减活动失败,请重试' , referer(),'error');
                }
            }else{
                $res = pdo_update(PDO_NAME . 'fullreduce_list',$active,array('id' => $id));
                if($res){
                    wl_message('更新满减活动成功' , web_url('fullreduce/fullreduce/activelist' , ['id' => $id]) , 'success');
                }else{
                    wl_message('更新满减活动失败,请重试' , referer(),'error');
                }
            }
        }
        include wl_template('fullreduce/activeedit');
    }

    /**
     * Comment: 满减活动规则页
     */
    public function rules(){
        include wl_template('fullreduce/rules');
    }

    /**
     * Comment: 满减活动启用禁用操作
     * Author: wlf
     * Date: 2020/06/29 19:30
     */
    public function changeStatus(){
        global $_W,$_GPC;
        #1、参数获取
        $id     = intval($_GPC['id']);
        $status = intval($_GPC['status']);
        #2、改变状态 0=禁用；1=启用
        if($status == 1) $data['status'] = 0;
        else $data['status'] = 1;
        #3、信息修改
        if(pdo_update(PDO_NAME."fullreduce_list",$data,['id'=>$id])) show_json(1);
        else show_json(0,'请刷新重试!');
    }

    /**
     * Comment: 删除满减活动
     */
    public function delete(){
        global $_W,$_GPC;
        $id     = intval($_GPC['id']);
        $res = pdo_delete(PDO_NAME."fullreduce_list",array('id'=>$id));
        if($res){
            show_json(1);
        }else{
            show_json(0,'删除失败,请刷新重试!');
        }
    }





}