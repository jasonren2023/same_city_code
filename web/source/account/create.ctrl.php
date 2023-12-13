<?php

defined('IN_IA') or exit('Access Denied');
load()->model('user');

$dos = array('display', 'save_account', 'get_user_info');
$do = in_array($do, $dos) ? $do : 'display';
$sign = safe_gpc_string($_GPC['sign']);
if (empty($account_all_type_sign[$sign])) {
    $error_msg = '所需创建的账号类型不存在, 请重试.';
    empty($_W['isajax']) ? message($error_msg, '', 'error') : iajax(-1, $error_msg);
}

if ('get_user_info' == $do) {
    if (!$_W['isfounder']) {
        iajax(-1, '非法请求数据！');
    }

    $uid = intval($_GPC['uid'][0]);
    $user = user_single(array('uid' => $uid));
    if (empty($user)) {
        iajax(-1, '用户不存在或是已经被删除', '');
    }
    $info = array(
        'uid'      => $user['uid'],
        'username' => $user['username'],
        'group'    => [],
        'endtime'  => user_end_time($user['uid']),
        'modules'  => array(),
    );
    $info['package'] = empty($info['group']['package']) ? array() : iunserializer($info['group']['package']);
    iajax(0, $info);
}
$sign_title = $account_all_type_sign[$sign]['title'];
$create_account_type = $account_all_type_sign[$sign]['contain_type'][0];
$_W['breadcrumb'] = '新建平台账号';

