<?
require_once "class_B.php";
require_once "class_top.php";
require_once "class_combo_admin.php";

////////////////////////////////
// class name: class_B600
//
class class_B600 extends class_top {
   var $table = "tbl_category";
   var $category_list;

   ///////////////////////////////////////////
   function B600()
   {
	global $template;
	global $connect, $action, $category, $category2, $category3, $category4, $id, $mod_id, $mod_text;

	////////////////////////////////////////////////////////
	// parameter: table name
        $this->table = "tbl_category";	
	$this->get_pathinfo($id);
	//$obj_category->disp_pathinfo();

	$master_code = substr( $template, 0,1);
	include "template/" . $master_code ."/" . $template . ".htm";
   }

//////////////////////////////////////////////////////////
// class
        function class_combo_admin($dbh,$table)
	{
		$this->dbh = $dbh;
		$this->table = $table;
		$this->category_list = array();
	}	

        function add_c1()
        {
           global $category, $parent;
           $this->add_category1($category); 
           $this->redirect( "?template=B600&id=$parent" );
           exit;
        }
	
        function add_c2()
        {
           global $category, $parent;
           $this->add_category($category, 2, $parent); 
           $this->redirect( "?template=B600&id=$parent" );
           exit;
        }

        function add_c3()
        {
           global $category, $parent;
           $this->add_category($category, 3, $parent); 
           $this->redirect( "?template=B600&id=$parent" );
           exit;
        }

        function add_c4()
        {
           global $category, $parent;
           $this->add_category($category, 4, $parent); 
           $this->redirect( "?template=B600&id=$parent" );
           exit;
        }

        function del()
        {
           global $id, $parent;
           $this->del_category($id);
           $this->redirect( "?template=B600&id=$parent" );
        }

        function mod()
        {
           global $mod_id, $mod_text, $parent;
           $this->mod_category($mod_id, $mod_text);
           $this->redirect( "?template=B600&id=$parent" );
        }
	
	function get_pathinfo($id)
	{
                global $connect;
		$query = "select * from ". $this->table . " where id='$id' order by id";		
		$result = mysql_query($query, $connect);		
		$list = mysql_fetch_array($result);	
		
		$depth = $list[depth];		
		$this->category_list[$depth] = array("id"=>$list[id], "name"=>$list[name], "depth"=>$list[depth]);
		$id = $list[parent];

		while($depth > 0)
		{			
			$depth--;
			$query = "select * from ". $this->table . " where id='$id' order by id";		
			$result = mysql_query($query, $connect);		
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
		global $id, $connect;
		$query = "insert into ". $this->table . " set id='$id', name='$name', parent='0', depth='1'";
		mysql_query($query, $connect);
	}
	
	function add_category($name, $depth, $parent)
	{
		global $id, $connect;
		$query = "insert into ". $this->table . " set id='$id',name='$name', parent='$parent', depth='$depth'";
		mysql_query($query, $connect);
	}
	
	function mod_category($mod_id, $name)
	{
		global $mod_org_id, $connect;
		$query = "update ". $this->table . " set id='$mod_id', name='$name' where id='$mod_org_id'";
		mysql_query($query, $connect);
	}	
	
	function disp_category1()
	{
                global $connect;
		$query = "select * from ". $this->table . " where depth='1' order by id";
		
		$result = mysql_query($query, $connect);
		
		while($list = mysql_fetch_array($result))
		{
			if($this->category_list[1]["id"] == $list[id])
				$bgcolor="cccccc";
			else
				$bgcolor="f7f7f7";
				
			$del_link = "?template=B600&action=del&id=$list[id]&parent=$list[id]";
			$exp_link = "?template=B600&id=$list[id]&parent=$list[id]";
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
                global $connect;
		$query = "select * from ". $this->table . " where parent='$id' and depth='$depth' order by id";
		$result = mysql_query($query, $connect);
		
		while($list = mysql_fetch_array($result))
		{
			if($this->category_list[$depth]["id"] == $list[id])
				$bgcolor="cccccc";
			else
				$bgcolor="f7f7f7";
					
			$del_link = "?template=B600&action=del&id=$list[id]&parent=$id";
			$exp_link = "?template=B600&id=$list[id]&parent=$id";
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
                global $connect;

                // 하부 category 삭제
		$query = "delete from ". $this->table . " where parent='$id'";
		$result = mysql_query($query, $connect);		

                // this 삭제
		$query = "delete from ". $this->table . " where id='$id'";		
		mysql_query($query, $connect );	
		
	}
}


