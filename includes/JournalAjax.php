<?php
/* $Id: ConnectDB_mssql.inc 6310 2014-08-06 14:41:50Z Jonathan Kiranga $ */
define ('LIKE','LIKE');
session_write_close(); //in case a previous session is not closed
session_name('ErpWithCRM');
session_start();
include('../config.php');
include('DateFunctions.inc');

global $db;
// Make sure it IS global, regardless of our context
$database = $_SESSION['DatabaseName'];
$db = odbc_connect("Driver={SQL Server};Server=$host;Database=$database;",$DBUser,$DBPassword);
 //DB wrapper functions to change only once for whole application

function DB_query($SQL , $Conn){
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

 /*
$sql="SELECT [coycode],[coyname],[PIN],[vat] ,[regoffice1],[regoffice2] ,[regoffice3]  ,[regoffice4] ,[regoffice5] ,[regoffice6] ,[telephone],[fax] ,[email],[currencydefault], currencies.decimalplaces
FROM companies  INNER JOIN currencies ON companies.currencydefault=currencies.currabrev  WHERE coycode=1";
$ReadCoyResult = DB_query($sql,$db);
if (DB_num_rows($ReadCoyResult)>0) {
    $_SESSION['CompanyRecord'] =  DB_fetch_array($ReadCoyResult);
}


$sql = "SELECT confname, confvalue FROM config";
$ConfigResult = DB_query($sql,$db);
while($myrow = DB_fetch_array($ConfigResult)) {
        if (is_numeric($myrow['confvalue']) AND $myrow['confname']!='DefaultPriceList' AND $myrow['confname']!='VersionNumber'){
                //the variable name is given by $myrow[0]
                $_SESSION[$myrow['confname']] = (double) $myrow['confvalue'];
        } else {
                $_SESSION[$myrow['confname']] =  $myrow['confvalue'];
        }
} 
*/
 
function getaccount($code){
    global $db;
    $result=DB_query("Select accno,accdesc from acct where accno='".$code."'",$db);
    $rows= DB_fetch_row($result);
    
    return $rows[1];
}

$Doctypes=array();
    
    $Results = DB_query("Select [typeid],[typename] from [systypes_1]",$db);
    while($roe = DB_fetch_array($Results)){
        $Doctypes[$roe['typeid']] = $roe['typename'];
    }
    


$getsql ="SELECT 
    [rowid],
    [journalno],
    [Docdate] ,
    [period] ,
    [DocumentNo],
    [DocumentType],
    [accountcode] ,
    [balaccountcode],
    ([amount] * [ExchangeRate]) as AMOUNT ,
      [currencycode] ,
      [ExchangeRate],
      [cutomercode] ,
      [suppliercode] ,
      [bankcode],
      [reconcilled] ,
      [narration] ,
      [ExchangeRateDiff],
      [VATaccountcode],
      [VATamount] ,
      [dimension] ,
      [dimension2]
      from [Generalledger] where 
     (Generalledger.Docdate between '".FormatDateForSQL($_POST['journaldate'])."'  and '".FormatDateForSQL($_POST['journaldate'])."')";
    if(mb_strlen($_POST['journalfind'])>0){
      $getsql .=" and Generalledger.DocumentNo='".$_POST['journalfind']."'" ;
    } 
     $getsql .="order by [Docdate],[rowid] asc ";
 
      $object ='<table class="statement display"><thead><tr>'
       . '<th>Date</th><th>Doc No</th><th>Doc Type</th>'
       . '<th>Debit Account</th><th>Credit Account</th>'
       . '<th>Narrative</th><th>Project</th><th>AMOUNT</th></tr></thead><tbody>';
      
       $key = '123';
       $ResultDrill = DB_query($getsql, $db);
       while($row=DB_fetch_array($ResultDrill)){
           if($row['AMOUNT']!=0){
               
              $debitanct = GetAccount($row['balaccountcode']);
              $creditanct = GetAccount($row['accountcode']);
                
               $object .= '<tr><td>'. ConvertSQLDate($row['Docdate']) .'</td>';
               $object .= sprintf('<td><a href="?DocumentNo=%s&Docdate=%s">%s</a></td>',$row['DocumentNo'],$row['Docdate'],$row['DocumentNo'] );
               $object .= '<td>'.$Doctypes[$row['DocumentType']] .'</td>';
               $object .= '<td>'.$debitanct .'</td>';
               $object .= '<td>'.$creditanct.'</td>';
               $object .= '<td>'.$row['narration'].'</td>';
               $object .= '<td>'.$row['dimension2'].'</td>';
               $object .= '<td class="number">'.number_format($row['AMOUNT'],2) .'</td></tr>';
                
           }
       }
       
$object .= '</tbody><tfoot><th>Date</th><th>Doc No</th><th>Doc Type</th>'
. '<th>Debit Account</th><th>Credit Account</th>'
. '<th>Narrative</th><th>Project</th><th>Amount</th></tfoot></table>';

echo $object;

 class AjaxUnsafeCrypto{
    const METHOD = 'aes-256-ctr';

    /**
     * Encrypts (but does not authenticate) a message
     * 
     * @param string $message - plaintext message
     * @param string $key - encryption key (raw binary expected)
     * @param boolean $encode - set to TRUE to return a base64-encoded 
     * @return string (raw binary)
     */
    public static function encrypt($message, $key, $encode = false)
    {
        $nonceSize = openssl_cipher_iv_length(self::METHOD);
        $nonce = openssl_random_pseudo_bytes($nonceSize);

        $ciphertext = openssl_encrypt(
            $message,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        );

        // Now let's pack the IV and the ciphertext together
        // Naively, we can just concatenate
        if ($encode) {
            return base64_encode($nonce.$ciphertext);
        }
        return $nonce.$ciphertext;
    }

    /**
     * Decrypts (but does not verify) a message
     * 
     * @param string $message - ciphertext message
     * @param string $key - encryption key (raw binary expected)
     * @param boolean $encoded - are we expecting an encoded string?
     * @return string
     */
    public static function decrypt($message, $key, $encoded = false)
    {
        if ($encoded) {
            $message = base64_decode($message, true);
            if ($message === false) {
                throw new Exception('Encryption failure');
            }
        }

        $nonceSize = openssl_cipher_iv_length(self::METHOD);
        $nonce = mb_substr($message, 0, $nonceSize, '8bit');
        $ciphertext = mb_substr($message, $nonceSize, null, '8bit');

        $plaintext = openssl_decrypt(
            $ciphertext,
            self::METHOD,
            $key,
            OPENSSL_RAW_DATA,
            $nonce
        );

        return $plaintext;
    }
}
 
?>
