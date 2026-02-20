<?php


$mysignitories=array(
    'Lab Confirmed By________________________________.  Signature_______________________________',
    'Issued by_______________________________________   Signature_______________________________',
    'Finance Approved by_____________________________   Signature_______________________________.',
    'Loading by______________________________________   Signature_______________________________',
    'Checked by______________________________________   Signature_______________________________',
    'LPO No_________________________________   Invoiced by______________Invoiced No...........',
    'Verified by_____________________________   Signature_____________________________________');


$myGRNSIGNATURES=array(
    'RECEIVED BY STORE____________________________   Signature___________________',
    'SECURITY NAME________________________________.  Signature___________________',
    'ACCOUNTS NAME________________________________   Signature___________________',
    'PURCHASING NAME _____________________________   Signature___________________.',
    'OTHER NAME   ________________________________  Signature____________________');

$Dimesion_one=array();
$Dimesion_two=array();
$Dimesion_three=array();

$_SESSION['SelectObject']=array('dimensionone','dimensiontwo');

$Result = DB_query("SELECT "
        . "[Dim1].[Dimension_type] as D1,"
        . "[Dim2].[Dimension_type] as D2,"
        . "isnull([companies].[DefaultDimension_1],'0') ,"
        . "isnull([companies].[DefaultDimension_2],'0') "
        . "FROM [companies]"
        . "left join DimensionSetUp Dim1 on [companies].[DefaultDimension_1]=Dim1.[id] "
        . "left join DimensionSetUp Dim2 on [companies].[DefaultDimension_2]=Dim2.[id] "
        . "where [companies].[coycode]=1",$db);
$DimensionRow = DB_fetch_row($Result);

$id1 = $DimensionRow[2];
$id2 = $DimensionRow[3];

$_SESSION['Dimesion_one_name']= $DimensionRow[0];
$_SESSION['Dimesion_two_name']= $DimensionRow[1];

DB_free_result($Result);

///////////////////////////////////////////////////////////////////////
$_SESSION['SelectObject']['dimensionone']='<td>'.$_SESSION['Dimesion_one_name'].':</td><td><Select name="DimensionOne"><option></option>'; 
$DRows=DB_query("EXEC [dbo].[OrderTransactionClasses] @id = '".$id1."' ",$db);
while($row = DB_fetch_array($DRows)){
     $styles=(($row['levels']==0)?'':'&nbsp;&nbsp;');
     if(isset($_POST['DimensionOne'])){
     $_SESSION['SelectObject']['dimensionone'] .= '<option  value="'.$row['Code'].'" '.($row['Code']==$_POST['DimensionOne']?'selected="selected"':'').'>'.$styles.$row['Dimension'].'</option>';
    }else{
     $_SESSION['SelectObject']['dimensionone'] .= '<option   value="'.$row['Code'].'" >'.$styles.$row['Dimension'].'</option>';
    }
}

$_SESSION['SelectObject']['dimensionone'] .='</select></td>';
  
////////////////////////////////////////////////////////////


$_SESSION['SelectObject']['dimensiontwo']='<td>'.$_SESSION['Dimesion_two_name'].':</td><td><Select name="DimensionTwo"><option></option>'; 
$DRows=DB_query("EXEC [dbo].[OrderTransactionClasses]  @id = '".$id2."' ",$db);
while($row =DB_fetch_array($DRows)){
     $styles=(($row['levels']==0)?'':'&nbsp;&nbsp;');
     if(isset($_POST['DimensionTwo'])){
     $_SESSION['SelectObject']['dimensiontwo'] .= '<option value="'.$row['Code'].'" '.($row['Code']==$_POST['DimensionTwo']?'selected="selected"':'').'>'.$styles.$row['Dimension'].'</option>';
    }else{
     $_SESSION['SelectObject']['dimensiontwo'] .= '<option value="'.$row['Code'].'" >'.$styles.$row['Dimension'].'</option>';
    }
}

$_SESSION['SelectObject']['dimensiontwo'] .='</select></td>';




/////////////////////////////////////////

