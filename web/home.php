<?php

define('IN_SYS', true);

require '../framework/bootstrap.inc.php';
// echo 22;die;
require IA_ROOT . '/web/common/bootstrap.sys.inc.php';

if (empty($_W['isfounder']) && !empty($_W['user']) && ($_W['user']['status'] == USER_STATUS_CHECK || $_W['user']['status'] == USER_STATUS_BAN)) {
	isetcookie('__session', '', -10000);
	itoast('您的账号正在审核或是已经被系统禁止，请联系网站管理员解决！');
}
if (($_W['setting']['copyright']['status'] == 1) && empty($_W['isfounder'])) {
	isetcookie('__session', '', -10000);
	itoast('站点已关闭，关闭原因：' . $_W['setting']['copyright']['reason'], url('user/login'), 'info');
}

$_W['page'] = array();
$_W['page']['copyright'] = $_W['setting']['copyright'];

checklogin();
isetcookie('__iscontroller', 0);
$_W['iscontroller'] = 0;

function _calc_current_frames() {
	global $_W;
	$_W['page']['title'] = '我的平台';
	return true;
}
if ($_GPC['getmenu']) {
	$home_menu = system_star_menu();
	iajax(0, $home_menu);
}
template('home/home');