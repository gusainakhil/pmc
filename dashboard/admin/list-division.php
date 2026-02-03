<?php
// Include database connection
include "../../connection.php";

// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch divisions from the database
$result = $conn->query("SELECT * FROM baris_division");
$totalDivisions = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Division List | Station Cleaning</title>
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
                    <h1 class="text-xl font-bold text-gray-800">Division Management</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="create-division.php" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Create Division
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
                                <span class="text-gray-700">Divisions</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <!-- Overview Section -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-800 mb-4">Division List</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                    <div class="card p-5">
                        <div class="flex items-center">
                            <div class="rounded-full bg-blue-100 p-3 mr-4">
                                <i class="fas fa-building text-blue-600"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Total Divisions</p>
                                <h3 class="text-2xl font-bold"><?php echo $totalDivisions; ?></h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card p-5">
                        <div class="flex items-center">
                            <div class="rounded-full bg-green-100 p-3 mr-4">
                                <i class="fas fa-user-check text-green-600"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Active Users</p>
                                <h3 class="text-2xl font-bold">24</h3>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card p-5">
                        <div class="flex items-center">
                            <div class="rounded-full bg-purple-100 p-3 mr-4">
                                <i class="fas fa-chart-line text-purple-600"></i>
                            </div>
                            <div>
                                <p class="text-gray-500 text-sm">Recent Activity</p>
                                <h3 class="text-2xl font-bold">15</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Division List -->
            <div class="card mb-6">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex flex-wrap items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-800">All Divisions</h3>
                        
                        <div class="flex items-center space-x-2 mt-2 sm:mt-0">
                            <div class="relative">
                                <input type="text" placeholder="Search divisions..." class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                            </div>
                            
                            <div class="relative">
                                <select class="appearance-none px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-8">
                                    <option>All</option>
                                    <option>Active</option>
                                    <option>Inactive</option>
                                </select>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 pointer-events-none"></i>
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
                                        <span>Division ID</span>
                                        <button class="ml-1 text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-sort"></i>
                                        </button>
                                    </div>
                                </th>
                                <th>
                                    <div class="flex items-center">
                                        <span>Division Name</span>
                                        <button class="ml-1 text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-sort"></i>
                                        </button>
                                    </div>
                                </th>
                                <th>Status</th>
                                <th>Associated Users</th>
                                <th class="rounded-tr-lg">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                $result->data_seek(0); // Reset result pointer
                                while ($row = $result->fetch_assoc()) {
                                    // Generate a random status for demo purposes
                                    $statusRand = rand(0, 10);
                                    $status = $statusRand > 2 ? 'Active' : 'Inactive';
                                    $statusClass = $status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800';
                                    
                                    // Generate random number of users for demo purposes
                                    $usersCount = rand(1, 15);
                                    
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['DivisionId']) . "</td>";
                                    echo "<td class='font-medium'>" . htmlspecialchars($row['DivisionName']) . "</td>";
                                    echo "<td><span class='px-2 py-1 text-xs rounded-full $statusClass'>$status</span></td>";
                                    echo "<td>$usersCount users</td>";
                                    echo "<td class='flex space-x-2'>";
                                    echo "<a href='edit-division.php?id=" . $row['DivisionId'] . "' class='btn btn-edit'><i class='fas fa-edit mr-1'></i> Edit</a>";
                                    echo "<a href='delete-division.php?id=" . $row['DivisionId'] . "' class='btn btn-delete' onclick='return confirm(\"Are you sure you want to delete this division?\")'><i class='fas fa-trash-alt mr-1'></i> Delete</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='py-4 text-center text-gray-500'>No divisions found. <a href='create-division.php' class='text-blue-500 hover:underline'>Create your first division</a>.</td></tr>";
                            }

                            // Close the connection
                            $conn->close();
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <?php if ($totalDivisions > 0): ?>
                <div class="flex items-center justify-between p-4 border-t border-gray-200">
                    <div class="text-sm text-gray-500">
                        Showing <span class="font-medium"><?php echo $totalDivisions; ?></span> divisions
                    </div>
                    
                    <div class="flex space-x-1">
                        <button class="px-3 py-1 rounded border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-50" disabled>
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button class="px-3 py-1 rounded border border-gray-300 bg-blue-50 text-blue-600">1</button>
                        <button class="px-3 py-1 rounded border border-gray-300 text-gray-500 hover:bg-gray-50 disabled:opacity-50" disabled>
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Quick Links -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div class="card p-4">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-info-circle text-blue-600"></i>
                        </div>
                        <h3 class="font-semibold">Managing Divisions</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        Divisions help you organize stations within your railway network. 
                        Create divisions based on geographical areas or operational zones.
                    </p>
                    <a href="#" class="text-sm text-blue-600 font-medium hover:text-blue-800 flex items-center">
                        <span>Learn more about divisions</span>
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
                        divisions or setting up your organization structure.
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