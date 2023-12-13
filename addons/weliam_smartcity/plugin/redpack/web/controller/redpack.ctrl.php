<?php
defined('IN_IA') or exit('Access Denied');

class Redpack_WeliamController{
    /**
     * Comment: 红包列表
     */
    public function pack_lists(){
        global $_W , $_GPC;
        //参数获取
        $pindex = max(1 , intval($_GPC['page']));
        $psize  = 10;
        $name   = trim($_GPC['name']);
        //条件生成
        $where  = ['uniacid' => $_W['uniacid'],'aid'=>$_W['aid']];
        if ($name) $where['title LIKE'] = "%".$name."%";
        if($_GPC['status'] > 0){
            if($_GPC['status'] == 3){
                $where['status'] = 0;
            }else{
                $where['status'] = $_GPC['status'];
            }
        }



        if($_GPC['export'] > 0){
            $this -> exportRedpack($where);
        }
        //信息获取
        $lists = pdo_getslice(PDO_NAME.'redpacks' , $where , [$pindex , $psize] , $total , [] , '' , "sort DESC,id DESC");

        foreach ($lists as $key => &$val) {
            $usetimes            = [
                date('Y-m-d' , $val['use_start_time']) . ' ~ ' . date('Y-m-d' , $val['use_end_time']) ,
                '领取当日起' . $val['usetime_day1'] . '天内有效' ,
                '领取次日起' . $val['usetime_day2'] . '天内有效'
            ];
            $val['usetime_text'] = $usetimes[$val['usetime_type']];
            $val['all_count']    = $val['all_count'] ? $val['all_count'] . '个' : '无限';
            $val['limit_count']  = $val['limit_count'] ? $val['limit_count'] . '个' : '无限';
            $val['createtime']   = date('Y-m-d H:i:s' , $val['createtime']);
            $val['get_count']    = pdo_getcolumn(PDO_NAME.'redpack_records' , ['packid' => $val['id']] , 'COUNT(id)');
        }
        $pager       = wl_pagination($total , $pindex , $psize);

        include wl_template('redpack/pack_lists');
    }

    /**
     * Comment: 导出红包
     */

    public function exportRedpack($where){
        global $_W , $_GPC;
        $lists = pdo_getall(PDO_NAME.'redpacks' , $where ,'*', '',"sort DESC,id DESC");
        foreach ($lists as $key => &$val) {
            $newinfo = [];
            $usetimes            = [
                date('Y-m-d' , $val['use_start_time']) . ' ~ ' . date('Y-m-d' , $val['use_end_time']) ,
                '领取当日起' . $val['usetime_day1'] . '天内有效' ,
                '领取次日起' . $val['usetime_day2'] . '天内有效'
            ];
            $val['all_count']    = $val['all_count'] ? $val['all_count'] . '个' : '无限';
            $val['limit_count']  = $val['limit_count'] ? $val['limit_count'] . '个' : '无限';
            $val['get_count']    = pdo_getcolumn(PDO_NAME.'redpack_records' , ['packid' => $val['id']] , 'COUNT(id)');
            //判断是否过期
            if($val['usetime_type'] == 0 && $val['use_end_time'] <= time()) $val['status'] = 2;

            $newinfo['id'] = $val['id'];
            $newinfo['title'] = $val['title'];
            $newinfo['price'] = $val['cut_money'].'元/满'.$val['full_money'].'可用';
            $newinfo['scene'] = $val['scene'] ? '系统发放' : '自助领取';
            $newinfo['limit'] = $val['limit_count'];
            $newinfo['stk'] = $val['all_count'].'/'.$val['get_count'];
            if($val['status'] == 2){
                $newinfo['status'] = '过期';
            }else if($val['status'] == 1){
                $newinfo['status'] = '上架';
            }else{
                $newinfo['status'] = '下架';
            }
            $newinfo['time'] = $usetimes[$val['usetime_type']];
            $newList[] = $newinfo;
        }


        //标题内容
        $filter = [
            'id'           => 'ID',
            'title'        => '红包名称' ,
            'price'        => '红包金额/使用条件' ,
            'scene'        => '红包场景' ,
            'limit'        => '每人限量' ,
            'stk'          => '总数量/已领数量' ,
            'time'         => '使用期限' ,
            'status'       => '状态' ,
            'mobile'       => '发放手机号'
        ];

        util_csv::export_csv_2($newList, $filter, '红包列表.csv');
        exit();


    }

