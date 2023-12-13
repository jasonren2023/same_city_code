<?php
defined('IN_IA') or exit('Access Denied');
use EasyWeChat\Factory;

class Live {
    protected $wxApp ,//实例化EasyWeChat
        $caCheKey  = 'weliam_live';//缓存键

    public function __construct(){
        //生成配置信息
        $set = Setting::wlsetting_read('wxapp_config');
        if(!$set['appid'] && !$set['secret']) throw new Exception('请完善小程序配置信息！');
        $config = [
            'app_id'        => trim($set['appid']) ,
            'secret'        => trim($set['secret']) ,
            'response_type' => 'array' ,
        ];
        $this->wxApp = Factory::miniProgram($config);

        return $this;
    }
    /**
     * Comment: 获取accessToken
     * Author: zzw
     * Date: 2020/10/26 13:57
     * @param bool $newToken
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function getAccessToken($newToken = false){
        $accessTokenObj = $this->wxApp->access_token;
        if($newToken){
            //强制重新从微信服务器获取新的token信息
            $accessToken = $accessTokenObj->getToken(true);
            $this->wxApp['access_token']->setToken($accessToken['access_token'], 7000);
        }else{
            //获取已存在的token信息
            $accessToken = $accessTokenObj->getToken();
        }

        return $accessToken;
    }
    /**
     * Comment: 微信上传获取临时素材
     * Author: zzw
     * Date: 2020/10/26 13:57
     * @param $img
     * @return array|bool|false|Memcache|mixed|Redis|string
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     */
    public function uploadTemporaryMaterial($img){
        global $_W;
        //基本参数信息获取
        $link = tomedia($img);
        $caCheName = md5($link);//当前图片缓存信息 防止同一张图片多次提交
        //获取缓存信息
        $mediaId =  Cache::getCache('live_goods_img',$caCheName);
        //上传图片
        if(!$mediaId){
            $imgInfo          = pathinfo($link);//获取图片信息
            if(empty($_W['setting']['remote']['type']) && empty($_W['wlsetting']['enclosure']['service'])){
                $imgPath = PATH_ATTACHMENT ."images/" . MODULE_NAME.'/'.$_W['uniacid'].'/'.$imgInfo['basename'];
                $res = 2;
            }else{
                //将图片添加到临时存储目录 在本地临时存储
                $setting = $_W['setting']['upload']['image'];
                $setting['folder'] = "images/" . MODULE_NAME;
                $temporaryAddress = PATH_ATTACHMENT . $setting['folder'];//文件在本地服务器暂存文件
                if (!file_exists($temporaryAddress)) mkdirs($temporaryAddress);//判断临时文件目录是否存在 不存在建立
                $imgResources = file_get_contents($link);//获取图片二进制信息(图片资源)
                $imgPath      = $temporaryAddress . $imgInfo['basename'];//图片在本地的绝对路径
                $res          = file_put_contents($imgPath , $imgResources);
            }
            if ($res) {
                $info = $this->wxApp->media->uploadImage($imgPath);
                if($info['errcode'] > 0){
                    return $info['errmsg'];
                }
                if($res != 2 ){
                    //删除临时图片
                    unlink($imgPath);
                }
                //获取media_id  并且储存缓存 防止二次提交
                $mediaId = $info['media_id'];
                Cache::setCache('live_goods_img',$caCheName,$mediaId);
            }else {
                throw new Exception("图片处理失败，请重试！");
            }
        }

        return $mediaId;
    }

/****** 直播间信息处理 ***********************************************************************************************/
    /**
     * Comment: 同步直播间信息
     * Author: zzw
     * Date: 2020/10/27 16:03
     * @param $pageStart
     * @param $pageIndex
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function liveInfoSynchronization($pageStart,$pageIndex){
        global $_W;
        //基本参数信息设置  获取当前页的直播间信息数据
        $params = [
            'start' => $pageStart,// 起始拉取房间，start = 0 表示从第 1 个房间开始拉取
            'limit' => $pageIndex,// 每次拉取的个数上限，不要设置过大，建议 100 以内
        ];
        $data = $this->getLiveList($params);
        foreach($data['list'] as $key => $val){
            //判断是否存在商品  存在则通过商品获取aid  以第一个商品为主
            $goodInfo = [];
            if(is_array($val['goods']) && count($val['goods'])) $goodInfo = $this->getUrlParams($val['goods'][0]['url']);
            //查看当前直播间是否存在直播回放
            $playbackParams = [
                'action'  => 'get_replay' ,
                'room_id' => $val['roomid']  ,
                'start'   => 0 ,// 起始拉取房间，start = 0 表示从第 1 个房间开始拉取
                'limit'   => 1 ,// 每次拉取的个数上限，不要设置过大，建议 100 以内
            ];
            $isPlayback = $this->getPlaybackList($playbackParams);
            //生成新的信息
            $newData = [
                'uniacid'     => $goodInfo['uniacid'] ? : $_W['uniacid'] ,
                'aid'         => $goodInfo['aid'] ? : $_W['aid'] ,
                'name'        => $val['name'] ,
                'roomid'      => $val['roomid'] ,
                'cover_img'   => $val['cover_img'] ,
                'share_img'   => $val['share_img'] ,
                'live_status' => $val['live_status'] ,
                'start_time'  => $val['start_time'] ,
                'end_time'    => $val['end_time'] ,
                'anchor_name' => $val['anchor_name'] ,
                'goods_list'  => json_encode(array_column($val['goods'] , 'goods_id')) ,
                'is_playback' => $isPlayback['total'] > 0 ? 1 : 0,
                'is_update'   => 1,
            ];
            //判断直播间是否存在  存在则修改，不存在则添加
            $isHave = pdo_get(PDO_NAME."live",['roomid'=>$val['roomid']]);
            if($isHave) pdo_update(PDO_NAME."live",$newData,['roomid'=>$val['roomid']]);
            else pdo_insert(PDO_NAME."live",$newData);
        }

        //返回信息数据
        return $data['total_page'];
    }
    /**
     * Comment: 直播间列表信息获取
     * Author: zzw
     * Date: 2020/10/27 15:46
     * @param      $params
     * @param bool $newToken
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    protected function getLiveList($params,$newToken = false){
        //token信息获取
        $accessToken = $this->getAccessToken($newToken);
        //信息列表获取
        $api = "https://api.weixin.qq.com/wxa/business/getliveinfo?access_token={$accessToken['access_token']}";
        $res = curlPostRequest($api,\GuzzleHttp\json_encode($params));
        //信息返回
        if($res['errcode'] == 40001){
            return $this->getLiveList($params,true);
        }else if($res['errcode'] == 9410000){
            //兼容没有直播间时
            return [
                'list'       => [] ,
                'total_page' => ceil($res['total'] / $params['limit']) ,
                'total'      => $res['total'] ,
            ];
        }else if($res['errcode'] != 0){
            throw new Exception($res['errmsg']);
        }else{
            return [
                'list'       => $res['room_info'] ,
                'total_page' => ceil($res['total'] / $params['limit']) ,
                'total'      => $res['total'] ,
            ];
        }
    }
    /**
     * Comment: 直播回放获取 —— 获取当前直播间所有回放视频
     * Author: zzw
     * Date: 2020/10/27 17:01
     * @param       $id
     * @param int   $page
     * @param array $info
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function getWholePlayback($id,$page = 0,$info = []){
        //基本参数信息设置  获取当前页的回放信息数据
        $pageIndex = 50;
        $params = [
            'action'  => 'get_replay' ,
            'room_id' => $id ,
            'start'   => $page * $pageIndex ,// 起始拉取房间，start = 0 表示从第 1 个房间开始拉取
            'limit'   => $pageIndex ,// 每次拉取的个数上限，不要设置过大，建议 100 以内
        ];
        //信息获取 信息处理
        $data = $this->getPlaybackList($params);
        if($info) $info['list'] = array_merge($info['list'],$data['list']);
        else $info = $data;
        $page++;
        if($info['total_page'] > $page){
            return $this->getWholePlayback($id,$page,$info);
        }else{
            return $info;
        }
    }
    /**
     * Comment: 直播回放获取 —— 根据条件获取某页回放列表
     * Author: zzw
     * Date: 2020/10/27 16:53
     * @param      $params
     * @param bool $newToken
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function getPlaybackList($params,$newToken = false){
        //token信息获取
        $accessToken = $this->getAccessToken($newToken);
        //信息列表获取
        $api = "https://api.weixin.qq.com/wxa/business/getliveinfo?access_token={$accessToken['access_token']}";
        $res = curlPostRequest($api,\GuzzleHttp\json_encode($params));
        //处理返回参数信息
        if($res['errcode'] == 40001){
            return $this->getPlaybackList($params,true);
        }else if($res['errcode'] != 0){
            throw new Exception($res['errmsg']);
        }else{
            return [
                'list'       => $res['live_replay'] ,
                'total_page' => ceil($res['total'] / $params['limit']) ,
                'total'      => $res['total'] ,
            ];
        }
    }
    /**
     * Comment: 申请添加直播间
     * Author: zzw
     * Date: 2020/10/27 11:33
     * @param      $data
     * @param bool $newToken
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function addLive($data,$newToken = false){
        //token信息获取
        $data['feedsImg']  = $data['coverImg'];
        $accessToken = $this->getAccessToken($newToken);
        //建立直播间
        $api = "https://api.weixin.qq.com/wxaapi/broadcast/room/create?access_token={$accessToken['access_token']}";
        $headers = [
            "Content-type: application/json;charset='utf-8'" ,
            "Accept: application/json" ,
            "Cache-Control: no-cache" ,
            "Pragma: no-cache"
        ];
        $res = curlPostRequest($api , json_encode($data) , $headers);
        if($res['errcode'] == 40001){
            return $this->addLive($data,true);
        }else if($res['errcode'] != 0 && $res['errcode'] != 300036){
            throw new Exception("直播间添加:".$res['errmsg']);
        }else{
            return $res;
        }
    }
    /**
     * Comment: 直播间商品导入
     * Author: zzw
     * Date: 2020/10/27 11:32
     * @param      $data
     * @param bool $newToken
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function importGoods($data,$newToken = false){
        //token信息获取
        $accessToken = $this->getAccessToken($newToken);
        //建立直播间
        $api = "https://api.weixin.qq.com/wxaapi/broadcast/room/addgoods?access_token={$accessToken['access_token']}";
        $res = curlPostRequest($api , json_encode($data) , ["Content-type: application/json;charset='utf-8'"]);
        if($res['errcode'] == 40001){
            return $this->importGoods($data,true);
        }else if($res['errcode'] != 0){
            throw new Exception("商品导入:".$res['errmsg']);
        }else{
            return $res;
        }
    }

/****** 商品信息处理 *************************************************************************************************/
    /**
     * Comment: 获取直播商品在本平台的储存信息
     * Author: zzw
     * Date: 2020/10/27 10:42
     * @param $where
     * @param $page
     * @param $pageIndex
     * @return array
     */
    public static function getGoodsParamsList($where,$page,$pageIndex){
        global $_W;
        //获取商品总数信息
        $total = pdo_count(PDO_NAME."live_goods",$where);
        //获取商品列表
        $field = ['id','aid','goods_id','goods_img','title','price_type','price','price2','third_party_tag'];
        $list = pdo_getall(PDO_NAME."live_goods" ,$where ,$field,'','goods_id DESC',[$page,$pageIndex]);
        foreach($list as &$item){
            if($item['aid'] > 0) $item['agent_name'] = pdo_getcolumn(PDO_NAME."agentusers",['id'=>$item['aid']],'agentname');
        }

        return [$total,$list];
    }
    /**
     * Comment: 根据类型和id获取商品信息
     * Author: zzw
     * Date: 2020/10/26 13:43
     * @param $id
     * @param $plugin
     * @return bool|mixed
     */
    public static function getGoodsInfo($id,$plugin){
        //获取商品信息
        switch ($plugin) {
            case 'rush':
                $table = tablename(PDO_NAME."rush_activity");
                $field = 'name as goods_name,id as goods_id,"rush" as goods_plugin,sid,thumb as logo,price';
                break;//抢购商品
            case 'groupon':
                $table = tablename(PDO_NAME."groupon_activity");
                $field = 'name as goods_name,id as goods_id,"groupon" as goods_plugin,sid,thumb as logo,price';
                break;//团购商品
            case 'wlfightgroup':
                $table = tablename(PDO_NAME."fightgroup_goods");
                $field = 'name as goods_name,id as goods_id,"wlfightgroup" as goods_plugin,merchantid as sid,logo,price';
                break;//拼团商品
            case 'coupon':
                $table = tablename(PDO_NAME."couponlist");
                $field = 'title as goods_name,id as goods_id,"coupon" as goods_plugin,merchantid as sid,logo,price';
                break;//优惠券
            case 'bargain':
                $table = tablename(PDO_NAME."bargain_activity");
                $field = 'name as goods_name,id as goods_id,"bargain" as goods_plugin,sid,thumb as logo,price';
                break;//砍价商品
        }
        $sql = "SELECT {$field} FROM ".$table." WHERE id = {$id}";
        $info = pdo_fetch($sql);

        return $info;
    }
    /**
     * Comment: 根据类型获取id获取对应的小程序路径信息
     * Author: zzw
     * Date: 2020/10/26 17:40
     * @param $id
     * @param $plugin
     * @return string
     */
    public static function getWxAppPath($id,$plugin){
        global $_W;
        switch ($plugin) {
            case 'rush':
                $url = "pages/subPages/goods/index?i={$_W['uniacid']}&type=1&id={$id}";
                break;//抢购商品
            case 'groupon':
                $url = "pages/subPages/goods/index?i={$_W['uniacid']}&type=2&id={$id}";
                break;//团购商品
            case 'wlfightgroup':
                $url = "pages/subPages/goods/index?i={$_W['uniacid']}&type=3&id={$id}";
                break;//拼团商品
            case 'coupon':
                $url = "pages/subPages/goods/index?i={$_W['uniacid']}&type=5&id={$id}";
                break;//优惠券
            case 'bargain':
                $url = "pages/subPages/goods/index?i={$_W['uniacid']}&type=7&id={$id}";
                break;//砍价商品
        }

        return $url;
    }
    /**
     * Comment: 直播商品提交审核
     * Author: zzw
     * Date: 2020/10/26 13:57
     * @param      $params
     * @param bool $newToken
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function submitGoods($params,$newToken = false){
        //token信息获取
        $accessToken = $this->getAccessToken($newToken);
        //信息列表获取
        $api = "https://api.weixin.qq.com/wxaapi/broadcast/goods/add?access_token={$accessToken['access_token']}";
        $res = curlPostRequest($api,json_encode(['goodsInfo'=>$params]),["Content-type: application/json;charset='utf-8'"]);
        //信息返回
        if($res['errcode'] == 40001){
            return $this->submitGoods($params,true);
        }else if($res['errcode'] != 0){
            throw new Exception($res['errmsg']);
        }else{
            return $res;
        }
    }
    /**
     * Comment: 撤回待审核中的商品
     * Author: zzw
     * Date: 2020/10/27 9:39
     * @param      $params
     * @param bool $newToken
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function withdrawGoods($params,$newToken = false){
        //token信息获取
        $accessToken = $this->getAccessToken($newToken);
        //信息列表获取
        $api = "https://api.weixin.qq.com/wxaapi/broadcast/goods/resetaudit?access_token={$accessToken['access_token']}";
        $res = curlPostRequest($api,json_encode($params),["Content-type: application/json;charset='utf-8'"]);
        //信息返回
        if($res['errcode'] == 40001){
            return $this->withdrawGoods($params,true);
        }else if($res['errcode'] != 0){
            throw new Exception($res['errmsg']);
        }else{
            return $res;
        }
    }
    /**
     * Comment: 重新提交未审核的商品进行审核
     * Author: zzw
     * Date: 2020/10/27 9:40
     * @param      $id
     * @param bool $newToken
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function reSubmitGoods($id,$newToken = false){
        //token信息获取
        $accessToken = $this->getAccessToken($newToken);
        //信息列表获取
        $api = "https://api.weixin.qq.com/wxaapi/broadcast/goods/audit?access_token={$accessToken['access_token']}";
        $res = curlPostRequest($api,json_encode(['goodsId'=>$id]),["Content-type: application/json;charset='utf-8'"]);
        //信息返回
        if($res['errcode'] == 40001){
            return $this->reSubmitGoods($id,true);
        }else if($res['errcode'] != 0){
            throw new Exception($res['errmsg']);
        }else{
            return $res;
        }
    }
    /**
     * Comment: 请求删除商品
     * Author: zzw
     * Date: 2020/10/26 16:23
     * @param int|string  $id
     * @param bool $newToken
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function deleteGoods($id,$newToken = false){
        //token信息获取
        $accessToken = $this->getAccessToken($newToken);
        //信息列表获取
        $api = "https://api.weixin.qq.com/wxaapi/broadcast/goods/delete?access_token={$accessToken['access_token']}";
        $res = curlPostRequest($api,json_encode(['goodsId'=>$id]),["Content-type: application/json;charset='utf-8'"]);
        //信息返回
        if($res['errcode'] == 40001){
            return $this->deleteGoods($id,true);
        }else if($res['errcode'] != 0){
            throw new Exception($res['errmsg']);
        }else{
            return $res;
        }
    }
    /**
     * Comment: 修改商品信息
     * Author: zzw
     * Date: 2020/10/27 9:09
     * @param      $params
     * @param bool $newToken
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function updateGoods($params,$newToken = false){
        //token信息获取
        $accessToken = $this->getAccessToken($newToken);
        //信息列表获取
        $api = "https://api.weixin.qq.com/wxaapi/broadcast/goods/update?access_token={$accessToken['access_token']}";
        $res = curlPostRequest($api,json_encode(['goodsInfo'=>$params]),["Content-type: application/json;charset='utf-8'"]);
        //信息返回
        if($res['errcode'] == 40001){
            return $this->updateGoods($params,true);
        }else if($res['errcode'] != 0){
            throw new Exception($res['errmsg']);
        }else{
            return $res;
        }
    }
    /**
     * Comment: 根据小程序page路径信息获取商品信息
     * Author: zzw
     * Date: 2020/10/27 15:52
     * @param $url
     * @return array|bool|mixed
     * @throws Exception
     */
    public function getUrlParams($url){
        //根据url获取参数信息
        $str = explode('?',$url)[1];
        $strArr = explode("&",$str);
        $strArr = is_array($strArr) ? $strArr : [];
        $params = [];
        foreach($strArr as $key => $val){
            $valArr = explode("=",$val);
            $params[$valArr[0]] = $valArr[1];
        }
        if(!$params['id'] || $params['id'] <= 0) throw new Exception("商品信息不存在，请刷新重试!");
        if(!$params['type']) throw new Exception("商品类型错误，请刷新重试!");
        //获取商品信息
        switch ($params['type']) {
            case 1:
                $table = tablename(PDO_NAME."rush_activity");
                $field = 'uniacid,aid,name as goods_name,id as goods_id,"rush" as goods_plugin,sid';
                break;//抢购商品
            case 2:
                $table = tablename(PDO_NAME."groupon_activity");
                $field = 'uniacid,aid,name as goods_name,id as goods_id,"groupon" as goods_plugin,sid';
                break;//团购商品
            case 3:
                $table = tablename(PDO_NAME."fightgroup_goods");
                $field = 'uniacid,aid,name as goods_name,id as goods_id,"wlfightgroup" as goods_plugin,merchantid as sid';
                break;//拼团商品
            case 5:
                $table = tablename(PDO_NAME."couponlist");
                $field = 'uniacid,aid,title as goods_name,id as goods_id,"coupon" as goods_plugin,merchantid as sid';
                break;//优惠券
            case 7:
                $table = tablename(PDO_NAME."bargain_activity");
                $field = 'uniacid,aid,name as goods_name,id as goods_id,"bargain" as goods_plugin,sid';
                break;//砍价商品
        }
        $sql = "SELECT {$field} FROM ".$table." WHERE id = {$params['id']}";
        $goodsInfo = pdo_fetch($sql);

        return is_array($goodsInfo) ? $goodsInfo : [];
    }
    /**
     * Comment: 商品信息同步 —— 代理商信息同步(仅当前代理商商品)
     * Author: zzw
     * Date: 2020/10/26 16:14
     * @param array ids
     * @param bool $newToken
     * @return bool
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function goodsInfoSynchronization($ids,$newToken = false){
        global $_W;
        //判断是否存在需要同步信息的内容
        if(!is_array($ids) && count($ids)> 0) return false;
        //token信息获取
        $accessToken = $this->getAccessToken($newToken);
        //信息列表获取
        $api = "https://api.weixin.qq.com/wxa/business/getgoodswarehouse?access_token={$accessToken['access_token']}";
        $res = curlPostRequest($api,json_encode(['goods_ids'=>$ids]),["Content-type: application/json;charset='utf-8'"]);
        //信息返回
        if($res['errcode'] == 40001){
            return $this->goodsInfoSynchronization($ids,true);
        }else if($res['errcode'] != 0){
            throw new Exception($res['errmsg']);
        }else{
            $list = $res['goods'];
            foreach($list as $item){
                //获取商品信息
                $goods  = $this->getUrlParams($item['url']);
                if($goods['goods_id'] > 0){
                    $params = [
                        'uniacid'         => $goods['uniacid'] ? : $_W['uniacid'] ,
                        'aid'             => $goods['aid'] ? : 0 ,
                        'wl_goods_id'     => $goods['goods_id'] ,
                        'goods_plugin'    => $goods['goods_plugin'] ,
                        'audit_status'    => $item['audit_status'] ,
                        'title'           => $item['name'] ,
                        'goods_img'       => $item['cover_img_url'] ,
                        'price_type'      => $item['price_type'] ,
                        'price'           => $item['price'] ,
                        'price2'          => $item['price2'] ,
                        'third_party_tag' => $item['third_party_tag'] ,
                    ];
                    pdo_update(PDO_NAME."live_goods",$params,['goods_id'=>$item['goods_id']]);
                }
            }
        }
    }
    /**
     * Comment: 商品信息同步 —— 总后台信息同步(平台全部商品)
     * Author: zzw
     * Date: 2020/10/28 10:00
     * @param int $pageStart
     * @param int $pageIndex
     * @param int $status
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function wholeGoodsInfoSynchronization(int $pageStart,int $pageIndex,int $status){
        global $_W;
        //商品列表信息获取
        $params = [
            'offset' => $pageStart ,
            'limit'  => $pageIndex ,
            'status' => $status ,
        ];
        $info = $this->getPageGoodsList($params);
        if(is_array($info['list']) && count($info['list']) > 0) {
            //循环同步商品基本信息(除状态信息)
            foreach ($info['list'] as $key => $item) {
                //获取当前商品在本平台的信息  商品存在则同步信息   不存在则删除信息
                $goods  = $this->getUrlParams($item['url']);
                if($goods['goods_id'] > 0) {
                    $params = [
                        'uniacid'         => $goods['uniacid'] ? : $_W['uniacid'] ,
                        'aid'             => $goods['aid'] ? : 0 ,
                        'wl_goods_id'     => $goods['goods_id'] ,
                        'goods_plugin'    => $goods['goods_plugin'] ,
                        'goods_id'        => $item['goodsId'] ,
                        'title'           => $item['name'] ,
                        'goods_img'       => $item['coverImgUrl'] ,
                        'price_type'      => $item['priceType'] ,
                        'price'           => $item['price'] ,
                        'price2'          => $item['price2'] ,
                        'third_party_tag' => $item['thirdPartyTag'] ,
                    ];
                    //判断商品是否存在  存在则修改信息，不存在则添加信息
                    $isHave = pdo_get(PDO_NAME . "live_goods" , ['goods_id' => $params['goods_id']]);
                    if ($isHave) pdo_update(PDO_NAME . "live_goods" , $params , ['goods_id' => $params['goods_id']]);
                    else pdo_insert(PDO_NAME . "live_goods" , $params);
                }else{
                    pdo_delete(PDO_NAME . "live_goods"  , ['goods_id' => $item['goodsId']]);
                }
            }
            //获取商品状态信息  同步当前页面所有商品的状态信息
            $goodIds = array_column($info['list'] , 'goodsId');
            $this->goodsInfoSynchronization($goodIds);
        }

        return $info['total_page'];
    }
    /**
     * Comment: 获取指定页数的商户信息列表
     * Author: zzw
     * Date: 2020/10/28 9:56
     * @param      $params
     * @param bool $newToken
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function getPageGoodsList($params,$newToken = false){
        //token信息获取
        $accessToken = $this->getAccessToken($newToken);
        //信息列表获取
        $api = "https://api.weixin.qq.com/wxaapi/broadcast/goods/getapproved?access_token={$accessToken['access_token']}&".http_build_query($params);
        $res = curlGetRequest($api);
        //信息返回
        if($res['errcode'] == 40001){
            return $this->getPageGoodsList($params,true);
        }else if($res['errcode'] != 0){
            throw new Exception($res['errmsg']);
        }else{
            return [
                'list'       => $res['goods'] ,
                'total_page' => ceil($res['total'] / $params['limit']) ,
                'total'      => $res['total'] ,
            ];
        }
    }

/****** 直播成员信息管理 *********************************************************************************************/
    /**
     * Comment: 添加设置成员信息
     * Author: zzw
     * Date: 2020/11/5 13:47
     * @param array $params
     * @param bool $newToken
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function addMemberInfo($params,$newToken = false){
        //token信息获取
        $accessToken = $this->getAccessToken($newToken);
        //信息列表获取
        $api = "https://api.weixin.qq.com/wxaapi/broadcast/role/addrole?access_token={$accessToken['access_token']}";
        $res = curlPostRequest($api,json_encode($params),["Content-type: application/json;charset='utf-8'"]);
        //信息返回
        if($res['errcode'] == 40001){
            return $this->addMemberInfo($params,true);
        }else if($res['errcode'] == 400001){
            throw new Exception('微信号不合规');
        }else if($res['errcode'] == 400002){
            throw new Exception('微信号需要实名认证');
        }else if($res['errcode'] == 400003){
            throw new Exception('添加角色达到上限');
        }else if($res['errcode'] != 0){
            throw new Exception($res['msg']);
        }
    }
    /**
     * Comment: 根据参数获取成员信息
     * Author: zzw
     * Date: 2020/11/5 10:08
     * @param      $params
     * @param bool $newToken
     * @return array
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function memberInfoSynchronization($params,$newToken = false){
        global $_W;
        //token信息获取
        $accessToken = $this->getAccessToken($newToken);
        //信息列表获取
        $api = "https://api.weixin.qq.com/wxaapi/broadcast/role/getrolelist?access_token={$accessToken['access_token']}&".http_build_query($params);
        $res = curlGetRequest($api);
        //信息返回
        if($res['errcode'] == 40001){
            return $this->memberInfoSynchronization($params,true);
        }else if($res['errcode'] != 0){
            throw new Exception($res['errmsg']);
        }else{
            return [
                'list'       => $res['list'] ,
                'total_page' => ceil($res['total'] / $params['limit']) ,
                'total'      => $res['total'] ,
            ];
        }
    }
    /**
     * Comment: 删除成员角色信息
     * Author: zzw
     * Date: 2020/11/5 13:58
     * @param      $params
     * @param bool $newToken
     * @return mixed
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\RuntimeException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\HttpException
     */
    public function deleteMember($params,$newToken = false){
        global $_W;
        //token信息获取
        $accessToken = $this->getAccessToken($newToken);
        //信息列表获取
        $api = "https://api.weixin.qq.com/wxaapi/broadcast/role/deleterole?access_token={$accessToken['access_token']}";
        $res = curlPostRequest($api,json_encode($params),["Content-type: application/json;charset='utf-8'"]);
        //信息返回
        if($res['errcode'] == 40001){
            return $this->deleteMember($params,true);
        }else if($res['errcode'] != 0){
            throw new Exception($res['msg']);
        }
    }

}
