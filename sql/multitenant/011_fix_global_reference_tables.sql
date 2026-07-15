USE school_saas;

-- Stage 14's blanket schema clone (CloneAllSchemas.php/LinkAllSchemaFKs.php)
-- unconditionally appends tenant_id NOT NULL + a tenant FK to every table it
-- creates, since it has no way to know a table's real semantic nature.
-- These 4 tables were confirmed live -- byte-identical content across
-- multiple real per-branch school databases -- to be GLOBAL reference
-- catalogs, not per-tenant data, exactly like the pre-Stage-14 fixture
-- tables (currencies, languages, email_config, permission_group), none of
-- which have a tenant_id column at all. Correcting the schema to match.

ALTER TABLE permission_student DROP FOREIGN KEY fk_permission_student_tenant;
ALTER TABLE permission_student DROP COLUMN tenant_id;

ALTER TABLE online_admission_fields DROP FOREIGN KEY fk_online_admission_fields_tenant;
ALTER TABLE online_admission_fields DROP COLUMN tenant_id;

ALTER TABLE resume_settings_fields DROP FOREIGN KEY fk_resume_settings_fields_tenant;
ALTER TABLE resume_settings_fields DROP COLUMN tenant_id;

ALTER TABLE notification_setting DROP FOREIGN KEY fk_notification_setting_tenant;
ALTER TABLE notification_setting DROP COLUMN tenant_id;
