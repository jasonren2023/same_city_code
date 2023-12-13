<?php
defined('IN_IA') or exit('Access Denied');

class RushModuleUniapp extends Uniapp {
    /**
     * Comment: 获取抢购商品信息列表
     * Author: zzw
     * Date: 2019/8/6 18:38
     */
    public function homeList() {
        global $_W, $_GPC;
        #1、参数获取
        $page       = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * $page_index - $page_index;
        $lng        = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度
        $lat        = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度
        $status     = !empty($_GPC['status']) ? $_GPC['status'] : '';
        $is_total   = $_GPC['is_total'] ? : 0;//0=不获取总页数；1=获取总页数
        $cate_id    = $_GPC['cate_id'] ? : 0;//商品分类id
        $is_vip     = $_GPC['is_vip'] ? : 0;//是否获取专属商品

        $time = $_GPC['time'] ?: time();//时间筛选
        $week = date("w", $time);//当前时间的星期
        if ($week == 0) $week = 7;//星期天时值的转换
        $toDay = date("j", $time);//当前时间的日期

        $set = Setting::agentsetting_read("pluginlist");
        $sort = $_GPC['sort'] ? : $set['qgsort'];
        #2、生成基本查询条件
        $where = " a.aid = {$_W['aid']} AND a.uniacid = {$_W['uniacid']} ";

        $where .= " AND CASE a.usedatestatus
                        WHEN 1 THEN a.`week` LIKE '%\"{$week}\"%'
                        WHEN 2 THEN a.`day` LIKE '%\"{$toDay}\"%'
                        WHEN 0 THEN a.id > 0
                    END ";

        if (!empty($status)) {
            $ids = explode(',', $status);
            if (count($ids) > 1) {
                $where .= " AND a.status IN ({$status}) ";
            } else {
                $where .= " AND a.status = {$status}  ";
            }
        } else {
            $where .= " AND a.status IN (1,2)  ";
        }
        if($cate_id > 0){
            $where .= " AND a.cateid = {$cate_id} ";
        }
        if ($is_vip == 1) $where .= " AND a.vipstatus IN (1,2) ";
        #4、生成排序条件 1=创建时间  2=店铺距离  3=推荐设置  4=浏览人气  5=商品销量
        switch ($sort) {
            case 1:
                $order = " ORDER BY a.id DESC ";
                break;//创建时间
            case 2:
                break;//店铺距离
            case 3:
                $order = " ORDER BY a.sort DESC,a.id DESC ";
                break;//默认排序
            case 4:
                $order = " ORDER BY a.pv DESC,a.id DESC ";
                break;//浏览人气
            case 5:
                $order = " ORDER BY buy_num DESC,a.id DESC ";
                break;//商品销量
            case 6:
                $order = " ORDER BY a.sort DESC,buy_num DESC,a.id DESC ";
                break;//超值精选  推荐、销量排序
            case 7:
                $order = " ORDER BY a.pv DESC,buy_num DESC,a.id DESC ";
                break;//热门好货  浏览量、销量排序
            case 8:
                $order = " ORDER BY a.sort DESC,pv DESC,a.id DESC ";
                break;//即将开场  未开始、推荐、浏览量
        }
        #5、按照排序方式获取商品列表
        if ($sort != 2) {
            $sql = "SELECT a.id,a.id as goods_id,(IFNULL(sum(b.num),0) + a.allsalenum) as buy_num FROM "
                . tablename(PDO_NAME . "rush_activity")
                . " as a LEFT JOIN " . tablename(PDO_NAME . "rush_order")
                . " as b ON a.id = b.activityid AND b.uniacid = {$_W['uniacid']} AND b.status IN (0,1,2,3,6,9,4,8) AND b.aid = {$_W['aid']} "
                . " WHERE {$where} GROUP BY a.id {$order}" . " LIMIT {$page_start},{$page_index} ";
            $info = pdo_fetchall($sql);
        } else if ($sort == 2) {
            //店铺距离排序
            $info = pdo_fetchall("SELECT a.id as goods_id,b.id,b.location FROM "
                . tablename(PDO_NAME . "rush_activity")
                . " as a LEFT JOIN "
                . tablename(PDO_NAME . "merchantdata")
                . " as b ON a.sid = b.id WHERE {$where} ");
            $info = Store::getstores($info, $lng, $lat, 2);
            $info = array_slice($info, $page_start, $page_index);
        }
        #6、循环处理信息
        foreach ($info as $key => &$val) {
            //获取最新的商品信息
            $val = WeliamWeChat::getHomeGoods(1, $val['goods_id']);
            $val['url'] = h5_url('pages/subPages/goods/index', ['type' => 1, 'id' => $val['id']]);
            $val['status'] = strval($val['status']);
            //当商品信息中带有sid时添加店铺链接
            if ($val['sid'] > 0) {
                $val['shop_url'] = h5_url('pages/mainPages/store/index', ['sid' => $val['sid']]);
                $val['distance'] = Store::shopLocation($val['sid'], $lng, $lat);
            }else{
                $val['storename'] = '平台商品';
            }
            //一卡通首页 会员特供时用普通价覆盖折扣价
            if($is_vip == 1){
                $val['price'] = sprintf("%.2f",$val['price'] - $val['discount_price']);
            }
            //删除多余的信息
            unset($val['user_list']);
            unset($val['address']);
            unset($val['user_num']);
            unset($val['totalnum']);
        }
        #7、获取总页数
        if ($is_total == 1) {
            $total = pdo_fetchcolumn("SELECT count(*) FROM " . tablename(PDO_NAME . "rush_activity") . " as a WHERE {$where}");
            $data['total'] = ceil($total / $page_index);
            $data['list'] = $info;

            $this->renderSuccess('抢购商品信息列表', $data);
        }

        $this->renderSuccess('抢购商品信息列表', $info);
    }
    /**
     * Comment: 获取抢购专题信息
     * Author: zzw
     * Date: 2019/8/14 9:51
     */
    public function specialInfo() {
        global $_W, $_GPC;
        #1、参数获取
        $id = intval($_GPC['id']) OR $this->renderError('缺少参数：专题id');
        $lng = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度
        $lat = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度
        #2、获取专题信息
        $info = pdo_get(PDO_NAME . "rush_special", ['id' => $id], ['bgcolor','title', 'thumb', 'share_title', 'imglink','share_desc', 'rule']);
        $info['rule'] = htmlspecialchars_decode($info['rule']);
        $info['thumb'] = tomedia($info['thumb']);
        $info['bgcolor'] = $info['bgcolor'] ? : '#FF4444';
        #3、获取抢购商品列表
        $sql = " SELECT a.id,b.location FROM ".tablename(PDO_NAME."rush_activity")
            ." as a LEFT JOIN ".tablename(PDO_NAME."merchantdata")
            ." as b ON a.sid = b.id WHERE a.status = 2 AND a.specialid = {$id} ORDER BY sort DESC ";
        $list = pdo_fetchall($sql);
        if (is_array($info) && count($list) > 0) {
            foreach ($list as $key => &$val) {
                //距离获取
                $location = unserialize($val['location']);
                $distance = Store::getdistance($location['lng'], $location['lat'], $lng, $lat);
                if (!empty($distance)) {
                    if($distance > 9999998){
                        $distance = " ";
                    }else if ($distance > 1000) {
                        $distance = (floor(($distance / 1000) * 10) / 10) . "km";
                    } else {
                        $distance = round($distance) . "m";
                    }
                }
                //获取抢购信息
                $val = WeliamWeChat::getHomeGoods(1, $val['id']);
                $val['distance'] = $distance;
                $val['url'] = h5_url('pages/subPages/goods/index',['id'=>$val['id'],'type'=>1]);
                unset($val['user_list']);
                unset($val['buy_limit']);
                unset($val['totalnum']);
                unset($val['allsalenum']);
                unset($val['plugin']);
                unset($val['user_num']);
                unset($val['pay_state']);
                unset($val['address']);
            }
        }
        $info['list'] = $list;

        $this->renderSuccess("抢购专题信息", $info);
    }
    /**
     * Comment: 抢购商品关注接口（仅允许关注未开始的商品）
     * Author: zzw
     * Date: 2019/11/12 16:50
     */
    public function follow(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'] OR $this->renderError('网络错误，请刷新重试！');
        //判断是取消还是关注
        $flagid = pdo_getcolumn(PDO_NAME.'rush_follows',array('actid'=>$id,'mid'=>$_W['mid']),'id');
        if($flagid>0){
            pdo_delete(PDO_NAME.'rush_follows',array('id'=>$flagid));
            $this->renderSuccess('取消关注成功!');
        }else{
            #2、计算提醒信息发送时间
            $config = Setting::agentsetting_read('rush');
            if($config['follow_time']) $sendTime = time() + 60*$config['follow_time'];
            else $sendTime = time() + 600;
            #3、判断是否符合发送提醒信息的条件
            $startTime = pdo_getcolumn(PDO_NAME."rush_activity",['id'=>$id],'starttime');
            if($startTime < time()) $this->renderError('只能关注未开始的商品哦!');
            if($startTime < $sendTime) $this->renderError('即将开始，无需关注');
            $isFollow = pdo_getcolumn(PDO_NAME.'rush_follows',['mid'=>$_W['mid'],'actid'=>$id],'id');
            if($isFollow) $this->renderError('请勿重复关注!');
            #4、关注成功，记录将要发送的提示信息
            $data = [
                'uniacid'  => $_W['uniacid'] ,
                'mid'      => $_W['mid'] ,
                'aid'      => $_W['aid'] ,
                'actid'    => $id ,
                'sendtime' => ($startTime - ($sendTime - time()))
            ];
            $res = pdo_insert(PDO_NAME.'rush_follows',$data);
            if($res) $this->renderSuccess('关注成功!');
            else $this->renderError('网络错误，请刷新重试!');
        }
    }

    /**
     * Comment: 获取抢购分类列表
     * Author: wlf
     * Date: 2020/09/21 14:13
     */
    public function cateList(){
        global $_W , $_GPC;
        $list = pdo_getall('wlmerchant_rush_category',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'is_show' => 0),array('id','name'), '' , 'sort DESC');
        $this->renderSuccess('抢购分类',$list);
    }


}




































