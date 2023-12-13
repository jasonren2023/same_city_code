<?php
defined('IN_IA') or exit('Access Denied');

class Template_WeliamController {
    #订单支付成功 = pay；订单发货提醒 = send；售后状态通知 = after_sale；退款成功通知 = refund；订单待付款提醒 = remind；业务处理结果通知 = service
    #核销成功提醒 = write_off；拼团结果通知 = fight；商品下架提醒 = shop；签到成功通知 = sign
    protected static $name = 'new_temp_set';
    /**
     * Comment: 进入模板消息首页
     * Author: zzw
     * Date: 2019/9/2 18:13
     */
    public function index(){
        global $_W,$_GPC;
        $set = Setting::wlsetting_read(self::$name);
        $mid = Setting::wlsetting_read('adminmid');
        $useTemplateType = Setting::wlsetting_read('use_template_type');
        $weChatTempId = Setting::wlsetting_read('wechat_template_id') ? : '';
        $member = pdo_get(PDO_NAME.'member',['id' => $mid],['id','nickname']);

        include wl_template("template/template");
    }
    /**
     * Comment: 设置模板消息
     * Author: zzw
     * Date: 2019/9/3 9:20
     */
    public function setting(){
        global $_W,$_GPC;
        #1、参数获取
        $type = $_GPC['type'] OR wl_message('非法访问');
        #2、获取设置信息
        $set = Setting::wlsetting_read(self::$name);
        $info = $set[$type];

        include wl_template("template/set");
    }
    /**
     * Comment: 保存模板消息的设置
     * Author: zzw
     * Date: 2019/9/3 9:21
     */
    public function saveSet(){
        global $_W,$_GPC;
        #1、参数接收
        $type = $_GPC['type'] OR wl_message('缺少参数，请刷新重试');
        $data = $_GPC['data'] OR wl_message('缺少参数，请刷新重试');
        #2、数据拼装
        $set = Setting::wlsetting_read(self::$name);
        $set[$type] = $data;
        #3、储存数据信息
        $res = Setting::wlsetting_save($set,self::$name);
        if($res) wl_message('设置成功！', web_url('news/template/index'), 'success');
            else wl_message('设置失败！');
    }
    /**
     * Comment: 模板id获取
     * Author: zzw
     * Date: 2019/9/4 9:42
     */
    public function getTempId(){
        global $_W,$_GPC;
        #1、参数获取
        $source = $_GPC['source'] OR wl_json('获取失败，请刷新重试!');//端口类型：1=微信公众号；2=H5；3=微信小程序
        $type = $_GPC['type'] OR wl_json('获取失败，请刷新重试!');
        #2、id获取
        TempModel::init($source,$type);
    }
    /**
     * Comment: 设置管理员mid
     * Author: wlf
     * Date: 2019/9/19 17:35
     */
    public function addmid(){
        global $_W,$_GPC;
        #1、参数获取
        $mid = $_GPC['mid'];
        $res = Setting::wlsetting_save($mid,'adminmid');
        return $mid;
    }
    /**
     * Comment: 修改模板消息中某一项的内容
     * Author: zzw
     * Date: 2019/12/10 14:17
     */
    public function changeStatus(){
        global $_W,$_GPC;
        #1、参数获取
        $name = $_GPC['name'] OR Commons::sRenderError('参数错误，请刷新重试!');
        $source = $_GPC['source'] OR Commons::sRenderError('参数错误，请刷新重试!');
        $key = $_GPC['key'] OR Commons::sRenderError('参数错误，请刷新重试!');
        $status = $_GPC['status'] ? $_GPC['status'] : '';
        #2、获取设置信息  并且覆盖原有的值 变更为新的值
        $set = Setting::wlsetting_read(self::$name);
        $set[$name][$source][$key] = $status;
        Setting::wlsetting_save($set,self::$name);

        Commons::sRenderSuccess('修改成功');
    }
    /**
     * Comment: 修改模板消息中的设置信息
     * Author: zzw
     * Date: 2021/1/27 17:41
     */
    public function changeSetInfo(){
        global $_W,$_GPC;
        //参数获取
        $name = $_GPC['name'] OR Commons::sRenderError('参数错误，请刷新重试!');
        $source = $_GPC['source'] OR Commons::sRenderError('参数错误，请刷新重试!');
        $remark = $_GPC['remark'] ? : '';
        $link = $_GPC['link'] ? : '';
        //获取设置信息  并且覆盖原有的值 变更为新的值
        $set = Setting::wlsetting_read(self::$name);
        $set[$name][$source]['remark'] = $remark;
        $set[$name][$source]['link'] = $link;
        Setting::wlsetting_save($set,self::$name);

        Commons::sRenderSuccess('修改成功');
    }
    /**
     * Comment: 修改公众号消息类型
     * Author: zzw
     * Date: 2021/2/19 11:08
     */
    public function changeTemplateType(){
        global $_W,$_GPC;
        //参数获取
        $type = $_GPC['type'];
        Setting::wlsetting_save($type,'use_template_type');

        Commons::sRenderSuccess('修改成功');
    }
    /**
     * Comment: 修改公众号模板id
     * Author: zzw
     * Date: 2021/2/20 13:44
     */
    public function changeWeChatTempId(){
        global $_W,$_GPC;
        //参数获取
        $tempId = $_GPC['temp_id'];
        Setting::wlsetting_save($tempId,'wechat_template_id');

        Commons::sRenderSuccess('修改成功');
    }



}