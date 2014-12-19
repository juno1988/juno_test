<?
require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_supply.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_lock.php";
include "class_C.php";

////////////////////////////////
// class name: class_DN00
//
// box4u 전용페이지 하드코딩 //2014.09
// 상차지시서
// upload_direction
//
//
//CREATE TABLE `upload_direction` (
//	`seq` INT NOT NULL,
//	`order_seq` INT NULL,
//	`pack` INT NULL,
//	`type` VARCHAR(50) NULL,
//	`truck_num` VARCHAR(50) NULL,
//	`driver` VARCHAR(50) NULL,
//	`driver_tel` VARCHAR(50) NULL,
//	`trans_price` INT NULL,
//	`etc` VARCHAR(50) NULL,
//	PRIMARY KEY (`seq`)
//) ENGINE=MyISAM DEFAULT CHARSET=utf8;
//
//


class class_DN00 extends class_top {


	function get_query()
    {
    	global $date_type, $start_date, $end_date, $query_type, $query_str, $shop_id;
    	
    	
    	$query = "SELECT  a.seq order_seq
    					, a.pack
        				, a.shop_id
        				, a.qty
        				, a.order_name
        				, a.recv_address
        				, date(a.trans_date) trans_date
        				, time(a.trans_date) trans_time
        				, b.seq
        				, b.no
        				, b.trans_price
        				, b.upload_type
        				, b.driver
        				, b.truck_num
        				, b.crworker
        				, b.etc
        				
        			FROM  orders a
        				, upload_direction b
        		   WHERE  a.seq = b.order_seq
        		   	 AND  a.$date_type >='$start_date 00:00:00' 
		    		 AND  a.$date_type <='$end_date 23:59:59' 
        		   
        		   ";
        		   
        		   
		if($query_str > '')
		{
			if($query_type =="order_recv_name")
				$query .=	"AND   (a.order_name LIKE '%$query_str%' OR a.recv_name LIKE '%$query_str%')";
			else if($query_type =="upload_type")
				$query .=	"AND   (b.$query_type LIKE '%$query_str%') ";
			else
				$query .=	"AND   (a.$query_type LIKE '%$query_str%') ";
		}
		
		if($shop_id)
			$query .=	"AND   a.shop_id = '$shop_id' ";
			
		return $query;
    }

    function DN00()
    {
		global $template					;
		global $connect						;
		global $line_per_page 				;
		
		global $date_type, $start_date, $end_date, $query_type, $query_str, $shop_id, $page;
		
		
		$line_per_page = 50;
		
		// 페이지
        if( !$page )
        {	
        	$page = 1;
        	$date_type = "trans_date";
            $start_date = date("Y-m-d");
            $end_date	= date("Y-m-d");
            
            $_REQUEST[page] 		= $page;
            $_REQUEST[start_date] 	= $start_date;
            $_REQUEST[end_date]		= $end_date;
        }
        
        // link url
        $par = array('template', 'start_date', 'end_date', 'date_type', 'query_type', 'query_str','shop_id');
        $link_url = $this->build_link_url3( $par );      
        
        
        $query = $this->get_query();
debug($query);
        
        
		$result = mysql_query($query, $connect);        
        $total_rows = mysql_num_rows( $result );


		$tot_qty = 0;
		$tot_trans_price = 0;
		//총 합계를 위한 쿼리..
	    while($data = mysql_fetch_assoc($result))
	    {
	    	$qty_seq = $data[order_seq		];
	    	if($data[pack])
	    		$qty_seq = $data[pack];
	    	
	    	$_query = "SELECT SUM(b.qty) qty FROM orders a, order_products b WHERE a.seq = b.order_seq AND (a.seq = '$qty_seq' OR a.pack = '$qty_seq')";
			$_result = mysql_query($_query, $connect);
	    	$_data = mysql_fetch_assoc($_result);
	    	
	    	$tot_qty += $_data[qty];
	    	$tot_trans_price += $data[trans_price];
		}
		
		//페이지별 리스트
        $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
		$result = mysql_query($query, $connect);

        $master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";
    }
	function DN01()
    {
    	global $template;
		global $connect;
		global $seq, $order_seq, $type;
		
        $query = "SELECT  a.seq order_seq
        				 ,b.seq
        				 ,b.pack
						 ,b.no
						 ,b.upload_type
						 ,b.truck_num
						 ,b.driver
						 ,b.driver_tel
						 ,b.trans_price
						 ,b.etc
						 ,a.order_id
						 ,a.qty
						 ,a.order_name
						 ,a.order_tel
						 ,a.order_mobile
						 ,a.recv_name
						 ,a.recv_tel
						 ,a.recv_mobile
						 ,a.recv_address
						 ,a.memo
						 ,date(a.trans_date) trans_date
						 ,a.status
						 ,a.trans_corp
						
					FROM  orders a
						, upload_direction b
				   WHERE  a.seq = b.order_seq
				   AND    a.seq = $order_seq
		   ";
		//CS창에서는 상차지시서(upload_direction)와 별개로 orders 내용만 출력함.
		if($type == "E900")   
	        $query = "SELECT  a.seq order_seq
	        				 ,a.pack
							 ,a.order_id
							 ,a.qty
							 ,a.order_name
							 ,a.order_tel
							 ,a.order_mobile
							 ,a.recv_name
							 ,a.recv_tel
							 ,a.recv_mobile
							 ,a.recv_address
							 ,a.memo
							 ,date(a.trans_date) trans_date
							 ,a.status
							 ,a.trans_corp
							
						FROM  orders a
					   WHERE  a.seq = $order_seq
			   ";
			   
		$result = mysql_query($query, $connect);
		$sheet_data = mysql_fetch_assoc($result);
		
		$query = "SELECT a.order_id, c.name, c.location,  b.qty FROM orders a, order_products b, products c WHERE a.seq = b.order_seq AND b.product_id = c.product_id ";
		//합포인가? 합포라면
		if($sheet_data[pack])
			$query .= "AND a.pack = $sheet_data[pack]";
		else
			$query .= "AND a.seq = $sheet_data[order_seq]";
			
		$query .= " ORDER BY REPLACE(SUBSTRING_INDEX(c.location,'-',1),'B',99) * 1
							,SUBSTRING_INDEX(SUBSTRING_INDEX(c.location,'-',2),'-',-1) * 1
							,SUBSTRING_INDEX(c.location,'-',-1) * 1
		
		
		 ";
debug($query);
		$result = mysql_query($query, $connect);
		$tot_qty = 0;
		$cnt_order_id = 0;
		while($data = mysql_fetch_assoc($result))
		{
			$tot_qty += $data[qty];
			$order_id[$data[order_id]] = 1;
			$data_arr[] = $data;
		}
		$cnt_order_id = count($order_id);
		$order_id_str = "";
		foreach($order_id as $key => $val)
		{
			$order_id_str.= $key."\n";
		}
		$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";
    }
    function DN02()
    {
    	global $template;
		global $connect;
		global $seq, $order_seq, $type;
		
		
    	$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";
    }
    
