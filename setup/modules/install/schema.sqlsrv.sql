/****** Object:  Table [dbo].[{PREFIX}users_meta]    Script Date: 11/18/2010 09:29:27 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}users_meta](
	[uid] [int] IDENTITY(8,1) NOT NULL,
	[name] [nvarchar](255) NOT NULL,
	[value] [nvarchar](255) NOT NULL,
 CONSTRAINT [PK_{PREFIX}users_meta_uid] PRIMARY KEY CLUSTERED 
(
	[uid] ASC,
	[name] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

/****** Object:  Table [dbo].[{PREFIX}users]    Script Date: 11/18/2010 09:29:27 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}users](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[status] [nvarchar](6) NOT NULL,
	[group] [int] NOT NULL,
	[username] [nvarchar](32) NOT NULL,
	[password] [nchar](64) NOT NULL,
	[email] [nvarchar](255) NOT NULL,
	[hide_email] [smallint] NOT NULL,
	[joined] [datetime2](0) NOT NULL,
	[first_name] [nvarchar](255) NOT NULL,
	[last_name] [nvarchar](255) NOT NULL,
	[last_login] [datetime2](0) NOT NULL,
	[last_pw_change] [datetime2](0) NOT NULL,
 CONSTRAINT [PK_{PREFIX}users_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY],
 CONSTRAINT [{PREFIX}users$username] UNIQUE NONCLUSTERED 
(
	[username] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [password] ON [dbo].[{PREFIX}users] 
(
	[password] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]


INSERT [dbo].[{PREFIX}users] ([status], [group], [username], [password], [email], [hide_email], [joined], [first_name], 
  [last_name], [last_login], [last_pw_change]) VALUES (N'active', 3, N'guest', N'guest', N'', 1, SYSUTCDATETIME(), N'', 
  N'', SYSUTCDATETIME(), SYSUTCDATETIME())
INSERT [dbo].[{PREFIX}users] ([status], [group], [username], [password], [email], [hide_email], [joined], [first_name], 
  [last_name], [last_login], [last_pw_change]) VALUES (N'active', 1, N'rootUser', N'rootPass', N'rootEmail', 1, 
  SYSUTCDATETIME(), N'', N'', SYSUTCDATETIME(), SYSUTCDATETIME())

/****** Object:  Table [dbo].[{PREFIX}sessions]    Script Date: 11/18/2010 09:29:27 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}sessions](
	[uid] [int] NOT NULL,
	[session_key] [nchar](64) NOT NULL,
	[session_id] [nvarchar](255) NOT NULL,
 CONSTRAINT [PK_{PREFIX}sessions_session_key] PRIMARY KEY CLUSTERED 
(
	[session_key] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

/****** Object:  Table [dbo].[{PREFIX}modules]    Script Date: 11/18/2010 09:29:27 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}modules](
	[name] [nvarchar](255) NOT NULL,
	[order] [smallint] NOT NULL,
	[disabled] [smallint] NOT NULL,
 CONSTRAINT [PK_{PREFIX}modules_name] PRIMARY KEY CLUSTERED 
(
	[name] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [order] ON [dbo].[{PREFIX}modules] 
(
	[order] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

/****** Object:  Table [dbo].[{PREFIX}layouts]    Script Date: 11/18/2010 09:29:27 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}layouts](
	[name] [nvarchar](255) NOT NULL,
	[regex] [nvarchar](255) NOT NULL,
 CONSTRAINT [PK_{PREFIX}layouts_name] PRIMARY KEY CLUSTERED 
(
	[name] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [regex] ON [dbo].[{PREFIX}layouts] 
(
	[regex] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

INSERT [dbo].[{PREFIX}layouts] ([name], [regex]) VALUES (N'main-fullwidth-edit', N'^page/config/(edit|add)')
/****** Object:  Table [dbo].[{PREFIX}groups]    Script Date: 11/18/2010 09:29:27 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}groups](
	[id] [smallint] IDENTITY(6,1) NOT NULL,
	[name] [nvarchar](32) NOT NULL,
	[status] [nvarchar](6) NOT NULL,
	[role_id] [smallint] NOT NULL,
 CONSTRAINT [PK_{PREFIX}groups_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY],
 CONSTRAINT [{PREFIX}groups$name] UNIQUE NONCLUSTERED 
(
	[name] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

SET IDENTITY_INSERT [dbo].[{PREFIX}groups] ON
INSERT [dbo].[{PREFIX}groups] ([id], [name], [status], [role_id]) VALUES (1, N'root', N'active', 4)
INSERT [dbo].[{PREFIX}groups] ([id], [name], [status], [role_id]) VALUES (2, N'admin', N'active', 3)
INSERT [dbo].[{PREFIX}groups] ([id], [name], [status], [role_id]) VALUES (3, N'guest', N'active', 1)
INSERT [dbo].[{PREFIX}groups] ([id], [name], [status], [role_id]) VALUES (4, N'member', N'active', 2)
SET IDENTITY_INSERT [dbo].[{PREFIX}groups] OFF
/****** Object:  Table [dbo].[{PREFIX}config]    Script Date: 11/18/2010 09:29:27 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}config](
	[name] [nvarchar](255) NOT NULL,
	[value] [nvarchar](max) NOT NULL,
 CONSTRAINT [{PREFIX}config$name] UNIQUE CLUSTERED 
(
	[name] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'antispam/backend', N'captcha')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'antispam/recaptcha/private', N'')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'antispam/recaptcha/public', N'')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'config/slogan', N'Powered by TangoCMS')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'config/title', N'websiteTitle')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'config/title_format', N'[PAGE] | [SITE_TITLE]')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'date/format', N'D j M, H:i')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'date/timezone', N'')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'date/use_relative', N'true')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'editor/default', N'Html')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'editor/parse_php', N'0')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'mail/incoming', N'mailIncoming')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'mail/outgoing', N'mailOutgoing')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'mail/signature', N'Regards,')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'mail/smtp_encryption', N'false')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'mail/smtp_host', N'localhost')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'mail/smtp_password', N'')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'mail/smtp_port', N'25')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'mail/smtp_username', N'')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'mail/subject_prefix', N'true')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'mail/type', N'mail')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'meta/description', N'')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'meta/keywords', N'')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'sql/host', N'zula-framework')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'sql/pass', N'zula-framework')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'sql/user', N'zula-framework')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'theme/admin_default', N'purity')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'theme/allow_user_override', N'0')
INSERT [dbo].[{PREFIX}config] ([name], [value]) VALUES (N'theme/main_default', N'carbon')
/****** Object:  Table [dbo].[{PREFIX}acl_rules]    Script Date: 11/18/2010 09:29:27 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}acl_rules](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[role_id] [smallint] NOT NULL,
	[resource_id] [int] NOT NULL,
	[access] [smallint] NOT NULL,
 CONSTRAINT [PK_{PREFIX}acl_rules_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

/****** Object:  Table [dbo].[{PREFIX}acl_roles]    Script Date: 11/18/2010 09:29:27 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}acl_roles](
	[id] [smallint] IDENTITY(6,1) NOT NULL,
	[name] [nvarchar](48) NOT NULL,
	[parent_id] [smallint] NOT NULL,
 CONSTRAINT [PK_{PREFIX}acl_roles_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY],
 CONSTRAINT [{PREFIX}acl_roles$name] UNIQUE NONCLUSTERED 
(
	[name] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

CREATE NONCLUSTERED INDEX [parent_id] ON [dbo].[{PREFIX}acl_roles] 
(
	[parent_id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, SORT_IN_TEMPDB = OFF, IGNORE_DUP_KEY = OFF, DROP_EXISTING = OFF, ONLINE = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]

SET IDENTITY_INSERT [dbo].[{PREFIX}acl_roles] ON
INSERT [dbo].[{PREFIX}acl_roles] ([id], [name], [parent_id]) VALUES (1, N'group_guest', 0)
INSERT [dbo].[{PREFIX}acl_roles] ([id], [name], [parent_id]) VALUES (2, N'group_member', 1)
INSERT [dbo].[{PREFIX}acl_roles] ([id], [name], [parent_id]) VALUES (3, N'group_admin', 2)
INSERT [dbo].[{PREFIX}acl_roles] ([id], [name], [parent_id]) VALUES (4, N'group_root', 0)
SET IDENTITY_INSERT [dbo].[{PREFIX}acl_roles] OFF
/****** Object:  Table [dbo].[{PREFIX}acl_resources]    Script Date: 11/18/2010 09:29:27 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}acl_resources](
	[id] [int] IDENTITY(1,1) NOT NULL,
	[name] [nvarchar](255) NOT NULL,
 CONSTRAINT [PK_{PREFIX}acl_resources_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY],
 CONSTRAINT [{PREFIX}acl_resources$name] UNIQUE NONCLUSTERED 
(
	[name] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

/****** Object:  Default [DF__{PREFIX}a__acces__3CF40B7E]    Script Date: 11/18/2010 09:29:27 ******/
ALTER TABLE [dbo].[{PREFIX}acl_rules] ADD  DEFAULT ((0)) FOR [access]

