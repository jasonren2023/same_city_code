<?php
defined('IN_IA') or exit('Access Denied');

class App {

    /**
     * 根据渠道获取插件
     * @param string $type  sys总后台 agent代理后台 store商家后台
     * @return array
     */
    static function get_cate_plugins($type = 'sys') {
        global $_W;
        if($type == 'sys'){
            $plugins = self::get_apps($_W['uniacid']);
        }else if($type == 'agent'){
            $plugins = self::get_apps($_W['aid'], 'agent');
        }else if($type == 'store'){
            $plugins = self::get_apps($_W['storeid'],'store');
        }
        $category = self::getCategory();

        foreach ($plugins as $plugin) {
            if ((!empty($_W['jurisdiction']) && (in_array(Util::urlRestore($plugin['cover']), $_W['jurisdiction']) || !in_array(Util::urlRestore($plugin['cover']), $_W['JUrlList']))) || empty($_W['jurisdiction'])) {
                $category[$plugin['category']]['plugins'][] = $plugin;
            }
        }

        return $category;
    }

    static function getPlugins($type = 3) {
        $styles = Util::traversingFiles(PATH_PLUGIN);
        $pluginsset = array();

        foreach ($styles as $key => $value) {
            $config = self::ext_plugin_config($value);
            if (!empty($config) && is_array($config)) {
                unset($config['menus']);
                //向数据库插入插件数据
                $plugininfo = pdo_get('wlmerchant_plugin', array('name' => $value));
                if (empty($plugininfo)) {
                    pdo_insert('wlmerchant_plugin', array('name' => $config['ident'], 'type' => $config['category'], 'title' => $config['name'], 'thumb' => '../addons/'.MODULE_NAME.'/plugin/' . $config['ident'] . '/icon.png', 'ability' => $config['des'], 'status' => 1));
                } elseif ($plugininfo['status'] != 1) {
                    continue;
                } else {
                    $config['name'] = $plugininfo['title'];
                    $config['thumb'] = $plugininfo['thumb'];
                    $config['des'] = $plugininfo['ability'];
                    $config['displayorder'] = $plugininfo['displayorder'];
                }
                if ($type == 1 && $config['setting']['agent'] == 'true') {
                    $pluginsset[$value] = $config;
                } elseif ($type == 2 && $config['setting']['system'] == 'true') {
                    $pluginsset[$value] = $config;
                } elseif ($type == 4 && $config['setting']['store'] == 'true') {
                    $pluginsset[$value] = $config;
                } elseif ($type == 3) {
                    $pluginsset[$value] = $config;
                }
            }
        }
        $pluginsset = Util::multi_array_sort($pluginsset, 'displayorder', SORT_DESC);

        return $pluginsset;
    }

    static function getCategory() {
        return array(
            'channel'  => array('name' => '渠道管理', 'color' => '#009AFE'),
            'market'   => array('name' => '营销应用', 'color' => '#F85959'),
            'interact' => array('name' => '互动应用', 'color' => '#11CD6E'),
            'expand'   => array('name' => '拓展应用', 'color' => '#FEB822'),
            'help'     => array('name' => '辅助应用', 'color' => '#8f57ff')
        );
    }

    static function get_apps($id = 0, $type = 'account') {
        global $_W;
        if ($type == 'account') {
            $plugins = self::getPlugins(2);
            $perms = self::get_account_perm("plugins", $id);
        }else if($type == 'store'){
            $plugins = self::getPlugins(4);
            if(!empty($_W['authority'])){
                $perms = $_W['authority'];
            }
            if(!in_array('halfcard',$perms)){
                $plugins['halfcard']['cover'] = web_url('halfcard/halfcard_web/packagelist');
            }
            if(in_array('package',$perms)){
                $perms[] = 'halfcard';
            }
        } else {
            $plugins = self::getPlugins(1);
            $perms = self::get_account_perm("plugins");
            if (!empty($id)) {
                $aperms = Area::getSingleGroup(pdo_getcolumn(PDO_NAME . 'agentusers', array('uniacid' => $_W['uniacid'], 'id' => $id), 'groupid'));
            }
            $perms = !empty($perms) ? (!empty($aperms['package']) ? array_intersect($perms, $aperms['package']) : $perms) : $aperms['package'];
        }

        foreach ($plugins as $key => $row) {
            if (!empty($perms) && !in_array($row["ident"], $perms)) {
                unset($plugins[$key]);
            }
        }
        return $plugins;
    }

    static function get_account_perm($key = '', $uniacid = 0) {
        global $_W;
        if (empty($uniacid)) {
            $uniacid = $_W['uniacid'];
        }
        $perm = pdo_get('wlmerchant_perm_account', array('uniacid' => $uniacid));
        if (empty($perm)) {
            return false;
        }
        if (!empty($perm)) {
            $perm['plugins'] = iunserializer($perm['plugins']);
            if (!is_array($perm['plugins'])) {
                $perm['plugins'] = array();
            }
            if (empty($perm['plugins'])) {
                $perm['plugins'] = array('none');
            }
            if (!empty($key)) {
                return $perm[$key];
            }
        }
        return $perm;
    }

