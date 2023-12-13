<?php
defined('IN_IA') or exit('Access Denied');

/**
 * Comment: 奖品管理
 * Author: zzw
 * Class Goods_WeliamController
 */
class Goods_WeliamController {
    /**
     * Comment: 获取奖品信息列表
     * Author: zzw
     * Date: 2020/9/15 9:45
     */
    public function goodsIndex(){
        global $_W,$_GPC;
        //参数获取
        $page = max(1, intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $title = $_GPC['title'] ? : '';//奖品名称
        $type = intval($_GPC['type']) ? : 0;//奖品类型:1=现金红包,2=线上红包,3=积分,4=激活码,5=商品
        $status = intval($_GPC['status']) ? : 0;//状态:1=开启，2=关闭
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if($title) $where .= " AND title LIKE '%{$title}%' ";
        if($type > 0) $where .= " AND type = {$type} ";
        if($status > 0) $where .= " AND status = {$status} ";
        //sql语句生成
        $field = "id,title,type,image,probability,status,create_time,day_number,total_number";
        $sql = "SELECT {$field} FROM ".tablename(PDO_NAME."draw_goods");
        $order = " ORDER BY create_time DESC,id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        $startTime = strtotime(date("Y-m-d",time())." 00:00:00");
        $endTime = strtotime(date("Y-m-d",time())." 23:59:59");
        foreach($list as $item => &$val){
            $sWhere = ['draw_goods_id'=>$val['id']];
            //总统计信息获取  sales = 已抽中，surplus = 剩余，number = 总数量
            $val['total_sales'] = pdo_count(PDO_NAME."draw_record",$sWhere);
            if($val['total_number'] > 0) $val['total_surplus'] = $val['total_number'] - intval($val['total_sales']);
            //当天统计信息获取  已抽中/剩余/总数量
            $sWhere['create_time >='] = $startTime;
            $sWhere['create_time <='] = $endTime;
            $val['day_sales'] = pdo_count(PDO_NAME."draw_record",$sWhere);
            if($val['day_number'] > 0) $val['day_surplus'] = $val['day_number'] - intval($val['day_sales']);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include  wl_template('goods/index');
    }
    /**
     * Comment: 添加奖品信息
     * Author: zzw
     * Date: 2020/9/14 17:20
     */
    public function goodsAdd(){
        global $_W,$_GPC;
        if ($_W['ispost']) {
            //基本信息获取 并且进行处理  奖品类型:1=现金红包,2=线上红包,3=积分,4=激活码,5=商品
            $data                = $_GPC['data'];
            $data['uniacid']     = $_W['uniacid'];
            $data['aid']         = $_W['aid'];
            $data['create_time'] = time();
            //判断是否存在对应类型的必须条件
            if ($data['type'] == 1 && ($data['prize_number'] <= 0 || !$data['prize_number'])) wl_message('请填写红包金额！' , referer() , 'error');
            if ($data['type'] == 2 && !$data['red_pack_id']) wl_message('请选择红包！' , referer() , 'error');
            if ($data['type'] == 3 && ($data['prize_number'] <= 0 || !$data['prize_number'])) wl_message('请填写积分数量！' , referer() , 'error');
            if ($data['type'] == 4 && !$data['code_keyword']) wl_message('请选择一个激活码序列！' , referer() , 'error');
            if ($data['type'] == 5 && !$data['goods_id']) wl_message('请选择一个商品！' , referer() , 'error');
            //判断是否已经存在同名称的奖品
            $isHave = pdo_get(PDO_NAME . "draw_goods" , ['title' => $data['title'],'uniacid' => $_W['uniacid'],'aid' => $_W['aid']]);
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
            $res = pdo_insert(PDO_NAME . "draw_goods" , $data);
            if ($res) wl_message('添加成功' , web_url('draw/goods/goodsIndex') , 'success');
            else wl_message('添加失败，请刷新重试！' , referer() , 'error');
        }
        //获取激活码分组信息
        $codeList = Halfcard::getGroupList();

        include  wl_template('goods/add');
    }
    /**
     * Comment: 编辑奖品信息
     * Author: zzw
     * Date: 2020/9/15 11:21
     */
    public function goodsEdit(){
        global $_W,$_GPC;
        //参数信息获取
        $id = intval($_GPC['id']) OR wl_message('参数错误，请刷新重试！' , referer() , 'error');;
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
            $isHave = pdo_get(PDO_NAME . "draw_goods" , ['title' => $data['title'],'id <>'=>$id]);
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
            $res = pdo_update(PDO_NAME . "draw_goods" , $data,['id'=>$id]);
            if ($res) wl_message('修改成功' , web_url('draw/goods/goodsIndex') , 'success');
            else wl_message('修改失败，请刷新重试！' , referer() , 'error');
        }
        //进入修改页面  获取当前奖品的基本信息
        $info = Draw::prizeInfo($id);
        //获取激活码分组信息
        $codeList = Halfcard::getGroupList();


        include  wl_template('goods/edit');
    }
    /**
     * Comment: 删除奖品信息
     * Author: zzw
     * Date: 2020/9/15 11:51
     */
    public function delete(){
        global $_W,$_GPC;
        //参数信息获取
        $ids = $_GPC['ids'] OR show_json(0, '参数错误，请刷新重试!');
        //删除内容
        $res = pdo_delete(PDO_NAME."draw_goods",['id IN'=>$ids]);
        if($res) show_json(1, '删除成功');
        else show_json(0, '删除失败，请刷新重试');
    }


}
