<?php
defined('IN_IA') or exit('Access Denied');


class Area{
    /////////////////////////////////////////////////////代理///////////////////////////////////////////////////////////////
    /**
     * 获取所有代理(检索所有代理，根据 username、group、status、endtime检索)
     */
    static function getAllAgent($page = 0,$pagenum = 10,$con = '')
    {
        global $_W;
        $condition = '';
        if (!empty($con) && is_array($con)) {
            foreach ($con as $key => $val) {
                if ($key == 'username') $condition .= " and ".$key." like '%".$val."%'";
                if ($key == 'groupid') $condition .= " and ".$key."=".$val;
                if ($key == 'status') $condition .= " and ".$key."=".$val;
                if ($key == 'agentname') $condition .= " and (".$key." like '%".$val."%' or mobile like '%".$val."%' or realname like '%".$val."%')";
            }
        }
        $re['data']  = pdo_fetchall("select * from".tablename(PDO_NAME.'agentusers')."where uniacid=:uniacid  ".$condition." order by groupid desc, starttime desc limit ".$page * $pagenum.",".$pagenum,[':uniacid' => $_W['uniacid']]);
        $re['count'] = pdo_fetchcolumn("select count(*) from".tablename(PDO_NAME.'agentusers')."where uniacid=:uniacid  ".$condition,[':uniacid' => $_W['uniacid']]);
        return $re;
    }
    /**
     * 获取单条代理信息
     */
    static function getSingleAgent($id)
    {
        global $_W;
        if (empty($id)) return false;
        $re            = pdo_get(PDO_NAME.'agentusers',['id' => $id,'uniacid' => $_W['uniacid']]);
        $re['percent'] = unserialize($re['percent']);
        return $re;
    }
    /**
     * 编辑代理
     */
    static function editAgent($arr,$id = '')
    {
        global $_W;
        if (empty($arr)) return false;
        if (empty($id)) {
            $arr['uniacid'] = $_W['uniacid'];
            pdo_insert(PDO_NAME.'agentusers',$arr);
            $id = pdo_insertid();
        } else {
            pdo_update(PDO_NAME.'agentusers',$arr,['id' => $id,'uniacid' => $_W['uniacid']]);
        }
        return $id;
    }

    /////////////////////////////////////////////////////代理分组/////////////////////////////////////////////////////////////
    /**
     * 获取所有分组
     */
    static function getAllGroup($page = 0,$pagenum = 10,$enabled = '')
    {
        global $_W;
        $condition = '';
        if (!empty($enabled) && $enabled != '') $condition = " and enabled=".$enabled;
        $re['data'] = pdo_fetchall("select * from".tablename(PDO_NAME.'agentusers_group')."where uniacid=:uniacid  ".$condition." order by enabled desc, createtime desc limit ".$page * $pagenum.",".$pagenum,[':uniacid' => $_W['uniacid']]);
        foreach ($re['data'] as $key => &$value) {
            if (!empty($value['package'])) {
                $value['package'] = iunserializer($value['package']);
            }
        }
        $re['count'] = pdo_fetchcolumn("select count(*) from".tablename(PDO_NAME.'agentusers_group')."where uniacid=:uniacid  ".$condition,[':uniacid' => $_W['uniacid']]);
        return $re;
    }
    /**
     * 获取单条分组
     */
    static function getSingleGroup($id)
    {
        global $_W;
        if (empty($id)) return false;
        $group = pdo_get(PDO_NAME.'agentusers_group',['id' => $id,'uniacid' => $_W['uniacid']]);
        if (!empty($group)) {
            $group['package'] = iunserializer($group['package']);
        }
        return $group;
    }
    /**
     * 编辑分组
     */
    static function editGroup($arr,$id = '')
    {
        global $_W;
        if (empty($arr)) return false;
        if ($arr['isdefault'] == 1) pdo_update(PDO_NAME.'agentusers_group',['isdefault' => 0],[
            'uniacid'   => $_W['uniacid'],
            'isdefault' => 1
        ]);
        if (!empty($id) && $id != '') return pdo_update(PDO_NAME.'agentusers_group',$arr,[
            'id'      => $id,
            'uniacid' => $_W['uniacid']
        ]);
        $arr['uniacid'] = $_W['uniacid'];
        return pdo_insert(PDO_NAME.'agentusers_group',$arr);
    }

