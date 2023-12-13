<?php

defined('IN_IA') or exit('Access Denied');


function module_system()
{
    return array(
        'basic', 'news', 'music', 'service', 'userapi', 'recharge', 'images', 'video', 'voice', 'wxcard',
        'custom', 'chats', 'paycenter', 'keyword', 'special', 'welcome', 'default', 'apply', 'reply', 'core', 'store',
    );
}


function module_types()
{
    static $types = array(
        'business'   => array(
            'name'  => 'business',
            'title' => '主要业务',
            'desc'  => ''
        ),
        'customer'   => array(
            'name'  => 'customer',
            'title' => '客户关系',
            'desc'  => ''
        ),
        'activity'   => array(
            'name'  => 'activity',
            'title' => '营销及活动',
            'desc'  => ''
        ),
        'services'   => array(
            'name'  => 'services',
            'title' => '常用服务及工具',
            'desc'  => ''
        ),
        'biz'        => array(
            'name'  => 'biz',
            'title' => '行业解决方案',
            'desc'  => ''
        ),
        'enterprise' => array(
            'name'  => 'enterprise',
            'title' => '企业应用',
            'desc'  => ''
        ),
        'h5game'     => array(
            'name'  => 'h5game',
            'title' => 'H5游戏',
            'desc'  => ''
        ),
        'other'      => array(
            'name'  => 'other',
            'title' => '其他',
            'desc'  => ''
        )
    );
    return $types;
}

function module_support_type()
{
    $module_support_type = array(
        'wxapp_support'      => array(
            'type'        => WXAPP_TYPE_SIGN,
            'type_name'   => '微信小程序',
            'support'     => MODULE_SUPPORT_WXAPP,
            'not_support' => MODULE_NONSUPPORT_WXAPP,
            'store_type'  => STORE_TYPE_WXAPP_MODULE,
        ),
        'account_support'    => array(
            'type'        => ACCOUNT_TYPE_SIGN,
            'type_name'   => '公众号',
            'support'     => MODULE_SUPPORT_ACCOUNT,
            'not_support' => MODULE_NONSUPPORT_ACCOUNT,
            'store_type'  => STORE_TYPE_MODULE,
        ),
        'welcome_support'    => array(
            'type'        => WELCOMESYSTEM_TYPE_SIGN,
            'type_name'   => '系统首页',
            'support'     => MODULE_SUPPORT_SYSTEMWELCOME,
            'not_support' => MODULE_NONSUPPORT_SYSTEMWELCOME,
        ),
        'webapp_support'     => array(
            'type'        => WEBAPP_TYPE_SIGN,
            'type_name'   => 'PC',
            'support'     => MODULE_SUPPORT_WEBAPP,
            'not_support' => MODULE_NOSUPPORT_WEBAPP,
            'store_type'  => STORE_TYPE_WEBAPP_MODULE,
        ),
        'phoneapp_support'   => array(
            'type'        => PHONEAPP_TYPE_SIGN,
            'type_name'   => 'APP',
            'support'     => MODULE_SUPPORT_PHONEAPP,
            'not_support' => MODULE_NOSUPPORT_PHONEAPP,
            'store_type'  => STORE_TYPE_PHONEAPP_MODULE,
        ),
        'aliapp_support'     => array(
            'type'        => ALIAPP_TYPE_SIGN,
            'type_name'   => '支付宝小程序',
            'support'     => MODULE_SUPPORT_ALIAPP,
            'not_support' => MODULE_NOSUPPORT_ALIAPP,
            'store_type'  => STORE_TYPE_ALIAPP_MODULE,
        ),
        'baiduapp_support'   => array(
            'type'        => BAIDUAPP_TYPE_SIGN,
            'type_name'   => '百度小程序',
            'support'     => MODULE_SUPPORT_BAIDUAPP,
            'not_support' => MODULE_NOSUPPORT_BAIDUAPP,
            'store_type'  => STORE_TYPE_BAIDUAPP_MODULE,
        ),
        'toutiaoapp_support' => array(
            'type'        => TOUTIAOAPP_TYPE_SIGN,
            'type_name'   => '字节跳动小程序',
            'support'     => MODULE_SUPPORT_TOUTIAOAPP,
            'not_support' => MODULE_NOSUPPORT_TOUTIAOAPP,
            'store_type'  => STORE_TYPE_TOUTIAOAPP_MODULE,
        )
    );
    return $module_support_type;
}


