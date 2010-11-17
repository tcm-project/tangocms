 IF NOT EXISTS(SELECT * FROM sys.schemas WHERE [name] = N'dbo')      
     EXEC (N'CREATE SCHEMA dbo')                                   
 GO                                                               


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}acl_resources' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}acl_resources' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}acl_resources]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}acl_resources]
(
   [id] int IDENTITY(106, 1)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(255)  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}acl_roles' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}acl_roles' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}acl_roles]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}acl_roles]
(
   [id] smallint IDENTITY(6, 1)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(48)  NOT NULL,
   [parent_id] smallint  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}acl_rules' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}acl_rules' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}acl_rules]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}acl_rules]
(
   [id] int IDENTITY(208, 1)  NOT NULL,
   [role_id] smallint  NOT NULL,
   [resource_id] int  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [access] smallint DEFAULT 0  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}config' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}config' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}config]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}config]
(

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(255) DEFAULT N''  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [value] nvarchar(max)  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}groups' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}groups' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}groups]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}groups]
(
   [id] smallint IDENTITY(6, 1)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(32)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [status] nvarchar(6) DEFAULT N'active'  NOT NULL,
   [role_id] smallint  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}layouts' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}layouts' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}layouts]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}layouts]
(

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [regex] nvarchar(255)  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_aliases' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_aliases' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_aliases]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_aliases]
(
   [id] smallint IDENTITY(1, 1)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [alias] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [url] nvarchar(255)  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [redirect] smallint DEFAULT 0  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_article_cats' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_article_cats' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_article_cats]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_article_cats]
(
   [id] smallint IDENTITY(2, 1)  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [parent] smallint DEFAULT 0  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [title] nvarchar(255) DEFAULT N'unknown'  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [identifier] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [description] nvarchar(255) DEFAULT N''  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_article_parts' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_article_parts' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_article_parts]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_article_parts]
(
   [id] smallint IDENTITY(1, 1)  NOT NULL,
   [article_id] int  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [title] nvarchar(255) DEFAULT N''  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [order] smallint DEFAULT 10  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [body] nvarchar(max)  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_articles' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_articles' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_articles]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_articles]
(
   [id] int IDENTITY(1, 1)  NOT NULL,
   [cat_id] smallint  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [title] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [identifier] nvarchar(255)  NOT NULL,
   [author] smallint  NOT NULL,
   [date] datetime2(0)  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [published] int DEFAULT 0  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_comments' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_comments' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_comments]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_comments]
(
   [id] int IDENTITY(1, 1)  NOT NULL,
   [user_id] int  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [url] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [status] nvarchar(10) DEFAULT N'moderation'  NOT NULL,
   [date] datetime2(0)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(255) DEFAULT N''  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [website] nvarchar(255) DEFAULT N''  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [body] nvarchar(max)  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_contact' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_contact' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_contact]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_contact]
(
   [id] smallint IDENTITY(2, 1)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [identifier] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [email] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [body] nvarchar(max)  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_contact_fields' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_contact_fields' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_contact_fields]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_contact_fields]
(
   [id] smallint IDENTITY(3, 1)  NOT NULL,
   [form_id] smallint  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [type] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [options] nvarchar(255) DEFAULT N''  NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [required] int DEFAULT 1  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [order] smallint DEFAULT 2  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_media_cats' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_media_cats' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_media_cats]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_media_cats]
(
   [id] smallint IDENTITY(2, 1)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [identifier] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [description] nvarchar(255) DEFAULT N''  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_media_items' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_media_items' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_media_items]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_media_items]
(
   [id] int IDENTITY(1, 1)  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [cat_id] smallint DEFAULT 1  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [outstanding] smallint DEFAULT 1  NOT NULL,
   [date] datetime2(0)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [type] nvarchar(8)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [identifier] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [filename] nvarchar(255) DEFAULT N''  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [thumbnail] nvarchar(255) DEFAULT N''  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [external_service] nvarchar(32) DEFAULT N''  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [external_id] nvarchar(128) DEFAULT N''  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [description] nvarchar(max)  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_menu' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_menu' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_menu]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_menu]
