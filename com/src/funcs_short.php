<?php #    快捷方式

function A( $m = '' ){ 
    $route = ox::route( array('m'=>$m) );
    ox::l( '调用模块: '.$route['m'] );
    return ox::m( $route );
}
function C( $n ,$v = NULL ){
    return ox::c($n,$v);
}