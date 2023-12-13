<?php
defined('IN_IA') or exit('Access Denied');


class Dating {
    /**
     * Comment: 默认标签列表
     * Author: zzw
     * Date: 2021/2/25 15:12
     * @return string[]
     */
    public static function defaultLabelList(){
        return [
            '爱网球',
            '爱羽毛球',
            '爱篮球',
            '爱养宠物',
            '爱足球',
            '背包客',
            '玩游戏',
            '爱摄影',
            '爱滑板',
            '二次元',
            '手办发烧友',
            '赛车',
            '健身',
            '文艺青年',
            '游泳'
        ];
    }

    /**
     * Comment: 默认选项设置
     * Author: wlf
     * Date: 2022/05/20 15:12
     * @return string[]
     */
    public static function defaultOptionList(){
        $default = [];
        if(Customized::init('university442')){
            $default[] = ['type' => 1,'title' => '大一'];
            $default[] = ['type' => 1,'title' => '大二'];
            $default[] = ['type' => 1,'title' => '大三'];
            $default[] = ['type' => 1,'title' => '大四'];
            $default[] = ['type' => 1,'title' => '大五'];
            $default[] = ['type' => 3,'title' => '体育社'];
            $default[] = ['type' => 3,'title' => '音乐社'];
            $default[] = ['type' => 3,'title' => '学生会'];
            $default[] = ['type' => 4,'title' => '游戏交友'];
        }else{
            $default[] = ['type' => 1,'title' => '未婚'];
            $default[] = ['type' => 1,'title' => '离异(无子女)'];
            $default[] = ['type' => 1,'title' => '离异(有抚养权)'];
            $default[] = ['type' => 1,'title' => '离异(无抚养权)'];
            $default[] = ['type' => 1,'title' => '丧偶(无子女)'];
            $default[] = ['type' => 1,'title' => '丧偶(有子女)'];
            $default[] = ['type' => 2,'title' => '小学'];
            $default[] = ['type' => 2,'title' => '初中'];
            $default[] = ['type' => 2,'title' => '高中/中专'];
            $default[] = ['type' => 2,'title' => '专科'];
            $default[] = ['type' => 2,'title' => '本科'];
            $default[] = ['type' => 2,'title' => '硕士'];
            $default[] = ['type' => 2,'title' => '博士'];
            $default[] = ['type' => 3,'title' => '农业户口'];
            $default[] = ['type' => 3,'title' => '非农业户口'];
            $default[] = ['type' => 4,'title' => '自购房(有贷款)'];
            $default[] = ['type' => 4,'title' => '自购房(无贷款)'];
            $default[] = ['type' => 4,'title' => '租房(合租)'];
            $default[] = ['type' => 4,'title' => '租房(整租)'];
            $default[] = ['type' => 4,'title' => '与父母同住'];
            $default[] = ['type' => 4,'title' => '借住亲朋家'];
            $default[] = ['type' => 4,'title' => '单位住房'];
            $default[] = ['type' => 5,'title' => '未购车'];
            $default[] = ['type' => 5,'title' => '已购车'];
        }
        return $default;
    }