(
   [id] smallint IDENTITY(15, 1)  NOT NULL,
   [cat_id] smallint  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [heading_id] smallint DEFAULT 0  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(255) DEFAULT N''  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [url] nvarchar(255) DEFAULT N''  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [attr_title] nvarchar(255) DEFAULT N''  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [order] smallint DEFAULT 0  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_menu_cats' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_menu_cats' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_menu_cats]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_menu_cats]
(
   [id] smallint IDENTITY(4, 1)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(255) DEFAULT N''  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_page' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_page' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_page]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_page]
(
   [id] smallint IDENTITY(2, 1)  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [parent] smallint DEFAULT 0  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [title] nvarchar(255) DEFAULT N''  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [identifier] nvarchar(255)  NOT NULL,
   [author] int  NOT NULL,
   [date] datetime2(0)  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [order] smallint DEFAULT 0  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [body] nvarchar(max)  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_poll' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_poll' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_poll]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_poll]
(
   [id] smallint IDENTITY(2, 1)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [status] nvarchar(6) DEFAULT N'active'  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [title] nvarchar(255)  NOT NULL,
   [start_date] datetime2(0)  NOT NULL,
   [end_date] datetime2(0)  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_poll_options' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_poll_options' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_poll_options]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_poll_options]
(
   [id] smallint IDENTITY(6, 1)  NOT NULL,
   [poll_id] smallint  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [title] nvarchar(255)  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_poll_votes' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_poll_votes' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_poll_votes]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_poll_votes]
(
   [id] int IDENTITY(1, 1)  NOT NULL,
   [option_id] smallint  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [ip] nvarchar(38) DEFAULT N''  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [uid] int DEFAULT 0  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_session' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_session' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_session]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_session]
(

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [ip] nvarchar(38) DEFAULT N''  NOT NULL,
   [attempts] smallint  NOT NULL,
   [blocked] datetime2(0)  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_shareable' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}mod_shareable' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}mod_shareable]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}mod_shareable]
(
   [id] smallint IDENTITY(8, 1)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(256)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [url] nvarchar(max)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [icon] nvarchar(256)  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [disabled] smallint DEFAULT 0  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [order] smallint DEFAULT 0  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}modules' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}modules' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}modules]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}modules]
(

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(255)  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [order] smallint DEFAULT 0  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [disabled] smallint DEFAULT 0  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}sessions' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}sessions' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}sessions]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}sessions]
(
   [uid] int  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [session_key] nchar(64)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [session_id] nvarchar(255)  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}users' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}users' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}users]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}users]
(
   [id] int IDENTITY(3, 1)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [status] nvarchar(6) DEFAULT N'active'  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [group] int DEFAULT 0  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [username] nvarchar(32)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [password] nchar(64)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [email] nvarchar(255) DEFAULT N''  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [hide_email] smallint DEFAULT 1  NOT NULL,
   [joined] datetime2(0)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [first_name] nvarchar(255) DEFAULT N''  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [last_name] nvarchar(255) DEFAULT N''  NOT NULL,

   /*
   *   SSMA informational messages:
   *   M2SS0052: string literal was converted to NUMERIC literal
   */

   [last_login] int DEFAULT 0  NOT NULL,
   [last_pw_change] datetime2(0)  NOT NULL
)
GO


IF  EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}users_meta' AND sc.name=N'dbo' AND type in (N'U'))
BEGIN

  DECLARE @drop_statement varchar(500)

  DECLARE drop_cursor CURSOR FOR
      SELECT 'alter table '+quotename(schema_name(ob.schema_id))+
      '.'+quotename(object_name(ob.object_id))+ ' drop constraint ' + quotename(fk.name) 
      FROM sys.objects ob INNER JOIN sys.foreign_keys fk ON fk.parent_object_id = ob.object_id
      WHERE fk.referenced_object_id = 
          (
             SELECT so.object_id 
             FROM sys.objects so JOIN sys.schemas sc
             ON so.schema_id = sc.schema_id
             WHERE so.name = N'{PREFIX}users_meta' AND sc.name=N'dbo' AND type in (N'U')
           )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement

  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)

     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP TABLE [dbo].[{PREFIX}users_meta]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE TABLE 
