USE school_saas;

CREATE TABLE feetype (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    is_system INT NOT NULL DEFAULT 0,
    type VARCHAR(50) DEFAULT NULL,
    code VARCHAR(100) NOT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    description TEXT DEFAULT NULL,
    session_id INT DEFAULT NULL,
    nature VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_feetype_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_feetype_session FOREIGN KEY (session_id) REFERENCES sessions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fee_groups (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    name VARCHAR(200) DEFAULT NULL,
    is_system INT NOT NULL DEFAULT 0,
    description TEXT DEFAULT NULL,
    nature VARCHAR(255) NOT NULL,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_feegroups_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fees_discounts (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    session_id INT DEFAULT NULL,
    name VARCHAR(100) DEFAULT NULL,
    code VARCHAR(100) DEFAULT NULL,
    type VARCHAR(20) DEFAULT NULL,
    percentage FLOAT(10,2) DEFAULT NULL,
    amount FLOAT(10,2) DEFAULT NULL,
    discount_limit INT DEFAULT NULL,
    expire_date DATE DEFAULT NULL,
    description TEXT DEFAULT NULL,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_feesdiscounts_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_feesdiscounts_session FOREIGN KEY (session_id) REFERENCES sessions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fee_session_groups (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    fee_groups_id INT DEFAULT NULL,
    session_id INT DEFAULT NULL,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_fsg_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_fsg_feegroups FOREIGN KEY (fee_groups_id) REFERENCES fee_groups (id),
    CONSTRAINT fk_fsg_session FOREIGN KEY (session_id) REFERENCES sessions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fee_groups_feetype (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    fee_session_group_id INT DEFAULT NULL,
    fee_groups_id INT DEFAULT NULL,
    feetype_id INT DEFAULT NULL,
    session_id INT DEFAULT NULL,
    amount DECIMAL(10,2) DEFAULT NULL,
    fine_type VARCHAR(50) NOT NULL DEFAULT 'none',
    due_date DATE DEFAULT NULL,
    fine_percentage FLOAT(10,2) NOT NULL DEFAULT 0.00,
    fine_amount FLOAT(10,2) NOT NULL DEFAULT 0.00,
    fine_per_day INT NOT NULL DEFAULT 0,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_fgf_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_fgf_fsg FOREIGN KEY (fee_session_group_id) REFERENCES fee_session_groups (id),
    CONSTRAINT fk_fgf_feegroups FOREIGN KEY (fee_groups_id) REFERENCES fee_groups (id),
    CONSTRAINT fk_fgf_feetype FOREIGN KEY (feetype_id) REFERENCES feetype (id),
    CONSTRAINT fk_fgf_session FOREIGN KEY (session_id) REFERENCES sessions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE fees_reminder (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    reminder_type VARCHAR(10) DEFAULT NULL,
    day INT DEFAULT NULL,
    is_active INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_feesreminder_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_fees_master (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    is_system INT NOT NULL DEFAULT 0,
    student_session_id INT DEFAULT NULL,
    fee_session_group_id INT DEFAULT NULL,
    amount FLOAT(10,2) DEFAULT 0.00,
    pre_discount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sfm_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_sfm_session FOREIGN KEY (fee_session_group_id) REFERENCES fee_session_groups (id),
    CONSTRAINT fk_sfm_studentsession FOREIGN KEY (student_session_id) REFERENCES student_session (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_fees_deposite (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    student_fees_master_id INT DEFAULT NULL,
    fee_groups_feetype_id INT DEFAULT NULL,
    student_transport_fee_id INT DEFAULT NULL,
    amount_detail TEXT DEFAULT NULL,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sfd_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_sfd_master FOREIGN KEY (student_fees_master_id) REFERENCES student_fees_master (id),
    CONSTRAINT fk_sfd_feegroupsfeetype FOREIGN KEY (fee_groups_feetype_id) REFERENCES fee_groups_feetype (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_fees_discounts (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    student_session_id INT DEFAULT NULL,
    fees_discount_id INT DEFAULT NULL,
    status VARCHAR(20) DEFAULT 'assigned',
    payment_id VARCHAR(50) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    is_active VARCHAR(10) NOT NULL DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sfdisc_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_sfdisc_feesdiscount FOREIGN KEY (fees_discount_id) REFERENCES fees_discounts (id),
    CONSTRAINT fk_sfdisc_studentsession FOREIGN KEY (student_session_id) REFERENCES student_session (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_applied_discounts (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    student_fees_deposite_id INT DEFAULT NULL,
    student_fees_discount_id INT DEFAULT NULL,
    date DATE DEFAULT NULL,
    invoice_id INT DEFAULT NULL,
    sub_invoice_id INT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sad_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_sad_deposite FOREIGN KEY (student_fees_deposite_id) REFERENCES student_fees_deposite (id),
    CONSTRAINT fk_sad_discount FOREIGN KEY (student_fees_discount_id) REFERENCES student_fees_discounts (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
