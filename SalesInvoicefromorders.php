<<<<<<< HEAD
<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');


$Title = _('Sales Invoice');

include('includes/header.inc');   
include('transactions/stockbalance.inc');  

$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    
echo '<div class="centre"><p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales Invoice') .'" alt="" />' . ' ' . _('Sales Invoice') . '</p>';

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
    $_SESSION['DocumentPosted']=false;
    $_SESSION['DocumentPicking']=false;
    $_SESSION['Qunatity_delivered']=0;
    $_POST['manualdocumentno']= GetTempNextNo(10);
}
    
if(isset($_POST['submit']) and $_POST['submit']=='Enter Delivery Details and Confirm Invoice'){
     
    include('transactions/Saveinvoice.inc');  
}


    if(isset($_POST['delete']) and $_POST['delete']=='Delete Document for ever'){
       if($_SESSION['Qunatity_delivered']==0){
        $sql= sprintf("delete from SalesLine where [documentno]='%s' "
                . " and [SalesLine].[documenttype]='1' ",$_POST['documentno']);
        DB_query($sql, $db);

        $sql=sprintf("delete from SalesHeader where [documentno]='%s' "
                . " and [SalesHeader].[documenttype]='1' ",$_POST['documentno']);
        DB_query($sql, $db);
      }else{  prnMsg("Items exist",'warn');}
  }    

$filter="SELECT 
            [documenttype]
           ,[documentno]
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
           ,[vatinclusive]
           ,shipping
           ,packagescharge
       FROM [SalesHeader] 
       where [documentno]='".$_POST['documentno']."'";
$ResultIndex= DB_query($filter, $db);
$rowresults = DB_fetch_row($ResultIndex);
    if(!isset($_POST['date'])){
      $_POST['date'] = is_null($rowresults[2])?'': ConvertSQLDate($rowresults[2]);
    }
    
    if(!isset($_POST['Salesoderdate'])){
        $_POST['Salesoderdate']= is_null($rowresults[3])?'': ConvertSQLDate($rowresults[3]);
    }
    
    if(!isset($_POST['datedue'])){
      $_POST['datedue'] = is_null($rowresults[4])?'': ConvertSQLDate($rowresults[4]);
    }
  
    if(!isset($_POST['reference'])){
        $_POST['reference'] = $rowresults[8];
    }
    
    $_POST['CustomerID'] = $rowresults[6];
    $_POST['CustomerName']= $rowresults[7];
    $_POST['currencycode']= $rowresults[13];
    $Headershipping =(float) $rowresults[16];
     $Headerpackaging =(float) $rowresults[17];
    
    if(!isset($_POST['salespersoncode'])){
        $_POST['salespersoncode']= $rowresults[14];
    }
    
    $_POST['documentno'] = $rowresults[1];
  
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '</div><div class="container-fluid">'
        . '<table class="table table-bordered"><caption>Sales Invoice Header Details</caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Document No</td>'
        . '<td><input tabindex="4" type="hidden" name="documentno" value="'.$_POST['documentno'].'"  size="10" readonly="readonly"/>'.$_POST['documentno'].'</td>'
        . '<td>Invoice No</td>'
        . '<td><input tabindex="4" type="text" name="manualdocumentno" value="'.$_POST['manualdocumentno'].'"  size="10" required="required"/></td>'
        . '</tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"/>'
        . '</td>'
        . '<td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" /></td></tr>';

echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode"  value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo '<td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode">'
. '<option value="not">Not selected</option>';

$ResultIndex=DB_query("SELECT [code],[salesman],[commission],[inactive] FROM [salesrepsinfo] where [inactive] is null or [inactive]=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
        echo sprintf('<option value="%s"  %s >%s</option>',$row['code'], ($_POST['salespersoncode']==$row['code']?'selected="selected"':''),$row['salesman']);
}
    
echo '</select></td><td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';
echo '</table>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
$runningshipping=0;
    
$sqldebtors=DB_query("SELECT [itemcode] ,[creditlimit],[customer]
      ,[phone] ,[email] ,[city] ,[country],[curr_cod],[customerposting],[salesman],[VATinclusive],[IsTaxed]
       FROM [debtors] join postinggroups on code=[customerposting] where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];
