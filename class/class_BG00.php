<?
require_once "class_top.php";
require_once "class_B.php";
require_once "Request.php";
require_once "./lib/lib_xml.php";

////////////////////////////////
// class name: class_BG00
//

class class_BG00 extends class_top {

    ///////////////////////////////////////////
    // 내정보 수정

    function BG00()
    {
        global $connect;
        global $template;

        // $sys_connect = sys_db_connect();
		// 5번 서버의 sys_bill table을 조회하도록 수정
		// 2011.1.10 syhwang

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function BG02()
    {
        global $connect;
        global $template;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";


        // 정보 가져온다
        $query = "select * from userinfo where id='root'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_assoc( $result );

        debug( "[get info1] $data[auth_code] / $query" );

        // auth_code가 있는지 여부 check
        if ( $data[auth_code] )
        {
            // 있으면 로긴 시작;
            if ( _DOMAIN_ == "ezadmin" )
                $url = "http://rsr.dtax.co.kr/login/?rsrid=easyadmin&uid=pimz&authinfo=$data[auth_code]";
            else
                $url = "http://rsr.dtax.co.kr/login/?rsrid=easyadmin&uid=" . _DOMAIN_ . "&authinfo=$data[auth_code]";

            debug ( "[go url] $url" );

            echo "<script language='javascript'>set_url('" . $url . "')</script>";         
        }
        else
        {

        	//-------------------------------
        	// sys_domain
        	$sys_connect = sys_db_connect();
        	$sql = "select * from sys_domain where id = '" . _DOMAIN_ . "'";
        	$sys_list = mysql_fetch_assoc(mysql_query($sql, $sys_connect));

            // 없으면 회원 가입 
            // 등록가능한지 체크..
            $corpno = str_replace("-","", $sys_list[corp_no]);

            debug( "[사업자번호] $corpno" );

            if ( !$sys_list[email] )
            {
                echo "<script language='javascript'>set_url('./popup.htm?template=BG04&msg=이메일 정보가 없습니다')</script>";         
                exit;
            }

            // 
            if ( $corpno )
            { 
                // 등록가능한지 여부 check한다
                $url = "http://rsr.dtax.co.kr/api/?rsrid=easyadmin&apikey=64f5c9a42da44000022d9bae525bcc13&comm=chk&uid=" . _DOMAIN_ . "&regnumber=" . $corpno;
                $req = &new HTTP_Request();
                $req->setMethod(HTTP_REQUEST_METHOD_GET);
                $req->setURL($url);
                $req->sendRequest();
                $response = $req->getResponseBody();
      
                debug( $response ); 
                // return check 
                $arrXml = xml2array( $response );
        
                // 등록 가능
                if ( $arrXml["response"]["rescode"] == 200 )
                {
                    // 등록이 가능한 경우 => 회원 등록한다.
                           $url = "http://rsr.dtax.co.kr/api/";

                     $req = &new HTTP_Request();
                    $req->setMethod(HTTP_REQUEST_METHOD_POST);
                    // $req->setMethod(HTTP_REQUEST_METHOD_GET);
                    // debug("[reg] " . iconv('utf-8',"cp949", $url ));
                
                    $req->addPostData("rsrid","easyadmin");
                    $req->addPostData("apikey","64f5c9a42da44000022d9bae525bcc13");
                    $req->addPostData("encoding","utf-8");
                    $req->addPostData("comm","reg");
                    $req->addPostData("regnumber",$corpno);                    
                    $req->addPostData("uid",_DOMAIN_);
                    $req->addPostData("comname", _DOMAIN_ );
                    $req->addPostData("ceoname", $sys_list[corp_boss] );
                    $req->addPostData("postzip", $sys_list[corp_zip1] . "-" .$sys_list[corp_zip2]);
                    $req->addPostData("address", $sys_list[corp_address]);
                    $req->addPostData("type", $sys_list[corp_job1]);
                    $req->addPostData("classification", $sys_list[corp_job2]);
                    $req->addPostData("personname", $sys_list[corp_boss]);
                    $req->addPostData("email", $sys_list[email] );
                    $req->addPostData("telephone",str_replace("-","",$sys_list[corp_tel])); 
                    $req->addPostData("mobilephone",str_replace("-","",$sys_list[corp_mobile]));


                    $req->setURL($url);
                    $req->sendRequest();
                    $response = $req->getResponseBody();

                    debug( $response );      
 
                    // return check 
                    $arrXml= xml2array( $response );

                    // 등록 성공
                    if ( $arrXml["response"]["rescode"] == 200 )
                    {
                        // return 받은 인증키를저장한다 
                        $query = "update userinfo set auth_code='" . $arrXml[response][resdata][authinfo] . "' where id='root'";
                        mysql_query( $query, $connect );
                        debug( "[save authinfo] $query" );

                        // 인증키를 이용해서 로그인..
                        $url = "http://rsr.dtax.co.kr/login/?rsrid=easyadmin&uid=" . _DOMAIN_ . "&authinfo=" . $arrXml["response"]["resdata"]["authinfo"];
                        debug ( "let's login 2: $url ");
                        echo "<script language='javascript'>set_url('" . $url . "')</script>";         
                    }
                    else
                    {
                        switch( $arrXml["response"]["rescode"] )
                          {
                            case 210:
                                $msg = "사업자번호 중복";
                                break;
                            case 211:
                                $msg = "아이디중복";
                                break;
                            case 504:
                                $msg = "필수항목 누락"; 
                                break;
                            case 530:
                                $msg = "리셀러 인증실패";
                                break;
                            case 999:
                                $msg = "시스템 에러";
                                break;
                        }
                               echo "<script language='javascript'>set_url('./popup.htm?template=BG04&msg=$msg')</script>";         
                    }
        
                }
                // 등록 불가 -> 관리자에게 문의 요망
                else if ( $arrXml["response"]["rescode"] == 210 )
                {
                           echo "<script language='javascript'>set_url('./popup.htm?template=BG04')</script>";         
                }
                exit;
            }
            else
            {
                // 사업자 번호 없는 경우..
                echo "<script language='javascript'>set_url('./popup.htm?template=BG04&msg=사업자등록번호가 없습니다')</script>";         
                exit;
            }
        }
    }
    
    function BG04()
    {
        global $connect;
        global $template;
        
        $master_code = substr( $template, 0,1); 
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function BG01()
    {
        global $connect;
        global $template;

        $sys_connect = sys_db_connect();

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }

    function modify()
    {
        global $connect;

        $mycode = $_REQUEST[mycode];
        $passwd = $_REQUEST[passwd];

        $corpname = $_REQUEST[corpname];
        $boss = $_REQUEST[boss];
        $corpno1 = $_REQUEST[corpno1];
        $corpno2 = $_REQUEST[corpno2];
        $corpno3 = $_REQUEST[corpno3];
        if ($corpno1) $corpno = $corpno1."-".$corpno2."-".$corpno3;
        else $corpno = "";

        $tel = $_REQUEST[tel];

        $mobile1 = $_REQUEST[mobile1];
        $mobile2 = $_REQUEST[mobile2];
        $mobile3 = $_REQUEST[mobile3];
        if ($mobile1 && $mobile2 && $mobile3)
          $mobile = $mobile1."-".$mobile2."-".$mobile3;

        $email = $_REQUEST[email];
        $smsok = $_REQUEST[smsok];

        $zip1 = $_REQUEST[zip1];
        $zip2 = $_REQUEST[zip2];
        $address1 = $_REQUEST[address1];
        $address2 = $_REQUEST[address2];
        $admin = $_REQUEST[admin];

        $sql = "update userinfo set
                        name = '$corpname',
                        passwd = '$passwd',
                        boss = '$boss',
                        corpno = '$corpno',
                        tel = '$tel',
                        mobile = '$mobile',
                        email = '$email',
                        zip1 = '$zip1',
                        zip2 = '$zip2',
                        address1 = '$address1',
                        address2 = '$address2',
                        smsok = '$smsok',
                        admin = '$admin'
                where code = '$mycode'";
        mysql_query($sql, $connect) or die(mysql_error());

        echo "<script>document.location.href = '?template=BG00';</script>";
        exit;
        
    }

}

?>
