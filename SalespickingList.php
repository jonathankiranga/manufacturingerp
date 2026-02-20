<<<<<<< HEAD

<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');   
include('transactions/stockbalance.inc');   
$Title = _('Sales Picking List');
include('includes/header.inc');  
 
$tankClass=new tankClass() ;
$tankClass->update_tank_balance();
 
$pge=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
 
echo '<div class="centre"><p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales Picking List') .'" alt="" />' . ' ' . _('Sales Picking List') . '</p>';
 
if(isset($_POST['submit'])){ 
    if($_POST['submit']=='Confirm Picking Details'){
        include('transactions/Savepickinglist.inc');  
    }
}

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
    $_POST['manualdocumentno']= GetTempNextNo(19);
    $_SESSION['DocumentPicking'] = false;
    $_SESSION['productionTankStore'] = array();
    $_SESSION['cumQty']=array();
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
    
    if(!isset($_POST['salespersoncode'])){
        $_POST['salespersoncode']= $rowresults[14];
    }
    
    $_POST['documentno'] = $rowresults[1];
  
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '</div><div class="container-fluid">'
        . '<table class="table-condensed table-responsive-small table-bordered"><caption>Sales Invoice Header Details</caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>
<td>Document No</td>'
        . '<td><input tabindex="4" type="hidden" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/>'.$_POST['documentno'].'</td>'
        . '<td>Delivery No</td>'
        . '<td><input tabindex="4" type="text" name="manualdocumentno" value="'.$_POST['manualdocumentno'].'"  size="10" required="required"/></td>'
   
         . '</tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  readonly="readonly"/>'
        . '<td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  readonly="readonly"/></td></tr>';

echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo '<td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode">'
. '<option value="not">Not selected</option>';

$ResultIndex=DB_query("SELECT [code],[salesman],[commission],[inactive] FROM [salesrepsinfo] where [inactive] is null or [inactive]=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
        echo sprintf('<option value="%s"  %s >%s</option>',$row['code'], ($_POST['salespersoncode']==$row['code']?'selected="selected"':''),$row['salesman']);
}
    
echo '</select></td><td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td>
   </tr>';
echo '</table></td></tr><tr><td>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
    
$sqldebtors=DB_query("SELECT [itemcode] ,[creditlimit],[customer]
      ,[phone] ,[email] ,[city] ,[country],[curr_cod],[customerposting],[salesman],[VATinclusive]
       FROM [debtors] join postinggroups on code=[customerposting] where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];



$query="SELECT
       [entryno]
      ,[documenttype]
      ,[docdate]
      ,[documentno]
      ,[locationcode]
      ,[stocktype]
      ,[code]
      ,[description]
      ,[unitofmeasure]
      ,[Quantity]
      ,[Quantity_toinvoice]
      ,[Qunatity_delivered]
      ,[UnitPrice]
      ,[vatamount]
      ,[invoiceamount]
      ,[completed]
      ,[printed]
      ,[containerprice]
      ,[containersunits]
      ,[totalchargedcontainers]
      ,[containercode]
      ,[vatrate]
      ,[inclusive]
      ,[SalesLine].[partperunit]
      ,[stockmaster].[averagestock]
      ,UOM
  FROM [SalesLine] 
  join [stockmaster] on [SalesLine].code=[stockmaster].itemcode
 where [documentno]='".$_POST['documentno']."'";
 $ResultIndex = DB_query($query, $db);

echo '<table class="table-condensed table-responsive-small table-bordered"><tr>'
        . '<td >Stock ID</td>'
        . '<td >Stock Description</td>'
        . '<td >Packed In</td>'
        . '<td class="number">Parts</td>'
        . '<td class="number">Qty<br />Ordered</td>'
        . '<td class="number">Store<br />Picked</td>'
        . '<td class="number">Store<br />Balance</td>'
        . '<td class="number">Qty To<br />Pick</td>'
        . '<td class="number">Qty<br />Not<br />Ccollected</td>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;
 

