<?

class class_convert_text_to_mp3 
{
		function __construct()
	    {	

		}

		function splitString($str)
		{
			$ret=array();
			$arr=explode(" ",$str);
			$constr='';
			for($i=0;$i<count($arr);$i++)
			{
				if(strlen($constr.$arr[$i]." ") < 98)
				{
					$constr =$constr.$arr[$i]." ";
				}
				else
				{
					$ret[] =$constr;
					$constr='';
					$i--; //add the word back.
				}
		 
			}
			$ret[]=$constr;
			return $ret;
		}

		function downloadMP3($url,$file)
		{
			$ch = curl_init();  
			curl_setopt($ch,CURLOPT_URL,$url);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			$output=curl_exec($ch);
			curl_close($ch);
			if($output === false)   
			return false;
		 
			$fp= fopen($file,"wb");
			fwrite($fp,$output);
			fclose($fp);
		 
			return true;
		}

		function CombineMultipleMP3sTo($FilenameOut, $FilenamesIn) {
		 
			foreach ($FilenamesIn as $nextinputfilename) {
				if (!is_readable($nextinputfilename)) {
					echo 'Cannot read "'.$nextinputfilename.'"<BR>';
					return false;
				}
			}
		 
			ob_start();
			if ($fp_output = fopen($FilenameOut, 'wb')) {
		 
				ob_end_clean();
				// Initialize getID3 engine
				$getID3 = new getID3;
				foreach ($FilenamesIn as $nextinputfilename) {
		 
					$CurrentFileInfo = $getID3->analyze($nextinputfilename);
					if ($CurrentFileInfo['fileformat'] == 'mp3') {
		 
						ob_start();
						if ($fp_source = fopen($nextinputfilename, 'rb')) {
		 
							ob_end_clean();
							$CurrentOutputPosition = ftell($fp_output);
		 
							// copy audio data from first file
							fseek($fp_source, $CurrentFileInfo['avdataoffset'], SEEK_SET);
							while (!feof($fp_source) && (ftell($fp_source) < $CurrentFileInfo['avdataend'])) {
								fwrite($fp_output, fread($fp_source, 32768));
							}
							fclose($fp_source);
		 
							// trim post-audio data (if any) copied from first file that we don't need or want
							$EndOfFileOffset = $CurrentOutputPosition + ($CurrentFileInfo['avdataend'] - $CurrentFileInfo['avdataoffset']);
							fseek($fp_output, $EndOfFileOffset, SEEK_SET);
							ftruncate($fp_output, $EndOfFileOffset);
		 
						} else {
		 
							$errormessage = ob_get_contents();
							ob_end_clean();
							echo 'failed to open '.$nextinputfilename.' for reading';
							fclose($fp_output);
							return false;
		 
						}
		 
					} else {
		 
						echo $nextinputfilename.' is not MP3 format';
						fclose($fp_output);
						return false;
		 
					}
		 
				}
		 
			} else {
		 
				$errormessage = ob_get_contents();
				ob_end_clean();
				echo 'failed to open '.$FilenameOut.' for writing';
				return false;
		 
			}
		 
			fclose($fp_output);
			return true;
		}

		function converTextToMP3($str,$outfile)
		{
			$base_url='http://translate.google.com/translate_tts?tl=en-us&ie=UTF-8&q=';
			$words = $this->splitString($str);
			$files=array();
			foreach($words as $word)
			{
				$url= $base_url.urlencode($word);
				$filename =md5($word).".mp3";
				echo ".";
				if(!$this->downloadMP3($url,$filename))
				{
					echo "Failed to Download URL.".$url."n";
				}
				else
				{
					$files[] = $filename;
				}
		 
			}
		 
			if(count($files) == count($words)) //if all the strings are converted
				$this->CombineMultipleMP3sTo($outfile,$files);
			else
				echo "ERROR. Unable to convert n";
		 
			foreach($files as $file)
			{
				unlink($file);
			}
		}

}


?>
