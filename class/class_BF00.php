<?
require_once "class_top.php";
require_once "class_B.php";
require_once "class_file.php";

////////////////////////////////
// class name: class_BF00
//

class class_BF00 extends class_top {

    ///////////////////////////////////////////
    // BF00

    function BF00()
    {
	global $connect;
	global $template, $line_per_page, $activate;
	
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    ////////////////////////////////////////////////////
    //
    // �� ��ȸ
    //
    function query()
    {
	global $connect, $template, $start_date, $begin_time, $end_time, $shop_id, $_date, $activate;

	$begin_time = $begin_time ? $begin_time : "00:00:00";	
	$end_time   = $end_time ? $end_time : "23:59:59";	
	$begin = "$star_date $begin_time";
	$end   = "$star_date $end_time";

	if ( $shop_id )
	    $option .= " and shop_id = $shop_id";

	$query_count = "select count(*) cnt from orders 
                   where $_date >= '$start_date $begin_time' 
                     and $_date <= '$start_date $end_time' 
                         ${option} ";
	$result = mysql_query ( $query_count, $connect ) or die( mysql_error() );
	$data   = mysql_fetch_array ( $result );
	$tot_rows = $data[cnt];

	$query = "select * from orders 
                   where $_date >= '$start_date $begin_time' 
                     and $_date <= '$start_date $end_time' 
                         ${option} limit 0,100";

	//if ( $_SESSION[LOGIN_LEVEL] == 9 )
	//	echo $query;

	$result = mysql_query ( $query, $connect ) or die( mysql_error() );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //////////////////////////////////////////////////
    //
    // ���� ��ȣ ����
    //
    function del_transno()
    {
	global $connect;
	global $template, $start_date, $begin_time, $end_time, $shop_id, $_date;

	if ( $shop_id )
	    $option .= " and shop_id = $shop_id";

	$query = "update orders set 
                         trans_no   = '',
                         trans_date = '',
                         status     = 1,
                         trans_corp = ''
                   where $_date >= '$start_date $begin_time' 
                     and $_date <= '$start_date $end_time'
                         ${option} ";

	mysql_query ( $query, $connect ) or die( mysql_error() );
	$this->query();
    }

    // upload
    function upload()
    {
	global $top_url, $connect, $_file;

	// �ʱ�ȭ
	$query = "delete from upload_temp where type='del_transno'";
	mysql_query ( $query, $connect );

	$this->show_wait();

	$obj = new class_file();
        $arr_result = $obj->upload();

	print_r ( $arr_result );

exit;
 	$rows = 0;
	foreach ( $arr_result as $row )
	{
	    $rows++;
//	    if ( $rows == 1 ) continue;
 
	    $infos[shop_id]    = "transno";
	    $infos[type]       = "del_transno";
	    $infos[row]        = $rows;
	    $infos[col]        = 1;
	    $infos[value]      = $row[0];

	    $this->insert_info( $infos );
	    ///////////////////////////////
	    // sync product 
	    $str = "${rows} / ${total_rows}��° �۾����Դϴ�.";
	    echo "<script>show_txt('$str');</script>";
	    flush();
	}

	$this->hide_wait();
	$this->jsAlert ( "����: $rows���� �۾� �Ϸ�" );
	$this->redirect ("?template=BF00&activate=2" );
	exit;
    }

    ///////////////////////////////////////////
    // upload_temp�� ���� �Է�
    function insert_info( $infos )
    {
	global $connect;
	$query = "insert into upload_temp 
                     set shop_id='$infos[shop_id]',
                         type   ='$infos[type]',
                         row    ='$infos[row]',
                         col    ='$infos[col]',
                         value  ='$infos[value]'";
	mysql_query ( $query, $connect );
    }

    // upload��� ���
    function disp_result()
    {
	global $connect;
	$query = "select * From upload_temp where type='del_transno' order by row";
	$result = mysql_query ( $query, $connect );
echo "<table border=0 cellpadding=0 cellspacing=1 width=150 bgcolor=cccccc>
	<tr>
            <td class=header1>�����ȣ</td>
	</tr>
";
	while ( $data = mysql_fetch_array( $result ) )
	{
	    echo "<tr><td bgcolor=ffffff align=center>$data[value]</td></tr>";
	}
echo "</table>";
    }

    function del_uploaded()
    {
	global $connect;
	$query = "select * From upload_temp where type='del_transno' order by row";
	$result = mysql_query ( $query, $connect );
	$i=0;
	while ( $data = mysql_fetch_array( $result ) )
	{
	    $trans_nos .= $i ? "," : "";
	    $trans_nos .= $data[value];
	    $i++;
	}

	$query = "update orders set status=1, trans_corp='', trans_no='', trans_date='', status=1 where trans_no in ( $trans_nos )";

	mysql_query( $query, $connect );
	echo mysql_affected_rows() . "���� ������ ���� �Ǿ����ϴ�.";

	// ������ ���� ���� ����	
	$query = "delete From upload_temp where type='del_transno'";
	mysql_query ( $query, $connect );
    }
}

?>
