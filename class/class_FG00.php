<?
require_once "class_top.php";
require_once "class_G.php";
require_once "class_C.php";
require_once "class_D.php";
require_once "class_file.php";
require_once "class_ui.php";
require_once "class_product.php";
require_once "class_supply.php";

////////////////////////////////
// class name: class_FG00
//

class class_FG00 extends class_top {

    // 엑셀파일 만들기 - 매칭버전
    function build_file()
    {
        global $connect, $page, $start_date, $end_date, $start_hour, $end_hour, $shop_id, $supply_id, $cmb_status, $products_sort;
        $filename = Date(Ymd) . "_" . rand(1,10000) . ".xls";
        $ask_date = Date("Y-m-d");

        switch($cmb_status){
            case "1":
                $option_status = "1";
                break;
            case "2":
                $option_status = "7";
                 break;
            default:
                $option_status = "1,7";
        }

        $query = "select a.product_id product_id, 
                         a.supply_id,
                         a.shop_id as shop_id,
                         a.shop_options,
                         a.marked a_marked,
                         sum(a.qty) qty,
                         b.name name,
                         b.brand brand,
                         b.barcode b_barcode,
                         b.location b_location,
                         b.options options,
                         e.org_price org_price,
                         b.enable_sale enable_sale,
                         b.supply_options supply_options,
                         c.name supply_name,
                         c.code supply_code,
                         c.tel supply_tel,
                         c.mobile supply_mobile,
                         c.address1 addr1,
                         c.address2 addr2,
                         ifnull(e.reg_date, b.reg_date) reg_date
                    from order_products a, userinfo c, orders d,
                         products b left outer join products e on b.org_id=e.product_id
                   where " . $this->query_where("d","a","b","c");

        if( _DOMAIN_ == 'ilovej' )
            $sort_c_options = "b.product_id";
        else
            $sort_c_options = "b.options";

        $query .= " group by a.product_id";
        if( $_SESSION[STOCK_MANAGE_USE] != 1 )
            $query .= ", a.shop_options ";

        $query .= " order by ";
        
        if( _DOMAIN_ == 'zangternet')
            $query .= "b.location, ";

        if ( $products_sort == 1 ) // 상품명
            $query .= "b.name, $sort_c_options ";
        else if ( $products_sort == 2 ) // 공급처 > 상품명
            $query .= "c.name, b.name, $sort_c_options ";
        else if ( $products_sort == 3 )  // 등록일 > 상품명
            $query .= "reg_date, b.name, $sort_c_options ";
        else if ( $products_sort == 4 )  // 등록일 > 공급처 > 상품명
            $query .= "reg_date, c.name, b.name, $sort_c_options ";           
        else
            $query .= "b.name, $sort_c_options ";

        if( $_SESSION[STOCK_MANAGE_USE] != 1 )
            $query .= ", a.shop_options ";

        $arr_result[] = array(
            "no"           => "no",
            "collect_date" => "발주일",
            "ask_date"     => "조회일",
            "product_id"   => "상품코드",
            "product_name" => "상품명",
            "brand"        => "사입처상품명",
            "supply_options" => "사입처옵션명",
            "barcode"      => "바코드",
            "location"     => "로케이션",
            "supply_tel"   => "전화",
            "supply_addr"  => "주소",
            "options"      => "옵션",
            "supply_name"  => "공급처",
            "supply_code"  => "공급처코드",
            "qty"          => "수량",
            "org_price"    => "원가",
            "stock"        => "재고",
            "soldout"      => "품절"
        );   
debug("FG00 download : " . $query);
       
        $result = mysql_query( $query, $connect );
        $i = 1;
        echo "<script language='javascript'>\n";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            if( $_SESSION[STOCK_MANAGE_USE] == 1 )
                $stock       = $this->get_stock( $data[product_id] );
            else
                $stock = "";

            // 가장 오래된 발주일 구하기
            $query_date = "select a.collect_date as collect_date 
                             from orders a, order_products b
                            where b.product_id='$data[product_id]' and 
                                  b.order_cs not in (1,2,3,4) and
                                  a.status in (" .  $option_status . ") and
                                  a.collect_date >= '$start_date' and
                                  a.collect_date <= '$end_date' and 
                                  a.collect_time >= '$start_hour:00:00' and
                     			  a.collect_time <= '$end_hour:59:59' and
                                  a.seq = b.order_seq "
                                  . ($str_shop_id ? " and a.shop_id in ($str_shop_id)" : "")
                                  . ($supply_id != "공급처" ? " and b.supply_id=$supply_id " : "" ) . 
                        "order by a.collect_date limit 1";
            $result_date = mysql_query($query_date, $connect);
            $data_date = mysql_fetch_assoc($result_date);

            $arr_result[] = array( 
                 "no"           => $i
                ,"collect_date" => $data_date["collect_date"]
                ,"ask_date"     => $ask_date
                ,"product_id"   => $data['product_id']
                ,"product_name" => $data['name']
                ,"brand"        => $data['brand']
                ,"supply_options" => $data['supply_options']
                ,"barcode"      => $data['b_barcode']
                ,"location"     => $data['b_location']
                ,"supply_tel"   => $data['supply_tel'] . " / " . $data['supply_mobile']
                ,"supply_addr"  => $data['addr1'] . " " . $data['addr2']
                ,"options"      => ( $_SESSION[STOCK_MANAGE_USE] == 1 ? $data['options'] : $data['shop_options'] )
                ,"supply_name"  => $data['supply_name']
                ,"supply_code"  => $data['supply_code']
                ,"qty"          => $data['qty']
                ,"org_price"    => $data['org_price']
                ,"stock"        => $stock 
                ,"soldout"      => $data['enable_sale'] = ($data['enable_sale'] ? "" : "품절")
            );
            
            $i++;
            if ( $i%97 == 0 )
            {
                echo "parent.show_txt( '$i' )\n";    
                flush();
            }
        }
        
