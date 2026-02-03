<?php
error_reporting(E_ALL); // Report all errors and warnings
ini_set('display_errors', 1); // Display errors in the browser
session_start();

include "../../connection.php";

// Handle toggling OTP status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_otp'])) {
    $stationId = intval($_POST['station_id'] ?? 0);
    $newStatus = intval($_POST['new_status'] ?? 0);
    
    if ($stationId <= 0) {
        $errorMsg = "Invalid station ID";
    } else {
        try {
            $stmt = $conn->prepare("UPDATE feedback_stations SET otp_status = ? WHERE id = ?");
            $stmt->bind_param("ii", $newStatus, $stationId);
            
            if ($stmt->execute()) {
                $successMsg = "OTP status updated successfully!";
            } else {
                $errorMsg = "Error updating OTP status: " . $stmt->error;
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $errorMsg = "Error: " . $e->getMessage();
        }
    }
}

// Get all feedback stations with their OTP status
$stations = [];
$query = "SELECT fs.id, fs.name, fs.zone, fs.division, fs.otp_status 
          FROM feedback_stations fs 
          ORDER BY fs.name ASC";

$result = $conn->query($query);
if ($result) {
    $stations = $result->fetch_all(MYSQLI_ASSOC);
}

// Count OTP enabled stations
$otpEnabledCount = 0;
$otpDisabledCount = 0;
foreach ($stations as $station) {
    if ($station['otp_status'] == 1) {
        $otpEnabledCount++;
    } else {
        $otpDisabledCount++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OTP Status Management | Station Feedback</title>
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
        
        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 50px;
            height: 24px;
        }
        
        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #cbd5e1;
            transition: .4s;
            border-radius: 24px;
        }
        
        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 16px;
            width: 16px;
            left: 4px;
            bottom: 4px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }
        
        input:checked + .toggle-slider {
            background-color: #10b981;
        }
        
        input:focus + .toggle-slider {
            box-shadow: 0 0 1px #10b981;
        }
        
        input:checked + .toggle-slider:before {
            transform: translateX(26px);
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
                    <h1 class="text-xl font-bold text-gray-800">OTP Status Management</h1>
                </div>
            </div>
        </header>
        
        <!-- Page Content -->
        <div class="p-6">
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
                    <p class="text-red-700"><?php echo htmlspecialchars($errorMsg); ?></p>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Info Card -->
            <div class="card p-6 mb-8 bg-blue-50 border border-blue-200">
                <div class="flex items-start">
                    <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-4 mt-1">
                        <i class="fas fa-info-circle text-blue-600"></i>
                    </div>
                    <div>
                        <h3 class="text-md font-semibold text-gray-800 mb-2">About OTP Status</h3>
                        <p class="text-sm text-gray-600">
                            Toggle the OTP status for each station. When OTP is enabled (1), verification is required.
                            When OTP is disabled (0), no verification is needed.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Stations Table -->
            <div class="card mb-8">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-semibold text-gray-800">
                            Station OTP Status
                        </h2>
                        <div class="flex items-center">
                            <div class="relative">
                                <input type="text" id="searchInput" placeholder="Search stations..." class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <i class="fas fa-search absolute right-3 top-2.5 text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Station Name
                                </th>
                                <!--<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">-->
                                <!--    Zone-->
                                <!--</th>-->
                                <!--<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">-->
                                <!--    Division-->
                                <!--</th>-->
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Current Status
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Toggle OTP
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="stationTableBody">
                            <?php if (empty($stations)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                        No stations found
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($stations as $station): ?>
                                    <tr class="station-row">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($station['name']) ?></div>
                                        </td>
                                        <!--<td class="px-6 py-4 whitespace-nowrap">-->
                                        <!--    <span class="zone-badge zone-<?= strtolower($station['zone']) ?>">-->
                                        <!--        <?= htmlspecialchars($station['zone']) ?>-->
                                        <!--    </span>-->
                                        <!--</td>-->
                                        <!--<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">-->
                                        <!--    <?= htmlspecialchars($station['division']) ?>-->
                                        <!--</td>-->
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php if ($station['otp_status'] == 1): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Enabled (1)
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    Disabled (0)
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <form method="POST" class="toggle-form">
                                                <input type="hidden" name="station_id" value="<?= $station['id'] ?>">
                                                <input type="hidden" name="new_status" value="<?= $station['otp_status'] == 1 ? 0 : 1 ?>">
                                                <button type="submit" name="toggle_otp" class="px-3 py-1 text-xs font-medium rounded-md <?= $station['otp_status'] == 1 ? 'bg-red-100 text-red-700 hover:bg-red-200' : 'bg-green-100 text-green-700 hover:bg-green-200' ?>">
                                                    <?= $station['otp_status'] == 1 ? 'Disable' : 'Enable' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 border-t border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <p class="text-sm text-gray-600" id="resultCount">
                            Showing <?= count($stations) ?> stations
                        </p>
                        <div>
                            <span class="text-sm text-gray-500">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 mr-2">
                                    Enabled: <?= $otpEnabledCount ?>
                                </span>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Disabled: <?= $otpDisabledCount ?>
                                </span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
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

// Search functionality
const searchInput = document.getElementById('searchInput');
const stationRows = document.querySelectorAll('.station-row');
const resultCount = document.getElementById('resultCount');

searchInput.addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    let visibleCount = 0;
    
    stationRows.forEach(row => {
        const stationName = row.querySelector('td:first-child').textContent.toLowerCase();
        
        if (stationName.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    resultCount.textContent = `Showing ${visibleCount} stations`;
});
</script>

</body>
</html>