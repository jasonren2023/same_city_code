<?php
defined('IN_IA') or exit('Access Denied');

/**
 * Comment: 奖品管理
 * Author: wlf
 * Class Luckygoods_WeliamController
 */
class Luckygoods_WeliamController {
    /**
     * Comment: 获取奖品信息列表
     * Author: wlf
     * Date: 2021/12/01 11:40
     */
    public function goodsIndex(){
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
        $goodslist = Util::getNumData('*','wlmerchant_luckydraw_goods',$where,'id DESC',$page,$pageIndex,1);
        $pager = $goodslist[1];
        $list = $goodslist[0];
        //信息处理


        include  wl_template('luckygoods/index');
    }

    /**
     * Comment: 编辑奖品信息
     * Author: wlf
     * Date: 2021/12/01 11:46
     */
    public function goodsEdit(){
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
            $isHave = pdo_get(PDO_NAME . "luckydraw_goods" , ['title' => $data['title'],'id <>'=>$id,'uniacid' => $_W['uniacid']]);
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
                $res = pdo_update(PDO_NAME . "luckydraw_goods" , $data,['id'=>$id]);
            }else{
                $data['uniacid'] = $_W['uniacid'];
                $data['aid'] = $_W['aid'];
                $data['create_time'] = time();
                $res = pdo_insert(PDO_NAME . 'luckydraw_goods', $data);
            }
            if ($res) wl_message('操作成功' , web_url('luckydraw/luckygoods/goodsIndex') , 'success');
            else wl_message('操作失败，请刷新重试！' , referer() , 'error');
        }
        //进入修改页面  获取当前奖品的基本信息
        if($id > 0 ){
            $info = Luckydraw::prizeInfo($id);
        }else{
            $info = [
                'type' => 1
            ];
        }
        //获取激活码分组信息
        $codeList = Halfcard::getGroupList();


        include  wl_template('luckygoods/edit');
    }

    /**
     * Comment: 删除奖品信息
     * Author: wlf
     * Date: 2021/12/01 16:51
     */
    public function delete(){
        global $_W,$_GPC;
        //参数信息获取
        $ids = $_GPC['ids'] OR show_json(0, '参数错误，请刷新重试!');
        //删除内容
        $res = pdo_delete(PDO_NAME."luckydraw_goods",['id IN'=>$ids]);
        if($res) show_json(1, '删除成功');
        else show_json(0, '删除失败，请刷新重试');
    }


}