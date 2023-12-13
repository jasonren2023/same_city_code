<?php
defined('IN_IA') or exit('Access Denied');

class Yellowpage {

    /**
     * 经纬度转行政区划代码
     * @param $location 经纬度
     * @param bool $convert 是否需要百度转腾讯
     * @return array
     */
    static function lnglat_to_adinfo($location, $convert = false) {
        global $_W;
        if (empty($location['lat']) || empty($location['lng'])) {
            return error(1, '经纬度不得为空');
        }

        $pro_code = $city_code = $area_code = 0;
        //$location = $convert ? Util::Convert_BD09_To_GCJ02($location['lat'], $location['lng']) : $location;
        $location_info = MapService::guide_gcoder($location['lat'] . ',' . $location['lng']);
        if (is_error($location_info)) {
            $agent_area = pdo_get('wlmerchant_oparea', array('aid' => $_W['aid']));
            $pro_code = ($agent_area['level'] == 1) ? $agent_area['areaid'] : $pro_code;
            $city_code = ($agent_area['level'] == 2) ? $agent_area['areaid'] : $city_code;
            $area_code = ($agent_area['level'] == 3) ? $agent_area['areaid'] : $area_code;
        } else {
            $area_code = $location_info['result']['ad_info']['adcode'];
        }

        if (!empty($area_code)) {
            $city_code = pdo_getcolumn('wlmerchant_area', ['id' => $area_code], 'pid');
        }
        if (!empty($city_code)) {
            $pro_code = pdo_getcolumn('wlmerchant_area', ['id' => $city_code], 'pid');
        }

        return ['lat' => $location['lat'], 'lng' => $location['lng'], 'pro_code' => $pro_code, 'city_code' => $city_code, 'area_code' => $area_code];
    }


