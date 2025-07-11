-- Update existing user and employee names to reflect proper roles
UPDATE users SET name = 'Super Administrator' WHERE email = 'superadmin@chickdream.com';
UPDATE users SET name = 'Administrator' WHERE email = 'admin@chickdream.com';

UPDATE employees SET name = 'Super Administrator' WHERE nip = 'EMP001';
UPDATE employees SET email = 'superadmin@chickdream.com' WHERE nip = 'EMP001';

-- Update any attendance records
UPDATE attendance a 
JOIN employees e ON a.nip = e.nip 
SET a.employee_id = e.id 
WHERE a.employee_id IS NULL;

-- Update any placement records
UPDATE placements p 
JOIN employees e ON p.employee_id = e.id 
SET p.employee_id = e.id 
WHERE e.nip = 'EMP001';
