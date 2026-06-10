<?php
require_once '../config/database.php';
requireLogin();

// Chỉ admin mới được vào
if ($_SESSION['role'] != 'admin') {
    header('Location: ../index.php');
    exit();
}

$database = new Database();
$db = $database->getConnection();

$message = '';
$messageType = '';

// XỬ LÝ FORM
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    // THÊM NHÂN VIÊN MỚI
    if ($action == 'add') {
        $username = $_POST['username'] ?? '';
        $password = password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT);
        $full_name = $_POST['full_name'] ?? '';
        $role = $_POST['role'] ?? 'staff';
        $phone = $_POST['phone'] ?? '';
        $position = $_POST['position'] ?? '';
        $salary = $_POST['salary'] ?? 0;
        $hire_date = $_POST['hire_date'] ?? date('Y-m-d');
        $status = $_POST['status'] ?? 'active';
        
        try {
            // Kiểm tra username đã tồn tại
            $stmt = $db->prepare("SELECT id FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            if ($stmt->rowCount() > 0) {
                $message = "❌ Username '$username' đã tồn tại!";
                $messageType = 'error';
            } else {
                // Thêm vào bảng users
                $query = "INSERT INTO users (username, password, full_name, role, status) 
                          VALUES (:username, :password, :full_name, :role, :status)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $password);
                $stmt->bindParam(':full_name', $full_name);
                $stmt->bindParam(':role', $role);
                $stmt->bindParam(':status', $status);
                $stmt->execute();
                
                $user_id = $db->lastInsertId();
                
                // Thêm vào bảng staff
                $query = "INSERT INTO staff (user_id, position, phone, salary, hire_date) 
                          VALUES (:user_id, :position, :phone, :salary, :hire_date)";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':user_id', $user_id);
                $stmt->bindParam(':position', $position);
                $stmt->bindParam(':phone', $phone);
                $stmt->bindParam(':salary', $salary);
                $stmt->bindParam(':hire_date', $hire_date);
                $stmt->execute();
                
                $message = "✅ Thêm nhân viên '$full_name' thành công!";
                $messageType = 'success';
            }
        } catch(PDOException $e) {
            $message = "❌ Lỗi: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // SỬA NHÂN VIÊN
    if ($action == 'update') {
        $id = $_POST['id'] ?? 0;
        $full_name = $_POST['full_name'] ?? '';
        $role = $_POST['role'] ?? 'staff';
        $phone = $_POST['phone'] ?? '';
        $position = $_POST['position'] ?? '';
        $salary = $_POST['salary'] ?? 0;
        $status = $_POST['status'] ?? 'active';
        
        try {
            // Cập nhật bảng users
            $query = "UPDATE users SET full_name = :full_name, role = :role, status = :status 
                      WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':full_name', $full_name);
            $stmt->bindParam(':role', $role);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            // Cập nhật bảng staff
            $query = "UPDATE staff SET position = :position, phone = :phone, salary = :salary 
                      WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':position', $position);
            $stmt->bindParam(':phone', $phone);
            $stmt->bindParam(':salary', $salary);
            $stmt->bindParam(':user_id', $id);
            $stmt->execute();
            
            $message = "✅ Cập nhật nhân viên '$full_name' thành công!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "❌ Lỗi: " . $e->getMessage();
            $messageType = 'error';
        }
    }
    
    // XÓA NHÂN VIÊN
    if ($action == 'delete') {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        
        try {
            // Xóa trong bảng staff
            $stmt = $db->prepare("DELETE FROM staff WHERE user_id = :user_id");
            $stmt->bindParam(':user_id', $id);
            $stmt->execute();
            
            // Xóa trong bảng users
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $message = "✅ Đã xóa nhân viên '$name'!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "❌ Không thể xóa nhân viên này!";
            $messageType = 'error';
        }
    }
    
    // RESET MẬT KHẨU
    if ($action == 'reset_password') {
        $id = $_POST['id'] ?? 0;
        $new_password = password_hash('123456', PASSWORD_DEFAULT);
        
        try {
            $stmt = $db->prepare("UPDATE users SET password = :password WHERE id = :id");
            $stmt->bindParam(':password', $new_password);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            
            $message = "✅ Đã reset mật khẩu về mặc định (123456)!";
            $messageType = 'success';
        } catch(PDOException $e) {
            $message = "❌ Lỗi: " . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// LẤY DANH SÁCH NHÂN VIÊN
$staffs = [];
try {
    $query = "SELECT u.*, s.position, s.phone, s.salary, s.hire_date 
              FROM users u 
              LEFT JOIN staff s ON u.id = s.user_id 
              WHERE u.role IN ('admin', 'manager', 'staff')
              ORDER BY FIELD(u.role, 'admin', 'manager', 'staff'), u.full_name";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $staffs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $message = "❌ Lỗi database: " . $e->getMessage();
    $messageType = 'error';
}

// Thống kê
$total_staff = count($staffs);
$active_staff = count(array_filter($staffs, function($s) { return $s['status'] == 'active'; }));
$admin_count = count(array_filter($staffs, function($s) { return $s['role'] == 'admin'; }));
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Nhân viên - Capy Huy Coffee</title>
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
        .modal {
            transition: opacity 0.3s ease;
        }
        .staff-row:hover {
            background: #1f2937;
        }
    </style>
</head>
<body class="bg-coffee-dark text-gray-300">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-gray-900 border-r border-coffee-brown flex flex-col">
            <div class="p-6">
                <h2 class="text-2xl font-bold text-coffee-gold">☕ Capy Huy</h2>
                <p class="text-gray-500 text-sm mt-1">Quản lý Nhân viên</p>
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
                <a href="staff.php" class="flex items-center px-4 py-3 bg-coffee-brown text-white rounded-lg">
                    <span class="text-xl">👥</span><span class="ml-3">Nhân viên</span>
                </a>
                <a href="inventory.php" class="flex items-center px-4 py-3 text-gray-400 hover:bg-gray-800 rounded-lg transition">
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
                        <p class="text-xs text-gray-400 capitalize"><?php echo $_SESSION['role'] ?? 'admin'; ?></p>
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
                        <h1 class="text-3xl font-bold text-white">👥 Quản lý Nhân viên</h1>
                        <p class="text-gray-400 mt-1">Tổng: <?php echo $total_staff; ?> nhân viên</p>
                    </div>
                    <button onclick="openAddModal()" class="bg-coffee-gold text-coffee-dark px-6 py-3 rounded-lg font-bold text-lg hover:bg-opacity-90 transition flex items-center">
                        <span class="text-xl mr-2">➕</span> Thêm nhân viên
                    </button>
                </div>
                
                <!-- Thông báo -->
                <?php if ($message): ?>
                <div class="<?php echo $messageType == 'success' ? 'bg-green-500' : 'bg-red-500'; ?> text-white p-4 rounded-lg mb-6">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div class="bg-gray-900 rounded-xl p-6 border border-coffee-brown">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm">Tổng nhân viên</p>
                                <p class="text-3xl font-bold text-white mt-2"><?php echo $total_staff; ?></p>
                            </div>
                            <span class="text-4xl">👥</span>
                        </div>
                    </div>
                    <div class="bg-gray-900 rounded-xl p-6 border border-coffee-brown">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm">Đang làm việc</p>
                                <p class="text-3xl font-bold text-green-400 mt-2"><?php echo $active_staff; ?></p>
                            </div>
                            <span class="text-4xl">✅</span>
                        </div>
                    </div>
                    <div class="bg-gray-900 rounded-xl p-6 border border-coffee-brown">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-gray-400 text-sm">Quản lý</p>
                                <p class="text-3xl font-bold text-coffee-gold mt-2"><?php echo $admin_count; ?></p>
                            </div>
                            <span class="text-4xl">👑</span>
                        </div>
                    </div>
                </div>
                
                <!-- Bảng nhân viên -->
                <div class="bg-gray-900 rounded-xl border border-coffee-brown overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-coffee-brown bg-gray-800">
                                    <th class="py-4 px-6 text-left text-sm font-semibold text-gray-400">Nhân viên</th>
                                    <th class="py-4 px-6 text-left text-sm font-semibold text-gray-400">Chức vụ</th>
                                    <th class="py-4 px-6 text-left text-sm font-semibold text-gray-400">Vai trò</th>
                                    <th class="py-4 px-6 text-left text-sm font-semibold text-gray-400">Lương</th>
                                    <th class="py-4 px-6 text-left text-sm font-semibold text-gray-400">Trạng thái</th>
                                    <th class="py-4 px-6 text-left text-sm font-semibold text-gray-400">Ngày vào</th>
                                    <th class="py-4 px-6 text-center text-sm font-semibold text-gray-400">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staffs as $staff): ?>
                                <tr class="staff-row border-b border-gray-800 transition">
                                    <td class="py-4 px-6">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-bold text-lg
                                                      <?php echo $staff['role'] == 'admin' ? 'bg-red-500' : ($staff['role'] == 'manager' ? 'bg-blue-500' : 'bg-green-500'); ?>">
                                                <?php echo strtoupper(substr($staff['full_name'], 0, 1)); ?>
                                            </div>
                                            <div class="ml-3">
                                                <p class="text-white font-semibold"><?php echo $staff['full_name']; ?></p>
                                                <p class="text-gray-500 text-sm">@<?php echo $staff['username']; ?></p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6 text-white"><?php echo $staff['position'] ?: 'Chưa cập nhật'; ?></td>
                                    <td class="py-4 px-6">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold
                                                   <?php echo $staff['role'] == 'admin' ? 'bg-red-500 text-white' : 
                                                             ($staff['role'] == 'manager' ? 'bg-blue-500 text-white' : 'bg-green-500 text-white'); ?>">
                                            <?php 
                                            $role_names = ['admin' => '👑 Admin', 'manager' => '🔧 Quản lý', 'staff' => '👤 Nhân viên'];
                                            echo $role_names[$staff['role']] ?? 'Nhân viên'; 
                                            ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="text-coffee-gold font-semibold">
                                            <?php echo $staff['salary'] ? number_format($staff['salary'], 0, ',', '.').'₫' : 'Chưa cập nhật'; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="px-3 py-1 rounded-full text-xs font-bold
                                                   <?php echo $staff['status'] == 'active' ? 'bg-green-500 bg-opacity-20 text-green-400' : 'bg-red-500 bg-opacity-20 text-red-400'; ?>">
                                            <?php echo $staff['status'] == 'active' ? '🟢 Đang làm' : '🔴 Nghỉ việc'; ?>
                                        </span>
                                    </td>
                                    <td class="py-4 px-6 text-gray-400">
                                        <?php echo $staff['hire_date'] ? date('d/m/Y', strtotime($staff['hire_date'])) : 'Chưa cập nhật'; ?>
                                    </td>
                                    <td class="py-4 px-6">
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick="editStaff(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars($staff['full_name'], ENT_QUOTES); ?>', '<?php echo $staff['role']; ?>', '<?php echo $staff['status']; ?>', '<?php echo htmlspecialchars($staff['position'] ?? '', ENT_QUOTES); ?>', '<?php echo $staff['phone'] ?? ''; ?>', <?php echo $staff['salary'] ?? 0; ?>)" 
                                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm transition">
                                                ✏️ Sửa
                                            </button>
                                            <button onclick="resetPassword(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars($staff['full_name'], ENT_QUOTES); ?>')" 
                                                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm transition">
                                                🔄 Reset MK
                                            </button>
                                            <?php if ($staff['role'] != 'admin' || $staff['id'] != $_SESSION['user_id']): ?>
                                            <button onclick="deleteStaff(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars($staff['full_name'], ENT_QUOTES); ?>')" 
                                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition">
                                                🗑️ Xóa
                                            </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (count($staffs) == 0): ?>
                                <tr>
                                    <td colspan="7" class="py-20 text-center text-gray-500">
                                        <span class="text-6xl block mb-4">👥</span>
                                        <p class="text-xl">Chưa có nhân viên nào</p>
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
    <div id="staffModal" class="modal hidden fixed inset-0 bg-black bg-opacity-70 flex items-center justify-center z-50">
        <div class="bg-gray-900 rounded-xl border border-coffee-gold p-8 w-full max-w-lg mx-4 max-h-[90vh] overflow-y-auto">
            <h2 id="modalTitle" class="text-2xl font-bold text-white mb-6">➕ Thêm nhân viên mới</h2>
            
            <form id="staffForm" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="staffId">
                
                <div class="space-y-4">
                    <div id="usernameField">
                        <label class="block text-gray-300 mb-2">👤 Username</label>
                        <input type="text" name="username" id="staffUsername" 
                               class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none"
                               placeholder="VD: nguyenvana">
                    </div>
                    
                    <div id="passwordField">
                        <label class="block text-gray-300 mb-2">🔒 Mật khẩu</label>
                        <input type="password" name="password" id="staffPassword" 
                               class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none"
                               placeholder="Mặc định: 123456">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">📝 Họ và tên</label>
                        <input type="text" name="full_name" id="staffName" required 
                               class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none"
                               placeholder="VD: Nguyễn Văn A">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">👑 Vai trò</label>
                        <select name="role" id="staffRole" required 
                                class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none">
                            <option value="staff">👤 Nhân viên</option>
                            <option value="manager">🔧 Quản lý</option>
                            <option value="admin">👑 Admin</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">💼 Chức vụ</label>
                        <input type="text" name="position" id="staffPosition" 
                               class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none"
                               placeholder="VD: Pha chế, Thu ngân...">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">📱 Số điện thoại</label>
                        <input type="text" name="phone" id="staffPhone" 
                               class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none"
                               placeholder="VD: 0912345678">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">💰 Lương (VNĐ/tháng)</label>
                        <input type="number" name="salary" id="staffSalary" min="0" step="100000"
                               class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none"
                               placeholder="VD: 8000000">
                    </div>
                    
                    <div>
                        <label class="block text-gray-300 mb-2">📅 Ngày vào làm</label>
                        <input type="date" name="hire_date" id="staffHireDate" value="<?php echo date('Y-m-d'); ?>"
                               class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none">
                    </div>
                    
                    <div id="statusField">
                        <label class="block text-gray-300 mb-2">📊 Trạng thái</label>
                        <select name="status" id="staffStatus" 
                                class="w-full px-4 py-3 bg-gray-800 text-white border border-gray-700 rounded-lg focus:border-coffee-gold focus:outline-none">
                            <option value="active">🟢 Đang làm</option>
                            <option value="inactive">🔴 Nghỉ việc</option>
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
        function openAddModal() {
            document.getElementById('modalTitle').textContent = '➕ Thêm nhân viên mới';
            document.getElementById('formAction').value = 'add';
            document.getElementById('staffForm').reset();
            document.getElementById('usernameField').style.display = 'block';
            document.getElementById('passwordField').style.display = 'block';
            document.getElementById('statusField').style.display = 'block';
            document.getElementById('staffModal').classList.remove('hidden');
        }
        
        function editStaff(id, name, role, status, position, phone, salary) {
            document.getElementById('modalTitle').textContent = '✏️ Sửa: ' + name;
            document.getElementById('formAction').value = 'update';
            document.getElementById('staffId').value = id;
            document.getElementById('staffName').value = name;
            document.getElementById('staffRole').value = role;
            document.getElementById('staffStatus').value = status;
            document.getElementById('staffPosition').value = position;
            document.getElementById('staffPhone').value = phone;
            document.getElementById('staffSalary').value = salary;
            
            // Ẩn fields khi sửa
            document.getElementById('usernameField').style.display = 'none';
            document.getElementById('passwordField').style.display = 'none';
            document.getElementById('statusField').style.display = 'block';
            
            document.getElementById('staffModal').classList.remove('hidden');
        }
        
        function closeModal() {
            document.getElementById('staffModal').classList.add('hidden');
        }
        
        function deleteStaff(id, name) {
            if (confirm('Bạn có chắc muốn xóa nhân viên "' + name + '"?\nHành động này không thể hoàn tác!')) {
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
        
        function resetPassword(id, name) {
            if (confirm('Reset mật khẩu của "' + name + '" về 123456?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Đóng modal khi click bên ngoài
        document.getElementById('staffModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
        
        // Đóng modal với ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeModal();
        });
    </script>
</body>
</html>