    function sheet_save()
    {
    	global $template;
		global $connect;
		
		global $seq,$no,$upload_type,$truck_num,$driver,$driver_tel,$trans_price,$etc,$trans_date;
		$arr[error] = 0;


		$query = "SELECT count(*) cnt FROM orders a, upload_direction b WHERE a.seq = b.order_seq AND date(trans_date) = '$trans_date' AND b.no = $no AND b.seq NOT IN ($seq)";
		$result = mysql_query($query, $connect);
		$cnt_data = mysql_fetch_assoc($result);
		
debug($query);
		//같은 차수가 있음
		if($cnt_data[cnt])
		{
			$arr[error] = 1;
			echo json_encode( $arr );
			return;	
		}

		$query = "UPDATE upload_direction SET
						 no          ='$no'
						,upload_type ='$upload_type'
						,truck_num   ='$truck_num'
						,driver      ='$driver'
						,driver_tel  ='$driver_tel'
						,trans_price ='$trans_price'
						,etc         ='$etc'
				  WHERE seq			='$seq'
		";
		
		$result = mysql_query($query, $connect);
		
		echo json_encode( $arr );
    }
    function save_file_DN00()
    {
    	global $template					;
		global $connect						;
		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	,	$stat_state	;
		global $shop_id		;
	
		$query = $this->get_query();
        $result = mysql_query($query, $connect);
        
        $n = 0;
        $old_time = time();
        $total_rows = mysql_num_rows( $result );
        
        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result))
        {
        	
        	$qty_seq = $data[order_seq		];
	    	if($data[pack])
	    		$qty_seq = $data[pack];
	    	
	    	$_query = "SELECT SUM(b.qty) qty FROM orders a, order_products b WHERE a.seq = b.order_seq AND (a.seq = '$qty_seq' OR a.pack = '$qty_seq')";
			$_result = mysql_query($_query, $connect);
	    	$_data = mysql_fetch_assoc($_result);
		    	
		    	
			$arr_temp = array();	
			$arr_temp['관리번호'	]= $data[order_seq];
			$arr_temp['날짜'		]= $data[trans_date];
			$arr_temp['차수'		]= $data[no];
			$arr_temp['출하시간'	]= $data[trans_time];
			$arr_temp['판매처'		]= class_C::get_shop_name($data['shop_id']);
			$arr_temp['주문자'		]= $data[order_name];
			$arr_temp['수량'		]= $_data[qty];
			$arr_temp['운임'		]= $data[trans_price];
			$arr_temp['상차구분'	]= $data[upload_type];
			$arr_temp['기사명'		]= $data[driver];
			$arr_temp['차량번호'	]= $data[truck_num];
			$arr_temp['비고'		]= $data[etc];
			$arr_temp['주소'		]= $data[recv_address];
			$arr_temp['출하담당자'	]= $data[crworker];

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
        $this->make_file_DN00( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }
	function make_file_DN00( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
    	global $template					;
		global $connect						;
		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	,	$stat_state	;
		global $shop_id		;
		
		$saveTarget = _upload_dir . $filename;
		
    	// file open
        $handle = fopen ($saveTarget, "w");
        
        $buffer = $this->default_header;
        
        fwrite($handle, $buffer);
        $buffer = "<tr>\n";
			$buffer .="<td class=header_item>관리번호	</td>";
			$buffer .="<td class=header_item>날짜		</td>";
			$buffer .="<td class=header_item>차수		</td>";
			$buffer .="<td class=header_item>출하시간	</td>";
			$buffer .="<td class=header_item>판매처		</td>";
			$buffer .="<td class=header_item>주문자		</td>";
			$buffer .="<td class=header_item>수량		</td>";
			$buffer .="<td class=header_item>운임		</td>";
			$buffer .="<td class=header_item>상차구분	</td>";
			$buffer .="<td class=header_item>기사명		</td>";
			$buffer .="<td class=header_item>차량번호	</td>";
			$buffer .="<td class=header_item>비고		</td>";
			$buffer .="<td class=header_item>주소		</td>";			
			$buffer .="<td class=header_item>출하담당자	</td>";
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

    function download_DN00()
    {
        global $filename;
        global $start_date	,	$end_date	;
        $obj = new class_file();        
        $obj->download_file( $filename, $start_date."~".$end_date."_upload_dir_file.xls");
    } 
}

?>
