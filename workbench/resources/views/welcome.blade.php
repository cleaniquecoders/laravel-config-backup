<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Config Backup Workbench</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; background:#0b0f17; color:#e5e7eb; margin:0; padding:3rem 1.5rem; }
        .wrap { max-width: 760px; margin: 0 auto; }
        h1 { font-size: 1.6rem; margin-bottom: .25rem; }
        p.sub { color:#9ca3af; margin-top:0; }
        code, pre { background:#111827; border:1px solid #1f2937; border-radius:.4rem; }
        code { padding:.1rem .35rem; font-size:.9em; }
        pre { padding:1rem; overflow:auto; }
        a { color:#60a5fa; }
        .card { background:#0f1623; border:1px solid #1f2937; border-radius:.75rem; padding:1.25rem 1.5rem; margin-top:1.25rem; }
        h2 { font-size:1rem; text-transform:uppercase; letter-spacing:.05em; color:#93c5fd; }
    </style>
</head>
<body>
    <div class="wrap">
        <h1>🔐 Config Backup — Workbench</h1>
        <p class="sub">A live Laravel app (via Orchestra Testbench) with <code>cleaniquecoders/laravel-config-backup</code> installed for end-to-end testing.</p>

        <div class="card">
            <h2>Seeded data</h2>
            <p>Admin user <code>admin@example.com</code> and three encrypted <code>settings</code> rows (general, mail, integrations) are registered in the database allowlist.</p>
        </div>

        <div class="card">
            <h2>Try the CLI</h2>
            <pre>vendor/bin/testbench config-backup:create --sections=env,database --password=secret-pass --notes="manual test"
vendor/bin/testbench config-backup:restore &lt;uuid&gt; --password=secret-pass --force
vendor/bin/testbench config-backup:prune</pre>
        </div>

        <div class="card">
            <h2>Management UI</h2>
            <p>The Livewire screen is at <a href="/admin/config-backup">/admin/config-backup</a>. It renders with <strong>Flux</strong> components — install <code>livewire/flux</code> in this workbench to view it.</p>
        </div>
    </div>
</body>
</html>
