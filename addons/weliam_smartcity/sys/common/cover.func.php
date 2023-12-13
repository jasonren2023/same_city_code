<?php
defined('IN_IA') or exit('Access Denied');
define('IN_SYS', true);
global $_W, $_GPC;
file_exists(VERSION_PATH) && require_once VERSION_PATH;
Func_loader::core('tpl');

$_W['aid'] = 0;
$_W['wlcloud']['authinfo'] = Cloud::wl_syssetting_read('authinfo');
if(empty($_W['highest_role'])){
    header("Location: " . url('user/login'));
}
if ($_W['isfounder'] && $_W['plugin'] != 'cloud' && ($_W['wlcloud']['authinfo']['status'] == 0 && $_W['wlcloud']['authinfo']['endtime'] > time()) && !IS_DEV) {
    $version = pdo_getcolumn('modules', array('name' => MODULE_NAME), 'version');
    if ($version != WELIAM_VERSION) {
        header("Location: " . web_url('cloud/auth/upgrade', ['up' => 'now']));
        exit;
    }
}

if(!empty(strstr($_W['siteroot'],'zbczc.com'))){
    wl_message("此域名被禁止访问!请联系管理员。");
}


//以下是代理商员工权限的操作。当前登录的账号为员工账号.进行权限判断
$ESession = json_decode(base64_decode($_GPC['__wlsystem_staff_session']), true);
if($ESession){
    //判断该员工账号是否存在  Employee information 。并且获取该员工的权限信息
    if(!$ESession['uniacid'] && $_W['uniacid'] > 0) $ESession['uniacid'] = $_W['uniacid'];
    $_W['EInfor'] = $EInfo = pdo_get(PDO_NAME."agentadmin",$ESession);
    if(!$EInfo){
        //员工不存在/已被删除
        isetcookie('__session', '', -10000);//删除代理商登录信息
        isetcookie('__wlsystem_staff_session', '', -10000);//删除员工登录信息
        wl_message("您的信息不存在!请联系管理员。", web_url('user/login/adminStaffLogin'));
    }
    //判断是否存在uniacid
    if(!$_W['uniacid']) $_W['uniacid'] = $EInfo['uniacid'];


    //获取当前员工的权限路径列表    判断是否拥有访问权限
    $_W['jurisdiction'] = unserialize($EInfo['jurisdiction']);
    if(count($_W['jurisdiction']) <= 0) wl_message("对不起！您没有访问权限。", getenv("HTTP_REFERER"));
    //调用权限方法  获取跳转地址
    Jurisdiction::judge();
}