    /**
     * Comment: 根据日期获取年龄
     * Author: zzw
     * Date: 2021/2/25 18:25
     * @param string $birthday
     * @return false|int|mixed|string
     */
    public static function getAge(string $birthday){
        [$year , $month , $day] = explode("-" , $birthday);
        $year_diff  = date("Y") - $year;
        $month_diff = date("m") - $month;
        $day_diff   = date("d") - $day;
        if ($day_diff < 0 && $month_diff < 0) $year_diff--;
        return $year_diff;
    }
    /**
     * Comment: 地区处理
     * Author: zzw
     * Date: 2021/2/26 9:48
     * @param $provinceId
     * @param $cityId
     * @param $areaId
     * @return mixed|string
     */
    public static function handleAreaInfo($provinceId,$cityId,$areaId){
        if ($provinceId) $province = pdo_getcolumn(PDO_NAME."area",['id' => $provinceId],'name');
        if ($cityId) $city = pdo_getcolumn(PDO_NAME."area",['id' => $cityId],'name');
        if ($areaId) $area = pdo_getcolumn(PDO_NAME."area",['id' => $areaId],'name');
        if($province && $city && $area) $address = $province.$city.$area;
        else if($province && $city && !$area) $address = $province.$city;
        else if(!$province && $city && $area) $address = $city.$area;
        else if($province) $address = $province;
        else if($city) $address = $city;
        else if($area) $address = $area;

        return $address;
    }
    /**
     * Comment: 判断当前用户是否收藏|浏览某个用户
     * Author: zzw
     * Date: 2021/3/8 15:09
     * @param int|string $mid          当前用户id
     * @param int|string $objectMid    收藏|浏览对象用户id
     * @param int $type  1=收藏，2=浏览历史
     * @return int
     */
    public static function isCollectionOrBrowse($mid,$objectMid,$type = 1){
        global $_W;
        $isHave = pdo_get(PDO_NAME."dating_record",['mid'=>$mid,'object_mid'=>$objectMid,'uniacid'=>$_W['uniacid'],'type'=>$type]);
        if ($isHave) return 1;
        else return 0;
    }
    /**
     * Comment: 判断当前会员是否为会员
     * Author: zzw
     * Date: 2021/3/8 16:40
     * @param $mid
     * @return array
     */
    public static function isVip($mid){
        global $_W;
        //获取用户开卡信息
        $info = pdo_get(PDO_NAME."dating_member_open",['mid'=>$mid]);
        $data = [
            'status' => 1,//是否为会员 0=不是，1=是
            'num_time' => '0',//到期时间|剩余次数
        ];
        if($info){
            //存在会员信息,进行处理  会员卡类型:1=时限卡,2=次数卡
            if($info['type'] == 1){
                $data['num_time'] = date("Y-m-d H:i",$info['end_time']);//到期时间
                if($info['end_time'] <= time()) $data['status'] = 0;//会员卡已到过期时间   已过期
            }else if($info['type'] == 2){
                $use = pdo_count(PDO_NAME."dating_member_use",['mid'=>$mid,'uniacid'=>$_W['uniacid']]);
                $data['num_time'] = $info['frequency'] - $use;//剩余次数
                if($data['num_time'] <= 0) $data['status'] = 0;//会员卡次数已使用完毕    已过期
            }
        }else{
            //无开卡信息  不是会员
            $data['status'] = 0;
        }

        return [$data['status'] ,$data['num_time']];
    }
    /**
     * Comment: 获取会员详细信息
     * Author: zzw
     * Date: 2021/4/1 14:17
     * @param $mid
     * @return array
     */
    public static function getVipInfo($mid){
        $vip = pdo_get(PDO_NAME."dating_member_open",['mid'=>$mid],['type','end_time','frequency']);
        if($vip){
            //到期时间
            $vip['end_time_text'] = date("Y-m-d H:i",$vip['end_time']);
            //剩余次数
            $useTotal      = pdo_count(PDO_NAME."dating_member_use",['mid' => $mid]);
            $surplusNumber = $vip['frequency'] - $useTotal;
            $vip['surplus_number'] = $surplusNumber > 0 ? $surplusNumber : 0;
            $isVip = 1;
            //判断是否已经过期或者失效  会员卡类型:1=时限卡,2=次数卡
            if($vip['type'] == 1 && $vip['end_time'] <= time()) {
                $vip['type'] = '3';//已过期
                $isVip = 0;
            } else if($vip['type'] == 2 && $surplusNumber <= 0) {
                $vip['type'] = '4';//已失效
                $isVip = 0;
            }
        }else{
            //不存在会员卡
            $vip  = [];
            $isVip = 0;
        }

        return [$vip,$isVip];
    }
    /**
     * Comment: 判断是否交换联系方式
     * Author: zzw
     * Date: 2021/3/8 16:46
     * @param $midOne
     * @param $midTwo
     * @return int
     */
    public static function isExchange($midOne,$midTwo){
        global $_W;
        $sql = "SELECT * FROM ".tablename(PDO_NAME."dating_exchange")
            ." WHERE (mid_one = {$midOne} AND mid_two = {$midTwo}) OR (mid_one = {$midTwo} AND mid_two = {$midOne})";
        $isHave = pdo_fetch($sql);
        if($isHave) return 1;
        else return 0;
    }
    /**
     * Comment: 佣金变更记录
     * Author: zzw
     * Date: 2021/3/15 16:10
     * @param int $mid 用户id
     * @param float|int|string $money 金额
     * @param string $reason 备注
     * @param int $type 类型
     * @param int|string $orderId 订单id
     * @return false|mixed
     */
    public static function commissionChangeRecord($mid,$money,$reason,$type = 1,$orderId = 0){
        global $_W;
        $matchmakerId = pdo_getcolumn(PDO_NAME."dating_matchmaker",['mid' => $mid,'uniacid' => $_W['uniacid']],'id');
        $data = [
            'uniacid'       => $_W['uniacid'],
            'mid'           => $mid,
            'matchmaker_id' => $matchmakerId,//红娘id
            'type'          => $type,//类型:1=增加,2=减少
            'money'         => $money,//金额
            'order_id'      => $orderId,
            'reason'        => $reason,
            'create_time'   => time()
        ];
        return pdo_insert(PDO_NAME."dating_matchmaker_commission",$data);
    }
    /**
     * Comment: 发送信息交换成功模板消息通知
     * Author: zzw
     * Date: 2021/4/8 14:22
     * @param int $theMid   当前用户的mid
     * @param int $mid      被推荐用户的mid
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendExchangeInfo(int $theMid,int $mid){
        global $_W;
        [$nickname,$avatar] = Dating::handleUserInfo($mid);
        $memberId = pdo_getcolumn(PDO_NAME."dating_member",['mid'=>$mid],'id');
        $modelData = [
            'first'   => '您的红娘为您推荐了一个朋友',
            'type'    => '信息交换',
            'content' => "您的红娘将您的信息和{$nickname}的信息进行了交换，现在您可以查看该用户的详细信息了！",
            'status'  => '交换成功',
            'time'    => date("Y-m-d H:i:s",time()),
            'remark'  => "点击查看"
        ];
        $url = h5_url('pages/subPages2/blindDate/member/detail',['id'=>$memberId]);
        TempModel::sendInit('service',$theMid,$modelData,$_W['source'],$url);
    }
    /**
     * Comment: 发送信息通知取消交换信息
     * Author: zzw
     * Date: 2021/4/8 14:33
     * @param int $theMid   当前用户的mid
     * @param int $mid      被推荐用户的mid
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendCancelExchangeInfo(int $theMid,int $mid){
        global $_W;
        [$nickname,$avatar] = Dating::handleUserInfo($mid);
        $modelData = [
            'first'   => '您的红娘取消了一个信息交换',
            'type'    => '取消交换',
            'content' => "您的红娘取消了您和{$nickname}的信息交换，您授权了查看该用户详细信息的权利！",
            'status'  => '取消成功',
            'time'    => date("Y-m-d H:i:s",time()),
            'remark'  => ""
        ];
        TempModel::sendInit('service',$theMid,$modelData,$_W['source']);
    }
    /**
     * Comment: 发送 动态评论&评论回复 的通知模板消息
     * Author: zzw
     * Date: 2021/4/13 14:24
     * @param $mid
     * @param $replyId
     * @param $dynamicId
     * @param $content
     * @param $source
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendCommentModel($mid,$replyId,$dynamicId,$content,$source){
        [$nickname,$avatar] = self::handleUserInfo($mid);
        //发送模板消息
        if($replyId > 0){
            //回复某条评论   发送给评论的用户
            $first = "您好，[{$nickname}]回复了您的评论";
            $type  = "评论回复";
            $theMid = pdo_getcolumn(PDO_NAME."dating_dynamic_comment",['id'=>$replyId],'mid');
        }else{
            //对动态进行评论  发送给动态发布用户
            $first = "您好，[{$nickname}]进行了评论";
            $type  = "动态评论";
            $theMid = pdo_getcolumn(PDO_NAME."dating_dynamic",['id'=>$dynamicId],'mid');
        }
        if($theMid > 0){
            $modelData = [
                'first'   => $first,
                'type'    => $type,
                'content' => $content,
                'status'  => '待查看',
                'time'    => date("Y-m-d H:i:s",time()),
                'remark'  => "请尽快查看"
            ];
            $url = h5_url('pages/subPages2/blindDate/dynamics/detail',['id'=>$dynamicId]);
            TempModel::sendInit('service',$theMid,$modelData,$source,$url);
        }
    }


    /**
     * Comment: 获取用户昵称和用户头像  先查看相亲交友会员表然后查看用户信息表
     * Author: zzw
     * Date: 2021/3/9 10:58
     * @param $mid
     * @return array
     */
    public static function handleUserInfo($mid){
        global $_W;
        $user = pdo_get(PDO_NAME."member",['id'=>$mid],['nickname','avatar','realname','encodename']);
        $user['nickname'] = !empty($user['encodename']) ? base64_decode($user['encodename']) : $user['nickname'];

        return [$user['nickname'],tomedia($user['avatar']),$user['realname']];
    }
    /**
     * Comment: 会员基本信息处理
     * Author: zzw
     * Date: 2021/2/26 15:54
     * @param $info
     * @return mixed
     */
    public static function handleMemberInfo($info){
        //用户基本信息处理
        if($info['mid']){
            [$info['nickname'],$info['avatar'],$info['realname']] = self::handleUserInfo($info['mid']);
        }else{
            $info['nickname'] = $info['falsename'];
            $info['avatar'] = $info['falseavatar'];
            $info['realname'] = $info['falsereal'];
        }
        //出生日期&年龄
        if($info['birth']) {
            $info['birth_text'] = date("Y-m-d",$info['birth']);//出生日期
            $info['age'] = self::getAge(date("Y-m-d",$info['birth']));//年龄
        }
        //婚姻情况:1=未婚,2=离异(无子女),3=离异(有抚养权),4=离异(无抚养权),5=丧偶(无子女),6=丧偶(有子女)
        if($info['marital_status']){
            $info['marital_status_text'] = pdo_getcolumn(PDO_NAME.'dating_option',array('id'=>$info['marital_status']),'title');
        }
        //学历:1=小学,2=初中,3=高中/中专,4=专科,5=本科,6=硕士,7=博士
        if($info['education']){
            $info['education_text'] = pdo_getcolumn(PDO_NAME.'dating_option',array('id'=>$info['education']),'title');
        }
        //所在城市
        if ($info['current_province'] || $info['current_city'] || $info['current_area']) $info['current_address'] = self::handleAreaInfo($info['current_province'],$info['current_city'],$info['current_area']);
        //户籍所在城市
        if ($info['hometown_province'] || $info['hometown_city'] || $info['hometown_area']) $info['hometown_address']  = self::handleAreaInfo($info['hometown_province'],$info['hometown_city'],$info['hometown_area']);
        //户籍类型:1=农业户口,2=非农业户口
        if($info['registered_residence_type']) {
            $info['registered_residence'] = pdo_getcolumn(PDO_NAME.'dating_option',array('id'=>$info['registered_residence_type']),'title');
        }
        //居住情况:1=自购房(有贷款),2=自购房(无贷款),3=租房(合租),4=租房(整租),5=与父母同住,6=借住亲朋家,7=单位住房
        if($info['live']){
            $info['live_text'] = pdo_getcolumn(PDO_NAME.'dating_option',array('id'=>$info['live']),'title');
        }
        //出行情况:1=未购车,2=已购车
        if($info['travel']){
            $info['travel_text'] = pdo_getcolumn(PDO_NAME.'dating_option',array('id'=>$info['travel']),'title');
        }
        //择偶要求 - 婚姻情况:1=不限,2=未婚,3=离异,4=丧偶
        if($info['require_marital_status']){
            $info['require_marital'] = pdo_getcolumn(PDO_NAME.'dating_option',array('id'=>$info['require_marital_status']),'title');
        }else{
            $info['require_marital'] = '无';
        }
        //择偶要求 - 学历:1=不限,2=小学,3=初中,4=高中/中专,5=专科,6=本科,7=硕士,8=博士
        if($info['require_education']){
            $info['require_education_text'] = pdo_getcolumn(PDO_NAME.'dating_option',array('id'=>$info['require_education']),'title');
        }else{
            $info['require_education_text'] = '无';
        }
        //个性标签
        if($info['label_id']){
            $labelId = explode(',',$info['label_id']);
            $info['labelId'] = $labelId;
            $labelList = pdo_getall(PDO_NAME."dating_label",['id IN'=>$labelId],['title']);
            $info['label_list'] = array_column($labelList,'title');
        }
        //个人照片
        if($info['photo']){
            $photo = unserialize($info['photo']);
            foreach($photo as &$img){
                $img = tomedia($img);
            }
            $info['photo_show'] = $photo;
        }
        //个人视频
        if($info['video']) $info['video_show'] = tomedia($info['video']);
        //封面图
        if($info['cover']) $info['cover_show'] = tomedia($info['cover']);
        //置顶结束时间
        if($info['top_end_time']) $info['top_end_time_text'] = date("Y-m-d H:i",$info['top_end_time']);
        //创建时间
        if($info['create_time']) $info['create_time_text'] = date("Y-m-d H:i",$info['create_time']);
        //距离处理
        if($info['distances']){
            if($info['distances'] < 1000){
                $info['distances_text'] = $info['distances'].'m';
            }else{
                $info['distances_text'] = sprintf("%.2f",$info['distances'] / 1000).'km';
            }
        }

        return $info;
    }
    /**
     * Comment: 动态基本信息处理
     * Author: zzw
     * Date: 2021/3/1 14:33
     * @param $info
     * @return mixed
     */
    public static function handleDynamicInfo($info){
        global $_W;
        //用户信息  是否为虚拟动态:1=不是,2=是
        if($info['is_fictitious'] == 1 && $info['mid'] > 0){
            //不是虚拟动态信息  获取对应的数据信息
            [$info['nickname'],$info['avatar']] = self::handleUserInfo($info['mid']);
            $dating = pdo_get(PDO_NAME."dating_member",['mid'=>$info['mid']],['gneder','id']);
            $info['gneder'] = $dating['gneder'];
            $info['datingid'] = $dating['id'];
        }else if($info['is_fictitious'] == 2 && $info['fictitious_nickname'] && $info['fictitious_avatar']){
            $info['nickname'] = $info['fictitious_nickname'];
            $info['avatar'] = tomedia($info['fictitious_avatar']);
            if(empty($info['gender'])){
                $info['gneder'] = 2;
            }else{
                $info['gneder'] = $info['gender'];
            }
        }
        //图片信息
        if($info['photo']){
            $photo = unserialize($info['photo']);
            foreach($photo as &$img){
                $img = tomedia($img);
            }
            $info['photo_show'] = $photo;
        }
        //视频信息处理
        if($info['video']){
            $info['video_show'] = tomedia($info['video']);
        }
        //发布时间
        if($info['create_time']){
            $info['create_time_text'] = date("Y-m-d H:i",$info['create_time']);
        }
        //点赞数量&评论数量
        if($info['id']){
            //点赞数量
            $info['fabulous'] = pdo_count(PDO_NAME."dating_dynamic_fabulous",['dynamic_id'=>$info['id']]);
            //评论数量
            $commentCountWhere = " WHERE dynamic_id = {$info['id']} AND (status = 3 OR mid = {$_W['mid']}) ";
            $commentCountSql = " SELECT count(*) FROM ".tablename(PDO_NAME."dating_dynamic_comment").$commentCountWhere;;
            $info['comment'] = pdo_fetchcolumn($commentCountSql);
        }
        //距离处理
        if($info['distances']){
            if($info['distances'] < 1000){
                $info['distances_text'] = $info['distances'].'m';
            }else{
                $info['distances_text'] = sprintf("%.2f",$info['distances'] / 1000).'km';
            }
        }

        return $info;
    }
    /**
     * Comment: 红娘信息处理
     * Author: zzw
     * Date: 2021/3/3 11:10
     * @param $info
     * @return mixed
     */
    public static function handleMatchmakerInfo($info){
        //用户信息
        if ($info['mid']){
            [$nickname,$avatar] = self::handleUserInfo($info['mid']);
            $info['nickname'] = $info['nickname'] ? : $nickname;
            $info['avatar'] = $info['avatar'] ? tomedia($info['avatar']) : $avatar;
        }
        //二维码
        if($info['qrcode']) $info['qrcode'] = tomedia($info['qrcode']);
        //状态:1=待付款,2=待审核,3=已通过,4=未通过
        if($info['status']){
            switch ($info['status']){
                case 1: $info['status_text'] = '待付款';break;
                case 2: $info['status_text'] = '待审核';break;
                case 3: $info['status_text'] = '已通过';break;
                case 4: $info['status_text'] = '未通过';break;
            }
        }
        //创建时间
        if($info['create_time']) $info['create_time_text'] = date('Y-m-d H:i',$info['create_time']);
        //获取客户数量
        if($info['id']) $info['customer'] = pdo_count(PDO_NAME."dating_member",['matchmaker_id'=>$info['id']]);

        return $info;
    }
    /**
     * Comment: 会员卡开通|续费操作处理
     * Author: zzw
     * Date: 2021/3/15 17:26
     * @param int|string $vipId
     * @param int|string $mid
     * @param int|float|string $money
     * @return false|mixed
     */
    public static function handleVipInfo($vipId,$mid,$money = 0){
        global $_W;
        //参数信息获取
        $vip                 = pdo_get(PDO_NAME."dating_vip",['id' => $vipId]);
        $info                = pdo_get(PDO_NAME."dating_member_open",['mid' => $mid],[
            'type',
            'end_time',
            'frequency',
            'create_time',
            'update_time'
        ]);
        $data['end_time']    = $info['end_time'] > 0 ? $info['end_time'] : time();
        $data['frequency']   = $info['frequency'] > 0 ? $info['frequency'] : 0;
        $data['create_time'] = $info['create_time'] > 0 ? $info['create_time'] : time();
        $data['update_time'] = time();
        //根据会员卡类型进行对应的操作  会员卡类型:1=时限卡,2=次数卡
        if($vip['type'] == 1) {
            $nowTime = time();
            $time = $data['end_time'] > $nowTime ? $data['end_time'] : $nowTime;
            $data['end_time'] = $time + ($vip['day'] * 86400);//修改过期时间
        } else if($vip['type'] == 2) {
            $data['frequency'] = $data['frequency'] + $vip['second'];//修改最大次数
        }
        //修改信息
        $data['type'] = $vip['type'];
        if($info){
            //修改信息
            $res = pdo_update(PDO_NAME."dating_member_open",$data,['mid'=>$mid]);
        }else{
            //添加信息
            $data['uniacid'] = $_W['uniacid'];
            $data['mid'] = $mid;
            $data['create_time'] = time();

            $res = pdo_insert(PDO_NAME."dating_member_open",$data);
        }
        if($res){
            //记录变更信息
            $record = [
                'create_time' => time(),
                'title'       => $vip['title'],
                'type'        => $vip['type'],
                'day'         => $vip['day'],
                'frequency'   => $vip['second'],
                'money'       => sprintf("%.2f",$money),
                'mid'         => $mid,
            ];
           return pdo_insert(PDO_NAME."dating_vip_record",$record);
        }
    }
    /**
     * Comment: 红娘佣金信息处理
     * Author: zzw
     * Date: 2021/4/1 15:54
     * @param        $matchmakerId
     * @param        $money
     * @param string $reason
     * @param string $orderId
     */
    public static function handleMatchmakerCommissionInfo($matchmakerId,$money,$reason = '',$orderId = ''){
        global $_W;
        //获取红娘信息
        $matchmaker = pdo_get(PDO_NAME."dating_matchmaker",['id' => $matchmakerId]
            ,['mid','status','total_commission','commission']);
        if($matchmaker['status'] == 3){
            //已通过红娘才能获取佣金
            $set = Setting::wlsetting_read('dating_set');
            $commission = $money * ($set['commission'] / 100);//佣金
            //佣金修改
            $updateDate = [
                'total_commission' => $matchmaker['total_commission'] + $commission,
                'commission'       => $matchmaker['commission'] + $commission,
            ];
            pdo_update(PDO_NAME."dating_matchmaker",$updateDate,['id'=>$matchmakerId]);
            //记录佣金变更记录  type:类型:1=增加,2=减少
            self::commissionChangeRecord($matchmaker['mid'],$commission,$reason,1,$orderId);
            //发送模板消息
            $modelData = [
                'first'   => $reason,
                'type'    => '佣金到账通知',
                'content' => "到账佣金：{$commission}",
                'status'  => '已到账',
                'time'    => date("Y-m-d H:i:s",time()),
            ];
            TempModel::sendInit('service',$matchmaker['mid'],$modelData,$_W['source']);
        }
    }


