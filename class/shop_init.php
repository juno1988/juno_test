<?

   ////////////////////////////////////////////////
   // gseshop 
   // 
   function init_7 ( &$header, &$file_format )
   {
      $file_format = "xls";
      $arr_items = array (
         "code1" 	=> "�ֹ���ȣ",
         "trans_no" 	=> "������ȣ",
      );
      return $arr_items;
   }

   ////////////////////////////////////
   // �����̽�������: 24
   // date: 2005.10.21
   function init_24( &$header, &$file_format )
   {
      $file_format = "xls";
      $arr_items = array ( 
          "code1" 	=> "����ֹ���ȣ",
          "trans_no" 	=> "������ȣ",
          "order_name" 	=> "�ֹ���",
          "deliv_or_not" => "��۱���",
          "recv_date" => "�Է���",
          "shop_product_id" => "��ǰ��ȣ",
          "product_name" => "��ǰ��",
          "trans_who" 	=> "�𵨸�",
          "options" 	=> "���û���",
          "qty" 	=> "����",
          "recv_name" 	=> "������",
          "recv_zip" 	=> "�����ȣ",
          "recv_address" => "�����ּ�",
          "recv_tel" 	=> "����ó",
          "recv_mobile" => "�ڵ���",
          "message" 	=> "��۸޸�",
          "supply_price" => "���޿���",
          "order_id" 	=> "�ֹ���ȣ",
          "order_subid" => "�ֹ�������ȣ",
          "pay" 	=> "�ֹ�����",
          "code2" 	=> "�ֹ�����",
          "code3" 	=> "������",
      ); 
      return $arr_items;
   } 
   ////////////////////////////////////
   // �Ե�: 09
   // date: 2005.10.21
   function init_9( &$header, &$file_format )
   {
      $file_format = "xls";

        $arr_items = array ( 
	"collect_date"	=> "������",		// a
	"code7"		=> "��������", 		// B
	"order_id"	=> "�ֹ���ȣ",		// C
	"code1"		=> "���ֹ���ȣ",	// D
        "code2" 	=> "�ֹ���ǰ��ȣ",	// e
        "code3" 	=> "���ֹ���ǰ����",	// f
        "trans_code" 	=> "�ù��",		// g
	"trans_no"	=> "�����ȣ",		// h
	"trans_date_pos"=> "�߼ۿ�����",	// I
	"code5"		=> "��ó������",	// j
	"code6"		=> "���»�ó����������",	// k
	"product_name"	=> "��ǰ��",		// l
	"recv_name"	=> "������",		// m
	"recv_zip"	=> "�����ο����ȣ",	// n
	"recv_address"	=> "�������ּ�",	// o
	"recv_tel"	=> "��������ȭ��ȣ1",	// p
	"recv_mobile"	=> "��������ȭ��ȣ2",	// q
	"empty1"	=> "�����»��(�޽���ī��)",		// r
	"empty2"	=> "�޴»��(�޽���ī��)",		// s
	"memo"		=> "�޽���",		// T
	"recv_name"	=> "������",		// u

	"order_tel"	=> "ȸ����ȭ��ȣ1",	// v
	"order_mobile"	=> "ȸ����ȭ��ȣ2",	// w
	"empty3"	=> "���������� �޸�",	// x
	"empty4"	=> "���޸�",	// y

	"shop_produt_id"=> "��ǰ�ڵ�",		// z
	"code8"		=> "�귣���",		// aa
	"code10"	=> "�𵨹�ȣ",		// ab
	"options"	=> "�ɼǰ�",		// ac
	"su_price"	=> "���Դܰ�",		// ad
	"empty1"	=> "�ǸŴܰ�",		// ae
	"empty2"	=> "�ֹ��ݾ�",		// af
	"qty"		=> "�ֹ�����",		// ag
	"code17"	=> "�߼ۿϷ����",	// ah
	"code18"	=> "�߼ۺҰ���",	// ai
	"code19"	=> "��ó������",	// aj
	"code20"	=> "�߼ۿϷ���",	// ak
	"code21"	=> "��ȯ����",		// al
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // ���� 
   // date: 2005.12.7 - jk.ryu
   function init_3( &$header, &$file_format )
   {
      $file_format = "csv";
	if ( _DOMAIN_ == "yonbang" )
	{
	      $arr_items = array ( 
		  "order_id" 	=> "�ֹ���ȣ",
		  "code1"	=> "���»��ȣ",
		  "code4"	=> "������ȣ",
		  "trans_code"	=> "�ù��ڵ�",
		  "trans_no"	=> "�����ȣ",
	      );
	}
	else 
	{
	      $arr_items = array ( 
		  "order_id" 	=> "�ֹ���ȣ",
		  "code1"	=> "���»��ȣ",
		  "code4"	=> "������ȣ",
		  "trans_code"	=> "�ù��ڵ�",
		  "trans_no"	=> "�����ȣ",
		  "order_name" 	=> "�ֹ���",
	      );
	}
      return $arr_items;
   }

   ////////////////////////////////////
   // ���� 
   // date: 2005.10.19
   // ����
   // date: 2006.12.9 - jk
   function init_1( &$header, &$file_format )
   {
      // $header = -99; //header ���� �׽�Ʈ
      $file_format = "xls";
      $arr_items = array ( 
        "no"			=> "�Ϸù�ȣ",
	"code3"			=> "����",
	"shop_product_id"	=> "��Ź�ȣ",	
        "order_id"		=> "������ȣ",
	"product_name"		=> "��ǰ��",
	"qty"			=> "����",
	"amount"		=> "�ݾ�",
	"order_name"		=> "������",
	"recv_name"		=> "������",
	"recv_tel"		=> "��ȭ��ȣ",
	"recv_mobile"		=> "�޴���",
	"trans_who"		=> "��ۺ�δ�",
	"recv_zip"		=> "�����ȣ",
	"recv_address"		=> "�ּ�",
	"options"		=> "�ֹ����� ����",
	"memo"			=> "�ֹ��䱸����",
        "trans_no"		=> "�����/����ȣ", 
	"code1"			=> "����������",
	"code2"			=> "�Ա���(�Աݹ��)",
      );
       
      return $arr_items;
   }

   ////////////////////////////////////
   // G ���� 
   // date: 2005.10.19
   // date: 2006.12.9	// ����� �ű� ��
   function init_2( &$header , &$file_format)
   {
      $file_format = "xls";
      $arr_items = array ( 
         "recv_date"=>"�����",
         "trans_name"=>"�ù��", 
         "trans_no"=>"�����ȣ", 
         "order_id"=>"ü���ȣ",
      );

      return $arr_items;
   }

   //////////////////////////////////
   // �츮Ȩ���� 14
   // date: 2006.12.9	// ����� �ű� ��
   function init_14( &$header , &$file_format)
   {
      $file_format = "csv";

      $header = "�ڰ���� ���Ȯ��(��½ð� : 2006/11/03 10:06),,,��ü�� : (),,,,,,,,,,,,,,,,,,,,,,,,
[��ۻ��ڵ�],,11:�����ù� 12:������� 15:�����ù� 16:CJGLS 17:õ���ù� 18:�Ͼ��ù� 19:��Ÿ�ù� 22:HTH 24:�����ù� 26:�ѹ̸�Ư�� 31:��ü�� 32:���ο� 34:���� 35:�ǿ� 36:Ʈ��� 37:�ѱ� 38:��� 40:KGB 41:�����ù� 99:��Ÿ �й�ۻ��ڵ忡�� �̿Ͱ��� �ڵ常 �Է��� �� �ֽ��ϴ�. �ٸ� ���� �Է��ϸ� ������� ��ϵ��� �ʽ��ϴ�.,,,,,,,,,,,,,,,,,,,,,,,,,
�� ��,,,,,,,,,,,,,,,,,,,,,,,,,,,
No,����������,�ֹ���ȣ,��ۻ�,��Ÿ,������ȣ,�����,�������,�������,������Ȳ,VIP����,����,������,����ó,�ڵ���,�ֹ�����,��������,��۱���,��ǰ����,��ǰ�ڵ�,��ǰ�ڵ�,��ǰ��,��ǰ��,����,�����ȣ,�����,�ǸŰ�,����
";

      $arr_items = array ( 
        "no"		=>"����",		// A
        "trans_date" 	=> "����������",	// B
        "order_id"	=> "�ֹ���ȣ", 		// C
        "trans_code" 	=> "��ۻ�",		// D
        "etc"		=> "��Ÿ",		// E
	"trans_no"	=> "������ȣ",	// F
 	"code2"		=> "������",		// G
        "trans_date_pos"=> "�������",		// H
        "code7" 	=> "�������",	// I
        "code3"		=> "������Ȳ",		// J
	"code4"		=> "vip����",		// K
	"order_name"	=> "����",		// L
	"recv_name"	=> "������",		// M
        "recv_tel"	=> "����ó",		// N
	"recv_mobile"	=> "�ڵ���",		// O
	"code4"		=> "�ֹ�����",		// P
	"code5"		=> "��������",		// Q
	"code6"		=> "��۱���",		// R
	"code8"		=> "��ǰ����",		// S
	"shop_product_id"=> "��ǰ�ڵ�",		// T
	"code9"		=> "��ǰ�ڵ�",		// U
	"product_name"	=> "��ǰ��",		// V
	"options"	=> "��ǰ��",		// W
	"qty"		=> "����",		// X
	"recv_zip"	=> "�����ȣ",		// Y
	"recv_address"	=> "�����",		// Z
	"shop_price"	=> "�ǸŰ�",		// AA
	"memo"		=> "����",		// AB
      );
      return $arr_items;
   }

   //////////////////////////////////
   // ����Ʈ 13
   // date: 2006.12.9	// ����� �ű� ��
   function init_13( &$header , &$file_format)
   {
      $file_format = "csv";

      $header = ",��Ȯ��LIST��
,���ù��ü�ڵ�
,���ش��ù��ü�� �� �ù��ü�ڵ��׸񰪿� �����ϸ� �ù���ڵ忡 �ش� �ڵ带 �Է��ϰ� �ù����� �Է����� �ʾƵ� �˴ϴ�
,���ù�ȸ���ڵ尡 �� �ù��ü�ڵ忡 ������ �Ʒ� �ù�� �ڵ� �Է¶���(-1)�� �Է��ϰ� �ù����Է¶��� �ش� �ù�縦 ���� �Է��մϴ�.(�� ��� ��� ������ ���� �ʽ��ϴ�.)
,���߼ۿ����Է��ڵ� ==> �߹߼� : 1 ��ǰ�� : 2 �߹������ : 3
,������߼ۻ����̸� 1,�ش��ǰ�� ǰ�� �����̸� 2,�ش� ��ǰ�� ��������̸� 3�� �߼ۿ����Է¶��� �Է��մϴ�.
,�ؼ��� ���ΰ� �Ʒ� �׸��� [���Ͼ��ε��] �� �ƴ� �����Ͻ� ���ʹ� �����ϼż� ���콺 ����Ű Ŭ���ϼż� [����]�� �����ϼż� ������ �ֽʽÿ�. (H������ ������ �����ʹ� �������)
���Ͼ��ε��,���Ͼ��ε��,���Ͼ��ε��
";

      $arr_items = array ( 
         "order"=>"����",
         "order_id"=>"�ֹ���ȣ0", 
         "order_seq"=>"�ֹ�����SEQ", 
         "1"=>"�߼ۿ����Է�",
         "trans_name"=>"�ù����Է�", 
         "trans_code"=>"�ù���ڵ��Է�",
         "trans_no"=>"�����ȣ�Է�",
      );

      return $arr_items;
   }

   ////////////////////////////////////////////////
   // �ż����
   // date: 2005.10.24
   // date: 2005.12.23 - ���� ����
   // date: 2006.12.9	// ����� �ű� ��
   function init_15( &$header , &$file_format)
   {
      $file_format = "csv";

      # $header = "���ID,�ù��ü,�����ȣ,�����\n"; // header ���� - 2007.5.29
      $header='';
      $arr_items = "";

      if ( _DOMAIN_ == "pnb" )
      {
          $arr_items = array ( 
             "code1"	        => "���ID",
             "trans_code"	=> "�ù��ü",
             "trans_no"	        => "�����ȣ",
             "user_defined"     => "�����:������"
          );
      }
      else if ( _DOMAIN_ == "metaphor" )
      {
          $arr_items = array ( 
             "code1"	        => "���ID",
             "user_defined1"	=> "�ù��ü:10000",
             "trans_no"	        => "�����ȣ",
             "user_defined"     => "�����:������"
          );
      }
      else
      {
          $arr_items = array ( 
             "code1"	=> "���ID",
             "trans_code"	=> "�ù��ü",
             "trans_no"	=> "�����ȣ",
             "recv_name"	=> "�����"
          );
      }

      return $arr_items;
   }
   
   // ������ũ ����� - jk 2006.4.12
   // �ֹ� �Ϸù�ȣ�� ���ֿ��� �������� ���� .. ���� ����
   //
   // date: 2006.12.9	// ����� �ű� ��
   function init_5( &$header , &$file_format)
   {
      $file_format = "csv";
      $arr_items = array (
	 "no"		=> "����",
         "order_id"	=> "�ֹ���ȣ",
         "code1"	=> "�ֹ��Ϸù�ȣ",
	 "product_name"	=> "��ǰ��",
	 "collect_date"	=> "�Ա�Ȯ����",
         "trans_code"   => "�ù��ü�ڵ�",
         "1"		=> "�߼۷�",
         "trans_no"	=> "�����ȣ",
      );
      return $arr_items;
   }

   // ������ũ ���� ����� - jk 2006.4.27
   function init_6( &$header , &$file_format)
   {
      $file_format = "csv";
      $arr_items = array (
	 "no"		=> "����",
         "order_id"	=> "�ֹ���ȣ",
         "code3"	=> "�ֹ��Ϸù�ȣ",
	 "product_name"	=> "��ǰ��",
	 "collect_date2"=> "�Ա�Ȯ����",
         "trans_code"   => "�ù��ü",
         "1"		=> "�߼۷�",
         "trans_no"	=> "�����ȣ",
      );
      return $arr_items;
   }
 
   /////////////////////////////////////////
   // 21. �Ϳ�
   // date : 2005.12.22 - jk.ryu
   function init_12( &$header , &$file_format)
   {
      $file_format = "csv";

      $arr_items = array (
         "order_id"	=> "�ֹ���ȣ",
         "code1" 	=> "�Ϸù�ȣ",
	 "trans_code"	=> "�ù��",
         "trans_no"	=> "������ȣ",
      );
      return $arr_items;
   }

   /////////////////////////////////////////
   // csŬ�� 
   // date : 2005.12.22 - jk.ryu
   function init_22( &$header , &$file_format)
   {
      $file_format = "xls";

      $arr_items = array (
         "code3"	=> "PO��ȣ",		// A
         "code4"	=> "PO����",		// B
         "trans_code"	=> "���ȸ���ڵ��ȣ",	// C
         "trans_no"	=> "������ȣ",	// D
         "code1"	=> "ȸ����ȣ",		// E
         "order_name"	=> "�ֹ���",		// F
         "order_phone"	=> "�ֹ��� ��ȭ��ȣ",	// G
         "order_id"	=> "�ֹ���ȣ",		// H
         "order_date"	=> "�ֹ�����",		// I
         "product_id"	=> "��ǰ�ڵ�",		// J
         "code2"	=> "��ǰ ���ڵ�",	// K
	 "trans_who"	=> "�������",		// L
         "product_name"	=> "��ǰ��",		// M
         "options"	=> "��ǰƯ��",		// N
         "supply_price"	=> "���ް�",		// O
         "qty"		=> "�ֹ�����",		// P
         "amount"	=> "�ֹ��ݾ�",		// Q
         "order_date"	=> "��������",		// R
	 "code5"	=> "ȸ������",		// S
         "recv_name"	=> "�����θ�",		// T
         "recv_phone"	=> "��������ȭ",	// U
         "recv_mobile"	=> "�������̵����",	// V
         "recv_zip"	=> "�����ȣ",		// W
         "recv_address"	=> "�ּ�",		// X
         "message"	=> "����1",		// Y
         "code6"	=> "����2",		// Z
         "code7"	=> "����3",		// AA
         "code8"	=> "�൵",		// AB
         "memo"		=> "�ֹ��ڸ޽���", 	// AC
         ""		=> "��ü�ڵ�",
         ""		=> "����",
         ""		=> "����ڵ�",
         ""		=> "������Ͽ���",
         ""		=> "���ڵ�",
         ""		=> "��һ���",
         ""		=> "�������",
         ""		=> "��۱���",
         ""		=> "��ۿϷΌ����",
         ""		=> "�ǸŰ�",
      );

      return $arr_items;
   }

   /////////////////////////////////////////
   // kt�� : 21 
   // date : 2005.12.22 - jk.ryu
   function init_21( &$header , &$file_format)
   {
      $file_format = "xls";

      $arr_items = array (
         "trans_name"	=> "�ù��",
         "trans_no"	=> "������ȣ",
         "no"		=> "�Ϸù�ȣ",
         "order_id"	=> "�ֹ���ȣ",
         "order_name"	=> "�ֹ���",
         "recv_name"	=> "������",
         "product_name"	=> "��ǰ��",
         "options"	=> "��ǰ��",
         "shop_product_id"=> "�𵨸�",
         "shop_price"	=> "�ǸŴܰ�",
         "amount"	=> "�Ǹűݾ�"
      );
      return $arr_items;
   }

   /////////////////////////////////////////
   // ���̼��̺���  - 19
   // date : 2005.12.22 - jk.ryu
   function init_19( &$header , &$file_format)
   {
      $file_format = "xls";

      $header = "�ϰ����";

      $arr_items = array (
         "order_id"	=> "�ֹ���ȣ",
         "order_subid" 	=> "�ֹ��Ϸù�ȣ",
         "code1"	=> "��ü��",
         "product_id"	=> "��ǰ�ڵ�",
         "product_name"	=> "��ǰ��",
         "options"	=> "��ǰ��",
         "recv_name"	=> "������",
         "recv_tel"	=> "������ ��ȭ��ȣ",
         "recv_mobile"	=> "������ ��ȭ��ȣ2",
         "recv_zip"	=> "�����ȣ",
         "recv_address"	=> "�ּ�",
	 ""		=> "��۸޽���",
         "trans_no"	=> "������ȣ",
      );
      return $arr_items;
   }

   /////////////////////////////////////////////
   // ���빮 ���� - 25
   function init_25 ( &$header , &$file_format)
   {
      ////////////////////////////////////////
      // header �� ���� �ٷ� ������ ���۵�
      $header = -99;
      // file format�� csv
      $file_format = "csv";
      $arr_items = array ( 
         "empty"=>"����",
         "code1"=>"�ֹ���ȣ",
         "trans_no"=>"�����ȣ",
         "trans_name"=>"�ù���", 
      );
      return $arr_items;
   }

   /////////////////////////////////////////////
   // ��Ĺ - 
   function init_4 ( &$header , &$file_format)
   {
      $file_format = "xls";
      $arr_items = array (
         "order_id"=>"�ŷ���ȣ",
         "onket_deliv_way"=>"��۹��",
         "trans_name"=>"��ۻ�",
         "onket_arrive_date"=>"����������",
         "trans_no"=>"�����ȣ",
      );
      return $arr_items;
   }

   ///////////////////////////////////////////////
   // �պ�
   // date: 2005.10.24
   function init_18( &$header, &$file_format )
   {
      ////////////////////////////////////////
      // header �� ���� �ٷ� ������ ���۵�
      // $header = -99;

      $file_format = "xls";
      $arr_items = array (
         "order_id" => "�ֹ���ȣ",
         "code1" => "�ֹ��󼼹�ȣ",
         "trans_no" => "�����ȣ�Է¶�",
         "code2" => "�Ǹ�ó",
         "product_name" => "��ǰ��",
         "options" => "��ǰ����",
         "qty" => "����",
         "trans_price" => "����",
         "order_name" => "�ֹ���",
         "recv_name" => "������",
         "order_email" => "���ڿ���",
         "recv_zip" => "�����ȣ",
         "recv_address" => "�ּ�",
         "recv_tel" => "��ȭ��ȣ",
         "recv_mobile" => "�޴���",
         "pay_date" => "������",
         "collect_date" => "���������",
         "amount" => "�ֹ���",
         "shop_price" => "�ǸŴܰ�",
         "code3" => "������",
         "supply_price" => "���ް�",
         "pay_date" => "������",
         "memo" => "����û",
         "shop_product_id" => "��ǰ�ڵ�",
      );
      return $arr_items;
   } 

   ///////////////////////////////////////////////
   // ����
    // date: 2005.10.24
   function init_20( &$header, &$file_format )
   {
      ////////////////////////////////////////
      // header �� ���� �ٷ� ������ ���۵�
      // $header = -99;

      $file_format = "xls";
      $arr_items = array (
         "v" => "���CHK",
         "order_id" => "�ֹ���ȣ",
         "order_name" => "�ֹ��ڸ�",
         "product_name" => "��ǰ��",
         "options" => "��ǰ�ɼ�",
         "qty" => "�ֹ���",
         "qty" => "���",
         "trans_name" => "�ù��",
         "trans_no" => "�����ȣ",
         "code1" => "�ֹ��󼼹�ȣ",
         "order_date" =>  "�ֹ�����",
         "����غ���" => "�ֹ�����",
         "order_tel" => "��ȭ��ȣ",
         "order_mobile" => "�޴�����ȣ",
         "shop_product_id" => "��ǰ�ڵ�",
         "recv_name" => "������",
         "recv_zip" => "�����ȣ",
         "recv_address" => "�ּ�",
         "recv_tel" => "�������ȭ��ȣ",
         "recv_mobile" => "������޴�����ȣ",
         "memo" => "��۸޽���",
         "supply_price" => "����",
         "shop_price" => "�Ǹž�",
         "trans_code" => "�ù���ڵ�",
         "0" => "�Ա�Ȯ����",
      );
      return $arr_items;
   } 

 ////////////////////////////////////
   // �����/ �����ִ�: 45
   // date: 2006.3.16
   // 
   function init_45( &$header, &$file_format )
   {
      $file_format = "csv";
      $header = "";
      $arr_items = array (
          "order_id"  		=> "�ֹ���ȣ",	
          "shop_product_id"	=> "��ǰ��ȣ",	
          "trans_name" 		=> "�ù��",	
          "trans_no"  		=> "�����ȣ",	
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // ����: 46
   // date: 2006.3.14
   // 
   function init_46( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "";
      $arr_items = array (
	"order_id"	=>"�ֹ���ȣ",
	"recv_zip"	=>" ����",
	"code1"		=>"�հ�", 
	"product_name"	=>"ǰ��",
	"qty"		=>"����", 
	"recv_name"	=>"����", 
	"recv_tel"	=>"��ȭ��ȣ", 
	"recv_mobile"	=>"�޴���", 
	"recv_address "	=>"�ּ�", 
	"code2"		=>"�ּ�2",
	"memo "		=>"�޸�",
	"options"	=>"�ɼ�1", 
	"code3"		=>"�ɼ�2",
	"trans_no"	=>"�����ȣ "
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // k��Ʈ: 47
   // date: 2006.3.14
   // 
   function init_47( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "";
      $arr_items = array (
        "code1"		=>"����",
 	"code2"		=>"���ּ� Ȯ�ο���",
	"order_id"	=>"�ֹ���ȣ",
	"trans_who"	=>"��ۺ�",
	"product_name"	=>"��ǰ��",
	"options"	=>"��������", 
	"qty"		=>"����", 
	"recv_name"	=>"������", 
	"recv_zip"	=>"����� �����ȣ",
	"recv_address "	=>"�����", 
	"recv_tel"	=>"�����ο���ó1", 
	"recv_mobile"	=>"�����ο���ó2", 
	"memo "		=>"�����ڸ޸�",
	"shop_price"	=>"ü�ᰡ",
	"order_name"	=>"�����ڸ�",
	"trans_no"	=>"�����ȣ "
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // 11����: 50 
   // date: 2006.3.14
   // 
   function init_50( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "�߼�ó��";
      $arr_items = array (
        "no"		=>"��ȣ",
        "code1"		=>"��۹�ȣ",
	"order_id"	=>"�ֹ���ȣ",
	"product_id"	=>"��ǰ��ȣ",
	"product_name"	=>"��ǰ��",
	"options"	=>"�ɼ�", 
	"shop_product_id"=>"�Ǹ��ڻ�ǰ�ڵ�",	// G
	"empty1"	=>"�ǸŴܰ�",
	"qty"		=>"����", 
	"shop_price"	=>"�ֹ��ݾ�",
	"empty2"	=>"�߰���ǰ",
	"empty3"	=>"�߰���",
	"amount"	=>"�� �ֹ��ݾ�",
	"empty4"	=>"��ۺ񱸺�",
	"trans_who"	=>"��ۺ�",
	"empty5"	=>"�Ǹ�������",
	"empty6"	=>"�����",	// Q
	"order_name"	=>"������",	// R
	"recv_id"	=>"������ID",	// S
	"recv_name"	=>"������",
	"recv_tel"	=>"��ȭ��ȣ",	// T
	"recv_mobile"	=>"�޴���",
	"recv_zip"	=>"�����ȣ",
	"recv_address"	=>"��۽��ּ�",
	"memo"		=>"��۽ÿ䱸����",
	"empty7"	=>"�ǸŹ��",
	"order_date"	=>"�ֹ��Ͻ�",
	"collect_date"	=>"�����Ϸ�",
	"empty8"	=> "��۹��",
	"trans_code"	=> "�ù���ڵ�",
	"trans_no"	=> "����/����ȣ",
      );

      return $arr_items;
   }



   ////////////////////////////////////
   // Hmall: 43
   // date: 2006.1.26
   // 
   function init_43( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "";
      $arr_items = array (
          "order_id"  	=> "�ֹ���ȣ",
          "trans_code"  => "�ù���ڵ�",	
          "trans_no"  	=> "�����ȣ",	
      );

      return $arr_items;
   }


   ////////////////////////////////////
   //
   // �����̺���Ʈ: 41
   // date: 2006.2.9
   //
   function init_41( &$header, &$file_format )
   {
      $file_format = "xls";
      $arr_items = array ( 
          "code1"  	=> "��ȣ",
          "order_id" 	=> "�ֹ���ȣ",
          "x" 		=> "ȸ�����̵�",
	  "order_name"	=> "�ֹ��� ��",
          "order_email"	=> "�ֹ��� email",
	  "order_mobile"=> "�ֹ��� �ڵ���",
	  "order_zip"	=> "�ֹ��� �����ȣ",
	  "order_address" => "�ֹ��� �ּ� 1",
	  "x"		=> "�ֹ��� �ּ�2",
	  "recv_name"	=> "�����θ�",
	  "recv_tel"	=> "������ ��ȭ��ȣ",
          "recv_mobile"	=> "������ �ڵ���",
 	  "recv_zip"	=> "������ �����ȣ",
	  "recv_address" => "������ �ּ�1",
	  "x"		=> "������ �ּ�2",
	  "memo"	=> "��û����",
	  "message"	=> "���޸޼���",
	  "x"		=> "ȸ�� ���ſ���",
          "x"		=> "�ǸŹ��",
          "amount"	=> "�ѱݾ�",
	  "trans_who"	=> "��ۺ�",
	  "x"		=> "ī������ݾ�",
	  "x"		=> "������ �����ݾ�",
	  "x"	 	=> "����Ʈ �����ݾ�",
	  "x"		=> "������ ��������",
	  "x"		=> "�Աݿ�����",
          "x"		=> "�Ա��ڸ�",
	  "trans_name"	=> "���ȸ��",
          "trans_no"	=> "��۹�ȣ",
      );
      return $arr_items;
   }

   ////////////////////////////////////
   //
   // �м� �÷��� : 38
   // date: 2006.11.3
   // 
   function init_38( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "";
      $arr_items = array (
          "trans_no" => "�����ȣ",	
          "order_date" => "��������",	
          "order_id" => "�ֹ���ȣ",
	"code1"		=> "�ֹ���ȣ(��ȯ)",
	"no"		=> "����",
	"code2"		=> "����",	// F
	"code3"		=> "���ҹ��",	// G
	"order_name"	=> "�ֹ��ڸ�",	// H
	"shop_product_id"	=> "ǰ��",	// I
	"product_name"	=> "��ǰ��",
	"options"	=> "�Ӽ�",		// K
	"qty"		=> "����",
	"shop_price"	=> "�ǸŴܰ�",
	"amount"	=> "�Ǹűݾ�",	// N

	"payer_name"	=> "�����ڸ�",	// O
	"order_address"	=> "�������ּ�", // P
	"recv_name"	=> "�޴»��",  // Q

	"recv_address"	=> "�����",    // R
	"recv_zip"	=> "�����ȣ",  // S


	"code5"		=> "��۷����ҿ���",	// T
	"code6"		=> "���ҹ�۷�",	// U
	"recv_tel"	=> "�޴� ��� ��ȭ", // V
	"recv_mobile"	=> "�޴���ȭ",	// W

	"memo"		=> "�޽���",    // X
	"code4"		=> "��ǰ",	// Y 
	"trans_date"	=> "�����������", // Z
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // �̸����: 39
   // date: 2006.1.2
   // 
   function init_39( &$header, &$file_format )
   {
      $file_format = "csv";
      $header = "";
      $arr_items = array (
          "order_id" => "�ֹ���ȣ",
          "trans_no" => "�����ȣ",	
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // cj mall: 26
   // date: 2005.12.14
   // 
   function init_26( &$header, &$file_format )
   {
      $file_format = "xls";
      //$header = -99;
      $arr_items = array (
          "order_id" 	=> "�ֹ���ȣ",
          "code1" 	=> "�����ĺ���ȣ",
          "trans_no" 	=> "������ȣ",	
          "1" 		=> "������",
      );

      return $arr_items;
   }

   /////////////////////////////////////////////////
   // GSEStore (���� ����)
   function init_8 ( &$header, &$file_format )
   {
      $file_format = "xls";
      $arr_items = array (
         "no" 		=> "����",
         "order_id" 	=> "�ֹ���ȣ",
         "trans_no" 	=> "������ȣ",   
      );
      return $arr_items;
   }


   //////////////////////////////////////////////////
   // ���� 
   // date: 2006.4.21 -jk
   function init_59( &$header, &$file_format )
   {
      $file_format = "csv";

      $arr_items = array ( 
          "order_id"	=> "�ֹ���ȣ",
	  "trans_no"	=> "�����ȣ",
      );
      return $arr_items;
   }

   // fashion story
   function init_75( &$header, &$file_format )
   {
   $file_format = "csv";

      $arr_items = array ( 
		"order_id"      => "�ֹ���ȣ",
                "code1"         => "����",
                "trans_no"      => "�����ȣ",
                "code2"         => "��ü�ڵ�",
                "code3"         => "������ ��ȣ",
                "order_name"    => "�ֹ����̸�",
                "order_tel"     => "�ֹ��ڿ���ó",
                "recv_name"     => "�������̸�",
                "recv_mobile"   => "�����ο���ó",
                "zip"           => "�����ο����ȣ",
                "address1"      => "�������ּ�",
                "trans_who"     => "�ù��",
                "product_name"  => "��ǰ��",
                "option1"       => "����",
                "option2"       => "������ (o)",
                "qty"           => "����",
                "price"         => "�ݾ�",
                "memo"          => "�޽���"	
      );
      return $arr_items;

   }

   //////////////////////////////////////////////////
   // ezAdmin 
   // ���ھ� 
   // date: 2006.4.29 -jk
   function init_58( &$header, &$file_format )
   {
      $file_format = "xls";

      $arr_items = array ( 
          "code1"	=> "����",
          "order_id"	=> "������(��ȣ)",
 	  "product_name" => "��ǰ��",
	  "qty"		=> "����",
	  "options"	=> "����",		// H
	  "code2"	=> "������",		// H
	  "recv_name"	=> "����(������)",
	  "recv_address"=> "�ּ�",
	  "recv_tel"	=> "��ȭ��ȣ",
	  "recv_mobile"	=> "��ȭ��ȣ2",
	  "recv_zip"	=> "�����ȣ",
	  "memo"	=> "��۸޸�",
	  "trans_no"	=> "�����ȣ",
      );
      return $arr_items;
   }


   //////////////////////////////////////////////////
   // makeshop
   // date: 2008.6.26 -jk
   // 
   function init_68( &$header, &$file_format )
   {
      $file_format = "csv";

      $arr_items = array ( 
        "order_id"	        => "�ŷ���ȣ",
	"order_name"		=> "�ֹ���",
	"trans_no"		=> "�����ȣ",
      );
      return $arr_items;
   }

   //////////////////////////////////////////////////
   // ����Ŀ 
   // date: 2006.11.3 -jk
   // 
   function init_66( &$header, &$file_format )
   {
      $file_format = "xls";

      $arr_items = array ( 
        "code1"			=> "�ֹ���ȣ",
	"shop_product_id"	=> "�ֹ���ǰ��ȣ",
	"trans_code"		=> "�ù��",
	"trans_no"		=> "�����ȣ",
      );
      return $arr_items;
   }

   //////////////////////////////////////////////////
   // ����Ŀ for mammacall
   // date: 2007.3.23 -jk
   // 
   function init_82( &$header, &$file_format )
   {
      $file_format = "xls";

      $arr_items = array ( 
        "code1"			=> "�ֹ���ȣ",
	"shop_product_id"	=> "�ֹ���ǰ��ȣ",
	"trans_code"		=> "�ù��",
	"trans_no"		=> "�����ȣ",
      );
      return $arr_items;
   }

   //////////////////////////////////////////////////
   // ezAdmin 
   // date: 2006.2.1 -jk
   function init_98( &$header, &$file_format )
   {
      $file_format = "csv";

      $arr_items = array ( 
          "order_id"	=> "�ֹ���ȣ",
	  "trans_no"	=> "�����ȣ",
      );
      return $arr_items;
   }

   //================================================
   //
   // play auto 97
   // date: 2006.12.26 - jk.ryu
   //
   function init_playauto( &$header, &$file_format )
   {
      $file_format = "xls";

      $arr_items = array ( 
	order_id	=> "DB",
	order_date    	=> "�����",
	product_name  	=> "��ǰ��",
	option1       	=> "�ɼ�  ",
	qty           	=> "����  ",
	su_price      	=> "���ް�",
	trans_fee     	=> "��۷�",
	trans_who     	=> "����  ",
	order_name    	=> "�ֹ���",
	order_tel     	=> "�ֹ�����ȭ",
	order_mobile  	=> "�ֹ����ڵ���",
	code10		=> "�ֹ����̸���",
	recv_name     	=> "������",
	recv_tel      	=> "��ȭ  ",
	recv_mobile   	=> "�ڵ���",
	zip           	=> "�����ȣ",
	address1      	=> "�ּ�",
	memo          	=> "��۸޼���",
	code2		=> "C/S�޼��� ",
	code3		=> "����Ȯ��",
	code4		=> "��ȯ����",
	code5		=> "��ǰȮ��",
	code6		=> "���Ȯ��",
	trans_name	=> "��ۻ�",
	trans_no	=> "�����ȣ",
	code7		=> "CS�ϰ�",
	code8		=> "����  ",
	code1         	=> "�Ǹ�ó",
	price         	=> "�ǸŰ�",
	code9		=> "�ֹ���ȣ",
	);

      return $arr_items;
   }

   //////////////////////////////////////////////////
   // ���θ���
   // date: 2005.12.8 -jk
   function init_10( &$header, &$file_format )
   {
      $file_format = "xls";

      $arr_items = array ( 
          "order_id"	=> "�ֹ���ȣ",
	  "trans_no"	=> "�����ȣ",
      );
      return $arr_items;
   }

   ////////////////////////////////////
   // �׿���: 28
   // date: 2006.01.04
   function init_28( &$header, &$file_format )
   {
      $file_format = "csv";
      $arr_items = array ( 
         "no"		=> "�Ϸù�ȣ",		// A
         "code1"	=> "������ȣ",		// B
         "x"		=> "����",		// C
         "order_id" 	=> "�ֹ���ȣ",	// D
         "order_date"	=> "�ֹ�����",	// E
         "trans_price"  => "�ù��",	// F
         "product_name"	=> "��ǰ��",        // G
         "options"      => "��ǰ�ɼ�",      // H
         "qty"        	=> "��ǰ����",      // I
         "order_name"   => "�ֹ���",        // J
         "order_tel"    => "����ó1",       // K
         "order_mobile" => "����ó2",       // L
         "recv_name"    => "������",        // M
         "recv_address" => "�ּ�",          // N
         "recv_tel"        => "����ó1",       // O
         "recv_mobile"        => "����ó2",       // P
         "memo"        => "�䱸����",      // Q
         "trans_no" => "�����ȣ",
         "trans_name" => "�ù��",
      );
      return $arr_items;
   }

   ////////////////////////////////////
   // ����Ŭ��: 27
   // date: 2006.5.16 �����
   function init_27( &$header, &$file_format )
   {
     $file_format = "csv";

     $arr_items = array (
       "halfclub_code" => "�ŷ�ó�ڵ�",
       "order_id"      => "�ֹ���ȣ",
       "code5"         => "�ֹ�����",
       "qty"           => "�ֹ�����",
       "1"             => "������",
       "zero"          => "ǰ������",
       "trans_code"    => "�ù���ڵ�",
       "trans_no"      => "������ȣ",
     );

     return $arr_items;
   }
 

   ////////////////////////////////////
   // ������: 32
   // date: 2006.5.01
   // 
   function init_32( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "";
      $arr_items = array (
          "trans_no" 	=> "�����ȣ",	
          "trans_code"	=> "�ù���ڵ�",
          "order_id" 	=> "�ֹ���ȣ",
	  "code1"	=> "�ڵ�",
	  "qty"		=> "����",
	  "code2"	=> "����",	// F
	  "code3"	=> "�������",
	  "shop_product_id" => "��ǰ�ڵ�",
	  "code4"	=> "��ǰ�ڵ�(��ü)",
	  "product_name" => "��ǰ��",
	  "options"	=> "�ɼǸ�",
	  "amount"	=> "�ֹ��ݾ�",
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // ���� ����: 40
   // date: 2006.1.26
   // 
   function init_40( &$header, &$file_format )
   {
      $file_format = "txt";
      $header = "";
      $arr_items = array (
          "trans_no" => "�����ȣ",	
          "order_id" => "�ֹ���ȣ",
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // �Ｚ��: 42
   // date: 2006.1.26
   // code1�� order_id�� ��ġ ���� - 
   function init_42( &$header, &$file_format )
   {
      $file_format = "csv";
      $header = "\n\n \n\n";
      $arr_items = array (
          "user_defined"   => "��ü��ȣ:24060",
          "order_id"  	   => "�ֹ���ȣ",	
          "code1"          => "�ֹ��󼼹�ȣ",	
          "trans_no"  	   => "�����ȣ",	
          "recv_date"	   => "��������",
          "trans_date"	   => "���Ͽ�����",
          "none1"	   => "���",
	  "none2"          => "���ϱ���",
	  "trans_code"	   => "�ù���ڵ�",
      );

      return $arr_items;
   }

   ////////////////////////////////////
   // ���̸���: 65
   // date: 2006.6.15
   // 
   function init_65( &$header, &$file_format )
   {
      $file_format = "csv";
      $arr_items = array (
          "cy_order_date"  	=> "������",	
          "cy_collect_date"  	=> "����Ȯ����",	
          "order_id"  		=> "�ֹ��󼼹�ȣ",	//
          "cy_trans_date"  	=> "�߼���",	
          "cy_trans_how"	=> "��۹��",		// E
	  "trans_code"		=> "�ù���ڵ�",
	  "trans_no"		=> "�����ȣ",		// G
          "code3"		=> "��������ڵ�",	// code2�� ���� ��� �ڵ尡 ��� ���� ����..
	  "order_name"		=> "�����ڸ�",
          "code1"		=> "���̿���",		// J
	  "order_tel"		=> "����ó��ȣ",
	  "order_mobile"	=> "�޴�����ȣ",	// L
	  "recv_name"		=> "�����θ�",		// M
	  "recv_tel"		=> "�����ο���ó",	// N
	  "recv_mobile"		=> "�������޴���",	// O
          "recv_zip"		=> "�����ȣ",
          "recv_address"	=> "�����",
	  "product_id"		=> "��ǰ��ȣ",
          "empty1"		=> "��ü ��ǰ��ȣ",
	  "product_name"	=> "������ǰ��",
	  "options"		=> "���û���",		// T
          "code2"		=> "�ɼ�",		// U
	  "code4"		=> "����ǰ",		// V
	  "qty"			=> "����",		// W
	  "amount"		=> "�ݾ�",		// X
	  "trans_who"		=> "�ù��",		// Y
	  "trans_date_pos"	=> "�߼ۿ�����",	// Z
          "code5"		=> "����Ʈ",		// AA 
          "memo"		=> "��۽ÿ�û����",	// AB
          "code6"		=> "�������忩��",	// AC
          "code7"		=> "��������޽���",	// AD
          "match_product_name"	=> "���û�ǰ��",	// AE
      );

      return $arr_items;
   }


//////////////////////////////////////////////////
   // ezAdmin 
   // date: 2007.2.24 - jk.ryu
   // ������ : �ϳ����̺�
   function init_84( &$header, &$file_format )
   {

	// ================================
        // date: 2008.3.19 -jk.ryu
        if ( _DOMAIN_ == "codipia" )
        {
                $file_format = "xls";
                $arr_items = array (
                        "order_date"            => "�ֹ��Ͻ�",		// A
                        "product_name"          => "��ǰ��", 		// B
                        "empaty1"       	=> "�ɼ�",	        // C
                        "qty"       		=> "����",	        // D
			"recv_name"		=> "�����ڸ�",    	// E
			"recv_tel"		=> "��������ȭ��ȣ",	// F
			"recv_mobile"		=> "�������޴�����ȣ",  // G
			"recv_address"		=> "�ּ�",
			"memo"			=> "��۸޽���",        // I
			"trans_price"		=> "��۷�",
			"trans_name"		=> "�ù���",
			"trans_no"		=> "�����ȣ",
			"trans_date_pos"	=> "����Ͻ�",
			"order_id"		=> "�ֹ��Ϸù�ȣ"
                );   

                return $arr_items;
        }
    }

   //////////////////////////////////////////////////
   // ezAdmin 
   // date: 2007.2.24 - jk.ryu
   // ������ : �ϳ����̺�
   function init_81( &$header, &$file_format )
   {

	// ================================
        // init_81�� ������
        // date: 2007.4.6 -jk.ryu
        if ( _DOMAIN_ == "zen" )
        {

                $file_format = "xls";
                $arr_items = array (
                        "code1"                 => "�ֹ���ȣ",
                        "shop_product_id"       => "�ֹ���ǰ��ȣ",
                        "trans_code"            => "�ù��",
                        "trans_no"              => "�����ȣ",
                );   

                return $arr_items;
        }
	else if ( _DOMAIN_ == "milkcoco" )
	{
		$file_format = "xls";
	      	$arr_items = array ( 
		    	no		=> "No",
		    	shop_product_id => "��ǰ�ڵ�",
			order_id	=> "�ֹ���ȣ",
			product_name	=> "��ǰ��",
			order_name	=> "��ǰ��",
			collect_date    => "�ֹ�Ȯ����",
			user_defined 	=> "��ü���̵�:han523",
			trans_name      => "�⺻�ù��",
			trans_no	=> "�����ȣ"
		);
                return $arr_items;
	}
	else if ( _DOMAIN_ == "sj" )
	{
		// �м� ��
		$file_format = "xls";
	      	$arr_items = array ( 
          	  "order_date"     => "�ֹ���¥",
		  "order_id"	   => "�ֹ���ȣ", // B
		  "shop_product_id"	   => "�ֹ���ǰ��ȣ", // C
		  "order_name"	   => "�ֹ���",   // D
		  "recv_name"	   => "������",	 // E
		  "recv_tel"	   => "��������ȭ��ȣ1",	 // F
		  "recv_mobile"	   => "��������ȭ��ȣ2",	 // G
		  "recv_zip"	   => "�����ȣ",	 // G
		  "recv_address"   => "�������ּ�",	 // I
		  "code1"	   => "�귣���",	// J
		  "product_name"   => "��ǰ��",		// K
		  "options"	   => "�ɼ�",		// L
		  "qty"		   => "����",		// M
		  "shop_price"	   => "�ǸŰ�",		// N
 		  "supply_price"   => "���ް�",		// O
		  "amount"	   => "���ǸŰ�",  	// P
 		  "supply_price"   => "�Ѱ��ް�", 	// Q
		  "memo"	   => "���䱸����",   // R
		  "org_trans_who"	   => "��ۺ�",		// S
		  "trans_name"=> "�ù���",
		  "trans_no"	   => "�����ȣ"
	      	);
	      	return $arr_items;
	}
	else if ( _DOMAIN_ == "purdream" )
	{
	    $file_format = "csv";
	    $arr_items = array ( 
          	  "order_id"     => "�ֹ���ȣ",
		  "trans_no"     => "�����ȣ",
	      	);
	    return $arr_items;
	}
	else if ( _DOMAIN_ == "hanlin829" )
	{
      		$file_format = "xls";
		$arr_items = array (
			"order_id"            => "�ֹ���ȣ",
          	  	"user_defined"        => "ǰ���ȣ:1",
          	  	"product_name"        => "��ǰ��",
          	  	"user_defined1"       => "�ù��ڵ�:1500",
			"trans_no"            => "�����ȣ",
			"recv_name"           => "������",
			"recv_address"        => "������ּ�",
		);  

		return $arr_items;
 	}
	else if ( _DOMAIN_ == "jnb" )
	{
	    $file_format = "xls";
	    $arr_items = array ( 
          	  "shop_name"      => "No",
		  "product_id"     => "��ǰ�ڵ�",
		  "order_id"	   => "�ֹ���ȣ", // C
		  "product_name"   => "��ǰ��",
		  "order_name"	   => "�ֹ���",
		  "collect_date"   => "�ֹ�Ȯ����",
		  "empty1"	   => "��ü���̵�",
          	  "user_defined"   => "�⺻�ù��:�����ͽ��������ù�",
		  "trans_no"	   => "�����ȣ",
		  "code1"	   => "��ü��"
	      	);
	    return $arr_items;
	}
        else if ( _DOMAIN_ == "codipia" )
	{
      		$file_format = "csv";
		$arr_items = array (
			"order_id"      => "�ֹ���ȣ",
			"code2"       	=> "����ó",
			"user_defined"  => "��ۻ��:�簡���ͽ�������",
			"trans_no"      => "�����ȣ",
			""              => "�����ĵ��",
		);  

		return $arr_items;
 	}
	else if ( _DOMAIN_ == "bigtree" )
	{
		$file_format = "csv";
		$header = "";
		/*
		$arr_items = array ( 
			"code3"	=> "SeqNo",
			"order_id" => "�ֹ���ȣ",
			"code1"	=> "�ֹ�����",
			"shop_product_id" => "��ǰ�ڵ�",
			"shop_product_name" => "��ǰ��",
			"options"	=> "�ɼ�",
			"shop_price"	=> "�ǸŰ�",
			"qty"	=> "����",
			"code2" => "������ID",
			"order_name" => "�̸�",
			"empty1"	=> "�������",
			"recv_address"	=> "�������ּ�",
			"recv_name"	=> "������",
			"recv_tel"	=> "��ȭ��ȣ",
			"recv_mobile"	=> "�ڵ���",
			"memo"	=> "�ֹ���û����",
			"trans_no" => "�����ȣ",
			"order_date" => "����(�Ա�)����",
		);
		*/
	
		$arr_items = array (
			"order_id" 	=> "�ֹ���ȣ",
			"code1"		=> "�����ڵ�",
			"order_date"	=> "�Ա�Ȯ�� �ð�",
			"order_name"	=> "�Ա���",
			"32"		=> "�ù��ڵ�",
			"trans_no"	=> "�����ȣ",
			"trans_date"	=> "�������",
			"1"		=> "�̸���",
			"0"		=> "SMS",
		);

		return $arr_items;
	}
        else
	{
      		$file_format = "xls";
	      	$arr_items = array ( 
		  "code1"	=> "No",
		  "shop_product_id"=> "��ǰ�ڵ�",
		  "order_id"	=> "�ֹ���ȣ",
		  "product_name" => "��ǰ��",
		  "order_name"	=> "�ֹ���",
		  "collect_date" => "�ֹ�Ȯ����",
		  "hana_id"	=> "��ü���̵�",
		  "trans_name"	=> "�⺻�ù��",
		  "trans_no"	=> "�����ȣ",
	      );
	      return $arr_items;
	}
   }


   //////////////////////////////////////////////////
   // ����ƾ ���̽�Ÿ��
   // date: 2007.10.1
   function init_74( &$header, &$file_format )
   {
        $file_format = "xls";
        $header = "";
        $arr_items = array (
                "code4"         => "�ߺ�����",          // A
                "code3"         => "������ù�ȣ",
                "order_id"      => "�ֹ���ȣ",
                "trans_date_pos" => "�����������",     // D
                "code5"         => "��������",          // E
                "order_name"    => "�ֹ��ڸ�",
                "recv_name"     => "�����θ�",          // G
                "recv_tel"      => "�����ο���ó",
                "recv_mobile"   => "�������޴���",
                "code6"         => "����׸��ȣ",      // J
                "code7"         => "��ۻ�ǰ�ڵ�",
                "code8"         => "��ۻ�ǰ��",        // L
                "code9"         => "������ǰ��",        // M
                "code10"        => "��Ÿ��No",          // N
                "code1"         => "��ۻ�ǰSKU",       // O
                "code2"         => "��ǰ����",
                "shop_price"    => "�ǸŰ���",
                "qty"           => "��ۼ���",
                "recv_zip"      => "�����ȣ",
                "recv_address"  => "������ּ�1",
                "recv_address2" => "������ּ�2",
                "memo"          => "��۸޸�",
                "code11"         => "��ۻ���",
                "trans_no"      => "�����ȣ",
                "trans_name"    => "�ù��",
                "memo"          => "�����޸�",
        );

        return $arr_items;
   }

//////////////////////////////////////////////////
   // ����ƾ ������ 
   // date: 2007.10.1
   function init_76( &$header, &$file_format )
   {
        $file_format = "csv";
        $header = "";

        $arr_items = array (
                "code3" => "SeqNo",
                "order_id" => "�ֹ���ȣ",
                "code1" => "�ֹ�����",
                "shop_product_id" => "��ǰ�ڵ�", // D
                "shop_product_name" => "��ǰ��",
                "options"       => "�ɼ�",
                "shop_price"    => "�ǸŰ�",
                "qty"   => "����",  		// H
                "code2" => "������ID",
                "order_name" => "�̸�",
                "empty1"        => "�������", // K
		"recv_zip"	=> "�����ȣ",
                "recv_address"  => "�������ּ�",
                "recv_name"     => "������",
                "recv_tel"      => "��ȭ��ȣ", // O
                "recv_mobile"   => "�ڵ���",   // P 
                "memo"  => "�ֹ���û����",
                "trans_no" => "�����ȣ",
                "order_date" => "����(�Ա�)����",
        );

        return $arr_items;

   }

   

   //////////////////////////////////////////////////
   // wizwid 
   // date: 2006.3.31
   function init_48( &$header, &$file_format )
   {
      $file_format = "csv";

      $header = "<!-- ICG Tempate: /venderdelivery/DeliveryListDetailFile.icm -->\n \n";

      $arr_items = array ( 
          "trans_code"	=> "�ù���ڵ�",
	  "trans_no"	=> "�����ȣ",
	  "order_date2"	=> "����Ƿ���",
	  "order_id"	=> "����ȣ",
	  "code1"	=> "�ֹ���ȣ",
	  "code2"	=> "����",
	  "shop_product_id"	=> "��ǰ�ڵ�",
	  "product_name" => "��ǰ��",
	  "code3"	=> "��",
	  "options"	=> "�Ӽ� 1-2-3",
	  "trans_who"   => "���ҿ���",	 	// 2009.4.2 �߰�
	  "qty"		=> "����",
	  "supply_price"=> "��ǰ��",
          "shop_price"  => "�ǸŰ�",
          "order_name"  => "����",
          "recv_name"	=> "������",
	  "recv_address" => "�������ּ�",
          "recv_tel"	=> "������TEL",
	  "recv_mobile"	=> "������HP",
      );


      return $arr_items;
   }

   //////////////////////////////////////////////////
   // Lotte rootl openmarket
   // date: 2007.4.27
   // 2006.12.9
   function init_77( &$header, &$file_format )
   {
      $file_format = "xls";
      $arr_items = array ( 
                "order_no"      => "�ֹ���ȣ",
                "NULL"          => "�ֹ���ǰ��ȣ",
                "order_date"    => "�ֹ���",
                "NULL"          => "������",
                "product_no"    => "��ǰ�ڵ�",
                "product_name"  => "��ǰ��",
                "option1"       => "��ǰ�ɼ�����",
                "order_name"    => "�����ڼ���",
                "NULL"          => "�����ڷα���ID",
                "recv_name"     => "�������̸�",
                "recv_tel"      => "��������ȭ",
                "recv_mobile"   => "�������ڵ���",
                "zip"           => "�����ȣ",
                "address1"      => "�������ּ�",
                "price"         => "��ǰ�ܰ�",
                "qty"           => "����",
                "amount"        => "�ֹ��ݾ�",
                "trans_who"     => "��ۺ�δ�",
                "code1"         => "��ۺ񼱳�����",
                "NULL"          => "��ۺ�",
                "memo"          => "���䱸",
                "trans_corp"    => "�ù��",
                "trans_no"      => "������ȣ",
      );

      return $arr_items;
   }
   //////////////////////////////////////////////////
   // mple
   // date: 2006.5.25
   // 2006.12.9
   function init_49( &$header, &$file_format )
   {
      $file_format = "xls";
      $header = "��۴����\n";
      $arr_items = array (
          "no"           => "�Ϸù�ȣ",       // A
          "product_id"   => "��Ϲ�ȣ",       // B
          "order_id"     => "�ŷ���ȣ",       // C
          "sale_type"    => "�ǸŹ��",       // D
          "code1"        => "�Ǹ��ڻ�ǰ�ڵ�", // E
          "product_name" => "��ǰ��",         // F
          "qty"          => "����",           // G
          "amount"       => "�ݾ�",           // H
          "order_date"   => "�����Ͻ�",       // I
          "x"		 => "���ֹ���",          
          "su_price"     => "���꿹���ݾ�",          
          "code2"        => "�Ÿż�����",
          "code3"        => "������ID",       // M
          "order_name"   => "�������̸�",     // N
          "order_tel"    => "����ó1",        // O
          "order_mobile" => "����ó2",        // P
          "recv_name"    => "������",         // Q
          "recv_tel"     => "����ó1",        // R
          "recv_mobile"  => "����ó2",        // S
          "recv_zip"     => "�����ȣ",       // T
          "recv_address" => "�ּ�",           // U
          "options"      => "���û���",       // V
          "memo1"        => "�߰��ֹ�����",   // W
          "code4"        => "�ֹ��ÿ�û����", // X
          "code5"        => "������ ����",    // Y
          "trans_who"    => "��ۺ�δ�",     // Z
          "code6"        => "������ۺ�",     // AA
          "code7"        => "���ع����",     // AB
          "recv_date"    => "����������",     // AC
          "trans_no"     => "�����ȣ",       // AD
      );
      return $arr_items;
   }


   //////////////////////////////////////////////////
   // ezAdmin 
   // date: 2006.4.21 -jk
   function init_90( &$header, &$file_format )
   {
      $file_format = "csv";

      $arr_items = array ( 
          "order_id"	=> "�ֹ���ȣ",
	  "trans_no"	=> "�����ȣ",
      );
      return $arr_items;
   }


   function init_83( &$header, &$file_format )
   {
	if ( _DOMAIN_ == "codipia" )
	{
		$file_format = "xls";
		$arr_items = array (
			"order_id"              => "�ֹ���ȣ(��ǰ��)",
			"1"                     => "�ֹ������ڵ�",
			"user_defined"          => "����ù��:�ϳ����ù�",
			"trans_no"              => "�����ȣ",
		);  
	}
	else if ( _DOMAIN_ == "midan" )
	{
	        $file_format = "xls";
		$arr_items = array (
			trans_no 	=> "�����ȣ",
			user_defined    => "�ù��:130",
			trans_date 	=> "����غ���",
			order_id	=> "�ֹ���ȣ",
			order_date	=>"�ֹ���",
			order_name	=>"�ֹ���",
			product_name	=>"��ǰ��",
			options 	=> "�ɼ�",
			qty     	=> "����",
			shop_product_id => "��ǰ�ڵ�",
			user_defined1 	=> "���޾�ü:(��)�̴ܶ���",	// K
			user_defined2    => "�Աݻ���:�����Ϸ�",
			user_defined3    => "�ֹ��ݾ�:9900",
			x4  		=> "���翩��",
			x5  		=> "��������",
			recv_name 	=> "������",
			recv_tel   	=> "��ȭ��ȣ",
			recv_mobile 	=> "�޴���",
			recv_zip 	=> "�����ȣ",
			recv_address 	=> "�ּ�",
			code5        	=> "���ּ�",
			memo        	=> "��ۿ䱸����",
			user_defined4    => "�̸���:gosajang@midan.com",
			code1      	=> "�ֹ��󼼹�ȣ",
			code2 		=> "�ֹ�ID",
		);
	}
        else
	{
	    $file_format = "xls";
	    $arr_items = $this->init_6( &$header, &$file_format );
	}
	return $arr_items;
   }

   //===============================================
   // date: 2007.3.8 
   function init_89( &$header, &$file_format )
   {
	// ================================
	// init_81�� ������
	// date: 2007.7.8 -jk.ryu
	if ( _DOMAIN_ == "js" )
	{
      		$file_format = "xls";
		$arr_items = array (
			"order_id"              => "�ֹ���ȣ",
			"code2"                 => "�ֹ� ��ǥ����",
			"order_name"            => "�ֹ��� ����",
			"recv_name"             => "������ �̸�",
			"user_defined"     => "�ù�� �ڵ�:9006",
			"trans_no"              => "�����ȣ",
		);  

		return $arr_items;
	}
    }

   //===============================================
   // ������
   // date: 2007.3.8 
   function init_80( &$header, &$file_format )
   {

	// ================================
	// init_81�� ������
	// date: 2007.4.6 -jk.ryu
	if ( _DOMAIN_ == "zen" )
	{

      		$file_format = "xls";
		$arr_items = array (
			"code1"                 => "�ֹ���ȣ",
			"shop_product_id"       => "�ֹ���ǰ��ȣ",
			"trans_code"            => "�ù��",
			"trans_no"              => "�����ȣ",
		);  

		return $arr_items;
	}
	else if ( _DOMAIN_ == "codipia" || _DOMAIN_ == "bigtree" )
	{
      		$file_format = "csv";
		$arr_items = array (
			"order_id"            => "������ȣ",
			"code3"               => "����ó",
			"trans_name"          => "��ۻ��",
			"trans_no"            => "�����ȣ",
			""                    => "�����ĵ��",
		);  

		return $arr_items;
 	}
	else if ( _DOMAIN_ == "milkcoco" )
 	{
		// shop name: 1300k
		$file_format = "csv";
		$arr_items = array (
		    order_id 	  =>"�ֹ���ȣ"
		    ,shop_product_id   =>"��ǰ�ڵ�"
		    ,trans_no     =>"�����ȣ"
		    ,trans_date_pos=>"�������"
		);
	
		return $arr_items;

	}
	else if ( _DOMAIN_ == "limegn" )
	{
      		$file_format = "xls";
		$arr_items = array (
			"order_date"	=> "�ֹ���¥",
			"order_id"	=> "�ֹ���ȣ",  // B
			"shop_product_id"	=> "�ֹ���ǰ��ȣ",  // C
			"order_name"	=> "�ֹ���",  // D
			"recv_name"		=> "������",	// e
			"recv_tel"		=> "��������ȭ��ȣ1",	// f
			"recv_mobile"		=> "��������ȭ��ȣ2",	// g
			"recv_zip"		=> "�����ȣ",		// h
			"recv_address"		=> "�������ּ�",		// i
			"user_defined"        	=> "�귣���:LimeGreen", // j
			"product_name"		=> "��ǰ��", // k
			"options"		=> "�ɼ�", // l
			"qty"			=> "����", // m
			"shop_price"		=> "�ǸŰ�",
			"supply_price"		=> "���ް�",
			"amount"		=> "���ǸŰ�",
			"total_amount"		=> "�Ѱ��ް�",	// q
			"memo"			=> "���䱸����",
			"trans_who"		=> "��ۺ�",	// s
			"trans_name"		=> "�ù���",
			"trans_no"		=> "�����ȣ",
		);
	
		return $arr_items;
 	}
	else if ( _DOMAIN_ == "cbj0111" )
	{
      		$file_format = "xls";
		$arr_items = array (
			"no"        	=> "��ȣ",
			"order_id"	=> "�ֹ���ȣ",
			"order_name"	=> "�ֹ��ڸ�",  // C
			"email"        	=> "�̸���",	// D
			"order_tel"		=> "�ֹ�����ȭ��ȣ",   	// e
			"order_mobile"		=> "�ֹ����ڵ���",   	// f
			"recv_name"		=> "�޴º��̸�",	// g
			"recv_tel"		=> "�޴º���ȭ��ȣ",	// h
			"recv_mobile"		=> "�޴º��ڵ���",	// i
			"recv_zip"		=> "�����ȣ",		// j
			"recv_address"		=> "�ּ�",		// k
			"memo"			=> "��۸޼���",	// L
			"empty2"		=> "��������",		// m
			"shop_price"		=> "�����ݾ�",		// n
			"order_date"        	=> "�ֹ�����",
			"user_defined"		=> "�ֹ�����:����غ���",
			"user_defined1"		=> "����ڵ�:6",
			"trans_no"		=> "�����ȣ",
			"cy_trans_date"	        => "�����",	// yyyy-mm-dd
		);
	
		return $arr_items;
 	}
	else if ( _DOMAIN_ == "shophouse" )
	{
      		$file_format = "xls";
		/*
		$arr_items = array (
			"code10"            	=> "ó������",
			"trans_date_pos"	=> "���/��ǰ��������",
			"trans_name"		=> "�ù��",	
			"trans_no"		=> "������ȣ",
			"product_name"		=> "��ǰ��",
			"product_id"		=> "���/��ǰ��������",
			"options"		=> "Color",
			"options2"		=> "Size",
			"qty" 			=> "����",
			"market_price"		=> "�ǸŰ�",
			"order_name"		=> "�ֹ���",
			"recv_name"		=> "������",
		);  
		*/
		$arr_items = array (
			"order_date"        => "�����������",
			"shop_product_id"	=> "Item No",
			"trans_name"		=> "�ù��",	
			"trans_no"		=> "������ȣ",
			"order_id"		=> "Invoice��ȣ",
			"product_name"		=> "��ǰ��",
			"options"		=> "Color",
			"options2"		=> "Size",
			"qty" 			=> "����",
			"shop_price"		=> "�ǸŰ�",
			"order_name"		=> "�ֹ���",   		// K
			"order_tel"		=> "�ֹ��� ����ó",   	// L
			"order_mobile"		=> "�ֹ��� �޴���",   	// L
			"recv_name"		=> "������",
			"recv_zip"		=> "������ �����ȣ",
			"recv_address"		=> "������ �ּ�",
			"recv_tel"		=> "������ ����ó",
			"recv_mobile"		=> "������ �޴���",
			"memo"			=> "��޸޽���",
			"empty1"		=> "�����޽���",
			"empty2"		=> "�޴»���޽���",
			"user_defined"		=> "Ȯ������:Ȯ��",
		);
	
		return $arr_items;
 	}
	else if ( _DOMAIN_ == "ssueim" )
	{
      		$file_format = "xls";
		$arr_items = array (
			"order_id"            => "�ֹ���ȣ",
			"trans_no"            => "�����ȣ",
		);  

		return $arr_items;
 	}
	else if ( _DOMAIN_ == "sccompany" )
	{
      		$file_format = "csv";
		$arr_items = array (
			"order_id"            => "������ȣ",
			"trans_no"            => "�����ȣ",
		);  

		return $arr_items;
 	}
	else if ( _DOMAIN_ == "leedb" )
	{
		$file_format = "xls";
		$header = -99;
		$arr_items = array (
			"trans_no"	=> "�����ȣ",
			"trans_name"	=> "�ù��",
			"user_defined"	=> "���",
			"order_id"	=> "�ֹ���ȣ",
			"recv_name"	=> "������",
		);
		return $arr_items;
	}
	else if ( _DOMAIN_ == "pnb" )
	{
	    $file_format = "xls";
	    $arr_items = array ( 
		"trans_no"	=> "�����ȣ",		// a
		"user_defined"  => "�ù��:170",	// b
		"collect_date"  => "����غ���",	// c
		"order_id"	=> "�ֹ���ȣ",		// d
		"order_date"	=> "�ֹ���",		// e
		"order_name"	=> "�ֹ���",		// f
		"product_name"   => "��ǰ��",		// g
		"options"	=> "�ɼ�",		// h
		"qty"		=> "����",		// i
		"shop_product_id"	=> "��ǰ�ڵ�",		// j
		"user_defined2"	=> "���޾�ü:(��)�Ǿغ�����", // k
		"user_defined3" => "�Աݻ���:�����Ϸ�",  // l
		"shop_price"	=> "�ֹ��ݾ�",		// m
		"user_defined4" => "���翩��:�����Ϸ�", // n
		"x"		=> "��������",		// o
		"recv_name"	=> "������",		// p
		"recv_tel"	=> "��ȭ��ȣ",		// q
		"recv_mobile"	=> "�޴���",		// r
		"recv_zip"	=> "�����ȣ",		// s
		"recv_address"	=> "�ּ�",		// t
		"user_defined5" => "���ּ�:-",	// u
		"memo"		=> "��۽ÿ䱸����",	// v
		"email"		=> "�̸���",
		"code1"		=> "�ֹ��󼼹�ȣ",	// x
		"code2"		=> "�ֹ�ID",		// y
	      	);
	    return $arr_items;
	}

	else if ( _DOMAIN_ == "jnb" )
	{
	    $file_format = "xls";
	    $arr_items = array ( 
          	  "shop_name"      => "No",
		  "product_id"     => "��ǰ�ڵ�",
		  "order_id"	   => "�ֹ���ȣ", // C
		  "product_name"   => "��ǰ��",
		  "order_name"	   => "�ֹ���",
		  "collect_date"   => "�ֹ�Ȯ����",
		  "empty1"	   => "��ü���̵�",
          	  "user_defined"   => "�⺻�ù��:�����ͽ��������ù�",
		  "trans_no"	   => "�����ȣ",
		  "code1"	   => "��ü��"
	      	);
	    return $arr_items;
	}
        else if ( _DOMAIN_ == "alicegohome" )
	{
	        // ������ ��
		$file_format = "xls";
	      	$arr_items = array ( 
		  "order_id"	   => "�ֹ���ȣ", // D
                  "code7"	   => "�����ڵ�",
                  "code8"	   => "�Ա�Ȯ�νð�",
		  "order_name"	   => "�Ա��ڸ�",
           	  "user_defined"   => "�ù��ڵ�:17",
		  "trans_no"       => "�����ȣ",
		  "trans_date_pos" => "�������",
           	  "user_defined1"   => "�̸���:1",
           	  "user_defined2"   => "SMS:1",
	      	);
	      	return $arr_items;
	}
	else if ( _DOMAIN_ == "yokkun" or _DOMAIN_ == "sj" )
	{
		// �м� ��
		$file_format = "xls";
	      	$arr_items = array ( 
          	  "order_date"     => "�ֹ���¥",
		  "order_id"	   => "�ֹ���ȣ", // B
		  "shop_product_id"	   => "�ֹ���ǰ��ȣ", // C
		  "order_name"	   => "�ֹ���",   // D
		  "recv_name"	   => "������",	 // E
		  "recv_tel"	   => "��������ȭ��ȣ1",	 // F
		  "recv_mobile"	   => "��������ȭ��ȣ2",	 // G
		  "recv_zip"	   => "�����ȣ",	 // G
		  "recv_address"   => "�������ּ�",	 // I
		  "code1"	   => "�귣���",	// J
		  "product_name"   => "��ǰ��",		// K
		  "options"	   => "�ɼ�",		// L
		  "qty"		   => "����",		// M
		  "shop_price"	   => "�ǸŰ�",		// N
 		  "supply_price"   => "���ް�",		// O
		  "amount"	   => "���ǸŰ�",  	// P
 		  "none1"   => "�Ѱ��ް�", 	// Q
		  "memo"	   => "���䱸����",   // R
		  "org_trans_who"	   => "��ۺ�",		// S
		  "trans_name"	   => "�ù���",
		  "trans_no"	   => "�����ȣ"
	      	);
	      	return $arr_items;
	}
	else if ( _DOMAIN_ == "andstyle" || _DOMAIN_ == "asa" )
	{
	        // ������ ����
		$file_format = "xls";
	      	$arr_items = array ( 
          	  "user_defined"   => "�ù��:20995",
		  "trans_no"       => "������ȣ",
                  "code7"	   => "�Ϸù�ȣ",
		  "order_id"	   => "�ֹ���ȣ", // D
		  "order_name"	   => "�ֹ���",
		  "recv_name"	   => "������",
		  "product_name"   => "��ǰ��",
                  "options"	   => "��ǰ��",
		  "empty2" 	   => "�𵨸�",
		  "shop_price" 	   => "�ǸŴܰ�",
		  "amount" 	   => "�Ǹűݾ�",
	      	);
	      	return $arr_items;
	}
	else
	{
		$file_format = "csv";
		$header = "";

		$arr_items = array ( 
			"code3"	=> "SeqNo",    		// a
			"order_id" => "�ֹ���ȣ",
			"code1"	=> "�ֹ�����",
			"shop_product_id" => "��ǰ�ڵ�",
			"shop_product_name" => "��ǰ��",
			"options"	=> "�ɼ�",
			"shop_price"	=> "�ǸŰ�",
			"qty"	=> "����",
			"code2" => "������ID",
			"order_name" => "�̸�",        // j
			"empty1"	=> "�������", // k
			"recv_zip"	=> "�����������ȣ", // l
			"recv_address"	=> "�������ּ�", // m
			"recv_name"	=> "������",	 // n
			"recv_tel"	=> "��ȭ��ȣ",
			"recv_mobile"	=> "�ڵ���",
			"memo"	=> "�ֹ���û����",
			"trans_no" => "�����ȣ",
			"order_date" => "����(�Ա�)����", // s
		);

		return $arr_items;
	}
   }


?>
