<? 
require_once "class_top.php";
require_once "class_product.php";
require_once "class_stock.php";
require_once "class_supply.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_lock.php";
include "class_C.php";

class class_FR00 extends class_top
{
	
	function get_option()
	{
		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	,	$stat_state	;
		global $n_stat		,	$_stat		;
		global $shop_id		,	$pay_type	;
		global $query_view					;
				
		
		if($stat_state == 'sel_in_stat')//기간내 정산이 안된건.
			$query_option .=	"AND    a.transfer_date > '$end_date 00:00:00'";
			
		$query_option = $this->get_select_query_option($query_option);
		
		if($stat_state == 'sel_in_stat')//모든기간 미정산
		{
			$query_option .=	"OR 	( a.$date_type >='$start_date 00:00:00' 
		    			 		AND    	  a.$date_type <='$end_date 23:59:59'
		    			 		AND 	  a.stat_check='N'";			
 			$query_option = $this->get_select_query_option($query_option);
 		}
			
		$query_option .= "	ORDER BY   a.trans_date_pos, a.order_id_seq, a.order_id, a.seq_id";		
		

		
		
		return $query_option;			
	}
		
	function get_select_query()
	{
		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	,	$stat_state	;
		global $n_stat		,	$_stat		;
		global $shop_id		,	$pay_type	;
		global $query_view					;
				
		$query = "    SELECT        a.seq                   a_seq             
							   ,	a.order_id              a_order_id        
							   ,	a.order_id_seq          a_order_id_seq
							   ,	a.seq_id				a_seq_id
							   ,	a.trans_date_pos        a_trans_date_pos  
							   ,	a.shop_id               a_shop_id         
							   ,	a.order_name            a_order_name      
							   ,	a.qty                   a_qty             
							   ,	a.amount                a_amount          
							   ,	a.discount_price        a_discount_price  
							   ,	a.service_price         a_service_price   
							   ,	a.supply_price          a_supply_price    
							   ,	a.order_date            a_order_date      
							   ,	a.collect_date          a_collect_date    
							   ,	a.transfer_date         a_transfer_date   
							   ,	a.transfer_price        a_transfer_price  
							   ,	a.stat_check            a_stat_check      
							   ,	a.pay_type              a_pay_type        
							   ,	a.row_span              a_row_span        
							   ,	a.charge              	a_charge        "
							   ;

			$query .="  FROM    shop_stat_upload a 
				  	   WHERE    (a.$date_type >='$start_date 00:00:00' 
		    			 AND    a.$date_type <='$end_date 23:59:59' ";	

		
		
		return $query;			
	}
	function get_select_query_option($query)
	{
		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	,	$stat_state	;
		global $n_stat		,	$_stat		;
		global $shop_id		,	$pay_type	;
		global $query_view					;
		
						  		    
		if($query_str > '')
		{
			if($query_type =="order_recv_name")			
				$query .=	"AND   (a.order_name = '$query_str' or a.recv_name = '$query_str')";
			else
				$query .=	"AND   (a.$query_type like '%$query_str%') ";
		}
			
		if($stat_state != 'all' && $stat_state != 'sel_in_stat' && $stat_state != 'trouble_stat')
			$query .=	"AND    a.stat_check = '$stat_state'";
		
		if($stat_state == 'trouble_stat')//정산액 불일치
			$query .=	"AND    a.transfer_price != a.supply_price
						 AND 	a.stat_check='Y'
						 AND 	a.row_span > 0 ";	
			
		if($pay_type =='acount' && $shop_id == 10058)
			$query .=	"AND    a.pay_type = '계좌이체'";
			
		if($pay_type =='credit' && $shop_id == 10058)
			$query .=	"AND    a.pay_type = '신용카드'";
		
		if($pay_type =='deposit' && $shop_id == 10058)
			$query .=	"AND    a.pay_type =  '무통장'";
			
		if($pay_type =='discount' && $shop_id == 10058)
			$query .=	"AND    a.pay_type =  '전액할인'";
		
		if($shop_id > '')		
			$query .=	"AND    a.shop_id = '$shop_id' ";
			
		$query .=	")";


		
		
		return $query;			
	}
	function FR00()
	{
		global $template					;
		global $connect						;
		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	,	$stat_state	;
		global $n_stat		,	$_stat		;
		global $shop_id		,	$pay_type	;
		global $page		,	$line_per_page,		$link_url;
		global $query_view					;
		
		$query_str = trim( $query_str );
		$line_per_page = 50;

        // 페이지
        if( !$page )
        {	
        	$page = 1;
            $start_date = "0000-00-00";
            $end_date	= date("Y-m-d");
            $date_type	= "trans_date_pos";
            $query_type = "order_id";
            $query_str	= "";
            $pay_type	= "all";
            $stat_state = "N";
            $n_stat 	= "";
            $_stat 		= "";
            $shop_id 	= "";
            
            $_REQUEST[page] 		= $page;
            $_REQUEST[start_date] 	= $start_date;
            $_REQUEST[end_date]		= $end_date;
            $_REQUEST[date_type]	= $date_type;
            $_REQUEST[query_type] 	= $query_type;
            $_REQUEST[query_str]	= $query_str;
            $_REQUEST[pay_type]		= $pay_type;
            $_REQUEST[stat_state] 	= $stat_state;
            $_REQUEST[n_stat] 		= $n_stat;
            $_REQUEST[_stat] 		= $_stat;
            $_REQUEST[shop_id] 		= $shop_id;
        }
        if($date_type == "transfer_date")
        	$stat_state = "Y";
        	
        if($pay_type != "all")
        	 $shop_id 	= 10058;
        	 
		if($shop_id != 10058)
			$pay_type = "all";

		// link url
        $par = array('template', 'start_date', 'end_date', 'date_type', 'query_type', 'query_str', 'pay_type', 'stat_state','n_stat','_stat','shop_id');
        $link_url = $this->build_link_url3( $par );      
		
		$query = $this->get_select_query();
		$query .= $this->get_option();

		if($_SESSION[LOGIN_LEVEL] == 9 && $query_view=='on')
		{			
			echo $query."<br><br>";
		}
		
		$result = mysql_query($query, $connect);
		$total_rows = mysql_num_rows( $result );

        $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
		$result = mysql_query($query, $connect);
		
		
		$data_arr = array();
		while( $data = mysql_fetch_assoc($result))
		{
			$data_arr[] = $data;
		}
		
		
		$query="SELECT	sum(qty) s_qty
						,sum(discount_price) s_discount_price
						,sum(amount) s_amount
						,sum(amount) - sum(supply_price) s_re_price
						,sum(supply_price) s_supply_price
						,sum(transfer_price) s_transfer_price
						,count(seq)	c_seq
				FROM	shop_stat_upload a
			   WHERE	($date_type >='$start_date 00:00:00'
				 AND	$date_type <='$end_date 23:59:59' ";

		$query .= $this->get_option();			

		$result_sum	=mysql_query($query,$connect);
		$data_sum = mysql_fetch_assoc($result_sum);
		
		$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";
	}
	function save_file_FR00()
    {
    	global $template					;
		global $connect						;
		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	,	$stat_state	;				
		global $n_stat		,	$_stat		;
		global $shop_id		,	$pay_type	;

		$query = $this->get_select_query();
		$query .= $this->get_option();
		
        $result = mysql_query($query, $connect);
        
        $n = 0;
        $old_time = time();
        $total_rows = mysql_num_rows( $result );
        
        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result))
        {
        	
			if($data['a_shop_id'] ==10058)
			{
				$salse_price = 0;
				$total_salse_price = $data['a_amount']+$data['a_discount_price'];			
			}
			else	
			{
				$salse_price = ($data['a_amount']+$data['a_discount_price'])/$data['a_qty'];
				$total_salse_price = $salse_price * $data[a_qty];			
			}
			
			$data['a_charge'] = str_replace(",","",$data['a_charge']);
			if($data['a_shop_id'] =='10058')
			{
				$data['a_charge'] = (($data['a_amount']-$data['a_supply_price'])/$data['a_amount'])*100;
			}
			else if($data['a_charge'] && $data['a_shop_id'] =='10002')
			{
				$data['a_charge'] = $data['a_charge'] / $total_salse_price * 100;
			}
			else if($data['a_shop_id'] =='10001')
			{
				if($data['a_collect_date'] >= '2014-04-29')
					$data['a_charge'] = round((($total_salse_price * 0.08)/$data['a_amount']) * 100);
				else 
					$data['a_charge'] = round(((($total_salse_price - $data['a_discount_price'])*0.08)/$data['a_amount']) * 100);
			}
			
        	$arr_temp = array();			
			$arr_temp['출하일'			] = $data['a_trans_date_pos'	];
			$arr_temp['판매처'			] = class_C::get_shop_name($data['a_shop_id'			]);
			$arr_temp['주문번호'		] = $data['a_order_id'		];
			$arr_temp['주문순번'		] = $data['a_order_id_seq'	];
			$arr_temp['고객명'			] = $data['a_order_name'		];
			$arr_temp['수량'			] = number_format($data['a_qty'				]);
			$arr_temp['판매가'			] = $salse_price;
			$arr_temp['판매금액'		] = $total_salse_price;
			$arr_temp['판매자할인'		] = number_format($data['a_discount_price'	]);
			if($data['a_shop_id'] == 10058)
			{
				
				$code_query="SELECT seq, pack, order_id, code15, code16, code17, code18
				 			   FROM	orders
							  WHERE	order_id = '$data[a_order_id]'
							    AND order_cs NOT IN (1, 3)
							    AND shop_id IN (10058)
						   
						   ";
				$code_val = array();
				$code_result = mysql_query($code_query,$connect);				
				while($code_data   = mysql_fetch_assoc($code_result))
				{
						
					if($code_data[pack]) // += : 같은 합포내 주문 더함
					{
						$code_val[code15] = $code_data[code15];
						$code_val[code16] = $code_data[code16];
						$code_val[code17] += $code_data[code17];
						$code_val[code18] = $code_data[code18];
						
					}
					else // += : 다른 합포끼리 더함
					{
						$code_val[code15] = $code_data[code15];
						$code_val[code16] += $code_data[code16];
						$code_val[code17] += $code_data[code17];
						$code_val[code18] = $code_data[code18];
						
					}
				}
				
						   
				//code15, if(pack > 0 ,code16 , sum(code16)) code16, code17, code18
//				$code_query="SELECT	sum(code17) code17, code15, if(pack > 0 ,code16 , sum(code16)) code16, code18	
//							   FROM	orders
//							  WHERE	order_id = '$data[a_order_id]'
//							    AND order_cs NOT IN (1, 3)
//							    AND shop_id IN (10058)
//						   GROUP BY order_id";
//
//				$code_result = mysql_query($code_query,$connect);
//				$code_data   = mysql_fetch_assoc($code_result);
//				
//				$arr_temp['쿠폰 사용금액'	] = number_format($code_data['code15'	]);
//				$arr_temp['적립금 사용금액'	] = number_format($code_data['code16'	]);
//				$arr_temp['회원할인액'		] = number_format($code_data['code17'	]);
//				$arr_temp['에누리 할인금액'	] = number_format($code_data['code18'	]);
				
				$arr_temp['쿠폰 사용금액'	] = number_format($code_val['code15'	]);
				$arr_temp['적립금 사용금액'	] = number_format($code_val['code16'	]);
				$arr_temp['회원할인액'		] = number_format($code_val['code17'	]);
				$arr_temp['에누리 할인금액'	] = number_format($code_val['code18'	]);
			}
			else
			{
				$arr_temp['쿠폰 사용금액'	] = 0;
				$arr_temp['적립금 사용금액'	] = 0;
				$arr_temp['회원할인액'		] = 0;
				$arr_temp['에누리 할인금액'	] = 0;
			}
			$arr_temp['매출계'			] = number_format($data['a_amount'			]);
			$arr_temp['공제금액'		] = number_format($data['a_amount'			]-$data['a_supply_price'		]);
			$arr_temp['정산예정액'		] = number_format($data['a_supply_price'		]);
			$arr_temp['은행입금일'		] = $data['a_transfer_date'	];
			$arr_temp['은행입금액'		] = number_format($data['a_transfer_price']);
			
			$arr_temp['수수료율'		] = sprintf("%2.2f" ,$data['a_charge'])."%";
			//$arr_temp['수수료율'		] = sprintf("%2.2f" ,(($data['a_amount']-$data['a_supply_price'])/$data['a_amount'])*100)."%";
			$arr_temp['결재수단'		] = $data['a_pay_type'	];
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
        $this->make_file_FR00( $arr_datas, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
	}

    function make_file_FR00( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
    {
    	global $template					;
		global $connect						;
		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	,	$stat_state	;				
		global $n_stat		,	$_stat		;
		global $shop_id		;
		
		$saveTarget = _upload_dir . $filename;
		
    	// file open
        $handle = fopen ($saveTarget, "w");
        
        $buffer = $this->default_header;
        
        fwrite($handle, $buffer);
        $buffer = "<tr>\n";
			$buffer .="<td class=header_item>	출하일   	</td>";
			$buffer .="<td class=header_item>	판매처   	</td>";
			$buffer .="<td class=header_item>	주문번호  	</td>";
			$buffer .="<td class=header_item>	주문순번  	</td>";
			$buffer .="<td class=header_item>	고객명   	</td>";
			$buffer .="<td class=header_item>	수량    	</td>";
			$buffer .="<td class=header_item>	판매가   	</td>";
			$buffer .="<td class=header_item>	판매금액  	</td>";
			$buffer .="<td class=header_item>	판매자할인 	</td>";
			$buffer .="<td class=header_item>	쿠폰사용금액	</td>";
			$buffer .="<td class=header_item>	적립금사용금액 	</td>";
			$buffer .="<td class=header_item>	회원할인액 		</td>";
			$buffer .="<td class=header_item>	에누리할인금액 	</td>";
			$buffer .="<td class=header_item>	매출계   	</td>";
			$buffer .="<td class=header_item>	공제금액  	</td>";
			$buffer .="<td class=header_item>	정산예정액	</td>";
			$buffer .="<td class=header_item>	은행입금일	</td>";
			$buffer .="<td class=header_item>	은행입금액	</td>";
			$buffer .="<td class=header_item>	수수료율  	</td>";
			$buffer .="<td class=header_item>	결재수단  	</td>";
        $buffer .= "</tr>\n";
        			
        fwrite($handle, $buffer);
        foreach( $arr_datas as $val )
        {
            $buffer = "<tr>\n";
            foreach( $val as $key => $v )
            {
            	if($key == '수량'|| $key == '판매가'||$key == '판매금액'||$key == '판매자할인'||$key == '쿠폰 사용금액'||$key == '적립금 사용금액'||$key == '회원할인액'||$key == '에누리 할인금액'||$key == '매출계'||$key == '공제금액'||$key == '정산예정액'||$key == '은행입금액')
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

    function download_FR00()
    {
        global $filename;
        global $start_date	,	$end_date	;
        $obj = new class_file();        
        $obj->download_file( $filename, $start_date."~".$end_date."_stat_file.xls");
    } 
    
    
	function FR01()
	{
		global $template;
		global $connect	;
		global $_file	;
		global $start_date;

		$query = "TRUNCATE TABLE shop_stat_upload_temp";
		$result = mysql_query($query, $connect);
		$data = mysql_fetch_assoc($result);

		$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";
	}

	function file_upload()
	{
		global $template;		
		global $connect;
		global $file_location;
		global $start_date;
		
		global $shop_id;
				
		$transfer_date = $start_date;
		$upload_shop_id = $shop_id;
		//TEMP TABLE TRUNCATE
		$query = "TRUNCATE TABLE shop_stat_upload_temp";
		$result = mysql_query($query, $connect);
		
		
		//SHOP_ID 검색
		if($shop_id)
		{
			$query="SELECT			A.url
					  		FROM	shopinfo A
					  		WHERE	A.shop_id	= $shop_id";
			$result	=mysql_query($query,$connect);
			$data = mysql_fetch_assoc($result);		
			$shop_url = $data[url];
		}
		else
		{
			$shop_url = "deposit";
		}
		
		$obj = new class_file();
		$arr_data =	$obj->upload();		
		
		$start_row;
		$colum_name	=array();
		
		switch($shop_url)		
		{
			//입력 파일이 고도몰 경우 아래와 같은 컬럼명으로 INDEX 색인
			case"www.box4u.co.kr/":
				$start_row = 11;
				for($i=$start_row ; $i < $start_row + 1 ; $i++)
				{
					for($j=0 ; $j<50 ; $j++)
					{
						switch(trim($arr_data[$i][$j]))
						{
							case "승인일자" :$colum_name['승인일자'	]	= $j; break;
							case "취소일자" :$colum_name['취소일자'	]	= $j; break;
							case "상태" 	:$colum_name['상태'		]	= $j; break;
							case "주문번호" :$colum_name['주문번호'	]	= $j; break;
							case "주문자" 	:$colum_name['주문자'	]	= $j; break;
							case "결제수단" :$colum_name['결제수단'	]	= $j; break;
							case "카드사" 	:$colum_name['카드사'	]	= $j; break;
							case "승인금액" :$colum_name['승인금액'	]	= $j; break;
							case "수수료" 	:$colum_name['수수료'	]	= $j; break;
							case "매입금액" :$colum_name['매입금액'	]	= $j; break;
							case "할부" 	:$colum_name['할부'		] 	= $j; break;
							case "반송" 	:$colum_name['반송'		]	= $j; break;
							case "거래" 	:$colum_name['거래'		] 	= $j; break;
						}	
					}
				}
				
				//취소주문은 상단표에 ROW 가 증가함.
				//따라서 검사후 start_row 를 증가 시킴.
				if( $colum_name['승인일자'	]== 0 && 
					$colum_name['취소일자'	]== 0 && 
					$colum_name['상태'		]== 0 && 
					$colum_name['주문번호'	]== 0 && 
					$colum_name['주문자'	]== 0 && 
					$colum_name['결제수단'	]== 0 && 
					$colum_name['카드사'	]== 0 && 
					$colum_name['승인금액'	]== 0 && 
					$colum_name['수수료'	]== 0 && 
					$colum_name['매입금액'	]== 0 && 
					$colum_name['할부'		]== 0 && 
					$colum_name['반송'		]== 0 && 
					$colum_name['거래'		]== 0 )
				{
					$start_row++;
					for($i=$start_row ; $i < $start_row + 1 ; $i++)
					{
						for($j=0 ; $j<50 ; $j++)
						{
							switch(trim($arr_data[$i][$j]))
							{
								case "승인일자" :$colum_name['승인일자'	]	= $j; break;
								case "취소일자" :$colum_name['취소일자'	]	= $j; break;
								case "상태" 	:$colum_name['상태'		]	= $j; break;
								case "주문번호" :$colum_name['주문번호'	]	= $j; break;
								case "주문자" 	:$colum_name['주문자'	]	= $j; break;
								case "결제수단" :$colum_name['결제수단'	]	= $j; break;
								case "카드사" 	:$colum_name['카드사'	]	= $j; break;
								case "승인금액" :$colum_name['승인금액'	]	= $j; break;
								case "수수료" 	:$colum_name['수수료'	]	= $j; break;
								case "매입금액" :$colum_name['매입금액'	]	= $j; break;
								case "할부" 	:$colum_name['할부'		] 	= $j; break;
								case "반송" 	:$colum_name['반송'		]	= $j; break;
								case "거래" 	:$colum_name['거래'		] 	= $j; break;
							}	
						}
					}
				}
			break;
			
			//입력 파일이 고도몰 무통장 경우 아래와 같은 컬럼명으로 INDEX 색인
			case"deposit":
				$start_row = 0;
				for($i=$start_row ; $i < $start_row + 1 ; $i++)
				{
					for($j=0 ; $j<50 ; $j++)
					{
						switch(trim($arr_data[$i][$j]))
						{
							case "수금일" 	:$colum_name['수금일'	]	= $j; break;
							case "결제기관" :$colum_name['결제기관'	]	= $j; break;
							case "입금자" 	:$colum_name['입금자'	]	= $j; break;
							case "주문번호" :$colum_name['주문번호'	]	= $j; break;
							case "홈피예정" :$colum_name['홈피예정'	]	= $j; break;
							case "은행입금" :$colum_name['은행입금'	]	= $j; break;
						}

					}
				}
			break;		
			//입력 파일이 옥션인 경우 아래와 같은 컬럼명으로 INDEX 색인
			case"www.auction.co.kr":
				$start_row = 0;
				for($i=$start_row ; $i < $start_row + 1 ; $i++)
				{
					for($j=0 ; $j<50 ; $j++)
					{
						switch(trim($arr_data[$i][$j]))
						{
							case"구분"      			:$colum_name['구분'			] = $j;break; 
							case"발송일자"      		:$colum_name['발송일자'		] = $j;break; 
							case"송금일자"      		:$colum_name['송금일자'		] = $j;break; 
							case"구매결정일"      		:$colum_name['구매결정일'	] = $j;break; 
							case"결제번호"      		:$colum_name['결제번호'		] = $j;break; 
							case"상품번호"      		:$colum_name['상품번호'		] = $j;break; 
							case"주문번호"      		:$colum_name['주문번호'		] = $j;break; 
							case"상품명"      			:$colum_name['상품명'		] = $j;break; 
							case"구매자ID"      		:$colum_name['구매자ID'		] = $j;break; 
							case"구매자명"      		:$colum_name['구매자명'		] = $j;break; 
							case"상품금액"      		:$colum_name['상품금액'		] = $j;break; 
							case"선결제 배송비"      	:$colum_name['선결제 배송비'] = $j;break; 
							case"반품 교환 배송비"      :$colum_name['반품 교환 배송비'] = $j;break; 
							case"송금액"      			:$colum_name['송금액'		] = $j;break; 
						}	
					}
				}
			break;
			
			//입력 파일이 G마켓인 경우 아래와 같은 컬럼명으로 INDEX 색인
			case "www.gmarket.co.kr": 
				$start_row = 0;
				for($i=$start_row ; $i < $start_row + 1 ; $i++)
				{
					for($j=0 ; $j<50 ; $j++)
					{
						switch(trim($arr_data[$i][$j]))
						{
							//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
							//G마켓 정산다운로드 엑셀파일
							//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++	

							case"주문번호"      		:$colum_name['주문번호'] = $j;break; 
							case"상품번호"     			:$colum_name['상품번호'] = $j;break; 
							case"장바구니번호"  		:$colum_name['장바구니번호'] = $j;break; 
							case"상품명"     		 	:$colum_name['상품명'] = $j;break; 
							case"구매자명"    		  	:$colum_name['구매자명'] = $j;break; 
							case"입금확인일"    	  	:$colum_name['입금확인일'] = $j;break; 
							case"배송완료일"  		    :$colum_name['배송완료일'] = $j;break; 
							case"환불일"      			:$colum_name['환불일'] = $j;break; 
							case"구매결정일"   	   		:$colum_name['구매결정일'] = $j;break; 
							case"정산방식"     		 	:$colum_name['정산방식'] = $j;break; 
							case"체결수량"      		:$colum_name['체결수량'] = $j;break; 
							case"구매자결제금"  	    :$colum_name['구매자결제금'] = $j;break; 
							case"구매대금"      		:$colum_name['구매대금'] = $j;break; 
							case"상품공급원가"  	    :$colum_name['상품공급원가'] = $j;break; 
							case"공제/환급금"  	 	   	:$colum_name['공제/환급금'] = $j;break; 
							case"송금액"      			:$colum_name['송금액'] = $j;break; 


						}	
					}
				}
			break;
			//입력 파일이 11번가인 경우 아래와 같은 컬럼명으로 INDEX 색인
			case "www.11st.co.kr": 
				$start_row = 9;
				for($i=$start_row ; $i < $start_row+1 ; $i++)
				{
					for($j=0 ; $j<50 ; $j++)
					{
						switch(trim($arr_data[$i][$j]))
						{
							//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
							//11번가 정산다운로드 엑셀파일
							//+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
							
							case"주문번호"      			:$colum_name['주문번호']				= $j;break; 
							case"주문순번"      			:$colum_name['주문순번']				= $j;break; 
							case"구매자명"      			:$colum_name['구매자명']				= $j;break; 
							case"상품번호"          	 	:$colum_name['상품번호']				= $j;break;
							case"상품명"           	 		:$colum_name['상품명']					= $j;break;
							case"[정산]판매금액"      		:$colum_name['[정산]판매금액'] 			= $j;break; 
							case"[공제]할인쿠폰이용료"      :$colum_name['[공제]할인쿠폰이용료']	= $j;break; 
							case"구매확정일"    			:$colum_name['구매확정일']				= $j;break; 
							case"송금액"					:$colum_name['송금액'] 					= $j;break; 
							case"[공제]서비스이용료"		:$colum_name['[공제]서비스이용료'] 		= $j;break; 
							case"공제금액"      			:$colum_name['공제금액'] 				= $j;break;							 
							case"결제완료일"      			:$colum_name['결제완료일']				= $j;break; 
							case"구매확정일"      			:$colum_name['구매확정일']				= $j;break; 
							case"발송완료일"      			:$colum_name['발송완료일']				= $j;break; 
							case"배송완료일"          	 	:$colum_name['배송완료일']				= $j;break;
							case"구매확정일"           	 	:$colum_name['구매확정일']				= $j;break;
							case"[정산]선결제배송비"   	 	:$colum_name['[정산]선결제배송비']		= $j;break;
						}
					}
				}	
			break;
		}
		
		//업로드 파일 체크
		$upload_check = true;
		if($shop_url == "www.box4u.co.kr/")
		{
			$excel_data_row = count($arr_data) - 1;
			$start_row++;
		}
		else
			$excel_data_row = count($arr_data);
		
		
		for($i = $start_row + 1 ; $i < $excel_data_row ; $i++)
		{
			switch($shop_url)
			{
				case "www.box4u.co.kr/":
						if(
								trim($arr_data[$i][$colum_name['상태'		]]) =="" ||
								trim($arr_data[$i][$colum_name['주문번호'	]]) =="" ||
								trim($arr_data[$i][$colum_name['주문자'		]]) =="" ||
								trim($arr_data[$i][$colum_name['결제수단'	]]) =="" ||
								trim($arr_data[$i][$colum_name['카드사'		]]) =="" ||
								trim($arr_data[$i][$colum_name['승인금액'	]]) =="" ||
								trim($arr_data[$i][$colum_name['수수료'		]]) =="" ||
								trim($arr_data[$i][$colum_name['매입금액'	]]) ==""
							)
							{
								$upload_check=false;
							}
				break;
				case"deposit":
						 if(
								trim($arr_data[$i][$colum_name['수금일'		]]) =="" ||
								trim($arr_data[$i][$colum_name['결제기관'	]]) =="" ||
								trim($arr_data[$i][$colum_name['입금자'		]]) =="" ||
								trim($arr_data[$i][$colum_name['주문번호'	]]) =="" ||
								trim($arr_data[$i][$colum_name['홈피예정'	]]) =="" ||
								trim($arr_data[$i][$colum_name['은행입금'	]]) =="" 
							)
							{
								$upload_check=false;
							}

				break;
									
				case "www.auction.co.kr":
						if(
								trim($arr_data[$i][$colum_name['구분'				]]) =="" ||
								trim($arr_data[$i][$colum_name['발송일자'			]]) =="" ||
								trim($arr_data[$i][$colum_name['송금일자'			]]) =="" ||
								trim($arr_data[$i][$colum_name['구매결정일'			]]) =="" ||
								trim($arr_data[$i][$colum_name['결제번호'			]]) =="" ||
								trim($arr_data[$i][$colum_name['상품번호'			]]) =="" ||
								trim($arr_data[$i][$colum_name['주문번호'			]]) =="" ||
								trim($arr_data[$i][$colum_name['상품명'				]]) =="" ||
								trim($arr_data[$i][$colum_name['구매자ID'			]]) =="" ||
								trim($arr_data[$i][$colum_name['구매자명'			]]) =="" ||
								trim($arr_data[$i][$colum_name['상품금액'			]]) =="" ||
								trim($arr_data[$i][$colum_name['선결제 배송비'		]]) =="" ||
								trim($arr_data[$i][$colum_name['반품 교환 배송비'	]]) =="" ||
								trim($arr_data[$i][$colum_name['송금액'				]]) ==""
							)	
							{
								$upload_check=false;
							}

				break;
							
							//trim($arr_data[$i][$colum_name['정산방식']		]) =="" || 지마켓 정산방식은 데이터 없어도 업로드
				case "www.gmarket.co.kr": 
						if(	
							trim($arr_data[$i][$colum_name['주문번호']		]) =="" ||
							trim($arr_data[$i][$colum_name['상품번호']		]) =="" ||
							trim($arr_data[$i][$colum_name['장바구니번호']	]) =="" ||
							trim($arr_data[$i][$colum_name['상품명']		]) =="" ||
                    		trim($arr_data[$i][$colum_name['구매자명']		]) =="" ||
							trim($arr_data[$i][$colum_name['입금확인일']	]) =="" ||
							trim($arr_data[$i][$colum_name['배송완료일']	]) =="" ||
							trim($arr_data[$i][$colum_name['구매결정일']	]) =="" ||							
							trim($arr_data[$i][$colum_name['체결수량']		]) =="" ||
							trim($arr_data[$i][$colum_name['구매자결제금']	]) =="" ||
							trim($arr_data[$i][$colum_name['구매대금']		]) =="" ||
							trim($arr_data[$i][$colum_name['상품공급원가']	]) =="" ||
							trim($arr_data[$i][$colum_name['공제/환급금']	]) =="" ||
							trim($arr_data[$i][$colum_name['송금액']		]) ==""
							)
							{
								$upload_check=false;
							}
				break;
				case "www.11st.co.kr":				
					$service = explode("(",$arr_data[$i][$colum_name['서비스이용료(%)']]);
						if(	
							trim($arr_data[$i][$colum_name['주문번호'			]	]) =="" ||
							trim($arr_data[$i][$colum_name['주문순번'			]	]) =="" ||
							trim($arr_data[$i][$colum_name['구매자명'			]	]) =="" ||
							trim($arr_data[$i][$colum_name['상품명'				]	]) =="" ||
							trim($arr_data[$i][$colum_name['상품번호'			]	]) =="" ||
							trim($arr_data[$i][$colum_name['[정산]판매금액'		]	]) =="" ||
							trim($arr_data[$i][$colum_name['[공제]할인쿠폰이용료']	]) =="" ||
							trim($arr_data[$i][$colum_name['구매확정일'			]	]) =="" ||
							trim($arr_data[$i][$colum_name['송금액'				]	]) =="" ||
							trim($arr_data[$i][$colum_name['[공제]서비스이용료'	]	]) =="" ||
							trim($arr_data[$i][$colum_name['공제금액'			]	]) ==""	)
							{
								$upload_check=false;
							}
				break;
				default:
					{
						$upload_check=false;
					}
			}
		}
		if($upload_check == false)
		{
			echo "<script>
					alert('파일형식이 잘못 되었거나 잘못된 데이터가 있습니다.\n (ex : 헤더명 변경이나 빈데이터가 있을경우)')
				  </script>";
		}
		else
		{		
			for($i = $start_row + 1 ; $i < $excel_data_row ; $i++)
			{
				switch($shop_url)
				{
					case "www.box4u.co.kr/":
						$excel_date_data = trim($arr_data[$i][$colum_name['승인일자']]);
						$year	= substr($excel_date_data, 0, 4);
						$month	= substr($excel_date_data, 4, 2);
						$day	= substr($excel_date_data, 6, 2);
						$excel_date_data = $year."-".$month."-".$day;
						
						if(trim($arr_data[$i][$colum_name['상태']		])=="취소")
						{
							$unique_seq = date("YmdHis");
							if(trim($arr_data[$i][$colum_name['결제수단']		])=="신용카드")
							{
								$_data	=	array(
										order_id		=>	trim($arr_data[$i][$colum_name['주문번호']])
									,	order_name		=>	preg_replace ("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i", "", trim($arr_data[$i][$colum_name['주문자']		]))
									,	order_date		=>	$excel_date_data
									,	confirm_date	=>	$excel_date_data
									,	service_price	=>	(trim($arr_data[$i][$colum_name['수수료']		]) + floor((trim($arr_data[$i][$colum_name['수수료']		]) * 0.1)))* -1
									,	transfer_price	=>	(trim($arr_data[$i][$colum_name['매입금액']		]) - floor((trim($arr_data[$i][$colum_name['수수료']		]) * 0.1)))* -1
									,	supply_price	=>	trim($arr_data[$i][$colum_name['승인금액']		])
								);
							}
							else
							{
								$_data	=	array(
										order_id		=>	trim($arr_data[$i][$colum_name['주문번호']		])
									,	order_name		=>	preg_replace ("/[ \'\"`|]/i", "", trim($arr_data[$i][$colum_name['주문자']		]))
									,	order_date		=>	$excel_date_data
									,	confirm_date	=>	$excel_date_data
									,	service_price	=>	(trim($arr_data[$i][$colum_name['수수료']		]) + round((trim($arr_data[$i][$colum_name['수수료']		]) * 0.1)))* -1
									,	transfer_price	=>	(trim($arr_data[$i][$colum_name['매입금액']		]) - round((trim($arr_data[$i][$colum_name['수수료']		]) * 0.1)))* -1
									,	supply_price	=>	trim($arr_data[$i][$colum_name['승인금액']		])
								);
							}
						}
						else
						{
							if(trim($arr_data[$i][$colum_name['결제수단']		])=="신용카드")
							{
								$_data	=	array(
										order_id		=>	trim($arr_data[$i][$colum_name['주문번호']		])
									,	order_name		=>	preg_replace ("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i", "", trim($arr_data[$i][$colum_name['주문자']		]))
									,	order_date		=>	$excel_date_data
									,	confirm_date	=>	$excel_date_data
									,	service_price	=>	trim($arr_data[$i][$colum_name['수수료']		]) + floor((trim($arr_data[$i][$colum_name['수수료']		]) * 0.1))
									,	transfer_price	=>	trim($arr_data[$i][$colum_name['매입금액']		]) - floor((trim($arr_data[$i][$colum_name['수수료']		]) * 0.1))
									,	supply_price	=>	trim($arr_data[$i][$colum_name['승인금액']		])
								);
							}
							else
							{
								$_data	=	array(
										order_id		=>	trim($arr_data[$i][$colum_name['주문번호']		])
									,	order_name		=>	preg_replace ("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i", "", trim($arr_data[$i][$colum_name['주문자']		]))
									,	order_date		=>	$excel_date_data
									,	confirm_date	=>	$excel_date_data
									,	service_price	=>	trim($arr_data[$i][$colum_name['수수료']		]) + round((trim($arr_data[$i][$colum_name['수수료']		]) * 0.1))
									,	transfer_price	=>	trim($arr_data[$i][$colum_name['매입금액']		]) - round((trim($arr_data[$i][$colum_name['수수료']		]) * 0.1))
									,	supply_price	=>	trim($arr_data[$i][$colum_name['승인금액']		])
								);
							}
						}
						
					break;
					
					case "deposit":
						$_data	=	array(
								order_id			=>	trim($arr_data[$i][$colum_name['주문번호']		])
							,	order_name			=>	preg_replace ("/[ \\\'\"`|]/i", "", trim($arr_data[$i][$colum_name['입금자']		]))
							,	bank_name			=>	preg_replace ("/[ \\\'\"`|]/i", "", trim($arr_data[$i][$colum_name['결제기관']		]))
							,	transfer_date		=>	gmdate("Y-m-d", ($arr_data[$i][$colum_name['수금일']] - 25569) * 86400)
							,	transfer_price		=>	trim($arr_data[$i][$colum_name['은행입금']		])
						);
						
					break;
					
					case "www.auction.co.kr":
						$_data	=	array(
								order_id			=>	trim($arr_data[$i][$colum_name['주문번호']		])
							,	product_id			=>	trim($arr_data[$i][$colum_name['상품번호']		])
							,	order_name			=>	preg_replace ("/[ \\\'\"`|]/i", "", trim($arr_data[$i][$colum_name['구매자명']		]))
							,	product_name		=>	trim($arr_data[$i][$colum_name['상품명']		])
							,	confirm_date		=>	gmdate("Y-m-d", ($arr_data[$i][$colum_name['구매결정일']] - 25569) * 86400)
							,	stat_date			=>	gmdate("Y-m-d", ($arr_data[$i][$colum_name['송금일자']] - 25569) * 86400)
							,	trans_start_date	=>	gmdate("Y-m-d", ($arr_data[$i][$colum_name['발송일자']] - 25569) * 86400)
							,	supply_price		=>	trim($arr_data[$i][$colum_name['상품금액']		])
							,	before_trans_price	=>	trim($arr_data[$i][$colum_name['선결제 배송비']	])
							,	transfer_price		=>	trim($arr_data[$i][$colum_name['송금액']		])
						);
						
						/*
							// 엑셀 날자 연산
			                $EXCEL_DATE = $order[order_date];
			                $UNIX_DATE = ($EXCEL_DATE - 25569) * 86400;
			                
			                $order_date = gmdate("Y-m-d", ($EXCEL_DATE - 25569) * 86400);
			                $order_time = gmdate("H:i:s", $UNIX_DATE);
		                */
					break;
					
					//G마켓 정산액 계산
					case "www.gmarket.co.kr":
					
						$_data	=	array(					
									order_id				=>	trim($arr_data[$i][$colum_name[주문번호]		])
								,	order_id_no				=>	''
								,	product_id				=>	trim($arr_data[$i][$colum_name[상품번호]		])
								,	order_name				=>	preg_replace ("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i", "", trim($arr_data[$i][$colum_name['구매자명']		]))
								,	product_name			=>	trim($arr_data[$i][$colum_name[상품명]			])
								,	confirm_date			=>	substr(trim($arr_data[$i][$colum_name[구매결정일]		]),0,10)
								,	trans_finish_date		=>	substr(trim($arr_data[$i][$colum_name[배송완료일]		]),0,10)
								,	stat_date				=>	substr(trim($arr_data[$i][$colum_name[정산완료일]		]),0,10)
								,	supply_price			=>	trim($arr_data[$i][$colum_name[상품공급원가]	])
								,	refund_price			=>	(trim($arr_data[$i][$colum_name['공제/환급금']		])*-1)
								,	transfer_price			=>	trim($arr_data[$i][$colum_name[송금액]			])
						);
						
					break;
					//11번가 정산액 계산
					case "www.11st.co.kr":
						$_data	=	array(					
									order_id				=>	trim($arr_data[$i][$colum_name['주문번호']		])
								,	order_id_no				=>	trim($arr_data[$i][$colum_name['주문순번']		])
								,	order_name				=>	preg_replace ("/[ #\&\+\-%@=\/\\\:;,\.'\"\^`~\_|\!\?\*$#<>()\[\]\{\}]/i", "", trim($arr_data[$i][$colum_name['구매자명']		]))
								,	product_id				=>	trim($arr_data[$i][$colum_name['상품번호']		])
								,	product_name			=>	trim($arr_data[$i][$colum_name['상품명']		])
								,	supply_price			=>	trim($arr_data[$i][$colum_name['[정산]판매금액']])
								,	discount_price			=>	trim($arr_data[$i][$colum_name['[공제]할인쿠폰이용료']])
								,	confirm_date			=>	trim(str_replace("/","-", $arr_data[$i][$colum_name['구매확정일']		]))
								,	transfer_price			=>	trim($arr_data[$i][$colum_name['송금액']					])
								,	service_price			=>	trim($arr_data[$i][$colum_name['[공제]서비스이용료']		])
								,	refund_price			=>	trim($arr_data[$i][$colum_name['공제금액']					])
								,	order_date				=>	trim(str_replace("/","-", $arr_data[$i][$colum_name['구매확정일']		]))
								,	trans_start_date		=>	trim(str_replace("/","-", $arr_data[$i][$colum_name['발송완료일']		]))
								,	trans_finish_date		=>	trim(str_replace("/","-", $arr_data[$i][$colum_name['배송완료일']		]))							
								,	stat_date				=>	trim(str_replace("/","-", $arr_data[$i][$colum_name['구매확정일']		]))
								,	before_trans_price		=>	trim($arr_data[$i][$colum_name['[정산]선결제배송비']])
						);
					break;
				}
				if(($shop_url == "deposit" && ($_data[bank_name] == "국민은행" || $_data[bank_name] == "기업은행")) || $shop_url != "deposit")
				{
					if($shop_url == "deposit")				
						$transfer_date = $_data[transfer_date];
					
					//TEMP DB에 삽입. 
					$query="INSERT	INTO	shop_stat_upload_temp
									 SET	order_id				='$_data[order_id]'	,
									 		order_id_no				='$_data[order_id_no]'	,
											product_id				='$_data[product_id]'	,
											order_name				='$_data[order_name]'	,
											product_name			='$_data[product_name]'	,
											product_count			='$_data[product_count]',
											confirm_date			='$_data[confirm_date]'	,
											order_date				='$_data[order_date]'	,	
											trans_start_date		='$_data[trans_start_date]'	,
											trans_finish_date		='$_data[trans_finish_date]',
											stat_date				='$transfer_date'			,
											supply_price			='$_data[supply_price]'		,
											discount_price			='$_data[discount_price]'	,
											service_price			='$_data[service_price]'	,
											refund_price			='$_data[refund_price]'		,
											transfer_price			='$_data[transfer_price]'		,
											before_trans_price		='$_data[before_trans_price]'	,
											shop_id					='$shop_id'						,										
											data_check				= (SELECT IF(count(order_id>=1),IF(stat_check='Y',1,0),2) FROM shop_stat_upload WHERE order_id = '$_data[order_id]' AND order_id_seq = '$_data[order_id_no]')
											";				
					$result	=mysql_query($query,$connect);
				}
			}
		}
		
		//값 INSERT 후 보여줌
		$query="SELECT		 A.seq					a_seq				,
							 A.order_id				a_order_id			,
							 A.order_id_no			a_order_id_no		,
							 A.product_id			a_product_id		,
							 A.order_name			a_order_name		,
							 A.product_name			a_product_name		,
							 A.product_count		a_product_count		,
							 A.confirm_date			a_confirm_date		,
							 A.order_date			a_order_date		,
							 A.trans_start_date		a_trans_start_date	,
							 A.trans_finish_date	a_trans_finish_date	,
							 A.stat_date			a_stat_date			,
							 A.supply_price			a_supply_price	    ,
							 A.discount_price		a_discount_price	,
							 A.service_price		a_service_price		,
							 A.refund_price			a_refund_price	    ,
							 A.transfer_price		a_transfer_price	,
							 A.SHOP_ID				a_shop_id			,
							 A.before_trans_price	a_before_trans_price,
							if((A.before_trans_price > 0 )AND (A.transfer_price <= A.before_trans_price) AND (A.data_check != 2), -1, A.data_check) AS a_data_check
 						FROM shop_stat_upload_temp A
 					ORDER BY A.seq
				";
				
		$result	=mysql_query($query,$connect);
		
		$query="SELECT		 sum(transfer_price) as sum
							,count(transfer_price) as count
 						FROM shop_stat_upload_temp
 					   WHERE data_check < 2
				";	
		$result_sum	=mysql_query($query,$connect);
		$data_sum = mysql_fetch_assoc($result_sum);

		$master_code=substr($template,0,1);
		include	"template/"	.$master_code."/"	.$template	.".htm";		
	}
	
	
	//업로드값 적용
	function data_upload()
	{
		global $template	;
		global $connect		;
		global $filename	;
				
		global $transfer_date;
		global $upload_shop_id;
		
		
		$login_id = $_SESSION[LOGIN_ID];
		$domain = _DOMAIN_;
		$addr = $_SERVER[REMOTE_ADDR];


// 차수 최대값 읽기		
		$log_query="SELECT MAX(upload_seq)+1 AS MAX_seq FROM shop_stat_upload_log WHERE transfer_date = '$transfer_date' AND shop_id='$upload_shop_id' GROUP BY transfer_date";
		$log_result = mysql_query($log_query, $connect);
		$log_data = mysql_fetch_assoc($log_result);
		
		if(!$log_data[MAX_seq])
			$log_data[MAX_seq] = 1;
			
		$log_query="INSERT INTO shop_stat_upload_log VALUES('','$login_id',now(),'$filename','$domain','$addr','$upload_shop_id','$transfer_date','$log_data[MAX_seq]','','')";
		$log_result = mysql_query($log_query, $connect);
		
		
		$query="SELECT		 A.seq					a_seq				,   
							 A.order_id				a_order_id			,		
							 A.order_id_no			a_order_id_no		,		
							 A.product_id			a_product_id		,		
							 A.order_name			a_order_name		,		
							 A.product_name			a_product_name		,		
							 A.product_count		a_product_count		,	    	
							 A.confirm_date			a_confirm_date		,		
							 A.order_date			a_order_date		,		
							 A.trans_start_date		a_trans_start_date	,		
							 A.trans_finish_date	a_trans_finish_date	,		
							 A.stat_date			a_stat_date			,		
							 A.supply_price			a_supply_price	    ,	    	
							 A.discount_price		a_discount_price	,	    	
							 A.service_price		a_service_price		,	    	
							 A.refund_price			a_refund_price	    ,	    	
							 A.transfer_price		a_transfer_price	,       
							 A.SHOP_ID				a_shop_id			,
							 A.data_check           a_data_check        ,
							 A.before_trans_price	a_before_trans_price
 						FROM shop_stat_upload_temp A
					   WHERE data_check < 2
					ORDER BY a_seq
				 ";	
		
		
		$result = mysql_query($query, $connect);
		//TEMP 값을 DB에 INSERT. 
		while( $data = mysql_fetch_assoc($result))
	    {	    	
	    	if($data[a_transfer_price] >= 0)
	    	{
		    	if($data[a_before_trans_price] > 0 && $data[a_supply_price] == 0)
		    	{
			    	$insert_query = "UPDATE	shop_stat_upload
											SET		transfer_date		    = '$data[a_stat_date]'  		,
													transfer_price		    =  (SELECT * FROM (SELECT transfer_price FROM shop_stat_upload WHERE order_id= '$data[a_order_id]' AND order_id_seq = '$data[a_order_id_no]') AS TEMP) + '$data[a_before_trans_price]',
													before_trans_price	    = '$data[a_before_trans_price]'	,
													stat_check				= 'Y'							,
													upload_seq				= '$log_data[MAX_seq]'
											WHERE	order_id				= '$data[a_order_id]'
											  AND	order_id_seq			= '$data[a_order_id_no]'
													";
		    	}
		    	else if($data[a_before_trans_price] == 0 && $data[a_supply_price] > 0)
		    	{
		    		$insert_query = "UPDATE	shop_stat_upload
											SET		transfer_date			= '$data[a_stat_date]'  	,
													transfer_price		    = (SELECT * FROM (SELECT IF(before_trans_price,before_trans_price,'0') FROM shop_stat_upload WHERE order_id= '$data[a_order_id]' AND order_id_seq = '$data[a_order_id_no]') AS TEMP) + '$data[a_transfer_price]',												
													stat_check				= 'Y'						,
													upload_seq				= '$log_data[MAX_seq]'
											WHERE	order_id				= '$data[a_order_id]'
											  AND	order_id_seq			= '$data[a_order_id_no]'
													";												
		    	}
		    	else if($data[a_shop_id] == 0)
		    	{
		    		$insert_query = "UPDATE	shop_stat_upload
											SET		transfer_date			= '$data[a_stat_date]'  	,
													transfer_price		    = (SELECT * FROM (SELECT IF(before_trans_price,before_trans_price,'0') FROM shop_stat_upload WHERE order_id= '$data[a_order_id]' AND order_id_seq = '$data[a_order_id_no]') AS TEMP) + '$data[a_transfer_price]',												
													stat_check				= 'Y'						,
													upload_seq				= '$log_data[MAX_seq]'
											WHERE	order_id				= '$data[a_order_id]'
											  AND	order_id_seq			= '$data[a_order_id_no]'
											  ";
		    	}
		    	$insert_result = mysql_query($insert_query, $connect);
		    }
		    else
		    {
				$minus_query = "SELECT * FROM shop_stat_upload WHERE order_id = '$data[a_order_id]' AND order_id_seq = '$data[a_order_id_no]' AND seq_id =''";
				$minus_result = mysql_query($minus_query, $connect);
				while($minus_data = mysql_fetch_assoc($minus_result))
				{
					$unique_seq = date("YmdHis");
				    $minus_query = "INSERT	shop_stat_upload
											SET	seq_id				=	'$minus_data[seq]'
											,	order_id			=	'$minus_data[order_id].$unique_seq'
											,	order_id_seq		=	'$minus_data[order_id_seq]'
											,	trans_date_pos		=	'$minus_data[trans_date_pos]'
											,	shop_id				=	'$minus_data[shop_id]'
											,	order_name			=	'$minus_data[order_name]'
											,	qty					=	0
											,	amount				=	0
											,	discount_price		=	0
											,	service_price		=	0
											,	supply_price		=	0
											,	order_date			=	'$minus_data[order_date]'
											,	collect_date		=	'$minus_data[collect_date]'
											,	transfer_date		=	'$data[a_stat_date]'
											,	transfer_price		=	'$data[a_transfer_price]'
											,	stat_check			=	'$minus_data[stat_check]'
											,	before_trans_price	=	0
											,	upload_seq			= 	'$log_data[MAX_seq]'
											,	pay_type			=	'$minus_data[pay_type]'
											,	row_span			=	0;
														 ";
					$minus_result = mysql_query($minus_query, $connect);
					$minus_query = "UPDATE	shop_stat_upload 
											SET	row_span = row_span + 1
											WHERE order_id = '$data[a_order_id]' AND order_id_seq = '$data[a_order_id_no]'
														 ";
					$minus_result = mysql_query($minus_query, $connect);
				}
		    }
		}
		
		//값 삽입후 TEMP TABLE TRUNCATE
		$truncate_query = "TRUNCATE TABLE shop_stat_upload_temp";
		mysql_query($truncate_query, $connect);				
		$master_code = substr( $template, 0,1);
		include	"template/"	. $master_code ."/"	. $template	. ".htm";		
	}
	function FR02()
	{
		global $connect		;
		global $template	;
		
		global $order_id	;
		global $order_id_seq;
		global $start_date	;
		
		if($order_id_seq == 0 )
			$order_id_seq ="";
			
			
		$query = "SELECT * FROM shop_stat_upload WHERE order_id like '$order_id%' AND order_id_seq = '$order_id_seq'";
		$result = mysql_query($query, $connect);
		
		$list = array();
		while ( $data = mysql_fetch_assoc($result) )
		{
			$list[] = $data;		
		}
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	function FR03()
	{
		global $connect		;
		global $template	;
		global $page		,	$line_per_page,		$link_url;		


		$line_per_page = 50;
		// 페이지
        if( !$page )
        {
            $page = 1;        
        }

		// link url
        $par = array('template');
        $link_url = $this->build_link_url3( $par );      
		
		$query="SELECT * FROM shop_stat_upload_log ORDER BY seq DESC";
		$result = mysql_query($query, $connect);
		$total_rows = mysql_num_rows( $result );

        $query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
		$result = mysql_query($query, $connect);
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
		
	}
	
	function FR04()
	{
		global $connect		;
		global $template	;
		
		global $page		;
		global $upload_seq	;
		global $transfer_date;
		
		$line_per_page = 50;
		// 페이지
        if( !$page )
        {
            $page = 1;        
        }
        
        
		// link url
        $par = array('template', 'upload_seq', 'transfer_date');
        $link_url = $this->build_link_url3( $par );    
		
		$query  ="SELECT * FROM shop_stat_upload 
						  WHERE	transfer_date	= '$transfer_date'
						    AND	upload_seq		= $upload_seq
												";
		$result = mysql_query($query, $connect);
		$total_rows = mysql_num_rows( $result );
		
		$query .= " limit " . ($page-1) * $line_per_page . ", $line_per_page";
		$result = mysql_query($query, $connect);
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";		
	}
	
	function delete_upload()
	{
		global $connect		;
		global $template	;
		global $upload_seq	;
		global $transfer_date;
		
		$login_id = $_SESSION[LOGIN_ID];
		
		$query  ="SELECT * FROM shop_stat_upload 
						  WHERE	transfer_date	= '$transfer_date'
  						    AND	upload_seq		= $upload_seq
												";												

		$result = mysql_query($query, $connect);
		$rows = mysql_num_rows( $result );
		
		if($rows >= 1)
		{		
			$query  ="UPDATE	shop_stat_upload
											SET		transfer_date			= ''  	,
													transfer_price		    = ''	,
													stat_check				= ''	,
													upload_seq				= ''	,
													before_trans_price		= ''	
											WHERE	transfer_date			= '$transfer_date'
											  AND	upload_seq				= $upload_seq
													";
			$result = mysql_query($query, $connect);
			
			
			$query  ="UPDATE	shop_stat_upload_log
											SET		del_worker				= '$login_id'	,
													del_date				= now()
											WHERE	transfer_date			= '$transfer_date'
											  AND	upload_seq				= $upload_seq
													";
			
			$result = mysql_query($query, $connect);
		}
		else
			echo "<script> alert('삭제할 데이터가 없습니다.');</script>";
			
		$this->FR03();
	}
	function transfer_price_edit()
	{
		global $connect		;
		global $template	;
		
		global $order_id;
		global $order_id_seq;
		global $before_transfer_price;
		global $after_transfer_price;
		global $start_date	;
		global $memo	;
		
		if($order_id_no=='0')
			$order_id_no = '';
			
		$crwork		= $_SESSION[LOGIN_ID];
		$domain		= _DOMAIN_;
		$login_ip 	= $_SERVER[REMOTE_ADDR];
		
		
		$query="INSERT INTO shop_stat_edit_log VALUES('','$order_id','$order_id_seq','$crwork',now(),'$domain','$before_transfer_price','$after_transfer_price','$login_ip')";
		$result = mysql_query($query, $connect);
				
		if($order_id_seq >= 0 )
			$order_id_seq = "";
		
		$insert_query = "SELECT * FROM shop_stat_upload WHERE order_id = '$order_id' AND order_id_seq = '$order_id_seq' AND seq_id =''";
		$insert_result = mysql_query($insert_query, $connect);
		while($data = mysql_fetch_assoc($insert_result))
		{
			$unique_seq = date("YmdHis");
		    $insert_query = "INSERT	shop_stat_upload
									SET	seq_id				=	'$data[seq]'
									,	order_id			=	'$data[order_id].$unique_seq'
									,	order_id_seq		=	'$data[order_id_seq]'
									,	trans_date_pos		=	'$data[trans_date_pos]'
									,	shop_id				=	'$data[shop_id]'
									,	order_name			=	'$data[order_name]'
									,	order_date			=	'$data[order_date]'
									,	collect_date		=	'$data[collect_date]'
									,	transfer_date		=	'$start_date'
									,	transfer_price		=	'$after_transfer_price'
									,	stat_check			=	'$data[stat_check]'
									,	upload_seq			=	'$data[upload_seq]'
									,	row_span			=	0
									,	pay_type			=	'$data[pay_type]'
									,	memo				=	'$memo'
												 ";
			$insert_result = mysql_query($insert_query, $connect);
		}
		$insert_query = "UPDATE shop_stat_upload SET row_span = row_span + 1 WHERE order_id = '$order_id' AND order_id_seq = '$order_id_seq' AND seq_id =''";
		$insert_result = mysql_query($insert_query, $connect);
	
	
		echo "<script>
				window.close();
				opener.location.reload();				
			  </script>";
	
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
	}
	
	
	
	
	
	
	
	
	function sync()
	{
		global $connect		;
		global $template	;		
		

		echo "<script>alert('동기화를 시작합니다.'); </script>";
		//동기화 기준 일자
		$sync_date = "2014-08-19 00:00:00";		
//		$sync_date = date("Y-m-d",strtotime("-1 days"))." 00:00:00";
	
//옥션
		$_query = "insert ignore shop_stat_upload (shop_id, order_id, order_id_seq, order_name, recv_name,       qty,  trans_date_pos, order_date, collect_date,       supply_price,       amount, service_price, discount_price)
					 			                             select shop_id, order_id,         	   '', order_name, recv_name, sum(qty), trans_date_pos, order_date, collect_date, sum(supply_price), sum(amount), sum(code15*11),    sum(code16)
								 from orders where trans_date_pos>='$sync_date' and shop_id = 10001 and status = 8 group by order_id
								 ";
debug("10001  :  ".$_query);
		$_result = mysql_query($_query, $connect);
		
//지마켓		
		$_query = "insert ignore shop_stat_upload (shop_id, order_id, order_id_seq, order_name, recv_name, qty, trans_date_pos, order_date, collect_date, supply_price, amount, service_price, discount_price , charge)
					 			 select shop_id, order_id, 	    	 '', order_name, recv_name, sum(qty), trans_date_pos, order_date, collect_date, sum(supply_price), sum(amount), sum(code15), sum(code16), sum(code15)
								 from orders where trans_date_pos>='$sync_date' and shop_id = 10002 and status = 8 group by order_id";
		$_result = mysql_query($_query, $connect);
debug("10002  :  ".$_query);		
//11번가		
		$_query = "insert ignore shop_stat_upload (shop_id, order_id, order_id_seq, order_name, recv_name, qty,trans_date_pos, order_date, collect_date, supply_price, amount, service_price, discount_price,  charge)
				         		 select shop_id, order_id, order_id_seq, order_name, recv_name, sum(qty),trans_date_pos, order_date, collect_date, sum(supply_price), sum(amount), replace(left(code6,5),',',''),    sum(code15), code5
								 from orders where trans_date_pos>='$sync_date' and shop_id = 10050 and status = 8  group by order_id, order_id_seq ";
		$_result = mysql_query($_query, $connect);
debug("10050  :  ".$_query);
		
//고도몰		
		$_query = "insert ignore shop_stat_upload (shop_id, order_id, order_id_seq, order_name, recv_name, qty     ,trans_date_pos, order_date, collect_date,      supply_price,      amount,                   discount_price, pay_type)
				      	 	                select shop_id, order_id, 	        '', order_name, recv_name, sum(qty),trans_date_pos, order_date, collect_date, sum(supply_price), sum(amount) - (sum(code17)+ code15 + code16 + code18), sum(code17)+ code15 + sum(code31) + code18, pay_type
						    from orders where shop_id = 10058 and status = 8 and pay_type NOT IN ('무통장', '전액할인') and order_cs NOT IN (1, 3) 
		                                                               and trans_date_pos >= '$sync_date' group by order_id";
		$_result = mysql_query($_query, $connect);
debug("10058  :  ".$_query);		
//고도몰		
		$_query = "insert ignore shop_stat_upload (shop_id, order_id, order_id_seq, order_name, recv_name,     qty ,trans_date_pos, order_date, collect_date,  supply_price,      amount,                        discount_price, pay_type, stat_check    )
 										 select	   shop_id, order_id, 	        '', order_name, recv_name, sum(qty),trans_date_pos, order_date, collect_date,  sum(amount) + sum(code17)- (code15 + code16 + code18), sum(amount) + sum(code17)- (code15 + code16 + code18), sum(code17)+ code15 + sum(code31) + code18, pay_type,        'N'
 									from orders where shop_id = 10058 and status = 8 and pay_type IN ('무통장') and order_cs NOT IN (1, 3) 
		                                                               and trans_date_pos >= '$sync_date' group by order_id";		
		$_result = mysql_query($_query, $connect);
debug("10058  :  ".$_query);		
//고도몰		
		$_query = "insert ignore shop_stat_upload (shop_id, order_id, order_id_seq, order_name, recv_name,     qty ,trans_date_pos, order_date, collect_date,      supply_price,amount,                   	   discount_price, pay_type, stat_check,    transfer_price) 										 
 										 select	   shop_id, order_id, 	        '', order_name, recv_name, sum(qty),trans_date_pos, order_date, collect_date, sum(supply_price), 	 0, sum(code17)+ code15 + sum(code31) + code18, pay_type,        'Y',	 code13
 									from orders where shop_id = 10058 and status = 8 and pay_type IN ('전액할인') and order_cs NOT IN (1, 3) 
		                                                               and trans_date_pos >= '$sync_date' group by order_id";		
		$_result = mysql_query($_query, $connect);		
debug("10058  :  ".$_query);

		
		$_query = "UPDATE shop_stat_upload 
				      SET supply_price = amount - floor(amount * 0.0278) - floor(floor(amount * 0.0278) * 0.10)
				    WHERE trans_date_pos >= '$sync_date'
				      AND shop_id = 10058
				      AND pay_type = '신용카드'";
				      //AND seq >= $m_seq";
		$_result = mysql_query($_query, $connect);
		
		$_query = "UPDATE shop_stat_upload 
				      SET supply_price = amount - floor(amount * 0.015) - round(floor(amount * 0.015) * 0.10)
				    WHERE trans_date_pos >= '$sync_date'
				      AND shop_id = 10058
				      AND pay_type = '계좌이체'";
				      //AND seq >= $m_seq";
		$_result = mysql_query($_query, $connect);

		
		
/*		
		//취소건 삭제
		$_query = "select  a.order_id FROM shop_stat_upload a, orders b WHERE a.order_id = b.order_id AND b.order_cs IN(1,2)";
		$_result = mysql_query($_query, $connect);
		while( $data = mysql_fetch_assoc($_result ))
		{		
			$__query = "DELETE FROM shop_stat_upload WHERE order_id = '$data[order_id]'";
			mysql_query($__query, $connect);
		}

		//주문 삭제건 삭제
		$_query = "select  a.order_id a_order_id FROM shop_stat_upload a, orders_del b WHERE a.order_id = b.order_id group by b.order_id";
		$_result = mysql_query($_query, $connect);
		while($_data = mysql_fetch_assoc($_result))
		{	
			//orders에 있으면 삭제 x,
			$__query	= "select count(*) cnt from orders where order_id = $_data[a_order_id]";
			$__result	= mysql_query($__query, $connect);
			$__data		= mysql_fetch_assoc($__result);
			if($__data[cnt] == 0)
			{
				$__query = "DELETE FROM shop_stat_upload WHERE order_id = '$_data[a_order_id]'";
				mysql_query($__query, $connect);
			}
		}
*/		
/*
		박스포유 중복 확인쿼리		
		select max(seq), count(order_id) cnt, order_id, stat_check from shop_stat_upload a group by order_id, order_id_seq having cnt >1;
*/

		echo "<script>alert('동기화를 끝냈습니다.'); </script>";
		
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";	
	}
}
?>