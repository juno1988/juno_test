<?
	include_once "class_html_parse.php";

	$arr_data = class_html_parse::parse( "test.html" );

	print_r( $arr_data );

?>
