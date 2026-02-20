<?php
   /* $Id: ConnectDB_mssql.inc 6310 2014-08-06 14:41:50Z Jonathan Kiranga $ */
define('LIKE','LIKE');

session_write_close(); //in case a previous session is not closed
session_name('ErpWithCRM');
session_start();
include('../config.php');

Global $db,$PriorityArray,$wwwusers,$TaskstatusArray,$NewContacts;
 
// Make sure it IS global, regardless of our context
$database = $_SESSION['DatabaseName'];
$db = odbc_connect("Driver={SQL Server};Server=$host;Database=$database;",$DBUser,$DBPassword);
 //DB wrapper functions to change only once for whole application


$CRMArray = Array(
    '0'=>'New Lead',
    '1'=>'Scheduling for a Meeting',
    '2'=>'Beginning Negotiations',
    '3'=>'Advanced Negotiations',
    '4'=>'Closing Deal',
    '5'=>'Lost Deal',
    '6'=>'Now A Customer');

$PriorityArray=array(
    "0"=>'High',
    "1"=>'Moderate',
    "2"=>'low');

$frequencyArray=array(
    "0"=>'Once',
    "1"=>'Daily',
    "7"=>'Weekly',
    "30"=>'Monthly',
    "365"=>'Annualy');

$TaskstatusArray=array(
    "0"=>'Not yet begun',
    "1"=>'In progress',
    "2"=>'Almost Done',
    "3"=>'Taking Longer than expected',
    "4"=>'Complete');

/*These arrays are for the CRM*/
$result = DB_query("SELECT userid,realname FROM www_users where [blocked]=0 ",$db);
While($row= DB_fetch_array($result)){
    $user=trim($row['userid']);
    $wwwusers[$user]=$row['realname'];
}

$result = DB_query("SELECT [Company],[pkey] FROM [NewContacts]",$db);
While($row= DB_fetch_array($result)){
    $pkey=(int)$row['pkey'];
    $NewContacts[$pkey]=$row['Company'];
}

 if(isset($_GET['TaskName'])){
    $Function=$_GET['TaskName'];
 }elseif(isset($_POST['TaskName'])){
    $Function=$_POST['TaskName'];
 }
 
 if(isset($_GET['TASKFORM'])){
    $TASKFORM=$_GET['TASKFORM'];
 }elseif(isset($_POST['TASKFORM'])){
    $TASKFORM=$_POST['TASKFORM'];
 }
 
if(isset($Function)){
     $PriodnoArray = GetReportDates($TASKFORM);
    switch ($Function) {
        case 'ACTIVITY':
            CompletedActivity();
            break;
       default:
            CompletedTasks();
            break;
    }
}

/* Begin of functions*/

Function CompletedTasks(){
    global $db,$CRMArray;
    $FinalArray = GetTasks();
    
    $html = '';

    if(is_array($FinalArray)){
        foreach ($FinalArray as $key => $value) {
           $html .= ShowTasksHtml($value);
        }
    }else{
        $html ='No Data';
    } 
  
   echo $html;
}

function ShowTasksHtml($value){
    Global $db,$PriorityArray,$wwwusers,$TaskstatusArray;
      
    $sp = trim($value["Status"]);
    $Taskstatus=$TaskstatusArray[$sp];
    
    if($value["Future"]>0){
        $style='style="background-color:pink"';
    }
    if($value["Future"]==0){
        $style='style="background-color:white;"';
    }
    if($value["Future"]<0){
        $style='style="background-color:lightcyan;"';
    }
      
    if($sp==4){
       $style='style="background-color:white;"';
    }
    
    $sp =(int) $value["Priority"];
    $Priority=$PriorityArray[$sp];

    $sp = trim($value["TaskOwner"]);
    $TaskOwner=$wwwusers[$sp];
    
   
       
    $taskdetails = html_entity_decode($value["taskdetails"]);
    $taskdetails = str_replace('<p>','<li>',$taskdetails);
    $taskdetails = str_replace('</p>','</li>',$taskdetails);
    return  sprintf('<tr %s>'
              . '<td><div>%s</div></td>'
              . '<td><div>%s</div></td>'
              . '<td><div>%s</div></td>'
              . '<td><div>%s</div></td>'
              . '<td><div>%s</div></td>'
              . '<td><ul>%s</ul></td>'
              . '</tr>',$style,$TaskOwner,$value["Taskname"],
            miniConvertSQLDate($value["datedue"]),$Priority,$taskdetails,$Taskstatus);

}

