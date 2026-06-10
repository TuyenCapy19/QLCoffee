<?php
// Xác định trang hiện tại để active menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!-- Sidebar -->
<div class="w-64 bg-gray-900 border-r border-coffee-brown">
    <div class="p-6">
        <h2 class="text-2xl font-bold text-coffee-gold">☕ Capy Huy</h2>
        <p class="text-gray-500 text-sm">Hệ thống quản lý</p>
    </div>
    
    <nav class="mt-6">
        <a href="index.php" 
           class="flex items-center px-6 py-3 <?php echo $current_page == 'index.php' ? 'bg-coffee-brown text-white' : 'text-gray-400 hover:bg-gray-800'; ?>">
            <span>📊</span>
            <span class="ml-3">Dashboard</span>
        </a>
        
        <a href="menu.php" 
           class="flex items-center px-6 py-3 <?php echo $current_page == 'menu.php' ? 'bg-coffee-brown text-white' : 'text-gray-400 hover:bg-gray-800'; ?>">
            <span>📝</span>
            <span class="ml-3">Quản lý Menu</span>
        </a>
        
        <a href="orders.php" 
           class="flex items-center px-6 py-3 <?php echo $current_page == 'orders.php' ? 'bg-coffee-brown text-white' : 'text-gray-400 hover:bg-gray-800'; ?>">
            <span>🛒</span>
            <span class="ml-3">Hệ thống Order</span>
        </a>
        
        <a href="staff.php" 
           class="flex items-center px-6 py-3 <?php echo $current_page == 'staff.php' ? 'bg-coffee-brown text-white' : 'text-gray-400 hover:bg-gray-800'; ?>">
            <span>👥</span>
            <span class="ml-3">Nhân viên</span>
        </a>
        
        <a href="inventory.php" 
           class="flex items-center px-6 py-3 <?php echo $current_page == 'inventory.php' ? 'bg-coffee-brown text-white' : 'text-gray-400 hover:bg-gray-800'; ?>">
            <span>📦</span>
            <span class="ml-3">Quản lý Kho</span>
        </a>
        
        <a href="reports.php" 
           class="flex items-center px-6 py-3 <?php echo $current_page == 'reports.php' ? 'bg-coffee-brown text-white' : 'text-gray-400 hover:bg-gray-800'; ?>">
            <span>📈</span>
            <span class="ml-3">Thống kê</span>
        </a>
    </nav>
    
    <div class="absolute bottom-0 w-64 p-4 border-t border-coffee-brown">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-10 h-10 bg-coffee-gold rounded-full flex items-center justify-center text-coffee-dark font-bold">
                    <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)); ?>
                </div>
            </div>
            <div class="ml-3">
                <p class="text-sm font-medium text-white"><?php echo $_SESSION['full_name'] ?? 'User'; ?></p>
                <p class="text-xs text-gray-400"><?php echo $_SESSION['role'] ?? 'staff'; ?></p>
            </div>
        </div>
        <a href="../logout.php" class="mt-3 text-red-400 text-sm hover:text-red-300 block">🚪 Đăng xuất</a>
    </div>
</div>