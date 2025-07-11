# Database Setup Instructions

## 1. Import Database Structure
Run the SQL file to create database and tables:
```sql
mysql -u root -p < setup.sql
```

## 2. Generate Additional Sample Data (Optional)
Run the PHP script to generate more sample data:
```bash
php insert_sample_data.php
```

## 3. Default Login Credentials

### Super Admin (Full Access)
- Email: superadmin@chickdream.com
- Password: superadmin123
- **Access:** All modules with full permissions

### Admin (Management Access)
- Email: admin@chickdream.com  
- Password: admin123
- **Access:** All modules except System Administration

### HR Manager (HR Operations)
- Email: hr@chickdream.com
- Password: hr123
- **Access:** Employee management, Attendance, Payroll, Reports (no delete permissions)

### Manager (Supervisory Access)
- Email: manager@chickdream.com
- Password: manager123
- **Access:** View-only access to most modules for reporting and oversight

### Employee (Basic Access)
- Email: employee@chickdream.com
- Password: emp123
- **Access:** Dashboard view and own attendance records only

## 4. Role-Based Access Control

### Super Administrator
- **Dashboard:** View, Export
- **Employees:** Full CRUD + Import/Export
- **Work Period:** View, Export
- **Leave Period:** View, Export
- **Placement:** Full CRUD + Export
- **Attendance:** Full CRUD + Export
- **Payroll:** Full CRUD + Approve + Export
- **Reports:** View, Export
- **Settings:** View, Edit, Backup, Restore
- **User Management:** Full CRUD
- **System Admin:** Full access including maintenance

### Administrator
- **Dashboard:** View, Export
- **Employees:** Full CRUD + Export
- **Work Period:** View, Export
- **Leave Period:** View, Export
- **Placement:** Full CRUD + Export
- **Attendance:** Full CRUD + Export
- **Payroll:** CRUD + Approve + Export (no delete)
- **Reports:** View, Export
- **Settings:** View, Edit (no backup/restore)
- **User Management:** View, Add, Edit (no delete)

### HR Manager
- **Dashboard:** View only
- **Employees:** View, Add, Edit, Export (no delete)
- **Work Period:** View, Export
- **Leave Period:** View, Export
- **Placement:** View, Add, Edit, Export (no delete)
- **Attendance:** View, Add, Edit, Export (no delete)
- **Payroll:** View, Add, Edit, Export (no approve/delete)
- **Reports:** View, Export

### Manager
- **Dashboard:** View only
- **Employees:** View, Export (read-only)
- **Work Period:** View, Export (read-only)
- **Leave Period:** View, Export (read-only)
- **Placement:** View, Export (read-only)
- **Attendance:** View, Export (read-only)
- **Reports:** View, Export (read-only)

### Employee
- **Dashboard:** View only (limited info)
- **Attendance:** View only (own records)

## 5. Sample Data Included

### Employees (15 records)
- Complete employee data with different roles and departments
- Mix of permanent, contract, and intern employees
- Realistic salary and personal information

### Attendance (30+ days)
- Daily attendance records with various statuses
- Realistic check-in/check-out times
- Different attendance patterns per employee

### Placements (13 records)
- Various placement types and locations
- Active placements with proper durations
- Complete placement documentation

### Payroll (Current + Previous months)
- Monthly payroll calculations
- Approval workflows
- Complete salary breakdowns

### System Configuration
- Company settings and policies
- Working hours and calculation rules
- Role-based permission matrix

## 6. Permission Testing

To test role-based access:

1. **Login as Super Admin** - Should see all menus and functions
2. **Login as Admin** - Should see management functions but no System Admin
3. **Login as HR** - Should see HR-related functions with limited edit rights
4. **Login as Manager** - Should see mostly read-only views for reporting
5. **Login as Employee** - Should see only dashboard and own attendance

## 7. Customizing Permissions

To modify permissions for any role:

1. Login as Super Admin
2. Go to Permission & Users menu
3. Select user to modify
4. Check/uncheck permissions as needed
5. Changes take effect immediately

## 8. Database Maintenance

- Regular backups are recommended
- Use System Admin tools for maintenance
- Monitor user activity through logs
- Update permissions as organization grows
