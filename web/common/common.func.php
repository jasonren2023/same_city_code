<?php

defined('IN_IA') or exit('Access Denied');

load()->model('module');
load()->model('miniapp');

function current_operate_is_controller()
{
    global $_W, $_GPC;
    $result = 0;
    if (!$_W['isfounder']) {
        return $result;
    }
    $result = igetcookie('__iscontroller');
    if (isset($_GPC['iscontroller'])) {
        if (1 == $_GPC['iscontroller']) {
            $result = 1;
            isetcookie('__iscontroller', $result);
            return $result;
        }
        if (0 == $_GPC['iscontroller']) {
            $result = 0;
        }
    }

    if (in_array(FRAME, array('welcome', 'module_manage', 'user_manage', 'permission', 'system', 'site'))) {
        $result = 1;
    }
    if (in_array(FRAME, array('account', 'wxapp')) && (($_GPC['m'] || $_GPC['module_name']) != 'store')) {
        $result = 0;
    }
    isetcookie('__iscontroller', $result);
    return $result;
}

function system_modules()
{
    return module_system();
}


function url($segment, $params = array(), $contain_domain = false)
{
    return wurl($segment, $params, $contain_domain);
}


function message($msg, $redirect = '', $type = '', $tips = false, $extend = array())
{
    global $_W, $_GPC;

    if ('refresh' == $redirect) {
        $redirect = $_W['script_name'] . '?' . $_SERVER['QUERY_STRING'];
    }
    if ('referer' == $redirect) {
        $redirect = referer();
    }
    $redirect = safe_gpc_url($redirect);

    if ('' == $redirect) {
        $type = in_array($type, array('success', 'error', 'info', 'warning', 'ajax', 'sql', 'expired')) ? $type : 'info';
    } else {
        $type = in_array($type, array('success', 'error', 'info', 'warning', 'ajax', 'sql', 'expired')) ? $type : 'success';
    }
    if ($_W['isajax'] || !empty($_GET['isajax']) || 'ajax' == $type) {
        if ('ajax' != $type && !empty($_GPC['target'])) {
            exit('
<script type="text/javascript">
	var url = ' . (!empty($redirect) ? 'parent.location.href' : "''") . ";
	var modalobj = util.message('" . $msg . "', '', '" . $type . "');
	if (url) {
		modalobj.on('hide.bs.modal', function(){\$('.modal').each(function(){if(\$(this).attr('id') != 'modal-message') {\$(this).modal('hide');}});top.location.reload()});
	}
</script>");
        } else {
            $vars = array();
            $vars['message'] = $msg;
            $vars['redirect'] = $redirect;
            $vars['type'] = $type;
            exit(json_encode($vars));
        }
    }
    if (empty($msg) && !empty($redirect)) {
        header('Location: ' . $redirect);
        exit;
    }
    $label = $type;
    if ('error' == $type || 'expired' == $type) {
        $label = 'danger';
    }
    if ('ajax' == $type || 'sql' == $type) {
        $label = 'warning';
    }

    if ($tips) {
        if (is_array($msg)) {
            $message_cookie['title'] = 'MYSQL 错误';
            $message_cookie['msg'] = 'php echo cutstr(' . $msg['sql'] . ', 300, 1);';
        } else {
            $message_cookie['title'] = $caption;
            $message_cookie['msg'] = $msg;
        }
        $message_cookie['type'] = $label;
        $message_cookie['redirect'] = $redirect ? $redirect : referer();
        $message_cookie['msg'] = rawurlencode($message_cookie['msg']);
        $extend_button = array();
        if (!empty($extend) && is_array($extend)) {
            foreach ($extend as $button) {
                if (!empty($button['title']) && !empty($button['url'])) {
                    $button['url'] = safe_gpc_url($button['url'], false);
                    $button['title'] = rawurlencode($button['title']);
                    $extend_button[] = $button;
                }
            }
        }
        $message_cookie['extend'] = !empty($extend_button) ? $extend_button : '';

        isetcookie('message', stripslashes(json_encode($message_cookie, JSON_UNESCAPED_UNICODE)));
        header('Location: ' . $message_cookie['redirect']);
    } else {
        include template('common/message', TEMPLATE_INCLUDEPATH);
    }
    exit;
}

function iajax($code = 0, $message = '', $redirect = '')
{
    message(error($code, $message), $redirect, 'ajax', false);
}

function itoast($message, $redirect = '', $type = '', $extend = array())
{
    message($message, $redirect, $type, true, $extend);
}


function checklogin($url = '')
{
    // echo 33;die;
    global $_W;
    if (empty($_W['uid'])) {
        $url = safe_gpc_url($url);
        itoast('', url('user/login', $url ? array('referer' => urlencode($url)) : ''), 'warning');
    }
    $cookie = json_decode(authcode(igetcookie('__session'), 'DECODE'), true);
    if (empty($cookie['rember'])) {
        $session = authcode(json_encode($cookie), 'encode');
        $autosignout = (int)$_W['setting']['copyright']['autosignout'] > 0 ? (int)$_W['setting']['copyright']['autosignout'] * 60 : 0;
        isetcookie('__session', $session, $autosignout, true);
    }

    return true;
}

function get_position_by_ip($ip = '')
{
    global $_W;
    $ip = $ip ? $ip : $_W['clientip'];
    $url = 'http://ip.taobao.com/outGetIpInfo?ip=' . $ip . '&accessKey=alibaba-inc';
    $ip_content = file_get_contents($url);
    $ip_content = json_decode($ip_content, true);
    if (empty($ip_content) || $ip_content['code'] != 0) {
        $res = @file_get_contents('https://whois.pconline.com.cn/ipJson.jsp');
        $res = strtoutf8($res);
        $json_matches = array();
        preg_match('/{IPCallBack\((.+?)\);\}/', $res, $json_matches);
        if (empty($json_matches[1])) {
            return error(-1, '获取地址失败，请重新配置Ip查询接口');
        }
        $ip_content = array(
            'code' => 0,
            'data' => json_decode($json_matches[1], true)
        );
    }
    return $ip_content;
}

function buildframes($framename = '')
{
    global $_W, $_GPC;
    $frames = system_menu_permission_list();
    $frames = frames_top_menu($frames);

    return !empty($framename) ? ('system_welcome' == $framename ? $frames['account'] : $frames[$framename]) : $frames;
}

function frames_top_menu($frames)
{
    global $_W, $top_nav;
    if (empty($frames)) {
        return array();
    }

    foreach ($frames as $menuid => $menu) {
//        if ((!empty($menu['founder']) || in_array($menuid, array('module_manage', 'site', 'advertisement', 'appmarket'))) && !$_W['isadmin'] ||
//            ACCOUNT_MANAGE_NAME_CLERK == $_W['highest_role'] && in_array($menuid, array('account', 'wxapp', 'system', 'platform', 'welcome', 'account_manage')) && !$_W['isadmin'] && in_array($menuid, array('user_manage', 'permission')) ||
//            'myself' == $menuid && $_W['isadmin'] ||
//            !$menu['is_display']) {
//            continue;
//        }

        $top_nav[] = array(
            'title'      => $menu['title'],
            'name'       => $menuid,
            'url'        => $menu['url'],
            'blank'      => $menu['blank'],
            'icon'       => $menu['icon'],
            'is_display' => $menu['is_display'],
            'is_system'  => $menu['is_system'],
        );
    }
    return $frames;
}


function filter_url($params)
{
    global $_W;
    if (empty($params)) {
        return '';
    }
    $query_arr = array();
    $parse = parse_url($_W['siteurl']);
    if (!empty($parse['query'])) {
        $query = $parse['query'];
        parse_str($query, $query_arr);
    }
    $params = explode(',', $params);
    foreach ($params as $val) {
        if (!empty($val)) {
            $data = explode(':', $val);
            $query_arr[$data[0]] = trim($data[1]);
        }
    }
    $query_arr['page'] = 1;
    $query = http_build_query($query_arr);

    return './index.php?' . $query;
}

function url_params($url)
{
    $result = array();
    if (empty($url)) {
        return $result;
    }
    $components = parse_url($url);
    $params = explode('&', $components['query']);
    foreach ($params as $param) {
        if (!empty($param)) {
            $param_array = explode('=', $param);
            $result[$param_array[0]] = $param_array[1];
        }
    }

    return $result;
}

function frames_menu_append()
{
    $system_menu_default_permission = array(
        'founder'      => array(),
        'vice_founder' => array(
            'system_setting_updatecache',
        ),
        'owner'        => array(
            'system_setting_updatecache',
        ),
        'manager'      => array(
            'system_setting_updatecache',
        ),
        'operator'     => array(
            'system_setting_updatecache',
        ),
        'clerk'        => array(),
        'expired'      => array(
            'system_setting_updatecache',
        ),
    );

    return $system_menu_default_permission;
}


function site_profile_perfect_tips()
{
    global $_W;

    if ($_W['isfounder'] && (empty($_W['setting']['site']) || empty($_W['setting']['site']['profile_perfect']))) {
        if (!defined('SITE_PROFILE_PERFECT_TIPS')) {
            $url = url('cloud/profile');

            return <<<EOF
$(function() {
	var html =
		'<div class="we7-body-alert">'+
			'<div class="container">'+
				'<div class="alert alert-info">'+
					'<i class="wi wi-info-sign"></i>'+
					'<button type="button" class="close" data-dismiss="alert"><span aria-hidden="true" class="wi wi-error-sign"></span><span class="sr-only">Close</span></button>'+
					'<a href="{$url}" target="_blank">请尽快完善您在微擎云服务平台的站点注册信息。</a>'+
				'</div>'+
			'</div>'+
		'</div>';
	$('body').prepend(html);
});
EOF;
            define('SITE_PROFILE_PERFECT_TIPS', true);
        }
    }

    return '';
}

function strtoutf8($str)
{
    $current_encode = mb_detect_encoding($str, array('ASCII', 'GB2312', 'GBK', 'BIG5', 'UTF-8'));
    return mb_convert_encoding($str, 'UTF-8', $current_encode);
}