<?php
defined('IN_IA') or exit('Access Denied');
/**
 * Comment: 抽奖活动管理
 * Author: zzw
 * Class Draw_WeliamController
 */
class Draw_WeliamController {
    /**
     * Comment: 获取抽奖活动信息列表
     * Author: zzw
     * Date: 2020/9/16 16:27
     */
    public function index(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $title     = $_GPC['title'] ? : '';//活动名称
        $type      = intval($_GPC['type']) ? : 0;//活动类型：1=9宫格，2=16宫格
        $status    = intval($_GPC['status']) ? : 0;//状态：1=未开启，2=使用中
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if($title) $where .= " AND title LIKE '%{$title}%' ";
        if($type > 0) $where .= " AND type = {$type} ";
        if($status > 0) $where .= " AND status = {$status} ";
        //sql语句生成
        $field = "id,title,type,status,create_time,start_time,end_time,usetype";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."draw");
        $order = " ORDER BY create_time DESC,id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            //时间信息处理
            $val['create_time'] = date("Y-m-d H:i:s" , $val['create_time']);
            $val['start_time']  = date("Y-m-d H:i:s" , $val['start_time']);
            $val['end_time']    = date("Y-m-d H:i:s" , $val['end_time']);
            //获取奖品数量
            $val['prize'] = pdo_count(PDO_NAME."draw_join",['draw_id'=>$val['id'],'draw_goods_id >'=>0]);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include  wl_template('draw/index');
    }
    /**
     * Comment: 添加抽奖活动
     * Author: zzw
     * Date: 2020/9/16 16:10
     */
    public function add(){
        global $_W,$_GPC;
        if($_W['ispost']){
            //基本参数信息获取
            $data         = $_GPC['data'];
            $prize        = $_GPC['prize'];
            $activityTime = $_GPC['activity_time'];
            $mysqlFun     = new MysqlFunction();
            //判断轮盘数量是否为偶数
            if($data['type'] == 3){
                if(count($prize) % 2 != 0) wl_message("轮盘数量不为偶数，请设置为偶数！" , referer() , 'error');
                $data['wheel_bg'] = serialize($data['wheel_bg']);
            }
            //判断抽奖概率是否合法
            $totalProbability = sprintf("%.0f",array_sum(array_column($prize,'probability')));
            if($totalProbability != sprintf("%.0f",100)) wl_message("中奖概率错误，概率之和不为100%！当前概率之和：{$totalProbability}%" , referer() , 'error');
            //判断活动是否存在
            $isHave = pdo_get(PDO_NAME."draw",['title'=>$data['title']]);
            if($isHave) wl_message('已存在同名称活动！' , referer() , 'error');
            //判断限制
            if($data['total_join_times'] < $data['day_join_times']) wl_message('免费的总参加次数必须大于等于免费的每天参加次数！' , referer() , 'error');
            if($data['total_draw_times'] < $data['day_draw_times']) wl_message('总中奖次数必须大于等于每天中奖次数！' , referer() , 'error');
            if($data['total_parin_times'] < $data['day_parin_times']) wl_message('总参加次数必须大于等于每天参加次数！' , referer() , 'error');
            //生成活动信息
            $mysqlFun->startTrans();
            $data['uniacid']     = $_W['uniacid'];
            $data['aid']         = $_W['aid'];
            $data['create_time'] = time();
            $data['start_time']  = strtotime($activityTime['start']);
            $data['end_time']    = strtotime($activityTime['end']);
            $data['share_image'] = serialize($data['share_image']);
            $res = pdo_insert(PDO_NAME."draw",$data);
            if($res){
                //处理奖品信息
                $drawId = pdo_insertid();
                foreach($prize as $pKey => $pVal){
                    $joinData = [
                        'draw_id'       => $drawId ,//抽奖活动id
                        'draw_goods_id' => $pVal['draw_goods_id'] ? : 0 ,//奖品id
                        'probability'   => $pVal['probability'] ,//中奖概率
                        'serial_number' => $pVal['serial_number'] ,//序号(当前奖品在该活动中的顺序)
                    ];
                    $result = pdo_insert(PDO_NAME."draw_join",$joinData);
                    if(!$result){
                        $mysqlFun->rollback();
                        wl_message('添加失败，请刷新重试！' , referer() , 'error');
                    }
                }

                $mysqlFun->commit();
                wl_message('添加成功！' , web_url('draw/draw/index') , 'success');
            }else{
                $mysqlFun->rollback();
                wl_message('添加失败，请刷新重试！' , referer() , 'error');
            }
        }
        //获取自定义装修菜单信息
        if (p('diypage')) $menus = DiyMenu::getMenuList();

        include  wl_template('draw/add');
    }
    /**
     * Comment: 编辑抽奖活动
     * Author: zzw
     * Date: 2020/9/16 17:36
     */
    public function edit(){
        global $_W,$_GPC;
        //参数信息获取
        $id = intval($_GPC['id']) OR wl_message('参数错误，请刷新重试！' , referer() , 'error');
        if($_W['ispost']){
            //基本参数信息获取
            $data         = $_GPC['data'];
            $prize        = $_GPC['prize'];
            $activityTime = $_GPC['activity_time'];
            $mysqlFun     = new MysqlFunction();
            //判断轮盘数量是否为偶数
            if($data['type'] == 3){
                if(count($prize) % 2 != 0) wl_message("轮盘数量不为偶数，请设置为偶数！" , referer() , 'error');
                $data['wheel_bg'] = serialize($data['wheel_bg']);
            }
            //判断抽奖概率是否合法
            $totalProbability = sprintf("%.0f",array_sum(array_column($prize,'probability')));
            if($totalProbability != sprintf("%.0f",100)) wl_message("中奖概率错误，概率之和不为100%！当前概率之和：{$totalProbability}%" , referer() , 'error');
            //判断活动是否存在
            $isHave = pdo_get(PDO_NAME."draw",['title'=>$data['title'],'id <>'=>$id]);
            if($isHave) wl_message('已存在同名称活动！' , referer() , 'error');
            //判断限制
            if($data['total_join_times'] < $data['day_join_times']) wl_message('免费的总参加次数必须大于等于免费的每天参加次数！' , referer() , 'error');
            if($data['total_draw_times'] < $data['day_draw_times']) wl_message('总中奖次数必须大于等于每天中奖次数！' , referer() , 'error');
            if($data['total_parin_times'] < $data['day_parin_times']) wl_message('总参加次数必须大于等于每天参加次数！' , referer() , 'error');
            //生成活动信息
            $mysqlFun->startTrans();
            $data['start_time']  = strtotime($activityTime['start']);
            $data['end_time']    = strtotime($activityTime['end']);
            $data['share_image'] = serialize($data['share_image']);
            pdo_update(PDO_NAME."draw",$data,['id'=>$id]);
            //删除所有的相关奖品信息
            pdo_delete(PDO_NAME."draw_join",['draw_id'=>$id]);
            //处理奖品信息
            foreach($prize as $pKey => $pVal){
                //判断当前奖品是否存在
                $joinData = [
                    'draw_id'       => $id ,//抽奖活动id
                    'draw_goods_id' => $pVal['draw_goods_id'] ? : 0 ,//奖品id
                    'probability'   => $pVal['probability'] ,//中奖概率
                    'serial_number' => $pVal['serial_number'] ,//序号(当前奖品在该活动中的顺序)
                ];
                $result = pdo_insert(PDO_NAME."draw_join",$joinData);
                if(!$result){
                    $mysqlFun->rollback();
                    wl_message('修改失败，请刷新重试！' , referer() , 'error');
                }
            }
            $mysqlFun->commit();
            wl_message('修改成功！' , web_url('draw/draw/index') , 'success');
        }
        //进入编辑页面  获取当前活动基本参数信息
        $info = pdo_get(PDO_NAME."draw",['id'=>$id]);
        $info['rule'] = htmlspecialchars_decode($info['rule']);
        $info['introduce'] = htmlspecialchars_decode($info['introduce']);
        $info['wheel_bg'] = unserialize($info['wheel_bg']);
        $data['share_image'] = unserialize($data['share_image']);
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
        }
        //获取当前活动相关的奖品信息列表
        $parizeList = Draw::drawJoinList($id);
        $orderWhere = array_column($parizeList, 'serial_number');
        array_multisort($orderWhere, SORT_ASC, $parizeList);
        $parizeList = array_combine($orderWhere,$parizeList) ;
        //获取自定义装修菜单信息
        if (p('diypage')) $menus = DiyMenu::getMenuList();

        include  wl_template('draw/edit');
    }
    /**
     * Comment: 删除一个抽奖活动信息
     * Author: zzw
     * Date: 2020/9/16 18:13
     */
    public function delete(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR show_json(0, '参数错误，请刷新重试!');
        $mysqlFun = new MysqlFunction();
        //删除内容
        $mysqlFun->startTrans();
        $res = pdo_delete(PDO_NAME."draw",['id'=>$id]);
        if($res && pdo_count(PDO_NAME."draw_join",['draw_id'=>$id]) > 0) $res = pdo_delete(PDO_NAME."draw_join",['draw_id'=>$id]);
        if ($res) {
            $mysqlFun->commit();
            show_json(1 , '删除成功');
        }else {
            $mysqlFun->rollback();
            show_json(0 , '删除失败，请刷新重试');
        }
    }
    /**
     * Comment: 基本设置内容
     * Author: zzw
     * Date: 2020/9/17 11:14
     */
    public function set(){
        global $_W,$_GPC;
        if($_W['ispost']){
            $set = $_GPC['set'];

            Setting::agentsetting_save($set,'draw_set');
            wl_message('设置成功！' , web_url('draw/draw/set') , 'success');
        }
        //获取已存在的设置信息
        $set = Setting::agentsetting_read('draw_set');
        $set['rule'] = htmlspecialchars_decode($set['rule']);
        $set['introduce'] = htmlspecialchars_decode($set['introduce']);

        include  wl_template('draw/set');
    }


    /**
     * Comment: 抽奖码列表
     * Author: wlf
     * Date: 2022/9/14 10:04
     */
    public function codelist(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $draw = pdo_get('wlmerchant_draw',array('id' => $id),array('title'));
        if(empty($draw)){
            wl_message('参数错误，请返回重试！' ,web_url('draw/draw/index'), 'error');
        }
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $pageStart = $pindex * $psize - $psize;

        $where = " WHERE drawid = {$id}";
        $status = $_GPC['status'] ? : 0;
        if($status == 1){
            $where .= " AND mid = 0";
        }else if($status == 2){
            $where .= " AND mid > 0";
        }

        //时间
        if (!empty($_GPC['time_limit']) && $_GPC['timetype'] ) {
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']);
            if($_GPC['timetype'] == 1){
                $where .= " and createtime > {$starttime} and createtime < {$endtime}";
            }else{
                $where .= " and usetime > {$starttime} and usetime < {$endtime}";
            }
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }
        //备注
        $remake = trim($_GPC['remark']);
        if(!empty($remake)){
            $where .= " AND ( remark LIKE '%{$remake}%' OR code LIKE '%{$remake}%' )";
        }

        //导出
        if($_GPC['export']){
            $this -> exportCode($where);
        }

        $orderBy = ' ORDER BY id DESC ';
        $limit = " LIMIT {$pageStart},{$psize} ";
        $list = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_draw_code').$where.$orderBy.$limit);
        if(!empty($list)){
            foreach ($list as &$lo){
                if($lo['mid'] > 0){
                    $user = pdo_get('wlmerchant_member', array('id' => $lo['mid']),['nickname','encodename','avatar']);
                    $lo['nickname'] = $user['encodename'] ? base64_decode($user['encodename']) : $user['nickname'];
                    $lo['avatar'] = tomedia($user['avatar']);
                    $lo['usetime'] = date('Y-m-d H:i:s',$lo['usetime']);
                }
                $lo['createtime'] = date('Y-m-d H:i:s',$lo['createtime']);
                //中奖记录
                if($lo['recodeid'] > 0){
                    $lo['drawres'] = pdo_getcolumn(PDO_NAME.'draw_record',array('id'=> $lo['recodeid']),'draw_goods_id');
                }
            }
        }
        $tatal = pdo_fetchcolumn('SELECT count(id) FROM '.tablename('wlmerchant_draw_code').$where);
        $pager = wl_pagination($tatal, $pindex, $psize);

        include  wl_template('draw/codelist');
    }

    /**
     * Comment: 生成抽奖码页面
     * Author: wlf
     * Date: 2022/9/14 10:41
     */
    public function createCode(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        if(empty($id)) {
            show_json(0, '参数错误，请刷新重试');
        }



        include wl_template('draw/createCode');

    }

    /**
     * Comment: 生成抽奖码页面
     * Author: wlf
     * Date: 2022/9/14 11:12
     */
    public function cateCodeApi(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        if(empty($id)) {
            show_json(0, '参数错误，请刷新重试');
        }
        $data = $_GPC['data'];
        $num = intval($data['num']);
        if($num < 1){
            show_json(0, '生成得数量错误，请重新输入');
        }
        for ($i=0; $i < $num;$i++){
            $info = [
                'uniacid' => $_W['uniacid'],
                'aid'     => $_W['aid'],
                'remark'  => $data['remark'],
                'code'    => Util::createConcode(8,8),
                'createtime' => time(),
                'drawid'  => $id
            ];
            pdo_insert(PDO_NAME . 'draw_code', $info);
        }
        show_json(1 , '生成完成');
    }


    /**
     * Comment: 删除抽奖码
     * Author: wlf
     * Date: 2022/9/14 11:12
     */
    public function deleteCode(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'];
        $id = $_GPC['id'];
        if(!empty($ids)){
            foreach ($ids as $v) {
                pdo_delete('wlmerchant_draw_code',array('id'=>$v));
            }
        }else{
            pdo_delete('wlmerchant_draw_code',array('id'=>$id));
        }
        show_json(1, '操作成功');
    }

    /**
     * Comment: 导出抽奖码
     * Author: wlf
     * Date: 2022/9/14 18:23
     */
    public function exportCode($where){
        $list = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_draw_code').$where);

        /* 输出表头 */
        $filter = array(
            'code'  => '抽奖码',//U
            'title' => '抽奖活动标题',//A
            'nickname' => '使用用户昵称',//B
            'remark' => '备注',//C
            'createtime' => '生成时间',//D
            'usetime' => '使用时间',//E
        );

        $data = array();
        for ($i=0; $i < count($list) ; $i++) {
            foreach ($filter as $key => $title) {
                if($key == 'createtime'){
                    $data[$i][$key] = date('Y-m-d H:i:s', $list[$i][$key]);
                }else if($key == 'usetime'){
                    if($list[$i][$key] > 0){
                        $data[$i][$key] = date('Y-m-d H:i:s', $list[$i][$key]);
                    }else{
                        $data[$i][$key] = '未使用';
                    }
                }else if($key == 'title'){
                    $data[$i][$key] = pdo_getcolumn(PDO_NAME.'draw',array('id'=>$list[$i]['drawid']),'title');
                }else if($key == 'nickname'){
                    $data[$i][$key] = pdo_getcolumn(PDO_NAME.'member',array('id'=>$list[$i]['mid']),'nickname');
                }else {
                    $data[$i][$key] = $list[$i][$key];
                }

            }
        }

        util_csv::export_csv_2($data, $filter, '抽奖码表.csv');
        exit();


    }


}
