<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

// Tenant-safe file storage, used by tenant* controllers instead of the
// legacy Media_storage library. Media_storage::fileupload() derives its
// storage folder from Customlib::getFolderPath(), which reads
// $admin['db_array']['folder_path'] -- for every tenant session this is an
// empty string (set explicitly in PilotLogin.php), which getFolderPath()
// then coerces to null, and the upload silently falls through to the SAME
// shared `uploads/<category>/` directory every legacy branch (and every
// other tenant) also falls back to. There is no tenant isolation in the
// legacy upload path today -- this is why every prior batch in this
// migration deferred file uploads rather than port that behavior.
//
// This class keeps every tenant's files under their own directory
// (uploads/tenant_uploads/tenant_<id>/<category>/), so cross-tenant
// filename collisions and directory-listing exposure are impossible by
// construction, not by convention. The one invariant callers MUST hold:
// never build a stored path from client input -- only ever read it back
// from a row already fetched via tenantScopedFind (or an equivalent
// tenant-scoped lookup). This class does not re-verify ownership on
// download because the caller's own tenant-scoped fetch is what already
// proved ownership; re-deriving it here from a bare filename would be
// weaker, not stronger (the class has no way to know which tenant a raw
// filename argument came from without trusting the caller anyway).
class Tenant_media_storage
{
    private const ROOT = 'uploads/tenant_uploads';

    // Returns the relative path to store in the DB (e.g.
    // "tenant_25/student_images/1737012345-abc123!photo.jpg"), or null if
    // no file was posted under $fieldName.
    public function upload(string $fieldName, int $tenantId, string $category): ?string
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (!is_uploaded_file($_FILES[$fieldName]['tmp_name'])) {
            return null;
        }

        $relativeDir = "tenant_{$tenantId}/{$category}";
        $absoluteDir = FCPATH . self::ROOT . '/' . $relativeDir;
        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }

        $originalName = basename($_FILES[$fieldName]['name']);
        $storedName   = time() . '-' . uniqid((string) rand(), true) . '!' . $originalName;
        $relativePath = $relativeDir . '/' . $storedName;

        if (!move_uploaded_file($_FILES[$fieldName]['tmp_name'], $absoluteDir . '/' . $storedName)) {
            return null;
        }

        return $relativePath;
    }

    public function url(?string $storedPath): ?string
    {
        if (!$storedPath) {
            return null;
        }

        return base_url() . self::ROOT . '/' . $storedPath;
    }

    public function delete(?string $storedPath): bool
    {
        if (!$storedPath) {
            return false;
        }

        $absolutePath = FCPATH . self::ROOT . '/' . $storedPath;
        if (is_file($absolutePath)) {
            return unlink($absolutePath);
        }

        return false;
    }
}
