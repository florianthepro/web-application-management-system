CREATE TABLE IF NOT EXISTS tenant_access(
 user_id TEXT NOT NULL,
 tenant_id TEXT NOT NULL,
 can_read INTEGER NOT NULL DEFAULT 1,
 PRIMARY KEY(user_id,tenant_id),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS group_tenants(
 group_id TEXT NOT NULL,
 tenant_id TEXT NOT NULL,
 can_read INTEGER NOT NULL DEFAULT 1,
 PRIMARY KEY(group_id,tenant_id),
 FOREIGN KEY(group_id) REFERENCES groups(id) ON DELETE CASCADE,
 FOREIGN KEY(tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS app_grants(
 id TEXT PRIMARY KEY,
 instance_id TEXT NOT NULL,
 principal_type TEXT NOT NULL CHECK(principal_type IN ('user','group')),
 principal_id TEXT NOT NULL,
 permission TEXT NOT NULL CHECK(permission IN ('use','edit')),
 created_at TEXT NOT NULL,
 UNIQUE(instance_id,principal_type,principal_id,permission),
 FOREIGN KEY(instance_id) REFERENCES app_instances(id) ON DELETE CASCADE
);
CREATE TABLE IF NOT EXISTS update_sources(
 target_type TEXT NOT NULL,
 target_id TEXT NOT NULL,
 repository TEXT NOT NULL,
 branch TEXT NOT NULL DEFAULT 'main',
 installed_ref TEXT NOT NULL DEFAULT '',
 available_ref TEXT NOT NULL DEFAULT '',
 checked_at TEXT,
 PRIMARY KEY(target_type,target_id)
);
CREATE TABLE IF NOT EXISTS update_jobs(
 id TEXT PRIMARY KEY,
 target_type TEXT NOT NULL,
 target_id TEXT NOT NULL,
 action TEXT NOT NULL,
 status TEXT NOT NULL DEFAULT 'queued',
 requested_at TEXT NOT NULL,
 finished_at TEXT,
 message TEXT NOT NULL DEFAULT ''
);
