<?php

include "connection.php";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stationId = isset($_GET['stationId']) ? intval($_GET['stationId']) : 0;
$station = null;
$selectedReports = [];
$db_questionsId = null;

// Fetch existing station data
if ($stationId > 0) {
    $stmt = $conn->prepare("SELECT * FROM baris_station WHERE stationId = ?");
    $stmt->bind_param("i", $stationId);
    $stmt->execute();
    $result = $stmt->get_result();
    $station = $result->fetch_assoc();
    $stmt->close();

    if ($station) {
        $db_questionsId = $station['db_questionsId'];
        // Fetch existing subqueIds from baris_question
        if ($db_questionsId) {
            $q = $conn->prepare("SELECT subqueId FROM baris_question WHERE queId = ?");
            $q->bind_param("i", $db_questionsId);
            $q->execute();
            $q->bind_result($subqueIdsCsv);
            if ($q->fetch()) {
                $selectedReports = array_map('intval', explode(',', $subqueIdsCsv));
            }
            $q->close();
        }
    }
}

// Handle update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $stationId > 0) {
    $stationName = $_POST['stationName'];
    $station_login_id = $_POST['db_stLoginId'];
    $zoneName = $_POST['zoneName'];
    $divisionId = $_POST['DivisionId'];
    $reportType = isset($_POST['reportType']) ? $_POST['reportType'] : [];

    // Update baris_question if exists, else insert new
    $subqueIdsCsv = implode(',', array_map('intval', $reportType));
    if ($db_questionsId) {
        $stmtQ = $conn->prepare("UPDATE baris_question SET subqueId = ? WHERE queId = ?");
        $stmtQ->bind_param("si", $subqueIdsCsv, $db_questionsId);
        $stmtQ->execute();
        $stmtQ->close();
    } else {
        $stmtQ = $conn->prepare("INSERT INTO baris_question (queName, subqueId) VALUES (?, ?)");
        $queName = "PMC";
        $stmtQ->bind_param("ss", $queName, $subqueIdsCsv);
        if ($stmtQ->execute()) {
            $db_questionsId = $conn->insert_id;
        }
        $stmtQ->close();
    }

    try {
        $stmt = $conn->prepare("UPDATE baris_station SET stationName=?, db_stLoginId=?, zoneName=?, divisionId=?, db_questionsId=? WHERE stationId=?");
        if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

        if (!$stmt->bind_param("ssssii", $stationName, $station_login_id, $zoneName, $divisionId, $db_questionsId, $stationId)) {
            throw new Exception("Bind param failed: " . $stmt->error);
        }

        if ($stmt->execute()) {
            $successMsg = "Station updated successfully";
            // Refresh data
            header("Location: edit-station.php?stationId=$stationId&updated=1");
            exit;
        } else {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
    }
}

