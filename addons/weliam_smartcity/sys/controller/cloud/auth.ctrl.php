<?php
defined('IN_IA') or exit('Access Denied');
set_time_limit(0);
load()->func('file');

class Auth_WeliamController {

    public function __construct() {
        global $_W;
        if (!$_W['isfounder']) {
            wl_message('无权访问!');
        }
    }

    public function auth() {
        global $_W, $_GPC;
        $auth = Cloud::auth_info();
        if (is_error($auth)) {
            wl_message($auth['message'], referer(), 'warning');
        }
        if (!empty($auth['plugins']) && $auth['encrypt'] == 1 && !file_exists(PATH_MODULE . 'check.php')) {
            Cloud::files_plugin_exit($auth['plugins']);
        }

        $auth['status'] = ($auth['status'] == 1) ? '禁用中' : ($auth['endtime'] < time() ? '已过期' : '已授权');
        $auth['number'] = '不限制';
        $auth['version'] = 'V' . $auth['version'];
        $auth['endtime'] = date("Y-m-d", $auth['endtime']);

        if(Customized::init('distributionText') > 0){
            $auth['name'] = str_replace('智慧城市同城','门店共享系统',$auth['name']);
        }

        include wl_template('cloud/auth');
    }

    public function upgrade() {
        global $_W, $_GPC;
        if ($_W['isajax']) {
            if (file_exists(PATH_MODULE . 'check.php')) {
                wl_json(1, '开发环境禁止更新');
            }

            //获取最新版本的文件
            $files_md5 = Cloud::api_post(array('do' => 'files_md5', 'url' => $_W['siteroot']));
            if ($files_md5['code'] != 0) {
                wl_json($files_md5['code'], $files_md5['message']);
            }

            $files = array();
            if (!empty($files_md5['data'])) {
                foreach ($files_md5['data'] as $file) {
                    if(IMS_FAMILY == 'wl'){
                        $entry = IA_ROOT.'/'.$file['path'];
                    }else{
                        $entry = PATH_MODULE . $file['path'];
                    }
                    //判断当前文件是否需要进行更新
                    if($this->notUpgradeFile($entry)) continue;
                    //储存需要更新的文件信息
                    if (!is_file($entry) || md5_file($entry) != $file['md5']) {
                        $files[] = array('path' => $file['path'], 'download' => 0, 'entry' => $entry);
                    }
                }
            }
            if (!empty($files)) {
                file_put_contents(FILES_UP_PATH, json_encode($files));
                wl_json(0, '', ['count' => count($files)]);
            }
            pdo_update('modules', array('version' => WELIAM_VERSION), array('name' => MODULE_NAME));
            wl_json(1, '已是最新版本，无需进行更新');
        }

        $log = [];
        $update_logs = Cloud::api_post(array('do' => 'get_update_log', 'url' => $_W['siteroot'], 'page' => intval($_GPC['page'])));
        if ($update_logs['code'] == 0) {
            $log = $update_logs['data']['logs'][0];
        }
        if (file_exists(PATH_MODULE . 'manifest.xml') && !IS_DEV) {
            unlink(PATH_MODULE . 'manifest.xml');
        }

        include wl_template('cloud/upgrade');
    }

