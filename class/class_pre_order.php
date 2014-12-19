<?
require_once "class_top.php";

class class_pre_order
{

    ///////////////////////////////////
    // 타이틀 등록
    // (2007.12.1)
    function reg_title( $title )
    {
        global $connect, $top_url;
        $query = "insert into pre_order set name='$title', priority=1, status=1";
        mysql_query ( $query, $connect );

        $query = "select seq from pre_order order by seq desc limit 1";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        return $data[seq];
    }

    /////////////////////////////////////
    //전체 pre order조건
    function get_list()
    {
        global $connect;
        $query = "select *,DATE_FORMAT(crdate, '%Y-%m-%d') crdate from pre_order order by priority desc";
        $result = mysql_query ( $query, $connect );
        return $result;
    }

    function del_seq( $seq )
    {
        global $connect;
        $query = "delete from pre_order where seq='$seq'";
        mysql_query ( $query, $connect ) or die( mysql_error() );
        $query = "delete from pre_order_option where seq='$seq'";
        mysql_query ( $query, $connect ) or die( mysql_error() );
    }

    ////////////////////////////////////////////////////////
    // 옵션 등록
    function reg_options ( $arr_options, $seq )
    {
        foreach ( $arr_options as $key=>$val )
        {
            $this->reg_option( $key, $val, $seq );
        }
    }

    ////////////////////////////////////////
    // 옵션 등록
    function reg_option( $key, $val, $seq )
    {
        global $connect;
        $query = "insert into pre_order_option set field='$key', value='$val',seq='$seq'";
        mysql_query ( $query, $connect );
    }

    /////////////////////////////////////////////////
    // pre_order 의 정보 가져옴
    function get_infos( $seq )
    {
        global $connect;
        $query = "select * from pre_order where seq=$seq";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        return $data;
    }

    ///////////////////////////////////////
    // 상세 쿼리 조건을 가져온다
    function get_options( $seq )
    {
        global $connect;
        $query = "select * from pre_order_option where seq='$seq'";
        $result = mysql_query( $query, $connect );

        $arr_options = array ();
        while ( $data = mysql_fetch_array( $result ) )
        {
            if ( $data[value] )
                    $arr_options[ $data[field] ] = $data[value];
        }
        return $arr_options;
    }

    /////////////////////////////////////////////
    // 조건을적용 
    function  reflect_priority( $arr_options, $priority, $warehouse )
    {
        global $connect;

        // 조건으로 검색
        $obj     = new class_order();
        $obj_3pl = new class_3pl();

        $result = $obj->get_list ( $arr_options );
        $tot = $obj->get_count ( $arr_options );

        $_cnt = 0;
        // 정보 전송
        $obj_top = new class_top();

        while ( $data = mysql_fetch_array ( $result ) )
        {
            // priority check
            // 자기보다 높은 priority는 건드리지 않는다
            if ( $data[priority] >= $priority )
                continue;

            // 조건에 맞는 상품이 합포 대표 상품이 아닌 경우 합포 대표 상품으로 변경
            // 출력 안됨...
            if ( $data[pack] )
                if ( $data[seq] != $data[pack] )
                {
                    echo "<br> seq: $data[seq] /pack: $data[pack] 다른<br>";
                    $obj->re_pack( $data[pack], $data[seq] );
                }

            // 주문의  priority set
            $update_cnt = $obj->set_priority( $data[seq], $priority, $warehouse );
            // echo "seq: $data[seq] / priority: $priority / $warehouse / update: $update_cnt <br>";

            $_cnt++;

            $_str = " ( $_cnt / $tot ) 수행중 ";
            $obj_top->show_txt( $_str );

            // 주문 data sync
            // 원 주문을 정상 처리
            // 이미 전송된 주문건에 대해 정보 전송
debug( "reflect priority: $data[seq] / $priority" );

            $infos[priority]  = $priority;
            $infos[warehouse] = $warehouse;
            $obj_3pl->sync_infos( $infos, $data[seq], 1 );
        }
    }

    /////////////////////////////////
    // 출력전 priority를 초기화
    function init_priority()
    {
        $obj     = new class_order();
        $obj_3pl = new class_3pl();

        $obj->init_priority();        
        $obj_3pl->init_priority();        
    }

    function update_priority( $priority, $seq, $warehouse='' )
    {
        global $connect;
        $query = "update pre_order set priority='$priority', warehouse='$warehouse' where seq='$seq'";
        $result = mysql_query ( $query, $connect );
        return mysql_affected_rows();
    }
}
?>
