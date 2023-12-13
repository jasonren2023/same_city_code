<?php
defined('IN_IA') or exit('Access Denied');

class Wxchannels{


    /**
     * 检查接入状态
     */
    static function getAccessInfo(){
        global $_W;
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/register/check?access_token='.$access_token;
        $res = curlPostRequest($url,json_encode([]));
        return $res;
    }

    /**
     * 检查任务完成
     */
    static function getfinishInfo($number){
        global $_W;
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/register/finish_access_info?access_token='.$access_token;
        $res = curlPostRequest($url,json_encode(['access_info_item' => $number]));
        return $res;
    }

    /**
     * 申请接入
     */

    static function applyAccess(){
        global $_W;
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/register/apply?access_token='.$access_token;
        $res = curlPostRequest($url,json_encode([]));
        return $res;
    }

    /**
     * 获取类目信息
     */
    static function getCateList(){
        global $_W;
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/cat/get?access_token='.$access_token;
        $res = curlPostRequest($url,json_encode([]));
        return $res;
    }

    /**
     * 校验已授权类目
     */
    static function getAuthority(){
        global $_W;
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/account/get_category_list?access_token='.$access_token;
        $res = curlPostRequest($url,json_encode([]));
        return $res;
    }

    /**
     * 上传商品
     */
    static function uploadGoods($goods){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/spu/add?access_token='.$access_token;
        $goods = json_encode($goods,256);
        $res = curlPostRequest($url,$goods);
        return $res;
    }

    /**
     * 上传图片
     */
    static function uploadImg($img,$type = 0){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/img/upload?access_token='.$access_token;
        $imgs = [
            'resp_type' => $type,
            'upload_type' => 1,
            'img_url' => $img
        ];
        $res = curlPostRequest($url,$imgs,['Content-Type:multipart/form-data']);
        if($type == 1){
            $data = $res['img_info']['temp_img_url'];
        }else if($type == 2){
            $data = $res['img_info']['pay_media_id'];
        }else{
            $data = $res['img_info']['media_id'];
        }
        return $data;
    }
    /**
     * 上传图片
     */
    static function uploadImg2($img,$type = 'png'){
        $access_token = WeliamWeChat::getAccessToken(0,1);
        $url = 'https://api.weixin.qq.com/cgi-bin/material/add_material?access_token='.$access_token.'&type=image';
        $imgPathInfo = pathinfo($img);
        $basename = $imgPathInfo['filename'].'.'.$type;
        $filename = PATH_MODULE.'data/cache'.$basename;
        //将网络图片保存到本地
        file_put_contents($filename, file_get_contents($img));

        $curl_file =  curl_file_create($filename, $type);

        $imgs = [
            'media' => $curl_file,
        ];
        $res = curlPostRequest($url,$imgs,['Content-Type:multipart/form-data']);

        return $res;
    }

    /**
     * 上架商品
     */

    static function listingGoods($product_id){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/spu/listing?access_token='.$access_token;
        $goods = json_encode(['product_id' => $product_id]);
        $res = curlPostRequest($url,$goods);
        return $res;
    }

    /**
     * 下架商品
     */

    static function delistingGoods($product_id){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/spu/delisting?access_token='.$access_token;
        $goods = json_encode(['product_id' => $product_id]);
        $res = curlPostRequest($url,$goods);
        return $res;
    }

    /**
     * 删除商品
     */

    static function deteleGoods($product_id){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/spu/del?access_token='.$access_token;
        $goods = json_encode(['product_id' => $product_id]);
        $res = curlPostRequest($url,$goods);
        return $res;
    }

    /**
     * 撤回审核
     */

    static function delAuditGoods($product_id){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/spu/del_audit?access_token='.$access_token;
        $goods = json_encode(['product_id' => $product_id]);
        $res = curlPostRequest($url,$goods);
        return $res;
    }

    /**
     * 获取商品信息
     */

    static function getGoods($product_id){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/spu/get?access_token='.$access_token;
        $goods = json_encode(['product_id' => $product_id]);
        $res = curlPostRequest($url,$goods);
        return $res;
    }

