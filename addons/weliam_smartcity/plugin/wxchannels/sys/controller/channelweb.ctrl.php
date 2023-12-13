<?php
defined('IN_IA') or exit('Access Denied');
set_time_limit(0);

class Channelweb_WeliamController
{

    /**
     * Comment: 类目列表
     * Author: wlf
     * Date: 2022/05/30 15:51
     */
    public function category(){
        global $_W, $_GPC;

        $name = $_GPC['name'];
        $authority = $_GPC['authority'];

        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;
        $where = 'WHERE third_cat_id > 0 AND uniacid = '.$_W['uniacid'];
        if ($name) {
            $where .= " AND ( third_cat_name LIKE '%{$name}%' OR second_cat_name LIKE '%{$name}%'  OR  first_cat_name LIKE '%{$name}%')";
        }

        if($authority == 1){
            $where .= " AND authority = 1 ";
        }else if($authority == 2){
            $where .= " AND authority = 0 ";
        }

        $sql = "SELECT * FROM " . tablename(PDO_NAME . "channels_cate")
            . " {$where}  ORDER BY third_cat_id ASC";
        $total = count(pdo_fetchall($sql));
        $sql .= " limit " . ($pindex - 1) * $psize . ',' . $psize;
        $list = pdo_fetchall($sql);

        $pager = wl_pagination($total, $pindex, $psize);



        include wl_template('wxchannels/category');

    }

    /**
     * Comment: 导入类目列表
     * Author: wlf
     * Date: 2022/05/30 16:03
     */
    public function getcategory(){
        global $_W, $_GPC;
        //通过接口获取数据
        $AccessInfo = Wxchannels::getCateList();
        if($AccessInfo['errcode'] > 0){
            show_json(1,$AccessInfo['errmsg']);
        }else{
            pdo_delete('wlmerchant_channels_cate',array('uniacid'=>$_W['uniacid']));
            //导入
            $list = $AccessInfo['third_cat_list'];
            foreach ($list as $li){
                $li['scene_group_list'] = serialize($li['scene_group_list']);
                $li['uniacid'] = $_W['uniacid'];
                pdo_insert(PDO_NAME . 'channels_cate', $li);
            }
            show_json(0);
        }

    }

    /**
     * Comment: 校验授权类目
     * Author: wlf
     * Date: 2022/05/31 10:20
     */
    public function getauthority(){
        global $_W, $_GPC;
        $authorityInfo = Wxchannels::getAuthority();
        if($authorityInfo['errcode'] > 0){
            show_json(1,$authorityInfo['errmsg']);
        }else{
            pdo_update('wlmerchant_channels_cate',array('authority' => 0),['uniacid' => $_W['uniacid']]);
            //导入
            $list = $authorityInfo['data'];
            foreach ($list as $li){
                pdo_update('wlmerchant_channels_cate',array('authority' => 1),array('third_cat_id' => $li['third_cat_id'],'uniacid'=>$_W['uniacid']));
            }
            show_json(0);
        }

    }

    /**
     * Comment: 上传商品至交易组件
     * Author: wlf
     * Date: 2022/05/31 14:20
     */
    public function addgoodsmodal(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $type = $_GPC['type'];
        $product_id = $_GPC['product_id'];
        $third_cat_id = $_GPC['third_cat_id'];

        include wl_template('wxchannels/addgoodsmodal');
    }

