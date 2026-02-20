<?php
   /* $Id: ConnectDB_mssql.inc 6310 2014-08-06 14:41:50Z Jonathan Kiranga $ */
define ('LIKE','LIKE');
session_write_close(); //in case a previous session is not closed
session_name('ErpWithCRM');
session_start();
include( '../config.php');

global $db;
// Make sure it IS global, regardless of our context
$database = $_SESSION['DatabaseName'];
$db = odbc_connect("Driver={SQL Server};Server=$host;Database=$database;",$DBUser,$DBPassword);
 //DB wrapper functions to change only once for whole application

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




   if(isset($_GET['filtercustomer'])){
       filtercustomer($_GET['offset'],$_GET['height'],$_GET['filtercustomer']);
   }elseif(isset($_POST['filtercustomer'])){
       filtercustomer($_POST['offset'],$_POST['height'],$_POST['filtercustomer']);
   }
   
   function filtercustomer($offset,$height,$value){
     Global $db;
   
       $SearchString = '%' . str_replace(' ', '%',substr($value,0,2)) . '%';
       
          if(mb_strlen(trim($value))>0){
              $ResultIndex = DB_query("SELECT itemcode,customer,[curr_cod],isnull([salesman],'') as salesman from debtors where inactive=0 "
                     . " and customer " . LIKE  . " '" . $SearchString ."' order by customer", $db);
          } else {
             $ResultIndex = DB_query("SELECT itemcode,customer,[curr_cod],isnull([salesman],'') as salesman "
                     . " from debtors where inactive=0 order by customer", $db);
          }
    
      
    $top  = $offset['top'];
    $left = $offset['left'];
    
    $return= '<div class="finderheader" id="findcustomer" style="top:'. $top .'px; left:'.$left.'px;">'
             . '<b>Search Customers:</b><div class="finder">'
            . '<table id="mycustomersTable" class="table table-bordered">';
            while($row=DB_fetch_array($ResultIndex)){
                $return .= sprintf('<tr><td class="cell" onclick="filtercustomer(\'%s\',\'%s\')">%s</td></tr>', 
                trim($row['itemcode']),trim($row['customer']),trim($row['customer'])) ;
            }
            
            $return .= '</table></div>'
           . '<input type="text" class="myInput" id="mycustomersInput" onkeyup="mycustomersFunction()" autofocus="autofocus" placeholder="Search for names..">'
           . '<input type="button" onclick="filtercustomer(\'\',\'\')" value="Cant find Account"/>
          </div>';
                   
        echo $return;
   }
     
 
   if(isset($_GET['vatrefresh'])){
       vatrefresh($_GET['vatrefresh']);
   }elseif(isset($_POST['vatrefresh'])){
       vatrefresh($_POST['vatrefresh']);
   }
   
