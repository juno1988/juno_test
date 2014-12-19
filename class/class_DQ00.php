<?
require_once "class_top.php";
require_once "class_D.php";
require_once "class_C.php";
require_once "class_E.php";
require_once "class_B.php";
require_once "class_file.php";
require_once "class_E900.php";
require_once "class_lock.php";
require_once "class_shop.php";


////////////////////////////////
// class name: class_DQ00
//

class class_DQ00 extends class_top 
{

	function DQ00()
	{
		global $template, $connect;
/* 
		$line_per_page = _line_per_page;
		$link_url = "?" . $this->build_link_url();
		if ( $_REQUEST["page"] )
	    	$result = $this->get_order_list( &$total_rows ); ; 
*/
		$master_code = substr($template, 0, 1);
    	include "template/" . $master_code ."/" . $template . ".htm";
	}

	function DQ01()
	{
		global $template;

		$master_code = substr($template, 0, 1);
    	include "template/" . $master_code ."/" . $template . ".htm";
	}

	function DQ02()
	{
		global $connect, $template, $start_date, $end_date, $shop_id,$group_id, $page;

		$link_url = "?" . $this->build_link_url();
		$line_per_page = 100;

		$page = $_REQUEST["page"];
		

		$total_rows = 0;		
		$result = $this->search( &$total_rows );
			
		$master_code = substr($template, 0, 1);
    	include "template/" . $master_code ."/" . $template . ".htm";
	}

