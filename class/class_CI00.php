<?
require_once "class_top.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_product.php";
require_once "class_file.php";

//////////////////////////////////////////////
// 상품 검색
class class_CI00 extends class_top
{
   var $table = "code_match";

    // 매칭정보 저장
    // date :2010.5.13 - jk
    function add_info()
    {
        global $connect, $shop_id, $shop_product_id, $shop_product_name, $shop_option, $product_id;
        
        $query = "select * from code_match where id='$product_id' and shop_id='$shop_id";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        $arr_info = array();
        
        if ( mysql_num_rows( $result ) )
        {
            $arr_info[error] = 1;
            $arr_info[msg]   = "이미 등록 되었습니다.";    
        }
        else
        {
            $query = "insert into code_match 
                        set id='$product_id'
                            ,shop_id     = '$shop_id'
                            ,shop_code   = '$shop_product_id'
                            ,shop_product_name   = '$shop_product_name'
                            ,shop_option = '$shop_option'
                            ,input_date  = Now()
                            ,input_time  = Now()
                            ,qty         = 1
                            ,worker      = '" . $_SESSION[LOGIN_NAME] . "'";   
                                        
            mysql_query( $query, $connect );
            
            $arr_info[error] = 0;
            $arr_info[msg]   = "등록 완료";
        }
        
        echo json_encode( $arr_info );
    }

    ////////////////////////////////////
    //
    // 상품명 확인...2010.5.13 - jk
    //
    function confirm()
    {
        global $connect, $product_id;
        
        $arr_data = class_product::get_info( $product_id, "name,options" );
        
        echo json_encode( $arr_data );
    }

   //////////////////////////////////////////////////////
   // 매칭정보 리스트 
   function CI00()
   {
      global $template, $page, $product_only, $query_download, $start_date, $end_date, $crworker;

      global $connect, $shop_id, $date_type, $start_date, $end_date, $product_id, $product_name, $options, $shop_code, $shop_product_name, $shop_option, $query_download, $enable_sale;

      $link_url = "?" . $this->build_link_url();     

      if (!$start_date) $start_date = date('Y-m-d', strtotime('-60 day'));
          $end_date = $end_date;

      $par_arr = array("template","action","shop_id","date_type","start_date", "end_date", "product_id", "product_name", "options", "shop_code", "shop_product_name", "shop_option","enable_sale","page","crworker");
      $link_url_list = $this->build_link_par($par_arr); 

      // 기본값이 1 -> 0 으로 변경 2006.11.24 - jk.ryu
      if ( !$page )
          $product_only = 0;     

      // 판매처별 상품 리스트를 가져온다 
      if ( $page )
         $result = $this->get_match_list( &$total_rows, $page );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
      echo "<script>hide_waiting()</script>";
   }

   //////////////////////////////////////////////////////
   // 매칭정보 삭제 리스트 
   function CI10()
   {
      global $template, $page, $product_only, $query_download, $start_date, $end_date;

      global $connect, $shop_id, $date_type, $start_date, $end_date, $product_id, $product_name, $options, $shop_code, $shop_product_name, $shop_option;

      $link_url = "?" . $this->build_link_url();     

      if (!$start_date) $start_date = date('Y-m-d', strtotime('-60 day'));
          $end_date = $end_date;

      $par_arr = array("template","action","shop_id","start_date", "end_date", "product_id", "product_name", "options", "shop_code", "shop_product_name", "shop_option","page");
      $link_url_list = $this->build_link_par($par_arr); 

      // 기본값이 1 -> 0 으로 변경 2006.11.24 - jk.ryu
      if ( !$page )
          $product_only = 0;     
 
      // 판매처별 상품 리스트를 가져온다 
      if ( $page )
         $result = $this->get_match_list_del( &$total_rows, $page );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
      echo "<script>hide_waiting()</script>";
   }

   //////////////////////////////////////////////////////
   // 매칭정보 일괄등록
   function CI20()
   {
      global $connect, $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
   }

   //////////////////////////////////////////////////////
   // 매칭정보 일괄삭제
   function CI30()
   {
      global $connect, $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
   }

