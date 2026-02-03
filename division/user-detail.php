<?php

session_start();
include "connection.php";

if (!isset($_GET['token'])) {
    die("Token missing.");
}

$token = $conn->real_escape_string($_GET['token']);

// Get detailed user information with joined tables for organization, division, and station names
$sql = "SELECT u.*, 
        o.db_Orgname as organization_name, 
        d.DivisionName as division_name, 
        s.stationName as station_name 
        FROM baris_userlogin u
        LEFT JOIN baris_organization o ON u.OrgID = o.OrgID
        LEFT JOIN baris_division d ON u.DivisionId = d.DivisionId
        LEFT JOIN baris_station s ON u.StationId = s.stationId
        WHERE u.login_token = '$token'";

$result = $conn->query($sql);

if ($result->num_rows !== 1) {
    die("Invalid or expired token.");
}

$user = $result->fetch_assoc();

// Store session variables if needed
$_SESSION['userId'] = $user['userId'];
$_SESSION['db_usertype'] = $user['db_usertype'];
$_SESSION['OrgID'] = $user['OrgID'];

// Check if user account is active or expired
$validDate = strtotime($user['db_valid']);
$isActive = $validDate > time();
$statusBadge = $isActive 
    ? '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><i class="fas fa-check-circle mr-1"></i> Active</span>' 
    : '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><i class="fas fa-times-circle mr-1"></i> Expired</span>';

// Format the validity dates
$validFrom = date('M d, Y', strtotime($user['db_valid_from']));
$validTo = date('M d, Y', strtotime($user['db_valid']));

// Calculate remaining days if active
$remainingDays = '';
if ($isActive) {
    $daysLeft = ceil(($validDate - time()) / (60 * 60 * 24));
    $remainingDays = $daysLeft . ' day' . ($daysLeft != 1 ? 's' : '') . ' remaining';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($user['db_userLoginName']); ?> | User Profile</title>
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
        
        .info-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .info-item:last-child {
            border-bottom: none;
        }
        
        .info-label {
            width: 140px;
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .info-value {
            flex: 1;
            font-size: 0.875rem;
            color: #1f2937;
            font-weight: 500;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
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
        
        .progress-bar {
            height: 6px;
            border-radius: 3px;
            background-color: #e5e7eb;
            overflow: hidden;
        }
        
        .progress-value {
            height: 100%;
            border-radius: 3px;
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
                    <h1 class="text-xl font-bold text-gray-800">User Profile</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="user-list.php" class="flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to User List
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
                                <a href="user-list.php" class="text-gray-500 hover:text-blue-600">Users</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                                <span class="text-gray-700">User Profile</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Left Column - User Profile Card -->
                <div class="card p-6">
                    <div class="text-center mb-6">
                        <div class="w-24 h-24 bg-blue-100 rounded-full mx-auto flex items-center justify-center mb-4">
                            <i class="fas fa-user text-blue-500 text-3xl"></i>
                        </div>
                        <h2 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($user['db_userLoginName']); ?></h2>
                        <p class="text-gray-500 text-sm mt-1"><?php echo htmlspecialchars($user['db_designation'] ?: 'No Designation'); ?></p>
                        <div class="mt-2">
                            <?php echo $statusBadge; ?>
                        </div>
                    </div>
                    
                    <div class="border-t border-gray-200 pt-4">
                        <h3 class="text-sm font-medium text-gray-500 uppercase tracking-wider mb-3">Account Details</h3>
                        
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-id-badge text-blue-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">User ID</p>
                                    <p class="font-medium"><?php echo htmlspecialchars($user['userId']); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-user-tag text-blue-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">User Type</p>
                                    <p class="font-medium"><?php echo htmlspecialchars(ucfirst($user['db_usertype'])); ?></p>
                                </div>
                            </div>
                            
                            <div class="flex items-center">
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-calendar-alt text-blue-500"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Validity Period</p>
                                    <p class="font-medium"><?php echo $validFrom; ?> - <?php echo $validTo; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($isActive): ?>
                    <div class="mt-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm font-medium text-gray-700">Subscription Status</span>
                            <span class="text-xs text-blue-600"><?php echo $remainingDays; ?></span>
                        </div>
                        <div class="progress-bar">
                            <?php
                            // Calculate percentage of time left
                            $totalDuration = strtotime($user['db_valid']) - strtotime($user['db_valid_from']);
                            $timeElapsed = time() - strtotime($user['db_valid_from']);
                            $percentLeft = max(0, min(100, 100 - ($timeElapsed / $totalDuration * 100)));
                            
                            // Determine color based on time left
                            $progressColor = 'bg-green-500';
                            if ($percentLeft < 30) $progressColor = 'bg-red-500';
                            else if ($percentLeft < 70) $progressColor = 'bg-yellow-500';
                            ?>
                            <div class="progress-value <?php echo $progressColor; ?>" style="width: <?php echo $percentLeft; ?>%"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-6 space-y-3">
                        <a href="../user-dashboard/index.php?token=<?php echo $token; ?>" target="_blank" class="btn btn-primary w-full">
                            <i class="fas fa-sign-in-alt mr-2"></i>
                            Login as This User
                        </a>
                        
                        <a href="edit-user-detail.php?token=<?php echo $token; ?>" class="btn btn-secondary w-full">
                            <i class="fas fa-edit mr-2"></i>
                            Edit User
                        </a>
                    </div>
                </div>
                
                <!-- Right Column - User Details -->
                <div class="md:col-span-2">
                    <div class="card mb-6">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-lg font-semibold">Personal Information</h2>
                        </div>
                        
                        <div class="p-6">
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['db_userLoginName']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Username</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['db_username']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Email</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['db_email']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Phone</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['db_phone']); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Designation</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['db_designation'] ?: 'Not specified'); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card mb-6">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-lg font-semibold">Organization Information</h2>
                        </div>
                        
                        <div class="p-6">
                            <div class="info-item">
                                <div class="info-label">Organization</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['organization_name'] ?: 'Not assigned'); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Division</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['division_name'] ?: 'Not assigned'); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Station</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['station_name'] ?: 'Not assigned'); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Report Type</div>
                                <div class="info-value"><?php echo htmlspecialchars($user['reportType']); ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="p-6 border-b border-gray-200">
                            <h2 class="text-lg font-semibold">Payment Information</h2>
                        </div>
                        
                        <div class="p-6">
                            <div class="info-item">
                                <div class="info-label">Paid Amount</div>
                                <div class="info-value">₹<?php echo number_format($user['paid_amount'], 2); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">GST Amount</div>
                                <div class="info-value">₹<?php echo number_format($user['gst_amount'], 2); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Total Paid</div>
                                <div class="info-value font-semibold">₹<?php echo number_format($user['total_paid_amount'], 2); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Renewal Amount</div>
                                <div class="info-value">₹<?php echo number_format($user['renewal_amount'], 2); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Renewal GST</div>
                                <div class="info-value">₹<?php echo number_format($user['renewal_gst_amount'], 2); ?></div>
                            </div>
                            
                            <div class="info-item">
                                <div class="info-label">Total Renewal</div>
                                <div class="info-value font-semibold">₹<?php echo number_format($user['renewal_total_amount'], 2); ?></div>
                            </div>
                        </div>
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
</script>

</body>
</html>

<?php $conn->close(); ?>