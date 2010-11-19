/****** Object:  Table [dbo].[{PREFIX}mod_aliases]    Script Date: 11/19/2010 15:18:42 ******/
SET ANSI_NULLS ON

SET QUOTED_IDENTIFIER ON

CREATE TABLE [dbo].[{PREFIX}mod_aliases](
	[id] [smallint] IDENTITY(1,1) NOT NULL,
	[alias] [nvarchar](255) NOT NULL,
	[url] [nvarchar](255) NOT NULL,
	[redirect] [smallint] NOT NULL,
 CONSTRAINT [PK_{PREFIX}mod_aliases_alias] PRIMARY KEY CLUSTERED 
(
	[alias] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY],
 CONSTRAINT [{PREFIX}mod_aliases$id] UNIQUE NONCLUSTERED 
(
	[id] ASC
)WITH (PAD_INDEX  = OFF, STATISTICS_NORECOMPUTE  = OFF, IGNORE_DUP_KEY = OFF, ALLOW_ROW_LOCKS  = ON, ALLOW_PAGE_LOCKS  = ON) ON [PRIMARY]
) ON [PRIMARY]


/****** Object:  Default [DF__{PREFIX}mod_a__redir__14D10B8B]    Script Date: 11/19/2010 15:18:42 ******/
ALTER TABLE [dbo].[{PREFIX}mod_aliases] ADD  DEFAULT ((0)) FOR [redirect]