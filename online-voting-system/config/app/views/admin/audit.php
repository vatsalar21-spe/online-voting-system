<h1 class="h3 mb-4">Audit Log</h1>

<div class="card card-ovs">
    <div class="card-body p-0">
        <table class="table mb-0 align-middle">
            <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th><th>IP</th></tr></thead>
            <tbody>
            <?php foreach ($entries as $entry): ?>
                <tr>
                    <td><small><?= htmlspecialchars($entry['created_at'], ENT_QUOTES) ?></small></td>
                    <td><?= htmlspecialchars($entry['user_name'] ?? 'system', ENT_QUOTES) ?></td>
                    <td><?= htmlspecialchars($entry['action'], ENT_QUOTES) ?></td>
                    <td><small><?= htmlspecialchars($entry['details'] ?? '', ENT_QUOTES) ?></small></td>
                    <td><small><?= htmlspecialchars($entry['ip_address'] ?? '', ENT_QUOTES) ?></small></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$entries): ?>
                <tr><td colspan="5" class="text-center text-muted py-3">No activity yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
