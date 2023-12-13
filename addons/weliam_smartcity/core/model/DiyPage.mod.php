<?php
defined('IN_IA') or exit('Access Denied');

/**
 * Comment: 获取自定义页面默认页面信息
 * Author: zzw
 * Class DiyPage
 */
class DiyPage {
    #type：页面类型：2=商城首页;3=抢购首页;4=团购首页;5=卡券首页;6=拼团首页;7=砍价首页;8=好店首页;14=活动首页;
    #15=招聘首页;16=相亲交友;18=家政服务;20=家政服务
    /**
     * Comment: 默认组件配置信息
     * Author: zzw
     * Date: 2019/8/8 16:44
     * @param $key
     * @return array
     */
    public function defaultInfo($key,$params = []){
        global $_W,$_GPC;
        $info = [];
        switch ($key){
            case 'search2':
                //默认配置
                if($params['type'] == 15){
                    //招聘首页搜索
                    $info = [
                        'params'      => [
                            'placeholder' => '请输入企业/工作/人才名称' ,
                            'search_type' => 2,//1=搜索商品，2=搜索招聘信息
                        ] ,
                        'style'       => [
                            'inputbackground' => '#ffffff' ,
                            'background'      => '#f1f1f2' ,
                            'iconcolor'       => '#b4b4b4' ,
                            'color'           => '#757575' ,
                            'textalign'       => 'left' ,
                            'searchstyle'     => 'round' ,
                            'marginBottom'    => 0 ,
                            'areaColor'       => '#5c5c5c' ,
                        ] ,
                        'group_name'  => 'search' ,
                        'group_key'   => 1 ,
                        'id'          => 'search2' ,
                        'currentTime' => time() ,
                    ];
                }
                else if($params['type'] == 16){
                    //招聘首页搜索
                    $info = [
                        'params'      => [
                            'placeholder' => '请输入昵称' ,
                            'search_type' => 3,//1=搜索商品，2=搜索招聘信息，3=相亲交友搜索
                        ] ,
                        'style'       => [
                            'inputbackground' => '#ffffff' ,
                            'background'      => '#f1f1f2' ,
                            'iconcolor'       => '#b4b4b4' ,
                            'color'           => '#757575' ,
                            'textalign'       => 'left' ,
                            'searchstyle'     => 'round' ,
                            'marginBottom'    => 0 ,
                            'areaColor'       => '#5c5c5c' ,
                        ] ,
                        'group_name'  => 'search' ,
                        'group_key'   => 1 ,
                        'id'          => 'search2' ,
                        'currentTime' => time() ,
                    ];
                }
                else if($params['type'] == 18){
                    //招聘首页搜索
                    $info = [
                        'params'      => [
                            'placeholder' => '请输入搜索信息' ,
                            'search_type' => 4,//1=搜索商品，2=搜索招聘信息，3=相亲交友搜索，3=家政服务搜索
                        ] ,
                        'style'       => [
                            'inputbackground' => '#ffffff' ,
                            'background'      => '#f1f1f2' ,
                            'iconcolor'       => '#b4b4b4' ,
                            'color'           => '#757575' ,
                            'textalign'       => 'left' ,
                            'searchstyle'     => 'round' ,
                            'marginBottom'    => 0 ,
                            'areaColor'       => '#5c5c5c' ,
                        ] ,
                        'group_name'  => 'search' ,
                        'group_key'   => 1 ,
                        'id'          => 'search2' ,
                        'currentTime' => time() ,
                    ];
                }
                else if($params['type'] == 20){
                    //招聘首页搜索
                    $info = [
                        'params'      => [
                            'placeholder' => '请输入搜索信息' ,
                            'search_type' => 5,//1=搜索商品，2=搜索招聘信息，3=相亲交友搜索，4=家政服务搜索，5=房产搜索
                        ] ,
                        'style'       => [
                            'inputbackground' => '#ffffff' ,
                            'background'      => '#f1f1f2' ,
                            'iconcolor'       => '#b4b4b4' ,
                            'color'           => '#757575' ,
                            'textalign'       => 'left' ,
                            'searchstyle'     => 'round' ,
                            'marginBottom'    => 0 ,
                            'areaColor'       => '#5c5c5c' ,
                        ] ,
                        'group_name'  => 'search' ,
                        'group_key'   => 1 ,
                        'id'          => 'search2' ,
                        'currentTime' => time() ,
                    ];
                }
                else {
                    //其他页面
                    $info = [
                        'params'      => [
                            'placeholder' => '请输入关键字进行搜索' ,
                            'url'         => h5_url('pages/mainPages/search/search') ,
                            'areaname'    => '' ,
                            'search_type' => 1,//1=搜索商品，2=搜索招聘信息
                        ] ,
                        'style'       => [
                            'inputbackground' => '#F7F7F7' ,
                            'background'      => '#FFFFFF' ,
                            'iconcolor'       => '#b4b4b4' ,
                            'color'           => '#999999' ,
                            'textalign'       => 'left' ,
                            'searchstyle'     => 'round' ,
                            'marginBottom'    => 0 ,
                            'areaColor'       => '#333333' ,
                        ] ,
                        'group_name'  => 'search' ,
                        'group_key'   => 1 ,
                        'id'          => 'search2' ,
                        'currentTime' => time() ,
                    ];
                    if($params['type'] == 9){
                        unset($info['params']['search_type']);
                    }
                }
                break;//搜索组件二
            case 'banner':
                //获取平台配置的轮播信息
                switch ($params['type']){
                    case 2:$where['type'] = '0';break;//商城首页
                    case 3:$where['type'] = 4;break;//抢购首页
                    case 4:$where['type'] = 7;break;//团购首页
                    case 5:$where['type'] = 2;break;//卡券首页
                    case 6:$where['type'] = 8;break;//拼团首页
                    case 7:$where['type'] = 9;break;//砍价首页
                    case 8:$where['type'] = 1;break;//好店首页
                    case 9:$where['type'] = 11;break;//名片首页
                    case 14:$where['type'] = 14;break;//活动首页
                    case 15:$where['type'] = 15;break;//招聘首页
                    case 16:$where['type'] = 16;break;//相亲交友
                    case 18:$where['type'] = 18;break;//家政服务
                    case 20:$where['type'] = 20;break;//房产
                }
                $where['enabled'] = 1;
                $where['uniacid'] = $_W['uniacid'];
                $where['aid']     = $_W['aid'];
                $list = pdo_getall(PDO_NAME."adv",$where,'','','displayorder DESC');
                $set = Setting::wlsetting_read('base');
                //平台存在轮播图 添加图片到数组
                if(is_array($list) && count($list) > 0){
                    foreach($list as $key => $val){
                        $data['D123456789' . $key]['imgurl']  = tomedia($val['thumb']);
                        $data['D123456789' . $key]['linkurl'] = $val['link'];
                        $data['D123456789' . $key]['order'] = $key;
                    }
                    //默认配置
                    $info = [
                        'group'       => 'banner' ,
                        'params'      => [
                            'img_width' => $set['listwidth'] ? : 640,
                            'img_height' => $set['listheight'] ? : 300
                        ] ,
                        'style'       => [
                            'dotstyle'    => 'round' ,
                            'dotalign'    => 'right' ,
                            'side_margin' => 1 ,
                            'bottom'      => 0 ,
                        ] ,
                        'data'        => $data,
                        'id'          => 'banner' ,
                        'group_name'  => 'banner' ,
                        'group_key'   => 0 ,
                        'currentTime' => 1565246255 ,
                    ];
                }
                break;//轮播组件一
            case 'notice':
                //获取平台配置的公告信息
                $notice = pdo_getall(PDO_NAME . "notice", ['aid' => $_W['aid'], 'uniacid' => $_W['uniacid'], 'enabled' => 1],['id', 'title', 'link']);
                if(is_array($notice) && count($notice) > 0) {
                    foreach ($notice as $notice_k => $notice_v) {
                        $data[$notice_k]['id']    = $notice_v['id'];
                        $data[$notice_k]['title'] = $notice_v['title'];
                        if($notice_v['link']) $data[$notice_k]['link']  = $notice_v['link'];
                            else $data[$notice_k]['link']  = h5_url('pages/mainPages/noticeDetail/noticeDetail', ['id' => $notice_v['id']]);
                    }
                    $info = [
                        'group'       => 'notice' ,
                        'style'       => [
                            'marginbottom' => 0
                        ] ,
                        'id'          => 'notice' ,
                        'group_name'  => 'notice' ,
                        'group_key'   => 0 ,
                        'currentTime' => 1565246255 ,
                        'data'        => $data ,
                    ];
                }
                break;//公告组件一
            case 'menu':
                switch ($params['type']){
                    case 15:
                        $info = [
                            'params'      => [] ,
                            'style'       => [
                                'navstyle'     => 'radius' ,
                                'background'   => '#ffffff' ,
                                'rownum'       => 4 ,//每行数量
                                'showtype'     => 0 ,//显示方式：0=单页显示；1=多页滑动显示
                                'pagenum'      => 8 ,//每页数量
                                'marginbottom' => 0 ,
                                'dotstyle' => 'round' ,
                                'dotalign' => 'center' ,
                            ] ,
                            'data'        => [
                                'C123456789101' => [
                                    'imgurl' => tomedia('../addons/'.MODULE_NAME.'/web/resource/images/recruit_work.png'),
                                    'linkurl' => 'pages/subPages2/hirePlatform/recruitmentList/recruitmentList?job_type=1',
                                    'text' => '找工作',
                                    'color' => '#666666',
                                ],
                                'C123456789102' => [
                                    'imgurl' =>  tomedia('../addons/'.MODULE_NAME.'/web/resource/images/recruit_part.png'),
                                    'linkurl' => 'pages/subPages2/hirePlatform/recruitmentList/recruitmentList?job_type=2',
                                    'text' => '找兼职',
                                    'color' => '#666666',
                                ],
                                'C123456789103' => [
                                    'imgurl' =>  tomedia('../addons/'.MODULE_NAME.'/web/resource/images/recruit_resume.png'),
                                    'linkurl' => 'pages/subPages2/hirePlatform/postRecruitment/postRecruitment',
                                    'text' => '招人才',
                                    'color' => '#666666',
                                ],
                                'C123456789104' => [
                                    'imgurl' =>  tomedia('../addons/'.MODULE_NAME.'/web/resource/images/recruit_enterprise.png'),
                                    'linkurl' => 'pages/subPages2/hirePlatform/companiesList/companiesList',
                                    'text' => '名企招聘',
                                    'color' => '#666666',
                                ]
                            ],
                            'id'          => 'menu' ,
                            'currentTime' => time()
                        ];
                        break;//求职招聘
                    case 16:
                        $info = [
                            'params'      => [] ,
                            'style'       => [
                                'navstyle'     => 'radius' ,
                                'background'   => '#ffffff' ,
                                'rownum'       => 4 ,//每行数量
                                'showtype'     => 0 ,//显示方式：0=单页显示；1=多页滑动显示
                                'pagenum'      => 8 ,//每页数量
                                'marginbottom' => 0 ,
                                'dotstyle' => 'round' ,
                                'dotalign' => 'center' ,
                            ] ,
                            'data'        => [
                                'C123456789101' => [
                                    'imgurl' => tomedia('../addons/'.MODULE_NAME.'/web/resource/images/dating_home.png'),
                                    'linkurl' => 'pages/subPages2/blindDate/member/all',
                                    'text' => '会员列表',
                                    'color' => '#666666',
                                ],
                                'C123456789102' => [
                                    'imgurl' =>  tomedia('../addons/'.MODULE_NAME.'/web/resource/images/dating_video.png'),
                                    'linkurl' => 'pages/subPages2/blindDate/member/all?get_type=1',
                                    'text' => '视频相亲',
                                    'color' => '#666666',
                                ],
                                'C123456789103' => [
                                    'imgurl' =>  tomedia('../addons/'.MODULE_NAME.'/web/resource/images/dating_matchmaker.png'),
                                    'linkurl' => 'pages/subPages2/blindDate/matchmakerService',
                                    'text' => '红娘服务',
                                    'color' => '#666666',
                                ],
                                'C123456789104' => [
                                    'imgurl' =>  tomedia('../addons/'.MODULE_NAME.'/web/resource/images/dating_dynamic.png'),
                                    'linkurl' => 'pages/subPages2/blindDate/dynamics/index',
                                    'text' => '动态专区',
                                    'color' => '#666666',
                                ]
                            ],
                            'id'          => 'menu' ,
                            'currentTime' => time()
                        ];
                        break;//相亲交友
                    case 18:
                        $typeList = pdo_getall(PDO_NAME."housekeep_type",
                            ['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'status'=>1,'onelevelid'=>0],
                            ['id','title','img','type','url','color'],'','sort DESC,id DESC');//onelevelid
                        $houseKeepData = array_map(function ($item){
                            //获取链接
                            if($item['type'] == 1) $link = $item['url'];
                            else $link = 'pages/subPages2/homemaking/homemakingMerchant/homemakingMerchant?onelevelid='.$item['id'].'&title='.$item['title'];
                            //返回配置信息
                            return [
                                'imgurl'  => $item['img'] ? tomedia($item['img']) : '',
                                'linkurl' => $link,
                                'text'    => $item['title'],
                                'color'   => $item['color'] ? : '#666666',
                            ];
                        },$typeList);
                        //配置信息
                        $info = [
                            'params'      => [] ,
                            'style'       => [
                                'navstyle'     => '' ,
                                'background'   => '#ffffff' ,
                                'rownum'       => 5 ,//每行数量
                                'showtype'     => 1 ,//显示方式：0=单页显示；1=多页滑动显示
                                'pagenum'      => 10 ,//每页数量
                                'marginbottom' => 0 ,
                                'dotstyle' => 'round' ,
                                'dotalign' => 'center' ,
                            ] ,
                            'data'        => $houseKeepData,
                            'id'          => 'menu' ,
                            'currentTime' => time()
                        ];
                        break;//家政服务
                    case 20:
                        $info = [
                            'params'      => [] ,
                            'style'       => [
                                'navstyle'     => 'radius' ,
                                'background'   => '#ffffff' ,
                                'rownum'       => 5 ,//每行数量
                                'showtype'     => 0 ,//显示方式：0=单页显示；1=多页滑动显示
                                'pagenum'      => 10 ,//每页数量
                                'marginbottom' => 0 ,
                                'dotstyle' => 'round' ,
                                'dotalign' => 'center' ,
                            ] ,
                            'data'        => [
                                'C123456789101' => [
                                    'imgurl' => tomedia('../addons/'.MODULE_NAME.'/h5/resource/wxapp/house/house_all.png'),
                                    'linkurl' => 'pages/subPages2/houseproperty/anewhouse/anewhouse',
                                    'text' => '全部楼盘',
                                    'color' => '#666666',
                                ],
                                'C123456789106' => [
                                    'imgurl' =>  tomedia('../addons/'.MODULE_NAME.'/h5/resource/wxapp/house/house_apartment.png'),
                                    'linkurl' => 'pages/subPages2/houseproperty/residentiallist/residentiallist',
                                    'text' => '小区列表',
                                    'color' => '#666666',
                                ],
                                'C123456789103' => [
                                    'imgurl' =>  tomedia('../addons/'.MODULE_NAME.'/h5/resource/wxapp/house/house_map.png'),
                                    'linkurl' => 'pages/subPages2/houseproperty/mapsearchroom/mapsearchroom',
                                    'text' => '地图找房',
                                    'color' => '#666666',
                                ],
                                'C123456789105' => [
                                    'imgurl' =>  tomedia('../addons/'.MODULE_NAME.'/h5/resource/wxapp/house/house_adviser.png'),
                                    'linkurl' => 'pages/subPages2/houseproperty/adviserhouse/adviserhouse',
                                    'text' => '置业顾问',
                                    'color' => '#666666',
                                ],
                                'C123456789104' => [
                                    'imgurl' =>  tomedia('../addons/'.MODULE_NAME.'/h5/resource/wxapp/house/house_talk.png'),
                                    'linkurl' => 'pages/subPages/feedback/feedback',
                                    'text' => '信息反馈',
                                    'color' => '#666666',
                                ],
                            ],
                            'id'          => 'menu' ,
                            'currentTime' => time()
                        ];
                        break;//房产
                    default:
                        $list = pdo_getall(PDO_NAME . "nav", ['aid' => $_W['aid'], 'uniacid' => $_W['uniacid'], 'enabled' => 1, 'merchantid' => 0]
                            , ['id','displayorder', 'name', 'link','thumb','color'] ,'','displayorder DESC');
                        if(is_array($list) && count($list) > 0){
                            foreach ($list as $key => $val){
                                $data['D123456789' . $key]['imgurl']  = tomedia($val['thumb']);
                                $data['D123456789' . $key]['linkurl'] = $val['link'];
                                $data['D123456789' . $key]['text']    = $val['name'];
                                $data['D123456789' . $key]['color']   = $val['color'];
                            }
                            $info = [
                                'params'      => [] ,
                                'style'       => [
                                    'navstyle'     => 'circle' ,
                                    'background'   => '#ffffff' ,
                                    'rownum'       => 5 ,//每行数量
                                    'showtype'     => 1 ,//显示方式：0=单页显示；1=多页滑动显示
                                    'pagenum'      => 10 ,//每页数量
                                    'marginbottom' => 0 ,
                                    'dotstyle' => 'round' ,
                                    'dotalign' => 'center' ,
                                ] ,
                                'data'        => $data,
                                'id'          => 'menu' ,
                                'currentTime' => 1565246255
                            ];
                        }
                        break;//首页按钮
                }
                break;//按钮组组件一
            case 'menu2':
                if($params['type'] == 18){
                    $info = [
                        'params' => [],
                        'style' => [
                            'navstyle'     => 'radius',
                            'background'   => '#ffffff',
                            'rownum'       => 2,
                            'showtype'     => 0,
                            'pagenum'      => 4,
                            'marginbottom' => 0,
                            'dotstyle' => 'round' ,
                            'dotalign' => 'center' ,
                        ],
                        'data' => [
                            'C0123456789101' => [
                                'imgurl'  => tomedia('../addons/'.MODULE_NAME.'/h5/resource/wxapp/homemakingImg/fabujiazheng.png'),
                                'linkurl' => 'pages/subPages2/homemaking/postDemand/postDemand',
                                'text'    => '发布需求',
                                'color'   => '#ffffff',
                                'bgColor' => '#a59aff',
                            ],
                            'C0123456789102' => [
                                'imgurl'  => tomedia('../addons/'.MODULE_NAME.'/h5/resource/wxapp/homemakingImg/jiazhengruzhu.png'),
                                'linkurl' => 'pages/subPages2/homemaking/serviceIn/serviceIn',
                                'text'    => '服务者入驻',
                                'color'   => '#ffffff',
                                'bgColor' => '#FDAD28',
                            ],
                        ],
                        'group_name'  => 'menu',
                        'group_key'   => 1,
                        'id'          => 'menu2',
                        'currentTime' => time(),
                    ];
                }elseif($params['type'] == 20){
                    $new_house_num = pdo_count(PDO_NAME."new_house",['aid' => $_W['aid'], 'uniacid' => $_W['uniacid'],'status'=>1]);
                    $old_house_num = pdo_count(PDO_NAME."old_house",['aid' => $_W['aid'], 'uniacid' => $_W['uniacid'],'status'=>[2,3]]);
                    $renting_num = pdo_count(PDO_NAME."renting",['aid' => $_W['aid'], 'uniacid' => $_W['uniacid'],'status'=>[2,3]]);

                    $info = [
                        'params' => [],
                        'style' => [
                            'navstyle'     => 'radius',
                            'background'   => '#ffffff',
                            'rownum'       => 2,
                            'showtype'     => 0,
                            'pagenum'      => 4,
                            'marginbottom' => 0,
                            'dotstyle' => 'round' ,
                            'dotalign' => 'center' ,
                        ],
                        'data' => [
                            'C0123456789101' => [
                                'imgurl'  => tomedia('../addons/'.MODULE_NAME.'/h5/resource/wxapp/house/new_house.png'),
                                'linkurl' => 'pages/subPages2/houseproperty/anewhouse/anewhouse',
                                'text'    => '新房',
                                'color'   => '#ffffff',
                                'bgColor' => '#a59aff',
                                'count'   => $new_house_num,
                            ],
                            'C0123456789102' => [
                                'imgurl'  => tomedia('../addons/'.MODULE_NAME.'/h5/resource/wxapp/house/old_house.png'),
                                'linkurl' => 'pages/subPages2/houseproperty/secondhandhouse/secondhandhouse',
                                'text'    => '二手房',
                                'color'   => '#ffffff',
                                'bgColor' => '#FDAD28',
                                'count'   => $old_house_num,
                            ],
                            'C0123456789103' => [
                                'imgurl'  => tomedia('../addons/'.MODULE_NAME.'/h5/resource/wxapp/house/renting.png'),
                                'linkurl' => 'pages/subPages2/houseproperty/rentahouse/rentahouse',
                                'text'    => '租房',
                                'color'   => '#ffffff',
                                'bgColor' => '#FDAD28',
                                'count'   => $renting_num,
                            ],
                        ],
                        'group_name'  => 'menu',
                        'group_key'   => 1,
                        'id'          => 'menu2',
                        'currentTime' => time(),
                    ];
                }
                break;//按钮组组件二
            case 'picturew2':
                //获取平台添加的广告信息
                $list = pdo_fetchall("SELECT id,link,thumb,displayorder FROM ".tablename(PDO_NAME."banner")
                                     ." WHERE aid = {$_W['aid']} AND uniacid = {$_W['uniacid']} AND enabled = 1 ORDER BY displayorder DESC ");
                if(is_array($list) && count($list) > 0){
                    foreach($list as $key => $val){
                        $data['D123456789' . $key]['imgurl']  = tomedia($val['thumb']);
                        $data['D123456789' . $key]['linkurl'] = $val['link'];
                    }
                    $info = [
                        'params'      => [] ,
                        'style'       => [
                            'marginLeftRight' => 0 ,
                            'marginTopBottom' => 0 ,
                            'bgColor'         => '#FFFFFF' ,
                        ] ,
                        'data'        => $data ,
                        'group_name'  => 'picturew' ,
                        'group_key'   => 1 ,
                        'id'          => 'picturew2' ,
                        'currentTime' => 1565246255
                    ];
                }
                break;//图片橱窗组件二
            case 'community':
                //获取平台设置的社群信息
                switch ($params['type']){
                    case 2:
                        $id = Setting::agentsetting_read("community")['id'];
                        break;//商城社群
                    case 3:
                        $id = Setting::agentsetting_read("rush")['communityid'];
                        break;//抢购社群
                    case 4:
                        $id = Setting::agentsetting_read("groupon")['communityid'];
                        break;//团购社群
                    case 5:
                        $id = Setting::agentsetting_read("coupon")['communityid'];
                        break;//卡券社群
                    case 6:
                        $id = Setting::agentsetting_read("fightgroup")['communityid'];
                        break;//拼团社群
                    case 7:
                        $id = Setting::agentsetting_read("bargainset")['communityid'];
                        break;//砍价社群
                    case 8:break;//好店社群
                    case 9:
                        $id = Setting::agentsetting_read('citycard')['communityid'];
                        break;//名片社群
                    case 14:
                        $id = Setting::agentsetting_read('activity')['communityid'];
                        break;//名片社群
                }
                if($id > 0){
                    $communityParams = Commons::getCommunity($id);
                    if($communityParams){
                        $info = [
                            'params' => $communityParams ,
                            'style'       => [
                                'bgColor'      => '#FFFFFF' ,
                                'marginBottom' => 0 ,
                                'buttonbg'     => '#ff0000' ,
                                'buttonColor'  => '#FFFFFF' ,
                            ] ,
                            'data'        => [] ,
                            'id'          => 'community' ,
                            'currentTime' => 1565246255
                        ];
                    }
                }
                break;//社群组件一
            case 'options':
                //获取平台选项卡配置信息
                $set = Setting::agentsetting_read('pluginlist');
                if (is_array($set) && count($set) > 0) {
                    //商家 —— 选项卡信息设置
                    $data['D1234567890'] = [
                        'status'       => $set['sjstatus'] ? : 0,
                        'name'         => '商家' ,
                        'nickname'     => $set['sjname'] ? $set['sjname'] : '商家' ,
                        'type'         => 'store' ,
                        'sort'         => $set['sjsort'] ,
                        'imgurl'       => '' ,
                        'request_link' => 'p=store&do=homeList' ,
                        'order'        => $set['sjorder']
                    ];
                    //抢购 —— 选项卡信息设置
                    if (p('rush')) {
                        $data['D123456789' . count($data)] = [
                            'status'       => $set['qgstatus'] ? : 0,
                            'name'         => '抢购' ,
                            'nickname'     => $set['qgname'] ? $set['qgname'] : '抢购' ,
                            'type'         => 'rush' ,
                            'sort'         => $set['qgsort'] ,
                            'imgurl'       => '' ,
                            'request_link' => 'p=rush&do=homeList' ,
                            'order'        => $set['qgorder']
                        ];
                    }
                    //卡券 —— 选项卡信息设置
                    if (p('wlcoupon')) {
                        $data['D123456789' . count($data)] = [
                            'status'       => $set['kqstatus'] ? : 0,
                            'name'         => '卡券' ,
                            'nickname'     => $set['kqname'] ? $set['kqname'] : '卡券' ,
                            'type'         => 'coupon' ,
                            'sort'         => $set['kqsort'] ,
                            'imgurl'       => '' ,
                            'request_link' => 'p=wlcoupon&do=homeList' ,
                            'order'        => $set['kqorder']
                        ];
                    }
                    //特权 —— 选项卡信息设置
                    if (p('halfcard')) {
                        $data['D123456789' . count($data)] = [
                            'status'       => $set['wzstatus'] ? : 0,
                            'name'         => '特权' ,
                            'nickname'     => $set['wzname'] ? $set['wzname'] : '特权' ,
                            'type'         => 'halfcard' ,
                            'sort'         => $set['wzsort'] ,
                            'imgurl'       => '' ,
                            'request_link' => 'p=halfcard&do=homeList' ,
                            'order'        => $set['wzorder']
                        ];
                    }
                    //拼团 —— 选项卡信息设置
                    if (p('wlfightgroup')) {
                        $data['D123456789' . count($data)] = [
                            'status'       => $set['ptstatus'] ? : 0,
                            'name'         => '拼团' ,
                            'nickname'     => $set['ptname'] ? $set['ptname'] : '拼团' ,
                            'type'         => 'fight' ,
                            'sort'         => $set['ptsort'] ,
                            'imgurl'       => '' ,
                            'request_link' => 'p=wlfightgroup&do=homeList' ,
                            'order'        => $set['ptorder']
                        ];
                    }
                    //同城 —— 选项卡信息设置
                    if (p('pocket')) {
                        $data['D123456789' . count($data)] = [
                            'status'       => $set['tcstatus'] ? : 0,
                            'name'         => '同城' ,
                            'nickname'     => $set['tcname'] ? $set['tcname'] : '同城' ,
                            'type'         => 'pocket' ,
                            'sort'         => $set['tcsort'] ,
                            'imgurl'       => '' ,
                            'request_link' => 'p=pocket&do=infoList' ,
                            'order'        => $set['tcorder']
                        ];
                    }
                    //团购 —— 选项卡信息设置
                    if (p('groupon')) {
                        $data['D123456789' . count($data)] = [
                            'status'       => $set['tgstatus'] ? : 0,
                            'name'         => '团购' ,
                            'nickname'     => $set['tgname'] ? $set['tgname'] : '团购' ,
                            'type'         => 'groupon' ,
                            'sort'         => $set['tgsort'] ,
                            'imgurl'       => '' ,
                            'request_link' => 'p=groupon&do=homeList' ,
                            'order'        => $set['tgorder']
                        ];
                    }
                    //砍价 —— 选项卡信息设置
                    if (p('bargain')) {
                        $data['D123456789' . count($data)] = [
                            'status'       => $set['kjstatus'] ? : 0,
                            'name'         => '砍价' ,
                            'nickname'     => $set['kjname'] ? $set['kjname'] : '砍价' ,
                            'type'         => 'bargain' ,
                            'sort'         => $set['kjsort'] ,
                            'imgurl'       => '' ,
                            'request_link' => 'p=bargain&do=homeList' ,
                            'order'        => $set['kjorder']
                        ];
                    }
                    //积分 —— 选项卡信息设置
                    if (p('consumption')) {
                        $data['D123456789' . count($data)] = [
                            'status'       => $set['jfstatus'] ? : 0,
                            'name'         => '积分' ,
                            'nickname'     => $set['jfname'] ? $set['jfname'] : '积分' ,
                            'type'         => 'consumption' ,
                            'sort'         => $set['jfsort'] ,
                            'imgurl'       => '' ,
                            'request_link' => 'p=consumption&do=homeList' ,
                            'order'        => $set['jforder']
                        ];
                    }
                    //礼包 —— 选项卡信息设置
                    if (p('halfcard')) {
                        $data['D123456789' . count($data)] = [
                            'status'       => $set['gpstatus'] ? : 0,
                            'name'         => '礼包' ,
                            'nickname'     => $set['gpname'] ? $set['gpname'] : '礼包' ,
                            'type'         => 'package' ,
                            'sort'         => $set['gpsort'] ,
                            'imgurl'       => '' ,
                            'request_link' => 'p=halfcard&do=packageList' ,
                            'order'        => $set['gporder']
                        ];
                    }
                    //招聘 —— 选项卡信息设置
                    if (p('recruit')) {
                        $data['D123456789' . count($data)] = [
                            'status'       => $set['zpstatus'] ? : 0,
                            'name'         => '招聘' ,
                            'nickname'     => $set['zpname'] ? $set['zpname'] : '招聘' ,
                            'type'         => 'recruit' ,
                            'sort'         => $set['zpsort'] ,
                            'imgurl'       => '' ,
                            'request_link' => 'p=recruit&do=homeList' ,
                            'order'        => $set['zporder']
                        ];
                    }
                    //相亲 —— 选项卡信息设置
                    if (p('dating')) {
                        $data['D123456789' . count($data)] = [
                            'status'       => $set['xqstatus'] ? : 0,
                            'name'         => '相亲' ,
                            'nickname'     => $set['xqname'] ? $set['xqname'] : '相亲' ,
                            'type'         => 'dating' ,
                            'sort'         => $set['xqsort'] ,
                            'imgurl'       => '' ,
                            'request_link' => 'p=dating&do=homeList' ,
                            'order'        => $set['xqorder']
                        ];
                    }
                    //对数组进行重新排序
                    $option_sort = array_column($data, 'order');
                    array_multisort($option_sort, SORT_DESC, $data);
                    //循环判断是否显示
                    foreach($data as $key => $val){
                        if($val['status'] != 1) unset($data[$key]);
                    }
                    //配置info信息
                    $info = [
                        'group'       => 'options' ,
                        'params'      => [] ,
                        'style'       => [
                            'marginBottom' => 0 ,
                            'selectBg'     => '#ff2d2d' ,
                            'background'   => '#FFFFFF' ,
                            'defaultBg'    => '#000000'
                        ] ,
                        'data'        => $data,
                        'id'          => 'options' ,
                        'group_name'  => 'options' ,
                        'group_key'   => 0 ,
                        'currentTime' => 1565246255
                    ] ;
                }
                break;//选项卡组件一
            case 'magic_cube':
                //获取平台魔方配置信息
                $set = Dashboard::readSetting('cube');
                //删除不显示的图片
                foreach($set as $key => $val){
                    if($val['on'] == 0) unset($set[$key]);
                }
                $set = array_values($set);
                //根据数量生成对应的配置数据信息
                switch (count($set)){
                    case 1:
                        $info = [
                            'id'          => 'magic_cube' ,
                            'style'       => [
                                'width'   => 372 ,
                                'min_w'   => 93 ,
                                'padding' => 0 ,
                                'bgColor' => 'FFFFFF' ,
                            ] ,
                            'data'        => [
                                'D0123456789101' => [
                                    'width'  => 372 ,
                                    'height' => 186 ,
                                    'top'    => 0 ,
                                    'left'   => 0 ,
                                    'url'    => $set[0]['link'],
                                    'imgurl' => tomedia($set[0]['thumb']),
                                ] ,
                            ] ,
                            'currentTime' => 1565321290 ,
                            'height'      => 186 ,
                        ];
                        break;//一张图片
                    case 2:
                        $info = [
                            'id'          => 'magic_cube' ,
                            'style'       => [
                                'width'   => 372 ,
                                'min_w'   => 93 ,
                                'padding' => 0 ,
                                'bgColor' => 'FFFFFF' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'width'  => 186 ,
                                    'height' => 186 ,
                                    'top'    => 0 ,
                                    'left'   => 0 ,
                                    'url'    => $set[0]['link'],
                                    'imgurl' => tomedia($set[0]['thumb']),
                                ] ,
                                'C0123456789102' => [
                                    'width'  => 186 ,
                                    'height' => 186 ,
                                    'top'    => 0 ,
                                    'left'   => 186 ,
                                    'url'    => $set[1]['link'],
                                    'imgurl' => tomedia($set[1]['thumb']),
                                ]
                            ] ,
                            'currentTime' => 1565321290 ,
                            'height'      => 186 ,
                        ];
                        break;//二张图片
                    case 3:
                        $info = [
                            'id'          => 'magic_cube' ,
                            'style'       => [
                                'width'   => 372 ,
                                'min_w'   => 93 ,
                                'padding' => 0 ,
                                'bgColor' => 'FFFFFF' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'width'  => 186 ,
                                    'height' => 186 ,
                                    'top'    => 0 ,
                                    'left'   => 0 ,
                                    'url'    => $set[0]['link'],
                                    'imgurl' => tomedia($set[0]['thumb']),
                                ] ,
                                'C0123456789102' => [
                                    'width'  => 186 ,
                                    'height' => 93 ,
                                    'top'    => 0 ,
                                    'left'   => 186 ,
                                    'url'    => $set[1]['link'],
                                    'imgurl' => tomedia($set[1]['thumb']),
                                ] ,
                                'C0123456789103' => [
                                    'width'  => 186 ,
                                    'height' => 93 ,
                                    'top'    => 93 ,
                                    'left'   => 186 ,
                                    'url'    => $set[2]['link'],
                                    'imgurl' => tomedia($set[2]['thumb']),
                                ]
                            ] ,
                            'currentTime' => 1565321290 ,
                            'height'      => 186 ,
                        ];
                        break;//三张图片
                    case 4:
                        $info = [
                            'id'          => 'magic_cube' ,
                            'style'       => [
                                'width'   => 372 ,
                                'min_w'   => 93 ,
                                'padding' => 0 ,
                                'bgColor' => 'FFFFFF' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'width'  => 186 ,
                                    'height' => 186 ,
                                    'top'    => 0 ,
                                    'left'   => 0 ,
                                    'url'    => $set[0]['link'],
                                    'imgurl' => tomedia($set[0]['thumb']),
                                ] ,
                                'C0123456789102' => [
                                    'width'  => 186 ,
                                    'height' => 93 ,
                                    'top'    => 0 ,
                                    'left'   => 186 ,
                                    'url'    => $set[1]['link'],
                                    'imgurl' => tomedia($set[1]['thumb']),
                                ] ,
                                'C0123456789103' => [
                                    'width'  => 93 ,
                                    'height' => 93 ,
                                    'top'    => 93 ,
                                    'left'   => 186 ,
                                    'url'    => $set[2]['link'],
                                    'imgurl' => tomedia($set[2]['thumb']),
                                ] ,
                                'C0123456789104' => [
                                    'width'  => 93 ,
                                    'height' => 93 ,
                                    'top'    => 93 ,
                                    'left'   => 279 ,
                                    'url'    => $set[3]['link'],
                                    'imgurl' => tomedia($set[3]['thumb']),
                                ]
                            ] ,
                            'currentTime' => 1565321290 ,
                            'height'      => 186 ,
                        ];
                        break;//四张图片
                    case 5:
                        $info = [
                            'id'          => 'magic_cube' ,
                            'style'       => [
                                'width'   => 372 ,
                                'min_w'   => 93 ,
                                'padding' => 0 ,
                                'bgColor' => 'FFFFFF' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'width'  => 186 ,
                                    'height' => 186 ,
                                    'top'    => 0 ,
                                    'left'   => 0 ,
                                    'url'    => $set[0]['link'],
                                    'imgurl' => tomedia($set[0]['thumb']),
                                ] ,
                                'C0123456789102' => [
                                    'width'  => 93 ,
                                    'height' => 93 ,
                                    'top'    => 0 ,
                                    'left'   => 186 ,
                                    'url'    => $set[1]['link'],
                                    'imgurl' => tomedia($set[1]['thumb']),
                                ] ,
                                'C0123456789103' => [
                                    'width'  => 93 ,
                                    'height' => 93 ,
                                    'top'    => 0 ,
                                    'left'   => 279 ,
                                    'url'    => $set[2]['link'],
                                    'imgurl' => tomedia($set[2]['thumb']),
                                ] ,
                                'C0123456789104' => [
                                    'width'  => 93 ,
                                    'height' => 93 ,
                                    'top'    => 93 ,
                                    'left'   => 186 ,
                                    'url'    => $set[3]['link'],
                                    'imgurl' => tomedia($set[3]['thumb']),
                                ] ,
                                'C0123456789105' => [
                                    'width'  => 93 ,
                                    'height' => 93 ,
                                    'top'    => 93 ,
                                    'left'   => 279 ,
                                    'url'    => $set[4]['link'],
                                    'imgurl' => tomedia($set[4]['thumb']),
                                ]
                            ] ,
                            'currentTime' => 1565321290 ,
                            'height'      => 186 ,
                        ];
                        break;//五张图片
                    case 6:
                        $info = [
                            'id'          => 'magic_cube' ,
                            'style'       => [
                                'width'   => 372 ,
                                'min_w'   => 93 ,
                                'padding' => 0 ,
                                'bgColor' => 'FFFFFF' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'width'  => 186 ,
                                    'height' => 93 ,
                                    'top'    => 0 ,
                                    'left'   => 0 ,
                                    'url'    => $set[0]['link'],
                                    'imgurl' => tomedia($set[0]['thumb']),
                                ] ,
                                'C0123456789102' => [
                                    'width'  => 186 ,
                                    'height' => 93 ,
                                    'top'    => 0 ,
                                    'left'   => 186 ,
                                    'url'    => $set[1]['link'],
                                    'imgurl' => tomedia($set[1]['thumb']),
                                ] ,
                                'C0123456789103' => [
                                    'width'  => 186 ,
                                    'height' => 93 ,
                                    'top'    => 93 ,
                                    'left'   => 0 ,
                                    'url'    => $set[2]['link'],
                                    'imgurl' => tomedia($set[2]['thumb']),
                                ] ,
                                'C0123456789104' => [
                                    'width'  => 186 ,
                                    'height' => 93 ,
                                    'top'    => 93 ,
                                    'left'   => 186 ,
                                    'url'    => $set[3]['link'],
                                    'imgurl' => tomedia($set[3]['thumb']),
                                ] ,
                                'C0123456789105' => [
                                    'width'  => 186 ,
                                    'height' => 93 ,
                                    'top'    => 186 ,
                                    'left'   => 0 ,
                                    'url'    => $set[4]['link'],
                                    'imgurl' => tomedia($set[4]['thumb']),
                                ] ,
                                'C0123456789106' => [
                                    'width'  => 186 ,
                                    'height' => 93 ,
                                    'top'    => 186 ,
                                    'left'   => 186 ,
                                    'url'    => $set[5]['link'],
                                    'imgurl' => tomedia($set[5]['thumb']),
                                ]
                            ] ,
                            'currentTime' => 1565321290 ,
                            'height'      => 279 ,
                        ];
                        break;//六张图片
                }
                break;//魔方组件一
            case 'options2':
                //根据页面类型获取选项卡配置信息
                switch ($params['type']){
                    case 3:
                        $info = [
                            'params'      => [
                                'goods_type' => 'rush' ,
                                'total'      => 4 ,
                            ] ,
                            'style'       => [
                                'marginBottom' => 0 ,
                                'selectBg'     => '#ff2d2d' ,
                                'background'   => '#FFFFFF' ,
                                'defaultBg'    => '#000000' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'name'         => '精选' ,//
                                    'sort'         => 1 ,//当前按钮顺序
                                    'status'       => 2 ,//商品状态
                                    'imgurl'       => '' ,//
                                    'orders'       => 6,//排序方式
                                    'request_link' => 'p=rush&do=homeList' ,
                                ] ,
                                'C0123456789102' => [
                                    'name'         => '热门' ,
                                    'sort'         => 2 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 7,
                                    'request_link' => 'p=rush&do=homeList' ,
                                ] ,
                                'C0123456789103' => [
                                    'name'         => '附近' ,//
                                    'sort'         => 3 ,//当前按钮顺序
                                    'status'       => 2 ,//商品状态
                                    'imgurl'       => '' ,//
                                    'orders'       => 2,//排序方式
                                    'request_link' => 'p=rush&do=homeList' ,
                                ] ,
                                'C0123456789104' => [
                                    'name'         => '预告' ,
                                    'sort'         => 4 ,
                                    'status'       => 1 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 8,
                                    'request_link' => 'p=rush&do=homeList' ,
                                ]
                            ] ,
                            'group_name'  => 'options' ,
                            'group_key'   => 1 ,
                            'id'          => 'options2' ,
                            'currentTime' => 1565338324 ,
                        ];
                        break;//抢购
                    case 4:
                        $info = [
                            'params'      => [
                                'goods_type' => 'groupon' ,
                                'total'      => 4 ,
                            ] ,
                            'style'       => [
                                'marginBottom' => 0 ,
                                'selectBg'     => '#ff2d2d' ,
                                'background'   => '#FFFFFF' ,
                                'defaultBg'    => '#000000' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'name'         => '附近' ,
                                    'sort'         => 1 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 2,
                                    'request_link' => 'p=groupon&do=homeList' ,
                                ] ,
                                'C0123456789102' => [
                                    'name'         => '精选' ,
                                    'sort'         => 2 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 6,
                                    'request_link' => 'p=groupon&do=homeList' ,
                                ] ,
                                'C0123456789103' => [
                                    'name'         => '最热' ,
                                    'sort'         => 3 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 7,
                                    'request_link' => 'p=groupon&do=homeList' ,
                                ] ,
                                'C0123456789104' => [
                                    'name'         => '最新' ,
                                    'sort'         => 4 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 1,
                                    'request_link' => 'p=groupon&do=homeList' ,
                                ]
                            ] ,
                            'group_name'  => 'options' ,
                            'group_key'   => 1 ,
                            'id'          => 'options2' ,
                            'currentTime' => 1565338324 ,
                        ];
                        break;//团购
                    case 5:
                        $info = [
                            'params'      => [
                                'goods_type' => 'coupon' ,
                                'total'      => 4 ,
                            ] ,
                            'style'       => [
                                'marginBottom' => 0 ,
                                'selectBg'     => '#ff2d2d' ,
                                'background'   => '#FFFFFF' ,
                                'defaultBg'    => '#000000' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'name'         => '附近',
                                    'sort'         => 1 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 2,
                                    'request_link' => 'p=wlcoupon&do=homeList' ,
                                ] ,
                                'C0123456789102' => [
                                    'name'         => '精选' ,
                                    'sort'         => 2 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 6,
                                    'request_link' => 'p=wlcoupon&do=homeList' ,
                                ] ,
                                'C0123456789103' => [
                                    'name'         => '最热' ,
                                    'sort'         => 3 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 7,
                                    'request_link' => 'p=wlcoupon&do=homeList' ,
                                ] ,
                                'C0123456789104' => [
                                    'name'         => '免费' ,
                                    'sort'         => 4 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 8,
                                    'request_link' => 'p=wlcoupon&do=homeList' ,
                                ]
                            ] ,
                            'group_name'  => 'options' ,
                            'group_key'   => 1 ,
                            'id'          => 'options2' ,
                            'currentTime' => 1565338324 ,
                        ];
                        break;//卡券
                    case 6:
                        $info = [
                            'params'      => [
                                'goods_type' => 'wlfightgroup' ,
                                'total'      => 4 ,
                            ] ,
                            'style'       => [
                                'marginBottom' => 0 ,
                                'selectBg'     => '#ff2d2d' ,
                                'background'   => '#FFFFFF' ,
                                'defaultBg'    => '#000000' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'name'         => '附近' ,
                                    'sort'         => 1 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 2,
                                    'request_link' => 'p=wlfightgroup&do=homeList' ,
                                ] ,
                                'C0123456789102' => [
                                    'name'         => '精选' ,
                                    'sort'         => 2 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 6,
                                    'request_link' => 'p=wlfightgroup&do=homeList' ,
                                ] ,
                                'C0123456789103' => [
                                    'name'         => '最热' ,
                                    'sort'         => 3 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 7,
                                    'request_link' => 'p=wlfightgroup&do=homeList' ,
                                ] ,
                                'C0123456789104' => [
                                    'name'         => '最新' ,
                                    'sort'         => 4 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 1,
                                    'request_link' => 'p=wlfightgroup&do=homeList' ,
                                ]
                            ] ,
                            'group_name'  => 'options' ,
                            'group_key'   => 1 ,
                            'id'          => 'options2' ,
                            'currentTime' => 1565338324 ,
                        ];
                        break;//拼团
                    case 7:
                        $info = [
                            'params'      => [
                                'goods_type' => 'bargain' ,
                                'total'      => 4 ,
                            ] ,
                            'style'       => [
                                'marginBottom' => 0 ,
                                'selectBg'     => '#ff2d2d' ,
                                'background'   => '#FFFFFF' ,
                                'defaultBg'    => '#000000' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'name'         => '附近' ,
                                    'sort'         => 1 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 2,
                                    'request_link' => 'p=bargain&do=homeList' ,
                                ] ,
                                'C0123456789102' => [
                                    'name'         => '精选' ,
                                    'sort'         => 2 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '',
                                    'orders'       => 6 ,
                                    'request_link' => 'p=bargain&do=homeList' ,
                                ] ,
                                'C0123456789103' => [
                                    'name'         => '最热' ,
                                    'sort'         => 3 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '',
                                    'orders'       => 7 ,
                                    'request_link' => 'p=bargain&do=homeList' ,
                                ] ,
                                'C0123456789104' => [
                                    'name'         => '最新' ,
                                    'sort'         => 4 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '',
                                    'orders'       => 1 ,
                                    'request_link' => 'p=bargain&do=homeList' ,
                                ]
                            ] ,
                            'group_name'  => 'options' ,
                            'group_key'   => 1 ,
                            'id'          => 'options2' ,
                            'currentTime' => 1565338324
                        ];
                        break;//砍价
                    case 14:
                        $info = [
                            'params'      => [
                                'goods_type' => 'activity' ,
                                'total'      => 4 ,
                            ] ,
                            'style'       => [
                                'marginBottom' => 0 ,
                                'selectBg'     => '#ff2d2d' ,
                                'background'   => '#FFFFFF' ,
                                'defaultBg'    => '#000000' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'name'         => '推荐' ,
                                    'sort'         => 1 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 3,
                                    'request_link' => 'p=activity&do=homeList' ,
                                ] ,
                                'C0123456789102' => [
                                    'name'         => '附近' ,
                                    'sort'         => 2 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '',
                                    'orders'       => 2 ,
                                    'request_link' => 'p=activity&do=homeList' ,
                                ] ,
                                'C0123456789103' => [
                                    'name'         => '热门' ,
                                    'sort'         => 3 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '',
                                    'orders'       => 4 ,
                                    'request_link' => 'p=activity&do=homeList' ,
                                ] ,
                                'C0123456789104' => [
                                    'name'         => '最新' ,
                                    'sort'         => 4 ,
                                    'status'       => 2 ,
                                    'imgurl'       => '',
                                    'orders'       => 1 ,
                                    'request_link' => 'p=activity&do=homeList' ,
                                ]
                            ] ,
                            'group_name'  => 'options' ,
                            'group_key'   => 1 ,
                            'id'          => 'options2' ,
                            'currentTime' => 1565338324
                        ];
                        break;//活动
                    case 15:
                        $info = [
                            'params'      => [
                                'goods_type' => 'recruit' ,
                                'total'      => 3 ,
                            ] ,
                            'style'       => [
                                'marginBottom' => 0 ,
                                'selectBg'     => '#3388ff' ,
                                'background'   => '#FFFFFF' ,
                                'defaultBg'    => '#000000' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'name'         => '最新' ,
                                    'sort'         => 1 ,
                                    'status'       => 4 ,
                                    'imgurl'       => '' ,
                                    'orders'       => 3,
                                    'request_link' => 'p=recruit&do=homeList' ,
                                ] ,
                                'C0123456789102' => [
                                    'name'         => '热门' ,
                                    'sort'         => 2 ,
                                    'status'       => 4 ,
                                    'imgurl'       => '',
                                    'orders'       => 2 ,
                                    'request_link' => 'p=recruit&do=homeList' ,
                                ] ,
                                'C0123456789103' => [
                                    'name'         => '推荐' ,
                                    'sort'         => 3 ,
                                    'status'       => 4 ,
                                    'imgurl'       => '',
                                    'orders'       => 1 ,
                                    'request_link' => 'p=recruit&do=homeList' ,
                                ] ,
                            ] ,
                            'group_name'  => 'options' ,
                            'group_key'   => 1 ,
                            'id'          => 'options2' ,
                            'currentTime' => time()
                        ];
                        break;//招聘
                    case 16:
                        $info = [
                            'params'      => [
                                'goods_type' => 'dating' ,
                                'total'      => 4 ,
                            ] ,
                            'style'       => [
                                'marginBottom' => 0 ,
                                'selectBg'     => '#3388ff' ,
                                'background'   => '#FFFFFF' ,
                                'defaultBg'    => '#000000' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'name'         => '新人' ,
                                    'sort'         => 1 ,
                                    'status'       => 1 ,
                                    'imgurl'       => '' ,
                                    'gneder'       => '1,2',
                                    'orders'       => 3,
                                    'request_link' => 'p=dating&do=homeList' ,
                                ] ,
                                'C0123456789102' => [
                                    'name'         => '找男友' ,
                                    'sort'         => 2 ,
                                    'status'       => 1 ,
                                    'imgurl'       => '',
                                    'gneder'       => 1,
                                    'orders'       => 1 ,
                                    'request_link' => 'p=dating&do=homeList' ,
                                ] ,
                                'C0123456789103' => [
                                    'name'         => '找女友' ,
                                    'sort'         => 3 ,
                                    'status'       => 1 ,
                                    'imgurl'       => '',
                                    'gneder'       => 2,
                                    'orders'       => 1 ,
                                    'request_link' => 'p=dating&do=homeList' ,
                                ] ,
                                'C0123456789104' => [
                                    'name'         => '附近' ,
                                    'sort'         => 4 ,
                                    'status'       => 1 ,
                                    'imgurl'       => '',
                                    'gneder'       => '1,2',
                                    'orders'       => 4 ,
                                    'request_link' => 'p=dating&do=homeList' ,
                                ] ,
                            ] ,
                            'group_name'  => 'options' ,
                            'group_key'   => 1 ,
                            'id'          => 'options2' ,
                            'currentTime' => time()
                        ];
                        break;//相亲交友
                    case 18:
                        $info = [
                            'params'      => [
                                'goods_type'       => 'housekeep',
                                'total'            => 2,
                                'class_list'       => [],
                                'class_img_switch' => 0,
                            ] ,
                            'style'       => [
                                'marginBottom' => 0 ,
                                'selectBg'     => '#ff2d2d' ,
                                'background'   => '#FFFFFF' ,
                                'defaultBg'    => '#000000' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'name'         => '服务商家' ,
                                    'sort'         => 1 ,
                                    'status'       => 1 ,
                                    'service_type' => 1 ,
                                    'orders'       => 1,
                                    'imgurl'       => '' ,
                                    'gneder'       => 1,
                                    'request_link' => 'p=housekeep&do=getNewStorelist' ,
                                ] ,
                                'C0123456789102' => [
                                    'name'         => '客户需求' ,
                                    'sort'         => 2 ,
                                    'status'       => 2 ,
                                    'service_type' => 3 ,
                                    'orders'       => 1 ,
                                    'imgurl'       => '',
                                    'gneder'       => 1,
                                    'request_link' => 'p=housekeep&do=getDemandlist' ,
                                ] ,
                            ] ,
                            'group_name'  => 'options' ,
                            'group_key'   => 1 ,
                            'id'          => 'options2' ,
                            'currentTime' => time()
                        ];
                        break;//家政服务
                    case 20:
                        $info = [
                            'params'      => [
                                'goods_type'       => 'house',
                                'total'            => 2,
                                'class_list'       => [],
                                'class_img_switch' => 0,
                            ] ,
                            'style'       => [
                                'marginBottom' => 0 ,
                                'selectBg'     => '#ff2d2d' ,
                                'background'   => '#FFFFFF' ,
                                'defaultBg'    => '#000000' ,
                            ] ,
                            'data'        => [
                                'C0123456789101' => [
                                    'name'         => '新房' ,
                                    'sort'         => 1 ,
                                    'status'       => 1 ,
                                    'house_type'   => 1 ,
                                    'orders'       => 1,
                                    'imgurl'       => '' ,
                                    'gneder'       => 1,
                                    'request_link' => 'p=house&do=newHouseList' ,
                                ] ,
                                'C0123456789102' => [
                                    'name'         => '二手房' ,
                                    'sort'         => 2 ,
                                    'status'       => 2 ,
                                    'house_type'   => 2 ,
                                    'orders'       => 1 ,
                                    'imgurl'       => '',
                                    'gneder'       => 1,
                                    'request_link' => 'p=house&do=oldHouseList' ,
                                ] ,
                                'C0123456789103' => [
                                    'name'         => '租房' ,
                                    'sort'         => 3 ,
                                    'status'       => 2 ,
                                    'house_type'   => 3 ,
                                    'orders'       => 1 ,
                                    'imgurl'       => '',
                                    'gneder'       => 1,
                                    'request_link' => 'p=house&do=rentingList' ,
                                ] ,
                            ] ,
                            'group_name'  => 'options' ,
                            'group_key'   => 1 ,
                            'id'          => 'options2' ,
                            'currentTime' => time()
                        ];
                        break;//房产
                }
                //分类信息获取
                if($params['type'] != 15 && $params['type'] != 16) {
                    $info['params'] = Diy::getGoodsCateInfo($info['params'],$info['params']['goods_type']);
                }
                break;//选项卡组件二
            case 'coupon':
                //获取卡券列表
                $params = [
                    'type'     => 2 ,
                    'orders'   => 5 ,
                    'classs'   => 0 ,
                    'show_num' => 10 ,
                ];
                $list = Diy::getDiyGoods($params, 5, 0);
                if(is_array($list) && count($list) > 0){
                    //循环获取卡券详细信息
                    foreach ($list as $goods_key => &$goods_val) {
                        //获取最新的商品信息
                        $goods_val = WeliamWeChat::getHomeGoods(5, $goods_val['id']);
                        $goods_val['url'] = h5_url('pages/subPages/goods/index',['type'=>5,'id'=>$goods_val['id']]);
                        //添加店铺链接
                        $goods_val['shop_url'] = h5_url('pages/mainPages/store/index',['sid'=>$goods_val['id']]);
                        $goods_val['distance'] = Store::shopLocation($goods_val['sid'], 0, 0);
                    }
                    //拼装数据
                    $info = [
                        'group'       => 'coupon_goods' ,
                        'params'      => $params ,
                        'style'       => [
                            'marginBottom' => 0
                        ] ,
                        'data'        => $list ,
                        'id'          => 'coupon_goods' ,
                        'group_name'  => 'coupon_goods' ,
                        'group_key'   => 0 ,
                        'currentTime' => 1565573142
                    ];
                }
                break;//卡券组件一
            case 'class_menu':
                switch ($params['type']){
                    case 8:
                        $list = pdo_getall(PDO_NAME."category_store"
                            ,['aid'=>$_W['aid'],'uniacid'=>$_W['uniacid'],'enabled'=>1,'parentid'=>0]
                            ,['id','thumb','name','abroad','state'],'','displayorder DESC');
                        if(is_array($list) && count($list) > 0){
                            foreach ($list as $key => $val){
                                $data['D123456789' . $key]['imgurl']  = tomedia($val['thumb']);
                                if($val['state'] == 1){
                                    $data['D123456789' . $key]['linkurl'] = $val['abroad'];
                                } else{
                                    if(Customized::init('storecate1520') > 0){
                                        $data['D123456789' . $key]['linkurl'] = h5_url('pages/subPages2/storeClass/storeClass',['id'=>$val['id']]);
                                    }else{
                                        $data['D123456789' . $key]['linkurl'] = h5_url('pages/mainPages/store/list',['cate_one'=>$val['id']]);
                                    }
                                }
                                $data['D123456789' . $key]['text']    = $val['name'];
                                $data['D123456789' . $key]['color']   = '#000000';
                            }
                            $info = [
                                'params'      => [] ,
                                'style'       => [
                                    'navstyle'     => 'circle' ,
                                    'background'   => '#FFFFFF' ,
                                    'rownum'       => 5 ,//每行数量
                                    'showtype'     => 1 ,//显示方式：0=单页显示；1=多页滑动显示
                                    'pagenum'      => 10 ,//每页数量
                                    'marginbottom' => 0 ,
                                ] ,
                                'data'        => $data,
                                'id'          => 'menu' ,
                                'currentTime' => 1565246255
                            ];
                        }
                        break;//好店首页
                }




                break;//分类菜单(使用按钮组件一)
            case 'shop_join':
                //获取店铺入驻列表（最近的10条入驻信息）
                $list = pdo_fetchall("SELECT id,storename,FROM_UNIXTIME(createtime,'%Y-%m-%d') as createtime FROM "
                                     . tablename(PDO_NAME."merchantdata")
                                     . " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 2 AND enabled = 1 ORDER BY createtime DESC LIMIT 10 ");
                $info = [
                    'group'       => 'shop_join' ,
                    'params'      => [
                        'title'  => '商户入驻' ,
                        'button' => '我要入驻' ,
                    ] ,
                    'style'       => [
                        'marginBottom' => 0
                    ] ,
                    'data'        => $list ,
                    'id'          => 'shop_join' ,
                    'group_name'  => 'shop_join' ,
                    'group_key'   => 0 ,
                    'currentTime' => time() ,
                ];
                break;//商户入驻
            case 'options3':
                $info = [
                    'params'      => [] ,
                    'style'       => [
                        'marginBottom' => 0
                    ] ,
                    'data'        => [
                        'C0123456789102' => [
                            'title'  => '附近' ,
                            'order'  => 2 ,
                            'imgurl' => '' ,
                        ] ,
                        'C0123456789103' => [
                            'title'  => '推荐' ,
                            'order'  => 3 ,
                            'imgurl' => '' ,
                        ] ,
                        'C0123456789104' => [
                            'title'  => '人气' ,
                            'order'  => 4 ,
                            'imgurl' => '' ,
                        ] ,
                        'C0123456789101' => [
                            'title'  => '最新' ,
                            'order'  => 1 ,
                            'imgurl' => '' ,
                        ] ,
                    ] ,
                    'group_name'  => 'options' ,
                    'group_key'   => 2 ,
                    'id'          => 'options3' ,
                    'currentTime' => time() ,
                ];
                break;//选项卡组件三
            case 'statistics':
                $info = [
                    'params'      => [
                        'enterprise' => [
                            'title'           => '入驻企业',
                            'numberColor'     => '#000000',
                            'titleColor'      => '#CCCCCC',
                            'backgroundColor' => '#FFFFFF',
                            'icon'            => tomedia('../addons/'.MODULE_NAME.'/web/resource/diy/images/enterprise.png'),
                            'statistics'      => pdo_count(PDO_NAME."merchantdata",[
                                'uniacid'        => $_W['uniacid'],
                                'aid'            => $_W['aid'],
                                'recruit_switch' => 1
                            ]) ? : 0,//入驻企业
                        ],
                        'recruit'    => [
                            'title'           => '职业',
                            'numberColor'     => '#000000',
                            'titleColor'      => '#CCCCCC',
                            'backgroundColor' => '#FFFFFF',
                            'icon'            => tomedia('../addons/'.MODULE_NAME.'/web/resource/diy/images/recruit.png'),
                            'statistics'      => pdo_count(PDO_NAME."recruit_position",[
                                'uniacid' => $_W['uniacid'],
                                'aid'     => $_W['aid']
                            ]) ? : 0,//职业
                        ],
                        'resume'     => [
                            'title'           => '简历',
                            'numberColor'     => '#000000',
                            'titleColor'      => '#CCCCCC',
                            'backgroundColor' => '#FFFFFF',
                            'icon'            => tomedia('../addons/'.MODULE_NAME.'/web/resource/diy/images/resume.png'),
                            'statistics'      => pdo_count(PDO_NAME."recruit_resume",[
                                'uniacid' => $_W['uniacid'],
                                'aid'     => $_W['aid']
                            ]) ? : 0,//简历
                        ],
                        'pv'         => [
                            'title'           => '访问',
                            'numberColor'     => '#000000',
                            'titleColor'      => '#CCCCCC',
                            'backgroundColor' => '#FFFFFF',
                            'icon'            => tomedia('../addons/'.MODULE_NAME.'/web/resource/diy/images/pv.png'),
                            'statistics'      => pdo_fetchcolumn("SELECT SUM(pv) FROM".tablename(PDO_NAME."recruit_recruit")." WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ") ? : 0,//访问
                        ],
                    ],
                    'style'       => [],
                    'data'        => [],
                    'id'          => 'recruit_statistics',
                    'currentTime' => time(),
                ];
                //获取虚拟数据
                $set = Setting::agentsetting_read('recruit_set');
                $info['params']['pv']['statistics'] = intval($info['params']['pv']['statistics']) + intval($set['look']);

                break;//统计信息
            case 'enterprise':
                $info = [
                    'params'      => [
                        'title'       => '企业推荐' ,
                        'show_more'   => 1 ,
                        'type'        => 2 ,
                        'orders'      => 3 ,
                        'industry_id' => 0 ,
                        'show_num'    => 10 ,
                    ] ,
                    'style'       => [
                        'bgColor'      => '#FFFFFF' ,
                        'padding'      => 10 ,
                        'marginBottom' => 0 ,
                    ] ,
                    'data'        => [] ,
                    'group_name'  => 'recruit_enterprise' ,
                    'group_key'   => 1 ,
                    'id'          => 'recruit_enterprise2' ,
                    'currentTime' => time() ,
                ];
                //条件生成
                $where = ' AND status = 2 AND enabled = 1 ';
                $order = " ORDER BY listorder DESC,id DESC ";
                //查询数量
                $limit = " LIMIT 0,{$info['params']['show_num']} ";
                $field = "id,logo,storename,recruit_nature_id,recruit_scale_id,recruit_industry_id,provinceid,areaid,distid";
                [$nterprise,$total] = Recruit::getEnterpriseList($where,$field,$order,$limit);
                foreach($nterprise as $nterpriseIndex => &$nterpriseItem){
                    unset($nterpriseItem['distances'],$nterpriseItem['recruit_nature_id'],$nterpriseItem['recruit_scale_id'],$nterpriseItem['recruit_industry_id'],$nterpriseItem['provinceid'],$nterpriseItem['areaid'],$nterpriseItem['distid']);
                }
                //信息赋值
                $info['data'] = $nterprise ? : [];

                break;//企业推荐
            case 'dating_statistics':
                $info = [
                    'params'      => [
                        'user' => [
                            'title'           => '会员数',
                            'numberColor'     => '#000000',
                            'titleColor'      => '#9E9E9E',
                            'backgroundColor' => '#F8F8F8',
                            'icon'            => tomedia('../addons/'.MODULE_NAME.'/web/resource/diy/images/dating_user.png'),
                            'statistics'      =>  pdo_count(PDO_NAME."dating_member",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'examine'=>3]) ? : 0,//会员数量
                        ],
                        'pv'         => [
                            'title'           => '访问量',
                            'numberColor'     => '#000000',
                            'titleColor'      => '#9E9E9E',
                            'backgroundColor' => '#F8F8F8',
                            'icon'            => tomedia('../addons/'.MODULE_NAME.'/web/resource/diy/images/dating_pv.png'),
                            'statistics'      => pdo_fetchcolumn("SELECT SUM(pv) FROM".tablename(PDO_NAME."dating_member")." WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ") ? : 0,//访问
                        ],
                    ],
                    'style'       => [],
                    'data'        => [],
                    'id'          => 'dating_statistics',
                    'currentTime' => time(),
                ];
                //获取虚拟数据
                $set = Setting::wlsetting_read('dating_set');
                $info['params']['pv']['statistics']  = intval($info['params']['pv']['statistics']) + intval($set['fictitious_pv']);
                break;//相亲交友统计信息
            case 'housekeep_service':
                //基本条件
                $shopWhere = " AND enabled = 1 AND housekeepstatus = 1  ";
                $artificerWhere = $demandWhere = $serviceWhere = " AND status = 1 ";
                //请求获取列表
                $houseKeep = Housekeep::getList(2,1,5,$shopWhere,$artificerWhere,$demandWhere,$serviceWhere);
                $info = [
                    'group'  => 'house_keep',
                    'params' => [
                        'title'        => '推荐服务者',
                        'show_more'    => 1,
                        'show_title'   => 1,
                        'service_type' => 2,
                        'type'         => 2,
                        'show_num'     => 5,
                        'orders'       => 1,
                    ],
                    'style'  => [
                        'bgColor'      => '#FFFFFF',
                        'padding'      => 10,
                        'marginBottom' => 0,
                    ],
                    'data'   => is_array($houseKeep['list']) && count($houseKeep['list']) ? $houseKeep['list'] : [],
                    'id'          => 'house_keep',
                    'group_name'  => 'house_keep',
                    'group_key'   => 0,
                    'currentTime' => time(),
                ];
                if(Customized::init('citycard1503') > 0 ){
                    $info['hidepersontag'] = 1;
                }else{
                    $info['hidepersontag'] = 0;
                }
                break;//家政服务 - 个人服务者
        }
        //组件为(轮播图||图片展播)时  移动端需要它图片的最高高度|| $info['id'] == 'pictures')
        if (($info['group_name'] == 'banner' || $info['id'] == 'pictures')) {
            $keyName = key($info['data']);
            $imgInfo = Tools::createImage($info['data'][$keyName]['imgurl']);
            $info['height'] = imagesy($imgInfo);
            $info['width'] = imagesx($imgInfo);
        }

        return $info ? $info : [];
    }
    /**
     * Comment: 获取首页默认配置信息
     * Author: zzw
     * Date: 2019/8/8 15:36
     * @return array
     */
    public static function getHomePageDefaultInfo(){
        global $_W;
        $page = pdo_get(PDO_NAME.'diypage',array('uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'name'=>'weliam_default_index'),['data']);
        if (!empty($page)){
            $data = json_decode(base64_decode($page['data']), true);
        }else{
            $data = [
                'type' => 2,
                'page' => [
                    'title'       => '',
                    'background'  => '#FFFFFF' ,
                    'share_title' => '' ,
                    'share_image' => '' ,
                ] ,
                'menu' => -1 ,
                'adv'  => '' ,
                'items' => [
                    'D1234567890' => self::defaultInfo('search2') ,
                    'D1234567891' => self::defaultInfo('banner',['type'=>2]) ,
                    'D1234567892' => self::defaultInfo('notice') ,
                    'D1234567893' => self::defaultInfo('menu') ,
                    'D1234567894' => self::defaultInfo('picturew2') ,
                    'D1234567895' => self::defaultInfo('community',['type'=>2]) ,
                    'D1234567896' => self::defaultInfo('magic_cube') ,
                    'D1234567897' => self::defaultInfo('options') ,
                ] ,
            ];
            $save_data = base64_encode(json_encode($data));
            pdo_insert(PDO_NAME . 'diypage', ['uniacid' => $_W['uniacid'],'aid'=>$_W['aid'],'name'=>'weliam_default_index','type'=>2,'data'=>$save_data,'page_class'=>1]);
        }
        return ['data'=>$data];
    }
    /**
     * Comment: 获取抢购首页默认配置信息
     * Author: zzw
     * Date: 2019/8/9 14:46
     * @return array
     */
    public static function getRushPageDefaultInfo(){
        $data = [
            'type' => 3,
            'page' => [
                'title'       => '抢购首页' ,
                'background'  => '#FFFFFF' ,
                'share_title' => '' ,
                'share_image' => '' ,
            ] ,
            'menu' => -1 ,
            'adv'  => '' ,
            'items' => [
                'D1234567890' => self::defaultInfo('search2') ,
                'D1234567891' => self::defaultInfo('banner',['type'=>3]) ,
                'D1234567892' => self::defaultInfo('community',['type'=>3]) ,
                'D1234567893' => self::defaultInfo('options2',['type'=>3]) ,
            ] ,
        ];
        return ['data'=>$data];
    }
    /**
     * Comment: 获取团购首页默认配置信息
     * Author: zzw
     * Date: 2019/8/9 15:06
     * @return array
     */
    public static function getGroupPageDefaultInfo(){
        $data = [
            'type' => 4,
            'page' => [
                'title'       => '团购首页' ,
                'background'  => '#FFFFFF' ,
                'share_title' => '' ,
                'share_image' => '' ,
            ] ,
            'menu' => -1 ,
            'adv'  => '' ,
            'items' => [
                'D1234567890' => self::defaultInfo('search2') ,
                'D1234567891' => self::defaultInfo('banner',['type'=>4]) ,
                'D1234567892' => self::defaultInfo('community',['type'=>4]) ,
                'D1234567893' => self::defaultInfo('options2',['type'=>4]) ,
            ] ,
        ];
        return ['data'=>$data];
    }
    /**
     * Comment: 获取卡券首页默认配置信息
     * Author: zzw
     * Date: 2019/8/9 15:25
     * @return array
     */
    public static function getCouponPageDefaultInfo(){
        $data = [
            'type' => 5,
            'page' => [
                'title'       => '卡券首页' ,
                'background'  => '#FFFFFF' ,
                'share_title' => '' ,
                'share_image' => '' ,
            ] ,
            'menu' => -1 ,
            'adv'  => '' ,
            'items' => [
                'D1234567890' => self::defaultInfo('search2') ,
                'D1234567891' => self::defaultInfo('banner',['type'=>5]) ,
                'D1234567892' => self::defaultInfo('community',['type'=>5]) ,
                'D1234567893' => self::defaultInfo('options2',['type'=>5]) ,
            ] ,
        ];
        return ['data'=>$data];
    }
    /**
     * Comment: 获取拼团首页默认配置信息
     * Author: zzw
     * Date: 2019/8/9 15:30
     * @return array
     */
    public static function getFightPageDefaultInfo(){
        $data = [
            'type' => 6,
            'page' => [
                'title'       => '拼团首页' ,
                'background'  => '#FFFFFF' ,
                'share_title' => '' ,
                'share_image' => '' ,
            ] ,
            'menu' => -1 ,
            'adv'  => '' ,
            'items' => [
                'D1234567890' => self::defaultInfo('search2') ,
                'D1234567891' => self::defaultInfo('banner',['type'=>6]) ,
                'D1234567892' => self::defaultInfo('community',['type'=>6]) ,
                'D1234567893' => self::defaultInfo('options2',['type'=>6]) ,
            ] ,
        ];

        return ['data'=>$data];
    }
    /**
     * Comment: 获取砍价首页默认配置信息
     * Author: zzw
     * Date: 2019/8/9 15:32
     * @return array
     */
    public static function getBargainPageDefaultInfo(){
        $data = [
            'type' => 7,
            'page' => [
                'title'       => '砍价首页' ,
                'background'  => '#FFFFFF' ,
                'share_title' => '' ,
                'share_image' => '' ,
            ] ,
            'menu' => -1 ,
            'adv'  => '' ,
            'items' => [
                'D1234567890' => self::defaultInfo('search2') ,
                'D1234567891' => self::defaultInfo('banner',['type'=>7]) ,
                'D1234567892' => self::defaultInfo('community',['type'=>7]) ,
                'D1234567893' => self::defaultInfo('options2',['type'=>7]) ,
            ] ,
        ];

        return ['data'=>$data];
    }
    /**
     * Comment: 获取好店首页默认配置信息
     * Author: zzw
     * Date: 2019/8/21 10:54
     * @return array
     */
    public static function getShopPageDefaultInfo(){
        $data = [
            'type' => 8,
            'page' => [
                'title'       => '好店首页' ,
                'background'  => '#FFFFFF' ,
                'share_title' => '' ,
                'share_image' => '' ,
            ] ,
            'menu' => -1 ,
            'adv'  => '' ,
            'items' => [
                'D1234567890' => self::defaultInfo('search2') ,
                'D1234567891' => self::defaultInfo('banner',['type'=>8]) ,
                'D1234567892' => self::defaultInfo('class_menu',['type'=>8]) ,
                'D1234567893' => self::defaultInfo('shop_join') ,
                'D1234567894' => self::defaultInfo('options3') ,
            ] ,
        ];

        return ['data'=>$data];
    }
    /**
     * Comment: 获取名片首页默认配置信息
     * Author: zzw
     * Date: 2019/12/17 16:39
     * @return array
     */
    public static function getCardPageDefaultInfo(){
        global $_W;
        $data = [
            'type' => 8,
            'page' => [
                'title'       => '名片首页' ,
                'background'  => '#FFFFFF' ,
                'share_title' => '' ,
                'share_image' => '' ,
            ] ,
            'menu' => -1 ,
            'adv'  => '' ,
            'items' => [
                'D1234567890' => self::defaultInfo('search2',['type' => 9]) ,
                'D1234567891' => self::defaultInfo('banner',['type'=>9]) ,
                'D1234567895' => self::defaultInfo('community',['type'=>9]) ,
            ] ,
        ];

        return ['data'=>$data];
    }
    /**
     * Comment: 获取活动首页默认配置信息
     * Author: zzw
     * Date: 2020/11/10 11:55
     * @return array
     */
    public static function getActivityPageDefaultInfo(){
        $data = [
            'type' => 14,
            'page' => [
                'title'       => '活动首页' ,
                'background'  => '#FFFFFF' ,
                'share_title' => '' ,
                'share_image' => '' ,
            ] ,
            'menu' => -1 ,
            'adv'  => '' ,
            'items' => [
                'D1234567890' => self::defaultInfo('search2') ,
                'D1234567891' => self::defaultInfo('banner',['type'=>14]) ,
                'D1234567892' => self::defaultInfo('community',['type'=>14]) ,
                'D1234567893' => self::defaultInfo('options2',['type'=>14]) ,
            ] ,
        ];
        return ['data'=>$data];
    }
    /**
     * Comment: 获取招聘首页默认配置信息
     * Author: zzw
     * Date: 2020/12/10 16:31
     * @return array
     */
    public static function getRecruitPageDefaultInfo(){
        $data = [
            'type' => 15,
            'page' => [
                'title'       => '招聘首页' ,
                'background'  => '#FFFFFF' ,
                'share_title' => '' ,
                'share_image' => '' ,
            ] ,
            'menu' => -1 ,
            'adv'  => '' ,
            'items' => [
                'D1234567890' => self::defaultInfo('search2',['type'=>15]) ,
                'D1234567891' => self::defaultInfo('banner',['type'=>15]) ,
                'D1234567892' => self::defaultInfo('menu',['type'=>15]) ,
                'D1234567893' => self::defaultInfo('statistics') ,
                'D1234567894' => self::defaultInfo('enterprise') ,
                'D1234567895' => self::defaultInfo('options2',['type'=>15]) ,
            ] ,
        ];

        return ['data'=>$data];
    }
    /**
     * Comment: 获取相亲交友默认配置信息
     * Author: zzw
     * Date: 2021/3/18 15:37
     * @return array[]
     */
    public static function getDatingPageDefaultInfo(){
        $data = [
            'type' => 16,
            'page' => [
                'title'       => '相亲交友' ,
                'background'  => '#FFFFFF' ,
                'share_title' => '' ,
                'share_image' => '' ,
            ] ,
            'menu' => -1 ,
            'adv'  => '' ,
            'items' => [
                'D1234567890' => self::defaultInfo('search2',['type'=>16]) ,
                'D1234567891' => self::defaultInfo('banner',['type'=>16]) ,
                'D1234567892' => self::defaultInfo('menu',['type'=>16]) ,
                'D1234567893' => self::defaultInfo('dating_statistics') ,
                'D1234567895' => self::defaultInfo('options2',['type'=>16]) ,
            ] ,
        ];

        return ['data'=>$data];
    }

    //获取家政服务默认配置信息
    public static function getHouseKeepPageDefaultInfo(){
        $data = [
            'type' => 18,
            'page' => [
                'title'       => '家政服务' ,
                'background'  => '#FFFFFF' ,
                'share_title' => '' ,
                'share_image' => '' ,
            ] ,
            'menu' => -1 ,
            'adv'  => '' ,
            'items' => [
                'D1234567890' => self::defaultInfo('search2',['type'=>18]) ,
                'D1234567892' => self::defaultInfo('banner',['type'=>18]) ,
                'D1234567891' => self::defaultInfo('menu',['type'=>18]) ,
                'D1234567893' => self::defaultInfo('menu2',['type'=>18]) ,
                'D1234567894' => self::defaultInfo('housekeep_service') ,
                'D1234567895' => self::defaultInfo('options2',['type'=>18]) ,
            ] ,
        ];

        return ['data'=>$data];
    }

    //获取房产默认配置信息
    public static function getHousePageDefaultInfo(){
        $data = [
            'type' => 20,
            'page' => [
                'title'       => '房产' ,
                'background'  => '#FFFFFF' ,
                'share_title' => '' ,
                'share_image' => '' ,
            ] ,
            'menu' => -1 ,
            'adv'  => '' ,
            'items' => [
                'D1234567890' => self::defaultInfo('search2',['type'=>20]) ,
                'D1234567891' => self::defaultInfo('menu',['type'=>20]) ,
                'D1234567892' => self::defaultInfo('banner',['type'=>20]) ,
                'D1234567893' => self::defaultInfo('menu2',['type'=>20]) ,
                'D1234567894' => self::defaultInfo('house_service') ,
                'D1234567895' => self::defaultInfo('options2',['type'=>20]) ,
            ] ,
        ];

        return ['data'=>$data];
    }



}