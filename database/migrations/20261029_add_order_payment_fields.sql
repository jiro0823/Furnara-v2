-- Run once after the order and delivery migrations.
ALTER TABLE orders
    ADD COLUMN payment_method VARCHAR(32) NOT NULL DEFAULT 'cash_on_delivery',
    ADD COLUMN payment_status VARCHAR(20) NOT NULL DEFAULT 'Pending';