    /**
     * Comment: 添加/编辑红包信息
     */
    public function pack_edit(){
        global $_W , $_GPC;
        $id = intval($_GPC['id']);
        //提交操作
        if (checksubmit('submit')) {
            $data                   = $_GPC['item'];
            $data['use_aids']       = iserializer($_GPC['item']['use_aids']);
            $data['use_sids']       = iserializer($_GPC['item']['use_sids']);
            $data['use_start_time'] = strtotime($_GPC['usetime']['start']);
            $data['use_end_time']   = strtotime($_GPC['usetime']['end']);
            //商品参数处理
            $data['rush_ids']    = iserializer($_GPC['item']['rush_ids']);
            $data['group_ids']   = iserializer($_GPC['item']['group_ids']);
            $data['fight_ids']   = iserializer($_GPC['item']['fight_ids']);
            $data['bargain_ids'] = iserializer($_GPC['item']['bargain_ids']);
            $level = $_GPC['level'];
            $data['transferlevel'] = serialize($level);
            //信息判断
            if (($data['usetime_type'] == 1 && empty($data['usetime_day1'])) || ($data['usetime_type'] == 2 && empty($data['usetime_day2']))) wl_message('请填写有效天数' , referer() , 'error');
            //添加/编辑操作
            if (!empty($id)) {
                pdo_update(PDO_NAME.'redpacks' , $data , ['id' => $id]);
            }else {
                $data['aid']        = $_W['aid'];
                $data['uniacid']    = $_W['uniacid'];
                $data['createtime'] = time();
                pdo_insert(PDO_NAME.'redpacks' , $data);
                $id = pdo_insertid();
            }
            wl_message('编辑红包成功' , web_url('redpack/redpack/pack_edit' , ['id' => $id]) , 'success');
        }
        //添加/修改操作
        if (!empty($id)) {
            $item             = pdo_get(PDO_NAME.'redpacks'
                , ['uniacid' => $_W['uniacid'] , 'id' => $id,'aid'=>$_W['aid']]);
            $item['use_aids'] = iunserializer($item['use_aids']);
            $item['use_sids'] = iunserializer($item['use_sids']);
            //商品参数处理
            $item['rush_ids']    = iunserializer($item['rush_ids']);
            $item['group_ids']   = iunserializer($item['group_ids']);
            $item['fight_ids']   = iunserializer($item['fight_ids']);
            $item['bargain_ids'] = iunserializer($item['bargain_ids']);
            $item['level'] = unserialize($item['transferlevel']);
        }else {
            //默认排序为当前的最大值
            $maxSort = pdo_fetchcolumn("SELECT max(sort) FROM " . tablename(PDO_NAME . "redpacks"));
            $sort    = $maxSort ? ($maxSort + 1) : 1;
            $item = [
                'sort'           => $sort ,
                'status'         => 1 ,
                'scene'          => 1 ,
                'use_start_time' => time() ,
                'use_end_time'   => time() + 60 * 24 * 3600,
                'usegoods_type'  => 1
            ];
            //在代理商平台的兼容信息
            if(is_agent()){
                $item['use_aids'] = [$_W['aid']];
            }
        }
        //代理商列表&商家列表
        if(is_agent()){
            $agents = pdo_getall(PDO_NAME.'agentusers' , ['uniacid' => $_W['uniacid'],'id'=>$_W['aid']] , ['id' , 'agentname']);
        }else{
            $agents = pdo_getall(PDO_NAME.'agentusers' , ['uniacid' => $_W['uniacid']] , ['id' , 'agentname']);
            $agents = array_merge([['id'=>0,'agentname'=>'总平台']],$agents);
        }
        $stores = pdo_getall(PDO_NAME.'merchantdata' , ['uniacid' => $_W['uniacid'],'aid'=>$_W['aid']] , ['id' , 'storename']);
        //获取当前代理的商品信息  抢购、团购、拼团、砍价
        $where       = ['uniacid' => $_W['uniacid'] , 'aid' => $_W['aid'],'status'=>[1,2]];
        $rushList    = pdo_getall(PDO_NAME . "rush_activity" , $where,['id','name']);
        $grouponList = pdo_getall(PDO_NAME . "groupon_activity" , $where,['id','name']);
        $fightList   = pdo_getall(PDO_NAME . "fightgroup_goods" , $where,['id','name']);
        $bargainList = pdo_getall(PDO_NAME . "bargain_activity" , $where,['id','name']);
        //获取会员信息
        $levels = pdo_fetchall("SELECT * FROM " . tablename('wlmerchant_halflevel') . "WHERE uniacid = {$_W['uniacid']} AND status = 1 ORDER BY sort DESC");

        include wl_template('redpack/pack_edit');
    }
    /**
     * Comment: 红包上下架操作
     * Author: zzw
     * Date: 2020/2/17 10:40
     */
    public function pack_changeStatus(){
        global $_W,$_GPC;
        #1、参数获取
        $id     = intval($_GPC['id']);
        $status = intval($_GPC['status']);
        #2、改变状态 0=下架；1=上架
        if($status == 1) $data['status'] = 0;
        else $data['status'] = 1;
        #3、信息修改
        if(pdo_update(PDO_NAME."redpacks",$data,['id'=>$id])) show_json(1);
        else show_json(0,'请刷新重试!');
    }
    /**
     * Comment: 删除红包并且同时删除已领取的红包
     */
    public function pack_del(){
        global $_W , $_GPC;
        $id = $_GPC['id'] ? : $_GPC['ids'];

        $items = pdo_getall(PDO_NAME.'redpacks' , ['id' => $id , 'uniacid' => $_W['uniacid']] , ['id']);
        foreach ($items as $item) {
            //删除红包信息
            pdo_delete(PDO_NAME.'redpacks' , ['id' => $item['id']]);
            //删除已领取的红包信息
            pdo_delete(PDO_NAME.'redpack_records' , ['packid' => $item['id']]);
            //删除已关联到节日红包中的信息
            pdo_delete(PDO_NAME."redpack_festival_join",['pack_id'=>$item['id']]);
        }

        show_json(1 , ['url' => referer()]);
    }
    /**
     * Comment: 红包发放
     */
    public function pack_send(){
        global $_W , $_GPC;
        $id = intval($_GPC['id']);

        if ($_W['ispost']) {
            $users = $_GPC['mids'];
            if (!empty($users)) {
                foreach ($users as $user) {
                    Redpack::pack_send($user , $id , 'send');
                }
            }
            show_json(1 , ['url' => referer()]);
        }

        include wl_template('redpack/pack_send');
    }