$IsTaxed = $debtorsrow[11];

      
$Slaqry ="SELECT [entryno],[documenttype],[docdate],[documentno],[locationcode]
      ,[stocktype],[code],[description],[unitofmeasure],[Quantity]
      ,[Quantity_toinvoice],[Qunatity_delivered],[UnitPrice],[vatamount],[invoiceamount]
      ,[completed],[printed] ,[containerprice],[containersunits] 
      ,[totalchargedcontainers],[containercode],[vatrate] ,[inclusive],[partperunit] 
      ,[PriceInPricelist]
  FROM [SalesLine] 
  where [documentno]='".$_POST['documentno']."' group by  
       [entryno],[documenttype],[docdate],[documentno]
      ,[locationcode],[stocktype],[code] ,[description]
      ,[unitofmeasure],[Quantity],[Quantity_toinvoice] ,[Qunatity_delivered]
      ,[UnitPrice] ,[vatamount] ,[invoiceamount],[completed] ,[printed]
      ,[containerprice],[containersunits] ,[totalchargedcontainers] ,[containercode]
      ,[vatrate] ,[inclusive] ,[partperunit] ,[PriceInPricelist]";


$PostShipping=0;
 if(is_float($Headershipping) and $Headershipping>0){
     $ResultIndex=DB_query(Sprintf("select count(*) from [SalesLine] where [documentno]='%s'",$_POST['documentno']),$db);
     $rowIndex=DB_fetch_row($ResultIndex);
     if($rowIndex[0]>0){
     $PostShipping = $Headershipping/$rowIndex[0];
       }
 }
 
 
$Postpackaging=0;
 if((is_float($Headerpackaging) and $Headerpackaging>0)||$_POST['packagescharge']>0){
     $packagescharge =(float)(isset($_POST['packagescharge'])?$_POST['packagescharge'] : $Headerpackaging); 
  
     $ResultIndex=DB_query(Sprintf("select count(*) from [SalesLine] where [documentno]='%s'",$_POST['documentno']),$db);
     $rowIndex=DB_fetch_row($ResultIndex);
     if($rowIndex[0]>0){
     $Postpackaging = $packagescharge/$rowIndex[0];
       }
 }
 
 
 
 
 $ResultIndex = DB_query($Slaqry,$db);
 
echo '<table class="table-condensed table-responsive-small table-bordered"><tr>'
        . '<td>Stock ID</td>'
        . '<td>Stock Description</td>'
        . '<td>Packed In</td>'
        . '<td>Parts</td>'
        . '<td class="number">Qty to<br />Invoice</td>'
        . '<td class="number">Invoice Price<br /> per part</td>'
        . '<td class="number">Empties<br />Charge</td>'
        . '<td class="number">Shipping<br />Charge</td>'
        . '<td class="number">Net Amount</td>'
        . '<td class="number">VAT Amount</td>'
        . '<td class="number">Gross Amount</td></tr>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;
$runningshipping = 0;


