<?php
/* $Id: FixedAssetDepreciation.php 4213 2010-12-22 14:33:20Z tim_schofield $*/
include('includes/session.inc');
$Title = _('Depreciation Journal Entry');

$ViewTopic = 'FixedAssets';
$BookMark = 'AssetDepreciation';
include('includes/header.inc');
include('includes/SQL_CommonFunctions.inc');
include('includes/AccountBalance.inc');

/*Get the last period depreciation (depn is transtype =44) was posted for */
$result = DB_query("SELECT [last_depreciation] FROM companies where [coycode]=1",$db);
$LastDepnRun = DB_fetch_row($result);
$AllowUserEnteredProcessDate = true;

if (!is_date(ConvertSQLDate($LastDepnRun[0]))) { //then depn has never been run yet?
        $_POST['ProcessDate'] = Date($_SESSION['DefaultDateFormat'],mktime(0,0,0,date('m'),0,date('Y')));
} else {
        $_POST['ProcessDate'] = ConvertSQLDate($LastDepnRun[0]);    
//depn calc has been run previously
	$AllowUserEnteredProcessDate = false;
}

/* Get list of assets for journal */

DB_FixedAssets();


   
$AssetsResult=DB_query('[dbo].[DB_FixedAssets]', $db);
$InputError = false; //always hope for the best
if (Date1GreaterThanDate2($_POST['ProcessDate'],Date($_SESSION['DefaultDateFormat']))){
	prnMsg(_('No depreciation will be committed as the processing date is beyond the current date. The depreciation run can only be run for periods prior to today'),'warn');
	$InputError =true;
}
if (isset($_POST['CommitDepreciation']) AND $InputError==false){
	$result = DB_Txn_Begin($db);
	$TransNo = GetNextTransNo(44, $db);
	$PeriodNo = GetPeriod($_POST['ProcessDate'],$db,TRUE);
        $SQLArray=array();
}

echo '<DIV class="container" id="GL"><table class="table-striped table-bordered">';
$Heading = '<tr>
            <th>' . _('Asset ID') . '</th>
            <th>' . _('Description') . '</th>
            <th>' . _('Date Purchased') . '</th>
            <th>' . _('Cost') . '</th>
            <th>' . _('Accum Depn') . '</th>
            <th>' . _('B/fwd Book Value') . '</th>
            <th>' .  _('Depn Type') . '</th>
            <th>' .  _('Depn Rate') . '</th>
            <th>' . _('New Depn') . '</th>
        </tr>';
echo $Heading;

$AssetCategoryDescription ='0';

$TotalCost =0;
$TotalAccumDepn=0;
$TotalDepn = 0;
$TotalCategoryCost = 0;
$TotalCategoryAccumDepn =0;
$TotalCategoryDepn = 0;

$RowCounter = 0;
$k=0;

