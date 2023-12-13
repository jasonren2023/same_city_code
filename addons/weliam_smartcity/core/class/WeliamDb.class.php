<?php
defined('IN_IA') or exit('Access Denied');

class WeliamDb {

    /**
     * 生成表差异SQL
     * @param $table
     * @return array
     */
    static function table_upgrade($table) {
        $sqlarr = [];
        if (!empty($table['table'])) {
            $oldtable = self::get_table_schema($table['table']);
            if (empty($oldtable)) {
                //创建表
                $sqlarr[] = self::create_table($table);
            } else {
                //对比字段
                foreach ($table['fields'] as $fk => $field) {
                    if (empty($oldtable['fields'][$fk])) {
                        //字段不存在，增加字段
                        $sqlarr[] = ($field['increment'] == 1) ? self::table_field_edit($table['table'], $field, 1, $table['indexes']['PRIMARY']) : self::table_field_edit($table['table'], $field, 1);
                    } elseif (array_diff_assoc($table['fields'][$fk], $oldtable['fields'][$fk]) || array_diff_assoc($oldtable['fields'][$fk], $table['fields'][$fk])) {
                        //字段有变化，修改字段
                        $sqlarr[] = self::table_field_edit($table['table'], $field, 2);
                    }
                }
                //对比索引
                foreach ($table['indexes'] as $idx => $index) {
                    if ($idx == 'PRIMARY') {
                        continue;
                    }
                    if (empty($oldtable['indexes'][$idx])) {
                        //索引不存在，增加索引
                        $sqlarr[] = self::table_index_edit($table['table'], $index, 1);
                    } elseif (array_diff_assoc($table['indexes'][$idx], $oldtable['indexes'][$idx]) || array_diff_assoc($oldtable['indexes'][$idx]['fields'], $table['indexes'][$idx]['fields']) || array_diff_assoc($table['indexes'][$idx]['fields'], $oldtable['indexes'][$idx]['fields'])) {
                        //索引有变化，删除索引，新建索引
                        $sqlarr[] = self::table_index_edit($table['table'], $index, 2);
                        $sqlarr[] = self::table_index_edit($table['table'], $index, 1);
                    }
                }
                //多余索引，需要删除
                foreach ($oldtable['indexes'] as $oidx => $oindex) {
                    if (empty($table['indexes'][$oidx])) {
                        $sqlarr[] = self::table_index_edit($table['table'], $oindex, 2);
                    }
                }
                //对比存储引擎
                if ($table['engine'] != $oldtable['engine']) {
                    $sqlarr[] = "ALTER TABLE " . self::tablename($table['table']) . " ENGINE=" . $table['engine'] . ", ROW_FORMAT=DEFAULT;";
                }
            }
        }
        return $sqlarr;
    }

    /**
     * 根据条件查找表名
     * @param $table
     * @param string $tablepre
     * @param bool $haspre
     * @return array
     */
    static function get_tables_name($table, $tablepre = 'ims_', $haspre = false) {
        $tablenames = pdo_fetchall("SHOW TABLES LIKE :tablename", array(":tablename" => "%" . $table . "%"));
        if (!empty($tablenames)) {
            $tables = [];
            foreach ($tablenames as $item) {
                $table = end($item);
                $tables[] = $haspre ? $table : substr($table, strlen($tablepre));
            }
            return $tables;
        }
        return [];
    }

    /**
     * 根据表名生成数据库插入语句
     * @param $tablename
     * @param $uniacid
     * @param $start
     * @param $size
     * @return array|bool
     */
    static function get_table_insert_sql($tablename, $uniacid, $start, $size) {
        $data = '';
        $tmp = '';
        $sql = "SELECT * FROM {$tablename} WHERE `uniacid` = {$uniacid} LIMIT {$start}, {$size}";
        $result = pdo_fetchall($sql);
        if (!empty($result)) {
            foreach ($result as $row) {
                $tmp .= '(';
                foreach ($row as $k => $v) {
                    $value = str_replace(array('\\', "\0", "\n", "\r", "'", '"', "\x1a"), array('\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'), $v);
                    $tmp .= "'" . $value . "',";
                }
                $tmp = rtrim($tmp, ',');
                $tmp .= "),\n";
            }
            $tmp = rtrim($tmp, ",\n");
            $data .= "INSERT INTO {$tablename} VALUES \n{$tmp};\n";
            $datas = array(
                'data' => $data,
                'result' => $result,
            );

            return $datas;
        } else {
            return false;
        }
    }

