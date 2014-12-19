<?
class class_icy
{
	public $x;
	public $y;
	public $z;	

	function __construct( $x="", $y="", $z="" )
	{
		if ( $x ) $this->x = $x; else $this->x = "999";
		if ( $y ) $this->y = $y; else $this->y = "999";
		if ( $z ) $this->z = $z; else $this->z = "999";
	}
}

	$icy = new class_icy( "1", "3");

	print_r ( $icy );

?>
