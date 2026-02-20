<<<<<<< HEAD
<?php
include('includes/session.inc');

include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
include('transactions/poscart.inc');
include('transactions/stockbalance.inc');   
$Title = _('SALES QUOTE');
include('includes/header.inc');

function decodeHtmlEntities($string) {
    return html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

if(!isset($_POST['paymentterms'])){
  $_POST['paymentterms']=$_SESSION['paymentterms'];
} 

 $POSclass = new QUOTES();
 
 if(isset($_GET['new'])){
    $POSclass->neworder();
 }
 
 if(isset($_POST['confirm'])){
    DB_query("UPDATE [SalesHeader]  SET [released] = 1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
    DB_query("UPDATE [SalesLine]  SET [completed] = 1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
 
     echo sprintf('<p class="page_title_text"><a id="'.$_SESSION['CompleteDocument'].'" href="%s?No=%s" >'
      . '<img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" title="'. _('Print Sales Quote').'" alt="" />%s</a></p>',
        'PDFPrintSalesQuote.php',$_SESSION['CompleteDocument'], _('Print Sales Quote ').$_SESSION['CompleteDocument']);
    
    echo sprintf('<script type="text/javascript">ForcePDFPrint(\'%s\');</script>',$_SESSION['CompleteDocument']);

    unset($_POST);unset($_SESSION['CompleteDocument']);
}


 if(!isset($_SESSION['units'])){
    $ResultIndex=DB_query("select code, descrip from unit",$db);
     while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['code']);
        $_SESSION['units'][$code]=$row;
    }
}
 
     
 if(!isset($_POST['date'])){ 
    unset($_SESSION['stockmaster']) ;
    $ResultIndex = DB_query('Select getdate() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['date']= ConvertSQLDate($rowdate[0]); 
    $POSclass->neworder();
}
    
 
    $SQL = "SELECT itemcode,barcode, stockmaster.descrip from stockmaster  where inactive=0 and  isstock_1=1  
order by stockmaster.descrip";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmaster'][$code]=$row;
    }
 

 if(isset($_POST['remove'])){
   $POSclass->RemoveOrder($_POST['stockitemcode']);
 }
 
 if(isset($_POST['submit'])){
     
     if($_POST['submit']=='Save Quote'){
         $POSclass->SaveFooter();
         include('transactions/Quotereadonly.inc');
         $POSclass->neworder();
     } 
     
     if($_POST['submit']=='Cancel/Delete Quote'){
         DeletePOS($_SESSION['CompleteDocument']);
         unset($_SESSION['CompleteDocument']);
     }
     
 }elseif($_POST['submit']=='Re-Calculate' or !isset($_POST['submit'])){
 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
echo '<div id="salesPhotosModal" class="modal">
  <div class="modal-content">
      <span class="close">&times;</span>
      <span class="accept">&checkmark;</span>
    <div id="salesPhotosContainer">
      <div id="imageContainer"></div>
    </div>
  </div>
</div>
<table class="table-condensed table-responsive-small"><tr><td valign="top">'; DisplayPOS($POSclass) ; echo '</td><td valign="top" >
            <table class="table-condensed table-responsive-small"><tr><td valign="top">'; Entry(); echo '</td></tr>'
            . '<tr><td valign="top">This list comes from the price list module<br/>'; getstocklist(); echo '<td></tr></table>
</td></tr></table>';

echo '</div><input type="hidden" id="selectedRef"  name="selectedRef" value="'.$_POST['selectedRef'].'">
<input type="hidden" id="selectedImages" name="selectedImages" value="'.$_POST['selectedImages'].'"/></form>';
 
 }
include('includes/footer.inc');
 
function DisplayPOS($POSclass){
global $db;
$_POST['documentno'] = GetTempNextNo(54);
echo '<table class="table-bordered table-condensed">'
        . '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Document No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  id="salesid"  readonly="readonly" /></td></tr>';
   
echo '<tr><td><input type="button" id="searchcustomer" value="Search Customer"/></td>'
        . '<td><input type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"/>'
        . '</td>'
        . '<td>Quote Sent to:'
        . '<input type="text"  name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'" readonly="readonly"/></td>'   ;

echo '<td>Currency Code'
    . '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode"  value="'.$_POST['currencycode'].'" readonly="readonly"/></td>'
    . '</tr>'
        . '<tr><td>Sales REP Account</td>'
        . '<td><select name="salespersoncode" id="salespersoncode">'
        . '<option></option>';

$ResultIndex=DB_query("SELECT [code],[salesman],[commission],[inactive] FROM [salesrepsinfo] where [inactive] is null or [inactive]=0 ", $db);
while($row=DB_fetch_array($ResultIndex)){
   echo sprintf('<option value="%s" %s>%s</option>',
           $row['code'],($_POST['salespersoncode']==$row['code']?'selected="selected"':''),
           $row['salesman']);
}
    
echo '</select></td><td>CREDIT TERMS</td>'
        . '<td><input tabindex="5" type="text" name="terms" value="'.$_POST['terms'].'"  size="10" /></td></tr>';
        
       $SQLstment="SELECT accountcode,bankName,BranchName,currency FROM [BankAccounts] where currency='".$_POST['currencycode']."'";
        echo '<tr><td colspan="2">Select Bank to Deposit :</td><td colspan="2"><select name="Bank_Code"><option></option>';
                 
        $resultindex=DB_query($SQLstment, $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code'])){
                $selected =((trim($_POST['Bank_Code'])==trim($row['accountcode']))?'selected="selected"':'');
                echo  '<option value="'.$row['accountcode'].'"  '.$selected.'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }else{
                echo '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }
        }
        echo '</select></td></tr>';
        
        $SQLstment="SELECT accountcode,bankName,BranchName,currency "
        . "  FROM [BankAccounts] where currency='".$_POST['currencycode']."' and accountcode<> '".trim($_POST['Bank_Code'])."'";
        echo '<tr><td colspan="2">Select otption 2 Bank to Deposit :</td><td colspan="2"><select name="Bank_Code2">';
        $resultindex=DB_query($SQLstment, $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code2'])){
                $selected=((trim($_POST['Bank_Code2'])==trim($row['accountcode']))?'selected="selected"':'');
                echo  '<option value="'.$row['accountcode'].'"  '.$selected.'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }else{
                echo '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }
        }
        
        echo '</select></td></tr>' 
        . '<tr><td>Gallery</td><td colspan="4"><button id="getsalesphotos">Get Sales Photos</button></td></tr>';
 
        echo  '</table>';

    echo '<table class="table-bordered table-condensed">'
        . '<thead><tr>'
        . '<th><label>BarCode</label></th>'
        . '<th><label>Description</label></th>'
        . '<th><label>No Units</label></th>'
        . '<th><label>Unit Descrip</label></th>'
        . '<th><label>Each Contains</label></th>'
        . '<th><label>Sales Price</label></th>'
        . '<th><label>Net Amount</label></th>'
        . '<th><label>VAT Amount</label></th>'
        . '<th><label>Gross Amount</label></th>'
        . '</tr>'
        . '</thead>';
     
          $POSclass->Getitems($_POST['stockitemcode'],$_POST['qty'],$_POST['units'],$_POST['packzize'],$_POST['unitprice']);
        
  echo '</table><table class="table"><tr style="outline: 1px solid"><td>TERMS FOOTER</td><td><textarea name="paymentterms" id="ParameterName" style="width:100%; height:100%;">' . $_POST['paymentterms'] . '</textarea></td></tr>
<tr><td><input type="submit" name="refresh" value="'. _('Re-Calculate').'" /></td><td>
 	<input type="submit" name="submit" value="'._('Save Quote').'" /></td></tr>
        </table>';  
  
    $selectedImages = $_POST['selectedImages'];
    $decodedString = decodeHtmlEntities($selectedImages);
        
    echo getiamgeobject($decodedString);
}
 

function getstocklist(){
    $return= '<div><div class="table"><label>ENTER BARCODE<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()"  autofocus="autofocus" placeholder="Search for barcode.." ></label>
               <div class="posfinder"><table id="myStockTable" class="table-bordered stockfind"><tr><th>BARCODE</th><th>INVENTORY NAME</th></tr>';
       
    foreach ($_SESSION['stockmaster'] as $key => $row){
    $return .= sprintf('<tr onclick="posInventory(\'%s\',\'%s\',\'%s\');"><td>%s</td><td>%s</td></tr>',
            trim($row['itemcode']),
            trim($row['barcode']),
            trim($row['descrip']),
            trim($row['barcode']),
            trim($row['descrip'])) ;
    }
   $return .= '</table></div></div></div>';
           
   echo $return;
}

Function Entry(){
      Global $db;
  
      echo '<table class="table-bordered"><caption>Entry Window</caption><tr><td>'
    . '<input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'"/></td></tr>'
    . '<tr><td>Barcode</td><td><input class="col-sm-push-3" type="text" id="barcode" readonly="readonly" name="barcode" value="'.$_POST['barcode'].'"/></td></tr>'
    . '<tr><td>Item Description</td><td><input class="col-sm-push-3"  type="text" id="stockname" readonly="readonly" name="stockname" size="20" value="'.$_POST['stockname'].'"/></td></tr>'
    . '<tr><td>No of units</td><td><input class="number col-sm-push-3"  type="text" maxlength="6" size="10" id="qty" name="qty"  value="'.$_POST['qty'].'"/></td></tr>'
    . '<tr><td>Units Measure In</td><td><select id="packid" name="units">';
             
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']); $selunit =(($_POST['units']==$code)?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
    }
              
    echo '</select></td></tr>'
      . '<tr><td>Each Unit Contains</td><td><input class="number col-sm-push-3"  type="text" maxlength="6" size="10" id="packzize" name="packzize"  value="'.$_POST['packzize'].'"/>'
      . '<input type="hidden" id="sp" name="sp"/></td></tr>'
      . '<tr><td>Unit Price</td><td><input class="number col-sm-push-3"  type="text" maxlength="6" size="10" id="unitprice" name="unitprice"  value="'.$_POST['unitprice'].'"/></td></tr>'
      . '<tr><td><input type="submit" name="refresh" value="Add/Update Record" class="btn-info" /></td>'
      . '<td><input type="submit" name="remove" value="Remove Record" class="btn-danger"/></td></tr>'
      . '</table>';
       
}