    /////////////////////////////////////////////////////自营地区/////////////////////////////////////////////////////////////
    /**
     * 获取可用地区
     */
    static function address_tree_in_use()
    {
        global $_W;
        $provinces = pdo_getall(PDO_NAME.'area',[
            'visible'      => 2,
            'level'        => 1,
            'displayorder' => ['0',$_W['uniacid']]
        ],['id','name'],'id');
        $cities    = pdo_getall(PDO_NAME.'area',[
            'visible'      => 2,
            'level'        => 2,
            'displayorder' => ['0',$_W['uniacid']]
        ],['id','pid','name'],'id');
        $districts = pdo_getall(PDO_NAME.'area',[
            'visible'      => 2,
            'level'        => 3,
            'displayorder' => ['0',$_W['uniacid']]
        ],['id','pid','name'],'id');
        $towns     = pdo_getall(PDO_NAME.'area',[
            'visible'      => 2,
            'level'        => 4,
            'displayorder' => ['0',$_W['uniacid']]
        ],['id','pid','name'],'id');
        $address_tree = [];
        foreach ($provinces as $province_id => $province) {
            $address_tree[$province_id] = [
                'title'  => $province['name'],
                'cities' => []
            ];
            foreach ($cities as $city_id => $city) {
                if ($city['pid'] == $province_id) {
                    $address_tree[$province_id]['cities'][$city_id] = [
                        'title'     => $city['name'],
                        'districts' => [],
                    ];
                    foreach ($districts as $district_id => $district) {
                        if ($district['pid'] == $city_id) {
                            $address_tree[$province_id]['cities'][$city_id]['districts'][$district_id] = [
                                'title' => $district['name'],
                                'towns' => [],
                            ];
                            foreach ($towns as $town_id => $town) {
                                if ($town['pid'] == $district_id) {
                                    $address_tree[$province_id]['cities'][$city_id]['districts'][$district_id]['towns'][$town_id] = $town['name'];
                                }
                            }
                        }
                    }
                }
            }
        }
        return $address_tree;
    }
    /**
     * 获取可用地区
     */
    static function get_all_in_use($type = 0)
    {
        global $_W;
        $area = pdo_get(PDO_NAME.'oparea',['uniacid' => $_W['uniacid'],'aid' => $_W['aid']]);
        if ($area['level'] == 1) {
            $address_tree[$area['areaid']] = [
                'title'  => pdo_getcolumn(PDO_NAME.'area',['id' => $area['areaid']],'name'),
                'cities' => []
            ];
            $cities                        = pdo_getall(PDO_NAME.'area',['pid' => $area['areaid']],['id','name']);
            foreach ($cities as $key => $value) {
                $address_tree[$area['areaid']]['cities'][$value['id']] = [
                    'title'     => $value['name'],
                    'districts' => [],
                ];
                $districts                                             = pdo_getall(PDO_NAME.'area',['pid' => $value['id']],[
                    'id',
                    'name'
                ]);
                foreach ($districts as $district_id => $district) {
                    $address_tree[$area['areaid']]['cities'][$value['id']]['districts'][$district['id']] = $district['name'];
                }
            }
        } else if ($area['level'] == 2) {
            $provinceid                                           = pdo_getcolumn(PDO_NAME.'area',['id' => $area['areaid']],'pid');
            $address_tree[$provinceid]                            = [
                'title'  => pdo_getcolumn(PDO_NAME.'area',['id' => $provinceid],'name'),
                'cities' => []
            ];
            $address_tree[$provinceid]['cities'][$area['areaid']] = [
                'title'     => pdo_getcolumn(PDO_NAME.'area',['id' => $area['areaid']],'name'),
                'districts' => [],
            ];
            $districts                                            = pdo_getall(PDO_NAME.'area',['pid' => $area['areaid']],[
                'id',
                'name'
            ]);
            foreach ($districts as $district_id => $district) {
                $address_tree[$provinceid]['cities'][$area['areaid']]['districts'][$district['id']] = $district['name'];
            }
        } else {
            $cityid     = pdo_getcolumn(PDO_NAME.'area',['id' => $area['areaid']],'pid');
            $provinceid = pdo_getcolumn(PDO_NAME.'area',['id' => $cityid],'pid');
            $address_tree[$provinceid]                                                  = [
                'title'  => pdo_getcolumn(PDO_NAME.'area',['id' => $provinceid],'name'),
                'cities' => []
            ];
            $address_tree[$provinceid]['cities'][$cityid]                               = [
                'title'     => pdo_getcolumn(PDO_NAME.'area',['id' => $cityid],'name'),
                'districts' => [],
            ];
            $address_tree[$provinceid]['cities'][$cityid]['districts'][$area['areaid']] = pdo_getcolumn(PDO_NAME.'area',['id' => $area['areaid']],'name');
        }
        if ($type == 1) {
            $address_tree = array_values($address_tree);
            foreach ($address_tree as $key => &$value) {
                $value['name'] = $value['title'];
                $value['sub']  = array_values($value['cities']);
                unset($value['title'],$value['cities']);
                foreach ($value['sub'] as $key1 => &$value1) {
                    $value1['name'] = $value1['title'];
                    $value1['sub']  = array_values($value1['districts']);
                    unset($value1['title'],$value1['districts']);
                    foreach ($value1['sub'] as $key2 => &$value2) {
                        $value2 = ['name' => $value2];
                    }
                }
            }
        }
        return $address_tree;
    }
    //商家入驻地区信息
    static function get_all_wx_use()
    {
        global $_W;
        $address_tree = self::address_tree_in_use();
        $address_tree = array_values($address_tree);
        foreach ($address_tree as $key => &$value) {
            $value['name'] = $value['title'];
            $value['sub']  = array_values($value['cities']);
            unset($value['title'],$value['cities']);
            foreach ($value['sub'] as $key1 => &$value1) {
                $value1['name'] = $value1['title'];
                $value1['sub']  = array_values($value1['districts']);
                unset($value1['title'],$value1['districts']);
                foreach ($value1['sub'] as $key2 => &$value2) {
                    $value2 = ['name' => $value2['title']];
                    unset($value2['title'],$value2['districts']);
                }
            }
        }
        return $address_tree;
    }
    /**
     * 获取代理地区
     */
    static function get_agent_area($aid = '')
    {
        global $_W;
        $data = ['uniacid' => $_W['uniacid']];
        if (!empty($aid)) $data['aid'] = $aid;
        $terarea = pdo_getall(PDO_NAME.'oparea',$data,'areaid');
        $terarea = Util::i_array_column($terarea,'areaid');
        return $terarea;
    }
    /**
     * 保存代理地区
     */
    static function save_agent_area($districts,$level,$aid)
    {
        global $_W,$_GPC;
        if (empty($districts) || ($level == 1 && empty($districts['province'])) || ($level == 2 && empty($districts['city'])) || ($level == 3 && empty($districts['district'])) || ($level == 4 && empty($districts['town']))) {
            WeliamWeChat::rollback();//事务回滚
            wl_message('请选择代理地区');
        }
        $data = ['uniacid' => $_W['uniacid'],'aid' => $aid,'level' => $level];
        switch ($level) {
            case '1':
                $data['areaid'] = $districts['province'];
                break;
            case '2':
                $data['areaid'] = $districts['city'];
                break;
            case '4':
                $data['areaid'] = $districts['town'];
                break;
            default:
                $data['areaid'] = $districts['district'];
                break;
        }
        $hasarea = pdo_getcolumn(PDO_NAME.'oparea',[
            'uniacid' => $_W['uniacid'],
            'aid !='  => $aid,
            'areaid'  => $data['areaid']
        ],'id');
        if ($hasarea) {
            WeliamWeChat::rollback();//事务回滚
            wl_message('当前地区已被代理，请重新选择地区');
        }
        $opareaid = pdo_getcolumn(PDO_NAME.'oparea',['uniacid' => $_W['uniacid'],'aid' => $aid],'id');
        if ($opareaid) {
            pdo_update(PDO_NAME.'oparea',$data,['id' => $opareaid]);
        } else {
            pdo_insert(PDO_NAME.'oparea',$data);
        }
        return true;
    }
    /**
     * 获取所有运营地区
     */
    static function get_all_area($type = '')
    {
        global $_W;
        $address_tree = [];
        $terarea      = pdo_getall(PDO_NAME.'oparea',['uniacid' => $_W['uniacid'],'status' => 1],['areaid','aid']);
        $terareas     = Util::i_array_column($terarea,'areaid');
        if ($type == 1) {
            foreach ($terarea as $key => $val) {
                $name               = pdo_getcolumn(PDO_NAME.'area',['id' => $val['areaid']],'name');
                $address_tree[$key] = ['id' => $val['areaid'],'name' => $name,'aid' => $val['aid']];
            }
            return $address_tree;
        }
        $provinces = pdo_getall(PDO_NAME.'area',[
            'visible'      => 2,
            'level'        => 1,
            'displayorder' => ['0',$_W['uniacid']]
        ],['id','name']);
        $cities    = pdo_getall(PDO_NAME.'area',[
            'visible'      => 2,
            'level'        => 2,
            'displayorder' => ['0',$_W['uniacid']]
        ],['id','pid','name']);
        foreach ($provinces as $province_id => $province) {
            $address_tree[$province_id] = [
                'id'       => $province['id'],
                'name'     => $province['name'],
                'children' => []
            ];
            foreach ($cities as $city_id => $city) {
                if (@in_array($city['id'],$terareas)) {
                    if ($city['pid'] == $province['id']) {
                        $address_tree[$province_id]['children'][$city_id] = [
                            'id'   => $city['id'],
                            'name' => $city['name']
                        ];
                    }
                }
            }
            if (empty($address_tree[$province_id]['children'])) {
                unset($address_tree[$province_id]);
            }
        }
        return $address_tree;
    }
    /**
     * 通过IP返回地区
     */
    static function get_area()
    {
        global $_W;
        $maera   = Util::httpPost("http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip=".$_W['clientip']);
        $maera   = Util::object_array(json_decode($maera));
        $allarea = self::get_all_area(1);
        if (count($allarea) == 1) {
            $areaid = $allarea[0]['id'];
            $name   = $allarea[0]['name'];
        } else {
            if (!empty($maera['district'])) {
                foreach ($allarea as $key => $val) {
                    if (mb_substr($maera['district'],0,2,'utf-8') == mb_substr($val['name'],0,2,'utf-8')) {
                        $areaid = $val['id'];
                        $name   = $val['name'];
                        break;
                    }
                }
            }
            if (empty($areaid)) {
                foreach ($allarea as $key => $val) {
                    if (mb_substr($maera['city'],0,2,'utf-8') == mb_substr($val['name'],0,2,'utf-8')) {
                        $areaid = $val['id'];
                        $name   = $val['name'];
                        break;
                    }
                }
            }
        }
        return [
            'id'   => $areaid,
            'name' => $name,
            'lc'   => $maera['district'] ? $maera['city'].$maera['district'] : $maera['province'].$maera['city']
        ];
    }
    /**
     * 根据名称返回地区id
     */
    static function getIdByName($name)
    {
        global $_W;
        if (empty($name)) return false;
        $re = pdo_get(PDO_NAME.'area',['name' => $name],'id');
        return $re['id'];
    }
    /**
     * 根据id返回地区名称  type=0:省份-市区 ；type=1:市区
     */
    static function getAreaNameById($id,$type = 0)
    {
        global $_W;
        if (empty($id)) return false;
        if ($type == 0) {
            $city  = pdo_getcolumn(PDO_NAME.'area',['id' => $id],'name');
            $proId = intval($id / 10000) * 10000;
            $pro   = pdo_getcolumn(PDO_NAME.'area',['id' => $proId],'name');
            return $pro.'-'.$city;
        }
    }
    static function initAgent()
    {
        global $_W;
        if (empty($_W['uniacid'])) {
            return false;
        }
        $oparea  = ['uniacid' => $_W['uniacid'],'areaid' => 110100,'aid' => 0,'ishot' => 1,'level' => 2,'status' => 1];
        $default = pdo_getcolumn(PDO_NAME.'oparea',['uniacid' => $_W['uniacid'],'aid' => 0],'id');
        if (empty($default)) {
            $all = pdo_get(PDO_NAME.'oparea',['uniacid' => $_W['uniacid']],'id');
            if (!empty($all)) {
                $oparea['status'] = 0;
            }
            pdo_insert(PDO_NAME.'oparea',$oparea);
        }
        return true;
    }


