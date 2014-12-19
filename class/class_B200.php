<?
require_once "class_B.php";
require_once "class_top.php";
require_once "class_file.php";
require_once "class_ui.php";
require_once "class_table.php";
////////////////////////////////
// class name: class_B200
//
class class_B200 extends class_top 
{
    
    ///////////////////////////////////////////
    // shop들의 list출력

    function get_groupname( $group_id )
    {
        global $connect;
        $query = "select name from supply_group where group_id='$group_id'";
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_array( $result );
        return $data[name];
    }

    // group 등록
    function reg_group()
    {
        global $connect, $name;
        
        $val = array();
        
        // 이미 등록된 그룹명인지 확인
        $query = "select * from supply_group where name='$name' ";
        $result = mysql_query($query, $connect);
        if( mysql_num_rows($result) )
        {
            $val['error'] = 1;
        }   
        else
        {
            $query = "insert supply_group set name='$name'";
            mysql_query( $query, $connect);
            
            $val['error'] = 0;
        }
        
        echo json_encode($val);
    }

    // 그룹 정보 query
    function group_query()
    {
        global $connect, $name;
        
        //
        // userinfo group의 개수를 가져온다.
        $query = "select group_id, count(*) cnt from userinfo where level=0 group by group_id";
        $result = mysql_query( $query, $connect );
        $arr_cnt = array();
        while ( $data   = mysql_fetch_assoc( $result ) )
        {
            $arr_cnt[$data[group_id]] = $data[cnt];   
        }
        
        //
        // list 
        $query = "select * from supply_group where name like '%$name%' order by name";
        $result = mysql_query ($query, $connect );

        $val = array();
        $val['list'] = array();

        while ( $data = mysql_fetch_array( $result ) )
        {
            $val['list'][] = array( 
                group_id   => $data[group_id], 
                name       => $data[name], 
                crdate     => $data[crdate],
                qty        => $arr_cnt[$data[group_id]] ? $arr_cnt[$data[group_id]] : 0
                );
        }
        echo json_encode( $val );
    }

    // 그룹 삭제
    function del_group()
    {
        global $connect, $group_id;
        
        $query = "select * from supply_group where group_id=$group_id";
        $result = mysql_query( $query, $connect );
        $data = mysql_fetch_assoc($result);

debug("공급처그룹삭제:$data[name]");

        // 그룹삭제
        $query = "delete from supply_group where group_id=$group_id";
        mysql_query( $query, $connect );
        
        // 해당 그룹의 공급처 그룹아이디 리셋
        $query = "update userinfo set group_id=0 where group_id=$group_id";
        mysql_query( $query, $connect );
        
    }
    
