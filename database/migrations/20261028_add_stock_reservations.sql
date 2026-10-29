-- Run once before deploying the inventory reservation handlers.
-- Existing cart rows remain unreserved until the customer changes them or checks out.
ALTER TABLE cart_items
    ADD COLUMN stock_reserved TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT NOT NULL,
    furniture_id INT NOT NULL,
    product_name VARCHAR(180) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    stock_restored TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_order_items_order (order_id, user_id),
    KEY idx_order_items_product (furniture_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

UPDATE furniture SET stock = 0 WHERE stock < 0;
ALTER TABLE furniture
    ADD CONSTRAINT chk_furniture_stock_nonnegative CHECK (stock >= 0);
