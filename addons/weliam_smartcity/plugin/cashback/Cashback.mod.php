<?php
defined('IN_IA') or exit('Access Denied');

class Cashback {
    /**
     * Comment: 支付返现信息处理、记录
     * Author: zzw
     * Date: 2020/1/13 15:23
     * @param string    $id     订单id
     * @param string    $plugin 模块信息
     * @return array
     */
    public static function record($id,$plugin){
        global $_W;
        #1、获取订单信息/商品信息
        switch ($plugin){
            case 'rush':
                $orderInfo = pdo_get(PDO_NAME."rush_order",['id'=>$id],['activityid','actualprice','mid','orderno']);
                $goodsInfo = pdo_get(PDO_NAME."rush_activity",['id'=>$orderInfo['activityid']],['id','cash_back','return_proportion']);
                //字段统一处理
                $orderInfo['plugin'] = 'rush';
                $orderInfo['price'] = $orderInfo['actualprice'];
                unset($orderInfo['actualprice']);
                break;//抢购
            case 'groupon':
                $orderInfo = pdo_get(PDO_NAME."order",['id'=>$id],['fkid','price','mid','plugin','orderno']);
                $goodsInfo = pdo_get(PDO_NAME."groupon_activity",['id'=>$orderInfo['fkid']],['id','cash_back','return_proportion']);
                break;//团购
            default:
                Util::wl_log('cashBackRecord' , PATH_MODULE . "log/" , ['order_id'=>$id,'plugin'=>$plugin,'error'=>'当前模块商品不支持返现'] , '订单返现信息' , false);
                return error(0,'当前模块商品不支持返现');
                break;//当前模块商品不允许返现
        }
        $money = sprintf("%.2f" , $orderInfo['price'] * ($goodsInfo['return_proportion'] / 100));
        #2、信息判断 是否允许返现操作   是否开启支付返现(0=关闭，1=开启)
        if(!$orderInfo) {
            Util::wl_log('cashBackRecord' , PATH_MODULE . "log/" , ['order_id'=>$id,'plugin'=>$plugin,'error'=>'订单不存在'] , '订单返现信息' , false);
            return error(0,'订单不存在');
        }
        if(!$goodsInfo) {
            Util::wl_log('cashBackRecord' , PATH_MODULE . "log/" , ['order_id'=>$id,'plugin'=>$plugin,'error'=>'商品不存在'] , '订单返现信息' , false);
            return error(0,'商品不存在');
        }
        if($goodsInfo['cash_back'] != 1){
            Util::wl_log('cashBackRecord' , PATH_MODULE . "log/" , ['order_id'=>$id,'plugin'=>$plugin,'error'=>'当前商品未开启支付返现'] , '订单返现信息' , false);
            return error(0,'当前商品未开启支付返现');
        }
        if($money <= 0){
            Util::wl_log('cashBackRecord' , PATH_MODULE . "log/" , ['order_id'=>$id,'plugin'=>$plugin,'error'=>'返现金额为0'] , '订单返现信息' , false);
            return error(0,'返现金额为0');
        }
        #3、支付返现操作
        $set = Setting::wlsetting_read("cash_back");//to_examine：是否需要审核：0=开启，1=关闭
        $data = [
            'uniacid'     => $_W['uniacid'] ,
            'mid'         => $orderInfo['mid'] ,
            'goods_id'    => $goodsInfo['id'] ,
            'order_id'    => $id ,
            'plugin'      => $orderInfo['plugin'] ,
            'status'      => $set['to_examine'] == 1 ? 1 : 0 ,//是否审核（0=审核中，1=已返现）
            'money'       => $money,
            'create_time' => time()
        ];
        $res = pdo_insert(PDO_NAME."cashback",$data);
        if($res){
            Util::wl_log('cashBackRecord' , PATH_MODULE . "log/" , ['order_id'=>$id,'plugin'=>$plugin,'cashback_id'=>pdo_insertid()] , '订单返现信息' , false);
            if($set['to_examine'] == 1){
                //未开启返现审核功能  自动返现
                self::moneyBack($data['mid'],$data['money'],$orderInfo['orderno']);
            }

            return error(1,'返现成功');
        }else{
            Util::wl_log('cashBackRecord' , PATH_MODULE . "log/" , ['order_id'=>$id,'plugin'=>$plugin,'error'=>'返现记录储存失败','data'=>$data] , '订单返现信息' , false);
            return error(0,'返现记录储存失败');
        }
    }
    /**
     * Comment: 支付返现余额处理
     * Author: zzw
     * Date: 2020/1/13 15:44
     * @param int $mid  用户id
     * @param float $money  返现金额
     * @param int|string $orderno  订单号
     * @return bool
     */
    public static function moneyBack($mid,$money,$orderno){
        //用户余额添加
        Member::credit_update_credit2($mid,$money,"订单：{$orderno}支付返现");
        //修改用户返现余额
        $cashBakcMoney = pdo_getcolumn(PDO_NAME."member",['id'=>$mid],'cash_back_money');
        return pdo_update(PDO_NAME."member",['cash_back_money'=>sprintf("%.2f",$cashBakcMoney + $money)],['id'=>$mid]);
    }
}