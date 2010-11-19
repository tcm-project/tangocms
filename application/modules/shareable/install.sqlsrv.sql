/****** Object:  Table [dbo].[{PREFIX}mod_shareable]    Script Date: 11/19/2010 15:18:42 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_shareable](
	[id] [smallint] IDENTITY(8,1) NOT NULL,
	[name] [nvarchar](256) NOT NULL,
	[url] [nvarchar](max) NOT NULL,
	[icon] [nvarchar](256) NOT NULL,
	[disabled] [smallint] NOT NULL,
	[order] [smallint] NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_shareable_id] PRIMARY KEY CLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]

SET IDENTITY_INSERT [dbo].[{PREFIX}mod_shareable] ON
INSERT [dbo].[{PREFIX}mod_shareable] ([id], [name], [url], [icon], [disabled], [order]) VALUES (1, N'Delicious', N'http://delicious.com/post?url={URL}&title={TITLE}', N'delicious', 0, 0)
INSERT [dbo].[{PREFIX}mod_shareable] ([id], [name], [url], [icon], [disabled], [order]) VALUES (2, N'Digg', N'http://digg.com/submit?phase=2&url={URL}&title={TITLE}', N'digg', 0, 0)
INSERT [dbo].[{PREFIX}mod_shareable] ([id], [name], [url], [icon], [disabled], [order]) VALUES (3, N'Facebook', N'http://www.facebook.com/share.php?u={URL}&t={TITLE}', N'facebook', 0, 0)
INSERT [dbo].[{PREFIX}mod_shareable] ([id], [name], [url], [icon], [disabled], [order]) VALUES (4, N'Google', N'http://www.google.com/bookmarks/mark?op=edit&bkmk={URL}&title={TITLE}', N'google', 0, 0)
INSERT [dbo].[{PREFIX}mod_shareable] ([id], [name], [url], [icon], [disabled], [order]) VALUES (5, N'Reddit', N'http://reddit.com/submit?url={URL}&title={TITLE}', N'reddit', 0, 0)
INSERT [dbo].[{PREFIX}mod_shareable] ([id], [name], [url], [icon], [disabled], [order]) VALUES (6, N'Slashdot', N'http://slashdot.org/bookmark.pl?title={TITLE}&url={URL}', N'slashdot', 0, 0)
INSERT [dbo].[{PREFIX}mod_shareable] ([id], [name], [url], [icon], [disabled], [order]) VALUES (7, N'Stumbleupon', N'http://www.stumbleupon.com/submit?url={URL}&title={TITLE}', N'stumbleupon', 0, 0)
SET IDENTITY_INSERT [dbo].[{PREFIX}mod_shareable] OFF

/****** Object:  Default [DF__{PREFIX}mod_s__disab__3EC74557]    Script Date: 11/19/2010 15:18:42 ******/
ALTER TABLE [dbo].[{PREFIX}mod_shareable] ADD  DEFAULT ((0)) FOR [disabled]

/****** Object:  Default [DF__{PREFIX}mod_s__order__3FBB6990]    Script Date: 11/19/2010 15:18:42 ******/
ALTER TABLE [dbo].[{PREFIX}mod_shareable] ADD  DEFAULT ((0)) FOR [order]
