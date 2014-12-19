<?
//==========================================
// 
// 화면 출력에 관한 class
// date: 2007.11.21 - jk

class class_ui
{
    
    function disp_trans_icon( $status )
    {
        
        switch ( $status )
        {
            // 송장
            case 7:
                $_icons = "icon_06";
            break;
            // 배송
            case 8:
                $_icons = "icon_04";
            break;
            // 접수
            default:
                $_icons = "icon_05";
                            
        }
        echo "<img src=http://premium.ezadmin.co.kr/images/" . $_icons . ".gif>";
    }
    
    function disp_cs_icon( $order_cs )
    {
        
        switch ( $order_cs )
        {
            // 취소
            case 1:
            case 2:
            case 3:
            case 4:
                $_icons = "icon_01";
            break;
            // 교환
            case 5:
            case 6:
            case 7:
            case 8:
                $_icons = "icon_03";
            break;
            // 접수
            default:
                $_icons = "icon_02";
        }
        echo "<img src=http://premium.ezadmin.co.kr/images/" . $_icons . ".gif>";
    }
    
    //==============================================
    // 
    // date: 2009.4.27 - jk
    function combo_shopid( $shop_id='', $command='' )
    {
        global $connect;

        $query = "select * from shopinfo where disable=0 order by disable, sort_name";
        $result = mysql_query ( $query, $connect ) or die( mysql_error() );
        echo "<select name='shop_id2' id='shop_id2' $command><option value=''";
        if ( !isset($shop_id) ) echo " selected ";
        echo ">판매처</option>";
                
        while ( $data = mysql_fetch_array ( $result ) )
        {
            echo "<option value='$data[shop_id]'";
            if ( $shop_id == $data[shop_id] ) echo " selected";
            echo ">$data[shop_name] ($data[shop_id])";
            echo "</option>";        
         }        
        echo "</select>";
    }
    
    //==============================================
    // 
    // date: 2012.1.20 - jk
    function combo_supplygroup( $code='', $command='' )
    {
        global $connect;

        $query = "select * from supply_group order by name";
        $result = mysql_query ( $query, $connect ) or die( mysql_error() );
        echo "<select name='s_group_id' id='s_group_id' $command><option value=''";
        if ( !isset($code) ) 
        {
            // 2014-09-11 장경희. 
            if( _DOMAIN_ == 'maru' )
                $code = 17;
            else
                echo " selected ";
        }
        echo ">전체그룹</option>";
                
        while ( $data = mysql_fetch_array ( $result ) )
        {
            echo "<option value='$data[group_id]'";
            if ( $code == $data[group_id] ) echo " selected";
            echo ">$data[name]";
            echo "</option>";        
         }        
        echo "</select>";
    }

    //==============================================
    // 
    // date: 2012.1.20 - jkh
    function combo_supplygroup2( $code='', $command='' )
    {
        global $connect;

        $query = "select * from supply_group order by name";
        $result = mysql_query ( $query, $connect ) or die( mysql_error() );
        echo "<select name='supply_group' ><option value=0";
        if ( !isset($code) ) echo " selected ";
        echo ">전체</option>";
                
        while ( $data = mysql_fetch_array ( $result ) )
        {
            echo "<option value='$data[group_id]'";
            if ( $code == $data[group_id] ) echo " selected";
            echo ">$data[name]";
            echo "</option>";        
         }        
        echo "</select>";
    }

    //==============================================
    // 
    // date: 2008.10.8 - jk
    function combo_shopgroup( $code='', $command='' )
    {
        global $connect;

        $query = "select * from shop_group";
        $result = mysql_query ( $query, $connect ) or die( mysql_error() );
        echo "<select name='group_id' id='group_id' $command><option value=''";
        if ( !isset($code) ) echo " selected ";
        echo ">판매처그룹</option>";
                
        while ( $data = mysql_fetch_array ( $result ) )
        {
            echo "<option value='$data[group_id]'";
            if ( $code == $data[group_id] ) echo " selected";
            echo ">$data[name]";
            echo "</option>";        
         }        
        echo "</select>";
    }

    function combo_shopgroup2( $code='', $command='' )
    {
        global $connect;

        $query = "select * from shop_group";
        $result = mysql_query ( $query, $connect ) or die( mysql_error() );
        echo "<select name='group_id' id='group_id' class='select20' style='width:130px' $command><option value=''";
        if ( !isset($code) ) echo " selected ";
        echo ">전체</option>";
                
        while ( $data = mysql_fetch_array ( $result ) )
        {
            echo "<option value='$data[group_id]'";
            if ( $code == $data[group_id] ) echo " selected";
            echo ">$data[name]";
            echo "</option>";        
         }        
        echo "</select>";
    }