Function DeletePOS($DOC){
    global $db;
    
    DB_query("Delete from [Salesline] where documentno='".$DOC."' and [documenttype]='15'", $db);
    DB_query("delete from [SalesHeader] where documentno='".$DOC."' and [documenttype]='15'", $db);
    prnMsg('Order :'.$DOC.' has been Deleted.');
    unset($_POST);
}

function getiamgeobject($urlString){
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
=======
<?php
include('includes/session.inc');

include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
include('includes/SQL_CommonFunctions.inc');
include('includes/PostStockCost.inc');  
include('transactions/poscart.inc');
include('transactions/stockbalance.inc');   
$Title = _('SALES QUOTE');
include('includes/header.inc');

function decodeHtmlEntities($string) {
    return html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

if(!isset($_POST['paymentterms'])){
  $_POST['paymentterms']=$_SESSION['paymentterms'];
} 

 $POSclass = new QUOTES();
 
 if(isset($_GET['new'])){
    $POSclass->neworder();
 }
 
 if(isset($_POST['confirm'])){
    DB_query("UPDATE [SalesHeader]  SET [released] = 1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
    DB_query("UPDATE [SalesLine]  SET [completed] = 1 where documentno='".$_SESSION['CompleteDocument']."'", $db);
 
     echo sprintf('<p class="page_title_text"><a id="'.$_SESSION['CompleteDocument'].'" href="%s?No=%s" >'
      . '<img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" title="'. _('Print Sales Quote').'" alt="" />%s</a></p>',
        'PDFPrintSalesQuote.php',$_SESSION['CompleteDocument'], _('Print Sales Quote ').$_SESSION['CompleteDocument']);
    
    echo sprintf('<script type="text/javascript">ForcePDFPrint(\'%s\');</script>',$_SESSION['CompleteDocument']);

    unset($_POST);unset($_SESSION['CompleteDocument']);
}


 if(!isset($_SESSION['units'])){
    $ResultIndex=DB_query("select code, descrip from unit",$db);
     while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['code']);
        $_SESSION['units'][$code]=$row;
    }
}
 
     
 if(!isset($_POST['date'])){ 
    unset($_SESSION['stockmaster']) ;
    $ResultIndex = DB_query('Select getdate() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['date']= ConvertSQLDate($rowdate[0]); 
    $POSclass->neworder();
}
    
 
    $SQL = "SELECT itemcode,barcode, stockmaster.descrip from stockmaster  where inactive=0 and  isstock_1=1  
order by stockmaster.descrip";
    $ResultIndex=DB_query($SQL, $db);
    while($row = DB_fetch_array($ResultIndex)){
        $code = trim($row['itemcode']);
        $_SESSION['stockmaster'][$code]=$row;
    }
 

 if(isset($_POST['remove'])){
   $POSclass->RemoveOrder($_POST['stockitemcode']);
 }
 
 if(isset($_POST['submit'])){
     
     if($_POST['submit']=='Save Quote'){
         $POSclass->SaveFooter();
         include('transactions/Quotereadonly.inc');
         $POSclass->neworder();
     } 
     
     if($_POST['submit']=='Cancel/Delete Quote'){
         DeletePOS($_SESSION['CompleteDocument']);
         unset($_SESSION['CompleteDocument']);
     }
     
 }elseif($_POST['submit']=='Re-Calculate' or !isset($_POST['submit'])){
 
$pge = htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8');

echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<div>';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" />';
echo '<div id="salesPhotosModal" class="modal">
  <div class="modal-content">
      <span class="close">&times;</span>
      <span class="accept">&checkmark;</span>
    <div id="salesPhotosContainer">
      <div id="imageContainer"></div>
    </div>
  </div>
</div>
<table class="table-condensed table-responsive-small"><tr><td valign="top">'; DisplayPOS($POSclass) ; echo '</td><td valign="top" >
            <table class="table-condensed table-responsive-small"><tr><td valign="top">'; Entry(); echo '</td></tr>'
            . '<tr><td valign="top">This list comes from the price list module<br/>'; getstocklist(); echo '<td></tr></table>
</td></tr></table>';

echo '</div><input type="hidden" id="selectedRef"  name="selectedRef" value="'.$_POST['selectedRef'].'">
<input type="hidden" id="selectedImages" name="selectedImages" value="'.$_POST['selectedImages'].'"/></form>';
 
 }
include('includes/footer.inc');
 
function DisplayPOS($POSclass){
global $db;
$_POST['documentno'] = GetTempNextNo(54);
echo '<table class="table-bordered table-condensed">'
        . '<tr><td>Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="date" size="11" maxlength="10" autofocus="autofocus" required="required" value="' .$_POST['date']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>';
echo '<td>Document No</td>'
        . '<td><input tabindex="4" type="text" name="documentno" value="'.$_POST['documentno'].'"  id="salesid"  readonly="readonly" /></td></tr>';
   
echo '<tr><td><input type="button" id="searchcustomer" value="Search Customer"/></td>'
        . '<td><input type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"/>'
        . '</td>'
        . '<td>Quote Sent to:'
        . '<input type="text"  name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'" readonly="readonly"/></td>'   ;

echo '<td>Currency Code'
    . '<input tabindex="6" type="text" id="currencycode" size="5" name="currencycode"  value="'.$_POST['currencycode'].'" readonly="readonly"/></td>'
    . '</tr>'
        . '<tr><td>Sales REP Account</td>'
        . '<td><select name="salespersoncode" id="salespersoncode">'
        . '<option></option>';

$ResultIndex=DB_query("SELECT [code],[salesman],[commission],[inactive] FROM [salesrepsinfo] where [inactive] is null or [inactive]=0 ", $db);
while($row=DB_fetch_array($ResultIndex)){
   echo sprintf('<option value="%s" %s>%s</option>',
           $row['code'],($_POST['salespersoncode']==$row['code']?'selected="selected"':''),
           $row['salesman']);
}
    
echo '</select></td><td>CREDIT TERMS</td>'
        . '<td><input tabindex="5" type="text" name="terms" value="'.$_POST['terms'].'"  size="10" /></td></tr>';
        
       $SQLstment="SELECT accountcode,bankName,BranchName,currency FROM [BankAccounts] where currency='".$_POST['currencycode']."'";
        echo '<tr><td colspan="2">Select Bank to Deposit :</td><td colspan="2"><select name="Bank_Code"><option></option>';
                 
        $resultindex=DB_query($SQLstment, $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code'])){
                $selected =((trim($_POST['Bank_Code'])==trim($row['accountcode']))?'selected="selected"':'');
                echo  '<option value="'.$row['accountcode'].'"  '.$selected.'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }else{
                echo '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }
        }
        echo '</select></td></tr>';
        
        $SQLstment="SELECT accountcode,bankName,BranchName,currency "
        . "  FROM [BankAccounts] where currency='".$_POST['currencycode']."' and accountcode<> '".trim($_POST['Bank_Code'])."'";
        echo '<tr><td colspan="2">Select otption 2 Bank to Deposit :</td><td colspan="2"><select name="Bank_Code2">';
        $resultindex=DB_query($SQLstment, $db);
        while($row=DB_fetch_array($resultindex)){
            if(Isset($_POST['Bank_Code2'])){
                $selected=((trim($_POST['Bank_Code2'])==trim($row['accountcode']))?'selected="selected"':'');
                echo  '<option value="'.$row['accountcode'].'"  '.$selected.'>'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }else{
                echo '<option value="'.$row['accountcode'].'">'.$row['bankName'].' '.$row['BranchName'].' '.$row['currency'].'</option>';
             }
        }
        
        echo '</select></td></tr>' 
        . '<tr><td>Gallery</td><td colspan="4"><button id="getsalesphotos">Get Sales Photos</button></td></tr>';
 
        echo  '</table>';

    echo '<table class="table-bordered table-condensed">'
        . '<thead><tr>'
        . '<th><label>BarCode</label></th>'
        . '<th><label>Description</label></th>'
        . '<th><label>No Units</label></th>'
        . '<th><label>Unit Descrip</label></th>'
        . '<th><label>Each Contains</label></th>'
        . '<th><label>Sales Price</label></th>'
        . '<th><label>Net Amount</label></th>'
        . '<th><label>VAT Amount</label></th>'
        . '<th><label>Gross Amount</label></th>'
        . '</tr>'
        . '</thead>';
     
          $POSclass->Getitems($_POST['stockitemcode'],$_POST['qty'],$_POST['units'],$_POST['packzize'],$_POST['unitprice']);
        
  echo '</table><table class="table"><tr style="outline: 1px solid"><td>TERMS FOOTER</td><td><textarea name="paymentterms" id="ParameterName" style="width:100%; height:100%;">' . $_POST['paymentterms'] . '</textarea></td></tr>
<tr><td><input type="submit" name="refresh" value="'. _('Re-Calculate').'" /></td><td>
 	<input type="submit" name="submit" value="'._('Save Quote').'" /></td></tr>
        </table>';  
  
    $selectedImages = $_POST['selectedImages'];
    $decodedString = decodeHtmlEntities($selectedImages);
        
    echo getiamgeobject($decodedString);
}
 

function getstocklist(){
    $return= '<div><div class="table"><label>ENTER BARCODE<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()"  autofocus="autofocus" placeholder="Search for barcode.." ></label>
               <div class="posfinder"><table id="myStockTable" class="table-bordered stockfind"><tr><th>BARCODE</th><th>INVENTORY NAME</th></tr>';
       
    foreach ($_SESSION['stockmaster'] as $key => $row){
    $return .= sprintf('<tr onclick="posInventory(\'%s\',\'%s\',\'%s\');"><td>%s</td><td>%s</td></tr>',
            trim($row['itemcode']),
            trim($row['barcode']),
            trim($row['descrip']),
            trim($row['barcode']),
            trim($row['descrip'])) ;
    }
   $return .= '</table></div></div></div>';
           
   echo $return;
}

Function Entry(){
      Global $db;
  
      echo '<table class="table-bordered"><caption>Entry Window</caption><tr><td>'
    . '<input type="hidden" id="stockitemcode" name="stockitemcode" value="'.$_POST['stockitemcode'].'"/></td></tr>'
    . '<tr><td>Barcode</td><td><input class="col-sm-push-3" type="text" id="barcode" readonly="readonly" name="barcode" value="'.$_POST['barcode'].'"/></td></tr>'
    . '<tr><td>Item Description</td><td><input class="col-sm-push-3"  type="text" id="stockname" readonly="readonly" name="stockname" size="20" value="'.$_POST['stockname'].'"/></td></tr>'
    . '<tr><td>No of units</td><td><input class="number col-sm-push-3"  type="text" maxlength="6" size="10" id="qty" name="qty"  value="'.$_POST['qty'].'"/></td></tr>'
    . '<tr><td>Units Measure In</td><td><select id="packid" name="units">';
             
    foreach ($_SESSION['units'] as $key => $value) {
           $code = trim($value['code']); $selunit =(($_POST['units']==$code)?'selected="selected"':'');
           echo '<option value="'.$code.'" '.$selunit.'>'.$value['descrip'].'</option>';
    }
              
    echo '</select></td></tr>'
      . '<tr><td>Each Unit Contains</td><td><input class="number col-sm-push-3"  type="text" maxlength="6" size="10" id="packzize" name="packzize"  value="'.$_POST['packzize'].'"/>'
      . '<input type="hidden" id="sp" name="sp"/></td></tr>'
      . '<tr><td>Unit Price</td><td><input class="number col-sm-push-3"  type="text" maxlength="6" size="10" id="unitprice" name="unitprice"  value="'.$_POST['unitprice'].'"/></td></tr>'
      . '<tr><td><input type="submit" name="refresh" value="Add/Update Record" class="btn-info" /></td>'
      . '<td><input type="submit" name="remove" value="Remove Record" class="btn-danger"/></td></tr>'
      . '</table>';
       
}

Function DeletePOS($DOC){
    global $db;
    
    DB_query("Delete from [Salesline] where documentno='".$DOC."' and [documenttype]='15'", $db);
    DB_query("delete from [SalesHeader] where documentno='".$DOC."' and [documenttype]='15'", $db);
    prnMsg('Order :'.$DOC.' has been Deleted.');
    unset($_POST);
}

function getiamgeobject($urlString){
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
>>>>>>> fcf1da09040591d58a8bc754034c8da36e93ca78
}