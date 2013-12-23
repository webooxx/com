<?php
        #    命令行模式,将参数完全复制到 $_GET 对象中去
        if( count($argv) > 1 ){
            $al = count($argv);
            ooxx::$configs['SYS_COMMAND_MOD'] = true;
            for($i=1;$i<$al;$i++){ $arg = explode('=',$argv[$i]);
                $_GET[$arg[0]]=$arg[1];
            }
            $_REQUEST = $_GET;
        }

?>
