<?
// abort user closing
ignore_user_abort(true);

require_once "class_top.php";
require_once "class_D.php";
require_once "class_E.php";
require_once "class_file.php";
require_once "class_stock.php";
require_once "class_lock.php";
include "template/inc/box4u_func.inc";
//require "lib/ez_trans_lib.php";

class class_D500 extends class_top 
{
   var $g_order_id;
   
    function D500()
    {
        global $template, $trans_corp, $default_format;
        $line_per_page = _line_per_page;
        
        $link_url = "?" . $this->build_link_url();
        $arr_result = $this->count_list();
        
        $arr_result2 = $this->get_error();
        
        if( !$trans_corp )
            $trans_corp = $_SESSION["BASE_TRANS_CODE"];
        
        // 관리번호, 송장번호 위치 구하기
        $this->get_pos($trans_corp, &$pos_seq, &$pos_transno);
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
 
    function get_error()
    {
        global $connect;
        static $arr_data;
        
        $query = "select a.collect_date,a.seq,a.order_id,a.status,a.trans_no order_trans_no
                        , b.crdate,b.trans_no upload_trans_no
                        , a.order_cs 
            from orders a, trans_upload_log b  
            where a.seq = b.order_seq  
            and b.crdate >='" . Date("Y-m-d") . " 00:00:00'  
            and a.status not in (7,8)";   
        
        //echo $query;
        
        $result = mysql_query( $query, $connect );
        
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $arr_data[] = $data;   
        }
        
        return $arr_data;
    }

	//-------------------------------------
	//
	// 배송처리
	//	date: 2013.2.27 
	//-------------------------------------
    function D503()
    {
        global $template;
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }


