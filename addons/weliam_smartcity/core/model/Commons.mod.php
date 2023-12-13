<?php
defined('IN_IA') or exit('Access Denied');

class Commons {
    /**
     * Comment: 操作成功输出方法
     * Author: zzw
     * Date: 2019/7/16 9:35
     * @param string $message
     * @param array  $data
     */
    public function renderSuccess ($message = '操作成功' , $data = []){
        exit(json_encode(array(
                             'errno'   => 0 ,
                             'message' => $message ,
                             'data'    => $data,
                         )));
    }
    /**
     * Comment: 操作失败返回内容
     * Author: zzw
     * Date: 2019/7/16 9:36
     * @param string $message
     * @param array  $data
     */
    public function renderError ($message = '操作失败' , $data = []){
        exit(json_encode(array(
                             'errno'   => 1 ,
                             'message' => $message ,
                             'data'    => $data,
                         )));
    }
    /**
     * Comment: 操作成功输出方法
     * Author: zzw
     * Date: 2019/7/16 9:35
     * @param string $message
     * @param array  $data
     */
    public static function sRenderSuccess ($message = '操作成功' , $data = []){
        exit(json_encode(array(
                             'errno'   => 0 ,
                             'message' => $message ,
                             'data'    => $data,
                         )));
    }
    /**
     * Comment: 操作失败返回内容
     * Author: zzw
     * Date: 2019/7/16 9:36
     * @param string $message
     * @param array  $data
     */
    public static function sRenderError ($message = '操作失败' , $data = []){
        exit(json_encode(array(
                             'errno'   => 1 ,
                             'message' => $message ,
                             'data'    => $data,
                         )));
    }
    /**
     * Comment: 距离转换
     * Author: zzw
     * Date: 2019/12/17 11:38
     * @param int $distance 距离数值
     * @return string
     */
    public static function distanceConversion($distance){
        if($distance > 0){
            if ($distance > 9999998) {
                $newDistance = " ";
            } else if ($distance > 1000) {
                $newDistance = (floor(($distance / 1000) * 10) / 10) . "km";
            } else {
                $newDistance = round($distance) . "m";
            }
        }
        return !empty($newDistance) ? $newDistance : '';
    }
    /**
     * Comment: 获取社群信息
     * Author: zzw
     * Date: 2019/12/17 17:09
     * @param int $id
     * @return array
     */
    public static function getCommunity($id,$title = '入群'){
        $info = pdo_get(PDO_NAME . "community" , [ 'id' => $id] , ['id', 'communname','systel','commundesc' , 'communimg' , 'communqrcode' ]);
        if($info){
            $data = [
                'title'        => $title ,
                'community_id' => $info['id'] ? : '' ,
                'name'         => $info['communname'] ? : '' ,
                'introduce'    => $info['commundesc'] ? : '' ,
                'imgUrl'       => tomedia($info['communimg']) ? : '' ,
                'qrcodeUrl'    => tomedia($info['communqrcode']) ? : '' ,
                'phone'        => $info['systel'] ? : '' ,
                'community'    => '' ,
            ];
        }else{
            $data = [];
        }
        return $data;
    }
    /**
     * Comment: 提现时间处理
     * Author: zzw
     * Date: 2021/3/15 11:44
     * @param int|string $lastTime     时间戳
     * @param int|string $day      天数
     * @return array|int[]
     */
    public static function handleTime($lastTime,$day){
        $time = time();
        $lastTime = $lastTime + ($day * 86400);//下一次申请提现应该在本时间之后
        if ($lastTime > $time) {
            $distance = $lastTime - $time;
            $d = floor($distance / 86400);//天
            $h = floor($distance % 86400 / 3600);//时
            $i = floor((($distance % 86400) % 3600) / 60);//分
            $str = "提现申请频率为{$day}天/次。请于";
            $d > 0 && $str .= $d . '天';
            $h > 0 && $str .= $h . '时';
            $i > 0 && $str .= $i . '分';
            if ($d < 1) {
                $s = floor(((($distance % 86400) % 3600) % 60));//分
                $s > 0 && $str .= $s . '秒';
            }
            $str .= '后进行提现申请';

            return [
                'status'=>1,
                'str'=>$str
            ];
        }
        return ['status'=>0];
    }







}
