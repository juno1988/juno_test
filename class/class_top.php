<?
class class_top
{
	function get_multi_supply_group_str ( $group_id )
	{
		global $connect;
		
		$query = "select code from userinfo where level=0 and group_id IN ($group_id)";
		$result = mysql_query( $query, $connect );
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $supply_codes .= $supply_codes ? "," : "";
            $supply_codes .= $data[code];
        }		
		return $supply_codes;   
	}
    function get_group_supply ( $group_id )
    {
        global $connect,$supply_code;
        
        $query = "select code from userinfo where level=0 and group_id=$group_id"; 
        
        if ( is_array( $supply_code ) )
        {
            $_str_code = "";
            foreach( $supply_code as $code )
            {
                $_str_code .= $_str_code ? "," : "";
                $_str_code .= $code;   
            }
            
            if ( $_str_code )
                $query .= " and code in ( $_str_code ) ";
        }
        else
        {
            if ( $supply_code )
                $query .= " and code in ($supply_code)";
        }
        
        $result = mysql_query( $query, $connect );
        $supply_codes = "";
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            $supply_codes .= $supply_codes ? "," : "";
            $supply_codes .= $data[code];
        }
        return $supply_codes;   
    }

    function get_str_supply()
    {
        global $supply_code,$str_supply_code;            
        
        $supply_code = $supply_code ? $supply_code : $str_supply_code;
        
        $_str_code = "";
        if ( is_array( $supply_code ) )
        {
            foreach( $supply_code as $code )
            {
                $_str_code .= $_str_code ? "," : "";
                $_str_code .= $code;   
            }
        }
        else
            $_str_code = $supply_code;
            
        return $_str_code;
    }

    function get_supply_group_name( $id )
    {
        if ( $id )
        {
            global $connect;
            $query = "select * from supply_group where group_id=$id";   
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
            
             return $data[name] ? $data[name] : "&nbsp;";
        }
        else
        {
            return "&nbsp;";
        }
    }

    function get_shop_group_name( $id )
    {
        if ( $id )
        {
            global $connect;
            $query = "select * from shop_group where group_id=$id";   
            $result = mysql_query( $query, $connect );
            $data   = mysql_fetch_assoc( $result );
            return $data[name] ? $data[name] : "&nbsp;";
        }
        else
        {
            return "&nbsp;";
        }
    }
    
    //***************************************
    // 멀티 바이트 형식의 이름..정리
    //***************************************
   function check_name( $str )
   {
        if (preg_match('/&#([0-9]{1,});/', $str))
        {
            $str = mb_decode_numericentity($str, array(0x0, 0x10000, 0, 0xfffff), 'UTF-8');
            $str = iconv('utf-8','cp949', $str );
        }
        return $str;
   }

    // category 보여주기..
    function category_disp( $category )
    {
        global $connect;
        $query = "select * from category where seq=$category";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        echo $data[name];
    }

    function get_category( $category )
    {
        global $connect;
        $query = "select * from category where seq=$category";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );
        return $data[name];
    }

    // category coombo box
    function category_select( $category = "")
    {
        global $connect;
        $query = "select * from category order by name";
        $result = mysql_query( $query, $connect );
        
        $selected[$category] = "selected";        
        echo "<select name='category' style=width:300>\n";        
        echo "<option value=0>카테고리</option>\n";     
        while ( $data = mysql_fetch_array( $result ) )
        echo "<option value='$data[seq]' " . $selected[$data[seq]] . ">$data[name]</option>\n";
        echo "\n</select>\n";
    }

   function root_debug( $str )
   {
        if ( $_SESSION[LOGIN_LEVEL] == 9 )
        {
            echo $str;
            exit;
        }
   }

   //===================================
   // date: 2007.7.25
   // jk.ryu $this->show_txt ( $str ) 의 형식으로 출력하려고 함
   function show_txt ( $str )
   {
        echo "<script language='javascript'> show_txt ( '$str') </script>";
        flush();
   }

   function show_wait($parent=0)
   {
        if( $parent )
        {
            echo str_pad(" " , 256); 
            echo "<script language='javascript'> parent.show_waiting() </script>";
            flush();

            echo str_pad(" " , 256); 
            echo "<script type='text/javascript'>parent.show_txt('')</script>";
            flush();
        }
        else
        {
            echo str_pad(" " , 256); 
            echo "<script language='javascript'> show_waiting() </script>";
            flush();

            echo str_pad(" " , 256); 
            echo "<script type='text/javascript'>show_txt('')</script>";
            flush();
        }
   }

   function hide_wait()
   {
        echo "<script>hide_waiting()</script>";
        flush();
   }

    // promotion shop list
    // 2008.7.8- jk
    function get_promotion_shop()
    {
        global $connect;
        $query = "select shop_id from shopinfo where promotion_id='" . $_SESSION[LOGIN_ID] . "'";
        $result = mysql_query ( $query, $connect );
        $i = 0;
        $_list = "";
        while ( $data = mysql_fetch_array( $result ) )
        {
            if ( $i != 0 ) $_list .= ",";
            $_list .= $data[shop_id];
            $i++;
        }
        return $_list;
    }


   /*//////////////////////////////////////
        $arr_items = array (
                "domain"        => "",
                "product_name"  => "like",
                "options"       => "like",
                "recv_name"     => "",
                );
 
   *////////////////////////////////////////
   function build_option( $arr_items )
   {
        $_options = "";
        $_cnt = 0;

        foreach ( $arr_items as $item=>$_opt )
        {
            global  $$item;
            if ( $$item )
            {
                if ( $_cnt == 0 )
                        $_options .= " where ";
                else
                        $_options .= " and ";

                if ( $_opt == "like" )
                        $_options .= "$item like '%". $$item."%'";
                else
                        $_options .= "$item = '". $$item."'";
                $_cnt++;
            }
        }

        // is_delete 처리 부분        
        if ( $_cnt == 0 )
                $_options .= " where ";
        else
                $_options .= " and ";
        $_options .= " is_delete = 0 ";

        return $_options;
    }

   function run( $action )
   {
      global $template;
      global $PPN;


      //////////////////////////////////////////
      // 사용자 레벨 설정 이부분은 사용하지 않는다.
      // 2012.7.24 jkryu
      /*
      if ( $PPN[$template][level] > $_SESSION[LOGIN_LEVEL] )
      {
        echo "<script>hide_waiting();</script>";
        include "template/inc/reject.htm";
        include_once "./main_foot.htm"; 
        exit;
      }
      */
      
      if ( $action )
          $this->${action}();
      else
          $this->${template}();
   }
  
