<?php
#    MYSQL数据模型类
class MysqlModel extends Model{

    #    自动构建SQL
    public function __call( $act,$args = array() ){





        #    执行：增
    case 'add';

        break;
        #    执行：删
    case 'del' :

            break;
            #    执行：改
            case 'save':
                if( $arg ){ $this->data($arg); }
                if( empty( $this->operate['where'] ) ){ $this->error('SQL查询错误！不允许无条件修改！'); return $this; }
                foreach ($this->operate['data'] as $k => $v) {  $kvs[] = '`'.$k.'` = \''.$v.'\''; }
                $sql[] = 'UPDATE';
                $sql[] = $this->operate['table'];
                $sql[] = 'SET';
                $sql[] = implode(',', $kvs);
                $sql[] = 'WHERE '.$this->operate['where'];
                $sql[] = empty( $this->operate['limit'] ) ? ' LIMIT 1 ' : 'LIMIT ' .$this->operate['limit'];
                return $this->query( implode(' ',$sql) );
            break;
            #    执行：查，默认：LIMIT 1
            case 'find':
                return $this->findAll( $arg ? $arg : '1' );
            break;
            case 'find0':
                $data =  $this->findAll( $arg ? $arg : '1' );
                return is_array($data) ? $data[0] : $data;
            break;
            #    执行：查，默认所有
            case 'findAll':
                if( $arg ){ $this->limit($arg); }
                $sql[] = 'SELECT';
                $sql[] = empty($this->operate['field']) ? '*' : $this->operate['field'] ;
                $sql[] = 'FROM';
                $sql[] = $this->operate['table'];
                $sql[] = $this->operate['where']  ? 'WHERE '   .$this->operate['where'] : '';
                $sql[] = $this->operate['group']  ? 'GROUP BY '.$this->operate['group'] : '';
                $sql[] = $this->operate['order']  ? 'ORDER BY '.$this->operate['order'] : '';
                $sql[] = $this->operate['limit']  ? 'LIMIT '   .$this->operate['limit'] : '';
                $sql[] = $this->operate['having'] ? 'HAVING '  .$this->operate['having'] : '';
                return $this->query( implode(' ',$sql) );
            break;

            #    快捷：统计
            case 'count':
                $this->field( 'COUNT('. ($arg?$arg:'*')  . ') AS `count` ' );
                $count = $this->findAll();
                return (int)$count[0]['count'];
            break;

            default:
                $this->operate[$act] = $arg;
            break;
        }
return $this;
}
}
