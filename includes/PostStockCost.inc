<?php

$_SESSION['PostRow'] = array();
$_SESSION['records'] = array();
    
function dropprocedure($procedurename){
global $db;
    $SQL="IF  EXISTS (SELECT * FROM sys.objects WHERE "
            . " object_id = OBJECT_ID(N'[dbo].[".$procedurename."]') 
                AND type in (N'P', N'PC'))
          DROP PROCEDURE [dbo].[".$procedurename."]";
    $r=DB_System($SQL,$db);

}

class ExchangeRates {
    var $FunctionalCurrency;
    Var $FromRate;
    var $Torate;
        
    function __construct() {
       $this->FunctionalCurrency = $_SESSION['CompanyRecord']['currencydefault'];
     }
    
    function get($fromcurrency){
        $this->FromRate =$this->ExtractRate($fromcurrency);
        $this->Torate =$this->ExtractRate($this->FunctionalCurrency);
        return ($this->FromRate/$this->Torate);
    }
    
    function ExtractRate($currbrieve){
        global $db;
        
        $SQLOldRate = "SELECT rate FROM currencies  WHERE currabrev = '" .$currbrieve. "'";
        $ResultOldRate = DB_query($SQLOldRate, $db);
        $myrow = DB_fetch_row($ResultOldRate);
        $OldRate = $myrow[0];
                
        return $OldRate;
    }
}

Function AverageCost($itemcode){
      Global $db; 
   
   $SQL ="Select TOP 1
        ([PurchaseLine].[UnitPrice] * currencies.rate) as UnitPrice,
        [PurchaseHeader].[documentno],
        [PurchaseHeader].[docdate], 
        [PurchaseHeader].[currencycode],
        [PurchaseLine].[code], 
        [PurchaseLine].[PartPerUnit]
     from PurchaseHeader 
     join PurchaseLine on [PurchaseLine].[documentno] = [PurchaseHeader].[documentno]
     join currencies on currencies.currabrev = [PurchaseHeader].[currencycode]
     where [PurchaseLine].[code]='". trim($itemcode) ."' 
     order by [PurchaseHeader].[docdate] DESC";
   
  $ResultIndex=DB_query($SQL, $db);
  $rows = DB_fetch_row($ResultIndex);
  return ($rows[5]==1)?($rows[0]/$rows[5]):$rows[0];
}

function FindJournal($invref){
    Global $db;
    $SQL="SELECT [journal] FROM [stockledger] where invref='".trim($invref)."'";
    $ResultIndex=DB_query($SQL, $db);
   $rows = DB_fetch_row($ResultIndex);
   
    return $rows[0];
}

function PostDebitRegister($Quantity,$key,$journal,$PeriodNo){
  global $db;
  
    $SQL ="select  
        PurchaseHeader.[docdate], 
        [PurchaseHeader].[currencycode],
        [PurchaseLine].[code], 
        [PurchaseLine].[UnitPrice],
        [PurchaseLine].[PartPerUnit]
     from PurchaseHeader 
     join PurchaseLine on [PurchaseLine].[documentno] = [PurchaseHeader].[documentno] 
     where [PurchaseLine].[entryno]=".$key;
    
    $RESULTS = DB_query($SQL,$db);
    $rows = DB_fetch_row($RESULTS);
    
    $ExchRates = New ExchangeRates();
    $R = $ExchRates->get($rows[1]); 
    $Price = $rows[3];
    $cost = ($Price  * $R) ;
    $itemcode = $rows[2];
    $PartPerUnit = $rows[4];
    DB_free_result($RESULTS);
    
    $SQL ="Select [averagestock] from stockmaster where itemcode='".$itemcode."'";
    $r = DB_query($SQL,$db);
    $row = DB_fetch_row($ResultIndex);
    $averagestock = $row[0];
    $quantity= (($PartPerUnit>1)?($PartPerUnit * $Quantity):$Quantity);
   
	 
    $SQL= "Insert into [Generalledger]
    ([journalno] ,[Docdate],[period],[DocumentNo],[DocumentType] ,[accountcode]
    ,[balaccountcode],[VATaccountcode],[amount],[VATamount],[currencycode],[ExchangeRate],[cutomercode],[narration])
    (select '".$journal."',PurchaseHeader.[docdate],'".$PeriodNo."',PurchaseHeader.documentno
     ,PurchaseHeader.documenttype,[inventorypostinggroup].[stockvariance] ,[inventorypostinggroup].[balancesheet] ,''
     ,(".$averagestock * $quantity." * [PurchaseLine].[PartPerUnit]),0,'".$_SESSION['CompanyRecord']['currencydefault']."',1,PurchaseHeader.[vendorcode]
     ,rtrim(PurchaseLine.[description]) +' X ".$quantity."' +PurchaseLine.[unitofmeasure] as narration
     from PurchaseHeader 
     join PurchaseLine on [PurchaseLine].[documentno] = [PurchaseHeader].[documentno] 
     join [stockmaster] on [PurchaseLine].[code]=[stockmaster].itemcode
     join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
     join [currencies] on [PurchaseHeader].[currencycode]=[currencies].[currabrev]
     where [PurchaseLine].[entryno]=".$key.")";

     $r=DB_System($SQL,$db);
     
     GetStockRegister();
}

