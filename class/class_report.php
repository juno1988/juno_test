<?
/*
* class_report
*     desc: 리포트 생성
*     date: 2009.2.10 - jk.ryu
*/
include_once "class_top.php";
include_once "class_common.php";
include_once "class_newstat.php";

class class_report extends class_top
{
    var $m_arr_items = array();

    /*****
    * 리포트 출력 준비. 리포트 생성 클릭
    * date: 2009.2.13 - jk
    *     stat_report 초기화 후 값 추가
    *****/
    function init_report()
    {
	global $test;

	// 삭제
	$this->clear_db_items();	

	// 기본 정보 저장
	$arr_items = $this->save_default();

	// TOT_ORDER_CNT저장
	$this->stat_order( $arr_items );

    }

    /****
    * @brief: 주문에 관련된 report값 생성
    * @date : 2009.2.xx - jk
    ****/
    function stat_order( $arr_items )
    {
	global $connect;

	/****
	* order에 관련된 stat	
	* 배송 후 전체 주문의 매출
	****/
	$query = "select count(*) cnt, sum(qty) qty, sum(supply_price*qty+extra_supply_price) supply_price, status, order_cs 
                    from orders ";
	$query_opt .= " where " . $arr_items[date_type] . ">= '" . $arr_items[from_date] . " 00:00:00'";
	$query_opt .= " and   " . $arr_items[date_type] . "<= '" . $arr_items[to_date]   . " 23:59:59'";

	if ( $arr_items[shop_id] )
	    $query_opt .= " and shop_id= " . $arr_items[shop_id];

	$query .= $query_opt . " group by status, order_cs";

	
	$result = mysql_query( $query, $connect );

	$_arr = array();
	while ( $data   = mysql_fetch_array( $result ) )
	{
	    $_arr[] = array( 
		cnt          => $data[cnt], 
		status       => $data[status], 
		order_cs     => $data[order_cs], 
		qty          => $data[qty], 
		supply_price => $data[supply_price]  );
	}

	/****
	* 배송 관련 정산 
	****/
	$query = "select count(*) cnt, trans_who, pre_paid from orders";
	$query .= $query_opt . " group by trans_who, pre_paid ";
	// debug ( $query );
	$arr_trans = array();
	$result     = mysql_query( $query, $connect );
	while ( $data = mysql_fetch_array( $result ) )
	{
	    $arr_trans[] = array(
		trans_who => $data[trans_who],
		pre_paid  => $data[pre_paid],
		cnt       => $data[cnt]
	    );
	}

	/****
	* get 환경 설정
	****/
	$arr_config = class_common::get_config(1);

	// 1. tot_order_cnt 전체 주문 개수
	$_cnt = 0;
	foreach( $_arr as $data )
	{
	    $_cnt = $_cnt + $data[cnt];	
	}
	$this->input_db_item( "TOT_ORDER_CNT" , $_cnt ); 

	// 2. 발주 상품 수량 : tot_order_product_cnt 	
        $_cnt = 0;
	foreach( $_arr as $data )
	{
	    $_cnt = $_cnt + $data[qty];	
	}
	$this->input_db_item( "TOT_PRODUCT_CNT" , $_cnt ); 

	// 3. 배송 예정: 
	$this->input_db_item( "TOT_READY_TRANS" , "준비중" ); 

	//*****************
	// 4. 총 배송: TOT_TRANS_CNT / tot_trans_cnt
        $_cnt = 0;
	foreach( $_arr as $data )
	{
	    if ( $data[status] == 8 )
	        $_cnt = $_cnt + $data[cnt];	
	}
	$this->input_db_item( "TOT_TRANS_CNT" , $_cnt ); 

	// 5. 총 배송 상품
        $_cnt = 0;
	foreach( $_arr as $data )
	{
	    if ( $data[status] == 8 )
	        $_cnt = $_cnt + $data[qty];	
	}
	$this->input_db_item( "TOT_TRANS_PRODUCT_CNT" , $_cnt );

	// 6. 미배송: 상태가 7 이고 order_cs가 not 1,2,3,4,12
        $_cnt = 0;
	foreach( $_arr as $data )
	{
	    if ( $data[status] != 8 )
		if ( $data[order_cs] != 1
		 and $data[order_cs] != 2
		 and $data[order_cs] != 3
		 and $data[order_cs] != 4
		 and $data[order_cs] != 12
		)
	        $_cnt = $_cnt + $data[cnt];	
	}
	$this->input_db_item( "TOT_NOT_TRANS_CNT" , $_cnt );

	
	// 7. 미 배송 상품(tot_not_trans_product_cnt)
	$_cnt = 0;
	foreach( $_arr as $data )
	{
	    if ( $data[status] != 8 )
		if ( $data[order_cs] != 1
		 and $data[order_cs] != 2
		 and $data[order_cs] != 3
		 and $data[order_cs] != 4
		 and $data[order_cs] != 12
		)
	        $_cnt = $_cnt + $data[qty];	
	}
	$this->input_db_item( "TOT_NOT_TRANS_PRODUCT_CNT" , $_cnt );

	// 8. 총 취소(tot_cancel_cnt)
	// 취소 상태의 주문의 개수를 count함
	$_cnt = 0;
	foreach( $_arr as $data )
	{
	    if ( $data[order_cs] == 1
	      or $data[order_cs] == 2
	      or $data[order_cs] == 3
	      or $data[order_cs] == 4
	      or $data[order_cs] == 12
	    )
	    $_cnt = $_cnt + $data[cnt];	
	}
	$this->input_db_item( "TOT_CANCEL_CNT" , $_cnt );

	// 9. 총 교환(tot_change_cnt)
	$_cnt = 0;
	foreach( $_arr as $data )
	{
	    if ( $data[order_cs] == 5
	      or $data[order_cs] == 6
	      or $data[order_cs] == 7
	      or $data[order_cs] == 8
	      or $data[order_cs] == 13
	    )
	    $_cnt = $_cnt + $data[cnt];	
	}
	$this->input_db_item( "TOT_CHANGE_CNT" , $_cnt );

	$arr_income = array();
	// 10. 정산 예정금액(income_sale)
	// 백마진 + 
	$_val = 0;
	foreach( $_arr as $data )
	{
	    $_val = $_val + $data[supply_price];	
	}
	$arr_income[income_sale] = $_val;
	$this->input_db_item( "INCOME_SALE" , $_val);

	// 11. 선결제(income_trans_price)
	$_val =0; 
	foreach( $arr_trans as $data )
	{
	    if ( $data[pre_paid] == "선결제" )
	    {
	        $_val = $_val + $data[cnt];	
	    }
	}
	$arr_income[income_trans_price] = $_val * $arr_config[supply_deliv_price]; // 선결제 개수 * 선결제 금액
	$this->input_db_item( "INCOME_TRANS_PRICE" , $_val * $arr_config[supply_deliv_price]);
	$this->input_db_item( "INCOME_TRANS_CNT" , $_val);

	debug ( "income_trans_cnt:"  . $_val );
	debug ( "income_trans_price:" . $_val * $arr_config[supply_deliv_price] );

	// 12. 백마진(income_backmargine)
	$_val =0; 
	foreach( $arr_trans as $data )
	{
	    if ( $data[trans_who] == "착불" )
	    {
	        $_val = $_val + $data[cnt];	
	    }
	}
	$arr_income[income_backmargine] = $_val * $arr_config[back_deliv_price]; // 선결제 개수 * 선결제 금액
	$this->input_db_item( "INCOME_BACKMARGIN_PRICE" , $_val * $arr_config[back_deliv_price] );
	$this->input_db_item( "INCOME_BACKMARGIN_CNT" , $_val );

	// 13. 취소   (income_cancel)
	$_cnt   = 0;
	$_price = 0;
	foreach( $_arr as $data )
	{
	    if ( $data[order_cs] == 1
	      or $data[order_cs] == 2
	      or $data[order_cs] == 3
	      or $data[order_cs] == 4
	      or $data[order_cs] == 12
	    )
	    {
	        $_price = $_price + $data[supply_price]; // 취소 금액
	        $_cnt   = $_cnt   + $data[cnt];		 // 취소 개수	
	    }
	}
	$arr_income[income_cancel] = $_price;
	$this->input_db_item( "INCOME_CANCEL_CNT"   , $_cnt );
	$this->input_db_item( "INCOME_CANCEL_PRICE" , $_price );

	// 14. 총 매출(tot_income)
	// income_sale + income_trans_price + income_backmargine - income_cancel = tot_income
	$arr_price[tot_income] = $arr_income[income_sale] + $arr_income[income_trans_price] + $arr_income[income_backmargine] - $arr_income[income_cancel];
	$this->input_db_item( "TOT_INCOME" , $arr_price[tot_income]);

	// 15. 광고비 (promotion_expense)
	$_val = 0;
	$this->input_db_item( "PROMOTION_EXPENSE" , $_val );

	// 16. 택배비(tot_trans_expense)
	$_val =0; 
	foreach( $arr_trans as $data )
	{
	    if ( $data[trans_who] == "선불" )
	    {
	        $_val = $_val + $data[cnt];	
	    }
	}
	$this->input_db_item( "TOT_TRANS_EXPENSE" , $_val * $arr_config[pre_deliv_price]);
        $arr_price[tot_trans_expense] = $_val * $arr_config[pre_deliv_price];


	// 17. 원가
	// products, orders, stat_product와 join해야 함.
	$arr_price = array_merge( $arr_price, class_newstat::get_product_price());
	debug ( "원가: " . $arr_price[tot_org_price] );
	print_r ( $arr_price );
	$this->input_db_item( "TOT_ORG_EXPENSE" , $arr_price[tot_org_price]);

        // 18. 총 지출
        $arr_price[tot_expense] = $arr_price[tot_trans_expense] + $arr_price[tot_org_price];
	$this->input_db_item( "TOT_EXPENSE" , $arr_price[tot_expense]);

	// 19. 판매 수익
        $arr_price[tot_margin] = $arr_price[tot_income] - $arr_price[tot_expense];
	$this->input_db_item( "TOT_MARGIN" , $arr_price[tot_margin]);

	// 20. 마진율
	$arr_price[tot_rate_margin] = ceil( (($arr_price[tot_income] - $arr_price[tot_expense]) / $arr_price[tot_income]) * 100 );
	$this->input_db_item( "TOT_RATE_MARGIN" , $arr_price[tot_rate_margin]);

	// 21. 부가세
	$arr_price[tax] = $arr_price[tot_margin] * 0.1;
	$this->input_db_item( "TAX" , $arr_price[tax] );

	// 22. 실 수익
	$arr_price[real_margin] = $arr_price[tot_margin] - $arr_price[tax];
	$this->input_db_item( "real_margin" , $arr_price[real_margin] );

	// 23. 실 수익률
	echo "m: $arr_price[real_margin] / $arr_price[tot_income]";

	$arr_price[real_rate_margin] = ceil($arr_price[real_margin] / $arr_price[tot_income]*100);
	$this->input_db_item( "real_rate_margin" , $arr_price[real_rate_margin]);

/*
	// 11. 총 지출(tot_expense)
	$_val = 0;
	foreach( $_arr as $data )
	{
	    if ( $data[order_cs] == 1
	      or $data[order_cs] == 2
	      or $data[order_cs] == 3
	      or $data[order_cs] == 4
	      or $data[order_cs] == 12
	    )
	    $_val = $_val + $data[supply_price];	
	}
	debug ( "11 $_val" );
	$this->input_db_item( "TOT_EXPENSE" , $_val); 

	// 12. 판매 수익(tot_margin)
	// 총수입(tot_income) - 총 지출( 
*/

    }