function module_entries($name, $types = array(), $rid = 0, $args = null)
{
    load()->func('communication');

    global $_W;

    $ts = array('rule', 'cover', 'menu', 'home', 'profile', 'shortcut', 'function', 'mine', 'system_welcome');


    if (empty($types)) {
        $types = $ts;
    } else {
        $types = array_intersect($types, $ts);
    }
    $bindings = pdo_getall('modules_bindings', array('module' => $name, 'entry' => $types), array(), '', 'displayorder DESC, multilevel DESC, eid ASC');
    $entries = array();
    $cache_key = cache_system_key('module_entry_call', array('module_name' => $name));
    $entry_call = cache_load($cache_key);
    if (empty($entry_call)) {
        $entry_call = array();
    }
    foreach ($bindings as $bind) {
        if (!empty($bind['call'])) {
            if (empty($entry_call[$bind['entry']])) {
                $call_url = url('utility/bindcall', array('modulename' => $bind['module'], 'callname' => $bind['call'], 'args' => $args, 'uniacid' => $_W['uniacid']));
                $response = ihttp_request($call_url);
                if (is_error($response) || $response['code'] != 200) {
                    $response = ihttp_request($_W['siteroot'] . 'web/' . $call_url);
                    if (is_error($response) || $response['code'] != 200) {
                        continue;
                    }
                }
                $response = json_decode($response['content'], true);
                $ret = $response['message']['message'];
                if (is_array($ret)) {
                    foreach ($ret as $i => $et) {
                        if (empty($et['url'])) {
                            continue;
                        }
                        $urlinfo = url_params($et['url']);
                        $et['do'] = empty($et['do']) ? $urlinfo['do'] : $et['do'];
                        $et['url'] = $et['url'] . '&__title=' . urlencode($et['title']);
                        $entry_call[$bind['entry']][] = array('eid' => 'user_' . $i, 'title' => $et['title'], 'do' => $et['do'], 'url' => $et['url'], 'from' => 'call', 'icon' => $et['icon'], 'displayorder' => $et['displayorder']);
                    }
                }
                cache_write($cache_key, $entry_call, 300);
            }
            $entries[$bind['entry']] = $entry_call[$bind['entry']];

        } else {
            if (in_array($bind['entry'], array('cover', 'home', 'profile', 'shortcut'))) {
                $url = murl('entry', array('eid' => $bind['eid']));
            }
            if (in_array($bind['entry'], array('menu', 'system_welcome'))) {
                $url = wurl("site/entry", array('eid' => $bind['eid']));
            }
            if ($bind['entry'] == 'mine') {
                $url = $bind['url'];
            }
            if ($bind['entry'] == 'rule') {
                $par = array('eid' => $bind['eid']);
                if (!empty($rid)) {
                    $par['id'] = $rid;
                }
                $url = wurl("site/entry", $par);
            }

            if (empty($bind['icon'])) {
                $bind['icon'] = 'wi wi-appsetting';
            }
            if (!defined('SYSTEM_WELCOME_MODULE') && $bind['entry'] == 'system_welcome') {
                continue;
            }
            $entries[$bind['entry']][] = array(
                'eid'          => $bind['eid'],
                'title'        => $bind['title'],
                'do'           => $bind['do'],
                'url'          => !$bind['multilevel'] ? $url : '',
                'from'         => 'define',
                'icon'         => $bind['icon'],
                'displayorder' => $bind['displayorder'],
                'direct'       => $bind['direct'],
                'multilevel'   => $bind['multilevel'],
                'parent'       => $bind['parent'],
            );
        }
    }
    return $entries;
}

