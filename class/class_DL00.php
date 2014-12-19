<?
require_once "class_top.php";
require_once "class_E100.php";
require_once "class_cs.php";

////////////////////////////////
// class name: class_D900
// 
//
class class_DL00 extends class_top 
{
   var $g_order_id;
   var $debug = "off"; // 전체 download: on/off
   var $no = 0;

   function DL00()
   {
        global $template, $start_date;
         $line_per_page = _line_per_page;

        if (!$start_date) 
            $start_date = date('Y-m-d', strtotime('-7 day'));
            
        $link_url = "?" . $this->build_link_url();

        if ( $_REQUEST[act] == '1' )
        {
            $this->query();    
        }
        else
        {
            $master_code = substr( $template, 0,1);
            include "template/" . $master_code ."/" . $template . ".htm";
        }
   }
   
   //
   // ezauto_reg_log에서 가져오는걸로 변경 2013.6.4
   //
   function DL01()
   {
        global $template, $start_date, $act,$order_id,$trans_no;
        
        $line_per_page = _line_per_page;

        if (!$start_date) 
            $start_date = date('Y-m-d', strtotime('-7 day'));
            
        $link_url = "?" . $this->build_link_url();

        $total_rows = 0;
        if ( $_REQUEST[act] == '1' )
        {
            $result = $this->query_reg_log(&$total_rows);    
        }
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
   }

	function DL02()
	{
		global $template, $start_date;

        if (!$start_date) 
            $start_date = date('Y-m-d');

		$data = $this->get_balju_time();		

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
	}

   function DL03()
   {
        global $template, $start_date, $act,$order_id,$trans_no, $page;
        
        $line_per_page = _line_per_page;

        if (!$start_date) 
            $start_date = date('Y-m-d', strtotime("-1 day"));
            
        $link_url = "?" . $this->build_link_url();


        $total_rows = 0;
        $result = $this->query_reg_result(&$total_rows);    
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
   }

	function DL04()
	{
		global $template, $start_date;

        if (!$start_date) 
            $start_date = date('Y-m-d');

		$data = $this->get_cancel_time();		

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
	}

	function DL05()
	{
		global $template, $start_date;

        if (!$start_date) 
            $start_date = date('Y-m-d');

		$data = $this->get_upload_time();		

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
	}

