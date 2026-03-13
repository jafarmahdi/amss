ALTER TABLE users
  MODIFY role ENUM('admin','it_manager','technician','finance','viewer') DEFAULT 'viewer';

CREATE TABLE IF NOT EXISTS asset_requests (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  request_no VARCHAR(40) NOT NULL UNIQUE,
  requested_by_user_id BIGINT NOT NULL,
  requested_for_employee_id BIGINT NULL,
  request_type ENUM('asset','spare_part','license','mixed') NOT NULL DEFAULT 'asset',
  scenario ENUM('general','employee_onboarding','branch_deployment','replacement','stock_replenishment') NOT NULL DEFAULT 'general',
  branch_id BIGINT NULL,
  category_id BIGINT NULL,
  title VARCHAR(150) NOT NULL,
  asset_specification TEXT,
  justification TEXT,
  quantity INT NOT NULL DEFAULT 1,
  estimated_cost DECIMAL(12,2) NULL,
  urgency ENUM('low','normal','high','critical') NOT NULL DEFAULT 'normal',
  needed_by_date DATE NULL,
  status ENUM('draft','pending_it','pending_it_manager','pending_finance','needs_info','rejected','approved','purchased','received','closed') NOT NULL DEFAULT 'draft',
  current_pending_role ENUM('requester','technician','it_manager','finance','none') NOT NULL DEFAULT 'requester',
  current_pending_user_id BIGINT NULL,
  fulfillment_source ENUM('purchase','storage') NULL,
  submitted_at TIMESTAMP NULL,
  approved_at TIMESTAMP NULL,
  closed_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (requested_by_user_id) REFERENCES users(id),
  FOREIGN KEY (requested_for_employee_id) REFERENCES employees(id),
  FOREIGN KEY (branch_id) REFERENCES branches(id),
  FOREIGN KEY (category_id) REFERENCES asset_categories(id),
  FOREIGN KEY (current_pending_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asset_request_items (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  request_id BIGINT NOT NULL,
  item_type ENUM('asset','spare_part','license') NOT NULL DEFAULT 'asset',
  item_name VARCHAR(150) NOT NULL,
  category_id BIGINT NULL,
  quantity INT NOT NULL DEFAULT 1,
  estimated_unit_cost DECIMAL(12,2) NULL,
  fulfillment_preference ENUM('purchase','storage','either') NOT NULL DEFAULT 'either',
  assignment_target ENUM('employee','branch','stock') NOT NULL DEFAULT 'employee',
  specification TEXT,
  notes TEXT,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (request_id) REFERENCES asset_requests(id) ON DELETE CASCADE,
  FOREIGN KEY (category_id) REFERENCES asset_categories(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asset_request_approvals (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  request_id BIGINT NOT NULL,
  step ENUM('it','it_manager','finance') NOT NULL,
  approver_user_id BIGINT NULL,
  decision ENUM('approved','returned','rejected') NOT NULL,
  comment TEXT,
  acted_at TIMESTAMP NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (request_id) REFERENCES asset_requests(id) ON DELETE CASCADE,
  FOREIGN KEY (approver_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asset_request_timeline (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  request_id BIGINT NOT NULL,
  actor_user_id BIGINT NULL,
  actor_role VARCHAR(50) NULL,
  action VARCHAR(80) NOT NULL,
  from_status VARCHAR(50) NULL,
  to_status VARCHAR(50) NULL,
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES asset_requests(id) ON DELETE CASCADE,
  FOREIGN KEY (actor_user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS spare_part_issues (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  request_id BIGINT NOT NULL,
  spare_part_id BIGINT NOT NULL,
  employee_id BIGINT NOT NULL,
  quantity INT NOT NULL DEFAULT 1,
  notes TEXT,
  issued_by BIGINT NULL,
  issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (request_id) REFERENCES asset_requests(id) ON DELETE CASCADE,
  FOREIGN KEY (spare_part_id) REFERENCES spare_parts(id),
  FOREIGN KEY (employee_id) REFERENCES employees(id),
  FOREIGN KEY (issued_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS license_allocations (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  license_id BIGINT NOT NULL,
  request_id BIGINT NULL,
  employee_id BIGINT NULL,
  branch_id BIGINT NULL,
  quantity INT NOT NULL DEFAULT 1,
  notes TEXT,
  allocated_by BIGINT NULL,
  allocated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (license_id) REFERENCES licenses(id),
  FOREIGN KEY (request_id) REFERENCES asset_requests(id) ON DELETE SET NULL,
  FOREIGN KEY (employee_id) REFERENCES employees(id),
  FOREIGN KEY (branch_id) REFERENCES branches(id),
  FOREIGN KEY (allocated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS asset_stock_groups (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  display_name VARCHAR(200) NOT NULL,
  normalized_key VARCHAR(255) NOT NULL UNIQUE,
  category_id BIGINT NULL,
  branch_id BIGINT NULL,
  brand VARCHAR(100),
  model VARCHAR(100),
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE SET NULL,
  FOREIGN KEY (branch_id) REFERENCES branches(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE asset_requests
  ADD COLUMN IF NOT EXISTS request_type ENUM('asset','spare_part','license','mixed') NOT NULL DEFAULT 'asset' AFTER requested_for_employee_id,
  ADD COLUMN IF NOT EXISTS scenario ENUM('general','employee_onboarding','branch_deployment','replacement','stock_replenishment') NOT NULL DEFAULT 'general' AFTER request_type,
  ADD COLUMN IF NOT EXISTS purchase_price DECIMAL(12,2) NULL AFTER estimated_cost,
  ADD COLUMN IF NOT EXISTS purchase_vendor VARCHAR(150) NULL AFTER purchase_price,
  ADD COLUMN IF NOT EXISTS purchase_reference VARCHAR(100) NULL AFTER purchase_vendor,
  ADD COLUMN IF NOT EXISTS purchase_date DATE NULL AFTER needed_by_date,
  ADD COLUMN IF NOT EXISTS received_date DATE NULL AFTER purchase_date,
  ADD COLUMN IF NOT EXISTS fulfillment_source ENUM('purchase','storage') NULL AFTER current_pending_user_id;

ALTER TABLE asset_requests
  MODIFY COLUMN requested_for_employee_id BIGINT NULL,
  MODIFY COLUMN request_type ENUM('asset','spare_part','license','mixed') NOT NULL DEFAULT 'asset';

ALTER TABLE assets
  ADD COLUMN IF NOT EXISTS request_id BIGINT NULL AFTER barcode,
  ADD COLUMN IF NOT EXISTS stock_group_id BIGINT NULL AFTER request_id;

SET @has_assets_request_fk := (
  SELECT COUNT(*)
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'assets'
    AND COLUMN_NAME = 'request_id'
    AND REFERENCED_TABLE_NAME = 'asset_requests'
);
SET @assets_request_fk_sql := IF(
  @has_assets_request_fk = 0,
  'ALTER TABLE assets ADD CONSTRAINT fk_assets_request_id FOREIGN KEY (request_id) REFERENCES asset_requests(id) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE assets_request_fk_stmt FROM @assets_request_fk_sql;
EXECUTE assets_request_fk_stmt;
DEALLOCATE PREPARE assets_request_fk_stmt;

SET @has_assets_stock_group_fk := (
  SELECT COUNT(*)
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'assets'
    AND COLUMN_NAME = 'stock_group_id'
    AND REFERENCED_TABLE_NAME = 'asset_stock_groups'
);
SET @assets_stock_group_fk_sql := IF(
  @has_assets_stock_group_fk = 0,
  'ALTER TABLE assets ADD CONSTRAINT fk_assets_stock_group_id FOREIGN KEY (stock_group_id) REFERENCES asset_stock_groups(id) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE assets_stock_group_fk_stmt FROM @assets_stock_group_fk_sql;
EXECUTE assets_stock_group_fk_stmt;
DEALLOCATE PREPARE assets_stock_group_fk_stmt;

ALTER TABLE asset_movements
  ADD COLUMN IF NOT EXISTS request_id BIGINT NULL AFTER asset_id,
  ADD COLUMN IF NOT EXISTS movement_type VARCHAR(50) NOT NULL DEFAULT 'manual' AFTER request_id;

SET @has_asset_movements_request_fk := (
  SELECT COUNT(*)
  FROM information_schema.KEY_COLUMN_USAGE
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'asset_movements'
    AND COLUMN_NAME = 'request_id'
    AND REFERENCED_TABLE_NAME = 'asset_requests'
);
SET @asset_movements_request_fk_sql := IF(
  @has_asset_movements_request_fk = 0,
  'ALTER TABLE asset_movements ADD CONSTRAINT fk_asset_movements_request_id FOREIGN KEY (request_id) REFERENCES asset_requests(id) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE asset_movements_request_fk_stmt FROM @asset_movements_request_fk_sql;
EXECUTE asset_movements_request_fk_stmt;
DEALLOCATE PREPARE asset_movements_request_fk_stmt;
