<?php
defined('IN_IA') or exit('Access Denied');


class Draw {
    /**
     * Comment: 获取商品信息
     * Author: zzw
     * Date: 2020/9/15 10:47
     * @param $id
     * @return bool|mixed
     */
    public static function prizeInfo($id){
        //获取奖品信息
        $info = pdo_get(PDO_NAME."draw_goods",['id'=>$id]);
        //处理奖品信息   奖品类型:1=现金红包,2=线上红包,3=积分,4=激活码,5=商品
        switch ($info['type']){
            case 1:break;//现金红包
            case 2:
                $field = "id as red_pack_id,title as red_pack_name";
                $where = " id = {$info['goods_id']} ";
                $redPack = self::getInfo($field,"redpacks",$where);
                //赋值
                $info['red_pack_id'] = $redPack['red_pack_id'];
                $info['red_pack_name'] = $redPack['red_pack_name'];
                break;//线上红包
            case 3:break;//积分
            case 4:break;//激活码  rush  groupon  wlfightgroup  coupon  bargain
            case 5:
                //根据商品类型获取商品信息
                switch ($info['goods_plugin']) {
                    case 'rush':
                        $field = "name as goods_name";
                        $where = " id = {$info['goods_id']} ";
                        $goods = self::getInfo($field,"rush_activity",$where);
                        break;//抢购
                    case 'groupon':
                        $field = "name as goods_name";
                        $where = " id = {$info['goods_id']} ";
                        $goods = self::getInfo($field,"groupon_activity",$where);
                        break;//团购
                    case 'wlfightgroup':
                        $field = "name as goods_name";
                        $where = " id = {$info['goods_id']} ";
                        $goods = self::getInfo($field,"fightgroup_goods",$where);
                        break;//拼团
                    case 'coupon':
                        $field = "title as goods_name";
                        $where = " id = {$info['goods_id']} ";
                        $goods = self::getInfo($field,"couponlist",$where);
                        break;//卡卷
                    case 'bargain':
                        $field = "name as goods_name";
                        $where = " id = {$info['goods_id']} ";
                        $goods = self::getInfo($field,"bargain_activity",$where);
                        break;//砍价
                }
                //赋值
                $info['goods_name'] = $goods['goods_name'];
                break;//商品
        }

        return $info;
    }
    /**
     * Comment: 根据内容获取信息
     * Author: zzw
     * Date: 2020/9/15 10:41
     * @param $field
     * @param $table
     * @param $where
     * @return array
     */
    protected static function getInfo($field,$table,$where){
        $info = pdo_fetch("SELECT {$field} FROM " .tablename(PDO_NAME.$table)." WHERE ".$where);

        return $info ? : [];
    }
    /**
     * Comment: 获取某个抽奖活动相关的所有奖品信息列表
     * Author: zzw
     * Date: 2020/9/16 17:10
     * @param $id
     * @return array|bool|mixed
     */
    public static function drawJoinList($id){
        global $_W,$_GPC;
        $field = "a.probability,a.serial_number,
        CASE WHEN a.draw_goods_id > 0 THEN b.id 
             ELSE 0
        END as draw_goods_id,
        CASE WHEN b.title != '' THEN b.title 
             ELSE '谢谢参与'
        END as draw_goods_name,
        CASE WHEN a.draw_goods_id > 0 THEN b.image 
             ELSE ''
        END as draw_goods_image";
        $sql = " SELECT {$field} FROM ".tablename(PDO_NAME.'draw_join')
            ." as a LEFT JOIN ".tablename(PDO_NAME."draw_goods")
            ." as b ON a.draw_goods_id = b.id AND a.draw_goods_id > 0 AND b.uniacid = {$_W['uniacid']} AND b.aid = {$_W['aid']}"
            ." WHERE a.draw_id = {$id} ORDER BY a.serial_number ASC ";
        $list = pdo_fetchall($sql);

        foreach($list as $listKey => &$listVal){
            $listVal['draw_goods_image'] = $listVal['draw_goods_image'] ? tomedia($listVal['draw_goods_image']) : '../addons/weliam_smartcity/web/resource/images/not-prize.png';
        }

        return is_array($list) && $list ? $list : self::defaultDrawJoinList($id);
    }
    /**
     * Comment: 生成默认的活动相关奖品信息列表
     * Author: zzw
     * Date: 2020/9/16 17:14
     * @param $id
     * @return array
     */
    public static function defaultDrawJoinList($id){
        //活动类型：1=9宫格，2=16宫格
        $type = pdo_getcolumn(PDO_NAME."draw",['id'=>$id],'type');
        if ($type == 1) $max = 9;
        else $max = 16;
        //生成默认列表
        $list = [];
        for($i=1;$i<=$max;$i++){
            $list[$i] = [
                'draw_goods_id'    => 0 ,
                'draw_goods_name'  => '谢谢参与' ,
                'draw_goods_image' => '' ,
                'probability'      => 10 ,
                'serial_number'    => $i ,
            ];
        }

        return $list;
    }
    /**
     * Comment: 获取某个抽奖活动相关的活动信息、奖品信息、商户信息
     * Author: zzw
     * Date: 2020/9/17 14:01
     * @param $id
     * @return array
     */
    public static function getDrawDetailedInfo($id){
        global $_W;
        $set = Setting::agentsetting_read('draw_set');
        //活动信息获取
        $info = self::getDrawActivityInfo($id);
        $info['rule']             = $info['rule'] ? : htmlspecialchars_decode($set['rule']) ;
        $info['introduce']        = $info['introduce'] ? : htmlspecialchars_decode($set['introduce']) ;
        //获取所有相关的奖品信息a.draw_goods_id,
        $field = "a.id,a.probability,a.serial_number,b.type,b.sid,b.title,b.image,b.day_number,b.total_number,b.status,b.code_keyword,
        CASE WHEN a.draw_goods_id > 0 AND b.id > 0 THEN a.draw_goods_id
             ELSE 0
        END as draw_goods_id";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."draw_join")
            ." as a LEFT JOIN ".tablename(PDO_NAME."draw_goods")
            ." as b ON a.draw_goods_id = b.id "
            ." WHERE a.draw_id = {$id} ORDER BY a.serial_number ASC ";
        $prize = pdo_fetchall($sql);
        $startTime = strtotime(date("Y-m-d 00:00:00",time()));
        $endTime = strtotime(date("Y-m-d 23:59:59",time()));
        $shop = [];//商户信息列表
        foreach($prize as $pKey => &$pVal){
            //图片信息处理
            $pVal['image'] = tomedia($pVal['image']);



/*            //判断奖品是否启用 状态:1=开启，2=关闭
            if($pVal['status'] == 2){
                //未启用  改为谢谢参与，概率为公共概率
                $pVal['draw_goods_id'] = 0;
                $pVal['title'] = '谢谢参与';
                //$pVal['probability'] = $set['not_probability'];
            }
            //判断奖品当日份数是否已经抽取完毕
            if($pVal['day_number'] > 0){
                $dayTotalSql = "SELECT count(*) FROM ".tablename(PDO_NAME."draw_record")
                    ." WHERE draw_goods_id = {$pVal['draw_goods_id']} "
                    ." AND create_time > {$startTime} AND create_time <= {$endTime} ";
                $dayTotal = pdo_fetchcolumn($dayTotalSql);
                if($dayTotal >= $pVal['day_number']){
                    //今日份数已抽取完毕  改为谢谢参与，概率为公共概率
                    $pVal['draw_goods_id'] = 0;
                    $pVal['title'] = '谢谢参与';
                    //$pVal['probability'] = $set['not_probability'];
                }
            }
            //判断奖品总份数是否已经抽取完毕
            if($pVal['total_number'] > 0){
                $totalSql = "SELECT count(*) FROM ".tablename(PDO_NAME."draw_record")
                    ." WHERE draw_goods_id = {$pVal['draw_goods_id']}";
                $total = pdo_fetchcolumn($totalSql);
                if($total >= $pVal['total_number']){
                    //总份数已抽取完毕  改为谢谢参与，概率为公共概率
                    $pVal['draw_goods_id'] = 0;
                    $pVal['title'] = '谢谢参与';
                    //$pVal['probability'] = $set['not_probability'];
                }
            }
            //商品为激活码  判断激活码序列是否已经全部送出
            if($pVal['type'] == 4){
                $codeWhere = [
                    'uniacid' => $_W['uniacid'] ,
                    'status'  => 0 ,
                    'remark'  => $pVal['code_keyword'] ,
                ];
                $codeSurplus = pdo_count(PDO_NAME."token",$codeWhere);
                if($codeSurplus <= 0){
                    $pVal['draw_goods_id'] = 0;
                    $pVal['title'] = '谢谢参与';
                    //$pVal['probability'] = $set['not_probability'];
                }
            }*/




            //判断是否存在商户信息 存在则获取对应的商户信息
            if($pVal['sid'] > 0 && $pVal['draw_goods_id'] > 0){
                $shop[$pKey] = pdo_get(PDO_NAME."merchantdata",['id'=>$pVal['sid']],['id','storename','logo']);
                $shop[$pKey]['logo'] = tomedia($shop[$pKey]['logo']);
                $shop[$pKey]['goods_logo'] = tomedia($pVal['image']);
                $shop[$pKey]['goods_name'] = $pVal['title'];
            }
        }

        return [$info,$prize,array_values($shop)];
    }
    /**
     * Comment: 获取某个活动的基本信息
     * Author: zzw
     * Date: 2020/9/17 16:42
     * @param $id
     * @return bool|mixed
     */
    public static function getDrawActivityInfo($id){
        global $_W,$_GPC;
        //活动信息获取
        $info                     = pdo_get(PDO_NAME . "draw" , ['id' => $id]);
        $info['prize_image']      = $info['prize_image'] ? tomedia($info['prize_image']) : '';
        $info['not_prize_image']  = $info['not_prize_image'] ? tomedia($info['not_prize_image']) : '';
        $info['background_image'] = $info['background_image'] ? tomedia($info['background_image']) : '';
        $info['background_music'] = $info['background_music'] ? tomedia($info['background_music']) : '';
        $info['start_image']      = $info['start_image'] ? tomedia($info['start_image']) : '';
        $info['use_music']        = $info['use_music'] ? tomedia($info['use_music']) : '';
        $info['button_more']      = $info['button_more'] ? tomedia($info['button_more']) : '';
        $info['button_draw']      = $info['button_draw'] ? tomedia($info['button_draw']) : '';
        $info['button_prize']     = $info['button_prize'] ? tomedia($info['button_prize']) : '';
        $info['rule']             = htmlspecialchars_decode($info['rule']);
        $info['introduce']        = htmlspecialchars_decode($info['introduce']);
        $info['pv']               = intval($info['fictitious_pv']) + intval($info['pv']);
        //处理顶部幻灯片
        if(!empty($info['share_image'])){
            $flag = unserialize($info['share_image']);
            if($flag){
                $info['share_image'] = $flag;
            }else{
                $flag = $info['share_image'];
                $info['share_image'] = [];
                $info['share_image'][] = $flag;
            }
            foreach ($info['share_image'] as &$th) {
                $th = tomedia($th);
            }
        }else{
            $info['share_image'] = '';
        }
        //用户登录后  判断当前用户剩余参与次数、剩余中奖次数、当日剩余参与次数、当日剩余中奖次数
        $info['surplus_total_join_times'] = $info['total_join_times'] ? : 0;//剩余免费参与次数
        $info['surplus_total_draw_times'] = $info['total_draw_times'] ? : 0;//剩余中奖次数
        $info['surplus_total_parin_times'] = $info['total_parin_times'] ? : 0;//剩余总参与次数
        $info['surplus_day_join_times'] = $info['day_join_times'] ? : 0;//当日剩余免费参与次数
        $info['surplus_day_draw_times'] = $info['day_draw_times'] ? : 0;//当日剩余中奖次数
        $info['surplus_day_parin_times'] = $info['day_parin_times'] ? : 0;//当日剩余总共参与次数
        if($_W['mid'] > 0){
            $startTime = strtotime(date("Y-m-d 00:00:00",time()));
            $endTime = strtotime(date("Y-m-d 23:59:59",time()));
            //获取  剩余免费参与次数
            if($info['total_join_times'] > 0){
                $totalJoinTimes = pdo_count(PDO_NAME."draw_record",['draw_id'=>$id,'mid'=>$_W['mid'],'is_free'=>1]);//已免费参与次数
                $info['surplus_total_join_times'] = $info['total_join_times'] - $totalJoinTimes;
            }
            //获取 剩余参加次数
            if($info['total_parin_times'] > 0){
                $allJoinTimes = pdo_count(PDO_NAME."draw_record",['draw_id'=>$id,'mid'=>$_W['mid']]);//已参与次数
                $info['surplus_total_parin_times'] = $info['total_parin_times'] - $allJoinTimes;
            }
            //获取  剩余中奖次数
            if($info['total_draw_times'] > 0){
                $totalDrawTimes = pdo_count(PDO_NAME . "draw_record" , [
                    'draw_id'         => $id ,
                    'mid'             => $_W['mid'] ,
                    'draw_goods_id >' => 0
                ]);//已中奖次数
                $info['surplus_total_draw_times'] = $info['total_draw_times'] - $totalDrawTimes;
            }
            //获取  当日剩余免费参与次数
            $totalDayJoinTimes = pdo_count(PDO_NAME . "draw_record" , [
                'draw_id'        => $id ,
                'mid'            => $_W['mid'] ,
                'create_time >'  => $startTime ,
                'create_time <=' => $endTime ,
                'is_free'        => 1,
            ]);//当日已参与次数
            $info['surplus_day_join_times'] = $info['day_join_times'] - $totalDayJoinTimes;
            //获取  当日剩余免费参与次数
            $allDayJoinTimes = pdo_count(PDO_NAME . "draw_record" , [
                'draw_id'        => $id ,
                'mid'            => $_W['mid'] ,
                'create_time >'  => $startTime ,
                'create_time <=' => $endTime ,
            ]);//当日已参与次数
            $info['surplus_day_parin_times'] = $info['day_parin_times'] - $allDayJoinTimes;
            //获取  当日剩余中奖次数
            if($info['day_draw_times'] > 0){
                $totalDayDrawTimes = pdo_count(PDO_NAME . "draw_record" , [
                    'draw_id'         => $id ,
                    'mid'             => $_W['mid'] ,
                    'create_time >'   => $startTime ,
                    'create_time <='  => $endTime ,
                    'draw_goods_id >' => 0
                ]);//当日已参与次数
                $info['surplus_day_draw_times'] = $info['day_draw_times'] - $totalDayDrawTimes;
            }
        }
        //剩余信息处理一
        $info['surplus_total_join_times'] = $info['surplus_total_join_times'] > 0 ? $info['surplus_total_join_times'] : 0 ;//剩余参与次数（总免费）
        $info['surplus_total_draw_times'] = $info['surplus_total_draw_times'] > 0 ? $info['surplus_total_draw_times'] : 0 ;//剩余中奖次数（总）
        $info['surplus_day_join_times'] = $info['surplus_day_join_times'] > 0 ? $info['surplus_day_join_times'] : 0 ;//当日剩余参与次数（天免费）
        $info['surplus_day_draw_times'] = $info['surplus_day_draw_times'] > 0 ? $info['surplus_day_draw_times'] : 0 ;//当日剩余中奖次数（天）
        $info['surplus_total_parin_times'] = $info['surplus_total_parin_times'] > 0 ? $info['surplus_total_parin_times'] : 0 ;//剩余参与次数（总共）
        $info['surplus_day_parin_times'] = $info['surplus_day_parin_times'] > 0 ? $info['surplus_day_parin_times'] : 0 ;//剩余参与次数（天免费）

        //剩余信息处理二  当天剩余信息不能大于总剩余信息
        if($info['total_join_times'] > 0) $info['surplus_day_join_times'] = $info['surplus_day_join_times'] > $info['surplus_total_join_times'] ? $info['surplus_total_join_times'] : $info['surplus_day_join_times'] ;//当日剩余参与次数（天免费）
        if($info['total_draw_times'] > 0) $info['surplus_day_draw_times'] = $info['surplus_day_draw_times'] > $info['surplus_total_draw_times'] ? $info['surplus_total_draw_times'] : $info['surplus_day_draw_times'] ;//当日剩余中奖次数（天）
        if($info['total_parin_times'] > 0) $info['surplus_day_parin_times'] = $info['surplus_day_parin_times'] > $info['surplus_total_parin_times'] ? $info['surplus_total_parin_times'] : $info['surplus_day_parin_times'] ;//当日剩余参与次数（天）
        //获取统计信息
        $info['actual_visit'] = pdo_count(PDO_NAME."draw_record",['draw_id'=>$id]);//当前活动参加人数
        $info['actual_prize'] = pdo_count(PDO_NAME."draw_record",['draw_id'=>$id,'draw_goods_id >'=>0]);//已中奖人数
        //虚拟信息 + 实际信息 = 最后的统计信息
        $info['visit'] = intval($info['fictitious_visit']) + intval($info['actual_visit']);//参加人数
        $info['prize'] = intval($info['fictitious_prize']) + intval($info['actual_prize']);//中奖人数
        //状态判断 状态：1=未开启，2=使用中，【3=未开始，4=已过期】
        if($info['status'] == 2 && $info['start_time'] >= time()) $info['status'] = 3;
        if($info['status'] == 2 && $info['end_time'] <= time()) $info['status'] = 4;

        return $info;
    }
    /**
     * Comment: 获取某个活动最近的10条中奖信息  如果中奖信息小于3条则获取虚拟中奖信息
     * Author: zzw
     * Date: 2020/9/17 14:59
     * @param $id
     * @param $fictitiousPrize
     * @param $prize
     * @return array|bool|mixed
     */
    public static function getDrawRecord($id,$fictitiousPrize,$prize){
        global $_W,$_GPC;
        //信息列表获取  仅获取最近10条中奖信息
        $sql = "SELECT b.nickname,g.title FROM ".tablename(PDO_NAME."draw_record")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id LEFT JOIN ".tablename(PDO_NAME."draw_goods")
            ." as g ON a.draw_goods_id = g.id WHERE a.draw_id = {$id} AND a.draw_goods_id > 0 ORDER BY a.create_time DESC " ;
        $list = pdo_fetchall($sql);
        //当真实中奖人数小于3   并且存在虚拟中奖人数时，生成10条虚拟中奖人数
        if(count($list) < 3 && $fictitiousPrize > 0){
            //获取奖品信息列表
            $prizeList = [];
            foreach($prize as $item){
                if($item['draw_goods_id'] > 0) $prizeList[] = $item;
            }
            //文本字符串定义
            $string  = '半夜时分一个人躺在床上四处静谧无声有一种孤独的感觉如爬虫般悄悄爬上我的心头辗转反侧无法入眠轻轻';
            $string .= '无助有一种美丽叫孤独对耐不住寂寞的人来说孤独是可怕的是恐惧的而对我来说孤独是生命圆满的开始是一';
            $string .= '孤独的在下着大雪的江面上一叶小舟一个老渔翁独自在寒冷的江心垂钓天地之间是如此纯洁而寂静只剩下他';
            $string .= '失意郁闷和苦恼时隐居在山水之间寄托自己清高而孤傲情感的真实写照他的幽静过于孤独过于冷清不带一点';
            $string .= '多时候这孤独总会被周遭的喧嚣浮华所蒙蔽以致造成繁荣的假象殊不知不理会这种孤独在某种意义上而言我';
            $string .= '己的心跳和呼吸寻找到迷失的自我知不觉我也喜欢上了孤独我的孤独和风月无关和苦闷无关有着浓浓的烟火';
            $string .= '地做自己喜欢的事任身心徜徉暂时忘却柴米油盐酱醋茶的烦琐去体验琴棋书画诗酒花的高雅暂时抛开追名逐';
            $string .= '中的充实祥和于是体会孤独感受孤独不失为一种最佳的休闲身体可以在孤独中得到休养繁重的体力超负的劳';
            $string .= '不再为生活中尔虞我诈的争斗而烦恼不再为日常生活的重负而苦闷而在孤独中寻找适合调整心情的方式让心';
            $string .= '古今写写心声种种花草多一份孤独的快乐少一份无为的浪费让生命在富有创造精神的孤独中度过让生命时光';
            $string .= '于是你就会明白能够真正拥有孤独的人是世界上最幸福的人是的我就是这样享受孤独因为我只是万千世界中';
            $string .= '它折射出一个人潜藏的能量孤独是一味珍宝它蕴涵着高贵的情愫和追求孤独是一场燃烧它灿烂的火光给人温';
            //通过循环获取20组虚拟提现的数据
            $string = preg_split('//u', $string, -1, PREG_SPLIT_NO_EMPTY);
            $list = [];
            for ($i = 0; $i < 10; $i++) {
                shuffle($string);
                $list[$i]['nickname'] = $string[0] .$string[1] .$string[2] .$string[3].$string[4];
                $list[$i]['title'] = $prizeList[array_rand($prizeList)]['title'];
            }
        }
        //循环处理用户昵称
        foreach($list as &$prizeInfo){
            $nicknameArr = preg_split('//u', $prizeInfo['nickname'], -1, PREG_SPLIT_NO_EMPTY);
            $prizeInfo['nickname'] = $nicknameArr[0].'***'.$nicknameArr[count($nicknameArr) - 1];
        }

        return $list;
    }
    /**
     * Comment: 获取好友助力信息
     * Author: zzw
     * Date: 2020/9/17 15:49
     * @param int $type
     * @return array
     */
    public static function getHelpList($type = 1,$drawid){
        global $_W,$_GPC;
        $where = " WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} AND type = {$type} AND activity_id = {$drawid} ";
        //助力好友总数获取
        $data['total_friends'] = count(pdo_fetchall("SELECT count(*) FROM" .tablename(PDO_NAME."draw_help").$where." GROUP BY click_mid"));
        //助力获取积分总数
        $data['total_integral'] = pdo_fetchcolumn("SELECT sum(integral) as integral FROM" .tablename(PDO_NAME."draw_help").$where);
        //列表信息获取
        $sql = "SELECT b.nickname,b.avatar,SUM(integral) as total_integral FROM ".tablename(PDO_NAME."draw_help")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id WHERE a.activity_id = {$drawid} AND a.type = {$type} AND a.uniacid = {$_W['uniacid']}"
            ." GROUP BY a.mid ORDER BY total_integral DESC,a.create_time ASC LIMIT 10";
        $list = pdo_fetchall($sql);
        $data['list'] = $list ? : [];

        return $data;
    }
    /**
     * Comment: 抽奖操作  通过递归进行抽奖  直到抽中未知
     * Author: zzw
     * Date: 2020/9/18 9:15
     * @param $prize
     * @return mixed
     */
    public static function drawOperation($prize,$id){
        global $_W;
        //概率数组的总概率精度 循环抽取奖品
        $proArr = array_column($prize, 'probability');
        $proSum = array_sum($proArr);
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if($proCur > 0){
                if ($randNum <= $proCur) {
                    $info = $prize[$key];
                    break;
                } else {
                    $proSum -= $proCur;
                }
            }
        }