function vatrefresh($CustomerID){
       global $db;
$SQL="select 
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[oderdate]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid] ,
      sum(SalesLine.[invoiceamount]) as OrderValue
      from [SalesHeader] join SalesLine on [SalesHeader].[documentno]=SalesLine.[documentno] 
      where [SalesHeader].[documenttype]='10'  
      and [SalesHeader].[customercode]='".$CustomerID."'
      group by 
       [SalesHeader].[documentno]
      ,[SalesHeader].[docdate]
      ,[SalesHeader].[oderdate]
      ,[SalesHeader].[duedate]
      ,[SalesHeader].[customercode]
      ,[SalesHeader].[customername]
      ,[SalesHeader].[currencycode]
      ,[SalesHeader].[salespersoncode]
      ,[SalesHeader].[status]
      ,[SalesHeader].[userid] order by [SalesHeader].[docdate] desc";
    $Result=DB_query($SQL,$db);
    
    $Echo= '<Table class="table table-bordered"  id="salesoderslist"><tr>'
        . '<th>Action</th>'
        . '<th>Date</th>'
        . '<th>Customer <br />ID</th>'
        . '<th>Customer <br /> Name</th>'
        . '<th>Sales <br />Order<br /> Value</th>'
        . '<th>Currency </th>'
        . '<th>Authorisation <br /> Status</th>'
        . '<th>Created <br /> By</th>'
        . '</tr>';
    
  while($row=DB_fetch_array($Result)){
        $Echo .=  '<tr>';
        $Echo .=  sprintf('<td><input type="checkbox" name="ref[%s]" />%s</td>',$row['documentno'],$row['documentno']);
        $Echo .=  sprintf('<td>%s</td>',is_null($row['docdate'])?'':($row['docdate']));
        $Echo .=  sprintf('<td>%s</td>',$row['customercode']);
        $Echo .=  sprintf('<td>%s</td>',$row['customername']);
        $Echo .=  sprintf('<td>%s</td>',number_format($row['OrderValue'],2));
        $Echo .=  sprintf('<td>%s</td>',$row['currencycode']);
        $Echo .=  sprintf('<td>%s</td>',$row['status']==2?'Approved':'');
        $Echo .=  sprintf('<td>%s</td>',$row['userid']);
        $Echo .=  '</tr>';
  }
        
    $Echo .=  '</table>';
    $Echo .=  '<div class="centre">
	<input type="submit" name="confirm" value="' . _('Proceed') . '"
            onclick="return confirm(\''._('Are you sure you wish to create this Credit Note ?').'\');" />
