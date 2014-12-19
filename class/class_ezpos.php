<?
///////////////////////////////////////////
//
//	date : 2014.11.17
//	worker : icy
//	name : class_ezpos
//
//

include_once "../lib/config_api.php";
include_once "class_db.php" ;
include_once "class_stock.php" ;

class class_ezpos
{
	public $domain;
	public $userid;
	public $connect;
	public $start_date;

	// 생성자 
	function __construct( $domain, $userid )
	{
		$this->domain = $domain;
		$this->userid = $userid;
		$this->connect = $this->connect_manual( $domain );
		$this->start_date = date("Y-m-d", strtotime("-490 day"));
	}

	function debug($str)
	{
		$today = date('Ymd');
        $logfile = "/home/ezadmin/public_html/log/class_" . $domain . "_" . $today . ".log";
        $fp = @fopen($logfile, "a+");
        $output = date("Y/m/d H:i:s")." ". $str."\n";
        @fwrite($fp, $output);
        @fclose($fp);
	}

	function connect_manual( $domain )
	{
		$sys_connect = mysql_connect(_MYSQL_SYS_HOST_, _MYSQL_SYS_ID_, _MYSQL_SYS_PASSWD_);
		mysql_select_db(_MYSQL_SYS_DB_, $sys_connect);

		$sql = "select * from sys_domain where id = '$domain'";
		$syscon = mysql_fetch_assoc(mysql_query($sql, $sys_connect));

		$odb = new class_db();
		$connect = $odb->connect( $syscon['host'], $syscon['id'], $syscon['db_pwd'] );

		return $connect;
	}

	function delete_log()
	{


	}

	function get_trans_info( $trans_no )	
	{
		$trans_info = array();

		$query = "select * from orders 
					where trans_no = '$trans_no'
					and trans_date > '$this->start_date'
					and status in ( 7, 8 )
					order by hold desc";
		$result = mysql_query( $query, $this->connect );

		$total_rows = mysql_num_rows( $result );
		if ( $total_rows == 0 )
		{
			$trans_info["error"] = "1";
			$trans_info["msg"] = "조회값이 없습니다";

			return $trans_info;
		}

		while ( $list = mysql_fetch_assoc( $result ) )
		{
			if ( !$trans_info  )
				$trans_info = $list;

			if ( $list["status"] == 8 )
			{
				$trans_info["error"] = "1";
				$trans_info["msg"] = "이미 배송되었습니다 (" . $list["trans_date_pos"] . ")" ;
				return $trans_info;		
			}

			$trans_info["error"] = "0";

			switch( $list["hold"] )
			{
				case 6 :
					$trans_info["msg"] = "합포 변경";
					return $trans_info;
				case 5 :
					$trans_info["msg"] = "부분 취소";
					return $trans_info;
				case 4 :
					$cancel_hold++;
				case 3 :
					$change_hold++;
				case 2 : 
					$address_hold++;
				case 1 : 
					$normal_hold++;	
			}
		}
		
		$trans_info["hold"] = "0";
		$trans_info["msg"] = "정상";	
	
		if ( $normal_hold > 0 )
		{
			$trans_info["hold"] = "10";
			$trans_info["msg"] = "일반 보류";
		}

		if ( $address_hold > 0 ) 
		{
			$trans_info["hold"] = "1";
			$trans_info["msg"] = "주소 변경";
		}

		if ( $change_hold > 0 )
		{
			if ( $total_rows == $change_hold )
			{
				$trans_info["hold"] = "21";
				$trans_info["msg"] = "전체 교환";
			}
			else
			{
				$trans_info["hold"] = "11";
				$trans_info["msg"] = "부분 교환";
			}
		}

		if ( $cancel_hold > 0 )
		{
			if ( $total_rows == $cancel_hold )
			{
				$trans_info["hold"] = "22";
				$trans_info["msg"] = "전체 취소";
			}
			else 
			{
				$trans_info["hold"] = "12";
				$trans_info["msg"] = "부분 취소";
			}
		}

		return $trans_info;
	}

