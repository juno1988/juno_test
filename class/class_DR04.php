
<?
require_once "class_top.php";
require_once "class_file.php";
require_once "Classes/PHPExcel.php";

class class_DR04 extends class_top
{
    //////////////////////////////////////
    // 로케이션 관리 
    function DR04()
    {
        global $connect, $template;
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

	function load()
	{
		global $connect, $date, $number;
		
		$query = "select seq_list from print_number where crdate='$date' and no='$number'";
		$result = mysql_query( $query, $connect );
		
		 $time = date("Y-m-d H:i:s",time());
        
        while ( $data = mysql_fetch_array ( $result ) )
        {
            $print_packs .= "<br><table border=1 style=table-layout:fixed;word-break:break-all;>";
            //$print_packs .= "<tr><td>출력일자</td><td>".$data['trans_date']."</td></tr>";
            
            $print_packs .= "<tr><td width='70'>관리번호</td><td width='400'>".$data['xx']."</td></tr>";
            $print_packs .= "<tr><td width='70'>출력일자</td><td width='400'>".$time."</td></tr>";
            $print_packs .= "<tr><td width='70'>발주일자</td><td width='400'>".$data['collect_date']."</td></tr>";  
            $print_packs .= "<tr><td width='70'>고객명</td><td width='400'>".$data['recv_name']."</td></tr>";  
            $print_packs .= "<tr><td width='70'>주소</td><td width='400'>".$data['recv_address']."</td></tr>";    
            
            $shop_name = class_shop::get_shop_name( $data['shop_id'] );     //  판매처 이름
            
            $print_packs .= "<tr><td width='70'>판매처</td><td width='400'>".$shop_name."</td></tr></table>";  

            //  하단 테이블            
            $print_packs .= "<table border=1 style=table-layout:fixed;word-break:break-all;>
                            <tr><td width='150' heignt='150'>이미지</td>
                            <td width='170' align=center>상품명</td>
                            <td width='200' align=center>옵션</td>
                            <td width='50' align=center>개수</td>
                            <td width='70' align=center>금액</td>
                            <td width='130' align=center>상태</td></tr>";
            
            if( $data['xx'] == $data['pack'] )  //  합포일때
            {
               $where = "a.pack='$data[xx]'";
            
            }
            else if( $data['xx'] == $data['seq'] )  //  합포가 아닐때
            {
                $where = "a.seq='$data[xx]'";
            }
            //$query_product = "select b.qty, b.product_id, c.options, c.shop_price from orders a, order_products b, products c 
            //                  where $where and a.seq=b.order_seq and b.product_id=c.product_id";
                                
            $query_product = "select b.qty, b.product_id from orders a, order_products b 
                                where $where and a.seq=b.order_seq";                    
                                
            $result_order_product = mysql_query( $query_product, $connect );
            
            while( $order_product = mysql_fetch_array( $result_order_product ) )
            {
                $name_product = $this->get_product_name($order_product['product_id']);   //  상품명
                $img_product = $this->get_product_img($order_product['product_id']);     //  상품 이미지
                $order_val = $this->get_product_cs($order_product['product_id']);        //  상품 상태(숫자)
                $cs_product = $this->get_order_cs2($order_val);                    //  상품 상태(문자) 
                
                $options = $this->get_product_options($order_product['product_id']);   //  옵션
                $shop_price = $this->get_product_price($order_product['product_id']);      //  가격
   
                $print_packs .= "<tr><td><img src=".$img_product." width=150 height=150></td>
                                <td align=center>".$name_product."</td><td align=center>".$options."</td>
                                <td align=center>".$order_product['qty']."</td>
                                <td align=center>".$shop_price."원</td>
                                <td align=center>".$cs_product."</td></tr>";
            }
            
            $print_packs .= "</table><br><br>";
	}
}
