<?php
session_start();

include "../../connection.php";

// Get all feedback stations with user information
$feedbackStations = [];
$query = "SELECT fs.*, fu.username, fu.id as user_id 
          FROM feedback_stations fs 
          LEFT JOIN feedback_users fu ON fs.id = fu.station_id 
          ORDER BY fs.name ASC";

$result = $conn->query($query);
if ($result) {
    $feedbackStations = $result->fetch_all(MYSQLI_ASSOC);
}

// Get statistics
$stats = [];
$statsQuery = "SELECT COUNT(*) as total_stations FROM feedback_stations";
$statsResult = $conn->query($statsQuery);
if ($statsResult && $statsRow = $statsResult->fetch_assoc()) {
    $stats = $statsRow;
}

// Get user count
$userCountQuery = $conn->query("SELECT COUNT(*) as user_count FROM feedback_users");
if ($userCountQuery && $userRow = $userCountQuery->fetch_assoc()) {
    $stats['user_count'] = $userRow['user_count'];
}

// Group by zones
$zoneStats = [];
$zoneQuery = "SELECT zone, COUNT(*) as count FROM feedback_stations GROUP BY zone";
$zoneResult = $conn->query($zoneQuery);
if ($zoneResult) {
    while ($row = $zoneResult->fetch_assoc()) {
        $zoneStats[$row['zone']] = $row['count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Feedback Stations | Station Management</title>
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
        
        .btn-success {
            background-color: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
        
        .btn-danger {
            background-color: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.75rem;
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
        
        .stat-card-orange {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #374151;
        }
        
        .zone-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .zone-north { background-color: #dbeafe; color: #1e40af; }
        .zone-south { background-color: #dcfce7; color: #166534; }
        .zone-east { background-color: #fef3c7; color: #a16207; }
        .zone-west { background-color: #fce7f3; color: #be185d; }
        .zone-central { background-color: #e0e7ff; color: #5b21b6; }
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
                    <h1 class="text-xl font-bold text-gray-800">Feedback Stations Overview</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="create_feedback_station.php" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Add New Station
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
                                <a href="#" class="text-gray-500 hover:text-blue-600">Feedback</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                                <span class="text-gray-700">View Stations</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <!-- Success/Error Messages -->
            <?php if (isset($_GET['message'])): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <p class="text-green-700"><?php echo htmlspecialchars($_GET['message']); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['error'])): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <p class="text-red-700"><?php echo htmlspecialchars($_GET['error']); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
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
                            <p class="text-blue-100 text-sm font-medium">Active Users</p>
                            <p class="text-2xl font-bold"><?= $stats['user_count'] ?? 0 ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Zone Distribution -->
            <?php if (!empty($zoneStats)): ?>
            <div class="card mb-6">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-chart-pie mr-2 text-blue-600"></i>
                        Zone Distribution
                    </h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                        <?php foreach ($zoneStats as $zone => $count): ?>
                            <div class="text-center">
                                <div class="zone-badge zone-<?= strtolower($zone) ?> mb-2">
                                    <?= htmlspecialchars($zone) ?>
                                </div>
                                <p class="text-2xl font-bold text-gray-800"><?= $count ?></p>
                                <p class="text-sm text-gray-500">stations</p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Stations List -->
            <div class="card">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-list mr-2 text-blue-600"></i>
                        All Feedback Stations
                    </h3>
                </div>
                
                <div class="p-6">
                    <?php if (empty($feedbackStations)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-map-marker-alt text-gray-400 text-6xl mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">No Stations Found</h3>
                            <p class="text-gray-500 mb-6">Start by creating your first feedback station.</p>
                            <a href="create_feedback_station.php" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>
                                Create First Station
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            <?php foreach ($feedbackStations as $station): ?>
                                <div class="bg-gray-50 p-6 rounded-lg">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-lg font-semibold text-gray-800">
                                            <?= htmlspecialchars($station['name']) ?>
                                        </h4>
                                        <span class="zone-badge zone-<?= strtolower($station['zone']) ?>">
                                            <?= htmlspecialchars($station['zone']) ?>
                                        </span>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-building w-4 mr-2"></i>
                                            <span><?= htmlspecialchars($station['division']) ?></span>
                                        </div>
                                        
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-bullseye w-4 mr-2"></i>
                                            <span>Target: <?= $station['feedback_target'] ?> feedbacks/day</span>
                                        </div>
                                        
                                        <div class="flex items-center text-sm text-gray-600">
                                            <i class="fas fa-user w-4 mr-2"></i>
                                            <span>
                                                <?php if ($station['username']): ?>
                                                    <?= htmlspecialchars($station['username']) ?>
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 ml-2">
                                                        Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-red-600">No user assigned</span>
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">
                                        <div class="flex items-center text-xs text-gray-500">
                                            <i class="fas fa-calendar mr-1"></i>
                                            ID: <?= $station['id'] ?>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button class="btn btn-secondary btn-sm" onclick="openEditModal(<?= $station['id'] ?>, '<?= htmlspecialchars($station['name']) ?>', '<?= htmlspecialchars($station['zone']) ?>', '<?= htmlspecialchars($station['division']) ?>', <?= $station['feedback_target'] ?>, '<?= htmlspecialchars($station['username'] ?? '') ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteStation(<?= $station['id'] ?>, '<?= htmlspecialchars($station['name']) ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
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

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Feedback Station</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editForm" method="POST" action="update_feedback_station.php">
                <input type="hidden" id="editStationId" name="station_id">
                
                <div class="mb-4">
                    <label for="editStationName" class="block text-sm font-medium text-gray-700 mb-2">Station Name</label>
                    <input type="text" id="editStationName" name="station_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" readonly>
                </div>
                
                <div class="mb-4">
                    <label for="editZone" class="block text-sm font-medium text-gray-700 mb-2">Zone</label>
                    <input type="text" id="editZone" name="zone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" readonly>
                </div>
                
                <div class="mb-4">
                    <label for="editDivision" class="block text-sm font-medium text-gray-700 mb-2">Division</label>
                    <input type="text" id="editDivision" name="division" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" readonly>
                </div>
                
                <div class="mb-4">
                    <label for="editFeedbackTarget" class="block text-sm font-medium text-gray-700 mb-2">Feedback Target</label>
                    <input type="number" id="editFeedbackTarget" name="feedback_target" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" min="1" required>
                </div>
                
                <div class="mb-6">
                    <label for="editUsername" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input type="text" id="editUsername" name="username" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" minlength="3" required>
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Update Station
                    </button>
                </div>
            </form>
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

function openEditModal(stationId, stationName, zone, division, feedbackTarget, username) {
    document.getElementById('editStationId').value = stationId;
    document.getElementById('editStationName').value = stationName;
    document.getElementById('editZone').value = zone;
    document.getElementById('editDivision').value = division;
    document.getElementById('editFeedbackTarget').value = feedbackTarget;
    document.getElementById('editUsername').value = username;
    
    document.getElementById('editModal').classList.remove('hidden');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});

function deleteStation(stationId, stationName) {
    if (confirm(`Are you sure you want to delete the station "${stationName}"? This will also delete the associated user account. This action cannot be undone.`)) {
        // Create a form to submit delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete_feedback_station.php';
        form.innerHTML = `
            <input type="hidden" name="station_id" value="${stationId}">
            <input type="hidden" name="action" value="delete">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</body>
</html>