    //////////////////////////////////////////////////////
   // 상품 리스트 
   function CI02()
   {
      global $template, $page, $product_only, $query_download;
      $link_url = "?" . $this->build_link_url();     


      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   //--------------------------------------
   //
   // 판매처 상품 코드 기준 조회
   // date: 2006.6.6
   //
   function CI01()
   {
      global $template, $page, $shop_id, $shop_product_id, $product_id, $connect;
      $link_url = "?" . $this->build_link_url();     

      // 상품명
      $product_name = class_D::get_product_name( $product_id ); 

      // 판매처
      $shop_name    = class_C::get_shop_name2( $shop_id );

      //---------------------------------
      // 판매처의 상품 정보
      // 매치 정보 조회
      $query = "select id as product_id, shop_option as options 
                  from " . $this->table . "
                 where shop_code='$shop_product_id'";
      $result = mysql_query ( $query, $connect );
     
      $i = 0;
      $arr_options = array ();
      while ( $data = mysql_fetch_array ( $result ) )
      {
        if ( $i )
          $options .= ","; 

        $options .= "'$data[product_id]'";
        $arr_options[$data[product_id]] = $data[options];
        $i++; 
      }
  
      // $query = "select id, shop_option,input_date from code_match where id in ( $options ) order by input_date desc";
      $query = "select product_id as id, options
                  from products 
                 where product_id in ( $options ) order by product_id";

      $result = mysql_query ( $query, $connect );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
      // echo "<script>hide_waiting()</script>";
   }



   // 옵션을 상속받는 상품 코드 등록
   function build_child()
   {
      global $connect, $child_product_id, $shop_product_id, $shop_id;

      //---------------------------------
      // 판매처의 상품 정보
      // 매치 정보 조회
      $query = "select id as product_id, shop_option as options 
                  from " . $this->table . " 
                 where shop_code='$shop_product_id' 
                   and shop_id='$shop_id'";

      $result = mysql_query ( $query, $connect );
     
      $i = 0;
      while ( $data = mysql_fetch_array ( $result ) )
      {
          $this->insert_match_info ( $shop_id, $child_product_id, $data[product_id], $data[options] ); 
      }

      $this->jsAlert("등록 완료" );

      $this->redirect("popup.htm?template=CI01&shop_id=$shop_id&shop_product_id=$child_product_id&product_id=$product_id&");
   }

   function insert_match_info( $shop_id, $shop_product_id, $product_id, $shop_option )
   {
       global $connect;
       $query = "insert " . $this->table . "set id='$product_id', shop_id='$shop_id', shop_code='$shop_product_id', shop_option='$shop_option',input_date=Now(), input_time=Now()";

       mysql_query ( $query, $connect );
   }

   //-----------------------------------------
   //
   // 판매되고 있는 상품의 실제 이름
   // date: 2006.6.6 - jk.ryu
   //
   function get_sales_name ( $shop_code, &$org_name, &$org_option )
   {
       global $connect;

       $query  = "select product_name,options from orders where shop_product_id = '$shop_code' limit 1";
       $result = mysql_query ( $query , $connect );
       $data   = mysql_fetch_array ( $result );

       $org_name   = $data[product_name];
       $org_option = $data[options];
   }

   //-----------------------------------------
   //
   // match table에서 정보를 대량으로 삭제
   // date: 2006.6.6 - jk.ryu
   //
   function mass_match_delete()
   {
     global $link_url, $shop_id, $shop_product_id, $connect;
  
     $query = "select id as product_id, shop_option as options 
                  from " . $this->table . " 
                 where shop_code='$shop_product_id'";

      $result = mysql_query ( $query, $connect );
     
      $i = 0;
      $arr_options = array ();
      while ( $data = mysql_fetch_array ( $result ) )
      {
        if ( $i )
          $options .= ","; 

        $options .= "'$data[product_id]'";
        $i++; 
      }
 
      $query = "delete from " . $this->table . " 
                 where shop_id = '$shop_id'
                   and shop_code = '$shop_product_id'
                   and id in ( $options )";

      mysql_query ( $query, $connect);

      $this->redirect($link_url);
      exit;
   }

   //-----------------------------------------
   //
   // match table에서 정보를 삭제
   // date: 2006.6.6 - jk.ryu
   //
   function match_delete()
   {
      global $connect, $seq_list, $page;

      $this->match_delete_log2($seq_list);
      $query = "delete from code_match 
                 where seq in ($seq_list) ";
debug("매칭삭제 : " . $query );
      mysql_query ( $query, $connect);
        
   }

   //-----------------------------------------
   //
   // match table에서 정보를 삭제 (체크된 상품만)
   // date: 2006.7.21 - sy.hwang
   //
   function match_delete_check()
   {
      global $link_url, $connect, $alldata;

      $data_ids = explode(",", $alldata);
      $del_cnt = count($data_ids);

      for ($i=0; $i < $del_cnt; $i++)
      {

        list($shop_id, $product_id, $shop_product_id) = explode("_", $data_ids[$i]);

        $transaction = $this->begin("매칭 정보 삭제(check) $product_id" );
        $query = "delete from " . $this->table . " 
                   where shop_id   = '$shop_id'
                     and shop_code = '$shop_product_id'
                     and id            = '$product_id'";

        mysql_query ( $query, $connect);
        debug($query);

        $this->end ( $transaction );

      }

      $this->redirect($link_url);
      exit;
   }



   //-----------------------------------------
   //
   // match table에서 정보를 모두 삭제
   // date: 2006.7.21 - sy.hwang
   //
   function match_delete_all()
   {
        global $connect, $shop_id, $date_type, $start_date, $end_date, $product_id, $product_name, $options, $shop_code, $shop_product_name, $shop_option, $query_download, $enable_sale, $crworker;
        
        $query   = "select a.shop_id, a.shop_code, a.shop_option from code_match a, products b where a.id=b.product_id ";

        // 판매처
        if ( $shop_id )
            $query .= " and a.shop_id= '$shop_id'";

        // 등록일
        if( $date_type )
            $query .= " and a.$date_type>='$start_date' and a.$date_type<='$end_date'";
        
        if ( $product_id )
            $query .= " and a.id = '$product_id'";

        if ( $product_name )
            $query .= " and b.name like '%$product_name%'";

        if ( $options )
            $query .= " and b.options like '%" . str_replace(" ","%",$options) . "%'";

        if ( $shop_code )
            $query .= " and a.shop_code = '$shop_code'";

        if ( $shop_product_name )
            $query .= " and a.shop_product_name like '%$shop_product_name%'";

        if ( $shop_option )
            $query .= " and a.shop_option like '%" . str_replace(" ","%",$shop_option) . "%'";

        if ( $enable_sale )
            $query .= " and b.enable_sale=0";

  		if ( $crworker )
            $query .= " and a.worker='$crworker'";


        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_assoc($result) )
        {
            // 로그
            $this->match_delete_log($data[shop_id], $data[shop_code], $data[shop_option]);

            $query_del = "delete from code_match where shop_id='$data[shop_id]' and shop_code='$data[shop_code]' and shop_option='$data[shop_option]'";
            mysql_query($query_del, $connect);
        }
   }


