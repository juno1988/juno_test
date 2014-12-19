<?
  $date_arr = array();

  $date_arr[] = "2012-04-01";
  $date_arr[] = "2012-03-01";
  $date_arr[] = "2012-05-01";
  $date_arr[] = "2012-03-01";
  $date_arr[] = "2012-03-01";
  $date_arr[] = "2012-03-01";

  sort($date_arr);
echo sizeof($date_arr);
  for ($i=0; $i < sizeof($date_arr); $i++)
  {
	echo $date_arr[$i] . "/";
  }

  echo "\n";
  $res_arr = array_unique($date_arr);
var_dump($res_arr);
echo sizeof($res_arr);

  foreach ($res_arr as $key=>$value)
  {
	echo $key . "=>" . $value;
  }
?>
