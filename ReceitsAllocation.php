<<<<<<< HEAD
<?php
include('includes/session.inc');
$Title = _('Customer Receipts Allocation');
include('includes/header.inc');  
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.

    $FR = new FinancialPeriods();
    
    if(isset($_POST['cancel'])){
        unset($_POST);
    }
    
    if(isset($_POST['resetaccount'])){
       DB_query("Delete from [ReceiptsAllocation] where  [itemcode]='".$_POST['CustomerID']."'", $db);
    }
    
    if(isset($_POST['Auto'])){
        DB_query("EXEC [dbo].[autoallocatedebtors]  @accountno ='".$_POST['CustomerID']."'", $db);
    }
 
    echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Customer Receipts Allocation') .'" alt="" />' . ' ' . _('Customer Receipts Allocation') . '</p>';
    echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
    echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
    if(isset($_POST['CustomerID'])){
      echo  '<input  type="hidden" name="CustomerID" value="'.$_POST['CustomerID'].'"/>';
      echo  '<input  type="hidden" name="CustomerName" value="'.$_POST['CustomerName'].'"/>';
      
      echo '<p class="page_title_text">For Account :'.$_POST['CustomerName'].'</p>';
      
        echo '<Table class="table table-bordered"><tr><th>DATE</th><th>DOC No</th><th>Doc Type</th><th class="number">Amount</th>'
             .'<th class="number">Unallocated</th><th class="number">To Allocate</th></tr>';
        
      $sql="SELECT [Date]
      ,[Documentno]
      ,(select systypes_1.typename from systypes_1 where systypes_1.typeid=CustomerStatement.Documenttype)  as doctypes
      ,[Accountno]
      ,[Grossamount]
      ,[JournalNo]
      ,([Grossamount]+ isnull((SELECT sum([amount]) FROM [ReceiptsAllocation] 
         where [itemcode]=[CustomerStatement].[Accountno] 
          and [invoiceno]=[CustomerStatement].[Documentno] 
          and [journalno]=[CustomerStatement].[JournalNo]),0)) as Pamount
      FROM [CustomerStatement] 
      where [CustomerStatement].[Accountno]='".$_POST['CustomerID']."'";

      $ResultIndex=DB_query($sql,$db);
      while($row=DB_fetch_array($ResultIndex)){
          $maxamount = $row['Pamount'];
          if($maxamount>0){
                echo sprintf('<tr>'
                           . '<td>%s</td>'
                           . '<td>%s</td>'
                           . '<td>%s</td>'
                           . '<td><input type="text" class="number" value="%f" readonly="readonly"/></td>'
                           . '<td><input type="text" class="number" value="%f" name="Minus['.$row['JournalNo'].']" readonly="readonly"/></td>'
                           . '<td></td>'
                           . '</tr>',ConvertSQLDate($row['Date']),
                           $row['Documentno'],
                           $row['doctypes'],
                           $row['Grossamount'],
                           $maxamount);
            }
      }

      echo '<tr><td colspan="4"><input type="submit" name="Auto" value="Auto Allocate"/>'
      . '<input type="submit" name="resetaccount" value="RESET_Account"/></td>'
      . '<td><input type="submit" name="cancel" value="Select Another account"/></td></tr>';
      echo '</table>';
      
    } else {

    echo '<Table class="table table-bordered">';
    echo '<tr><td>Customer ID</td>'
        . '<td><input type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchcustomer" value="Search Customer"/>'
        . '<input type="hidden" name="salespersoncode" id="salespersoncode" value=""/>'
        . '<input type="hidden" name="currencycode" id="currencycode" value=""/></td></tr>'
        . '<tr><td>Customer Name</td>'
        . '<td><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" /></td></tr>';
   
    echo '<tr><td></td><td><input type="submit" name="Submit" value="Select Account"/>'
    . '</td></tr>'
       . '</table>';
  
    }
    
   echo '</div></form>';

   include('includes/footer.inc');
   
    Function CreateAllocation(){
 
$output="
Create PROCEDURE [dbo].[autoallocatedebtors]
	 @accountno varchar(20) 
AS
BEGIN

Declare @amountreceipt decimal(18,2),@amountinvoice decimal(18,2),@amountleft decimal(18,2),@journalnoinv varchar(20),@journalreceipt varchar(20)
Declare @Receiptno varchar(20)


DECLARE receipts_cursor CURSOR FOR  
SELECT  
   ([grossamount]+isnull(dbo.ReceiptAllocations([CustomerStatement].[JournalNo]),0)) as AMOUNT2, 
   [JournalNo],
   [Documentno]
   FROM CustomerStatement 
where [Accountno]=@accountno 
and ([Grossamount]+isnull(dbo.ReceiptAllocations([CustomerStatement].[JournalNo]),0))<0
Order by Date



    OPEN  receipts_cursor
	FETCH NEXT FROM  receipts_cursor into @amountreceipt,@journalreceipt,@Receiptno
	WHILE (@@FETCH_STATUS = 0)
	BEGIN

	 
	   /* create second cursor */

	   DECLARE invoices_cursor CURSOR FOR  
		SELECT  
		([grossamount]+isnull(dbo.InvoiceAllocations([CustomerStatement].[JournalNo]),0)) as amount2, 
		 [JournalNo]
		FROM  CustomerStatement 
		where [Accountno]= @accountno 
		and ([CustomerStatement].[grossamount] + isnull(dbo.InvoiceAllocations([CustomerStatement].[JournalNo]),0)  )>0 order by Date
   
     /* open second cursor */

		 open invoices_cursor
		 FETCH NEXT FROM  invoices_cursor into @amountinvoice,@journalnoinv
	     WHILE (@@FETCH_STATUS = 0)
	     BEGIN
			
				if((@amountreceipt  * -1) > @amountinvoice) 
				 begin
						insert into [ReceiptsAllocation]
                        ([itemcode],[date],[invoiceno],[journalno],[doctype],[receiptno],[amount],[receiptjournal]) 
                         (Select [Accountno],[Date],Documentno,[JournalNo],Documenttype,@Receiptno,(@amountinvoice * -1),@journalreceipt 
                         from [CustomerStatement] where [JournalNo]=@journalnoinv) 

						set  @amountleft= @amountreceipt+@amountinvoice
						set  @amountreceipt=@amountleft
				end
				else 
				begin 

						 insert into [ReceiptsAllocation]
                        ([itemcode],[date],[invoiceno],[journalno],[doctype],[receiptno],[amount],[receiptjournal]) 
                         (Select [Accountno],[Date],Documentno,[JournalNo],Documenttype,@Receiptno,@amountreceipt,@journalreceipt 
                         from [CustomerStatement] where [JournalNo]=@journalnoinv) 

						 set  @amountleft = @amountreceipt+@amountinvoice
						 set  @amountreceipt=@amountleft

		 
		
				end

				print @amountreceipt

			

			/* end of the second cursors loop*/	

		FETCH NEXT FROM  invoices_cursor into @amountinvoice,@journalnoinv
		END
		CLOSE  invoices_cursor
		DEALLOCATE  invoices_cursor

	/* end of the first cursors loop*/	


	FETCH NEXT FROM  receipts_cursor into @amountreceipt,@journalreceipt,@Receiptno
	END
	CLOSE  receipts_cursor
	DEALLOCATE  receipts_cursor

END

";


   }

