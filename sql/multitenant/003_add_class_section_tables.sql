USE school_saas;

CREATE TABLE classes (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    class VARCHAR(60) DEFAULT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_classes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE sections (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    section VARCHAR(60) DEFAULT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sections_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE class_sections (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    class_id INT NOT NULL,
    section_id INT NOT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_classsections_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_classsections_class FOREIGN KEY (class_id) REFERENCES classes (id),
    CONSTRAINT fk_classsections_section FOREIGN KEY (section_id) REFERENCES sections (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
