<?php
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Xử lý tạo đơn hàng mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'create_order') {
        $table_number = $_POST['table_number'] ?? 0;
        $items = json_decode($_POST['items'] ?? '[]', true);
        $total_amount = $_POST['total_amount'] ?? 0;
        $payment_method = $_POST['payment_method'] ?? 'cash';
        
        if ($table_number > 0 && count($items) > 0) {
            try {
                // Tạo mã đơn hàng
                $order_number = 'DH' . date('Ymd') . rand(100, 999);
                
                // Insert đơn hàng
                $query = "INSERT INTO orders (order_number, table_number, total_amount, payment_method, status, staff_id) 
                          VALUES (:order_number, :table_number, :total_amount, :payment_method, 'completed', :staff_id)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':order_number', $order_number);
                $stmt->bindParam(':table_number', $table_number);
                $stmt->bindParam(':total_amount', $total_amount);
                $stmt->bindParam(':payment_method', $payment_method);
                $staff_id = $_SESSION['user_id'];
                $stmt->bindParam(':staff_id', $staff_id);
                $stmt->execute();
                
                $order_id = $db->lastInsertId();
                
                // Insert chi tiết đơn hàng
                foreach ($items as $item) {
                    $query = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                              VALUES (:order_id, :product_id, :quantity, :price)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':order_id', $order_id);
                    $stmt->bindParam(':product_id', $item['id']);
                    $stmt->bindParam(':quantity', $item['quantity']);
                    $stmt->bindParam(':price', $item['price']);
                    $stmt->execute();
                }
                
                $success_message = "✅ Đơn hàng #$order_number đã được tạo thành công!";
                
            } catch(PDOException $e) {
                $error_message = "❌ Lỗi: " . $e->getMessage();
            }
        }
    }
}

// Lấy danh sách sản phẩm từ database
$categories = [];
$products = [];

