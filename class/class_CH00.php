<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_C200.php";
require_once "class_B.php";
require_once "class_file.php";
require_once "class_combo.php";

class info
{
    var $hit;
    var $domain;
    var $sale;
    var $start_date;

    function info ( $hit, $domain, $sale, $start_date )
    {
	$this->hit        = $hit;
	$this->sale       = $sale;
	$this->start_date = $start_date;
        switch ( $domain )
        {
           case "daum"   : $img = "10003.gif"; break;	
           case "ddm"    : $img = "10025.gif"; break;	
           case "auction": $img = "10001.gif"; break;	
           case "gmarket": $img = "10002.gif"; break;	
           case "nate"   : $img = "10013.gif"; break;	
           case "wizwid" : $img = "10048.gif"; break;	
           case "cyworld" : $img = "10065.gif"; break;	
        }
	$this->domain     = "<img src=images/$img>";
    }

}

class info2
{
    var $hit;
    var $sale;
    var $domain;
    var $referer;

    function info2( $hit, $sale, $domain, $referer )
    {
        $this->hit  = $hit;
        $this->sale = $sale;
        switch ( $domain )
        {
           case "daum"   : $img = "10003.gif"; break;	
           case "ddm"    : $img = "10025.gif"; break;	
           case "auction": $img = "10001.gif"; break;	
           case "gmarket": $img = "10002.gif"; break;	
           case "nate"   : $img = "10013.gif"; break;	
           case "wizwid" : $img = "10048.gif"; break;	
           case "cyworld" : $img = "10065.gif"; break;	
        }
	$this->domain     = "<img src=images/$img align=absmiddle>";
// echo $referer;
        $this->referer     = $referer;
    }
}

class class_CH00 extends class_top
{
   function get_supply_code( $id )
   {
      global $connect;

      $query = "select supply_code from products where product_id='$id'";
      $result = mysql_query ( $query, $connect );
      return mysql_fetch_array( $result );
   }
   
   // 상품 리스트 
   function CH00()
   {
      echo "<script>show_waiting();</script>";

      global $template, $page;

      $link_url = "?" . $this->build_link_url();     
    
	global $supply_code, $string_type, $string, $page; 
	$connect = sys_db_connect();

	// page 설정
        $page    = $_REQUEST["page"] ? $_REQUEST["page"] : 1;
        $starter = ($page - 1) * 20;
        $limit  .= " limit $starter, " . _line_per_page; 

	// 총 개수 
	$line_per_page = _line_per_page;
        $starter = $page ? ($page-1) * $line_per_page : 0;

	// 상품명 검색

        //***********************************************************************
        // 전체 상품
        // group by ez_product_no => ez_product_no로 변경 - jk 2006.4.12
	$q         = "select ez_product_no,hit,domain from ez_counter where id='"._DOMAIN_."' group by ez_product_no";

	// ecstorm은 상품 순
	if ( _DOMAIN_ == "ecstorm" || _DOMAIN_ == "ezadmin" )
	    $q .= " order by ez_product_no desc ";
	else
            $q .= " order by hit desc ";

        $q .= "   limit $starter, $line_per_page";

        $r    = mysql_query( $q, $connect );
        while ( $d = mysql_fetch_array ( $r ) )
        {
		// 사이트별 조회
                // group by product_no 삭제
		$query = " select *, sum(hit) tot_hit, hit
		             from ez_counter 
		            where id='" . _DOMAIN_ . "' and ez_product_no='$d[ez_product_no]'
		            group by ez_product_no, domain order by tot_hit desc";
		// list query
//echo $query;
		$result = mysql_query( $query, $connect );

		// 판매 정보 저장
		while ( $data = mysql_fetch_array ( $result ) )
		{
// echo $data[crdate];
		  $sale_cnt = $this->get_sale_cnt( $data[ez_product_no], $data[domain], $data[crdate] );

                  if ( $data[domain] )
		      $counter{$data[ez_product_no]}{"infos"}[] = new info( $data[tot_hit], $data[domain], $sale_cnt, $data[crdate] );
		}
        }


        //************************************************************
	// sort 함..
        if ( $counter )
	foreach ( $counter as $key => $value )
	  rsort ( $counter{$key}{"infos"} );

        // *************************************************************
        // 
        // 총 개수를 query함
        // 
	$query_cnt = "select count(distinct ez_product_no) cnt 
                        from ez_counter 
                       where id='"._DOMAIN_."'";

	$result  = mysql_query( $query_cnt, $connect );
	$data    = mysql_fetch_array ( $result );
	$total_rows = $data[cnt];

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";

        echo "<script>hide_waiting();</script>";
   }

