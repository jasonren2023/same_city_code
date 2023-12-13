<?php
/**
 * Created by PhpStorm.
 * User: wlf
 * Date: 2022/7/29
 * Time: 17:08
 */
class CallModuleUniapp extends Uniapp {

    /**
     * Comment: 获取活动页面信息
     * Author: wlf
     * Date:  2022/8/10 18:34
     */
    public function callInfo(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $recordid = $_GPC['recordid'];
        if(empty($recordid)){
            $recordid = pdo_getcolumn(PDO_NAME.'call_list',array('call_id'=>$id,'mid'=>$_W['mid']),'id');
        }
        if(empty($id) || !is_numeric($id)){
            $this->renderError('参数错误，请返回重试');
        }
        $data = [];
        $info = pdo_get(PDO_NAME."call",array('id'=>$id));
        unset($info['share_desc']);
        unset($info['share_image']);
        unset($info['share_title']);
        unset($info['share_wxapp_image']);
        if(!is_numeric($recordid)){
        	$recordid = explode(',',$recordid);
        	$recordid = $recordid[0];
        }
        if($recordid > 0){
            $record = pdo_get(PDO_NAME."call_list",array('id'=>$recordid));
            $member = pdo_get(PDO_NAME."member",array('id'=>$record['mid']),['nickname','encodename','avatar']);
            $data['nickname'] = !empty($member['encodename']) ? base64_decode($member['encodename']) : $member['nickname'];
            $data['avatar'] = tomedia($member['avatar']);
        }else{
            $recordid = 0;
            $record = [];
            $data['nickname'] = $_W['wlmember']['nickname'];
            $data['avatar'] = tomedia($_W['wlmember']['avatar']) ;
        }

        //分享阶段奖品
        $info['sharegifts'] = unserialize($info['sharegifts']);
        foreach ($info['sharegifts'] as &$share){
            $giftinfo = pdo_get('wlmerchant_call_goods',array('id' => $share['prizeids']),array('title','image'));
            $share['title'] = $giftinfo['title'];
            $share['image'] = tomedia($giftinfo['image']);
            //判断是否领取
            if($recordid > 0){
                $share['getflag'] = pdo_getcolumn(PDO_NAME.'call_receive',array('call_id'=>$id,'mid'=>$_W['mid'],'list_id' => $recordid,'type' =>2,'number' => $share['usernum']),'id');
                if($share['getflag'] > 0){
                    $share['getflag'] = 1;
                }else{
                    $share['getflag'] = 0;
                }
            }else{
                $share['getflag'] = 0;
            }

        }
        //助力奖励
        $helpgift = pdo_get('wlmerchant_call_goods',array('id' => $info['prize_id']),array('title','image'));
        $info['helptitle'] = $helpgift['title'];
        $info['helpimage'] = tomedia($helpgift['image']);

        //顶部广告图幻灯片
        $info['advs'] = unserialize($info['advs']);
        if(!empty($info['advs'] )){
            foreach ($info['advs']  as &$add){
                $add['thumb'] = tomedia($add['thumb']);
            }

        }else{
            $info['advs'] = [];
        }
        //活动规则
        $info['explain'] = htmlspecialchars_decode(base64_decode($info['explain']));

        $data['callinfo'] = $info;
        //已助力人数
        $data['hirnum'] = pdo_fetchcolumn('SELECT COUNT(id) FROM '.tablename(PDO_NAME.'call_hit')." WHERE list_id = {$recordid}");
        //判断是否是本人活动
        $data['ownflag'] = $record['mid'] == $_W['mid'] ? 1 : 0;
        //是否已助力
        $data['helpflag'] = pdo_getcolumn(PDO_NAME.'call_hit',array('call_id'=>$id,'mid'=>$_W['mid'],'list_id'=>$recordid),'id');
        if($data['helpflag'] > 0){
            $data['helpflag'] = 1;
        }else{
            $data['helpflag'] = 0;
        }
        //是否已领取助力奖励
        $data['getflag'] = pdo_getcolumn(PDO_NAME.'call_receive',array('call_id'=>$id,'mid'=>$_W['mid'],'list_id' => $recordid,'type' =>1),'id');;
        if($data['getflag'] > 0){
            $data['getflag'] = 1;
        }else{
            $data['getflag'] = 0;
        }
        //好友助力榜
        $rank = pdo_fetchall("SELECT mid,hit_time FROM ".tablename('wlmerchant_call_hit')." WHERE call_id = {$id} AND list_id = {$recordid}  ORDER BY hit_time DESC LIMIT 5");
        if(!empty($rank)){
            foreach ($rank as &$rn){
                $member = pdo_get('wlmerchant_member',array('id' => $rn['mid']),array('nickname','encodename','avatar'));
                $rn['nickname'] = empty($member['encodename']) ? $member['nickname'] : base64_decode($member['encodename']);
                $rn['avatar'] = tomedia($member['avatar']);
                $rn['hit_time'] = date('Y-m-d H:i:s',$rn['hit_time']);
            }
        }
        $data['helprank'] = $rank;
        $data['recordid'] = $recordid;

        $this->renderSuccess('活动信息',$data);
    }

