<?
// name: misc
// 

class misc{

   function redirect($url)
   {
      //$url = server_url() . dirname($_SERVER['PHP_SELF']) . "/" . $relative_url;
      if (!headers_sent())
      {
          header("Location: $url");
      }
      else
      {
          echo "<meta http-equiv=\"refresh\" content=\"0;url=$url\">\r\n";
      }
   }

// 부모 page의 redirct
   function opener_redirect( $url )
   {
?>
      <script language=javascript>
         opener.location.href = "<?= $url ?>";
      </script>
<?
   }

   function self_close()
   {
?>

      <script language=javascript>
         self.close()
      </script>
<?
   }


   function self_focus()
   {
?>

      <script language=javascript>
         self.focus()
      </script>
<?
   }

   // error 발생시 처리
   function error($script="예기치 않은 호출 발생")
   {
?>
<script language=javascript>
        alert("<?=$script?>");
        history.back()
</script>

<?
      exit;
   }

   function jsAlert( $text )
   {
?>

<script language=javascript>
        alert("<?=$text?>");
</script>

<?
   }


   // popup open
   function openwin($url, $windowid, $width, $height)
   {
?>

<script language=javascript>

  function openwin()
  {
     url = "<?= $url ?>";
     windowid = "<?= $windowid ?>";
     width = <?= $width ?>;
     height = <?= $height ?>;

     var wID;
     wID = window.open(url, windowid, "width=" + width + ",height=" + height + ",status=no,resizable=no,scrollbars=yes");
     wID.focus() 
     // XP Service Pack 2
     if (wID == null)
     {
       alert("팝업창을 열수 없습니다. XP Service Pack2인경우 팝업창을 허용하신후 다시 시도하시기 바랍니다.");
     }
  }
</script>

<?
   }

// array에 값 추가
function addArray(&$array, $key, $val) 
{ 
   $tempArray = array($key => $val); 
   $array = array_merge ($array, $tempArray); 
} 

//////////////////////////////////////////////////////
// validate출력 하는 function
   function validate($arr_items)
   {

?>

<script language=javascript>

function checkGif( obj )
{
	var length = obj.value.length
	var file_ext
	var start, end;
	
	start = length - 3;
	end = length
	file_ext = ""
	
	for( var i = start; i <= end; i++)
		file_ext = file_ext + obj.value.charAt(i)
	
	if(file_ext == "gif")
	{
		alert("이미지는 gif를 넣으실 수 없습니다.");
                obj.value = "";
                obj.focus();
		return false;
	}else{
		return true;
	}
}

function validate()
{

<?
        foreach($arr_items as $item=>$name)
        {
                echo"
                obj = eval(document.myform.$item)
                if(obj.value== '' || obj.value == 0){
                alert('" . $name . "은 반드시 입력하셔야 합니다')
                obj.focus()
                return false
        }
        ";
        }

?>      
}
</script>

<?
   }

}
?>
                      
