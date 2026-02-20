<<<<<<< HEAD
<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Customer List');
include('includes/header.inc');
$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

$SalesmanArray=array();

$result=DB_query("select code,salesman from salesrepsinfo",$db);    
while ($myrow = DB_fetch_array($result)) {
    $code=trim($myrow['code']);
    $SalesmanArray[$code]=$myrow['salesman'];
} //end while loop
                
if(isset($_GET['itemcode'])){
    $_SESSION['Customeritemcode']=$_GET['itemcode'];
}

if(isset($_GET['newsearch'])){
    unset($_SESSION['Customeritemcode']);
}

if(isset($_SESSION['Customeritemcode'])){
    
        $ErrMsg = _('The customer name requested cannot be retrieved because');
	$result = DB_query("Select "
               . 'itemcode,'
               . 'customer,'
               . 'creditlimit,'
               . 'phone,'
               . 'email,'
               . 'salesman,'
               . 'inactive,'
               . 'customerposting '
               . "from debtors where itemcode='".$_SESSION['Customeritemcode']."'", $db, $ErrMsg);
	if ($myrow = DB_fetch_array($result)) {
		$CustomerName = htmlspecialchars($myrow['customer'], ENT_QUOTES, 'UTF-8', false);
		$PhoneNo = $myrow['phone'];
	} 
    
    echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/customer.png" title="' . _('Customer') . '" alt="" />' . ' ' . _('You have selected Customer') . ' : ' . $_SESSION['Customeritemcode'] . ' - ' . $CustomerName . ' - ' . $PhoneNo . '</p>';
    echo '<div class="page_help_text">' . _('Select a menu option to operate using this customer') . '.</div><br />';

	echo '<table cellpadding="4" width="90%" class="table table-bordered">
                <tr>
                        <th style="width:33%">' . _('Customer Inquiries') . '</th>
                        <th style="width:33%">' . _('Customer Transactions') . '</th>
                        <th style="width:33%">' . _('Customer Maintenance') . '</th>
                </tr>';
	echo '<tr><td valign="top" class="select">';
	/* Customer Inquiry Options */
	echo '<a href="' . $RootPath . '/PrintCustStatements.php?FromCust=' . $_SESSION['Customeritemcode'] . '&amp;ToCust=' . $_SESSION['Customeritemcode'] . '&amp;PrintPDF=Yes">' . _('Print Customer Statement') . '</a><br />';
	
	echo '</td><td valign="top" class="select">';
	echo '<a href="' . $RootPath . '/receipts.php?SelectedCustomer=' . $_SESSION['Customeritemcode'] . '">' . _('Record Receipts') . '</a><br />';
	echo '<a href="' . $RootPath . '/ReceitsAllocation.php?CustomerID=' . $_SESSION['Customeritemcode'] . '">' . _('Allocate Receipts OR Credit Notes') . '</a><br />';
	
	echo '</td><td valign="top" class="select">';
	echo '<a href="' . $RootPath . '/Customer.php">' . _('Add a New Customer') . '</a><br />';
	echo '<a href="' . $RootPath . '/Customer.php?Modify=' . $_SESSION['Customeritemcode'] . '">' . _('Modify Customer Details') . '</a><br />';
	
	echo '</td>';
	echo '</tr></table><br />';

    
}

echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" /><div class="card">';
echo '<table style="width: 67%; margin: 0 auto 2em auto;" cellspacing="0" cellpadding="3" border="0">
        <thead>
            <tr>
                <th>Target</th>
                 <th>Search text</th>
                <th>Treat as regex</th>
                <th>Use smart search</th>
            </tr>
        </thead>
        <tbody>
            <tr id="filter_global">
                <td>Global search</td>
                <td align="center"><input type="text" class="global_filter" id="global_filter"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_regex"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_smart" checked="checked"></td>
    
            </tr>
            <tr id="filter_col1" data-column="0">
                <td>Column - Vendor Name</td>
                <td align="center"><input type="text" class="column_filter" id="col0_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_smart" checked="checked"></td>
      
            </tr>
           
            
         </tbody>
    </table><input type="button" onclick="tableToExcel(\'mycustomersTable\',\'Debtors Balance by sales rep\')" value="Export to Excel">
    
    <table id="mycustomersTable" class="register display" style="width:100%">
    <thead>'
        . '<TR>'
        . '<th>Customer Name</th>'
        . '<th>Credit Limit</th>'
        . '<th>Total Sales(12 months)</th>'
        . '<th>Telephone No</th>'
        . '<th>Email</th>'
        . '<th>Sales Rep Code</th>'
        . '<th>Unpaid Balance</th>'
        . '</tr></thead><tbody>';
      
