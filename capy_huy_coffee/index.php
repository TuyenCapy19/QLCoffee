<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Lấy thống kê
try {
    // Tổng số users
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    $stmt->execute();
    $active_users = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Tổng số sản phẩm
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM products WHERE status = 'available'");
    $stmt->execute();
    $total_products = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

    // Số lượng cảnh báo kho
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM inventory WHERE quantity <= min_quantity");
    $stmt->execute();
    $low_inventory = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

} catch(PDOException $e) {
    $active_users = 0;
    $total_products = 0;
    $low_inventory = 0;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capy Huy Coffee - Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'coffee-dark': '#1a1410',
                        'coffee-brown': '#3e2723',
                        'coffee-gold': '#d4a574',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-coffee-dark text-gray-300">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 border-r border-coffee-brown flex flex-col">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-coffee-gold">☕ Capy Huy</h2>
                <p class="text-gray-500 text-sm mt-1">Hệ thống quản lý</p>
            </div>
            
            <nav class="flex-1 px-4 space-y-1">
                <a href="index.php" class="flex items-center px-4 py-3 bg-coffee-brown text-white rounded-lg">
                    <span class="text-xl">📊</span>
                    <span class="ml-3">Dashboard</span>
                </a>
                <a href="pages/menu.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📝</span>
                    <span class="ml-3">Quản lý Menu</span>
                </a>
                <a href="pages/orders.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">🛒</span>
                    <span class="ml-3">Hệ thống Order</span>
                </a>
                <a href="pages/staff.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">👥</span>
                    <span class="ml-3">Nhân viên</span>
                </a>
                <a href="pages/inventory.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📦</span>
                    <span class="ml-3">Quản lý Kho</span>
                </a>
                <a href="pages/reports.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📈</span>
                    <span class="ml-3">Thống kê</span>
                </a>
            </nav>
            
            <!-- User Info -->
            <div class="p-4 border-t border-coffee-brown">
                <div class="flex items-center p-3 bg-gray-800 rounded-lg">
                    <div class="w-10 h-10 bg-coffee-gold rounded-full flex items-center justify-center text-coffee-dark font-bold text-lg">
                        <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)); ?>
                    </div>
                    <div class="ml-3 flex-1">
                        <p class="text-sm font-medium text-white"><?php echo $_SESSION['full_name'] ?? 'Admin'; ?></p>
                        <p class="text-xs text-gray-400 capitalize"><?php echo $_SESSION['role'] ?? 'admin'; ?></p>
                    </div>
                </div>
                <a href="logout.php" class="mt-3 flex items-center justify-center text-red-400 text-sm hover:text-red-300 transition">
                    <span>🚪</span>
                    <span class="ml-1">Đăng xuất</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-gray-900 border-b border-coffee-brown p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-white">📊 Dashboard</h1>
                        <p class="text-gray-400 mt-1">Chào mừng trở lại, <?php echo $_SESSION['full_name'] ?? 'Admin'; ?>!</p>
                    </div>
                    <div class="text-right">
                        <p class="text-gray-400 text-sm">Hôm nay</p>
                        <p class="text-white text-lg font-semibold"><?php echo date('d/m/Y'); ?></p>
                    </div>
                </div>
            </header>
            
            <div class="p-8">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Card 1: Nhân viên -->
                    <div class="bg-gray-900 rounded-xl p-6 border border-coffee-brown hover:border-coffee-gold transition">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-blue-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                <span class="text-2xl">👥</span>
                            </div>
                            <span class="text-xs font-semibold px-2 py-1 bg-blue-500 bg-opacity-20 text-blue-400 rounded-full">Active</span>
                        </div>
                        <h3 class="text-gray-400 text-sm mb-1">Nhân viên đang làm</h3>
                        <p class="text-3xl font-bold text-white"><?php echo $active_users; ?></p>
                        <p class="text-gray-500 text-sm mt-2">Tổng số nhân viên active</p>
                    </div>
                    
                    <!-- Card 2: Sản phẩm -->
                    <div class="bg-gray-900 rounded-xl p-6 border border-coffee-brown hover:border-coffee-gold transition">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-green-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                <span class="text-2xl">📝</span>
                            </div>
                            <span class="text-xs font-semibold px-2 py-1 bg-green-500 bg-opacity-20 text-green-400 rounded-full">Available</span>
                        </div>
                        <h3 class="text-gray-400 text-sm mb-1">Sản phẩm trong menu</h3>
                        <p class="text-3xl font-bold text-white"><?php echo $total_products; ?></p>
                        <p class="text-gray-500 text-sm mt-2">Sản phẩm đang bán</p>
                    </div>
                    
                    <!-- Card 3: Cảnh báo kho -->
                    <div class="bg-gray-900 rounded-xl p-6 border border-coffee-brown hover:border-coffee-gold transition">
                        <div class="flex items-center justify-between mb-4">
                            <div class="w-12 h-12 bg-red-500 bg-opacity-20 rounded-lg flex items-center justify-center">
                                <span class="text-2xl">⚠️</span>
                            </div>
                            <span class="text-xs font-semibold px-2 py-1 bg-red-500 bg-opacity-20 text-red-400 rounded-full">Cảnh báo</span>
                        </div>
                        <h3 class="text-gray-400 text-sm mb-1">Cảnh báo tồn kho</h3>
                        <p class="text-3xl font-bold text-white"><?php echo $low_inventory; ?></p>
                        <p class="text-gray-500 text-sm mt-2">Nguyên liệu sắp hết</p>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-900 rounded-xl p-6 border border-coffee-brown">
                        <h2 class="text-xl font-bold text-white mb-4">🚀 Truy cập nhanh</h2>
                        <div class="grid grid-cols-2 gap-4">
                            <a href="pages/menu.php" class="p-4 bg-gray-800 rounded-lg hover:bg-gray-700 transition text-center">
                                <span class="text-3xl block mb-2">📝</span>
                                <span class="text-white text-sm">Quản lý Menu</span>
                            </a>
                            <a href="pages/orders.php" class="p-4 bg-gray-800 rounded-lg hover:bg-gray-700 transition text-center">
                                <span class="text-3xl block mb-2">🛒</span>
                                <span class="text-white text-sm">Tạo đơn hàng</span>
                            </a>
                            <a href="pages/staff.php" class="p-4 bg-gray-800 rounded-lg hover:bg-gray-700 transition text-center">
                                <span class="text-3xl block mb-2">👥</span>
                                <span class="text-white text-sm">Nhân viên</span>
                            </a>
                            <a href="pages/inventory.php" class="p-4 bg-gray-800 rounded-lg hover:bg-gray-700 transition text-center">
                                <span class="text-3xl block mb-2">📦</span>
                                <span class="text-white text-sm">Kho hàng</span>
                            </a>
                        </div>
                    </div>
                    
                    <div class="bg-gray-900 rounded-xl p-6 border border-coffee-brown">
                        <h2 class="text-xl font-bold text-white mb-4">💡 Thông tin hệ thống</h2>
                        <div class="space-y-3">
                            <div class="flex justify-between py-2 border-b border-gray-800">
                                <span class="text-gray-400">Phiên bản</span>
                                <span class="text-white">1.0.0</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-800">
                                <span class="text-gray-400">Database</span>
                                <span class="text-green-400">Đã kết nối</span>
                            </div>
                            <div class="flex justify-between py-2 border-b border-gray-800">
                                <span class="text-gray-400">PHP Version</span>
                                <span class="text-white"><?php echo phpversion(); ?></span>
                            </div>
                            <div class="flex justify-between py-2">
                                <span class="text-gray-400">Người dùng</span>
                                <span class="text-coffee-gold"><?php echo $_SESSION['full_name'] ?? 'Admin'; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Welcome Message -->
                <div class="bg-gradient-to-r from-coffee-brown to-gray-900 rounded-xl p-8 border border-coffee-gold">
                    <div class="flex items-center">
                        <div class="text-6xl mr-6">☕</div>
                        <div>
                            <h2 class="text-2xl font-bold text-white mb-2">
                                Chào mừng đến với Capy Huy Coffee!
                            </h2>
                            <p class="text-gray-300">
                                Hệ thống quản lý chuyên nghiệp giúp bạn dễ dàng kiểm soát 
                                menu, đơn hàng, nhân viên và kho hàng.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>