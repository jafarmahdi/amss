-- Database schema for Asset Management System

-- users must come first because other tables reference it
CREATE TABLE users (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(150) UNIQUE,
  password VARCHAR(255),
  role ENUM('admin','it_manager','technician','finance','viewer') DEFAULT 'viewer',
  status ENUM('active','inactive') DEFAULT 'active',
  locale ENUM('en','ar') DEFAULT 'en',
  theme ENUM('light','dark') DEFAULT 'light',
  remember_token VARCHAR(100),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE branches (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  parent_id BIGINT NULL,
  type ENUM('HQ','Branch','Office','Storage') NOT NULL,
  address TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (parent_id) REFERENCES branches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE employees (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  employee_code VARCHAR(50) NOT NULL UNIQUE,
  company_name VARCHAR(150) NULL,
  project_name VARCHAR(150) NULL,
  company_email VARCHAR(150) NULL UNIQUE,
  fingerprint_id VARCHAR(100) NULL UNIQUE,
  department VARCHAR(100),
  job_title VARCHAR(100),
  phone VARCHAR(50),
  branch_id BIGINT NULL,
  appointment_order_name VARCHAR(255) NULL,
  appointment_order_path VARCHAR(255) NULL,
  status ENUM('active','inactive') DEFAULT 'active',
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (branch_id) REFERENCES branches(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asset_categories (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description TEXT,
  created_at TIMESTAMP,
  updated_at TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asset_requests (
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
  purchase_price DECIMAL(12,2) NULL,
  purchase_vendor VARCHAR(150) NULL,
  purchase_reference VARCHAR(100) NULL,
  urgency ENUM('low','normal','high','critical') NOT NULL DEFAULT 'normal',
  needed_by_date DATE NULL,
  purchase_date DATE NULL,
  received_date DATE NULL,
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

CREATE TABLE asset_request_items (
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

CREATE TABLE asset_request_approvals (
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

CREATE TABLE asset_request_timeline (
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

CREATE TABLE assets (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  tag VARCHAR(50) UNIQUE,
  barcode VARCHAR(255),
  request_id BIGINT NULL,
  stock_group_id BIGINT NULL,
  category_id BIGINT NOT NULL,
  brand VARCHAR(100),
  model VARCHAR(100),
  serial_number VARCHAR(100),
  purchase_date DATE,
  warranty_expiry DATE,
  procurement_stage ENUM('ordered','received','deployed') DEFAULT 'received',
  vendor_name VARCHAR(150),
  invoice_number VARCHAR(100),
  status ENUM('active','repair','broken','storage','archived') DEFAULT 'active',
  archived_at TIMESTAMP NULL,
  archive_reason TEXT,
  branch_id BIGINT NULL,
  assigned_employee_id BIGINT NULL,
  notes TEXT,
  image_path VARCHAR(255),
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  FOREIGN KEY (request_id) REFERENCES asset_requests(id) ON DELETE SET NULL,
  FOREIGN KEY (stock_group_id) REFERENCES asset_stock_groups(id) ON DELETE SET NULL,
  FOREIGN KEY (category_id) REFERENCES asset_categories(id),
  FOREIGN KEY (branch_id) REFERENCES branches(id),
  FOREIGN KEY (assigned_employee_id) REFERENCES employees(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asset_documents (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  asset_id BIGINT NOT NULL,
  document_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (asset_id) REFERENCES assets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asset_assignments (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  asset_id BIGINT NOT NULL,
  employee_id BIGINT NOT NULL,
  notes TEXT,
  assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  returned_at TIMESTAMP NULL,
  FOREIGN KEY (asset_id) REFERENCES assets(id),
  FOREIGN KEY (employee_id) REFERENCES employees(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asset_stock_groups (
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

CREATE TABLE asset_movements (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  asset_id BIGINT NOT NULL,
  request_id BIGINT NULL,
  movement_type VARCHAR(50) NOT NULL DEFAULT 'manual',
  from_branch_id BIGINT NULL,
  to_branch_id BIGINT NULL,
  user_id BIGINT NULL,
  notes TEXT,
  moved_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (asset_id) REFERENCES assets(id),
  FOREIGN KEY (request_id) REFERENCES asset_requests(id) ON DELETE SET NULL,
  FOREIGN KEY (from_branch_id) REFERENCES branches(id),
  FOREIGN KEY (to_branch_id) REFERENCES branches(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asset_movement_documents (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  movement_id BIGINT NOT NULL,
  document_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (movement_id) REFERENCES asset_movements(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asset_repairs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  asset_id BIGINT NOT NULL,
  vendor_name VARCHAR(150) NOT NULL,
  reference_number VARCHAR(100),
  sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  completed_at TIMESTAMP NULL,
  outcome ENUM('in_progress','repaired','unrepairable') NOT NULL DEFAULT 'in_progress',
  return_status ENUM('active','broken','storage') NULL,
  notes TEXT,
  completion_notes TEXT,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (asset_id) REFERENCES assets(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asset_handovers (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  asset_id BIGINT NOT NULL,
  employee_id BIGINT NOT NULL,
  handover_type ENUM('issue','return') NOT NULL DEFAULT 'issue',
  handover_date DATE NOT NULL,
  notes TEXT,
  created_by BIGINT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (asset_id) REFERENCES assets(id),
  FOREIGN KEY (employee_id) REFERENCES employees(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE asset_maintenance (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  asset_id BIGINT NOT NULL,
  maintenance_type VARCHAR(100) NOT NULL,
  scheduled_date DATE NOT NULL,
  completed_date DATE NULL,
  status ENUM('scheduled','in_progress','completed') NOT NULL DEFAULT 'scheduled',
  technician_name VARCHAR(150) NULL,
  vendor_name VARCHAR(150) NULL,
  cost DECIMAL(12,2) NULL,
  notes TEXT,
  result_summary TEXT,
  next_service_date DATE NULL,
  created_by BIGINT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (asset_id) REFERENCES assets(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE licenses (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  product_name VARCHAR(150) NOT NULL,
  vendor_name VARCHAR(150),
  license_type ENUM('subscription','perpetual','trial','oem') NOT NULL DEFAULT 'subscription',
  license_key VARCHAR(255),
  seats_total INT NOT NULL DEFAULT 1,
  seats_used INT NOT NULL DEFAULT 0,
  purchase_date DATE NULL,
  expiry_date DATE NULL,
  status ENUM('active','renewal_due','expired','inactive') NOT NULL DEFAULT 'active',
  assigned_asset_id BIGINT NULL,
  assigned_employee_id BIGINT NULL,
  notes TEXT,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (assigned_asset_id) REFERENCES assets(id),
  FOREIGN KEY (assigned_employee_id) REFERENCES employees(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE license_renewals (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  license_id BIGINT NOT NULL,
  previous_expiry_date DATE NULL,
  new_expiry_date DATE NULL,
  previous_license_key VARCHAR(255) NULL,
  new_license_key VARCHAR(255) NULL,
  previous_seats_total INT NOT NULL DEFAULT 1,
  new_seats_total INT NOT NULL DEFAULT 1,
  renewal_cost DECIMAL(12,2) NULL,
  notes TEXT,
  renewed_at DATE NOT NULL,
  renewed_by BIGINT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (license_id) REFERENCES licenses(id),
  FOREIGN KEY (renewed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE license_allocations (
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

CREATE TABLE spare_parts (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  part_number VARCHAR(100) NULL,
  category VARCHAR(100) NULL,
  vendor_name VARCHAR(150) NULL,
  location VARCHAR(150) NULL,
  quantity INT NOT NULL DEFAULT 0,
  min_quantity INT NOT NULL DEFAULT 0,
  compatible_with VARCHAR(150) NULL,
  notes TEXT,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE spare_part_issues (
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

CREATE TABLE notifications (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT NOT NULL,
  type VARCHAR(50),
  data JSON,
  read_at TIMESTAMP NULL,
  created_at TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE system_settings (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(120) NOT NULL,
  setting_value TEXT NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uniq_system_setting (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE employee_offboarding (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  employee_id BIGINT NOT NULL,
  reason VARCHAR(255) NOT NULL,
  notes TEXT,
  offboarded_at DATE NOT NULL,
  completed_by BIGINT NULL,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  FOREIGN KEY (employee_id) REFERENCES employees(id),
  FOREIGN KEY (completed_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reports (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  type VARCHAR(50),
  filters JSON,
  generated_by BIGINT,
  file_path VARCHAR(255),
  created_at TIMESTAMP,
  FOREIGN KEY (generated_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE role_permissions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL,
  permission_key VARCHAR(100) NOT NULL,
  allowed TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL,
  updated_at TIMESTAMP NULL,
  UNIQUE KEY uniq_role_permission (role_name, permission_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_logs (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT,
  action VARCHAR(100),
  table_name VARCHAR(100),
  record_id BIGINT,
  old_values JSON,
  new_values JSON,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
