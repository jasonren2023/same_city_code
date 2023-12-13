<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 员工管理
 * Author: zzw
 * Date: 2021/1/8 9:57
 * Class AgentSetStaff_WeliamController
 */
class AgentSetStaff_WeliamController{
    //员工列表
    public function adminset(){
        global $_W , $_GPC;
        //校验代理入口文件
        $cityagent = IA_ROOT . '/web/citysys.php';
        $mcityagent = PATH_MODULE . '/web/citysys.php';
        if (!file_exists($cityagent) || md5_file($cityagent) != md5_file($mcityagent)) {
            copy($mcityagent, $cityagent);
        }
        //获取员工列表
        $pindex = max(1 , intval($_GPC['page']));
        $psize  = 10;
        $datas  = Util::getNumData('*' , 'wlmerchant_agentadmin' , [
            'uniacid' => $_W['uniacid'] ,
            'aid'     => $_W['aid']
        ] , 'id DESC' , $pindex , $psize , 1);
        $tags   = $datas[0];
        $pager  = $datas[1];
        foreach ($tags as $key => &$value) {
            $value['nickname'] = pdo_getcolumn(PDO_NAME . 'member' , ['id' => $value['mid']] , 'nickname');
        }
        //获取登录地址
        if($_W['aid'] > 0) $loginUrl = $_W['siteroot']."web/cityagent.php?p=user&ac=login&do=agent_login&aid={$_W['aid']}";//代理商员工登录
        else  $loginUrl = $_W['siteroot']."web/citysys.php?p=user&ac=login&do=adminStaffLogin&i={$_W['uniacid']}";//平台员工登录

        include wl_template('agentset/adminset');
    }
    //添加、编辑员工信息
    public function adminedit()
    {
        global $_W , $_GPC;
        if (!empty($_GPC['id'])) {
            $admin             = pdo_get('wlmerchant_agentadmin' , ['id' => $_GPC['id']]);
            $admin['nickname'] = pdo_getcolumn(PDO_NAME . 'member' , ['id' => $admin['mid']] , 'nickname');
            $jurisdiction      = unserialize($admin['jurisdiction']);
            $noticeauthority   = unserialize($admin['noticeauthority']);
        }
        if (checksubmit('submit')) {
            $admin            = [];
            $admin['account'] = trim($_GPC['account']);
            //判断是否已经有了
            $adminid = $_GPC['adminid'] ? $_GPC['adminid'] : 0;
            $flag    = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_agentadmin') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND account = {$admin['account']} AND id != {$adminid}");
            if ($flag) {
                wl_message('账户名已被注册' , referer() , 'error');
            }
            if($adminid > 0){
                $flag2 = pdo_fetch("SELECT id FROM " . tablename('wlmerchant_agentadmin') . "WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND mid = {$_GPC['mid']} AND id != {$adminid}");
                if ($flag2) {
                    wl_message('此用户已经是管理员' , referer() , 'error');
                }
            }
            $password                 = trim($_GPC['password']);
            $admin['openid']          = trim($_GPC['openid']);
            $admin['mid']             = trim($_GPC['mid']);
            $admin['notice']          = intval($_GPC['notice']);
            $admin['manage']          = intval($_GPC['manage']);
            $admin['jurisdiction']    = serialize($_GPC['jurisdiction']);
            $admin['noticeauthority'] = serialize($_GPC['noticeauthority']);
            if (empty($_GPC['adminid'])) {
                $admin['uniacid']    = $_W['uniacid'];
                $admin['aid']        = $_W['aid'];
                $admin['createtime'] = time();
                $admin['password']   = md5($password);
                $res                 = pdo_insert(PDO_NAME . 'agentadmin' , $admin);
            }
            else {
                $pwd = pdo_getcolumn(PDO_NAME . 'agentadmin' , ['id' => $_GPC['adminid']] , 'password');
                if ($password != $pwd) {
                    //密码已被修改 从新进行加密
                    $admin['password'] = md5($password);
                }
                $res = pdo_update('wlmerchant_agentadmin' , $admin , ['id' => $_GPC['adminid']]);
            }
            if ($res) {
                wl_message('保存成功' , web_url('agentset/agentSetStaff/adminset') , 'success');
            }
            else {
                wl_message('保存失败' , referer() , 'error');
            }
        }
        //获取菜单列表
        $list = Jurisdiction::menuList();
        include wl_template('agentset/adminedit');
    }
    //修改、删除员工通知权限
    public function changeadmin()
    {
        global $_W , $_GPC;
        $id       = $_GPC['id'];
        $type     = $_GPC['type'];
        $newvalue = trim($_GPC['value']);
        if ($type == 1) {
            $res = pdo_update('wlmerchant_agentadmin' , ['notice' => $newvalue] , ['id' => $id]);
        }
        else if ($type == 2) {
            $res = pdo_update('wlmerchant_agentadmin' , ['manage' => $newvalue] , ['id' => $id]);
        }
        else if ($type == 3) {
            $res = pdo_delete('wlmerchant_agentadmin' , ['id' => $id]);
        }
        if ($res) {
            show_json(1 , '修改成功');
        }
        else {
            show_json(0 , '修改失败，请重试');
        }
    }

}
