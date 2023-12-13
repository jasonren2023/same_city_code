<?php
defined('IN_IA') or exit('Access Denied');

/**
 * Comment: 自定义菜单默认菜单信息
 * Author: zzw
 * Class DiyMenu
 */
class DiyMenu {
    /**
     * Comment: 默认菜单组件配置信息
     * Author: zzw
     * Date: 2019/8/9 15:55
     * @param $key
     * @return array
     */
    protected static function menuSetInfo($key){
        switch ($key){
            case 'home':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/index/index'),
                    'iconclass' => 'icon-home' ,
                    'text'      => '首页' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/index/index'
                ];
                break;//首页按钮
            case 'comment':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/headline/index') ,
                    'iconclass' => 'icon-comment' ,
                    'text'      => '头条' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/headline/index' ,
                ];
                break;//头条按钮
            case 'shop':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/index/diypage?type=8') ,
                    'iconclass' => 'icon-shop' ,
                    'text'      => '好店' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/index/diypage?type=8'
                ];
                break;//好店按钮
            case 'my':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/userCenter/userCenter') ,
                    'iconclass' => 'icon-my' ,
                    'text'      => '我的' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/userCenter/userCenter'
                ];
                break;//我的按钮
            case 'news_light':
                //获取  一卡通 自定义文字
                $set = Setting::wlsetting_read("trade");
                $diyText = $set['halfcardtext'] ? : '一卡通';

                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/memberCard/memberCard'),
                    'iconclass' => 'icon-news_light' ,
                    'text'      => $diyText ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/memberCard/memberCard'
                ];
                break;//一卡通
            case 'activity':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/Settled/Settled') ,
                    'iconclass' => 'icon-activity' ,
                    'text'      => '入驻' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/Settled/Settled'
                ];
                break;//入驻
            case 'rush_home':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/index/diypage?type=3'),
                    'iconclass' => 'icon-countdown' ,
                    'text'      => '抢购' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/index/diypage?type=3'
                ];
                break;//抢购首页
            case 'group_home':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/index/diypage?type=4'),
                    'iconclass' => 'icon-tansuoa' ,
                    'text'      => '团购' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/index/diypage?type=4'
                ];
                break;//团购首页
            case 'coupon_home':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/index/diypage?type=5'),
                    'iconclass' => 'icon-choiceness' ,
                    'text'      => '卡券' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/index/diypage?type=5'
                ];
                break;//卡券首页
            case 'my_coupon':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages/coupon/coupon'),
                    'iconclass' => 'icon-newshot' ,
                    'text'      => '我的卡券' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages/coupon/coupon'
                ];
                break;//我的卡券
            case 'order_list':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages/orderList/orderList'),
                    'iconclass' => 'icon-form' ,
                    'text'      => '订单' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages/orderList/orderList'
                ];
                break;//我的订单
            case 'fight_home':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/index/diypage?type=6'),
                    'iconclass' => 'icon-group_light' ,
                    'text'      => '拼团' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/index/diypage?type=6'
                ];
                break;//拼团首页
            case 'bargain_home':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/index/diypage?type=7'),
                    'iconclass' => 'icon-tansuoa' ,
                    'text'      => '砍价' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/index/diypage?type=7'
                ];
                break;//砍价首页
            case 'pocket_home':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/pocketIInformant/pocketIInformant'),
                    'iconclass' => 'icon-news' ,
                    'text'      => '掌上信息' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/pocketIInformant/pocketIInformant'
                ];
                break;//掌上信息
            case 'send_pocket':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/sendInformation/sendInformation'),
                    'iconclass' => 'icon-voice' ,
                    'text'      => '信息发布' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/sendInformation/sendInformation'
                ];
                break;//信息发布
            case 'my_pocket':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages/myPost/myPost'),
                    'iconclass' => 'icon-my' ,
                    'text'      => '我的帖子' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages/myPost/myPost'
                ];
                break;//我的帖子
            case 'consumption':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages/integral/integralShop/integralShop'),
                    'iconclass' => 'icon-shop' ,
                    'text'      => '积分商城' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages/integral/integralShop/integralShop'
                ];
                break;//积分商城
            case 'sign_home':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages/signdesk/index/index'),
                    'iconclass' => 'icon-dingdanyichenggong' ,
                    'text'      => '签到中心' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages/signdesk/index/index'
                ];
                break;//签到中心
            case 'sign_record':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages/signdesk/record/record'),
                    'iconclass' => 'icon-liebiao1' ,
                    'text'      => '签到记录' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages/signdesk/record/record'
                ];
                break;//签到记录
            case 'sign_rank':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages/signdesk/rank/rank'),
                    'iconclass' => 'icon-medal_light' ,
                    'text'      => '签到排行' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages/signdesk/rank/rank'
                ];
                break;//签到排行
            case 'card_store':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/index/diypage?type=13'),
                    'iconclass' => 'icon-news_light' ,
                    'text'      => '名片库' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/index/diypage?type=13'
                ];
                break;//名片库
            case 'my_business_card':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages/businesscard/carddetail/renewcarddetail'),
                    'iconclass' => 'icon-news_light' ,
                    'text'      => '我的名片' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages/businesscard/carddetail/renewcarddetail'
                ];
                break;//我的名片
            case 'business_card_holder':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages/businesscard/mycard/mycard'),
                    'iconclass' => 'icon-news_light' ,
                    'text'      => '名片夹' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages/businesscard/mycard/mycard'
                ];
                break;//名片夹
            case 'recruit':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/hirePlatform/recruitmentList/recruitmentList'),
                    'iconclass' => 'icon-friend' ,
                    'text'      => '招聘列表' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/hirePlatform/recruitmentList/recruitmentList'
                ];
                break;//招聘列表
            case 'resume':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/hirePlatform/curriculumVitae/curriculumVitae'),
                    'iconclass' => 'icon-xiangqing' ,
                    'text'      => '简历列表' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/hirePlatform/curriculumVitae/curriculumVitae'
                ];
                break;//简历列表
            case 'companies':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/hirePlatform/companiesList/companiesList'),
                    'iconclass' => 'icon-jiudian' ,
                    'text'      => '企业列表' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/hirePlatform/companiesList/companiesList'
                ];
                break;//企业列表
            case 'dating_home':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/index/diypage?type=16'),
                    'iconclass' => 'icon-home' ,
                    'text'      => '首页' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/index/diypage?type=16'
                ];
                break;//相亲交友首页
            case 'dating_member':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/blindDate/member/all'),
                    'iconclass' => 'icon-my_light' ,
                    'text'      => '会员列表' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/blindDate/member/all'
                ];
                break;//相亲交友会员列表
            case 'dating_recommend':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/blindDate/recommend'),
                    'iconclass' => 'icon-favor' ,
                    'text'      => '推荐' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/blindDate/recommend'
                ];
                break;//相亲交友推荐
            case 'dating_my':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/blindDate/personal'),
                    'iconclass' => 'icon-my' ,
                    'text'      => '个人中心' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/blindDate/personal'
                ];
                break;//相亲交友推荐
            case 'house_keep_home':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/index/diypage?type=18'),
                    'iconclass' => 'icon-home' ,
                    'text'      => '首页' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/index/diypage?type=18'
                ];
                break;//家政服务 - 首页
            case 'house_keep_shop':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/homemaking/homemakingMerchant/homemakingMerchant'),
                    'iconclass' => 'icon-goods' ,
                    'text'      => '商家' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/homemaking/homemakingMerchant/homemakingMerchant'
                ];
                break;//家政服务 - 商家
            case 'house_keep_send':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/homemaking/postDemand/postDemand'),
                    'iconclass' => 'icon-add' ,
                    'text'      => '发布' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/homemaking/postDemand/postDemand'
                ];
                break;//家政服务 - 发布
            case 'house_keep_order':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/homemaking/myorderForm/myorderForm'),
                    'iconclass' => 'icon-form' ,
                    'text'      => '订单' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/homemaking/myorderForm/myorderForm'
                ];
                break;//家政服务 - 订单
            case 'house_keep_demand':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/homemaking/customerDemand/customerDemand'),
                    'iconclass' => 'icon-form' ,
                    'text'      => '需求' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/homemaking/customerDemand/customerDemand'
                ];
                break;//家政服务 - 订单
            case 'house_keep_my':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/homemaking/homeUser/homeUser'),
                    'iconclass' => 'icon-my' ,
                    'text'      => '我的' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/homemaking/homeUser/homeUser'
                ];
                break;//家政服务 - 我的
            case 'yellow_home':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/phoneBook/phoneBook'),
                    'iconclass' => 'icon-home' ,
                    'text'      => '首页' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/phoneBook/phoneBook'
                ];
                break;//黄页114 - 首页
            case 'yellow_cate':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/phoneBook/phoneClass/phoneClass'),
                    'iconclass' => 'icon-search_list_light' ,
                    'text'      => '分类' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/phoneBook/phoneClass/phoneClass'
                ];
                break;//黄页114 - 分类
            case 'yellow_join':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/phoneBook/enterForm/enterForm'),
                    'iconclass' => 'icon-tianjialeimu' ,
                    'text'      => '入驻' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/phoneBook/enterForm/enterForm'
                ];
                break;//黄页114 - 入驻
            case 'yellow_collect':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/phoneBook/yellowGoods/yellowGoods'),
                    'iconclass' => 'icon-tianjialeimu' ,
                    'text'      => '收藏' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/phoneBook/yellowGoods/yellowGoods'
                ];
                break;//黄页114 - 收藏
            case 'yellow_my':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/phoneBook/myGoods/myGoods'),
                    'iconclass' => 'icon-wode' ,
                    'text'      => '我的' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/phoneBook/myGoods/myGoods'
                ];
                break;//黄页114 - 我的
            case 'house_home':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/houseproperty/houseproperty'),
                    'iconclass' => 'icon-weilaijiudian' ,
                    'text'      => '房产' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/houseproperty/houseproperty'
                ];
                break;//房产 - 首页
            case 'house_send':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/houseproperty/secondaryrelease/secondaryrelease'),
                    'iconclass' => 'icon-add' ,
                    'text'      => '发布' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/houseproperty/secondaryrelease/secondaryrelease'
                ];
                break;//房产 - 发布
            case 'house_list':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/houseproperty/anewhouse/anewhouse'),
                    'iconclass' => 'icon-shangquanxiao' ,
                    'text'      => '楼盘' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/houseproperty/anewhouse/anewhouse'
                ];
                break;//房产 - 楼盘
            case 'old_house_send':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/houseproperty/secondaryrelease/secondaryrelease'),
                    'iconclass' => 'icon-add' ,
                    'text'      => '发布二手房' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/houseproperty/secondaryrelease/secondaryrelease'
                ];
                break;//二手房房产 - 发布
            case 'renting_send':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/houseproperty/releaserental/releaserental'),
                    'iconclass' => 'icon-add' ,
                    'text'      => '发布租房' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/houseproperty/releaserental/releaserental'
                ];
                break;//租房房产 - 发布
