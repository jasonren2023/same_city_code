<?php
defined('IN_IA') or exit('Access Denied');


/**
 * Comment: 附件管理
 * Author: zzw
 * Class attachment_WeliamController
 */
class attachment_WeliamController{
    /**
     * Comment: 进入附件操作选择页面
     * Author: zzw
     * Date: 2020/8/31 13:43
     */
    public function index(){
        global $_W,$_GPC;
        //基本参数信息获取
        $mimeType  = $_GPC['mime_type'] ? : 'image';//附件类型：image、video、application、audio
        $multi     = $_GPC['multi'] ? : '';
        $page      = intval($_GPC['page']) ? : 1;
        $pageIndex = intval($_GPC['page_index']) ? : 35;
        $group_id  = intval($_GPC['group_id']) ? : 0;
        $shop_id   = $_GPC['storeid'] ? : $_W['storeid'] ? : 0;
        $aid       = $_GPC['aid'] ? : $_W['aid'] ? : 0;
        $uniacid   = $_W['uniacid'];
        //如果是post请求则获取附件列表信息
        if($_W['ispost']){
            //条件生成
            $where = [
                'uniacid'       => $uniacid ,
                'mimetype like' => "%{$mimeType}%" ,
            ];
            if ($group_id > 0) $where['group_id'] = $group_id;
            if ($shop_id > 0) $where['shop_id'] = $shop_id;
            if ($shop_id < 0) $where['shop_id'] = 0;
            if ($aid > 0) $where['aid'] = $aid;
            //分页操作
            $limit = [$page,$pageIndex];
            //列表信息获取
            $field = ['id','aid','shop_id','url','imagewidth','imageheight','filesize','uploadtime','storage','name'];
            $list = pdo_getall(PDO_NAME.'attachment',$where,$field,'','id DESC',$limit);
            foreach($list as $key => &$val){
                //获取上传方信息
                if($val['aid'] > 0) $val['upload_user'] = pdo_getcolumn(PDO_NAME."agentusers",['id'=>$val['aid']],'agentname');
                if($val['shop_id'] > 0) $val['upload_user'] = pdo_getcolumn(PDO_NAME."merchantdata",['id'=>$val['shop_id']],'storename');
                //处理基本信息
                $val['http_url'] = tomedia($val['url']);//附件地址
                $val['filesize'] = sprintf("%.2f",$val['filesize'] / 1024);//附件大小 精确到M
                $val['uploadtime'] = date("Y-m-d H:i:s",$val['uploadtime']);//附件大小 精确到M
                //删除多余的信息
                unset($val['aid'],$val['shop_id']);
            }
            //生成分页数据
            $total = pdo_count(PDO_NAME.'attachment',$where);
            $totalPage = ceil($total / $pageIndex);


            Commons::sRenderSuccess('附件信息列表',['list'=>$list,'total_page'=>$totalPage]);
        }

        include wl_template('utility/attachment_index');
    }
    /**
     * Comment: 文件上传
     * Author: zzw
     * Date: 2020/8/28 16:44
     * @throws Exception
     */
    public function uploadFile(){
        global $_W,$_GPC;
        //基本参数信息获取
        $params = [
            'uniacid'  => $_W['uniacid'] ,
            'aid'      => $_W['aid'] ? : 0 ,
            'shop_id'  => $_W['storeid'] ? : 0 ,
            'group_id' => intval($_GPC['group_id']) ? : 0 ,
            'filesize' => $_FILES['file']['size'] ,
            'mimetype' => $_FILES['file']['type'] ,
            'name'     => $_FILES['file']['name'] ,
        ];

        UploadFile::uploadIndex($_FILES,1,0,$params);
    }
    /**
     * Comment: 删除附件信息
     * Author: zzw
     * Date: 2020/8/31 13:51
     */
    public function delAttachment(){
        global $_W,$_GPC;
        //基本参数信息获取
        $ids = $_GPC['ids'] OR Commons::sRenderError('参数错误，请刷新重试！');
        //循环处理判断图片信息
        if(is_array($ids)){
            foreach($ids as $id){
                $this->delAttachmentFile($id);
            }
        }else{
            $this->delAttachmentFile($ids);
        }
        //删除信息
        if(pdo_delete(PDO_NAME."attachment",['id IN'=>$ids])) Commons::sRenderSuccess('删除成功');
        else Commons::sRenderError('删除失败，请刷新重试！');
    }
    /**
     * Comment: 删除附件信息的同时删除本地文件信息
     * Author: zzw
     * Date: 2020/12/7 14:54
     * @param $id
     */
    public function delAttachmentFile($id){
        global $_W;
        //判断附件是否储存在本地
        $info = pdo_get(PDO_NAME."attachment",['id'=>$id],['storage','url']);
        if($info['storage'] == 0){
            $fullName = PATH_ATTACHMENT . $info['url'];//文件在本地服务器暂存地址

            unlink($fullName);
        }
    }
    /**
     * Comment: 移动附件到其他分组
     * Author: zzw
     * Date: 2020/9/1 11:06
     */
    public function moveAttachment(){
        global $_W,$_GPC;
        //参数信息获取
        $ids = $_GPC['ids'] OR Commons::sRenderError("未选择附件，请重试！");
        $group_id = $_GPC['group_id'] OR Commons::sRenderError("不存在的分组!");
        //移动操作
        pdo_update(PDO_NAME."attachment",['group_id'=>$group_id],['id IN'=>$ids]);

        Commons::sRenderSuccess("操作成功");
    }
    /**
     * Comment: 获取分组列表信息
     * Author: zzw
     * Date: 2020/8/28 17:31
     */
    public function groupIndex(){
        global $_W,$_GPC;
        //基本参数信息获取
        $mimeType  = $_GPC['mime_type'] ? : 'image';//附件类型：image、video、application
        $shop_id   = intval($_W['storeid']) ? : 0;
        $aid       = intval($_W['aid']) ? : 0;
        $uniacid   = $_W['uniacid'];
        //条件生成  总后可以查看所有图片但是只能查看总后台的分组
        $type = $this->groupValueTransformation($mimeType);
        $where = [
            'uniacid' => $uniacid ,
            'type'    => $type ,
            'shop_id' => $shop_id ,
            'aid'     => $aid ,
        ];
//        if ($shop_id > 0) $where['shop_id'] = $shop_id;
//        if ($aid > 0) $where['aid'] = $aid;
        //列表信息获取
        $list = pdo_getall(PDO_NAME."attachment_group",$where,['id','name'],'','id DESC');

        Commons::sRenderSuccess('分组信息',$list);
    }
    /**
     * Comment: 添加分组
     * Author: zzw
     * Date: 2020/8/28 16:57
     */
    public function groupAdd(){
        global $_W , $_GPC;
        //信息获取
        $name = $_GPC['name'] OR Commons::sRenderError('请输入组名称！');
        $mimeType = $_GPC['mime_type'] ? : 'image';//附件类型：image、video、application
        $shop_id  = $_W['storeid'];
        $aid      = $_W['aid'];
        $uniacid  = $_W['uniacid'];
        //条件生成  分组类型:1=图片,2=视频,3=文件
        $type = $this->groupValueTransformation($mimeType);
        $where = [
            'name'    => $name,
            'uniacid' => $uniacid ,
            'type'    => $type ,
        ];
        if ($shop_id > 0) $where['shop_id'] = $shop_id;
        if ($aid > 0) $where['aid'] = $aid;
        //判断是否已经存在
        $isHave = pdo_get(PDO_NAME . "attachment_group" , $where);
        if ($isHave) Commons::sRenderError('分组已存在！');
        //添加操作
        $data = [
            'uniacid' => $uniacid ,
            'aid'     => $aid ,
            'shop_id' => $shop_id ,
            'type'    => $type ,
            'name'    => $name ,
        ];
        pdo_insert(PDO_NAME."attachment_group",$data);

        Commons::sRenderSuccess('添加成功');
    }
    /**
     * Comment: 编辑组信息
     * Author: zzw
     * Date: 2020/8/28 18:35
     */
    public function groupEdit(){
        global $_W , $_GPC;
        //信息获取
        $id = $_GPC['id'] OR Commons::sRenderError('参数错误，请刷新重试！');
        $name = $_GPC['name'] OR Commons::sRenderError('请输入组名称！');
        $mimeType = $_GPC['mime_type'] ? : 'image';//附件类型：image、video、application
        $shop_id  = $_W['storeid'];
        $aid      = $_W['aid'];
        $uniacid  = $_W['uniacid'];
        //条件生成  分组类型:1=图片,2=视频,3=文件
        $type = $this->groupValueTransformation($mimeType);
        $where = [
            'name'    => $name,
            'uniacid' => $uniacid ,
            'type'    => $type ,
        ];
        if ($shop_id > 0) $where['shop_id'] = $shop_id;
        if ($aid > 0) $where['aid'] = $aid;
        //判断是否已经存在
        $isHave = pdo_get(PDO_NAME . "attachment_group" , $where);
        if ($isHave) Commons::sRenderError('分组已存在！');
        //修改操作
        pdo_update(PDO_NAME."attachment_group",['name'=>$name],['id'=>$id]);

        Commons::sRenderSuccess('修改成功');
    }
    /**
     * Comment: 删除分组
     * Author: zzw
     * Date: 2020/8/28 18:08
     */
    public function groupDelete(){
        global $_W,$_GPC;
        //参数信息获取
        $id = $_GPC['id'] OR Commons::sRenderError('参数错误，请刷新重试！');
        //删除信息
        pdo_delete(PDO_NAME."attachment_group",['id'=>$id]);

        Commons::sRenderSuccess('删除成功');
    }
    /**
     * Comment: 导入微擎图片信息
     * Author: zzw
     * Date: 2020/8/31 16:49
     */
    public function importAttachment(){
        global $_W,$_GPC;
        //获取微擎图片信息
        $list = Cache::getCache('import_attachment','list');
        if(!$list){
            $field = ['filename','attachment','type','createtime','module_upload_dir','group_id'];
            $where = ['uniacid'=>$_W['uniacid'],'type'=>1];
            $list = table('core_attachment')
                ->select($field)
                ->where($where)
                ->getall();
            Cache::setCache('import_attachment','list',json_encode($list));
        }else{
            $list = json_decode($list,true);
        }
        //循环储存附件信息  一次最多50条
        $index = 0;
        $tips = true;
        (new MysqlFunction())->startTrans();//开启事务
        foreach($list as $key => $value){
            //到达限制 跳出循环
            if(++$index > 50) break;
            //判断附件是否已经存在
            $isHave = pdo_get(PDO_NAME."attachment",['url'=>$value['attachment']]);
            if($isHave) {
                //已经存在当前图片  跳出当前循环
                unset($list[$key]);
                continue;
            }
            //获取图片基本信息
            $params = [
                'uniacid'    => $_W['uniacid'] ,
                'aid'        => $_W['aid'] ,
                'url'        => $value['attachment'] ,
                'name'       => $value['filename'] ,
                'uploadtime' => $value['createtime'] ,
                'mimetype'   => 'image' ,
            ];
            $url = tomedia($value['attachment']);
            list($params['imagewidth'] , $params['imageheight']) = getimagesize($url);//基本参数获取
            $nameArr = explode('.' , $value['attachment']);
            $params['suffix']  = $nameArr[count($nameArr) - 1];
            //储存图片信息
            $res = pdo_insert(PDO_NAME."attachment",$params);
            if(!$res){
                (new MysqlFunction())->rollback();
                $tips = false;
                break;
            }else{
               unset($list[$key]);
            }
        }
        //步骤完成  进行其他操作
        if($tips){
            //操作成功
            (new MysqlFunction())->commit();
            if(count($list) > 0){
                $list = array_values($list);
                Cache::setCache('import_attachment','list',json_encode($list));
            }else{
                Cache::deleteCache('import_attachment','list');
            }

            Commons::sRenderSuccess('操作成功',['surplus'=>count($list)]);
        }else{
            //操作失败
            Commons::sRenderSuccess('操作失败');
        }
    }
    /**
     * Comment: 文件mimetype信息转换   字符串转code
     * Author: zzw
     * Date: 2020/8/28 17:03
     * @param $mimeType
     * @return int
     */
    protected function groupValueTransformation($mimeType){
        $type = 1;
        switch ($mimeType) {
            case 'image':
                $type = 1;
                break;
            case 'video':
                $type = 2;
                break;
            case 'application':
                $type = 3;
                break;
        }

        return $type;
    }
    /**
     * Comment: 获取代理商列表
     * Author: zzw
     * Date: 2020/9/23 11:05
     */
    public function getAgentList(){
        global $_W,$_GPC;
        $field = ['id','agentname'];
        $list = pdo_getall(PDO_NAME."agentusers",['uniacid'=>$_W['uniacid']],$field);

        Commons::sRenderSuccess('代理商列表',['list'=>$list]);
    }
    /**
     * Comment: 获取店铺信息列表
     * Author: zzw
     * Date: 2020/9/23 11:16
     */
    public function getShopList(){
        global $_W,$_GPC;
        $aid = $_GPC['aid'] ? : $_W['aid'];
        $field = ['id','storename'];
        $list = pdo_getall(PDO_NAME."merchantdata",['uniacid'=>$_W['uniacid'],'aid'=>$aid],$field);

        Commons::sRenderSuccess('店铺信息列表',['list'=>$list]);
    }




}
