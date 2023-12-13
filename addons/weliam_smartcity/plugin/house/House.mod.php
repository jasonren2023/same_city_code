<?php
defined('IN_IA') or exit('Access Denied');


class House
{

    /**
     * Comment: 支付回调
     * @return array
     */
    static function payHouseOrderNotify($params)
    {
        Util::wl_log('payResult_notify',PATH_PLUGIN."house/data/",$params); //写入异步日志记录
        $order           = pdo_get('wlmerchant_order',['orderno' => $params['tid']],[
            'id',
            'fightstatus',
            'mid',
            'uniacid',
            'num',
            'specid',
            'price',
            'orderno',
            'fkid',
            'aid',
            'status'
        ]);
        $_W['uniacid']   = $order['uniacid'];
        $_W['aid']       = $order['aid'];
        $data            = [
            'status'  => 3,
            //'disorderid' => $disorderid,
            'paytime' => time()
        ];
        $data['paytype'] = $params['type'];
        if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
        //业务逻辑
        if($order['fightstatus'] == 2){//二手房
            $res = pdo_update(PDO_NAME.'old_house', ['status' => 2], ['id'=>$order['fkid']]);

//            $data['status'] = 1;
//
//            $nickname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$order['mid']),'nickname');
//            $service = pdo_get('wlmerchant_housekeep_service',['id' => $order['fkid']],['type','objid','title']);
//            $first   = "用户【{$nickname}】支付了[{$service['title']}]的家政服务";//消息头部
//            $type    = "家政服务";//业务类型
//            $content = $service['title'];//业务内容
//            $status  = "待处理";//处理结果
//            $remark  = "请尽快联系客户处理!";//备注信息
//            $time    = $data['paytime'];//操作时间
//            if($service['type'] == 1){
//                News::noticeShopAdmin($service['objid'], $first , $type , $content , $status , $remark , $time);
//            }else{
//                $mid = pdo_getcolumn(PDO_NAME.'housekeep_artificer',array('id'=>$service['objid']),'mid');
//                News::jobNotice($mid,$first,$type,$content,$status,$remark,$time);
//            }
        }else if ($order['fightstatus'] == 3) { //租房
            $res = pdo_update(PDO_NAME.'renting', ['status' => 2], ['id'=>$order['fkid']]);

//            $meal      = pdo_get('wlmerchant_housekeep_meals',['id' => $order['specid']],['day','check']);
//            $artificer = pdo_get('wlmerchant_housekeep_artificer',['id' => $order['fkid']],['name','endtime']);
//            if ($meal['check'] > 0) {
//                $newinfo['status'] = 5;
//            } else {
//                $newinfo['status'] = 1;
//            }
//            //计算时间
//            if ($artificer['endtime'] > time()) {
//                $newinfo['endtime'] = $artificer['endtime'] + $meal['day'] * 86400;
//            } else {
//                $newinfo['endtime'] = time() + $meal['day'] * 86400;
//            }
//            pdo_update('wlmerchant_housekeep_artificer',$newinfo,['id' => $order['fkid']]);
//            if ($newinfo['status'] == 5) {
//                $membername = pdo_getcolumn(PDO_NAME.'member',['id' => $order['mid']],'nickname');
//                //发送模板消息
//                $first   = '您好，一个家政服务者入驻申请待审核';
//                $type    = '审核家政服务者入驻信息';
//                $content = '服务者姓名:'.$artificer['name'];
//                $status  = '待审核';
//                $remark  = "微信用户[".$membername."]入驻家政服务者申请,请管理员尽快前往后台审核";
//                News::noticeAgent('housekeep',$_W['aid'],$first,$type,$content,$status,$remark,time(),'');
//            }
        }
//        } else if ($order['fightstatus'] == 3) {  //付费发布
//            $set = Setting::agentsetting_read('housekeep');
//            $demand = pdo_get('wlmerchant_housekeep_demand',['id' => $order['fkid']],['mid','topendtime','type']);
//            $getstatus = self::getStatus(2,$demand['mid'],$set,1);
//            if ($getstatus['status'] == 1) {  //免审核
//                pdo_update('wlmerchant_housekeep_demand',[
//                    'updatetime' => time(),
//                    'status'     => 1,
//                    'createtime' => time()
//                ],['id' => $order['fkid']]);
//            } else {
//                pdo_update('wlmerchant_housekeep_demand',[
//                    'updatetime' => time(),
//                    'status'     => 5,
//                    'createtime' => time()
//                ],['id' => $order['fkid']]);
//                $typetitle  = pdo_getcolumn(PDO_NAME.'housekeep_type',['id' => $demand['type']],'title');
//                $membername = pdo_getcolumn(PDO_NAME.'member',['id' => $order['mid']],'nickname');
//                //发送模板消息
//                $first   = '您好，您有一个待审核任务需要处理';
//                $type    = '审核用户家政需求';
//                $content = '需求类目:'.$typetitle;
//                $status  = '待审核';
//                $remark  = "用户[".$membername."]发布了一个商品待审核,请管理员尽快前往后台审核";
//                News::noticeAgent('housekeep',$order['aid'],$first,$type,$content,$status,$remark,time(),'');
//            }
//        } else if ($order['fightstatus'] == 4) {  //置顶
//            $demand = pdo_get('wlmerchant_housekeep_demand',['id' => $order['fkid']],['topendtime','type']);
//            if ($demand['topendtime'] > time()) {
//                $newtime = $demand['topendtime'] + 86400 * $order['num'];
//            } else {
//                $newtime = time() + 86400 * $order['num'];
//            }
//            pdo_update('wlmerchant_housekeep_demand',[
//                'updatetime' => time(),
//                'topflag'    => 1,
//                'topendtime' => $newtime
//            ],['id' => $order['fkid']]);
//        } else if ($order['fightstatus'] == 5) {  //刷新
//            pdo_update('wlmerchant_housekeep_demand',['updatetime' => time()],['id' => $order['fkid']]);
//        }
        //结算
        $res = pdo_update('wlmerchant_order',$data,['id' => $order['id']]);
        if ($res && $order['fightstatus'] != 1) {
            Store::ordersettlement($order['id']);
        }
    }



