<?php

$_SESSION['DocumentNo']=$_POST['documentno'] ;



if($_SESSION['DocumentPosted']==true){
    prnMsg('You cannot repost this invoice','warn');
}else{
       
    $ResultIndex = DB_query('Select getdate() as date ',$db);
    $rowdate = DB_fetch_row($ResultIndex);
    $_POST['posingdate'] = ConvertSQLDate($rowdate[0]);
    
    $sqldebtors=DB_query("SELECT [itemcode],[creditlimit],[customer],[phone],
    [email],[city],[country],[curr_cod],[customerposting],[salesman],[VATinclusive]
    FROM [debtors] join postinggroups on code=[customerposting] 
    where itemcode='".$_POST['CustomerID']."'", $db);
    
    $debtorsrow      = DB_fetch_row($sqldebtors);
    $customerposting = $debtorsrow[8];
    $salesrep        = $debtorsrow[9];
    $VATinclusive    = $debtorsrow[10];
    $PeriodNo        = GetPeriod($_POST['date'],$db,false);
    $TransBuffer     = DB_Txn_Begin($db);
    $journal         = GetNextTransNo(0,$db);
    if(($_SESSION['ManualNumber']==0)){
         $INVOICENO= GetNextTransNo(10,$db);
    }else{
        $INVOICENO = $_POST['manualdocumentno'];
    }
    $sql = array();
    
    $sql[] ="insert into [SalesHeader] 
      ([documenttype]
      ,[documentno]
      ,[docdate]
       ".(Is_date($_POST['Salesoderdate'])?',[oderdate]':'')."
       ".(Is_date($_POST['datedue'])?',[duedate]':'')."
      ,[postingdate]
      ,[customercode]
      ,[customername]
      ,[yourreference]
      ,[externaldocumentno]
      ,[postinggroup]
      ,[currencycode]
      ,[salespersoncode]
      ,[printed]
      ,[released]
      ,[status]
      ,[userid]
      ,[period]
      ,[vatinclusive]
      ,[journal]
      ,packagescharge
      ,[picture]) 
      (select 10
      ,'".$INVOICENO."'
      ,'".FormatDateForSQL($_POST['date'])."'
       ".(Is_date($_POST['Salesoderdate'])?",'".FormatDateForSQL($_POST['Salesoderdate'])."'":'')."
       ".(Is_date($_POST['datedue'])?",'".FormatDateForSQL($_POST['datedue'])."'":'') ."
       ,'".FormatDateForSQL($_POST['posingdate'])."'
       ,'".$_POST['CustomerID']."'
       ,'".$_POST['CustomerName']."'
      ,'".$_POST['reference']."'
      ,'".$_SESSION['DocumentNo']."'
      ,'".$customerposting."'
      ,'".$_POST['currencycode']."'
      ,'".$_POST['salespersoncode']."'
      ,0
      ,0
      ,1
      ,'".$_SESSION['UserID']."'
      ,'".$PeriodNo."'
      ,'".$VATinclusive ."'
      ,'".$journal ."'
      ,'". $_POST['packagescharge']."'
      ,[picture]
      from [SalesHeader] 
      where documentno='".$_SESSION['DocumentNo']."') ";
   
    foreach ($_POST['subunits'] as $key => $value) {
       
        if($value>0){
            $linepackage = $_POST['linepackage'][$key] ; 
            $Shipping = $_POST['Shipping'][$key] ; 
            $netamount = $_POST['netamount'][$key];
            $vatamount = $_POST['vatamount'][$key];
            $grossamount = $_POST['grossamount'][$key];
            $totalemptycost =  $_POST['totalemptycost'][$key];
     
           $sql[] =  sprintf("INSERT INTO [SalesLine]
           ([documenttype],[docdate],[documentno],[code],[description],[unitofmeasure],[Quantity],[UnitPrice],
           [vatamount],[invoiceamount],[vatrate],[inclusive],[locationcode],[partperunit],[shipping],[packagescharge],
           [PriceInPricelist],[totalchargedcontainers],[containercode]) 
           (select '%s','%s','%s',[code],[description],[unitofmeasure],[Qunatity_delivered],
           [UnitPrice] ,%f ,%f ,[vatrate],[inclusive],[locationcode],[partperunit],%f,%f,[PriceInPricelist] ,%f ,[containercode]
           from SalesLine where [entryno]='".$key."')" ,10 ,FormatDateForSQL($_POST['date'])
           ,$INVOICENO ,$vatamount ,$grossamount ,$Shipping, $linepackage,$totalemptycost );
         }
  
    }
        
    $sql[]=PostGL($PeriodNo,$journal,$INVOICENO);
    $sql[]=PostDebtors($PeriodNo,$journal,$INVOICENO);
    $sql[]=VATPostGL($PeriodNo,$journal,$INVOICENO);
    $sql[]=CustomerStatemet($journal,$INVOICENO);
    
    foreach ($sql as $value) {
        $ResultIndex=DB_query($value,$db);
    }
    
    
    if(DB_error_no($db)>0){
        DB_Txn_Rollback($db);
        prnMsg('There was an Error Posting the invoice, Contact your administrator','warn');
    }else{
        DB_query("Update SalesHeader set [released]=2 where documentno='".$_SESSION['DocumentNo']."'",$db);
        DB_Txn_Commit($db);
        
         prnMsg('This Sale has successfully been posted in the General Ledger');
  
         echo sprintf('<p class="page_title_text"><a id="'.$INVOICENO.'" href="%s?No=%s" >
         <img src="'.$RootPath.'/css/'.$Theme.'/images/pdf.png" title="' . _('Print Invoice') . '" alt="" />%s</a></p>',
        'PDFPrintSalesInvoice.php',$INVOICENO, _('Print Invoice ').$INVOICENO);
    
        echo sprintf('<script type="text/javascript">ForcePDFPrint(\'%s\');</script>',$INVOICENO);
        
         
        $_SESSION['DocumentPosted']=true;
        unset($_SESSION['DocumentNo']);
        unset($_POST);
    }
    
}

function PostDebtors($period,$journal,$doc){
   $SQL= sprintf("Insert into [debtorsledger]
       ([date]
      ,[details]
      ,[flag]
      ,[invref]
      ,[acctfolio]
      ,[amount]
      ,[pamount]
      ,[curr_cod]
      ,[i_n_t]
      ,[journal]
      ,[typ]
      ,[vatamt]
      ,[systypes_1]
      ,[ledger]
      ,period)
  (SELECT 
      SalesHeader.[docdate]
      ,rtrim(SalesLine.[description])  as narration,'I'
      ,SalesHeader.[documentno]
      ,SalesHeader.[customercode]
      ,SUM(SalesLine.invoiceamount) as Value
      ,0.00
      ,SalesHeader.[currencycode]
      ,'I'
      ,'%s'
      ,'r'
      ,SUM(SalesLine.vatamount) as VAT
      ,SalesHeader.[documenttype]
      ,SalesHeader.[postinggroup]
      ,'%s'
  FROM [SalesHeader] join SalesLine 
        on SalesHeader.documentno=SalesLine.documentno 
        and SalesHeader.documenttype=SalesLine.documenttype 
        where SalesHeader.documentno='%s' 
        group by
       SalesHeader.[documenttype]
      ,SalesHeader.[documentno]
      ,SalesHeader.[docdate]
      ,SalesHeader.[customercode]
      ,SalesHeader.[externaldocumentno]
      ,SalesHeader.[postinggroup]
      ,SalesHeader.[currencycode]
      ,SalesLine.[Quantity]
      ,SalesLine.[description]
      ,SalesLine.[code])", $journal,$period,$doc);
   
   return $SQL;
}

function PostGL($period,$journal,$doc){
    $sql=SPRINTF("Insert into [Generalledger]
      ([journalno]
      ,[Docdate]
      ,[period]
      ,[DocumentNo]
      ,[DocumentType]
      ,[accountcode]
      ,[balaccountcode]
      ,[VATaccountcode]
      ,[amount]
      ,[VATamount]
      ,[currencycode]
      ,[ExchangeRate]
      ,[cutomercode]
      ,[narration]
      ,[dimension]
      ,[dimension2])
      (select
       '%s'
      ,SalesHeader.[docdate]
      ,'%s'
      ,SalesHeader.documentno
      ,SalesHeader.documenttype
      ,[postinggroups].[debtorsaccount]
      ,[inventorypostinggroup].[defaultgl_sales]
      ,[inventorypostinggroup].[defaultgl_vat]
      ,SalesLine.invoiceamount
      ,isnull(SalesLine.vatamount,0) as vatamount
      ,SalesHeader.[currencycode]
      ,[currencies].[rate]
      ,SalesHeader.[customercode]
      ,(rtrim(SalesLine.[description]))  as narration
      ,'".$_POST['DimensionOne']."' 
      ,'".$_POST['DimensionTwo']."'
      from SalesHeader join [postinggroups] on [SalesHeader].[postinggroup]=[postinggroups].[code]
      join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
      join [stockmaster] on [SalesLine].[code]=[stockmaster].itemcode
      join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
      join [currencies] on [SalesHeader].[currencycode]=[currencies].[currabrev]
      where SalesHeader.documentno='%s'
      )",$journal,$period,$doc);
    
    return $sql;
    
}

function VATPostGL($period,$journal,$doc){
    $sql=SPRINTF("Insert into [Generalledger]
      ([journalno]
      ,[Docdate]
      ,[period]
      ,[DocumentNo]
      ,[DocumentType]
      ,[accountcode]
      ,[balaccountcode]
      ,[VATaccountcode]
      ,[amount]
      ,[VATamount]
      ,[currencycode]
      ,[ExchangeRate]
      ,[cutomercode]
      ,[narration])
      (select
       '%s'
      ,SalesHeader.[docdate]
      ,'%s'
      ,SalesHeader.documentno
      ,SalesHeader.documenttype
      ,[inventorypostinggroup].[defaultgl_sales]
      ,[inventorypostinggroup].[defaultgl_vat]
      ,''
      ,SalesLine.vatamount
      ,0
      ,SalesHeader.[currencycode]
      ,[currencies].[rate]
      ,SalesHeader.[customercode]
      ,(rtrim(SalesLine.[description]))  as narration
      from SalesHeader join [postinggroups] on [SalesHeader].[postinggroup]=[postinggroups].[code]
      join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
      join [stockmaster] on [SalesLine].[code]=[stockmaster].itemcode
      join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
      join [currencies] on [SalesHeader].[currencycode]=[currencies].[currabrev]
      where SalesHeader.documentno='%s'
      )",$journal,$period,$doc);
    
    return $sql;
    
}

Function CustomerStatemet($journal,$doc){
    $sql=sprintf("INSERT INTO [CustomerStatement]
           ([Date]
           ,[Documentno]
           ,[Documenttype]
           ,[Accountno]
           ,[Grossamount]
           ,[JournalNo]
           ,[Dimension_One]
           ,[Dimension_Two]
           ,[Currency])
      (select
              SalesHeader.[docdate]
             ,SalesHeader.documentno
             ,SalesHeader.documenttype
             ,SalesHeader.customercode
             ,sum(SalesLine.invoiceamount)
             ,'%s'
             ,'".$_POST['DimensionOne']."'
             ,'".$_POST['DimensionTwo']."' 
             ,SalesHeader.[currencycode]    
             from SalesHeader
             join SalesLine 
          on [SalesLine].[documentno] = [SalesHeader].[documentno] 
           where SalesHeader.documentno='%s' 
           Group by 
             SalesHeader.[docdate]
             ,SalesHeader.documentno
             ,SalesHeader.documenttype
             ,SalesHeader.customercode
             ,SalesHeader.[currencycode]
             ) ",
            $journal,$doc);
    
    return $sql;
}

?>