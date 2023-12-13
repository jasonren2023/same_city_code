<?php
defined('IN_IA') or exit('Access Denied');


class Member_WeliamController{
    /**
     * Comment: 会员列表
     * Author: zzw
     * Date: 2021/2/25 17:42
     */
    public function memberList(){
        global $_W,$_GPC;
        $university = Customized::init('university442') ? : 0;

        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $nickname  = $_GPC['nickname'] ? : '';
        $examine   = $_GPC['examine'] ? : '';
        //条件生成
        $where = " WHERE a.uniacid = {$_W['uniacid']} ";
        if($_W['aid'] > 0) $where .= " AND a.aid = {$_W['aid']} ";
        if($nickname) $where .= " AND b.nickname LIKE '%{$nickname}%' ";
        if($examine > 0) $where .= " AND a.examine = {$examine} ";
        //sql语句生成
        $field = "a.id,a.aid,a.mid,a.gneder,a.birth,a.height,a.weight,a.nation,a.marital_status,a.education,
        a.current_province,a.current_city,a.current_area,a.examine,a.is_top,a.top_end_time,a.create_time,
        a.sort,b.nickname,b.avatar,a.matchmaker_id,a.falsename,a.falseavatar,a.falsereal,b.encodename";
        $order = " ORDER BY a.sort DESC,a.id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."dating_member")
            ." as a LEFT JOIN ".tablename(PDO_NAME."member")
            ." as b ON a.mid = b.id ";
        //列表获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            if(empty($val['mid'])){
                $val['mid'] = 0;
                $val['nickname'] = $val['falsename'];
                $val['avatar'] = $val['falseavatar'];
                $val['realname'] = $val['falsereal'];
            }
            if(!empty($val['encodename'])){
                $val['nickname'] = base64_decode($val['encodename']);
            }
            //头像处理
            $val['avatar'] = tomedia($val['avatar']);
            //地区获取
            $val['province'] = pdo_getcolumn(PDO_NAME."area",['id' => $val['current_province']],'name');
            $val['city']     = pdo_getcolumn(PDO_NAME."area",['id' => $val['current_city']],'name');
            $val['area']     = pdo_getcolumn(PDO_NAME."area",['id' => $val['current_area']],'name');
            //出生日期
            $val['birth'] = date("Y-m-d",$val['birth']);
            //获取红娘昵称
            if($val['matchmaker_id'] > 0) $val['matchmaker_name'] = pdo_getcolumn(PDO_NAME."dating_matchmaker",['id'=>$val['matchmaker_id']],'nickname');
            //自定义信息
            $val['martext'] = pdo_getcolumn(PDO_NAME.'dating_option',array('id'=>$val['marital_status']),'title');
            $val['edutext'] = pdo_getcolumn(PDO_NAME.'dating_option',array('id'=>$val['education']),'title');