if(mb_strlen($_POST['salespersoncode'])>0){
     $results=DB_query("Select 
                        itemcode,
                        customer,
                        creditlimit,
                        SUM([CustomerStatement].[Grossamount]) as Totsales,
                        phone,
                        email,
                        salesman,
                        customerposting 
                        from debtors 
                        left join [CustomerStatement] on itemcode=[Accountno] and (Documenttype=10 or Documenttype=13) and ([CustomerStatement].[Date] between DATEADD(MONTH,-12,getdate()) and getdate() )
                        where salesman='".trim($_POST['salespersoncode'])."'
                        group by 
                        itemcode,
                        customer,
                        creditlimit,
                        phone,
                        email,
                        salesman,
                        customerposting order by Totsales DESC", $db);
}else{
       $results=DB_query('Select 
                        itemcode,
                        customer,
                        creditlimit,
                        SUM([CustomerStatement].[Grossamount]) as Totsales,
                        phone,
                        email,
                        salesman,
                        customerposting 
                        from debtors 
                        left join [CustomerStatement] on itemcode=[Accountno] and (Documenttype=10 or Documenttype=13) and ([CustomerStatement].[Date] between DATEADD(MONTH,-12,getdate()) and getdate() )
                        group by 
                        itemcode,
                        customer,
                        creditlimit,
                        phone,
                        email,
                        salesman,
                        customerposting order by Totsales DESC', $db);
}
       $k=0;
       while($rows=DB_fetch_array($results)){
           echo '<tr>';
           $code=trim($rows['salesman']);
           
           echo sprintf('<td><a href="%s?itemcode=%s">%s</a></td>',$thispage, 
                   trim($rows['itemcode']),trim($rows['customer']));
           echo '<td>'.number_format($rows['creditlimit'],0).'</td>';
           echo '<td>'.number_format($rows['Totsales'],0).'</td>';
           echo '<td>'.trim($rows['phone']).'</td>';
           echo '<td>'.trim($rows['email']).'</td>';
           echo '<td>'.$SalesmanArray[$code].'</td>';
           echo '<td class="number">'.GetUnpaid(trim($rows['itemcode'])).'</td>'
          . '</tr>';
            
       }        

echo '</tbody><tfoot>
            <th>Customer Name</th>'
        . '<th>Credit Limit</th>'
        . '<th>Total Sales(12 months)</th>'
        . '<th>Telephone No</th>'
        . '<th>Email</th>'
        . '<th>Sales Rep Code</th>'
        . '<th>Unpaid Balance</th>
            </tr>
        </tfoot></table></div></form>';


include('includes/footer.inc');


Function GetUnpaid($itemcode){
    global $db;
     $results=DB_query("Select SUM([Grossamount]) from CustomerStatement where [Accountno]='".$itemcode."'", $db);
     $rows=DB_fetch_row($results);
     return  (int) $rows[0];
}

?>
=======
<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Customer List');
include('includes/header.inc');
$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

$SalesmanArray=array();

$result=DB_query("select code,salesman from salesrepsinfo",$db);    
while ($myrow = DB_fetch_array($result)) {
    $code=trim($myrow['code']);
    $SalesmanArray[$code]=$myrow['salesman'];
} //end while loop
                
if(isset($_GET['itemcode'])){
    $_SESSION['Customeritemcode']=$_GET['itemcode'];
}

if(isset($_GET['newsearch'])){
    unset($_SESSION['Customeritemcode']);
}

if(isset($_SESSION['Customeritemcode'])){
    
        $ErrMsg = _('The customer name requested cannot be retrieved because');
	$result = DB_query("Select "
               . 'itemcode,'
               . 'customer,'
               . 'creditlimit,'
               . 'phone,'
               . 'email,'
               . 'salesman,'
               . 'inactive,'
               . 'customerposting '
               . "from debtors where itemcode='".$_SESSION['Customeritemcode']."'", $db, $ErrMsg);
	if ($myrow = DB_fetch_array($result)) {
		$CustomerName = htmlspecialchars($myrow['customer'], ENT_QUOTES, 'UTF-8', false);
		$PhoneNo = $myrow['phone'];
	} 
    
    echo '<p class="page_title_text"><img src="' . $RootPath . '/css/' . $Theme . '/images/customer.png" title="' . _('Customer') . '" alt="" />' . ' ' . _('You have selected Customer') . ' : ' . $_SESSION['Customeritemcode'] . ' - ' . $CustomerName . ' - ' . $PhoneNo . '</p>';
    echo '<div class="page_help_text">' . _('Select a menu option to operate using this customer') . '.</div><br />';

	echo '<table cellpadding="4" width="90%" class="table table-bordered">
                <tr>
                        <th style="width:33%">' . _('Customer Inquiries') . '</th>
                        <th style="width:33%">' . _('Customer Transactions') . '</th>
                        <th style="width:33%">' . _('Customer Maintenance') . '</th>
                </tr>';
	echo '<tr><td valign="top" class="select">';
	/* Customer Inquiry Options */
	echo '<a href="' . $RootPath . '/PrintCustStatements.php?FromCust=' . $_SESSION['Customeritemcode'] . '&amp;ToCust=' . $_SESSION['Customeritemcode'] . '&amp;PrintPDF=Yes">' . _('Print Customer Statement') . '</a><br />';
	
	echo '</td><td valign="top" class="select">';
	echo '<a href="' . $RootPath . '/receipts.php?SelectedCustomer=' . $_SESSION['Customeritemcode'] . '">' . _('Record Receipts') . '</a><br />';
	echo '<a href="' . $RootPath . '/ReceitsAllocation.php?CustomerID=' . $_SESSION['Customeritemcode'] . '">' . _('Allocate Receipts OR Credit Notes') . '</a><br />';
	
	echo '</td><td valign="top" class="select">';
	echo '<a href="' . $RootPath . '/Customer.php">' . _('Add a New Customer') . '</a><br />';
	echo '<a href="' . $RootPath . '/Customer.php?Modify=' . $_SESSION['Customeritemcode'] . '">' . _('Modify Customer Details') . '</a><br />';
	
	echo '</td>';
	echo '</tr></table><br />';

    
}

echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post" id="salesform">';
echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'" /><div class="card">';
echo '<table style="width: 67%; margin: 0 auto 2em auto;" cellspacing="0" cellpadding="3" border="0">
        <thead>
            <tr>
                <th>Target</th>
                 <th>Search text</th>
                <th>Treat as regex</th>
                <th>Use smart search</th>
            </tr>
        </thead>
        <tbody>
            <tr id="filter_global">
                <td>Global search</td>
                <td align="center"><input type="text" class="global_filter" id="global_filter"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_regex"></td>
                <td align="center"><input type="checkbox" class="global_filter" id="global_smart" checked="checked"></td>
    
            </tr>
            <tr id="filter_col1" data-column="0">
                <td>Column - Vendor Name</td>
                <td align="center"><input type="text" class="column_filter" id="col0_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_smart" checked="checked"></td>
      
            </tr>
           
            
         </tbody>
    </table><input type="button" onclick="tableToExcel(\'mycustomersTable\',\'Debtors Balance by sales rep\')" value="Export to Excel">
    
    <table id="mycustomersTable" class="register display" style="width:100%">
    <thead>'
        . '<TR>'
        . '<th>Customer Name</th>'
        . '<th>Credit Limit</th>'
        . '<th>Total Sales(12 months)</th>'
        . '<th>Telephone No</th>'
        . '<th>Email</th>'
        . '<th>Sales Rep Code</th>'
        . '<th>Unpaid Balance</th>'
        . '</tr></thead><tbody>';
      
if(mb_strlen($_POST['salespersoncode'])>0){
     $results=DB_query("Select 
                        itemcode,
                        customer,
                        creditlimit,
                        SUM([CustomerStatement].[Grossamount]) as Totsales,
                        phone,
                        email,
                        salesman,
                        customerposting 
                        from debtors 
                        left join [CustomerStatement] on itemcode=[Accountno] and (Documenttype=10 or Documenttype=13) and ([CustomerStatement].[Date] between DATEADD(MONTH,-12,getdate()) and getdate() )
                        where salesman='".trim($_POST['salespersoncode'])."'
                        group by 
                        itemcode,
                        customer,
                        creditlimit,
                        phone,
                        email,
                        salesman,
                        customerposting order by Totsales DESC", $db);
}else{
       $results=DB_query('Select 
                        itemcode,
                        customer,
                        creditlimit,
                        SUM([CustomerStatement].[Grossamount]) as Totsales,
                        phone,
                        email,
                        salesman,
                        customerposting 
                        from debtors 
                        left join [CustomerStatement] on itemcode=[Accountno] and (Documenttype=10 or Documenttype=13) and ([CustomerStatement].[Date] between DATEADD(MONTH,-12,getdate()) and getdate() )
                        group by 
                        itemcode,
                        customer,
                        creditlimit,
                        phone,
                        email,
                        salesman,
                        customerposting order by Totsales DESC', $db);
}
       $k=0;
       while($rows=DB_fetch_array($results)){
           echo '<tr>';
           $code=trim($rows['salesman']);
           
           echo sprintf('<td><a href="%s?itemcode=%s">%s</a></td>',$thispage, 
                   trim($rows['itemcode']),trim($rows['customer']));
           echo '<td>'.number_format($rows['creditlimit'],0).'</td>';
           echo '<td>'.number_format($rows['Totsales'],0).'</td>';
           echo '<td>'.trim($rows['phone']).'</td>';
           echo '<td>'.trim($rows['email']).'</td>';
           echo '<td>'.$SalesmanArray[$code].'</td>';
           echo '<td class="number">'.GetUnpaid(trim($rows['itemcode'])).'</td>'
          . '</tr>';
            
       }        

echo '</tbody><tfoot>
            <th>Customer Name</th>'
        . '<th>Credit Limit</th>'
        . '<th>Total Sales(12 months)</th>'
        . '<th>Telephone No</th>'
        . '<th>Email</th>'
        . '<th>Sales Rep Code</th>'
        . '<th>Unpaid Balance</th>
            </tr>
        </tfoot></table></div></form>';


include('includes/footer.inc');


Function GetUnpaid($itemcode){
    global $db;
     $results=DB_query("Select SUM([Grossamount]) from CustomerStatement where [Accountno]='".$itemcode."'", $db);
     $rows=DB_fetch_row($results);
     return  (int) $rows[0];
}

?>
>>>>>>> fcf1da09040591d58a8bc754034c8da36e93ca78
