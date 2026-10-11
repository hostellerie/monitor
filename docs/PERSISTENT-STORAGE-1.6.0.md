# Monitor persistent storage — Geeklog 2.2.2 safety

Geeklog 2.2.2 may remove arbitrary plugin files located directly inside `$_CONF['path_data']` when **Clear Cache** is used (Geeklog-Core issue #1171). Monitor stores persistent snapshots and log archives outside this directory.

## Storage

For `path_data=/private/data/` the persistent root is `/private/data-monitor/`.

- `changes/monitor-changes-<site-hash>.json`: last 10 change snapshots.
- `log-archives/<site-key>/`: dated log archives and `.rotation.json` state.
- The rebuildable GitHub plugin-catalog cache remains in `path_data`.

All roots are derived from the current site's `path_data`, not a global hardcoded path. Existing per-site hash/key names are retained.

## Migration and rollback

On first access to changes or archived logs, Monitor checks the old path and copies old files to the persistent path, checks file size and SHA-256, and **leaves all original data intact**. The new copy becomes authoritative. Existing destination files are never overwritten. If copying fails, Monitor does not write change snapshots or rotate logs through the new path. An error is recorded for administrators. Archive listing and file viewing also trigger the archive migration.

The old data must not be removed automatically. For a multisite installation sharing PHP sources, **back up each site's old data and perform the migration on each site separately**. Be sure the site's correct `path_data` is active. A site still serving old Monitor code must not run Geeklog 2.2.2 Clear Cache until its persistent data has been migrated.

There is **no recovery from files already deleted by Clear Cache** without an external backup.

## Before using Clear Cache under Geeklog 2.2.2

1. Back up the whole existing site-specific `path_data` and log directory.
2. Deploy these Monitor files, then open the change-history and log-archive pages (or invoke the relevant read paths) on each site.
3. Confirm corresponding files now exist under `data-monitor/changes` and `data-monitor/log-archives/<site-key>`, with correct owner and permissions.
4. Confirm snapshot history and archived log listing are readable. Confirm `.rotation.json` is copied.
5. Only after validating the new files, perform Clear Cache and confirm Monitor history and archives remain intact.

This implementation copies without deleting source files. Before using it in production, test one site with a full backup, both PHP 5.6/8.1 and Geeklog 2.1.1/2.2.2, and repeat the migration for idempotence. No live-host test is asserted here.

## Known concurrency consideration

This migration is designed to be non-destructive, but the first migration should be performed during a quiet maintenance window to avoid concurrent log rotation or history writes across old and new code.
