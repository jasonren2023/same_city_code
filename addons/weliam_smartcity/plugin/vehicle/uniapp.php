<?php
defined('IN_IA') or exit('Access Denied');


class VehicleModuleUniapp extends Uniapp{
    /**
     * Comment: 信息列表获取
     * Author: zzw
     * Date: 2021/4/6 17:44
     */
    public function homeList(){
        global $_W,$_GPC;
        //参数获取
        $page            = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex       = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart       = $page * $pageIndex - $pageIndex;
        $isTotal         = $_GPC['is_total'] ? : 0;//0=不获取总页数；1=获取总页数
        $lng             = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度  104.0091133118
        $lat             = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度  30.5681964123
        $sort            = $_GPC['sort'] ? : 1;//排序方式
        $type            = $_GPC['type'] ? : 0;//发布类型:1=乘客,2=车主
        $transportType   = $_GPC['transport_type'] ? : 0;//运输类型:1=载客,2=载货,3=找客,4=找货
        $frequency       = $_GPC['frequency'] ? : 0;//班次:1=一次,2=每天
        $startAddress    = $_GPC['start_address'] ? : '';//出发点 详细地址
//        $startProvinceId = $_GPC['start_province_id'] ? : 0;//出发点省id
//        $startCityId     = $_GPC['start_city_id'] ? : 0;//出发点市id
//        $startAreaId     = $_GPC['start_area_id'] ? : 0;//出发点区县id
        $endAddress      = $_GPC['end_address'] ? : '';//终点详细地址
//        $endProvinceId   = $_GPC['end_province_id'] ? : 0;//终点省id
//        $endCityId       = $_GPC['end_city_id'] ? : 0;//终点市id
//        $endAreaId       = $_GPC['end_area_id'] ? : 0;//终点区县id
        $startLat        = $_GPC['start_lat'] ? : '';//出发点 纬度
        $startLng        = $_GPC['start_lng'] ? : '';//出发点 经度
        $endLat          = $_GPC['end_lat'] ? : '';//终点 纬度
        $endLng          = $_GPC['end_lng'] ? : '';//终点 经度
        //特殊内容查询
        $distances = "ROUND(CASE 
                            WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.138 * 2 * ASIN(
                                    SQRT(
                                        POW(SIN(({$lat} * PI() / 180 - start_lat * PI() / 180) / 2),2) +
                                            COS({$lat} * PI() / 180) * COS(start_lat * PI() / 180) *
                                            POW(SIN(({$lng} * PI() / 180 - start_lng * PI() / 180) / 2),2)
                                        )
                                    ) * 1000
                                ) 
                            ELSE ''
                        END)";
        //出发点经纬度存在  获取出发城市
        if($startLat && $startLng){
            $startIds        = Area::getAreaId($startLat,$startLng);
            $startProvinceId = $startIds['province_id'];
            $startCityId     = $startIds['city_id'];
            $startAreaId     = $startIds['area_id'];
        }
        //终点经纬度存在  获取终点城市
        if($endLat && $endLng){
            $endIds        = Area::getAreaId($endLat,$endLng);
            $endProvinceId = $endIds['province_id'];
            $endCityId     = $endIds['city_id'];
            $endAreaId     = $endIds['area_id'];
        }
        //生成基本查询条件
        $time = time();
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 4";
        if ($type > 0) $where .= " AND type = {$type}";
        if ($transportType > 0) $where .= " AND transport_type = {$transportType}";
        if ($frequency > 0) $where .= " AND frequency = {$frequency}";
        if ($startAddress) $where .= " AND start_address LIKE '%{$startAddress}%' ";
        if ($startProvinceId > 0) $where .= " AND start_province_id = {$startProvinceId}";
        if ($startCityId > 0) $where .= " AND start_city_id = {$startCityId}";
        if ($startAreaId > 0) $where .= " AND start_area_id = {$startAreaId}";
        if ($endAddress) $where .= " AND end_address LIKE '%{$endAddress}%' ";
        if ($endProvinceId > 0) $where .= " AND end_province_id = {$endProvinceId}";
        if ($endCityId > 0) $where .= " AND end_city_id = {$endCityId}";
        if ($endAreaId > 0) $where .= " AND end_area_id = {$endAreaId}";
        //生成排序条件 1=推荐排序  2=浏览量  3=发布时间  4=距离排序
        switch ($sort) {
            case 1:
                $order = " ORDER BY id DESC ";
                break;//推荐排序
            case 2:
                $order = " ORDER BY pv DESC,id DESC ";
                break;//浏览量
            case 3:
                $order = " ORDER BY create_time DESC,id DESC ";
                break;//发布时间
            case 4:
                $order = " ORDER BY distances ASC,id DESC ";
                break;//距离排序
        }
        //sql语句生成
        $limit     = " LIMIT {$pageStart},{$pageIndex} ";
        $field     = "{$distances} as distances,id,transport_type,start_time,start_address,end_address,label_id,contacts_phone";
        $sql       = "SELECT {$field} FROM ".tablename(PDO_NAME."vehicle");
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach ($list as &$item) {
            $item['start_time_text']     = Vehicle::handleStartTime($item['start_time']);  //出发时间
            $item['distances_text']      = Vehicle::handleDistance($item['distances']);//距离处理
            $item['transport_type_text'] = Vehicle::handleTransport($item['transport_type']);//运输类型
            $item['label_id']            = Vehicle::handleLabel($item['label_id']);//标签列表
            $item['already_go']          = $item['start_time'] > time() ? 0 : 1 ;
            //删除多余的信息
            unset($item['transport_type'],$item['start_time'],$item['distances']);
        }
        //获取总页数
        if ($isTotal == 1) {
            //获取轮播图
            $adv = pdo_getall(PDO_NAME."adv",['type'=>17,'enabled' => 1,'uniacid'=>$_W['uniacid'],'aid' => $_W['aid']],['thumb','link'],'','displayorder DESC');
            foreach($adv as &$img){
                $img['thumb'] = tomedia($img['thumb']);
            }
            //获取总页数
            $totalSql      = str_replace($field,'count(*)',$sql);
            $total         = pdo_fetchcolumn($totalSql.$where);
            $data['count'] = $total ? : 0;
            $data['total'] = ceil($total / $pageIndex);
            $data['list']  = $list;
            $data['adv']   = is_array($adv) && count($adv) > 0 ? $adv : [];
            //ios虚拟支付
            $data['payclose'] = 0;
            if(intval($_W['wlsetting']['base']['payclose']) > 0){
                $set = Setting::agentsetting_read('vehicle_set');
                if($type == 1 && $set['owner'] == 2){
                    $data['payclose'] = 1;
                }else if($type == 2 && $set['passenger'] == 2){
                    $data['payclose'] = 1;
                }
            }
            $this->renderSuccess('信息列表',$data);
        }

        $this->renderSuccess('信息列表',$list);
    }
    /**
     * Comment: 路线详情
     * Author: zzw
     * Date: 2021/4/7 11:22
     */
    public function routeDesc(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR $this->renderError('信息不存在!');
        $set = Setting::agentsetting_read('vehicle_set');
        //获取信息内容
        $info = pdo_get(PDO_NAME."vehicle",['id'=>$id]);
        //信息处理
        $info['desc_disclaimers']    = $set['desc_disclaimers'] ? : '';//免责声明
        $info['start_time_text']     = Vehicle::handleStartTime($info['start_time']);  //出发时间
        $info['distances_text']      = Vehicle::handleDistance($info['distances']);//距离处理
        $info['transport_type_text'] = Vehicle::handleTransport($info['transport_type']);//运输类型
        $info['start_province_text'] = Vehicle::handleArea($info['start_province_id']);//出发城市 - 省
        $info['start_city_text']     = Vehicle::handleArea($info['start_city_id']);//出发城市 - 市
        $info['start_area_text']     = Vehicle::handleArea($info['start_area_id']);//出发城市 - 区
        $info['end_province_text']   = Vehicle::handleArea($info['end_province_id']);//目的地 - 省
        $info['end_city_text']       = Vehicle::handleArea($info['end_city_id']);//目的地 - 省
        $info['end_area_text']       = Vehicle::handleArea($info['end_area_id']);//目的地 - 省
        $passBy = explode(',',$info['pass_by']);//途径地点
        $info['pass_by']             = is_array($passBy) && $passBy[0] ? $passBy : [] ;//途径地点
        $info['label_id']            = Vehicle::handleLabel($info['label_id']);//标签列表
        //判断当前路线是否收藏 0=未收藏。1=已收藏
        $info['is_collection'] = 0;
        $isCollection = pdo_get(PDO_NAME."vehicle_collection",['vehicle_id'=>$id,'mid'=>$_W['mid']]);
        if($isCollection) $info['is_collection'] = 1;
        //记录浏览历史
        Vehicle::recordHistory($id,$_W['mid']);
        //删除多余的信息
        unset($info['uniacid'],$info['aid'],$info['start_time']
            ,$info['start_province_id'],$info['start_city_id'],$info['start_area_id'],$info['end_province_id'],$info['end_city_id']
            ,$info['end_area_id'],$info['create_time'],$info['status'],$info['reason']);

        $this->renderSuccess('路线详细信息',$info);
    }
    /**
     * Comment: 收藏&取消收藏
     * Author: zzw
     * Date: 2021/4/7 15:15
     */
    public function collection(){
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] OR $this->renderError('收藏失败，不存在的信息');
        //判断是否已经收藏  已收藏取消收藏，未收藏则进行收藏操作
        $data = [
            'vehicle_id' => $id,
            'mid'        => $_W['mid'],
            'uniacid'    => $_W['uniacid']
        ];
        $isCollection = pdo_get(PDO_NAME."vehicle_collection",$data);
        if($isCollection){
            //已收藏  取消操作
            pdo_delete(PDO_NAME."vehicle_collection",$data);
        }else if(!$isCollection){
            //未收藏  进行收藏操作
            $data['create_time'] = time();
            pdo_insert(PDO_NAME."vehicle_collection",$data);
        }

        $this->renderSuccess('操作成功');
    }
    /**
     * Comment: 举报路线
     * Author: zzw
     * Date: 2021/4/7 15:51
     */
    public function report(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR $this->renderError('不存在的信息!');
        $describe = $_GPC['describe'] OR $this->renderError('请输入描述信息！');
        $data = [
            'vehicle_id' => $id,
            'mid'        => $_W['mid'],
            'status'     => 1,//状态:1=待处理,2=处理中,3=已处理
        ];
        //判断是否重复提交
        $isRepeat = pdo_get(PDO_NAME."vehicle_report",$data);
        if(!$isRepeat){
            $data['uniacid']     = $_W['uniacid'];
            $data['aid']         = $_W['aid'];
            $data['describe']    = $describe;
            $data['create_time'] = time();

            pdo_insert(PDO_NAME."vehicle_report",$data);
        }

        $this->renderSuccess('举报成功!');
    }
    /**
     * Comment: 获取个人中心信息
     * Author: zzw
     * Date: 2021/4/7 16:11
     */
    public function userCenter(){
        global $_W,$_GPC;
        //获取用户信息
        $member = pdo_get(PDO_NAME."member",['id'=>$_W['mid']],['nickname','avatar']);
        $info['nickname'] = $member['nickname'];
        $info['avatar'] = tomedia($member['avatar']);
        //获取浏览历史数总数
        $info['history'] = pdo_count(PDO_NAME."vehicle_history",['uniacid'=>$_W['uniacid'],'mid'=>$_W['mid']]);
        //获取收藏信息的总数
        $info['collection'] = pdo_count(PDO_NAME."vehicle_collection",['uniacid'=>$_W['uniacid'],'mid'=>$_W['mid']]);
        //获取我发布的信息的总数
        $info['release'] = pdo_count(PDO_NAME."vehicle",['uniacid'=>$_W['uniacid'],'mid'=>$_W['mid']]);

        $this->renderSuccess('个人中心信息',$info);
    }
    /**
     * Comment: 获取浏览历史列表
     * Author: zzw
     * Date: 2021/4/7 16:28
     */
    public function getHistory(){
        global $_W,$_GPC;
        //参数获取
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        //生成基本查询条件
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.mid = {$_W['mid']} ";
        $order = " ORDER BY a.update_time DESC";
        $limit     = " LIMIT {$pageStart},{$pageIndex} ";
        $field     = "a.id as history_id,b.id,b.transport_type,b.start_time,b.start_address,b.end_address,b.label_id";
        $sql       = "SELECT {$field} FROM ".tablename(PDO_NAME."vehicle_history")
            ." as a RIGHT JOIN ".tablename(PDO_NAME."vehicle")
            ." as b ON a.vehicle_id = b.id ";
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach ($list as &$item) {
            $item['start_time_text']     = Vehicle::handleStartTime($item['start_time']);  //出发时间
            $item['transport_type_text'] = Vehicle::handleTransport($item['transport_type']);//运输类型
            $item['label_id']            = Vehicle::handleLabel($item['label_id']);//标签列表
            //删除多余的信息
            unset($item['transport_type'],$item['start_time'],$item['distances']);
        }
        //获取总页数
        $totalSql      = str_replace($field,'count(*)',$sql);
        $total         = pdo_fetchcolumn($totalSql.$where);
        $data['total'] = ceil($total / $pageIndex);
        $data['list']  = $list;

        $this->renderSuccess('获取浏览历史列表',$data);
    }
    /**
     * Comment: 删除浏览历史
     * Author: zzw
     * Date: 2021/4/7 17:20
     */
    public function delHistory(){
        global $_W,$_GPC;
        //参数获取
        $id = $_GPC['history_id'] OR $this->renderError('不存在的浏览历史!');
        //删除操作
        pdo_delete(PDO_NAME."vehicle_history",['id'=>$id]);

        $this->renderSuccess('删除成功');
    }
    /**
     * Comment: 获取收藏列表
     * Author: zzw
     * Date: 2021/4/7 16:29
     */
    public function getCollection(){
        global $_W,$_GPC;
        //参数获取
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        //生成基本查询条件
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.mid = {$_W['mid']} ";
        $order = " ORDER BY a.create_time DESC";
        $limit     = " LIMIT {$pageStart},{$pageIndex} ";
        $field     = "b.id,b.transport_type,b.start_time,b.start_address,b.end_address,b.label_id";
        $sql       = "SELECT {$field} FROM ".tablename(PDO_NAME."vehicle_collection")
            ." as a LEFT JOIN ".tablename(PDO_NAME."vehicle")
            ." as b ON a.vehicle_id = b.id ";
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach ($list as &$item) {
            $item['start_time_text']     = Vehicle::handleStartTime($item['start_time']);  //出发时间
            $item['transport_type_text'] = Vehicle::handleTransport($item['transport_type']);//运输类型
            $item['label_id']            = Vehicle::handleLabel($item['label_id']);//标签列表
            //删除多余的信息
            unset($item['transport_type'],$item['start_time'],$item['distances']);
        }
        //获取总页数
        $totalSql      = str_replace($field,'count(*)',$sql);
        $total         = pdo_fetchcolumn($totalSql.$where);
        $data['total'] = ceil($total / $pageIndex);
        $data['list']  = $list;

        $this->renderSuccess('获取收藏列表',$data);
    }
    /**
     * Comment: 获取我的发布信息列表
     * Author: zzw
     * Date: 2021/4/7 16:40
     */
    public function getMyRelease(){
        global $_W,$_GPC;
        //参数获取
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        //生成基本查询条件
        $where = " WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} ";
        $order = " ORDER BY create_time DESC";
        $limit     = " LIMIT {$pageStart},{$pageIndex} ";
        $field     = "id,transport_type,start_time,start_address,end_address,label_id,status";
        $sql       = "SELECT {$field} FROM ".tablename(PDO_NAME."vehicle");
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach ($list as &$item) {
            $item['start_time_text']     = Vehicle::handleStartTime($item['start_time']);  //出发时间
            $item['transport_type_text'] = Vehicle::handleTransport($item['transport_type']);//运输类型
            $item['label_id']            = Vehicle::handleLabel($item['label_id']);//标签列表
            $item['top_end_time_text']   = Vehicle::handleStartTime($item['top_end_time']);//标签列表
            //判断是否待支付 是则获取订单信息
            if($item['status'] == 1){
                $orderWhere = [
                    'fkid' =>$item['id'],
                    'plugin' => 'vehicle'
                ];
                $order = pdo_get(PDO_NAME."order",$orderWhere,['id','plugin']);
                $item['order'] = [
                    'type'    => $order['plugin'],
                    'orderid' => $order['id'],
                ];
            }
            //删除多余的信息
            unset($item['transport_type'],$item['start_time']);
        }
        //获取总页数
        $totalSql      = str_replace($field,'count(*)',$sql);
        $total         = pdo_fetchcolumn($totalSql.$where);
        $data['total'] = ceil($total / $pageIndex);
        $data['list']  = $list;

        $this->renderSuccess('我的发布信息列表',$data);
    }
    /**
     * Comment: 删除我的发布
     * Author: zzw
     * Date: 2021/4/7 17:21
     */
    public function delMyRelease(){
        global $_W,$_GPC;
        //参数获取
        $id = $_GPC['id'] OR $this->renderError('不存在的信息!');
        //删除操作
        pdo_delete(PDO_NAME."vehicle",['id'=>$id]);

        $this->renderSuccess('删除成功');
    }
    /**
     * Comment: 路线信息发布/编辑
     * Author: zzw
     * Date: 2021/4/8 10:21
     */
    public function editRoute(){
        global $_W,$_GPC;
        //参数信息获取
        $type = $_GPC['type'] ? : 'get';//get=获取数据,post=提交数据
        $id   = $_GPC['id'] ? : '';
        $set  = Setting::agentsetting_read('vehicle_set');
        //判断请求类型 进行对应的操作
        if($type == 'post') {
            //基本参数信息获取
            $data = json_decode(html_entity_decode($_GPC['data']),true);
            //判断数据是否完整
            if ($data['type'] == 1 && $data['transport_type'] == 1 && !$data['people']) $this->renderError('请输入人数');
            if ($data['type'] == 1 && $data['transport_type'] == 2 && !$data['weight']) $this->renderError('请输入重量(kg)');
            if ($data['type'] == 2 && $data['transport_type'] == 3 && !$data['people']) $this->renderError('请输入空位');
            if ($data['type'] == 2 && $data['transport_type'] == 4 && !$data['weight']) $this->renderError('请输入载重(kg)');
            if (!$data['start_time']) $this->renderError('请选择出发时间');
            if (!$data['start_address'] || !$data['start_lng'] || !$data['start_lat']) $this->renderError('请选择出发点详细地址');
            if (!$data['end_address'] || !$data['end_lng'] || !$data['end_lat']) $this->renderError('请选择目的地详细地址');
            if (!$data['contacts']) $this->renderError('请输入联系人姓名');
            if (!$data['contacts_phone']) $this->renderError('请输入联系人联系方式');
            //信息处理
            $data['pass_by']  = count($data['pass_by']) > 0 ? implode($data['pass_by'],',') : '';
            $data['label_id'] = implode($data['label_id'],',');
            $data['status']   = $set['is_examine'] == 1 ? 2 : 4;//是否需要审核 1=需要审核，2=免审核   状态:1=待付款,2=待审核,3=未通过,4=进行中,5=已完成
            //根据经纬度获取区域信息
            $startId                   = Area::getAreaId($data['start_lat'],$data['start_lng']);
            $data['start_province_id'] = $startId['province_id'];
            $data['start_city_id']     = $startId['city_id'];
            $data['start_area_id']     = $startId['area_id'];
            $endId                     = Area::getAreaId($data['end_lat'],$data['end_lng']);
            $data['end_province_id']   = $endId['province_id'];
            $data['end_city_id']       = $endId['city_id'];
            $data['end_area_id']       = $endId['area_id'];
            //数据库字段判断
            $data = fieldJudge($data,'vehicle');
            if($id){
                //修改数据

                pdo_update(PDO_NAME."vehicle",$data,['id'=>$id]);
            }else{
                //添加数据
                $data['uniacid']       = $_W['uniacid'];
                $data['aid']           = $_W['aid'];
                $data['mid']           = $_W['mid'];
                $data['source']        = $_W['source'];//1=公众号（默认）；2=h5；3=小程序
                $data['create_time']   = time();

                $res = pdo_insert(PDO_NAME."vehicle",$data);
                if($res){
                    //发布成功  判断是否需要支付
                    $id = pdo_insertid();//路线id
                    if(($data['type'] == 1 && $set['passenger'] == 2 && $set['passenger_money'] > 0) || ($data['type'] == 2 && $set['owner'] == 2 && $set['owner_money'] > 0)){
                        //需要支付 修改状态为待支付
                        pdo_update(PDO_NAME."vehicle",['status'=>1],['id'=>$id]);
                        //需要支付  根据类型获取支付金额
                        if($data['type'] == 1) $money = $set['passenger_money'];
                        else $money = $set['owner_money'];
                        //生成订单
                        $orderData = [
                            'uniacid'     => $_W['uniacid'],
                            'mid'         => $_W['mid'],
                            'aid'         => $_W['aid'],
                            'fkid'        => $id,//路线id
                            'createtime'  => time(),
                            'orderno'     => createUniontid(),
                            'price'       => sprintf("%.2f",$money),
                            'num'         => 1,
                            'plugin'      => 'vehicle',
                            'payfor'      => 'vehicleOrder',
                            'goodsprice'  => sprintf("%.2f",$money),
                            'fightstatus' => 1,
                        ];
                        pdo_insert(PDO_NAME.'order',$orderData);
                        $orderId = pdo_insertid();
                        if (empty($orderId)) $this->renderError('生成订单失败，请刷新重试');
                        else $this->renderSuccess('发布成功',['status' => 1,'type' => 'vehicle','orderid' => $orderId]);
                    }
                }
            }
            //判断是否发送模板消息
            if($data['status'] == 2){
                //不需要支付 但是需要审核，发布模板消息通知代理商员工
                Vehicle::sendAgentStaffTipMessage($id);
            } else if($data['status'] == 4){
                //不需要支付 不需要审核，发布模板消息通知用户审核成功
                Vehicle::sendSuccessfullyPublishedMessage($id,$data['status']);
            }

            $this->renderSuccess('操作成功');
        }else{
            //基础信息配置
            $data = [
                'label'           => pdo_getall(PDO_NAME."vehicle_label",['uniacid' => $_W['uniacid'],'aid' => $_W['aid']],['id','name','is_passenger','is_goods','are_passenger','are_goods'],'','sort DESC'),
                'info'            => [],
                'passenger'       => $set['passenger'] ? : 1,//乘客发布是否收费：1=免费发布,2=付费发布
                'passenger_money' => $set['passenger_money'] ? : 0,//乘客发布收费金额
                'owner'           => $set['owner'] ? : 1,//车主发布是否收费：1=免费发布,2=付费发布
                'owner_money'     => $set['owner_money'] ? : 0,//车主发布收费金额
                'order'           => [],//订单信息
            ];
            //存在id  获取路线信息
            $info = pdo_get(PDO_NAME."vehicle",['id'=>$id]);
            if($info){
                //信息处理
                $passBy = explode(',',$info['pass_by']);//途径地点
                $info['pass_by'] = is_array($passBy) && $passBy[0] ? $passBy : [] ;//途径地点
                $info['label_id'] = explode(',',$info['label_id']);
                //判断是否待支付 是则获取订单信息
                if($info['status'] == 1){
                    $orderWhere = [
                        'fkid' =>$id,
                        'plugin' => 'vehicle'
                    ];
                    $order = pdo_get(PDO_NAME."order",$orderWhere,['id','plugin']);
                    $info['order'] = [
                        'type'    => $order['plugin'],
                        'orderid' => $order['id'],
                    ];
                }
                //删除多余的信息
                unset($info['uniacid'],$info['aid'],$info['mid'],$info['create_time'],$info['pv']);
                $data['info'] = $info;
            }

            $this->renderSuccess('会员信息',$data);
        }
    }




}




