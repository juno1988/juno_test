<?
require_once "class_E.php";
require_once "class_B.php";
require_once "class_C.php";
require_once "class_top.php";
require_once "class_D.php";
require_once "class_product.php";
require_once "class_file.php";
require_once "ExcelReader/reader.php";
require_once "lib/ez_excel_lib.php";

////////////////////////////////
// class name: class_DZ00
//
class class_DZ00 extends class_top 
{
   var $order_id;
   var $debug = "on"; // 전체 download: on/off
   var $format;
   var $font   = 'Arial'; 
   var $size   = 10; 
   var $align  = 'right'; 
   var $valign = 'vcenter'; 
   var $bold   = 0; 
   var $italic = 0; 

   function DZ00()
   {
      global $template, $start_date, $end_date, $shop_id, $sub;
      global $page, $seq,$pack, $order_id, $recv_name, $recv_mobile, $product_id, $product_name, $options, $status_sel, $order_cs_sel, $recv_address, $trans_no, $priority, $enable_sale, $_date, $supply_code;

      $par_arr = array("template","action","_date","start_date","end_date","status_sel","order_cs_sel","seq","pack","order_id","recv_name","recv_mobile","product_id",
                 "product_name","options","trans_no","recv_address","page","supply_code");
      $link_url_list = $this->build_link_par($par_arr);  


      $line_per_page = _line_per_page;
      $link_url = "?" . $this->build_link_url();

      if ( $_REQUEST["page"] )
      {
         echo "<script>show_waiting()</script>";
         $opt = "only_not_trans";
         $result = $this->search2( &$total_orders, &$total_rows ); 
      }

      if (!$start_date) $start_date = date('Y-m-d', strtotime('-60 day'));
          $end_date = $end_date;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";

      if ( $_REQUEST["page"] )
         echo "<script>hide_waiting()</script>";
   }

    //============================================
    // 신발주용 검색로직
    // 2009-7-20 - jk
    function search2( &$total_orders, &$total_rows, $is_download )
     {
            global $connect, $start_date, $end_date, $shop_id,$page, $seq, $pack, $order_id, $recv_name, $recv_mobile, 
                   $product_id, $product_name, $options,$status_sel,$order_cs_sel, $recv_address,$trans_no, $_date, $supply_code;

            // 상품 개수
            // 주문 개수
            $query_order_cnt   = "select count(distinct orders.seq) cnt ";
            $query_product_cnt = "select count(distinct order_products.seq) cnt ";

            // 주문 정보
            $query = "select orders.seq,
                             orders.pack,
                             orders.shop_id,
                             orders.order_id,
                             orders.status,
                             order_products.order_cs,
                             orders.hold,
                             orders.trans_who,
                             orders.org_trans_who,
                             order_products.product_id,
                             order_products.supply_id,
                             orders.shop_product_id,
                             orders.product_name,
                             orders.options,
                             order_products.qty,
                             orders.amount,
                             orders.supply_price,
                             orders.trans_price,
                             orders.order_date,
                             order_products.cancel_date,
                             orders.collect_date,
                             orders.collect_time,
                             orders.trans_date,
                             orders.trans_no,
                             orders.recv_zip,
                             orders.recv_address,
                             orders.trans_date_pos,
                             orders.priority,
                             orders.order_name,
                             orders.order_tel,
                             orders.order_mobile,
                             orders.recv_name,
                             orders.recv_tel,
                             orders.recv_mobile,
                             orders.memo";

            $opt   = " from order_products,orders
               where order_products.order_seq = orders.seq
                                 and orders.$_date >= '$start_date 00:00:00'
                 and orders.$_date <= '$end_date 23:59:59' ";

            if ( $shop_id )
                    $opt .= " and orders.shop_id = '$shop_id' ";

            if ( $supply_code )
                    $opt .= " and order_products.supply_id = '$supply_code' ";

            switch( $status_sel )
            {
                case 1: $opt .= " and orders.status = 0 "; break;
                case 2: $opt .= " and orders.status = 1 "; break;
                case 3: $opt .= " and orders.status = 7 "; break;
                case 4: $opt .= " and orders.status = 8 "; break;
            }
            
            switch( $order_cs_sel )
            {
                case 1: $opt .= " and order_products.order_cs in ( 0 )"; break;
                case 2: $opt .= " and order_products.order_cs in ( 1,2,3,4 )"; break;
                case 3: $opt .= " and order_products.order_cs in ( 5,6,7,8 )"; break;
                case 4: $opt .= " and order_products.order_cs in ( 1,2 )"; break;
                case 5: $opt .= " and order_products.order_cs in ( 3,4 )"; break;
                case 6: $opt .= " and order_products.order_cs in ( 5,6 )"; break;
                case 7: $opt .= " and order_products.order_cs in ( 7,8 )"; break;
                case 8: $opt .= " and orders.hold > 0"; break;
                case 9: $opt .= " and orders.cross_change > 0"; break;
            }

            if ( $recv_name )
                    $opt .= " and orders.recv_name = '$recv_name' ";

            if ( $recv_mobile )
                    $opt .= " and orders.recv_name = '$recv_mobile' ";
    
            if ( $product_id )        
                    $opt .= " and order_products.product_id = '$product_id' ";

            if ( $trans_no )
                    $opt .= " and orders.trans_no = '$trans_no' ";
    
            if ( $seq )
                    $opt .= " and orders.seq = '$seq' ";

            if ( $order_id )
                    $opt .= " and orders.order_id = '$order_id' ";

            if ( $pack )
                    $opt .= " and orders.pack = '$pack' ";

            // 주문 개수
            $query_order_cnt   = $query_order_cnt . $opt;
            $query_product_cnt = $query_product_cnt . $opt;

            // 전체 주문 개수
            $result_order_cnt = mysql_query( $query_order_cnt, $connect );        
            $data             = mysql_fetch_assoc( $result_order_cnt );
            $total_orders     = $data[cnt];

            // 전체 상품 개수
            $result_product_cnt = mysql_query( $query_product_cnt, $connect );        
            $data               = mysql_fetch_assoc( $result_product_cnt );
            $total_rows         = $data[cnt];
                    
            if ( !$is_download )
            {
                // limit
                $start = (($page ? $page : 1 )-1) * 20;
                $opt .= " limit $start, 20";
            }

            $result = mysql_query( $query . $opt, $connect );        
            return $result;
    }


