<<<<<<< HEAD
<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.

include('transactions/stockbalance.inc');   
$Title = _('Print Stock');

$stockClass = new StockBalanceReport();
    
if(isset($_POST['trailbalance'])){
    if(Date1GreaterThanDate2($_POST['fromdate'],$_POST['todate'])==TRUE){
     unset($_POST['trailbalance']);
     $errorExists=1;
    }
}

if(isset($_POST['trailbalance'])){
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Inventory Reports'));
    $pdf->addInfo('Subject',_('inventory'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 12;
        
    Getheader();
     $FontSize = 8;
     $YPos = $firstrowpos;
     $Balance = 0;
     
     $ResultsP = $stockClass->EXECUTE();
     while($rows = DB_fetch_array($ResultsP)){
         $totalline = ($rows['Close_f'] *  $rows['averagestock']);
         $Balance += $totalline;
         
         $LeftOvers = $pdf->addTextWrap(50, $YPos,100, $FontSize, ucfirst($rows['stockname']),'left');
         $LeftOvers = $pdf->addTextWrap(150, $YPos,50, $FontSize, trim($rows['fulqtyName']),'right');
         $LeftOvers = $pdf->addTextWrap(230, $YPos,30, $FontSize, number_format($rows['averagestock'],2),'right');
         $LeftOvers = $pdf->addTextWrap(400, $YPos,30, $FontSize, number_format($rows['Close_f'],0),'right');
          $LeftOvers = $pdf->addTextWrap(500, $YPos,50, $FontSize, number_format($totalline,2),'right');
           
         $YPos -= $line_height ;
         if($YPos < ($lastrow + ($line_height * 3))){
             Getheader();
             $YPos=$firstrowpos;
             $FontSize = 8;
         }
      }
          
 $rowtanks = DB_query("SELECT [ProductionUnit].[itemcode],[capacity],[tankname],[UOM],[CapacityUOM] ,
        [ProductionUnit].[units],[balance],[stockmaster].[descrip],[stockmaster].[averagestock]
        FROM [ProductionUnit] join stockmaster on ProductionUnit.itemcode=stockmaster.itemcode
        where [ProductionUnit].[status]=1", $db);
     
    if(DB_num_rows($rowtanks)>0){
        $YPos -= $line_height ;
        $pdf->addTextWrap(50, $YPos,100, $FontSize,_('Work In Progress'),'left');
          
        $YPos -= $line_height ;
         $pdf->addTextWrap(50, $YPos,100, $FontSize,_('Product'),'left');
         $pdf->addTextWrap(150, $YPos,50, $FontSize, _('Tank Name'),'right');
         $pdf->addTextWrap(230, $YPos,30, $FontSize,_('Average Cost'),'right');
         $pdf->addTextWrap(450, $YPos,30, $FontSize,_('Stock QTY'),'right');
         $pdf->addTextWrap(500, $YPos,50, $FontSize,_('Stock Value'),'right');

    }
    
    
    $DateEntry = FormatDateForSQL($_POST['todate']);
    $rowtanks = DB_query("SELECT [ProductionUnit].[itemcode],[capacity],[tankname],[UOM],[CapacityUOM] ,
        [ProductionUnit].[units],[balance],[stockmaster].[descrip],[stockmaster].[averagestock]
        FROM [ProductionUnit] join stockmaster on ProductionUnit.itemcode=stockmaster.itemcode
        where [ProductionUnit].[status]=1", $db);
   while($rows = DB_fetch_array($rowtanks)){
         $YPos -= $line_height ;
         if($YPos < ($lastrow + ($line_height * 2))){
             Getheader();
             $YPos = $firstrowpos;
             $FontSize = 8;
         }
         
         $StockBalanceInloose = getTankbalanceBYDATE($rows['tankname'],$DateEntry);
         $Balance += $StockBalanceInloose * $rows['averagestock'];
         
         $LeftOvers = $pdf->addTextWrap(50, $YPos,100, $FontSize, ucfirst($rows['descrip']),'left');
         $LeftOvers = $pdf->addTextWrap(150, $YPos,50, $FontSize, _('Tank :').$rows['tankname'],'right');
         $LeftOvers = $pdf->addTextWrap(230, $YPos,30, $FontSize, number_format($rows['averagestock'],2),'right');
         $LeftOvers = $pdf->addTextWrap(450, $YPos,30, $FontSize, number_format($StockBalanceInloose,0),'right');
         $LeftOvers = $pdf->addTextWrap(500, $YPos,50, $FontSize, number_format($StockBalanceInloose * $rows['averagestock'],2),'right');
     
   }  
   
    $YPos -= $line_height ;
    
    $LeftOvers = $pdf->addTextWrap(450, $YPos,30, $FontSize,_('Total'),'right');
    $LeftOvers = $pdf->addTextWrap(500, $YPos,50, $FontSize, number_format($Balance,2),'right');
   
    
$pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('Inventory'). '_' . date('Y-m-d').'.pdf');
$pdf->__destruct();

    
} else {
    
  $Title = _('Print Inventory');
  include('includes/header.inc');
  
  if(isset($errorExists)){
     prnMsg('You have selected an invalid date range','warn');
  }
  
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Inventory Report') .'" alt="" />' . _('Inventory Report') . '</p>';
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>';
  echo '<table style="height:200px"><tr><td valign="top"><table class="table table-bordered"><tr>'
    . '<td>From Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="fromdate" size="11" maxlength="10" readonly="readonly" value="' .$_POST['fromdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>
       <td>To Date</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="todate" size="11" maxlength="10" readonly="readonly" value="' .$_POST['todate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
 echo '<tr><td>Select Store</td><td><select name="store"><option value="AllStores">All</option>';
 
   $sql="SELECT [code],[Storename] FROM [Stores]";
   $ResultsP = DB_query($sql,$db);
    while($rows = DB_fetch_array($ResultsP)){
          echo '<option value="'.$rows['code'].'">'.$rows['Storename'].'</option>' ;
   }
   
  echo '</select></td></tr></table></td></tr>'
  . '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Print Inventory"/></td></tr></table>';
  echo '</div></form>';
  
  include('includes/footer.inc');
  
  
}
 
class StockBalanceReport {
      var $procedure;
      var $Fromdate;
      var $Todate;
      var $StoreCode;
      
      function __construct() {
        
          $this->Fromdate = ConvertSQLDate($_POST['fromdate']);
          $this->Todate = ConvertSQLDate($_POST['todate']);
          $this->StoreCode = $_POST['store'];
      }
            
      function CreateScript(){
           global $db;
         
           
        $this->procedure="Create PROCEDURE [dbo].[InventoryReport]  AS   BEGIN
	
	create table  #inventory (
	[itemcode] [varchar](20) NULL,
	[stockname] [varchar](100) NULL,
	[averagestock] decimal(10,2) NULL,
	[fulqtyName]  [varchar](50) NULL,
	[openstock_f] decimal(10,0) NULL,
	[Purchases_f] decimal(10,0) null,
        [Prod_f] decimal(10,0) null,
	[Total_f] decimal(10,0) null,
	[Sales_f] decimal(10,0) null,
        [Work_f] decimal(10,0) null,
	[Close_f] decimal(10,0) null,
   )

	Declare @itemcode varchar(20),@stockname varchar(100),@ave float,@kit int,@fulldesc varchar(50),@loosedesc varchar(50)
	Declare @fulqty int ,@loosqty int ,@querry varchar(max), @Openfqty int , @openlsqty int
	Declare @Purchasesfqty int ,@purchaselsqty int ,@Totalfqty int ,@Totallsqty int
	Declare @Salesfqty int ,@Saleslsqty int ,@Closingfqty int ,@Closinglsqty int ,@results varchar(max)
        Declare @Prodfqty int ,@Prodlsqty int ,@Worderqty int , @Worklsqty int

	Declare Sstock cursor for select stockmaster.itemcode,stockmaster.descrip,stockmaster.averagestock
        from stockmaster 
        where (isstock_3=0 or isstock_3 is null) and ([inactive]=0 or [inactive] is null)
        open  Sstock
	fetch next from  Sstock into @itemcode,@stockname,@ave
	WHILE (@@FETCH_STATUS = 0)
	BEGIN 

	select @fulqty=sum(fulqty*partperunit),@loosqty=sum(loosqty) from stockledger where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and  date <'".$this->Fromdate."' ":" and date < '".$this->Fromdate."' and store= '".$this->StoreCode."'"); 
	
        $this->procedure .="  select @Openfqty = isnull(@fulqty,0) + isnull(@loosqty,0)
	
	select @fulqty=sum(fulqty*partperunit),@loosqty=sum(loosqty) from stockledger where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and date between '".$this->Fromdate."' and '".$this->Todate."'  and (doctyp=30)":" and date between '".$this->Fromdate."' and '".$this->Todate."'  and store='".$this->StoreCode."' and (doctyp=30)");
        
        $this->procedure .="  select @Purchasesfqty = isnull(@fulqty,0) + isnull(@loosqty,0)
	
	select @fulqty=sum(fulqty*partperunit),@loosqty=sum(loosqty) from stockledger  where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and date between '".$this->Fromdate."' and '".$this->Todate."'  and (doctyp=40)":" and date between '".$this->Fromdate."' and '".$this->Todate."'  and store='".$this->StoreCode."' and (doctyp=40)");
        
        $this->procedure .="  select @Prodfqty = isnull(@fulqty,0) + isnull(@loosqty,0) 
	
	select @fulqty=sum(fulqty*partperunit),@loosqty=sum(loosqty) from stockledger  where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and date between '".$this->Fromdate."' and '".$this->Todate."'  and (doctyp=26)":" and date between '".$this->Fromdate."' and '".$this->Todate."'  and store='".$this->StoreCode."' and (doctyp=26)");
        
        $this->procedure .="  select @Worderqty = isnull(@fulqty,0) + isnull(@loosqty,0) 
	
        select @fulqty=sum(fulqty*partperunit),@loosqty=sum(loosqty) from stockledger  where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and date between '".$this->Fromdate."' and '".$this->Todate."'  and (doctyp=19)":" and date between '".$this->Fromdate."' and '".$this->Todate."'  and store='".$this->StoreCode."' and (doctyp=19)");
        
        $this->procedure .="  select @Salesfqty = isnull(@fulqty,0) + isnull(@loosqty,0) 
	

	select  @Totalfqty = ISNULL(@Openfqty,0)+ISNULL(@Purchasesfqty,0) + ISNULL(@openlsqty,0)+ISNULL(@purchaselsqty,0)
	

	select @fulqty=sum(fulqty*partperunit),@loosqty=sum(loosqty) from stockledger  where itemcode=@itemcode "; 
        $this->procedure .=($this->StoreCode=='AllStores')?" and date <= '".$this->Todate."' ":  " and date <='".$this->Todate."' and store= '".$this->StoreCode."'";
	
        $this->procedure .="select @Closingfqty = isnull(@fulqty,0) + isnull(@loosqty,0)
	
	
	insert into #inventory ([itemcode],[stockname],[averagestock],[fulqtyName],
        [openstock_f],[Purchases_f],[Prod_f],[Total_f],[Sales_f],[Work_f],[Close_f])
	values (@itemcode,@stockname,@ave,@fulldesc ,@Openfqty,@Purchasesfqty,
	 @Prodfqty,@Totalfqty,@Salesfqty,@Worderqty,@Closingfqty)


	FETCH NEXT FROM  Sstock into @itemcode,@stockname,@ave
	END
	CLOSE  Sstock
	DEALLOCATE  Sstock

	select itemcode,stockname,averagestock,fulqtyName,openstock_f,Purchases_f,[Prod_f],Total_f, Sales_f, [Work_f], Close_f from  #inventory

       END";
        
        

      return  $this->procedure;
      }
  
      
      function dropprocedure($procedurename){
        global $db;

          $SQL="IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[".$procedurename."]') AND type in (N'P', N'PC'))
                DROP PROCEDURE [dbo].[".$procedurename."]";
         DB_query($SQL,$db);

        }
      
      
      function EXECUTE(){
          Global $db;
          
          $this->dropprocedure('InventoryReport');
          $reslts=$this->CreateScript();
          $Results=DB_query($reslts,$db);
          $Results=DB_query('[dbo].[InventoryReport]',$db);
          
          Return  $Results;
      }
      
      
  }
  
function Getheader(){
    Global $PageNumber,$pdf,$XPos,$YPos,$Page_Width,$Right_Margin,$FontSize,$firstrowpos,$lastrow,$Bottom_Margin;
    Global $Page_Height,$Top_Margin,$line_height;
// The $PageNumber variable is initialised in 0 by PDFStarter.php
// This page header increments variable $PageNumber before printing it.
$PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
    $pdf->newPage();
}
// Print 'Quotation' title
// $XPos = 361; 
$XPos=46;
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize * 1, 200, $FontSize, _('Page').': '.$PageNumber, 'right');

// Print company info
$XPos = 60;
$YPos = ($Page_Height-$Top_Margin-$FontSize * 2)-30;
$FontSize=12;
$pdf->addText($XPos, $YPos, $FontSize, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$FontSize =10;
$pdf->addText($XPos, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($XPos, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($XPos, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($XPos, $YPos-39, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($XPos, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($XPos, $YPos-57, $FontSize, _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);

$pdf->addText(($Page_Width/2)- $Right_Margin ,$YPos-70,12,_('Stock Balance Report') );
$pdf->addText(($Page_Width/2)- $Right_Margin ,$YPos-90,8,_('From :'). $_POST['fromdate']._(' To :'). $_POST['todate'] );
$pdf->line($Page_Width-$Right_Margin,$YPos-100,$Left_Margin,$YPos-100);

// Print 'Delivery To' info
$XPos = 46;
$YPos -= 120;
$FontSize=12;
$FontSize=10;


$LeftOvers = $pdf->addTextWrap(50, $YPos,100, $FontSize,_('Stock Name'),'left');
$LeftOvers = $pdf->addTextWrap(150, $YPos,80, $FontSize,_('UOM'),'left');
$LeftOvers = $pdf->addTextWrap(230, $YPos,80, $FontSize,_('Average Cost'),'center');
$LeftOvers = $pdf->addTextWrap(400, $YPos,80, $FontSize,_('Closing Stock'),'center');
$LeftOvers = $pdf->addTextWrap(490, $YPos,80, $FontSize ,_('Stock Value'),'center');

$pdf->line($Page_Width-$Right_Margin,($YPos - $line_height),$Left_Margin,($YPos - $line_height) );

$firstrowpos = ($YPos - $line_height) -20 ;
$lastrow = $Bottom_Margin + ($line_height * 2);

$YPos -= (2 * $line_height);

}
      
Function getTankbalanceBYDATE($tank,$DATE){
         global $db;
         $rowsunits="";
     
        $sql=sprintf("select sum([units]) as units 
        from [tanktrans]  where [date]<='%s' AND [tankname]='%s'",$DATE,$tank);
        $ResultIndex = DB_query($sql,$db);
         $dbrows= DB_fetch_row($ResultIndex);
         $rowsunits=$dbrows[0];

              
       return  $rowsunits;
     }
  
=======
<?php 
include('includes/session.inc');
include('includes/CurrenciesArray.php'); // To get the currency name from the currency code.
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.

include('transactions/stockbalance.inc');   
$Title = _('Print Stock');

$stockClass = new StockBalanceReport();
    
if(isset($_POST['trailbalance'])){
    if(Date1GreaterThanDate2($_POST['fromdate'],$_POST['todate'])==TRUE){
     unset($_POST['trailbalance']);
     $errorExists=1;
    }
}

if(isset($_POST['trailbalance'])){
    
    $PaperSize='A4';
    include('includes/PDFStarter.php');
    
    $pdf->addInfo('Title',_('Inventory Reports'));
    $pdf->addInfo('Subject',_('inventory'));
    $pdf->addInfo('Creator',_('SmartERP'));
     
    $FontSize = 15;
    $PageNumber = 0;
    $line_height = 12;
        
    Getheader();
     $FontSize = 8;
     $YPos = $firstrowpos;
     $Balance = 0;
     
     $ResultsP = $stockClass->EXECUTE();
     while($rows = DB_fetch_array($ResultsP)){
         $totalline = ($rows['Close_f'] *  $rows['averagestock']);
         $Balance += $totalline;
         
         $LeftOvers = $pdf->addTextWrap(50, $YPos,100, $FontSize, ucfirst($rows['stockname']),'left');
         $LeftOvers = $pdf->addTextWrap(150, $YPos,50, $FontSize, trim($rows['fulqtyName']),'right');
         $LeftOvers = $pdf->addTextWrap(230, $YPos,30, $FontSize, number_format($rows['averagestock'],2),'right');
         $LeftOvers = $pdf->addTextWrap(400, $YPos,30, $FontSize, number_format($rows['Close_f'],0),'right');
          $LeftOvers = $pdf->addTextWrap(500, $YPos,50, $FontSize, number_format($totalline,2),'right');
           
         $YPos -= $line_height ;
         if($YPos < ($lastrow + ($line_height * 3))){
             Getheader();
             $YPos=$firstrowpos;
             $FontSize = 8;
         }
      }
          
 $rowtanks = DB_query("SELECT [ProductionUnit].[itemcode],[capacity],[tankname],[UOM],[CapacityUOM] ,
        [ProductionUnit].[units],[balance],[stockmaster].[descrip],[stockmaster].[averagestock]
        FROM [ProductionUnit] join stockmaster on ProductionUnit.itemcode=stockmaster.itemcode
        where [ProductionUnit].[status]=1", $db);
     
    if(DB_num_rows($rowtanks)>0){
        $YPos -= $line_height ;
        $pdf->addTextWrap(50, $YPos,100, $FontSize,_('Work In Progress'),'left');
          
        $YPos -= $line_height ;
         $pdf->addTextWrap(50, $YPos,100, $FontSize,_('Product'),'left');
         $pdf->addTextWrap(150, $YPos,50, $FontSize, _('Tank Name'),'right');
         $pdf->addTextWrap(230, $YPos,30, $FontSize,_('Average Cost'),'right');
         $pdf->addTextWrap(450, $YPos,30, $FontSize,_('Stock QTY'),'right');
         $pdf->addTextWrap(500, $YPos,50, $FontSize,_('Stock Value'),'right');

    }
    
    
    $DateEntry = FormatDateForSQL($_POST['todate']);
    $rowtanks = DB_query("SELECT [ProductionUnit].[itemcode],[capacity],[tankname],[UOM],[CapacityUOM] ,
        [ProductionUnit].[units],[balance],[stockmaster].[descrip],[stockmaster].[averagestock]
        FROM [ProductionUnit] join stockmaster on ProductionUnit.itemcode=stockmaster.itemcode
        where [ProductionUnit].[status]=1", $db);
   while($rows = DB_fetch_array($rowtanks)){
         $YPos -= $line_height ;
         if($YPos < ($lastrow + ($line_height * 2))){
             Getheader();
             $YPos = $firstrowpos;
             $FontSize = 8;
         }
         
         $StockBalanceInloose = getTankbalanceBYDATE($rows['tankname'],$DateEntry);
         $Balance += $StockBalanceInloose * $rows['averagestock'];
         
         $LeftOvers = $pdf->addTextWrap(50, $YPos,100, $FontSize, ucfirst($rows['descrip']),'left');
         $LeftOvers = $pdf->addTextWrap(150, $YPos,50, $FontSize, _('Tank :').$rows['tankname'],'right');
         $LeftOvers = $pdf->addTextWrap(230, $YPos,30, $FontSize, number_format($rows['averagestock'],2),'right');
         $LeftOvers = $pdf->addTextWrap(450, $YPos,30, $FontSize, number_format($StockBalanceInloose,0),'right');
         $LeftOvers = $pdf->addTextWrap(500, $YPos,50, $FontSize, number_format($StockBalanceInloose * $rows['averagestock'],2),'right');
     
   }  
   
    $YPos -= $line_height ;
    
    $LeftOvers = $pdf->addTextWrap(450, $YPos,30, $FontSize,_('Total'),'right');
    $LeftOvers = $pdf->addTextWrap(500, $YPos,50, $FontSize, number_format($Balance,2),'right');
   
    
$pdf->OutputD($_SESSION['DatabaseName'] . '_' ._('Inventory'). '_' . date('Y-m-d').'.pdf');
$pdf->__destruct();

    
} else {
    
  $Title = _('Print Inventory');
  include('includes/header.inc');
  
  if(isset($errorExists)){
     prnMsg('You have selected an invalid date range','warn');
  }
  
  echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/maintenance.png" title="' . _('Inventory Report') .'" alt="" />' . _('Inventory Report') . '</p>';
  echo '<form autocomplete="off" action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8').'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
  echo '<input type="hidden" name="FormID" value="'.$_SESSION['FormID'].'"/>';
  echo '<table style="height:200px"><tr><td valign="top"><table class="table table-bordered"><tr>'
    . '<td>From Date</td><td><input tabindex="1" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="fromdate" size="11" maxlength="10" readonly="readonly" value="' .$_POST['fromdate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td>
       <td>To Date</td><td><input tabindex="2" type="text" class="date" alt="'.$_SESSION['DefaultDateFormat'].'" name="todate" size="11" maxlength="10" readonly="readonly" value="' .$_POST['todate']. '" onchange="isDate(this, this.value, '."'".$_SESSION['DefaultDateFormat']."'".')"/></td></tr>';
 echo '<tr><td>Select Store</td><td><select name="store"><option value="AllStores">All</option>';
 
   $sql="SELECT [code],[Storename] FROM [Stores]";
   $ResultsP = DB_query($sql,$db);
    while($rows = DB_fetch_array($ResultsP)){
          echo '<option value="'.$rows['code'].'">'.$rows['Storename'].'</option>' ;
   }
   
  echo '</select></td></tr></table></td></tr>'
  . '<tr><td colspan="2"><input type="submit" name="trailbalance" value="Print Inventory"/></td></tr></table>';
  echo '</div></form>';
  
  include('includes/footer.inc');
  
  
}
 
class StockBalanceReport {
      var $procedure;
      var $Fromdate;
      var $Todate;
      var $StoreCode;
      
      function __construct() {
        
          $this->Fromdate = ConvertSQLDate($_POST['fromdate']);
          $this->Todate = ConvertSQLDate($_POST['todate']);
          $this->StoreCode = $_POST['store'];
      }
            
      function CreateScript(){
           global $db;
         
           
        $this->procedure="Create PROCEDURE [dbo].[InventoryReport]  AS   BEGIN
	
	create table  #inventory (
	[itemcode] [varchar](20) NULL,
	[stockname] [varchar](100) NULL,
	[averagestock] decimal(10,2) NULL,
	[fulqtyName]  [varchar](50) NULL,
	[openstock_f] decimal(10,0) NULL,
	[Purchases_f] decimal(10,0) null,
        [Prod_f] decimal(10,0) null,
	[Total_f] decimal(10,0) null,
	[Sales_f] decimal(10,0) null,
        [Work_f] decimal(10,0) null,
	[Close_f] decimal(10,0) null,
   )

	Declare @itemcode varchar(20),@stockname varchar(100),@ave float,@kit int,@fulldesc varchar(50),@loosedesc varchar(50)
	Declare @fulqty int ,@loosqty int ,@querry varchar(max), @Openfqty int , @openlsqty int
	Declare @Purchasesfqty int ,@purchaselsqty int ,@Totalfqty int ,@Totallsqty int
	Declare @Salesfqty int ,@Saleslsqty int ,@Closingfqty int ,@Closinglsqty int ,@results varchar(max)
        Declare @Prodfqty int ,@Prodlsqty int ,@Worderqty int , @Worklsqty int

	Declare Sstock cursor for select stockmaster.itemcode,stockmaster.descrip,stockmaster.averagestock
        from stockmaster 
        where (isstock_3=0 or isstock_3 is null) and ([inactive]=0 or [inactive] is null)
        open  Sstock
	fetch next from  Sstock into @itemcode,@stockname,@ave
	WHILE (@@FETCH_STATUS = 0)
	BEGIN 

	select @fulqty=sum(fulqty*partperunit),@loosqty=sum(loosqty) from stockledger where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and  date <'".$this->Fromdate."' ":" and date < '".$this->Fromdate."' and store= '".$this->StoreCode."'"); 
	
        $this->procedure .="  select @Openfqty = isnull(@fulqty,0) + isnull(@loosqty,0)
	
	select @fulqty=sum(fulqty*partperunit),@loosqty=sum(loosqty) from stockledger where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and date between '".$this->Fromdate."' and '".$this->Todate."'  and (doctyp=30)":" and date between '".$this->Fromdate."' and '".$this->Todate."'  and store='".$this->StoreCode."' and (doctyp=30)");
        
        $this->procedure .="  select @Purchasesfqty = isnull(@fulqty,0) + isnull(@loosqty,0)
	
	select @fulqty=sum(fulqty*partperunit),@loosqty=sum(loosqty) from stockledger  where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and date between '".$this->Fromdate."' and '".$this->Todate."'  and (doctyp=40)":" and date between '".$this->Fromdate."' and '".$this->Todate."'  and store='".$this->StoreCode."' and (doctyp=40)");
        
        $this->procedure .="  select @Prodfqty = isnull(@fulqty,0) + isnull(@loosqty,0) 
	
	select @fulqty=sum(fulqty*partperunit),@loosqty=sum(loosqty) from stockledger  where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and date between '".$this->Fromdate."' and '".$this->Todate."'  and (doctyp=26)":" and date between '".$this->Fromdate."' and '".$this->Todate."'  and store='".$this->StoreCode."' and (doctyp=26)");
        
        $this->procedure .="  select @Worderqty = isnull(@fulqty,0) + isnull(@loosqty,0) 
	
        select @fulqty=sum(fulqty*partperunit),@loosqty=sum(loosqty) from stockledger  where itemcode=@itemcode "; 
        $this->procedure .=(($this->StoreCode=='AllStores')?" and date between '".$this->Fromdate."' and '".$this->Todate."'  and (doctyp=19)":" and date between '".$this->Fromdate."' and '".$this->Todate."'  and store='".$this->StoreCode."' and (doctyp=19)");
        
        $this->procedure .="  select @Salesfqty = isnull(@fulqty,0) + isnull(@loosqty,0) 
	

	select  @Totalfqty = ISNULL(@Openfqty,0)+ISNULL(@Purchasesfqty,0) + ISNULL(@openlsqty,0)+ISNULL(@purchaselsqty,0)
	

	select @fulqty=sum(fulqty*partperunit),@loosqty=sum(loosqty) from stockledger  where itemcode=@itemcode "; 
        $this->procedure .=($this->StoreCode=='AllStores')?" and date <= '".$this->Todate."' ":  " and date <='".$this->Todate."' and store= '".$this->StoreCode."'";
	
        $this->procedure .="select @Closingfqty = isnull(@fulqty,0) + isnull(@loosqty,0)
	
	
	insert into #inventory ([itemcode],[stockname],[averagestock],[fulqtyName],
        [openstock_f],[Purchases_f],[Prod_f],[Total_f],[Sales_f],[Work_f],[Close_f])
	values (@itemcode,@stockname,@ave,@fulldesc ,@Openfqty,@Purchasesfqty,
	 @Prodfqty,@Totalfqty,@Salesfqty,@Worderqty,@Closingfqty)


	FETCH NEXT FROM  Sstock into @itemcode,@stockname,@ave
	END
	CLOSE  Sstock
	DEALLOCATE  Sstock

	select itemcode,stockname,averagestock,fulqtyName,openstock_f,Purchases_f,[Prod_f],Total_f, Sales_f, [Work_f], Close_f from  #inventory

       END";
        
        

      return  $this->procedure;
      }
  
      
      function dropprocedure($procedurename){
        global $db;

          $SQL="IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[".$procedurename."]') AND type in (N'P', N'PC'))
                DROP PROCEDURE [dbo].[".$procedurename."]";
         DB_query($SQL,$db);

        }
      
      
      function EXECUTE(){
          Global $db;
          
          $this->dropprocedure('InventoryReport');
          $reslts=$this->CreateScript();
          $Results=DB_query($reslts,$db);
          $Results=DB_query('[dbo].[InventoryReport]',$db);
          
          Return  $Results;
      }
      
      
  }
  
function Getheader(){
    Global $PageNumber,$pdf,$XPos,$YPos,$Page_Width,$Right_Margin,$FontSize,$firstrowpos,$lastrow,$Bottom_Margin;
    Global $Page_Height,$Top_Margin,$line_height;
// The $PageNumber variable is initialised in 0 by PDFStarter.php
// This page header increments variable $PageNumber before printing it.
$PageNumber++;
// Inserts a page break if it is not the first page
if ($PageNumber>1) {
    $pdf->newPage();
}
// Print 'Quotation' title
// $XPos = 361; 
$XPos=46;
$pdf->addTextWrap($Page_Width-$Right_Margin-220, $Page_Height-$Top_Margin-$FontSize * 1, 200, $FontSize, _('Page').': '.$PageNumber, 'right');

// Print company info
$XPos = 60;
$YPos = ($Page_Height-$Top_Margin-$FontSize * 2)-30;
$FontSize=12;
$pdf->addText($XPos, $YPos, $FontSize, htmlspecialcharsLocal_decode($_SESSION['CompanyRecord']['coyname']));
$FontSize =10;
$pdf->addText($XPos, $YPos-12, $FontSize, $_SESSION['CompanyRecord']['regoffice1']);
$pdf->addText($XPos, $YPos-21, $FontSize, $_SESSION['CompanyRecord']['regoffice2']);
$pdf->addText($XPos, $YPos-30, $FontSize, $_SESSION['CompanyRecord']['regoffice3'] . ' ' . $_SESSION['CompanyRecord']['regoffice4'] . ' ' . $_SESSION['CompanyRecord']['regoffice5']);
$pdf->addText($XPos, $YPos-39, $FontSize, _('Ph') . ': ' . $_SESSION['CompanyRecord']['telephone'] . ' ' . _('Fax'). ': ' . $_SESSION['CompanyRecord']['fax']);
$pdf->addText($XPos, $YPos-48, $FontSize, $_SESSION['CompanyRecord']['email']);
$pdf->addText($XPos, $YPos-57, $FontSize, _('VAT') . ': ' .$_SESSION['CompanyRecord']['vat']);

$pdf->addText(($Page_Width/2)- $Right_Margin ,$YPos-70,12,_('Stock Balance Report') );
$pdf->addText(($Page_Width/2)- $Right_Margin ,$YPos-90,8,_('From :'). $_POST['fromdate']._(' To :'). $_POST['todate'] );
$pdf->line($Page_Width-$Right_Margin,$YPos-100,$Left_Margin,$YPos-100);

// Print 'Delivery To' info
$XPos = 46;
$YPos -= 120;
$FontSize=12;
$FontSize=10;


$LeftOvers = $pdf->addTextWrap(50, $YPos,100, $FontSize,_('Stock Name'),'left');
$LeftOvers = $pdf->addTextWrap(150, $YPos,80, $FontSize,_('UOM'),'left');
$LeftOvers = $pdf->addTextWrap(230, $YPos,80, $FontSize,_('Average Cost'),'center');
$LeftOvers = $pdf->addTextWrap(400, $YPos,80, $FontSize,_('Closing Stock'),'center');
$LeftOvers = $pdf->addTextWrap(490, $YPos,80, $FontSize ,_('Stock Value'),'center');

$pdf->line($Page_Width-$Right_Margin,($YPos - $line_height),$Left_Margin,($YPos - $line_height) );

$firstrowpos = ($YPos - $line_height) -20 ;
$lastrow = $Bottom_Margin + ($line_height * 2);

$YPos -= (2 * $line_height);

}
      
Function getTankbalanceBYDATE($tank,$DATE){
         global $db;
         $rowsunits="";
     
        $sql=sprintf("select sum([units]) as units 
        from [tanktrans]  where [date]<='%s' AND [tankname]='%s'",$DATE,$tank);
        $ResultIndex = DB_query($sql,$db);
         $dbrows= DB_fetch_row($ResultIndex);
         $rowsunits=$dbrows[0];

              
       return  $rowsunits;
     }
  
>>>>>>> fcf1da09040591d58a8bc754034c8da36e93ca78
?>