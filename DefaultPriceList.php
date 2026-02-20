<<<<<<< HEAD
<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
include('transactions/poscart.inc');
include('transactions/stockbalance.inc');   
$Title = _('Price List');
include('includes/header.inc');

 $POSclass = new PriceList();
 if(isset($_GET['new'])){
     $_SESSION['stockmaster']=array();
    $POSclass->getready();
}

if(isset($_GET['delid'])) {
   $POSclass->deleteMasterdata($_GET['delid']);
}
 
 if(isset($_POST['remove'])){
   $POSclass->deleteRow($_POST['rowid']);
 }
    
 
    $SQL = "SELECT itemcode,barcode,descrip from stockmaster  where inactive=0 and isstock_1=1 order by descrip";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmaster'][$code]=$row;
    }

   
    if(!isset($_SESSION['units'])){
    $ResultIndex=DB_query("select code, descrip from unit",$db);
        while($row = DB_fetch_array($ResultIndex)){
           $code = trim($row['code']);
           $_SESSION['units'][$code]=$row;
       }
    }   

    $_SESSION['containers']=array();
    $result=DB_query("SELECT itemcode,descrip FROM stockmaster where isstock_6=1 ",$db);
        while ($myrow = DB_fetch_array($result)) {
          $code = trim($myrow['itemcode']);
           $_SESSION['containers'][$code]=$myrow;
    }  
 
    
if(isset($_POST['submit'])){
     if($_POST['submit']=='Save Price List'){
         $POSclass->save();
     } 
 }
 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<div class="centre">';
echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
  
?>

<table class="table-condensed table-responsive-small"><tr><td valign="top"><?php DisplayPOS(); ?></td>
        <td valign="top" rowspan="2">
  <table class="table-condensed table-responsive-small"><tr><td valign="top"><?php Entry(); ?></td></tr>
       <tr><td valign="top"><?php getstocklist(); ?><td></tr></table>
</td></tr><tr><td valign="top" ><?php $POSclass->showPriceList(); ?>
</td></tr></table>

<?php
echo '</div></form>';
include('includes/footer.inc');

function getstocklist(){
   
    $return= '<div><label>ENTER BARCODE<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()"  autofocus="autofocus" placeholder="Search for barcode.." ></label>
               <div class="posfinder"><table id="myStockTable" class="table table-bordered stockfind"><tr><th>BARCODE</th><th>INVENTORY NAME</th></tr>';
       
    foreach ($_SESSION['stockmaster'] as $key => $row){
    $return .= sprintf('<tr onclick="posInventory(\'%s\',\'%s\',\'%s\');"><td>%s</td><td>%s</td></tr>',
            trim($row['itemcode']),trim($row['barcode']), trim($row['descrip']),trim($row['barcode']), trim($row['descrip'])) ;
    }
   $return .= '</table></div></div>';
           
   echo $return;
}


