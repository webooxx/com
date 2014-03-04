
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