    /**
     * Comment: 发起分享活动
     * Author: wlf
     * Date:  2022/8/11 10:11
     */

    public function launchCall(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        if(empty($id) || !is_numeric($id)){
            $this->renderError('参数错误，请返回重试');
        }
        //检查锁
        if(lockTool($_W['mid'],'call')){
            $this->renderError('处理中，请稍后');
        }
        //判断是否已经发起
        $recordid = pdo_getcolumn(PDO_NAME.'call_list',array('call_id'=>$id,'mid'=>$_W['mid']),'id');
        if($recordid > 0){
            $this->renderError('您已发起过此活动',['recordid' => $recordid]);
        }
        $info = [
            'uniacid' => $_W['uniacid'],
            'aid'     => $_W['aid'],
            'mid'     => $_W['mid'],
            'call_id' => $id,
            'start_time' => time()
        ];

        $res = pdo_insert(PDO_NAME . 'call_list', $info);
        $recordid = pdo_insertid();
        if($res){
            $this->renderSuccess('发起成功',['recordid' => $recordid]);
        }else{
            $this->renderError('发起活动失败，请刷新重试');
        }


    }

    /**
     * Comment: 助力活动
     * Author: wlf
     * Date:  2022/8/11 10:26
     */
    public function helpCall(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $recordid = $_GPC['recordid'];
        if(empty($id) || empty($recordid)){
            $this->renderError('参数错误，请刷新重试');
        }
        //检查锁
        if(lockTool($_W['mid'],'call')){
            $this->renderError('处理中，请稍后');
        }
        //判断是否已经助力
        $hitid = pdo_getcolumn(PDO_NAME.'call_hit',array('list_id'=>$recordid,'mid'=>$_W['mid']),'id');
        if($hitid > 0){
            $this->renderError('您已助力过此活动');
        }
        //判断是否有资格助力
        $callinfo = pdo_get(PDO_NAME."call",array('id'=>$id),['qualifications','new_time','sharegifts','helptimes','daytimes']);
        if($callinfo['qualifications'] > 0){
            $membertime =  pdo_getcolumn(PDO_NAME.'member',array('id'=>$_W['mid']),'createtime');
            if($membertime < $callinfo['new_time']){
                $timetext = date('Y/m/d H:i',$callinfo['new_time']);
                $this->renderError('此活动只能'.$timetext.'后注册的用户可以助力');
            }
        }
        //判断用户助力次数
        if($callinfo['helptimes'] > 0){
        	$helptimes = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_call_hit')." WHERE mid = {$_W['mid']} AND call_id = {$id} ");
        	if($helptimes > $callinfo['helptimes'] || $helptimes == $callinfo['helptimes'] ){
        		$this->renderError('此活动您只能助力'.$callinfo['helptimes'].'次，您已全部助力完毕');
        	}
        }
        //判断每日助力次数
        if($callinfo['daytimes'] > 0){
        	$begintime = strtotime(date("Y-m-d"),time());
        	$daytimes = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_call_hit')." WHERE list_id = {$recordid} AND call_id = {$id} AND hit_time > {$begintime} ");
        	if($daytimes > $callinfo['daytimes'] || $daytimes == $callinfo['daytimes'] ){
        		$this->renderError('此活动今天已获取全部助力，请您明天再为他助力');
        	}
        }
        //判断是否已经助力完成
        $sharegifts = unserialize($callinfo['sharegifts']);
        $usernum = array_column($sharegifts,'usernum'); 
		$maxnum = max($usernum);
		$helpnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_call_hit')." WHERE call_id = {$id} AND list_id = {$recordid}");
		if($helpnum >= $maxnum){
			$this->renderError('此活动已助力完成');
		}
	
        $info = [
            'uniacid' => $_W['uniacid'],
            'aid'     => $_W['aid'],
            'mid'     => $_W['mid'],
            'call_id' => $id,
            'list_id' => $recordid,
            'hit_time' => time()
        ];

        $res = pdo_insert(PDO_NAME . 'call_hit', $info);
        if($res){
            $this->renderSuccess('助力成功');
        }else{
            $this->renderError('助力失败，请刷新重试');
        }

    }

