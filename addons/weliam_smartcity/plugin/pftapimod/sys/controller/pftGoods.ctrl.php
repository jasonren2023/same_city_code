<?php
defined('IN_IA') or exit('Access Denied');

class pftGoods_WeliamController {
    /**
     * Comment: 票付通商品列表
     * Author: wlf
     * Date: 2021/07/29 16:37
     */
    public function pftGoodsList(){
        global $_W,$_GPC;
        $page = $_GPC['page'] ? : 1;
        $n = ($page - 1) * 20;
        $m = 20;
        $list = Pftapimod::getGoodsList($n,$m);
        if($list['error'] > 0){
            $msg = $list['msg'];
        }else{
            $total = Cache::getCache('pft_goods_total',$_W['uniacid']);
            if(empty($total)){
                $all = Pftapimod::getGoodsList(0,1000);
                $total = count($all);
                if($total > 0){
                    Cache::setCache('pft_goods_total',$_W['uniacid'],$total);
                }
            }
            $pager = wl_pagination($total, $page, $m);
        }

        include wl_template("pftGoods/pftGoodsList");
    }


    /**
     * Comment: 票付通商品获取门票信息
     * Author: wlf
     * Date: 2021/09/13 09:44
     */
    public function importGoodsModel(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        if($id > 0){
            //获取门票信息
            $tickets = Pftapimod::getTicketDetail($id);

            include wl_template("pftGoods/importGoodsModel");
        }else{
            show_json(1, '参数错误，请刷新重试');
        }

    }

    /**
     * Comment: 票付通商品导入
     * Author: wlf
     * Date: 2021/08/25 09:50
     */
    public function importGoods(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $lid = $_GPC['lid'];
        $plugin = $_GPC['plugin'];

        if($id > 0 && $lid > 0){
            //导入票付通商品
            $pftGoods = Pftapimod::getGoodsDetail($lid);
            if(!empty($pftGoods['UUerrorcode'])){
                Util::wl_log('pftApi.log',PATH_DATA,$pftGoods); //写入异步日志记录
                show_json(1, $pftGoods['UUerrorinfo']);
            }
            $tickets = Pftapimod::getTicketDetail($lid,$id);
            if(!empty($tickets['UUerrorcode'])){
                Util::wl_log('pftApi.log',PATH_DATA,$tickets); //写入异步日志记录
                show_json(1, $tickets['UUerrorinfo']);
            }
            $tickets = $tickets[0];
            $otherinfo = [
                'UUtourist_info' => $tickets['UUtourist_info'],
                'UUbuy_limit'    => $tickets['UUbuy_limit'],
                'UUdelaytype'    => $tickets['UUdelaytype'],
                'UUuse_early_days' => $tickets['UUuse_early_days'],
                'UUorder_end'    => $tickets['UUorder_end'],
                'UUorder_start'    => $tickets['UUorder_start'],
            ];
            $otherinfo = serialize($otherinfo);
            $goods = [
                'pftid' => $lid,
                'ticketid' => $id,
                'name' => $pftGoods['UUtitle'].':'.$tickets['UUtitle'],
                'thumb' => $pftGoods['UUimgpath'],
                'detail' => $pftGoods['UUbhjq'],
                'describe' => $pftGoods['UUjqts'],
                'price'    => $tickets['UUtprice'],
                'pftotherinfo' => $otherinfo
            ];
            if($plugin == 1){
                $goods['aid'] = $_W['aid'];
                $goodsid = Rush::saveRushActive($goods);
                $optype = 1;
            }else if($plugin == 2){
                $goods['aid'] = $_W['aid'];
                $goodsid = Groupon::savegrouponActive($goods);
                $optype = 3;
            }else if($plugin == 3){
                $goods['aid'] = $_W['aid'];
                $goodsid = Wlfightgroup::saveGoods($goods);
                $optype = 2;
            }
            if(empty($goodsid)){
                show_json(1, '导入失败，请刷新重试');
            }
            //导入门票类型 多规格
//            $tickets = Pftapimod::getTicketDetail($id);
//            if(!empty($tickets['UUerrorcode'])){
//                Util::wl_log('pftApi.log',PATH_DATA,$tickets); //写入异步日志记录
//                show_json(1, $tickets['UUerrorinfo']);
//            }
//            $specinfo = [
//                'uniacid' => $_W['uniacid'],
//                'goodsid' => $goodsid,
//                'title' => '票种',
//                'type'  => $optype
//            ];
//            pdo_insert('wlmerchant_goods_spec' , $specinfo);
//            $spec_id = pdo_insertid();
//
//            foreach ($tickets as $tick){
//                //子规格
//                $spec_item = [
//                    'uniacid'      => $_W['uniacid'] ,
//                    'specid'       => $spec_id ,
//                    'displayorder' => 0 ,
//                    'title'        => $tick['UUtitle'] ,
//                    'show'         => 1,
//                ];
//                pdo_insert('wlmerchant_goods_spec_item' , $spec_item);
//                $item_id = pdo_insertid();
//                $itemids[] = $item_id;
//                //规格项牧
//                $optioninfo = [
//                    'uniacid'      => $_W['uniacid'] ,
//                    'title'        => $tick['UUtitle'],
//                    'price'        => $tick['UUtprice'] ,
//                    'goodsid'      => $goodsid ,
//                    'specs'        => $item_id ,
//                    'type'         => $optype,
//                    'uuid'         => $tick['UUid']
//                ];
//                pdo_insert('wlmerchant_goods_option' , $optioninfo);
//                $option_id = pdo_insertid();
//            }
//            pdo_update('wlmerchant_goods_spec' , ['content' => serialize($itemids)] , ['id' => $spec_id]);
            die(json_encode(array('status'=>0,'id'=>$goodsid)));
        }
        show_json(1, '导入失败，请刷新重试');
    }