try {
    // Lấy danh mục
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy sản phẩm
    $stmt = $db->prepare("SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.status = 'available' 
                          ORDER BY c.name, p.name");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy đơn hàng gần đây
    $stmt = $db->prepare("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $error_message = "Lỗi database: " . $e->getMessage();
}

// Nhóm sản phẩm theo danh mục
$products_by_category = [];
foreach ($products as $product) {
    $cat_name = $product['category_name'] ?? 'Khác';
    $products_by_category[$cat_name][] = $product;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống Order - Capy Huy Coffee</title>
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
    <style>
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .slide-in {
            animation: slideIn 0.3s ease-out;
        }
        .fade-in {
            animation: fadeIn 0.5s ease;
        }
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(212, 165, 116, 0.2);
        }
        .quantity-badge {
            animation: pulse 0.3s ease;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }
    </style>
</head>
<body class="bg-coffee-dark text-gray-300">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 border-r border-coffee-brown flex flex-col">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-coffee-gold">☕ Capy Huy</h2>
                <p class="text-gray-500 text-sm mt-1">Hệ thống Order</p>
            </div>
            
            <nav class="flex-1 px-4 space-y-1">
                <a href="../index.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📊</span>
                    <span class="ml-3">Dashboard</span>
                </a>
                <a href="menu.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📝</span>
                    <span class="ml-3">Quản lý Menu</span>
                </a>
                <a href="orders.php" class="flex items-center px-4 py-3 bg-coffee-brown text-white rounded-lg">
                    <span class="text-xl">🛒</span>
                    <span class="ml-3">Hệ thống Order</span>
                </a>
                <a href="staff.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">👥</span>
                    <span class="ml-3">Nhân viên</span>
                </a>
                <a href="inventory.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📦</span>
                    <span class="ml-3">Quản lý Kho</span>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📈</span>
                    <span class="ml-3">Thống kê</span>
                </a>
            </nav>
            
            <div class="p-4 border-t border-coffee-brown">
                <div class="flex items-center p-3 bg-gray-800 rounded-lg">
                    <div class="w-10 h-10 bg-coffee-gold rounded-full flex items-center justify-center text-coffee-dark font-bold text-lg">
                        <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)); ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-white"><?php echo $_SESSION['full_name'] ?? 'Staff'; ?></p>
                        <p class="text-xs text-gray-400">Nhân viên</p>
                    </div>
                </div>
                <a href="logout.php" class="mt-3 flex items-center justify-center text-red-400 text-sm hover:text-red-300 transition">
                    <span>🚪</span>
                    <span class="ml-1">Đăng xuất</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col overflow-hidden">
            <!-- Header -->
            <header class="bg-gray-900 border-b border-coffee-brown p-6">
                <div class="flex items-center justify-between">
                    <h1 class="text-3xl font-bold text-white">🛒 Hệ thống Order</h1>
                    <div class="text-coffee-gold text-2xl font-bold" id="currentTime">
                        <?php echo date('H:i:s'); ?>
                    </div>
                </div>
            </header>
            
            <div class="flex-1 flex overflow-hidden">
                <!-- Menu bên trái -->
                <div class="flex-1 overflow-y-auto p-6">
                    <!-- Thông báo -->
                    <?php if (isset($success_message)): ?>
                    <div class="bg-green-500 text-white p-4 rounded-lg mb-6 fade-in">
                        <?php echo $success_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (isset($error_message)): ?>
                    <div class="bg-red-500 text-white p-4 rounded-lg mb-6">
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Chọn bàn -->
                    <div class="bg-gray-900 rounded-xl p-6 border border-coffee-brown mb-6">
                        <h2 class="text-xl font-bold text-white mb-4">🪑 Chọn bàn</h2>
                        <div class="grid grid-cols-5 sm:grid-cols-8 md:grid-cols-10 gap-3">
                            <?php for($i = 1; $i <= 20; $i++): ?>
                            <button onclick="selectTable(<?php echo $i; ?>)" 
                                    class="table-btn px-4 py-3 bg-gray-800 rounded-lg hover:bg-coffee-brown transition text-center font-semibold <?php echo $i == 1 ? 'bg-coffee-brown text-white' : 'text-gray-400'; ?>"
                                    data-table="<?php echo $i; ?>">
                                🪑<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                            </button>
                            <?php endfor; ?>
                        </div>
                        <div class="mt-4 flex items-center">
                            <span class="text-gray-400">Bàn đã chọn:</span>
                            <span class="ml-2 text-2xl font-bold text-coffee-gold" id="selectedTable">01</span>
                        </div>
                    </div>
                    
                    <!-- Danh mục filter -->
                    <div class="flex gap-2 mb-6 flex-wrap">
                        <button onclick="filterCategory('all')" class="cat-btn px-4 py-2 bg-coffee-brown text-white rounded-lg hover:bg-opacity-90 transition">
                            Tất cả
                        </button>
                        <?php foreach ($categories as $cat): ?>
                        <button onclick="filterCategory('<?php echo $cat['name']; ?>')" 
                                class="cat-btn px-4 py-2 bg-gray-800 text-gray-400 rounded-lg hover:bg-gray-700 transition">
                            <?php echo $cat['name']; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Danh sách sản phẩm -->
                    <?php foreach ($products_by_category as $cat_name => $items): ?>
                    <div class="product-category mb-8" data-category="<?php echo $cat_name; ?>">
                        <h2 class="text-2xl font-bold text-white mb-4"><?php echo $cat_name; ?></h2>
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <?php foreach ($items as $product): ?>
                            <div class="product-card bg-gray-900 rounded-xl border border-coffee-brown p-4 cursor-pointer hover:border-coffee-gold transition"
                                 onclick="addToCart(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name']); ?>', <?php echo $product['price']; ?>)">
                                <div class="flex items-start justify-between mb-3">
                                    <div>
                                        <h3 class="text-white font-semibold text-lg"><?php echo $product['name']; ?></h3>
                                        <p class="text-gray-400 text-sm mt-1"><?php echo $product['description'] ?? ''; ?></p>
                                    </div>
                                    <span class="text-3xl">☕</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-coffee-gold text-2xl font-bold">
                                        <?php echo number_format($product['price'], 0, ',', '.'); ?>₫
                                    </span>
                                    <span class="bg-coffee-brown text-white px-3 py-1 rounded-lg text-sm hover:bg-opacity-90">
                                        + Thêm
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Giỏ hàng bên phải -->
                <div class="w-96 bg-gray-900 border-l border-coffee-brown flex flex-col">
                    <div class="p-6 border-b border-coffee-brown">
                        <h2 class="text-xl font-bold text-white flex items-center">
                            <span class="text-2xl mr-2">🛒</span>
                            Giỏ hàng
                        </h2>
                        <p class="text-gray-400 text-sm mt-1" id="cartTableInfo">Bàn: 01</p>
                    </div>
                    
                    <!-- Danh sách món đã chọn -->
                    <div class="flex-1 overflow-y-auto p-6" id="cartItems">
                        <div class="text-center text-gray-500 py-20">
                            <span class="text-6xl block mb-4">🛒</span>
                            <p>Chưa có món nào</p>
                            <p class="text-sm mt-2">Chọn món từ menu bên trái</p>
                        </div>
                    </div>
                    
                    <!-- Tổng tiền và thanh toán -->
                    <div class="p-6 border-t border-coffee-brown">
                        <div class="flex justify-between mb-4">
                            <span class="text-gray-400">Tạm tính:</span>
                            <span class="text-white font-semibold" id="subtotal">0₫</span>
                        </div>
                        <div class="flex justify-between mb-4">
                            <span class="text-gray-400">Giảm giá:</span>
                            <span class="text-green-400" id="discount">0₫</span>
                        </div>
                        <div class="flex justify-between mb-6 pt-4 border-t border-coffee-brown">
                            <span class="text-xl font-bold text-white">Tổng tiền:</span>
                            <span class="text-2xl font-bold text-coffee-gold" id="total">0₫</span>
                        </div>
                        
                        <!-- Phương thức thanh toán -->
                        <div class="mb-4">
                            <label class="text-gray-400 text-sm mb-2 block">Phương thức thanh toán</label>
                            <select id="paymentMethod" class="w-full bg-gray-800 text-white px-4 py-2 rounded-lg border border-gray-700">
                                <option value="cash">💵 Tiền mặt</option>
                                <option value="card">💳 Thẻ ngân hàng</option>
                                <option value="transfer">📱 Chuyển khoản</option>
                            </select>
                        </div>
                        
                        <!-- Nút thanh toán -->
                        <button onclick="checkout()" 
                                class="w-full bg-coffee-gold text-coffee-dark py-3 rounded-lg font-bold text-lg hover:bg-opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed"
                                id="checkoutBtn" disabled>
                            💰 Thanh toán
                        </button>
                        
                        <button onclick="clearCart()" 
                                class="w-full mt-2 bg-gray-800 text-gray-400 py-2 rounded-lg hover:bg-gray-700 transition">
                            🗑️ Xóa giỏ hàng
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal in hóa đơn -->
    <div id="receiptModal" class="hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
        <div class="bg-gray-900 p-8 rounded-xl border border-coffee-gold max-w-md w-full slide-in">
            <div class="text-center mb-6">
                <h2 class="text-3xl font-bold text-coffee-gold mb-2">☕ Capy Huy Coffee</h2>
                <p class="text-gray-400">Hóa đơn thanh toán</p>
            </div>
            
            <div id="receiptContent" class="mb-6">
                <!-- Nội dung hóa đơn sẽ được điền bằng JavaScript -->
            </div>
            
            <div class="text-center border-t border-gray-700 pt-6">
                <p class="text-gray-400 mb-4">Cảm ơn quý khách!</p>
                <div class="flex gap-3">
                    <button onclick="printReceipt()" class="flex-1 bg-coffee-gold text-coffee-dark py-2 rounded-lg font-semibold hover:bg-opacity-90">
                        🖨️ In hóa đơn
                    </button>
                    <button onclick="closeReceipt()" class="flex-1 bg-gray-800 text-white py-2 rounded-lg hover:bg-gray-700">
                        Đóng
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Biến toàn cục
        let selectedTable = 1;
        let cart = [];
        
        // Cập nhật đồng hồ
        function updateClock() {
            const now = new Date();
            document.getElementById('currentTime').textContent = 
                now.toLocaleTimeString('vi-VN', { hour12: false });
        }
        setInterval(updateClock, 1000);
        
        // Chọn bàn
        function selectTable(tableNum) {
            selectedTable = tableNum;
            document.getElementById('selectedTable').textContent = 
                String(tableNum).padStart(2, '0');
            document.getElementById('cartTableInfo').textContent = 
                'Bàn: ' + String(tableNum).padStart(2, '0');
            
            // Cập nhật style các nút bàn
            document.querySelectorAll('.table-btn').forEach(btn => {
                if (parseInt(btn.dataset.table) === tableNum) {
                    btn.classList.add('bg-coffee-brown', 'text-white');
                    btn.classList.remove('text-gray-400');
                } else {
                    btn.classList.remove('bg-coffee-brown', 'text-white');
                    btn.classList.add('text-gray-400');
                }
            });
        }
        
        // Filter danh mục
        function filterCategory(category) {
            document.querySelectorAll('.product-category').forEach(cat => {
                if (category === 'all' || cat.dataset.category === category) {
                    cat.style.display = 'block';
                } else {
                    cat.style.display = 'none';
                }
            });
            
            // Cập nhật style buttons
            document.querySelectorAll('.cat-btn').forEach(btn => {
                if (btn.textContent.trim() === category || (category === 'all' && btn.textContent.trim() === 'Tất cả')) {
                    btn.classList.add('bg-coffee-brown', 'text-white');
                    btn.classList.remove('bg-gray-800', 'text-gray-400');
                } else {
                    btn.classList.remove('bg-coffee-brown', 'text-white');
                    btn.classList.add('bg-gray-800', 'text-gray-400');
                }
            });
        }
        
        // Thêm vào giỏ hàng
        function addToCart(id, name, price) {
            const existingItem = cart.find(item => item.id === id);
            
            if (existingItem) {
                existingItem.quantity++;
            } else {
                cart.push({
                    id: id,
                    name: name,
                    price: price,
                    quantity: 1
                });
            }
            
            updateCart();
            
            // Animation feedback
            const feedback = document.createElement('div');
            feedback.className = 'fixed top-20 right-96 bg-coffee-gold text-coffee-dark px-4 py-2 rounded-lg quantity-badge z-50';
            feedback.textContent = '✅ Đã thêm ' + name;
            document.body.appendChild(feedback);
            setTimeout(() => feedback.remove(), 2000);
        }
        
        // Cập nhật giỏ hàng
        function updateCart() {
            const cartItems = document.getElementById('cartItems');
            const subtotalEl = document.getElementById('subtotal');
            const totalEl = document.getElementById('total');
            const checkoutBtn = document.getElementById('checkoutBtn');
            
            if (cart.length === 0) {
                cartItems.innerHTML = `
                    <div class="text-center text-gray-500 py-20">
                        <span class="text-6xl block mb-4">🛒</span>
                        <p>Chưa có món nào</p>
                        <p class="text-sm mt-2">Chọn món từ menu bên trái</p>
                    </div>
                `;
                checkoutBtn.disabled = true;
            } else {
                let html = '<div class="space-y-3">';
                let subtotal = 0;
                
                cart.forEach((item, index) => {
                    const itemTotal = item.price * item.quantity;
                    subtotal += itemTotal;
                    
                    html += `
                        <div class="bg-gray-800 p-4 rounded-lg">
                            <div class="flex justify-between items-start mb-2">
                                <div class="flex-1">
                                    <h4 class="text-white font-semibold">${item.name}</h4>
                                    <p class="text-coffee-gold text-sm">${item.price.toLocaleString('vi-VN')}₫</p>
                                </div>
                                <button onclick="removeFromCart(${index})" class="text-red-400 hover:text-red-300 ml-2">
                                    ❌
                                </button>
                            </div>
                            <div class="flex items-center justify-between mt-3">
                                <div class="flex items-center space-x-2">
                                    <button onclick="updateQuantity(${index}, -1)" 
                                            class="w-8 h-8 bg-gray-700 rounded-lg hover:bg-gray-600 text-white flex items-center justify-center">
                                        ➖
                                    </button>
                                    <span class="text-white font-bold w-8 text-center">${item.quantity}</span>
                                    <button onclick="updateQuantity(${index}, 1)" 
                                            class="w-8 h-8 bg-gray-700 rounded-lg hover:bg-gray-600 text-white flex items-center justify-center">
                                        ➕
                                    </button>
                                </div>
                                <span class="text-coffee-gold font-bold">${itemTotal.toLocaleString('vi-VN')}₫</span>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                cartItems.innerHTML = html;
                
                subtotalEl.textContent = subtotal.toLocaleString('vi-VN') + '₫';
                totalEl.textContent = subtotal.toLocaleString('vi-VN') + '₫';
                checkoutBtn.disabled = false;
            }
        }
        
        // Cập nhật số lượng
        function updateQuantity(index, change) {
            cart[index].quantity += change;
            
            if (cart[index].quantity <= 0) {
                cart.splice(index, 1);
            }
            
            updateCart();
        }
        
        // Xóa món khỏi giỏ
        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCart();
        }
        
        // Xóa toàn bộ giỏ hàng
        function clearCart() {
            if (confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) {
                cart = [];
                updateCart();
            }
        }
        
        // Thanh toán
        function checkout() {
            if (cart.length === 0) {
                alert('Vui lòng chọn món trước khi thanh toán!');
                return;
            }
            
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            const paymentMethod = document.getElementById('paymentMethod').value;
            
            // Hiển thị hóa đơn
            const receiptContent = document.getElementById('receiptContent');
            let receiptHTML = `
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-400">Bàn:</span>
                        <span class="text-white">${String(selectedTable).padStart(2, '0')}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Thời gian:</span>
                        <span class="text-white">${new Date().toLocaleString('vi-VN')}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-400">Nhân viên:</span>
                        <span class="text-white"><?php echo $_SESSION['full_name'] ?? 'Staff'; ?></span>
                    </div>
                    <div class="border-t border-gray-700 my-3"></div>
            `;
            
            cart.forEach(item => {
                receiptHTML += `
                    <div class="flex justify-between">
                        <span class="text-white">${item.name} x${item.quantity}</span>
                        <span class="text-coffee-gold">${(item.price * item.quantity).toLocaleString('vi-VN')}₫</span>
                    </div>
                `;
            });
            
            receiptHTML += `
                    <div class="border-t border-gray-700 my-3"></div>
                    <div class="flex justify-between text-lg font-bold">
                        <span class="text-white">Tổng tiền:</span>
                        <span class="text-coffee-gold">${total.toLocaleString('vi-VN')}₫</span>
                    </div>
                    <div class="flex justify-between text-sm mt-2">
                        <span class="text-gray-400">Thanh toán:</span>
                        <span class="text-white">${paymentMethod === 'cash' ? 'Tiền mặt' : paymentMethod === 'card' ? 'Thẻ' : 'Chuyển khoản'}</span>
                    </div>
                </div>
            `;
            
            receiptContent.innerHTML = receiptHTML;
            document.getElementById('receiptModal').classList.remove('hidden');
            
            // Lưu đơn hàng vào database
            saveOrder(total, paymentMethod);
        }
        
        // Lưu đơn hàng
        function saveOrder(total, paymentMethod) {
            const formData = new FormData();
            formData.append('action', 'create_order');
            formData.append('table_number', selectedTable);
            formData.append('items', JSON.stringify(cart));
            formData.append('total_amount', total);
            formData.append('payment_method', paymentMethod);
            
            fetch('orders.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                console.log('Đơn hàng đã lưu');
            })
            .catch(error => {
                console.error('Lỗi:', error);
            });
            
            // Xóa giỏ hàng
            cart = [];
            updateCart();
        }
        
        // Đóng hóa đơn
        function closeReceipt() {
            document.getElementById('receiptModal').classList.add('hidden');
        }
        
        // In hóa đơn
        function printReceipt() {
            const receiptContent = document.getElementById('receiptContent').innerHTML;
            const printWindow = window.open('', '', 'width=400,height=600');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Hóa đơn Capy Huy Coffee</title>
                    <style>
                        body { font-family: 'Segoe UI', sans-serif; padding: 20px; }
                        h2 { text-align: center; color: #3e2723; }
                    </style>
                </head>
                <body>
                    <h2>☕ Capy Huy Coffee</h2>
                    ${receiptContent}
                    <p style="text-align: center; margin-top: 20px;">Cảm ơn quý khách!</p>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }
        
        // Khởi tạo
        document.addEventListener('DOMContentLoaded', function() {
            updateCart();
            selectTable(1);
        });
    </script>
</body>
</html>