function module_app_entries($name, $types = array(), $args = null)
{
    global $_W;
    $ts = array('rule', 'cover', 'menu', 'home', 'profile', 'shortcut', 'function');
    if (empty($types)) {
        $types = $ts;
    } else {
        $types = array_intersect($types, $ts);
    }
    $bindings = pdo_getall('modules_bindings', array('module' => $name, 'entry' => $types));
    $entries = array();
    foreach ($bindings as $bind) {
        if (!empty($bind['call'])) {
            $extra = array();
            $extra['Host'] = $_SERVER['HTTP_HOST'];
            load()->func('communication');
            $urlset = parse_url($_W['siteurl']);
            $urlset = pathinfo($urlset['path']);
            $response = ihttp_request($_W['sitescheme'] . $extra['Host'] . $urlset['dirname'] . '/' . url('utility/bindcall', array('modulename' => $bind['module'], 'callname' => $bind['call'], 'args' => $args, 'uniacid' => $_W['uniacid'])), array('W' => base64_encode(iserializer($_W))), $extra);
            if (is_error($response)) {
                continue;
            }
            $response = json_decode($response['content'], true);
            $ret = $response['message']['message'];
            if (is_array($ret)) {
                foreach ($ret as $et) {
                    $et['url'] = $et['url'] . '&__title=' . urlencode($et['title']);
                    $entries[$bind['entry']][] = array('title' => $et['title'], 'url' => $et['url'], 'from' => 'call');
                }
            }
        } else {
            if ($bind['entry'] == 'cover') {
                $url = murl("entry", array('eid' => $bind['eid']));
            }
            if ($bind['entry'] == 'home') {
                $url = murl("entry", array('eid' => $bind['eid']));
            }
            if ($bind['entry'] == 'profile') {
                $url = murl("entry", array('eid' => $bind['eid']));
            }
            if ($bind['entry'] == 'shortcut') {
                $url = murl("entry", array('eid' => $bind['eid']));
            }
            $entries[$bind['entry']][] = array('title' => $bind['title'], 'do' => $bind['do'], 'url' => $url, 'from' => 'define');
        }
    }
    return $entries;
}

function module_entry($eid)
{
    $sql = "SELECT * FROM " . tablename('modules_bindings') . " WHERE `eid`=:eid";
    $pars = array();
    $pars[':eid'] = $eid;
    $entry = pdo_fetch($sql, $pars);
    if (empty($entry)) {
        return error(1, '模块菜单不存在');
    }
    $module = module_fetch($entry['module']);
    if (empty($module)) {
        return error(2, '模块不存在');
    }
    $querystring = array(
        'do' => $entry['do'],
        'm'  => $entry['module'],
    );
    if (!empty($entry['state'])) {
        $querystring['state'] = $entry['state'];
    }

    $entry['url'] = murl('entry', $querystring);
    $entry['url_show'] = murl('entry', $querystring, true, true);
    return $entry;
}


function module_build_form($name, $rid, $option = array())
{
    $rid = intval($rid);
    $m = WeUtility::createModule($name);
    if (!empty($m)) {
        return $m->fieldsFormDisplay($rid, $option);
    } else {
        return null;
    }

}


function module_save_group_package($package)
{
    global $_W;
    load()->model('user');
    load()->model('cache');

    if (empty($package['name'])) {
        return error(-1, '请输入套餐名');
    }

    if (!empty($package['modules'])) {
        $package['modules'] = iserializer($package['modules']);
    }

    if (!empty($package['templates'])) {
        $templates = array();
        foreach ($package['templates'] as $id) {
            $templates[] = $id;
        }
        $package['templates'] = iserializer($templates);
    }

    if (!empty($package['id'])) {
        $name_exist = pdo_get('uni_group', array('uniacid' => 0, 'id <>' => $package['id'], 'name' => $package['name']));
    } else {
        $name_exist = pdo_get('uni_group', array('uniacid' => 0, 'name' => $package['name']));
    }

    if (!empty($name_exist)) {
        return error(-1, '套餐名已存在');
    }

    if (!empty($package['id'])) {
        pdo_update('uni_group', $package, array('id' => $package['id']));
        cache_build_account_modules();
    } else {
        pdo_insert('uni_group', $package);
        $uni_group_id = pdo_insertid();
        if (user_is_vice_founder()) {
            $table = table('users_founder_own_uni_groups');
            $table->addOwnUniGroup($_W['uid'], $uni_group_id);
        }
    }
    cache_build_uni_group();
    return error(0, '添加成功');
}

