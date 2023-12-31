<?php

define('IN_IA', true);
define('STARTTIME', microtime());
define('IA_ROOT', str_replace('\\', '/', dirname(dirname(__FILE__))));
define('MAGIC_QUOTES_GPC', (function_exists('get_magic_quotes_gpc') && @get_magic_quotes_gpc()) || @ini_get('magic_quotes_sybase'));
define('TIMESTAMP', time());

$_W = $_GPC = array();
$configfile = IA_ROOT . '/data/config.php';

if (!file_exists($configfile)) {
	if (file_exists(IA_ROOT . '/install.php')) {
        header('Location: ' . '/install.php');
		exit();
	} else {
		header('Content-Type: text/html; charset=utf-8');
		exit('配置文件不存在或是不可读，请检查“data/config”文件或是重新安装！');
	}
}

require $configfile;
require IA_ROOT . '/framework/version.inc.php';
require IA_ROOT . '/framework/const.inc.php';
require IA_ROOT . '/framework/class/loader.class.php';
load()->func('global');
load()->func('compat');
load()->func('compat.biz');
load()->func('pdo');
load()->func('file');
load()->classs('account');
load()->model('cache');
load()->model('account');
load()->model('setting');
load()->model('module');
load()->library('agent');
load()->classs('db');
load()->func('communication');

define('CLIENT_IP', getip());

$_W['config'] = $config;
$_W['config']['db']['tablepre'] = !empty($_W['config']['db']['master']['tablepre']) ? $_W['config']['db']['master']['tablepre'] : $_W['config']['db']['tablepre'];
$_W['timestamp'] = TIMESTAMP;
$_W['charset'] = $_W['config']['setting']['charset'];
$_W['clientip'] = CLIENT_IP;

unset($configfile, $config);

define('ATTACHMENT_ROOT', IA_ROOT . '/attachment/');
error_reporting(0);

if (!in_array($_W['config']['setting']['cache'], array('mysql', 'memcache', 'redis'))) {
	$_W['config']['setting']['cache'] = 'mysql';
}
load()->func('cache');

if (function_exists('date_default_timezone_set')) {
	date_default_timezone_set($_W['config']['setting']['timezone']);
}
if (!empty($_W['config']['setting']['memory_limit']) && function_exists('ini_get') && function_exists('ini_set')) {
	if ($_W['config']['setting']['memory_limit'] != @ini_get('memory_limit')) {
		@ini_set('memory_limit', $_W['config']['setting']['memory_limit']);
	}
}

if (isset($_W['config']['setting']['https']) && $_W['config']['setting']['https'] == '1') {
	$_W['ishttps'] = $_W['config']['setting']['https'];
} else {
	$_W['ishttps'] = isset($_SERVER['SERVER_PORT']) && 443 == $_SERVER['SERVER_PORT'] ||
	isset($_SERVER['HTTP_FROM_HTTPS']) && 'on' == strtolower($_SERVER['HTTP_FROM_HTTPS']) ||
	(isset($_SERVER['HTTPS']) && 'off' != strtolower($_SERVER['HTTPS'])) ||
	isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' == strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) ||
	isset($_SERVER['HTTP_X_CLIENT_SCHEME']) && 'https' == strtolower($_SERVER['HTTP_X_CLIENT_SCHEME']) 			? true : false;
}

$_W['isajax'] = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 'xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']);
$_W['ispost'] = isset($_SERVER['REQUEST_METHOD']) && 'POST' == $_SERVER['REQUEST_METHOD'];

$_W['sitescheme'] = $_W['ishttps'] ? 'https://' : 'http://';
$_W['script_name'] = htmlspecialchars(scriptname());
$sitepath = substr($_SERVER['PHP_SELF'], 0, strrpos($_SERVER['PHP_SELF'], '/'));
$_W['siteroot'] = htmlspecialchars($_W['sitescheme'] . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $sitepath);

