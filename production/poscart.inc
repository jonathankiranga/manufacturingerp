<?php

Class ProcessInformation{
    var $post;
    var $session=array();
    var $workorderclass;
    var $error1=0;
    var $error2=0;
    
    function get($post,$session,$workorderclass){
        $this->post=$post; 
        $this->session=$session;
        $this->workorderclass=$workorderclass;
    }
    
    function anythingwrong(){
        global $db;
        
        $return ='YES';
          // Production Tank
        $ResultIndex = DB_query("SELECT count(*) FROM [stockmaster] where itemcode='".trim($this->post['proitemcode'])."' ", $db);
        $rows=DB_fetch_row($ResultIndex);
        if($rows[0]==1){           
            
            if(mb_strstr($this->post['TankAllowedCapacity'],'No limit')==FALSE){
                if($this->post['TankAllowedCapacity'] < $this->post['TotalProduction']){
                   $this->error1++;
                 }
             }
        }else{
            $this->error1++;
        }
      
         foreach ($this->session as $key => $rowdata) {
            if($rowdata['stkbalance']<0){
                $this->error2++;
                
            }
         }
         
         
          if($this->error1>0){
              
            echo '<script type="text/javascript">
                function Checkproductionintegrity(){ 
                  alert(\'Please check tank capacity of the production tanks\'); 
                 } 
              Checkproductionintegrity(); 
            </script>';
            
             $this->savestockproduction();
             $return= 'NO';
             
         }elseif( $this->error2>0){
             
            echo '<script type="text/javascript">
                 function Checkproduction(){  
                    alert(\'Please check stock balances\');  
                 } 
                   Checkproduction(); 
                  </script>';
            
             $return= 'YES';
         }else{
             $this->savestockproduction();
             $return= 'NO';
         }
       
         return $return;
    }
    
    
    function savestockproduction(){
        global $db;
        
        
         $journal = GetNextTransNo(0,$db);
         $period = GetPeriod($this->post['date'],$db,true);
         $formatedDate = FormatDateForSQL($this->post['date']);
  
         $averagecost = $this->post['TotalProductionCost']/$this->post['TotalProduction'];
      // saving production items
         $sql[] = sprintf("INSERT INTO [ProductionMaster] ([Batchno],itemcode,production,[date],[userid],[bitumenph])"
                . " VALUES ('%s','%s',%f,'%s','%s','%s')",$this->post['documentno'],$this->post['proitemcode'],
                $this->post['TotalProduction'],$formatedDate,stripslashes($_SESSION['UserID']),$_POST['bitumenph']);
         
         if($this->post['productionTankStore']=="T"){
            $sql[] = sprintf("INSERT INTO [tanktrans] ([tankname],[units],[uom],[date],[batchno],[doctype],[itemcode])
                     VALUES  ('%s',%f ,'%s' ,'%s' ,'%s',28,'%s')", $this->post['ProdStore'],$this->post['TotalProduction'], '',$formatedDate,$this->post['documentno'],$this->post['proitemcode']);
         
         }elseif($this->post['productionTankStore']=="S"){
            $sql[] = "INSERT [stockledger] (fulqty,[date],[stname],[doctyp],[itemcode] ,[invref],[loosqty],[price],[store],[journal],[period],[PartPerUnit]) values "
                    . "(0,'".$formatedDate."' ,'".$this->post['Prostockname']."','28','".$this->post['proitemcode']."','". $this->post['documentno']."',".($this->post['TotalProduction']).",0,'".$this->post['ProdStore']."','". $journal ."','". $period ."',1)";
         }
         
        if($averagecost>0){ 
           $sql[] = "Update [stockmaster] set [averagestock]=".$averagecost." where itemcode='".$this->post['proitemcode']."'";

           $sql[] = Sprintf("INSERT INTO [StockRegister] ([itemcode],[StockIn],[cost],[journal],[GRN],[StockOut]) 
            VALUES  ('%s',%f,%f,'%s','%s',0)",$this->post['proitemcode'],$this->post['TotalProduction'],$averagecost, $journal,$this->post['documentno']);
        }
         
     
         //save raw materials
          foreach ($this->session as $key => $rowdata) {
                $storeTank = $this->post['location'][$key];
                $sql[] = sprintf("INSERT INTO [ProdcutionMasterLine] ([Batchno],[itemcode],[qty],[cost],volcorfactor,temperature)"
                        . "  VALUES ('%s','%s',%f,%f,%f,%f) ", $this->post['documentno'],$rowdata['itemcode'],$rowdata['quantity']
                        ,$rowdata['averagestock'], $rowdata['VCF'],$rowdata['TEMP']);
  
                
                if(mb_strstr($rowdata['itemcode'],'H2O')==FALSE){
                    if($rowdata['tankstore']=="T"){
                       $sql[] = sprintf("INSERT INTO [tanktrans] ([tankname],[units],[uom],[date],[batchno],[doctype],[itemcode])
                                VALUES ('%s',%f,'%s' ,'%s' ,'%s',28,'%s')",$storeTank,($rowdata['quantity'] *-1), 'loosqty',$formatedDate,$this->post['documentno'],$rowdata['itemcode']);

                    }elseif($rowdata['tankstore']=="S"){
                       $sql[] = "INSERT [stockledger] (fulqty,[date],[doctyp],[itemcode],[invref],[loosqty],[price],[store],[journal],[period],[PartPerUnit]) values"
                               . " (0,'".$formatedDate."','28','".$rowdata['itemcode']."','". $this->post['documentno']."',".($rowdata['quantity'] *-1).",0,'".$storeTank."','". $journal ."','". $period ."',1)";
                    }
              }
          }
          
          foreach ($sql as $value) {
              $ResultIndex = DB_query($value,$db);
          }
         
            echo '<script type="text/javascript">function Checkproductionintegrity(){ ';
            echo sprintf(" alert('Production %s has been posted. Now prepare Lab Test Report'); ",$this->post['documentno']);
            echo " }  Checkproductionintegrity();</script>";
          
    }
    
    
    
    
    
    
}

class NewAssemble{
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     var  $deleteID;
     var  $TotalProduction;
     var  $tankStore;
     var  $productionTankStore;
     var  $proitemcode;
     var  $work_orders;
     var  $Default=array();
     var  $check;
    
     Function MakeStoreTankForItem($ItemCode,$key){
        global $db;
        $line='';
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
           $this->tankStore="S";
            if(mb_stristr($ItemCode,'H2O')=='H2O'){
               $line .= '<option></option>';
             }else{
                $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
                while($rows= DB_fetch_array($REsults)){
                     $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$_POST['location'][$key]==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
                  }
           }
       }else{
           $this->tankStore="T";
           $ResultIndex=DB_query(sprintf("select tankname from [ProductionUnit] where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$_POST['location'][$key]==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
             }
       }
        return $line; 
    }
    
     Function MakeDefaultStoreTankForItem($ItemCode,$key){
        global $db;
        
         
        $line='';
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
            
                $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
                while($rows =DB_fetch_array($REsults)){
                     $this->Default[0]=array('code'=>trim($rows['code']),'Storename'=>$rows['Storename']);
                  }
       }else{
           
           $ResultIndex=DB_query(sprintf("select tankname from [ProductionUnit] where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                $this->Default[0]=array('code'=>trim($value['tankname']),'Storename'=>trim($value['tankname']));
           }
       }
       
    }
    
     Function ToStoreTankForItem($ItemCode){
        global $db;

        $line='';
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
            $this->productionTankStore="S";
            $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
            while($rows= DB_fetch_array($REsults)){
                 $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$_POST['ProdStore']==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
                  if($_POST['ProdStore']==trim($rows['code'])){
                      $this->check=trim($rows['code']);
                      
                  }
                 }
        } else {
            $this->productionTankStore="T";
            $ResultIndex=DB_query(sprintf("select tankname from [ProductionUnit] where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
              $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$_POST['ProdStore']==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
              if($_POST['ProdStore']==trim($value['tankname'])){
                  $this->check=trim($value['tankname']);
                  
              }
              }
       }
         return $line; 
    }
 
     function GetStockdetails($itemcode){
         global $db;
         
         if($itemcode=='H2O'){
             $this->stocklist['barcode'] = 'WATER';
             $this->stocklist['itemcode'] = 'H2O';
             $this->stocklist['stockname'] = 'WATER';
             $this->stocklist['averagestock'] = '1';
             $this->stocklist['production'] = "06";
          }else{
                 $ResultIndex = DB_query("SELECT 
                        [stockmaster].[barcode],
                        [stockmaster].[itemcode],
                        [stockmaster].[descrip] as [stockname],
                        [unit].[descrip] as [si],
                        [stockmaster].[averagestock],
                        [vatcategory].[vat],
                        [c].[itemcode] as [container],
                        [c].[descrip] as [packname],
                        [cv].[vat] as CVAT,
                        [stockmaster].[sellingprice],
                        [stockmaster].[production]
                  FROM [stockmaster] 
                  left join [stockmaster] c on [stockmaster].[container]=[c].[itemcode]
                  left join [unit] on [stockmaster].[units]=[unit].[code]
                  left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
                  left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
                  left join [inventorypostinggroup] ci on [c].[postinggroup]=[ci].[code]
                  left join [vatcategory] cv on [ci].[vatcategory]=[cv].[vatc] 
                  where [stockmaster].itemcode='".trim($itemcode)."' ", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode'] = $stocklist[0];
         $this->stocklist['itemcode'] = $stocklist[1];
         $this->stocklist['stockname'] = $stocklist[2];
         $this->stocklist['si'] = $stocklist[3];
         $this->stocklist['averagestock'] =  $stocklist[4];
         $this->stocklist['vat'] = $stocklist[5];
         $this->stocklist['container'] = $stocklist[6];
         $this->stocklist['packname'] = $stocklist[7];
         $this->stocklist['CVAT'] = $stocklist[8];
         $this->stocklist['sellingprice'] = $stocklist[9];
         $this->stocklist['production'] = $stocklist[10];
         }
         return $this->stocklist;
         
     }
    
     function GetStockProduction($itemcode){
         global $db;
         $stocklist=array();
          
         if($itemcode=='06'){
             $stocklist[] = array('barcode'=> 'WATER', 'itemcode' => 'H2O',  'stockname' => 'WATER','averagestock' => '1', 'production' => "06");
          }else{
                 $ResultIndex = DB_query("SELECT 
                        [stockmaster].[barcode],
                        [stockmaster].[itemcode],
                        [stockmaster].[descrip] as [stockname],
                        [unit].[descrip] as [si],
                        [stockmaster].[averagestock],
                        [vatcategory].[vat],
                        [c].[itemcode] as [container],
                        [c].[descrip] as [packname],
                        [cv].[vat] as CVAT,
                        [stockmaster].[sellingprice],
                        [stockmaster].[production]
                  FROM [stockmaster] 
                  left join [stockmaster] c on [stockmaster].[container]=[c].[itemcode]
                  left join [unit] on [stockmaster].[units]=[unit].[code]
                  left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
                  left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
                  left join [inventorypostinggroup] ci on [c].[postinggroup]=[ci].[code]
                  left join [vatcategory] cv on [ci].[vatcategory]=[cv].[vatc] 
                  where [stockmaster].production='".trim($itemcode)."' ", $db);
         
          while ($row=DB_fetch_array($ResultIndex)){
             $stocklist[]= $row;
         }
          
     }
     
      return $stocklist;
     
     }
     
     function loaddefaultitems($itemcode){
         global $db;
           
         if(empty($_SESSION['work_orders'])){
                $categorytoget = "SELECT category  FROM stockmaster WHERE itemcode='". $itemcode . "'";
                $ResultIndex = DB_query($categorytoget,$db);
                $stocklist=DB_fetch_row($ResultIndex);
                $selected_stock = trim($stocklist[0]);
	 
              $sqlUsed = "SELECT rawmatid FROM productionconfig WHERE categoryid='". trim($selected_stock) . "'";
              $UsedResult = DB_query($sqlUsed,$db);
              $TokensUsed = array();
              
            
                while ($myrow= DB_fetch_array($UsedResult)){
                   $TokensUsed[] =trim($myrow['rawmatid']);
                }
                
                 $stockmasterUsed = array();
                foreach ($TokensUsed as $key => $value) {
                   $VN = trim($value);
                   $stocklistarray = $this->GetStockProduction($VN);
                   foreach($stocklistarray as $key => $myrow) {
                       $stockmasterUsed[] = trim($myrow['itemcode']);
                    }
                }
                
               
                foreach ($stockmasterUsed as $key => $itemcode) {
                    $_POST['stockitemcode'] = $itemcode; $_POST['qty']=0; $_POST['packid']=''; $_POST['VCF']=1; $_POST['Temperature']=20;
                    
                    $StockList = $this->GetStockdetails($itemcode);
                    $id = trim($itemcode);
                    $_SESSION['work_orders'][$id] = array(
                             'line_no'=>$id,
                             'itemcode'=>$id,
                             'barcode'=>$StockList['barcode'],
                             'stockname'=>$StockList['stockname'],
                             'uom'=>1,
                             'VCF'=>1,
                             'TEMP'=>20,
                             'averagestock'=>$StockList['averagestock'],
                             'quantity'=>0,
                             'CostType'=>$StockList['production']);
                }
                
         }
     }
     
     function Getitems($itemcode,$qty,$uom,$VCF,$TEMP){
         $StockList = $this->GetStockdetails($itemcode);
          
         if(mb_strwidth($StockList['itemcode'])>0){
                $id = trim($StockList['itemcode']);
                $arrayi = $_SESSION['work_orders'][$id] ;
                
                  if($TEMP<15){
                   $VCF     =1 ;
                   $TEMP    =15 ;
                 }
                 
                 $_SESSION['work_orders'][$id] = array_replace_recursive($arrayi,
                     array(
                         'line_no'=>$id,
                         'itemcode'=>$id,
                         'barcode'=>$StockList['barcode'],
                         'stockname'=>$StockList['stockname'],
                         'uom'=>$uom,
                         'VCF'=>$VCF,
                         'TEMP'=>$TEMP,
                         'averagestock'=>$StockList['averagestock'],
                         'quantity'=>$qty)
                         );
         }
          
         if(is_array($_SESSION['work_orders'])){
           $this->GetSum();
            $this->showtable();
         }
        
     }
      
     function neworder(){
         $_SESSION['work_orders']=array();
         $this->TotalProduction=0;
     }
     
     function GetSum(){
         $TotalProduction=0;
          foreach ($_SESSION['work_orders'] as $lineno => $rowvalue) {
                $qty =(float) $rowvalue['quantity'] ;
                $VCF =(float) $rowvalue['VCF'] ;
                $costtype = trim($rowvalue['CostType']);
        // Do not calculate the quantity for consumable       
                if($costtype!='05'){
                   $TotalProduction +=($qty * $VCF);
                }
          }
         $this->TotalProduction = $TotalProduction;
         $this->proitemcode = $_POST['proitemcode'];
     }
     
    function RemoveOrder($itemcode){
            $this->deleteID=trim($itemcode);
            unset($_SESSION['work_orders'][$this->deleteID]);
            unset($_POST['stockitemcode']); 
            unset($_POST['qty']); 
            unset($_POST['sp']);
            unset($_POST['units']);
    }
    
    Function IsTankOrStore($ItemCode){
        global $db;
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
          $this->tankStore="S";
       }else{
          $this->tankStore="T";
       }
    }

    function tankresults($store,$item){
      global $db;
       $return ='';
       
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$item);
         $ResultIndex = DB_query($sql, $db);
        $Stores = DB_fetch_row($ResultIndex);
       if($Stores[0]>0){
            $sql=sprintf("SELECT [balance] FROM [ProductionUnit] where [tankname]='%s' and [itemcode]='%s'",$store,$item);
            $ResultIndex = DB_query($sql,$db);
            $row = DB_fetch_row($ResultIndex);
            $StockBalanceInloose = $row[0];
            $return= sprintf('%f',(int)$StockBalanceInloose);
         }else{
            $return= 'No limit';
         }
         return $return;
   }
   
    function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $totalProduction = 0;
        $percentTotal=0;
        $_SESSION["percentTotal"]=$percentTotal;
        $_SESSION["runningnettotal"]=$runningnettotal;
            
            foreach ($_SESSION['work_orders'] as $lineno => $rowvalue) {
                $line_no   = trim($rowvalue['line_no']);
                $itemcode  = $rowvalue['itemcode'];
                $VCF       =(float) $rowvalue['VCF'] ;
                $TEMP      =(string) $rowvalue['TEMP'] ;
                $costtype = trim($rowvalue['CostType']);
                  
              
                $this->IsTankOrStore($itemcode);
                $stocklist = $this->GetStockdetails($itemcode);
                
                $UOM = $rowvalue['uom']; 
                $package = 1; 
                $this->MakeDefaultStoreTankForItem($itemcode,$line_no);
                $location  = isset($_POST['location'][$line_no])? $_POST['location'][$line_no]:($this->Default[0]['code']);
                
                $averagestock  = (float) $rowvalue['averagestock'];
                $qty = (float) $rowvalue['quantity'] ;
                $baseamount  = (float) ($averagestock * $qty * $VCF );
                $runningnettotal += $baseamount;
                if($costtype!='05'){
                    $Production = $qty * $package * $VCF ;
                }else{
                    $Production = 0;
                }
                 $code    = trim($stocklist['itemcode']);
                 $ID      = trim($stocklist['itemcode']);
                 $BC      = trim($stocklist['barcode']);
                 $NAME    = trim($stocklist['stockname']);
                 $arrayi  = $_SESSION['work_orders'][$lineno];
                 if($this->TotalProduction>0){
                   $percent =(float) (100 * ($Production/$this->TotalProduction));
                 } else {
                   $percent=0;
                 }
                 $percentTotal += $percent;
                 $_SESSION["percentTotal"]=$percentTotal;
                 $_SESSION["runningnettotal"]=$runningnettotal;
                 
                 if(mb_stristr($itemcode,'H2O')=='H2O'){
                     $StockBalanceInloose=$qty;
                     $_SESSION['stockmasterp'][$code]['Balance']= $StockBalanceInloose;
                 }else{
                    if($this->tankStore=="T"){
                        $StockBalanceInloose =(int) getTankbalance($location,$code);
                        $_SESSION['stockmasterp'][$code]['Balance']= $StockBalanceInloose;
                    }elseif($this->tankStore=="S"){
                        $StockBalanceInloose=(int) getbalance($code,$location,'loosqty');
                        $_SESSION['stockmasterp'][$code]['Balance']=$StockBalanceInloose;
                    }
                      
                 }
                
                 
                $_SESSION['work_orders'][$lineno] = array_replace_recursive($arrayi,
                array(
                'TotalProduction'=>$this->TotalProduction,
                'tankstore'=>$this->tankStore,
                'netamount'=>$baseamount,'percent'=>$percent,
                'stkbalance'=>(($_SESSION['stockmasterp'][$code]['Balance'])-($qty * $VCF) ))
                );
            
                $this->htmltable .= sprintf('<tr onclick="ProductionGrid(\'%s\',\'%s\',\'%s\',\'%s\');">'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td class="number cell"><input type="hidden" name="quantity['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" />'.$qty.'</td>'
              . '<td><select name="location['.$line_no.']"  onchange="ReloadForm(prodform.refresh)">%s</select></td>'
              . '<td class="number cell"><input type="hidden" class="number cell" readonly="readonly" value="'.(($_SESSION['stockmasterp'][$code]['Balance'])-($qty * $VCF)).'"  size="8"/>'.(($_SESSION['stockmasterp'][$code]['Balance'])-($qty * $VCF)).'</td>'
              . '<td>%s</td>'
              . '<td class="number cell"><input type="hidden"   value="'.($averagestock).'"  size="8"/>'.($averagestock).'</td>'
              . '<td class="number cell"><input type="text" class="number" name="cents['.$line_no.']" value="'.$percent.'" readonly="readonly" size="8"/></td>'
              . '<td class="number cell">%s</td>'
              . '<td class="number cell">%s</td>'
              . '<td class="number cell"><input type="hidden" class="number" name="Production['.$line_no.']" value="'.number_format($Production,2).'" readonly="readonly" size="8"/>'.number_format($Production,2).'</td>'
              . '<td class="number cell"><input type="hidden" class="number" name="netamount['.$line_no.']" value="'.number_format($baseamount,2).'" readonly="readonly" size="8"/>'.number_format($baseamount,2).'</td>'
              . '</tr>',$ID,$BC,$NAME,$qty,
                trim($stocklist['barcode']),trim($stocklist['stockname']),
                $this->MakeStoreTankForItem($ID,$line_no),getGriduom($line_no,$UOM ),$VCF,$TEMP);
            }
            
    $this->ToStoreTankForItem($_POST['proitemcode']);
    $tankAllowedCapacity =(float) $this->tankresults($_POST['ProdStore'],$_POST['proitemcode']);
    $Reperent = 0;
   if($tankAllowedCapacity>0){
    $Reperent = (($this->TotalProduction/$tankAllowedCapacity)*100);
   }
   
   if(isset($this->check)){
    if(mb_stristr($_POST['ProdStore'],$this->check)==$this->check  
            and mb_strlen($this->check)>0 
            and mb_strlen($_POST['TotalProduction'])>0){
        $refreshPost="SaveProduction";
    }else{
         $refreshPost="refreshcheck";
    }
   }else{
         $refreshPost="refreshcheck";
   }
    $_SESSION['altername_SaveProduction'] = $refreshPost;
    $_SESSION['htmltable'] = $this->htmltable;
    }
    
    function RepopulateProduction($batchno){
        Global $db;
        
      $_POST['documentno'] = $batchno;
      $_SESSION['work_orders'] = array();
       
      $SQL = sprintf("SELECT [itemcode] FROM [ProductionMaster] where [Batchno]='%s'",$batchno);
      $ResultIndex=DB_query($SQL,$db);
      $row = DB_fetch_row($ResultIndex);
      $_POST['proitemcode'] = $row[0];
      $StockArray = $this->GetStockdetails($_POST['proitemcode']);
      $_POST['Prostockname'] = $StockArray['stockname'];
                 
      $SQL = sprintf("select Batchno,itemcode,qty,cost from ProdcutionMasterLine where Batchno='%s'",$batchno);
      $result=DB_query($SQL,$db);
      while($row= DB_fetch_array($result)){
         $StockList = $this->GetStockdetails($row['itemcode']);
          
         if(mb_strwidth($StockList['itemcode'])>0){
                $id = trim($StockList['itemcode']);
                $_SESSION['work_orders'][$id]= array('line_no'=>$id,
                         'itemcode'=>$id,
                         'barcode'=>$StockList['barcode'],
                         'stockname'=>$StockList['stockname'],
                         'uom'=>'1',
                         'averagestock'=>$StockList['averagestock'],
                         'quantity'=>$row['qty']);
         }
      }  
         $this->GetSum();
      
    }
    
}

class Assemble{
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     var  $deleteID;
     var  $TotalProduction;
     var  $tankStore;
     var  $productionTankStore;
     var  $proitemcode;
     var  $work_orders;
     var  $Default=array();
     var  $check;
    
     Function MakeStoreTankForItem($ItemCode,$key){
        global $db;
        $line='';
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
           $this->tankStore="S";
            if(mb_stristr($ItemCode,'H2O')=='H2O'){
               $line .= '<option></option>';
             }else{
                $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
                while($rows= DB_fetch_array($REsults)){
                     $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$_POST['location'][$key]==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
                  }
           }
       }else{
           $this->tankStore="T";
           $ResultIndex=DB_query(sprintf("select tankname from [ProductionUnit] where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$_POST['location'][$key]==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
             }
       }
        return $line; 
    }
    
     Function MakeDefaultStoreTankForItem($ItemCode,$key){
        global $db;
        
         
        $line='';
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
            
                $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
                while($rows =DB_fetch_array($REsults)){
                     $this->Default[0]=array('code'=>trim($rows['code']),'Storename'=>$rows['Storename']);
                  }
       }else{
           
           $ResultIndex=DB_query(sprintf("select tankname from [ProductionUnit] where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                $this->Default[0]=array('code'=>trim($value['tankname']),'Storename'=>trim($value['tankname']));
           }
       }
       
    }
    
     Function ToStoreTankForItem($ItemCode){
        global $db;

        $line='';
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
            $this->productionTankStore="S";
            $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
            while($rows= DB_fetch_array($REsults)){
                 $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$_POST['ProdStore']==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
                  if($_POST['ProdStore']==trim($rows['code'])){
                      $this->check=trim($rows['code']);
                      
                  }
                 }
        } else {
            $this->productionTankStore="T";
            $ResultIndex=DB_query(sprintf("select tankname from [ProductionUnit] where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
              $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$_POST['ProdStore']==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
              if($_POST['ProdStore']==trim($value['tankname'])){
                  $this->check=trim($value['tankname']);
                  
              }
              }
       }
         return $line; 
    }
 
     function GetStockdetails($itemcode){
         global $db;
         
         if($itemcode=='H2O'){
             $this->stocklist['barcode'] = 'WATER';
             $this->stocklist['itemcode'] = 'H2O';
             $this->stocklist['stockname'] = 'WATER';
             $this->stocklist['averagestock'] = '1';
          }else{
                 $ResultIndex = DB_query("SELECT 
                        [stockmaster].[barcode],
                        [stockmaster].[itemcode],
                        [stockmaster].[descrip] as [stockname],
                        [unit].[descrip] as [si],
                        [stockmaster].[averagestock],
                        [vatcategory].[vat],
                        [c].[itemcode] as [container],
                        [c].[descrip] as [packname],
                        [cv].[vat] as CVAT,
                        [stockmaster].[sellingprice]
                  FROM [stockmaster] 
                  left join [stockmaster] c on [stockmaster].[container]=[c].[itemcode]
                  left join [unit] on [stockmaster].[units]=[unit].[code]
                  left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
                  left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
                  left join [inventorypostinggroup] ci on [c].[postinggroup]=[ci].[code]
                  left join [vatcategory] cv on [ci].[vatcategory]=[cv].[vatc] 
                  where [stockmaster].itemcode='".trim($itemcode)."' ", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode'] = $stocklist[0];
         $this->stocklist['itemcode'] = $stocklist[1];
         $this->stocklist['stockname'] = $stocklist[2];
         $this->stocklist['si'] = $stocklist[3];
         $this->stocklist['averagestock'] =  $stocklist[4];
         $this->stocklist['vat'] = $stocklist[5];
         $this->stocklist['container'] = $stocklist[6];
         $this->stocklist['packname'] = $stocklist[7];
         $this->stocklist['CVAT'] = $stocklist[8];
         $this->stocklist['sellingprice'] = $stocklist[9];
         }
         return $this->stocklist;
         
     }
    
     function Getitems($itemcode,$qty,$uom){
         $StockList = $this->GetStockdetails($itemcode);
          
         if(mb_strwidth($StockList['itemcode'])>0){
                $id = trim($StockList['itemcode']);
                $_SESSION['work_orders'][$id]= array('line_no'=>$id,
                         'itemcode'=>$id,
                         'barcode'=>$StockList['barcode'],
                         'stockname'=>$StockList['stockname'],
                         'uom'=>$uom,
                         'averagestock'=>$StockList['averagestock'],
                         'quantity'=>$qty);
         }
          
         $this->GetSum();
         $this->showtable();
     }
      
     function neworder(){
         $_SESSION['work_orders']=array();
     }
     
     function GetSum(){
         $Total=0;
          foreach ($_SESSION['work_orders'] as $lineno => $rowvalue) {
                $qty=(float) $rowvalue['quantity'] ;
                $TotalProduction +=($qty);
          }
         $this->TotalProduction =$TotalProduction;
         $this->proitemcode = $_POST['proitemcode'];
     }
     
    function RemoveOrder($itemcode){
            $this->deleteID=trim($itemcode);
            unset($_SESSION['work_orders'][$this->deleteID]);
            unset($_POST['stockitemcode']); 
            unset($_POST['qty']); 
            unset($_POST['sp']);
            unset($_POST['units']);
    }
    
    Function IsTankOrStore($ItemCode){
        global $db;
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
          $this->tankStore="S";
       }else{
          $this->tankStore="T";
       }
    }

    function tankresults($store,$item){
      global $db;
       $return ='';
       
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$item);
         $ResultIndex = DB_query($sql, $db);
        $Stores = DB_fetch_row($ResultIndex);
       if($Stores[0]>0){
            $sql=sprintf("SELECT [balance] FROM [ProductionUnit] where [tankname]='%s' and [itemcode]='%s'",$store,$item);
            $ResultIndex = DB_query($sql,$db);
            $row = DB_fetch_row($ResultIndex);
            $StockBalanceInloose = $row[0];
            $return= sprintf('%f',(int)$StockBalanceInloose);
         }else{
            $return= 'No limit';
         }
         return $return;
   }
   
    function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $totalProduction = 0;
        $percentTotal=0;
            
            foreach ($_SESSION['work_orders'] as $lineno => $rowvalue) {
                $line_no   = trim($rowvalue['line_no']);
                $itemcode  = $rowvalue['itemcode'];
                $this->IsTankOrStore($itemcode);
                $stocklist = $this->GetStockdetails($itemcode);
                
                $UOM = $rowvalue['uom']; $package = 1; 
                 
                $this->MakeDefaultStoreTankForItem($itemcode,$line_no);
         
                $location  =isset($_POST['location'][$line_no])? $_POST['location'][$line_no]:($this->Default[0]['code']);
           
                $averagestock  = (float) $rowvalue['averagestock'];
                $qty =(float) $rowvalue['quantity'] ;
                $baseamount  =(float) ($averagestock * $qty );
                $runningnettotal += $baseamount;
                $Production = $qty * $package;
                
                 $code    = trim($stocklist['itemcode']);
                 $ID      = trim($stocklist['itemcode']);
                 $BC      = trim($stocklist['barcode']);
                 $NAME    = trim($stocklist['stockname']);
                 $arrayi  = $_SESSION['work_orders'][$lineno];
                 $percent = (100*($Production/$this->TotalProduction));
                 $percentTotal += $percent;
                 
                 if(mb_stristr($itemcode,'H2O')=='H2O'){
                     $StockBalanceInloose=$qty;
                     $_SESSION['stockmasterp'][$code]['Balance']= $StockBalanceInloose;
                 }else{
                    if($this->tankStore=="T"){
                        $StockBalanceInloose =(int) getTankbalance($location,$code);
                        $_SESSION['stockmasterp'][$code]['Balance']= $StockBalanceInloose;
                    }elseif($this->tankStore=="S"){
                        $StockBalanceInloose=(int) getbalance($code,$location,'loosqty');
                        $_SESSION['stockmasterp'][$code]['Balance']=$StockBalanceInloose;
                    }
                      
                 }
                
                 
                $_SESSION['work_orders'][$lineno] = array_replace_recursive($arrayi,
                array(
                'TotalProduction'=>$this->TotalProduction,
                'tankstore'=>$this->tankStore,
                'netamount'=>$baseamount,'percent'=>$percent,
                'stkbalance'=>(($_SESSION['stockmasterp'][$code]['Balance'])-$qty))
                );
            
                $this->htmltable .= sprintf('<tr onclick="ProductionGrid(\'%s\',\'%s\',\'%s\',\'%s\');">'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="hidden" name="quantity['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" />'
              . '<select name="location['.$line_no.']"  onchange="ReloadForm(prodform.refresh)">%s</select></td>'
              . '<td><input type="text" class="number cell" readonly="readonly" value="'.(($_SESSION['stockmasterp'][$code]['Balance'])-$qty).'"  size="8"/></td>'
              . '<td>%s</td>'
              . '<td><input type="text" class="number cell"  value="'.($averagestock).'"  size="8"/></td>'
              . '<td class="number cell"><input type="text" class="number" name="cents['.$line_no.']" value="'.$percent.'" readonly="readonly" size="8"/></td>'
              . '<td class="number cell"><input type="text" class="number" name="Production['.$line_no.']" value="'.number_format($Production,2).'" readonly="readonly" size="8"/></td>'
             . '<td class="number cell"><input type="text" class="number" name="netamount['.$line_no.']" value="'.number_format($baseamount,2).'" readonly="readonly" size="8"/></td>'
             . '</tr>',$ID,$BC,$NAME,$qty,trim($stocklist['barcode']),trim($stocklist['stockname']),$this->MakeStoreTankForItem($ID,$line_no),getGriduom($line_no,$UOM ));
            }
            
    $tankAllowedCapacity = $this->tankresults($_POST['ProdStore'],$_POST['proitemcode']);
    $Reperent = (($this->TotalProduction/$tankAllowedCapacity)*100);
   
   $this->htmltable .= '<tfoot><tr><td colspan="6"><input type="hidden" name="TotalProductionCost" value="'.$runningnettotal.'"/>'
    . '<input type="hidden" id="proitemcode" size="5" name="proitemcode" value="'.$_POST['proitemcode'].'" readonly="readonly"/>'
    . '<table class="table-bordered"><tr><td>Product To Produce</td><td colspan="4"><input type="button" id="searchProstock" value="Search stock"/>'
    . '<input type="text" style="width:70%;" id="Prostockname" name="Prostockname" value="'.$_POST['Prostockname'].'" readonly="readonly"/></td></tr>'
    . '<tr><td>Select Store/Tank</td><td><select name="ProdStore" onchange="ReloadForm(prodform.refresh)">'.$this->ToStoreTankForItem($_POST['proitemcode']).'</select></td>'
    . '<td>Tank Capacity in ltrs :</td><td><input type="text" class="number" name="TankAllowedCapacity" value="'.$tankAllowedCapacity.'" readonly="readonly" /></td></tr>'
    . '<tr><td colspan="5">Your not allowed to produce more than this limit . Now at '. number_format($Reperent,1).' %<input type="hidden" name="TotalProduction" value="'.$this->TotalProduction.'"/>'
    . '<input type="hidden" name="productionTankStore" value="'.$this->productionTankStore.'"/></td></tr>'
    . '</table></td>'. sprintf('<td class="number cell">%s</td><td class="number cell">%s</td>'
    . '<td class="number cell">%s</td></tr></tfoot>',$percentTotal,$this->TotalProduction,number_format($runningnettotal,2));
                  
           
    if(mb_stristr($_POST['ProdStore'],$this->check)==$this->check  
            and mb_strlen($this->check)>0 
            and mb_strlen($_POST['TotalProduction'])>0){
        $refreshPost="SaveProduction";
    }else{
         $refreshPost="refreshcheck";
    }

    $_SESSION['altername_SaveProduction'] = $refreshPost;
    $_SESSION['htmltable'] = $this->htmltable;
    }
    
    function RepopulateProduction($batchno){
        Global $db;
        
      $_POST['documentno'] = $batchno;
      $_SESSION['work_orders'] = array();
       
      $SQL = sprintf("SELECT [itemcode] FROM [ProductionMaster] where [Batchno]='%s'",$batchno);
      $ResultIndex=DB_query($SQL,$db);
      $row = DB_fetch_row($ResultIndex);
      $_POST['proitemcode'] = $row[0];
      $StockArray = $this->GetStockdetails($_POST['proitemcode']);
      $_POST['Prostockname'] = $StockArray['stockname'];
                 
      $SQL = sprintf("select Batchno,itemcode,qty,cost from ProdcutionMasterLine where Batchno='%s'",$batchno);
      $result=DB_query($SQL,$db);
      while($row= DB_fetch_array($result)){
         $StockList = $this->GetStockdetails($row['itemcode']);
          
         if(mb_strwidth($StockList['itemcode'])>0){
                $id = trim($StockList['itemcode']);
                $_SESSION['work_orders'][$id]= array('line_no'=>$id,
                         'itemcode'=>$id,
                         'barcode'=>$StockList['barcode'],
                         'stockname'=>$StockList['stockname'],
                         'uom'=>'1',
                         'averagestock'=>$StockList['averagestock'],
                         'quantity'=>$row['qty']);
         }
      }  
         $this->GetSum();
      
    }
    
}

class AssembleAdjust{
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
     var  $deleteID;
     var  $TotalProduction;
     var  $tankStore;
     var  $productionTankStore;
     var  $proitemcode;
     var  $work_orders;
     var  $Default=array();
     var  $check;
    
     Function MakeStoreTankForItem($ItemCode,$key){
        global $db;
        $line='';
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
           $this->tankStore="S";
            if(mb_stristr($ItemCode,'H2O')=='H2O'){
               $line .= '<option></option>';
             }else{
                $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
                while($rows= DB_fetch_array($REsults)){
                     $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$_POST['location'][$key]==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
                  }
           }
       }else{
           $this->tankStore="T";
           $ResultIndex=DB_query(sprintf("select tankname from [ProductionUnit] where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$_POST['location'][$key]==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
             }
       }
        return $line; 
    }
    
     Function MakeDefaultStoreTankForItem($ItemCode,$key){
        global $db;
        
         
        $line='';
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
            
                $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
                while($rows =DB_fetch_array($REsults)){
                     $this->Default[0]=array('code'=>trim($rows['code']),'Storename'=>$rows['Storename']);
                  }
       }else{
           
           $ResultIndex=DB_query(sprintf("select tankname from [ProductionUnit] where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
                $this->Default[0]=array('code'=>trim($value['tankname']),'Storename'=>trim($value['tankname']));
           }
       }
       
    }
    
     Function ToStoreTankForItem($ItemCode){
        global $db;

        $line='';
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
            $this->productionTankStore="S";
            $REsults=DB_query('SELECT [code],[Storename] FROM [Stores]', $db);
            while($rows= DB_fetch_array($REsults)){
                 $line .= sprintf('<option value="%s" %s >%s</option>',trim($rows['code']),$_POST['ProdStore']==trim($rows['code'])?'selected="selected"':'',$rows['Storename']);
                  if($_POST['ProdStore']==trim($rows['code'])){
                      $this->check=trim($rows['code']);
                      
                  }
                 }
        } else {
            $this->productionTankStore="T";
            $ResultIndex=DB_query(sprintf("select tankname from [ProductionUnit] where itemcode='%s'",$ItemCode), $db);
            while($value=DB_fetch_array($ResultIndex)){
              $line .= sprintf('<option value="%s" %s >%s</option>',trim($value['tankname']),$_POST['ProdStore']==trim($value['tankname'])?'selected="selected"':'',$value['tankname']);
              if($_POST['ProdStore']==trim($value['tankname'])){
                  $this->check=trim($value['tankname']);
                  
              }
              }
       }
         return $line; 
    }
 
     function GetStockdetails($itemcode){
         global $db;
         
         if($itemcode=='H2O'){
             $this->stocklist['barcode'] = 'WATER';
             $this->stocklist['itemcode'] = 'H2O';
             $this->stocklist['stockname'] = 'WATER';
             $this->stocklist['averagestock'] = '1';
          }else{
                 $ResultIndex = DB_query("SELECT 
                        [stockmaster].[barcode],
                        [stockmaster].[itemcode],
                        [stockmaster].[descrip] as [stockname],
                        [unit].[descrip] as [si],
                        [stockmaster].[averagestock],
                        [vatcategory].[vat],
                        [c].[itemcode] as [container],
                        [c].[descrip] as [packname],
                        [cv].[vat] as CVAT,
                        [stockmaster].[sellingprice]
                  FROM [stockmaster] 
                  left join [stockmaster] c on [stockmaster].[container]=[c].[itemcode]
                  left join [unit] on [stockmaster].[units]=[unit].[code]
                  left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
                  left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
                  left join [inventorypostinggroup] ci on [c].[postinggroup]=[ci].[code]
                  left join [vatcategory] cv on [ci].[vatcategory]=[cv].[vatc] 
                  where [stockmaster].itemcode='".trim($itemcode)."' ", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode'] = $stocklist[0];
         $this->stocklist['itemcode'] = $stocklist[1];
         $this->stocklist['stockname'] = $stocklist[2];
         $this->stocklist['si'] = $stocklist[3];
         $this->stocklist['averagestock'] =  $stocklist[4];
         $this->stocklist['vat'] = $stocklist[5];
         $this->stocklist['container'] = $stocklist[6];
         $this->stocklist['packname'] = $stocklist[7];
         $this->stocklist['CVAT'] = $stocklist[8];
         $this->stocklist['sellingprice'] = $stocklist[9];
         }
         return $this->stocklist;
         
     }
    
     function Getitems($itemcode,$qty,$uom){
         $StockList = $this->GetStockdetails($itemcode);
          
         if(mb_strwidth($StockList['itemcode'])>0){
                $id = trim($StockList['itemcode']);
                $_SESSION['work_orders'][$id]= array('line_no'=>$id,
                         'itemcode'=>$id,
                         'barcode'=>$StockList['barcode'],
                         'stockname'=>$StockList['stockname'],
                         'uom'=>$uom,
                         'averagestock'=>$StockList['averagestock'],
                         'quantity'=>$qty);
         }
          
         $this->GetSum();
         $this->showtable();
     }
      
     function neworder(){
         $_SESSION['work_orders']=array();
     }
     
     function GetSum(){
         $Total=0;
          foreach ($_SESSION['work_orders'] as $lineno => $rowvalue) {
                $qty=(float) $rowvalue['quantity'] ;
                $TotalProduction +=($qty);
          }
         $this->TotalProduction =$TotalProduction;
         $this->proitemcode = $_POST['proitemcode'];
     }
     
    function RemoveOrder($itemcode){
            $this->deleteID=trim($itemcode);
            unset($_SESSION['work_orders'][$this->deleteID]);
            unset($_POST['stockitemcode']); 
            unset($_POST['qty']); 
            unset($_POST['sp']);
            unset($_POST['units']);
    }
    
    Function IsTankOrStore($ItemCode){
        global $db;
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$ItemCode);
        $ResultIndex=DB_query($sql, $db);
        $Stores= DB_fetch_row($ResultIndex);
       if($Stores[0]==0){
          $this->tankStore="S";
       }else{
          $this->tankStore="T";
       }
    }

    function tankresults($store,$item){
      global $db;
       $return ='';
       
        $sql=sprintf("select count(*) from [ProductionUnit] where itemcode='%s'",$item);
         $ResultIndex = DB_query($sql, $db);
        $Stores = DB_fetch_row($ResultIndex);
       if($Stores[0]>0){
            $sql=sprintf("SELECT [balance] FROM [ProductionUnit] where [tankname]='%s' and [itemcode]='%s'",$store,$item);
            $ResultIndex = DB_query($sql,$db);
            $row = DB_fetch_row($ResultIndex);
            $StockBalanceInloose = $row[0];
            $return= sprintf('%f',(int)$StockBalanceInloose);
         }else{
            $return= 'No limit';
         }
         return $return;
   }
   
    function showtable(){
        global $db;
        
        $runningnettotal = 0;
        $totalProduction = 0;
        $percentTotal=0;
            
            foreach ($_SESSION['work_orders'] as $lineno => $rowvalue) {
                $line_no   = trim($rowvalue['line_no']);
                $itemcode  = $rowvalue['itemcode'];
                $this->IsTankOrStore($itemcode);
                $stocklist = $this->GetStockdetails($itemcode);
                
                $UOM = $rowvalue['uom']; $package = 1; 
                 
                $this->MakeDefaultStoreTankForItem($itemcode,$line_no);
         
                $location  =isset($_POST['location'][$line_no])? $_POST['location'][$line_no]:($this->Default[0]['code']);
           
                $averagestock  = (float) $rowvalue['averagestock'];
                $qty =(float) $rowvalue['quantity'] ;
                $baseamount  =(float) ($averagestock * $qty );
                $runningnettotal += $baseamount;
                $Production = $qty * $package;
                
                 $code    = trim($stocklist['itemcode']);
                 $ID      = trim($stocklist['itemcode']);
                 $BC      = trim($stocklist['barcode']);
                 $NAME    = trim($stocklist['stockname']);
                 $arrayi  = $_SESSION['work_orders'][$lineno];
                 $percent = (100*($Production/$this->TotalProduction));
                 $percentTotal += $percent;
                 
                 if(mb_stristr($itemcode,'H2O')=='H2O'){
                     $StockBalanceInloose=$qty;
                     $_SESSION['stockmasterp'][$code]['Balance']= $StockBalanceInloose;
                 }else{
                    if($this->tankStore=="T"){
                        $StockBalanceInloose =(int) getTankbalance($location,$code);
                        $_SESSION['stockmasterp'][$code]['Balance']= $StockBalanceInloose;
                    }elseif($this->tankStore=="S"){
                        $StockBalanceInloose=(int) getbalance($code,$location,'loosqty');
                        $_SESSION['stockmasterp'][$code]['Balance']=$StockBalanceInloose;
                    }
                      
                 }
                
                 
                $_SESSION['work_orders'][$lineno] = array_replace_recursive($arrayi,
                array(
                'TotalProduction'=>$this->TotalProduction,
                'tankstore'=>$this->tankStore,
                'netamount'=>$baseamount,'percent'=>$percent,
                'stkbalance'=>(($_SESSION['stockmasterp'][$code]['Balance'])-$qty))
                );
            
                $this->htmltable .= sprintf('<tr onclick="ProductionGrid(\'%s\',\'%s\',\'%s\',\'%s\');">'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="hidden" name="quantity['.$line_no.']" value="'.$qty.'" size="5" readonly="readonly" />'
              . '<select name="location['.$line_no.']"  onchange="ReloadForm(prodform.refresh)">%s</select></td>'
              . '<td><input type="text" class="number cell" readonly="readonly" value="'.(($_SESSION['stockmasterp'][$code]['Balance'])-$qty).'"  size="8"/></td>'
              . '<td>%s</td>'
              . '<td><input type="text" class="number cell"  value="'.($averagestock).'"  size="8"/></td>'
              . '<td class="number cell"><input type="text" class="number" name="cents['.$line_no.']" value="'.$percent.'" readonly="readonly" size="8"/></td>'
              . '<td class="number cell"><input type="text" class="number" name="Production['.$line_no.']" value="'.number_format($Production,2).'" readonly="readonly" size="8"/></td>'
             . '<td class="number cell"><input type="text" class="number" name="netamount['.$line_no.']" value="'.number_format($baseamount,2).'" readonly="readonly" size="8"/></td>'
             . '</tr>',$ID,$BC,$NAME,$qty,trim($stocklist['barcode']),trim($stocklist['stockname']),$this->MakeStoreTankForItem($ID,$line_no),getGriduom($line_no,$UOM ));
            }
            
    $tankAllowedCapacity = $this->tankresults($_POST['ProdStore'],$_POST['proitemcode']);
    $Reperent = (($this->TotalProduction/$tankAllowedCapacity)*100);
    
                       
    $this->htmltable .= '<tfoot><tr><td colspan="6"><input type="hidden" name="TotalProductionCost" value="'.$runningnettotal.'"/>'
    . '<input type="hidden" id="proitemcode" size="5" name="proitemcode" value="'.$_POST['proitemcode'].'" readonly="readonly"/>'
    . '<table class="table-bordered"><tr><td>Product To Produce</td><td colspan="4">'
    . '<input type="text" style="width:70%;" id="Prostockname" name="Prostockname" value="'.$_POST['Prostockname'].'" readonly="readonly"/></td></tr>'
    . '<tr><td>Select Store/Tank</td><td><select name="ProdStore" onchange="ReloadForm(prodform.refresh)">'.$this->ToStoreTankForItem($_POST['proitemcode']).'</select></td>'
    . '<td>Tank Capacity in ltrs :</td><td><input type="text" class="number" name="TankAllowedCapacity" value="'.$tankAllowedCapacity.'" readonly="readonly" /></td></tr>'
    . '<tr><td colspan="5">Your not allowed to produce more than this limit . Now at '. number_format($Reperent,1).' %</td></tr>'
    . '<tr><td colspan="4">Enter Production :</td><td><input type="text" name="TotalProduction" value="'.$_POST['TotalProduction'].'"/>'
    . '<input type="hidden" name="productionTankStore" value="'.$this->productionTankStore.'"/>'
    . '<input type="hidden" name="originalproduction" value="'.$this->TotalProduction.'"/></td></tr>'
    . '</table></td>'. sprintf('<td class="number cell">%s</td><td class="number cell">%s</td>'
    . '<td class="number cell">%s</td></tr></tfoot>',$percentTotal,$_POST['TotalProduction'],number_format($runningnettotal,2));
           
    if(mb_stristr($_POST['ProdStore'],$this->check)==$this->check  
            and mb_strlen($this->check)>0 
            and mb_strlen($_POST['TotalProduction'])>0){
        $refreshPost="SaveProduction";
    }else{
         $refreshPost="refreshcheck";
    }

    $_SESSION['altername_SaveProduction'] = $refreshPost;
    $_SESSION['htmltable'] = $this->htmltable;
    }
    
    function RepopulateProduction($batchno){
        Global $db;
        
      $_POST['documentno'] = $batchno;
      $_SESSION['work_orders'] = array();
       
      $SQL = sprintf("SELECT [itemcode] FROM [ProductionMaster] where [Batchno]='%s'",$batchno);
      $ResultIndex=DB_query($SQL,$db);
      $row = DB_fetch_row($ResultIndex);
      $_POST['proitemcode'] = $row[0];
      $StockArray = $this->GetStockdetails($_POST['proitemcode']);
      $_POST['Prostockname'] = $StockArray['stockname'];
                 
      $SQL = sprintf("select Batchno,itemcode,qty,cost from ProdcutionMasterLine where Batchno='%s'",$batchno);
      $result=DB_query($SQL,$db);
      while($row= DB_fetch_array($result)){
         $StockList = $this->GetStockdetails($row['itemcode']);
          
         if(mb_strwidth($StockList['itemcode'])>0){
                $id = trim($StockList['itemcode']);
                $_SESSION['work_orders'][$id]= array('line_no'=>$id,
                         'itemcode'=>$id,
                         'barcode'=>$StockList['barcode'],
                         'stockname'=>$StockList['stockname'],
                         'uom'=>'1',
                         'averagestock'=>$StockList['averagestock'],
                         'quantity'=>$row['qty']);
         }
      }  
         $this->GetSum();
      
    }
    
}
 
class Production {
     var  $customerposting;
     var  $VATinclusive;
     var  $IsTaxed;
     var  $htmltable;
     var  $stocklist=array();
    
     function GetStockdetails($itemcode){
         global $db;
         
         $ResultIndex = DB_query("SELECT 
                        [stockmaster].[barcode],
                        [stockmaster].[itemcode],
                        [stockmaster].[descrip] as [stockname],
                        [unit].[descrip] as [si],
                        [stockmaster].[averagestock],
                        [stockmaster].[partperunit],
                        [vatcategory].[vat]
                  FROM [stockmaster] 
                  left join [unit] on [stockmaster].[units]=[unit].[code]
                  left join [inventorypostinggroup] on [stockmaster].[postinggroup]=[inventorypostinggroup].[code]
                  left join [vatcategory] on [inventorypostinggroup].[vatcategory]=[vatcategory].[vatc]
                  where [stockmaster].itemcode='".$itemcode."'", $db);
         
         $stocklist=DB_fetch_row($ResultIndex);
         $this->stocklist['barcode']= $stocklist[0];
         $this->stocklist['itemcode']= $stocklist[1];
         $this->stocklist['stockname']= $stocklist[2];
         $this->stocklist['si']= $stocklist[3];
         $this->stocklist['averagestock']= $stocklist[4];
         $this->stocklist['partperunit']= $stocklist[5];
         $this->stocklist['vat']= $stocklist[6];
         return $this->stocklist;
         
     }
     
     function Getitems($itemcode,$qty){
         $StockList = $this->GetStockdetails($itemcode);
         $line_No   = $this->LineNo();
          
         $_SESSION['labreagents'][$line_No]=
            array('line_no'=>$line_No,
                  'itemcode'=>$StockList['itemcode'],
                  'barcode'=>$StockList['barcode'],
                  'stockname'=>$StockList['stockname'],
                  'partperunit'=>$StockList['partperunit'],
                  'quantity'=>$qty
                  );
         
         $this->showtable();
     }
     
     function LineNo(){
         return $_SESSION['labreagents_index'];
     }
     
     function neworder(){
         $_SESSION['labreagents'] = array();
         $_SESSION['labreagents_index'] = 0;
     }
     
     function Nextorder(){
         $_SESSION['labreagents_index']++;
    }
    
    function showtable(){
        global $db;
               
        foreach ($_SESSION['labreagents'] as $lineno => $rowvalue) {
                $line_no   = $rowvalue['line_no'];
                $itemcode  = $rowvalue['itemcode'];
                $stocklist = $this->GetStockdetails($itemcode);
                
                if(isset($_POST['quantity'][$line_no])){
                    $qty = $_POST['quantity'][$line_no];
                }else{
                    $qty = 0;
                }
                 
                $this->htmltable .= sprintf('<tr>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td>%s</td>'
              . '<td><input type="text" class="number" name="quantity['.$line_no.']" value="'.$qty.'" size="5"/>'
              . '<input type="hidden"  name="itemcode['.$line_no.']" value="'.$stocklist['itemcode'].'"/></td>'
              . '<td>%s</td>'
              . '</tr>',trim($stocklist['barcode']), 
                        trim($stocklist['stockname']),
                        CreateUnits($itemcode,$line_no),
                        createstores($line_no) );
            }
        echo $this->htmltable;

    }
       
 }

Class ProcessInformationAlter{
    var $post;
    var $session;
    var $workorderclass;
    var $error=0;
    
    function get($post,$session,$workorderclass){
        $this->post=$post; 
        $this->session=$session;
        $this->workorderclass=$workorderclass;
    }
    
    function anythingwrong(){
        global $db;
        
        $return ='YES';
          
        $ResultIndex = DB_query("SELECT count(*) FROM [stockmaster] where itemcode='".trim($this->post['proitemcode'])."' ", $db);
        $rows=DB_fetch_row($ResultIndex);
        if($rows[0]==1){           
            
            if(mb_strstr($this->post['TankAllowedCapacity'],'No limit')==FALSE){
                if($this->post['TankAllowedCapacity'] < $this->post['TotalProduction']){
                   $this->error++;
                 }
             }
        }else{
            $this->error++;
        }
        
      
         if($this->error>0){
            echo '<script type="text/javascript">function Checkproductionintegrity(){ ';
            echo " alert('Please check stock balances or tank capacity or prodction product code if its blank'); ";
            echo " }  Checkproductionintegrity();</script>";
            
             $return= 'YES';
         }else{
             $this->savestockproduction();
             $return= 'NO';
         }
       
         return $return;
    }
    
    
    function savestockproduction(){
        global $db;
         $journal = GetNextTransNo(0,$db);
         $period = GetPeriod($this->post['date'],$db,true);
         $formatedDate = FormatDateForSQL($this->post['date']);
         
         $DiffProduction = ($this->post['TotalProduction']-$this->post['originalproduction']);
         $averagecost = $this->post['TotalProductionCost']/$this->post['TotalProduction'];
         
         $sql[] = sprintf("UPDATE [ProductionMaster] SET modified=%f ,[userid]='%s'  where [Batchno]='%s' and itemcode='%s' ", 
                 $this->post['TotalProduction'],stripslashes($_SESSION['UserID']),$this->post['documentno'],$this->post['proitemcode']
                );
         
         if($this->post['productionTankStore']=="T"){
            $sql[] = sprintf("INSERT INTO [tanktrans] ([tankname],[units],[uom],[date],[batchno],[doctype],[itemcode])
                     VALUES  ('%s',%f ,'%s' ,'%s' ,'%s',28,'%s')", $this->post['ProdStore'],
                    $DiffProduction,'',$formatedDate,$this->post['documentno'],$this->post['proitemcode']);
         
         }elseif($this->post['productionTankStore']=="S"){
            $sql[] = "INSERT [stockledger] (fulqty,[date],[stname],[doctyp],[itemcode] ,[invref],[loosqty],[price],[store],[journal],[period],[PartPerUnit]) values "
                    . "(0,'".$formatedDate."' ,'".$this->post['Prostockname']."','28','".$this->post['proitemcode']."','". $this->post['documentno']."',".$DiffProduction.",0,'".$this->post['ProdStore']."','". $journal ."','". $period ."',1)";
         }
         
        if($averagecost>0){ 
           $sql[] = "Update [stockmaster] set [averagestock]=".$averagecost." where itemcode='".$this->post['proitemcode']."'";

           $sql[] = Sprintf("INSERT INTO [StockRegister] ([itemcode],[StockIn],[cost],[journal],[GRN],[StockOut]) 
            VALUES  ('%s',%f,%f,'%s','%s',0)",$this->post['proitemcode'],$DiffProduction,$averagecost, $journal,$this->post['documentno']);
        }
         
     
         
          foreach ($sql as $value) {
              
              $ResultIndex = DB_query($value,$db);
          }
         
       echo '<script type="text/javascript">function Checkproductionintegrity(){ ';
            echo sprintf(" alert('Production %s has been altered. Now prepare Lab Test Report'); ",$this->post['documentno']);
            echo " }  Checkproductionintegrity();</script>";
            
          
    }
    
    
    
    
    
    
}

class CalculateVCF {
    
    var $alpha ; // Coefficient of thermal expansion for bitumen
    var $Tb ; // Base temperature

    function __construct() {
          $this->alpha = 0.0008; 
          $this->Tb = 15; 
    }
            function VCF($T1) {
                if($T1>15){
               
                return (1 + $this->alpha * ($this->Tb - $T1)) / (1 + $this->alpha * ($T1 - $this->Tb));
                
                }else{ 
                    return 0;
                    
                }
            }

}

