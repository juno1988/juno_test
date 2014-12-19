<?
/*-------------------------------------------


	class_KC00.php
	desc : 3PL �԰��û

--------------------------------------------*/
require_once "class_top.php";
require_once "class_3pl.php";
require_once "class_product.php";
require_once "class_supply.php";
require_once "class_ui.php";

class class_KC00 extends class_top {
    var $m_3pl = "";
    var $m_connect = "";

    /////////////////////
    function class_KC00()
    {
	$this->m_3pl     = new class_3pl();
	$this->m_connect = $this->m_3pl->m_connect;
    }

    function KC00()
    {
	global $template, $start_date, $end_date;

	if ( !$start_date )
	    $start_date = date('Y-m-d', strtotime('today'));

	if ( !$end_date )
	    $end_date = date('Y-m-d', strtotime('today'));

	// ��ȸ
	$this->query();
    }

    function get_name_cnt_type( $status )
    {   
        $_code = array ( "direct" => "<span class=red>����԰�</span>",
                         "check"  => "��Ȯ��");
        return $_code[$status];
    }        

    ////////////////////////////////////
    // �԰� Ÿ�� ����
    //
    function modify_cnt_type()
    {
	global $sheet, $cnt_type;
	$query = "update 3pl_sheet_in set cnt_type='$cnt_type' 
                   where seq=$sheet";	
	mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
	$cnt = mysql_affected_rows();
	echo "���� �Ϸ�";
    }

    //////////////////////////////////////////
    // �԰� ���� Ȯ��
    // 2008-3-25 jk
    //
    function confirm_qty()
    {
	global $seq, $sheet;
	$query  = "select status from 3pl_stock_wait where seq=$seq";
	$result = mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
	$data   = mysql_fetch_array ( $result );

	// status
	$val = array();
	if ( $data[status] == 2 )
	{
	// ����
	    $query = "update 3pl_stock_wait set status=4,confirm_date=Now() where seq=$seq";	
	    mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
	    $val[status] = iconv( 'cp949', 'utf-8', $this->get_name_sheet_sts(4));
	    $val[error] = 0;
	}
	else
	{
	    $val[status] = iconv( 'cp949', 'utf-8', $this->get_name_sheet_sts( $data[status] ));
	// ���� ó��
	    switch ( $data[status] )
	    {
		case 4:
	            $val[error_msg] = iconv('cp949','utf-8',"�̹� Ȯ�� �Ǿ����ϴ�.");
		    break;
		default:
	            $val[error_msg] = iconv('cp949', 'utf-8', "����!" );
		
	    }
	    $val[error] = 1;
	}

	//////////////////////////////
	// 3pl_sheet_in �� ���� ó��
	// 3pl_stock_wait�� min(status)���� 3pl_sheet_in�� ������
	$_status = $this->arrange_sheet_status( $sheet );
	// $_status = $this->get_name_sheet_sts( $_status );
	$val[sheet_status] = iconv( 'cp949', 'utf-8', $this->get_name_sheet_sts( $_status )) ;

	//$val[sheet_status] = "test";
	echo json_encode( $val );
    }