[dbo].[{PREFIX}users_meta]
(
   [uid] int IDENTITY(8, 1)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [name] nvarchar(255)  NOT NULL,

   /*
   *   SSMA warning messages:
   *   M2SS0183: The following SQL clause was ignored during conversion: COLLATE utf8_unicode_ci.
   */

   [value] nvarchar(255)  NOT NULL
)
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'PK_{PREFIX}acl_resources_name' AND sc.name=N'dbo' AND type in (N'PK'))
ALTER TABLE [dbo].[{PREFIX}acl_resources] DROP CONSTRAINT [PK_{PREFIX}acl_resources_name]
 GO



ALTER TABLE [dbo].[{PREFIX}acl_resources]
 ADD CONSTRAINT [PK_{PREFIX}acl_resources_name]
 PRIMARY KEY 
   CLUSTERED ([name] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'PK_{PREFIX}acl_roles_name' AND sc.name=N'dbo' AND type in (N'PK'))
ALTER TABLE [dbo].[{PREFIX}acl_roles] DROP CONSTRAINT [PK_{PREFIX}acl_roles_name]
 GO



ALTER TABLE [dbo].[{PREFIX}acl_roles]
 ADD CONSTRAINT [PK_{PREFIX}acl_roles_name]
 PRIMARY KEY 
   CLUSTERED ([name] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'PK_{PREFIX}mod_aliases_alias' AND sc.name=N'dbo' AND type in (N'PK'))
ALTER TABLE [dbo].[{PREFIX}mod_aliases] DROP CONSTRAINT [PK_{PREFIX}mod_aliases_alias]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_aliases]
 ADD CONSTRAINT [PK_{PREFIX}mod_aliases_alias]
 PRIMARY KEY 
   CLUSTERED ([alias] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'PK_{PREFIX}mod_shareable_id' AND sc.name=N'dbo' AND type in (N'PK'))
ALTER TABLE [dbo].[{PREFIX}mod_shareable] DROP CONSTRAINT [PK_{PREFIX}mod_shareable_id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_shareable]
 ADD CONSTRAINT [PK_{PREFIX}mod_shareable_id]
 PRIMARY KEY 
   CLUSTERED ([id] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}acl_resources$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}acl_resources] DROP CONSTRAINT [{PREFIX}acl_resources$id]
 GO



ALTER TABLE [dbo].[{PREFIX}acl_resources]
 ADD CONSTRAINT [{PREFIX}acl_resources$id]
 UNIQUE 
   NONCLUSTERED ([id] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}acl_roles$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}acl_roles] DROP CONSTRAINT [{PREFIX}acl_roles$id]
 GO



ALTER TABLE [dbo].[{PREFIX}acl_roles]
 ADD CONSTRAINT [{PREFIX}acl_roles$id]
 UNIQUE 
   NONCLUSTERED ([id] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}acl_rules$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}acl_rules] DROP CONSTRAINT [{PREFIX}acl_rules$id]
 GO



ALTER TABLE [dbo].[{PREFIX}acl_rules]
 ADD CONSTRAINT [{PREFIX}acl_rules$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}config$name' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}config] DROP CONSTRAINT [{PREFIX}config$name]
 GO



ALTER TABLE [dbo].[{PREFIX}config]
 ADD CONSTRAINT [{PREFIX}config$name]
 UNIQUE 
   CLUSTERED ([name] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}groups$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}groups] DROP CONSTRAINT [{PREFIX}groups$id]
 GO



ALTER TABLE [dbo].[{PREFIX}groups]
 ADD CONSTRAINT [{PREFIX}groups$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO

IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}groups$name' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}groups] DROP CONSTRAINT [{PREFIX}groups$name]
 GO



ALTER TABLE [dbo].[{PREFIX}groups]
 ADD CONSTRAINT [{PREFIX}groups$name]
 UNIQUE 
   CLUSTERED ([name] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}layouts$name' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}layouts] DROP CONSTRAINT [{PREFIX}layouts$name]
 GO



