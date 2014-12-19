<?
require_once "class_top.php";
require_once "class_shop.php";
require_once "class_supply.php";
require_once "class_product.php";
require_once "class_file.php";
require_once "Classes/PHPExcel.php";

//////////////////////////////////////////////
// 반품정보
class class_EN00 extends class_top
{
    function EN00()
    {
        global $template, $connect, $page;
        global $date_type,$start_date,$end_date,$start_hour,$end_hour,$query_type,$query_string,$shop_id,$supply_code,
               $return_type,$cancel_type,$change_type,$restockin_auto,$expect_trans_who,$return_trans_who,$expect_price,$return_price,
               $expect_info,$return_info,$complete_info,$delete_data,$work_type,$work_who,$return_trans_corp, $sort_direction, $paytype_info;
        
        // 페이지
        if( !$page )
        {
            $page = 1;
            $master_code = substr( $template, 0,1);
            include "template/" . $master_code ."/" . $template . ".htm";
            return;
        }
        
        // 작업중
        $this->show_wait();

        // link url
        $par = array("date_type","start_date","end_date","start_hour","end_hour","query_type","query_string","shop_id","supply_code",
                     "return_type","cancel_type","change_type","restockin_auto","expect_trans_who","return_trans_who","expect_price","return_price",
                     "expect_info","return_info","complete_info","delete_data","product_info","work_type","work_who","return_trans_corp","sort_direction");
        $link_url = $this->build_link_url3($par);
        
        $total_rows    = 0;
        $total_expect  = 0;
        $total_return  = 0;
        $total_who1    = 0;
        $total_who2    = 0;
        $total_site    = 0;
        $total_envelop = 0;
        $total_account = 0;
        $total_notget  = 0;

        $query = $this->get_EN00();
        $result = mysql_query($query, $connect);
        while( $data=mysql_fetch_assoc($result) )
        {
            // 총 건수
            $total_rows++;
            
            // 예정
            if( $data[is_return] == 0 )  
                $total_expect++;
            // 도착
            else
            {
                $total_return++;
                
                // 선불
                if( $data[return_trans_who] == 0 )
                    $total_who1++;
                // 착불
                else
                    $total_who2++;
            }
            
            // 착불 타택배 배송비
            $total_price += $data[return_trans_price];
            
            // 사이트 결제 총금액
            $total_site += $data[return_site];
            
            // 동봉 총금액
            $total_envelop += $data[return_envelop];
            
            // 계좌 총금액
            $total_account += $data[return_account];
            
            // 미수 총금액
            $total_notget += $data[return_notget];
        }
        $result = mysql_query($query, $connect);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function get_EN00()
    {
        global $template, $connect, $page;
        global $date_type,$start_date,$end_date,$start_hour,$end_hour,$query_type,$query_string,$shop_id,$supply_code,
               $return_type,$cancel_type,$change_type,$restockin_auto,$expect_trans_who,$return_trans_who,$expect_price,$return_price,
               $expect_info,$return_info,$complete_info,$delete_data,$work_type,$work_who,$return_trans_corp, $sort_direction, $paytype_info;
		
		if( _DOMAIN_ == "alice" )
			$query = "select a.*, b.pay_type from return_money a, orders b where a.order_seq = b.seq and a.$date_type >= '$start_date $start_hour' and a.$date_type <=  '$end_date $end_hour'";
        else
        	$query = "select * from return_money a where a.$date_type >= '$start_date $start_hour' and a.$date_type <= '$end_date $end_hour'";



        if( $query_string )
        {
            switch( $query_type )
            {
                case 'recv_name'        :
                case 'shop_product_id'  :
                case 'ez_product_id'    :
                case 'org_trans_no'     :
                case 'return_trans_no'  :
                    $query .= " and a.$query_type = '$query_string'";
                    break;
                case 'shop_product_name':
                case 'shop_options'     :
                case 'ez_product_name'  :
                case 'ez_options'       :
                    $query .= " and a.$query_type like '%$query_string%'";
                    break;
            }
        }
        
        if( _DOMAIN_ =="alice" )
        {
        	switch ($paytype_info) 
        	{ 	
        		case 1://카드
        			$query .= " AND b.pay_type IN ('C', '신용카드', '신용카드 간편결제', '카드') ";
        		break;
        		
        		case 2://현금
        			$query .= " AND b.pay_type IN ('R','A','E', '무통장입금','실시간계좌이체','에스크로가상계좌','현금' ) ";
        		break;
        		
        		case 3://모바일
        			$query .= " AND b.pay_type IN ('H','휴대폰','휴대폰 간편결제','휴대폰결제' ) ";
        		break;
        		
        		case 4://적립금
        			$query .= " AND b.pay_type IN ('M' ) ";
        		break;
        		
        		case 5://네이버캐쉬
        			$query .= " AND b.pay_type IN ('네이버 캐쉬' ) ";
        		break;
        	}
        }




        if( $shop_id )
            $query .= " and a.shop_id=$shop_id";
        
        if( $supply_code )
            $query .= " and a.supply_id=$supply_code";
        
        if( $work_who != '전체' )
        {
            if( $work_type == 0 )
                $query .= " and ( a.expect_worker = '$work_who' or a.return_worker = '$work_who' or a.complete_worker = '$work_who' ) ";
            else if( $work_type == 1 )
                $query .= " and a.expect_worker = '$work_who' ";
            else if( $work_type == 2 )
                $query .= " and a.return_worker = '$work_who' ";
            else if( $work_type == 3 )
                $query .= " and a.complete_worker = '$work_who' ";
        }
        
        if( $return_type == 3 )
            $query .= " and a.return_type in (0,1)";
        else if( $return_type != 'all' )
            $query .= " and a.return_type = $return_type";
        
        if( $cancel_type != 'all' )
            $query .= " and a.cancel_type = '$cancel_type'";
        
        if( $change_type != 'all' )
            $query .= " and a.change_type = '$change_type'";
            
        if( $restockin_auto )
            $query .= " and a.restockin_auto = $restockin_auto - 1";
        
        if( $expect_trans_who != 'all' )
            $query .= " and a.expect_trans_who = $expect_trans_who";
            
        if( $return_trans_who != 'all' )
            $query .= " and a.return_trans_who = $return_trans_who";
            
        if( $expect_price != 'all' )
            $query .= " and a.expect_${expect_price} > 0";
            
        if( $return_price != 'all' )
            $query .= " and a.return_${return_price} > 0";
            
        if( $return_trans_corp )
            $query .= " and a.return_trans_corp=$return_trans_corp";
            
        if( $expect_info != 'all' )
            $query .= " and a.is_expect = $expect_info";
            
        if( $return_info != 'all' )
            $query .= " and a.is_return = $return_info";
            
        if( $complete_info != 'all' )
            $query .= " and a.is_complete = $complete_info";
            
        if( $delete_data )
            $query .= " and a.is_delete = 1";
        else
            $query .= " and a.is_delete = 0";
            
        $query .= " order by a.seq ";
        
        if( !$sort_direction )
            $query .= " desc";

debug($query);
        return $query;
    }   

    function save_file_EN00()
    {
        global $template, $connect, $link_url;
        global $date_type,$start_date,$end_date,$start_hour,$end_hour,$query_type,$query_string,$shop_id,$supply_code,
               $return_type,$cancel_type,$change_type,$restockin_auto,$expect_trans_who,$return_trans_who,$expect_price,$return_price,
               $expect_info,$return_info,$complete_info,$delete_data,$work_type,$work_who,$return_trans_corp, $sort_direction, $paytype_info;

        $query = $this->get_EN00();
        $result = mysql_query($query, $connect);

        $n = 0;
        $old_time = time();
        $total_rows = mysql_num_rows( $result );
        
        $arr_datas = array();
        while( $data = mysql_fetch_assoc($result) )
        {
            // 반품타입
            if( $data[is_return] )
            {
                // 취소
                if( $data[return_type] == 0 || $data[return_type] == 1 )
                    $return_type = "취소 : " . $data[cancel_type];
                // 교환
                else
                    $return_type = "교환 : " . $data[change_type];
            }
            // 도착 전
            else
                $return_type = "";
                    
            // 자동반품입고
            if( $data[restockin_auto] == 1 )
                $restockin_str = "설정-정상";
            else if( $data[restockin_auto] == 2 )
                $restockin_str = "설정-$_SESSION[EXTRA_STOCK_TYPE]";
            else
                $restockin_str = "미설정";
                
            // order_id
            $query_order_id = "select order_id from orders where seq=$data[order_seq]";
            $result_order_id = mysql_query($query_order_id, $connect);
            $data_order_id = mysql_fetch_assoc($result_order_id);
	        if( _DOMAIN_ == 'purple' )
	        {
		        $supply_data = class_supply::get_info($data[supply_id]);			             // 공급처,주소,연락처
		    }
		    $arr_temp = array();
			$arr_temp['order_seq'         ]= $data[order_seq];                                     // 관리번호
			$arr_temp['order_id'          ]= $data_order_id[order_id];                             // 주문번호
			$arr_temp['collect_date'      ]= $data[collect_date];                                  // 발주일
			$arr_temp['trans_date'        ]= $data[trans_date];                                    // 배송일
			$arr_temp['shop_name'         ]= class_shop::get_shop_name($data[shop_id]);            // 판매처
			$arr_temp['supply_name'       ]= class_supply::get_name($data[supply_id]);             // 공급처			
			if( _DOMAIN_ == 'purple' )
	        {	
	        	$supply_product_data = class_product::get_info($data[ez_product_id]);
		        $arr_temp['supply_addr'   ]= $supply_data[address1];        						// 공급처주소
		        $arr_temp['supply_contact']= $supply_data[tel]."/".$supply_data[mobile];            // 공급처연락처
		        $arr_temp['supply_brand'  ]= $supply_product_data[brand];   				        // 공급처상품명
		        $arr_temp['supply_origin' ]= $supply_product_data[org_price];           			// 상품원가
		    }
			$arr_temp['recv_name'         ]= $data[recv_name];                                     // 수령자
			$arr_temp['enable_sale'       ]= class_product::enable_sale($data[ez_product_id]);     // 품절
			$arr_temp['shop_product_id'   ]= $data[shop_product_id];                               // 판매처 상품코드
			$arr_temp['shop_product_name' ]= $data[shop_product_name] . " " . $data[shop_options]; // 판매처 상품
			$arr_temp['product_id'        ]= $data[ez_product_id];                                 // 어드민 상품코드
			
			if( _DOMAIN_ == 'purple' )
	        {
	        	$ez_options = array();
	        	$ez_options = explode(",",$data[ez_options]);
	        	if(substr($ez_options[0], 0, 4) =="Size" || substr($ez_options[0], 0, 9) == "사이즈")
	        	{
	        		$ez_options[1] = $ez_options[0];
	        		$ez_options[0] = "";	        		
	        	}
		        $arr_temp['product_name'  ]= $data[ez_product_name];  							   // 어드민 상품
		        $arr_temp['option_color'  ]= $ez_options[0];   	  								   // 어드민 옵션(컬러)
		        $arr_temp['option_size'   ]= $ez_options[1];    	 							   // 어드민 옵션(사이즈)
		    }
		    else
		    {
		    	$arr_temp['product_name'  ]= $data[ez_product_name] . " " . $data[ez_options];     // 어드민 상품	
		    }
		    
		    if( $data[expect_trans_who] == 0 )
		        $_expect_trans_who = "선불";
		    else if( $data[expect_trans_who] == 1 )
		        $_expect_trans_who = "착불(계약택배)";
		    else if( $data[expect_trans_who] == 2 )
		        $_expect_trans_who = "착불(타택배)";
		    
		    if( $data[return_trans_who] == 0 )
		        $_return_trans_who = "선불";
		    else if( $data[return_trans_who] == 1 )
		        $_return_trans_who = "착불(계약택배)";
		    else if( $data[return_trans_who] == 2 )
		        $_return_trans_who = "착불(타택배)";
		    
			$arr_temp['qty'               ]= $data[qty];                                           // 수량
			$arr_temp['org_trans_no'      ]= $data[org_trans_no];                                  // 원주문 송장번호
			$arr_temp['return_type'       ]= $return_type;                                         // 반품 타입
if( _DOMAIN_ == 'alice' )
			$arr_temp['pay_type'   		  ]= $data[pay_type];                                      // 결재 타입
			$arr_temp['expect_site'       ]= $data[expect_site];                                   // [반품예정] 사이트 결제
			$arr_temp['expect_envelop'    ]= $data[expect_envelop];                                // [반품예정] 동봉
			$arr_temp['expect_account'    ]= $data[expect_account];                                // [반품예정] 계좌
			$arr_temp['expect_trans_who'  ]= $_expect_trans_who;                                   // [반품예정] 선착불
			$arr_temp['return_site'       ]= $data[return_site];                                   // [반품도착] 사이트 결제
			$arr_temp['return_envelop'    ]= $data[return_envelop];                                // [반품도착] 동봉
			$arr_temp['return_account'    ]= $data[return_account];                                // [반품도착] 계좌
			$arr_temp['return_notget'     ]= $data[return_notget];                                 // [반품도착] 미수
			$arr_temp['return_trans_who'  ]= $_return_trans_who;                                   // [반품도착] 선착불
			$arr_temp['return_trans_corp' ]= $this->get_trans_corp_name($data[return_trans_corp]); // [반품도착] 택배사
			$arr_temp['return_trans_no'   ]= $data[return_trans_no];                               // [반품도착] 송장번호
			$arr_temp['return_trans_price']= $data[return_trans_price];                            // [반품도착] 착불 타택배 배송비
			$arr_temp['restockin_auto'    ]= $restockin_str;                                       // 자동반품입고
			$arr_temp['expect_date'       ]= ($data[expect_date]   == "0000-00-00 00:00:00" ? "" : $data[expect_date]  ); // 예정일
			$arr_temp['expect_worker'     ]= $data[expect_worker];                                 // 예정 작업자
			$arr_temp['return_date'       ]= ($data[return_date]   == "0000-00-00 00:00:00" ? "" : $data[return_date]  ); // 도착일
			$arr_temp['return_worker'     ]= $data[return_worker];                                 // 도착 작업자
			$arr_temp['complete_date'     ]= ($data[complete_date] == "0000-00-00 00:00:00" ? "" : $data[complete_date]); // 완료일
			$arr_temp['complete_worker'   ]= $data[complete_worker];                               // 완료 작업자
			$arr_temp['delete_date'       ]= ($data[delete_date]   == "0000-00-00 00:00:00" ? "" : $data[delete_date]  ); // 삭제일
			$arr_temp['delete_worker'     ]= $data[delete_worker];                                 // 삭제 작업자
			$arr_temp['memo'              ]= $data[memo];                                           // 메모

            
            $arr_datas[] = $arr_temp;

            // 진행
            $n++;
            if( $old_time < time() )
            {
                $old_time = time();
                $msg = " $n / $total_rows ";
                echo "<script type='text/javascript'>parent.show_txt( '$msg' )</script>";
                flush();
            }
        }

        if( count($arr_datas) > 0 )
        {
            $this->make_file_EN00( $arr_datas, "download.xls" );
            echo "<script type='text/javascript'>parent.set_file('download.xls')</script>";
        }
        else
        {
            echo "<script type='text/javascript'>parent.no_file()</script>";
        }
    }

    function make_file_EN00( $arr_datas, $fn)
    {
        $filename = _upload_dir . $fn;

        $excel = new PHPExcel();
        $sheet = $excel->getActiveSheet();
		$excel_column = 0;
		
        $sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("관리번호"              ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("주문번호"              ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("발주일"                ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("배송일"                ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("판매처"                ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("공급처"                ,PHPExcel_Cell_DataType::TYPE_STRING);
        if( _DOMAIN_ == 'purple' )
        {
        	$sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("공급처주소"        ,PHPExcel_Cell_DataType::TYPE_STRING);
        	$sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("공급처연락처"      ,PHPExcel_Cell_DataType::TYPE_STRING);
        	$sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("공급처상품명"      ,PHPExcel_Cell_DataType::TYPE_STRING);
        	$sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("공급원가"      ,PHPExcel_Cell_DataType::TYPE_STRING);
        }
        $sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("수령자"                ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("품절"                ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("판매처 상품코드"       ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("판매처 상품"           ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++,  1)->setValueExplicit("어드민 상품코드"       ,PHPExcel_Cell_DataType::TYPE_STRING);        
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("어드민 상품"            ,PHPExcel_Cell_DataType::TYPE_STRING);
		if( _DOMAIN_ == 'purple' )
        {
        	$sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("어드민 옵션(색상)"        ,PHPExcel_Cell_DataType::TYPE_STRING);
        	$sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("어드민 옵션(사이즈)"        ,PHPExcel_Cell_DataType::TYPE_STRING);
        }	
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("수량"                  ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("원주문 송장번호"       ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("반품 타입"             ,PHPExcel_Cell_DataType::TYPE_STRING);
if( _DOMAIN_ == 'alice' )
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("결재 타입"             ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("[반품예정] 사이트 결제",PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("[반품예정] 동봉"       ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("[반품예정] 계좌"       ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("[반품예정] 선착불"     ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("[반품도착] 사이트 결제",PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("[반품도착] 동봉"       ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("[반품도착] 계좌"       ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("[반품도착] 미수"       ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("[반품도착] 선착불"     ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("[반품도착] 택배사"     ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("[반품도착] 송장번호"   ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("[반품도착] 착불 타택배 배송비"   ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("자동반품입고"          ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("예정일"                ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("예정 작업자"           ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("도착일"                ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("도착 작업자"           ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("완료일"                ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("완료 작업자"           ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("삭제일"                ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("삭제 작업자"           ,PHPExcel_Cell_DataType::TYPE_STRING);
        $sheet->getCellByColumnAndRow($excel_column++, 1)->setValueExplicit("메모"                  ,PHPExcel_Cell_DataType::TYPE_STRING);

		if( _DOMAIN_ == 'purple' )
        {
        	$sheet->getStyle('A1:AP1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
	        $sheet->getStyle('A1:AP1')->getFill()->getStartColor()->setARGB('FFCCFFCC');
	        $sheet->getStyle('A1:AP1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	        $sheet->getStyle('A1:AP1')->getFont()->setBold(true);
        }
       	else
       	{
       		$sheet->getStyle('A1:AJ1')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
	        $sheet->getStyle('A1:AJ1')->getFill()->getStartColor()->setARGB('FFCCFFCC');
	        $sheet->getStyle('A1:AJ1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
	        $sheet->getStyle('A1:AJ1')->getFont()->setBold(true);
       	}

        

        foreach ($arr_datas as $row => $row_data) {
            $row = $row + 2;
            $col = 0;
            foreach ($row_data as $key => $value) {
                $value = trim($value);
                if( $key == 'expect_site'    ||
                    $key == 'expect_envelop' ||
                    $key == 'expect_account' ||
                    $key == 'return_site'    ||
                    $key == 'return_envelop' ||
                    $key == 'return_account' ||
                    $key == 'return_notget'  ||
                    $key == 'return_trans_price' )
                {
                    $cell = $sheet->getCellByColumnAndRow($col, $row);
                    $cell->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_NUMERIC);
                    $cell->getParent()->getStyle( $cell->getCoordinate() )->getNumberFormat()->setFormatCode('#,##0');
                }
                else
                    $sheet->getCellByColumnAndRow($col, $row)->setValueExplicit($value, PHPExcel_Cell_DataType::TYPE_STRING);
                $col++;
            }
        }

        // border
        $styleArray = array(
        	'font' => array(
        		'name' => '굴림체',
        		'size' => 9,
        	),
        	'borders' => array(
        		'allborders' => array(
        			'style' => PHPExcel_Style_Border::BORDER_THIN ,
        			'color' => array('argb' => 'FF000000'),
        		),
        	),
        );
        if( _DOMAIN_ == 'purple' )
        	$sheet->getStyle('A1:AP'.$row)->applyFromArray($styleArray);
        else
			$sheet->getStyle('A1:AJ'.$row)->applyFromArray($styleArray);
        $writer = PHPExcel_IOFactory::createWriter($excel, 'Excel5');
        $writer->save($filename);

        return $filename; 
    }

    function download_EN00()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, iconv('utf-8','cp949',"반품관리.xls"));
    }    
    
    // 금액 직접 변경
    function change_return_price()
    {
        global $connect, $seq, $price_type, $price;
        
        $val = array();
        
        // 해당 반품정보가 완료됐거나 삭제 됐는지 확인
        $query = "select * from return_money where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        // 완료처리
        if( $data[is_complete] )
        {
            $val['error'] = 1;
            echo json_encode($val);
            return;
        }

        // 삭제처리
        if( $data[is_delete] )
        {
            $val['error'] = 2;
            echo json_encode($val);
            return;
        }
        
        // 금액 변경
        $price = (int)preg_replace("/[^0-9]/", "", $price);
        $query = "update return_money set $price_type=$price where seq=$seq";
        mysql_query($query, $connect);
        
        // 반품 로그
        switch( $price_type )
        {
            case "expect_site"   : $price_type_str = "반품예정 사이트결제"; break;
            case "expect_envelop": $price_type_str = "반품예정 동봉"      ; break;
            case "expect_account": $price_type_str = "반품예정 계좌"      ; break;
            case "return_site"   : $price_type_str = "반품도착 사이트결제"; break;
            case "return_envelop": $price_type_str = "반품도착 동봉"      ; break;
            case "return_account": $price_type_str = "반품도착 계좌"      ; break;
            case "return_notget" : $price_type_str = "반품도착 미수"      ; break;
        }
        $query_return_log = "insert return_money_log
                                set seq                = '$seq',
                                    log_type           = 'update',
                                    log_contents       = '$price_type_str : " . number_format($price) . " 원',
                                    log_date           = now(),
                                    log_worker         = '$_SESSION[LOGIN_NAME]'";
        mysql_query($query_return_log, $connect);
        
        $val['error'] = 0;
        $val['new_value'] = number_format($price);
        echo json_encode($val);
    }
    
    // 선착불 직접 변경
    function change_return_trans_who()
    {
        global $connect, $seq, $is_expect, $trans_who;
        
        $val = array();
        
        // 해당 반품정보가 완료됐거나 삭제 됐는지 확인
        $query = "select * from return_money where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        // 완료처리
        if( $data[is_complete] )
        {
            $val['error'] = 1;
            echo json_encode($val);
            return;
        }

        // 삭제처리
        if( $data[is_delete] )
        {
            $val['error'] = 2;
            echo json_encode($val);
            return;
        }
        
        // 선착불 변경
        $query = "update return_money set " . ($is_expect ? "expect_trans_who" : "return_trans_who") . "=$trans_who where seq=$seq";
        mysql_query($query, $connect);
        
        // 반품 로그
        $query_return_log = "insert return_money_log
                                set seq                = '$seq',
                                    log_type           = 'update',
                                    log_contents       = '" . ($is_expect ? "반품예정 선착불 : " : "반품도착 선착불 : ") . ($trans_who ? "착불" : "선불") . "',
                                    log_date           = now(),
                                    log_worker         = '$_SESSION[LOGIN_NAME]'";
        mysql_query($query_return_log, $connect);
        
        $val['error'] = 0;
        $val['new_value'] = number_format($price);
        echo json_encode($val);
    }
    
    // 반품 택배사 변경
    function change_trans_corp()
    {
        global $connect, $sys_connect, $seq, $trans_corp;
        
        $val = array();
        
        // 해당 반품정보가 완료됐거나 삭제 됐는지 확인
        $query = "select * from return_money where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        // 완료처리
        if( $data[is_complete] )
        {
            $val['error'] = 1;
            echo json_encode($val);
            return;
        }

        // 삭제처리
        if( $data[is_delete] )
        {
            $val['error'] = 2;
            echo json_encode($val);
            return;
        }
        
        // 택배사 변경
        $query = "update return_money set return_trans_corp=$trans_corp where seq=$seq";
        mysql_query($query, $connect);
        
        // 반품 로그
        $query_return_log = "insert return_money_log
                                set seq                = '$seq',
                                    log_type           = 'update',
                                    log_contents       = '택배사 : " . $this->get_trans_corp_name($trans_corp) . "',
                                    log_date           = now(),
                                    log_worker         = '$_SESSION[LOGIN_NAME]'";
        mysql_query($query_return_log, $connect);
        
        $val['error'] = 0;
        echo json_encode($val);
    }
    
    // 송장번호 변경
    function change_trans_no()
    {
        global $connect, $seq, $trans_no;
        
        $val = array();
        
        // 해당 반품정보가 완료됐거나 삭제 됐는지 확인
        $query = "select * from return_money where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        // 완료처리
        if( $data[is_complete] )
        {
            $val['error'] = 1;
            echo json_encode($val);
            return;
        }

        // 삭제처리
        if( $data[is_delete] )
        {
            $val['error'] = 2;
            echo json_encode($val);
            return;
        }
        
        // 금액 변경
        $query = "update return_money set return_trans_no=$trans_no where seq=$seq";
        mysql_query($query, $connect);
        
        // 반품 로그
        $query_return_log = "insert return_money_log
                                set seq                = '$seq',
                                    log_type           = 'update',
                                    log_contents       = '송장번호 : $trans_no',
                                    log_date           = now(),
                                    log_worker         = '$_SESSION[LOGIN_NAME]'";
        mysql_query($query_return_log, $connect);
        
        $val['error'] = 0;
        $val['new_value'] = $trans_no;
        echo json_encode($val);
    }
    
    // 완료처리
    function return_complete()
    {
        global $connect, $seq;
        
        $query = "update return_money 
                     set is_complete = 1,
                         complete_date = now(),
                         complete_worker = '$_SESSION[LOGIN_NAME]'
                   where seq = $seq";
        mysql_query($query, $connect);

        // 반품 로그
        $query_return_log = "insert return_money_log
                                set seq                = '$seq',
                                    log_type           = 'complete',
                                    log_contents       = '',
                                    log_date           = now(),
                                    log_worker         = '$_SESSION[LOGIN_NAME]'";
        mysql_query($query_return_log, $connect);
    }
    
    // 완료처리 취소
    function cancel_complete()
    {
        global $connect, $seq;
        
        $query = "update return_money 
                     set is_complete = 0,
                         complete_date = 0,
                         complete_worker = ''
                   where seq = $seq";
        mysql_query($query, $connect);

        // 반품 로그
        $query_return_log = "insert return_money_log
                                set seq                = '$seq',
                                    log_type           = 'cancel complete',
                                    log_contents       = '',
                                    log_date           = now(),
                                    log_worker         = '$_SESSION[LOGIN_NAME]'";
        mysql_query($query_return_log, $connect);
    }
    
    // 반품정보 삭제
    function return_delete()
    {
        global $connect, $seq;
        
        $query = "update return_money 
                     set is_delete = 1,
                         delete_date = now(),
                         delete_worker = '$_SESSION[LOGIN_NAME]'
                   where seq = $seq";
        mysql_query($query, $connect);

        // 반품 로그
        $query_return_log = "insert return_money_log
                                set seq                = '$seq',
                                    log_type           = 'delete',
                                    log_contents       = '',
                                    log_date           = now(),
                                    log_worker         = '$_SESSION[LOGIN_NAME]'";
        mysql_query($query_return_log, $connect);
    }
    
    // 상세팝업
    function EN01()
    {
        global $template, $connect, $seq, $list_ref, $order_seq;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 반품로그 가져오기
    function get_return_log()
    {
        global $connect, $seq;
        
        $val = array();
        
        $query = "select * from return_money_log where seq=$seq order by no desc";
        $result = mysql_query($query, $connect);
        while( $data_log = mysql_fetch_assoc($result) )
        {
            $val["log_list"][] = array(
                "log_date"     => $data_log["log_date"    ],
                "log_worker"   => $data_log["log_worker"  ],
                "log_type"     => $data_log["log_type"    ],
                "log_contents" => $data_log["log_contents"]
            );
        }
        
        echo json_encode( $val );
    }

    // cs로그 가져오기
    function get_cs_log()
    {
        global $connect, $seq;
        
        $val = array();
        
        // seq 구하기
        $query = "select order_seq from return_money where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $seq = $data[order_seq];
        
        // pack 구하기
        $query = "select pack from orders where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $seq_list = "";
        // 합포
        if( $data[pack] > 0 )
        {
            $query = "select seq from orders where pack=$data[pack]";
            $result = mysql_query($query, $connect);
            while($data = mysql_fetch_assoc($result) )
                $seq_list .= ($seq_list ? "," : "") . $data[seq];
        }
        // 단품
        else
            $seq_list = $seq;
        
        $query = "select * from csinfo where order_seq in ($seq_list) order by seq desc";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $val["cs_list"][] = array(
                "cs_date"     => $data["input_date"] . " " . $data["input_time"],
                "cs_worker"   => $data["writer"],
                "cs_type"     => $this->get_cs_type_str($data["cs_type"]),
                "cs_contents" => $data["content"].$data["user_content"]
            );
        }
        
        echo json_encode( $val );
    }
    
    // cs 남기기
    function cs_write()
    {
        global $connect, $seq, $contents;
        
        // seq 구하기
        $query = "select order_seq from return_money where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        $seq = $data[order_seq];
     
        // cs 남기기
        $query = "insert csinfo 
                     set order_seq  = $seq,
                         input_date = now(),
                         input_time = now(),
                         writer     = '$_SESSION[LOGIN_NAME]',
                         cs_type    = 0,
                         cs_reason  = '',
                         cs_result  = 0,
                         user_content    = '$contents'";
        mysql_query($query, $connect);
    }
    
    function cs_complete()
    {
        global $connect, $seq, $is_all;
        
        // seq 구하기
        $query = "select order_seq from return_money where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $seq = $data[order_seq];
        $is_all = 1;

        require_once "class_E900.php";
        
        $obj = new class_E900();
        $obj->set_complete();
    }
}
?>