    static function ext_plugin_config($plugin) {
        $filename = PATH_PLUGIN . $plugin . '/config.xml';
        if (!file_exists($filename)) {
            return array();
        }
        $manifest = self::ext_plugin_config_parse(file_get_contents($filename));
        if (empty($manifest['name']) || $manifest['ident'] != $plugin) {
            return array();
        }
        return $manifest;
    }

    static function ext_plugin_config_parse($xml) {
        if (!strexists($xml, '<manifest')) {
            $xml = base64_decode($xml);
        }
        if (empty($xml)) {
            return array();
        }
        $dom = new DOMDocument();
        $dom->loadXML($xml);
        $root = $dom->getElementsByTagName('manifest')->item(0);
        if (empty($root)) {
            return array();
        }

        $application = $root->getElementsByTagName('application')->item(0);
        if (empty($application)) {
            return array();
        }
        $manifest = array(
            'name'     => trim($application->getElementsByTagName('name')->item(0)->textContent),
            'ident'    => trim($application->getElementsByTagName('identifie')->item(0)->textContent),
            'version'  => trim($application->getElementsByTagName('version')->item(0)->textContent),
            'category' => trim($application->getElementsByTagName('type')->item(0)->textContent),
            'des'      => trim($application->getElementsByTagName('description')->item(0)->textContent),
            'author'   => trim($application->getElementsByTagName('author')->item(0)->textContent),
            'url'      => trim($application->getElementsByTagName('url')->item(0)->textContent),
        );

        $manifest['setting']['store'] = 'false';
        $manifest['setting']['agent'] = 'false';
        $manifest['setting']['system'] = 'false';
        $manifest['setting']['task'] = 'false';
        $setting = $root->getElementsByTagName('setting')->item(0);
        if (!empty($setting)) {
            $agent = $setting->getElementsByTagName('agent')->item(0);
            if (!empty($agent) && $agent->getAttribute('embed') == 'true') {
                $manifest['setting']['agent'] = 'true';
            }
            $system = $setting->getElementsByTagName('system')->item(0);
            if (!empty($system) && $system->getAttribute('embed') == 'true') {
                $manifest['setting']['system'] = 'true';
            }
            $store = $setting->getElementsByTagName('store')->item(0);
            if (!empty($store) && $store->getAttribute('embed') == 'true') {
                $manifest['setting']['store'] = 'true';
            }
            $task = $setting->getElementsByTagName('task')->item(0);
            if (!empty($task) && $task->getAttribute('embed') == 'true') {
                $manifest['setting']['task'] = 'true';
            }
        }
        if (defined('IN_STORE') && $manifest['setting']['store'] == 'true') {
            $elm = $root->getElementsByTagName('storemenu')->item(0);
        } else if (defined('IN_WEB') && $manifest['setting']['agent']) {
            $elm = $root->getElementsByTagName('agentmenu')->item(0);
        } else {
            $elm = $root->getElementsByTagName('systemmenu')->item(0);
        }
        $manifest['menus'] = self::ext_plugin_config_entries($elm, $manifest);

        return $manifest;
    }

    static function ext_plugin_config_entries($elm, &$manifest) {
        $frames = array();
        $menus = $elm->getElementsByTagName('menu');
        foreach ($menus as $i => $cmenu) {
            $frames[$manifest['ident'] . $i]['title'] = '<i class="fa ' . $cmenu->getAttribute('font') . '"></i>&nbsp;&nbsp; ' . $cmenu->getAttribute('title');
            $entries = $cmenu->getElementsByTagName('entry');
            for ($j = 0; $j < $entries->length; $j++) {
                $entry = $entries->item($j);
                $ac = $entry->getAttribute('ac');
                $do = $entry->getAttribute('do');
                $iscover = $entry->getAttribute('iscover');
                $target = $entry->getAttribute('target');
                $actions = json_decode($entry->getAttribute('actions'));
                $actions = !empty($actions) ? $actions : array('ac', $ac, 'do', $do);
                $row = array(
                    'url'     => web_url($manifest['ident'] . '/' . $ac . '/' . $do),
                    'title'   => $entry->getAttribute('title'),
                    'actions' => $actions,
                    'active'  => ''
                );
                $manifest['target'] = $target ? : '_self';
                if ($iscover == 'true') {
                    $manifest['cover'] = $row['url'];
                }
                if (!empty($row['title']) && !empty($row['url'])) {
                    $frames[$manifest['ident'] . $i]['items'][$ac . $do] = $row;
                }
            }
        }
        return $frames;
    }
}
