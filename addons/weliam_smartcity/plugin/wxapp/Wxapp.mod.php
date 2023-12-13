<?php
defined('IN_IA') or exit('Access Denied');

class Wxapp{
    static function up_down_data($type = 'up', $token = '')
    {
        global $_W;
        $appidlist = [];
        if (!empty($_W['wlsetting']['wxapp_appids'])) {
            foreach ($_W['wlsetting']['wxapp_appids'] as $wxapp_appid) {
                $appidlist[] = $wxapp_appid['sapp_appid'];
            }
        }
        $data = [
            'do'        => $type == 'up' ? 'get_upload' : 'get_sapp_code',
            'url'       => $_W['siteroot'],
            'token'     => !empty($token) ? $token['token'] : '',
            'appid'     => $_W['wlsetting']['wxapp_config']['appid'],
            'uniacid'   => $_W['uniacid'],
            'appidlist' => base64_encode(json_encode($appidlist)),
            'liveplay'  => !empty($_W['wlsetting']['wxappset']['liveplay']) ? 'yes' : '',
            'version'   => WELIAM_VERSION
        ];
        return $data;
    }
    /**
     * Comment: 直播间列表信息获取
     * Author: zzw
     * Date: 2020/10/28 16:00
     * @param $page
     * @param $pageIndex
     * @return array
     */
    public static function getLiveList($page,$pageIndex){
        global $_W;
        //条件生成
        $where = ['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']];
        //获取直播相关信息
        $total = pdo_count(PDO_NAME."live",$where);//总数获取
        $field = ['name' , 'cover_img' , 'start_time' , 'end_time' , 'anchor_name' , 'roomid' , 'goods_list' , 'live_status' , 'share_img' , 'is_playback',];
        $list = pdo_getall(PDO_NAME."live",$where,$field,'','roomid DESC',[$page,$pageIndex]);
        $time = time();
        $minute = 600;//10分钟限制
        foreach($list as &$item){
            //商品信息处理
            $goodsIds = json_decode($item['goods_list'],true);
            $item['goods'] = [];
            if(is_array($goodsIds) && count($goodsIds) > 0) $item['goods'] = self::getLiveGoodsInfo($goodsIds);
            $item['goodsnum'] = count($item['goods']);
            //时间信息处理
            $item['start_time'] = date("Y-m-d H:i" , $item['start_time']);
            $item['end_time']   = date("Y-m-d H:i" , $item['end_time']);
            $item['starttime']  = strtotime($item['start_time']);
            $item['endtime']    = strtotime($item['end_time']);
            //图片处理
            $item['cover_img'] = str_replace('http://','https://',$item['cover_img']);
            $item['share_img'] = str_replace('http://','https://',$item['share_img']);
            //状态处理
            if ($item['starttime'] > time()) $item['status'] = 1;
            else if (time() > $item['endtime']) $item['status'] = 3;
            else $item['status'] = 2;
            //判断是否可能存在回放视频  0=不存在回放  1=可能存在回放
            if($item['live_status'] == 103 && $item['is_playback'] == 0){
                //判断是否可能存在回放  直播结束后10分钟内生成  判断直播结束10分钟后才能查看回放
                $diffTime = $time - $item['end_time'];
                if($diffTime > $minute) $item['is_playback'] = 1;
            }
            //删除多余的字段
            unset($item['goods_list']);
        }
        //信息返回
        return [
            'total_page' => ceil($total / $pageIndex) ,
            'list'       => $list
        ];
    }
    /**
     * Comment: 根据ids获取直播相关商品信息
     * Author: zzw
     * Date: 2020/10/28 15:51
     * @param array|int $ids
     * @return array|bool|mixed
     */
    protected static function getLiveGoodsInfo($ids){
        //商品信息列表获取
        $goodsField = ['title' , 'goods_img' , 'wl_goods_id' , 'goods_plugin' , 'price_type' , 'price' , 'price2', 'goods_id'];
        $list = pdo_getall(PDO_NAME."live_goods",['goods_id IN'=>$ids],$goodsField);
        //循环处理信息
        foreach($list as &$goods){
            //信息处理
            $goods['name'] = $goods['title'];
            $goods['cover_img'] = $goods['goods_img'];
            $goods['url'] = Live::getWxAppPath($goods['wl_goods_id'],$goods['goods_plugin']);
            //图片处理
            $goods['cover_img'] = str_replace('http://','https://',$goods['cover_img']);
            //删除多余的信息
            unset($goods['title'],$goods['goods_img'],$goods['wl_goods_id'],$goods['goods_plugin']);
        }


        return $list;
    }

}
