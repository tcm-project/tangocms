/****** Object:  Table [dbo].[{PREFIX}mod_media_items]    Script Date: 11/19/2010 15:18:42 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_media_items](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[cat_id] [smallint] NOT NULL,
	[outstanding] [smallint] NOT NULL,
	[date] [datetime2](0) NOT NULL,
	[type] [nvarchar](8) NOT NULL,
	[name] [nvarchar](255) NOT NULL,
	[identifier] [nvarchar](255) NOT NULL,
	[filename] [nvarchar](255) NOT NULL,
	[thumbnail] [nvarchar](255) NOT NULL,
	[external_service] [nvarchar](32) NOT NULL,
	[external_id] [nvarchar](128) NOT NULL,
	[description] [nvarchar](max) NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_media_items_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY],
 CONSTRAINT [{PREFIX}mod_media_items$identifier] UNIQUE NONCLUSTERED 
(
	[identifier] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [cat_id] ON [dbo].[{PREFIX}mod_media_items] 
(
	[cat_id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

/****** Object:  Table [dbo].[{PREFIX}mod_media_cats]    Script Date: 11/19/2010 15:18:42 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_media_cats](
	[id] [smallint] IDENTITY(2,1) NOT NULL,
	[name] [nvarchar](255) NOT NULL,
	[identifier] [nvarchar](255) NOT NULL,
	[description] [nvarchar](255) NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_media_cats_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY],
 CONSTRAINT [{PREFIX}mod_media_cats$identifier] UNIQUE NONCLUSTERED 
(
	[identifier] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

SET IDENTITY_INSERT [dbo].[{PREFIX}mod_media_cats] ON
INSERT [dbo].[{PREFIX}mod_media_cats] ([id], [name], [identifier], [description]) VALUES (1, N'General', N'general', N'')
SET IDENTITY_INSERT [dbo].[{PREFIX}mod_media_cats] OFF


/****** Object:  Default [DF__{PREFIX}mod_m__descr__231F2AE2]    Script Date: 11/19/2010 15:18:42 ******/
ALTER TABLE [dbo].[{PREFIX}mod_media_cats] ADD  DEFAULT (N'') FOR [description]

/****** Object:  Default [DF__{PREFIX}mod_m__cat_i__25077354]    Script Date: 11/19/2010 15:18:42 ******/
ALTER TABLE [dbo].[{PREFIX}mod_media_items] ADD  DEFAULT ((1)) FOR [cat_id]

/****** Object:  Default [DF__{PREFIX}mod_m__outst__25FB978D]    Script Date: 11/19/2010 15:18:42 ******/
ALTER TABLE [dbo].[{PREFIX}mod_media_items] ADD  DEFAULT ((1)) FOR [outstanding]

/****** Object:  Default [DF__{PREFIX}mod_m__filen__26EFBBC6]    Script Date: 11/19/2010 15:18:42 ******/
ALTER TABLE [dbo].[{PREFIX}mod_media_items] ADD  DEFAULT (N'') FOR [filename]

/****** Object:  Default [DF__{PREFIX}mod_m__thumb__27E3DFFF]    Script Date: 11/19/2010 15:18:42 ******/
ALTER TABLE [dbo].[{PREFIX}mod_media_items] ADD  DEFAULT (N'') FOR [thumbnail]

/****** Object:  Default [DF__{PREFIX}mod_m__exter__28D80438]    Script Date: 11/19/2010 15:18:42 ******/
ALTER TABLE [dbo].[{PREFIX}mod_media_items] ADD  DEFAULT (N'') FOR [external_service]

/****** Object:  Default [DF__{PREFIX}mod_m__exter__29CC2871]    Script Date: 11/19/2010 15:18:42 ******/
ALTER TABLE [dbo].[{PREFIX}mod_media_items] ADD  DEFAULT (N'') FOR [external_id]