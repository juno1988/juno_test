<?
//===================================
// class_board
// 게시물을 읽고 쓰고 지우고 변경, 게시판 생성, 게시물 개수세기..등등..
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
		<title>공지사항</title>	
	</menu>

	<menu key='2'>
                <title>전체공지</title>
                <tot>120</tot>
                <unread>10</unread>
        </menu>
	<menu key='3'>
		<title>받은작업</title>
		<tot>120</tot>
		<unread>10</unread>
	</menu>
	<menu key='4'>
		<title>업무일지</title>
		<tot>120</tot>
		<unread>10</unread>

	</menu>
	<menu key='5'>
                <title>게시판 생성</title>
        </menu>
	<menu key='6'>
                <title>게시판 삭제</title>
        </menu>
</menus>  

";

	return $xml;

	// xml이 retun되야 함	
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
			<title>전체공지</title>
			<textname>제목</textname>
	                <write_name>글쓴이</write_name> 
	                <day>글쓴날</day>
	                <time>시간</time>
	                <read>확인여부</read>
	                <unread>안읽은 사람</unread>
	                <page>페이지</page>
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
                        <title>받은작업</title>
                        <textname>제목</textname>
                        <write_name>글쓴이</write_name> 
                        <day>글쓴날</day>
                        <time>시간</time>
                        <read>확인여부</read>
                        <unread>안읽은 사람</unread>
                        <page>페이지</page>
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
			<title>업무일지</title>
			<textname>제목</textname>
			<write_name>글쓴이</write_name> 
			<day>글쓴날</day>
			<time>시간</time>
			<page>페이지</page>
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
     			<board_name>게시판이름<board_name>
     			<board_admin>게시판관리자정보</board_admin>
     			<title>글의제목</title>
     			<read_name>읽은 사람의 정보를 표시</read_name>
     			<unread_name>읽지 않은 사람의 정보를 표시 </unread_name>
     			<write_name>작성자</write_name>
     			<day>작성일</day>
     			<time>작성시간</time>
     			<text>글의 내용</text>
     			<common_level>열람레벨</common_level>
     			<common_day>열람일</common_day>
     			<common_time>열람시간</common_time>
     			<response>답변보내기</response>
     									
     									
     			<reply>댓글정보
	     			<reply_name>댓글 사용자</reply_name>
     				<reply_text>댓글 내용</reply_text>
     				<replay_day>댓글 작성일</replay_day>
     			  	<replay_time>댓글 작성시간</replay_time>
     			</reply>
     									
     			<common_complete>작업처리상태
     				<stand>대기</stand>
     				<underway>진행중</underway>
     				<complete>완료</complete>
     				<ripup>무시</ripup>
     			</common_complete>
     														
     				  
     			  
     			<businesslog>업무보고
     				<write_name>작성자</write_name>
            			<day>작성일</day>
        			<time>작성시간</time>
                		<text>업무보고내용</text>
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


	// insert query문 생성
	$query = "insert into intranet_board_text set board_id='$board_id', user_id='$user_id', owner='$owner', title='$title', content='$content', crdate=Now() ";

	echo $query;

	// 실제 db에 저장됨
	mysql_query ( $query, $connect );	

	// 입력된 값 읽어오기
	$query = "select max(id) id from intranet_board_text";
	
	
	// 읽어와	
	// logic 처리
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


        // insert query문 생성
        $query = "insert into intranet_reply set board_id='$board_id', user_id='$user_id',content_id='$content_id',content='$content', crdate=Now() ";

        echo $query;

        // 실제 db에 저장됨
        mysql_query ( $query, $connect );

  }


        

}
?>
