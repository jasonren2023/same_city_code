<?php
defined('IN_IA') or exit('Access Denied');


class Vip_WeliamController{
    /**
     * Comment: 会员卡列表
     * Author: zzw
     * Date: 2021/3/1 16:18
     */
    public function vipList(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $title     = $_GPC['title'] ? : '';
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} ";
        if($title) $where .= " AND title LIKE '%{$title}%' ";
        //sql语句生成
        $field = "id,title,type,day,second,sort,create_time";
        $order = " ORDER BY sort DESC,id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql   = "SELECT {$field} FROM ".tablename(PDO_NAME."dating_vip");
        //列表获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('vip/index');
    }
    /**
     * Comment: 会员卡编辑
     * Author: zzw
     * Date: 2021/3/1 16:23
     */
    public function vipEdit(){
        global $_W,$_GPC;
        //基本参数信息获取
        $id = $_GPC['id'] ? : '';
        //接收信息 并且进行对应的处理
        if($_W['ispost']){
            $data  = $_GPC['data'];
            //判断是否已经存在当前会员卡
            $where = [
                'uniacid' => $_W['uniacid'] ,
                'title'   => $data['title']
            ];
            if($id > 0) $where['id <>'] = $id;
            $isHave = pdo_get(PDO_NAME."dating_vip",$where);
            if($isHave) wl_message('会员卡已经存在',referer(),'error');
            //根据是否存在id 判断是添加操作还是修改操作
            if($id){
                pdo_update(PDO_NAME."dating_vip",$data,['id'=>$id]);

                wl_message('修改成功',web_url('dating/vip/vipList'),'success');
            }else{
                //信息补充  并且进行添加操作
                $data['uniacid']     = $where['uniacid'];
                $data['create_time'] = time();
                pdo_insert(PDO_NAME."dating_vip",$data);

                wl_message('添加成功',web_url('dating/vip/vipList'),'success');
            }
        }
        //准备信息
        if($id){
            //修改信息准备
            $info = pdo_get(PDO_NAME."dating_vip",['id'=>$id]);
        }else{
            //添加信息准备
            $sort = pdo_fetchcolumn("SELECT MAX(id) FROM ".tablename(PDO_NAME."dating_vip"));
            $info['sort'] = $sort ? : 1;
        }

        include wl_template('vip/edit');
    }
    /**
     * Comment: 会员卡删除
     * Author: zzw
     * Date: 2021/3/1 16:24
     */
    public function vipDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."dating_vip",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }


    /**
     * Comment: 会员卡开通记录列表
     * Author: zzw
     * Date: 2021/3/12 15:23
     */
    public function userList(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $nickname  = $_GPC['nickname'] ? : '';
        $timeType  = $_GPC['time_type'] ? : 0;//时间类型：1=开卡时间，2=续费时间
        $timeLimit = $_GPC['time_limit'];
        $timeStart = strtotime($timeLimit['start']) ? : time();
        $timeEnd   = strtotime($timeLimit['end']) ? : time();
        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} ";
        if($nickname) $where .= " AND b.nickname LIKE '%{$nickname}%' ";
        if($timeType == 1) $where .= " AND a.create_time >= {$timeStart} AND a.create_time <= {$timeEnd} ";
        else if($timeType == 2) $where .= " AND a.update_time >= {$timeStart} AND a.update_time <= {$timeEnd} ";
        //sql语句生成
        $field = "a.id,a.mid,a.type,a.end_time,a.frequency,a.create_time,a.update_time,b.nickname,b.avatar";
        $order = " ORDER BY a.update_time DESC,a.create_time DESC,a.id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql   = "SELECT {$field} FROM ".tablename(PDO_NAME."dating_member_open")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id ";
        //列表获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as &$value){
            //到期时间
            $value['end_time'] = date("Y-m-d H:i",$value['end_time']);
            //剩余次数
            $useTotal = pdo_count(PDO_NAME."dating_member_use",['mid'=>$value['mid']]);
            $value['surplus_number'] = $value['frequency'] - $useTotal;
            //其他信息处理
            $value['avatar'] = tomedia($value['avatar']);
            $value['create_time'] = date("Y-m-d H:i",$value['create_time']);
            $value['update_time'] = date("Y-m-d H:i",$value['update_time']);

            unset($value['mid'],$value['frequency']);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('user/index');
    }
    /**
     * Comment: 删除会员用户
     * Author: zzw
     * Date: 2021/4/7 11:40
     */
    public function userDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."dating_member_open",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }


    public function recordList(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $nickname  = $_GPC['nickname'] ? : '';
        //条件生成
        $where = " WHERE b.uniacid = {$_W['uniacid']} ";
        if($nickname) $where .= " AND b.nickname LIKE '%{$nickname}%' ";
        //sql语句生成
        $field = "a.create_time,a.title,a.type,a.day,a.frequency,a.money,b.nickname,b.avatar";
        $order = " ORDER BY a.create_time DESC,a.id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql   = "SELECT {$field} FROM ".tablename(PDO_NAME."dating_vip_record")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id ";
        //列表获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as &$value){
            //开通时间
            $value['create_time'] = date("Y-m-d H:i",$value['create_time']);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('record/index');
    }
    
    public function vipSee(){
    	global $_W,$_GPC;
    	$id = $_GPC['id'];
    	if($id > 0){
    		$vip = pdo_get('wlmerchant_dating_member_open',array('id' => $id));
    	}else{
    		$vip = ['type' => 1,'end_time' => time() + 86400*7];
    	}
    	
    	if($_W['ispost']){
            $data = $_GPC['data'];
            $data['end_time'] = strtotime($data['end_time']);
            if($id){
                $res = pdo_update('wlmerchant_dating_member_open',$data,array('id' => $id));
            }else{
                $data['uniacid'] = $_W['uniacid'];
                $data['create_time'] = time();
                $data['update_time'] = time();
                $res = pdo_insert('wlmerchant_dating_member_open',$data);
            }
            if($res){
                show_json(1,'操作成功');
            }else {
                show_json(0,'操作失败,请重试');
            }
        }
    	include  wl_template('user/vipSee');
    }








}