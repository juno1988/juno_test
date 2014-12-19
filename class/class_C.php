<?
///////////////////////////
// name: class_product
require_once "class_top.php";
require_once "class_product.php";
require_once "class_3pl.php";
require_once "class_stock.php";
require_once "class_multicategory.php";

class class_C extends class_top
{
   var $items;
   var $obj_3pl;

   ////////////////////////////////
   // MD 이름
   function get_md ( $code )
   {
      global $connect;

      $query = "select md from userinfo where code='$code'";
      $result = mysql_query ( $query , $connect );
      $data = mysql_fetch_array( $result );
      return $data[md];
   }
   ////////////////////////////////
   // 공급처 이름
   function get_supplyname ( $code )
   {
      global $connect;

      $query = "select name from userinfo where code='$code'";
      $result = mysql_query ( $query , $connect );
      $data = mysql_fetch_array( $result );
      return $data[name];
   }
   //////////////////////////////////////////////////////
   // 판매처 정보 가져오기
   function get_shopinfo( $shop_id , $name )
   {
      global $connect;
      $query = "select * from sys_shopinfo2 where shop_id='$shop_id'";
      $result = mysql_query ( $query , $connect );
      $data = mysql_fetch_array ( $result );
      
      if ( $name )
         return $data[$name];
      else
         return $data;
   }

   ////////////////////////////////////////////////////
   // 상품 변경 확인
   //
   function get_product_modify_list ( &$total_rows )
   {
      global $connect;
      $string = $_REQUEST["string"];

      $start_date = $_REQUEST["start_date"] ? $_REQUEST["start_date"] : strftime('%Y-%m-%d', strtotime('now'));
      $end_date = $_REQUEST["end_date"] ? $_REQUEST["end_date"] : strftime('%Y-%m-%d', strtotime('now'));

      $query = "select *,count(*) cnt ";
      $query_cnt = "select count(*) cnt ";

      $option = " from products a, code_match b ";
      $option .= " where a.product_id = b.id
                     and a.last_update_date >= '$start_date 00:00:00' 
                     and a.last_update_date <= '$end_date 23:59:59' ";
      if ( $string )
         $option .= " and a.name like '%$string%'
                       or a.product_id like '%$string%'";

      $option .= " group by a.product_id";

      ////////////////////////////////////////////////// 
      // total count 가져오기
      $result = mysql_query ( $query_cnt . $option );
      $data = mysql_fetch_array ( $result );
      $total_rows = $data[cnt];

      $result = mysql_query ( $query . $option, $connect );

      return $result;
   }

   ////////////////////////////////
   // 관리자가 확인해야 할 품절 리스트
   // date: 2005.8.18
   function get_product_soldout_list( $userid , &$max_row )
   {
      global $connect;
      $start_date = $_REQUEST["start_date"] ? $_REQUEST["start_date"] : strftime('%Y-%m-%d', strtotime('now'));
      $end_date = $_REQUEST["end_date"] ? $_REQUEST["end_date"] : strftime('%Y-%m-%d', strtotime('now'));
      
      $userid = $_SESSION["LOGIN_ID"];

      //////////////////////////////////////////////////////////////
      // u가 1이면 다른 사용자는 confirm하고 login한 사람은 confirm하지 않은 case
      // u가 0이면 새로 등록된 상품
      // 내가 이전에 confirm한 상품은 c가 1이 나옴
      $query = "select * from products a
                 where a.enable_sale = 0
                   and a.sale_stop_date >= '$start_date 00:00:00' 
                   and a.sale_stop_date <= '$end_date 23:59:59' 
                 order by sale_stop_date desc";
      // 개수 구함
      //$result = mysql_query ( $query_cnt . $option , $connect );
      //$data = mysql_fetch_array ( $result );
      //$max_row = $data[cnt];

      $result = mysql_query ( $query , $connect );
      return $result;
   }

   ///////////////////////////////////////
   // 공급처별 상품 리스트
   // date: 2005.9.7
   // 0 : stock 처리 없음
   // 1 : 안정 재고 이하 
   // 2 : 마이너스 재고
   function get_product_supply_list( &$total_rows, $stock=0, $download=0 )
   {
      global $connect;

      $page = $_REQUEST["page"] ? $_REQUEST["page"] : 1;
      $starter = ($page - 1) * 20;

      $query_cnt = "select count(*) cnt ";
      $query = "select * ";

      $option = " from products ";

      $string = $_REQUEST["string"];

      switch ( $_REQUEST["string_type"] )
      {
         case "name" :
            $option .= " where name like '%$string%' ";
         break;
         case "id" :
            $option .= " where product_id = '$string' ";
         break;
         case "option" :
            $option .= " where options like '%$string%' ";
         break;
         default :
            $option .= " where name like '%$string%' ";
      }

      if ( $_REQUEST["supply_code"] )
         $option .= " and supply_code =  '" . $_REQUEST["supply_code"] . "'";

      switch ( $stock )
      {
         case "1":
            $option .= " and safe_stock > current_stock ";
         break;
         case "2" :
            $option .= " and current_stock < 0 ";
         break;
      }

      $option .= " and is_delete = 0";

      // 대표 상품만 검색한다.
      //$option .= " and ( stock_manage=0 or is_represent=1 ) ";

      $result = mysql_query ( $query_cnt . $option, $connect );
      $data = mysql_fetch_array ( $result );
      $total_rows = $data[cnt];
                  
      $limit = " order by reg_date desc ";

      if ( !$download )
         $limit .= " limit $starter, " . _line_per_page; 

//echo $query.$option.$limit;
//exit;
      $result = mysql_query ( $query . $option . $limit, $connect );
      return $result;
   }

