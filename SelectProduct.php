<<<<<<< HEAD
<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Inventory List');
include('includes/header.inc');

$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');


if(isset($_GET['StockID'])){
    $_SESSION['StockID']=$_GET['StockID'];
}

if(isset($_GET['newsearch'])){
    unset($_SESSION['StockID']);
}

if(isset($_SESSION['StockID'])){
    $StockID = $_SESSION['StockID'];
}

if(isset($_SESSION['StockID'])){
    
        $ErrMsg = _('The Item name requested cannot be retrieved because');
	$result = DB_query("Select [isstock] ,[barcode] ,[itemcode] ,[descrip] ,[postinggroup] ,[averagestock]
                    ,[partperunit] ,[reorderlevel],[eoq] ,[category] ,[units] ,[inactive] ,[nextserialno] ,sellingprice
                FROM [stockmaster] where [itemcode]='".$_SESSION['StockID']."'", $db, $ErrMsg);
   
        if($myrow = DB_fetch_array($result)) {
		$StockName = htmlspecialchars($myrow['descrip'],ENT_QUOTES,'UTF-8',false);
		} 
    
    echo '<div class="page_help_text">' . _('Select a menu option to operate using this Inventory ').$StockName . '.</div><br />';
    echo '<table cellpadding="4" width="100%" class="table table-bordered">
                <tr>
                        <th style="width:33%">' . _('Inventory Inquiries') . '</th>
                        <th style="width:33%">' . _('Inventory Transactions') . '</th>
                        <th style="width:33%">' . _('Inventory Maintenance') . '</th>
                </tr>';
echo '<tr><td valign="top" class="select">';
/*Stock Inquiry Options */
        echo '<a href="' . $RootPath . '/StockMovements.php?StockID=' . $StockID . '">' . _('Show Stock Movements') . '</a><br />';
        echo '<a href="' . $RootPath . '/PurchaseOrderbystock.php?StockID=' . $StockID . '">' . _('Search Purchase Orders') . '</a><br />';
        echo '<a href="' . $RootPath . '/SalesOrderbystock.php?StockID=' . $StockID . '">' . _('Search Sals Orders') . '</a><br />';
     	
echo '</td><td valign="top" class="select">';
/* Stock Transactions */
	echo '<a href="' . $RootPath . '/StockAdjustments.php?StockID=' . $StockID . '">' . _('Quantity Adjustments') . '</a><br />';
   	echo '<a href="' . $RootPath . '/StockContainer.php?StockID=' . $StockID . '">' . _('Link Container') . '</a><br />';
        
 /* end of ($Its_A_Kitset_Assembly_Or_Dummy == False) */
echo '</td><td valign="top" class="select">';
/* Stock Maintenance Options */
        echo '<a href="' . $RootPath . '/Stocks.php">' . _('Insert New Item') . '</a><br />';
        echo '<a href="' . $RootPath . '/Stocks.php?StockID=' . $StockID . '">' . _('Modify Item Details') . '</a><br />';
	echo '</td>';
	echo '</tr></table><br />';

    
} else {
    

echo '<form><div class="card">
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
                <td>Column - Stock Code</td>
                <td align="center"><input type="text" class="column_filter" id="col0_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_smart" checked="checked"></td>
      
            </tr>
            <tr id="filter_col2" data-column="1">
                <td>Column - Inventory Name</td>
               <td align="center"><input type="text" class="column_filter" id="col1_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col1_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col1_smart" checked="checked"></td>
            </tr>
            
         </tbody>
    </table> 
    <table class="register display" style="width:100%">
    <thead>'
        . '<TR>'
        . '<th>Stock Code</th>'
        . '<th>Inventory Name</th>'
        . '<th>Sales Category</th>'
        . '<th>Production Category</th>'
        . '<th>Is Obsolete</th>'
        . '<th>Ave Cost</th>'
        . '<th>Posting Group</th>'
        . '</tr></thead><tbody>';
     
$results=DB_query('select 
    stockmaster.isstock,
    stockmaster.itemcode,
    stockmaster.descrip,
    stockmaster.inactive,
    stockmaster.averagestock,
    stockmaster.[postinggroup],
    stockmaster.production,
    unitfull.descrip as Fulqty,
     stockcategory.[categorydescription] as Groups
        from stockmaster 
        left join unit unitfull on stockmaster.units=unitfull.code 
        left join stockcategory on stockcategory.[categoryid]=stockmaster.[category]
        order by inactive,descrip asc', $db);
       
       $k=0;
       while($rows=DB_fetch_array($results)){
           echo  '<tr>';
           echo sprintf('<td><a href="%s?StockID=%s">%s</a></td>', $thispage, trim($rows['itemcode']),trim($rows['itemcode']));
           echo '<td><b>'.$rows['descrip'].'</b><i>'.$rows['Fulqty'].'</i></td>';
           echo '<td><b>'.$rows['Groups'].'</b></td>';
           echo '<td><b>'.$ProductionCategory[$rows['production']].'</b></td>';
        
           echo '<td>'. ($rows['inactive']==true?'YES':"NO").'</td>';
           echo '<td>'. number_format($rows['averagestock'],2).'</td>';
           echo '<td>'. trim($rows['postinggroup']).'</td></tr>';
            
       }        

echo '</tbody><tfoot>
            <tr><th>Stock Code</th>'
        . '<th>Inventory Name</th>'
        . '<th>Group</th>'
        . '<th>Is Obsolete</th>'
        . '<th>Ave Cost</th>'
        . '<th>Posting Group</th>
            </tr>
        </tfoot></table></div></form>';
}

include('includes/footer.inc');


?>
=======
<?php
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/CountriesArray.php');
$Title = _('Inventory List');
include('includes/header.inc');

$thispage = htmlspecialchars($_SERVER['PHP_SELF'], ENT_QUOTES, 'UTF-8');


if(isset($_GET['StockID'])){
    $_SESSION['StockID']=$_GET['StockID'];
}

if(isset($_GET['newsearch'])){
    unset($_SESSION['StockID']);
}

if(isset($_SESSION['StockID'])){
    $StockID = $_SESSION['StockID'];
}

if(isset($_SESSION['StockID'])){
    
        $ErrMsg = _('The Item name requested cannot be retrieved because');
	$result = DB_query("Select [isstock] ,[barcode] ,[itemcode] ,[descrip] ,[postinggroup] ,[averagestock]
                    ,[partperunit] ,[reorderlevel],[eoq] ,[category] ,[units] ,[inactive] ,[nextserialno] ,sellingprice
                FROM [stockmaster] where [itemcode]='".$_SESSION['StockID']."'", $db, $ErrMsg);
   
        if($myrow = DB_fetch_array($result)) {
		$StockName = htmlspecialchars($myrow['descrip'],ENT_QUOTES,'UTF-8',false);
		} 
    
    echo '<div class="page_help_text">' . _('Select a menu option to operate using this Inventory ').$StockName . '.</div><br />';
    echo '<table cellpadding="4" width="100%" class="table table-bordered">
                <tr>
                        <th style="width:33%">' . _('Inventory Inquiries') . '</th>
                        <th style="width:33%">' . _('Inventory Transactions') . '</th>
                        <th style="width:33%">' . _('Inventory Maintenance') . '</th>
                </tr>';
echo '<tr><td valign="top" class="select">';
/*Stock Inquiry Options */
        echo '<a href="' . $RootPath . '/StockMovements.php?StockID=' . $StockID . '">' . _('Show Stock Movements') . '</a><br />';
        echo '<a href="' . $RootPath . '/PurchaseOrderbystock.php?StockID=' . $StockID . '">' . _('Search Purchase Orders') . '</a><br />';
        echo '<a href="' . $RootPath . '/SalesOrderbystock.php?StockID=' . $StockID . '">' . _('Search Sals Orders') . '</a><br />';
     	
echo '</td><td valign="top" class="select">';
/* Stock Transactions */
	echo '<a href="' . $RootPath . '/StockAdjustments.php?StockID=' . $StockID . '">' . _('Quantity Adjustments') . '</a><br />';
   	echo '<a href="' . $RootPath . '/StockContainer.php?StockID=' . $StockID . '">' . _('Link Container') . '</a><br />';
        
 /* end of ($Its_A_Kitset_Assembly_Or_Dummy == False) */
echo '</td><td valign="top" class="select">';
/* Stock Maintenance Options */
        echo '<a href="' . $RootPath . '/Stocks.php">' . _('Insert New Item') . '</a><br />';
        echo '<a href="' . $RootPath . '/Stocks.php?StockID=' . $StockID . '">' . _('Modify Item Details') . '</a><br />';
	echo '</td>';
	echo '</tr></table><br />';

    
} else {
    

echo '<form><div class="card">
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
                <td>Column - Stock Code</td>
                <td align="center"><input type="text" class="column_filter" id="col0_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col0_smart" checked="checked"></td>
      
            </tr>
            <tr id="filter_col2" data-column="1">
                <td>Column - Inventory Name</td>
               <td align="center"><input type="text" class="column_filter" id="col1_filter"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col1_regex"></td>
                <td align="center"><input type="checkbox" class="column_filter" id="col1_smart" checked="checked"></td>
            </tr>
            
         </tbody>
    </table> 
    <table class="register display" style="width:100%">
    <thead>'
        . '<TR>'
        . '<th>Stock Code</th>'
        . '<th>Inventory Name</th>'
        . '<th>Sales Category</th>'
        . '<th>Production Category</th>'
        . '<th>Is Obsolete</th>'
        . '<th>Ave Cost</th>'
        . '<th>Posting Group</th>'
        . '</tr></thead><tbody>';
     
$results=DB_query('select 
    stockmaster.isstock,
    stockmaster.itemcode,
    stockmaster.descrip,
    stockmaster.inactive,
    stockmaster.averagestock,
    stockmaster.[postinggroup],
    stockmaster.production,
    unitfull.descrip as Fulqty,
     stockcategory.[categorydescription] as Groups
        from stockmaster 
        left join unit unitfull on stockmaster.units=unitfull.code 
        left join stockcategory on stockcategory.[categoryid]=stockmaster.[category]
        order by inactive,descrip asc', $db);
       
       $k=0;
       while($rows=DB_fetch_array($results)){
           echo  '<tr>';
           echo sprintf('<td><a href="%s?StockID=%s">%s</a></td>', $thispage, trim($rows['itemcode']),trim($rows['itemcode']));
           echo '<td><b>'.$rows['descrip'].'</b><i>'.$rows['Fulqty'].'</i></td>';
           echo '<td><b>'.$rows['Groups'].'</b></td>';
           echo '<td><b>'.$ProductionCategory[$rows['production']].'</b></td>';
        
           echo '<td>'. ($rows['inactive']==true?'YES':"NO").'</td>';
           echo '<td>'. number_format($rows['averagestock'],2).'</td>';
           echo '<td>'. trim($rows['postinggroup']).'</td></tr>';
            
       }        

echo '</tbody><tfoot>
            <tr><th>Stock Code</th>'
        . '<th>Inventory Name</th>'
        . '<th>Group</th>'
        . '<th>Is Obsolete</th>'
        . '<th>Ave Cost</th>'
        . '<th>Posting Group</th>
            </tr>
        </tfoot></table></div></form>';
}

include('includes/footer.inc');


?>
>>>>>>> fcf1da09040591d58a8bc754034c8da36e93ca78
