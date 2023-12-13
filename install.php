<?php
/****** 基本信息定义 *************************************************************************************************/
error_reporting(0);
define('IN_IA', true);
define('ROOT_PATH', __DIR__ . '/');//定义当前网站根目录
define('MODULE_NAME', 'weliam_smartcity');// 定义产品标识
define('API_URL', 'https://t.weliam.cn/api.php');// API地址
@$method = $_REQUEST['method'] ?: '';
/****** 判断准备信息是否完善 *****************************************************************************************/
if (!extension_loaded("PDO")) error('请开启【PDO】扩展！');
if (!extension_loaded("zip")) error('请开启【ZIP】扩展！');
if (!extension_loaded("curl")) error('请开启【CURL】扩展！');
//if (!is_https()) error('请使用https安全协议登录，且确保ssl证书正常！');
if (version_compare(PHP_VERSION, '7.1.0', '<')) error('当前版本(' . PHP_VERSION . ')过低，请使用PHP7.1.0以上版本！');
if (!is_writable(ROOT_PATH) || !is_readable(ROOT_PATH)) error('站点目录不可写，请修改目录权限！');
$php_self = substr($_SERVER['PHP_SELF'], strrpos($_SERVER['PHP_SELF'], '/') + 1);//当前文件名称

/****** 公共方法定义 *************************************************************************************************/
/**
 * Comment: 信息打印
 * Author: zzw
 * Date: 2020/6/30 15:58
 * @param $value
 */
function wl_debug($value)
{
    echo "<br><pre>";
    print_r($value);
    echo "</pre>";
    exit;
}

function getRequestMethod()
{
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest') return 'AJAX';
    else if (!empty($_POST)) return 'POST';
    else return 'GET';
}

/**
 * Comment: 错误抛出
 * Author: zzw
 * Date: 2020/6/30 15:58
 * @param string $msg
 */
function error($msg)
{
    $type = getRequestMethod();
    if ($type == 'GET') {
        $html = '<div id="error" style="position: absolute;top: 0;left: 0;width: 100vw;height: 100vh;background-image: linear-gradient(to left, #072e61, #004880);overflow: hidden;text-align: center;font-size: 20px;color: #ffffff;">
                 <div class="msg" style="margin: auto;margin-top: 15%;">' . $msg . '</div>
                 <div class="msg" style="margin: auto;">刷新重试！</div>
             </div>';
        wl_debug($html);
    } else {
        echo json_encode(['code' => 0, 'msg' => $msg]);
        die;
    }
}

/**
 * Comment: 判断文件或目录是否有写的权限
 * Date: 2020/6/30 15:59
 * @param $file
 * @return bool
 */
function is_really_writable($file)
{
    if (DIRECTORY_SEPARATOR == '/' AND @ ini_get("safe_mode") == false) {
        return is_writable($file);
    }
    if (!is_file($file) OR ($fp = @fopen($file, "r+")) === false) {
        return false;
    }

    fclose($fp);
    return true;
}

/**
 * Comment: 判断是否是https
 * Date: 2020/6/30 15:59
 * @return bool
 */
function is_https()
{
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        return true;
    }
    return false;
}

/**
 * Comment: 域名信息获取
 * Author: zzw
 * Date: 2020/6/30 16:10
 * @param bool $status
 * @return mixed|string
 */
function getDomain($status = true)
{
    //基本信息获取
    $domain = $_SERVER['HTTP_HOST'];//域名
    //判断是否获取完整域名(带有http|https前缀)
    if ($status) {
        $http = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        $domain = $http . $domain;
    }
    return $domain;
}

/**
 * Comment: 文件目录建立
 * Author: zzw
 * Date: 2020/7/1 9:22
 * @param $path
 * @return bool
 */
function file_mkdir($path)
{
    if (!is_dir($path)) {
        file_mkdir(dirname($path));
        mkdir($path);
    }
    return is_dir($path);
}

/**
 * Comment: 异步请求
 * Author: zzw
 * Date: 2020/6/30 16:11
 * @param        $url
 * @param array $postData
 * @param string $header
 * @return array|void
 */
function curlPostRequest($url, $postData = [], $header = '')
{
    $curl = curl_init();//初始化
    curl_setopt($curl, CURLOPT_URL, $url);//设置抓取的url
    if ($header) curl_setopt($curl, CURLOPT_HTTPHEADER, $header);//设置header信息
    curl_setopt($curl, CURLOPT_HEADER, 1);//设置头文件的信息作为数据流输出
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//设置获取的信息以文件流的形式返回，而不是直接输出。
    curl_setopt($curl, CURLOPT_POST, 1);//设置post方式提交
    curl_setopt($curl, CURLOPT_POSTFIELDS, $postData);
    $data = curl_exec($curl); //执行命令
    $curlError = '';
    if ($data === false) $curlError = curl_error($curl);
    $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);// 获得响应结果里的：header头大小
    $info = substr($data, $headerSize);//通过截取 获取body信息
    curl_close($curl);//关闭URL请求
    //返回结果信息
    if ($curlError) return ['code' => 0, 'msg' => $curlError];
    else return ['code' => 1, 'msg' => '请求结果', 'data' => json_decode($info, true)];//返回获取的信息数据
}

