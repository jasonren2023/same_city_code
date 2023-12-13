<?php
defined('IN_IA') or exit('Access Denied');

class HousekeepModuleUniapp extends Uniapp {

    public function __construct () {
        global $_W, $_GPC;
        $set = Setting::agentsetting_read('housekeep');
        if(empty($set['status'])){
            $this->renderError('家政服务已关闭');
        }

    }

    /**
     * Comment: 用户发布/编辑需求页面初始化接口
     * Author: wlf
     * Date: 2021/04/15 10:09
     */
    public function editDemandPage(){
        global $_GPC, $_W;
        //判断权限
        $jur = Housekeep::getJurisdiction(1,$_W['mid'],'demand');
        if($jur > 0){
            $this->renderError('认证后才能发布需求');
        }
        $vipju = Housekeep::getVipRight($_W['mid'],'demand');
        if($vipju > 0){
            $this->renderError('仅会员可以发布需求，请开通会员。',['toopen' => 1]);
        }
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array('housekeep',$mastmobile)){
            $this->renderError('未绑定手机号');
        }
        $id = $_GPC['id'];
        if($id > 0){
            $demand = pdo_get('wlmerchant_housekeep_demand',array('id' => $id));
            $demand['thumbs'] = Housekeep::beautifyImgInfo($demand['thumbs']);
        }else{
            $demand = [
                'type'    => 0,
                'address' => '',
                'lat'     => 0,
                'lng'     => 0,
                'visitingtime' => 0,
                'detail'  => '',
                'thumbs'  => [],
                'mobile'  => pdo_getcolumn(PDO_NAME.'member',array('id'=>$_W['mid']),'mobile')
            ];
        }
        $categoryes = Housekeep::getCategory();
        $data['demand'] = $demand;
        $data['cate'] = $categoryes;
        $this->renderSuccess('初始化信息',$data);
    }

    /**
     * Comment: 用户发布/编辑需求提交接口
     * Author: wlf
     * Date: 2021/04/15 14:10
     */
    public function editDemandApi(){
        global $_GPC, $_W;
        $set = Setting::agentsetting_read('housekeep');
        $id = $_GPC['id'];
        $demand = json_decode(base64_decode($_GPC['demandinfo']) , true);
        $demand['thumbs'] = serialize($demand['thumbs']);
        if(empty($demand['type'])){
            $this->renderError('请选择类目');
        }
        $demand['onetype'] = pdo_getcolumn(PDO_NAME.'housekeep_type',array('id'=>$demand['type']),'onelevelid');
        if(empty($demand['lng']) || empty($demand['lat'])){
            $this->renderError('请设置位置定位信息');
        }
        //付费发帖金额
        if($set['paystatus'] > 0){
            $payarray = unserialize($set['viparray']);
            $vipinfo = WeliamWeChat::VipVerification($_W['mid']);
            if(empty($vipinfo)){
                $levelid = 'N';
            }else{
                $levelid = $vipinfo['levelid'];
            }
            $price = $payarray[$levelid];
        }else{
            $price = 0;
        }
        //查询是否需要审核
        $getstatus = Housekeep::getStatus(2,$_W['mid'],$set,1);
        $demand['status'] = $getstatus['status'];
        if($id > 0){
            $res = pdo_update('wlmerchant_housekeep_demand',$demand,array('id' => $id));
        }else{
            $demand['uniacid'] = $_W['uniacid'];
            $demand['aid'] = $_W['aid'];
            $demand['mid'] = $_W['mid'];
            $demand['createtime'] = time();
            $demand['updatetime'] = time();
            if($price > 0){
                $demand['status'] = 3;
            }
            $res = pdo_insert(PDO_NAME.'housekeep_demand',$demand);
            $demandid = pdo_insertid();
        }
        if($res){
            if($demand['status'] == 5){
                $typetitle = pdo_getcolumn(PDO_NAME.'housekeep_type',array('id'=>$demand['type']),'title');
                $membername = pdo_getcolumn(PDO_NAME.'member',array('id'=>$_W['mid']),'nickname');
                //发送模板消息
                $first   = '您好，您有一个待审核任务需要处理';
                $type    = '审核用户家政需求';
                $content = '需求类目:'.$typetitle;
                $status  = '待审核';
                $remark  = "用户[" . $membername . "]发布了一个商品待审核,请管理员尽快前往后台审核";
                News::noticeAgent('housekeep',$_W['aid'],$first,$type,$content,$status,$remark,time(),'');
            }else if($demand['status'] == 3){
                $orderData = array(
                    'uniacid'    => $_W['uniacid'],
                    'mid'        => $demand['mid'],
                    'aid'        => $demand['aid'],
                    'fkid'       => $demandid,
                    'createtime' => time(),
                    'orderno'    => createUniontid(),
                    'price'      => $price,
                    'plugin'     => 'housekeep',
                    'payfor'     => 'housekeeporder',
                    'specid'     => $demand['type'],
                    'fightstatus'=> 3
                );
                pdo_insert(PDO_NAME.'order', $orderData);
                $orderid = pdo_insertid();
            }
            $this->renderSuccess('保存成功',['orderid' => $orderid]);
        }else{
            $this->renderError('保存失败，请刷新重试');
        }

    }

    /**
     * Comment: 个人中心信息接口
     * Author: wlf
     * Date: 2021/04/21 15:02
     */
    public function personalCenter(){
        global $_GPC, $_W;
        $member = pdo_get('wlmerchant_member',array('id' => $_W['mid']),array('id','nickname','avatar','mobile'));
        $data['nickname'] = $member['nickname'];
        $data['avatar'] = tomedia($member['avatar']);
        $data['mobile'] = $member['mobile'];
        //查询是否入驻
        $artificer = pdo_get('wlmerchant_housekeep_artificer',array('mid' => $_W['mid'],'uniacid' => $_W['uniacid']),array('name','id','mobile','status','reason'));
        if($artificer['id'] > 0){
            $data['arid'] = $artificer['id'];
            $data['arname'] = $artificer['arname'];
            $data['reason'] = $artificer['reason'];
            //我的业务
            $data['servicenum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_housekeep_service')." WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND type = 2 AND objid = {$artificer['id']} AND status != 4");
            $data['mobile'] = $artificer['mobile'];
            $data['status'] = $artificer['status'];
            //评论数量
            $data['commentnum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_comment')." WHERE sid = {$artificer['id']} AND housekeepflag = 1 AND checkone = 2");

        }else{
            $data['arid'] = 0;
            $data['arname'] = '';
        }
        //认证
        if(p('attestation')){
            $data['attestation'] = Attestation::checkAttestation(1,$member['id']);
            if($data['attestation']['bondflag'] > 0){
                $data['attestation']['bondmoney'] = pdo_getcolumn(PDO_NAME.'attestation_money',array('uniacid'=>$_W['uniacid'],'mid'=>$member['id'],'type' => 1),'money');
            }
            $attSet = Setting::wlsetting_read('attestation');
            $data['attSwitch'] = $attSet['switch'];
            if($attSet['memberstatus'] > 0 && $attSet['moneyswitch'] > 0){
                $data['moneySwitch'] = 1;
            }else{
                $data['moneySwitch'] = 0;
            }
        }else{
            $data['attestation'] = [];
            $data['moneySwitch'] = 0;
            $data['attSwitch'] = 0;
        }
        //我的订单
        $data['ordernum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_order')." WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} AND plugin = 'housekeep' AND fightstatus = 1 ");
        //我发布的需求
        $data['demandnum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_housekeep_demand')." WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} AND aid = {$_W['aid']}");

        $this->renderSuccess('个人中心信息',$data);
    }

    /**
     * Comment: 我的需求页面接口
     * Author: wlf
     * Date: 2021/04/22 10:09
     */
    public function myDemandList(){
        global $_GPC, $_W;
        $set = Setting::agentsetting_read('housekeep');
        $vipinfo = WeliamWeChat::VipVerification($_W['mid']);
        $toparray = unserialize($set['topprice']);
        $refarray = unserialize($set['refarray']);
        //设置信息
        $data['set']['payrefstatus'] = $set['payrefstatus'];
        $data['set']['paytopstatus'] = $set['paytopstatus'];
        //列表信息
        $list = pdo_getall('wlmerchant_housekeep_demand',array('mid' => $_W['mid'],'uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => [0,3,1,5,6]),array('id','mid','status','type','address','visitingtime','detail','thumbs','reason','topflag'),'','topflag DESC,updatetime DESC');
        if(!empty($list)){
            foreach($list as &$li){
                $li['typename'] = pdo_getcolumn('wlmerchant_housekeep_type',array('id' => $li['type']),'title');
                $li['visitingtime'] = str_replace(array('AM','PM'),array('上午','下午'),date("Y-m-d A H:i",$li['visitingtime']));
                $li['thumbs'] = Housekeep::beautifyImgInfo($li['thumbs']);
                if($li['status'] == 3){
                    $li['payorderid'] = pdo_getcolumn(PDO_NAME.'order',array('plugin' => 'housekeep','fightstatus' => 3,'fkid' => $li['id']),'id');
                }
            }
        }else{
            $list = [];
        }
        $data['list'] = $list;
        //置顶信息
        foreach($toparray as &$vip){
            if($vipinfo['id'] > 0){
                $vip['topprice'] = $vip['topvipprice'];
            }
            unset($vip['topvipprice']);
        }
        $data['toparray'] = $toparray;
        //刷新信息
        if(empty($vipinfo)){
            $levelid = 'N';
        }else{
            $levelid = $vipinfo['levelid'];
        }
        $data['refmoney'] = $refarray[$levelid];

        $this->renderSuccess('我的需求列表信息',$data);
    }

    /**
     * Comment: 关闭/删除我的需求接口
     * Author: wlf
     * Date: 2021/04/22 10:52
     */
    public function changeDemandStatus(){
        global $_GPC, $_W;
        $id = $_GPC['id'];  //需求id
        $dostatus = $_GPC['dostatus'];  //1上架 0关闭 4删除
        if(empty($id)){
            $this->renderError('缺少重要参数，请刷新重试');
        }
        if($dostatus == 1){
            $set = Setting::agentsetting_read('housekeep');
            $getstatus = Housekeep::getStatus(2,$id,$set,1);
            $dostatus = $getstatus['status'];
        }
        $res = pdo_update('wlmerchant_housekeep_demand',array('status' => $dostatus),array('id' => $id));
        if($res){
            if($dostatus == 5){
                $demand = pdo_get('wlmerchant_housekeep_demand',array('id' => $id),array('type'));
                $typetitle = pdo_getcolumn(PDO_NAME.'housekeep_type',array('id'=>$demand['type']),'title');
                $membername = pdo_getcolumn(PDO_NAME.'member',array('id'=>$_W['mid']),'nickname');
                //发送模板消息
                $first   = '您好，您有一个待审核任务需要处理';
                $type    = '审核用户家政需求';
                $content = '需求类目:'.$typetitle;
                $status  = '待审核';
                $remark  = "用户[" . $membername . "]发布了一个商品待审核,请管理员尽快前往后台审核";
                News::noticeAgent('housekeep',$_W['aid'],$first,$type,$content,$status,$remark,time(),'');
            }
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('操作失败，请刷新重试');
        }
    }

    /**
     * Comment: 需求刷新接口
     * Author: wlf
     * Date: 2021/04/25 11:06
     */
    public function refreshDemand(){
        global $_GPC, $_W;
        $id = $_GPC['id'];  //需求id
        if(empty($id)){
            $this->renderError('参数错误，请刷新重试');
        }
        $demand = pdo_get('wlmerchant_housekeep_demand',array('id' => $id),array('mid','aid','updatetime'));
        $set = Setting::agentsetting_read('housekeep');
        //判断时间
        if($demand['updatetime'] + $set['refhour'] * 3600 > time()){
            $hourflag = $demand['updatetime'] + $set['refhour'] * 3600 - time();
            $hourflag = ceil($hourflag/3600);
            $this->renderError('请勿频繁刷新，请在'.$hourflag.'小时后重试。');
        }
        //判断刷新金额
        $vipinfo = WeliamWeChat::VipVerification($_W['mid']);
        if(empty($vipinfo)){
            $levelid = 'N';
        }else{
            $levelid = $vipinfo['levelid'];
        }
        $refarray = unserialize($set['refarray']);
        $refmoney = $refarray[$levelid];
        if($refmoney > 0){ //付费刷新
            $orderData = array(
                'uniacid'    => $_W['uniacid'],
                'mid'        => $demand['mid'],
                'aid'        => $demand['aid'],
                'fkid'       => $id,
                'createtime' => time(),
                'orderno'    => createUniontid(),
                'price'      => $refmoney,
                'plugin'     => 'housekeep',
                'payfor'     => 'housekeeporder',
                'fightstatus'=> 5
            );
            pdo_insert(PDO_NAME.'order', $orderData);
            $orderid = pdo_insertid();
            if($orderid > 0){
                $this->renderSuccess('需要支付',['orderid' => $orderid]);
            }else{
                $this->renderError('操作失败，请刷新重试');
            }
        }else{
            $res = pdo_update('wlmerchant_housekeep_demand',array('updatetime' => time()),array('id' => $id));
            if($res){
                $this->renderSuccess('刷新成功',['orderid' => 0]);
            }else{
                $this->renderError('操作失败，请刷新重试');
            }
        }
    }

    /**
     * Comment: 需求置顶接口
     * Author: wlf
     * Date: 2021/04/25 16:53
     */
    public function TopDemand(){
        global $_GPC, $_W;
        $id = $_GPC['id'];  //需求id
        $day = $_GPC['day']; //置顶天数
        if(empty($id) || empty($day)){
            $this->renderError('参数错误，请刷新重试');
        }
        $demand = pdo_get('wlmerchant_housekeep_demand',array('id' => $id),array('mid','aid'));
        $set = Setting::agentsetting_read('housekeep');
        //判断置顶数量
        if($set['topnumber'] > 0){
            $topnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_housekeep_demand')." WHERE topflag = 1");
            if($topnum >= $set['topnumber']){
                $this->renderError('当前置顶数量已满，请稍后再试');
            }
        }
        //判断置顶金额
        $vipinfo = WeliamWeChat::VipVerification($_W['mid'],true);
        $toparray = unserialize($set['topprice']);
        foreach ($toparray as $key => $v) {
            if ($day == $v['day']) {
                $orderprice = $vipinfo > 0 ? $v['topvipprice'] : $v['topprice'];
            }
        }
        if($orderprice < 0.01){
            $this->renderError('金额错误，请刷新重试');
        }
        $orderData = array(
            'uniacid'    => $_W['uniacid'],
            'mid'        => $demand['mid'],
            'aid'        => $demand['aid'],
            'fkid'       => $id,
            'num'        => $day,
            'createtime' => time(),
            'orderno'    => createUniontid(),
            'price'      => $orderprice,
            'plugin'     => 'housekeep',
            'payfor'     => 'housekeeporder',
            'fightstatus'=> 4
        );
        pdo_insert(PDO_NAME.'order', $orderData);
        $orderid = pdo_insertid();
        if($orderid > 0){
            $this->renderSuccess('需要支付',['orderid' => $orderid]);
        }else{
            $this->renderError('操作失败，请刷新重试');
        }
    }

    /**
     * Comment: 服务者入驻页面初始化接口
     * Author: wlf
     * Date: 2021/04/28 15:12
     */
    public function editArtificerPage(){
        global $_GPC, $_W;
        //判断权限
        $jur = Housekeep::getJurisdiction(1,$_W['mid'],'artificer');
        if($jur > 0){
            $this->renderError('认证后才能入驻成为服务者');
        }
        $id = pdo_getcolumn(PDO_NAME.'housekeep_artificer',array('uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'mid' => $_W['mid']),'id');
        if($id > 0){
            $artificer = pdo_get('wlmerchant_housekeep_artificer',array('id' => $id));
            $artificer['thumbs'] = Housekeep::beautifyImgInfo($artificer['thumbs']);
            $artificer['casethumbs'] = Housekeep::beautifyImgInfo($artificer['casethumbs']);
            if(!empty($artificer['tagarray'])){
                $artificer['tagarray'] = unserialize($artificer['tagarray']);
            }
            if(!empty($artificer['region'])){
                $artificer['region'] = unserialize($artificer['region']);
            }
            //获取服务类目
            $artificer['catearray'] = Housekeep::getRelation($id,2,1);

            $artificer['thumb'] = tomedia($artificer['thumb']);
        }else{
            $artificer = [
                'name'    => '',
                'mobile'  => '',
                'gender'  => 1,
                'address' => '',
                'lat'     => 0,
                'lng'     => 0,
                'detail'  => '',
                'thumb'   => '',
                'thumbs'  => [],
                'casethumbs' => [],
                'tagarray' => [],
                'mealid'   => 0,
                'region'   => [],
                'catearray' => []
            ];
        }
        $data['artificer'] = $artificer;
        //服务类型
        $categoryes = Housekeep::getCategory();
        $data['cate'] = $categoryes;
        //入驻类型
        if(empty($id) || $artificer['status'] == 2 || $artificer['status'] == 3){
            $meals = pdo_getall('wlmerchant_housekeep_meals',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','name','is_free','price','day'),'','sort DESC');
            if(empty($meals)){
                $this->renderError('请联系管理员先设置入驻套餐');
            }
            foreach($meals as & $me){
                if($me['is_free'] > 0){
                    $me['name'] .= '(免费/'.$me['day'].'天)';
                }else{
                    $me['name'] .= '('.$me['price'].'元/'.$me['day'].'天)';
                }
            }
            $data['meals'] = $meals;
        }
        //服务区域
        $areaid = pdo_getcolumn(PDO_NAME.'oparea',array('uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']),'areaid');
        $areas = pdo_getall('wlmerchant_area',array('pid' => $areaid),array('id','name'),'','id ASC');
        if(empty($areas)){
            $areas[] = pdo_get('wlmerchant_area',array('id' => $areaid),array('id','name'));
        }
        $data['areas'] = $areas;
        $this->renderSuccess('初始化信息',$data);
    }

    /**
     * Comment: 服务者入驻页面提交编辑接口
     * Author: wlf
     * Date: 2021/04/28 17:26
     */
    public function editArtificerApi(){
        global $_GPC, $_W;
        $id = $_GPC['id'];
        $mealid = $_GPC['mealid'];
        $artificer = json_decode(base64_decode($_GPC['artificerinfo']) , true);
        $catearray = $artificer['catearray'];
        unset($artificer['catearray']);
        $artificer['thumbs'] = serialize($artificer['thumbs']);
        $artificer['casethumbs'] = serialize($artificer['casethumbs']);
        $artificer['tagarray'] = serialize($artificer['tagarray']);
        $artificer['region'] = serialize($artificer['region']);
        if(empty($catearray)){
            $this->renderError('请选择类目');
        }
        if(empty($artificer['lng']) || empty($artificer['lat'])){
            $this->renderError('请设置位置定位信息');
        }
        if(empty($id) && empty($mealid)){
            $this->renderError('请选择入驻套餐');
        }
        $orderid = 0;
        if($mealid > 0){  //编辑
            $meal = pdo_get('wlmerchant_housekeep_meals',array('id' => $mealid),array('day','is_free','price','check'));
            if($meal['is_free'] > 0){
                if($meal['check'] > 0){
                    $artificer['status'] = 5;
                }else{
                    $artificer['status'] = 1;
                }
                $artificer['endtime'] = time() + $meal['day'] * 86400;
            }else{
                $artificer['status'] = 2;
            }
            if($artificer['id'] > 0){
                $id = $artificer['id'];
                pdo_update('wlmerchant_housekeep_artificer',$artificer,array('id' => $id));
                $res = 1;
            }else{
                $artificer['uniacid'] = $_W['uniacid'];
                $artificer['aid'] = $_W['aid'];
                $artificer['mid'] = $_W['mid'];
                $artificer['mealid'] = $mealid;
                $artificer['createtime'] = time();
                $res = pdo_insert(PDO_NAME.'housekeep_artificer',$artificer);
                $id = pdo_insertid();
            }
            if(empty($meal['is_free'])){
                $orderData = [
                    'uniacid'    => $_W['uniacid'],
                    'mid'        => $artificer['mid'],
                    'aid'        => $artificer['aid'],
                    'fkid'       => $id,
                    'specid'     => $mealid,
                    'createtime' => time(),
                    'orderno'    => createUniontid(),
                    'price'      => $meal['price'],
                    'plugin'     => 'housekeep',
                    'payfor'     => 'housekeeporder',
                    'fightstatus'=> 2
                ];
                pdo_insert(PDO_NAME.'order', $orderData);
                $orderid = pdo_insertid();
            }
        }else{
            $meal = pdo_get('wlmerchant_housekeep_meals',array('id' => $artificer['mealid']),array('check'));
            if($meal['check'] > 0){
                $artificer['status'] = 5;
            }else{
                $artificer['status'] = 1;
            }
            pdo_update('wlmerchant_housekeep_artificer',$artificer,array('id' => $id));
            $res = 1; //防止只修改分类无法判断
        }
        if($res){
            //处理分类
            pdo_delete('wlmerchant_housekeep_relation', array('type' => 2,'objid' => $id));
            foreach ($catearray as $item) {
                $scate = pdo_get(PDO_NAME . 'housekeep_type', array('id' => $item), array('onelevelid'));
                $res = pdo_insert('wlmerchant_housekeep_relation', ['type' => 2,'objid' => $id, 'onelevelid' => $scate['onelevelid'], 'twolevelid' => $item]);
            }
            if($artificer['status'] == 5){
                $membername = pdo_getcolumn(PDO_NAME.'member',array('id'=>$_W['mid']),'nickname');
                //发送模板消息
                $first   = '您好，一个家政服务者入驻申请待审核';
                $type    = '审核家政服务者入驻信息';
                $content = '服务者姓名:'.$artificer['name'];
                $status  = '待审核';
                $remark  = "微信用户[" . $membername . "]入驻家政服务者申请,请管理员尽快前往后台审核";
                News::noticeAgent('housekeep',$_W['aid'],$first,$type,$content,$status,$remark,time(),'');
            }
            $this->renderSuccess('操作成功',['orderid' => $orderid]);
        }else{
            $this->renderError('操作失败，请刷新重试');
        }
    }

    /**
     * Comment: 服务项目发布页面初始化
     * Author: wlf
     * Date: 2021/04/29 14:45
     */
    public function editServicePage(){
        global $_GPC, $_W;
        $id = $_GPC['id'];
        $type = $_GPC['type'];    //服务商类型 类型 1商户发布 2个人发布'
        $objid = $_GPC['objid'];  //服务商id
        if(empty($type) || empty($objid)){
            $this->renderError('无重要参数，请刷新重试');
        }
        if($type == 2){
            $Jtype = 1;
            $Jid = pdo_getcolumn(PDO_NAME.'housekeep_artificer',array('id'=>$objid),'mid');
        }else{
            $Jtype = 2;
            $Jid = $objid;
        }
        $jur = Housekeep::getJurisdiction($Jtype,$Jid,'service');
        if($jur > 0){
            $this->renderError('认证后才能发布服务项目');
        }
        if($id > 0){
            $service = pdo_get('wlmerchant_housekeep_service',array('id' => $id));
            $service['thumb'] = tomedia($service['thumb']);
            $service['adv'] = Housekeep::beautifyImgInfo($service['adv']);
//            if(!empty($service['appointarray'])){
//                $service['appointarray'] = unserialize($service['appointarray']);
//            }
            //获取服务类目
            $service['catearray'] = Housekeep::getRelation($id,1,1);
        }else{
            $service = [
                'title'         => '',
                'pricetype'     => '',
                'price'         => '',
                'detail'        => '',
                'adv'           => [],
                'thumb'         => '',
                'videourl'      => '',
                'unit'          => '',
                'catearray'     => []
            ];
        }
        $data['service'] = $service;
        //服务类型
        $categoryes = Housekeep::getStoreCategory($objid,$type);
        $data['cate'] = $categoryes;

        $this->renderSuccess('初始化信息',$data);
    }

    /**
     * Comment: 服务项目发布接口
     * Author: wlf
     * Date: 2021/04/29 15:43
     */
    public function editServiceApi(){
        global $_GPC, $_W;
        $set = Setting::agentsetting_read('housekeep');
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        $objid = $_GPC['objid'];
        $service = json_decode(base64_decode($_GPC['serviceinfo']),true);
        if(empty($service)){
            $this->renderError('无信息，请刷新重试');
        }
        $catearray = $service['catearray'];
        unset($service['catearray']);
        $service['adv'] = serialize($service['adv']);
        //判断权限以及是否需要审核
        $getStatus = Housekeep::getStatus($type,$objid,$set,2);
        $service['status'] = $getStatus['status'];
        $objname = $getStatus['objname'];

        if($id > 0){ //编辑
            $res = pdo_update('wlmerchant_housekeep_service',$service,array('id' => $id));
        }else{
            $service['uniacid'] = $_W['uniacid'];
            $service['aid'] = $_W['aid'];
            $service['type'] = $type;
            $service['objid'] = $objid;
            $service['createtime'] = time();
            $res = pdo_insert(PDO_NAME.'housekeep_service',$service);
            $id = pdo_insertid();
        }
        if($res){
            //处理分类
            pdo_delete('wlmerchant_housekeep_relation', array('type' => 1,'objid' => $id));
            foreach ($catearray as $item) {
                $scate = pdo_get(PDO_NAME . 'housekeep_type', array('id' => $item), array('onelevelid'));
                pdo_insert('wlmerchant_housekeep_relation', ['type' => 1,'objid' => $id, 'onelevelid' => $scate['onelevelid'], 'twolevelid' => $item]);
            }
            if($service['status'] == 5){
                //发送模板消息
                $first   = '您好，一个家政服务项目待审核';
                $type    = '审核家政服务项目信息';
                $content = '发布方:'.$objname;
                $status  = '待审核';
                $remark  = "请管理员尽快前往后台审核";
                News::noticeAgent('housekeep',$_W['aid'],$first,$type,$content,$status,$remark,time(),'');
            }
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('操作失败，请刷新重试');
        }
    }

    /**
     * Comment: 服务项目详情
     * Author: wlf
     * Date: 2021/04/29 17:38
     */
    public function serviceDetail(){
        global $_GPC, $_W;
        $id = $_GPC['id'];
        if(empty($id)){
            $this->renderError('缺少重要参数id');
        }
        $set = Setting::agentsetting_read('housekeep');
        $service = pdo_get('wlmerchant_housekeep_service',array('id' => $id),array('type','objid','status','title','pricetype','price','salenum','detail','appointstatus','adv','videourl','unit'));
        if($service['status'] != 1){
            $this->renderError('项目已下架或被删除');
        }
        $service['adv'] = Housekeep::beautifyImgInfo($service['adv']);
        $service['catearray'] = Housekeep::getRelation($id,1);
        $service['videourl'] = tomedia($service['videourl']);
        $data['service'] = $service;
        //服务方
        if($service['type'] == 1){
            $merchant = pdo_get('wlmerchant_merchantdata',array('id' => $service['objid']),array('storename','mobile','logo','address','lat','lng','status','enabled'));
            if($merchant['status'] != 2 || $merchant['enabled'] != 1){
                $this->renderError('项目已下架或被删除');
            }
            $objinfo['name'] = $merchant['storename'];
            $objinfo['logo'] = tomedia($merchant['logo']);
            $objinfo['address'] = $merchant['address'];
            $objinfo['lat'] = $merchant['lat'];
            $objinfo['lng'] = $merchant['lng'];
            if(p('attestation')){
                $objinfo['attestation'] = Attestation::checkAttestation(2,$service['objid']);
            }
            $objinfo['catearray'] = Housekeep::getRelation($service['objid'],3);
            $objinfo['mobile'] = $merchant['mobile'];
            $objinfo['sid'] = $service['objid'];
        }else {
            $artificer = pdo_get('wlmerchant_housekeep_artificer',array('id' => $service['objid']),array('mid','mobile','status','mid','name','thumb','address','lat','lng'));
            if($artificer['status'] != 1){
                $this->renderError('项目已下架或被删除');
            }
            $objinfo['name'] = $artificer['name'];
            $objinfo['logo'] = tomedia($artificer['thumb']);
            $objinfo['address'] = $artificer['address'];
            $objinfo['lat'] = $artificer['lat'];
            $objinfo['lng'] = $artificer['lng'];
            if(p('attestation')) {
                $objinfo['attestation'] = Attestation::checkAttestation(1, $artificer['mid']);
            }
            $objinfo['catearray'] = Housekeep::getRelation($service['objid'],2);
            $objinfo['sid'] = $artificer['mid'];
            $objinfo['mobile'] = $artificer['mobile'];
        }
        $data['storeinfo'] = $objinfo;
        //评价
        $comment = pdo_fetchall("SELECT id,mid,pic,ispic,star,text,replyone,replytextone,replypicone,createtime FROM ".tablename('wlmerchant_comment')."WHERE uniacid = {$_W['uniacid']} AND plugin = 'housekeep' AND gid = {$id} AND checkone = 2 AND status = 1 ORDER BY id DESC LIMIT 5");
        $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."comment")."WHERE uniacid = {$_W['uniacid']} AND plugin = 'housekeep' AND gid = {$id} AND checkone = 2 AND status = 1");
        $data['commenttotal'] = ceil($total / 5);
        if(!empty($comment)){
            foreach($comment as &$com){
                $com = Housekeep::getCommentInfo($com);
            }
            $data['comment'] = $comment;
        }else{
            $data['comment'] = [];
        }
        $data['hidereply'] = 1;
        if($set['replystatus']){
            $jur = Housekeep::getJurisdiction(1,$_W['mid'],'reply');
            if(empty($jur)){
                $data['hidereply'] = 0;
            }
        }
        //默认地址
        $address = pdo_get('wlmerchant_address' , ['mid' => $_W['mid'] , 'uniacid' => $_W['uniacid'] , 'status'  => 1] , ['id' , 'status' , 'name' , 'tel' , 'province' , 'city' , 'county' , 'detailed_address']);
        if(!empty($address)){
            $data['expressid'] = $address['id'];
            $data['address'] = $address['county'].$address['detailed_address'];
        }

        $data['hidecontact'] = Housekeep::getVipRight($_W['mid'],'contact');

        $this->renderSuccess('服务项目信息',$data);
    }

    /**
     * Comment: 我的服务项目列表
     * Author: wlf
     * Date: 2021/05/06 11:34
     */
    public function getServicelist(){
        global $_GPC, $_W;
        //参数获取
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $mid = $_GPC['mid'] ? : $_W['mid'];
        //$page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * 10 - 10;
        $sort = 2;
        $type = $_GPC['type']; //发布方类型 1商户 2服务者
        $objid = $_GPC['storeid']; //商户id
        $search = $_GPC['search']; //查询信息
        $status = $_GPC['status']; //查询状态 1 销售中 2审核中 3已下架
        if($type == 2){
            $objid = pdo_getcolumn(PDO_NAME.'housekeep_artificer',array('uniacid'=>$_W['uniacid'],'mid'=>$mid),'id');
        }
        //查询条件
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']}";
        if($type > 0){
            $where .= " AND type = {$type} AND objid = {$objid}";
        }
        if(!empty($search)){
            $where .= " AND title LIKE '%{$search}%'";
        }
        if($status > 0){
            if($status == 1){
                $where .= " AND status = 1";
            }else if($status == 2){
                $where .= " AND status IN (5,6)";
            }else if($status == 3){
                $where .= " AND status = 0";
            }else{
                $this->renderError('条件错误，请刷新重试');
            }
        } else {
            $where .= " AND status != 4";
        }
        //排序
        switch ($sort) {
            case 1:$order = " ORDER BY sort DESC,id DESC ";break;//推荐
            case 2:$order = " ORDER BY createtime DESC ";break;//发布时间
            case 3:$order = " ORDER BY distance ASC ";break;//距离
            case 4:$order = " ORDER BY salenum DESC,id DESC ";break;//销量
        }
        //总数获取
        if($page == 1){
            $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."housekeep_service") .$where);
            $data['total'] = ceil($total / 10);
        }
        //新的处理看一下
        //$list = Housekeep::getList(4,$page,10,'','','',$where,$sort,$lng,$lat);
        //列表信息获取
        $field = "id,id as pid,type,objid,title,pricetype,price,salenum,createtime,thumb as logo,unit,status,reason";
        $list = pdo_fetchall("SELECT {$field} FROM ".tablename(PDO_NAME."housekeep_service") .$where.$order." LIMIT {$page_start},10");
        //循环进行信息的处理
        if(is_array($list) && count($list) > 0){
            foreach($list as $key => &$val){
                $val['logo'] = tomedia($val['logo']);
                $val['createtime'] = tomedia('Y-m-d H:i',$val['createtime']);
                //查询相关类目
                $val['catearray'] = Housekeep::getRelation($val['id'],1);
                //价格
                $val['price'] = Housekeep::getServicePrice($val['price'],$val['pricetype'],$val['unit']);
            }
        }
        $data['list'] = $list;
        $this->renderSuccess('服务项目列表',$data);
    }

    /**
     * Comment: 服务商户列表
     * Author: wlf
     * Date: 2021/05/06 16:40
     */
    public function getStorelist(){
        global $_GPC, $_W;
        //参数获取
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        //$page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * 10 - 10;
        $lng = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度
        $lat = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度
        $sort = $_GPC['sort'] ? : 1;//排序 1=推荐；2=创建时间；3=距离；4=销量
        $typeid = $_GPC['typeid']; //分类id
        $cityid = $_GPC['city_id']; //区域id
        //查询条件
        $where = " AND status = 2 AND enabled = 1 AND housekeepstatus = 1";
        if ($cityid > 0){
            $getAid = pdo_getcolumn(PDO_NAME . "oparea" , ['areaid'  => $cityid , 'status'  => 1 , 'uniacid' => $_W['uniacid']] , 'aid');
        }
        $aid = $getAid > 0 ? $getAid : $_W['aid'];
        if($typeid > 0){
            $onetype = pdo_getcolumn(PDO_NAME.'housekeep_type',array('id'=>$typeid),'onelevelid');
            if($onetype > 0){
                $sids = pdo_getall('wlmerchant_housekeep_relation',array('twolevelid' => $typeid,'type'=>3),array('objid'));
            }else{
                $sids = pdo_getall('wlmerchant_housekeep_relation',array('onelevelid' => $typeid,'type'=>3),array('objid'));
            }
            if(empty($sids)){
                $where .= 'id IN (0)';
            }else{
                $sidstext = "(";
                foreach ($sids as $key => $v) {
                    if ($key == 0) {
                        $sidstext .= $v['objid'];
                    } else {
                        $sidstext .= "," . $v['objid'];
                    }
                }
                $sidstext .= ")";
                $where .= 'id IN '.$sidstext;
            }
        }
        //排序
        switch ($sort) {
            case 1:$order = " ORDER BY listorder DESC,id DESC ";break;//推荐
            case 2:$order = " ORDER BY createtime DESC ";break;//创建时间
            case 3:$order = " ORDER BY distance ASC ";break;//距离
            case 4:$order = " ORDER BY salenum DESC,id DESC ";break;//销量
        }
        //总数获取
//        if($page == 1){
//            $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."merchantdata") .$where);
//            $data['total'] = ceil($total / 10);
//        }
        $list = Housekeep::getList(1,$page,10,$where,'','','',$sort,$lng,$lat,$aid);


        //列表信息获取
//        $field = "id,id as pid,
//        (SELECT
//            CASE
//                WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.137 * 2 * ASIN(
//                        SQRT(
//                            POW(SIN(({$lat} * PI() / 180 - lat * PI() / 180) / 2),2) +
//                                COS({$lat} * PI() / 180) * COS(lat * PI() / 180) *
//                                POW(SIN(({$lng} * PI() / 180 - lng * PI() / 180) / 2),2)
//                            )
//                        ) * 1000
//                    )
//                ELSE 0
//            END FROM ".tablename(PDO_NAME.'merchantdata')." as b WHERE b.id = pid) as distance,
//            IFNULL((SELECT sum(salenum) FROM ".tablename(PDO_NAME.'housekeep_service')." WHERE `type` = 1 AND objid = pid),0) as salenum,
//        storename,address,createtime,logo";
//        $list = pdo_fetchall("SELECT {$field} FROM ".tablename(PDO_NAME."merchantdata") .$where.$order." LIMIT {$page_start},10");
//        //循环进行信息的处理
//        if(is_array($list) && count($list) > 0){
//            foreach($list as $key => &$val){
//                $val['distance'] = Commons::distanceConversion($val['distance']);   //距离转换
//                $val['logo'] = tomedia($val['logo']);
//                $val['createtime'] = tomedia('Y-m-d H:i',$val['createtime']);
//                //查询相关类目
//                $val['catearray'] = Housekeep::getRelation($val['id'],3);
//                //认证
//                if(p('attestation')){
//                    $val['attestation'] = Attestation::checkAttestation(2,$val['id']);
//                }
//                //下级项目
//                $services = pdo_fetchall("SELECT id,title,pricetype,price,salenum,thumb FROM ".tablename(PDO_NAME."housekeep_service")."WHERE type = 1 AND objid = {$val['id']} AND status = 1 ORDER BY salenum DESC,id DESC LIMIT 2");
//                if(is_array($services) && count($services) > 0){
//                    foreach($services as &$ser){
//                        $ser['thumb'] = tomedia($ser['thumb']);
//                    }
//                    $val['services'] = $services;
//                }else{
//                    $val['services'] = [];
//                }
//            }
//        }
        $data = $list;
        $this->renderSuccess('服务商户列表',$data);
    }


    /**
     * Comment: 筛选页面商户列表接口
     * Author: wlf
     * Date: 2021/05/18 17:27
     */
    public function getNewStorelist(){
        global $_GPC, $_W;
        //参数获取
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        //$page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart = $page * 10 - 10;
        $lng = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度
        $lat = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度
        $sort = $_GPC['sort'] ? : 1;//排序 1=推荐；2=创建时间；3=距离；4=销量
        $typeid = $_GPC['typeid']; //分类id
        $cityid = $_GPC['city_id']; //区域id
        $search = trim($_GPC['search']);
        //查询条件
        if ($cityid > 0){
            $getAid = pdo_getcolumn(PDO_NAME . "oparea" , ['areaid'  => $cityid , 'status'  => 1 , 'uniacid' => $_W['uniacid']] , 'aid');
        }
        $aid = $getAid > 0 ? $getAid : $_W['aid'];
        $whereStore = "WHERE uniacid = {$_W['uniacid']} AND aid = {$aid} AND status = 2 AND enabled = 1 AND housekeepstatus = 1";
        $whereAfter = "WHERE uniacid = {$_W['uniacid']} AND aid = {$aid} AND status = 1";
        if(!empty($search)){
            $whereStore .= " AND storename LIKE '%{$search}%'";
            $whereAfter .= " AND name LIKE '%{$search}%'";
        }
        if($typeid > 0){
            $onetype = pdo_getcolumn(PDO_NAME.'housekeep_type',array('id'=>$typeid),'onelevelid');
            if($onetype > 0){
                $sids = pdo_getall('wlmerchant_housekeep_relation',array('twolevelid' => $typeid,'type'=>3),array('objid'));
                $afids = pdo_getall('wlmerchant_housekeep_relation',array('twolevelid' => $typeid,'type'=>2),array('objid'));
            }else{
                $sids = pdo_getall('wlmerchant_housekeep_relation',array('onelevelid' => $typeid,'type'=>3),array('objid'));
                $afids = pdo_getall('wlmerchant_housekeep_relation',array('onelevelid' => $typeid,'type'=>2),array('objid'));
            }
            if(empty($sids)){
                $whereStore .= ' AND id IN (0)';
            }else{
                $sidstext = "(";
                foreach ($sids as $key => $v) {
                    if ($key == 0) {
                        $sidstext .= $v['objid'];
                    } else {
                        $sidstext .= "," . $v['objid'];
                    }
                }
                $sidstext .= ")";
                $whereStore .= ' AND id IN '.$sidstext;
            }
            if(empty($afids)){
                $whereAfter .= ' AND id IN (0)';
            }else{
                $afidstext = "(";
                foreach ($afids as $key => $v) {
                    if ($key == 0) {
                        $afidstext .= $v['objid'];
                    } else {
                        $afidstext .= "," . $v['objid'];
                    }
                }
                $afidstext .= ")";
                $whereAfter .= ' AND id IN '.$afidstext;
            }
        }
        //排序
        switch ($sort){
            case 1: $orderBy = " ORDER BY sort DESC,id DESC ";break;
            case 2: $orderBy = " ORDER BY createtime DESC ";break;
            case 3: $orderBy = " ORDER BY distances ASC,createtime DESC ";break;
            case 4: $orderBy = " ORDER BY salenum DESC ";break;
        }
        //查询
        $limit = " LIMIT {$pageStart},10";
        $distances = getDistancesSql($lat,$lng,'lat','lng');
        $Ssalenum = "SELECT sum(salenum) FROM ".tablename(PDO_NAME."housekeep_service")
            ." WHERE uniacid = {$_W['uniacid']} AND aid = {$aid} AND `type` = 1 and objid = shop_id ";
        $Asalenum = "SELECT sum(salenum) FROM ".tablename(PDO_NAME."housekeep_service")
            ." WHERE uniacid = {$_W['uniacid']} AND aid = {$aid} AND `type` = 2 and objid = id ";

        $field = "*";
        $sql = "SELECT {$field} FROM (SELECT id,id as shop_id,listorder as sort,createtime,{$distances} as distances,'1' as service_type,IFNULL(({$Ssalenum}),0) as salenum FROM ".tablename(PDO_NAME."merchantdata")
            ." {$whereStore} "
            ." UNION ALL SELECT id,id as listorder,sort,createtime,{$distances} as distances,'2' as service_type,IFNULL(({$Asalenum}),0) as salenum FROM ".tablename(PDO_NAME."housekeep_artificer")
            ." {$whereAfter}) as total";
        //运行sql语句获取信息列表
        $list = pdo_fetchall($sql.$orderBy.$limit);
        $list = array_map(function ($item){
            //信息处理
            $itemRes = Housekeep::getDesc($item['id'],$item['service_type']);
            //距离处理
            if($item['distances'] > 0){
                if($item['distances'] < 1000) $itemRes['distances'] = $item['distances'].'m';
                else $itemRes['distances'] = sprintf("%.2f",$item['distances'] / 1000).'km';
            }
            return $itemRes;
        },$list);
        //获取总数信息  根据分页信息获取
        $totalSql = "SELECT count(*) FROM (SELECT id,id as shop_id,listorder as sort,createtime,'1' as service_type FROM ".tablename(PDO_NAME."merchantdata")
            ." {$whereStore} "
            ." UNION ALL SELECT id,id as listorder,sort,createtime,'2' as service_type FROM ".tablename(PDO_NAME."housekeep_artificer")
            ." {$whereAfter}) as total";
        $total = pdo_fetchcolumn($totalSql);
        //信息拼装
        $data = [
            'list'        => $list,
            'page'        => $page,
            'page_number' => ceil($total / 10)
        ];

        $this->renderSuccess('新版获取商户列表',$data);

    }



    /**
     * Comment: 个人服务者列表
     * Author: wlf
     * Date: 2021/05/06 18:10
     */
    public function getArtificerlist(){
        global $_GPC, $_W;
        //参数获取
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        //$page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * 10 - 10;
        $lng = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度
        $lat = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度
        $sort = $_GPC['sort'] ? : 1;//排序 1=推荐；2=创建时间；3=距离；4=销量
        //查询条件
        $where = " AND status = 1";
        //排序
        switch ($sort) {
            case 1:$order = " ORDER BY sort DESC,id DESC ";break;//推荐
            case 2:$order = " ORDER BY createtime DESC ";break;//创建时间
            case 3:$order = " ORDER BY distance ASC ";break;//距离
            case 4:$order = " ORDER BY salenum DESC,id DESC ";break;//销量
        }
        //总数获取
//        if($page == 1){
//            $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."housekeep_artificer") .$where);
//            $data['total'] = ceil($total / 10);
//        }
        $list = Housekeep::getList(2,$page,10,'',$where,'','',$sort,$lng,$lat);

        //列表信息获取
//        $field = "id,id as pid,
//        (SELECT
//            CASE
//                WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.137 * 2 * ASIN(
//                        SQRT(
//                            POW(SIN(({$lat} * PI() / 180 - lat * PI() / 180) / 2),2) +
//                                COS({$lat} * PI() / 180) * COS(lat * PI() / 180) *
//                                POW(SIN(({$lng} * PI() / 180 - lng * PI() / 180) / 2),2)
//                            )
//                        ) * 1000
//                    )
//                ELSE 0
//            END FROM ".tablename(PDO_NAME.'housekeep_artificer')." as b WHERE b.id = pid) as distance,
//            IFNULL((SELECT sum(salenum) FROM ".tablename(PDO_NAME.'housekeep_service')." WHERE `type` = 2 AND objid = pid),0) as salenum,
//        name,address,mid,createtime,thumb as logo";
//        $list = pdo_fetchall("SELECT {$field} FROM ".tablename(PDO_NAME."housekeep_artificer") .$where.$order." LIMIT {$page_start},10");
//        //循环进行信息的处理
//        if(is_array($list) && count($list) > 0){
//            foreach($list as $key => &$val){
//                $val['distance'] = Commons::distanceConversion($val['distance']);   //距离转换
//                $val['logo'] = tomedia($val['logo']);
//                $val['createtime'] = tomedia('Y-m-d H:i',$val['createtime']);
//                //查询相关类目
//                $val['catearray'] = Housekeep::getRelation($val['id'],3);
//                //认证
//                if(p('attestation')){
//                    $val['attestation'] = Attestation::checkAttestation(1,$val['mid']);
//                }
//                //下级项目
//                $services = pdo_fetchall("SELECT id,title,pricetype,price,salenum,thumb FROM ".tablename(PDO_NAME."housekeep_service")."WHERE type = 2 AND objid = {$val['id']} AND status = 1 ORDER BY salenum DESC,id DESC LIMIT 2");
//                if(is_array($services) && count($services) > 0){
//                    foreach($services as &$ser){
//                        $ser['thumb'] = tomedia($ser['thumb']);
//                    }
//                    $val['services'] = $services;
//                }else{
//                    $val['services'] = [];
//                }
//            }
//        }
        $data = $list;
        $this->renderSuccess('个人服务者列表',$data);
    }

    /**
     * Comment: 客户需求列表
     * Author: wlf
     * Date: 2021/05/06 18:23
     */
    public function getDemandlist(){
        global $_GPC, $_W;
        //参数获取
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $typeid = $_GPC['typeid'];
        //$page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * 10 - 10;
        $lng = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度
        $lat = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度
        $sort = $_GPC['sort'] ? : 1;//排序 1=推荐；2=发布时间；3=上门时间；4=距离
        $cityId = $_GPC['city_id'] ? : 0;//区域id
        $search = trim($_GPC['search']);
        //查询条件
        $where = " AND status = 1";
        if($typeid > 0){
            $onetype = pdo_getcolumn(PDO_NAME.'housekeep_type',array('id'=>$typeid),'onelevelid');
            if($onetype > 0){
                $where .= " AND type = {$typeid}";
            }else{
                $where .= " AND onetype = {$typeid}";
            }
        }
        if ($cityId > 0){
            $getAid = pdo_getcolumn(PDO_NAME . "oparea" , ['areaid'  => $cityId , 'status'  => 1 , 'uniacid' => $_W['uniacid']] , 'aid');
        }
        if(!empty($search)){
            $typeids = pdo_fetchall("SELECT id FROM ".tablename('wlmerchant_housekeep_type')."WHERE uniacid = {$_W['uniacid']} AND title LIKE '%{$search}%'");
            if(empty($typeids)){
                $where .= ' AND type IN (0)';
            }else{
                $sidstext = "(";
                foreach ($typeids as $key => $v) {
                    if ($key == 0) {
                        $sidstext .= $v['id'];
                    } else {
                        $sidstext .= "," . $v['id'];
                    }
                }
                $sidstext .= ")";
                $where .= ' AND type IN '.$sidstext;
            }
        }
        $aid = $getAid > 0 ? $getAid : $_W['aid'];

        //排序
        switch ($sort) {
            case 1:$order = " ORDER BY topflag DESC,updatetime DESC ";break;//推荐
            case 2:$order = " ORDER BY topflag DESC,createtime DESC ";break;//创建时间
            case 3:$order = " ORDER BY topflag DESC,visitingtime DESC ";break;//上门时间
            case 4:$order = " ORDER BY topflag DESC,distance ASC ";break;//距离
        }
        $list = Housekeep::getList(3,$page,10,'','',$where,'',$sort,$lng,$lat,$aid);
        //总数获取
//        if($page == 1){
//            $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."housekeep_demand") .$where);
//            $data['total'] = ceil($total / 10);
//        }
        //列表信息获取
//        $field = "id,id as pid,topflag,
//        (SELECT
//            CASE
//                WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.137 * 2 * ASIN(
//                        SQRT(
//                            POW(SIN(({$lat} * PI() / 180 - lat * PI() / 180) / 2),2) +
//                                COS({$lat} * PI() / 180) * COS(lat * PI() / 180) *
//                                POW(SIN(({$lng} * PI() / 180 - lng * PI() / 180) / 2),2)
//                            )
//                        ) * 1000
//                    )
//                ELSE 0
//            END FROM ".tablename(PDO_NAME.'housekeep_demand')." as b WHERE b.id = pid) as distance,
//        address,mid,visitingtime,type,thumbs";
//        $list = pdo_fetchall("SELECT {$field} FROM ".tablename(PDO_NAME."housekeep_demand") .$where.$order." LIMIT {$page_start},10");
//        //循环信息处理
//        if(is_array($list) && count($list) > 0){
//            foreach($list as $key => &$val){
//                $val['distance'] = Commons::distanceConversion($val['distance']);   //距离转换
//                $thumbs = Housekeep::beautifyImgInfo($val['thumbs']);
//                $val['logo'] = $thumbs[0];
//                $val['visitingtime'] = tomedia('Y-m-d H:i',$val['visitingtime']);
//                //查询相关类目
//                $val['name'] = pdo_getcolumn(PDO_NAME.'housekeep_type',array('id'=>$val['type']),'title');
//            }
//        }
        $data = $list;
        $this->renderSuccess('客户需求列表',$data);
    }

    /**
     * Comment: 客户下单接口
     * Author: wlf
     * Date: 2021/05/07 10:06
     */
    public function creatOrderApi(){
        global $_GPC, $_W;
        //参数获取
        $id = $_GPC['id'];  //项目id
        $num = $_GPC['num'] ? : 1; //份数
        $expressid = $_GPC['expressid'] ? : 0; //上门地址
        if(empty($id)){
            $this->renderError('参数错误，请重试');
        }
        $service = pdo_get('wlmerchant_housekeep_service',array('id' => $id),array('title','type','objid','aid','pricetype','price','status'));
        if($service['status'] != 1){
            $this->renderError('服务已下架,无法下单');
        }
        if($service['pricetype'] > 0){
            $price = sprintf("%.2f",$service['price'] * $num);
            if($price < 0.01){
                $this->renderError('服务金额错误，请刷新重试');
            }else{
                //计算结算金额
                $settlementmoney = Housekeep::getSettlementMoney($price,$service['pricetype'],$service['type'],$service['objid']);
            }
        }else{
            $price = 0;
        }
        $orderData = array(
            'uniacid'    => $_W['uniacid'],
            'mid'        => $_W['mid'],
            'aid'        => $service['aid'],
            'sid'        => $service['objid'],
            'fkid'       => $id,
            'createtime' => time(),
            'orderno'    => createUniontid(),
            'price'      => $price,
            'plugin'     => 'housekeep',
            'payfor'     => 'housekeeporder',
            'fightstatus'=> 1,
            'num'        => $num,
            'specid'     => $service['type'],
            'settlementmoney' => $settlementmoney,
            'expressid'  => $expressid
        );
        pdo_insert(PDO_NAME.'order', $orderData);
        $orderid = pdo_insertid();
        if(empty($orderid)){
            $this->renderError('创建订单失败，请刷新重试');
        }
        if($price > 0){ //去支付
            $data['status'] = 0;
            $data['orderid'] = $orderid;
            $this->renderSuccess('跳转到支付页面',$data);
        }else{  //0元购
            $newdata = [
                'status'   => 1,
                'paytime'  => time() ,
                'paytype'  => 6
            ];
            pdo_update(PDO_NAME . 'order' , $newdata , ['id' => $orderid]); //更新订单状态
            //修改服务项目销量
            pdo_fetch("update" . tablename('wlmerchant_housekeep_service') . "SET salenum=salenum+{$num} WHERE id = {$id}");
            //向服务者发送消息
            $first   = "用户【{$_W['wlmember']['nickname']}】下单了[{$service['title']}]的家政服务";//消息头部
            $type    = "家政服务";//业务类型
            $content = $service['title'];//业务内容
            $status  = "待处理";//处理结果
            $remark  = "请尽快联系客户处理!";//备注信息
            $time    = $orderData['createtime'];//操作时间
            if($service['type'] == 1){
                News::noticeShopAdmin($service['objid'], $first , $type , $content , $status , $remark , $time);
            }else{
                $mid = pdo_getcolumn(PDO_NAME.'housekeep_artificer',array('id'=>$service['objid']),'mid');
                News::jobNotice($mid,$first,$type,$content,$status,$remark,$time);
            }
            $data['status'] = 1;
            $data['orderid'] = $orderid;
            $this->renderSuccess('跳转到订单列表',$data);
        }
    }

    /**
     * Comment: 用户订单列表接口
     * Author: wlf
     * Date: 2021/05/07 14:28
     */
    public function memberOrderList(){
        global $_GPC, $_W;
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_start = $page * 10 - 10;
        $status = $_GPC['status']; //0 = 全部  1待支付 2待服务 3已完成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.mid = {$_W['mid']} AND a.fightstatus = 1 AND a.plugin = 'housekeep' ";
        if($status > 0){
            switch ($status) {
                case 1:$where .= " AND a.status = 0";break;//待支付
                case 2:$where .= " AND a.status = 1";break;//待服务
                case 3:$where .= " AND a.status IN (2,3)";break;//已完成
            }
        }
        //总数获取
        if($page == 1){
            $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."order")." a LEFT JOIN".tablename('wlmerchant_housekeep_service')." b ON a.fkid = b.id" .$where);
            $data['total'] = ceil($total / 10);
        }
        $list = pdo_fetchall("SELECT a.id,a.sid,a.mid,a.price,a.fkid,a.status,b.type,b.objid,b.title,b.pricetype,b.thumb FROM ".tablename(PDO_NAME."order")." a LEFT JOIN".tablename('wlmerchant_housekeep_service')." b ON a.fkid = b.id" .$where." ORDER BY a.id DESC LIMIT {$page_start},10");
        if(is_array($list) && count($list) > 0){
            foreach($list as &$st){
                $st['thumb'] = tomedia($st['thumb']);
                //是否申请退款
                $canre = pdo_getcolumn(PDO_NAME . 'aftersale' , [
                    'orderid' => $st['id'] ,
                    'plugin'  => 'housekeep' ,
                    'status'  => 1
                ] , 'id');
                if($canre > 0){
                    $st['status'] = 10;
                }
                if($st['type'] == 1){
                    $artificer = pdo_get('wlmerchant_merchantdata',array('id' => $st['objid']),array('mobile','storename','logo'));
                    $st['artificerName'] = $artificer['storename'];
                    $st['artificerLogo'] = tomedia($artificer['logo']);
                    $st['mobile'] = $artificer['mobile'];
                }else{
                    $artificer = pdo_get('wlmerchant_housekeep_artificer',array('id' => $st['objid']),array('mobile','name','thumb','mid'));
                    $st['artificerName'] = $artificer['name'];
                    $st['artificerLogo'] = tomedia($artificer['thumb']);
                    $st['sid'] = $artificer['mid'];
                    $st['mobile'] = $artificer['mobile'];
                }
                $st['express']['address'] = '';
            }
        }else{
            $list = [];
        }
        $data['list'] = $list;
        $this->renderSuccess('客户订单列表',$data);
    }

    /**
     * Comment: 服务者业务订单列表接口
     * Author: wlf
     * Date: 2021/05/07 15:28
     */
    public function artificerOrderList(){
        global $_GPC, $_W;
        $set = Setting::agentsetting_read('housekeep');
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_start = $page * 10 - 10;
        $artificerid = pdo_getcolumn(PDO_NAME.'housekeep_artificer',array('uniacid'=>$_W['uniacid'],'aid' => $_W['aid'],'mid'=>$_W['mid']),'id');
        $status = $_GPC['status']; //0 = 全部  1待服务 2待评价 3已完成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.sid = {$artificerid} AND a.specid = 2 AND a.fightstatus = 1 AND a.plugin = 'housekeep'";
        if($status > 0){
            $where .= " AND a.status = {$status}";
        }else{
            $where .= " AND a.status > 0";
        }
        if($page == 1){
            $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."order")." a LEFT JOIN".tablename('wlmerchant_housekeep_service')." b ON a.fkid = b.id" .$where);
            $data['total'] = ceil($total / 10);
        }
        $list = pdo_fetchall("SELECT a.id,a.sid,a.mid,a.price,a.fkid,a.status,b.title,b.pricetype,b.thumb,a.expressid FROM ".tablename(PDO_NAME."order")." a LEFT JOIN".tablename('wlmerchant_housekeep_service')." b ON a.fkid = b.id" .$where." ORDER BY a.id DESC LIMIT {$page_start},10");
        if(is_array($list) && count($list) > 0){
            foreach($list as &$st){
                //是否申请退款
                $canre = pdo_getcolumn(PDO_NAME . 'aftersale' , [
                    'orderid' => $st['id'] ,
                    'plugin'  => 'housekeep' ,
                    'status'  => 1
                ] , 'id');
                if($canre > 0){
                    $st['status'] = 10;
                }
                $st['thumb'] = tomedia($st['thumb']);
                $member = pdo_get('wlmerchant_member',array('id' => $st['mid']),array('nickname','avatar','mobile'));
                $st['nickname'] = $member['nickname'];
                $st['avatar'] = tomedia($member['avatar']);
                $st['mobile'] = $member['mobile'];
                if($st['expressid'] > 0){
                    $st['express'] = pdo_get('wlmerchant_address',array('id' => $st['expressid']),['name','tel','province','city','county','detailed_address']);
                    $st['express']['address'] = $st['express']['county'].$st['express']['detailed_address'];
                }
            }
        }else{
            $list = [];
        }
        $data['list'] = $list;
        $data['hidefish'] = $set['storefish'] ? : 0;
        $this->renderSuccess('业务订单列表',$data);
    }

    /**
     * Comment: 订单完成接口
     * Author: wlf
     * Date: 2021/05/07 16:21
     */
    public function finishOrder(){
        global $_GPC, $_W;
        $id = $_GPC['id'];  //订单id
        $channel = $_GPC['channel']; //完成渠道 1买家 2服务者
        if(empty($id) || empty($channel)){
            $this->renderError('参数错误，请刷新充实');
        }
        $res = pdo_update('wlmerchant_order',array('status' => 2,'retype' => $channel),array('id' => $id,'status' => 1));
        if($res){
            Housekeep::settlementOrder($id); //结算
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('操作失败，订单已完成或取消');
        }
    }

    /**
     * Comment: 游客留言接口
     * Author: wlf
     * Date: 2021/05/08 16:08
     */
    public function leaveMessage(){
        global $_GPC, $_W;
        $cid = $_GPC['cid'];
        $content = trim($_GPC['content']);
        if(empty($cid)){
            $this->renderError('参数错误，请刷新重试');
        }
        if(empty($content)){
            $this->renderError('请输入评论内容');
        }
        //判断文本内容是否非法
        $textRes = Filter::init($content , $_W['source'] , 1);
        if ($textRes['errno'] == 0) {
            $this->renderError($textRes['message']);
        }
        $amid = pdo_getcolumn(PDO_NAME.'comment',array('id'=>$cid),'mid');
        $data = [
            'uniacid' => $_W['uniacid'],
            'aid'     => $_W['aid'],
            'cid'     => $cid,
            'smid'    => $_W['mid'],
            'amid'    => $amid,
            'content' => $content,
            'createtime' => time()
        ];
        $res = pdo_insert(PDO_NAME . 'housekeep_reply', $data);
        if($res){
            $this->renderSuccess('评论成功');
        }else{
            $this->renderError('评论失败，请刷新重试');
        }
    }

    /**
     * Comment: 游客点赞接口
     * Author: wlf
     * Date: 2021/05/08 18:44
     */
    public function clickPraise(){
        global $_GPC, $_W;
        $cid = $_GPC['cid'];
        $praiseid = pdo_getcolumn(PDO_NAME.'housekeep_praise',array('cid'=>$cid,'mid'=>$_W['mid']),'id');
        if($praiseid){
            $res = pdo_delete(PDO_NAME.'housekeep_praise',array('id'=>$praiseid));
            if($res){
                $this->renderSuccess('取消点赞成功');
            }else{
                $this->renderError('取消点赞失败，请刷新重试');
            }
        }else{
            $res = pdo_insert(PDO_NAME . 'housekeep_praise', ['cid' => $cid,'mid' => $_W['mid']]);
            if($res){
                $this->renderSuccess('点赞成功');
            }else{
                $this->renderError('点赞失败，请刷新重试');
            }
        }
    }

    /**
     * Comment: 查看更多评论
     * Author: wlf
     * Date: 2021/05/08 18:53
     */
    public function showMoreComment(){
        global $_GPC, $_W;
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $id = $_GPC['id'];
        $sid = $_GPC['sid'];
        $page_start = $page * 5 - 5;
        if($id > 0){
            $comment = pdo_fetchall("SELECT id,mid,pic,ispic,star,text,replyone,replytextone,replypicone,createtime FROM ".tablename('wlmerchant_comment')."WHERE uniacid = {$_W['uniacid']} AND plugin = 'housekeep' AND gid = {$id} AND checkone = 2 AND status = 1 ORDER BY id DESC LIMIT {$page_start},5");
        }
        if($sid > 0){
            $comment = pdo_fetchall("SELECT id,mid,pic,ispic,star,text,replyone,replytextone,replypicone,createtime FROM ".tablename('wlmerchant_comment')."WHERE uniacid = {$_W['uniacid']} AND plugin = 'housekeep' AND sid = {$sid} AND checkone = 2 AND status = 1 AND housekeepflag = 1 ORDER BY id DESC LIMIT {$page_start},5");
        }
        foreach($comment as &$com){
            $com = Housekeep::getCommentInfo($com);
        }
        $this->renderSuccess('更多评论',$comment);
    }

    /**
     * Comment: 显示所有留言
     * Author: wlf
     * Date: 2021/05/08 19:10
     */
    public function showAllMessage(){
        global $_GPC, $_W;
        $cid = $_GPC['cid'];
        $list = pdo_fetchall("SELECT a.id,a.content,b.nickname FROM ".tablename(PDO_NAME."housekeep_reply")." a LEFT JOIN".tablename('wlmerchant_member')." b ON a.smid = b.id WHERE cid = {$cid}");
        $list = array_splice($list, 4);
        $this->renderSuccess('所有留言',$list);
    }

    /**
     * Comment: 删除留言接口
     * Author: wlf
     * Date: 2021/05/08 19:28
     */
    public function deleteMessage(){
        global $_GPC, $_W;
        $id = $_GPC['id'];
        $res = pdo_delete(PDO_NAME.'housekeep_reply',array('id'=>$id));
        if($res){
            $this->renderSuccess('删除成功');
        }else{
            $this->renderError('删除失败，请刷新重试');
        }
    }

    /**
     * Comment: 获取搜索项接口
     * Author: wlf
     * Date: 2021/05/18 14:51
     */
    public function housekeepSelectInfo(){
        global $_GPC, $_W;
        $type = $_GPC['type']; //1=需求；2=服务
        $classList = pdo_fetchall("SELECT id as cate_one,title as `name` FROM " . tablename(PDO_NAME . "housekeep_type") . " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND status = 1 AND onelevelid = 0  ORDER BY sort DESC,id DESC");
        if (is_array($classList) && count($classList) > 0) {
            foreach ($classList as $cardKey => &$cardVal) {
                $cardVal['list'] = pdo_fetchall("SELECT id as cate_two,title as `name` FROM " . tablename(PDO_NAME . "housekeep_type") . " WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND status = 1 AND onelevelid = {$cardVal['cate_one']} ORDER BY sort DESC,id DESC");
            }
        }
        $classList = array_merge([
            [
                'cate_one' => '0' ,
                'name' => '全部' ,
                'list' => []
            ]
        ] , $classList);

        $data = [
            'top'    => [
                ['title' => '区域' , 'subscript' => 'area' , 'status' => 1] ,
                ['title' => '分类' , 'subscript' => 'class' , 'status' => 1] ,
                ['title' => '排序' , 'subscript' => 'orders' , 'status' => 1] ,
            ] ,
            'area'   => 'do=WholeCityList' ,
            'class'  => $classList ,
        ];
        if($type == 1){
            $data['orders'] = [
                ['title' => '推荐' , 'val' => 1] ,
                ['title' => '附近' , 'val' => 4] ,
                ['title' => '发布时间' , 'val' => 2] ,
                ['title' => '上门时间' , 'val' => 3] ,
            ];
        }else if($type == 2){
            $data['orders'] = [
                ['title' => '推荐' , 'val' => 1] ,
                ['title' => '最新' , 'val' => 4] ,
                ['title' => '距离' , 'val' => 2] ,
                ['title' => '销量' , 'val' => 3] ,
            ];
        }
        $this->renderSuccess('选择项',$data);
    }

    /**
     * Comment: 服务项目操作接口
     * Author: wlf
     * Date: 2021/05/19 15:58
     */
    public function serviceUpdate(){
        global $_GPC, $_W;
        $id = $_GPC['id'];  //服务项目id
        $doStatus = $_GPC['doStatus']; //操作类型  1=上架 2=下架 3=删除
        if(empty($id) || empty($doStatus)){
            $this->renderError('缺少重要参数，请刷新充实');
        }
        $service = pdo_get('wlmerchant_housekeep_service',array('id' => $id),array('type','objid'));
        if($doStatus == 1){
            $set = Setting::agentsetting_read('housekeep');
            $getStatus = Housekeep::getStatus($service['type'],$service['objid'],$set,2);
            $status = $getStatus['status'];
            $objname = $getStatus['objname'];
        }else if($doStatus == 2){
            $status = 0;
        }else if($doStatus == 3){
            $status = 4;
        }
        $res = pdo_update('wlmerchant_housekeep_service',array('status' => $status),array('id' => $id));
        if($res){
            if($status == 5){
                if($service['status'] == 5){
                    //发送模板消息
                    $first   = '您好，一个家政服务项目待审核';
                    $type    = '审核家政服务项目信息';
                    $content = '发布方:'.$objname;
                    $status  = '待审核';
                    $remark  = "请管理员尽快前往后台审核";
                    News::noticeAgent('housekeep',$_W['aid'],$first,$type,$content,$status,$remark,time(),'');
                }
            }
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('操作失败，请刷新充实');
        }
    }

    /**
     * Comment: 家政个人主页
     * Author: wlf
     * Date: 2021/05/26 10:12
     */
    public function personalHomepage(){
        global $_GPC, $_W;
        $id = $_GPC['id'];
        if(empty($id)){
            $this->renderError('缺少关键参数,请返回重试');
        }
        $artificer = pdo_get('wlmerchant_housekeep_artificer',array('id' => $id),array('name','gender','mobile','address','thumbs','casethumbs','thumb','lat','lng'));
        $artificer['thumbs'] = Housekeep::beautifyImgInfo($artificer['thumbs']);
        $artificer['casethumbs'] = Housekeep::beautifyImgInfo($artificer['casethumbs']);
        $artificer['thumb'] = tomedia($artificer['thumb']);
        $artificer['catearray'] = Housekeep::getRelation($id,2);
        //评论
        $comment = pdo_fetchall("SELECT id,mid,pic,ispic,star,text,replyone,replytextone,replypicone,createtime FROM ".tablename('wlmerchant_comment')."WHERE uniacid = {$_W['uniacid']} AND plugin = 'housekeep' AND sid = {$id} AND checkone = 2 AND status = 1 AND housekeepflag = 1 ORDER BY id DESC LIMIT 5");
        $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."comment")."WHERE uniacid = {$_W['uniacid']} AND plugin = 'housekeep' AND sid = {$id} AND checkone = 2 AND status = 1 AND housekeepflag = 1");
        $artificer['commenttotal'] = ceil($total / 5);
        if(!empty($comment)){
            foreach($comment as &$com){
                $com = Housekeep::getCommentInfo($com);
            }
            $artificer['comment'] = $comment;
        }else{
            $artificer['comment'] = [];
        }
        $data = $artificer;
        $data['hidecontact'] = Housekeep::getVipRight($_W['mid'],'contact');

        $this->renderSuccess('服务者主页信息',$data);
    }




}