while($stocklist=DB_fetch_array($ResultIndex)){
    
    $emptycost=0; $totalemptycost=0; $cvatamount =0; $cnetamount=0; $cgrossamount=0; $emptyunits=0;$Shipping=0;
    $PriceInPricelist=0;
    
    $itemcode = trim($stocklist['entryno']);
    $stkcode = trim($stocklist['code']);
    $containercode = trim($stocklist['container']);
    $InfRowContainers = ContainerInfo($stkcode);
    $rate = ($IsTaxed==0)?0: $stocklist['vatrate'];
   
    $location = $_POST['location'][$itemcode];
    $qty = $stocklist['Qunatity_delivered'];
    $PriceInPricelist = $stocklist['PriceInPricelist'];
    $unitofmeasure = $stocklist['unitofmeasure'];
    $salesprice = $stocklist['UnitPrice'];
    $_SESSION['Qunatity_delivered'] += $qty;
       
    if($stocklist['partperunit']>1){
            $baseamount = ($salesprice * $qty);
 
            if(isset($_POST['emptycost'][$itemcode])){
                
                 $emptycost = $_POST['emptycost'][$itemcode];
                 $totalemptycost = ($emptycost * $qty);             
                 $crate = $InfRowContainers[3];
                 $cnetamount = $totalemptycost;
                 
                 if($VATinclusive==true){
                    $cvatamount = $cnetamount * ($crate/100+$crate);
                    $cgrossamount= $cnetamount ;
                }else{
                    $cvatamount = $cnetamount * ($crate/100);
                    $cgrossamount= $cnetamount + $cvatamount;
                }
                
            }
     } else {
          $baseamount = ($salesprice * $qty);
    }
        
    
   $Shipping =(float)(isset($_POST['Shipping'][$itemcode])?$_POST['Shipping'][$itemcode] :$PostShipping); 
   
    if($VATinclusive==true){
        $netamount = ($baseamount  * (1- ($rate/(100+$rate)))) ;
        $vatamount = ($baseamount  * ($rate/(100+$rate))) ;
        $grossamount = $baseamount + $Shipping+$Postpackaging   ;
    }else{
        $vatamount  = ($baseamount  * ($rate/100));
        $grossamount = $baseamount  + $vatamount + $Shipping+$Postpackaging ;
        $netamount = $baseamount;
    }
  
    
    $runningnettotal += ($netamount);
    $runningvattotal += ($vatamount);
    $runninggrosstotal += ($grossamount) ;
    $runningshipping += round($Shipping,1);
    
    echo '<tr>'
         .'<td>'.$stkcode.'<input type="hidden" name="code['.$itemcode.']" value="'.$stkcode.'" />'
         .'<input type="hidden"  name="PriceInPricelist['.$itemcode.']" value="'.$PriceInPricelist.'"/></td>'
         .'<td>'.trim($stocklist['description']).'</td>'
         .'<td>'.$unitofmeasure.'</td>'
         .'<td>'.number_format($stocklist['partperunit'],0).'</td>'
         .'<td class="number">'.$qty.'<input type="hidden" name="subunits['.$itemcode.']" value="'.$qty.'"/></td>'
         .'<td class="number">'.$salesprice.'<input type="hidden"  name="salesprice['.$itemcode.']" value="'.$salesprice.'"/></td>'
         .'<td><input type="text" class="number" name="emptycost['.$itemcode.']" value="'.$emptycost.'" size="5"/>'
         .'<input type="hidden" name="emptycode['.$itemcode.']" value="'.$containercode.'"/></td>'
         .'<td><input type="hidden" name="totalemptycost['.$itemcode.']" value="'.$cgrossamount.'"/>'
         .'<input type="hidden" name="linepackage['.$itemcode.']" value="'.$Postpackaging.'"/>'
         .'<input type="text" class="number" name="Shipping['.$itemcode.']" value="'.$Shipping.'" size="5"/></td>'
         .'<td class="number">'.$netamount.'<input type="hidden" name="netamount['.$itemcode.']" value="'.($netamount).'"/></td>'
         .'<td class="number">'.$vatamount.'<input type="hidden" name="vatamount['.$itemcode.']" value="'.($vatamount).'"/></td>'
         .'<td class="number">'.$grossamount.'<input type="hidden" name="grossamount['.$itemcode.']" value="'.($grossamount).'"/></td>'
         .'</tr>';
}
   
echo sprintf('<tfoot><tr>'
              . '<td colspan="9">Package Charge to add to Invoice Total </td>'
              . '<td colspan="3" class="number"><input type="text" class="number" name="packagescharge" value="'.$_POST['packagescharge'].'" size="10" />'
              . '</td><tr>'
        . '<td colspan="6"></td>'
        . '<td >TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>', 
                number_format($runningshipping,2),
                number_format($runningnettotal,2),
                number_format($runningvattotal,2),
                number_format($runninggrosstotal,2));

             $_SESSION['Grossamounttotal']=$runninggrosstotal;

echo '</table></td></tr><tr><td>';


echo '<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
	<input type="submit" name="submit" value="' . _('Enter Delivery Details and Confirm Invoice') . '"
            onclick="return confirm(\''._('Are you sure you wish to Close this Invoice ?').'\');" />'
        . '<input type="submit" name="delete" value="' . _('Delete Document for ever') . '"
            onclick="return confirm(\''._('Are you sure you wish to Delete This Document ?').'\');" />';

echo '</td></tr></table></div></form>';
 
include('includes/footer.inc');


function ContainerInfo($itemcode){
    global $db;
    
    $ResultIndex = DB_query("SELECT 
        [stockmaster].[container],
        [c].[itemcode] ,
        [c].[descrip] as [packname],
        isnull([cv].[vat],0) as CVAT
  FROM [stockmaster] 
  left join [stockmaster] c on [stockmaster].[container]=[c].[itemcode]
  left join [inventorypostinggroup] ci on [c].[postinggroup]=[ci].[code]
  left join [vatcategory] cv on [ci].[vatcategory]=[cv].[vatc]
  where [stockmaster].[itemcode]='".$itemcode."'", $db);
    
   $stkmaster = DB_fetch_row($ResultIndex);
   return $stkmaster;
}
=======
<?php

include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');


$Title = _('Sales Invoice');

include('includes/header.inc');   
include('transactions/stockbalance.inc');  

$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
    
echo '<div class="centre"><p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales Invoice') .'" alt="" />' . ' ' . _('Sales Invoice') . '</p>';

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
    $_SESSION['DocumentPosted']=false;
    $_SESSION['DocumentPicking']=false;
    $_SESSION['Qunatity_delivered']=0;
    $_POST['manualdocumentno']= GetTempNextNo(10);
}
    
