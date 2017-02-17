DELETE FROM configuration WHERE configuration_key LIKE 'EO_%';
DELETE FROM configuration_group WHERE configuration_group_title = 'Edit Orders' LIMIT 1;
DELETE FROM admin_pages WHERE page_key IN ('editOrders', 'configEditOrders');