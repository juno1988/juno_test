<?
//===================================
// class_board
// �Խù��� �а� ���� ����� ����, �Խ��� ����, �Խù� ��������..���..
//


class class_board
{
  function get_menu()
  {
	$query = "select * from intranet_board a, intranet_level b
                           where a.id = b.board_id
                             and b.user_id = '" . $_SESSION[LOGIN_ID] . "'";


	
	$xml = "
<?xml version='1.0' encoding='euc-kr'?>
<menus>
	<menu key='1'>		
		<title>��������</title>	
	</menu>

	<menu key='2'>
                <title>��ü����</title>
                <tot>120</tot>
                <unread>10</unread>
        </menu>
	<menu key='3'>
		<title>�����۾�</title>
		<tot>120</tot>
		<unread>10</unread>
	</menu>
	<menu key='4'>
		<title>��������</title>
		<tot>120</tot>
		<unread>10</unread>

	</menu>
	<menu key='5'>
                <title>�Խ��� ����</title>
        </menu>
	<menu key='6'>
                <title>�Խ��� ����</title>
        </menu>
</menus>  

";

	return $xml;

	// xml�� retun�Ǿ� ��	
  }

  function get_list()
  {
	 $query = "select * from intranet_board a, intranet_level b
                           where a.id = b.board_id
                             and b.user_id = '" . $_SESSION[LOGIN_ID] . "'";
	
	$xml  = "
	
	<?xml version='1.0' encoding='euc-kr'?>


	<lists>
	        <list>
			<title>��ü����</title>
			<textname>����</textname>
	                <write_name>�۾���</write_name> 
	                <day>�۾���</day>
	                <time>�ð�</time>
	                <read>Ȯ�ο���</read>
	                <unread>������ ���</unread>
	                <page>������</page>
	        </list>
     </lists>
";
        return $xml;
  }


  function get_list_message()
  {
         $query = "select * from intranet_board a, intranet_level b
                           where a.id = b.board_id
                             and b.user_id = '" . $_SESSION[LOGIN_ID] . "'";

        $xml  = "
        <?xml version='1.0' encoding='euc-kr'?>
        <lists>
                <list>
                        <title>�����۾�</title>
                        <textname>����</textname>
                        <write_name>�۾���</write_name> 
                        <day>�۾���</day>
                        <time>�ð�</time>
                        <read>Ȯ�ο���</read>
                        <unread>������ ���</unread>
                        <page>������</page>
                </list>
     </lists>
";
        return $xml;
  }

  function get_list_log()
  {
  	global $board_id;
         $query = "select * from intranet_board a, intranet_level b
                           where a.id = b.board_id
                             and b.user_id = '" . $_SESSION[LOGIN_ID] . "'";

        $xml  = "
        <?xml version='1.0' encoding='euc-kr'?>
	<lists>
		<list>
			<title>��������</title>
			<textname>����</textname>
			<write_name>�۾���</write_name> 
			<day>�۾���</day>
			<time>�ð�</time>
			<page>������</page>
		</list>
	</lists>
";
        return $xml;
  }
	

/*	while ( $data = mysql_fetch_array (  $result ) )
        {
		$xml .= "
		<list>
			<title>$data[title]</title>
			<regdate>$data[reg_date]</regdate>
		</list>
		";
	
        }

	$xml .= " /
</lists>"            ;

	return $xml;
  }
*/






function get_content()
  {
         $query = "select * from intranet_board a, intranet_level b
                           where a.id = b.board_id
                             and b.user_id = '" . $_SESSION[LOGIN_ID] . "'";

        $xml  = "
        <?xml version='1.0' encoding='euc-kr'?>
	<content>
     		<common>
     			<board_name>�Խ����̸�<board_name>
     			<board_admin>�Խ��ǰ���������</board_admin>
     			<title>��������</title>
     			<read_name>���� ����� ������ ǥ��</read_name>
     			<unread_name>���� ���� ����� ������ ǥ�� </unread_name>
     			<write_name>�ۼ���</write_name>
     			<day>�ۼ���</day>
     			<time>�ۼ��ð�</time>
     			<text>���� ����</text>
     			<common_level>��������</common_level>
     			<common_day>������</common_day>
     			<common_time>�����ð�</common_time>
     			<response>�亯������</response>
     									
     									
     			<reply>�������
	     			<reply_name>��� �����</reply_name>
     				<reply_text>��� ����</reply_text>
     				<replay_day>��� �ۼ���</replay_day>
     			  	<replay_time>��� �ۼ��ð�</replay_time>
     			</reply>
     									
     			<common_complete>�۾�ó������
     				<stand>���</stand>
     				<underway>������</underway>
     				<complete>�Ϸ�</complete>
     				<ripup>����</ripup>
     			</common_complete>
     														
     				  
     			  
     			<businesslog>��������
     				<write_name>�ۼ���</write_name>
            			<day>�ۼ���</day>
        			<time>�ۼ��ð�</time>
                		<text>����������</text>
     			</businesslog>
     		</common>
     </content>
";
        return $xml;
  }


  function insert_content()
  {

	global $user_id,$content,$board_id,$title,$content,$owner, $connect;

	echo "before: $content \n";

	//==============================================
	// decode part
	$board_id = iconv("utf-8", "euc-kr", $board_id );
	$title = iconv("utf-8", "euc-kr", $title );
	$content = iconv("utf-8", "euc-kr", $content );

	$user_id = $_SESSION[LOGIN_ID];
	$owner = $_SESSION[LOGIN_NAME];

	echo "board_id: $m_boardid /\n ";
	echo "user_id: $user_id /\n ";
	echo "user_name: $owner/\n ";
	echo "title: $title /\n ";
	echo "text: $content /\n ";


	// insert query�� ����
	$query = "insert into intranet_board_text set board_id='$board_id', user_id='$user_id', owner='$owner', title='$title', content='$content', crdate=Now() ";

	echo $query;

	// ���� db�� �����
	mysql_query ( $query, $connect );	

	// �Էµ� �� �о����
	$query = "select max(id) id from intranet_board_text";
	
	
	// �о��	
	// logic ó��
	$max_id = "";
	
	$this->get_content( $max_id);
	$result = mysql_query($query, $connect);
	$data = mysql_fetch_array($result);
	echo $data[id];

	
  }

  function insert_reply()
  {

        global $user_id,$content_id,$board_id,$crdate,$content, $connect;

        echo "before: $content \n";

        //==============================================
        // decode part
        $board_id = iconv("utf-8", "euc-kr", $board_id );
        $content = iconv("utf-8", "euc-kr", $content );

        $user_id = $_SESSION[LOGIN_ID];
        $content_id = $_SESSION[LOGIN_NAME];

        echo "board_id: $m_boardid /\n ";
        echo "user_id: $user_id /\n ";
        echo "content_id: $content_id/\n ";
        echo "text: $content /\n ";


        // insert query�� ����
        $query = "insert into intranet_reply set board_id='$board_id', user_id='$user_id',content_id='$content_id',content='$content', crdate=Now() ";

        echo $query;

        // ���� db�� �����
        mysql_query ( $query, $connect );

  }


        

}
?>