// Show success message after redirect
if (isset($_GET['updated'])) {
    $successMsg = "Station updated successfully";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Station | Station Cleaning</title>
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
        
        .checkbox-item {
            display: inline-flex;
            align-items: center;
            margin-right: 1rem;
            margin-bottom: 0.5rem;
        }
        
        .checkbox-input {
            width: 1rem;
            height: 1rem;
            border-radius: 0.25rem;
            border: 1px solid #d1d5db;
            appearance: none;
            background-color: #fff;
            margin-right: 0.5rem;
            transition: all 0.2s;
        }
        
        .checkbox-input:checked {
            background-color: #3b82f6;
            border-color: #3b82f6;
            background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e");
            background-position: center;
            background-repeat: no-repeat;
            background-size: 0.75em 0.75em;
        }
        
        .checkbox-input:focus {
            outline: none;
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
                    <h1 class="text-xl font-bold text-gray-800">Edit Station</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="list-station.php" class="flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Stations
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
                                <a href="list-station.php" class="text-gray-500 hover:text-blue-600">Stations</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                                <span class="text-gray-700">Edit Station</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <!-- Main Card -->
            <div class="card p-6 mb-6">
                <?php if ($station): ?>
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Edit Station: <?php echo htmlspecialchars($station['stationName']); ?></h2>
                            <p class="text-gray-500 text-sm mt-1">Update station information and report types</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-train text-blue-600"></i>
                        </div>
                    </div>
                    
                    <!-- Success/Error Messages -->
                    <?php if (isset($successMsg)): ?>
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <p class="text-green-700"><?php echo htmlspecialchars($successMsg); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($errorMsg)): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <p class="text-red-700">Error: <?php echo htmlspecialchars($errorMsg); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="stationId" value="<?php echo htmlspecialchars($stationId); ?>" />
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="stationName" class="block text-sm font-medium text-gray-700 mb-2">Station Name</label>
                                <input 
                                    type="text" 
                                    name="stationName" 
                                    id="stationName" 
                                    class="form-input" 
                                    value="<?php echo htmlspecialchars($station['stationName']); ?>"
                                    required
                                >
                            </div>
                            
                            <div>
                                <label for="db_stLoginId" class="block text-sm font-medium text-gray-700 mb-2">Station Login ID</label>
                                <input 
                                    type="text" 
                                    name="db_stLoginId" 
                                    id="db_stLoginId" 
                                    class="form-input" 
                                    value="<?php echo htmlspecialchars($station['db_stLoginId']); ?>"
                                    required
                                >
                                <p class="mt-1 text-xs text-gray-500">Used for station login authentication</p>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="zoneName" class="block text-sm font-medium text-gray-700 mb-2">Zone Name</label>
                                <select name="zoneName" id="zoneName" required class="form-select">
                                    <option value="">Select Zone</option>
                                    <?php
                                    $zones = ["western Zone", "eastern Zone", "northern Zone", "southern Zone"];
                                    foreach ($zones as $zone) {
                                        $selected = ($station['zoneName'] == $zone) ? 'selected' : '';
                                        echo "<option value=\"" . htmlspecialchars($zone) . "\" $selected>" . htmlspecialchars($zone) . "</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="DivisionId" class="block text-sm font-medium text-gray-700 mb-2">Division</label>
                                <select name="DivisionId" id="DivisionId" required class="form-select">
                                    <option value="">Select Division</option>
                                    <?php
                                    $divResult = $conn->query("SELECT DivisionId, divisionName FROM baris_division");
                                    if ($divResult && $divResult->num_rows > 0) {
                                        while ($div = $divResult->fetch_assoc()) {
                                            $selected = ($station['divisionId'] == $div['DivisionId']) ? 'selected' : '';
                                            echo '<option value="' . htmlspecialchars($div['DivisionId']) . "\" $selected>" . htmlspecialchars($div['divisionName']) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Report Types</label>
                            <div class="mt-1 bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                                    <?php
                                    $reportType = $conn->query("SELECT subqueName, MIN(subqueId) AS subqueId FROM baris_subquestion GROUP BY subqueName;");
                                    if ($reportType && $reportType->num_rows > 0) {
                                        while ($type = $reportType->fetch_assoc()) {
                                            $checked = in_array($type['subqueId'], $selectedReports) ? 'checked' : '';
                                            echo '<label class="checkbox-item">
                                                    <input type="checkbox" name="reportType[]" value="' . htmlspecialchars($type['subqueId']) . '" class="checkbox-input" ' . $checked . ' />
                                                    <span class="text-sm">' . htmlspecialchars($type['subqueName']) . '</span>
                                                  </label>';
                                        }
                                    }
                                    ?>
                                </div>
                                <p class="mt-3 text-xs text-gray-500">Select all the report types that apply to this station</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center justify-end space-x-4 pt-4 border-t border-gray-200">
                            <a href="list-station.php" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-2"></i>
                                Update Station
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="py-8 text-center">
                        <div class="w-16 h-16 bg-red-100 rounded-full mx-auto flex items-center justify-center mb-4">
                            <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Station Not Found</h3>
                        <p class="text-gray-500 mb-6">The station you are trying to edit does not exist or has been removed.</p>
                        <a href="list-station.php" class="btn btn-primary">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Return to Station List
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Info Cards -->
            <?php if ($station): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="card p-5">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-lightbulb text-blue-600"></i>
                        </div>
                        <h3 class="font-semibold">Tips for Editing Stations</h3>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Keep station names consistent with your organization's naming convention</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Ensure the station is assigned to the correct division and zone</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Select all relevant report types to ensure comprehensive monitoring</span>
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
                        If you need assistance with editing stations or have questions about report types, 
                        our support team is here to help.
                    </p>
                    <a href="#" class="text-sm text-purple-600 font-medium hover:text-purple-800 flex items-center">
                        <i class="fas fa-headset mr-2"></i>
                        Contact Support
                    </a>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Related Actions -->
            <?php if ($station): ?>
            <div class="card p-5 mb-6">
                <h3 class="font-semibold mb-4">Related Actions</h3>
                <div class="flex flex-wrap gap-3">
                    <a href="add-weightage.php?stationId=<?php echo $stationId; ?>" class="btn btn-secondary">
                        <i class="fas fa-balance-scale mr-2"></i>
                        Manage Weightages
                    </a>
                    <a href="#" class="btn btn-secondary">
                        <i class="fas fa-chart-bar mr-2"></i>
                        View Reports
                    </a>
                    <a href="#" class="btn btn-secondary text-red-600 border-red-600 hover:bg-red-50">
                        <i class="fas fa-trash-alt mr-2"></i>
                        Delete Station
                    </a>
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
    const sidebar = document.querySelector('aside');
    
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    }
    
    // Form validation
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(event) {
        const stationName = document.getElementById('stationName');
        const loginId = document.getElementById('db_stLoginId');
        const zoneName = document.getElementById('zoneName');
        const divisionId = document.getElementById('DivisionId');
        const reportTypes = document.querySelectorAll('input[name="reportType[]"]:checked');
        
        let isValid = true;
        
        // Basic validation
        if (stationName.value.trim() === '') {
            highlightError(stationName);
            isValid = false;
        }
        
        if (loginId.value.trim() === '') {
            highlightError(loginId);
            isValid = false;
        }
        
        if (zoneName.value === '') {
            highlightError(zoneName);
            isValid = false;
        }
        
        if (divisionId.value === '') {
            highlightError(divisionId);
            isValid = false;
        }
        
        if (reportTypes.length === 0) {
            // Highlight the report type section
            document.querySelector('.bg-gray-50').classList.add('border-red-500');
            isValid = false;
        }
        
        if (!isValid) {
            event.preventDefault();
        }
    });
    
    // Helper function to highlight errors
    function highlightError(element) {
        element.classList.add('border-red-500');
        element.addEventListener('input', function() {
            this.classList.remove('border-red-500');
        }, { once: true });
    }
    
    // Clear error highlighting on input change
    const inputs = document.querySelectorAll('.form-input, .form-select');
    inputs.forEach(input => {
        input.addEventListener('change', function() {
            this.classList.remove('border-red-500');
        });
    });
    
    // Clear report type error on checkbox change
    const checkboxes = document.querySelectorAll('.checkbox-input');
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            document.querySelector('.bg-gray-50').classList.remove('border-red-500');
        });
    });
</script>

</body>
</html>