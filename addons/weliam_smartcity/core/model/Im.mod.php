<?php
defined('IN_IA') or exit('Access Denied');

/**
 * Comment: 通讯信息操作模型
 * Author: zzw
 * Class Im
 */
class Im {
    protected static $table = PDO_NAME."im";
    /**
     * Comment: 获取通讯分组列表
     * Author: zzw
     * Date: 2019/9/28 9:44
     * @param $id           int     信息接收人id
     * @param $type         int     信息接收人类型（1=用户；2=商户）
     * @param $page         int     当前页；默认1
     * @param $pageIndex    int     每页的数量；默认10
     * @return mixed        array
     */
    public static function myList($id,$type,$page = 1,$pageIndex = 10,$plugin = ''){
        global $_W;
        #1、sql语句生成
        $where = " (receive_id = {$id} AND `receive_type` = {$type}) OR (`send_id` = {$id} AND `send_type` = {$type}) ";
        if($plugin) $where .= " AND plugin = {$plugin} ";
        else $where .= " AND (plugin = '' OR plugin IS NULL)";
        $sql = "SELECT max(id) as id,receive_type,plugin,send_type,FROM_UNIXTIME(max(create_time),'%Y-%m-%d %H:%i:%S') as date_time,
        CASE WHEN receive_id = {$id} AND `receive_type` = {$type} THEN `send_id` 
             ELSE `receive_id`
        END as other_party_id,
        CASE WHEN receive_id = {$id} AND `receive_type` = {$type} THEN `send_type`  
             ELSE `receive_type` 
        END as other_party_type,
        (SELECT COUNT(*) FROM ".tablename(PDO_NAME.'im')." as t 
         WHERE t.`send_id` = other_party_id AND t.`send_type` = other_party_type 
         AND t.`receive_id` = {$id} AND t.`receive_type` = {$type} AND t.`is_read` = 0) as total,
         CONCAT_WS('_',
            CASE WHEN receive_id = {$id} AND `receive_type` = {$type} THEN `send_id` 
                 ELSE `receive_id`
            END 
            ,
            CASE WHEN receive_id = {$id} AND `receive_type` = {$type} THEN `send_type`  
                ELSE `receive_type` 
            END
         ) as group_name 
         FROM ".tablename(PDO_NAME.'im')." 
         WHERE {$where} GROUP BY group_name ORDER BY date_time DESC ";
        #2、总数获取
        $list = pdo_fetchall($sql) ? : [];
        $data['total'] = ceil(count($list) / $pageIndex);
        #3、分页操作
        $startPage = $page * $pageIndex - $pageIndex;
        $data['list'] = array_slice($list,$startPage,$pageIndex);
        #4、循环处理数据信息
        foreach($data['list'] as $key => &$val){
            //获取最新的消息信息
            $val['content'] = pdo_getcolumn(PDO_NAME."im" ,['id'=>$val['id']],'content');
            $val['content'] = self::base64Processing($val['content']);
            $val['type'] = pdo_getcolumn(PDO_NAME."im" ,['id'=>$val['id']],'type');
            //获取聊天对象信息
            if($val['other_party_type'] == 1){
                //聊天对象：用户
                $info = pdo_get(PDO_NAME . "member" , [ 'id' => $val['other_party_id'] ] , [ 'nickname' , 'avatar' ]);
                $val['nickname'] = $info['nickname'];
                $val['avatar']   = $info['avatar'];
            } else {
                //聊天对象：商户
                $info = pdo_get(PDO_NAME."merchantdata",['id'=>$val['other_party_id']],['storename','logo']);
                $val['nickname'] = $info['storename'];
                $val['avatar']   = tomedia($info['logo']);
            }
            //删除多余的数据信息
            unset($val['receive_type']);
            unset($val['group_name']);
        }

        return $data;
    }
    /**
     * Comment: 保存通讯信息
     * Author: zzw
     * Date: 2019/8/26 15:45
     * @param array $data
     * @return bool
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    public static function insert(array $data){
        $endTime = self::caCheImInfo($data,2);
        if($endTime < time()){
            //通讯时间节点过期  当前发送信息用户未收到
            self::sendImModelInfo($data);
        }
        return pdo_insert(self::$table,$data);
    }
    /**
     * Comment: 获取通讯记录
     * Author: zzw
     * Date: 2019/8/26 17:34
     * @param $page
     * @param $pageIndex
     * @param $thisId
     * @param $thisType
     * @param $otherPartyType
     * @param $otherPartyId
     * @return mixed
     */
    public static function imRecord($page,$pageIndex,$sendInfo){
        global $_W,$_GPC;
        $plugin = $_GPC['plugin'] ? : '';
        #1、缓存通讯时间节点
        self::caCheImInfo($sendInfo);
        #1、分页
        $pageStart = $page * $pageIndex - $pageIndex;
        #2、条件生成
        $where = " as a WHERE a.uniacid = {$_W['uniacid']} AND 
        (
          (a.send_id = {$sendInfo['send_id']} AND a.send_type = {$sendInfo['send_type']} AND a.receive_id = {$sendInfo['receive_id']} AND a.receive_type = {$sendInfo['receive_type']})
          OR
          (a.send_id = {$sendInfo['receive_id']} AND a.send_type = {$sendInfo['receive_type']} AND a.receive_id = {$sendInfo['send_id']} AND a.receive_type = {$sendInfo['send_type']})
        )";
        if($plugin) $where .= " AND a.plugin = {$plugin} ";
        else $where .= " AND (a.plugin = '' OR a.plugin IS NULL)";
        #3、要查询的字段生成
        $field = " a.id,a.send_id,a.`receive_id`,a.content,a.plugin,a.create_time,a.type,FROM_UNIXTIME(create_time,'%Y-%m-%d %H:%i:%S') as date_time,
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
        $totalSql = "SELECT COUNT(*) FROM ".tablename(self::$table).$where;
        $total = pdo_fetchcolumn($totalSql);
        $data['total'] = ceil($total / $pageIndex);
        #5、获取当前页列表信息
        $sql = "SELECT {$field} FROM ".tablename(self::$table).$where
            ." ORDER BY a.create_time DESC LIMIT {$pageStart},{$pageIndex} ";
        $data['list'] = pdo_fetchall($sql);
        foreach($data['list'] as $key => &$val) {
            $val['avatar'] = tomedia($val['avatar']);
            $val['content'] = self::base64Processing($val['content']);
        }

        return $data;
    }
    /**
     * Comment: 修改数据
     * Author: zzw
     * Date: 2019/8/27 9:07
     * @param $data
     * @param $where
     * @return bool
     */
    public static function is_read($ids,$thisId = 0){
        global $_W;
        #1、条件生成
        if(!empty($thisId)){
            $where = " receive_id = {$thisId} ";
        }else{
            $where = " receive_id = {$_W['mid']} ";
        }
        if(count($ids) > 1){
            $idStr = implode(',',$ids);
            $where .= " AND id IN ({$idStr}) ";
        }else{
            $where .= " AND id = {$ids[0]} ";
        }
        #2、修改内容
        return pdo_query(" UPDATE ".tablename(self::$table)." SET is_read = 1 WHERE ".$where);
    }
    /**
     * Comment: 通讯时间节点信息
     * Author: zzw
     * Date: 2019/11/26 11:11
     * @param array     $data
     * @param int       $status  1=set;2=get
     * @return array|bool|false|Memcache|mixed|Redis|string|true
     */
    protected static function caCheImInfo($data,$status = 1){
        # 思路：
        # 储存：时按照正常参数 储存通讯时间节点信息
        # 获取：时所有参数调换 即发送信息变更为接收方信息 接收方信息变更为发送方信息
        #1、基本信息生成
        $caCheKey = 'im_info';//缓存名称
        $statusTime = 15;//聊天状态保存时间，改时间内未获取请求信息 则未进入聊天状态
        #2、根据操作类型进行对应的操作
        if($status == 1){
            //储存当前请求用户的通讯时间节点信息
            $caCheName = md5('id'.$data['send_id'].'type'.$data['send_type'].'other_id'.$data['receive_id'].'other_type'.$data['receive_type']);
            $endTime = intval(time() + $statusTime);
            $res = Cache::setCache($caCheKey,$caCheName,$endTime);
        }else{
            $caCheName = md5('id'.$data['receive_id'].'type'.$data['receive_type'].'other_id'.$data['send_id'].'other_type'.$data['send_type']);
            $res = Cache::getCache($caCheKey,$caCheName);
        }
        return intval($res);
    }
    /**
     * Comment: 发送通讯消息提醒模板
     * Author: zzw
     * Date: 2019/11/26 14:09
     * @param $data
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected static function sendImModelInfo($data){
        global $_W;
        # 思路：判断接收消息的用户是否在聊天室中，所以以接收方信息为主 判断接收方类型 获取接收模板消息的用户的openid
        # $data['send_id']          发送方id
        # $data['send_type']        发送方类型（1=用户；2=商户）
        # $data['receive_id']       接收人id
        # $data['receive_type']     接收人类型（1=用户；2=商户）
        #1、获取发送方信息
        if($data['send_type'] == 1){
            //消息发送方为用户
            $sendUser = pdo_get(PDO_NAME."member",['id'=>$data['send_id']],['nickname']);
            $first = '您好,用户[' . $sendUser['nickname'] . ']向您发送了通讯消息';
        }else{
            //消息发送方为商户
            $sendShop = pdo_get(PDO_NAME."merchantdata",['id'=>$data['send_id']],['storename']);
            $first = '您好,商户[' . $sendShop['storename'] . ']向您发送了通讯消息';
        }
        if(empty($data['type'])){
            $content = '文本消息';
        }else if($data['type'] == 1){
            $content = '图片消息';
        }else if($data['type'] == 2){
            $content = '视频消息';
        }else if($data['type'] == 3){
            $content = '个人名片';
        }else if($data['type'] == 4){
            $content = '个人简历';
        }

        #1、生成模板配置信息
        $modelData = [
            'first'   => $first,
            'type'    => '消息提醒',//业务类型
            'content' => $content,//业务内容
            'status'  => '未读' ,//处理结果
            'time'    => date("Y-m-d H:i:s",$data['create_time']) ,//操作时间
            'remark'  => '请尽快进行回复'
        ];
        #1、生成链接信息
        $link = h5_url('pagesA/instantMessenger/instantMessenger' , [
            'id' => $data['receive_id'] ,
            'type' => $data['receive_type'],
            'other_party_id' => $data['send_id'],
            'other_party_type' => $data['send_type']
        ]);
        #2、根据消息接收方类型 分别发送模板消息
        if($data['receive_type'] == 1){
            //接收消息方为用户时
            $res = TempModel::sendInit('service',$data['receive_id'] ,$modelData,$_W['source'],$link);
        }else{
            //接收消息方为商户时
            $list = pdo_fetchall("SELECT id,mid FROM ".tablename(PDO_NAME."merchantuser")
                ." WHERE storeid = {$data['receive_id']} AND enabled = 1 AND ismain IN (1,3)");
            if(is_array($list) && count($list) > 0){
                foreach($list as $key => &$val){
                    TempModel::sendInit('service',$val['mid'],$modelData,$_W['source'],$link);
                }
            }
        }
    }
    /**
     * Comment: 清除某个对话的所有通信记录
     * Author: zzw
     * Date: 2020/9/10 11:25
     * @param $sendId
     * @param $receiveId
     * @return bool|mixed
     */
    public function deleteRecord($sendId,$receiveId){
        //条件生成
        $where  = " WHERE (send_id = {$sendId} && receive_id = {$receiveId}) OR (send_id = {$receiveId} && receive_id = {$sendId})";
        //删除操作
        $sql = "DELETE FROM ".tablename(self::$table).$where;

        return pdo_query($sql);
    }
    /**
     * Comment: 通讯内容信息处理(base64处理)
     * Author: zzw
     * Date: 2021/4/6 16:13
     * @param string $content
     * @return false|mixed|string
     */
    protected static function base64Processing($content){
        if(is_base64($content) && json_encode(base64_decode($content,true),JSON_UNESCAPED_UNICODE)) {
            $content = base64_decode($content,true);
        }

        return $content;
    }