    /**
     * Comment: 导入红包发放
     */
    public function pack_send_csv(){
        global $_W, $_GPC;
        #1、将获取基本信息
        //$id = $_GPC['redpackid'];
        $name = $_GPC['name'];//文件储存路径
        $fullName = PATH_ATTACHMENT . $name;//文件在本地服务器暂存地址
        #2、读取excel中的内容
        $info = util_csv::read_csv_lines($fullName, 999, 0);
        unlink($fullName);//获取文件信息后将.cvs文件删除
        #3、对读取到的信息进行处理
        foreach ($info as $k => &$v) {
            //3-1 判断是否存在数据 不存在是空行，不进行任何操作
            if (!is_array($v)) {
                unset($info[$k]);
                continue;
            }

            //3-2 编码转换  由gbk转为urf-8
            $separator = '*separator*';//分割符 写成长字符串 防止出错
            $encodres = mb_detect_encoding(implode($separator, $v), array("ASCII","GB2312","GBK","UTF-8"));
            if($encodres != 'UTF-8'){
                $v = explode($separator, iconv('gbk', 'utf-8', implode($separator, $v)));
            }
            $id = $v[0];
            $getMember = pdo_get('wlmerchant_member',array('mobile' => $v[8],'uniacid' => $_W['uniacid']),array('id','nickname'));
            if(empty($getMember)){
                $v['send_result'] = '手机号不存在，无法发放';
                continue;
            }
            $res = Redpack::pack_send($getMember['id'] , $id , 'send');
            if(is_error($res)){
                $v['send_result'] = $res['message'];
            }else{
                $v['send_result'] = '发放成功';
            }
        }

        #4、定义结果表格的标题
        $filter = array(
            0      => 'ID',
            1      => '红包名称' ,
            2      => '红包金额/使用条件' ,
            3      => '红包场景' ,
            4      => '每人限量' ,
            5      => '总数量/已领数量' ,
            6      => '使用期限' ,
            7      => '状态' ,
            8      => '发放手机号',
            'send_result' => '发放结果'
        );
        #5、返回批量发货的结果信息表
        util_csv::save_csv($info, $filter, $_W['uniacid'].'/'.date('Y-m-d',time()).'/'.'批量发放结果信息'.date('Y-m-d',time()).'.csv');
        util_csv::export_csv_2($info, $filter, '批量发放结果信息'.date('Y-m-d',time()).'.csv');

    }


