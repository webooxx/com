<?php
namespace {
    $config = array(
        'ROUTER_KEY' => 'r',
        'APP_NAME'   => 'report',
    );
    require_once( dirname( __FILE__ ).'/core/framework.php' );
    Framework\Core::init( $config );
}
