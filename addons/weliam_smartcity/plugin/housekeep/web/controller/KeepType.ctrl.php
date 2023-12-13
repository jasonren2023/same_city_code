<?php
defined('IN_IA') or exit('Access Denied');

class KeepType_WeliamController {
    /**
     * Comment: 家政服务列表
     * Author: wlf
     * Date: 2021/03/31 16:39
     */
    public function typelists() {
        global $_W, $_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $title     = $_GPC['title'] ? : '';//活动名称
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if($title){
            $ids = pdo_fetchall("SELECT onelevelid FROM "
                .tablename(PDO_NAME."housekeep_type")
                .$where." AND title LIKE '%{$title}%' AND onelevelid > 0");
            $ids = array_column($ids,'onelevelid');
            $ids = $ids ? implode(',',$ids) : [];
            $where .= " AND (title LIKE '%{$title}%' OR id IN ({$ids})) ";
        }
        //sql语句生成
        $field = "id,title,sort,status,color,img";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."housekeep_type");
        $order = " ORDER BY sort DESC,id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        //列表获取
        $list = pdo_fetchall($sql.$where." AND onelevelid = 0".$order.$limit);
        foreach($list as $key => &$val){
            $val['list'] = pdo_fetchall($sql.$where." AND onelevelid = {$val['id']}".$order);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where." AND onelevelid = 0");
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('keeptype/typelists');
    }
    /**
     * Comment: 导入默认服务类型
     * Author: wlf
     * Date: 2021/03/31 15:20
     */
    public function importType(){
        global $_W, $_GPC;
        $list = Housekeep::defaultType();
        $tableName = PDO_NAME."housekeep_type";
        pdo_fetchall("update ".tablename($tableName)." set `sort` = 0 WHERE `sort` is null ");
        $publicWhere = ['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']];
        foreach($list as $item){
            //判断当前行业是否已经存在
            $pid = pdo_getcolumn($tableName,array_merge($publicWhere,['title'=>$item['title']]),'id');
            if(!$pid){
                //不存在 添加信息
                $insertData = [
                    'uniacid'     => $_W['uniacid'] ,
                    'aid'         => $_W['aid'] ,
                    'title'       => $item['title'] ,
                    'onelevelid'  => 0 ,
                    'createtime' => time() ,
                    'img'         => $item['img'],
                    'status'      => 1,
                    'color'       => '#000000'
                ];
                pdo_insert($tableName,$insertData);
                $pid = pdo_insertid();
            }
            //处理子行业信息
            foreach($item['list'] as $sub){
                //判断当前行业是否已经存在
                $isHave = pdo_getcolumn($tableName,array_merge($publicWhere,['title'=>$sub,'pid'=>$pid]),'id');
                if(!$isHave){
                    //不存在 添加信息
                    $subData = [
                        'uniacid'     => $_W['uniacid'] ,
                        'aid'         => $_W['aid'] ,
                        'title'       => $sub ,
                        'onelevelid'  => $pid ,
                        'createtime'  => time() ,
                        'color'       => '#000000',
                        'status'      => 1,
                    ];
                    pdo_insert($tableName,$subData);
                }
            }
        }
        wl_json(1,'生成成功');
    }

    /**
     * Comment: 修改分类状态
     * Author: wlf
     * Date: 2021/03/31 17:20
     */
    public function changeStatus (){
        global $_W , $_GPC;
        //获取参数信息
        $id = $_GPC['id'] OR Commons::sRenderError('缺少参数：id');
        $status = $_GPC['status'] ? : 0;
        //修改操作
        $res = pdo_update(PDO_NAME."housekeep_type",['status'=>$status],['id'=>$id]);
        if($res) Commons::sRenderSuccess('修改成功');
        else Commons::sRenderError('修改失败，请刷新重试!');
    }

    /**
     * Comment: 修改分类信息
     * Author: wlf
     * Date: 2021/03/31 17:36
     */
    public function setInfo (){
        global $_W , $_GPC;
        //获取参数信息
        $id = $_GPC['id'];
        $value = trim($_GPC['value']);
        $type = $_GPC['type'];
        $res = pdo_update(PDO_NAME."housekeep_type",array($type=>$value),array('id'=>$id));
        if($res){
            show_json(1, "修改成功");
        }else{
            show_json(0, "修改失败");
        }
    }

    /**
     * Comment: 删除分类信息
     * Author: wlf
     * Date: 2021/03/31 17:45
     */
    public function typeDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."housekeep_type",['id IN'=>$ids]);
        pdo_delete(PDO_NAME."housekeep_type",['onelevelid IN'=>$ids]);
        show_json(1, "删除成功");
    }

    /**
     * Comment: 添加/编辑家政服务类型
     * Author: wlf
     * Date: 2021/03/31 18:10
     */
    public function editType(){
        global $_W,$_GPC;
        //参数信息获取
        $parentId = trim($_GPC['parentid']);
        $id = $_GPC['id'];
        //进入添加子分类页面  获取上级分类信息
        if ($id > 0) {
            $data = Util::getSingelData("*", PDO_NAME . 'housekeep_type', array('id' => $id));
            $parentId = $data['onelevelid'];
        }
        if($parentId > 0){
            $parentTitle = pdo_getcolumn(PDO_NAME . 'housekeep_type',['id'=>$parentId],'title');
        }
        if ($_W['ispost']){
            $data = $_GPC['data'];
            $data['sort'] = sprintf("%.0f",$data['sort']);
            if($data['id'] > 0){
                $res = pdo_update(PDO_NAME.'housekeep_type', $data,array('id' => $id));
                if($res){
                    wl_message('修改成功', web_url('housekeep/KeepType/typelists'), 'success');
                }else{
                    wl_message('修改失败,请刷新重试', referer(), 'error');
                }
            }else{
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                $res = pdo_insert(PDO_NAME.'housekeep_type', $data);
                if($res){
                    wl_message('创建成功', web_url('housekeep/KeepType/typelists'), 'success');
                }else{
                    wl_message('创建失败,请刷新重试', referer(), 'error');
                }
            }
        }

        include wl_template('keeptype/editType');
    }

    //套餐列表
    public function mealLists() {
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 15;
        $lists = pdo_getslice('wlmerchant_housekeep_meals', array('uniacid' => $_W['uniacid'], 'aid' => $_W['aid']), array($pindex, $psize), $total, array(), '', "sort DESC");
        foreach ($lists as $key => &$val) {
            $val['usenum'] = intval(pdo_getcolumn('wlmerchant_housekeep_artificer', array('uniacid' => $_W['uniacid'], 'mealid' => $val['id']), array('COUNT(id)')));
        }
        $pager = wl_pagination($total, $pindex, $psize);
        include wl_template('keeptype/mealLists');
    }

    //套餐编辑
    public function mealEdit() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);

        if ($_W['ispost']) {
            $data = $_GPC['item'];
            $data['price'] = sprintf("%.2f",$data['price']);
            if (!empty($id)) {
                pdo_update('wlmerchant_housekeep_meals', $data, array('id' => $id));
            } else {
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                pdo_insert('wlmerchant_housekeep_meals', $data);
            }
            wl_message('编辑套餐成功', web_url('housekeep/KeepType/mealLists'), 'success');
        }

        if (!empty($id)) {
            $item = pdo_get('wlmerchant_housekeep_meals', array('id' => $id));
        } else {
            $item = ['sort' => 100, 'status' => 1];
        }
        include wl_template('keeptype/mealEdit');
    }

    //套餐删除
    public function mealDel() {
        global $_W, $_GPC;
        $id = $_GPC['id'] ?: $_GPC['ids'];

        $items = pdo_getall('wlmerchant_housekeep_meals', array('id' => $id, 'uniacid' => $_W['uniacid']), array('id'));
        foreach ($items as $item) {
            pdo_delete('wlmerchant_housekeep_meals', array('id' => $item['id']));
        }

        show_json(1, array('url' => referer()));
    }

    //修改套餐状态
    public function mealStatus() {
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'] ?: 0;
        $res = pdo_update("wlmerchant_housekeep_meals", ['status' => $status], ['id' => $id]);
        if ($res) {
            Commons::sRenderSuccess('修改成功');
        } else {
            Commons::sRenderError('修改失败，请刷新重试!');
        }
    }

    public function orderlists(){
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;

        $where = array();
        $where['uniacid'] = $_W['uniacid'];
        $where['aid'] = $_W['aid'];
        $where['plugin'] = 'housekeep';
        if(empty($_GPC['status'])){
            $where['status!='] = 5;
        }else if($_GPC['status'] == 8){
            $where['status'] = 0;
        }else{
            $where['status'] = $_GPC['status'];
        }

        if($_GPC['orderid'] > 0){
            $where['id'] = $_GPC['orderid'];
        }

        if($_GPC['fightstatus']){
            $where['fightstatus'] = $_GPC['fightstatus'];
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
                    $where['mid#'] = $mids;
                }
            }else if($_GPC['keywordtype'] == 2){
                $params[':name'] = "%{$keyword}%";
                $members = pdo_fetchall("SELECT * FROM ".tablename('wlmerchant_member')." WHERE uniacid = {$_W['uniacid']} AND mobile LIKE :name",$params);
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
                    $where['mid#'] = $mids;
                }
            }
        }

        if($_GPC['time_limit']){
            $time_limit = $_GPC['time_limit'];
            $starttime = strtotime($_GPC['time_limit']['start']);
            $endtime = strtotime($_GPC['time_limit']['end']) ;
            $where['paytime>'] = $starttime;
            $where['paytime<'] = $endtime+86400;
        }
        if (empty($starttime) || empty($endtime)) {
            $starttime = strtotime('-1 month');
            $endtime = time();
        }

        $payonlinelist = Util::getNumData('*','wlmerchant_order',$where,'paytime DESC',$pindex,$psize,1);
        $pager = $payonlinelist[1];
        $list = $payonlinelist[0];
        foreach ($list as $key => &$li) {
            $member = pdo_get('wlmerchant_member',array('id' => $li['mid']),array('avatar','nickname'));
            $li['avatar'] = tomedia($member['avatar']);
            $li['nickname'] = $member['nickname'];
            if($li['fightstatus'] == 1){
                $li['goodsname'] = pdo_getcolumn(PDO_NAME.'housekeep_service',array('id'=>$li['fkid']),'title');
            }else if($li['fightstatus'] == 2){
                $li['goodsname'] = pdo_getcolumn(PDO_NAME.'housekeep_meals',array('id'=>$li['specid']),'name');
            }else if($li['fightstatus'] == 3){
                $typetitle = pdo_getcolumn(PDO_NAME.'housekeep_type',array('id'=>$li['specid']),'name');
                $li['goodsname'] = '发布['.$typetitle.']需求';
            }else if($li['fightstatus'] == 4){
                $li['goodsname'] = '置顶需求'.$li['num'].'天';
            }else if($li['fightstatus'] == 5){
                $li['goodsname'] = '刷新需求';
            }

            $li['paytime'] = date('Y-m-d H:i:s',$li['paytime']);
        }
        include  wl_template('keeptype/order_lists');
    }

    public function finish(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $res = pdo_update('wlmerchant_order',array('status' => 2,'retype' => 3),array('id' => $id,'status' => 1));
        if($res){
            Housekeep::settlementOrder($id); //结算
            show_json(1, "操作成功");
        }else{
            show_json(1, "操作失败,请刷新重试");
        }
    }

    public function refund(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $res = Housekeep::refund($id);
        if($res){
            show_json(1, "操作成功");
        }else{
            show_json(1, "操作失败,请刷新重试");
        }
    }

}
