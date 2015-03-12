<?php # 快捷函数

/**
 * 路径合并函数
 * @return string
 */
function J(){
    $args = func_get_args();
    foreach( $args as $item){  $temp[] = trim($item,"/"); }
    return ( preg_match('/^\//',$paths[0]) ? '/' : '' ).join('/', $temp);
}


/**
 * @param null $n
 * @return mixed
 */
function I( $n=Null ){
     $p1 = C('PATH_APP').'/'. C('DIR_INC').'/'.$n.'.class.php'  ;
     $p2 = C('PATH_COM').'/'. C('DIR_INC').'/'.$n.'.class.php'  ;
     if( realpath(  $p1 ) ){ return include_once( $p1 ); }
     if( realpath(  $p2 ) ){ return include_once( $p2 ); }
     echo 'Class '.$n.' is non-existent! in '.$p1;
 }