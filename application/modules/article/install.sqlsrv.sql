/****** Object:  Table [dbo].[{PREFIX}mod_articles]    Script Date: 11/18/2010 11:00:01 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_articles](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[cat_id] [smallint] NOT NULL,
	[title] [nvarchar](255) NOT NULL,
	[identifier] [nvarchar](255) NOT NULL,
	[author] [smallint] NOT NULL,
	[date] [datetime2](0) NOT NULL,
	[published] [int] NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_articles_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY],
 CONSTRAINT [{PREFIX}mod_articles$identifier] UNIQUE NONCLUSTERED 
(
	[identifier] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [cat_id] ON [dbo].[{PREFIX}mod_articles] 
(
	[cat_id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [date] ON [dbo].[{PREFIX}mod_articles] 
(
	[date] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

/****** Object:  Table [dbo].[{PREFIX}mod_article_parts]    Script Date: 11/18/2010 11:00:01 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_article_parts](
	[id] [smallint] IDENTITY(1,1) NOT NULL,
	[article_id] [int] NOT NULL,
	[title] [nvarchar](255) NOT NULL,
	[order] [smallint] NOT NULL,
	[body] [nvarchar](max) NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_article_parts_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

/****** Object:  Table [dbo].[{PREFIX}mod_article_cats]    Script Date: 11/18/2010 11:00:01 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_article_cats](
	[id] [smallint] IDENTITY(2,1) NOT NULL,
	[parent] [smallint] NOT NULL,
	[title] [nvarchar](255) NOT NULL,
	[identifier] [nvarchar](255) NOT NULL,
	[description] [nvarchar](255) NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_article_cats_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY],
 CONSTRAINT [{PREFIX}mod_article_cats$identifier] UNIQUE NONCLUSTERED 
(
	[identifier] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

SET IDENTITY_INSERT [dbo].[{PREFIX}mod_article_cats] ON
INSERT [dbo].[{PREFIX}mod_article_cats] ([id], [parent], [title], [identifier], [description]) VALUES (1, 0, N'General', N'general', N'')
SET IDENTITY_INSERT [dbo].[{PREFIX}mod_article_cats] OFF
/****** Object:  Default [DF__{PREFIX}mod_a__paren__30592A6F]    Script Date: 11/18/2010 11:00:01 ******/
ALTER TABLE [dbo].[{PREFIX}mod_article_cats] ADD  DEFAULT ((0)) FOR [parent]

/****** Object:  Default [DF__{PREFIX}mod_a__title__314D4EA8]    Script Date: 11/18/2010 11:00:01 ******/
ALTER TABLE [dbo].[{PREFIX}mod_article_cats] ADD  DEFAULT (N'unknown') FOR [title]

/****** Object:  Default [DF__{PREFIX}mod_a__descr__324172E1]    Script Date: 11/18/2010 11:00:01 ******/
ALTER TABLE [dbo].[{PREFIX}mod_article_cats] ADD  DEFAULT (N'') FOR [description]

/****** Object:  Default [DF__{PREFIX}mod_a__title__3429BB53]    Script Date: 11/18/2010 11:00:01 ******/
ALTER TABLE [dbo].[{PREFIX}mod_article_parts] ADD  DEFAULT (N'') FOR [title]

/****** Object:  Default [DF__{PREFIX}mod_a__order__351DDF8C]    Script Date: 11/18/2010 11:00:01 ******/
ALTER TABLE [dbo].[{PREFIX}mod_article_parts] ADD  DEFAULT ((10)) FOR [order]

/****** Object:  Default [DF__{PREFIX}mod_a__publi__370627FE]    Script Date: 11/18/2010 11:00:01 ******/
ALTER TABLE [dbo].[{PREFIX}mod_articles] ADD  DEFAULT ((0)) FOR [published]