        //处理奖品信息
        if($info['draw_goods_id'] > 0){
            $field = "a.id,a.probability,a.serial_number,b.type,b.sid,b.title,b.image,b.day_number,b.total_number,b.status,b.code_keyword,
        CASE WHEN a.draw_goods_id > 0 AND b.id > 0 THEN a.draw_goods_id
             ELSE 0
        END as draw_goods_id,b.goods_id,b.get_type,b.prize_number,b.day_prize,b.total_prize";
            $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."draw_join")
                ." as a LEFT JOIN ".tablename(PDO_NAME."draw_goods")
                ." as b ON a.draw_goods_id = b.id "
                ." WHERE a.draw_goods_id = {$info['draw_goods_id']} AND a.draw_id = {$id} ";
            $goods = pdo_fetch($sql);
            $info = array_merge($info,$goods);
            //判断是否抽中
            $startTime = strtotime(date("Y-m-d",time())." 00:00:00");
            $endTime = strtotime(date("Y-m-d",time())." 23:59:59");
            //判断今日该商品中奖次数是否到达限制
            if($info['day_prize'] > 0){
                $dayTotal = pdo_count(PDO_NAME . "draw_record" , [
                    'mid'            => $_W['mid'] ,
                    'draw_id'        => $id ,
                    'draw_goods_id'  => $info['draw_goods_id'] ,
                    'create_time >=' => $startTime ,
                    'create_time <=' => $endTime ,
                ]);
                if($info['day_prize'] <= $dayTotal) return self::returnNotPrize($prize);
            }
            //判断该商品总中奖次数是否到达限制
            if($info['total_prize'] > 0){
                $totalTotal = pdo_count(PDO_NAME . "draw_record" , [
                    'mid'            => $_W['mid'] ,
                    'draw_id'        => $id ,
                    'draw_goods_id'  => $info['draw_goods_id'] ,
                ]);
                if($info['total_prize'] <= $totalTotal) return self::returnNotPrize($prize);
            }
            //判断奖品是否启用 状态:1=开启，2=关闭
            if($info['status'] == 2) return self::returnNotPrize($prize);
            //判断奖品当日份数是否已经抽取完毕
            if($info['day_number'] > 0){
                $dayTotalSql = "SELECT count(*) FROM ".tablename(PDO_NAME."draw_record")
                    ." WHERE draw_goods_id = {$info['draw_goods_id']} "
                    ." AND create_time >= {$startTime} AND create_time <= {$endTime} ";
                $dayTotal = pdo_fetchcolumn($dayTotalSql);
                //今日份数已抽取完毕  改为谢谢参与
                if($dayTotal >= $info['day_number']) return self::returnNotPrize($prize);
            }
            //判断奖品总份数是否已经抽取完毕
            if($info['total_number'] > 0){
                $totalSql = "SELECT count(*) FROM ".tablename(PDO_NAME."draw_record")
                    ." WHERE draw_goods_id = {$info['draw_goods_id']}";
                $total = pdo_fetchcolumn($totalSql);
                if($total >= $info['total_number']) return self::returnNotPrize($prize);
            }
            //商品为激活码  判断激活码序列是否已经全部送出
            if($info['type'] == 4){
                $codeWhere = [
                    'uniacid' => $_W['uniacid'] ,
                    'status'  => 0 ,
                    'remark'  => $info['code_keyword'] ,
                ];
                $codeSurplus = pdo_count(PDO_NAME."token",$codeWhere);
                if($codeSurplus <= 0) return self::returnNotPrize($prize);
            }
        }

        return $info;
    }
    /**
     * Comment: 随机返回一个未中奖信息
     * Author: zzw
     * Date: 2020/10/19 18:31
     * @param $prize
     * @return mixed
     */
    public static function returnNotPrize($prize){
        //循环获取谢谢参与信息
        $errorArr = [];
        foreach($prize as $vo){
            if($vo['draw_goods_id'] == 0){
                $errorArr[] = $vo;
            }
        }
        //随机获取一个谢谢参与返回
        return count($errorArr) > 0 ? $errorArr[array_rand($errorArr)] : [];
    }
    /**
     * Comment: 抽中后奖品的处理
     * Author: zzw
     * Date: 2020/9/21 15:12
     * @param $info
     * @throws Exception
     */
    public static function prizeProcessing($info){
        global $_W;
        //根据抽奖结果进行对应的操作
        if($info['draw_goods_id'] == 0){
            self::changeIsGetStatus($info['draw_record_id']);
        }else{
            //抽中奖品  根据奖品类型进行对应的处理操作  奖品类型:1=现金红包,2=线上红包,3=积分,4=激活码,5=商品
            switch (intval($info['type'])){
                case 1:
                    //判断领取方式   领取方式：1=发送现金红包，2=增加到余额
                    if($info['get_type'] == 1){
                        //订单号生成
                        if(!$info['order_no']){
                            $info['order_no'] = 'DRAW'.date("YmdHis").random(10,true);//订单号生成
                            pdo_update(PDO_NAME."draw_record" , ['order_no' => $info['order_no']] , ['id' => $info['draw_record_id']]);
                        }
                        //发送现金红包
                        Payment::cashRedPack($info);
                        self::changeIsGetStatus($info['draw_record_id']);
                    }else{
                        //增加到余额
                        $remark = "活动【{$info['draw_title']}】奖品【{$info['title']}】";
                        if (Member::credit_update_credit2($_W['mid'] , $info['prize_number'] , $remark)) self::changeIsGetStatus($info['draw_record_id']);
                    }
                    break;//现金红包
                case 2:
                    //线上红包领取
                    $res = Redpack::pack_send($_W['mid'] , $info['goods_id'] , 'draw' , $info['source']);
                    if ($res['errno'] == 1) throw new Exception($res['message']);
                    else self::changeIsGetStatus($info['draw_record_id']);
                    break;//线上红包
                case 3:
                    $remark = "活动【{$info['draw_title']}】奖品【{$info['title']}】";
                    if (Member::credit_update_credit1($_W['mid'] , $info['prize_number'] , $remark)) self::changeIsGetStatus($info['draw_record_id']);
                    break;//积分
                case 4:
                    //获取一个激活码
                    $codeInfo = Halfcard::getActivationCode($info['code_keyword']);
                    if(!array_key_exists('errno',$codeInfo)){
                        //记录激活码信息
                        $params = [
                            'token_id' => $codeInfo['id'] ,
                            'is_get'   => 2 ,//修改状态为已领取 但是激活码未使用
                        ];
                        pdo_update(PDO_NAME."draw_record" , $params , ['id' => $info['draw_record_id']]);
                    }else{
                        pdo_update(PDO_NAME."draw_record" , [
                            'draw_goods_id' => 0 , //修改状态为 未中奖
                            'is_get'   => 2 ,//修改状态为已领取
                        ] , ['id' => $info['draw_record_id']]);
                        throw new Exception("手慢一步，激活码已被抽取完毕，请刷新重试！");
                    }
                    break;//激活码
                case 5:break;//商品
            }
        }
    }
    /**
     * Comment: 记录抽奖信息
     * Author: zzw
     * Date: 2020/9/21 9:49
     * @param     $info
     * @param int $isGet
     * @return mixed
     * @throws Exception
     */
    public static function drawResRecord($info,$isGet = 1){
        global $_W,$_GPC;
        //参数信息生成
        $data = [
            'uniacid'       => $_W['uniacid'] ,
            'aid'           => $_W['aid'] ,
            'mid'           => $_W['mid'] ,
            'draw_id'       => $info['draw_id'] ,
            'draw_goods_id' => $info['draw_goods_id'] ,
            'create_time'   => time() ,
            'is_get'        => $isGet ,//是否领取奖品:1=未领取，2=已领取
            'is_free'       => $info['is_free'],//当前抽奖是否为免费抽奖:1=免费,2=使用积分抽奖
        ];
        //记录抽奖信息
        $res = pdo_insert(PDO_NAME."draw_record",$data);
        if($res) return pdo_insertid();
        else throw new Exception('抽奖信息错误！');
    }
    /**
     * Comment: 修改抽奖 - 奖品领奖记录为已领取
     * Author: zzw
     * Date: 2020/9/21 13:55
     * @param $id
     */
    public static function changeIsGetStatus($id){
        //修改领取状态为已领取
        pdo_update(PDO_NAME."draw_record" , ['is_get' => 2] , ['id' => $id]);
    }
    /**
     * Comment: 获取抽奖统计信息
     * Author: zzw
     * Date: 2020/9/22 11:34
     * @return mixed
     */
    public static function getStatistics(){
        global $_W;
        //基本参数信息
        $monthStart = strtotime(date("Y-m-1 00:00:00"));//当前月开始时间
        $monthEnd   = strtotime("+1 month ",strtotime(date("Y-m-1 00:00:00")));//下月开始时间
        $dayStart = strtotime(date("Y-m-d 00:00:00"));//当天开始时间
        $dayEnd   = strtotime("+1 day ",strtotime(date("Y-m-d 00:00:00")));//明天开始时间
        //总抽奖次数
        $data['total_draw'] = self::selectCount();
        //总中奖次数
        $data['total_prize'] = self::selectCount(['draw_goods_id >' => 0]);
        //当前月总抽奖次数
        $data['month_total_draw'] = self::selectCount(['create_time >' => $monthStart , 'create_time <' => $monthEnd]);
        //当前月总中奖次数
        $data['month_total_prize'] = self::selectCount(['draw_goods_id >' => 0,'create_time >' => $monthStart ,'create_time <' => $monthEnd]);
        //当天总抽奖次数
        $data['day_total_draw'] = self::selectCount(['create_time >' => $dayStart , 'create_time <' => $dayEnd]);
        //当天总中奖次数
        $data['day_total_prize'] = self::selectCount(['draw_goods_id >' => 0,'create_time >'   => $dayStart ,'create_time <' => $dayEnd]);

        return $data;
    }
    /**
     * Comment: 根据条件获取抽奖记录信息总数
     * Author: zzw
     * Date: 2020/9/22 11:28
     * @param array $where
     * @return int
     */
    public static function selectCount($where = []){
        global $_W;
        //基本条件生成
        $where['uniacid'] = $_W['uniacid'];
        $where['aid']     = $_W['aid'];
        //统计总数获取
        return pdo_count(PDO_NAME."draw_record",$where);
    }
    /**
     * Comment: 导出中奖信息
     * Author: zzw
     * Date: 2020/9/23 10:37
     * @param $sql
     */
    public static function exportInfoList($sql){
        //信息列表获取
        $list = pdo_fetchall($sql." LIMIT 20000");
        //循环处理内容 并且删除多余的内容
        foreach($list as &$item){
            //图片信息处理
//            $item['avatar'] = tomedia($item['avatar']);
//            $item['image'] = tomedia($item['image']);
            //时间处理
            $item['create_time'] = date("Y-m-d H:i:s",$item['create_time']);
            //判断是否中   未中奖则将奖品标题修改为未中奖、奖品图片修改为空
            if($item['draw_goods_id'] <= 0){
                $item['title'] = '未中奖';
                $item['image'] = '';
            }
            //判断是否已经领取
            if ($item['is_get'] == 1) $item['is_get'] = '未领取';
            else $item['is_get'] = '已领取';
            //删除多余的信息内容
            unset($item['id'],$item['draw_goods_id'],$item['avatar'],$item['image']);
        }

        //标题列表数组
        $title = [
            'draw_title'    => '活动名称' ,
            'nickname'    => '用户昵称' ,
            //'avatar'      => '用户头像' ,
            'create_time' => '抽奖时间' ,
            'title'       => '奖品标题' ,
            //'image'       => '奖品图片' ,
            'is_get'      => '是否领取' ,
        ];
        //导出信息
        util_csv::export_csv_2($list, $title, '抽奖记录信息.csv');
        exit();
    }
    /**
     * Comment: 导出参与抽奖用户信息
     * Author: zzw
     * Date: 2020/10/19 16:36
     * @param $sql
     */
    public static function exportUserList($sql){
        //信息列表获取
        $list = pdo_fetchall($sql." LIMIT 20000");
        //循环处理内容 并且删除多余的内容
        foreach($list as &$value){
            ksort($value);
            //获取中奖次数
            $value['total_prize'] = Draw::selectCount(['mid'=>$value['mid'],'draw_goods_id >' => 0]);
            //获取用户积分、余额
            $credit = pdo_get('mc_members' , ['uid' => $value['uid']] , ['credit1' , 'credit2']);
            $value['credit1'] = $credit['credit1'];
            $value['credit2'] = $credit['credit2'];
            //删除多余信息
            unset($value['avatar']);
            unset($value['mid']);
            unset($value['uid']);
        }
        //标题列表数组
        $title = [
            'mobile'      => '手机号' ,
            'nickname'    => '用户昵称' ,
            'total_draw'  => '抽奖次数' ,
            'total_prize' => '中奖次数' ,
            'credit1'     => '积分' ,
            'credit2'     => '余额' ,
        ];
        //导出信息
        util_csv::export_csv_2($list, $title, '抽奖记录信息.csv');
        exit();
    }

}
