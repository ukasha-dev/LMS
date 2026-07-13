USE school_saas;

CREATE TABLE department (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    department_name VARCHAR(200) NOT NULL,
    is_active VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_department_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE staff_designation (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    designation VARCHAR(200) NOT NULL,
    is_active VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_staffdesignation_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE leave_types (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    type VARCHAR(200) NOT NULL,
    is_active VARCHAR(50) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    UNIQUE KEY uniq_tenant_type (tenant_id, type),
    CONSTRAINT fk_leavetypes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE staff_leave_details (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    staff_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    alloted_leave VARCHAR(100) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sld_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_sld_staff FOREIGN KEY (staff_id) REFERENCES staff (id),
    CONSTRAINT fk_sld_leavetype FOREIGN KEY (leave_type_id) REFERENCES leave_types (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
