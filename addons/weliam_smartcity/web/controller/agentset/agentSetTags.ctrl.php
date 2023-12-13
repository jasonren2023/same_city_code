<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 标签管理
 * Author: zzw
 * Date: 2021/1/8 10:21
 * Class AgentSetTags_WeliamController
 */
class AgentSetTags_WeliamController{
    //标签列表
    public function tags()
    {
        global $_W , $_GPC;
        $pindex = max(1 , intval($_GPC['page']));
        $psize  = 10;
        $type   = $_GPC['type'] ? $_GPC['type'] : 0;
        $datas  = Util::getNumData('*' , 'wlmerchant_tags' , [
            'uniacid' => $_W['uniacid'] ,
            'aid'     => $_W['aid'] ,
            'type'    => $type
        ] , 'type ASC,sort DESC,id DESC' , $pindex , $psize , 1);
        $tags   = $datas[0];
        $pager  = $datas[1];
        include wl_template('agentset/tagsindex');
    }
    //添加、编辑标签信息
    public function tagsedit()
    {
        global $_W , $_GPC;
        if (!empty($_GPC['id'])) {
            $tag = pdo_get('wlmerchant_tags' , ['id' => $_GPC['id']]);
        }
        if (checksubmit('submit')) {
            $tag            = $_GPC['tag'];
            $tag['title']   = trim($tag['title']);
            $tag['sort']    = intval($tag['sort']);
            $tag['enabled'] = intval($_GPC['enabled']);
            if (empty($_GPC['tagid'])) {
                $tag['uniacid']    = $_W['uniacid'];
                $tag['aid']        = $_W['aid'];
                $tag['createtime'] = time();
                $res               = pdo_insert(PDO_NAME . 'tags' , $tag);
            }
            else {
                $res = pdo_update('wlmerchant_tags' , $tag , ['id' => $_GPC['tagid']]);
            }
            if ($res) {
                wl_message('保存成功' , web_url('agentset/agentSetTags/tags') , 'success');
            }
            else {
                wl_message('保存失败' , referer() , 'error');
            }
        }
        include wl_template('agentset/tagsedit');
    }
    //修改、删除标签信息
    public function changeinfo()
    {
        global $_W , $_GPC;
        $id       = $_GPC['id'];
        $type     = $_GPC['type'];
        $newvalue = trim($_GPC['value']);
        if ($type == 1) {
            $res = pdo_update('wlmerchant_tags' , ['title' => $newvalue] , ['id' => $id]);
        }
        else if ($type == 2) {
            $res = pdo_update('wlmerchant_tags' , ['sort' => $newvalue] , ['id' => $id]);
        }
        else if ($type == 3) {
            $res = pdo_update('wlmerchant_tags' , ['enabled' => $newvalue] , ['id' => $id]);
        }
        else if ($type == 4) {
            $res = pdo_delete('wlmerchant_tags' , ['id' => $id]);
        }
        if ($res) {
            show_json(1 , '修改成功');
        }
        else {
            show_json(0 , '修改失败，请重试');
        }
    }
}