while ($AssetRow=DB_fetch_array($AssetsResult)) {
    
	if ($AssetCategoryDescription != $AssetRow['categorydescription'] OR $AssetCategoryDescription =='0' ){
		if ($AssetCategoryDescription !='0'){ //then print totals
			echo '<tr><th colspan="3" align="right">' . _('Total for') . ' ' . $AssetCategoryDescription . ' </th>
					<th class="number">' . locale_number_format($TotalCategoryCost,$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
					<th class="number">' . locale_number_format($TotalCategoryAccumDepn,$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
					<th class="number">' . locale_number_format(($TotalCategoryCost-$TotalCategoryAccumDepn),$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
					<th colspan="2"></th>
					<th class="number">' . locale_number_format($TotalCategoryDepn,$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
					</tr>';
			$RowCounter = 0;
		}
		echo '<tr><th colspan="9" align="left">' . $AssetRow['categorydescription']  . '</th></tr>';
		$AssetCategoryDescription = $AssetRow['categorydescription'];
		$TotalCategoryCost = 0;
		$TotalCategoryAccumDepn =0;
		$TotalCategoryDepn = 0;
	}
        
	$BookValueBfwd = $AssetRow['costtotal'] - $AssetRow['depnbfwd'];
	if ($AssetRow['depntype']==0){ //striaght line depreciation
		$DepreciationType = _('SL');
		$NewDepreciation = $AssetRow['costtotal'] * $AssetRow['depnrate']/100/12;
		if ($NewDepreciation > $BookValueBfwd){
			$NewDepreciation = $BookValueBfwd;
		}
	} else { //Diminishing value depreciation
		$DepreciationType = _('DV');
		$NewDepreciation = $BookValueBfwd * $AssetRow['depnrate']/100/12;
	}
        
	if (Date1GreaterThanDate2(ConvertSQLDate($AssetRow['transdate']),$_POST['ProcessDate'])){
		/*Over-ride calculations as the asset was not purchased at the date of the calculation!! */
		$NewDepreciation =0;
	}
        
	$RowCounter++;
	if ($RowCounter ==15){
		echo $Heading;
		$RowCounter =0;
	}
	
	echo '<tr><td>' . $AssetRow['assetid'] . '</td>
		<td>' . $AssetRow['description'] . '</td>
		<td>' . ConvertSQLDate($AssetRow['transdate']) . '</td>
		<td class="number">' . locale_number_format($AssetRow['costtotal'],$_SESSION['CompanyRecord']['decimalplaces']) . '</td>
		<td class="number">' . locale_number_format($AssetRow['depnbfwd'],$_SESSION['CompanyRecord']['decimalplaces']) . '</td>
		<td class="number">' . locale_number_format($AssetRow['costtotal']-$AssetRow['depnbfwd'],$_SESSION['CompanyRecord']['decimalplaces']) . '</td>
		<td align="center">' . $DepreciationType . '</td>
		<td class="number">' . $AssetRow['depnrate']  . '</td>
		<td class="number">' . locale_number_format($NewDepreciation ,$_SESSION['CompanyRecord']['decimalplaces']) . '</td>
	</tr>';
        
	$TotalCategoryCost +=$AssetRow['costtotal'];
	$TotalCategoryAccumDepn +=$AssetRow['depnbfwd'];
	$TotalCategoryDepn +=$NewDepreciation;
	$TotalCost +=$AssetRow['costtotal'];
	$TotalAccumDepn +=$AssetRow['depnbfwd'];
	$TotalDepn +=$NewDepreciation;

	if(isset($_POST['CommitDepreciation']) AND $NewDepreciation !=0 AND $InputError==false){
            
        $SQL = sprintf("select [depnact],[accumdepnact] from [fixedassetcategories] where [categoryid]='%s'",$AssetRow['assetcategoryid']);
        $ResultIndex = DB_query($SQL,$db);
        $Row=DB_fetch_row($ResultIndex);

        $SQLArray[]= Sprintf("INSERT INTO [Generalledger] ([journalno],[Docdate],[period],[DocumentNo],[DocumentType],[accountcode],[balaccountcode],[amount],[currencycode],[ExchangeRate],[narration]) VALUES ('%s','%s','%s','%s','%s','%s','%s',%f,'%s',%f,'%s')",
            $TransNo , FormatDateForSQL($_POST['ProcessDate']) ,$PeriodNo ,$TransNo ,44 , $Row[0], $Row[1] , $NewDepreciation , $_SESSION['CompanyRecord']['currencydefault'] ,1 , _('Monthly depreciation for ') . ' ' . $AssetRow['categorydescription']  );
          //insert the fixedassettrans record
        $SQLArray[] = "INSERT INTO fixedassettrans (assetid, transtype, transno, transdate,periodno, inputdate, fixedassettranstype, amount)
                  VALUES ('" . $AssetRow['assetid'] . "', '44', '" . $TransNo . "',  '" . FormatDateForSQL($_POST['ProcessDate']) . "', '" . $PeriodNo . "', '" . FormatDateForSQL($_POST['ProcessDate']) . "','depn', '" . $NewDepreciation . "')";
         /*now update the accum depn in fixedassets */
        $SQLArray[] = "UPDATE fixedassets SET  accumdepn = accumdepn + " . $NewDepreciation  . " WHERE assetid = '" . $AssetRow['assetid'] . "'";
          
    } //end if Committing the depreciation to DB
        
        
} //end loop around the assets to calculate depreciation for
echo '<tr>
		<th colspan="3" align="right">' . _('Total for') . ' ' . $AssetCategoryDescription . ' </th>
		<th class="number">' . locale_number_format($TotalCategoryCost,$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
		<th class="number">' . locale_number_format($TotalCategoryAccumDepn,$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
		<th class="number">' . locale_number_format(($TotalCategoryCost-$TotalCategoryAccumDepn),$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
		<th colspan="2"></th>
		<th class="number">' . locale_number_format($TotalCategoryDepn,$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
	</tr>
	<tr>
		<th colspan="3" align="right">' . _('GRAND Total') . ' </th>
		<th class="number">' . locale_number_format($TotalCost,$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
		<th class="number">' . locale_number_format($TotalAccumDepn,$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
		<th class="number">' . locale_number_format(($TotalCost-$TotalAccumDepn),$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
		<th colspan="2"></th>
		<th class="number">' . locale_number_format($TotalDepn,$_SESSION['CompanyRecord']['decimalplaces']) . '</th>
	</tr>';

echo '</table></div>';

if (isset($_POST['CommitDepreciation']) AND $InputError==false){
    
            $SQLArray[] = sprintf("Update companies set [last_depreciation]=DATEADD(MONTH,1,CAST('%s' AS datetime))  where [coycode]=1",FormatDateForSQL($_POST['ProcessDate']));
  
            foreach ($SQLArray as $SQL) {
                $ErrMsg = _('CRITICAL ERROR! NOTE DOWN THIS ERROR AND SEEK ASSISTANCE. The fixed asset accumulated depreciation could not be updated:');
		$DbgMsg = _('The following SQL was used to attempt the update the accumulated depreciation of the asset was:');
		$Result = DB_query($SQL,$db,$ErrMsg, $DbgMsg, true);
           }
           
           if(DB_error_no($db)==0){
               $result = DB_Txn_Commit($db);
               prnMsg(_('Depreciation') . ' ' . $TransNo . ' ' . _('has been successfully entered'),'success');
           }else{
               DB_Txn_Rollback($db);
           }
	
	unset($_POST['ProcessDate']);
} else {
	echo '<form autocomplete="off"action="' . htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') . '" method="post" id="form">';
    echo '<div>';
	echo '<input type="hidden" name="FormID" value="' . $_SESSION['FormID'] . '" />';
	echo '<br />
            
		<table class="table-striped table-bordered">
		<tr>';
	if ($AllowUserEnteredProcessDate){
		echo '<td>' . _('Date to Process Depreciation'). ':</td><td><input type="text" class="date" alt="' .$_SESSION['DefaultDateFormat']. '" required="required" name="ProcessDate" maxlength="10" size="11" value="' . $_POST['ProcessDate'] . '" /></td>';
	} else {
		echo '<td>' . _('Date to Process Depreciation'). ':</td><td>' . $_POST['ProcessDate']  . '</td>';
	}
	echo '<td><div class="centre"><input type="submit" name="CommitDepreciation" value="'._('Commit Depreciation').'" /></div></td></tr></table></DIV>';
        echo '<input type="button" onclick="tableToExcel(\'GL\',\'Fixed Assets Journal\')" value="Export to Excel"></div></form>';
}
include('includes/footer.inc');
?>