    function B205()
    {
        global $template, $refresh;

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
   ///////////////////////////////////////////
   function B200()
   {
        global $connect;
        global $template, $line_per_page, $action, $search_type, $keyword, $page, $sort_type, $s_group_id;

        $curr_page = $_REQUEST[page];
        $keyword = $_REQUEST[keyword];

        if( !$page )
        {
            $_REQUEST[action] = "";
            $_REQUEST[keyword] = "";
            $_REQUEST[page] = "1";
        }
        
        $par_arr = array("template","action","page","search_type","keyword","sort_type","s_group_id");
        $link_url_list = $this->build_link_par($par_arr);  

        $line_per_page = _line_per_page;
        $link_url = "?" . $this->build_link_url(); 

        $top_url = base64_encode( $link_url );
        
        $result = class_B::get_supply_list( &$total_rows );

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
   }

   /////////////////////////////////////////
   // B201 -> add (공급처 신규등록)
   function add()
   {
        global $template;
        global $connect,$ext1,$ext2,$top_url;

        $id = trim($_POST["id"]);
        $passwd = trim($_POST["passwd"]);
        $name = trim($_POST["name"]);

        $zip1 = $_POST["zip1"];
        $zip2 = $_POST["zip2"];
        $address1 = $_POST["address1"];
        $address2 = $_POST["address2"];
        $boss = $_POST["boss"];
        $tel = $_POST["tel"];
        $mobile = $_POST["mobile"];
        $corpno = $_POST["corpno1"]."-".$_POST["corpno2"]."-".$_POST["corpno3"];
        $admin = $_POST["admin"];
        $md = $_POST["md"];
        $memo = $_POST["memo"];
        $email = $_POST["email"];
        $group_id = $_POST["s_group_id"];
        $account_number = $_POST["account_number"];

        $sql = "select max(code) max_code from userinfo where level = 0";
        $list = mysql_fetch_array(mysql_query($sql, $connect));
        $max_code = $list[max_code];
        if ($max_code < 20000) $max_code = 20000;
        $max_code = $max_code + 1;

        $sql = "
            insert into userinfo set
                id = '$id',
                passwd = password('$passwd'),
                name = '$name',
                level = 0,
                zip1 = '$zip1',
                zip2 = '$zip2',
                address1 = '$address1',
                address2 = '$address2',
                boss = '$boss',
                tel = '$tel',
                mobile = '$mobile',
                corpno = '$corpno',
                admin = '$admin',
                md = '$md',
                memo = '$memo',
                code = '$max_code',
                crdate = now(),
                ext1   = '$ext1',
                ext2   = '$ext2',
                email  = '$email',
                group_id = '$group_id',
                account_number = '$account_number'
                
        ";

        mysql_query ( $sql, $connect );
        //echo "<script>document.location.href = '?template=B200';</script>";
        //echo "<script>document.location.href = '" . base64_decode($top_url) . "';</script>";
        echo "<script>document.location.href = '?template=B202&_code=$max_code&top_url=$top_url';</script>";
        
        exit;
   }

   /////////////////////////////////////////
   // B204 -> add2 (공급처 신규등록 : 공급처 검색 화면에서)
   function add2()
   {
        global $template;
        global $connect,$ext1,$ext2;

        $id = trim($_POST["id"]);
        $passwd = trim($_POST["passwd"]);
        $name = trim($_POST["name"]);

        $zip1 = $_POST["zip1"];
        $zip2 = $_POST["zip2"];
        $address1 = $_POST["address1"];
        $address2 = $_POST["address2"];
        $boss = $_POST["boss"];
        $tel = $_POST["tel"];
        $mobile = $_POST["mobile"];
        $corpno = $_POST["corpno1"]."-".$_POST["corpno2"]."-".$_POST["corpno3"];
        $admin = $_POST["admin"];
        $md = $_POST["md"];
        $memo = $_POST["memo"];
        $email = $_POST["email"];
        $group_id = $_POST["s_group_id"];
        $account_number = $_POST["account_number"];

        $sql = "select max(code) max_code from userinfo where level = 0";
        $list = mysql_fetch_array(mysql_query($sql, $connect));
        $max_code = $list[max_code];
        if ($max_code < 20000) $max_code = 20000;
        $max_code = $max_code + 1;

        $sql = "
            insert into userinfo set
                id = '$id',
                passwd = password('$passwd'),
                name = '$name',
                level = 0,
                zip1 = '$zip1',
                zip2 = '$zip2',
                address1 = '$address1',
                address2 = '$address2',
                boss = '$boss',
                tel = '$tel',
                mobile = '$mobile',
                corpno = '$corpno',
                admin = '$admin',
                md = '$md',
                memo = '$memo',
                code = '$max_code',
                crdate = now(),
                ext1   = '$ext1',
                ext2   = '$ext2',
                email  = '$email',
                group_id = '$group_id',
                account_number = '$account_number'
                
        ";

        mysql_query ( $sql, $connect );
        echo "<script> opener.opener.load_supply(); opener.search(); self.close();</script>";
   }

    ///////////////////////////////////////////
    // B200 -> delete 
    function delete()
    {
        global $template, $connect,$top_url;

        $supply_id_list = $_POST["supply_id_list"];

        $id_del = '';
        $id_not = '';
        $id_arr = explode(',', $supply_id_list);
        foreach( $id_arr as $id )
        {
            $query = "select product_id from products where supply_code=$id and is_delete=0";
            $result = mysql_query($query, $connect);
            if( mysql_num_rows($result) > 0 )
                $id_not .= ($id_not ? ',' : '') . $id;
            else
                $id_del .= ($id_del ? ',' : '') . $id;
        }

        if( $id_del )
        {
            $sql = "delete from userinfo where level = 0 and code in ($id_del)";
            mysql_query($sql, $connect) or die(mysql_error());
        }
        if( $id_not )
            echo "<script>alert('삭제 실패 : [$id_not] 해당 공급처의 상품이 존재합니다.');</script>";
            
        //echo "<script>document.location.href = '?template=B200';</script>";
        
        echo "<script>document.location.href='" . base64_decode($top_url) . "';</script>";
        exit;
    }


   ////////////////////////////////////////////
   function B201()
   {
      global $template;
      global $connect, $top_url;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   // 변경 화면
   function B202()
   {
      global $template, $connect, $_code, $top_url, $is_popup, $change;
      
      $data = $this->get_supply_detail( $_code );
      $mobile = $data[mobile];
      
      $corp_pos = $this->get_ddm_pos($data[ddm_seq]);
        
        $sql = "select * from match_ddm a, sys_ddm b where a.ddm_seq = '$data[ddm_seq]' and a.ddm_seq=b.seq";
        $list = mysql_fetch_assoc(mysql_query($sql, $connect));
        
        $corp_name = $list[corp_name];

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   ////////////////////////////////////////////
   function B203()
   {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }
   ////////////////////////////////////////////
   function B204()
   {
      global $template;
      global $connect;

      $master_code = substr( $template, 0,1);
      include "template/" . $master_code ."/" . $template . ".htm";
   }

   ////////////////////////////////////////////
   // md list
   // md의 level은 2
   function get_md_list()
   {
      global $connect;
      $query = "select id, name from userinfo where level=2";
      //echo $query;
      $result = mysql_query ( $query, $connect );
      return $result; 
   }

   function get_supply_detail( $id, $type="code" )
   {
      global $connect;
      $query = "select * from userinfo where $type = '$id'";
      $result = mysql_query ( $query , $connect );
      $data = mysql_fetch_array ( $result );
      return $data;
   }

   /////////////////////////////////////////
   // 공급처 item 추가 
   function init_items()
   {
      $items = array ( "id", "name", "passwd", "md", "boss", "corpno", "tel", "email",
                       "address1","address2", "memo", "zip1", "zip2", "admin","ext1","ext2","s_group_id","account_number","ez_md","ez_admin", "ddm_seq","ddm_saip_use");
      return $items;
   }

   ///////////////////////////////////////////////
   // 공급처 리스트
   function supply_list( &$total_rows )
   {
      global $connect;
 
      $sql  = "select * from userinfo where level = 0 ";
      $query = "select * ";
      $query_cnt = "select count(*) as cnt ";

      $option = " from supply ";

      $keyword = $_POST["keyword"] ? $_POST["keyword"] : $_GET["keyword"];

      if ( $keyword  )
         $option .= " where id like '%$keyword%' 
                         or name like '%$keyword%'
                         or code like '%$keyword%'";

if ( $_SESSION[LOGIN_LEVEL] == 9 )
    echo $query. $option. $order . $limit;

      // total_row cnt
      $result = mysql_query ( $query_cnt . $option );
      $data = mysql_fetch_array ( $result );
      $total_rows = $data[cnt];

      $order = " order by reg_date";

      $page = $_GET["page"];
      if ( !$page ) $page = 1;
      $starter = ($page - 1) * 20;
      
      $limit = " limit $starter, 20";

      // total list
      $result = mysql_query ( $query . $option . $order . $limit, $connect );

      return $result;
   }

   ///////////////////////////////////////////////////////
   // 변경
   function modify()
   {
      global $connect, $_code, $top_url, $is_popup;

      $items = $this->init_items();

      // query build
      $mobile = $_POST[mobile];

      $query = "update userinfo set ";
      $query .= " mobile = '$mobile', ";

      $i = 1;
      foreach ( $items as $item )
      {
         global $$item;
         
         if ( $item == "s_group_id" )
            $query .= "group_id='$s_group_id'";
         else
            $query .= "$item='" . $$item ."'";
            
         if ( $i != count ( $items )) 
             $query .= ",";
         $i++;
      }

      $query .= " where code = '" . $_code . "'";
debug( $query );
      if( mysql_query ( $query, $connect ) )
      {
          // 팝업에서 실행
          if( $is_popup )
          {
            echo "<script type='text/javascript'>";
            echo "opener.update_supply_info('".class_table::get_supply_new_data($_code)."');";
            echo "</script>";
          }

          $this->jsAlert("변경 되었습니다.");
      }
      else
          $this->jsAlert("이미 등록된 공급처입니다.");
      
      $id = urlencode($id);
      $this->redirect("?template=B202&_code=$_code&change=1&is_popup=$is_popup&top_url=$top_url");
      exit;
 
   } 

    //////////////////////////////////////
    // 공급처 목록 다운로드 - 파일 만들기
    function save_file()
    {
        global $connect, $keyword, $search_type, $s_group_id, $sort_type;

        // 엑셀 헤더
        $supply_data = array();
        $supply_data[] = array(
            code     => Code,
            name     => 업체명,
            id       => 아이디,
            passwd   => 패스워드,
            md       => 담당MD,
            boss     => 대표이사,
            corpno   => 사업자등록번호,
            ext1     => 추가코드1,
            ext2     => 추가코드2,
            admin    => 담당자,
            zip      => 우편번호,
            address1 => 주소,
            address2 => 상세주소,
            tel      => 연락처,
            mobile   => 휴대폰번호,
            email    => 이메일,
            memo     => 비고,
            group    => 그룹,
            account_number => 계좌번호,
            return_money   => 잔,
            ddm_saip_use   => "사입서비스 사용"
        );

        $query = "select * from userinfo where level = 0 ";
        if( $keyword )
        {
            if( $search_type == 1 )
                $query .= " and name like '%$keyword%' ";
            else if( $search_type == 2 )
                $query .= " and code = '$keyword' ";
            else if( $search_type == 3 )
                $query .= " and id like '%$keyword%' ";
            else if( $search_type == 4 )
                $query .= " and (address1 like '%$keyword%' or address2 like '%$keyword%') ";
        }
        
		if ( $s_group_id )
			$query .= " and group_id=$s_group_id ";
        if( $sort_type == "code" )
            $query .= " order by code desc ";
        else
            $query .= " order by name asc ";

        $result = mysql_query( $query, $connect );
        while( $data = mysql_fetch_assoc($result) )
        {
            $supply_data[] = array(
                code     => $data[code    ],
                name     => $data[name    ],
                id       => $data[id      ],
                passwd   => $data[passwd  ],
                md       => $data[md      ],
                boss     => $data[boss    ],
                corpno   => $data[corpno  ],
                ext1     => $data[ext1    ],
                ext2     => $data[ext2    ],
                admin    => $data[admin   ],
                zip      => $data[zip1    ] . "-" . $data[zip2    ],
                address1 => $data[address1],
                address2 => $data[address2],
                tel      => $data[tel     ],
                mobile   => $data[mobile  ],
                email    => $data[email   ],
                memo     => $data[memo    ],
                group    => $this->get_supply_group_name($data[group_id]),
                account_number => $data[account_number],
                return_money => $data[return_money],
                ddm_saip_use => $data[ddm_saip_use]
            );
        }
        $this->make_file( $supply_data, "download.xls" );
        echo "<script language='javascript'>parent.set_file('download.xls')</script>";
    }

   function make_file( $arr_datas, $filename = "download_data.xls", $is_html = 1 )
   {
	    $saveTarget = _upload_dir . $filename; 

        // file open
        $handle = fopen ($saveTarget, "w");

        $buffer .= "<meta http-equiv='Content-Type' content='text/html; charset=utf-8'>
                    <html xmlns:x=\"urn:schemas-microsoft-com:office:excel\">
                    <body>
                    <html><table border=1>
                    ";
        fwrite($handle, $buffer);

        // for row
        for( $i=0; $i < count( $arr_datas ); $i++ )
        {
            $row = $arr_datas[$i];
            $buffer = "<tr>\n";

            if( $i == 0 )
            {
                // for column
                foreach ( $row as $key=>$value) 
                    $buffer .= "<td style='font:bold 10pt \"굴림\"; white-space:nowrap; background:#CCFFCC;'>" . $value . "</td>";
            }
            else
            {
                // for column
                foreach ( $row as $key=>$value) 
                {
                    // 숫자
                    if( $key == 'xxx' )
                        $buffer .= "<td style='font:12px \"굴림\"; white-space:nowrap; mso-number-format:\"\#\,\#\#0_\)\;\[Red\]\\\(\#\,\#\#0\\\)\";'>" . $value . "</td>";
                    // 문자
                    else
                        $buffer .= "<td style='font:12px \"굴림\"; white-space:nowrap; mso-number-format:\\@'>" . $value . "</td>";
                }
            }
            
            $buffer .= "</tr>\n";

            fwrite($handle, $buffer);
        }
        fwrite($handle, "</table>");

        ////////////////////////////////////// 
        // file close and delete it 
        // file은 보관함
        fclose($fp);

        return $filename; 
   }

    //////////////////////////////////////
    // 공급처 목록 다운로드 - 파일 다운받기
    function download2()
    {
        global $filename;
        $obj = new class_file();
        $obj->download_file( $filename, "supply_list.xls");
    }    

    //////////////////////////////////////
    // 공급처 목록 업로드
    function upload()
    {
        global $connect, $_file;
        
        $obj = new class_file();
        $arr = $obj->upload();

        $header = true;
        foreach ( $arr as $row )
        {
            // 코드가 정수가 아니면 넘어간다.
            if( !is_numeric($row[0]) )  continue;
            
            // 등록된 공급처인지 확인
            $query = "select * from userinfo where code='$row[0]' and level=0";
            $result = mysql_query($query, $connect);
            
            // 공급처 그룹 확인
            if( $row[17] )
            {
                // 등록된 그룹인지 확인
                $query_group = "select * from supply_group where name='" . addslashes($row[17]) . "'";
                $result_group = mysql_query($query_group, $connect);
                if( mysql_num_rows($result_group) )
                {
                    $data_group = mysql_fetch_assoc($result_group);
                    $group_id = $data_group[group_id];
                }
                else
                    continue;
            }
            
            // 잔
            $return_money = preg_replace('/[^0-9]/','',$row[19]);
            
            // 있으면 업데이트
            if( mysql_num_rows($result) > 0 )
            {
                $data = mysql_fetch_assoc($result);
                
                $query = "update userinfo 
                             set id       = '" . addslashes($row[2] ) . "',
                                 passwd   = password('" . addslashes($row[3] ) . "'),
                                 name     = '" . addslashes($row[1] ) . "',
                                 ext1     = '" . addslashes($row[7] ) . "',
                                 ext2     = '" . addslashes($row[8] ) . "',
                                 boss     = '" . addslashes($row[5] ) . "',
                                 corpno   = '" . addslashes($row[6] ) . "',
                                 admin    = '" . addslashes($row[9] ) . "',
                                 tel      = '" . addslashes($row[13]) . "',
                                 mobile   = '" . addslashes($row[14]) . "',
                                 zip1     = '" . substr( $row[10], 0, 3 ) . "',
                                 zip2     = '" . substr( $row[10], -3, 3 ) . "',
                                 address1 = '" . addslashes($row[11]) . "',
                                 address2 = '" . addslashes($row[12]) . "',
                                 md       = '" . addslashes($row[4] ) . "',
                                 email    = '" . addslashes($row[15]) . "',
                                 memo     = '" . addslashes($row[16]) . "',
                                 account_number = '" . addslashes($row[18]) . "',
                                 return_money = '$return_money',
                                 ddm_saip_use = '$row[20]',
                                 group_id = '$group_id',
                                 crdate   = now()
                           where code = '$row[0]'";

                // 잔 로그
                if( $return_money != $data[return_money] )
                {
                    $query_money_log = "insert supply_money_log
                                           set crdate = now()
                                              ,cruser = '$_SESSION[LOGIN_ID]'
                                              ,supply_code = '$row[0]'
                                              ,work_type = 0
                                              ,work_money = '$return_money'
                                              ,supply_money = '$return_money'
                                              ,memo = '일괄수정' ";
                    mysql_query($query_money_log, $connect);
                }
            }

            // 없으면 추가
            else
            {
                // 2014-09-11 장경희
                if( _DOMAIN_ == 'maru' && !$group_id )
                    $group_id = 17;
                
                $query = "insert userinfo 
                             set code     = '$row[0]',
                                 id       = '" . addslashes($row[2])  . "',
                                 passwd   = password('" . addslashes($row[3])  . "'),
                                 name     = '" . addslashes($row[1])  . "',
                                 ext1     = '" . addslashes($row[7])  . "',
                                 ext2     = '" . addslashes($row[8])  . "',
                                 boss     = '" . addslashes($row[5])  . "',
                                 corpno   = '" . addslashes($row[6])  . "',
                                 admin    = '" . addslashes($row[9])  . "',
                                 tel      = '" . addslashes($row[13]) . "',
                                 mobile   = '" . addslashes($row[14]) . "',
                                 zip1     = '" . substr( $row[10], 0, 3 ) . "',
                                 zip2     = '" . substr( $row[10], -3, 3 ) . "',
                                 address1 = '" . addslashes($row[11]) . "',
                                 address2 = '" . addslashes($row[12]) . "',
                                 md       = '" . addslashes($row[4] ) . "',
                                 email    = '" . addslashes($row[15]) . "',
                                 memo     = '" . addslashes($row[16]) . "',
                                 account_number = '" . addslashes($row[18]) . "',
                                 return_money = '$return_money',
                                 group_id = '$group_id',
                                 crdate   = now()";

                // 잔 로그
                if( $return_money )
                {
                    $query_money_log = "insert supply_money_log
                                           set crdate = now()
                                              ,cruser = '$_SESSION[LOGIN_ID]'
                                              ,supply_code = '$row[0]'
                                              ,work_type = 0
                                              ,work_money = '$return_money'
                                              ,supply_money = '$return_money'
                                              ,memo = '일괄등록' ";
                    mysql_query($query_money_log, $connect);
                }
            }
            mysql_query( $query, $connect );
            
        }
        $this->jsAlert("완료하였습니다.");
        $this->redirect("?template=B203");
        exit;
    }
    
    function update_request_info()
    {
        global $connect, $group_name, $group_id;

        $val = array();
        
        $query = "update supply_group set name ='$group_name' where group_id = '$group_id'";
        $result = mysql_query($query,$connect);
          
        $val["error"] = 0;
        echo json_encode($val);
    }
    
    function get_supplygroup_list()
    {
        global $connect;
        
        $val = array();
        
        $query = "select * from supply_group order by name";
        $result = mysql_query($query, $connect);
        while( $data = mysql_fetch_assoc($result) )
        {
            $val[] = array(
                "group_id" => $data[group_id],
                "name"     => $data[name]
            );
        }
        
        echo json_encode( $val );
    }
    
    //----------------------------------------
	//-- 임시 : 공급처 코드
	/*
    function B210()
    {
        //global $template, $connect, $_code, $top_url, $is_popup, $change;
        global $template, $_code, $top_url, $is_popup, $change;
        
        $connect10 = $this->temp_sys_db_connect("61.109.247.122", "root", "pimz8282", "ejjung");
      
        $data = $this->temp_get_supply_detail( '20191' );
        $mobile = $data[mobile];
        
        $corp_pos = $this->get_ddm_pos($data[ddm_seq]);
        
        $sql = "select * from match_ddm a, sys_ddm b where a.ddm_seq = '$data[ddm_seq]' and a.ddm_seq=b.seq";
        $list = mysql_fetch_assoc(mysql_query($sql, $connect10));
        
        $corp_name = $list[corp_name];
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";  
    }
    */
    
    //----------------------------------------
	//-- 동대문 검색 팝업
    function B211()
    {
        global $connect;
        global $template, $string;
        
        foreach($_REQUEST as $key => $val) $$key = $val;
        
        // 추후 sys계정으로 연결
        //$connect10 = $this->temp_sys_db_connect("61.109.247.122", "root", "pimz8282", "ejjung");
        
        //$par = array("template","action","start_date","end_date", "query_opt", "query_str");
        $link_url = "popup.htm?template=B211&string=$string";
        
        if ( $page )
            $result = $this->search_ddm( &$total_rows, $page);
        /*
        $sql = "select * from sys_ddm where corp_name like '%${string}%' order by corp_name";
        $result = mysql_query($sql, $connect10);
        */
        
        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    // 동대문 업체 리스트
    function search_ddm(&$max_row, $page)
    {
        //global $connect, $query_opt, $query_str, $start_date, $end_date;
        global $connect;
        global $string;
        
        // 추후 sys계정으로 연결
        //$connect10 = $this->temp_sys_db_connect("61.109.247.122", "root", "pimz8282", "ejjung");

        $query = "select a.* from sys_ddm a, match_ddm b where a.seq=b.ddm_seq";

        if ( $string )
        {
            $query .= " and a.corp_name like '%${string}%' ";
        }
        
        $group = "group by corp_name";
                     
        $order = " order by a.corp_name";

        $result = mysql_query( $query, $connect );
        
        $max_row = mysql_num_rows( $result );
        
        if ( !$page ) $page = 1;
	        $starter = ($page-1) * 10;
                        
        $limit = " limit $starter, 10";
        
        $query_final = $query . $group . $order . $limit;
        $result = mysql_query ( $query_final, $connect );

        return $result;
    }
    /*
    function modify_tmp()
    {
        //global $connect;
        global $template, $string;
        global $_code, $top_url, $is_popup;
        
        foreach($_REQUEST as $key => $val) $$key=$val;
        
        // 추후 로컬계정에서 저장
        $connect10 = $this->temp_sys_db_connect("61.109.247.122", "root", "pimz8282", "ejjung");
        
        $items = $this->temp_init_items();

        // query build
        $mobile = $_POST[mobile];
        
        $query = "update userinfo set ";
        $query .= " mobile = '$mobile', ";
        
        $i = 1;
        foreach ( $items as $item )
        {
            global $$item;
            
            if ( $item == "s_group_id" )
                $query .= "group_id='$s_group_id'";
            else
                $query .= "$item='" . $$item ."'";
            
            if ( $i != count ( $items )) 
                $query .= ",";
            $i++;
        }
        
        $query .= " where code = '" . $_code . "'";
        
        //echo $query;
        //exit;
        
        if( mysql_query ( $query, $connect10 ) )
        {
          // 팝업에서 실행
          if( $is_popup )
          {
            echo "<script type='text/javascript'>";
            echo "opener.update_supply_info('".class_table::get_supply_new_data($_code)."');";
            echo "</script>";
          }
        
          $this->jsAlert("변경 되었습니다.");
        }
        else
          $this->jsAlert("이미 등록된 공급처입니다.");
        
        $id = urlencode($id);
        $this->redirect("?template=B210&_code=$_code&change=1&is_popup=$is_popup&top_url=$top_url");
    }
    
    function temp_get_supply_detail( $id, $type="code" )
    {
        //global $connect;
        // 추후 로컬계정에서 저장
        $connect10 = $this->temp_sys_db_connect("61.109.247.122", "root", "pimz8282", "ejjung");
        
        $query = "select * from userinfo where $type = '$id'";
        $result = mysql_query ( $query , $connect10 );
        $data = mysql_fetch_array ( $result );
        return $data;
    }
    
    function temp_init_items()
    {
        $items = array ( "id", "name", "passwd", "md", "boss", "corpno", "tel", "email",
                       "address1","address2", "memo", "zip1", "zip2", "admin","ext1","ext2","s_group_id","account_number","ez_md","ez_admin", "ddm_seq");
        return $items;
    }
    */
    
    function get_ddm_pos($ddm_seq)
    {
        global $connect;
        // 추후 sys계정으로 연결
        //$connect10 = $this->temp_sys_db_connect("61.109.247.122", "root", "pimz8282", "ejjung");
        
        $sql = "select * from match_ddm where ddm_seq = '$ddm_seq'";
        $result = mysql_query($sql, $connect);
        
        $corp_pos = "";
	    $check = true;
	    while($list = mysql_fetch_assoc($result)){
	        $sql = "select * from sys_ddm_pos where ddm_code = '$list[ddm_code]'";
    	    $res = mysql_query($sql, $connect);
    	    $list2 = mysql_fetch_assoc($res);
    	    
	        if($check){
    	        $corp_pos = ($list2[bld]) ? $list2[bld] : "";
    	        $corp_pos .= ($list2[floor]) ? " ".$list2[floor]."층 " : "";
    	        $corp_pos .= ($list2[ho]) ? $list2[ho] : "";
    	        $first_ho = $list2[ho];
    	        $check = false;
    	    }
    	    $last_ho = $list2[ho];
	    }
	    
	    if(mysql_num_rows($result) > 0){   
    	    if($first_ho != $last_ho)
        	    $corp_pos .= " ~ ".$last_ho."호";
        	else
    	        $corp_pos .= "호";
    	}
        
        return $corp_pos;
    }
    
    /*
    function temp_sys_db_connect($host, $db_id, $db_pw, $db_name)
    {
        $connect10 = mysql_connect($host, $db_id, $db_pw, $db_name);
        mysql_select_db($db_name, $connect10);
        
        $charset="utf8";
        mysql_query("set session character_set_connection=${charset};", $connect10);
        mysql_query("set session character_set_results=${charset};", $connect10);                                                                                                                     
        mysql_query("set session character_set_client=${charset};", $connect10);
        
        return $connect10;
    }
    */
    
    function B220()
    {
        global $connect, $template, $supply_code, $page;

        $query = "select * from userinfo where code=$supply_code";
        $result = mysql_query($query, $connect);
        $supply_info = mysql_fetch_assoc($result);
        
        if( !$page )  $page = 1;
        $range = 20;
        $start = $range * ($page - 1);
        $query = "select * from supply_money_log where supply_code='$supply_code'";
        $result = mysql_query($query, $connect);
        
        $total_rows = mysql_num_rows($result);
        $total_pages = ceil( $total_rows / $range );

        $query .= " order by seq desc limit $start, $range";
        $result = mysql_query($query, $connect);

        $master_code = substr( $template, 0,1);
        include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    function arrange_money()
    {
        global $connect, $supply_code, $money, $memo;

        $query = "update userinfo set return_money = '$money' where code='$supply_code' ";
        mysql_query($query, $connect);
        
        $query = "insert supply_money_log
                     set crdate = now()
                        ,cruser = '$_SESSION[LOGIN_ID]'
                        ,supply_code = '$supply_code'
                        ,work_type = 0
                        ,work_money = '$money'
                        ,supply_money = '$money'
                        ,memo = '$memo' ";
        mysql_query($query, $connect);
    }


	function change_passwd()
	{
		global $connect;

		foreach ($_REQUEST as $key=>$value) $$key = trim($value);

		$sql = "select id from userinfo where code = '$code' and level = 0";
		$list = mysql_fetch_assoc(mysql_query($sql, $connect));

		if ($list) {
			$upd_sql = "update userinfo set passwd = password('$new_passwd'), last_modified = now() where code = '$code' and level = 0";
			mysql_query($upd_sql, $connect) or die(mysql_error());
			
			echo 1;
			
		} else {
			echo 0;
		}
	}
}

?>
