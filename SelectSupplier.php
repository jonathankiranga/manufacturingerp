<<<<<<< HEAD

<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Supplier List');
include('includes/header.inc');
$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

if(isset($_GET['itemcode'])){
    $_SESSION['Supplieritemcode']=$_GET['itemcode'];
}

if(isset($_GET['newsearch'])){
    unset($_SESSION['Supplieritemcode']);
}

if(isset($_SESSION['Supplieritemcode'])){
    
        $ErrMsg = _('The Vendor name requested cannot be retrieved because');
	$result = DB_query("Select "
               . 'itemcode,'
               . 'customer,'
               . 'inactive,'
               . 'phone,'
               . 'email,'
               . 'supplierposting,IsEmployee '
               . "from creditors "
                . "where itemcode='".$_SESSION['Supplieritemcode']."'", $db, $ErrMsg);
	if ($myrow = DB_fetch_array($result)) {
		$SupplierName = htmlspecialchars($myrow['customer'], ENT_QUOTES, 'UTF-8', false);
		$PhoneNo = $myrow['phone'];
	} 
         
    echo '<p class="page_title_text">'
        . '<img src="' . $RootPath . '/css/' . $Theme . '/images/supplier.png" title="' . _('Supplier') . '" alt="" />' . ' ' . _('Supplier') . ' : <b>' . $_SESSION['Supplieritemcode'] . ' - ' . $SupplierName . '-' . $PhoneNo. '</b> ' . _('has been selected') . '.</p>';
	echo '<div class="page_help_text">' . _('Select a menu option to operate using this supplier.') . '</div>';
	echo '<br />
		<table width="90%" cellpadding="4">
		<tr>
                        <th style="width:33%">' . _('Supplier Inquiries') . '</th>
			<th style="width:33%">' . _('Supplier Transactions') . '</th>
			<th style="width:33%">' . _('Supplier Maintenance') . '</th>
		</tr>';
	echo '<tr><td valign="top" class="select">'; /* Inquiry Options */
	echo '<a href="' . $RootPath . '/PrintvendorStatements.php?SupplierID=' . $_SESSION['Supplieritemcode'] . '">' . _('Supplier Account Inquiry') . '</a>
	<br /><br />';
	echo '</td><td valign="top" class="select">'; /* Supplier Transactions */
	echo '<a href="' . $RootPath . '/EnterBills.php?SupplierID=' . $_SESSION['Supplieritemcode'] . '&new=1">' . _('Enter Invoice') . '</a><br />';
	echo '<a href="' . $RootPath . '/PaymentVoucher.php?SupplierID=' . $_SESSION['Supplieritemcode'] . '">' . _('Make payment ') . '</a><br />';
	echo '<br />';
	echo '</td><td valign="top" class="select">'; /* Supplier Maintenance */
	echo '<a href="' . $RootPath . '/Supplier.php">' . _('Add a New Supplier') . '</a>
		<br /><a href="' . $RootPath . '/Supplier.php?Modify=' . $_SESSION['Supplieritemcode'] . '">' . _('Modify Or Delete Supplier Details') . '</a>
		</td>
		</tr>
		</table>';
}

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/supplier.png" title="' . _('Vendor') .
	'" alt="" />' . ' ' . _('Vendor List') . '</p>';


echo '<form>
    <div class="card">
     <table style="width: 67%; margin: 0 auto 2em auto;" cellspacing="0" cellpadding="3" border="0">
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
            <tr id="filter_col2" data-column="1">
                <td>Column - Employee/Supplier</td>
               <td align="center"><input type="text" class="column_filter" id="col1_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col1_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col1_smart" checked="checked"></td>
            </tr>
            
         </tbody>
    </table>
    <table class="register display" style="width:100%">
    <thead>'
        . '<TR>'
        . '<th>Vendor Name</th>'
        . '<th></th>'
        . '<th>Telephone No</th>'
        . '<th>Email</th>'
        . '<th>Posting Group</th>'
        . '<th>Status</th>'
        . '</tr></thead><tbody>';
      
       $results=DB_query('Select '
               . 'itemcode,'
               . 'customer,'
               . 'inactive,'
               . 'phone,'
               . 'email,'
               . 'supplierposting,IsEmployee '
               . 'from creditors order by customer asc', $db);
       $k=0;
       while($rows=DB_fetch_array($results)){
           echo '<tr>';
           echo sprintf('<td><a href="%s?itemcode=%s">%s</a></td>',
                   $thispage,trim($rows['itemcode']),trim($rows['customer']));
           echo '<td>'.($rows['IsEmployee']==1?'Employee':'Supplier').'</td>';
           echo '<td>'.trim($rows['phone']).'</td>';
           echo '<td>'.trim($rows['email']).'</td>';
           echo '<td>'.$rows['supplierposting'].'</td>';
           echo '<td>'.($rows['inactive']==1?'Blocked':'Open').'</td>'
          . '</tr>';
            
       }        

echo '</tbody><tfoot>
            <tr><th>Vendor Name</th>'
        . '<th></th>'
        . '<th>Telephone No</th>'
        . '<th>Email</th>'
        . '<th>Posting Group</th>'
        . '<th>Status</th>
            </tr>
        </tfoot></table></div></form>';


include('includes/footer.inc');


