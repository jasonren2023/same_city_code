<?php

defined('IN_IA') or exit('Access Denied');
load()->model('user');

$dos = array('post', 'save', 'check_user_info');
$do = in_array($do, $dos) ? $do : 'post';

if ('post' == $do) {
    $user_type = 'user';
    template('user/post');
}

if ('save' == $do) {
    $user_info = array(
        'username'   => safe_gpc_string($_GPC['username']),
        'password'   => $_GPC['password'],
        'repassword' => $_GPC['repassword'],
        'remark'     => safe_gpc_string($_GPC['remark']),
        'starttime'  => TIMESTAMP,
    );

    $user_add = user_info_save($user_info);
    if (is_error($user_add)) {
        iajax(-1, $user_add['message'], url('user/display'));
    }

    iajax(0, '添加成功', url('user/display'));
}

if ('check_user_info' == $do) {
    $user = $_GPC['user'];
    $user['username'] = safe_gpc_string($user['username']);
    $check_result = user_info_check($user);
    iajax($check_result['errno'], $check_result['message'], url('user/create'));
}