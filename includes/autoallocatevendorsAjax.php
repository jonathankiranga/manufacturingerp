<?php
   /* $Id: ConnectDB_mssql.inc 6310 2014-08-06 14:41:50Z Jonathan Kiranga $ */
define ('LIKE','LIKE');

session_write_close(); //in case a previous session is not closed
session_name('ErpWithCRM');
session_start();
include( '../config.php');

global $db,$database;
// Make sure it IS global, regardless of our context
$database = $_SESSION['DatabaseName'];
$db = odbc_connect("Driver={SQL Server};Server=$host;Database=$database;",$DBUser,$DBPassword);
 //DB wrapper functions to change only once for whole application
 
if(file('aging.bat')==false){
    $text="sqlcmd -S $host -E -i aging.sql  -o jonabackup.log";
    file_put_contents('aging.bat',$text);
}

if(file('aging.sql')==false){
$CMD="USE [$database] 
    go
    EXEC [dbo].[AutoAllocateALLcustomers]
    GO   

    EXEC [dbo].[AutoAllocateALLsuppliers] 
    Go
    ";

file_put_contents('aging.sql',$CMD);
}

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

$sql="SELECT [coycode],[coyname],[PIN],[vat] ,[regoffice1],[regoffice2] ,[regoffice3]  ,[regoffice4] ,[regoffice5] ,[regoffice6] ,[telephone],[fax] ,[email],[currencydefault], currencies.decimalplaces
FROM companies  INNER JOIN currencies ON companies.currencydefault=currencies.currabrev  WHERE coycode=1";
$ReadCoyResult = DB_query($sql,$db);
if (DB_num_rows($ReadCoyResult)>0) {
    $_SESSION['CompanyRecord'] =  DB_fetch_array($ReadCoyResult);
}

if(isset($_GET['autoallocatevendors'])){
    Autoallocatevendors($_GET['autoallocatevendors']);
}elseif(isset($_POST['autoallocatevendors'])){
    Autoallocatevendors($_POST['autoallocatevendors']);
}

function Autoallocatevendors($vendorid){
    global $db;
    
    echo 'Vendors payment now being allocated';
    DB_query("EXEC [dbo].[autoallocatevendors]  @accountno ='".$vendorid."'", $db);
         
}
      
if(isset($_GET['autoallocatedebtors'])){
    autoallocatedebtors($_GET['autoallocatedebtors']);
}elseif(isset($_POST['autoallocatedebtors'])){
    autoallocatedebtors($_POST['autoallocatedebtors']);
}

function autoallocatedebtors($vendorid){
    global $db;
    
    echo 'Debtors payment now being allocated';
    DB_query("EXEC [dbo].[autoallocatedebtors]  @accountno ='".$vendorid."'", $db);
      
}

if(isset($_GET['autoallocateall'])){
    execInBackground();
}elseif(isset($_POST['autoallocateall'])){
    execInBackground();
}

function execInBackground() {
    $handle = popen('start /B aging.bat >nul 2>&1', 'r');
    pclose($handle);
}


   
?>