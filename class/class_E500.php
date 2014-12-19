<?
///////////////////////////////////////////
// name: class_E500
// date: 2005.11.1
//////////////////////////////////////////
require_once "class_top.php";
require_once "class_D.php";
require_once "class_product.php";
require_once "class_E900.php";

class class_E500 extends class_top {

    ///////////////////////////////////////////
    //
    function E500()
    {
	global $connect;
	global $template, $seq, $isMulti;

        if ( $seq )
        {
            $query = "select * from orders where seq='$seq'";
            $result = mysql_query( $query, $connect );
            $data = mysql_fetch_array ( $result );
        }

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
 
    ///////////////////////////////////////////
    //
    function E501()
    {
	global $connect;
	global $template, $page;
        global $product_id, $shop_id;

        $link_url = "?" . $this->build_link_url();

        if ( $page )
           $result = $this->query();
           $total_rows = $this->query_rows();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ///////////////////////////////////////////
    // product data query 
    function query()
    {
        global $connect, $search_type, $keyword, $option_keyword, $page, $supply_name_keyword;
        
        $limit = 30;
        $start = ($page-1) * $limit;

        $query = "select * from products where ";
        $option .= " name like '%$keyword%'";

        if ( $option_keyword )
           $option .= " and options like '%" . str_replace(" ", "%",$option_keyword) ."%' ";

		if ( $supply_name_keyword )
           $option .= " and brand like '%" . str_replace(" ", "%",$supply_name_keyword) ."%' ";

        if( $_SESSION[CS_EXCEPT_SOLDOUT] )
            $option .= " and enable_sale=1 ";
        $option .= " and is_delete = 0 and is_represent<>1 order by supply_code, name, options desc limit $start, $limit";

        // max
        $result = mysql_query ( $query . $option, $connect );
        debug($query . $option);
        return $result; 
    }
    function query_rows()
    {
        global $connect, $search_type, $keyword, $option_keyword;
        $query = "select count(*) as cnt from products where ";
        $option .= " name like '%$keyword%'";
        if ( $option_keyword )
           $option .= " and options like '%" . str_replace(" ", "%",$option_keyword) ."%' ";

        $option .= " and is_delete = 0 and is_represent<>1";
 
        // max
        $total_rows = mysql_query ( $query . $option, $connect );
        return $total_rows; 
    }
        

    /////////////////////////////////////////
    // 주문 생성 (modified by sy.hwang 2005.12.5)
    function create( )
    {
	    global $connect, $shop_id; // shop_id 추가 2009.2.26 - jk

	    ///////////////////////////////////
	    // seq를 가져온다
	    $query = "select max(seq) as mseq from orders";
	    $data = mysql_fetch_array(mysql_query($query, $connect));
	    $max = $data[mseq] + 1;
	    $this->begin("신규 주문 등록[$max]", $max);

        // 주문 생성 - 2009.7.27 - jk
        $items = array("address1","address2","amount","memo","options","order_mobile","order_name","order_tel"
                    ,"product_id","product_name","qty"
                    ,"recv_address1","recv_address2","recv_mobile","recv_name","recv_tel","recv_zip1","recv_zip2"
                    ,"shop_id","shop_price","supply_id","trans_price","trans_who","zip1","zip2" );

        $query = "insert orders set ";
        $_query = "";
        foreach ( $items as $item )
        {
            global $$item;
            
            //if ( !$$item == "" ) continue;
            
            if (   $item == "zip1" 
                or $item == "zip2"
                or $item == "address1"
                or $item == "address2"
                or $item == "recv_zip1" 
                or $item == "recv_zip2"
                or $item == "recv_address1"
                or $item == "recv_address2"
            )
                continue;
            
            $_query .= $_query ? "," : "";
            $_query .= $item;
            $_query .= " = \"" . htmlspecialchars( $$item ) . "\"";
        }
        
        // 주소 정보 입력
        $_query .= ",order_zip     = '$zip1-$zip2'";
        $_query .= ",order_address = '$recv_address1 $recv_address2'";
        $_query .= ",recv_zip      = '$recv_zip1-$recv_zip2'";
        $_query .= ",recv_address  = '$recv_address1 $recv_address2'";
        $_query .= ",collect_date  = Now()";
        $_query .= ",order_id      = $max";
        $_query .= ",seq           = $max";
        
        // orders에 입력
        $query = $query . $_query;
        mysql_query( $query, $connect );
        
        //=============================================
        // order_products query
        $query = "insert into order_products 
                    set order_seq   = $max
                        ,product_id = '$product_id'
                        ,qty        = $qty
                        ,shop_id    = $shop_id
                        ,shop_product_id = '$product_id'
                        ,status     = 1
                        ,supply_id  = '$supply_id'
                        ,shop_price = '$shop_price'
                        ";
        mysql_query( $query, $connect );
                
        global $isMulti;
        if ( $isMulti )
	       $this->redirect("?template=E500&seq=$max&isMulti=true&top_url=" . base64_encode("?template=E100"));
        else
	       $this->redirect("?template=E101&seq=$max&top_url=" . base64_encode("?template=E100"));
	    exit;
    }


    ///////////////////////////////////////////////////
    //
    // 선/착불 정보를 price_history에서 가져와서 보여줌
    // date: 2005.12.26
    // 
    ///////////////////////////////////////////////////

    function trans_who( $shop_id, $product_id, &$trans_who, &$org_price, &$supply_price, &$shop_price )
    {
        global $connect;
        $today = date('Y-m-d');

        $query = "select * from price_history 
                   where shop_id='$shop_id' 
                     and product_id='$product_id'
                     and start_date <= '$today'
                     and end_date >= '$today'";

        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        if ( !$data )
        {
           $query = "select * from price_history 
                      where ( shop_id = '' or shop_id is null ) 
                        and product_id='$product_id'
                        and start_date <= '$today'
                        and end_date >= '$today'";

           $result = mysql_query ( $query, $connect );
           $data = mysql_fetch_array ( $result );
           
           if ( !$data )
           {
              $query = "select * from price_history 
                         where product_id='$product_id'";

              $result = mysql_query ( $query, $connect );
              $data = mysql_fetch_array ( $result );
           }
        }

        $trans_who = $data[is_free_deliv] ? "선불" : "착불";
        $org_price = $data[org_price];
        $supply_price = $data[supply_price];
        $shop_price = $data[shop_price];
    }

    // 검색상품 재고 가져오기
    function get_stock_info()
    {
        global $connect, $id_list;
        
        $val = array();
        foreach( explode(",", $id_list) as $id )
        {
            $val["products"][] = array(
                "product_id"   => $id,
                "stock_format" => class_E900::get_stock_format($id)
            );
        }
        echo json_encode( $val );
    }
}

?>
