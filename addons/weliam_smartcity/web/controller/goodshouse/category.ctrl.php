<?php
defined('IN_IA') or exit('Access Denied');

class Category_WeliamController {
    /**
     * Comment: 商品分类名称修改
     * Author: zzw
     * Date: 2019/12/20 14:20
     */
    public function cateNameChange(){
        global $_W,$_GPC;
        #1、参数获取
        $plugin = $_GPC['plugin'] OR show_json(0,'参数错误，请刷新重试!');
        $id = $_GPC['id'] OR show_json(0,'参数错误，请刷新重试!');
        $value = $_GPC['value'] OR show_json(0,'参数错误，请刷新重试!');
        #1、信息修改
        switch ($plugin) {
            case 'rush':
                $tableName = PDO_NAME."rush_category";
                break;//抢购
            case 'groupon':
                $tableName = PDO_NAME."groupon_category";
                break;//团购
            case 'wlfightgroup':
                $tableName = PDO_NAME."fightgroup_category";
                break;//拼团
            case 'bargain':
                $tableName = PDO_NAME."bargain_category";
                break;//砍价
        }
        $res = pdo_update($tableName,['name'=>$value],['id'=>$id]);
        if($res) show_json(1,'修改成功');
            else show_json(0,'修改失败，请刷新重试!');
    }
    /**
     * Comment: 调取分类编辑页面
     * Author: zzw
     * Date: 2019/12/20 14:35
     */
    public function cateModel(){
        global $_W,$_GPC;
        #1、参数获取
        $plugin = $_GPC['plugin'] OR show_json(0,'参数错误，请刷新重试!');
        $id = $_GPC['id'];
        #1、参数获取
        if($id){
            switch ($plugin) {
                case 'rush':
                    $tableName = PDO_NAME."rush_category";
                    break;//抢购
                case 'groupon':
                    $tableName = PDO_NAME."groupon_category";
                    break;//团购
                case 'wlfightgroup':
                    $tableName = PDO_NAME."fightgroup_category";
                    break;//拼团
                case 'bargain':
                    $tableName = PDO_NAME."bargain_category";
                    break;//砍价
            }
            $info = pdo_get($tableName,['id'=>$id]);
            if(!$info) show_json(0,'分类不存在，请刷新重试!');
            if($plugin == 'wlfightgroup'){
                $info['sort'] = $info['listorder'];
                $info['thumb'] = $info['logo'];
                unset($info['listorder']);
                unset($info['logo']);
            }
        }

        include wl_template('goodshouse/cate_model');
    }
    /**
     * Comment: 商品分类添加/编辑
     * Author: zzw
     * Date: 2019/12/20 14:48
     */
    public function cateEdit(){
        global $_W,$_GPC;
        #1、参数获取
        $plugin = $_GPC['plugin'] OR show_json(0,'参数错误，请刷新重试!');
        $id = $_GPC['id'];
        $data = $_GPC['data'];
        #2、表获取
        switch ($plugin) {
            case 'rush':
                $tableName = PDO_NAME."rush_category";
                break;//抢购
            case 'groupon':
                $tableName = PDO_NAME."groupon_category";
                break;//团购
            case 'wlfightgroup':
                $tableName = PDO_NAME."fightgroup_category";
                $data['listorder'] = $data['sort'];
                $data['logo'] = $data['thumb'];
                unset($data['sort']);
                unset($data['thumb']);
                break;//拼团
            case 'bargain':
                $tableName = PDO_NAME."bargain_category";
                break;//砍价
        }
        #3、添加/修改 操作
        if($id > 0){
            //修改操作
            $res = pdo_update($tableName,$data,['id'=>$id]);
        }else{
            //添加操作
            if(empty($data['name'])) show_json(0,'分类名称不能为空!');
            $data['aid'] = $_W['aid'];
            $data['uniacid'] = $_W['uniacid'];
            $res = pdo_insert($tableName,$data);
        }
        #4、判断操作是否超过
        if($res) show_json(1, '操作成功');
            else show_json(0, '操作失败,请刷新页面重试！');
    }
    /**
     * Comment: 删除分类
     * Author: zzw
     * Date: 2019/12/20 14:54
     */
    public function cateDelete(){
        global $_W,$_GPC;
        #1、参数获取
        $plugin = $_GPC['plugin'] OR show_json(0,'参数错误，请刷新重试!');
        $id = $_GPC['id'];
        #2、表获取
        switch ($plugin) {
            case 'rush':
                $tableName = PDO_NAME."rush_category";
                break;//抢购
            case 'groupon':
                $tableName = PDO_NAME."groupon_category";
                break;//团购
            case 'wlfightgroup':
                $tableName = PDO_NAME."fightgroup_category";
                break;//拼团
            case 'bargain':
                $tableName = PDO_NAME."bargain_category";
                break;//砍价
        }
        #3、删除分类
        $res = pdo_delete($tableName,['id'=>$id]);
        #4、判断操作是否超过
        if($res) show_json(1, '操作成功');
            else show_json(0, '操作失败,请刷新页面重试！');
    }
    /**
     * Comment: 导入商家分类信息
     * Author: zzw
     * Date: 2019/12/20 15:27
     */
    public function cateImportShop(){
        global $_W,$_GPC;
        global $_W,$_GPC;
        #1、参数获取
        $plugin = $_GPC['plugin'] OR show_json(0,'参数错误，请刷新重试!');
        #2、表获取
        switch ($plugin) {
            case 'rush':
                $tableName = PDO_NAME."rush_category";
                break;//抢购
            case 'groupon':
                $tableName = PDO_NAME."groupon_category";
                break;//团购
            case 'wlfightgroup':
                $tableName = PDO_NAME."fightgroup_category";
                break;//拼团
            case 'bargain':
                $tableName = PDO_NAME."bargain_category";
                break;//砍价
            case 'activity':
                $tableName = PDO_NAME."activity_category";
                break;//活动
        }
        #3、商家分类获取(仅获取一级)
        $shopList = pdo_fetchall("SELECT `name`,thumb FROM ".tablename(PDO_NAME."category_store")
                                 ." WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND parentid = 0 AND state = 0 ORDER BY displayorder ASC ");
        #4、获取当前分类列表最大排序
        $where = " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} ";
        if($plugin == 'wlfightgroup'){
            $max = pdo_fetchcolumn("SELECT MAX(listorder) FROM ".tablename($tableName).$where);
        }else{
            $max = pdo_fetchcolumn("SELECT MAX(sort) FROM ".tablename($tableName).$where);
        }
        #4、循环进行导入操作
        foreach ($shopList as $key => $val) {
            $max++;
            $data = [
                'aid'     => $_W['aid'] ,
                'uniacid' => $_W['uniacid'] ,
                'name'    => $val['name'] ,
            ];
            //根据类型 进行排序和图片的定义
            if($plugin == 'wlfightgroup'){
                $data['logo'] = $val['thumb'];
                $data['listorder'] = $max;
            }else if($plugin == 'activity'){
                $data['logo'] = $val['thumb'];
                $data['sort'] = $max;
                $data['status'] = 1;
            } else{
                $data['thumb'] = $val['thumb'];
                $data['sort'] = $max;
            }
            //判断是否已经存在同名称的分类 不存在则添加 存在则跳过
            if(!pdo_get($tableName,['name'=>$data['name'],'uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']])){
                pdo_insert($tableName,$data);
            }
        }

        show_json(1, '操作成功');
    }

}