if(isset($_POST['submit']) and $_POST['submit']=='Enter Delivery Details and Confirm Invoice'){
     
    include('transactions/Saveinvoice.inc');  
}


    if(isset($_POST['delete']) and $_POST['delete']=='Delete Document for ever'){
       if($_SESSION['Qunatity_delivered']==0){
        $sql= sprintf("delete from SalesLine where [documentno]='%s' "
                . " and [SalesLine].[documenttype]='1' ",$_POST['documentno']);
        DB_query($sql, $db);

        $sql=sprintf("delete from SalesHeader where [documentno]='%s' "
                . " and [SalesHeader].[documenttype]='1' ",$_POST['documentno']);
        DB_query($sql, $db);
      }else{  prnMsg("Items exist",'warn');}
  }    

$filter="SELECT 
            [documenttype]
           ,[documentno]
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
           ,[vatinclusive]
           ,shipping
           ,packagescharge
       FROM [SalesHeader] 
       where [documentno]='".$_POST['documentno']."'";
$ResultIndex= DB_query($filter, $db);
$rowresults = DB_fetch_row($ResultIndex);
    if(!isset($_POST['date'])){
      $_POST['date'] = is_null($rowresults[2])?'': ConvertSQLDate($rowresults[2]);
    }
    
    if(!isset($_POST['Salesoderdate'])){
        $_POST['Salesoderdate']= is_null($rowresults[3])?'': ConvertSQLDate($rowresults[3]);
    }
    
    if(!isset($_POST['datedue'])){
      $_POST['datedue'] = is_null($rowresults[4])?'': ConvertSQLDate($rowresults[4]);
    }
  
    if(!isset($_POST['reference'])){
        $_POST['reference'] = $rowresults[8];
    }
    
    $_POST['CustomerID'] = $rowresults[6];
    $_POST['CustomerName']= $rowresults[7];
    $_POST['currencycode']= $rowresults[13];
    $Headershipping =(float) $rowresults[16];
     $Headerpackaging =(float) $rowresults[17];
    
    if(!isset($_POST['salespersoncode'])){
        $_POST['salespersoncode']= $rowresults[14];
    }
    
    $_POST['documentno'] = $rowresults[1];
  
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '</div><div class="container-fluid">'
        . '<table class="table table-bordered"><caption>Sales Invoice Header Details</caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Document No</td>'
        . '<td><input tabindex="4" type="hidden" name="documentno" value="'.$_POST['documentno'].'"  size="10" readonly="readonly"/>'.$_POST['documentno'].'</td>'
        . '<td>Invoice No</td>'
        . '<td><input tabindex="4" type="text" name="manualdocumentno" value="'.$_POST['manualdocumentno'].'"  size="10" required="required"/></td>'
        . '</tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"/>'
        . '</td>'
        . '<td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" /></td></tr>';

echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode"  value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo '<td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode">'
. '<option value="not">Not selected</option>';

$ResultIndex=DB_query("SELECT [code],[salesman],[commission],[inactive] FROM [salesrepsinfo] where [inactive] is null or [inactive]=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
        echo sprintf('<option value="%s"  %s >%s</option>',$row['code'], ($_POST['salespersoncode']==$row['code']?'selected="selected"':''),$row['salesman']);
}
    
echo '</select></td><td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td></tr>';
echo '</table>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
$runningshipping=0;
    
$sqldebtors=DB_query("SELECT [itemcode] ,[creditlimit],[customer]
      ,[phone] ,[email] ,[city] ,[country],[curr_cod],[customerposting],[salesman],[VATinclusive],[IsTaxed]
       FROM [debtors] join postinggroups on code=[customerposting] where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];
