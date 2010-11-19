/****** Object:  Table [dbo].[{PREFIX}mod_page]    Script Date: 11/19/2010 14:54:39 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_page](
	[id] [smallint] IDENTITY(2,1) NOT NULL,
	[parent] [smallint] NOT NULL,
	[title] [nvarchar](255) NOT NULL,
	[identifier] [nvarchar](255) NOT NULL,
	[author] [int] NOT NULL,
	[date] [datetime2](0) NOT NULL,
	[order] [smallint] NOT NULL,
	[body] [nvarchar](max) NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_page_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY],
 CONSTRAINT [{PREFIX}mod_page$identifier] UNIQUE NONCLUSTERED 
(
	[identifier] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [date] ON [dbo].[{PREFIX}mod_page] 
(
	[date] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [title] ON [dbo].[{PREFIX}mod_page] 
(
	[title] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

SET IDENTITY_INSERT [dbo].[{PREFIX}mod_page] ON
INSERT [dbo].[{PREFIX}mod_page] ([id], [parent], [title], [identifier], [author], [date], [order], [body]) VALUES (1, 0, N'Welcome!', N'welcome!', 2, CAST(0x007141018E330B0000 AS DateTime2), 0, N'#!html
<p>Welcome to your new <a title="Opensource PHP CMS" href="http://tangocms.org">TangoCMS</a> powered website! The installation was a success and you can now <a href="session">login</a> and manage your website through the <a href="admin">Admin Control Pa(el</a>.</p>
<p>This page can be edited by <a href="admin/page/config">managing your pages</a> or changed to display something else by adjusting your <a href="admin/content_layout">content layout</a>.</p>
<p>If you need help with anything related to <a title="Opensource PHP CMS" href="http://tangocms.org/">TangoCMS</a>, feel free to join our <a href="http://tangocms.org/community">community</a> to ask any question you wish and we''ll help you out in anyway we can!</p>')
SET IDENTITY_INSERT [dbo].[{PREFIX}mod_page] OFF
/****** Object:  Default [DF__{PREFIX}mod_p__paren__1ABEEF0B]    Script Date: 11/19/2010 14:54:39 ******/
ALTER TABLE [dbo].[{PREFIX}mod_page] ADD  DEFAULT ((0)) FOR [parent]

/****** Object:  Default [DF__{PREFIX}mod_p__title__1BB31344]    Script Date: 11/19/2010 14:54:39 ******/
ALTER TABLE [dbo].[{PREFIX}mod_page] ADD  DEFAULT (N'') FOR [title]

/****** Object:  Default [DF__{PREFIX}mod_p__order__1CA7377D]    Script Date: 11/19/2010 14:54:39 ******/
ALTER TABLE [dbo].[{PREFIX}mod_page] ADD  DEFAULT ((0)) FOR [order]