    /**
     * Comment: 票付通商品导入
     * Author: wlf
     * Date: 2021/08/25 09:50
     */
    public function importOptionGoods(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'];
        $lid = $_GPC['lid'];
        $plugin = $_GPC['plugin'];

        if(!empty($ids) && $lid > 0){
            $pftGoods = Pftapimod::getGoodsDetail($lid);
            if(!empty($pftGoods['UUerrorcode'])){
                Util::wl_log('pftApi.log',PATH_DATA,$pftGoods); //写入异步日志记录
                show_json(1, $pftGoods['UUerrorinfo']);
            }
            foreach ($ids as $tid){
                $ticket = Pftapimod::getTicketDetail($lid,$tid);
                if(!empty($ticket['UUerrorcode'])){
                    Util::wl_log('pftApi.log',PATH_DATA,$ticket); //写入异步日志记录
                    show_json(1, $ticket['UUerrorinfo']);
                }else{
                    $tickets[] = $ticket[0];
                }
            }
            $goods = [
                'pftid' => $lid,
                'ticketid' => 0,
                'name' => $pftGoods['UUtitle'],
                'thumb' => $pftGoods['UUimgpath'],
                'detail' => $pftGoods['UUbhjq'],
                'describe' => $pftGoods['UUjqts'],
                'price'    => $tickets[0]['UUtprice'],
                'optionstatus' => 1
            ];
            if($plugin == 1){
                $goods['aid'] = $_W['aid'];
                $goodsid = Rush::saveRushActive($goods);
                $optype = 1;
            }else if($plugin == 2){
                $goods['aid'] = $_W['aid'];
                $goodsid = Groupon::savegrouponActive($goods);
                $optype = 3;
            }

            $specinfo = [
                'uniacid' => $_W['uniacid'],
                'goodsid' => $goodsid,
                'title' => '票种',
                'type'  => $optype
            ];
            pdo_insert('wlmerchant_goods_spec' , $specinfo);
            $spec_id = pdo_insertid();

            foreach ($tickets as $tick){
                //子规格
                $spec_item = [
                    'uniacid'      => $_W['uniacid'] ,
                    'specid'       => $spec_id ,
                    'displayorder' => 0 ,
                    'title'        => $tick['UUtitle'] ,
                    'show'         => 1,
                ];
                pdo_insert('wlmerchant_goods_spec_item' , $spec_item);
                $item_id = pdo_insertid();
                $itemids[] = $item_id;
                //规格项牧
                $otherinfo = [
                    'UUtourist_info' => $tick['UUtourist_info'],
                    'UUbuy_limit'    => $tick['UUbuy_limit'],
                    'UUdelaytype'    => $tick['UUdelaytype']
                ];
                $otherinfo = serialize($otherinfo);
                $optioninfo = [
                    'uniacid'      => $_W['uniacid'] ,
                    'title'        => $tick['UUtitle'],
                    'price'        => $tick['UUtprice'] ,
                    'goodsid'      => $goodsid ,
                    'specs'        => $item_id ,
                    'type'         => $optype,
                    'uuid'         => $tick['UUid'],
                    'pftotherinfo' => $otherinfo
                ];
                pdo_insert('wlmerchant_goods_option' , $optioninfo);
                $option_id = pdo_insertid();
            }
            pdo_update('wlmerchant_goods_spec' , ['content' => serialize($itemids)] , ['id' => $spec_id]);
            die(json_encode(array('status'=>0,'id'=>$goodsid)));
        }
        show_json(1, '导入失败，请刷新重试');
    }

