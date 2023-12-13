<?php
defined('IN_IA') or exit('Access Denied');

class PocketModuleUniapp extends Uniapp {
    /**
     * Comment: 获取掌上信息列表(平台首页)
     * Author: zzw
     * Date: 2019/8/7 11:58
     */
    public function homeList(){
        global $_GPC, $_W;
        $set = Setting::agentsetting_read('pocket');
        if(empty($set['status'])){
            $this->renderError('掌上信息已关闭');
        }
        #1、参数获取
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $page_index = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $page_start = $page * $page_index - $page_index;

        $set = Setting::agentsetting_read("pluginlist");
        #2、生成基本查询条件
        $where = " (a.aid = {$_W['aid']} OR a.fullchnnel > 0) AND a.uniacid = {$_W['uniacid']} AND a.status = 0 ";
        #3、生成排序条件
        switch ($set['tcsort']) {
            case 1:$order = " ORDER BY a.refreshtime DESC";break;//发帖时间
            case 2:$order = " ORDER BY a.look DESC ";break;//浏览人气
            case 3:$order = " ORDER BY a.share DESC ";break;//分享数量
            case 4:$order = " ORDER BY a.likenum DESC ";break;//点赞数量
            case 5:$order = " ORDER BY a.replytime DESC ";break;//回复时间
        }
        #4、获取信息列表
        $field = " a.top,a.img,a.id,a.avatar,a.nickname,b.title as one_name,c.title as two_name,a.phone,a.createtime,a.content,a.look,a.likenum ";
        $sql = "SELECT {$field} FROM "
            .tablename(PDO_NAME . "pocket_informations")
            ." as a LEFT JOIN ".tablename(PDO_NAME."pocket_type")
            ." as b ON a.onetype = b.id LEFT JOIN ".tablename(PDO_NAME."pocket_type")
            ." as c ON a.type = c.id "
            ." WHERE {$where}{$order}"." LIMIT {$page_start},{$page_index} ";
        $info = pdo_fetchall($sql);
        #6、循环处理信息
        foreach ($info as $key => &$val) {
            //生成链接信息
            $val['url'] = h5_url('pages/subPages/postDetails/postDetails',['id'=>$val['id']]);
            $val['createtime'] = date("m-d",$val['createtime']);
            //图片信息处理
            $imgArr = unserialize($val['img']);
            if(is_array($imgArr) && count($imgArr) > 0) {
                foreach($imgArr as $imgK => $imgV){
                    if(empty($imgV)){
                        unset($imgArr[$imgK]);
                    }else{
                        $val['img_list'][] = tomedia($imgV);
                    }
                }
            }
            unset($val['img']);
        }
        $this->renderSuccess('掌上信息列表',$info);
    }
    /**
     * Comment: 掌上信息首页信息
     * Author: zzw
     * Date: 2019/8/21 14:18
     */
    public function homeInfo(){
        global $_W,$_GPC;
        #1、设置信息获取
        $set = Setting::agentsetting_read('pocket');
        if(empty($set['status'])){
            $this->renderError('掌上信息已关闭');
        }
        #2、幻灯片信息获取
        $adv = pdo_getall(PDO_NAME."pocket_slide"
            ,['aid'=>$_W['aid'],'uniacid'=>$_W['uniacid'],'status'=>1]
            ,['img','url'],'','sort DESC');
        if(is_array($adv) && count($adv) > 0){
            foreach($adv as $advK => &$advV){
                $advV['img'] = tomedia($advV['img']);
            }
        }
        #3、获取统计信息
        $look  = $set['look'] > 0 ? intval($set['look']) : 0;//浏览量
        $total = $set['fabu'] > 0 ? intval($set['fabu']) : 0;//发布数
        $share = $set['share'] > 0 ? intval($set['share']) : 0;//分享数
        $info = pdo_fetch("SELECT (IFNULL(SUM(look),0) + {$look}) AS look,(IFNULL(COUNT(*),0) + {$total}) as total,(IFNULL(SUM(share),0) + {$share})AS share FROM "
            .tablename(PDO_NAME."pocket_informations")
            ." WHERE status = 0 AND aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} ");
        #4、获取分类列表信息
        $cateList = pdo_getall(PDO_NAME."pocket_type"
            ,['aid'=>$_W['aid'],'uniacid'=>$_W['uniacid'],'status'=>1,'type'=>0]
            ,['id','title','img','color','isnav','url','adv'],'','sort DESC');
        foreach($cateList as $key => &$val){
            $val['img'] = tomedia($val['img']);
            //幻灯片信息处理
            if($val['adv']){
                $val['adv'] = unserialize($val['adv']);
                foreach($val['adv'] as &$advImage){
                    $advImage = tomedia($advImage);
                }
            }else{
                $val['adv'] = [];
            }
        }
        #5、获取最新发布信息列表
        $releaseList = pdo_fetchall("SELECT FROM_UNIXTIME(a.createtime,'%y-%m-%d') as createtime,a.nickname,b.title,a.id FROM "
            .tablename(PDO_NAME."pocket_informations")
            ." as a RIGHT JOIN ".tablename(PDO_NAME."pocket_type")
            ." as b ON a.onetype = b.id WHERE a.status = 0 AND a.aid = {$_W['aid']} AND a.uniacid = {$_W['uniacid']} AND a.onetype > 0 ORDER BY createtime DESC LIMIT 10 ");
        #6、数据拼装
        $data['adv'] = $adv;//幻灯片
        $data['info'] = $info;//统计信息
        $data['cate_list'] = $cateList;//分类列表
        $data['release_list'] = $releaseList;//最新发布信息列表
        $data['set'] = [
            'text'=>$set['pocketname'],
            'status'=>$set['status']
        ];//设置信息

        $this->renderSuccess('掌上信息首页信息',$data);
    }
    /**
     * Comment: 获取分类列表
     * Author: zzw
     * Date: 2019/8/21 16:37
     */
    public function classList(){
        global $_W,$_GPC;
        #1、参数获取
        $is_two = $_GPC['is_two'] ? : 0;//0=不获取二级分类；1=获取二级分类
        $is_set = $_GPC['is_set'] ? : 0;//0=不获取设置信息；1=获取设置信息
        $is_ios = $_GPC['is_ios'] ? : 0;//0=安卓 1=IOS
        $areaid = $_GPC['areaid'] ? : 0;//0=默认 地区参数
        $onetype = 0;
        if(Customized::init('transfer1510') > 0){
            $onetype = $_GPC['onetype'];
            if($onetype > 0){
                $flag = pdo_get('wlmerchant_pocket_type',array('type' => $onetype,'status' => 1),array('id'));
                if(empty($flag)){
                    $this->renderSuccess('直接跳转',['url' => h5_url('pages/subPages/postRelease/postRelease',['onetype' => $onetype,'type' => $onetype])]);
                }
            }
        }
        $aid = $_W['aid'];
        if($areaid > 0 ){
            $aid = pdo_getcolumn(PDO_NAME.'oparea',array('uniacid'=>$_W['uniacid'],'status'=>1,'areaid' => $areaid),'aid');
            if(empty($aid)){
                $this->renderSuccess('无信息',[]);
            }
        }
        #2、基本信息配置
        $vipflag = WeliamWeChat::VipVerification($_W['mid'],true);
        $table = PDO_NAME."pocket_type";
        if($onetype > 0){
            $where = ['aid'=>$aid,'uniacid'=>$_W['uniacid'],'status'=>1,'type'=>$onetype,'isnav'=>0];//启用、一级分类、普通分类（不为导航标签）
        }else{
            $where = ['aid'=>$aid,'uniacid'=>$_W['uniacid'],'status'=>1,'type'=>0,'isnav'=>0];//启用、一级分类、普通分类（不为导航标签）
        }
        if($is_ios > 0 && $_W['wlsetting']['base']['payclose'] > 0){
            $where['price <'] = '0.01';
        }
        //用户禁止发布
        $where['userre'] = 0;

        $filed = ['id','title','img','price','vipstatus','vipprice'];
        $sort  = 'sort DESC';
        #3、获取一级分类列表
        $list = pdo_getall($table,$where ,$filed,'',$sort);
        #4、循环获取二级分类信息
        foreach($list as $key => &$val){
            //一级分类数据处理
            $val['img'] = tomedia($val['img']);
            //获取二级分类
            if($val['vipstatus'] == 2 && empty($vipflag)){
                $val['noflag'] = 1;
            }
            if($is_two == 1 && empty($onetype)){
                $where['type'] = $val['id'];
                $val['list'] = pdo_getall($table,$where ,$filed,'',$sort);
                foreach($val['list'] as $st){
                    if($st['vipstatus'] == 2 && empty($vipflag)){
                        $st['noflag'] = 1;
                    }
                }
            }else{
                $val['list'] = [];
            }
        }
        #5、获取设置信息
        if($is_set == 1){
            $set = Setting::agentsetting_read('pocket');
            $data['set'] = [
                'statement'=>$set['statement'],
            ];
        }
        #5、数据拼装
        $data['list'] = $list;

        $this->renderSuccess('掌上信息分类列表',$data);
    }
    /**
     * Comment: 信息列表获取(信息中心)
     * Author: zzw
     * Date: 2019/8/21 15:25
     */
    public function infoList(){
        global $_W,$_GPC;
        $set = Setting::agentsetting_read('pocket');
        $pluset = Setting::agentsetting_read("pluginlist");
        #1、参数接收
        $id = $_GPC['id'] ?  : 0;//一级分类id  0=全部
        $twoId = $_GPC['two_id'] ? : 0;//二级分类id 0=全部
        $page = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $sort = $_GPC['sort'] ? : 0;//排序方式
        $regionId = $_GPC['region_id'] ? : 0;
        $keyWord = trim($_GPC['keyword']) ? : '';

        if(empty($id)){
            $id = $_GPC['cate_id'] ?  : 0;
        }
        if($regionId > 0){
            $getAidinfo = pdo_get(PDO_NAME."oparea",['areaid'=>$regionId,'status'=>1,'uniacid'=>$_W['uniacid']]);
            if(empty($getAidinfo)){
                $aid = -1;
            }else{
                $aid = $getAidinfo['aid'];
            }
        }else{
            $aid = $_W['aid'];
        }
        //判断会员权限
        $userhalfcard = WeliamWeChat::VipVerification($_W['mid']);
        if($set['vip_show'] > 0){
            $vip_level = unserialize($set['vip_level']);
            if(empty($userhalfcard)){
                $noVip = 1;
            }else if(!empty($vip_level) && !in_array($userhalfcard['levelid'],$vip_level)){
                $noVip = 1;
            }
        }

        #2、生成查询条件
        $where = " WHERE (aid = {$aid} OR fullchnnel > 0)  AND uniacid = {$_W['uniacid']} AND status = 0 ";
        if($id > 0 ) $where .= " AND onetype IN (0,{$id}) ";
        if($twoId > 0) $where .= " AND type = {$twoId} ";
        if(!empty($keyWord)) $where .= " AND (keyword LIKE '%{$keyWord}%' OR content LIKE '%{$keyWord}%') ";
        #3、获取排序方式 0=发帖时间；1=浏览数量；2=分享数量；3=点赞数量；
        //首页标记
        if(!empty($_GPC['homeflag'])){
            $sort = $set['listorder'];
        }else if(empty($sort)){
            switch ($pluset['tcsort']){
                case 1:
                    $sort = 0;
                    break;
                case 2:
                    $sort = 1;
                    break;
                case 3:
                    $sort = 2;
                    break;
                case 4:
                    $sort = 3;
                    break;
                case 5:
                    $sort = 4;
                    break;
                default:
                    $sort = 0;
                    break;
            }
        }
        switch ($sort){
            case 0:$order = " ORDER BY top DESC,refreshtime DESC ";break;//发帖时间
            case 1:$order = " ORDER BY top DESC,look DESC ";break;//浏览数量
            case 2:$order = " ORDER BY top DESC,share DESC ";break;//分享数量
            case 3:$order = " ORDER BY top DESC,likenum DESC ";break;//点赞数量
            case 4:$order = " ORDER BY top DESC,replytime DESC ";break;//回复时间
        }
        #4、查询符合条件的信息列表
        $total = pdo_fetchcolumn("SELECT count(*) FROM " .tablename(PDO_NAME."pocket_informations") .$where.$order);
        $data['total'] = ceil($total / $pageIndex);
        $data['list'] = pdo_fetchall("SELECT id,avatar,mid,nickname,top,FROM_UNIXTIME(createtime,'%Y-%m-%d %H:%i') as createtime,img,
                                     phone,content,video_link,look,share,package,likeids,onetype,`type`,keyword,videoprice,linkplugin,linkid,transferstatus,transfermoney,refreshtime FROM "
            .tablename(PDO_NAME."pocket_informations") .$where.$order." LIMIT {$pageStart},{$pageIndex} ");
        #4、循环进行信息的处理
        if(is_array($data['list']) && count($data['list']) > 0){
            foreach($data['list'] as $key => &$val){
                $fabulous = unserialize($val['likeids']);
                if(!empty($val['refreshtime'])){
                    $val['createtime'] = date("Y-m-d H:i",$val['refreshtime']);
                }
                $videoInfo = UploadFile::videoInfoHandle($val['video_link']);
                $val['video_link'] = $videoInfo['link'];
                $val['video_img_link'] = $videoInfo['img_link'];

                //获取点赞数量
                $val['fabulous_num'] = is_array($fabulous) ? count($fabulous) : 0;
                //判断当前用户是否已经点赞   0=未点赞  1=已点赞
                if(is_array($fabulous) && in_array($_W['mid'],$fabulous)) $val['is_fabulous'] = 1;
                else $val['is_fabulous'] = 0;
                //获取点赞用户的头像
                $val['fabulous_avatar'] = [];
                if($val['fabulous_num'] > 0){
                    foreach($fabulous as $item){
                        $val['fabulous_avatar'][] = pdo_getcolumn(PDO_NAME."member",['id'=>$item],'avatar');
                    }
                }
                //从新定义头像链接
                if($val['mid'] > 0){
                    $meminfo = pdo_get('wlmerchant_member',array('id' => $val['mid']),array('nickname','avatar','encodename'));
                    if(!empty($meminfo)){
                        $val['nickname'] = !empty($meminfo['encodename']) ? base64_decode($meminfo['encodename']) : $meminfo['nickname'];
                        $val['avatar'] = $meminfo['avatar'];
                    }
                }

                //图片处理
                $val['img'] = is_array(unserialize($val['img'])) ? unserialize($val['img']) : [];
                if(is_array($val['img']) && count($val['img']) > 0){
                    foreach ($val['img'] as $imgK => &$imgV){
                        if(empty($imgV)){
                            unset($val['img'][$imgK]);
                        }else{
                            $imgV = tomedia($imgV);
                        }
                    }
                }
                //处理标签
                $val['keyword'] = unserialize($val['keyword']);
                //分类获取
                if($val['onetype'] > 0) $val['onetype'] = pdo_getcolumn(PDO_NAME."pocket_type",['id'=>$val['onetype']],'title');
                else $val['onetype'] = '';
                if($val['type'] > 0){
                    $val['type'] = pdo_getcolumn(PDO_NAME."pocket_type",['id'=>$val['type']],'title');
                } else{
                    $val['type'] = '官方公告';
                    $val['onetype'] = '官方公告';
                    $val['nickname'] = $set['kefu_name'];
                    $val['avatar'] = $set['kefu_avatar'];
                }
                $val['avatar'] = tomedia($val['avatar']);
                //评论获取 获取三条
                $val['comment_list'] = Pocket::getComment(1,3,$val['id']);
                $val['comment_list']['list'] = Pocket::cutComment($val['comment_list']['list'],3);
                unset($val['likeids']);
                //修改首页加载时的浏览量
                if(empty($set['listadd'])){
                    $addLook = rand(intval($set['minup']),intval($set['maxup']));
                    if($addLook<1){
                        $addLook = 1;
                    }
                    pdo_update(PDO_NAME."pocket_informations",['look'=>$val['look']+$addLook],['id'=>$val['id']]);
                }
                //查询认证和保证金
                if(p('attestation')){
                    $val['attestation'] = Attestation::checkAttestation(1,$val['mid']);
                }else{
                    $val['attestation'] = 0;
                }
                //查阅权限
                if($noVip > 0 || empty($val['phone'])){
                    unset($val['phone']);
                }
                //关联信息
                if($val['linkid'] > 0){
                    $linkinfo = Pocket::getLinkGoods($val['linkid'],$val['linkplugin']);
                    $val['linkname'] = $linkinfo['linkname'];
                    $val['linkthumb'] = tomedia($linkinfo['thumb']);
                }
                //1500定制信息处理
                $showmoney = Pocket::getShowMoney($userhalfcard,$val['videoprice']);
                if($showmoney != 0){
                    $videoflag = pdo_getcolumn(PDO_NAME.'pocket_payvideo',array('pocketid'=>$val['id'],'mid'=>$_W['mid']),'id');
                    if(empty($videoflag)){
//                        if($showmoney < 0){
//                            if(empty($userhalfcard)){
//                                $val['content'] = ' - 您无权查阅此帖子，请开通会员 -';
//                            }else{
//                                $val['content'] = ' - 您无权查阅此帖子，请开通高级会员 -';
//                            }
//                        }else{
//                            $val['content'] = ' - 请点击进入帖子详情付费查阅 -';
//                        }
                        $val['video_link'] = '';
                        $val['video_img_link'] = '';
                        $val['img'] = array_splice($val['img'],0,4);
                    }

                }
            }
        }
        //获取一级分类的子分类
        if(Customized::init('pocket140') > 0){
            if($id > 0 && empty($twoId)){
                $children_cates = pdo_getall('wlmerchant_pocket_type',array('uniacid' => $_W['uniacid'],'type' => $id,'status' => 1,'aid' => $_W['aid']),array('title','id'));
            }else if(empty($id)){
                $children_cates = pdo_getall('wlmerchant_pocket_type',array('uniacid' => $_W['uniacid'],'type' => 0,'status' => 1,'aid' => $_W['aid']),array('title','id'));
            }
            if(!empty($children_cates)){
                $all = [
                    'id' => 0,
                    'title' => '全部'
                ];
                array_unshift($children_cates,$all);
                $data['children'] = $children_cates;
            }
        }

        //获取用户默认地址
        if(Customized::init('transfer1510') > 0){
            $address = pdo_get('wlmerchant_address',array('mid' => $_W['mid'],'status' => 1),array('id','detailed_address'));
            if(empty($address)){
                $address = pdo_get('wlmerchant_address',array('mid' => $_W['mid']),array('id','detailed_address'));
            }
            $data['address'] = $address;
        }
        $this->renderSuccess('信息列表',$data);
    }
    /**
     * Comment: 获取帖子详细信息
     * Author: zzw
     * Date: 2019/8/21 17:51
     */
    public function detail(){
        global $_W,$_GPC;
        $set = Setting::agentsetting_read('pocket');
        if(empty($set['status'])){
            $this->renderError('掌上信息已关闭');
        }
        #1、参数获取
        $_GPC['id'] > 0 ? $id = $_GPC['id']  : $this->renderError('缺少参数：id') ;
        $lng = $_GPC['lng'] ? : 0;//经度
        $lat = $_GPC['lat'] ? : 0;//纬度
        #2、获取帖子基本信息
        $info = pdo_get(PDO_NAME."pocket_informations" ,['id'=>$id]
            ,['avatar','status','onetype','type','createtime','redpack','keyword','content','img','address','nickname','location','locastatus','aid'
                ,'look','share','likenum','likeids','phone','share_title','top','mid','video_link','videoprice','geredstatus','transferstatus'
                ,'transfermoney','transferom','linkplugin','linkid','diyformid']);
        if($info['status'] > 0 && $_W['mid'] != $info['mid']){
            if($_GPC['examine'] > 0){
                $agentuser = pdo_get('wlmerchant_agentadmin', array('uniacid' => $_W['uniacid'],'aid' => $info['aid'],'mid' => $_W['mid'], 'notice' => 1), array('noticeauthority'));
                $noticeauthority = unserialize($agentuser['noticeauthority']);
                if(empty($noticeauthority)) $noticeauthority = [];
                if(in_array('pocketfabu',$noticeauthority) || empty($noticeauthority)) {
                    $info['examineflag'] = 1;
                }
            }
            if(empty($info['examineflag'])){
                $this->renderError('帖子未通过审核或已删除');
            }
        }
        if($info['mid'] > 0){
            $memberinfo = pdo_get('wlmerchant_member',array('id' => $info['mid']),array('avatar','nickname','encodename'));
            $info['nickname'] = $memberinfo['encodename'] ? base64_decode($memberinfo['encodename']) : $memberinfo['nickname'];
            $info['avatar'] = $info['avatar'] ? : $memberinfo['avatar'];
        }
        #3、处理基本统计信息
        $info['look'] = ($info['look'] ? : 0);//浏览量
        $info['likenum'] = $info['likenum'] ? : 0;//点赞数
        $info['share'] = $info['share'] ? : 0;//分享数

        $info['avatar'] = tomedia($info['avatar']);
        $videoInfo = UploadFile::videoInfoHandle($info['video_link']);
        $info['video_link'] = $videoInfo['link'];
        $info['video_img_link'] = $videoInfo['img_link'];

        $info['createtime'] = date("Y-m-d H:i",$info['createtime']);
        $info['keyword'] = is_array(unserialize($info['keyword'])) ? unserialize($info['keyword']) : [];
        $info['location'] = is_array(unserialize($info['location'])) ? unserialize($info['location']) : [];
        //获取认证信息
        if(p('attestation')){
            $info['attestation'] = Attestation::checkAttestation(1,$info['mid']);
        }else{
            $info['attestation'] = 0;
        }
        //当前帖子浏览量增加
        $addLook = 1;
        if(intval($set['maxup']) > 0 ){
            $addLook = rand(intval($set['minup']),intval($set['maxup']));
        }
        $info['look'] = intval($info['look']) + intval($addLook);//浏览量
        pdo_update(PDO_NAME."pocket_informations",['look'=>$info['look']],['id'=>$id]);
        #4、处理图片信息
        $info['img'] = is_array(unserialize($info['img'])) ? unserialize($info['img']) : [];
        if(is_array($info['img']) && count($info['img']) > 0){
            foreach($info['img'] as $imgK => &$imgV){
                if(empty($imgV)){
                    unset($info['img'][$imgK]);
                }else{
                    $imgV = tomedia($imgV);
                }
            }
        }
        $userhalfcard = WeliamWeChat::VipVerification($_W['mid']);
        //判断帖子内容是否显示
        $showmoney = Pocket::getShowMoney($userhalfcard,$info['videoprice']);
        if($showmoney != 0){
            $videoflag = pdo_getcolumn(PDO_NAME.'pocket_payvideo',array('pocketid'=>$id,'mid'=>$_W['mid']),'id');
            if(empty($videoflag)){
                if($showmoney < 0){
//                    if(empty($userhalfcard)){
//                        $info['content'] = ' - 您无权查阅此帖子，请开通会员 -';
//                    }else{
//                        $info['content'] = ' - 您无权查阅此帖子，请开通高级会员 -';
//                    }
                    $info['hidevideo'] = 2;
                }else{
//                    $info['content'] = ' - 付费查阅后方可查阅帖子所有内容 -';
                    $info['hidevideo'] = 1;
                }
            }
        }
        $info['videoprice'] = $showmoney;
        $info['hidevideo'] = $info['hidevideo'] ? : 0;
        #5、处理分类信息
        if($info['onetype'] > 0){
            $onetype = pdo_get(PDO_NAME."pocket_type",['id'=>$info['onetype']],['title','commentswitch','replyswitch']);
            $info['one_name'] = $onetype['title'];
        } else{
            $info['one_name'] = '官方公告';
            $info['nickname'] = $set['kefu_name'];
            $info['avatar'] = tomedia($set['kefu_avatar']);
        }
        if($info['type'] > 0){
            $twotype = pdo_get(PDO_NAME."pocket_type",['id'=>$info['type']],['title','commentswitch','replyswitch']);
            $info['two_name'] = $twotype['title'];
        } else{
            $twotype = $onetype;
            $info['two_name'] = $info['one_name'];
        }
        unset($info['onetype']);
        unset($info['type']);
        #6、处理点赞信息
        $info['likeids'] = is_array(unserialize($info['likeids'])) ? unserialize($info['likeids']) : [];
        if(is_array($info['likeids']) && count($info['likeids']) > 0){
            //判断当前用户是否已经点赞  0=未点赞；1=已点赞
            if(in_array($_W['mid'],$info['likeids'])) $info['is_fabulous'] = 1;
            else $info['is_fabulous'] = 0;
            //循环获取点赞用户的头像信息
            foreach ($info['likeids'] as $idK => &$idV){
                $idV = pdo_getcolumn(PDO_NAME."member",['id'=>$idV],'avatar');
            }
            $info['likeids'] = array_values($info['likeids']);
        }
        $info['fabulous_num'] = is_array($info['likeids']) ? count($info['likeids']) : 0;
        #7、距离计算
        $distance = Store::getdistance($lng, $lat, $info['location']['lng'], $info['location']['lat']);
        if ($distance) {
            if ($distance > 1000) {
                $info['distance'] = (floor(($distance / 1000) * 10) / 10) . "km";
            } else {
                $info['distance'] = round($distance) . "m";
            }
        }
        #8、获取当前用户一共发送的帖子数量
        $info['info_total'] = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename(PDO_NAME."pocket_informations") ." WHERE mid = {$info['mid']} AND status = 0 ");
        #9、获取当前帖子的评论总数量
        $info['comment_total'] = pdo_fetchcolumn("SELECT count(*) FROM ".tablename(PDO_NAME."pocket_comment")." WHERE tid = {$id} ");
        #10、判断当前用户是否领取红包.0=无红包，1=有红包未领取；2=有红包已领取（有红包未领取但是红包已发放完毕）
        $info['res_status'] = 0;//默认无红包
        $info['tocash'] = 0;
        if($info['redpack'] > 0){
            //默认有红包未领取
            $info['res_status'] = 1;
            //判断是否领取红包
            $isGet = pdo_getcolumn(PDO_NAME."red_envelope",['pid'=>$id,'mid'=>$_W['mid']],'id');
            if($isGet > 0) {
                //有红包已领取
                $info['res_status'] = 2;
                if($_W['wlsetting']['cashset']['withdrawals'] > 0){
                    $info['tocash'] = 1;
                }
            }else{
                //有红包未领取但是红包已发放完毕
                $haveReceived = pdo_fetchcolumn("SELECT SUM(money) FROM ".tablename(PDO_NAME."red_envelope")." WHERE pid = {$id} ") ? : 0;
                if($info['redpack'] <= $haveReceived){
                    $info['res_status'] = 2;
                }
            }
        }
        #11、获取当前用户是否收藏当前帖子
        $info['is_collection'] = pdo_getcolumn(PDO_NAME."pocket_collection",['tid'=>$id,'mid'=>$_W['mid']],'id') ? : 0;
        //判断会员权限
        if($set['vip_show'] > 0){
            $vip_level = unserialize($set['vip_level']);
            if(empty($userhalfcard)){
                if(!empty($info['phone'])){
                    $info['tipsflag'] = 1;
                }
            }else if(!empty($vip_level) && !in_array($userhalfcard['levelid'],$vip_level)){
                if(!empty($info['phone'])){
                    $info['tipsflag'] = 1;
                }
            }
        }
        //转让定制地址信息
        if($info['transferstatus'] == 1){
            $address = pdo_get('wlmerchant_address' , ['mid' => $_W['mid'] , 'uniacid' => $_W['uniacid'] , 'status'  => 1] , ['id' , 'status' , 'name' , 'tel' , 'province' , 'city' , 'county' , 'detailed_address']);
            if(!empty($address)){
                $info['addressid'] = $address['id'];
                $info['address'] = $address['county'].$address['detailed_address'];
            }else{
                $info['addressid'] = 0;
                $info['address'] = '';
            }
        }
        //定制关联商户信息
        if($info['linkid'] > 0){
            $linkinfo = Pocket::getLinkGoods($info['linkid'],$info['linkplugin']);
            $info['linkname'] = $linkinfo['linkname'];
            $info['linkthumb'] = tomedia($linkinfo['thumb']);
        }
        //获取平台备注
        if(empty($set['commentStatus'])){
            $info['commentStatus'] = $twotype['commentswitch'] ? 0 : 1;
            $info['replyStatus'] = $twotype['replyswitch'] ? 0 : 1;
        }else{
            $info['commentStatus'] = 0;
            $info['replyStatus'] = 0;
        }
        //自定义表单
        if($info['diyformid'] > 0){
            $diyform = pdo_get(PDO_NAME.'diyform_list',array('id'=>$info['diyformid']),['formid','listinfo']);
            $forminfo = pdo_get('wlmerchant_diyform',array('id' => $diyform['formid']));
            $moinfo = json_decode(base64_decode($forminfo['info']) , true);
            $list = $moinfo['list'];
            $list = array_values($list);
            $info['diylist'] = Im::diyFormInfo($list,$diyform);
        }else{
            $info['diylist'] = [];
        }

