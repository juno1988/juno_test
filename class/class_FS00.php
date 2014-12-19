<? 
require_once "class_top.php";
require_once "class_C.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_transcorp.php";

////////////////////////////////
// class name: class_FS00
//
class class_FS00 extends class_top {

    ///////////////////////////////////////////

    function FS00()
    {
		global $connect		, 	$template	;
		global $start_date	,	$end_date	;
		global $date_type	,	$query_type	;
		global $query_str	;
		global $multi_print	;
		global $seq_group	,	$reg_type	;

		$query_str = trim($query_str);
		if($multi_print == 'on')
			$seq_group = 'on';

		$query = "	  SELECT	A.order_seq  a_order_seq ,
								A.trans_no   a_trans_no  ,
								A.trans_corp a_trans_corp,
								A.crdate     a_crdate    ,
								A.owner      a_owner	 ,
								A.reg_type	 a_reg_type	 ";
		if($seq_group == 'on')
			$query .=", COUNT(A.order_seq) AS	 a_cnt ";

	    $query .= "		FROM	trans_upload_log 	AS A	
	    			   WHERE	A.order_seq >= 0	";

        if($date_type != 'all')        
	        $query .= "	 AND	A.$date_type >='$start_date 00:00:00'
						 AND	A.$date_type <='$end_date 23:59:59' ";
		
		if($query_str != '')
				$query .= "  AND A.$query_type = '$query_str'";
				
		if($reg_type > 0)
				$query .= "  AND A.reg_type = '$reg_type'";
		
		if($seq_group  == 'on')
			$query .= "	GROUP BY	A.order_seq ASC";
			
        $query .= "	ORDER BY	 a_crdate DESC,								 
							 	 A.order_seq ASC";


		$result = mysql_query($query, $connect);
		
//		if( $_SESSION[LOGIN_ID] == '최웅' || $_SESSION[LOGIN_ID] == 'root2' )
//			echo $query;

		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
    }
    
    
    function FS01()
	{
		
		global $connect		;
		global $template	;
				
		global $pack_seq	;		
		global $cnt			;
		 
		 
		$query = "SELECT	B.order_seq		pack_seq	,
							B.trans_no		b_trans_no	,
							B.trans_corp	b_trans_corp,
							B.crdate		b_crdate	,
							B.owner			b_owner		,
							B.reg_type		b_reg_type		
							
								
					FROM	trans_upload_log	AS	B
					
					WHERE	B.order_seq = $pack_seq
					
					ORDER BY	 b_crdate DESC,
							 	 B.order_seq ASC";
		

//		if( $_SESSION[LOGIN_ID] == '최웅' )
//			echo $query;

		$result = mysql_query($query, $connect);
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
    }
    
}

?>