<?php
defined('IN_IA') or exit('Access Denied');

class WxchannelsModuleUniapp extends Uniapp {
    /**
     * Comment: 生成支付订单
     * Author: wlf
     * Date: 2022/06/10 9:45
     */
    public function addOrder(){
        global $_W,$_GPC;
        //获取参数
        $trace_id = $_GPC['trace_id'];
        $orderno = $_GPC['order_no'];
        //构建订单参数
        if(empty($orderno)){
            $orderno = createUniontid();
            $out_product_id = 'test666666';
            $out_sku_id = 'test666666';
            $product_cnt = 1;
            $sale_price = 10;
            $sku_real_price = 10;
            $title = '接口测试商品1号';
            $head_img = 'https://mmecimage.cn/p/wx255b67a1403adbc2/HAxwRwmaEQFm9zsSF58f9b-YljQkYbG1Hcyx6t5WvQ';
            $path = 'pages/subPages/goods/index';
            $freight = 5;
            $order_price = 15;
            $expressinfo = [
                'name' => '测试工',
                'tel'  => '17708000888',
                'address' => '四川省成都市双流区'
            ];
        }else{
            $paylog = pdo_get('wlmerchant_paylogvfour',array('tid' => $orderno),array('plugin'));
            $type = strtolower($paylog['plugin']);
            if($type == 'rush'){
                $orderinfo = pdo_get('wlmerchant_rush_order',array('orderno' => $orderno),array('activityid','expressid','actualprice','num','price'));
                $goodsid = pdo_get('wlmerchant_channels_goods',array('type' => 1,'goodsid' => $orderinfo['activityid']),array('product_id'));
                $goodsprice = $orderinfo['price'];
                $order_price = $orderinfo['actualprice'] * 100;
            }else if($type == 'groupon'){
                $orderinfo = pdo_get('wlmerchant_order',array('orderno' => $orderno),array('fkid','expressid','price','num','goodsprice'));
                $goodsid = pdo_get('wlmerchant_channels_goods',array('type' => 2,'goodsid' => $orderinfo['fkid']),array('product_id'));
                $goodsprice = $orderinfo['goodsprice'];
                $order_price = $orderinfo['price'] * 100;
            }

            $expressinfo = pdo_get('wlmerchant_express',array('id' => $orderinfo['expressid']),array('name','tel','address','expressprice'));
            $freight = $expressinfo['expressprice']*100;

            if(empty($goodsid['product_id'])){
                $this->renderError('商品非视频号分享商品，无法在视频号中支付');
            }else{
                $goodsinfo = Wxchannels::getGoods($goodsid['product_id']);
                if($goodsinfo['errcode'] != 0){
                    $this->renderError($goodsinfo['errmsg']);
                }else{
                    $title = $goodsinfo['spu']['title'];
                    $path = $goodsinfo['spu']['path'];
                    $head_img = $goodsinfo['spu']['skus'][0]['thumb_img'];
                    $out_product_id = $goodsinfo['spu']['skus'][0]['out_product_id'];
                    $out_sku_id = $goodsinfo['spu']['skus'][0]['out_sku_id'];
                }
                $product_cnt = $orderinfo['num'];
                $sku_real_price = $sale_price = $order_price - $freight;
            }
        }
        $data = [
            'create_time' => date('Y-m-d H:i:s',time()),
            'out_order_id' => $orderno,
            'openid' => $_W['wlmember']['wechat_openid'],
            'path' => 'pages/subPages/orderList/orderDetails/orderDetails?plugin='.$type.'&orderno='.$orderno,
            'out_user_id' => $_W['mid'],
            'fund_type' => 1,
            'expire_time' => time()+1800,
            'trace_id' => $trace_id
        ];
        $order_detail = [];
        //商品列表
        $product_infos[] = [
            'out_product_id' => $out_product_id,
            'out_sku_id'     => $out_sku_id,
            'product_cnt'    => $product_cnt,
            'sale_price'     => $sale_price,
            'sku_real_price' => $sku_real_price,
            'title'          => $title,
            'head_img'       => $head_img,
            'path'           => $path
        ];
        $order_detail['product_infos'] = $product_infos;

        $order_detail['pay_info'] = ['pay_method_type' => 0];
        $order_detail['price_info'] = [
            'freight' => $freight,
            'order_price' => $order_price
        ];
        $data['order_detail'] = $order_detail;
        $data['delivery_detail'] = ['delivery_type' => 1];
        $data['address_info'] = [
            'receiver_name' => $expressinfo['name'],
            'detailed_address' => $expressinfo['address'],
            'tel_number'    => $expressinfo['tel']
        ];
        $orderAccessInfo = Wxchannels::orderAdd($data);
        if($orderAccessInfo['errcode'] > 0){
            $this->renderError($orderAccessInfo['errmsg']);
        }else{
            $this->renderSuccess('订单信息',$orderAccessInfo);
        }
    }


