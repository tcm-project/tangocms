/****** Object:  Table [dbo].[{PREFIX}mod_menu_cats]    Script Date: 11/18/2010 13:29:46 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_menu_cats](
	[id] [smallint] IDENTITY(4,1) NOT NULL,
	[name] [nvarchar](255) NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_menu_cats_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

SET IDENTITY_INSERT [dbo].[{PREFIX}mod_menu_cats] ON
INSERT [dbo].[{PREFIX}mod_menu_cats] ([id], [name]) VALUES (1, N'AdminCP')
INSERT [dbo].[{PREFIX}mod_menu_cats] ([id], [name]) VALUES (2, N'Quick links')
INSERT [dbo].[{PREFIX}mod_menu_cats] ([id], [name]) VALUES (3, N'Main')
SET IDENTITY_INSERT [dbo].[{PREFIX}mod_menu_cats] OFF
/****** Object:  Table [dbo].[{PREFIX}mod_menu]    Script Date: 11/18/2010 13:29:46 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_menu](
	[id] [smallint] IDENTITY(15,1) NOT NULL,
	[cat_id] [smallint] NOT NULL,
	[heading_id] [smallint] NOT NULL,
	[name] [nvarchar](255) NOT NULL,
	[url] [nvarchar](255) NOT NULL,
	[attr_title] [nvarchar](255) NOT NULL,
	[order] [smallint] NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_menu_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [cat_id] ON [dbo].[{PREFIX}mod_menu] 
(
	[cat_id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [heading_id] ON [dbo].[{PREFIX}mod_menu] 
(
	[heading_id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [order] ON [dbo].[{PREFIX}mod_menu] 
(
	[order] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

SET IDENTITY_INSERT [dbo].[{PREFIX}mod_menu] ON
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (1, 1, 0, N'View website', N'/', N'', 1)
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (2, 1, 0, N'Modules', N'admin', N'', 2)
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (3, 1, 0, N'Settings', N'admin/settings', N'', 3)
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (4, 1, 0, N'Theme & style', N'admin/theme', N'', 4)
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (5, 1, 0, N'Content layout', N'admin/content_layout', N'', 5)
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (6, 2, 0, N'Manage menu', N'admin/menu/config', N'', 1)
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (7, 2, 0, N'Manage articles', N'admin/article/config', N'', 2)
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (8, 2, 0, N'Add page', N'admin/page/config/add', N'', 3)
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (9, 3, 0, N'Home', N'/', N'', 1)
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (10, 3, 0, N'Articles', N'article', N'', 2)
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (11, 3, 0, N'Media', N'media', N'', 3)
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (12, 3, 0, N'Users', N'users', N'', 4)
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (13, 3, 0, N'Contact', N'contact', N'', 5)
INSERT [dbo].[{PREFIX}mod_menu] ([id], [cat_id], [heading_id], [name], [url], [attr_title], [order]) VALUES (14, 3, 0, N'AdminCP', N'admin', N'', 6)
SET IDENTITY_INSERT [dbo].[{PREFIX}mod_menu] OFF
/****** Object:  Default [DF__{PREFIX}mod_m__headi__3D491139]    Script Date: 11/18/2010 13:29:46 ******/
ALTER TABLE [dbo].[{PREFIX}mod_menu] ADD  DEFAULT ((0)) FOR [heading_id]

/****** Object:  Default [DF__{PREFIX}mod_me__name__3E3D3572]    Script Date: 11/18/2010 13:29:46 ******/
ALTER TABLE [dbo].[{PREFIX}mod_menu] ADD  DEFAULT (N'') FOR [name]

/****** Object:  Default [DF__{PREFIX}mod_men__url__3F3159AB]    Script Date: 11/18/2010 13:29:46 ******/
ALTER TABLE [dbo].[{PREFIX}mod_menu] ADD  DEFAULT (N'') FOR [url]

/****** Object:  Default [DF__{PREFIX}mod_m__attr___40257DE4]    Script Date: 11/18/2010 13:29:46 ******/
ALTER TABLE [dbo].[{PREFIX}mod_menu] ADD  DEFAULT (N'') FOR [attr_title]

/****** Object:  Default [DF__{PREFIX}mod_m__order__4119A21D]    Script Date: 11/18/2010 13:29:46 ******/
ALTER TABLE [dbo].[{PREFIX}mod_menu] ADD  DEFAULT ((0)) FOR [order]

/****** Object:  Default [DF__{PREFIX}mod_me__name__4301EA8F]    Script Date: 11/18/2010 13:29:46 ******/
ALTER TABLE [dbo].[{PREFIX}mod_menu_cats] ADD  DEFAULT (N'') FOR [name]

