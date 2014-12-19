<?
/*****************************************
// category_combo.inc
// version 1.3
// last update date : 2004.5.25
// author jk.ryu

������ �۾� : 
	2004.5.25
		������ category�� �����ϴ°� ���� -> �Ұ�������
		List�� loading�Ǹ鼭 ���õ� ī�װ��� loading�ϰ�
		category ���ý� �������� �� ����(3���� ���ý� �� �ȵ�)
	2004.5.24
		���� category�� ������ �����ϰ� �ٲ�
		db�� category�� �����
	2004.5.17
		category_add()
		array_get_path() : path�� id, name�� ��� ���������� �Ѵ�.
	
History
	2004.5.25
		Loading�� multi_load($id)�ε����
		������ category������ �ȵ�
	2004.5.24
		����ī�װ��� �����
		save_category() �ϼ�
		table name = $this->table . "_product";�� ����ϸ� �ȴ�.
	
	2004.5.14 
		ī�װ� ������ ���� ����
		category_del() : �ϼ�
	2004.5.17
		category_select_all() �߰���. ������ ���õ� category�� �ѱ�� ���ؼ� �ݵ��� �־�� ��
		
*****************************************/

class item
{
	var $id, $name, $depth,$is_last,$step;
	
	function item($id, $name, $depth,$step)
	{
		$this->id = $id;
		$this->name = $name;	
		$this->depth = $depth;
		$this->step = $step;
		if($step == 0)
			$this->is_last = 1;
		else
			$this->is_last = 0;
	}
	
	function disp()
	{
		echo "id=> $this->id<br>";
		echo "name=>$this->name<br>";	
		echo "depth=>$this->depth<br>";	
		echo "step=>$this->step<br>";
		echo "is_last=>$this->is_last<br>---<br>";
	}
}

class category_combo
{	
	var $connect;
	var $maxDepth;
	var $table;
	var $formName;
	var $product_id;
	var $option_list;
	var $category_list;

   //////////////////////////////////
   // sub category�� list return
   function get_subcategory( &$count, &$key, &$index, $option1, $option2='', $option3='' )
   {
      global $connect;
      $id = $option1;
      $index = 1;
      if($option2)
      {      
         $id = $option2;
         $index = 2;
      }
      if($option3) 
      {
         $id = $option3;
         $index = 3;
      }
      
      $query = "select * from tbl_category where parent = '$id' order by id";
      $result = mysql_query( $query, $connect );
      $count = mysql_num_rows( $result );
    
      // subcategory�� ���� ��� ���� ī�װ��� data��� 
      if( !$count ) 
      {
         $index = $index - 1;
         $item= "option" . $index;
         $id = $$item;
         $query = "select * from tbl_category where parent = '$id' order by id";
         $result = mysql_query( $query, $connect );
         $count = mysql_num_rows( $result );
         $index = $index + 1;
      }else
         $index = $index + 1;
 
      $query = "select name from tbl_category where id='$id'";
      $result_key = mysql_query( $query, $connect );
      $data = mysql_fetch_array($result_key);
      $key = $data[name]; 

      return $result;
   }

   /////////////////////////////////////
   // page path���
   function disp_path( &$title, $option1, $option2, $option3 )
   {
      global $connect;
      global $page;
      $str = "";
      
      for ($i = 1; $i <= 3; $i++)
      {
         $var = "option" . $i;

         if ( !$$var )
           break;
 
         if($i != 1)
            echo " > ";

         $query = "select * from tbl_category where id='" . $$var . "' order by id";
         $result = mysql_query( $query, $connect ); 
         $data = mysql_fetch_array( $result );

         echo "<a href=?page=$page&";
         for( $j = 1; $j <= $i ; $j++)
         {
            $key = "option" . $j;
            echo $key . "=" . $$key . "&";
         } 
         echo ">$data[name]</a>";
         $title = $data[name];
      }
      
   }	
	// category combo�� submit�Ŀ��� ���� ������ ���� �� �ִ�.
	// option_list���� array�� �;� ��
	function category_combo($connect,$table,$formName,$id_field, $id, $option_list)
	{
		$this->connect = $connect;
		$this->table = $table;
		$this->formName = $formName;
		//$this->maxDepth = 2;
		
		$query = "select max(depth) as 	max_depth from $table";
//echo $query;	
		if($id_field != "")
		 	$query .= " where $id_field='$id'";	

		$result = mysql_query($query, $this->connect);
		$data = mysql_fetch_array($result);
		
		$this->maxDepth = $data[max_depth];
		$this->option_list = $option_list;
		
		$this->category_list = array();
	}	
	
