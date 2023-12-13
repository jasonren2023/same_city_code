<?php
defined('IN_IA') or exit('Access Denied');

class DistributionModuleUniapp extends Uniapp {

    public function __construct(){
        global $_W,$_GPC;
        //同步调用父类的构造函数
        parent::__construct();
        //判断是否为分销商
        $isDis = pdo_get(PDO_NAME."distributor",['mid'=>$_W['mid'],'disflag IN'=>[1,-1],'uniacid'=>$_W['uniacid']]);
        if(!$isDis && !in_array($_GPC['do'],['applyCondition','choselead','disApply'])) $this->renderError("请先成为分销商！",['type'=>1,'url'=>"pages/subPages/dealer/apply/apply"]);

    }
    /**
     * Comment: 分销申请条件获取
     * Author: zzw
     * Date: 2019/7/16 9:20
     */
    public function applyCondition() {
        global $_W, $_GPC;
        #1、判断分销商功能是否开启
        if ($this->disSetting['switch'] == 0) $this->renderError('未开启相关功能');
        #2、获取申请分销商申请说明
        $data['appdetail'] = htmlspecialchars_decode($this->disSetting['appdetail']);
        #3、获取提现列表
        $data['rollstatus'] = $this->disSetting['rollstatus'];//1=真实数据；2=虚拟数据；3=关闭
        $data['choselead'] = $this->disSetting['choselead']; //1=可以修改上级 0=关闭
        if($data['choselead']>0){
            $leadid = pdo_getcolumn(PDO_NAME.'distributor',array('mid'=>$_W['mid']),'leadid');
            $data['leaduser'] = pdo_get('wlmerchant_member',array('id' => $leadid),array('id','nickname','avatar'));
            if(!empty($data['leaduser'])){
                $data['leaduser']['avatar'] = tomedia($data['leaduser']['avatar']);
            }
        }
        if(empty($data['leaduser'])){
            $data['leaduser'] = [];
        }
        if ($this->disSetting['rollstatus'] == 1) {
            //获取真实的提现数据
            $list = pdo_fetchall("SELECT mid,sapplymoney FROM " . tablename(PDO_NAME . 'settlement_record')
                . " WHERE uniacid = {$_W['uniacid']} AND `type` = 3 AND sapplymoney > 1  ORDER BY applytime DESC limit 20");
            foreach ($list as $key => &$va) {
                $va['nickname'] = pdo_getcolumn(PDO_NAME . 'member', array('id' => $va['mid']), 'nickname');
                $va['nickname'] = mb_substr($va['nickname'], 0, 1, 'utf-8') . '***' . mb_substr($va['nickname'], -1, 1, 'utf-8');
                $va['applymoney'] = sprintf("%.2f", $va['sapplymoney']);
                unset($va['mid']);
                unset($va['sapplymoney']);
            }
        } else if ($this->disSetting['rollstatus'] == 2) {
            //获取虚拟的提现数据  保证每天的提现信息不一致
            $list = Cache::getCache('syssetting', 'cash_withdrawal_list');
            if (!$list) {
                //文本字符串定义
                $string = '半夜时分一个人躺在床上四处静谧无声有一种孤独的感觉如爬虫般悄悄爬上我的心头辗转反侧无法入眠轻轻起来戴上耳机听音乐打开书本曾经看见过这样一句话有一种心情叫';
                $string .= '无助有一种美丽叫孤独对耐不住寂寞的人来说孤独是可怕的是恐惧的而对我来说孤独是生命圆满的开始是一种静美不喧嚣不繁华是在静谧中独享一个人的清欢你看那独钓寒江雪的柳宗元是';
                $string .= '孤独的在下着大雪的江面上一叶小舟一个老渔翁独自在寒冷的江心垂钓天地之间是如此纯洁而寂静只剩下他一个人与万物共谋一尘不染万籁无声这清高孤傲的渔翁正是柳宗元自己在政治上';
                $string .= '失意郁闷和苦恼时隐居在山水之间寄托自己清高而孤傲情感的真实写照他的幽静过于孤独过于冷清不带一点人间烟火的气味其实人人骨子里皆有一份别人无法理解也无法自拔的孤独只是很';
                $string .= '多时候这孤独总会被周遭的喧嚣浮华所蒙蔽以致造成繁荣的假象殊不知不理会这种孤独在某种意义上而言我们便不算真正活过或许我们本就应该学会享受孤独在孤独的时候你才可以听到自';
                $string .= '己的心跳和呼吸寻找到迷失的自我知不觉我也喜欢上了孤独我的孤独和风月无关和苦闷无关有着浓浓的烟火气息只是一种一个人独处时的欢喜我喜欢孤独不与任何人说话在一份静谧中安然';
                $string .= '地做自己喜欢的事任身心徜徉暂时忘却柴米油盐酱醋茶的烦琐去体验琴棋书画诗酒花的高雅暂时抛开追名逐利的忙碌奔波去感受心无杂念的宁静淡泊暂时摆脱困扰你的喜怒哀乐去体味生活';
                $string .= '中的充实祥和于是体会孤独感受孤独不失为一种最佳的休闲身体可以在孤独中得到休养繁重的体力超负的劳动使身体需要有一份适时的孤独来调养心灵可以在孤独中寻找到一份难得的宁静';
                $string .= '不再为生活中尔虞我诈的争斗而烦恼不再为日常生活的重负而苦闷而在孤独中寻找适合调整心情的方式让心情在孤独中拥有一份独特的享受孤独的最高修为莫过于在孤独中创造亦或是读读';
                $string .= '古今写写心声种种花草多一份孤独的快乐少一份无为的浪费让生命在富有创造精神的孤独中度过让生命时光的每一分每一秒不至于虚度在孤独中拥有了自己的一切你会觉得你一点也不孤独';
                $string .= '于是你就会明白能够真正拥有孤独的人是世界上最幸福的人是的我就是这样享受孤独因为我只是万千世界中一株最不起眼的小草静是我的姿态淡是我的心境孤独是我的享受孤独是一种境界';
                $string .= '它折射出一个人潜藏的能量孤独是一味珍宝它蕴涵着高贵的情愫和追求孤独是一场燃烧它灿烂的火光给人温暖和力量孤独是一份爱因为无爱的人不会孤独';
                //通过循环获取20组虚拟提现的数据
                $string = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
                $lowestmoney = $this->disSetting['lowestmoney'];
                $list = [];
                for ($i = 0; $i < 20; $i++) {
                    shuffle($string);
                    $list[$i]['nickname'] = $string[0] . '***' . $string[1];
                    $list[$i]['applymoney'] = sprintf("%.2f", rand(0, 500) + $lowestmoney);
                }
                Cache::setCache('syssetting', 'cash_withdrawal_list', $list);
            }
        }
        $data['list'] = $list;
        #4、用户是否已经申请
        $distributor = pdo_get(PDO_NAME . 'applydistributor', ['mid' => $_W['mid'], 'status' => 0, 'uniacid' => $_W['uniacid']]);//分销商申请信息表的信息
        $data['is_apply'] = is_array($distributor) && count($distributor) > 0 ? 1 : 0;
        #5、生成返回个人中心的链接
        $data['user_link'] = h5_url('pages/mainPages/userCenter/userCenter');
        #6、用户是否为分销商
        $distributorInfo = pdo_get(PDO_NAME . 'distributor', ['mid' => $_W['mid'], 'disflag' => array(1, -1), 'uniacid' => $_W['uniacid']]);//分销商申请信息表的信息
        $data['is_dis'] = is_array($distributorInfo) && count($distributorInfo) > 0 ? 1 : 0;
        if ($data['is_dis']) {
            $data['is_apply'] = 1;
        }//用户已经是分销商时则默认已申请
        //获取幻灯片设置信息
        $set = Setting::wlsetting_read('distribution');
        $adv = $set['apply_adv'] ? : [];
        foreach($adv as &$advItem){
            $advItem = tomedia($advItem);
        }
        $data['adv'] = $adv ? : [];//幻灯片
        $data['bg_color'] = $set['apply_bgcolor'] ? : '#FF4444';//背景颜色
        #7、获取最近的申请信息 申请状态 0待审核 1已通过 2被驳回
        $info = pdo_fetch("SELECT id,status,reason FROM " . tablename(PDO_NAME . "applydistributor")
            . " WHERE mid = {$_W['mid']} ORDER BY createtime DESC ");
        //858定制 累计金额进度条
        $data['appdis'] = $set['appdis'];
        if($data['appdis'] == 5){
            $data['totallmoney'] = $set['totallmoney'];
            $data['nowmoney'] = Distribution::getNowMoney($_W['mid']);
        }


        if ($info['status'] == 2) {
            $data['status'] = $info['status'];
            $data['reason'] = $info['reason'];

            $this->renderSuccess('申请驳回信息', $data);
        }

        $this->renderSuccess('申请条件信息', $data);
    }
    /**
     * Comment: 申请成为分销商
     * Author: zzw
     * Date: 2019/7/19 11:24
     */
    public function disApply() {
        global $_W, $_GPC;
        #1、基本设置信息获取
        $base = $this->disSetting;//分销商设置信息
        $rank = $_GPC['rank'] ? $_GPC['rank'] : 1;
        $distributor = pdo_get(PDO_NAME . 'distributor', ['mid' => $_W['mid'], 'uniacid' => $_W['uniacid']]);//分销商信息表的信息
        $head_id = $_GPC['choseleadid'];
        if(empty($head_id)){
            $head_id = $distributor['leadid'] ? $distributor['leadid'] : 0;
        }
        $member = pdo_get(PDO_NAME . 'member', ['id' => $_W['mid']], array('mobile', 'nickname', 'realname'));//用户信息
        #2、信息判断
        //判断手机号是否绑定
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($member['mobile']) && in_array('distribution', $mastmobile)) $this->renderError('您未绑定手机,请先绑定',array('mustmobile'=>1));
        //判断是否开启分销商功能
        if ($base['switch'] == 0 || $base['appdis'] == 0) $this->renderError('申请已关闭');
        //判断是否已经是分销商
        $isDis = pdo_fetchcolumn("SELECT id FROM " . tablename(PDO_NAME . "distributor")
            . " WHERE mid = {$_W['mid']} AND disflag IN (1,-1) AND uniacid = {$_W['uniacid']}  ");
        if ($isDis) $this->renderError('申请失败，请勿重复申请');
        #3、判断是否为重复申请
        $appflag = pdo_getcolumn(PDO_NAME . 'applydistributor', ['mid' => $_W['mid'], 'status' => 0, 'uniacid' => $_W['uniacid']], 'id');
        if ($appflag) $this->renderError('请勿重复申请');
        #4、删除以前的申请信息
        pdo_delete(PDO_NAME . "applydistributor", ['mid' => $_W['mid'], 'uniacid' => $_W['uniacid']]);
        #4、基本信息生成
        $data = array(
            'uniacid'    => $_W['uniacid'],
            'aid'        => $_W['aid'],
            'mid'        => $_W['mid'],
            'realname'   => $member['realname'] ? $member['realname'] : $member['nickname'],
            'mobile'     => $member['mobile'],
            'createtime' => time(),
            'leadid'     => $head_id
        );
        #5、判断分销情况
        if ($base['mode'] == 1 && $rank == 2) {
            //渠道分销  二级
            $examine = $base['twoexamine'];//二级分销 —— 是否需要审核(0=不需要；1=需要)
            $applymoney = $base['twoapplymoney'];//二级分销 —— 成为分销商需要支付的费用
            $appdis = $base['twoappdis'];//二级分销 —— 成为分销商条件(0=关闭;1=申请;2=开通一卡通;3=付费)
        } else {
            //其他
            $examine = $base['examine'];//一级分销 —— 是否需要审核(0=不需要；1=需要)
            $applymoney = $base['applymoney'];//一级分销 —— 成为分销商需要支付的费用
            $appdis = $base['appdis'];//一级分销 —— 成为分销商条件(0=关闭;1=申请;2=开通一卡通;3=付费;4=用户即分销)
        }
        #5、生成并且添加分销商申请信息
        $url = h5_url('pages/subPages/dealer/index/index');
        if ($appdis == 2) {
            //申请条件为开通一卡通
            $flag = Member::checkhalfmember();
            if ($flag) {
                if ($examine == 1) {
                    //需要审核
                    $data['status'] = 0;
                    $data['rank'] = $rank;
                    $res = pdo_insert(PDO_NAME . 'applydistributor', $data);
                    if ($res) {
                        Distribution::toadmin($data['realname']);
                        $this->renderSuccess('申请成功,请等待审核', ['state' => 3]);//需要审核 跳转个人中心
                    } else {
                        $this->renderError('申请失败，请稍后重试');
                    }
                } else {
                    //不需要审核
                    if ($distributor && $distributor['disflag'] == 0) {
                        //在分销表中存在信息 判断是否为下线 进行对应的操作
                        if ($rank == 1 && $base['mode']) {
                            //渠道分销 一级分销申请
                            $res = pdo_update(PDO_NAME . 'distributor', array('disflag' => 1,'updatetime' => time() ,'leadid' => $head_id, 'lockflag' => 0, 'realname' => $member['realname'], 'mobile' => $member['mobile']), array('id' => $distributor['id']));
                        } else {
                            //其他申请
                            $res = pdo_update(PDO_NAME . 'distributor', array('disflag' => 1,'updatetime' => time(), 'lockflag' => 0, 'leadid' => $head_id), array('id' => $distributor['id']));
                        }
                        $disid = $distributor['id'];
                    } else {
                        //分销表中不存在信息 或者存在的信息不为下线  则生成添加分销商信息
                        $data['disflag'] = 1;
                        $data['realname'] = $member['realname'] ? $member['realname'] : $member['nickname'];
                        $data['leadid'] = $head_id;
                        $res = pdo_insert(PDO_NAME . 'distributor', $data);//储存分销信息
                        $disid = pdo_insertid();
                        pdo_update(PDO_NAME . 'member', ['distributorid' => $disid], ['id' => $_W['mid']]);//修改用户表信息
                    }
                    if ($res) {
                        Distribution::distriNotice($_W['mid'], $url, 1);//发送模板消息

                        if($head_id > 0) Distribution::distriNotice($head_id, '', 2,$disid);//发送模板消息
                        $this->renderSuccess('申请成功！', ['state' => 1]);
                    } else {
                        $this->renderError('申请失败，请稍后重试');
                    }
                }
            } else {
                $data         = Setting::wlsetting_read('trade');
                $halfcardtext = $data['halfcardtext'] ? $data['halfcardtext'] : '一卡通';

                $this->renderError("申请失败,请先开启{$halfcardtext}",['state' => 4]);
            }
        } else if ($appdis == 3) {
            //申请条件为付费开通
            $orderData = array(
                'uniacid'    => $_W['uniacid'],
                'aid'        => $_W['aid'],
                'mid'        => $_W['mid'],
                'fkid'       => 0,
                'sid'        => 0,
                'status'     => 0,
                'paytype'    => 0,
                'createtime' => time(),
                'orderno'    => createUniontid(),
                'price'      => $applymoney,
                'num'        => 1,
                'plugin'     => 'distribution',
                'payfor'     => 'applydis',
                'specid'     => $rank,  //申请层级
                'recordid'   => $head_id
            );
            $res = pdo_insert(PDO_NAME . 'order', $orderData);
            if ($res) {
                $orderid = pdo_insertid();
                if ($orderid) {
                    $this->renderSuccess('申请成功，请支付', ['state' => 2, 'url' => h5_url('pages/mainPages/payment/payment', ['orderid' => $orderid, 'plugin' => 'distribution'])]);//需要支付 跳转支付页面
                } else {
                    $this->renderError('申请失败,请稍后重试');
                }
            } else {
                $this->renderError('申请失败,请稍后重试');
            }
        } else {
            if($appdis == 5){
                $nowmoney = Distribution::getNowMoney($_W['mid']);
                if($nowmoney < $base['totallmoney'] ){
                    $this->renderError('累计消费金额不足，无法成为分销商');
                }
            }
            //申请条件为 直接申请即可
            if ($examine == 1) {
                //需要进行审核
                $data['status'] = 0;
                $data['rank'] = $rank;
                $res = pdo_insert(PDO_NAME . 'applydistributor', $data);
                if ($res) {
                    Distribution::toadmin($data['realname']);//发送模板消息通知
                    $this->renderSuccess('申请成功，请等待审核', ['state' => 3]);//需要审核
                } else {
                    $this->renderError('申请失败，请稍后重试');
                }
            } else {
                //不需要进行审核
                if ($distributor && $distributor['disflag'] == 0) {
                    //在分销表中存在信息 判断是否为下线 进行对应的操作
                    if ($rank == 1 && $base['mode']) {
                        //渠道分销 一级分销申请
                        $res = pdo_update(PDO_NAME . 'distributor', ['disflag' => 1,'updatetime' => time(), 'leadid' => $head_id, 'lockflag' => 0, 'realname' => $member['realname'], 'mobile' => $member['mobile']], ['id' => $distributor['id']]);
                    } else {
                        //其他申请
                        $res = pdo_update(PDO_NAME . 'distributor', ['disflag' => 1,'updatetime' => time(), 'lockflag' => 0, 'leadid' => $head_id], ['id' => $distributor['id']]);
                    }
                    $disid = $distributor['id'];
                } else {
                    //分销表中不存在信息 或者存在的信息不为下线  则生成添加分销商信息
                    $data['disflag'] = 1;
                    $data['realname'] = $member['realname'] ? $member['realname'] : $member['nickname'];
                    $data['leadid'] = $head_id;
                    $res = pdo_insert(PDO_NAME . 'distributor', $data);//储存分销信息
                    $disid = pdo_insertid();
                    pdo_update(PDO_NAME . 'member', array('distributorid' => $disid), array('id' => $_W['mid']));//修改用户表信息
                }
                if ($res) {
                    Distribution::distriNotice($_W['mid'], $url, 1);//发送模板消息
                    if($head_id > 0) Distribution::distriNotice($head_id, '', 2,$disid);//发送模板消息
                    $this->renderSuccess('申请成功！', ['state' => 1]);
                } else {
                    $this->renderError('申请失败，请稍后重试');
                }
            }
        }
    }
    /**
     * Comment: 获取分销商详细信息
     * Author: zzw
     * Date: 2019/7/16 14:53
     */
    public function distributorInfo() {
        global $_W, $_GPC;
        $mid = $_W['mid'];
        #1、判断当前用户是否为分销商
        $field = 'id,leadid,dismoney,disflag,nowmoney,nickname,realname,dislevel,mobile,groupflag,shareholder,grouplevel,sharetime';
        $info = pdo_fetch("SELECT {$field} FROM " . tablename(PDO_NAME . "distributor")
            . " WHERE mid = {$mid} AND disflag IN (1,-1) AND uniacid = {$_W['uniacid']} ");
        if (!$info){
            if(Customized::init('distributionText') > 0){
                $this->renderSuccess('请先成为共享股东', ['type' => 1]);
            }else{
                $this->renderSuccess('请先成为分销商', ['type' => 1]);
            }
        }
        Distribution::checkup($info['id']);
        $info['dislevel'] = pdo_getcolumn(PDO_NAME.'distributor',array('id'=>$info['id']),'dislevel');
        #2、获取推荐人信息
        if ($info['leadid'] > -1) $info['recommender_name'] = pdo_fetchcolumn("SELECT nickname FROM " . tablename(PDO_NAME . "member") . " WHERE id = {$info['leadid']} ");
        #3、获取分销商等级
        if($info['disflag'] == -1){
            $info['level_name'] = '已过期';
            $info['overtimeflag'] = 1;
        }else{
            $info['dislevel'] = $info['dislevel'] > 0 ? $info['dislevel'] : pdo_getcolumn(PDO_NAME . 'dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), 'id');
            $levelinfo = pdo_get(PDO_NAME .'dislevel',array('id' => $info['dislevel']),array('name','plugin'));
            $levelName = $levelinfo['name'];
            $displugin = unserialize($levelinfo['plugin']);
            if(!empty($displugin)){
                $newplu = array_intersect(['rush','consumption','groupon','fightgroup','bargain','coupon'],$displugin);
                if(empty($newplu)){
                    $info['hidegoods'] = 1;
                }
            }
            $info['level_name'] = $levelName ? $levelName : '无等级';
			if($info['groupflag'] > 0){
				$info['group_level_name'] = pdo_getcolumn(PDO_NAME.'grouplevel',array('id'=>$info['grouplevel']),'name');
			}

        }
        #4、获取本日、本月的收益
        $dayWhere = $monthWhere = " a.status IN (1,2) AND type = 1 AND (a.oneleadid = {$info['id']} OR a.twoleadid = {$info['id']} OR a.threeleadid = {$info['id']}) ";
        $field = "distinct a.id,a.leadmoney,a.oneleadid,a.twoleadid,a.threeleadid";
        $dayMoney = 0;//当日收益
        $monthMoney = 0;//当月收益
        //获取本日的收益信息
        $dayStart = strtotime(date("Y-m-d"));//当天开始时间
        $dayEnd = strtotime(date("Y-m-d") . " 23:59:59");//当天结束时间
        $dayWhere .= " AND b.createtime > {$dayStart} AND b.createtime < {$dayEnd} ";
        $dayList = Distribution::getDisOrder($dayWhere, $field);
        if (is_array($dayList) && count($dayList) > 0) {
            foreach ($dayList as $dayK => $dayV) {
                $moneyArr = unserialize($dayV['leadmoney']);
                if ($dayV['oneleadid'] == $info['id'] && $moneyArr['one'] > 0) {
                    $dayMoney += $moneyArr['one'];
                } else if ($dayV['twoleadid'] == $info['id'] && $moneyArr['two'] > 0) {
                    $dayMoney += $moneyArr['two'];
                } else if ($dayV['threeleadid'] == $info['id'] && $moneyArr['three'] > 0) {
                    $dayMoney += $moneyArr['three'];
                } else {
                    $dayMoney += 0;
                }
            }
        }
        if (p('taxipay')) {
            $taxipayDayMoney = pdo_fetchcolumn("SELECT SUM(price) FROM " . tablename(PDO_NAME . "disdetail") . " WHERE plugin = 'taxipay' AND createtime > {$dayStart} AND createtime < {$dayEnd}  AND leadid = {$mid} AND uniacid = {$_W['uniacid']} ");
            $dayMoney += $taxipayDayMoney;
        }
        //获取本月的收益
        $monthStart = strtotime(date("Y-m") . "-1 ");//当月开始时间
        $monthEnd = strtotime(date('Y-m-t') . " 23:59:59");//当月结束时间
        $monthWhere .= " AND b.createtime > {$monthStart} AND b.createtime < {$monthEnd} ";
        $monthList = Distribution::getDisOrder($monthWhere, $field);
        if (is_array($monthList) && count($monthList) > 0) {
            foreach ($monthList as $monthK => $monthV) {
                $moneyArr = unserialize($monthV['leadmoney']);
                if ($monthV['oneleadid'] == $info['id'] && $moneyArr['one'] > 0) {
                    $monthMoney += $moneyArr['one'];
                } else if ($monthV['twoleadid'] == $info['id'] && $moneyArr['two'] > 0) {
                    $monthMoney += $moneyArr['two'];
                } else if ($monthV['threeleadid'] == $info['id'] && $moneyArr['three'] > 0) {
                    $monthMoney += $moneyArr['three'];
                } else {
                    $monthMoney += 0;
                }
            }
        }
        if (p('taxipay')) {
            $taxipayMonthMoney = pdo_fetchcolumn("SELECT SUM(price) FROM " . tablename(PDO_NAME . "disdetail") . " WHERE plugin = 'taxipay' AND createtime > {$monthStart} AND createtime < {$monthEnd}  AND leadid = {$mid} AND uniacid = {$_W['uniacid']} ");
            $monthMoney += $taxipayMonthMoney;
        }
        $info['day_money'] = $dayMoney > 0 ? sprintf("%.2f", $dayMoney) : sprintf("%.2f", 0);
        $info['month_money'] = $monthMoney > 0 ? sprintf("%.2f", $monthMoney) : sprintf("%.2f", 0);
        #5、获取当前分销商下级人数
        if ($_W['wlsetting']['distribution']['lockstatus'] == 1 && $_W['wlsetting']['distribution']['showlock']) {
            $info['team_total'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "distributor") . " WHERE leadid = {$mid} ");
        } else {
            $info['team_total'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "distributor") . " WHERE leadid = {$mid} AND lockflag = 0 ");
        }
		
		if($info['groupflag'] > 0){
			$info['group_team_total'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "distributor") . " WHERE onegroupid = {$mid} ");
		}
		
        #6、获取当前分销商的商户总数量
        $salesmanSet = Setting::agentsetting_read('salesman');
        if (p('salesman') && $salesmanSet['isopen'] > 0) {
            $shop_total = pdo_getall('wlmerchant_merchantuser',array('mid' => $_W['mid'],'enabled'=>1,'ismain'=>4),array('aid'));
            if(!empty($shop_total)){
                foreach ($shop_total as $key => &$shop){
                    $setting = pdo_get('wlmerchant_agentsetting',array('uniacid' => $_W['uniacid'],'aid'=>$shop['aid'],'key' => 'salesman'),array('value'));
                    $setting = unserialize($setting['value']);
                    if(empty($setting['isopen'])){
                        unset($shop_total[$key]);
                    }
                }
                $info['shop_total'] = count($shop_total);
                $info['shop_switch'] = $info['shop_total'] > 0 ? 1 : 0;
            }else{
                $info['shop_switch'] = 0;
            }
            $info['salemerchant'] = 1;
        }
        #7、获取当前分销商推广订单
        $extensionWhere = " uniacid = {$_W['uniacid']} AND (oneleadid = {$info['id']} OR twoleadid = {$info['id']} OR threeleadid = {$info['id']}) ";
        $info['order_total'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "disorder") . " WHERE {$extensionWhere} ");
		
		if($info['groupflag'] > 0){
			$info['group_order_total'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "disorder") . " WHERE uniacid = {$_W['uniacid']} AND (onegroupid = {$mid} OR twogroupid = {$mid})  ");
		}
		
		if($info['shareholder'] > 0){
			if(empty($info['sharetime'])){
				$info['sharetime'] = 0;
			}
			$info['share_order_total'] = pdo_fetchcolumn("SELECT COUNT(id) FROM " . tablename(PDO_NAME . "disorder") . " WHERE uniacid = {$_W['uniacid']} AND shareholdermoney > 0 AND createtime > {$info['sharetime']} ");
		}
		
        #9、删除多余的信息
        unset($info['leadid']);//上级id
        $info['avatar'] = $_W['wlmember']['avatar'] ?: '../addons/'.MODULE_NAME.'/web/resource/images/nopic.jpg';
        if (empty($info['nickname']) && !empty($info['realname'])) {
            $info['nickname'] = $info['realname'];
        } else if (empty($info['nickname']) && !empty($_W['wlmember']['nickname'])) {
            $info['nickname'] = $_W['wlmember']['nickname'];
        } else if (empty($info['nickname']) && !empty($_W['wlmember']['realname'])) {
            $info['nickname'] = $_W['wlmember']['realname'];
        } else if (empty($info['nickname'])) {
            $info['nickname'] = '用户昵称';
        }
        #10、获取邀请海报跳转链接
        $info['invitation_posters_link'] = h5_url('pages/mainPages/poster/poster', ['id' => $mid, 'type' => '2']);//邀请海报
        $info['invitation_shop_link'] = h5_url('pages/mainPages/member/index/index');//邀请开店
        #11、判断是否显示分销商等级
        $info['show_lv'] = $this->disSetting['levelshow'];//是否显示分销等级：0=隐藏  1=开启
        $info['rankshow'] = $this->disSetting['rankshow'];//是否显示排行榜：0=隐藏  1=显示
        $info['dstext'] = $this->disSetting['dstext'] ? : '导师';

        if (p('taxipay')) {
            $all_arr = array('plugin' => 'taxipay', 'leadid' => $mid);
            $all_money = Taxipay::master_money_query($all_arr);
            $all_num = Taxipay::master_money_query($all_arr, 'count');

            $today_arr = array_merge($all_arr, array('createtime >' => $dayStart));
            $today_money = Taxipay::master_money_query($today_arr);
            $today_num = Taxipay::master_money_query($today_arr, 'count');

            $month_arr = array_merge($all_arr, array('createtime >' => $monthStart, 'createtime <' => $monthEnd));
            $month_money = Taxipay::master_money_query($month_arr);
            $month_num = Taxipay::master_money_query($month_arr, 'count');

            $info['taxipay'] = compact('today_money', 'today_num', 'month_money', 'month_num', 'all_money', 'all_num');
            $master = pdo_get('wlmerchant_taxipay_master',array('mid' => $_W['mid']),array('id','status'));
            if(empty($master)){
                $info['applytaxi'] = 1;
                $info['taxicom'] = pdo_getall('wlmerchant_taxipay_company',array('uniacid' => $_W['uniacid']),array('id','name'));
            }else{
                if($master['status'] == 1){
                    $info['taxiqr'] = 1;
                }
            }
        }

        $this->renderSuccess('分销商详细信息', $info);
    }
    /**
     * Comment: 获取分销提现申请设置信息
     * Author: zzw
     * Date: 2019/7/16 15:45
     */
    public function getCashWithdrawalSet() {
        global $_W, $_GPC;
        $mid = $_W['mid'];
        #1、获取提现配置信息
        $data['payment_set'] = $this->disSetting['payment_type'];//提现打款方式设置
        $data['min_money'] = $this->disSetting['lowestmoney'] > 0 ? $this->disSetting['lowestmoney'] : 1;//申请提现最少金额，单位：元
        $data['max_money'] = $this->disSetting['maxmoney'] ? : 0;
        $data['withdrawcharge'] = $this->disSetting['withdrawcharge'];//提现手续费,单位：%(百分比)
        #2、获取用户提现账户信息
        $data['user_info'] = pdo_fetch("SELECT bank_name,card_number,distributorid,bank_username,alipay,realname FROM " . tablename(PDO_NAME . "member") . " WHERE id = {$mid} ");
        #3、获取用户可提现金额
        $disid = $data['user_info']['distributorid'];
        $data['drawPrice'] = pdo_fetchcolumn("SELECT nowmoney FROM " . tablename(PDO_NAME . "distributor") . " WHERE uniacid = {$_W['uniacid']} AND id = {$disid}");


        $this->renderSuccess('提现申请设置信息', $data);
    }
    /**
     * Comment: 分销商申请提现
     * Author: zzw
     * Date: 2019/7/16 17:17
     */
    public function cashWithdrawalApply() {
        global $_W, $_GPC;
        $mid = $_W['mid'];
        $flag = pdo_get('wlmerchant_settlement_temporary',array('mid' => $mid,'uniacid'=>$_W['uniacid'],'type'=> 3),array('id'));
        if(!empty($flag)){
            $this->renderError('您有处理中的提现申请,请稍后再试');
        }
        #1、参数接收并且判断是否完整
        empty($_GPC['sapplymoney']) && $this->renderError('请输入申请提现金额');
        empty($_GPC['payment_type']) && $this->renderError('请选择打款方式');
        if ($_GPC['payment_type'] == 1) {
            empty($_GPC['alipay']) && $this->renderError('请输入支付宝账号');
            empty($_GPC['realname']) && $this->renderError('请输入真实姓名');
            //修改用户的支付宝账号信息
            $alipay = [
                'alipay' => $_GPC['alipay'],
                'id'     => $mid,
                'realname' => $_GPC['realname']
            ];
            $res = pdo_get(PDO_NAME . 'member', $alipay);
            if (!$res) {
                unset($alipay['id']);
                //支付宝内容被改变  更新信息
                $res = pdo_update(PDO_NAME . 'member', $alipay, array('id' => $mid));
                !$res && $this->renderError('支付宝账号保存失败，请联系管理员!');
            }
        } else if ($_GPC['payment_type'] == 3) {
            empty($_GPC['bank_name']) && $this->renderError('请输入银行卡开户行');
            empty($_GPC['card_number']) && $this->renderError('请输入银行卡账号');
            empty($_GPC['bank_username']) && $this->renderError('请输入银行卡开户人的姓名');
            //修改用户的银行账号信息
            $bankCard = [
                'bank_name'     => $_GPC['bank_name'],
                'card_number'   => $_GPC['card_number'],
                'bank_username' => $_GPC['bank_username'],
                'id'            => $mid
            ];
            $res = pdo_get(PDO_NAME . 'member', $bankCard);
            if (!$res) {
                unset($bankCard['id']);
                //银行卡内容被改变  更新信息
                $res = pdo_update(PDO_NAME . 'member', $bankCard, array('id' => $mid));
                !$res && $this->renderError('银行卡信息保存失败，请联系管理员!');
            }
        } else if ($_GPC['payment_type'] == 2) {
            if ($_W['source'] == 1) {
                if (empty($_W['wlmember']['openid'])) {
                    $this->renderError('您无微信账号数据，无法微信提现');
                } else {
                    $sopenid = $_W['wlmember']['openid'];
                }
            } else if ($_W['source'] == 3) {
                $wechat_openid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$mid),'wechat_openid');
                if (empty($wechat_openid)) {
                    $this->renderError('您无微信账号数据，无法微信提现');
                } else {
                    $sopenid = $wechat_openid;
                }
            }
        }
        #2、判断提现申请是否符合条件
        //获取上一次提现时间，判断当前时间是否允许进行提现
        $frequency = $this->disSetting['frequency'];
        if ($frequency > 0) {
            $lastTime = pdo_fetchcolumn("SELECT applytime FROM " . tablename(PDO_NAME . "settlement_record") . " WHERE mid = {$mid} ORDER BY applytime DESC ");
            if ($lastTime > 0) {
                $timeRes = Commons::handleTime($lastTime,$frequency);
                if($timeRes['status'] == 1) $this->renderError($timeRes['str']);
            }
        }
        //判断当前申请提现金额是否大于等于最低提现金额
        $minMoney = $this->disSetting['lowestmoney'] > 1 ? $this->disSetting['lowestmoney'] : 1;//申请提现最少金额，单位：元
        if ($minMoney > $_GPC['sapplymoney']) $this->renderError('提现金额必须大于' . $minMoney . '元');
        $maxMoney = $this->disSetting['maxmoney'] ? : 0;
        if ($maxMoney < $_GPC['sapplymoney'] && $maxMoney > 0) $this->renderError('单次提现金额必须小于' . $maxMoney . '元');
        //判断可提现金额是否大于提现金额
        $disid = pdo_getcolumn(PDO_NAME.'member',array('id'=>$mid),'distributorid');
        $distributor = pdo_get(PDO_NAME . 'distributor', array('id' => $disid), array('nowmoney', 'id'));
        if ($_GPC['sapplymoney'] > $distributor['nowmoney']) $this->renderError('可提现金额不足');
        #3、提现信息拼装
        $appmoney = $_GPC['sapplymoney'];//提现金额
        $payment_type = $_GPC['payment_type'];//提现方式 1=支付宝，2=微信，3=银行卡,4=余额[仅分销商有余额打款]
        $disset = $this->disSetting;//基本设置信息
        $cashsets = Setting::wlsetting_read('cashset');
        //修改可提现金额
        $spercentmoney = sprintf("%.2f", $appmoney * $disset['withdrawcharge'] / 100);
        $money = sprintf("%.2f", $appmoney - $spercentmoney);
        $data = array(
            'uniacid'       => $_W['uniacid'],
            'aid'           => $_W['aid'],
            'status'        => 7,
            'type'          => 3,
            'mid'           => $_W['wlmember']['id'],
            'sopenid'       => $sopenid ? $sopenid : 0,
            'disid'         => $distributor['id'],
            'sgetmoney'     => $money,
            'sapplymoney'   => $appmoney,
            'spercentmoney' => $spercentmoney,
            'spercent'      => sprintf("%.4f", ($appmoney - $money) / $appmoney * 100),
            'applytime'     => time(),
            'payment_type'  => $payment_type,
            'source'        => $_W['source']
        );
        if ($cashsets['disnoaudit']) {
            $data['status'] = 3;
            $trade_no = time() . random(4, true);
            $data['trade_no'] = $trade_no;
            $data['updatetime'] = time();
        }
        $value = serialize($data);
        $temporary = array(
            'info' => $value,
            'type' => 3,
            'uniacid' => $_W['uniacid'],
            'mid'  => $data['mid']
        );
        $res = pdo_insert(PDO_NAME . 'settlement_temporary' , $temporary);
        if($res){
            $this->renderSuccess('申请成功', ['id' => -1]);
        }else{
            $this->renderError('申请失败，请重试');
        }
    }
    /**
     * Comment: 推广商品列表获取
     * Author: zzw
     * Date: 2019/7/17 11:49
     */
    public function distributionGoods() {
        global $_W, $_GPC;
        $order = $_GPC['orders'];//0=默认，1=最新，2=佣金从大到小，3=佣金从小到大，4=销量从大到小，5=销量从小到大
        $page = $_GPC['page'] ? $_GPC['page'] : 1;//当前页
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;//每页的数量
        $name = $_GPC['name'];

        $dislevel = pdo_getcolumn(PDO_NAME.'distributor',array('mid'=>$_W['mid']),'dislevel');
        $dislevel = $dislevel > 0 ? $dislevel : pdo_getcolumn(PDO_NAME . 'dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), 'id');
        $levelinfo = pdo_get(PDO_NAME .'dislevel',array('id' => $dislevel),array('name','plugin'));
        $displugin = unserialize($levelinfo['plugin']);
        //$list = Cache::getCache('distributionGoods'.$_W['mid'],$name);
        if(empty($list)) {
            $lv_id = pdo_getcolumn(PDO_NAME . "distributor", ['id' => $_W['wlmember']['distributorid']], 'dislevel');
            $derate = pdo_getcolumn(PDO_NAME . "dislevel", ['id' => $lv_id, 'uniacid' => $_W['uniacid']], 'onecommission');
            if (!$lv_id) {
                $defaleve = pdo_get(PDO_NAME . "dislevel", ['isdefault' => 1, 'uniacid' => $_W['uniacid']], ['onecommission', 'id']);
                $lv_id = $defaleve['id'];
                $derate = $defaleve['onecommission'];
            }
            $derate = $derate ? $derate / 100 : 0;
            #1、条件生成
            $groupWhere = " aa.aid = {$_W['aid']} AND aa.uniacid = {$_W['uniacid']} AND aa.status IN (1,2) ";//团购基本条件
            $rushWhere = " ba.aid = {$_W['aid']} AND ba.uniacid = {$_W['uniacid']} AND ba.status IN (1,2) ";//抢购基本条件
            $fightWhere = " ca.aid = {$_W['aid']} AND ca.uniacid = {$_W['uniacid']} AND ca.status IN (1,2) ";//拼团基本条件
            $bargainWhere = " da.aid = {$_W['aid']} AND da.uniacid = {$_W['uniacid']} AND da.status IN (1,2) ";//砍价基本条件
            $couponWhere = " ea.aid = {$_W['aid']} AND ea.uniacid = {$_W['uniacid']} AND ea.status IN (1,2) ";//卡券基本条件
            $integralWhere = " fa.uniacid = {$_W['uniacid']} AND fa.status = 1";//积分商品基本条件
            if (!empty($name)) {
                $groupWhere .= " AND aa.name LIKE '%{$name}%' ";//团购条件
                $rushWhere .= " AND ba.name LIKE '%{$name}%' ";//抢购条件
                $fightWhere .= " AND ca.name LIKE '%{$name}%' ";//拼团条件
                $bargainWhere .= " AND da.name LIKE '%{$name}%' ";//砍价条件
                $couponWhere .= " AND ea.title LIKE '%{$name}%' ";//卡券条件
                $integralWhere .= " AND fa.title LIKE '%{$name}%' ";//积分商品条件
            }
            #3、sql语句生成  (IFNULL(sum(b.num),0) + a.allsalenum) as buy_num
            $sql = '';
            if(empty($displugin) || in_array('groupon',$displugin)){
                $sql .= "UNION ALL SELECT aa.`id`,aa.optionstatus,aa.onedismoney,aa.disarray,aa.isdistristatus,aa.name,(IFNULL(sum(ab.num),0) + aa.allsalenum) as sales_volume,aa.sort,'group' as `plugin`,aa.thumb,aa.price,aa.oldprice,aa.createtime FROM " . tablename(PDO_NAME . 'groupon_activity') . " as aa LEFT JOIN " . tablename(PDO_NAME . 'order') .
                    " as ab ON aa.id = ab.fkid AND plugin = 'groupon' WHERE  aa.isdistri != 1 AND aa.`status` = 2 AND {$groupWhere} GROUP BY aa.id ";
            }
            if(empty($displugin) || in_array('rush',$displugin)){
                $sql .= "UNION ALL SELECT ba.id,ba.optionstatus,ba.onedismoney,ba.disarray,ba.isdistristatus,ba.name,(IFNULL(sum(bb.num),0) + ba.allsalenum) as sales_volume,ba.sort,
                'rush' as `plugin`,ba.thumb,ba.price,ba.oldprice,ba.starttime as createtime  FROM " . tablename(PDO_NAME . 'rush_activity') . " as ba LEFT JOIN " . tablename(PDO_NAME . 'rush_order') .
                    " as bb ON ba.id = bb.activityid WHERE ba.isdistri != 1 AND ba.`status` = 2 AND {$rushWhere} GROUP BY ba.id ";
            }
            if(empty($displugin) || in_array('fight',$displugin)) {
                $sql .= "UNION ALL SELECT ca.id,ca.specstatus as optionstatus,ca.onedismoney,ca.disarray,ca.isdistristatus,ca.name,(IFNULL(sum(cb.num),0) + ca.falsesalenum) as sales_volume,ca.listorder as sort,
                'fight' as `plugin`,ca.logo as thumb,ca.price,ca.oldprice,ca.limitstarttime as createtime FROM " . tablename(PDO_NAME . 'fightgroup_goods') . " as ca LEFT JOIN " . tablename(PDO_NAME . 'order') .
                    " as cb ON ca.id = cb.fkid AND plugin = 'wlfightgroup' WHERE 
                 ca.isdistri != 1 AND ca.`status` = 2 AND {$fightWhere} GROUP BY ca.id ";
            }
            if(empty($displugin) || in_array('bargain',$displugin)) {
                $sql .= "UNION ALL SELECT da.id,0 as optionstatus,da.onedismoney,da.disarray,da.isdistristatus,da.name,IFNULL(sum(db.num),0) as sales_volume,da.sort,
                'bargain' as `plugin`,da.thumb,da.price,da.oldprice,da.createtime FROM " . tablename(PDO_NAME . 'bargain_activity') . "  as da LEFT JOIN " . tablename(PDO_NAME . 'order') .
                    " as db ON da.id = db.fkid AND plugin = 'bargain' WHERE
                da.isdistri != 1 AND da.`status` = 2 AND {$bargainWhere} GROUP BY da.id ";
            }
            if(empty($displugin) || in_array('coupon',$displugin)) {
                $sql .= "UNION ALL SELECT ea.id,0 as optionstatus,ea.onedismoney,ea.disarray,ea.isdistristatus,ea.title as name,SUM(eb.num) as sales_volume,ea.indexorder as sort,
                'coupon' as `plugin`,ea.logo as thumb,
                ea.price,ea.price as oldprice,ea.createtime FROM " . tablename(PDO_NAME . 'couponlist') . "  as ea LEFT JOIN " . tablename(PDO_NAME . 'order') .
                    " as eb ON ea.id = eb.fkid AND plugin = 'coupon' WHERE
                ea.isdistri != 1 AND ea.`status` = 2 AND ea.is_charge = 1 AND {$couponWhere} GROUP BY ea.id ";
            }
            if(empty($displugin) || in_array('integral',$displugin)) {
                $sql .= "UNION ALL SELECT fa.id,0 as optionstatus,fa.onedismoney,0 as disarray,1 as isdistristatus,fa.title as name,SUM(fb.num) as sales_volume,fa.displayorder as sort,
                'integral' as `plugin`,fa.thumb,
                fa.use_credit2 as price,fa.old_price as oldprice,fa.id as createtime FROM " . tablename(PDO_NAME . 'consumption_goods') . "  as fa LEFT JOIN " . tablename(PDO_NAME . 'order') .
                    " as fb ON fa.id = fb.fkid AND plugin = 'consumption' WHERE 
               fa.isdistri = 1 AND fa.`status` = 1 AND {$integralWhere} GROUP BY fa.id ";
            }
            $sql = ltrim($sql,'UNION ALL ');

//                $sql = "SELECT aa.`id`,aa.optionstatus,aa.onedismoney,aa.disarray,aa.isdistristatus,aa.name,(IFNULL(sum(ab.num),0) + aa.allsalenum) as sales_volume,aa.sort,'group' as `plugin`,aa.thumb,aa.price,aa.oldprice,aa.createtime FROM " . tablename(PDO_NAME . 'groupon_activity') . " as aa LEFT JOIN " . tablename(PDO_NAME . 'order') .
//                " as ab ON aa.id = ab.fkid AND plugin = 'groupon' WHERE  aa.isdistri != 1 AND aa.`status` = 2 AND {$groupWhere} GROUP BY aa.id
//                UNION ALL SELECT ba.id,ba.optionstatus,ba.onedismoney,ba.disarray,ba.isdistristatus,ba.name,(IFNULL(sum(bb.num),0) + ba.allsalenum) as sales_volume,ba.sort,
//                'rush' as `plugin`,ba.thumb,ba.price,ba.oldprice,ba.starttime as createtime  FROM " . tablename(PDO_NAME . 'rush_activity') . " as ba LEFT JOIN " . tablename(PDO_NAME . 'rush_order') .
//                " as bb ON ba.id = bb.activityid WHERE ba.isdistri != 1 AND ba.`status` = 2 AND {$rushWhere} GROUP BY ba.id
//                UNION ALL SELECT ca.id,ca.specstatus as optionstatus,ca.onedismoney,ca.disarray,ca.isdistristatus,ca.name,(IFNULL(sum(cb.num),0) + ca.falsesalenum) as sales_volume,ca.listorder as sort,
//                'fight' as `plugin`,ca.logo as thumb,ca.price,ca.oldprice,ca.limitstarttime as createtime FROM " . tablename(PDO_NAME . 'fightgroup_goods') . " as ca LEFT JOIN " . tablename(PDO_NAME . 'order') .
//                " as cb ON ca.id = cb.fkid AND plugin = 'wlfightgroup' WHERE
//                 ca.isdistri != 1 AND ca.`status` = 2 AND {$fightWhere} GROUP BY ca.id
//                UNION ALL SELECT da.id,0 as optionstatus,da.onedismoney,da.disarray,da.isdistristatus,da.name,IFNULL(sum(db.num),0) as sales_volume,da.sort,
//                'bargain' as `plugin`,da.thumb,da.price,da.oldprice,da.createtime FROM " . tablename(PDO_NAME . 'bargain_activity') . "  as da LEFT JOIN " . tablename(PDO_NAME . 'order') .
//                " as db ON da.id = db.fkid AND plugin = 'bargain' WHERE
//                da.isdistri != 1 AND da.`status` = 2 AND {$bargainWhere} GROUP BY da.id
//                UNION ALL SELECT ea.id,0 as optionstatus,ea.onedismoney,ea.disarray,ea.isdistristatus,ea.title as name,SUM(eb.num) as sales_volume,ea.indexorder as sort,
//                'coupon' as `plugin`,ea.logo as thumb,
//                ea.price,ea.price as oldprice,ea.createtime FROM " . tablename(PDO_NAME . 'couponlist') . "  as ea LEFT JOIN " . tablename(PDO_NAME . 'order') .
//                " as eb ON ea.id = eb.fkid AND plugin = 'coupon' WHERE
//                ea.isdistri != 1 AND ea.`status` = 2 AND ea.is_charge = 1 AND {$couponWhere} GROUP BY ea.id
//                UNION ALL SELECT fa.id,0 as optionstatus,fa.onedismoney,0 as disarray,1 as isdistristatus,fa.title as name,SUM(fb.num) as sales_volume,fa.displayorder as sort,
//                'integral' as `plugin`,fa.thumb,
//                fa.use_credit2 as price,fa.old_price as oldprice,fa.id as createtime FROM " . tablename(PDO_NAME . 'consumption_goods') . "  as fa LEFT JOIN " . tablename(PDO_NAME . 'order') .
//                " as fb ON fa.id = fb.fkid AND plugin = 'consumption' WHERE
//               fa.isdistri = 1 AND fa.`status` = 1 AND {$integralWhere} GROUP BY fa.id";
            #4、获取信息列表
            $list = pdo_fetchall($sql);
            foreach ($list as $key => &$val) {
                $rate = $derate;
                switch ($val['plugin']) {
                    case 'group':
                        $val['link_url'] = h5_url('pages/subPages/goods/index', ['type' => 2, 'id' => $val['id']]);
                        if ($val['optionstatus'] > 0) {
                            $options = pdo_getall('wlmerchant_goods_option', array('type' => 3, 'goodsid' => $val['id']), array('price','disarray'));
                            foreach($options as &$opp){
                                $disarray = WeliamWeChat::mergeDisArray($opp['disarray'],$val['disarray']);
                                $disarray = unserialize($disarray);
                                $opp['dismoney'] = WeliamWeChat::getDismoney($disarray,$lv_id,$val['isdistristatus'],$opp['price'],$rate);
                            }
                            $prices = array_column($options,'dismoney');
                            $val['commission'] = max($prices);
                        }else{
                            $disarray = unserialize($val['disarray']);
                            $val['commission'] = WeliamWeChat::getDismoney($disarray,$lv_id,$val['isdistristatus'],$val['price'],$rate);
                        }
                        break;//团购
                    case 'rush':
                        $val['link_url'] = h5_url('pages/subPages/goods/index', ['type' => 1, 'id' => $val['id']]);
                        if ($val['optionstatus'] > 0) {
                            $options = pdo_getall('wlmerchant_goods_option', array('type' => 1, 'goodsid' => $val['id']), array('price','disarray'));
                            foreach($options as &$opp){
                                $disarray = WeliamWeChat::mergeDisArray($opp['disarray'],$val['disarray']);
                                $disarray = unserialize($disarray);
                                $opp['dismoney'] = WeliamWeChat::getDismoney($disarray,$lv_id,$val['isdistristatus'],$opp['price'],$rate);
                            }
                            $prices = array_column($options,'dismoney');
                            $val['commission'] = max($prices);
                        }else{
                            $disarray = unserialize($val['disarray']);
                            $val['commission'] = WeliamWeChat::getDismoney($disarray,$lv_id,$val['isdistristatus'],$val['price'],$rate);
                        }
                        break;//抢购
                    case 'fight':
                        $val['link_url'] = h5_url('pages/subPages/goods/index', ['type' => 3, 'id' => $val['id']]);
                        if ($val['optionstatus'] > 0) {
                            $options = pdo_getall('wlmerchant_goods_option', array('type' => 2, 'goodsid' => $val['id']), array('price','disarray'));
                            foreach($options as &$opp){
                                $disarray = WeliamWeChat::mergeDisArray($opp['disarray'],$val['disarray']);
                                $disarray = unserialize($disarray);
                                $opp['dismoney'] = WeliamWeChat::getDismoney($disarray,$lv_id,$val['isdistristatus'],$opp['price'],$rate);
                            }
                            $prices = array_column($options,'dismoney');
                            $val['commission'] = max($prices);
                        }else{
                            $disarray = unserialize($val['disarray']);
                            $val['commission'] = WeliamWeChat::getDismoney($disarray,$lv_id,$val['isdistristatus'],$val['price'],$rate);
                        }
                        break;//拼团
                    case 'bargain':
                        $val['link_url'] = h5_url('pages/subPages/goods/index', ['type' => 7, 'id' => $val['id']]);
                        $disarray = unserialize($val['disarray']);
                        $val['commission'] = WeliamWeChat::getDismoney($disarray,$lv_id,$val['isdistristatus'],$val['price'],$rate);
                        break;//砍价
                    case 'coupon':
                        $val['link_url'] = h5_url('pages/subPages/goods/index', ['type' => 5, 'id' => $val['id']]);
                        $disarray = unserialize($val['disarray']);
                        $val['commission'] = WeliamWeChat::getDismoney($disarray,$lv_id,$val['isdistristatus'],$val['price'],$rate);
                        break;//卡券
                    case 'integral':
                        $val['link_url'] = h5_url('pages/subPages/goods/index', array('goods_id' => $val['id'], 'goodsType' => 'integral'));
                        if ($val['onedismoney'] > 0) {
                            $val['commission'] = $val['onedismoney'];
                        } else {
                            $val['commission'] = sprintf("%.2f", $val['price'] * $rate);
                        }
                        break;//积分商品
                }
                $val['thumb'] = tomedia($val['thumb']);
                //unset($val['createtime']);//时间
                //unset($val['sales_volume']);//销量
            }
            Cache::setCache('distributionGoods'.$_W['mid'], $name, $list);
        }


        #5、根据条件进行排序 0=默认，1=最新，2=佣金从大到小，3=佣金从小到大，4=销量从大到小，5=销量从小到大
        switch ($order) {
            case 1:
                //条件不足  最新排序暂时不可用
                $orderWhere = array_column($list, 'createtime');
                array_multisort($orderWhere, SORT_DESC, $list);
                break;//最新
            case 2:
                $orderWhere = array_column($list, 'commission');
                array_multisort($orderWhere, SORT_DESC, $list);
                break;//佣金从大到小
            case 3:
                $orderWhere = array_column($list, 'commission');
                array_multisort($orderWhere, SORT_ASC, $list);
                break;//佣金从小到大
            case 4:
                $orderWhere = array_column($list, 'sales_volume');
                array_multisort($orderWhere, SORT_DESC, $list);
                break;//销量从大到小
            case 5:
                $orderWhere = array_column($list, 'sales_volume');
                array_multisort($orderWhere, SORT_ASC, $list);
                break;//销量从小到大
            default:
                $orderWhere = array_column($list, 'sort');
                array_multisort($orderWhere, SORT_DESC, $list);
        }
        #6、分页操作
        $info['page_total'] = ceil(count($list) / $page_index);
        $pageStart = $page * $page_index - $page_index;
        $list = array_slice($list, $pageStart, $page_index);

        $info['list'] = $list;

        $this->renderSuccess('推广商品列表', $info);
    }
    /**
     * Comment: 分销商佣金明细
     * Author: zzw
     * Date: 2019/7/17 14:19
     */
    public function detailedCommission() {
        global $_W, $_GPC;
        $mid = $_W['mid'];
        #1、判断当前用户是否为分销商
        $field = 'id,dismoney';
        $info = pdo_fetch("SELECT {$field} FROM " . tablename(PDO_NAME . "distributor") . " WHERE mid = {$mid} AND disflag IN (1,-1) ");
        if (!$info){
            if(Customized::init('distributionText') > 0){
                $this->renderError('请先成为共享股东');
            }else{
                $this->renderError('请先成为分销商');
            }
        }
        #2、获取本日、本月的收益
        $dayWhere = $monthWhere = "  (a.oneleadid = {$info['id']} OR a.twoleadid = {$info['id']} OR a.threeleadid = {$info['id']}) ";
        $field = "distinct a.id,a.leadmoney,a.oneleadid,a.twoleadid,a.threeleadid";
        $dayMoney = 0;//当日收益
        $monthMoney = 0;//当月收益
        //获取本日的收益信息
        $dayStart = strtotime(date("Y-m-d"));//当天开始时间
        $dayEnd = strtotime(date("Y-m-d") . " 23:59:59");//当天结束时间
        $dayWhere .= " AND b.createtime > {$dayStart} AND b.createtime < {$dayEnd} ";
        $dayList = Distribution::getDisOrder($dayWhere, $field);
        if (is_array($dayList) && count($dayList) > 0) {
            foreach ($dayList as $dayK => $dayV) {
                $moneyArr = unserialize($dayV['leadmoney']);
                if ($dayV['oneleadid'] == $info['id'] && $moneyArr['one'] > 0) {
                    $dayMoney += $moneyArr['one'];
                } else if ($dayV['twoleadid'] == $info['id'] && $moneyArr['two'] > 0) {
                    $dayMoney += $moneyArr['two'];
                } else if ($dayV['threeleadid'] == $info['id'] && $moneyArr['three'] > 0) {
                    $dayMoney += $moneyArr['three'];
                } else {
                    $dayMoney += 0;
                }
            }
        }
        if (p('taxipay')) {
            $taxipayDayMoney = pdo_fetchcolumn("SELECT SUM(price) FROM " . tablename(PDO_NAME . "disdetail") . " WHERE plugin = 'taxipay' AND createtime > {$dayStart} AND createtime < {$dayEnd}  AND leadid = {$mid} AND uniacid = {$_W['uniacid']} ");
            $dayMoney += $taxipayDayMoney;
        }
        //获取本月的收益
        $monthStart = strtotime(date("Y-m") . "-1 ");//当月开始时间
        $monthEnd = strtotime(date('Y-m-t') . " 23:59:59");//当月结束时间
        $monthWhere .= " AND b.createtime > {$monthStart} AND b.createtime < {$monthEnd} ";
        $monthList = Distribution::getDisOrder($monthWhere, $field);
        if (is_array($monthList) && count($monthList) > 0) {
            foreach ($monthList as $monthK => $monthV) {
                $moneyArr = unserialize($monthV['leadmoney']);
                if ($monthV['oneleadid'] == $info['id'] && $moneyArr['one'] > 0) {
                    $monthMoney += $moneyArr['one'];
                } else if ($monthV['twoleadid'] == $info['id'] && $moneyArr['two'] > 0) {
                    $monthMoney += $moneyArr['two'];
                } else if ($monthV['threeleadid'] == $info['id'] && $moneyArr['three'] > 0) {
                    $monthMoney += $moneyArr['three'];
                } else {
                    $monthMoney += 0;
                }
            }
        }
        if (p('taxipay')) {
            $taxipayMonthMoney = pdo_fetchcolumn("SELECT SUM(price) FROM " . tablename(PDO_NAME . "disdetail") . " WHERE plugin = 'taxipay' AND createtime > {$monthStart} AND createtime < {$monthEnd}  AND leadid = {$mid} AND uniacid = {$_W['uniacid']} ");
            $monthMoney += $taxipayMonthMoney;
        }
        $info['day_money'] = $dayMoney > 0 ? sprintf("%.2f", $dayMoney) : sprintf("%.2f", 0);
        $info['month_money'] = $monthMoney > 0 ? sprintf("%.2f", $monthMoney) : sprintf("%.2f", 0);
        #3、获取佣金明细列表
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart = $page * $page_index - $page_index;
        //获取总页数
        $total = pdo_fetchcolumn("SELECT count(*) FROM " . tablename(PDO_NAME . "disdetail")
            . " as a LEFT JOIN " . tablename(PDO_NAME . "disorder") . " as b ON a.disorderid = b.id "
            . " WHERE a.leadid = {$mid} ");
        $info['page_total'] = ceil($total / $page_index);
        //获取列表信息
        $list = pdo_fetchall("SELECT a.disorderid,a.plugin,a.status,a.reason,FROM_UNIXTIME(a.createtime,'%Y-%m-%d %H:%i:%S') as createtime,a.price,a.buymid,a.type,
                            CASE b.plugin
                                WHEN 'rush' THEN (SELECT orderno FROM " . tablename(PDO_NAME . 'rush_order') . " WHERE id = b.orderid ) 
                                ELSE (SELECT orderno FROM " . tablename(PDO_NAME . 'order') . " WHERE id = b.orderid ) 
                            END as orderno,
                            CASE 
                                WHEN a.disorderid > 0 AND (a.reason = '分销提现驳回' OR a.reason = '分销佣金提现') 
                                    THEN (SELECT id FROM " . tablename(PDO_NAME . 'settlement_record') . " WHERE id = a.disorderid)
                                ELSE -1
                            END as is_cash_withdrawal FROM " . tablename(PDO_NAME . "disdetail")
            . " as a LEFT JOIN " . tablename(PDO_NAME . "disorder") . " as b ON a.disorderid = b.id "
            . " WHERE a.leadid = {$mid} ORDER BY a.createtime DESC LIMIT {$pageStart},{$page_index} ");

        if(!empty($list)){
            foreach ($list as &$li){
                if($li['buymid'] > 0){
                    if($li['status'] == 1){
                        $store = pdo_get('wlmerchant_merchantdata',array('id' => $li['buymid']),array('storename','logo'));
                        $li['name'] = $store['storename'];
                        $li['reason'] = $li['reason'];
                        $li['avatar'] = tomedia($store['logo']);
                    }else{
                        $member = pdo_get('wlmerchant_member',array('id' => $li['buymid']),array('avatar','nickname'));
                        $li['name'] = $member['nickname'];
                        
                        $li['avatar'] = tomedia($member['avatar']);
						if($li['status'] == 2){
							$li['reason'] = '团长分红';
						}else if($li['status'] == 3){
							$li['reason'] = '股东分红';
						}else{
							$li['reason'] = $li['reason'];
						}
						
                    }
                }else{
                    $li['avatar'] = tomedia($_W['wlsetting']['base']['logo']);
                    if(empty($li['reason']) && $li['plugin'] == 'system' ){
                        $li['reason'] = '系统修改';
                    }
                }
            }
        }
        $info['detail_list'] = $list;

        $this->renderSuccess('分销商佣金明细', $info);
    }
    /**
     * Comment: 分销商等级信息(等级说明)
     * Author: zzw
     * Date: 2019/7/17 15:03
     */
    public function disLvInfo() {
        global $_W, $_GPC;
        $mid = $_W['mid'];
        $lvId = $_GPC['id'];
        #1、判断当前用户是否为分销商
        $field = 'id,dislevel,dismoney,mid';
        $info = pdo_fetch("SELECT {$field} FROM " . tablename(PDO_NAME . "distributor") . " WHERE mid = {$mid} AND disflag IN (1,-1) ");
        if (!$info){
            if(Customized::init('distributionText') > 0){
                $this->renderError('请先成为共享股东');
            }else{
                $this->renderError('请先成为分销商');
            }
        }
        #2、获取当前分销商的头像、昵称
        $member = pdo_get(PDO_NAME . "member", ['id' => $mid], ['nickname', 'avatar']);
        $info['nickname'] = $member['nickname'];
        $info['avatar'] = $member['avatar'];
        #3、获取当前分销商等级所的下线总数(包括分销商&普通下线)
        $info['levelupstatus'] = unserialize($_W['wlsetting']['distribution']['levelupstatus']);
        #4、获取查询下一级&当前等级收益的条件
        if ($lvId > 0) {
            $lvWhere = " id = {$lvId} ";
        } else if ($info['dislevel'] > 0) {
            $lvWhere = " id = {$info['dislevel']} ";
        } else {
            $info['dislevel'] = pdo_getcolumn(PDO_NAME . 'dislevel', array('uniacid' => $_W['uniacid'], 'isdefault' => 1), 'id');
            $lvWhere = " id = {$info['dislevel']} ";
        }
        $highlevel = pdo_fetch("SELECT * FROM " . tablename(PDO_NAME . "dislevel") . " WHERE {$lvWhere} AND uniacid = {$_W['uniacid']} ");


        if (in_array(0,$info['levelupstatus']) && $highlevel['upstandard'] > 0) {
            $info['team_total'] = $info['dismoney'];
            $info['distance'] = sprintf("%.2f",$highlevel['upstandard'] - $info['team_total']);
            $info['distance'] = $info['distance'] < 0 ? 0 : $info['distance'];
            $info['flag'] = 1;
            $info['upstandard'] = $highlevel['upstandard'] ? : 0;
        }
        if (in_array(1,$info['levelupstatus']) && $highlevel['upstandard1'] > 0) {
            $onelows = pdo_getall(PDO_NAME . 'distributor', ['leadid' => $info['mid']], ['mid']);
            $onenum = count($onelows);
            $twonum = 0;
            if ($_W['wlsetting']['distribution']['ranknum'] > 1 && $onelows) {
                foreach ($onelows as $key => $one) {
                    $twolows = pdo_getall('wlmerchant_distributor', array('leadid' => $one['mid']), array('mid'));
                    $twonum += count($twolows);
                }
            }
            $info['team_total1'] = $onenum + $twonum;
            $info['distance1'] = $highlevel['upstandard1'] - $info['team_total1'];
            $info['distance1'] = $info['distance1'] < 0 ? 0 : $info['distance1'];
            $info['flag1'] = 1;
            $info['upstandard1'] = $highlevel['upstandard1'] ? : 0;
        }
        if (in_array(2,$info['levelupstatus']) && $highlevel['upstandard2'] > 0) {
            $info['team_total2'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "distributor") . " WHERE leadid = {$mid} AND disflag IN (0,1) ");
            $info['distance2'] = $highlevel['upstandard2'] - $info['team_total2'];
            $info['distance2'] = $info['distance2'] < 0 ? 0 : $info['distance2'];
            $info['flag2'] = 1;
            $info['upstandard2'] = $highlevel['upstandard2'] ? : 0;
        }
        if (in_array(3,$info['levelupstatus']) && $highlevel['upstandard3'] > 0) {
            $onelows = pdo_getall('wlmerchant_distributor', array('leadid' => $info['mid'], 'disflag' => array(-1, 1)), array('mid', 'disflag'));
            $onenum = count($onelows);
            $twonum = 0;
            if ($_W['wlsetting']['distribution']['ranknum'] > 1 && $onelows) {
                foreach ($onelows as $key => $one) {
                    $twolows = pdo_getall('wlmerchant_distributor', array('leadid' => $one['mid'], 'disflag' => array(-1, 1)), array('mid'));
                    $twonum += count($twolows);
                }
            }
            $info['team_total3'] = $onenum + $twonum;
            $info['distance3'] = $highlevel['upstandard3'] - $info['team_total3'];
            $info['distance3'] = $info['distance3'] < 0 ? 0 : $info['distance3'];
            $info['flag3'] = 1;
            $info['upstandard3'] = $highlevel['upstandard3'] ? : 0;
        }
        if (in_array(4,$info['levelupstatus']) && $highlevel['upstandard4'] > 0) {
            $info['team_total4'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . tablename(PDO_NAME . "distributor") . " WHERE leadid = {$mid} AND disflag IN (-1,1) ");
            $info['distance4'] = $highlevel['upstandard4'] - $info['team_total4'];
            $info['distance4'] = $info['distance4'] < 0 ? 0 : $info['distance4'];
            $info['flag4'] = 1;
            $info['upstandard4'] = $highlevel['upstandard4'] ? : 0;
        }
        if (in_array(5,$info['levelupstatus']) && $highlevel['upstandard5'] > 0) {
            $info['team_total5'] = pdo_getcolumn('wlmerchant_disorder',array('buymid' => $info['mid'],'oneleadid'=>$info['id']),array("SUM(orderprice)"));
            $info['distance5'] = sprintf("%.2f", $highlevel['upstandard5'] - $info['team_total5']);
            $info['distance5'] = $info['distance5'] < 0 ? 0 : $info['distance5'];
            $info['flag5'] = 1;
            $info['upstandard5'] = $highlevel['upstandard5'] ? : 0;
        }
        #5、获取分销商当前等级的收益
        $info['onecommission'] = $highlevel['onecommission'] ?: sprintf("%.2f", 0.00);
        $info['twocommission'] = $highlevel['twocommission'] ?: sprintf("%.2f", 0.00);
        $info['threecommission'] = $highlevel['threecommission'] ?: sprintf("%.2f", 0.00);
        #6、获取分销商等级列表 ,onecommission,twocommission,threecommission
        $info['lv_list'] = pdo_fetchall("SELECT id,name FROM " . tablename(PDO_NAME . "dislevel") . " WHERE uniacid = {$_W['uniacid']} AND (levelclass > 0 OR isdefault = 1) ORDER BY levelclass  DESC ");
        #7、判断开启的分销层级
        $info['hierarchy'] = intval($this->disSetting['ranknum']) ?: intval(1);
        $info['rankshow'] = $this->disSetting['rankshow'];//是否显示排行榜：0=隐藏  1=显示

        $this->renderSuccess('分销商等级信息(等级说明)', $info);
    }
    /**
     * Comment: 获取我的团队人员信息
     * Author: zzw
     * Date: 2019/7/17 18:33
     */
    public function myTeam() {
        global $_W, $_GPC;
        $mid = $_W['mid'];
        $state = $_GPC['state'] ? $_GPC['state'] : 0;//获取状态：0=全部；1=已下单；2=未下单
        $page = $_GPC['page'] ? $_GPC['page'] : 1;//当前页
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;//每页的数量
        $name = $_GPC['name'];//用户昵称
        $type = $_GPC['type'] ? $_GPC['type'] : 0;//0=全部 1=普通下线 2=下级分销商
        #1、判断当前用户是否为分销商
        $disId = pdo_fetchcolumn("SELECT id FROM " . tablename(PDO_NAME . "distributor") . " WHERE mid = {$mid} AND disflag IN (1,-1) ");
        if (!$disId || $disId < 0){
            if(Customized::init('distributionText') > 0){
                $this->renderError('请先成为共享股东');
            }else{
                $this->renderError('请先成为分销商');
            }
        }
        #2、查询条件生成
        $where = " WHERE leadid = {$mid} AND `uniacid` = {$_W['uniacid']} ";//基本条件
        //未锁定用户
        if ($_W['wlsetting']['distribution']['lockstatus'] == 1 && empty($_W['wlsetting']['distribution']['showlock'])) {
            $where .= " AND lockflag = 0";
        }
        if ($type == 1) {
            $where .= " AND disflag IN (0,-1)";//普通下线
        } else if ($type == 2) {
            $where .= " AND disflag = 1";//下级分销商
        }
        $isPurchaseWhere = '';//是否下单的条件
//        $caseStr = "CASE disflag
//                         WHEN 0 THEN
//                            CASE WHEN ((SELECT count(*) FROM " . tablename(PDO_NAME . "order") . " WHERE mid = a.`mid`  AND `status` IN (0,1,2,3,4,6,8,9) AND createtime > a.`createtime` )
//                                       + (SELECT count(*) FROM " . tablename(PDO_NAME . "rush_order") . " WHERE mid = a.`mid` AND `status` IN (0,1,2,3,4,6,8,9) AND createtime > a.`createtime` )) > 0
//                                 THEN ((SELECT count(*) FROM " . tablename(PDO_NAME . "order") . " WHERE mid = a.`mid`  AND `status` IN (0,1,2,3,4,6,8,9) AND createtime > a.`createtime` )
//                                       + (SELECT count(*) FROM " . tablename(PDO_NAME . "rush_order") . " WHERE mid = a.`mid` AND `status` IN (0,1,2,3,4,6,8,9) AND createtime > a.`createtime` ))
//                                 ELSE 0
//                            END
//                         WHEN 1 THEN
//                            CASE WHEN ((SELECT count(*) FROM " . tablename(PDO_NAME . "order") . " WHERE mid = a.`mid`  AND `status` IN (0,1,2,3,4,6,8,9) AND createtime > a.`createtime` )
//                                       + (SELECT count(*) FROM " . tablename(PDO_NAME . "rush_order") . " WHERE mid = a.`mid` AND `status` IN (0,1,2,3,4,6,8,9)  AND createtime > a.`createtime` )
//                                       + (SELECT count(*) FROM " . tablename(PDO_NAME . "disorder") . " WHERE oneleadid = a.`id` OR twoleadid = a.`id` OR threeleadid = a.`id` AND createtime > a.`createtime`)) > 0
//
//                                 THEN ((SELECT count(*) FROM " . tablename(PDO_NAME . "order") . " WHERE mid = a.`mid`  AND `status` IN (0,1,2,3,4,6,8,9) AND createtime > a.`createtime` )
//                                       + (SELECT count(*) FROM " . tablename(PDO_NAME . "rush_order") . " WHERE mid = a.`mid` AND `status` IN (0,1,2,3,4,6,8,9)  AND createtime > a.`createtime` )
//                                       + (SELECT count(*) FROM " . tablename(PDO_NAME . "disorder") . " WHERE oneleadid = a.`id` OR twoleadid = a.`id` OR threeleadid = a.`id` AND createtime > a.`createtime`))
//                                 ELSE 0
//                            END
//                    END";

        $caseStr = "CASE 
                         WHEN (SELECT count(*) FROM " . tablename(PDO_NAME . "disorder") . " WHERE buymid = a.`mid` AND oneleadid = '{$disId}' ) > 0 
                         THEN (SELECT count(*) FROM " . tablename(PDO_NAME . "disorder") . " WHERE buymid = a.`mid` AND oneleadid = '{$disId}' )
                         ELSE 0
                    END ";
        //生成已下单/未下单的查询条件
        if ($state == 1 || $state == 2) {
            $isPurchaseWhere .= ' AND ' . $caseStr;
            //获取状态：0=全部；1=已下单；2=未下单
            if ($state == 1) {
                $isPurchaseWhere .= "> 0 ";//已下单
            } else if ($state == 2) {
                $isPurchaseWhere .= "< 1";//未下单
            }
        }
        //生成当天新增查询条件
        $dayStart = strtotime(date("Y-m-d"));//当天开始时间
        $dayEnd = strtotime(date("Y-m-d") . " 23:59:59");//当天结束时间
        $dayWhere = " AND a.createtime > {$dayStart} AND a.createtime < {$dayEnd} ";
        #3、排序条件生成
        $order = " ORDER BY `createtime` DESC  ";
        #4、生成基本的查询语句
        $field = "a.disflag,a.mid,a.nickname,FROM_UNIXTIME(a.`createtime`,'%Y-%m-%d %H:%i:%S') as createtime,a.dislevel  ";
        $sql = "SELECT {$caseStr} as order_number,{$field} FROM " . tablename(PDO_NAME . "distributor") . " as a {$where} ";
        $totalSql = "SELECT count(*) FROM " . tablename(PDO_NAME . "distributor") . " as a {$where} ";
        #5、获取基本信息统计信息
        $info['day_number'] = pdo_fetchcolumn($totalSql . $dayWhere);//count(pdo_fetchall($sql.$dayWhere.$order));//当天新增下线
        $info['total_number'] = pdo_fetchcolumn($totalSql);//count(pdo_fetchall($sql.$order));//合计下线人数
        #6、生成分页信息
        $pageStart = $page * $page_index - $page_index;
        $limit = " LIMIT {$pageStart},{$page_index} ";
        #7、获取并且处理列表信息
        if ($name) {
            $searchWhere = " AND a.nickname LIKE '%{$name}%' ";
        }
        $total = pdo_fetchcolumn($totalSql . $searchWhere . $isPurchaseWhere);
        $info['page_total'] = ceil($total / $page_index);
        $list = pdo_fetchall($sql . $searchWhere . $isPurchaseWhere . $order . $limit);
        //获取默认的分销商等级信息
        $defaultLvName = pdo_fetchcolumn("SELECT name FROM " . tablename(PDO_NAME . "dislevel") . " WHERE uniacid = {$_W['uniacid']} AND isdefault = 1 ");
        foreach ($list as $key => &$val) {
            //获取用户信息
            $userInfo = pdo_get(PDO_NAME . "member", ['id' => $val['mid']], ['nickname', 'avatar']);
            $val['avatar'] = $userInfo['avatar'];
            //!empty($val['nickname']) && $val['nickname'] = $userInfo['nickname'];
            //判断获取分销商等级
            if ($val['disflag'] == 1) {
                //分销商 —— 获取分销等级
                if ($val['dislevel'] > 0) {
                    $lvName = pdo_fetchcolumn("SELECT name FROM " . tablename(PDO_NAME . "dislevel") . " WHERE id = {$val['dislevel']} ");
                    $val['lv_name'] = $lvName;
                } else {
                    $val['lv_name'] = $defaultLvName;
                }
            } else {
                //普通用户
                $val['lv_name'] = '普通用户';
            }
            //获取累计佣金
            $val['commission'] = pdo_fetchcolumn("SELECT SUM(price) FROM " . tablename(PDO_NAME . "disdetail") . " WHERE type = 1 AND leadid = {$mid} AND buymid = {$val['mid']} AND uniacid = {$_W['uniacid']} ");
            //删除无用的数据
            unset($val['disflag']);//下线类型
            unset($val['mid']);//下线的mid
            unset($val['dislevel']);//下线的分销等级id
        }
        $info['list'] = $list;

        $this->renderSuccess('团队人员信息列表', $info);
    }

	
	/**
     * Comment: 获取我的团员人员信息
     * Author: wlf
     * Date: 2022/10/27 14:36
     */
     
     public function myGroupTeam() {
        global $_W, $_GPC;
        $mid = $_W['mid'];
        $page = $_GPC['page'] ? $_GPC['page'] : 1;//当前页
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;//每页的数量
        $name = trim($_GPC['name']);//用户昵称
        $type = $_GPC['type'] ? $_GPC['type'] : 0;//0=全部 1=普通团员  2=下级团长
        #1、判断当前用户是否为分销商
        $disId = pdo_fetchcolumn("SELECT id FROM " . tablename(PDO_NAME . "distributor") . " WHERE mid = {$mid} AND groupflag = 1 ");
        if (!$disId || $disId < 0){
            $this->renderError('请先成为团长');
        }
        //查询条件
        $where = " WHERE onegroupid = {$mid} AND `uniacid` = {$_W['uniacid']} ";//基本条件
        if ($type == 1) {
            $where .= " AND groupflag != 1";//普通下线
        } else if ($type == 2) {
            $where .= " AND groupflag = 1";//下级团长
        }
		if (!empty($name)) {
            $where .= " AND nickname LIKE '%{$name}%' ";
        }
		
		$order = " ORDER BY updatetime DESC";
		$pageStart = $page * $page_index - $page_index;
        $limit = " LIMIT {$pageStart},{$page_index} ";
		
		$list = pdo_fetchall("SELECT mid,grouplevel,groupflag,updatetime,id FROM ".tablename('wlmerchant_distributor').$where.$order.$limit);
		if(empty($list)){
			$list = [];
		}else{
			foreach ($list as &$li) {
				$member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('nickname','avatar','encodename'));
				$li['nickname'] = !empty($member['encodename']) ? base64_decode($member['encodename']) : $member['nickname'];
				$li['avatar'] = tomedia($member['avatar']);
				$li['updatetime'] = date("Y-m-d H:i",$li['updatetime']);
				if($li['groupflag'] > 0){
					$li['levelname'] = pdo_getcolumn(PDO_NAME.'grouplevel',array('id'=>$li['grouplevel']),'name');
				}else{
					$li['levelname'] = '团员';
				}
				//下单信息
				$li['order_number'] = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_disorder')." WHERE buymid = {$li['mid']} AND onegroupid = '{$mid}'");
				$li['order_money'] = pdo_fetchcolumn('SELECT SUM(onegroupmoney) FROM '.tablename('wlmerchant_disorder')." WHERE buymid = {$li['mid']} AND onegroupid = '{$mid}'");
				if(empty($li['order_money'])){
					$li['order_money'] = 0;
				}
			}
		}
		
		$total = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_distributor').$where);
		$allpage = ceil($total / $page_index);
		
		$data = [
			'list' => $list,
			'total' => $total,
			'allpage' => $allpage
		];
		$this->renderSuccess('团队人员信息列表', $data);
    }


    /**
     * Comment: 推广订单列表
     * Author: zzw
     * Date: 2019/7/18 18:07
     */
    public function disOrder() {
        global $_W, $_GPC;
        $mid = $_W['mid'];
        $state = $_GPC['state'] ? $_GPC['state'] : 0;//分销结算状态 1未结算 2已结算 3已退款',
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $orderno = $_GPC['order_no'] ? $_GPC['order_no'] : '';
        $halfset = Setting::wlsetting_read('halfcard');
		
		$type = $_GPC['type'] ? : 0; // 0分销商订单 1团队订单 //2股东订单
        #1、判断当前用户是否为分销商
        if($type > 0){
        	$disId = pdo_fetchcolumn("SELECT id FROM " . tablename(PDO_NAME . "distributor") . " WHERE mid = {$mid} AND groupflag = 1 ");
	        if (!$disId || $disId < 0){
	            $this->renderError('请先成为团长');
	        }
        }else{
        	$disId = pdo_fetchcolumn("SELECT id FROM " . tablename(PDO_NAME . "distributor") . " WHERE uniacid = {$_W['uniacid']} AND mid = {$mid} AND disflag IN (1,-1) ");
	        if (!$disId || $disId < 0){
	            if(Customized::init('distributionText') > 0){
	                $this->renderError('请先成为共享股东');
	            }else{
	                $this->renderError('请先成为分销商');
	            }
	        }
        }
        
        
        #2、基本条件生成
        if($type == 1){
        	$select = tablename(PDO_NAME . "disorder") . " as a WHERE a.uniacid = {$_W['uniacid']} AND ( a.onegroupid = {$mid} OR a.twogroupid = {$mid}) ";
        }else if($type == 2){
        	$sharetime = pdo_getcolumn(PDO_NAME.'distributor',array('id'=>$disId),'sharetime');
        	if(empty($sharetime)){
				$sharetime = 0;
			}
        	$select = tablename(PDO_NAME . "disorder") . " as a WHERE a.uniacid = {$_W['uniacid']} AND shareholdermoney > 0 AND createtime > {$sharetime} ";
       	}else{
        	$select = tablename(PDO_NAME . "disorder") . " as a WHERE a.uniacid = {$_W['uniacid']} AND ( a.oneleadid = {$disId} OR a.twoleadid = {$disId} OR a.threeleadid = {$disId} ) ";
        }

        
        #3、统计信息获取
        $info['not_settlement'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . $select . " AND a.status = 0");//不可结算
        $info['unsettled_order'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . $select . " AND a.status IN (0,1)");//未结算
        $info['settled_order'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . $select . " AND a.status = 2");//已结算
        $info['refunded_order'] = pdo_fetchcolumn("SELECT COUNT(*) FROM " . $select . " AND a.status = 3");//已退款
        #4、列表查询条件生成
        //条件生成
        $where = '';
        if ($state == 1) {
            $where = " AND a.status IN (0,1) ";
        } else if ($state > 0) {
            $where = " AND a.status = {$state} ";
        }
        //分页生成
        $pageStart = $page * $page_index - $page_index;
        //订单号查询
        if (!empty($orderno)) {
            $where .= " AND 
                        CASE `plugin` 
                             WHEN 'rush' THEN ( SELECT orderno FROM " . tablename(PDO_NAME . "rush_order") . " WHERE id = a.orderid AND status != 9)
                             WHEN 'halfcard' THEN ( SELECT orderno FROM " . tablename(PDO_NAME . "halfcard_record") . " WHERE id = a.orderid )
                             ELSE ( SELECT orderno FROM " . tablename(PDO_NAME . "order") . " WHERE id = a.orderid )
                        END = '{$orderno}' ";
        }
        #5、获取与当前分销商相关的分销订单
        $field = " a.id,a.status,a.plugin,a.orderid,a.orderprice,a.buymid,a.leadmoney,a.oneleadid,a.twoleadid,a.threeleadid,a.onegroupid,a.twogroupid,a.onegroupmoney,a.twogroupmoney,a.shareholdermoney";
        $list = pdo_fetchall("SELECT {$field} FROM " . $select . $where . " ORDER BY a.createtime DESC LIMIT {$pageStart},{$page_index}");
        $info['page_total'] = ceil(count(pdo_fetchall("SELECT {$field} FROM " . $select . $where)) / $page_index);
        #6、通过循环获取相关的信息
        foreach ($list as $key => &$val) {
            //获取下单用户信息
            $userInfo = pdo_get(PDO_NAME . "member", ['id' => $val['buymid']], ['nickname', 'avatar','encodename']);
            $val['nickname'] = !empty($userInfo['encodename']) ? base64_decode($userInfo['encodename']) : $userInfo['nickname'];
            $val['avatar'] = $userInfo['avatar'];
            //获取对应的订单信息
            if ($val['plugin'] == 'rush') {
                //抢购订单
                $orderInfo = pdo_get(PDO_NAME . "rush_order", ['id' => $val['orderid']], ['activityid', 'num', 'orderno', 'paytime', 'expressid', 'checkcode', 'usedtime']);
            } else if ($val['plugin'] == 'halfcard') {
                //开卡订单
                $orderInfo = pdo_get(PDO_NAME . "halfcard_record", ['id' => $val['orderid']], ['typeid', 'orderno', 'paytime']);
            } else {
                //其他订单
                $orderInfo = pdo_get(PDO_NAME . "order", ['id' => $val['orderid']], ['fkid', 'num', 'sid', 'orderno', 'paytime', 'expressid', 'recordid']);
            }
            //判断订单消费类型：快递订单、核销订单、立即使用  获取对应的时间
            //bargain 砍价；charge 入驻；consumption 积分兑换；coupon  卡券；distribution  申请分销商；fightgroup  拼团；
            //groupon  团购；halfcard 会员卡；payonline  在线买单；pocket  掌上信息；rush  抢购；vip   会员卡
            $consumption_type = 3;//立即使用
            if ($orderInfo['expressid'] > 0) {
                //快递订单 —— 获取快递信息
                $timeList = pdo_fetchall(" SELECT receivetime FROM " . tablename(PDO_NAME . "express") . " WHERE receivetime is not null AND id = {$orderInfo['expressid']} ");
                $timeList = is_array($timeList) && count($timeList) > 0 ? array_column($timeList, 'receivetime') : '';
                $consumption_type = 1;//快递订单
            } else if (!empty($orderInfo['checkcode']) && $val['plugin'] == 'rush') {
                //核销订单 —— 抢购订单
                if (!empty($orderInfo['usedtime'])) {
                    $timeInfo = unserialize($orderInfo['usedtime']);
                    $timeList = is_array($timeInfo) ? array_column($timeInfo, 'time') : '';
                }
                $consumption_type = 2;
            } else if ($val['plugin'] != 'rush') {
                //核销订单 —— 普通订单
                $consumption_type = 2;//核销订单
                switch ($val['plugin']) {
                    case 'bargain':
                        $usedtime = pdo_fetchcolumn(" SELECT usedtime FROM " . tablename(PDO_NAME . "bargain_userlist") . " WHERE orderid = {$val['orderid']} ");
                        if ($usedtime) {
                            $usedtime = unserialize($usedtime);
                            $timeList = is_array($usedtime) && count($usedtime) > 0 ? array_column($usedtime, 'time') : '';
                        }
                        break;
                    case 'coupon':
                        $usedtime = pdo_fetchcolumn(" SELECT usedtime FROM " . tablename(PDO_NAME . "member_coupons") . " WHERE id = {$orderInfo['recordid']} ");
                        if ($usedtime) {
                            $usedtime = unserialize($usedtime);
                            $timeList = is_array($usedtime) && count($usedtime) > 0 ? array_column($usedtime, 'time') : '';
                        }
                        break;
                    case 'fightgroup':
                        $usedtime = pdo_fetchcolumn(" SELECT usedtime FROM " . tablename(PDO_NAME . "fightgroup_userecord") . " WHERE id = {$orderInfo['recordid']} ");
                        if ($usedtime) {
                            $usedtime = unserialize($usedtime);
                            $timeList = is_array($usedtime) && count($usedtime) > 0 ? array_column($usedtime, 'time') : '';
                        }
                        break;
                    case 'groupon':
                        $usedtime = pdo_fetchcolumn(" SELECT usedtime FROM " . tablename(PDO_NAME . "groupon_userecord") . " WHERE id = {$orderInfo['recordid']} ");
                        if ($usedtime) {
                            $usedtime = unserialize($usedtime);
                            $timeList = is_array($usedtime) && count($usedtime) > 0 ? array_column($usedtime, 'time') : '';
                        }
                        break;
                    default:
                        //立即使用
                        $timeList = [$orderInfo['paytime']];
                        $consumption_type = 3;//立即使用
                        break;
                }
            }
            //时间转换
            if (is_array($timeList) && count($timeList) > 0) {
                for ($i = 0; $i < count($timeList); $i++) {
                    if (count(explode('-', $timeList[$i])) != 3) {
                        if ($timeList[$i] > 0) {
                            $timeList[$i] = date("Y-m-d H:i:s", $timeList[$i]);
                        } else {
                            unset($timeList[$i]);
                        }
                    }
                }
            }
            $timeList = array_values($timeList);
            //获取商品信息
            switch ($val['plugin']) {
                case 'bargain':
                    $goodsInfo = pdo_get(PDO_NAME . "bargain_activity", ['id' => $orderInfo['fkid']], ['name', 'thumb']);
                    $val['goods_name'] = $goodsInfo['name'] ? $goodsInfo['name'] : '砍价商品';
                    $val['goods_logo'] = $goodsInfo['thumb'] ? tomedia($goodsInfo['thumb']) : '../addons/weliam_smartcity/web/resource/images/nopic.jpg';
                    break;//砍价商品
                case 'charge':
                    $val['goods_name'] = '商家入驻';
                    $val['goods_logo'] = tomedia(pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $orderInfo['sid']), 'logo'));
                    break;//商家入驻
                case 'consumption':
                    $goodsInfo = pdo_get(PDO_NAME . "consumption_goods", ['id' => $orderInfo['fkid']], ['title', 'thumb']);
                    $val['goods_name'] = $goodsInfo['title'] ? $goodsInfo['title'] : '积分兑换';
                    $val['goods_logo'] = $goodsInfo['thumb'] ? tomedia($goodsInfo['thumb']) : '../addons/weliam_smartcity/web/resource/images/nopic.jpg';
                    break;//积分兑换
                case 'coupon':
                    $goodsInfo = pdo_get(PDO_NAME . "couponlist", ['id' => $orderInfo['fkid']], ['title', 'logo']);
                    $val['goods_name'] = $goodsInfo['title'] ? $goodsInfo['title'] : '卡券';
                    $val['goods_logo'] = $goodsInfo['logo'] ? tomedia($goodsInfo['logo']) : '../addons/weliam_smartcity/web/resource/images/nopic.jpg';
                    break;//卡券
                case 'distribution':
                    if(Customized::init('distributionText') > 0){
                        $val['goods_name'] = '申请共享股东';
                    }else{
                        $val['goods_name'] = '申请分销商';
                    }
                    $val['goods_name'] = '申请分销商';
                    $val['goods_logo'] = '../addons/weliam_smartcity/web/resource/images/nopic.jpg';
                    break;//申请分销商
                case 'fightgroup':
                    $goodsInfo = pdo_get(PDO_NAME . "fightgroup_goods", ['id' => $orderInfo['fkid']], ['name', 'logo']);
                    $val['goods_name'] = $goodsInfo['name'] ? $goodsInfo['name'] : '拼团商品';
                    $val['goods_logo'] = $goodsInfo['logo'] ? tomedia($goodsInfo['logo']) : '../addons/weliam_smartcity/web/resource/images/nopic.jpg';
                    break;//拼团
                case 'groupon':
                    $goodsInfo = pdo_get(PDO_NAME . "groupon_activity", ['id' => $orderInfo['fkid']], ['name', 'thumb']);
                    $val['goods_name'] = $goodsInfo['name'] ? $goodsInfo['name'] : '团购商品';
                    $val['goods_logo'] = $goodsInfo['thumb'] ? tomedia($goodsInfo['thumb']) : '../addons/weliam_smartcity/web/resource/images/nopic.jpg';
                    break;//团购
                case 'activity':
                    $goodsInfo = pdo_get(PDO_NAME . "activitylist", ['id' => $orderInfo['fkid']], ['title', 'thumb']);
                    $val['goods_name'] = $goodsInfo['title'] ? $goodsInfo['title'] : '活动报名';
                    $val['goods_logo'] = $goodsInfo['thumb'] ? tomedia($goodsInfo['thumb']) : '../addons/weliam_smartcity/web/resource/images/nopic.jpg';
                    break;//活动
                case 'halfcard':
                    $goods = pdo_get('wlmerchant_halfcard_type', array('id' => $orderInfo['typeid']), array('name'));
                    $val['goods_name'] = $goods['name'];
                    $val['goods_logo'] = $halfset['cardimg'] ? tomedia($halfset['cardimg']) : '../addons/weliam_smartcity/web/resource/images/nopic.jpg';
                    break;//会员卡
                case 'payonline':
                    $val['goods_name'] = '在线买单';
                    $val['goods_logo'] = tomedia(pdo_getcolumn(PDO_NAME . 'merchantdata', array('id' => $orderInfo['sid']), 'logo'));
                    break;//在线买单
                case 'pocket':
                    $val['goods_name'] = '掌上信息';
                    $val['goods_logo'] = tomedia(pdo_getcolumn(PDO_NAME . 'pocket_type', array('id' => $orderInfo['fkid']), 'img'));
                    break;//掌上信息
                case 'rush':
                    $goodsInfo = pdo_get(PDO_NAME . "rush_activity", ['id' => $orderInfo['activityid']], ['name', 'thumb']);
                    $val['goods_name'] = $goodsInfo['name'] ? $goodsInfo['name'] : '团购商品';
                    $val['goods_logo'] = $goodsInfo['thumb'] ? tomedia($goodsInfo['thumb']) : '../addons/weliam_smartcity/web/resource/images/nopic.jpg';
                    break;//抢购
                case 'hotel':
                    $goodsInfo = pdo_get(PDO_NAME . "hotel_room", ['id' => $orderInfo['fkid']], ['name', 'thumb']);
                    $val['goods_name'] = $goodsInfo['name'];
                    $val['goods_logo'] = $goodsInfo['thumb'] ? tomedia($goodsInfo['thumb']) : '../addons/weliam_smartcity/web/resource/images/nopic.jpg';
                    break;//酒店
                case 'vip':
                    $val['goods_name'] = '会员卡';
                    $val['goods_logo'] = '../addons/weliam_smartcity/web/resource/images/nopic.jpg';
                    break;//会员卡
                case 'citydelivery':
                	$mername = pdo_get(PDO_NAME . "merchantdata", ['id' => $orderInfo['sid']], ['storename', 'logo']);
                    $val['goods_name'] = '['.$mername['storename'].']配送商品';
                    $val['goods_logo'] = tomedia($mername['logo']);
                    break;//配送商品
            }
            //获取佣金信息
            if($type == 1){
            	if ($val['onegroupid'] == $mid) {
	                $val['commission'] = $val['onegroupmoney'];
	            } else if ($val['twogroupid'] == $mid ) {
	                $val['commission'] = $val['twogroupmoney'];
	            }
            }else if($type == 2){
            	$val['commission'] = pdo_getcolumn(PDO_NAME.'disdetail',array('disorderid'=>$val['id'],'status'=>3),'price');
            	if(empty($val['commission'])){
            		$sharenum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_distributor')." WHERE uniacid = {$_W['uniacid']} AND shareholder = 1");
            		$val['commission'] = sprintf("%.2f",$val['shareholdermoney'] / $sharenum);
            	}
            }else{
	            $moneyArr = unserialize($val['leadmoney']);
	            if ($val['oneleadid'] == $disId && $moneyArr['one'] > 0) {
	                $val['commission'] = $moneyArr['one'];
	            } else if ($val['twoleadid'] == $disId && $moneyArr['two'] > 0) {
	                $val['commission'] = $moneyArr['two'];
	            } else if ($val['threeleadid'] == $disId && $moneyArr['three'] > 0) {
	                $val['commission'] = $moneyArr['three'];
	            } else {
	                $val['commission'] = 0;
	            }
            }  
            //获取有效信息
            $val['consumption_type'] = $consumption_type;//消费类型 1=快递；2=核销；3=立即使用
            $val['num'] = $orderInfo['num'];//购买数量
            $val['orderno'] = $orderInfo['orderno'];//订单号
            $val['paytime'] = date("Y-m-d H:i:s", $orderInfo['paytime']);//支付时间
            $val['use_time'] = is_array($timeList) && count($timeList) > 0 ? $timeList : [];//快递/使用/操作时间列表
            //删除多余的信息内容
            unset($val['id']);//分销订单id
            unset($val['plugin']);//模块信息
            unset($val['orderid']);//订单id
            unset($val['buymid']);//用户id
            unset($val['leadmoney']);//佣金信息字符串
            unset($val['oneleadid']);//一级分销商id
            unset($val['twoleadid']);//二级分销商id
            unset($val['threeleadid']);//三级分销商id
        }
        $info['list'] = $list;

        $this->renderSuccess('推广订单列表', $info);
    }
    /**
     * Comment: 分销提现详细信息
     * Author: zzw
     * Date: 2019/7/19 9:18
     */
    public function detailsOfWithdrawal() {
        global $_W, $_GPC;
        $detailId = $_GPC['detail_id'];
        if (empty($detailId)) $this->renderError('缺少参数:detail_id');
        if ($detailId < 0){
            #1、获取提现信息
            $info = pdo_fetch("SELECT info FROM " . tablename(PDO_NAME . "settlement_temporary") . " WHERE mid = {$_W['mid']} ");
            if(empty($info)){
                $detailId = pdo_fetch("SELECT id FROM ".tablename('wlmerchant_settlement_record')."WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['uniacid']} AND type = 3 ORDER BY id DESC");
                $detailId = $detailId['id'];
            }else{
                $info = unserialize($info['info']);
                $info['applytime'] = date('Y-m-d H:i:s',$info['applytime']);
            }
        }
        if($detailId > 0){
            #1、获取提现信息
            $field = " payment_type,status,sapplymoney,sgetmoney,spercentmoney,FROM_UNIXTIME(applytime,'%Y-%m-%d %H:%i:%S') as applytime,FROM_UNIXTIME(updatetime,'%Y-%m-%d %H:%i:%S') as updatetime ";
            $info = pdo_fetch("SELECT {$field} FROM " . tablename(PDO_NAME . "settlement_record") . " WHERE id = {$detailId} ");
        }
        #2、获取当前提现状态信息
        $res = Distribution::getStatusDetailInfo($info['status']);
        $info['status_title'] = $res['title'];
        //$info['status_list'] = Distribution::getCashWithdrawalStateList();

        $this->renderSuccess('分销提现详细信息', $info);
    }
    /**
     * Comment: 分销商佣金排行榜信息
     * Author: zzw
     * Date: 2019/7/19 15:10
     */
    public function commissionRanking() {
        global $_W, $_GPC;
        $state = $_GPC['state'] ? $_GPC['state'] : 1;//1=周排行；2=月排行；3=总排行
        #1、条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.type = 1 AND a.plugin != 'cash' AND a.plugin != 'system' ";
        $groupOrder = " GROUP BY a.leadid ORDER BY total_price DESC ";
        switch ($state) {
            case 1:
                //当前日期
                $defaultDate = date("Y-m-d", time());
                $first = 1;//表示每周星期一为开始日期 0表示每周日为开始日期
                $w = date('w', strtotime($defaultDate)); //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
                $startTime = strtotime("$defaultDate -" . ($w ? $w - $first : 6) . ' days'); //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
                $endTime = strtotime(date('Y-m-d H:i:s', $startTime) . " +7 days ");//下一周开始时间
                $where .= " AND a.createtime > {$startTime} AND a.createtime < {$endTime} ";
                break;//周排行
            case 2:
                $startTime = strtotime(date("Y-m" . "-1 00:00:00"));//本月开始时间
                $endTime = strtotime(date("Y-m" . "-1 00:00:00") . " +1 month");//下月开始时间
                $where .= " AND a.createtime > {$startTime} AND a.createtime < {$endTime} ";
                break;//月排行
        }
        #2、获取排行榜信息  最前的10条信息
        $sql = "SELECT a.id,a.leadid,SUM(a.price) as total_price,b.nickname,b.avatar FROM "
            . tablename(PDO_NAME . "disdetail")
            . " a RIGHT JOIN "
            . tablename(PDO_NAME . "member") . " as b ON a.leadid = b.id ";
        $list = pdo_fetchall($sql . $where . $groupOrder . " LIMIT 10 ");
        #3、判断获取用户当前的排行信息
        $userInfo = pdo_fetch($sql . " WHERE leadid = {$_W['mid']} ");
        $userInfo['sort'] = -1;//默认没有加入排行
        if (is_array($list)) {
            foreach ($list as $key => &$val) {
                if ($val['leadid'] == $_W['mid']) {
                    $userInfo['sort'] = $key;
                }
                //删除多余的内容信息
                unset($val['id']);
                unset($val['leadid']);
            }
        }
        #4、删除多余的内容信息
        unset($userInfo['id']);
        unset($userInfo['leadid']);
        #5、信息拼装
        $info['user'] = $userInfo;
        $info['list'] = $list;

        $this->renderSuccess('分销商佣金排行榜信息', $info);
    }
    /**
     * Comment: 联系方式设置
     * Author: zzw
     * Date: 2019/7/22 9:35
     */
    public function setContactInformation() {
        global $_W, $_GPC;
        #1、信息判断
        if (!empty($_GPC['wechat_number'])) $data['wechat_number'] = $_GPC['wechat_number'];
        if (!empty($_GPC['wechat_qrcode'])) $data['wechat_qrcode'] = $_GPC['wechat_qrcode'];
        if (count($data) == 0) $this->renderError('请提交修改内容');
        #2、判断内容是否修改
        $data['id'] = $_W['mid'];
        $have = pdo_get(PDO_NAME . "member", $data);
        if ($have) $this->renderError('请修改后在提交');
        #2、修改用户的内容信息
        $result = pdo_update(PDO_NAME . 'member', $data, ['id' => $_W['mid']]);
        if ($result) $this->renderSuccess('修改成功');
        else $this->renderError('修改失败');
    }
    /**
     * Comment: 联系方式获取
     * Author: zzw
     * Date: 2019/7/22 9:16
     */
    public function getContactInformation() {
        global $_W, $_GPC;
        #1、获取用户信息
        $set = $this->disSetting ?: Setting::wlsetting_read("distribution");
        $info = pdo_get(PDO_NAME . "member", ['id' => $_W['mid']], ['wechat_number', 'wechat_qrcode']);
        $info['wechat_number'] = $info['wechat_number'] ?: '';
        $info['wechat_qrcode'] = $info['wechat_qrcode'] ? tomedia($info['wechat_qrcode']) : '';
        #2、获取上级分销商的信息
        $info['is_head'] = pdo_getcolumn(PDO_NAME . "distributor", ['mid' => $_W['mid']], 'leadid');
        if ($info['is_head'] > 0) {
            $headInfo = pdo_get(PDO_NAME . "member", ['id' => $info['is_head']], ['wechat_number', 'wechat_qrcode', 'nickname', 'mobile', 'avatar']);
            if(empty($headInfo['wechat_qrcode']) || empty($headInfo['wechat_number'])){
                $headInfo['hidehead'] = 1;//代表隐藏上级分销商信息
            }else{
                $headInfo['wechat_qrcode'] = tomedia($headInfo['wechat_qrcode']);
            }
        }else{
            $headInfo['hidehead'] = 1;//代表隐藏上级分销商信息
        }
        //获取社群设置信息
        if ($set['communityid'] > 0) {
            $community = pdo_get(PDO_NAME . "community", ['id' => $set['communityid']]
                , ['communname', 'commundesc', 'communimg', 'communqrcode']);
            $comm['title'] = $community['communname'];
            $comm['desc'] = $community['commundesc'];
            $comm['qrcode'] = tomedia($community['communqrcode']);
            $comm['images'] = tomedia($community['communimg']);
        }else{
            $comm['hidecomm'] = 1;//代表隐藏社群
        }
        #2、信息拼装
        $data['user_info'] = $info;
        $data['head_info'] = $headInfo;
        $data['comm_info'] = $comm;
        $data['text_set']['dstext'] = $set['dstext'] ? : '导师';
        $data['text_set']['dsinfotext'] = $set['dsinfotext'] ? : '想赚取更多利益，获得更多优惠？快联系你的指导老师拜师学艺吧！';
        $data['text_set']['grinfotext'] = $set['grinfotext'] ? : '为了让你的社群成员能够更方便的找到你，请上传你的微信二维码';

        $this->renderSuccess('联系方式获取', $data);
    }
    /**
     * Comment: 获取分销商帮助说明
     * Author: zzw
     * Date: 2019/7/24 14:10
     */
    public function getHelpNote() {
        $info = $this->disSetting['distriqa'];
        $this->renderSuccess('帮助说明', $info);
    }
    /**
     * Comment: 查询上级分销商
     * Author: wlf
     * Date: 2020/07/29 17:05
     */
    public function choselead() {
        global $_W, $_GPC;
        $keyword = trim($_GPC['keyword']);
        if(empty($keyword)){
            $this->renderError('请输入查询关键字');
        }
        $list = pdo_fetchall("SELECT a.id,a.nickname,a.avatar FROM " . tablename(PDO_NAME . "member")
            . " as a LEFT JOIN " . tablename(PDO_NAME . "distributor")
            . " as b ON a.distributorid = b.id WHERE b.disflag = 1 AND a.uniacid = {$_W['uniacid']} AND ( a.nickname LIKE '%{$keyword}%' OR a.id = '{$keyword}' ) ORDER BY a.id DESC");
        foreach($list as &$li){
            $li['avatar'] = tomedia($li['avatar']);
        }
        $this->renderSuccess('查询结果', $list);
    }


}
