<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 相亲交友
 * Author: zzw
 * Date: 2021/2/25 13:50
 * Class RecruitModuleUniapp
 */
class DatingModuleUniapp extends Uniapp{

    public function __construct(){
        global $_W,$_GPC;
        parent::__construct();
        //绑定红娘关系
        $headId = $_GPC['dating_head_id'] ? : 0;
        $memberInfo = pdo_get(PDO_NAME."dating_member",['mid'=>$_W['mid']]);
        if($headId > 0 && $memberInfo && !$memberInfo['matchmaker_id']) {
            $matchmakerId = pdo_getcolumn(PDO_NAME."dating_matchmaker",['mid'=>$headId,'mid <>'=>$_W['mid'],'uniacid'=>$_W['uniacid']],'id');
            if($matchmakerId > 0) pdo_update(PDO_NAME."dating_member",['matchmaker_id'=>$matchmakerId],['id'=>$memberInfo['id']]);
        }
        //判断当前用户是否允许进入当前页面
        $do = $_GPC['do'] ? : '';
        $limitApi = [
            'matchmakerCenter',//红娘中心
            'getMatchmakerWithdrawal',//获取红娘提现信息
            'matchmakerWithdrawal',//红娘提现
        ];
        if(in_array($do,$limitApi)){
            $isMatchmaker = pdo_get(PDO_NAME."dating_matchmaker",['mid'=>$_W['mid']]);
            if(!$isMatchmaker) $this->renderError('红娘信息不存在，请先申请！',['dating_is_matchmaker'=>1]);
        }



    }
    /**
     * Comment: 会员信息列表
     * Author: zzw
     * Date: 2021/3/4 9:56
     */
    public function homeList(){
        global $_W,$_GPC;
        //参数获取
        $page                    = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex               = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart               = $page * $pageIndex - $pageIndex;
        $lng                     = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度  104.0091133118
        $lat                     = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度  30.5681964123
        $is_total                = $_GPC['is_total'] ? : 0;//0=不获取总页数；1=获取总页数
        $nickname                = $_GPC['nickname'] ? : '';//用户昵称
        $gneder                  = $_GPC['gneder'] ? : 0;//性别:1=男,2=女
        $minAge                  = $_GPC['min_age'] ? : 0;//最小年龄
        $maxAge                  = $_GPC['max_age'] ? : 0;//最大年龄
        $minHeight               = $_GPC['min_height'] ? : 0;//最小身高
        $maxHeight               = $_GPC['max_height'] ? : 0;//最大身高
        $minWeight               = $_GPC['min_weight'] ? : 0;//最小体重
        $maxWeight               = $_GPC['max_weight'] ? : 0;//最大体重
        $maritalStatus           = $_GPC['marital_status'] ? : 0;//婚姻情况:1=未婚,2=离异(无子女),3=离异(有抚养权),4=离异(无抚养权),5=丧偶(无子女),6=丧偶(有子女)
        $education               = $_GPC['education'] ? : 0;//学历:1=小学,2=初中,3=高中/中专,4=专科,5=本科,6=硕士,7=博士
        $areaId                  = $_GPC['area_id'] ? : 0;//区域id
        $registeredResidenceType = $_GPC['registered_residence_type'] ? : 0;//户籍类型:1=农业户口,2=非农业户口
        $live                    = $_GPC['live'] ? : 0;//居住情况:1=自购房(有贷款),2=自购房(无贷款),3=租房(合租),4=租房(整租),5=与父母同住,6=借住亲朋家,7=单位住
        $travel                  = $_GPC['travel'] ? : 0;//出行情况:1=未购车,2=已购车
        $matchmakerId            = $_GPC['matchmaker_id'] ? : 0;//红娘id
        $isRecommend             = $_GPC['is_recommend'] ? : 0;//是否为获取推荐信息，0=不获取，1=获取未推荐用户信息，2=获取已推荐用户信息
        $memberId                = $_GPC['member_id'] ? : 0;//用户id，相亲交友会员表的member_id，获取推荐信息时必须。
        $getType                 = $_GPC['get_type'] ? : 0;//获取类型：0=获取全部，1=仅获取视频
        //获取当前用户信息
        $memberInfo = pdo_get(PDO_NAME."dating_member",['mid'=>$_W['mid']],['id','gneder']);
        //获取默认排序方式
        $set  = Setting::agentsetting_read("pluginlist");
        $sort = $_GPC['sort'] ? : $set['xqsort'];
        //特殊内容查询
        $distances = "ROUND(CASE 
                            WHEN {$lat} > 0 AND {$lng} > 0 AND a.lat > 0 AND a.lng > 0 THEN ROUND(6378.138 * 2 * ASIN(
                                    SQRT(
                                        POW(SIN(({$lat} * PI() / 180 - a.lat * PI() / 180) / 2),2) +
                                            COS({$lat} * PI() / 180) * COS(a.lat * PI() / 180) *
                                            POW(SIN(({$lng} * PI() / 180 - a.lng * PI() / 180) / 2),2)
                                        )
                                    ) * 1000
                                ) 
                            ELSE ''
                        END)";
        $area = "CASE WHEN current_area > 0 THEN current_area
                    WHEN current_city > 0 THEN current_city
                    ELSE current_province
                 END";
        $age = "CASE WHEN (FROM_UNIXTIME(unix_timestamp(now()) ,'%m') - FROM_UNIXTIME(a.birth,'%m')) < 0 AND (FROM_UNIXTIME(unix_timestamp(now()) ,'%d') - FROM_UNIXTIME(a.birth,'%d')) < 0 
                        THEN (TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(a.birth,'%Y-%m-%d'), CURDATE()) - 1)
                     ELSE TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(a.birth,'%Y-%m-%d'), CURDATE())
                END";
        //生成基本查询条件
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.examine = 3";
        if($nickname) $where .= " AND ( b.nickname LIKE '%{$nickname}%' or a.falsename  LIKE '%{$nickname}%' ) ";
        if($gneder > 0) $where .= " AND a.gneder IN ({$gneder}) ";
        if($minAge > 0) $where .= " AND {$age} >= {$minAge} ";
        if($maxAge > 0) $where .= " AND {$age} <= {$maxAge} ";
        if($minHeight > 0) $where .= " AND a.height >= {$minHeight} ";
        if($maxHeight > 0) $where .= " AND a.height <= {$maxHeight} ";
        if($minWeight > 0) $where .= " AND a.weight >= {$minWeight} ";
        if($maxWeight > 0) $where .= " AND a.weight <= {$maxWeight} ";
        if($maritalStatus > 0) $where .= " AND a.marital_status = {$maritalStatus} ";
        if($education > 0) $where .= " AND a.education = {$education} ";
        if($areaId > 0) $where .= " AND (a.current_province = {$areaId} OR a.current_city = {$areaId} OR a.current_area = {$areaId}) ";
        if($registeredResidenceType > 0) $where .= " AND a.registered_residence_type = {$registeredResidenceType} ";
        if($live > 0) $where .= " AND a.live = {$live} ";
        if($travel > 0) $where .= " AND a.travel = {$travel} ";
        if($matchmakerId > 0) $where .= " AND a.matchmaker_id = {$matchmakerId} ";
        else $where .= " AND a.aid = {$_W['aid']} ";
        if($isRecommend > 0){
            //判断是否存在用户id
            if(!$memberId) $this->renderError('用户信息不存在');
            //获取所有已给该用户推荐的会员的id
            $recommendMemberInfo = pdo_get(PDO_NAME."dating_member",['id'=>$memberId],['id','gneder','mid']);
            $memberField = " CASE WHEN mid_one = {$recommendMemberInfo['mid']} THEN mid_two
                                  ELSE mid_one
                             END as uid ";
            $sql = "SELECT {$memberField} FROM ".tablename(PDO_NAME."dating_exchange")
                ." WHERE uniacid = {$_W['uniacid']} AND (mid_one = {$recommendMemberInfo['mid']} OR mid_two = {$recommendMemberInfo['mid']})";
            $memberList = pdo_fetchall($sql);
            if($memberList && $isRecommend == 1) {
                //1=获取未推荐用户信息   存在已推荐则除却已推荐   不存在获取全部
                $where .= " AND a.mid NOT IN (" . implode(',' , array_column($memberList , 'uid')) . ") ";
            } else if($isRecommend == 2) {
                //获取已推荐用户信息  存在已推荐则获取已推荐  不存在则获取空内容
                if($memberList) $where .= " AND a.mid IN (" . implode(',' , array_column($memberList , 'uid')) . ") ";
                else $where .= " AND a.mid = -999 ";//-999  强制查询失败
            }
            //获取异性  性别:1=男,2=女
            if($recommendMemberInfo['gneder'] == 1) $where .= " AND a.gneder = 2 ";
            else if($recommendMemberInfo['gneder'] == 2) $where .= " AND a.gneder = 1 ";
        }
        if($getType > 0) $where .= " AND a.video IS NOT NULL AND a.video <> '' ";
        //生成排序条件 1=推荐排序  2=浏览量  3=发布时间  4=距离排序
        $order = " ORDER BY a.is_top DESC";
        switch ($sort) {
            case 1:
                $order .= ",a.sort DESC,a.id DESC ";
                break;//推荐排序
            case 2:
                $order .= ",pv DESC,a.id DESC ";
                break;//浏览量
            case 3:
                $order .= ",a.create_time DESC,a.id DESC ";
                break;//发布时间
            case 4:
                $order .= ",distances ASC,a.id DESC ";
                break;//距离排序
        }
        //sql语句生成
        $limit     = " LIMIT {$pageStart},{$pageIndex} ";
        $field     = "{$age} as age,{$distances} as distances,{$area} AS area_id,a.id,a.is_top,a.mid,a.gneder,a.birth,a.live,a.travel,a.pv,a.video,a.cover,a.work,a.hometown_province,a.hometown_city,a.hometown_area,a.marital_status,a.education,a.registered_residence_type,a.falsename,a.falseavatar,a.falsereal";
        $sql       = "SELECT {$field} FROM ".tablename(PDO_NAME."dating_member")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id ";
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        $data['university'] = Customized::init('university442') ? 1 : 0;

        foreach ($list as &$item) {
            //综合信息处理
            $item = Dating::handleMemberInfo($item);
            //最小一级区域信息查询
            $item['area'] = pdo_getcolumn(PDO_NAME."area",['id' => $item['area_id']],'name');
            //个人视频
            $item['video'] = tomedia($item['video']);
            //封面图
            $item['cover'] = tomedia($item['cover']);
            //年龄不能低于0
            $item['age'] = $item['age'] > 0 ? $item['age'] : 0;

            //删除多余的信息
            unset($item['birth'],$item['area_id'],$item['distances'],$item['mid']);
            //定制信息处理
            if($data['university'] > 0){
                $item['area'] = $item['hometown_address'];
                $item['distances_text'] = '';
                $optionid = pdo_getcolumn(PDO_NAME.'dating_checkbox',array('did'=>$item['id']),'optionid');
                $item['work'] =pdo_getcolumn(PDO_NAME.'dating_option',array('id'=>$optionid),'title');
            }else{
                $item['marital_status_text'] = $item['live_text'];
                $item['education_text'] = $item['travel_text'];
            }
        }
        //获取总页数
        if ($is_total == 1) {
            $totalSql      = str_replace($field,'count(*)',$sql);
            $total         = pdo_fetchcolumn($totalSql.$where);
            $data['total'] = ceil($total / $pageIndex);
            $data['list']  = $list;

            $this->renderSuccess('相亲会员信息列表',$data);
        }


        $this->renderSuccess('相亲会员信息列表',$list);
    }
    /**
     * Comment: 获取用户详细信息
     * Author: zzw
     * Date: 2021/3/8 15:30
     */
    public function getMemberInfo(){
        global $_W,$_GPC;
        //获取参数信息
        $id = $_GPC['id'] OR $this->renderError('当前会员不存在');//会员id
        $this->isPerfectInfo();//判断是否完善资料信息


        //详细信息获取
        $info = pdo_get(PDO_NAME."dating_member",['id'=>$id]);
        $info = Dating::handleMemberInfo($info);

        $info['university'] = Customized::init('university442') ? 1 : 0;

        //获取被收藏总数
        $info['collection'] = pdo_count(PDO_NAME."dating_record",['uniacid'=>$_W['uniacid'],'object_mid'=>$info['mid'],'type'=>1]);
        $info['is_collection'] = Dating::isCollectionOrBrowse($_W['mid'],$info['mid']);
        //判断当前用户是否为vip
        [$info['is_vip'],$numTime] = Dating::isVip($info['mid']);
        //判断是否交换联系方式
        $info['is_exchange'] = Dating::isExchange($_W['mid'],$info['mid']);
        //记录浏览历史  需要存在会员资料才会记录
        $isHave = pdo_get(PDO_NAME."dating_member",['mid'=>$_W['mid']]);
        if($isHave && $info['mid'] != $_W['mid']){
            $history = [
                'uniacid'          => $_W['uniacid'],
                'mid'              => $_W['mid'],
                'member_id'        => $isHave['id'],
                'object_mid'       => $info['mid'],
                'object_member_id' => $id,
                'type'             => 2
            ];
            $isHaveHistory = pdo_get(PDO_NAME."dating_record",$history);
            if($isHaveHistory){
                //已经存在浏览历史 修改时间
                pdo_update(PDO_NAME."dating_record",['create_time'=>time()],['id'=>$isHaveHistory['id']]);
            }else{
                //不存在浏览历史  添加浏览历史
                $history['create_time'] = time();
                pdo_insert(PDO_NAME."dating_record",$history);
            }
        }
        //判断信息隐藏、显示
        $info['switch_info']    = $info['is_open_base'];//是否显示基本信息:1=不显示,2=显示
        $info['switch_phone']    = $info['is_open_photo'];//是否显示照片信息:1=不显示,2=显示
        $info['switch_tel']    = $info['is_open_contact'];//是否显示联系方式信息:1=不显示,2=显示

        if($info['is_vip']) {
            $vipOpen = pdo_get(PDO_NAME."dating_member_open",['mid' => $_W['mid']]);//会员卡信息
            if (($vipOpen['type'] == 1 && $vipOpen['end_time'] > time())){
                $info['switch_info'] = 2;//是否显示基本信息:1=不显示,2=显示
                $info['switch_phone'] = 2;//是否显示基本信息:1=不显示,2=显示
                $info['switch_tel'] = 2;//是否显示基本信息:1=不显示,2=显示
            }
        }
        //判断次数卡和自己
        $seeflag = pdo_getcolumn(PDO_NAME.'dating_member_use',array('mid'=>$_W['mid'],'uniacid' => $_W['uniacid'],'see_id'=>$id),'id');
        if($seeflag > 0 || $_W['mid'] == $info['mid']){
            $info['switch_info'] = 2;//是否显示基本信息:1=不显示,2=显示
            $info['switch_phone'] = 2;//是否显示基本信息:1=不显示,2=显示
            $info['switch_tel'] = 2;//是否显示基本信息:1=不显示,2=显示
        }
        //删除不需要的内容
        unset($info['uniacid'],$info['aid'],$info['real_name'],$info['birth'],$info['marital_status'],
            $info['education'],$info['current_province'],$info['current_city'],$info['current_area'],$info['hometown_province'],
            $info['hometown_city'],$info['hometown_area'],$info['lng'],$info['lat'],$info['registered_residence_type'],
            $info['live'],$info['require_marital_status'],$info['require_education'],$info['label_id'],$info['photo'],
            $info['video'],$info['is_open_base'],$info['is_open_contact'],$info['is_open_photo'],$info['examine'],$info['reason'],
            $info['is_top'],$info['top_end_time'],$info['create_time'],$info['sort'],$info['matchmaker_id'],$info['pv'],$info['birth_text'],
            $info['registered_residence'],$info['create_time_text']);
        //浏览量增加
        pdo_fetchall("update ".tablename(PDO_NAME."dating_member")." set `pv` = (`pv` + 1) WHERE id = {$id}");

        //定制信息
        if($info['university'] > 0 ){
            $tags = pdo_getall('wlmerchant_dating_checkbox',array('did' => $id),array('optionid'));
            $info['tags'] = array_column($tags,'optionid');
            if(!empty($info['tags'])){
                foreach ($info['tags'] as $ta){
                    $new[] = pdo_getcolumn(PDO_NAME.'dating_option',array('id'=>$ta),'title');
                }
            }
            $info['tags'] = $new;
        }

        $this->renderSuccess('会员详细信息',$info);
    }
    /**
     * Comment: 用户申请查看某个用户的信息
     * Author: zzw
     * Date: 2021/3/30 10:12
     */
    public function seeMemberInfo(){
        global $_W,$_GPC;
        //参数信息获取
        $memberId = $_GPC['member_id'] or $this->renderError('不存在的会员信息！');
        $mid = $_W['mid'];
        $this->isPerfectInfo();//判断是否完善资料信息
        //获取当前用户的会员卡信息
        $info = pdo_get(PDO_NAME."dating_member_open",['mid'=>$mid]);
        if($info){
            //存在会员信息,进行处理  会员卡类型:1=时限卡,2=次数卡
            if($info['type'] == 1){
                //时限卡  每次都需要申请，在时限内可以一直查看
                if($info['end_time'] > time()) $this->renderSuccess('时限卡，可以查看',['status'=>1]);
                else $this->renderError('时限卡已过期，请重新开通!');
            }else if($info['type'] == 2){
                //次数卡  查看消耗次数，然后一直可以查看
                $use = pdo_count(PDO_NAME."dating_member_use",['mid'=>$mid,'uniacid'=>$_W['uniacid']]);
                $surplus = $info['frequency'] - $use;//剩余次数
                if($surplus > 0){
                    //次数卡可使用
                    $useData = [
                        'uniacid'     => $_W['uniacid'],
                        'mid'         => $mid,
                        'see_id'      => $memberId
                    ];
                    if(!pdo_get(PDO_NAME."dating_member_use",$useData)){
                        $useData['create_time'] = time();

                        pdo_insert(PDO_NAME."dating_member_use",$useData);
                    }
                    $this->renderSuccess('次数卡，可以查看',['status'=>1]);
                }else{
                    $this->renderError('次数卡已失效，请重新开通！');
                }
            }
        }else{
            //无开卡信息  不是会员
            $this->renderError('请先成为会员！');
        }
    }
    /**
     * Comment: 收藏&取消收藏
     * Author: zzw
     * Date: 2021/3/10 14:52
     */
    public function collectionOperation(){
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] OR $this->renderError('收藏失败，不存在的会员信息');
        $isCancel = $_GPC['is_cancel'] ? : 1;//是否默认取消：1=默认取消（已收藏再次收藏时进行取消操作）,2=不取消
        $this->isPerfectInfo();//判断是否完善资料信息
        //获取会员信息 并且判断当前用户是否存在会员信息
        $member = pdo_get(PDO_NAME."dating_member",['mid'=>$_W['mid']]);//当前用户会员信息
        $object = pdo_get(PDO_NAME."dating_member",['id'=>$id]);//被收藏会员信息

        //判断是否已经收藏  已收藏取消收藏，为收藏则进行收藏操作
        $data = [
            'mid'              => $_W['mid'],
            'member_id'        => $member['id'],
            'object_mid'       => $object['mid'],
            'object_member_id' => $id,
            'type'             => 1
        ];
        $isCollection = pdo_get(PDO_NAME."dating_record",$data);
        if($isCollection && $isCancel == 1){
            //已收藏  取消操作
            pdo_delete(PDO_NAME."dating_record",$data);
        }else if(!$isCollection){
            if($_W['mid'] == $object['mid']) $this->renderError('不能收藏自己哦！');
            //未收藏  进行收藏操作
            $data['uniacid'] = $_W['uniacid'];
            $data['create_time'] = time();
            pdo_insert(PDO_NAME."dating_record",$data);
        }

        $this->renderSuccess('操作成功');
    }
    /**
     * Comment: 动态发布/编辑
     * Author: zzw
     * Date: 2021/3/30 11:11
     */
    public function dynamicEdit(){
        global $_W,$_GPC;
        if(lockTool($_W['mid'],'dating')){
            $this->renderError('请稍后');
        }
        //参数信息获取
        $type = $_GPC['type'] ? : 'get';//get=获取数据,post=提交数据
        $id = $_GPC['id'] ? : '';
        $this->isPerfectInfo();//判断是否完善资料信息
        if($type == 'post') {
            //基本参数信息获取
            $data = json_decode(html_entity_decode($_GPC['data']),true);
            $set  = Setting::wlsetting_read('dating_set');
            //判断数据是否完整
            if(!$data['content'] && !$data['photo'] && !$data['video']) $this->renderError('动态内容不能为空！');
            //信息处理
            $data['photo'] = serialize($data['photo']);
            $data['status'] = $set['dynamic_examine'] == 1 ? 1 : 3;//状态:1=审核中,2=未通过,3=显示中    是否需要审核：1=需要审核，2=免审核
            //数据库字段判断
            $data = fieldJudge($data,'dating_dynamic');
            if($id){
                //修改数据
                $res = pdo_update(PDO_NAME."dating_dynamic",$data,['id'=>$id]);
                //模板消息
                $content = '您好，用户['.$_W['wlmember']['nickname'].']编辑了动态信息，需要重新进行审核，请尽快审核!';//业务内容

            }else{
                //添加数据
                $data['uniacid']       = $_W['uniacid'];
                $data['aid']           = $_W['aid'];
                $data['mid']           = $data['mid'] ? : $_W['mid'];
                $data['create_time']   = time();
                $data['is_fictitious'] = 1;//是否为虚拟动态:1=不是,2=是
                $res = pdo_insert(PDO_NAME."dating_dynamic",$data);
                //模板消息
                $content = '您好，用户['.$_W['wlmember']['nickname'].']发布了新的动态信息，请尽快审核!';//业务内容
            }
            //发布成功 并且需要审核，给代理商员工发送模板消息
            if($res && $data['status'] == 1){
                $first   = '有新的动态信息需要审核!';//消息头部
                $type    = "动态审核";//业务类型
                $status  = "待审核";//处理结果
                $remark  = "请尽快处理!";//备注信息
                $time    = $data['create_time'];//操作时间
                News::noticeAgent('dating_dynamic_examine',$_W['aid'],$first,$type,$content,$status,$remark,$time);
            }

            $this->renderSuccess('操作成功');
        }else{
            //存在id  获取动态信息
            $info = pdo_get(PDO_NAME."dating_dynamic",['id'=>$id]);
            if($info){
                //图片信息处理
                $info['photo'] = unserialize($info['photo']);
                foreach($info['photo'] as $img){
                    $info['photo_show'][] = tomedia($img);
                }
                //删除多余的信息
                unset($info['uniacid'],$info['aid'],$info['mid'],$info['create_time'],$info['pv'],$info['is_fictitious'],$info['fictitious_nickname'],$info['fictitious_avatar']);
            }

            $this->renderSuccess('动态信息',$info);
        }
    }
    /**
     * Comment: 我的动态列表
     * Author: zzw
     * Date: 2021/3/30 11:18
     */
    public function myDynamicList(){
        global $_W,$_GPC;
        //参数信息获取
        $page      = $_GPC['page'] ? : 1;//当前页
        $pageIndex = $_GPC['page_index'] ? : 10;//每页的数量
        $pageStart = $page * $pageIndex - $pageIndex;
        $status    = $_GPC['status'] ? : 0;//状态:0=全部,1=审核中,2=未通过,3=显示中
        //基本sql语句生成
        $field = "a.id,a.mid,a.content,a.photo,a.video,a.create_time,a.gender,
        CASE WHEN a.pv IS NULL THEN 0
             ELSE a.pv
        END as pv,a.status";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."dating_dynamic")
            ." as a LEFT JOIN ".tablename(PDO_NAME."dating_member_open")
            ." as b ON a.mid = b.mid";
        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.mid = {$_W['mid']} ";
        if($status > 0) $where .= " AND a.status = {$status} ";
        //排序方式  1=最新,2=热门,3=附近,4=VIP专区(时间排序)
        $order = " ORDER BY a.create_time DESC,a.id DESC ";
        //列表信息获取
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as &$item){
            //基础信息处理
            $item = Dating::handleDynamicInfo($item);
            //删除不需要的内容
            unset($item['mid'],$item['photo'],$item['video'],$item['gender'],$item['create_time']);
        }
        //获取总页数
        $totalSql      = str_replace($field,'count(*)',$sql);
        $total         = pdo_fetchcolumn($totalSql.$where);
        //信息拼接
        $data = [
            'total' => ceil($total / $pageIndex),
            'list'  => $list
        ];

        $this->renderSuccess('动态信息列表',$data);
    }
    /**
     * Comment: 删除我的动态
     * Author: zzw
     * Date: 2021/3/30 11:24
     */
    public function delMyDynamic(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR $this->renderError('不存在的动态信息!');
        //删除操作
        $res = pdo_delete(PDO_NAME."dating_dynamic",['id'=>$id]);
        if($res) $this->renderSuccess('删除成功');
        else $this->renderError('删除失败，请刷新重试!');
    }
    /**
     * Comment: 动态信息列表
     * Author: zzw
     * Date: 2021/3/8 17:31
     */
    public function dynamicList(){
        global $_W,$_GPC;
        //参数信息获取
        $page      = $_GPC['page'] ? : 1;//当前页
        $pageIndex = $_GPC['page_index'] ? : 10;//每页的数量
        $pageStart = $page * $pageIndex - $pageIndex;
        $sort      = $_GPC['sort'] ? : 1;//排序方式：1=最新,2=热门,3=附近,4=VIP专区(时间排序)
        $lng       = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度  104.0091133118
        $lat       = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度  30.5681964123
        $memberId  = $_GPC['member_id'] ? :0;//会员id
        $isGetVip  = $_GPC['is_vip'] ? : 0;//是否获取vip信息，0=全部，1=非vip，2=vip信息
        //获取VIP专区内容 判断当前用户是否为VIP
        if($sort == 4){
            [$vip,$is_vip] = Dating::getVipInfo($_W['mid']);
            if($is_vip == 0) $this->renderError('您不是会员，无查看VIP专区的权限，请先开通VIP',['dating_is_vip'=>1]);
        }
        //基本sql语句生成
        $time = time();
        $isVip = "CASE 
                    WHEN b.id > 0 THEN
                        CASE WHEN b.type = 1 AND b.end_time > {$time} THEN 1
                             WHEN b.type = 2 AND (b.frequency - (SELECT COUNT(*) FROM".tablename(PDO_NAME.'dating_member_use')." WHERE mid = a.mid)) > 0 THEN 1
                             ELSE 0
                        END
                    ELSE 0
                  END";
        $distances = "ROUND(CASE 
                            WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.138 * 2 * ASIN(
                                    SQRT(
                                        POW(SIN(({$lat} * PI() / 180 - a.lat * PI() / 180) / 2),2) +
                                            COS({$lat} * PI() / 180) * COS(a.lat * PI() / 180) *
                                            POW(SIN(({$lng} * PI() / 180 - a.lng * PI() / 180) / 2),2)
                                        )
                                    ) * 1000
                                ) 
                            ELSE ''
                        END)";
        $field = "$isVip as is_vip,{$distances} as distances,a.id,a.mid,a.content,a.photo,a.video,a.create_time,a.gender,
        CASE WHEN a.pv IS NULL THEN 0
             ELSE a.pv
        END as pv,a.is_fictitious,a.fictitious_nickname,a.fictitious_avatar,a.address";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."dating_dynamic")
            ." as a LEFT JOIN ".tablename(PDO_NAME."dating_member_open")
            ." as b ON a.mid = b.mid";
        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} AND a.status = 3 ";
        if($isGetVip == 2) $where .= " AND $isVip = 1 ";
        else if($isGetVip == 1) $where .= " AND $isVip = 0 ";
        if($memberId > 0) {
            $mid = pdo_getcolumn(PDO_NAME."dating_member",['id'=>$memberId],'mid');
            $where .= " AND a.mid = {$mid} ";
        }
        //排序方式  1=最新,2=热门,3=附近,4=VIP专区(时间排序)
        switch ($sort){
            case 1: $order = " ORDER BY a.create_time DESC,a.id DESC ";break;//时间排序
            case 2: $order = " ORDER BY a.pv DESC,a.id DESC ";break;//浏览量
            case 3: $order = " ORDER BY distances ASC,a.id DESC ";break;//距离排序
            case 4: $order = " ORDER BY a.create_time DESC,a.id DESC ";break;
        }
        //列表信息获取
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as &$item){
            //基础信息处理
            $item = Dating::handleDynamicInfo($item);
            //当前用户是否点赞
            $item['is_fabulous'] = 0;//是否点赞：0=未点赞，1=已点赞
            if($_W['mid']){
                $isFabulous = pdo_get(PDO_NAME."dating_dynamic_fabulous",['mid'=>$_W['mid'],'dynamic_id'=>$item['id']]);
                if($isFabulous) $item['is_fabulous'] = 1;
            }
            //删除不需要的内容
            unset($item['mid'],$item['photo'],$item['video'],$item['create_time'],$item['is_fictitious']
                ,$item['fictitious_nickname'],$item['fictitious_avatar'],$item['distances'],$item['gender']);
        }
        //获取总页数
        $totalSql      = str_replace($field,'count(*)',$sql);
        $total         = pdo_fetchcolumn($totalSql.$where);
        //信息拼接
        $data = [
            'total' => ceil($total / $pageIndex),
            'list'  => $list
        ];

        $this->renderSuccess('动态信息列表',$data);
    }
    /**
     * Comment: 获取动态详情
     * Author: zzw
     * Date: 2021/3/9 9:32
     */
    public function dynamicDesc(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] or $this->renderError('不存在的动态信息');
        $lng = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度  104.0091133118
        $lat = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度  30.5681964123
        //信息获取
        $time = time();
        $isVip = "CASE 
                    WHEN b.id > 0 THEN
                        CASE WHEN b.type = 1 AND b.end_time > {$time} THEN 1
                             WHEN b.type = 2 AND (b.frequency - (SELECT COUNT(*) FROM".tablename(PDO_NAME.'dating_member_use')." WHERE mid = a.mid)) > 0 THEN 1
                             ELSE 0
                        END
                    ELSE 0
                  END";
        $distances = "ROUND(CASE 
                            WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.138 * 2 * ASIN(
                                    SQRT(
                                        POW(SIN(({$lat} * PI() / 180 - a.lat * PI() / 180) / 2),2) +
                                            COS({$lat} * PI() / 180) * COS(a.lat * PI() / 180) *
                                            POW(SIN(({$lng} * PI() / 180 - a.lng * PI() / 180) / 2),2)
                                        )
                                    ) * 1000
                                ) 
                            ELSE ''
                        END)";
        $field = "$isVip as is_vip,{$distances} as distances,a.id,a.mid,a.content,a.photo,a.video,a.create_time,a.pv
        ,a.is_fictitious,a.fictitious_nickname,a.fictitious_avatar,a.address";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."dating_dynamic")
            ." as a LEFT JOIN ".tablename(PDO_NAME."dating_member_open")
            ." as b ON a.mid = b.mid WHERE a.id = {$id}";
        $info = pdo_fetch($sql);
        $info = Dating::handleDynamicInfo($info);
        //当前用户是否点赞
        $isFabulous = pdo_get(PDO_NAME."dating_dynamic_fabulous",['dynamic_id'=>$info['id'],'mid'=>$_W['mid']]);
        if($isFabulous) $info['is_fabulous'] = 1;
        else  $info['is_fabulous'] = 0;
        //删除不需要的内容
        unset($info['mid'],$info['photo'],$info['video'],$info['create_time'],$info['is_fictitious']
            ,$info['fictitious_nickname'],$info['fictitious_avatar'],$info['distances']);
        //增加人气
        pdo_fetchall("update ".tablename(PDO_NAME."dating_dynamic")." set `pv` = (`pv` + 1) WHERE id = {$id}");

        $this->renderSuccess('动态详情',$info);
    }
    /**
     * Comment: 动态评论列表
     * Author: zzw
     * Date: 2021/3/9 11:28
     */
    public function dynamicComment(){
        global $_W,$_GPC;
        //信息获取
        $dynamicId = $_GPC['dynamic_id'] OR $this->renderSuccess('动态id错误',[]);//不存在动态id返回空数组，不报错
        $page      = $_GPC['page'] ? : 1;//当前页
        $pageIndex = $_GPC['page_index'] ? : 3;//每页的数量
        $pageStart = $page * $pageIndex - $pageIndex;
        //sql语句生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND (a.status = 3 OR a.mid = {$_W['mid']}) AND a.dynamic_id = {$dynamicId} ";
        $group = " GROUP BY a.id ";
        $order = " ORDER BY fabulous DESC,a.id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $field = "a.id,a.mid,a.content,a.create_time,a.reply_id,COUNT(b.id) as fabulous";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."dating_dynamic_comment")
            ." as a LEFT JOIN ".tablename(PDO_NAME."dating_comment_fabulous")
            ." as b ON a.id = b.comment_id ";
        $list = pdo_fetchall($sql.$where.$group.$order.$limit);
        foreach($list as &$item){
            //获取用户信息
            [$item['nickname'],$item['avatar']] = Dating::handleUserInfo($item['mid']);
            //获取当前用户的性别
            $datingmember = pdo_get(PDO_NAME."dating_member",['mid'=>$item['mid']],['gneder','id']);
            $item['gneder'] = $datingmember['gneder'];
            $item['datingid'] = $datingmember['id'];
            //判断当前用户是否点赞
            $isFabulous = pdo_get(PDO_NAME."dating_comment_fabulous",['comment_id'=>$item['id'],'mid'=>$_W['mid']]);
            if($isFabulous) $item['is_fabulous'] = 1;
            else $item['is_fabulous'] = 0;
            //处理时间信息
            $item['create_time'] = date("m-d H:i",$item['create_time']);
            //判断当前评论是否为回复信息  是则获取被回复用户昵称
            if($item['reply_id'] > 0){
                $replyMid = pdo_getcolumn(PDO_NAME."dating_dynamic_comment",['id'=>$item['reply_id']],'mid');
                [$item['reply_name'],$avatar] = Dating::handleUserInfo($replyMid);
            }
            //当前评论的回复评论总数
            $replyWhere = " WHERE reply_id = {$item['id']} AND dynamic_id = {$dynamicId} AND (status = 3 OR mid = {$_W['mid']}) ";
            $countSql = " SELECT count(*) FROM ".tablename(PDO_NAME."dating_dynamic_comment").$replyWhere;
            $item['reply_total'] = pdo_fetchcolumn($countSql);
            //获取当前评论的所有回复信息
            $listSql = " SELECT id,mid,content FROM ".tablename(PDO_NAME."dating_dynamic_comment").$replyWhere." ORDER BY id DESC ";
            $item['list'] = pdo_fetchall($listSql);
            foreach($item['list'] as &$val){
                [$val['nickname'],$avatar] = Dating::handleUserInfo($val['mid']);
                unset($val['mid']);
            }
        }
        //获取总页数
        $totalSql      = str_replace($field,'count(*)',$sql);
        $total         = pdo_fetchcolumn($totalSql.$where);
        $data['total'] = ceil($total / $pageIndex);
        $data['list']  = $list;

        $this->renderSuccess('动态评论',$data);
    }
    /**
     * Comment: 动态点赞|取消点赞
     * Author: zzw
     * Date: 2021/3/9 13:45
     */
    public function dynamicFabulous(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR $this->renderError('操作失败，不存在的动态信息');
        $this->isPerfectInfo();//判断是否完善资料信息
        //判断是否存在点赞信息  存在则取消点赞，不存在则进行点赞操作
        $data = [
            'mid'        => $_W['mid'],
            'dynamic_id' => $id
        ];
        $isHave = pdo_get(PDO_NAME.'dating_dynamic_fabulous',$data);
        if($isHave) {
            //取消点赞
            pdo_delete(PDO_NAME.'dating_dynamic_fabulous',$data);
        } else {
            //点赞操作
            $data['create_time'] = time();
            pdo_insert(PDO_NAME.'dating_dynamic_fabulous',$data);
        }

        $this->renderSuccess('操作成功');
    }
    /**
     * Comment: 动态评论&评论回复
     * Author: zzw
     * Date: 2021/3/9 10:34
     */
    public function addComment(){
        global $_W,$_GPC;
        //参数信息获取
        $content = $_GPC['content'] OR $this->renderError('请输入评论内容');
        $dynamicId = $_GPC['dynamic_id'] OR $this->renderError('评论失败，请刷新重试');
        $replyId = $_GPC['reply_id'] ? : 0;//回复评论的id
        $set = Setting::wlsetting_read('dating_set');
        $this->isPerfectInfo();//判断是否完善资料信息
        //判断文本内容是否非法
        $textRes = Filter::init($content,$_W['source'],1);
        if($textRes['errno'] == 0) $this->renderError($textRes['message']);
        //信息拼接
        $data = [
            'uniacid'     => $_W['uniacid'],
            'aid'         => $_W['aid'],
            'mid'         => $_W['mid'],
            'content'     => $content,
            'create_time' => time(),
            'reply_id'    => $replyId,
            'dynamic_id'  => $dynamicId,
            //审核状态:1=待审核,2=未通过,3=已通过
            'status'      => $set['comment_examine'] == 1 ? 1 : 3,//是否需要审核：1=需要审核，2=不用审核
            //source  1=公众号（默认）；2=h5；3=小程序
            'source'      => $_W['source'],
        ];
        $res  = pdo_insert(PDO_NAME."dating_dynamic_comment",$data);
        if($res && $data['status'] == 3){
            //不用审核 直接发送模板消息通知，需要则后台审核通过后发送
            Dating::sendCommentModel($_W['mid'],$replyId,$dynamicId,$content,$_W['source']);
        }

        $this->renderSuccess('评论成功');
    }
    /**
     * Comment: 评论点赞|取消点赞
     * Author: zzw
     * Date: 2021/3/9 13:49
     */
    public function commentFabulous(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR $this->renderError('操作失败，不存在的评论信息');
        $this->isPerfectInfo();//判断是否完善资料信息
        //判断是否存在点赞信息  存在则取消点赞，不存在则进行点赞操作
        $data = [
            'mid'        => $_W['mid'],
            'comment_id' => $id
        ];
        $isHave = pdo_get(PDO_NAME.'dating_comment_fabulous',$data);
        if($isHave) pdo_delete(PDO_NAME.'dating_comment_fabulous',$data);//取消点赞
        else pdo_insert(PDO_NAME.'dating_comment_fabulous',$data);//点赞操作


        $this->renderSuccess('操作成功');
    }
    /**
     * Comment: 会员信息编辑
     * Author: zzw
     * Date: 2021/3/10 9:33
     */
    public function editMember(){
        global $_W,$_GPC;
        if(lockTool($_W['mid'],'dating')){
            $this->renderError('请稍后');
        }
        //参数信息获取
        $type = $_GPC['type'] ? : 'get';//get=获取数据,post=提交数据
        $id = $_GPC['id'] ? : '';
        $headId = $_GPC['dating_head_id'] ? : 0;
        $set  = Setting::wlsetting_read('dating_set');
        $university = Customized::init('university442') ? 1 : 0;
        $dotype = $_GPC['dotype']; // 0用户自填资料  1红娘上传资料

        if($type == 'post') {
            //基本参数信息获取
            $data = json_decode(html_entity_decode($_GPC['data']),true);
            //判断数据是否完整
            if (!$data['cover']) $this->renderError('请上传封面图片');
            if (!$data['realname']) $this->renderError('请输入姓名');
            if (!$data['height']) $this->renderError('请输入身高信息');
            if (!$data['weight']) $this->renderError('请输入体重信息');
            if (!$data['nation']) $this->renderError('请输入民族信息');
            if(empty($university)){
                if (!$data['work']) $this->renderError('请输入工作职务');
                if (!$data['income']) $this->renderError('请输入月收入信息');
                if ($data['travel'] == 2 && !$data['vehicle']) $this->renderError('请输入车型号');
            }
            if (!$data['birth']) $this->renderError('请选择出生日期');
            if (!$data['address'] || !$data['lat'] || !$data['lng']) $this->renderError('请选择详细地址');
            //判断是否绑定手机
            $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
            if (empty($_W['wlmember']['mobile']) && in_array('dating',$mastmobile)){
                $this->renderError('未绑定手机号');
            }
            if(empty($dotype)){
            	//修改真实姓名
           		$res2 = pdo_update('wlmerchant_member',array('realname' => $data['realname']),array('id' => $data['mid']));
            }
            //信息处理
            $data['photo']    = serialize($data['photo']);
            $data['label_id'] = implode($data['label_id'],',');
            $data['examine']  = $set['member_examine'] == 1 ? 1 : 3;//是否需要审核 1=需要审核，2=免审核
            $data['aid']      = $_W['aid'];
            //获取用户id
            if($headId > 0){
                $matchmakerinfo = pdo_get(PDO_NAME."dating_matchmaker",['mid'=>$headId,'mid <>'=>$_W['mid'],'uniacid'=>$_W['uniacid']],['id','nickname']);
                $data['matchmaker_id'] = $matchmakerinfo['id'] > 0 ? $matchmakerinfo['id'] : 0;
            }
            //隐私信息处理
            if($set['member_privacy'] == 1){
                $data['is_open_base'] = 2;
            }else if($set['member_privacy'] == 2){
                $data['is_open_base'] = 1;
            }
            if($set['member_photo'] == 1){
                $data['is_open_photo'] = 2;
            }else if($set['member_photo'] == 2){
                $data['is_open_photo'] = 1;
            }
            if($set['member_phone'] == 1){
                $data['is_open_contact'] = 2;
            }else if($set['member_phone'] == 2){
                $data['is_open_contact'] = 1;
            }
			$realname = $data['realname'];
            $newtags = $data['tags'];
            //数据库字段判断
            $data = fieldJudge($data,'dating_member');
            //通过mid判断用户信息是否存在
            if($id > 0){
            	$isHave = 1;
            }else{
                if($dotype > 0){
                    $isHave = 0;
                }else{
                    $isHave = pdo_get(PDO_NAME."dating_member",['mid'=>$_W['mid']]);
                }
            }
            if($isHave){
                //修改数据
                unset($data['matchmaker_id']);//编辑时删除红娘id
                if($id > 0){
                	$did = $id;
                	$res1 = pdo_update(PDO_NAME."dating_member",$data,['id'=>$id]);
                	//模板消息
                	$content = '您好，红娘['.$matchmakerinfo['nickname'].']修改了相亲会员信息，需要重新进行审核，请尽快审核!';//业务内容
                }else{
                	$did = $isHave['id'];
                	$res1 = pdo_update(PDO_NAME."dating_member",$data,['mid'=>$_W['mid']]);
                	//模板消息
                	$content = '您好，用户['.$_W['wlmember']['nickname'].']编辑了个人信息，需要重新进行审核，请尽快审核!';//业务内容
                }
                
                
            }else{
                //添加数据
                if($dotype > 0){
                	$data['mid'] = 0;
                	$data['falsereal'] = $realname;
                	$data['matchmaker_id'] = pdo_getcolumn(PDO_NAME.'dating_matchmaker',array('mid'=>$_W['mid']),'id');
                	//模板消息
                	$content = '您好，红娘['.$matchmakerinfo['nickname'].']上传了相亲会员信息，请尽快进行审核!';//业务内容
                }else{
                	$data['mid'] = $data['mid'] ? : $_W['mid'];
                	//模板消息
                	$content = '您好，用户['.$_W['wlmember']['nickname'].']编辑了个人信息，请尽快进行审核!';//业务内容
                }
                $data['uniacid']       = $_W['uniacid'];
                $data['create_time']   = time();
                $data['sort']          = pdo_count(PDO_NAME."dating_member",['uniacid' => $_W['uniacid']]);
                $data['matchmaker_id'] = $data['matchmaker_id'] ? : 0;
                $res1 = pdo_insert(PDO_NAME."dating_member",$data);
                $did = pdo_insertid();
                
            }
            if($res1 || $res2){
                $res = 1;
            }
            //定制信息处理
            pdo_delete('wlmerchant_dating_checkbox',array('did'=>$did));
            if(!empty($newtags)){
                foreach ($newtags as $new){
                    pdo_insert(PDO_NAME . 'dating_checkbox', ['did' => $did,'optionid' => $new]);
                }
            }
            //需要审核，给代理商员工发送模板消息
            if($res && $data['examine'] == 1){
                $first   = '有用户需要审核!';//消息头部
                $type    = "用户审核";//业务类型
                $status  = "待审核";//处理结果
                $remark  = "请尽快处理!";//备注信息
                $time    = $data['create_time'] ? : time();//操作时间
                News::noticeAgent('dating_member_examine',$_W['aid'],$first,$type,$content,$status,$remark,$time);
                $this->renderSuccess('提交成功,请等待系统审核资料。');
            }else{
                $this->renderSuccess('提交成功');
            }
        }else{
            //基础信息配置
            $data = [
                'label' => pdo_getall(PDO_NAME."dating_label",['uniacid'=>$_W['uniacid']],['id','title'],'','sort DESC'),
                'info'  => [],
                'disclaimers' => $set['disclaimers'] ? : '',
                'hidePrivacy' => $set['member_privacy'] > 0 ? 1 : 0,
                'hidePhoto' => $set['member_photo'] > 0 ? 1 : 0,
                'hidePhone' => $set['member_phone'] > 0 ? 1 : 0,
            ];
            //存在id  获取用户的会员信息
            if($id > 0){
            	$info = pdo_get(PDO_NAME."dating_member",['id'=>$id]);
            }else if(empty($dotype)){
            	$info = pdo_get(PDO_NAME."dating_member",['mid'=>$_W['mid']]);
            }else{
            	$info = [];
            }
            if($info){
                //信息处理
                $info['photo'] = unserialize($info['photo']);
                $info['label_id'] = explode(',',$info['label_id']);
                if($info['mid'] > 0){
                	$info['realname'] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$info['mid']),'realname');
                }else{
                	$info['realname'] = $info['falsereal'];
                }
                foreach($info['photo'] as $img){
                    $info['photo_show'][] = tomedia($img);
                }
                //用户头像
                $info['show_avatar'] = tomedia($info['avatar']);
                $info['show_cover'] = tomedia($info['cover']);
                $info['falseavatar'] = tomedia($info['falseavatar']);
                //删除多余的信息
                unset($info['uniacid'],$info['aid'],$info['is_top'],$info['top_end_time'],$info['create_time']
                    ,$info['sort'],$info['pv'],$info['matchmaker_id']);

                $tags = pdo_getall('wlmerchant_dating_checkbox',array('did' => $info['id']),array('optionid'));
                $info['tags'] = array_column($tags,'optionid');

                $data['info'] = $info;
            }

            $data['marital_status'] = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 1),array('id','title'));
            $data['education'] = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 2),array('id','title'));
            $data['registered_residence_type'] = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 3),array('id','title'));
            $data['live'] = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 4),array('id','title'));
            $data['travel'] = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 5),array('id','title'));
            $data['university'] = $university;

            $this->renderSuccess('会员信息',$data);
        }
    }
    /**
     * Comment: 获取个人中心信息
     * Author: zzw
     * Date: 2021/3/10 11:18
     */
    public function memberCenter(){
        global $_W,$_GPC;
        //个人基本信息获取
        $info = pdo_get(PDO_NAME."dating_member",['mid'=>$_W['mid']]);
        $data = [];
        if($info){
            //信息存在
            [$data['nickname'],$data['avatar']] = Dating::handleUserInfo($_W['mid']);
            //获取会员详细信息
            [$data['vip'],$data['is_vip']] = Dating::getVipInfo($_W['mid']);
            //获取消息数量
            $data['im'] = pdo_count(PDO_NAME."im",['receive_id'=>$_W['mid'],'receive_type'=>1,'is_read'=>0]);
            //获取其他信息
            $tableName = PDO_NAME."dating_record";
            $data['my_collection'] = pdo_count($tableName,['uniacid'=>$_W['uniacid'],'mid'=>$_W['mid'],'member_id'=>$info['id'],'type'=>1]);//我的收藏
            $data['collection_my'] = pdo_count($tableName,['uniacid'=>$_W['uniacid'],'object_mid'=>$_W['mid'],'object_member_id'=>$info['id'],'type'=>1]);//收藏我的
            $data['my_browse'] = pdo_count($tableName,['uniacid'=>$_W['uniacid'],'mid'=>$_W['mid'],'member_id'=>$info['id'],'type'=>2]);//我的浏览
            $data['browse_my'] = pdo_count($tableName,['uniacid'=>$_W['uniacid'],'object_mid'=>$_W['mid'],'object_member_id'=>$info['id'],'type'=>2]);//浏览我的
            //置顶信息
            $data['is_top'] = $info['is_top'];
            $data['top_end_time'] = date("Y-m-d H:i",$info['top_end_time']);
            //审核信息
            $data['examine'] = $info['examine'];
            $data['reason'] = $info['reason'];
        }else{
            //信息不存在  使用默认信息
            $data = [
                'is_vip'        => 0,
                'vip'           => [],
                'im'            => pdo_count(PDO_NAME."im",['receive_id'=> $_W['mid'],'receive_type' => 1,'is_read'=>0]),
                'my_collection' => 0,
                'collection_my' => 0,
                'my_browse'     => 0,
                'browse_my'     => 0,
                'is_top'        => 1,//是否置顶:1=未置顶,2=置顶中
                'top_end_time'  => 0,
                'examine'       => 0,
                'reason'        => ''
            ];
            [$data['nickname'],$data['avatar']] = Dating::handleUserInfo($_W['mid']);
        }
        //获取置顶设置列表
        $set = Setting::wlsetting_read('dating_set');
        $data['top_rule'] = $set['top_rule'];
        //资料是否完善
        $memberId = pdo_getcolumn(PDO_NAME."dating_member",['mid'=>$_W['mid']],'id');
        $data['member_id'] = $memberId > 0 ? $memberId : 0;//0=未完善，1=已完善
        //定制内容
        $data['university'] = Customized::init('university442') ? 1 : 0;


        $this->renderSuccess('个人中心信息',$data);
    }
    /**
     * Comment: 获取记录列表(浏览记录|收藏记录)
     * Author: zzw
     * Date: 2021/3/10 14:11
     */
    public function recordList(){
        global $_W,$_GPC;
        //参数获取
        $page      = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $type      = $_GPC['type'] ? : 1;//记录类型：1=我的收藏，2=收藏我的，3=我的浏览，4=浏览我的
        $lng       = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度  104.0091133118
        $lat       = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度  30.5681964123
        $this->isPerfectInfo();//判断是否完善资料信息
        //特殊内容查询
        $distances = "ROUND(CASE 
                            WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.138 * 2 * ASIN(
                                    SQRT(
                                        POW(SIN(({$lat} * PI() / 180 - lat * PI() / 180) / 2),2) +
                                            COS({$lat} * PI() / 180) * COS(lat * PI() / 180) *
                                            POW(SIN(({$lng} * PI() / 180 - lng * PI() / 180) / 2),2)
                                        )
                                    ) * 1000
                                ) 
                            ELSE ''
                        END)";
        $area = "CASE WHEN current_area > 0 THEN current_area
                    WHEN current_city > 0 THEN current_city
                    ELSE current_province
                 END";
        $age = "CASE WHEN (FROM_UNIXTIME(unix_timestamp(now()) ,'%m') - FROM_UNIXTIME(birth,'%m')) < 0 AND (FROM_UNIXTIME(unix_timestamp(now()) ,'%d') - FROM_UNIXTIME(birth,'%d')) < 0 
                        THEN (TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(birth,'%Y-%m-%d'), CURDATE()) - 1)
                     ELSE TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(birth,'%Y-%m-%d'), CURDATE())
                END";
        //生成基本查询条件  记录类型：1=我的收藏，2=收藏我的，3=我的浏览，4=浏览我的
        $member = pdo_get(PDO_NAME."dating_member",['mid'=>$_W['mid']]);
        switch ($type) {
            case 1:
                //我的收藏
                $where = ['uniacid' => $_W['uniacid'],'mid' => $_W['mid'],'member_id' => $member['id'],'type' => 1];
                $total = pdo_count(PDO_NAME."dating_record",$where);
                $list = pdo_getall(PDO_NAME."dating_record",$where,'','','create_time DESC',[$page,$pageIndex]);
                break;
            case 2:
                //收藏我的
                $where = [
                    'uniacid'          => $_W['uniacid'],
                    'object_mid'       => $_W['mid'],
                    'object_member_id' => $member['id'],
                    'type'             => 1
                ];
                $total = pdo_count(PDO_NAME."dating_record",$where);
                $list = pdo_getall(PDO_NAME."dating_record",$where,'','','create_time DESC',[$page,$pageIndex]);
                break;
            case 3:
                //我的浏览
                $where = ['uniacid' => $_W['uniacid'],'mid' => $_W['mid'],'member_id' => $member['id'],'type' => 2];
                $total = pdo_count(PDO_NAME."dating_record",$where);
                $list = pdo_getall(PDO_NAME."dating_record",$where,'','','create_time DESC',[$page,$pageIndex]);
                break;
            case 4:
                //浏览我的
                $where = [
                    'uniacid'          => $_W['uniacid'],
                    'object_mid'       => $_W['mid'],
                    'object_member_id' => $member['id'],
                    'type'             => 2
                ];
                $total = pdo_count(PDO_NAME."dating_record",$where);
                $list = pdo_getall(PDO_NAME."dating_record",$where,'','','create_time DESC',[$page,$pageIndex]);
                break;
        }
        //sql语句生成
        $field = "{$age} as age,{$distances} as distances,{$area} AS area_id,id,is_top,mid,cover,gneder,birth,live,travel,pv";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."dating_member");
        //列表信息获取
        foreach ($list as &$item) {
            //记录类型：1=我的收藏(查询object_member_id)，2=收藏我的(查询member_id)，3=我的浏览(查询object_member_id)，4=浏览我的(查询member_id)
            if($type == 1 || $type == 3) $item = pdo_fetch($sql." WHERE id = {$item['object_member_id']}");
            else $item = pdo_fetch($sql." WHERE id = {$item['member_id']}");
            //信息处理
            $item = Dating::handleMemberInfo($item);
            //最小一级区域信息查询
            $item['area'] = pdo_getcolumn(PDO_NAME."area",['id' => $item['area_id']],'name');
            $item['cover'] = tomedia($item['cover']);
            //删除多余的信息
            unset($item['birth'],$item['area_id'],$item['distances'],$item['mid']);
        }
        //获取总页数
        $data['total'] = ceil($total / $pageIndex);
        $data['list']  = $list;

        $this->renderSuccess('记录信息表',$data);
    }
    /**
     * Comment: 红娘信息编辑
     * Author: zzw
     * Date: 2021/3/10 17:15
     */
    public function applyMatchmaker(){
        global $_W,$_GPC;
        //检查锁
        if(lockTool($_W['mid'],'dating')){
            $this->renderError('请稍后');
        }
        //参数信息获取
        $type = $_GPC['type'] ? : 'get';//get=获取数据,post=提交数据
        $id = $_GPC['id'] ? : '';
        $set  = Setting::wlsetting_read('dating_set');
        //根据设置判断状态
        if($set['pay_settle'] == 1) $status = 1;//付费入驻  待付款
        else if($set['matchmaker_examine'] == 1) $status = 2;//免费入驻,但是需要审核   待审核
        else $status = 3;//免费入驻,并且不用审核审核    已通过
        //进行操作类型进行对应的操作
        if($type == 'post') {
            //基本参数信息获取
            $data = json_decode(html_entity_decode($_GPC['data']),true);
            //判断信息是否完善
            if(!$data['nickname']) $this->renderError('请输入红娘昵称');
            if(!$data['avatar']) $this->renderError('请上传头像');
            if(!$data['phone']) $this->renderError('请输入联系方式');
            if(!$data['qrcode']) $this->renderError('请上传微信或QQ二维码');
            $data['status'] = $status;
            $data = fieldJudge($data,'dating_matchmaker');//判断数据库字段
            //判断是添加还是编辑操作
            if($id){
                if($data['status'] == 1){
                    //需要付费时判断当前状态
                    $newStatus = pdo_getcolumn(PDO_NAME."dating_matchmaker",['id'=>$id],'status');
                    if($newStatus != 1){
                        //已付费成功  判断是否需要审核
                        if($set['matchmaker_examine'] == 1) $data['status'] = 2;//免费入驻,但是需要审核   待审核
                        else $data['status'] = 3;//免费入驻,并且不用审核审核    已通过
                    }
                }
                //修改数据
                $res = pdo_update(PDO_NAME."dating_matchmaker",$data,['id'=>$id]);
                //模板消息
                $first = '有红娘编辑了个人信息，需要重新审核!';//消息头部
                $content = '您好，用户['.$_W['wlmember']['nickname'].']编辑了红娘信息，需要重新进行审核，请尽快进行审核!';//业务内容
            }else{
                //添加数据
                $data['uniacid']       = $_W['uniacid'];
                $data['mid']           = $_W['mid'];
                $data['create_time']   = time();
                $data['create_source'] = 1;
                $res = pdo_insert(PDO_NAME."dating_matchmaker",$data);
                //判断是否需要付费  进行对应的操作
                if($res && $status == 1 && $set['money'] > 0){
                    $id = pdo_insertid();
                    //需要付费
                    $orderData = [
                        'uniacid'     => $data['uniacid'],
                        'mid'         => $_W['mid'],
                        'aid'         => $data['aid'],
                        'fkid'        => $id,//红娘id
                        'createtime'  => time(),
                        'orderno'     => createUniontid(),
                        'price'       => $set['money'],
                        'num'         => 1,
                        'plugin'      => 'dating',
                        'payfor'      => 'datingMatchmaker',
                        'goodsprice'  => $set['money'],
                        'fightstatus' => 1,
                    ];
                    pdo_insert(PDO_NAME.'order',$orderData);
                    $orderId = pdo_insertid();
                    if (empty($orderId)) $this->renderError('生成订单失败，请刷新重试');
                    else $this->renderSuccess('申请成功',['status' => 1,'type' => 'dating','orderid' => $orderId]);
                }
                //模板消息
                $first = '有新的红娘需要进行审核!';//消息头部
                $content = '您好，用户['.$_W['wlmember']['nickname'].']申请成为红娘，请尽快进行审核!';//业务内容
            }
            //需要审核，给总后台员工发送模板消息
            if($res && $status == 2){
                $type    = "红娘审核";//业务类型
                $status  = "待审核";//处理结果
                $remark  = "请尽快处理!";//备注信息
                $time    = $data['create_time'];//操作时间

                News::noticeAgent('dating_matchmaker_examine',-1,$first,$type,$content,$status,$remark,$time);
            }

            $this->renderSuccess('操作成功');
        }else{
            //获取用户的会员信息
            $info = pdo_get(PDO_NAME."dating_matchmaker",['mid' => $_W['mid']],[
                'id',
                'nickname',
                'avatar',
                'phone',
                'wechat_number',
                'qq_unmber',
                'describe',
                'qrcode',
                'reason',
                'status'
            ]);
            if($info){
                //信息存在 进行处理
                $info['avatar_show'] = tomedia($info['avatar']);
                $info['qrcode_show'] = tomedia($info['qrcode']);
                //判断状态等于待付款 获取订单信息
                if($info['status'] == 1){
                    $order = pdo_get(PDO_NAME."order",['fkid'=>$info['id'],'plugin'=>'dating','payfor'=>'datingMatchmaker'],['id','plugin']);
                    $info['order'] = [
                        'type'    => $order['plugin'],
                        'orderid' => $order['id']
                    ];
                }
            }else{
                //不存在 获取默认信息
                $member = pdo_get(PDO_NAME."member",['id'=>$_W['mid']],['nickname','avatar','mobile']);
                $info = [
                    'nickname'    => $member['nickname'],
                    'avatar'      => $member['avatar'],
                    'phone'       => $member['mobile'],
                    'qrcode_show' => tomedia($member['avatar']),
                ];
            }
            $info['pay_settle'] = $set['pay_settle'] ? : 2;
            $info['money']      = $set['money'];

            $this->renderSuccess('会员信息',$info);
        }
    }
    /**
     * Comment: 红娘中心
     * Author: zzw
     * Date: 2021/3/10 18:06
     */
    public function matchmakerCenter(){
        global $_W,$_GPC;
        //获取红娘信息
        $info = pdo_get(PDO_NAME."dating_matchmaker",['mid'=>$_W['mid']],[
            'id',
            'total_commission',
            'commission',
        ]);
        //获取客户数量
        $info['customer'] = pdo_count(PDO_NAME."dating_member",
            ['uniacid'=>$_W['uniacid'],'matchmaker_id'=>$info['id'],'mid <>'=>$_W['mid'],'examine'=>3]);

        $this->renderSuccess('红娘信息',$info);
    }
    /**
     * Comment: 获取红娘提现信息
     * Author: zzw
     * Date: 2021/3/15 10:45
     */
    public function getMatchmakerWithdrawal(){
        global $_W,$_GPC;
        //获取设置信息
        $set = Setting::wlsetting_read('dating_set');
        //获取红娘信息
        $matchmaker = pdo_get(PDO_NAME."dating_matchmaker",['mid'=>$_W['mid'],'uniacid'=>$_W['uniacid']]);
        $userInfo  = pdo_get(PDO_NAME."member",['id'=>$_W['mid']],['bank_name','card_number','bank_username','alipay']);
        //信息拼装
        $data = [
            'commission'     => $matchmaker['commission'],//可提现金额
            'min_money'      => $set['min_money'],//最低提现金额
            'max_money'      => $set['max_money'],//最高提现金额
            'service_charge' => $set['service_charge'],//手续费
            'type'           => $set['matchmaker_type'],//可提现方式
            'user_info'      => $userInfo ? : [],//用户相关设置
        ];

        $this->renderSuccess('红娘提现准备信息',$data);
    }
    /**
     * Comment: 红娘提现
     * Author: zzw
     * Date: 2021/3/15 14:31
     */
    public function matchmakerWithdrawal(){
        global $_W,$_GPC;
        //参数信息获取  红娘提现:type=5
        $data['sapplymoney'] = $_GPC['sapplymoney'] OR $this->renderError('请输入申请提现佣金');
        $data['payment_type'] = $_GPC['payment_type'] OR $this->renderError('请选择提现打款方式');
        $set = Setting::wlsetting_read('dating_set');
        $matchmaker = pdo_get(PDO_NAME."dating_matchmaker",['mid'=>$_W['mid'],'uniacid'=>$_W['uniacid']]);
        //判断信息是否完整
        switch ($data['payment_type']) {
            case 1:
                //判断支付宝账号是否存在
                $alipay = trim($_GPC['alipay']) or $this->renderError("请输入支付宝账号");
                //修改用户的支付宝账号
                $alipayInfo = pdo_get(PDO_NAME."member",['alipay' => $alipay,'id' => $_W['mid']]);
                if (!$alipayInfo) {
                    $updateAliPay = pdo_update(PDO_NAME.'member',['alipay' => $alipay],['id' => $_W['mid']]);
                    if (!$updateAliPay) $this->renderError('支付宝账号保存失败，请联系管理员!');
                }
                break;//支付宝
            case 2:
                //1=公众号（默认）；2=h5；3=小程序
                $member = pdo_get(PDO_NAME.'member',['id' => $_W['mid']],['wechat_openid','openid']);
                if ($_W['source'] == 3) {
                    //小程序提现
                    if (!$member['wechat_openid']) $this->renderError('您无微信账号数据，无法微信提现'); else $sopenid = $member['wechat_openid'];
                } else {
                    //公众号或者H5提现
                    if (!$member['openid']) $this->renderError('您无微信账号数据，无法微信提现'); else $sopenid = $member['openid'];
                }
                break;//微信
            case 3:
                $bankName = trim($_GPC['bank_name']) or $this->renderError('请输入银行卡开户行');
                $cardNumber = trim($_GPC['card_number']) or $this->renderError('请输入银行卡账号');
                $bankUsername = trim($_GPC['bank_username']) or $this->renderError('请输入银行卡开户人的姓名');
                //修改用户的银行账号信息
                $bankInfo = pdo_get(PDO_NAME."member",[
                    'bank_name'     => $bankName,
                    'card_number'   => $cardNumber,
                    'bank_username' => $bankUsername,
                    'id'            => $_W['mid']
                ]);
                if (!$bankInfo) {
                    $updateBank = pdo_update(PDO_NAME.'member',[
                        'bank_name'     => $bankName,
                        'card_number'   => $cardNumber,
                        'bank_username' => $bankUsername,
                    ],['id' => $_W['mid']]);
                    if (!$updateBank) $this->renderError('银行卡信息保存失败，请联系管理员!');
                }
                break;//银行卡
        }
        //判断是否存在处理中的提现信息
        $isHave = pdo_get(PDO_NAME."settlement_temporary",['mid'=>$_W['mid'],'uniacid'=>$_W['uniacid'],'type'=>5]);
        if($isHave) $this->renderError('您有处理中的提现申请,请稍后再试');
        //获取上一次提现时间，判断当前时间是否允许进行提现
        if ($set['frequency'] > 0) {
            $sql = "SELECT applytime FROM "
                .tablename(PDO_NAME."settlement_record")
                ." WHERE mid = {$_W['mid']} AND `type` = 5 ORDER BY applytime DESC ";
            $lastTime = pdo_fetchcolumn($sql);//AND `type` = 5
            if ($lastTime > 0) {
                $timeRes = Commons::handleTime($lastTime,$set['frequency']);
                if($timeRes['status'] == 1) $this->renderError($timeRes['str']);
            }
        }
        //判断提现金额是否符合最大最小金额
        $minMoney = $set['min_money'] ? : 1;//申请提现最少金额，默认1。单位：元
        $maxMoney = $set['max_money'] ? : 0;//申请提现最大金额，默认无限制，单位：元
        if($data['sapplymoney'] < $minMoney) $this->renderError('提现金额必须大于等于' . $minMoney . '元');
        if($data['sapplymoney'] > $maxMoney) $this->renderError('单次提现金额必须小于等于' . $maxMoney . '元');
        if($data['sapplymoney'] > $matchmaker['commission']) $this->renderError('可提现金额不足');
        //获取提现手续费
        $serviceCharge = sprintf("%.2f", $data['sapplymoney'] * $set['service_charge'] / 100);
        $info = [
            'uniacid'       => $_W['uniacid'],
            'aid'           => $_W['aid'],
            'status'        => 15,//15=红娘提现审核中,16=红娘提现审核通过(待打款),17=红娘提现驳回,18=红娘提现已完成(打款完成)
            'type'          => 5,//1商家提现申请2代理提现申请3分销商申请提现 4用户余额提现 5红娘提现
            'mid'           => $_W['mid'],
            'sopenid'       => $sopenid ? : 0,//微信公众号|小程序openid
            'disid'         => $matchmaker['id'],//这里是红娘id
            'sgetmoney'     => sprintf("%.2f", $data['sapplymoney']  - $serviceCharge),//实际得到金额
            'sapplymoney'   => $data['sapplymoney'],//申请提现金额
            'spercentmoney' => $serviceCharge,//系统抽成金额
            'spercent'      => sprintf("%.4f", $set['service_charge']),//系统抽成比例
            'applytime'     => time(),
            'payment_type'  => $data['payment_type'],//打款方式
            'source'        => $_W['source']
        ];
        //判断是否需要免审核
        if ($set['matchmaker_withdrawal'] != 1) {
            //免审核 状态修改为待打款状态
            $info['status'] = 16;
            $trade_no = time() . random(4, true);
            $info['trade_no'] = $trade_no;
            $info['updatetime'] = time();
        }
        //中间表记录
        $value = serialize($info);
        $temporary = [
            'info'    => $value,
            'type'    => 5,//5=红娘提现申请
            'uniacid' => $_W['uniacid'],
            'mid'     => $info['mid']
        ];
        $res = pdo_insert(PDO_NAME . 'settlement_temporary' , $temporary);
        if($res) $this->renderSuccess('申请成功');
        else $this->renderError('申请失败，请重试');
    }
    /**
     * Comment: 我的红娘
     * Author: zzw
     * Date: 2021/3/11 10:28
     */
    public function matchmakerService(){
        global $_W,$_GPC;
        //参数信息获取
        $page = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $this->isPerfectInfo();//判断是否完善资料信息
        $info =  Setting::wlsetting_read('dating_set');
        //获取我的红娘
        $matchmakerId = pdo_getcolumn(PDO_NAME."dating_member",['mid'=>$_W['mid']],'matchmaker_id');
        $field = ['id','mid','nickname','avatar','phone','wechat_number','qq_unmber','describe','qrcode'];
        $matchmaker = pdo_get(PDO_NAME."dating_matchmaker",['id'=>$matchmakerId],$field);
        $matchmaker = Dating::handleMatchmakerInfo($matchmaker);
        unset($matchmaker['mid'],$matchmaker['customer']);
        //获取其他红娘列表
        $list = pdo_getall(PDO_NAME."dating_matchmaker",['uniacid'=>$_W['uniacid'],'id <>'=>$matchmakerId,'status'=>3,'mid <>'=>$_W['mid']],$field,'','create_time DESC',[$page,$pageIndex]);
        foreach($list as &$item){
            $item = Dating::handleMatchmakerInfo($item);
            unset($item['mid'],$item['customer']);
        }
        $data = [
            'matchmaker' => $matchmaker,
            'list'       => $list,
            'hideChange' => $info['change_matchmaker'] ? : 0
        ];

        $this->renderSuccess('红娘信息列表',$data);
    }
    /**
     * Comment: 切换红娘
     * Author: zzw
     * Date: 2021/3/11 10:37
     */
    public function matchmakerChange(){
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] OR $this->renderError('切换失败，不存在的红娘!');
        $this->isPerfectInfo();//判断是否完善资料信息
        //查询用户信息  不可移动位置，必须在前面因为这里获取的红娘id为旧的红娘的id
        $memberInfo = pdo_get(PDO_NAME."dating_member",['mid'=>$_W['mid']],['matchmaker_id','id']);
        //切换操作
        $res = pdo_update(PDO_NAME."dating_member",['matchmaker_id'=>$id],['mid'=>$_W['mid']]);
        if($res) {
            [$nickname,$avatar] = Dating::handleUserInfo($_W['mid']);
            $nowMatchmakerMid = pdo_getcolumn(PDO_NAME."dating_matchmaker",['id'=>$id],'mid');
            //发送模板消息通知当前红娘
            $modelData = [
                'first'   => '',
                'type'    => '新增用户',
                'content' => "用户：[{$nickname}]指定您作为他的红娘",
                'status'  => '待查看',
                'time'    => date("Y-m-d H:i:s",time()),
                'remark'  => "点击查看用户信息"
            ];
            $url = h5_url('pages/subPages2/blindDate/member/detail',['id'=>$memberInfo['id']]);
            TempModel::sendInit('service',$nowMatchmakerMid,$modelData,$_W['source'],$url);
            //以前存在红娘  发布模消息通知红娘失去了一个用户
            if($memberInfo['matchmaker_id'] > 0){
                //发送模板消息
                $oldMatchmakerMid = pdo_getcolumn(PDO_NAME."dating_matchmaker",['id'=>$memberInfo['matchmaker_id']],'mid');
                $modelData = [
                    'first'   => '失去了一个用户',
                    'type'    => '失去用户',
                    'content' => "用户：[{$nickname}]和您解除关系",
                    'status'  => '系统通知',
                    'time'    => date("Y-m-d H:i:s",time()),
                    'remark'  => ""
                ];
                TempModel::sendInit('service',$oldMatchmakerMid,$modelData,$_W['source']);
                //取消推荐关系
                $sql = " DELETE FROM ".tablename(PDO_NAME."dating_exchange")." WHERE mid_one = {$_W['mid']} OR mid_two = {$_W['mid']} ";
                pdo_query($sql);
            }

            $this->renderSuccess('切换成功');
        } else {
            $this->renderError('切换失败，请刷新重试!');
        }
    }
    /**
     * Comment: 推荐会员信息
     * Author: zzw
     * Date: 2021/3/11 11:53
     */
    public function recommendMember(){
        global $_W,$_GPC;
        //参数信息获取
        $page = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $this->isPerfectInfo();//判断是否完善资料信息
        //获取当前用户 择偶要求
        $field = ['id','gneder','min_age','max_age','min_height','max_height','require_marital_status','require_education'];
        $member = pdo_get(PDO_NAME."dating_member",['mid'=>$_W['mid']],$field);
        //特殊查询
        $area = "CASE WHEN current_area > 0 THEN current_area
                    WHEN current_city > 0 THEN current_city
                    ELSE current_province
                 END";
        $age = "CASE WHEN (FROM_UNIXTIME(unix_timestamp(now()) ,'%m') - FROM_UNIXTIME(birth,'%m')) < 0 AND (FROM_UNIXTIME(unix_timestamp(now()) ,'%d') - FROM_UNIXTIME(birth,'%d')) < 0 
                        THEN (TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(birth,'%Y-%m-%d'), CURDATE()) - 1)
                     ELSE TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(birth,'%Y-%m-%d'), CURDATE())
                END";
        //基本条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND examine = 3 ";
        if($member){
            //不能是自己
            $where .= " AND id <> {$member['id']} ";
            //推荐必须是异性  性别:1=男,2=女
            if($member['gneder'] == 1) $where .= " AND gneder = 2 ";
            else  $where .= " AND gneder = 1 ";
            //年龄条件
            if($member['min_age'] > 0) $where .= " AND {$age} >= {$member['min_age']} ";
            if($member['max_age'] > 0) $where .= " AND {$age} <= {$member['max_age']} ";
            //身高条件
            if($member['min_height'] > 0) $where .= " AND height >= {$member['min_height']}  ";
            if($member['max_height'] > 0) $where .= " AND height <= {$member['max_height']} ";
            //择偶要求 - 婚姻情况:1=不限,2=未婚,3=离异,4=丧偶
            switch ($member['require_marital_status']){
                case 2: $where .= " AND marital_status = 1 ";break;
                case 3: $where .= " AND marital_status IN (2,3,4) ";break;
                case 4: $where .= " AND marital_status IN (5,6) ";break;
            }
            //择偶要求 - 学历:1=不限,2=小学,3=初中,4=高中/中专,5=专科,6=本科,7=硕士,8=博士
            switch ($member['require_education']){
                case 2: $where .= " AND education >= 1 ";break;
                case 3: $where .= " AND education >= 2 ";break;
                case 4: $where .= " AND education >= 3 ";break;
                case 5: $where .= " AND education >= 4 ";break;
                case 6: $where .= " AND education >= 5 ";break;
                case 7: $where .= " AND education >= 6 ";break;
                case 8: $where .= " AND education >= 7 ";break;
            }
        }
        //信息列表获取
        $order = " ORDER BY is_top DESC,create_time DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $field = "id,mid,gneder,cover,label_id,{$area} as area_id,{$age} as age";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."dating_member");
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as &$item){
            $item = Dating::handleMemberInfo($item);
            //获取会员详细信息
            [$item['vip'],$item['is_vip']] = Dating::getVipInfo($item['mid']);
            $item['cover'] = tomedia($item['cover']);
            //获取区域信息
            $item['area'] = pdo_getcolumn(PDO_NAME."area",['id'=>$item['area_id']],'name');
            unset($item['real_name'],$item['label_id'],$item['mid'],$item['area_id']);
        }
        //获取总页数
        $totalSql      = str_replace($field,'count(*)',$sql);
        $total         = pdo_fetchcolumn($totalSql.$where);
        $data['total'] = ceil($total / $pageIndex);
        $data['list']  = $list;

        $this->renderSuccess('推荐列表',$data);
    }
    /**
     * Comment: 会员卡列表
     * Author: zzw
     * Date: 2021/3/12 13:41
     */
    public function vipList(){
        global $_W,$_GPC;
        $this->isPerfectInfo();//判断是否完善资料信息
        //获取用户信息
        [$data['nickname'],$data['avatar']] = Dating::handleUserInfo($_W['mid']);
        //获取会员详细信息
        [$data['vip'],$data['is_vip']] = Dating::getVipInfo($_W['mid']);
        //获取会员卡列表
        $where['uniacid'] = $_W['uniacid'];
        if($data['is_vip'] == 1){
            //用户是会员的情况下  仅获取和当前会员同类型的会员卡
            $vipType = pdo_getcolumn(PDO_NAME."dating_member_open",['mid'=>$_W['mid']],'type');
            $where['type'] = $vipType;
        }
        $data['list'] = pdo_getall(PDO_NAME."dating_vip",$where,['id','title','type','day','second','money']);


        $this->renderSuccess('会员卡列表',$data);
    }
    /**
     * Comment: 会员卡开通|会员卡续费
     * Author: zzw
     * Date: 2021/3/16 9:48
     */
    public function vipOpen(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR $this->renderError('请选择会员卡');//vip卡id
        $this->isPerfectInfo();//判断是否完善资料信息
        //获取会员卡信息
        $vipInfo = pdo_get(PDO_NAME."dating_vip",['id'=>$id]);
        //根据是否存在会员卡信息进行对应的操作  如果是会员则当前为续费操作，判断会员卡类型是否一致
        [$isVip['status'],$isVip['end_time']] = Dating::isVip($_W['mid']);
        if($isVip['status'] == 1){
            $vipType = pdo_getcolumn(PDO_NAME."dating_member_open",['mid' => $_W['mid']],'type');
            if($vipType != $vipInfo['type']) $this->renderError('会员卡类型不一致');
        }
        //根据是否付费进行对应的操作
        if($vipInfo['money'] > 0){
            //需要付费
            $orderData = [
                'uniacid'     => $_W['uniacid'],
                'mid'         => $_W['mid'],
                'aid'         => $_W['aid'],
                'fkid'        => $id,//会员卡id
                'createtime'  => time(),
                'orderno'     => createUniontid(),
                'price'       => sprintf("%.2f",$vipInfo['money']),
                'num'         => 1,
                'plugin'      => 'dating',
                'payfor'      => 'datingVip',
                'goodsprice'  => sprintf("%.2f",$vipInfo['money']),
                'fightstatus' => 1,
            ];
            pdo_insert(PDO_NAME.'order',$orderData);
            $orderId = pdo_insertid();
            if (empty($orderId)) $this->renderError('生成订单失败，请刷新重试');
            else $this->renderSuccess('申请成功',['status' => 1,'type' => 'dating','orderid' => $orderId]);
        }else{
            //不需要付费
            Dating::handleVipInfo($id,$_W['mid']);

            $this->renderSuccess('开通成功');
        }
    }
    /**
     * Comment: 申请置顶
     * Author: zzw
     * Date: 2021/3/16 10:25
     */
    public function applyTop(){
        global $_W,$_GPC;
        //参数信息获取
        $topDay = $_GPC['day'] or $this->renderError('请选择置顶方式！');//置顶天数
        $set = Setting::wlsetting_read('dating_set');
        $this->isPerfectInfo();//判断是否完善资料信息
        //判断当前置顶信息是否存在
        $days = array_column($set['top_rule'],'day');
        $tops = array_combine($days,$set['top_rule']);
        if(!$tops[$topDay]) $this->renderError('不存在的置顶方式，请刷新重试！');
        //获取并且判断置顶总数量
        if($set['top_number'] > 0){
            $totalTop = pdo_count(PDO_NAME."dating_member",[
                'is_top'  => 2,//是否置顶:1=未置顶,2=置顶中
                'uniacid' => $_W['uniacid'],
                'aid'     => $_W['aid']
            ]);
            if($totalTop >= $set['top_number']) $this->renderError('置顶失败，置顶数量已达上限！');
        }
        //置顶成功 生成置顶订单
        $member = pdo_get(PDO_NAME."dating_member",['mid'=>$_W['mid']]);
        $orderdata = [
            'uniacid'     => $member['uniacid'],
            'mid'         => $_W['mid'],
            'aid'         => $member['aid'],
            'fkid'        => $member['id'],
            'createtime'  => time(),
            'orderno'     => createUniontid(),
            'price'       => sprintf("%.2f",$tops[$topDay]['price']),
            'num'         => $topDay,//num这里是置顶的天数
            'plugin'      => 'dating',
            'payfor'      => 'datingTop',
            'goodsprice'  => sprintf("%.2f",$tops[$topDay]['price']),
            'fightstatus' => 2,//代表这里是置顶操作
        ];
        pdo_insert(PDO_NAME.'order',$orderdata);
        $orderid = pdo_insertid();
        if (empty($orderid)) {
            $this->renderError('生成订单失败，请刷新重试');
        } else {
            $this->renderSuccess('置顶成功',['status' => 1,'type' => 'dating','orderid' => $orderid]);
        }
    }
    /**
     * Comment: 用户信息交换(向会员推荐对象)|取消交换
     * Author: zzw
     * Date: 2021/3/16 16:16
     */
    public function userInfoExchange(){
        global $_W,$_GPC;
        //参数信息获取
        $memberId = $_GPC['member_id'] OR $this->renderError('请选择用户');//会员id，被推荐的会员的id
        $ids = json_decode(html_entity_decode($_GPC['ids']),true) OR $this->renderError('请选择至少一个会员');//会员id，推荐的会员的id   接收格式[1,2,3,4,5]
        $type = $_GPC['type'] ? : 1;//操作类型：1=推荐，2=取消推荐
        $theMid = pdo_getcolumn(PDO_NAME."dating_member",['id'=>$memberId],'mid');
        //循环处理信息
        foreach($ids as $id){
            $mid = pdo_getcolumn(PDO_NAME."dating_member",['id'=>$id],'mid');
            if($type == 1 && !Dating::isExchange($theMid,$mid)){
                //交换用户信息操作
                $data = [
                    'uniacid'     => $_W['uniacid'],
                    'mid_one'     => $theMid,
                    'mid_two'     => $mid,
                    'create_time' => time()
                ];
                pdo_insert(PDO_NAME."dating_exchange",$data);
                //发送模板消息通知
                Dating::sendExchangeInfo($theMid,$mid);
                Dating::sendExchangeInfo($mid,$theMid);
            }else if($type == 2 && Dating::isExchange($theMid,$mid)){
                //取消信息交换操作
                $sql = "DELETE FROM ".tablename(PDO_NAME."dating_exchange")
                    ." WHERE (mid_one = {$theMid} AND mid_two = {$mid}) OR (mid_one = {$mid} AND mid_two = {$theMid})";
                pdo_query($sql);
                //发送模板消息通知
                Dating::sendCancelExchangeInfo($theMid,$mid);
                Dating::sendCancelExchangeInfo($mid,$theMid);
            }
        }

        $this->renderSuccess('操作成功');
    }
    /**
     * Comment: 判断当前用户是否完善资料
     * Author: zzw
     * Date: 2021/4/8 11:08
     */
    private function isPerfectInfo(){
        global $_W;
        $member = pdo_get(PDO_NAME."dating_member",['mid'=>$_W['mid']]);//当前用户会员信息
        $matchmaker = pdo_get(PDO_NAME."dating_matchmaker",['mid'=>$_W['mid'],'status' => 3]);//当前用户红娘信息
        if(!$member && !$matchmaker){
            $this->renderError('请先完善个人资料信息!',['is_perfect'=>1]);
        }
    }



}




