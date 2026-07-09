USE school_saas;

CREATE TABLE staff (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    employee_id VARCHAR(200) NOT NULL,
    name VARCHAR(200) NOT NULL,
    surname VARCHAR(200) DEFAULT NULL,
    email VARCHAR(200) NOT NULL,
    password VARCHAR(250) NOT NULL,
    gender VARCHAR(50) DEFAULT NULL,
    image VARCHAR(200) DEFAULT NULL,
    is_active INT NOT NULL DEFAULT 1,
    verification_code VARCHAR(100) DEFAULT NULL,
    lang_id INT NOT NULL DEFAULT 0,
    currency_id INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_staff_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE roles (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    name VARCHAR(100) DEFAULT NULL,
    slug VARCHAR(150) DEFAULT NULL,
    is_active INT NOT NULL DEFAULT 1,
    is_system INT NOT NULL DEFAULT 0,
    is_superadmin INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_roles_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE staff_roles (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    staff_id INT NOT NULL,
    role_id INT NOT NULL,
    is_active INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_staffroles_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_staffroles_staff FOREIGN KEY (staff_id) REFERENCES staff (id),
    CONSTRAINT fk_staffroles_role FOREIGN KEY (role_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