//            case 'house_recommend':
//                return [
//                    'imgurl'    => '' ,
//                    'linkurl'   => h5_url('pages/mainPages/userCenter/userCenter') ,
//                    'iconclass' => 'icon-house_recommend' ,
//                    'text'      => '推荐' ,
//                    'url_type'  => '' ,
//                    'page_path' => 'pages/mainPages/userCenter/userCenter'
//                ];
//                break;//房产 - 推荐
//            case 'house_information':
//                return [
//                    'imgurl'    => '' ,
//                    'linkurl'   => h5_url('pages/subPages2/houseproperty/realestateinformation/realestateinformation') ,
//                    'iconclass' => 'icon-house_information' ,
//                    'text'      => '资讯' ,
//                    'url_type'  => '' ,
//                    'page_path' => 'pages/subPages2/houseproperty/realestateinformation/realestateinformation'
//                ];
//                break;//房产 - 资讯
            case 'my_house':
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/subPages2/houseproperty/personalcenter/personalcenter') ,
                    'iconclass' => 'icon-my' ,
                    'text'      => '我的' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/houseproperty/personalcenter/personalcenter'
                ];
                break;//我的按钮
            case 'house_new_house':
                return [
                    'imgurl'    => tomedia('../addons/'.MODULE_NAME.'/h5/resource/image/house_icon_newhouse.png') ,
                    'linkurl'   => h5_url('pages/subPages2/houseproperty/anewhouse/anewhouse') ,
                    'iconclass' => 'icon-jiudian1' ,
                    'text'      => '新房' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/houseproperty/anewhouse/anewhouse'
                ];
                break;//房产 - 新房
            case 'house_old_house':
                return [
                    'imgurl'    => tomedia('../addons/'.MODULE_NAME.'/h5/resource/image/house_icon_oldhouse.png') ,
                    'linkurl'   => h5_url('pages/subPages2/houseproperty/secondhandhouse/secondhandhouse') ,
                    'iconclass' => 'icon-home_light' ,
                    'text'      => '二手房' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/houseproperty/secondhandhouse/secondhandhouse'
                ];
                break;//房产 - 二手房
            case 'house_renting':
                return [
                    'imgurl'    => tomedia('../addons/'.MODULE_NAME.'/h5/resource/image/house_icon_renting.png') ,
                    'linkurl'   => h5_url('pages/subPages2/houseproperty/rentahouse/rentahouse') ,
                    'iconclass' => 'icon-zhufang' ,
                    'text'      => '租房' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/subPages2/houseproperty/rentahouse/rentahouse'
                ];
                break;//房产 - 租房

            case 'hotelindex':
                return [
                    'imgurl'    => '',
                    'linkurl'   => h5_url('pagesA/hotelhomepage/hotelhomepage') ,
                    'iconclass' => 'icon-jiudian' ,
                    'text'      => '酒店' ,
                    'url_type'  => '' ,
                    'page_path' => 'pagesA/hotelhomepage/hotelhomepage'
                ];
                break;//酒店首页
            case 'hotellist':
                return [
                    'imgurl'    => '',
                    'linkurl'   => h5_url('pagesA/hotelhomepage/hotellist/hotellist') ,
                    'iconclass' => 'icon-mulu' ,
                    'text'      => '列表' ,
                    'url_type'  => '' ,
                    'page_path' => 'pagesA/hotelhomepage/hotellist/hotellist'
                ];
                break;//酒店列表
            default:
                return [
                    'imgurl'    => '' ,
                    'linkurl'   => h5_url('pages/mainPages/member/index/index') ,
                    'iconclass' => 'icon-home' ,
                    'text'      => '首页' ,
                    'url_type'  => '' ,
                    'page_path' => 'pages/mainPages/member/index/index'
                ];//首页按钮My business card.
        }
    }
    /**
     * Comment: 头条默认菜单数据配置
     * Author: zzw
     * Date: 2019/7/11 16:43
     */
    public static function defaultHeadlineMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '平台底部默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '平台底部默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#999999' ,
                    'iconcoloron' => '#FE433F' ,
                    'textcolor'   => '#999999' ,
                    'textcoloron' => '#FE433F' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('home')  ,
                    'M0123456789104' => self::menuSetInfo('comment') ,
                    'M0123456789102' => self::menuSetInfo('shop') ,
                    'M0123456789105' => self::menuSetInfo('my')
                ] ,
            ] ,
        ];

        return $default;
    }
    /**
     * Comment: 默认的平台底部菜单(首页菜单)数据配置
     * Author: zzw
     * Date: 2019/7/25 16:02
     */
    public static function defaultBottomMenu(){
        global $_W,$_GPC;
        //判断后台是否设置公用菜单
        $foot = Setting::agentsetting_read('foot');
        //判断使用菜单类型：0=使用默认；1=使用当前自定义菜单
        if($foot['status'] == 1){
            $navstyle = '2';
            $foot['list'] = array_values($foot['list']);
            foreach($foot['list'] as $key => $val){
                //判断是否开启：0=关，1=开启
                if($val['switch']== 1){
                    $data['M012345678910'.$key] = [
                        'default_img' => tomedia($val['default_img']),
                        'select_img'  => tomedia($val['selected_img']),
                        'imgurl'      => '' ,
                        'linkurl'     => $val['link'],
                        'iconclass'   => '' ,
                        'text'        => $val['diy_name']? :$val['default_name'],
                        'url_type'    => '' ,
                        'page_path'   => str_replace('?i='.$_W['uniacid'],'',str_replace('i='.$_W['uniacid'].'&','',$val['link'])),
                    ];
                }
            }
        }else{
            $navstyle = '0';
            $data = [
                'M0123456789101' => self::menuSetInfo('home')  ,
                'M0123456789102' => self::menuSetInfo('shop') ,
                'M0123456789103' => self::menuSetInfo('news_light') ,
                'M0123456789104' => self::menuSetInfo('activity'),
                'M0123456789105' => self::menuSetInfo('my')
            ];
        }
        //信息拼装
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '平台底部默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '平台底部默认菜单' ,
                'params'     => [
                    'navstyle' => $navstyle ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#999999' ,
                    'iconcoloron' => '#FE433F' ,
                    'textcolor'   => '#999999' ,
                    'textcoloron' => '#FE433F' ,
                ] ,
                'data'       => $data
            ] ,
        ];

        return $default;
    }
    /**
     * Comment: 抢购首页默认菜单数据配置
     * Author: zzw
     * Date: 2019/8/9 17:37
     * @return array
     */
    public static function defaultRushMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '抢购首页默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '抢购首页默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#999999' ,
                    'iconcoloron' => '#FE433F' ,
                    'textcolor'   => '#999999' ,
                    'textcoloron' => '#FE433F' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('home')  ,
                    'M0123456789102' => self::menuSetInfo('rush_home') ,
                    'M0123456789103' => self::menuSetInfo('order_list') ,
                    'M0123456789104' => self::menuSetInfo('my'),
                ] ,
            ] ,
        ];

        return $default;
    }
    /**
     * Comment: 团购首页默认菜单数据配置
     * Author: zzw
     * Date: 2019/8/9 17:39
     * @return array
     */
    public static function defaultGroupMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '团购首页默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '团购首页默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#999999' ,
                    'iconcoloron' => '#FE433F' ,
                    'textcolor'   => '#999999' ,
                    'textcoloron' => '#FE433F' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('home') ,
                    'M0123456789102' => self::menuSetInfo('group_home') ,
                    'M0123456789103' => self::menuSetInfo('order_list') ,
                    'M0123456789104' => self::menuSetInfo('my'),
                ] ,
            ] ,
        ];

        return $default;
    }
    /**
     * Comment: 卡券首页默认菜单数据配置
     * Author: zzw
     * Date: 2019/8/9 17:43
     */
    public static function defaultCouponMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '卡券首页默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '卡券首页默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#999999' ,
                    'iconcoloron' => '#FE433F' ,
                    'textcolor'   => '#999999' ,
                    'textcoloron' => '#FE433F' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('home') ,
                    'M0123456789102' => self::menuSetInfo('coupon_home') ,
                    'M0123456789103' => self::menuSetInfo('my_coupon') ,
                    'M0123456789104' => self::menuSetInfo('my'),
                ] ,
            ] ,
        ];

        return $default;
    }
    /**
     * Comment: 拼团首页默认菜单数据配置
     * Author: zzw
     * Date: 2019/8/9 17:57
     * @return array
     */
    public static function defaultFightMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '拼团首页默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '拼团首页默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#999999' ,
                    'iconcoloron' => '#FE433F' ,
                    'textcolor'   => '#999999' ,
                    'textcoloron' => '#FE433F' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('home') ,
                    'M0123456789102' => self::menuSetInfo('fight_home') ,
                    'M0123456789103' => self::menuSetInfo('order_list') ,
                    'M0123456789104' => self::menuSetInfo('my'),
                ] ,
            ] ,
        ];

        return $default;
    }
    /**
     * Comment: 砍价首页默认菜单数据配置
     * Author: zzw
     * Date: 2019/8/9 18:03
     * @return array
     */
    public static function defaultBargainMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '砍价首页默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '砍价首页默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#999999' ,
                    'iconcoloron' => '#FE433F' ,
                    'textcolor'   => '#999999' ,
                    'textcoloron' => '#FE433F' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('home') ,
                    'M0123456789102' => self::menuSetInfo('bargain_home') ,
                    'M0123456789103' => self::menuSetInfo('order_list') ,
                    'M0123456789104' => self::menuSetInfo('my'),
                ] ,
            ] ,
        ];

        return $default;
    }
    /**
     * Comment: 掌上信息首页默认菜单数据配置
     * Author: zzw
     * Date: 2019/10/12 15:56
     * @return array
     */
    public static function defaultPocketMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '掌上信息默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '掌上信息默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#999999' ,
                    'iconcoloron' => '#FE433F' ,
                    'textcolor'   => '#999999' ,
                    'textcoloron' => '#FE433F' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('home')  ,
                    'M0123456789102' => self::menuSetInfo('pocket_home') ,
                    'M0123456789103' => self::menuSetInfo('send_pocket') ,
                    'M0123456789104' => self::menuSetInfo('my_pocket'),
                ] ,
            ] ,
        ];

        return $default;
    }
    /**
     * Comment: 积分商城首页默认菜单数据配置
     * Author: zzw
     * Date: 2019/10/12 16:10
     * @return array
     */
    public static function defaultConsumptionMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '积分商城默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '积分商城默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#999999' ,
                    'iconcoloron' => '#FE433F' ,
                    'textcolor'   => '#999999' ,
                    'textcoloron' => '#FE433F' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('home')  ,
                    'M0123456789102' => self::menuSetInfo('consumption') ,
                    'M0123456789103' => self::menuSetInfo('my'),
                ] ,
            ] ,
        ];

        return $default;
    }
    /**
     * Comment: 积分签到首页默认菜单数据配置
     * Author: zzw
     * Date: 2019/10/12 16:12
     * @return array
     */
    public static function defaultSignMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '积分签到默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '积分签到默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#999999' ,
                    'iconcoloron' => '#FE433F' ,
                    'textcolor'   => '#999999' ,
                    'textcoloron' => '#FE433F' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('home'),
                    'M0123456789102' => self::menuSetInfo('sign_home') ,
                    'M0123456789103' => self::menuSetInfo('sign_record') ,
                    'M0123456789104' => self::menuSetInfo('sign_rank') ,
                    'M0123456789105' => self::menuSetInfo('my'),
                ] ,
            ] ,
        ];

        return $default;
    }
    /**
     * Comment: 名片首页默认菜单
     * Author: zzw
     * Date: 2019/12/25 15:14
     * @return array
     */
    public static function defaultCardMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '名片首页默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '名片首页默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#999999' ,
                    'iconcoloron' => '#FE433F' ,
                    'textcolor'   => '#999999' ,
                    'textcoloron' => '#FE433F' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('home'),
                    'M0123456789102' => self::menuSetInfo('card_store'),
                    'M0123456789103' => self::menuSetInfo('my_business_card'),
                    'M0123456789104' => self::menuSetInfo('business_card_holder'),
                    'M0123456789105' => self::menuSetInfo('my'),
                ] ,
            ] ,
        ];

        return $default;
    }
    /**
     * Comment: 招聘首页默认菜单
     * Author: zzw
     * Date: 2021/1/15 15:43
     * @return array
     */
    public static function defaultRecruitMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '招聘首页默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '招聘首页默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#999999' ,
                    'iconcoloron' => '#FE433F' ,
                    'textcolor'   => '#999999' ,
                    'textcoloron' => '#FE433F' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('home'),
                    'M0123456789102' => self::menuSetInfo('recruit'),
                    //'M0123456789103' => self::menuSetInfo('resume'),
                    'M0123456789104' => self::menuSetInfo('companies'),
                    'M0123456789105' => self::menuSetInfo('my'),
                ] ,
            ] ,
        ];

        return $default;
    }
    /**
     * Comment: 相亲交友默认菜单
     * Author: zzw
     * Date: 2021/3/18 15:12
     * @return array
     */
    public static function defaultDatingMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '相亲交友默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '相亲交友默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#999999' ,
                    'iconcoloron' => '#FE433F' ,
                    'textcolor'   => '#999999' ,
                    'textcoloron' => '#FE433F' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('dating_home'),
                    'M0123456789102' => self::menuSetInfo('dating_member'),
                    'M0123456789104' => self::menuSetInfo('dating_recommend'),
                    'M0123456789105' => self::menuSetInfo('dating_my'),
                ] ,
            ] ,
        ];

        return $default;
    }
    /**
     * Comment: 家政服务默认菜单
     * Author: zzw
     * Date: 2021/5/10 15:41
     * @return array
     */
    public static function defaultHouseKeepMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '家政服务默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '家政服务默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#C8CDE6' ,
                    'iconcoloron' => '#6CA3FD' ,
                    'textcolor'   => '#C8CDE6' ,
                    'textcoloron' => '#6CA3FD' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('house_keep_home'),
                    'M0123456789102' => self::menuSetInfo('house_keep_shop'),
                    'M0123456789104' => self::menuSetInfo('house_keep_send'),
                    'M0123456789105' => self::menuSetInfo('house_keep_demand'),
                    'M0123456789106' => self::menuSetInfo('house_keep_my'),
                ] ,
            ] ,
        ];

        return $default;
    }

    /**
     * Comment: 家政服务默认菜单
     * Author: zzw
     * Date: 2021/5/10 15:41
     * @return array
     */
    public static function defaultYellowMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '114默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '114默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#7C858D' ,
                    'iconcoloron' => '#ff4444' ,
                    'textcolor'   => '#7C858D' ,
                    'textcoloron' => '#ff4444' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('yellow_home'),
                    'M0123456789102' => self::menuSetInfo('yellow_cate'),
                    'M0123456789104' => self::menuSetInfo('yellow_join'),
                    'M0123456789105' => self::menuSetInfo('yellow_collect'),
                    'M0123456789106' => self::menuSetInfo('yellow_my'),
                ] ,
            ] ,
        ];

        return $default;
    }


    /**
     * Comment: 房产默认菜单
     * @return array
     */
    public static function defaultHouseMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '房产默认菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '房产默认菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#C8CDE6' ,
                    'iconcoloron' => '#6CA3FD' ,
                    'textcolor'   => '#C8CDE6' ,
                    'textcoloron' => '#6CA3FD' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('home'),
                    'M0123456789102' => self::menuSetInfo('house_home'),
                    'M0123456789103' => self::menuSetInfo('house_list'),
