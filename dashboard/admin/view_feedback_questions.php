<?php
session_start();

include "../../connection.php";

// Get all feedback questions with station information
$feedbackQuestions = [];
$query = "SELECT fq.*, bs.stationName 
          FROM feedback_questions fq 
          LEFT JOIN baris_station bs ON fq.station_id = bs.stationId 
          ORDER BY bs.stationName ASC, fq.id ASC";

$result = $conn->query($query);
if ($result) {
    $feedbackQuestions = $result->fetch_all(MYSQLI_ASSOC);
}

// Get statistics
$stats = [];
$statsQuery = "SELECT 
                COUNT(*) as total_questions,
                COUNT(DISTINCT station_id) as stations_with_questions
               FROM feedback_questions";
$statsResult = $conn->query($statsQuery);
if ($statsResult && $statsRow = $statsResult->fetch_assoc()) {
    $stats = $statsRow;
}

// Group questions by station
$questionsByStation = [];
foreach ($feedbackQuestions as $question) {
    $stationName = $question['stationName'] ?? 'Unknown Station';
    if (!isset($questionsByStation[$stationName])) {
        $questionsByStation[$stationName] = [];
    }
    $questionsByStation[$stationName][] = $question;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Feedback Questions | Station Management</title>
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
                    <h1 class="text-xl font-bold text-gray-800">Feedback Questions Overview</h1>
                </div>
                
                <div class="flex items-center space-x-4">
                    <a href="create_feedback_questions.php" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i>
                        Add New Questions
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
                                <span class="text-gray-700">View Questions</span>
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
                            <p class="text-blue-100 text-sm font-medium">Total Questions</p>
                            <p class="text-2xl font-bold"><?= $stats['total_questions'] ?? 0 ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-question-circle text-xl"></i>
                        </div>
                    </div>
                </div>
                
                <div class="stat-card stat-card-green">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-100 text-sm font-medium">Stations with Questions</p>
                            <p class="text-2xl font-bold"><?= $stats['stations_with_questions'] ?? 0 ?></p>
                        </div>
                        <div class="w-12 h-12 bg-white bg-opacity-20 rounded-lg flex items-center justify-center">
                            <i class="fas fa-map-marker-alt text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Questions List -->
            <div class="card">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                        <i class="fas fa-list mr-2 text-blue-600"></i>
                        All Feedback Questions
                    </h3>
                </div>
                
                <div class="p-6">
                    <?php if (empty($feedbackQuestions)): ?>
                        <div class="text-center py-8">
                            <i class="fas fa-question-circle text-gray-400 text-6xl mb-4"></i>
                            <h3 class="text-xl font-semibold text-gray-800 mb-2">No Questions Found</h3>
                            <p class="text-gray-500 mb-6">Start by creating your first feedback question.</p>
                            <a href="create_feedback_questions.php" class="btn btn-primary">
                                <i class="fas fa-plus mr-2"></i>
                                Create First Question
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="space-y-8">
                            <?php foreach ($questionsByStation as $stationName => $questions): ?>
                                <div class="bg-gray-50 p-6 rounded-lg">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-semibold text-gray-800">
                                                <?= htmlspecialchars($stationName) ?>
                                            </h4>
                                            <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?= count($questions) ?> <?= count($questions) == 1 ? 'question' : 'questions' ?>
                                            </span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button class="btn btn-secondary btn-sm" onclick="editStationQuestions(<?= $questions[0]['station_id'] ?>)">
                                                <i class="fas fa-edit mr-1"></i>
                                                Edit
                                            </button>
                                            <button class="btn btn-danger btn-sm" onclick="deleteStationQuestions(<?= $questions[0]['station_id'] ?>, '<?= htmlspecialchars($stationName) ?>')">
                                                <i class="fas fa-trash mr-1"></i>
                                                Delete All
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-3">
                                        <?php foreach ($questions as $index => $question): ?>
                                            <div class="flex items-start justify-between bg-white p-4 rounded border">
                                                <div class="flex items-start">
                                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-3 mt-1">
                                                        <?= $index + 1 ?>
                                                    </span>
                                                    <div class="flex-1">
                                                        <p class="text-gray-800"><?= htmlspecialchars($question['question_text']) ?></p>
                                                        <p class="text-xs text-gray-500 mt-1">
                                                            ID: <?= $question['id'] ?> • 
                                                            Created: <?= date('M j, Y', strtotime($question['created_at'])) ?>
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center space-x-2 ml-4">
                                                    <button class="btn btn-secondary btn-sm" onclick="editQuestion(<?= $question['id'] ?>, '<?= htmlspecialchars($question['question_text']) ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-sm" onclick="deleteQuestion(<?= $question['id'] ?>, '<?= htmlspecialchars(substr($question['question_text'], 0, 30)) ?>...')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
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

<!-- Edit Question Modal -->
<div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Edit Question</h3>
                <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form id="editForm" method="POST" action="update_feedback_question.php">
                <input type="hidden" id="editQuestionId" name="question_id">
                
                <div class="mb-4">
                    <label for="editQuestionText" class="block text-sm font-medium text-gray-700 mb-2">Question Text</label>
                    <textarea id="editQuestionText" name="question_text" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" required></textarea>
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                        Update Question
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

function editStationQuestions(stationId) {
    window.location.href = `create_feedback_questions.php?station_id=${stationId}`;
}

function editQuestion(questionId, questionText) {
    document.getElementById('editQuestionId').value = questionId;
    document.getElementById('editQuestionText').value = questionText;
    
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

function deleteQuestion(questionId, questionPreview) {
    if (confirm(`Are you sure you want to delete this question?\n\n"${questionPreview}"\n\nThis action cannot be undone.`)) {
        // Create a form to submit delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete_feedback_question.php';
        form.innerHTML = `
            <input type="hidden" name="question_id" value="${questionId}">
            <input type="hidden" name="action" value="delete">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function deleteStationQuestions(stationId, stationName) {
    if (confirm(`Are you sure you want to delete ALL questions for "${stationName}"? This action cannot be undone.`)) {
        // Create a form to submit delete request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'delete_feedback_question.php';
        form.innerHTML = `
            <input type="hidden" name="station_id" value="${stationId}">
            <input type="hidden" name="action" value="delete_all">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

</body>
</html>
