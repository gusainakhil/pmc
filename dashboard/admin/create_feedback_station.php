<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

include "../../connection.php";

// Initialize variables
$successMsg = '';
$errorMsg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $selectedStationId = intval($_POST['selected_station'] ?? 0);
    $feedbackTarget = intval($_POST['feedback_target'] ?? 0);
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirmPassword = trim($_POST['confirm_password'] ?? '');
    
    // Get station details from baris_station table
    $stationData = null;
    if ($selectedStationId > 0) {
        $stmt = $conn->prepare("SELECT stationId, stationName, zoneName, DivisionId FROM baris_station WHERE stationId = ?");
        $stmt->bind_param("i", $selectedStationId);
        $stmt->execute();
        $result = $stmt->get_result();
        $stationData = $result->fetch_assoc();
        $stmt->close();
    }
    
    // Validate input
    $errors = [];
    
    if ($selectedStationId <= 0 || !$stationData) {
        $errors[] = "Please select a valid station";
    }
    
    if ($feedbackTarget <= 0) {
        $errors[] = "Feedback target must be greater than 0";
    }
    
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (strlen($username) < 3) {
        $errors[] = "Username must be at least 3 characters";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }
    
    // Check if username already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM feedback_users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $errors[] = "Username already exists";
        }
        $stmt->close();
    }
    

    
    if (!empty($errors)) {
        $errorMsg = implode(', ', $errors);
    } else {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Create feedback station using data from baris_station
            $stmt = $conn->prepare("INSERT INTO feedback_stations (id, name, zone, division, feedback_target) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isssi", $selectedStationId, $stationData['stationName'], $stationData['zoneName'], $stationData['DivisionId'], $feedbackTarget);
            
            if (!$stmt->execute()) {
                throw new Exception("Error creating station: " . $stmt->error);
            }
            
            $feedbackStationId = $conn->insert_id;
            $stmt->close();
            
            // Create feedback user
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO feedback_users (username, password_hash, station_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $username, $passwordHash, $feedbackStationId);
            
            if (!$stmt->execute()) {
                throw new Exception("Error creating user: " . $stmt->error);
            }
            
            $stmt->close();
            
            // Commit transaction
            $conn->commit();
            
            $successMsg = "Feedback station and user created successfully! Station: " . $stationData['stationName'] . " (ID: $feedbackStationId)";
            
            // Clear form data
            $_POST = [];
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errorMsg = "Error: " . $e->getMessage();
        }
    }
}

