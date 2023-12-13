<?php
defined('IN_IA') or exit('Access Denied');
require_once __DIR__ . "/../../vendor/autoload.php";//引入composer自动加载类
define('IN_SYS', true);
global $_W, $_GPC;
load()->web('common');
load()->web('template');
load()->func('tpl');
Func_loader::core('tpl');

$_W['token'] = token();
$_W['uniacid'] = $_GPC['i'] ? intval($_GPC['i']) : intval($_GPC['__wluniacid_session']);

$session = json_decode(base64_decode($_GPC['__wlstore_session']), true);
if (is_array($session)) {
    if (!empty($_W['uniacid']) && $_W['uniacid'] != $session['uniacid']) {
        isetcookie('__wlstore_session', '', -10000);
        isetcookie('__wlstoreid_session', '', -10000);
    } else {
        $_W['mid'] = $session['mid'];
        $_W['uniacid'] = $session['uniacid'];
        $_W['storeuser'] = Member::wl_member_get($session['mid']);
    }
}
if (!empty($_W['uniacid'])) {
    $_W['uniaccount'] = $_W['account'] = uni_fetch($_W['uniacid']);
    $_W['acid'] = $_W['account']['acid'];
}
if (empty($_W['uniacid'])) {
    die('缺少重要参数，请检查链接是否正确');
}


if (!empty($_GPC['checkstoreid'])) {
    $_W['storeid'] = intval($_GPC['checkstoreid']);
    isetcookie('__wlstoreid_session', intval($_GPC['checkstoreid']), 86400, true);
    $user = pdo_get(PDO_NAME . 'merchantuser', array('uniacid' => $_W['uniacid'], 'storeid' => $_W['storeid'], 'mid' => $_W['mid']), array('ismain', 'manage_store', 'hasmanage'));
    if ($user['ismain'] == 4) { //业务员
        if ($user['manage_store']) {
            if (empty($user['hasmanage'])) {
                wl_message('抱歉，您无权管理店铺！', web_url('user/storelogin/store_login'), 'warning');
            }
        } else {
            $salesetting = Setting::wlsetting_read('salesman');
            if (empty($salesetting['hasmanage'])) {
                wl_message('抱歉，您无权管理店铺！', web_url('user/storelogin/store_login'), 'warning');
            }
        }
    } else if (empty($user['ismain']) || $user['ismain'] == 2) {
        wl_message('抱歉，您无权管理店铺！', web_url('user/storelogin/store_login'), 'warning');
    }
    $_W['storeismain'] = $user['ismain'];
    isetcookie('__storeismain_session'.$_W['storeid'].$_W['mid'], intval($user['ismain']), 86400, true);
}
isetcookie('__wluniacid_session', $_W['uniacid'], 7 * 86400, true);
isetcookie('__uniacid', $_W['uniacid'], 7 * 86400, true);

$_W['storeid'] = intval($_GPC['__wlstoreid_session']);
$_W['storeismain'] = intval($_GPC['__storeismain_session'.$_W['storeid'].$_W['mid']]);
if(empty($_W['storeismain'])){
    $user = pdo_get(PDO_NAME . 'merchantuser', array('uniacid' => $_W['uniacid'], 'storeid' => $_W['storeid'], 'mid' => $_W['mid']), array('ismain', 'manage_store', 'hasmanage'));
    $_W['storeismain'] = $user['ismain'];
    isetcookie('__storeismain_session'.$_W['storeid'].$_W['mid'], intval($user['ismain']), 86400, true);
}

if (empty($_W['aid'])) {
    $_W['aid'] = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $_W['storeid']), 'aid');
}
if (empty($_W['authority'])) {
    $groupid = pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $_W['storeid']), 'groupid');
    if ($groupid) {
        $authority = pdo_getcolumn(PDO_NAME . 'chargelist', array('id' => $groupid), 'authority');
    }
    $_W['authority'] = unserialize($authority);
}
if($_W['storeid'] > 0){
    $enabled = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$_W['storeid']),'enabled');
    if($enabled != 1){
        isetcookie('__wlstore_session', '', -10000);
        isetcookie('__wlstoreid_session', '', -10000);
    }
    if($enabled == 2){
        wl_message('商户暂停营业中，无法登录后台！', web_url('user/storelogin/store_login'), 'warning');
    }else if($enabled == 3){
        wl_message('商户已过期，无法登录后台！', web_url('user/storelogin/store_login'), 'warning');
    }else if($enabled == 4){
        wl_message('商户已删除，无法登录后台！', web_url('user/storelogin/store_login'), 'warning');
    }else if($enabled == 5){
        wl_message('商户审核中，无法登录后台！', web_url('user/storelogin/store_login'), 'warning');
    }else if($enabled == 6){
        wl_message('商户未在入驻中，无法登录后台！', web_url('user/storelogin/store_login'), 'warning');
    }
}
if ((empty($_W['storeid']) || empty($_W['mid'])) && $_W['controller'] != 'storelogin') {
    wl_message('抱歉，您无权进行该操作，请先登录！', web_url('user/storelogin/store_login'), 'warning');
}
$_W['storeinfo'] = pdo_get(PDO_NAME . 'merchantdata',array('id' => $_W['storeid']),array('marketstatus'));



























