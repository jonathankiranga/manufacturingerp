<<<<<<< HEAD
<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Sales Invoice');

if(isset($_GET['No'])){
    
$INVOICEno = GetDeliveries($_GET['No']);
    
$SQL="select
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[oderdate]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid]
      ,[debtors].email
      ,[debtors].city
      ,[debtors].postcode
      ,[debtors].country
      ,[debtors].phone
      ,[debtors].contact
      ,[SalesHeader].[yourreference]
      ,[SalesHeader].[printed]
      ,[debtors].middlen
      ,[SalesHeader].packagescharge
       ,[SalesHeader].[picture]
      from [SalesHeader] join SalesLine on 
      [SalesHeader].[documentno]=SalesLine.[documentno] 
      join debtors on [SalesHeader].[customercode]=debtors.itemcode
      where [SalesHeader].[documenttype]='10' 
      and [SalesHeader].[documentno]='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    $urlString=$myrow[21];
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="INVOICE";
    
    $pdf->addInfo('Title',_('Sales Invoice'));
    $pdf->addInfo('Subject',_('Sales Invoice'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
    $pdf->SetFont('helvetica', 'B',12); // (font, style, size)

    include('includes/PDFInvoiceheader.inc');
          
     $SQL=Sprintf("SELECT 
       stockmaster.barcode
       ,Container.[descrip] as containername
      ,SalesHeader.docdate
      ,SalesLine.[description] 
      ,salesline.Quantity,
       SalesLine.UnitPrice as Sp ,
       [SalesLine].unitofmeasure as UOMDesc
      ,SalesHeader.[documentno]
      ,SalesHeader.[customercode]
      ,SalesLine.invoiceamount
      ,SalesHeader.[currencycode]
      ,SalesLine.vatamount
      ,SalesLine.invoiceamount-SalesLine.vatamount as netamt
      ,SalesHeader.[documenttype]
      ,salesline.vatrate
      ,salesline.[totalchargedcontainers]
      ,salesline.[shipping]
      ,SalesHeader.[QtyDiscount]
      ,SalesLine.[partperunit]
      ,SalesLine.code as itemcode
      ,salesline.packagescharge
  FROM [SalesHeader] 
  join SalesLine  on SalesHeader.documentno=SalesLine.documentno 
  and SalesHeader.documenttype=SalesLine.documenttype and SalesHeader.documenttype=10
  join stockmaster on stockmaster.itemcode=SalesLine.code
  left join stockmaster Container on Container.itemcode=SalesLine.containercode
        where SalesHeader.documentno='%s' ",$_GET['No']);
     
     $FontSize = 10;
     $YPos=$firstrowpos-20;
     $R1=0;
     $R2=0;
     $R3=0;
     $R4=0;
     $R5=0;
     $SalesAddCategory =New SalesAddCategory();
     
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         $R1 +=$rows['vatamount'];
         $R2 +=$rows['netamt'];
         $R3 +=$rows['invoiceamount'];
         $R4 +=$rows['shipping'];
         $R5 +=$rows['packagescharge'];
         $ppu=(int)$rows['partperunit'];
         
         if($ppu>1){
           $units=$rows['UOMDesc'].' (1x'.$ppu.' lts)';
         }else{
            $units=$rows['UOMDesc'];
         }
          $PRICE = $rows['Sp'];
          $qty=(int)$rows['Quantity'];
           
          $roadgrade = $SalesAddCategory->AddPrefix($rows['itemcode']);
     
          $LeftOvers = $pdf->addTextWrap(42, $YPos,50, $FontSize, $rows['barcode'],'left');
          $LeftOversDescription = $pdf->addTextWrap(92, $YPos,150, $FontSize, $roadgrade.$rows['description'],'left');
          $LeftOversDesc = $pdf->addTextWrap(260, $YPos,50, $FontSize,$units,'left');
          $LeftOvers = $pdf->addTextWrap(300, $YPos, 50, $FontSize,$qty,'right');
          $LeftOvers = $pdf->addTextWrap(355, $YPos, 50, $FontSize, number_format($PRICE,2),'right');
          $LeftOvers = $pdf->addTextWrap(390, $YPos, 50, $FontSize, number_format($rows['vatrate'],0),'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 70, $FontSize, number_format($rows['vatamount'],2),'right');
          $LeftOvers = $pdf->addTextWrap(490, $YPos, 70, $FontSize, number_format($rows['netamt']-($rows['shipping']+$rows['packagescharge']),2),'right');

          
            while((strlen($LeftOversDesc) > 0) or (strlen($LeftOversDescription) > 0)) { // If translated text is greater than 103, prints remainder
               $YPos -= $line_height;
               $LeftOversDescription = $pdf->addTextWrap(92,$YPos,150, $FontSize,$LeftOversDescription,'left');
               $LeftOversDesc = $pdf->addTextWrap(260,$YPos,50, $FontSize,$LeftOversDesc,'left');
	    }
            
         $YPos -= $line_height *1.2;
         if($YPos< $lastrow){
             $PageNumber++;
             include('includes/PDFInvoiceheader.inc');
              $YPos=$firstrowpos;
         }
       
     }
     
        $zYpos = $tableBottom+100;
        $LeftOvers = $pdf->addTextWrap(492,$zYpos,70,10, number_format($R2-($R4+$R5),2),'right');
        $zYpos -=$line_height *1.8;
        $LeftOvers = $pdf->addTextWrap(492,$zYpos,70,10, number_format($R1,2),'right');
        $zYpos -=$line_height *2.2;
        $LeftOvers = $pdf->addTextWrap(492,$zYpos,70,10, number_format($R4,2),'right');
        $zYpos -=$line_height *2.0;
        $LeftOvers = $pdf->addTextWrap(492,$zYpos,70,10, number_format($R5,2),'right');
        $zYpos -=$line_height *2.2 ;
        $LeftOvers = $pdf->addTextWrap(492,$zYpos,70,10, number_format($R3,2),'right');
        
        if($rows['totalchargedcontainers']>0){
            $zYpos = $tableBottom+80;
            $footer = sprintf("NOTE:%s Are Charged Seperately",$rows['containername']);
            $LeftOvers = $pdf->addTextWrap(42,$zYpos,1000,8,$footer,'left');
            $footer = sprintf("Value of %s is %s",$rows['containername'],number_format($rows['totalchargedcontainers'],2));
            $LeftOvers = $pdf->addTextWrap(42,$zYpos-= $line_height,1000,8,$footer,'left');
            $footer = sprintf("Total Invoice value  %s ",number_format($rows['totalchargedcontainers']+$R3,2));
            $LeftOvers = $pdf->addTextWrap(42,$zYpos-= $line_height,1000,8,$footer,'left');
          }
            
    $pdf->OutputD($_SESSION['DatabaseName'] . '_INVOICE_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
    DB_query("Update [SalesHeader] set printed=1 where SalesHeader.documenttype=10 and SalesHeader.documentno='".$_GET['No']."'", $db);
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';



echo '<div class="container">'
    . '<table class="table table-bordered"><tr>'
    . '<td><input type="submit" value="Refresh" id="f1lt3r" class="btn-info" />'
    . '<input type="hidden" name="CustomerID" id="CustomerID"/></td>'
    . '<td><input type="button" id="filtercustomer" value="Search Customer" class="btn-info" />Customer Name</td>'
    . '<td><input type="text" name="CustomerName" id="CustomerName" readonly="readonly"/></td></tr>'
    . '<tr><td colspan="2"><input type="submit" id="filterdocumentno" name="filterdocumentno" value="Search Invoice No" class="btn-info"/>Filter By Document No</td>'
    . '<td><input type="text" name="documentno" id="documentno"/></td></tr>'
    . '</table></div>';


$SQL="select top ". $_SESSION['DefaultDisplayRecordsMax'] ."
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[oderdate]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid] ,
      sum(SalesLine.[invoiceamount]) as OrderValue
      from [SalesHeader] join SalesLine on 
      [SalesHeader].[documentno]=SalesLine.[documentno] 
      where [SalesHeader].[documenttype]='10' ".    
      ((mb_strlen($_POST['CustomerID'])>0)?" and [SalesHeader].[customercode]='".$_POST['CustomerID']."'":'').
      ((mb_strlen($_POST['documentno'])>0)?" and [SalesHeader].[documentno] like '%".$_POST['documentno']."%'":'')."
       group by 
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[oderdate]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid]    
      order by 
      [SalesHeader].[docdate] desc";
    $Result=DB_query($SQL,$db);
       
    
    Echo '<div class="container">'
             . '<Table class="table table-bordered"><tr>'
             . '<th>Invoice <br />No</th>' 
             . '<th>Date</th>'
             . '<th>Customer <br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Sales <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Sales<br /> Person</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
               . '<th>Pictures</th>'
             . '</tr>';
    
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
           
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
              
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['salespersoncode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo sprintf('<td>%s</td>',getiamge($row['documentno']));
         
        echo '</tr>';
  }
        
echo '</table></DIV>';
echo '</div></form>';

include('includes/footer.inc');

}   


Function GetDeliveries($INVOICEno){
    global $db;
    //[yourreference] //19
    //[externaldocumentno]//10
    
    $SQL="select top 1
        [SalesHeader].[externaldocumentno] as salesorder
      from [SalesHeader] where [SalesHeader].[documenttype]='10'
      and [SalesHeader].[documentno]='".$INVOICEno."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    $ref=$myrow[0];
    
    $array=array();
    $SQL="select
        [SalesHeader].[documentno] as deleiveries
      from [SalesHeader] where [SalesHeader].[documenttype]='19' 
      and [SalesHeader].[yourreference]='".$ref."'";
    $Result=DB_query($SQL,$db);
    while($myrow= DB_fetch_array($Result)){
        $array[]=$myrow['deleiveries'];
    }
    
    $return='';
    foreach ($array as $value) {
        $return .= $value."";
    }
    return $return;
}

function getiamge($ref){
    global $host,$database,$DBUser,$DBPassword;
   $dbimage = odbc_connect("Driver={SQL Server};Server=$host;Database=$database;",trim($DBUser),trim($DBPassword));
  
    $SQL=sprintf("SELECT [picture] FROM [SalesHeader] where documentno='%s'",trim($ref));
     $Result=DB_query($SQL,$dbimage);
      $myrow=DB_fetch_row($Result);
    $urlString=$myrow[0];
     $images="";
          $urls = json_decode($urlString,TRUE);
        if (is_array($urls) && count($urls) > 0) {
            foreach ($urls as $url) {
                 $validUrl = htmlspecialchars($url,ENT_QUOTES,'utf-8'); // Decode the HTML entities
                 $validUrl = urldecode($url); // Decode the URL-encoded characters
                 $validUrl = str_replace('[', '' ,$validUrl);
                 $validUrl = str_replace( ']', '' ,$validUrl);
                 $validUrl = str_replace( '&quot;', '' ,$validUrl);
                 $images .= sprintf('<img src="%s" width="50" alt="Image">',$validUrl);
            }
           
        } 
        
        return $images;
}
=======
<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Sales Invoice');

if(isset($_GET['No'])){
    
$INVOICEno = GetDeliveries($_GET['No']);
    
$SQL="select
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[oderdate]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid]
      ,[debtors].email
      ,[debtors].city
      ,[debtors].postcode
      ,[debtors].country
      ,[debtors].phone
      ,[debtors].contact
      ,[SalesHeader].[yourreference]
      ,[SalesHeader].[printed]
      ,[debtors].middlen
      ,[SalesHeader].packagescharge
       ,[SalesHeader].[picture]
      from [SalesHeader] join SalesLine on 
      [SalesHeader].[documentno]=SalesLine.[documentno] 
      join debtors on [SalesHeader].[customercode]=debtors.itemcode
      where [SalesHeader].[documenttype]='10' 
      and [SalesHeader].[documentno]='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    $urlString=$myrow[21];
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="INVOICE";
    
    $pdf->addInfo('Title',_('Sales Invoice'));
    $pdf->addInfo('Subject',_('Sales Invoice'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
    $pdf->SetFont('helvetica', 'B',12); // (font, style, size)

    include('includes/PDFInvoiceheader.inc');
          
     $SQL=Sprintf("SELECT 
       stockmaster.barcode
       ,Container.[descrip] as containername
      ,SalesHeader.docdate
      ,SalesLine.[description] 
      ,salesline.Quantity,
       SalesLine.UnitPrice as Sp ,
       [SalesLine].unitofmeasure as UOMDesc
      ,SalesHeader.[documentno]
      ,SalesHeader.[customercode]
      ,SalesLine.invoiceamount
      ,SalesHeader.[currencycode]
      ,SalesLine.vatamount
      ,SalesLine.invoiceamount-SalesLine.vatamount as netamt
      ,SalesHeader.[documenttype]
      ,salesline.vatrate
      ,salesline.[totalchargedcontainers]
      ,salesline.[shipping]
      ,SalesHeader.[QtyDiscount]
      ,SalesLine.[partperunit]
      ,SalesLine.code as itemcode
      ,salesline.packagescharge
  FROM [SalesHeader] 
  join SalesLine  on SalesHeader.documentno=SalesLine.documentno 
  and SalesHeader.documenttype=SalesLine.documenttype and SalesHeader.documenttype=10
  join stockmaster on stockmaster.itemcode=SalesLine.code
  left join stockmaster Container on Container.itemcode=SalesLine.containercode
        where SalesHeader.documentno='%s' ",$_GET['No']);
     
     $FontSize = 10;
     $YPos=$firstrowpos-20;
     $R1=0;
     $R2=0;
     $R3=0;
     $R4=0;
     $R5=0;
     $SalesAddCategory =New SalesAddCategory();
     
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         $R1 +=$rows['vatamount'];
         $R2 +=$rows['netamt'];
         $R3 +=$rows['invoiceamount'];
         $R4 +=$rows['shipping'];
         $R5 +=$rows['packagescharge'];
         $ppu=(int)$rows['partperunit'];
         
         if($ppu>1){
           $units=$rows['UOMDesc'].' (1x'.$ppu.' lts)';
         }else{
            $units=$rows['UOMDesc'];
         }
          $PRICE = $rows['Sp'];
          $qty=(int)$rows['Quantity'];
           
          $roadgrade = $SalesAddCategory->AddPrefix($rows['itemcode']);
     
          $LeftOvers = $pdf->addTextWrap(42, $YPos,50, $FontSize, $rows['barcode'],'left');
          $LeftOversDescription = $pdf->addTextWrap(92, $YPos,150, $FontSize, $roadgrade.$rows['description'],'left');
          $LeftOversDesc = $pdf->addTextWrap(260, $YPos,50, $FontSize,$units,'left');
          $LeftOvers = $pdf->addTextWrap(300, $YPos, 50, $FontSize,$qty,'right');
          $LeftOvers = $pdf->addTextWrap(355, $YPos, 50, $FontSize, number_format($PRICE,2),'right');
          $LeftOvers = $pdf->addTextWrap(390, $YPos, 50, $FontSize, number_format($rows['vatrate'],0),'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 70, $FontSize, number_format($rows['vatamount'],2),'right');
          $LeftOvers = $pdf->addTextWrap(490, $YPos, 70, $FontSize, number_format($rows['netamt']-($rows['shipping']+$rows['packagescharge']),2),'right');

          
            while((strlen($LeftOversDesc) > 0) or (strlen($LeftOversDescription) > 0)) { // If translated text is greater than 103, prints remainder
               $YPos -= $line_height;
               $LeftOversDescription = $pdf->addTextWrap(92,$YPos,150, $FontSize,$LeftOversDescription,'left');
               $LeftOversDesc = $pdf->addTextWrap(260,$YPos,50, $FontSize,$LeftOversDesc,'left');
	    }
            
         $YPos -= $line_height *1.2;
         if($YPos< $lastrow){
             $PageNumber++;
             include('includes/PDFInvoiceheader.inc');
              $YPos=$firstrowpos;
         }
       
     }
     
        $zYpos = $tableBottom+100;
        $LeftOvers = $pdf->addTextWrap(492,$zYpos,70,10, number_format($R2-($R4+$R5),2),'right');
        $zYpos -=$line_height *1.8;
        $LeftOvers = $pdf->addTextWrap(492,$zYpos,70,10, number_format($R1,2),'right');
        $zYpos -=$line_height *2.2;
        $LeftOvers = $pdf->addTextWrap(492,$zYpos,70,10, number_format($R4,2),'right');
        $zYpos -=$line_height *2.0;
        $LeftOvers = $pdf->addTextWrap(492,$zYpos,70,10, number_format($R5,2),'right');
        $zYpos -=$line_height *2.2 ;
        $LeftOvers = $pdf->addTextWrap(492,$zYpos,70,10, number_format($R3,2),'right');
        
        if($rows['totalchargedcontainers']>0){
            $zYpos = $tableBottom+80;
            $footer = sprintf("NOTE:%s Are Charged Seperately",$rows['containername']);
            $LeftOvers = $pdf->addTextWrap(42,$zYpos,1000,8,$footer,'left');
            $footer = sprintf("Value of %s is %s",$rows['containername'],number_format($rows['totalchargedcontainers'],2));
            $LeftOvers = $pdf->addTextWrap(42,$zYpos-= $line_height,1000,8,$footer,'left');
            $footer = sprintf("Total Invoice value  %s ",number_format($rows['totalchargedcontainers']+$R3,2));
            $LeftOvers = $pdf->addTextWrap(42,$zYpos-= $line_height,1000,8,$footer,'left');
          }
            
    $pdf->OutputD($_SESSION['DatabaseName'] . '_INVOICE_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
    DB_query("Update [SalesHeader] set printed=1 where SalesHeader.documenttype=10 and SalesHeader.documentno='".$_GET['No']."'", $db);
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';



echo '<div class="container">'
    . '<table class="table table-bordered"><tr>'
    . '<td><input type="submit" value="Refresh" id="f1lt3r" class="btn-info" />'
    . '<input type="hidden" name="CustomerID" id="CustomerID"/></td>'
    . '<td><input type="button" id="filtercustomer" value="Search Customer" class="btn-info" />Customer Name</td>'
    . '<td><input type="text" name="CustomerName" id="CustomerName" readonly="readonly"/></td></tr>'
    . '<tr><td colspan="2"><input type="submit" id="filterdocumentno" name="filterdocumentno" value="Search Invoice No" class="btn-info"/>Filter By Document No</td>'
    . '<td><input type="text" name="documentno" id="documentno"/></td></tr>'
    . '</table></div>';


$SQL="select top ". $_SESSION['DefaultDisplayRecordsMax'] ."
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[oderdate]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid] ,
      sum(SalesLine.[invoiceamount]) as OrderValue
      from [SalesHeader] join SalesLine on 
      [SalesHeader].[documentno]=SalesLine.[documentno] 
      where [SalesHeader].[documenttype]='10' ".    
      ((mb_strlen($_POST['CustomerID'])>0)?" and [SalesHeader].[customercode]='".$_POST['CustomerID']."'":'').
      ((mb_strlen($_POST['documentno'])>0)?" and [SalesHeader].[documentno] like '%".$_POST['documentno']."%'":'')."
       group by 
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[oderdate]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid]    
      order by 
      [SalesHeader].[docdate] desc";
    $Result=DB_query($SQL,$db);
       
    
    Echo '<div class="container">'
             . '<Table class="table table-bordered"><tr>'
             . '<th>Invoice <br />No</th>' 
             . '<th>Date</th>'
             . '<th>Customer <br />ID</th>'
             . '<th>Customer<br /> Name</th>'
             . '<th>Sales <br />Order<br /> Value</th>'
             . '<th>Currency</th>'
             . '<th>Sales<br /> Person</th>'
             . '<th>Authorisation<br /> Status</th>'
             . '<th>Created<br /> By</th>'
               . '<th>Pictures</th>'
             . '</tr>';
    
  while($row=DB_fetch_array($Result)){
      echo '<tr>';
           
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',
        htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8'),$row['documentno'],$row['documentno']);
              
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        echo sprintf('<td>%s</td>',$row['currencycode']);
        echo sprintf('<td>%s</td>',$row['salespersoncode']);
        echo sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        echo sprintf('<td>%s</td>',$row['userid']);
        echo sprintf('<td>%s</td>',getiamge($row['documentno']));
         
        echo '</tr>';
  }
        
echo '</table></DIV>';
echo '</div></form>';

include('includes/footer.inc');

}   


Function GetDeliveries($INVOICEno){
    global $db;
    //[yourreference] //19
    //[externaldocumentno]//10
    
    $SQL="select top 1
        [SalesHeader].[externaldocumentno] as salesorder
      from [SalesHeader] where [SalesHeader].[documenttype]='10'
      and [SalesHeader].[documentno]='".$INVOICEno."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
    $ref=$myrow[0];
    
    $array=array();
    $SQL="select
        [SalesHeader].[documentno] as deleiveries
      from [SalesHeader] where [SalesHeader].[documenttype]='19' 
      and [SalesHeader].[yourreference]='".$ref."'";
    $Result=DB_query($SQL,$db);
    while($myrow= DB_fetch_array($Result)){
        $array[]=$myrow['deleiveries'];
    }
    
    $return='';
    foreach ($array as $value) {
        $return .= $value."";
    }
    return $return;
}

function getiamge($ref){
    global $host,$database,$DBUser,$DBPassword;
   $dbimage = odbc_connect("Driver={SQL Server};Server=$host;Database=$database;",trim($DBUser),trim($DBPassword));
  
    $SQL=sprintf("SELECT [picture] FROM [SalesHeader] where documentno='%s'",trim($ref));
     $Result=DB_query($SQL,$dbimage);
      $myrow=DB_fetch_row($Result);
    $urlString=$myrow[0];
     $images="";
          $urls = json_decode($urlString,TRUE);
        if (is_array($urls) && count($urls) > 0) {
            foreach ($urls as $url) {
                 $validUrl = htmlspecialchars($url,ENT_QUOTES,'utf-8'); // Decode the HTML entities
                 $validUrl = urldecode($url); // Decode the URL-encoded characters
                 $validUrl = str_replace('[', '' ,$validUrl);
                 $validUrl = str_replace( ']', '' ,$validUrl);
                 $validUrl = str_replace( '&quot;', '' ,$validUrl);
                 $images .= sprintf('<img src="%s" width="50" alt="Image">',$validUrl);
            }
           
        } 
        
        return $images;
}
>>>>>>> fcf1da09040591d58a8bc754034c8da36e93ca78
?>