<?
/***********************************
* date: 2011.1.10 - jk
* 에러처리..
***********************************/
class class_error{

    /***************************************
    * date: 2011-1-10 jkryu
    *
    /***************************************/
    function class_error()
    {
    	global $msg,$top;    	
    	
    	echo "
            <div height=700 align=center style='padding-top:100px;'>
                <img src='images/authorize_error4.gif'>
                <div> $msg </div>
                <div><a href='template15.htm?template=$top'>돌아가기</a></div>
            </div>";
            exit;
    }
}

?>
