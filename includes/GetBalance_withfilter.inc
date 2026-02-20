<?php

function db_GetBalance_withfilter($prd,$accno){
dropprocedure('GetBalance_withfilter');

Global $db;

$sql="CREATE PROCEDURE [dbo].[GetBalance_withfilter]
 	AS
BEGIN

        create table  #chartofaccounts (
	[ReportCode] [varchar](10) NULL,
	[accdesc] [char](30) NOT NULL,
	[balance_income] [bit] NULL,
	[ReportStyle] [int] NULL,
	[direct] [bit] NULL,
	[Sale_Purchase_Neither] [int] NULL,
	[accno] [char](20) NULL,
	[debit] float null,
	[credit] float null,
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
	[Calculation])
	SELECT 
        isnull([ReportCode],''),
        [accdesc],
        isnull([balance_income],''),
        isnull([ReportStyle],''),
        [direct],
        isnull([Sale_Purchase_Neither],''),
        [accno],
     case acct.[balance_income] when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
        from Generalledger where accountcode=acct.accno and [period] <".$prd.") else 
	(select 0) end as debit,
	
	case acct.[balance_income] when 0 then 
	(select sum(Generalledger.amount * Generalledger.[ExchangeRate]) 
        from Generalledger where balaccountcode=acct.accno and [period] <".$prd.") else 
	(select 0) end as credit,
        
[Calculation]
	from acct where [accno]='".$accno."'
	 group by 
	    [ReportCode],
            [accdesc],
            [balance_income],
            [ReportStyle],
            [direct],
            [Sale_Purchase_Neither],
            [Calculation],
            [accno]
            ORDER BY [ReportCode]
		
    
	select 
	isnull([debit],0) as debit,
	isnull([credit],0) as credit,
	[ReportCode] ,
	[accdesc] ,
	[balance_income],
	[ReportStyle] ,
	[direct],
	[Sale_Purchase_Neither],
	[accno],
        [Calculation] 
        from #chartofaccounts 
        order by [ReportCode] 

END";

DB_query($sql,$db);
};

?>