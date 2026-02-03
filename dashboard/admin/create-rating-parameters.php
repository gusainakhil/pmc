<?php

session_start();

include "../../connection.php";
// Check if the connection was successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$successMsg = '';
$errorMsg = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stationId = $_POST['station_id'];
    $ratings = $_POST['ratings'] ?? [];
    $values = $_POST['values'] ?? [];
    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');
    
    // Validate input
    if (empty($stationId)) {
        $errorMsg = "Please select a station";
    } elseif (empty($ratings) || empty($values) || count($ratings) == 0) {
        $errorMsg = "Please add at least one rating parameter";
    } else {
        try {
            // Start transaction for multiple inserts
            $conn->begin_transaction();
            
            // Prepare statement for inserting ratings
            $stmt = $conn->prepare("INSERT INTO rating_parameters (rating_name, value, station_id, created_at, updated_at) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("siiss", $rating_name, $rating_value, $stationId, $created_at, $updated_at);
            
            $successCount = 0;
            // Process each rating parameter
            for ($i = 0; $i < count($ratings); $i++) {
                // Skip empty ratings
                if (empty(trim($ratings[$i]))) {
                    continue;
                }
                
                $rating_name = trim($ratings[$i]);
                $rating_value = intval($values[$i]);
                
                if ($stmt->execute()) {
                    $successCount++;
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            if ($successCount > 0) {
                $parameterCount = isset($stationParameterCounts[$stationId]) ? $stationParameterCounts[$stationId] : 0;
                $totalParameters = $parameterCount + $successCount;
                $successMsg = "$successCount new rating parameters added successfully. Station now has $totalParameters total parameters.";
                // Reset the form
                unset($_POST);
            } else {
                $errorMsg = "No valid rating parameters were submitted";
            }
            
            $stmt->close();
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $errorMsg = "Error: " . $e->getMessage();
        }
    }
}

// Get all stations for dropdown
$stations = [];
$stationQuery = $conn->query("SELECT stationId, stationName FROM baris_station ORDER BY stationName ASC");
if ($stationQuery) {
    $stations = $stationQuery->fetch_all(MYSQLI_ASSOC);
}

// Get station rating parameter counts for display purposes
$stationParameterCounts = [];
$countQuery = $conn->query("SELECT station_id, COUNT(*) as parameter_count FROM rating_parameters GROUP BY station_id");
if ($countQuery) {
    $result = $countQuery->fetch_all(MYSQLI_ASSOC);
    foreach ($result as $row) {
        $stationParameterCounts[$row['station_id']] = $row['parameter_count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Rating Parameters | Station Cleaning</title>
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
        
        .parameter-row {
            position: relative;
            display: flex;
            margin-bottom: 1rem;
            gap: 1rem;
        }
        
        .remove-parameter {
            position: absolute;
            right: -30px;
            top: 50%;
            transform: translateY(-50%);
            color: #ef4444;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        
        .remove-parameter:hover {
            opacity: 1;
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
                    <h1 class="text-xl font-bold text-gray-800">Create Rating Parameters</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                   <a href="view_rating_parameters.php" class="flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                        <i class="fas fa-list mr-2"></i>
                        View All Parameters
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
                                <a href="#" class="text-gray-500 hover:text-blue-600">Ratings</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                                <span class="text-gray-700">Create Parameters</span>
                            </div>
                        </li>
                    </ol>
                </nav>
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
            
            <!-- Main Card -->
            <div class="card mb-6">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Rating Parameters</h2>
                            <p class="text-gray-500 text-sm mt-1">Create rating options for station feedback</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-star text-blue-600"></i>
                        </div>
                    </div>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="ratingForm" class="p-6">
                    <div class="grid grid-cols-1 gap-6 mb-8">
                        <div>
                            <label for="station_id" class="block text-sm font-medium text-gray-700 mb-2">Select Station*</label>
                            <select id="station_id" name="station_id" class="form-select" required>
                                <option value="">-- Choose Station --</option>
                                <?php foreach ($stations as $station): ?>
                                    <?php 
                                    $parameterCount = isset($stationParameterCounts[$station['stationId']]) ? $stationParameterCounts[$station['stationId']] : 0;
                                    $selected = isset($_POST['station_id']) && $_POST['station_id'] == $station['stationId'] ? 'selected' : '';
                                    ?>
                                    <option 
                                        value="<?= htmlspecialchars($station['stationId']) ?>" 
                                        <?= $selected ?>
                                    >
                                        <?= htmlspecialchars($station['stationName']) ?> 
                                        <?= $parameterCount > 0 ? "($parameterCount parameters)" : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Select the station to add rating parameters. Stations with existing parameters will show the current count.</p>
                        </div>
                        
                        <!-- Existing Parameters Display -->
                        <div id="existingParametersSection" class="hidden bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-medium text-gray-700">
                                    <i class="fas fa-list mr-2 text-blue-500"></i>
                                    Existing Parameters for This Station
                                </h4>
                                <button type="button" id="editExistingBtn" class="text-xs text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit mr-1"></i>
                                    Edit Existing
                                </button>
                            </div>
                            <div id="existingParametersList" class="space-y-2">
                                <!-- Existing parameters will be loaded here -->
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-md font-medium text-gray-700 flex items-center">
                                    <i class="fas fa-star-half-alt mr-2 text-blue-500"></i>
                                    Add New Rating Options
                                </h3>
                                <button type="button" id="addParameter" class="text-sm text-blue-600 flex items-center">
                                    <i class="fas fa-plus-circle mr-1"></i>
                                    Add Rating
                                </button>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-4">
                                <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                                Add new rating parameters below. If the station already has parameters, they will be preserved.
                            </p>
                            
                            <div id="parametersContainer">
                                <!-- Default 4 parameters (similar to your example) -->
                                <div class="parameter-row">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Rating Name*</label>
                                        <input 
                                            type="text" 
                                            name="ratings[]" 
                                            class="form-input" 
                                            placeholder="e.g. Very Good"
                                            value="Very Good"
                                            required
                                        />
                                    </div>
                                    <div class="w-24">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Value*</label>
                                        <input 
                                            type="number" 
                                            name="values[]" 
                                            class="form-input text-center" 
                                            placeholder="3"
                                            value="3"
                                            min="0"
                                            required
                                        />
                                    </div>
                                    <span class="remove-parameter">
                                        <i class="fas fa-times-circle"></i>
                                    </span>
                                </div>
                                
                                <div class="parameter-row">
                                    <div class="flex-1">
                                        <input 
                                            type="text" 
                                            name="ratings[]" 
                                            class="form-input" 
                                            placeholder="e.g. Satisfactory"
                                            value="Satisfactory"
                                            required
                                        />
                                    </div>
                                    <div class="w-24">
                                        <input 
                                            type="number" 
                                            name="values[]" 
                                            class="form-input text-center" 
                                            placeholder="2"
                                            value="2"
                                            min="0"
                                            required
                                        />
                                    </div>
                                    <span class="remove-parameter">
                                        <i class="fas fa-times-circle"></i>
                                    </span>
                                </div>
                                
                                <div class="parameter-row">
                                    <div class="flex-1">
                                        <input 
                                            type="text" 
                                            name="ratings[]" 
                                            class="form-input" 
                                            placeholder="e.g. Poor"
                                            value="Poor"
                                            required
                                        />
                                    </div>
                                    <div class="w-24">
                                        <input 
                                            type="number" 
                                            name="values[]" 
                                            class="form-input text-center" 
                                            placeholder="1"
                                            value="1"
                                            min="0"
                                            required
                                        />
                                    </div>
                                    <span class="remove-parameter">
                                        <i class="fas fa-times-circle"></i>
                                    </span>
                                </div>
                                
                                <div class="parameter-row">
                                    <div class="flex-1">
                                        <input 
                                            type="text" 
                                            name="ratings[]" 
                                            class="form-input" 
                                            placeholder="e.g. Not Attended"
                                            value="Not Attended"
                                            required
                                        />
                                    </div>
                                    <div class="w-24">
                                        <input 
                                            type="number" 
                                            name="values[]" 
                                            class="form-input text-center" 
                                            placeholder="0"
                                            value="0"
                                            min="0"
                                            required
                                        />
                                    </div>
                                    <span class="remove-parameter">
                                        <i class="fas fa-times-circle"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mt-2 mb-4">
                                <p class="text-sm text-gray-500">
                                    <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                                    These ratings will be available for users to select when giving feedback about the station.
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                        <button type="button" id="resetForm" class="btn btn-secondary">
                            <i class="fas fa-undo mr-2"></i>
                            Reset
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i>
                            Save Parameters
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="card p-5">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-lightbulb text-blue-600"></i>
                        </div>
                        <h3 class="font-semibold">Tips for Creating Rating Parameters</h3>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Use clear, descriptive rating names (e.g., "Very Good", "Poor")</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Assign values in descending order (higher is better)</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>You can add more parameters to stations that already have some</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Include a "Not Attended" option with value 0 for tracking purposes</span>
                        </li>
                    </ul>
                </div>
                
                <div class="card p-5">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center mr-3">
                            <i class="fas fa-question-circle text-purple-600"></i>
                        </div>
                        <h3 class="font-semibold">Need Help?</h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-4">
                        If you need assistance with creating rating parameters or understanding how they work, 
                        our support team is here to help.
                    </p>
                    <a href="#" class="text-sm text-purple-600 font-medium hover:text-purple-800 flex items-center">
                        <i class="fas fa-headset mr-2"></i>
                        Contact Support
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
    // Mobile menu toggle - using a different variable name to avoid conflicts
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const sidebarElement = document.querySelector('aside');
    
    if (mobileMenuButton && sidebarElement) {
        mobileMenuButton.addEventListener('click', () => {
            sidebarElement.classList.toggle('-translate-x-full');
        });
    }
    
    // Add new parameter field
    document.addEventListener('DOMContentLoaded', function() {
        const addParameterBtn = document.getElementById('addParameter');
        const parametersContainer = document.getElementById('parametersContainer');
        const resetFormBtn = document.getElementById('resetForm');
        const ratingForm = document.getElementById('ratingForm');
        const stationSelect = document.getElementById('station_id');
        const existingParametersSection = document.getElementById('existingParametersSection');
        const existingParametersList = document.getElementById('existingParametersList');
        const editExistingBtn = document.getElementById('editExistingBtn');

        // Load existing parameters when station is selected
        if (stationSelect && existingParametersSection) {
            stationSelect.addEventListener('change', function() {
                const stationId = this.value;
                
                if (stationId) {
                    // Show the existing parameters section
                    existingParametersSection.classList.remove('hidden');
                    
                    // Load existing parameters via AJAX
                    loadExistingParameters(stationId);
                } else {
                    existingParametersSection.classList.add('hidden');
                }
            });
        }

        // Function to load existing parameters
        function loadExistingParameters(stationId) {
            // Create a simple AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_rating_parameters.php?station_id=' + stationId, true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            displayExistingParameters(data);
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                            existingParametersList.innerHTML = '<p class="text-sm text-red-600">Error loading parameters</p>';
                        }
                    } else {
                        existingParametersList.innerHTML = '<p class="text-sm text-red-600">Error loading parameters</p>';
                    }
                }
            };
            
            xhr.send();
        }

        // Function to display existing parameters
        function displayExistingParameters(parameters) {
            if (parameters.length === 0) {
                existingParametersList.innerHTML = '<p class="text-sm text-gray-500">No existing parameters found for this station.</p>';
                return;
            }

            let html = '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">';
            
            parameters.forEach(function(param) {
                html += `
                    <div class="flex items-center justify-between bg-white p-3 rounded border">
                        <div class="flex items-center">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-3">
                                ${param.value}
                            </span>
                            <span class="text-sm font-medium text-gray-700">${param.rating_name}</span>
                        </div>
                        <button type="button" class="edit-param-btn text-xs text-blue-600 hover:text-blue-800" data-id="${param.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                `;
            });
            
            html += '</div>';
            existingParametersList.innerHTML = html;
        }

        // Edit existing parameter functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-param-btn') || e.target.parentElement.classList.contains('edit-param-btn')) {
                const button = e.target.classList.contains('edit-param-btn') ? e.target : e.target.parentElement;
                const paramId = button.getAttribute('data-id');
                editParameter(paramId);
            }
        });

        // Edit existing parameters button
        if (editExistingBtn) {
            editExistingBtn.addEventListener('click', function() {
                const stationId = stationSelect.value;
                if (stationId) {
                    // Open a modal or redirect to edit page
                    window.open('edit_rating_parameters.php?station_id=' + stationId, '_blank');
                }
            });
        }

        function editParameter(paramId) {
            // Simple inline editing - you can make this more sophisticated
            const paramElement = document.querySelector(`[data-id="${paramId}"]`).closest('.flex');
            const nameElement = paramElement.querySelector('.text-gray-700');
            const valueElement = paramElement.querySelector('.bg-blue-100');
            
            const currentName = nameElement.textContent;
            const currentValue = valueElement.textContent;
            
            // Replace with input fields
            nameElement.innerHTML = `<input type="text" value="${currentName}" class="form-input text-sm w-full" id="edit-name-${paramId}">`;
            valueElement.innerHTML = `<input type="number" value="${currentValue}" class="form-input text-sm w-16 text-center" id="edit-value-${paramId}">`;
            
            // Add save/cancel buttons
            const button = paramElement.querySelector('.edit-param-btn');
            button.innerHTML = '<i class="fas fa-save text-green-600 mr-2"></i><i class="fas fa-times text-red-600"></i>';
            button.setAttribute('data-action', 'save');
        }

        // Add parameter button functionality
        if (addParameterBtn && parametersContainer) {
            addParameterBtn.addEventListener('click', function(e) {
                e.preventDefault(); // Prevent any default behavior
                
                // Create a new parameter row
                const parameterRow = document.createElement('div');
                parameterRow.className = 'parameter-row';
                
                parameterRow.innerHTML = `
                    <div class="flex-1">
                        <input 
                            type="text" 
                            name="ratings[]" 
                            class="form-input" 
                            placeholder="Enter rating name"
                            required
                        />
                    </div>
                    <div class="w-24">
                        <input 
                            type="number" 
                            name="values[]" 
                            class="form-input text-center" 
                            placeholder="Value"
                            min="0"
                            required
                        />
                    </div>
                    <span class="remove-parameter">
                        <i class="fas fa-times-circle"></i>
                    </span>
                `;
                
                // Append to the container
                parametersContainer.appendChild(parameterRow);
                
                // Focus the new input
                const newInput = parameterRow.querySelector('input');
                if (newInput) {
                    newInput.focus();
                }
            });
        }
        
        // Remove parameter using event delegation
        document.body.addEventListener('click', function(e) {
            const target = e.target;
            
            // Check if the click was on the X icon or its parent
            if (target.classList.contains('fa-times-circle') || 
                target.classList.contains('remove-parameter')) {
                
                // Find the parent parameter-row div
                const parameterRow = target.closest('.parameter-row');
                if (parameterRow) {
                    // Don't remove if it's the last parameter
                    const allParameters = document.querySelectorAll('.parameter-row');
                    if (allParameters.length > 1) {
                        parameterRow.remove();
                    } else {
                        // If it's the last one, just clear the inputs
                        const inputs = parameterRow.querySelectorAll('input');
                        inputs.forEach(input => {
                            input.value = '';
                        });
                    }
                }
            }
        });
        
        // Reset form
        if (resetFormBtn && ratingForm) {
            resetFormBtn.addEventListener('click', function() {
                // Reset the form fields
                ratingForm.reset();
                
                // Remove all additional parameter rows, keeping only the original 4
                const parameterRows = document.querySelectorAll('.parameter-row');
                
                // Keep only the first 4 rows (default setup)
                for (let i = 4; i < parameterRows.length; i++) {
                    parameterRows[i].remove();
                }
                
                // Reset the values of the first 4 rows to the defaults
                const defaultValues = [
                    ["Very Good", "3"],
                    ["Satisfactory", "2"],
                    ["Poor", "1"],
                    ["Not Attended", "0"]
                ];
                
                for (let i = 0; i < 4 && i < parameterRows.length; i++) {
                    const inputs = parameterRows[i].querySelectorAll('input');
                    if (inputs.length >= 2) {
                        inputs[0].value = defaultValues[i][0];
                        inputs[1].value = defaultValues[i][1];
                    }
                }
            });
        }
        
        // Form validation
        if (ratingForm) {
            ratingForm.addEventListener('submit', function(event) {
                let isValid = true;
                const requiredFields = ratingForm.querySelectorAll('[required]');
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('border-red-500');
                        isValid = false;
                    } else {
                        field.classList.remove('border-red-500');
                    }
                });
                
                if (!isValid) {
                    event.preventDefault();
                    
                    // Scroll to first error
                    const firstError = ratingForm.querySelector('.border-red-500');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                }
            });
        }
        
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