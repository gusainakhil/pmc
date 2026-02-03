<?php

session_start();

include "connection.php";
// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// add user in database
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle POST data for required fields only
    $db_userLoginName = $_POST['db_username']; 
    $db_username = $_POST['db_userLoginName'];
    // Hash the password before storing
    $db_password = password_hash($_POST['db_password'], PASSWORD_DEFAULT);
    $db_phone = $_POST['db_phone'];
    $db_email = $_POST['db_email'];
    $db_usertype = $_POST['db_usertype'];
    $db_designation = $_POST['db_designation'];
    $reportType = $_POST['reportType'];
    $OrgID = $_POST['OrgID'];
    $DivisionId = 30; // Fixed Division ID
    $StationId = $_POST['OrgID']; // Use OrgID as StationId
    $login_token = $_POST['login_token'];

    // Get OrgName from the station table
    $OrgName = null;
    $stationQuery = $conn->prepare("SELECT stationName FROM baris_station WHERE stationId = ?");
    $stationQuery->bind_param("i", $OrgID);
    $stationQuery->execute();
    $stationResult = $stationQuery->get_result();
    if ($stationResult && $stationResult->num_rows > 0) {
        $stationRow = $stationResult->fetch_assoc();
        $OrgName = $stationRow['stationName'];
    }
    $stationQuery->close();

    // Set default values for fields not in the form
    $db_valid_from = date('Y-m-d H:i:s');
    $db_valid = date('Y-m-d H:i:s', strtotime('+1 year'));
    $paid_amount = 0.0;
    $gst_amount = 0.0;
    $total_paid_amount = 0.0;
    $renewal_amount = 0.0;
    $renewal_gst_amount = 0.0;
    $renewal_total_amount = 0.0;
    $LastLogin = null; // Will be set when user logs in


    try {
        // Prepare and bind - include OrgName and LastLogin
        $stmt = $conn->prepare("INSERT INTO baris_userlogin (db_userLoginName, db_username, db_password, db_phone, db_email, db_usertype, db_designation, reportType, OrgID, OrgName, DivisionId, StationId, db_valid_from, db_valid, paid_amount, gst_amount, total_paid_amount, renewal_amount, renewal_gst_amount, renewal_total_amount, LastLogin, login_token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssssssssisiissddddddss", $db_userLoginName, $db_username, $db_password, $db_phone, $db_email, $db_usertype, $db_designation, $reportType, $OrgID, $OrgName, $DivisionId, $StationId, $db_valid_from, $db_valid, $paid_amount, $gst_amount, $total_paid_amount, $renewal_amount, $renewal_gst_amount, $renewal_total_amount, $LastLogin, $login_token);

        if ($stmt->execute()) {
            $successMsg = "User created successfully";
        } else {
            $errorMsg = "Error: " . $stmt->error;
        }
        $stmt->close();
    } catch (Exception $e) {
        $errorMsg = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User | Station Cleaning</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
        }
        
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            background-color: white;
        }
        
        .form-input {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        
        .form-input:read-only {
            background-color: #f9fafb;
            cursor: not-allowed;
        }
        
        .form-select {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 0.5rem center;
            background-repeat: no-repeat;
            background-size: 1.5em 1.5em;
            transition: all 0.2s;
        }
        
        .form-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
        }
        
        .btn-secondary {
            background-color: white;
            color: #3b82f6;
            border: 1px solid #3b82f6;
        }
        
        .btn-secondary:hover {
            background-color: #eff6ff;
        }
        
        .dropdown-menu {
            opacity: 0;
            visibility: hidden;
            transform: translateY(10px);
            transition: opacity 0.2s, transform 0.2s, visibility 0.2s;
        }
        
        .dropdown:hover .dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
    </style>