Function GetConsumables($journal,$PeriodNo,$salesoderno,$stcode,$qty,$key){
 global $db;
 
 $SQL ="select  
        [SalesLine].PartPerUnit,
        [SalesHeader].[documentno] 
     from SalesHeader
     join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
     where [SalesLine].[entryno]=".$key;
    
    $RESULTS = DB_query($SQL,$db);
    $rows = DB_fetch_row($RESULTS);
    $PartPerUnit = trim($rows[0]);
    DB_free_result($RESULTS);
    
     
    $SQL ="select 
        StockRegister.[cost],
        StockRegister.[StockBalance],
        StockRegister.[rowid] 
        from [StockRegister] 
        where [StockRegister].[itemcode]='".$containercode."' and [StockBalance]>0 
        order by StockRegister.rowid asc";
    $RESULTS = DB_query($SQL,$db);
    while($rows = DB_fetch_row($RESULTS)){
        $StockRegister[]=$rows;
    }
     DB_free_result($RESULTS);


     if(is_array($StockRegister)){
         foreach ($StockRegister as $key => $Register) {
             $quantity = (($PartPerUnit>1)?($PartPerUnit * $qty):$qty);
             if($quantity>$Register['stockbalance']) {
                   DB_System(sprintf("Update [StockRegister] set [StockOut]=([StockOut] + %f) where rowid='%s'",
                   $Register['stockbalance'], $Register['rowid']),$db);
                   $usequantity = $Register['stockbalance'];
                   $quantity    = ($quantity - $usequantity);
                } else{
                     DB_System(sprintf("Update [StockRegister] set [StockOut]=([StockOut] + %f) where rowid='%s'",
                     $quantity,$Register['rowid']),$db);
                     $usequantity = $quantity;
                     $quantity    = 0;
                }
            
            $usequantity = (($PartPerUnit==1)?($usequantity / $PartPerUnit):$usequantity);
            
            $usecost =(($PartPerUnit>1)?($Register['cost'] * $PartPerUnit):$Register['cost']);
            
            if($usequantity>0){      
               
              $SQL="Insert into [Generalledger] ([journalno],[Docdate],[period],[DocumentNo],[DocumentType],[accountcode],[balaccountcode]
                 ,[VATaccountcode] ,[amount],[VATamount],[currencycode],[ExchangeRate] ,[cutomercode],[narration])
                  (select '".$journal."',SalesHeader.[docdate],'".$PeriodNo."' , SalesHeader.documentno,SalesHeader.documenttype,
                 [inventorypostinggroup].[productionexpense],[inventorypostinggroup].[balancesheet],''
                 ,".$usecost * $usequantity.", 0 ,'".$_SESSION['CompanyRecord']['currencydefault']."', 1 ,
                 SalesHeader.[customercode] ,
                 rtrim(SalesLine.[description])+'@ ".$usecost." X ".$usequantity."' +[SalesLine].[unitofmeasure] as narration
               from SalesHeader 
               join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
               join [stockmaster] on [SalesLine].[code]=[stockmaster].itemcode
               join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
               join [currencies] on [SalesHeader].[currencycode]=[currencies].[currabrev] where SalesHeader.documentno='".$salesoderno."' 
               and [SalesLine].[code]='".$stcode."' and [SalesLine].[entryno]='".$key."')";
              
            }
    
        }
     
    }
 $RESULTS = DB_System($SQL,$db); 
   GetStockRegister();
}

