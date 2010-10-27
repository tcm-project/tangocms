DELETE FROM {PREFIX}config WHERE name = 'media/thumb_size_y' OR name LIKE 'media/medium_%';
UPDATE {PREFIX}config SET name = 'media/max_thumb_width' WHERE name = 'media/thumb_size_x';
INSERT INTO {PREFIX}config (name, value) VALUES ('media/max_fs', '12582912'), ('media/max_image_width', '9000');