<?php

defined('IN_IA') or exit('Access Denied');

load()->model('user');

$dos = array('edit_base');
$do = in_array($do, $dos) ? $do : 'edit_base';

$uid = intval($_GPC['uid']);
$user = user_single($uid);
if (empty($user)) {
    if ($_W['isajax']) {
        iajax(-1, '访问错误, 未找到该用户.');
    }
    itoast('访问错误, 未找到该用户.', url('user/display'), 'error');
}
if (USER_STATUS_NORMAL != $user['status']) {
    if ($_W['isajax']) {
        iajax(-1, '该用户未审核或者已被禁用，请先修改用户状态');
    }
    itoast('该用户未审核或者已被禁用，请先修改用户状态', url('user/display'), 'info');
}

$profile = pdo_get('users_profile', array('uid' => $uid));
if (!empty($profile)) {
    $profile['avatar'] = tomedia($profile['avatar']);
}
if ('edit_base' == $do) {
    $user['last_visit'] = date('Y-m-d H:i:s', $user['lastvisit']);
    $user['joindate'] = date('Y-m-d H:i:s', $user['joindate']);
    $user['endtype'] = 0 == $user['endtime'] ? 1 : 2;
    $user['end'] = user_end_time($uid);
    $user['end'] = 0 == $user['end'] ? '永久' : $user['end'];
    $profile = user_detail_formate($profile);

    if ($_W['isajax']) {
        iajax(0, array(
            'user'         => $user,
            'profile'      => $profile,
            'extra_fileds' => ''
        ));
    }
    template('user/edit-base');
}