function PostStockRegister($Quantity,$key,$journal,$PeriodNo){
    global $db;
    
    $SQL ="Select  
        [PurchaseHeader].[documentno],
        [PurchaseHeader].[docdate], 
        [PurchaseHeader].[currencycode],
        [PurchaseLine].[code], 
        ([PurchaseLine].[UnitPrice] * currencies.rate) as UnitPrice,
        [PurchaseLine].[PartPerUnit],
        ([PurchaseLine].[invoiceamount] * currencies.rate) as invoiceamount,
        ([PurchaseHeader].[freight] * currencies.rate) as freight,
        [PurchaseLine].[Quantity],
        ([PurchaseLine].[PartPerUnit]*[PurchaseLine].[Quantity]) as Totunits,
        ISNULL([PurchaseLine].[discount],0) * currencies.rate as discount
     from PurchaseHeader 
     join PurchaseLine on [PurchaseLine].[documentno] = [PurchaseHeader].[documentno]
     join currencies on currencies.currabrev = [PurchaseHeader].[currencycode]
     and  [PurchaseLine].[entryno]=".$key;
    
        $RESULTS  = DB_query($SQL,$db);
        $rows     = DB_fetch_row($RESULTS);

        $GRN      = (string)trim($rows[0]);
        $Price    = (float)$rows[4]-(float)$rows[10];
        $itemcode = (string)trim($rows[3]);
        $partperunit   = (int)$rows[5];
        $freight       = (float)$rows[7];
        $Unitstodevide = (int)$rows[9]; //[PartPerUnit]*[Quantity]

        DB_free_result($RESULTS);

        $AMOUNT   = $Price * $Quantity   ;
       If(mb_strlen($itemcode)>0){

            if($freight>0){
                $freight=($freight/$Unitstodevide);
                $Price += $freight; 
            }

            $SQL=sprintf("INSERT INTO [StockRegister] ([itemcode],[StockIn],[cost],[StockOut],[journal],[GRN],PartPerUnit) 
                VALUES  ('%s','%s','%s',0,'%s','%s','%s')",$itemcode,($Quantity*$partperunit),($Price),$journal,$GRN,$partperunit);
             $RESULTS  = DB_System($SQL,$db);

            $SQL= " Insert into [Generalledger] ([journalno],[Docdate],[period],[DocumentNo],[DocumentType] ,[accountcode]
                    ,[balaccountcode],[VATaccountcode],[amount],[VATamount],[currencycode],[ExchangeRate],[cutomercode],[narration])
                    (select  '".$journal."',PurchaseHeader.[docdate],'".$PeriodNo."',PurchaseHeader.documentno,
                    PurchaseHeader.documenttype,[inventorypostinggroup].[balancesheet] ,[inventorypostinggroup].[stockvariance] ,''
                     ,(".$AMOUNT."),0,'".$_SESSION['CompanyRecord']['currencydefault']."',1,PurchaseHeader.[vendorcode]
                     ,rtrim(PurchaseLine.[description]) +' X ".$Quantity."' +PurchaseLine.[unitofmeasure]  as narration
                     from PurchaseHeader 
                     join PurchaseLine on [PurchaseLine].[documentno] = [PurchaseHeader].[documentno] 
                     join [stockmaster] on [PurchaseLine].[code]=[stockmaster].itemcode
                     join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
                     join [currencies] on [PurchaseHeader].[currencycode]=[currencies].[currabrev]
                     where [PurchaseLine].[entryno]='".$key."')";
            $RESULTS  = DB_System($SQL,$db);
    }
            
     GetStockRegister($itemcode);
}

