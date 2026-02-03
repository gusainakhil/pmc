<?php

session_start();

include "connection.php";
// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if user ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $errorMsg = "Invalid user ID";
} else {
    $userId = intval($_GET['id']);
    
    // Fetch user information
    $stmt = $conn->prepare("SELECT * FROM baris_userlogin WHERE userId = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $errorMsg = "User not found";
    } else {
        $userData = $result->fetch_assoc();
    }
    $stmt->close();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    // Get values from form
    $userId = intval($_POST['user_id']);
    $db_phone = $_POST['db_phone'];
    $db_email = $_POST['db_email'];
    $db_designation = $_POST['db_designation'];
    $OrgID = $_POST['OrgID'];
    
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

    // Check if password should be updated
    $passwordUpdate = "";
    $passwordParams = "";
    
    if (!empty($_POST['db_password'])) {
        // Hash the new password
        $db_password = password_hash($_POST['db_password'], PASSWORD_DEFAULT);
        $passwordUpdate = ", db_password = ?";
        $passwordParams = "s";
    }

    try {
        // Update user information
        $query = "UPDATE baris_userlogin SET 
                  db_phone = ?,
                  db_email = ?,
                  db_designation = ?,
                  OrgID = ?,
                  OrgName = ?,
                  StationId = ?
                  $passwordUpdate
                  WHERE userId = ?";
        
        $stmt = $conn->prepare($query);
        
        if (!empty($_POST['db_password'])) {
            $stmt->bind_param("sssisssi", $db_phone, $db_email, $db_designation, $OrgID, $OrgName, $OrgID, $db_password, $userId);
        } else {
            $stmt->bind_param("sssissi", $db_phone, $db_email, $db_designation, $OrgID, $OrgName, $OrgID, $userId);
        }

        if ($stmt->execute()) {
            $successMsg = "User information updated successfully";
            // Refresh user data
            $stmt = $conn->prepare("SELECT * FROM baris_userlogin WHERE userId = ?");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $userData = $result->fetch_assoc();
        } else {
            $errorMsg = "Error updating user information: " . $stmt->error;
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
    <title>Edit CHI | Station Cleaning</title>
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

        .badge {
            display: inline-flex;
            align-items: center;
            padding: 0.35em 0.65em;
            font-size: 0.75em;
            font-weight: 600;
            border-radius: 9999px;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
        }

        .badge-success {
            background-color: #10b981;
            color: white;
        }

        .badge-warning {
            background-color: #f59e0b;
            color: white;
        }

        .badge-danger {
            background-color: #ef4444;
            color: white;
        }
    </style>
</head>
<body>

<?php if (!isset($userData) && !isset($errorMsg)): ?>
    <div class="flex items-center justify-center min-h-screen">
        <div class="text-center p-6 max-w-md mx-auto bg-white rounded-lg shadow-md">
            <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
            <h2 class="text-xl font-semibold">Loading user data...</h2>
            <p class="text-gray-500 mt-2">Please wait while we retrieve the user information.</p>
        </div>
    </div>
<?php elseif (isset($errorMsg) && !isset($userData)): ?>
    <div class="flex items-center justify-center min-h-screen">
        <div class="text-center p-6 max-w-md mx-auto bg-white rounded-lg shadow-md">
            <i class="fas fa-exclamation-circle text-4xl text-red-500 mb-4"></i>
            <h2 class="text-xl font-semibold">Error</h2>
            <p class="text-red-500 mt-2"><?php echo htmlspecialchars($errorMsg); ?></p>
            <a href="./user-list.php" class="mt-4 inline-block px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to User List
            </a>
        </div>
    </div>
<?php else: ?>

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
                    <h1 class="text-xl font-bold text-gray-800">Edit CHI</h1>
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
                            <a href="logout.php" class="block p-3 hover:bg-gray-50 border-t border-gray-200">
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
                                <span class="text-gray-700">Edit CHI</span>
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
            
            <!-- User Information Card -->
            <div class="card mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">CHI Information</h2>
                            <p class="text-gray-500 text-sm mt-1">Edit user details for <?php echo htmlspecialchars($userData['db_userLoginName']); ?></p>
                        </div>
                        <div class="flex items-center space-x-2">
                            <span class="badge <?php 
                                $today = new DateTime();
                                $validUntil = new DateTime($userData['db_valid']);
                                if ($validUntil > $today) {
                                    echo 'badge-success';
                                } else {
                                    echo 'badge-danger';
                                }
                            ?>">
                                <?php 
                                    if ($validUntil > $today) {
                                        echo 'Active';
                                    } else {
                                        echo 'Expired';
                                    }
                                ?>
                            </span>
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <i class="fas fa-user-edit text-blue-600"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . $userData['userId']; ?>" method="POST" id="editUserForm" class="p-6">
                    <input type="hidden" name="user_id" value="<?php echo $userData['userId']; ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                        <div class="md:col-span-3">
                            <h3 class="text-md font-medium text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-user-circle mr-2 text-blue-500"></i>
                                Account Information
                            </h3>
                            <div class="h-px bg-gray-200 mb-4"></div>
                        </div>
                        
                        <div>
                            <label for="db_userLoginName" class="block text-sm font-medium text-gray-700 mb-2">User Login Name</label>
                            <input 
                                type="text" 
                                id="db_userLoginName" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($userData['db_userLoginName']); ?>"
                                readonly
                            />
                            <p class="mt-1 text-xs text-gray-500">Login name cannot be changed</p>
                        </div>
                        
                        <div>
                            <label for="db_username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                            <input 
                                type="text" 
                                id="db_username" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($userData['db_username']); ?>"
                                readonly
                            />
                            <p class="mt-1 text-xs text-gray-500">Username cannot be changed</p>
                        </div>
                        
                        <div>
                            <label for="db_password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                            <input 
                                type="password" 
                                id="db_password" 
                                name="db_password" 
                                class="form-input" 
                                placeholder="Leave blank to keep current password"
                            />
                            <p class="mt-1 text-xs text-gray-500">Only fill if you want to change the password</p>
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
                                value="<?php echo htmlspecialchars($userData['db_email']); ?>"
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
                                value="<?php echo htmlspecialchars($userData['db_phone']); ?>"
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
                                value="<?php echo htmlspecialchars($userData['db_designation']); ?>"
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
                                class="form-input" 
                                value="<?php echo htmlspecialchars($userData['db_usertype']); ?>"
                                readonly
                            />
                        </div>
                        
                        <div>
                            <label for="reportType" class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                            <input 
                                type="text" 
                                id="reportType" 
                                class="form-input" 
                                value="<?php echo htmlspecialchars($userData['reportType']); ?>"
                                readonly
                            />
                        </div>
                        
                        <div>
                            <label for="OrgID" class="block text-sm font-medium text-gray-700 mb-2">Station*</label>
                            <select id="OrgID" name="OrgID" class="form-select" required>
                                <?php
                                // Include database connection
                                include "../../connection.php";

                                // Fetch stations from the database for Division ID 30
                                $result = $conn->query("SELECT stationName, stationId FROM baris_station WHERE DivisionId = 30");

                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        $selected = ($row['stationId'] == $userData['OrgID']) ? 'selected' : '';
                                        echo "<option value='" . htmlspecialchars($row['stationId']) . "' $selected>" . htmlspecialchars($row['stationName']) . "</option>";
                                    }
                                }
                                $conn->close();
                                ?>
                            </select>
                        </div>
                        
                        <div class="md:col-span-3">
                            <h3 class="text-md font-medium text-gray-700 mb-3 flex items-center">
                                <i class="fas fa-calendar-alt mr-2 text-blue-500"></i>
                                Account Status
                            </h3>
                            <div class="h-px bg-gray-200 mb-4"></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Created Date</label>
                            <div class="form-input bg-gray-50"><?php echo date('d M Y, h:i A', strtotime($userData['db_valid_from'])); ?></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Valid Until</label>
                            <div class="form-input bg-gray-50"><?php echo date('d M Y, h:i A', strtotime($userData['db_valid'])); ?></div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Last Login</label>
                            <div class="form-input bg-gray-50">
                                <?php 
                                    if ($userData['LastLogin']) {
                                        echo date('d M Y, h:i A', strtotime($userData['LastLogin']));
                                    } else {
                                        echo '<span class="text-gray-500">Never logged in</span>';
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                        <a href="../user-dashboard/user-list.php" class="btn btn-secondary">
                            <i class="fas fa-times mr-2"></i>
                            Cancel
                        </a>
                        <button type="submit" name="update_user" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Action Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                <div class="card p-5">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-green-100 flex items-center justify-center mr-3">
                            <i class="fas fa-key text-green-600"></i>
                        </div>
                        <h3 class="font-semibold">Reset Password</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Generate a new password for this user and send it to their email address.
                    </p>
                    <button type="button" id="resetPasswordBtn" class="text-sm text-green-600 font-medium hover:text-green-800 flex items-center">
                        <i class="fas fa-key mr-2"></i>
                        Reset Password
                    </button>
                </div>
                
                <div class="card p-5">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-yellow-100 flex items-center justify-center mr-3">
                            <i class="fas fa-clock text-yellow-600"></i>
                        </div>
                        <h3 class="font-semibold">Extend Validity</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Extend the account validity period for this user by one year.
                    </p>
                    <button type="button" id="extendValidityBtn" class="text-sm text-yellow-600 font-medium hover:text-yellow-800 flex items-center">
                        <i class="fas fa-clock mr-2"></i>
                        Extend Validity
                    </button>
                </div>
                
                <div class="card p-5">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center mr-3">
                            <i class="fas fa-user-slash text-red-600"></i>
                        </div>
                        <h3 class="font-semibold">Deactivate Account</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Temporarily disable this account. The user will not be able to log in.
                    </p>
                    <button type="button" id="deactivateAccountBtn" class="text-sm text-red-600 font-medium hover:text-red-800 flex items-center">
                        <i class="fas fa-user-slash mr-2"></i>
                        Deactivate Account
                    </button>
                </div>
            </div>
            
            <!-- Activity Log -->
            <div class="card mb-6">
                <div class="p-5 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Activity</h3>
                </div>
                <div class="p-5">
                    <div class="text-center text-gray-500 py-10">
                        <i class="fas fa-history text-4xl mb-3 opacity-30"></i>
                        <p>No recent activity found for this user.</p>
                    </div>
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
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const sidebar = document.querySelector('aside');
    
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    }
    
    // Form validation
    const form = document.getElementById('editUserForm');
    
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
        
        // Password validation (if provided)
        const passwordField = document.getElementById('db_password');
        if (passwordField.value && passwordField.value.length < 8) {
            passwordField.classList.add('border-red-500');
            alert('Password must be at least 8 characters long');
            isValid = false;
        }
        
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
    
    // Example action handlers
    document.getElementById('resetPasswordBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to reset the password for this user? A new password will be generated and sent to their email address.')) {
            alert('Password reset functionality will be implemented here.');
        }
    });
    
    document.getElementById('extendValidityBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to extend the validity of this account by one year?')) {
            alert('Account validity extension functionality will be implemented here.');
        }
    });
    
    document.getElementById('deactivateAccountBtn').addEventListener('click', function() {
        if (confirm('Are you sure you want to deactivate this account? The user will not be able to log in.')) {
            alert('Account deactivation functionality will be implemented here.');
        }
    });
</script>

<?php endif; ?>

</body>
</html>