// Get all stations from baris_station for dropdown
$allStations = [];
$stationQuery = $conn->query("
    SELECT bs.stationId, bs.stationName, bs.zoneName, bs.DivisionId
    FROM baris_station bs
    ORDER BY bs.stationName ASC
");
if ($stationQuery) {
    $allStations = $stationQuery->fetch_all(MYSQLI_ASSOC);
}

// Get existing feedback stations with their original station data
$feedbackStations = [];
$feedbackQuery = $conn->query("
    SELECT fs.*, fu.username, bs.stationName as original_station_name 
    FROM feedback_stations fs 
    LEFT JOIN feedback_users fu ON fs.id = fu.station_id 
    LEFT JOIN baris_station bs ON fs.id = bs.stationId
    ORDER BY fs.name ASC
");
if ($feedbackQuery) {
    $feedbackStations = $feedbackQuery->fetch_all(MYSQLI_ASSOC);
}

// Get statistics
$stats = [
    'total_stations' => 0,
    'total_users' => 0,
    'avg_target' => 0
];

$statsQuery = $conn->query("SELECT COUNT(*) as total_stations, AVG(feedback_target) as avg_target FROM feedback_stations");
if ($statsQuery && $statsRow = $statsQuery->fetch_assoc()) {
    $stats['total_stations'] = $statsRow['total_stations'];
    $stats['avg_target'] = $statsRow['avg_target'];
}

$userStatsQuery = $conn->query("SELECT COUNT(*) as total_users FROM feedback_users");
if ($userStatsQuery && $userStatsRow = $userStatsQuery->fetch_assoc()) {
    $stats['total_users'] = $userStatsRow['total_users'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Feedback Station & User | Station Management</title>
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
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            margin-top: 8px;
            background-color: #e5e7eb;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .strength-weak { background-color: #ef4444; }
        .strength-fair { background-color: #f59e0b; }
        .strength-good { background-color: #10b981; }
        .strength-strong { background-color: #059669; }
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
                    <h1 class="text-xl font-bold text-gray-800">Create Feedback Station & User</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="view_feedback_stations.php" class="flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                        <i class="fas fa-list mr-2"></i>
                        View All Stations
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
                                <span class="text-gray-700">Create Station & User</span>
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
                <p class="text-2xl font-bold"><?= $stats['total_stations'] ?></p>
            </div>
            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                <i class="fas fa-map-marker-alt text-xl"></i>
            </div>
        </div>
    </div>

    <div class="stat-card stat-card-green">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-blue-100 text-sm font-medium">Total Users</p>
                <p class="text-2xl font-bold"><?= $stats['total_users'] ?></p>
            </div>
            <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-xl"></i>
            </div>
        </div>
    </div>
</div>

            
            <!-- Success/Error Messages -->
            <?php if (!empty($successMsg)): ?>
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <p class="text-green-700"><?php echo htmlspecialchars($successMsg); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($errorMsg)): ?>
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                    <p class="text-red-700"><?php echo htmlspecialchars($errorMsg); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Main Form Card -->
            <div class="card mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Create New Feedback Station</h2>
                            <p class="text-gray-500 text-sm mt-1">Set up a new feedback station with associated user account</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-plus text-blue-600"></i>
                        </div>
                    </div>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="feedbackStationForm" class="p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Station Information -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-md font-semibold text-gray-700 mb-4 flex items-center">
                                    <i class="fas fa-map-marker-alt mr-2 text-blue-500"></i>
                                    Station Information
                                </h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="selected_station" class="block text-sm font-medium text-gray-700 mb-2">Select Station*</label>
                                        <select 
                                            id="selected_station" 
                                            name="selected_station" 
                                            class="form-select" 
                                            required
                                        >
                                            <option value="">-- Choose Station --</option>
                                            <?php foreach ($allStations as $station): ?>
                                                <option 
                                                    value="<?= $station['stationId'] ?>" 
                                                    data-zone="<?= htmlspecialchars($station['zoneName']) ?>"
                                                    data-division="<?= htmlspecialchars($station['DivisionId']) ?>"
                                                    <?= (isset($_POST['selected_station']) && $_POST['selected_station'] == $station['stationId']) ? 'selected' : '' ?>
                                                >
                                                    <?= htmlspecialchars($station['stationName']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="mt-1 text-xs text-gray-500">All stations are shown.</p>
                                    </div>
                                    
                                    <div>
                                        <label for="station_zone" class="block text-sm font-medium text-gray-700 mb-2">Zone</label>
                                        <input 
                                            type="text" 
                                            id="station_zone" 
                                            name="station_zone" 
                                            class="form-input bg-gray-100" 
                                            placeholder="Zone will be auto-filled"
                                            readonly
                                        />
                                    </div>
                                    
                                    <div>
                                        <label for="station_division" class="block text-sm font-medium text-gray-700 mb-2">Division</label>
                                        <input 
                                            type="text" 
                                            id="station_division" 
                                            name="station_division" 
                                            class="form-input bg-gray-100" 
                                            placeholder="Division will be auto-filled"
                                            readonly
                                        />
                                    </div>
                                    
                                    <div>
                                        <label for="feedback_target" class="block text-sm font-medium text-gray-700 mb-2">Feedback Target*</label>
                                        <input 
                                            type="number" 
                                            id="feedback_target" 
                                            name="feedback_target" 
                                            class="form-input" 
                                            placeholder="Enter daily feedback target"
                                            value="<?= htmlspecialchars($_POST['feedback_target'] ?? '') ?>"
                                            min="1"
                                            required
                                        />
                                        <p class="mt-1 text-xs text-gray-500">Number of feedbacks expected per day</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- User Account Information -->
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-md font-semibold text-gray-700 mb-4 flex items-center">
                                    <i class="fas fa-user mr-2 text-green-500"></i>
                                    User Account
                                </h3>
                                
                                <div class="space-y-4">
                                    <div>
                                        <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username*</label>
                                        <input 
                                            type="text" 
                                            id="username" 
                                            name="username" 
                                            class="form-input" 
                                            placeholder="Enter username"
                                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                                            minlength="3"
                                            required
                                        />
                                        <p class="mt-1 text-xs text-gray-500">Minimum 3 characters</p>
                                    </div>
                                    
                                    <div>
                                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password*</label>
                                        <div class="relative">
                                            <input 
                                                type="password" 
                                                id="password" 
                                                name="password" 
                                                class="form-input pr-10" 
                                                placeholder="Enter password"
                                                minlength="6"
                                                required
                                            />
                                            <button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                <i class="fas fa-eye text-gray-400"></i>
                                            </button>
                                        </div>
                                        <div class="password-strength">
                                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                                        </div>
                                        <p class="mt-1 text-xs text-gray-500">Minimum 6 characters</p>
                                    </div>
                                    
                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password*</label>
                                        <div class="relative">
                                            <input 
                                                type="password" 
                                                id="confirm_password" 
                                                name="confirm_password" 
                                                class="form-input pr-10" 
                                                placeholder="Confirm password"
                                                minlength="6"
                                                required
                                            />
                                            <button type="button" id="toggleConfirmPassword" class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                                <i class="fas fa-eye text-gray-400"></i>
                                            </button>
                                        </div>
                                        <p class="mt-1 text-xs" id="passwordMatch"></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-4 pt-8 border-t border-gray-200 mt-8">
                        <button type="button" id="resetForm" class="btn btn-secondary">
                            <i class="fas fa-undo mr-2"></i>
                            Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            Create Station & User
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Existing Stations Preview -->
            <?php if (!empty($feedbackStations)): ?>
            <div class="card">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-list mr-2 text-blue-600"></i>
                        Recent Feedback Stations
                    </h3>
                </div>
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <?php foreach (array_slice($feedbackStations, 0, 6) as $station): ?>
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-medium text-gray-800"><?= htmlspecialchars($station['name']) ?></h4>
                                    <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                        <?= htmlspecialchars($station['zone']) ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-1">
                                    <i class="fas fa-building mr-1"></i>
                                    <?= htmlspecialchars($station['division']) ?>
                                </p>
                                <p class="text-sm text-gray-600 mb-1">
                                    <i class="fas fa-bullseye mr-1"></i>
                                    Target: <?= $station['feedback_target'] ?>/day
                                </p>
                                <p class="text-sm text-gray-600">
                                    <i class="fas fa-user mr-1"></i>
                                    User: <?= htmlspecialchars($station['username'] ?? 'Not assigned') ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (count($feedbackStations) > 6): ?>
                    <div class="mt-4 text-center">
                        <a href="view_feedback_stations.php" class="text-blue-600 hover:text-blue-800 text-sm">
                            View all <?= count($feedbackStations) ?> stations →
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
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

<script>
// Mobile menu toggle
const mobileMenuButton = document.getElementById('mobile-menu-button');
const sidebarElement = document.querySelector('aside');

if (mobileMenuButton && sidebarElement) {
    mobileMenuButton.addEventListener('click', () => {
        sidebarElement.classList.toggle('-translate-x-full');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('feedbackStationForm');
    const passwordInput = document.getElementById('password');
    const confirmPasswordInput = document.getElementById('confirm_password');
    const passwordStrengthBar = document.getElementById('passwordStrengthBar');
    const passwordMatchText = document.getElementById('passwordMatch');
    const resetBtn = document.getElementById('resetForm');
    const stationSelect = document.getElementById('selected_station');
    const zoneInput = document.getElementById('station_zone');
    const divisionInput = document.getElementById('station_division');
    
    // Handle station selection
    stationSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        
        if (selectedOption.value) {
            // Auto-fill zone and division from data attributes
            zoneInput.value = selectedOption.getAttribute('data-zone') || '';
            divisionInput.value = selectedOption.getAttribute('data-division') || '';
        } else {
            // Clear fields if no station selected
            zoneInput.value = '';
            divisionInput.value = '';
        }
    });
    
    // Password visibility toggles
    const togglePassword = document.getElementById('togglePassword');
    const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
    
    togglePassword.addEventListener('click', function() {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
    
    toggleConfirmPassword.addEventListener('click', function() {
        const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        confirmPasswordInput.setAttribute('type', type);
        this.querySelector('i').classList.toggle('fa-eye');
        this.querySelector('i').classList.toggle('fa-eye-slash');
    });
    
    // Password strength checker
    passwordInput.addEventListener('input', function() {
        const password = this.value;
        const strength = getPasswordStrength(password);
        
        passwordStrengthBar.style.width = strength.percentage + '%';
        passwordStrengthBar.className = 'password-strength-bar ' + strength.class;
        
        checkPasswordMatch();
    });
    
    // Password match checker
    confirmPasswordInput.addEventListener('input', checkPasswordMatch);
    
    function checkPasswordMatch() {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (confirmPassword.length > 0) {
            if (password === confirmPassword) {
                passwordMatchText.textContent = 'Passwords match';
                passwordMatchText.className = 'mt-1 text-xs text-green-600';
            } else {
                passwordMatchText.textContent = 'Passwords do not match';
                passwordMatchText.className = 'mt-1 text-xs text-red-600';
            }
        } else {
            passwordMatchText.textContent = '';
        }
    }
    
    function getPasswordStrength(password) {
        let score = 0;
        
        if (password.length >= 6) score += 25;
        if (password.length >= 8) score += 25;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score += 25;
        if (/\d/.test(password)) score += 25;
        if (/[^A-Za-z0-9]/.test(password)) score += 25;
        
        if (score <= 25) return { percentage: 25, class: 'strength-weak' };
        if (score <= 50) return { percentage: 50, class: 'strength-fair' };
        if (score <= 75) return { percentage: 75, class: 'strength-good' };
        return { percentage: 100, class: 'strength-strong' };
    }
    
    // Form reset
    resetBtn.addEventListener('click', function() {
        form.reset();
        passwordStrengthBar.style.width = '0%';
        passwordMatchText.textContent = '';
        zoneInput.value = '';
        divisionInput.value = '';
    });
    
    // Form validation
    form.addEventListener('submit', function(e) {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (password !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match');
            confirmPasswordInput.focus();
            return false;
        }
        
        // Additional validation
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;
        
        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                field.classList.add('border-red-500');
                isValid = false;
            } else {
                field.classList.remove('border-red-500');
            }
        });
        
        if (!isValid) {
            e.preventDefault();
            const firstError = form.querySelector('.border-red-500');
            if (firstError) {
                firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstError.focus();
            }
        }
    });
    
    // Clear validation styling on input
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('form-input') || e.target.classList.contains('form-select')) {
            e.target.classList.remove('border-red-500');
        }
    });
});
</script>

</body>
</html>