    /**
     * Comment: 上传商品至交易组件
     * Author: wlf
     * Date: 2022/05/31 14:40
     */
    public function addgoodsapi(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $third_cat_id = $_GPC['third_cat_id'];
        $type = $_GPC['type'];

        $product_id = $_GPC['product_id'];
        if($type == 'rush'){
            $typenum = 1;
            $goodsidfo = pdo_get('wlmerchant_rush_activity',array('id' => $id),array('id','name','detail','price','oldprice','num','thumb','thumbs'));

        }else if($type == 'groupon'){
            $typenum = 2;
            $goodsidfo = pdo_get('wlmerchant_groupon_activity',array('id' => $id),array('id','name','detail','price','oldprice','num','thumb','thumbs'));

        }
        $brand_id = '2100000000';
        $desc_info['desc'] = $goodsidfo['detail'];
        $thumbs = unserialize($goodsidfo['thumbs']);
        if(!empty($thumbs)){
            foreach ($thumbs as $thu){
                $desc_info['imgs'][] = Wxchannels::uploadImg(tomedia($thu),1);
            }
        }
        $thumb = Wxchannels::uploadImg(tomedia($goodsidfo['thumb']),1);
        if(empty($thumb)){
            show_json(0,'图片上传错误，请刷新重试');
        }
        $out_product_id = $type.$id;
        $skus = [
            'out_product_id' => $out_product_id,
            'out_sku_id'     => $out_product_id,
            'thumb_img'      => $thumb,
            'sale_price'     => $goodsidfo['price'] * 100,
            'market_price'   => $goodsidfo['oldprice'] * 100,
            'stock_num'      => $goodsidfo['num'],
            'sku_attrs'      => ['attr_key' => '无规格','attr_value' => '通用']
        ];
        $data = [
            'out_product_id' => $out_product_id,
            'title'          => $goodsidfo['name'],
            'path'           => 'pages/subPages/goods/index?type=1&goodsType='.$typenum.'&id='.$id,
            'direct_path'    => 'pages/subPages/goods/index?type=1&goodsType='.$typenum.'&id='.$id,
            'head_img'       => [$thumb],
            'desc_info'      => $desc_info,
            'third_cat_id'   => $third_cat_id,
            'brand_id'       => $brand_id,
            'skus'           => [$skus],
            'scene_group_list' => [1]
        ];

        if($product_id > 0){
            $Info = Wxchannels::updateGoods($data);
            if($Info['errcode'] > 0){
                show_json(0,$Info['errmsg']);
            }else{
                show_json(1);
            }
        }else{
            $Info = Wxchannels::uploadGoods($data);

            if($Info['errcode'] > 0){
                show_json(0,$Info['errmsg']);
            }else{
                //导入
                $goodsdata['goodsid'] = $id;
                $goodsdata['product_id'] = $Info['data']['product_id'];
                $goodsdata['createtime'] = time();
                $goodsdata['skus'] = serialize($Info['data']['skus']);
                $goodsdata['type'] = $typenum;
                $goodsdata['uniacid'] = $_W['uniacid'];
                $goodsdata['third_cat_id'] = $third_cat_id;
                pdo_insert(PDO_NAME . 'channels_goods', $goodsdata);
                show_json(1);
            }
        }

    }

    /**
     * Comment: 商品列表
     * Author: wlf
     * Date: 2022/06/07 11:20
     */
    public function goodslist(){
        global $_W, $_GPC;

        $pindex = max(1, intval($_GPC['page']));
        $psize = 10;

        $data = array();
        $data['uniacid'] = $_W['uniacid'];

        $info = Util::getNumData('*', PDO_NAME . 'channels_goods', $data, 'ID DESC', $pindex, $psize, 1);
        $list = $info[0];
        $pager = $info[1];

        foreach ($list as &$st){
            $channelsinfo = Wxchannels::getGoods($st['product_id']);
            if($channelsinfo['errcode'] > 0){
                $st['error'] = $channelsinfo['errmsg'];
            }else{
                $st['title'] = $channelsinfo['spu']['title'];
                $st['thumb'] = tomedia($channelsinfo['spu']['head_img'][0]);
                $st['status'] = $channelsinfo['spu']['status'];
                $st['edit_status'] = $channelsinfo['spu']['edit_status'];
                $st['catinfo'] = pdo_get(PDO_NAME.'channels_cate',array('third_cat_id'=>$channelsinfo['spu']['third_cat_id']),['third_cat_name','second_cat_name','first_cat_name']);
                $st['reject_reason'] = $channelsinfo['spu']['audit_info']['reject_reason'];
                $st['create_time'] = $channelsinfo['spu']['create_time'];
                $st['update_time'] = $channelsinfo['spu']['update_time'];
                $st['sealprice'] = $channelsinfo['spu']['skus'][0]['sale_price']/100;
                $st['stock_num'] = $channelsinfo['spu']['skus'][0]['stock_num'];

            }

        }

        include wl_template('wxchannels/goodslist');

    }

    /**
     * Comment: 商品操作
     * Author: wlf
     * Date: 2022/06/07 15:05
     */
    public function changeegoods(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'];

        if($status == 1){ //上架
            $channelsinfo = Wxchannels::listingGoods($id);
        }else if($status == 2){ //下架
            $channelsinfo = Wxchannels::delistingGoods($id);
        }else if($status == 3 ){ //删除
            $channelsinfo = Wxchannels::deteleGoods($id);
        }else if($status == 4 ){ //撤回审核
            $channelsinfo = Wxchannels::delAuditGoods($id);
        }

        if($channelsinfo['errcode'] > 0){
            show_json(0,$channelsinfo['errmsg']);
        }else{
            show_json(1);
        }
    }

    /**
     * Comment: 快速更新操作
     * Author: wlf
     * Date: 2022/06/09 15:05
     */
    public function updatemodal(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $channelsinfo = Wxchannels::getGoods($id);
        $channelsinfo = $channelsinfo['spu'];
        $goods = pdo_get(PDO_NAME . 'channels_goods',array('product_id' => $id));
        $sealprice = $channelsinfo['skus'][0]['sale_price']/100;
        $market_price = $channelsinfo['skus'][0]['market_price']/100;
        $stock_num = $channelsinfo['skus'][0]['stock_num'];


        include wl_template('wxchannels/updatemodal');
    }