    public function upgrade_download() {

        global $_W, $_GPC;
        // wl_debug(MODULE_NAME);
        if (!file_exists(FILES_UP_PATH)) {
            wl_json(1, '不存在需要更新的文件或更新异常');
        }
        //清理opcache缓存
        if (ini_get('opcache.enable') || ini_get('opcache.enable_cli')) {
            opcache_reset();
        }

        $files = json_decode(file_get_contents(FILES_UP_PATH), true);
        $count_files = count($files);
        //判断是否存在需要更新的文件
        $key = $path = $success = 0;
        foreach ($files as $k => &$f) {
            if (empty($f['download'])) {
                $path = $f['path'];
                $key = $k;
                break;
            } else {
                $success++;
            }
        }

        if (!empty($path)) {

            $files_up = Cloud::api_post(array('do' => 'files_get', 'url' => $_W['siteroot'], 'path' => $path));
            if ($files_up['code'] != 0) {
                wl_json(1, $files_up['message'], array('total' => $count_files, 'success' => $success));
            }
            $content = base64_decode(trim($files_up['data']));
            //根据路径创建目录和文件
            if(IMS_FAMILY == 'wl'){
                FilesHandle::file_mkdirs(dirname(IA_ROOT.'/'. $path));
            }else{
                FilesHandle::file_mkdirs(dirname(PATH_MODULE . $path));
            }
            //修改文件内容
            $fileName = basename($path,'.'.pathinfo($path)['extension']);
            $fileName = ucfirst(preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
                return strtoupper($matches[2]);
            }, $fileName));
            $funName = "update{$fileName}Content";
            if(method_exists($this,$funName)) $content = $this->$funName($content);
            //替换  h5中模块标志
            if ((strpos($path, 'index.html')  !== false || strpos($path, 'static/js/index.')  !== false) && MODULE_NAME != 'weliam_smartcity') {
                $content = str_replace('weliam_smartcity', MODULE_NAME, $content);
            }
            //覆盖更新文件内容
            if(strpos($path, 'auth.ctrl.php') === false){
                if(IMS_FAMILY == 'wl'){
                    file_put_contents(IA_ROOT.'/' . $path, $content);
                }else{
                    file_put_contents(PATH_MODULE . $path, $content);
                }
            }
            // wl_debug($content);
            //修改文件下载状态
            $files[$key]['download'] = 1;
            file_put_contents(FILES_UP_PATH, json_encode($files));

