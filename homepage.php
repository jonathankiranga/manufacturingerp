<<<<<<< HEAD
<?php
$PageSecurity=0;
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');;

echo '<link rel="shortcut icon" href="'.$RootPath.'/comerp.ico" />';
echo '<link rel="icon" href="'.$RootPath.'/comerp.ico" />';
    
echo '<link rel="stylesheet" href="'.$RootPath.'/css/bootstrap.min.css"  type="text/css"/>
      <link rel="stylesheet" href="'.$RootPath.'/css/bootstrap-responsive.min.css"  type="text/css"/>
      <link rel="stylesheet" href="'.$RootPath.'/css/homepage.css" type="text/css"/>';

echo '<script type="text/javascript" src="'.$RootPath.'/javascripts/1.9.1/jquery.min.js"></script>
      <script type="text/javascript" src="'.$RootPath.'/Javascripts/2.3.1/bootstrap.min.js"></script>
      <script type="text/javascript" src="'.$RootPath.'/javascripts/JQueryclases.mim.js"></script>
      <script type="text/javascript" src="'.$RootPath.'/javascripts/MiscFunctions.min.js"></script>'; 
      
echo '<div class="overlay"></div><DIV class="homePageOne" id="h1k" onmouseover="dragElement(this);">'
. '<DIV class="homepage centre"><ul>' . _('Documents Pending Approval') . '  '.' <img src="'.$RootPath.'/css/'.$Theme.'/images/tick.png"/></ul></DIV><ul>'
. '<li>' . _('Purchase Orders') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/supplier.png" /><a href="ApprovePurchase.php" target="mainContentIFrame">'.CountPO(1).'</a></li>'
. '<li>' . _('Store Requests') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" /><a href="ApproveStcokissues.php" target="mainContentIFrame">'.CountPO(2).'</a></li>'
. '<li>' . _('Payment Vouchers(Head of Finance)') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/bank.png" /><a href="PDFFAMpaymentvoucher.php" target="mainContentIFrame">'.CountPO(3).'</a></li>'
. '<li>' . _('Payment Vouchers(C.E.O)') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/bank.png" /><a href="PDFCEOpaymentvoucher.php" target="mainContentIFrame">'.CountPO(4).'</a></li>'
. '<li>' . _('Price list for Sales Reps') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" /><a href="ApprovePriceList.php" target="mainContentIFrame">'.CountPO(6).'</a></li>'
. '<li>' . _('Product Test Pending') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" /><a href="LaboratoryDataEntry.php" target="mainContentIFrame">'.CountPO(7).'</a></li>'
. '</ul></div>';

        
echo '<DIV class="homePageTwo" id="h2k" onmouseover="dragElement(this);">'
. '<DIV class="homepage centre"><UL>' . _('To Do LIST') . '  '.' <img src="'.$RootPath.'/css/'.$Theme.'/images/help.png"/></ul></DIV><ul>'
. '<li>' . _('Update Commisions ') . '  '.'<img  src="'.$RootPath.'/css/'.$Theme.'/images/help.png"/><a id="Checkwhenpaid"  target="mainContentIFrame">Update Commisions</a></li>'
. '<li>' . _('Replenish Stock') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" />'.CountPO(14).'</li>'
. '<li>' . _('Un-Delivered Purchase Orders ') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/supplier.png" /><a href="PurchaseOrderList.php" target="mainContentIFrame">'.CountPO(9).'</a></li>'
. '<li>' . _('Un-invoiced Purchase Orders ') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/supplier.png" /><a href="GoodsReceivedNote.php" target="mainContentIFrame">'.CountPO(11).'</a></li>'
. '<li>' . _('Un-posted Cheques') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/bank.png" /><a href="WriteCheque.php" target="mainContentIFrame">'.CountPO(5).'</a></li>'
. '<li>' . _('Un-Collected Sales Orders ') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" /><a href="SalesDelivery.php" target="mainContentIFrame">'.CountPO(10).'</a></li>'
. '<li>' . _('Un-posted Sales Orders') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" /><a href="SalesInvoice.php" target="mainContentIFrame">'.CountPO(12).'</a></li>'
. '</ul></div>';