   //-----------------------------------------
   // match table에서 정보를 가져옴
   //-----------------------------------------
    function get_match_list( &$total_rows , $page , $total=0)
    {
        global $connect, $shop_id, $date_type, $start_date, $end_date, $product_id, $product_name, $options, $shop_code, $shop_product_name, $shop_option, $query_download, $enable_sale, $crworker;
        
        $page = $page ? $page : 1;   
        $starter = ($page - 1) * 20;
        
        $query   = "select * from code_match ";

        $options_cond = "";
        
        // 판매처
        if ( $shop_id )
            $options_cond .= ($options_cond ? " and " : " where " ) . " shop_id= '$shop_id' ";

        // 등록일
        if( $date_type )
            $options_cond .= ($options_cond ? " and " : " where " ) . " $date_type>='$start_date' and $date_type<='$end_date' ";
        
        if ( $product_id )
            $options_cond .= ($options_cond ? " and " : " where " ) . " id = '$product_id' ";

        // 상품코드
        if ( $product_name || $options || $enable_sale )
        {
            $product_id_list = "";
            
            $query_prd = "select product_id from products where is_delete=0 and is_represent<>1 ";
            if( $product_name )
                $query_prd .= " and name like '%$product_name%' ";
                
            if( $options )
                $query_prd .= " and options like '%$options%' ";
                
            if( $enable_sale )
                $query_prd .= " and enable_sale=0 ";
                
            $result_prd = mysql_query($query_prd, $connect);
            while( $data_prd = mysql_fetch_assoc($result_prd) )
                $product_id_list .= "'" . $data_prd[product_id] . "',";

            // 마지막 , 제거
            if( strlen($product_id_list) > 1 )
                $product_id_list = substr($product_id_list, 0, -1);
                
            $options_cond .= ($options_cond ? " and " : " where " ) . " id in ($product_id_list) ";
        }    

        if ( $shop_code )
            $options_cond .= ($options_cond ? " and " : " where " ) . " shop_code = '$shop_code' ";

        if ( $shop_product_name )
            $options_cond .= ($options_cond ? " and " : " where " ) . " shop_product_name like '%$shop_product_name%' ";

        if ( $shop_option )
            $options_cond .= ($options_cond ? " and " : " where " ) . " shop_option like '%" . str_replace(" ","%",$shop_option) . "%' ";

		if ( $crworker )
            $options_cond .= ($options_cond ? " and " : " where " ) . " worker='$crworker'";
            
        $query = $query . $options_cond;
        
        // 전체 수량
        $result_cnt = mysql_query($query, $connect);
        $total_rows = mysql_num_rows( $result_cnt );

        // $query .= " group by shop_id, shop_code, shop_option ";
        $query .= " order by input_date desc, input_time desc ";
        if ( !$total )
            $limit   = " limit $starter, " . _line_per_page; 
debug("매칭정보:".$query.$limit);
        $result = mysql_query ( $query . $limit, $connect );
        
        return $result;      
    }