   function search( &$_rows , $opt="", $download = 0 )
   {
      global $connect, $start_date, $end_date, $shop_id, $sub;
      global $page, $seq, $pack, $order_id, $recv_name, $recv_mobile, $product_id, $product_name, $options,$status, $order_cs, $recv_address,$trans_no, $priority, $enable_sale, $_date, $warehouse;

      $today= date('Ymd', strtotime("now"));

      $start_date = $start_date ? $start_date : $today;
      $end_date = $end_date ? $end_date : $today;

      $line_per_page = _line_per_page;
      $page = $_REQUEST["page"];
      $today= date('Ymd', strtotime("now"));

      if ( !$page ) $page = 1;
      $start = ( $page - 1 ) * 20;

      ////////////////////////////////////////////////////////////////
      $query_cnt = "select count(*) as cnt, sum(orders.qty) tot_qty ";
      $query = "select orders.*, date_format( trans_date, '%Y-%m-%d') trans_date, products.name, products.options opt ";

      ////////////////////////////////////////////////////////////////
      $option = " from orders , products 
                 where orders.product_id = products.product_id
                   and $_date >= '$start_date 00:00:00'
                   and $_date <= '$end_date 23:59:59'";

      ///////////////////////////////////////////////////////
      // warehouse: 1 => 반포의 경우
      if ( $warehouse )
      {

      }

      if ( $seq )
         $option .= " and orders.seq = '$seq' ";

      if ( $pack )
         $option .= " and orders.pack = '$pack' ";

//echo $option;

      if ( $sub )
         $option .= " and orders.order_subid >= '$sub'";
        
      if ( !$enable_sale )
         $option .= " and products.enable_sale = '$enable_sale'";

      if ( $priority )
         $option .= " and orders.priority = '$priority'";

      if ( $order_id )
         $option .= " and orders.order_id = '$order_id'";

      if ( $recv_name )
         $option .= " and orders.recv_name = '$recv_name'";

      if ( $recv_mobile )
         $option .= " and orders.recv_mobile = '$recv_mobile'";

      if ( $shop_id)
         $option .= " and orders.shop_id = $shop_id ";

      if ( $trans_no )
         $option .= " and orders.trans_no = $trans_no";

      if ( $recv_address )
      {
               $recv_address = str_replace (" ", "%", $recv_address );
         $option .= " and orders.recv_address like '%$recv_address%'";
      }

      if ( $product_id)
         $option .= " and products.product_id = '$product_id'";

      if ( $product_name )
         $option .= " and products.name like '%$product_name%'";

      if ( $options )
      {
               $options = str_replace (" ", "%", $options );
         $option .= " and products.options like '%$options%'";
      }

      if ( $status )
         if ( $status == 99 )
            $option .= " and orders.status not in ( 1,7,8 )";
         else if ( $status == 1)        // 접수
            $option .= " and orders.status in (1,2) ";
         else if ( $status == 98 )
            $option .= " and orders.hold > 0";
         else
            $option .= " and orders.status = '$status'";

      if ( $order_cs )
      {
         switch ( $order_cs )
         {
            case "91": // 정상
               $option .= " and orders.order_cs = 0";
               break;
            case "92": // 취소
               $option .= " and orders.order_cs in ( 1,12,3,4,2 )";
               break;
            case "93": // 교환
               $option .= " and orders.order_cs in ( 11,5,7,13,6,8 )";
               break;
            default:
               $option .= " and orders.order_cs = $order_cs";
         }
      }


      $option .= " order by $_date desc";

      if ( !$download )
        $limit = " limit $start, $line_per_page";

      ////////////////////////////////////////////////////////////////
      // total count
      $result     = mysql_query ( $query_cnt . $option, $connect);

      $data       = mysql_fetch_array ( $result );
      $_rows[total_rows] = $data[cnt];
      $_rows[total_qty]  = $data[tot_qty];

      $result     = mysql_query ( $query . $option . $limit, $connect );

//if ( $_SESSION[LOGIN_LEVEL] == 9 )
    print $query_cnt . $option;
//exit;

      /////////////////////////////////////////////////////////////// 
      return $result;
   } 