    /**
     * Comment: 领取奖品
     * Author: wlf
     * Date:  2022/8/11 14:58
     */
    public function receivePrize(){
        global $_W,$_GPC;
        $id = $_GPC['id'];  //活动id
        $recordid = $_GPC['recordid']; //记录id
        $type = $_GPC['type']; //领奖类型 1助力 2分享
        $num = $_GPC['num'] ? : 0; //人数id
        $hitid = 0;
        $code = 0;
        if(empty($id) || empty($recordid) || empty($type)){
            $this->renderError('参数错误，请刷新重试');
        }
        //检查锁
        if(lockTool($_W['mid'],'call')){
            $this->renderError('处理中，请稍后');
        }
        //判断能否领取 是否领取
        $callinfo = pdo_get(PDO_NAME."call",array('id'=>$id),['prize_id','sharegifts','title']);
        if($type == 1){
            $hitid = pdo_getcolumn(PDO_NAME.'call_hit',array('call_id'=>$id,'list_id'=>$recordid,'mid' => $_W['mid']),'id');
            if(empty($hitid)){
                $this->renderError('您还未助力，无法领取');
            }
            $getflag = pdo_getcolumn(PDO_NAME.'call_receive',array('call_id'=>$id,'list_id'=>$recordid,'mid' => $_W['mid'],'type' => 1),'id');
            if($getflag){
                $this->renderError('您已经领取奖励，请在领奖记录查看');
            }
            $prizeid = $callinfo['prize_id'];
        }else if($type == 2){
            //查询激励人数
            $helpnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_call_hit')." WHERE call_id = {$id} AND list_id = {$recordid}");
            if($helpnum < $num){
                $this->renderError('您还收到足够的好友助力，无法领取');
            }
            $getflag = pdo_getcolumn(PDO_NAME.'call_receive',array('call_id'=>$id,'list_id'=>$recordid,'mid' => $_W['mid'],'type' => 2,'num' => $num),'id');
            if($getflag){
                $this->renderError('您已经领取奖励，请在领奖记录查看');
            }
            $sharegifts = unserialize($callinfo['sharegifts']);
            foreach ($sharegifts as $shares){
                if($num == $shares['usernum']){
                    $prizeid = $shares['prizeids'];
                }
            }
            if(empty($prizeid)){
                $this->renderError('助力人数错误，请刷新重试');
            }
        }
        //获取奖品信息
        $info = pdo_get('wlmerchant_call_goods',array('id' => $prizeid));
        if(empty($info)){
            $this->renderError('奖品信息错误，无法领取');
        }
        $getinfo = [
            'draw_title' => $callinfo['title'],
            'title' => $info['title'],
            'source' => $_W['source'] ? : 1,
            'prize_number' => $info['prize_number']
        ];
        $callres = 0;
        try {
            switch (intval($info['type'])) {
                case 1:
                    //判断领取方式   领取方式：1=发送现金红包，2=增加到余额
                    if ($info['get_type'] == 1) {
                        $getinfo['order_no'] = 'ca'.$id.'r'.$recordid.'m'.$_W['mid'].'t'.$type.'n'.$num;
                        $getinfo['actype'] = 1;
                        //发送现金红包
                        Payment::cashRedPack($getinfo);
                        $callres = 1;
                    }else {
                        //增加到余额
                        $remark = "分享有礼活动【{$getinfo['draw_title']}】奖品【{$getinfo['title']}】";
                        if (Member::credit_update_credit2($_W['mid'] , $getinfo['prize_number'] , $remark)) {
                            $callres = 1;
                        }
                    }
                    break;//现金红包
                case 2:
                    //线上红包领取
                    $res = Redpack::pack_send($_W['mid'] , $info['goods_id'] , 'call' , $getinfo['source']);
                    if ($res['errno'] == 1) {
                        throw new Exception($res['message']);
                    }else {
                        $callres = 1;
                    }
                    break;//线上红包
                case 3:
                    $remark = "分享有礼活动【{$getinfo['draw_title']}】奖品【{$getinfo['title']}】";
                    if (Member::credit_update_credit1($_W['mid'] , $getinfo['prize_number'] , $remark)) {
                        $callres = 1;
                    }
                    break;//积分
                case 4:
                    //获取一个激活码
                    $codeInfo = Halfcard::getActivationCode($info['code_keyword']);
                    if (is_error($codeInfo)) {
                        throw new Exception($codeInfo['message']);
                    }else {
                        $callres = 1;
                        $code = $codeInfo['number'];
                    }
                    break;//激活码
                case 5:
                    $callres = 1;
                    break;//商品
            }
        }catch (Exception $e){
            $this->renderError($e->getMessage());
        }
        if($callres > 0){
            $receive = [
                'uniacid' => $_W['uniacid'],
                'aid'     => $_W['aid'],
                'mid'     => $_W['mid'],
                'type'    => $type,
                'number'  => $num,
                'prizeid' => $prizeid,
                'call_id' => $id,
                'list_id' => $recordid,
                'hit_id'  => $hitid,
                'createtime'  => time(),
                'code'    => $code
            ];
            pdo_insert(PDO_NAME . 'call_receive', $receive);
            $receiveid = pdo_insertid();
            if($info['type'] == 5){
                $this->renderSuccess("实物商品，请下单",['goodsid' => $info['goods_id'],'plugin' => $info['goods_plugin'],'callid' => $receiveid]);
            }else{
                $this->renderSuccess("领取成功");
            }
        }else{
            $this->renderError('领取失败，请刷新重试');
        }
    }

