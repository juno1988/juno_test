<?
	include_once "class_convert_address.php";

	$zip_code = class_convert_address::get_zip_code_by_road_name( '다산로', '150' );

	print_r ( $zip_code );


?>