/**
 * Comment: 公共请求方法
 * Author: zzw
 * Date: 2020/6/30 16:12
 * @param $params
 * @return array|void
 */
function request($params)
{
    //公共参数
    $domain = getDomain() . '/';//获取域名
    $where = [
        'url' => $domain,
        'pd'  => MODULE_NAME,
        'ind' => 1
    ];
    //合并参数
    $data = array_merge($params, $where);
    $res = curlPostRequest(API_URL, $data, ['Content-Type' => 'application/x-www-form-urlencoded']);
    if ($res['code'] == 0) {
        error($res['msg']);
    }

    return $res['data'];
}

/**
 * Comment: 获取授权信息
 * Author: zzw
 * Date: 2020/6/30 16:21
 * @return mixed
 */
function authInfo()
{
    //请求获取授权信息 微连返回code: 0=成功。1=失败   需要转换
    $res = request(['code' => '']);
    if ($res['code'] != 0) error($res['message']);
}

/**
 * Comment: 获取所有的待安装文件
 * Author: zzw
 * Date: 2020/6/30 16:29
 * @return array
 */
function newFile()
{
    //请求获取最新版本的文件
    $newFile = request(['do' => 'files_md5']);
    if ($newFile['code'] != 0) error($newFile['msg']);
    //获取需要更新的文件列表
    $files = [];
    foreach ($newFile['data'] as $file) {
        if (!is_file(ROOT_PATH . $file['path']) || md5_file(ROOT_PATH . $file['path']) != $file['md5']) {
            $files[] = ['path' => $file['path'], 'download' => 0];
        }
    }
    //信息拼装
    $data = [
        'files' => $files,
        'total' => count($files)
    ];

    file_put_contents('upgrade.json', json_encode($data));
}

/**
 * Comment: 获取下载文件信息
 * Author: zzw
 * Date: 2020/6/30 18:16
 * @param $path
 * @return array|void
 */
function getDownloadFile($path)
{
    //请求获取最新版本的文件
    $res = request(['do' => 'files_get', 'path' => $path]);
    if ($res['code'] != 0) error($res['message']);

    return $res['data'];
}

/**
 * Comment: 文件下载
 * Author: zzw
 * Date: 2020/6/30 18:33
 * @param $data
 * @param $path
 * @return string
 */
function downloadFile($data, $path)
{
    $fileAbsolutePath = ROOT_PATH . $path;
    $content = base64_decode(trim($data));
    if (!file_exists(dirname($fileAbsolutePath))) file_mkdir(dirname($fileAbsolutePath));
    file_put_contents($fileAbsolutePath, $content);

    return $fileAbsolutePath;
}


/****** 安装类 *******************************************************************************************************/
class WeliamInstall
{
    protected $sqlData;

    /**
     * Comment: 基本信息处理
     * WeliamInstall constructor.
     * @param $data
     */
    public function __construct($data)
    {
        $this->sqlData = $data;
    }

