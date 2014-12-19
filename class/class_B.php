<?
////////////////////////////////
// class name: class_B
//

class class_B {

    ///////////////////////////////////
    // shop_list
    function get_shop_list( )
    {
        global $connect;
        $line_per_page = _line_per_page;
        $string = $_REQUEST["string"];
        $curr_page = $_REQUEST["page"];

        $starter = $curr_page ? ($curr_page - 1) * $line_per_page : 0;

        $sql = "select * from shopinfo ";

        if ( $string )
           $sql .= " where shop_name like '%$string%' "; 

        if ( $line_per_page )
           $sql .= " order by shop_id limit $starter, $line_per_page";
        $result = mysql_query ( $sql, $connect );
   
        return $result; 
    }

    function get_user_name( $id )
    {
        global $connect;
        $query = "select name from userinfo where code='$id'";
        $result = mysql_query ( $query, $connect );
        $data   = mysql_fetch_array ( $result );

        return $data[name];
    }

    ///////////////////////////////////
    // supply list 공급처 목록
    function get_supply_list( &$total_rows )
    {
        global $connect;
        global $line_per_page, $search_type, $sort_type,$s_group_id;

        $curr_page = $_REQUEST[page];
        $keyword = $_REQUEST["keyword"];

        $starter = $curr_page ? ($curr_page - 1) * $line_per_page : 0;

        // build query
        $sql = "select * from userinfo ";
        $sql_cnt = "select count(*) cnt from userinfo";

        $options = " where level = 0  ";
        
        if( $keyword )
        {
            if( $search_type == 1 )
                $options .= " and name like '%$keyword%' ";
            else if( $search_type == 2 )
                $options .= " and code = '$keyword' ";
            else if( $search_type == 3 )
                $options .= " and id like '%$keyword%' ";
            else if( $search_type == 4 )
                $options .= " and (address1 like '%$keyword%' or address2 like '%$keyword%') ";
        }

		if ( $s_group_id )
			$options .= " and group_id=$s_group_id ";

        if( $sort_type == "code" )
            $options .= " order by code desc ";
        else
            $options .= " order by name asc ";


        // for count
        $result = mysql_query ( $sql_cnt . $options . $limit, $connect );
        $data = mysql_fetch_array ( $result );
        $total_rows = $data[cnt];

        // for list
        if ( $line_per_page )
           $limit.= " limit $starter, $line_per_page";
debug("공급처 검색 : " . $sql . $options );
        $result = mysql_query ( $sql . $options . $limit, $connect );
   
        return $result; 
    }
}

?>