/****** Object:  Default [DF__{PREFIX}co__name__3EDC53F0]    Script Date: 11/18/2010 09:29:27 ******/
ALTER TABLE [dbo].[{PREFIX}config] ADD  DEFAULT (N'') FOR [name]

/****** Object:  Default [DF__{PREFIX}g__statu__40C49C62]    Script Date: 11/18/2010 09:29:27 ******/
ALTER TABLE [dbo].[{PREFIX}groups] ADD  DEFAULT (N'active') FOR [status]

/****** Object:  Default [DF__{PREFIX}m__order__43A1090D]    Script Date: 11/18/2010 09:29:27 ******/
ALTER TABLE [dbo].[{PREFIX}modules] ADD  DEFAULT ((0)) FOR [order]

/****** Object:  Default [DF__{PREFIX}m__disab__44952D46]    Script Date: 11/18/2010 09:29:27 ******/
ALTER TABLE [dbo].[{PREFIX}modules] ADD  DEFAULT ((0)) FOR [disabled]

/****** Object:  Default [DF__{PREFIX}u__statu__477199F1]    Script Date: 11/18/2010 09:29:27 ******/
ALTER TABLE [dbo].[{PREFIX}users] ADD  DEFAULT (N'active') FOR [status]

/****** Object:  Default [DF__{PREFIX}u__group__4865BE2A]    Script Date: 11/18/2010 09:29:27 ******/
ALTER TABLE [dbo].[{PREFIX}users] ADD  DEFAULT ((0)) FOR [group]

