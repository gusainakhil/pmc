<?php

// Include database connection
include "../../connection.php";

// Initialize variables
$orgName = "";
$orgId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = "";
$messageType = ""; // 'success' or 'error'

// Fetch organization data if ID is set
if ($orgId > 0) {
    $stmt = $conn->prepare("SELECT * FROM baris_organization WHERE OrgID = ?");
    $stmt->bind_param("i", $orgId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $orgName = $row['db_Orgname'];
    } else {
        $message = "Organization not found.";
        $messageType = "error";
    }
    $stmt->close();
} else {
    $message = "Invalid organization ID.";
    $messageType = "error";
}

// Get related statistics
$divisionCount = 0;
$userCount = 0;
if ($orgId > 0) {
    $divQuery = $conn->query("SELECT COUNT(*) as count FROM baris_division WHERE OrgID = $orgId");
    $userQuery = $conn->query("SELECT COUNT(*) as count FROM baris_userlogin WHERE OrgID = $orgId");
    
    if ($divQuery && $divResult = $divQuery->fetch_assoc()) {
        $divisionCount = $divResult['count'];
    }
    
    if ($userQuery && $userResult = $userQuery->fetch_assoc()) {
        $userCount = $userResult['count'];
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $orgId > 0) {
    $orgName = trim($_POST['org_name']);
    if ($orgName != "") {
        $stmt = $conn->prepare("UPDATE baris_organization SET db_Orgname = ? WHERE OrgID = ?");
        $stmt->bind_param("si", $orgName, $orgId);
        if ($stmt->execute()) {
            $message = "Organization updated successfully.";
            $messageType = "success";
        } else {
            $message = "Error updating organization: " . $conn->error;
            $messageType = "error";
        }
        $stmt->close();
    } else {
        $message = "Organization name cannot be empty.";
        $messageType = "error";
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Organization | Station Cleaning</title>
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
                    <h1 class="text-xl font-bold text-gray-800">Edit Organization</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="list-organisation.php" class="flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Organizations
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
                                <a href="list-organisation.php" class="text-gray-500 hover:text-blue-600">Organizations</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                                <span class="text-gray-700">Edit Organization</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <?php if ($orgId > 0 && $orgName !== ""): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Main Card -->
                    <div class="md:col-span-2">
                        <div class="card mb-6">
                            <div class="p-6 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-lg font-semibold text-gray-800">Organization Details</h2>
                                        <p class="text-gray-500 text-sm mt-1">Update organization information</p>
                                    </div>
                                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <i class="fas fa-building text-blue-600"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Success/Error Messages -->
                            <?php if ($message): ?>
                                <div class="mx-6 mt-6 <?php echo $messageType === 'success' ? 'bg-green-50 border-l-4 border-green-500' : 'bg-red-50 border-l-4 border-red-500'; ?> p-4 rounded">
                                    <div class="flex items-center">
                                        <i class="<?php echo $messageType === 'success' ? 'fas fa-check-circle text-green-500' : 'fas fa-exclamation-circle text-red-500'; ?> mr-3"></i>
                                        <p class="<?php echo $messageType === 'success' ? 'text-green-700' : 'text-red-700'; ?>"><?php echo htmlspecialchars($message); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <form method="POST" class="p-6" id="organizationForm">
                                <div class="mb-6">
                                    <label for="org_name" class="block text-sm font-medium text-gray-700 mb-2">Organization Name</label>
                                    <input 
                                        type="text" 
                                        id="org_name" 
                                        name="org_name" 
                                        value="<?php echo htmlspecialchars($orgName); ?>" 
                                        class="form-input" 
                                        placeholder="Enter organization name"
                                        required
                                    >
                                    <p class="mt-1 text-xs text-gray-500">This name will be displayed across the system</p>
                                </div>
                                
                                <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                                    <a href="list-organisation.php" class="btn btn-secondary">
                                        Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i>
                                        Update Organization
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Side Panel -->
                    <div>
                        <!-- Organization Stats -->
                        <div class="card p-5 mb-6">
                            <h3 class="font-semibold mb-4">Organization Overview</h3>
                            
                            <div class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                            <i class="fas fa-id-card text-blue-600 text-sm"></i>
                                        </div>
                                        <span class="text-sm text-gray-600">Organization ID</span>
                                    </div>
                                    <span class="font-medium"><?php echo $orgId; ?></span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center mr-3">
                                            <i class="fas fa-sitemap text-indigo-600 text-sm"></i>
                                        </div>
                                        <span class="text-sm text-gray-600">Divisions</span>
                                    </div>
                                    <span class="font-medium"><?php echo $divisionCount; ?></span>
                                </div>
                                
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center mr-3">
                                            <i class="fas fa-users text-green-600 text-sm"></i>
                                        </div>
                                        <span class="text-sm text-gray-600">Users</span>
                                    </div>
                                    <span class="font-medium"><?php echo $userCount; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Tips Card -->
                        <div class="card p-5">
                            <div class="flex items-center mb-4">
                                <div class="w-8 h-8 rounded-full bg-amber-100 flex items-center justify-center mr-3">
                                    <i class="fas fa-lightbulb text-amber-600"></i>
                                </div>
                                <h3 class="font-semibold">Tips</h3>
                            </div>
                            <ul class="space-y-2 text-sm text-gray-600">
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                    <span>Use clear, descriptive names for organizations</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                    <span>Consistent naming helps with reporting and organization</span>
                                </li>
                                <li class="flex items-start">
                                    <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                                    <span>Organizations can contain multiple divisions</span>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Related Actions -->
                <div class="card p-5 mb-6">
                    <h3 class="font-semibold mb-4">Related Actions</h3>
                    <div class="flex flex-wrap gap-3">
                        <a href="list-division.php?org=<?php echo $orgId; ?>" class="btn btn-secondary">
                            <i class="fas fa-sitemap mr-2"></i>
                            View Divisions
                        </a>
                        <a href="create-division.php?org=<?php echo $orgId; ?>" class="btn btn-secondary">
                            <i class="fas fa-plus mr-2"></i>
                            Add Division
                        </a>
                        <?php if ($divisionCount == 0 && $userCount == 0): ?>
                        <button onclick="confirmDelete(<?php echo $orgId; ?>, '<?php echo htmlspecialchars($orgName); ?>')" class="btn btn-secondary text-red-600 border-red-600 hover:bg-red-50">
                            <i class="fas fa-trash-alt mr-2"></i>
                            Delete Organization
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- Not Found State -->
                <div class="card p-8 text-center">
                    <div class="w-16 h-16 bg-red-100 rounded-full mx-auto flex items-center justify-center mb-4">
                        <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Organization Not Found</h3>
                    <p class="text-gray-500 mb-6">The organization you are trying to edit does not exist or has been removed.</p>
                    <a href="list-organisation.php" class="btn btn-primary">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Return to Organization List
                    </a>
                </div>
            <?php endif; ?>
            
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
    
    // Form validation
    const form = document.getElementById('organizationForm');
    
    if (form) {
        form.addEventListener('submit', function(event) {
            const orgName = document.getElementById('org_name');
            
            if (orgName.value.trim() === '') {
                event.preventDefault();
                orgName.classList.add('border-red-500');
                
                // Add error message if not already present
                const errorElement = document.querySelector('.input-error');
                if (!errorElement) {
                    const errorMsg = document.createElement('p');
                    errorMsg.className = 'text-red-500 text-xs mt-1 input-error';
                    errorMsg.textContent = 'Organization name is required';
                    orgName.insertAdjacentElement('afterend', errorMsg);
                }
            }
        });
        
        // Clear validation styling on input
        const orgName = document.getElementById('org_name');
        if (orgName) {
            orgName.addEventListener('input', function() {
                this.classList.remove('border-red-500');
                const errorElement = document.querySelector('.input-error');
                if (errorElement) {
                    errorElement.remove();
                }
            });
        }
    }
    
    // Delete confirmation modal
    function confirmDelete(orgId, orgName) {
        const deleteModal = document.getElementById('deleteModal');
        const cancelDelete = document.getElementById('cancelDelete');
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        const deleteMessage = document.getElementById('deleteMessage');
        
        deleteMessage.textContent = `Are you sure you want to delete the organization "${orgName}"?`;
        confirmDeleteBtn.href = `list-organisation.php?delete=${orgId}`;
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
</script>

</body>
</html>