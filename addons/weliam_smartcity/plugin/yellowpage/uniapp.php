<?php
defined('IN_IA') or exit('Access Denied');

class YellowpageModuleUniapp extends Uniapp {
    /**
     * Comment: 获取黄页分类列表
     * Author: wlf
     * Date: 2020/8/14 15:54
     */
    public function cateList(){
        global $_W,$_GPC;
        $search = trim($_GPC['search']);
        $list = Yellowpage::get_cates(false,$_W['aid'],$search);
        $data['list'] = $list;
        $this->renderSuccess('分页列表',$data);
    }

    /**
     * Comment: 获取黄页首页初始化数据
     * Author: wlf
     * Date: 2020/8/14 17:58
     */
    public function homeInfo(){
        global $_W,$_GPC;
        //幻灯片
        $settings = Setting::agentsetting_read('yellowpage');
        $data = [];
        $advs = pdo_getall('wlmerchant_adv',array('aid' => $_W['aid'],'uniacid' => $_W['uniacid'],'type' => 13,'enabled' => 1),array('link','thumb'),'','displayorder DESC,id DESC');
        if(!empty($advs)){
            foreach($advs as &$adv){
                $adv['thumb'] = tomedia($adv['thumb']);
            }
        }
        $data['adv'] = $advs;
        //社群
        if($settings['communityid'] > 0){
            $community = pdo_get('wlmerchant_community',array('id' => $settings['communityid']),array('id','communname','commundesc','communimg','communqrcode','systel'));
            $community['community_id'] = $community['id'];
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
        //入驻广告
        $time = time();
        $notice = pdo_fetchall("SELECT name,createtime,id FROM ".tablename('wlmerchant_yellowpage_lists')."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 1 AND checkstatus = 1 AND meal_endtime > {$time} ORDER BY createtime DESC LIMIT 10");
        if(!empty($notice)){
            foreach($notice as &$not){
                $not['createtime'] = date('y-m-d',$not['createtime']);
            }
        }
        $data['notice'] = $notice;
        $data['noticeflag'] = 'yellowpage';

        $this->renderSuccess('黄页首页初始化数据',$data);
    }

    /**
     * Comment: 获取黄页首页列表数据
     * Author: wlf
     * Date: 2020/8/17 10:17
     */
    public function homeList(){
        global $_W,$_GPC;
        $data      = [];
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $keyword   = $_GPC['keyword'];   //搜索关键词
        $sort      = $_GPC['sort'] ? : 1;
        $cateOne   = $_GPC['cate_one'] ? : 0;//一级分类
        $cateTwo   = $_GPC['cate_two'] ? : 0;//二级分类
//        $cityId    = $_GPC['city_id'] ? : 0;//区域id
        $cityId = 0;
        $lng       = $_GPC['lng'] && $_GPC['lng'] != 'undefined' ? $_GPC['lng'] : 0;//用户当前所在经度 104.0091133118 经度
        $lat       = $_GPC['lat'] && $_GPC['lat'] != 'undefined' ? $_GPC['lat'] : 0;//用户当前所在纬度 30.5681964123  纬度
        $time      = time();
        $settings = Setting::agentsetting_read('yellowpage');
        //查询条件生成  基本条件：启用，审核通过，已支付
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 1 AND checkstatus = 1 AND meal_endtime > {$time} ";
        if($cateOne > 0) $where .= " AND one_class = {$cateOne} ";
        if($cateTwo > 0) $where .= " AND two_class = {$cateTwo} ";
        if($cityId  > 0) $where .= " AND ( pro_code = {$cityId} OR city_code = {$cityId} OR area_code = {$cityId} ) ";
        if($keyword) $where .= " AND ( name LIKE '%{$keyword}%' OR `desc` LIKE '%{$keyword}%' )";
        //排序条件生成
        switch ($sort){
            case 1:$order = " ORDER BY distance ASC ";break;//附近
            case 2:$order = " ORDER BY createtime DESC ";break;//最新
            case 3:$order = " ORDER BY pv DESC ";break;//人气榜
            case 4:$order = " ORDER BY total_collect DESC,id DESC ";break;//收藏榜
            case 5:$order = " ORDER BY sort DESC,id DESC ";break;//系统推荐
        }
        //总数获取
        if($page == 1){
            $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."yellowpage_lists") .$where);
            $data['total'] = ceil($total / $pageIndex);
        }
        //列表信息获取
        $field = "id,id as pid,mid,
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
            END FROM ".tablename(PDO_NAME.'yellowpage_lists')." as b WHERE b.id = pid) as distance,
            (SELECT COUNT(*) FROM ".tablename(PDO_NAME.'yellowpage_collect')." WHERE pageid = pid) as total_collect,
        name,logo,mobile,`desc`,one_class,two_class,pv,share,address";
        $list = pdo_fetchall("SELECT {$field} FROM ".tablename(PDO_NAME."yellowpage_lists") .$where.$order." LIMIT {$pageStart},{$pageIndex}");
        //循环进行信息的处理
        if(is_array($list) && count($list) > 0){
            foreach($list as $key => &$val){
                //基本信息处理
                $val['distance'] = Commons::distanceConversion($val['distance']);   //距离转换
                $val['logo'] = tomedia($val['logo']);
                $one_class = pdo_get(PDO_NAME.'yellowpage_cates',array('id'=>$val['one_class']),array('name','querymoney'));
                $val['oneCateName'] = $one_class['name'];
                $two_class = pdo_get(PDO_NAME.'yellowpage_cates',array('id'=>$val['two_class']),array('name','querymoney'));
                $val['twoCateName'] = $two_class['name'];
                $val['querymoney'] = !empty($two_class) ? $two_class['querymoney'] : $one_class['querymoney'];

                Yellowpage::changepv($val['id'],$settings['minup'],$settings['maxup']);
                $val['showtel'] = 1;
                if($val['querymoney']>0){
                    $payflag = pdo_getcolumn(PDO_NAME.'order',array('fkid'=>$val['id'],'mid'=>$_W['mid'],'status'=>3,'fightstatus'=>2),'id');
                    if(empty($payflag)){
                        $val['showtel'] = 0;
                    }
                }

            }
        }
        $data['list'] = $list;
        $this->renderSuccess('首页列表信息',$data);
    }

    /**
     * Comment: 获取黄页详情
     * Author: wlf
     * Date: 2020/8/17 16:12
     */
    public function pageDetail(){
        global $_W,$_GPC;
        $data      = [];
        $id = $_GPC['id'];
        $sid = $_GPC['sid'];
        if(empty($id) && empty($sid)){
            $this->renderError('缺少关键参数id');
        }
        $settings = Setting::agentsetting_read('yellowpage');
        if($id > 0){
            $yellowpage = pdo_get('wlmerchant_yellowpage_lists',array('id' => $id),array('name','mid','lng','lat','storeid','detail','logo','mobile','thumbs','desc','address','two_class','one_class','pv','share','status','wechat_number','wechat_qrcode'));
        }else{
            $yellowpage = pdo_get('wlmerchant_yellowpage_lists',array('storeid' => $sid),array('id','lng','lat','name','mid','detail','storeid','logo','mobile','thumbs','desc','address','two_class','one_class','pv','share','status','wechat_number','wechat_qrcode'));
            $id = $yellowpage['id'];
        }

        if(empty($yellowpage)){
            $this->renderError('页面不存在请刷新重试');
        }
        //更新PV
        Yellowpage::changepv($id,$settings['minup'],$settings['maxup']);
        if($yellowpage['status'] != 1){
            $this->renderError('页面已关闭');
        }
        $yellowpage['logo'] = tomedia($yellowpage['logo']);
        $yellowpage['wechat_qrcode'] = tomedia($yellowpage['wechat_qrcode']);
        $yellowpage['thumbs'] = unserialize($yellowpage['thumbs']);
        if(!empty($yellowpage['thumbs'])){
            foreach($yellowpage['thumbs'] as &$thumb){
                $thumb = tomedia($thumb);
            }
        }
        $one_class = pdo_get(PDO_NAME.'yellowpage_cates',array('id'=>$yellowpage['one_class']),array('name','claimmoney','querymoney'));
        $yellowpage['oneCateName'] = $one_class['name'];
        $two_class = pdo_get(PDO_NAME.'yellowpage_cates',array('id'=>$yellowpage['two_class']),array('name','claimmoney','querymoney'));
        $yellowpage['twoCateName'] = $two_class['name'];
        $yellowpage['claimmoney'] = !empty($two_class) ? $two_class['claimmoney'] : $one_class['claimmoney'];
        $yellowpage['querymoney'] = !empty($two_class) ? $two_class['querymoney'] : $one_class['querymoney'];

        if(!empty($yellowpage['detail']) && is_base64($yellowpage['detail'])) $yellowpage['detail'] = htmlspecialchars_decode(base64_decode($yellowpage['detail']));
        else if(!empty($yellowpage['detail'])) $yellowpage['detail'] = htmlspecialchars_decode($yellowpage['detail']);
        //是否认领
        if(empty($yellowpage['mid']) && $settings['claimstatus'] > 0){
            //判断用户是否申请过
            $alreadyClaim = pdo_getcolumn(PDO_NAME.'yellowpage_claim_lists',array('mid'=>$_W['mid'],'pageid'=>$id,'status' => 0,'paystatus >' => 0),'id');
            if($alreadyClaim > 0){
                $yellowpage['claimdiv'] = 0;
            } else{
                $yellowpage['claimdiv'] = 1;
            }
        }else{
            $yellowpage['claimdiv'] = 0;
        }
        //是否付费查看
        $yellowpage['showtel'] = 1;
        if($yellowpage['querymoney']>0){
            $payflag = pdo_getcolumn(PDO_NAME.'order',array('fkid'=>$id,'mid'=>$_W['mid'],'status'=>3,'fightstatus'=>2),'id');
            if(empty($payflag)){
                $yellowpage['showtel'] = 0;
            }
        }
        //是否已收藏
        $yellowpage['aCollect'] = 0;
        $collectflag = pdo_getcolumn(PDO_NAME.'yellowpage_collect',array('pageid'=>$id,'mid'=>$_W['mid']),'id');
        if(!empty($collectflag)){
            $yellowpage['aCollect'] = 1;
        }
        $yellowpage['collectNum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_yellowpage_collect')." WHERE pageid = {$id}");
        $data['info'] = $yellowpage;
        $data['remark'] = $settings['remark'];
        $this->renderSuccess('黄页详情信息',$data);

    }

    /**
     * Comment: 认领接口
     * Author: wlf
     * Date: 2020/8/18 15:02
     */
    public function pageClaim(){
        global $_W,$_GPC;
        $pageid = $_GPC['id'];
        $desc = $_GPC['desc'];
        $name = $_GPC['name'];
        $mobile = $_GPC['mobile'];
        $data = [];
        $yellowpage = pdo_get('wlmerchant_yellowpage_lists',array('id' => $pageid),array('name','uniacid','aid','two_class','one_class'));
        if($yellowpage['mid']){
            $this->renderError('此黄页已被认领');
        }
        $alreadyClaim = pdo_getcolumn(PDO_NAME.'yellowpage_claim_lists',array('mid'=>$_W['mid'],'pageid'=>$pageid,'status' => 0,'paystatus >' => 0),'id');
        if($alreadyClaim > 0){
            $this->renderError('您已在认领此黄页');
        }
        $one_class = pdo_get(PDO_NAME.'yellowpage_cates',array('id'=>$yellowpage['one_class']),array('name','claimmoney','querymoney'));
        $two_class = pdo_get(PDO_NAME.'yellowpage_cates',array('id'=>$yellowpage['two_class']),array('name','claimmoney','querymoney'));
        $claimmoney = !empty($two_class) ? $two_class['claimmoney'] : $one_class['claimmoney'];
        if($claimmoney > 0){   //收费
            //查询之前的订单
            $orderid = pdo_getcolumn(PDO_NAME.'order',array('plugin'=>'yellowpage','fightstatus' => 1,'mid'=>$_W['mid'],'status'=>0,'fkid'=>$pageid),'id');
            if(!empty($orderid)){
                $data['orderid'] = $orderid;
            }else{
                //创建订单
                $canceltime = time() + $_W['wlsetting']['orderset']['cancel'] * 60;
                $orderdata = [
                    'uniacid'         => $yellowpage['uniacid'] ,
                    'mid'             => $_W['mid'] ,
                    'aid'             => $yellowpage['aid'] ,
                    'fkid'            => $pageid ,
                    'createtime'      => time() ,
                    'orderno'         => createUniontid() ,
                    'price'           => $claimmoney ,
                    'num'             => 1 ,
                    'plugin'          => 'yellowpage' ,
                    'payfor'          => 'pageOrder' ,
                    'goodsprice'      => $claimmoney,
                    'fightstatus'     => 1,
                    'canceltime'      => $canceltime,
                    'buyremark'       => $desc,
                    'name'            => $name,
                    'mobile'          => $mobile
                ];
                pdo_insert(PDO_NAME . 'order', $orderdata);
                $orderid = pdo_insertid();
                if(empty($orderid)){
                    $this->renderError('生成订单失败，请刷新重试');
                }else{
                    $data['orderid'] = $orderid;
                }
            }
            $data['status'] = 1;
            $this->renderSuccess('请支付认领订单',$data);
        }else{   //免费
            $claimData = [
                'uniacid'    => $yellowpage['uniacid'],
                'aid'        => $yellowpage['aid'],
                'mid'        => $_W['mid'],
                'pageid'     => $pageid,
                'createtime' => time(),
                'paystatus'  => 2,
                'desc'       => $desc,
                'name'       => $name,
                'mobile'     => $mobile
            ];
            $res = pdo_insert(PDO_NAME . 'yellowpage_claim_lists', $claimData);
            if($res){
                //发送模板消息通知
                $first   = "用户【{$name}】申请认领114页面";//消息头部
                $type    = "114页面认领";//业务类型
                $content = "114页面:[".$yellowpage['name']."]";//业务内容
                $status  = "待审核";//处理结果
                $remark  = "请尽快审核！";//备注信息
                $time    = $claimData['createtime'];//操作时间
                News::noticeAgent('yellowpage' , $claimData['aid'] , $first , $type , $content , $status , $remark , $time);
                $data['status'] = 0;
                $this->renderSuccess('认领申请成功，请等待系统审核',$data);
            }else{
                $this->renderError('认领申请失败，请刷新重试');
            }
        }
    }

    /**
     * Comment: 付费查看接口
     * Author: wlf
     * Date: 2020/8/18 17:36
     */
    public function pageQuery(){
        global $_W,$_GPC;
        $pageid = $_GPC['id'];
        $data = [];
        $yellowpage = pdo_get('wlmerchant_yellowpage_lists',array('id' => $pageid),array('name','uniacid','aid','two_class','one_class'));
        $one_class = pdo_get(PDO_NAME.'yellowpage_cates',array('id'=>$yellowpage['one_class']),array('name','claimmoney','querymoney'));
        $two_class = pdo_get(PDO_NAME.'yellowpage_cates',array('id'=>$yellowpage['two_class']),array('name','claimmoney','querymoney'));
        $querymoney = !empty($two_class) ? $two_class['querymoney'] : $one_class['querymoney'];
        if($querymoney > 0){
            //查询之前的订单
            $orderid = pdo_getcolumn(PDO_NAME.'order',array('plugin'=>'yellowpage','fightstatus' => 2,'mid'=>$_W['mid'],'status'=>0,'fkid'=>$pageid),'id');
            if(!empty($orderid)){
                $data['orderid'] = $orderid;
            }else{
                //创建订单
                $canceltime = time() + $_W['wlsetting']['orderset']['cancel'] * 60;
                $orderdata = [
                    'uniacid'         => $yellowpage['uniacid'] ,
                    'mid'             => $_W['mid'] ,
                    'aid'             => $yellowpage['aid'] ,
                    'fkid'            => $pageid ,
                    'createtime'      => time() ,
                    'orderno'         => createUniontid() ,
                    'price'           => $querymoney ,
                    'num'             => 1 ,
                    'plugin'          => 'yellowpage' ,
                    'payfor'          => 'pageOrder' ,
                    'goodsprice'      => $querymoney,
                    'fightstatus'     => 2,
                    'canceltime'      => $canceltime
                ];
                pdo_insert(PDO_NAME . 'order', $orderdata);
                $orderid = pdo_insertid();
                if(empty($orderid)){
                    $this->renderError('生成订单失败，请刷新重试');
                }else{
                    $data['orderid'] = $orderid;
                }
            }
            $data['status'] = 1;
            $this->renderSuccess('请支付认领订单',$data);

        }else{
            $this->renderError('支付金额错误，请刷新重试');
        }

    }


    /**
     * Comment: 页面纠错/举报接口
     * Author: wlf
     * Date: 2020/8/19 09:30
     */
    public function pageCorrection(){
        global $_W,$_GPC;
        $pageid = $_GPC['id'];
        $desc = $_GPC['desc'];
        $name = $_GPC['name'];
        $mobile = $_GPC['mobile'];
        $type = $_GPC['type'] ? : 2;   //1纠错 2举报
        if(empty($pageid)){
            $this->renderError('缺少关键参数');
        }
        if(empty($desc)){
            $this->renderError('请输入描述');
        }
        if(empty($name)){
            $this->renderError('请输入名字');
        }
        if(empty($mobile)){
            $this->renderError('请输入联系方式');
        }
        $yellowpage = pdo_get('wlmerchant_yellowpage_lists',array('id' => $pageid),array('uniacid','aid'));
        $correct = [
            'uniacid'  => $yellowpage['uniacid'],
            'aid'      => $yellowpage['aid'],
            'mid'      => $_W['mid'],
            'pageid'   => $pageid,
            'createtime' => time(),
            'desc'     => $desc,
            'name'     => $name,
            'mobile'   => $mobile
        ];
        if($type == 1){
            pdo_insert(PDO_NAME . 'yellowpage_correction_lists', $correct);
        }else{
            pdo_insert(PDO_NAME . 'yellowpage_report_lists', $correct);
        }
        $res = pdo_insertid();
        if(empty($res)){
            $this->renderError('提交失败，请刷新重试');
        }else{
            $this->renderSuccess('提交成功');
        }
    }

    /**
     * Comment: 页面收藏接口
     * Author: wlf
     * Date: 2020/8/19 09:48
     */
    public function pageCollect(){
        global $_W,$_GPC;
        $pageid = $_GPC['id'];
        if(empty($pageid)){
            $this->renderError('缺少关键参数');
        }
        $collectflag = pdo_getcolumn(PDO_NAME.'yellowpage_collect',array('pageid'=>$pageid,'mid'=>$_W['mid']),'id');
        if($collectflag > 0){
            $res = pdo_delete('wlmerchant_yellowpage_collect',array('id'=>$collectflag));
            if(empty($res)){
                $this->renderError('取消收藏失败，请刷新重试');
            }else{
                $this->renderSuccess('取消收藏成功');
            }
        }else{
            $yellowpage = pdo_get('wlmerchant_yellowpage_lists',array('id' => $pageid),array('uniacid','aid'));
            $collectdata = [
                'uniacid'   => $yellowpage['uniacid'],
                'aid'       => $yellowpage['aid'],
                'mid'       => $_W['mid'],
                'pageid'    => $pageid,
                'createtime' => time()
            ];
            $res = pdo_insert(PDO_NAME . 'yellowpage_collect', $collectdata);
            if(empty($res)){
                $this->renderError('收藏失败，请刷新重试');
            }else{
                $this->renderSuccess('收藏成功');
            }
        }
    }

    /**
     * Comment: 黄页入住页面初始化接口
     * Author: wlf
     * Date: 2020/8/19 16:45
     */

    public function getPageInfo(){
        global $_W,$_GPC;
        $settings = Setting::agentsetting_read('yellowpage');

        $pageid = $_GPC['id'];
        $storeid = $_GPC['storeid'];
        $data = [];
        if($pageid > 0){
            $yellowpage = pdo_get('wlmerchant_yellowpage_lists',array('id' => $pageid),['aid','detail','name','logo','mobile','thumbs','desc','address','one_class','two_class','pro_code','city_code','area_code','wechat_number','wechat_qrcode']);
            $yellowpage['logo'] = tomedia($yellowpage['logo']);
            $yellowpage['thumbs'] = unserialize($yellowpage['thumbs']);
            $yellowpage['wechat_qrcode'] = tomedia($yellowpage['wechat_qrcode']);
            if(!empty($yellowpage['detail']) && is_base64($yellowpage['detail'])) $yellowpage['detail'] = htmlspecialchars_decode(base64_decode($yellowpage['detail']));
            else if(!empty($yellowpage['detail'])) $yellowpage['detail'] = htmlspecialchars_decode($yellowpage['detail']);

            $data['pageinfo'] = $yellowpage;
            $aid = $yellowpage['aid'];
            $data['provinceid'] = $yellowpage['pro_code'];
            $data['areaid']     = $yellowpage['city_code'];
            $data['distid']     = $yellowpage['area_code'];
        }else if($storeid > 0){
            $store = pdo_get('wlmerchant_merchantdata',array('id' => $storeid),array('album','introduction','provinceid','areaid','distid','storename','aid','mobile','logo','address','describe'));
            $aid = $store['aid'];
            $store['album'] = unserialize($store['album']);
            $store['logo'] = tomedia($store['logo']);
            $data['pageinfo'] = [
                'name'      => $store['storename'],
                'logo'      => $store['logo'],
                'mobile'    => $store['mobile'],
                'thumbs'    => $store['album'],
                'desc'      => $store['describe'],
                'address'   => $store['address'],
                'one_class' => 0,
                'two_class' => 0,
                'pro_code'  => $store['provinceid'],
                'city_code' => $store['areaid'],
                'area_code' => $store['distid']
            ];
            if(!empty($store['introduction']) && is_base64($store['introduction'])) $store['introduction'] = htmlspecialchars_decode(base64_decode($store['introduction']));
            else if(!empty($store['introduction'])) $store['introduction'] = htmlspecialchars_decode($store['introduction']);
            $data['pageinfo']['detail'] = $store['introduction'];

            $data['provinceid'] = $store['provinceid'];
            $data['areaid']     = $store['areaid'];
            $data['distid']     = $store['distid'];
        }else{
            $aid = $_W['aid'];
            //获取地区信息
            if ($aid) {
                $areaid = pdo_getcolumn(PDO_NAME . 'oparea' , [
                    'uniacid' => $_W['uniacid'] ,
                    'aid'     => $aid
                ] , 'areaid');
            }
            else {
                $areaid = pdo_getcolumn(PDO_NAME . 'oparea' , ['uniacid' => $_W['uniacid'] , 'aid' => 0] , 'areaid');
            }
            if($areaid<110100){
                $areaid = 110100;
            }
            $area = pdo_get('wlmerchant_area' , ['id' => $areaid] , ['pid' , 'name' , 'level']);
            if ($area['level'] == 3) {
                $data['distid']     = $areaid;
                $data['areaid']     = $area['pid'];
                $data['provinceid'] = pdo_getcolumn(PDO_NAME . 'area' , ['id' => $data['areaid']] , 'pid');
            }
            else if ($area['level'] == 2) {
                $data['distid']     = pdo_getcolumn(PDO_NAME . 'area' , ['pid' => $areaid] , 'id');
                $data['areaid']     = $areaid;
                $data['provinceid'] = $area['pid'];
            }
            else {
                $data['provinceid'] = $areaid;
                $data['areaid']     = pdo_getcolumn(PDO_NAME . 'area' , ['pid' => $areaid] , 'id');
                $data['distid']     = pdo_getcolumn(PDO_NAME . 'area' , ['pid' => $data['areaid']] , 'id');
            }
        }
        if(!empty($data['pageinfo']['thumbs'])){
            foreach($data['pageinfo']['thumbs'] as &$thumb){
                $thumb = tomedia($thumb);
            }
        }
        //初始化信息 分类
        $data['catelist'] = Yellowpage::get_cates(false,$aid);
        //入驻套餐
        $data['meallist'] = pdo_getall('wlmerchant_yellowpage_meals',array('uniacid' =>$_W['uniacid'] ,'aid' => $aid,'status' => 1),array('id','name','check','is_free','price','day'),'','sort DESC');
        //入驻协议
        $data['agreement'] = $settings['agreement'];


        $this->renderSuccess('入驻页面初始化信息',$data);
    }

    /**
     * Comment: 通过地区更换分类
     * Author: wlf
     * Date: 2020/8/19 18:29
     */
    public function area2cate()
    {
        global $_W , $_GPC;
        $provinceid = $_GPC['provinceid'];
        $areaid     = $_GPC['areaid'];
        $distid     = $_GPC['distid'];
        $aid        = pdo_getcolumn(PDO_NAME . 'oparea' , ['uniacid' => $_W['uniacid'] , 'areaid' => $distid] , 'aid');
        if (empty($aid)) {
            $aid = pdo_getcolumn(PDO_NAME . 'oparea' , ['uniacid' => $_W['uniacid'] , 'areaid' => $areaid] , 'aid');
        }
        if (empty($aid)) {
            $aid = pdo_getcolumn(PDO_NAME . 'oparea' , ['uniacid' => $_W['uniacid'] , 'areaid' => $provinceid] , 'aid');
        }
        if (empty($aid)) {
            $aid = $_W['aid'];
        }
        $categoryes = Yellowpage::get_cates(false,$aid);
        $this->renderSuccess('页面分类信息' , $categoryes);
    }

    /**
     * Comment: 黄页入驻/编辑
     * Author: wlf
     * Date: 2020/8/20 09:45
     */
    public function pageSettlement(){
        global $_W , $_GPC;
        //获取参数
        $data = [];
        $pageid = $_GPC['pageid'];
        $storeid = $_GPC['storeid']? : 0;  //关联商户
        $mealid = $_GPC['meralid'];      //入驻类型
        //获取黄页拥有人
        if($storeid > 0){
            $mid = pdo_getcolumn(PDO_NAME.'merchantuser',array('storeid'=>$storeid,'ismain'=>1),'mid');
        }else{
            $mid = $_W['mid'];
        }
        //获取图集
        $album = $_GPC['thumbs'];
        if (!empty($album)) {
            $thumbs = serialize(explode(',' , $album));
        }
        else {
            $thumbs = '';
        }
        $pagedata = [
            'storeid'       => $storeid,
            'mid'           => $mid,
            'name'          => trim($_GPC['name']),
            'logo'          => $_GPC['logo'],
            'mobile'        => $_GPC['mobile'],
            'thumbs'        => $thumbs,
            'desc'          => $_GPC['desc'],
            'address'       => $_GPC['address'],
            'one_class'     => $_GPC['one_class'],
            'two_class'     => $_GPC['two_class'],
            'pro_code'      => $_GPC['provinceid'],
            'city_code'     => $_GPC['areaid'],
            'area_code'     => $_GPC['distid'],
            'lng'           => $_GPC['lng'],
            'lat'           => $_GPC['lat'],
            'wechat_number' => $_GPC['wechat_number'],
            'wechat_qrcode' => $_GPC['wechat_qrcode'],
            'detail'       => htmlspecialchars_decode($_GPC['detail']),         //黄页详情
        ];
        //判断内容是否完善
        if(!$pagedata['mobile'] && !$pagedata['wechat_number'] && !$pagedata['wechat_qrcode']) $this->renderError('联系电话&微信号&微信二维码请至少完善一个');


        //获取aid
        $aid             = pdo_getcolumn(PDO_NAME . 'oparea' , [
            'uniacid' => $_W['uniacid'] ,
            'areaid'  => $pagedata['distid'] ,
            'status'  => 1
        ] , 'aid');
        if (empty($aid)) {
            $aid = pdo_getcolumn(PDO_NAME . 'oparea' , [
                'uniacid' => $_W['uniacid'] ,
                'areaid'  => $pagedata['areaid'] ,
                'status'  => 1
            ] , 'aid');
        }
        if (empty($aid)) {
            $aid = pdo_getcolumn(PDO_NAME . 'oparea' , [
                'uniacid' => $_W['uniacid'] ,
                'areaid'  => $pagedata['provinceid'] ,
                'status'  => 1
            ] , 'aid');
        }
        if (empty($aid)) {
            $aid = 0;
        }
        $pagedata['aid']        = $aid ? : $_W['aid'];

        if(empty($pageid)){
            //创建
            $pagedata['meal_id'] = $mealid;
            $meal = pdo_get('wlmerchant_yellowpage_meals',array('id' => $mealid),array('is_free','price','day','check'));
            if(empty($meal)){
                $this->renderError('入驻类型选择错误，请刷新重试');
            }
            $pagedata['uniacid'] = $_W['uniacid'];
            $pagedata['createtime'] = time();
            pdo_insert(PDO_NAME . 'yellowpage_lists', $pagedata);
            $pageid = pdo_insertid();
            if($pageid > 0){
                if($meal['is_free'] > 0){  //免费
                    $meal_endtime = time() + $meal['day'] * 86400;
                    if($meal['check']){
                        pdo_update('wlmerchant_yellowpage_lists',['meal_endtime' => $meal_endtime,'paystatus'=>1],array('id' => $pageid));
                        $member = pdo_get('wlmerchant_member',array('id' => $pagedata['mid']),array('realname','nickname'));
                        $name = $member['realname'] ? : $member['nickname'];
                        //发送审核通知
                        $first   = "用户【{$name}】入驻114页面";//消息头部
                        $type    = "114页面入驻";//业务类型
                        $content = "114页面:[".$pagedata['name']."]";//业务内容
                        $status  = "待审核";//处理结果
                        $remark  = "请尽快审核！";//备注信息
                        $time    = time();//操作时间
                        News::noticeAgent('yellowpage' , $pagedata['aid'] , $first , $type , $content , $status , $remark , $time);
                        $data['status'] = 0;
                        $this->renderSuccess('页面创建成功,请等待系统审核',$data);
                    }else{
                        pdo_update('wlmerchant_yellowpage_lists',['status' =>1 ,'checkstatus' => 1,'meal_endtime' => $meal_endtime,'paystatus'=>1],array('id' => $pageid));
                        $data['status'] = 0;
                        $this->renderSuccess('页面创建成功',$data);
                    }
                }else{ //收费 创建订单
                    if($meal['price'] > 0){
                        //创建订单
                        $orderdata = [
                            'uniacid'         => $_W['uniacid'] ,
                            'mid'             => $mid,
                            'aid'             => $pagedata['aid'] ,
                            'fkid'            => $pageid ,
                            'createtime'      => time() ,
                            'orderno'         => createUniontid() ,
                            'price'           => $meal['price'] ,
                            'num'             => 1,
                            'plugin'          => 'yellowpage' ,
                            'payfor'          => 'pageOrder' ,
                            'goodsprice'      => $meal['price'],
                            'fightstatus'     => 3,
                            'specid'          => $mealid
                        ];
                        pdo_insert(PDO_NAME . 'order', $orderdata);
                        $orderid = pdo_insertid();
                        if(empty($orderid)){
                            $this->renderError('生成订单失败，请刷新重试');
                        }else{
                            $data['orderid'] = $orderid;
                            $data['status'] = 1;
                            $this->renderSuccess('订单生成成功，需要支付',$data);
                        }
                    }else{
                        $this->renderError('入驻类型金额错误，请联系管理员');
                    }
                }
            }else{
                $this->renderError('页面创建失败，请刷新重试');
            }
        }else{
            //编辑
            $mealid = pdo_getcolumn(PDO_NAME.'yellowpage_lists',array('id'=>$pageid),'meal_id');
            $meal = pdo_get('wlmerchant_yellowpage_meals',array('id' => $mealid),array('check'));
            if($meal['check']){
                $pagedata['checkstatus'] = 0;
            }else{
                $pagedata['checkstatus'] = 1;
            }
            $res = pdo_update('wlmerchant_yellowpage_lists',$pagedata,array('id' => $pageid));
            if($res){
                if($pagedata['checkstatus'] > 0){
                    $data['status'] = 0;
                    $this->renderSuccess('信息修改成功',$data);
                }else{
                    $member = pdo_get('wlmerchant_member',array('id' => $pagedata['mid']),array('realname','nickname'));
                    $name = $member['realname'] ? : $member['nickname'];
                    //发送审核通知
                    $first   = "用户【{$name}】入驻114页面";//消息头部
                    $type    = "114页面入驻";//业务类型
                    $content = "114页面:[".$pagedata['name']."]";//业务内容
                    $status  = "待审核";//处理结果
                    $remark  = "请尽快审核！";//备注信息
                    $time    = time();//操作时间
                    News::noticeAgent('yellowpage' , $pagedata['aid'] , $first , $type , $content , $status , $remark , $time);
                    $data['status'] = 0;
                    $this->renderSuccess('信息修改成功,请等待系统审核',$data);
                }
            }else{
                $this->renderError('信息修改失败，请刷新重试');
            }
        }


    }



    /**
     * Comment: 我的页面列表数据
     * Author: wlf
     * Date: 2020/8/20 15:15
     */
    public function myPageList(){
        global $_W , $_GPC;
        $data      = [];
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart = $page * $pageIndex - $pageIndex;

        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND mid = {$_W['mid']} ";
        $list = pdo_fetchall("SELECT id,name,logo,one_class,two_class,meal_endtime,status,checkstatus,paystatus,meal_id FROM ".tablename(PDO_NAME."yellowpage_lists") .$where." ORDER BY id DESC LIMIT {$pageStart},{$pageIndex}");
        if(!empty($list)){
            foreach($list as &$li){
                $li['logo'] = tomedia($li['logo']);
                $li['oneClassName'] = pdo_getcolumn(PDO_NAME.'yellowpage_cates',array('id'=>$li['one_class']),'name');
                $li['twoClassName'] = pdo_getcolumn(PDO_NAME.'yellowpage_cates',array('id'=>$li['two_class']),'name');
                if($li['meal_endtime'] > 0){
                    $li['meal_endtime'] = date('Y-m-d H:i:s',$li['meal_endtime']);
                }
                $meal = pdo_get('wlmerchant_yellowpage_meals',array('id' => $li['meal_id']),array('is_free'));
                if(empty($li['status'])){
                    if(empty($li['paystatus']) && empty($meal['is_free'])){
                        $li['showstatus'] = 2; //待付款
                        $li['orderid'] = pdo_getcolumn(PDO_NAME.'order',array('fkid'=>$li['id'],'specid'=>$li['meal_id'],'plugin'=>'yellowpage'),'id');
                    }else if(empty($li['checkstatus'])){
                        $li['showstatus'] = 3; //待审核
                    }else if($li['checkstatus'] == 2){
                        $li['showstatus'] = 4; //被驳回
                    }else{
                        $li['showstatus'] = 0; //已关闭
                    }
                }else{
                    $li['showstatus'] = 1; //展示中
                }
            }
        }
        $data['list'] = $list;
        if($page == 1){
            $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."yellowpage_lists") .$where);
            $data['total'] = ceil($total / $pageIndex);
            $data['meallist'] = pdo_getall('wlmerchant_yellowpage_meals',array('uniacid' =>$_W['uniacid'] ,'aid' => $_W['aid'],'status' => 1),array('id','name','check','is_free','price','day'),'','sort DESC');
        }
        $this->renderSuccess('我的列表信息数据',$data);
    }

    /**
     * Comment: 我的页面列表上下架接口
     * Author: wlf
     * Date: 2020/8/20 17:33
     */
    public function changeStatus(){
        global $_W,$_GPC;
        $pageid = $_GPC['id'];
        if(empty($pageid)){
            $this->renderError('缺少重要参数，请刷新重试');
        }
        $page = pdo_get('wlmerchant_yellowpage_lists',array('id' => $pageid),array('meal_endtime','status','checkstatus','paystatus','meal_id'));
        if(empty($page['status'])){
            if($page['meal_endtime'] < time()){
                $this->renderError('页面已过期，请续费页面');
            }
            if($page['checkstatus'] == 2){
                $this->renderError('审核被驳回，请重新提交审核');
            }
            if(empty($page['checkstatus'])){
                $this->renderError('页面审核中，请耐心等待');
            }
            $meal = pdo_get('wlmerchant_yellowpage_meals',array('id' => $pageid),array('is_free'));
            if(empty($page['paystatus']) && empty($meal['is_free'])){
                $this->renderError('请先进行入驻付款');
            }
            $res = pdo_update('wlmerchant_yellowpage_lists',array('status' => 1),array('id' => $pageid));
            if($res){
                $this->renderSuccess('展示成功');
            }else{
                $this->renderError('展示失败，请刷新重试');
            }
        }else{
            $res = pdo_update('wlmerchant_yellowpage_lists',array('status' => 0),array('id' => $pageid));
            if($res){
                $this->renderSuccess('关闭成功');
            }else{
                $this->renderError('关闭失败，请刷新重试');
            }
        }

    }

    /**
     * Comment: 我的页面列表续费架接口
     * Author: wlf
     * Date: 2020/8/20 16:28
     */
    public function renewPage(){
        global $_W,$_GPC;
        $pageid = $_GPC['pageid'];
        $mealid = $_GPC['mealid'];
        if(empty($pageid) || empty($mealid)){
            $this->renderError('缺少重要参数，请刷新重试');
        }
        $page = pdo_get('wlmerchant_yellowpage_lists',array('id' => $pageid),array('aid','mid','meal_endtime'));
        $meal = pdo_get('wlmerchant_yellowpage_meals',array('id' => $mealid),array('is_free','day','price','check','aid'));
        if($meal['is_free']){
            $meal_endtime = $page['meal_endtime'] > time() ? $page['meal_endtime'] + $meal['day'] * 86400 : time() + $meal['day'] * 86400;
            $res = pdo_update('wlmerchant_yellowpage_lists',array('meal_endtime' => $meal_endtime,'meal_id' => $mealid),array('id' => $pageid));
            if($res){
                $data['status'] = 0;
                $this->renderSuccess('续费成功',$data);
            }else{
                $this->renderError('续费失败，请刷新重试');
            }
        }else{
            //创建订单
            $orderdata = [
                'uniacid'         => $_W['uniacid'] ,
                'mid'             => $page['mid'],
                'aid'             => $page['aid'] ,
                'fkid'            => $pageid ,
                'createtime'      => time() ,
                'orderno'         => createUniontid() ,
                'price'           => $meal['price'] ,
                'num'             => 1,
                'plugin'          => 'yellowpage' ,
                'payfor'          => 'pageOrder' ,
                'goodsprice'      => $meal['price'],
                'fightstatus'     => 4,
                'specid'          => $mealid
            ];
            pdo_insert(PDO_NAME . 'order', $orderdata);
            $orderid = pdo_insertid();
            if(empty($orderid)){
                $this->renderError('生成订单失败，请刷新重试');
            }else{
                $data['orderid'] = $orderid;
                $data['status'] = 1;
                $this->renderSuccess('订单生成成功，需要支付',$data);
            }
        }

    }

    /**
     * Comment: 收藏页面列表
     * Author: wlf
     * Date: 2020/8/27 14:41
     */
    public function myCollectList(){
        global $_W , $_GPC;
        $data      = [];
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $lng = $_GPC['lng'];
        $lat = $_GPC['lat'];

        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND mid = {$_W['mid']} ";
        $list = pdo_fetchall("SELECT pageid FROM ".tablename(PDO_NAME."yellowpage_collect") .$where." ORDER BY createtime DESC LIMIT {$pageStart},{$pageIndex}");
        if(!empty($list)){
            foreach($list as &$li){
                $li = pdo_get('wlmerchant_yellowpage_lists',array('id' => $li['pageid']),array('id','logo','one_class','two_class','name','pv','share','mobile','lng','lat','desc','address'));
                $li['logo'] = tomedia($li['logo']);
                $li['oneClassName'] = pdo_getcolumn(PDO_NAME.'yellowpage_cates',array('id'=>$li['one_class']),'name');
                $li['twoClassName'] = pdo_getcolumn(PDO_NAME.'yellowpage_cates',array('id'=>$li['two_class']),'name');
                $li['collectNum'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_yellowpage_collect')." WHERE pageid = {$li['id']}");
                $li['distance'] = Store::getdistance($lng,$lat,$li['lng'],$li['lat'],1);
            }
        }
        $data['list'] = $list;
        if($page == 1){
            $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."yellowpage_collect") .$where);
            $data['total'] = ceil($total / $pageIndex);
        }
        $this->renderSuccess('收藏列表信息数据',$data);
    }

    /**
     * Comment: 分享后增加浏览量的接口
     * Author: wlf
     * Date: 2020/09/14 14:15
     */
    public function addShare(){
        global $_W , $_GPC;
        $id = $_GPC['id'];
        pdo_update('wlmerchant_yellowpage_lists', array('share +=' => 1), array('id' => $id));
        $this->renderSuccess('操作成功');
    }


}