    /**
     * Comment: 领取记录
     */
    public function record_lists(){
        global $_W , $_GPC;
        //参数获取
        $pindex = max(1 , intval($_GPC['page']));
        $psize  = 10;
        $packId = intval($_GPC['packid']);
        $name   = trim($_GPC['name']);
        $festivalId = intval($_GPC['festival_id']);
        $type = $_GPC['type'];
        $status = $_GPC['status'];

        //查询条件生成
        $where['uniacid'] = $_W['uniacid'];
        $where['aid'] = $_W['aid'];
        //通过红包名称查询
        if($name){
            //信息获取
            $lists = pdo_getall(PDO_NAME."redpacks",['title LIKE'=> '%' . $name. '%'],'id');
            $ids = array_column($lists,'id');
            $where['packid IN'] = $ids;
        }
        if($type == 1){
            $where['type'] = 1;
        }else if($type == 2){
            $where['type'] = 2;
        }else if($type == 3){
            $where['type'] = 0;
        }
        if($status > 0){
            if($status == 4){
                $where['status'] = 0;
            }else{
                $where['status'] = $status;
            }
        }
        //通过红包id查询
        if($packId){
            $where['packid'] = $packId;
            $name =  pdo_getcolumn(PDO_NAME.'redpacks',array('id'=>$packId),'title');
        }
        //通过节日红包活动查询
        if($festivalId) $where['festival_id'] = $festivalId;
        //领取记录获取
        $lists  = pdo_getslice(PDO_NAME.'redpack_records' ,$where , [$pindex , $psize] , $total , [] , '' , "id DESC");
        foreach ($lists as $key => &$val) {
            $val['createtime'] = date('Y-m-d H:i:s' , $val['createtime']);
            $val['usetime']    = $val['usetime'] ? date('Y-m-d H:i:s' , $val['usetime']) : '--';
            $val['pack']       = Redpack::pack_get($val['packid']);
            $val['member']     = Member::wl_member_get($val['mid'] , ['mobile' , 'nickname' , 'avatar']);
            //判断是否为节日红包领取
            $val['festival_name'] = $val['festival_id'] > 0 ? pdo_getcolumn(PDO_NAME."redpack_festival",['id'=>$val['festival_id']],'name') : '--';
            //判断是否有转赠记录
            $val['trflag'] = pdo_getcolumn(PDO_NAME.'transferRecord',array('type'=>2,'objid'=>$val['id']),'id');
            //查询使用商户
            if($val['plugin'] == 'rush'){
                $val['sid'] = pdo_getcolumn(PDO_NAME.'rush_order',array('id'=>$val['orderid']),'sid');
            }else{
                $val['sid'] = pdo_getcolumn(PDO_NAME.'order',array('id'=>$val['orderid']),'sid');
            }
            $val['storename'] = pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$val['sid']),'storename');
        }
        $pager = wl_pagination($total , $pindex , $psize);
        //统计信息获取
        $all_num = pdo_getcolumn(PDO_NAME.'redpack_records' ,array_merge(['aid'=>$_W['aid'],'uniacid' => $_W['uniacid']],$where)   , 'COUNT(id)');
        $use_num = pdo_getcolumn(PDO_NAME.'redpack_records' ,array_merge($where,['aid'=>$_W['aid'],'uniacid' => $_W['uniacid'] , 'status'  => 1]) , 'COUNT(id)');
        $end_num = pdo_getcolumn(PDO_NAME.'redpack_records' ,array_merge($where,['aid'=>$_W['aid'],'uniacid' => $_W['uniacid'] , 'status'  => 2]), 'COUNT(id)');
        if($use_num > 0) $use_rate = sprintf("%0.2f",$use_num/$all_num*100);
        else $use_rate = 0;

        $transfer_num = pdo_getcolumn(PDO_NAME.'redpack_records' ,array_merge($where, ['aid'=>$_W['aid'],'uniacid' => $_W['uniacid'],'transferflag'=>1]) , 'COUNT(id)');


        include wl_template('redpack/record_lists');
    }
    /**
     * Comment: 删除领取记录
     */
    public function record_del(){
        global $_W , $_GPC;
        $id = $_GPC['id'] ? : $_GPC['ids'];

        $items = pdo_getall(PDO_NAME.'redpack_records' , ['id' => $id , 'uniacid' => $_W['uniacid']] , ['id']);
        foreach ($items as $item) {
            pdo_delete(PDO_NAME.'redpack_records' , ['id' => $item['id']]);
        }

        show_json(1 , ['url' => referer()]);
    }
    /**
     * Comment: 红包使用
     */
    public function record_use(){
        global $_W , $_GPC;
        $id = $_GPC['id'] ? : $_GPC['ids'];
        $items = pdo_getall(PDO_NAME.'redpack_records' , ['id' => $id , 'uniacid' => $_W['uniacid']] , ['id']);
        foreach ($items as $item) {
            pdo_update(PDO_NAME.'redpack_records' , ['status' => 1 , 'usetime' => time()] , ['id' => $item['id']]);
        }

        show_json(1 , ['url' => referer()]);
    }

    /**
     * Comment: 转赠记录
     */
    public function record_transfer(){
        global $_W , $_GPC;
        $id = $_GPC['id'];
        $record = pdo_getall('wlmerchant_transferRecord',array('objid' => $id,'type' =>2),'','','createtime DESC');
        if(!empty($record)){
            foreach ($record as &$re){
                $omember = pdo_get(PDO_NAME.'member',array('id'=>$re['omid']),['nickname','avatar']);
                $re['nickname'] = $omember['nickname'];
                $re['avatar'] = tomedia($omember['avatar']);
                if($re['nmid'] > 0){
                    $nmember = pdo_get(PDO_NAME.'member',array('id'=>$re['nmid']),['nickname','avatar']);
                    $re['getnickname'] = $nmember['nickname'];
                    $re['getavatar'] = $nmember['avatar'];
                }
            }
        }
        include wl_template('redpack/record_transfer');
    }


    /**
     * Comment: 进入新人红包设置页面
     * Author: zzw
     * Date: 2020/2/18 14:11
     */
    public function new_pack(){
        global $_W , $_GPC;
        #1、获取红包列表信息  仅获取上架并且为 系统发放 的红包
        $where['uniacid'] = $_W['uniacid'];
        $where['aid']     = $_W['aid'];
        //$where['status']  = 1;
        $where['scene']   = 1;
        $list = pdo_getall(PDO_NAME."redpacks",$where
            ,['id','title','all_count','limit_count','full_money','cut_money','usegoods_type','use_start_time','use_end_time','usetime_type','usetime_day1','usetime_day2']);
        if(!$list) wl_message('无可使用的红包，请先添加系统发放并且上架中的红包！' ,web_url('redpack/redpack/pack_lists'), 'error');

        include wl_template('redpack/new_pack');
    }
    /**
     * Comment: 新人红包信息设置
     * Author: zzw
     * Date: 2020/2/18 14:10
     */
    public function new_pack_set(){
        global $_W,$_GPC;
        $setName = 'red_pack_new';
        $set = $_GPC['set'];
        #2、判断是否添加红包
        $ids = array_column($set['list'],'id');
        if($set['status'] == 1 && !$set['list']) Commons::sRenderError('无红包信息，请先添加红包！');
        if(in_array(0,$ids)) Commons::sRenderError('存在未选择红包的信息！');
        if(count($ids) != count(array_unique($ids))) Commons::sRenderError('存在重复的红包信息！');
        #3、记录设置信息
        $set['color'] = serialize($set['color']);//颜色设置信息转义
        Setting::agentsetting_save($set,$setName);

        Commons::sRenderSuccess('编辑成功！');
    }
    /**
     * Comment: 获取新人红包设置信息
     * Author: zzw
     * Date: 2020/2/18 14:10
     */
    public function new_pack_get(){
        global $_W,$_GPC;
        $setName = 'red_pack_new';
        #1、获取红包列表信息  仅获取上架并且为 系统发放 的红包
        $where['uniacid'] = $_W['uniacid'];
        $where['aid']     = $_W['aid'];
        //$where['status']  = 1;
        $where['scene']   = 1;
        $list = pdo_getall(PDO_NAME."redpacks",$where
            ,['id','title','status','all_count','limit_count','full_money','cut_money','usegoods_type','use_start_time','use_end_time','usetime_type','usetime_day1','usetime_day2']);
        if(!$list) Commons::sRenderError('无可使用的红包，请先添加系统发放且上架中的红包！',['url'=>web_url('redpack/redpack/pack_lists')]);
        #2、循环处理红包信息
        foreach($list as $key => &$val){
            //状态信息
            if($val['usetime_type'] == 0 && $val['use_end_time'] <= time()) {
                //判断是否过期  删除当前信息并且跳出本次循环
//                unset($list[$key]);
//                continue;
                $state = '已过期';
            }else if($val['status'] == 0){
                $state = '已下架';
            }else{
                $state = '正常';
            }
            $val['title'] = $val['title']."({$state})";
            //信息处理
            $usetimes            = [
                date('Y-m-d' , $val['use_start_time']) . ' ~ ' . date('Y-m-d' , $val['use_end_time']) ,
                '领取当日起' . $val['usetime_day1'] . '天内有效' ,
                '领取次日起' . $val['usetime_day2'] . '天内有效'
            ];
            $val['usetime_text'] = $usetimes[$val['usetime_type']];
            $val['cut_money'] = sprintf("%.2f",$val['cut_money']);
            $val['full_money'] = sprintf("%.2f",$val['full_money']);
            //删除无效数据
            unset($val['use_start_time']);
            unset($val['usetime_day1']);
            unset($val['usetime_day2']);
            unset($val['usetime_type']);
        }
        #2、构建一个以id作为下标的新数组
        $idArr = array_column($list,'id');
        $newList = array_combine($idArr,$list);
        #2、获取设置信息
        $set = Setting::agentsetting_read($setName);
        if(!$set){
            $set = [
                'status'    => 0 ,
                'image'     => URL_WEB_RESOURCE . 'images/new_redpack.png' ,
                'image_url' => URL_WEB_RESOURCE . 'images/new_redpack.png' ,
                'wheres'    => 0 ,
                'list'      => [] ,
                'color'     => [
                    'bg_color'     => '#F02C2C' ,
                    'price_color'  => '#FF4444' ,
                    'button_color' => '#FFE95D' ,
                    'text_color'   => '#F02C2C' ,
                ] ,
            ];
        }else{
            $set['color'] = unserialize($set['color']);
        }
        #2、信息拼装
        $data = [
            'list'     => $list ,
            'new_list' => $newList ,
            'set'      => $set ,
        ];

        Commons::sRenderSuccess('成功',$data);
    }


    /**
     * Comment: 节日红包信息
     * Author: zzw
     * Date: 2020/2/19 10:46
     */
    public function festival_pack(){
        global $_W,$_GPC;
        #1、参数获取
        $pindex = max(1 , intval($_GPC['page']));
        $psize  = 10;
        $name   = trim($_GPC['name']);
        #1、条件生成
        $where  = ['uniacid' => $_W['uniacid'],'aid'=>$_W['aid']];
        if ($name) $where['name LIKE'] = "%".$name."%";
        #1、信息获取
        $field = ['id','status','name','label','start_time','end_time'];
        $lists = pdo_getslice(PDO_NAME.'redpack_festival' , $where , [$pindex , $psize] , $total , $field , '' , "id DESC");
        foreach ($lists as $key => &$val) {
            //数据处理
            $val['time'] = date("Y-m-d",$val['start_time']).' ~ '.date("Y-m-d",$val['end_time']);
            $val['list'] = Redpack::getRedPackFestivalJoin($val['id'],'b.id,b.title,convert(b.full_money,decimal(10,2)) as full_money,convert(b.cut_money,decimal(10,2)) as cut_money,a.limit');
            //判断是否已经过期
            if($val['end_time'] <= time()) $val['status'] = 2;
            unset($val['start_time']);
            unset($val['end_time']);
        }
        $pager = wl_pagination($total , $pindex , $psize);

        include wl_template('redpack/festival_pack');
    }
    /**
     * Comment: 进入节日红包编辑页面
     * Author: zzw
     * Date: 2020/2/18 17:23
     */
    public function festival_pack_edit(){
        global $_W,$_GPC;
        $id = intval($_GPC['id']);
        $where['uniacid'] = $_W['uniacid'];
        $where['aid']     = $_W['aid'];
        $where['status']  = 1;
        $where['scene']   = 1;
        $list = pdo_getall(PDO_NAME."redpacks",$where
            ,['id','title','all_count','limit_count','full_money','cut_money','usegoods_type','use_start_time','use_end_time','usetime_type','usetime_day1','usetime_day2']);
        if(!$list) wl_message('无可使用的红包，请先添加系统发放并且上架中的红包！' ,web_url('redpack/redpack/pack_lists'), 'error');

        include wl_template('redpack/festival_pack_edit');
    }
    /**
     * Comment: 获取某条节日红包信息
     * Author: zzw
     * Date: 2020/2/18 18:18
     */
    public function festival_pack_get(){
        global $_W,$_GPC;
        $id = intval($_GPC['id']);
        #1、获取红包列表信息  仅获取上架并且为 系统发放 的红包
        $where['uniacid'] = $_W['uniacid'];
        $where['aid']     = $_W['aid'];
        $where['status']  = 1;
        $where['scene']   = 1;
        $list = pdo_getall(PDO_NAME."redpacks",$where
            ,['id','title','all_count','limit_count','full_money','cut_money','usegoods_type','use_start_time','use_end_time','usetime_type','usetime_day1','usetime_day2']);
        if(!$list) Commons::sRenderError('无红包信息，请先添加红包！');
        #2、循环处理红包信息
        foreach($list as $key => &$val){
            //判断是否过期  删除当前信息并且跳出本次循环
            if($val['usetime_type'] == 0 && $val['use_end_time'] <= time()) {
                unset($list[$key]);
                continue;
            }
            //信息处理
            $usetimes            = [
                date('Y-m-d' , $val['use_start_time']) . ' ~ ' . date('Y-m-d' , $val['use_end_time']) ,
                '领取当日起' . $val['usetime_day1'] . '天内有效' ,
                '领取次日起' . $val['usetime_day2'] . '天内有效'
            ];
            $val['usetime_text'] = $usetimes[$val['usetime_type']];
            $val['cut_money'] = sprintf("%.2f",$val['cut_money']);
            $val['full_money'] = sprintf("%.2f",$val['full_money']);
            //删除无效数据
            unset($val['use_start_time']);
            unset($val['usetime_day1']);
            unset($val['usetime_day2']);
            unset($val['usetime_type']);
        }
        #2、构建一个以id作为下标的新数组
        $idArr = array_column($list,'id');
        $newList = array_combine($idArr,$list);
        #2、获取当前节日红包的信息
        if($id){
            $info = pdo_get(PDO_NAME."redpack_festival",['id'=>$id]);
            if(!$info) Commons::sRenderError('信息获取失败，请刷新重试！');
            $info['color'] = unserialize($info['color']);
            $info['image_url'] = toimage($info['images']);
            $info['start_time'] = date("Y-m-d",$info['start_time']);
            $info['end_time'] = date("Y-m-d",$info['end_time']);
            $info['list'] = Redpack::getRedPackFestivalJoin($id,'a.pack_id,a.limit');
        }else{
            $info = [
                'status'        => 1 ,
                'name'          => '新年红包' ,
                'label'         => '新年' ,
                'images'        => URL_WEB_RESOURCE . 'images/festival_redpack_5.png' ,
                'image_url'     => URL_WEB_RESOURCE . 'images/festival_redpack_5.png' ,
                'color'         => [
                    'bg_color'     => '#BD1F29' ,
                    'price_color'  => '#FF4444' ,
                    'button_color' => '#F6D286' ,
                    'text_color'   => '#BD1F29' ,
                ] ,
                'start_time'    => date("Y-m-d" , time()) ,
                'end_time'      => date("Y-m-d" , strtotime("+1 Month" , time())) ,
                'list'          => [] ,
                'redpack_calss' => 5 ,//1=自定义类型，2=中秋红包，3=国庆红包，4=圣诞红包，5=新年红包，6=端午红包
            ];
        }
        #2、信息拼装
        $data = [
            'list'     => $list ,
            'new_list' => $newList ,
            'info'     => $info ,
        ];

        Commons::sRenderSuccess('成功',$data);
    }
    /**
     * Comment: 编辑节日红包信息
     * Author: zzw
     * Date: 2020/2/18 17:53
     */
    public function festival_pack_set(){
        global $_W,$_GPC;
        #1、参数获取
        $id = intval($_GPC['id']);
        $info = $_GPC['info'] ? : [];
        #2、判断信息是否填写完整
        if($info['status'] == 1 && !$info['name']) Commons::sRenderError('请输入节日名称！');
        if($info['status'] == 1 && !$info['label']) Commons::sRenderError('请输入红包标签！');
        $ids = array_column($info['list'],'pack_id');
        if($info['status'] == 1 && !$info['list']) Commons::sRenderError('无红包信息，请先添加红包！');
        if(in_array(0,$ids)) Commons::sRenderError('存在未选择红包的信息！');
        if(count($ids) != count(array_unique($ids))) Commons::sRenderError('存在重复的红包信息！');
        #3、记录信息/修改信息
        $joinList = $info['list'];
        unset($info['image_url']);
        unset($info['list']);
        //颜色设置信息转义
        $info['color'] = serialize($info['color']);
        //时间信息转义
        $info['start_time'] = strtotime($info['start_time']);
        $info['end_time'] = strtotime($info['end_time']);
        if($id){
            //修改操作
            pdo_update(PDO_NAME."redpack_festival",$info,['id'=>$id]);
            //删除已经存在的关联信息
            pdo_delete(PDO_NAME."redpack_festival_join",['festival_id'=>$id]);
        }else{
            $info['uniacid'] = $_W['uniacid'];
            $info['aid'] = $_W['aid'];
            //添加操作
            pdo_insert(PDO_NAME."redpack_festival",$info);
            $id = pdo_insertid();
        }
        #3、添加关联信息
        foreach($joinList as $key => $val){
            $data = [
                'uniacid'     => $_W['uniacid'] ,
                'pack_id'     => $val['pack_id'] ,//红包id
                'festival_id' => $id ,//节日信息id
                'limit'       => $val['limit'] ,//每人领取限制
            ];
            pdo_insert(PDO_NAME."redpack_festival_join",$data);
        }
        Commons::sRenderSuccess('编辑成功！',['id'=>$id]);

    }
    /**
     * Comment: 节日红包上下架操作
     * Author: zzw
     * Date: 2020/2/19 10:28
     */
    public function festival_pack_changeStatus(){
        global $_W,$_GPC;
        #1、参数获取
        $id     = intval($_GPC['id']);
        $status = intval($_GPC['status']);
        #2、改变状态 0=下架；1=上架
        if($status == 1) $data['status'] = 0;
        else $data['status'] = 1;
        #3、信息修改
        if(pdo_update(PDO_NAME."redpack_festival",$data,['id'=>$id])) show_json(1);
        else show_json(0,'请刷新重试!');
    }
    /**
     * Comment: 节日红包删除操作
     * Author: zzw
     * Date: 2020/2/19 10:31
     */
    public function festival_pack__del(){
        global $_W , $_GPC;
        $id = $_GPC['id'] ? : $_GPC['ids'];
        $items = pdo_getall(PDO_NAME.'redpack_festival' , ['id' => $id , 'uniacid' => $_W['uniacid']] , ['id']);
        foreach ($items as $item) {
            //删除节日红包信息
            pdo_delete(PDO_NAME.'redpack_festival' , ['id' => $item['id']]);
            //删除通过当前活动领取的红包信息
            pdo_delete(PDO_NAME.'redpack_records' , ['festival_id' => $item['id']]);
        }

        show_json(1);
    }
    /**
     * Comment: 更换模板
     * Author: zzw
     * Date: 2020/2/26 10:34
     */
    public function festival_pack_modelSelect(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'] ? : 0;
        $redPackCalss = $_GPC['redpack_calss'] ? : 1;
        #2、判断是否已存在信息  并且模板类型为当前选中类型  符合条件则返回已存在的信息  否则返回默认信息
        if($id > 0){
            $info = pdo_get(PDO_NAME."redpack_festival",['id'=>$id]);
            $list = Redpack::getRedPackFestivalJoin($id,'a.pack_id,a.limit');
            if($info['redpack_calss'] == $redPackCalss){
                if(!$info) Commons::sRenderError('信息获取失败，请刷新重试！');
                $info['color'] = unserialize($info['color']);
                $info['image_url'] = toimage($info['images']);
                $info['start_time'] = date("Y-m-d",$info['start_time']);
                $info['end_time'] = date("Y-m-d",$info['end_time']);
                $info['list'] = $list;

                Commons::sRenderSuccess('成功',$info);
            }
        }
        #3、模板获取  2=中秋红包，3=国庆红包，4=圣诞红包，5=新年红包，6=端午红包
        switch ($redPackCalss){
            case 2:
                $info = [
                    'name'          => '中秋红包' ,
                    'label'         => '中秋' ,
                    'images'        => URL_WEB_RESOURCE . 'images/festival_redpack_2.png' ,
                    'image_url'     => URL_WEB_RESOURCE . 'images/festival_redpack_2.png' ,
                    'color'         => [
                        'bg_color'     => '#31345D' ,
                        'price_color'  => '#FF4444' ,
                        'button_color' => '#FFE95D' ,
                        'text_color'   => '#31345D' ,
                    ] ,
                    'redpack_calss' => 2 ,
                ];
                break;//中秋红包
            case 3:
                $info = [
                    'name'          => '国庆红包' ,
                    'label'         => '国庆' ,
                    'images'        => URL_WEB_RESOURCE . 'images/festival_redpack_3.png' ,
                    'image_url'     => URL_WEB_RESOURCE . 'images/festival_redpack_3.png' ,
                    'color'         => [
                        'bg_color'     => '#F02C2C' ,
                        'price_color'  => '#FF4444' ,
                        'button_color' => '#FFE95D' ,
                        'text_color'   => '#F02C2C' ,
                    ] ,
                    'redpack_calss' => 3 ,
                ];
                break;//国庆红包
            case 4:
                $info = [
                    'name'          => '圣诞红包' ,
                    'label'         => '圣诞' ,
                    'images'        => URL_WEB_RESOURCE . 'images/festival_redpack_4.png' ,
                    'image_url'     => URL_WEB_RESOURCE . 'images/festival_redpack_4.png' ,
                    'color'         => [
                        'bg_color'     => '#E33D2C' ,
                        'price_color'  => '#FF4444' ,
                        'button_color' => '#FFE95D' ,
                        'text_color'   => '#E33D2C' ,
                    ] ,
                    'redpack_calss' => 4 ,
                ];
                break;//圣诞红包
            case 5:
                $info = [
                    'name'          => '新年红包' ,
                    'label'         => '新年' ,
                    'images'        => URL_WEB_RESOURCE . 'images/festival_redpack_5.png' ,
                    'image_url'     => URL_WEB_RESOURCE . 'images/festival_redpack_5.png' ,
                    'color'         => [
                        'bg_color'     => '#BD1F29' ,
                        'price_color'  => '#FF4444' ,
                        'button_color' => '#F6D286' ,
                        'text_color'   => '#BD1F29' ,
                    ] ,
                    'redpack_calss' => 5 ,
                ];
                break;//新年红包
            case 6:
                $info = [
                    'name'          => '端午红包' ,
                    'label'         => '端午' ,
                    'images'        => URL_WEB_RESOURCE . 'images/festival_redpack_6.png' ,
                    'image_url'     => URL_WEB_RESOURCE . 'images/festival_redpack_6.png' ,
                    'color'         => [
                        'bg_color'     => '#C1ECD3' ,
                        'price_color'  => '#FF4444' ,
                        'button_color' => '#038233' ,
                        'text_color'   => '#C1ECD3' ,
                    ] ,
                    'redpack_calss' => 6 ,
                ];
                break;//端午红包
        }
        #4、添加模板公共信息
        $info['status']     = 1;
        $info['start_time'] = date("Y-m-d" , time());
        $info['end_time']   = date("Y-m-d" , strtotime("+1 Month" , time()));
        $info['list']       = $list ? $list : [];

        Commons::sRenderSuccess('成功',$info);
    }



    /**
     * Comment: 保存设置信息
     * Author: zzw
     * Date: 2020/3/17 15:13
     */
    public function set(){
        global $_W,$_GPC;
        #1、名称设置
        $name = 'red_pack_set';
        #2、保存设置信息
        if (checksubmit('submit')){
            $set = $_GPC['set'];
            $set['intervalmin'] = sprintf("%.0f",$set['intervalmin']);
            $res = Setting::wlsetting_save($set,$name);
            if ($res) show_json(1);
            else show_json(0, '保存失败');
        }
        #3、获取设置信息
        $set = Setting::wlsetting_read($name);

        include wl_template('redpack/set');
    }

}