if ('save_account' == $do) {
    $post = array();
    $post['step'] = safe_gpc_string(trim($_GPC['step']));
    $post['name'] = safe_gpc_string(trim($_GPC['name']));
    $post['description'] = safe_gpc_string($_GPC['description']);
    $post['owner_uid'] = intval($_GPC['owner_uid']);
    $post['version'] = safe_gpc_string(trim($_GPC['version']));
    //判断商户名称
    if (empty($post['step']) || 'base_info' == $post['step']) {
        if (empty($post['name'])) iajax(-1, $sign_title . '名称不能为空');
        //判断平台名称是否重复
        $isHave = pdo_get('uni_account', ['name'=>$post['name']]);
        //if ($isHave) iajax(-1, "该名称'{$post['name']}'已经存在");
    }
    //保存平台信息 并且进行相关操作
    if (in_array($sign, array(ACCOUNT_TYPE_SIGN, WEBAPP_TYPE_SIGN, PHONEAPP_TYPE_SIGN))) {
        //添加信息
        $account_insert = [
            'groupid'       => 0 ,
            'default_acid'  => 0 ,
            'name'          => $post['name'] ,
            'description'   => $post['description'] ,
            'title_initial' => get_first_pinyin($post['name']) ,
            'createtime'    => TIMESTAMP ,
            'create_uid'    => intval($_W['uid']) ,
        ];
        if (!empty($_GPC['headimg'])) {
            $headimg = safe_gpc_path($_GPC['headimg']);
            if (file_is_image($headimg)) {
                $account_insert['logo'] = $headimg;
            }
        }
        pdo_insert('uni_account' , $account_insert);
        $uniacid = pdo_insertid();
        if (empty($uniacid)) iajax(-1 , "添加{$sign_title}失败, 请重试");
        $account_data = ['name' => $post['name'] , 'type' => $create_account_type];
        if (ACCOUNT_TYPE_SIGN == $sign) {
            $account_data['account']  = safe_gpc_string(trim($_GPC['account']));
            $account_data['original'] = safe_gpc_string(trim($_GPC['original']));
            $account_data['level']    = 4;
            $account_data['key']      = safe_gpc_string(trim($_GPC['key']));
            $account_data['secret']   = safe_gpc_string(trim($_GPC['secret']));
        }
        $acid = account_create($uniacid , $account_data);
        if (empty($acid)) iajax(-1 , "添加{$sign_title}信息失败");
        pdo_update('uni_account' , ['default_acid' => $acid] , ['uniacid' => $uniacid]);
        if (empty($_W['isfounder'])) uni_account_user_role_insert($uniacid , $_W['uid'] , ACCOUNT_MANAGE_NAME_OWNER);
        if (!pdo_get(PDO_NAME."uni_modules",['uniacid'=>$uniacid])){
            pdo_insert(PDO_NAME."uni_modules",['uniacid'=>$uniacid,'module_name'=>MODULE_NAME]);
        }
        //cache_build_account_modules($uniacid);
        if (in_array($sign , [ACCOUNT_TYPE_SIGN])) {
            pdo_insert('mc_groups' , ['uniacid' => $uniacid , 'title' => '默认会员组' , 'isdefault' => 1]);
//            $fields = pdo_getall('profile_fields');
//            if (is_array($fields)) {
//                foreach ($fields as $field) {
//                    pdo_insert('mc_member_fields' , [
//                        'uniacid'      => $uniacid ,
//                        'fieldid'      => $field['id'] ,
//                        'title'        => $field['title'] ,
//                        'available'    => $field['available'] ,
//                        'displayorder' => $field['displayorder'] ,
//                    ]);
//                }
//            }
        }
        if (ACCOUNT_TYPE_SIGN == $sign) {
            $oauth = uni_setting($uniacid , ['oauth']);
            if ($acid && empty($oauth['oauth']['account']) && !empty($account_data['key']) && !empty($account_data['secret']) && ACCOUNT_SERVICE_VERIFY == $account_data['level']) {
                pdo_update('uni_settings' , [
                                              'oauth' => iserializer([
                                                                         'account' => $acid ,
                                                                         'host'    => $oauth['oauth']['host']
                                                                     ])
                                          ] , ['uniacid' => $uniacid]);
            }
        }
        pdo_insert('uni_settings' , [
            'creditnames'     => iserializer([
                                                 'credit1' => ['title' => '积分' , 'enabled' => 1] ,
                                                 'credit2' => ['title' => '余额' , 'enabled' => 1]
                                             ]) ,
            'creditbehaviors' => iserializer(['activity' => 'credit1' , 'currency' => 'credit2']) ,
            'uniacid'         => $uniacid ,
            'default_site'    => 0 ,
            'sync'            => iserializer(['switch' => 0 , 'acid' => '']) ,
        ]);
    }
    if ($_W['isfounder']) {
        if (!empty($post['owner_uid'])) {
            $owner = pdo_get('uni_account_users', array('uniacid' => $uniacid, 'role' => 'owner'));
            if (!empty($owner)) {
                pdo_update('uni_account_users', array('uid' => $post['owner_uid']), array('uniacid' => $uniacid, 'role' => 'owner'));
            } else {
                uni_account_user_role_insert($uniacid, $post['owner_uid'], ACCOUNT_MANAGE_NAME_OWNER);
            }
        }

        if (!empty($_GPC['endtime'])) {
            $account_end_time = strtotime($_GPC['endtime']);
            if (!empty($post['owner_uid'])) {
                $user_end_time = strtotime(user_end_time($post['owner_uid']));
                if ($user_end_time > 0 && $account_end_time > $user_end_time) {
                    $account_end_time = $user_end_time;
                }
            }
            pdo_update('account', array('endtime' => $account_end_time), array('uniacid' => $uniacid));
        }
        cache_delete(cache_system_key('uniaccount', array('uniacid' => $uniacid)));
        cache_delete(cache_system_key('unimodules', array('uniacid' => $uniacid)));
        cache_delete(cache_system_key('proxy_wechatpay_account'));
        $cash_index = 'account' == $sign ? 'app' : $sign;
        cache_delete(cache_system_key('user_accounts', array('type' => $cash_index, 'uid' => $_W['uid'])));
        if (!empty($post['owner_uid'])) {
            cache_delete(cache_system_key('user_accounts', array('type' => $cash_index, 'uid' => $post['owner_uid'])));
            //cache_build_account_modules($uniacid, $post['owner_uid']);
            if (!pdo_get(PDO_NAME."uni_modules",['uniacid'=>$uniacid])){
                pdo_insert(PDO_NAME."uni_modules",['uniacid'=>$uniacid,'module_name'=>MODULE_NAME]);
            }
        }
    }
    $next_url = url('account/manage');
    if (!empty($next_url)) {
        $result = array('next_url' => $next_url, 'uniacid' => $uniacid);
        iajax(0, $result, $next_url);
    }
}

template('account/create');