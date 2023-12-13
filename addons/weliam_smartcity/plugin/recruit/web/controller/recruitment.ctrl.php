<?php
defined('IN_IA') or exit('Access Denied');
/**
 * Comment: 求职招聘
 * Author: zzw
 * Class Recruitment_WeliamController
 */
class Recruitment_WeliamController{
    /**
     * Comment: 标签列表
     * Author: zzw
     * Date: 2020/12/1 10:49
     */
    public function labelList(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $title     = $_GPC['title'] ? : '';//名称
        $type      = $_GPC['type'] ? : 0;//类型
        //条件生成
        $where = [
            'uniacid' => $_W['uniacid'] ,
            'aid'     => $_W['aid']
        ];
        if($title) $where['title LIKE'] = "%{$title}%";
        if($type > 0) $where['type'] = $type;
        //列表获取
        $field = ['id','title','type','create_time','sort'];
        $order = 'sort DESC,id DESC';
        $list = pdo_getall(PDO_NAME."recruit_label",$where,$field,'',$order,[$page,$pageIndex]);
        //分页操作
        $total = pdo_count(PDO_NAME."recruit_label",$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('label/labelindex');
    }
    /**
     * Comment: 标签添加/编辑
     * Author: zzw
     * Date: 2020/12/1 11:04
     */
    public function labelEdit(){
        global $_W,$_GPC;
        //基本参数信息获取
        $id = $_GPC['id'] ? : '';
        //接收信息 并且进行对应的处理
        if($_W['ispost']){
            $data  = $_GPC['data'];
            //判断是否已经存在当前标签
            $where = [
                'uniacid' => $_W['uniacid'] ,
                'aid'     => $_W['aid'] ,
                'title'   => $data['title'] ,
                'type'    => $data['type']
            ];
            if($id > 0) $where['id <>'] = $id;
            $isHave = pdo_get(PDO_NAME."recruit_label",$where);
            if($isHave) wl_message('标签已经存在',referer(),'error');
            //根据是否存在id 判断是添加操作还是修改操作
            if($id){
                pdo_update(PDO_NAME."recruit_label",$data,['id'=>$id]);

                wl_message('修改成功',web_url('recruit/recruitment/labelList'),'success');
            }else{
                //信息补充  并且进行添加操作
                $data['uniacid']     = $where['uniacid'];
                $data['aid']         = $where['aid'];
                $data['create_time'] = time();
                pdo_insert(PDO_NAME."recruit_label",$data);

                wl_message('添加成功',web_url('recruit/recruitment/labelList'),'success');
            }
        }
        //准备信息
        if($id){
            //修改信息准备
            $info = pdo_get(PDO_NAME."recruit_label",['id'=>$id],['title','type','sort']);
        }else{
            //添加信息准备
            $sort = pdo_fetchcolumn("SELECT MAX(id) FROM ".tablename(PDO_NAME."recruit_label"));
            $info['sort'] = $sort ? : 0;
        }

        include wl_template('label/edit');
    }
    /**
     * Comment: 生成默认的标签信息
     * Author: zzw
     * Date: 2020/12/1 10:33
     */
    public function labelDefaultInfo(){
        global $_W,$_GPC;
        //获取默认行业类别
        $list = Recruit::defaultLabelList();
        foreach($list as $val){
            //判断是否已经存在当前标签
            $val['uniacid'] = $_W['uniacid'];
            $val['aid']     = $_W['aid'];
            $isHave = pdo_get(PDO_NAME."recruit_label",$val);
            if(!$isHave){
                //不存在 添加标签信息
                $val['create_time'] = time();
                pdo_insert(PDO_NAME."recruit_label",$val);
            }
        }
        //修改所有默认信息的排序信息
        pdo_fetchall("update ".tablename(PDO_NAME."recruit_label")." set `sort` = `id` WHERE `sort` is null ");

        wl_json(1,'生成成功');
    }
    /**
     * Comment: 删除标签信息
     * Author: zzw
     * Date: 2020/12/1 11:07
     */
    public function labelDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."recruit_label",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }



