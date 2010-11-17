UPDATE {PREFIX}acl_resources
	SET name = REPLACE(name, "page-", "page-view_") WHERE name LIKE "page-%";