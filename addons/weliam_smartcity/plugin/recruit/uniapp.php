<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 求职招聘接口管理
 * Author: zzw
 * Class RecruitModuleUniapp
 */
class RecruitModuleUniapp extends Uniapp
{
    /**
     * Comment: 招聘信息列表
     * Author: zzw
     * Date: 2020/12/11 10:24
     */
    public function homeList()
    {
        global $_W,$_GPC;
        //参数获取
        $page            = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex       = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart       = $page * $pageIndex - $pageIndex;
        $lng             = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度  104.0091133118
        $lat             = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度  30.5681964123
        $status          = !empty($_GPC['status']) ? $_GPC['status'] : 4;
        $is_total        = $_GPC['is_total'] ? : 0;//0=不获取总页数；1=获取总页数
        $title           = $_GPC['title'] ? : '';//职位标题
        $industryPid     = $_GPC['industry_pid'] ? : 0;//上级行业id
        $industryId      = $_GPC['industry_id'] ? : 0;//子行业id
        $positionId      = $_GPC['position_id'] ? : 0;//职位id
        $recruitmentType = $_GPC['recruitment_type'] ? : 0;//招聘类型:1=个人招聘,2=企业招聘
        $releaseSid      = $_GPC['release_sid'] ? : 0;//发布企业id
        $releaseMid      = $_GPC['release_mid'] ? : 0;//发布企业id
        $jobType         = $_GPC['job_type'] ? : 0;//工作类型：1=全职，2=兼职
        $salaryMin       = $_GPC['salary_min'] ? : 0;//最低薪资
        $salaryMax       = $_GPC['salary_max'] ? : 0;//最高薪资
        $areaId          = $_GPC['area_id'] ? : 0;//期望工作区域
        $isPrivate       = $_GPC['is_private'] ? : 0;//是否私有查询。0=不是（判断状态），1=是（不判断状态）
        $experience      = $_GPC['work_experience'] ? : 0;//工作经验
        $educational     = $_GPC['educational_experience'] ? : 0;//最高学历
        //获取默认排序方式
        $set  = Setting::agentsetting_read("pluginlist");
        $sort = $_GPC['sort'] ? : $set['zpsort'];
        //生成基本查询条件
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if (count(explode(',',$status)) > 1 && $isPrivate == 0) $where .= " AND status IN ({$status}) ";
        else if ($status > 0 && $isPrivate == 0) $where .= " AND status = {$status} ";
        if ($industryPid > 0) $where .= " AND industry_pid = {$industryPid} ";
        if ($industryId > 0) $where .= " AND industry_id = {$industryId} ";
        if ($positionId > 0) $where .= " AND position_id = {$positionId} ";
        if ($recruitmentType > 0) $where .= " AND recruitment_type = {$recruitmentType} ";
        if ($jobType > 0) $where .= " AND job_type = {$jobType} ";
        if ($salaryMin > 0) $where .= " AND ((full_salary_min >= {$salaryMin} && job_type = 1) OR (job_type = 2 && part_salary >= {$salaryMin})) ";
        if ($salaryMax > 0) $where .= " AND ((full_salary_max <= {$salaryMax} && job_type = 1) OR (job_type = 2 && part_salary <= {$salaryMax})) ";
        if ($areaId > 0) $where .= " AND (work_province = {$areaId} OR work_city = {$areaId} OR work_area = {$areaId}) ";
        if ($releaseSid > 0) $where .= " AND release_sid = {$releaseSid} ";
        if ($releaseMid > 0) $where .= " AND release_mid = {$releaseMid} ";
        if ($experience > 0) $where .= " AND experience_label_id = {$experience} ";
        if ($educational > 0) $where .= " AND education_label_id = {$educational} ";
        //生成其他关联查询条件
        if($title){
            $storeList = pdo_fetchall("SELECT id FROM ".tablename(PDO_NAME."merchantdata")."WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 2 AND enabled = 1 AND storename LIKE '%{$title}%'");
            $memberList = pdo_fetchall("SELECT id FROM ".tablename(PDO_NAME."member")."WHERE uniacid = {$_W['uniacid']} AND nickname LIKE '%{$title}%'");

            if (count($storeList) > 0) {
                $storeIds = implode(array_column($storeList,'id'),',');
            }else{
                $storeIds = '0';
            }
            if (count($memberList) > 0) {
                $memberIds = implode(array_column($memberList,'id'),',');
            }else{
                $memberIds = '0';
            }
            $where    .= " AND ( ((recruitment_type = 1 AND release_mid IN ({$memberIds}) )  OR (recruitment_type = 2 AND release_sid IN ({$storeIds}))) OR title LIKE '%{$title}%' )";
        }else{
            $storeList = pdo_getall(PDO_NAME."merchantdata",[
                'uniacid' => $_W['uniacid'],
                'aid'     => $_W['aid'],
                'status'  => 2,
                'enabled' => 1
            ],['id']);
            if (count($storeList) > 0) {
                $storeIds = implode(array_column($storeList,'id'),',');
                $where    .= " AND (recruitment_type = 1 OR (recruitment_type = 2 AND release_sid IN ({$storeIds})))";
            }
        }
        //wl_debug($where);
        //生成排序条件 1=推荐排序  2=浏览量  3=发布时间  4=距离排序
        $order = " ORDER BY is_top DESC";
        switch ($sort) {
            case 1:
                $order .= ",sort DESC,id DESC ";
                break;//推荐排序
            case 2:
                $order .= ",pv DESC,id DESC ";
                break;//浏览量
            case 3:
                $order .= ",create_time DESC,id DESC ";
                break;//发布时间
            case 4:
                $order .= ",distances ASC,id DESC ";
                break;//距离排序
        }
        //sql语句生成
        $limit     = " LIMIT {$pageStart},{$pageIndex} ";
        $distances = "ROUND(CASE 
                            WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.138 * 2 * ASIN(
                                    SQRT(
                                        POW(SIN(({$lat} * PI() / 180 - work_lat * PI() / 180) / 2),2) +
                                            COS({$lat} * PI() / 180) * COS(work_lat * PI() / 180) *
                                            POW(SIN(({$lng} * PI() / 180 - work_lng * PI() / 180) / 2),2)
                                        )
                                    ) * 1000
                                ) 
                            ELSE ''
                        END)";
        $field     = "{$distances} as distances,id,title,recruitment_type,release_mid,release_sid,job_type,full_type,full_salary_min,full_salary_max,
        welfare,part_type,part_salary,work_province,work_city,work_area,create_time,is_top,status,industry_pid,industry_id,position_id,reason";
        $sql       = "SELECT {$field} FROM ".tablename(PDO_NAME."recruit_recruit");
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach ($list as &$item) {
            //信息处理
            $item = Recruit::handleRecruitInfo($item);
            //私有查询 获取订单信息
            if($isPrivate == 1 && $item['status'] == 1){
                //fightstatus=1   代表这是招聘信息发布生成的订单
                $order = pdo_get(PDO_NAME."order",['fkid'=>$item['id'],'plugin'=>'recruit','fightstatus'=>1],['id','plugin']);
                $item['order_id'] = $order['id'];
                $item['order_plugin'] = $order['plugin'];
            }
            //删除多余的信息
            unset($item['release_mid'],$item['release_sid'],$item['full_type'],$item['full_salary_min'],$item['full_salary_max'],$item['welfare'],$item['part_type'],$item['part_salary'],$item['part_settlement'],$item['work_province'],$item['work_city'],$item['work_area'],$item['create_time'],$item['province'],$item['city'],$item['area'],$item['job_type'],$item['distances']);
        }
        //获取总页数
        if ($is_total == 1) {
            $totalSql      = str_replace($field,'count(*)',$sql);
            $total         = pdo_fetchcolumn($totalSql.$where);
            $data['total'] = ceil($total / $pageIndex);
            $data['list']  = $list;
            $this->renderSuccess('招聘信息列表',$data);
        }
        $this->renderSuccess('招聘信息列表',$list);
    }
    /**
     * Comment: 获取招聘详情信息
     * Author: zzw
     * Date: 2020/12/11 15:20
     */
    public function recruitDesc()
    {
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] or $this->renderError('请求错误，参数非法');
        //信息获取
        $field = [
            'status',
            'title',
            'recruitment_type',
            'release_mid',
            'release_sid',
            'job_type',
            'full_type',
            'full_salary_min',
            'full_salary_max',
            'welfare',
            'part_type',
            'part_salary',
            'work_province',
            'work_city',
            'work_area',
            'create_time',
            'job_description',
            'contacts',
            'contact_phone',
            'gender',
            'age_min',
            'age_max',
            'education_label_id',
            'experience_label_id',
            'pv',
            'fictitious_pv',
            'position_id',
            'work_address',
            'work_lng',
            'work_lat',
            'people_number',
            'industry_pid',
            'industry_id'
        ];
        $info  = pdo_get(PDO_NAME."recruit_recruit",['id' => $id],$field);
        if($info['status'] != 4){
            $this->renderError('招聘已结束或未开始');
        }
        $info  = Recruit::handleRecruitInfo($info);
        //获取推荐招聘信息
        $info['recommend'] = Recruit::getRecruitRecommend(intval($id),intval($info['position_id']),intval($info['work_province']),4);
        //浏览量增加
        $pv = $info['pv'] + 1;
        pdo_update(PDO_NAME."recruit_recruit",['pv' => $pv],['id' => $id]);
        //修改浏览量为当前浏览量 + 虚拟浏览量
        $info['pv'] = $pv + $info['fictitious_pv'];
        //判断是否已经投递简历
        $info['is_delivery'] = 0;//未投递
        if ($_W['mid']) {
            $isHave = pdo_get(PDO_NAME."recruit_submit",['mid' => $_W['mid'],'recruit_id' => $id]);
            if ($isHave) $info['is_delivery'] = 1;//已投递简历
        }
        //获取招聘详情页面的免责声明
        $set                      = Setting::agentsetting_read('recruit_set');
        $info['desc_disclaimers'] = $set['desc_disclaimers'] ? : '';
        //删除多余的信息
        unset($info['position_id'],$info['release_mid'],$info['full_type'],$info['full_salary_min'],$info['full_salary_max'],$info['welfare'],
            $info['part_type'],$info['part_salary'],$info['part_settlement'],$info['work_province'],$info['work_city'],$info['work_area'],
            $info['create_time'],$info['province'],$info['city'],$info['area'],$info['job_type'],$info['distances'],$info['industry_pid'],
            $info['industry_id'],$info['gender'],$info['age_min'],$info['age_max'],$info['education_label_id'],$info['experience_label_id'],$info['fictitious_pv']);

        $this->renderSuccess('招聘详情',$info);
    }
    /**
     * Comment: 简历信息列表
     * Author: zzw
     * Date: 2020/12/11 17:11
     */
    public function resumeList()
    {
        global $_W,$_GPC;
        //参数获取
        $page        = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex   = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart   = $page * $pageIndex - $pageIndex;
        $is_total    = $_GPC['is_total'] ? : 0;//0=不获取总页数；1=获取总页数
        $name        = $_GPC['name'] ? : '';//真实姓名
        $industryPid = $_GPC['industry_pid'] ? : 0;//上级行业id
        $industryId  = $_GPC['industry_id'] ? : 0;//子行业id
        $positionId  = $_GPC['position_id'] ? : 0;//职位id
        $jobType     = $_GPC['job_type'] ? : 0;//工作类型：1=全职，2=兼职
        $salaryMin   = $_GPC['salary_min'] ? : 0;//最低薪资
        $salaryMax   = $_GPC['salary_max'] ? : 0;//最高薪资
        $areaId      = $_GPC['area_id'] ? : 0;//期望工作区域
        $experience  = $_GPC['work_experience'] ? : 0;//工作经验
        $educational = $_GPC['educational_experience'] ? : 0;//最高学历
        $sort        = $_GPC['sort'] ? : 1;
        $recruitId   = $_GPC['recruit_id'] ? : 0;//招聘信息id
        //生成基本查询条件AND aid = {$_W['aid']}
        $where = " WHERE uniacid = {$_W['uniacid']} ";
        if ($name) $where .= " AND name LIKE '%{$name}%' ";
        if ($industryPid > 0) $where .= " AND industry_pid = {$industryPid} ";
        if ($industryId > 0) $where .= " AND industry_id = {$industryId} ";
        if ($positionId > 0) $where .= " AND INSTR(CONCAT(expect_position),CONCAT({$positionId})) ";
        if ($jobType > 0) $where .= " AND job_type = {$jobType} ";
        if ($salaryMin > 0) $where .= " AND expect_salary_min >= {$salaryMin} ";
        if ($salaryMax > 0) $where .= " AND expect_salary_max <= {$salaryMax} ";
        if ($areaId > 0) $where .= " AND (expect_work_province = {$areaId} OR expect_work_city = {$areaId} OR expect_work_area = {$areaId}) ";
        if ($experience > 0) $where .= " AND experience_label_id = {$experience} ";
        if ($educational > 0) $where .= " AND education_label_id = {$educational} ";
        //如果存在招聘信息id  则不获取已向改招聘信息提交简历的简历信息
        if ($recruitId > 0) {
            $recruit = pdo_getall(PDO_NAME."recruit_submit",[
                'uniacid'    => $_W['uniacid'],
                'recruit_id' => $recruitId
            ],['resume_id']);
            if (is_array($recruit) && count($recruit) > 0) {
                $resumeIds = array_column($recruit,'resume_id');
                $resumeIds = implode($resumeIds,',');
                $where     .= " AND id NOT IN ({$resumeIds}) ";
            }
        }
        //生成排序条件 1=推荐排序  2=浏览量  3=发布时间
        switch ($sort) {
            case 1:
                $order = "  ";
                break;//推荐排序
            case 2:
                $order = " ORDER BY pv DESC,id DESC ";
                break;//浏览量
            case 3:
                $order = " ORDER BY create_time DESC,id DESC ";
                break;//发布时间
        }
        //sql语句生成
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $field = "id,name,phone,avatar,gender,work_status,experience_label_id,education_label_id,birth_time,expect_position,job_type,expect_salary_min,expect_salary_max,expect_work_province,expect_work_city,expect_work_area";
        $sql   = "SELECT {$field} FROM ".tablename(PDO_NAME."recruit_resume");
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach ($list as &$item) {
            $item = Recruit::handleResumeInfo($item);
            //处理姓名
            $item['name'] = mb_substr($item['name'],0,1,'utf-8').'**';
            //处理电话
            $phoneLen      = strlen($item['phone']);
            $item['phone'] = mb_substr($item['phone'],0,3,'utf-8').'***'.mb_substr($item['phone'],$phoneLen - 4,4,'utf-8');
            //删除多余的信息
            unset($item['work_status'],$item['experience_label_id'],$item['education_label_id'],$item['birth_time'],$item['expect_salary_min'],$item['expect_salary_max'],$item['expect_work_province'],$item['expect_work_city'],$item['expect_work_area'],$item['expect_position'],$item['province'],$item['city'],$item['area']);
        }
        //获取总页数
        if ($is_total == 1) {
            $totalSql      = str_replace($field,'count(*)',$sql);
            $total         = pdo_fetchcolumn($totalSql.$where);
            $data['total'] = ceil($total / $pageIndex);
            $data['list']  = $list;
            $this->renderSuccess('简历信息列表',$data);
        }
        $this->renderSuccess('简历信息列表',$list);
    }
    /**
     * Comment: 简历详情
     * Author: zzw
     * Date: 2020/12/11 17:55
     */
    public function resumeDesc()
    {
        global $_W,$_GPC;
        //参数获取
        $id = $_GPC['id'] or $this->renderError('请求错误，参数非法');//简历id
        $recruitId = $_GPC['recruit_id'] ? : 0;//招聘信息id
        //信息获取
        $field = [
            'mid',
            'name',
            'phone',
            'avatar',
            'gender',
            'job_type',
            'work_status',
            'experience_label_id',
            'education_label_id',
            'birth_time',
            'self_evaluation',
            'expect_position',
            'expect_salary_min',
            'expect_salary_max',
            'expect_work_province',
            'expect_work_city',
            'expect_work_area',
            'work_experience',
            'educational_experience',
            'create_time',
            'pv',
        ];
        $info  = pdo_get(PDO_NAME."recruit_resume",['id' => $id],$field);
        $info  = Recruit::handleResumeInfo($info);
        //删除
        unset($info['work_status'],$info['experience_label_id'],$info['education_label_id'],$info['birth_time'],$info['expect_salary_min'],$info['expect_salary_max'],$info['expect_work_province'],$info['expect_work_city'],$info['expect_work_area'],$info['expect_position'],$info['province'],$info['city'],$info['area'],$info['create_time']);
        //浏览量增加
        $info['pv'] = intval($info['pv']) + 1;
        pdo_update(PDO_NAME."recruit_resume",['pv' => $info['pv']],['id' => $id]);
        //修改已投递简历状态
        if ($recruitId > 0) pdo_update(PDO_NAME."recruit_submit",['status' => 1],[
            'resume_id'  => $id,
            'recruit_id' => $recruitId,
            'status'     => 0
        ]);
        //根据是否已经收到简历 显示姓名和电话信息
        $info['is_received_resume'] = 0;//是否收到简历0=未收到，1=已经收到
        $isHave                     = pdo_get(PDO_NAME."recruit_submit",[
            'resume_id'  => $id,
            'recruit_id' => $recruitId
        ]);
        if (!$isHave) {
            //未收到简历
            $info['name']               = mb_substr($info['name'],0,1,'utf-8').'**';//处理姓名
            $phoneLen                   = strlen($info['phone']);//处理电话
            $info['phone']              = mb_substr($info['phone'],0,3,'utf-8').'***'.mb_substr($info['phone'],$phoneLen - 4,4,'utf-8');
            $info['is_received_resume'] = 1;
        }
        $this->renderSuccess('简历详情',$info);
    }
    /**
     * Comment: 企业信息列表
     * Author: zzw
     * Date: 2020/12/14 11:30
     */
    public function enterpriseList()
    {
        global $_W,$_GPC;
        //参数获取
        $page              = $_GPC['page'] ? $_GPC['page'] : 1;
        $pageIndex         = $_GPC['page_index'] ? $_GPC['page_index'] : 10;
        $pageStart         = $page * $pageIndex - $pageIndex;
        $lng               = $_GPC['lng'] ? $_GPC['lng'] : 0;//用户当前所在经度  104.0091133118
        $lat               = $_GPC['lat'] ? $_GPC['lat'] : 0;//用户当前所在纬度  30.5681964123
        $is_total          = $_GPC['is_total'] ? : 0;//0=不获取总页数；1=获取总页数
        $areaId            = $_GPC['area_id'] ? : 0;//位置
        $recruitScaleId    = $_GPC['recruit_scale_id'] ? : 0;//规模
        $recruitNatureId   = $_GPC['recruit_nature_id'] ? : 0;//性质
        $recruitIndustryId = $_GPC['recruit_industry_id'] ? : 0;//行业 仅上级行业
        //获取默认排序方式
        $set  = Setting::agentsetting_read("pluginlist");
        $sort = $_GPC['sort'] ? : $set['sjsort'];
        //生成基本查询条件
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} AND status = 2 AND enabled = 1 AND recruit_switch = 1 ";
        if ($areaId) $where .= " AND (provinceid = {$areaId} OR areaid = {$areaId} OR distid = {$areaId}) ";
        if ($recruitScaleId) $where .= " AND recruit_scale_id = {$recruitScaleId} ";
        if ($recruitNatureId) $where .= " AND recruit_nature_id = {$recruitNatureId} ";
        if ($recruitIndustryId) $where .= " AND recruit_industry_id = {$recruitIndustryId} ";
        //生成排序条件  1=创建时间，2=店铺距离，3=默认设置，4=浏览人气
        switch ($sort) {
            case 1:
                $order = " ORDER BY createtime DESC,id DESC ";
                break;//创建时间
            case 2:
                $order = " ORDER BY distances ASC,id DESC ";
                break;//店铺距离
            case 3:
                $order = " ORDER BY listorder DESC,id DESC ";
                break;//默认设置
            case 4:
                $order = " ORDER BY pv DESC,id DESC ";
                break;//浏览人气
        }
        //sql语句生成
        $limit     = " LIMIT {$pageStart},{$pageIndex} ";
        $distances = "ROUND(CASE 
                            WHEN {$lat} > 0 AND {$lng} > 0 THEN ROUND(6378.138 * 2 * ASIN(
                                    SQRT(
                                        POW(SIN(({$lat} * PI() / 180 - lat * PI() / 180) / 2),2) +
                                            COS({$lat} * PI() / 180) * COS(lat * PI() / 180) *
                                            POW(SIN(({$lng} * PI() / 180 - lng * PI() / 180) / 2),2)
                                        )
                                    ) * 1000
                                ) 
                            ELSE ''
                        END)";
        $field     = "{$distances} as distances,id,logo,storename,recruit_nature_id,recruit_scale_id,recruit_industry_id,provinceid,areaid,distid";
        $sql       = "SELECT {$field} FROM ".tablename(PDO_NAME."merchantdata");
        //列表信息获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach ($list as $index => &$item) {
            $item = Recruit::handleEnterpriseInfo($item);
            //删除多余的数据信息
            unset($item['distances'],$item['recruit_nature_id'],$item['recruit_scale_id'],$item['recruit_industry_id'],$item['provinceid'],$item['areaid'],$item['distid']);
        }
        //获取总页数
        if ($is_total == 1) {
            $totalSql      = str_replace($field,'count(*)',$sql);
            $total         = pdo_fetchcolumn($totalSql.$where);
            $data['total'] = ceil($total / $pageIndex);
            $data['list']  = $list;
            $this->renderSuccess('企业信息列表',$data);
        }
        $this->renderSuccess('企业信息列表',$list);
    }
    /**
     * Comment: 企业详情
     * Author: zzw
     * Date: 2020/12/14 11:51
     */
    public function enterpriseDesc()
    {
        global $_W,$_GPC;
        //参数获取
        $id = $_GPC['id'] or $this->renderError('请求错误，参数非法');
        //信息获取
        $field = [
            'id',
            'logo',
            'storename',
            'recruit_nature_id',
            'recruit_scale_id',
            'recruit_industry_id',
            'provinceid',
            'areaid',
            'distid',
            'introduction',
            'lng',
            'lat',
            'recruit_adv',
            'address'
        ];
        $info  = pdo_get(PDO_NAME."merchantdata",['id' => $id],$field);
        $info  = Recruit::handleEnterpriseInfo($info);
        //删除多余的信息
        unset($info['distances'],$info['recruit_nature_id'],$info['recruit_scale_id'],$info['recruit_industry_id'],$info['provinceid'],$info['areaid'],$info['distid']);
        //浏览量增加
        $info['pv'] = intval($info['pv']) + 1;
        pdo_update(PDO_NAME."recruit_resume",['pv' => $info['pv']],['id' => $id]);
        $this->renderSuccess('企业详情',$info);
    }
    /**
     * Comment: 修改招聘状态（招聘结束|再次招聘）
     * Author: zzw
     * Date: 2021/1/12 16:36
     */
    public function recruitChangeStatus()
    {
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] or $this->renderError('参数错误，请刷新重试!');
        $status = $_GPC['status'] ? : 5;//招聘状态：1=待付款，2=审核中，3=未通过，4=招聘中，5=已结束
        //修改招聘信息状态
        pdo_update(PDO_NAME."recruit_recruit",['status' => $status],['id' => $id]);
        $this->renderSuccess('操作成功');
    }
    /**
     * Comment: 招聘信息发布/编辑
     * Author: zzw
     * Date: 2020/12/14 16:26
     */
    public function editRecruit()
    {
        global $_W,$_GPC;
        //参数信息获取
        $id   = intval($_GPC['id']) ? : 0;//招聘信息id   存在即是编辑
        $type = $_GPC['type'] ? : 'get';//请求类型：get=获取信息，post为储存数据
        $set  = Setting::agentsetting_read('recruit_set');
        //根据类型进行对应的操作
        if ($type == 'post') {
            //储存数据  根据是否存在id判断是添加信息还是修改信息
            $data = json_decode(html_entity_decode($_GPC['data']),true);
            $sid  = $_GPC['sid'] ? : 0;
            //判断内容是否完善
            if (!$data['title']) $this->renderError('请输入职位名称');
            if ($data['job_type'] == 1 && $data['full_type'] == 2 && !$data['full_salary_min']) $this->renderError('请输入最低薪资');
            if ($data['job_type'] == 1 && $data['full_type'] == 2 && !$data['full_salary_max']) $this->renderError('请输入最高薪资');
            if ($data['job_type'] == 2 && !$data['part_salary']) $this->renderError('请输入薪资金额');
            if (!$data['work_address'] || !$data['work_lng'] || !$data['work_lat']) $this->renderError('请选择工作地址');
            if (!$data['contacts']) $this->renderError('请输入联系人');
            if (!$data['contact_phone']) $this->renderError('请输入联系方式');
            if (!$data['age_min'] || !$data['age_max']) $this->renderError('请输入年龄要求');
            //信息处理
            if (is_array($data['welfare'])) $data['welfare'] = implode(',',$data['welfare']);
            //判断是否需要审核
            if ($set['is_examine'] == 1) $data['status'] = 2;//需要审核 待审核
            else $data['status'] = 4;//不需要审核  招聘中
            //根据是否存在id 判断是添加操作还是修改操作
            if ($id > 0) {
                //信息修改
                $res = pdo_update(PDO_NAME."recruit_recruit",$data,['id' => $id]);
                if ($res) $this->renderSuccess('编辑成功'); else $this->renderError('编辑失败');
            } else {
                //信息添加  招聘类型 recruitment_type:1=个人招聘,2=企业招聘
                $data['uniacid']     = $_W['uniacid'];
                $data['aid']         = $_W['aid'];
                $data['release_mid'] = $data['recruitment_type'] == 1 ? $_W['mid'] : 0;
                $data['release_sid'] = $data['recruitment_type'] == 2 ? $sid : 0;
                $data['create_time'] = time();
                $res                 = pdo_insert(PDO_NAME."recruit_recruit",$data);
                if ($res) {
                    $id = pdo_insertid();
                    //修改排序信息为当前id
                    pdo_update(PDO_NAME."recruit_recruit",['sort' => $id],['id' => $id]);
                    //判断是否需要付费  招聘类型:1=个人招聘,2=企业招聘
                    $releaseSituation = Recruit::getReleaseSituation($data['recruitment_type'],$data['release_mid'],$data['release_sid']);
                    if ($releaseSituation['is_pay'] > 0) {
                        //需要支付  修改招聘信息状态为待支付
                        pdo_update(PDO_NAME."recruit_recruit",['status' => 1],['id' => $id]);
                        //生成订单
                        $orderdata = [
                            'uniacid'     => $data['uniacid'],
                            'mid'         => $_W['mid'],
                            'aid'         => $data['aid'],
                            'fkid'        => $id,
                            'createtime'  => time(),
                            'orderno'     => createUniontid(),
                            'price'       => $releaseSituation['price'],
                            'num'         => 1,
                            'plugin'      => 'recruit',
                            'payfor'      => 'recruitOrder',
                            'goodsprice'  => $releaseSituation['price'],
                            'fightstatus' => 1,
                            'name'        => $data['contacts'],
                            'mobile'      => $data['contact_phone']
                        ];
                        pdo_insert(PDO_NAME.'order',$orderdata);
                        $orderid = pdo_insertid();
                        if (empty($orderid)) {
                            $this->renderError('生成订单失败，请刷新重试');
                        } else {
                            $this->renderSuccess('发布成功',['status' => 1,'type' => 'recruit','orderid' => $orderid]);
                        }
                    } else {
                        //判断 需要审核，给代理商管理员发送审核消息
                        if ($data['status'] == 2) {
                            $first   = '招聘审核通知';
                            $content = "用户{$_W['wlmember']['nickname']}在".date('m月d日H时i分',$data['create_time'])."发布了招聘信息";//业务内容
                            $status  = '待审核';//处理结果
                            $remark  = '请尽快审核！';//备注信息
                            $time    = time();//操作时间(时间戳)
                            News::noticeAgent('recruit_examine',$_W['aid'],$first,'招聘审核通知',$content,$status,$remark,$time);
                        }
                        $this->renderSuccess('发布成功',['status' => 0,'id' => $id]);
                    }
                } else {
                    $this->renderError('发布失败');
                }
            }
        } else {
            $info = [];
            if ($id) {
                //修改信息准备
                $info = pdo_get(PDO_NAME."recruit_recruit",['id' => $id]);
                //处理福利标签信息
                $info['welfare'] = $info['welfare'] ? explode(',',$info['welfare']) : [];
                //行业&职位信息处理
                $info['show_industry_pid'] = pdo_get(PDO_NAME."recruit_industry",['id' => $info['industry_pid']],[
                    'id',
                    'title'
                ]);
                $info['show_industry_id']  = pdo_get(PDO_NAME."recruit_industry",['id' => $info['industry_id']],[
                    'id',
                    'title'
                ]);
                $info['show_position_id']  = pdo_get(PDO_NAME."recruit_position",['id' => $info['position_id']],[
                    'id',
                    'title'
                ]);
                //删除多余的信息
                unset($info['id'],$info['uniacid'],$info['aid'],$info['create_time'],$info['sort'],$info['pv'],$info['is_top'],$info['top_end_time'],$info['status'],$info['release_mid'],$info['release_sid']);
                //$data['info'] = $info;
            }
            //获取免责声明
            $info['disclaimers'] = htmlspecialchars_decode($set['disclaimers']);
            $this->renderSuccess('获取招聘信息',$info);
        }
    }
    /**
     * Comment: 个人简历编辑
     * Author: zzw
     * Date: 2020/12/14 18:27
     */
    public function editResume()
    {
        global $_W,$_GPC;
        //参数信息获取
        $type = $_GPC['type'] ? : 'get';//请求类型：get=获取信息，post为储存数据
        $info = pdo_get(PDO_NAME."recruit_resume",['uniacid' => $_W['uniacid'],'mid' => $_W['mid']]);//用户简历信息
        //根据type 进行对应的操作
        if ($type == 'post') {
            //添加/编辑信息
            $data = json_decode(html_entity_decode($_GPC['data']),true);
            //判断内容是否完善
            if (!$data['name']) $this->renderError('请输入真实姓名');
            if (!$data['phone']) $this->renderError('请输入联系方式');
            if (!$data['avatar']) $this->renderError('请上传头像');
            if (!$data['expect_position']) $this->renderError('请选择期望职位');
            if (!$data['expect_salary_min'] || !$data['expect_salary_max']) $this->renderError('请输入期望薪资');
            if (!$data['expect_work_province']) $this->renderError('请选择期望工作区域');
            if (!$data['birth_time']) $this->renderError('请选择出生日期');
            //信息处理
            if (is_array($data['expect_position'])) $data['expect_position'] = implode(',',$data['expect_position']);
            if ($data['work_experience']) $data['work_experience'] = serialize($data['work_experience']);
            if ($data['educational_experience']) $data['educational_experience'] = serialize($data['educational_experience']);
            //根据是否存在id 判断是添加操作还是修改操作
            if ($info) {
                //信息修改
                $data['update_time'] = time();
                $res                 = pdo_update(PDO_NAME."recruit_resume",$data,[
                    'uniacid' => $_W['uniacid'],
                    'mid'     => $_W['mid']
                ]);
            } else {
                //信息添加
                $data['uniacid']     = $_W['uniacid'];
                $data['aid']         = $_W['aid'];
                $data['mid']         = $_W['mid'] ? : 0;
                $data['create_time'] = time();
                $data['update_time'] = time();
                $res                 = pdo_insert(PDO_NAME."recruit_resume",$data);
            }
            if ($res) $this->renderSuccess('编辑成功'); else $this->renderError('操作失败');
        } else {
            //获取信息
            if ($info) {
                //头像信息处理
                $info['show_avatar'] = tomedia($info['avatar']);
                //期望职位
                $info['expect_position'] = $info['expect_position'] ? explode(',',$info['expect_position']) : [];
                if ($info['expect_position']) $expectPosition = pdo_getall(PDO_NAME."recruit_position",['id IN' => $info['expect_position']],[
                    'id',
                    'title'
                ]);
                $info['show_expect_position'] = is_array($expectPosition) ? $expectPosition : [];
                //项目经验
                $info['work_experience'] = unserialize($info['work_experience']);
                foreach ($info['work_experience'] as $key => &$val) {
                    $val['start_time'] = date("Y-m-d",$val['start_time']);
                    $val['end_time']   = date("Y-m-d",$val['end_time']);
                }
                $info['work_experience'] = is_array($info['work_experience']) ? $info['work_experience'] : [];
                //教育经历
                $info['educational_experience'] = unserialize($info['educational_experience']);
                foreach ($info['educational_experience'] as $index => &$item) {
                    $item['start_time'] = date("Y-m-d",$item['start_time']);
                    $item['end_time']   = date("Y-m-d",$item['end_time']);
                }
                $info['educational_experience'] = is_array($info['educational_experience']) ? $info['educational_experience'] : [];
                //生日
                $info['birth_time'] = date("Y-m-d",$info['birth_time']);
                //删除多余的信息
                unset($info['id'],$info['uniacid'],$info['aid'],$info['mid'],$info['create_time'],$info['update_time'],$info['pv']);
            }
            $this->renderSuccess('用户简历信息',$info);
        }
    }
    /**
     * Comment: 求职招聘标签信息列表
     * Author: zzw
     * Date: 2020/12/15 9:29
     */
    public function getLabel()
    {
        global $_W,$_GPC;
        //参数信息获取
        $type = $_GPC['type'] ? : '';
        if (!in_array($type,[1,2,3,4,5])) $this->renderError('请求失败，参数非法');//标签类型:1=学历要求,2=职位福利,3=经验标签,4=企业规模,5=企业性质
        $title = [
            1 => '学历要求',
            2 => '职位福利',
            3 => '工作经验',
            4 => '企业规模',
            5 => '企业性质',
        ];
        //获取对应类型的标签信息
        $list = Recruit::getLabelList($type);
        //拼接无限
        $list = array_merge([['id'=>0,'title'=>'不限']],$list);

        $this->renderSuccess($title[$type]."标签列表",$list);
    }
    /**
     * Comment: 投递简历
     * Author: zzw
     * Date: 2020/12/15 10:32
     */
    public function submitResume(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] or $this->renderError("非法请求，招聘信息不存在");
        //判断当前用户是否存在简历
        $resumeId = pdo_getcolumn(PDO_NAME."recruit_resume",[
            'uniacid' => $_W['uniacid'],
            'mid'     => $_W['mid']
        ],'id');//用户简历信息
        if (!$resumeId) $this->renderError("请先完善简历信息！");
        //判断是否已经投递过简历了
        $data     = [
            'uniacid'    => $_W['uniacid'],
            'mid'        => $_W['mid'],
            'resume_id'  => $resumeId,
            'recruit_id' => $id,
        ];
        $isSubmit = pdo_get(PDO_NAME."recruit_submit",$data);
        if (!$isSubmit) {
            //未提交   储存信息，完成建立投递存在
            $data['create_time'] = time();
            $data['status']      = 0;
            pdo_insert(PDO_NAME."recruit_submit",$data);
            //发送模板消息通知招聘方
            $recruit = pdo_get(PDO_NAME."recruit_recruit",['id'=>$id],['recruitment_type','release_mid','release_sid','title','position_id']);
            $positionName = pdo_getcolumn(PDO_NAME."recruit_position",['id'=>$recruit['position_id']],'title');
            $time = time();
            $modelData = [
                'first'   => '您好，有用户投递简历',
                'type'    => '简历投递',//业务类型
                'content' => "用户[{$_W['wlmember']['nickname']}]提交简历",//业务内容，职位：[{$recruit['title']}]。
                'status'  => '待处理',//处理结果
                'time'    => date("Y-m-d H:i:s",$time),//操作时间$store['createtime']
                'remark'  => "职位：[{$positionName}]"
            ];
            //招聘类型:1=个人招聘,2=企业招聘
            if($recruit['recruitment_type'] == 1) TempModel::sendInit('service',$recruit['release_mid'],$modelData,$_W['source']);
            else News::noticeShopAdmin($recruit['release_sid'], $modelData['first'], $modelData['type'], $modelData['content'], $modelData['status'], $modelData['remark'], $time);

            $this->renderSuccess('投递成功');
        } else {
            $this->renderError('请勿重复投递！');
        }
    }
    /**
     * Comment: 获取简历投递信息列表
     * Author: zzw
     * Date: 2020/12/15 11:54
     */
    public function submitResumeList()
    {
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] or $this->renderError("非法请求，招聘信息不存在");//招聘信息id
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        //信息列表获取
        $where = " WHERE a.recruit_id = {$id} AND a.uniacid = {$_W['uniacid']} ";
        $order = " ORDER BY a.create_time DESC,a.id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $field = "b.id,a.id as submit_id,b.name,b.phone,b.avatar,b.gender,b.experience_label_id,b.education_label_id,b.birth_time,
        b.expect_position,b.expect_salary_min,b.expect_salary_max,b.expect_work_province,b.expect_work_city,
        b.expect_work_area,a.create_time,a.status";
        $sql   = "SELECT {$field} FROM ".tablename(PDO_NAME."recruit_submit")." as a RIGHT JOIN ".tablename(PDO_NAME."recruit_resume")." as b ON a.mid = b.mid AND a.resume_id = b.id ";
        $list  = pdo_fetchall($sql.$where.$order.$limit);
        foreach ($list as $index => &$item) {
            $item = Recruit::handleResumeInfo($item);
            //处理时间
            $item['create_time_text'] = date("Y-m-d H:i",$item['create_time']);
            //删除多余的字段
            unset($item['experience_label_id'],$item['education_label_id'],$item['birth_time'],$item['expect_salary_min'],$item['expect_salary_max'],$item['expect_work_province'],$item['expect_work_city'],$item['expect_work_area'],$item['expect_position'],$item['province'],$item['city'],$item['area'],$item['create_time']);
        }
        //获取总页数
        $totalSql      = str_replace($field,'count(*)',$sql);
        $total         = pdo_fetchcolumn($totalSql.$where);
        $data['total'] = ceil($total / $pageIndex);
        $data['list']  = $list;
        $this->renderSuccess("简历投递信息列表",$data);
    }
    /**
     * Comment: 邀请面试
     * Author: zzw
     * Date: 2020/12/16 10:09
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function inviteAnInterview()
    {
        global $_W,$_GPC;
        //参数信息获取
        $resumeId = $_GPC['resume_id'] or $this->renderError('用户不存在');
        $recruitId = $_GPC['recruit_id'] or $this->renderError('招聘信息不存在');
        $interviewTime = $_GPC['interview_time'] or $this->renderError('请确定面试时间');
        $interviewArea = $_GPC['interview_area'] or $this->renderError('请确定面试地点');
        //获取建立投递信息
        $field = "a.id,b.name,a.mid,b.gender,r.title,r.contact_phone";
        $sql   = "SELECT {$field} FROM ".tablename(PDO_NAME."recruit_submit")." as a LEFT JOIN ".tablename(PDO_NAME."recruit_resume")." as b ON a.resume_id = b.id AND a.mid = b.mid LEFT JOIN ".tablename(PDO_NAME."recruit_recruit")." as r ON a.recruit_id = r.id WHERE a.resume_id = {$resumeId} AND a.recruit_id = {$recruitId} ";
        $info  = pdo_fetch($sql);
        //根据是否提交简历进行对应的邀请面试操作
        if (!$info) {
            //用户未提交简历时  生成提交信息并且状态为已邀请面试
            $recruit = pdo_get(PDO_NAME."recruit_recruit",['id' => $recruitId]);//招聘信息
            $resume  = pdo_get(PDO_NAME."recruit_resume",['id' => $resumeId]);//简历信息
            //获取招聘发布方信息  招聘类型:1=个人招聘,2=企业招聘
            if ($recruit['recruitment_type'] == 1) $releaseName = pdo_getcolumn(PDO_NAME."member",['id' => $recruit['release_mid']],'nickname'); else $releaseName = pdo_getcolumn(PDO_NAME."merchantdata",['id' => $recruit['release_sid']],'storename');
            //生成简历投递信息 并且邀请
            $submitData = [
                'uniacid'        => $_W['uniacid'],
                'mid'            => $resume['mid'],
                'resume_id'      => $resumeId,
                'recruit_id'     => $recruitId,
                'create_time'    => time(),
                'status'         => 2,
                'interview_time' => $interviewTime,
                'interview_area' => $interviewArea,
            ];
            pdo_insert(PDO_NAME."recruit_submit",$submitData);
            //判断性别    性别:2=男，3=女
            if ($resume['gender'] == 2) $gender = '先生'; else $gender = '女士';
            //发送模板消息给用户
            $message = [
                'first'   => "尊敬的{$resume['name']}{$gender}",
                'type'    => $recruit['recruitment_type'] == 1 ? '个人邀请面试' : '企业邀请面试',
                //业务类型
                'content' => "我们诚挚的邀请您应聘[{$recruit['title']}]职位，请于".date("Y-m-d H:i",$interviewTime)."在{$interviewArea}进行面试。如有时间冲突请致电：{$recruit['contact_phone']}",
                //业务内容
                'status'  => '面试邀请',
                //处理结果
                'time'    => date('Y-m-d H:i:s',time()),
                //操作时间
                'remark'  => '邀请方：'.$releaseName
            ];
            TempModel::sendInit('service',$resume['mid'],$message,$_W['source']);
        } else {
            //用户已经提交简历  正常邀请面试操作
            pdo_update(PDO_NAME."recruit_submit",[
                'status'         => 2,
                'interview_time' => $interviewTime,
                'interview_area' => $interviewArea
            ],['id' => $info['id']]);
            //判断性别    性别:2=男，3=女
            if ($info['gender'] == 2) $gender = '先生'; else $gender = '女士';
            //发送模板消息给用户
            $message = [
                'first'   => "尊敬的{$info['name']}{$gender}",
                'type'    => '面试通知',
                //业务类型
                'content' => "欢迎您应聘[{$info['title']}]职位，请于".date("Y-m-d H:i",$interviewTime)."在{$interviewArea}进行面试。如有时间冲突请致电：{$info['contact_phone']}",
                //业务内容
                'status'  => '面试邀请',
                //处理结果
                'time'    => date('Y-m-d H:i:s',time()),
                //操作时间
                'remark'  => ''
            ];
            TempModel::sendInit('service',$info['mid'],$message,$_W['source']);
        }
        $this->renderSuccess("邀请成功");
    }
    /**
     * Comment: 面试结束
     * Author: zzw
     * Date: 2021/1/12 11:33
     */
    public function interviewEnd()
    {
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] or $this->renderError('参数错误，请刷新重试');//已投递简历id
        //修改状态
        pdo_update(PDO_NAME."recruit_submit",['status' => 3],['id' => $id]);
        $this->renderSuccess('操作成功');
    }
    /**
     * Comment: 根据类型获取行业列表、子行业列表、职位列表
     * Author: zzw
     * Date: 2020/12/16 10:23
     */
    public function industryList()
    {
        global $_W,$_GPC;
        //参数信息获取
        $type = $_GPC['type'] ? : 1;//行业类型：1=上级行业，2=子行业，3=职位信息
        $pid  = $_GPC['pid'] ? : 0;//上级id   子行业为上级行业的id，职位信息为子行业的id
        //根据类型 获取对应的数据信息
        if ($type == 1) $list = Recruit::getIndustryList(['pid' => 0],['id','title']);//上级行业列表
        else if ($type == 2 && $pid > 0) $list = Recruit::getIndustryList(['pid' => $pid],['id','title']);//子行业列表
        else if ($type == 3 && $pid > 0) $list = Recruit::getPositionList(['industry_id' => $pid],['id','title']);//职位信息
        //消息返回
        $title = [
            1 => '上级行业',
            2 => '子行业',
            3 => '职位信息',
        ];
        $this->renderSuccess($title[$type],is_array($list) ? $list : []);
    }
    /**
     * Comment: 删除招聘信息及相关的求职信息
     * Author: zzw
     * Date: 2020/12/29 10:07
     */
    public function deleteRecruit()
    {
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] or $this->renderError('参数错误，招聘信息不存在！');
        //删除对应的信息
        pdo_delete(PDO_NAME."recruit_submit",['recruit_id' => $id]);//删除求职信息
        pdo_delete(PDO_NAME."recruit_recruit",['id' => $id]);//删除招聘信息
        $this->renderSuccess('删除成功');
    }
    /**
     * Comment: 删除单条求职信息
     * Author: zzw
     * Date: 2020/12/29 10:08
     */
    public function deleteSubmit()
    {
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] or $this->renderError('参数错误，求职信息不存在！');
        //删除对应的信息
        pdo_delete(PDO_NAME."recruit_submit",['id' => $id]);//删除求职信息
        $this->renderSuccess('删除成功');
    }
    /**
     * Comment: 获取我的求职（已投递简历）
     * Author: zzw
     * Date: 2021/1/7 10:03
     */
    public function getMyJobSearch()
    {
        global $_W,$_W;
        //参数信息获取
        global $_W,$_GPC;
        //参数信息获取
        $mid       = $_W['mid'];
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        //信息列表获取
        $where = " WHERE a.mid = {$mid} AND a.uniacid = {$_W['uniacid']} ";
        $order = " ORDER BY a.create_time DESC,a.id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $field = "b.id,b.title,b.recruitment_type,b.release_mid,b.release_sid,b.job_type,b.full_type,b.full_salary_min,b.full_salary_max,
        b.welfare,b.part_type,b.part_salary,b.work_province,b.work_city,b.work_area,b.create_time,b.is_top,a.status as submit_status";
        $sql   = "SELECT {$field} FROM ".tablename(PDO_NAME."recruit_submit")." as a RIGHT JOIN ".tablename(PDO_NAME."recruit_recruit")." as b ON a.recruit_id = b.id";
        $list  = pdo_fetchall($sql.$where.$order.$limit);
        foreach ($list as $index => &$item) {
            $item = Recruit::handleRecruitInfo($item);
            //求职状态   状态：0=未查看，1=已查看，2=已邀请，3=已完成
            switch ($item['submit_status']) {
                case 0:
                    $item['submit_status_text'] = '未查看';
                    break;
                case 1:
                    $item['submit_status_text'] = '已查看';
                    break;
                case 2:
                    $item['submit_status_text'] = '已邀请';
                    break;
                case 3:
                    $item['submit_status_text'] = '已完成';
                    break;
            }
            //删除多余的信息
            unset($item['position_id'],$item['release_mid'],$item['release_sid'],$item['full_type'],$item['full_salary_min'],$item['full_salary_max'],$item['welfare'],$item['part_type'],$item['part_salary'],$item['part_settlement'],$item['work_province'],$item['work_city'],$item['work_area'],$item['create_time'],$item['province'],$item['city'],$item['area'],$item['job_type'],$item['distances']);
        }
        //获取总页数
        $totalSql      = str_replace($field,'count(*)',$sql);
        $total         = pdo_fetchcolumn($totalSql.$where);
        $data['total'] = ceil($total / $pageIndex);
        $data['list']  = $list;
        $this->renderSuccess("简历投递信息列表",$data);
    }
    /**
     * Comment: 简历置顶
     * Author: zzw
     * Date: 2021/1/12 14:36
     */
    public function recruitTop()
    {
        global $_W,$_GPC;
        //参数信息获取
        $type = $_GPC['type'] ? : 'get';//请求类型：get=获取数据，post=提交数据
        $set  = Setting::agentsetting_read('recruit_set');
        if ($type == 'get') {
            //获取置顶规则列表
            $this->renderSuccess('置顶信息列表',$set['top_rule']);
        } else {
            //提交置顶信息
            $recruitId = $_GPC['recruit_id'] or $this->renderError('参数错误，请刷新重试!');
            $topDay = $_GPC['top_day'] or $this->renderError('请选择置顶方式！');
            $topPrice = sprintf("%.2f",$_GPC['top_price']) or $this->renderError('请选择置顶方式！');
            //获取并且判断置顶总数量
            $totalTop = pdo_count(PDO_NAME."recruit_recruit",[
                'is_top'  => 1,
                'uniacid' => $_W['uniacid'],
                'aid'     => $_W['aid']
            ]);
            if ($totalTop >= $set['top_number'] && $set['top_number'] > 0) $this->renderError('置顶失败，置顶数量已达上限！');
            //获取招聘信息
            $recruit = pdo_get(PDO_NAME."recruit_recruit",['id' => $recruitId]);
            //置顶成功 生成置顶订单
            $orderdata = [
                'uniacid'     => $recruit['uniacid'],
                'mid'         => $_W['mid'],
                'aid'         => $recruit['aid'],
                'fkid'        => $recruit['id'],
                'createtime'  => time(),
                'orderno'     => createUniontid(),
                'price'       => $topPrice,
                'num'         => $topDay,//num这里是置顶的天数
                'plugin'      => 'recruit',
                'payfor'      => 'recruitOrder',
                'goodsprice'  => $topPrice,
                'fightstatus' => 2,//代表这里是置顶操作
                'name'        => $recruit['contacts'],
                'mobile'      => $recruit['contact_phone'],
            ];
            pdo_insert(PDO_NAME.'order',$orderdata);
            $orderid = pdo_insertid();
            if (empty($orderid)) {
                $this->renderError('生成订单失败，请刷新重试');
            } else {
                $this->renderSuccess('置顶成功',['status' => 1,'type' => 'recruit','orderid' => $orderid]);
            }
        }
    }
}




