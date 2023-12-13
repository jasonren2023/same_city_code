<?php
defined('IN_IA') or exit('Access Denied');

class WlsignModuleUniapp extends Uniapp {
    /**
     * Comment: 签到信息初始化
     * Author: zzw
     * Date: 2019/8/15 14:55
     */
    public function getSignInfo(){
        global $_W,$_GPC;
        $settings = $_W['wlsetting']['wlsign'];
        $jifentext = $_W['wlsetting']['trade']['credittext']? $_W['wlsetting']['trade']['credittext'] : '积分';
        if($settings['signstatus'] == 0 || empty($settings['signstatus'])) {
            $this->renderError('签到系统已关闭!',['url'=>h5_url('pages/mainPages/index/index')]);
        }
        #1、获取基本设置信息
        //幻灯片列表
        $data['adv_list'] = $settings['adv'];
        if(is_array($data['adv_list'])){
            foreach ($data['adv_list'] as $key => &$val) {
                $val = [
                    'link' => $val['data_url'],
                    'thumb' => tomedia($val['data_img'])
                ];
            }
        }
        //是否开启自动签到
        $data['is_auto'] = $settings['autosign'];//1=开启自动签到；0=关闭自动签到
        //连续签到奖励列表
        if(is_array($settings['totalreward']) && count($settings['totalreward']) > 0){
            $timesTotal     = array_column($settings['totalreward'] , 'total');//连续签到天数
            $timesTotalKeys = array_flip($timesTotal);//连续签到天数数组 键&值互换后的数组
            $timesReward    = array_column($settings['totalreward'] , 'reward');//连续签到奖励
        }
        //获取特殊奖励列表
        if(is_array($settings['specialreward']) && count($settings['specialreward']) > 0){
            foreach ($settings['specialreward'] as $reK => $reV){
                $specialReward[$reK]['date']   = date("Ymd" , $reV['signtime']);
                $specialReward[$reK]['reward'] = $reV['special'];
                $specialReward[$reK]['title']  = $reV['signtitle'];
            }
            $specialDate     = array_column($specialReward , 'date');//特殊奖励日期
            $specialDateKeys = array_flip($specialDate);//特殊奖励日期数组 键&值互换后的数组
        }
        #2、获取用户签到信息
        $data['member'] = Wlsign::querySignmember('id,mid,nickname,avatar,times');
        $data['member']['avatar'] = tomedia($data['member']['avatar']);
        $data['member']['totaltimes'] = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename(PDO_NAME."signrecord")." WHERE mid = {$_W['mid']} AND uniacid = {$_W['uniacid']} ");
        //判断用户昨天是否签到  正常签到连续签到记录有效 未签到连续签到归零
        $Yesterday = Wlsign::queryRecord('*',date("Ymd",strtotime("-1 Day")));
        $dayDate = date("Ymd");
        $have = Wlsign::queryRecord('*',$dayDate);//判断今天是否签到
        $day = date("j");
        if(!($Yesterday || ($day == 1 && $settings['cycletype'] == 2)) && !$have) {
            //前一天未签到  或者签到周期为自然月并且当前为1号
            $data['member']['times'] = 0;//累计天数归零
        }
        #3、查看今天是否签到
        $dates = date("Ymd");
        $daySign = Wlsign::queryRecord('*',$dates);
        $data['is_sign'] = $daySign ? 1 : 0 ;
        #4、获取签到总数
        $todayTime         = date("Ymd");
        $yesterdayTime     = date("Ymd" , strtotime("-1 Day"));
        $today             = Wlsign::getTotal(" date = '{$todayTime}' ");//今日签到总数
        $yesterday         = Wlsign::getTotal(" date = '{$yesterdayTime}' ");//昨日签到总数
        $data['today']     = ( $today ? $today : 0 ) + ( $settings['signnum'] ? $settings['signnum'] : 0 );//今日签到总人数 = 实际签到人数 + 虚拟签到人数
        $data['yesterday'] = ( $yesterday ? $yesterday : 0 ) + ( $settings['signnum'] ? $settings['signnum'] : 0 );//昨日签到总人数 = 实际签到人数 + 虚拟签到人数
        #5、获取本月签到记录
        $stertTime = strtotime(date("Y-m",time())."-1 00:00:00");
        $endTime = strtotime(date("Y-m-t",time())." 23:59:59");
        $where = " WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} AND createtime > {$stertTime} AND createtime < {$endTime}";
        $signMonth = pdo_fetchall("SELECT FROM_UNIXTIME(createtime,'%d') as date_time FROM " . tablename(PDO_NAME."signrecord") .$where." ORDER BY createtime ASC");
        $signList = array_column($signMonth,'date_time');
        #6、获取活动规则
        $data['detail'] = empty($settings['detail']) ? '<p>暂无规则!</p>' : htmlspecialchars_decode($settings['detail']);
        #7、处理签到信息
        $t = date("t");
        $d = date("d");
        $signInfo = [];
        //获取用户签到信息 判断获取用户连续签到天数
        $member = Wlsign::querySignmember('times');
        $times = $member['times'] ? : 0;
        $Yesterday = Wlsign::queryRecord('*',date("Ymd",strtotime("-1 Day")));
        if(!$Yesterday) $times = 0;//累计天数归零
        for($i=1;$i<=$t;$i++){
            //当天信息初始化
            $signInfo[$i] = [
                'date'    => $i,
                'is_sign' => 0 ,//是否签到：0=未签到(已过期【灰色背景】)，1=已签到【黄色背景】；2=未签到（未过期【白色背景】）
                'times'   => '' ,//连续签到信息
                'reward'  => '' ,//特殊奖励信息
            ];
            //判断当天是否签到
            if (in_array($i,$signList)) $signInfo[$i]['is_sign'] = 1;
            //判断是否过期
            if($i > $d) $signInfo[$i]['is_sign'] = 2;
            //判断额外奖励信息
            if($i >= $d){
                //连续签到奖励信息
                if(is_array($timesTotal) && count($timesTotal) > 0){
                    $times++;
                    //判断今天是否签到，判断当天是否存在连续签到奖励
                    if($data['is_sign'] && $Yesterday){
                        //今天已经签到
                        $newTimes = $times - 1;
                        if(in_array($newTimes,$timesTotal) && $i != $d) {
                            $signInfo[$i]['times'] = '连续签到'.$newTimes.'天'.','.$jifentext.'奖励'.$timesReward[$timesTotalKeys[$newTimes]];
                        }
                    }else{
                        //今天未签到
                        if(in_array($times,$timesTotal)) {
                            $signInfo[$i]['times'] = '连续签到'.$times.'天'.','.$jifentext.'奖励'.$timesReward[$timesTotalKeys[$times]];
                        }
                    }
                }
                //判断特殊奖励信息
                if(is_array($specialDate) && count($specialDate) > 0){
                    $day = date("Ymd",strtotime(date("Y-m-".$i)));
                    if(in_array($day,$specialDate)){
                        $dayKey = $specialDateKeys[$day];
                        $signInfo[$i]['reward'] = $specialReward[$dayKey]['title'].',额外奖励'.$specialReward[$dayKey]['reward'];
                    }
                }
            }
        }
        #8、签到信息补丁 在签到信息数组中添加前置空位
        //获取当月一号的星期时间
        $w = date("w",$stertTime);
        $null = [];
        if($w > 0){
            for($i=0;$i<$w;$i++){
                $null[$i] = [
                    'date'    => '',
                    'is_sign' => 3 ,//是否签到：0=未签到(已过期【灰色背景】)，1=已签到【黄色背景】；2=未签到（未过期【白色背景】）；3=空位
                    'times'   => '' ,//连续签到信息
                    'reward'  => '' ,//特殊奖励信息
                ];
            }
        }
        $signInfo = array_merge($null,$signInfo);

