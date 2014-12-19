<? 
require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_supply.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_lock.php";
include "class_C.php";

class class_ET00 extends class_top
{	
	function get_option()
	{
		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	,	$shop_id	, $confirm_check;
		
		$query_option="";
		if($date_type == "collect_date" || $date_type == "order_date" || $date_type == "trans_date_pos")
			$query_option .= " AND b.$date_type >= '$start_date' AND b.$date_type <= '$end_date' ";
		else if($date_type == "cancel_date")
			$query_option .= " AND c.$date_type >= '$start_date 00:00:00' AND c.$date_type <= '$end_date 23:59:59' ";
		else if($date_type == "insert_date")
			$query_option .= " AND a.$date_type >= '$start_date 00:00:00' AND a.$date_type <= '$end_date 23:59:59' ";
		else if($date_type == "confirm_date")
			$query_option .= " AND a.$date_type >= '$start_date 00:00:00' AND a.$date_type <= '$end_date 23:59:59' ";
		
		if($query_str)
		{
			if($query_type  == "order_id" || $query_type  == "trans_no")
				$query_option .= " AND b.$query_type = '$query_str' ";
			else if($query_type  == "order_name" || $query_type  == "recv_name")
				$query_option .= " AND b.$query_type LIKE '%$query_str%' ";
			else if($query_type  == "order_recv_name")
				$query_option .= " AND (b.order_name LIKE '%$query_str%' OR b.recv_name LIKE '%$query_str%') ";
			else if($query_type  == "worker" )
				$query_option .= " AND a.confirm_worker LIKE '%$query_str%' ";
		}
		if($confirm_check == 1)
			$query_option .= " AND a.confirm_check = 1 ";
		else if($confirm_check == 2)
			$query_option .= " AND a.confirm_check = 0 ";
		
		if($shop_id)
			$query_option .= " AND b.shop_id = '$shop_id' ";
			
		$query_option .= " ORDER BY a.insert_date DESC";
			
		return $query_option;
	}
		
	function get_select_query()
	{

		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	,	$shop_id	, $confirm_check;
				
		$query = "    SELECT b.seq
						   , b.order_id
						   , b.order_id_seq
						   , b.shop_id
						   , b.order_name
						   , b.collect_date
						   , b.collect_time
						   , b.trans_date_pos
						   , b.trans_no
						   , b.order_cs
						   , b.status
						   , a.cancel_date
						   , a.insert_date		
						   , a.seq cancel_seq
						   , a.confirm_check
						   , a.confirm_worker
						   , a.confirm_date
						   
					    FROM cancel_withdraw a
					       , orders b
					       
					   WHERE a.order_seq = b.seq ";
		
		return $query;
	}
	
	function ET00()
	{
		global $template					;
		global $connect						;
		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	,	$shop_id	, $confirm_check;
		global $page		,	$line_per_page,		$link_url;
		
		$query_str = trim( $query_str );
		$line_per_page = 50;

        // 페이지
        if( !$page )
        {	
        	$page = 1;            
            $start_date	= date("Y-m-d");
            $end_date	= date("Y-m-d");
            $query_str	= "";            
            $shop_id 	= "";
            $date_type	= "insert_date";
            
            $_REQUEST[page] 		= $page;
            $_REQUEST[start_date] 	= $start_date;
            $_REQUEST[end_date]		= $end_date;
            $_REQUEST[date_type]	= $date_type;
            $_REQUEST[query_type] 	= $query_type;
            $_REQUEST[query_str]	= $query_str;
            $_REQUEST[shop_id] 		= $shop_id;
        }
        
		// link url
        $par = array('template', 'start_date', 'end_date', 'date_type', 'query_type', 'query_str','shop_id','confirm_check');
        $link_url = $this->build_link_url3( $par );      

		$query = $this->get_select_query();
		$query .= $this->get_option();
		
//echo $query;

		$result = mysql_query($query, $connect);
		$total_rows = mysql_num_rows( $result );

        $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
		$result = mysql_query($query, $connect);

		$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";
	}
	
