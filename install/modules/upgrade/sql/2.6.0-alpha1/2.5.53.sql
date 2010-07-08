DELETE FROM {SQL_PREFIX}config WHERE name = 'media/thumb_size_y' OR name LIKE 'media/medium_%';
UPDATE {SQL_PREFIX}config SET name = 'media/max_thumb_width' WHERE name = 'media/thumb_size_x';
INSERT INTO {SQL_PREFIX}config (name, value) VALUES ('media/max_fs', '12582912'), ('media/max_image_width', '9000');