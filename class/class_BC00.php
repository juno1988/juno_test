<?
require_once "class_B.php";
require_once "class_top.php";

class class_BC00 extends class_top
{
   var $arr_items;
   var $val_items;  // 반듯이 입력해야 하는 item

   function init_val_items()
   {
      $this->val_items = array ("id"=>"회원 아이디", "passwd"=>"비밀 번호", "level"=>"회원 등급", "name"=>"회원 이름");
   }

   function BC00()
   {
      global $template;
      $link_url = "?" . $this->build_link_url();
      $line_per_page = _line_per_page;

      $list_type = "member";
      $result = $this->get_member_list ( &$total_rows, $list_type );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function BC01()
   {
      global $template;

      $this->init_val_items();
      $this->validate ( $this->val_items );

      $list_type = "member";

      $master_code = substr( $template, 0,1);
      include "template/B/BC01.htm";
   }

   function BC02()
   {
      global $template,$link_url;
      $master_code = substr( $template, 0,1);
      $data = $this->get_detail ( $_GET["code"] );

      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function get_detail ( $code )
   {
      global $connect;

      $query = "select * from userinfo where code='$code'";
      //echo $query;
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      return $data;
   }

   function init_items()
   {
      $arr_items = array ( "id", "level","address1", "name", "tel", "memo", "white_ip" );
      return $arr_items;
   }

   function member_modify()
   {
      global $connect,$link_url;

      $query = "update userinfo set ";
      
      $items = $this->init_items();
      foreach ( $items as $item )
      {
        // 등급 변경 안함
        if( $item == "level" ) continue;

         $query .= " $item = '" . addslashes( $_POST[$item] ) . "',";
      }
      
        // 사용자 이름
        if( isset($_POST['user_name'] ) )
             $query .= " name = '" . addslashes( $_POST['user_name'] ) . "',";

        // auth
        $_a = "";
        global $auth;
        foreach ( $auth as $a )
        {
            $_a .= $_a ? "," : "";
            $_a .= $a;
        }
      
        $query .= "auth='$_a'";
        
      $query .= " where code = '" . $_REQUEST["code"] . "'";
	  debug("사용자정보수정:" . $query);
      mysql_query ( $query , $connect );

      $this->redirect ( "?template=BC00");
      exit;
   }

   function member_add()
   {
        global $connect;
        
        $userid = $_REQUEST[id];
        
        // 2010.11.15 syhwang@pimz.co.kr
        if (strstr($userid, "root"))
        {
            echo "<script>alert('해당아이디로 등록이 불가합니다(예약어사용)');</script>";
            echo "<script>history.back();</script>";
            exit;
        }

        // 공급처
        $sql = "select id from userinfo where id ='$userid' and level=0";
        $list = mysql_fetch_array(mysql_query($sql, $connect));
        if ($list)
        {
            echo "<script>alert('공급처와 동일한 ID는 사용할 수 없습니다.');</script>";
            echo "<script>history.back();</script>";
            exit;
        }
        
        $sql = "select id from userinfo where id ='$userid'";
        $list = mysql_fetch_array(mysql_query($sql, $connect));
        if ($list)
        {
            echo "<script>alert('이미 등록된 아이디입니다.');</script>";
            echo "<script>history.back();</script>";
            exit;
        }
        
        $sql = "select max(code) max_code from userinfo where level <> 0";
        $list = mysql_fetch_array(mysql_query($sql, $connect));
        $max_code = $list[max_code];
        if ($max_code < 1000) $max_code = 1000;
            $max_code = $max_code + 1;
        
        $query = "insert into userinfo set crdate = Now(), code='$max_code'";
        
        // 등급은 무조건 7
        $_POST[level] = 7;

        foreach ( $this->init_items() as $item )
        $query .= ", $item = '" . addslashes( $_POST[$item] ) . "'";
        
		// 비번
        if( isset($_POST['passwd'] ) ) {
             $query .= ", passwd = password('" . $_POST[passwd] . "')";
		}

        // auth
        $_a = "";
        global $auth;
        foreach ( $auth as $a )
        {
            $_a .= $_a ? "," : "";
            $_a .= $a;
        }
        $query .= ",auth='$_a'";
        
        mysql_query ($query , $connect ) or die(mysql_error());
        
        $this->redirect ( "?template=BC00" );
        exit;
   }

   function get_member_list( &$total_rows, $list_type="" )
   {
      global $connect, $keyword;
      
      $query = "select * from userinfo where level >= 1 and level < 9 ";

      if ( $keyword  )
         $option .= " and ( id like '%$keyword%' or name like '%$keyword%' ) ";

      $order = " order by crdate desc";

      // total list
      $result = mysql_query ( $query . $option . $order, $connect );
      return $result;
   }


    //------------------------
    // add by syhwang
    function toggle_main_menu()
    {
        global $connect;

		$id = $_REQUEST[id];
        $sts = $_REQUEST[sts];

        $upd_sql= "update userinfo set show_main_info =  $sts where id = '$id' and level < 8";
        mysql_query($upd_sql, $connect) or die(mysql_error());

		$sql = "select show_main_info from userinfo where id = '$id'";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));

		echo $list[show_main_info];

        exit;
    }


   ///////////////////////////////
   // jk
   // date: 2005.8.23
   function delete()
   {
      global $connect;
      $link_url = $_REQUEST["link_url"];
      $id_list = $_POST["result"];
      $id_list = substr ( $id_list, 0, strlen( $id_list) - 1 );

      $sql = "delete from userinfo where code in ($id_list)";
debug("사용자 삭제 : " . $sql);
      mysql_query($sql, $connect) or die(mysql_error());

      $this->redirect( $link_url );
      exit;
   }
}

?>