    //////////////////////////////////////////
    // ���� ����
    // 2008-5-16 jkh
    //
    function disagree_qty()
    {
	global $seq, $sheet;
	$query  = "select status from 3pl_stock_wait where seq=$seq";
	$result = mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
	$data   = mysql_fetch_array ( $result );

	// status
	$val = array();
	if ( $data[status] == 2 )
	{
	// ����
	    $query = "update 3pl_stock_wait set status=3 where seq=$seq";	
	    mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
	    $val[status] = iconv( 'cp949', 'utf-8', $this->get_name_sheet_sts(3));
	    $val[error] = 0;
	}
	else
	{
	    $val[status] = iconv( 'cp949', 'utf-8', $this->get_name_sheet_sts( $data[status] ));
	// ���� ó��
	    switch ( $data[status] )
	    {
		case 4:
	            $val[error_msg] = iconv('cp949','utf-8',"�̹� Ȯ�� �Ǿ����ϴ�.");
		    break;
		default:
	            $val[error_msg] = iconv('cp949', 'utf-8', "����!" );
		
	    }
	    $val[error] = 1;
	}

	//////////////////////////////
	// 3pl_sheet_in �� ���� ó��
	// 3pl_stock_wait�� min(status)���� 3pl_sheet_in�� ������
	$_status = $this->arrange_sheet_status( $sheet );
	// $_status = $this->get_name_sheet_sts( $_status );
	$val[sheet_status] = iconv( 'cp949', 'utf-8', $this->get_name_sheet_sts( $_status )) ;

	//$val[sheet_status] = "test";
	echo json_encode( $val );
    }

    //////////////////////////////////////
    //  sheet�� status ����
    // 2008.3.25 - jk
    function arrange_sheet_status( $sheet )
    {
	global $connect;

	$query = "select min(status) status from 3pl_stock_wait where sheet=$sheet";
	$result = mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );
	$data   = mysql_fetch_array ( $result );

	$_status = $data[status];

	////////////////////////////
	// sheet in�� status����
	$query = "update 3pl_sheet_in set status=$_status where seq=$sheet";
	$result = mysql_query ( $query, $this->m_connect ) or die ( mysql_error() );

