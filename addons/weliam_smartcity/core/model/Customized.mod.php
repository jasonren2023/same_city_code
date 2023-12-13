<?php
/**
 * Comment: 定制信息判断
 * Author: zzw
 * Date: 2020/4/2
 * Time: 16:10
 */
defined('IN_IA') or exit('Access Denied');

class Customized{
    /**
     * Comment: 判断当前用户是否存在该定制功能的使用权
     * Author: zzw
     * Date: 2020/4/2 16:25
     * @param $name
     * @return bool
     */
    public static function init($name){
        if($name == 'allocation' || $name == 'pft147'){
            return 1;
        }
        $authinfo = Cloud::wl_syssetting_read('authinfo');
        $list = self::authList($name);
        return in_array($authinfo['code'],$list);
    }
    /**
     * Comment: 获取授权码列表
     * Author: zzw
     * Date: 2020/4/2 16:24
     * @param $name
     * @return array
     */
    protected static function authList($name){
        //定制信息授权列表  功能名称 => 【授权码数组】
        $list =  [
            //打印消息授权(568定制)
            'printing' => ['5AFBA464F822EAE4959837E979D30171'],
            //自定义装修 用户信息组件(881定制)
            'diy_userInfo' => ['996C4795D9661D590400C0B0DC93A193'],
            //全民分销商|生成分销订单后解绑上下级|商户买单普通用户折扣|分销商自动提现     336定制
            'customized336' => ['69EF7FE62BAEBD7D23741FD76687EA83'],
            //测试服务商分账功能
            'allocation' => ['A321041C8EAD8108AF9D5AD50F442FC2'],
            //设置买单结算方式功能
            'payOlSetStatus' => ['178FBB14D1DB9A63F0EF6E18AD8DE5E7','569A3DCF87F3514FD5EBCE07D4E48F73','C4E6BF7978DA38FBDA080E17F06B74FE'],
            //收益到账模板消息通知
            'customized530'  => ['E42B66E240ED831B957640A4C7DB2B70','C4E6BF7978DA38FBDA080E17F06B74FE'],
            //幸运团
            'luckygroup' => ['1047AE3ECA2D800F7A1BA6BF1A1B05D3','09B945EDB70F2F1FF74D1859D2C4A7A8'],
            //余额返现
            'yuecashback' => ['C4E6BF7978DA38FBDA080E17F06B74FE','08E2DDB0633F37A36E122E287F3F409F','8A8A9A64918A37456A82F7669CFE2394','1047AE3ECA2D800F7A1BA6BF1A1B05D3'],
            //共享股东文本替换
            'distributionText' => ['4897239A840F136884F18CC32F72C60F'],
            //云收单-微信支付
            'yunmis160' => ['2A83006129E758100E2184B2E8DF26BF'],
            //147定制
            'pft147' => ['FFBB7D90AFC418BAF94310747ACC6815'],
            //转赠定制
            'transfergift' => ['AFFD1B0DF199A71EF6B2232E3B057E63','C4E6BF7978DA38FBDA080E17F06B74FE'],
            //掌上信息绑定商户和视频付费查阅
            'pocket1500' => ['C4E6BF7978DA38FBDA080E17F06B74FE','E16627FFC9A6CE60B82A1B7AF8214CA8','1047AE3ECA2D800F7A1BA6BF1A1B05D3'],
            //掌上信息全局推广功能
            'pocket140' => ['8A8A9A64918A37456A82F7669CFE2394'],
            //名片页面文本修改
            'citycard1503' => ['C4E6BF7978DA38FBDA080E17F06B74FE','A56EA6E46481D9AD18080EB2243083FB'],
            //1512上传
            'upfile1512' => ['BF749328FBA75ED8F56BA8B4BB92F2A8'],
            //1520商户二级页面
            'storecate1520' => ['B26A8E91BF4F990F2F8A16816F4C5C1C'],
            //074积分定制
            'integral074' => ['E8FB5DC83D1618526E4AEFA1F5FC998D'],
            //735隐藏内定
            'priest735' => ['452B79B26E82E151AB19C8A8765DD08C'],
            //1543语言包定制
            'language1543' => ['813C1C014FA2C93DCC19908C09609B9F'],
            //1510掌上信息转让功能
            'transfer1510' => ['C49FE7A1A9E6A4E2A2FF6CF2E28E3DED'],
            //442大学交友定制
            'university442' => ['5270E5F123F3AB4C19B4C1C23FE45DDB'],
            //559定制首页转个人中心
            'personal559' => ['018E9567D01BBCED5130746784A807EE'],
            //858定制会员优惠和累计分销
            'discount858' => ['9DCC289268BFE9411358E96BF0386D90','1047AE3ECA2D800F7A1BA6BF1A1B05D3'],
            //1045幸运抽奖抽奖码
            'luckycode1045' => ['1047AE3ECA2D800F7A1BA6BF1A1B05D3','C89C2B99DCC200771E6A2354899D759A'],
            //138定制团长分红
            'groupon138' => ['1047AE3ECA2D800F7A1BA6BF1A1B05D3','63E2984A22190E2B4794EB6F1518CF5E'],
            //商户红包
            'redpack527' => ['1047AE3ECA2D800F7A1BA6BF1A1B05D3','95FDFE5155EDF25D1EF152F83AF3DAD7']
        ];
        //默认授权码  拥有所有功能
        //$default = ['1047AE3ECA2D800F7A1BA6BF1A1B05D3'];
        $default = [];
        return array_merge($list[$name],$default);
    }
}












