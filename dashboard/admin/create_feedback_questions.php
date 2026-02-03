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
    $questions = $_POST['questions'] ?? [];
    $created_at = date('Y-m-d H:i:s');
    $updated_at = date('Y-m-d H:i:s');
    
    // Validate input
    if (empty($stationId)) {
        $errorMsg = "Please select a station";
    } elseif (empty($questions) || count($questions) == 0) {
        $errorMsg = "Please add at least one question";
    } else {
        try {
            // Start transaction for multiple inserts
            $conn->begin_transaction();
            
            // Prepare statement for inserting questions
            $stmt = $conn->prepare("INSERT INTO feedback_questions (question_text, station_id, created_at, updated_at) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $question_text, $stationId, $created_at, $updated_at);
            
            $successCount = 0;
            // Process each question
            foreach ($questions as $question) {
                // Skip empty questions
                if (empty(trim($question))) {
                    continue;
                }
                
                $question_text = trim($question);
                
                if ($stmt->execute()) {
                    $successCount++;
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            if ($successCount > 0) {
                $questionCount = isset($stationQuestionCounts[$stationId]) ? $stationQuestionCounts[$stationId] : 0;
                $totalQuestions = $questionCount + $successCount;
                $successMsg = "$successCount new feedback questions added successfully. Station now has $totalQuestions total questions.";
                // Reset the form
                unset($_POST);
            } else {
                $errorMsg = "No valid questions were submitted";
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

// Get station question counts for display purposes
$stationQuestionCounts = [];
$countQuery = $conn->query("SELECT station_id, COUNT(*) as question_count FROM feedback_questions GROUP BY station_id");
if ($countQuery) {
    $result = $countQuery->fetch_all(MYSQLI_ASSOC);
    foreach ($result as $row) {
        $stationQuestionCounts[$row['station_id']] = $row['question_count'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Feedback Questions | Station Management</title>
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
        
        .question-row {
            position: relative;
            display: flex;
            margin-bottom: 1rem;
            gap: 1rem;
        }
        
        .remove-question {
            position: absolute;
            right: -30px;
            top: 50%;
            transform: translateY(-50%);
            color: #ef4444;
            cursor: pointer;
            opacity: 0.7;
            transition: opacity 0.2s;
        }
        
        .remove-question:hover {
            opacity: 1;
        }
        
        .form-textarea {
            width: 100%;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            transition: all 0.2s;
            resize: vertical;
            min-height: 60px;
        }
        
        .form-textarea:focus {
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
                    <h1 class="text-xl font-bold text-gray-800">Create Feedback Questions</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                   <a href="view_feedback_questions.php" class="flex items-center text-sm font-medium text-blue-600 hover:text-blue-800">
                        <i class="fas fa-list mr-2"></i>
                        View All Questions
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
                                <a href="#" class="text-gray-500 hover:text-blue-600">Feedback</a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                                <span class="text-gray-700">Create Questions</span>
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
                            <h2 class="text-lg font-semibold text-gray-800">Feedback Questions</h2>
                            <p class="text-gray-500 text-sm mt-1">Create questions for station feedback collection</p>
                        </div>
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                            <i class="fas fa-question-circle text-blue-600"></i>
                        </div>
                    </div>
                </div>
                
                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="questionForm" class="p-6">
                    <div class="grid grid-cols-1 gap-6 mb-8">
                        <div>
                            <label for="station_id" class="block text-sm font-medium text-gray-700 mb-2">Select Station*</label>
                            <select id="station_id" name="station_id" class="form-select" required>
                                <option value="">-- Choose Station --</option>
                                <?php foreach ($stations as $station): ?>
                                    <?php 
                                    $questionCount = isset($stationQuestionCounts[$station['stationId']]) ? $stationQuestionCounts[$station['stationId']] : 0;
                                    $selected = isset($_POST['station_id']) && $_POST['station_id'] == $station['stationId'] ? 'selected' : '';
                                    ?>
                                    <option 
                                        value="<?= htmlspecialchars($station['stationId']) ?>" 
                                        <?= $selected ?>
                                    >
                                        <?= htmlspecialchars($station['stationName']) ?> 
                                        <?= $questionCount > 0 ? "($questionCount questions)" : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="mt-1 text-xs text-gray-500">Select the station to add feedback questions. Stations with existing questions will show the current count.</p>
                        </div>
                        
                        <!-- Existing Questions Display -->
                        <div id="existingQuestionsSection" class="hidden bg-gray-50 p-4 rounded-lg">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="text-sm font-medium text-gray-700">
                                    <i class="fas fa-list mr-2 text-blue-500"></i>
                                    Existing Questions for This Station
                                </h4>
                                <button type="button" id="editExistingBtn" class="text-xs text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-edit mr-1"></i>
                                    Edit Existing
                                </button>
                            </div>
                            <div id="existingQuestionsList" class="space-y-2">
                                <!-- Existing questions will be loaded here -->
                            </div>
                        </div>
                        
                        <div class="border-t border-gray-200 pt-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-md font-medium text-gray-700 flex items-center">
                                    <i class="fas fa-question mr-2 text-blue-500"></i>
                                    Add New Questions
                                </h3>
                                <button type="button" id="addQuestion" class="text-sm text-blue-600 flex items-center">
                                    <i class="fas fa-plus-circle mr-1"></i>
                                    Add Question
                                </button>
                            </div>
                            
                            <p class="text-sm text-gray-600 mb-4">
                                <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                                Add new feedback questions below. If the station already has questions, they will be preserved.
                            </p>
                            
                            <div id="questionsContainer">
                                <!-- Default questions -->
                                <div class="question-row">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Question*</label>
                                        <textarea 
                                            name="questions[]" 
                                            class="form-textarea" 
                                            placeholder="e.g. How would you rate the cleanliness of the station?"
                                            required
                                        >How would you rate the cleanliness of the station?</textarea>
                                    </div>
                                    <span class="remove-question">
                                        <i class="fas fa-times-circle"></i>
                                    </span>
                                </div>
                                
                                <div class="question-row">
                                    <div class="flex-1">
                                        <textarea 
                                            name="questions[]" 
                                            class="form-textarea" 
                                            placeholder="e.g. How satisfied are you with the maintenance of facilities?"
                                            required
                                        >How satisfied are you with the maintenance of facilities?</textarea>
                                    </div>
                                    <span class="remove-question">
                                        <i class="fas fa-times-circle"></i>
                                    </span>
                                </div>
                                
                                <div class="question-row">
                                    <div class="flex-1">
                                        <textarea 
                                            name="questions[]" 
                                            class="form-textarea" 
                                            placeholder="e.g. How would you rate the overall hygiene standards?"
                                            required
                                        >How would you rate the overall hygiene standards?</textarea>
                                    </div>
                                    <span class="remove-question">
                                        <i class="fas fa-times-circle"></i>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="mt-2 mb-4">
                                <p class="text-sm text-gray-500">
                                    <i class="fas fa-info-circle mr-1 text-blue-500"></i>
                                    These questions will be displayed to users when they provide feedback about the station.
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
                            Save Questions
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
                        <h3 class="font-semibold">Tips for Creating Questions</h3>
                    </div>
                    <ul class="space-y-2 text-sm text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Use clear, specific questions about station aspects</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Focus on measurable aspects like cleanliness, maintenance</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>Keep questions concise and easy to understand</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-check-circle text-green-500 mt-1 mr-2"></i>
                            <span>You can add more questions to stations that already have some</span>
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
                        If you need assistance with creating feedback questions or understanding how they work, 
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
    // Mobile menu toggle
    const mobileMenuButton = document.getElementById('mobile-menu-button');
    const sidebarElement = document.querySelector('aside');
    
    if (mobileMenuButton && sidebarElement) {
        mobileMenuButton.addEventListener('click', () => {
            sidebarElement.classList.toggle('-translate-x-full');
        });
    }
    
    // Add new question field
    document.addEventListener('DOMContentLoaded', function() {
        const addQuestionBtn = document.getElementById('addQuestion');
        const questionsContainer = document.getElementById('questionsContainer');
        const resetFormBtn = document.getElementById('resetForm');
        const questionForm = document.getElementById('questionForm');
        const stationSelect = document.getElementById('station_id');
        const existingQuestionsSection = document.getElementById('existingQuestionsSection');
        const existingQuestionsList = document.getElementById('existingQuestionsList');
        const editExistingBtn = document.getElementById('editExistingBtn');

        // Load existing questions when station is selected
        if (stationSelect && existingQuestionsSection) {
            stationSelect.addEventListener('change', function() {
                const stationId = this.value;
                
                if (stationId) {
                    // Show the existing questions section
                    existingQuestionsSection.classList.remove('hidden');
                    
                    // Load existing questions via AJAX
                    loadExistingQuestions(stationId);
                } else {
                    existingQuestionsSection.classList.add('hidden');
                }
            });
        }

        // Function to load existing questions
        function loadExistingQuestions(stationId) {
            // Create a simple AJAX request
            const xhr = new XMLHttpRequest();
            xhr.open('GET', 'get_feedback_questions.php?station_id=' + stationId, true);
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (xhr.status === 200) {
                        try {
                            const data = JSON.parse(xhr.responseText);
                            displayExistingQuestions(data);
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                            existingQuestionsList.innerHTML = '<p class="text-sm text-red-600">Error loading questions</p>';
                        }
                    } else {
                        existingQuestionsList.innerHTML = '<p class="text-sm text-red-600">Error loading questions</p>';
                    }
                }
            };
            
            xhr.send();
        }

        // Function to display existing questions
        function displayExistingQuestions(questions) {
            if (questions.length === 0) {
                existingQuestionsList.innerHTML = '<p class="text-sm text-gray-500">No existing questions found for this station.</p>';
                return;
            }

            let html = '<div class="space-y-3">';
            
            questions.forEach(function(question, index) {
                html += `
                    <div class="flex items-start justify-between bg-white p-3 rounded border">
                        <div class="flex items-start">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-3 mt-1">
                                ${index + 1}
                            </span>
                            <span class="text-sm text-gray-700">${question.question_text}</span>
                        </div>
                        <button type="button" class="edit-question-btn text-xs text-blue-600 hover:text-blue-800 ml-2" data-id="${question.id}">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                `;
            });
            
            html += '</div>';
            existingQuestionsList.innerHTML = html;
        }

        // Edit existing questions button
        if (editExistingBtn) {
            editExistingBtn.addEventListener('click', function() {
                const stationId = stationSelect.value;
                if (stationId) {
                    window.open('edit_feedback_questions.php?station_id=' + stationId, '_blank');
                }
            });
        }

        // Add question button functionality
        if (addQuestionBtn && questionsContainer) {
            addQuestionBtn.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Create a new question row
                const questionRow = document.createElement('div');
                questionRow.className = 'question-row';
                
                questionRow.innerHTML = `
                    <div class="flex-1">
                        <textarea 
                            name="questions[]" 
                            class="form-textarea" 
                            placeholder="Enter your feedback question here..."
                            required
                        ></textarea>
                    </div>
                    <span class="remove-question">
                        <i class="fas fa-times-circle"></i>
                    </span>
                `;
                
                // Append to the container
                questionsContainer.appendChild(questionRow);
                
                // Focus the new textarea
                const newTextarea = questionRow.querySelector('textarea');
                if (newTextarea) {
                    newTextarea.focus();
                }
            });
        }
        
        // Remove question using event delegation
        document.body.addEventListener('click', function(e) {
            const target = e.target;
            
            // Check if the click was on the X icon or its parent
            if (target.classList.contains('fa-times-circle') || 
                target.classList.contains('remove-question')) {
                
                // Find the parent question-row div
                const questionRow = target.closest('.question-row');
                if (questionRow) {
                    // Don't remove if it's the last question
                    const allQuestions = document.querySelectorAll('.question-row');
                    if (allQuestions.length > 1) {
                        questionRow.remove();
                    } else {
                        // If it's the last one, just clear the textarea
                        const textarea = questionRow.querySelector('textarea');
                        if (textarea) {
                            textarea.value = '';
                        }
                    }
                }
            }
        });
        
        // Reset form
        if (resetFormBtn && questionForm) {
            resetFormBtn.addEventListener('click', function() {
                // Reset the form fields
                questionForm.reset();
                
                // Remove all additional question rows, keeping only the original 3
                const questionRows = document.querySelectorAll('.question-row');
                
                // Keep only the first 3 rows (default setup)
                for (let i = 3; i < questionRows.length; i++) {
                    questionRows[i].remove();
                }
                
                // Reset the values of the first 3 rows to the defaults
                const defaultQuestions = [
                    "How would you rate the cleanliness of the station?",
                    "How satisfied are you with the maintenance of facilities?",
                    "How would you rate the overall hygiene standards?"
                ];
                
                for (let i = 0; i < 3 && i < questionRows.length; i++) {
                    const textarea = questionRows[i].querySelector('textarea');
                    if (textarea && defaultQuestions[i]) {
                        textarea.value = defaultQuestions[i];
                    }
                }
            });
        }
        
        // Form validation
        if (questionForm) {
            questionForm.addEventListener('submit', function(event) {
                let isValid = true;
                const requiredFields = questionForm.querySelectorAll('[required]');
                
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
                    const firstError = questionForm.querySelector('.border-red-500');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        firstError.focus();
                    }
                }
            });
        }
        
        // Clear validation styling on input
        document.addEventListener('input', function(e) {
            if (e.target.classList.contains('form-textarea') || 
                e.target.classList.contains('form-input') || 
                e.target.classList.contains('form-select')) {
                e.target.classList.remove('border-red-500');
            }
        });
    });
</script>

</body>
</html>
