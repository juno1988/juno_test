<?
require_once "class_top.php";

class class_pre_order
{

    ///////////////////////////////////
    // Ÿ��Ʋ ���
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
    //��ü pre order����
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
    // �ɼ� ���
    function reg_options ( $arr_options, $seq )
    {
        foreach ( $arr_options as $key=>$val )
        {
            $this->reg_option( $key, $val, $seq );
        }
    }

    ////////////////////////////////////////
    // �ɼ� ���
    function reg_option( $key, $val, $seq )
    {
        global $connect;
        $query = "insert into pre_order_option set field='$key', value='$val',seq='$seq'";
        mysql_query ( $query, $connect );
    }

    /////////////////////////////////////////////////
    // pre_order �� ���� ������
    function get_infos( $seq )
    {
        global $connect;
        $query = "select * from pre_order where seq=$seq";
        $result = mysql_query ( $query, $connect );
        $data = mysql_fetch_array ( $result );
        return $data;
    }

    ///////////////////////////////////////
    // �� ���� ������ �����´�
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
    // ���������� 
    function  reflect_priority( $arr_options, $priority, $warehouse )
    {
        global $connect;

        // �������� �˻�
        $obj     = new class_order();
        $obj_3pl = new class_3pl();

        $result = $obj->get_list ( $arr_options );
        $tot = $obj->get_count ( $arr_options );

        $_cnt = 0;
        // ���� ����
        $obj_top = new class_top();

        while ( $data = mysql_fetch_array ( $result ) )
        {
            // priority check
            // �ڱ⺸�� ���� priority�� �ǵ帮�� �ʴ´�
            if ( $data[priority] >= $priority )
                continue;

            // ���ǿ� �´� ��ǰ�� ���� ��ǥ ��ǰ�� �ƴ� ��� ���� ��ǥ ��ǰ���� ����
            // ��� �ȵ�...
            if ( $data[pack] )
                if ( $data[seq] != $data[pack] )
                {
                    echo "<br> seq: $data[seq] /pack: $data[pack] �ٸ�<br>";
                    $obj->re_pack( $data[pack], $data[seq] );
                }

            // �ֹ���  priority set
            $update_cnt = $obj->set_priority( $data[seq], $priority, $warehouse );
            // echo "seq: $data[seq] / priority: $priority / $warehouse / update: $update_cnt <br>";

            $_cnt++;

            $_str = " ( $_cnt / $tot ) ������ ";
            $obj_top->show_txt( $_str );

            // �ֹ� data sync
            // �� �ֹ��� ���� ó��
            // �̹� ���۵� �ֹ��ǿ� ���� ���� ����
debug( "reflect priority: $data[seq] / $priority" );

            $infos[priority]  = $priority;
            $infos[warehouse] = $warehouse;
            $obj_3pl->sync_infos( $infos, $data[seq], 1 );
        }
    }

    /////////////////////////////////
    // ����� priority�� �ʱ�ȭ
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
