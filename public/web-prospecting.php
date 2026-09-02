<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth();
require_once __DIR__ . '/../app/AI/WebProspector.php';

$message = '';
$error = '';
$results = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $seedUrl = trim($_POST['seed_url'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $maxPages = (int)($_POST['max_pages'] ?? 10);

    try {
        if ($seedUrl === '') throw new InvalidArgumentException('Seed URL is required.');
        $prospector = new WebProspector();
        $runStmt = db()->prepare('INSERT INTO scrape_runs (seed_url,status) VALUES (?,\'running\')');
        $runStmt->execute([$seedUrl]);
        $runId = (int)db()->lastInsertId();

        $result = $prospector->crawl($seedUrl, $category, $location, $maxPages);
        $insert = db()->prepare('INSERT INTO web_prospects (scrape_run_id,business_name,category,website,domain,email,phone,address,city,source_url,source_type,raw_data,fingerprint) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE updated_at=CURRENT_TIMESTAMP');
        $count = 0;
        foreach ($result['prospects'] as $prospect) {
            $insert->execute([$runId,$prospect['business_name'],$prospect['category'],$prospect['website'],$prospect['domain'],$prospect['email'],$prospect['phone'],$prospect['address'],$prospect['city'],$prospect['source_url'],$prospect['source_type'],$prospect['raw_data'],$prospect['fingerprint']]);
            if ($insert->rowCount() > 0) $count++;
        }
        $done = db()->prepare('UPDATE scrape_runs SET status=\'completed\',pages_crawled=?,prospects_found=?,finished_at=NOW() WHERE id=?');
        $done->execute([(int)$result['pages_crawled'],$count,$runId]);
        $results = db()->prepare('SELECT * FROM web_prospects WHERE scrape_run_id=? ORDER BY id DESC');
        $results->execute([$runId]); $results = $results->fetchAll();
        $message = "Scrape completed: {$result['pages_crawled']} page(s) crawled and {$count} new prospect(s) saved.";
    } catch (Throwable $e) {
        if (!empty($runId)) {
            $fail = db()->prepare('UPDATE scrape_runs SET status=\'failed\',error_message=?,finished_at=NOW() WHERE id=?');
            $fail->execute([$e->getMessage(),$runId]);
        }
        $error = $e->getMessage();
    }
}

$recent = db()->query("SELECT * FROM web_prospects ORDER BY created_at DESC LIMIT 50")->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Web Prospecting | AI CRM</title><link rel="stylesheet" href="assets/app.css"></head><body>
<header class="topbar"><div class="brand">AI CRM</div><nav><a href="index.php">Dashboard</a><a href="leads.php">Leads</a><a href="pipeline.php">Pipeline</a><a href="web-prospecting.php">Web Prospecting</a><a href="logout.php">Logout</a></nav></header>
<main class="container"><div class="page-head"><div><p class="eyebrow">AI LEAD GENERATION</p><h1>Web Prospecting</h1><p class="muted">Crawl public pages you are permitted to access and turn business details into prospects.</p></div></div>
<?php if($message):?><div class="alert success"><?=e($message)?></div><?php endif;?><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?>
<section class="panel"><div class="panel-head"><h2>Start a prospecting run</h2></div><form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><label class="full">Seed URL *<input type="url" name="seed_url" required placeholder="https://example.com/business-directory"></label><label>Business category<input name="category" value="Grocery / Kirana" placeholder="Grocery shops"></label><label>Location<input name="location" value="Delhi" placeholder="Delhi"></label><label>Pages to crawl<select name="max_pages"><option value="5">5</option><option value="10" selected>10</option><option value="20">20</option><option value="25">25</option></select></label><div class="full"><button class="btn primary" type="submit">🔎 Find prospects</button></div></form><p class="muted small">Use a public directory or business website that allows automated access. This MVP stays on the same domain as the seed URL and does not bypass logins, CAPTCHAs, paywalls, or access controls.</p></section>
<section class="panel"><div class="panel-head"><h2>Recent prospects</h2><span class="muted">Latest 50</span></div><div class="table-wrap"><table><thead><tr><th>Business</th><th>Category</th><th>Phone</th><th>Email</th><th>City</th><th>Source</th><th>Status</th></tr></thead><tbody><?php if(!$recent):?><tr><td colspan="7" class="empty">No web prospects yet.</td></tr><?php endif;?><?php foreach($recent as $p):?><tr><td><?=e($p['business_name'])?></td><td><?=e($p['category'])?></td><td><?=e($p['phone'])?></td><td><?=e($p['email'])?></td><td><?=e($p['city'])?></td><td><a href="<?=e($p['source_url'])?>" target="_blank" rel="noopener">Open source</a></td><td><span class="pill"><?=e($p['status'])?></span></td></tr><?php endforeach;?></tbody></table></div></section>
</main></body></html>
