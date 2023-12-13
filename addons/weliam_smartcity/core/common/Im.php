<?php
define('IN_UNIAPP',true);
error_reporting(0);
header('Access-Control-Allow-Origin:*');
require '../../../../framework/bootstrap.inc.php';
require '../../../../addons/'.MODULE_NAME.'/core/common/defines.php';
require PATH_MODULE."/vendor/autoload.php";
require PATH_CORE."common/autoload.php";
Func_loader::core('global');
global $_W,$_GPC,$db,$socket;
load()->model('attachment');
//获取基本设置信息
$set = pdo_get(PDO_NAME.'setting',['key' => 'im_set'],['value']);
if (is_array($set)) $set = iunserializer($set['value']);
$port = $set['port'];
$cert = $set['pem_path'];
$key  = $set['key_path'];
//$cert = 'D:/Software/phpstudy_pro/Extensions/Nginx1.15.11/conf/ssl/www.locatiom.smartcity.com.pem';
//$key  = 'D:/Software/phpstudy_pro/Extensions/Nginx1.15.11/conf/ssl/www.locatiom.smartcity.com.key';

//开始进行通讯处理
use Workerman\Worker;
require_once PATH_VENDOR.'workerman/workerman/Autoloader.php';
//创建一个Worker监听端口，使用websocket协议通讯
$context = [
    // 更多ssl选项请参考手册 http://php.net/manual/zh/context.ssl.php
    'ssl' => [
        'local_cert'  => $cert,
        'local_pk'    => $key,
        'verify_peer' => false,
        // 'allow_self_signed' => true, //如果是自签名证书需要开启此选项
    ]
];
$socket            = new Worker("websocket://0.0.0.0:{$port}",$context);
$socket->transport = 'ssl';
//$socket->count = 4;//启动x个进程对外提供服务
$socket->onWorkerStart = function($worker) {
    // 将db实例存储在全局变量中(也可以存储在某类的静态成员中)
    global $db;
    require '../../../../data/config.php';
    $master = $config['db']['master'];
    $db = new \Workerman\MySQL\Connection($master['host'],$master['port'], $master['username'], $master['password'],$master['database']);
};
//接收数据并且进行对应的处理
$socket->onMessage = function ($connection,$data) {
    //处理信息
    (new handle($data,$connection))->handleData();
};
//当某个链接断开时触发
$socket->onClose = function ($connection) {


};
//方法类
class handle{
    private $data,//当前接收的消息
        $sendId,//发送方唯一id
        $receiveId,//接收方唯一id
        $idsFile = PATH_CORE."common/im_ids.php";

