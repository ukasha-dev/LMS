USE school_saas;

CREATE TABLE sessions (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    session VARCHAR(60) DEFAULT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_sessions_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE subjects (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    name VARCHAR(100) DEFAULT NULL,
    code VARCHAR(100) NOT NULL,
    type VARCHAR(100) NOT NULL,
    is_active VARCHAR(255) DEFAULT 'no',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_subjects_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_groups (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    name VARCHAR(250) DEFAULT NULL,
    exam_type VARCHAR(250) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    is_active INT DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_examgroups_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_group_class_batch_exams (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    exam VARCHAR(250) DEFAULT NULL,
    passing_percentage FLOAT(10,2) DEFAULT NULL,
    session_id INT NOT NULL,
    date_from DATE DEFAULT NULL,
    date_to DATE DEFAULT NULL,
    exam_group_id INT DEFAULT NULL,
    use_exam_roll_no INT NOT NULL DEFAULT 1,
    is_publish INT DEFAULT 0,
    is_rank_generated INT NOT NULL DEFAULT 0,
    description TEXT DEFAULT NULL,
    is_active INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_egcbe_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_egcbe_examgroup FOREIGN KEY (exam_group_id) REFERENCES exam_groups (id),
    CONSTRAINT fk_egcbe_session FOREIGN KEY (session_id) REFERENCES sessions (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_group_class_batch_exam_subjects (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    exam_group_class_batch_exams_id INT DEFAULT NULL,
    subject_id INT NOT NULL,
    date_from DATE NOT NULL,
    time_from TIME NOT NULL,
    duration VARCHAR(50) NOT NULL,
    room_no VARCHAR(100) DEFAULT NULL,
    max_marks FLOAT(10,2) DEFAULT NULL,
    min_marks FLOAT(10,2) DEFAULT NULL,
    credit_hours FLOAT(10,2) DEFAULT 0.00,
    date_to DATETIME DEFAULT NULL,
    is_active INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_egcbes_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_egcbes_exam FOREIGN KEY (exam_group_class_batch_exams_id) REFERENCES exam_group_class_batch_exams (id),
    CONSTRAINT fk_egcbes_subject FOREIGN KEY (subject_id) REFERENCES subjects (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_group_class_batch_exam_students (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    exam_group_class_batch_exam_id INT NOT NULL,
    student_id INT NOT NULL,
    student_session_id INT NOT NULL,
    roll_no INT DEFAULT NULL,
    teacher_remark TEXT DEFAULT NULL,
    `rank` INT NOT NULL DEFAULT 0,
    is_active INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_egcbest_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_egcbest_exam FOREIGN KEY (exam_group_class_batch_exam_id) REFERENCES exam_group_class_batch_exams (id),
    CONSTRAINT fk_egcbest_student FOREIGN KEY (student_id) REFERENCES students (id),
    CONSTRAINT fk_egcbest_session FOREIGN KEY (student_session_id) REFERENCES student_session (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE exam_group_exam_results (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    exam_group_class_batch_exam_student_id INT NOT NULL,
    exam_group_class_batch_exam_subject_id INT DEFAULT NULL,
    attendence VARCHAR(10) DEFAULT NULL,
    get_marks FLOAT(10,2) DEFAULT 0.00,
    note TEXT DEFAULT NULL,
    is_active INT DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_result_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id),
    CONSTRAINT fk_result_student FOREIGN KEY (exam_group_class_batch_exam_student_id) REFERENCES exam_group_class_batch_exam_students (id),
    CONSTRAINT fk_result_subject FOREIGN KEY (exam_group_class_batch_exam_subject_id) REFERENCES exam_group_class_batch_exam_subjects (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
