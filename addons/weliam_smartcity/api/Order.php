<?php
defined('IN_IA') or exit('Access Denied');
class OrderModuleUniapp extends Uniapp
{
    /**
     * Comment: 预约页面初始化和查询库存
     * Author: wlf
     * Date: 2020/12/30 15:46
     */
    public function getAppointNumber(){
        global $_W , $_GPC;
        //参数获取
        $appDate = $_GPC['appdate'] ? : date("Y-m-d",time()); //日期
        $type = $_GPC['pluginno'];  //类型
        $goodsid = $_GPC['goodsid'];  //商品id
        $orderid = $_GPC['orderid'];  //订单id
        $initialization = $_GPC['initialization']; //初始化标记
        switch($type){
            case 1:
                $goods = pdo_get('wlmerchant_rush_activity',array('id' => $goodsid),array('appointdays','appointarray'));
                $order = pdo_get('wlmerchant_rush_order',array('id' => $orderid),array('estimatetime'));
                break;
            case 2:
                $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $goodsid),array('appointdays','appointarray'));
                $order = pdo_get('wlmerchant_order',array('id' => $orderid),array('estimatetime'));
                break;
            case 3:
                $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $goodsid),array('appointdays','appointarray'));
                $order = pdo_get('wlmerchant_order',array('id' => $orderid),array('estimatetime'));
                break;
            case 7:
                $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $goodsid),array('appointdays','appointarray'));
                $order = pdo_get('wlmerchant_order',array('id' => $orderid),array('estimatetime'));
                break;
        }
        $data['appointdays'] = $goods['appointdays'];
        $appointarray = unserialize($goods['appointarray']);
        if($initialization > 0){
            //计算天数
            $casttime = $order['estimatetime'] - time();
            $castday = ceil($casttime/86400);
            $goods['appointdays'] = $goods['appointdays'] ? $goods['appointdays'] + 1: 999;
            $limitday = min($goods['appointdays'],$castday,30);
            $weekArray = ["周日","周一","周二","周三","周四","周五","周六"];
            for($i=0;$i<$limitday;$i++){
                $time = $i * 86400 + time();
                if($i == 0){
                    $week = '今天';
                }else if($i == 1){
                    $week = '明天';
                }else{
                    $weekN = date("w",$time);
                    $week = $weekArray[$weekN];
                }
                $day = date("d",$time);
                $dateI = date("Y-m-d",$time);
                $dayArray[] = [
                    'week' => $week,
                    'day'  => $day,
                    'date' => $dateI
                ];
            }
            $data['dayArray'] = $dayArray;
        }
        $appointNarray = [];
        foreach($appointarray as $appo){
            $appo['peoplenums'] = $appo['peoplenums'] ? : 0;
            //计算库存
            $appstarttime = strtotime($appDate.$appo['startTime']);
            $appendtime = strtotime($appDate.$appo['endTime']);
            if($appendtime < $appstarttime){
                $appendtime = $appendtime + 86400;
            }
            if($appstarttime > $order['estimatetime'] || $appendtime < time()){
                continue;
            }else{
                $num = pdo_getcolumn('wlmerchant_appointlist',array('status' => [0,1],'date' => $appDate,'starttime' => $appo['startTime'],'endtime' => $appo['endTime'],'type' => $type, 'goodid' => $goodsid ),array("SUM(num)"));
                $num = $num ? : 0;
                $surnum = $appo['peoplenums'] - $num;
                $appo['surnum'] = $surnum > 0 ? $surnum : 0;
                $appointNarray[] = $appo;
            }
        }
        $data['appointarray'] = $appointNarray;
        $this->renderSuccess('预约页面',$data);
    }

    /**
     * Comment: 填入预约申请
     * Author: wlf
     * Date: 2020/12/30 16:10
     */
    public function addAppoint(){
        global $_W , $_GPC;
        //参数获取
        $num = $_GPC['num'];  //预约数量
        $alnum = $_GPC['peoplenums']; //总数量
        $appDate = $_GPC['appdate']; //日期
        $appStime = $_GPC['appstarttime']; //开始时间
        $appEtime = $_GPC['appendtime'];  //结束时间
        $type = $_GPC['pluginno'];  //类型
        $goodsid = $_GPC['goodsid'];  //商品id
        $orderid = $_GPC['orderid'];  //订单id
        $remark = $_GPC['remark']; //备注
        //查询数据
        switch($type){
            case 1:
                $goods = pdo_get('wlmerchant_rush_activity',array('id' => $goodsid),array('uniacid','aid','appointment','name','appointstatus','sid'));
                $plugin = 'rush';
                $pluginName = '抢购';
                $goodsName = $goods['name'];
                $orderNo = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$orderid),'orderno');
                break;
            case 2:
                $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $goodsid),array('uniacid','appointment','name','aid','appointstatus','sid'));
                $plugin = 'groupon';
                $pluginName = '团购';
                $goodsName = $goods['name'];
                $orderNo = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'orderno');
                break;
            case 3:
                $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $goodsid),array('uniacid','name','aid','appointment','appointstatus','sid'));
                $plugin = 'wlfightgroup';
                $pluginName = '拼团';
                $goodsName = $goods['name'];
                $orderNo = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'orderno');
                break;
            case 7:
                $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $goodsid),array('uniacid','name','aid','appointment','appointstatus','sid'));
                $plugin = 'bargain';
                $pluginName = '砍价';
                $goodsName = $goods['name'];
                $orderNo = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'orderno');
                break;
        }
        if($goods['appointstatus'] == 2){
            $status = 1;
        }else{
            $status = 0;
        }
        $appstarttime = strtotime($appDate.$appStime);
        $appendtime = strtotime($appDate.$appEtime);
        if($appendtime < $appstarttime){
            $appendtime = $appendtime + 86400;
        }
        if($alnum > 0){
            $alreadnum = pdo_getcolumn('wlmerchant_appointlist',array('date' => $appDate,'starttime' => $appStime,'endtime' => $appEtime,'type' => $type,'goodid'=> $goodsid),array("SUM(num)"));
            $supunm = $alnum - $alreadnum;
            if($supunm < 1){
                $this->renderError('此时间段已经约满，请选择其他的时间段');
            }else if($supunm < $num){
                $this->renderError('此时间段还能预约'.$supunm.'份');
            }
        }
        if($goods['appointment'] > 0 && ($appendtime - time() < $goods['appointment'] * 3600) ){
            $this->renderError('此商品需提前'.$goods['appointment'].'小时预约，请选择靠后的时间段');
        }
        $ids = pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_smallorder')."WHERE plugin = '{$plugin}' AND orderid = {$orderid} AND appointstatus = 1 AND status = 1 ORDER BY id DESC LIMIT {$num}");
        if(empty($ids)){
            $this->renderError('无可用核销码，请刷新重试');
        }
        $ids = array_column($ids,'id');
        $sorderids = serialize($ids);
        $data = [
            'uniacid' => $goods['uniacid'],
            'aid'     => $goods['aid'],
            'orderid' => $orderid,
            'mid'     => $_W['mid'],
            'sid'     => $goods['sid'],
            'type'    => $type,
            'goodid'  => $goodsid,
            'num'     => $num,
            'date'    => $appDate,
            'starttime' => $appStime,
            'endtime' => $appEtime,
            'starttimestamp' => $appstarttime,
            'endtimestamp' => $appendtime,
            'status'  => $status,
            'appointtime' => time(),
            'sorderids' => $sorderids,
            'orderno'   => $orderNo,
            'remark'    => $remark
        ];
        MysqlFunction::setTrans(4);
        MysqlFunction::startTrans();
        $res = pdo_insert(PDO_NAME . 'appointlist', $data);
        if($res){
            if($status > 0){  //免审核
                $upSres = pdo_update('wlmerchant_smallorder',array('appointstatus' => 3,'appstarttime' => $appstarttime,'appendtime' => $appendtime),array('id' => $ids));
                //发送消息给商家
                if($upSres){
                    $first = '一个'.$pluginName.'订单已经预约';
                    $type = '订单消费预约';
                    $content = '商品名:['.$goodsName.']';
                    $newStatus = '预约成功';
                    $remark = '订单号:['.$orderNo.'],预约数量:'.$num.'份,点击查看订单';
                    $urls = h5_url('pages/subPages2/booked/reservationList/reservationList' , ['sid' => $goods['sid']]);
                    News::noticeShopAdmin($goods['sid'],$first,$type,$content,$newStatus,$remark,time(),$urls);
                    //发送消息给客户
                    $first = '您的'.$pluginName.'订单已经成功预约';
                    $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails' , ['orderid' => $orderid , 'plugin'  => $plugin]);
                    News::jobNotice($_W['mid'],$first,$type,$content,$newStatus,$remark,time(),$url);
                }
            }else{
                $upSres = pdo_update('wlmerchant_smallorder',array('appointstatus' => 2,'appstarttime' => $appstarttime,'appendtime' => $appendtime),array('id' => $ids));
                if($upSres) {
                    //发送消息给商家
                    $first = '一个' . $pluginName . '订单申请预约';
                    $type = '订单消费预约';
                    $content = '商品名:[' . $goodsName . ']';
                    $newStatus = '待审核';
                    $remark = '订单号:[' . $orderNo . '],预约数量:' . $num . '份,点击审核预约';
                    $urls = h5_url('pages/subPages2/booked/reservationList/reservationList' , ['sid' => $goods['sid']]);
                    News::noticeShopAdmin($goods['sid'], $first, $type, $content, $newStatus, $remark, time(),$urls);
                }
            }
            if($upSres){
                MysqlFunction::commit();
                $this->renderSuccess('预约成功',['status'=>1]);
            }else{
                MysqlFunction::rollback();
                $this->renderError('预约失败，请刷新重试');
            }
        }else{
            MysqlFunction::rollback();
            $this->renderError('预约失败，请刷新重试');
        }
    }

    /**
     * Comment: 用户预约详情
     * Author: wlf
     * Date: 2020/01/12 17:03
     */
    public function appointDetail(){
        global $_W , $_GPC;
        $orderid = $_GPC['orderid'];
        $goodsid = $_GPC['goodsid'];
        $type = $_GPC['pluginno'];
        if(empty($orderid)  || empty($goodsid) || empty($type)){
            $this->renderError('缺少重要参数，请刷新重试');
        }
        switch($type){
            case 1:
                $goods = pdo_get('wlmerchant_rush_activity',array('id' => $goodsid),array('thumb','name','sid'));
                $optionid = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$orderid),'optionid');
                $data['goods']['logo'] = tomedia($goods['thumb']);
                $data['goods']['name'] = $goods['name'];
                $sid = $goods['sid'];
                break;
            case 2:
                $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $goodsid),array('thumb','name','sid'));
                $optionid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'specid');
                $data['goods']['logo'] = tomedia($goods['thumb']);
                $data['goods']['name'] = $goods['name'];
                $sid = $goods['sid'];
                break;
            case 3:
                $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $goodsid),array('merchantid','name','logo'));
                $optionid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'specid');
                $data['goods']['logo'] = tomedia($goods['logo']);
                $data['goods']['name'] = $goods['name'];
                $sid = $goods['merchantid'];
                break;
            case 7:
                $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $goodsid),array('thumb','name','sid'));
                $optionid = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'specid');
                $data['goods']['logo'] = tomedia($goods['thumb']);
                $data['goods']['name'] = $goods['name'];
                $sid = $goods['sid'];
                break;
        }
        if($optionid > 0){
            $data['goods']['optionName'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$optionid),'title');
        }
        //商户信息
        $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $sid),array('storename','mobile','address','lat','lng','storehours'));

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
        $data['merchant'] = $merchant;
        //预约列表
        $applist = pdo_getall('wlmerchant_appointlist',array('orderid' => $orderid,'type' => $type),array('remark','num','id','status','date','starttime','endtime','reason'));
        foreach($applist as &$app){
            if($app['status'] == 0){
                $app['statusTips'] = '预约审核中';
                $app['statusText'] = '正在预约中，请耐心等待哦~';
            }else if($app['status'] == 1){
                $app['statusTips'] = '预约已通过';
                $app['statusText'] = '商户已确认，请在预约时间内前往商家核销~';
            }else if($app['status'] == 2){
                $app['statusTips'] = '预约被驳回';
                $app['statusText'] = '驳回原因:'.$app['reason'];
            }else if($app['status'] == 3){
                $app['statusTips'] = '预约已取消';
                $app['statusText'] = '本次预约已被您取消';
            }else if($app['status'] == 4){
                $app['statusTips'] = '预约已取消';
                $app['statusText'] = '本次预约已被商户取消';
            }
        }
        $data['applist'] = $applist;
        $this->renderSuccess('预约详情',$data);
    }

    /**
     * Comment: 取消预约接口
     * Author: wlf
     * Date: 2020/01/13 13:05
     */
    public function cancelAppoint(){
        global $_W , $_GPC;
        $id = $_GPC['appointid'];
        $channel = $_GPC['channel'];  //操作方 3用户 4商户
        if(empty($id)){
            $this->renderError('缺少重要参数，请刷新重试');
        }
        $res = pdo_update('wlmerchant_appointlist',array('status' => $channel,'dotime' => time()),array('id' => $id));
        if($res){
            $appoint = pdo_get('wlmerchant_appointlist',array('id' => $id),array('goodid','orderno','num','type','mid','sid','sorderids'));
            //修改核销码状态
            $sorderids = unserialize($appoint['sorderids']);
            pdo_update('wlmerchant_smallorder',array('appointstatus' => 1,'appstarttime' => 0,'appendtime' => 0),array('id' => $sorderids));
            switch($appoint['type']){
                case 1:
                    $goods = pdo_get('wlmerchant_rush_activity',array('id' => $appoint['goodid']),array('name'));
                    $goodsName = $goods['name'];
                    $pluginName = '抢购';
                    break;
                case 2:
                    $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $appoint['goodid']),array('name'));
                    $goodsName = $goods['name'];
                    $pluginName = '团购';
                    break;
                case 3:
                    $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $appoint['goodid']),array('name'));
                    $goodsName = $goods['name'];
                    $pluginName = '拼团';
                    break;
                case 7:
                    $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $appoint['goodid']),array('name'));
                    $goodsName = $goods['name'];
                    $pluginName = '砍价';
                    break;
            }
            if($channel == 3){
                //发送消息给商户
                $first = '一个' . $pluginName . '订单预约已取消';
                $type = '订单预约取消';
                $content = '商品名:[' . $goodsName . ']';
                $newStatus = '已取消';
                $remark = '订单号:[' . $appoint['orderno'] . '],预约数量:' . $appoint['num'] . '份,用户已取消预约';
                News::noticeShopAdmin($appoint['sid'], $first, $type, $content, $newStatus, $remark, time());
            }else if($channel == 4){
                //发送消息给商户
                $first = '您的' . $pluginName . '订单预约已被取消';
                $type = '订单预约取消';
                $content = '商品名:[' . $goodsName . ']';
                $newStatus = '已取消';
                $remark = '订单号:[' . $appoint['orderno'] . '],预约数量:' . $appoint['num'] . '份,商户已取消预约';
                $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails' , ['orderid' => $orderid , 'plugin'  => $plugin]);
                News::jobNotice($appoint['mid'],$first,$type,$content,$newStatus,$remark,time(),$url);
            }
            $this->renderSuccess('取消成功');
        }else{
            $this->renderError('取消失败，请刷新重试');
        }
    }


    /**
     * Comment: 商户预约列表
     * Author: wlf
     * Date: 2021/01/06 14:34
     */
    public function appointList(){
        global $_W , $_GPC;
        $sid = $_GPC['sid'];
        $status = $_GPC['status'];
        if(empty($sid)){
            $this->renderError('店铺信息错误，请刷新重试');
        }
        $data = [];
        //查询数据
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;//当前页
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $where = ['uniacid' => $_W['uniacid'],'sid' => $sid];
        if($status > 0){
            if($status == 5){
                $where['status'] = 0;
            }else{
                $where['status'] = trim($status);
            }
        }
        $List = Util::getNumData('id,orderid,mid,num,orderno,date,sorderids,starttime,endtime,status,remark,type,goodid,type,starttimestamp', PDO_NAME . 'appointlist', $where,'appointtime DESC', $pageStart, $pageIndex, 1);
        $applist = $List[0];
        $data['pagetotal'] = ceil($List[2] / $pageIndex);
        foreach($applist as &$al){
            //$al['appointtime'] = date('Y-m-d H:i',$al['appointtime']);
            switch($al['type']){
                case 1:
                    $order = pdo_get('wlmerchant_rush_order',array('id' => $al['orderid']),array('orderno','username','mobile','optionid'));
                    $order['specid'] = $order['optionid'];
                    $goods = pdo_get('wlmerchant_rush_activity',array('id' => $al['goodid']),array('thumb','name'));
                    $al['username'] = $order['username'];
                    $al['mobile'] = $order['mobile'];
                    break;
                case 2:
                    $order = pdo_get('wlmerchant_order',array('id' => $al['orderid']),array('orderno','name','mobile','specid'));
                    $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $al['goodid']),array('thumb','name'));
                    $al['username'] = $order['name'];
                    $al['mobile'] = $order['mobile'];
                    break;
                case 3:
                    $order = pdo_get('wlmerchant_order',array('id' => $al['orderid']),array('orderno','name','mobile','specid'));
                    $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $al['goodid']),array('logo','name'));
                    $al['username'] = $order['name'];
                    $al['mobile'] = $order['mobile'];
                    $goods['thumb'] = $goods['logo'];
                    break;
                case 7:
                    $order = pdo_get('wlmerchant_order',array('id' => $al['orderid']),array('orderno','name','mobile','specid'));
                    $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $al['goodid']),array('thumb','name'));
                    $al['username'] = $order['name'];
                    $al['mobile'] = $order['mobile'];
                    break;
            }
            $al['orderno'] = $order['orderno'];
            $al['goodsName'] = $goods['name'];
            $al['goodsLogo'] = tomedia($goods['thumb']);
            if($order['specid'] > 0){
                $al['optionName'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['specid']),'title');
            }
            if($al['status'] == 1){
                //判断核销码是否还正常
                $sorderids = unserialize($al['sorderids']);
                foreach($sorderids as $soid){
                    $status = pdo_getcolumn(PDO_NAME.'smallorder',array('id'=>$soid),'status');
                    if($status != 1){
                        $eFlag = 1;
                    }
                }
                if(empty($eFlag)){
                    if($al['starttimestamp'] < time()){
                        $al['hexiaoflag'] = 1;
                    }
                    $al['cancelflag'] = 1;
                }
            }
            if(empty($al['hexiaoflag'])){
                $al['hexiaoflag'] = 0;
            }
            if(empty($al['cancelflag'])){
                $al['cancelflag'] = 0;
            }

        }
        $data['list'] = $applist;
        $this->renderSuccess('预约列表',$data);
    }


    /**
     * Comment: 商户预约审核
     * Author: wlf
     * Date: 2021/01/06 16:33
     */
    public function examineAppoint(){
        global $_W , $_GPC;
        $id = $_GPC['id'];  //预约id
        $status = $_GPC['examine']; //审核类型   1通过 2驳回
        $reason = $_GPC['reason'];  //驳回原因
        if(empty($status) || empty($id)){
            $this->renderError('缺少关键参数，请刷新重试');
        }
        $appinfo = pdo_get(PDO_NAME.'appointlist',array('id'=>$id),['sorderids','goodid','mid','type','orderid','num']);
        $goodsid = $appinfo['goodid'];
        $orderid = $appinfo['orderid'];
        $ids = unserialize($appinfo['sorderids']);
        switch($appinfo['type']){
            case 1:
                $goods = pdo_get('wlmerchant_rush_activity',array('id' => $goodsid),array('uniacid','aid','appointment','name','appointstatus','sid'));
                $plugin = 'rush';
                $pluginName = '抢购';
                $goodsName = $goods['name'];
                $orderNo = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$orderid),'orderno');
                break;
            case 2:
                $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $goodsid),array('uniacid','appointment','name','aid','appointstatus','sid'));
                $plugin = 'groupon';
                $pluginName = '团购';
                $goodsName = $goods['name'];
                $orderNo = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'orderno');
                break;
            case 3:
                $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $goodsid),array('uniacid','name','aid','appointment','appointstatus','sid'));
                $plugin = 'wlfightgroup';
                $pluginName = '拼团';
                $goodsName = $goods['name'];
                $orderNo = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'orderno');
                break;
            case 7:
                $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $goodsid),array('uniacid','name','aid','appointment','appointstatus','sid'));
                $plugin = 'bargain';
                $pluginName = '砍价';
                $goodsName = $goods['name'];
                $orderNo = pdo_getcolumn(PDO_NAME.'order',array('id'=>$orderid),'orderno');
                break;
        }
        if($status == 1){
            $res = pdo_update('wlmerchant_appointlist',array('status' => 1),array('id' => $id));
            if($res){
                $upSres = pdo_update('wlmerchant_smallorder',array('appointstatus' => 3),array('id' => $ids));
                if($upSres){
                    //发送消息给客户
                    $first = '您的'.$pluginName.'订单已经成功预约';
                    $type = '订单预约结果通知';
                    $content = '商品名:['.$goodsName.']';
                    $newStatus = '预约成功';
                    $remark = '订单号:['.$orderNo.'],预约数量:'.$appinfo['num'].'份,点击查看详情';
                    $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails' , ['orderid' => $orderid , 'plugin'  => $plugin]);
                    News::jobNotice($appinfo['mid'],$first,$type,$content,$newStatus,$remark,time(),$url);
                }
            }
        }else if($status == 2){
            if(empty($reason)){
                $this->renderError('请输入驳回原因');
            }
            $res = pdo_update('wlmerchant_appointlist',array('status' => 2,'reason' => $reason),array('id' => $id));
            if($res){
                $upSres = pdo_update('wlmerchant_smallorder',array('appointstatus' => 1,'appstarttime'=> 0,'appendtime'=>0),array('id' => $ids));
                if($upSres){
                    //发送消息给客户
                    $first = '您的'.$pluginName.'订单预约失败';
                    $type = '订单预约结果通知';
                    $content = '商品名:['.$goodsName.']';
                    $newStatus = '预约失败';
                    $remark = '驳回原因:['.$reason.'],点击重新预约';
                    $url = h5_url('pages/subPages/orderList/orderDetails/orderDetails' , ['orderid' => $orderid , 'plugin'  => $plugin]);
                    News::jobNotice($appinfo['mid'],$first,$type,$content,$newStatus,$remark,time(),$url);
                }
            }
        }
        $this->renderSuccess('操作成功',['status'=>1]);
    }

    /**
     * Comment: 商户核销预约记录
     * Author: wlf
     * Date: 2021/01/25 11:46
     */
    public function hexiaoAppoint(){
        global $_W, $_GPC;
        $id = $_GPC['id'];  //预约id
        if(empty($id)){
            $this->renderError('缺少重要参数，请刷新重试');
        }
        $appoint = pdo_get('wlmerchant_appointlist',array('id' => $id),array('sorderids','type','orderid'));
        $sorderids = unserialize($appoint['sorderids']);
        if(!is_array($sorderids)){
            $this->renderError('无核销码编号，请刷新重试');
        }
        foreach($sorderids as $smid){
            $checkcode = pdo_getcolumn(PDO_NAME.'smallorder',array('id'=>$smid),'checkcode');
            if($appoint['type'] == 1){
                $res = Rush::hexiaoorder($appoint['orderid'],-1,1,3,$checkcode);
            }else if($appoint['type'] == 2){
                $res = Groupon::hexiaoorder($appoint['orderid'],-1,1,3,$checkcode);
            }else if($appoint['type'] == 3){
                $res = Wlfightgroup::hexiaoorder($appoint['orderid'],-1,1,3,$checkcode);
            }else if($appoint['type'] == 7){
                $res = Bargain::hexiaoorder($appoint['orderid'],-1,1,3,$checkcode);
            }
        }
        if($res){
            $this->renderSuccess('核销成功',['status'=>1]);
        }else{
            $this->renderError('核销失败，请刷新重试');
        }
    }

    /**
     * Comment: 评价页面初始化
     * Author: wlf
     * Date: 2021/05/08 15:26
     */
    public function commentPage(){
        global $_W, $_GPC;
        $id = $_GPC['order_id'];  //订单
        $plugin = $_GPC['plugin'];  //订单类型
        if(empty($id) || empty($plugin)){
            $this->renderError('参数错误，请返回重试');
        }
        switch($plugin){
            case 'rush':
                $order = pdo_get('wlmerchant_rush_order',array('id' => $id),array('actualprice','activityid','optionid'));
                $data['price'] = $order['actualprice'];
                if($order['optionid'] > 0){
                    $data['specname'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['optionid']),'title');
                }
                $goods = pdo_get('wlmerchant_rush_activity',array('id' => $order['activityid']),array('name','thumb'));
                $data['thumb'] = tomedia($goods['thumb']);
                $data['goodsname'] = $goods['name'];
                break;
            case 'groupon':
                $order = pdo_get('wlmerchant_order',array('id' => $id),array('price','fkid','specid'));
                $data['price'] = $order['price'];
                if($order['specid'] > 0){
                    $data['specname'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['specid']),'title');
                }
                $goods = pdo_get('wlmerchant_groupon_activity',array('id' => $order['fkid']),array('name','thumb'));
                $data['thumb'] = tomedia($goods['thumb']);
                $data['goodsname'] = $goods['name'];
                break;
            case 'wlfightgroup':
                $order = pdo_get('wlmerchant_order',array('id' => $id),array('price','fkid','specid'));
                $data['price'] = $order['price'];
                if($order['specid'] > 0){
                    $data['specname'] = pdo_getcolumn(PDO_NAME.'goods_option',array('id'=>$order['specid']),'title');
                }
                $goods = pdo_get('wlmerchant_fightgroup_goods',array('id' => $order['fkid']),array('name','logo'));
                $data['thumb'] = tomedia($goods['logo']);
                $data['goodsname'] = $goods['name'];
                break;
            case 'bargain':
                $order = pdo_get('wlmerchant_order',array('id' => $id),array('price','fkid'));
                $data['price'] = $order['price'];
                $goods = pdo_get('wlmerchant_bargain_activity',array('id' => $order['fkid']),array('name','thumb'));
                $data['thumb'] = tomedia($goods['thumb']);
                $data['goodsname'] = $goods['name'];
                break;
            case 'coupon':
                $order = pdo_get('wlmerchant_order',array('id' => $id),array('price','fkid'));
                $data['price'] = $order['price'];
                $goods = pdo_get('wlmerchant_couponlist',array('id' => $order['fkid']),array('title','logo'));
                $data['thumb'] = tomedia($goods['logo']);
                $data['goodsname'] = $goods['title'];
                break;
            case 'halfcard':
                $order = pdo_get('wlmerchant_order',array('id' => $id),array('price','fkid','sid'));
                $data['price'] = $order['price'];
                $goods = pdo_get('wlmerchant_halfcardlist',array('id' => $order['fkid']),array('title'));
                $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('logo'));
                $data['thumb'] = tomedia($merchant['logo']);
                $data['goodsname'] = $goods['title'];
                break;
            case 'citydelivery':
                $order = pdo_get('wlmerchant_order',array('id' => $id),array('price','fkid','sid'));
                $data['price'] = $order['price'];
                $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $order['sid']),array('storename','logo'));
                $data['thumb'] = tomedia($merchant['logo']);
                $data['goodsname'] = '['.$merchant['storename'].']同城配送商品';
                break;
            case 'housekeep':
                $order = pdo_get('wlmerchant_order',array('id' => $id),array('price','fkid','sid'));
                $data['price'] = $order['price'];
                $goods = pdo_get('wlmerchant_housekeep_service',array('id' => $order['fkid']),array('title','thumb'));
                $data['thumb'] = tomedia($goods['thumb']);
                $data['goodsname'] = $goods['title'];
                break;
        }
        $this->renderSuccess('评价页面初始化信息',$data);
    }


    /**
     * Comment: 手机号转赠接口
     * Author: wlf
     * Date: 2021/08/19 11:48
     */
    public function transferApi(){
        global $_W, $_GPC;
        $type = $_GPC['type']; //类型 1卡券 2红包
        $id = $_GPC['id']; //转赠卡券或红包id
        $mobile = $_GPC['mobile']; //转赠人的手机号
        if(empty($type) || empty($id) || empty($mobile)){
            $this->renderError('参数错误，请刷新重试');
        }

        //判断被转赠用户是否存在
        $getMember = pdo_get('wlmerchant_member',array('mobile' => $mobile,'uniacid' => $_W['uniacid']),array('id','nickname'));
        if(empty($getMember)){
            $this->renderError('手机号不存在，请先要求好友进入系统绑定手机重试');
        }
        if($getMember['id'] == $_W['mid']){
            $this->renderError('不能转赠给自己');
        }
        MysqlFunction::setTrans(4);
        MysqlFunction::startTrans();
        //修改原纪录状态
        if($type == 1){
            $coupon = pdo_get('wlmerchant_member_coupons',array('id' => $id),array('mid','parentid','status','title','orderno','transferflag'));
            if($coupon['mid'] != $_W['mid']){
                MysqlFunction::rollback();
                $this->renderError('卡券已经转赠，请勿重复转赠');
            }
            if($coupon['status'] != 1){
                MysqlFunction::rollback();
                $this->renderError('卡券状态错误，请刷新重试');
            }
            $get_limit = pdo_getcolumn(PDO_NAME.'couponlist',array('id'=>$coupon['parentid']),'get_limit');
            if($coupon['get_limit'] > 0){
                $num = wlCoupon::getCouponNum($coupon['parentid'],1,$getMember['id']);
                if($num > $get_limit || $num == $get_limit){
                    MysqlFunction::rollback();
                    $this->renderError('该用户不能拥有更多的此卡券');
                }
            }
            if(empty($coupon['transferflag'])){
                $res2 = pdo_update('wlmerchant_order',array('status' => 2),array('orderno' => $coupon['orderno'],'status' => 1));  //修改订单状态
            }else{
                $res2 = 1;
            }
            $res1 = pdo_update('wlmerchant_member_coupons',array('mid' => $getMember['id'],'transferflag' => 1),array('id' => $id));  //修改卡券所属用户
            $res3 = pdo_update('wlmerchant_smallorder',array('mid' => $getMember['id']),array('orderno' => $coupon['orderno']));  //修改子订单状态
            if(empty($res1) || empty($res2) || empty($res3)){
                MysqlFunction::rollback();
                $this->renderError('原卡券修改失败，请刷新重试');
            }
            $typename = '卡券';
            $title = $coupon['title'];
            $url = h5_url('pages/subPages/coupon/coupon');
        }else{
            $redapck = pdo_get('wlmerchant_redpack_records',array('id' => $id),array('mid','packid','status','transferflag'));
            $parredpack = pdo_get(PDO_NAME.'redpacks',array('id'=>$redapck['packid']),['title','limit_count']);
            if($redapck['mid'] != $_W['mid']){
                MysqlFunction::rollback();
                $this->renderError('红包已经转赠，请勿重复转赠');
            }
            if($redapck['status'] > 0){
                MysqlFunction::rollback();
                $this->renderError('红包状态错误，请刷新重试');
            }
            if($parredpack['limit_count'] > 0){
                $mycounts = Redpack::getReceiveTotal($redapck['packid'],$getMember['id'],1,0);
                if ($mycounts >= $parredpack['limit_count']){
                    MysqlFunction::rollback();
                    $this->renderError('该用户不能拥有更多的此红包');
                }
            }
            $res1 = pdo_update('wlmerchant_redpack_records',array('mid' => $getMember['id'],'transferflag' => 1),array('id' => $id));  //修改红包所属用户
            if(empty($res1)){
                MysqlFunction::rollback();
                $this->renderError('原红包修改失败，请刷新重试');
            }
            $title = $parredpack['title'];
            $typename = '线上红包';
            $url = h5_url('pages/subPages/redpacket/myredpacket');
        }
        //生成转赠记录
        $data = [
            'uniacid' => $_W['uniacid'],
            'type'    => $type,
            'objid'   => $id,
            'omid'    => $_W['mid'],
            'nmid'    => $getMember['id'],
            'status'  => 1,
            'createtime' => time(),
            'gettime' => time(),
            'transfermode' => 1,
            'mobile'  => $mobile
        ];
        $inRes = pdo_insert(PDO_NAME . 'transferRecord', $data);
        if($inRes){
            MysqlFunction::commit();
            //发送模板消息
            $first = '恭喜您获得好友['.$_W['wlmember']['nickname'].']转赠的'.$typename;
            $type = $typename.'转赠通知';
            $content = $typename.'名:['.$title.']';
            $newStatus = '获取成功';
            $remark = '点击前去使用';
            News::jobNotice($getMember['id'],$first,$type,$content,$newStatus,$remark,time(),$url);
            $this->renderSuccess('转赠成功');
        }else{
            MysqlFunction::rollback();
            $this->renderError('转赠记录生成失败，请刷新重试');
        }
    }


    /**
     * Comment: 分享预转赠接口
     * Author: wlf
     * Date: 2021/08/19 16:26
     */
    public function planTransfer(){
        global $_W, $_GPC;
        $type = $_GPC['type']; //类型 1卡券 2红包
        $id = $_GPC['id']; //转赠卡券或红包id
        if(empty($type) || empty($id)){
            $this->renderError('参数错误，请刷新重试');
        }
        //分享图片信息
        if($_W['source'] == 3){
            $trdata['shareimg'] = $_W['wlsetting']['share']['tr_wxapp_share_image'] ? : $_W['wlsetting']['share']['wxapp_share_image'];
        }else{
            $trdata['shareimg'] = $_W['wlsetting']['share']['tr_share_image'] ? : $_W['wlsetting']['share']['share_image'];
        }
        $trdata['shareimg'] = tomedia($trdata['shareimg']);
        //查看是否已有预转赠记录
        $recordid =  pdo_getcolumn(PDO_NAME.'transferRecord',array('uniacid'=>$_W['uniacid'],'type'=>$type['areaid'],'objid' => $id,'omid' => $_W['mid'],'status' => 0),'id');
        if($recordid > 0){
            if($type == 1){
                $coupon = pdo_get('wlmerchant_member_coupons',array('id' => $id),array('title','sub_title'));
                $trdata['title'] = $coupon['sub_title'];
                $trdata['desc'] = $coupon['title'];
            }else{
                $redapck = pdo_get('wlmerchant_redpack_records',array('id' => $id),array('packid'));
                $redpar = pdo_get('wlmerchant_redpacks',array('id' => $redapck['packid']),array('title','full_money','cut_money'));
                $trdata['title'] = '满'.$redpar['full_money'].'元减'.$redpar['cut_money'].'元红包在线领取';
                $trdata['desc'] = $redpar['title'];
            }
            $trdata['recordid'] = $recordid;
            $this->renderSuccess('转赠记录生成成功',$trdata);
        }else{
            if($type == 1){
                $coupon = pdo_get('wlmerchant_member_coupons',array('id' => $id),array('mid','parentid','sub_title','status','title','orderno'));
                if($coupon['mid'] != $_W['mid']){
                    $this->renderError('卡券已经转赠，请勿重复转赠');
                }
                if($coupon['status'] != 1){
                    $this->renderError('卡券状态错误，请刷新重试');
                }
                $trdata['title'] = $coupon['sub_title'];
                $trdata['desc'] = $coupon['title'];
                pdo_update('wlmerchant_member_coupons',array('status' => 6),array('id' => $id));
            }else{
                $redapck = pdo_get('wlmerchant_redpack_records',array('id' => $id),array('mid','packid','status'));
                if($redapck['mid'] != $_W['mid']){
                    $this->renderError('红包已经转赠，请勿重复转赠');
                }
                if($redapck['status'] > 0){
                    $this->renderError('红包状态错误，请刷新重试');
                }
                $redpar = pdo_get('wlmerchant_redpacks',array('id' => $redapck['packid']),array('title','full_money','cut_money'));
                $trdata['title'] = '满'.$redpar['full_money'].'元减'.$redpar['cut_money'].'元红包在线领取';
                $trdata['desc'] = $redpar['title'];
                pdo_update('wlmerchant_redpack_records',array('status' => 6),array('id' => $id));
            }
            $data = [
                'uniacid' => $_W['uniacid'],
                'type'    => $type,
                'objid'   => $id,
                'omid'    => $_W['mid'],
                'nmid'    => 0,
                'status'  => 0,
                'createtime' => time(),
                'transfermode' => 2
            ];
            $inRes = pdo_insert(PDO_NAME . 'transferRecord', $data);
            $recordid = pdo_insertid();
            $trdata['recordid'] = $recordid;
            if($recordid){
                $this->renderSuccess('转赠记录生成成功',$trdata);
            }else{
                $this->renderError('转赠记录生成失败，请刷新重试');
            }
        }
    }

    /**
     * Comment: 转赠信息
     * Author: wlf
     * Date: 2021/08/20 09:46
     */
    public function transferInfo(){
        global $_W, $_GPC;
        $id = $_GPC['id'];  //记录
        if(empty($id)){
            $this->renderError('无id参数');
        }
        $record = pdo_get('wlmerchant_transferRecord',array('id' => $id),array('type','status','objid','nmid','omid'));
        if(empty($record)){
            $this->renderError('参数错误，无信息');
        }
        //用户信息
        $member = pdo_get('wlmerchant_member',array('id' => $record['omid']),array('nickname','avatar'));
        $record['nickname'] = $member['nickname'];
        $record['avatar'] = tomedia($member['avatar']);
        if($record['type'] == 1){ //卡券
            $coupon = pdo_get('wlmerchant_member_coupons',array('id' => $record['objid']),array('title','sub_title'));
            $record['title'] = $coupon['title'];
            $record['sub_title'] = $coupon['sub_title'];
        }else{ //红包
            $redpack = pdo_get('wlmerchant_redpack_records',array('id' => $record['objid']),array('packid'));
            $parentRed = pdo_get('wlmerchant_redpacks',array('id' => $redpack['packid']),array('full_money','cut_money'));
            $record['title'] = $parentRed['cut_money'].'元';
            if($parentRed['full_money'] > 0){
                $record['sub_title'] = '满'.$parentRed['full_money'].'元可使用';
            }else{
                $record['sub_title'] = '任意金额可使用';
            }
        }
        if($record['omid'] == $_W['mid']){
            $record['ownflag'] = 1;
        }else{
            $record['ownflag'] = 0;
        }
        $record['ownget']  = $record['nmid'] == $_W['mid'] ? 1 : 0;

        $this->renderSuccess('转赠信息',$record);

    }

    /**
     * Comment: 领取转赠
     * Author: wlf
     * Date: 2021/08/24 11:03
     */
    public function getTransfer()
    {
        global $_W, $_GPC;
        $id = $_GPC['id'];  //记录
        if (empty($id)) {
            $this->renderError('无id参数');
        }
        $record = pdo_get('wlmerchant_transferRecord', array('id' => $id), array('type', 'status', 'objid', 'omid'));
        if (empty($record)) {
            $this->renderError('参数错误，无信息');
        }
        $getMember = pdo_get('wlmerchant_member',array('id' => $_W['mid']),array('id','nickname','mobile'));
        if(empty($getMember)){
            $this->renderError('无用户信息，请先登录');
        }
        if(empty($getMember['mobile'])){
            $this->renderError('未绑定手机号');
        }
        $nickname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$record['omid']),'nickname');
        MysqlFunction::setTrans(4);
        MysqlFunction::startTrans();
        //修改原纪录状态
        if($record['type'] == 1){
            $coupon = pdo_get('wlmerchant_member_coupons',array('id' => $record['objid']),array('mid','parentid','status','title','orderno','transferflag'));

            $parcoupon = pdo_get(PDO_NAME.'couponlist',array('id'=>$coupon['parentid']),['get_limit','transferlevel']);
            $get_limit = $parcoupon['get_limit'];
            if($coupon['get_limit'] > 0){
                $num = wlCoupon::getCouponNum($coupon['parentid'],1,$getMember['id']);
                if($num > $get_limit || $num == $get_limit){
                    MysqlFunction::rollback();
                    $this->renderError('您不能拥有更多的此卡券');
                }
            }

//            $transferlevel = unserialize($parcoupon['transferlevel']);
//            if(!empty($transferlevel)){
//                $halfflag = WeliamWeChat::VipVerification($_W['mid']);
//                if(empty($halfflag)){
//                    $levelid = -1;
//                }else{
//                    $levelid = $halfflag['levelid'];
//                }
//                if(!in_array($levelid,$transferlevel)){
//                    MysqlFunction::rollback();
//                    $this->renderError('您所在的会员等级无法领取此卡券');
//                }
//            }

            if(empty($coupon['transferflag'])){
                $res2 = pdo_update('wlmerchant_order',array('status' => 2),array('orderno' => $coupon['orderno'],'status' => 1));  //修改订单状态
            }else{
                $res2 = 1;
            }
            $res1 = pdo_update('wlmerchant_member_coupons',array('mid' => $getMember['id'],'transferflag'=>1,'status' => 1),array('id' => $record['objid']));  //修改卡券所属用户
            $res3 = pdo_update('wlmerchant_smallorder',array('mid' => $getMember['id']),array('orderno' => $coupon['orderno']));  //修改子订单状态
            if(empty($res1) || empty($res2) || empty($res3)){
                MysqlFunction::rollback();
                $this->renderError('原卡券修改失败，请刷新重试');
            }
            $typename = '卡券';
            $title = $coupon['title'];
            $url = h5_url('pages/subPages/coupon/coupon');
        }else{
            $redapck = pdo_get('wlmerchant_redpack_records',array('id' => $record['objid']),array('mid','packid','status'));
            $parredpack = pdo_get(PDO_NAME.'redpacks',array('id'=>$redapck['packid']),['transferlevel','title','limit_count']);
            if($parredpack['limit_count'] > 0){
                $mycounts = Redpack::getReceiveTotal($redapck['packid'],$getMember['id'],1,0);
                if ($mycounts >= $parredpack['limit_count']){
                    MysqlFunction::rollback();
                    $this->renderError('您不能拥有更多的此红包');
                }
            }
//            $transferlevel = unserialize($parredpack['transferlevel']);
//            if(!empty($transferlevel)){
//                $halfflag = WeliamWeChat::VipVerification($_W['mid']);
//                if(empty($halfflag)){
//                    $levelid = -1;
//                }else{
//                    $levelid = $halfflag['levelid'];
//                }
//                if(!in_array($levelid,$transferlevel)){
//                    MysqlFunction::rollback();
//                    $this->renderError('您所在的会员等级无法领取此红包');
//                }
//            }
            $res1 = pdo_update('wlmerchant_redpack_records',array('mid' => $getMember['id'],'transferflag' => 1,'status' => 0),array('id' => $record['objid']));  //修改红包所属用户
            if(empty($res1)){
                MysqlFunction::rollback();
                $this->renderError('原红包修改失败，请刷新重试');
            }
            $title = $parredpack['title'];
            $typename = '线上红包';
            $url = h5_url('pages/subPages/redpacket/myredpacket');
        }
        //修改转赠记录
        $data = [
            'nmid' => $_W['mid'],
            'status' => 1,
            'gettime' => time(),
            'mobile'  => $getMember['mobile']
        ];
        $inRes = pdo_update(PDO_NAME.'transferRecord',$data,array('id' => $id));
        if($inRes){
            MysqlFunction::commit();
            //发送模板消息
            $first = '恭喜您获得好友['.$nickname.']转赠的'.$typename;
            $type = $typename.'转赠通知';
            $content = $typename.'名:['.$title.']';
            $newStatus = '获取成功';
            $remark = '点击前去使用';
            News::jobNotice($getMember['id'],$first,$type,$content,$newStatus,$remark,time(),$url);
            //发送给转赠人
            $first = '您的好友['.$_W['wlmember']['nickname'].']领取了您分享的'.$typename;
            $type = $typename.'转赠领取通知';
            $newStatus = '转赠成功';
            $remark = '点击查看详情';
            News::jobNotice($record['omid'],$first,$type,$content,$newStatus,$remark,time(),$url);
            $this->renderSuccess('领取成功');
        }else{
            MysqlFunction::rollback();
            $this->renderError('领取失败，请刷新重试');
        }
    }

    /**
     * Comment: 取消转赠
     * Author: wlf
     * Date: 2021/08/24 11:28
     */
    public function cancelTransfer()
    {
        global $_W, $_GPC;
        $id = $_GPC['id'];  //记录
        $objid = $_GPC['objid'];  //对象id
        $type = $_GPC['type'];  //对象类型 1卡券 2红包

        if($id > 0){
            $record = pdo_get('wlmerchant_transferRecord', array('id' => $id), array('type', 'status', 'objid', 'omid'));
        }else if($objid > 0 && $type > 0){
            $record = pdo_get('wlmerchant_transferRecord', array('type' => $type ,'status' => 0,'objid' => $objid), array('type','id','status', 'objid', 'omid'));
            $id = $record['id'];
        }
        if (empty($record)) {
            $this->renderError('参数错误，无信息');
        }
        if($record['omid'] != $_W['mid']){
            $this->renderError('非原用户，无法取消');
        }
        if($record['type'] == 1) {
            pdo_update('wlmerchant_member_coupons',array('status' => 1),array('id' => $record['objid']));
        }else{
            pdo_update('wlmerchant_redpack_records',array('status' => 0),array('id' => $record['objid']));
        }
        $data = [
            'status' => 2,
            'canceltime' => time(),
        ];
        $inRes = pdo_update(PDO_NAME.'transferRecord',$data,array('id' => $id));
        if($inRes){
            $this->renderSuccess('取消成功');
        }else{
            $this->renderError('取消失败，请刷新重试');
        }

    }

    /**
     * Comment: 已转赠列表
     * Author: wlf
     * Date: 2021/08/27 11:41
     */
    public function alreadyTransferList(){
        global $_W, $_GPC;
        $type = $_GPC['type']; //类型 1卡券 2红包
        $mobile = $_GPC['mobile'];  //筛选手机号
        $page = $_GPC['page'] ? $_GPC['page'] : 1;  //页码
        $pageStart = $page*20 - 20;
        $where = " WHERE uniacid = {$_W['uniacid']} AND type = {$type} AND omid = {$_W['mid']} AND status = 1";
        if(!empty($mobile)){
            $where .= " AND mobile LIKE '%{$mobile}%'";
        }
        $list = pdo_fetchall("select objid,type,nmid,createtime,mobile,transfermode from " . tablename(PDO_NAME.'transferRecord').$where." GROUP BY objid ORDER BY id DESC LIMIT {$pageStart},20" );
        if(count($list) > 0){
            foreach($list as &$li){
                if($li['type'] == 1){  //卡券
                    $coupon = pdo_get('wlmerchant_member_coupons',array('id' => $li['objid']),array('parentid','title','sub_title','endtime','starttime','orderno'));
                    $parent = pdo_get('wlmerchant_couponlist' , ['id' => $coupon['parentid']] , ['merchantid']);
                    $store  = pdo_get('wlmerchant_merchantdata' , ['id' => $parent['merchantid']] , ['storename' , 'logo']);
                    $li['storename'] = $store['storename'];
                    $li['storelogo'] = tomedia($store['logo']);
                    $li['storeid'] = $parent['merchantid'];
                    $li['title'] = $coupon['title'];
                    $li['sub_title'] = $coupon['sub_title'];
                    $li['orderno'] = $coupon['orderno'];
                    $li['endtime'] = date('Y-m-d',$coupon['endtime']);
                    $li['starttime'] = date('Y-m-d',$coupon['starttime']);
                }else{  //红包
                    $redpack = pdo_get('wlmerchant_redpack_records',array('id' => $li['objid']),array('start_time','end_time','packid'));
                    $redparent = pdo_get('wlmerchant_redpacks',array('id' => $redpack['packid']),array('title','full_money','cut_money','usegoods_type'));
                    $li['title'] = $redparent['title'];
                    $endtime = date('Y-m-d',$redpack['end_time']);
                    $starttime = date('Y-m-d',$redpack['start_time']);
                    $li['time'] = $starttime.'-'.$endtime;
                    $li['full_money'] = $redparent['full_money'];
                    $li['cut_money'] = $redparent['cut_money'];
                    $li['usegoods_type'] = $redparent['usegoods_type'];
                    if ($redparent['usegoods_type'] == 1) {
                        $li['use_where'] = "仅限指定地区可用";
                    }else if ($redparent['usegoods_type'] == 2) {
                        $li['use_where'] = "仅限指定商家可用";
                    }else if ($redparent['usegoods_type'] == 3) {
                        $li['use_where'] = "仅限指定商品可用";
                    }else {
                        $li['use_where'] = '全平台可用';
                    }
                }
                $li['mobile'] = substr($li['mobile'],0,3).'****'.substr($li['mobile'], -4);
                $li['getname'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$li['nmid']),'nickname');
                $li['transfertime'] = date('Y/m/d H:i:s',$li['createtime']);
            }
        }
        if($page = 1){
            $alllist = pdo_fetchall("select distinct objid from " . tablename(PDO_NAME.'transferRecord').$where);
            $allnum = count($alllist);
            $data['totalnum'] = $allnum;
            $data['pagetotal'] = ceil($allnum / 20);
        }
        $data['list'] = $list;
        $this->renderSuccess('已转赠列表' , $data);

    }


}