	function query_reg_result( &$total_rows )
	{
        global $connect,$shop_id, $start_date, $end_date, $template, $page, $chk_reg,$order_id,$trans_no, $order_by;

		include_once "lib/ezauto_shoplist.php";
		$ezauto_shoplist = get_ezauto_trans_shoplist();

        $line_per_page = 100;	
		if ( !$end_date )	
			$end_date = Date("Y-m-d H:i:s");

		if ( $shop_id )
			$orders_shop_id = " and orders.shop_id = $shop_id ";
		else
		{	
	    	$sql = "select shop_id 
					  from shopinfo 
					 where balju_stop = 0 
					   and shop_id % 100 < 80
					   and shop_id % 100 in ( $ezauto_shoplist )
					";

			if ( _DOMAIN_ == "huiz2" )
		    	$sql .= " and shop_id % 100 not in ( 15,16 )";


		    $r = mysql_query($sql, $connect);
		    $orders_shop_id = " and orders.shop_id in ( ";
						
			if ( mysql_num_rows( $r ) == 0 )
			{
				$orders_shop_id = " and orders.shop_id in ('')";	
			}
			else
			{
			    while ($l = mysql_fetch_assoc($r))
				{
		    		$orders_shop_id .= "'$l[shop_id]',";
			    }
				$orders_shop_id = substr($orders_shop_id,0,-1);
			    $orders_shop_id .= ")";
			}
		}

        //==========================
        // data count
        $query = "
			select count(*) as cnt	
			  from orders
		 left join ezauto_reg_log
				on orders.seq = ezauto_reg_log.seq 
			 where orders.status = 8";
			 
	    if ( $order_id == "" && $trans_no == "" )
	    {
	        $query .= " 
	           and orders.trans_date_pos >= '$start_date 00:00:00'
			   and orders.trans_date_pos <= '$end_date 23:59:59' ";
		}
			   
        $query .= " and orders.seq <> orders.order_id
			   and orders.c_seq = 0
			   and orders.order_id not rlike 'gift$' 
			   $orders_shop_id ";

        if ( $order_id )
            $query .= " and orders.order_id ='$order_id'";
        
        if ( $trans_no )
            $query .= " and orders.trans_no ='$trans_no'";

		if ( $chk_reg == "0" )
		{
			$query .= " and (ezauto_reg_log.seq is null or ( ezauto_reg_log.is_error = 1 and ezauto_reg_log.confirm = 0 ) )";
		}
		else if ( $chk_reg == "1")
		{
			$query .= " and (ezauto_reg_log.is_error = '0' or ezauto_reg_log.is_error is null )";
			$query .= " and ezauto_reg_log.seq is not null ";
		}
		else if ( $chk_reg == "2" )
		{	
			$query .= " and ezauto_reg_log.is_error = '1' and ezauto_reg_log.confirm = 1";
		}

//echo $query . "<br><br>";

        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_assoc( $result );
        $total_rows = $data[cnt];

        //==========================
        // data query
		$query = "
			select orders.seq as seq, 
		 	 	   orders.shop_id as shop_id, 
		 		   orders.order_id as order_id,
				   orders.order_id_seq as order_id_seq, 
				   orders.trans_no as trans_no,
				   orders.trans_corp as trans_corp,
				   orders.trans_date_pos as trans_date_pos, 

		 		   ezauto_reg_log.reg_date as reg_date,
		 		   ezauto_reg_log.last_update_date as last_update_date,
		 		   ezauto_reg_log.status as status,	
				   ezauto_reg_log.job_count as job_count,
				   ezauto_reg_log.division_code as division_code,
				   ezauto_reg_log.is_error as is_error,
				   ezauto_reg_log.error_msg as error_msg,
				   ezauto_reg_log.confirm as confirm
	
			  from orders
		 left join ezauto_reg_log
			    on orders.seq = ezauto_reg_log.seq 
			 where orders.status = 8";
			 
        if ( $order_id == "" && $trans_no == "" )
	    {
	        $query .= " 
			   and orders.trans_date_pos >= '$start_date 00:00:00'
			   and orders.trans_date_pos <= '$end_date 23:59:59' ";
	    }
		
	    $query .= " 
			   and orders.order_id not rlike 'gift$' 
			   and orders.c_seq = 0 
			   and orders.seq <> orders.order_id
			   $orders_shop_id ";

        if ( $order_id )
            $query .= " and orders.order_id ='$order_id'";
        
        if ( $trans_no )
            $query .= " and orders.trans_no ='$trans_no'";

		if ( $chk_reg == "0" )
			$query .= " and (ezauto_reg_log.seq is null or (ezauto_reg_log.is_error = 1 and ezauto_reg_log.confirm = 0))";
		else if ( $chk_reg == "1")
		{
			$query .= " and (ezauto_reg_log.is_error = '0' or ezauto_reg_log.is_error is null )";
			$query .= " and ezauto_reg_log.seq is not null ";
		}
		else if ( $chk_reg == "2" )
		{	
			$query .= " and ezauto_reg_log.is_error = '1' and ezauto_reg_log.confirm = 1";
		}

		if ( $order_by == "0" )	
			$query .= " order by orders.trans_date_pos desc";
		else 
			$query .= " order by ezauto_reg_log.reg_date desc";


        $starter = $page ? ($page-1) * $line_per_page : 0;        
        $query .= " limit $starter, $line_per_page";

//echo $query . "<br><br>";

        $result = mysql_query( $query, $connect );
        return $result;
	}


	function action_DL03()
	{
		global $connect, $seq, $new_confirm;

		$query = "update ezauto_reg_log set 
					confirm = '$new_confirm'
					where seq = '$seq'";

		$result = mysql_query ( $query, $connect );
	}