    /**
     * Comment: 安装初始包
     * Author: zzw
     * Date: 2020/6/30 18:37
     * @return $this
     */
    public function initStall()
    {
        //判断内容是否完整
        if (!$this->sqlData['hostname']) error("请输入服务器地址");
        if (!$this->sqlData['database']) error("请输入数据库名");
        if (!$this->sqlData['username']) error("请输入数据库用户名");
        if (!$this->sqlData['password']) error("请输入数据库密码");
        if (!$this->sqlData['prefix']) error("请输入数据库表前缀");
        if (!$this->sqlData['hostport']) error("请输入数据库端口号");
        //判断数据库是否可用
        @$con = mysqli_connect($this->sqlData['hostname'], $this->sqlData['username'], $this->sqlData['password']);
        if (mysqli_connect_errno($con)) {
            error("数据库连接失败: " . mysqli_connect_error());
        }
        //修改数据库配置信息
        $databasePath = ROOT_PATH . 'data/config.php';
        if (!file_exists($databasePath)) {
            if (!is_dir(dirname($databasePath))) {
                file_mkdir(dirname($databasePath));
            }
            $content = <<<VER
<?php
defined('IN_IA') or exit('Access Denied');

\$config = array();
\$config['db']['master']['host'] = "{$this->sqlData['hostname']}";
\$config['db']['master']['username'] = "{$this->sqlData['username']}";
\$config['db']['master']['password'] = "{$this->sqlData['password']}";
\$config['db']['master']['port'] = "{$this->sqlData['hostport']}";
\$config['db']['master']['database'] = "{$this->sqlData['database']}";
\$config['db']['master']['charset'] = 'utf8';
\$config['db']['master']['pconnect'] = 0;
\$config['db']['master']['tablepre'] = "{$this->sqlData['prefix']}";

\$config['db']['slave_status'] = false;
\$config['db']['slave']['1']['host'] = '';
\$config['db']['slave']['1']['username'] = '';
\$config['db']['slave']['1']['password'] = '';
\$config['db']['slave']['1']['port'] = '3307';
\$config['db']['slave']['1']['database'] = '';
\$config['db']['slave']['1']['charset'] = 'utf8';
\$config['db']['slave']['1']['pconnect'] = 0;
\$config['db']['slave']['1']['tablepre'] = "{$this->sqlData['prefix']}";
\$config['db']['slave']['1']['weight'] = 0;
\$config['db']['common']['slave_except_table'] = array('core_sessions');

\$config['cookie']['pre'] = 'b906_';
\$config['cookie']['domain'] = '';
\$config['cookie']['path'] = '/';

\$config['setting']['charset'] = 'utf-8';
\$config['setting']['cache'] = 'mysql';
\$config['setting']['timezone'] = 'Asia/Shanghai';
\$config['setting']['memory_limit'] = '256M';
\$config['setting']['filemode'] = 0644;
\$config['setting']['authkey'] = 'a132aeb1';
\$config['setting']['founder'] = '1';
\$config['setting']['development'] = 0;
\$config['setting']['referrer'] = 0;
\$config['setting']['https'] = 0;

\$config['upload']['image']['extentions'] = array('gif', 'jpg', 'jpeg', 'png');
\$config['upload']['image']['limit'] = 5000;
\$config['upload']['attachdir'] = 'attachment';
\$config['upload']['audio']['extentions'] = array('mp3', 'mp4');
\$config['upload']['audio']['limit'] = 5000;

\$config['setting']['redis']['server'] = '127.0.0.1';
\$config['setting']['redis']['port'] = 6379;
\$config['setting']['redis']['pconnect'] = 1;
\$config['setting']['redis']['timeout'] = 30;
\$config['setting']['redis']['session'] = 0;
\$config['setting']['redis']['requirepass'] = '';
VER;
            file_put_contents($databasePath, $content);
        }
        //获取需要更新的文件
        newFile();

        echo json_encode(['code' => 1, 'msg' => '安装成功']);
        die;
    }

    /**
     * Comment: 文件更新
     * Author: zzw
     * Date: 2020/6/30 18:49
     * @return bool
     */
    public function upgradeFile()
    {
        //获取需要更新的文件的列表
        $newFile = [];
        $total = 0;
        if (file_exists('upgrade.json')) {
            $data = json_decode(file_get_contents('upgrade.json'), true);
            $newFile = $data['files'];
            $total = $data['total'];
        } else {
            error("未找到更新文件！");
        }

        if (is_array($newFile) && count($newFile) > 0) {
            //更新文件
            $item = $newFile[0];
            $data = getDownloadFile($item['path']);
            //文件下载
            downloadFile($data, $item['path']);
            //删除当前文件信息
            unset($newFile[0]);
            $newFile = array_values($newFile);
            //覆盖文件储存信息
            $data = [
                'files' => $newFile,
                'total' => $total
            ];
            file_put_contents('upgrade.json', json_encode($data));
            //计算进度
            $progress = ($total - count($newFile)) / $total;
            $progress = sprintf("%.2f", $progress * 100);

            echo json_encode(['code' => 1, 'msg' => '下载成功', 'type' => 1, 'progress' => $progress, 'path' => $item['path']]);
            die;
        } else {
            //下载完成  删除更新文件内容
            unlink("upgrade.json");

            echo json_encode(['code' => 1, 'msg' => '安装完成', 'type' => 0]);
            die;
        }
    }


