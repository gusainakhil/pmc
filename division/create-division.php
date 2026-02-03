<?php

// add division in database
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Include database connection
    include "connection.php";

    // Get the division name from the form
    $division_name = $_POST['division_name'];

    // Prepare and execute the SQL statement
    $stmt = $conn->prepare("INSERT INTO baris_division (DivisionName) VALUES (?)");
    $stmt->bind_param("s", $division_name);

    if ($stmt->execute()) {
        // Redirect before any output
        header("Location: create-division.php?success=true");
        exit();
    } else {
        $errorMsg = "Error: " . $stmt->error;
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Division | Station Cleaning</title>
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
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .form-input {
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            padding: 0.75rem 1rem;
            width: 100%;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.3);
        }
        
        .btn-primary {
            background-color: #3b82f6;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: background-color 0.2s;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
        }
        
        .btn-secondary {
            background-color: white;
            color: #3b82f6;
            border: 1px solid #3b82f6;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: background-color 0.2s, color 0.2s;
        }
        
        .btn-secondary:hover {
            background-color: #eff6ff;
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
                    <button class="mr-4 text-gray-600 md:hidden">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="text-xl font-bold text-gray-800">Create Division</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="list-division.php" class="flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                        <i class="fas fa-list mr-2"></i>
                        View All Divisions
                    </a>
                    
                    <div class="relative">
                        <button class="flex items-center space-x-2">
                            <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center">
                                <span class="font-bold text-white">A</span>
                            </div>
                            <span class="hidden md:inline-block font-medium text-sm">Admin</span>
                            <i class="fas fa-chevron-down text-xs text-gray-500"></i>
                        </button>
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
                                <a href="list-division.php" class="text-gray-500 hover:text-blue-600">Divisions</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                                <span class="text-gray-700">Create Division</span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <!-- Card -->
            <div class="card p-6">
                <div class="mb-6">
                    <h2 class="text-lg font-semibold text-gray-800">Division Information</h2>
                    <p class="text-gray-500 text-sm mt-1">Create a new division for your organization</p>
                </div>
                
                <?php if (isset($_GET['success']) && $_GET['success'] == 'true'): ?>
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-500 mr-3"></i>
                        <p class="text-green-700">Division has been created successfully!</p>
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
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                    <div class="mb-6">
                        <label for="division_name" class="block text-sm font-medium text-gray-700 mb-2">Division Name</label>
                        <input 
                            type="text" 
                            id="division_name" 
                            name="division_name" 
                            class="form-input" 
                            placeholder="Enter division name"
                            required
                        >
                        <p class="mt-1 text-xs text-gray-500">Enter a unique name for the division</p>
                    </div>
                    
                    <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                        <a href="list-division.php" class="btn-secondary">
                            Cancel
                        </a>
                        <button type="submit" class="btn-primary">
                            <i class="fas fa-plus mr-2"></i>
                            Create Division
                        </button>
                    </div>
                </form>
            </div>
            
            <!-- Quick Tips -->
            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="card p-5">
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center mr-3">
                            <i class="fas fa-lightbulb text-blue-600"></i>
                        </div>
                        <h3 class="font-semibold">Tips for Creating Divisions</h3>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Use clear, descriptive names for divisions</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Organize divisions by geographical area or function</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Ensure division names are unique across your organization</span>
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
                        If you need assistance with creating divisions or have questions about organization structure, 
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
    // Form validation
    const form = document.querySelector('form');
    const divisionNameInput = document.getElementById('division_name');
    
    form.addEventListener('submit', function(event) {
        let isValid = true;
        
        // Validate division name
        if (divisionNameInput.value.trim().length < 2) {
            isValid = false;
            divisionNameInput.classList.add('border-red-500');
            
            // Add error message if it doesn't exist
            if (!divisionNameInput.nextElementSibling.classList.contains('text-red-500')) {
                const errorMsg = document.createElement('p');
                errorMsg.classList.add('mt-1', 'text-xs', 'text-red-500');
                errorMsg.textContent = 'Division name must be at least 2 characters long';
                divisionNameInput.parentNode.insertBefore(errorMsg, divisionNameInput.nextElementSibling.nextSibling);
            }
        } else {
            divisionNameInput.classList.remove('border-red-500');
            
            // Remove error message if it exists
            if (divisionNameInput.nextElementSibling.classList.contains('text-red-500')) {
                divisionNameInput.nextElementSibling.remove();
            }
        }
        
        if (!isValid) {
            event.preventDefault();
        }
    });
    
    // Remove error styling when input changes
    divisionNameInput.addEventListener('input', function() {
        this.classList.remove('border-red-500');
        
        // Remove error message if it exists
        if (this.nextElementSibling.classList.contains('text-red-500')) {
            this.nextElementSibling.remove();
        }
    });
</script>
</body>
</html>