Function GetStockRegister($itemcode){
global $db;
              
$SQL=Sprintf("Update [stockmaster] set 
[averagestock]=(select top 1 cost from [StockRegister] where [itemcode]='%s' and ([StockBalance]>0) 
order by rowid asc) where itemcode='%s'",$itemcode,$itemcode);
 $RESULTS  = DB_System($SQL,$db);
        
}

Function GetFIFO($journal,$PeriodNo,$salesoderno,$stcode,$qty,$key){
    global $db;
    
    
 $SQL ="select  
        [SalesLine].[unitofmeasure],
        [SalesLine].PartPerUnit,
        [SalesHeader].[documentno] 
     from SalesHeader
     join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
     where [SalesLine].[entryno]=".$key;
    
    $RESULTS = DB_query($SQL,$db);
    $rows = DB_fetch_row($RESULTS);
    $unitofmeasure = trim($rows[0]);
    $PartPerUnit = trim($rows[1]);
    DB_free_result($RESULTS);
    
    
    $SQL ="select 
        StockRegister.[cost],
        StockRegister.[StockBalance],
        StockRegister.[rowid] 
        from [StockRegister] 
        where [StockRegister].[itemcode]='".$stcode."' and [StockBalance]>0 
         order by StockRegister.rowid asc";
    $RESULTS = DB_query($SQL,$db);
    while($rows = DB_fetch_array($RESULTS)){
        $StockRegister[]=$rows;
    }
     DB_free_result($RESULTS);
    
     if(is_array($StockRegister)){
         foreach ($StockRegister as $key => $Register) {
              $quantity = (($PartPerUnit>1)?($PartPerUnit * $qty):$qty);
              if($quantity>$Register['stockbalance']) {
               
                   DB_System(sprintf("Update [StockRegister] set [StockOut]=([StockOut] + %f) where rowid='%s'",
                   $Register['stockbalance'], $Register['rowid']),$db);
                   $usequantity = $Register['stockbalance'];
                   $quantity    = ($quantity-$usequantity);
            
                } else{
                     DB_System(sprintf("Update [StockRegister] set [StockOut]=([StockOut] + %f) where rowid='%s'",
                     $quantity, $Register['rowid']),$db);
                     $usequantity = $quantity;
                     $quantity    = 0;
                }
            
            $usequantity = (($PartPerUnit>1)?($usequantity / $PartPerUnit):$usequantity);
            $usecost = (($PartPerUnit>1)?($Register['cost'] * $PartPerUnit):$Register['cost']);
            if($usequantity>0){ 

               $SQL= "Insert into [Generalledger] ([journalno],[Docdate],[period],[DocumentNo],[DocumentType],[accountcode],[balaccountcode]
                 ,[VATaccountcode] ,[amount],[VATamount],[currencycode],[ExchangeRate] ,[cutomercode],[narration])
                  (select '".$journal."',
                    SalesHeader.[docdate],'".$PeriodNo."' ,
                    SalesHeader.documentno,SalesHeader.documenttype,
                    [inventorypostinggroup].[stockvariance],
                    [inventorypostinggroup].[balancesheet],''
                 ,(".$usecost * $usequantity."), 0 ,'".$_SESSION['CompanyRecord']['currencydefault']."', 1 ,
                 SalesHeader.[customercode] ,(rtrim(SalesLine.[description])+'@".$usecost."X".$usequantity." ".$unitofmeasure."')  as narration
               from SalesHeader 
               join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
               join [stockmaster] on [SalesLine].[code]=[stockmaster].itemcode
               join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
               join [currencies] on [SalesHeader].[currencycode]=[currencies].[currabrev]
               where SalesHeader.documentno='".$salesoderno."' and [SalesLine].[code]='".$stcode."' and [SalesLine].[entryno]='".$key."')";
               $RESULTS = DB_System($SQL,$db);
            }
      
         }
     }
     
      GetStockRegister($stcode);
}

Function GetEmptiesFIFO($journal,$PeriodNo,$salesoderno,$stcode,$qty,$key){
    global $db;
      
 $SQL ="select  
        [SalesLine].[containercode],
        [SalesLine].[unitofmeasure],
        [SalesLine].PartPerUnit,
        [SalesHeader].[documentno] 
     from SalesHeader
     join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
     where [SalesLine].[entryno]=".$key;
    
    $RESULTS = DB_query($SQL,$db);
    $rows = DB_fetch_row($RESULTS);
    $containercode = trim($rows[0]);
    $unitofmeasure = trim($rows[1]);
    $PartPerUnit   = trim($rows[2]);
    DB_free_result($RESULTS);
    
    
    $SQL ="select 
        StockRegister.[cost],
        StockRegister.[StockBalance],
        StockRegister.[rowid] 
        from [StockRegister] 
        where [StockRegister].[itemcode]='".$containercode."' and [StockBalance]>0 
        order by StockRegister.rowid asc";
    $RESULTS = DB_query($SQL,$db);
    while($rows = DB_fetch_row($RESULTS)){
        $StockRegister[]=$rows;
    }
     DB_free_result($RESULTS);
    
     if(is_array($StockRegister)){
         foreach ($StockRegister as $key => $Register) {
              $quantity = (($PartPerUnit>1)?($PartPerUnit * $qty):$qty);
              if($quantity>$Register['stockbalance']) {
               
                   DB_System(sprintf("Update [StockRegister] set [StockOut]=([StockOut] + %f) where rowid='%s'",
                   $Register['stockbalance'], $Register['rowid']),$db);
                   $usequantity = $Register['stockbalance'];
                   $quantity    = ($quantity-$usequantity);
            
                } else{
                     DB_System(sprintf("Update [StockRegister] set [StockOut]=([StockOut] + %f) where rowid='%s'",
                     $quantity, $Register['rowid']),$db);
                     $usequantity = $quantity;
                     $quantity    = 0;
                }
            
            $usequantity=(($PartPerUnit>1)?($usequantity / $PartPerUnit):$usequantity);
            $usecost=(($PartPerUnit>1)?($Register['cost'] * $PartPerUnit):$Register['cost']);
            if($usequantity>0){ 

               $SQL= "Insert into [Generalledger] ([journalno],[Docdate],[period],[DocumentNo],[DocumentType],[accountcode],[balaccountcode]
                 ,[VATaccountcode] ,[amount],[VATamount],[currencycode],[ExchangeRate] ,[cutomercode],[narration])
                  (select '".$journal."',
                    SalesHeader.[docdate],'".$PeriodNo."' ,
                    SalesHeader.documentno,SalesHeader.documenttype,
                    [inventorypostinggroup].[stockvariance],
                    [inventorypostinggroup].[balancesheet],''
                 ,(".$usecost * $usequantity."), 0 ,'".$_SESSION['CompanyRecord']['currencydefault']."', 1 ,
                 SalesHeader.[customercode] ,rtrim(SalesLine.[description])+'@".$usecost."X".$usequantity." ".$unitofmeasure."' as narration
               from SalesHeader 
               join SalesLine on [SalesLine].[documentno] = [SalesHeader].[documentno] 
               join [stockmaster] on [SalesLine].[containercode]=[stockmaster].itemcode
               join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
               join [currencies] on [SalesHeader].[currencycode]=[currencies].[currabrev] where SalesHeader.documentno='".$salesoderno."' 
               and [SalesLine].[containercode]='".$containercode."' and [SalesLine].[entryno]='".$key."' )";
               $RESULTS = DB_System($SQL,$db);
            }
      
         }
     }
     
      
}