$IsTaxed = $debtorsrow[11];

      
$Slaqry ="SELECT [entryno],[documenttype],[docdate],[documentno],[locationcode]
      ,[stocktype],[code],[description],[unitofmeasure],[Quantity]
      ,[Quantity_toinvoice],[Qunatity_delivered],[UnitPrice],[vatamount],[invoiceamount]
      ,[completed],[printed] ,[containerprice],[containersunits] 
      ,[totalchargedcontainers],[containercode],[vatrate] ,[inclusive],[partperunit] 
      ,[PriceInPricelist]
  FROM [SalesLine] 
  where [documentno]='".$_POST['documentno']."' group by  
       [entryno],[documenttype],[docdate],[documentno]
      ,[locationcode],[stocktype],[code] ,[description]
      ,[unitofmeasure],[Quantity],[Quantity_toinvoice] ,[Qunatity_delivered]
      ,[UnitPrice] ,[vatamount] ,[invoiceamount],[completed] ,[printed]
      ,[containerprice],[containersunits] ,[totalchargedcontainers] ,[containercode]
      ,[vatrate] ,[inclusive] ,[partperunit] ,[PriceInPricelist]";


$PostShipping=0;
 if(is_float($Headershipping) and $Headershipping>0){
     $ResultIndex=DB_query(Sprintf("select count(*) from [SalesLine] where [documentno]='%s'",$_POST['documentno']),$db);
     $rowIndex=DB_fetch_row($ResultIndex);
     if($rowIndex[0]>0){
     $PostShipping = $Headershipping/$rowIndex[0];
       }
 }
 
 
$Postpackaging=0;
 if((is_float($Headerpackaging) and $Headerpackaging>0)||$_POST['packagescharge']>0){
     $packagescharge =(float)(isset($_POST['packagescharge'])?$_POST['packagescharge'] : $Headerpackaging); 
  
     $ResultIndex=DB_query(Sprintf("select count(*) from [SalesLine] where [documentno]='%s'",$_POST['documentno']),$db);
     $rowIndex=DB_fetch_row($ResultIndex);
     if($rowIndex[0]>0){
     $Postpackaging = $packagescharge/$rowIndex[0];
       }
 }
 
 
 
 
 $ResultIndex = DB_query($Slaqry,$db);
 
echo '<table class="table-condensed table-responsive-small table-bordered"><tr>'
        . '<td>Stock ID</td>'
        . '<td>Stock Description</td>'
        . '<td>Packed In</td>'
        . '<td>Parts</td>'
        . '<td class="number">Qty to<br />Invoice</td>'
        . '<td class="number">Invoice Price<br /> per part</td>'
        . '<td class="number">Empties<br />Charge</td>'
        . '<td class="number">Shipping<br />Charge</td>'
        . '<td class="number">Net Amount</td>'
        . '<td class="number">VAT Amount</td>'
        . '<td class="number">Gross Amount</td></tr>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;
$runningshipping = 0;