//                    'M0123456789103' => self::menuSetInfo('house_send'),
//                    'M0123456789102' => self::menuSetInfo('house_recommend'),
//                    'M0123456789104' => self::menuSetInfo('house_information'),
                    'M0123456789105' => self::menuSetInfo('my_house'),
                ] ,
            ] ,
        ];

        return $default;
    }


    /**
     * Comment: 房产列表菜单
     * @return array
     */
    public static function defaultHouseListMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '房产列表菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '房产列表菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#C8CDE6' ,
                    'iconcoloron' => '#6CA3FD' ,
                    'textcolor'   => '#C8CDE6' ,
                    'textcoloron' => '#6CA3FD' ,
                ] ,
                'data'       => [
                    'M0123456789106' => self::menuSetInfo('house_new_house'),
                    'M0123456789107' => self::menuSetInfo('house_old_house'),
                    'M0123456789108' => self::menuSetInfo('house_renting'),
                ] ,
            ] ,
        ];

        return $default;
    }



    /**
     * Comment: 发布房产菜单
     * @return array
     */
    public static function defaultReleaseHouseMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '发布房产菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '发布房产菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#C8CDE6' ,
                    'iconcoloron' => '#6CA3FD' ,
                    'textcolor'   => '#C8CDE6' ,
                    'textcoloron' => '#6CA3FD' ,
                ] ,
                'data'       => [
                    'M0123456789103' => self::menuSetInfo('old_house_send'),
                    'M0123456789105' => self::menuSetInfo('renting_send'),
                ] ,
            ] ,
        ];

        return $default;
    }

    /**
     * Comment: 酒店首页菜单
     * @return array
     */
    public static function defaultHotelMenu(){
        global $_W,$_GPC;
        $default = [
            'id'           => -1 ,
            'uniacid'      => -1 ,
            'aid'          => -1 ,
            'name'         => '酒店页面菜单' ,
            'createtime'   => time() ,
            'lastedittime' => time() ,
            'menu_class'   => 1 ,
            'is_public'    => '' ,
            'data'         => [
                'menu_calss' => 1 ,
                'name'       => '酒店页面菜单' ,
                'params'     => [
                    'navstyle' => '0' ,
                    'navfloat' => 'top' ,
                ] ,
                'style'      => [
                    'bgcolor'     => '#FFFFFF' ,
                    'iconcolor'   => '#C8CDE6' ,
                    'iconcoloron' => '#6CA3FD' ,
                    'textcolor'   => '#C8CDE6' ,
                    'textcoloron' => '#6CA3FD' ,
                ] ,
                'data'       => [
                    'M0123456789101' => self::menuSetInfo('home'),
                    'M0123456789102' => self::menuSetInfo('hotelindex'),
                    'M0123456789103' => self::menuSetInfo('hotellist'),
                    'M0123456789104' => self::menuSetInfo('my'),
                ] ,
            ] ,
        ];

        return $default;
    }



    /**
     * Comment: 获取自定义装修菜单列表
     * Author: zzw
     * Date: 2020/10/9 9:44
     * @return array|bool|mixed
     */
    public static function getMenuList(){
        global $_W,$_GPC;
        $where = [
            'aid'        => [$_W['aid'],0] ,
            'uniacid'    => $_W['uniacid'] ,
            'menu_class' => 1
        ];
        $menus = pdo_getall(PDO_NAME . "diypage_menu" , $where , ['id' , 'name']);

        return $menus;
    }


}