	function search( &$total_rows , $is_download = 0)
	{
		global $connect, $template, $start_date, $end_date, $shop_id,$group_id, $page;

		$line_per_page = 100;

		$query = "select * from fake_trans
				   where reg_date >= '$start_date 00:00:00'
					 and reg_date <= '$end_date 23:59:59' ";

		if ( $shop_id )
			$query .= " and shop_id = '$shop_id'";
			
		if ( $group_id )
        {
            $shop_group = $this->get_group_shop( $group_id ); 
            $query .= " AND shop_id IN ($shop_group)";
        }
        
debug( $query );
		$result = mysql_query ( $query, $connect );
		$total_rows = mysql_num_rows ( $result );
		
		if($is_download == 0)
		{
			$starter = $page ? ( $page-1 ) * $line_per_page : 0;
			$query .= " limit $starter, $line_per_page";
		}
		$result = mysql_query ( $query, $connect );

		return $result;
	}
    function get_group_shop( $group_id )
    {
        global $connect;
        
        $query = "select shop_id from shopinfo where group_id=$group_id"; 
        

        $result = mysql_query( $query, $connect );
        $shop_ids = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $shop_ids .= $shop_ids ? "," : "";
            $shop_ids .= $data[shop_id];
        }
        
debug("shop_ids : " . $shop_ids );
        return $shop_ids;
    }
	function query()
    {
        global $connect, $template, $start_date, $begin_time, $end_time, $shop_id ,$group_id;

        $begin_time = $begin_time ? $begin_time : "00:00:00";
        $end_time   = $end_time ? $end_time : "23:59:59";

        $query = "select * from orders 
                   where trans_date >= '$start_date $begin_time' 
                     and trans_date <= '$start_date $end_time' ";

		if ( $shop_id )
	          $query .= " and shop_id = $shop_id";
		if ( $group_id )
        {
            $shop_id = $this->get_group_shop( $group_id );      
            $use_products = 1;
        }
        $result = mysql_query ( $query, $connect ) or die( mysql_error() );
        $tot_rows = mysql_num_rows ( $result );

        // 전체 송장수
        $query_trans = $query . " group by trans_no";
        $result_trans = mysql_query($query_trans, $connect);
        $trans_total = mysql_num_rows($result_trans);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
	function save_file_DQ01()
    {
    	global $connect, $template, $start_date, $begin_time, $end_time, $shop_id;

        $begin_time = $begin_time ? $begin_time : "00:00:00";
        $end_time   = $end_time ? $end_time : "23:59:59";

        $query = "select * from orders 
                   where trans_date >= '$start_date $begin_time' 
                     and trans_date <= '$start_date $end_time' ";

		if ( $shop_id )
	          $query .= " and shop_id = $shop_id";
    	$result = mysql_query ( $query, $connect ) or die( mysql_error() );
    	
    	
        $n = 0;
        $old_time = time();
        $total_rows = mysql_num_rows( $result );
        
        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result))
        {
        	$arr_temp = array();			
			$arr_temp['판매처'		] = class_shop::get_shop_name( $data[shop_id] );
			$arr_temp['주문번호'	] = $data[order_id];
			$arr_temp['수령자'		] = $data[recv_name];
			$arr_temp['발주일'		] = $data[collect_date];
			$arr_temp['송장입력일'	] = $data[trans_date];
			$arr_temp['배송일'		] = $data[trans_date_pos];
			$arr_temp['송장번호'	] = $data[trans_no];
			$arr_temp['택배사'		] = class_transcorp::get_corp_name( $data[trans_corp] );
					
			$arr_datas[] = $arr_temp;
			// 진행
            $n++;
            if( $old_time < time() )
            {
                $old_time = time();
                $msg = " $n / $total_rows ";
                //echo str_pad(" " , 256); 
                //echo "<script type='text/javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
		}
		$this->make_file_DQ01( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }
    function make_file_DQ01( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
    	global $connect, $template, $start_date, $begin_time, $end_time, $shop_id;
		$saveTarget = _upload_dir . $filename;
		
    	// file open
        $handle = fopen ($saveTarget, "w");
        
        $buffer = $this->default_header;
        
        fwrite($handle, $buffer);
        $buffer = "<tr>\n";
			$buffer .="<td class=header_item>	판매처   	</td>";
			$buffer .="<td class=header_item>	주문번호   	</td>";
			$buffer .="<td class=header_item>	수령자  	</td>";
			$buffer .="<td class=header_item>	발주일  	</td>";
			$buffer .="<td class=header_item>	송장입력일  </td>";
			$buffer .="<td class=header_item>	배송일    	</td>";
			$buffer .="<td class=header_item>	송장번호   	</td>";
			$buffer .="<td class=header_item>	택배사  	</td>";
        $buffer .= "</tr>\n";
        			
        
        fwrite($handle, $buffer);
        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";
            foreach( $val as $key => $v )
            {
            	$buffer .= "<td class=str_item>$v</td>\n";
            }
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }
    
    function download_DQ01()
    {
        global $filename;
        $obj = new class_file();        
        $obj->download_file( $filename, "fake_trans_list.xls");
    }
    
    function save_file_DQ02()
    {
    	global $connect, $template, $start_date, $end_date, $shop_id;


		$total_rows = 0;	
        $result = $this->search( &$total_rows, 1);
    	
    	
        $n = 0;
        $old_time = time();
        $total_rows = mysql_num_rows( $result );
        
        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result))
        {
        	
			$status = "";
			switch ( $data[status] )
			{
				case 0 :
					$status = "발주";
					break;
				case 1 :
					$status = "접수";
					break;
				case 7 :
					$status = "송장";
					break;
				case 8 : 
					$status = "배송";
					break;	
			}	
			
        	$arr_temp = array();
			$arr_temp['등록일'] = $data[reg_date];
			$arr_temp['판매처'] =  class_shop::get_shop_name( $data[shop_id] );
			$arr_temp['관리번호'] = $data[seq]; 
			$arr_temp['주문번호'] = $data[order_id]; 
			$arr_temp['주문상세번호'] = $data[order_id_seq];
			$arr_temp['주문상태'] = $status;
			$arr_temp['송장번호'] = $data[trans_no];
			$arr_temp['택배사']= class_transcorp::get_corp_name( $data[trans_corp] );
					
					
					
					
					
			$arr_datas[] = $arr_temp;
			// 진행
            $n++;
            if( $old_time < time() )
            {
                $old_time = time();
                $msg = " $n / $total_rows ";
                //echo str_pad(" " , 256); 
                //echo "<script type='text/javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
		}
		$this->make_file_DQ02( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }
    function make_file_DQ02( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
    	global $connect, $template, $start_date, $end_date, $shop_id;
		$saveTarget = _upload_dir . $filename;
		
    	// file open
        $handle = fopen ($saveTarget, "w");
        
        $buffer = $this->default_header;
        
        fwrite($handle, $buffer);
        $buffer = "<tr>\n";
			$buffer .="<td class=header_item>	등록일   	</td>";
			$buffer .="<td class=header_item>	판매처   	</td>";
			$buffer .="<td class=header_item>	관리번호  	</td>";
			$buffer .="<td class=header_item>	주문번호  	</td>";
			$buffer .="<td class=header_item>	주문상세번호  </td>";
			$buffer .="<td class=header_item>	주문상태    	</td>";
			$buffer .="<td class=header_item>	송장번호   	</td>";
			$buffer .="<td class=header_item>	택배사  	</td>";
        $buffer .= "</tr>\n";
        			
        
        fwrite($handle, $buffer);
        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";
            foreach( $val as $key => $v )
            {
            	$buffer .= "<td class=str_item>$v</td>\n";
            }
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }
    
    function download_DQ02()
    {
        global $filename;
        $obj = new class_file();        
        $obj->download_file( $filename, "fake_trans_list.xls");
    } 
	function upload_fake_trans()
	{
		global $connect, $_file;
		
		$this->show_wait();
		$query = "truncate table fake_trans_temp";
		mysql_query( $query, $connect );

		$obj = new class_file();
		$arr_result = $obj->upload();

		$rows = 1 ;
		foreach ( $arr_result as $row )
		{
			$t_no = preg_replace('/[^0-9]/', '', $row[0]);
			if ( !is_numeric( $t_no) ) continue;
		
			$query = "insert into fake_trans_temp set
						row = '$rows',
						trans_no = '$t_no'
					  on duplicate key update 
						row = row"	;
			mysql_query( $query, $connect );			
			$rows++;
		}

        $this->hide_wait();
        $this->redirect ("?template=DQ00" );
	}

	function reg_fake_trans()
	{
		global $connect;

		$this->show_wait();

		$query = "select * from fake_trans_temp";
		$result = mysql_query( $query, $connect );
		$trans_no = "";
		
		while ( $data = mysql_fetch_array( $result ) )
		{
			if ( $trans_no == "" )
				$trans_no = $data[trans_no];
			else
				$trans_no .= "," . $data[trans_no];
		}

		include_once "class_fake_trans.php";
		class_fake_trans::reg_trans_no( $trans_no );

		$query = "truncate table fake_trans_temp";
		mysql_query( $query, $connect );

        $this->hide_wait();
        $this->redirect ("?template=DQ00" );
	}

	function reg_fake_trans2()
	{
        global $connect, $template, $start_date, $begin_time, $end_time, $shop_id;

		$this->show_wait();

        $begin_time = $begin_time ? $begin_time : "00:00:00";
        $end_time   = $end_time ? $end_time : "23:59:59";

        $query = "select * from orders 
                   where trans_date >= '$start_date $begin_time' 
                     and trans_date <= '$start_date $end_time' ";

		if ( $shop_id )
			$query .= " and shop_id = $shop_id";

        $result = mysql_query ( $query, $connect ) or die( mysql_error() );
		$trans_no = "";
		
		while ( $data = mysql_fetch_array( $result ) )
		{
			if ( $trans_no == "" )
				$trans_no = $data[trans_no];
			else
				$trans_no .= "," . $data[trans_no];
		}

		include_once "class_fake_trans.php";
		class_fake_trans::reg_trans_no( $trans_no );

        $this->hide_wait();
        $this->redirect ("?template=DQ01" );
	}

}
