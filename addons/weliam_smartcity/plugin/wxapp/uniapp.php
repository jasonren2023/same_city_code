<?php
defined('IN_IA') or exit('Access Denied');

class WxappModuleUniapp extends Uniapp {
    /**
     * Comment: 获取小程序直播间列表信息
     * Author: zzw
     * Date: 2020/2/27 9:32
     */
    public function liveList(){
        global $_W,$_GPC;
        #1、参数接收
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        if($_W['source'] != 3){
            $link = h5_url('pages/subPages/live/index',[],'',$_W['aid']);
            $path = explode('#/', $link)[1];
            //小程序码名称（带logo）
            $filePath = 'addons/' . MODULE_NAME . '/data/qrcode/'.$_W['uniacid']. '/';//保存路径
            $savePath = IA_ROOT . '/' . $filePath;//保存完整路径
            $filename = 'wxappLiveIndex.png';
            $qrCodeLink3 = $savePath.$filename;
            if(!file_exists($qrCodeLink3)){
                $qrCodeLink3 = WeApp::getQrCode($path,$filename);
            }
            $data = [
                'status' => 1,
                'qrimg'  => tomedia($qrCodeLink3)
            ];
            $this->renderSuccess('小程序二维码',$data);
        }
        #2、信息获取
        try{
            //直播间信息列表获取
            $info = Wxapp::getLiveList($page,$pageIndex);

            $this->renderSuccess('直播间列表',$info);
        }catch (Exception $e){
            $this->renderError($e->getMessage());
        }
    }
    /**
     * Comment: 获取直播回放列表
     * Author: zzw
     * Date: 2020/4/8 17:56
     */
    public function playBack(){
        global $_W,$_GPC;
        try{
            //基本参数信息获取
            $page      = $_GPC['page'] ? : 1;
            $pageIndex = $_GPC['page_index'] ? : 10;
            $id        = $_GPC['room_id'] OR $this->renderError('错误的直播间房号');
            //条件生成
            $params = [
                'action'  => 'get_replay' ,
                'room_id' => $id ,
                'start'   => $page * $pageIndex - $pageIndex,// 起始拉取房间，start = 0 表示从第 1 个房间开始拉取
                'limit'   => $pageIndex ,// 每次拉取的个数上限，不要设置过大，建议 100 以内
            ];
            //获取信息并且进行缓存
            $caCheKey = 'api_live_playback';//缓存键
            $caCheName = md5("uniacid={$_W['uniacid']}&room_id={$id}");//缓存名
            //获取缓存中的回放信息
            $info = Cache::getCache($caCheKey,$caCheName);
            if($info){
                $info = json_decode($info,true);
                if($info['end_time'] <= time()) $info = [];//清除缓存数据
            }
            //获取新的回放列表信息
            if(!is_array($info) || !$info['list']){
                $info = (new Live())->getPlaybackList($params);
                //循环处理信息
                foreach($info['list'] as $index => &$item){
                    $item['create_time'] = date("Y-m-d H:i:s",strtotime($item['create_time']));
                    $item['expire_time'] = date("Y-m-d H:i:s",strtotime($item['expire_time']));
                }
                $info['live_replay'] = $info['list'];
                unset($info['list']);
                $info['end_time'] = time() + (3600 * 34);//缓存一天
                Cache::setCache($caCheKey,$caCheName,json_encode($info));
            }

            $this->renderSuccess('直播间列表',$info);
        }catch (Exception $e){
            $this->renderError($e->getMessage());
        }
    }
    /**
     * Comment: 获取小程序初始化页面(L304定制)
     * Author: wlf
     * Date: 2020/06/23 14:58
     */
    public function newLiveList(){
        global $_W,$_GPC;
        #1、参数接收
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        if($_W['source'] != 3){
            $link = h5_url('pages/subPages/live/index',[],'',$_W['aid']);
            $path = explode('#/', $link)[1];
            //小程序码名称（带logo）
            $filePath = 'addons/' . MODULE_NAME . '/data/qrcode/'.$_W['uniacid']. '/';//保存路径
            $savePath = IA_ROOT . '/' . $filePath;//保存完整路径
            $filename = 'wxappLiveIndex.png';
            $qrCodeLink3 = $savePath.$filename;
            if(!file_exists($qrCodeLink3)){
                $qrCodeLink3 = WeApp::getQrCode($path,$filename);
            }
            $data = [
                'status' => 1,
                'qrimg'  => tomedia($qrCodeLink3)
            ];
            $this->renderSuccess('小程序二维码',$data);
        }
        #2、信息获取
        try{
            //直播间信息列表获取
            $info = Wxapp::getLiveList($page,$pageIndex);
            $advs = pdo_getall('wlmerchant_adv',array('uniacid' => $_W['uniacid'],'type'=> 120 ,'enabled'=>1),array('thumb','link'),'','displayorder DESC');
            if(!empty($advs)){
                foreach($advs as &$ad){
                    $ad['thumb'] = tomedia($ad['thumb']);
                }
                $info['advs'] = $advs;
            }else{
                $info['advs'] = [];
            }
            $info['bgc'] = $_W['wlsetting']['wxappset']['livebgc'] ? $_W['wlsetting']['wxappset']['livebgc'] : '#F8F8F8';

            $this->renderSuccess('直播间列表',$info);
        }catch (Exception $e){
            $this->renderError($e->getMessage());
        }
    }


}