	return $_status;
    }

    ///////////////////////////////////////////////////
    // query
    function query_stockout()
    {
	global $template,$status,$txt_name, $start_date, $end_date, $supply_id, $isgroup, $isoption;

	// jk
	$link_url = $this->build_link_url();
	$link_url = base64_encode( $link_url );

	$obj_product = new class_product();
	$obj_supply  = new class_supply();

	if ( !$start_date )
	    $start_date = date('Y-m-d', strtotime('-5 days'));

	if ( !$end_date )
	    $end_date = date('Y-m-d', strtotime('today'));

	///////////////////////////////////////
	// status�� ���� ��쿡�� status check
	if ( is_numeric($status) )
	    if ( $status != -99 )
	        $options .= " and status='$status'";

	///////////////////////////////////////
	$sql = "select * ";

	if ( $isgroup || $isoption)
	    $sql .= " , sum(a.qty) qty ";

	$sql .= " from 3pl_stock_out a, 3pl_products b
		 where a.product_id = b.product_id
		   and a.domain     = '$_SESSION[LOGIN_DOMAIN]'
		   and b.domain     = '$_SESSION[LOGIN_DOMAIN]'
                   and a.start_date >= '$start_date'
                   and a.start_date <= '$end_date'
		   and a.qty        <> 0";

	if ( $supply_id <> '����ó')
	    $sql .= " and b.supply_id = '$supply_id'";

	if ( $isgroup )
	    $sql .= " group by b.org_id";

	if ( $isoption)
	    $sql .= " group by b.product_id";

	$sql .= " order by a.product_id";

	$result = mysql_query($sql, $this->m_connect) or die(mysql_error());
     
        $master_code = substr( $template, 0,1);
        include "template/K/KC01.htm";
    }


    ///////////////////////////////////////////////////
    // query
    function query()
    {
	global $template,$status,$txt_name, $start_date, $end_date, $supply_id, $isgroup, $isoption;

	// jk
	$link_url = $this->build_link_url();
	$link_url = base64_encode( $link_url );

	$obj_product = new class_product();
	$obj_supply  = new class_supply();

	if ( !$start_date )
	    $start_date = date('Y-m-d', strtotime('-5 days'));

	if ( !$end_date )
	    $end_date = date('Y-m-d', strtotime('today'));

	///////////////////////////////////////
	// status�� ���� ��쿡�� status check
	if ( is_numeric($status) )
	    if ( $status != -99 )
	        $options .= " and status='$status'";

	///////////////////////////////////////
	$sql = "select * ";

	if ( $isgroup || $isoption)
	    $sql .= " , sum(a.qty) qty ";

	$sql .= " from 3pl_stock_in a, 3pl_products b
		 where a.product_id = b.product_id
		   and a.domain     = '$_SESSION[LOGIN_DOMAIN]'
		   and b.domain     = '$_SESSION[LOGIN_DOMAIN]'
                   and a.start_date >= '$start_date'
                   and a.start_date <= '$end_date'
		   and a.memo       in('�����̼� �԰�Ȯ��', '��ǰ �԰�Ȯ��')
		   and a.qty        <> 0";
	if ( $supply_id <> '����ó')
	    $sql .= " and b.supply_id = '$supply_id'";

	if ( $isgroup )
	    $sql .= " group by b.org_id";

	if ( $isoption)
	    $sql .= " group by b.product_id";

	$sql .= " order by memo,a.product_id ";

//echo "$sql";

	$result = mysql_query($sql, $this->m_connect) or die(mysql_error());
     
        $master_code = substr( $template, 0,1);
        include "template/K/KC00.htm";
    }

    ////////////////////////////////////////////////
    // ���º� ���� �ľ�
    function get_count( $status )
    {
	$sql = "select count(*) cnt 
                  from 3pl_sheet_in
		 where domain = '$_SESSION[LOGIN_DOMAIN]'
                   and status = $status 
		 order by seq desc";

	$result = mysql_query($sql, $this->m_connect) or die(mysql_error());
	$data   = mysql_fetch_array( $result );
	return $data[cnt];
    }

    function KC01()
    {
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function KC02()
    {
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function KC03()
    {
	global $template, $sheet;
	
  	$sql         = "select a.*,date_format(b.arrive_date, '%Y-%m-%d %h:%m:%s') arrive_date,
                               date_format(b.count_date, '%Y-%m-%d %h:%m:%s') count_date,
                               date_format(b.confirm_date, '%Y-%m-%d %h:%m:%s') confirm_date,
                               date_format(b.complete_date, '%Y-%m-%d %h:%m:%s') complete_date
                          from 3pl_sheet_in a, 3pl_stock_wait b
                         where a.seq = b.sheet
                           and a.seq = '$sheet'";
	$result      = mysql_query($sql, $this->m_connect);
	$rows        = mysql_num_rows( $result );
  	$list        = mysql_fetch_array( $result );

	if ( !$rows )
        {
            $sql         = "select a.*
                              from 3pl_sheet_in a
                             where a.seq = '$sheet'";

  	    $list        = mysql_fetch_array(mysql_query($sql, $this->m_connect));
        }

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function KC04()
    {
	global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    // window close
    function win_close()
    {
	$this->closewin();
/*
?>
        <script language=javascript>
            function cl(){
                self.close();
            }
        </script>
        <a href='javascript:cl()'>cl</a>
<?	
*/
    }

    function KC05()
    {
	global $template, $connect,$keyword,$options;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function select_sheet($sheet)
    {
	$sql = "select * from 3pl_sheet_in 
		 where domain = '$_SESSION[LOGIN_DOMAIN]'
		 order by seq desc";
	$result = mysql_query($sql, $this->m_connect) or die(mysql_error());
        echo "<select name=sheet>";
        echo "<option value=''>��ǥ�� �����ϼ���</option>";
        while ($list = mysql_fetch_array($result))
        {
            $selected = ($list[seq] == $sheet) ? "selected" : "";

            echo "<option value=${list[seq]} ${selected}>${list[title]}</option>";
        }
        echo "</select>";
    }

    ////////////////////////////////
    // �԰� ��ǥ ����
    // 2008.3.24 - jk
    function del_sheet()
    {
	global $sheet, $sheet, $link_url;
	$domain = $_SESSION[LOGIN_DOMAIN];

	$query = "delete from 3pl_sheet_in where domain='$domain' and seq=$sheet";
        mysql_query($query, $this->m_connect) or die(mysql_error());

	$this->redirect("?". base64_decode( $link_url ));
	exit;
    }

    ////////////////////////////////
    // �԰� ��ǥ ����
    function create_sheet()
    {
        global $template;

        foreach ($_REQUEST as $key=>$value)
            $$key = $value;

        $title     = addslashes($title);
        $warehouse = addslashes($warehouse);

	//////////////////////////////////////////////////////////////////////
	// stock_type: asking => �԰� ��û
	// stock_type: self   => ��ü �԰� ( â���� ����� ���� ��� ���� ����)
	// date: 2008.3.7 jk
        $sql = "insert into 3pl_sheet_in set
                        domain     = '$_SESSION[LOGIN_DOMAIN]',
                        crdate     = now(),
                        crtime     = now(),
                        cruser     = '$_SESSION[LOGIN_ID]',
                        title      = '$title',
                        warehouse  = '$warehouse',
                        cnt_type   = 'check',
                        stock_type = 'asking'";

        mysql_query($sql, $this->m_connect) or die(mysql_error());
        echo "<script>alert('�԰���ǥ�� �����Ǿ����ϴ�.');</script>";
        echo "<script>opener.location.reload();</script>";
        echo "<script>self.close();</script>";
	// $this->opener_redirect ( "template.htm?template=KC00" );
        exit;
    }

    ////////////////////////////////////////////
    // ���� ���ε�
    function upload()
    {
	global $_file;

	$sheet = $_REQUEST[sheet];
	$warehouse = $_REQUEST[warehouse];

        $obj = new class_file();
        $arrs = $obj->upload();

        $total_rows = sizeof($arrs);

        $rows = 0;
	$_data = array();
        foreach ( $arrs as $line )
        {
	    $product_id = $line[0];
	    $qty        = $line[1];

	    if (ereg("��ǰ�ڵ�", $product_id)) continue;
	    $_data[$product_id] = $_data[$product_id] + $qty;

	    /*
	    // �ϳ��� ��ǰ�� ���� �� ��� �� ��� �� ���� ����
            if ($product_id && $qty)
            {   
                $sql = "insert into 3pl_tmp_stock_wait set
                                domain     = '$_SESSION[LOGIN_DOMAIN]',
                                product_id = '$product_id',
                                qty        = '$qty',
                                sheet      = '$sheet',
                                warehouse  = '$warehouse'
                ";
                mysql_query($sql, $this->m_connect) or die(mysql_error()); 
            } 
	    $rows++;
	    */
        }

	/////////////////////////////////
	// �Է� �� ���� �ڷ� ���� ��
	$query = "truncate 3pl_tmp_stock_wait";
        mysql_query($query, $this->m_connect) or die(mysql_error()); 

	//////////////////////////////////////
	$rows=0;
	foreach( $_data as $product_id=>$qty )
	{
            $sql = "insert into 3pl_tmp_stock_wait set
                                domain     = '$_SESSION[LOGIN_DOMAIN]',
                                product_id = '$product_id',
                                qty        = '$qty',
                                sheet      = '$sheet',
                                warehouse  = '$warehouse'
            ";
            mysql_query($sql, $this->m_connect) or die(mysql_error()); 
	    $rows++;
	}

        echo "<script>alert('${rows}���� �԰��ڷ��Է��� �Ϸ�Ǿ����ϴ�.');</script>";
        echo "<script>document.location.href = 'template.htm?template=KC02&sheet=$sheet';</script>";
        exit;

    }

    ////////////////////////////////////////////
    // �԰����� ����� ���� �⺻ ���� �ٿ�ε�
    function download()
    {
        global $template;

        require_once  "Spreadsheet/Excel/Writer.php";

        // create a workbook & worksheet
        $workbook = new Spreadsheet_Excel_Writer();
        $worksheet =& $workbook->addWorksheet('�԰������ �������');

        $format_header =& $workbook->addFormat();
        $format_header->setAlign('center');
        $format_header->setBold();


        ////////////////////////////////////////////////////////////////
        $header_items = array ("��ǰ�ڵ�", "����");
        $col = 0;
        foreach ($header_items as $item)
        {
            $worksheet->write(0, $col, $item, $format_header);
            $col++;
        }
        $workbook->close();
    }

    function list_tmp_stock_wait()
    {
        $sql = "select * from 3pl_tmp_stock_wait where domain = '$_SESSION[LOGIN_DOMAIN]'";
        $result = mysql_query($sql, $this->m_connect) or die(mysql_error());
	$row = mysql_num_rows($result);

        return $result;	
    }

    function get_name($product_id, &$retname, &$retoption)
    {
	$sql = "select product_name, options from 3pl_products
		 where domain = '$_SESSION[LOGIN_DOMAIN]'
		   and product_id = '$product_id'";
	$list = mysql_fetch_array(mysql_query($sql, $this->m_connect));
	$retname = $list[product_name];
	$retoption = $list[options];

	if (!$retname) $retname = "*** 3PL �̵�ϻ�ǰ ***";

	return;
    }

    // â�� ���
    function select_warehouse($warehouse)
    {
        echo "<select name=warehouse>";
	$sql = "select * from 3pl_warehouse order by priority";
        $result = mysql_query($sql, $this->m_connect) or die(mysql_error());
	while ($list = mysql_fetch_array($result))
	{
            $selected = ($list[warehouse] == $warehouse) ? "selected" : "";
            echo "<option value=${list[warehouse]} ${selected}>${list[name]}</option>";
	}
        echo "</select>";
    }

    function get_warehouse_name($warehouse)
    {
	$sql = "select * from 3pl_warehouse where warehouse= '$warehouse'";
	$list = mysql_fetch_array(mysql_query($sql, $this->m_connect));

	if ($list) return $list[name];
	else return $warehouse;
    }

    ///////////////////////
    function save_stock_wait()
    {
        global $template;

        $sql = "select * from 3pl_tmp_stock_wait";
        $result = mysql_query($sql, $this->m_connect) or die(mysql_error());
        $rows = 0;
        while ($list = mysql_fetch_array($result))
        {
            // 3pl_stock_wait�� ������ �߰��Ѵ�
            $ins_sql = "insert into 3pl_stock_wait set
                                domain     = '$list[domain]',
                                product_id = '$list[product_id]',
                                warehouse  = '$list[warehouse]',
                                qty        = '$list[qty]',
                                sheet      = '$list[sheet]'
            ";
            mysql_query($ins_sql, $this->m_connect) or die(mysql_error());
        
        
            // �ӽ����̺��� ������ �����Ѵ�.
            $del_sql = "delete from 3pl_tmp_stock_wait
                         where domain = '$list[domain]'
                           and product_id = '$list[product_id]'";
            mysql_query($del_sql, $this->m_connect) or die(mysql_error());

	    /* �⺻ ���´� 0 */
	    $upd_sql = "update 3pl_sheet_in set
				status = 0
			 where seq = '$list[sheet]'
	    ";
            mysql_query($upd_sql, $this->m_connect) or die(mysql_error());

            $rows++;
        }
        // echo "<script>alert('${rows}���� �ڷ��Է��� �Ϸ�Ǿ����ϴ�.');</script>";
        echo "<script>document.location.href = 'template.htm?template=KC00';</script>";
        exit;
    }


    //////////////////////////////
    function del_stock_wait()
    {
        global $template;

        $del_sql = "truncate table 3pl_tmp_stock_wait";
        mysql_query($del_sql, $this->m_connect) or die(mysql_error());

        echo "<script>alert('�����Ǿ����ϴ�.');</script>";
	$this->redirect("template.htm?template=$template");
        exit;
    }

    function register()
    {
        global $template;

	foreach ($_REQUEST as $key=>$value)
	    $$key = $value;

	if ($product_id1 && $qty1)
	{
            $ins_sql = "insert into 3pl_stock_wait set
                                domain     = '$_SESSION[LOGIN_DOMAIN]',
                                product_id = '$product_id1',
                                qty        = '$qty1',
                                sheet      = '$sheet'
            ";
            mysql_query($ins_sql, $this->m_connect) or die(mysql_error());
	}
	if ($product_id2 && $qty2)
	{
            $ins_sql = "insert into 3pl_stock_wait set
                                domain     = '$_SESSION[LOGIN_DOMAIN]',
                                product_id = '$product_id2',
                                qty        = '$qty2',
                                sheet      = '$sheet'
            ";
            mysql_query($ins_sql, $this->m_connect) or die(mysql_error());
	}
	if ($product_id3 && $qty3)
	{
            $ins_sql = "insert into 3pl_stock_wait set
                                domain     = '$_SESSION[LOGIN_DOMAIN]',
                                product_id = '$product_id3',
                                qty        = '$qty3',
                                sheet      = '$sheet'
            ";
            mysql_query($ins_sql, $this->m_connect) or die(mysql_error());
	}
	if ($product_id4 && $qty4)
	{
            $ins_sql = "insert into 3pl_stock_wait set
                                domain     = '$_SESSION[LOGIN_DOMAIN]',
                                product_id = '$product_id4',
                                qty        = '$qty4',
                                sheet      = '$sheet'
            ";
            mysql_query($ins_sql, $this->m_connect) or die(mysql_error());
	}
	if ($product_id5 && $qty5)
	{
            $ins_sql = "insert into 3pl_stock_wait set
                                domain     = '$_SESSION[LOGIN_DOMAIN]',
                                product_id = '$product_id5',
                                qty        = '$qty5',
                                sheet      = '$sheet'
            ";
            mysql_query($ins_sql, $this->m_connect) or die(mysql_error());
	}

 	////////////////////////////////////////
	/* jk ���� ���� ����� �԰� ��û
	if ($qty1 || $qty2)
	{
	    $upd_sql = "update 3pl_sheet_in set status = 1 
			 where seq = '$sheet'
			   and status = 0";
            mysql_query($upd_sql, $this->m_connect) or die(mysql_error());
	}
	*/

        echo "<script>alert('�ڷ��Է��� �Ϸ�Ǿ����ϴ�.');</script>";
        echo "<script>opener.location.reload();</script>";
	$this->redirect("?template=KC00&action=win_close");
	exit;
    }

    /////////////////////////////////////
    function save_preok_qty()
    {
	foreach ($_REQUEST as $key=>$value)
	    $$key = $value;


	$sql = "update 3pl_stock_wait set
		       qty = '$qty'
		 where seq = '$seq'
		   and domain = '$_SESSION[LOGIN_DOMAIN]'
		   and status = 0
	";
	mysql_query($sql, $this->m_connect) or die(mysql_error());
	exit;
    }

    /////////////////////////////////////
    function save_ok_qty()
    {
	foreach ($_REQUEST as $key=>$value)
	    $$key = $value;


	$sql = "update 3pl_stock_wait set
		       qty = '$qty'
		 where seq = '$seq'
		   and domain = '$_SESSION[LOGIN_DOMAIN]'
		   and status = 1
	";
	mysql_query($sql, $this->m_connect) or die(mysql_error());
	exit;
    }

    /////////////////////////////////////
    function del_preok_qty()
    {
	foreach ($_REQUEST as $key=>$value)
	    $$key = $value;


	$sql = "delete from 3pl_stock_wait
		 where seq = '$seq'
		   and domain = '$_SESSION[LOGIN_DOMAIN]'
		   and status = 0
	";
	mysql_query($sql, $this->m_connect) or die(mysql_error());
	
	$response = "<strike>������</strike>";
	echo $response;
	exit;
    }
    
    function preok_all()
    {
	$sheet = $_REQUEST[sheet];

	//////////////////////////////////////
	$sql = "update 3pl_stock_wait set
		       status = 1
		 where sheet = '$sheet'
		   and domain = '$_SESSION[LOGIN_DOMAIN]'
		   and status = 0
	";
	mysql_query($sql, $this->m_connect) or die(mysql_error());

	/////////////////////////////////////
	$sql = "update 3pl_sheet_in set
		       status = 2
		 where seq    = '$sheet'
		   and domain = '$_SESSION[LOGIN_DOMAIN]'
		   and status = 1
	";
	mysql_query($sql, $this->m_connect) or die(mysql_error());

        echo "<script>document.location.href = 'template.htm?template=KC03&sheet=${sheet}';</script>";
	exit;
    }

    function ok_all()
    {
	$sheet = $_REQUEST[sheet];

	//////////////////////////////////////
	$sql = "update 3pl_stock_wait set
		       status = 2
		 where sheet = '$sheet'
		   and domain = '$_SESSION[LOGIN_DOMAIN]'
		   and status = 1
	";
	mysql_query($sql, $this->m_connect) or die(mysql_error());

	/////////////////////////////////////
	$sql = "update 3pl_sheet_in set
		       status = 4
		 where seq    = '$sheet'
		   and domain = '$_SESSION[LOGIN_DOMAIN]'
		   and status = 3
	";
	mysql_query($sql, $this->m_connect) or die(mysql_error());

        echo "<script>document.location.href = 'template.htm?template=KC03&sheet=${sheet}';</script>";
	exit;
    }

    /////////////////////////////////////////
    function get_sheet_status($sheet)
    {
	/////////////////////////////////////
	$sql = "select a.status,b.arrive_date 
                  from 3pl_sheet_in a, 3pl_stock_wait b
		 where a.seq    = b.sheet
                   and a.seq    = '$sheet'
		   and a.domain = '$_SESSION[LOGIN_DOMAIN]'
	";
echo $sql;
	$result = mysql_query($sql, $this->m_connect) or die(mysql_error());
	$list = mysql_fetch_array($result);

	return $list;
    }

    //////////////////////////////////////
    function get_name_sheet_sts($status)
    {
        switch ($status)
        {
            case 0:
                $ret = "â�� ���� ���(0)";
                break;
            case 1:
                $ret = "���� Ȯ�� ���(1)";
                break;
            case 2:
                $ret = "���� Ȯ�� �Ϸ�(2)";
                break;
            case 3:
                $ret = "���� ��Ȯ�� ���(3)";
                break;
            case 4:
                $ret = "�԰� �Ϸ� ���(4)";
                break;
            case 5:
                $ret = "�԰� �Ϸ�(5)";
                break;
            case 6:
                $ret = "��ǰ �̵��(6)";
                break;
            case 7:
                $ret = "�԰� �Ϸ� ���(7)";
                break;
            default :
                $ret = $status;
                break;
        }

        // $ret = "<img src=/images/icon_step0${status}.gif align=absmiddle>";

        return $ret;
    }

    function get_org_id($product_id)
    {
	$sql = "select org_id from 3pl_products
		 where product_id = '$product_id'";
	$result = mysql_query($sql, $this->m_connect) or die(mysql_error());
	$list = mysql_fetch_array($result);

	return $list[org_id];

    }

    function get_goods_cnt($sheet)
    {
	$sql = "select count(*) cnt from 3pl_stock_wait
		 where sheet = '$sheet'";

	$result = mysql_query($sql, $this->m_connect) or die(mysql_error());
	$list = mysql_fetch_array($result);

	return $list[cnt];
    }
}
?>
