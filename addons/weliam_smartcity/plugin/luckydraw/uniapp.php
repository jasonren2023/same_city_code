<?php
defined('IN_IA') or exit('Access Denied');

class LuckydrawModuleUniapp extends Uniapp {

    /**
     * Comment: 抽奖页面信息
     * Author: wlf
     * Date: 2021/12/07 11:39
     */
    public function drawPageInfo(){
        global $_GPC, $_W;
        $set =  Setting::agentsetting_read('luckydraw');
        if(empty($set['status'])){
            $this->renderError("活动已关闭,无法访问",['url'=>h5_url('pages/mainPages/index/index')]);
        }
        $id = $_GPC['id'];
        $recordid = $_GPC['recordid'];
        $lat = $_GPC['lat'];
        $lng = $_GPC['lng'];
        if(empty($recordid) && empty($id)){
            $this->renderError("参数错误,无法访问",['url'=>h5_url('pages/mainPages/index/index')]);
        }
        if($recordid > 0){
            $id =  pdo_getcolumn(PDO_NAME.'luckydraw_record',array('id'=>$recordid),'drawid');
        }
        $drawinfo = pdo_get('wlmerchant_luckydraw',array('id' => $id));
        if(empty($recordid) && $drawinfo['status'] != 1){
            //查询最后一次记录
            $recordid = pdo_getcolumn(PDO_NAME.'luckydraw_record',array('uniacid'=>$_W['uniacid'],'drawid'=>$id),'id');
            if(empty($recordid)){
                $this->renderError("活动未开始",['url'=>h5_url('pages/mainPages/index/index')]);
            }
        }
        if(empty($recordid)){
            $recordid = pdo_getcolumn(PDO_NAME.'luckydraw_record',array('uniacid'=>$_W['uniacid'],'drawid'=>$id,'status' => 0),'id');
            if(empty($recordid)){
                //创建活动
                if($drawinfo['drawstatus'] > 0 ){
                    $countall = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_luckydraw_record')." WHERE drawid = {$id}");
                    if($countall > $drawinfo['drawsucnum']  || $countall == $drawinfo['drawsucnum']){
                        $this->renderError("活动已结束",['url'=>h5_url('pages/mainPages/index/index')]);
                    }
                }else{
                    if($drawinfo['endtime'] < time()){
                        $this->renderError("活动已结束",['url'=>h5_url('pages/mainPages/index/index')]);
                    }
                }
                $recordid = Luckydraw::createNewRecodrd($drawinfo);
            }
        }
        $recordinfo = pdo_get('wlmerchant_luckydraw_record',array('id' => $recordid));
        //数据组装
        $data = [];
        //活动状态
        $data['status'] = $recordinfo['status'];
        $data['id'] = $id;
        $data['recordid'] = $recordid;
        //幻灯片
        $advs = unserialize($drawinfo['advarray']);
        foreach ($advs as &$ad){
            $ad['thumb'] = tomedia($ad['thumb']);
        }
        $data['adv'] = $advs;
        //中奖记录
        $luckyList = pdo_fetchall("SELECT draw_goods_id,mid FROM ".tablename('wlmerchant_luckydraw_drawcode')."WHERE uniacid = {$_W['uniacid']} AND draw_goods_id > 0 ORDER BY drawtime DESC LIMIT 10");
        if(!empty($luckyList)){
            foreach ($luckyList as &$luckyli){
                $member = pdo_get('wlmerchant_member',array('id' => $luckyli['mid']),array('nickname','avatar'));
                $luckyli['nickname'] = $member['nickname'];
                $luckyli['avatar'] = tomedia($member['avatar']);
                $goodsname = pdo_getcolumn(PDO_NAME.'luckydraw_goods',array('id'=>$luckyli['draw_goods_id']),'title');
                $luckyli['goodsname'] = $goodsname;
            }
        }else{
            $luckyList = [];
        }
        $data['luckylist'] = $luckyList;
        //剩余时间或人数
        $data['supstatus'] = $recordinfo['drawstatus'];
        if(empty($recordinfo['status'])){
            if($data['supstatus'] > 0){
//                $allcodenum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_luckydraw_drawcode')." WHERE draw_reid = {$recordid}");
//                $recordinfo['lacknum'] = $recordinfo['drawcodenum'] - $allcodenum;
//                if($recordinfo['lacknum'] < 0){
//                    $recordinfo['lacknum'] = 0;
//                }
                $data['supnum'] = $recordinfo['lacknum'];
                //pdo_update('wlmerchant_luckydraw_record',array('lacknum' => $recordinfo['lacknum']),array('id' => $recordid));
            }else{
                $data['supnum'] = $recordinfo['endtime'] - time();
            }
            if($data['supnum'] < 0){
                $data['supnum'] = 0;
            }
        }else{
            $data['supnum'] = 0;
        }
        //奖品展示
        $one = pdo_get('wlmerchant_luckydraw_goods',array('id' => $drawinfo['oneprizeid']),array('title','image'));
        $one['image'] = tomedia($one['image']);
        $one['number'] = $drawinfo['oneprizenum'];
        $prize[] = $one;
        if($drawinfo['twoprizeid'] > 0 && $drawinfo['twoprizenum'] > 0){
            $two = pdo_get('wlmerchant_luckydraw_goods',array('id' => $drawinfo['twoprizeid']),array('title','image'));
            $two['image'] = tomedia($two['image']);
            $two['number'] = $drawinfo['twoprizenum'];
            $prize[] = $two;
        }
        if($drawinfo['threeprizeid'] > 0 && $drawinfo['threeprizenum'] > 0){
            $thr = pdo_get('wlmerchant_luckydraw_goods',array('id' => $drawinfo['threeprizeid']),array('title','image'));
            $thr['image'] = tomedia($thr['image']);
            $thr['number'] = $drawinfo['threeprizenum'];
            $prize[] = $thr;
        }
        $data['prize'] = $prize;
        //提示文本
        $data['tips'] = $set['statement'] ?  :  "100%开奖·绝对公平公正·快来加入吧";
        //参与人头像和人数
        $headlist =  pdo_fetchall('select distinct mid from ' . tablename('wlmerchant_luckydraw_drawcode')."WHERE  uniacid = {$_W['uniacid']} AND draw_reid = {$recordid} ORDER BY createtime DESC LIMIT 5");
        if(!empty($headlist)){
            foreach ($headlist as &$head){
                $member = pdo_get('wlmerchant_member',array('id' => $head['mid']),array('avatar'));
                $head['avatar'] = tomedia($member['avatar']);
            }
        }
        $joinnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_luckydraw_drawcode')." WHERE draw_reid = {$recordid}");
        $data['headlist'] = $headlist;
        $data['joinnum'] = $joinnum;
        //我的抽奖码或中奖信息
        if($data['status'] == 2){
            //一等奖名单
            $onedrawlist = pdo_getall('wlmerchant_luckydraw_drawcode',array('status' => 2,'draw_reid' => $recordid),array('codenum','mid'));
            $onedrawlist = Luckydraw::beautifulList($onedrawlist,$_W['mid']);
            $data['onecodes'] = $onedrawlist;
            $data['onecodenum'] = count($onedrawlist);
            //二等奖名单
            $twodrawlist = pdo_getall('wlmerchant_luckydraw_drawcode',array('status' => 3,'draw_reid' => $recordid),array('codenum','mid'));
            if(!empty($twodrawlist)){
                $twodrawlist = Luckydraw::beautifulList($twodrawlist,$_W['mid']);
            }
            $data['twocodes'] = $twodrawlist;
            $data['twocodenum'] = count($twodrawlist);
            //三等奖名单
            $thrdrawlist = pdo_getall('wlmerchant_luckydraw_drawcode',array('status' => 4,'draw_reid' => $recordid),array('codenum','mid'));
            if(!empty($thrdrawlist)){
                $thrdrawlist = Luckydraw::beautifulList($thrdrawlist,$_W['mid']);
            }
            $data['thrcodes'] = $thrdrawlist;
            $data['thrcodenum'] = count($thrdrawlist);
            //查询自己中奖记录
            $mydrawlist = pdo_fetchall("SELECT id,draw_goods_id,status FROM ".tablename('wlmerchant_luckydraw_drawcode')."WHERE uniacid = {$_W['uniacid']} AND draw_reid = {$recordid} AND mid = {$_W['mid']} AND status IN (2,3,4) AND is_get = 0 ORDER BY status ASC");
            if(!empty($mydrawlist)){
                $mydraw = $mydrawlist[0];
                $drawgoods = pdo_get('wlmerchant_luckydraw_goods',array('id' => $mydraw['draw_goods_id']),array('title','image'));
                $data['mycodeid'] = $mydraw['id'];
                $data['mycodestatus'] = $mydraw['status'];
                $data['mygoodstitle'] = $drawgoods['title'];
                $data['mygoodslogo'] =  tomedia($drawgoods['image']);
            }else{
                $data['mycodeid'] = 0;
            }
        }else{
            $mycode = pdo_getall('wlmerchant_luckydraw_drawcode',array('mid' => $_W['mid'],'draw_reid' => $recordid),array('codenum'));
            $data['mycode'] = $mycode;
            $data['mycodenum'] = count($mycode);
        }
        //说明
        $data['detail'] = htmlspecialchars_decode(base64_decode($drawinfo['detail']));
        //购买的商品列表
        $data['buygoodslist'] = Luckydraw::getBuyGoodsList($id,$lat,$lng,1);
        //额外获取抽奖码方式
        $data['drawvideostatus'] = $drawinfo['drawvideostatus'];
        $data['videoid'] = trim($drawinfo['videoid']);
        $data['drawsharestatus'] = $drawinfo['drawsharestatus'];
        //手机号
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array('luckydraw',$mastmobile)){
            $data['nomobile'] = 1;
        }else{
            $data['nomobile'] = 0;
        }