function module_fetch($name, $enabled = true)
{
    global $_W;
    $cachekey = cache_system_key('module_info', array('module_name' => $name));

    $module = cache_load($cachekey);

    if (empty($module)) {
        $module_info = table('modules')->getByName($name);
        if (empty($module_info)) {
            return array();
        }
        $module_info['isdisplay'] = 1;
        $module_info['logo'] = tomedia($module_info['logo']);
        $module_info['preview'] = tomedia(IA_ROOT . '/addons/' . $module_info['name'] . '/preview.jpg', '', true);
        if (file_exists(IA_ROOT . '/addons/' . $module_info['name'] . '/preview-custom.jpg')) {
            $module_info['preview'] = tomedia(IA_ROOT . '/addons/' . $module_info['name'] . '/preview-custom.jpg', '', true);
        }
        if (APPLICATION_TYPE_TEMPLATES == $module_info['application_type']) {
            $module_info['preview'] = tomedia(IA_ROOT . '/app/themes/' . $module_info['name'] . '/preview-custom.jpg', '', true);
        }
        $module = $module_info;
        cache_write($cachekey, $module_info);
    }

    if (!empty($enabled)) {
        if (!empty($module['is_delete'])) {
            return array();
        }
    }

    if (!empty($module) && !empty($_W['uniacid'])) {
        $setting_cachekey = cache_system_key('module_setting', array('module_name' => $name, 'uniacid' => $_W['uniacid']));
        $setting = cache_load($setting_cachekey);
        if (empty($setting)) {
            $setting = table('uni_account_modules')->getByUniacidAndModule($name, $_W['uniacid']);
            $setting = empty($setting) ? array('module' => $name) : $setting;
            cache_write($setting_cachekey, $setting);
        }
        $module['config'] = $setting['settings'];
        $module['enabled'] = $module['issystem'] || !isset($setting['enabled']) ? 1 : $setting['enabled'];
        $module['displayorder'] = $setting['displayorder'];
        $module['shortcut'] = $setting['shortcut'];
        $module['module_shortcut'] = $setting['module_shortcut'];
    }
    return $module;
}

function module_main_info($module_name)
{
    $cachekey = cache_system_key('module_main_info', array('module_name' => $module_name));
    $module = cache_load($cachekey);
    if (empty($module)) {
        $fileds = array('name', 'title', 'version', 'logo', 'account_support', 'wxapp_support', 'webapp_support', 'phoneapp_support', 'aliapp_support', 'baiduapp_support', 'toutiaoapp_support', 'welcome_support');
        $module_info = pdo_get('modules', array('name' => $module_name), $fileds);
        if (empty($module_info)) {
            return array();
        }
        $module_info['logo'] = tomedia($module_info['logo']);
        $module = $module_info;
        cache_write($cachekey, $module_info);
    }
    return $module;
}

function module_permission_fetch($name)
{
    $module = module_fetch($name);
    $data = array();
    if ($module['settings']) {
        $data[] = array('title' => '参数设置', 'permission' => $name . '_settings');
    }
    if ($module['isrulefields']) {
        $data[] = array('title' => '回复规则列表', 'permission' => $name . '_rule');
    }
    $entries = module_entries($name);
    if (!empty($entries['home'])) {
        $data[] = array('title' => '微站首页导航', 'permission' => $name . '_home');
    }
    if (!empty($entries['profile'])) {
        $data[] = array('title' => '个人中心导航', 'permission' => $name . '_profile');
    }
    if (!empty($entries['shortcut'])) {
        $data[] = array('title' => '快捷菜单', 'permission' => $name . '_shortcut');
    }
    if (!empty($entries['cover'])) {
        foreach ($entries['cover'] as $cover) {
            $data[] = array('title' => $cover['title'], 'permission' => $name . '_cover_' . $cover['do']);
        }
    }
    if (!empty($entries['menu'])) {
        foreach ($entries['menu'] as $menu) {
            if (!empty($menu['multilevel'])) {
                continue;
            }
            $data[$menu['do']] = array('title' => $menu['title'], 'permission' => $name . '_menu_' . $menu['do']);
        }
    }
    unset($entries);
    if (!empty($module['permissions'])) {
        $module['permissions'] = (array)iunserializer($module['permissions']);
        foreach ($module['permissions'] as $permission) {
            if (!empty($permission['parent']) && !empty($data[$permission['parent']])) {
                $sub_permission = array(
                    'title'      => $permission['title'],
                    'permission' => $name . '_menu_' . $permission['parent'] . '_' . $permission['permission'],
                );
                if (empty($data[$permission['parent']]['sub_permission'])) {
                    $data[$permission['parent']]['sub_permission'] = array($sub_permission);
                } else {
                    array_push($data[$permission['parent']]['sub_permission'], $sub_permission);
                }
            }
            $data[] = array('title' => $permission['title'], 'permission' => $name . '_permission_' . $permission['permission']);
        }
    }
    return $data;
}


