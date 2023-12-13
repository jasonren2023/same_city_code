<?php
defined('IN_IA') or exit('Access Denied');
use EasyWeChat\Factory;

/**
 * Comment: 模板消息操作模型
 * Author: zzw
 * Class Template
 */
class TempModel {
    protected static $source,//端口类型：1=微信公众号(wechat)；2=H5（h5）；3=微信小程序(weapp)
        $type,#订单支付成功 = pay；订单发货提醒 = send；售后状态通知 = after_sale；退款成功通知 = refund；订单待付款提醒 = remind；业务处理结果通知 = service
        #核销成功提醒 = write_off；拼团结果通知 = fight；商品下架提醒 = shop；签到成功通知 = sign
        $setName = 'new_temp_set',//设置项储存名称
        $sendData = [],//发送的模板信息内容数组(由用户传递)
        $sendConfig = [],//发送模板 - 模板配置信息
        $sendLink,//发送模板 - 模板跳转地址(可空)
        $sendMid,//接收模板信息的用户的id
        $nickName,//用户昵称信息
        $member,//用户信息
        $miniProgram = [],//公众号跳转小程序配置信息
        $app,//EasyWeChat 实例化对象
        $useTemplateType,//公众号消息类型 0=使用模板消息,1=使用订阅消息
        #--- 微信公众号 - 模板消息 ---------------------------------------------------------------
        $WeChatTempNameList = [
        'pay'        => 'OPENTM207498902' ,//订单支付成功
        'send'       => 'OPENTM200565259' ,//订单发货提醒
        'after_sale' => 'OPENTM415747403' ,//售后状态通知
        'refund'     => 'TM00430' ,//退款成功通知
        'remind'     => 'OPENTM401751289' ,//订单待付款提醒
        'service'    => 'OPENTM415477060' ,//业务处理结果通知
        'write_off'  => 'OPENTM406638019' ,//核销成功提醒
        'fight'      => 'OPENTM413234525' ,//拼团结果通知
        'shop'       => 'OPENTM401799417' ,//商品下架提醒
        'sign'       => 'OPENTM408761110' ,//签到成功通知
        'change'     => 'OPENTM403182052',//变更通知
        'profit'     => 'OPENTM405637175',//收益到账通知
    ] ,//微信公众号 —— 模板名称对照列表
        $WeChatTempTitleList = [
        'pay'        => '订单支付成功通知' ,
        'send'       => '订单发货提醒' ,
        'after_sale' => '售后状态通知' ,
        'refund'     => '退款成功通知' ,
        'remind'     => '订单待付款提醒' ,
        'service'    => '业务处理结果通知' ,
        'write_off'  => '核销成功提醒' ,
        'fight'      => '拼团结果通知' ,
        'shop'       => '商品下架提醒' ,
        'sign'       => '签到成功通知' ,
        'change'     => '变更通知',
        'profit'     => '收益到账通知',
    ],//微信公众号 —— 模板标题对照表
        #--- 微信小程序 - 订阅消息 ---------------------------------------------------------------
        $WeAppSubscriptionNameList = [
        'pay'        => '4616', //订单支付成功
        'send'       => '855', //订单发货提醒
        'after_sale' => '5049', //售后状态通知
        'refund'     => '7517', //退款成功通知
        'service'    => '17364', //业务处理通知
        'write_off'  => '6196', //核销成功提醒
        'fight'      => '5008', //拼团结果通知
        'sign'       => '6240',//签到成功通知
        'change'     => '310',//积分变更提醒
    ],//微信小程序 —— 订阅消息ID对照表
        $WeAppSubscriptionTitleList = [
        'pay'        => '付款成功通知',//客户名称、订单编号、订单金额、商品名称
        'send'       => '订单发货通知',//订单号、物流公司、快递单号
        'after_sale' => '售后状态通知',//订单编号、申请时间、状态、订单金额、温馨提示
        'refund'     => '退款成功通知',//退款金额、商品名称、订单编号
        'service'    => '业务受理进度通知',//业务类型、业务内容、处理结果、操作时间、温馨提示
        'write_off'  => '核销成功通知',//订单编号、核销时间、商品名、备注
        'fight'      => '拼团进度通知',//拼团状态、温馨提示
        'sign'       => '签到提醒',//用户名称、签到时间、温馨提示
        'change'     => '积分变更提醒',//变更数量、积分余额、变动时间、变更原因
    ],//微信小程序 —— 订阅消息标题对照表
        $WeAppSubscriptionParamsList = [
        'pay'        => [1,8,10,11],
        'send'       => [1,17,4],
        'after_sale' => [2,3,6,5],
        'refund'     => [6,3,2],
        'service'    => [1,2,3,4,5],
        'write_off'  => [1,2,4,3],
        'fight'      => [1,2,3],
        'sign'       => [7,2,3],
        'change'     => [1,2,4,3],
    ],//微信小程序 —— 订阅消息配置信息对照表
        #--- 微信公众号 - 订阅消息 ---------------------------------------------------------------
        $WeChatSubscriptionNameList = [
        'pay'        => '4616', //订单支付成功
        'send'       => '855', //订单发货提醒
        'after_sale' => '5049', //售后状态通知
        'refund'     => '7517', //退款成功通知
        'service'    => '17364', //业务处理通知
        'write_off'  => '6196', //核销成功提醒
        'fight'      => '5008', //拼团结果通知
        'sign'       => '6240',//签到成功通知
        'change'     => '310',//积分变更提醒
    ],//微信公众号 —— 订阅消息ID对照表
        $WeChatSubscriptionTitleList = [
        'pay'        => '付款成功通知',//客户名称、订单编号、订单金额、商品名称
        'send'       => '订单发货通知',//订单号、物流公司、快递单号
        'after_sale' => '售后状态通知',//订单编号、申请时间、状态、订单金额、温馨提示
        'refund'     => '退款成功通知',//退款金额、商品名称、订单编号
        'service'    => '业务受理进度通知',//业务类型、业务内容、处理结果、操作时间、温馨提示
        'write_off'  => '核销成功通知',//订单编号、核销时间、商品名、备注
        'fight'      => '拼团进度通知',//拼团状态、温馨提示
        'sign'       => '签到提醒',//用户名称、签到时间、温馨提示
        'change'     => '积分变更提醒',//变更数量、积分余额、变动时间、变更原因
    ],//微信公众号 —— 订阅消息标题对照表
        $WeChatSubscriptionParamsList = [
        'pay'        => [1,8,10,11],
        'send'       => [1,17,4],
        'after_sale' => [2,3,6,5],
        'refund'     => [6,3,2],
        'service'    => [1,2,3,4,5],
        'write_off'  => [1,2,4,3],
        'fight'      => [1,2,3],
        'sign'       => [7,2,3],
        'change'     => [1,2,4,3],
    ];//微信公众号 —— 订阅消息配置信息对照表


/****** 模板id获取 ****************************************************************************************************/
    /**
     * Comment: 模板操作初始化
     * Author: zzw
     * Date: 2019/9/3 14:11
     * @param $source
     * @param $type
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function init($source,$type){
        #1、初始化基本信息
        self::$source = $source;
        self::$type = $type;
        #2、根据来源调用方法  端口类型：1=微信公众号(wechat)；2=微信小程序(weapp)
        switch ($source) {
            case 1:
                $useTemplateType = Setting::wlsetting_read('use_template_type');//公众号消息类型 0=使用模板消息,1=使用订阅消息
                if ($useTemplateType == 1) self::WeChatSubscriptionMessage();
                else self::WeChat();
                break;//微信公众号self::WeChat();
            case 2:
                self::WeAppSubscriptionMessage();
                break;//微信小程序
            default:
                Commons::sRenderError('端口错误!');
                break;
        }
    }
    /**
     * Comment: 公众号模板生成
     * Author: zzw
     * Date: 2019/9/3 16:13
     */
    protected static function WeChat(){
        global $_W;
        #1、获取模板名称
        $tempName = self::$WeChatTempNameList[self::$type];
        #2、生成配置信息
        $params = $_W['account']->account ? : get_object_vars($_W['account']);
        $config = [
            'app_id' => $params['key'],
            'secret' => $params['secret'],
            'token' => $params['token'],
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);
        #3、获取已存在的所有模板信息，并且判断当前模板是否已存在
        try{
            $list = $app->template_message->getPrivateTemplates()['template_list'];
            if(is_array($list) && count($list) > 0){
                //判断模板是否存在
                $title = self::$WeChatTempTitleList[self::$type];//当前将要添加的模板的标题
                //建立一个以title为下标，template_id为值的新数组
                $keyArr = array_column($list,'title');//数据的下标
                $valArr = array_column($list,'template_id');//数据的值
                $newList = array_combine($keyArr , $valArr);
                //获取模板id
                $template_id = $newList[trim($title)];
            }
            #4、模板id不存在 获取新的模板id
            if(!$template_id){
                $res = $app->template_message->addTemplate(trim($tempName));
                if($res['errcode'] == 0){
                    $template_id = $res['template_id'];
                }else{
                    Commons::sRenderError($res['errmsg']);
                }
            }

            Commons::sRenderSuccess('模板id',$template_id);
        }catch(Exception $e){
            Commons::sRenderError($e->getMessage());
        }
    }
    /**
     * Comment: 微信小程序订阅消息模板id获取
     * Author: zzw
     * Date: 2019/12/11 18:10
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected static function WeAppSubscriptionMessage(){
        #1、生成配置信息
        $params = Setting::wlsetting_read('wxapp_config');
        $config = [
            'app_id'        => trim($params['appid']) ,
            'secret'        => trim($params['secret']) ,
            'response_type' => 'array' ,
        ];
        #2、基本参数信息获取
        $tid     = self::$WeAppSubscriptionNameList[self::$type];//tid获取
        $kidList = self::$WeAppSubscriptionParamsList[self::$type];//kid数组获取
        $title   = self::$WeAppSubscriptionTitleList[self::$type];//标题获取
        if (intval($tid) > 0) {
            try {
                self::$app = Factory::miniProgram($config);
                #3、获取当前账户下面所有的模板列表
                $list = self::$app->subscribe_message->getTemplates()['data'];
                #4、判断当前模板是否已经存在  存在则直接返回模板id
                if (is_array($list) && count($list) > 0) {
                    //建立一个以title为下标，template_id为值的新数组
                    $keyArr  = array_column($list , 'title');//数据的下标
                    $valArr  = array_column($list , 'priTmplId');//数据的值
                    $newList = array_combine($keyArr , $valArr);
                    //获取模板id
                    $template_id = $newList[trim($title)];
                }
                #5、模板不存在 添加模板
                if (empty($template_id)) {
                    $res = self::$app->subscribe_message->addTemplate($tid , $kidList , $title);
                    if ($res['errcode'] == 0) $template_id = $res['priTmplId'];
                    else Commons::sRenderError($res['errmsg']);
                }
                #5、输出模板id
                Commons::sRenderSuccess('模板id' , $template_id);
            } catch (Exception $e) {
                $error = $e->getMessage();
                Commons::sRenderError($error);
            }
        } else {
            Commons::sRenderError('暂不支持该订阅消息！');
        }
    }
    /**
     * Comment: 微信公众号订阅消息模板id获取
     * Author: zzw
     * Date: 2021/2/18 14:34
     */
    private static function WeChatSubscriptionMessage(){
        global $_W;
        //生成配置信息
        /*$params = $_W['account']->account ? : get_object_vars($_W['account']);
        $config = [
            'app_id' => $params['key'],
            'secret' => $params['secret'],
            'token' => $params['token'],
            'response_type' => 'array',
        ];*/
        //基本参数信息获取
        $tid     = self::$WeChatSubscriptionNameList[self::$type];//tid获取
        $kidList = self::$WeChatSubscriptionParamsList[self::$type];//kid数组获取
        $title   = self::$WeChatSubscriptionTitleList[self::$type];//标题获取
        if (intval($tid) > 0) {
            try {
                //判断当前模板是否已经存在  存在则直接返回模板id
                $list = self::getTemplateList();//获取当前账户下面所有的模板列表
                if (is_array($list) && count($list) > 0) {
                    //建立一个以title为下标，template_id为值的新数组
                    $keyArr  = array_column($list , 'title');//数据的下标
                    $valArr  = array_column($list , 'priTmplId');//数据的值
                    $newList = array_combine($keyArr , $valArr);
                    //获取模板id
                    $template_id = $newList[trim($title)];
                }
                //模板不存在 添加模板
                if (empty($template_id)) {
                    $addRes = self::addTemplate(false,$tid , $kidList , $title);
                    if($addRes['errcode'] == 0) $template_id = $addRes['priTmplId'];
                    else Commons::sRenderError($addRes['errmsg']);
                }
                //输出模板id
                Commons::sRenderSuccess('模板id' , $template_id);
            } catch (Exception $e) {
                $error = $e->getMessage();
                Commons::sRenderError($error);
            }
        } else {
            Commons::sRenderError('暂不支持该订阅消息！');
        }
    }
    /**
     * Comment: 微信公众号订阅消息 —— 获取当前账户下面所有的模板列表
     * Author: zzw
     * Date: 2021/2/18 14:40
     * @param false $nowToken
     * @return array
     * @throws Exception
     */
    protected static function getTemplateList($nowToken = false){
        //token信息获取
        $accessToken = WeliamWeChat::getAccessToken($nowToken);
        //获取模板列表
        $http = "https://api.weixin.qq.com/wxaapi/newtmpl/gettemplate?access_token={$accessToken}";
        $list = curlGetRequest($http);
        //信息返回
        if($list['errcode'] == 40001){
            return self::getTemplateList(true);
        }else if($list['errcode'] != 0){
            throw new Exception($list['errmsg']);
        }else{
            return is_array($list['data']) ? $list['data'] : [];
        }
    }
    /**
     * Comment: 微信公众号订阅消息 —— 选用模板
     * Author: zzw
     * Date: 2021/2/18 14:42
     * @param false $nowToken
     * @param       $tid
     * @param       $kidList
     * @param       $title
     * @return array|void
     * @throws Exception
     */
    protected static function addTemplate($nowToken = false,$tid , $kidList , $title){
        //token信息获取
        $accessToken = WeliamWeChat::getAccessToken($nowToken);
        //请求选用模板
        $http = "https://api.weixin.qq.com/wxaapi/newtmpl/addtemplate?access_token={$accessToken}";
        $data = [
            'tid'          => $tid,
            'kidList'      => $kidList,
            'sceneDesc'    => $title,
        ];
        $headers = [
            "Content-type: application/json;charset='utf-8'" ,
            "Accept: application/json" ,
            "Cache-Control: no-cache" ,
            "Pragma: no-cache"
        ];
        $res = curlPostRequest($http,\GuzzleHttp\json_encode($data,JSON_UNESCAPED_UNICODE),$headers);
        //信息返回
        if($res['errcode'] == 40001){
            return self::addTemplate(true,$tid , $kidList , $title);
        }else if($res['errcode'] != 0){
            throw new Exception($res['errmsg']);
        }else{
            return is_array($res) ? $res : [];
        }
    }


/****** 模板消息发送(PS：发送消息操作中出现的所有错误只能返回不能直接输出) *******************************************/
    /**
     * Comment: 模板信息发送初始化
     * Author: zzw
     * Date: 2019/9/4 11:27
     * @param        $type
     * @param        $mid
     * @param        $data
     * @param int    $source
     * @param string $link
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function sendInit($type,$mid,$data,$source = 1,$link = ''){
        global $_W;
        #1、信息的处理
        if(array_key_exists('goods_name',$data)) $data['goods_name'] = self::goodsNameHandle($data['goods_name']);
        //mid 小于0，则发送信息给管理员
        if ($mid < 0) $mid = Setting::wlsetting_read('adminmid');
        #2、初始化基本信息
        self::$useTemplateType = Setting::wlsetting_read('use_template_type') ? : 0;//公众号消息类型 0=使用模板消息,1=使用订阅消息
        self::$source   = $source;
        self::$type     = $type;
        self::$sendData = $data;
        self::$sendLink = $link;
        self::$sendMid  = $mid;

        if(empty(self::$source)) self::$source = 1;
        self::getSupplementInfo(); //补充信息获取
        #2、用户信息判断
        if(!self::$member){
            return '用户不存在';
        }else{
            //小程序 判断用户是否存在form_id
            if(self::$source == 3 && empty(self::$member['form_id'])){
                //小程序模板信息 用户form_id不存在 判断是否存在openid 存在使用公众号发送模板信息
                if(!empty(self::$member['openid'])){
                    //用户没有小程序form_id  但是存在公众号openid 使用公众号发送模板信息
                    self::$source = 1;
                    //配置通过公众号模板消息 跳转到小程序的参数信息
                    $params = Setting::wlsetting_read('wxapp_config');
                    self::$miniProgram = [
                        'appid' => trim($params['appid']),
                        'pagepath' => str_replace('i='.$_W['uniacid'].'&','',explode('#/',self::$sendLink)[1]) ,//这里的路径是小程序path路径
                    ];
                }else{
                    //用户即没有小程序的form_id 同时也没有公众号openid 不进行任何操作
                    return '缺少小程序订阅消息授权模板id('.self::$WeChatSubscriptionTitleList[self::$type].')';
                }
            }
            //公众号订阅消息 判断是否存在form_id
            if(self::$source != 3 && self::$useTemplateType == 1 && empty(self::$member['form_id'])) return '缺少公众号订阅消息授权模板id('.self::$WeChatSubscriptionTitleList[self::$type].')';
        }
        #3、根据来源调用方法  端口类型：1=微信公众号(wechat)；2=H5（h5）；3=微信小程序(weapp)
        switch (self::$source) {
            case 1:
            case 2:
                //todo 确定公众号取消模板消息使用订阅消息后取消判断 固定使用weChatSubscriptionMessageSend方法发送公众号订阅消息
                if (self::$useTemplateType == 1) return self::weChatSubscriptionMessageSend();//公众号订阅消息
                else return self::weChatMessageSend();//公众号模板消息
                break;//微信公众号
            case 3:
                return self::weAppSubscriptionMessageSend();
                break;//微信小程序
            default:
                return '端口错误';
                break;
        }
    }
    /**
     * Comment: 微信公众号 —— 模板信息发送
     * Author: zzw
     * Date: 2019/9/4 11:27
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected static function weChatMessageSend(){
        global $_W;
        #1、获取设置信息
        $set = Setting::wlsetting_read(self::$setName);
        $setInfo = $set[self::$type]['wechat'];
        //修改备注信息和跳转链接
        if($setInfo['remark']) self::$sendData['remark'] = $setInfo['remark'] ? : self::$sendData['remark'];//修改备注信息
        if($setInfo['link'] && self::$source == 3) {
            self::$miniProgram['pagepath'] = $setInfo['link'];//修改小程序路径
        }else if($setInfo['link'] && self::$source != 3){
            self::$sendLink = h5_url($setInfo['link']) ;//修改公众号跳转链接
        }
        //判断是否存在模板配置信息
        if($setInfo['status'] != 1 || empty($setInfo['id'])) return '未开启'.self::$WeChatTempTitleList[self::$type].'模板消息';
        $temp_id = $setInfo['id'];
        #2、获取模板消息配置信息
        $typeArr = explode('_',self::$type);
        $configFunName = 'weChat';
        foreach($typeArr as $key => $val){
            $configFunName .= ucfirst($val);
        }
        self::$configFunName();//调用方法获取配置信息
        #3、生成主要配置信息
        if($_W['account']){
            $params = $_W['account']->account ? : get_object_vars($_W['account']);
        }else{
            $params = uni_fetch(self::$member['uniacid'])->account ? : get_object_vars($_W['account']);
        }
        $config = [
            'app_id' => $params['key'],
            'secret' => $params['secret'],
            'token' => $params['token'],
            'response_type' => 'array',
        ];
        $app = Factory::officialAccount($config);
        #4、发送模板消息信息
        try{
            //默认配置信息
            $sendData = [
                'touser'      => self::$member['openid'],
                'template_id' => trim($temp_id),
                'url'         => self::$sendLink,
                'data'        => self::$sendConfig
            ];
            //判断是否存在小程序跳转信息
            if(!empty(self::$miniProgram['appid']) && !empty(self::$miniProgram['pagepath'])){
                $sendData['miniprogram'] = self::$miniProgram;
            }
            return $app->template_message->send($sendData);
        }catch (Exception $e){
            $error = $e->getMessage();
            return $error;
        }
    }
    /**
     * Comment: 微信公众号 —— 订阅消息发送
     * Author: zzw
     * Date: 2021/2/23 14:56
     * @param false $nowToken
     * @return array|mixed|string|void
     */
    protected static function weChatSubscriptionMessageSend($nowToken = false){
        global $_W;
        //获取设置信息
        $set = Setting::wlsetting_read(self::$setName);
        $setInfo = $set[self::$type]['wechatSubscription'];
        if($setInfo['status'] != 1 || empty($setInfo['id'])) return '未开启'.self::$WeChatSubscriptionTitleList[self::$type].'模板消息';
        $temp_id = $setInfo['id'];
        //获取模板消息配置信息
        $typeArr = explode('_',self::$type);
        $configFunName = 'weChatSubscription';
        foreach($typeArr as $key => $val){
            $configFunName .= ucfirst($val);
        }
        self::$configFunName();
        //发送模板消息信息
        try{
            $accessToken = WeliamWeChat::getAccessToken($nowToken);  //token信息获取
            $http = "https://api.weixin.qq.com/cgi-bin/message/subscribe/bizsend?access_token={$accessToken}";
            $data = [
                'touser'      => trim(self::$member['openid']),
                'template_id' => trim($temp_id),
                'page'        => self::$sendLink,//跳转网页链接
                //'miniprogram' => json_encode(['appid'=>'','pagepath'=>'']),//跳转小程序设置
                'data'        => self::$sendConfig,
            ];
            $headers = [
                "Content-type: application/json;charset='utf-8'" ,
                "Accept: application/json" ,
                "Cache-Control: no-cache" ,
                "Pragma: no-cache"
            ];
            $res = curlPostRequest($http,\GuzzleHttp\json_encode($data,JSON_UNESCAPED_UNICODE),$headers);
            //信息返回
            if ($res['errcode'] == 40001) return self::weChatSubscriptionMessageSend(true);
            else if ($res['errcode'] != 0) throw new Exception($res['errmsg']);
            else return $res;
        }catch (Exception $e){
            $error = $e->getMessage();
            Util::wl_log('tempError', PATH_MODULE."log/",[$error,$data],'公众号订阅消息发送失败'); //写入日志记录
            return $error;
        }
    }
    /**
     * Comment: 微信小程序 —— 订阅消息发送
     * Author: zzw
     * Date: 2020/1/10 14:57
     * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected static function weAppSubscriptionMessageSend(){
        global $_W;
        #1、获取设置信息
        $set = Setting::wlsetting_read(self::$setName);
        $setInfo = $set[self::$type]['weappSubscription'];
        if($setInfo['status'] != 1 || empty($setInfo['id'])) return '未开启'.self::$WeAppSubscriptionTitleList[self::$type].'模板消息';
        $temp_id = $setInfo['id'];
        #2、获取模板消息配置信息
        $typeArr = explode('_',self::$type);
        $configFunName = 'weAppSubscription';
        foreach($typeArr as $key => $val){
            $configFunName .= ucfirst($val);
        }
        self::$configFunName();
        #3、生成主要配置信息
        $params = Setting::wlsetting_read('wxapp_config');
        $config = [
            'app_id' => trim($params['appid']),
            'secret' => trim($params['secret']),
            'response_type' => 'array',
        ];
        Util::wl_log('error', PATH_MODULE."payment/",$config,'小程序订阅消息发送'); //写入日志记录
        #4、发送模板消息信息trim($temp_id)
        $data = [
            'template_id' => trim($temp_id), // 所需下发的订阅模板id
            'touser'      => self::$member['wechat_openid'] ,     // 接收者（用户）的 openid
            'page'        => str_replace('i='.$_W['uniacid'].'&','',explode('#/',self::$sendLink)[1]),       // 点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转。
            'data'        => self::$sendConfig
        ];
        try{
            $app = Factory::miniProgram($config);
            return $app->subscribe_message->send($data);
        }catch (Exception $e){
            $error = $e->getMessage();
            return $error;
        }
    }
    /**
     * Comment: 补充信息获取
     * Author: zzw
     * Date: 2019/10/29 18:07
     */
    protected static function getSupplementInfo(){
        #1、获取用户信息
        self::$member = pdo_get(PDO_NAME."member",['id'=>self::$sendMid]
            ,['openid','uniacid','wechat_openid','nickname']);

        //判断用户是否存在当前端口的openid
        if(self::$source == 1 && empty(self::$member['openid'])){
            self::$source = 3;//公众号端口 但是用户没有openid 则使用小程序模板消息
        }else if (self::$source == 3 && empty(self::$member['wechat_openid'])){
            self::$source = 1;//小程序端口 但是用户没有wechat_openid 则使用公众号模板消息
        }

        #2、获取用户formid
        if(self::$source == 3 || self::$useTemplateType == 1) self::$member['form_id'] = self::getFromId();
    }
    /**
     * Comment: 获取小程序formid
     * Author: zzw
     * Date: 2019/9/5 13:42
     * @return mixed
     */
    protected static function getFromId(){
        //操作准备
        $table = PDO_NAME."formid";
        $mid = self::$sendMid;
        $tempType = self::$type;
        //获取当前用户最早一条formid
        if(self::$source == 3) $where = " type = 2 ";
        else $where = " type = 3 ";
        $info = pdo_fetch("SELECT id,form_id FROM ".tablename($table)." WHERE mid = {$mid} AND temp_type = '{$tempType}' AND {$where} ORDER BY create_time ASC ");
        //删除被获取的这条formid的信息
        pdo_delete($table,['id'=>$info['id']]);

        return $info['form_id'];
    }
    /**
     * Comment: 处理商品名称长度（微信支付限制不能超过128字节）
     * Author: zzw
     * Date: 2020/9/28 10:58
     * @param $name
     * @return string
     */
    protected static function goodsNameHandle($name){
        //微信支付body字节数量不能大于128   这里判断120字节   余下8字节用作字节保留数
        $byteLength = strlen($name);//获取商品名称的字节长度  单位：字节数
        $byteRestriction = 50;
        #1、判断当前名称是否大于120字节
        if($byteLength >= $byteRestriction){
            #2、大于：进行裁剪。urf-8编码中：中文 = 3字节   初始截取位置为120/3=40开始
            $start = 40;
            $newName = substr($name,0,$start);
            for($i=40;strlen($newName)<$byteRestriction;$i++){
                $newName = substr($name,0,$i);
            }
            #3、防止最后一个字符乱码 进行截取
            $newName = mb_substr($newName,0,(mb_strlen($newName) - 1));
            $newName .= '...';
        }

        return !empty($newName) ? $newName : $name;
    }
    /**
     * Comment: 根据限制长度进行字符串截取
     * Author: zzw
     * Date: 2021/4/28 15:42
     * @param int|string $str
     * @param int        $len
     * @return false|mixed|string
     */
    public static function subStr($str,$len = 20){
        if(mb_strlen($str) > $len){
            $str = mb_substr($str,0,$len);
        }

        return $str;
    }


/****** 微信公众号模板消息配置信息生成 *******************************************************************************/
    /**
     * Comment: 微信公众号模板配置信息 —— 订单支付成功
     * Author: zzw
     * Date: 2019/9/4 17:30
     */
    protected static function weChatPay(){
        self::$sendConfig = [
            'first'    => self::$sendData['first'] ,
            'keyword1' => self::$member['nickname'] ,//用户名
            'keyword2' => self::$sendData['order_no'] ,//订单号
            'keyword3' => self::$sendData['money'] . "元",//订单金额
            'keyword4' => self::$sendData['goods_name'] ,//商品信息
            'Remark'   => self::$sendData['remark'] ,
        ];
    }
    /**
     * Comment: 微信公众号模板配置信息 —— 订单发货提醒
     * Author: zzw
     * Date: 2019/9/4 17:36
     */
    protected static function weChatSend(){
        self::$sendConfig = [
            'first'    => self::$sendData['first'] ,
            'keyword1' => self::$sendData['order_no'] ,//订单编号
            'keyword2' => self::$sendData['express_name'] ,//物流公司
            'keyword3' => self::$sendData['express_no'] ,//物流单号
            'remark'   => self::$sendData['remark'] ,
        ];
    }
    /**
     * Comment: 微信公众号模板配置信息 —— 售后状态通知
     * Author: zzw
     * Date: 2019/9/4 17:40
     */
    protected static function weChatAfterSale(){
        self::$sendConfig = [
            'first'    => self::$sendData['first'] ,
            'keyword1' => self::$sendData['order_no'] ,//售后编号
            'keyword2' => self::$sendData['goods_name'] ,//商品信息
            'keyword3' => self::$sendData['status'] ,//状态
            'keyword4' => self::$sendData['time'] ,//时间
            'remark'   => self::$sendData['remark'] ,
        ];
    }
    /**
     * Comment: 微信公众号模板配置信息 —— 退款成功通知
     * Author: zzw
     * Date: 2019/9/4 17:47
     */
    protected static function weChatRefund(){
        self::$sendConfig = [
            'first'             => self::$sendData['first'] ,
            'orderProductPrice' => self::$sendData['money'] . "元",//退款金额
            'orderProductName'  => self::$sendData['goods_name'],//商品详情
            'orderName'         => self::$sendData['order_no'],//订单编号
            'remark'            => self::$sendData['remark'] ,
        ];
    }
    /**
     * Comment: 微信公众号模板配置信息 —— 业务处理结果通知
     * Author: zzw
     * Date: 2019/9/4 18:03
     */
    protected static function weChatService(){
        self::$sendConfig = [
            'first'    => self::$sendData['first'] ,
            'keyword1' => self::$sendData['type'],//业务类型
            'keyword2' => self::$sendData['content'] ,//业务内容
            'keyword3' => self::$sendData['status'],//处理结果
            'keyword4' => self::$sendData['time'] ,//操作时间
            'remark'   => self::$sendData['remark'] ,
        ];
    }
    /**
     * Comment: 微信公众号模板配置信息 —— 核销成功提醒
     * Author: zzw
     * Date: 2019/9/4 18:04
     */
    protected static function weChatWriteOff(){
        self::$sendConfig = [
            'first'    => self::$sendData['first'],
            'keyword1' => self::$sendData['goods_name'] ,//商品名称
            'keyword2' => self::$sendData['goods_num'] ,//商品数量
            'keyword3' => self::$sendData['time'] ,//核销时间
            'remark'   => self::$sendData['remark'],
        ];
    }
    /**
     * Comment: 微信公众号模板配置信息 —— 拼团结果通知
     * Author: zzw
     * Date: 2019/9/4 18:10
     */
    protected static function weChatFight(){
        self::$sendConfig = [
            'first'    => self::$sendData['first'],
            'keyword1' => self::$sendData['result'] ,//拼团结果
            'keyword2' => self::$sendData['detail'] ,//拼团详情
            'remark'   => self::$sendData['remark'],
        ];
    }
    /**
     * Comment: 微信公众号模板配置信息 —— 签到成功通知
     * Author: zzw
     * Date: 2019/9/4 18:25
     */
    protected static function weChatSign(){
        self::$sendConfig = [
            'first'    => self::$sendData['first'] ,
            'keyword1' => self::$sendData['user_name'] ,//用户名
            'keyword2' => self::$sendData['total'] ,//累计签到次数
            'remark'   => self::$sendData['remark'] ,
        ];
    }
    /**
     * Comment: 微信公众号模板配置信息 —— 积分变更提醒
     * Author: zzw
     * Date: 2021/1/26 14:12
     */
    protected static function weChatChange(){
        self::$sendConfig = [
            'first'    => self::$sendData['first'] ,
            'keyword1' => self::$sendData['old_number'] ,//原有积分
            'keyword2' => self::$sendData['current_number'] ,//现有积分
            'keyword3' => self::$sendData['time'] ,//变更时间
            'remark'   => self::$sendData['remark'] ,
        ];
    }
    /**
     * Comment: 微信公众号模板配置信息 —— 收益到账通知
     * Author: zzw
     * Date: 2021/1/29 10:13
     */
    protected static function weChatProfit(){
        self::$sendConfig = [
            'first'    => self::$sendData['first'] ,
            'keyword1' => self::$sendData['profit_money'] ,//收益金额
            'keyword2' => self::$sendData['profit_source'] ,//收益来源
            'keyword3' => self::$sendData['time'] ,//到账时间
            'remark'   => self::$sendData['remark'] ,
        ];
    }


/****** 微信公众号订阅消息配置信息生成 *******************************************************************************/
    /**
     * Comment: 微信公众号订阅消息配置信息 —— 订单支付成功
     * Author: zzw
     * Date: 2020/1/10 13:54
     */
    protected static function weChatSubscriptionPay(){
        self::$sendConfig = [
            'name1'             => ['value' => self::$member['nickname']],//客户名称
            'character_string8' => ['value' => self::$sendData['order_no']],//订单编号
            'amount10'          => ['value' => self::$sendData['money']."元"],//订单金额
            'thing11'           => ['value' => self::$sendData['goods_name']],//商品名称
        ];
    }
    /**
     * Comment: 微信公众号订阅消息配置信息 —— 订单发货提醒
     * Author: zzw
     * Date: 2020/1/10 13:58
     */
    protected static function weChatSubscriptionSend(){
        self::$sendConfig = [
            'character_string1' => ['value' => self::$sendData['order_no']],//订单号
            'thing17'           => ['value' => self::$sendData['express_name']],//物流公司
            'character_string4' => ['value' => self::$sendData['express_no']],//快递单号
        ];
    }
    /**
     * Comment: 微信公众号订阅消息配置信息 —— 售后状态通知
     * Author: zzw
     * Date: 2020/1/10 14:01
     */
    protected static function weChatSubscriptionAfterSale(){
        self::$sendConfig = [
            'character_string2' => ['value' => self::$sendData['order_no']],//订单编号
            'date3'             => ['value' => self::$sendData['time']],//申请时间
            'thing6'            => ['value' => self::$sendData['status']],//状态
            'thing5'            => ['value' => self::$sendData['remark']],//温馨提示
        ];
    }
    /**
     * Comment: 微信公众号订阅消息配置信息 —— 退款成功通知
     * Author: zzw
     * Date: 2020/1/10 14:09
     */
    protected static function weChatSubscriptionRefund(){
        self::$sendConfig = [
            'amount6'           => ['value' => self::$sendData['money']."元"],//退款金额
            'thing3'            => ['value' => self::$sendData['goods_name']],//商品名称
            'character_string2' => ['value' => self::$sendData['order_no']],//订单编号
        ];
    }
    /**
     * Comment: 微信公众号订阅消息配置信息 —— 业务处理结果通知
     * Author: zzw
     * Date: 2021/2/19 11:53
     */
    protected static function weChatSubscriptionService(){
        self::$sendConfig = [
            'thing1' => ['value' => self::$sendData['type']],//业务类型
            'thing2' => ['value' => self::$sendData['content']],//业务内容
            'thing3' => ['value' => self::$sendData['status']],//处理结果
            'time4'  => ['value' => self::$sendData['time']],//操作时间
            'thing5' => ['value' => self::$sendData['remark']],//温馨提示

        ];
    }
    /**
     * Comment: 微信公众号订阅消息配置信息 —— 核销成功提醒
     * Author: zzw
     * Date: 2020/1/10 14:20
     */
    protected static function weChatSubscriptionWriteOff(){
        self::$sendConfig = [
            'character_string1' => ['value' => self::$sendData['order_no']] ,//订单编号
            'time2'             => ['value' => self::$sendData['time']] ,//核销时间
            'thing4'            => ['value' => self::$sendData['goods_name']] ,//商品名
            'thing3'            => ['value' => self::$sendData['remark']] ,//备注
        ];
    }
    /**
     * Comment: 微信公众号订阅消息配置信息 —— 拼团结果通知
     * Author: zzw
     * Date: 2020/1/10 14:56
     */
    protected static function weChatSubscriptionFight(){
        self::$sendConfig = [
            'thing1'  => ['value' => self::$sendData['goods_name']] ,//拼团商品
            'thing2'  => ['value' => self::$sendData['nickname_string']] ,//拼团成员
            'thing3'  => ['value' => self::$sendData['result']] ,//拼团进度
        ];
    }
    /**
     * Comment: 微信公众号订阅消息配置信息 —— 签到成功通知
     * Author: zzw
     * Date: 2021/2/19 11:57
     */
    protected static function weChatSubscriptionSign(){
        self::$sendConfig = [
            'thing7' => ['value' => self::$sendData['user_name']],//用户名称
            'date2'  => ['value' => self::$sendData['sign_time']],//签到时间
            'thing3' => ['value' => self::$sendData['remark']],//温馨提示
        ];
    }
    /**
     * Comment: 微信公众号订阅消息配置信息 —— 积分变更提醒
     * Author: zzw
     * Date: 2021/2/19 13:56
     */
    protected static function weChatSubscriptionChange(){
        self::$sendConfig = [
            'character_string1' => ['value' => self::$sendData['change_num']],//变更数量
            'character_string2' => ['value' => self::$sendData['balance']],//积分余额
            'time4'             => ['value' => self::$sendData['time']],//变动时间
            'thing3'            => ['value' => self::$sendData['change_remark']],//变更原因
        ];
    }


/****** 微信小程序订阅消息配置信息生成 ***********************************************************************************/
    /**
     * Comment: 微信小程序订阅消息配置信息 —— 订单支付成功
     * Author: zzw
     * Date: 2020/1/10 13:54
     */
    protected static function weAppSubscriptionPay(){
        self::$sendConfig = [
            'name1'             => ['value' => self::$member['nickname']],//客户名称
            'character_string8' => ['value' => self::$sendData['order_no']],//订单编号
            'amount10'          => ['value' => self::$sendData['money']."元"],//订单金额
            'thing11'           => ['value' => self::subStr(self::$sendData['goods_name'])],//商品名称
        ];
    }
    /**
     * Comment: 微信小程序订阅消息配置信息 —— 订单发货提醒
     * Author: zzw
     * Date: 2020/1/10 13:58
     */
    protected static function weAppSubscriptionSend(){
        self::$sendConfig = [
            'character_string1' => ['value' => self::$sendData['order_no']],//订单号
            'thing17'           => ['value' => self::subStr(self::$sendData['express_name'])],//物流公司
            'character_string4' => ['value' => self::$sendData['express_no']],//快递单号
        ];
    }
    /**
     * Comment: 微信小程序订阅消息配置信息 —— 售后状态通知
     * Author: zzw
     * Date: 2020/1/10 14:01
     */
    protected static function weAppSubscriptionAfterSale(){
        self::$sendConfig = [
            'character_string2' => ['value' => self::$sendData['order_no']],//订单编号
            'date3'             => ['value' => self::$sendData['time']],//申请时间
            'thing6'            => ['value' => self::$sendData['status']],//状态
            'thing5'            => ['value' => self::subStr(self::$sendData['remark'])],//温馨提示
        ];
    }
    /**
     * Comment: 微信小程序订阅消息配置信息 —— 退款成功通知
     * Author: zzw
     * Date: 2020/1/10 14:09
     */
    protected static function weAppSubscriptionRefund(){
        self::$sendConfig = [
            'amount6'           => ['value' => self::$sendData['money']."元"],//退款金额
            'thing3'            => ['value' => self::subStr(self::$sendData['goods_name'])],//商品名称
            'character_string2' => ['value' => self::$sendData['order_no']],//订单编号
        ];
    }
    /**
     * Comment: 微信小程序订阅消息配置信息 —— 业务处理结果通知
     * Author: zzw
     * Date: 2021/2/19 11:53
     */
    protected static function weAppSubscriptionService(){
        self::$sendConfig = [
            'thing1' => ['value' => self::$sendData['type']],//业务类型
            'thing2' => ['value' => self::subStr(self::$sendData['content'])],//业务内容
            'thing3' => ['value' => self::$sendData['status']],//处理结果
            'time4'  => ['value' => self::$sendData['time']],//操作时间
            'thing5' => ['value' => self::subStr(self::$sendData['remark']) ? : '无提示'],//温馨提示

        ];
    }
    /**
     * Comment: 微信小程序订阅消息配置信息 —— 核销成功提醒
     * Author: zzw
     * Date: 2020/1/10 14:20
     */
    protected static function weAppSubscriptionWriteOff(){
        self::$sendConfig = [
            'character_string1' => ['value' => self::$sendData['order_no']] ,//订单编号
            'time2'             => ['value' => self::$sendData['time']] ,//核销时间
            'thing4'            => ['value' => self::subStr(self::$sendData['goods_name'])] ,//商品名
            'thing3'            => ['value' => self::subStr(self::$sendData['remark']) ? : '暂无备注'] ,//备注
        ];
    }
    /**
     * Comment: 微信小程序订阅消息配置信息 —— 拼团结果通知
     * Author: zzw
     * Date: 2020/1/10 14:56
     */
    protected static function weAppSubscriptionFight(){
        self::$sendConfig = [
            'thing1'  => ['value' => self::subStr(self::$sendData['goods_name'])] ,//拼团商品
            'thing2'  => ['value' => self::subStr(self::$sendData['nickname_string'])] ,//拼团成员
            'thing3'  => ['value' => self::$sendData['result']] ,//拼团进度
            'amount4' => ['value' => self::$sendData['money'] . "元"] ,//支付金额
        ];
    }
    /**
     * Comment: 微信小程序订阅消息配置信息 —— 签到成功通知
     * Author: zzw
     * Date: 2021/2/19 11:57
     */
    protected static function weAppSubscriptionSign(){
        self::$sendConfig = [
            'thing7' => ['value' => self::subStr(self::$sendData['user_name'])],//用户名称
            'date2'  => ['value' => self::$sendData['sign_time']],//签到时间
            'thing3' => ['value' => self::subStr(self::$sendData['remark']) ? : '暂无提示'],//温馨提示
        ];
    }
    /**
     * Comment: 微信小程序订阅消息配置信息 —— 积分变更提醒
     * Author: zzw
     * Date: 2021/2/19 13:56
     */
    protected static function weAppSubscriptionChange(){
        self::$sendConfig = [
            'character_string1' => ['value' => self::$sendData['change_num']],//变更数量
            'character_string2' => ['value' => self::$sendData['balance']],//积分余额
            'time4'             => ['value' => self::$sendData['time']],//变动时间
            'thing3'            => ['value' => self::subStr(self::$sendData['change_remark']) ? : '无原因'],//变更原因
        ];
    }


}