	function query_reg_log( &$total_rows )
	{
        global $connect,$shop_id, $start_date, $end_date, $template, $page,$chk_not_reg,$order_id,$trans_no;

        $line_per_page = 100;		
		$end_date = Date("Y-m-d H:i:s");

		if ( $shop_id )
			$orders_shop_id = " and orders.shop_id = $shop_id ";
		else
		{	
	    	$sql = "select shop_id 
					  from shopinfo 
					 where balju_stop = 0 
					   and shop_id % 100 < 80
					   and shop_id % 100 not in ( 43,22,20,41,49,17,45,13,52,33,67,56,62,65,37,47,77,25,78,79 )
					";

			if ( _DOMAIN_ == "huiz2" )
			{
		    	$sql .= " and shop_id % 100 not in ( 15,16 )";
			}

		    $r = mysql_query($sql, $connect);
		    $orders_shop_id = " and orders.shop_id in ( ";
						
			if ( mysql_num_rows( $r ) == 0 )
			{
				$orders_shop_id = " and orders.shop_id in ('')";	
			}
			else
			{
			    while ($l = mysql_fetch_assoc($r))
				{
		    		$orders_shop_id .= "'$l[shop_id]',";
			    }
				$orders_shop_id = substr($orders_shop_id,0,-1);
			    $orders_shop_id .= ")";
			}
		}

        //==========================
        // data count
        $query = "
			select count(*) as cnt	
			  from orders
		 left join ezauto_reg_log
				on orders.seq = ezauto_reg_log.seq 
			 where orders.status = 8";
			 
	    if ( $order_id == "" && $trans_no == "" )
	    {
	        $query .= " 
	           and orders.trans_date_pos >= '$start_date 00:00:00'
			   and orders.trans_date_pos <= '$end_date' ";
		}
			   
        $query .= " and orders.seq <> orders.order_id
			   and orders.c_seq = 0
			   and orders.order_id not rlike 'gift$' 
			   $orders_shop_id ";

        if ( $order_id )
            $query .= " and orders.order_id ='$order_id'";
        
        if ( $trans_no )
            $query .= " and orders.trans_no ='$trans_no'";

        if ( $chk_not_reg > 0 )
   	    {
			if ( $chk_not_reg == 1 )
				$query .= " and (ezauto_reg_log.seq is null or ezauto_reg_log.status <> 3) ";
  	    	if ( $chk_not_reg == 2 )
                $query .= " and ezauto_reg_log.status = 3 ";   
        }

debug($query);
                  
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_assoc( $result );
        $total_rows = $data[cnt];

        //==========================
        // data query
		$query = "
			select orders.seq as seq, 
		 	 	   orders.shop_id as shop_id, 
		 		   orders.order_id as order_id,
				   orders.order_id_seq as order_id_seq, 

		 		   ezauto_reg_log.reg_date as reg_date,
		 		   ezauto_reg_log.last_update_date as last_update_date,
		 		   ezauto_reg_log.trans_no as trans_no,
		 		   ezauto_reg_log.status as status,	
				   ezauto_reg_log.job_count as job_count,
				   ezauto_reg_log.division_code as division_code,
				   ezauto_reg_log.error_msg as error_msg
	
			  from orders
		 left join ezauto_reg_log
			    on orders.seq = ezauto_reg_log.seq 
			 where orders.status = 8";
			 
        if ( $order_id == "" && $trans_no == "" )
	    {
	        $query .= " 
			   and orders.trans_date_pos >= '$start_date 00:00:00'
			   and orders.trans_date_pos <= '$end_date' ";
	    }
		
	    $query .= " 
			   and orders.seq <> orders.order_id
			   and orders.order_id not rlike 'gift$' 
			   and orders.c_seq = 0 
			   $orders_shop_id ";
debug($query);

        if ( $order_id )
            $query .= " and orders.order_id ='$order_id'";
        
        if ( $trans_no )
            $query .= " and orders.trans_no ='$trans_no'";

        if ( $chk_not_reg > 0 )
        {
			if ( $chk_not_reg == 1 )
				$query .= " and (ezauto_reg_log.seq is null or ezauto_reg_log.status <> 3) ";
  	    	if ( $chk_not_reg == 2 )
                $query .= " and ezauto_reg_log.status = 3 ";   
        }

        $starter = $page ? ($page-1) * $line_per_page : 0;        
        $query .= " limit $starter, $line_per_page";
        debug( $query );
        $result = mysql_query( $query, $connect );
        return $result;
	}
	
