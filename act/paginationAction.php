<?php
/**
 * 分页代码控制器，需要自定义一个html页来处理
 *         $limit = A('pagination')->limit(20);   # 20 为一页显示的数据长度
 *         $htmls = A('pagination')->htmls( M('Tables')->where( $args ) , 'layout/pagination.html' ,5); # 5 为分页标记最大的显示长度
 */
class paginationAction extends Action{

   public $size = 20;
   public $page = 0;

  function limit( $size = 20 ){

      $pag = (int)$_GET['pag'];

      $this->size = $size;
      $this->page = max($pag,1);
      return ( max($pag-1,0) * $size ) .',' .( max($pag,1) * $size );
  }
  function htmls( $M, $html = 'pagination.html' , $max = 3){

    $count = $M->count() ;
    $page_count = ceil( $count/$this->size ) ;

    $this->assign('count', $count);
    $this->assign('this_page',$this->page);
    $this->assign('page_size',$this->size);
    $this->assign('page_count', $page_count );


    $ps = $this->page;
    $pe = $page_count;

    $pd = $pe-$ps;

    # 如果总数量超出了要显示的数量, 以当前页开始，到当前页+总显示数量为准
    if( $pd > $max ){
        $pe = $ps + $max;
    }
    if( ($ps+$max) >  $page_count){
      $ps = $page_count - $max;
    }
    $ps = max(1,$ps);
    $this->assign('ps',$ps);
    $this->assign('pe',$pe);

    return $this->fetch($html);
  }

}