	function get_stock_manage_use()
	{
	    $query = "select stock_manage_use from ez_config";
	    $result = mysql_query( $query, $this->connect );
	    $list = mysql_fetch_assoc( $result );
    	$stock_manage_use = $list["stock_manage_use"];

		return $stock_manage_use;
	}

	function get_product_detail( $product_id )
	{
		$query = "select * from products where product_id = '$product_id'";
		$result = mysql_query( $query, $this->connect );
		$list = mysql_fetch_assoc( $result );

		return $list;
	}

	function get_product_info( $trans_no )
	{
		$product_info = array();

		$stock_manage_use = $this->get_stock_manage_use();
	    $arr_order_cs = array( 
                0  => "정상"
                ,1 => "취소"
                ,2 => "취소"
                ,3 => "취소"
                ,4 => "취소"
                ,5 => "교환"
                ,6 => "교환"
                ,7 => "교환"
                ,8 => "교환"
        );

		$order_seqs = $this->get_seqs( $trans_no );            
        $query = "select product_id, qty, shop_options, order_cs
					from order_products
					where order_seq in ( $order_seqs )
					order by product_id";

//echo $query . "\n";
		$result = mysql_query( $query, $this->connect );
        while( $list = mysql_fetch_assoc( $result ) )
        {
			$product_detail = $this->get_product_detail( $list["product_id"] );

			$list["name"] = $product_detail["name"];

			if ( $stock_manage_use != "0" )
				$list["options"] = $product_detail["options"];
						
			$list["order_cs"] = $arr_order_cs[ $list["order_cs"] ];
			$list["enable_stock"] = $product_detail["enable_stock"];
			$list["barcode"] = $product_detail["barcode"];

			$product_info[] = $list;
		}

		return $product_info;
	}

	function get_cs_info( $trans_no )
	{
		$cs_info = "";
		$str_seq = $this->get_seqs( $trans_no );

	    if ( $str_seq )
	    {
	        $query = "select * from csinfo 
						where order_seq in ( $str_seq ) 
						order by seq desc limit 10";
    	    $result = mysql_query( $query, $this->connect );
	        while ( $data = mysql_fetch_assoc( $result ) )
    	    {
	            $cs_info .= "$data[writer] / $data[input_date] $data[input_time] \r\n";
    	        $cs_info .= "$data[content]  \r\n";
        	    $cs_info .= "$data[user_content]  \r\n";
	            $cs_info .= "-----------------------------------\r\n";
    	    }
	    }

		return $cs_info;
	}

	function unhold( $trans_no )
	{
	    $query = "select seq 
					from orders 
					where trans_no = '$trans_no'";
	    $result = mysql_query( $query, $this->connect );

		if ( mysql_num_rows( $result ) > 0 )
		{
			$query = "update orders set
						hold = 0 
						where trans_no = '$trans_no'";
			mysql_query( $query, $this->connect );
		    while ( $data = mysql_fetch_assoc( $result ) )
		    {
		        $query = "insert into csinfo set 
							order_seq  = '$data[seq]',
							input_date = now(),
							input_time = now(),
							writer     = '$this->userid',
							cs_result  = '1',
							content    = '보류 해제'";

    	        mysql_query ( $query, $this->connect );
			}
		}
	}

	function check_complete( $trans_no )
	{
	    $query = "select seq 
					from orders 
					where trans_no = '$trans_no'";
	    $result = mysql_query( $query, $this->connect );

		$rows = mysql_num_rows( $result );

		if ( $rows > 0 )
		{
		    while ( $data = mysql_fetch_assoc( $result ) )
		    {
		        $query = "insert into csinfo set 
							order_seq  = '$data[seq]',
							input_date = now(),
							input_time = now(),
							writer     = '$this->userid',
							cs_result  = '1',
							content    = '검품 완료'";

    	        mysql_query ( $query, $this->connect );
			}
		}
		
		return $rows;
	}