//-------------------------------------                   
// 한글로 자르기 (return arr[])                           
function substr_kor($msg, $len)
{
    $msg = iconv('utf-8','cp949',$msg);
    if (strlen($msg) > $len) {
        $submsg = substr($msg,0,$len);
        preg_match('/^([\x00-\x7e]|.{2})*/', $submsg, $z);

        $arr[] = iconv('cp949','utf-8',$z[0]);
        $arr[] = iconv('cp949','utf-8',str_replace($z[0], "", $msg));

        return $arr;
    } else {
        $arr[] = iconv('cp949','utf-8',$msg);
        return $arr;
    }
}    
  
  ///////////////////////////////////////////////
  // 
  function cutstr2($str, $len)
  {
    
    if(strlen($str)>$len)
    {
      for($i=0; $i<$len; $i++) if(ord($str[$i])>127) $i++;
      $str=substr($str,0,$i);
      return $str . "..";
    }
    return $str;
  }

  ///////////////////////////////////////////////
  // 한글 string을 언하는 만큼 자른다.
  function cutstr($str, $len)
  {
    if(strlen($str)>$len)
    {
      for($i=0; $i<$len; $i++) if(ord($str[$i])>127) $i++;
      $str=substr($str,0,$i);
      return $str;
    }
    return $str;
  }

	function len_mysql($str)
	{
		global $connect;

		if ( !$connect )
			$connect = mysql_connect("66.232.146.171", "ezadmin", "pimz8282");

		$query = "select char_length('$str') len";
		$result = mysql_query( $query, $connect );
		$data = mysql_fetch_assoc($result);

		return $data['len'];
	}

	function cutstr_mysql($str, $pos, $len )
	{
		global $connect;

		if ( !$connect )
			$connect = mysql_connect("66.232.146.171", "ezadmin", "pimz8282");

		$query = "select substr('$str', $pos, $len) cutstr";
		$result = mysql_query( $query, $connect );
		$data = mysql_fetch_assoc($result);

		return $data['cutstr'];
	}

    //
    // 공급처 멀티 선택..
    function supply_selectx()
    {
      global $connect,$supply_code;

      $query = "select * from userinfo where level=0 order by name";
      $result = mysql_query( $query, $connect );

      $code = $_REQUEST["supply_code"] ? $_REQUEST["supply_code"] : $supply_code;

/*
      if ( $supply_code )
        $selected[$supply_code] = "selected";
*/
      echo "<select name='supply_code' id='supply_code' style='width:120px;' multiple='multiple' >\n";
      //echo "<option value=''>공급처 선택</option>\n";     
      while ( $data = mysql_fetch_array( $result ) )
      {
          echo "<option value='$data[code]' ";
          if ( in_array( $data[code],$supply_code) == true)
          {
            echo "selected='selected'";
          }

          echo ">$data[name] ($data[code])</option>\n";
      }
      echo "\n</select>\n";

/*        
        global $connect,$supply_code;
        
        $query = "select * from userinfo where level=0 order by name";
        $result = mysql_query( $query, $connect );
        
        $code = $_REQUEST["supply_code"] ? $_REQUEST["supply_code"] : $supply_code;
        
        echo "<select name='supply_code' id='supply_code' style=width:150 multiSelect size=1>\n";
        while ( $data = mysql_fetch_array( $result ) )
        {
            echo "<option value='$data[code]' ";
            if ( in_array( $data[code],$supply_code) == true)
            { 
              echo "selected";
            }
              
            echo ">$data[name] ($data[code])</option>\n"; 
        }
        echo "\n</select>\n";    
*/
    }
    
   //////////////////////////////////////////
   // select 박스 출력:신상품등록
   function supply_select4()
   {
      global $connect;
      $query = "select * from userinfo where level=0 order by name";
      $result = mysql_query( $query, $connect );

      echo "<select name='supply_code' id='supply_code' style=width:300>\n";
      echo "<option value='0' >공급처를 선택하세요</option>\n";
      while ( $data = mysql_fetch_array( $result ) )
      echo "<option value='$data[code]' >$data[name]</option>\n";
      echo "\n</select>\n";
   }

   //////////////////////////////////////////
   // select 박스 출력:상품추가
   function supply_select3( $supply_code="" )
   {
      global $connect;
      $query = "select * from userinfo where level=0 order by name";
      $result = mysql_query( $query, $connect );
      
      $code = $_REQUEST["supply_code"] ? $_REQUEST["supply_code"] : $supply_code;

      if ( $supply_code )
        $selected[$supply_code] = "selected";

      echo "<select name='supply_code' id='supply_code' style=width:300>\n";
      //echo "<option value=''>공급처 선택</option>\n";     
      while ( $data = mysql_fetch_array( $result ) )
      echo "<option value='$data[code]' " . $selected[$data[code]] . ">$data[name]</option>\n";
      echo "\n</select>\n";
   }

   //////////////////////////////////////////
   // select 박스 출력:w
   function supply_select2( $supply_id="" )
   {
      global $connect;
      $query = "select * from userinfo where level=0 order by name";
      $result = mysql_query( $query, $connect );
      
      $code = $_REQUEST["supply_id"] ? $_REQUEST["supply_id"] : $supply_id;

      $selected[$code] = "selected";


      echo "<select name=supply_id style=width:200>\n";

      echo "<option value=0>전체 공급처</option>\n";     
      while ( $data = mysql_fetch_array( $result ) )
      echo "<option value='$data[code]' " . $selected[$data[code]] . ">$data[name]</option>\n";
      echo "\n</select>\n";
   }

   //////////////////////////////////////////
   // select 박스 출력:w
   function supply_select( $supply_code="", $width=300 )
   {
      global $connect;
      $query = "select * from userinfo where level=0 order by name";
      $result = mysql_query( $query, $connect );
      
      $code = $_REQUEST["supply_code"] ? $_REQUEST["supply_code"] : $supply_code;

      $selected[$code] = "selected";


      echo "<select name='supply_code' id='supply_code' style=width:$width>\n";
      echo "<option value=0>전체 공급처</option>\n";     
      while ( $data = mysql_fetch_array( $result ) )
      echo "<option value='$data[code]' " . $selected[$data[code]] . ">$data[name]</option>\n";
      echo "\n</select>\n";
   }

   function supply_inputbox ( $supply_code )
   {
        global $connect;
        $query = "select name from userinfo where code=$supply_code";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );

        echo "<input type='hidden' name='supply_code' value='$supply_code'>$data[name]";
   }

   // 보류 메시지
   function hold_string( $code )
   {
        switch ( $code )
        {
            case 1:
                return "일반보류";
                break;
            case 2:
                return "주소변경";
                break;
            case 3:
                return "선착불변경";
                break;
            case 5:
                return "취소보류";
                break;
            case 4:
                return "교환보류";
                break;
            default:
                return "정상";
        }
   }

   function is_utf8($string)
   {
       if ( preg_match("/\;\&\#/",$string) )
             return 1;
       else
          return 0;
   }

   ///////////////////////////////////////////
   // 교환 상태
   function get_order_cs( $code,$option=0 )
   {
      switch ( $code )
      {
         case 0: $str = "<img src=images/icon_02.gif alt=정상 align=absmiddle>"; 
                 $str .= $option ? " 정상":""; 
                 if ( $option == 2 ) $str = "정상";
                 break;
         case 1: $str = "<img src=images/icon_01.gif alt='배송전 전체 취소' align=absmiddle>"; 
                 $str .= $option ? " 배송전 전체 취소":""; 
                 if ( $option == 2 ) $str = "배송전 전체 취소";
                 break;
         case 2: $str = "<img src=images/icon_01.gif alt='배송전 부분 취소' align=absmiddle>"; 
                 $str .= $option ? " 배송전 부분 취소":""; 
                 if ( $option == 2 ) $str = "배송전 부분 취소";
                 break;
         case 3: $str = "<img src=images/icon_01.gif alt='배송후 전체 취소' align=absmiddle>"; 
                 $str .= $option ? " 배송후 전체 취소":""; 
                 if ( $option == 2 ) $str = "배송후 전체 취소";
                 break;
         case 4: $str = "<img src=images/icon_01.gif alt='배송후 부분 취소' align=absmiddle>"; 
                 $str .= $option ? " 배송후 부분 취소":""; 
                 if ( $option == 2 ) $str = "배송후 부분 취소";
                 break;
         case 5: $str = "<img src=images/icon_03.gif alt='배송전 전체 교환' align=absmiddle>"; 
                 $str .= $option ? " 배송전 전체 교환":""; 
                 if ( $option == 2 ) $str = "배송전 전체 교환";
                 break;
         case 6: $str = "<img src=images/icon_03.gif alt='배송전 부분 교환' align=absmiddle>"; 
                 $str .= $option ? " 배송전 부분 교환":""; 
                 if ( $option == 2 ) $str = "배송전 부분 교환";
                 break;
         case 7: $str = "<img src=images/icon_03.gif alt='배송후 전체 교환' align=absmiddle>"; 
                 $str .= $option ? " 배송후 전체 교환":""; 
                 if ( $option == 2 ) $str = "배송후 전체 교환";
                 break;
         case 8: $str = "<img src=images/icon_03.gif alt='배송후 부분 교환' align=abamiddle>"; 
                 $str .= $option ? " 배송후 부분 교환":""; 
                 if ( $option == 2 ) $str = "배송후 부분 교환";
                 break;
      }
      return $str;
   }

   function get_cs_type( $code )
   {
      switch( $code )
      {
         case 0: $str = "일반"; break;
         case 1: $str = "주문취소"; break;
         case 2: $str = "교환"; break;
      }
      return $str;
   }

   function order_status_select ( $readonly = 0)
   {
       $stauts = $_REQUEST["status"];
       $selected[$stauts] = "selected";
?>
   <select name=status  <?= $readonly ? "disabled" : "" ?>>
      <option value=0>전체</option> 
      <option value=1 <?= $selected[1] ?>>정상주문</option> 
      <option value=2 <?= $selected[2] ?>>교환발주</option> 
      <option value=11 <?= $selected[11] ?>>맞교환 발주</option> 
      <option value=3 <?= $selected[3] ?>>배송 준비중</option> 
      <option value=4 <?= $selected[4] ?>>배송 전 품절</option> 
      <option value=5 <?= $selected[5] ?>>품절 주문</option> 
      <option value=6 <?= $selected[6] ?>>교환 요청</option> 
      <option value=7 <?= $selected[7] ?>>송장 입력</option> 
      <option value=8 <?= $selected[8] ?>>배송 확인</option> 
      <option value=9 <?= $selected[9] ?>>택배사 이관</option> 
      <option value=10 <?= $selected[10] ?>>정산 완료</option> 
   <select>

<?
   }
