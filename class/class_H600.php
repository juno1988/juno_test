<?
require_once "class_top.php";
require_once "class_D.php";
require_once "class_C.php";
require_once "class_E.php";
require_once "class_board.php";

////////////////////////////////
// class name: class_D600
//

class class_H600 extends class_top 
{
  function H600()
  {
	global $template, $connect;

	$query = "select * from orders limit 10";
	$result = mysql_query ( $query, $connect );
	$data = mysql_fetch_array ( $result );
	$master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
  }


  //=======================
  // insert content
  // �Է�
  function insert_content () 
  {
	class_board::insert_content();
  }

  // ����
  function update_content ()
  {
        class_board::update_content();
  }

  // ����
  function delect_content ()
  {
        class_board::delect_content();
  }

  // ��� �Է� 
  function insert_reply ()
  {
        class_board::insert_reply();
  }
 
  // menu������ ��������
  function get_menu()
  {
	$infos = class_board::get_menu();
	echo $infos;
  }

  // list ����� ��������
  function get_list()
  {
	$infos = class_board::get_list();
	echo $infos;
  }


  function get_list_message()
  {
        $infos = class_board::get_list_message();
        echo $infos;
  }



 function get_list_log()
  {
        $infos = class_board::get_list_log();
        echo $infos;
  }	
 function get_content()
  {
        $infos = class_board::get_content();
        echo $infos;
  }
	
 function text_write()
  {
	$infos = class_board::get_content();
        echo $infos;
  }
  
 function text_reply()
  {
        $infos = class_board::get_content();
        echo $infos;
  }

  function write_view()
  {
        $infos = class_board::get_content();
        echo $infos;
  }
 
}
