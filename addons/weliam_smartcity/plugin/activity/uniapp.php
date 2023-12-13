<?php
defined('IN_IA') or exit('Access Denied');

class ActivityModuleUniapp extends Uniapp {

    /**
     * Comment: 获取活动首页初始化数据
     * Author: wlf
     * Date: 2020/10/21 09:19
     */
    public function homeInfo(){
        global $_W,$_GPC;
        //获取设置
        $settings = Setting::agentsetting_read('activity');
        $data = [];
        //幻灯片
        $advs = pdo_getall('wlmerchant_adv',array('aid' => $_W['aid'],'uniacid' => $_W['uniacid'],'type' => 14,'enabled' => 1),array('link','thumb'),'','displayorder DESC,id DESC');
        if(!empty($advs)){
            foreach($advs as &$adv){
                $adv['thumb'] = tomedia($adv['thumb']);
            }
        }
        $data['adv'] = $advs;
        //分类
        $cates = pdo_getall('wlmerchant_activity_category',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status'=>1),array('name','id'));
        $data['cates'] = $cates;
        //社群
        if($settings['communityid'] > 0){
            $community = pdo_get('wlmerchant_community',array('id' => $settings['communityid']),array('communname','commundesc','communimg','communqrcode','systel'));
            if(!empty($community['communimg'])){
                $community['communimg'] = tomedia($community['communimg']);
            }
            if(!empty($community['communqrcode'])){
                $community['communqrcode'] = tomedia($community['communqrcode']);
            }
            $data['community'] = $community;
        }else{
            $data['community'] = [];
        }
        $this->renderSuccess('首页初始化信息',$data);
    }
    /**
     * Comment: 获取活动首页列表数据
     * Author: wlf
     * Date: 2020/10/21 10:08
     */
    public function homeList(){
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

        $set = Setting::agentsetting_read("pluginlist");
        $sort = $_GPC['sort'] ? : $set['hdsort'];
        #2、生成基本查询条件
        $where = " a.aid = {$_W['aid']} AND a.uniacid = {$_W['uniacid']} ";
        if (!empty($status)) {
            $ids = explode(',', $status);
            if (count($ids) > 1) {
                $where .= " AND a.status IN ({$status}) ";
            } else {
                $where .= " AND a.status = {$status}  ";
            }
        } else {
            $where .= " AND a.status = 2";
        }
        if($cate_id > 0) $where .= " AND a.cateid = {$cate_id} ";
        if ($is_vip == 1) $where .= " AND a.vipstatus IN (1,2) ";
        #4、生成排序条件 1=创建时间  2=店铺距离  3=推荐设置  4=浏览人气  5=商品销量
        switch ($sort) {
            case 1:
                $order = " ORDER BY a.createtime,a.id DESC ";
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
        }
        #5、按照排序方式获取商品列表
        if ($sort != 2) {
            $sql = "SELECT a.id,a.id as goods_id,IFNULL(sum(b.num),0) as buy_num FROM "
                . tablename(PDO_NAME . "activitylist")
                . " as a LEFT JOIN " . tablename(PDO_NAME . "order")
                . " as b ON a.id = b.fkid AND b.plugin = 'activity' AND b.uniacid = {$_W['uniacid']} AND b.status IN (0,1,2,3,6,9,4,8) AND b.aid = {$_W['aid']} "
                . " WHERE {$where} GROUP BY a.id {$order}" . " LIMIT {$page_start},{$page_index} ";
            $info = pdo_fetchall($sql);
        } else if ($sort == 2) {
            //店铺距离排序
            $info = pdo_fetchall("SELECT a.id as goods_id,b.id,b.location FROM "
                                 . tablename(PDO_NAME . "activitylist")
                                 . " as a RIGHT JOIN "
                                 . tablename(PDO_NAME . "merchantdata")
                                 . " as b ON a.sid = b.id WHERE {$where} ");
            $info = Store::getstores($info, $lng, $lat, 2);
            $info = array_slice($info, $page_start, $page_index);
        }
        #6、循环处理信息
        foreach ($info as $key => &$val) {
            //获取最新的商品信息
            $val = WeliamWeChat::getHomeGoods(9, $val['goods_id']);
            $val['url'] = h5_url('pages/subPages2/coursegoods/coursegoods', ['id' => $val['id']]);
            $val['status'] = strval($val['status']);
            //当商品信息中带有sid时添加店铺链接
            if ($val['sid']) {
                $val['shop_url'] = h5_url('pages/mainPages/store/index', ['sid' => $val['sid']]);
                $val['distance'] = Store::shopLocation($val['sid'], $lng, $lat);
            }
            if($is_vip > 0){
                $val['price'] = sprintf("%.2f",$val['price'] - $val['discount_price']);
            }
            //删除多余的信息
            unset($val['user_list']);
            unset($val['user_num']);
            unset($val['totalnum']);
        }
        #7、获取总页数
        if ($is_total == 1) {
            $total = pdo_fetchcolumn("SELECT count(*) FROM " . tablename(PDO_NAME . "activitylist") . " as a WHERE {$where}");
            $data['total'] = ceil($total / $page_index);
            $data['list'] = $info;

            $this->renderSuccess('同城活动信息列表', $data);
        }
        $data['list'] = $info;
        $this->renderSuccess('同城活动信息列表', $data);
    }
    /**
     * Comment: 获取活动详情页面数据
     * Author: wlf
     * Date: 2020/10/21 13:58
     */
    public function activityDetail(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        if(empty($id)){
            $this->renderError('缺少关键参数id');
        }
        $settings = Setting::agentsetting_read('activity');
        $activity = pdo_get('wlmerchant_activitylist',array('id' => $id),array('enrollnum','onelimit','status','maxpeoplenum','sid','title','cateid','thumb','activestarttime','activeendtime','enrollstarttime','enrollendtime','price','vipstatus','viparray','address','addresstype','threeurl','bgmusic','pv','enrolldetail','detail','thumbs','advs','optionstatus','lng','lat'));
        if(!empty($activity['threeurl'])){
            $data['url'] = $activity['threeurl'];
            $this->renderSuccess('页面跳转',$data);
        }else{
            unset($activity['threeurl']);
        }
        if($activity['status'] != 1 && $activity['status'] != 2 && $activity['status'] != 3){
            $this->renderError('活动不存在或已关闭');
        }
        $data = $activity;
        //修改浏览量
        Activity::changepv($id,$settings['minup'],$settings['maxup']);
        //时间
        $data['activestarttime'] = date('y-m-d H:i',$data['activestarttime']);
        $data['activeendtime'] = date('y-m-d H:i',$data['activeendtime']);
        $data['enrollstarttime'] = date('y-m-d H:i',$data['enrollstarttime']);
        $data['enrollendtime'] = date('y-m-d H:i',$data['enrollendtime']);
        //详情
        if(!empty($data['detail'])){$data['detail'] = htmlspecialchars_decode($data['detail']);}
        if(!empty($data['enrolldetail'])){$data['enrolldetail'] = htmlspecialchars_decode($data['enrolldetail']);}
        //图集,背景音乐与幻灯片
        $data['bgmusic'] = tomedia($data['bgmusic']);
        $data['thumb'] = tomedia($data['thumb']);
        $data['advs'] = unserialize($data['advs']);
        if(!empty($data['advs'])){
            foreach($data['advs'] as &$adv){
                $adv = tomedia($adv);
            }
        }
        $data['thumbs'] = unserialize($data['thumbs']);
        if(!empty($data['thumbs'])){
            foreach($data['thumbs'] as &$thumb){
                $thumb = tomedia($thumb);
            }
        }
        //商家信息
        $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $data['sid']),array('id','storename','mobile','address','lng','lat','storehours'));
        $storehours = unserialize($merchant['storehours']);
        if(!empty($storehours['startTime'])){
            $merchant['storehours'] = $storehours['startTime'] . ' - ' . $storehours['endTime'];
        }else{
            $merchant['storehours'] = '';
            foreach($storehours as $hk => $hour){
                if($hk > 0){
                    $merchant['storehours'] .= ','.$hour['startTime'] . ' - ' . $hour['endTime'];
                }else{
                    $merchant['storehours'] .= $hour['startTime'] . ' - ' . $hour['endTime'];
                }
            }
        }
        $data['storeinfo'] = $merchant;
        //最近参与与报名人数统计
        $data['salenum'] = WeliamWeChat::getSalesNum(6,$id,0,1);
        if($activity['enrollnum'] > 0){
            $data['salenum'] = $data['salenum'] + $activity['enrollnum'];
        }
        $list = pdo_fetchall("SELECT distinct mid FROM ".tablename('wlmerchant_order')."WHERE uniacid = {$_W['uniacid']} AND fkid = {$id} AND plugin = 'activity' AND status IN (1,2,3) ORDER BY createtime DESC LIMIT 5");
        if(!empty($list)){
            foreach($list as &$li){
                $li['avatar'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$li['mid']),'avatar');
                $li['avatar'] = tomedia($li['avatar']);
            }
            $data['memberlist'] = array_column($list,'avatar');
        }
        if($activity['onelimit']){
            $salesVolume = WeliamWeChat::getSalesNum(6,$id,0,1,$_W['mid']);
            $surplus = $activity['onelimit'] - $salesVolume;
        }else{
            $surplus = 9999;
        }

        //会员优惠
        if($activity['vipstatus'] == 1){
            $usercard = WeliamWeChat::VipVerification($_W['mid']);
            if($usercard['id'] > 0){
                $usercardlevel = $usercard['levelid'];
                if($usercardlevel > 0){
                    $data['levelname'] = pdo_getcolumn(PDO_NAME.'halflevel',array('id'=>$usercardlevel),'name');
                }else{
                    $data['levelname'] = $_W['wlsetting']['halflevel']['name'];
                }
            }else{
                $usercardlevel = -1;
                $data['levelname'] = '会员最多';
            }
            $data['discount_price'] = WeliamWeChat::getVipDiscount($activity['viparray'],$usercardlevel);
        }
        //多规格 与 限量购买
        if($data['optionstatus'] > 0){
            $specs = pdo_getall('wlmerchant_activity_spec',array('activityid' => $id),array('name','id','price','maxnum','viparray'));
            foreach($specs as &$sp){
                if($sp['maxnum'] > 0){
                    $sp['salenum'] = WeliamWeChat::getSalesNum(6,$id,$sp['id'],1);
                    $sp['surplus'] = $sp['maxnum'] - $sp['salenum'];
                }else{
                    $sp['surplus'] = 9999;
                }
                //会员减免
                $viparray = WeliamWeChat::mergeVipArray($sp['viparray'],$activity['viparray']);
                $sp['discount_price'] = WeliamWeChat::getVipDiscount($viparray,$usercardlevel);
            }
            $sp['surplus'] = min([$sp['surplus'],$surplus]);
            $data['specarray'] = $specs;
            $prices = array_column($specs,'price');
            $data['price'] = min($prices);
        }else{
            if($data['maxpeoplenum'] > 0){
                $data['surplus'] = $data['maxpeoplenum'] - $data['salenum'];
            }else{
                $data['surplus'] = 9999;
            }
            $data['surplus'] = min([$data['surplus'],$surplus]);
        }
        $storeSet = Setting::wlsetting_read('agentsStoreSet');
        $data['hidestoreinfo'] = $storeSet['goodshide'] ? : 0;

        $this->renderSuccess('活动详情信息',$data);
    }
    /**
     * Comment: 手机端创建活动页面初始化
     * Author: wlf
     * Date: 2020/10/22 09:42
     */
    public function createActivityPage(){
        global $_W,$_GPC;
        $sid = $_GPC['sid'];
        $id = $_GPC['id'];
        if(empty($sid)){
            $this->renderError('缺少关键参数:商户id');
        }
        $data = [];
        //分类
        $data['catelist'] = pdo_getall('wlmerchant_activity_category',array('status' => 1,'uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']),array('name','id'),'','sort DESC');
        //自定义表单
        $data['diyform'] = pdo_getall('wlmerchant_diyform',array('uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']),array('title','id'),'','sort DESC,id DESC');
        if(!empty($id)){
            $activity = pdo_get('wlmerchant_activitylist',array('id' => $id),array('status','addresstype','maxpeoplenum','minpeoplenum','title','cateid','thumb','activestarttime','activeendtime','enrollstarttime','enrollendtime','price','address','enrolldetail','detail','thumbs','advs','optionstatus','lng','lat','addresstype'));
            //状态
            if($activity['status'] == 1 || $activity['status'] == 2 || $activity['status'] == 3){
                $activity['status'] = 1;
            }else{
                $activity['status'] = 0;
            }
            //详情
           // if(!empty($activity['detail'])){$activity['detail'] = htmlspecialchars_decode($activity['detail']);}
           // if(!empty($activity['enrolldetail'])){$activity['enrolldetail'] = htmlspecialchars_decode($activity['enrolldetail']);}
            //图集与幻灯片
            $activity['thumb'] = tomedia($activity['thumb']);
            $activity['advs'] = unserialize($activity['advs']);
            if(!empty($activity['advs'])){
                foreach($activity['advs'] as &$adv){
                    $adv = tomedia($adv);
                }
            }else{
                $activity['advs'] = [];
            }
            $activity['thumbs'] = unserialize($activity['thumbs']);
            if(!empty($activity['thumbs'])){
                foreach($activity['thumbs'] as &$thumb){
                    $thumb = tomedia($thumb);
                }
            }else{
                $activity['thumbs'] = [];
            }
            //多规格
            if($activity['optionstatus'] > 0){
                $specs = pdo_getall('wlmerchant_activity_spec',array('activityid' => $id),array('name','id','price','minnum','maxnum'));
                $activity['specarray'] = $specs;
            }
            $data['activity'] = $activity;
        }
        $this->renderSuccess('创建页面初始化',$data);
    }
    /**
     * Comment: 手机端创建/编辑活动接口
     * Author: wlf
     * Date: 2020/10/22 10:46
     */
    public function createActivityApi(){
        global $_W,$_GPC;
        $sid = $_GPC['sid'];
        $id = $_GPC['id'];
        if(empty($sid)){
            $this->renderError('缺少关键参数:商户id');
        }
        $optionArray = json_decode(base64_decode($_GPC['optionArray']),true);
        $data = [];
        //商户信息
        $store = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('aid','audits','address','lng','lat','enabled'));
        if($store['enabled'] != 1){
            $this->renderError('商户未在营业中，无法进行此操作');
        }
        //图集与幻灯片
        $thumbs = trim($_GPC['thumbs']);
        if(!empty($thumbs)){
            $thumbs = explode(',' , $thumbs);
            $thumbs = serialize($thumbs);
        }else{
            $thumbs = '';
        }
        $advs = trim($_GPC['advs']);
        if(!empty($advs)){
            $advs = explode(',',$advs);
            $advs = serialize($advs);
        }else{
            $advs = '';
        }
        //储存
        $gooddata = [
            'title'                => $_GPC['title'],
            'thumb'                => $_GPC['thumb'],
            'cateid'               => trim($_GPC['cateid']),
            'thumbs'               => $thumbs,
            'advs'                 => $advs,
            'activestarttime'      => strtotime(trim($_GPC['activestarttime'])),
            'activeendtime'        => strtotime(trim($_GPC['activeendtime'])),
            'enrollstarttime'      => strtotime(trim($_GPC['enrollstarttime'])),
            'enrollendtime'        => strtotime(trim($_GPC['enrollendtime'])),
            'maxpeoplenum'         => trim($_GPC['maxpeoplenum']),
            'minpeoplenum'         => trim($_GPC['minpeoplenum']),
            'price'                => trim($_GPC['price']),
            'enrolldetail'         => htmlspecialchars_decode($_GPC['enrolldetail']),
            'detail'               => htmlspecialchars_decode($_GPC['detail']),
            'addresstype'          => trim($_GPC['addresstype']),
            'diyformid'            => $_GPC['diyformid'],
        ];
        if($gooddata['addresstype'] > 0){
            $gooddata['address'] = trim($_GPC['address']);
            $gooddata['lng'] = trim($_GPC['lng']);
            $gooddata['lat'] = trim($_GPC['lat']);
        }else{
            $gooddata['address'] = trim($store['address']);
            $gooddata['lng'] = trim($store['lng']);
            $gooddata['lat'] = trim($store['lat']);
        }
        if(count($optionArray)>0){
            $gooddata['optionstatus'] = 1;
        }
        $gooddata['status'] = $_GPC['status'];
        if($gooddata['status'] == 1 && empty($store['audits'])){
            $gooddata['status'] == 5;
        }
        //保存
        if(empty($id)){
            $gooddata['createtime'] = time();
            $gooddata['uniacid'] = $_W['uniacid'];
            $gooddata['aid'] = $store['aid'];
            $gooddata['sid'] = $sid;
            $gooddata['independent'] = 1;
            $gooddata['isdistri'] = 1;
            $res = pdo_insert(PDO_NAME.'activitylist',$gooddata);
            if($res){
                $id = pdo_insertid();
            }
        }else{
            $res = pdo_update(PDO_NAME.'activitylist',$gooddata,array('id' => $id));
        }
        if(empty($id)){
            $this->renderError('保存失败,请重试');
        }
        //多规格
        if(count($optionArray)>0){
            $specids = [];
            foreach ($optionArray as $option){
                if($option['id']>0){
                    $specids[] = $specid = $option['id'];
                    unset($option['id']);
                    pdo_update('wlmerchant_activity_spec',$option,array('id' => $specid));
                }else{
                    unset($option['id']);
                    $option['uniacid'] = $_W['uniacid'];
                    $option['activityid'] = $id;
                    pdo_insert(PDO_NAME . 'activity_spec',$option);
                    $specid[] = pdo_insertid();
                }
            }
            pdo_query('delete from ' . tablename('wlmerchant_activity_spec') . ' where activityid = '.$id.' AND id not in ('.implode(',' , $specid).')');
            $res = 1;
        }
        if($res){
            $this->renderSuccess('保存成功');
        }else{
            $this->renderError('保存商品失败,请重试');
        }
    }





}