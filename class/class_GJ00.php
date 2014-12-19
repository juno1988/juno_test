<?
require_once "class_top.php";
require_once "class_product.php";
require_once "class_shop.php";
require_once "class_file.php";
require_once "class_C.php";

////////////////////////////////
// class name: class_GJ00
//
// product model ;
class cProduct{
    var $m_product_id;    // ��ǰ id
    var $m_name;	  // ��ǰ��
    var $m_shopname;	  // �Ǹ�ó
    var $m_sum;	  	  // �� ��
    var $m_total;	  // ��ü ����
    var $m_list = array();

    function cProduct( $product_id, $date_type, $start_date, $end_date , $shop_id, $_ids )
    {
	global $connect;
	global $product_name, $shop_product_id, $options;

	$this->m_name       = class_product::get_product_name( $product_id );
	$this->m_product_id = $product_id;
	$this->m_shopname = class_shop::get_shop_name( $shop_id );

	$query = "select sum(qty) s, sum(amount) amt, shop_id, shop_product_id,options 
		    from orders 
		   where product_id='$product_id'
                     and $date_type >= '$start_date'
                     and $date_type <= '$end_date'";

	if ( $shop_id )
	    $query .= " and shop_id=$shop_id ";

	if ( $_ids)
            $query .= " and product_id in ($_ids)";

	if ( $shop_product_id)
            $query .= " and shop_product_id = '$shop_product_id'";

        if ( $options )
            $query .= " and options like '%$options%'";

	$query .= " group by shop_product_id,options order by s desc";

	$result = mysql_query ( $query, $connect );
	while ( $data = mysql_fetch_array ( $result ) )
	{
	    class_product::get_product_name_option( $data[shop_id], &$name, &$_options );
	    $this->m_list[] = array( 
				shop_product_id => $data[shop_product_id],
				options         => $data[options],
				amount          => $data[amt],
				shop_name       => $_shop_name ? $_shop_name : $data[shop_id],
				qty             => $data[s] );
	}
    }
}
//////////////////////////////////
//
class class_GJ00 extends class_top {

    ///////////////////////////////////////////
    function GJ00()
    {
	global $connect;
	global $template, $line_per_page;

        $transaction = $this->begin("�Ǹ�ó�� �������");

	if ( _DOMAIN_ == "qmart" )
	{
            include "template/G/GJ00_qmart.htm";
	    exit;
	}else{
            $master_code = substr( $template, 0,1);
            include "template/" . $master_code ."/" . $template . ".htm";
	}
    }

    // 
    function qmart_query()
    {
	global $connect, $start_date, $end_date;

	$query = "select recv_mobile,collect_date,seq from orders 
		   where collect_date >= '$start_date'
		     and collect_date <= '$end_date'
		     and recv_mobile <> ''
		   group by collect_date, recv_mobile";

	$result = mysql_query( $query, $connect ); 
	$arr_data = array();
	while( $data = mysql_fetch_assoc( $result ) )
	{
	    $arr_data[$data[recv_mobile]][] = array( 
			collect_date => $data[collect_date]
			,seq         => $data[seq]
	    );
	}

	// disp
	$arr_result = array();	
	$str_seq    = "";
	$i = 0;
	foreach( $arr_data as $key=>$value )
	{
	    // echo "$key: " . sizeof($value) . " : ";
	    if ( sizeof( $value ) > 1 )
	    {
		if ( $i > 0 ) $str_seq .= ",";
		$i++;
	
		$j=0;	
	        foreach( $value as $v=>$seq )
	        {
		    if ( $j > 0 ) $str_seq .= ",";
		    $j++;
		    $str_seq .= $seq[seq];
	        }
	    }
	}

	// ��ȭ��ȣ,�����ڸ�,�����θ�,�ּ�,����Ʈ
	$query = "select order_name, order_mobile, recv_name, recv_mobile, recv_address, shop_id 
                   from orders where seq in ( $str_seq )";
	$result = mysql_query( $query, $connect );

        include "template/G/GJ00_qmart.htm";
    }

    function query()
    {
	global $date_type, $shop_id, $connect,$start_date, $end_date, $query_string, $connect, $query_type;
	// 2008.10.8 - �߰�
	global $product_name, $shop_product_id, $options;


	$this->show_wait();

	// ��ǰ�� �Ǹ�ó �� ��ȸ
	// ��ǰ ��ȸ ����
	$query = "select product_id , shop_id from orders 
                   where $date_type >='$start_date' and $date_type <= '$end_date'";

	if ( $shop_id )
	    $query .= " and shop_id='$shop_id'";

	if ( $product_name )
	{
	    // product_id�� ã�ƾ� ��
	    $_ids   = class_product::get_product_ids( $product_name );
            $query .= " and product_id in ( $_ids )";
	}

	if ( $shop_product_id)
            $query .= " and shop_product_id = '$shop_product_id'";

        if ( $options )
            $query .= " and options like '%$options%'";

	$query .= " group by shop_id, product_id";

	$result = mysql_query ( $query, $connect );
	$arr_obj = array();
	$i = 0;
	while ( $data = mysql_fetch_array( $result ) )
	{
	    // ��ǰ�� ���� �˻�
	    $obj = new cProduct( $data[product_id], $date_type, $start_date, $end_date, $data[shop_id] , $_ids);
	    // echo $obj->m_name . "<br>";
	    $arr_obj[] = $obj;
	    $i++;
	    $this->show_txt( "������ " . $i);
	}

	$this->hide_wait();
        include "template/G/GJ00.htm";
    }

    // 
    function download2() // begin of download
    {
	global $date_type, $shop_id, $connect,$start_date, $end_date, $query_string, $connect, $query_type;
	// 2008.10.8 - �߰�
	global $product_name, $shop_product_id, $options;

	// ��ǰ�� �Ǹ�ó �� ��ȸ
	// ��ǰ ��ȸ ����
	$query = "select product_id , shop_id from orders 
                   where $date_type >='$start_date' and $date_type <= '$end_date'";

	if ( $shop_id )
	    $query .= " and shop_id='$shop_id'";

	if ( $product_name )
	{
	    // product_id�� ã�ƾ� ��
	    $_ids   = class_product::get_product_ids( $product_name );
            $query .= " and product_id in ( $_ids )";
	}

	if ( $shop_product_id)
            $query .= " and shop_product_id = '$shop_product_id'";

        if ( $options )
            $query .= " and options like '%$options%'";

	$query .= " group by shop_id, product_id";

	$result = mysql_query ( $query, $connect );
	$arr_obj = array();
	$i = 0;
	while ( $data = mysql_fetch_array( $result ) )
	{
	    // ��ǰ�� ���� �˻�
	    $obj = new cProduct( $data[product_id], $date_type, $start_date, $end_date, $data[shop_id] , $_ids);
	    $arr_obj[] = $obj;
	    $i++;
	} // end of while

	$val= array();
	$val[] = array("�Ǹ�ó", "��ǰ�ڵ�","��ǰ��","�Ǹ�ó ��ǰ�ڵ�", "�ɼ�", "�ǸŰ���", "�Ǹűݾ�");
	
	foreach ( $arr_obj as $obj )	// begin of foreach
        {
            $row_cnt = count($obj->m_list);
            $i = 0;
            foreach ( $obj->m_list as $p )
            {
		$val[] = array( $obj->m_shopname, $obj->m_product_id,  $obj->m_name, $p[shop_product_id],$p[options], $p[qty], $p[amount] );
            }  // end of foreach

        } // end of foreach     
	
	class_file::download( $val, "���.xls" );

    } // end of download

}
?>
