<?php
require_once '../config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$messageType = '';

// XỬ LÝ FORM
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // THÊM NGUYÊN LIỆU MỚI
    if ($action == 'add') {
        $item_name = $_POST['item_name'] ?? '';
        $category = $_POST['category'] ?? '';
        $quantity = $_POST['quantity'] ?? 0;
        $unit = $_POST['unit'] ?? '';
        $min_quantity = $_POST['min_quantity'] ?? 0;
        $supplier = $_POST['supplier'] ?? '';
        $last_restock = $_POST['last_restock'] ?? date('Y-m-d');
        
        try {
            $query = "INSERT INTO inventory (item_name, category, quantity, unit, min_quantity, supplier, last_restock) 
                      VALUES (:item_name, :category, :quantity, :unit, :min_quantity, :supplier, :last_restock)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':item_name', $item_name);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':quantity', $quantity);
            $stmt->bindParam(':unit', $unit);
            $stmt->bindParam(':min_quantity', $min_quantity);
            $stmt->bindParam(':supplier', $supplier);
            $stmt->bindParam(':last_restock', $last_restock);
            $stmt->execute();
            
            $message = "✅ Thêm '$item_name' vào kho thành công!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "❌ Lỗi: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // NHẬP KHO (Tăng số lượng)
    if ($action == 'import') {
        $id = $_POST['id'] ?? 0;
        $import_quantity = $_POST['import_quantity'] ?? 0;
        $item_name = $_POST['item_name'] ?? '';
        
        try {
            $query = "UPDATE inventory SET quantity = quantity + :import_quantity, last_restock = :last_restock WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':import_quantity', $import_quantity);
            $last_restock = date('Y-m-d');
            $stmt->bindParam(':last_restock', $last_restock);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $message = "✅ Nhập thêm $import_quantity đơn vị cho '$item_name'!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "❌ Lỗi: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // XUẤT KHO (Giảm số lượng)
    if ($action == 'export') {
        $id = $_POST['id'] ?? 0;
        $export_quantity = $_POST['export_quantity'] ?? 0;
        $item_name = $_POST['item_name'] ?? '';
        
        // Kiểm tra số lượng tồn kho
        $stmt = $db->prepare("SELECT quantity FROM inventory WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $current = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($current['quantity'] >= $export_quantity) {
            try {
                $query = "UPDATE inventory SET quantity = quantity - :export_quantity WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':export_quantity', $export_quantity);
                $stmt->bindParam(':id', $id);
                $stmt->execute();
                
                $message = "✅ Xuất $export_quantity đơn vị từ '$item_name'!";
                $messageType = 'success';
            } catch(PDOException $e) {
                $message = "❌ Lỗi: " . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = "❌ Không đủ số lượng! Tồn kho chỉ còn {$current['quantity']}";
            $messageType = 'error';
        }
    }
    
    // SỬA THÔNG TIN
    if ($action == 'update') {
        $id = $_POST['id'] ?? 0;
        $item_name = $_POST['item_name'] ?? '';
        $category = $_POST['category'] ?? '';
        $unit = $_POST['unit'] ?? '';
        $min_quantity = $_POST['min_quantity'] ?? 0;
        $supplier = $_POST['supplier'] ?? '';
        
        try {
            $query = "UPDATE inventory SET item_name = :item_name, category = :category, unit = :unit, 
                      min_quantity = :min_quantity, supplier = :supplier WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':item_name', $item_name);
            $stmt->bindParam(':category', $category);
            $stmt->bindParam(':unit', $unit);
            $stmt->bindParam(':min_quantity', $min_quantity);
            $stmt->bindParam(':supplier', $supplier);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $message = "✅ Cập nhật '$item_name' thành công!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "❌ Lỗi: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // XÓA
    if ($action == 'delete') {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        
        try {
            $stmt = $db->prepare("DELETE FROM inventory WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $message = "✅ Đã xóa '$name' khỏi kho!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "❌ Lỗi: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// LẤY DANH SÁCH KHO
$inventory_items = [];
$alerts = [];
$stats = ['total' => 0, 'low_stock' => 0, 'out_of_stock' => 0, 'total_value' => 0];

try {
    $stmt = $db->prepare("SELECT * FROM inventory ORDER BY category, item_name");
    $stmt->execute();
    $inventory_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($inventory_items as $item) {
        $stats['total']++;
        if ($item['quantity'] <= 0) {
            $stats['out_of_stock']++;
            $alerts[] = "🔴 {$item['item_name']} - HẾT HÀNG!";
        } elseif ($item['quantity'] <= $item['min_quantity']) {
            $stats['low_stock']++;
            $alerts[] = "🟡 {$item['item_name']} - Sắp hết (còn {$item['quantity']} {$item['unit']})";
        }
    }
} catch(PDOException $e) {
    $message = "❌ Lỗi database: " . $e->getMessage();
    $messageType = 'error';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Kho - Capy Huy Coffee</title>
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
        .progress-bar {
            transition: width 0.5s ease;
        }
    </style>
</head>
<body class="bg-coffee-dark text-gray-300">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 border-r border-coffee-brown flex flex-col">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-coffee-gold">☕ Capy Huy</h2>
                <p class="text-gray-500 text-sm mt-1">Quản lý Kho</p>
            </div>
            
            <nav class="flex-1 px-4 space-y-1">
                <a href="../index.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📊</span><span class="ml-3">Dashboard</span>
                </a>
                <a href="menu.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📝</span><span class="ml-3">Quản lý Menu</span>
                </a>
                <a href="orders.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">🛒</span><span class="ml-3">Hệ thống Order</span>
                </a>
                <a href="staff.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">👥</span><span class="ml-3">Nhân viên</span>
                </a>
                <a href="inventory.php" class="flex items-center px-4 py-3 bg-coffee-brown text-white rounded-lg">
                    <span class="text-xl">📦</span><span class="ml-3">Quản lý Kho</span>
                </a>
                <a href="reports.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
                    <span class="text-xl">📈</span><span class="ml-3">Thống kê</span>
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
                <a href="../logout.php" class="mt-3 flex items-center justify-center text-red-400 text-sm hover:text-red-300 transition">
                    <span>🚪</span><span class="ml-1">Đăng xuất</span>
                </a>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 overflow-y-auto">
            <div class="p-8">
                <!-- Header -->
                <div class="flex items-center justify-between mb-8">
                    <div>
                        <h1 class="text-3xl font-bold text-white">📦 Quản lý Kho</h1>
                        <p class="text-gray-400 mt-1">Tổng: <?php echo $stats['total']; ?> mặt hàng</p>
                    </div>
                    <button onclick="openAddModal()" class="bg-coffee-gold text-coffee-dark px-6 py-3 rounded-lg font-bold text-lg hover:bg-opacity-90 transition flex items-center">
                        <span class="text-xl mr-2">➕</span> Thêm nguyên liệu
                    </button>
                </div>
                
                <!-- Thông báo -->
                <?php if ($message): ?>
                <div class="<?php echo $messageType == 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white p-4 rounded-lg mb-6">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <!-- Cảnh báo -->
                <?php if (count($alerts) > 0): ?>
                <div class="bg-red-900 bg-opacity-50 border border-red-500 rounded-lg p-4 mb-6">
                    <h3 class="text-red-400 font-bold text-lg mb-2">⚠️ Cảnh báo tồn kho (<?php echo count($alerts); ?>)</h3>
                    <ul class="space-y-1">
                        <?php foreach ($alerts as $alert): ?>
                        <li class="text-red-300"><?php echo $alert; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                
                <!-- Stats -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-gray-900 rounded-xl p-6 border border-coffee-brown">
                        <p class="text-gray-400 text-sm">Tổng mặt hàng</p>
                        <p class="text-3xl font-bold text-white mt-2"><?php echo $stats['total']; ?></p>
                    </div>
                    <div class="bg-gray-900 rounded-xl p-6 border border-coffee-brown">
                        <p class="text-gray-400 text-sm">Sắp hết</p>
                        <p class="text-3xl font-bold text-yellow-400 mt-2"><?php echo $stats['low_stock']; ?></p>
                    </div>
                    <div class="bg-gray-900 rounded-xl p-6 border border-coffee-brown">
                        <p class="text-gray-400 text-sm">Hết hàng</p>
                        <p class="text-3xl font-bold text-red-400 mt-2"><?php echo $stats['out_of_stock']; ?></p>
                    </div>
                    <div class="bg-gray-900 rounded-xl p-6 border border-coffee-brown">
                        <p class="text-gray-400 text-sm">Cần nhập gấp</p>
                        <p class="text-3xl font-bold text-orange-400 mt-2"><?php echo $stats['low_stock'] + $stats['out_of_stock']; ?></p>
                    </div>
                </div>
                
                <!-- Danh sách kho -->
                <div class="bg-gray-900 rounded-xl border border-coffee-brown overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-coffee-brown bg-gray-800">
                                    <th class="py-4 px-6 text-left text-sm font-semibold text-gray-400">Tên nguyên liệu</th>
                                    <th class="py-4 px-6 text-left text-sm font-semibold text-gray-400">Danh mục</th>
                                    <th class="py-4 px-6 text-left text-sm font-semibold text-gray-400">Tồn kho</th>
                                    <th class="py-4 px-6 text-left text-sm font-semibold text-gray-400">Đơn vị</th>
                                    <th class="py-4 px-6 text-left text-sm font-semibold text-gray-400">Mức tối thiểu</th>
                                    <th class="py-4 px-6 text-left text-sm font-semibold text-gray-400">Trạng thái</th>
                                    <th class="py-4 px-6 text-left text-sm font-semibold text-gray-400">Nhà cung cấp</th>
                                    <th class="py-4 px-6 text-center text-sm font-semibold text-gray-400">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventory_items as $item): 
                                    // Tính trạng thái
                                    $status_class = 'bg-green-500';
                                    $status_text = 'Đủ hàng';
                                    $quantity_percent = 100;
                                    
                                    if ($item['quantity'] <= 0) {
                                        $status_class = 'bg-red-500';
                                        $status_text = 'Hết hàng';
                                        $quantity_percent = 0;
                                    } elseif ($item['quantity'] <= $item['min_quantity']) {
                                        $status_class = 'bg-yellow-500';
                                        $status_text = 'Sắp hết';
                                        $quantity_percent = ($item['quantity'] / $item['min_quantity']) * 50;
                                    }
                                    $quantity_percent = min(100, max(0, $quantity_percent));
                                ?>
                                <tr class="border-b border-gray-800 hover:bg-gray-800 transition">
                                    <td class="py-4 px-6">
                                        <p class="text-white font-semibold"><?php echo $item['item_name']; ?></p>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-3 py-1 bg-gray-700 rounded-full text-xs"><?php echo $item['category']; ?></span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div>
                                            <p class="text-white font-bold"><?php echo number_format($item['quantity'], 1); ?></p>
                                            <div class="w-full bg-gray-700 rounded-full h-2 mt-1">
                                                <div class="progress-bar h-2 rounded-full <?php echo $status_class; ?>" 
                                                     style="width: <?php echo $quantity_percent; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-gray-400"><?php echo $item['unit']; ?></td>
                                    <td class="py-4 px-6 text-gray-400"><?php echo number_format($item['min_quantity'], 1); ?></td>
                                    <td class="py-4 px-6">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold text-white <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-gray-400 text-sm"><?php echo $item['supplier'] ?: 'Chưa cập nhật'; ?></td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick="importStock(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES); ?>')" 
                                                    class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm transition" title="Nhập kho">
                                                📥 Nhập
                                            </button>
                                            <button onclick="exportStock(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES); ?>', <?php echo $item['quantity']; ?>)" 
                                                    class="bg-orange-500 hover:bg-orange-600 text-white px-3 py-1 rounded text-sm transition" title="Xuất kho">
                                                📤 Xuất
                                            </button>
                                            <button onclick="editItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($item['category'], ENT_QUOTES); ?>', '<?php echo $item['unit']; ?>', <?php echo $item['min_quantity']; ?>, '<?php echo htmlspecialchars($item['supplier'] ?? '', ENT_QUOTES); ?>')" 
                                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition" title="Sửa">
                                                ✏️
                                            </button>
                                            <button onclick="deleteItem(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['item_name'], ENT_QUOTES); ?>')" 
                                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition" title="Xóa">
                                                🗑️
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (count($inventory_items) == 0): ?>
                                <tr>
                                    <td colspan="8" class="py-20 text-center text-gray-500">
                                        <span class="text-6xl block mb-4">📦</span>
                                        <p class="text-xl">Kho trống</p>
                                        <button onclick="openAddModal()" class="mt-4 bg-coffee-gold text-coffee-dark px-6 py-2 rounded-lg font-bold hover:bg-opacity-90">
                                            ➕ Thêm nguyên liệu đầu tiên
                                        </button>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Thêm/Sửa -->
    <div id="itemModal" class="modal hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
        <div class="bg-gray-900 rounded-xl border border-coffee-gold p-8 w-full max-w-lg mx-4">
            <h2 id="modalTitle" class="text-2xl font-bold text-white mb-6">➕ Thêm nguyên liệu</h2>
            
            <form id="itemForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="itemId">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-gray-300 mb-2">📦 Tên nguyên liệu</label>
                        <input type="text" name="item_name" id="itemName" required 
                               class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none"
                               placeholder="VD: Cà phê hạt">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">📂 Danh mục</label>
                        <select name="category" id="itemCategory" required 
                                class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none">
                            <option value="Nguyên liệu">Nguyên liệu</option>
                            <option value="Vật dụng">Vật dụng</option>
                            <option value="Đồ uống">Đồ uống</option>
                            <option value="Khác">Khác</option>
                        </select>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div id="quantityField">
                            <label class="block text-gray-300 mb-2">📊 Số lượng</label>
                            <input type="number" name="quantity" id="itemQuantity" step="0.1" min="0" required 
                                   class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-gray-300 mb-2">📏 Đơn vị</label>
                            <select name="unit" id="itemUnit" required 
                                    class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none">
                                <option value="kg">kg</option>
                                <option value="g">g</option>
                                <option value="lít">lít</option>
                                <option value="ml">ml</option>
                                <option value="hộp">hộp</option>
                                <option value="cái">cái</option>
                                <option value="gói">gói</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">⚠️ Mức tối thiểu</label>
                        <input type="number" name="min_quantity" id="itemMinQuantity" step="0.1" min="0" required 
                               class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none"
                               placeholder="Cảnh báo khi dưới mức này">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">🏢 Nhà cung cấp</label>
                        <input type="text" name="supplier" id="itemSupplier" 
                               class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none"
                               placeholder="VD: Công ty ABC">
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
    
    <!-- Modal Nhập/Xuất -->
    <div id="stockModal" class="modal hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
        <div class="bg-gray-900 rounded-xl border border-coffee-gold p-8 w-full max-w-md mx-4">
            <h2 id="stockModalTitle" class="text-2xl font-bold text-white mb-6">📥 Nhập kho</h2>
            
            <form id="stockForm" method="POST">
                <input type="hidden" name="action" id="stockAction" value="import">
                <input type="hidden" name="id" id="stockItemId">
                <input type="hidden" name="item_name" id="stockItemName">
                
                <div>
                    <label class="block text-gray-300 mb-2">🔢 Số lượng</label>
                    <input type="number" name="import_quantity" id="stockQuantity" step="0.1" min="0.1" required 
                           class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none text-2xl text-center"
                           placeholder="Nhập số lượng...">
                </div>
                
                <p class="text-gray-500 text-sm mt-2" id="stockInfo"></p>
                
                <div class="flex gap-4 mt-6">
                    <button type="submit" id="stockSubmitBtn" class="flex-1 bg-green-500 text-white py-3 rounded-lg font-bold hover:bg-green-600 transition">
                        ✅ Xác nhận
                    </button>
                    <button type="button" onclick="closeStockModal()" class="flex-1 bg-gray-700 text-white py-3 rounded-lg font-bold hover:bg-gray-600 transition">
                        ❌ Hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Modal Thêm/Sửa
        function openAddModal() {
            document.getElementById('modalTitle').textContent = '➕ Thêm nguyên liệu mới';
            document.getElementById('formAction').value = 'add';
            document.getElementById('itemForm').reset();
            document.getElementById('quantityField').style.display = 'block';
            document.getElementById('itemModal').classList.remove('hidden');
        }
        
        function editItem(id, name, category, unit, minQuantity, supplier) {
            document.getElementById('modalTitle').textContent = '✏️ Sửa: ' + name;
            document.getElementById('formAction').value = 'update';
            document.getElementById('itemId').value = id;
            document.getElementById('itemName').value = name;
            document.getElementById('itemCategory').value = category;
            document.getElementById('itemUnit').value = unit;
            document.getElementById('itemMinQuantity').value = minQuantity;
            document.getElementById('itemSupplier').value = supplier;
            document.getElementById('quantityField').style.display = 'none';
            document.getElementById('itemModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('itemModal').classList.add('hidden');
        }
        
        function deleteItem(id, name) {
            if (confirm('Bạn có chắc muốn xóa "' + name + '"?\nHành động này không thể hoàn tác!')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${id}">
                    <input type="hidden" name="name" value="${name}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Modal Nhập/Xuất kho
        function importStock(id, name) {
            document.getElementById('stockModalTitle').textContent = '📥 Nhập kho: ' + name;
            document.getElementById('stockAction').value = 'import';
            document.getElementById('stockItemId').value = id;
            document.getElementById('stockItemName').value = name;
            document.getElementById('stockQuantity').name = 'import_quantity';
            document.getElementById('stockQuantity').value = '';
            document.getElementById('stockInfo').textContent = 'Số lượng sẽ được CỘNG vào tồn kho hiện tại';
            document.getElementById('stockSubmitBtn').className = 'flex-1 bg-green-500 text-white py-3 rounded-lg font-bold hover:bg-green-600 transition';
            document.getElementById('stockSubmitBtn').textContent = '✅ Nhập kho';
            document.getElementById('stockModal').classList.remove('hidden');
        }
        
        function exportStock(id, name, currentQuantity) {
            document.getElementById('stockModalTitle').textContent = '📤 Xuất kho: ' + name;
            document.getElementById('stockAction').value = 'export';
            document.getElementById('stockItemId').value = id;
            document.getElementById('stockItemName').value = name;
            document.getElementById('stockQuantity').name = 'export_quantity';
            document.getElementById('stockQuantity').value = '';
            document.getElementById('stockQuantity').max = currentQuantity;
            document.getElementById('stockInfo').textContent = 'Tồn kho hiện tại: ' + currentQuantity + ' (Số lượng sẽ được TRỪ đi)';
            document.getElementById('stockSubmitBtn').className = 'flex-1 bg-orange-500 text-white py-3 rounded-lg font-bold hover:bg-orange-600 transition';
            document.getElementById('stockSubmitBtn').textContent = '✅ Xuất kho';
            document.getElementById('stockModal').classList.remove('hidden');
        }
        
        function closeStockModal() {
            document.getElementById('stockModal').classList.add('hidden');
        }
        
        // Đóng modal khi click bên ngoài
        document.getElementById('itemModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        document.getElementById('stockModal').addEventListener('click', function(e) {
            if (e.target === this) closeStockModal();
        });
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeModal();
                closeStockModal();
            }
        });
    </script>
</body>
</html>