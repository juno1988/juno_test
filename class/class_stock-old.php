<?
/* ------------------------------------------------------------

	Name : class_stock.php
	Desc : 재고관리 모듈
    Date : 2009.7.28        

주요 클래스
모든 입,출,재고 조정은 다음의 function을 타야 함
class_stock::stock_in( $product_id, $qty )
    : call이 되는 시점 입고
    : curent_stock 증가, stock_tx: 증가, 없으면 등록(reg_stock(product_id,type:in,out,trans)
    
class_stock::stock_out( $product_id, $qty )
    : call이 되는 시점 출고
    
class_stock::trans( $product_id, $qty )
    : call이 되는 시점 배송

class_stock::cancel_trans( $product_id, $qty )
    : call이 되는 시점 배송 취소

get_current_stock        
get_ready_stock

get_location( $product_id )
    : 상품의 전체 로케이션을 구한다.
    : str_product => 'loc1','loc2'
    : list        => array의 형태

2009.10.10
    ret 반품 여부
    bad 불량 여부 추가하기로 함.
    current_stock: 로케이션 별로 1개씩
    stock_tx     : 일 상품,로케이션,ret,bad별 1개 
    stock_tx     : 작업 이력을 모두 남김
------------------------------------------------------------ */


class class_stock
{
    //******************************************
    // arr_info 값을 초기화 한다.
    function init( &$arr_info )
    {
        $_now  = date('Y-m-d');     
        
        $arr_info[location] = $arr_info[location] ? $arr_info[location] : "";
        //if ( !is_int($arr_info[ret]) ) $arr_info[ret]  = "";
        //if ( !is_int($arr_info[bad]) ) $arr_info[bad]  = "";
        $arr_info[fromdate] = $arr_info[fromdate] ? $arr_info[fromdate] : $_now;  
        $arr_info[todate]   = $arr_info[todate]   ? $arr_info[todate]   : $_now;  
    }
    
    //
    // 현 재고 출력 - 상품 코드만 넣으면 정상 상태의 재고 수량의 합을 출력
    //
    function get_current_stock( $product_id )
    {
        global $connect;
        $query  = "select sum(stock) stock from current_stock where product_id='$product_id' and bad=0";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        return $data[stock];
    }
    
    // 
    // 현 재고 출력
    // 상세 설명은 구글 doc에 처리 
    function get_current_stock_all( $arr_info )
    {
        global $connect;
        $this->init( &$arr_info );
               
        $query = "select * from current_stock ";
        
        $is_where = 0;        
        if ( $arr_info[product_id] )
        {
            $query   .= !$is_where ? " where " : "";
            $is_where = 1;
            $query   .= " product_id = '$arr_info[product_id]'";
        }
        
        if ( $arr_info[location] )
        {
            $query   .= !$is_where ? " where " : "and";
            $is_where = 1;
            $query   .= " location = '$arr_info[location]'";
        }
        
        $result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_array( $result ) )
        { 
            // data 초기화
            $arr_return[$data[location]][ $data[bad] ] = array( 
                success     => 1
                ,product_id => $data[product_id]
                ,location   => $data[location]
                ,bad        => $data[bad]
                ,stock      => $data[stock]
            );
        }     
        
        // 초기화
        foreach ( $arr_return as $key => $val )
        {
            for ( $i=0; $i<2; $i++ )
            {
                if ( !isset( $arr_return[$key][$i] ) )
                {
                    // data 초기화
                    $arr_return[$key][ $i ] = array( 
                        success     => 0
                        ,product_id => ""
                        ,location   => ""
                        ,bad        => $i
                        ,stock      => 0
                    );
                }
            }   
        }
           