	function disp_script_data_stock()
	{
		echo "
		<Script language=javascript>
		";
		
		// 1st option���
		$this->DispData(0, 1);
		
		for($i=2; $i <= $this->maxDepth; $i++)
		{
			$query = "select id 
				    from " . $this->table . " 
				   where depth='" . ($i-1) . "'";
			
			//echo $query . "\n";
			
			$result = mysql_query($query, $this->connect);
			while($data = mysql_fetch_array($result))
			{
				$this->DispData_stock($data[id], $i);	// id, depth
			}
		}
		
		echo "\n</Script>";
	}
	
	function DispData_stock($parent, $depth)
	{
		if ($depth == 1)
		{
			$variable = "option" . $depth;
		}else{
			$variable = "option" . $depth . "_" . $parent;			
		}
		
		echo "var " . $variable . "=new Array();\n";
		
		$query = "select id, name from " . $this->table . " 
		           where depth='$depth' 
		             and parent='$parent'
		             order by id";
		
		//echo $query . "\n";
		
		$result = mysql_query($query, $this->connect);
		
		$i = 0;
		while($data = mysql_fetch_array($result))
		{
			echo $variable . "[$i] = new Option('$data[name]', '$data[id]')\n";
			$i++;
		}
		echo "\n";
	}
	function disp_script_data()
	{
		echo "
		<Script language=javascript>
		";
		
		// 1st option���
		$this->DispData(0, 1);
		
		for($i=2; $i <= $this->maxDepth; $i++)
		{
			$query = "select id 
				    from " . $this->table . " 
				   where depth='" . ($i-1) . "'";
			
			//echo $query . "\n";
			
			$result = mysql_query($query, $this->connect);
			while($data = mysql_fetch_array($result))
			{
				$this->DispData($data[id], $i);	// id, depth
			}
		}
		
		echo "\n</Script>";
	}
	
	function DispData($parent, $depth)
	{
		if ($depth == 1)
		{
			$variable = "option" . $depth;
		}else{
			$variable = "option" . $depth . "_" . $parent;			
		}
		
		echo "var " . $variable . "=new Array();\n";
		
		$query = "select id, name from " . $this->table . " 
		           where depth='$depth' 
		             and parent='$parent'
		             order by id";
		
		//echo $query . "\n";
		
		$result = mysql_query($query, $this->connect);
		
		$i = 0;
		while($data = mysql_fetch_array($result))
		{
			echo $variable . "[$i] = new Option('$data[name]', '$data[id]')\n";
			$i++;
		}
		echo "\n";
	}
	