        echo "</script>\n";
        
        $this->make_file( $arr_result, $filename );
        
        echo "<script language='javascript'>
                        parent.hide_waiting()
                        parent.download('$filename')
              </script>";
              
        flush(); 
    }

    // 엑셀파일 만들기 - 미매칭버전
    function build_file2()
    {
        global $connect, $page, $start_date, $end_date, $start_hour, $end_hour, $shop_id, $supply_id, $cmb_status, $products_sort;
        $filename = Date(Ymd) . "_" . rand(1,10000). ".xls";
        $ask_date = Date("Y-m-d");
        
        $query = "select a.product_name
                        ,a.shop_id a_shop_id
                        ,b.shop_options
                        ,b.marked b_marked
                        ,sum(b.qty) qty
                    from orders a, order_products b
                   where " . $this->query_where("a","b","","");
        
        $query .= " group by b.shop_options";
        $query .= " order by b.shop_options ";           

        if( $_SESSION[PRODUCT_NAME_EXP] )
        {
            $arr_result[] = array(
                "no"           => "no",
                "qty"          => "수량",
                "product_name" => "상품명",
                "shop_options" => "옵션"
            );   
        }else{
            $arr_result[] = array(
                "no"           => "no",
                "qty"          => "수량",
                "shop_options" => "상품"
            );
        }   

        $result = mysql_query( $query, $connect );
        $i = 1;
        echo "<script language='javascript'>\n";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            if( _DOMAIN_ == 'memorette' && $data[b_marked] == 2 && ($data[a_shop_id] % 100 == 1 || $data[a_shop_id] % 100 == 2 || $data[a_shop_id] % 100 == 78 || $data[a_shop_id] % 100 == 79 ) )
                $data['product_name'] = "추가구성";
            
            if( $_SESSION[PRODUCT_NAME_EXP] )
            {
                $arr_result[] = array( 
                     "no"           => $i
                    ,"qty"          => $data['qty']
                    ,"product_name" => $data[product_name]
                    ,"shop_options" => $data['shop_options']
                );
            }else{
                $arr_result[] = array( 
                     "no"           => $i
                    ,"qty"          => $data['qty']
                    ,"shop_options" => $data['shop_options']
                );
            }
            
            $i++;
            if ( $i%97 == 0 )
            {
                echo "parent.show_txt( '$i' )\n";    
                flush();
            }
        }
        
        echo "</script>\n";
        
        $this->make_file( $arr_result, $filename );
        
        echo "<script language='javascript'>
                        parent.hide_waiting()
                        parent.download('$filename')
              </script>";
              
        flush(); 
    }

   function make_file( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
   {
	    $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer .= "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<style>
.num_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\"\#\,\#\#0_ \;\[Red\]\\-\#\,\#\#0\\ \" ;
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
            $row = $arr_datas[$i];
            $buffer = "<tr>\n";

            if( $i == 0 )
            {
                // for column
                foreach ( $row as $key=>$value) 
                    $buffer .= "<td style='font:bold 10pt \"굴림\"; white-space:nowrap; background:#CCFFCC;'>" . $value . "</td>";
            }
            else
            {
                // for column
                foreach ( $row as $key=>$value) 
                {
                    if( $key == 'no' || $key == 'org_price' || $key == 'qty' || $key == 'stock' )
                        $buffer .= "<td class=num_item>" . $value . "</td>";
                    else
                        $buffer .= "<td class=str_item>" . $value . "</td>";
                }
            }
            
            $buffer .= "</tr>\n";

            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");

        ////////////////////////////////////// 
        // file close and delete it 
        // file은 보관함
        fclose($fp);

        return $filename; 
    }
    
    function file_download()
    {
        global $filename;
       $obj = new class_file();
       $obj->download_file($filename, "data_" . $filename);
    }
    
    // FG00 display - 매칭버전
    function query()
    {
        global $connect, $page, $start_date, $end_date, $start_hour, $end_hour, $shop_id, $supply_id, $cmb_status, $enable_sale, $products_sort;
        $start = ( $page - 1 ) * 30;
        $arr_data = array();
        
        // list
        $query = "select a.product_id, 
                         a.supply_id,
                         a.shop_id as shop_id,  
                         a.shop_options,
                         b.img_500,
                         sum(a.qty) qty,
                         b.name name,
                         b.brand brand,
                         b.options options,
                         e.org_price org_price,
                         b.enable_sale enable_sale,
                         b.barcode b_barcode,
                         b.location b_location,
                         c.name supply_name,
                         b.supply_options supply_options,
                         b.is_url_img0 b_is_url_img0,
                         ifnull(e.reg_date, b.reg_date) reg_date
                    from order_products a, userinfo c, orders d,
                         products b left outer join products e on b.org_id=e.product_id
                   where " . $this->query_where("d","a","b","c");

        $query .= " group by a.product_id";
        if( $_SESSION[STOCK_MANAGE_USE] != 1 )
            $query .= ", a.shop_options ";
debug("FG00 query: " . $query);
        // 전체 개수
        $result = mysql_query( $query, $connect );
        $arr_data['total_rows'] = mysql_num_rows($result);
        
        $arr_data['list']    = array();
        $arr_data['success'] = 0;

        if( _DOMAIN_ == 'ilovej' )
            $sort_c_options = "b.product_id";
        else
            $sort_c_options = "b.options";

        $query .= " order by ";

        if( _DOMAIN_ == 'zangternet' )
            $query .= "b.location, ";

        if ( $products_sort == 1 ) // 상품명
            $query .= "b.name, $sort_c_options ";
        else if ( $products_sort == 2 ) // 공급처 > 상품명
            $query .= "c.name, b.name, $sort_c_options ";
        else if ( $products_sort == 3 )  // 등록일 > 상품명
            $query .= "reg_date, b.name, $sort_c_options ";
        else if ( $products_sort == 4 )  // 등록일 > 공급처 > 상품명
            $query .= "reg_date, c.name, b.name, $sort_c_options ";
            
        else if ( $products_sort == 8 )  // 로케이션
            $query .= " b.location desc ";
        else if ( $products_sort == 9)  // 로케이션 > 상품명
            $query .= " b.location desc, b.name";
        else
            $query .= "b.name, $sort_c_options ";

        if( $_SESSION[STOCK_MANAGE_USE] != 1 )
            $query .= ", a.shop_options ";

        $query .= " limit $start, 30";               
        $result = mysql_query ($query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )        
        {
            if( $data[b_is_url_img0] )
			    $image_url = ($data[img_500]) ? $data[img_500] : "";
			else
			    $image_url = ($data[img_500]) ? _IMG_SERVER_ . "/" . _upload_path . "/" . $data[img_500] : "";
			
			if ($image_url) $img_url = "<img src='$image_url' width=60>";
			else $img_url = " ";

            if( $_SESSION[STOCK_MANAGE_USE] == 1 )
                $stock = $this->get_stock( $data[product_id] );
            else
                $stock = 0;
            
            $arr_data['list'][]     = array(
                        product_id    => $data[product_id]
                        ,product_name => $data['name']
                        ,brand        => $data['brand']
                        ,options      => $data['options']
                        ,image_url    => $img_url
                        ,barcode      => $data['b_barcode']
                        ,location     => $data['b_location']
                        ,shop_id      => $data['shop_id']
                        ,shop_options => $data['shop_options']
                        ,supply_name  => $data['supply_name']
                        ,supply_options  => $data['supply_options']
                        ,org_price    => number_format( $data['org_price'] )
                        ,qty          => number_format( $data[qty] )
                        ,current_stock=> number_format( $stock )
                        ,enable_sale  => ($data[enable_sale] ? "" : "품절")
                    );
        }                
        $arr_data['success'] = count( $arr_data['list'] );
        echo json_encode( $arr_data );                      
    }
    
    // FG00 display - 미매칭버전
    function query3()
    {
        global $connect, $page, $start_date, $end_date, $start_hour, $end_hour, $shop_id, $supply_id, $cmb_status, $enable_sale, $products_sort;
        $start = ( $page - 1 ) * 30;
        $arr_data = array();
        
        // list
        $query = "select a.product_name, 
                         b.shop_options,
                         sum(b.qty) qty
                    from orders a, order_products b
                   where " . $this->query_where("a","b","","");
        $query .= " group by b.shop_options";

        // 전체 개수
        $result = mysql_query( $query, $connect );
        $arr_data['total_rows'] = mysql_num_rows($result);
        
        $arr_data['list']    = array();
        $arr_data['success'] = 0;

        $query .= " order by b.shop_options ";
        $query .= " limit $start, 30";               
debug( "미출고 : " . $query );
        $result = mysql_query ($query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )        
        {
            $stock = $this->get_stock( $data[product_id] );
            
            $arr_data['list'][]     = array(
                        qty          => number_format( $data[qty] ),
                        image_url    => " ",
                        shop_options => ( $_SESSION[PRODUCT_NAME_EXP] ? $data[product_name] . " " : "" ) . $data['shop_options']
                    );
        }                
        $arr_data['success'] = count( $arr_data['list'] );
        echo json_encode( $arr_data );                      
    }

    function query_where($orders,$order_products,$products,$userinfo)
    {
        global $connect, $page, $start_date, $end_date, $start_hour, $end_hour, $shop_id, $supply_id, $cmb_status, $enable_sale, $products_sort;

        switch($cmb_status){
            case "1":
                $option_status = "1";
                break;
            case "2":
                $option_status = "7";
                 break;
            default:
                $option_status = "1,7";
        }

        $where_str = "   $orders.seq = $order_products.order_seq
                     and $order_products.order_cs not in (1,2,3,4)
                     and $orders.status in (" . $option_status . ")
                     and timestamp($orders.collect_date, $orders.collect_time) >= '$start_date $start_hour:00:00'
                     and timestamp($orders.collect_date, $orders.collect_time) <= '$end_date $end_hour:59:59' ";
					 
        if( $products > "" )
            $where_str .= " and $order_products.product_id = $products.product_id ";

        if( $userinfo > "" )
            $where_str .= " and $order_products.supply_id = $userinfo.code ";

        if( is_array($shop_id) && count($shop_id) )
            $where_str .= " and $order_products.shop_id in (" . implode(",",$shop_id) . ")";
        else if( $shop_id > "" )   // 판매처
            $where_str .= " and $order_products.shop_id in ($shop_id)";

        if ( $supply_id > "" && $supply_id != "공급처" && $supply_id != "undefined" )
            $where_str .= " and $order_products.supply_id = '$supply_id'";
       
        return $where_str;
    }

    function get_stock( $product_id )
    {
        global $connect;
        
        $query = "select sum(stock) stock from current_stock where product_id='$product_id' and bad=0 group by product_id";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        return $data[stock] ? $data[stock] : 0;   
    }

    ///////////////////////////////////////////
    function FG00()
    {
        global $connect,$act,$start_date,$end_date,$start_hour,$end_hour,$supply_id,$shop_id;
        global $template;
        
        $transaction = $this->begin("미발주요약표");
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        $this->end($transaction);
    }

    ///////////////////////////////////////////
    function FG01()
    {
        global $connect, $template, $start_date, $end_date, $start_hour, $end_hour, $shop_id, $supply_id, $cmb_status, $products_sort;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
        $this->end($transaction);
    }

    ///////////////////////////////////////////
    function FG02()
    {
        global $connect, $template, $start_date, $end_date, $start_hour, $end_hour, $shop_id, $supply_id, $cmb_status, $products_sort;

        // list
        $query = "select a.product_id, 
                         a.supply_id,
                         a.shop_id as shop_id,  
                         a.shop_options,
                         b.img_500,
                         sum(a.qty) qty,
                         b.name name,
                         b.brand brand,
                         b.options options,
                         e.org_price org_price,
                         b.enable_sale enable_sale,
                         b.barcode b_barcode,
                         b.location b_location,
                         c.name supply_name,
                         b.is_url_img0 b_is_url_img0,
                         ifnull(e.reg_date, b.reg_date) reg_date
                    from order_products a, userinfo c, orders d,
                         products b left outer join products e on b.org_id=e.product_id
                   where " . $this->query_where("d","a","b","c");
					 
        $query .= " group by a.product_id";
        if( $_SESSION[STOCK_MANAGE_USE] != 1 )
            $query .= ", a.shop_options ";
debug("FG00 print : " . $query);
        // 전체 개수
        $result = mysql_query( $query, $connect );
        $arr_data['total_rows'] = mysql_num_rows($result);
        
        $arr_data['list']    = array();
        $arr_data['success'] = 0;

        if( _DOMAIN_ == 'ilovej' )
            $sort_c_options = "b.product_id";
        else
            $sort_c_options = "b.options";

        $query .= " order by ";

        if( _DOMAIN_ == 'zangternet' )
            $query .= "b.location, ";

        if ( $products_sort == 1 ) // 상품명
            $query .= "b.name, $sort_c_options ";
        else if ( $products_sort == 2 ) // 공급처 > 상품명
            $query .= "c.name, b.name, $sort_c_options ";
        else if ( $products_sort == 3 )  // 등록일 > 상품명
            $query .= "reg_date, b.name, $sort_c_options ";
        else if ( $products_sort == 4 )  // 등록일 > 공급처 > 상품명
            $query .= "reg_date, c.name, b.name, $sort_c_options ";
        else
            $query .= "b.name, $sort_c_options ";

        if( $_SESSION[STOCK_MANAGE_USE] != 1 )
            $query .= ", a.shop_options ";

        $result = mysql_query ($query, $connect );

        include "template/F/FG02.htm";
    }

    function get_brand_name( $org_id )
    {
        global $connect;
        $query = "select brand from products where product_id='$org_id'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        return $data[brand];
    }

    function get_phone( $supply_id )
    {
        global $connect;

        $query = "select * from userinfo where code='$supply_id'";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        return "$data[tel] / $data[mobile]";
    }

    //==================================
    // excel download
    // date: 2007.7.31 - jk
    function download2()
    {
        global $connect, $saveTarget, $download_type;
        global $trans_corp, $trans_format;
        global $start_date, $end_date;


        $query = "select * from orders 
                        where status in (1,2,7) 
                        and order_cs not in (1,2,3,4,12) 
                        and collect_date >= '$start_date' 
                        and collect_date <= '$end_date' 
                order by product_id";

        $result = mysql_query ( $query, $connect );
                
        // file open
        $handle = fopen ($saveTarget, "w");
    }
}

?>