    function save_default()
    {
	$arr_items = array();
	$items     = array( "date_type", "shop_id", "supply_id", "from_date", "to_date" );
	foreach ( $items as $item )
	{
	    global $$item;
	    $this->input_db_item( $item, $$item );  

	    $arr_items[$item] = $$item;
	}
	return $arr_items;
    }

    // 값 입력 
    function input_db_item( $code, $value )
    {
	global $connect;

	if ( $code )
	{
	    $query = "insert stat_report 
                         set code='" . strtoupper($code) . "', value='$value'";
	    mysql_query( $query, $connect );
	}
    }

    // 초기화
    function clear_db_items()
    {
	global $connect;
	$query = "truncate stat_report";
	mysql_query($query, $connect);	
    }

    // report에서 변경할 내용 등록
    function add_item( $arr_item )
    {
	return array_merge( $this->m_arr_items, $arr_item );
    }

    function build_report()
    {
	global $from_date, $to_date, $date_type, $shop_id;

	echo "aaa";
    }

    function get_report_items()
    {
	global $connect;

	$query = "select * from stat_report";
	$result = mysql_query( $query, $connect );
	return $result;
    }

    /*****
    * 요약 리포트 출력
    * date: 2009.2.12 - jk
    *****/
    function summary_report()
    {
	global $from_date, $to_date, $date_type;

        // step 1. 통계 데이터 계산
	$arr_item = array( 
			FROM_DATE   => $from_date,
			TO_DATE     => $to_date,
			SEARCH_TYPE => $date_type	
			);
	$this->m_arr_items = $this->add_item( $arr_item ); 

	//**************************
	// TOT_ORDER_CNT - 총 발주
	//
	$result = $this->get_report_items();
	while ( $data = mysql_fetch_array( $result ) )
	{
	    $this->m_arr_items = $this->add_item( array( $data['code'] => $data['value']) ); 
	}

	// step 2. 리포트 생성
	$out = $this->read_report("report_summary");
	$out = $this->conv( $out );
	echo $out;
    }

    /*****
    * read report
    * date: 2009.2.11 - jk
    *****/
    function read_report( $report_name )
    {
	$buffer = "";
	$handle = fopen("./template/" . $report_name . ".rpt", "r");	
	if ($handle) {
	    while (!feof($handle)) {
        	$buffer .= fgets($handle, 4096);
    	    }
    	    fclose($handle);
	}
	return $buffer;
    }

    // conv 
    function conv( $out )
    {
	foreach( $this->m_arr_items as $key=>$val )
	{
	    $arr_change["{" . $key . "}"] = is_numeric($val)?number_format($val):$val;
	}

	return strtr( $out, $arr_change );
    }

    // report name의 report file생성
    function write_report( $out, $report_name )
    {

    }
}

?>
