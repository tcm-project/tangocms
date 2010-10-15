UPDATE {SQL_PREFIX}acl_resources SET name = "session_manage" WHERE name = "session_manage_config";
DELETE FROM {SQL_PREFIX}acl_resources WHERE name = "users_manage_validations";