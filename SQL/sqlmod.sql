/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  Administratorss
 * Created: Dec 1, 2022
 */

alter table stockmaster add  production varchar(2);
Go

/****** Object:  Table [dbo].[productionconfig]    Script Date: 01-Dec-22 2:41:55 PM ******/
SET ANSI_NULLS ON
GO

SET QUOTED_IDENTIFIER ON
GO

CREATE TABLE [dbo].[productionconfig](
	[categoryid] varchar(5) NOT NULL,
	[rawmatid] varchar(5) NOT NULL
) ON [PRIMARY]
GO


INSERT [dbo].[scripts] ([script], [pagesecurity], [description])
 VALUES (N'ProductionConfig.php', 0, N'Production Setup')
GO

-- 11 feb 2023
Insert into config (confname,confvalue)values('ManualNumber',0)