/*<select name="cars" id="cars">
    <optgroup label="Swedish Cars">
      <option value="volvo">Volvo</option>
      <option value="saab">Saab</option>
    </optgroup>
    <optgroup label="German Cars">
      <option value="mercedes">Mercedes</option>
      <option value="audi">Audi</option>
    </optgroup>
  </select>*/
$_SESSION['report']['project']='<td>'.$_SESSION['Dimesion_two_name'].':</td><td><Select name="DimensionTwo"><option value="">Admin</option>'; 
$DRows=DB_query("EXEC [dbo].[OrderReportClasses]  @id = '".$id2."'  ",$db);
while($row =DB_fetch_array($DRows)){
     $styles=(($row['levels']==0)?'':'&nbsp;&nbsp;');
     if(isset($_POST['DimensionTwo'])){
     $_SESSION['report']['project'] .= '<option  value="'.$row['Code'].'" '.($row['Code']==$_POST['DimensionTwo']?'selected="selected"':'').'>'.$styles.$row['Dimension'].'</option>';
    }else{
      $_SESSION['report']['project'] .= '<option value="'.$row['Code'].'" >'.$styles.$row['Dimension'].'</option>';
    }
}
$_SESSION['report']['project'] .='</select></td>';

 ///////////////////////////////////////////////////////////////////////


$_SESSION['report']['budget']='<td>'.$_SESSION['Dimesion_one_name'].':</td><td><Select name="DimensionOne"><option value="">Admin</option>'; 
$DRows=DB_query("EXEC [dbo].[OrderReportClasses]  @id = '".$id1."'  ",$db);
while($row =DB_fetch_array($DRows)){
    $styles=(($row['levels']==0)?'':'&nbsp;&nbsp;');
     if(isset($_POST['DimensionOne'])){
     $_SESSION['report']['budget'] .= '<option  value="'.$row['Code'].'" '.($row['Code']==$_POST['DimensionOne']?'selected="selected"':'').'>'.$styles.$row['Dimension'].'</option>';
    }else{
      $_SESSION['report']['budget'] .= '<option  value="'.$row['Code'].'" >'.$styles.$row['Dimension'].'</option>';
    }
}
$_SESSION['report']['budget'] .='</select></td>';





FUNCTION IssqldateGreater($PriodClosedDate,$CurrentDate){
    Global $db;
    
    $SQL="if(DATEDIFF(DAY,'".$PriodClosedDate."','".$CurrentDate."') <0)
        begin
            select 0 
        end
    else
        begin 
            select 1
        end";
    
    $ResultIndex = DB_query($SQL,$db); 
    $rows = DB_fetch_row($ResultIndex);
    
    return $rows[0];
}

function OrderTransactionClasses(){
 Global $db;
   DropConn('OrderTransactionClasses');
  
 $SQL="Create PROCEDURE [dbo].[OrderTransactionClasses]  
     @id int
AS
BEGIN

Create table #dimensions (
[Code] varchar(10) not null,
[Dimension] varchar(50) not null,
[levels] int not null,
[myx] float not null);



        with Classwithsubclasses  as
        (
        select Code,Dimension,parent ,
        cast(row_number()over(partition by parent order by Dimension) as varchar(max)) as [paths],
            0 as levels,
            row_number()over(partition by parent order by Dimension) / power(10.0,0) as myx
        from Dimensions where parent is null and id=@id and [Blocked]=0 
        union all
        select Child.Code,Child.Dimension,Child.parent,
            Pa.[paths] +'-'+ cast(row_number()over(partition by Pa.parent order by Pa.Dimension) as varchar(max)),
            Pa.levels+1,
            Pa.myx + row_number()over(partition by Pa.parent order by Pa.Dimension) / power(10.0,Pa.levels+1)
        from Dimensions Child 
        inner join Classwithsubclasses Pa on Child.parent=Pa.Code
        where id=@id and [Blocked]=0 
        )

  
       insert into #dimensions ([Code],[Dimension],[levels],[myx])
       (select [Code] ,[Dimension],[levels],[myx] from Classwithsubclasses )
     
       select [Code],[Dimension],[levels],[myx] from  #dimensions order by [myx] asc
     
