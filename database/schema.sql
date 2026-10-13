CREATE TABLE IF NOT EXISTS balcony_fred_observations (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    series_id VARCHAR(32) NOT NULL,
    observed_at DATE NOT NULL,
    value DECIMAL(14,4) NULL,
    created_at TIMESTAMP NULL DEFAULT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY balcony_fred_observations_series_observed_unique (series_id, observed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
