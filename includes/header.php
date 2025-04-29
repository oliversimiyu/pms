<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Bumbe Technical Training Institute (BTTI) Resource Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .dropdown-menu {
            margin-top: 0;
        }
        .dropdown:hover .dropdown-menu {
            display: block;
        }
        .navbar .dropdown-toggle::after {
            transition: transform 0.2s;
        }
        .navbar .dropdown:hover .dropdown-toggle::after {
            transform: rotate(180deg);
        }
        /* Additional custom styles can be added here */
    </style>
    <?php if (isset($page_styles)): ?>
        <?php echo $page_styles; ?>
    <?php endif; ?>
</head>
<body>
    <?php include('nav.php'); ?>
