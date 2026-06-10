-- Tạo bảng users
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager', 'staff') DEFAULT 'staff',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng categories
CREATE TABLE IF NOT EXISTS categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tạo bảng products
CREATE TABLE IF NOT EXISTS products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255),
    status ENUM('available', 'unavailable') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Kiểm tra bảng orders tồn tại
CREATE TABLE IF NOT EXISTS orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    table_number INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'card', 'transfer') DEFAULT 'cash',
    status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
    staff_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES users(id)
);

-- Kiểm tra bảng order_items tồn tại
CREATE TABLE IF NOT EXISTS order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Tạo bảng staff
CREATE TABLE IF NOT EXISTS staff (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    position VARCHAR(50),
    phone VARCHAR(20),
    address TEXT,
    salary DECIMAL(10,2),
    hire_date DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Tạo bảng inventory
CREATE TABLE IF NOT EXISTS inventory (
    id INT PRIMARY KEY AUTO_INCREMENT,
    item_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    quantity DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    min_quantity DECIMAL(10,2) NOT NULL,
    supplier VARCHAR(100),
    last_restock DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Xóa dữ liệu cũ nếu có
DELETE FROM users WHERE username = 'admin';

-- Thêm tài khoản admin (Password: admin123)
INSERT INTO users (username, password, full_name, role) 
VALUES ('admin', '$2y$10$rNyT7cCR9D1L6nQk3KqFweXVB5kT5pF5GyLZ8xKq/.G5qMYfRIv5K', 'Quản lý Capy Huy', 'admin');

-- Thêm tài khoản nhân viên (Password: staff123)
INSERT INTO users (username, password, full_name, role) 
VALUES ('staff1', '$2y$10$rNyT7cCR9D1L6nQk3KqFweXVB5kT5pF5GyLZ8xKq/.G5qMYfRIv5K', 'Nguyễn Văn A', 'staff');

-- Thêm categories mẫu
INSERT INTO categories (name, description) VALUES
('Cà phê', 'Các loại cà phê truyền thống và đặc biệt'),
('Trà', 'Các loại trà thơm ngon'),
('Sinh tố', 'Sinh tố trái cây tươi'),
('Bánh ngọt', 'Các loại bánh ngọt');

-- Thêm sản phẩm mẫu
INSERT INTO products (category_id, name, description, price) VALUES
(1, 'Cà phê đen', 'Cà phê đen truyền thống đậm đà', 25000),
(1, 'Cà phê sữa', 'Cà phê sữa đặc thơm ngon', 30000),
(1, 'Bạc xỉu', 'Bạc xỉu béo ngậy', 35000),
(2, 'Trà đào', 'Trà đào cam sả mát lạnh', 35000),
(2, 'Trà vải', 'Trà vải thiều ngọt thanh', 35000),
(3, 'Sinh tố bơ', 'Sinh tố bơ tươi béo ngậy', 40000),
(3, 'Sinh tố xoài', 'Sinh tố xoài chín ngọt mát', 40000),
(4, 'Bánh mì nướng muối ớt', 'Bánh mì nướng giòn cay', 20000),
(4, 'Tiramisu', 'Bánh tiramisu chuẩn Ý', 45000);

-- Thêm inventory mẫu
INSERT INTO inventory (item_name, category, quantity, unit, min_quantity, supplier) VALUES
('Cà phê hạt', 'Nguyên liệu', 5.5, 'kg', 2, 'Công ty cà phê ABC'),
('Sữa đặc', 'Nguyên liệu', 20, 'hộp', 10, 'Vinamilk'),
('Đường', 'Nguyên liệu', 15, 'kg', 5, 'Công ty đường XYZ'),
('Trà đào', 'Nguyên liệu', 3, 'kg', 1, 'Công ty trà ABC'),
('Ly giấy', 'Vật dụng', 500, 'cái', 100, 'Công ty bao bì XYZ'),
('Ống hút', 'Vật dụng', 1000, 'cái', 200, 'Công ty bao bì XYZ');