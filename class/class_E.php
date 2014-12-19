<?
////////////////////////////////
// class name: class_E
//

class class_E {

    ////////////////////////////
   function get_trans_name ( $trans_code )
    {
       global $connect;
       $query = "select * from trans_info where id='$trans_code'";
       $result = mysql_query ($query, $connect);
       $data = mysql_fetch_array ( $result );
       return $data[trans_corp];
    }

    function enable_sale ( $product_id )
    {
       global $connect;
       $query = "select enable_sale from products where product_id='$product_id'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );

       if ( !$data[enable_sale] )
          echo "<span class=red>[품절]</span>"; 
    }

    function get_part_cancel_count ( $seq )
    {
        global $connect;
        $query = "select sum(qty) tot from part_cancel where order_seq='$seq'";

        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        return $data[tot] ? $data[tot] : 0;
    }

    ////////////////////////////////////////////////////
    // status 값이 따라 count가 다름
    // 1: 미처리 cs개수
    // 2: 총 CS개수
    function get_cs_count( $status )
    {
        global $connect;

        $query = "select count(*) cnt from ";

    }

   function get_org_id( $product_id )
   {
       global $connect;
       $query = "select org_id from products where product_id='$product_id'";
       $result = mysql_query ( $query, $connect );
       $data = mysql_fetch_array ( $result );

       if ( $data[org_id] )
           return $data[org_id];
       else
           return $product_id;	// 자신이 orgid인 경우
   }

   //========================================
   // 2006.
   function link_shop_cs( $order_info, $shop_id )
   {
	$shop_xp = (int)($shop_id%100);
       switch ( $shop_xp )
       {
           case "3":	// 다음
               $link = "https://dnshopadmin.shop.daum.net/bo/selling/BoOrdDetail?ordid=$order_info&viewpop=P";
               break;
           case "4":	// 프라이스 앤지오
               $link = "http://admin.pricengo.com/admin_btob/mi/popup_main_kyul_all.jsp?in_ORDERID1=$order_info";
               break;
           case "45": 	// 가비아
               $link = "http://partner.gabiamall.co.kr/Bae-Song/index.html?search_field=ord_no&search_str=$order_info&start=0&mode=search";
               break;
           case "10":	// 제로마켓
               $link = "http://www.zeromarket.com/servlet/OrderProcess?func=rOrderContent&orderId=$order_info&choose=1";
               break;
	   case "2":
		$link = "http://gsm.gmarket.co.kr/gsm_new/E_CONTR_PROCESS_MNG/cs_delive_ok.asp";
		break;
	   case "65":
		$link = "http://seller.market.cyworld.com/main.do?method=displayMainShop";
		break;
       }

       if ( $link )
           echo "<a href='$link' target=new> <span class=red><u>판매처 cs 연결</u></span></a>";
   }

}

?>
