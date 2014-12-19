<?
/************************************************************************************
title: 자동 login
date: 2011.1.28 - jkryu

로그인이 실행되면
is_login (login_id, shop_id, login_time)
    10분 이상 지나면 재 로그인..[로그인]/[CS] 이렇게 뜨는게 가능함..
    로그인 새로 하면 전부 초기화...ok..

************************************************************************************/
require_once "class_shop.php";

class class_autologin
{
    //
    // Click하면 로그인이 가능한 Popup의 링크 제공
    function login( $shop_id )
    {
        global $connect;        
        $shop_code = $shop_id % 100;
        
        // 1  옥션     (1)    - 2011.1.28
        // 50 11번가   (50) - 2011.1.28
        // 6  인터파크 6
        // 14 롯데 아이몰
        // 43 hmall
        // 2  gmarket
        $arr_code = array(1,6,50,14,43,2);
        
        $enable = 0;
        foreach( $arr_code as $_code )
        {
            if ( $_code == $shop_code )
            {
                echo "<a href='popup_utf8.htm?template=autologin&action=do_login&shop_id=$shop_id' target='_new' class='btn_premium2'>로그인</a>"; 
                $enable = 1;
            }
        }
        
        if ( $enable == 0 )
        {
            echo "준비중";   
        }
    }
    
    //
    // 로그인 실행
    // 2011.1.28 - jk.ryu
    function do_login()
    {
        global $connect,$shop_id;        
        $shop_code = $shop_id % 100;
        
        // 로그인 정보 저장
        $this->reg_login( $shop_id );
        
        // userid / passwd / auth_code를 사용함.
        $arr_info  = class_shop::get_info( $shop_id );
        
        echo '
        <script language="javascript">
        $(document).ready(function(){
            //$("#myform").submit();
        });
        
        function _go()
        {
            $("#myform").submit();
        }
        </script>';
        
        $method = "login_" . $shop_code;        
        $this->${method}($arr_info);
        
        echo "<a href='javascript:_go()'>go</a>";
    }
    
    //
    // 로그인 등록
    // 2011.1.28 - jk.ryu
    // is_login table에 로그인 여부를 등록함.
    //
    function reg_login()
    {
        global $connect,$shop_id;
        
        // 삭제 
        
        // 입력
        
        
    }
    
    //
    // 판매처 cs연동..
    // 로그인이 안된 경우 로그인을 실행한다.
    //
    function cs()
    {
        
    }
    
    //
    // 준비중
    function _ready()
    {
        echo "준비중";   
    } 

    // 옥션 로그인
    // 
    function login_1( $arr_info )
    {
        echo "
<form id='myform' action='https://signin.auction.co.kr/Authenticate/login.aspx' method='post'>
    <input type=hidden name=id value='$arr_info[userid]'>
    <input type=hidden name=password value='$arr_info[passwd]'>
    <input type=hidden name=url value='http://sell.auction.co.kr/sell/Sellplus/SellplusDefault.aspx'>
</form>";
    }
    
    // 11번가 로그인
    function login_50( $arr_info )
    {
        echo "
<form id='myform' action='https://soffice.11st.co.kr/login/LoginOk.tmall' method='post'>
<input type=hidden name=authMethod value='login'>
<input type=hidden name=returnURL value='https://soffice.11st.co.kr/Index.tmall'>
<input type=hidden name=loginName value='$arr_info[userid]'>
<input type=hidden name=passWord value='$arr_info[passwd]'>
</form>";    
    }
    
    // 인터파크
    function login_6( $arr_info )
    {
        echo "
<form id='myform' action='http://ipss.interpark.com/member/login.do?_method=login' method='post'>
<input type=text name='_method'      value='login'>
<input type=text name='sc.memId'     value='$arr_info[userid]'>
<input type=text name='sc.pwd'       value='$arr_info[passwd]'>
<input type=text name='sc.enterEntr' value='Y'>
<input type=text name='isAjax'       value='N'>
</form>";    
    }
    
    // 롯데 아이몰
    function login_14( $arr_info )
    {
        echo "
<form id='myform' method='get' action='http://escm.lotteimall.com'></form>";           
    }
    
    // hmall 43
    function login_43( $arr_info )
    {
        echo "
<form id='myform' method='get' action='http://help.hmall.com/HELP.html'></form>";           
    }
    
    // gmarket
    function login_2( $arr_info )
    {
        echo "
<form id='myform' action='https://gsm.gmarket.co.kr/GMKT.GSM.Web/login_normal.aspx' method='get'>
<!--input type=text name='__EVENTTARGET'  value='btnAuth'-->
<!--input type=text name='txtLoginID'     value='$arr_info[userid]'-->
<!--input type=text name='txtLoginPwd'    value='$arr_info[passwd]'-->
<!--input type=text name='seq'            value='GcF+cipUR95ewv9tGfewSasBo7oHfVhZsE/QCMZ1v5o='-->
<!--input type=text name='__VIEWSTATE'    value='1GWit5/uc4fPH+QUw+FqgvRFgpW2PeWvpAiZ0Zlkn9Rf+0vFlnIvXVLb/b/ufEjiUAOIZ7GRjpWu8WuYBcsLiM5lqgjip8O8ibyyYk4CewTHTgUNBOKhgPwWem+ooeWxwFdojfCepjYcl4+fQ2CZ0vLw9wxDTc4aEQzBC1Eptz+GpGNrJ76mnuqAVadyOFF/x9N34eN4jKkjskKkkMCjAq6FmA0DaHZvTmH13DXdZVMUD3+88gdmdQZ4VUKI5B46YU1ng9iKz4iHWzkPrRH+U6qJe00umzh1eLiu2AvpdLVl2MbqfPIJPKne5PsSLCB2CppEaUC0OQ3FJJaUfdRMHHRf+0UlJQNYRSUqgP4FEpR5pIoIfSomcwB27Q+tbhrIGa+0mqLWu7GXu1gcVpMSmJnMXxzt9pciUyGa4HCo5r3I1PZ4Eloj3x76oB2m4P5mnNqb9F4t0H0bnMsgXuTf7+iJ4G/m+irYt40YFMsvXPhJLFGb7Ioecgqa4YLnPkEfLt1Yv7Wk/DF69O7zsWC6qa4SJopkLRf+GAV2jWVoN9qoXYTUm2XMRbLhoPykFmcJtRJmUvSk7x+/tWo31CAAAw=='-->
<!--input type=text name='Admin_YN'       value='N'-->
</form>";    
    }  
}
