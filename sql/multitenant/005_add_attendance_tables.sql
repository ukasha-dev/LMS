USE school_saas;

CREATE TABLE attendence_type (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    type VARCHAR(50) DEFAULT NULL,
    key_value VARCHAR(50) NOT NULL,
    long_lang_name VARCHAR(250) DEFAULT NULL,
    long_name_style VARCHAR(250) DEFAULT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    for_qr_attendance INT NOT NULL DEFAULT 1,
    for_schedule INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_attendencetype_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE student_attendences (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    student_session_id INT NOT NULL,
    date DATE DEFAULT NULL,
    attendence_type_id INT NOT NULL,
    remark VARCHAR(200) DEFAULT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    in_time TIME DEFAULT NULL,
    out_time TIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_studentattendences_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_studentattendences_type FOREIGN KEY (attendence_type_id) REFERENCES attendence_type (id),
    CONSTRAINT fk_studentattendences_session FOREIGN KEY (student_session_id) REFERENCES student_session (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
