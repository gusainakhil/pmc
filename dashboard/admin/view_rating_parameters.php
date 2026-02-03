<?php
session_start();

include "../../connection.php";

// Get all rating parameters with station information
$parameters = [];
$query = "SELECT rp.*, bs.stationName 
          FROM rating_parameters rp 
          LEFT JOIN baris_station bs ON rp.station_id = bs.stationId 
          ORDER BY bs.stationName ASC, rp.value DESC";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $stationId = $row['station_id'];
        if (!isset($parameters[$stationId])) {
            $parameters[$stationId] = [
                'stationName' => $row['stationName'],
                'parameters' => []
            ];
        }
        $parameters[$stationId]['parameters'][] = $row;
    }
}

// Get station statistics
$stats = [];
$statsQuery = "SELECT 
                COUNT(DISTINCT station_id) as total_stations,
                COUNT(*) as total_parameters,
                AVG(value) as avg_value
               FROM rating_parameters";
$statsResult = $conn->query($statsQuery);
if ($statsResult && $statsRow = $statsResult->fetch_assoc()) {
    $stats = $statsRow;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View All Rating Parameters | Station Cleaning</title>
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
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
            transition: all 0.2s;
            text-decoration: none;
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
            color: #6b7280;
            border: 1px solid #d1d5db;
        }
        
        .btn-secondary:hover {
            background-color: #f9fafb;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 1.5rem;
        }
        
        .stat-card-green {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }
        
        .stat-card-purple {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #374151;
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
                    <h1 class="text-xl font-bold text-gray-800">Rating Parameters Overview</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="create-rating-parameters.php" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Add Parameters
                    </a>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="p-6">
            <!-- Breadcrumb -->
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
                                <a href="#" class="text-gray-500 hover:text-blue-600">Ratings</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                                <span class="text-gray-700">View Parameters</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="stat-card">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm font-medium">Total Stations</p>
                <p class="text-2xl font-bold"><?= $stats['total_stations'] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                <i class="fas fa-map-marker-alt text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="stat-card stat-card-green">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm font-medium">Total Parameters</p>
                <p class="text-2xl font-bold"><?= $stats['total_parameters'] ?? 0 ?></p>
            </div>
            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                <i class="fas fa-star text-xl"></i>
            </div>
        </div>
    </div>
</div>

            
            <!-- Parameters by Station -->
            <div class="space-y-6">
                <?php if (empty($parameters)): ?>
                    <div class="card p-8 text-center">
                        <i class="fas fa-star text-gray-400 text-6xl mb-4"></i>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">No Rating Parameters Found</h3>
                        <p class="text-gray-500 mb-6">Start by creating rating parameters for your stations.</p>
                        <a href="create-rating-parameters.php" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>
                            Create First Parameter
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($parameters as $stationId => $stationData): ?>
                        <div class="card">
                            <div class="p-6 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                                            <i class="fas fa-map-marker-alt text-blue-600"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-lg font-semibold text-gray-800">
                                                <?= htmlspecialchars($stationData['stationName']) ?>
                                            </h3>
                                            <p class="text-sm text-gray-500">
                                                <?= count($stationData['parameters']) ?> rating parameters
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="create-rating-parameters.php?station_id=<?= $stationId ?>" class="btn btn-secondary">
                                            <i class="fas fa-plus mr-1"></i>
                                            Add More
                                        </a>
                                        <a href="edit_rating_parameters.php?station_id=<?= $stationId ?>" class="btn btn-primary">
                                            <i class="fas fa-edit mr-1"></i>
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <?php foreach ($stationData['parameters'] as $param): ?>
                                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                                            <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-blue-100 text-blue-800 font-bold text-sm mr-3">
                                                <?= $param['value'] ?>
                                            </span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate">
                                                    <?= htmlspecialchars($param['rating_name']) ?>
                                                </p>
                                                <p class="text-xs text-gray-500">
                                                    <?= date('M j, Y', strtotime($param['created_at'])) ?>
                                                </p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
const sidebarElement = document.querySelector('aside');

if (mobileMenuButton && sidebarElement) {
    mobileMenuButton.addEventListener('click', () => {
        sidebarElement.classList.toggle('-translate-x-full');
    });
}
</script>

</body>
</html>
