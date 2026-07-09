CREATE DATABASE IF NOT EXISTS school_saas CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE school_saas;

CREATE TABLE tenants (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(200) NOT NULL,
    source_database VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE students (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    parent_id INT NOT NULL DEFAULT 0,
    admission_no VARCHAR(100) DEFAULT NULL,
    firstname VARCHAR(100) DEFAULT NULL,
    middlename VARCHAR(255) DEFAULT NULL,
    lastname VARCHAR(100) DEFAULT NULL,
    is_active VARCHAR(255) DEFAULT 'yes',
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_students_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    tenant_id INT NOT NULL,
    user_id INT NOT NULL DEFAULT 0,
    username VARCHAR(50) DEFAULT NULL,
    password VARCHAR(255) DEFAULT NULL,
    role VARCHAR(30) NOT NULL,
    is_active VARCHAR(255) DEFAULT 'yes',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tenant (tenant_id),
    CONSTRAINT fk_users_tenant FOREIGN KEY (tenant_id) REFERENCES tenants (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO tenants (id, name, source_database, is_active)
VALUES (25, 'Al-Hafeez Campus', 'al_hafeez_campus', 1);