    /**
     * Comment: 自定义表单信息数据合成
     * Author: wlf
     * Date: 2022/09/29 17:29
     * @param string $content
     * @return false|mixed|string
     */

    public function diyFormInfo($list,$nowlist){
        if(!empty($nowlist)){
            $moreinfo = unserialize($nowlist['listinfo']);
            if(!empty($moreinfo)){
                foreach($list as $key => &$ccinfo){
                    foreach($moreinfo as $mminfo){
                        if($mminfo['id'] == 'img'){
                            foreach ($mminfo['data'] as &$img){
                                $img = tomedia($img);
                            }
                        }
                        if($ccinfo['data']['title'] == $mminfo['title']){
                            $ccinfo['data']['value'] = $mminfo['data'];
                        }
                    }
                }
                $data['listid'] = $nowlist['id'];
            }else{
                foreach($list as $key => &$ccinfo){
                    if($ccinfo['id'] == 'city'){
                        $ccinfo['data']['value'][] = '';
                    }else{
                        $ccinfo['data']['value'] = '';
                    }
                }
            }
        }else{
            foreach($list as $key => &$ccinfo){
                if($ccinfo['id'] == 'city'){
                    $ccinfo['data']['value'][] = '';
                }else{
                    $ccinfo['data']['value'] = '';
                }
            }
        }
        return $list;
    }

}