=======
<?php
include('includes/session.inc');
$Title = _('Customer Receipts Allocation');
include('includes/header.inc');  
include('includes/chartbalancing.inc'); // To get the currency name from the currency code.

    $FR = new FinancialPeriods();
    
    if(isset($_POST['cancel'])){
        unset($_POST);
    }
    
    if(isset($_POST['resetaccount'])){
       DB_query("Delete from [ReceiptsAllocation] where  [itemcode]='".$_POST['CustomerID']."'", $db);
    }
    
    if(isset($_POST['Auto'])){
        DB_query("EXEC [dbo].[autoallocatedebtors]  @accountno ='".$_POST['CustomerID']."'", $db);
    }
 
    echo '<p class="page_title_text"><img src="'.$RootPath.'/css/'.$Theme.'/images/sales.png" title="' . _('Customer Receipts Allocation') .'" alt="" />' . ' ' . _('Customer Receipts Allocation') . '</p>';
    echo '<form autocomplete="off"action="'. htmlspecialchars($_SERVER['PHP_SELF'],ENT_QUOTES,'UTF-8') .'" method="post"><input autocomplete="false" name="hidden" type="text" style="display:none;"><div>';
    echo '<input type="hidden" name="FormID" value="'. $_SESSION['FormID'] .'"/>';
    if(isset($_POST['CustomerID'])){
      echo  '<input  type="hidden" name="CustomerID" value="'.$_POST['CustomerID'].'"/>';
      echo  '<input  type="hidden" name="CustomerName" value="'.$_POST['CustomerName'].'"/>';
      
      echo '<p class="page_title_text">For Account :'.$_POST['CustomerName'].'</p>';
      
        echo '<Table class="table table-bordered"><tr><th>DATE</th><th>DOC No</th><th>Doc Type</th><th class="number">Amount</th>'
             .'<th class="number">Unallocated</th><th class="number">To Allocate</th></tr>';
        
      $sql="SELECT [Date]
      ,[Documentno]
      ,(select systypes_1.typename from systypes_1 where systypes_1.typeid=CustomerStatement.Documenttype)  as doctypes
      ,[Accountno]
      ,[Grossamount]
      ,[JournalNo]
      ,([Grossamount]+ isnull((SELECT sum([amount]) FROM [ReceiptsAllocation] 
         where [itemcode]=[CustomerStatement].[Accountno] 
          and [invoiceno]=[CustomerStatement].[Documentno] 
          and [journalno]=[CustomerStatement].[JournalNo]),0)) as Pamount
      FROM [CustomerStatement] 
      where [CustomerStatement].[Accountno]='".$_POST['CustomerID']."'";

      $ResultIndex=DB_query($sql,$db);
      while($row=DB_fetch_array($ResultIndex)){
          $maxamount = $row['Pamount'];
          if($maxamount>0){
                echo sprintf('<tr>'
                           . '<td>%s</td>'
                           . '<td>%s</td>'
                           . '<td>%s</td>'
                           . '<td><input type="text" class="number" value="%f" readonly="readonly"/></td>'
                           . '<td><input type="text" class="number" value="%f" name="Minus['.$row['JournalNo'].']" readonly="readonly"/></td>'
                           . '<td></td>'
                           . '</tr>',ConvertSQLDate($row['Date']),
                           $row['Documentno'],
                           $row['doctypes'],
                           $row['Grossamount'],
                           $maxamount);
            }
      }

      echo '<tr><td colspan="4"><input type="submit" name="Auto" value="Auto Allocate"/>'
      . '<input type="submit" name="resetaccount" value="RESET_Account"/></td>'
      . '<td><input type="submit" name="cancel" value="Select Another account"/></td></tr>';
      echo '</table>';
      
    } else {

    echo '<Table class="table table-bordered">';
    echo '<tr><td>Customer ID</td>'
        . '<td><input type="text" name="CustomerID" id="CustomerID" value="'.$_POST['CustomerID'].'"  size="5" readonly="readonly"  required="required" />'
        . '<input type="button" id="searchcustomer" value="Search Customer"/>'
        . '<input type="hidden" name="salespersoncode" id="salespersoncode" value=""/>'
        . '<input type="hidden" name="currencycode" id="currencycode" value=""/></td></tr>'
        . '<tr><td>Customer Name</td>'
        . '<td><input tabindex="5" type="text" name="CustomerName" id="CustomerName" value="'.$_POST['CustomerName'].'"  size="50"  required="required" /></td></tr>';
   
    echo '<tr><td></td><td><input type="submit" name="Submit" value="Select Account"/>'
    . '</td></tr>'
       . '</table>';
  
    }
    
   echo '</div></form>';

   include('includes/footer.inc');
   
    Function CreateAllocation(){
 
$output="
Create PROCEDURE [dbo].[autoallocatedebtors]
	 @accountno varchar(20) 
AS
BEGIN

Declare @amountreceipt decimal(18,2),@amountinvoice decimal(18,2),@amountleft decimal(18,2),@journalnoinv varchar(20),@journalreceipt varchar(20)
Declare @Receiptno varchar(20)


DECLARE receipts_cursor CURSOR FOR  
SELECT  
   ([grossamount]+isnull(dbo.ReceiptAllocations([CustomerStatement].[JournalNo]),0)) as AMOUNT2, 
   [JournalNo],
   [Documentno]
   FROM CustomerStatement 
where [Accountno]=@accountno 
and ([Grossamount]+isnull(dbo.ReceiptAllocations([CustomerStatement].[JournalNo]),0))<0
Order by Date



    OPEN  receipts_cursor
	FETCH NEXT FROM  receipts_cursor into @amountreceipt,@journalreceipt,@Receiptno
	WHILE (@@FETCH_STATUS = 0)
	BEGIN

	 
	   /* create second cursor */

	   DECLARE invoices_cursor CURSOR FOR  
		SELECT  
		([grossamount]+isnull(dbo.InvoiceAllocations([CustomerStatement].[JournalNo]),0)) as amount2, 
		 [JournalNo]
		FROM  CustomerStatement 
		where [Accountno]= @accountno 
		and ([CustomerStatement].[grossamount] + isnull(dbo.InvoiceAllocations([CustomerStatement].[JournalNo]),0)  )>0 order by Date
   
     /* open second cursor */

		 open invoices_cursor
		 FETCH NEXT FROM  invoices_cursor into @amountinvoice,@journalnoinv
	     WHILE (@@FETCH_STATUS = 0)
	     BEGIN
			
				if((@amountreceipt  * -1) > @amountinvoice) 
				 begin
						insert into [ReceiptsAllocation]
                        ([itemcode],[date],[invoiceno],[journalno],[doctype],[receiptno],[amount],[receiptjournal]) 
                         (Select [Accountno],[Date],Documentno,[JournalNo],Documenttype,@Receiptno,(@amountinvoice * -1),@journalreceipt 
                         from [CustomerStatement] where [JournalNo]=@journalnoinv) 

						set  @amountleft= @amountreceipt+@amountinvoice
						set  @amountreceipt=@amountleft
				end
				else 
				begin 

						 insert into [ReceiptsAllocation]
                        ([itemcode],[date],[invoiceno],[journalno],[doctype],[receiptno],[amount],[receiptjournal]) 
                         (Select [Accountno],[Date],Documentno,[JournalNo],Documenttype,@Receiptno,@amountreceipt,@journalreceipt 
                         from [CustomerStatement] where [JournalNo]=@journalnoinv) 

						 set  @amountleft = @amountreceipt+@amountinvoice
						 set  @amountreceipt=@amountleft

		 
		
				end

				print @amountreceipt

			

			/* end of the second cursors loop*/	

		FETCH NEXT FROM  invoices_cursor into @amountinvoice,@journalnoinv
		END
		CLOSE  invoices_cursor
		DEALLOCATE  invoices_cursor

	/* end of the first cursors loop*/	


	FETCH NEXT FROM  receipts_cursor into @amountreceipt,@journalreceipt,@Receiptno
	END
	CLOSE  receipts_cursor
	DEALLOCATE  receipts_cursor

END

";


   }

>>>>>>> fcf1da09040591d58a8bc754034c8da36e93ca78
?>