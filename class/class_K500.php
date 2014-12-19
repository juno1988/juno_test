<?
//====================================
//
// ��� ��û
// name: class_K500
// date: 2007.11.9 - jk
//
require_once "class_top.php";
require_once "class_order.php";
require_once "class_3pl.php";
require_once "class_product.php";
require_once "class_pre_order.php";

class class_K500 extends class_top {
    var $m_obj_3pl = "";

    // init class
    function class_K500()
    {
    	$this->m_obj_3pl = new class_3pl();
    }

    function K500()
    {
	global $template;
	$top_url = base64_encode ( $this->build_link_url() );

	if ( !$start_date )
            $start_date = date("Y-m-d", mktime (0,0,0,date("m")  , date("d")-5, date("Y")));

	$obj_pre = new class_pre_order();
	$result = $obj_pre->get_list();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function K502()
    {
	global $template, $seq, $page, $top_url;

	$obj     = new class_order();
	$obj_pre = new class_pre_order();

	$link_url = base64_encode ( $this->build_link_url() );

	// query���� K500���� ��� �����
	$arr_options = $obj_pre->get_options( $seq );
	$data        = $obj_pre->get_infos( $seq );

	//$result     = $obj->get_list( $arr_options );
	$req_cnt    = $obj->get_count( $arr_options );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    function K501()
    {
	global $template, $seq, $page,$top_url;
	global $shop_id, $product_name, $options, $status, $order_cs, $page, $txt_title, $seq;

	$obj     = new class_order();
	$obj_pre = new class_pre_order();

	$link_url = base64_encode( $this->build_link_url() );

	// query���� K500���� ��� �����
	$arr_options = $this->build_options();

	if ( $seq )
	   $arr_pre_order = $obj_pre->get_infos( $seq );

	if ( $page )
	{
	    $result     = $obj->get_list( $arr_options );
	    $req_cnt    = $obj->get_count( $arr_options );
	}

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    ////////////////////////////////////////////
    // �켱 ���� update
    //	
    function update_priority ()
    {
	global $seq, $priority, $warehouse, $top_url, $link_url;

	$obj_pre = new class_pre_order();
	$obj_pre->update_priority( $priority, $seq, $warehouse );
	$this->redirect ( "?" . base64_decode( $link_url ) . "&top_url=$top_url" );
    }

    function build_options()
    {
	global $shop_id, $product_name, $options, $status, $order_cs, $page, $txt_title, $seq;
	// status�� �̹��
	$status   =  "1,2,3,4,5,6";
	$order_cs =  "1,2,3,4,5";
	$arr_options = array ( 
			shop_id      => $shop_id,
			product_name => $product_name,
			options      => $options,
			status       => $status,
			order_cs     => $order_cs );

	return $arr_options;
    }

    ///////////////////////////////////////
    // ���� ����
    function save_options()
    {
	echo "save options";
	global $template;
	global $shop_id, $product_name, $options, $status, $order_cs, $page, $txt_title, $seq, $top_url;
	$obj     = new class_order();
	$obj_pre = new class_pre_order();

	$top_url = base64_encode( $this->build_link_url() );
	// reg_title
	$seq = $obj_pre->reg_title( $txt_title );

	// reg_option
	$arr_options = $this->build_options();
	$obj_pre->reg_options( $arr_options, $seq );

	$this->redirect( "?template=K502&seq=$seq&top_url=$top_url");
    }

    ////////////////////////////
    // title ����
    function reg_title()
    {
	global $template;
	global $shop_id, $product_name, $options, $status, $order_cs, $page, $top_url, $txt_title;
	echo "reg_title: $txt_title<br>";

	class_pre_order::reg_title( $txt_title );	
echo "link_url: $top_url ";
	$this->redirect( "?". base64_decode( $top_url ) );
    }
    //======================================
    //  ��ȸ
    function query()
    {
	global $template, $start_date, $end_date;

	$_interval = intval((strtotime( $end_date )-strtotime( $start_date ))/86400);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


    function request_detail()
    {
	global $_date;
	$result = $this->m_obj_3pl->request_detail( $_date );

	echo "<table border=1 width=500>
		<tr>
		    <td>�ð�</td>
		    <td>����</td>
		    <td>msg</td>
		    <td>�����</td>
		    <td>����</td>
		</tr>";

	if ( $result )
	while ( $data = mysql_fetch_array ( $result ) )
	{
		echo "
		<tr>
		    <td>$data[reg_date]</td>
		    <td>$data[req_cnt]</td>
		    <td>$data[msg]</td>
		    <td>$data[req_user]</td>
		    <td>$data[status]</td>
		</tr>
		";
	}
	echo "</table>";
	
    }

    //=========================================
    // �ֹ��� ���� �ľ�.
    // date: 2007.11.13 - jk
    function get_count ( $_switch, $_date )
    {
	if ( $this->m_obj_3pl )
		$obj_3pl = $this->m_obj_3pl;
	else
		$obj_3pl = new class_3pl();

	switch ( $_switch )
	{
	    case "tot_orders":
		return $this->cnt_tot_orders( $_date );
		break;
	    case "trans_request":
		return $obj_3pl->cnt_req_orders( $_date );
		break;
	}

    }

    //=============================
    // ��ü �ֹ� ����
    function cnt_tot_orders( $_date )
    {
	global $connect;
	$query = "select count(*) cnt 
                   from orders a, products b
                   where a.product_id = b.product_id
                     and b.use_3pl = 1
                     and a.collect_date = '$_date'
                     and a.status = 1
                     and a.order_cs not in (1,2,3,4,12)"; 

	$result = mysql_query ( $query , $connect );
	$data = mysql_fetch_array ( $result );
	return $data[cnt];
    }

    function del_seq()
    {
	global $seq;
	$obj_pre = new class_pre_order();
	$result = $obj_pre->del_seq( $seq );

	$this->jsAlert("���� �Ϸ�");
	$this->redirect( "?template=K500" );
    }

    /////////////////////////////////////
    // ��ü data sync
    function tot_sync()
    {
	global $connect;

	$obj_pre = new class_pre_order();
	$result = $obj_pre->get_list();

	$this->show_wait();

	// repack
	/* �̰� ����? �ֺ��� ��û����..�����س�����.
	$arr_options[priority] = 99;
	$obj_pre->reflect_priority( $arr_options, 99 );
	*/
	// ���� logic�� ������
	while ( $data = mysql_fetch_array( $result ) )
	{
	    // �� �˻� ������ ������
	    $arr_options = $obj_pre->get_options( $data[seq] );

	    if ( $arr_options )
	    {
	        $obj_pre->reflect_priority( $arr_options, $data[priority], $data[warehouse] );
	    }
	}
	$this->hide_wait();
	$this->jsAlert("�۾� �Ϸ�");
	$this->redirect("?template=K500");
    }

    function init()
    {
	$this->show_wait();
	$obj_pre = new class_pre_order();
	$obj_pre->init_priority();

	$this->hide_wait();
    }

    ////////////////////////////////////
    // ���� sync
    function one_sync()
    {
	global $seq;

	$obj_pre = new class_pre_order();
	$data    = $obj_pre->get_infos( $seq );
	
	$this->show_wait();

 	$arr_options = $obj_pre->get_options( $data[seq] );
	if ( $arr_options )
	{
	    $obj_pre->reflect_priority( $arr_options, $data[priority], $data[warehouse] );
	}
	$this->hide_wait();
	$this->jsAlert("�Ϸ�");
	$this->redirect( "?template=K500" );
    }

    //=============================
    //
    // ��� ��û
    //
    function trans_request()
    {
	global $_date, $connect;

	if ( $this->m_obj_3pl )
		$obj_3pl = $this->m_obj_3pl;
	else
		$obj_3pl = new class_3pl();

	$query = "select a.* from orders a, products b 
		   where a.product_id = b.product_id
                     and b.use_3pl = 1
                     and a.collect_date = '$_date'
                     and status = 1
                     and order_cs not in (1,2,3,4,12)";
	$result = mysql_query ( $query, $connect );

	$cnt = 0;	
	$_date = "";
	while ( $data = mysql_fetch_array ( $result ) )
	{
		// name�� option�� �����;� ��
		class_product::get_product_name_option($data[product_id], &$name, &$option);

		$data[product_name] = $name;
		$data[options] = $option;

		if ( $obj_3pl->order_reg( $data ) )
		{
		    //echo "     cnt: $cnt<br>";
		    $_date = $data[collect_date];
		    $cnt++;
		}
  	}

	echo "$cnt �� �Ϸ�";
	
	// 3pl_tx�� data ���
	// type: Delivery
	$obj_3pl->regist_tx( "D", $cnt, "��ۿ�û", $_SESSION[LOGIN_ID] , $_date );
    }

}

?>