    /**
     * 获取所有分类
     * @param bool $all true全部分类 false启用的分类
     * @return array
     */
    static function get_cates($all = true,$aid = 0,$search = '',$sys = false) {
        global $_W;
        $aid = $aid ? : $_W['aid'];
        $where = $all ? [] : ['enabled' => 1];
        $parents = $childrens = [];
        $lists = pdo_getall('wlmerchant_yellowpage_cates', array_merge($where, array('uniacid' => $_W['uniacid'], 'aid' => $aid)), [], '', "sort DESC,id DESC");
        foreach ($lists as $list) {
            $list['logo'] = tomedia($list['logo']);
            if (empty($list['parentid'])) {
                $parents[$list['id']] = $list;
            } else {
                if(!$all){
                    $list['num'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_yellowpage_lists')." WHERE two_class = {$list['id']} AND status = 1 AND checkstatus = 1");
                }
                $childrens[$list['parentid']][] = $list;
            }
        }
        //条件筛出
        if(!empty($search)){
            foreach ($parents as $pk2 => $parse){
                if(!(strpos($parse['name'],$search) !== false)){
                    foreach($childrens[$parse['id']] as $ck => $chil){
                        if(!(strpos($chil['name'],$search) !== false)){
                            unset($childrens[$parse['id']][$ck]);
                        }
                    }
                }
            }
        }
        if(empty($sys)){
            foreach ($parents as $pk1 => $par){
                if(empty($childrens[$pk1])){
                    unset($parents[$pk1]);
                }
            }
            foreach ($parents as $par){
                $nparents[] = $par;
            }
        }else{
            $nparents = $parents;
        }
        return ['parents' => $nparents, 'childrens' => $childrens];
    }

    /**
     * 修改黄页浏览量
     * @param number $id 黄页id
     * @return bool
     */
    static function changepv($id,$minup,$maxup) {
        global $_W;
        if($minup > 0 && $maxup > 0){
            $up = rand($minup,$maxup);
        }else{
            $up = 1;
        }
        pdo_query('UPDATE ' . tablename(PDO_NAME . 'yellowpage_lists') . " SET `pv` = `pv` + {$up} WHERE id = {$id}");
    }

    /**
     * 订单退款
     * @param number $id 黄页id
     */
    static function refund($id,$type = 2){
        global $_W;
        $res = wlPay::refundMoney($id,0, '黄页114订单退款', 'yellowpage',$type);
        if ($res['status']) {
            $orderdata['status'] = 7;
            $orderdata['refundtime'] = time();
            pdo_update('wlmerchant_order',$orderdata, array('id' => $id));
            pdo_update('wlmerchant_yellowpage_claim_lists',['paystatus' => 3],array('orderid' => $id));
            return true;
        }else{
            return false;
        }
    }

    /**
     * 黄页支付回调
     * @param array $param
     */
    static function payPageOrderNotify($params){
        $order = pdo_get('wlmerchant_order', array('orderno' => $params['tid']));
        if ($order['status'] == 0 || $order['status'] == 5) {
            //更新订单
            if($order['fightstatus'] == 1){
                $data = array('status' => $params['result'] == 'success' ? 1 : 0);
            }else{
                $data = array('status' => $params['result'] == 'success' ? 3 : 0);
            }
            $data['paytype'] = $params['type'];
            if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
            $data['paytime'] = TIMESTAMP;
            pdo_update(PDO_NAME . 'order', $data, array('id' => $order['id']));
            $task = array(
                'type'    => $order['plugin'],
                'orderid' => $order['id']
            );
            $task = serialize($task);
            Queue::addTask(2, $task, time(), $order['id']);
            $pagedata = pdo_get('wlmerchant_yellowpage_lists',array('id' => $order['fkid']));
            if($order['fightstatus'] == 1){  //认领
                $claimData = [
                    'uniacid'    => $order['uniacid'],
                    'aid'        => $order['aid'],
                    'mid'        => $order['mid'],
                    'pageid'     => $order['fkid'],
                    'createtime' => time(),
                    'paystatus'  => 1,
                    'desc'       => $order['buyremark'],
                    'name'       => $order['name'],
                    'mobile'     => $order['mobile'],
                    'orderid'    => $order['id']
                ];
                pdo_insert(PDO_NAME . 'yellowpage_claim_lists', $claimData);
                //发送模板消息通知
                News::paySuccess($order['id'],'yellowpage',1);
                $first   = "用户【{$order['name']}】申请认领114页面";//消息头部
                $type    = "114页面认领";//业务类型
                $content = "114页面:[".$pagedata['name']."]";//业务内容
                $status  = "待审核";//处理结果
                $remark  = "请尽快审核！";//备注信息
                $time    = $claimData['createtime'];//操作时间
                News::noticeAgent('yellowpage' , $claimData['aid'] , $first , $type , $content , $status , $remark , $time);
            }else if($order['fightstatus'] == 2){
                News::paySuccess($order['id'],'yellowpage',1);
            }else if($order['fightstatus'] == 3){  //入驻
                $meal = pdo_get('wlmerchant_yellowpage_meals',array('id' => $order['specid']),array('is_free','price','day','check'));
                $meal_endtime = time() + $meal['day'] * 86400;
                pdo_update('wlmerchant_yellowpage_lists',['paystatus' => 1,'meal_endtime'=>$meal_endtime],array('id' => $order['fkid']));
                if($meal['check']){
                    $member = pdo_get('wlmerchant_member',array('id' => $order['mid']),array('realname','nickname'));
                    $name = $member['realname'] ? : $member['nickname'];
                    //发送审核通知
                    $first   = "用户【{$name}】入驻114页面";//消息头部
                    $type    = "114页面入驻";//业务类型
                    $content = "114页面:[".$pagedata['name']."]";//业务内容
                    $status  = "待审核";//处理结果
                    $remark  = "请尽快审核！";//备注信息
                    $time    = time();//操作时间
                    News::noticeAgent('yellowpage' , $pagedata['aid'] , $first , $type , $content , $status , $remark , $time);
                }else{
                    pdo_update('wlmerchant_yellowpage_lists',['status' =>1 ,'checkstatus' => 1,'paystatus' => 1,'meal_endtime' => $meal_endtime],array('id' => $order['fkid']));
                }
                News::paySuccess($order['id'],'yellowpage',1);
            }else if($order['fightstatus'] == 4){
                $meal = pdo_get('wlmerchant_yellowpage_meals',array('id' => $order['specid']),array('day','check'));
                $meal_endtime = $pagedata['meal_endtime'] > time() ? $pagedata['meal_endtime'] + $meal['day'] * 86400 : time() + $meal['day'] * 86400;
                $updateinfo = array('meal_endtime' => $meal_endtime,'meal_id' => $order['specid'],'paystatus'=>1);
                if(empty($meal['check'])){
                    $updateinfo['checkstatus'] = 1;
                    $updateinfo['status'] = 1;
                }
                $res = pdo_update('wlmerchant_yellowpage_lists',$updateinfo,array('id' => $order['fkid']));
            }
        }
    }

    /**
     * 退款
     */
    static function refundOrder($orderid){
        global $_W, $_GPC;
        $res = wlPay::refundMoney($orderid, 0, '认领页面驳回退款', 'yellowpage', 2);
        if ($res['status']) {
            pdo_update('wlmerchant_order',['status' => 7], array('id' => $orderid));
            pdo_update('wlmerchant_yellowpage_claim_lists',['paystatus' => 3], array('orderid' => $orderid));
        }
    }

    /**
     * 发送模板消息通知
     * type 1 = 页面审核 2 = 认领审核
     * status 1 = 通过 2 = 驳回
     */
    static function Notice($mid,$type,$status,$pageid,$reason=''){
        global $_W, $_GPC;
        if($type == 1){  //页面审核
            $first = '您的114页面入驻申请已审核';
            $type = '114页面审核';
            if($status == 1){
                $status = '已通过';
                $content = '审核已通过，点击查看页面';
                $remark = '谢谢您对平台的支持';
                $url = h5_url('pages/subPages2/phoneBook/logistics/logistics',['id'=>$pageid]);
            }else if($status == 2){
                $status = '被驳回';
                $content = '入驻页面被驳回';
                $remark = '驳回原因:'.$reason;
                $url = h5_url('pages/subPages2/phoneBook/myGoods/myGoods');
            }
        }else{
            $first = '您的114页面认领申请已审核';
            $type = '114页面认领';
            if($status == 1){
                $status = '已通过';
                $content = '审核已通过，点击查看页面';
                $remark = '谢谢您对平台的支持';
                $url = h5_url('pages/subPages2/phoneBook/logistics/logistics',['id'=>$pageid]);
            }else if($status == 2){
                $status = '被驳回';
                $content = '入驻页面被驳回,点击查看页面';
                $remark = '驳回原因:'.$reason;
                $url = h5_url('pages/subPages2/phoneBook/logistics/logistics',['id'=>$pageid]);
            }
        }
        News::jobNotice($mid, $first, $type, $content, $status, $remark, time(), $url);
    }

    /**
     * 同城名片计划任务
     */
    static function doTask() {
        global $_W, $_GPC;
        //取消关闭过期黄页
        pdo_update('wlmerchant_yellowpage_lists',array('status' => 0),array('status' => 1,'meal_endtime <' => time()));
    }

}