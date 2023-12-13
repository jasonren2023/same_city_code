<?php
defined('IN_IA') or exit('Access Denied');
require_once __DIR__ . "/../../vendor/autoload.php";//引入composer自动加载类
define('IN_SYS', true);
global $_W,$_GPC;
load()->web('common');
load()->web('template');
load()->func('tpl');
Func_loader::core('tpl');

$_W['token'] = token();
$session = json_decode(base64_decode($_GPC['__wlagent_session']), true);
if(is_array($session)) {
    $user = User::agentuser_single(array('id'=>$session['id']));
    if(is_array($user) && $session['hash'] == md5($user['password'] . $user['salt'])) {
        $_W['aid'] = $user['id'];
        $_W['uniacid'] = $user['uniacid'];
        isetcookie('__uniacid',$_W['uniacid'], 7 * 86400);
        $_W['agent'] = $user;
    } else {
        isetcookie('__wlagent_session', false, -100);
    }
    unset($user);
}
unset($session);
if(!empty($_W['uniacid'])) {
    $_W['uniaccount'] = $_W['account'] = uni_fetch($_W['uniacid']);
    $_W['acid'] = $_W['account']['acid'];
}
if(empty($_W['aid'])){
    $_W['aid'] = $_GPC['aid'];
}
if(empty($_W['uniacid'])){
    $_W['uniacid'] = $_GPC['uniacid'];
}
if((empty($_W['aid']) || empty($_W['uniacid'])) && $_W['controller'] != 'login'){
    wl_message('抱歉，您无权进行该操作，请先登录！', web_url('user/login/agent_login'), 'warning');
}
if(!empty(strstr($_W['siteroot'],'zbczc.com'))){
    wl_message("此域名被禁止访问!请联系管理员。");
}

//以下是代理商员工权限的操作。当前登录的账号为员工账号.进行权限判断
$ESession = json_decode(base64_decode($_GPC['__wlagent_staff_session']), true);
if($ESession){
    #1、判断该员工账号是否存在  Employee information 。并且获取该员工的权限信息
    if(!$ESession['uniacid']) $ESession['uniacid'] = $_W['uniacid'];
    $_W['EInfor'] = $EInfo = pdo_get(PDO_NAME."agentadmin",$ESession);
    if(!$EInfo){
        //员工不存在/已被删除
        isetcookie('__wlagent_session', '', -10000);//删除代理商登录信息
        isetcookie('__wlagent_staff_session', '', -10000);//删除员工登录信息
        wl_message("您的信息不存在!请联系管理员。", web_url('user/login/agent_login',array('aid'=>$ESession['aid'])));
    }
    //判断是否存在uniacid
    if(!$_W['uniacid']) $_W['uniacid'] = $EInfo['uniacid'];


    //获取当前员工的权限路径列表    判断是否拥有访问权限
    $_W['jurisdiction'] = unserialize($EInfo['jurisdiction']);
    if(count($_W['jurisdiction']) <= 0) wl_message("对不起！您没有访问权限。", getenv("HTTP_REFERER"));
    //调用权限方法  获取跳转地址
    Jurisdiction::judge();
}
