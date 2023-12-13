<?php
defined('IN_IA') or exit('Access Denied');

class CitycardModuleUniapp extends Uniapp {
    /**
     * Comment: 名片列表信息
     * Author: zzw
     * Date: 2019/12/17 13:46
     */
    public function homeList(){
        global $_W,$_GPC;
        #1、参数获取
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $sort      = $_GPC['sort'] ? : 1;
        $cateOne   = $_GPC['cate_one'] ? : 0;//一级分类
        $cateTwo   = $_GPC['cate_two'] ? : 0;//二级分类
        $cityId    = $_GPC['city_id'] ? : 0;//区域id
        $lng       = $_GPC['lng'] && $_GPC['lng'] != 'undefined' ? $_GPC['lng'] : 0;//用户当前所在经度 104.0091133118 经度
        $lat       = $_GPC['lat'] && $_GPC['lat'] != 'undefined' ? $_GPC['lat'] : 0;//用户当前所在纬度 30.5681964123  纬度
        $isCollect = $_GPC['is_collect'] ? : 0;//是否获取收藏的名片 0=获取所有人，1=只获取当前用户收藏的名片
        $search    = $_GPC['search'] ? : '';
        $time = time();
        #2、查询条件生成  基本条件：启用，审核通过，已支付
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 1 AND checkstatus = 1 AND paystatus = 1 AND meal_endtime > {$time} ";
        if($cateOne > 0) $where .= " AND one_class = {$cateOne} ";
        if($cateTwo > 0) $where .= " AND two_class = {$cateTwo} ";
        if($cityId > 0 && empty($isCollect)){
            $displayorder = pdo_getcolumn(PDO_NAME.'area',array('id'=>$cityId),'displayorder');
            if(empty($displayorder)){
                $where .= " AND ( pro_code = {$cityId} OR city_code = {$cityId} OR area_code = {$cityId} ) ";
            }
        }
        if($search) $where .= " AND ( name LIKE '%{$search}%' OR company LIKE '%{$search}%' OR `desc` LIKE '%{$search}%' )";
        //判断是否获取当前用户收藏的名片
        if($isCollect == 1){
            //仅获取当前用户的名片列表  判断是否登录
            if(intval($_W['mid']) <= 0){
                $this->reLogin();
            }else {
                $collectList = pdo_getall(PDO_NAME."citycard_collect",['mid'=>$_W['mid']],['cardid']);
                if(is_array($collectList) && count($collectList) > 1){
                    $collectIds = array_column($collectList,'cardid');
                    $idStr = trim(implode($collectIds,','),',');
                    $where .= " AND id IN ({$idStr}) ";
                }else if(is_array($collectList) && count($collectList) == 1){
                    $where .= " AND id = {$collectList[0]['cardid']} ";
                }else{
                    //没有内容 则代表当前用户没有收藏名片 不查询任何信息
                    $where .= " AND id = -1 ";
                }
            }
        }
        #3、排序条件生成
        $order = " ORDER BY top_is DESC";
        switch ($sort){
            case 1:$order .= ",createtime DESC ";break;//最新
            case 2:$order .= ",show_addr DESC,distance ASC ";break;//附近
            case 3:$order .= ",laud DESC ";break;//点赞榜
            case 4:$order .= ",pv DESC ";break;//人气榜
            case 5:$order .= ",total_collect DESC ";break;//收存榜
        }
        #4、名片总数获取
        $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."citycard_lists") .$where);
        $data['total'] = ceil($total / $pageIndex);
        #5、名片列表信息获取
        $field = "id,id as pid,mid,top_is,
        (SELECT 
            CASE 
                WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.137 * 2 * ASIN(
                        SQRT(
                            POW(SIN(({$lat} * PI() / 180 - lat * PI() / 180) / 2),2) +
                                COS({$lat} * PI() / 180) * COS(lat * PI() / 180) *
                                POW(SIN(({$lng} * PI() / 180 - lng * PI() / 180) / 2),2)
                            )
                        ) * 1000
                    ) 
                ELSE 0
            END FROM ".tablename(PDO_NAME.'citycard_lists')." as b WHERE b.id = pid) as distance,
            (SELECT COUNT(*) FROM ".tablename(PDO_NAME.'citycard_collect')." WHERE cardid = pid) as total_collect,
        name,logo,mobile,wechat,company,branch,position,`desc`,show_addr,show_mobile,show_wechat,laud,pv,share,lat,lng,one_class,two_class";
        $data['list'] = pdo_fetchall("SELECT {$field} FROM ".tablename(PDO_NAME."citycard_lists") .$where.$order." LIMIT {$pageStart},{$pageIndex}");
        #6、循环进行信息的处理
        if(is_array($data['list']) && count($data['list']) > 0){
            foreach($data['list'] as $key => &$val){
                $member = pdo_get(PDO_NAME."member",['id'=>$val['mid']],['nickname','avatar']);
                //基本信息处理
                $val['distance'] = Commons::distanceConversion($val['distance']);   //距离转换
                $val['logo'] = !empty($val['logo']) ? tomedia($val['logo']) : tomedia($member['avatar']) ;
                $val['name'] = !empty($val['name']) ? $val['name'] : $member['nickname'] ;
                $val['one_class_title'] = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$val['one_class']),'name');
                $val['two_class_title'] = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$val['two_class']),'name');
            }
        }
        #6、判断当前用户是否存在有效的名片   0=不存在，1=存在
        $myCard = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename(PDO_NAME."citycard_lists")
                                  ." WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} AND meal_endtime > {$time} AND status = 1");
        $data['is_have'] = intval($myCard) > 0 ? 1 : 0 ;

        $this->renderSuccess('名片首页信息',$data);
    }
    /**
     * Comment: 获取名片的详细信息
     * Author: zzw
     * Date: 2019/12/17 14:14
     */
    public function cardInfo(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'] OR $this->renderError('参数错误，id不存在');
        #2、浏览量添加
        $set = Setting::agentsetting_read('citycard');
        if($set['minup'] > 0 || $set['maxup'] > 0){
            $randNumber = [$set['minup'],$set['maxup']];
            $min = min($randNumber);
            $max = max($randNumber);
            $addPv = rand($min,$max);
        }else{
            $addPv = 1;
        }
        pdo_fetch("UPDATE ".tablename(PDO_NAME.'citycard_lists')." SET `pv` = (`pv` + {$addPv}) WHERE `id` = {$id} ");
        #3、获取当前名片想信息
        $field = "id,id as pid,
        (SELECT COUNT(*) FROM ".tablename(PDO_NAME.'citycard_collect')." WHERE cardid = pid) as total_collect,
        mid,name,logo,mobile,wechat,company,branch,position,address,show_addr,show_mobile,pro_code,city_code,area_code,show_wechat,laud,pv,share,`desc`,laud_user,one_class,two_class";
        $info = pdo_fetch("SELECT {$field} FROM ".tablename(PDO_NAME."citycard_lists") ." WHERE id = {$id} ");
        $member = pdo_get(PDO_NAME."member",['id'=>$info['mid']],['nickname','avatar']);
        #4、信息处理
        if($info['show_mobile'] == 0 && strlen($info['mobile']) > 1){
            //手机号隐藏处理
            $replaceStr = substr($info['mobile'],3,(strlen($info['mobile']) - 7));
            $info['mobile'] = str_replace($replaceStr,'***',$info['mobile']);
        }
        $info['logo'] = !empty($info['logo']) ? tomedia($info['logo']) : tomedia($member['avatar']) ;
        $info['name'] = !empty($info['name']) ? $info['name'] : $member['nickname'] ;
        $info['one_class_title'] = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$info['one_class']),'name');
        $info['two_class_title'] = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$info['two_class']),'name');
        #5、判断当前用户是否点赞
        $info['is_laud'] = intval(0);
        if($_W['mid'] && !empty($info['laud_user'])){
            $laudUser = explode(',',$info['laud_user']);
            if(in_array($_W['mid'],$laudUser)){
                $info['is_laud'] = intval(1);
            }
        }
        unset($info['laud_user']);
        #6、判断当前用户是否已收存当前名片
        $isCollect = pdo_getcolumn(PDO_NAME."citycard_collect",['cardid' => $id , 'mid' => $_W['mid']],'id');
        $info['is_collect'] = intval($isCollect) > 0 ? 1 : 0 ;
        //查询是否有动态和店铺
        $pocketflag = pdo_getcolumn(PDO_NAME.'pocket_informations',array('uniacid'=>$_W['uniacid'],'mid'=>$info['mid'],'aid' => $_W['aid'],'status' =>0),'id');
        $info['showpocket'] = $pocketflag > 0 ? 1 : 0;
        $storelist = pdo_fetchall("SELECT b.id,b.storename,b.logo,b.address,b.location,b.storehours,b.pv,b.score,b.tag FROM " . tablename(PDO_NAME . "merchantuser") . " as a RIGHT JOIN " . tablename(PDO_NAME . "merchantdata") . " as b ON a.storeid = b.id WHERE a.mid = {$info['mid']} AND a.enabled = 1 AND b.enabled = 1 GROUP BY a.storeid ORDER BY a.createtime ASC ");
        $info['showstore'] = !empty($storelist)  ? 1 : 0;
        if(!strstr($info['address'], '省') && !strstr($info['address'], '市') && !strstr($info['address'], '县') &&  !strstr($info['address'], '自治区')){
            $proName = pdo_getcolumn(PDO_NAME.'area',array('id'=>$info['pro_code']),'name');
            $cityName = pdo_getcolumn(PDO_NAME.'area',array('id'=>$info['city_code']),'name');
            $areaName = pdo_getcolumn(PDO_NAME.'area',array('id'=>$info['area_code']),'name');
            $info['address'] = $proName.$cityName.$areaName.$info['address'];
        }

        $this->renderSuccess("名片详细信息",$info);
    }
    /**
     * Comment: 用户 点赞/取消点赞 操作
     * Author: zzw
     * Date: 2019/12/17 14:46
     */
    public function cardFabulous(){
        global $_W,$_GPC;
        #1、参数接收
        $id = $_GPC['id'] OR $this->renderError('缺少参数：id') ;//名片id
        #2、获取名片的点赞信息
        $ids = pdo_getcolumn(PDO_NAME."citycard_lists",['id'=>$id],'laud_user');
        if($ids){
            //判断是否已经点赞，做出对应的操作
            $idArr = explode(',',$ids);
            if(in_array($_W['mid'],$idArr)){
                //已经点赞 取消点赞操作
                $idArr = array_flip($idArr);
                unset($idArr[$_W['mid']]);
                $idArr = array_flip($idArr);
            }else{
                //未点赞  点赞操作
                $idArr[] = $_W['mid'];
            }
            //生成新的信息
            $data = [
                'laud_user' => implode(',', $idArr) ,
                'laud' => count($idArr)
            ];
        }else{
            $data = ['laud_user' => $_W['mid'] , 'laud' => 1];
        }
        #2、修改当前名片的点赞信息
        $res = pdo_update(PDO_NAME."citycard_lists",$data,['id'=>$id]);
        if($res) $this->renderSuccess('操作成功!');
        else $this->renderError('操作失败，请刷新重试!');
    }
    /**
     * Comment: 名片 收存/取消收存 操作
     * Author: zzw
     * Date: 2019/12/17 15:00
     */
    public function cardCollect(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'] OR $this->renderError('参数错误，id不存在！');
        #2、判断用户是否已收存  做出对应的操作
        $data = ['cardid' => $id , 'mid' => $_W['mid']];
        $isCollect = pdo_getcolumn(PDO_NAME."citycard_collect",$data,'id');
        if($isCollect){
            //已收存  取消收存
            $res = pdo_delete(PDO_NAME."citycard_collect",$data);
        }else{
            //未收存  进行收存操作
            $res = pdo_insert(PDO_NAME."citycard_collect",$data);
        }
        #2、返回存在结果
        if($res) $this->renderSuccess('操作成功!');
        else $this->renderError('操作失败，请刷新重试!');
    }
    /**
     * Comment: 名片分享数量增加操作
     * Author: zzw
     * Date: 2019/12/17 15:23
     */
    public function cardShare(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'] OR $this->renderError('参数错误，id不存在！');
        #2、修改分享数量
        pdo_fetch("UPDATE ".tablename(PDO_NAME.'citycard_lists')." SET `share` = (`share` + 1) WHERE `id` = {$id} ");
        $this->renderSuccess('操作成功!');
    }
    /**
     * Comment: 我的名片列表
     * Author: zzw
     * Date: 2019/12/18 15:58
     */
    public function myCard(){
        global $_W,$_GPC;
        #1、参数获取
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        #2、查询条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND mid = {$_W['mid']} ";
        #3、排序条件生成
        $order = " ORDER BY top_is DESC,createtime DESC ";
        #4、名片总数获取
        $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."citycard_lists") .$where);
        $data['total'] = ceil($total / $pageIndex);
        #5、获取用户还能创建的名片数量
        $set = Setting::agentsetting_read('citycard');
        $maxNumber = $set['maxcardnum'];
        if($maxNumber > 0)  $data['surplus'] = intval($maxNumber - $total);
        else $data['surplus'] = intval(999);
        #6、名片列表信息获取
        $field = "id,id as pid,top_is,
        (SELECT COUNT(*) FROM ".tablename(PDO_NAME.'citycard_collect')." WHERE cardid = pid) as total_collect,
        name,logo,mobile,wechat,company,branch,position,address,pro_code,city_code,area_code
        ,checkstatus,`desc`,show_addr,show_mobile,show_wechat,laud,pv,share,meal_endtime,paystatus,one_class,two_class";
        $data['list'] = pdo_fetchall("SELECT {$field} FROM ".tablename(PDO_NAME."citycard_lists") .$where.$order." LIMIT {$pageStart},{$pageIndex}");
        #7、循环进行信息的处理
        if(is_array($data['list']) && count($data['list']) > 0){
            $member = pdo_get(PDO_NAME."member",['id'=>$_W['mid']],['nickname','avatar']);
            foreach($data['list'] as $key => &$val){
                //基本信息处理
                $val['logo'] = !empty($val['logo']) ? tomedia($val['logo']) : tomedia($member['avatar']) ;
                $val['name'] = !empty($val['name']) ? $val['name'] : $member['nickname'] ;
                $val['one_class_title'] = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$val['one_class']),'name');
                $val['two_class_title'] = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$val['two_class']),'name');
                //判断是否过期  0=未过期，1=已过期
                $val['is_endtime'] = intval(0);
                if($val['meal_endtime'] < time()){
                    $val['is_endtime'] = intval(1);
                }
                $val['meal_endtime'] = date("Y-m-d",$val['meal_endtime']);
                //手机号隐藏处理
                if($val['show_mobile'] == 0 && strlen($val['mobile']) > 1){
                    $replaceStr = substr($val['mobile'],3,(strlen($val['mobile']) - 7));
                    $val['mobile'] = str_replace($replaceStr,'***',$val['mobile']);
                }
                //处理地址信息
                if(!strstr($val['address'], '省') && !strstr($val['address'], '市') && !strstr($val['address'], '县') &&  !strstr($val['address'], '自治区')){
                    $proName = pdo_getcolumn(PDO_NAME.'area',array('id'=>$val['pro_code']),'name');
                    $cityName = pdo_getcolumn(PDO_NAME.'area',array('id'=>$val['city_code']),'name');
                    $areaName = pdo_getcolumn(PDO_NAME.'area',array('id'=>$val['area_code']),'name');
                    $val['address'] = $proName.$cityName.$areaName.$val['address'];
                }
            }
        }

        $this->renderSuccess('我的名片列表',$data);
    }
    /**
     * Comment: 创建或编辑名片数据接口
     * Author: wlf
     * Date: 2019/12/16 17:50
     */
    public function createCityCardApi(){
        global $_W,$_GPC;
        $id = $_GPC['id']; //名片id
        $data = array('paystatus'=>0);  //初始化返回参数
        $carddata = array(
            'name'        => trim($_GPC['name']),        //姓名
            'mobile'      => trim($_GPC['mobile']),      //电话
            'wechat'      => trim($_GPC['wechat']),      //微信
            'company'     => trim($_GPC['company']),     //公司
            'branch'      => trim($_GPC['branch']),      //部门
            'position'    => trim($_GPC['position']),    //岗位
            'desc'        => trim($_GPC['desc']),        //简介
            'address'     => trim($_GPC['address']),     //地址
            'lng'         => trim($_GPC['lng']),         //定位经度
            'lat'         => trim($_GPC['lat']),         //定位纬度
            'one_class'   => trim($_GPC['one_class']),   //一级分类
            'two_class'   => trim($_GPC['two_class']),   //二级分类
            'show_addr'   => trim($_GPC['show_addr']),   //是否显示地址 0隐藏 1显示
            'show_mobile' => trim($_GPC['show_mobile']), //是否显示电话 0隐藏 1显示
            'show_wechat' => trim($_GPC['show_wechat']), //是否显示微信 0隐藏 1显示
            'logo'        => trim($_GPC['logo']),
            'pro_code'    => trim($_GPC['provinceid']),
            'city_code'   => trim($_GPC['cityid']),
            'area_code'   => trim($_GPC['countyid'])
        );
        //校验文本
        $textRes = Filter::init($carddata['name'],$_W['source'],1);
        if($textRes['errno'] == 0){
            $this->renderError('名片名字'.$textRes['message']);
        }
        $textRes = Filter::init($carddata['desc'],$_W['source'],1);
        if($textRes['errno'] == 0){
            $this->renderError('名片简介'.$textRes['message']);
        }
        if(empty($id)){
            $carddata['createtime'] = time();
            $carddata['uniacid'] = $_W['uniacid'];
            $carddata['aid'] = $_W['aid'];
            $carddata['mid'] = $_W['mid'];
            $carddata['meal_id'] = trim($_GPC['meal_id']);
            if(empty($carddata['logo'])){
                $carddata['logo'] = $_W['wlmember']['avatar'];
            }
            $res = pdo_insert('wlmerchant_citycard_lists',$carddata);
            if($res){
                $cardid = pdo_insertid();
                //获取套餐数据
                $meal = pdo_get('wlmerchant_citycard_meals',array('id' => $carddata['meal_id']));
                //会员判断
                $vipflag = WeliamWeChat::VipVerification($_W['mid'],true);
                if($meal['vipstatus'] == 1){
                    if($vipflag > 0){
                        if($meal['vipprice'] > 0){
                            $meal['price'] = $meal['vipprice'];
                        }else{
                            $meal['is_free'] = 1;
                        }
                    }
                }else if($meal['vipstatus'] == 2){
                    if(empty($vipflag)){
                        $this->renderError('此套餐为会员特供，请先成为会员');
                    }
                }
                if($meal['is_free']){   //免费
                    $updata['paystatus'] = 1;
                    if(empty($meal['check'])){
                        $updata['checkstatus'] = 1;
                        $updata['status'] = 1;//默认启用
                    }//免审核
                    $updata['meal_endtime'] = time() + $meal['day']*3600*24;
                    pdo_update('wlmerchant_citycard_lists',$updata,array('id' => $cardid));
                    if(empty($updata['checkstatus'])){   //通知管理员
                        $nickname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$carddata['mid']),'nickname');
                        $onecatename = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$carddata['one_class']),'name');
                        $twocatename = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$carddata['two_class']),'name');
                        $catename = !empty($twocatename)?$onecatename.'-'.$twocatename:$onecatename;
                        $first = '您好,用户['.$nickname. ']上传了新的城市名片信息';
                        $type = '新的城市名片信息认证';
                        $content = '名片分类:['.$catename.']';
                        $status = '待审核';
                        $remark = '请尽快前往系统后台审核名片资料';
                        News::noticeAgent('citycard',$carddata['aid'],$first,$type,$content,$status,$remark,time());
                    }
                    $data['cardid'] = $cardid;
                    $this->renderSuccess('创建成功',$data);
                }else{    //收费创建订单
                    if($meal['price']<0.01){
                        $this->renderError('支付金额有误，请联系管理员');
                    }
                    $orderdata = array(
                        'uniacid'         => $carddata['uniacid'],
                        'mid'             => $carddata['mid'],            //付款人id
                        'sid'             => 0,
                        'aid'             => $carddata['aid'],
                        'fkid'            => $carddata['meal_id'],        //套餐id
                        'plugin'          => 'citycard',
                        'payfor'          => 'citycardOrder',
                        'orderno'         => createUniontid(),
                        'status'          => 0,//订单状态：0未支付,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
                        'createtime'      => TIMESTAMP,
                        'oprice'          => $meal['price'],
                        'price'           => $meal['price'],
                        'num'             => 1,
                        'fightstatus'     => 1,                    //付费激活或续费
                        'specid'          => $cardid,              //名片的id
                        'goodsprice'      => $meal['price'],
                        'remark'          => '',
                        'settlementmoney' => 0
                    );
                    pdo_insert(PDO_NAME . 'order', $orderdata);
                    $data['orderid'] = pdo_insertid();
                    $data['paystatus'] = 1;
                    $this->renderSuccess('请支付',$data);
                }
            }else{
                $this->renderError('保存失败请刷新重试');
            }
        }else{
            $card = pdo_get('wlmerchant_citycard_lists',array('id' => $id),array('meal_id'));
            $meal = pdo_get('wlmerchant_citycard_meals',array('id' => $card['meal_id']));
            if(empty($meal['check'])){
                $carddata['checkstatus'] = 1;
            }else{
                $carddata['checkstatus'] = 0;
            }
            //if(!empty($meal['check'])){$carddata['checkstatus'] = 0;}//需要审核
            $res = pdo_update('wlmerchant_citycard_lists',$carddata,array('id' => $id));
            if(empty($carddata['checkstatus'])){   //通知管理员
                $nickname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$carddata['mid']),'nickname');
                $onecatename = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$carddata['one_class']),'name');
                $twocatename = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$carddata['two_class']),'name');
                $catename = !empty($twocatename)?$onecatename.'-'.$twocatename:$onecatename;
                $first = '您好,用户['.$nickname. ']修改了自己的城市名片信息';
                $type = '修改城市名片信息认证';
                $content = '名片分类:['.$catename.']';
                $status = '待审核';
                $remark = '请尽快前往系统后台审核名片资料';
                News::noticeAgent('citycard',$carddata['aid'],$first,$type,$content,$status,$remark,time());
            }
            if($res){
                $this->renderSuccess('修改成功',$data);
            }else{
                $this->renderError('修改失败或无数据修改');
            }
        }
    }
    /**
     * Comment: 创建或编辑名片数据页面
     * Author: wlf
     * Date: 2019/12/17 18:20
     */
    public function createCityCardPage(){
        global $_W,$_GPC;
        $id = $_GPC['id']; //名片id
        $data = [];  //初始化返回参数
        if($id){  //用户数据
            $data['cardinfo'] = pdo_get('wlmerchant_citycard_lists',array('id' => $id),array('name','pro_code','city_code','area_code','logo','mobile','wechat','company','branch','position','desc','address','lng','lat','one_class','two_class','show_addr','show_mobile','show_wechat'));
            $data['cardinfo']['logo'] = tomedia($data['cardinfo']['logo']);
        }
        //获取分类名称
        $data['cardinfo']['onecatename'] = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$data['cardinfo']['one_class']),'name');
        $data['cardinfo']['twocatename'] = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$data['cardinfo']['two_class']),'name');
        if(!$data['cardinfo']){$data['cardinfo'] = [];}
        //获取行业信息
        $cates = pdo_getall(PDO_NAME.'citycard_cates' , [
            'parentid' => 0 ,
            'enabled'  => 1 ,
            'uniacid'  => $_W['uniacid'] ,
            'aid'      => $_W['aid']
        ] , [] , '' , "sort DESC");
        foreach ($cates as  &$cate){
            $cate['childrens'] = pdo_getall('wlmerchant_citycard_cates',array('parentid'=>$cate['id'],'enabled'=>1,'uniacid' => $_W['uniacid'],'aid' => $_W['aid']), [], '', "sort DESC");
            if(!empty($cate['childrens'])){
                $data['cates'][] = $cate;
            }
        }
        //获取套餐信息
        $data['meals'] = Citycard::get_meals(false);
        if(!empty($data['meals'])){
            $vipflag = WeliamWeChat::VipVerification($_W['mid'],true);
            foreach($data['meals'] as $key => &$meal){
                if($meal['vipstatus'] == 1){
                    if($vipflag > 0){
                        if($meal['vipprice'] > 0){
                            $meal['price'] = $meal['vipprice'];
                        }else{
                            $meal['is_free'] = 1;
                        }
                    }
                }else if($meal['vipstatus'] == 2){
                    if(empty($vipflag)){
                       unset($data['meals'][$key]);
                    }
                }
                if($_W['wlsetting']['base']['payclose'] > 0 && is_ios()){
                    if(empty($meal['is_free'])){
                        unset($data['meals'][$key]);
                    }
                }
            }
        }
        $new = [];
        foreach($data['meals'] as $key=>$value) {
            $new[] = $value;
        }
        $data['meals'] = $new;
        //获取入驻协议
        $set = Setting::agentsetting_read('citycard');
        $data['agreement'] = $set['agreement'];

        $data['cardinfo']['provinceid'] = $data['cardinfo']['pro_code'];unset($data['cardinfo']['pro_code']);
        $data['cardinfo']['cityid'] = $data['cardinfo']['city_code'];unset($data['cardinfo']['city_code']);
        $data['cardinfo']['countyid'] = $data['cardinfo']['area_code'];unset($data['cardinfo']['area_code']);
        //定制替换文本
        if(Customized::init('citycard1503') > 0){
            $data['schoolText'] = 1;
        }else{
            $data['schoolText'] = 0;
        }
        $this->renderSuccess('创建页面初始化',$data);
    }
    /**
     * Comment: 续费或置顶名片接口
     * Author: wlf
     * Date: 2019/12/18 09:55
     */
    public function renewCityCard(){
        global $_W,$_GPC;
        $cardid = $_GPC['cardid'];
        $objid = $_GPC['objid'];
        $type = $_GPC['type'];  //1续费 2置顶
        $card = pdo_get('wlmerchant_citycard_lists',array('id' => $cardid));
        $vipflag = WeliamWeChat::VipVerification($_W['mid'],true);
        if(empty($card)){
            $this->renderError('名片参数错误,请刷新重试');
        }
        if($type == 1){
            $renew = pdo_get('wlmerchant_citycard_meals',array('id' => $objid));
            if(empty($renew)){
                $this->renderError('套餐参数无效,请刷新重试');
            }
            //会员判断
            if($renew['vipstatus'] == 1){
                if($vipflag > 0){
                    if($renew['vipprice'] > 0){
                        $renew['price'] = $renew['vipprice'];
                    }else{
                        $renew['is_free'] = 1;
                    }
                }
            }else if($renew['vipstatus'] == 2){
                if(empty($vipflag)){
                    $this->renderError('此套餐为会员特供，请先成为会员');
                }
            }

        }else if($type == 2){
            $renew = pdo_get('wlmerchant_citycard_tops',array('id' => $objid));
            if(empty($renew)){
                $this->renderError('置顶餐参数无效,请刷新重试');
            }
            //判断会员
            if($renew['vipstatus'] == 1 && $vipflag > 0){
                $renew['price'] = $renew['vipprice'];
            }else if($renew['vipstatus'] == 2 && empty($vipflag)){
                $this->renderError('此套餐为会员特供，请先成为会员');
            }
        }else{
            $this->renderError('类型参数错误,请刷新重试');
        }
        if($renew['is_free']){
            if($card['meal_endtime']>time()){
                $updata['meal_endtime'] = $card['meal_endtime'] + $renew['day']*3600*24;
            }else{
                $updata['meal_endtime'] = time() + $renew['day']*3600*24;
            }
            $updata['paystatus'] = 1;
            if(empty($renew['check'])){$updata['checkstatus'] = 1;}//免审核
            pdo_update('wlmerchant_citycard_lists',$updata,array('id' => $cardid));
            if(empty($updata['checkstatus'])){   //通知管理员
                $nickname = pdo_getcolumn(PDO_NAME.'member',array('id'=>$card['mid']),'nickname');
                $onecatename = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$card['one_class']),'name');
                $twocatename = pdo_getcolumn(PDO_NAME.'citycard_cates',array('id'=>$card['two_class']),'name');
                $catename = !empty($twocatename)?$onecatename.'-'.$twocatename:$onecatename;
                $first = '您好,用户['.$nickname. ']修改了自己的城市名片信息';
                $type = '修改城市名片信息认证';
                $content = '名片分类:['.$catename.']';
                $status = '待审核';
                $remark = '请尽快前往系统后台审核名片资料';
                News::noticeAgent('citycard',$card['aid'],$first,$type,$content,$status,$remark,time());
            }
            $data['paystatus'] = 0;
            $this->renderSuccess('续费成功',$data);
        }else{
            if($renew['price']<0.01){
                $this->renderError('支付金额有误，请联系管理员');
            }
            $orderdata = array(
                'uniacid'         => $card['uniacid'],
                'mid'             => $card['mid'],            //付款人id
                'sid'             => 0,
                'aid'             => $card['aid'],
                'fkid'            => $objid,                 //套餐或置顶id
                'plugin'          => 'citycard',
                'payfor'          => 'citycardOrder',
                'orderno'         => createUniontid(),
                'status'          => 0,//订单状态：0未支付,1支付，2待发货，3已发货，4已签收，5已取消，6待退款，7已退款
                'createtime'      => TIMESTAMP,
                'oprice'          => $renew['price'],
                'price'           => $renew['price'],
                'num'             => 1,
                'fightstatus'     => $type,                //1付费激活或续费 2置顶
                'specid'          => $cardid,              //名片的id
                'goodsprice'      => $renew['price'],
                'remark'          => '',
                'settlementmoney' => 0
            );
            pdo_insert(PDO_NAME . 'order', $orderdata);
            $data['orderid'] = pdo_insertid();
            $data['paystatus'] = 1;
            $this->renderSuccess('请支付',$data);
        }

    }
    /**
     * Comment: 名片（置顶|套餐）信息列表
     * Author: zzw
     * Date: 2019/12/23 14:34
     */
    public function cardRelevantInfo(){
        global $_W,$_GPC;
        $vipflag = WeliamWeChat::VipVerification($_W['mid'],true);
        #1、获取套餐列表
        $data['meals'] = pdo_getall(PDO_NAME."citycard_meals"
            , ['status'=>1,'aid'=>$_W['aid'],'uniacid'=>$_W['uniacid']]
            , ['id','name','is_free','price','day','vipstatus','vipprice'],'','sort DESC');

        if(!empty($data['meals'])){
            foreach($data['meals'] as $key => &$meal){
                if($meal['vipstatus'] == 1){
                    if($vipflag > 0){
                        if($meal['vipprice'] > 0){
                            $meal['price'] = $meal['vipprice'];
                        }else{
                            $meal['is_free'] = 1;
                        }
                    }
                }else if($meal['vipstatus'] == 2){
                    if(empty($vipflag)){
                        unset($data['meals'][$key]);
                    }
                }
                if($_W['wlsetting']['base']['payclose'] > 0 && is_ios()){
                    if(empty($meal['is_free'])){
                        unset($data['meals'][$key]);
                    }
                }
            }
            $new = [];
            foreach($data['meals'] as $key=>$value) {
                $new[] = $value;
            }
            $data['meals'] = $new;

        }


        #2、获取置顶列表
        $data['tops'] = pdo_getall(PDO_NAME."citycard_tops"
            , ['status'=>1,'aid'=>$_W['aid'],'uniacid'=>$_W['uniacid']]
            , ['id','name','price','day','vipstatus','vipprice'],'','sort DESC');

        if(!empty($data['tops'])){
            foreach($data['tops'] as $key => &$top){
                if($top['vipstatus'] == 1 && $vipflag > 0){
                    $top['price'] = $top['vipprice'];
                    if($top['price'] < 0.01){
                        $top['is_free'] = 1;
                    }
                }else if($top['vipstatus'] == 2 && empty($vipflag)){
                    unset($data['tops'][$key]);
                }

                if($_W['wlsetting']['base']['payclose'] > 0 && is_ios()){
                    if(empty($top['is_free'])){
                        unset($data['tops'][$key]);
                    }
                }
            }
            $new = [];
            foreach($data['tops'] as $key=>$value) {
                $new[] = $value;
            }
            $data['tops'] = $new;

        }

        $this->renderSuccess('名片相关信息',$data);
    }


}