    /**
     * Comment: 更新操作
     * Author: wlf
     * Date: 2022/06/09 15:50
     */

    public function updategoodsapi(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $out_sku_id = $_GPC['out_sku_id'];
        $sealprice = sprintf("%.2f",$_GPC['sealprice'])*100;
        $market_price = sprintf("%.2f",$_GPC['market_price'])*100;
        $stock_num = intval($_GPC['stock_num']);

        $data = ['product_id' => $id];
        $skuarray = [
            'out_sku_id' => $out_sku_id,
            'sale_price' => $sealprice,
            'market_price' => $market_price,
            'stock_num' => $stock_num,
        ];
        $data['skus'][] = $skuarray;

        $channelsinfo = Wxchannels::AuditGoods($data);

        if($channelsinfo['errcode'] > 0){
            show_json(0,$channelsinfo['errmsg']);
        }else{
            show_json(1);
        }

    }

    /**
     * Comment: 订单列表
     * Author: wlf
     * Date: 2022/06/15 16:02
     */
    public function orderlist(){
        global $_W, $_GPC;
        $pindex = max(1, intval($_GPC['page']));
        $orderlist = Wxchannels::getOrderList($pindex);
        if($orderlist['errcode'] > 0){
            wl_message($orderlist['errmsg']);
        }else{
            $list = $orderlist['orders'];

            foreach ($list as &$lil){
                $member = pdo_get('wlmerchant_member',array('wechat_openid' => $lil['openid']),array('nickname','avatar','encodename'));
                $lil['nickname'] = empty($member['encodename']) ? $member['nickname'] : base64_decode($member['encodename']);
                $lil['avatar'] = tomedia($member['avatar']);


            }

            $allnum = $orderlist['total_num'];
            $pager = wl_pagination($allnum, $pindex, 20);
        }

        include wl_template('wxchannels/orderlist');

    }

    /**
     * Comment: 订单关闭
     * Author: wlf
     * Date: 2022/06/16 10:22
     */
    public function closeOrder(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $openid = $_GPC['openid'];
        $data = [
            'order_id' => $id,
            'openid' => $openid
        ];
        $channelsinfo = Wxchannels::closeOrder($data);
        if($channelsinfo['errcode'] > 0){
            show_json(0,$channelsinfo['errmsg']);
        }else{
            show_json(1);
        }

    }

    /**
     * Comment: 订单发货
     * Author: wlf
     * Date: 2022/06/16 13:22
     */
    public function sendDelivery(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $openid = $_GPC['openid'];
        //获取订单数据
        $data = [
            'order_id' => $id,
            'openid' => $openid
        ];
        $channelsinfo = Wxchannels::getOrderInfo($data);
        if($channelsinfo['errcode'] > 0){
            show_json(0,$channelsinfo['errmsg']);
        }
        $order = $channelsinfo['order'];
        $address = $order['address_info'];
        //获取快递数据
        $companyList = Wxchannels::getCompanyList();

        if($_W['ispost']){
            $delivery_id = trim($_GPC['delivery_id']);
            $waybill_id = trim($_GPC['waybill_id']);
            if(empty($delivery_id) || empty($waybill_id) ){
                wl_message('选择快递公司并输入快递单号!');
            }

            $data = [
                'order_id' => $id,
                'openid' => $openid,
                'finish_all_delivery' => 1,
                'ship_done_time' => date('Y-m-d H:i:s',time()),
            ];

            $product_info_list = [
                'out_product_id' => $order['order_detail']['product_infos'][0]['out_product_id'],
                'out_sku_id' => $order['order_detail']['product_infos'][0]['out_sku_id'],
                'product_cnt' => $order['order_detail']['product_infos'][0]['product_cnt']
            ];

            $deliveryinfo = [
                'delivery_id' => $delivery_id,
                'waybill_id' => $waybill_id,
            ];
            $deliveryinfo['product_info_list'][] = $product_info_list;

            $data['delivery_list'][] = $deliveryinfo;

            $sendres = Wxchannels::sendDelivery($data);
            if($sendres['errcode'] > 0){
                wl_message($sendres['errmsg']);
            }else{
                wl_message('发货成功！',web_url('wxchannels/channelweb/orderlist'),'success');
            }
        }
        include wl_template('wxchannels/sendDeliveryModal');
    }