    /**
     * Comment: 招聘列表
     * Author: zzw
     * Date: 2020/12/3 10:27
     */
    public function recruitList(){
        global $_W,$_GPC;
        //参数获取
        $page            = max(1 , intval($_GPC['page']));
        $pageIndex       = 10;
        $pageStart       = $page * $pageIndex - $pageIndex;
        $title           = $_GPC['title'] ? : '';//名称
        $industryPid     = $_GPC['industry_pid'] ? : 0;//上级行业id
        $industryId      = $_GPC['industry_id'] ? : 0;//子行业id
        $positionId      = $_GPC['position_id'] ? : 0;//职位id
        $recruitmentType = $_GPC['recruitment_type'] ? : 0;//招聘类型:1=个人招聘,2=企业招聘
        $status          = $_GPC['status'] ? : 0;//招聘状态：1=待付款，2=审核中，3=未通过，4=招聘中，5=已结束
        $isTop           = $_GPC['is_top'] ? : 0;//0=全部，1=置顶中，2=未置顶

        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if ($title) $where .= " AND title LIKE '%{$title}%' ";
        if ($industryPid > 0) $where .= " AND industry_pid = {$industryPid} ";
        if ($industryId > 0) $where .= " AND industry_id = {$industryId} ";
        if ($positionId > 0) $where .= " AND position_id = {$positionId} ";
        if ($recruitmentType > 0) $where .= " AND recruitment_type = {$recruitmentType} ";
        if ($status > 0) $where .= " AND status = {$status} ";
        if ($isTop == 1) $where .= " AND is_top = 1 ";
        else if($isTop == 2) $where .= " AND is_top = 0 ";

        if(is_store()){
            $where .= " AND recruitment_type = 2 AND release_sid = {$_W['storeid']}";
        }

        //sql信息生成
        $field = "id,title,position_id,recruitment_type,release_mid,release_sid,work_address,contacts,contact_phone,status,create_time,sort,
        CASE WHEN job_type = 1 THEN 
                  CASE WHEN full_type = 1 THEN '面议'
                       ELSE concat(full_salary_min,'元 ~ ',full_salary_max,'元')
                  END 
             ELSE CASE WHEN part_type = 1 THEN concat(part_salary,'元/时','(',
                            CASE WHEN part_settlement = 1 THEN '日结'
                                 WHEN part_settlement = 2 THEN '周结'
                                 WHEN part_settlement = 3 THEN '月结'
                                 WHEN part_settlement = 4 THEN '完工结算'
                            END,')')
                       ELSE concat(part_salary,'元/天','(',
                            CASE WHEN part_settlement = 1 THEN '日结'
                                 WHEN part_settlement = 2 THEN '周结'
                                 WHEN part_settlement = 3 THEN '月结'
                                 WHEN part_settlement = 4 THEN '完工结算'
                            END,')')
                 END
        END as salary,is_top,top_end_time";
        $sql = " SELECT {$field} FROM ".tablename(PDO_NAME."recruit_recruit");
        $order = ' ORDER BY sort DESC,id DESC ';
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        //列表获取并且处理
        if ($_GPC['export'] == 1) $this->recruitExport(pdo_fetchall($sql.$where.$order));
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach ($list as $index => &$item) {
            //职位信息获取
            $item['position'] = pdo_getcolumn(PDO_NAME . "recruit_position" , ['id' => $item['position_id']] , 'title');
            //发布方信息  招聘类型:1=个人招聘,2=企业招聘
            if ($item['recruitment_type'] == 1) $item['release'] = pdo_getcolumn(PDO_NAME . "member" , ['id' => $item['release_mid']] , 'nickname');
            else $item['release'] = pdo_getcolumn(PDO_NAME . "merchantdata" , ['id' => $item['release_sid']] , 'storename');
            //删除多余的信息
            unset($item['position_id'] , $item['release_mid'] , $item['release_sid']);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);
        //获取行业职位信息
        $industry = Recruit::getIndustryList(['pid'=>0],['id','title']);//上级行业列表
        if ($industryPid) $subIndustry = Recruit::getIndustryList(['pid'=>$industryPid],['id','title']);//子行业列表
        if ($industryId) $position = Recruit::getPositionList(['industry_id'=>$industryId],['id','title']);//子行业列表

        include wl_template('recruit/index');
    }
    /**
     * Comment: 招聘信息添加/编辑
     * Author: zzw
     * Date: 2020/12/3 15:19
     */
    public function recruitEdit(){
        global $_W,$_GPC;
        //基本参数信息获取
        $id = $_GPC['id'] ? : '';
        //接收信息 并且进行对应的处理
        if($_W['ispost']){
            $data  = $_GPC['data'];
            //判断内容是否完善
            if(!$data['title']) wl_json(0,'请输入职位名称');
            if(is_store()){
                $data['recruitment_type'] = 2;
                $data['release_sid'] = $_W['storeid'];
            }else{
                if($data['recruitment_type'] == 1 && !$data['release_mid']) wl_json(0,'请选择发布人');
                if($data['recruitment_type'] == 2 && !$data['release_sid']) wl_json(0,'请选择发布企业');
            }
            if($data['job_type'] == 1 && $data['full_type'] == 2 && !$data['full_salary_min']) wl_json(0,'请输入最低薪资');
            if($data['job_type'] == 1 && $data['full_type'] == 2 && !$data['full_salary_max']) wl_json(0,'请输入最高薪资');
            if($data['job_type'] == 2 && !$data['part_salary']) wl_json(0,'请输入薪资金额');
            if(!$data['work_address'] || !$data['work_lng'] || !$data['work_lat']) wl_json(0,'请选择工作地址');
            if(!$data['contacts']) wl_json(0,'请输入联系人');
            if(!$data['contact_phone']) wl_json(0,'请输入联系方式');
            if(!$data['age_min'] || !$data['age_max']) wl_json(0,'请输入年龄要求');
            //信息处理
            if(is_array($data['welfare'])) $data['welfare'] = implode(',',$data['welfare']);
            //时间处理
            $data['top_end_time'] = strtotime($data['top_end_time']);
            //status参数
            if(is_store() && $data['status'] == 4){
                $audits =  pdo_getcolumn(PDO_NAME.'merchantdata',array('id'=>$data['release_sid']),'audits');
                if(empty($audits)){
                    $data['status'] = 2;
                }
            }
            //根据是否存在id 判断是添加操作还是修改操作
            if($id){
                pdo_update(PDO_NAME."recruit_recruit",$data,['id'=>$id]);

                wl_json(1,'编辑成功');
            }else{
                $data['uniacid']     = $_W['uniacid'];
                $data['aid']         = $_W['aid'];
                $data['create_time'] = time();
                pdo_insert(PDO_NAME."recruit_recruit",$data);

                wl_json(1,'发布成功');
            }
        }
        //准备信息
        $oneIndustry = Recruit::getIndustryList(['pid'=>0],['id','title']);//上级行业列表
        $provinceList = pdo_getall(PDO_NAME."area",['level'=>1,'visible'=>2],['id','name']);//省级列表
        $welfareLabel = Recruit::getLabelList(2);
        $educationLabel = Recruit::getLabelList(1);
        $experienceLabel = Recruit::getLabelList(3);
        if($id){
            //修改信息准备
            $info = pdo_get(PDO_NAME."recruit_recruit",['id'=>$id]);
            //行业职位信息
            $subIndustryList = Recruit::getIndustryList(['pid'=>$info['industry_pid']],['id','title']);//子行业列表
            $positionList = Recruit::getPositionList(['industry_id'=>$info['industry_id']],['id','title']);//子行业列表
            //区域信息获取
            if($info['work_city']) $cityList = pdo_getall(PDO_NAME."area",['pid'=>$info['work_province']],['id','name']);
            if($info['work_area']) $areaList = pdo_getall(PDO_NAME."area",['pid'=>$info['work_city']],['id','name']);
            //获取发布方信息
            if($info['recruitment_type'] == 1) $user = pdo_get(PDO_NAME."member",['id'=>$info['release_mid']],['nickname','avatar']);
            else $shop = pdo_get(PDO_NAME."merchantdata",['id'=>$info['release_sid']],['storename','logo']);
            //处理福利标签信息
            $info['welfare'] = $info['welfare'] ? explode(',',$info['welfare']) : [];
            //处理富文本信息
            $jobDescription = $info['job_description'];
            //时间信息处理
            $info['top_end_time'] = $info['top_end_time'] > 0 ? date('Y-m-d H:i',$info['top_end_time']) : date('Y-m-d H:i',strtotime('-1 month'));
            unset($info['id'],$info['uniacid'],$info['aid'],$info['job_description']);
        }else{
            //添加信息准备
            $sort = pdo_fetchcolumn("SELECT MAX(id) FROM ".tablename(PDO_NAME."recruit_recruit"));
        }

        include wl_template('recruit/edit');
    }
    /**
     * Comment: 删除招聘信息
     * Author: zzw
     * Date: 2020/12/3 15:20
     */
    public function recruitDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? : [];
        pdo_delete(PDO_NAME."recruit_recruit",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }
    /**
     * Comment: 查看招聘信息
     * Author: zzw
     * Date: 2021/1/11 14:54
     */
    public function recruitSee(){
        global $_W,$_GPC;
        //基本参数信息获取
        $id = $_GPC['id'] ? : '';
        //获取招聘信息
        $info = pdo_get(PDO_NAME."recruit_recruit",['id'=>$id]);
        //获取行业职位信息
        $info['industry_pid_text'] = pdo_getcolumn(PDO_NAME."recruit_industry",['id'=>$info['industry_pid']],'title');
        $info['industry_id_text'] = pdo_getcolumn(PDO_NAME."recruit_industry",['id'=>$info['industry_id']],'title');
        $info['position_id_text'] = pdo_getcolumn(PDO_NAME."recruit_position",['id'=>$info['position_id']],'title');
        //获取发布方信息  招聘类型:1=个人招聘,2=企业招聘
        if($info['recruitment_type'] == 1) $info['release_name'] = pdo_getcolumn(PDO_NAME."member",['id'=>$info['release_mid']],'nickname');
        else $info['release_name'] = pdo_getcolumn(PDO_NAME."merchantdata",['id'=>$info['release_sid']],'storename');
        //职位福利信息获取
        if($info['job_type'] == 1) {
            //处理福利标签信息
            $welfare = $info['welfare'] ? explode(',',$info['welfare']) : [];
            $info['welfare'] = pdo_getall(PDO_NAME."recruit_label",['id IN'=>$welfare],['title']);
        }
        //获取工作区域信息
        $info['work_province_text'] = pdo_getcolumn(PDO_NAME."area",['id'=>$info['work_province']],'name');
        $info['work_city_text'] = pdo_getcolumn(PDO_NAME."area",['id'=>$info['work_city']],'name');
        $info['work_area_text'] = pdo_getcolumn(PDO_NAME."area",['id'=>$info['work_area']],'name');
        //获取学历要求
        if($info['education_label_id'] > 0) $info['education'] = pdo_getcolumn(PDO_NAME."recruit_label",['id'=>$info['education_label_id']],'title');
        else $info['education'] = '学历不限';
        //获取经验要求
        if($info['experience_label_id'] > 0) $info['experience'] = pdo_getcolumn(PDO_NAME."recruit_label",['id'=>$info['experience_label_id']],'title');
        else $info['experience'] = '经验不限';
        //职位描述信息处理
        $info['job_description'] = htmlspecialchars_decode($info['job_description']);


        include wl_template('recruit/see');
    }
    /**
     * Comment: 修改招聘信息状态
     * Author: zzw
     * Date: 2021/1/11 11:39
     */
    public function changeRecruitStatus(){
        global $_W,$_GPC;
        //参数信息获取
        $ids = $_GPC['ids'] or show_json(0,"参数错误，请刷新重试！");
        $status = $_GPC['status'] or show_json(0,"参数错误，请刷新重试！");
        $reson = $_GPC['reason'];
        //修改状态
        if(is_array($ids)) pdo_update(PDO_NAME."recruit_recruit",['status'=>$status],['id IN'=>$ids]);
        else pdo_update(PDO_NAME."recruit_recruit",['status'=>$status,'reason' =>$reson ],['id'=>$ids]);


        show_json(1, "操作成功");
    }
    /**
     * Comment: 导出招聘信息
     * Author: zzw
     * Date: 2021/2/22 16:52
     * @param $list
     */
    public function recruitExport($list){
        //循环处理信息
        foreach($list as &$val){
            $val['create_time'] = date("Y-m-d H:i",$val['create_time']);
            //职位信息获取
            $val['position'] = pdo_getcolumn(PDO_NAME . "recruit_position" , ['id' => $val['position_id']] , 'title');
            //发布方信息  招聘类型:1=个人招聘,2=企业招聘
            if ($val['recruitment_type'] == 1) {
                $val['release_type'] = '个人招聘';
                $val['release'] = pdo_getcolumn(PDO_NAME . "member" , ['id' => $val['release_mid']] , 'nickname');
            } else {
                $val['release_type'] = '企业招聘';
                $val['release'] = pdo_getcolumn(PDO_NAME . "merchantdata" , ['id' => $val['release_sid']] , 'storename');
            }
            //招聘状态
            switch ($val['status']){
                case 1:$val['status'] = '待付款';break;
                case 2:$val['status'] = '审核中';break;
                case 3:$val['status'] = '未通过';break;
                case 4:$val['status'] = '招聘中';break;
                case 5:$val['status'] = '已结束';break;
            }
            //是否置顶
            if($val['is_top'] == 1){
                $val['is_top'] = '置顶中';
                $val['top_end_time'] = date("Y-m-d H:i",$val['top_end_time']);
            }else{
                $val['is_top'] = '未置顶';
                $val['top_end_time'] = '';
            }
            //删除多余的信息
            unset($val['id'] , $val['position_id'] , $val['recruitment_type'] , $val['release_mid'] , $val['release_sid']);
        }
        //标题列表数组
        $title = [
            'title'         => '职位名称',
            'work_address'  => '工作详细地址',
            'contacts'      => '联系人',
            'contact_phone' => '联系方式',
            'status'        => '状态',
            'create_time'   => '发布时间',
            'sort'          => '排序',
            'salary'        => '薪资待遇',
            'is_top'        => '是否置顶',
            'top_end_time'  => '置顶结束时间',
            'position'      => '所属职位',
            'release_type'  => '招聘类型',
            'release'       => '发布方',
        ];
        //导出信息
        util_csv::export_csv_2($list, $title, '招聘信息.csv');die;
    }