///////////////////////////////////////////
   // 교환 상태
   function get_order_cs2( $code,$option=0 )
   {     
      switch ( $code )
      {  
         case 0:  $str = "정상"; break;
         case 1:  $str = "배송전 전체 취소"; break;
         case 2:  $str = "배송전 부분 취소"; break;
         case 3:  $str = "배송후 전체 취소"; break;
         case 4:  $str = "배송후 부분 취소"; break;
         case 5:  $str = "배송전 전체 교환"; break;
         case 6:  $str = "배송전 부분 교환"; break;
         case 7:  $str = "배송후 전체 교환"; break;
         case 8:  $str = "배송후 부분 교환"; break;
      }
      return $str;
   }
   ///////////////////////////////////////////
   // 주문 상태 출력
   function get_order_status2 ( $status, $option=0, $data=0 )
   {    
      switch ( $status )
      { 
         case 0: $str = "발주"; break;
         case 1: $str = "접수"; break;
         case 2: $str = "교환발주"; break;      // reserved for 교환발주
         case 3: $str = "배송준비중"; break;
         case 4: $str = "배송전품절"; break;
         case 5: $str = "품절주문"; break;
         case 6: $str = "교환요청"; break;
         case 7: $str = "송장"; break;
         case 8: $str = "배송"; break;
         case 9: $str = "택배사이관"; break;
         case 10: $str = "정산완료"; break;
         case 11: $str = "맞교환발주"; break;
      }
      return $str;
   }
   ///////////////////////////////////////////
   // 주문 상태 출력
   function get_order_status ( $status, $option=0, $data=0 )
   {
      switch ( $status )
      {
         case 0: $str = "<img src=images/icon_15.gif alt=발주 align=absmiddle>"; 
                 $str .= $option ? " 발주":""; 
                 if ( $option == 2 ) $str = "발주";
                 break;
         case 1: $str = "<img src=images/icon_05.gif alt=주문접수1 align=absmiddle>"; 
                 $str .= $option ? " 주문접수":""; 
                 if ( $option == 2 ) $str = "주문접수";
                 break;
         case 2: $str = "교환발주"; break;        // reserved for 교환발주
         case 3: $str = "배송준비중"; break;
         case 4: $str = "배송전품절"; break;
         case 5: $str = "품절주문"; break;
         case 6: $str = "교환요청"; break;
         case 7: $str = "<img src=images/icon_06.gif alt=송장입력 align=absmiddle>"; 
                 $str .= $option ? " 송장입력":""; 
                 if ( $option == 2 ) $str = "송장입력";
                 break;
         case 8: 
                 if ( $data )
                 {
                     require_once "class_E100.php";
                     $trans_corp = class_E::get_trans_name( $data[trans_corp] );
                     echo class_E100::print_delivery( $trans_corp, $data[trans_no], 1 );        
                 }
                 else
                     $str="<img src=images/icon_04.gif alt=배송확인 align=absmiddle>";

                 $str .= $option ? " 배송확인":""; 
                 if ( $option == 2 ) $str = "배송확인";
                 break;
         case 9: $str = "택배사이관"; break;
         case 10: $str = "정산완료"; break;
         case 11: $str = "맞교환발주"; break;
      }
      return $str;
   }

        function get_supply_name_arr()
        {
                global $connect;
                $query  = "select * from userinfo where level=0";
                $result = mysql_query( $query, $connect );
                $_arr   = array();
                while ( $data = mysql_fetch_assoc( $result ) )
                {
                   $_arr[$data[code]] = $data[name];
                }
                return $_arr;
        }

   //////////////////////////////////////////
   // 상품 코드를 넣으면 공급처를 알 수 있다
   function get_supply_name ( $product_id )
   {
       global $connect;

       $query = "select b.name as uname
                   from products a
                   left join userinfo b on b.code = a.supply_code
                  where a.product_id = '$product_id'";


       $r = mysql_query ( $query , $connect );
       $d = mysql_fetch_array ( $r );

       return $d[uname];
   }
   //////////////////////////////////////////
   // supply의 code 를 넣고 이름을 가져온다 
   function get_supply_name2 ( $supply_code )
   {
       global $connect;

        if ( $supply_code )
        {
            $supply_code = str_replace(",","','", $supply_code );
            
            
           $query = "select name 
                       from userinfo a
                      where a.code in( '$supply_code' )";
    
            $query = str_replace(",''","",$query);
            
           $r = mysql_query ( $query , $connect );
           
           $_name = "";
           $i = 0; 
           while( $d = mysql_fetch_array ( $r ) )
           {
                $i++;
                $_name .= $_name ? ",":"";
                $_name .= $d['name'];   
           }

            $template = $_REQUEST['template'];
            if ( (_DOMAIN_ == "realcoco" || _DOMAIN_ == "buyclub") && $template=="DR00" )
            {
                $short = $this->substr_kor( $_name, 30 );
 
                if ( $i > 5 )
                {
                    $short[0] .= " (총 " . $i . "개)";
                }
            
                return $short[0];
            }
			else
                return $_name; 
        }
        else
        {
            return "";
        }  
   }

   /////////////////////////////////////////
   // 사용자 등급 출력
   function get_level ( $level )
   {
      switch ( $level )
      {
         case 0: $str="협력업체"; break;
         case 1: $str="일반사용자"; break;
         case 2: $str="중간관리자"; break;
         case 3: $str="CS전용"; break;
         case 8: $str="관리자"; break;
         case 9: $str="시스템관리자"; break;
      }
      //return iconv('utf-8','cp949', $str) . " [ " . $level;
      return $str;
   }

   function build_link_par($par_arr)
   {
      foreach ( $_REQUEST as $key=>$val )
      {
           if( array_search($key, $par_arr) === false )  continue;
           $link_par .= $key . "=" . $this->base64_encode_url($val) . "&"; 
      }
      return $this->base64_encode_url($link_par);
   }
   
   ///////////////////////////////////////////
   // link url을 만들어 준다.
   // date: 2005.8.22 - jk
   function build_link_url( $code="" )
   {
      foreach ( $_REQUEST as $key=>$val )
      {
         if ( $key == "popup1" ) continue;
         if ( $key == "popup2" ) continue;
         if ( $key == "popup3" ) continue;
         if ( $key == "popup4" ) continue;

         if ( $key != "action" && $key != "PHPSESSID" && $key != "link_url" && $key != "top_url" )
         {
            if ( $code != "" && $key == "template")
               $link_url .= "template=" . $code. "&"; 
            else
               $link_url .= $key . "=" . $val . "&"; 
         }
      }
      return $link_url;
   }

   function build_link_url2( $code="" )
   {
      foreach ( $_REQUEST as $key=>$val )
      {
         if ( $key == "popup1" ) continue;
         if ( $key == "popup2" ) continue;
         if ( $key == "popup3" ) continue;
         if ( $key == "popup4" ) continue;

         if ( $key != "PHPSESSID" 
           && $key != "link_url" 
           && $key != "top_url" 
           && $key != "__utmz" 
           && $key != "__utma" 
           && $key != "_dwiC")
         {
            if ( $code != "" && $key == "template")
                $link_url .= "template=" . $code. "&"; 
            else
                $link_url .= $key . "=" . $val . "&"; 
               //$link_url .= $key . "=" . rawurlencode($val) . "&"; 
         }
      }
      return $link_url;
   }

   function build_link_url3( $par )
   {
        $link_url = $_SERVER[PHP_SELF] . "?";
        foreach ( $par as $val )
            $link_url .= "$val=" . $_REQUEST[$val] . "&"; 

        return $link_url;
   }

   function disp( $template )
   {
      global $result;

echo "r->" . $result;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function redirect($url)
   {
      //$url = server_url() . dirname($_SERVER['PHP_SELF']) . "/" . $relative_url;
      if (!headers_sent())
      {
          header("Location: $url");
      }
      else
      {
          echo "<meta http-equiv=\"refresh\" content=\"0;url=$url\">\r\n";
      }
      exit;
   }
 
  function back()
   {
?>
<script language=javascript>
   history.back()
</script>
<?
   }

   function opener_redirect($url)
   {
?>
<script language=javascript>
   opener.location.href = "<?= $url ?>"
</script>
<?
   }

   function jsAlert( $text )
   {
?>

<script type="text/javascript">
        alert("<?=$text?>");
</script>

<?
   }

  function closewin()
  {
?>
<script language=javascript>
   self.close();
</script>
<?
  }

  ///////////////////////////////////////////
  // input data validate
  function validate( $items )
  {
?>

<script language=javascript>
   function validate()
   {
<?
        foreach($items as $item=>$name)
        {
           echo"
           obj = eval(document.myform.$item)
           if(obj.value== '' || obj.value == 0){
             alert('" . $name . "은(는) 반드시 입력하셔야 합니다')
             obj.focus()
             return false
           }
           ";
        }

?> 
      return true;     
   }
</script>

<?
   }

	//-----------------------------------------
	//
	//  이미지 원형 함수(return)
	//  param1 : 변형된 image, param2 : 원형 image 
	//
	function img_origin($img, $org_path="")
	{
		$path = "/"._upload_path;
		$new_path = _IMG_SERVER_ . $path;
debug("img_origin 1 : " . $new_path);

		if ($org_path && preg_match("/http/", $org_path )) {
			return $org_path;
		}

		if (preg_match("/http/", $img )) {
			return $img;
		}

	    // 1. Local 에 이미지가 있으면...
		if ($img && is_file(_upload_path . "/" . $img))
			$str = "${path}/${img}";
	    else if ($img) // 3. img.ezadmin.co.kr 
			$str = "${new_path}/${img}";
	    else
            $str = "/images/noimage.gif";

      	return $str;
   	}

	//-----------------------------------------
	//
	// make image url string
	//
	function make_image_str($img, $img_style="", $link="")
	{
        // 2014-07-09 한글 이미지파일명
        if( _DOMAIN_ == 'polotown' )
            $img = $this->convert_euckr_img_filename($img);

		if (preg_match("/noimage/", $img))
		{
			$str = "<img src='$img' align=absmiddle>";
		}
		else
		{
			$str = "<img src='$img' ${img_style}>";
			if ($link) $str = $link . $str . "</a>";
		}

		return $str;
	}


	//-----------------------------------------
	//
	//  get_popup_tag
	//
	function get_popup_tag($id="")
	{
		$popup_tag = "<a href='javascript:openwin2_1(\"popup.htm?template=C209&id=$id\",\"descwin\", 800, 500)'>";

		return $popup_tag;
	}


	//-----------------------------------------
	//
	// 팝업링크없이 단순 이미지 출력(echo)
	//
	function disp_image_p($img)
    {
		$img_style = "border=0 align=center";

		//-- make string
		$origin = class_top::img_origin($img);
		$str 	= class_top::make_image_str($origin, $img_style);

		//-- only suvin
		if (_DOMAIN_ == "suvin" && strpos($img, "img7") > 0) {
			$str = "<img src='http://premium.ezadmin.co.kr/client/ms_proxy.pl?url=$img' border=0 align=center>";
		}

		echo $str;
	}

	//-----------------------------------------
	//
	//  팝업링크있으며 500을 100으로 출력(echo)
	//
	function disp_image_pl($img)
	{
		global $id;

		$img_100 = str_replace("_500.", "_100.", $img);
      
		$img_style = "border=0 align=center width=100 name=img_main";
		$popup_tag = class_top::get_popup_tag($id);

		//-- make string
		$origin = class_top::img_origin($img_100, $img);
		$str 	= class_top::make_image_str($origin, $img_style, $popup_tag);

		echo $str;
	}



	//------------------------------------------------------
	//
	//  500을 넘겨서 100이 있으면 100을 출력 없으면 500 출력
	//
	function disp_image ( $img, $id=0 )
	{
		$img_style = "border=0 align=center width=100 name=img_main";
		$popup_tag = class_top::get_popup_tag($id);

		$img_100 = str_replace("_500.", "_100.", $img);

		if ($id > 0) {

			//-- make string
			$origin = class_top::img_origin($img_100, $img);
			$str 	= class_top::make_image_str($origin, $img_style, $popup_tag);
		}
		else
		{
			$str =  "<img src='/images/noimage.gif' align=absmiddle>";
		}

		echo $str;
	}

	//------------------------------------------------------
	//
	//  disp_image3
	//
   	function disp_image3 ( $product_id, $img = '' )
   	{
		echo class_top::disp_image5($product_id, $img);
	}

	//------------------------------------------------------
	//
	//  disp_image3_1 (문자열 리턴)
	//
	function disp_image3_1 ( $product_id, $img = '' )
	{
		$img_style = "border=0 align=center width=100 name=img_main";
		$popup_tag = class_top::get_popup_tag($product_id);

		list($img_top, $misc) = explode("_", $img);
		if ($img_top)
			$img_100  = $img_top . "_100." . substr($img, -3);

		
		//-- make string
		$origin = class_top::img_origin($img_100, $img);
		$str 	= class_top::make_image_str($origin, $img_style, $popup_tag);

		return $str;
	}



	//------------------------------------------------------
	// 문자열 리턴(파일명 또는 경로)  이미지 출력 [new table 에서 사용]
	function disp_image3_2 ( $product_id, $img = '', $width = 100 )
	{
		if (strpos($img, "http") === 0)
			$img_str = $img;
		else
		{
    		if($img)
    		{
        		$img_path = _upload_path . "/" . $img;
        		$new_path = _IMG_SERVER_ . "/" . $img_path;

    		    if( is_file($img_path) )
    			    $img_str = $img_path;  // local 서버
    	        else
    			    $img_str = $new_path;  // img 서버
            }
    	    else
                $img_str = "/images/noimage.gif";
		}

		$img_style = "width = $width height = $width align=absmiddle";
		$str = class_top::make_image_str($img_str, $img_style);
		$str = $str . "|" . $img;

		return $str;
	}

	//------------------------------------------------------
	//
	//  100 image echo with product_id
	//
	function disp_image3_3 ( $product_id, $img = '' )
	{
		$img_style = "border=0 align=center width=100 name=img_main";
		$popup_tag = class_top::get_popup_tag($product_id);

        list($file_org, $file_ext) = explode(".", $img);

		list($img_top, $misc) = explode("_", $img);
		$img_100  = $img_top . "_100." . $file_ext;

		//-- make string
		$origin = class_top::img_origin($img_100, $img);
		$str 	= class_top::make_image_str($origin, $img_style, $popup_tag);

		echo $str;
	}


	//--------------------------------------------------------
	//
	//	100 image return with product_id
	//
	function disp_image5 ( $product_id, $img = '' )
   	{
		$img_100 = $product_id . "_100." . substr($img, -3);

		$img_style = "border=0 align=center width=100 name=img_main";
		$popup_tag = class_top::get_popup_tag($product_id);

		//-- make string
		$origin = class_top::img_origin($img_100, $img);
		$str 	= class_top::make_image_str($origin, $img_style, $popup_tag);

		return $str;
	}

	//-------------------------------------------
	//
	// 상품코드로 100 이미지 리턴
	//
	function get_image_from_product_id($product_id)
	{
		global $connect;

		$query = "select img_500, org_id from products where product_id = '$product_id'";
		$d = mysql_fetch_assoc(mysql_query($query, $connect));

		if ( $d[org_id] )
		{
			$query = "select img_500 from products where product_id = '$d[org_id]'";
		  	$d = mysql_fetch_assoc(mysql_query($query, $connect));
		}     

		$img_100 = str_replace("_500.", "_100.", $d[img_500]);
		$img_style = "width=100 align=absmiddle";


		//-- make string
		$origin = class_top::img_origin($img_100, $d[img_500]);
		$str 	= class_top::make_image_str($origin, $img_style);

		return $str;
	}

	//-------------------------------------------
	//
	// 상품코드로 100 이미지 출력
	//
	function disp_image2 ( $product_id )
	{
		echo class_top::get_image_from_product_id($product_id);
   	}

	//-------------------------------------------
	//
	// 상품코드로 100 이미지 리턴
	//
	function disp_image4 ($product_id)
	{
		return class_top::get_image_from_product_id($product_id);
	}


   ///////////////////////////////////////////
   // transaction 생성
   // date : 2005.9.26
   function begin( $status = 0 , $target_id = 0)
   {
      global $connect, $template, $seq, $product_id, $id, $order_seq;

      $owner = $_SESSION[LOGIN_ID];
      $product_id = $product_id ? $product_id : $id;
      $seq = $seq ? $seq : $order_seq; 
      
      if ( !$target_id )
         $target_id = $seq ? $seq : $product_id;

      if ( $template == "D900" || $template == "DF00" )
         $target_id = $_REQUEST["shop_id"];

      /////////////////////////////////////////////////
      // build transaction id
      //$query = "select max(no) max from transaction";
      //$result = mysql_query ( $query, $connect );
      //$data = mysql_fetch_array ( $result );
      //$transaction = $data[max] + 1;

      ////////////////////////////////////////////////
      // insert transaction infos
      $query = "insert into transaction set template='$template', commit_date=Now(), starttime=Now(), owner='$owner', target_id='$target_id'";


      if ( $status )
         $query .= ",status='$status'";

      mysql_query ( $query, $connect );

      return $transaction;
   }

   ////////////////////////////////////////////////////
   // 입력 받은 transaction의 endtime을 적어줌
   // date: 2005.9.26
   function end( $transaction, $status = 0 )
   {
      global $connect;

      // transaction이 있어야만 수행한다     
      if ( $transaction ) 
      {
         // build query  
         $query = "update transaction set endtime=Now() ";

         if ( $status )
            $query .= ",status='" . $status . "'";

         $query .= " where no='$transaction'";

         mysql_query ( $query, $connect ); 
      }

      return 0;
   }

   function get_price_info( $product_id, &$org_price, &$supply_price, &$shop_price )
   {
       global $connect;

       $query = "select * from price_history where product_id='$product_id' and shop_id=0 order by seq desc limit 1 ";
       $result = mysql_query ( $query, $connect);
       $data = mysql_fetch_array ( $result);

       $supply_price = $data[supply_price];
       $shop_price = $data[shop_price];

       // 원가
       $query = "select org_price from products where product_id='$product_id'";
       $result = mysql_query ( $query, $connect);
       $data = mysql_fetch_array ( $result);

       $org_price = $data[org_price];
   }

      
  ///////////////////////////////////////////////////////////
  //
  // shop_id로 부터 어떤 판매처인지 정보를 가져오는 function
  // date : 2006.4.27 - jk.ryu
  // 
  function find_site( $shop_id )
  {
      $arr_site = array ( 
                          "gmarket" => array (10002, 10102, 10202,10302,10402,10502),
                        );
      
      foreach ( $arr_site as $key => $values )
         foreach ( $values as $v )
         { 
             if ( $shop_id == $v )
             {
                 return $key; // 사이트 값 리턴 
             }
         } 
  }
  /////////////////////////////////////////////
  //
  // XLS파일내부형식이 어떤형식인지 알아낸다.
  //
  /////////////////////////////////////////////
  function get_file_ext($filename)
  {
    $fd = fopen($filename, "r");
    $data = fread($fd, 128);

    if (bin2hex($data[0]) == 'd0' && bin2hex($data[1]) == 'cf' && bin2hex($data[2]) == '11' && bin2hex($data[3]) == 'e0')
    {
        $file_ext = "XLS";
    }
    else if (bin2hex($data[0]) == '3c' && bin2hex($data[1]) == '68' && bin2hex($data[2]) == '74' && bin2hex($data[3]) == '6d
')
    {
        $file_ext = "HTML";
    }
    else if (strpos($data, "\t"))
    {
        $file_ext = "TXT";
    }
    else if (strpos($data, ","))
    {
        $file_ext = "CSV";
    }
    else
    {
        $file_ext = "";
    }
    fclose($fd);
  
    return $file_ext;
  }

    ////////////////////////////////////////////////
    //
    // 현재 발주작업 상태 리턴
    //
    // DB에는 1 ~ 7 까지만 있음.
    //
    // 0:발주대기  1:발주중        2:상품정보수정
    // 3:매칭      4:묶음상품매칭  5:합포전검증
    // 6:합포      7:완료
    ////////////////////////////////////////////////
    function get_working_sts()
    {
        global $connect;
        
        $sql = "select sts from working_sts
                 order by crdate desc, seq desc limit 1";
        $result = mysql_query($sql, $connect) or die(mysql_error());
        $list = mysql_fetch_array($result);

        if ($list) return $list[sts];
        else return 0;
    }

    ////////////////////////////////////
    // return $list
    function get_working_row()
    {
        global $connect;
        
        $sql = "select * from working_sts
                 order by crdate desc, seq desc limit 1";
        $result = mysql_query($sql, $connect) or die(mysql_error());
        $list = mysql_fetch_array($result);

        if ($list) return $list;
        else return NULL;
    }

    /////////////////////////////////////////
    // set working rows
    function set_working_sts($status, $memo="")
    {
        global $connect;

        $row = get_working_row();

        if ($row != NULL) $current_sts = $row[sts];
        else $cureent_sts = 0;

        // 발주대기(0), 완료(7) 인경우에는 새로운 Row 추가
        if ($current_sts == 0 || $current_sts == 7)
        {
            if ($memo != "") $memo_str = "memo = '$memo',"; 
            $ins_sql = "insert  into working_sts set
                                sts    = '$status',
                                ${memo_str}
                                crdate = now()
            ";
            mysql_query($ins_sql, $connect) or die(mysql_error());
        }
        else
        {
            if ($memo != "") $memo_str = "memo = concat(memo, '$memo'),"; 
            $upd_sql = "update working_set set
                                ${memo_str}
                                sts    = '$status'
                         where crdate = '$row[crdate]'
                           and seq    = '$row[seq]'
            ";
            mysql_query($upd_sql, $connect) or die(mysql_error());
        }
    }


  ///////////////////////////////////////////
  // explode_ex($sep, $string)
  // only return not null string
  function explode_ex($sep, $string)
  {
    $arr = explode($sep, $string);
    for ($i=0, $j=0; $i < count($arr); $i++)
    {
        if (trim($arr[$i]))
        {
            $ret[$j] = $arr[$i];
            $j++;
        }
    }
    return $ret;
  }

  //////////////////////////////////////////////
  // 
  // insert sys_monitor
  //
  function insert_sys_mon($domain)
  {
    $sys_connect = sys_db_connect();

    $today = date("Y-m-d");
    $total_order = class_top::get_today_order();
  
    $sql = "select domain from sys_monitor
             where crdate = '$today'
               and domain = '$domain'";
    $list = mysql_fetch_array(mysql_query($sql, $sys_connect));

    // 주문건수를 가져와서 sys_monitor 테이블에 저장한다.
    if ($list[domain]) 
    {
        // update
        $work_sql = "update sys_monitor set
                                is_order    = '1',
                            total_order = '$total_order'
                      where crdate = '$today'
                        and domain = '$domain'";
    } else {
        // insert
        $work_sql = "insert into sys_monitor set
                            crdate         = now(),
                            crtime         = now(),
                            domain         = '$domain',
                                is_order    = '1',
                            total_order = '$total_order'";

    }
    @mysql_query($work_sql, $sys_connect);
  }

  //////////////////////////////////////////////
  // get today order count
  function get_today_order() 
  {
    global $connect;

    $today = date("Y-m-d");

    $sql = "select count(*) cnt from orders
             where collect_date = '$today'";
    $list = mysql_fetch_array(mysql_query($sql, $connect));
    
    return $list[cnt];
  }

    function htmlspecialchars_decode($string, $quote_style = null)
    {
        // Sanity check
        if (!is_scalar($string)) {
            user_error('htmlspecialchars_decode() expects parameter 1 to be string, ' .
                gettype($string) . ' given', E_USER_WARNING);
            return;
        }

        if (!is_int($quote_style) && $quote_style !== null) {
            user_error('htmlspecialchars_decode() expects parameter 2 to be integer, ' .
                gettype($quote_style) . ' given', E_USER_WARNING);
            return;
        }

        // Init
        $from   = array('&amp;', '&lt;', '&gt;');
        $to     = array('&', '<', '>');

        // The function does not behave as documented
        // This matches the actual behaviour of the function
        if ($quote_style & ENT_COMPAT || $quote_style & ENT_QUOTES) {
            $from[] = '&quot;';
            $to[]   = '"';

            $from[] = '&#039;';
            $to[]   = "'";
        }

        return str_replace($from, $to, $string);
    }
    
    function base64_encode_url($str)
    {
        $temp_str = base64_encode($str);
        return str_replace( array('+','/','='), array('-','_','.'), $temp_str);
    }

    function base64_decode_url($str)
    {
        $temp_str = str_replace( array('-','_','.'), array('+','/','='), $str);
        return base64_decode($temp_str);
    }
    
    function print_delivery( $delivery_office, $delivery_no, $icon_type=0, $option="" )
    {

    
        if($delivery_office == "") 
        {
            $delivery_office = "";
            return $delivery_office;
        }

        //$delivery_office = strtoupper( trim( $delivery_office ) );

        $result = "";
        $delivery_no = str_replace( array("\n","\r","\r\n"), "", $delivery_no );
        switch ( $delivery_office )
        {
                case "한덱스":
                        $result = "<a href='http://ptop.e-handex.co.kr:8080/jsp/tr/detailSheet.jsp?iSheetNo=$delivery_no' target=new>";
                        break;
                case "로엑스택배":
                        $result = "<a href='http://www.loexe.co.kr/customer/cus_trace_00_result.asp?invc_no=$delivery_no&searchMethod=I' target=new>";
                        break;
                case "로젠택배":
                        $result = "<a href='http://www.ilogen.com/iLOGEN.Web.New/TRACE/TraceView.aspx?gubun=slipno&slipno=$delivery_no' target=new>";
                        break;
                case "CJ택배":
                case "CJ 택배":
                case "CJGLS" :
                case "CJ GLS택배" :
                case "대한통운" :
                        $url = "https://www.doortodoor.co.kr/parcel/doortodoor.do?fsp_action=PARC_ACT_002&fsp_cmd=retrieveInvNoACT&invc_no=";
                        $result = "<a href='" . $url . $delivery_no ."' target=_new>";
                        break;
                case "사가와택배":
                case "SC로지스":
                case "SC로지스(사가와)":
                        $no = str_replace( array(" "),"",$delivery_no);
                        $result = "<a href='http://www.sc-logis.co.kr/cus_search_result.html?awbino=$no' target=new>";
                        break;
                case "삼성택배" :
                case "CJ HTH" :
                case "CJHTH" :
                        //$result = "<a href='http://cjhth.com/homepage/searchTraceGoods/SearchTraceDtdShtno.jhtml?dtdShtno=$delivery_no' target=_new>";
                        //$result = "<a href='http://www.cjgls.co.kr/kor/service/service02_01.asp?slipno={$delivery_no}' target=_new>";
                        $result = "<a href='http://nexs.cjgls.com/web/detailform.jsp?slipno={$delivery_no}' target=_new>";
                        break;
                case "아주택배" :
                case "아주택배(구형)" :
                        $no1 = substr( $delivery_no, 0,2);
                        $no2 = substr( $delivery_no, 2,4);
                        $no3 = substr( $delivery_no, 6,4);
                        $url = "http://www.ajulogis.co.kr/common/asp/search_history_proc.asp?sheetno1=" . $no1. "&sheetno2=$no2&sheetno3=$no3";
                        $result = "<a href='$url' target=_new>";
                        break;
                case "우체국" :
                case "우편등기" :
                case "우체국택배" :
                        $result = "<a href='http://trace.epost.go.kr/xtts/servlet/kpl.tts.common.svl.SttSVL?target_command=kpl.tts.tt.epost.cmd.RetrieveOrderConvEpostPoCMD&sid1={$delivery_no}' target=_new>";
                        break;
                case "한국택배" :
                        $result = "<a href='http://dms.ktlogis.com:8080/trace/TraceProduct.jsp' target=_new>$delivery_office</a>";
                        break;
                case "한진택배" :
                        $result = "<a href='http://www.hanjin.co.kr/delivery_html/inquiry/result_waybill.jsp?wbl_num=".trim($delivery_no)."' target=_new>";
                        break;
                case "하나로택배" :
                        $result = "<a href='http://www.hanarologis.com/branch/chase/listbody.html?a_gb=center&a_cd=4&a_item=0&fr_slipno=$delivery_no' target=new>";
                        break;
                case "현대택배" :
                        //$result = "<a href='http://www.hyundaiexpress.com/hydex/jsp/support/search/re_08.jsp?InvNo={$delivery_no}' target=_new>";
                        //$result = "<a href='http://admin.ezadmin.co.kr/proxy_transno.pl?trans_no={$delivery_no}&trans_corp=hyundai' target=_new>";
                        $result = "<a href='http://www.hlc.co.kr/hydex/jsp/tracking/trackingViewCus.jsp?InvNo={$delivery_no}' target=_new>";
                        break;
                case "트라넷택배" :
                        $result = "<a href='/template/tranet.htm?gubun=1&iv_no={$delivery_no}' target=_new>";
                        break;
                case "KGB택배" :
                case "KGB" :
                        $result = "<a href='http://www.kgbls.co.kr/tracing.asp?number={$delivery_no}' target=new>";
                        break;
                case "KGB특급택배" :
                        $result = "<a href='http://www.ikgb.co.kr/' target=_new>";
                        break;
                case "훼미리택배" :
                        if ( strlen( $delivery_no) < 10 )
                                $result = "<a href='http://www.e-family.co.kr/' target=_new>";
                        else
                                $result = "<a href='http://www.e-family.co.kr/tracking.jsp?item_no1=". substr( $delivery_no, 0, 4 ) ."&item_no2=". substr( $delivery_no, 4, 4 ) . "&item_no3=". substr( $delivery_no, 8, 4 )."' target=_new>";
                        break;
                case "동부익스프레스택배" :
                        if ( strlen( $delivery_no) < 10 )
                                $result = "<a href='http://www.e-family.co.kr/' target=_new>";
                        else
                                $result = "<a href='http://www.dongbups.com/newHtml/delivery/dvsearch_View.jsp?item_no=$delivery_no' target=_new>";
                        break;
                case "이클라인" :                
                        $result = "<a href='http://www.ecline.net/tracking/customer02.html#t01' target=_new>";
                        break;
                case "이노지스" :                
                case "이노지스택배" :
                case "GTX 로지스" :
                        $result = "<a href='http://www.gtxlogis.co.kr/tracking/default.asp?awblno=$delivery_no' target=_new>";
                        break;
                case "옐로우캡" :
                        $result = "<a href='http://www.yellowcap.co.kr/custom/inquiry_result.asp?INVOICE_NO=$delivery_no' target=_new>";
                        break;
                case "경동택배" :
                        $result = "<a href='http://k.kdexp.com/insu/sc_sear.asp?stype=1&p_item=$delivery_no' target=_new>";
                        // $result = "<a href='http://www.kdexp.com/sub3_shipping.asp?stype=1&p_item=$delivery_no' target=_new>";
                        break;
                case "합동택배" :
                        $result = "<a href='http://www.hdexp.co.kr/parcel/order_result.asp?stype=1&p_item=$delivery_no' target=_new>";
                        break;
                default :
                        return $delivery_office;
                        break;
        }

        if ( $icon_type )
          $result .= "<img src=images/icon_04.gif alt=배송확인 align=absmiddle>"; 
        
        if( $option == "cs_trans_no" )
            $result .= "$delivery_no </a>";
        else if( $option !="FB00" )
            $result .= "$delivery_office </a>";
            //$result .= "<img src=images/car.gif border=0 align=absmiddle alt='택배조회'>";
            
        else $result .= "&nbsp;";
        
        return $result;
   }    
   
    function event_log( $type, $cmt )
    {
        global $sys_connect;
        
        $query_event = "insert sys_event_list 
                           set domain = '" . _DOMAIN_ . "', 
                               who = '" . $_SESSION[LOGIN_ID] . "',
                               event = '$type',
                               cmt = '$cmt'";
        mysql_query($query_event, $sys_connect);
    }
    
    function link_product1($product_id, $val)
    {
        return "<a href='javascript:openwin2(\"popup_utf8.htm?template=C208&id=" . $product_id . "\",\"change_product\", 950, 800)'>" . $val . "</a>";
    }

    function link_product2($product_id, $val)
    {
        return "<a href='javascript:openwin2(\"popup_utf8.htm?template=C231&id=" . $product_id . "&cs_open=1\",\"change_option_product\", 500, 480)'>" . $val . "</a>";
    }

    function get_trans_corp_name($trans_corp)
    {
        global $sys_connect;
        
	    $query = "select * from sys_transinfo where id=$trans_corp";
	    $result = mysql_query($query, $sys_connect);
	    $data = mysql_fetch_assoc($result);

        return $data[trans_corp];
    }

    function get_cs_type_str($t)
    {
        switch($t)
        {
            case 0  : $str = '일반'        ; break;
            case 1  : $str = '보류설정'    ; break;
            case 2  : $str = '보류해제'    ; break;
            case 3  : $str = '배송정보변경'; break;
            case 4  : $str = '송장입력'    ; break;
            case 5  : $str = '송장삭제'    ; break;
            case 6  : $str = '배송확인'    ; break;
            case 7  : $str = '배송취소'    ; break;
            case 8  : $str = '합포추가'    ; break;
            case 9  : $str = '합포제외'    ; break;
            case 10 : $str = '전체취소'    ; break;
            case 11 : $str = '주문취소'    ; break;
            case 12 : $str = '전체정상복귀'; break;
            case 13 : $str = '주문정상복귀'; break;
            case 14 : $str = '주문복사'    ; break;
            case 15 : $str = '주문생성'    ; break;
            case 16 : $str = '개별취소'    ; break;
            case 17 : $str = '상품교환'    ; break;
            case 18 : $str = '개별정상복귀'; break;
            case 19 : $str = '상품추가'    ; break;
            case 20 : $str = '매칭삭제'    ; break;
            case 21 : $str = '미송설정'    ; break;
            case 22 : $str = '회수설정'    ; break;
            case 23 : $str = '우선순위설정'; break;
            case 24 : $str = '우선순위해제'; break;
            case 25 : $str = '회수수정'    ; break;
            case 26 : $str = '회수접수'    ; break;
            case 27 : $str = '회수도착'    ; break;
            case 28 : $str = '회수개봉'    ; break;
            case 29 : $str = '합포금지설정'; break;
            case 30 : $str = '합포금지해제'; break;
            case 31 : $str = 'SMS전송'     ; break;
            case 33 : $str = '주문삭제'    ; break;
            case 34 : $str = '회수요청'    ; break;
            case 35 : $str = '반품예정'    ; break;
            case 36 : $str = '가배송처리'  ; break;
            case 100: $str = '완료처리'    ; break;
            default : $str = '';
        }
        return $str;
    }
    
    function svr_load_log($t, $work)
    {
        global $sys_connect;
        
        return;
        $svr_load_time = time() - $t;
        $query_event = "insert sys_event_list 
                           set domain = '" . _DOMAIN_ . "', 
                               who = '" . $_SESSION[LOGIN_ID] . "',
                               event = 'Sever Load Check',
                               cmt = '$work|$svr_load_time'";
        mysql_query($query_event, $sys_connect);
    }

    function get_barcode($product_id, $auto=0, $barcode='')
    {
        global $connect;
        
        if( _DOMAIN_ == 'pinkage' )
        {
            // 가발인지 아닌지 제조사로 확인
            $query = "select maker from products where product_id='$product_id'";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            if( $data[maker] == '유통바코드' )
            {
                // 8809341195657 부터
                $query = "insert ean13_code set product_id='$product_id', crdate=now()";
                mysql_query($query, $connect);

                $query = "select max(seq) max_seq from ean13_code";
                $result = mysql_query($query, $connect);
                $data = mysql_fetch_assoc($result);
                
                $barcode = 880934988001 + $data[max_seq] - 435;
    
                $len = strlen($barcode);
                $oddsum = 0;
                $evensum = 0;
                for($i=0; $i<$len; $i++)
                {
                    // 홀수
                    if($i % 2 == 0)  
                        $oddsum += substr($barcode, $i, 1);
                    // 짝수
                    else
                        $evensum += substr($barcode, $i, 1);
                }
                $allsum = $evensum * 3 + $oddsum;
                $checkcode = 10 - ($allsum % 10);
                
                $barcode .= ($checkcode == 10 ? "0" : $checkcode);
            }
            else
            {
                $barcode = strtoupper( $_SESSION[BARCODE_FORMAT] . $product_id );
            }
        }
        else
        {
            if( $auto )
                // 자동생성 안함. 지정한 값으로
                $barcode = strtoupper( $barcode );
            else
                // 자동생성.
                $barcode = strtoupper( $_SESSION[BARCODE_FORMAT] . $product_id );
        }
    	return $barcode;
    }

    function popupcs($seq, $str='', $order_id='')
    {
        global $connect;
        
        $query = "select collect_date, order_id from orders where seq=$seq";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        
        $start_date = date('Y-m-d', strtotime('-30 day'));
        
        if( $start_date > $data[collect_date] )  
            $s_date = $data[collect_date];
        else
            $s_date = $start_date;
            
        if( $str == '' )  $str = $seq;

        if( $order_id )
            return "<a href=\"javascript:popupcs_order_id( $seq, '$s_date', '$order_id' )\">$str</a>";
        else
            return "<a href=\"javascript:popupcs( $seq, '$s_date' )\">$str</a>";
    }

    function array_array_sort($multiArray, $keyColumn) {
        foreach($multiArray as $tmpRecords){
            $sortColumn[] = $tmpRecords[$keyColumn];        
        }
        array_multisort($sortColumn, SORT_ASC, $multiArray);
        return $multiArray;
    }
    
    function array_array_rsort($multiArray, $keyColumn) { 
        foreach($multiArray as $tmpRecords){
            $sortColumn[] = $tmpRecords[$keyColumn];        
        }
        array_multisort($sortColumn, SORT_DESC, $multiArray);
        return $multiArray;
    }    

    // 멀티컬럼 정렬
    function array_multi_column_sort($ss_arr, &$data_all)
    {
        foreach ($ss_arr as $ss_key => $ss_val) 
        {
            if (is_string($ss_val)) 
            {
                $tmp = array();
                foreach ($data_all as $da_key => $da_val)
                    $tmp[$da_key] = $da_val[$ss_val];
                $ss_arr[$ss_key] = $tmp;
            }
        }
        $ss_arr[] = &$data_all;
        call_user_func_array('array_multisort', $ss_arr);
    }
    

    //-----------------------------------
    // 필드를 가지고 있는 테이블을 리턴
    function find_tables_having_field($field, $filter="")
    {
        global $connect;

        $arr = array();

        $sql = "show tables";
        if ($filter) $sql .= " like '$filter%'";

        $result = mysql_query($sql, $connect);
        while ($table = mysql_fetch_array($result))
        {
            $table_name = $table[0];
            $result2 = mysql_query("SHOW COLUMNS FROM $table_name", $connect);
            while ($row = mysql_fetch_assoc($result2))
            {
                if ($row['Field'] == $field) {
                    $arr[] = $table_name;
                    continue;
                }
            }
        }

        return $arr;
    }


    //###############################
    // 입고예정 수량 구하기
    //###############################
    function get_expect_stockin_qty($product_id)
    {
        global $connect;

        $query_exp = "select ifnull(sum(b.exp_qty), 0) sum_qty
                        from expect_stockin_sheet a
                            ,expect_stockin_item b
                       where a.seq = b.sheet_seq
                         and a.is_delete = 0
                         and a.status = 0
                         and b.product_id = '$product_id' ";
        $result_exp = mysql_query($query_exp, $connect);
        $data_exp = mysql_fetch_assoc($result_exp);
        
        return $data_exp[sum_qty];
    }


    //###############################
    // get_default_zip_code
    //###############################
    function get_default_zip_code($return_type = "string")
    {
    	$zip_str = "343852,355842,355845,355846,355847,355848,356878,357941,400460,409830,409831,409832,409833,409840,409841,409842,409850,409851,409852,409853,409880,409881,409882,409883,409890,409891,409892,409893,409910,409911,409912,409913,409919,417910,417911,417912,417913,417920,417921,417922,417923,417930,417931,417932,417933,513890,513891,513892,513893,530145,530430,530440,535805,535806,535811,535813,535816,535823,535824,535830,535831,535832,535833,535834,535835,535836,535837,535838,535840,535841,535842,535843,535844,535845,535847,535850,535851,535852,535860,535861,535862,535863,535870,535871,535872,535873,535880,535881,535882,535883,535884,535885,535890,535891,535892,535893,535894,535895,535896,535897,535898,535910,535911,535912,535913,535914,535915,535916,535917,535918,535919,535920,535921,535922,535923,535924,535925,535926,535930,535931,535932,535933,535934,535935,535936,535940,535941,535942,535943,536928,536929,536935,537809,537814,537815,537816,537817,537818,537820,537821,537822,537823,537824,537825,537826,537830,537831,537832,537833,537834,537835,537836,537840,537841,537842,537843,537844,537845,537846,537847,537848,537849,537850,537851,537852,537853,537900,537901,537902,537903,537904,537905,537907,537909,537920,537921,537922,539910,539911,539912,539913,539914,539915,539916,539917,539918,539919,546908,548894,548902,548906,548909,548941,548990,548991,548992,548993,548994,550270,556830,556831,556832,556834,556835,556836,556837,556838,556839,556840,556841,556842,556843,556844,556846,556847,556848,556849,556850,556851,556852,556853,556854,556855,573810,573811,573812,573813,573814,573815,573816,573817,573818,573819,573955,579910,579911,579912,579913,579914,579915,618420,618430,618440,618450,650833,650835,650910,650911,650912,650913,650914,650915,650916,650920,650921,650922,650923,650924,650925,650926,650927,650930,650931,650932,650933,650934,650941,650944,650945,650946,656876,664250,664270,695950,695951,695952,695980,695983,799800,799801,799802,799803,799804,799805,799810,799811,799812,799813,799820,799821,799822,799823,690831,	690851,	697823,	695794,	697836,	690803,	690819,	690835,	690772,	699945,	690744,	695919,	690844,	699926,	697861,	695925,	690191,	690043,	695971,	690130,	690600,	695930,	699900,	695979,	697853,	695900,	695907,	690842,	697705,	699923,	697100,	699947,	690073,	697703,	690799, 697831,	699906,	697110,	695975,	690707,	697844,	690778,	690822,	690846,	690050,	697826,	697838,	690767,	690811,	697011,	690728,	690003,	699921,	690736,	690712,	690032,	690192,	697014,	690796,	697862,	690808,	697010,	695906,	690817,	690709,	690786,	695969,	690012,	690700,	695981,	697320,	697600,	690821,	697390,	690220,	695978,	697847,	695918,	690741,	697839,	695943,	690812,	690782,	695972,	690161,	699940,	697849,	697020,	690805,	690719,	695912,	690841,	695946,	690760,	697370,	699915,	695910,	697840,	695977,	695961,	697829,	699702,	697310,	697837,	690755,	690031,	697856,	690833,	695793,	690779,	697841,	699913,	699905,	690723,	697858,	697808,	697806,	690747,	690042, 690743,	699937,	699924,	695944,	690764,	690041,	690775,	695968,	697380,	699916,	695949,	699903,	699930,	690777,	697832,	697050,	697340,	690180,	690162,	695795,	695965,	690802,	690072,	690122,	697360,	697350,	697819,	697822,	690081,	690071,	695928,	695904,	690721,	690826,	695942,	695947,	697834,	697707,	695945,	690701,	690610,	697706,	697704,	695976,	690838,	690732,	697854,	695911,	690813,	697855,	690765,	699904,	697821,	695948,	690809,	699942,	697852,	690800,	690823,	695967,	695901,	690750,	697827,	690801,	695926,	697825,	690022,	690751,	690834,	699701,	697851,	695932,	695982,	690210,	690836,	697864,	690082,	690785,	695980,	695791,	690773,	697828,	699934,	699931, 690110,	690824,	697013,	697820,	690810,	690847,	697060,	695970,	697830,	690771,	690742,	690787,	690776,	699922,	697842,	690843,	695908,	697859,	699920,	690726,	690200,	690710,	690815,	699908,	697070,	695974,	697835,	690807,	690725,	690029,	690718,	690140,	690150,	695941,	699949,	699933,	697807,	690790,	690730,	690840,	695913,	690774,	699944,	695914,	690830,	690717,	695927,	697846,	690740,	690090,	690780,
    		699948,	697030,	699912,	697843,	690789,	695915,	690242,	690818,	695940,	697860,	690722,	690828,	690729,	690011,	695923,	690705,	695973,	690820,	690021,	695924,	695933,	695905,	695934,	690100,	690762,	690232,	695796,	695917,	697700,	697833,	697080,	697848,	699911, 690788,	690714,	697863,	697040,	695931,	690715,	690704,	690781,	690837,	690829,	690734,	695922,	690706,	699914,	697330,	695903,	690121,	690231,	695964,	690756,	695920,	699946,	697701,	699902,	690850,	690163,	690731,	690735,	690170,	695960,	690804,	695929,	695902,	695789,	695705,	690241,	690814,	697120,	699910,	690724,	690720,	695962,	690727,	697857,	697845,	690806,	690708,	690061,	695909,	690711,	695916,	699901,	699932,	697850,	699941,	695983,	697805,	697130,	690737,	697012,	690739,	699925,	695963,	690766,	695921,	690839,	690770,	690832,	699943,	697090,	690738,	697824,	690769,	697301,	690827,	690062,	695792,	699936,	690703,	690825,	699935,	699907,	690816,	695966,618410,697802, 697912 ";
    	$zip_arr = explode(",", $zip_str);
    	
    	
    	if($return_type=="string")
			return $zip_str;
		else if($return_type=="array")
			return $zip_arr;
    }
    
    //###############################
    // donnandeco_zip_code
    //###############################
    function get_donnandeco_zip_code($return_type,$zip_type)
    {
    	$zip_arr = array();
    	$zip_str = "";
    	
    	switch($zip_type)
    	{
    		case "all": //전체 도서지역
    			$zip_str = "355842, 355843, 355844, 355845, 355846, 355847, 355848, 357941, 537810, 537820, 537830, 537840, 537850, 537920, 548894, 548902, 548906, 548909, 548941, 573810, 579910, 664250, 664270, 417910, 417920, 417930, 343814, 400460, 409830, 409840, 409850, 409870, 409880, 409890, 409910, 530430, 530440, 550270, 555300, 556830, 556840, 556850, 799800, 799810, 799820, 695980, 695950, 343852, 513890, 546908, 618410, 618411, 618412, 618413, 618414, 618415, 618416, 618417, 618418, 618419, 618420, 618421, 618422, 618423, 618424, 618425, 618426, 618427, 618428, 618429, 618430, 618431, 618432, 618433, 618434, 618435, 618436, 618437, 618438, 618439, 618440, 618441, 618442, 618443, 618444, 618445, 618446, 618447, 618448, 618449, 618450, 618451, 618452, 618453, 618454, 618455, 618456, 618457, 618458, 618459, 618460, 618461, 618462, 618463, 618464, 618465, 618466, 618467, 618468, 618469, 618470, 618471, 618472, 618473, 618474, 618475, 618476, 618477, 618478, 618479, 618480, 618481, 618482, 618483, 618484, 618485, 618486, 618487, 618488, 618489, 618490, 618491, 618492, 618493, 618494, 618495, 618496, 618497, 618498, 618499, 618500, 618501, 618502, 618503, 618504, 618505, 618506, 618507, 618508, 618509, 618510, 618511, 618512, 618513, 618514, 618515, 618516, 618517, 618518, 618519, 618520, 618521, 618522, 618523, 618524, 618525, 618526, 618527, 618528, 618529, 618530, 618531, 618532, 618533, 618534, 618535, 618536, 618537, 618538, 618539, 618540, 618541, 618542, 618543, 618544, 618545, 618546, 618547, 618548, 618549, 618550, 618551, 618552, 618553, 618554, 618555, 618556, 618557, 618558, 618559, 618560, 618561, 618562, 618563, 618564, 618565, 618566, 618567, 618568, 618569, 618570, 618571, 618572, 618573, 618574, 618575, 618576, 618577, 618578, 618579, 618580, 618581, 618582, 618583, 618584, 618585, 618586, 618587, 618588, 618589, 618590, 618591, 618592, 618593, 618594, 618595, 618596, 618597, 618598, 618599, 618600, 618601, 618602, 618603, 618604, 618605, 618606, 618607, 618608, 618609, 618610, 618611, 618612, 618613, 618614, 618615, 618616, 618617, 618618, 618619, 618620, 618621, 618622, 618623, 618624, 618625, 618626, 618627, 618628, 618629, 618630, 618631, 618632, 618633, 618634, 618635, 618636, 618637, 618638, 618639, 618640, 618641, 618642, 618643, 618644, 618645, 618646, 618647, 618648, 618649, 618650, 618651, 618652, 618653, 618654, 618655, 618656, 618657, 618658, 618659, 618660, 618661, 618662, 618663, 618664, 618665, 618666, 618667, 618668, 618669, 618670, 618671, 618672, 618673, 618674, 618675, 618676, 618677, 618678, 618679, 618680, 618681, 618682, 618683, 618684, 618685, 618686, 618687, 618688, 618689, 618690, 618691, 618692, 618693, 618694, 618695, 618696, 618697, 618698, 618699, 618700, 618701, 618702, 618703, 618704, 618705, 618706, 618707, 618708, 618709, 618710, 618711, 618712, 618713, 618714, 618715, 618716, 618717, 618718, 618719, 618720, 618721, 618722, 618723, 618724, 618725, 618726, 618727, 618728, 618729, 618730, 618731, 618732, 618733, 618734, 618735, 618736, 618737, 618738, 618739, 618740, 618741, 618742, 618743, 618744, 618745, 618746, 618747, 618748, 618749, 618750, 618751, 618752, 618753, 618754, 618755, 618756, 618757, 618758, 618759, 618760, 618761, 618762, 618763, 618764, 618765, 618766, 618767, 618768, 618769, 618770, 618771, 618772, 618773, 618774, 618775, 618776, 618777, 618778, 618779, 618780, 618781, 618782, 618783, 618784, 618785, 618786, 618787, 618788, 618789, 618790, 618791, 618792, 618793, 618794, 618795, 618796, 618797, 618798, 618799, 618800, 618801, 618802, 618803, 618804, 618805, 618806, 618807, 618808, 618809, 618810, 618811, 618812, 618813, 618814, 618815, 618816, 618817, 618818, 618819, 618820, 618821, 650833, 650835, 650910, 650920, 650930, 650940, 650944, 650945, 650946, 656876, 356862, 356863, 356864, 356865, 356866, 356867, 356868, 356869, 356870, 356871, 356872, 356873, 356874, 356875, 356876, 356877, 356878, 530130, 530145, 535705, 535800, 535801, 535802, 535803, 535804, 535805, 535806, 535807, 535810, 535811, 535812, 535813, 535814, 535815, 535816, 535817, 535830, 535831, 535832, 535833, 535834, 535835, 535836, 535837, 535838, 535840, 535841, 535842, 535843, 535844, 535845, 535846, 535847, 535850, 535851, 535852, 535860, 535861, 535862, 535863, 535870, 535871, 535872, 535873, 535880, 535881, 535882, 535883, 535884, 535885, 535890, 535891, 535892, 535893, 535894, 535895, 535896, 535897, 535898, 535910, 535911, 535912, 535913, 535914, 535915, 535916, 535917, 535918, 535919, 535920, 535921, 535922, 535923, 535924, 535925, 535926, 535930, 535931, 535932, 535933, 535934, 535935, 535936, 535940, 535941, 535942, 535943, 537900, 539910, 548990";
	        	for($i = 690000 ; $i<= 699999 ; $i++)
	        	{
	        		$zip_str .=", $i";
	        	}
    			$zip_arr = explode(", ", $zip_str); 
    		break;	
    		case "1": // 제주포함 3000
    			$zip_str = "664250, 664270";
	        	for($i = 690000 ; $i<= 699999 ; $i++)
	        	{
	        		if($i == 695980 || $i == 695950)
	        			continue;
	        		$zip_str .=", $i";
	        	}
    			$zip_arr = explode(", ", $zip_str);
    		break;
    		case "2": // 4000
    			$zip_str = "343852, 513890, 546908, 618410, 618411, 618412, 618413, 618414, 618415, 618416, 618417, 618418, 618419, 618420, 618421, 618422, 618423, 618424, 618425, 618426, 618427, 618428, 618429, 618430, 618431, 618432, 618433, 618434, 618435, 618436, 618437, 618438, 618439, 618440, 618441, 618442, 618443, 618444, 618445, 618446, 618447, 618448, 618449, 618450, 618451, 618452, 618453, 618454, 618455, 618456, 618457, 618458, 618459, 618460, 618461, 618462, 618463, 618464, 618465, 618466, 618467, 618468, 618469, 618470, 618471, 618472, 618473, 618474, 618475, 618476, 618477, 618478, 618479, 618480, 618481, 618482, 618483, 618484, 618485, 618486, 618487, 618488, 618489, 618490, 618491, 618492, 618493, 618494, 618495, 618496, 618497, 618498, 618499, 618500, 618501, 618502, 618503, 618504, 618505, 618506, 618507, 618508, 618509, 618510, 618511, 618512, 618513, 618514, 618515, 618516, 618517, 618518, 618519, 618520, 618521, 618522, 618523, 618524, 618525, 618526, 618527, 618528, 618529, 618530, 618531, 618532, 618533, 618534, 618535, 618536, 618537, 618538, 618539, 618540, 618541, 618542, 618543, 618544, 618545, 618546, 618547, 618548, 618549, 618550, 618551, 618552, 618553, 618554, 618555, 618556, 618557, 618558, 618559, 618560, 618561, 618562, 618563, 618564, 618565, 618566, 618567, 618568, 618569, 618570, 618571, 618572, 618573, 618574, 618575, 618576, 618577, 618578, 618579, 618580, 618581, 618582, 618583, 618584, 618585, 618586, 618587, 618588, 618589, 618590, 618591, 618592, 618593, 618594, 618595, 618596, 618597, 618598, 618599, 618600, 618601, 618602, 618603, 618604, 618605, 618606, 618607, 618608, 618609, 618610, 618611, 618612, 618613, 618614, 618615, 618616, 618617, 618618, 618619, 618620, 618621, 618622, 618623, 618624, 618625, 618626, 618627, 618628, 618629, 618630, 618631, 618632, 618633, 618634, 618635, 618636, 618637, 618638, 618639, 618640, 618641, 618642, 618643, 618644, 618645, 618646, 618647, 618648, 618649, 618650, 618651, 618652, 618653, 618654, 618655, 618656, 618657, 618658, 618659, 618660, 618661, 618662, 618663, 618664, 618665, 618666, 618667, 618668, 618669, 618670, 618671, 618672, 618673, 618674, 618675, 618676, 618677, 618678, 618679, 618680, 618681, 618682, 618683, 618684, 618685, 618686, 618687, 618688, 618689, 618690, 618691, 618692, 618693, 618694, 618695, 618696, 618697, 618698, 618699, 618700, 618701, 618702, 618703, 618704, 618705, 618706, 618707, 618708, 618709, 618710, 618711, 618712, 618713, 618714, 618715, 618716, 618717, 618718, 618719, 618720, 618721, 618722, 618723, 618724, 618725, 618726, 618727, 618728, 618729, 618730, 618731, 618732, 618733, 618734, 618735, 618736, 618737, 618738, 618739, 618740, 618741, 618742, 618743, 618744, 618745, 618746, 618747, 618748, 618749, 618750, 618751, 618752, 618753, 618754, 618755, 618756, 618757, 618758, 618759, 618760, 618761, 618762, 618763, 618764, 618765, 618766, 618767, 618768, 618769, 618770, 618771, 618772, 618773, 618774, 618775, 618776, 618777, 618778, 618779, 618780, 618781, 618782, 618783, 618784, 618785, 618786, 618787, 618788, 618789, 618790, 618791, 618792, 618793, 618794, 618795, 618796, 618797, 618798, 618799, 618800, 618801, 618802, 618803, 618804, 618805, 618806, 618807, 618808, 618809, 618810, 618811, 618812, 618813, 618814, 618815, 618816, 618817, 618818, 618819, 618820, 618821, 650833, 650835, 650910, 650920, 650930, 650940, 650944, 650945, 650946, 656876";
    			$zip_arr = explode(", ", $zip_str);
    		break;
    		case "3": // 4500
    			$zip_str = "417910, 417920, 417930";
    			$zip_arr = explode(", ", $zip_str);
    		break;
    		case "4": // 5000
    			$zip_str = "355842, 355843, 355844, 355845, 355846, 355847, 355848, 357941, 537810, 537820, 537830, 537840, 537850, 537920, 548894, 548902, 548906, 548909, 548941, 573810, 579910";
    			$zip_arr = explode(", ", $zip_str);
    		break;
    		case "5": // 6000
    			$zip_str = "343814, 400460, 409830, 409840, 409850, 409870, 409880, 409890, 409910, 530430, 530440";
    			$zip_arr = explode(", ", $zip_str);
    		break;
    		case "6": // 7000
    			$zip_str = "356862, 356863, 356864, 356865, 356866, 356867, 356868, 356869, 356870, 356871, 356872, 356873, 356874, 356875, 356876, 356877, 356878, 530130, 530145, 535705, 535800, 535801, 535802, 535803, 535804, 535805, 535806, 535807, 535810, 535811, 535812, 535813, 535814, 535815, 535816, 535817, 535830, 535831, 535832, 535833, 535834, 535835, 535836, 535837, 535838, 535840, 535841, 535842, 535843, 535844, 535845, 535846, 535847, 535850, 535851, 535852, 535860, 535861, 535862, 535863, 535870, 535871, 535872, 535873, 535880, 535881, 535882, 535883, 535884, 535885, 535890, 535891, 535892, 535893, 535894, 535895, 535896, 535897, 535898, 535910, 535911, 535912, 535913, 535914, 535915, 535916, 535917, 535918, 535919, 535920, 535921, 535922, 535923, 535924, 535925, 535926, 535930, 535931, 535932, 535933, 535934, 535935, 535936, 535940, 535941, 535942, 535943, 537900, 539910, 548990";
    			$zip_arr = explode(", ", $zip_str);
    		break;
    		case "7": // 8000
    			$zip_str = "550270, 555300, 556830, 556840, 556850, 799800, 799810, 799820";
    			$zip_arr = explode(", ", $zip_str);
    		break;
    		case "8": // 10000
    			$zip_str = "695980";
    			$zip_arr = explode(", ", $zip_str);
    		break;
    		case "9": // 11000
    			$zip_str = "695950";
    			$zip_arr = explode(", ", $zip_str);
    		break;
    	}
    	    	
		if($return_type=="string")
		{
			return $zip_str;
		}
		else if($return_type=="array")
		{
			return $zip_arr;
		}
    }

    //###############################
    // 이지체인 창고재고조회
    //###############################
    function get_ecn_info()
    {
        global $template, $connect, $ecn_stock, $ecn_w_info, $ecn_w_list;

        $ecn_w_info = array();
        if( _DOMAIN_ == 'dabagirl2' || _DOMAIN_ == 'pimz' )
        {
            $ecn_stock = true;
            $ecn_w_list = "";

            $query_ecn = "select seq, name from ecn_warehouse where type='w' order by seq";
            $result_ecn = mysql_query($query_ecn, $connect);
            while( $data_ecn = mysql_fetch_assoc($result_ecn) )
            {
                $ecn_w_info[] = array(
                    "seq"  => $data_ecn[seq]
                   ,"name" => $data_ecn[name]
                );
                $ecn_w_list .= ($ecn_w_list ? "," : "") . $data_ecn[seq];
            }
        }
        else 
            $ecn_stock = false;
    }

    //###############################
    // 한글 euckr 파일명 이미지 url 처리
    //###############################
    function convert_euckr_img_filename($origin)
    {
        if( preg_match('/^(.+\/)([^\.\/]+\.[^\.\/]+)$/', $origin, $matches ) )
            $origin = $matches[1] . urlencode(iconv("utf-8","cp949",$matches[2]));
        
        return $origin;
    }

    //###############################
    // CS 전화번호 검색 입력
    //###############################
    function inset_tel_info($seq, $org_tel)
    {
        global $connect;
        
        $new_tel = array();
        foreach($org_tel as $tel_val)
        {
            $_tel = preg_replace('/[^0-9]/','',$tel_val);
            if( strlen($_tel) >= 4 )
                $new_tel[] = $_tel;
        }

        $new_tel = array_unique($new_tel);
        foreach($new_tel as $tel_val)
        {
            $tel_short = substr($tel_val, -4);

            $query_ins = "insert tel_info set seq=$seq, tel='$tel_val', tel_short='$tel_short' ";
            mysql_query($query_ins, $connect);
        }
    }

    //###############################
    // 특수주문 검색 쿼리 DC00, D800, DS00
    //###############################
    function query_special_order()
    {
    	$ret = " and ( ( a.shop_id % 100 = 7  and a.order_type <> '주문' and a.order_type <> 3 ) or
                       ( a.shop_id % 100 = 65 and a.order_type = '교환배송' ) or
                       ( a.shop_id % 100 = 9  and a.order_type <> '주문' and a.order_type <> 3 ) or
                       ( a.shop_id % 100 = 14 and a.order_type <> '주문' and a.order_type <> 3 ) or
                       ( a.shop_id % 100 = 26 and a.order_type <> '주문' and a.order_type <> 3 ) or 
                       ( a.shop_id % 100 = 27 and a.order_type =  'Y' ) or 
                       ( a.shop_id % 100 = 70 and a.order_type =  'Y' ) or 
                       ( a.shop_id % 100 = 1  and a.order_type =  '방문수령' ) or 
                       ( a.shop_id % 100 = 2  and a.order_type =  '방문수령' ) ) ";
        return $ret;
    }



    // 엑셀파일 다운로드 셀 형식
    var $default_header = "
<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
<html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
<style>
.header_item{
    font:bold 12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
    background:#CCFFCC;
	text-align:center;
}
.num_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
	mso-number-format:\"\\#\\,\\#\\#0_ \\;\\[Red\\]\\\\-\\#\\,\\#\\#0\\\\ \";
}
.per_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
	mso-number-format:0%;
}
.str_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
}
.str_item_center{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
	text-align:center;
}
.mul_item{
    font:12px \"굴림\"; 
    white-space:nowrap; 
    mso-number-format:\\@;
    white-space:normal;
}
br
    {mso-data-placement:same-cell;}
</style>
<body>
<table border=1>
";

}