   ///////////////////////////////////////
   // 판매처별 상품 리스트
   // date: 2005.9.24
   // stock 처리
   // 0 : stock 처리 없음
   // 1 : 안정 재고 이하 
   // 2 : 마이너스 재고
   function get_product_shop_list2 ( &$total_rows, $stock = 0, $download=0 )
   {
      global $connect;

      $page = $_REQUEST["page"] ? $_REQUEST["page"] : 1;
      $starter = ($page - 1) * 20;

      $query_cnt = "select count(*) cnt ";
      $query = "select a.*, b.shop_id, b.shop_code ";

      $option = " from products a, code_match b
                 where a.product_id = b.id";

      if ( $_REQUEST["supply_code"] )
        $option .= " and a.supply_code =  '" . $_REQUEST["supply_code"] . "'";

      $string = $_REQUEST["string"];

      switch ( $_REQUEST["string_type"] )
      {
         case "name" :
            if ( $string )
               $option .= " and a.name like '%$string%' ";
         break;
         case "id" :
            if ( $string )
               $option .= " and a.product_id = '$string' ";
         break;
         case "shop_id":
            if ( $string )
               $option .= " and b.shop_code = '$string' ";
         break;
      }

      if ( $_REQUEST["e_sale"] )
         $option .= " and enable_sale = '". $_REQUEST["e_sale"] . "'";

      $result = mysql_query ( $query_cnt . $option, $connect );
      $data = mysql_fetch_array ( $result );
      $total_rows = $data[cnt];

      if ( !$download )
         $limit = " limit $starter, " . _line_per_page; 
      else
         $limit = "";

      $result = mysql_query ( $query . $option . $limit, $connect );
      return $result;
   }

   ///////////////////////////////////////
   // 판매처별 상품 리스트
   // date: 2005.9.7
   function get_product_shop_list ( &$total_rows, $stock=0, $download = 0 )
   {
      global $connect;

      $page = $_REQUEST["page"] ? $_REQUEST["page"] : 1;
      $starter = ($page - 1) * 20;

      $query_cnt = "select count(*) cnt ";
      $query = "select a.*, b.*";

      $option = " from products a, code_match b
                 where a.product_id = b.id ";

      if ( $_REQUEST["shop_id"] )
         $option .= " and b.shop_id =  '" . $_REQUEST["shop_id"] . "'";

      if ( $_REQUEST["supply_code"] )
        $option .= " and a.supply_code =  '" . $_REQUEST["supply_code"] . "'";

      $string = $_REQUEST["string"];

      switch ( $_REQUEST["string_type"] )
      {
         case "name" :
            $option .= " and a.name like '%$string%' ";
         break;
         case "id" :
            $option .= " and a.product_id = '$string' ";
         break;
         case "shop_id":
            $option .= " and b.shop_code = '$string' ";
         break;
      }
      
      if ( $_REQUEST["e_sale"] )
         $option .= " and enable_sale = '". $_REQUEST["e_sale"] . "'";

      //stock 처리
      // 0 : stock 처리 없음
      // 1 : 안정 재고 이하 
      // 2 : 마이너스 재고

      switch ( $stock )
      {
         case 1:
            $option .= " and safe_stock < current_stock ";
         break;
         case 2:
            $option .= " and current_stock <= 0 ";
         break;
      }

//echo $query_cnt . $option;


      $result = mysql_query ( $query_cnt . $option, $connect );
      $data = mysql_fetch_array ( $result );
      $total_rows = $data[cnt];

      if ( !$download )
         $limit = " limit $starter, " . _line_per_page; 
      else
         $limit = "";

//echo $query . $option . $limit;
//exit;

      $result = mysql_query ( $query . $option . $limit, $connect );
      return $result;
   }

   ////////////////////////////////
   // 관리자가 확인해야 할 상품 리스트
   // date: 2005.8.18
   function get_product_confirm_list( $userid , &$max_row )
   {
      global $connect;
      $start_date = $_REQUEST["start_date"] ? $_REQUEST["start_date"] : strftime('%Y-%m-%d', strtotime('now'));
      $end_date = $_REQUEST["end_date"] ? $_REQUEST["end_date"] : strftime('%Y-%m-%d', strtotime('now'));

      $query = "select * from products a
                 where 
                   a.reg_date >= '$start_date 00:00:00' 
                   and a.reg_date <= '$end_date 23:59:59'";
//echo $query;

      $result = mysql_query ( $query . $option , $connect );
      return $result;
   }

   ///////////////////////////////
   // mach 정보 input
   function match_input ()
   {
      global $connect;
      global $product_id, $shop_pid, $link_url;
      /*
      $shop_id = $_REQUEST["shop_id"];
      $query = "insert into code_match set shop_id = '$shop_id', shop_code='$shop_pid', id='$product_id'";
      mysql_query ( $query, $connect );

      $this->redirect ( "?template=C205&id=$product_id" );
      */

      $this->jsAlert( "[match input] 허가받지 않은 요청입니다." );
      exit;
   }

   function match_delete ()
   {
      global $connect;
      global $product_id, $shop_id, $shop_pid;

      $query = "delete from code_match 
                      where shop_id = '$shop_id' 
                        and  shop_code='$shop_pid' 
                        and  id='$product_id'";

      mysql_query ( $query, $connect );

      $this->redirect ( "?template=C205&id=$product_id" );
      exit;
   }

   //////////////////////////////
   // 상품 상세 정보
   function get_detail ( $id )
   {
      global $connect;

      $query = "select * from products where product_id='$id'";

      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      return $data;
   }