        $data['sign_info'] = $signInfo;

        $this->renderSuccess('签到信息初始化',$data);
    }
    /**
     * Comment: 进行签到
     * Author: zzw
     * Date: 2019/8/16 9:28
     */
    public function signIn(){
        global $_W,$_GPC;
        #1、基本参数信息获取
        $jifentext = $_W['wlsetting']['trade']['credittext']? $_W['wlsetting']['trade']['credittext'] : '积分';
        $dayDate = date("Ymd");
        $settings = $_W['wlsetting']['wlsign'];
        #2、判断是否已经签到
        $have = Wlsign::queryRecord('*',$dayDate);
        if($have) $this->renderError("今天已经签到了哟");
        #3、获取用户签到信息
        $member = Wlsign::querySignmember('*');
        //判断用户昨天是否签到  正常签到连续签到记录有效 未签到连续签到归零
        $Yesterday = Wlsign::queryRecord('*',date("Ymd",strtotime("-1 Day")));
        $day = date("j");
        if(!$Yesterday || ($day == 1 && $settings['cycletype'] == 2)) {
            //前一天未签到  或者签到周期为自然月并且当前为1号
            $member['times'] = 0;
        }//累计天数归零
        $member['times']++;//累计天数加一
        #4、判断是否为首次签到 不是首次签到判断是否存在连续签到奖励  不存在则是日常签到奖励
        $is_first = pdo_getcolumn(PDO_NAME."signrecord",['uniacid'=>$_W['uniacid'],'mid'=>$_W['mid']],'id');
        if(!$is_first){
            //当前签到是首次签到
            $integral = $settings['first'];
            $title = '首次签到奖励';
        }else{
            //当前签到不是首次签到  判断是否存在连续签到奖励  不存在则是日常签到奖励
            $keyList = array_column($settings['totalreward'],'total');//键值
            $rewardList = array_combine($keyList,$settings['totalreward']);
            $continuity = $rewardList[$member['times']];
            if(is_array($continuity)){
                //连续签到奖励
                $integral = $continuity['reward'];
                $title = $continuity['total'].'次连续签到奖励';
            }else{
                //日常奖励
                $integral = $settings['daily'];
                $title = '日常奖励';
            }
        }
        #5、判断是否存在特殊奖励
        $festivalReward  = $settings['specialreward'];
        if(is_array($festivalReward) && count($festivalReward) > 0){
            $timeList = array_column($festivalReward,'signtime');
            $todayTime = strtotime(date("Y-m-d")." 00:00:00");
            $festivalList = array_combine($timeList,$festivalReward);
            $festivalRewardInfo = $festivalList[$todayTime];//特殊奖励信息
            if($festivalRewardInfo){
                $integral += $festivalRewardInfo['special'];
                $title .= '+'.$festivalRewardInfo['signtitle'].'奖励';
            }
        }
        MysqlFunction::setTrans(4);
        MysqlFunction::startTrans();
        #6、添加签到记录数据
        $data = [
            'uniacid'    => $_W['uniacid'] ,
            'mid'        => $_W['mid'] ,
            'date'       => $dayDate ,
            'createtime' => time() ,
            'reward'     => $integral ,
            'sign_class' => $title
        ];
        $res = pdo_insert(PDO_NAME.'signrecord',$data);
        if(!$res){
            MysqlFunction::rollback();
            $this->renderError('签到失败，请刷新重试!');
        }
        #6、修改用户签到统计信息记录表
        $updateData['times']        = $member['times'] ? : 0;//累计(连续)签到天数
        $updateData['integral']     = ($member['integral'] + $integral) ? : 0;//积分总数
        $updateData['totaltimes']   = ($member['totaltimes'] + 1) ? : 0;//总共签到天数
        $where['uniacid'] = $_W['uniacid'];
        $res = Wlsign::updateSignmember($updateData,$where);
        if(!$res){
            MysqlFunction::rollback();
            $this->renderError('签到失败，请刷新重试!');
        }
        MysqlFunction::commit();
        #6、更新积分信息
        Member::credit_update_credit1($_W['mid'],$integral,'签到:'.$title);
        #7、积分签到模板消息通知
        $url = h5_url('pages/subPages/signdesk/record/record');
        $modelData = [
            'first'     => '签到通知' ,
            'user_name' => $member['nickname'] ,//用户名
            'total'     => $updateData['totaltimes'] ,//累计签到次数
            'remark'    => '签到成功，获得'.$jifentext.'：'.$integral,
            'sign_time' => date("Y-m-d H:i:s",$data['createtime']),
        ];
        TempModel::sendInit('sign',$_W['mid'],$modelData,$_W['source'],$url);
        #8、获取返回的数据信息  第多少签到   获取的积分
        $dayNum = Wlsign::getTotal(" date = '{$dayDate}' ");//今日签到总数
        $returnData['orders'] = ($dayNum?$dayNum:0) + ($_W['wlsetting']['wlsign']['signnum']?$_W['wlsetting']['wlsign']['signnum']:0);
        $returnData['integral'] = $integral;

        $this->renderSuccess('签到成功',$returnData);
    }
    /**
     * Comment: 获取签到排行榜信息
     * Author: zzw
     * Date: 2019/8/16 11:37
     */
    public function rankingList(){
        global $_W,$_GPC;
        #1、参数获取
        $type = $_GPC['type'] ? $_GPC['type'] : 1;//排行方式：1=连续签到排行（最执着）；2=总签到排行（签到狂）；3=签到积分排行（最富有）
        #2、获取信息列表
        switch ($type){
            case 1:$data = Wlsign::getRankingList('times');break;//连续签到排行
            case 2:$data = Wlsign::getRankingList('totaltimes');break;//总签到排行
            case 3:$data = Wlsign::getRankingList('integral');break;//签到积分排行
            default:
                $this->renderError("错误的排行方式");
                break;
        }
        #3、获取设置信息
        $data['set'] = [
            'integral' => $_W['wlsetting']['trade']['credittext'],
        ];


        $this->renderSuccess('签到排行信息',$data);
    }
    /**
     * Comment: 获取签到记录
     * Author: zzw
     * Date: 2019/8/26 10:34
     */
    public function recordList(){
        global $_W,$_GPC;
        #1、参数获取
        $page = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $table = tablename(PDO_NAME."signrecord");
        $where = " WHERE mid = {$_W['mid']} AND uniacid = {$_W['uniacid']} ";
        #2、获取统计信息
        $data['info'] = pdo_fetch("SELECT COUNT(*) as total,SUM(reward) as integral FROM ".$table.$where);
        $data['total'] = ceil($data['info']['total'] / $pageIndex);
        #3、获取记录列表
        $data['list'] = pdo_fetchall("SELECT FROM_UNIXTIME(createtime,'%Y-%m-%d %H:%i:%S') as createtime,reward,sign_class FROM "
            .$table.$where." ORDER BY createtime DESC LIMIT {$pageStart},{$pageIndex} ");

        $this->renderSuccess('签到记录',$data);
    }





}
