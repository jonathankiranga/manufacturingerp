<?php
include('includes/session.inc');
$Title = _('Upcomming Activities');
include('includes/header.inc');


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
?>

<DIV><p class="good">Upcoming Events</p></DIV>
<div class="table-responsive">
    <p class="good"><?php echo $CRMArray[1]; ?></p>
        <?php Meetings(); ?>
    <input type="button" onclick="tableToExcel('abc','<?php echo $CRMArray[1] ?>')" 
           value="Open Activites '<?php echo $CRMArray[1] ?>' in Excel">
</div>

<div class="table-responsive">
    <p class="good"><?php echo $CRMArray[2]; ?></p>
        <?php NegotiationBasic(); ?>
    <input type="button" onclick="tableToExcel('def','<?php echo $CRMArray[2] ?>')" 
           value="Open Activites '<?php echo $CRMArray[2] ?>' in Excel">
</div>
<div class="table-responsive">
    <p class="good"><?php echo $CRMArray[3]; ?></p>
        <?php NegotiationAdvaned(); ?>
    <input type="button" onclick="tableToExcel('ghi','<?php echo $CRMArray[3] ?>')" 
           value="Open Activites '<?php echo $CRMArray[3] ?>' in Excel">
</div>
<div class="table-responsive">
    <p class="good"><?php echo $CRMArray[4]; ?></p>
        <?php ClosingDeals(); ?>
   <input type="button" onclick="tableToExcel('klm','<?php echo $CRMArray[4] ?>')" 
          value="Open Activites '<?php echo $CRMArray[4] ?>' in  Excel">
</div>


<?php

include('includes/footer.inc');

Function Meetings(){
    global $db,$CRMArray;
    $FinalArray=array();
    $FinalArray = GetActivities('1');
  

    if(count($FinalArray)==0){
        echo "No Data";
    }else{
       echo '<table class="table table-bordered table-striped" id="abc">';
       echo '<tr><th>Activity Name</th><th>Start Date</th><th>End Date</th><th>Contact</th><th>Value Of Business</th><th>Activity Details</th><th>Sales Person</th></tr>';
        foreach ($FinalArray as $value) {
            ShowHtml($value);
        }
       echo '</table>';

    }


}

Function NegotiationBasic(){
    global $db,$CRMArray;
      $FinalArray=array();
    $FinalArray = GetActivities('2');
  
  
    if(count($FinalArray)==0){
        echo "No Data";
    }else{
       echo '<table class="table table-bordered table-striped" id="def">';
       echo '<tr><th>Activity Name</th><th>Start Date</th><th>End Date</th><th>Contact</th><th>Value Of Business</th><th>Activity Details</th><th>Sales Person</th></tr>';
          foreach ($FinalArray as $value) {
            ShowHtml($value);
        }
       echo '</table>';

    }

}

Function NegotiationAdvaned(){
    global $db,$CRMArray;
      $FinalArray=array();

    $FinalArray=array();
    $FinalArray = GetActivities('3');
  

    if(count($FinalArray)==0){
        echo "No Data";
    }else{
       echo '<table class="table table-bordered table-striped" id="ghi">';
      echo '<tr><th>Activity Name</th><th>Start Date</th><th>End Date</th><th>Contact</th><th>Value Of Business</th><th>Activity Details</th><th>Sales Person</th></tr>';
         foreach ($FinalArray as $value) {
            ShowHtml($value);
        }
       echo '</table>';

    }


}

Function ClosingDeals(){
    global $db,$CRMArray;
    $FinalArray=array();
    $FinalArray = GetActivities('4');
   
    if(count($FinalArray)==0){
        echo "No Data";
    }else{
       echo '<table class="table table-bordered table-striped" id="klm">';
       echo '<tr><th>Activity Name</th><th>Start Date</th><th>End Date</th><th>Contact</th><th>Value Of Business</th><th>Activity Details</th><th>Sales Person</th></tr>';
        foreach ($FinalArray as $value) {
            ShowHtml($value);
        }
       echo '</table>';

    }

}



function GetActivities($status){
     global $db;

     $SQL=sprintf("select [pkey],[ActivityOwner],[Activityname],[fromdue],[todue],[Contact],[Status]
      ,[valueofbusiness],[taskdetails],[createdby],[createdon],[lastactivity],DATEDIFF(DAY,getdate(),[fromdue]) as Future
       FROM [NewActivity] where [Status]=%f  order by [pkey] desc",$status);

      $result=DB_query($SQL,$db);
     while($myrow = DB_fetch_array($result)){
         $GetActivities[]=$myrow;
     }

 return  $GetActivities;
}

function ShowHtml($value){
    Global $db,$NewContacts,$wwwusers;
        
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
      
        $co=(int)$value["Contact"];
        $contact=$NewContacts[$co];
        
        $sp= $value["ActivityOwner"];
        $salesperson=$wwwusers[$sp];
        
        echo sprintf('<tr %s>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div style="width:100px;">%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '</tr>',$style,$value["Activityname"],
                  ConvertSQLDate($value["fromdue"]),ConvertSQLDate($value["todue"]),
                $contact, $antipatedBusines,$value["taskdetails"],$salesperson);
}

?>