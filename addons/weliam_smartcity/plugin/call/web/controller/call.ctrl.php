<?php
/**
 * Comment: ...
 * Author: ZZW
 * Date: 2018/9/12
 * Time: 18:02
 */
defined('IN_IA') or exit('Access Denied');

class Call_WeliamController {
    /**
     * Comment: 集call活动列表
     * Author: zzw
     */
    public function callList(){
        global $_W,$_GPC;
        $name = $_GPC['name'];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = " uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if($name){
            $where .= " AND title LIKE '%{$name}%' ";
        }
        $sql = "SELECT id,title,state,prize_id,sharegifts,start_time,end_time,qualifications,new_time FROM ".tablename(PDO_NAME."call") ." WHERE {$where} ORDER BY id DESC ";
        $sql .= " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list  = pdo_fetchall($sql);
        foreach ($list as &$li){
            $li['start_time'] = date('Y/m/d',$li['start_time']);
            $li['end_time'] = date('Y/m/d',$li['end_time']);
            $li['new_time'] = date('Y/m/d',$li['new_time']);
            $li['price'] = pdo_get('wlmerchant_call_goods',array('id' => $li['prize_id']),array('title','image'));

            $li['sharegifts'] = unserialize($li['sharegifts']);
            foreach($li['sharegifts'] as &$gift){
                $gift['title'] = pdo_getcolumn(PDO_NAME.'call_goods',array('id'=>$gift['prizeids']),'title');
            }
        }

        $total = pdo_fetchcolumn('SELECT COUNT(id) FROM '.tablename(PDO_NAME.'call').$where);
        $pager = wl_pagination($total, $pindex, $psize);

