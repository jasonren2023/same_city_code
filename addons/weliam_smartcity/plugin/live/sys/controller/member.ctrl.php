<?php
defined('IN_IA') or exit('Access Denied');


class Member_WeliamController {
    /**
     * Comment: 获取成员列表
     * Author: zzw
     * Date: 2020/11/5 11:19
     */
    public function memberIndex(){
        global $_W,$_GPC;
        //基本参数信息获取
        $page      = $_GPC['page'] ? : 1;
        $pageIndex = 10;
        $nickname  = $_GPC['nickname'] ? : '';
        $memberRole  = $_GPC['member_role'] ? : '';
        //条件生成
        $where['uniacid'] = $_W['uniacid'];
        if($nickname) $where['nickname LIKE'] = "%{$nickname}%";
        if($memberRole) $where['member_role'] = $memberRole;
        if($memberRole == 5) $where['member_role'] = 0;
        //获取直播相关信息
        $total = pdo_count(PDO_NAME."live_member",$where);
        $field = ['id','nickname','avatar','openid','member_role','update_time','account_number'];
        $list = pdo_getall(PDO_NAME."live_member",$where,$field,'','update_time DESC',[$page,$pageIndex]);
        //分页操作
        $pager = wl_pagination($total, $page, $pageIndex);

        include wl_template('member/list');
    }
    /**
     * Comment: 添加成员信息
     * Author: zzw
     * Date: 2020/11/5 11:48
     */
    public function addMember(){
        global $_W,$_GPC;
        if($_W['ispost']){
            //参数信息获取
            $data = $_GPC['data'];
            //请求添加设置成员信息
            try{
                (new Live())->addMemberInfo($data);

                wl_message('操作成功' , web_url('live/member/memberIndex') , 'success');
            }catch (Exception $e){
                wl_message($e->getMessage() , referer() , 'error');
            }
        }

        include wl_template('member/add');
    }
    /**
     * Comment: 同步成员信息
     * Author: zzw
     * Date: 2020/11/5 10:41
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function infoSynchronization(){
        global $_W,$_GPC;
        //参数信息获取
        $page = $_GPC['page'] ? : 1;
        $pageIndex = 30;
        $pageStart = $page * $pageIndex - $pageIndex;
        if($page == 1) pdo_update(PDO_NAME . "live_member" , ['is_synchronization'=>0]);
        //请求获取列表信息
        $params = [
            'role'    => -1 ,//取值 [-1:所有成员， 0:超级管理员，1:管理员，2:主播，3:运营者]
            'offset'  => $pageStart ,// 起始偏移量
            'limit'   => $pageIndex ,// 查询个数，最大30，默认10
            'keyword' => '' ,// 搜索的微信号，不传返回全部
        ];
        try{
            $info = (new Live())->memberInfoSynchronization($params);
            //循环处理列表信息
            foreach($info['list'] as $item){
                foreach($item['roleList'] as $role){
                    $data = [
                        'uniacid'            => $_W['uniacid'] ,
                        'nickname'           => $item['nickname'] ,//昵称
                        'avatar'             => $item['headingimg'] ,//头像
                        'openid'             => $item['openid'] ,//用户openid  唯一标识
                        'member_role'        => $role ,//具有的身份  [0:超级管理员，1:管理员，2:主播，3:运营者]
                        'update_time'        => $item['updateTimestamp'] ,//更新时间
                        'account_number'     => $item['username'] ,//脱敏微信号
                        'is_synchronization' => 1 ,//信息是否同步:0=未同步;1=已经同步
                    ];
                    //判断是否存在成员信息 不存在则添加、存在则修改
                    $isHave = pdo_get(PDO_NAME . "live_member" , [
                        'uniacid'     => $data['uniacid'] ,
                        'openid'      => $data['openid'] ,
                        'member_role' => $data['member_role'] ,
                    ]);
                    if ($isHave) pdo_update(PDO_NAME . "live_member" , $data , ['id' => $isHave['id']]);
                    else pdo_insert(PDO_NAME . "live_member" , $data);
                }
            }
            //同步信息完成后  删除未同步的数据  这些数据就是在微信后台已经删除的成员信息
            if ($page == $info['total_page']) pdo_delete(PDO_NAME . "live_member" , ['is_synchronization' => 0]);
            Commons::sRenderSuccess("操作成功",['total_page'=>$info['total_page']]);
        }catch (Exception $e){
            Commons::sRenderError($e->getMessage());
        }
    }
    /**
     * Comment: 点击删除成员信息
     * Author: zzw
     * Date: 2020/11/5 13:57
     */
    public function deleteMemberInfo(){
        global $_W,$_GPC;
        //参数信息获取
        $username = $_GPC['username'] OR Commons::sRenderError('请输入微信号！');
        $id = $_GPC['id'] OR Commons::sRenderError('参数错误，请刷新重试！');
        //请求删除
        try{
            //获取角色信息
            $role = pdo_getcolumn(PDO_NAME."live_member",['id'=>$id],'member_role');
            //请求删除角色信息
            $params = [
                'username' => $username ,
                'role'     => $role ,
            ];
            (new Live())->deleteMember($params);
            //删除本平台角色信息
            pdo_delete(PDO_NAME."live_member",['id'=>$id]);

            Commons::sRenderSuccess("操作成功");
        }catch (Exception $e){
            Commons::sRenderError($e->getMessage() ? : '非法请求，请确认微信号是否正确');
        }
    }


}
