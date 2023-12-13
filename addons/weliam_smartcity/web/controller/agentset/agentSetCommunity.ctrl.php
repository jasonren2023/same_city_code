<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 社群管理
 * Author: zzw
 * Date: 2021/1/8 10:15
 * Class agentSetCommunity_WeliamController
 */
class agentSetCommunity_WeliamController{
    //社群列表
    function communityList()
    {
        global $_W , $_GPC;
        $pindex           = max(1 , intval($_GPC['page']));
        $psize            = 10;
        $where['uniacid'] = $_W['uniacid'];
        $where['aid']     = $_W['aid'];
        if (!empty($_GPC['keyword'])) {
            $keyword              = trim($_GPC['keyword']);
            $where['communname@'] = trim($keyword);
        }
        $communityData = Util::getNumData("*" , PDO_NAME . 'community' , $where , 'createtime desc' , $pindex , $psize , 1);
        $communitylist = $communityData[0];
        $pager         = $communityData[1];
        $commset = Setting::agentsetting_read('community');
        include wl_template('agentset/communityList');
    }
    //设置为首页社群
    function changecomm()
    {
        global $_W , $_GPC;
        $id        = $_GPC['id'];
        $community = pdo_get('wlmerchant_community' , ['id' => $id]);
        Setting::agentsetting_save($community , 'community');
        show_json(1);
    }
    //社群添加
    function communityAdd()
    {
        global $_W , $_GPC;
        $id        = $_GPC['id'];
        $community = pdo_get('wlmerchant_community' , ['id' => $id]);
        if ($_W['ispost']) {
            $data = $_GPC['community'];
            if ($id) {
                if ($community['communqrcode'] != $data['communqrcode']) {
                    $data['media_id']      = '';
                    $data['media_endtime'] = 0;
                }
                $res = pdo_update('wlmerchant_community' , $data , ['id' => $id]);
            }
            else {
                $data['uniacid']    = $_W['uniacid'];
                $data['aid']        = $_W['aid'];
                $data['createtime'] = time();
                $res                = pdo_insert(PDO_NAME . 'community' , $data);
            }
            if ($res) {
                wl_message('操作成功！' , web_url('agentset/agentSetCommunity/communityList'));
            }
            else {
                wl_message('操作失败，请重试！');
            }
        }
        include wl_template('agentset/communityAdd');
    }
    //删除社群
    function deletecommunity()
    {
        global $_W , $_GPC;
        $id  = $_GPC['id'];
        $res = pdo_delete('wlmerchant_community' , ['id' => $id]);
        if ($res) {
            show_json(1);
        }
        else {
            show_json(0 , '删除失败,请重试');
        }
    }
}