	function disp_script_engine()
	{
		if($this->maxDepth == "")
			$option_count = 0;
		else
			$option_count = $this->maxDepth;
		
		echo "
		<script language=javascript>		
		
		option_count=$option_count;
		
		function populate_wrap(x){
			populate(x)
			
			var depthobj = eval('document.". $this->formName. ".depth')	// 1��° option field
			depthobj.value = x;
			//alert(x)	
			for(j=x + 1;j<=option_count;j++)
			{
				populate(j)
			}
		}
		
		function populate(x){
		
			var cacheobj= eval('document.". $this->formName. ".option' + x)	// 1��° option field
			
	
			for (m=cacheobj.options.length-1;m>0;m--)
				cacheobj.options[m]=null
			
			if(x == 1)
				selectedarray=eval('option' + x)
			else
			{
				i = x - 1
				x_obj = eval('document.". $this->formName. ".option' + i)	// 1��° option field		
				
				// ���� ���� ��� exit
				if(x_obj.value == '0')
					return;
					
				obj = 'option' + x + '_' + x_obj.value
				selectedarray=eval(obj)
			}
			
			for (i=1;i<=selectedarray.length;i++)
			{
				cacheobj.options[i]=new Option(selectedarray[i-1].text,selectedarray[i-1].value)
			}
			
			cacheobj.options[0].selected=true
			/*	
			if(cacheobj.length == 1)
			{				
				var button_obj = document.". $this->formName. ".add_button
				button_obj.disabled = false
			}else{
				
				var button_obj = document.". $this->formName. ".add_button
				button_obj.disabled = true
			}
			*/
		}
		
		// ��� ���� ����
		// category_combo������ ������� �ʴ´�.
                
		function set_id(depth)
		{
			//x_obj = eval('document.". $this->formName. ".option' + depth)	// 1��° option field						
			//document.". $this->formName. ".option_id.value = x_obj.value
			//var button_obj = document.". $this->formName. ".add_button
			
			//button_obj.disabled = false
		}
		

		function category_del()
		{
			//list_obj = document.". $this->formName. ".categoryList
			list_obj = document.". $this->formName. ".elements['categoryList[]']
			
			selIdx=list_obj.options.selectedIndex;	
			
			// selIdx�� -1 �̸� ���õȰ� ����			
			//alert(selIdx)	
			
			removeMenu(list_obj,selIdx)
		}
		
		function removeMenu(target, index)
		{
			if (index < 0)
				return;
				
			target.remove(index);
			if(index > 0) target.selectedIndex = index - 1;
			else if(index == 0 && target.length > 1) target.selectedIndex = 0;
		}
		
		function category_add()
		{	
			depth = eval('document.". $this->formName. ".depth.value')
		
			x_obj = eval('document.". $this->formName. ".option' + depth)
			selIdx=x_obj.options.selectedIndex;
				
			cat_id=x_obj.options[selIdx].value;
			cat_text=x_obj.options[selIdx].text;
			
			// 2004.5.24 add by jk
			// ���� category�� ���� ���� ���
			var string = ''
			if(cat_id == 0)
			{
				depth = depth - 1;
				x_obj = eval('document.". $this->formName. ".option' + depth)
				selIdx=x_obj.options.selectedIndex;
				
				cat_id=x_obj.options[selIdx].value;
				cat_text=x_obj.options[selIdx].text;

				for(i=1;i<depth;i++)
                                {
                                        var obj = eval('document.".$this->formName.".option' + i)

                                        var sel = obj.options.selectedIndex

                                        string += obj.options[sel].text + '>'
                                }

			}else{
				for(i=1;i<depth;i++)
				{
					var obj = eval('document.".$this->formName.".option' + i)
					
					var sel = obj.options.selectedIndex
					
					string += obj.options[sel].text + '>'
				}	
			}
			
			list_obj = document.". $this->formName. ".elements['categoryList[]']

			if(!is_exist(cat_id))
			{
				index = list_obj.length			
				list_obj.length = index+1;
				list_obj.options[index].value = cat_id
				list_obj.options[index].text =  string + cat_text
			}
		}
		
		function is_exist(id)
		{
			list_obj = document.".$this->formName.".elements['categoryList[]']
			
			for(var i=0; i<list_obj.length; i++)
			{
				value = list_obj.options[i].value
				if(id == value)
					return true
			}
			return false
		}	
	function category_select_all()
	{
		list_obj = document.". $this->formName. ".elements['categoryList[]']
		
		for(var i=0; i<list_obj.length; i++) {
			list_obj.options[i].selected = true;
		}				
	}
	
	function select_combo(x){
		var cacheobj= eval('document.". $this->formName. ".option' + x)	// 1��° option field
		
		if(x == 1)
			selectedarray=eval('option' + x)
		else
		{
			i = x - 1
			x_obj = eval('document.". $this->formName. ".option' + i)	// 1��° option field		
			
			// ���� ���� ��� exit
			if(x_obj.value == '0')
				return;
				
			obj = 'option' + x + '_' + x_obj.value
			selectedarray=eval(obj)
		}

		for (i=1;i<=selectedarray.length;i++)
		{
		";							
			for($i=1; $i<=$this->maxDepth;$i++)
			{
				echo "					
				// $i option ó�� �κ�
				if(x == $i)
				{";
					
					if($this->option_list[$i-1] != "")
					{
						// �� �κп��� ���� ���� ��찡 ���� ���� - error����
						echo "
						if('". $this->option_list[$i-1] . "' == selectedarray[i-1].value)				
						{
							cacheobj.options[i].selected=true
							if(x == 1)
								populate_wrap(2)
						}
						";
					}
				echo"
				}
				";	
			}
			
		echo "			
		}
		
	}
		
	if(option_count > 0)
		populate_wrap(1);		
	
	// select ó��
	";
	
	for($i=1; $i<=$this->maxDepth;$i++)
	{
		echo "select_combo($i)\n";
	}
	
	echo"	
	
	</script>\n";
}
// db�� category������ categoryList�� ����Ѵ�.
function multi_load($product_id)
{
	$query = "select a.id, a.name
                    from " . $this->table . " a," . $this->table . "_product b
                   where a.id = b.cid
                     and b.is_last = 1
                     and b.pid = '$product_id'";
	
	$result = mysql_query($query, $this->connect);

echo "

<Script language=javascript>
	
	list_obj = document.". $this->formName. ".elements['categoryList[]']
	
";	
	while($data = mysql_fetch_array($result))
	{
		$string = $this->get_path_array($data[id]);	
		echo "

		index = list_obj.length			
		list_obj.length = index+1;
		list_obj.options[index].value = '$data[id]'
		list_obj.options[index].text = '$string > $data[name]'
		
		";
	}

echo "
</Script>";

}
function multi()
{
	echo"
	<select name=categoryList[] size=3 style=font-size=9pt multiple style=width:400>
		</select>
		<input type=button name=add_button disabled=true onClick=javascript:category_add() value=' �߰� '>
		<input type=button onClick=javascript:category_del() value=' ���� '>
		";
	}
	