   //////////////////////////////
   // 그룹별 검색이 가능한 상품 리스트
   // date: 2006.1.9
   // jk.ryu
   function get_list2( &$max_row, $page, $download=0 )
   {
      global $connect, $page, $is_free_deliv;
      global $string, $disp_copy, $supply_code, $group_id, $date_type;

//      if ( !$disp_copy ) $disp_copy = 2;

      /// logic
      $query = "select * ";
      $query_cnt = "select count(*) as cnt ";

      $option = " from products, product_group
                 where products.product_id = product_group.product_id
                   and is_delete = 0
                   and product_group.group_id = $group_id";

      if ( _DOMAIN_ == "femi" )
        $disp_copy = 3;
     
      ///////////////////////////////// 
//echo "copy: " . $disp_copy;
      switch ( $disp_copy )
      {
         case "1": // 원본 + 재고관리본
            $option .= " and org_id = '' ";
         break;
         case "2": // 원본
            $option .= " and org_id = '' and (stock_manage = 0 or stock_manage is null) ";
         break;
         case "3": // 복사본
            $option .= " and org_id <> '' and (stock_manage is null or stock_manage = 0) ";
         break;
         case "4": // 재고관리 본 
            $option .= " and org_id = '' and stock_manage = 1 ";
         break;
         default:
            $option .= " and org_id = '' ";
         break;
      }

      // session level이 0이면 업체임
      if ( !$_SESSION["LOGIN_LEVEL"] )
          $option .= " and supply_code = '" . $_SESSION["LOGIN_CODE"] . "'";
      else
          if ( $supply_code )
             $option .= " and supply_code = '$supply_code'";


      // id 인지 name인지 결정해야 함
      if ( $string )
      {
         if ( $_REQUEST["string_type"] == "id" )
            $option .= " and product_id = '$string' ";
         else if ( $_REQUEST["string_type"] == "name" )
            $option .= " and name like '%$string%' ";
         else if ( $_REQUEST["string_type"] == "brand" )
            $option .= " and brand like '%$string%' ";
         else if ( $_REQUEST["string_type"] == "orgin" )
            $option .= " and orgin like '%$string%' ";
      }

      switch ( $_REQUEST["e_sale"] )
      {
         case 1:  // 판매가능
            $option .= " and enable_sale = '1'";
         break;
         case 2:  // 판매 불가
            $option .= " and enable_sale = '0'";
         case 3:  // 부분 불가 date: 2007.7.2
            $option .= " and enable_sale = '2'";
         case 4:  // 전체 판매 불가
            $option .= " and enable_sale = 0 and substring(product_id,1,1)<>'S' ";
         break;
      }
      // 배송비 무료
      if ( $is_free_deliv != -1 )
            $option .= " and is_free_deliv='$is_free_deliv'";

      if ( !$page )
         $page = 1;
      $starter = ($page-1) * _line_per_page;


      // count
      $result = mysql_query( $query_cnt . $option );

//debug( $query_cnt . $option );

      $data = mysql_fetch_array ( $result );
      $max_row = $data[cnt];

      if ( $_REQUEST["e_sale"] == 2)
          $order = " order by sale_stop_date desc";
      else
          $order = " order by reg_date desc , reg_time desc";
     
      if ( $download )
         $limit = "";
      else
         $limit = " limit $starter, 20";

        debug( $query . $option . $order . $limit );
        
      $result = mysql_query ( $query . $option . $order . $limit, $connect );
      return $result;
   }   