    /**
     * Comment: 获取并且执行sql操作语句
     * Author: zzw
     * Date: 2020/7/2 19:26
     */
    public function upgradeDb()
    {
        //获取数据库更新信息
        if (!file_exists('upgrade_db.json')) {
            $diffSql = [];
            $tables = json_decode(base64_decode(file_get_contents(ROOT_PATH . "addons/" . MODULE_NAME . "/core/common/dbfile.php")), true);
            foreach ($tables as $tableInfo) {
                $diffSql = array_merge($diffSql, $this->getTableDiffInfo($tableInfo));
            }
            $sqlCaChe = ['total' => count($diffSql) + 2, 'success' => 0, 'sql' => $diffSql];
            $sqlCaChe = $this->otherInfo($sqlCaChe);
            file_put_contents('upgrade_db.json', json_encode($sqlCaChe));
        } else {
            $sqlCaChe = json_decode(file_get_contents('upgrade_db.json'), true);
        }

        //判断是否存在需要继续执行
        if (is_array($sqlCaChe['sql']) && !empty($sqlCaChe['sql'])) {
            //执行语句
            (new Db())->query(trim($sqlCaChe['sql'][0]));
            $sqlCaChe['success'] = $sqlCaChe['success'] + 1;
            unset($sqlCaChe['sql'][0]);
            $sqlCaChe['sql'] = array_values($sqlCaChe['sql']);
            file_put_contents('upgrade_db.json', json_encode($sqlCaChe));
            //计算进度
            $progress = $sqlCaChe['success'] / $sqlCaChe['total'];
            $progress = sprintf("%.2f", $progress * 100);

            echo json_encode(['code' => 1, 'msg' => '下载数据库', 'type' => 1, 'progress' => $progress]);
            die;
        } else {
            //下载完成  删除更新文件内容
            unlink("upgrade_db.json");
            //下载完成  删除当前安装文件
            unlink("index.php");

            echo json_encode(['code' => 1, 'msg' => '安装完成', 'type' => 0]);
            die;
        }
    }

    /**
     * Comment: 数据库更新 - 获取表的差异
     * Author: zzw
     * Date: 2020/7/2 19:27
     * @param $table
     * @return array
     */
    protected function getTableDiffInfo($table)
    {
        $sqlArr = [];
        if ($table['table']) {
            $oldTable = $this->getTableData($this->getTableName($table['table']));
            if (!$oldTable) {
                //表不存在  创建表
                $sqlArr[] = $this->createTable($table);
            } else {
                //对比字段
                foreach ($table['fields'] as $fk => $field) {
                    if (empty($oldTable['fields'][$fk])) {
                        //字段不存在，增加字段
                        $sqlArr[] = ($field['increment'] == 1) ? $this->tableFieldEdit($table['table'], $field, 1, $table['indexes']['PRIMARY']) : $this->tableFieldEdit($table['table'], $field, 1);
                    } elseif (array_diff_assoc($table['fields'][$fk], $oldTable['fields'][$fk]) || array_diff_assoc($oldTable['fields'][$fk], $table['fields'][$fk])) {
                        //字段有变化，修改字段
                        $sqlArr[] = $this->tableFieldEdit($table['table'], $field, 2);
                    }
                }
                //对比索引
                foreach ($table['indexes'] as $idx => $index) {
                    if ($idx == 'PRIMARY') continue;
                    if (empty($oldTable['indexes'][$idx])) {
                        //索引不存在，增加索引
                        $sqlArr[] = $this->tableIndexEdit($table['table'], $index, 1);
                    } elseif (array_diff_assoc($table['indexes'][$idx], $oldTable['indexes'][$idx]) || array_diff_assoc($oldTable['indexes'][$idx]['fields'], $table['indexes'][$idx]['fields']) || array_diff_assoc($table['indexes'][$idx]['fields'], $oldTable['indexes'][$idx]['fields'])) {
                        //索引有变化，删除索引，新建索引
                        $sqlArr[] = $this->tableIndexEdit($table['table'], $index, 2);
                        $sqlArr[] = $this->tableIndexEdit($table['table'], $index, 1);
                    }
                }
                //对比表内容
                if (is_array($table['content']) && $table['content']) {
                    $contentSqlArr = $this->tableContentSql($table['table'], $table['content']);
                    if (is_array($contentSqlArr) && $contentSqlArr) {
                        foreach ($contentSqlArr as $contentSql) {
                            $sqlArr[] = $contentSql;
                        }
                    }
                }
                //多余索引，需要删除
                foreach ($oldTable['indexes'] as $oidx => $oindex) {
                    if (empty($table['indexes'][$oidx])) {
                        $sqlArr[] = $this->tableIndexEdit($table['table'], $oindex, 2);
                    }
                }
                //对比存储引擎
                if ($table['engine'] != $oldTable['engine']) {
                    $sqlArr[] = "ALTER TABLE " . $this->getTableName($table['table']) . " ENGINE=" . $table['engine'] . ", ROW_FORMAT=DEFAULT;";
                }
            }
        }

        return $sqlArr;
    }