	//작업 확인
	function confirm()
	{
		global $seq, $work, $query;
		global $connect;
		
		$login_id = $_SESSION[LOGIN_ID];
		$val=array();
		
		
		if($work)
		{// 확인->미확인
			$query ="SELECT seq, order_id, order_id_seq FROM orders WHERE seq IN ($seq)";
			$result = mysql_query($query, $connect);
			while($data = mysql_fetch_assoc($result))
			{
				$query ="INSERT INTO auto_canceled_order SET seq = $data[seq]
														   , order_id = $data[order_id]
														   , cancel_date = now()
														   , status = 1
														   , order_id_seq = $data[order_id_seq]";
				mysql_query($query, $connect);
			}
			$query ="UPDATE cancel_withdraw SET confirm_date = now(), confirm_worker ='$login_id' , confirm_check = $work WHERE seq IN ($seq) AND confirm_check = 0";
		}
		else 
		{//미확인->확인
			$query ="DELETE FROM auto_canceled_order WHERE seq IN ($seq)";
			mysql_query($query, $connect);
		 	$query ="UPDATE cancel_withdraw SET confirm_date = now(), confirm_worker ='$login_id' , confirm_check = $work WHERE seq IN ($seq) AND confirm_check = 1";
		}

		$result = mysql_query($query, $connect);
		$val['error'] = 0;
		if($result < 1)
			$val['error'] = 1;
		
        echo json_encode( $val );
	}
	
	function save_file_ET00()
    {
    	global $template					;
		global $connect						;
		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	,	$shop_id	, $confirm_check;

		$query = $this->get_select_query();
		$query .= $this->get_option();
		

        $result = mysql_query($query, $connect);
        
        $n = 0;
        $old_time = time();
        $total_rows = mysql_num_rows( $result );
        
        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result))
        {
			if($data[confirm_check])
				$work_str = "확인";
			else
				$work_str = "미확인";
				
        	$arr_temp = array();			
			$arr_temp['관리번호'		]=$data[seq];
			$arr_temp['판매처'			]=class_C::get_shop_name($data[shop_id]);
			$arr_temp['주문번호'		]=$data[order_id];
			$arr_temp['주문자'			]=$data[order_name];
			$arr_temp['상태'			]=$this->get_order_status2( $data[status]);
			$arr_temp['cs'				]=$this->get_order_cs2($data[order_cs]);
			$arr_temp['발주일'			]=$data[collect_date] ." ".$data[collect_time];
			$arr_temp['배송일'			]=$data[trans_date_pos];
			$arr_temp['송장번호'		]=$data[trans_no];
			$arr_temp['취소일'			]=$data[cancel_date];
			$arr_temp['취소철회접수일'	]=$data[insert_date];
			$arr_temp['작업자'			]=$data[confirm_worker];
			$arr_temp['작업일자'		]=$data[confirm_date];
			$arr_temp['확인'			]=$work_str;
	
        	$arr_datas[] = $arr_temp;
        	        	
            // 진행
            $n++;
            if( $old_time < time() )
            {
                $old_time = time();
                $msg = " $n / $total_rows ";
                echo str_pad(" " , 256); 
                echo "<script type='text/javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
        }
        $this->make_file_ET00( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
	}

    function make_file_ET00( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
    	global $template					;
		global $connect						;
		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	,	$shop_id	, $confirm_check;
		
		$saveTarget = _upload_dir . $filename;
		
    	// file open
        $handle = fopen ($saveTarget, "w");
        
        $buffer = $this->default_header;
        
        fwrite($handle, $buffer);
		$buffer = "<tr>\n";			
			$buffer .="<td class=header_item>관리번호		</td>";
			$buffer .="<td class=header_item>판매처			</td>";
			$buffer .="<td class=header_item>주문번호		</td>";
			$buffer .="<td class=header_item>주문자			</td>";
			$buffer .="<td class=header_item>상태			</td>";
			$buffer .="<td class=header_item>cs				</td>";
			$buffer .="<td class=header_item>발주일			</td>";
			$buffer .="<td class=header_item>배송일			</td>";
			$buffer .="<td class=header_item>송장번호		</td>";
			$buffer .="<td class=header_item>취소일			</td>";
			$buffer .="<td class=header_item>취소철회접수일	</td>";
			$buffer .="<td class=header_item>작업자			</td>";
			$buffer .="<td class=header_item>작업일자		</td>";
			$buffer .="<td class=header_item>확인			</td>";
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

    function download_ET00()
    {
        global $filename;
        global $start_date	,	$end_date	;
        $obj = new class_file();        
        $obj->download_file( $filename, $start_date."~".$end_date."_stat_file.xls");
    } 
}
?>
