<?php

// #    直接在内存中存取数据
// public static function shmop($n,$v = NULL,$delete = false){
//     $shmid = shmop_open($n, "w", 0, 102400);
//     if( is_null($v) ){
//         $back = shmop_write($shmid, $v, 0);
//     }else{
//         $size = shmop_size($shmid);
//         $back = shmop_read($shmid, 0, $size);
//     }
//     if( $delete ){
//         shmop_delete($shmid);
//         shmop_close($shmid);
//     }
//     return $back;
// }

// #    效率函数

// function log4j($msg) { echo "[".date('Y-m-d H:i:s')."]".( C('SYS_COMMAND_MOD') ?  $msg."\n" : '<p>'.$msg.'</p>' );}
// function o2a($obj){ $result = array(); if(!is_array($obj)){ if($var = get_object_vars($obj)){ foreach($var as $key => $value){ $result[$key] = o2a($value); } } else{ return $obj; } } else{ foreach($obj as $key => $value){ $result[$key] = o2a($value); } } return $result; }
//  #    路径合并函数
// function joinp(){
//     $paths = func_get_args();
//     foreach( $paths as $item){  $temp[] = trim($item,"/"); }
//     return ( preg_match('/^\//',$paths[0]) ? '/' : '' ).join('/', $temp);
// }



// require('dev/Model.php');
// require('dev/MysqlModel.php');
// require('dev/CsvModel.php');







// function C( $n = NULL,$v = NULL ){ return ox::cfg($n,$v);}
// function F( $n , $v = NULL){
//     $n = explode('::', $n);
//     if( count($n)>1 ){
//         return is_null($v) ? A($n[0])->get( $n[1] ) : A($n[0])->put( $n[1] );
//     }else{
//         return is_null($v) ? file_get_contents( $n[0] ) : file_put_contents( $n[0] , $v );
//     }
// }
// function I( $n=Null ){
//     $p1 =  realpath( J( C('PATH_APP'), C('DIR_INC'),$n.'.class.php') );
//     $p2 =  realpath( J( C('PATH_MVC'), C('DIR_INC'),$n.'.class.php') );
//     if( $p1 ){ return include_once( $p1 ); }
//     if( $p2 ){ return include_once( $p2 ); }
//     echo 'Class '.$n.' is non-existent!';
// }
// function J(){ $args = func_get_args(); return  call_user_func_array(array('ox', 'joinp'), $args );}
// function M( $table = Null, $type = Null ){ return Model::getInstace( $table , $type); }
// function S( $n = NULL,$v = NULL ){ return ox::set($n,$v);}
