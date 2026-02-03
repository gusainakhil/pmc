<?php
session_start();
include "connection.php";

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle search functionality
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$whereClause = "WHERE db_usertype = 'viewer' AND reportType = 'PMC'";

if (!empty($searchTerm)) {
    $searchTerm = $conn->real_escape_string($searchTerm);
    $whereClause .= " AND (db_userLoginName LIKE '%$searchTerm%' OR db_email LIKE '%$searchTerm%' OR db_phone LIKE '%$searchTerm%' OR OrgName LIKE '%$searchTerm%')";
}

// Pagination setup
$recordsPerPage = 10;
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $recordsPerPage;

// Count total records for pagination
$countQuery = "SELECT COUNT(*) as total FROM baris_userlogin $whereClause";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Fetch CHI users with pagination
$query = "SELECT id, db_userLoginName, db_username, db_email, db_phone, db_designation, OrgName, db_valid_from, db_valid, LastLogin 
          FROM baris_userlogin 
          $whereClause 
          ORDER BY id DESC 
          LIMIT $offset, $recordsPerPage";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CHI List | Station Cleaning</title>
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
                    <h1 class="text-xl font-bold text-gray-800">CHI List</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="create-chi.php" class="flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                        <i class="fas fa-plus mr-2"></i>
                        Create CHI
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
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                                <span class="text-gray-700">CHI Users</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <!-- Main Card -->
            <div class="card mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                        <div class="mb-4 md:mb-0">
                            <h2 class="text-lg font-semibold text-gray-800">Chief Health Inspectors</h2>
                            <p class="text-gray-500 text-sm mt-1">Manage CHI users and their access</p>
                        </div>
                        
                        <div class="flex flex-col sm:flex-row gap-3">
                            <form action="" method="GET" class="flex">
                                <input 
                                    type="text" 
                                    name="search" 
                                    placeholder="Search CHI users..." 
                                    value="<?php echo htmlspecialchars($searchTerm); ?>" 
                                    class="border border-gray-300 rounded-l-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-blue-300 focus:border-transparent"
                                >
                                <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded-r-lg hover:bg-blue-600">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                            
                            <a href="create-chi.php" class="flex items-center justify-center bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                                <i class="fas fa-plus mr-2"></i>
                                Create CHI
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Contact
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Station
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Created
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Last Login
                                </th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php 
                            if ($result && $result->num_rows > 0) {
                                while($row = $result->fetch_assoc()) {
                                    $today = new DateTime();
                                    $validUntil = new DateTime($row['db_valid']);
                                    $isValid = $validUntil > $today;
                            ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                            <span class="font-bold text-blue-600">
                                                <?php echo strtoupper(substr($row['db_userLoginName'], 0, 1)); ?>
                                            </span>
                                        </div>
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($row['db_userLoginName']); ?>
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                <?php echo htmlspecialchars($row['db_username']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['db_email']); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($row['db_phone']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars($row['OrgName'] ?: 'N/A'); ?>
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        <?php echo htmlspecialchars($row['db_designation']); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="badge <?php echo $isValid ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $isValid ? 'Active' : 'Expired'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('d M Y', strtotime($row['db_valid_from'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php 
                                        if ($row['LastLogin']) {
                                            echo date('d M Y', strtotime($row['LastLogin']));
                                        } else {
                                            echo '<span class="text-gray-400">Never</span>';
                                        }
                                    ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <a href="view-edit-chi.php?id=<?php echo $row['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View/Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['db_userLoginName']); ?>')" class="text-red-600 hover:text-red-900" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                }
                            } else {
                            ?>
                            <tr>
                                <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                    <i class="fas fa-users-slash text-4xl mb-3 opacity-30"></i>
                                    <p>No CHI users found<?php echo !empty($searchTerm) ? ' matching "' . htmlspecialchars($searchTerm) . '"' : ''; ?></p>
                                    <?php if (!empty($searchTerm)): ?>
                                        <a href="chi-list.php" class="text-blue-500 hover:underline mt-2 inline-block">Clear search</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            Showing <span class="font-medium"><?php echo $offset + 1; ?></span> to 
                            <span class="font-medium"><?php echo min($offset + $recordsPerPage, $totalRecords); ?></span> of 
                            <span class="font-medium"><?php echo $totalRecords; ?></span> results
                        </div>
                        <div class="flex items-center space-x-1">
                            <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" class="px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php
                            // Show limited number of page links
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<a href="?page=1' . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '') . '" class="px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50">1</a>';
                                if ($startPage > 2) {
                                    echo '<span class="px-2 py-1">...</span>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                $activeClass = ($i === $page) ? 'bg-blue-50 text-blue-600 border-blue-500' : 'text-gray-600 hover:bg-gray-50';
                                echo '<a href="?page=' . $i . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '') . '" class="px-3 py-1 rounded border ' . $activeClass . '">' . $i . '</a>';
                            }
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<span class="px-2 py-1">...</span>';
                                }
                                echo '<a href="?page=' . $totalPages . (!empty($searchTerm) ? '&search=' . urlencode($searchTerm) : '') . '" class="px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50">' . $totalPages . '</a>';
                            }
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo !empty($searchTerm) ? '&search=' . urlencode($searchTerm) : ''; ?>" class="px-3 py-1 rounded border border-gray-300 text-gray-600 hover:bg-gray-50">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="card p-5">
                    <div class="flex justify-between mb-2">
                        <div class="text-gray-500 text-sm">Total CHI Users</div>
                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-users text-blue-600"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-800">
                        <?php
                        $countTotal = $conn->query("SELECT COUNT(*) as total FROM baris_userlogin WHERE db_usertype = 'viewer' AND reportType = 'PMC'");
                        echo $countTotal->fetch_assoc()['total'];
                        ?>
                    </div>
                </div>
                
                <div class="card p-5">
                    <div class="flex justify-between mb-2">
                        <div class="text-gray-500 text-sm">Active Users</div>
                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center">
                            <i class="fas fa-user-check text-green-600"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-800">
                        <?php
                        $currentDate = date('Y-m-d H:i:s');
                        $countActive = $conn->query("SELECT COUNT(*) as active FROM baris_userlogin WHERE db_usertype = 'viewer' AND reportType = 'PMC' AND db_valid > '$currentDate'");
                        echo $countActive->fetch_assoc()['active'];
                        ?>
                    </div>
                </div>
                
                <div class="card p-5">
                    <div class="flex justify-between mb-2">
                        <div class="text-gray-500 text-sm">Expired Users</div>
                        <div class="w-8 h-8 rounded-full bg-red-100 flex items-center justify-center">
                            <i class="fas fa-user-clock text-red-600"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-800">
                        <?php
                        $countExpired = $conn->query("SELECT COUNT(*) as expired FROM baris_userlogin WHERE db_usertype = 'viewer' AND reportType = 'PMC' AND db_valid <= '$currentDate'");
                        echo $countExpired->fetch_assoc()['expired'];
                        ?>
                    </div>
                </div>
                
                <div class="card p-5">
                    <div class="flex justify-between mb-2">
                        <div class="text-gray-500 text-sm">Never Logged In</div>
                        <div class="w-8 h-8 rounded-full bg-yellow-100 flex items-center justify-center">
                            <i class="fas fa-user-slash text-yellow-600"></i>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-gray-800">
                        <?php
                        $countNeverLogged = $conn->query("SELECT COUNT(*) as never_logged FROM baris_userlogin WHERE db_usertype = 'viewer' AND reportType = 'PMC' AND LastLogin IS NULL");
                        echo $countNeverLogged->fetch_assoc()['never_logged'];
                        ?>
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

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Confirm Deletion</h3>
                <button onclick="closeDeleteModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <p class="text-gray-600 mb-6">Are you sure you want to delete <span id="deleteUserName" class="font-semibold"></span>? This action cannot be undone.</p>
            <div class="flex justify-end space-x-3">
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded">
                    Cancel
                </button>
                <a id="confirmDeleteBtn" href="#" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded">
                    <i class="fas fa-trash mr-2"></i>Delete
                </a>
            </div>
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
    
    // Delete modal functionality
    const deleteModal = document.getElementById('deleteModal');
    const deleteUserName = document.getElementById('deleteUserName');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    
    function confirmDelete(userId, userName) {
        deleteUserName.textContent = userName;
        confirmDeleteBtn.href = `delete-chi.php?id=${userId}`;
        deleteModal.classList.remove('hidden');
    }
    
    function closeDeleteModal() {
        deleteModal.classList.add('hidden');
    }
    
    // Close modal if clicking outside of it
    window.addEventListener('click', (e) => {
        if (e.target === deleteModal) {
            closeDeleteModal();
        }
    });
</script>

</body>
</html>
<?php
$conn->close();
?>
