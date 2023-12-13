<?php
defined('IN_IA') or exit('Access Denied');

class Citycard {

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
    static function get_cates($all = true) {
        global $_W;
        $where = $all ? [] : ['enabled' => 1];
        $parents = $childrens = [];
        $lists = pdo_getall('wlmerchant_citycard_cates', array_merge($where, array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid'])), [], '', "sort DESC");
        foreach ($lists as $list) {
            if (empty($list['parentid'])) {
                $parents[$list['id']] = $list;
            } else {
                $childrens[$list['parentid']][] = $list;
            }
        }
        return ['parents' => $parents, 'childrens' => $childrens];
    }

    /**
     * 获取所有套餐
     * @param bool $all true全部套餐 false启用的套餐
     * @return array
     */
    static function get_meals($all = true) {
        global $_W;
        $where = $all ? [] : ['status' => 1];
        $meals = pdo_getall('wlmerchant_citycard_meals', array_merge($where, array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid'])), ['id','name','price','day','is_free','vipstatus','vipprice'], '', "sort DESC");
        return $meals;
    }

    /**
     * 名片的支付回调
     * @param array $param
     */
    static function payCitycardOrderNotify($params){
        global $_W;
        $order = pdo_get('wlmerchant_order', array('orderno' => $params['tid']));
        if ($order['status'] == 0 || $order['status'] == 5) {
            //更新订单
            $data = array('status' => $params['result'] == 'success' ? 3 : 0);
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
            $carddata = pdo_get('wlmerchant_citycard_lists',array('id' => $order['specid']));
            if($order['fightstatus'] == 1){ //创建或续费
                $meal = pdo_get('wlmerchant_citycard_meals',array('id' => $order['fkid']));
                $updata['paystatus'] = 1;
                if(empty($meal['check'])){
                    $updata['checkstatus'] = 1;
                    $updata['status'] = 1;//默认启用
                }//免审核
                if($carddata['meal_endtime']>time()){
                    $updata['meal_endtime'] = $carddata['meal_endtime'] + $meal['day']*3600*24;
                }else{
                    $updata['meal_endtime'] = time() + $meal['day']*3600*24;
                }
                if(p('distribution') && $meal['isdistri']){
                    $disorderid = Distribution::disCore($order['mid'], $order['price'], $meal['onedismoney'],$meal['twodismoney'],0,$order['id'],'citycard',1);
                    pdo_update(PDO_NAME.'order',array('disorderid' => $disorderid),array('id' => $order['id']));
                }
                pdo_update('wlmerchant_citycard_lists',$updata,array('id' => $order['specid']));
                if(empty($updata['checkstatus'])){   //通知管理员
                    $nickname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$order['mid']),'nickname');
                    $onecatename = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$carddata['one_class']),'name');
                    $twocatename = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$carddata['two_class']),'name');
                    $catename = !empty($twocatename)?$onecatename.'-'.$twocatename:$onecatename;
                    $first = '您好,用户['.$nickname. ']上传了新的城市名片信息';
                    $type = '新的城市名片信息认证';
                    $content = '名片分类:['.$catename.']';
                    $status = '待审核';
                    $remark = '请尽快前往系统后台审核名片资料';
                    News::noticeAgent('citycard',$carddata['aid'],$first,$type,$content,$status,$remark,time());
                }
            }else if($order['fightstatus'] == 2){ //置顶
                $top = pdo_get('wlmerchant_citycard_tops',array('id' => $order['fkid']));
                $updata['top_is'] = 1;
                if($carddata['top_endtime']>time()){
                    $updata['top_endtime'] = $carddata['top_endtime'] + $top['day']*3600*24;
                }else{
                    $updata['top_endtime'] = time() + $top['day']*3600*24;
                }
                pdo_update('wlmerchant_citycard_lists',$updata,array('id' => $order['specid']));
                if(p('distribution') && $top['isdistri']){
                    $disorderid = Distribution::disCore($order['mid'], $order['price'], $top['onedismoney'],$top['twodismoney'],0,$order['id'],'citycard',1);
                    pdo_update(PDO_NAME.'order',array('disorderid' => $disorderid),array('id' => $order['id']));
                }
            }
        }

    }

    /**
     * 同城名片计划任务
     */
    static function doTask() {
        global $_W, $_GPC;
        //取消过期置顶
        pdo_update('wlmerchant_citycard_lists',array('top_is' => 0),array('top_is' => 1,'top_endtime <' => time()));
        pdo_update('wlmerchant_citycard_lists',array('status' => 0),array('status' => 1,'meal_endtime <' => time()));
    }



}