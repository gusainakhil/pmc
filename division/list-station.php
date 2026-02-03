<?php
$division_id = 30;
session_start();

include "connection.php";

// Get user type filter (default to 'owner')
$userType = isset($_GET['type']) ? $_GET['type'] : 'owner';
$validUserTypes = ['owner', 'auditor', 'all'];
if (!in_array($userType, $validUserTypes)) $userType = 'owner';

// Build the query based on filters
$sql = "SELECT u.userId, u.db_valid, u.db_valid_from, u.db_userLoginName, u.db_username, u.db_phone, 
        u.db_email, u.reportType, u.OrgID, u.DivisionId, u.StationId, u.login_token, u.db_usertype,
        o.db_Orgname as organization_name,
        d.DivisionName as division_name,
        s.stationName as station_name
        FROM baris_userlogin u
        LEFT JOIN baris_organization o ON u.OrgID = o.OrgID
        LEFT JOIN baris_division d ON u.DivisionId = d.DivisionId
        LEFT JOIN baris_station s ON u.StationId = s.stationId
        WHERE u.DivisionId = $division_id";

// Apply user type filter
if ($userType !== 'all') {
    $sql .= " AND u.db_usertype = '$userType'";
}

$sql .= " ORDER BY u.userId DESC";
$result = $conn->query($sql);

// Count total users by type
$countOwners = $conn->query("SELECT COUNT(*) as count FROM baris_userlogin WHERE db_usertype = 'owner' AND DivisionId = $division_id")->fetch_assoc()['count'];
$countAuditors = $conn->query("SELECT COUNT(*) as count FROM baris_userlogin WHERE db_usertype = 'auditor' AND DivisionId = $division_id")->fetch_assoc()['count'];
$countAllUsers = $countOwners + $countAuditors;