   //-----------------------------------------
   // match table del에서 정보를 가져옴
   //-----------------------------------------
    function get_match_list_del( &$total_rows , $page , $total=0)
    {
        global $connect, $shop_id, $date_type, $start_date, $end_date, $product_id, $product_name, $options, $shop_code, $shop_product_name, $shop_option, $crworker;
        
        $page = $page ? $page : 1;   
        $starter = ($page - 1) * 20;
        
        $query   = "select *, a.delete_date del_date from code_match_del a, products b where a.product_id=b.product_id ";

        // 판매처
        if ( $shop_id )
            $query .= " and a.shop_id= '$shop_id'";

        // 삭제일
        $query .= " and a.delete_date>='$start_date 00:00:00' and a.delete_date<='$end_date 23:59:59'";
        
        if ( $product_id )
            $query .= " and a.product_id = '$product_id'";

        if ( $product_name )
            $query .= " and b.name like '%$product_name%'";

        if ( $options )
            $query .= " and b.options like '%" . str_replace(" ","%",$options) . "%'";

        if ( $shop_code )
            $query .= " and a.shop_code = '$shop_code'";

        if ( $shop_product_name )
            $query .= " and a.shop_product_name like '%$shop_product_name%'";

        if ( $shop_option )
            $query .= " and a.shop_option like '%" . str_replace(" ","%",$shop_option) . "%'";
            
        if ( $crworker )
            $query .= " and a.worker='$crworker'";
            

        // 전체 수량
        $result_cnt = mysql_query($query, $connect);
        $total_rows = mysql_num_rows( $result_cnt );

        // $query .= " group by a.delete_date ";
        $query .= " order by a.delete_date desc ";
        if ( !$total )
            $limit   = " limit $starter, " . _line_per_page; 
debug( "매칭삭제로그 : " . $query );
        $result = mysql_query ( $query . $limit, $connect );
        
        return $result;      
    }

   function get_shop_name( $shop_id )
   {
      return class_C::get_shop_name($shop_id);
   }
 