</head>
<body>

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <!-- Main Content -->
    <div class="ml-0 md:ml-72 flex-1">
        <!-- Top Navigation -->
        <header class="bg-white shadow-sm sticky top-0 z-10">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center">
                    <button id="mobile-menu-button" class="mr-4 text-gray-600 md:hidden">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="text-xl font-bold text-gray-800">Create Auditor</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="../user-dashboard/user-list.php" class="flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                        <i class="fas fa-list mr-2"></i>
                        View All Users
                    </a>
                    
                    <!-- User Menu -->
                    <div class="relative dropdown">
                        <button class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center">
                                <span class="font-bold text-white">A</span>
                            </div>
                            <span class="hidden md:inline-block font-medium text-sm">Admin</span>
                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                        </button>
                        <div class="dropdown-menu absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg overflow-hidden z-20">
                            <a href="#" class="block p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <i class="fas fa-user-circle w-5 mr-2 text-gray-500"></i>
                                    <span>Profile</span>
                                </div>
                            </a>
                            <a href="#" class="block p-3 hover:bg-gray-50">
                                <div class="flex items-center">
                                    <i class="fas fa-cog w-5 mr-2 text-gray-500"></i>
                                    <span>Settings</span>
                                </div>
                            </a>
                            <a href="#" class="block p-3 hover:bg-gray-50 border-t border-gray-200">
                                <div class="flex items-center">
                                    <i class="fas fa-sign-out-alt w-5 mr-2 text-gray-500"></i>
                                    <span>Logout</span>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="p-6">
            <div class="mb-6">
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="dashboard.php" class="text-gray-500 hover:text-blue-600">
                                <i class="fas fa-home mr-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                                <a href="../user-dashboard/user-list.php" class="text-gray-500 hover:text-blue-600">Users</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                                <span class="text-gray-700">Create Auditor</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($successMsg)): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <p class="text-green-700"><?php echo htmlspecialchars($successMsg); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($errorMsg)): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <p class="text-red-700"><?php echo htmlspecialchars($errorMsg); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Main Card -->
            <div class="card mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Auditor Information</h2>
                            <p class="text-gray-500 text-sm mt-1">Enter details to create a new user account</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-user-plus text-blue-600"></i>
                        </div>
                    </div>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="userForm" class="p-6">
                    <!-- Hidden field for login token -->
                    <input type="hidden" name="login_token" id="login_token" />
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="md:col-span-3">
                            <h3 class="text-md font-medium text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-user-circle mr-2 text-blue-500"></i>
                                Account Information
                            </h3>
                            <div class="h-px bg-gray-200 mb-4"></div>
                        </div>
                        
                        <div>
                            <label for="db_userLoginName" class="block text-sm font-medium text-gray-700 mb-2">User Login Name*</label>
                            <input 
                                type="text" 
                                id="db_userLoginName" 
                                name="db_userLoginName" 
                                class="form-input" 
                                placeholder="Enter login name"
                                required
                            />
                            <p class="mt-1 text-xs text-gray-500">This will be used to generate the username</p>
                        </div>
                        
                        <div>
                            <label for="db_username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                            <input 
                                type="text" 
                                id="db_username" 
                                name="db_username" 
                                class="form-input" 
                                placeholder="Auto-generated"
                                readonly
                            />
                            <p class="mt-1 text-xs text-gray-500">Auto-generated from login name (spaces removed)</p>
                        </div>
                        <script>
                            // Remove spaces from login name and set as username
                            document.getElementById('db_userLoginName').addEventListener('input', function() {
                                let val = this.value.replace(/\s+/g, '');
                                document.getElementById('db_username').value = val;
                            });
                        </script>
                        <div>
                            <label for="db_password" class="block text-sm font-medium text-gray-700 mb-2">Password*</label>
                            <input 
                                type="password" 
                                id="db_password" 
                                name="db_password" 
                                class="form-input" 
                                placeholder="Enter password"
                                required
                            />
                            <p class="mt-1 text-xs text-gray-500">Minimum 8 characters recommended</p>
                        </div>
                        
                        <div class="md:col-span-3">
                            <h3 class="text-md font-medium text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-address-card mr-2 text-blue-500"></i>
                                Contact Information
                            </h3>
                            <div class="h-px bg-gray-200 mb-4"></div>
                        </div>
                        
                        <div>
                            <label for="db_email" class="block text-sm font-medium text-gray-700 mb-2">Email Address*</label>
                            <input 
                                type="email" 
                                id="db_email" 
                                name="db_email" 
                                class="form-input" 
                                placeholder="email@example.com"
                                required
                            />
                        </div>
                        
                        <div>
                            <label for="db_phone" class="block text-sm font-medium text-gray-700 mb-2">Phone Number*</label>
                            <input 
                                type="text" 
                                id="db_phone" 
                                name="db_phone" 
                                class="form-input" 
                                placeholder="Enter phone number"
                                maxlength="12"
                                required
                            />
                        </div>
                        
                        <div>
                            <label for="db_designation" class="block text-sm font-medium text-gray-700 mb-2">Designation*</label>
                            <input 
                                type="text" 
                                id="db_designation" 
                                name="db_designation" 
                                class="form-input" 
                                placeholder="Enter designation"
                                required
                            />
                        </div>
                        
                        <div class="md:col-span-3">
                            <h3 class="text-md font-medium text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-sitemap mr-2 text-blue-500"></i>
                                Organization & Access
                            </h3>
                            <div class="h-px bg-gray-200 mb-4"></div>
                        </div>
                        
                        <div>
                            <label for="db_usertype" class="block text-sm font-medium text-gray-700 mb-2">User Type</label>
                            <input 
                                type="text" 
                                id="db_usertype" 
                                name="db_usertype" 
                                class="form-input" 
                                value="auditor"
                                readonly
                            />
                        </div>
                        
                        <div>
                            <label for="reportType" class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                            <input 
                                type="text" 
                                id="reportType" 
                                name="reportType" 
                                class="form-input" 
                                value="PMC"
                                readonly
                            />
                        </div>
                        
                        <div>
                            <label for="OrgID" class="block text-sm font-medium text-gray-700 mb-2">Station*</label>
                            <select id="OrgID" name="OrgID" class="form-select" required>
                                <option value="">Select Station</option>
                                <?php
                                // Include database connection
                                include "../../connection.php";

                                // Fetch stations from the database for Division ID 30
                                $result = $conn->query("SELECT stationName, stationId FROM baris_station WHERE DivisionId = 30");

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        echo "<option value='" . htmlspecialchars($row['stationId']) . "'>" . htmlspecialchars($row['stationName']) . "</option>";
                                    }
                                }
                                $conn->close();
                                ?>
                            </select>
                        </div>
                        
                        <!-- Hidden fields for fixed values -->
                        <input type="hidden" name="DivisionId" value="30" />
                       
                    </div>
                    
                    <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                        <a href="../user-dashboard/user-list.php" class="btn btn-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>
                            Create Auditor
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="card p-5">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-lightbulb text-blue-600"></i>
                        </div>
                        <h3 class="font-semibold">Tips for Creating Auditor</h3>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Choose a strong password with at least 8 characters</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Assign users to the correct organization and division</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Set an appropriate validity period for account access</span>
                        </li>
                    </ul>
                </div>
                
                <div class="card p-5">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                            <i class="fas fa-question-circle text-purple-600"></i>
                        </div>
                        <h3 class="font-semibold">Need Help?</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        If you need assistance with creating users or understanding user permissions, 
                        our support team is here to help.
                    </p>
                    <a href="#" class="text-sm text-purple-600 font-medium hover:text-purple-800 flex items-center">
                        <i class="fas fa-headset mr-2"></i>
                        Contact Support
                    </a>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="mt-8 border-t border-gray-200 pt-6 pb-4">
                <p class="text-sm text-gray-500 text-center">
                    &copy; 2025 BeatleBuddy. All rights reserved.
                </p>
            </footer>
        </div>
    </div>
