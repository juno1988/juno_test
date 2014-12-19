<?
require_once "class_B.php";
require_once "class_top.php";

class class_B300 extends class_top
{
   var $arr_items;
   var $val_items;  // 반듯이 입력해야 하는 item

   function init_val_items()
   {
      $this->$val_items = array ("id"=>"회원 아이디", "passwd"=>"비밀 번호", "level"=>"회원 등급", "name"=>"회원 이름");
   }

   function B300()
   {
      global $template;
      $link_url = "?" . $this->build_link_url();
      $line_per_page = _line_per_page;

      $list_type = "member";
      $result = $this->get_member_list ( &$total_rows, $list_type );

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function B301()
   {
      global $template;

      $this->init_val_items();
      $this->validate ( $this->$val_items );

      $list_type = "member";

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function B302()
   {
      global $template;
      $master_code = substr( $template, 0,1);

      $data = $this->get_detail ( $_GET["userid"] );

      include "template/" . $master_code ."/" . $template . ".htm";
   }

   function get_detail ( $id )
   {
      global $connect;

      $query = "select * from userinfo where id='$id'";
      $result = mysql_query ( $query, $connect );
      $data = mysql_fetch_array ( $result );

      return $data;
   }

   function init_items()
   {
      $arr_items = array ( "id","passwd", "level","address1", "name", "tel", "memo" );
      return $arr_items;
   }

   function member_modify()
   {
      global $connect;

      $query = "update userinfo set ";
      
      $i = 1; 
      $items = $this->init_items();
      foreach ( $items as $item )
      {
         $query .= " $item = '" . addslashes( $_POST[$item] ) . "'";
         if ( $i != count($items) )
             $query .= ",";
         $i++;
      }
      $query .= " where id = '" . $_REQUEST["id"] . "'";
      mysql_query ( $query , $connect );


      $this->redirect ( "?template=B302&userid=" . $_POST["id"] );
      exit;
   }

   function member_add()
   {
      global $connect;

        $sql = "select max(code) max_code from userinfo where level <> 0";
	$list = mysql_fetch_array(mysql_query($sql, $connect));
	$max_code = $list[max_code];
	if ($max_code < 1000) $max_code = 1000;
	$max_code = $max_code + 1;

      $query = "insert into userinfo set crdate = Now(), code='$max_code'";
 
      foreach ( $this->init_items() as $item )
         $query .= ", $item = '" . addslashes( $_POST[$item] ) . "'";

      mysql_query ( $query , $connect );

      $this->redirect ( "?template=B302&userid=" . $_REQUEST["id"] );
      exit;
   }

   function get_member_list( &$total_rows, $list_type="" )
   {
      global $connect, $level;
      
      $query_cnt = "select count(*) as cnt ";
      $query = "select * ";

      $option = " from userinfo ";
      
      $keyword = $_POST["keyword"] ? $_POST["keyword"] : $_GET["keyword"];

      ///////////////////////////////////////////////////////////
      // level의 값이 -1 이면      
      // level값이 없으면 전체 검색
      if ( !isset($level) ) $level = -1;

      if ( $level < 0 ) 
         $option .= " where level >= 0 ";
      else
         $option .= " where level = $level ";

      $option .= $list_type ? " and level <> 0" : ""; 

      if ( $keyword  )
         $option .= " and id like '%$keyword%' 
                         or name like '%$keyword%'";

      $result = mysql_query ( $query_cnt . $option );
      $data = mysql_fetch_array ( $result );
      $total_rows = $data[cnt]; 

      $page = $_GET["page"];
      if ( !$page ) $page = 1;
      $starter = ($page - 1) * 20;
      
      $order = " order by crdate desc";
      $limit = " limit $starter, " . _line_per_page;

//echo $query . $option. $order . $limit;

      // total list
      $result = mysql_query ( $query . $option . $order . $limit, $connect );

      return $result;
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

      mysql_query($sql, $connect) or die(mysql_error());

      $this->redirect( $link_url );
      exit;
   }
}

?>
