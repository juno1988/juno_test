<?
include_once "class_top.php";

class class_period extends class_top
{
   
    
    //****************************************
    //
    // class�� �����Ǹ� �� ��� ����Ǵ� �κ�
   
    function class_period()
    {
        global $connect, $from_date, $to_date, $shop_type;
        
        $_datas['list'] = array();
        $arr_idx        = array(); // date index
        $msg            = '';
       

        // �����ؾ��� data check
        $query = "select order_date
                    from orders
                   where order_date >= '$from_date' 
                     and order_date <= '$to_date'";
                     
       
        
        $query .= " group by order_date";
                     
        
            
        
    }
    
    //******************************************
    // ���� ��� ��������
    
    function period_list()
    {
        global $connect, $from_date, $to_date,$shop_type;
       
        //******************************************
        // ������ �� data�������� -shop������ ������ ������� ��� �Ǹ�ó �����ֱ�
        //******************************************
        if($shop_type!=''){
        $query = "select order_date,
                         shop_id,
                         product_id ,
                        
                         qty,
                         options,
                         product_name ,
                         shop_price
                    from orders
                    
                   where order_date >= '$from_date' 
                     and order_date <= '$to_date'
                     and shop_id=$shop_type";
        }else {  
        	$query = "select order_date,
                         shop_id,
                         product_id ,
                         qty,
                         options,
                         product_name ,
                         shop_price
                    from orders
                    
                   where order_date >= '$from_date' 
                     and order_date <= '$to_date'";
         }            
        $query .= " group by shop_product_id ";        
        $result = mysql_query ( $query, $connect );   
        
        //$this->debug( $query );        
        
        echo " in period_list $query";
        echo "\n-----------------\n";
        
        while ( $data = mysql_fetch_array( $result ) )
        { 
            
            $shop_name = $this->shop_info($data[shop_id]); //���θ� �̸� ��������
            
            $_datas['list'][] = array( 
                                   order_date       => $data[order_date], 
                                   shop_id          => $data[shop_id], 
                                   product_id       => $data[product_id],                                   
                                   product_name     => iconv( 'cp949', 'utf-8', $data[product_name] ),
                                   options          => iconv( 'cp949', 'utf-8', $data[options]),
                                   shop_name        =>iconv( 'cp949', 'utf-8', $shop_name) ,
                                   qty              => number_format($data[qty]),
                                   shop_price       => number_format($data[shop_price]),
                                   total_shop_price => number_format($total_shop_price)
                                ); 
        }
        
           
        $_datas['query']     = $query;
        $_datas['from_date'] = $from_date;
        $_datas['to_date']   = $to_date;
        $_datas['msg']       = $msg;        
        
        
        return $_datas;   
    }
    
    //shopinfo ���̺�κ��� ���θ� �̸��� ��������
    function shop_info($shop_id )
    {
        global $connect;   
        $query = "select shop_name 
                    from shopinfo
                    where shop_id =$shop_id";
                     
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_array( $result );
        
        return $data[shop_name];
    }
    
    function product_url($product_id )
    {
        global $connect;   
        $query = "select count(*)
                    from products
                    where product_id =$product_id";
                     
        $result = mysql_query( $query, $connect );
        $data   = mysql_fetch_array( $result );
        
        return $data[product_id];
    }
    
    
    
    
    
    
    
    
    
    
}
?>
