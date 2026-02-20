<?php
include('../Mailer/PHPMailerAutoload.php');
include('../config.php');

if(!file_exists("../chats/EmailConfig.php")){
    header('Location:../EmailConfigurator.php');
}else{
   include('../chats/EmailConfig.php');
}

global $db;
// Make sure it IS global, regardless of our context
$db = odbc_connect("Driver={SQL Server};Server=$host;Database=$DefaultDatabase;",$DBUser,$DBPassword);
 //DB wrapper functions to change only once for whole application

function DB_query ($SQL,$Conn){
   $result = odbc_exec($Conn,$SQL);
   return $result;
}

function DB_fetch_array ($ResultIndex) {
  return  odbc_fetch_array($ResultIndex,$rownumber=null);
}

function Resetpassword($email,$password){
   Global $db;
   $SQL = "update [www_users] set [password]='".$password."' WHERE [email]='" .$email. "'";
   $ResultIndex=DB_query($SQL,$db);
}

Function GetCredentials($email){
 Global $db;
  $arr = array();
   $jsonData = '{"results":[';
  
   $SQL = "SELECT [userid],[password],[realname],[phone],[email] FROM [www_users]  WHERE [email]='" .$email. "'";
   
   $ResultIndex=DB_query($SQL,$db);
   $line = new stdClass;
      while ($rows = odbc_fetch_array($ResultIndex)) {
        $line->membershipNo = $rows['phone'];
        $line->userid = $rows['userid'];
        $line->userpassword = $rows['password'];
        $line->names = $rows['realname'];
        $arr[] = json_encode($line);
      }
   
   $jsonData .= implode(",", $arr);
      $jsonData .= ']}';
      return $jsonData;
}

$password = rand(999,9856245);
Resetpassword($_POST['getemail'],$password);
$callbackJSONData=GetCredentials($_POST['getemail']);
$callbackData=json_decode($callbackJSONData);

if(count($callbackData->results)==1){
    
$pf_no=$callbackData->results[0]->membershipNo;
$userid=$callbackData->results[0]->userid;
$userpassword=$callbackData->results[0]->userpassword;
$names=$callbackData->results[0]->names;
  
//Create a new PHPMailer instance
$mail = new PHPMailer;
//Tell PHPMailer to use SMTP
$mail->isSMTP();
//Enable SMTP debugging
// 0 = off (for production use)
// 1 = client messages
// 2 = client and server messages
$mail->SMTPDebug =0;
//Ask for HTML-friendly debug output
$mail->Debugoutput = 'html';
//Set the hostname of the mail server
$mail->Host = HOST ;
// use
// $mail->Host = gethostbyname('smtp.gmail.com');
// if your network does not support SMTP over IPv6
$mail->SMTPOptions = array('ssl' => array('verify_peer' => false,'verify_peer_name' => false,'allow_self_signed' => true));
//Set the SMTP port number - 587 for authenticated TLS, a.k.a. RFC4409 SMTP submission
$mail->Port = PORT;
//Set the encryption system to use - ssl (deprecated) or tls
$mail->SMTPSecure = 'tls';
//Whether to use SMTP authentication
$mail->SMTPAuth = true;
//Username to use for SMTP authentication - use full email address for gmail
$mail->Username = username;
//Password to use for SMTP authentication
$mail->Password = trim(decrypt(password));
//Set who the message is to be sent from
$mail->setFrom(username,'SmartERP');
//Set an alternative reply-to address
//$mail->addReplyTo('No-Reply','SmartERP');
//Set who the message is to be sent to
$mail->addAddress($_POST['getemail'],$names);
//Set the subject line

$ConfirmationURL = $_SERVER["SERVER_NAME"].':'.$_SERVER["SERVER_PORT"] ;
$ConfirmationURL = htmlspecialchars($ConfirmationURL.'/SmartERP/index.php',ENT_QUOTES,'UTF-8');

$mail->Subject = 'Your ERP,  Login Credentials';

$mailbody="Dear ".$names."<br/>"
        . "Mobile No=".$pf_no."<br/>"
        . "Once you Login please Change your password.<br/>"
        . "Your user ID: ".$userid ."<br/>"
        . "Your password is : ". trim($userpassword)."<br/>"
        . "Use this URL http://".$ConfirmationURL ."<br/>";
//Replace the plain text body with one created manually

$mail->WordWrap = 78;
$mail->msgHTML($mailbody, dirname(__FILE__), true); //Create message bodies and embed images
//$mail->AltBody = $mailbody;
//send the message, check for errors
if (!$mail->send()) {
    echo "Mailer Error: " . $mail->ErrorInfo;
   
} else {
    echo "Message sent!";
}

}else{
     echo 'The system cannot find this email address';
}

function encrypt($data){
 // Store cipher method
$ciphering = "BF-CBC";
 
$options = 0;
// Use random_bytes() function which gives
// randomly 16 digit values
$encryption_iv = "12345678";
// Alternatively, we can use any 16 digit
// characters or numeric for iv
$encryption_key = "12345678";
// Encryption of string process starts
$encryption = openssl_encrypt($data, $ciphering,$encryption_key, $options, $encryption_iv);

return $encryption;
}


function decrypt($data){
// Store cipher method
$ciphering = "BF-CBC";

$options = 0;

$decryption_iv ="12345678" ;
// Store the decryption key
$decryption_key = "12345678";
// Descrypt the string
$decryption = openssl_decrypt ($data, $ciphering,$decryption_key, $options,$decryption_iv);

return $decryption;
}

?>