   function download()
   {
        global $connect,$shop_id, $trans_corp, $start_date, $end_date, $template, $page,$chk_not_reg;

        $query   = "select seq,shop_id,order_id,trans_no,trans_corp,trans_date,status
                           trans_date_pos,auto_trans,auto_trans_date,order_cs from orders ";
        $query_cnt = "select count(*) cnt from orders ";

        $options = "where trans_date_pos >= '$start_date 00:00:00'
                     and trans_date_pos <= '$end_date 23:59:59' ";

        if ( $shop_id )
            $options.= " and shop_id='$shop_id'";

        if ( $trans_corp != 99)
            $options.= " and trans_corp ='$trans_corp'";
        
        // 미배송 자료만 조회            
        if ( $chk_not_reg )
            $options .= " and auto_trans = 0 ";    
        else if( $chk_not_reg == 2)
            $options .= " and auto_trans = 2 ";        
            
        $limit = " group by trans_no";

//echo $query . $options . $limit;

        $result = mysql_query ( $query . $options . $limit , $connect );

        $arr_data = array();
        $arr_data[] = array( "관리번호","주문번호","송장번호","상태","송장등록일","배송일","배송확인일","배송확인여부" );

        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_data[] = array(
                $data[seq]
                ,$data[order_id]
                ,$data[trans_no]
                ,$data[status]
                ,$data[order_cs]
                ,$data[trans_date]
                ,$data[trans_date_pos]
                ,$data[auto_trans_date]
                ,$data[auto_trans]
            );
        }