</div>';
    
    echo $Echo ;
}
   
   
   
   if(isset($_GET['Customerfind'])){
       getcustomers($_GET['offset'],$_GET['height'],$_GET['Customerfind']);
   }elseif(isset($_POST['Customerfind'])){
       getcustomers($_POST['offset'],$_POST['height'],$_POST['Customerfind']);
   }
   
   function getcustomers($offset,$height,$value){
     Global $db;
   
       $SearchString = '%' . str_replace(' ', '%',substr($value,0,2)) . '%';
       
          if(mb_strlen(trim($value))>0){
              $ResultIndex = DB_query("SELECT itemcode,customer,[curr_cod],isnull([salesman],'') as salesman from debtors where inactive=0 "
                     . " and customer " . LIKE  . " '" . $SearchString ."' order by customer", $db);
          } else {
             $ResultIndex = DB_query("SELECT itemcode,customer,[curr_cod],isnull([salesman],'') as salesman "
                     . " from debtors where inactive=0 order by customer", $db);
          }
    
      
    $top  =  $offset['top'];
    $left = $offset['left'];
    
    $return= '<div class="finderheader" id="findcustomer" style="top:'. $top .'px; left:'.$left.'px;">'
             . '<b>Search Customers:</b><div class="finder">'
            . '<table id="mycustomersTable" class="table table-bordered">';
            while($row=DB_fetch_array($ResultIndex)){
                $return .= sprintf('<tr><td class="cell" onclick="selectcustomer(\'%s\',\'%s\',\'%s\',\'%s\')">%s</td></tr>', 
                trim($row['itemcode']),trim($row['customer']),trim($row['curr_cod']),trim($row['salesman']),trim($row['customer'])) ;
            }
            
            $return .= '</table></div>'
           . '<input type="text" class="myInput" id="mycustomersInput" onkeyup="mycustomersFunction()" autofocus="autofocus" placeholder="Search for names..">'
           . '<input type="button"  onclick="selectcustomer(\'\',\'\',\'\',\'not\')" value="Cant Find Account"/>
          </div>';
                   
        echo $return;
   }
     
        
        
  if(isset($_GET['Chartfind'])){
       Chartfind($_GET['offset'],$_GET['height'],$_GET['Chartfind']);
   }elseif(isset($_POST['Chartfind'])){
       Chartfind($_POST['offset'],$_POST['height'],$_POST['Chartfind']);
   }
   
   function Chartfind($offset,$height,$value){
    Global $db;
    
    $AccountType=array();
    $AccountType[0]="Posting";
    $AccountType[1]="Heading";
    $AccountType[2]="Total";
    $AccountType[3]="Begin-Total";
    $AccountType[4]="End-Total";
    
    $BalanceSheet=array();
    $BalanceSheet[0]="Balance Sheet";
    $BalanceSheet[1]="Profit and Loss";
               
     if($value=='reload'){
         $java ='ReloadForm(Journal.update);';
             $ResultIndex=DB_query("Select accno,accdesc,ReportCode,ReportStyle,[balance_income] 
              from acct join GLpostinggroup on GLpostinggroup.code=acct.postinggroup
              order by ReportCode,accdesc",$db);
     }else{
         $java='';
         $ResultIndex=DB_query("Select accno,accdesc,ReportCode,ReportStyle,[balance_income] from acct order by ReportCode,accdesc",$db);
     }
     
    $top  =  $offset['top'];
    $left = $offset['left'];
    
    $return= '<div class="finderheader" id="findSchart" style="top:'. $top .'px; left:'.$left.'px;">'
             . '<b>Search for Account:</b><div class="finder">'
            . '<table id="myAccountTable" class="table-bordered">';
    
            while($row=DB_fetch_array($ResultIndex)){
               $accdesc= trim($row['accdesc']);
                if($row['ReportStyle']==0){
                    $toclick=sprintf('<td onclick="selectaccount(\'%s\',\'%s\');%s">%s</td>',trim($row['accno']),$accdesc,$java,$accdesc);
                }else{
                   $toclick=sprintf('<td onclick="alert(\'This Account is not a posting account\');">%s</td>',$accdesc);
                }
             $return .= sprintf('<tr>%s<td>%s</td><td>%s</td></tr>',$toclick,$BalanceSheet[$row['balance_income']],$AccountType[$row['ReportStyle']]) ;
           }
           
    $return .= '</table></div>'
    . '<input type="text" tabindex="1" class="myInput" id="myAccountInput" onkeyup="myAccountFunction()"  autofocus="autofocus" placeholder="Search for account..">'
    . '<input type="button" onclick="findSchart.setAttribute(\'style\',\'visibility:hidden;display:none\');" value="Cancel"/>
   </div>';
 
   echo $return;
 }
   
   
   if(isset($_GET['Vendorfind'])){
       GetVendors($_GET['offset'],$_GET['height'],$_GET['Vendorfind']);
   }elseif(isset($_POST['Vendorfind'])){
       GetVendors($_POST['offset'],$_POST['height'],$_POST['Vendorfind']);
   }
   
   function GetVendors($offset,$height,$value){
        Global $db;
   
       $SearchString = '%' . str_replace(' ', '%',substr($value,0,2)) . '%';
       
          if(mb_strlen(trim($value))>0){
              $ResultIndex = DB_query("SELECT itemcode,customer,[curr_cod] from creditors where inactive=0 "
            . " and customer " . LIKE  . " '" . $SearchString ."' order by customer", $db);
          } else {
             $ResultIndex = DB_query("SELECT itemcode,customer,[curr_cod]  from creditors where inactive=0 order by customer", $db);
          }
    
  
    $top  =  $offset['top'];
    $left = $offset['left'];
    
      $return= '<div class="finderheader" id="findVendor" style="top:'. $top .'px; left:'.$left.'px;">'
             . '<b>Search Venders or Suppliers:</b><div class="finder">'
            . '<table id="myVendorTable" class="table table-bordered">';
  
       while($row=DB_fetch_array($ResultIndex)){
                $return .= sprintf('<tr><td onclick="selectvendor(\'%s\',\'%s\',\'%s\')">%s</td></tr>', 
                trim($row['itemcode']),trim($row['customer']),trim($row['curr_cod']),trim($row['customer'])) ;
            }
            $return .= '</table></div>'
           . '<input type="text" tabindex="1" class="myInput"  id="myVendorInput" onkeyup="myVendorFunction()" autofocus="autofocus" placeholder="Search for names..">'
           . '<input type="button"  onclick="selectvendor(\'\',\'\',\'\',\'not\')" value="Cancel"/>
          </div>';
    
        echo $return;
   }
   

   
   
  if(isset($_GET['stocktransferitemcode'])) {
      ShowStockUOM($_GET['stocktransferitemcode']);
  }elseif(isset($_POST['stocktransferitemcode'])){
      ShowStockUOM($_POST['stocktransferitemcode']);
  }
  
  function ShowStockUOM($stockcode){
      global $db;
      
        $REsults=DB_query("SELECT f.descrip as fulqty, l.descrip as loosqty  "
                . "FROM [stockmaster] left join [unit] f on [stockmaster].[units]=f.code "
                . "  left join [unit] l on [stockmaster].[units]=l.code "
                . " where itemcode= '".$stockcode."'", $db);
        
        $rows= DB_fetch_row($REsults);
        $UOMline = '<option value="fulqty" >'.$rows[0].'</option>'
                 . '<option value="loosqty" >'.$rows[1].'</option>';
        
        echo $UOMline;
        
  }
   
  
  
   
   if(isset($_GET['Jobfind'])){
      Jobfind($_GET['offset'],$_GET['height'],$_GET['Jobfind']);
   }elseif(isset($_POST['Jobfind'])){
      Jobfind($_POST['offset'],$_POST['height'],$_POST['Jobfind']);
   }
   
function Jobfind($offset,$height,$value){
 Global $db;
         
    $ResultIndex = DB_query("SELECT itemcode,descrip from stockmaster where inactive=0 and (isstock_3=1) order by descrip", $db);
     
    $top  =  $offset['top'];
    $left = $offset['left'];
    
       $return= '<div class="finderheader" id="findJob" style="top:'. $top .'px; left:'.$left.'px;">'
             . '<b>Search for services offered:</b><div class="finder">'
            . '<table id="myStockTable" class="table table-bordered">';
            while($row=DB_fetch_array($ResultIndex)){
             $return .= sprintf('<tr><td onclick="selectservice(\'%s\',\'%s\');CalculateForm(salesform.submit)">%s</td></tr>', 
             trim($row['itemcode']),trim($row['descrip']),trim($row['descrip'])) ;
             }
           $return .= '</table></div>'
           . '<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()"  autofocus="autofocus" placeholder="Search for names..">'
           . '<input type="button"  onclick="selectservice(\'\',\'\')" value="Cancel" />
          </div>';
    
 
   echo $return;
 }
   
 
  if(isset($_GET['Stockfind'])){
       Stockfind($_GET['offset'],$_GET['height'],$_GET['Stockfind']);
   }elseif(isset($_POST['Stockfind'])){
       Stockfind($_POST['offset'],$_POST['height'],$_POST['Stockfind']);
   }
   
   function Stockfind($offset,$height,$value){
    Global $db;
    
    $top  = $offset['top']-250;
    $left = $offset['left'];
   
     $ResultIndex = DB_query("SELECT itemcode,stockmaster.descrip,isnull(sellingprice,0) as sellingprice, "
               . "[unit].[descrip] as [UOM] from stockmaster "
               . "left join [unit] on [stockmaster].[units]=[unit].[code] where inactive=0 "
               . "order by stockmaster.descrip", $db);
    
       $return= '<div class="finderheader" id="findStock" style="top:'. $top .'px; left:'.$left.'px;">'
             . '<b>Search for Inventory:</b><div class="finder">'
            . '<table id="myStockTable" class="table table-bordered">';
            while($row=DB_fetch_array($ResultIndex)){
    $return .= sprintf('<tr><td onclick="selectInventory(\'%s\',\'%s\');'
            . 'CalculateForm(salesform.submit)">%s</td>'
            . '<td>%s</td>'
            . '<td class="number">%s</td></tr>',trim($row['itemcode']),
            trim($row['descrip']),trim($row['descrip']),trim($row['UOM']),$row['sellingprice']) ;
    }
           $return .= '</table></div>'
           . '<input type="text" tabindex="1" class="myInput" id="myStockInput" onkeyup="myStockFunction()"  autofocus="autofocus" placeholder="Search for names..">'
           . '<input type="button" onclick="selectInventory(\'\',\'\')" value="Cancel" />
          </div>';
    
 
   echo $return;
 }
   
   if(isset($_GET['Assetfind'])){
       Assetfind($_GET['offset'],$_GET['height'],$_GET['Assetfind']);
   }elseif(isset($_POST['Assetfind'])){
       Assetfind($_POST['offset'],$_POST['height'],$_POST['Assetfind']);
   }
   
   function Assetfind($offset,$height,$value){
        Global $db;
     
    $ResultIndex = DB_query("SELECT [assetid],[description],[longdescription]  from fixedassets  order by description", $db);
    $top  = $height + $offset['top'];
    $left = $offset['left'];
    
    
         $return= '<div class="finderheader" id="AssetStock" style="top:'. $top .'px; left:'.$left.'px;">'
            . '<b>Search Assets and equipment:</b><div class="finder">'
            . '<table id="myAssetTable" class="table table-bordered">';
            while($row=DB_fetch_array($ResultIndex)){
            $return .= sprintf('<tr><td onclick="selectFixedassets(\'%s\',\'%s\');CalculateForm(salesform.submit)"><label>%s</label></td></tr>', 
            trim($row['assetid']),trim($row['description']),trim($row['description'])) ;
            }
           $return .= '</table></div>'
           . '<input type="text" class="myInput"  id="myAssetInput" onkeyup="myAssetFunction()" autofocus="autofocus" placeholder="Search for names..">'
           . '<input type="button"  onclick="selectFixedassets(\'\',\'\')" value="Cancel"/>
          </div>';
      
        echo $return;
   }
   

   
   
  if(isset($_GET['EmployeeNamefind'])){
       EmployeeNamefind($_GET['offset'],$_GET['height'],$_GET['EmployeeNamefind']);
   }elseif(isset($_POST['EmployeeNamefind'])){
       EmployeeNamefind($_POST['offset'],$_POST['height'],$_POST['EmployeeNamefind']);
   }
   
 function EmployeeNamefind($offset,$height,$value){
    Global $db;
         
    $ResultIndex = DB_query("SELECT code,salesman from productionEmployee", $db);
    $top  =  $offset['top'];
    $left = $offset['left'];
  
    $return= '<div class="finderheader" id="EmployeeNamefind" style="top:'. $top .'px; left:'.$left.'px;">'
            . '<b>Search for employee:</b><div class="finder">'
            . '<table id="myEmployeeTable" class="table table-bordered">';
            while($row=DB_fetch_array($ResultIndex)){
            $return .= sprintf('<tr><td onclick="selectemployee(\'%s\',\'%s\');CalculateForm(salesform.submit)">'
                    . '%s</td></tr>', trim($row['code']),trim($row['salesman']),trim($row['salesman'])) ;
            }
           $return .= '</table></div>'
           . '<input type="text" tabindex="1" class="myInput" id="myEmployeeInput" onkeyup="myEmployeeFunction()" autofocus="autofocus" placeholder="Search for names..">'
           . '<input type="button"  onclick="selectemployee(\'\',\'\')" value="Cancel" />
          </div>';
   
   echo $return;
 }
   
 
 
if(isset($_GET['Checkwhenpaid'])){
       Checkwhenpaid();
}elseif(isset($_POST['Checkwhenpaid'])){
       Checkwhenpaid();
}
   
Function Checkwhenpaid(){
      Global $db;
     
      $sqlarray = array();
        $ResultIndex = DB_query("SELECT [Accountno],[JournalNo] FROM  CustomerStatement", $db);
        while($rows= DB_fetch_array($ResultIndex)){
            $sqlarray[] =$rows;
        }
        
        foreach ($sqlarray as $value) {
            $ResultIndex = DB_query(sprintf("dbo.checkInvoiceWhenpaid @accountno ='%s' , @journalno='%s' ",
                    $value['Accountno'],$value['JournalNo']), $db);
        }
   echo "Done ";
}
 
?>