?>
=======

<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Supplier List');
include('includes/header.inc');
$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');

if(isset($_GET['itemcode'])){
    $_SESSION['Supplieritemcode']=$_GET['itemcode'];
}

if(isset($_GET['newsearch'])){
    unset($_SESSION['Supplieritemcode']);
}

if(isset($_SESSION['Supplieritemcode'])){
    
        $ErrMsg = _('The Vendor name requested cannot be retrieved because');
	$result = DB_query("Select "
               . 'itemcode,'
               . 'customer,'
               . 'inactive,'
               . 'phone,'
               . 'email,'
               . 'supplierposting,IsEmployee '
               . "from creditors "
                . "where itemcode='".$_SESSION['Supplieritemcode']."'", $db, $ErrMsg);
	if ($myrow = DB_fetch_array($result)) {
		$SupplierName = htmlspecialchars($myrow['customer'], ENT_QUOTES, 'UTF-8', false);
		$PhoneNo = $myrow['phone'];
	} 
         
    echo '<p class="page_title_text">'
        . '<img src="' . $RootPath . '/css/' . $Theme . '/images/supplier.png" title="' . _('Supplier') . '" alt="" />' . ' ' . _('Supplier') . ' : <b>' . $_SESSION['Supplieritemcode'] . ' - ' . $SupplierName . '-' . $PhoneNo. '</b> ' . _('has been selected') . '.</p>';
	echo '<div class="page_help_text">' . _('Select a menu option to operate using this supplier.') . '</div>';
	echo '<br />
		<table width="90%" cellpadding="4">
		<tr>
                        <th style="width:33%">' . _('Supplier Inquiries') . '</th>
			<th style="width:33%">' . _('Supplier Transactions') . '</th>
			<th style="width:33%">' . _('Supplier Maintenance') . '</th>
		</tr>';
	echo '<tr><td valign="top" class="select">'; /* Inquiry Options */
	echo '<a href="' . $RootPath . '/PrintvendorStatements.php?SupplierID=' . $_SESSION['Supplieritemcode'] . '">' . _('Supplier Account Inquiry') . '</a>
	<br /><br />';
	echo '</td><td valign="top" class="select">'; /* Supplier Transactions */
	echo '<a href="' . $RootPath . '/EnterBills.php?SupplierID=' . $_SESSION['Supplieritemcode'] . '&new=1">' . _('Enter Invoice') . '</a><br />';
	echo '<a href="' . $RootPath . '/PaymentVoucher.php?SupplierID=' . $_SESSION['Supplieritemcode'] . '">' . _('Make payment ') . '</a><br />';
	echo '<br />';
	echo '</td><td valign="top" class="select">'; /* Supplier Maintenance */
	echo '<a href="' . $RootPath . '/Supplier.php">' . _('Add a New Supplier') . '</a>
		<br /><a href="' . $RootPath . '/Supplier.php?Modify=' . $_SESSION['Supplieritemcode'] . '">' . _('Modify Or Delete Supplier Details') . '</a>
		</td>
		</tr>
		</table>';
}

echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/supplier.png" title="' . _('Vendor') .
	'" alt="" />' . ' ' . _('Vendor List') . '</p>';


echo '<form>
    <div class="card">
     <table style="width: 67%; margin: 0 auto 2em auto;" cellspacing="0" cellpadding="3" border="0">
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
            <tr id="filter_col2" data-column="1">
                <td>Column - Employee/Supplier</td>
               <td align="center"><input type="text" class="column_filter" id="col1_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col1_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col1_smart" checked="checked"></td>
            </tr>
            
         </tbody>
    </table>
    <table class="register display" style="width:100%">
    <thead>'
        . '<TR>'
        . '<th>Vendor Name</th>'
        . '<th></th>'
        . '<th>Telephone No</th>'
        . '<th>Email</th>'
        . '<th>Posting Group</th>'
        . '<th>Status</th>'
        . '</tr></thead><tbody>';
      
       $results=DB_query('Select '
               . 'itemcode,'
               . 'customer,'
               . 'inactive,'
               . 'phone,'
               . 'email,'
               . 'supplierposting,IsEmployee '
               . 'from creditors order by customer asc', $db);
       $k=0;
       while($rows=DB_fetch_array($results)){
           echo '<tr>';
           echo sprintf('<td><a href="%s?itemcode=%s">%s</a></td>',
                   $thispage,trim($rows['itemcode']),trim($rows['customer']));
           echo '<td>'.($rows['IsEmployee']==1?'Employee':'Supplier').'</td>';
           echo '<td>'.trim($rows['phone']).'</td>';
           echo '<td>'.trim($rows['email']).'</td>';
           echo '<td>'.$rows['supplierposting'].'</td>';
           echo '<td>'.($rows['inactive']==1?'Blocked':'Open').'</td>'
          . '</tr>';
            
       }        

echo '</tbody><tfoot>
            <tr><th>Vendor Name</th>'
        . '<th></th>'
        . '<th>Telephone No</th>'
        . '<th>Email</th>'
        . '<th>Posting Group</th>'
        . '<th>Status</th>
            </tr>
        </tfoot></table></div></form>';


include('includes/footer.inc');


?>
>>>>>>> fcf1da09040591d58a8bc754034c8da36e93ca78
