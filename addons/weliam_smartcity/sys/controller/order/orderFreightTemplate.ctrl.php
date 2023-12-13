<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 运费模板
 * Author: zzw
 * Date: 2021/1/7 17:28
 * Class orderFreightTemplate_WeliamController
 */
class orderFreightTemplate_WeliamController {
    //运费模板列表
    public function freightlist() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $wheres = array();
        $wheres['uniacid'] = $_W['uniacid'];
        $wheres['aid'] = $_W['aid'];
        if(is_store()){
            $wheres['sid'] = $_W['storeid'];
        }
        $freightlist = Store::getNumExpress('*',$wheres,'ID DESC',$pindex, $psize, 1);
        $pager = $freightlist[1];
        $list = $freightlist[0];

        include wl_template('order/freightlist');
    }
    //新建运费模板
    public function creatfreight(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        if (!is_agent()) {
            $agents = pdo_getall('wlmerchant_agentusers', array('uniacid' => $_W['uniacid']), array('id', 'agentname'));
        }
        if($id){
            $info = pdo_get('wlmerchant_express_template',array('id' => $id));
            $info['expressarray'] = unserialize($info['expressarray']);
        }
        if (checksubmit('submit')){

            $data['name'] = htmlspecialchars($_GPC['expressname']);
            $data['defaultnum'] = intval($_GPC['defaultnum']);
            $data['defaultmoney'] = sprintf('%.2f',$_GPC['defaultmoney']);
            $data['defaultnumex'] = intval($_GPC['defaultnumex']);
            $data['defaultmoneyex'] = sprintf('%.2f',$_GPC['defaultmoneyex']);
            $data['freenumber'] = intval($_GPC['freenumber']);


            if(!empty($_GPC['express']['area']) && is_array($_GPC['express']['area'])){
                foreach($_GPC['express']['area'] as $k=>$v){
                    $expressarray[] = array(
                        'area'=>$v,
                        'num'=> intval($_GPC['express']['num'][$k]),
                        'money'=>sprintf('%.2f',$_GPC['express']['money'][$k]),
                        'numex'=>intval($_GPC['express']['numex'][$k]),
                        'moneyex'=>sprintf('%.2f',$_GPC['express']['moneyex'][$k]),
                        'freenumber'=>intval($_GPC['express']['freenumber'][$k]),
                    );
                }
            }

            $data['expressarray'] = serialize($expressarray);
            $data['createtime'] = time();

            if($_GPC['aid']){
                $data['aid'] = $_GPC['aid'];
            }else{
                $data['aid'] = $_W['aid'];
            }
            $data['sid'] = $_W['storeid'];
            if($id){
                $res = Store::updateExpress($data,$id);
                if ($res) {
                    wl_message('更新运费模板成功', web_url('order/orderFreightTemplate/freightlist'), 'success');
                } else {
                    wl_message('更新运费模板失败', referer(),'error');
                }
            }else {
                $res = Store::saveExpress($data);
                if ($res) {
                    wl_message('创建运费模板成功', web_url('order/orderFreightTemplate/freightlist'), 'success');
                } else {
                    wl_message('创建运费模板失败', referer(), 'error');
                }
            }
        }
        include wl_template('order/creatfreight');
    }
    //删除运费模板
    public function deleteExpress(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $res = Store::deteleExpress($id);
        if($res){
            die(json_encode(array('errno'=>0,'message'=>$res,'id'=>$id)));
        }else {
            die(json_encode(array('errno'=>2,'message'=>$res,'id'=>$id)));
        }
    }

}