    /**
     * Comment: 生成数据库更新文件 - 获取表的基本信息&配置信息
     * Author: zzw
     * Date: 2020/7/2 19:27
     * @param $tableName
     * @return array
     */
    protected function getTableData($tableName)
    {
        //获取表的基本信息  并且判断表是否存在
        $result = (new Db())->query("SHOW TABLE STATUS LIKE '{$tableName}'")[0];
        if (!$result || !$result['Create_time']) return [];
        $completePrefix = $this->sqlData['prefix'] . "house_";//完整的表前缀
        //基本信息获取
        $prefix = str_replace('house_', '', $completePrefix);
        $ret["table"] = str_replace($prefix, '', $tableName);//table取消前缀
        $ret["tablename"] = $result["Name"];
        $ret["charset"] = $result["Collation"];
        $ret["engine"] = $result["Engine"];
        $ret["increment"] = $result["Auto_increment"];
        $fieldList = (new Db())->query("SHOW FULL COLUMNS FROM `{$tableName}`");
        foreach ($fieldList as $fieldV) {
            $temp = [];
            $type = explode(" ", $fieldV["Type"], 2);
            $temp["name"] = $fieldV["Field"];
            $pieces = explode("(", $type[0], 2);
            $temp["type"] = $pieces[0];
            $temp["length"] = rtrim($pieces[1], ")");
            $temp["null"] = !($fieldV["Null"] == "NO");
            $temp["signed"] = empty($type[1]);
            $temp["increment"] = $fieldV["Extra"] == "auto_increment";
            if (!empty($fieldV['Comment'])) {
                $temp["comment"] = $fieldV["Comment"];
            }
            if ($fieldV["Default"] != null) {
                $temp["default"] = $fieldV["Default"];
            }
            $ret["fields"][$fieldV["Field"]] = $temp;
        }
        //获取表的基本配置信息
        $configList = (new Db())->query("SHOW INDEX FROM `{$tableName}`");
        foreach ($configList as $configV) {
            $ret["indexes"][$configV["Key_name"]]["name"] = $configV["Key_name"];
            $ret["indexes"][$configV["Key_name"]]["type"] = $configV["Key_name"] == "PRIMARY" ? "primary" : ($configV["Non_unique"] == 0 ? "unique" : "index");
            $ret["indexes"][$configV["Key_name"]]["fields"][] = $configV["Column_name"];
        }
        //判断当前表是否需要更新内容
        $contentTableList = ['auth_rule'];//需要更新表内容的表
        $name = str_replace($completePrefix, '', $tableName);
        if (in_array($name, $contentTableList)) {
            $ret['content'] = (new Db())->name($name)->select();
        }


        return $ret;
    }

    /**
     * Comment: 获取完整的表名称
     * Author: zzw
     * Date: 2020/7/2 19:27
     * @param $table
     * @return string
     */
    protected function getTableName($table)
    {
        return $this->sqlData['prefix'] . $table;
    }

    /**
     * Comment: 生成表的创建语句
     * Author: zzw
     * Date: 2020/7/2 19:27
     * @param $schema
     * @return string
     */
    protected function createTable($schema)
    {
        if (empty($schema)) return '';
        $sql = "CREATE TABLE IF NOT EXISTS " . $this->getTableName($schema['table']) . " (";
        //生成表的字段
        foreach ($schema['fields'] as $field) {
            $sql .= $this->createTableField($field);
            $sql .= ",";
        }
        //生成表的索引
        foreach ($schema['indexes'] as $index) {
            $sql .= $this->createTableIndex($index);
            $sql .= ",";
        }
        $sql = rtrim($sql, ",");
        $charset = substr($schema['charset'], 0, stripos($schema['charset'], "_"));
        $sql .= ") ENGINE={$schema['engine']} AUTO_INCREMENT=1 DEFAULT CHARSET={$charset};";

        return $sql;
    }

    /**
     * Comment: 生成字段的SQL
     * Author: zzw
     * Date: 2020/7/2 19:28
     * @param       $tableName
     * @param       $field
     * @param int $type
     * @param array $idx_field
     * @return string
     */
    protected function tableFieldEdit($tableName, $field, $type = 1, $idx_field = [])
    {
        $sqlstr = ($type == 1) ? " ADD " : " MODIFY COLUMN ";
        $sql = "ALTER TABLE " . $this->getTableName($tableName) . $sqlstr . $this->createTableField($field);
        //特殊情况，增加主键字段时
        if ($type == 1 && $field['increment'] == 1) {
            $sql .= ", ADD " . $this->createTableIndex($idx_field, 'ADD');
        }
        $sql .= ";";
        return $sql;
    }

