<?
	
///////////////////////////////////////////
//
//	date : 2014.01.20 
//	worker : icy
//	name : class_fake_trans
//

include_once "../lib/config_api.php";
include_once "class_db.php" ;

class class_fake_trans
{
	function reg_trans_no( $trans_no )
	{
		$query = "select * 
					from orders
				   where trans_no in ( $trans_no )";
		class_fake_trans::reg_action( $query );
	}

	function reg_seq( $seq )
	{
		$query = "select * 
					from orders 
				   where seq in ( $seq )";
		class_fake_trans::reg_action( $query );
	}
	
	function reg_action ( $query )
	{
		$odb = new class_db();
		$connect = $odb->connect( _MYSQL_HOST_, _MYSQL_ID_, _MYSQL_PASSWD_ );

		$now = date("Y-m-d H:i:s");
		$now_date = date("Y-m-d");
		$now_time = date("H:i:s");
		$writer = $_SESSION[LOGIN_NAME];
		$cs_type = "36";
		$cs_reason = "";

		if ( _DOMAIN_ == "dorosiwa" )
			$cs_result = "1";
		else
			$cs_result = "0";
	
		$content = "가배송 처리";
		$trans_who = "";
		$trans_fee = "";

		$result = mysql_query ( $query, $connect );
		while ( $data = mysql_fetch_assoc( $result ) )
		{
			$deal_no = "";
 			switch ($data[shop_id] % 100 )
			{
				case 53 : 			
					$deal_no = $data[code2];
					break;
				case 54 : 										
					$deal_no = $data[code2];
					break;
				case 55 :
					$deal_no = $data[order_id_seq];
					break;
				default : 
					$deal_no = "";
					break;
			}

			$query = "insert into fake_trans set
						seq = '$data[seq]', 
						order_id = '$data[order_id]', 
						order_id_seq = '$data[order_id_seq]', 
						shop_id = '$data[shop_id]', 
						deal_no = '$deal_no', 
						trans_no = '$data[trans_no]', 
						trans_corp = '$data[trans_corp]',
						status = '$data[stauts]', 
						reg_date = '$now' 
					on duplicate key update 
						trans_no = '$data[trans_no]',
						trans_corp = '$data[trans_corp]',
						status = '$data[status]',
						reg_date = '$now' ";

			mysql_query( $query, $connect );
		
			$query = "select seq, pack 
						from orders 
						where seq = '$data[seq]'";

			$result_ = mysql_query ( $query, $connect );
			$list = mysql_fetch_assoc( $result_ );
			if ( $list['seq'] == $list['pack'] || $list['pack'] == "0" )
			{
				$query = "select * 
							from csinfo 
							where order_seq = '$data[seq]'
							and cs_type = '$cs_type'";
				
				$result_2 = mysql_query( $query, $connect );
				if ( !mysql_num_rows( $result_2 ) )
				{
					$query = "insert into csinfo set 
								order_seq = '$data[seq]',
								input_date = '$now_date',
								input_time = '$now_time',
								writer = '$writer',
								cs_type = '$cs_type',
								cs_reason = '$cs_reason',
								cs_result = '$cs_result',
								content = '$content',
								pack = '$data[pack]' ";
	
					mysql_query ( $query, $connect );				
				}
			}
		}
	}	
}

?>
