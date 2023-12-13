<?php
define('IN_MOBILE', true);
require '../framework/bootstrap.inc.php';
require IA_ROOT . '/app/common/bootstrap.app.inc.php';

$acl = array(
    'home' => array(
        'default' => 'home',
    ),
    'mc'   => array(
        'default' => 'home'
    )
);

if ($_W['setting']['copyright']['status'] == 1) {
    $_W['siteclose'] = true;
    message('抱歉，站点已关闭，关闭原因：' . $_W['setting']['copyright']['reason']);
}

$controllers = array();
$handle = opendir(IA_ROOT . '/app/source/');
if (!empty($handle)) {
    while ($dir = readdir($handle)) {
        if ($dir != '.' && $dir != '..') {
            $controllers[] = $dir;
        }
    }
}
if (!in_array($controller, $controllers)) {
    $controller = 'home';
}
$init = IA_ROOT . "/app/source/{$controller}/__init.php";
if (is_file($init)) {
    require $init;
}

$actions = array();
$handle = opendir(IA_ROOT . '/app/source/' . $controller);
if (!empty($handle)) {
    while ($dir = readdir($handle)) {
        if ($dir != '.' && $dir != '..' && strexists($dir, '.ctrl.php')) {
            $dir = str_replace('.ctrl.php', '', $dir);
            $actions[] = $dir;
        }
    }
}

if (!in_array($action, $actions)) {
    $action = $acl[$controller]['default'];
}
if (!in_array($action, $actions)) {
    $action = $actions[0];
}
require _forward($controller, $action);

function _forward($c, $a)
{
    $file = IA_ROOT . '/app/source/' . $c . '/' . $a . '.ctrl.php';
    return $file;
}