   function reset()
   {
      global $template, $market, $ez_product_no, $connect, $sys_connect, $userid, $id;
      $sys_connect = sys_db_connect();
      $query = "delete from ez_counter where domain='$market' 
				and id='$userid' 
				and ez_product_no='$ez_product_no'";
      mysql_query ( $query, $sys_connect );

      $this->redirect ( "?template=CH01&id=$id" );	
   }

   // 상품 상세 
   function CH01()
   {
      global $template, $id, $referer, $product_no, $connect;
      $link_url = "?" . $this->build_link_url();     
 
      // 상세 정보 가져온다
      $product_data = class_C200::get_detail( $id );
 
      // counter 정보를 가져온다.
      $sys_connect = sys_db_connect();
      $query = "select sum(hit) hit,domain,crdate,referer from ez_counter where ez_product_no='$id' and id='" . _DOMAIN_. "' 
                 group by domain, crdate order by crdate desc";
      $result = mysql_query ( $query, $sys_connect );

      while ( $data = mysql_fetch_array ( $result ) )
      {
          $cnt = $this->get_sale_cnt( $id, $data[domain], $data[crdate],1); // date_type: 1을 설정
          if ( $cnt == 0) $cnt = "";
          // $cnt = $cnt ? $cnt : 0;
	  $counter{$data[crdate]}{$data[domain]} = new info2( $data[hit], $cnt, $data[domain], $data[referer] );
      }
 
      /////////////////////////////////////////////////////////////////
      // 
      $strXML = "<graph   caption='조회 그래프' shownames='1' showValues='1' xAxisName='' yAxisName='Hits' numberPrefix='' formatNumber='1' formatNumberScale='0' thousandSeperator=',' decimalPrecision='0' rotateNames='1'  numdivlines='4' baseFontSize='11'>";
      $strXML2 = "<graph   caption='매출 그래프' shownames='1' showValues='1' xAxisName='' yAxisName='Sales' numberPrefix='' formatNumber='1' formatNumberScale='0' thousandSeperator=',' decimalPrecision='0' rotateNames='1'  numdivlines='4' baseFontSize='11'>";

      $strCategory        = "<categories>"; 
      $strDataset4Daum    = "<dataset seriesName='다음' color='0099FF' showValues='0'>";
      $strDataset4Ddm     = "<dataset seriesName='동대문' color='66CC66' showValues='0'>";
      $strDataset4Auction = "<dataset seriesName='옥션' color='C4C23B' showValues='0'>";
      $strDataset4Gmarket = "<dataset seriesName='지마켓' color='33CC11' showValues='0'>";
      $strDataset4Nate    = "<dataset seriesName='네이트' color='C43B75' showValues='0'>";
      $strDataset4Wizwid  = "<dataset seriesName='위즈위드' color='CCCCCC' showValues='0'>";
      $strDataset4Cyworld = "<dataset seriesName='싸이월드' color='373737' showValues='0'>";
      
      // 매출 chart
      $strDataset4Daum2    = "<dataset seriesName='다음' color='0099FF' showValues='0'>";
      $strDataset4Ddm2     = "<dataset seriesName='동대문' color='66CC66' showValues='0'>";
      $strDataset4Auction2 = "<dataset seriesName='옥션' color='C4C23B' showValues='0'>";
      $strDataset4Gmarket2 = "<dataset seriesName='지마켓' color='33CC11' showValues='0'>";
      $strDataset4Nate2    = "<dataset seriesName='네이트' color='C43B75' showValues='0'>";
      $strDataset4Wizwid2  = "<dataset seriesName='위즈위드' color='CCCCCC' showValues='0'>";
      $strDataset4Cyworld  = "<dataset seriesName='싸이월드' color='373737' showValues='0'>";

      $i = 0; 
	
      if ( $counter )
      foreach ( $counter as $key=>$value )
      {
          $strCategory        .= "<category name='$key' />";

          // 조회 
          $strDataset4Daum    .= "<set value='" . $counter{$key}{"daum"}->hit . "'/>";
          $strDataset4Ddm     .= "<set value='" . $counter{$key}{"ddm"}->hit . "'/>";
          $strDataset4Auction .= "<set value='" . $counter{$key}{"auction"}->hit . "'/>";
          $strDataset4Gmarket .= "<set value='" . $counter{$key}{"gmarket"}->hit . "'/>";
          $strDataset4Nate    .= "<set value='" . $counter{$key}{"nate"}->hit . "'/>";
          $strDataset4Wizwid  .= "<set value='" . $counter{$key}{"wizwid"}->hit . "'/>";
          $strDataset4Cyworld .= "<set value='" . $counter{$key}{"cyworld"}->hit . "'/>";

          // 매출
          // $val = $counter{$key}{"daum"}->sale ? $counter{$key}{"daum"}->sale : 0;
          $val = $counter{$key}{"daum"}->sale;
          $strDataset4Daum2    .= "<set value='$val'/>";

          // $val = $counter{$key}{"ddm"}->sale ? $counter{$key}{"ddm"}->sale : 0;
          $val = $counter{$key}{"ddm"}->sale;
          $strDataset4Ddm2     .= "<set value='$val'/>";

          // $val = $counter{$key}{"auction"}->sale ? $counter{$key}{"auction"}->sale : 0;
          $val = $counter{$key}{"auction"}->sale;
          $strDataset4Auction2 .= "<set value='$val'/>";

          // $val = $counter{$key}{"gmarket"}->sale ? $counter{$key}{"gmarket"}->sale : 0;
          $val = $counter{$key}{"gmarket"}->sale;
          $strDataset4Gmarket2 .= "<set value='$val'/>";

          // $val = $counter{$key}{"nate"}->sale ? $counter{$key}{"nate"}->sale : 0;
          $val = $counter{$key}{"nate"}->sale;
          $strDataset4Nate2    .= "<set value='$val'/>";

          $val = $counter{$key}{"wizwid"}->sale;
          $strDataset4Wizwid2  .= "<set value='$val'/>";

	  // cyworld
          $val = $counter{$key}{"cyworld"}->sale;
          $strDataset4Cyworld2 .= "<set value='$val'/>";
          $i++;
      }

      $strCategory         .= "</categories>"; 
      $strDataset4Daum     .= "</dataset>";
      $strDataset4Ddm      .= "</dataset>";
      $strDataset4Auction  .= "</dataset>";
      $strDataset4Gmarket  .= "</dataset>";
      $strDataset4Nate     .= "</dataset>";
      $strDataset4Wizwid   .= "</dataset>";
      $strDataset4Cyworld  .= "</dataset>";

      // 매출 chart
      $strDataset4Daum2     .= "</dataset>";
      $strDataset4Ddm2      .= "</dataset>";
      $strDataset4Auction2  .= "</dataset>";
      $strDataset4Gmarket2  .= "</dataset>";
      $strDataset4Nate2     .= "</dataset>";
      $strDataset4Wizwid2   .= "</dataset>";
      $strDataset4Cyworld2  .= "</dataset>";

      $strXML = $strXML . $strCategory . $strDataset4Daum . $strDataset4Ddm . $strDataset4Auction . $strDataset4Gmarket . $strDataset4Nate. $strDataset4Wizwid . $strDataset4Cyworld . "</graph>";

      $strXML2 = $strXML2 . $strCategory . $strDataset4Daum2 . $strDataset4Ddm2 . $strDataset4Auction2 . $strDataset4Gmarket2 . $strDataset4Nate2. $strDataset4Wizwid2 . $strDataset5Cyworld2 . "</graph>";

      //----------------------------------------------------
      //
      // 매출 정보 보여줌
      //
      // echo "<textarea rows=10 cols=80>$strXML2</textarea>";

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }


   function get_counter_list( &$max_row, $page, $download=0 )
   {
	global $supply_code, $string_type, $string, $page; 
	$connect = sys_db_connect();

	// page 설정
        $page    = $_REQUEST["page"] ? $_REQUEST["page"] : 1;
        $starter = ($page - 1) * 20;
        $limit  .= " limit $starter, " . _line_per_page; 

	// 총 개수 
	$query_cnt = "select count(*) cnt ";
	$query     = " select *, sum(hit) tot_hit, hit ";

	$debug = 0;
	$domain = $debug ? "whales" : _DOMAIN_;
	$option    = "from ez_counter 
                     where id='$domain' group by product_no";

 // echo $query_cnt, $option;

        // 총 개수를 query함
	$result  = mysql_query( $query_cnt . $option, $connect );
	$data    = mysql_fetch_array ( $result );
	$max_row = $data[cnt];


	
	// list query
	$result = mysql_query( $query . $option, $connect );


	return $result;
   }   
  
   // 카운터에 관련된 정보 가져온다 
   function get_counter_info( $id, &$product_name )
   {
	global $connect;
	
	// org_id를 query해 와야 함
	$query = "select org_id, name from products where product_id='$id'";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );

//echo $query;

	if ( $data[org_id] )
        {
		$product_id = $data[org_id];
        }
        $product_name = $data[name];
   }

   // date_type : 1: 하루만 조회
   //             0: 해당일 보다 큰거나 같은 날 조회
   function get_sale_cnt( $product_id, $domain, $crdate, $date_type=0 )
   {
       global $connect;

       // product_id를 가져온다
       $query = "select product_id from products where ( org_id='$product_id' or product_id='$product_id' )";
       $result = mysql_query( $query, $connect );
       $i = 0;
       while ( $data = mysql_fetch_array ( $result ))
          $products .= $i++ ? ",'$data[product_id]'" : "'$data[product_id]'";

       //*********************************************
       // 상품이 없는 경우는 0을 return함
       if ( !$products ) return 0;

       //*********************************************
       // 도메인에 따라 다른 로직을 탄다
       switch ( $domain )
       {
           case "auction":
              $query = "select count(*) cnt from orders where product_id in ($products) 
                           and shop_id in ('10001','10101') and collect_date";
              break;
           case "gmarket":
              $query = "select count(*) cnt from orders where product_id in ($products) 
                           and shop_id in ('10002','10102') and collect_date";
              break;
           case "daum":
              $query = "select count(*) cnt from orders where product_id in ($products) 
                           and shop_id='10003' and collect_date";
              break;
           case "ddm":
              $query = "select count(*) cnt from orders where product_id in ($products) 
                           and shop_id='10025' and collect_date";
              break;
           case "wizwid":
              $query = "select count(*) cnt from orders where product_id in ($products) 
                           and shop_id='10048' and collect_date";
              break;
           default :
              return 0;
       }

       $query .= $date_type ? "='$crdate'" : ">='$crdate'";

// echo $query . "<br>";
// exit;
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array( $result );

// echo "cnt->" . $data[cnt] . "<br>";

       return $data[cnt];
   }
}
?>