function GetTasks(){
     global $db,$PriodnoArray ;
   
     $SQL=sprintf("SELECT [TaskOwner],[Taskname],[datedue],[Status],[Priority],[frequency],[taskdetails] 
      ,DATEDIFF(DAY,getdate(),[datedue]) as Future  FROM [Tasks] where datedue between '%s' and '%s' 
      order by [TaskOwner],[Priority],[Status] asc", $PriodnoArray['fromDate'],$PriodnoArray['toDate']);
     
     $Activities=array();
     $result=DB_query($SQL,$db);
     while($myrow = DB_fetch_array($result)){
         $Activities[]=$myrow;
     }
     
    
 return  $Activities;
}

/* Ene of functions*/

Function ShowActivityHtml($value){
     Global $db,$NewContacts,$wwwusers,$CRMArray;

        $sp = trim($value["Status"]);
        $Taskstatus =$CRMArray[$sp];
        
        if($value["valueofbusiness"]==''){
            $antipatedBusines='Not yet Known';
        }else{
           $antipatedBusines= number_format($value["valueofbusiness"]);
        }
        if($value["Future"]>0){
            $style='style="background-color:pink"';
        }
        if($value["Future"]==0){
            $style='style="background-color:white;"';
        }
        if($value["Future"]<0){
            $style='style="background-color:lightcyan;"';
        }

        if($sp>=4){
            $style='style="background-color:white;"';
        }
        
        $co=(int)$value["Contact"];
        $contact =$NewContacts[$co];
        
        $sp= $value["ActivityOwner"];
        $salesperson =$wwwusers[$sp];
        
     
        $taskdetails = html_entity_decode($value["taskdetails"]);
        $taskdetails = str_replace('<p>','<li>',$taskdetails);
        $taskdetails = str_replace('</p>','</li>',$taskdetails);
     
       return  sprintf('<tr %s>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div style="width:100px;">%s</div></td>'
                  . '<td><ul>%s</ul></td>'
                  . '<td><div>%s</div></td>'
                  . '</tr>',$style,$value["Activityname"],
                  miniConvertSQLDate($value["fromdue"]),miniConvertSQLDate($value["todue"]),
                $contact,$antipatedBusines,$Taskstatus,$taskdetails,$salesperson);

}

Function CompletedActivity(){
     global $db,$CRMArray;
   
     $FinalArray = GetActivities();
     $HTML='';

     if(is_array($FinalArray)){
        foreach ($FinalArray as $value) {
          $HTML  .=   ShowActivityHtml($value);
        }
     }else{
         $HTML= 'No data';
     }
    echo $HTML;
}

function GetActivities(){
       global $db,$PriodnoArray ;
 
       $SQL=sprintf("select [pkey],[ActivityOwner],[Activityname],[fromdue],[todue],[Contact],[Status]
      ,[valueofbusiness],[taskdetails],[createdby],[createdon],[lastactivity],DATEDIFF(DAY,getdate(),[fromdue]) as Future
       FROM [NewActivity] where [todue] between '%s' and '%s'  order by [ActivityOwner],[Status] desc ",$PriodnoArray['fromDate'],$PriodnoArray['toDate']);

      $Activities=array();
      $result=DB_query($SQL,$db);
     while($myrow = DB_fetch_array($result)){
         $Activities[]=$myrow;
     }

 return  $Activities;
}

/* Ene of functions*/

function GetReportDates($dates){
    global $db;
    
   $ResultIndex=DB_query("Select DATEADD(M,-1,[lastdate_in_period]+1),DATEADD(MINUTE,1439,[lastdate_in_period])
             from [periods] where [periodno]='".$dates."'", $db);
   $rowdate = DB_fetch_row($ResultIndex);
   $FromDate = $rowdate[0];
   $ToDate = $rowdate[1];
 
return array("fromDate"=>$FromDate,"toDate"=>$ToDate);
}

/* Ene of functions*/


 if(isset($_GET['Stockfind'])){
    Stockfind($_GET['offset'],$_GET['top'],$_GET['Stockfind']);
 }elseif(isset($_POST['Stockfind'])){
    Stockfind($_POST['offset'],$_POST['top'],$_POST['Stockfind']);
 }
   
function Stockfind($offset,$height,$value){
    Global $db;
    $_SESSION['work_orders']=array();
    unset($_SESSION['work_orders']);
    
    $top  = 50;
    $left = $offset['left'];
   
     $ResultIndex = DB_query("SELECT itemcode,upper(descrip) as descrip from stockmaster 
               where inactive=0 and (isstock_4 =1 or isstock_1 =1)  order by descrip", $db);
    
       $return= '<div class="finderheader" id="findStock" style="top:'. $top .'px; left:'.$left.'px;">'
             . '<b>What do you want to Produce</b><div class="finder"><table id="multStockTable" class="table table-bordered">';
            while($row=DB_fetch_array($ResultIndex)){
           $return .= sprintf('<tr onclick="prodInventory(\'%s\',\'%s\');ReloadForm(prodform.refresh);"><td>%s</td></tr>',trim($row['itemcode']),trim($row['descrip']),trim($row['descrip'])) ;
           }
           $return .= '</table></div>'
           . '<input type="text" tabindex="1" class="myInput" id="multStockInput" onkeyup="multStockFunction();"  autofocus="autofocus" placeholder="Search for names..">'
           . '<input type="button" onclick="prodInventory(\'\',\'\')" value="Cancel" />
          </div>';
    
 
   echo $return;
 }
 
 
 if(isset($_GET['stockname'])){
    stockname($_GET['offset'],$_GET['top'],$_GET['stockname']);
 }elseif(isset($_POST['stockname'])){
    stockname($_POST['offset'],$_POST['top'],$_POST['stockname']);
 }
   
function stockname($offset,$height,$value){
    Global $db;
    
     $ResultIndex=DB_query("select code, descrip from unit",$db);
             while($row = DB_fetch_array($ResultIndex)){
                $code = trim($row['code']);
                $_SESSION['units'][$code]=$row;
            }
            
    $top  = 50;
    $left = $offset['left'];
   
     $ResultIndex = DB_query("SELECT itemcode,upper(descrip) as descrip,[units] from stockmaster 
               where inactive=0 and (isstock_4 =1 or isstock_2 =1)
               order by descrip", $db);
    
       $return= '<div class="finderheader" id="findStock" style="top:'. $top .'px; left:'.$left.'px;">'
               . '<b>What do you want to Produce</b><div class="finder">'
               . '<table id="multStockTable" class="table table-bordered">';
            while($row=DB_fetch_array($ResultIndex)){
                $code = trim($row['units']);
                $description=trim($row['descrip']).' '.$_SESSION['units'][$code]['descrip'];
                $return .= sprintf('<tr onclick="ItemInventory(\'%s\',\'%s\');ReloadForm(prodform.refresh);">'
                      . '<td>%s</td></tr>',trim($row['itemcode']),$description,trim($row['descrip'])) ;
           }
              $return .= sprintf('<tr onclick="ItemInventory(\'%s\',\'%s\');ReloadForm(prodform.refresh);">'
                      . '<td>%s</td></tr>',trim('H2O'),trim('WATER ltrs'),trim('WATER')) ;
           
           
           
           $return .= '</table></div>'
           . '<input type="text" tabindex="1" class="myInput" id="multStockInput" onkeyup="multStockFunction();"  autofocus="autofocus" placeholder="Search for names..">'
           . '<input type="button" onclick="ItemInventory(\'\',\'\')" value="Cancel" />
          </div>';
    
 
   echo $return;
 }
 
 
 
 
 
 
 
 /*End These arrays are for the CRM*/

 
function DB_query ($SQL , $Conn){
  $result = odbc_exec($Conn,$SQL);
    return $result;
}

function DB_fetch_array ($ResultIndex) {
  return  odbc_fetch_array($ResultIndex,$rownumber=null);
}

function DB_num_rows ($ResultIndex){
  return odbc_num_rows($ResultIndex);
}

function DB_fetch_row($ResultIndex) {
    $ARRY = array();  $r = 0;
     if (odbc_fetch_row($ResultIndex)){
         for ($i=1; $i <= odbc_num_fields($ResultIndex); $i++) {
              $ARRY[$r] = odbc_result($ResultIndex,$i);
              $r++;
            }
       }
     return $ARRY;
 }   

Function DB_Find_Table($SelectedTable){
    global $db;
    $rows = 0;
    $TableResult = DB_show_tables($db);

    while(Table_fetch_row($TableResult)) {
        if(Table_name($TableResult) == $SelectedTable) {
            $rows=1;
         }
    }

return $rows;
}

Function DB_Table_rename($tablefrom,$tableto){
    global $db;
    $result = DB_query("sp_rename 'dbo.".$tablefrom."', '".$tableto."' ",$db);
}

Function Db_Drop_Table($tableName){
    global $db;

    $sql = "IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[".$tableName."]') AND type in (N'U'))
            DROP TABLE [dbo].[".$tableName."]";
    $result = DB_query($sql,$db);
}

function DB_html_decode($String){
    return  htmlspecialchars($String);
}

function DB_escape_string($String){
    $addedslashes = addslashes(htmlspecialchars($String, ENT_COMPAT,'utf-8', false));
   return str_replace("\\","'", $addedslashes );
}

function DB_backup_string($String){
    $addedslashes = addslashes(htmlspecialchars($String, ENT_COMPAT,'utf-8', false));
   return str_replace("/","\\", $addedslashes );
}

function Table_fetch_row ($ResultIndex) {
  return odbc_fetch_row($ResultIndex);
}

function Table_name($ResultIndex) {
  return odbc_result($ResultIndex,"TABLE_NAME");
}

function DB_show_fields($TableName, $Conn){
   Return odbc_columns($Conn,'','',$TableName);
}

function DB_show_tables($Conn){
   return odbc_tables($Conn);
 }



function DB_fetch_assoc ($ResultIndex) {
    Return odbc_fetch_assoc($ResultIndex);
}


function DB_data_seek ($ResultIndex,$Record) {
    return  odbc_fetch_row($ResultIndex,$Record);
}

function DB_free_result ($ResultIndex){
    if (is_resource($ResultIndex)) {
    	odbc_free_result($ResultIndex);
    }
}


function DB_affected_rows($ResultIndex){
    return odbc_num_rows($ResultIndex);
}

function DB_error_no ($Conn){
   return odbc_error();
}

function DB_error_msg($Conn){
   return odbc_errormsg();
}

function DB_Last_Insert_ID($Conn, $Table, $FieldName){
    $SQL = "SELECT IDENT_CURRENT('".$Table."')";
    $resuts = DB_query($SQL,$Conn);
    $row = DB_fetch_row($resuts);
    return $row[0];
}

function interval( $val, $Inter ){
    global $dbtype;
    return "\n".'interval ' . $val . ' '. $Inter."\n";
}

function DB_Maintenance($Conn){
   prnMsg(_('The system has just run the regular database administration and optimisation routine.'),'info');
   $Result = DB_query("UPDATE config SET confvalue='" . Date('Y-m-d') . "' WHERE confname='DB_Maintenance_LastRun'", $Conn);
}

function DB_Txn_Begin($Conn){
     odbc_autocommit($Conn,false) ;
}

function DB_Txn_Commit($Conn){
   odbc_commit($Conn);
   odbc_autocommit($Conn,true) ;
}

function DB_Txn_Rollback($Conn){
    odbc_rollback($Conn);
}

function DB_IgnoreForeignKeys($Conn){
   odbc_exec($Conn, 'sp_MSforeachtable @command1="ALTER TABLE ? NOCHECK CONSTRAINT ALL"');
}

function DB_ReinstateForeignKeys($Conn){
    odbc_exec($Conn, 'sp_MSforeachtable @command1="ALTER TABLE ? CHECK CONSTRAINT ALL"');
}

function fetch_array($id_res){
  $ARRY = array();

     if (odbc_fetch_row($id_res)){
         for ($i = 1; $i <= odbc_num_fields($id_res); $i++) {
              $field_name = odbc_field_name($id_res, $i);
              $ARRY[$field_name] = odbc_result($id_res,$field_name);
            }
       }
           return $ARRY;
   }

   

function miniConvertSQLDate($DateEntry) {
    	
//for MySQL dates are in the format YYYY-mm-dd

	if (mb_strpos($DateEntry,'/')) {
		$Date_Array = explode('/',$DateEntry);
	} elseif (mb_strpos ($DateEntry,'-')) {
		$Date_Array = explode('-',$DateEntry);
	} elseif (mb_strpos ($DateEntry,'.')) {
		$Date_Array = explode('.',$DateEntry);
	} else {
		switch ($_SESSION['DefaultDateFormat']) {
			case 'd/m/Y':
				return '0/0/000';
				break;
			case 'd.m.Y':
				return '0.0.000';
				break;
			case 'm/d/Y':
				return '0/0/0000';
				break;
			case 'Y/m/d':
				return '0000/0/0';
				break;
			case 'Y-m-d':
				return '0000-0-0';
				break;
		}
	}

	if (mb_strlen($Date_Array[2])>4) {  /*chop off the time stuff */
		$Date_Array[2]= mb_substr($Date_Array[2],0,2);
	}

	if ($_SESSION['DefaultDateFormat']=='d/m/Y'){
		return $Date_Array[2].'/'.$Date_Array[1].'/'.$Date_Array[0];
	} elseif ($_SESSION['DefaultDateFormat']=='d.m.Y'){
		return $Date_Array[2].'.'.$Date_Array[1].'.'.$Date_Array[0];
	} elseif ($_SESSION['DefaultDateFormat']=='m/d/Y'){
		return $Date_Array[1].'/'.$Date_Array[2].'/'.$Date_Array[0];
	} elseif ($_SESSION['DefaultDateFormat']=='Y/m/d'){
		return $Date_Array[0].'/'.$Date_Array[1].'/'.$Date_Array[2];
	} elseif ($_SESSION['DefaultDateFormat']=='Y-m-d'){
		return $Date_Array[0].'-'.$Date_Array[1].'-'.$Date_Array[2];
	}
} // end function ConvertSQLDate

