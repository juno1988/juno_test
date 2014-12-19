<?

class class_test
{
	public $x;
	public $y;

	function __construct( $x, $y )
	{
		$this->x = $x;
		$this->y = $y;
	}

	function s()
	{
		echo serialize( $this);
	}
}


	$test = new class_test("a", "b");
	$test->s();

	print_r ( $test );




?>
