<?php
#   MYSQL的模型基类
class MysqlModel extends Model{


        function connect( ){
            $db = $this->db[$this->dbKey];
            if( $db['DB_ENGINE'] == 'Mysql' ){
                $handle = @mysql_connect( $db['DB_HOST'] , $db['DB_USERNAME'] ,  $db['DB_PASSWORD'] , time() );
            }
            if($handle){
                @mysql_select_db( $db['DB_NAME'] ) or ox::l( '没有找到数据库!',99,99);
                @mysql_query('set names "'.$db['DB_DEFCHART'].'"') or  ox::l( '字符集设置错误!',2 );
            }else{
                ox::l( '无法连接到服务器!',99,99);
            }
            return $handle;
        }

        function query( $sql ){
            $sql = trim($sql);

            $resource = @mysql_query( $sql, $this->handle );
            #    每次查询过后清理查询参数条件


            if(!$resource){
                ox::l(mysql_error(),3);
                if(  $this->operate['debug'] == 1 ){
                    ox::l('MYSQL查询错误!',3,3);
                }else{
                    die('MYSQL查询错误!');
                }

            }

            $this->operate = array();
            if( is_resource( $resource ) ){

                while( $row = mysql_fetch_assoc( $resource )) { $result[] = $row; }
                return (array)$result;
            }
            return $resource;
        }

}