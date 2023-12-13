<?php
defined('IN_IA') or exit('Access Denied');

class BargainModuleUniapp extends Uniapp {
    /**
     * Comment: 砍价商品信息列表
     * Author: zzw
     * Date: 2019/8/7 14:09
     */
    public function homeList(){
        global $_W,$_GPC;
        #1、参数获取
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * $page_index - $page_index;
        $lng = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度
        $lat = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度
        $status = $_GPC['status'] ? $_GPC['status'] : 0;
        $is_total   = $_GPC['is_total'] ? : 0;//0=不获取总页数；1=获取总页数
        $cate_id    = $_GPC['cate_id'] ? : 0;//商品分类id
        $is_vip     = $_GPC['is_vip'] ? : 0;//是否获取会员专属商品

        $time = $_GPC['time'] ?: time();//时间筛选
        $week = date("w", $time);//当前时间的星期
        if ($week == 0) $week = 7;//星期天时值的转换
        $toDay = date("j", $time);//当前时间的日期

        $set = Setting::agentsetting_read("pluginlist");
        $sort = $_GPC['sort'] ? : $set['kjsort'];
        #2、生成基本查询条件
        $where = " a.aid = {$_W['aid']} AND a.uniacid = {$_W['uniacid']} ";
        if($status > 0){
            $ids = explode(',',$status);
            if(count($ids) > 1){
                $where .= " AND a.status IN ({$status}) ";
            }else{
                $where .= " AND a.status = {$status}  ";
            }
        }else{
            $where .= " AND a.status IN (1,2)  ";
        }
        if($cate_id > 0){
            $where .= " AND a.cateid = {$cate_id} ";
        }
        if ($is_vip > 0) $where .= " AND a.vipstatus IN (1,2) ";
        $where .= " AND CASE a.usedatestatus
                        WHEN 1 THEN a.`week` LIKE '%\"{$week}\"%'
                        WHEN 2 THEN a.`day` LIKE '%\"{$toDay}\"%'
                        WHEN 0 THEN a.id > 0
                    END ";
        #3、生成排序条件
        switch ($sort) {
            case 1:$order = " ORDER BY a.createtime DESC ";break;//创建时间
            case 2:break;//店铺距离
            case 3:$order = " ORDER BY sort DESC ";break;//默认排序
            case 4:$order = " ORDER BY pv DESC ";break;//浏览人气
            case 5:$order = " ORDER BY buy_num DESC ";break;//商品销量
            case 6:$order = " ORDER BY a.sort DESC,buy_num DESC ";break;//精选  推荐、销量排序
            case 7:$order = " ORDER BY a.pv DESC,buy_num DESC ";break;//最热  浏览量、销量排序
        }
        #4、获取商品列表
        if($sort != 2){
            //普通查询
            $sql = "SELECT a.id,a.id as goods_id,IFNULL(sum(b.num),0) as buy_num FROM "
                . tablename(PDO_NAME . "bargain_activity")
                . " as a LEFT JOIN ".tablename(PDO_NAME."order")
                . " as b ON a.id = b.fkid AND b.plugin = 'bargain' AND b.uniacid = {$_W['uniacid']} AND b.status IN (1,2,3,4,8,6,7,9) "
                ."WHERE {$where} GROUP BY a.id {$order} ";
            if($is_total == 1) $total = count(pdo_fetchall($sql));
            $info = pdo_fetchall($sql." LIMIT {$page_start},{$page_index} ");
        }else{
            //关联店铺查询
            $info = pdo_fetchall("SELECT a.id as goods_id,b.id,b.location FROM "
                . tablename(PDO_NAME."bargain_activity")
                ." as a RIGHT JOIN "
                .tablename(PDO_NAME."merchantdata")
                ." as b ON a.sid = b.id WHERE {$where} ");
            $info = Store::getstores($info, $lng, $lat, 2);
            if($is_total == 1) $total = count($info);
            $info = array_slice($info,$page_start,$page_index);
        }
        #5、循环处理信息
        foreach ($info as $key => &$val) {
            //获取最新的商品信息
            $val = WeliamWeChat::getHomeGoods(7, $val['goods_id']);
            $val['url'] = h5_url('pages/subPages/goods/index',['type'=>7,'id'=>$val['id']]);
            //添加店铺链接
            $val['shop_url'] = h5_url('pages/mainPages/store/index',['sid'=>$val['sid']]);
            $val['distance'] = Store::shopLocation($val['sid'], $lng, $lat);
            if($is_vip > 0){
                $val['price'] = sprintf("%.2f",$val['price'] - $val['discount_price']);
            }
            //删除多余的信息
            unset($val['user_list']);
            unset($val['address']);
            unset($val['user_num']);
            unset($val['totalnum']);
            unset($val['sid']);
        }
        #6、信息拼装
        if($is_total == 1){
            $data['total'] = ceil($total / $page_index);
            $data['list'] = $info;

            $this->renderSuccess('砍价商品信息列表', $data);
        }


        $this->renderSuccess('砍价商品信息列表',$info);
    }
    /**
     * Comment: 参加砍价
     * Author: zzw
     * Date: 2019/8/23 14:57
     */
    public function launchBargain(){
        global $_W,$_GPC;
        #1、参数获取
        $_GPC['id'] ? $id = $_GPC['id'] : $this->renderError('缺少参数：id');//商品id
        //判断绑定手机
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array('bargain',$mastmobile)){
            $this->renderError('未绑定手机号');
        }
        #2、判断是否已经参与当前砍价活动
        $is_participate = pdo_get(PDO_NAME."bargain_userlist"
            ,['activityid'=>$id,'mid'=>$_W['mid']]);
        if($is_participate) $this->renderError("每人只能参加一次哦!");
        #3、获取商品信息
        $goods = pdo_get(PDO_NAME."bargain_activity",['id'=>$id]
            ,['sid','oldprice','joinlimit','starttime','usedatestatus','week','day','status','endtime','vipstatus','level']);
        if(!$goods) $this->renderError('商品不存在');
        #4、判断是否开始/结束
        if($goods['starttime'] > time()) $this->renderError('活动未开始');
        if($goods['endtime'] < time()) $this->renderError('活动已结束');
        if($goods['status'] != 2) $this->renderError('活动未在进行中!');
        //判断时间
        if($goods['usedatestatus'] > 0){
            $check = WeliamWeChat::checkUseDateStatus($goods['usedatestatus'],$goods['week'],$goods['day']);
            if(empty($check)){
                $this->renderError('今日活动未在进行中');
            }
        }
        #5、判断参与人数是否已满
        if($goods['joinlimit'] > 0){
            $total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename(PDO_NAME."bargain_userlist") ." WHERE activityid = {$id} "  );
            if($total >= $goods['joinlimit']) $this->renderError("参加人数已到上限!");
        }
        //判断会员
        if($goods['vipstatus'] == 2){
            $level = unserialize($goods['level']);
            $halfflag = WeliamWeChat::VipVerification($_W['mid']);
            if(empty($halfflag)){
                $this->renderError('请先开通会员才能参加此活动');
            }else if(!empty($level)){
                if(!in_array($halfflag['levelid'],$level)){
                    $this->renderError('您所在的会员等级无法参加此活动');
                }
            }
        }
        #6、生成参与砍价的信息
        $data = [
            'uniacid'=>$_W['uniacid'],//
            'aid'=>$_W['aid'],//
            'activityid'=>$id,//商品id
            'merchantid'=>$goods['sid'],//商户id
            'mid'=>$_W['mid'],//用户id
            'status'=>1,//状态 1进行中 2支付 3已失败
            'price'=>$goods['oldprice'],//当前价格
            'createtime'=>time(),//创建时间
            'updatetime'=>time(),//修改时间
        ];
        #7、储存参与信息
        $res = pdo_insert(PDO_NAME."bargain_userlist",$data);
        if ($res) $this->renderSuccess('参与成功',['id'=>pdo_insertid()]);
        else $this->renderError('参与失败');
    }
    /**
     * Comment: 用户参加砍价具体信息
     * Author: zzw
     * Date: 2019/8/23 13:49
     */
    public function detail(){
        global $_W,$_GPC;
        #1、 参数接收
        $id = $_GPC['id'] ? : 0;
        $order_id = $_GPC['order_id'] ? : 0;
        if(!$id && !$order_id) $this->renderError("缺少参数：id");
        #2、条件生成
        if($id) $where = " WHERE a.id = {$id} ";
        else $where = " WHERE a.orderid = {$order_id} ";
        if(!empty($id)){
            $usermid = pdo_getcolumn(PDO_NAME.'bargain_userlist',array('id'=>$id),'mid');
        }else{
            $usermid = pdo_getcolumn(PDO_NAME.'bargain_userlist',array('orderid'=>$order_id),'mid');
        }
        $vip = WeliamWeChat::VipVerification($usermid);
        $vipid = $vip['id'];
        $viplevelid = $vip['levelid'];
        #3、获取信息
        $field = "a.activityid as goods_id,b.stock,b.thumbs,b.name,u.nickname,u.avatar,b.endtime,a.price,b.price as goods_price,b.oldprice,b.viparray,b.vipstatus,b.bar_bgc,b.bar_image,
            (b.stock - (SELECT count(id) FROM ".tablename('wlmerchant_order')
            ." WHERE fkid = a.activityid AND plugin = 'bargain' AND status IN (0,1,2,3,4,8,6,7,9))) as stk,
            m.storename,m.address,m.location,m.mobile,a.id,a.mid,b.submitmoneylimit,a.status,a.orderid";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."bargain_userlist")
            ." as a RIGHT JOIN ".tablename(PDO_NAME."bargain_activity")
            ." as b ON a.activityid = b.id RIGHT JOIN ".tablename(PDO_NAME."merchantdata")
            ." as m ON a.merchantid = m.id RIGHT JOIN ".tablename(PDO_NAME."member")
            ." as u ON a.mid = u.id ".$where;
        $info = pdo_fetch($sql);
        if(!$info) $this->renderError('砍价信息不存在!');
        //砍价信息处理
        if($info['vipstatus'] == 1 && $vipid > 0){
            $vipdiscount = WeliamWeChat::getVipDiscount($info['viparray'],$viplevelid);
            $info['goods_price'] = sprintf("%.2f",$info['goods_price'] - $vipdiscount);
        }
        //商品图片处理
        $info['location'] = unserialize($info['location']);
        $info['thumbs'] = unserialize($info['thumbs']);
        if(is_array($info['thumbs']) && count($info['thumbs']) > 0){
            foreach($info['thumbs'] as $thumbK => &$thumbV){
                $thumbV = tomedia($thumbV);
            }
        }
        if(!empty($info['bar_image'])){
            $info['bar_image'] = tomedia($info['bar_image']);
        }
        if(empty($info['bar_bgc'])){
            $info['bar_bgc'] = '#68d3ff';
        }
        #4、获取帮砍记录
        $info['list'] = pdo_fetchall("SELECT a.mid,b.nickname,b.avatar,FROM_UNIXTIME(a.createtime,'%Y-%m-%d %H:%i:%S') as createtime,a.bargainprice as price FROM "
            .tablename(PDO_NAME."bargain_helprecord")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id WHERE a.userid = {$info['id']} ORDER BY a.createtime DESC ");
        #5、判断当前用户是否砍价
        $info['is_bargain'] = 0;//0=未砍价 1=已砍价
        if (is_array($info['list']) && count($info['list']) > 0){
            $ids = array_column($info['list'],'mid');
            if(in_array($_W['mid'],$ids)) $info['is_bargain'] = 1;
        }
        #6、获取已砍价的百分比
        $total = sprintf(   "%.2f",$info['oldprice'] - $info['goods_price']);//最多可以砍掉的价格
        $price = sprintf(   "%.2f",$info['oldprice'] - $info['price']);//已经砍掉的价格
        $info['proportion'] = sprintf("%.2f",$price / $total);
        $info['is_originator'] = $info['mid'] == $_W['mid'] ? 1 : 0;//0=不是发起者；1=是发起者
        //判断是否可以出手购买
        if($info['status'] == 1 && $info['price'] <= $info['submitmoneylimit'] && $info['is_originator'] == 1 && empty($info['orderid'])){
            $info['buytip'] = 1;
        }


        //砍价排行榜
        $rank = pdo_fetchall("SELECT mid,price,updatetime FROM ".tablename('wlmerchant_bargain_userlist')."WHERE uniacid = {$_W['uniacid']} AND activityid = {$info['goods_id']}  ORDER BY price ASC,updatetime ASC LIMIT 10");

        if(!empty($rank)){
            foreach ($rank as &$ra){
                $member = pdo_get('wlmerchant_member',array('id' => $ra['mid']),array('nickname','encodename','avatar'));
                $ra['nickname'] = !empty($member['encodename']) ? base64_decode($member['encodename']) : $member['nickname'];
                $ra['avatar'] = tomedia($member['avatar']);
                $ra['updatetime'] = date('Y-m-d H:i:s',$ra['updatetime']);
            }
        }
        $info['rank'] = $rank;

        $this->renderSuccess('用户参加砍价具体信息',$info);
    }
    /**
     * Comment: 砍价操作
     * Author: zzw
     * Date: 2019/8/23 18:04
     */
    public function cut(){
        global $_W,$_GPC;
        #1、参数获取
        $_GPC['id'] ? $id = $_GPC['id'] : $this->renderError('缺少参数：id');//参与砍价的信息id
        $tableHelp = PDO_NAME."bargain_helprecord";
        $tableList = PDO_NAME."bargain_userlist";
        //判断是否绑定手机
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array('helpbargain',$mastmobile)){
            $this->renderError('未绑定手机号');
        }
        #2、获取砍价参与信息
        MysqlFunction::setTrans(4);
        MysqlFunction::startTrans();
        $info = pdo_get($tableList,['id'=>$id] ,['activityid','mid','status','price','orderid']);
        if(!$info) $this->renderError('参加信息不存在');
        if($info['status'] != 1) $this->renderError('当前已不可进行砍价!');
        #3、判断当前用户是否已砍过
        $is_cut = pdo_get($tableHelp,['userid'=>$id,'mid'=>$_W['mid']]);
        if($is_cut) $this->renderError("请不要重复操作!");
        #4、设置信息获取
        $set = Setting::agentsetting_read("bargainset");
        $day = date("Y-m-d",time());
        $dayStart = strtotime($day." 00:00:00");
        $dayEnd   = strtotime($day." 23:59:59");
        #5、判断当前用户今日砍价次数是否已到上限
        if($set['syslimit'] > 0){
            $is_max = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename($tableHelp)
                ." WHERE mid = {$_W['mid']} AND createtime > {$dayStart} AND createtime < {$dayEnd} ");
            if($is_max >= $set['syslimit']) $this->renderError('今日砍价次数已到上限!');
        }
        #6、获取商品信息
        $goods = pdo_get(PDO_NAME."bargain_activity",['id'=>$info['activityid']]
            ,['helplimit','dayhelpcount','onlytimes','viparray','usedatestatus','week','day','status','vipstatus','rules','price','endtime',]);
        //判断时间
        if($goods['usedatestatus'] > 0){
            $check = WeliamWeChat::checkUseDateStatus($goods['usedatestatus'],$goods['week'],$goods['day']);
            if(empty($check)){
                $this->renderError('今日活动未在进行中');
            }
        }
        #7、更具用户是否为会员获取低价信息
        $vipInfo = WeliamWeChat::VipVerification($info['mid']);
        $vipId = $vipInfo['id'];
        $vipLevel = $vipInfo['levelid'];
        if($goods['vipstatus'] ==1 && $vipId > 0){
            $vipdiscount = WeliamWeChat::getVipDiscount($goods['viparray'],$vipLevel);
            $floorPrice = sprintf("%.2f", $goods['price'] - $vipdiscount);
        }else{
            $floorPrice = $goods['price'];
        }
        #8、判断是否已经到达低价
        if($info['price'] <= $floorPrice) $this->renderError("已被砍到底价!");
        if($goods['endtime'] <= time()) $this->renderError('活动已结束!');
        if($goods['status'] != 2) $this->renderError('活动未在进行中!');
        #9、好友帮砍限制数量  限制当前活动商品,最多多少好友帮忙砍价.0或空则无限
        if($goods['helplimit'] > 0){
            $totalNum = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename($tableHelp) ." WHERE userid = {$id} ");
            if($totalNum >= $goods['helplimit']) $this->renderError('帮砍好友已到上限!');
        }
        #10、每天帮砍好友人数限制  限制每天帮助的好友数量.0或空则无限
        if($goods['dayhelpcount'] > 0){
            $dayTotalNum = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename($tableHelp)
                ." WHERE userid = {$id} AND authorid != mid AND createtime > {$dayStart} AND createtime < {$dayEnd} ");
            if($dayTotalNum >= $goods['dayhelpcount']) $this->renderError('今日帮砍好友已到上限!');
        }
        #11、判断该商品每人可砍价次数
        if($goods['onlytimes'] > 0){
            $dayTotalNum = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename($tableHelp)
                ." WHERE activityid = {$info['activityid']} AND mid = {$_W['mid']} AND authorid != {$_W['mid']} ");
            if($dayTotalNum >= $goods['onlytimes']) $this->renderError('当前商品的帮砍次数已达上限!');
        }
        #12、生成应该砍掉的价格
        $ruleList = unserialize($goods['rules']);
        $startPrice = 0.5;
        $endPrice = 1;
        if(is_array($ruleList) && count($ruleList) > 0){
            $orderWhere = array_column($ruleList,'rule_pice');
            array_multisort($orderWhere, SORT_DESC,  $ruleList);
            foreach($ruleList as $priceK => $priceV){
                if($info['price'] >= $priceV['rule_pice']){
                    $startPrice = $priceV['rule_start'];
                    $endPrice = $priceV['rule_end'];
                    break;
                }
            }
        }
        $price = sprintf("%.2f",$startPrice+mt_rand()/mt_getrandmax()*($endPrice-$startPrice));
        if($price < 0.01){
            $price = 1;
        }
        $newPayPrice = sprintf("%.2f",$info['price'] - $price);//新的支付价格
        if($newPayPrice < $floorPrice){
            $price = sprintf("%.2f",$info['price'] - $floorPrice);
            $newPayPrice = sprintf("%.2f",$info['price'] - $price);//新的支付价格
        }
        #13、修改参与砍价信息中的价格信息
        $save = pdo_update($tableList,['price'=>$newPayPrice],['id'=>$id]);
        if(!$save){
            MysqlFunction::rollback();
            $this->renderError('砍价失败，请刷新重新!');
        }
        #14、储存砍价记录信息
        $data = [
            'uniacid'      => $_W['uniacid'] ,
            'aid'          => $_W['aid'] ,
            'activityid'   => $info['activityid'] ,//商品id
            'userid'       => $id ,//参与信息的id
            'authorid'     => $info['mid'] ,//发起人id
            'mid'          => $_W['mid'] ,//当前用户id
            'bargainprice' => $price ,//砍价价格
            'afterprice'   => $newPayPrice ,//砍后价格
            'createtime'   => time() ,//创建时间
        ];
        $res = pdo_insert($tableHelp,$data);
        if($res){
            if($info['mid'] != $_W['mid']){
                //帮砍操作 发送信息通知砍价发起人
                $modelData = [
                    'first'   => '' ,
                    'type'    => '砍价提醒' ,//业务类型
                    'content' => '您的好友'.$_W['wlmember']['nickname'].'成功帮您砍下'.$price.'元' ,//业务内容
                    'status'  => '当前价格'.$newPayPrice."元" ,//处理结果
                    'time'    => date("Y-m-d H:i:s",$data['createtime']) ,//操作时间
                    'remark'  => ''
                ];
                $link = h5_url('pages/subPages/bargin/barginDetail/barginDetail',['bargin_id'=>$id]);
                TempModel::sendInit('service',$info['mid'],$modelData,$_W['source'],$link);
            }
            MysqlFunction::commit();
            $this->renderSuccess('砍价成功',['price'=>$price]);
        }else{
            MysqlFunction::rollback();
            $this->renderError('砍价失败，请刷新重新!');
        }
    }

    /**
     * Comment: 获取砍价分类列表
     * Author: wlf
     * Date: 2020/09/24 14:06
     */
    public function cateList(){
        global $_W , $_GPC;
        $list = pdo_getall('wlmerchant_bargain_category',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'is_show' => 0),array('id','name'), '' , 'sort DESC');
        $this->renderSuccess('砍价分类',$list);
    }


}