    //////////////////////////////////////
    // 상품목록 다운로드 - 파일 만들기
    function save_file()
    {
        global $connect, $shop_id, $date_type, $start_date, $end_date, $product_id, $product_name, $options, $shop_code, $shop_product_name, $shop_option, $enable_sale;

        // 엑셀 헤더
        $product_data = array();
        $product_data[] = array(
            "shop_id"           => "판매처",
            "shop_code"         => "판매처 상품코드",
            "shop_product_name" => "판매처 상품명",
            "shop_option"       => "판매처 옵션",
            "type"              => "타입",
            "supply_name"       => "공급처",
            "product_id"        => "상품코드",
            "soldout"           => "품절",
            "product_name"      => "상품명",
            "options"           => "옵션",
            "qty"               => "수량",
            "input_date"        => "등록일",
            "worker"            => "등록자"
        );

        $result = $this->get_match_list( &$total_rows, $page, 1 );
        while( $data = mysql_fetch_assoc($result) )
        {
            $product_info = class_product::get_info($data[id]);
            $supply_name = $this->get_supply_name2( $product_info[supply_code] );
            
            $product_data[] = array ( 
                "shop_id"           => $data[shop_id],
                "shop_code"         => $data[shop_code],
                "shop_product_name" => $data[shop_product_name],
                "shop_option"       => $data[shop_option],
                "type"              => $data[type],
                "supply_name"       => $supply_name,
                "product_id"        => $data[id],
                "soldout"           => "",
                "product_name"      => $product_info[name],
                "options"           => $product_info[options],
                "qty"               => $data[qty],
                "input_date"        => $data[input_date] . " " . $data[input_time],
                "worker"            => $data[worker]
            );
        }
        $this->make_file( $product_data, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

    function make_file( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
        global $connect;
        
	    $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer = "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<style>
.num_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\"\#\,\#\#0_\)\;\[Red\]\\\(\#\,\#\#0\\\)\";
}
.str_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
}
.mul_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
    white-space:normal;
}
br
	{mso-data-placement:same-cell;}
</style>
<body>
<html><table border=1>
";

        fwrite($handle, $buffer);
        // for row
        for( $i=0; $i < count( $arr_datas ); $i++ )
        {
            $buffer = "";
            $row = $arr_datas[$i];
            if( $i == 0 )
            {
                // for column
                $buffer .= "<tr>\n";
                foreach ( $row as $key=>$value) 
                    $buffer .= "<td style='font:bold 10pt \"굴림\"; white-space:nowrap; background:#CCFFCC;'>" . $value . "</td>";
                $buffer .= "\n</tr>\n";
                fwrite($handle, $buffer);
            }
            else
            {
                $query_prd = "select *, a.qty a_qty
                                from code_match a,
                                     products b
                               where a.id=b.product_id and 
                                     a.shop_id='" . $row[shop_id] . "' and 
                                     a.shop_code='" . $row[shop_code] . "' and 
                                     a.shop_option='" . $row[shop_option] . "'";
                $result_prd = mysql_query($query_prd, $connect);
                $cnt = mysql_num_rows($result_prd);

                $line = 0;
                while( $data_prd = mysql_fetch_assoc($result_prd) )
                {
                    $buffer = "";
                    $line++;
                    switch( $data_prd[match_type] )
                    {
                        case 0: $match_type = '일반';   break;
                        case 1: $match_type = '본상품'; break;
                        case 2: $match_type = '추가';   break;
                    }
                    $soldout_img = ( $data_prd[enable_sale] ? "" : "<img src=images/soldout.gif>" );

                    // for column
                    foreach ( $row as $key=>$value) 
                    {
                        // 판매처
                        if( $key == 'shop_id' )
                        {
                            if( $line > 1 )  continue;
                            if( $line == 1 )  $buffer .= "<tr>\n";
                            $buffer .= "<td class=str_item rowspan=$cnt>" . htmlspecialchars(class_C::get_shop_name($value)) . "</td>";
                        }
                        // 판매처 상품코드
                        else if( $key == 'shop_code' )
                        {
                            if( $line > 1 )  continue;
                            $buffer .= "<td class=str_item rowspan=$cnt>" . $value . "</td>";
                        }
                        // 판매처 상품명
                        else if( $key == 'shop_product_name' )
                        {
                            if( $line > 1 )  continue;
                            $buffer .= "<td class=mul_item rowspan=$cnt>" . htmlspecialchars($value) . "</td>";
                        }
                        // 판매처 옵션
                        else if( $key == 'shop_option' )
                        {
                            if( $line > 1 )  continue;
                            $buffer .= "<td class=mul_item rowspan=$cnt>" . htmlspecialchars($value) . "</td>";
                        }
                        // 타입
                        else if( $key == 'type' )
                        {
                            if( $line > 1 )  $buffer .= "<tr>\n";
                            $buffer .= "<td class=str_item>" . $match_type . "</td>";
                        }
                        // 공급처 명
                        else if( $key == 'supply_name' )
                            $buffer .= "<td class=str_item>" . $value . "</td>";
                        // 상품코드
                        else if( $key == 'product_id' )
                            $buffer .= "<td class=str_item>" . $data_prd[product_id] . "</td>";
                        // 품절
                        else if( $key == 'soldout' )
                            $buffer .= "<td class=str_item>" . ( $data_prd[enable_sale] ? "" : "품절" ) . "</td>";
                        // 상품명
                        else if( $key == 'product_name' )
                            $buffer .= "<td class=str_item>" . htmlspecialchars($data_prd[name]) . "</td>";
                        // 옵션
                        else if( $key == 'options' )
                            $buffer .= "<td class=str_item>" . htmlspecialchars($data_prd[options]) . "</td>";
                        // 수량
                        else if( $key == 'qty' )
                        {
                            $buffer .= "<td class=str_item>" . $data_prd[a_qty] . "</td>";
                            if( $line > 1 )  $buffer .= "\n</tr>\n";
                        }
                        // 등록일
                        else if( $key == 'input_date' )
                        {
                            if( $line > 1 )  continue;
                            $buffer .= "<td class=str_item rowspan=$cnt>" . $value . "</td>";
                        }
                        // 작업자
                        else if( $key == 'worker' )
                        {
                            if( $line > 1 )  continue;
                            $buffer .= "<td class=str_item rowspan=$cnt>" . $value . "</td>";
                            if( $line == 1 )  $buffer .= "\n</tr>\n";
                        }
                    }
                    fwrite($handle, $buffer);
                }
            }

            if( $i % 3 == 0 )
            {
                $msg = " $i / $cnt_all ";
                echo "<script language='javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
        }
        fwrite($handle, "</table>");

        ////////////////////////////////////// 
        // file close and delete it 
        // file은 보관함
        fclose($fp);

        return $filename; 
    }
            
