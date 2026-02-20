<?php
include('includes/session.inc');
$Title = _('Upcomming Tasks');
include('includes/header.inc');


$result = DB_query("SELECT userid,realname FROM www_users ",$db);
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
    <p class="good"><?php echo $TaskstatusArray[0]; ?></p>
        <?php Meetings(); ?>
    <input type="button" onclick="tableToExcel('abc','<?php echo $TaskstatusArray[0] ?>')" 
           value="Open Tasks window '<?php echo $TaskstatusArray[0] ?>' in Excel">
</div>

<div class="table-responsive">
    <p class="good"><?php echo $TaskstatusArray[1]; ?></p>
        <?php NegotiationBasic(); ?>
    <input type="button" onclick="tableToExcel('def','<?php echo $TaskstatusArray[1] ?>')" 
           value="Open Tasks window '<?php echo $TaskstatusArray[1] ?>' in Excel">
</div>
<div class="table-responsive">
    <p class="good"><?php echo $TaskstatusArray[2]; ?></p>
        <?php NegotiationAdvaned(); ?>
    <input type="button" onclick="tableToExcel('ghi','<?php echo $TaskstatusArray[2]; ?>')" 
           value="Open Tasks window '<?php echo $TaskstatusArray[2] ?>' in Excel">
</div>
<div class="table-responsive">
    <p class="good"><?php echo $TaskstatusArray[3] ?></p>
        <?php ClosingDeals(); ?>
   <input type="button" onclick="tableToExcel('klm','<?php echo $TaskstatusArray[3] ?>')" 
          value="Open Tasks window '<?php echo $TaskstatusArray[3] ?>' in  Excel">
</div>


<?php

include('includes/footer.inc');

Function Meetings(){
    global $db,$CRMArray;
    $FinalArray = array();
    $FinalArray = GetActivities('0');
  

    if(count($FinalArray)==0){
        echo "No Data";
    }else{
       echo '<table class="table table-bordered table-striped" id="abc">';
         echo '<tr><th>Task Owner/User</th><th>Task Name</th><th>Due On</th><th>Priority</th><th>Task Details</th></tr>';
        foreach ($FinalArray as $value) {
            ShowHtml($value);
        }
       echo '</table>';

    }


}

Function NegotiationBasic(){
    global $db,$CRMArray;
      $FinalArray = array();
      $FinalArray = GetActivities('1');
 
    if(count($FinalArray)==0){
        echo "No Data";
    }else{
       echo '<table class="table table-bordered table-striped" id="def">';
          echo '<tr><th>Task Owner/User</th><th>Task Name</th><th>Due On</th><th>Priority</th><th>Task Details</th></tr>';
       foreach ($FinalArray as $value) {
            ShowHtml($value);
        }
       echo '</table>';

    }

}

Function NegotiationAdvaned(){
    global $db,$CRMArray;
    $FinalArray = array();
    $FinalArray = GetActivities('2');

    if(count($FinalArray)==0){
        echo "No Data";
    }else{
       echo '<table class="table table-bordered table-striped" id="ghi">';
       echo '<tr><th>Task Owner/User</th><th>Task Name</th><th>Due On</th><th>Priority</th><th>Task Details</th></tr>';
         foreach ($FinalArray as $value) {
            ShowHtml($value);
        }
       echo '</table>';

    }


}

Function ClosingDeals(){
    global $db,$CRMArray;
    $FinalArray=array();
    $FinalArray = GetActivities('3');
   
    if(count($FinalArray)==0){
        echo "No Data";
    }else{
       echo '<table class="table table-bordered table-striped" id="klm">';
       echo '<tr><th>Task Owner/User</th><th>Task Name</th><th>Due On</th><th>Priority</th><th>Task Details</th></tr>';
        foreach ($FinalArray as $value) {
            ShowHtml($value);
        }
       echo '</table>';

    }

}



function GetActivities($status){
     global $db;

     $SQL=sprintf("SELECT [TaskOwner],[Taskname],[datedue]
      ,[Status],[Priority],[frequency],[taskdetails] 
      ,DATEDIFF(DAY,getdate(),[datedue]) as Future
       FROM [Tasks] where [Status]=%f order by [Priority] asc",$status);

      $result=DB_query($SQL,$db);
     while($myrow = DB_fetch_array($result)){
         $GetActivities[]=$myrow;
     }

 return  $GetActivities;
}

function ShowHtml($value){
    Global $db,$PriorityArray,$wwwusers;
        
    if($value["Future"]>0){
        $style='style="background-color:pink"';
    }
    if($value["Future"]==0){
        $style='style="background-color:white;"';
    }
    if($value["Future"]<0){
        $style='style="background-color:lightcyan;"';
    }
      
    $sp =(int) $value["Priority"];
    $Priority=$PriorityArray[$sp];

    $sp = trim($value["TaskOwner"]);
    $TaskOwner=$wwwusers[$sp];
       
        echo sprintf('<tr %s>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '<td><div>%s</div></td>'
                  . '</tr>',$style,$TaskOwner,$value["Taskname"],
                  ConvertSQLDate($value["datedue"]),$Priority,$value["taskdetails"]);
}

?>