<?php

session_start();

include "../../connection.php";

$sql = "SELECT userId, db_userLoginName, db_username, db_phone, db_email, reportType, OrgID, DivisionId, StationId ,login_token FROM baris_userlogin where db_usertype= 'owner'";
$result = $conn->query($sql);

// Get user type distribution for chart
$userTypeQuery = "SELECT db_usertype, COUNT(*) as count FROM baris_userlogin WHERE db_usertype IS NOT NULL GROUP BY db_usertype";
$userTypeResult = $conn->query($userTypeQuery);

$userTypes = [];
$userCounts = [];
$chartColors = [
  '#3b82f6', // blue
  '#6366f1', // indigo
  '#a855f7', // purple
  '#ec4899', // pink
  '#f43f5e', // rose
  '#10b981' // emerald
];

while($row = $userTypeResult->fetch_assoc()) {
  $userTypes[] = $row['db_usertype'];
  $userCounts[] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | Station Cleaning</title>
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
    
    .card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }
    
    .stat-card {
      display: flex;
      flex-direction: column;
      padding: 1.5rem;
    }
    
    .stat-icon {
      width: 48px;
      height: 48px;
      border-radius: 12px;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1rem;
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
  <div class="ml-0 md:ml-72 flex-1 min-h-screen">
    <!-- Top Navigation -->
    <header class="bg-white shadow-sm sticky top-0 z-10">
      <div class="flex items-center justify-between px-6 py-4">
        <div class="flex items-center">
          <button id="mobile-menu-button" class="mr-4 text-gray-600 md:hidden">
            <i class="fas fa-bars"></i>
          </button>
          <h1 class="text-xl font-bold text-gray-800">Dashboard</h1>
        </div>
        
        <div class="flex items-center space-x-4">
          <!-- Search -->
          <div class="relative hidden md:block">
            <input type="text" placeholder="Search..." class="w-64 pl-10 pr-4 py-2 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
          </div>
          
          <!-- Notifications -->
          <div class="relative dropdown">
            <button class="p-2 rounded-full hover:bg-gray-100">
              <i class="fas fa-bell text-gray-600"></i>
              <span class="absolute top-0 right-0 h-4 w-4 bg-red-500 rounded-full text-xs text-white flex items-center justify-center">3</span>
            </button>
            <div class="dropdown-menu absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg overflow-hidden z-20">
              <div class="p-3 border-b border-gray-200">
                <h3 class="font-semibold">Notifications</h3>
                <p class="text-xs text-gray-500">You have 3 unread notifications</p>
              </div>
              <div class="max-h-64 overflow-y-auto">
                <a href="#" class="block p-4 border-b border-gray-200 hover:bg-gray-50">
                  <div class="flex">
                    <div class="rounded-full bg-blue-100 p-2 mr-3">
                      <i class="fas fa-clipboard-check text-blue-600"></i>
                    </div>
                    <div>
                      <p class="font-medium text-sm">Station #3 report completed</p>
                      <p class="text-xs text-gray-500">2 hours ago</p>
                    </div>
                  </div>
                </a>
                <a href="#" class="block p-4 border-b border-gray-200 hover:bg-gray-50">
                  <div class="flex">
                    <div class="rounded-full bg-yellow-100 p-2 mr-3">
                      <i class="fas fa-exclamation-triangle text-yellow-600"></i>
                    </div>
                    <div>
                      <p class="font-medium text-sm">Cleaning alert at Station #5</p>
                      <p class="text-xs text-gray-500">5 hours ago</p>
                    </div>
                  </div>
                </a>
                <a href="#" class="block p-4 hover:bg-gray-50">
                  <div class="flex">
                    <div class="rounded-full bg-green-100 p-2 mr-3">
                      <i class="fas fa-user-plus text-green-600"></i>
                    </div>
                    <div>
                      <p class="font-medium text-sm">New user registered</p>
                      <p class="text-xs text-gray-500">1 day ago</p>
                    </div>
                  </div>
                </a>
              </div>
              <div class="p-3 border-t border-gray-200 bg-gray-50">
                <a href="#" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View all notifications</a>
              </div>
            </div>
          </div>
          
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

    <!-- Dashboard Content -->
    <div class="p-6">
      <!-- Overview Section -->
      <div class="mb-8">
        <div class="flex flex-wrap items-center justify-between mb-6">
          <h2 class="text-2xl font-bold text-gray-800">Overview</h2>
          <div class="flex space-x-2">
            <select class="px-4 py-2 rounded-lg border border-gray-300 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
              <option>Last 7 days</option>
              <option>Last 30 days</option>
              <option>This month</option>
              <option>Last month</option>
            </select>
            <button class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg text-sm transition-colors">
              <i class="fas fa-download mr-2"></i>Export
            </button>
          </div>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          <div class="card stat-card">
            <div class="stat-icon bg-blue-100 text-blue-600">
              <i class="fas fa-users text-xl"></i>
            </div>
            <div class="mt-2">
              <h3 class="text-3xl font-bold"><?php echo $result->num_rows; ?></h3>
              <p class="text-gray-500 text-sm">Total Users</p>
            </div>
            <div class="mt-4 flex items-center text-sm">
              <span class="text-green-500 flex items-center">
                <i class="fas fa-arrow-up mr-1"></i>12%
              </span>
              <span class="text-gray-500 ml-2">vs last week</span>
            </div>
          </div>

          <div class="card stat-card">
            <div class="stat-icon bg-green-100 text-green-600">
              <i class="fas fa-chart-line text-xl"></i>
            </div>
            <div class="mt-2">
              <h3 class="text-3xl font-bold">28</h3>
              <p class="text-gray-500 text-sm">Active Reports</p>
            </div>
            <div class="mt-4 flex items-center text-sm">
              <span class="text-green-500 flex items-center">
                <i class="fas fa-arrow-up mr-1"></i>8%
              </span>
              <span class="text-gray-500 ml-2">vs last week</span>
            </div>
          </div>

          <div class="card stat-card">
            <div class="stat-icon bg-purple-100 text-purple-600">
              <i class="fas fa-clipboard-check text-xl"></i>
            </div>
            <div class="mt-2">
              <h3 class="text-3xl font-bold">64%</h3>
              <p class="text-gray-500 text-sm">Completed Tasks</p>
            </div>
            <div class="mt-4 flex items-center text-sm">
              <span class="text-red-500 flex items-center">
                <i class="fas fa-arrow-down mr-1"></i>3%
              </span>
              <span class="text-gray-500 ml-2">vs last week</span>
            </div>
          </div>

          <div class="card stat-card">
            <div class="stat-icon bg-yellow-100 text-yellow-600">
              <i class="fas fa-star text-xl"></i>
            </div>
            <div class="mt-2">
              <h3 class="text-3xl font-bold">4.8/5</h3>
              <p class="text-gray-500 text-sm">Satisfaction</p>
            </div>
            <div class="mt-4 flex items-center text-sm">
              <span class="text-green-500 flex items-center">
                <i class="fas fa-arrow-up mr-1"></i>2%
              </span>
              <span class="text-gray-500 ml-2">vs last week</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Section -->
      <div class="mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">Analytics</h2>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
          <!-- Main Chart -->
          <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-lg">Station Cleaning Performance</h3>
              <div class="flex space-x-2">
                <button class="text-sm px-3 py-1 rounded-full bg-blue-100 text-blue-600">Daily</button>
                <button class="text-sm px-3 py-1 rounded-full text-gray-500 hover:bg-gray-100">Weekly</button>
                <button class="text-sm px-3 py-1 rounded-full text-gray-500 hover:bg-gray-100">Monthly</button>
              </div>
            </div>
            <div class="h-80">
              <canvas id="performanceChart"></canvas>
            </div>
          </div>
          
          <!-- User Distribution -->
          <div class="card p-5">
            <div class="flex items-center justify-between mb-4">
              <h3 class="font-semibold text-lg">User Distribution</h3>
              <button class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-ellipsis-h"></i>
              </button>
            </div>
            <div class="h-80">
              <canvas id="userDistributionChart"></canvas>
            </div>
          </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
          <!-- Station Compliance -->
          <div class="card p-5">
            <h3 class="font-semibold text-lg mb-4">Station Compliance</h3>
            <div class="h-60">
              <canvas id="complianceChart"></canvas>
            </div>
          </div>
          
          <!-- Task Completion -->
          <div class="card p-5">
            <h3 class="font-semibold text-lg mb-4">Task Completion</h3>
            <div class="h-60">
              <canvas id="taskCompletionChart"></canvas>
            </div>
          </div>
          
          <!-- Performance Metrics -->
          <div class="card p-5">
            <h3 class="font-semibold text-lg mb-4">Performance Metrics</h3>
            <div class="h-60">
              <canvas id="metricsChart"></canvas>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Recent Activity -->
      <div class="mb-8">
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-bold text-gray-800">Recent Activity</h2>
          <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View all</a>
        </div>
        
        <div class="card overflow-hidden">
          <div class="overflow-x-auto">
            <table class="w-full">
              <thead class="bg-gray-50">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Station</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                </tr>
              </thead>
              <tbody class="bg-white divide-y divide-gray-200">
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center mr-3">
                        <span class="font-bold text-white">J</span>
                      </div>
                      <div>
                        <p class="font-medium text-sm">John Doe</p>
                        <p class="text-xs text-gray-500">john@example.com</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">Completed cleaning audit</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">Station #4</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Completed</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">2 hours ago</td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-8 h-8 rounded-full bg-purple-500 flex items-center justify-center mr-3">
                        <span class="font-bold text-white">M</span>
                      </div>
                      <div>
                        <p class="font-medium text-sm">Mike Johnson</p>
                        <p class="text-xs text-gray-500">mike@example.com</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">Started maintenance check</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">Station #2</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">In Progress</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">5 hours ago</td>
                </tr>
                <tr>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                      <div class="w-8 h-8 rounded-full bg-pink-500 flex items-center justify-center mr-3">
                        <span class="font-bold text-white">S</span>
                      </div>
                      <div>
                        <p class="font-medium text-sm">Sarah Wilson</p>
                        <p class="text-xs text-gray-500">sarah@example.com</p>
                      </div>
                    </div>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">Submitted cleaning report</td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm">Station #5</td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">Pending Review</span>
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">1 day ago</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Set default Chart.js options
  Chart.defaults.font.family = "'Inter', sans-serif";
  Chart.defaults.color = '#6b7280';
  Chart.defaults.plugins.legend.position = 'bottom';
  
  // Station Cleaning Performance Chart (Line Chart)
  const performanceCtx = document.getElementById('performanceChart').getContext('2d');
  new Chart(performanceCtx, {
    type: 'line',
    data: {
      labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
      datasets: [
        {
          label: 'Cleanliness Score',
          data: [78, 82, 80, 85, 83, 90, 93],
          borderColor: '#3b82f6', // blue
          backgroundColor: 'rgba(59, 130, 246, 0.1)',
          tension: 0.3,
          fill: true,
          borderWidth: 3
        },
        {
          label: 'Target Score',
          data: [80, 80, 80, 80, 85, 85, 90],
          borderColor: '#ef4444', // red
          borderDash: [6, 6],
          borderWidth: 2,
          tension: 0.3,
          fill: false,
          pointRadius: 0
        }
      ]
    },
    options: {
      scales: {
        y: {
          beginAtZero: false,
          min: 70,
          max: 100
        }
      },
      plugins: {
        tooltip: {
          mode: 'index',
          intersect: false
        }
      }
    }
  });
  
  // User Distribution Chart (Doughnut)
  const userDistributionCtx = document.getElementById('userDistributionChart').getContext('2d');
  new Chart(userDistributionCtx, {
    type: 'doughnut',
    data: {
      labels: <?php echo json_encode($userTypes); ?>,
      datasets: [{
        data: <?php echo json_encode($userCounts); ?>,
        backgroundColor: <?php echo json_encode(array_slice($chartColors, 0, count($userTypes))); ?>,
        borderWidth: 0,
        borderRadius: 5,
      }]
    },
    options: {
      cutout: '65%',
      plugins: {
        legend: {
          position: 'right',
        }
      }
    }
  });
  
  // Station Compliance Chart (Horizontal Bar)
  const complianceCtx = document.getElementById('complianceChart').getContext('2d');
  new Chart(complianceCtx, {
    type: 'bar',
    data: {
      labels: ['Station 1', 'Station 2', 'Station 3', 'Station 4', 'Station 5'],
      datasets: [{
        label: 'Compliance %',
        data: [92, 87, 76, 89, 95],
        backgroundColor: [
          '#3b82f6', '#3b82f6', '#ef4444', '#3b82f6', '#3b82f6'
        ],
        borderWidth: 0,
        borderRadius: 4
      }]
    },
    options: {
      indexAxis: 'y',
      scales: {
        x: {
          beginAtZero: true,
          max: 100
        }
      },
      plugins: {
        legend: {
          display: false
        }
      }
    }
  });
  
  // Task Completion Chart (Pie)
  const taskCompletionCtx = document.getElementById('taskCompletionChart').getContext('2d');
  new Chart(taskCompletionCtx, {
    type: 'pie',
    data: {
      labels: ['Completed', 'In Progress', 'Overdue', 'Not Started'],
      datasets: [{
        data: [64, 22, 8, 6],
        backgroundColor: [
          '#10b981', '#3b82f6', '#ef4444', '#9ca3af'
        ],
        borderWidth: 0
      }]
    }
  });
  
  // Performance Metrics Chart (Radar)
  const metricsCtx = document.getElementById('metricsChart').getContext('2d');
  new Chart(metricsCtx, {
    type: 'radar',
    data: {
      labels: ['Cleaning', 'Safety', 'Organization', 'Maintenance', 'Timeliness'],
      datasets: [{
        label: 'Current',
        data: [85, 90, 78, 82, 88],
        backgroundColor: 'rgba(59, 130, 246, 0.2)',
        borderColor: '#3b82f6',
        borderWidth: 2
      }, {
        label: 'Previous Month',
        data: [80, 85, 75, 80, 84],
        backgroundColor: 'rgba(156, 163, 175, 0.2)',
        borderColor: '#9ca3af',
        borderWidth: 2
      }]
    },
    options: {
      scales: {
        r: {
          beginAtZero: true,
          max: 100,
          ticks: {
            display: false
          }
        }
      }
    }
  });
</script>

</body>
</html>