    /**
     * Comment: 生成操作索引的SQL
     * Author: zzw
     * Date: 2020/7/2 19:28
     * @param     $tableName
     * @param     $field
     * @param int $type
     * @return string
     */
    protected function tableIndexEdit($tableName, $field, $type = 1)
    {
        $sqlstr = ($type == 1) ? " ADD " : " DROP ";
        $sql = "ALTER TABLE " . $this->getTableName($tableName) . $sqlstr . $this->createTableIndex($field, trim($sqlstr)) . ";";
        return $sql;
    }

    /**
     * Comment: 生成表数据操作的sql
     * Author: zzw
     * Date: 2020/7/2 19:28
     * @param $table
     * @param $content
     * @return array
     */
    protected function tableContentSql($table, $content)
    {
        //获取完整的表名称
        $name = $this->getTableName($table);
        //循环处理数据
        $contentSql = [];
        foreach ($content as $contentV) {
            //新增内容
            $contentSql[] = (new Db())->name($name)->insertSql($contentV);
        }

        return $contentSql;
    }

    /**
     * Comment: 生成字段的SQL段
     * Author: zzw
     * Date: 2020/7/2 19:28
     * @param $field
     * @return string
     */
    protected function createTableField($field)
    {
        if (empty($field)) return "";
        $sql = "";
        $sql .= " `{$field['name']}` {$field['type']}";
        $sql .= !empty($field['length']) ? "({$field['length']})" : "";
        $sql .= !empty($field['signed']) ? "" : " UNSIGNED";
        $sql .= !empty($field['null']) ? (array_key_exists("default", $field) ? "" : " DEFAULT NULL") : " NOT NULL";
        $sql .= array_key_exists("default", $field) ? " DEFAULT '{$field['default']}'" : "";
        $sql .= !empty($field['increment']) ? " AUTO_INCREMENT" : "";
        $sql .= !empty($field['comment']) ? " COMMENT '{$field['comment']}'" : "";
        return $sql;
    }

    /**
     * Comment: 生成索引的SQL段
     * Author: zzw
     * Date: 2020/7/2 19:28
     * @param        $index
     * @param string $type
     * @return string
     */
    protected function createTableIndex($index, $type = 'ADD')
    {
        if (empty($index)) return "";
        $sql = "";
        $sql .= $index['type'] == 'primary' ? "PRIMARY KEY" : "KEY `{$index['name']}`";
        if ($type == 'ADD') $sql .= " (`" . implode("`,`", $index['fields']) . "`)";
        return $sql;
    }

    /**
     * Comment: 固定补充信息
     * Author: zzw
     * Date: 2020/7/2 19:36
     * @param $sqlCaChe
     * @return mixed
     */
    protected function otherInfo($sqlCaChe)
    {
        $prefix = $_REQUEST['data']['prefix'];
        $time = time();
        //超级管理角色信息
        $sqlCaChe['sql'][] = "INSERT INTO `{$prefix}users` VALUES (1, 1, 'admin', 'c80586c6559432bdcb31a8bd77420568b9fe494d', 'ijhFZo62', 2, 1451972357, '110.184.56.61', 1605171045, '172.19.0.1', '管理员', 1451972357, 0, 1, 0, 1, 0, '', 0, 0, '')";
        $sqlCaChe['sql'][] = <<<EOF
            INSERT INTO `{$prefix}modules` VALUES (1, 'weliam_smartcity', 'biz', '智慧城市同城V4', '1.0.0', '智慧城市同城V4', '智慧城市同城V4', '微连科技', 'http://bbs.we7.cc/', 0, 'a:1:{i:0;s:9:\"subscribe\";}', 'a:8:{i:0;s:4:\"text\";i:1;s:5:\"image\";i:2;s:5:\"voice\";i:3;s:8:\"location\";i:4;s:9:\"subscribe\";i:5;s:2:\"qr\";i:6;s:5:\"trace\";i:7;s:5:\"click\";}', 0, 0, 0, 0, 'a:0:{}', 'Z', 1, 1, 1, 1, 1, 1, 2, 1, 1, 'addons/weliam_smartcity/icon.jpg', 1, 1, 'local', 0, 1, 0);
EOF;

        return $sqlCaChe;
    }

}


class Db
{
    protected $link;

    /**
     * 初始化基本信息
     * Db constructor.
     */
    public function __construct()
    {
        $data = $_REQUEST['data'];
        //链接数据库
        $this->link = mysqli_connect($data['hostname'], $data['username'], $data['password'], $data['database']);
        mysqli_set_charset($this->link, 'UTF-8'); // 设置数据库字符集
    }

    /**
     * Comment: 执行指定的sql语句
     * Author: zzw
     * Date: 2020/7/2 16:33
     * @param $sql
     * @return bool
     */
    public function query($sql)
    {
        $res = mysqli_query($this->link, $sql);
        if (is_object($res)) {
            $newArr = [];
            foreach ($res as $values) {
                $newArr[] = $values;
            }

            return $newArr;
        }

        return $res;
    }

