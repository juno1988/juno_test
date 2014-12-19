<?
require_once "class_top.php";
require_once "class_B.php";

////////////////////////////////
// class name: class_B400
//

class class_B400 extends class_top {

    ///////////////////////////////////////////
    // shop들의 list출력

    function B400()
    {
	global $connect;
	global $template, $line_per_page;
	$link_url = $this->build_link_url();

        $result = $this->trans_list();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    // 
    // 포맷 변경 내역보기 2014.10.28 - jkryu
    //
    function B402()
    {
	    global $connect;
	    global $template, $line_per_page,$start_date,$end_date;
	    
	    if ( !$start_date )
	        $start_date = date('Y-m-d', strtotime('-20 days'));
	    
	    $query = "select * from user_print_item_changed 
	               where crdate >= '$start_date 00:00:00'
	                 and crdate <= '$end_date 23:59:59'
	               order by crdate desc limit 100";
	    $result = mysql_query( $query, $connect );
	    
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    //
    // title 정보 
    // 2014.10.28 - jkryu
    function get_title_info( $title_seq )
    {
        global $connect;
        
        $query = "select * from user_print_title where seq=$title_seq";  
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        
        return $data;
    }
    
    ///////////////////////////////////////////
    // add
    // date: 2005.9.2
    function add()
    {
	    global $connect, $trans_corp, $url;
	    $sys_connect = sys_db_connect();

	    $sql = "select * from sys_transinfo where id = '$trans_corp'";
	    $list = mysql_fetch_array(mysql_query($sql, $sys_connect));

	    $sql = "insert into trans_info 
                   set  id = '$list[id]',
			            trans_corp = '" . $list[trans_corp] . "',
                        url='$url'";
	    mysql_query($sql, $connect) or die(mysql_error());
	    
	    // 각 택배사별 기본 "이지어드민" 포멧 추가
	    $query = "select * from sys_trans_conf where trans_corp=$trans_corp";
	    $result = mysql_query($query, $sys_connect);
	    if( $result )
	    {
	        $data = mysql_fetch_assoc( $result ) ;
	        
	        // 포멧추가
	        $query_conf = "insert trans_conf
	                          set trans_corp       = '$data[trans_corp]',
                                  name             = '$data[name]',
                                  is_header        = '$data[is_header]',
                                  pack_multiline   = '$data[pack_multiline]',
                                  sortinfo         = '$data[sortinfo]',
                                  position_seq     = '$data[position_seq]',
                                  position_transno = '$data[position_transno]',
                                  crdate           = now(),
                                  product_sum      = '$data[product_sum]'";
	        mysql_query($query_conf, $connect);
	        
	        // format_id 가져오기
	        $query_format_id = "select * from trans_conf where trans_corp = $trans_corp order by crdate desc limit 1";
	        $result_format_id = mysql_query($query_format_id, $connect);
	        $data_format_id = mysql_fetch_assoc($result_format_id);
	        
	        $new_format_id = $data_format_id[format_id];
	        
	        // sys에서 포멧 정보 가져오기
	        $query_sys_format = "select * from sys_trans_format where format_id=$data[format_id]";
	        $result_sys_format = mysql_query($query_sys_format, $sys_connect);
	        while( $data_sys_format = mysql_fetch_assoc($result_sys_format) )
	        {
	            $query_format = "insert trans_format
	                                set trans_id    = $trans_corp,
	                                    format_id   = $new_format_id,
	                                    seq         = $data_sys_format[seq],
	                                    macro_value = '$data_sys_format[macro_value]',
	                                    macro_desc  = '$data_sys_format[macro_desc]'";
	            mysql_query( $query_format, $connect );
	        }
	    }

        $this->redirect ( "?template=B400 " );
	    exit;
    }

    ///////////////////////////////////////////
    // delete
    function delete()
    {
    	global $connect;
        
    	$id_list = $_REQUEST["result"];
            $id_list = substr ( $id_list, 0, strlen( $id_list) - 1 );
    
    	$sql = "delete from trans_info where id in ($id_list)";
    	mysql_query($sql, $connect) or die(mysql_error());
    
    	echo "<script>document.location.href = '?template=B400';</script>";
    	exit;
    }

    function set_master()
    {
	    global $connect, $chk;
    
	    $id_list = $_REQUEST["result"];
        $id_list = substr ( $id_list, 0, strlen( $id_list) - 1 );

        if ( $chk )
        {
    	    $query = "update ez_config set base_trans_code=$chk";
    	    mysql_query( $query, $connect );
    	    //echo $query;   
    	    
    	    $_SESSION[BASE_TRANS_CODE] = $chk;
    	}
    	echo "<script>document.location.href = '?template=B400';</script>";
    	exit;
    }

    //////////////////////////////////////////////////////
   
    function trans_list()
    {
       global $connect;

       $query = "select * from trans_info order by trans_corp";
       $result = mysql_query ( $query, $connect );
       return $result;
    }


}

?>
