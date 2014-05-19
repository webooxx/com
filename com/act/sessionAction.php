<?php
/**
    会话控制器模块，适合用于在云环境下不支持本地存储 session  的场景
    @数据依赖 cfg/session.sql
*/
class sessionAction extends Action{
    

    function __construct(){
        #    禁止客户端访问
		S('SYS_VERIFY_FUNC','rbac:reject');
		#    会话超时设置
		C('SESSION_TIMEOUT', C('SESSION_TIMEOUT') ?  C('SESSION_TIMEOUT')  : 1800  );
	}


    
    #    读取/初始化cid
    function cid(){

        $cid = $_COOKIE['_wbsid'];
        
        #    刷新
        $info['id']  = (int)substr($cid , 32, 5);
        $info['cid'] = substr($cid , 0, 32);
        
        $flash = $this->flash($info);
        
        if( $flash ){
            return $flash;
        }else{
            $timeout = C('SESSION_TIMEOUT');
            $cid = md5(time()+rand(100,999)*100000);
            $data['cid'] = $cid;
            $data['val'] = json_encode( array('ts'=>$cid) );
            $data['timeout'] = time() + $timeout;
            M('session')->add( $data );
            $id = mysql_insert_id();
            setcookie ("_wbsid", $cid .str_pad( $id , 5 ,'0',STR_PAD_LEFT), time() + 999999);
            $data['id'] = $id;
            return $data;
        }        
    }

    #通用读取入口
    function val( $key = false , $val = false){
    
        $info = $this->cid();
        $where['id'] = $info['id'];
        $where['cid']= $info['cid'];
        $row = M('session')->where($where)->find0();
        $json = json_decode( $row['val'] ,true);
        if($key == false){            
            return $json;
        }else{
            
            if( $val == false ){
                return $json[$key];
            }else{
            
                if( is_null( $val ) ){
                    unset( $json[$key] );
                }else{
                     $json[$key] = $val;
                }
                $data['val'] = json_encode($json);
                return M('session')->where($where)->save( $data );
            }
        }
        return $val;
    }
    
    #    刷新
    function flash($info){
        $timeout = C('SESSION_TIMEOUT');
        
        $row = M('session')->where($info)->find0();
        if( $row && $row['timeout'] > time() ){
            $data['timeout'] = time()+$timeout;
            M('session')->where($info)->save($data);
            return $row;
        }
        return false;
    }
    
    #    定时清理过期
    function clear(){
        
    }
}