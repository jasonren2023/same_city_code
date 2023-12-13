<?php
defined('IN_IA') or exit('Access Denied');


class Vehicle {
    /**
     * Comment: 默认标签列表
     * Author: zzw
     * Date: 2021/4/1 10:10
     * @return array[]
     */
    public static function defaultLabelList(){
        //is_passenger  是否适用于载客:1=不适用,2=适用
        //is_goods      是否适用于载货:1=不适用,2=适用
        //are_passenger 是否适用于找客:1=不适用,2=适用
        //are_goods     是否适用于找货:1=不适用,2=适用
        $list = [
            ['name' => '干净整洁','is_passenger' => 2,'is_goods' => 1,'are_passenger' => 2,'are_goods' => 1],
            ['name' => '消毒','is_passenger' => 2,'is_goods' => 1,'are_passenger' => 2,'are_goods' => 1],
            ['name' => '限男性','is_passenger' => 2,'is_goods' => 1,'are_passenger' => 2,'are_goods' => 1],
            ['name' => '限女性','is_passenger' => 2,'is_goods' => 1,'are_passenger' => 2,'are_goods' => 1],
            ['name' => '男女不限','is_passenger' => 2,'is_goods' => 1,'are_passenger' => 2,'are_goods' => 1],
            ['name' => '可带宠物','is_passenger' => 2,'is_goods' => 1,'are_passenger' => 2,'are_goods' => 1],
            ['name' => '禁止宠物','is_passenger' => 2,'is_goods' => 1,'are_passenger' => 2,'are_goods' => 1],
            ['name' => '上门接送','is_passenger' => 2,'is_goods' => 2,'are_passenger' => 2,'are_goods' => 2],
            ['name' => '可包车','is_passenger' => 2,'is_goods' => 2,'are_passenger' => 2,'are_goods' => 2],
            ['name' => '走高速','is_passenger' => 2,'is_goods' => 2,'are_passenger' => 2,'are_goods' => 2],
            ['name' => '需后备箱','is_passenger' => 2,'is_goods' => 1,'are_passenger' => 1,'are_goods' => 1],
            ['name' => '有小孩','is_passenger' => 2,'is_goods' => 1,'are_passenger' => 2,'are_goods' => 1],
            ['name' => '无小孩','is_passenger' => 2,'is_goods' => 1,'are_passenger' => 2,'are_goods' => 1],
            ['name' => '多件行李','is_passenger' => 2,'is_goods' => 1,'are_passenger' => 2,'are_goods' => 1],
            ['name' => '赶时间','is_passenger' => 2,'is_goods' => 2,'are_passenger' => 2,'are_goods' => 2],
            ['name' => '准时发车','is_passenger' => 2,'is_goods' => 2,'are_passenger' => 2,'are_goods' => 2],
            ['name' => '免费搬货','is_passenger' => 1,'is_goods' => 2,'are_passenger' => 1,'are_goods' => 2],
            ['name' => '付费搬货','is_passenger' => 1,'is_goods' => 2,'are_passenger' => 1,'are_goods' => 2],
            ['name' => '货物代运','is_passenger' => 1,'is_goods' => 1,'are_passenger' => 1,'are_goods' => 2],
            ['name' => '搬家','is_passenger' => 1,'is_goods' => 2,'are_passenger' => 1,'are_goods' => 2]
        ];

        return $list;
    }
    /**
     * Comment: 记录用户浏览历史
     * Author: zzw
     * Date: 2021/4/7 15:30
     * @param $id
     * @param $mid
     */
    public static function recordHistory($id,$mid){
        global $_W,$_GPC;
        $data = [
            'vehicle_id' => $id,
            'mid'        => $mid,
            'uniacid'    => $_W['uniacid']
        ];
        $isHave = pdo_get(PDO_NAME."vehicle_history",$data);
        if($isHave){
            //存在浏览历史  修改最近浏览时间
            pdo_update(PDO_NAME."vehicle_history",['update_time'=>time()],['id'=>$isHave['id']]);
        }else{
            //不存在浏览历史  添加浏览历史
            $data['create_time'] = $data['update_time'] = time();
            pdo_insert(PDO_NAME."vehicle_history",$data);
        }
    }
    /**
     * Comment: 发布成功模板消息通知
     * Author: zzw
     * Date: 2021/4/25 9:33
     * @param $id
     * @param $status
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendSuccessfullyPublishedMessage($id,$status){
        $vehicle = pdo_get(PDO_NAME."vehicle",['id'=>$id]);
        //根据状态获取发送内容 状态:1=待付款,2=待审核,3=未通过,4=进行中,5=已完成
        if($status == 3) $statusText = "未通过";
        else $statusText = "已通过";
        //生成模板消息
        $modelData = [
            'first'   => "您发布的出行路线已审核",
            'type'    => '路线审核',
            'content' => '您于'.date("Y-m-d H:s",$vehicle['create_time']).'发布的路线已审核完毕!',
            'status'  => $statusText,
            'time'    => date("Y-m-d H:i:s",time()),
            'remark'  => "点击查看"
        ];
        $url = h5_url('pages/subPages2/hitchRide/hitchRideDetails/hitchRideDetails',['id'=>$id]);
        TempModel::sendInit('service',$vehicle['mid'],$modelData,$vehicle['source'],$url);
    }
    /**
     * Comment: 发送消息通知代理商员工进行路线审核
     * Author: zzw
     * Date: 2021/4/25 9:41
     * @param $id
     */
    public static function sendAgentStaffTipMessage($id){
        //获取基本信息
        $vehicle = pdo_get(PDO_NAME."vehicle",['id'=>$id]);
        $nickname  = pdo_getcolumn(PDO_NAME."member",['id'=>$vehicle['mid']],'nickname');
        //生成模板消息
        $first   = '有新的顺风车路线需要进行审核!';//消息头部
        $content = "您好，用户[{$nickname}]发布了一条顺风车路线,[{$vehicle['start_address']} ~ {$vehicle['end_address']}]，请尽快进行审核!";//业务内容
        $type    = "路线审核";//业务类型
        $status  = "待审核";//处理结果
        $remark  = "请尽快处理!";//备注信息
        $time    = time();//操作时间

        News::noticeAgent('vehicle_examine',$vehicle['aid'],$first,$type,$content,$status,$remark,$time);
    }