            wl_json(0, '', array('total' => $count_files, 'success' => $success + 1));
        } else {
            //更新版本号
            touch(VERSION_PATH);
            pdo_update('modules', array('version' => WELIAM_VERSION), array('name' => MODULE_NAME));
            //删除更新文件列表
            unlink(FILES_UP_PATH);
            wl_json(2, '');
        }
    }

    public function upgrade_db() {
        global $_W, $_GPC;
        $sqlcache = Cache::getCache('upgrade', 'db');
        if (empty($sqlcache)) {
            $sqls = Cloud::auth_up_data();
            $sqlcache = ['total' => count($sqls), 'success' => 0, 'sqls' => $sqls];
        }

        if (!empty($sqlcache['sqls'])) {
            if (is_array($sqlcache['sqls'][0])) {
                switch ($sqlcache['sqls'][0]['type']) {
                    case 'store_cate':
                        Cloud::data_store_cate($sqlcache['sqls'][0]['sid']);
                        break;
                }
            } else {
                pdo_query($sqlcache['sqls'][0]);
            }

            $sqlcache['success'] = $sqlcache['success'] + 1;
            unset($sqlcache['sqls'][0]);
            $sqlcache['sqls'] = array_values($sqlcache['sqls']);
            Cache::setCache('upgrade', 'db', $sqlcache);

            wl_json(0, '', array('total' => $sqlcache['total'], 'success' => $sqlcache['success']));
        }

        Cache::deleteCache('upgrade', 'db');
        wl_json(1, '');
    }

    public function upgrade_log() {
        global $_W, $_GPC;
        if ($_W['isajax']) {
            $update_logs = Cloud::api_post(array('do' => 'get_update_log', 'url' => $_W['siteroot'], 'page' => intval($_GPC['page'])));
            if ($update_logs['code'] != 0) {
                wl_json(1, $update_logs['message']);
            }
            foreach ($update_logs['data']['logs'] as &$log) {
                $log['content'] = htmlspecialchars_decode($log['content']);
                $log['year'] = date('Y-m', $log['updated_at']);
                $log['day'] = date('d', $log['updated_at']);
                $log['hour'] = date('H:i:s', $log['updated_at']);
            }

            wl_json(0, '', $update_logs['data']);
        }
        include wl_template('cloud/upgrade_log');
    }


    /**
     * Comment: 判断当前文件是否需要进行更新
     * Author: zzw
     * Date: 2020/8/21 12:12
     * @param $path
     * @return bool
     */
    public function notUpgradeFile($path){
        $status = false;
        if(strpos($path, '/plugin/weliam_house') !== false){
            //无需更新文件
            $notFile = [
                '.txt' ,
                '.zip' ,
                'check.php' ,
                '.md' ,
                'LICENSE' ,
                '.gitignore' ,//git限制更新文件
                'runtime' ,//缓存文件
                'public/file/image' ,//本地图片储存路径
                'public/file/qrcode' ,//二维码储存地址
                'public/file/video' ,//本地视频储存地址
                'public/file/log' ,//日志信息记录
                'public/w7_wl_house_install.zip' ,//微擎版安装包
                'public/uploads' ,//本地图片储存地址(已弃用)
            ];
            foreach($notFile as $notFilePath){
                if(strpos($path, $notFilePath) !== false) {
                    $status = true;
                    break;
                }
            }
            //只在第一次进入时更新的文件(需要安装单不能更新的文件)
            $oneFile = [
                'addons/address/config.php' ,//fastadmin第三方插件 - 地图插件配置文件,
                'addons/alisms/config.php' ,//fastadmin第三方插件 - 阿里云短信配置文件,
                'application/system/sys_config.php' ,//总后台配置文件
                'application/database.php' ,//数据库配置文件
            ];
            if(!$status){
                foreach($oneFile as $noeFilePath){
                    if(strpos($path, $noeFilePath) !== false && file_exists($path)){
                        $status = true;
                        break;
                    }
                }
            }
        }
        return $status;
    }
    /**
     * Comment: 数据库文件初始化内容
     * Author: zzw
     * Date: 2020/8/21 14:46
     * @param $content
     * @return string
     */
    protected function updateDatabaseContent($content){
        //内容替换
        $html = '
<?php        
// +----------------------------------------------------------------------

use think\Env;

return [
    // 数据库类型
    \'type\'            => Env::get(\'database.type\', \'mysql\'),
    \'hostname\'        => Env::get(\'database.hostname\', \'__HOSTNAME__\'), // 服务器地址
    \'database\'        => Env::get(\'database.database\', \'__DATABASE__\'),// 数据库名
    \'username\'        => Env::get(\'database.username\', \'__USERNAME__\'),// 用户名
    \'password\'        => Env::get(\'database.password\', \'__PASSWORD__\'),// 密码
    \'hostport\'        => Env::get(\'database.hostport\', \'__HOSTPORT__\'),// 端口
    // 连接dsn
    \'dsn\'             => \'\',
    // 数据库连接参数
    \'params\'          => [],
    // 数据库编码默认采用utf8
    \'charset\'         => Env::get(\'database.charset\', \'utf8\'),
    // 数据库表前缀
    \'prefix\'          => Env::get(\'database.prefix\', \'__PREFIX__\'),
    // 数据库调试模式
    \'debug\'           => Env::get(\'database.debug\', false),
    // 数据库部署方式:0 集中式(单一服务器),1 分布式(主从服务器)
    \'deploy\'          => 0,
    // 数据库读写是否分离 主从式有效
    \'rw_separate\'     => false,
    // 读写分离后 主服务器数量
    \'master_num\'      => 1,
    // 指定从服务器序号
    \'slave_no\'        => \'\',
    // 是否严格检查字段是否存在
    \'fields_strict\'   => true,
    // 数据集返回类型
    \'resultset_type\'  => \'array\',
    // 自动写入时间戳字段
    \'auto_timestamp\'  => true,
    // 时间字段取出后的默认时间格式,默认为Y-m-d H:i:s
    \'datetime_format\' => false,
    // 是否需要进行SQL性能分析
    \'sql_explain\'     => false,
];';

        return $html;
    }



}