    //////////////////////////////
    // 상품 리스트
    function get_list( &$max_row, $page, $download=0 )
    {
        global $connect, $page, $is_free_deliv, $start_date, $end_date, $stock_manage, $category;
        global $string, $disp_copy, $supply_code, $pick_soldout_date,$packed, $date_type, $string_type, $e_stock, $sort, $str_category,$current_category1,$current_category2,$current_category3;
        global $m_sub_category_1, $m_sub_category_2, $m_sub_category_3,$sync;
        global $multi_supply_group, $multi_supply;
        
        $string = trim($string);

        // 상품코드 검색일 경우
        if( $string && $_REQUEST["string_type"] == "id" )
        {
            $product_id = strtoupper($string);
            // 옵션코드
            if( substr($product_id,0,1)=='S' )
            {
                $query_options = "select org_id from products where is_delete=0 and product_id='$product_id'";
                $result_options = mysql_query($query_options, $connect);
                if( mysql_num_rows($result_options) )
                {
                    $data_options = mysql_fetch_assoc($result_options);
                    $product_id = $data_options[org_id];
                }
                else
                    $product_id = "";
            }
            else
                $product_id = $string;

            $query = "select * from products where is_delete=0 and product_id='$product_id'";
            
            // 멀티카테고리
            $arr_search_id = class_multicategory::get_search_id($m_sub_category_1,$m_sub_category_2,$m_sub_category_3);
            if ( $arr_search_id[$m_sub_category_1] )
                $query .= " and m_category1=" . $arr_search_id[$m_sub_category_1];
            
            if ( $arr_search_id[$m_sub_category_2] )
                $query .= " and m_category2=" . $arr_search_id[$m_sub_category_2];
            
            if ( $arr_search_id[$m_sub_category_3] )
                $query .= " and m_category3=" . $arr_search_id[$m_sub_category_3];
            
            /*
            if( $str_category )
            {
                $_category = str_replace('null', '', $str_category);
                $_category = str_replace('-1', '', $_category);
                $_category = str_replace('>  >', '>', $_category);
                $_category = str_replace('>', '%', $_category);
                $_category = str_replace(' ', '', $_category);
                //$_category = str_replace('%%', '', $_category);
                
                // 제일 마지막의 %는 삭제
                // $_category = preg_replace ( "/\%$/", '', $_category );
                
                if ( $_category && $_category != "%%")
                    $query .= " and str_category like '$_category' ";
            }
            */
            debug( "xxx:" . $query );
            
            $result = mysql_query($query, $connect);
            $max_row = mysql_num_rows($result);
        }
        else
        {
            /// logic
            $query = "select if(org_id>'00000',org_id,product_id) stand_id from products where is_delete = 0 ";
            if ( $string > '' )
            {
                switch( $_REQUEST["string_type"] )
                {
                    case "name":
                        // 독립서버에 한해서 멀티검색어 기능. 구분자는 $.
                        // 2014-07-21 장경희 beginning 추가
                        if( ($_SESSION[IS_DB_ALONE] || _DOMAIN_ == 'beginning' || _DOMAIN_ == 'ezadmin') && strpos($string, "$") !== false )
                        {
                            $or_str = "";
                            foreach(explode("$", $string) as $str_val)
                            {
                                if( !$str_val )  continue;
                                
                                $str_val = str_replace( array("%","_"," "), array("\\%","\\_",""), trim($str_val) );
                                $or_str .= ($or_str ? " or " : "") . " replace(name,' ','') like '%$str_val%' ";
                            }
                            $option .= " and ($or_str) ";
                        }
                        else
                        {
                        	$option .= " and name like '%" . str_replace(array("%","_"," "), array("\\%","\\_","%"), trim($string)) . "%' ";
                        }
                        break;
                    case "options":
                        // 독립서버에 한해서 멀티검색어 기능. 구분자는 $.
                        // 2014-07-21 장경희 beginning 추가
                        if( ($_SESSION[IS_DB_ALONE] || _DOMAIN_ == 'beginning' || _DOMAIN_ == 'ezadmin') && strpos($string, "$") !== false )
                        {
                            $or_str = "";
                            foreach(explode("$", $string) as $str_val)
                            {
                                if( !$str_val )  continue;
                                
                                $str_val = str_replace( array("%","_"), array("\\%","\\_"), trim($str_val) );
                                $or_str .= ($or_str ? " or " : "") . " options like '%$str_val%' ";
                            }
                            $option .= " and ($or_str) ";
                        }
                        else
                        	$option .= " and options like '%" . str_replace(array("%","_"," "), array("\\%","\\_","%"), trim($string)) . "%' ";
                        break;
                    case "brand":
                        $option .= " and brand like '%$string%' "; break;
                    case "brand_option":
                        $option .= " and supply_options like '%$string%' "; break;    
                    case "origin":
                        $option .= " and origin like '%$string%' "; break;
                    case "barcode_part":
                        $option .= " and barcode like '%$string%' "; break;
                    case "barcode":
                        if( $string == '#' )
                        {
                            $option .= " and substring(product_id, 1,1)='S' and barcode = product_id "; break;
                        }
                        else
                        {
                            $option .= " and barcode = '$string' "; break;
                        }
                    case "org_price":
                        $option .= " and org_price = '$string' "; break;
                    case "supply_price":
                        $option .= " and supply_price= '$string' "; break;
                    case "shop_price":
                        $option .= " and shop_price= '$string' "; break;
                    case "maker":
                        $option .= " and maker like '%$string%' "; break;
                    case "link_id":
                        $option .= " and link_id like '%$string%' "; break;
                    case "shop_product_code":
                        $option .= " and shop_product_code like '$string%' "; break;
                    case "location":
                        $option .= " and location like '%$string%' "; break;
                    case "md":
                        $option .= " and md = '$string' "; break;
                }
            }
            
            // 옵션관리 여부
            if ( $stock_manage != -1 )
                $option .= " and stock_manage=$stock_manage";
            
            // 재고관리 여부
            if ( $e_stock != -1 )
                $option .= " and enable_stock=$e_stock";

            // 공급처 멀티 선택 - 2012-2.15 - jk
            global $s_group_id, $str_supply_code;
            if ( $s_group_id != "")
                $str_supply_code = $this->get_group_supply( $s_group_id );   
            else
                $str_supply_code = $this->get_str_supply();
            
            if ( $str_supply_code )
                $option .= " and supply_code in ( $str_supply_code ) ";
                
                
                
            
//            if($multi_supply_group)
//            {
//            	$multi_supply_group_str = $this->get_multi_supply_group_str($multi_supply_group);
//            	$option .= " and supply_code in ( $multi_supply_group_str ) ";
//            }
            if($multi_supply)
            	$option .= " and supply_code in ( $multi_supply ) ";
            
            switch ( $_REQUEST["e_sale"] )
            {
                case 1:  // 판매가능
                    $option .= " and enable_sale = 1";
                    break;
                case 2:  // 품절
                    $option .= " and enable_sale = 0";
                    break;
                case 3:  // 부분 품절
                    $option .= " and enable_sale = 2";
                    break;
                case 4:  // 전체 판매 불가
                    $option .= " and enable_sale = 0 and substring(product_id,1,1)<>'S' ";
                    break;
            }

            switch ( $_REQUEST["sync"] )
            {
                case 1:  // 상품동기화
                    $option .= " and no_sync=0 ";
                    break;
                case 2:  // 상품동기화 미사용
                    $option .= " and no_sync=1 ";
                    break;
                case 3:  // 재고동기화
                    $option .= " and no_sync=0 and no_stock_sync=0 ";
                    break;
                case 4:  // 재고동기화 미사용
                    $option .= " and no_sync=0 and no_stock_sync=1 ";
                    break;
            }
            
            // 카테고리
            if ( $category )
                $option .= " and category = '$category'";
            
            if ( $date_type )
                $option .= " and $date_type >= '$start_date 00:00:00' and $date_type <= '$end_date 23:59:59'";
            
            // 품절일 설정시 품절 상품만. 대표상품 제외
            if( $date_type == "sale_stop_date" )
                $option .= " and enable_sale<>1";
                
            // 멀티카테고리
            $arr_search_id = class_multicategory::get_search_id($m_sub_category_1,$m_sub_category_2,$m_sub_category_3);
            if ( $arr_search_id[$m_sub_category_1] )
                $option .= " and m_category1=" . $arr_search_id[$m_sub_category_1];
            
            if ( $arr_search_id[$m_sub_category_2] )
                $option .= " and m_category2=" . $arr_search_id[$m_sub_category_2];
            
            if ( $arr_search_id[$m_sub_category_3] )
                $option .= " and m_category3=" . $arr_search_id[$m_sub_category_3];
            
            /*
            if( $str_category )
            {
                $_category = str_replace('null', '', $str_category);
                $_category = str_replace('-1', '', $_category);
                $_category = str_replace('>  >', '>', $_category);
                $_category = str_replace('>', '%', $_category);
                $_category = str_replace(' ', '', $_category);
                //$_category = str_replace('%%', '', $_category);
                
                //$str_category = substr($str_category,0,-1);
                // $_category = preg_replace ( "/\%$/", '', $_category );
                                                
                if ( $_category && $_category != "%%")
                    $option .= " and str_category like '$_category' ";
            }
            */
            
            $option .= " group by stand_id";
            
            
            
            // count
            $result = mysql_query( $query . $option, $connect );
            $max_row = mysql_num_rows( $result );
            
            if( $sort == 0 )
                $order = " order by reg_date desc, reg_time desc, supply_code, name ";
            else if( $sort == 1 )
                $order = " order by supply_code, name ";
            else if( $sort == 2 )
                $order = " order by name ";
            else if( $sort == 3 )
                $order = " order by sale_stop_date desc ";                
            else if( $sort == 4 )
                $order = " order by location asc ";
            else if( $sort == 5 )
                $order = " order by location asc, name ";
            
            if ( $download )
                $limit = "";
            else
            {
                if ( !$page ) $page = 1;
                $starter = ($page-1) * _line_per_page50;
            
                $limit = " limit $starter, " . _line_per_page50;
            }
            $query_final = $query . $option . $order . $limit;
            
            debug( "상품검색:".$query_final  );
            
            $result = mysql_query ( $query_final, $connect );
            $id_arr = array();
            while( $data = mysql_fetch_assoc($result) )
                $id_arr[] = "'" . $data[stand_id] . "'" ;
            
            $id_str = implode(",", $id_arr);
            
            $query = "select * from products where product_id in ($id_str) ";
            
            debug( "22222:" . $query.$order );
            
            $result = mysql_query($query.$order, $connect);
        }
        
        return $result;
    }   

 
   /////////////////////////////////////
   // copy list
   function get_copy_list ( $id )
   {
      global $connect;

      $query = "select * from products 
                 where is_delete = 0 
                   and (stock_manage is null or stock_manage = 0)
                   and org_id = '$id'";

      $result = mysql_query( $query, $connect );
      
      return $result;
   }

