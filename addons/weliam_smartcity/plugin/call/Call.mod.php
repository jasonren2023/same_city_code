<?php
defined('IN_IA') or exit('Access Denied');

class Call{


    /**
     * Comment: 获取奖品信息
     * Author: zzw
     * Date: 2022/8/03 09:19
     * @param $id
     * @return bool|mixed
     */
    public static function prizeInfo($id){
        //获取奖品信息
        $info = pdo_get(PDO_NAME."call_goods",['id'=>$id]);
        //处理奖品信息   奖品类型:1=现金红包,2=线上红包,3=积分,4=激活码,5=商品
        switch ($info['type']){
            case 1:break;//现金红包
            case 2:
                $field = "id as red_pack_id,title as red_pack_name";
                $where = " id = {$info['goods_id']} ";
                $redPack = self::getInfo($field,"redpacks",$where);
                //赋值
                $info['red_pack_id'] = $redPack['red_pack_id'];
                $info['red_pack_name'] = $redPack['red_pack_name'];
                break;//线上红包
            case 3:break;//积分
            case 4:break;//激活码  rush  groupon  wlfightgroup  coupon  bargain
            case 5:
                //根据商品类型获取商品信息
                switch ($info['goods_plugin']) {
                    case 'rush':
                        $field = "name as goods_name";
                        $where = " id = {$info['goods_id']} ";
                        $goods = self::getInfo($field,"rush_activity",$where);
                        break;//抢购
                    case 'groupon':
                        $field = "name as goods_name";
                        $where = " id = {$info['goods_id']} ";
                        $goods = self::getInfo($field,"groupon_activity",$where);
                        break;//团购
                    case 'wlfightgroup':
                        $field = "name as goods_name";
                        $where = " id = {$info['goods_id']} ";
                        $goods = self::getInfo($field,"fightgroup_goods",$where);
                        break;//拼团
                    case 'coupon':
                        $field = "title as goods_name";
                        $where = " id = {$info['goods_id']} ";
                        $goods = self::getInfo($field,"couponlist",$where);
                        break;//卡卷
                    case 'bargain':
                        $field = "name as goods_name";
                        $where = " id = {$info['goods_id']} ";
                        $goods = self::getInfo($field,"bargain_activity",$where);
                        break;//砍价
                }
                //赋值
                $info['goods_name'] = $goods['goods_name'];
                break;//商品
        }

        return $info;
    }


    /**
     * Comment: 根据内容获取信息
     * Author: zzw
     * Date: 2020/9/15 10:41
     * @param $field
     * @param $table
     * @param $where
     * @return array
     */
    protected static function getInfo($field,$table,$where){
        $info = pdo_fetch("SELECT {$field} FROM " .tablename(PDO_NAME.$table)." WHERE ".$where);

        return $info ? : [];
    }
	
}