// Get search term if provided
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Division Management | Station Cleaning</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            text-transform: capitalize;

        }
        
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            background-color: white;
        }
        
        .table-container {
            overflow-x: auto;
            border-radius: 8px;
        }
        
        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        th {
            background-color: #f9fafb;
            font-weight: 500;
            text-align: left;
            padding: 0.75rem 1rem;
            font-size: 0.75rem;
            color: #4b5563;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-bottom: 1px solid #e5e7eb;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            color: #1f2937;
            font-size: 0.875rem;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background-color: #f9fafb;
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
        
        .btn-login {
            background-color: #e0e7ff;
            color: #4f46e5;
        }
        
        .btn-login:hover {
            background-color: #c7d2fe;
        }
        
        .btn-edit {
            background-color: #d1fae5;
            color: #059669;
        }
        
        .btn-edit:hover {
            background-color: #a7f3d0;
        }
        
        .btn-delete {
            background-color: #fee2e2;
            color: #ef4444;
        }
        
        .btn-delete:hover {
            background-color: #fecaca;
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
        
        .user-status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .active-badge {
            background-color: #d1fae5;
            color: #059669;
        }
        
        .expired-badge {
            background-color: #fee2e2;
            color: #ef4444;
        }
        
        .tab-button {
            position: relative;
            padding: 0.75rem 1rem;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .tab-button.active {
            color: #3b82f6;
        }
        
        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #3b82f6;
        }
        
        .tab-button:hover {
            color: #3b82f6;
        }
        
        .search-input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
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
                    <h1 class="text-xl font-bold text-gray-800">Division Management</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <!-- <a href="create-user.php" class="btn btn-primary">
                        <i class="fas fa-user-plus mr-2"></i>
                        Add New User
                    </a> -->
                    
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
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                                <span class="text-gray-700">Division Management</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <!-- Overview Section -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Division Overview</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <!--<div class="card p-5">-->
                    <!--    <div class="flex items-center">-->
                    <!--        <div class="rounded-full bg-blue-100 p-3 mr-4">-->
                    <!--            <i class="fas fa-users text-blue-600"></i>-->
                    <!--        </div>-->
                    <!--        <div>-->
                    <!--            <p class="text-gray-500 text-sm">Total Users</p>-->
                    <!--            <h3 class="text-2xl font-bold"><?php echo $countAllUsers; ?></h3>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                    
                    <div class="card p-5">
                        <div class="flex items-center">
                            <div class="rounded-full bg-indigo-100 p-3 mr-4">
                                <i class="fas fa-user-tie text-indigo-600"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Stations</p>
                                <h3 class="text-2xl font-bold"><?php echo $countOwners; ?></h3>
                            </div>
                        </div>
                    </div>

                    <!--<div class="card p-5">-->
                    <!--    <div class="flex items-center">-->
                    <!--        <div class="rounded-full bg-purple-100 p-3 mr-4">-->
                    <!--            <i class="fas fa-user-check text-purple-600"></i>-->
                    <!--        </div>-->
                    <!--        <div>-->
                    <!--            <p class="text-gray-500 text-sm">Auditors</p>-->
                    <!--            <h3 class="text-2xl font-bold"><?php echo $countAuditors; ?></h3>-->
                    <!--        </div>-->
                    <!--    </div>-->
                    <!--</div>-->
                </div>
            </div>
            
            <!-- User List -->
            <div class="card mb-6">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div class="mb-4 md:mb-0">
                            <h3 class="text-lg font-semibold text-gray-800 mb-3">Station List</h3>
                            
                            <!-- Tabs -->
                            <div class="flex border-b">
                                <a href="?type=owner" class="tab-button <?php echo $userType === 'owner' ? 'active' : ''; ?>">
                                    <i class="fas fa-user-tie mr-2"></i>Stations
                                </a>
                                <!--<a href="?type=auditor" class="tab-button <?php echo $userType === 'auditor' ? 'active' : ''; ?>">-->
                                <!--    <i class="fas fa-user-check mr-2"></i>Auditors-->
                                <!--</a>-->
                                <!--<a href="?type=all" class="tab-button <?php echo $userType === 'all' ? 'active' : ''; ?>">-->
                                <!--    <i class="fas fa-users mr-2"></i>All Users-->
                                <!--</a>-->
                            </div>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-2">
                            <div class="relative">
                                <form action="" method="GET" id="searchForm">
                                    <input type="hidden" name="type" value="<?php echo $userType; ?>">
                                    <input 
                                        type="text" 
                                        name="search" 
                                        id="searchInput" 
                                        placeholder="Search users..." 
                                        class="search-input"
                                        value="<?php echo htmlspecialchars($searchTerm); ?>"
                                    >
                                    <button type="submit" class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-400">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </form>
                            </div>
                            
                            <button id="refreshButton" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200">
                                <i class="fas fa-sync-alt mr-2"></i>
                                Refresh
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="50">
                                    <div class="flex items-center">
                                        <span>ID</span>
                                        <button class="ml-1 text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-sort"></i>
                                        </button>
                                    </div>
                                </th>
                                <th>
                                    <div class="flex items-center">
                                        <span>User</span>
                                        <button class="ml-1 text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-sort"></i>
                                        </button>
                                    </div>
                                </th>
                                <th>
                                    <div class="flex items-center">
                                        <span>Username</span>
                                    </div>
                                </th>
                                <th>Contact</th>
                                <th>Organization</th>
                                <th>Status</th>
                                <th>Validity</th>
                                <th width="200">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result && $result->num_rows > 0) {
                                $counter = 1;
                                while ($row = $result->fetch_assoc()) {
                                    // Skip if searching and no match
                                    if ($searchTerm !== '') {
                                        $searchIn = $row['db_userLoginName'] . ' ' . $row['db_username'] . ' ' . $row['db_email'];
                                        if (stripos($searchIn, $searchTerm) === false) {
                                            continue;
                                        }
                                    }
                                    
                                    // Check if user account is active or expired
                                    $validDate = strtotime($row['db_valid']);
                                    $isActive = $validDate > time();
                                    $statusBadge = $isActive 
                                        ? '<span class="user-status-badge active-badge"><i class="fas fa-check-circle mr-1"></i> Active</span>' 
                                        : '<span class="user-status-badge expired-badge"><i class="fas fa-times-circle mr-1"></i> Expired</span>';
                                    
                                    // Format the validity dates
                                    $validFrom = date('M d, Y', strtotime($row['db_valid_from']));
                                    $validTo = date('M d, Y', strtotime($row['db_valid']));
                                    
                                    echo "<tr class='user-row'>";
                                    echo "<td>" . htmlspecialchars($row['userId']) . "</td>";
                                    echo "<td>
                                            <div>
                                                <div class='font-medium'>" . htmlspecialchars($row['station_name']) . "</div>
                                                <div class='text-gray-500 text-xs'>" . htmlspecialchars($row['db_usertype']) . "</div>
                                            </div>
                                          </td>";
                                    echo "<td>" . htmlspecialchars($row['db_username']) . "</td>";
                                    echo "<td>
                                            <div>
                                                <div>" . htmlspecialchars($row['db_email']) . "</div>
                                                <div class='text-gray-500 text-xs'>" . htmlspecialchars($row['db_phone']) . "</div>
                                            </div>
                                          </td>";
                                    echo "<td>
                                            <div>
                                                <div>" . htmlspecialchars($row['organization_name'] ?: 'Not assigned') . "</div>
                                                <div class='text-gray-500 text-xs'>" . 
                                                    ($row['division_name'] ? htmlspecialchars($row['division_name']) : 'No Division') . 
                                                    ($row['station_name'] ? ' / ' . htmlspecialchars($row['station_name']) : '') . 
                                                "</div>
                                            </div>
                                          </td>";
                                    echo "<td>" . $statusBadge . "</td>";
                                    echo "<td>
                                            <div>
                                                <div class='text-xs text-gray-500'>From: " . $validFrom . "</div>
                                                <div class='text-xs " . ($isActive ? 'text-gray-500' : 'text-red-500 font-medium') . "'>To: " . $validTo . "</div>
                                            </div>
                                          </td>";
                                    echo "<td>
                                            <div class='flex space-x-2'>
                                                <a href='../dashboard/user-dashboard/index.php?token=" . $row['login_token'] . "' target='_blank' class='btn btn-login' title='Login as this user'>
                                                    <i class='fas fa-sign-in-alt'></i>
                                                </a>
                                          
                                              
                                            </div>
                                          </td>";
                                    echo "</tr>";
                                    $counter++;
                                }
                                
                                // If search was performed but no results found
                                if ($searchTerm !== '' && $counter === 1) {
                                    echo "<tr><td colspan='8' class='py-4 text-center text-gray-500'>No users found matching '<strong>" . htmlspecialchars($searchTerm) . "</strong>'. <a href='?type=$userType' class='text-blue-500 hover:underline'>Clear search</a></td></tr>";
                                }
                            } else {
                                echo "<tr><td colspan='8' class='py-4 text-center text-gray-500'>";
                                if ($userType === 'owner') {
                                    echo "No owners found. <a href='create-user.php' class='text-blue-500 hover:underline'>Create your first owner account</a>.";
                                } elseif ($userType === 'auditor') {
                                    echo "No auditors found. <a href='create-user.php' class='text-blue-500 hover:underline'>Create your first auditor account</a>.";
                                } else {
                                    echo "No users found. <a href='create-user.php' class='text-blue-500 hover:underline'>Create your first user</a>.";
                                }
                                echo "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Quick Links -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="card p-4">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-user-shield text-blue-600"></i>
                        </div>
                        <h3 class="font-semibold">User Permissions</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Users have different permissions based on their type:
                        <ul class="list-disc pl-5 mt-2 text-sm text-gray-600 space-y-1">
                            <li><span class="font-medium">Owners</span>: Can manage their own organization, divisions, and stations</li>
                            <li><span class="font-medium">Auditors</span>: Can view reports and conduct audits</li>
                        </ul>
                    </p>
                    <a href="#" class="text-sm text-blue-600 font-medium hover:text-blue-800 flex items-center">
                        <span>Learn more about user permissions</span>
                        <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <div class="card p-4">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                            <i class="fas fa-question-circle text-purple-600"></i>
                        </div>
                        <h3 class="font-semibold">Need Help?</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Our support team is here to help you with any questions about managing users
                        or setting up user permissions.
                    </p>
                    <a href="#" class="text-sm text-purple-600 font-medium hover:text-purple-800 flex items-center">
                        <i class="fas fa-headset mr-2"></i>
                        <span>Contact Support</span>
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

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden z-50">
    <div class="bg-white rounded-lg max-w-md w-full p-6">
        <div class="text-center mb-6">
            <div class="w-16 h-16 bg-red-100 rounded-full mx-auto flex items-center justify-center mb-4">
                <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Confirm Deletion</h3>
            <p class="text-gray-500" id="deleteMessage">Are you sure you want to delete this user?</p>
        </div>
        <div class="flex justify-end space-x-3">
            <button id="cancelDelete" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                Cancel
            </button>
            <a id="confirmDeleteBtn" href="#" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600">
                Yes, Delete
            </a>
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
    
    // Delete confirmation modal
    function confirmDelete(userId, userName) {
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const deleteMessage = document.getElementById('deleteMessage');
        
        deleteMessage.textContent = `Are you sure you want to delete the user "${userName}"?`;
        confirmDeleteBtn.href = `delete-user.php?id=${userId}`;
        deleteModal.classList.remove('hidden');
        
        cancelDelete.addEventListener('click', () => {
            deleteModal.classList.add('hidden');
        });
        
        // Close modal when clicking outside
        deleteModal.addEventListener('click', (e) => {
            if (e.target === deleteModal) {
                deleteModal.classList.add('hidden');
            }
        });
    }
    
    // Refresh button action
    document.getElementById('refreshButton').addEventListener('click', function() {
        window.location.reload();
    });
    
    // Client-side search (in addition to server-side)
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        if (searchTerm.length < 2) return; // Only search when at least 2 characters
        
        const rows = document.querySelectorAll('.user-row');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            if (text.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
</script>

</body>
</html>

<?php $conn->close(); ?>