    /**
     * Comment: 订单确认收货
     * Author: wlf
     * Date: 2022/06/16 15:31
     */
    public function recieveOrder(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $openid = $_GPC['openid'];
        $data = [
            'order_id' => $id,
            'openid' => $openid
        ];
        $channelsinfo = Wxchannels::recieveOrder($data);
        if($channelsinfo['errcode'] > 0){
            show_json(0,$channelsinfo['errmsg']);
        }else{
            show_json(1);
        }

    }

    /**
     * Comment: 售后列表
     * Author: wlf
     * Date: 2022/06/16 16:40
     */
    public function ecaftersalelist(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $data = [
            'order_id' => $id,
            'offset' => 0,
            'limit' => 50
        ];

        $orderlist = Wxchannels::getEcaftersaleList($data);
        if($orderlist['errcode'] > 0){
            wl_message($orderlist['errmsg']);
        }else{
            $list = $orderlist['after_sales_orders'];
            foreach ($list as &$lil){
                $lil = Wxchannels::getEcaftersaleInfo($lil)['after_sales_order'];
                $lil['refund_reason_type_text'] = Wxchannels::afterSalesReasonType($lil['refund_reason_type']);
                $lil['status_text'] = Wxchannels::afterSalesReasonStatus($lil['status']);
            }
        }

        include wl_template('wxchannels/ecaftersalelist');

    }

    /**
     * Comment: 修改售后信息
     * Author: wlf
     * Date: 2022/06/16 17:20
     */
    public function changeafter(){
        global $_W, $_GPC;
        $id = $_GPC['id'];
        $status = $_GPC['status'];
        if($status == 1){
            $channelsinfo = Wxchannels::acceptrefund($id);
        }else if($status == 2){
            $channelsinfo = Wxchannels::rejectEcaftersale($id);
        }
        if($channelsinfo['errcode'] != 0){
            show_json(0,$channelsinfo['errmsg']);
        }else{
            show_json(1);
        }

    }

    /**
     * Comment: 上传资质
     * Author: wlf
     * Date: 2022/06/16 14:20
     */
    public function upQualification(){
        global $_W, $_GPC;
        $third_cat_id = $_GPC['id'];
        $page = $_GPC['page'];
        $qualif = pdo_get('wlmerchant_channels_cate',array('third_cat_id' => $third_cat_id,'uniacid' => $_W['uniacid']),array('second_cat_id','first_cat_id','license','certificate'));
        $qualif['license'] = beautifyImgInfo($qualif['license']);
        $qualif['certificate'] = beautifyImgInfo($qualif['certificate']);


        if($_W['ispost']){
            $license = $_GPC['license'];
            if(empty($license)){
                wl_message('请上传营业执照或组织机构代码证');
            }
            foreach ($license as $lic){
                $lic = Wxchannels::uploadImg(tomedia($lic),1);
                $newlicense[] = $lic;
            }
            $certificate = $_GPC['certificate'];
            if(empty($certificate)){
                wl_message('请上传类目所需资质材料!');
            }
            foreach ($certificate as $cert){
                $cert = Wxchannels::uploadImg(tomedia($cert),1);
                $newcertificate[] = $cert;
            }

            $data['audit_req'] = [
                'license' => $newlicense,
                'category_info' => [
                    'level1' => $qualif['first_cat_id'],
                    'level2' => $qualif['second_cat_id'],
                    'level3' => $third_cat_id,
                    'certificate' => $newcertificate
                ],
                'scene_group_list' => [1]
            ];

            $res = Wxchannels::upQualification($data);
            if($res['errcode'] != 0){
                wl_message($res['errmsg']);
            }else{
                $newinfo['license'] = serialize($license);
                $newinfo['certificate'] = serialize($certificate);
                $newinfo['audit_id'] = $res['audit_id'];
                pdo_update('wlmerchant_channels_cate',$newinfo,array('third_cat_id' => $third_cat_id,'uniacid'=>$_W['uniacid']));
                wl_message('资质上传成功！',web_url('wxchannels/channelweb/category',['page' => $page]),'success');
            }
        }

        include wl_template('wxchannels/upQualification');
    }

    /**
     * Comment: 查询资质审核情况
     * Author: wlf
     * Date: 2022/06/27 15:06
     */
    public function auditResult(){
        global $_W, $_GPC;
        $audit_id = $_GPC['audit_id'];
        $channelsinfo = Wxchannels::auditResult($audit_id);
        if($channelsinfo['errcode'] != 0){
            show_json(0,$channelsinfo['errmsg']);
        }else{
            $res = $channelsinfo['data'];
            if($res['status'] == 0){
                show_json(0,'微信还在审核中');
            }else if($res['status'] == 1){
                pdo_update('wlmerchant_channels_cate',['audit_id' => '','authority' => 1],array('audit_id' => $audit_id));
                show_json(1,'资质通过审核');
            }else {
                show_json(0,$res['reject_reason']);
            }
        }
    }
}