ALTER TABLE [dbo].[{PREFIX}layouts]
 ADD CONSTRAINT [{PREFIX}layouts$name]
 UNIQUE 
   CLUSTERED ([name] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_aliases$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_aliases] DROP CONSTRAINT [{PREFIX}mod_aliases$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_aliases]
 ADD CONSTRAINT [{PREFIX}mod_aliases$id]
 UNIQUE 
   NONCLUSTERED ([id] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_article_cats$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_article_cats] DROP CONSTRAINT [{PREFIX}mod_article_cats$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_article_cats]
 ADD CONSTRAINT [{PREFIX}mod_article_cats$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO

IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_article_cats$identifier' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_article_cats] DROP CONSTRAINT [{PREFIX}mod_article_cats$identifier]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_article_cats]
 ADD CONSTRAINT [{PREFIX}mod_article_cats$identifier]
 UNIQUE 
   CLUSTERED ([identifier] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_article_parts$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_article_parts] DROP CONSTRAINT [{PREFIX}mod_article_parts$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_article_parts]
 ADD CONSTRAINT [{PREFIX}mod_article_parts$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_articles$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_articles] DROP CONSTRAINT [{PREFIX}mod_articles$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_articles]
 ADD CONSTRAINT [{PREFIX}mod_articles$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO

IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_articles$identifier' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_articles] DROP CONSTRAINT [{PREFIX}mod_articles$identifier]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_articles]
 ADD CONSTRAINT [{PREFIX}mod_articles$identifier]
 UNIQUE 
   CLUSTERED ([identifier] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_comments$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_comments] DROP CONSTRAINT [{PREFIX}mod_comments$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_comments]
 ADD CONSTRAINT [{PREFIX}mod_comments$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_contact$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_contact] DROP CONSTRAINT [{PREFIX}mod_contact$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_contact]
 ADD CONSTRAINT [{PREFIX}mod_contact$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO

IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_contact$identifier' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_contact] DROP CONSTRAINT [{PREFIX}mod_contact$identifier]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_contact]
 ADD CONSTRAINT [{PREFIX}mod_contact$identifier]
 UNIQUE 
   CLUSTERED ([identifier] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_contact_fields$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_contact_fields] DROP CONSTRAINT [{PREFIX}mod_contact_fields$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_contact_fields]
 ADD CONSTRAINT [{PREFIX}mod_contact_fields$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_media_cats$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_media_cats] DROP CONSTRAINT [{PREFIX}mod_media_cats$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_media_cats]
 ADD CONSTRAINT [{PREFIX}mod_media_cats$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO

IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_media_cats$identifier' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_media_cats] DROP CONSTRAINT [{PREFIX}mod_media_cats$identifier]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_media_cats]
 ADD CONSTRAINT [{PREFIX}mod_media_cats$identifier]
 UNIQUE 
   CLUSTERED ([identifier] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_media_items$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_media_items] DROP CONSTRAINT [{PREFIX}mod_media_items$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_media_items]
 ADD CONSTRAINT [{PREFIX}mod_media_items$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO

IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_media_items$identifier' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_media_items] DROP CONSTRAINT [{PREFIX}mod_media_items$identifier]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_media_items]
 ADD CONSTRAINT [{PREFIX}mod_media_items$identifier]
 UNIQUE 
   CLUSTERED ([identifier] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_menu$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_menu] DROP CONSTRAINT [{PREFIX}mod_menu$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_menu]
 ADD CONSTRAINT [{PREFIX}mod_menu$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_menu_cats$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_menu_cats] DROP CONSTRAINT [{PREFIX}mod_menu_cats$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_menu_cats]
 ADD CONSTRAINT [{PREFIX}mod_menu_cats$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_page$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_page] DROP CONSTRAINT [{PREFIX}mod_page$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_page]
 ADD CONSTRAINT [{PREFIX}mod_page$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO

IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_page$identifier' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_page] DROP CONSTRAINT [{PREFIX}mod_page$identifier]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_page]
 ADD CONSTRAINT [{PREFIX}mod_page$identifier]
 UNIQUE 
   CLUSTERED ([identifier] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_poll$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_poll] DROP CONSTRAINT [{PREFIX}mod_poll$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_poll]
 ADD CONSTRAINT [{PREFIX}mod_poll$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_poll_options$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_poll_options] DROP CONSTRAINT [{PREFIX}mod_poll_options$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_poll_options]
 ADD CONSTRAINT [{PREFIX}mod_poll_options$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_poll_votes$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_poll_votes] DROP CONSTRAINT [{PREFIX}mod_poll_votes$id]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_poll_votes]
 ADD CONSTRAINT [{PREFIX}mod_poll_votes$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}mod_session$ip' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}mod_session] DROP CONSTRAINT [{PREFIX}mod_session$ip]
 GO



ALTER TABLE [dbo].[{PREFIX}mod_session]
 ADD CONSTRAINT [{PREFIX}mod_session$ip]
 UNIQUE 
   CLUSTERED ([ip] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}modules$name' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}modules] DROP CONSTRAINT [{PREFIX}modules$name]
 GO



ALTER TABLE [dbo].[{PREFIX}modules]
 ADD CONSTRAINT [{PREFIX}modules$name]
 UNIQUE 
   CLUSTERED ([name] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}sessions$session_key' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}sessions] DROP CONSTRAINT [{PREFIX}sessions$session_key]
 GO



ALTER TABLE [dbo].[{PREFIX}sessions]
 ADD CONSTRAINT [{PREFIX}sessions$session_key]
 UNIQUE 
   CLUSTERED ([session_key] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}users$id' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}users] DROP CONSTRAINT [{PREFIX}users$id]
 GO



ALTER TABLE [dbo].[{PREFIX}users]
 ADD CONSTRAINT [{PREFIX}users$id]
 UNIQUE 
   CLUSTERED ([id] ASC)

GO

IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}users$username' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}users] DROP CONSTRAINT [{PREFIX}users$username]
 GO



ALTER TABLE [dbo].[{PREFIX}users]
 ADD CONSTRAINT [{PREFIX}users$username]
 UNIQUE 
   CLUSTERED ([username] ASC)

GO



IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'{PREFIX}users_meta$uid' AND sc.name=N'dbo' AND type in (N'UQ'))
ALTER TABLE [dbo].[{PREFIX}users_meta] DROP CONSTRAINT [{PREFIX}users_meta$uid]
 GO



ALTER TABLE [dbo].[{PREFIX}users_meta]
 ADD CONSTRAINT [{PREFIX}users_meta$uid]
 UNIQUE 
   CLUSTERED ([uid] ASC, [name] ASC)

GO



IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_menu' AND sc.name = N'dbo' AND si.name = N'cat_id' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_menu].[cat_id] 
GO
CREATE NONCLUSTERED INDEX [cat_id] ON [dbo].[{PREFIX}mod_menu]
(
   [cat_id] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_articles' AND sc.name = N'dbo' AND si.name = N'cat_id' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_articles].[cat_id] 
GO
CREATE NONCLUSTERED INDEX [cat_id] ON [dbo].[{PREFIX}mod_articles]
(
   [cat_id] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_media_items' AND sc.name = N'dbo' AND si.name = N'cat_id' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_media_items].[cat_id] 
GO
CREATE NONCLUSTERED INDEX [cat_id] ON [dbo].[{PREFIX}mod_media_items]
(
   [cat_id] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_articles' AND sc.name = N'dbo' AND si.name = N'date' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_articles].[date] 
GO
CREATE NONCLUSTERED INDEX [date] ON [dbo].[{PREFIX}mod_articles]
(
   [date] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_page' AND sc.name = N'dbo' AND si.name = N'date' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_page].[date] 
GO
CREATE NONCLUSTERED INDEX [date] ON [dbo].[{PREFIX}mod_page]
(
   [date] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_comments' AND sc.name = N'dbo' AND si.name = N'date' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_comments].[date] 
GO
CREATE NONCLUSTERED INDEX [date] ON [dbo].[{PREFIX}mod_comments]
(
   [date] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_contact_fields' AND sc.name = N'dbo' AND si.name = N'form_id' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_contact_fields].[form_id] 
GO
CREATE NONCLUSTERED INDEX [form_id] ON [dbo].[{PREFIX}mod_contact_fields]
(
   [form_id] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_menu' AND sc.name = N'dbo' AND si.name = N'heading_id' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_menu].[heading_id] 
GO
CREATE NONCLUSTERED INDEX [heading_id] ON [dbo].[{PREFIX}mod_menu]
(
   [heading_id] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_poll_votes' AND sc.name = N'dbo' AND si.name = N'option_id' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_poll_votes].[option_id] 
GO
CREATE NONCLUSTERED INDEX [option_id] ON [dbo].[{PREFIX}mod_poll_votes]
(
   [option_id] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}modules' AND sc.name = N'dbo' AND si.name = N'order' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}modules].[order] 
GO
CREATE NONCLUSTERED INDEX [order] ON [dbo].[{PREFIX}modules]
(
   [order] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_contact_fields' AND sc.name = N'dbo' AND si.name = N'order' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_contact_fields].[order] 
GO
CREATE NONCLUSTERED INDEX [order] ON [dbo].[{PREFIX}mod_contact_fields]
(
   [order] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_menu' AND sc.name = N'dbo' AND si.name = N'order' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_menu].[order] 
GO
CREATE NONCLUSTERED INDEX [order] ON [dbo].[{PREFIX}mod_menu]
(
   [order] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}acl_roles' AND sc.name = N'dbo' AND si.name = N'parent_id' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}acl_roles].[parent_id] 
GO
CREATE NONCLUSTERED INDEX [parent_id] ON [dbo].[{PREFIX}acl_roles]
(
   [parent_id] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}users' AND sc.name = N'dbo' AND si.name = N'password' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}users].[password] 
GO
CREATE NONCLUSTERED INDEX [password] ON [dbo].[{PREFIX}users]
(
   [password] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_poll_options' AND sc.name = N'dbo' AND si.name = N'poll_id' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_poll_options].[poll_id] 
GO
CREATE NONCLUSTERED INDEX [poll_id] ON [dbo].[{PREFIX}mod_poll_options]
(
   [poll_id] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}layouts' AND sc.name = N'dbo' AND si.name = N'regex' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}layouts].[regex] 
GO
CREATE NONCLUSTERED INDEX [regex] ON [dbo].[{PREFIX}layouts]
(
   [regex] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_poll' AND sc.name = N'dbo' AND si.name = N'start_date' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_poll].[start_date] 
GO
CREATE NONCLUSTERED INDEX [start_date] ON [dbo].[{PREFIX}mod_poll]
(
   [start_date] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_comments' AND sc.name = N'dbo' AND si.name = N'status' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_comments].[status] 
GO
CREATE NONCLUSTERED INDEX [status] ON [dbo].[{PREFIX}mod_comments]
(
   [status] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_page' AND sc.name = N'dbo' AND si.name = N'title' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_page].[title] 
GO
CREATE NONCLUSTERED INDEX [title] ON [dbo].[{PREFIX}mod_page]
(
   [title] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF  EXISTS (
       SELECT * FROM sys.objects  so JOIN sys.indexes si
       ON so.object_id = si.object_id
       JOIN sys.schemas sc
       ON so.schema_id = sc.schema_id
       WHERE so.name = N'{PREFIX}mod_comments' AND sc.name = N'dbo' AND si.name = N'url' AND so.type in (N'U'))
   DROP INDEX [dbo].[{PREFIX}mod_comments].[url] 
GO
CREATE NONCLUSTERED INDEX [url] ON [dbo].[{PREFIX}mod_comments]
(
   [url] ASC
)
WITH (SORT_IN_TEMPDB = OFF, DROP_EXISTING = OFF, IGNORE_DUP_KEY = OFF, ONLINE = OFF) ON [PRIMARY] 
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'enum2str${PREFIX}groups$status' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'enum2str${PREFIX}groups$status' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[enum2str${PREFIX}groups$status]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.enum2str${PREFIX}groups$status 
( 
   @setval tinyint
)
RETURNS nvarchar(max)
AS 
   BEGIN
      RETURN 
         CASE @setval
            WHEN 1 THEN 'active'
            WHEN 2 THEN 'locked'
            ELSE ''
         END
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'enum2str${PREFIX}mod_comments$status' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'enum2str${PREFIX}mod_comments$status' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[enum2str${PREFIX}mod_comments$status]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.enum2str${PREFIX}mod_comments$status 
( 
   @setval tinyint
)
RETURNS nvarchar(max)
AS 
   BEGIN
      RETURN 
         CASE @setval
            WHEN 1 THEN 'accepted'
            WHEN 2 THEN 'moderation'
            ELSE ''
         END
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'enum2str${PREFIX}mod_media_items$type' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'enum2str${PREFIX}mod_media_items$type' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[enum2str${PREFIX}mod_media_items$type]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.enum2str${PREFIX}mod_media_items$type 
( 
   @setval tinyint
)
RETURNS nvarchar(max)
AS 
   BEGIN
      RETURN 
         CASE @setval
            WHEN 1 THEN 'image'
            WHEN 2 THEN 'video'
            WHEN 3 THEN 'audio'
            WHEN 4 THEN 'external'
            ELSE ''
         END
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'enum2str${PREFIX}mod_poll$status' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'enum2str${PREFIX}mod_poll$status' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[enum2str${PREFIX}mod_poll$status]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.enum2str${PREFIX}mod_poll$status 
( 
   @setval tinyint
)
RETURNS nvarchar(max)
AS 
   BEGIN
      RETURN 
         CASE @setval
            WHEN 1 THEN 'active'
            WHEN 2 THEN 'closed'
            ELSE ''
         END
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'enum2str${PREFIX}users$status' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'enum2str${PREFIX}users$status' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[enum2str${PREFIX}users$status]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.enum2str${PREFIX}users$status 
( 
   @setval tinyint
)
RETURNS nvarchar(max)
AS 
   BEGIN
      RETURN 
         CASE @setval
            WHEN 1 THEN 'active'
            WHEN 2 THEN 'locked'
            ELSE ''
         END
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'norm_enum${PREFIX}groups$status' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'norm_enum${PREFIX}groups$status' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[norm_enum${PREFIX}groups$status]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.norm_enum${PREFIX}groups$status 
( 
   @setval nvarchar(max)
)
RETURNS nvarchar(max)
AS 
   BEGIN
      RETURN dbo.enum2str${PREFIX}groups$status(dbo.str2enum${PREFIX}groups$status(@setval))
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'norm_enum${PREFIX}mod_comments$status' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'norm_enum${PREFIX}mod_comments$status' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[norm_enum${PREFIX}mod_comments$status]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.norm_enum${PREFIX}mod_comments$status 
( 
   @setval nvarchar(max)
)
RETURNS nvarchar(max)
AS 
   BEGIN
      RETURN dbo.enum2str${PREFIX}mod_comments$status(dbo.str2enum${PREFIX}mod_comments$status(@setval))
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'norm_enum${PREFIX}mod_media_items$type' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'norm_enum${PREFIX}mod_media_items$type' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[norm_enum${PREFIX}mod_media_items$type]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.norm_enum${PREFIX}mod_media_items$type 
( 
   @setval nvarchar(max)
)
RETURNS nvarchar(max)
AS 
   BEGIN
      RETURN dbo.enum2str${PREFIX}mod_media_items$type(dbo.str2enum${PREFIX}mod_media_items$type(@setval))
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'norm_enum${PREFIX}mod_poll$status' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'norm_enum${PREFIX}mod_poll$status' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[norm_enum${PREFIX}mod_poll$status]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.norm_enum${PREFIX}mod_poll$status 
( 
   @setval nvarchar(max)
)
RETURNS nvarchar(max)
AS 
   BEGIN
      RETURN dbo.enum2str${PREFIX}mod_poll$status(dbo.str2enum${PREFIX}mod_poll$status(@setval))
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'norm_enum${PREFIX}users$status' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'norm_enum${PREFIX}users$status' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[norm_enum${PREFIX}users$status]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.norm_enum${PREFIX}users$status 
( 
   @setval nvarchar(max)
)
RETURNS nvarchar(max)
AS 
   BEGIN
      RETURN dbo.enum2str${PREFIX}users$status(dbo.str2enum${PREFIX}users$status(@setval))
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'str2enum${PREFIX}groups$status' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'str2enum${PREFIX}groups$status' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[str2enum${PREFIX}groups$status]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.str2enum${PREFIX}groups$status 
( 
   @setval nvarchar(max)
)
RETURNS tinyint
AS 
   BEGIN
      RETURN 
         CASE @setval
            WHEN 'active' THEN 1
            WHEN 'locked' THEN 2
            ELSE 0
         END
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'str2enum${PREFIX}mod_comments$status' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'str2enum${PREFIX}mod_comments$status' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[str2enum${PREFIX}mod_comments$status]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.str2enum${PREFIX}mod_comments$status 
( 
   @setval nvarchar(max)
)
RETURNS tinyint
AS 
   BEGIN
      RETURN 
         CASE @setval
            WHEN 'accepted' THEN 1
            WHEN 'moderation' THEN 2
            ELSE 0
         END
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'str2enum${PREFIX}mod_media_items$type' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'str2enum${PREFIX}mod_media_items$type' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[str2enum${PREFIX}mod_media_items$type]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.str2enum${PREFIX}mod_media_items$type 
( 
   @setval nvarchar(max)
)
RETURNS tinyint
AS 
   BEGIN
      RETURN 
         CASE @setval
            WHEN 'image' THEN 1
            WHEN 'video' THEN 2
            WHEN 'audio' THEN 3
            WHEN 'external' THEN 4
            ELSE 0
         END
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'str2enum${PREFIX}mod_poll$status' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'str2enum${PREFIX}mod_poll$status' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[str2enum${PREFIX}mod_poll$status]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.str2enum${PREFIX}mod_poll$status 
( 
   @setval nvarchar(max)
)
RETURNS tinyint
AS 
   BEGIN
      RETURN 
         CASE @setval
            WHEN 'active' THEN 1
            WHEN 'closed' THEN 2
            ELSE 0
         END
   END
GO


IF EXISTS (SELECT * FROM sys.objects so JOIN sys.schemas sc ON so.schema_id = sc.schema_id WHERE so.name = N'str2enum${PREFIX}users$status' AND sc.name=N'dbo' AND type in (N'FN',N'TF',N'IF'))
BEGIN

  DECLARE @drop_statement varchar(500)
  DECLARE drop_cursor CURSOR FOR
     SELECT
                 'ALTER TABLE ' +
                       quotename(schema_name(tbl.schema_id)) + '.' + 
                       quotename(object_name(tbl.object_id)) + 
                 ' DROP CONSTRAINT ' + quotename(object_name(constr.object_id))
     FROM sys.sql_expression_dependencies dep
           JOIN sys.objects constr 
                 ON constr.object_id = dep.referencing_id AND constr.type = N'C'
           JOIN sys.objects tbl
                 ON tbl.object_id = constr.parent_object_id
     WHERE 
           dep.referenced_id = 
           (
                 SELECT so.object_id 
                       FROM sys.objects so 
                             JOIN sys.schemas sc 
                                   ON so.schema_id = sc.schema_id 
                       WHERE 
                             so.name = N'str2enum${PREFIX}users$status' AND 
                             sc.name=N'dbo' AND 
                             type in (N'FN',N'TF',N'IF')
            )

  OPEN drop_cursor

  FETCH NEXT FROM drop_cursor
  INTO @drop_statement


  WHILE @@FETCH_STATUS = 0
  BEGIN
     EXEC (@drop_statement)
     FETCH NEXT FROM drop_cursor
     INTO @drop_statement
  END

  CLOSE drop_cursor
  DEALLOCATE drop_cursor

  DROP FUNCTION [dbo].[str2enum${PREFIX}users$status]
END 
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO

CREATE FUNCTION dbo.str2enum${PREFIX}users$status 
( 
   @setval nvarchar(max)
)
RETURNS tinyint
AS 
   BEGIN
      RETURN 
         CASE @setval
            WHEN 'active' THEN 1
            WHEN 'locked' THEN 2
            ELSE 0
         END
   END
GO