    /**
     * Comment: 生成致富参数
     * Author: wlf
     * Date: 2019/06/15 14:05
     */
    public function getPaymentParams(){
        global $_W,$_GPC;
        $out_order_id = $_GPC['out_order_id'];
        if(empty($out_order_id)){
            $this->renderError('缺少订单参数');
        }
        $data = [
            'out_order_id' => $out_order_id,
            'openid' => $_W['wlmember']['wechat_openid'],
        ];
        $orderAccessInfo = Wxchannels::getPaymentParams($data);
        if($orderAccessInfo['errcode'] > 0){
            $this->renderError($orderAccessInfo['errmsg']);
        }else{
            $this->renderSuccess('支付参数',$orderAccessInfo);
        }
    }

    /**
     * Comment: 提交售后申请
     * Author: wlf
     * Date: 2019/06/16 15:55
     */

    public function addEcaftersale(){
        global $_W,$_GPC;
        $order_id = $_GPC['order_id'];
        if(empty($order_id)){
            $this->renderError('缺少订单参数');
        }
        //获取订单数据
        $orderdata = [
            'order_id' => $order_id,
            'openid' => $_W['wlmember']['wechat_openid']
        ];
        $channelsinfo = Wxchannels::getOrderInfo($orderdata);
        if($channelsinfo['errcode'] > 0){
            $this->renderError($channelsinfo['errmsg']);
        }else{
            $order = $channelsinfo['order'];
        }
        $data = [
            'order_id' => $order_id,
            'openid' => $_W['wlmember']['wechat_openid'],
            'out_aftersale_id' => 'saleafter1001',
            'type' => 1,
            'product_info' => [
                'out_product_id' => $order['order_detail']['product_infos'][0]['out_product_id'],
                'out_sku_id' => $order['order_detail']['product_infos'][0]['out_sku_id'],
                'product_cnt' => 1
            ],
            'orderamt' => $order['order_detail']['price_info']['order_price'],
            'refund_reason' => '测试退款',
            'refund_reason_type' => 1
        ];
        $orderAccessInfo = Wxchannels::addEcaftersale($data);
        if($orderAccessInfo['errcode'] != 0){
            $this->renderError($orderAccessInfo['errmsg']);
        }else{
            $this->renderSuccess('申请成功');
        }
    }


