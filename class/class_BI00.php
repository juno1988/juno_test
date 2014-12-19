<?
require_once "class_top.php";
require_once "class_B.php";

////////////////////////////////
// class name: class_BI00
//

class class_BI00 extends class_top {

    ///////////////////////////////////////////
    // 내정보 수정

    function BI00()
    {
        global $connect, $template;
    
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function change_passwd()
    {
        global $connect, $pass1, $pass2, $pass3;
        
        $val = array();
        
        // 현재 비밀번호 가져오기
        $query = "select * from userinfo where id='$_SESSION[LOGIN_ID]'";
        $result = mysql_query($query, $connect);
        $data = mysql_fetch_assoc($result);
        

        $cur_passwd = get_dec_passwd($data[passwd]);
        
        // 현재 비밀번호 틀림
        if( $cur_passwd != $pass1 )
        {
            $val['error'] = 1;
            echo json_encode( $val );
            return;
        }

		//-- passwd encrypt
		$enc_passwd = get_enc_passwd($pass2);

        // 비밀번호 변경
        $query = "update userinfo set passwd='$enc_passwd' where id='$_SESSION[LOGIN_ID]'";
        if( mysql_query($query, $connect) )
            $val['error'] = 0;
        else
            $val['error'] = 2;
        
        echo json_encode( $val );
    }

}

?>