   ///////////////////////////////////
   // date: 2005.09.08 - jk
   // 상품의 판매처 개수
   function get_shop_count ( $product_id )
   {
      global $connect;
      $query = "select count(*) cnt from code_match where id='$product_id' group by id";
//echo $query;

      $r = mysql_query ( $query, $connect );
      $d = mysql_fetch_array( $r );

      if ( $d[cnt] )
         return $d[cnt];
      else
         return 0;
   }  

   //////////////////////////////////////////
   // date: 2005.09.14
   function get_shop_name( $shop_id )
   {
      global $connect;
      $query = "select shop_name from shopinfo where shop_id='$shop_id'";
  
      $r = mysql_query ( $query, $connect );
      $d = mysql_fetch_array ( $r );

      if ( $d[shop_name] )
         return $d[shop_name];
      else
         return $shop_id;
   }

   function get_shop_name2($shop_id)
   {
        global $connect;
        $query = "select shop_name, logo, url from shopinfo where shop_id='$shop_id'";

        $r = mysql_query ( $query, $connect );
        $d = mysql_fetch_array ( $r );

        $shop_logo = "";
        
        if ($d[url]) 
        {
            if( substr($d[url],0,4) != 'http' )  $d[url] = 'http://' . $d[url];
            $shop_logo .= "<a href='$d[url]' target=_new>";
        }
    
        if ( $d[shop_name] )
        {
          if ($d[logo])
            $shop_logo .= "<img src=/images/shop_logo/$d[logo] align=absmiddle alt='$shop_id:$d[shop_name]'>";
          else
            $shop_logo .=  $d[shop_name];
        }
        else
          $shop_logo .=  $shop_id;

        if ($d[url]) $shop_logo .= "</a>";
        
        return $shop_logo;
   }

   // stocks : data
   // arr_row_index : 현재 row의 index값들
   // current_row : 마지막 row의 값
   function get_options( $stocks, $arr_row_index,  $current_row )
   {

echo "<br>===============<br>";
echo  $current_row .":" . $arr_row_index[$current_row] ." - " . count ( $stocks[$current_row] ); 
echo "<br>===============<br>";

      if ( $arr_row_index[$current_row] < count ( $stocks[$current_row] ) )
      {
//        $arr_row_index[$current_row]++;
echo "aaa->" . $arr_row_index[$current_row]++ . "<br>";
         $var = "stocks";
         for( $i=0; $i < count( $arr_row_index ); $i++)
            $var .= "[" . $arr_row_index[$i] . "]";
         
echo "<br>$var";
flush();
         class_C::get_options( $stocks, $arr_row_index, $current_row ); 
      }
      else
      {
         if ( $current_row >= 0 )
         {
            $current_row = $current_row - 1;
echo "<br>row-> $current_row / " . $arr_row_index[$current_row];
flush();
            class_C::get_options( $stocks, $arr_row_index, $current_row ); 
         }
      }

exit;
   }