while($stocklist=DB_fetch_array($ResultIndex)){
    $_SESSION['cumQty'][$stocklist['code']]=0;
    $Rowdata[]=$stocklist;
}


if(is_array($Rowdata)){
foreach ($Rowdata as $key => $stocklist) {

    $emptycost=0; $totalemptycost=0; $cvatamount =0;
    $cnetamount=0; $cgrossamount=0; $emptyunits=0;
    
    $itemcode = trim($stocklist['entryno']);
    $stkcode  = trim($stocklist['code']);
    IsTankOrStore($stkcode);
    $packedas = trim($stocklist['unitofmeasure']);
    $containercode = trim($stocklist['container']);
   
      
    $qtyorderd = $stocklist['Quantity'];
    $qtyreceived = $stocklist['Qunatity_delivered'];
        
    if(isset($_POST['subunits'][$itemcode])){
        $qty = $_POST['subunits'][$itemcode]; 
        $_SESSION['cumQty'][$stkcode][$itemcode] = ($qty * $stocklist['partperunit']);
     }else{
        $qty=0; 
    }
    
    
    if(isset($qty)){
        $qtytoinvoice = $qtyorderd-($qty+$qtyreceived);
    }else{
        $qtytoinvoice = $stocklist['Quantity_toinvoice'];
    }
     
       
    $location = getDefaultStore($itemcode);
    if($_SESSION['productionTankStore'][$stkcode]=="T"){
        $StockBalanceInloose =(int) getTankbalance($location,$stkcode);
    }elseif($_SESSION['productionTankStore'][$stkcode]=="S"){
        $StockBalanceInloose=(int) getbalance($stkcode,$location,'loosqty');
    }
    
    $total=0;
    $sessioncum=$_SESSION['cumQty'][$stkcode];
    if(is_array($sessioncum)){
          foreach ($sessioncum as $value) {
                foreach ($value as $rowqty) {
                      $total += $rowqty;
                }
            }
    }else{
        $total +=$_SESSION['cumQty'][$stkcode];
    }
       
    $StockRemaining = (int) $StockBalanceInloose - $total;
    $stockBalance = intdiv($StockRemaining,$stocklist['partperunit']);
    $balanceDescription = sprintf('%f %s',$stockBalance,$packedas);
    
    echo sprintf('<tr>'
         .'<td>'.$stkcode.'<input type="hidden" name="code['.$itemcode.']" value="'.$stkcode.'"/>'
            . '<input type="hidden" name="stockbal['.$itemcode.']" value="'.$stockBalance.'"/></td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td class="number">'.$qtyorderd.'<input type="hidden"  name="ordered['.$itemcode.']" value="'.$qtyorderd.'"/></td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td><input type="text" class="number" name="subunits['.$itemcode.']" value="'.$qty.'" autofocus="autofocus" required="required" size="10" /></td>'
         .'<td class="number">'.$qtytoinvoice.'<input type="hidden"  name="Quantity_toinvoice['.$itemcode.']" value="'.$qtytoinvoice.'"/>'
         .'<input type="hidden" name="emptycode['.$itemcode.']" value="'.$containercode.'"/>'
        . '<input type="hidden" name="partperunit['.$itemcode.']" value="'.$stocklist['partperunit'].'"/></td>'
         .'</tr>',trim($stocklist['description']),$packedas,$stocklist['partperunit'],
            StoreTankCMB($itemcode,$stkcode),$balanceDescription);
}
}

echo '<tfoot><tr>'
        . '<td colspan="3"></td>'
        . '<td colspan="3" class="number"><input type="submit" name="ReCalculate" value="' . _('Re-Calculate') . '" /></td>'
        . '<td colspan="3" class="number"><input type="submit" name="submit" value="' . _('Confirm Picking Details') . '"'
        . ' onclick="return confirm(\''._('Are you sure you wish to Dispath  this Picking List ?').'\');" /></td>'
        . '</tr></tfoot>';
echo '</table>';
echo '</div></div></form>';
 
include('includes/footer.inc');

