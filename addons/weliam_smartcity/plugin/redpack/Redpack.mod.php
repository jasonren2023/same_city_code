<?php
defined('IN_IA') or exit('Access Denied');

class Redpack {
    /**
     * Comment: 获取某个红包的信息
     */
    static function pack_get($id) {
        global $_W;
        return pdo_get('wlmerchant_redpacks' , ['uniacid' => $_W['uniacid'] , 'id' => $id]);
    }
    /**
     * Comment: 红包记录
     * @param int $mid          用户ID
     * @param int $id           红包ID
     * @param string $type      get用户自行领取，send后台发送
     * @param int $source       红包领取渠道（1=普通红包领取；2=新人红包领取；3=节日红包领取）
     * @param int $festivalId  节日红包id
     * @return array
     */
    static function pack_send($mid, $id, $type = 'get',$source = 1,$festivalId = 0) {
        global $_W;
        #1、信息判断
        $member = Member::wl_member_get($mid, ['id']);
        if (empty($member)) return error(1, '用户不存在，请检查后重试');
        $pack = self::pack_get($id);
        if (empty($pack)) return error(1, '红包不存在，请检查后重试');
        if (empty($pack['status'])) return error(1, '您来晚了，红包已下架');
        if (empty($pack['usetime_type']) && $pack['use_end_time'] < time()){
            return error(1, '红包已过期，无法领取');
        }
        #2、判断领取限制
        if ($type == 'get') {
            //剩余数量判断
            if (!empty($pack['all_count'])) {
                $counts = Redpack:: getReceiveTotal($id);
                if ($counts >= $pack['all_count']) return error(1, '您来晚了，没有更多红包了');
            }
            //领取数量限制
            if (!empty($pack['limit_count'])) {
                $mycounts = Redpack:: getReceiveTotal($id,$mid,$source,$festivalId);
                if ($mycounts >= $pack['limit_count']) return error(1, '小淘气别贪心哦，你已经领完了');
            }
        }
        #3、信息添加
        $timetoday = strtotime(date("Y-m-d", time()));
        $usetimes = [
            ['start_time' => $pack['use_start_time'], 'end_time' => $pack['use_end_time']],
            ['start_time' => time(), 'end_time' => $timetoday + 86400 * $pack['usetime_day1']],
            ['start_time' => $timetoday + 86400, 'end_time' => $timetoday + 86400 * ($pack['usetime_day2'] + 1)],
        ];
        //获取type信息
        $typeVal = 1;//系统发放
        if ($type == 'get') $typeVal = 0;//自助领取
        if ($type == 'draw') $typeVal = 2;//抽奖获取
        if ($type == 'call') $typeVal = 3;//分享有礼获取
        $data = [
            'uniacid'     => $_W['uniacid'] ,
            'aid'         => $_W['aid'],
            'mid'         => $mid ,
            'status'      => 0 ,
            'type'        => intval($typeVal),
            'packid'      => $id ,
            'start_time'  => $usetimes[$pack['usetime_type']]['start_time'] ,
            'end_time'    => $usetimes[$pack['usetime_type']]['end_time'] ,
            'createtime'  => time() ,
            'festival_id' => $festivalId ,//节日红包id
            'source'      => $source,//红包领取渠道（1=普通红包领取(默认)；2=新人红包领取；3=节日红包领取）
        ];
        #4、红包领取 如果为新人红包 则循环进行领取
        if($source == 2){
            $newList = Setting::agentsetting_read("red_pack_new")['list'];
            $idArr = array_column($newList,'id');
            $newInfo = array_combine($idArr,$newList)[$id];
            $limit = $newInfo['limit'];
            //判断数量
            $mycounts = Redpack:: getReceiveTotal($id,$mid,$source,$festivalId);
            $sup = $limit - $mycounts;
            if($sup > 0){
                for($i=1;$i<=$sup;$i++){
                    $res = pdo_insert(PDO_NAME.'redpack_records', $data);
                }
            }
        }else{
            $res = pdo_insert(PDO_NAME.'redpack_records', $data);
            $data['id'] = pdo_insertid();
        }
        //发放模板消息
        if($res){
            $first = '一个在线红包已经到账';
            $type = '在线红包到账通知';
            $content = '红包名:['.$pack['title'].']';
            $newStatus = '已到账';
            $remark = '点击查看红包';
            $url = h5_url('pages/subPages/redpacket/myredpacket');
            News::jobNotice($mid,$first,$type,$content,$newStatus,$remark,time(),$url);
        }
        return $data;
    }
    /**
     * Comment: 获取某个节日红包下面所有的有效（存在并且已上架）红包信息
     * Author: zzw
     * Date: 2020/2/19 9:24
     * @param int      $id      节日红包id
     * @param string $field     获取的字段信息，默认全部
     * @return array|bool|mixed
     */
    public static function getRedPackFestivalJoin($id,$field = '*'){
        return pdo_fetchall("SELECT {$field} FROM ".tablename(PDO_NAME."redpack_festival_join")
                     ." as a RIGHT JOIN ".tablename(PDO_NAME."redpacks")
                     ." as b ON a.pack_id = b.id WHERE a.festival_id = {$id} AND status = 1");
    }
    /**
     * Comment: 获取红包已领取的总数
     * Author: zzw
     * Date: 2020/2/24 9:21
     * @param int $id       红包id
     * @param int $mid      用户id
     * @param int $source   领取方式 （1=普通红包领取；2=新人红包领取；3=节日红包领取）
     * @param int $festivalId   节日红包id
     * @return int
     */
    public static function getReceiveTotal($id,$mid = 0,$source = 1,$festivalId = 0){
        #1、条件生成  不存在mid则为获取红包总共已领取的数量   存在mid则为获取用户在该领取方式中已领取的总数量
        $where['packid'] = $id;
        if($mid > 0){
            $where['source'] = $source;
            $where['mid'] = $mid;
        }
        //存在$festivalId  则为获取某个节日礼包中某个礼包的已领取数量
        if($festivalId > 0){
            $where['festival_id'] = $festivalId;
        }
        $getTotal = pdo_getcolumn(PDO_NAME.'redpack_records' , $where , 'COUNT(id)');

        return $getTotal ? : 0;
    }
    /**
     * Comment: 获取节日礼包 - 当前id和下一条信息的id
     * Author: zzw
     * Date: 2020/3/10 13:41
     * @param int $id
     * @return bool
     */
    public static function getDayFestivalRedPack($id = 0){
        global $_W;
        #1、条件生成  只获取进行中的节日礼包信息
        $time = time();
        $where = " WHERE start_time <= {$time} AND end_time > {$time} AND status = 1 AND uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        #2、节日信息列表获取
        $field = "id";
        $list = pdo_fetchall("SELECT {$field} FROM ".tablename(PDO_NAME."redpack_festival") .$where." ORDER BY start_time ASC ");
        #2、判断是否存在内容
        if(is_array($list) && $list) {
            $ids    = array_column($list , 'id');//正常数组  键=>值
            $newIds = array_flip($ids);//新数组 键值对换 值=>键
            #2、操作
            $data['current'] = $id ? $id : $ids[0];
            $key = $newIds[$id] + 1;
            $data['next'] = $ids[$key] ? intval($ids[$key]) : '';
            return $data;
        }
        return false;
    }
    /**
     * Comment: 根据条件获取用户已领取红包的指定信息
     * Author: zzw
     * Date: 2020/3/11 9:30
     * @param string      $field        要获取的字段
     * @param string      $where        条件
     * @param string $limit             条数限制
     * @return array|bool
     */
    public static function getUserRedPackInfo($field,$where,$limit = ''){
        $list = pdo_fetchall("SELECT {$field} FROM ".tablename(PDO_NAME."redpack_records")
                             ." as a RIGHT JOIN ".tablename(PDO_NAME."redpacks")
                             ." as b ON a.packid = b.id {$where} ORDER BY a.id DESC {$limit} ");
        return $list;
    }
    /**
     * Comment: 获取用户在当前订单中可使用的红包信息列表
     * Date: 2020/4/28 16:28
     * @param $money
     * @param $sid
     * @param $aid
     * @param $gid
     * @return mixed
     */
    public function getNotUseList($money,$sid,$aid,$gid,$type){
        global $_W,$_GPC;
        #1、参数获取
        $time = time();
        #2、条件生成
        $useWhere = " WHERE a.uniacid = {$_W['uniacid']} AND b.full_money <= {$money} AND a.mid = {$_W['mid']} AND a.status = 0 AND a.start_time < {$time} AND a.end_time > {$time}";
        if($type == 'payonline'){
            $useWhere .= " AND b.redpack_type IN (0,2) ";
        }else{
            $useWhere .= " AND b.redpack_type IN (0,1) ";
        }
        #3、可使用列表获取  循环判断当前红包是否符合使用条件
        $useField = " a.id,b.usegoods_type,b.use_aids,b.use_sids,b.rush_ids,b.group_ids,b.fight_ids,b.bargain_ids";
        $useList = Redpack::getUserRedPackInfo($useField,$useWhere);
        foreach($useList as $key => &$val){
            //使用条件处理  0全平台1指定代理2指定商家3指定商品可用
            if ($val['usegoods_type'] == 1) {
                //仅限指定地区可用
                $useAid = unserialize($val['use_aids']);
                if(!in_array($aid,$useAid)){
                    unset($useList[$key]);
                    continue;
                }
            }else if ($val['usegoods_type'] == 2) {
                //仅限指定商家可用
                $useSid = unserialize($val['use_sids']);
                if(!in_array($sid,$useSid)){
                    unset($useList[$key]);
                    continue;
                }
            }else  if ($val['usegoods_type'] == 3){
                //仅限指定商品可用
                $useGid = [];
                //获取对应类型的商品id
                switch ($type){
                    case 'rush': $useGid = unserialize($val['rush_ids']);break;//抢购
                    case 'groupon': $useGid = unserialize($val['group_ids']);break;//团购
                    case 'wlfightgroup': $useGid = unserialize($val['fight_ids']);break;//拼团
                    case 'bargain': $useGid = unserialize($val['bargain_ids']);break;//砍价
                }
                //判断是否可用
                if(!in_array($gid,$useGid)){
                    unset($useList[$key]);
                    continue;
                }
            }
        }
        $useIds = implode(array_column($useList,'id'),',');
        #4、判断是否存在符合使用条件的红包
        if($useIds){
            $field = " a.id,a.status,a.start_time,a.end_time,a.source,a.festival_id,b.full_money,b.cut_money,b.title,b.usegoods_type";
            //  $limit = " limit {$pageStart},{$pageIndex} ";
            $where = " WHERE a.id IN ({$useIds}) ";
            $list = Redpack::getUserRedPackInfo($field,$where);
            //  $total = Redpack::getUserRedPackInfo("count(*) as total",$where);
            $total = count($list);
            #4、信息处理
            foreach($list as $key => &$val){
                //金额处理
                $val['full_money'] = sprintf("%0.2f",$val['full_money']);
                $val['cut_money'] = sprintf("%0.2f",$val['cut_money']);
                //判断是否已过期
                if($val['end_time'] <= $time) $val['status'] = 2;
                //时间处理
                $val['time'] = date("Y-m-d",$val['start_time'])."至".date("Y-m-d",$val['end_time']);
                //使用条件处理  0全平台1指定代理2指定商家3指定商品
                if ($val['usegoods_type'] == 1) $val['use_where'] = '仅限指定地区可用';
                else if ($val['usegoods_type'] == 2) $val['use_where'] = '仅限指定商家可用';
                else if ($val['usegoods_type'] == 3) $val['use_where'] = '仅限指定商品可用';
                else $val['use_where'] = '全平台可用';
                //判断是否为节日红包  是则获取红包标签
                $val['label'] = '';
                if($val['source'] == 2) $val['label'] = '新人';
                if($val['source'] == 3) $val['label'] = pdo_getcolumn(PDO_NAME."redpack_festival",['id'=>$val['festival_id']],'label');
                //删除多余的信息
                unset($val['start_time']);
                unset($val['end_time']);
                unset($val['source']);
                unset($val['start_time']);
                unset($val['usegoods_type']);
                unset($val['festival_id']);
            }
        }
        //排序
        $sortArr = array_column($list, 'cut_money');
        array_multisort($sortArr, SORT_DESC, $list);
        #5、信息拼装
        $data['list'] = $list ? : [];
        $data['total'] = $total ? : 0;
        return $data;
    }


}