    //////////////////////////////////////////
    // 상품을 재고 관리 상품으로 만듦
    function stock_build( $name, $options, $product_id='')
    {
        global $connect, $id, $top_url;
       
        $org_id = $product_id ? $product_id : $id;
        $id = $org_id;
        
        $_options = $options;
        
        debug ( "stock_build : id=$id, options=$_options ");
        
        $transaction = $this->begin("옵션별 재고 복사");
        $stocks = array();
        
        // 원 주문을 stock_manage 상품으로 등록
        $_options = str_replace( "\r", "", $_options );
        $_options = split("\n", $_options);
        $row_count = count( $_options );
        
        // data를 쪼갬
        for( $i=0; $i < $row_count; $i++ )
        {
            list ( $key[$i], $_option[$i] ) = split(":", $_options[$i] );
            if( $_option[$i] != null && $_option[$i] != "" )
                $stocks[$i] = split(",", $_option[$i] );
        }
        
        // 각 옵션별 개수를 셈
        for ( $i=0; $i < $row_count; $i++)
        {
            $count[$i] = count ( $stocks[$i] );      
        }
        
        // 옵션 조합의 총 개수 
        $num_row = 1;
        for ( $i=0; $i < $row_count; $i++)
            $arr_row_index[$i] = 0;
        
        $current_row = $num_row;
        
        // search tree 사용
        $start = 1; // 시작
        $position = array ();
        class_C::build_option3( $name, $stocks,  $key, $id);
        
        // barcode생성
        $obj_product = new class_product();
        $obj_product->create_barcode($id);
        
        // 복사 상품 등록
        $this->end( $transaction );
        
    }

   // build_option3
   function build_option3( $name, $stocks , $key, $product_id = '')
   {
      global $connect, $id;
      $id = $product_id ? $product_id : $id;

      $query = "update products set stock_manage = 1, is_represent=1 where product_id='$id'";
      mysql_query ( $query, $connect );
      
      $tot = 0;

	  if( _DOMAIN_ == "alice")
	  {
	  	$opt_head = "";
		$opt_tail = "";
	  }
	  else 
	  {
		$opt_head = "[";
		$opt_tail = "]";
	  }
      
      
      for ( $i = 0; $i< count ( $stocks[0] ); $i++)
      {
         if( $_SESSION[NEW_OPTION_FORMAT] )
            $key1 = $stocks[0][$i];
         else
            $key1 = $key[0] . ":" . $stocks[0][$i];

         if ( !count ( $stocks[1] ) )
         {
            $tot++;
            echo "<script language=javascript>show_txt('$tot번째 처리중')</script>";
            flush();
            if( $_SESSION[NEW_OPTION_FORMAT] )  $key1 = $opt_head . $key1 . $opt_tail;
            class_C::insert_option ( $name, $key1, $id );
         }
         else
         for ( $j = 0; $j< count ( $stocks[1] ); $j++)
         {
            if( $_SESSION[NEW_OPTION_FORMAT] )
                $key2 = $key1 . "-" . $stocks[1][$j];
            else
                $key2 = $key1 . ", " .  $key[1].":" . $stocks[1][$j];
          
            if ( !count ( $stocks[2] ) )
            {
               $tot++;
               echo "<script language=javascript>show_txt('" . $tot ."번째 처리중')</script>";
               flush();
               // insert_option;
               if( $_SESSION[NEW_OPTION_FORMAT] )  $key2 = $opt_head . $key2 . $opt_tail;
               class_C::insert_option ( $name, $key2,$id );
            }
            else
            for ( $k = 0; $k< count ( $stocks[2] ); $k++)
            {
               if( $_SESSION[NEW_OPTION_FORMAT] )
                   $key3 = $key2 . "-" . $stocks[2][$k];
               else
                   $key3 = $key2 . ", " . $key[2] . ":" . $stocks[2][$k];
               
               if ( !count ( $stocks[3] ) )
               {
                  $tot++;
                  echo "<script language=javascript>show_txt('" . $tot . "번째 처리중')</script>";
                        flush();
                  // insert_option;
                  if( $_SESSION[NEW_OPTION_FORMAT] )  $key3 = $opt_head . $key3 . $opt_tail;
                  class_C::insert_option ( $name,$key3, $id );
               }
               else
               for ( $l = 0; $l< count ( $stocks[3] ); $l++)
               {
                  if( $_SESSION[NEW_OPTION_FORMAT] )
                      $key4 = $key3 . "-" . $stocks[3][$l];
                  else
                      $key4 = $key3 . ", " . $key[3] . ":" . $stocks[3][$l];

                  if ( !count ( $stocks[4] ) )
                  {
                     $tot++;
                     echo "<script language=javascript>show_txt('" . $tot . "번째 처리중')</script>";
                        flush();
                     // insert_option;
                     if( $_SESSION[NEW_OPTION_FORMAT] )  $key4 = $opt_head . $key4 . $opt_tail;
                     class_C::insert_option ($name, $key4, $id );
                  }
                  else
                  for ( $m = 0; $m< count ( $stocks[4] ); $m++)
                  {
                     if( $_SESSION[NEW_OPTION_FORMAT] )
                         $key5 = $key4 . "-" . $stocks[4][$m];
                     else
                         $key5 = $key4 . ", " . $key[4] . ":" . $stocks[4][$m];

                     if ( !count ( $stocks[5] ) )
                     {
                        // insert_option;
                        $tot++;
                        echo "<script language=javascript>show_txt('" . $tot . "번째 처리중')</script>";
                        flush();
                        if( $_SESSION[NEW_OPTION_FORMAT] )  $key5 = $opt_head . $key5 . $opt_tail;
                        class_C::insert_option ($name, $key5, $id );
                     }
                     else
                     for ( $n = 0; $n< count ( $stocks[5] ); $n++)
                     {
                        if( $_SESSION[NEW_OPTION_FORMAT] )
                            $key6 = $key5 . "-" . $stocks[5][$n];
                        else
                            $key6 = $key5 . ", " . $key[5] . ":" . $stocks[5][$n];

                        if ( !count ( $stocks[6] ) )
                        {
                           // insert_option;
                           $tot++;
                           echo "<script language=javascript>show_txt('" . $tot . "번째 처리중')</script>";
                           flush();
                           if( $_SESSION[NEW_OPTION_FORMAT] )  $key6 = $opt_head . $key6 . $opt_tail;
                           class_C::insert_option ($name, $key6, $id );
                        }
                        else
                        for ( $o = 0; $o< count ( $stocks[6] ); $o++)
                        {
                           if( $_SESSION[NEW_OPTION_FORMAT] )
                               $key7 = $key6 . "-" . $stocks[6][$o];
                           else
                               $key7 = $key6 . ", " . $key[6] . ":" . $stocks[6][$o];

                           if ( !count ( $stocks[7] ) )
                           {
                              // insert_option;
                              $tot++;
                              echo "<script language=javascript>show_txt('" . $tot . "번째 처리중')</script>";
                              flush();
                              if( $_SESSION[NEW_OPTION_FORMAT] )  $key7 = $opt_head . $key7 . $opt_tail;
                              class_C::insert_option ( $name,$key7, $id );
                           }
                           for ( $p = 0; $p< count ( $stocks[7] ); $p++)
                           {
                              if( $_SESSION[NEW_OPTION_FORMAT] )
                                  $key8 = $key7 . "-" . $stocks[7][$o];
                              else
                                  $key8 = $key7 . ", " . $key[7] . ":" . $stocks[7][$o];

                              if ( !count ( $stocks[8] ) )
                              {
                                 // insert_option;
                                 $tot++;
                                 echo "<script language=javascript>show_txt('" . $tot . "번째 처리중')</script>";
                                 flush();
                                 if( $_SESSION[NEW_OPTION_FORMAT] )  $key8 = $opt_head . $key8 . $opt_tail;
                                 class_C::insert_option ( $name, $key8, $id );
                              }
                              else
                                 $this->jsAlert ( "옵션은 9개이상 처리 불가 합니다 ");
                           }
                        }
                     }
                  }
               }
            }
         }
      }
   }