Function GetFIFOissues($journal,$PeriodNo,$salesoderno,$stcode,$qty,$key=Array(),$DocumentType='40'){
 global $db;
 
 $SQL ="select 
        StockRegister.[cost],
        StockRegister.[StockBalance],
        StockRegister.[rowid] ,
        StockRegister.PartPerUnit
        from [StockRegister] 
        where [StockRegister].[itemcode]='".$stcode."' and [StockBalance]>0 
        order by StockRegister.rowid asc";
    $RESULTS = DB_query($SQL,$db);
    while($rows = DB_fetch_row($RESULTS)){
        $StockRegister[]=$rows;
    }
    
    
    if(is_array($StockRegister)){
         foreach ($StockRegister as $key => $Register) {
             
              $PartPerUnit = $key['PartPerUnit'];
              $quantity = (($PartPerUnit>1)?($PartPerUnit* $qty):$qty);
              
              if($quantity>$Register['stockbalance']) {
                   DB_System(sprintf("Update [StockRegister] set [StockOut]=([StockOut] + %f) where rowid='%s'",$Register['stockbalance'], $Register['rowid']),$db);
                   $usequantity =(int) $Register['stockbalance'];
                   $quantity    =(int) ($quantity-$usequantity);
            
                } else{
                     DB_System(sprintf("Update [StockRegister] set [StockOut]=([StockOut] + %f) where rowid='%s'",$quantity, $Register['rowid']),$db);
                     $usequantity = $quantity;
                     $quantity    = 0;
                }
         
            
            $usecost = (($PartPerUnit>1)?($Register['cost'] * $PartPerUnit):$Register['cost']);
            if($usequantity>0){ 

            $SQL="Insert into [Generalledger] ([journalno],[Docdate],[period],[DocumentNo],[DocumentType],[accountcode],[balaccountcode]
            ,[VATaccountcode] ,[amount],[VATamount],[currencycode],[ExchangeRate] ,[cutomercode],[narration],[dimension]
            ,[dimension2]) (select '".$journal."','".$key['docdate']."','".$PeriodNo."' ,'".$salesoderno."','".$DocumentType."',
             [inventorypostinggroup].[productionexpense],[inventorypostinggroup].[balancesheet],'',
            ".($usecost * $usequantity).", 0 ,'".$_SESSION['CompanyRecord']['currencydefault']."', 1 ,
            '".$_POST['CustomerID']."','".$key['description']."@".$usecost."X".$usequantity.$key['unitofmeasure']."' as narration,
            '".trim($_POST['DimensionOne'])."', '".trim($_POST['DimensionTwo'])."'    
            from  [stockmaster]  join [inventorypostinggroup] on [inventorypostinggroup].[code]=[stockmaster].[postinggroup]
            where [stockmaster].[itemcode]='".$stcode."')";
                        
            $RESULTS = DB_System($SQL,$db);
            }
      
         }
     }
     
    
     DB_free_result($RESULTS);
       

}

?>