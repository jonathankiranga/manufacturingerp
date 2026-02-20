<?php
$SQLArray=array();
  
    $SQLArray[]= sprintf("UPDATE [ProductionMaster] SET [DateTestended]='%s'  %s  WHERE [Batchno]= '%s' ",
    FormatDateForSQL($_POST['Endeddate']), (($_POST['update']=='Finished All Tests')?",[testreport]='Review'":''), $_POST['documentno']);

    foreach ($_POST['results'] as $Index => $value) {
      $SampleID = $_POST['SampleID'][$Index];
      $ParameterID = $_POST['ParameterID'][$Index];
      $Method = $_POST['Method'][$Index];
      $Limits_min= $_POST['Limits_min'][$Index];    
      $Limits_max= $_POST['Limits_max'][$Index];   
     
      IF(mb_strlen($value)>0){
      $SQLArray[] = sprintf("DELETE from [LabPostingDetail] "
              . " WHERE [SampleID]='%s' and [ParameterID]='%s' and [DocumentNo]='%s' and SampleTypeID='%s'",$SampleID,$ParameterID, $_POST['documentno'],$SampleID);
  
      $SQLArray[] = sprintf("INSERT INTO [LabPostingDetail] ([Results],[lastuserid],
          [lastdatetime],[Method],[Limits_min],[Limits_max],[SampleID],[ParameterID],[DocumentNo],SampleTypeID) 
              values ('%s','%s','%s','%s','%s','%s','%s','%s','%s','%s')",
              $value,$_SESSION['UserID'],Date('Y-m-d H:i:s'),$Method,$Limits_min,
              $Limits_max,$SampleID,$ParameterID, $_POST['documentno'],$SampleID);
    }
    
}
    


DB_Txn_Begin($db);
foreach ($SQLArray as $sql) {
  DB_query($sql, $db);
}

If(DB_error_no($db)==0){
    DB_Txn_Commit($db);
    prnMsg('Lab results have been saves sucessfully','info');
    Unset($_POST);
}else{
    DB_Txn_Rollback($db);
}


?>