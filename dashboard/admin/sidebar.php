
<aside class="w-72 bg-gray-900 text-white h-screen overflow-y-auto fixed left-0 top-0 transition-all duration-300 ease-in-out" id="sidebar">
    <!-- Logo & Brand -->
    <div class="flex items-center justify-between p-4 border-b border-gray-800">
        <div class="flex items-center space-x-3">
            <img src="https://pa.beatlebuddy.com/assets/railway_logo.jpg" alt="Logo" class="w-10 h-10 rounded-lg">
            <div>
                <h1 class="font-bold text-xl">Beatle Analytics</h1>
                <p class="text-xs text-gray-400">Station Cleaning Dashboard</p>
            </div>
        </div>
        <button id="collapse-btn" class="text-gray-400 hover:text-white md:hidden">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <!-- User Profile -->
    <div class="flex items-center p-4 border-b border-gray-800">
        <div class="w-10 h-10 rounded-full bg-blue-500 flex items-center justify-center mr-3">
            <span class="font-bold text-white">A</span>
        </div>
        <div>
            <p class="font-medium">Admin User</p>
            <p class="text-xs text-gray-400">Administrator</p>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="p-4">
        <p class="text-xs font-bold text-gray-400 uppercase mb-4 mt-2">Main Navigation</p>
        
        <a href="dashboard.php" class="flex items-center p-3 mb-2 rounded-lg bg-blue-600 text-white">
            <i class="fas fa-tachometer-alt w-5 mr-3"></i>
            <span>Dashboard</span>
        </a>
        
        <div class="mb-2">
            <button class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-800 transition-colors">
                <div class="flex items-center">
                    <i class="fas fa-building w-5 mr-3"></i>
                    <span>Divisions</span>
                </div>
                <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div class="pl-12 mt-1 space-y-1">
                <a href="create-division.php" class="block py-2 px-3 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors">
                    Create Division
                </a>
                <a href="list-division.php" class="block py-2 px-3 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors">
                    View Divisions
                </a>
            </div>
        </div>
        
        <div class="mb-2">
            <button class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-800 transition-colors">
                <div class="flex items-center">
                    <i class="fas fa-train w-5 mr-3"></i>
                    <span>Stations</span>
                </div>
                <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div class="pl-12 mt-1 space-y-1">
                <a href="create-station.php" class="block py-2 px-3 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors">
                    Create Station
                </a>
                <a href="list-station.php" class="block py-2 px-3 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors">
                    View Stations
                </a>
            </div>
        </div>
        
        <div class="mb-2">
            <button class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-800 transition-colors">
                <div class="flex items-center">
                    <i class="fas fa-sitemap w-5 mr-3"></i>
                    <span>Organizations</span>
                </div>
                <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div class="pl-12 mt-1 space-y-1">
                <a href="create-organization.php" class="block py-2 px-3 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors">
                    Create Organization
                </a>
                <a href="list-organisation.php" class="block py-2 px-3 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors">
                    View Organizations
                </a>
            </div>
        </div>
        
        <div class="mb-2">
            <button class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-800 transition-colors">
                <div class="flex items-center">
                    <i class="fas fa-user w-5 mr-3"></i>
                    <span>Users</span>
                </div>
                <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div class="pl-12 mt-1 space-y-1">
                <a href="user-list.php" class="block py-2 px-3 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors">
                    View Users
                </a>
                <a href="create-user.php" class="block py-2 px-3 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors">
                    Create User
                </a>
            </div>
        </div>
        
        <div class="mb-2">
            <button class="w-full flex items-center justify-between p-3 rounded-lg hover:bg-gray-800 transition-colors">
                <div class="flex items-center">
                    <i class="fas fa-user w-5 mr-3"></i>
                    <span>Feedback</span>
                </div>
                <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div class="pl-12 mt-1 space-y-1">
                <a href="create_feedback_station.php" class="block py-2 px-3 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors">
                    Create Feedback
                </a>
                <a href="create_feedback_questions.php" class="block py-2 px-3 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors">
                    Create Question
                </a>
                <a href="create-rating-parameters.php" class="block py-2 px-3 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors">
                    Create Parameters
                </a>
                 <a href="otp_status.php" class="block py-2 px-3 rounded-lg hover:bg-gray-800 text-gray-400 hover:text-white transition-colors">
                    Manage OTP Status
                </a>
            </div>
        </div>
        
        <p class="text-xs font-bold text-gray-400 uppercase mb-4 mt-8">Other</p>
        
        <a href="#" class="flex items-center p-3 mb-2 rounded-lg hover:bg-gray-800 transition-colors">
            <i class="fas fa-cog w-5 mr-3"></i>
            <span>Settings</span>
        </a>
        
        <a href="#" class="flex items-center p-3 mb-2 rounded-lg hover:bg-gray-800 transition-colors">
            <i class="fas fa-question-circle w-5 mr-3"></i>
            <span>Help & Support</span>
        </a>
        
        <a href="#" class="flex items-center p-3 mb-2 rounded-lg hover:bg-gray-800 transition-colors">
            <i class="fas fa-sign-out-alt w-5 mr-3"></i>
            <span>Logout</span>
        </a>
    </nav>
</aside>

<!-- Mobile menu button -->
<div class="fixed bottom-4 right-4 md:hidden z-50">
    <button id="mobile-menu-toggle" class="bg-blue-600 text-white p-3 rounded-full shadow-lg">
        <i class="fas fa-bars"></i>
    </button>
</div>

<script>
    // Handle sidebar toggle for mobile
    const mobileMenuToggle = document.getElementById('mobile-menu-toggle');
    const sidebar = document.getElementById('sidebar');
    const collapseBtn = document.getElementById('collapse-btn');
    
    mobileMenuToggle.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');
    });
    
    collapseBtn.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');
    });
    
    // Add active class to menu items when clicked
    const menuItems = document.querySelectorAll('nav a, nav button');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            // If it's a submenu button, just toggle its dropdown
            if (this.nextElementSibling && this.nextElementSibling.classList.contains('pl-12')) {
                this.nextElementSibling.classList.toggle('hidden');
                return;
            }
            
            // Otherwise, set this as active
            menuItems.forEach(el => {
                if (el.classList.contains('bg-blue-600')) {
                    el.classList.remove('bg-blue-600', 'text-white');
                    el.classList.add('hover:bg-gray-800');
                }
            });
            
            if (!this.classList.contains('block')) {
                this.classList.add('bg-blue-600', 'text-white');
                this.classList.remove('hover:bg-gray-800');
            }
        });
    });
</script>