        $obj_file = new class_file();
        $obj_file->download( $arr_data, "reg_error.xls" );
   } 

   function query()
   {
        global $connect,$shop_id, $trans_corp, $start_date, $end_date, $template, $page,$chk_not_reg;

		// 송장전송대기에서 자동발주를 하지 않는 판매처는 제외
		$sql = "select shop_id from shopinfo where balju_stop = 1";
		$result = mysql_query($sql, $connect);
		$skip_option = "and shop_id not in ( ";
		while ($list = mysql_fetch_assoc($result)) {
			  $skip_option .= "$list[shop_id] ,";
		}
		$skip_option .= "'')";

        $line_per_page = _line_per_page;
        $link_url = "?" . $this->build_link_url();
        $starter = $page ? ($page-1) * $line_per_page : 0;

        $query   = "select count(*) cnt, seq,shop_id,recv_name,order_id,trans_no,trans_corp,trans_date,
                           trans_date_pos,auto_trans,auto_trans_date,order_cs from orders ";
        $query_cnt = "select count(distinct trans_no) cnt from orders ";

        $options = "where trans_date_pos >= '$start_date 00:00:00'
                     and trans_date_pos <= '$end_date 23:59:59' 
                     and shop_id%100 in (1,2,50,6,9,38,7,3,29,76,51,5)
					 $skip_option
                     and substr(order_id,1,1) <> 'C'
					 and order_cs not in (1,2) ";

        if ( $shop_id )
            $options.= " and shop_id='$shop_id'";

        if ( $trans_corp != 99)
            $options.= " and trans_corp ='$trans_corp'";
        
        // 미배송 자료만 조회
        if ( $chk_not_reg == 1)
            $options .= " and auto_trans = 0 ";            
        else if( $chk_not_reg == 2)
            $options .= " and auto_trans = 2 ";            

        // 미전송 개수
        $_query = "select count(distinct trans_no) cnt from orders " . $options . " and auto_trans=0 ";
        
        $result = mysql_query( $_query , $connect );
        $data   = mysql_fetch_assoc( $result );
        $no_reg_cnt = $data[cnt];

        // 총 개수
        $result = mysql_query ( $query_cnt . $options . $limit , $connect );
        $data   = mysql_fetch_array ( $result );
        $total_rows = $data[cnt];

        $limit = " group by trans_no,auto_trans";
        $limit .= " order by trans_date_pos limit $starter, $line_per_page";
        
        //if ( $_SESSION[LOGIN_LEVEL] == 9 )
            //echo $query . $options . $limit ;
        
        // list 구하기
        //echo $query . $options. $limit;
        $result = mysql_query ( $query . $options . $limit , $connect );
        include "template/D/DL00.htm";
   }

   function get_cnt()
   {
        global $connect,$shop_id, $trans_corp, $start_date, $end_date, $template, $page;

        $start_date = $start_date ? $start_date : date('Y-m-d', strtotime('-2 day'));
        $end_date   = $end_date ? $end_date : date('Y-m-d');

        $arr_cnt = array(
            total_rows  => 0
            ,no_reg_cnt => 0
        );

        $query_cnt = "select count(distinct trans_no) cnt from orders ";

        $options = "where trans_date >= '$start_date 00:00:00'
                     and trans_date <= '$end_date 23:59:59' ";

        if ( $shop_id )
            $options.= " and shop_id='$shop_id'";

        if ( $trans_corp != 99)
            $options.= " and trans_corp ='$trans_corp'";

        // 미전송 개수
        $_query = "select count(distinct trans_no) cnt from orders " . $options . " and auto_trans=0 ";

        $result = mysql_query( $_query . $limit, $connect );
        $data   = mysql_fetch_assoc( $result );
        $no_reg_cnt = $data[cnt];

        // 총 개수
        $result = mysql_query ( $query_cnt . $options . $limit , $connect );
        $data   = mysql_fetch_array ( $result );
        $total_rows = $data[cnt];

        $arr_cnt = array(
            total_rows  => $total_rows
            ,no_reg_cnt => $no_reg_cnt
        );

        return $arr_cnt;
   }

   // orders의 필요한 정보 구함
   function get_info( $seq )
   {
        global $connect;
        $query  = "select product_name,status, order_cs,qty from orders where seq=$seq";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_array ( $result );
        return $data;
   }

    //
    function remove()
    {
        global $connect, $seq;
        
        $query = "update orders set auto_trans=2 where seq=$seq";
        mysql_query( $query, $connect );
        echo $query;
    }

	function delete_from_DL01( )
	{
		global $connect, $seq;

		$query = "select * from orders where seq = '$seq'";
		$result = mysql_query( $query, $connect );

		$list = mysql_fetch_assoc( $result );

		$query = "update orders set auto_trans = 3 where seq = '$seq'";
		mysql_query( $query, $connect );

		$query = "insert into ezauto_reg_log set
					seq = '$list[seq]',
					order_id = '$list[order_id]',
					order_id_seq = '$list[order_id_seq]',
					shop_id = '$list[shop_id]',
					trans_no = '$list[trans_no]',
					reg_date = now(),
					last_update_date = now(),
					status = 3,
					error_msg = '삭제'
				  on duplicate key update 
					status = 3";
		
		mysql_query ( $query, $connect );
		echo $query;
	}

   function set_cancel()
   {
        global $connect, $seq;

        // E804의 로직을 가져옴
        $query  = "select seq, product_name, status, order_cs, pack from orders where seq='$seq'";
        $result = mysql_query ( $query, $connect );
        $data   = mysql_fetch_array ( $result );

        $obj = new class_cs();

        if ( $data[order_cs] == 0 )
            $obj->change_status2( $seq, $data[status], 2  ); // 배송 후 취소
        else
            $obj->change_status2( $seq, $data[status], 0  ); // 정상

        $obj->csinsert($seq, "송장등록 오류로 인한 취소 / $data[order_cs]");

        // 3pl을 사용하는 업체의 경우
        $_info = class_order::get_order( $seq );
        if ( $_SESSION[USE_3PL] )
        {
            $infos[order_cs] = $_info[order_cs];      // 배송 후 교환 요청
            $obj->sync_infos( $infos, $_seq );
        }
        $val = array();
        $val[order_cs] = $_info[order_cs];
        echo json_encode( $val );
   }

	function get_balju_time()
	{
		global $connect, $start_date, $end_date, $shop_id;

        if (!$start_date) 
            $start_date = date('Y-m-d');
        if (!$end_date) 
            $end_date = date('Y-m-d');

		$data = array();
		$query = "select * from ezauto_balju_time
					where date(crdate) >= '$start_date'
					and date(crdate) <= '$end_date' 
					and sub_seq = 0";
		$result = mysql_query( $query, $connect );
		while( $list = mysql_fetch_assoc( $result ) )
		{
			$data[ $list[seq] ]['start_time'] = $list[start_time] ;
			$data[ $list[seq] ]['end_time'] = $list[end_time] ;
			$data[ $list[seq] ]['worker'] = $list[worker] ; 
	
			$query_2 = "select * from ezauto_balju_time 
						where sub_seq = $list[seq]
						and date(crdate) >= '$start_date'
						and date(crdate) <= '$end_date' ";

			$result_2 = mysql_query( $query_2, $connect );
			while( $list_2 = mysql_fetch_assoc( $result_2 ) )
			{
				$shop_info = $this->get_shop_name($list_2[shop_id]);

				$data[ $list[seq] ][ 'data' ][ $list_2[seq] ] = array ( 
					"crdate"  	 	 => $list_2[crdate],
					"shop_id"    	 => $list_2[shop_id],
					"shop_id_name" 	 => $shop_info . " (" . $list_2[shop_id] . ")",
					"start_time" 	 => $list_2[start_time],
					"end_time" 	 	 => $list_2[end_time],
					"success"	 	 => $list_2[success],
					"result"	 	 => $list_2[result]
				);	
			}
		}
		return $data;
	}

	function get_cancel_time()
	{
		global $connect, $start_date, $end_date, $shop_id;

        if (!$start_date) $start_date = date('Y-m-d');
        if (!$end_date) $end_date = date('Y-m-d');

		$data = array();
		$query = "select * from ezauto_cancel_time
					where date(crdate) >= '$start_date'
					and date(crdate) <= '$end_date' 
					and sub_seq = 0";
		$result = mysql_query( $query, $connect );
		while( $list = mysql_fetch_assoc( $result ) )
		{
			$data[ $list[seq] ]['start_time'] = $list[start_time] ;
			$data[ $list[seq] ]['end_time'] = $list[end_time] ;
			$data[ $list[seq] ]['worker'] = $list[worker] ; 
	
			$query_2 = "select * from ezauto_cancel_time 
						where sub_seq = $list[seq]
						and date(crdate) >= '$start_date'
						and date(crdate) <= '$end_date' ";

			if ( $shop_id )
				$query_2 .= " and shop_id = '$shop_id'";

			$result_2 = mysql_query( $query_2, $connect );
			while( $list_2 = mysql_fetch_assoc( $result_2 ) )
			{
				$shop_info = $this->get_shop_name($list_2[shop_id]);

				$data[ $list[seq] ][ 'data' ][ $list_2[seq] ] = array ( 
					"crdate"  	 	 => $list_2[crdate],
					"shop_id"    	 => $list_2[shop_id],
					"shop_id_name" 	 => $shop_info . " (" . $list_2[shop_id] . ")",
					"start_time" 	 => $list_2[start_time],
					"end_time" 	 	 => $list_2[end_time],
					"success"	 	 => $list_2[success],
					"result"	 	 => $list_2[result]
				);	
			}
		}
		return $data;
	}

	function get_upload_time()
	{
		global $connect, $start_date, $end_date, $shop_id;

        if (!$start_date) $start_date = date('Y-m-d');
        if (!$end_date) $end_date = date('Y-m-d');

		$data = array();
		$query = "select * from ezauto_upload_time
					where date(crdate) >= '$start_date'
					and date(crdate) <= '$end_date' 
					and sub_seq = 0";
		$result = mysql_query( $query, $connect );
		while( $list = mysql_fetch_assoc( $result ) )
		{
			$data[ $list[seq] ]['start_time'] = $list[start_time] ;
			$data[ $list[seq] ]['end_time'] = $list[end_time] ;
			$data[ $list[seq] ]['worker'] = $list[worker] ; 
	
			$query_2 = "select * from ezauto_upload_time 
						where sub_seq = $list[seq]
						and date(crdate) >= '$start_date'
						and date(crdate) <= '$end_date' ";

			if ( $shop_id )
				$query_2 .= " and shop_id = '$shop_id'";

			$result_2 = mysql_query( $query_2, $connect );
			while( $list_2 = mysql_fetch_assoc( $result_2 ) )
			{
				$shop_info = $this->get_shop_name($list_2[shop_id]);

				$data[ $list[seq] ][ 'data' ][ $list_2[seq] ] = array ( 
					"crdate"  	 	 => $list_2[crdate],
					"shop_id"    	 => $list_2[shop_id],
					"shop_id_name" 	 => $shop_info . " (" . $list_2[shop_id] . ")",
					"start_time" 	 => $list_2[start_time],
					"end_time" 	 	 => $list_2[end_time],
					"success"	 	 => $list_2[success],
					"result"	 	 => $list_2[result]
				);	
			}
		}
		return $data;
	}

	function get_shop_name( $shop_id )
	{
		global $connect;
		
		$query = "select * from shopinfo where shop_id = '$shop_id'";
		$result = mysql_query( $query, $connect );
	
		$list = mysql_fetch_assoc( $result );
		$shop_name = $list[shop_name];

		return $shop_name;
	}
}
