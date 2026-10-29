-- Run once after the account feedback migration.
ALTER TABLE orders
    ADD COLUMN public_order_number CHAR(20) NULL,
    ADD COLUMN delivery_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    ADD UNIQUE KEY uq_orders_public_order_number (public_order_number);

CREATE TABLE IF NOT EXISTS customer_admin_messages (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender_role ENUM('customer','admin') NOT NULL,
    message TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL DEFAULT NULL,
    KEY idx_customer_admin_thread (user_id, id),
    KEY idx_customer_admin_unread (user_id, sender_role, read_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
