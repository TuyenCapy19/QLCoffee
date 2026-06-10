<?php
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$messageType = '';

// XỬ LÝ THÊM/SỬA/XÓA MÓN
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // THÊM MÓN MỚI
    if ($action == 'add') {
        $name = $_POST['name'] ?? '';
        $category_id = $_POST['category_id'] ?? 0;
        $price = $_POST['price'] ?? 0;
        $description = $_POST['description'] ?? '';
        $status = $_POST['status'] ?? 'available';
        
        // Upload ảnh
        $image_name = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "assets/uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $target_dir . $image_name;
            
            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                // Upload thành công
            } else {
                $image_name = '';
            }
        }
        
        try {
            $query = "INSERT INTO products (name, category_id, price, description, image, status) 
                      VALUES (:name, :category_id, :price, :description, :image, :status)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':image', $image_name);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            $message = "✅ Thêm món '$name' thành công!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "❌ Lỗi: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // SỬA MÓN
    if ($action == 'update') {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $category_id = $_POST['category_id'] ?? 0;
        $price = $_POST['price'] ?? 0;
        $description = $_POST['description'] ?? '';
        $status = $_POST['status'] ?? 'available';
        $current_image = $_POST['current_image'] ?? '';
        
        // Upload ảnh mới nếu có
        $image_name = $current_image;
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "assets/uploads/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            // Xóa ảnh cũ
            if ($current_image && file_exists($target_dir . $current_image)) {
                unlink($target_dir . $current_image);
            }
            
            $image_name = time() . '_' . basename($_FILES['image']['name']);
            $target_file = $target_dir . $image_name;
            move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
        }
        
        try {
            $query = "UPDATE products 
                      SET name = :name, category_id = :category_id, price = :price, 
                          description = :description, image = :image, status = :status 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':category_id', $category_id);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':image', $image_name);
            $stmt->bindParam(':status', $status);
            $stmt->execute();
            
            $message = "✅ Cập nhật món '$name' thành công!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "❌ Lỗi: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // XÓA MÓN
    if ($action == 'delete') {
        $id = $_POST['id'] ?? 0;
        
        try {
            // Lấy thông tin ảnh để xóa
            $stmt = $db->prepare("SELECT image, name FROM products WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Xóa ảnh
            if ($product['image'] && file_exists("assets/uploads/" . $product['image'])) {
                unlink("assets/uploads/" . $product['image']);
            }
            
            // Xóa trong database
            $stmt = $db->prepare("DELETE FROM products WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $message = "✅ Đã xóa món '{$product['name']}' thành công!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "❌ Lỗi: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// LẤY DANH SÁCH SẢN PHẨM
try {
    $query = "SELECT p.*, c.name as category_name 
              FROM products p 
              LEFT JOIN categories c ON p.category_id = c.id 
              ORDER BY c.name, p.name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy danh mục cho form
    $stmt = $db->prepare("SELECT * FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $products = [];
    $categories = [];
    $message = "❌ Lỗi database: " . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Menu - Capy Huy Coffee</title>
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
        .menu-card {
            transition: all 0.3s ease;
        }
        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
        }
        .modal {
            transition: opacity 0.3s ease;
        }
    </style>
</head>
<body class="bg-coffee-dark text-gray-300">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 border-r border-coffee-brown flex flex-col">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-coffee-gold">☕ Capy Huy</h2>
                <p class="text-gray-500 text-sm mt-1">Quản lý Menu</p>
            </div>
            
            <nav class="flex-1 px-4 space-y-1">
                <a href="../index.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📊</span>
                    <span class="ml-3">Dashboard</span>
                </a>
                <a href="menu.php" class="flex items-center px-4 py-3 bg-coffee-brown text-white rounded-lg">
                    <span class="text-xl">📝</span>
                    <span class="ml-3">Quản lý Menu</span>
                </a>
                <a href="orders.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
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
                        <p class="text-sm font-medium text-white"><?php echo $_SESSION['full_name'] ?? 'Admin'; ?></p>
                        <p class="text-xs text-gray-400"><?php echo $_SESSION['role'] ?? 'admin'; ?></p>
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
            <div class="p-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-white">📝 Quản lý Menu</h1>
                        <p class="text-gray-400 mt-1">Tổng số: <?php echo count($products); ?> món</p>
                    </div>
                    <button onclick="openAddModal()" class="bg-coffee-gold text-coffee-dark px-6 py-3 rounded-lg font-bold text-lg hover:bg-opacity-90 transition flex items-center">
                        <span class="text-xl mr-2">➕</span> Thêm món mới
                    </button>
                </div>
                
                <!-- Thông báo -->
                <?php if ($message): ?>
                <div class="<?php echo $messageType == 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white p-4 rounded-lg mb-6 flex items-center">
                    <span class="text-xl mr-3"><?php echo $messageType == 'success' ? '✅' : '❌'; ?></span>
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <!-- Lọc danh mục -->
                <div class="flex gap-2 mb-8 flex-wrap">
                    <button onclick="filterProducts('all')" class="filter-btn px-4 py-2 bg-coffee-brown text-white rounded-lg hover:bg-opacity-90 transition">
                        📋 Tất cả
                    </button>
                    <?php foreach ($categories as $cat): ?>
                    <button onclick="filterProducts('<?php echo $cat['id']; ?>')" class="filter-btn px-4 py-2 bg-gray-800 text-gray-400 rounded-lg hover:bg-gray-700 transition">
                        <?php echo $cat['name']; ?>
                    </button>
                    <?php endforeach; ?>
                </div>
                
                <!-- Grid sản phẩm -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                    <?php foreach ($products as $product): ?>
                    <div class="menu-card bg-gray-900 rounded-xl border border-coffee-brown overflow-hidden product-item" 
                         data-category="<?php echo $product['category_id']; ?>">
                        <!-- Ảnh sản phẩm -->
                        <div class="h-48 bg-gray-800 flex items-center justify-center relative overflow-hidden">
                            <?php if ($product['image'] && file_exists("assets/uploads/" . $product['image'])): ?>
                            <img src="assets/uploads/<?php echo $product['image']; ?>" 
                                 alt="<?php echo $product['name']; ?>" 
                                 class="w-full h-full object-cover">
                            <?php else: ?>
                            <span class="text-6xl">
                                <?php 
                                $icons = ['☕', '🍵', '🥤', '🍰', '🍞', '🥑', '🍑', '🧋', '🍪'];
                                echo $icons[$product['id'] % count($icons)];
                                ?>
                            </span>
                            <?php endif; ?>
                            
                            <!-- Badge trạng thái -->
                            <div class="absolute top-3 right-3">
                                <span class="px-2 py-1 rounded-full text-xs font-bold <?php echo $product['status'] == 'available' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'; ?>">
                                    <?php echo $product['status'] == 'available' ? 'Còn hàng' : 'Hết hàng'; ?>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Thông tin sản phẩm -->
                        <div class="p-4">
                            <div class="text-xs text-coffee-gold mb-1">
                                <?php echo $product['category_name'] ?? 'Chưa phân loại'; ?>
                            </div>
                            <h3 class="text-white font-bold text-lg mb-1"><?php echo $product['name']; ?></h3>
                            <p class="text-gray-400 text-sm mb-3"><?php echo $product['description'] ?? 'Chưa có mô tả'; ?></p>
                            
                            <div class="flex items-center justify-between">
                                <span class="text-coffee-gold text-2xl font-bold">
                                    <?php echo number_format($product['price'], 0, ',', '.'); ?>₫
                                </span>
                            </div>
                            
                            <!-- Nút thao tác -->
                            <div class="flex gap-2 mt-4">
                                <button onclick="editProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>', <?php echo $product['category_id']; ?>, <?php echo $product['price']; ?>, '<?php echo htmlspecialchars($product['description'] ?? '', ENT_QUOTES); ?>', '<?php echo $product['status']; ?>', '<?php echo $product['image']; ?>')" 
                                        class="flex-1 bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600 transition text-sm">
                                    ✏️ Sửa
                                </button>
                                <button onclick="deleteProduct(<?php echo $product['id']; ?>, '<?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?>')" 
                                        class="flex-1 bg-red-500 text-white py-2 rounded-lg hover:bg-red-600 transition text-sm">
                                    🗑️ Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if (count($products) == 0): ?>
                    <div class="col-span-4 text-center py-20 text-gray-500">
                        <span class="text-8xl block mb-4">📝</span>
                        <p class="text-xl">Chưa có món nào trong menu</p>
                        <button onclick="openAddModal()" class="mt-4 bg-coffee-gold text-coffee-dark px-6 py-3 rounded-lg font-bold hover:bg-opacity-90">
                            ➕ Thêm món đầu tiên
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Thêm/Sửa món -->
    <div id="productModal" class="modal hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
        <div class="bg-gray-900 rounded-xl border border-coffee-gold p-8 w-full max-w-lg mx-4">
            <h2 id="modalTitle" class="text-2xl font-bold text-white mb-6">➕ Thêm món mới</h2>
            
            <form id="productForm" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="productId">
                <input type="hidden" name="current_image" id="currentImage">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2">📝 Tên món</label>
                        <input type="text" name="name" id="productName" required 
                               class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none"
                               placeholder="VD: Cà phê sữa, Trà đào...">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">📂 Danh mục</label>
                        <select name="category_id" id="productCategory" required 
                                class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none">
                            <option value="">Chọn danh mục</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">💰 Giá (VNĐ)</label>
                        <input type="number" name="price" id="productPrice" required min="1000" step="1000"
                               class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none"
                               placeholder="VD: 25000">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">📄 Mô tả</label>
                        <textarea name="description" id="productDescription" rows="3"
                                  class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none"
                                  placeholder="Mô tả ngắn về món..."></textarea>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">📸 Hình ảnh</label>
                        <input type="file" name="image" id="productImage" accept="image/*"
                               class="w-full text-gray-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-coffee-brown file:text-white hover:file:bg-opacity-90">
                        <p class="text-gray-500 text-sm mt-1">Để trống nếu không muốn thay đổi ảnh</p>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">📊 Trạng thái</label>
                        <select name="status" id="productStatus" 
                                class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none">
                            <option value="available">🟢 Còn hàng</option>
                            <option value="unavailable">🔴 Hết hàng</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" class="flex-1 bg-coffee-gold text-coffee-dark py-3 rounded-lg font-bold hover:bg-opacity-90 transition">
                        💾 Lưu
                    </button>
                    <button type="button" onclick="closeModal()" class="flex-1 bg-gray-700 text-white py-3 rounded-lg font-bold hover:bg-gray-600 transition">
                        ❌ Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Mở modal thêm mới
        function openAddModal() {
            document.getElementById('modalTitle').textContent = '➕ Thêm món mới';
            document.getElementById('formAction').value = 'add';
            document.getElementById('productForm').reset();
            document.getElementById('productId').value = '';
            document.getElementById('currentImage').value = '';
            document.getElementById('productModal').classList.remove('hidden');
        }
        
        // Mở modal sửa
        function editProduct(id, name, categoryId, price, description, status, image) {
            document.getElementById('modalTitle').textContent = '✏️ Sửa món: ' + name;
            document.getElementById('formAction').value = 'update';
            document.getElementById('productId').value = id;
            document.getElementById('productName').value = name;
            document.getElementById('productCategory').value = categoryId;
            document.getElementById('productPrice').value = price;
            document.getElementById('productDescription').value = description;
            document.getElementById('productStatus').value = status;
            document.getElementById('currentImage').value = image;
            document.getElementById('productModal').classList.remove('hidden');
        }
        
        // Đóng modal
        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
        }
        
        // Xóa sản phẩm
        function deleteProduct(id, name) {
            if (confirm('Bạn có chắc muốn xóa món "' + name + '"?\nHành động này không thể hoàn tác!')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Lọc sản phẩm theo danh mục
        function filterProducts(categoryId) {
            document.querySelectorAll('.product-item').forEach(item => {
                if (categoryId === 'all' || item.dataset.category === categoryId) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
            
            // Cập nhật style nút filter
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.remove('bg-coffee-brown', 'text-white');
                btn.classList.add('bg-gray-800', 'text-gray-400');
            });
            event.target.classList.add('bg-coffee-brown', 'text-white');
            event.target.classList.remove('bg-gray-800', 'text-gray-400');
        }
        
        // Đóng modal khi click bên ngoài
        document.getElementById('productModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
        
        // Đóng modal với phím ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
            }
        });
    </script>
</body>
</html>