/****** Object:  Default [DF__{PREFIX}u__email__4959E263]    Script Date: 11/18/2010 09:29:27 ******/
ALTER TABLE [dbo].[{PREFIX}users] ADD  DEFAULT (N'') FOR [email]

/****** Object:  Default [DF__{PREFIX}u__hide___4A4E069C]    Script Date: 11/18/2010 09:29:27 ******/
ALTER TABLE [dbo].[{PREFIX}users] ADD  DEFAULT ((1)) FOR [hide_email]

/****** Object:  Default [DF__{PREFIX}u__first__4B422AD5]    Script Date: 11/18/2010 09:29:27 ******/
ALTER TABLE [dbo].[{PREFIX}users] ADD  DEFAULT (N'') FOR [first_name]

/****** Object:  Default [DF__{PREFIX}u__last___4C364F0E]    Script Date: 11/18/2010 09:29:27 ******/
ALTER TABLE [dbo].[{PREFIX}users] ADD  DEFAULT (N'') FOR [last_name]

/****** Object:  Default [DF__{PREFIX}u__last___4D2A7347]    Script Date: 11/18/2010 09:29:27 ******/
ALTER TABLE [dbo].[{PREFIX}users] ADD  DEFAULT (SYSUTCDATETIME()) FOR [last_login]