    /**
     * Comment: 置顶支付回调
     * Author: zzw
     * Date: 2021/4/1 15:55
     * @param $params
     */
    public static function payDatingTopNotify($params) {
        global $_W;
        $order = pdo_get(PDO_NAME."order",['orderno' => $params['tid']],['plugin','id','fkid','fightstatus','num']);
        //更新订单
        $data            = ['status' => $params['result'] == 'success' ? 3 : 0];
        $data['paytype'] = $params['type'];
        if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
        $data['paytime'] = TIMESTAMP;
        pdo_update(PDO_NAME.'order',$data,['id' => $order['id']]);
        //用户信息置顶/置顶续费 操作
        $memberInfo = pdo_get(PDO_NAME."dating_member",['id'=>$order['fkid']],['is_top','mid','top_end_time','matchmaker_id']);
        $nowTime = time();
        $endTime = $memberInfo['top_end_time'] > $nowTime ? $memberInfo['top_end_time'] : $nowTime;//当前时间  过期时间和当前时间取最大值
        $updateData['is_top'] = 2;//是否置顶:1=未置顶,2=置顶中
        $updateData['top_end_time'] = $endTime + ($order['num'] * 86400);//过期时间
        pdo_update(PDO_NAME."dating_member",$updateData,['id'=>$order['fkid']]);
        //红娘佣金生成
        if($memberInfo['matchmaker_id'] > 0) {
            [$nickname,$avatar] = self::handleUserInfo($memberInfo['mid']);
            self::handleMatchmakerCommissionInfo($memberInfo['matchmaker_id'],$params['fee'],"用户[{$nickname}]信息置顶佣金",$params['tid']);
        }
    }
    /**
     * Comment: 红娘入驻支付回调
     * Author: zzw
     * Date: 2021/4/1 16:03
     * @param $params
     */
    public static function payDatingMatchmakerNotify($params) {
        global $_W;
        $order = pdo_get(PDO_NAME."order",['orderno' => $params['tid']],['plugin','id','fkid','fightstatus','num']);
        //更新订单
        $data            = ['status' => $params['result'] == 'success' ? 3 : 0];
        $data['paytype'] = $params['type'];
        if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
        $data['paytime'] = TIMESTAMP;
        pdo_update(PDO_NAME.'order',$data,['id' => $order['id']]);
        //红娘入驻支付回调
        $matchmaker = pdo_get(PDO_NAME."dating_matchmaker",['id'=>$order['fkid']]);
        if($matchmaker){
            $set = Setting::wlsetting_read('dating_set');
            //根据设置判断状态
            if($set['matchmaker_examine'] == 1) $updateData['status'] = 2;//需要审核   待审核
            else $updateData['status'] = 3;//不用审核审核    已通过

            $res = pdo_update(PDO_NAME."dating_matchmaker",$updateData,['id'=>$order['fkid']]);
            //需要审核  支付成功后通知管理员进行审核操作
            if($res && $updateData['status'] == 2){
                [$nickname,$avatar] = Dating::handleUserInfo($matchmaker['mid']);
                $first   = '有新的红娘需要进行审核!';//消息头部
                $content = '您好，用户['.$nickname.']申请成为红娘，请尽快进行审核!';//业务内容
                $type    = "红娘审核";//业务类型
                $status  = "待审核";//处理结果
                $remark  = "请尽快处理!";//备注信息
                $time    = time();//操作时间

                News::noticeAgent('dating_matchmaker_examine',-1,$first,$type,$content,$status,$remark,$time);
            }


        }
    }
    /**
     * Comment: 开通会员支付回调
     * Author: zzw
     * Date: 2021/4/1 16:28
     * @param $params
     */
    public static function payDatingVipNotify($params) {
        global $_W;
        $order = pdo_get(PDO_NAME."order",['orderno' => $params['tid']],['plugin','id','fkid','fightstatus','num','mid']);
        //更新订单
        $data            = ['status' => $params['result'] == 'success' ? 3 : 0];
        $data['paytype'] = $params['type'];
        if ($params['tag']['transaction_id']) $data['transid'] = $params['tag']['transaction_id'];
        $data['paytime'] = TIMESTAMP;
        pdo_update(PDO_NAME.'order',$data,['id' => $order['id']]);
        //用户信息置顶/置顶续费 操作
        self::handleVipInfo($order['fkid'],$order['mid'],$params['fee']);
        $matchmakerId = pdo_getcolumn(PDO_NAME."dating_member",['mid'=>$order['mid']],'matchmaker_id');
        //红娘佣金生成
        if($matchmakerId > 0) {
            [$nickname,$avatar] = self::handleUserInfo($order['mid']);
            self::handleMatchmakerCommissionInfo($matchmakerId,$params['fee'],"用户[{$nickname}]开通会员卡佣金",$params['tid']);
        }

    }

