-- Run once against the Furnara database to enable saved map pins and delivery routes.
ALTER TABLE users
    ADD COLUMN delivery_address VARCHAR(500) NULL,
    ADD COLUMN delivery_latitude DECIMAL(10,7) NULL,
    ADD COLUMN delivery_longitude DECIMAL(10,7) NULL;

ALTER TABLE orders
    ADD COLUMN delivery_latitude DECIMAL(10,7) NULL,
    ADD COLUMN delivery_longitude DECIMAL(10,7) NULL;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(80) NOT NULL PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