    /**
     * Comment: 简历列表
     * Author: zzw
     * Date: 2020/12/4 14:02
     */
    public function resumeList(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $search    = $_GPC['search'] ? : '';//真实姓名|手机号
        $jobType   = $_GPC['job_type'] ? : 0;//工作类型：1=全职，2=兼职
        $isExport  = $_GPC['export'] ? : '';//是否为导出操作
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if($search) $where .= " AND (name LIKE '%{$search}%' OR phone LIKE '%{$search}%') ";
        if($jobType > 0) $where .= " AND job_type = {$jobType} ";
        //条件生成
        $areaName = tablename(PDO_NAME.'area');
        $field = "id,name,phone,avatar,gender,work_status,job_type,
        CASE WHEN experience_label_id > 0 THEN (SELECT title FROM ".tablename(PDO_NAME.'recruit_label')." WHERE id = experience_label_id ) 
             ELSE '无经验'
        END as experience,
        (SELECT title FROM ".tablename(PDO_NAME.'recruit_label')." WHERE id = education_label_id ) as education,
        concat(expect_salary_min,
        CASE WHEN job_type = 1 THEN '元/月'
             ELSE '元/天'
        END,' ~ ',expect_salary_max,
        CASE WHEN job_type = 1 THEN '元/月'
             ELSE '元/天'
        END) as expect_salary,create_time,
        concat(
        (SELECT name FROM ".$areaName." WHERE id = expect_work_province ),
        CASE WHEN expect_work_city > 0 THEN concat('.',(SELECT name FROM ".$areaName." WHERE id = expect_work_city ))
             ELSE ''
        END,
        CASE WHEN expect_work_city > 0 AND expect_work_area > 0 THEN concat('.',(SELECT name FROM ".$areaName." WHERE id = expect_work_area ))
             ELSE ''
        END) as expect_work_address";
        $sql = " SELECT {$field} FROM ".tablename(PDO_NAME."recruit_resume");
        $order = ' ORDER BY create_time DESC,id DESC';
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        //列表获取并且处理
        if($isExport == 'export'){
            //导出操作
            $list = pdo_fetchall($sql.$where.$order);
            if(is_array($list) && count($list) > 0) Recruit::exportResumeList($list);
            else wl_message('暂无可导出信息');
        }else{
            //查看操作
            $list = pdo_fetchall($sql.$where.$order.$limit);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('resume/index');
    }
    /**
     * Comment: 简历查看
     * Author: zzw
     * Date: 2020/12/4 17:08
     */
    public function resumeSee(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] ? : '';
        $isExport  = $_GPC['export'] ? : '';//是否为导出操作
        //信息获取并且处理
        $info = pdo_get(PDO_NAME."recruit_resume",['id'=>$id]);
        //判断是否为导出信息
        if($isExport == 'export') Recruit::exportResumeInfo($info);
        //上岗状态
        switch ($info['work_status']){
            case 1:$info['work_status'] = '随时上岗';break;
            case 2:$info['work_status'] = '一周之内';break;
            case 3:$info['work_status'] = '一月之内';break;
            case 4:$info['work_status'] = '考虑中';break;
            case 5:$info['work_status'] = '无换岗意向';break;
        }
        //获取工作经验信息
        if($info['experience_label_id'] > 0) $info['experience'] = pdo_getcolumn(PDO_NAME."recruit_label",['id'=>$info['experience_label_id']],'title');
        else  $info['experience'] = '无工作经验';
        //最高学历
        $info['education'] = pdo_getcolumn(PDO_NAME."recruit_label",['id'=>$info['education_label_id']],'title');
        //通过出生日期获取年龄信息
        $info['age'] = Recruit::getAge(date("Y-m-d",$info['birth_time']));
        //求职意向
        $expectPositionIds = explode(',',$info['expect_position']);
        $expectPosition = pdo_getall(PDO_NAME."recruit_position",['id IN'=>$expectPositionIds],['title']);
        $info['expect_position'] = array_column($expectPosition,'title');
        //期望工作区域
        $info['expect_work_province'] = pdo_getcolumn(PDO_NAME."area",['id'=>$info['expect_work_province']],'name');
        $info['expect_work_city'] = pdo_getcolumn(PDO_NAME."area",['id'=>$info['expect_work_city']],'name');
        $info['expect_work_area'] = pdo_getcolumn(PDO_NAME."area",['id'=>$info['expect_work_area']],'name');
        //工作经历
        $info['work_experience'] = unserialize($info['work_experience']);
        //教育经历
        $info['educational_experience'] = unserialize($info['educational_experience']);
        //删除多余的信息
        unset($info['id'],$info['uniacid'],$info['aid'],$info['experience_label_id'],$info['education_label_id'],$info['birth_time']);

        include wl_template('resume/see');
    }
    /**
     * Comment: 删除简历信息
     * Author: zzw
     * Date: 2021/1/6 11:41
     */
    public function resumeDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? : [];
        pdo_delete(PDO_NAME."recruit_resume",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }


