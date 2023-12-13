<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 商户套餐管理(入驻套餐)
 * Author: zzw
 * Date: 2021/1/7 15:36
 * Class StorePayMeal_WeliamController
 */
class StoreSetMeal_WeliamController{
    //套餐列表
    public function chargelist(){
        global $_W,$_GPC;
        $pindex = max(1, $_GPC['page']);
        $where['uniacid'] = $_W['uniacid'];
        if(is_agent()){
            $where['aid'] = $_W['aid'];
        }
        $listData = Util::getNumData("*", PDO_NAME . 'chargelist',$where, 'sort desc,id desc', $pindex, 10, 1);
        $list = $listData[0];
        $pager = $listData[1];
        foreach ($list as $key => &$v) {
            if($v['aid']){
                $v['agentname'] = pdo_getcolumn(PDO_NAME.'agentusers',array('id'=>$v['aid']),'agentname');
            }else{
                $v['agentname'] = '平台通用';
            }
        }
        include wl_template('store/chargelist');
    }
    /**
     * Comment: 商户套餐添加、修改操作
     * Author: zzw
     * Date: 2020/4/13 11:04
     */
    public function add() {
        global $_W, $_GPC;
        #1、参数获取
        $id         = $_GPC['id'] ? : '';
        if (checksubmit('submit')) {
            //添加、修改操作
            $memberType              = $_GPC['data'] ? : [];
            $memberType['authority'] = serialize($_GPC['authority']);

            //团长分红数组
            if($memberType['disgroup'] > 0){
                $grouparray = [];
                $groupleid = $_GPC['groupleid'];
                $onegroupmoney = $_GPC['onegroupmoney'];
                $twogroupmoney = $_GPC['twogroupmoney'];
                foreach($groupleid as $gkey => $gle){
                    $glea['onegroupmoney'] = sprintf("%.2f",$onegroupmoney[$gkey]);
                    $glea['twogroupmoney'] = sprintf("%.2f",$twogroupmoney[$gkey]);
                    $grouparray[$gle] = $glea;
                }
                $memberType['grouparray'] = serialize($grouparray);
            }


            //根据是否存在id  判断是添加信息还是修改信息
            if ($id) {
                //判断是否修改信息
                $isUpdate = pdo_get(PDO_NAME."chargelist",$memberType);
                if($isUpdate) wl_message('请修改后提交！' , web_url('store/storeSetMeal/add',['id'=>$id]) , 'error');
                $res = pdo_update(PDO_NAME . 'chargelist' , $memberType , ['id' => $_GPC['id']]);
            }else {
                $memberType['aid']     = $_W['aid'];
                $memberType['uniacid'] = $_W['uniacid'];
                $res                   = pdo_insert(PDO_NAME . 'chargelist' , $memberType);
            }
            //判断操作是否成功
            if ($res) wl_message('操作成功！' , web_url('store/storeSetMeal/chargelist') , 'success');
            else wl_message('操作失败！' , web_url('store/storeSetMeal/chargelist') , 'error');
        }
        #2、获取基本信息
        if ($id) {
            $data = Util::getSingelData("*", PDO_NAME . 'chargelist', ['id' => $id]);
            $data['authority'] = unserialize($data['authority']);
            $grouparray = unserialize($data['grouparray']);
        }
        $agents = pdo_getall(PDO_NAME.'agentusers',['uniacid' => $_W['uniacid'],'status'=>1],['id','agentname']);
        $grouplevel = pdo_getall('wlmerchant_grouplevel', array('uniacid' => $_W['uniacid']),['id','name']);


        include  wl_template('store/chargeadd');
    }
    //删除套餐
    public function delete() {
        global $_W, $_GPC;
        $res = pdo_delete(PDO_NAME . 'chargelist', array('id' => $_GPC['id']));
        if($res){
            show_json(1);
        }else{
            show_json(0,'删除失败，请刷新重试');
        }
    }
    /**
     * Comment: 修改状态
     * Author: zzw
     * Date: 2019/9/18 16:33
     */
    public function changeStatus (){
        global $_W , $_GPC;
        #1、获取参数信息
        $id = $_GPC['id'] OR Commons::sRenderError('缺少参数：id');
        $status = $_GPC['status'] ? : 0;
        #1、修改操作
        $res = pdo_update(PDO_NAME."chargelist",['status'=>$status],['id'=>$id]);
        if($res) Commons::sRenderSuccess('修改成功');
        else Commons::sRenderError('修改失败，请刷新重试!');
    }
}