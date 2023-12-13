<?php
defined('IN_IA') or exit('Access Denied');
/**
 * Comment: 行业职位
 * Author: zzw
 * Class industryPosition_WeliamController
 */
class IndustryPosition_WeliamController{
    /**
     * Comment: 行业列表
     * Author: zzw
     * Date: 2020/11/25 17:53
     */
    public function industryList(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $title     = $_GPC['title'] ? : '';//活动名称
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if($title){
            $ids = pdo_fetchall("SELECT pid FROM "
                                .tablename(PDO_NAME."recruit_industry")
                                .$where." AND title LIKE '%{$title}%' AND pid > 0");
            $ids = array_column($ids,'pid');
            $ids = $ids ? implode($ids) : [];
            $where .= " AND (title LIKE '%{$title}%' OR id IN ({$ids})) ";
        }
        //sql语句生成
        $field = "id,title,sort,create_time";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."recruit_industry");
        $order = " ORDER BY sort DESC,id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        //列表获取
        $list = pdo_fetchall($sql.$where." AND pid = 0".$order.$limit);
        foreach($list as $key => &$val){
            $val['list'] = pdo_fetchall($sql.$where." AND pid = {$val['id']}".$order);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where." AND pid = 0");
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('industry/index');
    }
    /**
     * Comment: 添加/编辑  行业
     * Author: zzw
     * Date: 2020/11/26 10:24
     */
    public function industryEdit(){
        global $_W,$_GPC;
        //基本参数信息获取
        $id = $_GPC['id'] ? : '';
        //接收信息 并且进行对应的处理
        if($_W['ispost']){
            $data  = $_GPC['data'];
            //判断当前行业是否已经存在
            $isWhere = ['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'title'=>$data['title']];
            if($id > 0) $isWhere['id <>'] = $id;
            $isHave = pdo_get(PDO_NAME."recruit_industry" ,$isWhere);
            if($isHave) wl_message('该行业已存在！',referer(),'error');
            //根据是否存在id 判断是添加操作还是修改操作
            if($id){
                //判断当前行业是否可以修改子行业信息
                if($data['pid']){
                    $pid = pdo_getcolumn(PDO_NAME."recruit_industry",['id'=>$id],'pid');
                    $sub = pdo_count(PDO_NAME."recruit_industry",['pid'=>$id]);
                    if($pid != $data['pid'] && $sub > 0) wl_message('当前行业存在子行业信息，禁止修改上级行业信息。',referer(),'error');
                }
                //修改当前行业信息
                pdo_update(PDO_NAME."recruit_industry",$data,['id'=>$id]);

                wl_message('修改成功',web_url('recruit/industryPosition/industryList'),'success');
            }else{
                //不存在 保存添加的行业信息
                $data['uniacid']     = $_W['uniacid'];
                $data['aid']         = $_W['aid'];
                $data['create_time'] = time();
                pdo_insert(PDO_NAME."recruit_industry",$data);

                wl_message('添加成功',web_url('recruit/industryPosition/industryList'),'success');
            }
        }
        //准备信息
        $pList = pdo_getall(PDO_NAME."recruit_industry",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'pid'=>0,'id <>'=>$id],['id','title']);
        if($id){
            //修改信息准备
            $info = pdo_get(PDO_NAME."recruit_industry",['id'=>$id],['title','pid','sort']);
            $info['sub'] = pdo_count(PDO_NAME."recruit_industry",['pid'=>$id]);
        }else{
            //添加信息准备
            $sort = pdo_fetchcolumn("SELECT MAX(id) FROM ".tablename(PDO_NAME."recruit_industry"));
            $info['sort'] = $sort ? : 0;
        }

        include wl_template('industry/edit');
    }
    /**
     * Comment: 生成默认行业信息
     * Author: zzw
     * Date: 2020/11/26 10:47
     */
    public function industryDefaultInfo(){
        global $_W,$_GPC;
        //获取默认行业类别
        $list = Recruit::defaultIndustryList();
        $tableName = PDO_NAME."recruit_industry";
        $publicWhere = ['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid']];
        //修改所有默认信息的排序信息
        pdo_fetchall("update ".tablename($tableName)." set `sort` = `id` WHERE `sort` is null ");
        foreach($list as $item){
            //判断当前行业是否已经存在
            $pid = pdo_getcolumn($tableName,array_merge($publicWhere,['title'=>$item['title']]),'id');
            if(!$pid){
                //不存在 添加信息
                $insertData = [
                    'uniacid'     => $_W['uniacid'] ,
                    'aid'         => $_W['aid'] ,
                    'title'       => $item['title'] ,
                    'pid'         => 0 ,
                    'create_time' => time() ,
                ];
                pdo_insert($tableName,$insertData);
                $pid = pdo_insertid();
            }
            //处理子行业信息
            foreach($item['list'] as $sub){
                //判断当前行业是否已经存在
                $isHave = pdo_getcolumn($tableName,array_merge($publicWhere,['title'=>$sub,'pid'=>$pid]),'id');
                if(!$isHave){
                    //不存在 添加信息
                    $subData = [
                        'uniacid'     => $_W['uniacid'] ,
                        'aid'         => $_W['aid'] ,
                        'title'       => $sub ,
                        'pid'         => $pid ,
                        'create_time' => time() ,
                    ];
                    pdo_insert($tableName,$subData);
                }
            }
        }
        //修改所有默认信息的排序信息
        pdo_fetchall("update ".tablename($tableName)." set `sort` = `id` WHERE `sort` is null ");

        wl_json(1,'生成成功');
    }
    /**
     * Comment: 删除行业信息
     * Author: zzw
     * Date: 2020/11/26 11:15
     */
    public function industryDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."recruit_industry",['id IN'=>$ids]);
        pdo_delete(PDO_NAME."recruit_industry",['pid IN'=>$ids]);
        pdo_delete(PDO_NAME."recruit_position",['industry_pid IN'=>$ids]);

        show_json(1, "删除成功");
    }
    /**
     * Comment: 获取某个上级行业下所有子行业信息
     * Author: zzw
     * Date: 2020/11/26 17:56
     */
    public function industrySubList(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR wl_json(0,'参数错误，请刷新重试!');
        //子行业列表信息获取
        $list = pdo_getall(PDO_NAME."recruit_industry",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'pid'=>$id],['id','title'],'','sort DESC,create_time DESC');

        wl_json(1,'子行业信息列表',$list);
    }
    /**
     * Comment: 导出行业信息
     * Author: zzw
     * Date: 2021/2/22 16:19
     */
    public function industryExport(){
        global $_W,$_GPC;
        //参数获取
        $title     = $_GPC['title'] ? : '';//活动名称
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if($title) $where .= " AND (title LIKE '%{$title}%' ) ";
        //sql语句生成
        $field = "a.title,
        CASE WHEN pid = 0 THEN ''
             ELSE (SELECT title FROM ".tablename(PDO_NAME."recruit_industry")." as b where b.id = a.pid)
        END as pid,a.sort,FROM_UNIXTIME(a.create_time) as create_time";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."recruit_industry")." as a ";
        $order = " ORDER BY sort DESC,id DESC ";
        //列表获取
        $list = pdo_fetchall($sql.$where.$order);
        //标题列表数组
        $title = [
            'title'       => '行业标题',
            'pid'         => '上级行业',
            'sort'        => '排序',
            'create_time' => '创建时间',
        ];
        //导出信息
        util_csv::export_csv_2($list, $title, '行业信息.csv');die;
    }


    /**
     * Comment: 职位列表
     * Author: zzw
     * Date: 2020/11/26 15:40
     */
    public function positionList(){
        global $_W,$_GPC;
        //参数获取
        $page        = max(1 , intval($_GPC['page']));
        $pageIndex   = 10;
        $pageStart   = $page * $pageIndex - $pageIndex;
        $title       = $_GPC['title'] ? : '';//活动名称
        $industryPid = $_GPC['industry_pid'] ? : '';//上级行业id
        $industryId  = $_GPC['industry_id'] ? : '';//子行业id
        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} AND a.aid = {$_W['aid']} ";
        if($title) $where .= " AND a.title LIKE '%{$title}%' ";
        if($industryPid > 0) $where .= " AND a.industry_pid = {$industryPid} ";
        if($industryId > 0) $where .= " AND a.industry_id = {$industryId} ";
        //sql语句生成
        $field = "a.id,a.title,a.sort,a.create_time,b.title as industry_one_title,c.title as industry_two_title";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."recruit_position")
            ." as a LEFT JOIN ".tablename(PDO_NAME."recruit_industry")
            ." as b ON a.industry_pid = b.id LEFT JOIN ".tablename(PDO_NAME."recruit_industry")
            ." as `c` ON a.industry_id = c.id ";
        $order = " ORDER BY a.sort DESC,a.id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        //列表获取
        if($_GPC['export'] == 1) $this->positionExport(pdo_fetchall($sql.$where.$order));
        $list = pdo_fetchall($sql.$where.$order.$limit);
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);
        //获取上级行业信息
        $industryList = pdo_getall(PDO_NAME."recruit_industry",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'pid'=>0],['id','title'],'','sort DESC,create_time DESC');
        if($industryPid > 0) $subList = pdo_getall(PDO_NAME."recruit_industry",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'pid'=>$industryPid],['id','title'],'','sort DESC,create_time DESC');

        include wl_template('position/index');
    }
    /**
     * Comment: 职位添加/编辑
     * Author: zzw
     * Date: 2020/11/26 15:41
     */
    public function positionEdit(){
        global $_W,$_GPC;
        $id = $_GPC['id'] ? : '';
        //接收信息 并且进行对应的处理
        if($_W['ispost']){
            $data  = $_GPC['data'];
            //获取上级行业id
            $data['industry_pid'] = pdo_getcolumn(PDO_NAME."recruit_industry",['id'=>$data['industry_id']],'pid');
            //判断职位是否已经存在
            $haveWhere = [
                'uniacid'      => $_W['uniacid'] ,
                'aid'          => $_W['aid'] ,
                'title'        => $data['title'] ,
                'industry_pid' => $data['industry_pid'] ,
                'industry_id'  => $data['industry_id']
            ];
            if($id) $haveWhere['id <>'] = $id;
            $isHave = pdo_get(PDO_NAME."recruit_position",$haveWhere);
            if($isHave) wl_message('当前职位已经存在，请勿重复添加！',referer(),'error');
            //根据是否存在id 判断是添加操作还是修改操作
            if($id){
                pdo_update(PDO_NAME."recruit_position",$data,['id'=>$id]);

                wl_message('修改成功',web_url('recruit/industryPosition/positionList'),'success');
            }else{
                //判断职位是否已经存在
                $data['uniacid']     = $_W['uniacid'];
                $data['aid']         = $_W['aid'];
                $data['create_time'] = time();
                pdo_insert(PDO_NAME."recruit_position",$data);

                wl_message('添加成功',web_url('recruit/industryPosition/positionList'),'success');
            }
        }
        //获取所有的行业列表信息
        $industryList = pdo_getall(PDO_NAME."recruit_industry",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'pid'=>0],['id','title'],'','sort DESC,create_time DESC');
        foreach($industryList as &$industryItem){
            $industryItem['list'] = pdo_getall(PDO_NAME."recruit_industry",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'pid'=>$industryItem['id']],['id','title'],'','sort DESC,create_time DESC');
        }
        //准备信息
        if($id){
            //修改信息准备
            $info = pdo_get(PDO_NAME."recruit_position",['id'=>$id],['title','industry_id','sort']);
        }else{
            //添加信息准备
            $sort = pdo_fetchcolumn("SELECT MAX(id) FROM ".tablename(PDO_NAME."recruit_position"));
            $info['sort'] = $sort ? : 0;
        }

        include wl_template('position/edit');
    }
    /**
     * Comment: 生成默认职位信息
     * Author: zzw
     * Date: 2020/11/26 16:47
     */
    public function positionDefaultInfo(){
        global $_W,$_GPC;
        //获取职位列表信息
        $list = Cache::getCache('recruit','position');
        if($list){
            $list = json_decode($list,true);
        }else{
            $list = Recruit::defaultPositionList();
            Cache::setCache('recruit','position',json_encode($list));
        }
        //当前需要处理的行业的key值获取  职位列表信息获取
        $industry = array_keys($list)[0];
        $position = $list[$industry];
        //获取职位信息
        $info = pdo_get(PDO_NAME."recruit_industry",['uniacid'=>$_W['uniacid'],'aid'=>$_W['aid'],'title'=>$industry],['id','pid']);
        if($info){
            //循环删除对应的职位信息
            foreach($position as $title){
                //判断是否存在职位信息
                $data = [
                    'uniacid'      => $_W['uniacid'] ,
                    'aid'          => $_W['aid'] ,
                    'title'        => trim($title) ,
                    'industry_pid' => $info['pid'] ,
                    'industry_id'  => $info['id']
                ];
                $isHave = pdo_get(PDO_NAME."recruit_position",$data);
                if(!$isHave){
                    //不存在 添加职位信息
                    $data['create_time'] = time();
                    pdo_insert(PDO_NAME."recruit_position",$data);
                }
            }
        }
        //修改所有默认信息的排序信息
        pdo_fetchall("update ".tablename(PDO_NAME."recruit_position")." set `sort` = `id` WHERE `sort` is null ");
        //删除当前行业信息 修改缓存信息
        unset($list[$industry]);
        $total = count($list);
        if($total > 0) Cache::setCache('recruit','position',json_encode($list));
        else Cache::deleteCache('recruit','position');
        //计算已生成的占比率
        $rate = sprintf("%.2f",(count(Recruit::defaultPositionList()) - $total) / count(Recruit::defaultPositionList()) * 100);

        wl_json(1,'生成成功',['total'=>$total,'rate'=>$rate]);
    }
    /**
     * Comment: 删除指定的职位信息
     * Author: zzw
     * Date: 2020/11/26 16:57
     */
    public function positionDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        if(is_array($ids)) pdo_delete(PDO_NAME."recruit_position",['id IN'=>$ids]);
        else pdo_delete(PDO_NAME."recruit_position",['id'=>$ids]);

        show_json(1, "删除成功");
    }
    /**
     * Comment: 导出职位信息
     * Author: zzw
     * Date: 2021/2/22 16:33
     * @param $list
     */
    public function positionExport($list){
        //处理职位信息
        foreach($list as &$val){
            //处理时间信息
            $val['create_time'] = date("Y-m-d H:i:s",$val['create_time']);
            //行业拼接
            $val['industry'] = $val['industry_one_title'].' - '.$val['industry_two_title'];
            //删除不需要的数据
            unset($val['id'],$val['industry_one_title'],$val['industry_two_title']);
        }
        //标题列表数组
        $title = [
            'title'       => '职位名称',
            'sort'        => '排序',
            'create_time' => '创建时间',
            'industry'    => '所属行业',
        ];
        //导出信息
        util_csv::export_csv_2($list, $title, '职位信息.csv');die;
    }



}