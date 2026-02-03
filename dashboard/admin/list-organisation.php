<?php

// Include database connection
include "../../connection.php";
// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Delete organization if requested
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $orgId = intval($_GET['delete']);
    
    // Check if organization has associated divisions or stations before deleting
    $checkDivisions = $conn->query("SELECT COUNT(*) as count FROM baris_division WHERE OrgID = $orgId");
    $checkUsers = $conn->query("SELECT COUNT(*) as count FROM baris_userlogin WHERE OrgID = $orgId");
    
    $hasDivisions = ($checkDivisions && $checkDivisions->fetch_assoc()['count'] > 0);
    $hasUsers = ($checkUsers && $checkUsers->fetch_assoc()['count'] > 0);
    
    if ($hasDivisions || $hasUsers) {
        $deleteError = "Cannot delete organization because it has associated divisions or users.";
    } else {
        // Safe to delete
        $deleteStmt = $conn->prepare("DELETE FROM baris_organization WHERE OrgID = ?");
        $deleteStmt->bind_param("i", $orgId);
        
        if ($deleteStmt->execute()) {
            $deleteSuccess = "Organization deleted successfully.";
        } else {
            $deleteError = "Error deleting organization: " . $conn->error;
        }
        $deleteStmt->close();
    }
}

// Count total organizations
$countQuery = $conn->query("SELECT COUNT(*) as total FROM baris_organization");
$totalOrganizations = ($countQuery) ? $countQuery->fetch_assoc()['total'] : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization List | Station Cleaning</title>
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
            font-size: 0.875rem;
            color: #4b5563;
            border-bottom: 1px solid #e5e7eb;
        }
        
        td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
            color: #1f2937;
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
        
        .btn-edit {
            background-color: #e0e7ff;
            color: #4f46e5;
        }
        
        .btn-edit:hover {
            background-color: #c7d2fe;
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
                    <h1 class="text-xl font-bold text-gray-800">Organization Management</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="create-organization.php" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Create Organization
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
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                                <span class="text-gray-700">Organizations</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($deleteSuccess)): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <p class="text-green-700"><?php echo htmlspecialchars($deleteSuccess); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($deleteError)): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <p class="text-red-700"><?php echo htmlspecialchars($deleteError); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Overview Section -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Organization List</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="card p-5">
                        <div class="flex items-center">
                            <div class="rounded-full bg-blue-100 p-3 mr-4">
                                <i class="fas fa-building text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Organizations</p>
                                <h3 class="text-2xl font-bold"><?php echo $totalOrganizations; ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card p-5">
                        <div class="flex items-center">
                            <div class="rounded-full bg-green-100 p-3 mr-4">
                                <i class="fas fa-sitemap text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Active Divisions</p>
                                <h3 class="text-2xl font-bold">
                                    <?php 
                                    $divCount = $conn->query("SELECT COUNT(*) as count FROM baris_division");
                                    echo ($divCount) ? $divCount->fetch_assoc()['count'] : 0;
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card p-5">
                        <div class="flex items-center">
                            <div class="rounded-full bg-purple-100 p-3 mr-4">
                                <i class="fas fa-users text-purple-600"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Active Users</p>
                                <h3 class="text-2xl font-bold">
                                    <?php 
                                    $userCount = $conn->query("SELECT COUNT(*) as count FROM baris_userlogin");
                                    echo ($userCount) ? $userCount->fetch_assoc()['count'] : 0;
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Organization List -->
            <div class="card mb-6">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex flex-wrap items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">All Organizations</h3>
                        
                        <div class="flex items-center space-x-2 mt-2 sm:mt-0">
                            <div class="relative">
                                <input type="text" id="searchOrganization" placeholder="Search organizations..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th class="rounded-tl-lg">
                                    <div class="flex items-center">
                                        <span>Organization ID</span>
                                        <button class="ml-1 text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-sort"></i>
                                        </button>
                                    </div>
                                </th>
                                <th>
                                    <div class="flex items-center">
                                        <span>Organization Name</span>
                                        <button class="ml-1 text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-sort"></i>
                                        </button>
                                    </div>
                                </th>
                                <th>Divisions</th>
                                <th>Users</th>
                                <th class="rounded-tr-lg">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch organizations from the database
                            $result = $conn->query("SELECT * FROM baris_organization ORDER BY db_Orgname ASC");

                            if ($result && $result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    // Count divisions and users for this organization
                                    $divQuery = $conn->query("SELECT COUNT(*) as count FROM baris_division WHERE OrgID = " . $row['OrgID']);
                                    $userQuery = $conn->query("SELECT COUNT(*) as count FROM baris_userlogin WHERE OrgID = " . $row['OrgID']);
                                    
                                    $divCount = ($divQuery) ? $divQuery->fetch_assoc()['count'] : 0;
                                    $userCount = ($userQuery) ? $userQuery->fetch_assoc()['count'] : 0;
                                    
                                    $divBadge = $divCount > 0 ? 
                                        "<span class='px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800'>$divCount Division" . ($divCount > 1 ? "s" : "") . "</span>" : 
                                        "<span class='px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800'>No Divisions</span>";
                                    
                                    $userBadge = $userCount > 0 ? 
                                        "<span class='px-2 py-1 text-xs rounded-full bg-green-100 text-green-800'>$userCount User" . ($userCount > 1 ? "s" : "") . "</span>" : 
                                        "<span class='px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800'>No Users</span>";
                                    
                                    echo "<tr class='organization-row'>";
                                    echo "<td>" . htmlspecialchars($row['OrgID']) . "</td>";
                                    echo "<td class='font-medium'>" . htmlspecialchars($row['db_Orgname']) . "</td>";
                                    echo "<td>$divBadge</td>";
                                    echo "<td>$userBadge</td>";
                                    echo "<td>";
                                    echo "<div class='flex space-x-2'>";
                                    echo "<a href='edit-organisation.php?id=" . $row['OrgID'] . "' class='btn btn-edit'><i class='fas fa-edit mr-1'></i> Edit</a>";
                                    echo "<button onclick='confirmDelete(" . $row['OrgID'] . ", \"" . htmlspecialchars($row['db_Orgname']) . "\")' class='btn btn-delete'><i class='fas fa-trash-alt mr-1'></i> Delete</button>";
                                    echo "</div>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='py-4 text-center text-gray-500'>No organizations found. <a href='create-organization.php' class='text-blue-500 hover:underline'>Create your first organization</a>.</td></tr>";
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
                            <i class="fas fa-sitemap text-blue-600"></i>
                        </div>
                        <h3 class="font-semibold">Organization Structure</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Organizations are the top level of your hierarchy. Create divisions within organizations, 
                        and stations within divisions to build your complete operational structure.
                    </p>
                    <a href="#" class="text-sm text-blue-600 font-medium hover:text-blue-800 flex items-center">
                        <span>Learn more about organizational hierarchy</span>
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
                        Our support team is here to help you with any questions about managing 
                        organizations or setting up your operational structure.
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
            <p class="text-gray-500" id="deleteMessage">Are you sure you want to delete this organization?</p>
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
    
    // Search functionality
    const searchInput = document.getElementById('searchOrganization');
    const orgRows = document.querySelectorAll('.organization-row');
    
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        
        orgRows.forEach(row => {
            const orgName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
            const orgId = row.querySelector('td:nth-child(1)').textContent.toLowerCase();
            
            if (orgName.includes(searchTerm) || orgId.includes(searchTerm)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    });
    
    // Delete confirmation modal
    const deleteModal = document.getElementById('deleteModal');
    const cancelDelete = document.getElementById('cancelDelete');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
    const deleteMessage = document.getElementById('deleteMessage');
    
    function confirmDelete(orgId, orgName) {
        deleteMessage.textContent = `Are you sure you want to delete the organization "${orgName}"?`;
        confirmDeleteBtn.href = `list-organisation.php?delete=${orgId}`;
        deleteModal.classList.remove('hidden');
    }
    
    cancelDelete.addEventListener('click', () => {
        deleteModal.classList.add('hidden');
    });
    
    // Close modal when clicking outside
    deleteModal.addEventListener('click', (e) => {
        if (e.target === deleteModal) {
            deleteModal.classList.add('hidden');
        }
    });
</script>

</body>
</html>