<?php
defined("IN_IA") or exit("访问非法");

$_W['current_module']['name'] = $_W['current_module']['name'] ? : 'weliam_smartcity';
!defined("MODULE_NAME") && define("MODULE_NAME", $_W['current_module']['name']);
if(!is_file('../../../../wlversion.txt')){
    $version = file_put_contents('../../../../wlversion.txt',MODULE_NAME);
    define("MODULE_NAME",$version);
}
$_W['siteroot'] = str_replace(array('/addons/' . MODULE_NAME,'/addons/weliam_smartcity', '/core/common', '/addons/bm_payms'), '', $_W['siteroot']);
!defined("PATH_MODULE") && define("PATH_MODULE", IA_ROOT . '/addons/' . MODULE_NAME . '/');
!defined("IS_DEV") && define("IS_DEV", file_exists(IA_ROOT . '/check.php') ? true : false);
!defined("FILES_UP_PATH") && define("FILES_UP_PATH", PATH_MODULE . 'upfiles.json');
!defined("VERSION_PATH") && define("VERSION_PATH", PATH_MODULE . 'version.php');
!defined("URL_MODULE") && define("URL_MODULE", $_W['siteroot'] . 'addons/' . MODULE_NAME . '/');
!defined('WL_URL_AUTH') && define('WL_URL_AUTH', 'http://citydev.weliam.com.cn/api/api.php');
!defined('WELIAM_API') && define('WELIAM_API', 'https://auth.weliam.com.cn/api.php');
!defined('PATH_ATTACHMENT') && define('PATH_ATTACHMENT', IA_ROOT . '/attachment/');
!defined('PAY_PATH') && define('PAY_PATH', $_W['siteroot'] . 'addons/' . MODULE_NAME . '/payment/');

/*
 * 表前缀定义
 */
!defined("PDO_NAME") && define("PDO_NAME", "wlmerchant_");

!defined("PATH_APP") && define("PATH_APP", PATH_MODULE . 'app/');
!defined("PATH_WEB") && define("PATH_WEB", PATH_MODULE . 'web/');
!defined("PATH_SYS") && define("PATH_SYS", PATH_MODULE . 'sys/');
!defined("PATH_CORE") && define("PATH_CORE", PATH_MODULE . 'core/');
!defined("PATH_DATA") && define("PATH_DATA", PATH_MODULE . 'data/');
!defined("PATH_PAYMENT") && define("PATH_PAYMENT", PATH_MODULE . 'payment/');
!defined("PATH_PLUGIN") && define("PATH_PLUGIN", PATH_MODULE . 'plugin/');
!defined("PATH_VENDOR") && define("PATH_VENDOR", PATH_MODULE . 'vendor/');
/*
 * app  resource路径
 */
!defined("URL_APP_RESOURCE") && define("URL_APP_RESOURCE", URL_MODULE . 'app/resource/');
!defined("URL_APP_CSS") && define("URL_APP_CSS", URL_APP_RESOURCE . 'css/');
!defined("URL_APP_JS") && define("URL_APP_JS", URL_APP_RESOURCE . 'js/');
!defined("URL_APP_IMAGE") && define("URL_APP_IMAGE", URL_APP_RESOURCE . 'image/');
!defined("URL_APP_COMP") && define("URL_APP_COMP", URL_APP_RESOURCE . 'components/');
/*
 * web  resource路径
 */
!defined("URL_WEB_RESOURCE") && define("URL_WEB_RESOURCE", URL_MODULE . 'web/resource/');
!defined("URL_WEB_CSS") && define("URL_WEB_CSS", URL_WEB_RESOURCE . 'css/');
!defined("URL_WEB_JS") && define("URL_WEB_JS", URL_WEB_RESOURCE . 'js/');
!defined("URL_WEB_IMAGE") && define("URL_WEB_IMAGE", URL_WEB_RESOURCE . 'image/');
!defined("URL_WEB_COMP") && define("URL_WEB_COPM", URL_WEB_RESOURCE . 'components/');
!defined("URL_WEB_COMP") && define("URL_WEB_DIY", URL_WEB_RESOURCE . 'diy/');

!defined('IMAGE_PIXEL') && define('IMAGE_PIXEL', URL_MODULE . 'web/resource/images/pixel.gif');
!defined('IMAGE_NOPIC_SMALL') && define('IMAGE_NOPIC_SMALL', URL_MODULE . 'web/resource/images/nopic-small.jpg');
!defined('IMAGE_LOADING') && define('IMAGE_LOADING', URL_MODULE . 'web/resource/images/loading.gif');

!defined("URL_H5_RESOURCE") && define("URL_H5_RESOURCE", URL_MODULE . 'h5/resource/');