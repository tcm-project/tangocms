/****** Object:  Table [dbo].[{PREFIX}mod_comments]    Script Date: 11/19/2010 14:58:09 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_comments](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[user_id] [int] NOT NULL,
	[url] [nvarchar](255) NOT NULL,
	[status] [nvarchar](10) NOT NULL,
	[date] [datetime2](0) NOT NULL,
	[name] [nvarchar](255) NOT NULL,
	[website] [nvarchar](255) NOT NULL,
	[body] [nvarchar](max) NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_comments_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [date] ON [dbo].[{PREFIX}mod_comments] 
(
	[date] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [status] ON [dbo].[{PREFIX}mod_comments] 
(
	[status] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [url] ON [dbo].[{PREFIX}mod_comments] 
(
	[url] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

/****** Object:  Default [DF__{PREFIX}mod_c__statu__3296789C]    Script Date: 11/19/2010 14:58:09 ******/
ALTER TABLE [dbo].[{PREFIX}mod_comments] ADD  DEFAULT (N'moderation') FOR [status]

/****** Object:  Default [DF__{PREFIX}mod_co__name__338A9CD5]    Script Date: 11/19/2010 14:58:09 ******/
ALTER TABLE [dbo].[{PREFIX}mod_comments] ADD  DEFAULT (N'') FOR [name]

/****** Object:  Default [DF__{PREFIX}mod_c__websi__347EC10E]    Script Date: 11/19/2010 14:58:09 ******/
ALTER TABLE [dbo].[{PREFIX}mod_comments] ADD  DEFAULT (N'') FOR [website]