    /**
     * 根据表名返回表的数据
     * @param string $tablename
     * @return array
     */
    static function get_table_schema($tablename = '') {
        $result = self::pdo()->fetch("SHOW TABLE STATUS LIKE '" . trim(self::tablename($tablename), "`") . "'");
        if (empty($result) || empty($result['Create_time'])) {
            return array();
        }
        $ret["table"] = $tablename;
        $ret["tablename"] = $result["Name"];
        $ret["charset"] = $result["Collation"];
        $ret["engine"] = $result["Engine"];
        $ret["increment"] = $result["Auto_increment"];
        $result = self::pdo()->fetchall("SHOW FULL COLUMNS FROM " . self::tablename($tablename));
        foreach ($result as $value) {
            $temp = array();
            $type = explode(" ", $value["Type"], 2);
            $temp["name"] = $value["Field"];
            $pieces = explode("(", $type[0], 2);
            $temp["type"] = $pieces[0];
            $temp["length"] = rtrim($pieces[1], ")");
            $temp["null"] = !($value["Null"] == "NO");
            $temp["signed"] = empty($type[1]);
            $temp["increment"] = $value["Extra"] == "auto_increment";
            if (!empty($value['Comment'])) {
                $temp["comment"] = $value["Comment"];
            }
            if ($value["Default"] != NULL) {
                $temp["default"] = $value["Default"];
            }
            $ret["fields"][$value["Field"]] = $temp;
        }
        $result = self::pdo()->fetchall("SHOW INDEX FROM " . self::tablename($tablename));
        foreach ($result as $value) {
            $ret["indexes"][$value["Key_name"]]["name"] = $value["Key_name"];
            $ret["indexes"][$value["Key_name"]]["type"] = $value["Key_name"] == "PRIMARY" ? "primary" : ($value["Non_unique"] == 0 ? "unique" : "index");
            $ret["indexes"][$value["Key_name"]]["fields"][] = $value["Column_name"];
        }
        return $ret;
    }

    /**
     * 返回完整表名
     * @param $table
     * @return string
     */
    private static function tablename($table) {
        return self::pdo()->tablename($table);
    }

    /**
     * 数据库操作类
     * @return DB|SlaveDb
     */
    private static function pdo() {
        return pdo();
    }

    /**
     * 生成创建表的SQL
     * @param $schema
     * @return string
     */
    private static function create_table($schema) {
        if (empty($schema)) {
            return '';
        }
        $sql = "CREATE TABLE IF NOT EXISTS " . self::tablename($schema['table']) . " (";
        //生成表的字段
        foreach ($schema['fields'] as $field) {
            $sql .= self::create_table_field($field);
            $sql .= ",";
        }
        //生成表的索引
        foreach ($schema['indexes'] as $index) {
            $sql .= self::create_table_index($index);
            $sql .= ",";
        }
        $sql = rtrim($sql, ",");

        $charset = substr($schema['charset'], 0, stripos($schema['charset'], "_"));
        $sql .= ") ENGINE={$schema['engine']} DEFAULT CHARSET={$charset};";

        return $sql;
    }

    /**
     * 生成操作字段的SQL段
     * @param $field
     * @return string
     */
    private static function create_table_field($field) {
        if (empty($field)) {
            return "";
        }
        $sql = "";
        $sql .= " `{$field['name']}` {$field['type']}";
        $sql .= !empty($field['length']) ? "({$field['length']})" : "";
        $sql .= !empty($field['signed']) ? "" : " UNSIGNED";
        $sql .= !empty($field['null']) ? (array_key_exists("default", $field) ? "" : " DEFAULT NULL") : " NOT NULL";
        $sql .= array_key_exists("default", $field) ? " DEFAULT '{$field['default']}'" : "";
        $sql .= !empty($field['increment']) ? " AUTO_INCREMENT" : "";
        $sql .= !empty($field['comment']) ? " COMMENT '{$field['comment']}'" : "";
        return $sql;
    }

    /**
     * 生成操作索引的SQL段
     * @param $index
     * @param string $type
     * @return string
     */
    private static function create_table_index($index, $type = 'ADD') {
        if (empty($index)) {
            return "";
        }
        $sql = "";
        $sql .= $index['type'] == 'primary' ? "PRIMARY KEY" : "KEY `{$index['name']}`";
        if ($type == 'ADD') {
            $sql .= " (`" . implode("`,`", $index['fields']) . "`)";
        }
        return $sql;
    }

    /**
     * 生成操作字段的SQL
     * @param string $tablename
     * @param $field
     * @param int $type
     * @return string
     */
    private static function table_field_edit($tablename = '', $field, $type = 1, $idx_field = []) {
        $sqlstr = ($type == 1) ? " ADD " : " MODIFY COLUMN ";
        $sql = "ALTER TABLE " . self::tablename($tablename) . $sqlstr . self::create_table_field($field);
        //特殊情况，增加主键字段时
        if ($type == 1 && $field['increment'] == 1) {
            $sql .= ", ADD " . self::create_table_index($idx_field, 'ADD');
        }
        $sql .= ";";
        return $sql;
    }

    /**
     * 生成操作索引的SQL
     * @param string $tablename
     * @param $field
     * @param int $type
     * @return string
     */
    private static function table_index_edit($tablename = '', $field, $type = 1) {
        $sqlstr = ($type == 1) ? " ADD " : " DROP ";
        $sql = "ALTER TABLE " . self::tablename($tablename) . $sqlstr . self::create_table_index($field, trim($sqlstr)) . ";";
        return $sql;
    }
}