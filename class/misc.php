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

// �θ� page�� redirct
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

   // error �߻��� ó��
   function error($script="����ġ ���� ȣ�� �߻�")
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
       alert("�˾�â�� ���� �����ϴ�. XP Service Pack2�ΰ�� �˾�â�� ����Ͻ��� �ٽ� �õ��Ͻñ� �ٶ��ϴ�.");
     }
  }
</script>

<?
   }

// array�� �� �߰�
function addArray(&$array, $key, $val) 
{ 
   $tempArray = array($key => $val); 
   $array = array_merge ($array, $tempArray); 
} 

//////////////////////////////////////////////////////
// validate��� �ϴ� function
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
		alert("�̹����� gif�� ������ �� �����ϴ�.");
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
                alert('" . $name . "�� �ݵ�� �Է��ϼž� �մϴ�')
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
                      
