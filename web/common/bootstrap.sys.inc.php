<?php
defined('IN_IA') or exit('Access Denied');

load()->web('common');
load()->web('template');
load()->func('file');
load()->func('tpl');
load()->model('cloud');
load()->model('user');
load()->model('permission');
load()->model('attachment');
load()->classs('oauth2/oauth2client');
load()->model('switch');
load()->model('system');

$_W['token'] = token();
$session = json_decode(authcode($_GPC['__session']), true);
if (is_array($session)) {
    $user = user_single(array('uid' => $session['uid']));
    if (is_array($user) && $session['hash'] === $user['hash']) {
        $_W['uid'] = $user['uid'];
        $_W['username'] = $user['username'];
        $user['currentvisit'] = $user['lastvisit'];
        $user['currentip'] = $user['lastip'];
        $user['lastvisit'] = $session['lastvisit'];
        $user['lastip'] = $session['lastip'];
        $_W['user'] = $user;
        $_W['isfounder'] = user_is_founder($_W['uid']);
        $_W['isadmin'] = user_is_founder($_W['uid'], true);
    } else {
        isetcookie('__session', false, -100);
    }
    unset($user);
}
unset($session);
$_W['uniacid'] = intval(igetcookie('__uniacid'));

if (!empty($_W['uid'])) {
    $_W['highest_role'] = permission_account_user_role($_W['uid']);
    $_W['role'] = permission_account_user_role($_W['uid'], $_W['uniacid']);
}

if($_GPC['c'] == 'system'){
    $_W['uniacid'] = 0;
}

$_W['template'] = 'default';
$_W['attachurl'] = attachment_set_attach_url();
