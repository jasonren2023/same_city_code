<?php
defined('IN_IA') or exit('Access Denied');


class Matchmaker_WeliamController{
    /**
     * Comment: 红娘列表
     * Author: zzw
     * Date: 2021/3/3 11:26
     */
    public function matchmakerList(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $nickname  = $_GPC['nickname'] ? : '';
        $status    = $_GPC['status'] ? : 0;
        $createSource = $_GPC['create_source'] ? : 0;
        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} ";
        if($nickname) $where .= " AND (a.nickname LIKE '%{$nickname}%' OR b.nickname LIKE '%{$nickname}%') ";
        if($status > 0) $where .= " AND a.status = {$status} ";
        if($createSource > 0) $where .= " AND a.create_source = {$createSource} ";
        //sql语句生成
        $field = "a.id,a.mid,a.nickname,a.avatar,a.phone,a.wechat_number,a.qq_unmber,a.`describe`,a.status,
        a.total_commission,a.commission,a.create_time,a.create_source";
        $order = " ORDER BY a.create_time DESC,a.id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."dating_matchmaker")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id ";
        //列表获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            $val = Dating::handleMatchmakerInfo($val);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('matchmaker/index');
    }
    /**
     * Comment: 红娘信息编辑
     * Author: zzw
     * Date: 2021/3/3 10:40
     */
    public function matchmakerEdit(){
        global $_W,$_GPC;
        //基本参数信息获取
        $id = $_GPC['id'] ? : '';
        //接收信息 并且进行对应的处理
        if($_W['ispost']){
            $data  = $_GPC['data'];
            //判断信息是否完整
            if(!$data['mid']) wl_message('请选择用户！',referer(),'error');
            if(!$data['phone']) wl_message('请输入联系方式！',referer(),'error');
            if(!$data['qrcode']) wl_message('请上传二维码！',referer(),'error');
            //根据是否存在id 判断是添加操作还是修改操作
            if($id){
                pdo_update(PDO_NAME."dating_matchmaker",$data,['id'=>$id]);

                wl_message('修改成功',web_url('dating/matchmaker/matchmakerList'),'success');
            }else{
                //判断当前用户是否已经成为红娘
                $isHave = pdo_get(PDO_NAME."dating_matchmaker",['uniacid'=>$_W['uniacid'],'mid'=>$data['mid']]);
                if($isHave) wl_message('添加失败，该用户已是红娘!',referer(),'error');
                //信息补充  并且进行添加操作
                $data['uniacid']     = $_W['uniacid'];
                $data['create_time'] = time();
                $data['create_source'] = 2;//创建来源:1=用户申请,2=后台创建
                $data['status'] = 3;//状态:1=待付款,2=待审核,3=已通过,4=未通过
                pdo_insert(PDO_NAME."dating_matchmaker",$data);

                wl_message('添加成功',web_url('dating/matchmaker/matchmakerList'),'success');
            }
        }
        //准备信息
        if($id){
            //修改信息准备
            $info = pdo_get(PDO_NAME."dating_matchmaker",['id'=>$id]);
        }

        include wl_template('matchmaker/edit');
    }
    /**
     * Comment: 红娘审核操作
     * Author: zzw
     * Date: 2021/3/3 11:39
     */
    public function matchmakerExamine(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR show_json(0, "参数错误，请刷新重试!");
        $status = $_GPC['status'] ? : 3;//状态:1=待付款,2=待审核,3=已通过,4=未通过
        $reason = $_GPC['reason'] ? : '';
        if($status == 4 && !$reason) show_json(0, "请输入驳回原因");
        //修改状态
        $data = [
            'status' => $status,
            'reason'  => $reason
        ];
        pdo_update(PDO_NAME."dating_matchmaker",$data,['id'=>$id]);
        //发送模板消息通知用户审核结果
        if($status == 4) {
            $resultText = '未通过';
            $contentText = "驳回原因：{$reason}";
            $link = "pages/subPages2/blindDate/form/matchmakerApply";
        } else if($status == 3) {
            $resultText = '已通过';
            $contentText = "恭喜您，红娘信息审核已通过！";
            $link = "pages/subPages2/blindDate/form/matchmakerApply";
        }
        $member = pdo_get(PDO_NAME."dating_matchmaker",['id'=>$id]);
        $modelData = [
            'first'   => '',
            'type'    => '审核结果',
            'content' => $contentText,
            'status'  => $resultText,
            'time'    => date("Y-m-d H:i:s",time()),
            'remark'  => "点击查看"
        ];
        TempModel::sendInit('service',$member['mid'],$modelData,$_W['source'],h5_url($link));


        show_json(1, "操作成功");
    }
    /**
     * Comment: 删除红娘信息
     * Author: zzw
     * Date: 2021/3/3 11:39
     */
    public function matchmakerDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."dating_matchmaker",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }
    /**
     * Comment: 获取红娘名下的用户列表
     * Author: zzw
     * Date: 2021/3/3 14:56
     */
    public function matchmakerMember(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $id        = $_GPC['id'] OR wl_message('参数错误，请刷新重试',web_url('dating/matchmaker/matchmakerList'),'error');
        $nickname  = $_GPC['nickname'] ? : '';
        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.matchmaker_id = {$id} ";
        if($nickname) $where .= " AND b.nickname LIKE '%{$nickname}%' ";
        //sql语句生成
        $field = "a.id,a.mid,a.gneder,a.birth,a.height,a.weight,a.nation,a.marital_status,a.education,
        a.current_province,a.current_city,a.current_area";
        $order = " ORDER BY sort DESC,id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."dating_member")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id ";
        //列表获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            $val = Dating::handleMemberInfo($val);
        }

        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('matchmaker/member');
    }


    /**
     * Comment: 佣金明细列表
     * Author: zzw
     * Date: 2021/3/16 11:07
     */
    public function commissionList(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1,intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $mid       = $_GPC['mid'] ? : '';
        $type      = $_GPC['type'] ? : '';
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} ";
        if($mid) $where .= " AND mid = {$mid} ";
        if($type) $where .= " AND type = {$type} ";
        //sql语句生成
        $field = "id,mid,type,money,order_id,reason,create_time";
        $order = " ORDER BY create_time DESC,id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."dating_matchmaker_commission");
        //列表获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            //获取用户信息
            [$val['nickname'],$val['avatar']] = Dating::handleUserInfo($val['mid']);
            //获取订单号
            if($val['order_id']) $val['orderno'] = pdo_getcolumn(PDO_NAME."order",['id'=>$val['order_id']],'orderno');
            //时间处理
            $val['create_time'] = date("Y-m-d H:i:s",$val['create_time']);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('commission/index');
    }


    /**
     * Comment: 订单列表
     * Author: zzw
     * Date: 2021/3/16 11:08
     */
    public function orderList(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1,intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND plugin = 'dating' ";
        if(!empty($_GPC['payfor'])){
            if($_GPC['payfor'] == 1){
                $where .= "AND payfor = 'datingTop' ";
            }else if($_GPC['payfor'] == 2){
                $where .= "AND payfor = 'datingMatchmaker' ";
            }else if($_GPC['payfor'] == 3){
                $where .= "AND payfor = 'datingVip' ";
            }
        }
        if($_GPC['keyword']){
            $keyword = $_GPC['keyword'];
            if($_GPC['keywordtype'] == 1){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']}  AND nickname LIKE :name",$params);
                if($members){
                    $mids = "(";
                    foreach ($members as $key => $v) {
                        if($key == 0){
                            $mids.= $v['id'];
                        }else{
                            $mids.= ",".$v['id'];
                        }
                    }
                    $mids.= ")";
                    $where .= "AND mid IN {$mids} ";
                }else{
                    $where .= "AND mid = 0 ";
                }
            }else if($_GPC['keywordtype'] == 2){
                $where .= "AND orderno LIKE '%{$keyword}%' ";
            }
        }
        if($_GPC['time_limit']){
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']) + 86400;
            $where .= "AND paytime > {$starttime} ";
            $where .= "AND paytime < {$endtime} ";
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 years');
            $endtime = time();
        }

        if(!empty($_GPC['export'])){
            $this -> export($where);
        }
        //sql语句生成
        $field = "id,orderno,status,paytype,paytime,payfor,price,mid,fkid,num,createtime";
        $order = " ORDER BY createtime DESC,id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."order");
        //列表获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            //获取用户信息
            [$val['nickname'],$val['avatar']] = Dating::handleUserInfo($val['mid']);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('order/datorderindex');
    }

    /**
     * Comment: 订单导出
     * Author: wlf
     * Date: 2021/11/23 11:28
     */
    public function export($where){
        global $_W,$_GPC;
        //sql语句生成
        $field = "id,orderno,status,paytype,paytime,payfor,price,mid,fkid,num,createtime";
        $order = " ORDER BY createtime DESC,id DESC ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."order");
        //列表获取
        $list = pdo_fetchall($sql.$where.$order);
        foreach($list as $key => &$val){
            //获取用户信息
            [$val['nickname'],$val['avatar']] = Dating::handleUserInfo($val['mid']);
            $val['createtime'] = date("Y-m-d H:i",$val['createtime']);
            $val['paytime'] = date("Y-m-d H:i",$val['paytime']);
            $val['orderno'] = "\t".$val['orderno']."\t";

        }

        $filter = array(
            'orderno' => '订单编号',
            'nickname' => '用户昵称',
            'createtime' => '下单时间',
            'payfor' => '付费类型',
            'price' => '订单金额',
            'paytype' => '支付方式',
            'paytime' => '支付时间'
        );

        $data = array();
        for ($i=0; $i < count($list) ; $i++) {
            foreach ($filter as $key => $title) {
                if($key == 'payfor') {
                    switch ($list[$i][$key]) {
                        case 'datingTop':
                            $data[$i][$key] = '置顶';
                            break;
                        case 'datingMatchmaker':
                            $data[$i][$key] = '红娘入驻';
                            break;
                        case 'datingVip':
                            $data[$i][$key]  = '开通会员';
                            break;
                    }
                }else if($key == 'paytype'){
                    switch ($list[$i][$key]) {
                        case '1':
                            $data[$i][$key] = '余额支付';
                            break;
                        case '2':
                            $data[$i][$key] = '微信支付';
                            break;
                        case '3':
                            $data[$i][$key] = '支付宝支付';
                            break;
                        case '4':
                            $data[$i][$key] = '货到付款';
                            break;
                        case '5':
                            $data[$i][$key] = '小程序支付';
                            break;
                        case '6':
                            $data[$i][$key] = '0元购';
                            break;
                        case '7':
                            $data[$i][$key] = '混合支付';
                            break;
                        default:
                            $data[$i][$key]  = '其他或未支付';
                            break;
                    }
                }else {
                    $data[$i][$key] = $list[$i][$key];
                }
            }
        }
        util_csv::export_csv_2($data, $filter, '相亲交友订单表.csv');
        exit();

    }




}