   function D501()
   {
      global $template;

      $link_url = "?" . $this->build_link_url();

      $result = $this->trans_today2( &$total_rows ); 

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   //========================================
   //
   // date: 2007.5.30 - jk
   // pos확인해서 목록 삭제
   //
   function D502()
   {
      global $template;
      $link_url = "?" . $this->build_link_url();
      $result = $this->trans_today2( &$total_rows ); 
      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

	//
	// 배송처리
	//
    function set_trans( $arr_data )
	{
		global $connect;
		// 송장을 올리면 무조건 배송 처리를 한다.
		$str_trans_no = "";
		
		foreach( $arr_data as $d )
		{
			$trans_no     = trim( $d[0] );

			if ( $trans_no )
			{
			    $str_trans_no .= $str_trans_no ? "," : "";
			    $str_trans_no .= $trans_no;
			}
		}
	

        // 재고차감 - 재고차감을 먼저한다. 송장상태에서 재고차감후 배송처리. 중복으로 올릴경우 중복 차감 방지

        // class_stock
        $obj = new class_stock();

        $query_stock = "select a.seq a_seq
                              ,b.product_id b_product_id
                              ,b.qty b_qty
                          from orders a
                              ,order_products b
                         where a.seq = b.order_seq 
                           and a.status = 7
                           and a.trans_no in ( $str_trans_no )
                           and b.order_cs not in (1,2,3,4)";
        $result_stock = mysql_query($query_stock, $connect);
        while( $data_stock = mysql_fetch_assoc($result_stock) )
        {
            $info = array(
                type       => 'trans',
                product_id => $data_stock[b_product_id],
                bad        => 0,
                location   => 'Def',
                qty        => $data_stock[b_qty],
                memo       => '송장입력 배송처리',
                order_seq  => $data_stock[a_seq]
            );
            $obj->set_stock($info, $worker);
        }


		$query = "update orders set status=8, trans_date_pos=Now() 
                   where status=7 
                     and order_cs not in (1,3)
                     and trans_no in ( $str_trans_no )";	

		$result = mysql_query( $query, $connect );	
		$cnt    = mysql_affected_rows ();	

		$cnt    = $cnt ? $cnt : 0;


        $this->jsAlert( $cnt . "건 적용 되었습니다.");
        $this->redirect("template15.htm?template=D503");
        exit;    
	}
	
    function upload()
    {
        global $connect, $shop_id, $_file, $admin_file_name, $trans_corp, $default_format, $command;
        
        // trans_temp를 비워준다.
        mysql_query("truncate table trans_temp", $connect);
        
        $obj = new class_file();
        $arr_data = $obj->upload();

		// 배송처리
		if ( $command == "set_trans")
		{
			$this->set_trans( $arr_data );
			return;
		}	

        // header 찾기 + 송장 정보 upload
        $i = 0;
        $body_row = 0;
        $sys_pos_transno = -1; // 초기화
        $sys_pos_seq     = -1; // 초기화
        
        //
        // header parsing
        foreach ($arr_data as $data )
        {
            // 1번째 줄은 반듯이 header가 와야 한다.
            // upload한 자료로 sys_pos_transno , sys_pos_seq를 구한다.
            for( $j = 0; $j < sizeof($data); $j++ )
            {
                $data[$j] = str_replace(" ","", $data[$j] );
                
                //
                // 송장 번호 index 구한다.
                if ( strpos("/송장번호|등기번호|운송장번호/", trim($data[$j]) ) )
                {
                    $sys_pos_transno = $j;
                }
                
                //
                // 관리번호 index 구한다.
                
                $f = array(" ","/");
                $_str = str_replace( $f,"",trim($data[$j]) );
                
                if ( strlen($_str ) > 10 )
                {
                    if ( strpos("/출고번호|관리번호|관리번호2|주문번호(체결번호)|고객사용번호|주문번호|고객주문번호|고객주문번호|예약주문번호/", $_str ) )
                    {
                        $sys_pos_seq = $j;
                    }
                }
                debug( "출고번호: $j / $data[$j] / " . strlen($_str ) );
            }            
            debug( "pos_seq: $sys_pos_seq / trans: $sys_pos_transno");
            
            $body_row++; // body의 start..
            
            // 헤더를 모두 찾으면 break;
            if ( $sys_pos_seq != -1 && $sys_pos_transno != -1 )
                break;
            
            // 헤더가 둘 중 하나라도 없으면 초기화.
            if ( $sys_pos_seq == -1 || $sys_pos_transno == -1 )
            {
                $sys_pos_seq     = -1;
                $sys_pos_transno = -1;
            }
            
            if ( $body_row == 10 )
                break;
        }
        
        debug( "110 xxxx no pos_header : $sys_pos_seq / pos_trans: $sys_pos_transno");
        //
        // 헤더를 못 찾는 경우
        if ( $sys_pos_seq == -1 || $sys_pos_transno == -1 )
        {
            debug( "11 xxxx no pos_header : $sys_pos_seq / pos_trans: $sys_pos_transno");
            $this->get_pos($trans_corp, &$sys_pos_seq, &$sys_pos_transno);
            $body_row = 0;
            debug( "22 xxxx no pos_header : $sys_pos_seq / pos_trans: $sys_pos_transno");
        }
        
        //
        // body parsing
        //
        for( $j=0; $j < count($arr_data); $j++ )
        {
            $data = $arr_data[$j];
            
            // 1번째 줄은 반듯이 header가 와야 한다.
            // upload한 자료로 sys_pos_transno , sys_pos_seq를 구한다.
            if ( $i < $body_row )
            {
                $i++;
                continue;
            }
            // body_row + 1번째 줄 부터 송장 입력 자료가 온다.
            else
            {
                debug( " $j ) no pos_header : $sys_pos_seq / pos_trans: $sys_pos_transno");
                
                // sys_pos_transno, sys_pos_seq가 모두 -1이 올 경우 header오류 작업 중지
                if ( $sys_pos_transno == -1 || $sys_pos_seq == -1 )
                {
                    //$this->redirect("popup_utf8.htm?top=D500&template=error&msg=필수 헤더가 없습니다.");
                    $this->jsAlert("필수 헤더가 없습니다.");
                    $this->redirect("template15.htm?template=D500");
                    exit;    
                }
                
                // 2nd line의 값 부터 처리한다.
                $trans_no = $data[$sys_pos_transno];
                $seq      = $data[$sys_pos_seq];
                
                debug( "seq: $seq / trans_no: $trans_no" );
                
                $seq      = preg_replace("/[^0-9]/", "", $seq);
                $trans_no = preg_replace("/[^0-9]/", "", $trans_no);
                
                if ( $seq )
                {
                    $query = "insert trans_temp 
                                 set seq        = '$seq',
                                     trans_corp = '$trans_corp', 
                                     trans_no   = '$trans_no'";
                    @mysql_query ($query, $connect); 
                    
                                                     
                }
                
                if( $i % 100 == 0 )
                {
                    $str = "$i/" . count($arr_data);
                    echo "<script language=javascript> 
                            show_waiting() 
                            show_txt ( '$str' );
                          </script>";
                    flush();
                }
                
                $i++;
            }
        }   
        $this->redirect( "?template=D501" );
    }
    
    function affect_row()
    {
        
    }

    function get_pos($trans_corp, &$pos_seq, &$pos_transno)
    {
        global $connect, $default_format;
        
        debug( "get_pos default_format: $default_format");
        
        if( $default_format == 1 )
        {
            $pos_seq = 0;
            $pos_transno = 1;
        }
        else
        {
            $query = "select * from trans_conf where trans_corp=$trans_corp";
            
            debug( $query );
            
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
        
            $pos_seq = $data[position_seq] - 1;
            $pos_transno = $data[position_transno] - 1;
        }
    }

    function get_pos_info()
    {
        global $connect, $trans_corp;
        
        $this->get_pos($trans_corp, &$pos_seq, &$pos_transno);
        $val = array();
        $val['pos_seq'] = $this->get_alphabet( $pos_seq );
        $val['pos_transno'] = $this->get_alphabet( $pos_transno );
        
        echo json_encode( $val );
    }
    
    function get_alphabet($num)
    {
        switch( $num )
        {
            case 0:   $chr = "A";  break;
            case 1:   $chr = "B";  break;
            case 2:   $chr = "C";  break;
            case 3:   $chr = "D";  break;
            case 4:   $chr = "E";  break;
            case 5:   $chr = "F";  break;
            case 6:   $chr = "G";  break;
            case 7:   $chr = "H";  break;
            case 8:   $chr = "I";  break;
            case 9:   $chr = "J";  break;
            case 10:  $chr = "K";  break;
            case 11:  $chr = "L";  break;
            case 12:  $chr = "M";  break;
            case 13:  $chr = "N";  break;
            case 14:  $chr = "O";  break;
            case 15:  $chr = "P";  break;
            case 16:  $chr = "Q";  break;
            case 17:  $chr = "R";  break;
            case 18:  $chr = "S";  break;
            case 19:  $chr = "T";  break;
            case 20:  $chr = "U";  break;
            case 21:  $chr = "V";  break;
            case 22:  $chr = "W";  break;
            case 23:  $chr = "X";  break;
            case 24:  $chr = "Y";  break;
            case 25:  $chr = "Z";  break;
            case 26:  $chr = "AA";  break;
            case 27:  $chr = "AB";  break;
            case 28:  $chr = "AC";  break;
            case 29:  $chr = "AD";  break;
            case 30:  $chr = "AE";  break;
            case 31:  $chr = "AF";  break;
            case 32:  $chr = "AG";  break;
            case 33:  $chr = "AH";  break;
            case 34:  $chr = "AI";  break;
            case 35:  $chr = "AJ";  break;
            case 36:  $chr = "AK";  break;
            case 37:  $chr = "AL";  break;
            case 38:  $chr = "AM";  break;
            case 39:  $chr = "AN";  break;
            case 40:  $chr = "AO";  break;
            case 41:  $chr = "AP";  break;
            case 42:  $chr = "AQ";  break;
            case 43:  $chr = "AR";  break;
            case 44:  $chr = "AS";  break;
            case 45:  $chr = "AT";  break;
            case 46:  $chr = "AU";  break;
            case 47:  $chr = "AV";  break;
            case 48:  $chr = "AW";  break;
            case 49:  $chr = "AX";  break;
            case 50:  $chr = "AY";  break;
            case 51:  $chr = "AZ";  break;
            default:  $chr = "-";
        }
        return $chr;
    }            

    //////////////////////////////////////////////////////////
    // 송장 업로드 확인 후 실제로 입력
    // date: 2005.9.1
    function upload_confirm()
    {
        global $connect, $status;
        
        // Lock Check
        $obj_lock = new class_lock(302);
        if( !$obj_lock->set_start(&$msg) )
        {
            $this->jsAlert($msg);
            $this->redirect ( "?template=D501" );
            exit;
        }

        $query = "select * from trans_temp";
        $result = mysql_query ( $query, $connect );
        $max = mysql_num_rows( $result );
        
        $i = 0; 
        $fail_count = 0;
        $trans_seq_arr = array();
        while ( $data = mysql_fetch_array ( $result ) )
        {
            // 송장번호
            $trans_no = str_replace( array("\t","\w", "\r","\n"), "", $data[trans_no] );
            if ( $trans_no == '' )
            {
                $fail_count++;
                continue;
            }
            
            // 합포, 보류 확인            
            $query_pack = "select pack, hold, status from orders where seq=$data[seq]";
debug( "합포, 보류 확인 : " . $query_pack);
            $result_pack = mysql_query($query_pack, $connect);
            $data_pack = mysql_fetch_assoc($result_pack);

            // 이미 배송상태이면 pass
            if ( $data_pack[status] == 8 )
            {
debug( "status:8 / $data[seq]" );
                $fail_count++;
                continue;
            }
            // 배송 아니어도 합포 중에 배송이 있는지 확인
            else if( $data_pack[pack] > 0 && _DOMAIN_ != 'box4u' )
            {
                $query_trans = "select seq from orders where pack=$data_pack[pack] and status=8";
                $result_trans = mysql_query($query_trans, $connect);
                if( mysql_num_rows($result_trans) )
                {
debug( "status:8 / $data[seq]" );
                    $fail_count++;
                    continue;
                }
            }
                        
            $hold = $data_pack[hold];
            $packed = ( $data_pack[pack] > 0 ? 1 : 0 );

            if( _DOMAIN_ == 'box4u' )
                $pack_str = " seq=$data[seq] ";
            else
                $pack_str = ( $packed ? "pack=$data_pack[pack]" : "seq=$data[seq]" );

debug( "update run $data[seq]" );

            $i++;
            $query = "update orders 
                         set trans_corp = '$data[trans_corp]',
                             trans_no = '$trans_no',
                             trans_date = Now(),";
            /////////////////////
            // 배송완료 처리
            // 합포된 주문이 송장 업로드시 부분 취소일 경우
            // As is: 송장입력 누락
            // To be: 부분적으로 배송입력
            if ( $status == 8 && $hold == 0 )
            {
                $query .= " status =8, trans_date_pos=Now()";
                $query_con = " where " . $pack_str . "
                                 and order_cs <> 1 
                                 and hold=0 
                                 and status < 8 
                                 and status <> 0";
                mysql_query ( $query . $query_con, $connect );
                
                
                
                if( _DOMAIN_ == 'box4u' || _DOMAIN_ == 'ezadmin')
                {
                	$temp_arr[seq]  = $data[seq];
                	$temp_arr[pack] = $data_pack[pack];                	
					$trans_seq_arr[] = $data[seq];
					$this->box4u_func_($temp_arr);//box4u inc function호출
                }
                
                // seq list. 원래는 송장번호로 처리했으나, 전체 주문에 대해 동일 송장번호를 사용하는 업체가 있어서 seq list 로 사용
                $seq_arr = array();
                $query_seq = "select seq from orders where " . $pack_str;
                $result_seq = mysql_query($query_seq, $connect);
                while( $data_seq = mysql_fetch_assoc($result_seq) )
                    $seq_arr[] = $data_seq[seq];
                
                $seq_list = implode(",", $seq_arr);
                
                // 재고 차감
                $this->stock_out( $seq_list );////////////////
            }
            else if( $data_pack[status] == 1 )
            {
                $query .= " status = 7 ";
                $_query = " where " . $pack_str . "
                              and status = 1";
                $_result = mysql_query ( $query . $_query  , $connect );      
                 
                // 합포 번호가 틀려서 안 들어가는 경우 합포 번호를 다시 계산해서 처리.                                  
                // 2011.8.3 - jk
                if ( mysql_affected_rows( $_result ) == 0 && $packed )
                {
                    static $_query;
                    static $_result;
                    static $_data;
                    $_query = "select pack from orders where seq=$data[seq]";
                    $_result = mysql_query( $_query, $connect );
                    $_data   = mysql_fetch_assoc( $_result );                    
                    debug( "new pack: $_data[pack] ");
                    
                    $_query = " where " . ($packed ? "pack" : "seq" ) . "=$_data[seq] and status = 1";
                    debug( $query . $_query );
                    $_result = mysql_query ( $query . $_query  , $connect );
                }
            }
            else
            {
                $fail_count++;
                continue;
            }
            
            debug( "[upload_confirm] $query $query_con \n result:" . mysql_affected_rows());
            
            //
            // 이력을 남긴다. 2011.1.26 - jkryu
            //
            $this->insert_upload_log( $data, $connect,$packed );        
                    
            //////////////////////////////////////
            $str = " $i / $max 번 데이터 처리중";
            echo "<script language=javascript> 
                     show_waiting() 
                     show_txt ( '$str' );
                  </script>";
            flush();
        }
        if( _DOMAIN_ == 'box4u' || _DOMAIN_ == 'ezadmin')
        {
        	stat_month_confirm_trans($trans_seq_arr);	   //판매처별매출조정에 삽입 box4u_func_
        }
        
        $this->end( $transaction );

        // Lock End
        if( !$obj_lock->set_end(&$msg) )
        {
            $this->jsAlert($msg);
        }

        $this->jsAlert( $i . "개의 Data가 입력되었습니다 실패 개수( $fail_count)");
        $this->redirect ( "?template=D500" );
    }
    
    //box4u일 경우 배송시 정산에 데이터 추가
    function box4u_func_($temp_arr)
    {
    	//box4u_func.inc 
    	shop_stat_upload_confirm_trans($temp_arr); //정산내역관리에 삽입
    	revenue_modify_confirm_trans($temp_arr);   //일자별매출조정에 삽입    	
    }
   
    
    // 
    // trans_upload_log에 값을 입력한다.
    //
    function insert_upload_log( $data, $connect, $packed )
    {
        $trans_corp = $data[trans_corp];
        $trans_no   = $data[trans_no];
        
        $arr_seqs = array();
        if ( $packed == 1)
        {
            $query = "select seq from orders where pack=$data[seq]";
            $result = mysql_query( $query, $connect );
        
            while ( $_data = mysql_fetch_assoc( $result ) )
            {
                $arr_seqs[] = $_data[seq];   
            }
        }
        else
        {
            $arr_seqs[] = $data[seq];   
        }
        
        foreach( $arr_seqs as $seq )
        {
            $query = "insert trans_upload_log
                             set order_seq   = $seq
                                 ,trans_no   = '$trans_no'
                                 ,trans_corp = '$trans_corp'
                                 ,owner      = '" . $_SESSION[LOGIN_NAME] . "'
                                 ,reg_type   = 2";
            @mysql_query ($query, $connect);
        }
       
    }
    
    /////////////////////////////////
    //
    // 재고 출고
    function stock_out( $seq_list )
    {
        global $connect;

        $query = "select a.product_id, a.qty, b.seq as seq 
                    from order_products a, orders b
                   where b.seq      = a.order_seq
                     and b.status   = 8 
                     and b.seq in ($seq_list)
                     and a.order_cs not in (1,2)";
        
        $result = mysql_query( $query, $connect );
        $obj = new class_stock();
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            // input parameter
            $info_arr = array(
                type       => "trans",
                product_id => $data[product_id],
                bad        => 0,
                location   => 'Def',
                qty        => $data[qty],
                memo       => "송장입력 즉시 배송",
                order_seq  => $data[seq]
            );
            $obj->set_stock($info_arr);
        }
    }
    
   // 송장 미입력 count
   function count_list()
   {
      global $connect;
      $query = "select order_date, date_format(collect_date,'%Y-%m-%d') collect_date, count(*) cnt
                from orders 
                where status not in (0,7,8)
                  and order_cs not in ( 1,2,3,4,5 )";
       
      if ( !$_SESSION[LOGIN_LEVEL] ) 
          $query .= " and supply_id = '". $_SESSION[LOGIN_CODE] . "'";      

      $query .= " group by date_format(collect_date,'%Y-%m-%d') order by collect_date desc";

//if ( _DOMAIN_ == "bose5546" )
//        echo $query;

      $result = mysql_query ( $query, $connect );
      
      $arr_result = array();
      while( $data = mysql_fetch_assoc( $result ) )
      {
        $arr_result[$data[collect_date]] = array( 
            order_date   => $data[order_date]
            ,collect_date=> $data[collect_date]
            ,cnt         => $data[cnt]
            ,reg_cnt     => 0
        );
      }
      
      // 발주일자 별...송장 입력 개수를 구한다.
      $query = "select count(*) cnt,a.collect_date,a.order_date,a.trans_date
                 from orders a, trans_temp b
                where a.seq=b.seq 
                  and a.status in (7,8)
                group by a.collect_date";
      $result = mysql_query ( $query, $connect );
      while( $data = mysql_fetch_assoc( $result ) )
      {
        $arr_result[$data[collect_date]][reg_cnt]      = $data[cnt];
        $arr_result[$data[collect_date]][order_date]   = $data[order_date];
        $arr_result[$data[collect_date]][collect_date] = $data[collect_date];
        $arr_result[$data[collect_date]][trans_date  ] = $data[trans_date];
      }
       
      return $arr_result;
   }

   //////////////////////////////////////////////////////
   // 금일 송장입력한 결과
   function trans_today2 ( &$total_rows )
   {
      global $connect;

      $query_cnt = "select count(*) as cnt from trans_temp";
      $result = mysql_query ( $query_cnt, $connect);
      $data = mysql_fetch_array ( $result );
      $total_rows = $data[cnt];
 
      ////////////////////////////////////////////////////////////////
      $query = "select * from trans_temp limit 0, 5";
 
      $result = mysql_query ( $query, $connect );
      
      return $result;
   }


   //////////////////////////////////////////////////////
   // 금일 송장입력한 결과
   function trans_today ( &$total_rows )
   {
      global $connect;
      global $page;

      $line_per_page = _line_per_page;
      $page = $_REQUEST["page"];
      $today= date('Ymd', strtotime("now"));

      if ( !$page ) $page = 1;
      $start = ( $page - 1 ) * 20;

      $query_cnt = "select count(*) as cnt from orders where trans_date > '$today'";
      $result = mysql_query ( $query_cnt, $connect);
      $data = mysql_fetch_array ( $result );
      $total_rows = $data[cnt];
 
      ////////////////////////////////////////////////////////////////
      $query = "select *, date_format( trans_date, '%Y-%m-%d') trans_date 
                  from orders , shopinfo
                 where orders.shop_id = shopinfo.shop_id
                   and trans_date > '$today'
                 order by order_date desc
                 limit $start, $line_per_page";
 
      $result = mysql_query ( $query, $connect );
      
      return $result;
   }


   function write ( $datas, $num_rows , &$filename)
   {
       global $shop_id;

       // 결과를 write할 새로운 data를 open
       $filename = $_SESSION["LOGIN_ID"] . $shop_id . ".csv";
       $saveTarget = _save_dir . $filename;
       $handle = fopen ($saveTarget, "w");

       // 결과를 저장
       $start_index = $this->start_index ? $this->start_index : 0;
       for ( $i = $start_index; $i <= $num_rows; $i++ )
       {
            switch ( $this->type)
            {
               // excel의 처리
               case "xls": 
                  $j = $i + 1; // excel reader의 시작은 1부터
                  $data = $datas->sheets[0]['cells'][$j];
                  $buffer = $this->parse_data ( $data, $i );
               break;
               case "tab":
                  $data = $datas[$i];
                  $data = split ( "\t", $data );
                  $buffer = $this->parse_data ( $data,$i );
               break;
               case "csv":
                  $data = $datas[$i];
                  $data = split ( ",", $data );
                  $buffer = $this->parse_data ( $data, $i );
               break;
            }

            ///////////////////////////////////////o /
            // 값이 있을때만 저장함
            if ( $buffer )
               fwrite($handle, $buffer . "\n");
       }

       // file handle close
       fclose($handle);
   }

   // order_subid를 가져오는 계산이 있어야 함 
   function parse_data ( $data , $no)
   {
      $order_id = $data[$this->order_id];
      $order_subid = 1;

      // 배송정보를 가져온다.
      $this->get_transinfo ( $order_id, $order_subid, &$trans_corp, &$trans_no );

      $column_count = count ( $data );
      $end_index = $column_count;
      $start_index = 0;

      if ( $this->type == "xls" )
      {
         $start_index = 1;
         $end_index = $column_count + 1;
      }

      $rep = array(",", "\n", "\r");

      // same 인지 diff인지 확인 함     
      if ( $this->data_type == "diff" )
      {
          $start_index = 0;
          $end_index = count( $this->data_format );

          if ( $this->type == "xls" )
             $end_index++;

          // 같은 경우
          for ( $i = $start_index; $i < $end_index; $i++ )
          {
               // 일련 번호가 오는 경우가 있음 gseshop
            if ( $this->data_format[$i] == "No")
               $str .= $no;
            else if ( $this->data_format[$i] == "trans_no")
               $str .= $trans_no;
            else if ( $this->data_format[$i] == "trans_corp")
               $str .= $trans_corp;
            else if ( $this->data_format[$i] == "check")
               $str .= "v";
            else if ( $i == $this->order_id)
               $str .= "'" . $data[$i];
            else
               $str .= str_replace( $rep,"",$data[$this->data_format[$i]] );

            if ( $i != $end_index - 1 )
               $str .= ",";
         }        
      } 
      else
      {
         if ( $this->type == "xls" )
            $end_index++;

         // 같은 경우
         // 기준은 1부터
         for ( $i = $start_index; $i < $end_index; $i++ )
         {
            if ( $i == $this->trans_no )
               $str .= $trans_no;
            else if ( $i == $this->trans_corp )
               $str .= $trans_corp;
            else if ( $i == $this->order_id)
               $str .= "'" . $data[$i];
            else 
            {
               $str .= str_replace( $rep,"",$data[$i] );
            }

            if ( $i != $end_index - 1)
               $str .= ",";
         }        
      }

      return $str;
   } 

   function get_transinfo ( $order_id, $order_subid, &$trans_corp, &$trans_no )
   {
      global $connect;
      $query = "select trans_no, trans_corp from orders where order_id='$order_id' and  order_subid='$order_subid'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      $trans_corp = $data[trans_corp];
      $trans_no = $data[trans_no];

      if ( $this->debug == "on" )
      {
         $trans_corp = "토인택배";
         $trans_no = "123-123-123";
      }

   }


   //////////////////////////////////////////////////////////////////////

   function download()
   {
      global $saveTarget;

      if (is_file($saveTarget)) { 
          $fp = fopen($saveTarget, "r");   
          fpassthru($fp);  
      } else {
          echo "can not open it ";
      }
      
      ////////////////////////////////////// 
      // file close and delete it 
      fclose($fp);
      unlink($saveTarget);

      exit; 
   }


}

?>