    /**
     * 快捷更新商品
     */
    static function AuditGoods($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/spu/update_without_audit?access_token='.$access_token;
        $goods = json_encode($data);
        $res = curlPostRequest($url,$goods);
        return $res;
    }

    /**
     * 更新商品
     */
    static function updateGoods($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/spu/update?access_token='.$access_token;
        $goods = json_encode($data,256);
        $res = curlPostRequest($url,$goods);
        return $res;
    }
    /**
     * 生成订单
     */
    static function orderAdd($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/order/add?access_token='.$access_token;
        $goods = json_encode($data,256);
        $res = curlPostRequest($url,$goods);
        return $res;
    }

    /**
     * 获取支付参数
     */
    static function getPaymentParams($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/order/getpaymentparams?access_token='.$access_token;
        $goods = json_encode($data);
        $res = curlPostRequest($url,$goods);
        return $res;
    }

    /**
     * 获取订单列表
     */
    static function getOrderList($pindex){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/order/get_list?access_token='.$access_token;
        $data = [
            'page' => $pindex,
            'page_size' => 20,
            'sort_order' => 1
        ];
        $data = json_encode($data);
        $res = curlPostRequest($url,$data);
        return $res;
    }

    /**
     * 关闭订单
     */
    static function closeOrder($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/order/close?access_token='.$access_token;
        $data = json_encode($data);
        $res = curlPostRequest($url,$data);
        return $res;
    }
    /**
     * 获取订单
     */
    static function getOrderInfo($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/order/get?access_token='.$access_token;
        $data = json_encode($data);
        $res = curlPostRequest($url,$data);
        return $res;
    }

    /**
     * 获取物流公司
     */
    static function getCompanyList(){
        $name = 'wxComList';
        session_start();
        $comListInfo = json_decode($_SESSION[$name],true);
        if($comListInfo && time() < $comListInfo['end_time']){
            $comList = $comListInfo['list'];
        }else{
            $access_token = WeliamWeChat::getAccessToken(0,3);
            $url = 'https://api.weixin.qq.com/shop/delivery/get_company_list?access_token='.$access_token;
            $res = curlPostRequest($url,json_encode([]));
            $comList = $res['company_list'];
            $endTime = time() + 86400;
            //记录进入session中
            $_SESSION[$name] = json_encode(['list'=>$comList,'end_time'=>$endTime]);
        }
        return $comList;
    }

    /**
     * 快递发货
     */
    static function sendDelivery($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/delivery/send?access_token='.$access_token;
        $data = json_encode($data,256);
        $res = curlPostRequest($url,$data);
        return $res;
    }

    /**
     * 确认收货
     */
    static function recieveOrder($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/delivery/recieve?access_token='.$access_token;
        $data = json_encode($data);
        $res = curlPostRequest($url,$data);
        return $res;
    }

    /**
     * 申请退坤
     */
    static function addEcaftersale($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/ecaftersale/add?access_token='.$access_token;
        $data = json_encode($data,256);
        $res = curlPostRequest($url,$data);
        return $res;
    }

    /**
     * 售后列表
     */
    static function getEcaftersaleList($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/ecaftersale/get_list?access_token='.$access_token;
        $data = json_encode($data,256);
        $res = curlPostRequest($url,$data);
        return $res;
    }

    /**
     * 售后记录
     */
    static function getEcaftersaleInfo($aftersale_id){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/ecaftersale/get?access_token='.$access_token;
        $res = curlPostRequest($url,json_encode(['aftersale_id' => $aftersale_id ]));
        return $res;
    }

    /**
     * 售后类型文本转换
     */
    static function afterSalesReasonType($refund_reason_type){
        $data = [
            '1' => '商品无货',
            '2' => '发货时间问题',
            '3' => '不想要了',
            '5' => '地址信息填写错误',
            '6' => '买多/买错/不想要了',
            '7' => '商品损坏/包装脏污',
            '8' => '少/错商品/与页面描述不符',
            '9' => '无效的物流单号',
            '10' => '物流超72小时停滞',
            '11' => '快递无法送到指定地点',
            '12' => '显示签收但未收到商品',
            '14' => '质量问题',
            '15' => '其他',
        ];
        return $data[$refund_reason_type];
    }