        return $arr_return;
    }
    
    //***********************************************
    // 현 재고 이동 이력을 날짜별로 조회 함
    // stock_tx 를 조회 함
    // 2009.10.19 - jk
    function get_detail( $arr_info )
    {
        global $connect;        

        $query = "select * from stock_tx ";

        // option
        $option = "";

        if ( $arr_info[fromdate] )
            $option .= ($option?" and ":"") . " crdate >= '$arr_info[fromdate]'";

        if ( $arr_info[todate] )
            $option .= ($option?" and ":"") . " crdate <= '$arr_info[todate]' ";
       
        if ( $arr_info[product_id] )
            $option .= ($option?" and ":"") . " product_id = '$arr_info[product_id]'";
        
        if ( $arr_info[location] )
            $option .= ($option?" and ":"") . " location = '$arr_info[location]'";
        
        if ( $arr_info[bad] )
            $option .= ($option?" and ":"") . " bad = '$arr_info[bad]'";
        
        if( $option )
            $query .= " where " . $option;
            
        $query .= " order by crdate desc, product_id, bad, location ";
        $result = mysql_query( $query, $connect );

        $arr_return = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            $arr_return[] = array( 
                    crdate      => $data[crdate] 
                    ,product_id => $data[product_id] 
                    ,location   => $data[location] 
                    ,bad        => $data[bad     ] 
                    ,in         => $data[stockin ] 
                    ,out        => $data[stockout] 
                    ,trans      => $data[trans   ] 
                    ,arrange    => $data[arrange ] 
                    ,movein     => $data[movein  ] 
                    ,moveout    => $data[moveout ] 
                    ,retin      => $data[retin   ] 
                    ,retout     => $data[retout  ]  );
        }
        
        return $arr_return;
    }
    
    //
    // 일별 총 합..
    function get_sum( $arr_info )
    {
        global $connect;        
    
        //query
        $query = "select sum(stockin ) as stockin ,
                         sum(stockout) as stockout,
                         sum(trans   ) as trans   ,
                         sum(arrange ) as arrange ,
                         sum(movein  ) as movein  ,
                         sum(moveout ) as moveout ,
                         sum(retin   ) as retin   ,
                         sum(retout  ) as retout  
                    from stock_tx ";

        // option
        $option = "";

        if ( $arr_info[fromdate] )
            $option .= ($option?" and ":"") . " crdate >= '$arr_info[fromdate]'";

        if ( $arr_info[todate] )
            $option .= ($option?" and ":"") . " crdate <= '$arr_info[todate]' ";
       
        // 필수
        $option .= ($option?" and ":"") . " product_id = '$arr_info[product_id]'";
        
        if ( $arr_info[location] )
            $option .= ($option?" and ":"") . " location = '$arr_info[location]'";
        
        // 필수
        $option .= ($option?" and ":"") . " bad = '$arr_info[bad]'";
        
        if( $option )
            $query .= " where " . $option;
            
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_assoc($result);
        
        // 정상 데이터 초기화
        $arr_return[$arr_info[bad]] = array( 
                product_id  => $arr_info[product_id] 
                ,location   => $arr_info[location] 
                ,in         => $data[stockin ] 
                ,out        => $data[stockout] 
                ,trans      => $data[trans   ] 
                ,arrange    => $data[arrange ] 
                ,movein     => $data[movein  ] 
                ,moveout    => $data[moveout ] 
                ,retin      => $data[retin   ] 
                ,retout     => $data[retout  ]  );

        return $arr_return;
    }
    
    // 입고
    function stock_in ( $arr_info )
    {
        $arr_info["type"] = "in";
        $this->stock_manage( $arr_info );   
    }
    
    // 입고
    function stock_out ( $arr_info )
    {
        $arr_info["type"] = "out";
        $this->stock_manage( $arr_info );   
    }
    
    // 배송
    function trans ( $arr_info )
    {
        $arr_info["type"] = "trans";
        $this->stock_manage( $arr_info );   
    }
    
    // 조정
    function arrange ( $arr_info )
    {
        $arr_info["type"] = "arrange";
        $this->stock_manage( $arr_info );   
    }
    
    // 반품 입고
    function stock_retin ( $arr_info )
    {
        $arr_info["type"] = "retin";
        $this->stock_manage( $arr_info );   
    }
    
    // 반품 출고
    function stock_retout ( $arr_info )
    {
        $arr_info["type"] = "retout";
        $this->stock_manage( $arr_info );   
    }
    // 반품 출고
    function movein ( $arr_info )
    {
        $arr_info["type"] = "movein";
        $this->stock_manage( $arr_info );   
    }
    // 반품 출고
    function moveout ( $arr_info )
    {
        $arr_info["type"] = "moveout";
        $this->stock_manage( $arr_info );   
    }
    
    //****************************
    // date: 2009-7-28
    // 입고 이력
    //function stock_in( $product_id, $qty, $location="" )
    // $arr_info는 product_id / $location / $bad / $return / $qty 를 갖는다.
    // product_id, location, bad는 필수값
    // arr_info[type] = "in / out / trans / arrange";
    function stock_manage( $arr_info )
    {
        global $connect;   
        $this->init( &$arr_info );        
        
        // arr_info[type] 이 arrange인 경우...현 재고에서 조정을 함.
        
        // ex) 현재 재고가 30인 경우
        //     arrange를 50으로 조정할 경우 arrange의 stock을 +20
        //     arrange를 20으로 조정할 경우 arrange의 stock을 -20
        if ( $arr_info[type] == "arrange" )
        {
            // bad값이 반듯이 와야 함
            $arr_stock   = $this->get_current_stock_all( $arr_info );
            $_stock      = $arr_stock[$arr_info[location]][$arr_info[bad]][stock];            
            
            // 조정개수 구하기
            // 현재고 10, 조정11 => +1
            // 현재고 10, 조정 9 => -1
            $arr_info[qty] = $arr_info[qty] - $_stock;
        }
        
        $this->begin( $arr_info, $arr_info[type] );        // stock_tx_history에 값 등록
        $this->reg_stock( $arr_info, $arr_info[type] );    // stock_tx, current_stock 에 등록        
        
        // 금일날짜 지정..
        $_date = date('Y-m-d');        
        
        //-------------------------
        // stock_tx 처리
        // stock은 작업 개수 ex) 3개 입고 => stock: 3 / type: in
        $query = "update stock_tx 
                    set stock      = stock + $arr_info[qty]
                  where crdate     = '$_date' 
                    and type       = '$arr_info[type]' 
                    and bad        = '$arr_info[bad]'
                    and ret        = '$arr_info[ret]'
                    and location   = '$arr_info[location]'
                    and product_id = '$arr_info[product_id]'";
                            
        mysql_query( $query, $connect );
        
        //-------------------------
        // current_stock 처리
        // 이미 current_stock table에는 값이 있음.  
        // out,trans,retout은 current_stock의 stock을 차감..
        if ( $arr_info['type'] == "out" 
          || $arr_info['type'] == "trans" 
          || $arr_info['type'] == "retout" 
          || $arr_info['type'] == "moveout" 
          )
            $arr_info[qty] = $arr_info[qty] * -1;
                  
        $query = "update current_stock 
                     set stock      = stock + $arr_info[qty] 
                   where product_id = '$arr_info[product_id]'
                     and location   = '$arr_info[location]'
                     and bad        = '$arr_info[bad]'";                     
        mysql_query( $query, $connect );
    }
    
    
    //*******************************************
    // date: 2009.7.29 - jk
    // 당일 tx 자료가 있는지 check함
    // location추가 - 2009.10.9 - jk
    // location이 없을 경우 기본값 Def
    // bad : 기본값 정상
    // ret : 반품 기본은 정상 입고
    function reg_stock( $arr_info )
    {
        global $connect;
        
        // 초기화
        $this->init(&$arr_info);
        
        print_r ( $arr_info );
        
        
        $_date    = date('Y-m-d');
        
        // stock_tx 처리
        $query = "select count(*) cnt 
                    from stock_tx 
                   where type       = '$arr_info[type]' 
                     and location   = '$arr_info[location]' 
                     and product_id = '$arr_info[product_id]' 
                     and crdate     = '$_date'
                     and bad        = $arr_info[bad]";
                     
        if ( $arr_info[ret] != "" )
            $query .= " and ret     = $arr_info[ret]";
        
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_assoc( $result );
        
        echo "\n\n cnt: $data[cnt] \n\n";
        
        // data가 없을 경우 등록..
        if ( $data[cnt] == 0 )
        {
            $arr_info[ret] = $arr_info[ret] ? $arr_info[ret] : 0;            
            $query = "insert into stock_tx 
                         set product_id = '$arr_info[product_id]'
                            ,type       = '$arr_info[type]'
                            ,crdate     = '$_date'
                            ,stock      = 0
                            ,location   = '$arr_info[location]'
                            ,bad        = $arr_info[bad]
                            ,ret        = $arr_info[ret]";   
            mysql_query( $query, $connect );  
     
            //================================================
            // current_stock 처리
            // stock_tx가 없을때만 current_stock여부를 check함 
            // stock_tx가 있으면 무조건 current_stock도 있음   
            // 해당일의 stock_tx가 없다면 금일의 current_stock도 없음
            $arr_stock = $this->get_current_stock_all( $arr_info );                    
            
            // arr_info의 qty값 조정..
            if ( $arr_info['type'] == "out" 
            || $arr_info['type'] == "trans" 
            || $arr_info['type'] == "retout" 
            )
                $arr_info[qty] = $arr_info[qty] * -1;
                    
            // 조회 성공 여부 check
            if ( $arr_stock[$arr_info[location]][$arr_info[bad]][success] )
            {
                $query = "update current_stock 
                             set stock      = stock + $arr_info[qty];
                           where product_id = '$arr_info[product_id]'                             
                             and location   = '$arr_info[location]'                                 
                             and bad        = $arr_info[bad]";
            }
            else
            {
                $query = "insert into current_stock 
                             set product_id = '$arr_info[product_id]'
                                 ,location  = '$arr_info[location]'
                                 ,stock     = $arr_info[qty]
                                 ,bad       = $arr_info[bad]";
            }
            mysql_query( $query, $connect );  
        }
    }
    
    //==========================================
    //
    // 상태가 7인 상품의 개수를 차감한다.
    function get_ready_stock( $product_id, $location="" )
    {
        global $connect;
                
        $str_location = "";
        if ( $location )
        {
            $str_location = "'$location'";  
        }
        else
        {
            $arr_location = $this->get_location( $product_id );   
            $str_location = $arr_location['str'];
        }
        
        $query = "select sum(a.qty) tot_qty
                    from order_products a, orders b
                   where a.order_seq  = b.seq
                     and b.status     = 7 
                     and a.location   in ( $str_location )
                     and a.product_id = '$product_id'
                     and a.order_cs not in (1,2,3,4)";
    
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        return $data[tot_qty] ? $data[tot_qty] : 0;
    }
    
    // 실재고 - 배송 예정
    function get_real_stock( $product_id )
    {
        $arr_info = array();
        $arr_info[product_id] = $product_id;
        $arr_info[bad]        = 0; // 정상품
        $arr_info[ret]        = 0; // 정상품
        return $this->current_stock( $arr_info ) - $this->get_ready_stock( $product_id );   
    }
        
    
    //****************************
    // date: 2009-7-28
    // 출고 이력
    function cancel_trans( $product_id, $qty, $location="Def" )
    {
        global $connect;
        $this->reg_stock( $product_id, "trans", $location );  
        $this->begin($product_id, $qty*-1, "trans", $location, "CS배송처리 취소");      
        $_date = date('Y-m-d');        
        
        // stock_tx 처리
        $query = "update stock_tx 
                     set stock     = stock-$qty 
                   where crdate    = '$_date' 
                     and type      = 'trans' 
                     and location  = '$location'
                     and product_id= '$product_id'";
        mysql_query( $query, $connect );
        
        // current_stock 처리
        $query = "update current_stock set stock=stock+$qty 
                   where crdate     = '$_date' 
                     and product_id = '$product_id'
                     and location   = '$location'";
        mysql_query( $query, $connect );
    }    
    
    
    
    //****************************
    // stock transaction을 저장한다.
    // 2009.7.30 - jk
    // 2009.10.7 메모 추가 - jk
    // function begin( $product_id, $qty, $job , $location="Def", $memo = "")
    // 무조건 쌓는다.
    function begin( $arr_info, $job )
    {
        global $connect;
        
        // current stock의 개수를 가져온다.
        // $stock = $this->get_current_stock( $arr_info );
        
        // 입력
        $query = "insert stock_tx_history 
                     set product_id = '$arr_info[product_id]'
                        ,crdate     = Now()
                        ,qty        = $arr_info[qty]
                        ,job        = '$job'
                        ,memo       = '$arr_info[memo]'
                        ,location   = '$arr_info[location]'
                        ,bad        = '$arr_info[bad]'
                        ,ret        = '$arr_info[ret]'
                        ,stock      = '$stock'
                        ,owner      = '" . $_SESSION[LOGIN_NAME] . "'";  
        mysql_query( $query, $connect ); 
    }
    
    // 상품의 로케이션 array를 가져온다.
    // date: 2009.10.9 - jk
    // $arr_location['list']: array를 가져옴
    // $arr_location['str'] : "loc1","loc2","loc3" 의 식으로 가져옴
    function get_location( $product_id )
    {
        global $connect;
        
        $query = "select distinct location from current_stock where product_id='$product_id'";
        $result = mysql_query( $query, $connect );
        
        $arr_location = array();
        $str_location = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_location['list'][] = $data[location];   
            $str_location .= $str_location ? "," : "";
            $str_location .= "'$_loc'";
        }
        $arr_location['str'] = $str_location;
        return $arr_location;
    }
    
    // 
    // $locatoin정보가 없으면 전체를 다 가져오면 됨.
    function get_tx_history( $product_id, $is_org, $location="" )
    {
        global $connect;
        
        // 전체 로케이션을 구한다.
        $str_location = "";
        if ( !$location )
        {
            $arr_location = $this->get_location( $product_id ); 
            $str_location = $arr_location['str'];
        }
        else
            $str_location = "'$location'";
        
        // history를 구한다.
        $query    = "select * from stock_tx_history 
                      where product_id = '$product_id' 
                        and location in ( $str_location )
                      order by crdate desc";
        $result   = mysql_query($query,$connect);
        $arr_data = array();
        $cnt      = 0;
        $arr_data['cnt'] = 0;
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_data['list'][] = $data;   
            $cnt++;
        }   
        $arr_data['cnt'] = $cnt;
        
        return $arr_data;
    }
    
    // stock_tx에서 해당 상품코드에 대해 날짜별 과거자료를 조회한다.
    function get_date_stock($product_id, $bad, $page, $page_lines, &$total)
    {
        global $connect;
        
        $query = "select crdate,
                         sum(stockin ) as stockin ,
                         sum(stockout) as stockout,
                         sum(trans   ) as trans   ,
                         sum(arrange ) as arrange ,
                         sum(retin   ) as retin   ,
                         sum(retout  ) as retout  
                    from stock_tx 
                   where product_id = '$product_id' and
                         bad = $bad
                group by crdate";
        
        // 전체 수량
        $result = mysql_query($query, $connect);
        $total = mysql_num_rows($result);
        
        // 화면 표시 데이터
        $start = ($page-1) * $page_lines;
        $query .= " order by crdate desc limit $start, $page_lines";
        $result = mysql_query( $query, $connect );
        $arr_return = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            $arr_return[] = array( 
                crdate      => $data[crdate] 
                ,in         => ($data[stockin ] ? $data[stockin ] : "&nbsp;")
                ,out        => ($data[stockout] ? $data[stockout] : "&nbsp;")
                ,trans      => ($data[trans   ] ? $data[trans   ] : "&nbsp;")
                ,arrange    => ($data[arrange ] ? $data[arrange ] : "&nbsp;")
                ,retin      => ($data[retin   ] ? $data[retin   ] : "&nbsp;")
                ,retout     => ($data[retout  ] ? $data[retout  ] : "&nbsp;") );
        }
        return $arr_return;
    }

    // stock_tx에서 해당 상품코드에 대해 날짜별 과거자료를 다운로드한다.
    function get_date_stock_download($product_id, $bad)
    {
        global $connect;
        
        $query = "select crdate,
                         sum(stockin ) as stockin ,
                         sum(stockout) as stockout,
                         sum(trans   ) as trans   ,
                         sum(arrange ) as arrange ,
                         sum(retin   ) as retin   ,
                         sum(retout  ) as retout  
                    from stock_tx 
                   where product_id = '$product_id' and
                         bad = $bad
                group by crdate";
        $result = mysql_query($query, $connect);
        $arr_return = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            $arr_return[] = array( 
                crdate      => $data[crdate] 
                ,in         => ($data[stockin ] ? $data[stockin ] : "")
                ,out        => ($data[stockout] ? $data[stockout] : "")
                ,trans      => ($data[trans   ] ? $data[trans   ] : "")
                ,arrange    => ($data[arrange ] ? $data[arrange ] : "")
                ,retin      => ($data[retin   ] ? $data[retin   ] : "")
                ,retout     => ($data[retout  ] ? $data[retout  ] : "") );
        }
        return $arr_return;
    }

}
// end of class

?>
