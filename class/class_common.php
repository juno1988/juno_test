<?
include_once "class_top.php";

class class_common extends class_top
{
    // test
    function order_test()
    {
        $_datas = array();        
        $_datas['list'] = array();
        $_datas['list'][] = array( shop_id => "1", shop_name=> "aaa" ); 
        $_datas['list'][] = array( shop_id => "1", shop_name=> "bb" ); 
        $_datas['list'][] = array( shop_id => "1", shop_name=> "cc" ); 
        $_datas['list'][] = array( shop_id => "1", shop_name=> "dd" ); 
        $_datas['list'][] = array( shop_id => "1", shop_name=> "ee" ); 
        echo json_encode( $_datas );     
    }
    
    // 환경 설정 
    function set_config()
    {
        global $connect;
        $items = array( "pre_deliv_price", "back_deliv_price","supply_deliv_price","cancel_option" );
        
        foreach ( $items as $key )
        {
            global $$key;
            $query  = "delete from stat_config where code='$key'";
            $result = mysql_query( $query, $connect );
            
            $query = "insert stat_config set code='$key', value='" . $$key . "'";
            mysql_query( $query, $connect );
        }
    }
    
    //=============================
    // 환경 설정값 가져오기
    function get_config( $is_ret = 0)
    {
        global $connect;
        
        $_datas = array();
        $query = "select * from stat_config";
        $result = mysql_query ( $query, $connect );
        while ( $data = mysql_fetch_array( $result ) )
        {
            $_datas[ $data['code'] ] = $data['value'];               
        }

	if ( $is_ret )
	    return $_datas;
	else
            echo json_encode( $_datas );
    }
    
    // shop list
    function get_shop_list()
    {
        global $connect;
        
        $_datas = array();        
        $_datas['list'] = array();
        
        $query = "select * from shopinfo order by shop_name";
        $result = mysql_query( $query, $connect );
        
        $_datas['list'][] = array( shop_id => "", shop_name=>  iconv('cp949', 'utf-8', "판매처" ) );
         
        while ( $data = mysql_fetch_array( $result ) )
        {
            $_datas['list'][] = array( shop_id => $data['shop_id'] , shop_name=> iconv('cp949', 'utf-8', $data['shop_name'] )); 
        }
        
        echo json_encode( $_datas );        
    }
    
    // chart data test
    function chart()
    {
?>
<chart caption='재고' yAxisName='수량' bgColor='F7F7F7, E9E9E9' showValues='0' numVDivLines='10' divLineAlpha='30'  labelPadding ='10' yAxisValuesPadding ='10'>
 <categories><category label='2008-10-20' />
 <category label='2008-10-21' />
 <category label='2008-10-22' />
 <category label='2008-10-23' />
 <category label='2008-10-24' />
 <category label='2008-10-25' />
 <category label='2008-10-26' />
 <category label='2008-10-27' />
 </categories>
 <dataset seriesName='재고' color='A66EDD' >
<set value='0' />
 <set value='1' />
 <set value='2' />
 <set value='3' />
 <set value='4' />
 <set value='5' />
 <set value='6' />
 <set value='7' />
 </dataset>
<dataset seriesName='배송' color='FF0000'>
<set value='0' />
 <set value='2' />
 <set value='0' />
 <set value='2' />
 <set value='0' />
 <set value='2' />
 <set value='0' />
 <set value='2' />
 </dataset>
<dataset seriesName='입고' color='F99998'>
<set value='7' />
 <set value='6' />
 <set value='5' />
 <set value='4' />
 <set value='3' />
 <set value='2' />
 <set value='1' />
 <set value='0' />
 </dataset>
<dataset seriesName='미배송 합계' color='F6BD0F'>
<set value='0' />
 <set value='0' />
 <set value='0' />
 <set value='0' />
 <set value='0' />
 <set value='0' />
 <set value='0' />
 <set value='0' />
 </dataset>
<dataset id='common' seriesName='common' tot_stockin='0' tot_deliv='0'></dataset></chart>
<?
    }
}
?>