while($stocklist=DB_fetch_array($ResultIndex)){
    
    $emptycost=0; $totalemptycost=0; $cvatamount =0; $cnetamount=0; $cgrossamount=0; $emptyunits=0;$Shipping=0;
    $PriceInPricelist=0;
    
    $itemcode = trim($stocklist['entryno']);
    $stkcode = trim($stocklist['code']);
    $containercode = trim($stocklist['container']);
    $InfRowContainers = ContainerInfo($stkcode);
    $rate = ($IsTaxed==0)?0: $stocklist['vatrate'];
   
    $location = $_POST['location'][$itemcode];
    $qty = $stocklist['Qunatity_delivered'];
    $PriceInPricelist = $stocklist['PriceInPricelist'];
    $unitofmeasure = $stocklist['unitofmeasure'];
    $salesprice = $stocklist['UnitPrice'];
    $_SESSION['Qunatity_delivered'] += $qty;
       
    if($stocklist['partperunit']>1){
            $baseamount = ($salesprice * $qty);
 
            if(isset($_POST['emptycost'][$itemcode])){
                
                 $emptycost = $_POST['emptycost'][$itemcode];
                 $totalemptycost = ($emptycost * $qty);             
                 $crate = $InfRowContainers[3];
                 $cnetamount = $totalemptycost;
                 
                 if($VATinclusive==true){
                    $cvatamount = $cnetamount * ($crate/100+$crate);
                    $cgrossamount= $cnetamount ;
                }else{
                    $cvatamount = $cnetamount * ($crate/100);
                    $cgrossamount= $cnetamount + $cvatamount;
                }
                
            }
     } else {
          $baseamount = ($salesprice * $qty);
    }
        
    
   $Shipping =(float)(isset($_POST['Shipping'][$itemcode])?$_POST['Shipping'][$itemcode] :$PostShipping); 
   
    if($VATinclusive==true){
        $netamount = ($baseamount  * (1- ($rate/(100+$rate)))) ;
        $vatamount = ($baseamount  * ($rate/(100+$rate))) ;
        $grossamount = $baseamount + $Shipping+$Postpackaging   ;
    }else{
        $vatamount  = ($baseamount  * ($rate/100));
        $grossamount = $baseamount  + $vatamount + $Shipping+$Postpackaging ;
        $netamount = $baseamount;
    }
  
    
    $runningnettotal += ($netamount);
    $runningvattotal += ($vatamount);
    $runninggrosstotal += ($grossamount) ;
    $runningshipping += round($Shipping,1);
    
    echo '<tr>'
         .'<td>'.$stkcode.'<input type="hidden" name="code['.$itemcode.']" value="'.$stkcode.'" />'
         .'<input type="hidden"  name="PriceInPricelist['.$itemcode.']" value="'.$PriceInPricelist.'"/></td>'
         .'<td>'.trim($stocklist['description']).'</td>'
         .'<td>'.$unitofmeasure.'</td>'
         .'<td>'.number_format($stocklist['partperunit'],0).'</td>'
         .'<td class="number">'.$qty.'<input type="hidden" name="subunits['.$itemcode.']" value="'.$qty.'"/></td>'
         .'<td class="number">'.$salesprice.'<input type="hidden"  name="salesprice['.$itemcode.']" value="'.$salesprice.'"/></td>'
         .'<td><input type="text" class="number" name="emptycost['.$itemcode.']" value="'.$emptycost.'" size="5"/>'
         .'<input type="hidden" name="emptycode['.$itemcode.']" value="'.$containercode.'"/></td>'
         .'<td><input type="hidden" name="totalemptycost['.$itemcode.']" value="'.$cgrossamount.'"/>'
         .'<input type="hidden" name="linepackage['.$itemcode.']" value="'.$Postpackaging.'"/>'
         .'<input type="text" class="number" name="Shipping['.$itemcode.']" value="'.$Shipping.'" size="5"/></td>'
         .'<td class="number">'.$netamount.'<input type="hidden" name="netamount['.$itemcode.']" value="'.($netamount).'"/></td>'
         .'<td class="number">'.$vatamount.'<input type="hidden" name="vatamount['.$itemcode.']" value="'.($vatamount).'"/></td>'
         .'<td class="number">'.$grossamount.'<input type="hidden" name="grossamount['.$itemcode.']" value="'.($grossamount).'"/></td>'
         .'</tr>';
}
   
echo sprintf('<tfoot><tr>'
              . '<td colspan="9">Package Charge to add to Invoice Total </td>'
              . '<td colspan="3" class="number"><input type="text" class="number" name="packagescharge" value="'.$_POST['packagescharge'].'" size="10" />'
              . '</td><tr>'
        . '<td colspan="6"></td>'
        . '<td >TOTAL</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '<td class="number">%s</td>'
              . '</tr></tfoot>', 
                number_format($runningshipping,2),
                number_format($runningnettotal,2),
                number_format($runningvattotal,2),
                number_format($runninggrosstotal,2));

             $_SESSION['Grossamounttotal']=$runninggrosstotal;

echo '</table></td></tr><tr><td>';


echo '<input type="submit" name="submit" value="' . _('Re-Calculate') . '" />
	<input type="submit" name="submit" value="' . _('Enter Delivery Details and Confirm Invoice') . '"
            onclick="return confirm(\''._('Are you sure you wish to Close this Invoice ?').'\');" />'
        . '<input type="submit" name="delete" value="' . _('Delete Document for ever') . '"
            onclick="return confirm(\''._('Are you sure you wish to Delete This Document ?').'\');" />';

echo '</td></tr></table></div></form>';
 
include('includes/footer.inc');


function ContainerInfo($itemcode){
    global $db;
    
    $ResultIndex = DB_query("SELECT 
        [stockmaster].[container],
        [c].[itemcode] ,
        [c].[descrip] as [packname],
        isnull([cv].[vat],0) as CVAT
  FROM [stockmaster] 
  left join [stockmaster] c on [stockmaster].[container]=[c].[itemcode]
  left join [inventorypostinggroup] ci on [c].[postinggroup]=[ci].[code]
  left join [vatcategory] cv on [ci].[vatcategory]=[cv].[vatc]
  where [stockmaster].[itemcode]='".$itemcode."'", $db);
    
   $stkmaster = DB_fetch_row($ResultIndex);
   return $stkmaster;
}
>>>>>>> fcf1da09040591d58a8bc754034c8da36e93ca78
?>