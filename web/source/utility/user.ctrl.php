<?php

defined('IN_IA') or exit('Access Denied');
load()->model('user');
$dos = array('browser', 'detail_info');
$do = in_array($do, $dos) ? $do : 'browser';

if ('browser' == $do) {
    $mode = in_array($_GPC['mode'], array('invisible', 'visible')) ? $_GPC['mode'] : 'visible';

    $callback = $_GPC['callback'];

    $uids = $_GPC['uids'];
    $uidArr = array();
    if (empty($uids)) {
        $uids = '';
    } else {
        foreach (explode(',', $uids) as $uid) {
            $uidArr[] = intval($uid);
        }
        $uids = implode(',', $uidArr);
    }
    $where = " WHERE status = '2' and type != '" . ACCOUNT_OPERATE_CLERK . "' AND founder_groupid != " . ACCOUNT_MANAGE_GROUP_VICE_FOUNDER;
    if ($mode == 'invisible' && !empty($uids)) {
        $where .= " AND uid not in ( {$uids} )";
    }
    $params = array();
    if (!empty($_GPC['keyword'])) {
        $where .= ' AND `username` LIKE :username';
        $params[':username'] = '%' . safe_gpc_string($_GPC['keyword']) . '%';
    }
    $page = max(1, intval($_GPC['page']));
    $page_size = 10;
    $total = 0;

    $list = pdo_fetchall("SELECT uid, groupid, username, remark FROM " . tablename('users') . " {$where} ORDER BY `uid` LIMIT " . (($page - 1) * $page_size) . ",{$page_size}", $params);
    $total = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename('users') . $where, $params);

    $pager = pagination($total, $page, $page_size, '', array('ajaxcallback' => 'null', 'mode' => $mode, 'uids' => $uids));

    if ($_W['isw7_request'] && $_W['isajax']) {
        iajax(0, array(
            'total'     => $total,
            'page'      => $page,
            'page_size' => $page_size,
            'list'      => $list,
        ));
    }
    template('utility/user-browser');
    exit;
}

if ('detail_info' == $do) {
    if (!$_W['isfounder']) {
        iajax(-1, '非法请求数据！');
    }
    $sign = $_GPC['sign'];
    $uid = intval($_GPC['uid']);
    if (empty($uid)) {
        $uid = intval($_GPC['uid'][0]);
    }
    $user = user_single(array('uid' => $uid));

    if (empty($user)) {
        iajax(-1, '用户不存在或是已经被删除', '');
    }
    $user['group'] = [];
    $user['endtime'] = user_end_time($user['uid']);
    $user['modules'] = array();
    $user['package'] = empty($user['group']['package']) ? array() : iunserializer($user['group']['package']);
    iajax(0, $user);
}