    /**
     * 售后状态文本转换
     */
    static function afterSalesReasonStatus($status){
        $data = [
            '1' => '用户取消售后申请',
            '2' => '商家处理退款申请中',
            '4' => '商家拒绝退款',
            '5' => '商家拒绝退货',
            '6' => '待用户退货',
            '7' => '售后单关闭',
            '8' => '待商家收货',
            '21' => '平台处理退款申请中',
            '23' => '商家处理退货申请中',
            '11' => '平台退款中',
            '13' => '退款成功',
            '24' => '平台处理退货申请中',
            '25' => '平台退款失败',
        ];
        return $data[$status];
    }


    /**
     * 同意售后
     */
    static function acceptrefund($aftersale_id){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/ecaftersale/acceptrefund?access_token='.$access_token;
        $res = curlPostRequest($url,json_encode(['aftersale_id' => $aftersale_id ]));
        return $res;
    }
    /**
     * 拒绝售后
     */
    static function rejectEcaftersale($aftersale_id){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/ecaftersale/reject?access_token='.$access_token;
        $res = curlPostRequest($url,json_encode(['aftersale_id' => $aftersale_id ]));
        return $res;
    }

    /**
     * 获取商户信息
     */
    static function getStoreInfo(){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/account/get_info?access_token='.$access_token;
        $res = curlPostRequest($url,json_encode([]));
        return $res;
    }

    /**
     * 更新商户信息
     */
    static function updateStoreInfo($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/account/update_info?access_token='.$access_token;
        $data = json_encode($data,256);
        $res = curlPostRequest($url,$data);
        return $res;
    }

    /**
     * 新建图文草稿
     */
    static function addDraft($data){
        $access_token = WeliamWeChat::getAccessToken(1,1);
        $url = 'https://api.weixin.qq.com/cgi-bin/draft/add?access_token='.$access_token;
        $data = json_encode($data,256);
        $res = curlPostRequest($url,$data);
        return $res;
    }

    /**
     * 发布图文信息
     */
    static function submitDraft($media_id){
        $access_token = WeliamWeChat::getAccessToken(0,1);
        $taggeturl='https://api.weixin.qq.com/cgi-bin/tags/get?access_token='.$access_token;//获取公众号已创建的标签
        $tags = curlGetRequest($taggeturl);
        $tags_a = array_column($tags['tags'],null,'name');
        if (!$tags_a['extend']['id']){
            $url2='https://api.weixin.qq.com/cgi-bin/tags/create?access_token='.$access_token;//创建标签
            $res = curlPostRequest($url2,json_encode(['tag'=>['name'=>'extend']]));
            $tag_id = $tags['tag']['id'];
        }else{
            $tag_id = $tags_a['extend']['id'];
        }
        $url = 'https://api.weixin.qq.com/cgi-bin/message/mass/sendall?access_token='.$access_token;
        $data = [
            'filter' => [
                'is_to_all' => false,
                'tag_id' => $tag_id,
            ],
            'mpnews' => [
                'media_id' => $media_id
            ],
            'msgtype' => "mpnews",
            'send_ignore_reprint' => 0
        ];
        $data = json_encode($data);
        $res = curlPostRequest($url,$data);
        return $res;
    }

    /**
     * 轮训图文发布结果
     */
    static function freepublishGet($publish_id){
        $access_token = WeliamWeChat::getAccessToken(0,1);
        $url = 'https://api.weixin.qq.com/cgi-bin/freepublish/get?access_token='.$access_token;
        $data = json_encode(['publish_id' => $publish_id]);
        $res = curlPostRequest($url,$data);
        return $res;
    }

    /**
     * 上传资质
     */
    static function upQualification($data){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/audit/audit_category?access_token='.$access_token;
        $data = json_encode($data);
        $res = curlPostRequest($url,$data);
        return $res;
    }

    /**
     * 上传资质
     */
    static function auditResult($audit_id){
        $access_token = WeliamWeChat::getAccessToken(0,3);
        $url = 'https://api.weixin.qq.com/shop/audit/result?access_token='.$access_token;
        $data = json_encode(['audit_id' => $audit_id]);
        $res = curlPostRequest($url,$data);
        return $res;
    }







}
