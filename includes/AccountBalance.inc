<?php

function dropprocedure($procedurename){
global $db;
    $SQL="IF  EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[".$procedurename."]') AND type in (N'P', N'PC'))
        DROP PROCEDURE [dbo].[".$procedurename."]";
    $r=DB_query($SQL,$db);

}

function DB_AccountBalance(){
    
dropprocedure('AccountBalance');

global $db,$YearPeriod;
    
$procodedure ="Create PROCEDURE [dbo].[AccountBalance]
AS
BEGIN

create table  #chartofaccounts (
    [ReportCode] [varchar](10) NULL,
    [accdesc] [char](30) NOT NULL,
    [balance_income] [int] NULL,
    [ReportStyle] [int] NULL,
    [direct] [bit] NULL,
    [Sale_Purchase_Neither] [int] NULL,
    [accno] [char](20) NULL,
    [debit] float null,
    [credit] float null,
    [debit_Last] float null,
    [credit_Last] float null,
    [Calculation] [varchar](max) NULL
)

	insert into #chartofaccounts ([ReportCode],
	[accdesc],
	[balance_income],
	[ReportStyle],
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit],
	[debit_Last],
	[credit_Last],
	[Calculation])
	SELECT 
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
case acct.[balance_income] 
     when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate <=(select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) 
     when 1 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.") and (select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) 
     end as debit,
	
case acct.[balance_income] 
    when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod."))
     when 1 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.") and (select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) 
     end as credit,
       
 case acct.[balance_income]
     when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate <=(select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) else 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12).") and (select max([end_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12).")) 
     end as debit_Last,

case acct.[balance_income] 
    when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate <=(select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) else 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12).") and (select max([end_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12)."))
    end as credit_Last,
       [Calculation]
	from acct   
	 group by 
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]  
            ORDER BY 
	[ReportCode]
		
    
	select 
	[ReportCode] ,
	[accdesc] ,
	[balance_income],
	[ReportStyle] ,
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit],
        [debit_Last],
        [credit_Last],
	[Calculation] 
        from #chartofaccounts 
        order by [ReportCode] 

END
";

 $r=DB_query($procodedure,$db);

}

function DB_TBalance(){
    
dropprocedure('AccountBalance');

global $db,$YearPeriod;
    
$procodedure ="Create PROCEDURE [dbo].[AccountBalance]
AS
BEGIN

create table  #chartofaccounts (
    [ReportCode] [varchar](10) NULL,
    [accdesc] [char](30) NOT NULL,
    [balance_income] [int] NULL,
    [ReportStyle] [int] NULL,
    [direct] [bit] NULL,
    [Sale_Purchase_Neither] [int] NULL,
    [accno] [char](20) NULL,
    [debit] float null,
    [credit] float null,
    [debit_Last] float null,
    [credit_Last] float null,
    [Calculation] [varchar](max) NULL
)

	insert into #chartofaccounts ([ReportCode],
	[accdesc],
	[balance_income],
	[ReportStyle],
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit],
	[debit_Last],
	[credit_Last],
	[Calculation])
	SELECT 
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
case acct.[balance_income] 
     when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate <=(select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) 
     when 1 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.") and (select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) 
     end as debit,
	
case acct.[balance_income] 
    when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod."))
     when 1 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.") and (select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) 
     end as credit,
       
 case acct.[balance_income]
     when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate <=(select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) else 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12).") and (select max([end_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12).")) 
     end as debit_Last,

case acct.[balance_income] 
    when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate <=(select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) else 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12).") and (select max([end_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12)."))
    end as credit_Last,
       [Calculation]
	from acct   
	 group by 
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]  
            ORDER BY 
	[ReportCode]
		
    
	select 
	[ReportCode] ,
	[accdesc] ,
	[balance_income],
	[ReportStyle] ,
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit],
        [debit_Last],
        [credit_Last],
	[Calculation] 
        from #chartofaccounts where [ReportStyle]=0
        order by [ReportCode] 

END
";

 $r=DB_query($procodedure,$db);

}