   // jk 2008.7.21
   function _getFormatArray($params = NULL) { 
    $temp = array('font'   => $this->font, 
                  'size'   => $this->size, 
                  'bold'   => $this->bold, 
                  'align'  => $this->align, 
                  'valign' => $this->valign); 
    if(isset($params)) { 
      foreach($params as $key => $value) { 
        $temp[$key] = $value; 
      } 
    } 
    return $temp; 
  } 


    function save_file()
    {
        global $connect, $saveTarget, $filename, $search_date;
        
        // download format에 대한 정보를 가져온다
        $download_items = array(
            "supply_name"         => "공급처",
            "product_name"        => "상품명",
            "options"             => "선택사항",
            "real_options"        => "실제 옵션",
            "qty"                 => "판매개수",
            "org_price"           => "원가", 
            "order_date"          => "주문일",
            "refund_date"         => "취소일",
            "collect_date"        => "발주일",
            "collect_time"        => "발주시간",
            "trans_date"          => "송장입력일",
            "trans_no"            => "송장번호",
            "recv_zip"            => "배송지우편번호",
            "recv_address"        => "배송지주소",
            "trans_date_pos"      => "배송일",
            "order_name"          => "주문자",        
            "recv_name"           => "수령자",        
            "recv_tel"            => "수령자전화",        
            "recv_mobile"         => "수령자핸드폰",        
            "memo"                => "메모",
            "seq"                 => "관리번호"
        );
    
        $_rows     = "";
        $_products = "";
        $download  = 1;
        $result    = $this->search2( &$_rows, &$_products , $download ); 
        
        $arr = array();
        // header
        $_row = array();
        foreach( $download_items as $key=>$value ) 
        {
            $_row[] = $value;
        }
        $arr[] = $_row;
        
        // data
        $i = 0;
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $_row = array();
            foreach( $download_items as $key=>$value ) 
            {
                $product_info = class_product::get_info( $data[product_id], " name,options,barcode,org_price " );
                $_row[] = $this->get_data( $data, $key,$product_info );
            }
            $arr[] = $_row;

            $i++;
            if( $i % 73 == 0 )
            {
                $msg = " $i / $_products ";
                echo "<script language='javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
        }     
        
        $obj = new class_file();
        $obj->save_file( $arr, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    } 

    //////////////////////////////////////
    // 다운로드 - 파일 다운받기
    function download2()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "order_list.xls");
    }    

   function get_data ( $data, $key, $info='')
   {
      $arr_chars     = array("`","/","=","\r", "\n", "\r\n","\t", ",", ".", ";", chr(13),"\"","'","<br>" );
      $_option       = "";

      switch ( $key )
      {
          case "order_cs":
              return $this->get_order_cs2( $data['order_cs'] );
              break;
          case "status":
              return $this->get_order_status2( $data['status'] );
              break;
          case "shop_name":
              return class_D::get_shop_name($data[shop_id]);
              break;
          case "barcode":
              return $info['barcode'];
              break;
          case "supply_name":
              return $this->get_supply_name2($data[supply_id]);
              break;
          case "real_product_name":
              return $data[product_name];
              break;
          case "real_options":
              return $data[options];
              break;
          case "product_name":
              return $info['name'];
              break;
          case "options":
              return $info['options'];
              break;
          case "org_price":
              return $info['org_price'];
              break;
          default:
              $val = $data[$key] ? $data[$key] : "";
              return  str_replace( array("=","\r", "\n", "\r\n","\t" ), " ", $val );
              break; 
      }
   }

   function get_cs_reason( $seq )
   {
        global $connect;
        $query = "select cs_reason from csinfo where order_seq=$seq";
        $result = mysql_query( $query, $connect );
        $_str = "";
        while ( $data = mysql_fetch_array( $result ) )
         {
            if ( $data[cs_reason] )
                    $_str = $data[cs_reason];
        }
        return $_str;
   }
}
