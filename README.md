# Property Management System (PMS)

A comprehensive PHP-based Property/Purchase Management System with robust user authentication, role-based access control, requisition workflows, inventory management, reporting, and notifications.

## Features

### Authentication & Access Control
- Secure login system using email/password
- Session-based authentication
- Role-based access control with multiple user roles:
  - Admin
  - Approver
  - Procurement
  - Requester
  - Employee
  - Manager

### Dashboard
- **Admin Dashboard**: System-wide statistics, pending approvals, user management, and inventory status
- **User Dashboard**: Personalized view of requisitions, notifications, and quick actions

### Requisition Management
- Create requisitions with multiple items
- Track requisition status (pending, approved, rejected, processed)
- Role-based approval workflow
- Detailed requisition history and comments

### Inventory Management
- Track items, quantities, and pricing
- Low stock notifications
- Categorization and filtering
- Stock adjustment functionality

### Reporting & Analytics
- Department-wise requisition reports
- Monthly trend analysis
- Inventory usage and value reports
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

- **Admin**: Full system access, user management, approvals
- **Approver**: Can approve/reject requisitions
- **Procurement**: Inventory management, processing approved requisitions
- **Requester/Employee/Manager**: Can create requisitions, view own data

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

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Author

Oliver Simiyu - [GitHub Profile](https://github.com/oliversimiyu)