            //所属代理
            if($val['aid'] > 0) $val['agent_name'] = pdo_getcolumn(PDO_NAME."agentusers",['id'=>$val['aid']],'agentname');
            else $val['agent_name'] = '平台';
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('member/index');
    }
    /**
     * Comment: 查看会员详细信息
     * Author: zzw
     * Date: 2021/2/26 15:19
     */
    public function memberSee(){
        global $_W,$_GPC;
        $university = Customized::init('university442') ? : 0;

        //参数消息获取
        $id = $_GPC['id'];
        if($id > 0){
            //获取信息
            $info = pdo_get(PDO_NAME."dating_member",['id'=>$id]);
            //信息处理
            $info = Dating::handleMemberInfo($info);
        }else{
            $info = [];
        }


        $AreaTab = tablename(PDO_NAME . "area");
        $orderBy = " ORDER BY id ASC ";
        $province = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 1 " . $orderBy);

        $province_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 2 AND id = '{$info['current_city']}'");
        $city = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 2 AND pid = {$province_id}" . $orderBy);

        $city_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 3 AND id = '{$info['current_area']}'");
        $district = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 3 AND pid = {$city_id}" . $orderBy);


        $province_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 2 AND id = '{$info['hometown_city']}'");
        $hometown_city = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 2 AND pid = {$province_id}" . $orderBy);

        $city_id = pdo_fetchcolumn("SELECT pid FROM " . $AreaTab . " WHERE level = 3 AND id = '{$info['hometown_area']}'");
        $hometown_district = pdo_fetchall("SELECT id,name FROM " . $AreaTab . " WHERE level = 3 AND pid = {$city_id}" . $orderBy);

        $marital_status = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 1),array('id','title'));
        $education = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 2),array('id','title'));
        $registered_residence_type = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 3),array('id','title'));
        $live = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 4),array('id','title'));
        $travel = pdo_getall('wlmerchant_dating_option',array('uniacid' => $_W['uniacid'],'type' => 5),array('id','title'));
        //定制内容
        if($university){
            $tags = pdo_getall('wlmerchant_dating_checkbox',array('did' => $id),array('optionid'));
            $tags = array_column($tags,'optionid');
        }
        //个性标签
        $labelList = pdo_getall(PDO_NAME."dating_label",['uniacid'=>$_W['uniacid']],['id','title']);


        if ($_W['ispost']) {
            $service = $_GPC['info'];
            $service['birth'] = strtotime($service['birth_text']);
            if(!empty($service['photo_show'])){
                $service['photo'] = serialize($service['photo_show']);
            }
            $service['top_end_time'] = strtotime($service['top_end_time']);

            $realname = $service['realname'];
            if(empty($service['mid'])){
                $service['falsereal'] = $realname;
            }
            unset($service['birth_text']);
            unset($service['realname']);
            unset($service['photo_show']);
            if(empty($service['height'])){
                wl_message('请输入身高');
            }
            if(empty($service['weight'])){
                wl_message('请输入体重');
            }
            if(empty($service['nation'])){
                wl_message('请输入民族');
            }
            $userlabel = $_GPC['userlabel'];
            if(!empty($userlabel)){
                $service['label_id'] = implode($userlabel,',');
            }
            if($id > 0){
                $res = pdo_update(PDO_NAME."dating_member",$service,array('id' => $id));
                if($info['mid'] > 0){
                    $member_arr = ['realname' => $realname];
                    $member_res = pdo_update(PDO_NAME."member",$member_arr,array('id' => $info['mid']));
                }
            }else{
                $service['uniacid'] = $_W['uniacid'];
                $service['aid'] = $_W['aid'];
                $service['create_time'] = time();
                $res = pdo_insert(PDO_NAME."dating_member",$service);
                $id = pdo_insertid();
            }

            $newtags = $_GPC['tag'];
            $res2 = pdo_delete('wlmerchant_dating_checkbox',array('did'=>$id));
            if(!empty($newtags)){
                foreach ($newtags as $new){
                    $res3 = pdo_insert(PDO_NAME . 'dating_checkbox', ['did' => $id,'optionid' => $new]);
                }
            }
            if($res || $res2 || $res3){
                wl_message('信息编辑成功',web_url('dating/member/memberList'), 'success');
            }else{
                wl_message('信息保存失败，请重试');
            }

        }


        include wl_template('member/see');
    }
    /**
     * Comment: 审核会员信息
     * Author: zzw
     * Date: 2021/2/26 15:54
     */
    public function memberExamine(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR show_json(0, "参数错误，请刷新重试!");
        $status = $_GPC['status'] ? : 3;//审核状态:1=待审核,2=未通过,3=已通过/显示中
        $reason = $_GPC['reason'] ? : '';
        if($status == 2 && !$reason) show_json(0, "请输入驳回原因");
        //修改状态
        $data = [
            'examine' => $status,
            'reason'  => $reason
        ];
        pdo_update(PDO_NAME."dating_member",$data,['id'=>$id]);
        //发送模板消息通知用户审核结果
        if($status == 2) {
            $resultText = '未通过';
            $contentText = "个人资料被驳回：{$reason}";
            $link = "pages/subPages2/blindDate/form/userInfo";
        } else if($status == 3) {
            $resultText = '已通过';
            $contentText = "恭喜您，会员信息审核已通过！";
            $link = "pages/subPages2/blindDate/member/detail?id={$id}";
        }
        $member = pdo_get(PDO_NAME."dating_member",['id'=>$id]);
        $modelData = [
            'first'   => '您好，您的资料已审核完毕',
            'type'    => '审核结果',
            'content' => $contentText,
            'status'  => $resultText,
            'time'    => date("Y-m-d H:i:s",time()),
            'remark'  => "点击查看"
        ];
        TempModel::sendInit('service',$member['mid'],$modelData,$_W['source'],h5_url($link));

        show_json(1, "操作成功");
    }
    /**
     * Comment: 切换红娘
     * Author: zzw
     * Date: 2021/4/12 15:52
     */
    public function changeMatchmaker(){
        global $_W,$_GPC;
        //参数获取
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = $_GPC['page_index'] ? : 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $type      = $_GPC['type'] ? : 'get';//get=获取信息，post=修改信息
        $id        = $_GPC['id'] ? : '';//红娘id  get=当前红娘id，post=新的红娘的id
        $mid       = $_GPC['mid'] ? : 0;
        $memberId  = $_GPC['member_id'] ? : '';
        $status    = $_GPC['status'] ? : 3;
        $nickname  = $_GPC['nickname'] ? : '';
        //根据type进行对应的操作
        if($type == 'post'){
            //修改信息
            pdo_update(PDO_NAME."dating_member",['matchmaker_id'=>$id],['id'=>$memberId]);
            //取消推荐关系
            $sql = " DELETE FROM ".tablename(PDO_NAME."dating_exchange")." WHERE mid_one = {$mid} OR mid_two = {$mid} ";
            pdo_query($sql);

            show_json(1, ['message'=>'操作成功','url'=>web_url('dating/member/memberList')]);
        }else{
            //获取红娘信息
            $where = " WHERE uniacid = {$_W['uniacid']} AND mid <> {$mid} ";
            if($status != 5){
                $where .= " AND status = {$status} ";
            }
            if($nickname) $where .= " AND nickname LIKE '%{$nickname}%' ";
            $field = " id,nickname,avatar,status ";
            $order = " ORDER BY create_time DESC ";
            $limit = " LIMIT {$pageStart},{$pageIndex} ";
            $sql   = "SELECT {$field} FROM ".tablename(PDO_NAME."dating_matchmaker");
            $list  = pdo_fetchall($sql.$where.$order.$limit);
            foreach($list as &$mVal){
                $mVal['avatar'] = tomedia($mVal['avatar']);
            }

            //分页操作
            $total = pdo_fetchall($sql.$where);
            $total = count($total);
            $pager = wl_pagination($total, $page, $pageIndex);

            include wl_template('member/matchmaker');
        }
    }
    /**
     * Comment: 删除会员信息
     * Author: zzw
     * Date: 2021/2/25 17:43
     */
    public function memberDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."dating_member",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }


    /**
     * Comment: 个性标签列表
     * Author: zzw
     * Date: 2021/2/25 15:01
     */
    public function labelList(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $title     = $_GPC['title'] ? : '';//名称
        //条件生成
        $where = [
            'uniacid' => $_W['uniacid'] ,
            'aid'     => $_W['aid']
        ];
        if($title) $where['title LIKE'] = "%{$title}%";
        //列表获取
        $field = ['id','title','create_time','sort'];
        $order = 'sort DESC,id DESC';
        $list = pdo_getall(PDO_NAME."dating_label",$where,$field,'',$order,[$page,$pageIndex]);
        //分页操作
        $total = pdo_count(PDO_NAME."dating_label",$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('label/index');
    }
    /**
     * Comment: 标签添加/编辑
     * Author: zzw
     * Date: 2021/2/25 15:05
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
                'title'   => $data['title']
            ];
            if($id > 0) $where['id <>'] = $id;
            $isHave = pdo_get(PDO_NAME."dating_label",$where);
            if($isHave) wl_message('标签已经存在',referer(),'error');
            //根据是否存在id 判断是添加操作还是修改操作
            if($id){
                pdo_update(PDO_NAME."dating_label",$data,['id'=>$id]);

                wl_message('修改成功',web_url('dating/member/labelList'),'success');
            }else{
                //信息补充  并且进行添加操作
                $data['uniacid']     = $where['uniacid'];
                $data['aid']         = $where['aid'];
                $data['create_time'] = time();
                pdo_insert(PDO_NAME."dating_label",$data);

                wl_message('添加成功',web_url('dating/member/labelList'),'success');
            }
        }
        //准备信息
        if($id){
            //修改信息准备
            $info = pdo_get(PDO_NAME."dating_label",['id'=>$id],['title','sort']);
        }else{
            //添加信息准备
            $sort = pdo_fetchcolumn("SELECT MAX(id) FROM ".tablename(PDO_NAME."dating_label"));
            $info['sort'] = $sort ? : 1;
        }

        include wl_template('label/edit');
    }
    /**
     * Comment: 生成默认的标签信息
     * Author: zzw
     * Date: 2021/2/25 15:05
     */
    public function labelDefaultInfo(){
        global $_W,$_GPC;
        //获取默认标签
        $list = Dating::defaultLabelList();
        foreach($list as $val){
            $data = [];
            //判断是否已经存在当前标签
            $data['title'] = $val;
            $data['uniacid'] = $_W['uniacid'];
            $data['aid']     = $_W['aid'];
            $isHave = pdo_get(PDO_NAME."dating_label",$data);
            if(!$isHave){
                //不存在 添加标签信息
                $data['create_time'] = time();
                pdo_insert(PDO_NAME."dating_label",$data);
            }
        }
        //修改所有默认信息的排序信息
        pdo_fetchall("update ".tablename(PDO_NAME."dating_label")." set `sort` = `id` WHERE `sort` is null ");

        wl_json(1,'生成成功');
    }
    /**
     * Comment: 删除标签信息
     * Author: zzw
     * Date: 2021/2/25 15:14
     */
    public function labelDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."dating_label",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }


    /**
     * Comment: 动态列表
     * Author: zzw
     * Date: 2021/3/1 14:11
     */
    public function dynamicList(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $content   = $_GPC['content'] ? : '';
        $isFictitious   = $_GPC['is_fictitious'] ? : 0;
        $examine   = $_GPC['examine'] ? : 0;
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if($content) $where .= " AND content LIKE '%{$content}%' ";
        if($isFictitious > 0) $where .= " AND is_fictitious = {$isFictitious} ";
        if($examine > 0) $where .= " AND status = {$examine} ";
        //sql语句生成
        $field = "id,mid,content,create_time,pv,status,is_fictitious,fictitious_nickname,fictitious_avatar";
        $order = " ORDER BY create_time DESC,id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."dating_dynamic");
        //列表获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            //是否为虚拟动态:1=不是,2=是
            if($val['is_fictitious'] == 1){
                //不是虚拟动态信息  获取对应的数据信息
                [$val['nickname'],$val['avatar']] = Dating::handleUserInfo($val['mid']);
            }else{
                //虚拟动态信息
                $val['nickname'] = $val['fictitious_nickname'];
                $val['avatar'] = tomedia($val['fictitious_avatar']);
            }
            //发布时间
            $val['create_time'] = date("Y-m-d H:i",$val['create_time'] );
            //删除多余的信息
            unset($val['fictitious_nickname'],$val['fictitious_avatar']);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('dynamic/index');
    }
    /**
     * Comment: 查看动态信息
     * Author: zzw
     * Date: 2021/3/1 14:47
     */
    public function dynamicSee(){
        global $_W,$_GPC;
        //参数消息获取
        $id = $_GPC['id'] OR wl_message('不存在的动态信息',web_url('dating/member/dynamicList'));
        //获取信息
        $info = pdo_get(PDO_NAME."dating_dynamic",['id'=>$id]);
        //信息处理
        $info = Dating::handleDynamicInfo($info);


        include wl_template('dynamic/see');
    }
    /**
     * Comment: 添加、编辑动态信息
     * Author: zzw
     * Date: 2021/2/26 18:24
     */
    public function dynamicEdit(){
        global $_W,$_GPC;
        //基本参数信息获取
        $id = $_GPC['id'] ? : '';
        //接收信息 并且进行对应的处理
        if($_W['ispost']){
            $data  = $_GPC['data'];
            //判断信息是否完整
            if(!$data['fictitious_nickname']) wl_message('请输入昵称!',referer(),'success');
            if(!$data['fictitious_avatar']) wl_message('请选择头像!',referer(),'success');
            if(!$data['content'] && !$data['photo'] && !$data['video']) wl_message('动态、图片、视频至少需要存在一项!',referer(),'success');
            if(!$data['address']) wl_message('请选择地址!',referer(),'success');
            //信息处理
            $data['photo'] = serialize($data['photo']);
            //根据是否存在id 判断是添加操作还是修改操作
            if($id){
                pdo_update(PDO_NAME."dating_dynamic",$data,['id'=>$id]);

                wl_message('修改成功',web_url('dating/member/dynamicList'),'success');
            }else{
                //信息补充  并且进行添加操作
                $data['uniacid']       = $_W['uniacid'];
                $data['aid']           = $_W['aid'];
                $data['mid']           = 0;
                $data['is_fictitious'] = 2;
                $data['create_time']   = time();
                pdo_insert(PDO_NAME."dating_dynamic",$data);

                wl_message('添加成功',web_url('dating/member/dynamicList'),'success');
            }
        }
        //准备信息
        if($id){
            //修改信息准备
            $info = pdo_get(PDO_NAME."dating_dynamic",['id'=>$id]);
            $info['photo'] = unserialize($info['photo']);
        }

        include wl_template('dynamic/edit');
    }
    /**
     * Comment: 审核动态信息
     * Author: zzw
     * Date: 2021/3/1 14:21
     */
    public function dynamicExamine(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR show_json(0, "参数错误，请刷新重试!");
        $status = $_GPC['status'] ? : 3;//审核状态:1=待审核,2=未通过,3=已通过/显示中
        $reason = $_GPC['reason'] ? : '';
        if($status == 2 && !$reason) show_json(0, "请输入驳回原因");
        //修改状态
        $data = [
            'status' => $status,
            'reason' => $reason
        ];
        pdo_update(PDO_NAME."dating_dynamic",$data,['id'=>$id]);
        //发送模板消息
        $dynamic = pdo_get(PDO_NAME."dating_dynamic",['id'=>$id]);
        if($status == 2) $statusText = "未通过";
        else $statusText = "已通过";
        $modelData = [
            'first'   => "您发布的动态已审核",
            'type'    => '动态审核',
            'content' => '您于'.date("Y-m-d H:s",$dynamic['create_time']).'发布的动态已审核完毕!',
            'status'  => $statusText,
            'time'    => date("Y-m-d H:i:s",time()),
            'remark'  => "点击查看"
        ];
        $url = h5_url('pages/subPages2/blindDate/dynamics/detail',['id'=>$id]);
        TempModel::sendInit('service',$dynamic['mid'],$modelData,$_W['source'],$url);

        show_json(1, "操作成功");
    }
    /**
     * Comment: 删除动态信息
     * Author: zzw
     * Date: 2021/3/1 11:37
     */
    public function dynamicDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."dating_dynamic",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }


    /**
     * Comment: 动态评论列表
     * Author: zzw
     * Date: 2021/3/12 14:47
     */
    public function commentList(){
        global $_W,$_GPC;
        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $pageStart = $page * $pageIndex - $pageIndex;
        $status    = $_GPC['status'] ? : '';
        $content   = $_GPC['content'] ? : '';
        $dynamicId = $_GPC['dynamic_id'] ? : '';
        //条件生成
        $where = " WHERE uniacid = {$_W['uniacid']} AND aid = {$_W['aid']} ";
        if($status) $where .= " AND status = {$status} ";
        if($content) $where .= " AND content LIKE '%{$content}%' ";
        if($dynamicId > 0) $where .= " AND dynamic_id = {$dynamicId} ";
        //sql语句生成
        $field = "id,mid,content,create_time,status";
        $order = " ORDER BY create_time DESC,id DESC ";
        $limit = " LIMIT {$pageStart},{$pageIndex} ";
        $sql = "SELECT {$field} FROM " .tablename(PDO_NAME."dating_dynamic_comment");
        //列表获取
        $list = pdo_fetchall($sql.$where.$order.$limit);
        foreach($list as $key => &$val){
            [$val['nickname'],$val['avatar']] = Dating::handleUserInfo($val['mid']);
            $val['create_time'] = date("Y-m-d H:i:s",$val['create_time']);
            //删除多余的信息
            unset($val['mid']);
        }
        //分页操作
        $totalSql = str_replace($field,'count(*)',$sql);
        $total = pdo_fetchcolumn($totalSql.$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('comment/index');
    }
    /**
     * Comment: 评论审核操作
     * Author: zzw
     * Date: 2021/3/12 14:41
     */
    public function commentExamine(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR show_json(0, "参数错误，请刷新重试!");
        $status = $_GPC['status'] ? : 3;//审核状态:1=待审核,2=未通过,3=已通过/显示中
        //修改状态
        pdo_update(PDO_NAME."dating_dynamic_comment",['status' => $status],['id'=>$id]);
        //发送模板消息
        $dynamicComment = pdo_get(PDO_NAME."dating_dynamic_comment",['id'=>$id]);
        if($status == 2) {
            $statusText = "未通过";
        } else {
            $statusText = "已通过";
            //通过审核  发布模板消息通知
            Dating::sendCommentModel($dynamicComment['mid'],$dynamicComment['reply_id'],$dynamicComment['dynamic_id'],$dynamicComment['content'],$dynamicComment['source']);
        }
        $modelData = [
            'first'   => "您发布的评论已审核",
            'type'    => '评论审核',
            'content' => '您于'.date("Y-m-d H:s",$dynamicComment['create_time']).'发布的评论已审核完毕!',
            'status'  => $statusText,
            'time'    => date("Y-m-d H:i:s",time()),
            'remark'  => "点击查看"
        ];
        $url = h5_url('pages/subPages2/blindDate/dynamics/detail',['id'=>$dynamicComment['dynamic_id']]);
        TempModel::sendInit('service',$dynamicComment['mid'],$modelData,$_W['source'],$url);

        show_json(1, "操作成功");
    }
    /**
     * Comment: 删除评论
     * Author: zzw
     * Date: 2021/3/12 14:47
     */
    public function commentDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."dating_dynamic_comment",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }


    /**
     * Comment: 设置信息
     * Author: zzw
     * Date: 2021/3/1 17:38
     */
    public function setEdit(){
        global $_W,$_GPC;
        $name = 'dating_set';
        if($_W['ispost']){
            $data = $_GPC['data'];

            Setting::wlsetting_save($data,$name);
            wl_message('设置成功！' , web_url('dating/member/setEdit') , 'success');
        }
        //获取已存在的设置信息
        $info =  Setting::wlsetting_read($name);

        include wl_template('datingSet');
    }

    /**
     * Comment: 选项设置
     * Author: wlf
     * Date: 2022/05/20 10:48
     */
    public function optionList(){
        global $_W,$_GPC;
        $university = Customized::init('university442') ? : 0;

        //参数获取
        $page      = max(1 , intval($_GPC['page']));
        $pageIndex = 10;
        $title     = $_GPC['title'] ? : '';//名称
        $type      = $_GPC['type'] ? : 0;//名称
        //条件生成
        $where = ['uniacid' => $_W['uniacid'] ,];
        if($title) $where['title LIKE'] = "%{$title}%";
        if($type>0) $where['type'] = $type;
        //列表获取
        $field = ['id','title','create_time','sort','type'];
        $order = 'sort DESC,id DESC';
        $list = pdo_getall(PDO_NAME."dating_option",$where,$field,'',$order,[$page,$pageIndex]);
        //分页操作
        $total = pdo_count(PDO_NAME."dating_option",$where);
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('label/optionindex');

    }

    /**
     * Comment: 生成默认的选项设置
     * Author: wlf
     * Date: 2022/05/20 14:19
     */
    public function optionDefaultInfo(){
        global $_W,$_GPC;
        //获取默认标签
        $list = Dating::defaultOptionList();
        foreach($list as $val){
            $data = [];
            //判断是否已经存在当前标签
            $data['title'] = $val['title'];
            $data['type'] = $val['type'];
            $data['uniacid'] = $_W['uniacid'];
            $isHave = pdo_get(PDO_NAME."dating_option",$data);
            if(!$isHave){
                //不存在 添加标签信息
                $data['create_time'] = time();
                pdo_insert(PDO_NAME."dating_option",$data);
            }
        }
        //修改所有默认信息的排序信息
        pdo_fetchall("update ".tablename(PDO_NAME."dating_option")." set `sort` = `id` WHERE `sort` = 0 ");

        wl_json(1,'生成成功');
    }

    /**
     * Comment: 删除选项设置
     * Author: wlf
     * Date: 2021/05/20 15:21
     */
    public function optionDel(){
        global $_W,$_GPC;
        $ids = $_GPC['ids'] ? :[] ;
        pdo_delete(PDO_NAME."dating_option",['id IN'=>$ids]);

        show_json(1, "删除成功");
    }

    /**
     * Comment: 标签添加/编辑
     * Author: zzw
     * Date: 2021/2/25 15:05
     */
    public function optionEdit(){
        global $_W,$_GPC;
        $university = Customized::init('university442') ? : 0;

        //基本参数信息获取
        $id = $_GPC['id'] ? : '';
        //接收信息 并且进行对应的处理
        if($_W['ispost']){
            $data  = $_GPC['data'];
            //判断是否已经存在当前标签
            $where = [
                'uniacid' => $_W['uniacid'] ,
                'title'   => $data['title'],
                'type'    => $_GPC['type']
            ];
            if($id > 0) $where['id <>'] = $id;
            $isHave = pdo_get(PDO_NAME."dating_option",$where);
            if($isHave) wl_message('选项已经存在',referer(),'error');
            //根据是否存在id 判断是添加操作还是修改操作
            if($id){
                pdo_update(PDO_NAME."dating_option",$data,['id'=>$id]);

                wl_message('修改成功',web_url('dating/member/optionList'),'success');
            }else{
                //信息补充  并且进行添加操作
                $data['uniacid']     = $where['uniacid'];
                $data['create_time'] = time();
                pdo_insert(PDO_NAME."dating_option",$data);

                wl_message('添加成功',web_url('dating/member/optionList'),'success');
            }
        }
        //准备信息
        if($id){
            //修改信息准备
            $info = pdo_get(PDO_NAME."dating_option",['id'=>$id],['title','sort','type']);
        }else{
            //添加信息准备
            $sort = pdo_fetchcolumn("SELECT MAX(id) FROM ".tablename(PDO_NAME."dating_option"));
            $info['sort'] = $sort ? $sort + 1 : 1;
        }

        include wl_template('label/optionedit');
    }

    /**
     * Comment: 同步旧数据
     * Author: wlf
     * Date: 2022/05/23 10:12
     */
    public function synchronizationInfo(){
        global $_W,$_GPC;

        $list = pdo_getall('wlmerchant_dating_member',array('uniacid' => $_W['uniacid']),array('id','marital_status','education','registered_residence_type','live','travel'));
        if(!empty($list)){
            if(Customized::init('university442')){

            }else{
                foreach($list as $li){
                    $data['marital_status'] = Dating::synInfo($li['marital_status'],'marital_status');
                    $data['education'] = Dating::synInfo($li['education'],'education');
                    $data['registered_residence_type'] = Dating::synInfo($li['registered_residence_type'],'registered_residence_type');
                    $data['live'] = Dating::synInfo($li['live'],'live');
                    $data['travel'] = Dating::synInfo($li['travel'],'travel');
                    pdo_update('wlmerchant_dating_member',array($data),array('id' => $li['id']));
                }
            }


        }


        wl_json(1,'操作成功');
    }

}