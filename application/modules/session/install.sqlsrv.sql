/****** Object:  Table [dbo].[{PREFIX}mod_session]    Script Date: 11/18/2010 10:49:53 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_session](
	[ip] [nvarchar](38) NOT NULL,
	[attempts] [smallint] NOT NULL,
	[blocked] [datetime2](0) NOT NULL,
 CONSTRAINT [{PREFIX}mod_session$ip] PRIMARY KEY CLUSTERED 
(
	[ip] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

/****** Object:  Default [DF__{PREFIX}mod_sess__ip__2D7CBDC4]    Script Date: 11/18/2010 10:49:53 ******/
ALTER TABLE [dbo].[{PREFIX}mod_session] ADD  DEFAULT (N'') FOR [ip]