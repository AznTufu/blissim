DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS customers;

CREATE TABLE customers (
	id int AUTO_INCREMENT PRIMARY KEY,
	first_name VARCHAR(255) NOT NULL,
	last_name VARCHAR(255) NOT NULL,
	email VARCHAR(255) NOT NULL UNIQUE,
	created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
	id int AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(255) NOT NULL,
	description TEXT,
	price DECIMAL(10,2) NOT NULL CHECK (price >= 0),
	stock INT NOT NULL DEFAULT 0 CHECK (stock >= 0)
);

CREATE TABLE orders (
	id int AUTO_INCREMENT PRIMARY KEY,
	customer_id INT NOT NULL,
	order_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	status ENUM('pending', 'processing', 'completed') NOT NULL DEFAULT 'pending',
	CONSTRAINT fk_order_customer FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
	id int AUTO_INCREMENT PRIMARY KEY,
	order_id INT NOT NULL,
	product_id INT NOT NULL,
	quantity INT NOT NULL CHECK (quantity > 0),
	unit_price DECIMAL(10,2) NOT NULL CHECK (unit_price >= 0),
	CONSTRAINT fk_item_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
	CONSTRAINT fk_item_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

INSERT INTO customers (first_name, last_name, email) VALUES 
('pierre', 'dupont', 'pierre@gmail.com'),
('test', 'test', 'test@gmail.com');

INSERT INTO products (name, description, price, stock) VALUES
('parfum', 'ceci est une description', 10.99, 100),
('crème', 'ceci est une description', 5.99, 50);

INSERT INTO orders (customer_id, order_date, status) VALUES
(1, NOW(), 'processing'),
(1, NOW() - INTERVAL 1 DAY, 'pending'),
(2, NOW() - INTERVAL 2 DAY, 'completed'),
(2, NOW() - INTERVAL 8 DAY, 'completed');

INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES
(1, 1, 5, 10.99),
(2, 2, 2, 12.50),
(3, 2, 2, 12.50),
(4, 2, 20, 12.50);

SELECT c.first_name, c.last_name
FROM customers c
JOIN orders o ON c.id = o.customer_id
JOIN order_items oi ON o.id = oi.order_id
WHERE oi.product_id = 1;

SELECT p.name, SUM(oi.quantity) AS total
FROM products p
JOIN order_items oi ON p.id = oi.product_id
JOIN orders o ON oi.order_id = o.id
WHERE o.order_date >= NOW() - INTERVAL 7 DAY
GROUP BY p.id, p.name
ORDER BY total DESC;
