UPDATE {PREFIX}acl_resources SET name = "session_manage" WHERE name = "session_manage_config";
DELETE FROM {PREFIX}acl_resources WHERE name = "users_manage_validations";