<?php
defined('IN_IA') or exit('Access Denied');
define('IN_GW', true);

load()->model('utility');

if (!empty($_W['uid']) && 'bind' != $_GPC['handle_type']) {
    if ($_W['isajax']) {
        iajax(-1, '请先退出再登录！');
    }
    itoast('', $_W['siteroot'] . 'web/home.php');
}
if (checksubmit() || $_W['isajax']) {
    _login($_GPC['referer']);
}

$setting = $_W['setting'];
$_GPC['login_type'] = !empty($_GPC['login_type']) ? $_GPC['login_type'] : (!empty($_W['setting']['copyright']['mobile_status']) ? 'mobile' : 'system');

//$login_urls = user_support_urls();

template('user/login-half');

function _login($forward = '')
{
    global $_GPC, $_W;
    if (empty($_GPC['login_type'])) {
        $_GPC['login_type'] = 'system';
    }

    if (empty($_GPC['handle_type'])) {
        $_GPC['handle_type'] = 'login';
    }
    $member = OAuth2Client::create($_GPC['login_type'], $_W['setting']['thirdlogin'][$_GPC['login_type']]['appid'], $_W['setting']['thirdlogin'][$_GPC['login_type']]['appsecret'])->login();
    if (is_error($member)) {
        if ($_W['isajax']) {
            iajax(-1, $member['message'], url('user/login'));
        }
        itoast($member['message'], url('user/login'), '');
    }

    $record = user_single($member);
    $failed = pdo_get('users_failed_login', array('username' => trim($_GPC['username'])));
    if (!empty($record)) {
        if (USER_STATUS_CHECK == $record['status'] || USER_STATUS_BAN == $record['status']) {
            if ($_W['isajax']) {
                iajax(-1, '您的账号正在审核或是已经被系统禁止，请联系网站管理员解决?', url('user/login'));
            }
            itoast('您的账号正在审核或是已经被系统禁止，请联系网站管理员解决?', url('user/login'), '');
        }
        $_W['uid'] = $record['uid'];
        $_W['isfounder'] = user_is_founder($record['uid']);
        $_W['isadmin'] = user_is_founder($_W['uid'], true);
        $_W['user'] = $record;

        if (!empty($_W['siteclose']) && empty($_W['isfounder'])) {
            if ($_W['isajax']) {
                iajax(-1, '站点已关闭，关闭原因:' . $_W['setting']['copyright']['reason']);
            }
            itoast('站点已关闭，关闭原因:' . $_W['setting']['copyright']['reason'], '', '');
        }

        $cookie = array();
        $cookie['uid'] = $record['uid'];
        $cookie['lastvisit'] = $record['lastvisit'];
        $cookie['lastip'] = $record['lastip'];
        $cookie['hash'] = !empty($record['hash']) ? $record['hash'] : md5($record['password'] . $record['salt']);
        $cookie['rember'] = safe_gpc_int($_GPC['rember']);
        $session = authcode(json_encode($cookie), 'encode');
        $autosignout = (int)$_W['setting']['copyright']['autosignout'] > 0 ? (int)$_W['setting']['copyright']['autosignout'] * 60 : 0;
        isetcookie('__session', $session, !empty($_GPC['rember']) ? 7 * 86400 : $autosignout, true);
        pdo_update('users', array('lastvisit' => TIMESTAMP, 'lastip' => $_W['clientip']), array('uid' => $record['uid']));

        if (empty($forward)) {
            $forward = user_login_forward($_GPC['forward']);
        }
        $forward = safe_gpc_url($forward);

        if ($record['uid'] != $_GPC['__uid']) {
            isetcookie('__uniacid', '', -7 * 86400);
            isetcookie('__uid', '', -7 * 86400);
        }
        if (!empty($failed)) {
            pdo_delete('users_failed_login', array('id' => $failed['id']));
        }
        if ($_W['isajax']) {
            iajax(0, "欢迎回来，{$record['username']}", $forward);
        }
        itoast("欢迎回来，{$record['username']}", $forward, 'success');
    } else {
        if (empty($failed)) {
            pdo_insert('users_failed_login', array('ip' => $_W['clientip'], 'username' => trim($_GPC['username']), 'count' => '1', 'lastupdate' => TIMESTAMP));
        } else {
            pdo_update('users_failed_login', array('count' => $failed['count'] + 1, 'lastupdate' => TIMESTAMP), array('id' => $failed['id']));
        }
        if ($_W['isajax']) {
            iajax(-1, '登录失败，请检查您输入的账号和密码');
        }
        itoast('登录失败，请检查您输入的账号和密码', '', '');
    }
}