        $this->renderSuccess('活动页面初始化',$data);
    }

    /**
     * Comment: 看视频获取抽奖码
     * Author: wlf
     * Date: 2021/12/07 22:20
     */
    public function getVideoCode(){
        global $_GPC, $_W;
        $id = $_GPC['id'];
        $recordid = $_GPC['recordid'];
        if(empty($id) || empty($recordid)){
            $this->renderError("参数错误，请刷新重试");
        }
        $drawinfo = pdo_get('wlmerchant_luckydraw',array('id' => $id),['drawvideostatus','videonum','dayvideonum']);
        if($drawinfo['drawvideostatus'] > 0){
            //判断是否绑定手机号
            $mobile = pdo_getcolumn(PDO_NAME.'member',array('id'=>$_W['mid']),'mobile');
            if(empty($mobile)){
                $this->renderError("未绑定手机号，请绑定手机号再试");
            }
            if($drawinfo['videonum'] > 0){
                //查询已获取的抽奖码
                $allnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_luckydraw_drawcode')." WHERE mid = {$_W['mid']} AND plugin = 'drawvideo' AND draw_reid = {$recordid}");
                if($allnum > $drawinfo['videonum'] || $allnum == $drawinfo['videonum']){
                    $this->renderError("您已经获取所有激励抽奖码");
                }
            }

            if($drawinfo['dayvideonum'] > 0){
                //查询已获取的抽奖码
                $daytime = strtotime(date('Y-m-d'));
                $dayallnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_luckydraw_drawcode')." WHERE mid = {$_W['mid']} AND plugin = 'drawvideo' AND draw_reid = {$recordid} AND createtime > {$daytime} ");
                if($dayallnum > $drawinfo['dayvideonum'] || $dayallnum == $drawinfo['dayvideonum']){
                    $this->renderError("您已获取所有今天的激励抽奖码，请明天再来");
                }
            }


            //获取抽奖码
            $res = Luckydraw::getDrawCode($id,$_W['mid'],0,'drawvideo',0,1,$recordid);
            if($res){
                $this->renderSuccess('获取抽奖码成功');
            }else{
                $this->renderError("获取抽奖码失败，请刷新重试");
            }

        }else{
            $this->renderError("激励渠道已关闭");
        }

    }

    /**
     * Comment: 分享活动获取抽奖码
     * Author: wlf
     * Date: 2021/12/07 22:41
     */
    public function getShareCode(){
        global $_GPC, $_W;
        $recordid = $_GPC['recordid'];  //抽奖记录id
        $sharemid = $_GPC['sharemid'];  //分享人mid
        if(empty($sharemid) || empty($recordid)){
            $this->renderError("参数错误，请刷新重试");
        }
        $id = pdo_getcolumn(PDO_NAME.'luckydraw_record',array('id'=>$recordid),'drawid');
        $drawinfo = pdo_get('wlmerchant_luckydraw',array('id' => $id),['drawsharestatus','sharenum','daysharenum']);
        if($drawinfo['drawsharestatus'] > 0) {
            //判断分享者
            if(strstr($sharemid,',')){
                $nono = 1;
            }
            //判断是否绑定手机号
            $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
            if (in_array('luckydraw',$mastmobile)){
                $mobile = pdo_getcolumn(PDO_NAME.'member',array('id'=>$sharemid),'mobile');
                if(empty($mobile)){
                    $nono = 1;
                }
            }
            //判断已获取数量
            if($drawinfo['sharenum'] > 0 && empty($nono)){
                $allnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_luckydraw_drawcode')." WHERE mid = {$sharemid} AND plugin = 'drawshare' AND draw_reid = {$recordid}");
                if($allnum > $drawinfo['sharenum'] || $allnum == $drawinfo['sharenum']) {
                    $nono = 1;
                }
            }
            if($drawinfo['daysharenum'] > 0 && empty($nono)){
                //查询已获取的抽奖码
                $daytime = strtotime(date('Y-m-d'));
                $dayallnum = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_luckydraw_drawcode')." WHERE mid = {$sharemid} AND plugin = 'drawshare' AND draw_reid = {$recordid} AND createtime > {$daytime} ");
                if($dayallnum > $drawinfo['daysharenum'] || $dayallnum == $drawinfo['daysharenum'] || empty($daytime) ){
                    $nono = 1;
                }
            }
            //判断用户与分享人是否添加过
            if(empty($nono)){
                $flag = pdo_getcolumn(PDO_NAME . 'luckydraw_share_record', array('sharemid' => $sharemid, 'mid' => $_W['mid'], 'recordid' => $recordid), 'id');
                if (empty($flag)) {
                    pdo_insert(PDO_NAME . 'luckydraw_share_record', array('sharemid' => $sharemid, 'mid' => $_W['mid'], 'recordid' => $recordid));
                    Luckydraw::getDrawCode($id, $sharemid, 0, 'drawshare', 0, 1, $recordid);
                }
            }
        }
        $this->renderSuccess($nono);
    }

    /**
     * Comment: 活动商品列表页面
     * Author: wlf
     * Date: 2021/12/07 23:14
     */
    public function goodsListInfo(){
        global $_GPC, $_W;
        $id = $_GPC['id'];  //抽奖活动id
        if(empty($id)){
            $this->renderError("参数错误，请返回重试");
        }
        $lat = $_GPC['lat'];
        $lng = $_GPC['lng'];
        $data = [];
        $drawinfo = pdo_get('wlmerchant_luckydraw',array('id' => $id),['advarray']);
        //幻灯片
        $advs = unserialize($drawinfo['advarray']);
        foreach ($advs as &$ad){
            $ad['thumb'] = tomedia($ad['thumb']);
        }
        $data['adv'] = $advs;
        $data['buygoodslist'] = Luckydraw::getBuyGoodsList($id,$lat,$lng);


        $this->renderSuccess("商品列表页",$data);
    }

    /**
     * Comment: 我的抽奖码列表
     * Author: wlf
     * Date: 2021/12/07 23:32
     */
    public function getCodeList(){
        global $_GPC, $_W;
        $page       = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_start = $page * 10 - 10;
        $status = $_GPC['status'];  //抽奖码状态  1 = 未开奖 2 = 已中奖 3 = 未中奖
        $whewe = " WHERE uniacid = {$_W['uniacid']} AND mid = {$_W['mid']}";
        if($status == 1){
            $whewe .= " AND status = 0";
        }else if($status == 2){
            $whewe .= " AND status > 1";
        }else if($status == 3){
            $whewe .= " AND status = 1";
        }

        $records = pdo_fetchall("SELECT * FROM " . tablename("wlmerchant_luckydraw_drawcode") . $whewe ."  ORDER BY createtime DESC LIMIT {$page_start},10");
        $allnum = pdo_fetchcolumn('SELECT count(id) FROM ' . tablename('wlmerchant_luckydraw_drawcode') .$whewe);
        $newrecords = [];

        if (!empty($records)) {
            foreach ($records as $red){
                $new = [];
                //构建数组
                $new['id'] = $red['id'];
                $new['drawid'] = $red['draw_acid'];
                $new['recordid'] = $red['draw_reid'];
                $new['codenum'] = $red['codenum'];
                $new['status'] = $red['status'];
                $new['is_get'] = $red['is_get'];
                $new['plugin'] = $red['plugin'];
                $new['drawname'] = pdo_getcolumn(PDO_NAME.'luckydraw',array('id'=>$red['draw_acid']),'title');
                if($red['draw_goods_id'] > 0){
                    $goods = pdo_get(PDO_NAME.'luckydraw_goods',array('id' => $red['draw_goods_id']),array('title','image'));
                    $new['goodname'] = $goods['title'];
                    $new['goodlogo'] = tomedia($goods['image']);
                }
                if($red['token_id'] > 0){
                    $code = pdo_getcolumn(PDO_NAME."token",['id'=>$red['token_id']],'number');
                    $new['code'] = $code['number'];
                }

                $newrecords[] = $new;
            }
        }
        $data['list']      = $newrecords;
        $data['pagetotal'] = ceil($allnum / 10);

        $this->renderSuccess('抽奖码列表' , $data);

    }

    /**
     * Comment: 领取奖品
     * Author: wlf
     * Date: 2021/12/07 23:51
     */

    public function getDrawGoods(){
        global $_GPC, $_W;
        $id = $_GPC['id']; //抽奖码id
        if(empty($id)){
            $this->renderError("参数错误，请返回重试");
        }
        $record = pdo_get('wlmerchant_luckydraw_drawcode',array('id' => $id),array('draw_goods_id','is_get','draw_acid','order_no'));
        if($record['is_get'] > 0){
            $this->renderError("已领取奖品，请勿重复领取!");
        }
        $drawgoods = pdo_get('wlmerchant_luckydraw_goods',array('id' => $record['draw_goods_id']));
        //其他信息获取
        $info = array_merge($record,$drawgoods);
        $info['draw_title']     = pdo_getcolumn(PDO_NAME."luckydraw",['id'=>$record['draw_acid']],'title');//活动标题
        $info['source']         = $_W['source'] ? : 1;//渠道信息：1=公众号（默认）；2=h5；3=小程序

        //生成奖品领取信息
        try {
            switch (intval($info['type'])) {
                case 1:
                    //判断领取方式   领取方式：1=发送现金红包，2=增加到余额
                    if ($info['get_type'] == 1) {
                        //订单号生成
                        if (!$info['order_no']) {
                            $info['order_no'] = 'LD'.date("YmdHis").random(4,true);//订单号生成
                            pdo_update(PDO_NAME . "luckydraw_drawcode" , ['order_no' => $info['order_no']] , ['id' => $id]);
                        }
                        //发送现金红包
                        Payment::cashRedPack($info);
                        Luckydraw::changeIsGetStatus($id);
                        $this->renderSuccess("领取成功");
                    }else {
                        //增加到余额
                        $remark = "活动【{$info['draw_title']}】奖品【{$info['title']}】";
                        if (Member::credit_update_credit2($_W['mid'] , $info['prize_number'] , $remark)) {
                            Luckydraw::changeIsGetStatus($id);
                            $this->renderSuccess("领取成功");
                        }
                    }
                    break;//现金红包
                case 2:
                    //线上红包领取
                    $res = Redpack::pack_send($_W['mid'] , $info['goods_id'] , 'draw' , $info['source']);
                    if ($res['errno'] == 1) {
                        throw new Exception($res['message']);
                    }else {
                        Luckydraw::changeIsGetStatus($id);
                        $this->renderSuccess("领取成功");
                    }
                    break;//线上红包
                case 3:
                    $remark = "活动【{$info['draw_title']}】奖品【{$info['title']}】";
                    if (Member::credit_update_credit1($_W['mid'] , $info['prize_number'] , $remark)) {
                        Luckydraw::changeIsGetStatus($id);
                        $this->renderSuccess("领取成功");
                    }
                    break;//积分
                case 4:
                    //判断是否已经领取过激活码
                    if ($info['token_id']) {
                        $code = pdo_getcolumn(PDO_NAME."token",['id'=>$info['token_id']],'number');

                        $this->renderSuccess("领取成功",['code'=>$code]);
                    }else {
                        //获取一个激活码
                        $codeInfo = Halfcard::getActivationCode($info['code_keyword']);
                        if (!is_error($codeInfo)) {
                            //记录激活码信息
                            $params = [
                                'token_id' => $codeInfo['id'] ,
                                'is_get'   => 1 ,//修改状态为已领取 但是激活码未使用
                            ];
                            pdo_update(PDO_NAME . "luckydraw_drawcode" , $params , ['id' => $id]);

                            $this->renderSuccess("领取成功",['code'=>$codeInfo['number']]);
                        }else {
                            throw new Exception($codeInfo['message']);
                        }
                    }
                    break;//激活码
                case 5:
                    $this->renderSuccess("实物商品，请下单",['goodsid' => $info['goods_id'],'plugin' => $info['goods_plugin']]);
                    break;//商品
            }
        }catch (Exception $e){
            $this->renderError($e->getMessage());
        }
    }

    /**
     * Comment: 往期列表
     * Author: wlf
     * Date: 2021/12/24 15:22
     */
    public function getRecordList(){
        global $_GPC, $_W;
        $id = $_GPC['id']; //抽奖活动id
        if(empty($id)){
            $this->renderError("参数错误，请返回重试");
        }
        //参数获取
        $page = max(1, intval($_GPC['page']));
        $pageIndex = 10;
        //条件筛选
        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $where['aid'] = $_W['aid'];
        $drawid = $id;
        $where['drawid'] = $drawid;
        $draw = pdo_get('wlmerchant_luckydraw',array('id' => $drawid),array('title','oneprizeid'));
        if(empty($draw)){
            $this->renderError("活动信息错误，请返回重试");
        }
        $oneprice = pdo_get('wlmerchant_luckydraw_goods',array('id' => $draw['oneprizeid']),array('image'));
        $logo = tomedia($oneprice['image']);

        $recordlist = Util::getNumData('id,status,drawtime,issueno','wlmerchant_luckydraw_record',$where,'createtime DESC',$page,$pageIndex,1);
        $list = $recordlist[0];
        foreach ($list as &$li){
            $li['drawid'] = $id;
            $li['title'] = $draw['title'];
            $li['logo'] = $logo;
            if($li['drawtime'] > 0 ){
                $li['drawtime'] = date('Y-m-d H:i',$li['drawtime']);
            }
        }
        $this->renderSuccess('往期列表' , $list);
    }




















}