<?php
defined('IN_IA') or exit('Access Denied');

class DrawModuleUniapp extends Uniapp {

    public function __construct(){
        global $_W,$_GPC;
        //参数信息获取
        $drawId = $_GPC['draw_id'] ? : 0;
        $head_id = $_GPC['head_id'] ? : 0;
        //操作分享信息 记录分享信息
        if ($drawId > 0 && $head_id > 0 && $_W['mid'] > 0 && $_W['mid'] != $head_id) $this->recordHelpInfo($drawId , $head_id);

    }
    /**
     * Comment: 获取抽奖页面基本信息
     * Author: zzw
     * Date: 2020/9/17 17:07
     */
    public function home(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR $this->renderError('不存在的抽奖活动!');
        //增加浏览信息
        pdo_query("update ".tablename(PDO_NAME.'draw')." set pv=pv+1 where id = {$id}");
        //活动相关基本信息获取
        list($info,$prize,$shop) = Draw::getDrawDetailedInfo($id);
        $info['user_integral'] = $_W['wlmember']['credit1'];//用户当前积分
        $info['rule'] = htmlspecialchars_decode($info['rule']);
        $info['introduce'] = htmlspecialchars_decode($info['introduce']);
        $info['wheel_bg'] = unserialize($info['wheel_bg']);
        //获取当前活动抽奖记录信息
        $record = Draw::getDrawRecord($id,$info['fictitious_prize'],$prize);
        //获取好友助力信息  需要用户登录
        $help = [];
        if($_W['mid'] > 0) $help = Draw::getHelpList(1,$id);
        //删除多余的参数信息
        unset($info['id'] , $info['uniacid'] , $info['aid'] , $info['integral_give'] , $info['create_time']  , $info['total_join_times'] , $info['total_draw_times'] , $info['day_join_times'] , $info['day_draw_times'] , $info['fictitious_visit'] , $info['fictitious_prize'], $info['actual_visit'] , $info['actual_prize']);
        foreach($prize as &$del){
            unset($del['id'],$del['type'],$del['sid'],$del['day_number'],$del['total_number'],$del['status']);
        }
        //获取装修菜单配置信息
        if($info['menu_id'] > 0 && p('diypage')) $menu = Diy::getMenu($info['menu_id']);
        else $menu = DiyMenu::defaultBottomMenu();
        //信息拼装  并且输出
        $data = [
            'info'   => $info ,//活动信息
            'prize'  => $prize ,//奖品信息列表
            'shop'   => $shop ,//商户信息列表
            'record' => $record ,//中奖记录信息列表
            'help'   => $help ,//助力信息列表
            'menu'   => $menu ,//底部菜单信息
        ];

        $this->renderSuccess('抽奖页面基本信息',$data);
    }
    /**
     * Comment: 抽奖操作
     * Author: zzw
     * Date: 2020/9/21 15:25
     */
    public function draw(){
        global $_W,$_GPC;
        //获取基本参数信息
        $id = $_GPC['id'] OR $this->renderError("参数错误，不存在的抽奖活动!");
        $usetype = $_GPC['usetype'];
        $code = trim($_GPC['code']);
        //获取活动信息、奖品信息、商户信息
        list($info,$prize,$shop) = Draw::getDrawDetailedInfo($id);
        if($info['surplus_total_parin_times'] < 1 && $info['total_parin_times'] > 0){
            $this->renderError("您在此活动抽奖次数已用完");
        }
        if($info['surplus_day_parin_times'] < 1 && $info['day_parin_times'] > 0){
            $this->renderError("您今天在此活动抽奖次数已用完，请明天再来");
        }
        //状态判断 状态：1=未开启，2=使用中，【3=未开始，4=已过期】
        if($info['status'] == 1) $this->renderError('抽奖已关闭！');
        if($info['status'] == 3) $this->renderError('抽奖暂未开始！');
        if($info['status'] == 4) $this->renderError('活动时间已过！');
        //判断绑定手机号
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array('draw',$mastmobile)){
            $this->renderError('未绑定手机号');
        }
        //判断是否存在免费抽奖次数  不存在则扣除对应的积分
        $isFree = 1;//当前抽奖是否为免费抽奖:1=免费,2=使用积分抽奖 3=抽奖码
        if ($info['surplus_total_join_times'] <= 0 || $info['surplus_day_join_times'] <= 0 ) {
            if($usetype > 0){
                $isFree = 3;
                $codeinfo = pdo_get('wlmerchant_draw_code',array('code' => $code,'drawid' => $id),array('mid','id'));
                if(empty($codeinfo)){
                    $this->renderError("此抽奖码不存在");
                }
                if($codeinfo['mid'] > 0){
                    $this->renderError("此抽奖码已被使用");
                }
            }else{
                $isFree = 2;
                $trade = Setting::wlsetting_read('trade');
                $text  = $trade['credittext'] ? : '积分';
                //判断积分是否充足
                if($_W['wlmember']['credit1'] >= $info['integral_consume']) Member::credit_update_credit1($_W['mid'] , -$info['integral_consume'] , "抽奖【{$info['integral_consume']}】消耗{$text}}");
                else $this->renderError("{$text}不足！");
            }

        };
        //判断是否可以中奖
        if($info['surplus_total_draw_times'] <= 0 && $info['total_draw_times'] > 0) $this->drawError($prize,$id,$isFree,$codeinfo);//总中奖次数是否为0
        if($info['surplus_day_draw_times'] <= 0 && $info['day_draw_times'] > 0) $this->drawError($prize,$id,$isFree,$codeinfo);//当天中奖次数是否为0
        //获取抽奖信息
        $drawRes = Draw::drawOperation($prize,$id);
        try{
            if(!$drawRes) $this->renderError('非法请求，请联系管理员！');
            //其他信息获取
            $drawRes['draw_id']    = $id;//活动id
            $drawRes['draw_title'] = $info['title'];//活动标题
            $drawRes['source']     = $_GPC['source'] ? : 1;//渠道信息：1=公众号（默认）；2=h5；3=小程序
            $drawRes['is_free']    = $isFree;
            //生成奖品领取信息
            $id = Draw::drawResRecord($drawRes);
            $drawRes['draw_record_id'] = $id;
            if($isFree == 3){
                pdo_update('wlmerchant_draw_code',array('mid' => $_W['mid'],'usetime' => time(),'recodeid' =>$id),array('id' => $codeinfo['id']));
            }
            //奖品信息处理
            Draw::prizeProcessing($drawRes);
            //给用户发送中奖模板消息
            if($drawRes['draw_goods_id'] > 0) {
                $modelData = [
                    'first'   => '恭喜您，中奖了!' ,
                    'type'    => '抽奖结果' ,//业务类型
                    'content' => "恭喜您，在抽奖活动【{$drawRes['draw_title']}】中抽中了奖品:{$drawRes['title']}" ,//业务内容
                    'status'  => '已中奖' ,//处理结果
                    'time'    => date("Y-m-d H:i:s" , time()) ,//操作时间
                    'remark'  => ''
                ];
                $link      = h5_url('pages/subPages2/drawGame/myPrize');
                TempModel::sendInit('service' , $_W['mid'] , $modelData , $_W['source'] , $link);
            }
            //给商户发送用户中奖模板消息
            if($drawRes['sid'] > 0){
                $member = $_W['wlmember'];
                $first   = "中奖信息通知";//消息头部
                $type    = "中奖信息";//业务类型
                $content = "用户【{$member['nickname']}】在抽奖活动【{$drawRes['draw_title']}】中抽中了奖品:{$drawRes['title']}";//业务内容
                $status  = "已中奖";//处理结果
                $time    = time();//操作时间
                News::noticeShopAdmin( $drawRes['sid'], $first , $type , $content , $status , '' , $time);
            }
            //返回抽奖结果
            $this->renderSuccess('抽奖结果',['serial_number' => $drawRes['serial_number']]);
        }catch (Exception $e){
            $errorArr = [
                'data'  => $drawRes ,
                'error' => $e->getMessage()
            ];
            Util::wl_log('getDraw', PATH_PLUGIN . "draw/",$errorArr,'抽奖错误信息'); //写入日志记录

            $this->renderError($e->getMessage());
        }
    }
    /**
     * Comment: 获取抽奖时报错信息
     * Describe：当用户当前抽奖必须为谢谢参与时返回信息使用，如果奖品列表中没有谢谢参与则进行错误抛出
     * Author: zzw
     * Date: 2020/9/17 17:49
     * @param $prize
     * @param $id
     * @throws Exception
     */
    protected function drawError($prize,$id,$isFree,$codeinfo = []){
        global $_W;
        //随机获取一个谢谢参与
        $info = Draw::returnNotPrize($prize);
        if($info){
            //记录抽奖信息
            $info['draw_id'] = $id;
            $info['is_free'] = $isFree;
            $id = Draw::drawResRecord($info,2);
            if($isFree == 3){
                pdo_update('wlmerchant_draw_code',array('mid' => $_W['mid'],'usetime' => time(),'recodeid' =>$id),array('id' => $codeinfo['id']));
            }
            $this->renderSuccess('抽奖结果',['serial_number' => $info['serial_number']]);
        }
        //当奖品列表不存在【谢谢参与】奖品时  强制错误抛出
        $this->renderError('非法请求，请联系管理员！');
    }
    /**
     * Comment: 我的奖品列表
     * Author: zzw
     * Date: 2020/9/21 16:03
     */
    public function prizeList(){
        global $_W,$_GPC;
        //参数获取
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} AND a.mid = {$_W['mid']} AND a.draw_goods_id > 0 ";
        $order = " ORDER BY a.create_time DESC";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $field = "a.id,a.create_time,a.is_get,a.token_id,b.title,b.image,b.goods_id,b.type,b.goods_plugin";
        //sql语句生成
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."draw_record")
            ." as a LEFT JOIN ".tablename(PDO_NAME."draw_goods")
            ." as b ON a.draw_goods_id  = b.id ";
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach ($list as $key => &$val) {
            //基本信息处理
            $val['image'] = tomedia($val['image']);
            $val['create_time'] = date("Y-m-d H:i:s", $val['create_time']);
            //状态信息处理    is_get：1=未领取，2=已领取，3=未使用(激活码状态)，4=已使用（激活码状态）
            if ($val['type'] == 4) {
                $token = pdo_get(PDO_NAME . "token" , ['id' => $val['token_id']] , ['status','number']);
                $val['token_code'] = $token['number'];
                if ($token['status'] == 1) $val['is_get'] = 4;
                else if ($token['status'] == 2) $val['is_get'] = 3;
            }
            //获取订单信息
            if($val['type'] == 5 && $val['is_get'] == 2){
                if($val['goods_plugin'] == 'rush'){
                    $val['order_id'] = pdo_getcolumn(PDO_NAME."rush_order",['drawid'=>$val['id']],'id');
                }else{
                    $val['order_id'] = pdo_getcolumn(PDO_NAME."order",['drawid'=>$val['id']],'id');
                }
            }
            //删除多余内容
            unset($val['token_id']);
        }
        //总数信息获取
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        //信息拼装并且输出
        $set = Setting::agentsetting_read('draw_set');
        $data = [
            'color'   => $set['prize_color'] ? : '',
            'top'   => $set['top'] ? tomedia($set['top']) : '',
            'url'   => $set['top_link'],
            'total' => ceil($total / $pageIndex),
            'list'  => $list ? : [],
        ];

        $this->renderSuccess('我的奖品列表',$data);
    }
    /**
     * Comment: 领取奖品
     * Author: zzw
     * Date: 2020/9/21 17:37
     */
    public function getPrize(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR $this->renderError("不存在的中奖信息！");
        //获取基本信息
        $field = "a.is_get,a.draw_id,a.order_no,a.token_id,b.type,b.goods_id,b.goods_plugin,b.sid,b.get_type,b.prize_number,
        b.code_keyword,b.type,b.type,b.type,b.type,b.type,b.type,b.type,b.title";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."draw_record")
            ." AS a RIGHT JOIN ".tablename(PDO_NAME."draw_goods")
            ." AS b ON a.draw_goods_id = b.id WHERE a.id = {$id} AND mid = {$_W['mid']}" ;
        $info = pdo_fetch($sql);
        //判断是否已经领取  是否领取奖品:1=未领取，2=已领取
        if($info['is_get'] == 2) $this->renderError("已领取奖品，请勿重复领取!");
        //根据奖品类型,进行对应的领取操作  奖品类型:1=现金红包,2=线上红包,3=积分,4=激活码,5=商品
        //其他信息获取
        $info['draw_title']     = pdo_getcolumn(PDO_NAME."draw",['id'=>$info['draw_id']],'title');//活动标题
        $info['source']         = $_GPC['source'] ? : 1;//渠道信息：1=公众号（默认）；2=h5；3=小程序
        $info['draw_record_id'] = $id;
        //生成奖品领取信息
        try {
            switch (intval($info['type'])) {
                case 1:
                    //判断领取方式   领取方式：1=发送现金红包，2=增加到余额
                    if ($info['get_type'] == 1) {
                        //订单号生成
                        if (!$info['order_no']) {
                            $info['order_no'] = 'DRAW' . date("YmdHis") . random(10 , true);//订单号生成
                            pdo_update(PDO_NAME . "draw_record" , ['order_no' => $info['order_no']] , ['id' => $id]);
                        }
                        //发送现金红包
                        Payment::cashRedPack($info);
                        Draw::changeIsGetStatus($id);
                        $this->renderSuccess("领取成功");
                    }else {
                        //增加到余额
                        $remark = "活动【{$info['draw_title']}】奖品【{$info['title']}】";
                        if (Member::credit_update_credit2($_W['mid'] , $info['prize_number'] , $remark)) {
                            Draw::changeIsGetStatus($id);
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
                        Draw::changeIsGetStatus($id);
                        $this->renderSuccess("领取成功");
                    }
                    break;//线上红包
                case 3:
                    $remark = "活动【{$info['draw_title']}】奖品【{$info['title']}】";
                    if (Member::credit_update_credit1($_W['mid'] , $info['prize_number'] , $remark)) {
                        Draw::changeIsGetStatus($id);
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
                        if ($codeInfo['errno'] == 1) {
                            //记录激活码信息
                            $params = [
                                'token_id' => $codeInfo['id'] ,
                                'is_get'   => 2 ,//修改状态为已领取 但是激活码未使用
                            ];
                            pdo_update(PDO_NAME . "draw_record" , $params , ['id' => $id]);

                            $this->renderSuccess("领取成功",['code'=>$codeInfo['number']]);
                        }else {
                            throw new Exception($codeInfo['message']);
                        }
                    }
                    break;//激活码
                case 5:
                    //$info['goods_plugin'] 商品类型，英文
                    //$info['goods_id'] 商品id
                    break;//商品
            }
        }catch (Exception $e){
            $this->renderError($e->getMessage());
        }
    }
    /**
     * Comment: 记录好友助力信息 并且获取对应的积分信息
     * Author: zzw
     * Date: 2020/9/24 9:38
     * @param $drawId
     * @param $head_id
     */
    protected function recordHelpInfo($drawId,$head_id){
        global $_W,$_GPC;
        //判断是否已经存在助力信息
        $where = [
            'uniacid'     => $_W['uniacid'] ,
            'aid'         => $_W['aid'] ,
            'type'        => 1 ,
            'activity_id' => $drawId ,
            'mid'         => $head_id ,
            'click_mid'   => $_W['mid'] ,
        ];
        $isHave = pdo_get(PDO_NAME."draw_help",$where);
        if(!$isHave && $_W['mid'] != $head_id){
            //获取活动信息
            $integral = pdo_getcolumn(PDO_NAME."draw",['id'=>$drawId],'integral_give');
            if($integral > 0){
                $res = Member::credit_update_credit1($head_id , $integral , "好友助力获取");
                if(!is_error($res)){
                    $data = array_merge($where,[
                        'create_time' => time(),
                        'integral'    => $integral,
                    ]);
                    pdo_insert(PDO_NAME."draw_help",$data);
                }
            }
        }
    }
    /**
     * Comment: 获取分享页面基本设置信息
     * Author: zzw
     * Date: 2020/9/27 14:56
     */
    public function shareSetInfo(){
        global $_W,$_GPC;
        //设置信息获取
        $set = Setting::agentsetting_read('draw_set');
        //信息获取
        $data = [
            'image' => $set['share_top'] ? tomedia($set['share_top']) : '',
            'color' => $set['bg_color'],
            'url'   => $set['share_top_link'],
        ];

        $this->renderSuccess("分享页面基本设置",$data);
    }

}