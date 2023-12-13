<?php
defined('IN_IA') or exit('Access Denied');

class UserIm_WeliamController {
    /**
     * Comment: 获取通讯信息列表
     * Author: zzw
     * Date: 2020/9/9 14:34
     */
    public function index(){
        global $_W,$_GPC;
        //参数信息获取
        $page = max(1, intval($_GPC['page']));
        $pageIndex = intval($_GPC['page_index'] ? : 10);
        $pageStart = $page * $pageIndex - $pageIndex;
        $sendType = $_GPC['send_type'] ? : 1;//发送方类型 1=用户；2=商户
        $receiveType = $_GPC['receive_type'] ? : 1;//接收人类型 1=用户；2=商户
        $searchType = $_GPC['search_type'] ? : 0;//搜索类型：1=发送方昵称，2=接收方昵称，3=通信内容
        $search = $_GPC['search'] ? : '';//搜索内容

        $userTable = tablename(PDO_NAME."member");
        $shopTable = tablename(PDO_NAME."merchantdata");
        //生成查询相关信息
        $order = " ORDER BY i.create_time DESC";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        //条件生成
        $where = " WHERE i.uniacid = {$_W['uniacid']} AND i.send_type = {$sendType} AND i.receive_type = {$receiveType} ";//条件生成
        if($search){
            switch ($searchType) {
                case 1:
                    if ($sendType == 1) $where .= " AND send_m.nickname LIKE '%{$search}%' ";
                    else $where .= " AND send_s.storename LIKE '%{$search}%' ";
                    break;
                case 2:
                    if ($sendType == 1) $where .= " AND receive_m.nickname LIKE '%{$search}%' ";
                    else $where .= " AND receive_s.storename LIKE '%{$search}%' ";
                    break;
                case 3:
                    $where .= " AND i.content LIKE '%{$search}%' ";
                    break;
            }
        }
        //生成需要获取的字段
        $field = "i.id,i.send_type,i.send_id,i.receive_type,i.receive_id,i.type,i.content,";
        if($sendType == 1) $field .= "send_m.nickname as send_user,send_m.avatar as send_logo,";//用户发送
        else $field .= "send_s.storename as send_user,send_s.logo as send_logo,";//商户发送
        if($receiveType == 1) $field .= "receive_m.nickname as receive_user,receive_m.avatar as receive_logo ";//用户接收
        else $field .= "receive_s.storename as receive_user,receive_s.logo as receive_logo  ";//商户接收
        //生成sql语句
        $sql = " SELECT {$field} FROM ".tablename(PDO_NAME."im")." as i ";
        if($sendType == 1) $sql .= " LEFT JOIN ".$userTable." send_m ON i.send_id = send_m.id AND i.send_type = 1 ";//用户发送
        else $sql .= " LEFT JOIN ".$shopTable." send_s ON i.send_id = send_s.id AND i.send_type = 2 ";//商户发送
        if($receiveType == 1) $sql .= " LEFT JOIN ".$userTable." receive_m ON i.receive_id = receive_m.id AND i.receive_type = 1 ";//用户接收
        else $sql .= " LEFT JOIN ".$shopTable." receive_s ON i.receive_id = receive_s.id AND i.receive_type = 2 ";//商户接收
        //信息列表获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            //发送方类型1=用户；2=商户
            if($val['send_type'] == 1) $val['send_type_text'] = '用户';
            else if($val['send_type'] == 2) $val['send_type_text'] = '商户';
            //接收人类型1=用户；2=商户
            if($val['receive_type'] == 1) $val['receive_type_text'] = '用户';
            else if($val['receive_type'] == 2) $val['receive_type_text'] = '商户';
            //图片信息处理
            $val['send_logo'] = tomedia($val['send_logo']);
            $val['receive_logo'] = tomedia($val['receive_logo']);
            $val['new_send_user'] = $val['send_user'];
            //信息处理
            if(is_base64($val['content'])) {
                $val['content'] = base64_decode($val['content'],true);
                $val['content'] = strip_tags($val['content']);
            }
            switch ($val['type']){
                case 1: $val['content'] = '[图片]';break;
                case 2: $val['content'] = '[视频]';break;
                case 3: $val['content'] = '[名片]';break;
                case 4: $val['content'] = '[简历]';break;
                case 5: $val['content'] = '[其他]';break;
            }
            //删除多余的信息
            unset($val['send_type']);
            unset($val['receive_type']);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where.$order);
        $pager =  pagination($total , $page , $pageIndex);