</div>

<script>
    // Generate and set login token on page load
    function generateToken(length = 32) {
        const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let token = '';
        for (let i = 0; i < length; i++) {
            token += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return token;
    }
    
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const sidebar = document.querySelector('aside');
    
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    }
    
    // Auto-generate username from login name
    document.getElementById('db_userLoginName').addEventListener('input', function() {
        let val = this.value.replace(/\s+/g, '');
        let rand = Math.floor(100 + Math.random() * 900);
        document.getElementById('db_username').value = val ? (val + rand) : '';
    });
    
    // Calculate total amount
    function calculateTotal() {
        const paidAmount = parseFloat(document.getElementById('paid_amount').value) || 0;
        const gstAmount = parseFloat(document.getElementById('gst_amount').value) || 0;
        const totalAmount = paidAmount + gstAmount;
        document.getElementById('total_paid_amount').value = totalAmount.toFixed(2);
    }
    
    // Calculate renewal total amount
    function calculateRenewalTotal() {
        const renewalAmount = parseFloat(document.getElementById('renewal_amount').value) || 0;
        const renewalGST = parseFloat(document.getElementById('renewal_gst_amount').value) || 0;
        const renewalTotal = renewalAmount + renewalGST;
        document.getElementById('renewal_total_amount').value = renewalTotal.toFixed(2);
    }
    
    // Form validation
    const form = document.getElementById('userForm');
    
    form.addEventListener('submit', function(event) {
        let isValid = true;
        const requiredFields = form.querySelectorAll('[required]');
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('border-red-500');
                isValid = false;
            } else {
                field.classList.remove('border-red-500');
            }
        });
        
        // Validate email format
        const emailField = document.getElementById('db_email');
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(emailField.value)) {
            emailField.classList.add('border-red-500');
            isValid = false;
        }
        
        // Password strength
        const passwordField = document.getElementById('db_password');
        if (passwordField.value.length < 8) {
            passwordField.classList.add('border-red-500');
            isValid = false;
        }
        
        // Set login token before submission
        document.getElementById('login_token').value = generateToken(32);
        
        if (!isValid) {
            event.preventDefault();
            
            // Scroll to first error
            const firstError = form.querySelector('.border-red-500');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        }
    });
    
    // Clear validation styling on input
    const formInputs = form.querySelectorAll('.form-input, .form-select');
    formInputs.forEach(input => {
        input.addEventListener('input', function() {
            this.classList.remove('border-red-500');
        });
    });
    
    // Initialize calculations and token on page load
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('login_token').value = generateToken(32);
        calculateTotal();
        calculateRenewalTotal();
    });
</script>

</body>
</html>