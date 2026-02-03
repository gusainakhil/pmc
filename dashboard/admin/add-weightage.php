<?php

//get stationId
$stationId = isset($_GET['stationId']) ? intval($_GET['stationId']) : 0;
include "../../connection.php";
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get station name for display
$stationName = "";
$stationQuery = $conn->query("SELECT stationName FROM baris_station WHERE stationId = $stationId");
if ($stationQuery && $stationQuery->num_rows > 0) {
    $stationData = $stationQuery->fetch_assoc();
    $stationName = $stationData['stationName'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['weightage'])) {
    // First, delete existing weightages for this station to prevent duplicates
    $deleteStmt = $conn->prepare("DELETE FROM baris_report_weight WHERE stationId = ?");
    $deleteStmt->bind_param("i", $stationId);
    $deleteStmt->execute();
    $deleteStmt->close();
    
    // Then insert the new weightages
    $insertCount = 0;
    foreach ($_POST['weightage'] as $key => $weight) {
        if (empty($weight)) continue; // Skip empty weightages
        
        $queId = intval($_POST['queId'][$key]);
        $subqueId = intval($_POST['subqueId'][$key]);
        $weightage = intval($weight);
        
        if ($weightage > 0) {
            $stmt = $conn->prepare("INSERT INTO baris_report_weight (stationId, subqueId, weightage) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $stationId, $subqueId, $weightage);
            if ($stmt->execute()) {
                $insertCount++;
            }
            $stmt->close();
        }
    }
    
    if ($insertCount > 0) {
        $successMessage = "Weightages saved successfully for $insertCount report items.";
    } else {
        $errorMessage = "No valid weightages were provided.";
    }
}

// Get existing weightages for pre-filling the form
$existingWeightages = [];
$weightQuery = $conn->query("SELECT subqueId, weightage FROM baris_report_weight WHERE stationId = $stationId");
if ($weightQuery && $weightQuery->num_rows > 0) {
    while ($weight = $weightQuery->fetch_assoc()) {
        $existingWeightages[$weight['subqueId']] = $weight['weightage'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Weightage Management | Station Cleaning</title>
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
        
        .weightage-input {
            width: 80px;
            text-align: center;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.5rem;
            transition: all 0.2s;
        }
        
        .weightage-input:focus {
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
                    <h1 class="text-xl font-bold text-gray-800">Weightage Management</h1>
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
                                <span class="text-gray-700">Weightage for <?php echo htmlspecialchars($stationName); ?></span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <!-- Weightage Form Card -->
            <div class="card mb-6">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Report Weightages</h2>
                            <p class="text-gray-500 text-sm mt-1">Assign weightages to reporting criteria for station: <span class="font-medium"><?php echo htmlspecialchars($stationName); ?></span></p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-balance-scale text-blue-600"></i>
                        </div>
                    </div>
                    
                    <!-- Success/Error Messages -->
                    <?php if (isset($successMessage)): ?>
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <p class="text-green-700"><?php echo htmlspecialchars($successMessage); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($errorMessage)): ?>
                    <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-500 mr-3"></i>
                            <p class="text-red-700"><?php echo htmlspecialchars($errorMessage); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <form method="post" id="weightageForm">
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th class="rounded-tl-lg">Report Type</th>
                                        <th>Report Item</th>
                                        <th>Description</th>
                                        <th class="rounded-tr-lg text-center">Weightage (%)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $qResult = $conn->query("
                                        SELECT 
                                            bq.queId, 
                                            bq.queName, 
                                            bq.subqueId, 
                                            bs.stationName,
                                            bsub.subqueId as subqueId,
                                            bsub.subqueName
                                        FROM 
                                            baris_question bq
                                        INNER JOIN 
                                            baris_station bs ON bq.queId = bs.db_questionsId
                                        INNER JOIN 
                                            baris_subquestion bsub ON FIND_IN_SET(bsub.subqueId, bq.subqueId)
                                        WHERE 
                                            bs.stationId = $stationId
                                        ORDER BY 
                                            bsub.subqueName ASC
                                    ");
                                    
                                    if ($qResult && $qResult->num_rows > 0) {
                                        $i = 0;
                                        $currentQueName = '';
                                        
                                        while ($row = $qResult->fetch_assoc()) {
                                            $weightValue = isset($existingWeightages[$row['subqueId']]) ? $existingWeightages[$row['subqueId']] : '';
                                            
                                            echo '<tr>';
                                            
                                            // Only show the queName if it's different from the previous row
                                            if ($currentQueName !== $row['queName']) {
                                                echo '<td class="font-medium">' . htmlspecialchars($row['queName']) . '</td>';
                                                $currentQueName = $row['queName'];
                                            } else {
                                                echo '<td></td>';
                                            }
                                            
                                            echo '<td class="font-medium">' . htmlspecialchars($row['subqueName']) . '
                                                    <input type="hidden" name="queId['.$i.']" value="'.htmlspecialchars($row['queId']).'">
                                                    <input type="hidden" name="subqueId['.$i.']" value="'.htmlspecialchars($row['subqueId']).'">
                                                </td>';
                                            
                                            // Generate random placeholder description for demo purposes
                                            $descriptions = [
                                                "Verify cleanliness standards are maintained",
                                                "Check for proper equipment operation",
                                                "Ensure safety procedures are followed",
                                                "Monitor compliance with regulations",
                                                "Assess overall condition and functionality"
                                            ];
                                            $randomDesc = $descriptions[array_rand($descriptions)];
                                            
                                            echo '<td class="text-gray-500 text-sm">' . $randomDesc . '</td>';
                                            
                                            echo '<td class="text-center">
                                                    <input type="number" 
                                                        min="0" 
                                                        max="100" 
                                                        name="weightage['.$i.']" 
                                                        value="' . $weightValue . '" 
                                                        placeholder="0-100" 
                                                        class="weightage-input"
                                                        oninput="validateWeightage(this); calculateTotal();">
                                                </td>';
                                            
                                            echo '</tr>';
                                            $i++;
                                        }
                                        
                                        // Add a summary row for total
                                        echo '<tr class="bg-gray-50 font-medium">
                                                <td colspan="3" class="text-right">Total Weightage:</td>
                                                <td class="text-center">
                                                    <span id="totalWeightage" class="font-bold">0</span>%
                                                    <div id="weightageWarning" class="hidden text-xs text-red-500 mt-1">
                                                        Total should equal 100%
                                                    </div>
                                                </td>
                                              </tr>';
                                        
                                    } else {
                                        echo '<tr><td colspan="4" class="text-center py-8 text-gray-500">No report items found for this station.</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="flex items-center justify-between mt-6 pt-6 border-t border-gray-200">
                            <a href="list-station.php" class="btn btn-secondary">
                                Cancel
                            </a>
                            <button type="submit" class="btn btn-primary" id="saveButton">
                                <i class="fas fa-save mr-2"></i>
                                Save Weightages
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Info Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div class="card p-5">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-lightbulb text-blue-600"></i>
                        </div>
                        <h3 class="font-semibold">Tips for Weightages</h3>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Assign higher weightage to critical items</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Ensure total weightage equals 100% for accurate reporting</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Consider each station's unique requirements when assigning weightages</span>
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
                        If you need assistance with setting weightages or understanding 
                        how they affect reporting, our support team is here to help.
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
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const sidebar = document.querySelector('aside');
    
    if (mobileMenuButton) {
        mobileMenuButton.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    }
    
    // Validate weightage input (0-100)
    function validateWeightage(input) {
        let value = parseInt(input.value);
        
        if (isNaN(value)) {
            input.value = '';
            return;
        }
        
        if (value < 0) input.value = 0;
        if (value > 100) input.value = 100;
    }
    
    // Calculate total weightage
    function calculateTotal() {
        const inputs = document.querySelectorAll('input[name^="weightage"]');
        const totalElement = document.getElementById('totalWeightage');
        const warningElement = document.getElementById('weightageWarning');
        const saveButton = document.getElementById('saveButton');
        
        let total = 0;
        
        inputs.forEach(input => {
            const value = parseInt(input.value) || 0;
            total += value;
        });
        
        totalElement.textContent = total;
        
        // Show warning if total is not 100%
        if (total !== 0 && total !== 100) {
            warningElement.classList.remove('hidden');
            // Disable save button for strict enforcement (optional)
            // saveButton.disabled = true;
        } else {
            warningElement.classList.add('hidden');
            // saveButton.disabled = false;
        }
    }
    
    // Run calculation on page load
    document.addEventListener('DOMContentLoaded', calculateTotal);
    
    // Form validation before submission
    document.getElementById('weightageForm').addEventListener('submit', function(e) {
        const total = parseInt(document.getElementById('totalWeightage').textContent);
        
        if (total === 0) {
            // Allow submission with zero total (optional)
            if (!confirm('No weightages have been assigned. Do you want to continue?')) {
                e.preventDefault();
            }
        } else if (total !== 100) {
            // Warning for incorrect total
            if (!confirm('Total weightage is ' + total + '%, but should be 100%. Do you want to continue anyway?')) {
                e.preventDefault();
            }
        }
    });
</script>

</body>
</html>