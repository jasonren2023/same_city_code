<?php
defined('IN_IA') or exit('Access Denied');

use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
class Live_WeliamController {
    /**
     * Comment: 获取直播间列表
     * Author: zzw
     * Date: 2020/10/27 14:54
     */
    public function liveList(){
        global $_W,$_GPC;
        //基本参数信息获取
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        //条件生成
        $where = ['uniacid'=>$_W['uniacid']];
        if($_W['aid']) $where['aid'] = $_W['aid'];
        //获取直播相关信息
        $total = pdo_count(PDO_NAME."live",$where);
        $field = ['name','roomid','cover_img','share_img','live_status','start_time','end_time','anchor_name','goods_list','is_playback'];
        $list = pdo_getall(PDO_NAME."live",$where,$field,'','start_time DESC',[$page,$pageIndex]);
        $goodsField = ['wl_goods_id','goods_plugin','title','goods_img','price_type','price','price2'];
        foreach($list as &$item){
            //商品信息处理
            $goodsIds = json_decode($item['goods_list'],true);
            $item['goods'] = pdo_getall(PDO_NAME."live_goods",['goods_id IN'=>$goodsIds],$goodsField);
            //时间信息处理
            $item['start_time'] = date("Y-m-d H:i",$item['start_time']);
            $item['end_time'] = date("Y-m-d H:i",$item['end_time']);
        }
        //分页操作
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template("live/list");
    }
    /**
     * Comment: 添加直播间操作
     * Author: zzw
     * Date: 2020/10/27 14:36
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function addLive(){
        global $_W,$_GPC;
        if($_W['ispost']){
            $data = $_GPC['data'];
            $time = $_GPC['time'];
            $liveGoods = $_GPC['live_goods'];
            try{
                //判断信息数据是否完善
                !$data['name'] && Commons::sRenderError('请输入房间名称');
                !$data['coverImg'] && Commons::sRenderError('请上传背景图');
                !$data['anchorName'] && Commons::sRenderError('请输入主播昵称');
                !$data['anchorWechat'] && Commons::sRenderError('请输入主播微信');
                !$data['shareImg'] && Commons::sRenderError('请上传分享图');
                $wxAppModel = (new Live());
                //处理信息
                $coverImg = tomedia($data['coverImg']);
                $shareImg = tomedia($data['shareImg']);
                $data['coverImg']  = $wxAppModel->uploadTemporaryMaterial(tomedia($data['coverImg']));
                $data['shareImg']  = $wxAppModel->uploadTemporaryMaterial(tomedia($data['shareImg']));
                $data['startTime'] = strtotime($time['start']);
                $data['endTime']   = strtotime($time['end']);
                //直播间建立
                $liveRes = $wxAppModel->addLive($data);
                if($liveRes['errcode'] == 300036){
                    //主播未认证   进行认证操作
                    Commons::sRenderError('请进行主播认证操作',['qrcode_url'=>$liveRes['qrcode_url']]);
                }else{
                    //直播间添加成功  开始导入商品
                    if(is_array($liveGoods) && count($liveGoods) > 0){
                        $importParams = [
                            'ids' => $liveGoods,
                            'roomId' => $liveRes['roomId']
                        ];
                        $wxAppModel->importGoods($importParams);
                    }
                    //直播间添加成功  记录直播间信息
                    $liveInfo = [
                        'uniacid'     => $_W['uniacid'] ,
                        'aid'         => $_W['aid'] ,
                        'name'        => $data['name'] ,//直播间名称
                        'roomid'      => $liveRes['roomId'] ,//直播间ID
                        'cover_img'   => $coverImg ,//直播间背景图链接
                        'share_img'   => $shareImg ,//直播间分享图链接
                        'live_status' => 102 ,//直播间状态。101：直播中，102：未开始，103已结束，104禁播，105：暂停，106：异常，107：已过期
                        'start_time'  => $data['startTime'] ,//直播间开始时间
                        'end_time'    => $data['endTime'] ,//直播计划结束时间
                        'anchor_name' => $data['anchorName'] ,//主播名
                        'goods_list'  => json_encode($liveGoods) ,//当前直播间相关联的商品列表
                    ];
                    pdo_insert(PDO_NAME."live",$liveInfo);
                }

                Commons::sRenderSuccess("操作成功");
            }catch (Exception $e){
                Commons::sRenderError($e->getMessage());
            }catch (InvalidArgumentException $e){
                Commons::sRenderError($e->getMessage());
            }
        }

        include wl_template("live/add");
    }
    /**
     * Comment: 获取直播回放列表
     * Author: zzw
     * Date: 2020/10/27 17:01
     */
    public function getPlayback(){
        global $_W,$_GPC;
        try{
            //基本参数信息获取
            $id = $_GPC['id'] OR wl_json(0 , "不存在的直播间，请刷新重试！");
            $caCheKey = 'live_playback';//缓存键
            $caCheName = md5("uniacid={$_W['uniacid']}&aid={$_W['aid']}&room_id={$id}");//缓存名
            //获取缓存中的回放信息
            $info = Cache::getCache($caCheKey,$caCheName);
            if($info){
                $info = json_decode($info,true);
                if($info['end_time'] <= time()) $info = [];//清除缓存数据
            }
            //获取新的回放列表信息
            if(!is_array($info) || !$info['list']){
                $info = (new Live())->getWholePlayback($id);
                //循环处理信息
                foreach($info['list'] as $index => &$item){
                    $item['create_time'] = date("Y-m-d H:i:s",strtotime($item['create_time']));
                    $item['expire_time'] = date("Y-m-d H:i:s",strtotime($item['expire_time']));
                }
                $info['end_time'] = time() + (3600 * 34);//缓存一天
                Cache::setCache($caCheKey,$caCheName,json_encode($info));
            }

            include wl_template("live/playback");
        }catch (Exception $e){
            wl_json(0 , $e->getMessage());
        }
    }
    /**
     * Comment: 开播二维码获取
     * Author: zzw
     * Date: 2020/10/27 15:09
     */
    public function getOpenLiveQrCode(){
        global $_W,$_GPC;
        //生成二维码
        $path = "plugin-private://wx2b03c6e691cd7370/pages/live-player-plugin?room_id={$_GPC['id']}&type=9";
        $qrcode = WeApp::getQrCode($path , 'qrcode_live_' . md5($path) . '.png');
        $qrcode = tomedia($qrcode);

        include wl_template('live/qrcode');
    }
    /**
     * Comment: 直播间信息同步
     * Author: zzw
     * Date: 2020/10/27 16:26
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function infoSynchronization(){
        global $_W,$_GPC;
        //基本参数信息获取
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        //直播信息同步操作
        try{
            //初始化更新状态
            if($page == 1) pdo_update(PDO_NAME."live",['is_update'=>0]);
            //循环处理直播间信息
            $totalPage = (new Live())->liveInfoSynchronization($pageStart,$pageIndex);
            //删除未更新的直播间
            if($page == $totalPage || $totalPage == 0) pdo_delete(PDO_NAME."live",['is_update'=>0]);

            Commons::sRenderSuccess("操作成功",['total_page'=>$totalPage]);
        }catch (Exception $e){
            Commons::sRenderError($e->getMessage());
        }catch (InvalidArgumentException $e){
            Commons::sRenderError($e->getMessage());
        }
    }

}