    /**
     * Comment: 添加或更新浏览记录
     * Author: wlf
     * Date: 2022/08/08 14:40
     * @return array
     */
    public static function addHistory($data){
        global $_W;
        //判断是否存在
        $id = pdo_getcolumn(PDO_NAME.'house_history',array('cid'=>$data['cid'],'mid'=>$data['mid'],'type'=>$data['type']),'id');
        if(empty($id)){
            $info = [
                'cid' => $data['cid'],
                'mid' => $data['mid'],
                'type' => $data['type'],
                'create_time' => time(),
                'releaseid' => $data['releaseid'],
                'releasetype' => $data['releasetype'],
            ];
            pdo_insert(PDO_NAME . 'house_history',$info);
            $newflag = 0;
        }else{
            pdo_update(PDO_NAME.'house_history',array('create_time' => time()),array('id' => $id));
            $newflag = 1;
        }
        //消息推送

        switch ($data['type']){
            case 1:
                $typename = '新房';
                $houseTitle = pdo_getcolumn(PDO_NAME.'new_house',array('id'=>$data['cid']),'title');
                break;
            case 2:
                $typename = '二手房';
                $houseTitle = pdo_getcolumn(PDO_NAME.'old_house',array('id'=>$data['cid']),'title');
                break;
            case 3:
                $typename = '租房';
                $houseTitle = pdo_getcolumn(PDO_NAME.'renting',array('id'=>$data['cid']),'title');
                break;
        }
        if($newflag > 0){
            $first = '老客户[' . $_W['wlmember']['nickname'] . ']再次查看了您发布的'.$typename.'信息';
        }else{
            $first = '新客户[' . $_W['wlmember']['nickname'] . ']查看了您发布的'.$typename.'信息';
        }
        $type = '用户访问提醒';
        $content = '房源标题:[' . $houseTitle . ']';
        $newStatus = '浏览中';
        $remark = '点击进入获客列表联系用户';

        if($data['releasetype'] == 1){
            $url = h5_url('pages/subPages2/houseproperty/browsetime/browsetime',['type'=>1]);
            if($data['mid'] != $data['releaseid']){
                News::jobNotice($data['releaseid'],$first,$type,$content,$newStatus,$remark,time(),$url);
            }
        }else{
            $url = h5_url('pages/subPages2/houseproperty/browsetime/browsetime' , ['sids' => $data['releaseid'],'type'=>2]);

            //获取顾问名单
            $alladviser = pdo_getall('wlmerchant_house_adviser_house',array('house_id' => $data['cid'],'type' =>$data['type']),array('adviser_id'));
            if(empty($alladviser)){
                News::noticeShopAdmin($data['releaseid'],$first,$type,$content,$newStatus,$remark,time(),$url);
            }else{
                foreach ($alladviser as $adviser){
                    $mid = pdo_getcolumn(PDO_NAME.'house_adviser',array('id'=>$adviser['adviser_id']),'user_id');
                    News::jobNotice($mid,$first,$type,$content,$newStatus,$remark,time(),$url);
                }
            }
        }
    }


    //计划任务
    static function doTask()
    {
        global $_W;
        //预约记录自动过期
        pdo_update('wlmerchant_house_make_appointment',['status' => 4],['appointment_time <' => time()]);
    }

}