echo '<DIV class="homePagetree" id="h3k" onmouseover="dragElement(this);">'
  . '<DIV class="centre"><div><div><ul class="nav nav-list"><li><label class="tree-toggler nav-header">My Banks</label>';
	$BankSecurity = $_SESSION['PageSecurityArray'][basename('BankReconciliation.php')]; 
  if ((in_array($BankSecurity, $_SESSION['AllowedPageSecurityTokens']) )) {
   echo '<ul class="nav nav-list tree">'. GetMyBankBalances().'</ul></li></ul></div></div>';
  }
  
  echo '  <DIV class="centre"><div><div><ul class="nav nav-list"><li><label class="tree-toggler nav-header">My Tasks<br><a href="crmclientsTasks.php?new=yes" target="mainContentIFrame">New Task</a></label>
  <ul class="nav nav-list tree">'. GetMytacks().'</ul></li></ul></div></div>'
   . '<div><div><ul class="nav nav-list"><li><label class="tree-toggler nav-header">My Activities<br><a href="crmclientsActivity.php?new=yes" target="mainContentIFrame">New Activity</a></label>
    <ul class="nav nav-list tree">'. GetMyActivities().'</ul></li></ul></div></div>';
echo '<script type="text/javascript" src="'.$RootPath.'/Javascripts/treeview/tree.js"></script>';

Function GetMyBankBalances(){
    global $db;
    
    $sql="SELECT 
           [accountcode]
          ,[bankName]
          ,[currency]
          ,[lastreconcileddate]
          ,[AccountNo]
          ,[BranchCode]
          ,[BranchName]
          ,[lastreconbalance]
          ,[lastChequeno]
          ,[PostingGroup]
          ,[Fluctuation]
          ,[Makeinactive]
          ,lastreconbalance
          ,[AcctName]
          ,[bankCode]
          ,[swiftcode]
      FROM [BankAccounts]
      where ([Makeinactive]=0 or [Makeinactive] is null)";
    
   $lineecho= '<table class="table-striped table-bordered" style="font-size: 14px;">';
    $ResultIndex=DB_query($sql,$db);
   while( $rowbanks = DB_fetch_array($ResultIndex)){
    $lineecho .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td class="number">%s</td></tr>',
    $rowbanks['bankName'],$rowbanks['currency'],
    _('Last Recon Date :'). ConvertSQLDate($rowbanks['lastreconcileddate']),
    _('last Statemet balance :').$rowbanks['lastreconbalance'],
    _('CB balance :').getbanklastbalance($rowbanks['accountcode']));
     }
   
   $lineecho .= '</table>';
   return $lineecho;
}

function getbanklastbalance($accountcode){
     global $db;
    
    $ResultIndex = DB_query("Select sum([amount]) from [BankTransactions]  where ([bankcode]='".$accountcode."' ) ",$db);
    $cashbookbalance=DB_fetch_row($ResultIndex);
    $show=number_format($cashbookbalance[0],2);
    if (in_array($_SESSION['PageSecurityArray']['WWW_Users.php'], $_SESSION['AllowedPageSecurityTokens'])) {
        $return =$show;
    }else{
         $return ='Information not avilable';
    }
    return $return;
}

function Getstockbalance($itemcode){
    global $db;
   
    $SQLSTMENT="select  
                    stockmaster.[descrip] ,
                    unit.descrip as uom,
                    (sum([stockledger].[PartPerUnit] * stockledger.fulqty) + sum(stockledger.loosqty))
                from [stockmaster]  
                join stockledger on stockmaster.itemcode=stockledger.itemcode 
                left join [unit] on [stockmaster].[units]=[unit].code
                where stockmaster.itemcode='".$itemcode."'
		GROUP BY [stockledger].[PartPerUnit],
                [stockmaster].reorderlevel,
                stockmaster.[descrip],
                unit.descrip";
    
     $results =  DB_query($SQLSTMENT,$db);
     $row =DB_fetch_row($results);
     
return number_format($row[2],0) .' '.$row[1]	;
}

