USE school_saas;

-- Same bug as 011_fix_global_reference_tables.sql: Stage 14's blanket
-- schema clone unconditionally appends tenant_id NOT NULL + a tenant FK to
-- every table it creates. These 2 more tables were confirmed live --
-- byte-identical content across multiple real per-branch school databases
-- via CHECKSUM TABLE -- to be GLOBAL reference catalogs, not per-tenant
-- data. Both are currently empty in school_saas, so this is a clean
-- schema-only correction with no data to migrate first.

ALTER TABLE addons DROP FOREIGN KEY fk_addons_tenant;
ALTER TABLE addons DROP COLUMN tenant_id;

ALTER TABLE resume_additional_fields_settings DROP FOREIGN KEY fk_resume_additional_fields_settings_tenant;
ALTER TABLE resume_additional_fields_settings DROP COLUMN tenant_id;
