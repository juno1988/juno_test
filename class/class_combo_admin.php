<?	// class combo 를 위한 관리자 기능
	// class_combo_admin.php
	// date : 2004.3.15

class class_combo_admin{
	var $dbh;
	var $table;
	var $category_list;
	
	function class_combo_admin($dbh,$table)
	{
		$this->dbh = $dbh;
		$this->table = $table;
		$this->category_list = array();
	}	
	
	function switch_action($action, $category,$category2,$category3,$category4,$id, $mod_id, $mod_text)
	{
		global $id,$parent;

		switch ($action){
			case "add_c1" :
				$this->add_category1($category); 
				echo "<meta http-equiv='Refresh' content='0; URL=?id=$parent'>";
				exit;
			break;
			case "add_c2" :
				$this->add_category($category,2,$parent);
				echo "<meta http-equiv='Refresh' content='0; URL=?id=$parent'>";
				exit;
			break;
			case "add_c3" :
				$this->add_category($category,3,$parent);
				echo "<meta http-equiv='Refresh' content='0; URL=?id=$parent'>";
				exit;
			break;
			case "add_c4" :
				$this->add_category($category,4,$parent);
				echo "<meta http-equiv='Refresh' content='0; URL=?id=$parent'>";
				exit;
			break;
			case "del" :
				$this->del_category($id);
			break;	
			case "mod" :
				//echo "$mod_id / $mod_text";
				$this->mod_category($mod_id, $mod_text);
				echo "<meta http-equiv='Refresh' content='0; URL=?id=$parent'>";
				exit;
			break;
			
		}	
	}
	
	function get_pathinfo($id)
	{
		$query = "select * from ". $this->table . " where id='$id' order by id";		
		$result = mysql_query($query, $this->dbh);		
		$list = mysql_fetch_array($result);	
		
		$depth = $list[depth];		
		$this->category_list[$depth] = array("id"=>$list[id], "name"=>$list[name], "depth"=>$list[depth]);
		$id = $list[parent];

		while($depth > 0)
		{			
			$depth--;
			$query = "select * from ". $this->table . " where id='$id' order by id";		
			$result = mysql_query($query, $this->dbh);		
			$list = mysql_fetch_array($result);		
			$this->category_list[$depth] = array("id"=>$list[id], "name"=>$list[name], "depth"=>$list[depth]);
			$id = $list[parent];
		}
	}
	
	function get_max()
	{
		return count($this->category_list);
	}
	
	function get_category($depth)
	{
		return $this->category_list[$depth]["id"];
	}
	
	function disp_pathinfo()
	{

		for($i=1; $i < count($this->category_list); $i++)
		{
			$category = $this->category_list[$i];
			echo $category[name];
			
			if($i != count($this->category_list) - 1)
				echo " > ";
		}
	}
		
	function add_category1($name)
	{
		global $id;
		$query = "insert into ". $this->table . " set id='$id', name='$name', parent='0', depth='1'";
		mysql_query($query, $this->dbh);
	}
	
	function add_category($name, $depth, $parent)
	{
		global $id;
		$query = "insert into ". $this->table . " set id='$id',name='$name', parent='$parent', depth='$depth'";
		mysql_query($query, $this->dbh);
	}
	
	function mod_category($mod_id, $name)
	{
		global $mod_org_id;
		$query = "update ". $this->table . " set id='$mod_id', name='$name' where id='$mod_org_id'";
		mysql_query($query, $this->dbh);
	}	
	
	function disp_category1()
	{
		$query = "select * from ". $this->table . " where depth='1' order by id";
		
		$result = mysql_query($query, $this->dbh);
		
		while($list = mysql_fetch_array($result))
		{
			if($this->category_list[1]["id"] == $list[id])
				$bgcolor="cccccc";
			else
				$bgcolor="f7f7f7";
				
			$del_link = "?action=del&id=$list[id]";
			$exp_link = "?id=$list[id]";
			$mod_link = "javascript:mod(1, '$list[name]', '$list[id]')";
			echo "<tr>";
			echo "<td bgcolor=$bgcolor>[" . $list[id] . "]<a href=$exp_link>". $list[name] . "</a> <a href=$del_link><img src=images/d.gif border=0></a>
			<a href=\"$mod_link\"><img src=images/m.gif border=0></a>
			</td>";
			echo "</tr>";
		}
	}
	
	function disp_category($id, $depth)
	{
		$query = "select * from ". $this->table . " where parent='$id' and depth='$depth' order by id";
		
		$result = mysql_query($query, $this->dbh);
		
		while($list = mysql_fetch_array($result))
		{
			if($this->category_list[$depth]["id"] == $list[id])
				$bgcolor="cccccc";
			else
				$bgcolor="f7f7f7";
					
			$del_link = "?action=del&id=$list[id]";
			$exp_link = "?id=$list[id]";
			$mod_link = "javascript:mod($depth, '$list[name]', '$list[id]')";
			echo "<tr>";
			echo "<td bgcolor=$bgcolor>[" . $list[id] . "]<a href=$exp_link>". $list[name] . "</a> <a href=$del_link><img src=images/d.gif border=0></a>
			<a href=\"$mod_link\"><img src=images/m.gif border=0></a>
			</td>";
			echo "</tr>";
		}
	}
	function del_category($id)
	{
		$query = "select id from ". $this->table . " where parent='$id'";
		$result = mysql_query($query, $this->dbh);		
		$query = "delete from ". $this->table . " where id='$id'";		
		mysql_query($query, $this->dbh);	
		
		while($list = mysql_fetch_array($result))
		{
			$id = $list[id];
			$this->del_category($id);
		}
		
		
	}
}


function print_methods($obj) 
{
   $arr = get_class_methods(get_class($obj));
   foreach ($arr as $method)
       echo "\tfunction $method()\n";
}


function print_vars($obj) 
{
   $arr = get_object_vars($obj);
   while (list($prop, $val) = each($arr))
       echo "\t$prop = $val<br>";
}

?>