function GetContact($pkey){
    global $db;
    
     $result=DB_query("SELECT [company]
      ,[postcode] ,[city] ,[country],[Physical_Address],[PIN_VAT] ,[phone] ,[email] ,[salesman]
      ,[Contact_Name] ,[Contact_Designation],[Contact_Telephone],[Contact_email]
      ,[Alt_Contact_Name] ,[Alt_Contact_Designation],[Alt_Contact_Telephone],[Alt_Contact_email]
      ,[createdby]  FROM [NewContacts] where pkey='".$pkey."'",$db);
    $myrow = DB_fetch_row($result);
    return $myrow;
}

function CountPO($Index=0){
    Global $db;
    $sqlarray=array();
    
    $sqlarray[1] ="select count(*) from [PurchaseHeader] where [PurchaseHeader].[documenttype]='18' and [PurchaseHeader].[released] IS NULL ";
    $sqlarray[2] ="select count(*) from [SalesHeader] where [SalesHeader].[documenttype]='40' and [SalesHeader].[status]=1 ";
    $sqlarray[3] ="select count(*) FROM [paymentvoucherheader] where [paymentvoucherheader].[status]=0 ";
    $sqlarray[4] ="select count(*) FROM [paymentvoucherheader] where [paymentvoucherheader].[status]=1 ";
    $sqlarray[5] ="select count(*) FROM [paymentvoucherheader] where [paymentvoucherheader].[status]=2 ";
    $sqlarray[6] ="SELECT count(*) FROM [PriceList] where approved=0 and customerCode='' or  customerCode is null";
    $sqlarray[7] ="SELECT count(*) FROM [ProductionMaster] where [testreport] is null and [itemcode] is not null";
    $sqlarray[8] ="select count(*) from [PurchaseHeader] where printed is null or printed=0";
    $sqlarray[9] ="select count(*) from [PurchaseHeader] where [documenttype]='18' and [released]=1";
    $sqlarray[10]="select count(*) from SalesHeader where [documenttype]='1' and [released]=1";
    $sqlarray[11]="select count(*) from [PurchaseHeader] where [documenttype]='18' and [released]=1";
    $sqlarray[12]="select count(*) from SalesHeader where [documenttype]='1' and [released]=1";
    $sqlarray[13]="select count(*) from SalesHeader where [documenttype]='32' and [released]=1";
    $sqlarray[14]="select count(*) FROM [stockmaster] where stockmaster.itemcode in
        (select  stockmaster.itemcode  from [stockmaster] 
        join stockledger on stockmaster.itemcode=stockledger.itemcode
		GROUP BY [stockledger].[PartPerUnit],[stockmaster].reorderlevel,stockmaster.itemcode
		having ([stockmaster].reorderlevel) >  (sum([stockledger].[PartPerUnit] * stockledger.fulqty) + sum(stockledger.loosqty))
		)  ";
    
        $results =  DB_query($sqlarray[$Index], $db);
        if(DB_num_rows($results)>0){
             $myrow=DB_fetch_row($results);
            if($myrow[0]>0){
                if($Index==14){
                         
                $SQLSTMENT="select  
                    stockmaster.[descrip] ,
                    stockmaster.[eoq] ,
                    stockmaster.itemcode
                from [stockmaster] 
                join stockledger on stockmaster.itemcode=stockledger.itemcode
		GROUP BY  
                stockmaster.itemcode,
                [stockledger].[PartPerUnit],
                [stockmaster].reorderlevel,
                stockmaster.[descrip],
                stockmaster.[eoq]
		having ([stockmaster].reorderlevel) > (sum([stockledger].[PartPerUnit] * stockledger.fulqty) + sum(stockledger.loosqty)) ";
            
                $return='<div>'
                        . '<div>'
                        . '<ul class="nav nav-list">'
                        . '<li><label class="tree-toggler nav-header">Purchase Or Manufacture The Following</label>'
                        . '<ul class="nav nav-list tree">';

                 $results =  DB_query($SQLSTMENT,$db);
                 while($row= DB_fetch_array($results)){  
                     $return .='<li><label class="tree-toggler nav-header grouper">'.$row['descrip'].' :- Current Stock Balance :'.Getstockbalance($row['itemcode']).'</label></li>'; 
                  }

                 $return .='</ul></li></ul>'
                         . '</div></div>';
                        
  
                }else{
                    $return = (($myrow[0]>0)?_('There is some work here'):'');
                }
            }else{
                $return="No items, Good work !";
            }
        }else{
           $return="No items";
        }
        
  
  return $return;
}
 

=======
<?php
$PageSecurity=0;
include('includes/session.inc');
include('includes/SQL_CommonFunctions.inc');;

echo '<link rel="shortcut icon" href="'.$RootPath.'/comerp.ico" />';
echo '<link rel="icon" href="'.$RootPath.'/comerp.ico" />';
    
echo '<link rel="stylesheet" href="'.$RootPath.'/css/bootstrap.min.css"  type="text/css"/>
      <link rel="stylesheet" href="'.$RootPath.'/css/bootstrap-responsive.min.css"  type="text/css"/>
      <link rel="stylesheet" href="'.$RootPath.'/css/homepage.css" type="text/css"/>';

echo '<script type="text/javascript" src="'.$RootPath.'/javascripts/1.9.1/jquery.min.js"></script>
      <script type="text/javascript" src="'.$RootPath.'/Javascripts/2.3.1/bootstrap.min.js"></script>
      <script type="text/javascript" src="'.$RootPath.'/javascripts/JQueryclases.mim.js"></script>
      <script type="text/javascript" src="'.$RootPath.'/javascripts/MiscFunctions.min.js"></script>'; 
      
echo '<div class="overlay"></div><DIV class="homePageOne" id="h1k" onmouseover="dragElement(this);">'
. '<DIV class="homepage centre"><ul>' . _('Documents Pending Approval') . '  '.' <img src="'.$RootPath.'/css/'.$Theme.'/images/tick.png"/></ul></DIV><ul>'
. '<li>' . _('Purchase Orders') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/supplier.png" /><a href="ApprovePurchase.php" target="mainContentIFrame">'.CountPO(1).'</a></li>'
. '<li>' . _('Store Requests') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" /><a href="ApproveStcokissues.php" target="mainContentIFrame">'.CountPO(2).'</a></li>'
. '<li>' . _('Payment Vouchers(Head of Finance)') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/bank.png" /><a href="PDFFAMpaymentvoucher.php" target="mainContentIFrame">'.CountPO(3).'</a></li>'
. '<li>' . _('Payment Vouchers(C.E.O)') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/bank.png" /><a href="PDFCEOpaymentvoucher.php" target="mainContentIFrame">'.CountPO(4).'</a></li>'
. '<li>' . _('Price list for Sales Reps') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" /><a href="ApprovePriceList.php" target="mainContentIFrame">'.CountPO(6).'</a></li>'
. '<li>' . _('Product Test Pending') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" /><a href="LaboratoryDataEntry.php" target="mainContentIFrame">'.CountPO(7).'</a></li>'
. '</ul></div>';

        
echo '<DIV class="homePageTwo" id="h2k" onmouseover="dragElement(this);">'
. '<DIV class="homepage centre"><UL>' . _('To Do LIST') . '  '.' <img src="'.$RootPath.'/css/'.$Theme.'/images/help.png"/></ul></DIV><ul>'
. '<li>' . _('Update Commisions ') . '  '.'<img  src="'.$RootPath.'/css/'.$Theme.'/images/help.png"/><a id="Checkwhenpaid"  target="mainContentIFrame">Update Commisions</a></li>'
. '<li>' . _('Replenish Stock') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/inventory.png" />'.CountPO(14).'</li>'
. '<li>' . _('Un-Delivered Purchase Orders ') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/supplier.png" /><a href="PurchaseOrderList.php" target="mainContentIFrame">'.CountPO(9).'</a></li>'
. '<li>' . _('Un-invoiced Purchase Orders ') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/supplier.png" /><a href="GoodsReceivedNote.php" target="mainContentIFrame">'.CountPO(11).'</a></li>'
. '<li>' . _('Un-posted Cheques') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/bank.png" /><a href="WriteCheque.php" target="mainContentIFrame">'.CountPO(5).'</a></li>'
. '<li>' . _('Un-Collected Sales Orders ') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" /><a href="SalesDelivery.php" target="mainContentIFrame">'.CountPO(10).'</a></li>'
. '<li>' . _('Un-posted Sales Orders') . '  '.'<img src="'.$RootPath.'/css/'.$Theme.'/images/customer.png" /><a href="SalesInvoice.php" target="mainContentIFrame">'.CountPO(12).'</a></li>'
. '</ul></div>';

echo '<DIV class="homePagetree" id="h3k" onmouseover="dragElement(this);">'
  . '<DIV class="centre"><div><div><ul class="nav nav-list"><li><label class="tree-toggler nav-header">My Banks</label>';
	$BankSecurity = $_SESSION['PageSecurityArray'][basename('BankReconciliation.php')]; 
  if ((in_array($BankSecurity, $_SESSION['AllowedPageSecurityTokens']) )) {
   echo '<ul class="nav nav-list tree">'. GetMyBankBalances().'</ul></li></ul></div></div>';
  }
  
  echo '  <DIV class="centre"><div><div><ul class="nav nav-list"><li><label class="tree-toggler nav-header">My Tasks<br><a href="crmclientsTasks.php?new=yes" target="mainContentIFrame">New Task</a></label>
  <ul class="nav nav-list tree">'. GetMytacks().'</ul></li></ul></div></div>'
   . '<div><div><ul class="nav nav-list"><li><label class="tree-toggler nav-header">My Activities<br><a href="crmclientsActivity.php?new=yes" target="mainContentIFrame">New Activity</a></label>
    <ul class="nav nav-list tree">'. GetMyActivities().'</ul></li></ul></div></div>';
echo '<script type="text/javascript" src="'.$RootPath.'/Javascripts/treeview/tree.js"></script>';

Function GetMyBankBalances(){
    global $db;
    
    $sql="SELECT 
           [accountcode]
          ,[bankName]
          ,[currency]
          ,[lastreconcileddate]
          ,[AccountNo]
          ,[BranchCode]
          ,[BranchName]
          ,[lastreconbalance]
          ,[lastChequeno]
          ,[PostingGroup]
          ,[Fluctuation]
          ,[Makeinactive]
          ,lastreconbalance
          ,[AcctName]
          ,[bankCode]
          ,[swiftcode]
      FROM [BankAccounts]
      where ([Makeinactive]=0 or [Makeinactive] is null)";
    
   $lineecho= '<table class="table-striped table-bordered" style="font-size: 14px;">';
    $ResultIndex=DB_query($sql,$db);
   while( $rowbanks = DB_fetch_array($ResultIndex)){
    $lineecho .= sprintf('<tr><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td class="number">%s</td></tr>',
    $rowbanks['bankName'],$rowbanks['currency'],
    _('Last Recon Date :'). ConvertSQLDate($rowbanks['lastreconcileddate']),
    _('last Statemet balance :').$rowbanks['lastreconbalance'],
    _('CB balance :').getbanklastbalance($rowbanks['accountcode']));
     }
   
   $lineecho .= '</table>';
   return $lineecho;
}

function getbanklastbalance($accountcode){
     global $db;
    
    $ResultIndex = DB_query("Select sum([amount]) from [BankTransactions]  where ([bankcode]='".$accountcode."' ) ",$db);
    $cashbookbalance=DB_fetch_row($ResultIndex);
    $show=number_format($cashbookbalance[0],2);
    if (in_array($_SESSION['PageSecurityArray']['WWW_Users.php'], $_SESSION['AllowedPageSecurityTokens'])) {
        $return =$show;
    }else{
         $return ='Information not avilable';
    }
    return $return;
}

function Getstockbalance($itemcode){
    global $db;
   
    $SQLSTMENT="select  
                    stockmaster.[descrip] ,
                    unit.descrip as uom,
                    (sum([stockledger].[PartPerUnit] * stockledger.fulqty) + sum(stockledger.loosqty))
                from [stockmaster]  
                join stockledger on stockmaster.itemcode=stockledger.itemcode 
                left join [unit] on [stockmaster].[units]=[unit].code
                where stockmaster.itemcode='".$itemcode."'
		GROUP BY [stockledger].[PartPerUnit],
                [stockmaster].reorderlevel,
                stockmaster.[descrip],
                unit.descrip";
    
     $results =  DB_query($SQLSTMENT,$db);
     $row =DB_fetch_row($results);
     
return number_format($row[2],0) .' '.$row[1]	;
}

function GetContact($pkey){
    global $db;
    
     $result=DB_query("SELECT [company]
      ,[postcode] ,[city] ,[country],[Physical_Address],[PIN_VAT] ,[phone] ,[email] ,[salesman]
      ,[Contact_Name] ,[Contact_Designation],[Contact_Telephone],[Contact_email]
      ,[Alt_Contact_Name] ,[Alt_Contact_Designation],[Alt_Contact_Telephone],[Alt_Contact_email]
      ,[createdby]  FROM [NewContacts] where pkey='".$pkey."'",$db);
    $myrow = DB_fetch_row($result);
    return $myrow;
}

function CountPO($Index=0){
    Global $db;
    $sqlarray=array();
    
    $sqlarray[1] ="select count(*) from [PurchaseHeader] where [PurchaseHeader].[documenttype]='18' and [PurchaseHeader].[released] IS NULL ";
    $sqlarray[2] ="select count(*) from [SalesHeader] where [SalesHeader].[documenttype]='40' and [SalesHeader].[status]=1 ";
    $sqlarray[3] ="select count(*) FROM [paymentvoucherheader] where [paymentvoucherheader].[status]=0 ";
    $sqlarray[4] ="select count(*) FROM [paymentvoucherheader] where [paymentvoucherheader].[status]=1 ";
    $sqlarray[5] ="select count(*) FROM [paymentvoucherheader] where [paymentvoucherheader].[status]=2 ";
    $sqlarray[6] ="SELECT count(*) FROM [PriceList] where approved=0 and customerCode='' or  customerCode is null";
    $sqlarray[7] ="SELECT count(*) FROM [ProductionMaster] where [testreport] is null and [itemcode] is not null";
    $sqlarray[8] ="select count(*) from [PurchaseHeader] where printed is null or printed=0";
    $sqlarray[9] ="select count(*) from [PurchaseHeader] where [documenttype]='18' and [released]=1";
    $sqlarray[10]="select count(*) from SalesHeader where [documenttype]='1' and [released]=1";
    $sqlarray[11]="select count(*) from [PurchaseHeader] where [documenttype]='18' and [released]=1";
    $sqlarray[12]="select count(*) from SalesHeader where [documenttype]='1' and [released]=1";
    $sqlarray[13]="select count(*) from SalesHeader where [documenttype]='32' and [released]=1";
    $sqlarray[14]="select count(*) FROM [stockmaster] where stockmaster.itemcode in
        (select  stockmaster.itemcode  from [stockmaster] 
        join stockledger on stockmaster.itemcode=stockledger.itemcode
		GROUP BY [stockledger].[PartPerUnit],[stockmaster].reorderlevel,stockmaster.itemcode
		having ([stockmaster].reorderlevel) >  (sum([stockledger].[PartPerUnit] * stockledger.fulqty) + sum(stockledger.loosqty))
		)  ";
    
        $results =  DB_query($sqlarray[$Index], $db);
        if(DB_num_rows($results)>0){
             $myrow=DB_fetch_row($results);
            if($myrow[0]>0){
                if($Index==14){
                         
                $SQLSTMENT="select  
                    stockmaster.[descrip] ,
                    stockmaster.[eoq] ,
                    stockmaster.itemcode
                from [stockmaster] 
                join stockledger on stockmaster.itemcode=stockledger.itemcode
		GROUP BY  
                stockmaster.itemcode,
                [stockledger].[PartPerUnit],
                [stockmaster].reorderlevel,
                stockmaster.[descrip],
                stockmaster.[eoq]
		having ([stockmaster].reorderlevel) > (sum([stockledger].[PartPerUnit] * stockledger.fulqty) + sum(stockledger.loosqty)) ";
            
                $return='<div>'
                        . '<div>'
                        . '<ul class="nav nav-list">'
                        . '<li><label class="tree-toggler nav-header">Purchase Or Manufacture The Following</label>'
                        . '<ul class="nav nav-list tree">';

                 $results =  DB_query($SQLSTMENT,$db);
                 while($row= DB_fetch_array($results)){  
                     $return .='<li><label class="tree-toggler nav-header grouper">'.$row['descrip'].' :- Current Stock Balance :'.Getstockbalance($row['itemcode']).'</label></li>'; 
                  }

                 $return .='</ul></li></ul>'
                         . '</div></div>';
                        
  
                }else{
                    $return = (($myrow[0]>0)?_('There is some work here'):'');
                }
            }else{
                $return="No items, Good work !";
            }
        }else{
           $return="No items";
        }
        
  
  return $return;
}
 

>>>>>>> fcf1da09040591d58a8bc754034c8da36e93ca78
?>