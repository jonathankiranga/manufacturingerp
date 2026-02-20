<?php
  
echo '<form action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

$sqldebtors=DB_query("SELECT [itemcode] ,[creditlimit],[customer]
      ,[phone] ,[email] ,[city] ,[country],[curr_cod],[customerposting],[salesman],[VATinclusive]
       FROM [debtors] join postinggroups on code=[customerposting]
       where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];

$transstart = DB_Txn_Begin($db);
$PeriodNo = GetPeriod($_POST['date'],$db,true);
if(($_SESSION['ManualNumber']==0)){
  $_POST['documentno'] = GetNextTransNo(1,$db);
}

$_SESSION['CompleteDocument']=$_POST['documentno'];
$sql=array();

 $selectedImages = $_POST['selectedImages'];
 $decodedString = decodeHtmlEntities($selectedImages);
 $imagesArray = json_decode($decodedString);
 $imagesArray2 = json_encode($imagesArray);
 
 
$sql[]="INSERT INTO [SalesHeader]
           ([documenttype]
           ,[documentno]
           ,[docdate]
           ,[oderdate] 
           ,[duedate] 
           ,[customercode]
           ,[customername]
           ,[yourreference]
           ,[postinggroup]
           ,[currencycode]
           ,[salespersoncode]
           ,[status]
           ,[userid]
           ,[period]
           ,[vatinclusive]
           ,[locationcode]
           ,shipping
           ,packagescharge
           ,[picture])
     VALUES
           ('1'
           ,'".$_POST['documentno']."'
           ,'".FormatDateForSQL($_POST['date'])."'
           ".(Is_date($_POST['date'])?",'".FormatDateForSQL($_POST['date'])."'":'')."
           ".(Is_date($_POST['date'])?",'".FormatDateForSQL($_POST['date'])."'":'') ."
           ,'".$_POST['CustomerID']."'
           ,'".$_POST['CustomerName']."'
           ,'".$_POST['reference']."'
           ,'".$customerposting ."'
           ,'".$_POST['currencycode']."'
           ,'".$_POST['salespersoncode']."'
           ,'1'
           ,'".$_SESSION['UserID']."'
           ,'".$PeriodNo."'
           ,'".$VATinclusive."'
           ,'".$_POST['Bank_Code']."'
           ,'".$_POST['FREIGHT']."'
           ,'".$_POST['packagescharge']."'
           ,'".$imagesArray2."'"
        . ")" ;
 
    foreach ($_SESSION['sales_orders'] as $line_No => $rows ) {
       if($rows['grossamount']>0){

        $UnitCode    = trim($rows['uom']);
        $stockcode   = trim($rows['itemcode']);
        $Partperunit = (float) $rows['partsperunit'];
        $Inpacks     = $_SESSION['units'][$UnitCode]['descrip'];
        $qty         = (float) $rows['quantity'];
         
        $ResultIndex = DB_query("SELECT 
                            [stockmaster].[barcode],
                            [stockmaster].[itemcode],
                            [stockmaster].[descrip] as [stockname],
                            [stockmaster].[averagestock],
                            [stockmaster].[partperunit],
                            [vatcategory].[vat]
                      FROM [stockmaster] 
                      left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
                      left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
                      where  [stockmaster].[itemcode]='".$stockcode."'", $db);

       $stkmaster = DB_fetch_row($ResultIndex);
       $stockname = $stkmaster[2];
       $vatrate= $stkmaster[5];       
       $containervat=0; 
       $container = trim($rows['containerCode']);
       $containername = $_SESSION['stockmaster'][$containerCode]['descrip'];
  
        if(mb_strlen($container)>0){
              $emptyunits= $qty;
              $emptycode = $container;
              $emptycost = (float) $rows['emptyPrice'];
              $totalemptycost= $emptyunits * $emptycost;
        }    
    
          $PriceInPricelist = $rows['PriceInPricelist'];
          $salesprice   = $rows['salesprice'] ;
          $grossamount  = $rows['grossamount'] ;
          $vatamount    = $rows['vatamount'];
 
        $sql[] =  sprintf("INSERT INTO [SalesLine]
               ([documenttype]
               ,[docdate]
               ,[documentno]
               ,[code]
               ,[description]
               ,[unitofmeasure]
               ,[Quantity]
               ,[UnitPrice]
               ,[vatamount]
               ,[invoiceamount]
               ,[vatrate]
               ,[inclusive]
               ,[containerprice]
               ,[containersunits]
               ,[totalchargedcontainers]
               ,[containercode]
               ,PartPerUnit
               ,PriceInPricelist)
         VALUES
               ('%s'
               ,'%s'
               ,'%s'
               ,'%s'
               ,'%s'
               ,'%s'
               ,'%s'
               ,%f
               ,%f
               ,%f
               ,'%s'
               ,'%s'
               ,%f
               ,%f
               ,%f
               ,'%s'
               ,'%s'
               ,%f)"
                ,"1"
               ,FormatDateForSQL($_POST['date'])
               ,$_POST['documentno']
               ,$stockcode
               ,$stockname
               ,$Inpacks
               ,$qty
               ,$salesprice
               ,$vatamount
               ,$grossamount
               ,$vatrate
               ,$VATinclusive
               ,$emptycost
               ,0
               ,$totalemptycost
               ,$emptycode
               ,$Partperunit
               ,$PriceInPricelist);
            }
    }

    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }

    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('Failed to save sales order :'.$_POST['documentno'],'warn');

    } else {
        DB_Txn_Commit($db);
        prnMsg('Sales order :'.$_POST['documentno'].' has been created','info');
     }
 
    
    $SQL="select 
       [documentno]
      ,[docdate]
      ,[oderdate]
      ,[duedate]
      ,[postingdate]
      ,[customercode]
      ,[customername]
      ,[yourreference]
      ,[externaldocumentno]
      ,[locationcode]
      ,[paymentterms]
      ,[postinggroup]
      ,[currencycode]
      ,[salespersoncode]
      ,[printed]
      ,[released]
      ,[status]
      ,[userid]
      ,shipping
      ,packagescharge
      from [SalesHeader] where [documenttype]='1' and  [documentno]='".$_POST['documentno']."'";
    $Result=DB_query($SQL,$db);
       
    Echo '<div class="confirmso" id="conso" onmouseover="dragElement(this);">'
       . '<table class="table-bordered table-condensed table-responsive-small">';
    $row=DB_fetch_row($Result);
        
    echo sprintf("<tr><td>Order No</td><td>%s</td></tr>",$_POST['documentno']);
    echo sprintf("<tr><td>Date</td><td>%s</td></tr>",Is_null($row[1])?'': ConvertSQLDate($row[1]));
    echo sprintf("<tr><td>Customer ID</td><td>%s</td></tr>", $row[5]);
    echo sprintf("<tr><td>Customer Name</td><td>%s</td></tr>",$row[6]);
    echo sprintf("<tr><td>Cost of transport</td><td>%s</td></tr>",$row[18]);
    echo sprintf("<tr><td>Cost of packaging</td><td>%s</td></tr>",$row[19]);
    $shipping =(float) $row[18];
    $packaging =(float) $row[19];
    
            $SQL="select 
                [documenttype]
               ,[docdate]
               ,[documentno]
               ,[code]
               ,[description]
               ,[unitofmeasure]
               ,[Quantity]
               ,[UnitPrice]
               ,[vatamount]
               ,[invoiceamount]
               ,[vatrate]
               ,[inclusive]
               ,[containerprice]
               ,[containersunits]
               ,[totalchargedcontainers]
               ,[containercode]
               ,PartPerUnit
               ,PriceInPricelist
               ,shipping
           from [SalesLine] where [documenttype]=1 and [documentno]='".$_POST['documentno']."'";
    echo '<tr><td colspan="2">';
    
    echo '<table class="table-bordered table-condensed table-responsive-small"><tr>'
            . '<td>Code</td>'
            . '<td>Description</td>'
            . '<td>Unit Of Measure</td>'
            . '<td>Quantity</td>'
            . '<td>Sales Price</td>'
            . '<td>Charge on Empties</td>'
            . '<td>VAT Amount</td>'
            . '<td>Gross Amount</td>'
            . '</tr>';
    
    $VATAMOUNT=0;
    $INVOICEAMOUNT=0;
    
    $ResultIndex=DB_query($SQL,$db);
    while($myrow=DB_fetch_array($ResultIndex)){
         echo sprintf('<tr>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td>%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td>'
                 . '<td class="number">%s</td>'
                 . '</tr>',
                 $myrow['code'],
                 $myrow['description'].'(1 X'.$myrow['PartPerUnit'].')',
                 $myrow['unitofmeasure'],
                 $myrow['Quantity'],
                 number_format($myrow['UnitPrice'],2),
                 number_format($myrow['totalchargedcontainers'],2),
                 number_format($myrow['vatamount'],2),
                 number_format($myrow['invoiceamount'],2));
     
                $VATAMOUNT += $myrow['vatamount'];
                $INVOICEAMOUNT  +=$myrow['invoiceamount'];
    }
    
    
    echo sprintf('<tr><td colspan="6" class="number">%s</td><td class="number">%s</td><td class="number">%s</td></tr>','Sales Order Totals',number_format($VATAMOUNT,2),number_format($INVOICEAMOUNT+$shipping+$packaging,2));
    echo '</table></td></tr>';
    echo '<tr><td><input type="submit" name="confirmOrder" value="'._('Confirm Sales Order And Print').'"/></td>'
            . '<td><input type="submit" name="submit" value="'._('Delete Order').'"/></td>'
            . '</tr></table></div>';
 
    $_SESSION['Grossamounttotal']=0;
 
 echo '</div></form>';   
?>