    /**
     * Comment: 生成完整的表名称
     * Author: zzw
     * Date: 2020/7/2 19:07
     * @param $name
     * @return $this
     */
    public function name($name)
    {
        $data = $_REQUEST['data'];
        $name = str_replace($data['prefix'] . "house_", '', $name);
        $table = "{$data['prefix']}house_{$name}";
        $this->tablename = $table;
        return $this;
    }

    /**
     * Comment: 获取所有数据信息
     * Author: zzw
     * Date: 2020/7/2 19:07
     * @return bool
     */
    public function select()
    {
        $sql = "SELECT * FROM {$this->tablename}";
        $list = $this->query($sql);

        return $list;
    }

    /**
     * Comment: 获取添加语句
     * Author: zzw
     * Date: 2020/7/2 19:25
     * @param $params
     * @return string
     */
    public function insertSql($params)
    {
        //信息添加语句
        $keys = array_keys($params);
        $values = array_values($params);
        $col = implode("`, `", $keys);
        $val = implode("', '", $values);
        $paramsSql = "(`{$col}`) VALUES ('{$val}')";
        //完整的添加语句
        $sql = "INSERT INTO {$this->tablename} {$paramsSql}";
        return $sql;
    }

}

/****** 安装流程 *****************************************************************************************************/
//判断是否授权
$authInfo = authInfo();
if (file_exists(ROOT_PATH . 'data/config.php')) {
    $config = call_user_func(function () {
        require ROOT_PATH . 'data/config.php';
        return $config['db']['master'] ?: $config['db'];
    });
} else {
    $config = ['host' => "127.0.0.1", 'username' => '', 'password' => '', 'database' => '', 'port' => '3306', 'tablepre' => 'wl_'];
}
//执行安装程序
if ($method) {
    //参数信息获取
    $data = $_REQUEST['data'];
    //调用安装类
    (new WeliamInstall($data))->$method();
}
?>
<!DOCTYPE html>
<html lang="zh-cn">
<head>
    <meta charset="utf-8">
    <title><?php echo $authInfo['name']; ?>安装</title>
    <link href="https://libs.baidu.com/bootstrap/3.0.3/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://cdn.bootcdn.net/ajax/libs/layer/3.1.1/theme/default/layer.min.css" rel="stylesheet">
    <script src="https://cdn.staticfile.org/jquery/2.1.4/jquery.min.js"></script>
    <script src="https://cdn.bootcdn.net/ajax/libs/layer/3.1.1/layer.min.js"></script>
</head>
<style>
    #installContent {
        width: 100vw;
        height: 100vh;
        background: url('https://house.weliam.cn/assets/img/default-bg.png') no-repeat;
        background-size: 100%;
        padding-top: 15%;
    }

    .container {
        max-width: 500px;
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        padding: 15px;
        background-color: #f5f5f5;
        border-radius: 10px;
    }

    #content {
        margin-top: 15px;
    }

    #progress {
        margin-bottom: 0;
        height: 15px;
        border-radius: 10px;
        -webkit-box-shadow: none;
        box-shadow: none;
        display: block;
        background: #CCC;
        width: 100%;
    }

    #progress .progress-bar {
        line-height: 15px;
        border-radius: 10px;
    }
    #tips{
        text-align: center;
    }
