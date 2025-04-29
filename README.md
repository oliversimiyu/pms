# School Resource Management System

A comprehensive PHP-based School Resource Management System with robust user authentication, role-based access control, resource request workflows, inventory management, reporting, and notifications. Designed specifically for educational institutions to manage school resources efficiently.

## Features

### Authentication & Access Control
- Secure login system using email/password
- Session-based authentication
- Role-based access control with school-specific user roles:
  - Administrator
  - Head of Department (HOD)
  - Staff
  - Student

### Dashboard
- **Admin Dashboard**: School-wide statistics, pending approvals, user management, and inventory status
- **HOD Dashboard**: Department-specific view of resource requests, inventory, and reports
- **Staff Dashboard**: Personalized view of resource requests, notifications, and quick actions
- **Student Dashboard**: Simple interface for resource requests and status tracking

### Resource Request Management
- Create resource requests with multiple items
- Track request status (pending, approved, rejected, processed)
- Role-based approval workflow (Administrators and HODs can approve)
- Detailed request history and comments

### School Supplies Inventory
- Track school supplies, quantities, and pricing
- Low stock notifications
- Categorization and filtering by department
- Stock adjustment functionality

### Reporting & Analytics
- Department-wise resource request reports
- Monthly trend analysis for school resource allocation
- School supplies usage and value reports
- Exportable data formats

### Notifications
- System notifications for requisition status changes
- Low stock alerts
- User action notifications
- Mark as read functionality

## Technology Stack

- PHP 8.x
- Bootstrap 5 for responsive UI
- Chart.js for data visualization
- File-based JSON storage (no database required)
- Bootstrap Icons

## Installation

1. Clone the repository:
   ```
   git clone https://github.com/oliversimiyu/pms.git
   ```

2. Set up a PHP server (Apache, Nginx, or PHP's built-in server):
   ```
   cd pms
   php -S localhost:8000
   ```

3. Ensure the `/data` directory has write permissions:
   ```
   chmod 755 data
   ```

4. Access the application in your browser:
   ```
   http://localhost:8000
   ```

5. Default admin credentials:
   - Email: admin@example.com
   - Password: admin123

## Directory Structure

- `/includes`: Configuration files and utility functions
- `/data`: JSON storage files (users, requisitions, inventory, etc.)
- Root directory: Main application files

## User Roles and Permissions

- **Administrator**: Full system access, user management, approvals, and rights management
- **Head of Department (HOD)**: Department-level management, can approve/reject department resource requests
- **Staff**: Can create resource requests, view inventory, and track own requests
- **Student**: Limited access to create and track personal resource requests

## Currency

The system uses Kenyan Shillings (KES) as the default currency for all monetary values.

## Security

- Passwords are hashed using PHP's `password_hash` function
- Session-based authentication with security checks
- Role verification before critical actions

## Contributing

1. Fork the repository
2. Create your feature branch: `git checkout -b feature/my-new-feature`
3. Commit your changes: `git commit -am 'Add some feature'`
4. Push to the branch: `git push origin feature/my-new-feature`
5. Submit a pull request

## Additional Features

- **Account Request System**: Users can request accounts which administrators can approve/reject
- **User Rights Management**: Administrators can change user roles and permissions
- **Responsive Design**: Works on desktop, tablet, and mobile devices
- **Data Validation**: Input validation for all forms
- **Error Handling**: Comprehensive error messages and logging
- **Audit Trail**: Track user actions and system changes

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Author

Oliver Simiyu - [GitHub Profile](https://github.com/oliversimiyu)