Function Entry(){
 echo '<table class="table-bordered"><caption>Entry Window</caption><tr><td>'
    . '<input type="hidden" id="rowid" name="rowid" value="'.$_POST['rowid'].'" />'
    . '<input type="hidden" id="childid" name="childid" value="'.$_POST['childid'].'"  />'
    . '<input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'"/></td></tr>'
    . '<tr><td>Barcode</td><td><input class="col-sm-push-3" type="text" id="barcode" readonly="readonly" name="barcode" value="'.$_POST['barcode'].'"/></td></tr>'
    . '<tr><td>Item Description</td><td><input class="col-sm-push-3"  type="text" id="stockname" readonly="readonly" name="stockname" size="20" value="'.$_POST['stockname'].'"/></td></tr>'
    . '<tr><td>Measured by</td><td><select id="packid" name="units">';
             
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']);
           $selected = (($code==$_POST['units'])?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selected.'>'.$value['descrip'].'</option>';
    }
              
    echo '</select></td></tr>'
    . '<tr><td>Packing Container</td><td><select id="containerid" name="container"><option></option>';
             
    foreach ($_SESSION['containers'] as $key => $value) {
           $code = trim($value['itemcode']);
           $selected = (($code==$_POST['container'])?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selected.'>'.$value['descrip'].'</option>';
    }
    
             
    echo '</select></td></tr>
        <tr><td>Measurement Quantity</td><td><input class="col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="qty" name="qty" required="required" value="'.$_POST['qty'].'" onmouseleave="multiply(\'qty\',\'unitprice\',\'sp\');"/></td></tr>
        <tr><td>UNIT PRICE</td><td><input class="col-sm-push-3" tabindex="3" type="text" maxlength="20" size="10" id="unitprice" name="unitprice" required="required"  value="'.$_POST['unitprice'].'" onmouseleave="multiply(\'qty\',\'unitprice\',\'sp\');"/></td></tr>
        <tr><td>Selling Price as per Measurement</td><td><input class="col-sm-push-3"  type="text" maxlength="6" size="10" id="sp" name="sp" readonly="readonly"  value="'.$_POST['sp'].'"/></td></tr>
        <tr><td><input type="submit" name="refresh" value="Add/Update Record" class="btn-info"/></td>
        <td><input type="submit" name="remove" value="Remove Record" class="btn-danger"/></td></tr>
        </table>';
       
}

function DisplayPOS(){
global $db,$POSclass;
    
    echo '<table class="table-bordered">'
        . '<thead><tr>'
        . '<th><label>Code</label></th>'
        . '<th><label>Stock Description</label></th>'
        . '<th><label>Measured With</label></th>'
        . '<th><label>Pack size</label></th>'
        . '<th class="number"><label>Sales Price</label></th>'
        . '<th><label>Pack Container</label></th>'
        . '</tr>'
        . '</thead>';
    
    
   $POSclass->Getitems($_POST['stockitemcode'],$_POST['qty'],$_POST['units'],$_POST['sp'],$_POST['container']);
        
  echo '</table><table><tr><td>
	<input type="submit" name="submit" value="'. _('Refresh').'" /></td><td>
 	<input type="submit" name="submit" value="'._('Save Price List').'" /></td>'
          . '</table>';  
}

=======
<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
include('transactions/poscart.inc');
include('transactions/stockbalance.inc');   
$Title = _('Price List');
include('includes/header.inc');

 $POSclass = new PriceList();
 if(isset($_GET['new'])){
     $_SESSION['stockmaster']=array();
    $POSclass->getready();
}

if(isset($_GET['delid'])) {
   $POSclass->deleteMasterdata($_GET['delid']);
}
 
 if(isset($_POST['remove'])){
   $POSclass->deleteRow($_POST['rowid']);
 }
    
 
    $SQL = "SELECT itemcode,barcode,descrip from stockmaster  where inactive=0 and isstock_1=1 order by descrip";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmaster'][$code]=$row;
    }

   
    if(!isset($_SESSION['units'])){
    $ResultIndex=DB_query("select code, descrip from unit",$db);
        while($row = DB_fetch_array($ResultIndex)){
           $code = trim($row['code']);
           $_SESSION['units'][$code]=$row;
       }
    }   

    $_SESSION['containers']=array();
    $result=DB_query("SELECT itemcode,descrip FROM stockmaster where isstock_6=1 ",$db);
        while ($myrow = DB_fetch_array($result)) {
          $code = trim($myrow['itemcode']);
           $_SESSION['containers'][$code]=$myrow;
    }  
 
    
if(isset($_POST['submit'])){
     if($_POST['submit']=='Save Price List'){
         $POSclass->save();
     } 
 }
 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');
echo '<div class="centre">';
echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform"><input autocomplete="false" name="hidden" type="text" style="display:none;">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
  
?>

<table class="table-condensed table-responsive-small"><tr><td valign="top"><?php DisplayPOS(); ?></td>
        <td valign="top" rowspan="2">
  <table class="table-condensed table-responsive-small"><tr><td valign="top"><?php Entry(); ?></td></tr>
       <tr><td valign="top"><?php getstocklist(); ?><td></tr></table>
</td></tr><tr><td valign="top" ><?php $POSclass->showPriceList(); ?>
</td></tr></table>

<?php
echo '</div></form>';
include('includes/footer.inc');

function getstocklist(){
   
    $return= '<div><label>ENTER BARCODE<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()"  autofocus="autofocus" placeholder="Search for barcode.." ></label>
               <div class="posfinder"><table id="myStockTable" class="table table-bordered stockfind"><tr><th>BARCODE</th><th>INVENTORY NAME</th></tr>';
       
    foreach ($_SESSION['stockmaster'] as $key => $row){
    $return .= sprintf('<tr onclick="posInventory(\'%s\',\'%s\',\'%s\');"><td>%s</td><td>%s</td></tr>',
            trim($row['itemcode']),trim($row['barcode']), trim($row['descrip']),trim($row['barcode']), trim($row['descrip'])) ;
    }
   $return .= '</table></div></div>';
           
   echo $return;
}


Function Entry(){
 echo '<table class="table-bordered"><caption>Entry Window</caption><tr><td>'
    . '<input type="hidden" id="rowid" name="rowid" value="'.$_POST['rowid'].'" />'
    . '<input type="hidden" id="childid" name="childid" value="'.$_POST['childid'].'"  />'
    . '<input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'"/></td></tr>'
    . '<tr><td>Barcode</td><td><input class="col-sm-push-3" type="text" id="barcode" readonly="readonly" name="barcode" value="'.$_POST['barcode'].'"/></td></tr>'
    . '<tr><td>Item Description</td><td><input class="col-sm-push-3"  type="text" id="stockname" readonly="readonly" name="stockname" size="20" value="'.$_POST['stockname'].'"/></td></tr>'
    . '<tr><td>Measured by</td><td><select id="packid" name="units">';
             
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']);
           $selected = (($code==$_POST['units'])?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selected.'>'.$value['descrip'].'</option>';
    }
              
    echo '</select></td></tr>'
    . '<tr><td>Packing Container</td><td><select id="containerid" name="container"><option></option>';
             
    foreach ($_SESSION['containers'] as $key => $value) {
           $code = trim($value['itemcode']);
           $selected = (($code==$_POST['container'])?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selected.'>'.$value['descrip'].'</option>';
    }
    
             
    echo '</select></td></tr>
        <tr><td>Measurement Quantity</td><td><input class="col-sm-push-3" tabindex="2" type="text" maxlength="6" size="10" id="qty" name="qty" required="required" value="'.$_POST['qty'].'" onmouseleave="multiply(\'qty\',\'unitprice\',\'sp\');"/></td></tr>
        <tr><td>UNIT PRICE</td><td><input class="col-sm-push-3" tabindex="3" type="text" maxlength="20" size="10" id="unitprice" name="unitprice" required="required"  value="'.$_POST['unitprice'].'" onmouseleave="multiply(\'qty\',\'unitprice\',\'sp\');"/></td></tr>
        <tr><td>Selling Price as per Measurement</td><td><input class="col-sm-push-3"  type="text" maxlength="6" size="10" id="sp" name="sp" readonly="readonly"  value="'.$_POST['sp'].'"/></td></tr>
        <tr><td><input type="submit" name="refresh" value="Add/Update Record" class="btn-info"/></td>
        <td><input type="submit" name="remove" value="Remove Record" class="btn-danger"/></td></tr>
        </table>';
       
}

function DisplayPOS(){
global $db,$POSclass;
    
    echo '<table class="table-bordered">'
        . '<thead><tr>'
        . '<th><label>Code</label></th>'
        . '<th><label>Stock Description</label></th>'
        . '<th><label>Measured With</label></th>'
        . '<th><label>Pack size</label></th>'
        . '<th class="number"><label>Sales Price</label></th>'
        . '<th><label>Pack Container</label></th>'
        . '</tr>'
        . '</thead>';
    
    
   $POSclass->Getitems($_POST['stockitemcode'],$_POST['qty'],$_POST['units'],$_POST['sp'],$_POST['container']);
        
  echo '</table><table><tr><td>
	<input type="submit" name="submit" value="'. _('Refresh').'" /></td><td>
 	<input type="submit" name="submit" value="'._('Save Price List').'" /></td>'
          . '</table>';  
}

>>>>>>> fcf1da09040591d58a8bc754034c8da36e93ca78
 