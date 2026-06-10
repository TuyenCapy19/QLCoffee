<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Tạo mật khẩu mới đã mã hóa
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

// Cập nhật mật khẩu cho tất cả users
$query = "UPDATE users SET password = :password WHERE username = 'admin'";
$stmt = $db->prepare($query);
$stmt->bindParam(':password', $hashed_password);

if ($stmt->execute()) {
    echo "<h2>✅ Đã reset mật khẩu thành công!</h2>";
    echo "<p>Username: <strong>admin</strong></p>";
    echo "<p>Password mới: <strong>admin123</strong></p>";
    echo "<p>Hash: " . $hashed_password . "</p>";
    echo "<p><a href='login.php' style='color: blue; font-size: 20px;'>👉 ĐĂNG NHẬP NGAY</a></p>";
} else {
    echo "<h2>❌ Lỗi khi reset mật khẩu</h2>";
}

// Hiển thị tất cả users để kiểm tra
echo "<h3>Danh sách users trong database:</h3>";
$query = "SELECT username, password, full_name FROM users";
$stmt = $db->prepare($query);
$stmt->execute();
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Username</th><th>Password (hash)</th><th>Full Name</th></tr>";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<tr>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td style='font-size: 11px;'>" . substr($row['password'], 0, 30) . "...</td>";
    echo "<td>" . $row['full_name'] . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test verify
echo "<h3>Kiểm tra mật khẩu:</h3>";
$test = password_verify('admin123', $hashed_password);
echo "Verify 'admin123': " . ($test ? '✅ TRUE' : '❌ FALSE');
?>