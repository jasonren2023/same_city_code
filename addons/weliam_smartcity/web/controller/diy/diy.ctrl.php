<?php
defined('IN_IA') or exit('Access Denied');

class Diy_WeliamController {
    /**
     * Comment: 进入页面的编辑页面
     * Author: zzw
     */
    public function editPage() {
        global $_W, $_GPC;
        $back = $_GPC['back_url'];//返回的地址
        $id = $_GPC['id'] ? $_GPC['id'] : -1;//id   存在修改配置  不存在是添加配置;//页面id
        $tid = $_GPC['tid'] ? $_GPC['tid'] : -1;//模板id 存在调用模板信息
        $type = $_GPC['type'];//页面的类型 1=自定义;2=商城首页;3=会员中心;4=分销中心;5=商品详情页;6=积分商城;7=整点秒杀;8=兑换中心;9=快速购买;99=公用模块
        //返回的页面地址
        $result = Diy::verify($id, $type, $tid);

        extract($result);
        //获取公共的内容
        $common = Diy::getCommon();
        #3、获取其他信息
        if (empty($_W['aid'])) {
            $diyadvs = pdo_getall(PDO_NAME . "diypage_adv", array('uniacid' => $_W['uniacid']));
            $diymenu = pdo_getall(PDO_NAME . "diypage_menu", array('uniacid' => $_W['uniacid']));
            $category = pdo_getall(PDO_NAME . "diypage_temp_cate", array('uniacid' => $_W['uniacid'],'aid' => $_W['aid']), array('id', 'name'));
        } else {
            //启动广告信息
            $diyadvs = pdo_getall(PDO_NAME . "diypage_adv", array('aid' => $_W['aid'], 'uniacid' => $_W['uniacid']));
            //菜单信息
            $diymenu = pdo_getall(PDO_NAME . "diypage_menu", array('aid' => [$_W['aid'],0], 'uniacid' => $_W['uniacid']));
            //模板分类信息
            $category = pdo_getall(PDO_NAME . "diypage_temp_cate", array('aid' => $_W['aid'], 'uniacid' => $_W['uniacid']), array('id', 'name'));
        }

        include wl_template('diy/page_edit');
    }
    /**
     * Comment: 保存页面信息
     * Author: zzw
     */
    public function savePage() {
        global $_W, $_GPC;
        #1、参数接收
        $id = $_GPC['id'];//修改的id
        $data = json_decode(base64_decode($_GPC['data']), true);//页面的配置信息
        #2、组装页面配置基本信息
        $diypage = [
            'data'         => base64_encode(json_encode($data,JSON_UNESCAPED_UNICODE)),
            'name'         => $data['page']['name'],
            'type'         => $data['page']['type'],
            'lastedittime' => time(),
        ];
        #3、进行数据的添加||修改
        if ($id > 0) {
            if(!$diypage['name']) unset($diypage['name']);//修改时如果页面标题为空则删除
            if(!$diypage['type']) unset($diypage['type']);//修改时如果页面类型为空则删除
            //修改已存在的页面配置信息
            pdo_update(PDO_NAME . 'diypage', $diypage, array('id' => $id));
        } else {
            //添加储存新的页面配置信息
            $diypage['uniacid'] = $_W['uniacid'];
            $diypage['aid'] = $_W['aid'];
            $diypage['createtime'] = time();
            if (empty($diypage['aid'])) {
                $diypage['is_public'] = 1;
            }

            pdo_insert(PDO_NAME . 'diypage', $diypage);
            $id = pdo_insertid();
        }

        #4、返回最终的结果
        $backUrl = "diypage/diy/pagelist";
        wl_json(1, '操作成功', array('id' => $id, 'url' => web_url('diy/diy/editPage', array('id' => $id, 'type' => $data['page']['type'], 'page_type' => 'page', 'back_url' => $backUrl))));
    }
    /**
     * Comment: 删除一个页面
     * Author: zzw
     */
    public function delPage() {
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? : [];
        pdo_delete(PDO_NAME."diypage",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }


    /**
     * Comment: 保存菜单信息
     * Author: zzw
     */
    public function saveMenu() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $data = $_GPC['menu'];
        $menuClass = intval($_GPC['menu_class']);
        $menudata = array('name' => $data['name'], 'data' => base64_encode(json_encode($data)), 'lastedittime' => time());
        if (!empty($id)) {
            pdo_update(PDO_NAME . 'diypage_menu', $menudata, array('id' => $id, 'uniacid' => $_W['uniacid']));
        } else {
            $menudata['uniacid'] = $_W['uniacid'];
            $menudata['aid'] = $_W['aid'];
            if (empty($menudata['aid'])) {
                $menudata['is_public'] = 1;
            }
            $menudata['createtime'] = time();
            $menudata['menu_class'] = $menuClass;
            pdo_insert(PDO_NAME . 'diypage_menu', $menudata);
            $id = pdo_insertid();
        }
        if ($menuClass == 1) {
            $Url = "diypage/diy/menuEdit";
        } else {
            $Url = "chosen/diy/menuEdit";
        }
        wl_json(1, '保存成功', web_url($Url, array('id' => $id, 'menu_class' => $menuClass)));

    }
    /**
     * Comment: 删除菜单
     * Author: zzw
     */
    public function delMenu() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            show_json(0, '参数错误，请刷新重试！');
        } else {
            pdo_delete(PDO_NAME . 'diypage_menu', array('id' => $id));
            show_json(1);
        }
    }


    /**
     * Comment: 保存广告信息
     * Author: zzw
     */
    public function saveAdv() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $advClass = $_GPC['adv_class'];
        $data = $_GPC['advs'];
        $advsdata = array('name' => $data['name'], 'data' => base64_encode(json_encode($data)), 'lastedittime' => time(), 'type' => 1, 'aid' => intval($_W['aid']));
        if (empty($advsdata['aid'])) {
            $advsdata['is_public'] = 1;
        }
        if (!empty($id)) {
            pdo_update(PDO_NAME . 'diypage_adv', $advsdata, array('id' => $id, 'uniacid' => $_W['uniacid']));
        } else {
            $advsdata['uniacid'] = $_W['uniacid'];
            $advsdata['createtime'] = time();
            $advsdata['adv_class'] = $advClass;
            pdo_insert(PDO_NAME . 'diypage_adv', $advsdata);
            $id = pdo_insertid();
        }
        if ($advClass == 1) {
            $Url = "diypage/diy/advEdit";
        } else {
            $Url = "chosen/diy/advEdit";
        }
        wl_json(1, '保存成功', web_url($Url, array('id' => $id, 'adv_class' => $advClass)));

    }
    /**
     * Comment: 删除广告信息
     * Author: zzw
     */
    public function delAdv() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            show_json(0, '参数错误，请刷新重试！');
        } else {
            pdo_delete(PDO_NAME . 'diypage_adv', array('id' => $id, 'aid' => intval($_W['aid'])));
            show_json(1);
        }
    }



    /**
     * Comment: 保存模板预览图片 并且返回图片路径
     * Author: zzw
     * Date: 2020/8/28 9:20
     */
    public function saveTempImage(){
        global $_W, $_GPC;
        //进行预览图-图片的处理
        $pageImg = $_GPC['pageImg'];//预览图base64码
        $imageName = "page_image" . time() . rand(1000, 9999) . '.png';
        $imageName = "images/" . MODULE_NAME . "/" . $imageName;//文件储存路径  图片地址
        $fullName = PATH_ATTACHMENT . $imageName;//文件在本地服务器暂存地址
        if (strstr($pageImg, ",")) {
            $pageImg = explode(',', $pageImg);
            $pageImg = $pageImg[1];
        }
        file_put_contents($fullName, base64_decode($pageImg));

        wl_json(1, '操作成功',['img_name'=>$imageName]);
    }
    /**
     * Comment: 保存模板信息
     * Author: zzw
     */
    public function saveTemp() {
        global $_W, $_GPC;
        #1、数据获取
        $id      = $_GPC['id'];//模板id
        $type    = $_GPC['type'];//页面类型
        $cate    = $_GPC['cate'];//模板分类id
        $name    = $_GPC['name'];//模板名称
        $data    = $_GPC['data'];//模板配置信息
        $pageImg = $_GPC['pageImg'];//预览图
        #2、组装页面配置基本信息
        $temp = array(
            'type'    => intval($type),//页面类型
            'cate'    => intval($cate),//分类id
            'name'    => trim($name), //模板名称
            'preview' => trim($pageImg),//预览图片
            'data'    => base64_encode(json_encode($data, JSON_UNESCAPED_UNICODE)),//模板配置信息
            'aid'     => $_W['aid'],
            'uniacid' => $_W['uniacid']
        );
        #3、进行数据的添加
        pdo_insert(PDO_NAME . 'diypage_temp', $temp);
        $id = pdo_insertid();
        #4、返回最终的结果
        wl_json(1, '操作成功');
    }
    /**
     * Comment: 删除一个模板
     * Author: zzw
     */
    public function delTemp() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $result = pdo_delete(PDO_NAME . "diypage_temp", array('id' => $id));
        if ($result) {
            show_json(0);
        } else {
            show_json(1, '删除失败');
        }
    }


    /**
     * Comment: 保存模板分类信息
     * Author: zzw
     */
    public function saveCate() {
        global $_W, $_GPC;
        $name = trim($_GPC['name']);
        $cateClass = $_GPC['cateClass'];
        if (empty($name)) {
            show_json(0, '分类名称为空！');
        }
        $result = pdo_insert(PDO_NAME . 'diypage_temp_cate'
            , array('name' => $name, 'uniacid' => $_W['uniacid'], 'aid' => intval($_W['aid']), 'cate_class' => $cateClass));
        if ($result) {
            show_json(1);
        } else {
            show_json(1, '添加失败');
        }
    }
    /**
     * Comment: 编辑模板分类信息
     * Author: zzw
     */
    public function editCate() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $name = trim($_GPC['value']);
        $item = pdo_fetch('SELECT id, name, uniacid FROM ' . tablename(PDO_NAME . 'diypage_temp_cate') . ' WHERE id=:id and aid=:aid and uniacid=:uniacid ', array(':aid' => intval($_W['aid']), ':uniacid' => $_W['uniacid'], ':id' => $id));
        if (!empty($item)) {
            pdo_update(PDO_NAME . 'diypage_temp_cate', array('name' => $name), array('id' => $id, 'aid' => intval($_W['aid'])));
            show_json(1, '分类名称编辑成功！');
        } else {
            show_json(0, '分类不存在,请刷新页面重试！');
        }
    }
    /**
     * Comment: 删除模板分类信息
     * Author: zzw
     */
    public function delCate() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            show_json(0, '参数错误，请刷新重试！');
        }
        $item = pdo_fetch('SELECT id, name, uniacid FROM ' . tablename(PDO_NAME . 'diypage_temp_cate') . ' WHERE id=:id and aid=:aid and uniacid=:uniacid ', array(':aid' => intval($_W['aid']), ':uniacid' => $_W['uniacid'], ':id' => $id));
        if (!empty($item)) {
            pdo_delete(PDO_NAME . 'diypage_temp_cate', array('id' => $id, 'aid' => intval($_W['aid'])));
        }
        show_json(1);
    }


    /**
     * Comment: 根据图片地址下载当前页面要使用的图片信息
     * Author: zzw
     */
    public function getImgInfo() {
        global $_W, $_GPC;
        $imgUrl = $_GPC['img_url'];
        foreach ($imgUrl as $k => $v) {
            $imgResources = file_get_contents($v);
            $setting = $_W['setting']['upload']['image'];
            $setting['folder'] = "images/" . MODULE_NAME;
            $imageName = date("Y-m-dHisw") . time() . rand(1000, 9999) . '.png';
            $imageName = $setting['folder'] . "/" . $imageName;//文件储存路径
            $fullName = PATH_ATTACHMENT . $imageName;//文件在本地服务器暂存地址
            file_put_contents($fullName, $imgResources);
            $image_info = getimagesize($fullName);
            $image_data = fread(fopen($fullName, 'r'), filesize($fullName));
            $base64_image = 'data:' . $image_info['mime'] . ';base64,' . chunk_split(base64_encode($image_data));
            $base64[$k] = $base64_image;
            unlink($fullName);
        }
        wl_json(1, '处理后的图片', $base64);
    }
    /**
     * Comment: 获取对应的商品信息
     * Author: zzw
     */
    public function getGoodsInfo() {
        global $_W, $_GPC;
        $plugin = $_GPC['plugin'];//商品类型:0=全部,1=抢购,2=团购,3=拼团,4=大礼包,5=优惠券,6=折扣卡,7=砍价商品,8=积分商品,9=同城活动,10=配送商品
        $search = $_GPC['search'];//搜索内容  商品名称
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageNum = $_GPC['pageNum'] ? $_GPC['pageNum'] : 8;
        $geturl = $_GPC['geturl'] ? $_GPC['geturl'] : 0;
        $start = $page * $pageNum - $pageNum;
        if ($plugin == 0) {
            $rush = self::getGoods(1, $search);//抢购商品
            $groupon = self::getGoods(2, $search);//团购商品
            $wlfightgroup = self::getGoods(3, $search);//拼团商品
            $coupon = self::getGoods(5, $search);//优惠券
            $bargain = self::getGoods(7, $search);//砍价
            $integral = [];//self::getGoods(8, $search);//积分
            $activity = self::getGoods(9, $search);//活动
            $goods = array_merge($rush, $groupon, $wlfightgroup, $coupon, $bargain,$integral,$activity);
        } else {
            $goods = self::getGoods($plugin, $search, $start, $pageNum);
        }
        if (!$goods) {
            wl_json(0, '暂无该类型商品!');
        }
        //获取总页数  进行分页
        $data['page_number'] = ceil(count($goods) / $pageNum);//总页数
        $goods = array_slice($goods, $start, $pageNum);
        //只有抢购、团购、拼团、优惠券才会进行下面的操作
        $initPlugin = $plugin;
        foreach ($goods as $k => &$v) {
            //查询全部商品时 每个商品从新定义内容
            if ($initPlugin == 0) {
                switch ($v['plugin']) {
                    case 'rush':
                        $plugin = 1;
                        break;//抢购商品
                    case 'groupon':
                        $plugin = 2;
                        break;//团购商品
                    case 'wlfightgroup':
                        $plugin = 3;
                        break;//拼团商品
                    case 'coupon':
                        $plugin = 5;
                        break;//优惠券
                    case 'bargain':
                        $plugin = 7;
                        break;//砍价商品
                    case 'integral':
                        $plugin = 8;
                        break;//砍价商品
                    case 'activity':
                        $plugin = 9;
                        break;//同城活动
                }
            }
            $v = WeliamWeChat::getHomeGoods($plugin, $v['id']);
            //获取商品详情页面的跳转地址
            if ($geturl == 1) {
                switch ($plugin) {
                    case 1:
                        $v['detail_url'] = h5_url('pages/subPages/goods/index', ['type' => 1, 'id' => $v['id']]);
                        break;//抢购商品
                    case 2:
                        $v['detail_url'] = h5_url('pages/subPages/goods/index', ['type' => 2, 'id' => $v['id']]);
                        break;//团购商品
                    case 3:
                        $v['detail_url'] = h5_url('pages/subPages/goods/index', ['type' => 3, 'id' => $v['id']]);
                        break;//拼团商品
                    case 5:
                        $v['detail_url'] = h5_url('pages/subPages/goods/index', ['type' => 5, 'id' => $v['id']]);
                        break;//优惠券
                    case 7:
                        $v['detail_url'] = h5_url('pages/subPages/goods/index', ['type' => 7, 'id' => $v['id']]);
                        break;//砍价商品
                    case 8:
                        $v['detail_url'] = h5_url('pages/subPages/goods/index', ['goodsType' => 'integral', 'goods_id' => $v['id']]);
                        break;//砍价商品
                    case 9:
                        $v['detail_url'] = h5_url('pages/subPages2/coursegoods/localindex/localindex');
                        break;//同城活动
                }
            }
        }
        //信息拼装
        $data['goods'] = $goods;//商品信息
        $data['page'] = $page;//当前页


        //输出结果
        if ($goods) {
            wl_json(1, '获取商品信息', $data);
        } else {
            wl_json(0, '获取失败');
        }
    }
    /**
     * Comment: 获取头条信息
     * Author: zzw
     */
    public function getHeadline() {
        global $_W, $_GPC;
        $search = $_GPC['search'];//搜索内容  头条标题/作者名称
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageNum = $_GPC['pageNum'] ? $_GPC['pageNum'] : 10;
        $start = $page * $pageNum - $pageNum;
        $where = " uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if ($search) {
            $where .= " AND (title LIKE '%{$search}%' || author LIKE '%{$search}%') ";
        }
        //获取头条列表
        $list = pdo_fetchall("SELECT id,title,summary,display_img,author,author_img,browse,one_id,two_id FROM "
            . tablename(PDO_NAME . "headline_content")
            . " WHERE {$where} ORDER BY release_time DESC ");
        $data['page_number'] = ceil(count($list) / $pageNum);//总页数
        $list = array_slice($list, $start, $pageNum);
        foreach ($list as $k => &$v) {
            $v['display_img'] = tomedia($v['display_img']);
            $v['author_img'] = tomedia($v['author_img']);
            $v['one_name'] = implode(pdo_get(PDO_NAME . 'headline_class', array('id' => $v['one_id']), array('name')));
            $v['two_name'] = implode(pdo_get(PDO_NAME . 'headline_class', array('id' => $v['two_id']), array('name')));
            unset($v['one_id']);
            unset($v['two_id']);
        }
        $data['list'] = $list;//头条信息
        $data['page'] = $page;//当前页
        if ($list) {
            wl_json(1, '获取商品信息', $data);
        } else {
            wl_json(0, '获取失败');
        }
    }
    /**
     * Comment: 获取商户信息
     * Author: zzw
     */
    public function getShop() {
        global $_W, $_GPC;
        $search = $_GPC['search'];//搜索内容  店铺名称
        $page = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageNum = $_GPC['pageNum'] ? $_GPC['pageNum'] : 10;
        $start = $page * $pageNum - $pageNum;
        $where = " uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 2 AND enabled = 1 ";
        if ($search) {
            $where .= " AND storename LIKE '%{$search}%' ";
        }
        $shopList = pdo_fetchall("SELECT id,storename,logo,address,location,storehours,pv,score,tag FROM "
            . tablename(PDO_NAME . "merchantdata")
            . " WHERE {$where}");
        foreach ($shopList as $shop_key => &$shop_val) {
            $shop_val['salesVolume'] = Store::getShopSales($shop_val['id'], 1, 0);
            $shop_val['score'] = sprintf("%.1f", $shop_val['score']);
            //标签信息处理
            $tag = unserialize($shop_val['tag']);
            if(is_array($tag) && count($tag) > 0){
                $tags              = pdo_getall(PDO_NAME.'tags' , ['id' => $tag] , ['title']);
                $shop_val['tag'] = is_array($tag) && count($tag) > 0 ? array_column($tags , 'title') : [];
            }else{
                $shop_val['tag'] = [];
            }
        }
        $shopList = Store::getstores($shopList, 0, 0, 4);
        $data['page_number'] = ceil(count($shopList) / $pageNum);//总页数
        $shopList = array_slice($shopList, $start, $pageNum);
        //获取店铺商品活动信息
        $shopList = WeliamWeChat::getStoreList($shopList);
        $data['list'] = $shopList;//店铺信息
        $data['page'] = $page;//当前页

        if ($shopList) {
            wl_json(1, '获取商品信息', $data);
        } else {
            wl_json(0, '获取失败');
        }
    }
    /**
     * Comment: 预览页面内容
     * Author: zzw
     */
    public function previewPage() {
        global $_W, $_GPC;
        $id = intval($_GPC['id']);
        $url = h5_url('pages/mainPages/index/diypage?type=1', ['id' => $id]);
        $path = 'pages/mainPages/index/diypage?type=1&id='.$id;
        $imageUrl = tomedia(WeApp::getQrCode($path,'system_url'.md5($path).'.png'));


        include wl_template('diy/preview');
    }
    /**
     * Comment: 根据状态获取商品信息
     * Author: zzw
     * @param $plugin int    商品类型 1=抢购  2=团购  3=拼团  5=优惠券
     * @param $search string 搜索内容
     * @return array
     */
    protected function getGoods($plugin, $search) {
        global $_W;
        $where = " AND a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        switch ($plugin) {
            case 1:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','rush') as `plugin` FROM "
                    . tablename(PDO_NAME . "rush_activity")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.sid = b.id WHERE a.status IN (1,2,3) {$where} AND a.name LIKE '%{$search}%'");//
                break;//抢购商品
            case 2:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','groupon') as `plugin` FROM "
                    . tablename(PDO_NAME . "groupon_activity")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.sid = b.id WHERE a.status IN (1,2,3) {$where}  AND a.name LIKE '%{$search}%'");
                break;//团购商品
            case 3:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','wlfightgroup') as `plugin` FROM "
                    . tablename(PDO_NAME . "fightgroup_goods")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.merchantid = b.id WHERE a.status IN (1,2,3) {$where} AND b.storename != '' AND a.name LIKE '%{$search}%'");
                break;//拼团商品
            case 4:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','package') as `plugin` FROM "
                    . tablename(PDO_NAME . "package")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.merchantid = b.id WHERE a.status = 1 {$where} AND b.storename != '' AND a.title LIKE '%{$search}%'");
                break;//大礼包
            case 5:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','coupon') as `plugin` FROM "
                    . tablename(PDO_NAME . "couponlist")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.merchantid = b.id WHERE a.status IN (1,2,3) {$where} AND b.storename != '' AND a.title LIKE '%{$search}%'");
                break;//优惠券
            case 6:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','halfcard') as `plugin` FROM "
                    . tablename(PDO_NAME . "halfcardlist")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.merchantid = b.id WHERE a.status = 1 {$where} AND b.storename != '' AND a.title LIKE '%{$search}%'");
                break;//折扣卡
            case 7:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','bargain') as `plugin` FROM "
                    . tablename(PDO_NAME . "bargain_activity")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.sid = b.id WHERE a.status IN (1,2,3) {$where} AND b.storename != '' AND a.name LIKE '%{$search}%'");
                break;//砍价
            case 8:
                $goods = pdo_fetchall("SELECT id,REPLACE('table','table','integral') as `plugin` FROM "
                    . tablename(PDO_NAME . "consumption_goods")
                    . " WHERE status = 1 AND uniacid = {$_W['uniacid']} AND title LIKE '%{$search}%'");
                break;//砍价
            case 9:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','activity') as `plugin` FROM "
                                      . tablename(PDO_NAME . "activitylist")
                                      . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                                      . " b ON a.sid = b.id WHERE a.status IN (1,2) {$where} AND b.storename != '' AND a.title LIKE '%{$search}%'");
                break;//同城活动
            case 10:
                $goods = pdo_fetchall("SELECT a.id,REPLACE('table','table','citydelivery') as `plugin` FROM "
                    . tablename(PDO_NAME . "delivery_activity")
                    . " a LEFT JOIN " . tablename(PDO_NAME . "merchantdata")
                    . " b ON a.sid = b.id WHERE a.status = 2 {$where} AND b.storename != '' AND a.name LIKE '%{$search}%'");
                break;//配送商品
        }
        return $goods;
    }
    /**
     * Comment: 在这里配置diy编辑页面中选项卡组件的详细信息
     * Author: zzw
     */
    public function getOption() {
        //商家sotre;抢购rush;卡券coupon;特权halfcard;拼团fight;同城pocket;活动activity;团购groupon;砍价bargain;积分consumption;礼包package
        $configInfo = [
            ['status' => 1 , 'name' => '商家' , 'plugin' => '' , 'nickname' => '商家' , 'type' => 'store'] ,
            ['status' => 1 , 'name' => '抢购' , 'plugin' => 'rush' , 'nickname' => '抢购' , 'type' => 'rush'] ,
            ['status' => 1 , 'name' => '卡券' , 'plugin' => 'wlcoupon' , 'nickname' => '卡券' , 'type' => 'coupon'] ,
            ['status' => 1 , 'name' => '特权' , 'plugin' => 'halfcard' , 'nickname' => '特权' , 'type' => 'halfcard'] ,
            ['status' => 1 , 'name' => '拼团' , 'plugin' => 'wlfightgroup' , 'nickname' => '拼团' , 'type' => 'fight'] ,
            ['status' => 1 , 'name' => '同城' , 'plugin' => 'pocket' , 'nickname' => '同城' , 'type' => 'pocket'] ,
            ['status' => 1 , 'name' => '团购' , 'plugin' => 'groupon' , 'nickname' => '团购' , 'type' => 'groupon'] ,
            ['status' => 1 , 'name' => '砍价' , 'plugin' => 'bargain' , 'nickname' => '砍价' , 'type' => 'bargain'] ,
            ['status' => 1 , 'name' => '积分' , 'plugin' => '' , 'nickname' => '积分' , 'type' => 'consumption'] ,
            ['status' => 1 , 'name' => '礼包' , 'plugin' => 'halfcard' , 'nickname' => '礼包' , 'type' => 'package'] ,
            ['status' => 1 , 'name' => '活动' , 'plugin' => 'activity' , 'nickname' => '活动' , 'type' => 'activity'] ,
            ['status' => 1 , 'name' => '招聘' , 'plugin' => 'recruit' , 'nickname' => '招聘' , 'type' => 'recruit'] ,
        ];
        $data       = array();
        foreach ($configInfo as $k => &$v) {
            if ($v['plugin']) {
                if (!p($v['plugin'])) {
                    unset($configInfo[$k]);
                    continue;
                }
            }
            unset($v['plugin']);
            $name = 'C012345678910' . (count($data) + 1);
            $v['sort'] = (count($data) + 1);
            $data[$name] = $v;
        }

        echo json_encode($data, JSON_UNESCAPED_UNICODE);
    }
    /**
     * Comment: 获取社群信息
     * Author: zzw
     * Date: 2019/11/21 14:52
     */
    public function getCommunityInfo() {
        global $_W, $_GPC;
        #1、参数获取
        $id = $_GPC['id'] OR Commons::sRenderError('id错误，请刷新重试！');
        #2、获取社群信息
        $info = pdo_get(PDO_NAME . "community", ['id' => $id], ['communname', 'commundesc', 'communimg', 'communqrcode']);
        $info['communimg'] = tomedia($info['communimg']);
        $info['communqrcode'] = tomedia($info['communqrcode']);

        Commons::sRenderSuccess('社群信息', $info);
    }


}