        include wl_template('member/im/index');
    }
    /**
     * Comment: 获取通讯记录详细信息
     * Author: zzw
     * Date: 2020/9/10 10:00
     */
    public function record(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR show_json(0, '参数错误，请刷新重试！');
        $page = max(1, intval($_GPC['page']));
        $pageIndex = 10;
        if($_W['ispost']){
            //获取当前通讯记录信息
            $info = pdo_get(PDO_NAME."im",['id'=>$id],['send_id','send_type','receive_id','receive_type']);
            $data = Im::imRecord($page,$pageIndex,$info);
            //信息排序
            $sortArr = array_column($data['list'], 'create_time');
            array_multisort($sortArr, SORT_ASC, $data['list']);
            //循环处理信息
            foreach($data['list'] as $index => &$item){
                //获取名片和简历信息 内容类型:0=文本内容，1=图片信息，2=视频信息，3=名片信息，4=简历信息,5=其他信息(数组)
                if($item['type'] == 3){
                    //名片信息
                    $field = ['name','logo','position','company','branch'];
                    $card  = pdo_get(PDO_NAME."citycard_lists",['id' => $item['content']],$field);
                    $card['logo'] = tomedia($card['logo']);
                    $item['other'] = $card;
                }else if($item['type'] == 4){
                    //简历信息
                    $field = ['name','avatar','birth_time','experience_label_id','education_label_id','expect_position'];
                    $resume  = pdo_get(PDO_NAME."recruit_resume",['id' => $item['content']],$field);
                    $resume = Recruit::handleResumeInfo($resume);
                    $resume['name'] = mb_substr($resume['name'],0,1);
                    //删除多余的内容
                    unset($resume['birth_time'],$resume['experience_label_id'],$resume['education_label_id'],$resume['expect_position']);

                    $item['other'] = $resume;
                }
            }

            Commons::sRenderSuccess("通讯记录列表",$data);
        }

        include wl_template('member/im/record');
    }
    /**
     * Comment: 删除单条通讯信息
     * Author: zzw
     * Date: 2020/9/10 10:24
     */
    public function deleteOneInfo(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR show_json(0, '参数错误，请刷新重试！');
        //开始进行删除操作
        pdo_delete(PDO_NAME."im",['id'=>$id]);

        show_json(1);
    }
    /**
     * Comment: 清除某个对话的所有通信信息
     * Author: zzw
     * Date: 2020/9/10 11:27
     */
    public function clearRecord(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR show_json(0, '参数错误，请刷新重试！');
        //信息获取
        $info = pdo_get(PDO_NAME."im",['id'=>$id],['send_id','receive_id']);
        //删除操作
        $res = Im::deleteRecord($info['send_id'],$info['receive_id']);
        if($res) show_json(1, '操作成功！');
        else show_json(0, '清除失败，请刷新重试！');
    }
    /**
     * Comment: 通信设置
     * Author: zzw
     * Date: 2021/3/8 10:59
     */
    public function imSet(){
        global $_W,$_GPC;
        $name = 'im_set';
        if($_W['ispost']){
            $data = $_GPC['data'];

            Setting::wlsetting_save($data,$name);
            wl_message('设置成功！' , referer() , 'success');
        }
        //获取已存在的设置信息
        $set =  Setting::wlsetting_read($name);

        include wl_template('member/im/set');
    }



}