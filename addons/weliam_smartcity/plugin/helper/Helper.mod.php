<?php 
defined('IN_IA') or exit('Access Denied');

class Helper{
    /**
     * Comment: 获取帮助分类列表
     * Author: zzw
     * Date: 2019/9/18 9:45
     * @param bool $status
     * @return array|bool
     */
    public static function getTypeList ($status = true){
        global $_W;
        #1、根据条件获取帮助分类列表
        if ($status) {
            //获取所有符合条件分类信息
            $class = pdo_getall(PDO_NAME . "helper_type"
                , [ 'uniacid' => $_W['uniacid'] , 'status' => 1 ]
                , [ 'id' , 'name' , 'img' ] , '' , ' sort DESC ');
        } else {
            //仅获取存在问题并且符合条件的分类列表
            $class = pdo_fetchall("SELECT a.id,a.name,a.img FROM " . tablename(PDO_NAME . "helper_type")
                                  . " as a RIGHT JOIN " . tablename(PDO_NAME . "helper_question")
                                  . " as b ON a.id = b.type WHERE a.uniacid = {$_W['uniacid']} AND a.status = 1 GROUP BY a.id ORDER BY a.sort DESC");
        }
        #2、循环处理分类列表信息
        if (is_array($class) && count($class) > 0) {
            foreach ($class as $classK => &$classV) {
                $classV['img'] = tomedia($classV['img']);
            }
        }

        return $class;
    }






}