	function get_seqs( $trans_no )
	{
		$str_seqs = "";

        $query = "select seq from orders 
                    where trans_no = '$trans_no'
					and trans_date >= '$this->start_date'";

        $result = mysql_query( $query, $this->connect );
        $str_seq = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $str_seq .= $str_seq ? "," : "";
            $str_seq .= $data[seq];
        }

		return $str_seq;
	}

	function init_pack( $trans_no, $scanned_products )
	{
		$order_seqs = $this->get_seqs( $trans_no );
		
        $query = "select seq,order_seq,product_id,qty from order_products
                   where order_seq in ($order_seqs)
                     and order_cs not in (1,2,3,4)
                   order by order_seq ";

//echo $query . "\n";

        $result = mysql_query( $query, $this->connect );
        $_arr_result = array();  // DB에 있는 값으로 array를 만든다.
        while( $data = mysql_fetch_assoc( $result ) )
        {
            // qty 개수만큼 array를 생성한다.
            $_arr_result[] = array(
                product_seq          => $data[seq]
                ,order_seq           => $data[order_seq]
                ,qty                 => $data[qty]
                ,product_id          => $data[product_id]
                ,scaned              => 0
            );
        }	

//print_r( $_arr_result );

        $arr_products = split(",", $scanned_products);
      
        foreach( $arr_products as $product )
        {
            // check 표시를 한다.
            for( $i = 0; $i < count( $_arr_result ); $i++ )
            {    
                if ( $_arr_result[$i]['product_id'] == $product )
                {
                    if ( $_arr_result[$i]['qty'] > $_arr_result[$i]['scaned'] )
                    { 
                        $_arr_result[$i]['scaned']++;
                        break;
                    }
                }
            }
        }

//print_r( $_arr_result );

        // check가 0인 주문은 합포를 분리한다.
        // order_seq_check_all이 0인 주문은 orders를 복사한 후 order_seq를 변경한다.
        // i == qty 일 경우 qty개수로 주문 생성
        // i < qty 일 경우 원 주문은 i - 1, 신규 주문은 qty - i인 주문을 생성한다.
        $arr_unpack = array();  // 접수 상태가 될 주문.
        $arr_pack   = array();  // 배송 상태가 될 주문

        for( $i = 0; $i < count( $_arr_result)  ; $i++ )
        {
			if ( $_arr_result[$i]['scaned'] > 0 )
            {
                 // 배송.
                 $arr_pack[] = $_arr_result[$i]['order_seq'];
            }
        }

        for( $i = 0; $i < count( $_arr_result)  ; $i++ )
        {
            // 합포 분리
            if ( $_arr_result[$i]['scaned'] == 0 )
            {
                $is_exist = 0; // false
                foreach( $arr_pack as $p )
                {
                    if ( $p == $_arr_result[$i]['order_seq'] )
                    {
                        $is_exist = 1; // true
                        break;
                    }
                }

                if ( $is_exist )
                {
                    // 주문을 분리해야 함.
                    $arr_unpack[] = $this->copy_order_pack($_arr_result[$i]['order_seq'], $_arr_result[$i]['product_seq'], $_arr_result[$i]['qty']);
                }
                else
                {
                    // 미배송 합포 분리
                    $arr_unpack[] = $_arr_result[$i]['order_seq'];
                }
            }
            else
            {
                // 주문 분리 후 합포 분리.
                if ( $_arr_result[$i]['qty'] != $_arr_result[$i]['scaned']  )
                {
                    // 주문 분리..
                    // qty에 입력한 값이 합포 분리됨..
                    //echo "copy order pack";
                    $qty = $_arr_result[$i]['qty'] - $_arr_result[$i]['scaned'];

                    // 미배송
                    $arr_unpack[] = $this->copy_order_pack($_arr_result[$i]['order_seq'], $_arr_result[$i]['product_seq'], $qty);
                }
            }
        }
		
        // check가 0인 주문은 합포를 분리한다.
        // 미배송 분리된 주문끼리 다시 합포를 한다.
        $this->change_pack( $arr_unpack );

        // 배송건들의 합포 번호를 변경해준다.
        $this->change_pack_only( $arr_pack,$order_seqs );
	}

    //
    // pack 번호 변경, 상태도 접수로 변경
    //
    function change_pack( $arr_unpack )
    {
        if ( count( $arr_unpack) > 0 )
        {
            $str_pack = "";
            foreach ( $arr_unpack as $seq )
            {
                $str_pack .= $str_pack ? "," : "";
                $str_pack .= $seq;
            }

            $query = "update orders 
                         set status=1, trans_no='', trans_date='', trans_corp='', pack=" . $arr_unpack[0] . " 
                       where seq in ( $str_pack )";

//            debug( "change_pack: $query" );

            mysql_query( $query, $this->connect );

            // pack 주문이 1개일 경우 pack => 0 으로 수정
            $this->check_pack_only( $arr_unpack[0] );

            return $query;
        }
    }

    // 합포주문이 1개인지 확인해서 합포를 삭제한다.
    function check_pack_only( $_pp )
    {
        if ( $_pp )
        {
            $query = "select count(*) cnt from orders where pack=$_pp";
            $result = mysql_query( $query, $this->connect );
            $data   = mysql_fetch_assoc( $result );

            if ( $data[cnt] == 1 )
            {
                $query = "update orders set pack=0 where pack=$_pp";
                mysql_query( $query, $this->connect );
            }
        }
    }

    // 배송건의 
    // pack번호만 변경
    function change_pack_only( $arr_pack, $order_seqs )
    {
        if ( count( $arr_pack) > 0 )
        {
            $str_pack = "";
            foreach ( $arr_pack as $seq )
            {
                $str_pack .= $str_pack ? "," : "";
                $str_pack .= $seq;
            }

            $query = "update orders set pack=" . $arr_pack[0] . " where seq in ( $str_pack )";
            mysql_query( $query, $this->connect );

            // 취소건은 배송건과 함께 묶어준다.
            $query = "update orders set pack=" . $arr_pack[0] . " where seq in ( $order_seqs ) and order_cs in (1,2,3,4)";
            mysql_query( $query, $this->connect );

            $this->check_pack_only( $arr_pack[0] );
        }
    }	

	function trans_pos( $trans_no )
	{
		$arr_products = $this->get_product_info( $trans_no );	
		$rows = $this->set_trans( $this->domain, $trans_no, $arr_products);
		
		return $rows;
	}

    //*******************************
    // 재고 처리 - 11.2 수정됨 jkh
    // global userid 처리 - jk
    function stock_deliv( $product )
    {
        $obj = new class_stock();
		$data = array(  type       => 'trans',
                        product_id => $product[product_id],
						bad        => 0,
						location   => 'Def',
						qty        => $product[qty],
						memo       => 'pos',
						order_seq  => $product[order_seq]
				);
        $obj->set_stock( $data, $this->userid);
    }

    //*******************************
    // 배송 처리
    function set_trans($id, $trans_no, $arr_products)
    {
        $trans_no = trim($trans_no);
        $query = "update orders set 
						trans_date_pos = Now(), 
						status = 8 
					where trans_no = '$trans_no' 
					  and order_cs in (0,2,5,6)";

        mysql_query( $query, $this->connect );
	
		$rows = mysql_affected_rows();

		if ( $rows > 0 )
		{
	        // 재고 차감..
			$this->debug ( "재고 차감 시작" );
	        foreach( $arr_products as $product )
    	    {
				if ( $product["order_cs"] == "1" || $product["order_cs"] == "2" )
					continue;

	            // 로그를 쌓는다.
				$this->debug ( " stock out id: $id / pid:" . $product[product_id] . " / qty: $product[qty] / order_seq: $product[order_seq]" );
	            $this->db_debug( $product[order_seq],$product[product_id],$product[qty], $trans_no );
    	        $this->stock_deliv( $product );
	        }
		}

		return $rows;
    }

    // db에 로그 남김
    function db_debug( $order_seq, $product_id, $qty, $trans_no)
    {
        $query = "insert ezpos_log 
                     set order_seq  = $order_seq
                        ,product_id = '$product_id'
                        ,qty        = $qty
                        ,crdate     = Now()
                        ,trans_no   = '$trans_no' 
        ";

        mysql_query( $query, $this->connect );
    }

    //////////////////////////////////////////////////////////
    // 합포 제외용 주문 복사
    // 
    // pack=0, 정산코드=0, supply_price=0, amount=0
    //
    function copy_order_pack($seq, $seq_prd, $qty)
    {
        // 원본 orders
        $query = "select * from orders where seq=$seq";
        $result = mysql_query($query, $this->connect);
        $data = mysql_fetch_assoc($result);
        
        // 원본 order_products 
        $query = "select * from order_products where seq=$seq_prd";
        $result = mysql_query($query, $this->connect);
        $data_prd = mysql_fetch_assoc($result);
        
        // 복사 쿼리 orders
        $new_query = "insert orders set ";
        foreach( $data as $key => $val )
        {
            if( $key == "seq"        ||
                $key == "trans_no"   ||
                $key == "trans_date" ||
                $key == "trans_corp" ) continue;
            
            if( $key == "pack"         ||
                $key == "hold"         ||
                $key == "trans_key"    )
                $new_query .= "$key=0,";
            else if( $key == "amount"       ||
                     $key == "supply_price" ||
                     $key == "code11"       ||
                     $key == "code12"       ||
                     $key == "code13"       ||
                     $key == "code14"       ||
                     $key == "code15"       ||
                     $key == "code16"       ||
                     $key == "code17"       ||
                     $key == "code18"       ||
                     $key == "code19"       ||
                     $key == "code20"       ||
                     $key == "code31"       ||
                     $key == "code32"       ||
                     $key == "code33"       ||
                     $key == "code34"       ||
                     $key == "code35"       ||
                     $key == "code36"       ||
                     $key == "code37"       ||
                     $key == "code38"       ||
                     $key == "code39"       ||
                     $key == "code40"       )
            {
                if( !$data_prd[product_type] && !$data[unpack_type] && $org_change )
                    $new_query .= "$key=$val,";
                else
                    $new_query .= "$key=0,";
            }
            else if( $key == "status" )
                $new_query .= "status=1,";
            else if( $key == "cs_priority" )
                $new_query .= "cs_priority=$data[cs_priority],";
            else if( $key == "gift" )
                $new_query .= "gift='',";
            else if( $key == "unpack_type" )
                $new_query .= "$key=2,";
            else if( $key == "unpack_org" )
                $new_query .= "$key=$seq,";
            else if( $key == "org_seq" )
            {
                // 상품이 추가상품일 경우, 정산 데이터를 기준 주문에서 승계하지 않는다
                if( $data_prd[product_type] )
                    $new_query .= "$key=0,";
                else
                {
                    // 원 주문이 합포찢어진 주문이면, 원 주문의 기본 주문 seq를 가져온다.
                    if( $data[unpack_type] )
                        $new_query .= "$key=$data[unpack_org],";
                    else
                    {
                        if( $org_change )
                            $new_query .= "$key=0,";
                        else
                            $new_query .= "$key=$seq,";
                    }
                }
            }
            else if( $key == "order_id" && $data_prd[is_gift] > 0 )
                $new_query .= "order_id='". $val . "_사은품(" . $seq . ")',";
            else if( $key == "order_cs" )
            {
                // 원 주문이 부분취소
                if( $data[order_cs] == 2 )
                {
                    // 취소상품 복사
                    if( $data_prd[order_cs] == 2 )
                        $new_query .= "$key=1,";
                    // 교환 상품이 복사되는 경우
                    else if( $data_prd[order_cs] == 6 )
                        $new_query .= "$key=5,";
                    // 정상 상품이 복사되는 경우
                    else if( $data_prd[order_cs] == 0 )
                        $new_query .= "$key=0,";
                    else
                        $new_query .= "$key=$val,";
                }
                // 원 주문이 부분교환
                else if( $data[order_cs] == 6 )
                {
                    // 교환상품 복사
                    if( $data_prd[order_cs] == 6 )
                        $new_query .= "$key=5,";
                    // 정상 상품이 복사되는 경우
                    else if( $data_prd[order_cs] == 0 )
                        $new_query .= "$key=0,";
                    else
                        $new_query .= "$key=$val,";
                }
                else
                    $new_query .= "$key=$val,";
            }
            else if( $val === "" )
                $new_query .= "$key='',";
            else if( $val === null )
                continue;
            else
                $new_query .= "$key='$val',";
        }
        // 마지막 , 삭제
        $new_query = substr($new_query, 0, -1);

//echo $new_query . "\n";

        mysql_query($new_query,$this->connect);

        // seq 읽어오기
        $query = "select seq, order_cs, recv_tel, recv_mobile, order_tel, order_mobile from orders where status in (1,7) and recv_name='$data[recv_name]' order by seq desc limit 1";
        $result = mysql_query($query, $this->connect);
        $data = mysql_fetch_assoc($result);
        $new_seq = $data[seq];

        // 전화번호 검색
        $this->inset_tel_info($new_seq, array($data[recv_tel],$data[recv_mobile],$data[order_tel],$data[order_mobile]));

        // 복사 쿼리 order_products
        $new_query = "insert order_products set ";
        foreach( $data_prd as $key => $val )
        {
            if( $key == "seq" || !$val ) continue;
            
            if( $key == "order_seq" )
                $new_query .= "order_seq=$new_seq,";
            else if( $key == "order_cs" )
                $new_query .= "order_cs=$data[order_cs],";
            else if( $key == "qty" )
                $new_query .= "qty=$qty,";
            else if( $val === "" )
                $new_query .= "$key='',";
            else if( $val === null )
                continue;
            else
                $new_query .= "$key='$val',";
        }
        // 마지막 , 삭제
        $new_query = substr($new_query, 0, -1);
        mysql_query($new_query,$this->connect);

        if( $data_prd[qty] > $qty )
        {
            // 제외되는 주문 수량 변경
            $query = "update order_products set qty=qty-$qty where seq=$seq_prd";
            $result = mysql_query($query, $this->connect);
        }
        else
        {
            // 제외되는 주문 삭제
            $query = "delete from order_products where seq=$seq_prd";
            $result = mysql_query($query, $this->connect);
        }   
        return $new_seq;
    }

    //###############################
    // CS 전화번호 검색 입력
    //###############################
    function inset_tel_info($seq, $org_tel)
    {
        $new_tel = array();
        foreach($org_tel as $tel_val)
        {
            $_tel = preg_replace('/[^0-9]/','',$tel_val);
            if( strlen($_tel) >= 4 )
            {
                $new_tel[] = array(
                    "tel"       => $_tel,
                    "tel_short" => substr($_tel, -4)
                );
            }
        }
        
        $new_tel = array_unique($new_tel);
        foreach($new_tel as $tel_val)
        {
            $query_ins = "insert tel_info set seq=$seq, tel='$tel_val[tel]', tel_short='$tel_val[tel_short]' ";
            mysql_query($query_ins, $this->connect);
        }
    }
}
?>
