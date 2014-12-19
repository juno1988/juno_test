<? 
require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_supply.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_lock.php";
include "class_C.php";

class class_FU00 extends class_top
{	
	function get_select_query($function="")
	{
		global $start_date	,	$end_date	;
		global $date_type	;
		global $shop_id		;
		global $query_type	;
		global $query_str	;
		global $modify_type	;
		
		if($function=="sum")
		{
			$query = "SELECT	sum(modify_price) tot_modify_price
						FROM	revenue_modify
					   WHERE	$date_type >='$start_date 00:00:00'
						 AND	$date_type <='$end_date 23:59:59' ";
		}
		else 
		{
			$query = "SELECT	* 
						FROM	revenue_modify
					   WHERE	$date_type >='$start_date 00:00:00'
						 AND	$date_type <='$end_date 23:59:59' ";	
		}
		if($query_str > '')		
			$query .=	"AND	$query_type = '$query_str' ";
			
		if($shop_id > '')		
			$query .=	"AND	shop_id = '$shop_id' ";
		
		if($modify_type == 'modify_etc')
			$query .=	"AND	order_type = '기타' ";
			
		if($modify_type == 'modify_custom')
			$query .=	"AND	order_type NOT IN ('기타','할인','반품','원단','조정')";
			
		if($modify_type == 'modify_discount')
			$query .=	"AND	order_type = '할인' ";
		
		if($modify_type == 'modify_return')
			$query .=	"AND	order_type = '반품' ";
		
		if($modify_type == 'modify_fabric')
			$query .=	"AND	order_type = '원단' ";
		
		if($modify_type == 'modify_')
			$query .=	"AND	order_type = '조정' ";	
			
		$query .= "ORDER BY modify_date DESC, seq DESC";
		
		return $query;		
	}
	function FU00()
	{
		global $template					;
		global $connect						;		
		global $start_date	,	$end_date	;
		global $date_type	;
		global $shop_id		;
		global $query_type	;
		global $query_str	;
		global $modify_type	;
		
		global $page		,	$line_per_page,		$link_url;		
		
		$query_str = trim( $query_str );
		$line_per_page = 50;

        if( !$page )
        {	
        	$page = 1;
            $start_date = date("Y-m-01");
            $end_date	= date("Y-m-d");
            $date_type	= "modify_date";
            $modify_type= "modify_return";
            $query_str	= "";
            $shop_id 	= "";
            
            $_REQUEST[page] 		= $page;
            $_REQUEST[start_date] 	= $start_date;
            $_REQUEST[end_date]		= $end_date;
            $_REQUEST[date_type]	= $date_type;
            $_REQUEST[modify_type]	= $modify_type;
            $_REQUEST[query_str]	= $query_str;            
            $_REQUEST[shop_id] 		= $shop_id;
        }
        
		// link url
        $par = array('template', 'start_date', 'end_date', 'date_type', 'query_type', 'query_str','shop_id','modify_type');
        $link_url = $this->build_link_url3( $par );      
		
		$query = $this->get_select_query();
		
		$result = mysql_query($query, $connect);
		$total_rows = mysql_num_rows( $result );

        $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
		$result = mysql_query($query, $connect);		
		
		$tot_query = $this->get_select_query("sum");		
		$tot_result = mysql_query($tot_query, $connect);
		$tot_data = mysql_fetch_assoc($tot_result); 
		
		$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";
	}
	