   // data를 입력
   function insert_option ($name, $option, $id='' )
   {
      global $connect, $org_id;
      $org_id = $id ? $id : $org_id;

      // 옵션 중복이면 등록하지 않는다.
      if( class_product::dup_check_options( $option, $id, $org_id ) )  return;

      // id 생성 
      $query = "select max(max) m from products";
      $result = mysql_query($query, $connect);
      $data = mysql_fetch_array($result);
      $max = $data[m] + 1;
      $id = $id_index . sprintf("%05d", $max);
      $id = "S" . $id;

      // 기존 data 가져오는 작업
      $query = "select * from products where product_id='$org_id'";

      $result = mysql_query($query, $connect);
      $data = mysql_fetch_array($result);
      $org_items = array ("supply_code","name","origin","brand","org_price","supply_price","shop_price","img_500","img_desc1","img_desc2","img_desc3","img_desc4","img_desc5","img_desc6",
                          "is_url_img","is_url_img0","is_url_img1","is_url_img2","is_url_img3","is_url_img4","is_url_img5","is_url_img6",
                          "enable_sale","stock_manage","market_price","trans_code","enable_stock","weight","trans_fee","no_sync","no_stock_sync","maker",
                          "stock_alarm1","stock_alarm2","pack_disable","pack_cnt","location","memo","category","str_category","trans_type","m_category1","m_category2","m_category3");

      // query 만드는 부분      
      $query = "insert into products set product_id='$id', reg_date=Now(), reg_time=Now(), last_update_date=now(), ";

      $i = 1;
      foreach ( $org_items as $item )
      {
         if( $item == 'enable_sale' )
             $query .= "$item = 1";
         else
             $query .= "$item = \"" . addslashes( $data[$item] ) . "\"";
         
         if ( $i != count( $org_items ) )
            $query .= ",";
         $i++;
      }

      $query .= ", max=$max, org_id='$org_id', is_represent=0, options='" . $option . "'";

        debug ( "[stock_option]" . $query );

        mysql_query ( $query, $connect );
        //옵션 상품에 대한 재고 0 초기화
        class_stock::new_current_stock($id);
   }

   function build_option4 ( $stocks, $index )
   {
      for ( $i = 0; $i< count ( $stocks[ $index ] ); $i++)
      {
         if ( $index < count ( $stocks ) )
         {
            $index++;
            class_C::build_option4( $stocks, $index );
         } 
         else
            echo $stock[$index][$i];
      }
   }

   ///////////////////////////
   // search tree
   function search_position( $stocks, &$position, $start=0 )
   {
      if ( $start )
         class_C::begin( $stocks, &$position );

   }

   // 무조건 끝까지 내려가야 함
   function begin( $stocks, &$position )
   {
      echo "<br>begin<br>";

      for ( $i = 0; $i < count ( $stocks ); $i++ )
      {
         array_push( $position, 0 );
      }

      // 시작은 0
      $back = 1;
      class_C::get_position ( $stocks, &$position, $back );

      // 실제 실행
      class_C::option_run( $stocks, $position );

      class_C::build_option2( $stocks, &$position );
      // check column count
   }

   ////////////////////////////////////
   // 
   function build_option2( $stocks, &$position, $i=0 )
   {
echo "<script>hide_waiting()</script>";
      if ( $i == 40 )
      {
         exit;
      }

      // check last element
      if ( class_C::get_position( $stocks, &$position ) )
      {
         $i++;
         echo "<br>" . $i . "th build";
         echo "<br>-----<br>";
         class_C::build_option2( $stocks, &$position , $i);
      }
   }

   ////////////////////////////////////////////
   // 실제 실행
   function option_run( $stock, $position )
   {

echo "run";

   }