function DB_AccountBalance_withfilter($filter=""){
    
dropprocedure('AccountBalance_withfilter');

global $db,$YearPeriod;
    
$procodedure ="Create PROCEDURE [dbo].[AccountBalance_withfilter]
AS
BEGIN

create table  #chartofaccounts (
    [ReportCode] [varchar](10) NULL,
    [accdesc] [char](30) NOT NULL,
    [balance_income] [int] NULL,
    [ReportStyle] [int] NULL,
    [direct] [bit] NULL,
    [Sale_Purchase_Neither] [int] NULL,
    [accno] [char](20) NULL,
    [debit] float null,
    [credit] float null,
    [debit_Last] float null,
    [credit_Last] float null,
    [Calculation] [varchar](max) NULL
)

	insert into #chartofaccounts ([ReportCode],
	[accdesc],
	[balance_income],
	[ReportStyle],
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit],
	[debit_Last],
	[credit_Last],
	[Calculation])
	SELECT 
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
     case acct.[balance_income] when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
        from Generalledger where accountcode=acct.accno and Docdate <(select max([end_date]) 
        from FinancialPeriods  where periodno=".$YearPeriod.")) 
        when 1 then
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
        from Generalledger where accountcode=acct.accno and Docdate between (select min([start_date]) 
        from FinancialPeriods  where periodno=".$YearPeriod.") and (select max([end_date]) "
        . " from FinancialPeriods  where periodno=".$YearPeriod.")) 
            end as debit,
	
    case acct.[balance_income] when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod."))
        when 1 then
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.") and (select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) 
            end as credit,
       
     case acct.[balance_income] when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate <=(select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) else 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12).") and (select max([end_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12).")) 
            end as debit_Last,

	case acct.[balance_income] when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate <=(select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) else 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12).") and (select max([end_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12)."))
            end as credit_Last,
       
	    [Calculation]
	from acct  where accdesc like '%'+'".$filter."'+'%'  
	 group by 
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]  ORDER BY 
	[ReportCode]
		
    
	select 
	[ReportCode] ,
	[accdesc] ,
	[balance_income],
	[ReportStyle] ,
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit],
	[Calculation] 
        from #chartofaccounts 
        order by [ReportCode] 

END
";

 $r=DB_query($procodedure,$db);

}

Function DB_Balancesheet(){
    
dropprocedure('AccountBalance');

global $db,$YearPeriod;
    
$procodedure ="Create PROCEDURE [dbo].[AccountBalance]
AS
BEGIN

create table  #chartofaccounts (
    [ReportCode] [varchar](10) NULL,
    [accdesc] [char](30) NOT NULL,
    [balance_income] [int] NULL,
    [ReportStyle] [int] NULL,
    [direct] [bit] NULL,
    [Sale_Purchase_Neither] [int] NULL,
    [accno] [char](20) NULL,
    [debit] float null,
    [credit] float null,
    [debit_Last] float null,
    [credit_Last] float null,
    [Calculation] [varchar](max) NULL
)

	insert into #chartofaccounts ([ReportCode],
	[accdesc],
	[balance_income],
	[ReportStyle],
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit],
	[debit_Last],
	[credit_Last],
	[Calculation])
	SELECT 
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
     case acct.[balance_income] when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate <=(select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) 
        when 1 then
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.") and (select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) 
            end as debit,
	
    case acct.[balance_income] when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod."))
          when 1 then
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.") and (select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) 
            end as credit,
       
     case acct.[balance_income] when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate <=(select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) else 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12).") and (select max([end_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12).")) 
            end as debit_Last,
	case acct.[balance_income] when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate <=(select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod.")) else 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate between (select min([start_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12).") and (select max([end_date]) from FinancialPeriods  where  periodno=".($YearPeriod-12)."))
            end as credit_Last,
  	    [Calculation]
	from acct where [balance_income]=0
	 group by 
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]  
            ORDER BY 
	[ReportCode]
		
    
	select 
	[ReportCode] ,
	[accdesc] ,
	[balance_income],
	[ReportStyle] ,
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit], 
        [debit_Last],
        [credit_Last],
	[Calculation] 
        from #chartofaccounts 
        order by [ReportCode] 

END
";

 $r=DB_query($procodedure,$db);

}