    public function __construct($data,$connection){
        global $_W;
        //data信息处理
        $data = json_decode($data,true);
        $_W['uniacid'] = $data['i'] ? : $data['uniacid'];//公众号id
        $_W['aid']     = $data['aid'];//代理商id
        $_W['source']  = $data['source'];//渠道
        //通讯信息获取
        $data['send_id']    = intval($data['send_id']) ? : $_W['mid'];//当前发送信息的用户的id（商户id）
        $data['receive_id'] = intval($data['receive_id']);//接受消息的用户id(商户id)
        $data['page']       = intval($data['page']) ? : 1;//页码
        $data['page_index'] = intval($data['page_index']) ? : 10;//每页的数量
        $this->sendId = $data['send_id'].'_'.$data['receive_id'];
        $this->receiveId = $data['receive_id'].'_'.$data['send_id'];
        $this->data = $data;
        //通讯节点处理
        $this->setIds($this->sendId,$connection->id);
    }
    /**
     * Comment: 处理接收的data信息
     * Author: zzw
     * Date: 2021/3/8 11:44
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     */
    public function handleData(){
        global $_W,$db;
        //接受信息
        $data = $this->data;
        //判断是否给自己发送消息
        if($data['send_id'] == $data['receive_id'] && $data['send_type'] == $data['receive_type']) $this->socketSend($this->sendId,"不能给自己发送信息哦",5);
        //判断通讯类型,进行对应的操作。信息类型：1=系统信息，2=通信信息，3=断开链接，4=通讯记录,5=错误抛出
        if($data['im_type'] == 2){
            //记录信息到表
            $newData['uniacid']      = $_W['uniacid'];
            $newData['send_id']      = $data['send_id'];//发送方id
            $newData['send_type']    = $data['send_type'];//发送方类型（1=用户；2=商户）
            $newData['receive_id']   = $data['receive_id'];//接收人id
            $newData['receive_type'] = $data['receive_type'];//接收人类型（1=用户；2=商户）
            $newData['create_time']  = time();//发送时间（建立时间）
            $newData['type']         = $data['type'];//内容类型:0=文本内容（可带表情），1=图片信息（不可带表情），2=视频信息（不可带表情），3=名片信息（不可带表情），4=简历信息（不可带表情），5=其他信息（不可带表情）
            $newData['content']      = base64_encode($data['content']);//发送内容
            $newData['plugin']       = $data['plugin'];//发送内容
            $table = trim(tablename(PDO_NAME."im"),'`');
            $db->insert($table)->cols($newData)->query();
            //判断接收方是否在线  在线进行推送
            $memberInfo = $db->select('nickname,avatar')
                ->from(trim(tablename(PDO_NAME."member"),'`'))
                ->where("id={$newData['send_id']}")
                ->row();
            $sendInfo = [
                'avatar'      => tomedia($memberInfo['avatar']),
                'content'     => $data['content'],
                'create_time' => $newData['create_time'],
                'date_time'   => date('Y-m-d H:i:s',$newData['create_time']),
                'is_my'       => 1,//0=自己发送的消息，1=对方发送的消息
                'nickname'    => $memberInfo['nickname'],
                'receive_id'  => $newData['receive_id'],
                'send_id'     => $newData['send_id'],
                'type'        => $newData['type']
            ];

            $this->socketSend($this->receiveId,'即时通讯信息',2,$data['type'],['list'=>[$sendInfo]]);
        }else if ($data['im_type'] == 3){
            //断开链接
            //$this->log(['断开',$data]);
            $this->deleteIds($this->sendId);
        }else if($data['im_type'] == 4){
            //获取通讯记录信息
            $sendInfo['send_id']      = $data['send_id'] ;//发送方id
            $sendInfo['send_type']    = $data['send_type'] ;//发送方类型（1=用户；2=商户）
            $sendInfo['receive_id']   = $data['receive_id'] ;//接收人id
            $sendInfo['receive_type'] = $data['receive_type'] ;//接收人类型（1=用户；2=商户）
            //$list = Im::imRecord($data['page'],$data['page_index'],$sendInfo);
            $pageStart = $data['page'] * $data['page_index'] - $data['page_index'];
            //条件生成
            $where = " as a WHERE a.uniacid = {$_W['uniacid']} AND 
        (
          (a.send_id = {$sendInfo['send_id']} AND a.send_type = {$sendInfo['send_type']} AND a.receive_id = {$sendInfo['receive_id']} AND a.receive_type = {$sendInfo['receive_type']})
          OR
          (a.send_id = {$sendInfo['receive_id']} AND a.send_type = {$sendInfo['receive_type']} AND a.receive_id = {$sendInfo['send_id']} AND a.receive_type = {$sendInfo['send_type']})
        )";
            if($data['plugin']) $where .= " AND a.plugin = {$data['plugin']} ";
            else $where .= " AND (a.plugin = '' OR a.plugin IS NULL)";
            //要查询的字段生成
            $field = " a.id,a.send_id,a.`receive_id`,a.content,a.create_time,a.type,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%S') as date_time,
                    CASE {$sendInfo['receive_id']} WHEN a.send_id THEN 1
                        ELSE 0
                    END as is_my,
                    CASE a.`send_type` 
                         WHEN 1 THEN (SELECT `nickname`  FROM ".tablename(PDO_NAME."member")." WHERE `id` = a.`send_id`)
                         ELSE (SELECT storename  FROM ".tablename(PDO_NAME."merchantdata")." WHERE `id` = a.`send_id` )
                    END as nickname,
                    CASE a.`send_type` 
                         WHEN 1 THEN (SELECT `avatar`  FROM ".tablename(PDO_NAME."member")." WHERE `id` = a.`send_id`)
                         ELSE (SELECT logo  FROM ".tablename(PDO_NAME."merchantdata")." WHERE `id` = a.`send_id` )
                    END as avatar";
            #4、获取总页数
            $totalSql = "SELECT COUNT(*) as total FROM ".tablename(PDO_NAME."im").$where;
            $total = $db->single($totalSql);
            $list['total'] = ceil($total / $data['page_index']);
            #5、获取当前页列表信息
            $listSql = "SELECT {$field} FROM ".tablename(PDO_NAME."im").$where
                ." ORDER BY a.create_time DESC LIMIT {$pageStart},{$data['page_index']} ";
            $list['list'] = $db->query($listSql);
            foreach($list['list'] as $key => &$val) {
                $val['avatar'] = tomedia($val['avatar']);
                if(is_base64($val['content'])) $val['content'] = base64_decode($val['content'],true);
            }
            //修改信息为已读
            /*if(is_array($list['list']) && count($list['list']) > 0) {
                //条件生成
                $ids = array_column($list['list'],'id');
                if(!empty($sendInfo['send_id'])) $where = " receive_id = {$sendInfo['send_id']} ";
                else $where = " receive_id = {$_W['mid']} ";
                if(count($ids) > 1){
                    $idStr = implode(',',$ids);
                    $where .= " AND id IN ({$idStr}) ";
                }else{
                    $where .= " AND id = {$ids[0]} ";
                }
                #2、修改内容
                $updateSql = "UPDATE ".tablename(PDO_NAME."im")." SET is_read = 1 WHERE ".$where;
                $db->query($updateSql);
            }*/
            $where = " send_id = {$sendInfo['receive_id']} AND send_type = {$sendInfo['receive_type']} AND receive_id = {$sendInfo['send_id']} AND receive_type = {$sendInfo['send_type']} AND is_read = 0 ";
            $sql = " UPDATE ".tablename(PDO_NAME."im")." SET is_read = 1 WHERE {$where}  ";
            $db->query($sql);
            //信息排序
            $sortArr = array_column($list['list'], 'create_time');
            array_multisort($sortArr, SORT_ASC, $list['list']);
            //获取聊天对象的昵称  1=用户；2=商户
            if($sendInfo['receive_type'] == 1) {
                $nicknameSql = "SELECT nickname FROM ".tablename(PDO_NAME."member")." WHERE id = {$sendInfo['receive_id']} ";
                $list['receive_name'] = $db->single($nicknameSql);
            } else {
                $nicknameSql = "SELECT storename FROM ".tablename(PDO_NAME."merchantdata")." WHERE id = {$sendInfo['receive_id']} ";
                $list['receive_name'] = $db->single($nicknameSql);
            }
            $this->socketSend($this->sendId,'通讯记录',4,5,$list);
        }
    }
    /**
     * Comment: 发送数据给移动端
     * Author: zzw
     * Date: 2021/3/8 11:44
     * @param       $id
     * @param       $content
     * @param int   $imType
     * @param int   $type
     * @param array $other
     */
    public function socketSend($id,$content,$imType = 2,$type = 0,$other = []){
        global $socket;
        //生成需要发送的信息
        $data = [
            'im_type'   => $imType,//信息类型：1=系统信息，2=通信信息，3=断开链接，4=通讯记录,5=错误抛出
            //具体通讯信息
            'type'    => $type,//内容类型:0=文本内容（可带表情），1=图片信息（不可带表情），2=视频信息（不可带表情），3=名片信息（不可带表情），4=简历信息（不可带表情）,5=其他信息(数组)
            'content' => $content,//文本内容
            'other'   => $other,//其他信息（名片信息|简历信息）
        ];
        //发送信息
        $connectionId = $this->getIds($id);
        if($connectionId > 0){
            $connection = $socket->connections[$connectionId];

            if(is_object($connection)) $connection->send(json_encode($data,JSON_UNESCAPED_UNICODE));
        }
    }
    /**
     * Comment: 日志记录
     * Author: zzw
     * Date: 2021/3/8 11:44
     * @param        $content
     * @param string $title
     */
    public function log($content,$title = '日志'){
        Util::wl_log('socket',PATH_MODULE . "log/",$content,$title,false);
    }
    /**
     * Comment: 获取ids数组信息
     * Author: zzw
     * Date: 2021/3/12 10:21
     * @param int $key      发送者id
     * @return array|bool|Memcache|mixed|Redis|string
     */
    public function getIds($id = 0){
        //获取文件读写权限
        if(!is_readable($this->idsFile)) chmod($this->idsFile,0755);
        //获取文件内容
        $data = file_get_contents($this->idsFile);
        $data = json_decode($data,true);
        if($id > 0) return $data[$id];
        else return $data;
    }
    /**
     * Comment: 设置id缓存
     * Author: zzw
     * Date: 2021/3/12 10:21
     * @param int $key  发送者id
     * @param int $val  通讯节点id
     */
    public function setIds($key,$val){
        header('Content-Type:text/html;charset=utf-8');
        //数组转字符串
        $data = $this->getIds();
        $data[$key] = $val;
        $idStr = json_encode($data,JSON_UNESCAPED_UNICODE);
        file_put_contents($this->idsFile, print_r($idStr, true));
    }
    /**
     * Comment: 删除id缓存
     * Author: zzw
     * Date: 2021/3/18 11:13
     * @param $id
     */
    public function deleteIds($id){
        header('Content-Type:text/html;charset=utf-8');
        //数组转字符串
        $data = $this->getIds();
        unset($data[$id]);
        $idStr = json_encode($data,JSON_UNESCAPED_UNICODE);
        file_put_contents($this->idsFile, print_r($idStr, true));
    }




}
//运行
Worker::runAll();
