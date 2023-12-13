<?php
defined('IN_IA') or exit('Access Denied');

class GoodsModuleUniapp extends Uniapp {
    /**
     * Comment: 获取商品详细信息
     * Author: zzw
     * Date: 2019/8/13 18:34
     */
    public function getGoodsDetail(){
        global $_W,$_GPC;
        #1、参数获取
        $id      = $_GPC['id'];//商品id
        $type    = $_GPC['type'];//商品类型：1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品 8=积分商品 9活动
        $mid     = $_W['mid'] ? : 0;//用户id
        if(empty($id)) $this->renderError('缺少参数：商品id');
        if(empty($type)) $this->renderError('缺少参数：商品类型');
        #2、调用方法获取公共商品数据信息
        WeliamWeChat::browseRecord($type, $id);//记录当前商品的浏览记录
        $info = WeliamWeChat::getHomeGoods($type, $id);
        if(empty($info['id'])){
            $this->renderError('商品不存在或已被删除',['url'=>h5_url('pages/mainPages/index/index')]);
        }
        unset($info['address']);
        unset($info['storename']);
        #3、获取商品补充数据信息
        switch ($type) {
            case 1:
                $tableName = "rush_activity";
                $table = tablename(PDO_NAME."rush_activity");
                $field = 'fullreduceid,paidid,drawid,thumbs,detail,`describe`,pv,creditmoney,threestatus,pftotherinfo,alldaylimit,daylimit,monthlimit,usedatestatus,week,day,tag,isdistri,retainage,share_title,share_image,share_desc,lp_status,lp_set,unit,is_describe_tip,bgmusic,videourl';
                $commentPlugin = 'rush';
                $saletype = 1;
                break;//抢购商品
            case 2:
                $tableName = "groupon_activity";
                $table = tablename(PDO_NAME."groupon_activity");
                $field = 'fullreduceid,paidid,drawid,thumbs,detail,`describe`,pv,creditmoney,threestatus,pftotherinfo,alldaylimit,daylimit,usedatestatus,week,day,tag,isdistri,retainage,share_title,share_image,share_desc,unit,is_describe_tip,bgmusic,videourl';
                $commentPlugin = 'groupon';
                $saletype = 2;
                break;//团购商品
            case 3:
                $tableName = "fightgroup_goods";
                $table = tablename(PDO_NAME."fightgroup_goods");
                $field = 'fullreduceid,paidid,drawid,adv as thumbs,detail,pv,alldaylimit,creditmoney,daylimit,usedatestatus,week,day,tag,aloneprice,limitstarttime as starttime,limitendtime as endtime,isdistri,share_title,share_image,share_desc,`describe`,unit,is_describe_tip,bgmusic,videourl';
                $commentPlugin = 'wlfightgroup';
                $saletype = 3;
                break;//拼团商品
            case 4:
                $tableName = "package";
                $table = tablename(PDO_NAME."package");
                $field = '`describe`,pv,usedatestatus,week,day';
                $commentPlugin = 'package';
                $saletype = 0;
                break;//大礼包
            case 5:
                $tableName = "couponlist";
                $table = tablename(PDO_NAME."couponlist");
                $field = 'fullreduceid,diyformid,paidid,drawid,goodsdetail as detail,description as `describe`,pv,creditmoney,alldaylimit,daylimit,usedatestatus,week,day,is_charge,isdistri,starttime,is_describe_tip,endtime,time_type,adv as thumbs';
                $commentPlugin = 'coupon';
                $saletype = 4;
                break;//优惠券
            case 6:
                $tableName = "halfcardlist";
                $table = tablename(PDO_NAME."halfcardlist");
                $field = 'detail,`limit` as `describe`,pv';
                $commentPlugin = 'halfcard';
                $saletype = 0;
                break;//折扣卡
            case 7:
                $tableName = "bargain_activity";
                $table = tablename(PDO_NAME."bargain_activity");
                $field = 'fullreduceid,paidid,drawid,thumbs,`describe`,detail,pv,creditmoney,usedatestatus,week,day,isdistri,share_title,share_image,share_desc,unit,is_describe_tip,bgmusic,videourl';
                $commentPlugin = 'bargain';
                $info['vipprice'] = $info['price'] - $info['discount_price'];
                $saletype = 5;
                break;//砍价商品
            case 8:
                $tableName = "consumption_goods";
                $table = tablename(PDO_NAME."consumption_goods");
                $field = 'usedatestatus,week,day,chance';
                $commentPlugin = 'consumption';
                $info['logo'] = $info['thumb'];
                $saletype = 0;
                break;//积分商品
        }
        if(!empty($field)) {
            $where = " WHERE uniacid = {$_W['uniacid']} AND id = {$id}";
            $goods = pdo_fetch("SELECT {$field} FROM " . $table . $where);
            $info  = array_merge($info , $goods);
            //修改商品人气（浏览量）信息
            $pv = intval($info['pv']) + 1;
            pdo_update(PDO_NAME.$tableName,['pv'=>$pv],['id'=>$id]);
        }
        #4、处理商品数据信息
        //分享信息的处理
        $info['share_title'] = $info['share_title'] ? : $info['goods_name'];
        $info['share_image'] = tomedia($info['share_image']) ? : $info['logo'];
        $info['share_desc']  = $info['share_desc'] ? : '';
        //处理轮播图信息
        $info['thumbs'] = is_array(unserialize($info['thumbs'])) ? unserialize($info['thumbs']) : [];
        if(is_array($info['thumbs']) && count($info['thumbs']) > 0){
            foreach ($info['thumbs'] as $thumbKey => &$thumbVal) {
                $thumbVal = tomedia($thumbVal);
            }
        }
        //视频信息
        if(!empty($info['videourl'])){
            $info['videourl'] = tomedia($info['videourl']);
        }
        //处理商品标签信息
        if($info['tag']){
            $tag = unserialize($info['tag']);
            if(is_array($tag) && count($tag) > 0){
                if(count($tag) > 1){
                    $tagIds = implode(',',$tag);
                    $tagWhere = " WHERE id IN ({$tagIds}) ";
                }else{
                    $tagWhere = " WHERE id = {$tag[0]} ";
                }
                $tagList = pdo_fetchall("SELECT title,content FROM ".tablename(PDO_NAME."tags").$tagWhere.' ORDER BY sort DESC,id DESC');
                if(is_array($tagList) && count($tagList) > 0){
                   $info['tag_list'] = array_column($tagList,'title');
                   $info['tagslist'] = $tagList;
                }
            }
            unset($info['tag']);
        }
        if($info['appointstatus']>0){
            $stagt = '需提前预约';
            $info['tag_list'][] = $stagt;
            if($info['appointment'] > 0){
                $info['tagslist'][] = array( 'title'=> $stagt,'content'=> '消费本商品请至少提前'.$info['appointment'].'小时联系商家预约');
            }else{
                $info['tagslist'][] = array( 'title'=> $stagt,'content'=> '消费本商品请提前联系商家预约');
            }
        }
        if($info['allowapplyre']>0){
            $stagt = '不退款商品';
            $info['tag_list'][] = $stagt;
            $info['tagslist'][] = array( 'title'=> $stagt,'content'=> '本商品是特殊商品，不支持购买后退款操作');
        }
        if($info['creditmoney']>0){
            $jifen = $_W['wlsetting']['trade']['credittext'] ? : '积分';
            $stagt = $jifen.'抵扣';
            $info['tag_list'][] = $stagt;
            $info['tagslist'][] = array('title'=> $stagt,'content'=> '每份商品最多可以使用'.$jifen.'抵扣'.$info['creditmoney'].'元');
        }
        if($info['drawid'] > 0){
            $draw = pdo_get('wlmerchant_luckydraw',array('id' => $info['drawid'],'status' => 1),array('title','status'));
            if(!empty($draw)){
                $info['tag_list'][] = '抽奖活动';
                $info['tagslist'][] = array('title'=> '抽奖活动','content'=> '购买商品核销或收货以后可以参与【'.$draw['title'].'】抽奖活动');
            }
        }
        if($info['paidid'] > 0){
            $paid = pdo_get('wlmerchant_payactive',array('id' => $info['paidid'],'status' => 1),array('title'));
            if(!empty($paid)){
                $info['tag_list'][] = '支付有礼';
                $info['tagslist'][] = array('title'=> '支付有礼','content'=> '购买商品支付以后可以参与【'.$paid['title'].'】活动奖励');
            }
        }
        if($info['fullreduceid'] > 0){
            $fullreduce = pdo_get('wlmerchant_fullreduce_list',array('id' => $info['fullreduceid'],'status' => 1),array('title'));
            if(!empty($fullreduce)){
                $info['tag_list'][] = '满减活动';
                $info['tagslist'][] = array('title'=> '满减活动','content'=> '购买商品时会享受【'.$fullreduce['title'].'】满减优惠');
            }
        }
        //处理商品详情/购买须知
        if(!empty($info['detail']) && is_base64($info['detail'])) $info['detail'] = htmlspecialchars_decode(base64_decode($info['detail']));
        else if(!empty($info['detail'])) $info['detail'] = htmlspecialchars_decode($info['detail']);

        if(!empty($info['describe']) && is_base64($info['describe'])) $info['describe'] = htmlspecialchars_decode(base64_decode($info['describe']));
        else if(!empty($info['describe'])) $info['describe'] = htmlspecialchars_decode($info['describe']);
        //处理音乐信息
        if($info['bgmusic']) $info['bgmusic'] = tomedia($info['bgmusic']);
        #5、获取商户信息
        if($info['sid'] > 0){
            $shop = pdo_fetch("SELECT id as sid,logo,address,mobile,storename,storehours,location FROM ".
                tablename(PDO_NAME."merchantdata")." WHERE id = {$info['sid']} ");
            $shop['logo'] = tomedia($shop['logo']);
            $storehours = unserialize($shop['storehours']);
            $shop['storehours'] = $storehours['startTime'].' - '.$storehours['endTime'];
            if(!empty($storehours['startTime'])){
                $shop['storehours'] = $storehours['startTime'] . ' - ' . $storehours['endTime'];
            }else{
                $shop['storehours'] = '';
                foreach($storehours as $hk => $hour){
                    if($hk > 0){
                        $shop['storehours'] .= ','.$hour['startTime'] . ' - ' . $hour['endTime'];
                    }else{
                        $shop['storehours'] .= $hour['startTime'] . ' - ' . $hour['endTime'];
                    }
                }
            }
            $shop['location'] = unserialize($shop['location']);
            $info['shop'] = $shop;
        }else{
            $info['sid'] = 0;
        }
        //认证
        if(p('attestation')){
            $info['attestation'] = Attestation::checkAttestation(2,$info['sid']);
        }else{
            $info['attestation'] = 0;
        }
        #6、获取商品评价信息
        $comment = pdo_fetchall("SELECT headimg,nickname,createtime,star,pic,text,replytextone,replypicone FROM ".tablename(PDO_NAME.'comment')."WHERE gid = {$id}  AND plugin = '{$commentPlugin}' AND status = 1 AND (checkone = 2 OR mid = {$mid}) ORDER BY createtime DESC LIMIT 5 ");
        //处理评论信息
        foreach($comment as $commentKey => &$commentVal){
            //用户评论图片的处理
            $commentPic = unserialize($commentVal['pic']);
            if(is_array($commentPic) && count($commentPic) > 0){
                foreach($commentPic as $picKey => &$picVal){
                    if($picVal) $picVal = tomedia($picVal);
                    else unset($commentPic[$picKey]);
                }
            }else{
                $commentPic = [];
            }
            $commentVal['pic'] = array_values($commentPic);
            //商家回复信息图片的处理
            $replyPic = unserialize($commentVal['replypicone']);
            if(is_array($replyPic) && count($replyPic) > 0){
                foreach($replyPic as $replyPicKey => &$replyPicVal){
                    $replyPicVal = tomedia($replyPicVal);
                }
            }else{
                $replyPic = [];
            }
            $commentVal['replypicone'] = array_values($replyPic);
        }
        $info['comment'] = $comment;
        #7、获取拼团商品详情时 获取拼团已开团但未成团的信息列表
        if($type == 3){
            $info['group_list'] = pdo_fetchall("SELECT a.id,a.failtime,a.neednum,a.lacknum,m.nickname,m.avatar FROM " .tablename(PDO_NAME."fightgroup_group")
                                      ." as a RIGHT JOIN " .tablename(PDO_NAME."order")
                                      ." as b ON a.id = b.fightgroupid RIGHT JOIN ".tablename(PDO_NAME."member")
                                      ." as m ON b.mid = m.id "
                                      ." WHERE a.goodsid = {$id} AND a.uniacid = {$_W['uniacid']} AND a.status = 1 "
                                      ."  GROUP BY b.fightgroupid ORDER BY a.starttime ASC  LIMIT 5 ");
        }
        #8、分销助手，获取当前用户分享最高可以获得的佣金
        if(empty($info['isdistri'])){
            $info['dis_assistant'] = WeliamWeChat::getDisInfo($type,$id);
        }else{
            $info['dis_assistant'] = [];
        }
        #10、社群信息获取
        if ($info['communityid'] > 0) {
            $community = Commons::getCommunity($info['communityid']);
            $info['community'] = is_array($community) ? $community : [];
        }
        #11、判断用户是否已经参与砍价
        if($type == 7){
            $info['is_participate'] = pdo_getcolumn(PDO_NAME."bargain_userlist"
                ,['activityid'=>$id,'mid'=>$_W['mid']] ,'id');
        }
        #12、判断当前商品的状态：1=未开始，2=已开始，3=已结束
        if(in_array($type,[1,2,3,5,7])){
            //基本判断
            if($info['starttime'] > time()){
                //开始时间大于当前时间  未开始
                $info['sales_status'] = 1;
            }else if($info['endtime'] > time()){
                //结束时间大于当前时间  已开始未结束
                $info['sales_status'] = 2;
            }else if($info['endtime'] < time()){
                //结束时间小于当前时间 已结束
                $info['sales_status'] = 3;
            }
            //卡券补丁判断 时间段为领取后限制则固定为 已开始未结束
            if($type == 5 && $info['time_type'] == 2){
                $info['sales_status'] = 2;
            }
            if($info['usedatestatus'] > 0 ){
                $stagt = '定时发售';
                if($info['usedatestatus'] == 1){
                    $content = '此商品每周';
                    $week = unserialize($info['week']);
                    foreach($week as $key => $we){
                        switch ($we){
                            case '1':
                                $content .= '星期一';
                                break;
                            case '2':
                                if($key == 0){
                                    $content .= '星期二';
                                }else{
                                    $content .= ',星期二';
                                }
                                break;
                            case '3':
                                if($key == 0){
                                    $content .= '星期三';
                                }else{
                                    $content .= ',星期三';
                                }
                                break;
                            case '4':
                                if($key == 0){
                                    $content .= '星期四';
                                }else{
                                    $content .= ',星期四';
                                }
                                break;
                            case '5':
                                if($key == 0){
                                    $content .= '星期五';
                                }else{
                                    $content .= ',星期五';
                                }
                                break;
                            case '6':
                                if($key == 0){
                                    $content .= '星期六';
                                }else{
                                    $content .= ',星期六';
                                }
                                break;
                            case '7':
                                if($key == 0){
                                    $content .= '星期天';
                                }else{
                                    $content .= ',星期天';
                                }
                                break;
                        }
                    }
                }else{
                    $content = '此商品每月';
                    $day = unserialize($info['day']);
                    foreach($day as $key => $dd){
                        if($key == 0){
                            $content .= $dd.'号';
                        }else{
                            $content .= ','.$dd.'号';
                        }
                    }
                }
                $content .= '开放购买';
                $info['tag_list'][] = $stagt;
                $info['tagslist'][] = array( 'title'=> $stagt,'content'=> $content);
                if($info['sales_status'] == 2){
                    $check = WeliamWeChat::checkUseDateStatus($info['usedatestatus'],$info['week'],$info['day']);
                    if(empty($check)){
                        $info['sales_status'] = 3;
                    }
                }
            }
        }
        $supar = [];
        $supar[] = $info['stk'];
        #6、判断用户购买限制
        //亿企达数量处理
        if($info['threestatus'] == 1){
            $pftotherinfo = unserialize($info['pftotherinfo']);
            if($pftotherinfo['limitCountMax'] > 0){
                $supar[] = $pftotherinfo['limitCountMax'];
            }
        }
        if ($info['buy_limit'] > 0 && $saletype > 0) {
            $userBuyNum = WeliamWeChat::getSalesNum($saletype,$id,0,1,$_W['mid']);
            $supar[] = $info['buy_limit'] - intval($userBuyNum);//当前用户还能购买的数量
        }
        //每日限量
        if($info['daylimit']>0 || $info['alldaylimit']>0){
            $stagt = '每日限量';
            $info['tag_list'][] = $stagt;
            $daylimittips = '此商品';
            if($info['alldaylimit']>0){
                $daylimittips .= '每天限量供应'.$info['alldaylimit'].'份.';
            }
            if($info['daylimit']>0){
                $daylimittips .= '每天每人只能购买'.$info['daylimit'].'份.';
            }
            $info['tagslist'][] = array( 'title'=> $stagt,'content'=> $daylimittips);
            if($info['sales_status'] == 2){
                $today = strtotime(date('Y-m-d'));
                if($info['alldaylimit']>0 ){
                    $daysalenum = WeliamWeChat::getSalesNum($saletype,$id,0,1,0,$today);
                    $sup = $info['alldaylimit'] - intval($daysalenum);
                    $supar[] = $sup;
                    if($daysalenum >= $info['alldaylimit']){
                        $info['sales_status'] = 4;
                    }
                    $info['todayselenum'] = $daysalenum;
                }
                if($info['daylimit']>0 && $_W['mid'] > 0 ){
                    $daysalenum = WeliamWeChat::getSalesNum($saletype,$id,0,1,$_W['mid'],$today);
                    $sup = $info['daylimit'] - intval($daysalenum);
                    $supar[] = $sup;
                    if($daysalenum >= $info['daylimit']){
                        $info['sales_status'] = 4;
                    }
                }
            }else{
                $info['todayselenum'] = 0;
            }
        }
        //每月限量
        if($info['monthlimit']>0 && $_W['mid'] > 0){
            $stagt = '每月限量';
            $info['tag_list'][] = $stagt;
            $monthlimittips = '此商品每月每人只能购买'.$info['monthlimit'].'份.';
            $info['tagslist'][] = array( 'title'=> $stagt,'content'=> $monthlimittips);
            if($info['sales_status'] == 2) {
                $tomonth = strtotime(date('Y-m'));
                $monthsalenum = WeliamWeChat::getSalesNum($saletype,$id,0,1,$_W['mid'],$tomonth);
                $sup = $info['monthlimit'] - intval($monthsalenum);
                $supar[] = $sup;
                if($monthsalenum >= $info['monthlimit']){
                    $info['sales_status'] = 4;
                }
            }
        }

        //积分商品判断
        if($type == 8){
            $supar = [];
            if ($info['chance'] > 0) {
                $times = pdo_fetchcolumn('SELECT SUM(num) FROM ' . tablename('wlmerchant_consumption_record') . " WHERE uniacid = {$_W['uniacid']} AND  goodsid = {$id} AND mid = {$_W['mid']} ");
                $sup = $info['chance'] - $times;
                $supar[] = $sup;
            }
            //判断库存
            $cctotal = pdo_fetchcolumn("SELECT SUM(num) FROM " . tablename(PDO_NAME . "order") . " WHERE plugin = 'consumption' AND fkid = {$id} AND status != 5 AND status != 7");
            $sup = $info['stock'] - $cctotal;
            $supar[] = $sup;
        }
        $info['user_limit_num'] = min($supar);
        if($info['user_limit_num'] < 0){
            $info['user_limit_num']  = 0;
        }
        //尾款
        if($info['retainage'] > 0){
            $stagt = '核销付尾款';
            $info['tag_list'][] = $stagt;
            $info['tagslist'][] = array( 'title'=> $stagt,'content'=> '此商品使用时必须再向商户支付尾款：'.$info['retainage'].'元');
        }
        #13、是否开启一卡通
        if($_W['wlsetting']['halfcard']['status'] > 0 && $info['vipstatus'] > 0){
            $info['is_open_vip'] = 1;
        }else{
            $info['is_open_vip'] = 0;
        }
        #14、获取当前商品的浏览记录
        $browseRecord = array_column(WeliamWeChat::getBrowseRecord($type,$id), 'avatar');
        $info['user_list'] = is_array($browseRecord) ? $browseRecord :[];
        #15、判断是否开启阶梯价（目前仅抢购存在）  是否开启阶梯价(0=关闭 1=开启)
        if($type == 1){
            if($info['lp_status'] == 1){
                $info['lp_set'] = is_array(unserialize($info['lp_set'])) ? unserialize($info['lp_set']) :[];
                if(count($info['lp_set']) > 0){
                    $updateStatus = 0;
                    $nowStatus = 0;
                    $newArr = [];
                    $buyNum = $info['buy_num'];
                    $allsalenum = intval($info['allsalenum']);
                    foreach ($info['lp_set'] as $lpKey => &$lpVal){
                        $lpVal['max'] = intval($lpVal['max']) + $allsalenum;
                        //修改当前商品价格、会员价格、会员优惠
                        if($info['buy_num'] < $lpVal['max'] && $updateStatus == 0){
                            $info['price'] = $lpVal['price'];
                            //$info['vipprice'] = $lpVal['vip_price'];
                            //$info['discount_price'] = sprintf("%.2f", ($lpVal['price'] - $lpVal['vip_price']));
                            $updateStatus = 1;
                        }
                        //获取最小值 第一个的最小值为0  之后的最小值为前一个区间上限 +1
                        $thisMin = intval($lpKey == 0 ? 0  : ($info['lp_set'][$lpKey - 1]['max'])) + 1;
                        //获取当前区间总共的可销售数量 第一个的可销售总数为其最大区间  之后的可销售总数为当前区间上限 - 前一个区间的上限
                        $thisTotal = $lpKey == 0 ? $lpVal['max'] :($lpVal['max'] - $info['lp_set'][$lpKey - 1]['max']);
                        //获取当前区间的剩余数量 剩余数量为当前区间上限 减去销量
                        $surplusTotalStk = ((($lpVal['max'] - $buyNum) >= 0) ? ($lpVal['max'] - $buyNum) : 0);
                        $thisStk = $surplusTotalStk > $thisTotal ? $thisTotal : $surplusTotalStk ;
                        //获取当前区间的已售数量 总共的可销售数量 - 剩余数量
                        $thisBuyNum = $thisTotal - $thisStk;
                        //获取当前区间已售百分比  已售数量 / 总共可售数量
                        $proportion = sprintf("%.2f",$thisBuyNum/$thisTotal * 100);
                        //新数组建立 方便移动端循环
                        $newArr[$lpKey] = [
                            'this_total' => $thisTotal ,
                            'price'      => sprintf("%.2f" , $lpVal['price']) ,//当前区间的销售价格
                            'min'        => intval($thisMin) ,//当前区间的最小值
                            'max'        => intval($lpVal['max']) ,//当前区间的最大值
                            'surplus'    => intval($thisStk) < 0 ? intval(0) : intval($thisStk) ,//当前区间剩余数量
                            'buy_num'    => intval($thisBuyNum) ,//当前区间已售数量
                            'proportion' => $proportion
                        ];
                        //获取当前销售阶段的库存信息
                        if($newArr[$lpKey]['surplus'] > 0 && $nowStatus == 0){
                            $info['lp_now_stk'] = intval($newArr[$lpKey]['surplus']);
                            $nowStatus = 1;
                        }
                    }
                    //重新复制 lp_set
                    $info['lp_set'] = $newArr;
                }
            }else{
                $info['lp_set'] = [];
            }
        }
        #16、获取使用流程和价格说明
        $settings = Setting::wlsetting_read('orderset');
        $info['info_set'] = [
            'use_info'   => $settings['use_info'] ? htmlspecialchars_decode($settings['use_info']) : '',
            'price_info' => $settings['price_info'] ? htmlspecialchars_decode($settings['price_info']) : '',
            'hide_use' => $settings['use_switch'],
            'hide_price' => $settings['price_switch'],
        ];
        #17、获取图片高度
        $info['imgstyle']['width'] = !empty(trim($_W['wlsetting']['base']['width'])) ? trim($_W['wlsetting']['base']['width']) : 750;
        $info['imgstyle']['height'] = !empty(trim($_W['wlsetting']['base']['height'])) ? trim($_W['wlsetting']['base']['height']) :  560;
        //删除不需要的多余信息
        unset($info['communityid']);
        unset($info['allsalenum']);
        unset($info['plugin']);
        unset($info['usedatestatus']);
        unset($info['week']);
        unset($info['day']);
        unset($info['daylimit']);
        unset($info['monthlimit']);
        unset($info['viparray']);
        //881定制内容
        $isAuth = Customized::init('diy_userInfo');
        if($isAuth){
            $info['diy_userInfo']['credit'] = $_W['wlmember']['credit2']?$_W['wlmember']['credit2']:'0.00';
            $info['diy_userInfo']['dhurl'] = $_W['wlsetting']['recharge']['dhurl'];
            $info['diy_userInfo']['dhtip1'] = $_W['wlsetting']['recharge']['dhtip1']?$_W['wlsetting']['recharge']['dhtip1']:'1乐豆抵用1元';
            $info['diy_userInfo']['dhtip2'] = $_W['wlsetting']['recharge']['dhtip2']?$_W['wlsetting']['recharge']['dhtip2']:'中国移动/中国银行积分可兑换乐豆';
        }
        //砍价设置详情
        if($type == 7){
            $barset = Setting::agentsetting_read('bargainset');
            $info['barset']['playtitle'] = $barset['playtitle'] ? : '';
            $info['barset']['playdesc'] = $barset['playdesc'] ? : '';
            $info['barset']['playdetail'] = $barset['playdetail'] ? : '';
        }else{
            $info['barset']['playtitle'] = '';
            $info['barset']['playdesc'] = '';
            $info['barset']['playdetail'] = '';
        }
        //商户信息隐藏
        $storeSet = Setting::wlsetting_read('agentsStoreSet');
        $info['hidestoreinfo'] = $storeSet['goodshide'] ? : 0;
        //定制底部菜单
        if(Customized::init('luckygroup') &&  $type == 3){
            $info['pddfoot'] = 1;
        }else{
            $info['pddfoot'] = 0;
        }
        //视频号图文
        $info['imgtext'] = 0;
        if(p('wxchannels') && ($type == 1 || $type == 2)){
            $wxappset = Setting::wlsetting_read('wxapp_config');
            $wxplat = Util::object_array($_W['account']);
            if(!empty($wxappset['appid']) && !empty($wxplat['key'])){
                $info['imgtext'] = 1;
            }
        }
        $this->renderSuccess('商品详细信息',$info);
    }
    /**
     * Comment: 获取在线买单评价表
     * Author: wlf
     * Date: 2019/11/25 18:29
     */
    public function getPayOnlieOrder(){
        global $_W,$_GPC;
        $id = $_GPC['orderid'];
        $order = pdo_get('wlmerchant_order',array('id' => $id),array('sid','price'));
        $data['price'] = $order['price'];
        $store = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('storename','logo'));
        $data['goods_name'] = $store['storename'].'在线买单';
        $data['logo'] = tomedia($store['logo']);
        $this->renderSuccess('订单信息',$data);
    }
    /**
     * Comment: 获取商品推广图文设置信息
     * Author: zzw
     * Date: 2019/12/26 10:21
     */
    public function getGoodsExtensionInfo(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'] OR $this->renderError('缺少参数：商品id');//商品id
        $type = $_GPC['type'] OR $this->renderError('缺少参数：商品类型');//商品类型：1=抢购  2=团购  3=拼团 4=大礼包 5=优惠券 6=折扣卡 7=砍价商品 8=积分商品
        #2、根据商品类型获取推广文案设置信息
        switch ($type) {
            case 1:
                $table = tablename(PDO_NAME."rush_activity");
                $field = 'name,thumbs,extension_text,extension_img,oldprice,price,vipprice,vipstatus,viparray';
                break;//抢购商品
            case 2:
                $table = tablename(PDO_NAME."groupon_activity");
                $field = 'name,thumbs,extension_text,extension_img,oldprice,price,vipprice,vipstatus,viparray';
                break;//团购商品
            case 3:
                $table = tablename(PDO_NAME."fightgroup_goods");
                $field = 'name,adv as thumbs,extension_text,extension_img,price,aloneprice,oldprice,vipstatus,vipdiscount,peoplenum,vipdiscount';
                break;//拼团商品
            case 7:
                $table = tablename(PDO_NAME."bargain_activity");
                $field = 'name,thumbs,extension_text,extension_img,oldprice,price,vipprice,vipstatus,viparray';
                break;//砍价商品
        }
        $info = pdo_fetch("SELECT {$field} FROM ".$table." WHERE id = {$id} ");
        #3、数据处理
        $thumbs       = unserialize($info['thumbs']);
        $extensionImg = unserialize($info['extension_img']);
        //判断推广图片是否存在 不存在使用商品图集
        if (count($extensionImg) > 0 && is_array($extensionImg)) {
            $info['extension_img'] = $extensionImg;
        } else {
            $info['extension_img'] = $thumbs;
        }
        //循环处理图片
        foreach($info['extension_img'] as &$img){
            $img = tomedia($img);
            if(!strstr($img,'https')){
                $img = str_replace('http','https',$img);
            }
        }
        $info['extension_img'] = is_array($info['extension_img']) ? $info['extension_img'] : [];
        //判断推广文案是否存在 不存在使用商品名称
        if($info['extension_text']){
            //[昵称] [时间] [商品名称] [原价] [特权类型] [拼团价] [单购价] [会员减免金额] [开团人数] [会员底价] [底价] [活动价] [会员价]
            if($type != 3){
                $info['vipdiscount'] = WeliamWeChat::getVipDiscount($info['viparray'],-1);
                $info['vipprice'] = sprintf("%.2f",$info['price'] - $info['vipdiscount']);
            }
            $nickname = $_W['wlmember']['nickname'];
            $time     = date("Y-m-d H:i:s" , time());
            if ($info['vipstatus'] == 1) $vipstatus = '会员特价';
            else if ($info['vipstatus'] == 2) $vipstatus = '会员特供';
            else $vipstatus = '';
            $info['extension_text'] = str_replace('[昵称]' , $nickname , $info['extension_text']);
            $info['extension_text'] = str_replace('[时间]' , $time , $info['extension_text']);
            $info['extension_text'] = str_replace('[商品名称]' , $info['name'] , $info['extension_text']);
            $info['extension_text'] = str_replace('[原价]' , $info['oldprice'] , $info['extension_text']);
            $info['extension_text'] = str_replace('[特权类型]' , $vipstatus , $info['extension_text']);
            $info['extension_text'] = str_replace('[活动价]' , $info['price'] , $info['extension_text']);
            $info['extension_text'] = str_replace('[会员价]' , $info['vipprice'] , $info['extension_text']);
            $info['extension_text'] = str_replace('[拼团价]' , $info['price'] , $info['extension_text']);
            $info['extension_text'] = str_replace('[单购价]' , $info['aloneprice'] , $info['extension_text']);
            $info['extension_text'] = str_replace('[市场价]' , $info['oldprice'] , $info['extension_text']);
            $info['extension_text'] = str_replace('[会员减免金额]' , $info['vipdiscount'] , $info['extension_text']);
            $info['extension_text'] = str_replace('[开团人数]' , $info['peoplenum'] , $info['extension_text']);
            $info['extension_text'] = str_replace('[会员底价]' , $info['vipprice'] , $info['extension_text']);
            $info['extension_text'] = str_replace('[底价]' , $info['price'] , $info['extension_text']);
        }else{
            $info['extension_text'] = $info['name'] ? : '';
        }
        #4、删除多余的数据
        unset($info['name']);
        unset($info['thumbs']);

        $this->renderSuccess('推广信息',$info,1);
    }
    /**
     * Comment: 获取商品评价信息
     * Author: wlf
     * Date: 2020/04/10 15:19
     */
    public function getComment(){
        global $_W,$_GPC;
        #1、参数获取
        $sid = $_GPC['sid'];  //商户id
        if(empty($sid)){
            $this->renderError('缺少商户参数,请返回重试');
        }
        $plugin = $_GPC['plugin'];  //商品插件
        $gid = $_GPC['goodsid'];  //商品id
        $page = $_GPC['page'] ? $_GPC['page'] : 1; //页码
        $page_start = $page * 10 - 10;
        //查询
        $where = " sid = {$sid} AND status = 1 AND (checkone = 2 OR mid = {$_W['mid']})";
        if(!empty($plugin) && !empty($gid)){
            $where .= " AND plugin = '{$plugin}' AND gid = {$gid}";
        }
        $data['totalnum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_comment')." WHERE {$where}");
        $data['totalpage'] = ceil($data['totalnum'] / 10);
        $comments = pdo_fetchall("SELECT headimg,nickname,createtime,star,pic,text,replytextone,replypicone,mid FROM ".tablename('wlmerchant_comment')."WHERE {$where} ORDER BY createtime DESC LIMIT {$page_start},10");
        if(!empty($comments)){
            foreach($comments as $commentKey => &$commentVal){
                //用户评论图片的处理
                $commentPic = unserialize($commentVal['pic']);
                if(is_array($commentPic) && count($commentPic) > 0){
                    foreach($commentPic as $picKey => &$picVal){
                        if($picVal) $picVal = tomedia($picVal);
                        else unset($commentPic[$picKey]);
                    }
                }else{
                    $commentPic = [];
                }
                $commentVal['pic'] = $commentPic;
                //商家回复信息图片的处理
                $replyPic = unserialize($commentVal['replypicone']);
                if(is_array($replyPic) && count($replyPic) > 0){
                    foreach($replyPic as $replyPicKey => &$replyPicVal){
                        $replyPicVal = tomedia($replyPicVal);
                    }
                }else{
                    $replyPic = [];
                }
                $commentVal['replypicone'] = $replyPic;
                //用户信息处理
                if(!empty($commentVal['mid'])){
                    $member = pdo_get('wlmerchant_member',array('id' => $commentVal['mid']),array('nickname','avatar'));
                }
                if(!empty($member['nickname'])){
                    $commentVal['nickname'] = $member['nickname'];
                }
                if(!empty($member['avatar'])){
                    $commentVal['headimg'] = tomedia($member['avatar']) ;
                }else{
                    $commentVal['headimg'] = tomedia($commentVal['headimg']) ;
                }
                //处理时间
                $commentVal['createtime'] = date('Y-m-d H:i:s',$commentVal['createtime']);
            }
        }
        $data['commentinfo'] = $comments;
        $this->renderSuccess('评论列表',$data);
    }
    /**
     * Comment: 商品分类页面 - 顶部排序分类信息
     * Author: zzw
     * Date: 2020/12/7 10:24
     */
    public function getGoodsCateList(){
        global $_W,$_GPC;
        //参数信息获取
        $type = $_GPC['type'] ? : 3;//页面类型：3=抢购首页;4=团购首页;6=拼团首页;7=砍价首页;14=活动首页
        //信息获取
        $info = DiyPage::defaultInfo('options2',['type'=>$type]);

        $this->renderSuccess('评论列表',$info);
    }
    

}