        $info['remark'] = $set['remark'];
        $info['remarkcolor'] = $set['remarkcolor'];
        $this->renderSuccess('帖子详情',$info);
    }
    /**
     * Comment: 获取帖子的评论/回复信息
     * Author: zzw
     * Date: 2019/8/21 18:27
     */
    public function commentList(){
        global $_W,$_GPC;
        #1、参数信息获取
        $_GPC['id'] > 0 ? $id = $_GPC['id']  : $this->renderError('缺少参数：id') ;
        $page = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 5;
        #2、获取评论信息列表
        $list = Pocket::getComment($page,$pageIndex,$id);

        $this->renderSuccess('帖子的评论/回复信息',$list);
    }
    /**
     * Comment: 帖子点赞操作
     * Author: zzw
     * Date: 2019/8/22 9:45
     */
    public function fabulous(){
        global $_W,$_GPC;
        //检查锁
        if(lockTool($_W['mid'],'pocket')){
            $this->renderError('请稍后');
        }
        #1、参数接收
        $_GPC['id'] > 0 ? $id = $_GPC['id']  : $this->renderError('缺少参数：id') ;//帖子id
        #2、获取帖子的点赞信息
        $info = pdo_get(PDO_NAME."pocket_informations",['id'=>$id],['likeids','likenum']);
        $ids = unserialize($info['likeids']);
        $num = count($ids);
        #3、判断是否为重复操作
        if(is_array($ids) && $num > 0){
            if(in_array($_W['mid'],$ids)){
                #4、取消点赞的操作
                $ids = array_flip($ids);
                unset($ids[$_W['mid']]);
                $ids = array_flip($ids);
                $likenum = $info['likenum'] - 1;
            }else{
                $ids = array_values($ids);//初始化数组 重新生成键值 从0开始
                $ids[$num] = $_W['mid'];
                $likenum = $info['likenum'] + 1;
            }
        }else{
            $ids[$num] = $_W['mid'];
            $likenum = $info['likenum'] + 1;
        }
        #5、点赞成功的操作
        $res = pdo_update(PDO_NAME."pocket_informations",['likeids'=>serialize($ids),'likenum'=>$likenum],['id'=>$id]);
        if($res){
            if(Customized::init('pocket1500')){
                $set = Setting::agentsetting_read('pocket');
                Pocket::giveCredit($id,2,$_W['mid'],$set);
            }
            $this->renderSuccess("操作成功");
        } else{
            $this->renderError('操作失败');
        }
    }
    /**
     * Comment: 帖子评论
     * Author: zzw
     * Date: 2019/8/22 10:05
     */
    public function comment(){
        global $_W,$_GPC;
        //检查锁
        if(lockTool($_W['mid'],'pocket')){
            $this->renderError('请稍后');
        }
        //判断是否绑定手机
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array('private',$mastmobile)){
            $this->renderError('未绑定手机号');
        }
        #1、参数接收
        $id = $_GPC['id']  OR $this->renderError('缺少参数：id') ;//帖子id
        $text = $_GPC['text'] OR $this->renderError('请输入评论内容!') ;//评论内容
        //判断文本内容是否非法
        $textRes = Filter::init($text,$_W['source'],1);
        if($textRes['errno'] == 0){
            $this->renderError($textRes['message']);
        }
        #2、判断用户是否为黑名单用户
        $this->checkBlack();
        #3、评论信息拼装
        $data['uniacid']    = $_W['uniacid'];
        $data['aid']        = $_W['aid'];
        $data['tid']        = $id;
        $data['content']    = $text;
        $data['mid']        = $_W['mid'];
        $data['createtime'] = time();
        //判断是否需要审核
        $set = Setting::agentsetting_read('pocket');
        if($set['comment_reply'] == 1) $data['status'] = 0;
        #4、保存评论内容
        $res = pdo_insert(PDO_NAME."pocket_comment",$data);
        if($res){
            $cid = pdo_insertid();
            //修改帖子回复时间
            if(empty($set['comment_reply'])){
                pdo_update('wlmerchant_pocket_informations',array('replytime' => time()),array('id' => $id));
            }
            if(Customized::init('pocket1500')){
                Pocket::giveCredit($id,3,$_W['mid'],$set);
            }
            if($set['comment_reply'] != 1) Pocket::setModelInfo($id,$cid,$_W['source']);
            $this->renderSuccess('评论成功',['cid' => $cid,'amid' => $data['mid']]);
        } else {
            $this->renderError('评论失败，请稍后重试');
        }
    }

    /**
     * Comment: 编辑或删除评论信息
     * Author: wlf
     * Date: 2022/02/09 11:25
     */
    public function changeComment(){
        global $_W,$_GPC;
        $this->checkBlack();
        $id = $_GPC['id'] OR $this->renderError('缺少参数：id');//评论id
        $type = $_GPC['type'] ? : 0;  // 1修改 0删除
        if($type > 0){
            $text = $_GPC['text'] OR $this->renderError('请输入修改内容!');//评论内容
            //判断文本内容是否非法
            $textRes = Filter::init($text,$_W['source'],1);
            if($textRes['errno'] == 0){
                $this->renderError($textRes['message']);
            }
            $data['content'] = $text;
            //判断是否需要审核
            $set = Setting::agentsetting_read('pocket');
            if($set['comment_reply'] == 1) $data['status'] = 0;
            $res = pdo_update('wlmerchant_pocket_comment',$data,['id' => $id]);
        }else{
            $res = pdo_delete('wlmerchant_pocket_comment',array('id'=>$id));
        }
        if($res > 0){
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('操作失败，请稍后重试');
        }
    }


    /**
     * Comment: 回复评论信息
     * Author: zzw
     * Date: 2019/8/22 10:37
     */
    public function reply(){
        global $_W,$_GPC;
        //检查锁
        if(lockTool($_W['mid'],'pocket')){
            $this->renderError('请稍后');
        }
        //判断是否绑定手机
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array('private',$mastmobile)){
            $this->renderError('未绑定手机号');
        }
        $this->checkBlack();
        #1、参数接收
        $id = $_GPC['id'] OR $this->renderError('缺少参数：id');//帖子id
        $cid = $_GPC['cid'] OR $this->renderError('缺少参数：cid');//评论id
        $amid = $_GPC['amid'] OR $this->renderError('缺少参数：amid');//接收回复信息的用户的id
        $text = $_GPC['text'] OR $this->renderError('请输入回复内容!');//回复内容
        //判断文本内容是否非法
        $textRes = Filter::init($text,$_W['source'],1);
        if($textRes['errno'] == 0){
            $this->renderError($textRes['message']);
        }
        #2、判断用户是否为黑名单用户
        if(Pocket::is_black($id)) $this->renderError('对不起，您暂无回复权限!');
        #3、评论信息拼装
        $data['uniacid']    = $_W['uniacid'];
        $data['aid']        = $_W['aid'];
        $data['tid']        = $id;
        $data['cid']        = $cid;
        $data['smid']       = $_W['mid'];//回复人
        $data['amid']       = $amid;//被回复人,接收消息的用户
        $data['content']    = $text;
        $data['createtime'] = time();
        //判断是否需要审核
        $set = Setting::agentsetting_read('pocket');
        if($set['comment_reply'] == 1) $data['status'] = 0;
        #4、保存评论内容
        $res = pdo_insert(PDO_NAME."pocket_reply",$data);
        if($res) {
            $rid = pdo_insertid();
            //修改帖子回复时间
            if(empty($set['comment_reply'])){
                pdo_update('wlmerchant_pocket_informations',array('replytime' => time()),array('id' => $id));
            }
            if(Customized::init('pocket1500')){
                Pocket::giveCredit($id,3,$_W['mid'],$set);
            }
            if($set['comment_reply'] != 1) Pocket::setReplyModelInfo($id,$rid,$data['smid'],$data['amid'],$_W['source']);
            $this->renderSuccess('回复成功',$rid);
        } else{
            $this->renderError('回复失败，请稍后重试');
        }
    }

    /**
     * Comment: 编辑或删除回复信息
     * Author: wlf
     * Date: 2022/02/09 11:15
     */
    public function changeReply(){
        global $_W,$_GPC;
        $this->checkBlack();
        $id = $_GPC['id'] OR $this->renderError('缺少参数：id');//回复id
        $type = $_GPC['type'] ? : 0;  // 1修改 0删除
        if($type > 0){
            $text = $_GPC['text'] OR $this->renderError('请输入修改内容!');//回复内容
            //判断文本内容是否非法
            $textRes = Filter::init($text,$_W['source'],1);
            if($textRes['errno'] == 0){
                $this->renderError($textRes['message']);
            }
            $data['content'] = $text;
            //判断是否需要审核
            $set = Setting::agentsetting_read('pocket');
            if($set['comment_reply'] == 1) $data['status'] = 0;
            $res = pdo_update(PDO_NAME."pocket_reply",$data,['id' => $id]);
        }else{
            $res = pdo_delete(PDO_NAME."pocket_reply",array('id'=>$id));
        }
        if($res > 0){
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('操作失败，请稍后重试');
        }
    }




    /**
     * Comment: 获取我的帖子列表
     * Author: zzw
     * Date: 2019/8/22 11:48
     */
    public function userList(){
        global $_W,$_GPC;
        #1、参数接收
        $status = $_GPC['status'];//-1=全部;0=显示;1=审核中;2=不显示(未通过);3=已删除;5=未支付
        $page = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $mid = $_GPC['mid'] ? : $_W['mid'];
        #2、生成查询条件
        $where = " WHERE uniacid = {$_W['uniacid']} AND mid = {$mid} ";
        if($status > -1) $where .= " AND status = {$status} ";
        else $where .= " AND status IN (0,1,2,5) ";
        #3、获取列表信息
        $sql = "SELECT id,avatar,nickname,mid,top,FROM_UNIXTIME(createtime,'%Y-%m-%d %H-%i') as createtime,img,
                phone,content,look,video_link,share,package,likeids,onetype,type,keyword,share_title,status,reason,fullchnnel,redpackstatus,transferstatus FROM "
            .tablename(PDO_NAME."pocket_informations")
            .$where." ORDER BY top DESC,refreshtime DESC LIMIT {$pageStart},{$pageIndex} ";
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".tablename(PDO_NAME."pocket_informations").$where);
        $list = pdo_fetchall($sql);

        //判断会员权限
        $set = Setting::agentsetting_read('pocket');
        if($set['vip_show'] > 0){
            $vip_level = unserialize($set['vip_level']);
            $userhalfcard = WeliamWeChat::VipVerification($_W['mid']);
            if(empty($userhalfcard)){
                $noVip = 1;
            }else if(!empty($vip_level) && !in_array($userhalfcard['levelid'],$vip_level)){
                $noVip = 1;
            }
        }

        #4、循环进行信息的处理
        if(is_array($list) && count($list) > 0){
            foreach($list as $key => &$val){
                $fabulous = unserialize($val['likeids']);
                $videoInfo = UploadFile::videoInfoHandle($val['video_link']);
                $val['video_link'] = $videoInfo['link'];
                $val['video_img_link'] = $videoInfo['img_link'];
                //获取点赞数量
                $val['fabulous_num'] = is_array($fabulous) ? count($fabulous) : 0;
                //判断当前用户是否已经点赞   0=未点赞  1=已点赞
                if(is_array($fabulous) && in_array($_W['mid'],$fabulous)) $val['is_fabulous'] = 1;
                else $val['is_fabulous'] = 0;
                //获取点赞用户的头像
                $val['fabulous_avatar'] = [];
                if($val['fabulous_num'] > 0){
                    foreach($fabulous as $item){
                        $val['fabulous_avatar'][] = pdo_getcolumn(PDO_NAME."member",['id'=>$item],'avatar');
                    }
                }
                //从新定义头像链接
                $memberinfo = pdo_get('wlmerchant_member',array('id' => $val['mid']),array('avatar','nickname','encodename'));
                $val['nickname'] = !empty($memberinfo['encodename']) ? base64_decode($memberinfo['encodename']) : $memberinfo['nickname'];
                $val['avatar'] = $val['avatar'] ? : $memberinfo['avatar'];
                $val['avatar'] = tomedia($val['avatar']);
                //图片处理
                $val['img'] = unserialize($val['img']);
                if(is_array($val['img']) && count($val['img']) > 0){
                    foreach ($val['img'] as $imgK => &$imgV){
                        if(empty($imgV)){
                            unset($val['img'][$imgK]);
                        }else{
                            $imgV = tomedia($imgV);
                        }
                    }
                    $val['shareimg'] = $val['img'][0];
                }else{
                    $val['shareimg'] = $val['avatar'];
                    $val['img'] = [];
                }
                //处理标签
                $val['keyword'] = is_array(unserialize($val['keyword'])) ? unserialize($val['keyword']) : [];
                //查阅权限
                if($noVip > 0 || empty($val['phone'])){
                    unset($val['phone']);
                }
                //未支付订单
                if($val['status'] == 5){
                    $val['orderid'] = pdo_getcolumn(PDO_NAME.'order',array('fightstatus'=>1,'plugin'=>'pocket','fkid' => $val['id']),'id');
                }
                //转让信息
                if($val['transferstatus'] == 2){
                    $orderinfo = pdo_get('wlmerchant_order',array('fkid' => $val['id'],'fightstatus' => 7,'status' =>3),array('mid','expressid'));
                    $buymember = pdo_get('wlmerchant_member',array('id' => $orderinfo['mid']),array('nickname','encodename','avatar','mobile'));
                    $express = pdo_get('wlmerchant_address',array('id' => $orderinfo['expressid']),array('detailed_address'));
                    $val['buynickname'] = !empty($buymember['encodename']) ? base64_decode(($buymember['encodename'])) : $val['nickname'];
                    $val['buyavatar'] = tomedia($buymember['avatar']);
                    $val['buymobile'] = $buymember['mobile'];
                    $val['address'] = $express['detailed_address'];
                }
                //分类获取
                if($val['onetype'] > 0) $val['onetype'] = pdo_getcolumn(PDO_NAME."pocket_type",['id'=>$val['onetype']],'title');
                else $val['onetype'] = '';
                if($val['type'] > 0) $val['type'] = pdo_getcolumn(PDO_NAME."pocket_type",['id'=>$val['type']],'title');
                else $val['type'] = '';
                unset($val['likeids']);
            }
        }
        #4、数据拼装
        $data['total'] = ceil($total / $pageIndex);
        $data['list'] = $list;

        $this->renderSuccess('我的帖子列表',$data);
    }
    /**
     * Comment: 创建发帖订单
     * Author: wlf
     * Date: 2019/8/22 11:57
     */
    public function createOrder(){
        global $_W,$_GPC;
        $fk_id = $_GPC['id'];
        $num = $_GPC['num']; //数量
        $getmoney = $_GPC['money']; //红包金额
        $payfor = $_GPC['payfor'];  // 1发帖 2置顶 3红包 4刷新 5观看视频 6推广 7转让
        $tiezi = pdo_get('wlmerchant_pocket_informations',array('id' => $fk_id),array('transfermoney','status','top','type','mid','aid'));
        $typeid = $tiezi['type'];

        $redpagestatus = $_GPC['redpagestatus'] ? : 0; //红包类型  0手气红包  1均分红包
        $geredstatus = $_GPC['geredstatus'] ? : 0; //红包发放方式  0直接领取  1分享领取
        $addressid = $_GPC['addressid'] ? : 0; //地址id

        $data = Setting::agentsetting_read('pocket');
        $userhalf = WeliamWeChat::VipVerification($_W['mid']);
        $isVip = empty($userhalf) ? 0 : $userhalf['id'];
        $mid = $tiezi['mid'];
        $orderprice = 0;
        if($payfor == 1){
            $typeinfo = pdo_get(PDO_NAME . 'pocket_type', array('id' => $typeid), ['price','vipprice','vipstatus']);
            if($typeinfo['vipstatus'] == 1 && $isVip > 0){
                $orderprice = $typeinfo['vipprice'];
            }else{
                $orderprice = $typeinfo['price'];
            }
        }else if($payfor == 2){
            if($data['number']>0){
                $nowtop = pdo_getcolumn(PDO_NAME."pocket_informations",['uniacid'=>$_W['uniacid'],'top'=>1,'status'=>0],'count(id)');
                if($nowtop>$data['number'] && empty($tiezi['top']) ){
                    $this -> renderError('平台置顶数量已达上限，请稍后再试');
                }
            }
            $price = $data['price'];
            foreach ($price as $key => $v) {
                if ($num == $v['day']) {
                    $orderprice = $isVip > 0 ? $v['vipprice'] : $v['price'];
                }
            }
            //免费置顶
            if($orderprice < 0.01){
                $inf['top'] = 1;
                if($tiezi['endtime']>time()){
                    $inf['endtime'] = $tiezi['endtime'] + $num * 24 * 3600;
                }else{
                    $inf['endtime'] = time() + $num * 24 * 3600;
                }
                $inf['refreshtime'] = time();
                $res = pdo_update('wlmerchant_pocket_informations', $inf, array('id' => $fk_id));
                if($res > 0){
                    $this->renderSuccess('置顶成功');
                }else{
                    $this->renderError('置顶失败，请稍后重试');
                }
            }
        }else if($payfor == 3){
            $price = $data['red_envelopes'];
            foreach ($price as $key => $v) {
                if ($num == $v['num'] && $getmoney == $v['red_price']){
                    $orderprice = $v['red_price'];
                }
            }
            pdo_update('wlmerchant_pocket_informations',array('redpagestatus' => $redpagestatus,'geredstatus' => $geredstatus),array('id' => $fk_id));
        }else if($payfor == 4){
            $typeinfo = pdo_get(PDO_NAME . 'pocket_type', array('id' => $typeid), ['refreshprcie','refreshvip']);
            if( $isVip > 0){
                $orderprice = $typeinfo['refreshvip'];
            }else{
                $orderprice = $typeinfo['refreshprcie'];
            }
        }else if($payfor == 5){
            $pcoketinfo = pdo_get(PDO_NAME . 'pocket_informations', array('id' => $fk_id), ['videoprice']);
            $orderprice = Pocket::getShowMoney($userhalf,$pcoketinfo['videoprice']);
            $mid = $_W['mid'];
        }else if($payfor == 6){
            if($isVip > 0){
                $orderprice = $data['fullprice'];
            }else{
                $orderprice = $data['fullvip'];
            }
        }else if($payfor == 7){
            $orderprice = $tiezi['transfermoney'];
            $mid = $_W['mid'];
            if(empty($addressid)){
                $this -> renderError('请选择送货地址');
            }
        }
        if($orderprice > 0){
            $data = array(
                'uniacid'    => $_W['uniacid'],
                'mid'        => $mid,
                'aid'        => $tiezi['aid'],
                'fkid'       => $fk_id,
                'sid'        => 0,
                'status'     => 0,
                'paytype'    => 0,
                'createtime' => time(),
                'orderno'    => createUniontid(),
                'price'      => $orderprice,
                'num'        => $num,
                'plugin'     => 'pocket',
                'payfor'     => 'pocketfabusharge',
                'fightstatus'=> $payfor,
                'expressid'  => $addressid
            );
            $res = Pocket::saveFightOrder($data);
            if($res){
                $orderdata['orderid'] = $res;
                $this->renderSuccess('生成订单id',$orderdata);
            }else{
                $this -> renderError('生成订单失败，请刷新重试');
            }
        }else{
            $this -> renderError('无可支付项,请刷新重试');
        }

    }
    /**
     * Comment: 发布准备信息获取
     * Author: zzw
     * Date: 2019/8/22 15:38
     */
    public function preparation(){
        global $_W,$_GPC;
        #1、接收参数信息
        $_GPC['id'] ? $id = $_GPC['id'] : $this->renderError('缺少参数：id');
        #2、获取用户信息
        $data['user'] = [
            'nickname' => $_W['wlmember']['nickname'],
            'mobile' => $_W['wlmember']['mobile'],
        ];
        $data['usemoney'] = 1;
        $data['usecredit'] = 0;
        $vipflag = WeliamWeChat::VipVerification($_W['mid'],true);
        #3、获取当前分类标签信息
        $type = pdo_get(PDO_NAME."pocket_type",['id'=>$id]);
        if($type['vipstatus'] == 1 && $vipflag > 0){
            $data['price'] = $type['vipprice'];
        }else{
            $data['price'] = $type['price'];
        }
        $data['credit'] = $type['usecredit'] > 0 ? $type['usecredit'] : 0;
        if($data['credit'] > 0){
            $data['usecredit'] = 1;
            if($data['price'] < 0.01){
                $data['usemoney'] = 0;
            }
        }
        $label = $type['keyword'];
        $label = trim($label,',');
        if(strlen($label) > 0) $label = explode(',',$label);
        #4、数据拼装
        $set = Setting::agentsetting_read('pocket');
        $data['label'] = $label;//标签信息列表
        $data['set'] = [
            'locastatus' => $set['locastatus'],//是否定位：0=关闭，1=默认开启，2=强制开启
            'hideimg' => $set['imgupload'] ? : 0,  //是否隐藏图片上传
            'hidevideo' => $set['videoupload'] ? : 0, //是否隐藏视频上传
            'hideaudio' => $set['audioupload'] ? : 0, //是否隐藏音频上传
            'wxapptip' => $set['wxapptip'] ? : 0 //小程序是否提示订阅
        ]; //设置信息
        if(Customized::init('pocket140') > 0){
            $data['set']['hideimg'] = $type['imgupload'] ? : 0;
            $data['set']['hidevideo'] = $type['videoupload'] ? : 0;
            $data['set']['hideaudio'] = $type['audioupload'] ? : 0;
            $data['set']['contenttip'] = $type['contenttip'];
            $data['set']['hidetitle'] = $type['titleswitch'] ? : 0;
            $data['set']['hidetel'] = $type['telswitch'] ? : 0;
            $data['set']['hidecontacts'] = $type['contactsswitch'] ? : 0;
            $data['set']['linkinfo'] = 1;
        }
        //转让付费
        $data['set']['transfer'] = Customized::init('transfer1510') > 0 ? 1 : 0;

        if($set['wxapptip'] > 0 && $_W['source'] == 3){
            $tempSet = Setting::wlsetting_read('new_temp_set');
            if($tempSet['service']['weappSubscription']['status'] == 1){
                $data['set']['tempId'] =  $tempSet['service']['weappSubscription']['id'];
            }else{
                $data['set']['wxapptip'] = 0;
            }
        }
        //自定义表单
        if($type['diyformid'] > 0){
            $diyFromInfo       = pdo_getcolumn(PDO_NAME . 'diyform' , ['id' => $type['diyformid']] , 'info');
            $data['diyform']   = json_decode(base64_decode($diyFromInfo) , true);//页面的配置信息
            $data['diyformid'] = $type['diyformid'];
        }else{
            $data['diyform'] = [];
            $data['diyformid'] = 0;
        }
        $this->renderSuccess('发布准备信息',$data);
    }
    /**
     * Comment: 发布信息
     * Author: zzw
     * Date: 2019/8/22 16:28
     */
    public function release(){
        global $_W,$_GPC;
        //检查锁
        if(lockTool($_W['mid'],'pocket')){
            $this->renderError('请稍后');
        }

        $set = Setting::agentsetting_read('pocket');


        //判断是否绑定手机
        $this->checkBlack();
        $mastmobile = unserialize($_W['wlsetting']['userset']['plugin']);
        if (empty($_W['wlmember']['mobile']) && in_array('pocket',$mastmobile)){
            $this->renderError('未绑定手机号');
        }
        #1、参数接收
        $paytype = $_GPC['paytype'] ? : 0;  //0正常支付 1积分发帖
        $linkplugin = $_GPC['linkplugin'];
        $linkid = $_GPC['linkid'];

        $content = $_GPC['content'] OR $this->renderError('请输入具体内容');//具体内容
        $oneType = $_GPC['onetype'] OR $this->renderError('请先选择分类');//一级分类id
        $nickname = $_GPC['nickname'];//联系人姓名
        $phone = $_GPC['phone'];//电话
        $share_title = $_GPC['share_title'] ? : '';//分享标题
        $type       = $_GPC['type'] ? $_GPC['type'] : 0;//二级分类id
        $keyword    = $_GPC['keyword'] ? serialize(explode(',',$_GPC['keyword'])) : '';//关键词
        $locastatus = $_GPC['locastatus'] ? : 0;//定位开关 1开启 0关闭

        $typeinfo = pdo_get(PDO_NAME."pocket_type",['id'=>$type]);
        if(Customized::init('pocket140') > 0){
            $set['imgupload'] = $typeinfo['imgupload'];
            $set['audioupload'] = $typeinfo['audioupload'];
        }
        if(empty($set['imgupload'])){
            $img        = trim($_GPC['img']);
            if(!empty($img)){
                $img = explode(',',$_GPC['img']);
                foreach ($img as $ikey => $imm){
                    if(empty($imm)){
                        unset($img[$ikey]);
                    }
                }
                $img = serialize($img);
            }
        }else{
            $img = '';
        }

        if(empty($set['videoupload'])) {
            $videoLink = $_GPC['video_link'] ?: '';//视频文件信息
        }else{
            $videoLink = '';
        }
        $id  = $_GPC['id'] ? : 0;//帖子id

        if ($locastatus == 1) {
            $address = $_GPC['address'] OR $this->renderError('定位失败，请刷新重试');//地址
            //lat 纬度，浮点数，范围为90 ~ -90
            //lng 经度，浮点数，范围为180 ~ -180。
            $location['lat'] = $_GPC['lat'] OR $this->renderError('定位失败，请刷新重试');
            $location['lng'] = $_GPC['lng'] OR $this->renderError('定位失败，请刷新重试');
            $location = serialize($location);
        }
        //判断文本内容是否非法
        $textRes = Filter::init($content,$_W['source'],1);
        if($textRes['errno'] == 0){
            $this->renderError($textRes['message']);
        }
        //判断当前用户是否为黑名单  blackflag
        $blackFlag = pdo_getcolumn(PDO_NAME."member",['id'=>$_W['mid']],'blackflag');
        if($blackFlag == 1) $this->renderError('您已被加入黑名单，如有疑问请联系平台');
        #2、判断获取相关的值
        $set = Setting::agentsetting_read('pocket');
        if($set['locastatus'] == 2 && empty($location)){
            $this->renderError('请设置定位信息');
        }
        //判断总共的发帖限制
        $isVip = WeliamWeChat::VipVerification($_W['mid'],true);
        if(empty($id)){
            $allLimit = $set['alllimit'] ? : 0;//普通发帖限制
            $daylimit = $set['daylimit'] ? : 0;//普通发帖限制
            if($isVip > 0){
                $allLimit = $set['vipalllimit'] ? : 0;//会员发帖限制
                $daylimit = $set['vipdaylimit'] ? : 0;//会员发帖限制
            }
            if($allLimit > 0){
                $sendTotal = pdo_getcolumn(PDO_NAME."pocket_informations",['mid'=>$_W['mid']],'count(*)');
                if($sendTotal >= $allLimit){
                    if(empty($isVip) && $set['vipalllimit'] >  $set['alllimit']){
                        $this->renderError('发帖数量已到上限，开通会员发布更多信息',['tovip' => 1]);
                    }else{
                        $this->renderError('发帖失败，发帖数量已到限制!');
                    }
                }
            }
            //判断今日的发帖限制
            if($daylimit > 0){
                $dayStartTime = strtotime(date("Y-m-d "." 00:00:00 "));
                $dayEneTime = strtotime(date("Y-m-d "." 23:59:59 "));
                $sendTotal = pdo_fetchcolumn("SELECT count(id) FROM ".tablename(PDO_NAME."pocket_informations")
                    ." WHERE createtime > {$dayStartTime} AND createtime < {$dayEneTime} AND mid = {$_W['mid']} ");
                if($sendTotal >= $daylimit){
                    if(empty($isVip) && $set['vipdaylimit'] > $set['daylimit']){
                        $this->renderError('发帖数量已到上限，开通会员发布更多信息',['tovip' => 1]);
                    }else{
                        $this->renderError('发帖失败，今日发帖数量已到限制!');
                    }
                }
            }
        }
        //判断是否需要支付
        if($id > 0){
            $informations = pdo_get(PDO_NAME."pocket_informations",array('id'=>$id),['status','diyformid']);
            $status = $informations['status'];
            $recirdid = $informations['diyformid'];
        }else{
            $status = 0;
            $recirdid = 0;
        }
        if(empty($id) || $status == 5){
            $class_id = $type > 0 ? $type : $oneType;
            $class_info = pdo_get(PDO_NAME."pocket_type",['id'=>$class_id],['price','vipprice','vipstatus','usecredit']);
            if($paytype > 0 && empty($id)){
                $jifentext = $_W['wlsetting']['trade']['credittext'] ? $_W['wlsetting']['trade']['credittext'] : '积分' ;
                if($_W['wlmember']['credit1'] < $class_info['usecredit']){
                    $this->renderError('您的'.$jifentext.'不足，无法发帖');
                }else{
                    Member::credit_update_credit1($_W['mid'] ,-$class_info['usecredit'],$jifentext.'发帖扣除');
                }
                $pay_price = 0;
            }else{
                if($class_info['vipstatus'] == 1 && $isVip > 0 ){
                    $class_price = $class_info['vipprice'];
                }else{
                    $class_price = $class_info['price'];
                }
                if($class_info['vipstatus'] == 2 && empty($isVip)){
                    $this->renderError('此分类为会员专区，请先成为会员');
                }
                $pay_price = sprintf("%.2f", $class_price);
            }
        }else{
            $pay_price = 0;
        }

        //自定义表单
        $diyformid = $_GPC['diyformid'];
        $info = [];
        $diyFormInfo = array_values(json_decode(html_entity_decode($_GPC['datas']),true));
        $diyForm  = pdo_get(PDO_NAME."diyform",['id'=>$diyformid]);
        $diyFormSet  = array_values(json_decode(base64_decode($diyForm['info']), true)['list']);//页面的配置信息
        foreach($diyFormInfo as $formKey => &$formVal){
            $formVal['att_show'] = $diyFormSet[$formKey]['data']['att_show'];
        }
        $info['listinfo'] = serialize($diyFormInfo);
        $info['dotime'] = time();
        if($recirdid > 0){
            $res1 = pdo_update('wlmerchant_diyform_list',$info,array('id' => $recirdid));
        }else{
            $info['uniacid'] = $_W['uniacid'];
            $info['aid'] = $diyForm['aid'];
            $info['mid'] = $_W['mid'];
            $info['formid'] = $typeinfo['diyformid'];
            $info['plugin'] = 'pocket';
            $res1 = pdo_insert(PDO_NAME.'diyform_list',$info);
            $recirdid = pdo_insertid();
        }


        //判断是否需要审核
        $status = $set['passstatus'] == 1 ? 0 : 1;
        #3、信息拼装
        $data = [
            'uniacid'     => $_W['uniacid'] ,//公众号id
            'aid'         => $_W['aid'] ,//代理id
            'status'      => $pay_price > 0 ? 5 : $status ,//0 显示，1 审核中 2 不显示 3已删除 5未支付
            'content'     => $content ,//具体内容
            'img'         => $img ,//图片数组
            'mid'         => $_W['mid'] ,//用户id
            'onetype'     => $oneType ,//一级分类
            'type'        => $type > 0 ? $type : $oneType ,//二级分类
            'avatar'      => $_W['wlmember']['avatar'] ,//头像
            'nickname'    => $nickname ,//联系人姓名
            'phone'       => $phone ,//电话
            'share_title' => $share_title ,//分享标题
            'keyword'     => $keyword ,//关键词
            'location'    => $location ? : '' ,//定位(经纬度)
            'address'     => $address ? : '' ,//地址
            'locastatus'  => $locastatus ,//定位开关 1开启 0关闭
            'video_link'  => $videoLink,//视频文件路径
            'transferstatus' => $_GPC['transferstatus'],
            'transfermoney'  => sprintf("%.2f",$_GPC['transfermoney']),
            'transferom'  => trim($_GPC['transferom']),
            'linkplugin'  => $linkplugin,
            'linkid'      => $linkid,
            'diyformid'   => $recirdid
        ];
        #4、储存信息
        if($id > 0){
            //编辑帖子
            if($pay_price > 0 ){
                //当前帖子需要支付  关联查询订单 判断是否已经支付
                $status = pdo_getcolumn(PDO_NAME . "order" , ['fkid' => $id , 'plugin' => 'pocket'] , 'status');
                $status = $status ? $status : 0;
                if($status == 3){
                    //当前帖子已经支付
                    $pay_price = 0;
                    $data['status'] = $set['passstatus'] == 1 ? 0 : 1;
                }
            }
            $res = pdo_update(PDO_NAME."pocket_informations",$data,['id'=>$id]);
        }else{
            //添加帖子
            $data['createtime'] = $data['replytime'] = $data['refreshtime'] = time();//创建时间
            $res = pdo_insert(PDO_NAME."pocket_informations",$data);
            $id = pdo_insertid();
            //信息发布成功 发送模板消息
            if($res || $res1){
                if($set['passstatus'] != 1 && $pay_price <= 0){
                    //未开启免审核 给代理商管理员发送模板消息
                    if(empty($_W['areaname'])){
                        $areaid = pdo_getcolumn(PDO_NAME.'oparea',array('aid'=>$_W['aid']),'areaid');
                        $_W['areaname'] =  pdo_getcolumn(PDO_NAME.'area',array('id'=>$areaid),'name');
                    }
                    $className = pdo_getcolumn(PDO_NAME."pocket_type",['id'=>$oneType],'title');
                    $first   = '您好，用户['.$nickname.']在[' .$_W['areaname'].']发布了一个同城信息';//消息头部
                    $type    = "帖子发布";//业务类型
                    $content = '帖子分类:'.$className;//业务内容
                    $status  = "待审核";//处理结果
                    $remark  = "请尽快处理!";//备注信息
                    $time    = $data['createtime'];//操作时间
                    $url     = h5_url('pages/subPages/postDetails/postDetails',['id' => $id,'examine' => 1]);
                    News::noticeAgent('pocketfabu',$_W['aid'],$first,$type,$content,$status,$remark,$time,$url);
                }
            }
        }
        if($res || $res1) $this->renderSuccess('发布成功',['id'=>$id,'status'=>$pay_price > 0 ? 1 : 0]);
        else $this->renderError('发布失败或无内容修改');
    }
    /**
     * Comment: 发布成功后的扩展操作
     * Author: zzw
     * Date: 2019/8/29 16:05
     */
    public function extension(){
        global $_W,$_GPC;
        $set = Setting::agentsetting_read('pocket');
        $isVip = WeliamWeChat::VipVerification($_W['mid'],true);
        $data['list'] = $set['price'];//置顶信息列表
        if(empty($set['is_openRed'])){
            $data['red_list'] = [];
        }else{
            $data['red_list'] = $set['red_envelopes'];//红包设置信息列表
        }
        if(!empty($data['list'])){
            $data['set']['is_openTop'] = 1;
            if($isVip > 0){
                foreach($data['list'] as &$st){
                    $st['price'] = $st['vipprice'];
                }
            }
        }else{
            $data['set']['is_openTop'] = 0;
        }
        if(!empty($data['red_list'])){
            $data['set']['is_openRed'] = 1;
        }else{
            $data['set']['is_openRed'] = 0;
        }
        //定制 全频推广
        if(Customized::init('pocket140')){
            $data['set']['fullchnnel'] = 1;
        }else{
            $data['set']['fullchnnel'] = 0;
        }
        $this->renderSuccess('扩展操作信息',$data);
    }
    /**
     * Comment: 红包领取
     * Author: zzw
     * Date: 2019/8/29 17:30
     */
    public function redDetail(){
        global $_W, $_GPC;
        #1、获取参数信息
        $_GPC['id'] > 0 ? $id = $_GPC['id'] : $this->renderError("缺少参数：id");//红包(帖子)id
        $mid = $_W['mid'];//用户id
        #2、获取红包信息  红包领取信息
        $pocket = pdo_get(PDO_NAME . "pocket_informations", ['id' => $id],['aid','package','sredpack','redpagestatus']);//红包(帖子)信息
        $count = pdo_fetchcolumn("SELECT count(*) FROM " . tablename(PDO_NAME . "red_envelope") . " WHERE pid = {$id}");//已领取人数
        $surplus = $pocket['package'] - $count;//剩余的红包个数
        if ($surplus <= 0) $this->renderError('来晚一步,红包已被抢完！');
        $userGRE = pdo_get(PDO_NAME . "red_envelope",['mid' => $_W['mid'], 'pid' => $id]);
        if ($userGRE) $this->renderError('不可以重复领取哦！');
        #3、根据红包算法获取当前用户应该领取的红包
        $money = Pocket::redEnvelopeAlgorithm($pocket['sredpack'], $surplus,$pocket['redpagestatus']);
        #4、生成并且记录用户领取信息
        $getInfo = [
            'uniacid' => $_W['uniacid'],
            'aid'     => $pocket['aid'],
            'pid'     => $id,//关联红包(帖子)id
            'mid'     => $mid,//用户id
            'gettime' => time(),//领取时间
            'money'   => $money,//红包金额
        ];
        $res = pdo_insert(PDO_NAME . "red_envelope", $getInfo);
        if(!$res){
            $this->renderError('红包领取失败，请刷新重试!');
        }
        #5、修改红包信息
        $balance = $pocket['sredpack'] - $money;//红包余额
        $res = pdo_update(PDO_NAME . "pocket_informations",['sredpack' => $balance], ['id' => $id]);
        if(!$res){
            $this->renderError('红包领取失败，请刷新重试!');
        }
        #6、领取成功  修改用户余额信息
        $change = Member::credit_update_credit2($mid, $money, '掌上信息抢红包');
        if(is_error($change)){
            $this->renderError($change['message']);
        }
        $data = [
            'money'    => $money ,
            'nickname' => $_W['wlmember']['nickname'] ,
            'avatar'   => $_W['wlmember']['avatar'] ,
        ];
        $this->renderSuccess('领取成功',$data);
    }
    /**
     * Comment: 红包领取信息列表
     * Author: zzw
     * Date: 2019/8/29 18:32
     */
    public function receivingRecords(){
        global $_W,$_GPC;
        #1、获取基本信息
        $_GPC['id'] > 0 ? $id = $_GPC['id'] : $this->renderError("缺少参数：id");//红包(帖子)id
        $page = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        #2、获取列表信息
        $data = Pocket::getGetList($id,$page,$pageIndex);
        $data['cashflag'] = $_W['wlsetting']['cashset']['withdrawals'];
        $this->renderSuccess('红包领取信息列表',$data);
    }
    /**
     * Comment: 用户收藏帖子功能
     * Author: zzw
     * Date: 2019/8/30 14:45
     */
    public function collection(){
        global $_W,$_GPC;
        #1、参数获取
        $_GPC['id'] ? $id = $_GPC['id'] : $this->renderError('缺少参数：id');
        $table = PDO_NAME."pocket_collection";
        $data = ['tid'=>$id,'mid'=>$_W['mid']];
        #2、判断是否已经收藏
        $is_have = pdo_get($table,$data);
        if($is_have){
            //已收藏，取消收藏
            $res = pdo_delete($table,$data);
        }else{
            //未收藏，添加收藏
            $data['aid'] = $_W['aid'];
            $data['uniacid'] = $_W['uniacid'];
            $data['create_time'] = time();
            $res = pdo_insert($table,$data);
        }
        #3、判断操作是否成功，返回对应的内容
        if($res) $this->renderSuccess('操作成功');
        else $this->renderError('操作失败');
    }
    /**
     * Comment: 获取当前用户收藏的帖子的列表
     * Author: zzw
     * Date: 2019/8/30 15:23
     */
    public function getCollectionList(){
        global $_W,$_GPC;
        #1、参数获取
        $page = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 5;
        $pageStart = $page * $pageIndex - $pageIndex;
        $table = tablename(PDO_NAME."pocket_collection");
        #2、获取总数
        $total = pdo_fetchcolumn("SELECT COUNT(*) FROM ".$table ." WHERE mid = {$_W['mid']} ");
        $data['total'] = ceil($total / $pageIndex);
        #3、获取列表信息
        $data['list'] = pdo_fetchall("SELECT b.id,b.avatar,b.nickname,b.top,FROM_UNIXTIME(b.createtime,'%Y-%m-%d %H-%i') as createtime,b.img,
                b.phone,b.content,b.look,b.share,b.package,b.video_link,b.likeids,b.onetype,b.type,b.keyword FROM ".$table
            ." as a LEFT JOIN ".tablename(PDO_NAME."pocket_informations")
            ." as b ON a.tid = b.id WHERE a.mid = {$_W['mid']} ORDER BY a.create_time DESC LIMIT {$pageStart},{$pageIndex} ");
        #4、循环进行信息的处理
        if(is_array($data['list']) && count($data['list']) > 0){
            foreach($data['list'] as $key => &$val){
                $fabulous = unserialize($val['likeids']);
                //$val['video_link'] = tomedia($val['video_link']);
                $videoInfo = UploadFile::videoInfoHandle($val['video_link']);
                $val['video_link'] = $videoInfo['link'];
                $val['video_img_link'] = $videoInfo['img_link'];
                //获取点赞数量
                $val['fabulous_num'] = is_array($fabulous) ? count($fabulous) : 0;
                //判断当前用户是否已经点赞   0=未点赞  1=已点赞
                if(is_array($fabulous) && in_array($_W['mid'],$fabulous)) $val['is_fabulous'] = 1;
                else $val['is_fabulous'] = 0;
                //获取点赞用户的头像
                $val['fabulous_avatar'] = [];
                if($val['fabulous_num'] > 0){
                    foreach($fabulous as $item){
                        $val['fabulous_avatar'][] = pdo_getcolumn(PDO_NAME."member",['id'=>$item],'avatar');
                    }
                }
                //从新定义头像链接
                $val['avatar'] = tomedia($val['avatar']);
                //图片处理
                $val['img'] = unserialize($val['img']);
                if(is_array($val['img']) && count($val['img']) > 0){
                    foreach ($val['img'] as $imgK => &$imgV){
                        $imgV = tomedia($imgV);
                    }
                }
                //处理标签
                $val['keyword'] = unserialize($val['keyword']);
                //分类获取
                if($val['onetype'] > 0) $val['onetype'] = pdo_getcolumn(PDO_NAME."pocket_type",['id'=>$val['onetype']],'title');
                else $val['onetype'] = '';
                if($val['type'] > 0) $val['type'] = pdo_getcolumn(PDO_NAME."pocket_type",['id'=>$val['type']],'title');
                else $val['type'] = '';
                //评论获取 获取三条
                $val['comment_list'] = Pocket::getComment(1,3,$val['id']);
                unset($val['likeids']);
            }
        }

        $this->renderSuccess('帖子收藏列表',$data);
    }
    /**
     * Comment: 删除帖子
     * Author: zzw
     * Date: 2019/9/4 16:33
     */
    public function delete(){
        global $_W,$_GPC;
        #1、参数获取
        $id = $_GPC['id'] OR $this->renderError('缺少参数：帖子id');
        $table = PDO_NAME."pocket_informations";
        #2、获取帖子信息
        $sendMid = pdo_getcolumn($table,['id'=>$id],'mid');
        if($_W['mid'] != $sendMid) $this->renderError('非法操作');
        #2、删除操作
        $res = pdo_update($table,array('status' => 3),array('id' => $id));
        if($res) $this->renderSuccess('删除成功');
        else $this->renderError('删除失败');
    }
    /**
     * Comment: 获取帖子编辑信息
     * Author: zzw
     * Date: 2019/9/16 14:01
     */
    public function editPocket(){
        global $_W,$_GPC;
        #1、获取参数
        $id = $_GPC['id'] OR $this->renderError("缺少id：帖子id");
        #2、获取帖子详细信息
        $info = pdo_get(PDO_NAME."pocket_informations",['id'=>$id]
            ,['content','img','onetype','type','nickname','phone','share_title'
                ,'keyword','location','address','locastatus','video_link'
                ,'transferstatus','transfermoney','transferom','linkid','linkplugin','diyformid']);
        if(!$info) $this->renderError('帖子不存在!');
        //图片信息处理
        $info['img'] = is_array(unserialize($info['img'])) ? unserialize($info['img']) : [];
        if(is_array($info['img']) && count($info['img']) > 0){
            foreach ($info['img'] as $key => $val) {
                $info['img_url'][$key] = tomedia($val);
            }
        }
        //标签信息处理
        $info['keyword'] = is_array(unserialize($info['keyword'])) ? unserialize($info['keyword']) : [];
        //经纬度处理
        $info['location'] = unserialize($info['location']);
        //视频地址
        //$info['video_url'] = $info['video_link'] ? tomedia($info['video_link']) : '';
        $videoInfo = UploadFile::videoInfoHandle($info['video_link']);
        $info['video_url'] = $videoInfo['link'];
        $info['video_img_link'] = $videoInfo['img_link'];
        #2、获取当前分类的全部标签信息
        $cate_id = $info['type'] > 0 ? $info['type'] : $info['onetype'] > 0 ? $info['onetype'] : 0;
        $keyword = pdo_getcolumn(PDO_NAME."pocket_type",['id'=>$cate_id],'keyword');
        if($keyword) $keyword = explode(',',trim($keyword,','));
        $info['whole_key'] = $keyword;
        //获取关联商品
        if($info['linkid'] > 0){
            $linkinfo = Pocket::getLinkGoods($info['linkid'],$info['linkplugin']);
            $info['linkname'] = $linkinfo['linkname'];
            $info['linkthumb'] = tomedia($linkinfo['thumb']);
        }
        //表单信息
        if($info['diyformid'] > 0){
            $listinfo = pdo_get('wlmerchant_diyform_list',array('id' => $info['diyformid']));
            $forminfo = pdo_get('wlmerchant_diyform',array('id' => $listinfo['formid']));
            $moinfo = json_decode(base64_decode($forminfo['info']) , true);
            $list = $moinfo['list'];
            $list = array_values($list);
            $info['diyform']['list'] = Im::diyFormInfo($list,$listinfo);
        }

        $this->renderSuccess('帖子编辑信息',$info);
    }
    /**
     * Comment: 获取掌上信息免责声明
     * Author: zzw
     * Date: 2019/10/14 11:33
     */
    public function getDisclaimer(){
        global $_W,$_GPC;
        $set = Setting::agentsetting_read('pocket');
        $data['disclaimer'] = !empty($set['statement']) ? $set['statement'] : '本平台发布的所有信息展示，内容本身与平台本身无关，平台不负任何责任。';
        $data['hidesettle'] = $set['storesettle']?$set['storesettle']:0;
        //定制
        if(Customized::init('pocket140') > 0){
            $data['korea'] = 1;
        }else{
            $data['korea'] = 0;
        }
        $this->renderSuccess('掌上信息发帖免责声明',$data);
    }
    /**
     * Comment: 分享时记录分享数量
     * Author: zzw
     * Date: 2019/10/18 11:05
     */
    public function shareNum(){
        global $_W,$_GPC;
        #1、参数获取
        $id  = $_GPC['id'] OR $this->renderError('缺少参数：id');
        #2、获取当前分享数量
        $shareNum = pdo_getcolumn(PDO_NAME."pocket_informations",['id'=>$id],'share');
        $totalNum = intval($shareNum) + 1;
        #2、修改分享数量
        pdo_update(PDO_NAME."pocket_informations",['share'=>$totalNum],['id'=>$id]);

        $this->renderSuccess('记录成功');
    }

    /**
     * Comment: 判断用户是否被加入黑名单
     * Author: wlf
     * Date: 2020/06/28 16:28
     */
    public function checkBlack(){
        global $_W;
        $flag = pdo_getcolumn(PDO_NAME.'pocket_blacklist',array('uniacid'=>$_W['uniacid'],'mid'=>$_W['mid'],'aid'=>$_W['aid']),'id');
        if(!empty($flag)){
            $tips = $_W['wlsetting']['userset']['black_desc'] ? : '您被禁止评论，请联系客服';
            $this->renderError($tips);
        }
    }

    /**
     * Comment: 帖子刷新接口
     * Author: wlf
     * Date: 2021/11/05 15:38
     */
    public function refreshPocket(){
        global $_W,$_GPC;
        $informid = $_GPC['id'];
        if(empty($informid)){
            $this->renderError('参数错误，请刷新重试');
        }
        $inform = pdo_get('wlmerchant_pocket_informations',array('id' => $informid),['refreshtime','onetype','type']);
        if(empty($inform)){
            $this->renderError('信息错误，请刷新重试');
        }
        $type = pdo_get(PDO_NAME."pocket_type",['id'=>$inform['type']],['refreshprcie','refreshvip','refreshday']);
        //判断价格
        $vipflag = WeliamWeChat::VipVerification($_W['mid'],true);
        if($vipflag > 0 ){
            $price = $type['refreshvip'];
        }else{
            $price = $type['refreshprcie'];
        }
        $timeflag = $inform['refreshtime'] + $type['refreshday']*86400 - time();
        if(Customized::init('pocket140') > 0){
            if($timeflag < 0){
                $price = 0;
            }
        }else{
            if($timeflag > 0){
                $timeflag = ceil($timeflag/3600);
                $this->renderError('您在'.$timeflag.'小时后可以再次刷新');
            }
        }
        if($price > 0){
            $this->renderSuccess('需要支付',['prcie' => $price]);
        }else{
            pdo_update('wlmerchant_pocket_informations',array('refreshtime' => time()),array('id' => $informid));
            $this->renderSuccess('刷新成功',['prcie' => 0]);
        }
    }

    /**
     * Comment: 获取帖子关联商户接口
     * Author: wlf
     * Date: 2021/11/10 14:18
     */
    public function getPocketStore(){
        global $_W,$_GPC;
        #1、参数获取
        $pocketid = $_GPC['pocketid'] ? : -1;
        $lng = $_GPC['lng'] ? : 0;//104.0091133118 经度
        $lat = $_GPC['lat'] ? : 0;//30.5681964123  纬度

        $storeids = pdo_getcolumn(PDO_NAME.'pocket_informations',array('id'=>$pocketid),'storeid');
        $list = [];
        if(!empty($storeids)){
            $storeids = unserialize($storeids);
            $storeids = implode(",",$storeids);
            $storeids = '('.$storeids.')';
            $list = pdo_fetchall("SELECT id,storename,logo,address,location,storehours,pv,score,tag FROM " . tablename(PDO_NAME . "merchantdata") . " WHERE id IN {$storeids} AND enabled = 1 ORDER BY listorder DESC ");

            #2、循环处理商户信息
            foreach ($list as $key => &$val) {
                //图片处理
                $val['logo'] = tomedia($val['logo']);
                //店铺标签
                $val['tags'] = [];
                $tagids      = unserialize($val['tag']);
                if (!empty($tagids)) {
                    $tags        = pdo_getall('wlmerchant_tags' , ['id' => $tagids] , ['title']);
                    $val['tags'] = $tags ? array_column($tags , 'title') : [];
                }
                unset($val['tag']);
                //定位处理
                $val['distance'] = Store::shopLocation(0 , $lng , $lat , unserialize($val['location']));
                //营业时间处理
                $storehours            = unserialize($val['storehours']);
                if(!empty($storehours['startTime'])){
                    $val['storehours'] = $storehours['startTime'] . ' - ' . $storehours['endTime']. "  营业";
                }else{
                    $val['storehours'] = '';
                    foreach($storehours as $hk => $hour){
                        if($hk > 0){
                            $val['storehours'] .= ','.$hour['startTime'] . ' - ' . $hour['endTime'];
                        }else{
                            $val['storehours'] .= $hour['startTime'] . ' - ' . $hour['endTime'];
                        }
                    }
                    $val['storehours'] .= "  营业";
                }
                $val['score']      = intval($val['score']);
                //查询认证和保证金
                if (p('attestation')) {
                    $val['attestation'] = Attestation::checkAttestation(2 , $val['id']);
                }
                else {
                    $val['attestation'] = 0;
                }
            }
            #2、获取店铺商品活动信息
            $list = WeliamWeChat::getStoreList($list);
        }


        $this->renderSuccess('用户店铺列表' , $list);


    }


    /**
     * Comment: 帖子推广接口
     * Author: wlf
     * Date: 2021/11/17 14:56
     */
    public function channelPocket(){
        global $_W,$_GPC;
        $informid = $_GPC['id'];
        if(empty($informid)){
            $this->renderError('参数错误，请刷新重试');
        }
        $inform = pdo_get('wlmerchant_pocket_informations',array('id' => $informid),['id']);
        if(empty($inform)){
            $this->renderError('信息错误，请刷新重试');
        }
        $set = Setting::agentsetting_read('pocket');
        //判断价格
        $vipflag = WeliamWeChat::VipVerification($_W['mid'],true);
        if($vipflag > 0 ){
            $price = $set['fullvip'];
        }else{
            $price = $set['fullprice'];
        }
        if($price > 0){
            $this->renderSuccess('需要支付',['prcie' => $price]);
        }else{
            pdo_update('wlmerchant_pocket_informations',array('fullchnnel' => 1),array('id' => $informid));
            $this->renderSuccess('推广成功',['prcie' => 0]);
        }
    }

    /**
     * Comment: 帖子审核接口
     * Author: wlf
     * Date: 2022/03/11 11:38
     */
    public function examinePocket(){
        global $_W,$_GPC;
        $informid = $_GPC['id'];
        $status = $_GPC['status'];  //1通过 2驳回
        $reason = $_GPC['reason'];  //驳回原因
        if(empty($informid)){
            $this->renderError('参数错误，请刷新重试');
        }
        $inform = pdo_get('wlmerchant_pocket_informations',array('id' => $informid),['status','mid']);
        if($inform['status'] != 1){
            $this->renderError('帖子状态错误，无法审核');
        }
        $agentuser = pdo_get('wlmerchant_agentadmin', array('uniacid' => $_W['uniacid'],'aid' => $_W['aid'],'mid' => $_W['mid'], 'notice' => 1), array('id','noticeauthority'));
        if(empty($agentuser)){
            $this->renderError('您无审核权限');
        }else{
            $noticeauthority = unserialize($agentuser['noticeauthority']);
            if(empty($noticeauthority)) $noticeauthority = [];
            if(!in_array('pocketfabu',$noticeauthority) && !empty($noticeauthority)) {
                $this->renderError('您无审核权限');
            }
        }
        if($status == 1){
            $res = pdo_update('wlmerchant_pocket_informations', array('status' => 0), array('id' => $informid));
        }else{
            $res = pdo_update(PDO_NAME . 'pocket_informations', array('status' => 2, 'reason' => $reason), array('id' => $informid));
        }
        if ($res) {
            Pocket::passnotice($informid);
            $this->renderSuccess('操作成功');
        }else{
            $this->renderError('操作失败，请刷新重试');
        }
    }


    /**
     * Comment: 选择关联商品和商户
     * Author: wlf
     * Date: 2022/06/29 17:14
     */
    public function linkInfo(){
        global $_W,$_GPC;
        $name = $_GPC['name'];
        $where = "uniacid = {$_W['uniacid']}  AND aid = {$_W['aid']}";
        $pageNum = 10;
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $start = $page * $pageNum - $pageNum;

        //抢购
        $rushwhere = $where;
        if(!empty($name)){
            $rushwhere .= " AND name LIKE '%{$name}%' ";
        }
        $rushwhere .= " AND status IN (1,2)";
        $rush = pdo_fetchall("SELECT id,1 as `plugin`,name,thumb,price FROM ".tablename('wlmerchant_rush_activity')."WHERE {$rushwhere} ORDER BY sort DESC,id DESC");
        //团购
        $groupwhere = $where;
        if(!empty($name)){
            $groupwhere .= " AND name LIKE '%{$name}%' ";
        }
        $groupwhere .= " AND status IN (1,2)";
        $group = pdo_fetchall("SELECT id,2 as `plugin`,name,thumb,price FROM ".tablename('wlmerchant_groupon_activity')."WHERE {$groupwhere} ORDER BY sort DESC,id DESC");
        //商户
        $storewhere = $where;
        if(!empty($name)){
            $storewhere .= " AND storename LIKE '%{$name}%' ";
        }
        $storewhere .= " AND status = 2 AND enabled = 1 ";
        $store = pdo_fetchall("SELECT id,6 as `plugin`,storename as name,logo as thumb FROM ".tablename('wlmerchant_merchantdata')."WHERE {$storewhere} ORDER BY listorder DESC,id DESC");
        //拼团
        $fightwhere = $where;
        if(!empty($name)){
            $fightwhere .= " AND name LIKE '%{$name}%' ";
        }
        $fightwhere .= " AND status IN (1,2)";
        $fight = pdo_fetchall("SELECT id,3 as `plugin`,name,logo as thumb,price FROM ".tablename('wlmerchant_fightgroup_goods')."WHERE {$fightwhere} ORDER BY listorder DESC,id DESC");
        //砍价
        $brgainwhere = $where;
        if(!empty($name)){
            $brgainwhere .= " AND name LIKE '%{$name}%' ";
        }
        $brgainwhere .= " AND status IN (1,2)";
        $bargain = pdo_fetchall("SELECT id,4 as `plugin`,name,thumb,price FROM ".tablename('wlmerchant_bargain_activity')."WHERE {$brgainwhere} ORDER BY sort DESC,id DESC");
        //优惠券
        $couponwhere = $where;
        if(!empty($name)){
            $couponwhere .= " AND title LIKE '%{$name}%' ";
        }
        $couponwhere .= " AND status IN (1,2)";
        $coupon = pdo_fetchall("SELECT id,5 as `plugin`,title as name,logo as thumb,price FROM ".tablename('wlmerchant_couponlist')."WHERE {$couponwhere} ORDER BY indexorder DESC,id DESC");
        //活动
        $activitywhere = $where;
        if(!empty($name)){
            $activitywhere .= " AND name LIKE '%{$name}%' ";
        }
        $activitywhere .= " AND status IN (1,2)";
        $activity = pdo_fetchall("SELECT id,7 as `plugin`,title as name,thumb,price FROM ".tablename('wlmerchant_bargain_activity')."WHERE {$activitywhere} ORDER BY sort DESC,id DESC");
        $goods = array_merge($rush, $group, $store, $fight, $bargain,$coupon,$activity);
        if (!$goods) {
            $this->renderSuccess('暂无信息',[]);
        }
        $data['page_number'] = ceil(count($goods) / $pageNum);//总页数
        $goods = array_slice($goods, $start, $pageNum);
        foreach ($goods as &$good){
            $good['thumb'] = tomedia($good['thumb']);
        }
        $data['list'] = $goods;
        $this->renderSuccess('数据信息 ',$data);
    }


    /**
     * Comment: 看视频获得积分接口
     * Author: wlf
     * Date: 2022/10/10 14:50
     */
    public function getVideoCredit(){
        global $_W,$_GPC;
        $id = $_GPC['id'];
        $minute = $_GPC['minute'];
        $set = Setting::agentsetting_read('pocket');
        $videoc = $set['videocredit'];
        $allcredit = 0;
        if(!empty($videoc)){
            foreach ($videoc as $cinfo){
                if($cinfo['minute'] <= $minute){
                    $getcredit = Pocket::giveCredit($id,1,$_W['mid'],$set,$cinfo['minute'],$cinfo['integral']);
                    $allcredit += $getcredit;
                }
            }
        }
        $this->renderSuccess('获赠积分 ',['credit' => $allcredit]);
    }


    /**
     * Comment: 分享数据页面信息
     * Author: wlf
     * Date: 2022/10/11 10:40
     */
    public function getSharePageInfo(){
        global $_W,$_GPC;
        $headid = intval($_GPC['head_id']);
        $set = Setting::agentsetting_read('pocket');
        if($headid > 0){
            Pocket::invitationRecord($_W['mid'],$headid,$set);
        }
        $advs = unserialize($set['advs']);
        if(!empty($advs)){
            foreach ($advs as &$ad){
                $ad['thumb'] = tomedia($ad['thumb']);
            }
        }
        $data = [
            'advs' => $advs,
            'title' => $set['share_page_title']
        ];
        $list = pdo_fetchall("select distinct invmid,invmid as pid,(SELECT COUNT(id) FROM ".tablename(PDO_NAME.'pocket_invitation')." WHERE invmid = pid) as invnum from " . tablename(PDO_NAME.'pocket_invitation')." WHERE uniacid = {$_W['uniacid']} ORDER BY invnum DESC LIMIT 10");

        if(!empty($list)){
            foreach ($list as &$li){
                $member = pdo_get('wlmerchant_member',array('id' => $li['invmid']),array('nickname','avatar','encodename'));
                $li['nickname'] = !empty($member['encodename']) ? base64_decode($member['encodename']) : $member['nickname'];
                $li['avatar'] = tomedia($member['avatar']);
            }
            $data['list'] = $list;
        }else{
            $data['list'] = [];
        }
        $this->renderSuccess('页面信息 ',$data);
    }

}