    /**
     * Comment: 行业职位三级联动 —— 改变上级行业时获取子行业信息
     * Author: zzw
     * Date: 2020/12/1 15:41
     */
    public function getSubIndustry(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR wl_json(0,'参数错误，请刷新重试');
        //列表信息获取
        $list = $twoOne = Recruit::getIndustryList(['pid'=>$id],['id','title']);//子行业信息列表

        wl_json(1,'子行业列表信息',$list);
    }
    /**
     * Comment: 行业职位三级联动 —— 改变子行业时获取职位信息
     * Author: zzw
     * Date: 2020/12/1 15:54
     */
    public function getPosition(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR wl_json(0,'参数错误，请刷新重试');
        //列表信息获取
        $list = $twoOne = Recruit::getPositionList(['industry_id'=>$id],['id','title']);//子行业信息列表

        wl_json(1,'子行业列表信息',$list);
    }
    /**
     * Comment: 求职招聘基本设置信息
     * Author: zzw
     * Date: 2020/12/3 17:15
     */
    public function setEdit(){
        global $_W,$_GPC;
        $name = 'recruit_set';
        if($_W['ispost']){
            $data = $_GPC['data'];

            Setting::agentsetting_save($data,$name);
            wl_message('设置成功！' , web_url('recruit/recruitment/setEdit') , 'success');
        }
        //获取已存在的设置信息
        $info =  Setting::agentsetting_read($name);

        include wl_template('set');
    }




}