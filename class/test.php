<?
require_once "class_top.php";

$str = "가나다라마바사아자차카타";
echo "$str\n";

$str = class_top::substr_kor( $str, 10 );
echo "$str[0] \n";


exit;

$arr = array();
array_push( $arr, "abc" );
array_push( $arr, "abc" );
array_push( $arr, "abc" );

print_r ( $arr );
$pos = array_search('abc', $arr);
unset($arr[$pos]);
print_r ( $arr );

exit;
$macro = "<selector>[shop_id][10001:옥션||10002:지마켓||10003:다음]";
preg_match( "/[\[](.*)[\]]/", $macro, $matches );

print_r ( $matches );
exit;

echo substr('abcdef',1, -1);
echo("\n");
?>
