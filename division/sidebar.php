<!-- Sidebar -->
<div class="sidebar-wrapper">
  <!-- Mobile sidebar overlay -->
  <div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-20 hidden transition-opacity duration-300">
  </div>

  <!-- Actual sidebar -->
  <aside
    class="fixed w-64 bg-gray-900 text-white h-screen overflow-y-auto left-0 top-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out shadow-xl z-30"
    id="sidebar">
    <!-- IR Logo Header -->
    <div class="py-4 px-4 bg-blue-800 border-b border-blue-700">
      <div class="flex items-center justify-between">
        <div class="flex items-center space-x-3">
          <div class="bg-white p-1 rounded">
            <img src="assets/image/tarin_logo.png" alt="IR" class="w-8 h-8">
          </div>
          <div>
            <h1 class="font-bold">Indian Railways</h1>
            <p class="text-xs text-blue-200">Division Manager Portal</p>
          </div>
        </div>
        <button id="close-sidebar" class="text-white hover:text-blue-200 md:hidden">
          <i class="fas fa-times"></i>
        </button>
      </div>
    </div>

    <!-- User Profile -->
    <!-- <div class="px-4 py-3 border-b border-gray-800">
      <div class="flex items-center">
        <div class="w-10 h-10 rounded-full bg-blue-600 flex items-center justify-center mr-3">
          <span class="font-bold text-white">DM</span>
        </div>
        <div>
          <p class="font-medium">Division Manager</p>
          <p class="text-xs text-gray-400">Western Railways</p>
        </div>
      </div>
    </div> -->

    <!-- Navigation -->
    <nav class="p-3">
      <!-- Main menu items -->
      <div class="space-y-1">
        <a href="dashboard.php" class="flex items-center px-3 py-2 rounded bg-blue-700 text-white">
          <i class="fas fa-tachometer-alt w-5 mr-3"></i>
          <span>Dashboard</span>
        </a>

        <a href="list-station.php"
          class="flex items-center px-3 py-2 rounded hover:bg-blue-700 text-gray-300 hover:text-white transition-colors">
          <i class="fas fa-building w-5 mr-3"></i>
          <span>Stations Login</span>
        </a>

        <a href="create-chi.php"
          class="flex items-center px-3 py-2 rounded hover:bg-blue-700 text-gray-300 hover:text-white transition-colors">
          <i class="fas fa-tachometer-alt w-5 mr-3"></i>
          <span>Create CHI Dashboard</span>
        </a>
        <a href="view-edit-chi.php"
          class="flex items-center px-3 py-2 rounded hover:bg-blue-700 text-gray-300 hover:text-white transition-colors">
          <i class="fas fa-tachometer-alt w-5 mr-3"></i>
          <span>View/Edit CHI Dashboard</span>
        </a>

        <a href="create-contractor.php"
          class="flex items-center px-3 py-2 rounded hover:bg-blue-700 text-gray-300 hover:text-white transition-colors">
          <i class="fas fa-users w-5 mr-3"></i>
          <span>Create Contractor</span>
        </a>
             <a href="view-edit-contractor.php"
          class="flex items-center px-3 py-2 rounded hover:bg-blue-700 text-gray-300 hover:text-white transition-colors">
          <i class="fas fa-user-edit w-5 mr-3"></i>
          <span>View/Edit Contractor</span>
        </a>  


        <a href="create-auditor.php"
          class="flex items-center px-3 py-2 rounded hover:bg-blue-700 text-gray-300 hover:text-white transition-colors">
          <i class="fas fa-user-check w-5 mr-3"></i>
          <span>Create Auditor</span>
        </a>

        <!-- view and edit -->
        <a href="view-edit-auditor.php"
          class="flex items-center px-3 py-2 rounded hover:bg-blue-700 text-gray-300 hover:text-white transition-colors">
          <i class="fas fa-user-edit w-5 mr-3"></i>
          <span>View/Edit Auditor</span>
        </a>
         
   

  

        <!--<a href="reports.php" class="flex items-center px-3 py-2 rounded hover:bg-gray-800 text-gray-300 hover:text-white">-->
        <!--  <i class="fas fa-chart-line w-5 mr-3"></i>-->
        <!--  <span>Reports</span>-->
        <!--</a>-->
      </div>

      <!-- Divider -->
      <div class="my-4 border-t border-gray-800"></div>

      <!-- System menu -->
      <!--<div class="space-y-1">-->
      <!--  <a href="alerts.php" class="flex items-center justify-between px-3 py-2 rounded hover:bg-gray-800 text-gray-300 hover:text-white">-->
      <!--    <div class="flex items-center">-->
      <!--      <i class="fas fa-bell w-5 mr-3"></i>-->
      <!--      <span>Alerts</span>-->
      <!--    </div>-->
      <!--    <span class="bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">3</span>-->
      <!--  </a>-->

      <!--  <a href="settings.php" class="flex items-center px-3 py-2 rounded hover:bg-gray-800 text-gray-300 hover:text-white">-->
      <!--    <i class="fas fa-cog w-5 mr-3"></i>-->
      <!--    <span>Settings</span>-->
      <!--  </a>-->

      <!--  <a href="help.php" class="flex items-center px-3 py-2 rounded hover:bg-gray-800 text-gray-300 hover:text-white">-->
      <!--    <i class="fas fa-question-circle w-5 mr-3"></i>-->
      <!--    <span>Help</span>-->
      <!--  </a>-->
      <!--</div>-->
    </nav>

    <!-- Logout button at bottom -->
    <div class="absolute bottom-0 left-0 right-0 p-4 border-t border-gray-800">
      <a href="logout.php"
        class="flex items-center justify-center bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded transition-colors">
        <i class="fas fa-sign-out-alt mr-2"></i>
        <span>Logout</span>
      </a>
    </div>
  </aside>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    // Get DOM elements
    const sidebar = document.getElementById('sidebar');
    const sidebarOverlay = document.getElementById('sidebar-overlay');
    const closeSidebarBtn = document.getElementById('close-sidebar');

    // Function to toggle sidebar on mobile
    function toggleSidebar() {
      sidebar.classList.toggle('-translate-x-full');
      sidebarOverlay.classList.toggle('hidden');

      if (!sidebar.classList.contains('-translate-x-full')) {
        document.body.classList.add('overflow-hidden');
      } else {
        document.body.classList.remove('overflow-hidden');
      }
    }

    // Event listeners
    if (closeSidebarBtn) {
      closeSidebarBtn.addEventListener('click', toggleSidebar);
    }

    if (sidebarOverlay) {
      sidebarOverlay.addEventListener('click', toggleSidebar);
    }

    // Expose toggle function for dashboard.php
    window.toggleSidebar = toggleSidebar;

    // Set active menu item based on current page
    const menuItems = document.querySelectorAll('nav a');
    const currentPage = window.location.pathname.split('/').pop();

    menuItems.forEach(item => {
      if (item.getAttribute('href') === currentPage) {
        item.classList.remove('text-gray-300', 'hover:bg-gray-800');
        item.classList.add('bg-blue-700', 'text-white');
      }
    });

    // Handle window resize
    window.addEventListener('resize', function () {
      if (window.innerWidth >= 768) {
        sidebar.classList.remove('-translate-x-full');
        sidebarOverlay.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
      } else if (!sidebarOverlay.classList.contains('hidden')) {
        // Don't auto-close on resize if it was explicitly opened
      } else {
        sidebar.classList.add('-translate-x-full');
      }
    });

    // Initialize correct state based on screen size
    if (window.innerWidth < 768) {
      sidebar.classList.add('-translate-x-full');
    }
  });
</script>