    /**
     * Comment: 根据条件获取区域列表
     * Author: zzw
     * Date: 2021/4/15 9:56
     * @param array    $where   条件
     * @param string[] $field   查询字段
     * @return array|false|mixed
     */
    public static function getAreaList($where = [],$field = ['id','name']){
        $list = pdo_getall(PDO_NAME."area",$where,$field);

        return $list ? : [];
    }
    /**
     * Comment: 根据经纬度获取省、市、区id
     * Author: zzw
     * Date: 2021/4/22 9:36
     * @param $lat
     * @param $lng
     * @return array|string
     */
    public static function getAreaId($lat,$lng){
        //获取当前区域id
        $info = MapService::guide_gcoder($lat . ',' . $lng , 0);
        $id = $info['result']['ad_info']['adcode'] ? : '';
        if(!$id) return '';
        //根据当前区域id获取信息
        $areaInfo = pdo_get(PDO_NAME."area",['id'=>$id],['id','pid','level']);
        //根据等级获取内容
        switch ($areaInfo['level']){
            case 1:
                //省  当前id为省id
                $res = ['province_id'=>$areaInfo['id'],'city_id'=>'','area_id'=>''];
                break;//当前区域为省
            case 2:
                //市  当前id为市id  上级id为省id
                $res = ['province_id'=>$areaInfo['pid'],'city_id'=>$areaInfo['id'],'area_id'=>''];
                break;//当前区域为市
            case 3:
                //区、县  当前id为区、县id  上级id为市id   获取上级id的上级id，为省级id
                $provinceId = pdo_getcolumn(PDO_NAME."area",['id'=>$areaInfo['pid']],'pid');
                $res = ['province_id'=>$provinceId,'city_id'=>$areaInfo['pid'],'area_id'=>$areaInfo['id']];
                break;//当前区域为区、县
        }

        return $res;
    }

