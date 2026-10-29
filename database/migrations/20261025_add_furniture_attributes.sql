-- Run once against the Furnara database to store product specifications.
ALTER TABLE furniture
    ADD COLUMN color VARCHAR(255) NULL AFTER furniture_type,
    ADD COLUMN size VARCHAR(255) NULL AFTER color,
    ADD COLUMN material VARCHAR(255) NULL AFTER size;