    /**
     * Comment: 亿奇达商品列表
     * Author: wlf
     * Date: 2022/03/03 16:37
     */
    public function yqdGoodsList()
    {
        global $_W, $_GPC;
        $page = $_GPC['page'] ?: 1;
        $listdata = Pftapimod::getYqdGoodList($page);

        if($listdata['code'] != '200'){
            $msg = $listdata['msg'];
        }else{
            $list = $listdata['data'];
            $pager = wl_pagination($listdata['sum'], $page, 20);
        }

        include wl_template("pftGoods/yqdGoodsList");
    }

    /**
     * Comment: 亿奇达商品导入
     * Author: wlf
     * Date: 2022/03/07 13:46
     */
    public function importYqdGoods(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $plugin = $_GPC['plugin'];
        if($id > 0){
            //获取商品信息
            $tickets = Pftapimod::getYqdGoodInfo($id);
            if($tickets['code'] != '200'){
                show_json(1, $tickets['msg']);
            }
            $tickets = $tickets['data'];
            $otherinfo = [
                'template' => $tickets['template'],
                'price'    => $tickets['price'],
                'guidePrice'    => $tickets['guidePrice'],
                'limitCountMax' => $tickets['limitCountMax'],
                'limitCountMin'    => $tickets['limitCountMin'],
                'delivery'    => $tickets['delivery'],
            ];
            $otherinfo = serialize($otherinfo);
            $goods = [
                'pftid' => $tickets['mainId'],
                'ticketid' => $tickets['branchId'],
                'name' => $tickets['name'],
                'thumb' => $tickets['MainImg'],
                'detail' => $tickets['remarks'],
                'price'  => $tickets['guidePrice'],
                'pftotherinfo' => $otherinfo,
                'threestatus' => 1,
                'starttime' => strtotime($tickets['preSaleETime']),
                'endtime' => strtotime($tickets['preSaleSTime']),
                'num'    => $tickets['stockAmount'] ? : 99999
            ];
            if($plugin == 'rush'){
                $goods['aid'] = $_W['aid'];
                $goodsid = Rush::saveRushActive($goods);
            }else if($plugin == 'groupon'){
                $goods['aid'] = $_W['aid'];
                $goodsid = Groupon::savegrouponActive($goods);
            }
            if(empty($goodsid)){
                show_json(1, '导入失败，请刷新重试');
            }
            die(json_encode(array('status'=>0,'id'=>$goodsid)));
        }else{
            show_json(1, '参数错误，请刷新重试');
        }
    }


}