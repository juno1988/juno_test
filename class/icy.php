<?
	include_once "class_convert_address.php";

//	$address_input = "서울시 중구 퇴계로 6길 22-15";
	$address_input = "경기 의정부시 신흥로 239번길 39-21 ";
	$zipcode = "480849";


	$address_input = "경기도 평택시 세교2로 38";
	$zipcode = "450739";


	$address_input = $argv[1];
	$zipcode = $argv[2];


//	$address_input = "경기 의정부시 신흥로 239번길 39-21 ";
//	$zipcode = "480849";
	$address_input = "제주특별자치도 제주시 한림읍 한림중앙로 283 못거리가든";
	$zipcode = "695921";



	$address_input = "서울특별시 동대문구 제기로31길 24 (청량리동) (지번:청량리동 ) 지층101호";
	$zipcode = "130-867";

	$address_input = "광주광역시 동구 천변좌로 718-9 (용산동) 용산동 245 번지 1층";
	$zipcode = "501-832";






	$address_input = "인천 남동구 정각로 2 2층 원무부";
	$zipcode = "405-835";

	$address_input = "서울 양천구 은행정로6길 6 101호";
	$zipcode = "158-861";




	$address_input = "전남 여수시 남면 우두로 227";
	$zipcode = "556-842";
	
	$address_input = "서울시 중구 다산로 150";
	$zipcode = "100-450";

	$address_return = class_convert_address::convert_address( $address_input, $zipcode );

	echo $address_return."\n";





	

?>
