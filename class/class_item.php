<?
include_once "class_top.php";

class class_item extends class_top
{
   
    
    //****************************************
    //
    // class�� �����Ǹ� �� ��� ����Ǵ� �κ�
    // stat_product�� stat_shop�� ������ ����
    // 2008.10.30 - jk
    //
    function class_item()
    {
        global $connect, $from_date, $to_date;
        
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
    // 
    function item_list()
    {
        global $connect, $from_date, $to_date, $date_type;
        
        //******************************************
        // ������ �� data��������
        //******************************************
        $query = "select order_date,
                         shop_product_id ,
                         qty,
                         options,
                         product_name ,
                         order_time,
                         shop_price
                    from orders
                   where order_date >= '$from_date' 
                     and order_date <= '$to_date'";
        
        $query .= " group by shop_product_id ";        
        $result = mysql_query ( $query, $connect );   
        
        $this->debug( $query );        
        
        while ( $data = mysql_fetch_array( $result ) )
        { 
            
            
            $_datas['list'][] = array( 
                                    order_date            => $data[order_date], 
                                    order_time            => $data[order_time],
                                    shop_name          => $data[shop_name],
                                   shop_product_id         => $data[shop_product_id],
                                   product_name        => iconv( 'cp949', 'utf-8', $data[product_name] ),
                                   
                                    qty   => number_format($data[qty]),
                                    shop_price      => number_format($data[shop_price]),
                                    
                                   
                                ); 
        }
        
        $_datas['query']     = $query;
        $_datas['from_date'] = $from_date;
        $_datas['to_date']   = $to_date;
        $_datas['msg']       = $msg;        
        
        return $_datas;   
    }
    

    
    
    
    
    
    
    
    
    
    
    
    
}
?>