    /**
     * Comment: 创建图文
     * Author: wlf
     * Date: 2019/06/23 13:54
     */
    public function addDraft(){
        global $_W,$_GPC;
        $goodsid = $_GPC['id'];
        $type = $_GPC['type'];
        if(empty($goodsid) || empty($type)){
            $this->renderError('缺少关键参数');
        }

        $article_url = pdo_getcolumn(PDO_NAME.'goods_freepublish',array('mid'=>$_W['mid'],'goodsid'=>$goodsid,'type' => $type),'articleinfo');

        if(!empty($article_url)){
            if(!strstr($article_url,'https')){
                $article_url = str_replace('http','https',$article_url);
            }
            $this->renderSuccess('旧数据',['status' => 0,'article_url' => $article_url]);
        }else{
            $wxAppSet = Setting::wlsetting_read('wxapp_config');
            $wxappid = $wxAppSet['appid'];
            if($type == 6){
                $buyinfo = Setting::wlsetting_read('channelsstoreinfo');
            }else{
                $buyinfo = Setting::wlsetting_read('channelsbuyinfo');
            }
            if(empty($buyinfo['channelsbuy']) || empty($buyinfo['channelsbuymedia'])){
                if($type == 6){
                    $base_url = PATH_MODULE."h5/resource/image/channelsstore.gif";
                }else{
                    $base_url = PATH_MODULE."h5/resource/image/channelsbuy.gif";
                }

                $base_url = tomedia($base_url);
                $imgInfo = Wxchannels::uploadImg2($base_url,'gif');
                $img = $buyinfo['channelsbuy'] = $imgInfo['url'];
                $thumbinfo = $buyinfo['channelsbuymedia'] = $imgInfo['media_id'];
                if($type == 6){
                    Setting::wlsetting_save($buyinfo,'channelsstoreinfo');
                }else{
                    Setting::wlsetting_save($buyinfo,'channelsbuyinfo');
                }
            }else{
                $img = $buyinfo['channelsbuy'];
                $thumbinfo = $buyinfo['channelsbuymedia'];
            }
            if($type == 1){
                $goodsinfo = pdo_get('wlmerchant_rush_activity',array('id' => $goodsid),array('name','thumb'));
                $title = '点击按钮进入小程序购买商品';
            }else if($type == 2){
                $goodsinfo = pdo_get('wlmerchant_groupon_activity',array('id' => $goodsid),array('name','thumb'));
                $title = '点击按钮进入小程序购买商品';
            }else if($type == 6){
                $title = '点击按钮进入小程序查看商户';
            }
            if($type == 6){
                $data = [
                    'articles' => [
                        [
                            'title' => $title,
                            'thumb_media_id' => $thumbinfo,
                            'content' => '
                              <section style="">
                              <a data-miniprogram-appid="'.$wxappid.'" data-miniprogram-path="/pages/mainPages/store/index?sid='.$goodsid.'&head_id='.$_W['mid'].' " href wah-hotarea="click" style="">
                              <img src="'.$img.'" wah-hotarea="click" style="width: 100%;"/>
                              </a>
                              </section>'
                        ]
                    ],
                ];
            }else{
                $data = [
                    'articles' => [
                        [
                            'title' => $title,
                            'thumb_media_id' => $thumbinfo,
                            'content' => '
                              <section style="">
                              <a data-miniprogram-appid="'.$wxappid.'" data-miniprogram-path="/pages/subPages/goods/index?id='.$goodsid.'&type='.$type.'&head_id='.$_W['mid'].' " href wah-hotarea="click" style="">
                              <img src="'.$img.'" wah-hotarea="click" style="width: 100%;"/>
                              </a>
                              </section>'
                        ]
                    ],
                ];
            }

            $res = Wxchannels::addDraft($data);
            if($res['errcode'] != 0){
                $this->renderError($res['errmsg']);
            }else{
                $media_id = $res['media_id'];
            }
            //发布
            $subres = Wxchannels::submitDraft($media_id);
            if($subres['errcode'] != 0){
                $this->renderError($subres['errmsg']);
            }else{
                pdo_insert(PDO_NAME . 'goods_freepublish', ['mid' => $_W['mid'],'goodsid'=>$goodsid,'type' => $type,'msg_id' => $subres['msg_id'],'msg_data_id' => $subres['msg_data_id'] ]);
                $this->renderSuccess('发布成功',['status' => 1,'publish_id' => $subres['msg_id']]);
            }
        }
    }


    /**
     * Comment: 轮询结果
     * Author: wlf
     * Date: 2019/06/23 15:57
     */
    public function getfreepublishRes(){
        global $_W,$_GPC;
        $publish_id = $_GPC['publish_id'];
        $goodsid = $_GPC['id'];
        $type = $_GPC['type'];
        $res = pdo_get(PDO_NAME.'goods_freepublish',array('msg_id' => $publish_id),array('articleinfo'));
        if(empty($res['articleinfo'])){
            $this->renderSuccess('继续轮询',['status' => 1]);
        }else{
            if(!strstr($res['articleinfo'],'https')){
                $res['articleinfo'] = str_replace('http','https',$res['articleinfo']);
            }
            $this->renderSuccess('发布成功',['status' => 0,'article_url' => $res['articleinfo']]);
        }
    }


}