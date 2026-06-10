<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE username = :username AND status = 'active'";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            header('Location: index.php');
            exit();
        } else {
            $error = 'Mật khẩu không chính xác!';
        }
    } else {
        $error = 'Tài khoản không tồn tại hoặc đã bị khóa!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Capy Huy Coffee - Đăng nhập</title>
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
<body class="bg-coffee-dark min-h-screen flex items-center justify-center">
    <div class="bg-gray-900 p-8 rounded-lg shadow-2xl w-96 border border-coffee-brown">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-coffee-gold mb-2">☕ Capy Huy Coffee</h1>
            <p class="text-gray-400">Hệ thống quản lý chuyên nghiệp</p>
        </div>
        
        <?php if ($error): ?>
        <div class="bg-red-500 text-white p-3 rounded mb-4">
            <?php echo $error; ?>
        </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="mb-4">
                <label class="block text-gray-300 mb-2">Tên đăng nhập</label>
                <input type="text" name="username" required 
                       class="w-full px-4 py-2 bg-gray-800 text-white border border-gray-700 rounded focus:outline-none focus:border-coffee-gold">
            </div>
            
            <div class="mb-6">
                <label class="block text-gray-300 mb-2">Mật khẩu</label>
                <input type="password" name="password" required 
                       class="w-full px-4 py-2 bg-gray-800 text-white border border-gray-700 rounded focus:outline-none focus:border-coffee-gold">
            </div>
            
            <button type="submit" 
                    class="w-full bg-coffee-brown text-white py-2 rounded hover:bg-opacity-90 transition duration-300">
                Đăng nhập
            </button>
        </form>
        
        <p class="text-gray-500 text-center mt-4 text-sm">
            Admin: admin / password
        </p>
    </div>
</body>
</html>