	function create_combo()
	{
		echo "
		<input type=hidden name=depth>
		";
		
		for($i=1; $i<=$this->maxDepth;$i++)
			$this->combo_disp($i);
	}
	
	function disp_path_array($id)
	{
		$arr_path = array();
				
		$this->get_path($id, &$arr_path);
		$string = "";
		for($i=count($arr_path); $i>0;$i--)
		{
			echo $arr_path[($i-1)];
			$string .= $arr_path[($i-1)];
			if($i != 1)
			{
				echo ", ";
				$string .= ",";
			}
		}
		return $string;
	}
	
	function get_path_array($id)
	{
		$arr_path = array();
				
		$this->get_path($id, &$arr_path);
		$string = "";
		for($i=count($arr_path); $i>0;$i--)
		{
			$string .= $arr_path[($i-1)];
			if($i != 1)
			{
				$string .= ">";
			}
		}
		return $string;
	}
	
	// category�� db�� ����
	function save_category($categoryList, $product_id)
	{		
		// ���� ���� ��� ������ �����ؾ� ��
		$query = "delete from " .$this->table . "_product where pid='$product_id'";
		mysql_query($query, $this->connect);

		for($i=0;$i<count($categoryList);$i++) {
			$this->array_get_path($categoryList[$i],0);
		}
		
		foreach ($this->category_list as $c)
		{
			//$c->disp();
			// id, name, depth	
			$query = "insert into " . $this->table . "_product set cid='" . $c->id . "', pid='" . $product_id . "',depth='".$c->depth."', is_last='" .$c->is_last ."'";
			echo $query;	
			echo "<br>";
			mysql_query($query, $this->connect);
		}
	}
	
	// �ϳ��� ��ǰ�� ���� ��� category�� ������ �� �ִ�.
	function array_get_path($id,$step)
	{
		$query = "select * from " . $this->table . " where id='$id'";
		$result = mysql_query($query, $this->connect);
		$data = mysql_fetch_array($result);	
		
		$obj = new item($data[id], $data[name], $data[depth],$step);
		
		$step = $step + 1;
		$is_exist = 0;
		foreach ($this->category_list as $c)
		{
			if($c->id == $data[id])
				$is_exist = 1;
		}
		
		if(!$is_exist)
			array_push($this->category_list, $obj);
		
		if($data[depth] != 1)
		{
			$this->array_get_path($data[parent],$step);
		}
	}
	
	function get_path($id, &$arr_path)
	{
		$query = "select * from " . $this->table . " where id='$id'";
		$result = mysql_query($query, $this->connect);
		$data = mysql_fetch_array($result);	
			
		array_push($arr_path, $data[name]);
		
		if($data[depth] != 1)
		{
			$this->get_path($data[parent], &$arr_path);
		}
	}
	
	function combo_disp($depth)
	{
		if($depth != $this->maxDepth)
			$index_string = "onchange=populate_wrap(" . ($depth+1) .")";
		else
			$index_string = "onchange=set_id($depth)";

		if ($depth==1) $category_title = "��з�";
		else if ($depth==2) $category_title = "�� �з�";

		echo "
		<select name=option".$depth." $index_string style=width:120>
			<option value=0>$category_title</option>
		</select>
		";	
	}
}
?>
