<?
//
// class_proc_info
//
// 특정 작업의 중복 실행을 막기위함. 자세한 내용은 구글닥스 참조
// 
// date: 2009.2.13
// jkh

class class_proc_info
{
    function run( $action )
    {
        $this->${action}();
    }

    // 해당 작업이 실행 중인지 확인한다.
    function is_running($proc_id)
    {
        global $connect;

        $val = array();
                
        $query = "select * from proc_info where proc_id=$proc_id and status=1";
        // proc_id 값에 따라 대기시간을 따로 설정한다.
        switch($proc_id)
        {
            // 발주 프로세스 : 500ms 10번 총 5초.. 
            case 1:
                for($i=0; $i<10; $i++)
                {
                    $result = mysql_query( $query, $connect );
                    if( mysql_num_rows($result) > 0 ){
                        $val['run'] = 1;
                        usleep(500000);
                    }else{
                        $val['run'] = 0;
                        break;
                    }
                }
                break;
            // 기본은 대기시간 없음.
            default:
                $result = mysql_query( $query, $connect );
                if( mysql_num_rows($result) == 0 )
                    $val['run'] = 0;
                else
                    $val['run'] = 1;
        }

        if( $val['run'] != 0 )
        {
            $data = mysql_fetch_array( $result );
            $val['run'] = 1;
            $val['worker'] = $data[worker];
            $val['crdate'] = $data[crdate];
        }
        
        return $val;
    }
        
    // 작업이 시작됨을 저장한다. 
    function run_proc($proc_id)
    {
        global $connect;

        // 해당 프로스세 데이터가 있는지 확인한다. - 처음 실행의 경우를 위해
        $query = "select * from proc_info where proc_id=$proc_id";
        $result = mysql_query( $query, $connect );
        // 있으면 update
        if( mysql_num_rows( $result ) > 0 )
        {
            $query = "update proc_info 
                         set status  = 1,
                             worker  = '$_SESSION[LOGIN_NAME]',
                             crdate  = now()
                       where proc_id = $proc_id and
                             status  = 0";
            return mysql_query( $query, $connect );
        }
        // 없으면 insert
        else
        {
            $query = "insert proc_info 
                         set proc_id = $proc_id,
                             status  = 1,
                             worker  = '$_SESSION[LOGIN_NAME]',
                             crdate  = now()";
            return mysql_query( $query, $connect );
        }            
    }

    // 작업이 종료됐음을 저장한다.
    function end_proc($proc_id)
    {
        global $connect;

        $query = "update proc_info set status = 0 where proc_id = $proc_id";
        mysql_query( $query, $connect );
    }


    // 
    // 해당 작업이 실행 중인지 확인하는 is_running과, 작업이 시작됨을 저장하는 run_proc을 하나로 합친 함수
    // 
    // function_common에서 ajax request로 호출한다.
    // 
    function check_run()
    {
        // ajax request로 날라온 값
        global $proc_id;
        
        $val = array();
        
        $val = $this->is_running($proc_id);
        if( !$val[run] )
            $val['run_ok'] = $this->run_proc($proc_id);
        else
            $val['run_ok'] = false;
            
        echo json_encode( $val );
    }

    // 
    // 작업이 종료됐음을 저장한다.
    // 
    // function_common에서 ajax request로 호출한다.
    // 
    function end_run()
    {
        // ajax request로 날라온 값
        global $proc_id;

        
        if( $this->end_proc($proc_id) === false )  $val['error'] = 1;
        else  $val['error'] = 0;
        
        echo json_encode( $val );
    }
        
}
?>
