<?
require_once "class_top.php";
require_once "class_B.php";
require_once "class_C.php";
require_once "class_ui.php";

////////////////////////////////
// class name: class_B800
//

class class_B800 extends class_top {

    //***************************
    // promotion 들의 list출력
    // 2008.7.4 - jk
    function B800()
    {
    	global $connect;
    	global $template, $line_per_page;
    
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    //*****************************
    // update format
    // 2009.11.26 - jk
    function update_format()
    {
        global $data, $save_target, $shop_id,$connect;
        
        $data     = str_replace("\\","",$data );
        $arr_data = json_decode($data,true);
        
        if ( $save_target == "system" )
        {   
            if ( $_SESSION[LOGIN_LEVEL] == 9 )
            {
                $connect = sys_db_connect();
                $shop_id = $shop_id % 100;
                
                // 삭제
                $query = "delete from sys_shopheader where shop_id=$shop_id";
                mysql_query( $query, $connect );
                
                // 입력
                for( $i=0; $i<count($arr_data); $i++)
                {
                    $data = $arr_data[$i];
                    
                    if ( $data[field] && $data[name] && $data[header] )
                    {
                        $query = "insert sys_shopheader 
                                      set shop_id     ='$shop_id'
                                         ,field_id    ='$data[field]'
                                         ,field_name  = '$data[name]'
                                         ,idx         = '$data[idx]'
                                         ,shop_header = '$data[header]'
                                         ,abs         = 1";
                        mysql_query( $query, $connect );
                    }
                }   
            }
        }
        else
        {
            // 삭제
            $query = "delete from shopheader where shop_id='$shop_id'";
            mysql_query( $query, $connect );
            
            // 입력
            for( $i=count($arr_data); $i>=0; $i--)
            {
                $data = $arr_data[$i];
                
                if ( $data[field] && $data[name] && $data[header] )
                {
                    $query = "insert shopheader 
                                  set shop_id     = '$shop_id'
                                     ,field_id    = '$data[field]'
                                     ,field_name  = '$data[name]'
                                     ,shop_header = '$data[header]'
                                     ,idx         = '$data[idx]'
                                     ,abs         = 1";
                    mysql_query( $query, $connect );
                }
            }
        }
        
        $this->load_format( $save_target );
        // print_r ( json_decode($data,true) );
    }

    //***************************
    // format load
    // 2009.11.24 - jk
    function load_format( $save_target = "")
    {
        global $shop_id, $save_target, $connect, $load_target;
        
        if ( $load_target == "system" || $save_target == "system")
        {
            $connect   = sys_db_connect();
            $shop_code = $shop_id % 100;
            $query     = "select * from sys_shopheader where shop_id='$shop_code' order by idx";            
        }
        else
        {
            $query = "select * from shopheader where shop_id='$shop_id' order by idx";
        }
        $result = mysql_query( $query, $connect );
        $arr_result = array();
        $i     = ord('A');
        $last  = ord('Z');
        
        // AA는 어떻게 처리?
        while ( $data = mysql_fetch_assoc( $result ) )
        {
            if ( $i > $last )
            {
                $_idx = $i - 26;
                $index = "A" . chr($_idx);
            }
            else
                $index = chr($i);
                
            $arr_result[] = array( 'index'  => $index
                                   ,'name'  => $data['field_name']
                                   ,'field' => $data['field_id']
                                   ,'header'=> $data['shop_header'] );
            $i++;                                   
            
        }        
        echo json_encode( $arr_result );
    }

}

?>
