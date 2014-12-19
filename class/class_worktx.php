<?
require_once "class_top.php";
/****************************
* 자동화를 위한 class
* date: 2009.3.28 - jk
* reg    : 등록
* update : 갱신
* get    : 조회
* commit : 완료
****************************/

class class_worktx extends class_top {

    var $arr_type = array(
	'pack'   => "합포"
       ,'cancel' => "취소적용");

    //************************
    // 등록
    function worktx_list()
    {
	global $connect;

	$query = "select * from work_transaction order by crdate desc limit 10";
	$result = mysql_query( $query, $connect );
	$arr = array();
	while ( $data = mysql_fetch_assoc( $result ) )
	{
	    $arr[] = array( 
	        seq    => $data[seq]
               ,crdate => $data[crdate] 
               ,type   => $data[type]
               ,name   => iconv('cp949', 'utf-8', $this->arr_type['pack'])
	    );
	}
	echo json_encode( $arr );
    }

    function clean_tx()
    {
	global $connect;

	$query = "truncate work_transaction";
	mysql_query( $query, $connect );
    }

    function docancel()
    {
	$this->insert_tx('cancel');
    }

    function dopack()
    {
	$this->insert_tx('pack');
    }

    // 등록
    function insert_tx( $type )
    {
	global $connect;

	$query = "insert into work_transaction set crdate=Now(), type='$type' ";
	mysql_query( $query, $connect );
    }
}
	
