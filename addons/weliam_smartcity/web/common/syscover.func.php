<?php
defined('IN_IA') or exit('Access Denied');
require_once __DIR__ . "/../../vendor/autoload.php";//引入composer自动加载类
define('IN_STAFF', true);
global $_W, $_GPC;
load()->web('common');
load()->web('template');
load()->func('tpl');
Func_loader::core('tpl');

file_exists(VERSION_PATH) && require_once VERSION_PATH;

$_W['aid'] = 0;
$_W['uniacid'] = $_GPC['i'] ? intval($_GPC['i']) : intval($_GPC['__wluniacid_session']);

$ESession = json_decode(base64_decode($_GPC['__wlsystem_staff_session']), true);
//判断是否登录  未登录跳转到登录页面
if(!is_array($ESession) && $_GPC['ac'] != 'login') {
    //未登录 进入登录页面
    $url = web_url('user/login/adminStaffLogin');

    header("Location: " . $url);
}
if(!empty(strstr($_W['siteroot'],'zbczc.com'))){
    wl_message("此域名被禁止访问!请联系管理员。");
}

if($ESession){
    //判断该员工账号是否存在  Employee information 。并且获取该员工的权限信息
    if(!$ESession['uniacid'] && $_W['uniacid'] > 0) $ESession['uniacid'] = $_W['uniacid'];
    $ESession['aid'] = $_W['aid'];//平台员工aid固定为1
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
if (!empty($_W['uniacid'])) {
    $_W['uniaccount'] = $_W['account'] = uni_fetch($_W['uniacid']);
    $_W['acid'] = $_W['account']['acid'];
}

