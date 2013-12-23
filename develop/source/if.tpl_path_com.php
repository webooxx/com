<?php

        #    设定模板编译目录
        if( ooxx::$configs['TPL_ENGINE'] != 'none' ){
                if( !@mkdir( ooxx::$configs['PATH_COM'] , 0700)  ){
                    ooxx::$configs['PATH_COM'] = sys_get_temp_dir();
                }
        }
?>
