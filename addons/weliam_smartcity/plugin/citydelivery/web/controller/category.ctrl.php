<?php
defined('IN_IA') or exit('Access Denied');

class Category_WeliamController
{
    /**
     * Comment: 同城配送分类列表
     * Author: wlf
     * Date: 2020/03/19 09:37
     */
    public function catelist() {
        global $_W, $_GPC;
        #1、参数获取
        $page = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        $keyword = $_GPC['keyword'] ? : '';
        #1、条件生成
        $where = ['aid'=>$_W['aid'],'uniacid'=>$_W['uniacid']];
        if (is_store()) {
            $where['sid'] = $_W['storeid'];
        }
        if(!empty($keyword)){
            if($_GPC['keywordtype'] == 1){
                $where['name LIKE'] = '%' . $keyword . '%';
            }else if($_GPC['keywordtype'] == 2){
                $params[':storename'] = "%{$keyword}%";
                $merchants = pdo_fetchall("SELECT id FROM " . tablename('wlmerchant_merchantdata') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND storename LIKE :storename", $params);
                if ($merchants) {
                    foreach ($merchants as $key => $v) {
                        $sids[] = $v['id'];
                    }
                    $where['sid'] = $sids;
                } else {
                    $where['sid'] = [];
               }
            }
        }
        #1、列表获取
        $list = pdo_getslice(PDO_NAME . 'delivery_category',$where,[$page, $pageIndex],$total,['id','status','name','sort','sid'],'','sort DESC,id DESC');
        foreach ($list as &$li){
            $li['merchantname'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$li['sid']),'storename');
        }
        $pager = wl_pagination($total, $page, $pageIndex);


        include wl_template('delivery/catelist');
    }

    /**
     * Comment: 同城配送分类编辑页面
     * Author: wlf
     * Date: 2020/03/19 10:38
     */
    public function cateModel(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'];
        if($id){
            $info = pdo_get('wlmerchant_delivery_category',['id'=>$id]);
            $merchant = pdo_get('wlmerchant_merchantdata',['id'=>$info['sid']],['id','storename','logo']);
            $merchant['logo'] = tomedia($merchant['logo']);
            if(!$info) show_json(0,'分类不存在，请刷新重试!');
        }

        include wl_template('delivery/cate_model');
    }

    /**
     * Comment: 商品分类添加/编辑
     * Author: wlf
     * Date: 2020/03/19 10:46
     */
    public function cateEdit(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'];
        $data = $_GPC['data'];
        if($id > 0){
            //修改操作
            $res = pdo_update('wlmerchant_delivery_category',$data,['id'=>$id]);
        }else{
            //添加操作
            if(empty($data['name'])) show_json(0,'分类名称不能为空!');
            if(empty($data['sid'])) $data['sid'] = $_W['storeid'];
            $data['aid'] = $_W['aid'];
            $data['uniacid'] = $_W['uniacid'];
            $data['status'] = 1;
            $res = pdo_insert('wlmerchant_delivery_category',$data);
        }
        #4、判断操作是否超过
        if($res) show_json(1, '操作成功');
        else show_json(0, '操作失败,请刷新页面重试！');
    }

    /**
     * Comment: 商品分类名称修改
     * Author: wlf
     * Date: 2020/03/19 11:09
     */
    public function cateNameChange(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'] OR show_json(0,'参数错误，请刷新重试!');
        $value = $_GPC['value'] OR show_json(0,'参数错误，请刷新重试!');
        $type = $_GPC['type'] OR show_json(0,'参数错误，请刷新重试!');
        #2、信息修改
        $res = pdo_update('wlmerchant_delivery_category',[$type => $value],['id'=>$id]);
        if($res) show_json(1,'修改成功');
        else show_json(0,'修改失败，请刷新重试!');
    }

    /**
     * Comment: 删除分类
     * Author: wlf
     * Date: 2020/03/19 11:13
     */
    public function cateDelete(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'];
        #3、删除分类
        $res = pdo_delete('wlmerchant_delivery_category',['id'=>$id]);
        #4、判断操作是否超过
        if($res) show_json(1, '操作成功');
        else show_json(0, '操作失败,请刷新页面重试！');
    }
    /**
     * Comment: 修改分类状态
     * Author: wlf
     * Date: 2020/03/25 18:13
     */
    public function changestatus(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'];
        $res = pdo_update('wlmerchant_delivery_category',array('status' => $status),array('id' => $id));
        if($res)  wl_json(0, $message = '操作成功');
        else wl_json(1,'操作失败,请刷新页面重试！');
    }

}