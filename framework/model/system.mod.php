<?php

defined('IN_IA') or exit('Access Denied');

function system_menu()
{
    global $w7_system_menu;
    require_once IA_ROOT . '/web/common/frames.inc.php';
    return $w7_system_menu;
}

function system_menu_permission_list($role = '')
{
    global $_W;
    $system_menu = cache_load(cache_system_key('system_frame', array('uniacid' => $_W['uniacid'])));
    if (empty($system_menu)) {
        cache_build_frame_menu();
        $system_menu = cache_load(cache_system_key('system_frame', array('uniacid' => $_W['uniacid'])));
    }
    if ($role == ACCOUNT_MANAGE_NAME_OPERATOR) {
        unset($system_menu['appmarket']);
        unset($system_menu['advertisement']);
        unset($system_menu['system']);
    }
    return $system_menu;
}

function system_check_statcode($statcode)
{
    $allowed_stats = array(
        'baidu' => array(
            'enabled' => true,
            'reg'     => '/(http[s]?\:)?\/\/hm\.baidu\.com\/hm\.js\?/'
        ),

        'qq' => array(
            'enabled' => true,
            'reg'     => '/(http[s]?\:)?\/\/tajs\.qq\.com/'
        ),
    );
    foreach ($allowed_stats as $key => $item) {
        $preg = preg_match($item['reg'], $statcode);
        if (!$preg && !$item['enabled']) {
            continue;
        } else {
            return htmlspecialchars_decode($statcode);
        }
        return safe_gpc_html(htmlspecialchars_decode($statcode));
    }
}

function system_check_items()
{
    return array(
        'mbstring'                      => array(
            'operate'       => 'system_check_php_ext',
            'description'   => 'mbstring 扩展',
            'error_message' => '不支持库',
            'solution'      => '安装 mbstring 扩展',
            'handle'        => 'http://s.w7.cc/wo/problem/46'
        ),
        'mcrypt'                        => array(
            'operate'       => 'system_check_php_ext',
            'description'   => 'mcrypt 扩展',
            'error_message' => '不支持库',
            'solution'      => '安装 mcrypt 扩展',
            'handle'        => 'http://s.w7.cc/wo/problem/46'
        ),
        'openssl'                       => array(
            'operate'       => 'system_check_php_ext',
            'description'   => 'openssl 扩展',
            'error_message' => '不支持库',
            'solution'      => '安装 openssl 扩展',
            'handle'        => 'http://s.w7.cc/wo/problem/46'
        ),
        'max_allowed_packet'            => array(
            'operate'       => 'system_check_mysql_params',
            'description'   => 'mysql max_allowed_packet 值',
            'error_message' => 'max_allowed_packet 小于 20M',
            'solution'      => '修改 mysql max_allowed_packet 值',
            'handle'        => 'https://bbs.w7.cc/thread-33415-1-1.html'
        ),
        'always_populate_raw_post_data' => array(
            'operate'       => 'system_check_php_raw_post_data',
            'description'   => 'php always_populate_raw_post_data 配置',
            'error_message' => '配置有误',
            'solution'      => '修改 php always_populate_raw_post_data 配置为 -1',
            'handle'        => 'https://s.w7.cc/wo/problem/134'
        ),
    );
}

function system_check_php_ext($extension)
{
    return extension_loaded($extension) ? true : false;
}

function system_check_mysql_params($param)
{
    $check_result = pdo_fetchall("SHOW GLOBAL VARIABLES LIKE '{$param}'");
    return $check_result[0]['Value'] < 1024 * 1024 * 20 ? false : true;
}

function system_check_php_raw_post_data()
{
    if (version_compare(PHP_VERSION, '7.0.0') == -1 && version_compare(PHP_VERSION, '5.6.0') >= 0) {
        return @ini_get('always_populate_raw_post_data') == '-1';
    }
    return true;
}

function system_setting_items()
{
    return array(
        'bind',
        'develop_status',
        'icp',
        'policeicp',
        'login_type',
        'log_status',
        'mobile_status',
        'reason',
        'autosignout',
        'status',
        'welcome_link',
        'login_verify_status',
        'address',
        'blogo',
        'baidumap',
        'background_img',
        'company',
        'companyprofile',
        'description',
        'email',
        'footerleft',
        'footerright',
        'flogo',
        'icon',
        'keywords',
        'leftmenufixed',
        'notice',
        'oauth_bind',
        'phone',
        'person',
        'qq',
        'statcode',
        'slides',
        'showhomepage',
        'sitename',
        'template',
        'login_template',
        'url',
        'verifycode',
        'slide_logo',
    );
}

function system_star_menu()
{
    global $_W;
    $result = array(
        'platform' => array(
            'title'     => '所有平台',
            'icon'      => 'wi wi-platform',
            'apiurl'    => url('account/display/list', array('type' => ACCOUNT_TYPE_SIGN)),
            'one_page'  => 0,
            'hide_sort' => 0,
        ),
    );
    $account_all = table('account')->searchAccountList();
    $result['platform']['num'] = max(0, count($account_all));
    if ($result['platform']['num'] == 0) {
        unset($result['platform']['num']);
    }

    return $result;
}