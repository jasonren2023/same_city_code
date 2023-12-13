<?php
defined('IN_IA') or exit('Access Denied');

function url($segment, $params = array(), $noredirect = false)
{
    return murl($segment, $params, $noredirect);
}

function message($msg, $redirect = '', $type = '')
{
    global $_W;
    if ($redirect == 'refresh') {
        $redirect = $_W['script_name'] . '?' . $_SERVER['QUERY_STRING'];
    } elseif (!empty($redirect) && !strexists($redirect, 'http://') && !strexists($redirect, 'https://')) {
        $urls = parse_url($redirect);
        $redirect = $_W['siteroot'] . 'app/index.php?' . $urls['query'];
    } else {
        $redirect = safe_gpc_url($redirect);
    }
    if ($redirect == '') {
        $type = in_array($type, array('success', 'error', 'info', 'warning', 'ajax', 'sql')) ? $type : 'info';
    } else {
        $type = in_array($type, array('success', 'error', 'info', 'warning', 'ajax', 'sql')) ? $type : 'success';
    }
    if ($_W['isajax'] || $type == 'ajax') {
        $vars = array();
        $vars['message'] = $msg;
        $vars['redirect'] = $redirect;
        $vars['type'] = $type;
        exit(json_encode($vars));
    }
    if (empty($msg) && !empty($redirect)) {
        header('location: ' . $redirect);
    }
    $label = $type;
    if ($type == 'error') {
        $label = 'danger';
    }
    if ($type == 'ajax' || $type == 'sql') {
        $label = 'warning';
    }
    if (defined('IN_API')) {
        exit($msg);
    }
    include template('common/message', TEMPLATE_INCLUDEPATH);
    exit();
}