   function get_position ( $stocks, &$position, $back = 0 )
   {
      // 무조건 가장 마지막을 가져온다 
      // ex: 0,0,0,3 => 3을 가져온다
      $last = array_pop( &$position );

      // 해당 row의 max count check 
      // 0 부터 시작
      $count_p = count( $position );

         if ( !$back )
            $last++;
         else 
         {
            echo "back->";
            $last = 0;
         }

      // 더 있을 경우 
      if ( count( $stocks[ $count_p ] ) > $last )
      {
         // back으로 거슬러 올라왔었는지 여부 check

         array_push ( $position, $last );

echo "<br>1:". count( $stocks[ $count_p ] );
print_r($position);
echo "<br>";

         // position 정보를 build함
         for ( $i = 0; $i < count ( $stocks ); $i++ )
         {
            if ( $i > $count_p )
               array_push( $position, 0 );
         }
      }
      // 더이상 없을 경우
      // go back을 함 
      else
      {
         // 더이상 갈데가 없으면 false 그렇지 않으면 true
         if ( count ( $position ) == 0 ) 
         {
            $last++;
            array_push ( $position, $last );
echo "<br> no place to go <br>";
print_r($position);
         }

echo "<br> go back <br>";
print_r($position);
         // go back
         class_C::get_position( $stocks, &$position, 1 );
        // class_C::get_position( $stocks, &$position );
flush();
//exit;
      }
/* 

      class_C::get_position( $stocks, &$position );
*/
      return true;
   }

   function go_back( $stocks, &$position )
   {


   }

   function build_option( $stocks, $current_row, $calc_row , $index=0 )
   {
      //  global $connect;

      if ( $current_row > count ( $stocks) )
      {
          echo " <br>current row가 stocks 개수 초과 ";
          exit; 
      }     

      // $current row 증가
      if ( $calc_row == $current_row ) // 같으면 $current_row를 하나 올림
      {
         $current_row++;
         // index 초기화
         $index = 0;
         class_C::build_option( $stocks, $current_row, $calc_row, $index );
      }
      // index 증가
      // calc row의 개수와 index보다 작으면 index를 하나 올린다
      else if ( $index < count ( $stocks[$calc_row] ) )
      {
echo "<br>increas index row  to current: $current_row / $calc_row: calc_row: $calc_row/ index: $index";
         $index++;
         class_C::build_option( $stocks, $current_row, $calc_row, $index );
      }
      // calc_row 증가
      else
      {
         $calc_row++;
         class_C::build_option( $stocks, $current_row, $calc_row, $index );
      }
   }
   
    //////////////////////////////////////
    // 판매 상태 변경 : 옵션 상품의 판매 상태를 변경할 때, 대표상품의 판매상태도 바꿔준다.
    function update_soldout( $org_id )
    {
        global $connect;
        
        $soldout = false;
        $selling = false;

        // 옵션 상품의 판매상태를 조사
        $query = "select distinct enable_sale from products where org_id='$org_id' and is_delete=0";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            if( $data[enable_sale] )  $selling = true;
            else  $soldout = true;
        }
        
        if( !$soldout && !$selling )
            return;
        else if( !$soldout && $selling )
            $enable_sale = 1;
        else if( $soldout && !$selling )
            $enable_sale = 0;
        else
            $enable_sale = 2;
        
        // 대표상품의 판매상태를 변경
        $query = "update products set enable_sale='$enable_sale' where product_id='$org_id'";
        mysql_query($query, $connect);
    }

    //////////////////////////////////////
    // 대표상품 삭제 : 옵션 상품이 삭제될 경우, 대표상품의 삭제 여부 결정
    function check_option_delete( $org_id )
    {
        global $connect;
        
        $query = "select product_id from products where org_id='$org_id' and is_delete=0";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) == 0 )
        {
            // 대표상품삭제
            $query = "update products set is_delete=1, delete_date=now() where product_id='$org_id'";
            mysql_query($query, $connect);
            return true;
        }
        else
            return false;
    }

    // 상품의 재고, 주문 존재를 체크한다.
    function check_order($product_id, $check_stock=0)
    {
        global $connect;
        
        $val = array();
        // product_id는 원 상품 하위 상품이 없는지 확인
        $query       = "select product_id from products where org_id='$product_id' and is_delete=0";
        $result      = mysql_query( $query, $connect );
        if( mysql_num_rows($result) )
        {
            $product_ids = "";
            while ( $data = mysql_fetch_array( $result ) )
                $product_ids .= "'" . $data[product_id] . "',";
        }
        else
            $product_ids = "'" . $product_id . "',";

        $product_ids = substr( $product_ids, 0, strlen( $product_ids) -1 ); 
        
        // 주문에서 찾는다.(배송전이고 취소주문 아닌 경우만)
        $query   = "select count(a.seq) cnt 
                      from orders a, 
                           order_products b
                     where a.seq = b.order_seq and
                           a.status < 8 and
                           b.order_cs not in (1,2,3,4) and
                           b.product_id in ( $product_ids )";
debug("옵션관리취소1 : " . $query);
        $result  = mysql_query( $query, $connect );
        $data    = mysql_fetch_array( $result );
        $val[is_reg] = $data[cnt] ? $data[cnt] : 0; // true
        $val[query1] = $query;

        $val[stock] = 0;

        if( $check_stock )
        {
            // current_stock에서 찾는다.  (재고는 조사하지 않는다)
            $query = "select sum(stock) cnt from current_stock where product_id in ( $product_ids )";
debug("옵션관리취소2 : " . $query);
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
            $val[stock]  = $data[cnt] ? $data[cnt] : 0;
        }
        
        
        return $val;
    }
    
    //#########################################
    //
    //  상품정보변경 로그
    // 
    /*
        1. 품절
            1) 전체상품목록에서 품절체크박스
            2) 상품정보변경에서 판매상태수정
            3) 상품정보변경에서 전체품절처리
            4) 상품정보변경에서 전체판매가능
            5) 상품정보변경 옵션목록에서 품절체크박스
            6) 옵션상품 정보수정에서 판매상태수정
            7) 상품일괄수정
            8) 상품일괄선택수정
    */
    function insert_products_history($product_id, $work, $cmt)
    {
        global $connect;
        
        $query = "insert products_history
                     set crdate = now()
                        ,cruser = '$_SESSION[LOGIN_ID]'
                        ,product_id = '$product_id'
                        ,work = '$work' 
                        ,cmt = '$cmt' ";
        mysql_query($query, $connect);
    }
}
// class end
?>
