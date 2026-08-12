ALTER TABLE payments ADD COLUMN gateway_reference VARCHAR(191) NULL AFTER payment_method;
ALTER TABLE payments ADD UNIQUE INDEX idx_payments_gateway_reference (gateway_reference);

CREATE TABLE IF NOT EXISTS payment_webhook_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    gateway VARCHAR(40) NOT NULL,
    event_id VARCHAR(191) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_webhook_event (gateway, event_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