function module_get_plugin_list($module_name)
{
    $module_info = module_fetch($module_name);
    if (!empty($module_info['plugin_list']) && is_array($module_info['plugin_list'])) {
        $plugin_list = array();
        foreach ($module_info['plugin_list'] as $plugin) {
            $plugin_info = module_fetch($plugin);
            if (!empty($plugin_info)) {
                $plugin_list[$plugin] = $plugin_info;
            }
        }
        return $plugin_list;
    } else {
        return array();
    }
}


function module_exist_in_account($module_name, $uniacid)
{
    load()->model('user');
    $module_name = trim($module_name);
    $uniacid = intval($uniacid);
    if (empty($module_name) || empty($uniacid)) {
        return false;
    }
    $result = table('uni_modules')->where(array('uniacid' => $uniacid, 'module_name' => $module_name))->getcolumn('id');
    return $result ? true : false;
}


function module_check_notinstalled_support($module, $manifest_support)
{
    if (empty($manifest_support)) {
        return array();
    }
    $has_notinstalled_support = false;
    $notinstalled_support = array();
    $module_support_type = module_support_type();

    foreach ($manifest_support as $support) {
        if ($support == 'app') {
            $support = 'account';
        } elseif ($support == 'system_welcome') {
            $support = 'welcome';
        } elseif ($support == 'android' || $support == 'ios') {
            $support = 'phoneapp';
        }
        $support .= '_support';
        if (!in_array($support, array_keys($module_support_type))) {
            continue;
        }

        if ($module[$support] != $module_support_type[$support]['support']) {
            $has_notinstalled_support = true;
            $notinstalled_support[$support] = $module_support_type[$support]['support'];
        } else {
            $notinstalled_support[$support] = $module_support_type[$support]['not_support'];
        }
    }
    if ($has_notinstalled_support) {
        return $notinstalled_support;
    } else {
        return array();
    }
}


function module_add_to_uni_group($module, $uni_group_id, $support)
{
    if (!in_array($support, array_keys(module_support_type()))) {
        return error(1, '支持类型不存在');
    }
    if (empty($module[$support]) || $module[$support] != MODULE_SUPPORT_ACCOUNT) {
        return error(1, '模块支持不存在');
    }
    $unigroup_table = table('uni_group');
    $uni_group = $unigroup_table->getById($uni_group_id);
    if (empty($uni_group)) {
        return error(1, '应用权限组不存在');
    }
    if (!empty($uni_group['modules'])) {
        $uni_group['modules'] = iunserializer($uni_group['modules']);
    }
    $update_data = $uni_group['modules'];

    $key = str_replace('_support', '', $support);
    $key = $key == 'account' ? 'modules' : $key;
    if (!in_array($module['name'], $update_data[$key])) {
        $update_data[$key][] = $module['name'];
    }
    return $unigroup_table->fill('modules', iserializer($update_data))->where('id', $uni_group_id)->save();
}


function module_recycle($modulename, $type, $support)
{
    global $_W;
    $module_support_types = module_support_type();
    $module_support_type = $module_support_types[$support]['type'];
    $all_support = array_keys($module_support_types);

    if ($type == MODULE_RECYCLE_INSTALL_DISABLED) {
        table('system_welcome_binddomain')->where(array('module_name' => $modulename))->delete();
        $uni_modules_table = table('uni_modules');
        $uni_accounts = $uni_modules_table->where('module_name', $modulename)->getall('uniacid');
        if (!empty($uni_accounts)) {
            foreach ($uni_accounts as $uni_account_val) {
                $account_info = uni_fetch($uni_account_val['uniacid']);
                if ($account_info['type_sign'] == $module_support_type) {
                    $uni_modules_table->deleteUniModules($modulename, $uni_account_val['uniacid']);
                }
            }
        }

        $lastuse_table = table('users_lastuse');
        $lastuse_accounts = switch_getall_lastuse_by_module($modulename);
        if (!empty($lastuse_accounts)) {
            foreach ($lastuse_accounts as $lastuse_account_val) {
                $lastuse_account_info = uni_fetch($lastuse_account_val['uniacid']);
                if ($lastuse_account_info['type_sign'] == $module_support_type) {
                    $lastuse_table->searchWithUid($_W['uid']);
                    $lastuse_table->searchWithUniacid($lastuse_account_val['uniacid']);
                    $lastuse_table->searchWithModule($modulename);
                    $lastuse_table->delete();
                }
            }
        }
    }

    if (!in_array($support, $all_support)) {
        return false;
    }
    if ($type == MODULE_RECYCLE_UNINSTALL_IGNORE) {
        table('modules_cloud')->fill(array($support => 1, 'module_status' => MODULE_CLOUD_UNINSTALL_NORMAL))->where('name', $modulename)->save();
    }
    $module_recycle = table('modules_recycle');
    $record = $module_recycle->searchWithNameType($modulename, $type)->get();
    if (empty($record)) {
        return $module_recycle->fill(array('name' => $modulename, 'type' => $type, $support => 1))->save();
    } else {
        $record[$support] = 1;
        return $module_recycle->where('id', $record['id'])->fill($record)->save();
    }
}


