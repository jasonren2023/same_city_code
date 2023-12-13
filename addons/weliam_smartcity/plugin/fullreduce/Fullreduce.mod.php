<?php
defined('IN_IA') or exit('Access Denied');

class Fullreduce{

    /**
     * 根据订单金额返回满减金额
     * @access static
     * @name  initSingleGoods
     * @param $price  订单金额
     * @param $id  满减活动id
     * @return $money 返回减少金额
     */
    static function getFullreduceMoney($price,$id){
        global $_W;
        $full = pdo_get('wlmerchant_fullreduce_list',array('id' => $id),array('rules','status'));
        if($full['status']>0){
            $rules = unserialize($full['rules']);
            if(!empty($rules)){
                foreach ($rules as $ru){
                    if($price >= $ru['full_money']){
                        $money = $ru['cut_money'];
                        break;
                    }
                }
            }
        }
        if($money<0 || empty($money)){
            $money = 0;
        }
        return $money;
    }


}