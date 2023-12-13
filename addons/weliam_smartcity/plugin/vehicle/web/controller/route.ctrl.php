<?php
defined('IN_IA') or exit('Access Denied');


class Route_WeliamController{
    /**
     * Comment: 路线列表
     * Author: zzw
     * Date: 2021/4/2 9:45
     */
    public function routeList(){
        global $_W,$_GPC;
        //参数获取
        $page          = max(1,intval($_GPC['page']));
        $pageIndex     = 10;
        $pageStart     = $page * $pageIndex - $pageIndex;
        $nickname      = $_GPC['nickname'] ? : '';
        $status        = $_GPC['status'] ? : 0;
        $transportType = $_GPC['transport_type'] ? : 0;
        //条件生成
        $where = " where a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        if($nickname) $where .= " AND b.nickname LIKE '%{$nickname}%' ";
        if($status > 0) $where .= " AND a.status = {$status} ";
        if($transportType > 0) $where .= " AND a.transport_type = {$transportType} ";
        //列表获取
        $field = "a.id,a.mid,a.transport_type,a.start_time,a.start_address,a.end_address,a.create_time,
        a.contacts,a.contacts_phone,a.status,b.nickname,b.avatar";
        $order = ' ORDER BY a.create_time DESC,a.id DESC ';
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."vehicle")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id ";
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach ($list as &$val){
            //时间处理
            $val['start_time'] = date("Y-m-d H:i",$val['start_time']);
            $val['create_time'] = date("Y-m-d H:i:s",$val['create_time']);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total    = pdo_fetchcolumn($totalSql.$where);
        $pager    = wl_pagination($total,$page,$pageIndex);

        include wl_template('route/index');
    }
    /**
     * Comment: 路线添加/编辑
     * Author: zzw
     * Date: 2021/4/2 9:55
     */
    public function routeEdit(){
        global $_W,$_GPC;
        //基本参数信息获取
        $id = $_GPC['id'] ? : '';
        //接收信息 并且进行对应的处理
        if($_W['ispost']){
            $data  = $_GPC['data'];
            //判断内容是否完善
            if (!$data['mid']) wl_json(0,'请选择发布人');
            if ($data['transport_type'] == 1 && !$data['people']) wl_json(0,'请输入人数');
            if ($data['transport_type'] == 2 && !$data['weight']) wl_json(0,'请输入重量');
            if ($data['transport_type'] == 3 && !$data['people']) wl_json(0,'请输入空位');
            if ($data['transport_type'] == 4 && !$data['weight']) wl_json(0,'请输入载重');
            if (!$data['start_time']) wl_json(0,'请选择出发时间');
            if (!$data['start_address'] || !$data['start_lng'] || !$data['start_lat']) wl_json(0,'请选择出发点详细地址');
            if (!$data['end_address'] || !$data['end_lng'] || !$data['end_lat']) wl_json(0,'请选择终点点详细地址');
            if (!$data['contacts']) wl_json(0,'请输入联系人');
            if (!$data['contacts_phone']) wl_json(0,'请输入联系方式');
            //信息处理
            if(is_array($data['pass_by'])) $data['pass_by'] = implode(',',$data['pass_by']);
            if(is_array($data['label_id'])) $data['label_id'] = implode(',',$data['label_id']);
            $data['start_time'] = strtotime($data['start_time']);
            //根据经纬度获取区域信息
            $startId                   = Area::getAreaId($data['start_lat'],$data['start_lng']);
            $data['start_province_id'] = $startId['province_id'];
            $data['start_city_id']     = $startId['city_id'];
            $data['start_area_id']     = $startId['area_id'];
            $endId                     = Area::getAreaId($data['end_lat'],$data['end_lng']);
            $data['end_province_id']   = $endId['province_id'];
            $data['end_city_id']       = $endId['city_id'];
            $data['end_area_id']       = $endId['area_id'];
            //根据是否存在id 判断是添加操作还是修改操作
            if($id){
                pdo_update(PDO_NAME."vehicle",$data,['id'=>$id]);

                wl_json(1,'编辑成功');
            }else{
                $data['uniacid']     = $_W['uniacid'];
                $data['aid']         = $_W['aid'];
                $data['pv']          = 0;
                $data['create_time'] = time();
                pdo_insert(PDO_NAME."vehicle",$data);

                wl_json(1,'发布成功');
            }
        }
        //准备信息
        $province = pdo_getall(PDO_NAME."area",['level'=>1,'visible'=>2],['id','name']);//省级列表
        $label = pdo_getall(PDO_NAME."vehicle_label",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']]
            ,['id','name','is_passenger','is_goods','are_passenger','are_goods'],'','sort DESC');
        //编辑准备信息
        if($id){
            //修改信息准备
            $info = pdo_get(PDO_NAME."vehicle",['id'=>$id]);
            //区域信息获取
            if($info['start_city_id']) $startCity = pdo_getall(PDO_NAME."area",['pid'=>$info['start_province_id']],['id','name']);//出发城市 - 市列表
            if($info['start_area_id']) $startArea = pdo_getall(PDO_NAME."area",['pid'=>$info['start_city_id']],['id','name']);//出发城市 - 区列表
            if($info['end_city_id']) $endCity = pdo_getall(PDO_NAME."area",['pid'=>$info['end_province_id']],['id','name']);//终点 - 市列表
            if($info['end_area_id']) $endArea = pdo_getall(PDO_NAME."area",['pid'=>$info['end_city_id']],['id','name']);//终点 - 区列表
            //获取发布方信息
            $user = pdo_get(PDO_NAME."member",['id'=>$info['mid']],['nickname','avatar']);
            //处理标签信息
            $info['pass_by'] = $info['pass_by'] ? explode(',',$info['pass_by']) : [];
            $info['label_id'] = $info['label_id'] ? explode(',',$info['label_id']) : [];
            //时间信息处理
            $info['start_time'] = date('Y-m-d H:i',$info['start_time']);
            unset($info['id'],$info['uniacid'],$info['aid']);
        }

        include wl_template('route/edit');
    }
    /**
     * Comment: 删除标签信息
     * Author: zzw
     * Date: 2021/4/2 9:56
     */
    public function routeDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."vehicle",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }
    /**
     * Comment: 审核路线信息
     * Author: zzw
     * Date: 2021/4/2 10:05
     */
    public function routeExamine(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR show_json(0, "参数错误，请刷新重试!");
        $status = $_GPC['status'] ? : 4;//状态:1=待付款,2=待审核,3=未通过,4=进行中,5=已完成
        $reason = $_GPC['reason'] ? : '';
        if($status == 2 && !$reason) show_json(0, "请输入驳回原因");
        //修改状态
        $data = [
            'status' => $status,
            'reason' => $reason
        ];
        pdo_update(PDO_NAME."vehicle",$data,['id'=>$id]);
        //发送模板消息
        Vehicle::sendSuccessfullyPublishedMessage($id,$status);

        show_json(1, "操作成功");
    }

    /**
     * Comment: 标签列表
     * Author: zzw
     * Date: 2021/4/1 9:42
     */
    public function labelList(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $title     = $_GPC['title'] ? : '';//名称
        $type      = $_GPC['type'] ? : 0;//类型:1=载客,2=载货,3=找客,4=找货
        //条件生成
        $where = [
            'uniacid' => $_W['uniacid'] ,
            'aid'     => $_W['aid']
        ];
        if($title) $where['name LIKE'] = "%{$title}%";
        switch ($type){
            case 1: $where['is_passenger'] = 2;break;//是否适用于载客:1=不适用,2=适用
            case 2: $where['is_goods'] = 2;break;//是否适用于载货:1=不适用,2=适用
            case 3: $where['are_passenger'] = 2;break;//是否适用于找客:1=不适用,2=适用
            case 4: $where['are_goods'] = 2;break;//是否适用于找货:1=不适用,2=适用
        }
        //列表获取
        $field = ['id','name','is_passenger','is_goods','are_passenger','are_goods','sort','create_time'];
        $order = 'sort DESC,id DESC';
        $list = pdo_getall(PDO_NAME."vehicle_label",$where,$field,'',$order,[$page,$pageIndex]);
        //分页操作
        $total = pdo_count(PDO_NAME."vehicle_label",$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('label/index');
    }
    /**
     * Comment: 标签添加/编辑
     * Author: zzw
     * Date: 2021/4/1 9:35
     */
    public function labelEdit(){
        global $_W,$_GPC;
        //基本参数信息获取
        $id = $_GPC['id'] ? : '';
        //接收信息 并且进行对应的处理
        if($_W['ispost']){
            $data  = $_GPC['data'];
            $data['is_passenger'] = $data['is_passenger'] ?  : 1;
            $data['is_goods'] = $data['is_goods'] ?  : 1;
            $data['are_passenger'] = $data['are_passenger'] ?  : 1;
            $data['are_goods'] = $data['are_goods'] ?  : 1;
            //判断是否已经存在当前标签
            $where = [
                'uniacid' => $_W['uniacid'] ,
                'aid'     => $_W['aid'] ,
                'title'   => $data['title']
            ];
            if($id > 0) $where['id <>'] = $id;
            $isHave = pdo_get(PDO_NAME."vehicle_label",$where);
            if($isHave) wl_message('标签已经存在',referer(),'error');
            //根据是否存在id 判断是添加操作还是修改操作
            if($id){
                pdo_update(PDO_NAME."vehicle_label",$data,['id'=>$id]);

                wl_message('修改成功',web_url('vehicle/route/labelList'),'success');
            }else{
                //信息补充  并且进行添加操作
                $data['uniacid']     = $where['uniacid'];
                $data['aid']         = $where['aid'];
                $data['create_time'] = time();
                pdo_insert(PDO_NAME."vehicle_label",$data);

                wl_message('添加成功',web_url('vehicle/route/labelList'),'success');
            }
        }
        //准备信息
        if($id){
            //修改信息准备
            $info = pdo_get(PDO_NAME."vehicle_label",['id'=>$id],['name','is_passenger','is_goods','are_passenger','are_goods','sort']);
        }else{
            //添加信息准备
            $sort = pdo_fetchcolumn("SELECT MAX(id) FROM ".tablename(PDO_NAME."vehicle_label"));
            $info['sort'] = $sort ? : 0;
        }

        include wl_template('label/edit');
    }
    /**
     * Comment: 生成默认的标签信息
     * Author: zzw
     * Date: 2021/4/1 10:33
     */
    public function labelDefaultInfo(){
        global $_W,$_GPC;
        //获取默认行业类别
        $list = Vehicle::defaultLabelList();
        foreach($list as $val){
            //判断是否已经存在当前标签
            $where['uniacid'] = $val['uniacid'] = $_W['uniacid'];
            $where['aid']     = $val['aid'] = $_W['aid'];
            $where['name'] = $val['name'];
            $isHave = pdo_get(PDO_NAME."vehicle_label",$where);
            if(!$isHave){
                //不存在 添加标签信息
                $val['create_time'] = time();
                pdo_insert(PDO_NAME."vehicle_label",$val);
            }
        }
        //修改所有默认信息的排序信息
        pdo_fetchall("update ".tablename(PDO_NAME."vehicle_label")." set `sort` = `id` WHERE `sort` is null ");

        wl_json(1,'生成成功');
    }
    /**
     * Comment: 删除标签信息
     * Author: zzw
     * Date: 2021/4/1 9:43
     */
    public function labelDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."vehicle_label",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }


    /**
     * Comment: 顺风车基本设置
     * Author: zzw
     * Date: 2021/4/2 10:07
     */
    public function vehicleSet(){
        global $_W,$_GPC;
        $name = 'vehicle_set';
        if($_W['ispost']){
            $data = $_GPC['data'];

            Setting::agentsetting_save($data,$name);
            wl_message('设置成功！' , web_url('vehicle/route/vehicleSet') , 'success');
        }
        //获取已存在的设置信息
        $info =  Setting::agentsetting_read($name);

        include wl_template('vehicleSet');
    }


}