        include wl_template("call/callList");
    }
    /**
     * Comment: 进入编辑集call活动信息页面
     * Author: zzw
     */
    public function getEditCall(){
        global $_W,$_GPC;
        $prizeList = pdo_getall('wlmerchant_call_goods',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));
        $id = $_GPC['id'];

        if($id > 0){
            //修改信息
            $info = pdo_get(PDO_NAME."call",array('id'=>$id));
            $sharegifts = unserialize($info['sharegifts']);
            $advs = unserialize($info['advs']);
            $giftnum = count($sharegifts);

            $info['explain'] = htmlspecialchars_decode(base64_decode($info['explain']));
        }else{
            $giftnum = 1;
            $info['start_time'] = time();
            $info['end_time'] = time()+ 86400*30;
            $info['new_time'] = time()- 86400*30;
        }

        if($_W['ispost']) {
            $data = $_GPC['data'];
            $data['uniacid']      = $_W['uniacid'];
            $data['aid']          = $_W['aid'];
            $data['new_time']     = strtotime($data['new_time']);
            $data['explain'] = base64_encode(htmlspecialchars_decode($data['explain']));
            $data['helptimes'] = intval($data['helptimes']);
			$data['daytimes'] = intval($data['daytimes']);
			
            //活动时间
            $actime = $_GPC['time'];
            $data['start_time'] = strtotime($actime['start']);
            $data['end_time'] = strtotime($actime['end']);
            //阶段奖励
            $usernum = $_GPC['usernum'];
            $prizeids = $_GPC['prizeids'];
            $sharegifts = [];
            if(empty($prizeids)){
                wl_message('未设置分享人阶段奖励',referer(),'error');
            }else{
                foreach($prizeids as $nk => $pe){
                    $gift = [];
                    if($usernum[$nk] == 0 || $usernum[$nk] < 0 ){
                        wl_message('分享阶段人数必须大于0',referer(),'error');
                    }
                    if($prizeids[$nk] == 0 || $prizeids[$nk] < 0 ){
                        wl_message('未设置分享人阶段奖励奖品',referer(),'error');
                    }
                    $gift['usernum'] = $usernum[$nk];
                    $gift['prizeids'] = $prizeids[$nk];
                    $sharegifts[] = $gift;
                }
            }
            $data['sharegifts'] = serialize($sharegifts);
            //幻灯片
            $advlogo = $_GPC['advlogo'];
            $advlink = $_GPC['advlink'];
            $advs = [];
            if(!empty($advlogo)){
                foreach($advlogo as $ak => $logo){
                    $adv = [];
                    $adv['link'] = $advlink[$ak];
                    $adv['thumb'] = $advlogo[$ak];
                    $advs[] = $adv;
                }
            }
            $data['advs'] = serialize($advs);


            if($_GPC['id']){
                //修改活动信息
                $result = pdo_update(PDO_NAME."call",$data,array('id'=>$_GPC['id']));
            }else{
                $data['createtime'] = time();


                $result = pdo_insert(PDO_NAME."call",$data);
            }
            if($result){
                wl_message('操作成功！',web_url('call/call/callList'),'success');
            }else {
                wl_message('操作失败,请重试',referer(),'error');
            }

        }

        include wl_template("call/editCall");
    }

    /**
     * Comment: 删除一条集call活动信息
     * Author: zzw
     */
    public function delCall(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $result = pdo_delete(PDO_NAME."call",array('id'=>$id));
        if($result){
            show_json(1);
        }else{
            show_json(0,'删除失败');
        }
    }

    /**
     * Comment: 已发起集call活动的信息列表
     * Author: zzw
     */
    public function callLaunchList(){
        global $_W,$_GPC;
        $name = $_GPC['name'];
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = " a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        if($name){
            $where .= " AND (c.title LIKE '%{$name}%' || b.nickname LIKE '%{$name}%')";
        }
        $sql = "SELECT 
            b.nickname,
            c.title,
            e.title as prize_name,
            a.start_time, 
            c.number,
            count(d.list_id) as collect_number,
            (number - count(d.list_id)) as surplus_number,
            c.receive_time FROM ".tablename(PDO_NAME."call_list")
            ." a LEFT JOIN "
            .tablename(PDO_NAME."member")
            ." b ON a.mid = b.id LEFT JOIN "
            .tablename(PDO_NAME."call")
            ." `c` ON a.call_id = c.id LEFT JOIN "
            .tablename(PDO_NAME."call_hit")
            ." d ON a.id = d.list_id LEFT JOIN "
            .tablename(PDO_NAME."couponlist")
            ." e ON c.prize_id = e.id "
            ." WHERE {$where} GROUP BY d.list_id  ORDER BY a.start_time DESC ";
        $sql .= " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list  = pdo_fetchall($sql);
        $total = pdo_fetchcolumn('SELECT COUNT(1) FROM '.tablename(PDO_NAME.'call_list'));
        $pager = wl_pagination($total, $pindex, $psize);

        include wl_template("call/launchList");
    }



    /**
     * Comment: 奖品列表
     * Author: wlf
     */
    public function prizeList(){
        global $_W,$_GPC;
        //参数获取
        $page = max(1, intval($_GPC['page']));
        $pageIndex = 10;
        $title = $_GPC['title'] ? : '';//奖品名称
        $type = intval($_GPC['type']) ? : 0;//奖品类型:1=现金红包,2=线上红包,3=积分,4=激活码,5=商品
        $status = intval($_GPC['status']) ? : 0;//状态:1=开启，2=关闭
        //条件生成
        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $where['aid'] = $_W['aid'];
        if($title) $where['title@'] = "%{$title}%";
        if($type > 0) $where['type'] = $type;
        if($status > 0) $where['status'] = $status;
        //查询
        $goodslist = Util::getNumData('*','wlmerchant_call_goods',$where,'id DESC',$page,$pageIndex,1);
        $pager = $goodslist[1];
        $list = $goodslist[0];
        //信息处理


        include wl_template("call/prizeList");
    }

    /**
     * Comment: 编辑奖品
     * Author: wlf
     */
    public function prizeEdit(){
        global $_W,$_GPC;

        //参数信息获取
        $id = intval($_GPC['id']);
        if($_W['ispost']){
            //处理修改后的信息
            $data = $_GPC['data'];
            //判断是否存在对应类型的必须条件
            if ($data['type'] == 1 && ($data['prize_number'] <= 0 || !$data['prize_number'])) wl_message('请填写红包金额！' , referer() , 'error');
            if ($data['type'] == 2 && !$data['red_pack_id']) wl_message('请选择红包！' , referer() , 'error');
            if ($data['type'] == 3 && ($data['prize_number'] <= 0 || !$data['prize_number'])) wl_message('请填写积分数量！' , referer() , 'error');
            if ($data['type'] == 4 && !$data['code_keyword']) wl_message('请选择一个激活码序列！' , referer() , 'error');
            if ($data['type'] == 5 && !$data['goods_id']) wl_message('请选择一个商品！' , referer() , 'error');
            if (!$data['image']) wl_message('请上传奖品logo！' , referer() , 'error');
            //判断是否已经存在同名称的奖品
            $isHave = pdo_get(PDO_NAME . "call_goods" , ['title' => $data['title'],'id <>'=>$id,'uniacid' => $_W['uniacid']]);
            if ($isHave) wl_message('已存在同名称奖品！' , referer() , 'error');
            //奖品类型为线上红包时  修改字段内容信息
            if ($data['type'] == 2) {
                $data['goods_id']     = $data['red_pack_id'];
                $data['goods_plugin'] = 'red_pack';
            }
            //删除多余信息字段
            unset($data['red_pack_name']);
            unset($data['red_pack_id']);
            unset($data['goods_name']);
            //添加内容
            if($id > 0){
                $res = pdo_update(PDO_NAME . "call_goods" , $data,['id'=>$id]);
            }else{
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                $data['create_time'] = time();
                $res = pdo_insert(PDO_NAME . 'call_goods', $data);
            }
            if ($res) wl_message('操作成功' , web_url('call/call/prizeList') , 'success');
            else wl_message('操作失败，请刷新重试！' , referer() , 'error');
        }
        //进入修改页面  获取当前奖品的基本信息
        if($id > 0 ){
            $info = Call::prizeInfo($id);
        }else{
            $info = [
                'type' => 1
            ];
        }
        //获取激活码分组信息
        $codeList = Halfcard::getGroupList();



        include wl_template("call/prizeEdit");
    }

    /**
     * Comment: 删除奖品
     * Author: wlf
     */
    public function prizedelete(){
        global $_W,$_GPC;
        //参数信息获取
        $ids = $_GPC['ids'] OR show_json(0, '参数错误，请刷新重试!');
        //删除内容
        $res = pdo_delete(PDO_NAME."call_goods",['id IN'=>$ids]);
        if($res) show_json(1, '删除成功');
        else show_json(0, '删除失败，请刷新重试');
    }

    /**
     * Comment: 阶段商品div
     * Author: wlf
     */
    public function sharegift(){
        global $_W,$_GPC;
        $prizeList = pdo_getall('wlmerchant_call_goods',array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'status' => 1),array('id','title'));

        include wl_template("call/sharegift");
    }

    /**
     * Comment: 幻灯片div
     * Author: wlf
     */
    public function advinfo(){
        global $_W,$_GPC;
        $kw = $_GPC['kw'];

        include wl_template("call/advinfo");
    }


    /**
     * Comment: 参与活动记录
     * Author: wlf
     */
    public function joinList(){
        global $_W,$_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = " a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        //条件筛选
        if($_GPC['call_id'] > 0){
            $where .= " AND a.call_id = {$_GPC['call_id']}";
        }
        $keyword = trim($_GPC['keyword']);
        if(!empty($keyword)){
            if($_GPC['keywordtype'] == 1){
                $where .= " AND c.nickname LIKE '%{$keyword}%'";
            }else if($_GPC['keywordtype'] == 2){
                $where .= " AND c.mobile LIKE '%{$keyword}%'";
            }else if($_GPC['keywordtype'] == 3){
                $where .= " AND c.id = {$keyword}";
            }else if($_GPC['keywordtype'] == 4){
                $where .= " AND b.title LIKE '%{$keyword}%'";
            }else if($_GPC['keywordtype'] == 5){
                $where .= " AND b.id = {$keyword}";
            }else if($_GPC['keywordtype'] == 6){
                $where .= " AND a.id = {$keyword}";
            }
        }

        if($_GPC['time_limit']  && $_GPC['timetype'] > 0){
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($time_limit['start']);
            $endtime = strtotime($time_limit['end']);
            $where .= " AND a.start_time > {$time_limit} AND a.start_time < {$endtime}";
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $sql = "SELECT a.id,a.mid,a.call_id,a.start_time,b.title,c.nickname,c.encodename,c.avatar,c.mobile FROM ".tablename(PDO_NAME."call_list")
        ." as a LEFT JOIN".tablename(PDO_NAME."call")." as b ON a.call_id = b.id"
        ." LEFT JOIN".tablename(PDO_NAME."member")." as c ON a.mid = c.id";
        $sql .= " WHERE {$where} ORDER BY a.start_time DESC";
        $limit = " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list  = pdo_fetchall($sql.$limit);
        foreach ($list as &$li){
            $li['start_time'] = date('Y-m-d H:i:s',$li['start_time']);
            $li['nickname'] = empty($li['encodename']) ? $li['nickname'] : base64_decode($li['encodename']);
            $li['helpnum'] = pdo_fetchcolumn('SELECT COUNT(id) FROM '.tablename(PDO_NAME.'call_hit')." WHERE list_id = {$li['id']}");
        }

        $total = count(pdo_fetchall($sql));
        $pager = wl_pagination($total, $pindex, $psize);

        include wl_template("call/joinList");
    }


    /**
     * Comment: 助力记录
     * Author: wlf
     */
    public function helpList(){
        global $_W,$_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = " a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        //条件筛选
        $keyword = trim($_GPC['keyword']);
        if(!empty($keyword)){
            if($_GPC['keywordtype'] == 1){
                $where .= " AND c.nickname LIKE '%{$keyword}%'";
            }else if($_GPC['keywordtype'] == 2){
                $where .= " AND c.mobile LIKE '%{$keyword}%'";
            }else if($_GPC['keywordtype'] == 3){
                $where .= " AND a.mid = {$keyword}";
            }else if($_GPC['keywordtype'] == 4){
                $where .= " AND b.title LIKE '%{$keyword}%'";
            }else if($_GPC['keywordtype'] == 5){
                $where .= " AND a.call_id = {$keyword}";
            }else if($_GPC['keywordtype'] == 6){
                $where .= " AND a.list_id = {$keyword}";
            }
        }

        if($_GPC['time_limit']  && $_GPC['timetype'] > 0){
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($time_limit['start']);
            $endtime = strtotime($time_limit['end']);
            $where .= " AND a.hit_time > {$time_limit} AND a.hit_time < {$endtime}";
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }


        $sql = "SELECT a.id,a.mid,a.list_id,a.call_id,a.hit_time,b.title,c.nickname,c.encodename,c.avatar,c.mobile FROM ".tablename(PDO_NAME."call_hit")
            ." as a LEFT JOIN".tablename(PDO_NAME."call")." as b ON a.call_id = b.id"
            ." LEFT JOIN".tablename(PDO_NAME."member")." as c ON a.mid = c.id";
        $sql .= " WHERE {$where} ORDER BY a.hit_time DESC";
        $limit = " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list  = pdo_fetchall($sql.$limit);

        foreach ($list as &$li){
            $li['hit_time'] = date('Y-m-d H:i:s',$li['hit_time']);
            $li['nickname'] = empty($li['encodename']) ? $li['nickname'] : base64_decode($li['encodename']);
            //查询发起人信息
            $launchmid =  pdo_getcolumn(PDO_NAME.'call_list',array('id'=>$li['list_id']),'mid');
            $launch = pdo_get('wlmerchant_member',array('id' => $launchmid),array('mobile','nickname','avatar','encodename'));
            $li['launchmid'] = $launchmid;
            $li['launchname'] = empty($launch['encodename']) ? $launch['nickname'] : base64_decode($launch['encodename']);
            $li['launchavatar'] = $launch['avatar'];
            $li['launchmobile'] = $launch['mobile'];
        }
        $total = count(pdo_fetchall($sql));
        $pager = wl_pagination($total, $pindex, $psize);

        include wl_template("call/helpList");
    }

    /**
     * Comment: 领奖记录
     * Author: wlf
     */
    public function getList(){
        global $_W,$_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = " a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        //条件筛选
        $keyword = trim($_GPC['keyword']);
        if(!empty($keyword)){
            if($_GPC['keywordtype'] == 1){
                $where .= " AND c.nickname LIKE '%{$keyword}%'";
            }else if($_GPC['keywordtype'] == 2){
                $where .= " AND c.mobile LIKE '%{$keyword}%'";
            }else if($_GPC['keywordtype'] == 3){
                $where .= " AND a.mid = {$keyword}";
            }else if($_GPC['keywordtype'] == 4){
                $where .= " AND b.title LIKE '%{$keyword}%'";
            }else if($_GPC['keywordtype'] == 5){
                $where .= " AND a.call_id = {$keyword}";
            }else if($_GPC['keywordtype'] == 6){
                $where .= " AND a.list_id = {$keyword}";
            }else if($_GPC['keywordtype'] == 7){
                $where .= " AND a.prizeid = {$keyword}";
            }
        }

        if($_GPC['time_limit']  && $_GPC['timetype'] > 0){
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($time_limit['start']);
            $endtime = strtotime($time_limit['end']);
            $where .= " AND a.createtime > {$time_limit} AND a.createtime < {$endtime}";
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $sql = "SELECT a.id,a.mid,a.call_id,a.list_id,a.createtime,a.prizeid,a.type,a.number,b.title,a.code,c.nickname,c.encodename,c.avatar,c.mobile FROM ".tablename(PDO_NAME."call_receive")
            ." as a LEFT JOIN".tablename(PDO_NAME."call")." as b ON a.call_id = b.id"
            ." LEFT JOIN".tablename(PDO_NAME."member")." as c ON a.mid = c.id";
        $sql .= " WHERE {$where} ORDER BY a.createtime DESC";
        $limit = " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list  = pdo_fetchall($sql.$limit);


        foreach ($list as &$li){
            $li['createtime'] = date('Y-m-d H:i:s',$li['createtime']);
            $li['nickname'] = empty($li['encodename']) ? $li['nickname'] : base64_decode($li['encodename']);
            //查询奖品信息
            $goods = pdo_get(PDO_NAME.'call_goods',array('id' => $li['prizeid']),array('title','image','type'));
            $li['goodsname'] = $goods['title'];
            $li['goodsimg'] = $goods['image'];
            $li['goodstype'] = $goods['type'];

        }
        $total = count(pdo_fetchall($sql));
        $pager = wl_pagination($total, $pindex, $psize);


        include wl_template("call/getList");


    }


}