    function disp_abc( $name, $value=''  )
    {
        if ( $value )
            $sel[ $value ] = "selected";

?>
        <select id="<?= $name?>"  name="<?= $name ?>">
                <option value='0'>값 없음</option>
                <?
                $j = 1;
                for ( $i=ord('A'); $i <= ord('Z'); $i++ )
                {
                ?>
                  <option value="<?= $j ?>" <?= $sel[$j] ?>><?= chr($i) ?></option>
                <?
                    $j++;
                } ?>
                <?
                for ( $i=ord('A'); $i <= ord('Z'); $i++ )
                {
                ?>
                  <option value="<?= $j ?>" <?= $sel[$j] ?>>A<?= chr($i) ?></option>
                <?
                    $j++;
                } ?>

        </select>
<?
    }

    function text( $text )
    {
        echo "text: $text <br>";
    }
    //==============================================
    // icon 출력
    // date: 2007.11.21 - jk
    //
    function disp_icon( $switch, $value )
    {
        $_img = "";
        switch ( $switch )
        {
            case "use_3pl":    
                if ( $value )
                    $_img = "bul_memo.gif";        
                break;
            case "stock_manage":
                if ( $value )
                    $_img = "bul_reply.gif";
                    break;
            case "enable_sale":   
                if ( !$value )
                    $_img = "icon_soldout.gif";    
                break;
        }

        if ( $_img )
            echo "<img src='images/$_img'>";
    }

    //==============================================
    // 
    // date: 2008.10.16 - jk
    //
    function combo_supply( $code='' )
    {
        global $connect;

        $query = "select * from userinfo where level=0 order by name";
        $result = mysql_query ( $query, $connect ) or die( mysql_error() );

        echo "<select name='supply_id' id='supply_id'><option";
        if ( !isset($code) ) echo " selected ";
        echo ">공급처</option>";
                
        while ( $data = mysql_fetch_array ( $result ) )
        {
            echo "<option value='$data[code]'";
            if ( $code == $data[code] ) echo " selected";
            echo ">$data[name]";
            echo "</option>";        
         }        
        echo "</select>";
    }

    //==============================================
    // 
    // date: 2008.4.23 - jk
    function combo_promotion( $code='' )
    {
        global $connect;

        $query = "select * from userinfo where level=6";
        $result = mysql_query ( $query, $connect ) or die( mysql_error() );
        echo "<select name='promotion_id'><option";
        if ( !isset($code) ) echo " selected ";
        echo ">없음</option>";
                
        while ( $data = mysql_fetch_array ( $result ) )
        {
            echo "<option value='$data[id]'";
            if ( $code == $data[id] ) echo " selected";
            echo ">$data[name]";
            echo "</option>";        
         }        
        echo "</select>";
    }

    //==============================================
    // 
    // date: 2008.4.23 - jk
    function combo_trans_code( $code='' )
    {
        global $connect;

        $query = "select * from trans_price";
        $result = mysql_query ( $query, $connect ) or die( mysql_error() );
        echo "<select name='trans_code'><option";
        if ( !isset($code) ) echo " selected ";
        echo ">없음</option>";
                
        while ( $data = mysql_fetch_array ( $result ) )
        {
            echo "<option value='$data[trans_code]'";
            if ( $code == $data[trans_code] ) echo " selected";
            echo ">$data[trans_code] ($data[price1] / $data[price2] / $data[price3] )";
            echo "</option>";        
         }        
        echo "</select>";
    }

    //==============================================
    // 
    // date: 2008.4.23 - jk
    function exist_trans_code()
    {
        global $connect;

        $query = "select * from trans_price";
        $result = mysql_query ( $query, $connect );
        return mysql_num_rows( $result );
    }

    ////////////////////////////////////////////
    function page_index( $page, $total_rows )
    {           
        if (!$page) $page = 1;
        if ( $total_rows % 20 )
                $_tot_page = ((int)($total_rows / 20 )) + 1;
        else        
                $_tot_page = $total_rows / 20;
                
        echo " << ";
        echo "<select name='page' onChange='javascript:change_page( this.value )'>";
                
        for ( $i = 1; $i <= $_tot_page; $i++ )
        {       
            echo "<option value=$i";
            if ( $i == $page )
                echo " selected ";
        
            echo ">$i</option>";
        }

        echo "</select>";
        echo " /$_tot_page Page ";
        echo ">>";
    }
}

?>