    /**
     * Comment: 完整助力记录
     * Author: wlf
     * Date:  2022/8/12 09:24
     */
    public function helpList(){
        global $_W,$_GPC;
        $page = $_GPC['page'];
        $id = $_GPC['id'];
        $recordid = $_GPC['recordid'];

        $pindex = max(1, intval($page));
        $psize = 10;

        $where = " WHERE call_id = {$id} AND list_id = {$recordid} ";

        $sql = "SELECT mid,hit_time FROM ".tablename(PDO_NAME."call_hit") ." {$where} ORDER BY hit_time DESC ";
        $sql .= " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list  = pdo_fetchall($sql);
        foreach ($list as &$rn){
            $member = pdo_get('wlmerchant_member',array('id' => $rn['mid']),array('nickname','encodename','avatar'));
            $rn['nickname'] = empty($member['encodename']) ? $member['nickname'] : base64_decode($member['encodename']);
            $rn['avatar'] = tomedia($member['avatar']);
            $rn['hit_time'] = date('Y-m-d H:i:s',$rn['hit_time']);
        }
        $data['list'] = $list;
        if($pindex == 1){
            $totalnum = pdo_fetchcolumn('SELECT COUNT(id) FROM '.tablename(PDO_NAME.'call_hit').$where);
            $total = ceil($totalnum/$psize);
            $data['total'] = $total;
        }

        $this->renderSuccess("助力记录",$data);
    }

    /**
     * Comment: 领奖记录
     * Author: wlf
     * Date:  2022/8/12 09:44
     */
    public function prizeList(){
        global $_W,$_GPC;
        $page = $_GPC['page'];
        $pindex = max(1, intval($page));
        $psize = 10;

        $where = " WHERE  uniacid = {$_W['uniacid']} AND mid = {$_W['mid']} ";

        $sql = "SELECT id,prizeid,type,call_id,createtime,orderid,code FROM ".tablename(PDO_NAME."call_receive") ." {$where} ORDER BY createtime DESC ";
        $sql .= " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list  = pdo_fetchall($sql);

        foreach ($list as &$li){
            $call = pdo_get('wlmerchant_call',array('id' => $li['call_id']),array('title'));
            $li['calltitle'] = $call['title'];
            $prize = pdo_get('wlmerchant_call_goods',array('id' => $li['prizeid']),array('title','image','type','goods_plugin','goods_id'));
            $li['prizetitle'] = $prize['title'];
            $li['prizeimage'] = tomedia($prize['image']) ;
            $li['createtime'] = date('Y-m-d H:i:s',$li['createtime']);
            $li['goodstype'] = $prize['type'];
            if($li['goodstype'] == 5){
                $li['goods_plugin'] = $prize['goods_plugin'];
                $li['goods_id'] = $prize['goods_id'];
            }
            unset($li['prizeid']);
            unset($li['call_id']);
        }

        $data['list'] = $list;
        if($pindex == 1){
            $totalnum = pdo_fetchcolumn('SELECT COUNT(id) FROM '.tablename(PDO_NAME.'call_receive').$where);
            $total = ceil($totalnum/$psize);
            $data['total'] = $total;
        }

        $this->renderSuccess("领奖记录",$data);

    }


}