Function DB_Profit_loss(){
    
dropprocedure('DB_Profit_loss');

global $db,$YearPeriod;
    
$procodedure ="Create PROCEDURE [dbo].[DB_Profit_loss]
AS
BEGIN

create table  #chartofaccounts ( [ReportCode] [varchar](10) NULL,
    [accdesc] [char](30) NOT NULL, [balance_income] [int] NULL,
    [ReportStyle] [int] NULL, [direct] [bit] NULL,
    [Sale_Purchase_Neither] [int] NULL, [accno] [char](20) NULL,
    [debit] float null,  [credit] float null,
    [debit_Last] float null,   [credit_Last] float null,
    [Calculation] [varchar](max) NULL
)

	insert into #chartofaccounts ([ReportCode],
	[accdesc],
	[balance_income],
	[ReportStyle],
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit],
	[debit_Last],
	[credit_Last],
	[Calculation])
	SELECT 
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
            case when acct.[balance_income]=0 then 
                    (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
                    from Generalledger where accountcode=acct.accno and Docdate <=(select max([end_date]) 
                    from FinancialPeriods  where periodno=".$YearPeriod.")) 
            else
                    (select  sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
                    from Generalledger where accountcode=acct.accno and Docdate between (select min([start_date]) 
                    from FinancialPeriods  where periodno=".$YearPeriod.") and (select max([end_date]) "
                  . "from FinancialPeriods  where periodno=".$YearPeriod.")) 
          
           end as debit,
	
    case when acct.[balance_income]=0 then 
                (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate <=(select max([end_date]) from FinancialPeriods  where periodno=".$YearPeriod."))
            else 
                (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger 
                where balaccountcode=acct.accno and Docdate between (select min([start_date]) 
                from FinancialPeriods  where periodno=".$YearPeriod.") and (select max([end_date]) "
               . "from FinancialPeriods  where periodno=".$YearPeriod.")) 
            
         end as credit,
       
     case when acct.[balance_income]=0 then 
                (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where accountcode=acct.accno and Docdate <=(select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod."))
            else 
                
                (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger 
                where accountcode=acct.accno and Docdate between (select min([start_date]) 
                from FinancialPeriods  where  periodno=".($YearPeriod-12).") and (select max([end_date]) "
              . "from FinancialPeriods  where  periodno=".($YearPeriod-12).")) 
             
          end as debit_Last,

	case when acct.[balance_income]=0 then 
                (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger where balaccountcode=acct.accno and Docdate <=(select min([start_date]) from FinancialPeriods  where periodno=".$YearPeriod."))
        else 
	       (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger 
                where balaccountcode=acct.accno and Docdate between (select min([start_date]) 
                from FinancialPeriods  where  periodno=".($YearPeriod-12).") and (select max([end_date]) "
              . "from FinancialPeriods  where  periodno=".($YearPeriod-12)."))
             
           end as credit_Last,
           
  	[Calculation]
	from acct where [balance_income]=1
	 group by 
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]  
            ORDER BY 
	[ReportCode]
		
    
	select 
	[ReportCode] ,
	[accdesc] ,
	[balance_income],
	[ReportStyle] ,
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit], 
        [debit_Last],
        [credit_Last],
	[Calculation] 
        from #chartofaccounts 
        order by [ReportCode] 

END
";



 $r=DB_query($procodedure,$db);

}

Function DB_Profit_loss_by_month(){
        
dropprocedure('Profit_loss_by_month');

global $db,$periodno;
    
$procodedure = "Create PROCEDURE [dbo].[Profit_loss_by_month] 
	AS
BEGIN


create table  #chartofaccounts (
	[ReportCode] [varchar](10) NULL,
	[accdesc] [char](30) NOT NULL,
	[balance_income] [int] NULL,
	[ReportStyle] [int] NULL,
	[direct] [bit] NULL,
	[Sale_Purchase_Neither] [int] NULL,
	[accno] [char](20) NULL,
	[debit] float null,
	[credit] float null,
	[debit_Last] float null,
	[credit_Last] float null,
	[Calculation] [varchar](max) NULL
)

	insert into #chartofaccounts 
        ([ReportCode],
	[accdesc],
	[balance_income],
	[ReportStyle],
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit],
	[Calculation])
	SELECT 
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno], 
        case acct.[balance_income] when 0 then 
            (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
            from Generalledger where accountcode=acct.accno and period <='".$periodno."')
	else 
           (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
            from Generalledger where accountcode=acct.accno and period='". $periodno ."') 
        end  
        as debit,

       case when acct.[balance_income]=0 then 
            (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
            from Generalledger where balaccountcode=acct.accno and period <='".$periodno."')
	else 
            (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
            from Generalledger where balaccountcode=acct.accno and period='".$periodno."') 
              
        end
        as credit,
        [Calculation]
	from acct   
	where [balance_income]=1
	group by 
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]  
	ORDER BY 
	[ReportCode]
		
    
	select 
	[ReportCode] ,
	[accdesc] ,
	[balance_income],
	[ReportStyle] ,
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit],
	[debit_Last] ,
	[credit_Last] ,
	[Calculation] from #chartofaccounts order by [ReportCode] 

END
";
    

 $r=DB_query($procodedure,$db);

}

Function DB_Profit_loss_Project(){
    
dropprocedure('DB_Profit_loss_Project');

global $db,$YearPeriod,$ProjectSelected;
    
     
$procodedure ="Create PROCEDURE [dbo].[DB_Profit_loss_Project]
AS
BEGIN

create table  #chartofaccounts ( [ReportCode] [varchar](10) NULL,
    [accdesc] [char](30) NOT NULL, [balance_income] [int] NULL,
    [ReportStyle] [int] NULL, [direct] [bit] NULL,
    [Sale_Purchase_Neither] [int] NULL, [accno] [char](20) NULL,
    [debit] float null,  [credit] float null,
    [debit_Last] float null,   [credit_Last] float null,
    [Calculation] [varchar](max) NULL
)


        with Classwithsubclasses as
        (
        select Code,Dimension,parent ,
        cast(row_number()over(partition by parent order by Dimension) as varchar(max)) as [paths],
            0 as levels,
            row_number()over(partition by parent order by Dimension) / power(10.0,0) as myx
        from Dimensions where Code='".$ProjectSelected."'  
        union all
        select Child.Code,Child.Dimension,Child.parent,
            Pa.[paths] +'-'+ cast(row_number()over(partition by Pa.parent order by Pa.Dimension) as varchar(max)),
            Pa.levels+1,
            Pa.myx + row_number()over(partition by Pa.parent order by Pa.Dimension) / power(10.0,Pa.levels+1)
        from Dimensions Child 
        inner join Classwithsubclasses Pa on Child.parent=Pa.Code
        )



	insert into #chartofaccounts ([ReportCode],
	[accdesc],
	[balance_income],
	[ReportStyle],
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit],
	[debit_Last],
	[credit_Last],
	[Calculation])
	SELECT 
        [ReportCode],
        [accdesc],
        [balance_income],
        [ReportStyle],
        [direct],
        [Sale_Purchase_Neither],
        [accno],
            case when acct.[balance_income]=0 then 
                    (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
                    from Generalledger join Classwithsubclasses Pa on Pa.CODE=Generalledger.[dimension2]
                    where accountcode=acct.accno and Docdate <=(select max([end_date]) 
                    from FinancialPeriods  where periodno=".$YearPeriod.")) 
            else
                    (select  sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
                    from Generalledger  join Classwithsubclasses Pa on Pa.CODE=Generalledger.[dimension2]
                    where accountcode=acct.accno  and Docdate between (select min([start_date]) 
                    from FinancialPeriods  where periodno=".$YearPeriod.") and (select max([end_date]) "
                  . "from FinancialPeriods  where periodno=".$YearPeriod.")) 
          
           end as debit,
	
    case when acct.[balance_income]=0 then 
                (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
                from Generalledger  join Classwithsubclasses Pa on Pa.CODE=Generalledger.[dimension2]
                where balaccountcode=acct.accno  and Docdate <=(select max([end_date]) 
                from FinancialPeriods  where periodno=".$YearPeriod."))
            else 
                (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger 
                join Classwithsubclasses Pa on Pa.CODE=Generalledger.[dimension2]
                where balaccountcode=acct.accno  and Docdate between (select min([start_date]) 
                from FinancialPeriods  where periodno=".$YearPeriod.") and (select max([end_date]) 
               from FinancialPeriods  where periodno=".$YearPeriod.")) 
            
         end as credit,
       
     case when acct.[balance_income]=0 then 
                (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
                from Generalledger  join Classwithsubclasses Pa on Pa.CODE=Generalledger.[dimension2] 
                where accountcode=acct.accno  and Docdate <=(select min([start_date]) 
                from FinancialPeriods  where periodno=".$YearPeriod."))
            else 
                
                (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger 
                join Classwithsubclasses Pa on Pa.CODE=Generalledger.[dimension2] 
                where accountcode=acct.accno and Docdate between (select min([start_date]) 
                from FinancialPeriods  where  periodno=".($YearPeriod-12).") and (select max([end_date]) "
              . "from FinancialPeriods  where  periodno=".($YearPeriod-12).")) 
             
          end as debit_Last,

	case when acct.[balance_income]=0 then 
                (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
                from Generalledger  join Classwithsubclasses Pa on Pa.CODE=Generalledger.[dimension2] 
                where balaccountcode=acct.accno  and Docdate <=(select min([start_date]) 
                from FinancialPeriods  where periodno=".$YearPeriod."))
        else 
	       (select sum(Generalledger.amount * Generalledger.[ExchangeRate]) from Generalledger 
               join Classwithsubclasses Pa on Pa.CODE=Generalledger.[dimension2]
                where balaccountcode=acct.accno and Docdate between (select min([start_date]) 
                from FinancialPeriods  where  periodno=".($YearPeriod-12).") and (select max([end_date]) "
              . "from FinancialPeriods  where  periodno=".($YearPeriod-12)."))
             
           end as credit_Last,
           
  	[Calculation]
	from acct where [balance_income]=1
	 group by 
            [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]  
            ORDER BY 
	[ReportCode]
		
    
	select 
	[ReportCode] ,
	[accdesc] ,
	[balance_income],
	[ReportStyle] ,
	[direct],
	[Sale_Purchase_Neither],
	[accno],
	[debit],
	[credit], 
        [debit_Last],
        [credit_Last],
	[Calculation] 
        from #chartofaccounts 
        order by [ReportCode] 

END
";



 $r=DB_query($procodedure,$db);

}

Function DB_House_cost($ProjectSelected){
    dropprocedure('DB_House_cost');

global $db,$YearPeriod;
    
     
$procodedure ="create PROCEDURE [dbo].[DB_House_cost]
AS
BEGIN
	
create table  #Projects (
    [Code] [varchar](10) NULL,[BudgetName] [char](100) NOT NULL,[BudgetAmount]  decimal(18,2) NULL,[Expeses]  decimal(18,2) NULL,
    [Committed]  decimal(18,2)NULL,[Total]  decimal(18,2) NULL,[Balance] decimal(18,2) NULL,[percent] decimal(18,2) null
)

Declare @code varchar(10),@budgetname varchar(100),@budgetamount float,@expensed float,@committed float,@balance float,@percent float
Declare @datefrom date,@dateto date ,@stockfrom varchar(10),@stockto varchar(10)


Declare CursorDimensions cursor for SELECT Dimensions.code,Dimensions.Dimension from Dimensions
where Dimensions.code='".$ProjectSelected."' or Dimensions.parent='".$ProjectSelected."'

	open CursorDimensions
	fetch next from CursorDimensions into @code,@budgetname
	WHILE (@@FETCH_STATUS = 0)
	BEGIN 
                SELECT @committed=sum(PurchaseLine.invoiceamount) FROM [PurchaseHeader] join PurchaseLine on PurchaseHeader.documenttype=PurchaseLine.documenttype  and PurchaseHeader.documentno=PurchaseLine.documentno
                where PurchaseHeader.released=1 and PurchaseHeader.documenttype=18  and (PurchaseHeader.Dimension_2=@code)

                select @expensed=(select sum(Generalledger.amount) from Generalledger  where Generalledger.dimension2= @code and  Generalledger.[accountcode] in (select [accno] from [acct] where [balance_income]=1))

                insert into #Projects ([Code],[BudgetName],[Expeses],[Committed],[Total]) (select @code,@budgetname,@expensed,@committed,(isnull(@expensed,0)+isnull(@committed,0)))

	FETCH NEXT FROM  CursorDimensions into @code,@budgetname
	END
	CLOSE  CursorDimensions
	DEALLOCATE  CursorDimensions

	select [Code],[BudgetName],sum([BudgetAmount]) as BudgetAmount,sum([Expeses]) as Expeses,sum([Committed]) as Committed, sum([Total]) as Total,sum([Balance]) as Balance,sum([percent]) as [percent] from #Projects
        group by [Code],[BudgetName]
	
END";

 $r=DB_query($procodedure,$db);

}

function DB_FixedAssets(){
    
dropprocedure('DB_FixedAssets');

global $db,$YearPeriod;
    
$procodedure ="create PROCEDURE [dbo].[DB_FixedAssets]
AS
BEGIN
	
create table  #fixedassets ([assetid] int NOT NULL,[description] [char](100) NULL,
    [longdescription] [char](100) NULL,[assetcategoryid] varchar(10) NULL,[categorydescription] [char](100) NULL,
    [serialno] [char](100) NULL,[locationdescription] [char](100) NULL,[datepurchased] datetime NULL,
    [costtotal] float null,[depnbfwd] float null,[depntype] char(5) null,[depnrate] float null )


Declare @assetid int ,@description [char](100),@longdescription [char](100),@assetcategoryid varchar(10),
    @serialno [char](100) ,@locationdescription [char](100),@datepurchased datetime,
    @parentlocationid int ,@assetlocation [char](100),@disposaldate datetime ,
    @depntype char(5),@depnrate float,@categorydescription varchar(100)


Declare CursorFassets cursor for 
SELECT 
fixedassets.assetid,fixedassets.description,fixedassets.longdescription,
fixedassets.assetcategoryid,fixedassets.serialno,fixedassets.assetlocation,
fixedassets.depntype,fixedassets.depnrate
FROM fixedassets
WHERE fixedassets.disposaldate IS NULL 
            
open CursorFassets
fetch next from CursorFassets into @assetid ,@description,@longdescription ,@assetcategoryid , @serialno ,@assetlocation,@depntype,@depnrate 
WHILE (@@FETCH_STATUS = 0)
BEGIN 
Declare @costbfwd float,@depnbfwd float

select @categorydescription=[categorydescription] from [fixedassetcategories] where [categoryid]=@assetcategoryid
   
select top 1 @datepurchased=transdate from fixedassettrans where transdate <='" . FormatDateForSQL($_POST['ProcessDate']) . "' and assetid =@assetid
    
select @costbfwd = sum(amount) from fixedassettrans where transdate <='" . FormatDateForSQL($_POST['ProcessDate']) . "' 
    AND fixedassettranstype='cost' and assetid =@assetid
select @depnbfwd = sum(amount) from fixedassettrans where transdate <='" . FormatDateForSQL($_POST['ProcessDate']) . "' 
    AND fixedassettranstype='depn' and assetid =@assetid

insert into #fixedassets ([assetid],[description],[longdescription],[assetcategoryid],[serialno],[locationdescription],[datepurchased],[costtotal],[depnbfwd],[depntype],[depnrate],categorydescription)
values (@assetid ,@description,@longdescription ,@assetcategoryid , @serialno ,@assetlocation ,@datepurchased,@costbfwd ,@depnbfwd,@depntype,@depnrate,@categorydescription)

fetch next from CursorFassets into @assetid ,@description,@longdescription ,@assetcategoryid , @serialno ,@assetlocation,@depntype,@depnrate 

END
CLOSE  CursorFassets
DEALLOCATE  CursorFassets


        select 
        [assetid],
        [description],
        [longdescription],
        [assetcategoryid],
        [serialno],
        [locationdescription],
        [datepurchased] as transdate,
        [costtotal],
        [depnbfwd],
        [depntype],
        [depnrate],
        categorydescription  
        from #fixedassets 
        order by [assetcategoryid] 
        asc
	
END";

 $r=DB_query($procodedure,$db);

}




function DB_FixedAssetsRegister(){
    
dropprocedure('DB_FixedAssetsRegister');

global $db,$YearPeriod;

$DateFrom = FormatDateForSQL($_POST['FromDate']);
$DateTo = FormatDateForSQL($_POST['ToDate']);
    
$procodedure ="create PROCEDURE [dbo].[DB_FixedAssetsRegister]
AS
BEGIN
	
create table  #fixedassets ([assetid] int NOT NULL,[description] [char](100) NULL,
    [longdescription] [char](100) NULL,[assetcategoryid] varchar(10) NULL,[categorydescription] [char](100) NULL,
    [serialno] [char](100) NULL,[locationdescription] [char](100) NULL,[datepurchased] datetime NULL,
    [costtotal] float null,[depnbfwd] float null,[depntype] char(5) null,[depnrate] float null,
    [periodadditions]  float null,[perioddepn]  float null,[perioddisposal]  float null,[pcs]  int null,[Hiredout]  float null)


Declare @assetid int ,@description [char](100),@longdescription [char](100),@assetcategoryid varchar(10),
    @serialno [char](100) ,@locationdescription [char](100),@datepurchased datetime,@units int,
    @parentlocationid int ,@assetlocation [char](100),@disposaldate datetime ,@depntype char(5),@depnrate float,
    @periodadditions  float ,@perioddepn  float ,@perioddisposal  float ,@pcs  int ,@Hiredout  float 
    
Declare @costbfwd float,@depnbfwd float,@categorydescription varchar(100)


Declare CursorFassets cursor for 
SELECT 
fixedassets.assetid,fixedassets.description,fixedassets.longdescription,
fixedassets.assetcategoryid,fixedassets.serialno,fixedassets.assetlocation,
fixedassets.depntype,fixedassets.depnrate
FROM fixedassets
 WHERE fixedassets.assetcategoryid " . LIKE . "'" . $_POST['AssetCategory'] . "'
    AND fixedassets.assetid " . LIKE . "'" . $_POST['AssetID'] . "'
    AND fixedassets.assetlocation " . LIKE . "'" . $_POST['AssetLocation'] . "'
            
open CursorFassets
fetch next from CursorFassets into @assetid ,@description,@longdescription ,@assetcategoryid ,@serialno ,@assetlocation,@depntype,@depnrate 
WHILE (@@FETCH_STATUS = 0)
BEGIN 

    select @categorydescription=[categorydescription] from [fixedassetcategories] where [categoryid]=@assetcategoryid
          
    select @costbfwd = sum(amount) from fixedassettrans where  fixedassettrans.transdate <'" . $DateFrom . "' AND fixedassettrans.fixedassettranstype='cost' and assetid =@assetid
    select @depnbfwd = sum(amount) from fixedassettrans where  fixedassettrans.transdate <'" . $DateFrom . "' AND fixedassettrans.fixedassettranstype='depn' and assetid =@assetid

    select @periodadditions = sum(amount) from fixedassettrans where  fixedassettrans.transdate >='" . $DateFrom ."'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='cost' and assetid =@assetid
    select @perioddepn = sum(amount) from fixedassettrans where  fixedassettrans.transdate >='" . $DateFrom ."'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='depn' and assetid =@assetid
    select @perioddisposal = sum(amount) from fixedassettrans where  fixedassettrans.transdate >='" . $DateFrom ."'  AND fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='disposal' and assetid =@assetid

    select @pcs = sum(units) from fixedassettrans where  fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='cost' and assetid =@assetid
    select @Hiredout = sum(units) from fixedassettrans where  fixedassettrans.transdate <='" . $DateTo . "' AND fixedassettrans.fixedassettranstype='Hired' and assetid =@assetid
   
    insert into #fixedassets ([assetid],[description],[longdescription],[assetcategoryid],[serialno],[locationdescription],[datepurchased],
    [costtotal],[depnbfwd],[periodadditions],[perioddepn],[perioddisposal],[pcs],[Hiredout],categorydescription)
    values (@assetid ,@description,@longdescription ,@assetcategoryid , @serialno ,@assetlocation ,@datepurchased,@costbfwd ,
    @depnbfwd,@periodadditions,@perioddepn,@perioddisposal,@pcs,@Hiredout,@categorydescription)
           
fetch next from CursorFassets into @assetid ,@description,@longdescription ,@assetcategoryid ,@serialno ,@assetlocation,@depntype,@depnrate 

END
CLOSE  CursorFassets
DEALLOCATE  CursorFassets


select 
[assetid],[description],[longdescription],[assetcategoryid],[serialno],[locationdescription],
[datepurchased],[costtotal] as costbfwd,[depnbfwd],[periodadditions],[perioddepn],
[perioddisposal],[pcs],[Hiredout],[categorydescription] 
from #fixedassets 
 order by [assetcategoryid] ,[assetid] asc
	
END";

 $r=DB_query($procodedure,$db);

}



?>