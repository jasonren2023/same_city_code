<?php
defined('IN_IA') or exit('Access Denied');

class MysqlFunction {
    /**
     * Comment: [由于数据库权限问题，当前方法弃用]创建数据库方法 —— 距离获取(需要数据库存在lng，lat两个字段才能使用)
     * Author: zzw
     * Date: 2019/12/16 18:36
     * @param string $tableName 表名称（非全名；例如：citycard_lists）
     */
    public static function getDistance($tableName){
        $table = tablename(PDO_NAME.$tableName);
        $functionName = "get_distance_{$tableName}";
        //判断方法是否存在
        $isHave = pdo_fetch(" SHOW CREATE FUNCTION {$functionName};");
        if(!$isHave){
            $functionSql = "CREATE FUNCTION {$functionName}(object_lat VARCHAR(16),object_lng VARCHAR(16),object_id INT(11)) RETURNS FLOAT
                        BEGIN
                            DECLARE distances DOUBLE DEFAULT 0;
                            SELECT 
                                CASE 
                                    WHEN object_lat > 0 AND object_lng > 0 THEN ROUND(6378.138 * 2 * ASIN(
                                            SQRT(
                                                POW(SIN((object_lat * PI() / 180 - lat * PI() / 180) / 2),2) +
                                                    COS(object_lat * PI() / 180) * COS(lat * PI() / 180) *
                                                    POW(SIN((object_lng * PI() / 180 - lng * PI() / 180) / 2),2)
                                                )
                                            ) * 1000
                                        ) 
                                    ELSE 0
                                END INTO distances FROM ".$table." WHERE id = object_id;
                            RETURN distances;
                        END";
            pdo_fetch($functionSql);//建立方法
        }
        //$list = pdo_fetch("SELECT {$functionName}({$lat},{$lng},6)");//使用方法
        //pdo_fetch(" DROP FUNCTION {$functionName};");//删除方法
    }
    /**
     * Comment: [由于数据库权限问题，当前方法弃用]获取总数
     * Author: zzw
     * Date: 2019/12/17 10:37
     * @param string $tableName    表名称
     * @param int $field        条件字段（id）
     */
    public static function getCount($tableName,$field){
        $table = tablename(PDO_NAME.$tableName);
        $functionName = "get_count_{$tableName}";
        //判断方法是否存在
        $isHave = pdo_fetch(" SHOW CREATE FUNCTION {$functionName};");
        if(!$isHave){
            $functionSql = "CREATE FUNCTION {$functionName}(id int(11)) RETURNS INT
                        BEGIN
                            DECLARE count_num DOUBLE DEFAULT 0;
                            SELECT COUNT(*) INTO count_num  FROM ".$table." WHERE {$field} = id;
                            RETURN count_num;
                        END";
            pdo_fetch($functionSql);//建立方法
        }
    }
    /**
     * Comment: 设置隔离等级
     * Author: wlf
     * Date: 2020/01/02 16:42
     * @return string $level 隔离等级 （1 READ UNCOMMITTED 读未提交 2 READ COMMITTED 读提交 3 REPEATABLE READ 可重复读 4 SERIALIZABLE 串行化）
     */
    public function setTrans($level = 1){
        switch ($level){
            case 1:
                $levelset = 'READ UNCOMMITTED';
                break;
            case 2:
                $levelset = 'READ COMMITTED';
                break;
            case 3:
                $levelset = 'REPEATABLE READ';
                break;
            case 4:
                $levelset = 'SERIALIZABLE';
                break;
        }
        return pdo_query(" SET SESSION TRANSACTION ISOLATION LEVEL {$levelset}");
    }
    /**
     * Comment: 开启事务处理
     * Author: zzw
     * Date: 2019/8/15 11:47
     * @return bool
     */
    public function startTrans(){
        return pdo_query(" BEGIN ");
    }
    /**
     * Comment: 提交事务处理
     * Author: zzw
     * Date: 2019/8/15 11:47
     * @return bool
     */
    public function commit(){
        return pdo_query(" COMMIT ");
    }
    /**
     * Comment: 事务回滚
     * Author: zzw
     * Date: 2019/8/15 11:47
     * @return bool
     */
    public function rollback(){
        return pdo_query(" ROLLBACK ");
    }




}
