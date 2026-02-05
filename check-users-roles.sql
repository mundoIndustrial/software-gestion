SELECT 
    u.id,
    u.name,
    u.email,
    u.roles_ids,
    r.id as role_id,
    r.name as role_name
FROM users u
LEFT JOIN roles r ON CAST(u.id AS varchar) = CAST(r.id AS varchar)
WHERE u.email LIKE '%@%'
ORDER BY u.id DESC
LIMIT 10;
