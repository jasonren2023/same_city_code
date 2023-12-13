<?php
defined('IN_IA') or exit('Access Denied');

class Goods_WeliamController {
    /**
     * Comment: 商品列表信息获取
     * Author: zzw
     * Date: 2020/10/26 15:07
     */
    public function goodsList(){
        global $_W,$_GPC;
        //基本参数信息获取
        $status    = $_GPC['status'] ? : 0;//商品状态，0：未审核。1：审核中，2：审核通过，3：审核驳回
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        //条件生成
        $where = ['uniacid'=>$_W['uniacid'],'audit_status'=>$status];
        if($_W['aid'] > 0) $where['aid'] = $_W['aid'];
        //信息获取
        list($total,$list) = Live::getGoodsParamsList($where,$page,$pageIndex);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template("goods/list");
    }
    /**
     * Comment: 直播商品提交审核
     * Author: zzw
     * Date: 2020/10/26 14:08
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function add(){
        global $_W,$_GPC;
        if($_W['ispost']){
            //信息获取
            $data = $_GPC['data'];
            if (!$data['goods_id']) wl_message("请选择商品！" , referer() , 'error');
            if (($data['priceType'] == 2 || $data['priceType'] == 3) && !$data['price'] && !$data['price2']) wl_message("请完善价格信息！" , referer(), 'error');
            //获取商品信息
            $goods = Live::getGoodsInfo($data['goods_id'],$data['goods_plugin']);
            //基本信息参数配置
            $params = [
                'coverImgUrl' => $data['coverImgUrl'] ? tomedia($data['coverImgUrl']) : '' ,//图片
                'name'        => $data['name'] ? : '' ,//商品名称
                'priceType'   => $data['priceType'] ,//价格类型  1：一口价；2：价格区间；3：显示折扣价
                'price'       => $data['price'] ? sprintf("%.2f" , $data['price']) : '' ,//价格一
                'price2'      => $data['price2'] ? sprintf("%.2f" , $data['price2']) : '' ,//价格二
                'url'         => '' ,//跳转链接
            ];
            //商品名称不存在 使用当前商品名称
            if(!$params['name']){
                $goodsName = $goods['goods_name'];
                if(mb_strlen($goodsName) > 14) $goodsName = mb_substr($goodsName,0,14,'utf-8');
                $params['name'] = $goodsName;
            }
            //价格一不存在  默认使用一口价 价格为商品当前价格
            if(!$params['price'] || $params['price'] <= 0 ){
                $params['priceType'] = intval(1);
                $params['price'] = sprintf("%.2f",$goods['price']);
            }
            //图片不存在 使用当前商品logo图片
            if(!$params['coverImgUrl']) {
                //判断图片信息  微信规则限制 图片大小不能大于300像素*300像素；
                list($width,$height) = getimagesize(tomedia($goods['logo']));//获取图片信息
                if($width > 300 || $height > 300 ) wl_message("商品logo图片过大，请自行上传图片!", referer() , 'error');
                //使用商品logo图片
                $params['coverImgUrl'] = tomedia($goods['logo']);
            }
            //获取跳转地址
            $params['url'] = Live::getWxAppPath($data['goods_id'],$data['goods_plugin']);
            //提交商品信息
            try{
                //判断图片信息  微信规则限制 图片大小不能大于300像素*300像素；
                list($width,$height) = getimagesize($params['coverImgUrl']);//获取图片信息
                if($width > 300 || $height > 300 ) wl_message("图片超出限制，宽高不能大于300像素!", referer() , 'error');
                //商品图片信息处理
                $coverImgUrl = $params['coverImgUrl'];
                $params['coverImgUrl'] =  (new Live())->uploadTemporaryMaterial($params['coverImgUrl']);
                //提交商品
                $res = (new Live())->submitGoods($params);
                //记录商品提交信息
                $data = [
                    'aid'          => $_W['aid'] ? : 0 ,
                    'uniacid'      => $_W['uniacid'] ,
                    'goods_id'     => $res['goodsId'] ,
                    'audit_id'     => $res['auditId'] ,
                    'wl_goods_id'  => $data['goods_id'] ,
                    'goods_plugin' => $data['goods_plugin'] ,
                    'title'        => $params['name'] ,
                    'goods_img'    => $coverImgUrl ,
                    'price_type'   => $params['priceType'] ,
                    'price'        => $params['price'] ,
                    'price2'       => $params['price2'] ? : '' ,
                ];
                pdo_insert(PDO_NAME."live_goods",$data);

                wl_message('提交成功,请等待审核结果！' , web_url('live/goods/goodsList',['status'=>1]) , 'success');
            }catch (Exception $e){
                wl_message($e->getMessage(), referer() , 'error');
            }
        }

        include wl_template("goods/add");
    }
    /**
     * Comment: 请求撤回正在审核中的商品
     * Author: zzw
     * Date: 2020/10/26 16:31
     */
    public function cancelAdd(){
        global $_W,$_GPC;
        //基本参数信息获取
        $id = $_GPC['id'] OR Commons::sRenderError("不存在的商品信息，请刷新重试!");
        //获取直播商品提交审核记录信息
        $info = pdo_get(PDO_NAME."live_goods",['goods_id'=>$id]);
        try{
            //申请撤回商品
            $params = [
                'auditId' => $info['audit_id'],
                'goodsId' => $info['goods_id']
            ];
            $res = (new Live())->withdrawGoods($params);
            //返回操作结果
            if($res['errcode'] == 0) {
                pdo_update(PDO_NAME."live_goods",['audit_status'=>0],['goods_id'=>$id]);
                Commons::sRenderSuccess("操作成功");
            }else Commons::sRenderError("操作失败");
        }catch (Exception $e){
            Commons::sRenderError($e->getMessage());
        }
    }
    /**
     * Comment: 请求让未审核的商品进行审核操作
     * Author: zzw
     * Date: 2020/10/27 9:41
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function reSubmit(){
        global $_W,$_GPC;
        //基本参数信息获取
        $id = $_GPC['id'] OR Commons::sRenderError("不存在的商品信息，请刷新重试!");
        try{
            //请求重新提交商品
            $res = (new Live())->reSubmitGoods($id);
            if($res['errcode'] == 0){
                //修改 提交审核记录
                pdo_update(PDO_NAME."live_goods"
                    ,['audit_id'=>$res['auditId'],'audit_status'=>1],['goods_id'=>$id]);

                Commons::sRenderSuccess("操作成功");
            }else {
                Commons::sRenderError("操作失败");
            }
        }catch (Exception $e){
            Commons::sRenderError($e->getMessage());
        }
    }
    /**
     * Comment: 请求删除商品
     * Author: zzw
     * Date: 2020/10/26 16:23
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function deleteLiveGoods(){
        global $_W,$_GPC;
        //基本参数信息获取
        $id = $_GPC['id'] OR Commons::sRenderError("不存在的商品信息，请刷新重试!");
        try{
            //请求删除商品
            $res = (new Live())->deleteGoods($id);
            if($res['errcode'] == 0){
                //商品删除成功  删除商品提交审核记录
                pdo_delete(PDO_NAME."live_goods",['goods_id'=>$id]);

                Commons::sRenderSuccess("操作成功");
            }else {
                Commons::sRenderError("操作失败");
            }
        }catch (Exception $e){
            Commons::sRenderError($e->getMessage());
        }
    }
    /**
     * Comment: 编辑商品信息
     * Author: zzw
     * Date: 2020/10/27 9:31
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function editGoods(){
        global $_W,$_GPC;
        //基本参数信息获取
        $status = $_GPC['status'] ? : 0;
        $goods_id = $_GPC['goods_id'] OR wl_message("商品不存在，请刷新重试！" ,web_url('live/goods/goodsList',['status'=>$status]), 'error');
        //商品信息获取
        $params = pdo_get(PDO_NAME."live_goods",['goods_id'=>$goods_id]);//直播商品
        $info = Live::getGoodsInfo($params['wl_goods_id'],$params['goods_plugin']);//平台商品
        //信息修改操作
        if($_W['ispost']){
            //参数信息获取
            $data = $_GPC['data'];//修改后新的直播商品信息
            $data['wl_goods_id'] = $data['goods_id'];
            unset($data['goods_name'],$data['goods_id'],$data['sid']);
            //判断值是否被修改
            if($status == 0) $allowUpdateFiled = ['coverImgUrl' , 'name' , 'priceType' , 'price' , 'price2' , 'url'];//所有信息都可以更新
            else $allowUpdateFiled = ['priceType' , 'price' , 'price2'];//仅允许更新 价格类型和价格
            $updateData['goodsId'] = $goods_id;//需要修改的信息
            if(($data['goods_plugin'] != $params['goods_plugin'] || $data['wl_goods_id'] != $params['wl_goods_id']) && in_array('url',$allowUpdateFiled)){
                //商品被修改  从新获取链接
                $updateData['url'] = Live::getWxAppPath($data['goods_id'],$data['goods_plugin']);
            }
            if($data['goods_img'] != $params['goods_img'] && in_array('coverImgUrl',$allowUpdateFiled)){
                //图片被修改  获取新图片的信息
                if(!$data['goods_img']) $data['goods_img'] = tomedia($info['logo']);
                else $data['goods_img'] = tomedia($data['goods_img']);
                //判断图片信息  微信规则限制 图片大小不能大于300像素*300像素；
                list($width,$height) = getimagesize($data['goods_img']);//获取图片信息
                if($width > 300 || $height > 300 ) wl_message("图片超出限制，宽高不能大于300像素!", web_url('live/goods/editGoods',['status'=>$status,'goods_id'=>$goods_id])  , 'error');

                $updateData['coverImgUrl'] = $data['goods_img'];
            }
            if($data['title'] != $params['title'] && in_array('name',$allowUpdateFiled)){
                //商品名称被修改  获取新的商品名称
                if(!$data['title']){
                    //商品名称不存在 使用当前商品名称
                    $goodsName = $info['goods_name'];
                    if(mb_strlen($goodsName) > 14) $goodsName = mb_substr($goodsName,0,14,'utf-8');
                    $updateData['name'] = $goodsName;
                }else{
                    $updateData['name'] = $data['title'];
                }
            }
            if($data['price_type'] != $params['price_type'] && in_array('priceType',$allowUpdateFiled)){
                //价格类型被修改  获取新的价格类型
                $updateData['priceType'] = $data['price_type'];
            }
            if($data['price'] != $params['price'] && in_array('price',$allowUpdateFiled)){
                //左价格被修改  获取新的价格
                $updateData['price'] = sprintf("%.2f" , $data['price']);
            }
            if($data['price2'] != $params['price2'] && in_array('price2',$allowUpdateFiled)){
                //右价格被修改  获取新的价格
                $updateData['price2'] = sprintf("%.2f" , $data['price2']);
            }
            //价格一不存在  默认使用一口价 价格为商品当前价格
            if (!$updateData['price'] || $updateData['price'] <= 0) {
                $updateData['priceType'] = intval(1);
                $updateData['price']     = sprintf("%.2f" , $data['price']);
                $updateData['price2']    = '';
            }
            //请求编辑信息
            try{
                //是否需要处理图片信息
                if(array_key_exists('coverImgUrl',$updateData) && !empty($updateData['coverImgUrl'])){
                    $imgInfo = pathinfo($updateData['coverImgUrl']);//获取图片信息
                    if($imgInfo['basename']) $updateData['coverImgUrl'] = (new Live())->uploadTemporaryMaterial($updateData['coverImgUrl']);
                }
                //修改价格信息则三个价格参数都要传递
                if(array_key_exists('price',$updateData) || array_key_exists('price2',$updateData) || array_key_exists('priceType',$updateData)){
                    $updateData['priceType'] = $data['price_type'];
                    $updateData['price'] = $data['price'];
                    $updateData['price2'] = $data['price2'];
                }
                //请求修改商品信息
                $res = (new Live())->updateGoods($updateData);
                if($res['errcode'] == 0) {
                    pdo_update(PDO_NAME."live_goods",$data,['goods_id'=>$goods_id]);

                    wl_message("操作成功！" ,web_url('live/goods/goodsList',['status'=>$status]) , 'success');
                }else wl_message("操作失败！", web_url('live/goods/goodsList',['status'=>$status]) , 'error');
            }catch (Exception $e){
                wl_message($e->getMessage(), web_url('live/goods/goodsList',['status'=>$status]) , 'error');
            }
        }

        include wl_template("goods/edit");
    }
    /**
     * Comment: 信息同步
     * Author: zzw
     * Date: 2020/10/26 16:15
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function infoSynchronization(){
        global $_W,$_GPC;
        //基本参数信息获取
        $page      = $_GPC['page'] ? : 1;
        //根据总后台和代理商后台 调用不同的同步操作
        try{
            if($_W['aid'] > 0){
                //代理商后台
                $pageIndex = 50;
                $where = ['uniacid' => $_W['uniacid'],'aid'=>$_W['aid']];//条件生成
                //获取商品相关信息
                $total = pdo_count(PDO_NAME."live_goods",$where);//商品总数信息
                $list = pdo_getall(PDO_NAME."live_goods" ,$where ,['goods_id'],'','goods_id DESC',[$page,$pageIndex]);//商品列表

                (new Live())->goodsInfoSynchronization(array_column($list,'goods_id'));
                $totalPage = ceil($total / $pageIndex);
            }else{
                //总后台  商品状态，0：未审核。1：审核中，2：审核通过，3：审核驳回
                $pageIndex = 15;
                $pageStart = $page * $pageIndex - $pageIndex;
                $res0 = (new Live())->wholeGoodsInfoSynchronization($pageStart,$pageIndex,0);
                $res1 = (new Live())->wholeGoodsInfoSynchronization($pageStart,$pageIndex,1);
                $res2 = (new Live())->wholeGoodsInfoSynchronization($pageStart,$pageIndex,2);
                $res3 = (new Live())->wholeGoodsInfoSynchronization($pageStart,$pageIndex,3);
                $totalPage = max([$res0,$res1,$res2,$res3]);
            }

            Commons::sRenderSuccess("同步成功",['total_page' => $totalPage]);
        }catch (Exception $e){
            Commons::sRenderError($e->getMessage());
        }
    }
    /**
     * Comment: 直播商品选择器
     * Author: zzw
     * Date: 2020/10/27 11:03
     */
    public function selectGoods(){
        global $_W,$_GPC;
        try{
            //基本参数信息获取   商品状态，0：未审核。1：审核中，2：审核通过，3：审核驳回
            $status    = intval(2);//选择商品 商品状态固定为2:审核通过
            $page      = $_GPC['page'] ? : 1;
            $pageIndex = $_GPC['page_index'] ? : 10;
            //条件生成
            $where = ['uniacid'=>$_W['uniacid'],'audit_status'=>$status];
            if($_W['aid'] > 0) $where['aid'] = $_W['aid'];
            //信息获取
            list($total,$list) = Live::getGoodsParamsList($where,$page,$pageIndex);
            $pager = wl_pagination($total, $page, $pageIndex);

            if($_GPC['is_block']) include wl_template("goods/select_goods");
            else include wl_template("goods/select");
        }catch (Exception $e){
            Commons::sRenderError($e->getMessage());
        }
    }
}