function module_cancel_recycle($modulename, $type, $support)
{
    $all_support = array_keys(module_support_type());
    if (!in_array($support, $all_support)) {
        return false;
    }
    $module_recycle = table('modules_recycle');
    $record = $module_recycle->searchWithNameType($modulename, $type)->get();
    if (empty($record)) {
        return true;
    }
    $record[$support] = 0;
    $is_update = false;
    foreach ($all_support as $s) {
        if ($record[$s] == 1) {
            $is_update = true;
        }
    }
    if ($type == MODULE_RECYCLE_UNINSTALL_IGNORE) {
        table('modules_cloud')->fill(array($support => 2, 'module_status' => MODULE_CLOUD_UNINSTALL_NORMAL))->where('name', $modulename)->save();
    }
    if ($is_update) {
        return $module_recycle->where('id', $record['id'])->fill($record)->save();
    } else {
        return $module_recycle->where('id', $record['id'])->delete();
    }
}


function module_get_direct_enter_status($module_name)
{
    global $_W;
    if (empty($module_name)) {
        return STATUS_OFF;
    }
    $module_setting = table('uni_account_modules')->getByUniacidAndModule($module_name, $_W['uniacid']);
    $status = !empty($module_setting['settings']) && $module_setting['settings']['direct_enter'] == STATUS_ON ? STATUS_ON : STATUS_OFF;
    return $status;
}

function module_change_direct_enter_status($module_name)
{
    global $_W;
    if (empty($module_name)) {
        return false;
    }
    $module_setting = table('uni_account_modules')->getByUniacidAndModule($module_name, $_W['uniacid']);
    $direct_enter_status = !empty($module_setting['settings']) && $module_setting['settings']['direct_enter'] == STATUS_ON ? STATUS_OFF : STATUS_ON;
    if (empty($module_setting)) {
        $data = array('direct_enter' => $direct_enter_status);
        $result = table('uni_account_modules')->fill(array('settings' => iserializer($data), 'uniacid' => $_W['uniacid'], 'module' => $module_name))->save();
    } else {
        $module_setting['settings']['direct_enter'] = $direct_enter_status;
        $data = $module_setting['settings'];
        $result = table('uni_account_modules')->fill(array('settings' => iserializer($data)))->where('module', $module_name)->where('uniacid', $_W['uniacid'])->save();
    }
    return $result ? true : false;
}

function module_delete_store_wish_goods($module_name, $support_name)
{
    load()->model('store');
    $all_type = store_goods_type_info();
    foreach ($all_type as $info) {
        if ($info['group'] == 'module' && $support_name == $info['sign'] . '_support') {
            $type = $info['type'];
            break;
        }
    }
    if (!empty($type)) {
        pdo_update('site_store_goods', array('status' => 2), array('module' => $module_name, 'type' => $type));
    }
    return true;
}

function module_expire_notice()
{
    $module_expire = setting_load('module_expire');
    $module_expire = !empty($module_expire['module_expire']) ? $module_expire['module_expire'] : array();
    foreach ($module_expire as $value) {
        if ($value['status'] == 1) {
            $expire_notice = $value['notice'];
            break;
        }
    }
    if (empty($expire_notice)) {
        $system_module_expire = setting_load('system_module_expire');
        $expire_notice = !empty($system_module_expire['system_module_expire']) ? $system_module_expire['system_module_expire'] : '您访问的功能模块不存在，请重新进入';
    }
    return $expire_notice;
}