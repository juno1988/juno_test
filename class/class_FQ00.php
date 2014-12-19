<? 
require_once "class_top.php";
require_once "class_C.php";
require_once "class_file.php";
require_once "class_shop.php";
require_once "class_transcorp.php";

////////////////////////////////
// class name: class_FQ00
//

class class_FQ00 extends class_top {

    ///////////////////////////////////////////

    function FQ00()
    {
		global $connect, 	$template;
		global $start_date,	$end_date;
		global $date_type,	$query_type;
		global $status,		$order_cs;
		global $query_str;
		global $many_print;

		if($query_str != ''&& $query_type == 'trans_no')
		{
			$query = "SELECT	B.seq				b_seq			,
								IF(B.seq IS NULL , A.order_seq, IF(B.pack > 0,B.pack ,B.seq)) AS pack_seq,
								B.collect_date		b_collect_date	,
								B.collect_time		b_collect_time	,
								B.order_cs			b_order_cs		,                                
								B.status			b_status		,
								A.trans_no			b_trans_no		,
								A.trans_corp		b_trans_corp	,
								B.trans_date		b_trans_date	,
								B.trans_date_pos	b_trans_date_pos	 

	             		FROM	trans_upload_log 	AS A
	  		 LEFT OUTER JOIN 	orders           	AS B
	  				   	  ON 	A.order_seq = B.seq

	            	   WHERE	A.trans_no = '$query_str'
	            	   
	            	GROUP BY	pack_seq
	            	ORDER BY	B.collect_date ASC,
								B.collect_time ASC";
		}
		else
		{
			$query = "SELECT	B.seq				b_seq			,
								IF(B.pack>0, B.pack, B.seq) AS pack_seq,
								B.collect_date		b_collect_date	,
								B.collect_time		b_collect_time	,
								B.order_cs			b_order_cs		,
								B.status			b_status	    ,
								B.trans_no			b_trans_no		,
								B.trans_corp		b_trans_corp    ,
								B.trans_date		b_trans_date    ,
								B.trans_date_pos	b_trans_date_pos  
                                
	             		FROM	orders           	AS B
	             		
	             	   WHERE	B.$date_type >='$start_date 00:00:00'
						 AND	B.$date_type <='$end_date 23:59:59'";
						  
			if($query_str != '')
			{			
				$query_2 = "SELECT 	IF(A.pack != 0, A.pack , A.seq) AS pack_seq
							  FROM 	orders A
							 WHERE	A.$query_type = '$query_str'";
					
				$result = mysql_query($query_2, $connect);
				$data = mysql_fetch_assoc($result);
				
				$query .= "and	B.seq = $data[pack_seq] ";
			}
	
			if($status > '-1')			
				$query .= "and	B.status = $status ";
				
			if($order_cs > '-1')
				$query .= "and	B.order_cs = $order_cs ";
			
			$query .= "	GROUP BY	pack_seq		
						ORDER BY	B.collect_date ASC,
									B.collect_time ASC";
		}
		
		$result = mysql_query($query, $connect);
		
		if( $_SESSION[LOGIN_ID] == 'root10' || $_SESSION[LOGIN_ID] == 'root2' )
			echo $query;

		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
    }



	function FQ01()
	{
		
		global $connect		;
		global $template	;
				
		global $pack_seq	;		
		global $order_seq	;
		global $collect_date;
		global $status		;
		global $order_cs	;
		 
		 
		if($status == " ")
		{
			$query = "SELECT	B.order_seq		pack_seq	,
								B.trans_no		b_trans_no	,
								B.trans_corp	b_trans_corp,
								B.crdate		b_crdate	,
								B.owner			b_owner		
									
						FROM	trans_upload_log	AS	B
						
						WHERE	B.order_seq = $pack_seq
						
						ORDER BY B.crdate		ASC,
								 B.order_seq	ASC	";
		}
		else
		{
			$query = "SELECT	A.seq			pack_seq	,
								A.pack			a_pack		,
								B.trans_no		b_trans_no	,
								B.trans_corp	b_trans_corp,
								B.crdate		b_crdate	,
								B.owner			b_owner		
								
						FROM	orders AS A,
								trans_upload_log	AS	B
								
						WHERE	A.seq = B.order_seq
						AND		(A.pack = $pack_seq
						OR		A.seq =  $pack_seq)
						
						ORDER BY B.crdate	ASC,
								 A.seq		ASC		";
		}


		if( $_SESSION[LOGIN_ID] == '최웅' )
			echo $query;

		$result = mysql_query($query, $connect);
		$master_code = substr( $template, 0,1);
		include "template/" . $master_code ."/" . $template . ".htm";
    }
}

?>