    /**
     * Comment: 处理出发时间
     * Author: zzw
     * Date: 2021/4/7 10:50
     * @param $time
     * @return false|string
     */
    public static function handleStartTime($time){
        $toDayStart = strtotime(date("Y-m-d 00:00:00",time()));//今天 - 开始时间
        $toDayEnd   = strtotime(date("Y-m-d 23:59:59",time()));//今天 - 结束时间
        $dayStart   = strtotime(date("Y-m-d 00:00:00",strtotime("+1 Day")));//明天 - 开始时间
        $dayEnd     = strtotime(date("Y-m-d 23:59:59",strtotime("+1 Day")));//明天 - 结束时间
        //判断
        if ($time > $toDayStart && $time <= $toDayEnd) $text = '今天  '.date("H:i",$time);
        else if ($time > $dayStart && $time <= $dayEnd) $text = '明天  '.date("H:i",$time);
        else $text = date("Y-m-d H:i",$time);

        return $text;
    }
    /**
     * Comment: 距离处理
     * Author: zzw
     * Date: 2021/4/7 10:52
     * @param $distance
     * @return string
     */
    public static function handleDistance($distance){
        if($distance){
            if($distance < 1000) $text = $distance.'m';
            else $text = sprintf("%.2f",$distance / 1000).'km';
        }

        return $text;
    }
    /**
     * Comment: 处理运输类型  1=载客,2=载货,3=找客,4=找货
     * Author: zzw
     * Date: 2021/4/7 10:54
     * @param $transportType
     * @return string
     */
    public static function handleTransport($transportType){
        switch ($transportType){
            case 1:$text = '载客';break;
            case 2:$text = '载货';break;
            case 3:$text = '找客';break;
            case 4:$text = '找货';break;
        }

        return $text;
    }
    /**
     * Comment: 获取城市名称
     * Author: zzw
     * Date: 2021/4/7 11:08
     * @param $id
     * @return false|mixed
     */
    public static function handleArea($id){
        $name = pdo_getcolumn(PDO_NAME."area",['id'=>$id],'name');

        return $name;
    }
    /**
     * Comment: 获取标签列表
     * Author: zzw
     * Date: 2021/4/7 11:19
     * @param $ids
     * @return array
     */
    public static function handleLabel($ids){
        if($ids){
            //如果为字符串 则转换为数组
            if(is_string($ids)) $ids = explode(',',$ids);
            $list = pdo_getall(PDO_NAME."vehicle_label",['id IN'=>$ids],['name']);
            $list = array_column($list,'name');

            return $list ? : [];
        }

        return [];
    }


    /**
     * Comment: 发布回调
     * Author: zzw
     * Date: 2021/4/21 17:58
     * @param $params
     */
    public static function payVehicleOrderNotify($params) {
        global $_W;
        $order = pdo_get(PDO_NAME."order",['orderno' => $params['tid']],['plugin','uniacid','aid','id','fkid','fightstatus','num']);
        $_W['uniacid'] = $order['uniacid'];
        $_W['aid'] = $order['aid'];
        //更新订单
        $data            = ['status' => $params['result'] == 'success' ? 3 : 0];
        $data['paytype'] = $params['type'];
        if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
        $data['paytime'] = TIMESTAMP;
        pdo_update(PDO_NAME.'order',$data,['id' => $order['id']]);
        //修改路线状态
        $set = Setting::agentsetting_read('vehicle_set');
        //状态:1=待付款,2=待审核,3=未通过,4=进行中,5=已完成
        $status = $set['is_examine'] == 1 ? 2 : 4;//是否需要审核 1=需要审核，2=免审核
        pdo_update(PDO_NAME."vehicle",['status'=>$status],['id'=>$order['fkid']]);
        //发送代理商员工审核通知模板
        if($status == 2) self::sendAgentStaffTipMessage($order['fkid']);
    }




}
