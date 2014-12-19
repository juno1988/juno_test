<?

//////////////////////////////////////////////
//
//	date : 2014.01.21
//	worker : icy(임찬영)
//	name : class_html_parse 
//	
//

class class_html_parse
{
	function parse( $filename )
	{
		$table_data = array();
		
		$string_html = file_get_contents($filename);		

		$string_html = "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\">" . $string_html;

		$string_html = str_replace("<TH", "<td", $string_html);
		$string_html = str_replace("<th", "<td", $string_html);
		$string_html = str_replace("/TH>", "/td>", $string_html);
		$string_html = str_replace("/th>", "/td>", $string_html);
		$string_html = mb_convert_encoding($string_html, 'UTF-8', mb_detect_encoding($string_html));

		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->loadHTML( $string_html );
		$rows = $dom->getElementsByTagName('tr');
		for ($i=0; $i < $rows->length; $i++)
		{
		    $cells = $rows->item($i)->getElementsByTagName('td');
			for ($j=0; $j < $cells->length; $j++)
			{
				$raw_data = $cells->item($j)->textContent;
		        $table_data[$i][$j] = trim( $raw_data );
		    }
		}

		return $table_data;
	}
}

?>