	function save_file_FU00()
    {
		global $template					;
		global $connect						;		
		global $start_date	,	$end_date	;
		global $date_type	;
		global $shop_id		;
		global $query_type	;
		global $query_str	;
		global $modify_type	;

		$query = $this->get_select_query();		
        $result = mysql_query($query, $connect);
        
        $n = 0;
        $old_time = time();
        $total_rows = mysql_num_rows( $result );
        
        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result))
        {
        	$arr_temp = array();			
			$arr_temp['주문번호'] = $data['order_id'		];
			$arr_temp['주문순번'] = $data['order_id_seq'	];
			$arr_temp['고객명'	] = $data['order_name'		];
			$arr_temp['판매처'	] = class_C::get_shop_name($data['shop_id']);
			$arr_temp['조정구분'] = $data['order_type'		];
			$arr_temp['조정일자'] = $data['modify_date'		];
			$arr_temp['배송일'	] = $data['trans_date_pos'	];
			$arr_temp['조정액'	] = $data['modify_price'	];
			$arr_temp['메모'	] = $data['memo'			];
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
        $this->make_file_FU00( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
	}
	
	function make_file_FU00( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
		global $template					;
		global $connect						;		
		global $start_date	,	$end_date	;
		global $date_type	;
		global $shop_id		;
		global $query_type	;
		global $query_str	;
		global $modify_type	;
		
		$saveTarget = _upload_dir . $filename;
		
    	// file open
        $handle = fopen ($saveTarget, "w");
        
        $buffer = $this->default_header;
        
        fwrite($handle, $buffer);
        $buffer = "<tr>\n";
			$buffer .="<td class=header_item>	주문번호   	</td>";
			$buffer .="<td class=header_item>	주문순번   	</td>";
			$buffer .="<td class=header_item>	고객명  	</td>";
			$buffer .="<td class=header_item>	판매처  	</td>";
			$buffer .="<td class=header_item>	조정구분   	</td>";
			$buffer .="<td class=header_item>	조정일자    	</td>";
			$buffer .="<td class=header_item>	배송일   	</td>";
			$buffer .="<td class=header_item>	조정액  	</td>";
			$buffer .="<td class=header_item>	메모 	</td>";
        $buffer .= "</tr>\n";
        
        fwrite($handle, $buffer);
        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";
            foreach( $val as $key => $v )
            {
            	if($key == '조정액')
            		$buffer .= "<td class=num_item>$v</td>\n";
				else
                	$buffer .= "<td class=str_item>$v</td>\n";
            }
            $buffer .= "</tr>\n";
            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");
        fclose($handle);

        return $filename; 
    }
    
    function download_FU00()
    {
        global $filename;
        global $start_date	,	$end_date	;
        
        $obj = new class_file();        
        $obj->download_file( $filename, $start_date."~".$end_date."_modify_file.xls");
    } 
    
	function FU01()
	{
		global $template					;
		global $connect						;
		
		$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";
	}
	
	function file_upload()
	{
		global $template;		
		global $connect;
		
		global $file_location;
		global $new_file_name;
		
		global $start_date;
		global $shop_id;

		global $serial_no;
		$serial_no = date("Ymd_His");
		$transfer_date = $start_date;
		
		//TEMP TABLE TRUNCATE
		$query = "TRUNCATE TABLE revenue_modify_update";
		$result = mysql_query($query, $connect);
		
		
	    $file_params = pathinfo($_FILES['_file']['name']);
        $file_ext = strtoupper($file_params["extension"]);
		$new_file_name = "revenue-" . $serial_no. "_.".$file_ext;		
		$obj = new class_file();
		$arr_data =	$obj->upload("", true, $new_file_name);
		
		
		$start_row;
		$start_row = 0;
		$colum_name	=array();
		
		$excel_type;
		if(	trim($arr_data[0][0]) == "차 수" )
		{
			$excel_type="발주";
		}
		else if(trim($arr_data[0][0]) == "원단업체")
		{	
			$excel_type="원단";
		}


		
		for($i=$start_row ; $i < $start_row + 1 ; $i++)
		{
			for($j=0 ; $j<50 ; $j++)
			{
				if($excel_type=="발주")
				{
					switch(trim($arr_data[$i][$j]))
					{
						case "차 수"	:$colum_name['차수'		]	= $j; break;
						case "대분류"	:$colum_name['대분류'	]	= $j; break;
						case "상 호"	:$colum_name['상호'		]	= $j; break;
						case "매출액"	:$colum_name['매출액'	]	= $j; break;
					}
				}
				else if($excel_type=="원단")
				{
					switch(trim($arr_data[$i][$j]))
					{
						case "계"	:$colum_name['계'		]	= $j; break;
					}
				}
			}
		}
		
		//업로드 파일 체크
		$upload_check = true;
		$excel_data_row = count($arr_data);
		
		for($i = $start_row + 1 ; $i < $excel_data_row ; $i++)
		{
			if($excel_type=="발주")
			{	
				if(	trim($arr_data[$i][$colum_name['차수'		]]) =="" ||
					trim($arr_data[$i][$colum_name['대분류'		]]) =="" ||
					trim($arr_data[$i][$colum_name['상호'		]]) =="" ||
					trim($arr_data[$i][$colum_name['매출액'		]]) =="" )
				{
					$upload_check=false;
				}
			}
			else if($excel_type=="원단")
			{	
				if(	trim($arr_data[$i][$colum_name['계'		]]) =="")
				{
					$upload_check=false;
				}
			}
		}
		if($upload_check == false)
		{
			echo "<script>
					alert('파일형식이 잘못 되었거나 잘못된 데이터가 있습니다.')
				  </script>";
		}
		else
		{
			if($excel_type=="발주")
			{
				for($i = $start_row + 1 ; $i < $excel_data_row ; $i++)
				{
						$trans_year	 = "20".substr(trim($arr_data[$i][$colum_name['차수']			]),0,2);
						$trans_month = 		substr(trim($arr_data[$i][$colum_name['차수']			]),2,2);
						$trans_day   = 		substr(trim($arr_data[$i][$colum_name['차수']			]),4,2);
						
							$_data	=	array(
									order_id			=>	trim($arr_data[$i][$colum_name['차수']			])
								,	trans_date_pos		=>	$trans_year."-".$trans_month."-".$trans_day
								,	order_type			=>	trim($arr_data[$i][$colum_name['대분류']		])
								,	order_name			=>	trim($arr_data[$i][$colum_name['상호']			])
								,	modify_price		=>	trim($arr_data[$i][$colum_name['매출액']		])
							);
							
					//TEMP DB에 삽입. 
					$query="INSERT	INTO	revenue_modify_update
									 SET	order_id				='$_data[order_id]'		,
											order_name				='$_data[order_name]'	,
											trans_date_pos			='$_data[trans_date_pos]'	,
											order_type				='$_data[order_type]'	,
											modify_date				='$transfer_date'		,
											modify_price			='$_data[modify_price]'	,
											serial_no				='$serial_no'
											";
											
					$result	=mysql_query($query,$connect);
				}
			}
			else if($excel_type=="원단")
			{
				for($i = $excel_data_row - 2 ; $i < $excel_data_row - 1 ; $i++)
				{
							$_data	=	array(
									order_id			=>	date("YmdHis")
								,	order_type			=>	'원단'
								,	order_name			=>	''
								,	modify_price		=>	trim($arr_data[$i][$colum_name['계']]) + trim($arr_data[$i+1][$colum_name['계']])
							);
							
					//TEMP DB에 삽입. 
					$query="INSERT	INTO	revenue_modify_update
									 SET	order_id				='$_data[order_id]'		,
											order_name				='$_data[order_name]'	,
											order_type				='$_data[order_type]'	,
											modify_date				='$transfer_date'		,
											modify_price			='$_data[modify_price]'	,
											serial_no				='$serial_no'
											";										
											
					$result	=mysql_query($query,$connect);
				}
			}
		}
		//값 INSERT 후 보여줌
		$query="SELECT * from revenue_modify_update";
		$result	=mysql_query($query,$connect);
				
		$master_code=substr($template,0,1);
		include	"template/"	.$master_code."/"	.$template	.".htm";		
	}
	
	
	//업로드값 적용
	function data_upload()
	{
		global $template	;
		global $connect		;
		global $filename	;
		global $memo		;
		global $new_file_name;
				
		global $transfer_date;
		global $start_date;
		
		global $serial_no;
		
		$login_id = $_SESSION[LOGIN_ID];
		$domain = _DOMAIN_;
		$addr = $_SERVER[REMOTE_ADDR];
		
		
		$query="SELECT * from revenue_modify_update";				 
		$result = mysql_query($query, $connect);
		
		//TEMP 값을 DB에 INSERT. 
		while( $data = mysql_fetch_assoc($result))
	    {
	    	if($data[order_type] =="원단")
	    	{
	    		$insert_query = " INSERT INTO stat_month 
	    								 SET 	crdate		  = '$data[modify_date]'
	    								 ,		fabric_price  = $data[modify_price]
	    								 ON DUPLICATE KEY UPDATE fabric_price = fabric_price + $data[modify_price]
	    								 ";
	    	}
	    	else if($data[order_type] =="기타")
	    	{
	    		$insert_query = " INSERT INTO stat_month 
	    								 SET 	crdate		  = '$data[trans_date_pos]'
	    								 ,		etc_box_price = $data[modify_price]
	    								 ON DUPLICATE KEY UPDATE etc_box_price = etc_box_price + $data[modify_price]
	    								 ";
	    	}
	    	else
	    	{
	    		$insert_query = " INSERT INTO stat_month 
	    								 SET 	crdate			= '$data[trans_date_pos]'
	    								 ,		order_box_price	= $data[modify_price]
	    								 ON DUPLICATE KEY UPDATE order_box_price = order_box_price + $data[modify_price]
	    								 ";
	    	}
	    	mysql_query($insert_query, $connect);
	    	
    		$insert_query = " INSERT INTO revenue_modify 
    								 SET 	order_id		= '$data[order_id]'
										,	order_name		= '$data[order_name]'
										,	order_type		= '$data[order_type]'
										,	modify_date		= '$data[modify_date]'
										,	trans_date_pos	= '$data[trans_date_pos]'
										,	modify_price   	=  $data[modify_price]
										,	memo			= '$memo'    		
										,	serial_no		= '$data[serial_no]'
										";
			mysql_query($insert_query, $connect);
		}
		
		$query="SELECT sum(modify_price) s_modify_price from revenue_modify_update";				 
		$result = mysql_query($query, $connect);
		$data = mysql_fetch_assoc($result);
		
		
		$query="INSERT INTO revenue_modify_log SET
							crworker	 = '$login_id'
							,crdate		 =  now()
							,work_type	 = '업로드'
							,ip			 = '$addr'
							,memo		 = '$memo'
							,modify_date = '$start_date'
							,old_fn		 = '$filename'
							,new_fn		 = '$new_file_name'
							,modify_price=  $data[s_modify_price]
							,serial_no	 =  '$serial_no'";
		$result = mysql_query($query, $connect);
		
		//값 삽입후 TEMP TABLE TRUNCATE
		$truncate_query = "TRUNCATE TABLE revenue_modify_update";
		mysql_query($truncate_query, $connect);
		
		echo "<script>
				window.close();
				opener.location.reload();
			  </script>";
				  
		$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";		
	}

	
	function revenue_delete()
	{
		global $template					;
		global $connect						;
		global $serial_no					;
		
		$query="SELECT * FROM  revenue_modify WHERE serial_no = '$serial_no'";
		$result = mysql_query($query, $connect);
		while($data = mysql_fetch_assoc($result))
		{
			if($data[order_type] =="조정")
			{
				switch ($data[shop_id])
				{
					case 10001:
						$_query = "UPDATE stat_month SET auction_sales_price = auction_sales_price - $data[modify_price] WHERE crdate = '$data[modify_date]'";
			 			$_result = mysql_query($_query, $connect);
					break;
					case 10002:
						$_query = "UPDATE stat_month SET gmarket_sales_price = gmarket_sales_price - $data[modify_price] WHERE crdate = '$data[modify_date]'";
			 			$_result = mysql_query($_query, $connect);					
					break;
					case 10050:
						$_query = "UPDATE stat_month SET elevenst_sales_price = elevenst_sales_price - $data[modify_price] WHERE crdate = '$data[modify_date]'";
			 			$_result = mysql_query($_query, $connect);					
					break;
					case 10058:
						$_query = "UPDATE stat_month SET homepage_sales_price = homepage_sales_price - $data[modify_price] WHERE crdate = '$data[modify_date]'";
			 			$_result = mysql_query($_query, $connect);					
					break;
				}
			}
			else if($data[order_type] =="원단")
			{
			 	$_query = "UPDATE stat_month SET fabric_price = fabric_price - $data[modify_price] WHERE crdate = '$data[modify_date]'";
			 	$_result = mysql_query($_query, $connect);
			}
			else if($data[order_type] =="주문 1" || $data[order_type] =="주문 2" ||  $data[order_type] =="오프라인" || $data[order_type] =="주문제작" )
			{
				if($data[order_type] =="주문제작")
					$data[trans_date_pos] = $data[modify_date];
					
				$_query = "UPDATE stat_month SET order_box_price = order_box_price - $data[modify_price] WHERE crdate = '$data[trans_date_pos]'";
			 	$_result = mysql_query($_query, $connect);
				
			}
			else if($data[order_type] =="기타")
			{
				if($data[trans_date_pos] == "")
					$data[trans_date_pos] = $data[modify_date];
				$_query = "UPDATE stat_month SET etc_box_price = etc_box_price - $data[modify_price] WHERE crdate = '$data[trans_date_pos]'";
			 	$_result = mysql_query($_query, $connect);				
			}
			if($_result)
			{
				$_query = "DELETE FROM revenue_modify WHERE seq = '$data[seq]'";
				$_result = mysql_query($_query, $connect);
			}
		}
		
		$login_id = $_SESSION[LOGIN_ID];
		$domain = _DOMAIN_;
		$addr = $_SERVER[REMOTE_ADDR];
		
		$query="UPDATE revenue_modify_log
				   SET delete_ip = '$addr'
				   	 , delete_worker = '$login_id'
				   	 , delete_date = now()
				    WHERE serial_no = '$serial_no'";
		$result = mysql_query($query, $connect);
		
		$query = "SELECT * FROM revenue_modify_log order by seq DESC";
		$result = mysql_query($query, $connect);
		
		
		$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";
	}
	
	function FU02()
	{
		global $template					;
		global $connect						;
		
		$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";
	}
	function date_modify()
	{
		global $template					;
		global $connect						;
		global $start_date, $shop_id, $modify_price, $modify_memo, $modify_type	;
		
		$serial_no = date("Ymd_His");
		
		$login_id = $_SESSION[LOGIN_ID];
		$domain = _DOMAIN_;
		$addr = $_SERVER[REMOTE_ADDR];	
		
		if($modify_type == "조정" && $shop_id == 0)
		{
			echo "<script>alert('판매처를 선택하세요');</script>";
		}	
		else if($modify_type != "조정" && $shop_id > 0)
		{
			echo "<script>alert('판매처 선택불가');</script>";
		}
		else
		{
			$query="INSERT INTO revenue_modify_log
							SET crworker	= '$login_id'
							  , crdate		=  now()
							  , work_type	= '$modify_type'
							  , ip			= '$addr'
							  , modify_price= '$modify_price'
							  , modify_date = '$start_date'
							  , memo		= '$modify_memo'
							  , serial_no	= '$serial_no' ";
			$result = mysql_query($query, $connect);
			

			$order_id =  date("YmdHis");
			$query = "INSERT INTO revenue_modify SET 
								  order_id		=  $order_id
								  ,order_type	= '$modify_type'
								  ,order_name	= ''
								  ,modify_date	= '$start_date'
								  ,modify_price	=  $modify_price
								  ,shop_id		= '$shop_id'
								  ,memo			= '$modify_memo'
								  ,serial_no	= '$serial_no' ";
						
			mysql_query($query, $connect);
			
			switch($modify_type)
			{
				case '조정':
					switch($shop_id)
					{
						case 10001:
							$query = "INSERT INTO stat_month SET crdate = '$start_date', auction_sales_price = $modify_price ON DUPLICATE KEY UPDATE  auction_sales_price = auction_sales_price + $modify_price";
						break;	
						case 10002:
							$query = "INSERT INTO stat_month SET crdate = '$start_date', gmarket_sales_price = $modify_price ON DUPLICATE KEY UPDATE gmarket_sales_price= gmarket_sales_price + $modify_price ";
						break;
						case 10050:
							$query = "INSERT INTO stat_month SET crdate = '$start_date', elevenst_sales_price = $modify_price ON DUPLICATE KEY UPDATE elevenst_sales_price = elevenst_sales_price + $modify_price";
						break;
						case 10058:
							$query = "INSERT INTO stat_month SET crdate = '$start_date', homepage_sales_price = $modify_price ON DUPLICATE KEY UPDATE homepage_sales_price = homepage_sales_price + $modify_price";
						break;
					}
				break;
				case '주문제작':
					$query = "INSERT INTO stat_month SET crdate = '$start_date', order_box_price = $modify_price ON DUPLICATE KEY UPDATE  order_box_price = order_box_price + $modify_price";
				break;
				case '기타':
					$query = "INSERT INTO stat_month SET crdate = '$start_date', etc_box_price = $modify_price ON DUPLICATE KEY UPDATE etc_box_price = etc_box_price + $modify_price";
				break;
				case '원단':
					$query = "INSERT INTO stat_month SET crdate = '$start_date', fabric_price = $modify_price ON DUPLICATE KEY UPDATE  fabric_price = fabric_price + $modify_price";
				break;
			}

			mysql_query($query, $connect);

			echo "<script>
					window.close();
					opener.location.reload();
				  </script>";
		}
		
		$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";
	}
	
	function FU03()
	{
		global $template					;
		global $connect						;
		
		$query = "SELECT * FROM revenue_modify_log order by seq DESC";
		$result = mysql_query($query, $connect);
		
		$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";
	}
	
	function FU04()
	{
		global $template					;
		global $connect						;
		global $seq;
		
		$query = "SELECT * FROM revenue_modify where seq = $seq";
		$result = mysql_query($query, $connect);
		
		$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";
	}
	function memo_modify()
	{
		global $template					;
		global $connect						;
		global $seq;
		global $memo;
		
		$query = "UPDATE revenue_modify SET memo = '$memo' where seq = $seq";
		$result = mysql_query($query, $connect);
		
		echo "<script>
				window.close();
				opener.location.reload();				
			  </script>";
	}
}
?>