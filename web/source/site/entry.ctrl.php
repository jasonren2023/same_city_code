<?php

defined('IN_IA') or exit('Access Denied');

load()->model('module');
load()->model('extension');

$entry_module_name = safe_gpc_string($_GPC['module_name']) ? safe_gpc_string($_GPC['module_name']) : safe_gpc_string($_GPC['m']);
$entry = array(
    'module' => $entry_module_name,
    'do'     => $_GPC['do'],
    'state'  => $_GPC['state'],
    'direct' => $_GPC['direct'],
);
if (empty($entry['do'])) {
    itoast('非法访问.', '', '');
}

$module = module_fetch($entry['module']);
if (empty($module)) {
    itoast("访问非法, 没有操作权限. (module: {$entry['module']})", '', '');
}
$_GPC['__entry'] = $entry['title'];
$_GPC['__state'] = $entry['state'];
$_GPC['state'] = $entry['state'];
$_GPC['m'] = $entry['module'];
$_GPC['do'] = $entry['do'];

$_W['current_module'] = $module;

if ('system_welcome' == $entry['entry'] || 'system_welcome' == $_GPC['module_type']) {
    $_GPC['module_type'] = 'system_welcome';
    define('SYSTEM_WELCOME_MODULE', true);
    $site = WeUtility::createModuleSystemWelcome($entry['module']);
} else {
    $site = WeUtility::createModuleSite($entry['module']);
}

define('IN_MODULE', $entry['module']);
if (!is_error($site)) {
    if (ACCOUNT_MANAGE_NAME_OWNER == $_W['role']) {
        $_W['role'] = ACCOUNT_MANAGE_NAME_MANAGER;
    }
    $sysmodule = module_system();
    if (in_array($m, $sysmodule)) {
        $site_urls = $site->getTabUrls();
    }


    $do_function = defined('SYSTEM_WELCOME_MODULE') ? 'doPage' : 'doWeb';
    $method = $do_function . ucfirst($entry['do']);

    exit($site->$method());
}
itoast("访问的方法 {$method} 不存在.", referer(), 'error');