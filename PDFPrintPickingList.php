<<<<<<< HEAD
<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
$Title = _('Print Picking List');
$SELFPAGE = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

if(isset($_GET['No'])){
    
$SQL="select 
       [SalesHeader].[yourreference]
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
      ,[SalesHeader].[documentno] as jobcard
      ,[SalesHeader].[picture] 
      from [SalesHeader]
      join [SalesLine] on [SalesHeader].[documentno]=[SalesLine].[documentno]
      join [debtors] on [SalesHeader].[customercode]=[debtors].[itemcode]
      where [SalesHeader].[documentno]='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
        $urlString=$myrow[18];
        
        
     $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="Delivery Note";
    
    $pdf->addInfo('Title',_('Sales Order'));
    $pdf->addInfo('Subject',_('Sales Order'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFPicklistheader.inc');
     
     
     $SQL=Sprintf("SELECT 
         stockmaster.barcode ,
         SalesHeader.docdate ,
         SalesLine.[description] ,
        [SalesLine].Quantity,
        [SalesLine].unitofmeasure as UOMDesc,
        [SalesLine].UnitPrice as Sp , 
        SalesHeader.[documentno] ,
        SalesHeader.[customercode] ,
        SalesHeader.[documenttype] 
        FROM [SalesHeader] 
        join SalesLine on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype 
        join stockmaster on stockmaster.itemcode=SalesLine.code
        where SalesHeader.documentno='%s' and SalesHeader.documenttype=19 ",$_GET['No']);
     
     $FontSize = 12;
     $YPos = $firstrowpos-20;
      
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,100, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(365, $YPos, 85, $FontSize, $rows['UOMDesc'],'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, number_format($rows['Quantity'],0),'right');
               
         $YPos -= $line_height * 2;
         if($YPos < $Bottom_Margin+100){
             $PageNumber++;
             
             include('includes/PDFPicklistheader.inc');
              $YPos = $firstrowpos;
         }
                
     }
     
   
     
       
    $pdf->OutputD($_SESSION['DatabaseName'] . '_Pickinglist_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.$SELFPAGE.'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


$SQL="Select top 50
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[yourreference]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid] 
      ,[SalesHeader].[picture] 
      from [SalesHeader] 
      where [SalesHeader].[documenttype]='19' 
      order by [SalesHeader].[documentno] desc";
    $Result=DB_query($SQL,$db);
       
    Echo '<table class="table-condensed table-responsive-small table-bordered"><tr>'
             . '<td>Pick List No</td>'
             . '<td>Sales Order No</td>' 
             . '<td>Pick on date</td>'
             . '<td>Customer ID</td>'
             . '<td>Customer Name</td>'
             . '<td>Sales Person</td>'
             . '<td>Created By</td>'
          . '<td></td>'
            . '</tr>';
  while($row=DB_fetch_array($Result)){
        echo '<tr>';
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',$SELFPAGE,$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',$row['yourreference']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',getSalemanDescrip($row['salespersoncode']));
        echo sprintf('<td>%s</td>',$row['userid']);
        echo sprintf('<td>%s</td>',getiamge($row['documentno']));
        echo '</tr>';
  }
        
    echo '</table><br />';
	
echo '</div></form>';

include('includes/footer.inc');

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
$Title = _('Print Picking List');
$SELFPAGE = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

if(isset($_GET['No'])){
    
$SQL="select 
       [SalesHeader].[yourreference]
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
      ,[SalesHeader].[documentno] as jobcard
      ,[SalesHeader].[picture] 
      from [SalesHeader]
      join [SalesLine] on [SalesHeader].[documentno]=[SalesLine].[documentno]
      join [debtors] on [SalesHeader].[customercode]=[debtors].[itemcode]
      where [SalesHeader].[documentno]='".$_GET['No']."'";
    $Result=DB_query($SQL,$db);
    $myrow=DB_fetch_row($Result);
        $urlString=$myrow[18];
        
        
     $PaperSize='A4';
    include('includes/PDFStarter.php');
    $headerName="Delivery Note";
    
    $pdf->addInfo('Title',_('Sales Order'));
    $pdf->addInfo('Subject',_('Sales Order'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 12;
    $PageNumber = 0;
    $line_height = 12;
        
     include('includes/PDFPicklistheader.inc');
     
     
     $SQL=Sprintf("SELECT 
         stockmaster.barcode ,
         SalesHeader.docdate ,
         SalesLine.[description] ,
        [SalesLine].Quantity,
        [SalesLine].unitofmeasure as UOMDesc,
        [SalesLine].UnitPrice as Sp , 
        SalesHeader.[documentno] ,
        SalesHeader.[customercode] ,
        SalesHeader.[documenttype] 
        FROM [SalesHeader] 
        join SalesLine on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype 
        join stockmaster on stockmaster.itemcode=SalesLine.code
        where SalesHeader.documentno='%s' and SalesHeader.documenttype=19 ",$_GET['No']);
     
     $FontSize = 12;
     $YPos = $firstrowpos-20;
      
     $Results=DB_query($SQL,$db);
     while($rows = DB_fetch_array($Results)){
         
          $LeftOvers = $pdf->addTextWrap(42, $YPos,100, $FontSize, $rows['barcode'],'left');
          $LeftOvers = $pdf->addTextWrap(145, $YPos,250, $FontSize, $rows['description'],'left');
          $LeftOvers = $pdf->addTextWrap(365, $YPos, 85, $FontSize, $rows['UOMDesc'],'right');
          $LeftOvers = $pdf->addTextWrap(420, $YPos, 85, $FontSize, number_format($rows['Quantity'],0),'right');
               
         $YPos -= $line_height * 2;
         if($YPos < $Bottom_Margin+100){
             $PageNumber++;
             
             include('includes/PDFPicklistheader.inc');
              $YPos = $firstrowpos;
         }
                
     }
     
   
     
       
    $pdf->OutputD($_SESSION['DatabaseName'] . '_Pickinglist_' . $_GET['No'] . '_' . date('Y-m-d').'.pdf');
    $pdf->__destruct();
    
}else{

include('includes/header.inc');

echo '<p class="page_title_text">'
. '<img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Search') . '" alt="" />' . ' ' . $Title . '</p>';

echo '<form autocomplete="off"action="'.$SELFPAGE.'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';


$SQL="Select top 50
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[yourreference]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid] 
      ,[SalesHeader].[picture] 
      from [SalesHeader] 
      where [SalesHeader].[documenttype]='19' 
      order by [SalesHeader].[documentno] desc";
    $Result=DB_query($SQL,$db);
       
    Echo '<table class="table-condensed table-responsive-small table-bordered"><tr>'
             . '<td>Pick List No</td>'
             . '<td>Sales Order No</td>' 
             . '<td>Pick on date</td>'
             . '<td>Customer ID</td>'
             . '<td>Customer Name</td>'
             . '<td>Sales Person</td>'
             . '<td>Created By</td>'
          . '<td></td>'
            . '</tr>';
  while($row=DB_fetch_array($Result)){
        echo '<tr>';
        echo sprintf('<td><a href="%s?No=%s">Print :%s</a></td>',$SELFPAGE,$row['documentno'],$row['documentno']);
        echo sprintf('<td>%s</td>',$row['yourreference']);
        echo sprintf('<td>%s</td>',is_null($row['docdate'])?'': ConvertSQLDate($row['docdate']));
        echo sprintf('<td>%s</td>',$row['customercode']);
        echo sprintf('<td>%s</td>',$row['customername']);
        echo sprintf('<td>%s</td>',getSalemanDescrip($row['salespersoncode']));
        echo sprintf('<td>%s</td>',$row['userid']);
        echo sprintf('<td>%s</td>',getiamge($row['documentno']));
        echo '</tr>';
  }
        
    echo '</table><br />';
	
echo '</div></form>';

include('includes/footer.inc');

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