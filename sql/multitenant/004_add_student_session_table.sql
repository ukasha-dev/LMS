USE school_saas;

CREATE TABLE student_session (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    student_id INT NOT NULL,
    class_id INT NOT NULL,
    section_id INT NOT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_studentsession_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_studentsession_student FOREIGN KEY (student_id) REFERENCES students (id),
    CONSTRAINT fk_studentsession_class FOREIGN KEY (class_id) REFERENCES classes (id),
    CONSTRAINT fk_studentsession_section FOREIGN KEY (section_id) REFERENCES sections (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