END";

$r=DB_System($SQL,$db);
 }

function OrderReportClasses(){
Global $db;
  DropConn('OrderReportClasses');
   
 $SQL="Create PROCEDURE [dbo].[OrderReportClasses] 
     @id int
AS
BEGIN

Create table #dimensions (
[Code] varchar(10) not null,
[Dimension] varchar(50) not null,
[levels] int not null,
[myx] float not null);



with Classwithsubclasses  as
(
select Code,Dimension,parent,
cast(row_number()over(partition by parent order by Dimension) as varchar(max)) as [paths],
    0 as levels,
    row_number()over(partition by parent order by Dimension) / power(10.0,0) as myx
  from Dimensions where parent is null and id=@id
union all
select Child.Code,Child.Dimension,Child.parent,
    Pa.[paths] +'-'+ cast(row_number()over(partition by Pa.parent order by Pa.Dimension) as varchar(max)),
    Pa.levels+1,
    Pa.myx + row_number()over(partition by Pa.parent order by Pa.Dimension) / power(10.0,Pa.levels+1)
  from Dimensions Child 
inner join Classwithsubclasses Pa on Child.parent=Pa.Code where id=@id
)
    
    insert into #dimensions ([Code],[Dimension],[levels],[myx])
       (select [Code],[Dimension],[levels],[myx] from Classwithsubclasses)
     
       select [Code],[Dimension],[levels],[myx] from  #dimensions order by [myx] asc
END";

 $r=DB_System($SQL,$db);
 }

function DropConn($name){
   Global $db;
     
   $SQL="IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[".$name."]') AND type in (N'P', N'PC'))
   DROP PROCEDURE [dbo].[".$name."] ";

   $r=DB_System($SQL,$db);
}



 function SelectPriceListToUse($stockid,$quantity,$unitscode,$customerid=''){
         global $db;
            $price=''; $return=0; $Dprice='';
          
            if(mb_strlen($customerid)>1){
                $SQL1 = sprintf("select price from PriceList where stockcode='%s' and approved=1 and units_code='%s' and quantity=%f and customerCode='%s'",
                        $stockid,$unitscode,$quantity,$customerid);
                $ResultIndex = DB_query($SQL1,$db);
                $row = DB_fetch_row($ResultIndex);
                $price = $row[0];
            }

            $SQL2 = sprintf("select price from PriceList where stockcode='%s' and units_code='%s' and quantity=%f and (customerCode is null or customerCode='')",
                    $stockid,$unitscode,$quantity);
            $ResultIndex = DB_query($SQL2,$db);
            $row = DB_fetch_row($ResultIndex);
            $Dprice = $row[0];
                
            if($price>0){
                $return=(float) $price;
            }else{
                $return=(float) $Dprice;
            }
            
        return $return;     
     }
    
function getSalemanDescrip($code){
    global $db;
    
$ResultIndex=DB_query(Sprintf("SELECT [salesman],[commission],[inactive]  FROM [salesrepsinfo]  where [code]='%s'",$code), $db);
$row=DB_fetch_row($ResultIndex);
       
return (isset($row[0])?$row[0]:'');
}



 function SelectContainerToUse($stockid,$quantity,$unitscode){
         global $db;
            $price=''; $return=0; $Dprice='';
  
            $SQL2 = sprintf("select isnull(container,'') from PriceList where stockcode='%s' and units_code='%s' and quantity=%f and (customerCode is null or customerCode='')",
                    $stockid,$unitscode,$quantity);
            $ResultIndex = DB_query($SQL2,$db);
            $row = DB_fetch_row($ResultIndex);
            $containerCode = $row[0];
            
        return $containerCode;     
     }
     
     
     function SelectSupplierInvoiceNo($invoice_journal){
         global $db;
        
        $SQL2 = sprintf("SELECT [invref] FROM [creditorsledger] where [journal]='%s'",$invoice_journal);
        $ResultIndex = DB_query($SQL2,$db);
        $row = DB_fetch_row($ResultIndex);
        $containerCode = $row[0];
            
        return $containerCode;     
     } 
?>