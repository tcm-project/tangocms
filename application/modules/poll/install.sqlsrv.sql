/****** Object:  Table [dbo].[{PREFIX}mod_poll_votes]    Script Date: 11/19/2010 15:18:42 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_poll_votes](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[option_id] [smallint] NOT NULL,
	[ip] [nvarchar](38) NOT NULL,
	[uid] [int] NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_poll_votes_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [option_id] ON [dbo].[{PREFIX}mod_poll_votes] 
(
	[option_id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

/****** Object:  Table [dbo].[{PREFIX}mod_poll_options]    Script Date: 11/19/2010 15:18:42 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_poll_options](
	[id] [smallint] IDENTITY(6,1) NOT NULL,
	[poll_id] [smallint] NOT NULL,
	[title] [nvarchar](255) NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_poll_options_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [poll_id] ON [dbo].[{PREFIX}mod_poll_options] 
(
	[poll_id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

SET IDENTITY_INSERT [dbo].[{PREFIX}mod_poll_options] ON
INSERT [dbo].[{PREFIX}mod_poll_options] ([id], [poll_id], [title]) VALUES (1, 1, N'Ampache')
INSERT [dbo].[{PREFIX}mod_poll_options] ([id], [poll_id], [title]) VALUES (2, 1, N'Jamendo')
INSERT [dbo].[{PREFIX}mod_poll_options] ([id], [poll_id], [title]) VALUES (3, 1, N'Last.fm')
SET IDENTITY_INSERT [dbo].[{PREFIX}mod_poll_options] OFF
/****** Object:  Table [dbo].[{PREFIX}mod_poll]    Script Date: 11/19/2010 15:18:42 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_poll](
	[id] [smallint] IDENTITY(2,1) NOT NULL,
	[status] [nvarchar](6) NOT NULL,
	[title] [nvarchar](255) NOT NULL,
	[start_date] [datetime2](0) NOT NULL,
	[end_date] [datetime2](0) NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_poll_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [start_date] ON [dbo].[{PREFIX}mod_poll] 
(
	[start_date] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

SET IDENTITY_INSERT [dbo].[{PREFIX}mod_poll] ON
INSERT [dbo].[{PREFIX}mod_poll] ([id], [status], [title], [start_date], [end_date]) VALUES (1, N'active', N'Which music service/radio do you use?', CAST(0x00AA46018E330B0000 AS DateTime2), CAST(0x00AA46018E330B0000 AS DateTime2))
SET IDENTITY_INSERT [dbo].[{PREFIX}mod_poll] OFF


/****** Object:  Default [DF__{PREFIX}mod_p__statu__3726238F]    Script Date: 11/19/2010 15:18:42 ******/
ALTER TABLE [dbo].[{PREFIX}mod_poll] ADD  DEFAULT (N'active') FOR [status]

/****** Object:  Default [DF__{PREFIX}mod_poll__ip__3A02903A]    Script Date: 11/19/2010 15:18:42 ******/
ALTER TABLE [dbo].[{PREFIX}mod_poll_votes] ADD  DEFAULT (N'') FOR [ip]

/****** Object:  Default [DF__{PREFIX}mod_pol__uid__3AF6B473]    Script Date: 11/19/2010 15:18:42 ******/
ALTER TABLE [dbo].[{PREFIX}mod_poll_votes] ADD  DEFAULT ((0)) FOR [uid]