if ('/' != substr($_W['siteroot'], -1)) {
	$_W['siteroot'] .= '/';
}
$urls = parse_url($_W['siteroot']);
$urls['path'] = str_replace(array('/web', '/app', '/payment/wechat', '/payment/alipay', '/payment/jueqiymf', '/api'), '', $urls['path']);
$urls['scheme'] = !empty($urls['scheme']) ? $urls['scheme'] : 'http';
$urls['host'] = !empty($urls['host']) ? $urls['host'] : '';
$_W['siteroot'] = $urls['scheme'] . '://' . $urls['host'] . ((!empty($urls['port']) && '80' != $urls['port']) ? ':' . $urls['port'] : '') . $urls['path'];

if (MAGIC_QUOTES_GPC) {
	$_GET = istripslashes($_GET);
	$_POST = istripslashes($_POST);
	$_COOKIE = istripslashes($_COOKIE);
}
foreach ($_GET as $key => $value) {
	if (is_string($value) && !is_numeric($value)) {
		$value = safe_gpc_string($value);
	}
	$_GET[$key] = $_GPC[$key] = $value;
}
$cplen = strlen($_W['config']['cookie']['pre']);
foreach ($_COOKIE as $key => $value) {
	if ($_W['config']['cookie']['pre'] == substr($key, 0, $cplen)) {
		$_GPC[substr($key, $cplen)] = $value;
	}
}
unset($cplen, $key, $value);

$_GPC = array_merge($_GPC, $_POST);
$_GPC = ihtmlspecialchars($_GPC);

$_W['siteurl'] = $urls['scheme'] . '://' . $urls['host'] . ((!empty($urls['port']) && '80' != $urls['port']) ? ':' . $urls['port'] : '') . $_W['script_name'] . '?' . http_build_query($_GET, '', '&');

if (!$_W['isajax']) {
	$input = file_get_contents('php://input');
	if (!empty($input)) {
		$__input = @json_decode($input, true);
		if (!empty($__input)) {
			$_GPC['__input'] = $__input;
			$_W['isajax'] = true;
		}
	}
	unset($input, $__input);
}
$_W['uniacid'] = $_W['uid'] = 0;

if ($_W['config']['setting']['development'] == 1) {
	ini_set('display_errors', '1');
	error_reporting(E_ALL ^ E_NOTICE);
}
setting_load();
define('DEVELOPMENT', $_W['config']['setting']['development'] == 1 || $_W['setting']['copyright']['develop_status'] ==1);
if (empty($_W['setting']['upload'])) {
	$_W['setting']['upload'] = array_merge($_W['config']['upload']);
}

$_W['os'] = Agent::deviceType();
if (Agent::DEVICE_MOBILE == $_W['os']) {
	$_W['os'] = 'mobile';
} elseif (Agent::DEVICE_DESKTOP == $_W['os']) {
	$_W['os'] = 'windows';
} else {
	$_W['os'] = 'unknown';
}

$_W['container'] = Agent::browserType();
if (Agent::MICRO_MESSAGE_YES == Agent::isMicroMessage()) {
	$_W['container'] = 'wechat';
} elseif (Agent::BROWSER_TYPE_ANDROID == $_W['container']) {
	$_W['container'] = 'android';
} elseif (Agent::BROWSER_TYPE_IPAD == $_W['container']) {
	$_W['container'] = 'ipad';
} elseif (Agent::BROWSER_TYPE_IPHONE == $_W['container']) {
	$_W['container'] = 'iphone';
} elseif (Agent::BROWSER_TYPE_IPOD == $_W['container']) {
	$_W['container'] = 'ipod';
}else {
	$_W['container'] = 'unknown';
}

if ('wechat' == $_W['container'] || 'baidu' == $_W['container']) {
	$_W['platform'] = 'account';
}

$controller = !empty($_GPC['c']) ? $_GPC['c'] : '';
$action = !empty($_GPC['a']) ? $_GPC['a'] : '';
$do = !empty($_GPC['do']) ? $_GPC['do'] : '';
header('Content-Type: text/html; charset=' . $_W['charset']);