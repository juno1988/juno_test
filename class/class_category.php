<?
/***********************************
* date: 2009.3.11 - jk
*
***********************************/
class class_category{

    /***************************************
    * date: 2009-3-11 jkryu
    * category combo string을 return한다
    /***************************************/
    function get_category_combo( $category )
    {
	global $connect;

	// 사용자가 만든 전체 카테고리 
	$query  = "select * from user_category";
	$result = mysql_query( $query, $connect );

	$str = "<select name=category><option value=''>카테고리 선택</option>\n";
	$i = 0;
	while ( $data = mysql_fetch_array( $result ) )
	{
	    $str .= "<option value='$data[code]' ";
	    // 카테고리 코드와 값이 동일할 경우 선택~~~
	    if ( $data[code] == $category ) $str .= " selected";
	    $str .= ">$data[name]</option>\n";
	    $i++;
	}
	$str .= "</select>";

	return $i ? $str : "";
    }
    
    function get_category_name( $id )
    {
        global $connect;
        
        if( $id )
        {
            $query = "select name from category where seq='$id'";
            $result = mysql_query($query, $connect);
            $data = mysql_fetch_assoc($result);
            
            return $data[name];
        }
        else
            return "";
    }
}

?>
