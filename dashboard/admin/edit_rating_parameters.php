<?php
session_start();

include "../../connection.php";

// Initialize variables
$successMsg = '';
$errorMsg = '';
$stationId = isset($_GET['station_id']) ? intval($_GET['station_id']) : 0;

// Handle form submission for updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $paramId = $_POST['param_id'] ?? 0;
    $action = $_POST['action'] ?? '';
    
    if ($action == 'update' && $paramId > 0) {
        $newName = trim($_POST['rating_name'] ?? '');
        $newValue = intval($_POST['value'] ?? 0);
        
        if (!empty($newName)) {
            try {
                $stmt = $conn->prepare("UPDATE rating_parameters SET rating_name = ?, value = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("sii", $newName, $newValue, $paramId);
                
                if ($stmt->execute()) {
                    $successMsg = "Parameter updated successfully";
                } else {
                    $errorMsg = "Error updating parameter";
                }
                $stmt->close();
            } catch (Exception $e) {
                $errorMsg = "Error: " . $e->getMessage();
            }
        } else {
            $errorMsg = "Rating name cannot be empty";
        }
    } elseif ($action == 'delete' && $paramId > 0) {
        try {
            $stmt = $conn->prepare("DELETE FROM rating_parameters WHERE id = ?");
            $stmt->bind_param("i", $paramId);
            
            if ($stmt->execute()) {
                $successMsg = "Parameter deleted successfully";
            } else {
                $errorMsg = "Error deleting parameter";
            }
            $stmt->close();
        } catch (Exception $e) {
            $errorMsg = "Error: " . $e->getMessage();
        }
    }
}

// Get station information
$stationName = '';
if ($stationId > 0) {
    $stmt = $conn->prepare("SELECT stationName FROM baris_station WHERE stationId = ?");
    $stmt->bind_param("i", $stationId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $stationName = $row['stationName'];
    }
    $stmt->close();
}

// Get existing parameters
$parameters = [];
if ($stationId > 0) {
    $stmt = $conn->prepare("SELECT id, rating_name, value FROM rating_parameters WHERE station_id = ? ORDER BY value DESC");
    $stmt->bind_param("i", $stationId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $parameters[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Rating Parameters | <?= htmlspecialchars($stationName) ?></title>
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
        
        .btn-danger {
            background-color: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background-color: #dc2626;
        }
        
        .btn-success {
            background-color: #10b981;
            color: white;
        }
        
        .btn-success:hover {
            background-color: #059669;
        }
    </style>
</head>
<body>

<div class="min-h-screen p-6">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Edit Rating Parameters</h1>
                <p class="text-gray-600">Station: <?= htmlspecialchars($stationName) ?></p>
            </div>
            <button onclick="window.location.href='view_rating_parameters.php'" class="btn btn-secondary">
    <i class="fas fa-times mr-2"></i>
    Close
</button>

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
        
        <!-- Parameters List -->
        <div class="card">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800 flex items-center">
                    <i class="fas fa-star mr-2 text-blue-600"></i>
                    Rating Parameters (<?= count($parameters) ?> total)
                </h2>
            </div>
            
            <div class="p-6">
                <?php if (empty($parameters)): ?>
                    <div class="text-center py-8">
                        <i class="fas fa-star text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No rating parameters found for this station.</p>
                        <a href="create-rating-parameters.php" class="btn btn-primary mt-4">
                            <i class="fas fa-plus mr-2"></i>
                            Add Parameters
                        </a>
                    </div>
                <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($parameters as $param): ?>
                            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg" id="param-<?= $param['id'] ?>">
                                <div class="flex items-center flex-1">
                                    <span class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-800 font-bold mr-4">
                                        <?= $param['value'] ?>
                                    </span>
                                    <div class="flex-1">
                                        <span class="parameter-name text-lg font-medium text-gray-800">
                                            <?= htmlspecialchars($param['rating_name']) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button onclick="editParameter(<?= $param['id'] ?>)" class="btn btn-secondary btn-sm">
                                        <i class="fas fa-edit mr-1"></i>
                                        Edit
                                    </button>
                                    <button onclick="deleteParameter(<?= $param['id'] ?>)" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash mr-1"></i>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <a href="test.php?station_id=<?= $stationId ?>" class="btn btn-primary">
                            <i class="fas fa-plus mr-2"></i>
                            Add More Parameters
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-500">
            <p>&copy; 2025 BeatleBuddy. All rights reserved.</p>
        </div>
    </div>
</div>

<!-- Edit Parameter Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-semibold mb-4">Edit Rating Parameter</h3>
        
        <form id="editForm" method="POST">
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="param_id" id="editParamId">
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Rating Name</label>
                <input type="text" name="rating_name" id="editRatingName" class="form-input" required>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Value</label>
                <input type="number" name="value" id="editValue" class="form-input" min="0" required>
            </div>
            
            <div class="flex justify-end space-x-2">
                <button type="button" onclick="closeEditModal()" class="btn btn-secondary">Cancel</button>
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save mr-2"></i>
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function editParameter(paramId) {
    // Get the parameter row
    const paramRow = document.getElementById('param-' + paramId);
    const nameElement = paramRow.querySelector('.parameter-name');
    const valueElement = paramRow.querySelector('.bg-blue-100');
    
    // Set modal values
    document.getElementById('editParamId').value = paramId;
    document.getElementById('editRatingName').value = nameElement.textContent.trim();
    document.getElementById('editValue').value = valueElement.textContent.trim();
    
    // Show modal
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
}

function deleteParameter(paramId) {
    if (confirm('Are you sure you want to delete this rating parameter? This action cannot be undone.')) {
        // Create a form to submit delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="param_id" value="${paramId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close modal when clicking outside
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeEditModal();
    }
});
</script>

</body>
</html>