    //////////////////////////////////////
    // 매칭정보 다운로드 - 파일 다운받기
    function download()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "match_list.xls");
    }    

    // 품절 정보
    function get_enable_sale($val)
    {
        switch( $val )
        {
            case 0: return "판매불가";
            case 1: return "판매가능";
            case 2: return "부분품절";
        }
    }

   function get_shop_product_name( $product_id, $shop_product_id )
   {
      global $connect;

      $query = "select product_name from orders where product_id='$product_id' and shop_product_id='$shop_product_id' limit 1";

      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      echo "&nbsp;" . $data[product_name];
   }
   
    // 매칭정보 삭제 로그
    function match_delete_log($shop_id, $shop_code, $shop_option)
    {
        global $connect;
        
        $query = "select * from code_match where shop_id='$shop_id' and shop_code='$shop_code' and shop_option='$shop_option'";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query = "insert code_match_del 
                         set shop_id           = '" . $data[shop_id          ] . "',
                             shop_code         = '" . $data[shop_code        ] . "',
                             shop_product_name = '" . $data[shop_product_name] . "',
                             shop_option       = '" . $data[shop_option      ] . "',
                             input_date        = '" . $data[input_date       ] . "',
                             input_time        = '" . $data[input_time       ] . "',
                             product_id        = '" . $data[id               ] . "',
                             qty               = '" . $data[qty              ] . "',
                             match_type        = '" . $data[match_type       ] . "',
                             auto_count        = '" . $data[auto_count       ] . "',
                             worker            = '" . $data[worker           ] . "',
                             delete_date       = now(),
                             person            = '$_SESSION[LOGIN_NAME]'";
debug("매칭정보 삭제 로그 : " . $query);
            mysql_query($query, $connect);
        }
    }

    // 매칭정보 삭제 로그
    function match_delete_log2($seq_list)
    {
        global $connect;
        
        $query = "select * from code_match where seq in ($seq_list)";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $query = "insert code_match_del 
                         set shop_id           = '" . $data[shop_id          ] . "',
                             shop_code         = '" . $data[shop_code        ] . "',
                             shop_product_name = '" . $data[shop_product_name] . "',
                             shop_option       = '" . $data[shop_option      ] . "',
                             input_date        = '" . $data[input_date       ] . "',
                             input_time        = '" . $data[input_time       ] . "',
                             product_id        = '" . $data[id               ] . "',
                             qty               = '" . $data[qty              ] . "',
                             match_type        = '" . $data[match_type       ] . "',
                             auto_count        = '" . $data[auto_count       ] . "',
                             worker            = '" . $data[worker           ] . "',
                             delete_date       = now(),
                             person            = '$_SESSION[LOGIN_NAME]'";
debug("매칭정보 삭제 로그2 : " . $query);
            mysql_query($query, $connect);
        }
    }

    ///////////////////////////////////
    // 매칭정보 일괄등록
    function upload()
    {
        global $connect, $_file;
        
        $max_crr_cnt = 100;
        
        $transaction = $this->begin("대량등록");
  
        $obj = new class_file();
        $arr = $obj->upload();

        $this->show_wait();
        
        $err_result = "";
        $err_cnt = 0;
        
        $i = 0;
        $n = 0;
        $row_cnt = count( $arr );
        
        $old_time = time();
        foreach ( $arr as $row )
        {
            $i++;
            if ( $i <= 1 ) continue;  // 헤더

            // 필수 입력 항목이 없으면 넘어간다.
            if( !$row[0] )
            {
                if( $err_cnt++ < $max_crr_cnt )
                {
                    $err_result .= " $i 행 : 판매처를 입력하세요 <br> ";
debug(" $i 행 : 판매처를 입력하세요");
                }
                continue;
            }else if( !$row[1] ){
                if( $err_cnt++ < $max_crr_cnt )
                {
                    $err_result .= " $i 행 : 판매처 상품코드를 입력하세요 <br> ";
debug(" $i 행 : 판매처 상품코드를 입력하세요.");
                }
                continue;
            }else if( !$row[3] ){
                if( $err_cnt++ < $max_crr_cnt )
                {
                    $err_result .= " $i 행 : 상품코드를 입력하세요 <br> ";
debug(" $i 행 : 상품코드를 입력하세요.");
                }
                continue;
            }else if( !$row[4] ){
                if( $err_cnt++ < $max_crr_cnt )
                {
                    $err_result .= " $i 행 : 수량을 입력하세요 <br> ";
debug(" $i 행 : 수량을 입력하세요.");
                }
                continue;
            }
                
            // 공급처 코드 검사
            $query = "select * from shopinfo where shop_name='$row[0]'";
            $result = mysql_query($query, $connect);
            if( !mysql_num_rows($result) )
            {
                if( $err_cnt++ < $max_crr_cnt )
                {
                    $err_result .= " $i 행 : 잘못된 판매처입니다.<br> ";
debug(" $i 행 : 잘못된 판매처입니다.");
                }
                continue;
            }
            $data = mysql_fetch_assoc($result);
            $shop_id = $data[shop_id];
            
            // 상품코드 검사
            $query = "select * from products where product_id='$row[3]' and is_represent=0 and is_delete=0";
            $result = mysql_query($query, $connect);
            if( !mysql_num_rows($result) )
            {
                if( $err_cnt++ < $max_crr_cnt )
                {
                    $err_result .= " $i 행 : 잘못된 상품코드입니다.<br> ";
debug(" $i 행 : 잘못된 판매처입니다.");
                }
                continue;
            }
            
            if( _DOMAIN_ != 'efolium2' && _DOMAIN_ != 'changsin2' && _DOMAIN_ != 'changsin' && _DOMAIN_ != 'themobile' )
            {
                // 이미 등록된 매칭정보 확인
                if( $_SESSION[MATCH_OPTION] )
                {
                    $query = "select * from code_match 
                               where shop_id           = '$shop_id' and
                                     shop_option       = '$row[2]'";
                }
                else
                {
                    $query = "select * from code_match 
                               where shop_id           = '$shop_id' and
                                     shop_code         = '$row[1]'  and
                                     shop_option       = '$row[2]'";
                }

                $result = mysql_query($query, $connect);
                if( mysql_num_rows($result) )
                {
                    if( $err_cnt++ < $max_crr_cnt )
                        $err_result .= " $i 행 : 이미 등록된 매칭정보입니다.<br> ";
                    continue;
                }
            }
            
            // 매칭정보 등록
            $query = "insert code_match
                         set id                = '$row[3]',
                             shop_id           = '$shop_id',
                             shop_code         = '$row[1]',
                             shop_option       = '$row[2]',
                             input_date        = now(),
                             input_time        = now(),
                             qty               = '$row[4]',
                             worker            = '$_SESSION[LOGIN_NAME](일괄등록)',
                             match_type        = 0,
                             shop_product_name = '',
                             auto_count        = 1";
            mysql_query($query, $connect);
            $n++;
            
            if ( $old_time < time() )
            {
                $this->show_txt( $i . "/" . count($arr));
                $old_time = time();
            }
        }
       
        $this->hide_wait();
        $this->jsAlert("$n 개 입력 완료 되었습니다.");
    
        $err_result = $this->base64_encode_url($err_result);
        $this->redirect("?template=CI20&err_cnt=$err_cnt&err_result=$err_result");
        
    }

    ///////////////////////////////////
    // 매칭정보 일괄삭제
    function upload2()
    {
        global $connect, $_file;
        
        $max_crr_cnt = 100;
        
        $transaction = $this->begin("대량등록");
  
        $obj = new class_file();
        $arr = $obj->upload();

        $this->show_wait();
        
        $err_result = "";
        $err_cnt = 0;
        
        $i = 0;
        $n = 0;
        $row_cnt = count( $arr );
        
        $old_time = time();
        foreach ( $arr as $row )
        {
            $i++;
            if ( $i <= 1 ) continue;  // 헤더
            if ( $i == $row_cnt ) continue;  // 마지막행

            $shop_name = trim($row[0]);
            $shop_code = trim($row[1]);
            $product_id = trim($row[2]);
            
            // 판매처 && 판매처 상품코드
            if( $shop_name && $shop_code )
            {
                // 판매처 코드 검사
                $query = "select * from shopinfo where shop_name='$row[0]'";
                $result = mysql_query($query, $connect);
                if( !mysql_num_rows($result) )
                {
                    if( $err_cnt++ < $max_crr_cnt )
                        $err_result .= " $i 행 : 잘못된 판매처입니다.<br> ";
                    continue;
                }
                $data = mysql_fetch_assoc($result);
                $shop_id = $data[shop_id];
                
                // 삭제 로그
                $query_log = "select * from code_match where shop_id='$shop_id' and shop_code='$shop_code'";
                $result_log = mysql_query($query_log, $connect);
                while( $data_log = mysql_fetch_assoc($result_log) )
                    $this->match_delete_log($data_log[shop_id], $data_log[shop_code], $data_log[shop_option]);
                
                // 매칭정보 삭제
                $query = "delete from code_match
                           where shop_id           = '$shop_id' and
                                 shop_code         = '$shop_code'";
                mysql_query($query, $connect);
            }
            // 어드민 상품코드
            else if( $product_id )
            {
                $query = "select shop_id, shop_code, shop_option from code_match where id='$product_id' ";
                $result = mysql_query($query, $connect);
                while( $data = mysql_fetch_assoc($result) )
                {
                    $shop_id = $data[shop_id];
                    $code = addslashes( $data[shop_code] );
                    $option = addslashes( $data[shop_option] );
                    
                    // 삭제 로그
                    $query_log = "select * from code_match where shop_id='$shop_id' and shop_code='$code' and shop_option='option'";
                    $result_log = mysql_query($query_log, $connect);
                    while( $data_log = mysql_fetch_assoc($result_log) )
                        $this->match_delete_log($data_log[shop_id], $data_log[shop_code], $data_log[shop_option]);
                    
                    // 매칭정보 삭제
                    $query = "delete from code_match
                               where shop_id     = '$shop_id' and
                                     shop_code   = '$code' and
                                     shop_option = '$option' ";
                    mysql_query($query, $connect);
                }
            }
            else
            {
                if( $err_cnt++ < $max_crr_cnt )
                    $err_result .= " $i 행 : 항목를 입력하세요 <br> ";
                continue;
            }
            
            $n++;
            
            if ( $old_time < time() )
            {
                $this->show_txt( $i . "/" . count($arr));
                $old_time = time();
            }
        }
       
        $this->hide_wait();
        $this->jsAlert("$n 개 삭제 완료 되었습니다.");
    
        $err_result = $this->base64_encode_url($err_result);
        $this->redirect("?template=CI30&err_cnt=$err_cnt&err_result=$err_result");
        
    }

}
?>