function getDefaultStore($rowid){
  return  (isset($_POST['location'][$rowid]))?($_POST['location'][$rowid]):'';
}

Function IsTankOrStore($ItemCode){
    global $db;
    
    $stcode = trim($ItemCode);
    $sql = sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
    $ResultIndex=DB_query($sql,$db);
    $Stores = DB_fetch_row($ResultIndex);
   if($Stores[0]==0){
      $_SESSION['productionTankStore'][$stcode]="S";
   }else{
      $_SESSION['productionTankStore'][$stcode]="T";
   }
   
}

Function StoreTankCMB($id,$ItemCode){
        global $db;
        $stcode = trim($ItemCode);

        $line='';
        $line .='<select name="location['.$id.']" onchange="ReloadForm(salesform.ReCalculate);" required="required">';
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
          $_SESSION['productionTankStore'][$stcode]="S";
           $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
           while($rows= DB_fetch_array($REsults)){
                $line .= sprintf('<option value="%s" %s>%s</option>',trim($rows['code']),$_POST['location'][$id]==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
            }
       }else{
          $_SESSION['productionTankStore'][$stcode]="T";
           $ResultIndex=DB_query(sprintf("select tankname from [ProductionUnit] where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                   $line .= sprintf('<option value="%s" %s>%s</option>',trim($value['tankname']),$_POST['location'][$id]==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
           }
       }
       $line .= '</select>';
        return $line; 
    }
=======

<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');   
include('transactions/stockbalance.inc');   
$Title = _('Sales Picking List');
include('includes/header.inc');  
 
$tankClass=new tankClass() ;
$tankClass->update_tank_balance();
 
$pge=htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
 
echo '<div class="centre"><p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Sales Picking List') .'" alt="" />' . ' ' . _('Sales Picking List') . '</p>';
 
if(isset($_POST['submit'])){ 
    if($_POST['submit']=='Confirm Picking Details'){
        include('transactions/Savepickinglist.inc');  
    }
}