</style>
<body>
<div>
    <!--数据库配置信息-->
    <div id="installContent">
        <div class="container">
            <div id="content">
                <form class="form-horizontal nice-validator n-default n-bootstrap" onsubmit="return false;">
                    <h3 class="text-center" style="margin-bottom: 20px;">数据库配置</h3>
                    <!--数据库服务器地址-->
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-3">服务器地址:</label>
                        <div class="col-xs-12 col-sm-8">
                            <input class="form-control" name="hostname" type="text" placeholder="请输入服务器地址" value="<?php echo $config['host']; ?>">
                        </div>
                    </div>
                    <!--数据库名-->
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-3">数据库名:</label>
                        <div class="col-xs-12 col-sm-8">
                            <input class="form-control" name="database" type="text" placeholder="请输入数据库名" value="<?php echo $config['database']; ?>">
                        </div>
                    </div>
                    <!--用户名-->
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-3">用户名:</label>
                        <div class="col-xs-12 col-sm-8">
                            <input class="form-control" name="username" type="text" placeholder="请输入用户名" value="<?php echo $config['username']; ?>">
                        </div>
                    </div>
                    <!--密码-->
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-3">密码:</label>
                        <div class="col-xs-12 col-sm-8">
                            <input class="form-control" name="password" type="text" placeholder="请输入密码" value="<?php echo $config['password']; ?>">
                        </div>
                    </div>
                    <!--数据库表前缀-->
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-3">表前缀:</label>
                        <div class="col-xs-12 col-sm-8">
                            <input class="form-control" name="prefix" type="text" placeholder="请输入表前缀" value="<?php echo $config['tablepre']; ?>">
                        </div>
                    </div>
                    <!--端口-->
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-3">端口:</label>
                        <div class="col-xs-12 col-sm-8">
                            <input class="form-control" name="hostport" type="text" value="<?php echo $config['port']; ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-3"></label>
                        <div class="col-xs-12 col-sm-8">
                            <button type="button" class="btn btn-success btn-embossed" id="submitDbInfo">立即安装</button>
                            <button type="button" class="btn btn-success btn-embossed" id="continueUpgrade"
                                    style="display: none" onclick="upgrade()">继续安装
                            </button>
                            <button type="button" class="btn btn-success btn-embossed" id="continueUpgradeDb"
                                    style="display: none" onclick="upgradeDb()">继续安装
                            </button>
                        </div>
                    </div>
                    <div id="tips" style="display: none">
                        <span id="upgradeDesc">正在下载文件信息，请稍等....</span>
                        <span id="progress">
                            <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    let filename = "<?php echo $php_self?>";
    function checkData() {
        //参数信息获取
        let hostname, database, username, password, prefix, hostport;
        if (!(hostname = $("[name='hostname']").val())) {
            layer.alert("请输入服务器地址");
            return false;
        }
        if (!(database = $("[name='database']").val())) {
            layer.alert("请输入数据库名");
            return false;
        }
        if (!(username = $("[name='username']").val())) {
            layer.alert("请输入用户名");
            return false;
        }
        if (!(password = $("[name='password']").val())) {
            layer.alert("请输入密码");
            return false;
        }
        if (!(prefix = $("[name='prefix']").val())) {
            layer.alert("请输入表前缀");
            return false;
        }
        if (!(hostport = $("[name='hostport']").val())) {
            layer.alert("请输入表前缀");
            return false;
        }
        //信息提交
        let data = {
            hostname: hostname,
            database: database,
            username: username,
            password: password,
            prefix: prefix,
            hostport: hostport,
        };

        return data;
    }

    $('#submitDbInfo').on('click', function () {
        let data = checkData();
        if (data == false) {
            return false;
        }
        var index = layer.load(3, {
            shade: [0.4, '#000000']
        });
        $.post(filename + '?method=initStall', {data: data}, function (data) {
            layer.closeAll();
            if (data.code == 0) {
                layer.alert(data.msg);
            } else {
                $('#submitDbInfo').toggle();
                $('#tips').toggle();
                upgrade();
            }
        }, 'json');
    });

    /**
     * 文件更新
     */
    function upgrade() {
        $.post(filename + '?method=upgradeFile', function (data) {
            $('#continueUpgrade').css({"display": 'none'});
            if (data.code == 1) {
                if (data.type == 1) {
                    let progress = data['progress'] + '%';
                    $('.progress-bar').css({"width": progress});
                    $('.progress-bar').html(progress);
                    upgrade();
                } else {
                    //文件更新完成  开始更新数据库
                    $('.progress-bar').css({"width": '0%'});
                    $('.progress-bar').html('0%');
                    $('#upgradeDesc').html('正在同步数据库信息，请稍等....');
                    upgradeDb();
                }
            } else {
                $('#continueUpgrade').toggle();
                layer.alert(data.msg);
            }
        }, 'json');
    }

    /**
     * 数据库更新
     */
    function upgradeDb() {
        let data = checkData();
        if (data == false) {
            return false;
        }
        $.post(filename + '?method=upgradeDb', {data: data}, function (data) {
            $('#continueUpgradeDb').css({"display": 'none'});
            if (data.code == 1) {
                if (data.type == 1) {
                    let progress = data['progress'] + '%';
                    $('.progress-bar').css({"width": progress});
                    $('.progress-bar').html(progress);
                    upgradeDb();
                } else {
                    let progress = '100%';
                    $('.progress-bar').css({"width": progress});
                    $('.progress-bar').html(progress);
                    //文件更新完成  开始更新数据库
                    $('#submitDbInfo').toggle();
                    $('#tips').toggle();
                    layer.alert("安装成功！", function(index) {
                        location.href = window.location.protocol + "//" + window.location.host;
                    });
                }
            } else {
                $('#continueUpgradeDb').toggle();
                layer.alert(data.msg);
            }
        }, 'json');
    }
</script>
</body>
</html>