    /**
     * Comment: 同步数据
     * Author: wlf
     * Date: 2022/05/23 10:30
     * @return
     */
    public function synInfo($now,$status){
        global $_W;
        if($status == 'marital_status'){
            switch ($now){
                case '1':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>1,'title' => '未婚'),'id');break;
                case '2':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>1,'title' => '离异(无子女)'),'id');break;
                case '3':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>1,'title' => '离异(有抚养权)'),'id');break;
                case '4':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>1,'title' => '离异(无抚养权)'),'id');break;
                case '5':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>1,'title' => '丧偶(无子女)'),'id');break;
                case '6':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>1,'title' => '丧偶(有子女)'),'id');break;
                default:$new = 0;break;
            }
        }else if($status == 'education'){
            switch ($now){
                case '1':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>2,'title' => '小学'),'id');break;
                case '2':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>2,'title' => '初中'),'id');break;
                case '3':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>2,'title' => '高中/中专'),'id');break;
                case '4':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>2,'title' => '专科'),'id');break;
                case '5':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>2,'title' => '本科'),'id');break;
                case '6':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>2,'title' => '硕士'),'id');break;
                case '7':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>2,'title' => '博士'),'id');break;
                default:$new = 0;break;
            }
        }else if($status == 'registered_residence_type'){
            switch ($now){
                case '1':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>3,'title' => '农业户口'),'id');break;
                case '2':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>3,'title' => '非农业户口'),'id');break;
                default:$new = 0;break;
            }
        }else if($status == 'live'){
            switch ($now){
                case '1':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>4,'title' => '自购房(有贷款)'),'id');break;
                case '2':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>4,'title' => '自购房(无贷款)'),'id');break;
                case '3':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>4,'title' => '租房(合租)'),'id');break;
                case '4':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>4,'title' => '租房(整租)'),'id');break;
                case '5':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>4,'title' => '与父母同住'),'id');break;
                case '6':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>4,'title' => '借住亲朋家'),'id');break;
                case '7':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>4,'title' => '单位住房'),'id');break;
                default:$new = 0;break;
            }
        }else if($status == 'travel'){
            switch ($now){
                case '1':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>5,'title' => '未购车'),'id');break;
                case '2':$new = pdo_getcolumn(PDO_NAME.'dating_option',array('uniacid'=>$_W['uniacid'],'type'=>5,'title' => '已购车'),'id');break;
                default:$new = 0;break;
            }
        }
        if(empty($new)){
            $new = $now;
        }
        return $new;
    }


    /**
     * Comment: 计划任务
     * Author: zzw
     * Date: 2021/4/1 16:37
     */
    public function doTask() {
        global $_W;
        //修改所有已过期的置顶
        pdo_update(PDO_NAME."dating_member",['is_top'=>1],['top_end_time <='=>time()]);
    }


}