if(isset($_GET['ref'])){
    $_POST['documentno'] = $_GET['ref'];
    $_POST['manualdocumentno']= GetTempNextNo(19);
    $_SESSION['DocumentPicking'] = false;
    $_SESSION['productionTankStore'] = array();
    $_SESSION['cumQty']=array();
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
    
    if(!isset($_POST['salespersoncode'])){
        $_POST['salespersoncode']= $rowresults[14];
    }
    
    $_POST['documentno'] = $rowresults[1];
  
echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';

echo '</div><div class="container-fluid">'
        . '<table class="table-condensed table-responsive-small table-bordered"><caption>Sales Invoice Header Details</caption>';

echo '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" readonly="readonly" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>
<td>Document No</td>'
        . '<td><input tabindex="4" type="hidden" name="documentno" value="'.$_POST['documentno'].'"  size="5" readonly="readonly"/>'.$_POST['documentno'].'</td>'
        . '<td>Delivery No</td>'
        . '<td><input tabindex="4" type="text" name="manualdocumentno" value="'.$_POST['manualdocumentno'].'"  size="10" required="required"/></td>'
   
         . '</tr>';

echo '<tr><td>Customer ID</td>'
        . '<td><input tabindex="4" type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  readonly="readonly"/>'
        . '<td>Customer Name</td>'
        . '<td colspan="3"><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  readonly="readonly"/></td></tr>';

echo '<tr><td>Currency Code</td><td>'
. '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode" id="currencycode" value="'.$_POST['currencycode'].'" readonly="readonly"/></td>';

echo '<td>Sales Rep</td><td><select tabindex="7" name="salespersoncode" id="salespersoncode">'
. '<option value="not">Not selected</option>';

$ResultIndex=DB_query("SELECT [code],[salesman],[commission],[inactive] FROM [salesrepsinfo] where [inactive] is null or [inactive]=0 ", $db);

while($row=DB_fetch_array($ResultIndex)){
        echo sprintf('<option value="%s"  %s >%s</option>',$row['code'], ($_POST['salespersoncode']==$row['code']?'selected="selected"':''),$row['salesman']);
}
    
echo '</select></td><td>Your Reference</td>'
        . '<td><input tabindex="5" type="text" name="reference" value="'.$_POST['reference'].'"  size="5" /></td>
   </tr>';
echo '</table></td></tr><tr><td>';

$runningnettotal = 0;
$runningvattotal = 0;
$runninggrosstotal = 0;
    
$sqldebtors=DB_query("SELECT [itemcode] ,[creditlimit],[customer]
      ,[phone] ,[email] ,[city] ,[country],[curr_cod],[customerposting],[salesman],[VATinclusive]
       FROM [debtors] join postinggroups on code=[customerposting] where itemcode='".$_POST['CustomerID']."'", $db);
$debtorsrow = DB_fetch_row($sqldebtors);
$customerposting = $debtorsrow[8];
$VATinclusive = $debtorsrow[10];



$query="SELECT
       [entryno]
      ,[documenttype]
      ,[docdate]
      ,[documentno]
      ,[locationcode]
      ,[stocktype]
      ,[code]
      ,[description]
      ,[unitofmeasure]
      ,[Quantity]
      ,[Quantity_toinvoice]
      ,[Qunatity_delivered]
      ,[UnitPrice]
      ,[vatamount]
      ,[invoiceamount]
      ,[completed]
      ,[printed]
      ,[containerprice]
      ,[containersunits]
      ,[totalchargedcontainers]
      ,[containercode]
      ,[vatrate]
      ,[inclusive]
      ,[SalesLine].[partperunit]
      ,[stockmaster].[averagestock]
      ,UOM
  FROM [SalesLine] 
  join [stockmaster] on [SalesLine].code=[stockmaster].itemcode
 where [documentno]='".$_POST['documentno']."'";
 $ResultIndex = DB_query($query, $db);

echo '<table class="table-condensed table-responsive-small table-bordered"><tr>'
        . '<td >Stock ID</td>'
        . '<td >Stock Description</td>'
        . '<td >Packed In</td>'
        . '<td class="number">Parts</td>'
        . '<td class="number">Qty<br />Ordered</td>'
        . '<td class="number">Store<br />Picked</td>'
        . '<td class="number">Store<br />Balance</td>'
        . '<td class="number">Qty To<br />Pick</td>'
        . '<td class="number">Qty<br />Not<br />Ccollected</td>';

$runningnettotal = 0;
$runningvattotal  = 0;
$runninggrosstotal  = 0;
 

while($stocklist=DB_fetch_array($ResultIndex)){
    $_SESSION['cumQty'][$stocklist['code']]=0;
    $Rowdata[]=$stocklist;
}


if(is_array($Rowdata)){
foreach ($Rowdata as $key => $stocklist) {

    $emptycost=0; $totalemptycost=0; $cvatamount =0;
    $cnetamount=0; $cgrossamount=0; $emptyunits=0;
    
    $itemcode = trim($stocklist['entryno']);
    $stkcode  = trim($stocklist['code']);
    IsTankOrStore($stkcode);
    $packedas = trim($stocklist['unitofmeasure']);
    $containercode = trim($stocklist['container']);
   
      
    $qtyorderd = $stocklist['Quantity'];
    $qtyreceived = $stocklist['Qunatity_delivered'];
        
    if(isset($_POST['subunits'][$itemcode])){
        $qty = $_POST['subunits'][$itemcode]; 
        $_SESSION['cumQty'][$stkcode][$itemcode] = ($qty * $stocklist['partperunit']);
     }else{
        $qty=0; 
    }
    
    
    if(isset($qty)){
        $qtytoinvoice = $qtyorderd-($qty+$qtyreceived);
    }else{
        $qtytoinvoice = $stocklist['Quantity_toinvoice'];
    }
     
       
    $location = getDefaultStore($itemcode);
    if($_SESSION['productionTankStore'][$stkcode]=="T"){
        $StockBalanceInloose =(int) getTankbalance($location,$stkcode);
    }elseif($_SESSION['productionTankStore'][$stkcode]=="S"){
        $StockBalanceInloose=(int) getbalance($stkcode,$location,'loosqty');
    }
    
    $total=0;
    $sessioncum=$_SESSION['cumQty'][$stkcode];
    if(is_array($sessioncum)){
          foreach ($sessioncum as $value) {
                foreach ($value as $rowqty) {
                      $total += $rowqty;
                }
            }
    }else{
        $total +=$_SESSION['cumQty'][$stkcode];
    }
       
    $StockRemaining = (int) $StockBalanceInloose - $total;
    $stockBalance = intdiv($StockRemaining,$stocklist['partperunit']);
    $balanceDescription = sprintf('%f %s',$stockBalance,$packedas);
    
    echo sprintf('<tr>'
         .'<td>'.$stkcode.'<input type="hidden" name="code['.$itemcode.']" value="'.$stkcode.'"/>'
            . '<input type="hidden" name="stockbal['.$itemcode.']" value="'.$stockBalance.'"/></td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td class="number">'.$qtyorderd.'<input type="hidden"  name="ordered['.$itemcode.']" value="'.$qtyorderd.'"/></td>'
         .'<td>%s</td>'
         .'<td>%s</td>'
         .'<td><input type="text" class="number" name="subunits['.$itemcode.']" value="'.$qty.'" autofocus="autofocus" required="required" size="10" /></td>'
         .'<td class="number">'.$qtytoinvoice.'<input type="hidden"  name="Quantity_toinvoice['.$itemcode.']" value="'.$qtytoinvoice.'"/>'
         .'<input type="hidden" name="emptycode['.$itemcode.']" value="'.$containercode.'"/>'
        . '<input type="hidden" name="partperunit['.$itemcode.']" value="'.$stocklist['partperunit'].'"/></td>'
         .'</tr>',trim($stocklist['description']),$packedas,$stocklist['partperunit'],
            StoreTankCMB($itemcode,$stkcode),$balanceDescription);
}
}

echo '<tfoot><tr>'
        . '<td colspan="3"></td>'
        . '<td colspan="3" class="number"><input type="submit" name="ReCalculate" value="' . _('Re-Calculate') . '" /></td>'
        . '<td colspan="3" class="number"><input type="submit" name="submit" value="' . _('Confirm Picking Details') . '"'
        . ' onclick="return confirm(\''._('Are you sure you wish to Dispath  this Picking List ?').'\');" /></td>'
        . '</tr></tfoot>';
echo '</table>';
echo '</div></div></form>';
 
include('includes/footer.inc');

function getDefaultStore($rowid){
  return  (isset($_POST['location'][$rowid]))?($_POST['location'][$rowid]):'';
}

Function IsTankOrStore($ItemCode){
    global $db;
    
    $stcode = trim($ItemCode);
    $sql = sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
    $ResultIndex=DB_query($sql,$db);
    $Stores = DB_fetch_row($ResultIndex);
   if($Stores[0]==0){
      $_SESSION['productionTankStore'][$stcode]="S";
   }else{
      $_SESSION['productionTankStore'][$stcode]="T";
   }
   
}

Function StoreTankCMB($id,$ItemCode){
        global $db;
        $stcode = trim($ItemCode);

        $line='';
        $line .='<select name="location['.$id.']" onchange="ReloadForm(salesform.ReCalculate);" required="required">';
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
          $_SESSION['productionTankStore'][$stcode]="S";
           $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
           while($rows= DB_fetch_array($REsults)){
                $line .= sprintf('<option value="%s" %s>%s</option>',trim($rows['code']),$_POST['location'][$id]==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
            }
       }else{
          $_SESSION['productionTankStore'][$stcode]="T";
           $ResultIndex=DB_query(sprintf("select tankname from [ProductionUnit] where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                   $line .= sprintf('<option value="%s" %s>%s</option>',trim($value['tankname']),$_POST['location'][$id]==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
           }
       }
       $line .= '</select>';
        return $line; 
    }
>>>>>>> fcf1da09040591d58a8bc754034c8da36e93ca78
  