    /**
     * Comment: 获取所有地区信息
     * Author: wlf
     * Date: 2022/07/19 10:27
     * @param $lat
     * @param $lng
     * @return array|string
     */

    static function get_all_address()
    {
        global $_W;
        $provinces = pdo_getall(PDO_NAME.'area',[
            'visible'      => 2,
            'level'        => 1,
            'displayorder' => ['0',$_W['uniacid']]
        ],['id','name'],'id');
        $cities    = pdo_getall(PDO_NAME.'area',[
            'visible'      => 2,
            'level'        => 2,
            'displayorder' => ['0',$_W['uniacid']]
        ],['id','pid','name'],'id');
        $districts = pdo_getall(PDO_NAME.'area',[
            'visible'      => 2,
            'level'        => 3,
            'displayorder' => ['0',$_W['uniacid']]
        ],['id','pid','name'],'id');
        $towns     = pdo_getall(PDO_NAME.'area',[
            'visible'      => 2,
            'level'        => 4,
            'displayorder' => ['0',$_W['uniacid']]
        ],['id','pid','name'],'id');
        $address_tree = [];
        foreach ($provinces as $province_id => $province) {
            $address_tree[$province_id] = [
                'title'  => $province['name'],
                'cities' => []
            ];
            foreach ($cities as $city_id => $city) {
                if ($city['pid'] == $province_id) {
                    $address_tree[$province_id]['cities'][$city_id] = [
                        'title'     => $city['name'],
                        'districts' => [],
                    ];
                    foreach ($districts as $district_id => $district) {
                        if ($district['pid'] == $city_id) {
                            $address_tree[$province_id]['cities'][$city_id]['districts'][$district_id] = $district['name'];
                        }
                    }
                }
            }
        }
        return $address_tree;
    }

}
