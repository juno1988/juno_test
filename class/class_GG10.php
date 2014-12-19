<?
////////////////////////////////
// class name: class_GG00
// date: 2007.7.24
// jk.ryu

require_once "class_top.php";
require_once "class_G.php";

class class_GG11 extends class_top {


  //===========================================
  // 
  // query 내용 조회
  // date: 2007.7.24
  // jk.ryu

  // gr작업
  function requestData2()
  {
	echo "<mxl version=\"1.0\" encoding=\"euc-kr\">
	


	<recordset>
        	<travelpackage>
                	<country_name>cuba</country_name>
	                <city>cayo coco</city>
	                <resort>club tryp cayo coco</resort>
	                <resort_rating>4</resort_rating>
	                <resort_typeofholiday>bench</resort_typeofholiday>
	                <resort_watersports>true</resort_watersports>
	                <resort_meals>true</resort_meals>
	                <resort_drinks>true</resort_drinks>
	                <package>
	                        <package_dateofdep>5/8/98</package_dateofdep>
	                        <package_price>879</package_price>
	                </package>
	        </travelpackage>
	        <travelpackage>
	                <country_name>cubfdsa</country_name>
	                <city>cayo cofdsaco</city>
	                <resort>club trypdsfa cayo coco</resort>
	                <resort_rating>3</resort_rating>
	                <resort_typeofholiday>bfdsaench</resort_typeofholiday>
	                <resort_watersports>true</resort_watersports>
	                <resort_meals>true</resort_meals>
	                <resort_drinks>true</resort_drinks>
	                <package>
	                        <package_dateofdep>5/8/99</package_dateofdep>
	                        <package_price>678</package_price>
	                </package>
	        </travelpackage>
	</recordset>

";

  }

  
}

?>
