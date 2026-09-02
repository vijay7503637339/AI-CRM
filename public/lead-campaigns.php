<?php
require_once __DIR__ . '/../app/bootstrap.php';
require_auth();
require_once __DIR__ . '/../app/AI/WebProspector.php';

$message = '';
$error = '';
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $name = trim($_POST['name'] ?? '');
            $category = trim($_POST['category'] ?? '');
            $location = trim($_POST['location'] ?? '');
            $keywords = trim($_POST['keywords'] ?? '');
            $target = max(1, min(5000, (int)($_POST['target_count'] ?? 100)));
            $pages = max(1, min(25, (int)($_POST['pages_per_source'] ?? 10)));
            $sourceIds = array_values(array_filter(array_map('intval', $_POST['source_ids'] ?? [])));
            if ($name === '' || !$sourceIds) throw new InvalidArgumentException('Campaign name and at least one source are required.');

            $db = db();
            $db->beginTransaction();
            $stmt = $db->prepare('INSERT INTO lead_campaigns (name,category,location,keywords,target_count,pages_per_source,created_by) VALUES (?,?,?,?,?,?,?)');
            $stmt->execute([$name,$category ?: null,$location ?: null,$keywords ?: null,$target,$pages,$user['id']]);
            $campaignId = (int)$db->lastInsertId();
            $link = $db->prepare('INSERT INTO campaign_sources (campaign_id,source_id) VALUES (?,?)');
            foreach ($sourceIds as $sourceId) $link->execute([$campaignId,$sourceId]);
            $db->commit();
            header('Location: lead-campaigns.php?run=' . $campaignId);
            exit;
        }

        if ($action === 'run') {
            $campaignId = (int)($_POST['campaign_id'] ?? 0);
            $campaign = db()->prepare('SELECT * FROM lead_campaigns WHERE id=? LIMIT 1');
            $campaign->execute([$campaignId]);
            $campaign = $campaign->fetch();
            if (!$campaign) throw new RuntimeException('Campaign not found.');

            $sources = db()->prepare('SELECT ps.* FROM prospect_sources ps INNER JOIN campaign_sources cs ON cs.source_id=ps.id WHERE cs.campaign_id=? ORDER BY ps.id');
            $sources->execute([$campaignId]);
            $sources = $sources->fetchAll();
            if (!$sources) throw new RuntimeException('No sources configured for this campaign.');

            db()->prepare("UPDATE lead_campaigns SET status='running' WHERE id=?")->execute([$campaignId]);
            $prospector = new WebProspector();
            $newProspects = 0;
            $pagesCrawled = 0;
            $insert = db()->prepare('INSERT INTO web_prospects (source_id,scrape_run_id,campaign_id,business_name,category,website,domain,email,phone,address,city,source_url,source_type,raw_data,fingerprint) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE updated_at=CURRENT_TIMESTAMP');
            foreach ($sources as $source) {
                $run = db()->prepare("INSERT INTO scrape_runs (source_id,seed_url,status) VALUES (?,?, 'running')");
                $run->execute([$source['id'],$source['seed_url']]);
                $runId = (int)db()->lastInsertId();
                try {
                    $result = $prospector->crawl($source['seed_url'], $campaign['category'] ?: ($source['category'] ?? ''), $campaign['location'] ?: ($source['location'] ?? ''), (int)$campaign['pages_per_source']);
                    $pagesCrawled += (int)$result['pages_crawled'];
                    $sourceNew = 0;
                    foreach ($result['prospects'] as $p) {
                        $insert->execute([$source['id'],$runId,$campaignId,$p['business_name'],$p['category'],$p['website'],$p['domain'],$p['email'],$p['phone'],$p['address'],$p['city'],$p['source_url'],$p['source_type'],$p['raw_data'] ?? null,$p['fingerprint']]);
                        if ($insert->rowCount() > 0) { $sourceNew++; $newProspects++; }
                    }
                    db()->prepare("UPDATE scrape_runs SET status='completed',pages_crawled=?,prospects_found=?,finished_at=NOW() WHERE id=?")->execute([$result['pages_crawled'],$sourceNew,$runId]);
                } catch (Throwable $e) {
                    db()->prepare("UPDATE scrape_runs SET status='failed',error_message=?,finished_at=NOW() WHERE id=?")->execute([$e->getMessage(),$runId]);
                }
            }
            db()->prepare("UPDATE lead_campaigns SET status='completed',total_found=(SELECT COUNT(*) FROM web_prospects WHERE campaign_id=?),updated_at=CURRENT_TIMESTAMP WHERE id=?")->execute([$campaignId,$campaignId]);
            $message = "Campaign completed: {$pagesCrawled} page(s) crawled, {$newProspects} new prospect(s) found.";
        }
    } catch (Throwable $e) { $error = $e->getMessage(); }
}

$sources = db()->query('SELECT * FROM prospect_sources ORDER BY created_at DESC')->fetchAll();
$campaigns = db()->query('SELECT c.*, (SELECT COUNT(*) FROM campaign_sources cs WHERE cs.campaign_id=c.id) source_count FROM lead_campaigns c ORDER BY c.created_at DESC LIMIT 50')->fetchAll();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Lead Generation | AI CRM</title><link rel="stylesheet" href="assets/app.css"></head><body>
<header class="topbar"><div class="brand">AI CRM</div><nav><a href="index.php">Dashboard</a><a href="leads.php">Leads</a><a href="pipeline.php">Pipeline</a><a href="lead-campaigns.php">Lead Generation</a><a href="web-prospecting.php">Web Prospecting</a><a href="logout.php">Logout</a></nav></header>
<main class="container"><div class="page-head"><div><p class="eyebrow">AI LEAD GENERATION ENGINE</p><h1>Prospecting Campaigns</h1><p class="muted">Create a target and run it against your configured, permitted public-web sources.</p></div></div>
<?php if($message):?><div class="alert success"><?=e($message)?></div><?php endif;?><?php if($error):?><div class="alert error"><?=e($error)?></div><?php endif;?>
<section class="panel"><div class="panel-head"><h2>New campaign</h2></div><form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="create"><label>Campaign name<input name="name" required placeholder="Delhi Grocery Shops"></label><label>Category<input name="category" value="Grocery / Kirana" placeholder="Grocery / Kirana"></label><label>Location<input name="location" value="Delhi" placeholder="Delhi NCR"></label><label>Target prospects<input type="number" name="target_count" min="1" max="5000" value="100"></label><label>Pages per source<input type="number" name="pages_per_source" min="1" max="25" value="10"></label><label class="full">Keywords<input name="keywords" placeholder="kirana, grocery store, general store"></label><div class="full"><strong>Sources</strong><?php if(!$sources):?><p class="muted">No sources yet. Add one below.</p><?php else: foreach($sources as $s):?><label class="check"><input type="checkbox" name="source_ids[]" value="<?=$s['id']?>"> <?=e($s['name'])?> <span class="muted">(<?=e($s['location'] ?: 'all locations')?>)</span></label><?php endforeach; endif;?></div><div class="full"><button class="btn primary" type="submit">Create campaign</button></div></form></section>
<section class="panel"><div class="panel-head"><h2>Sources</h2><span class="muted">Only use sources you are permitted to crawl and use.</span></div><form method="post" action="prospect-source.php" class="form-grid"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><label>Source name<input name="name" required placeholder="Public business directory"></label><label>Seed URL<input type="url" name="seed_url" required placeholder="https://example.com/directory"></label><label>Category<input name="category" placeholder="Grocery"></label><label>Location<input name="location" placeholder="Delhi"></label><div class="full"><button class="btn" type="submit">+ Add source</button></div></form></section>
<section class="panel"><div class="panel-head"><h2>Campaign history</h2></div><div class="table-wrap"><table><thead><tr><th>Campaign</th><th>Category</th><th>Location</th><th>Sources</th><th>Found</th><th>Status</th><th></th></tr></thead><tbody><?php foreach($campaigns as $c):?><tr><td><?=e($c['name'])?></td><td><?=e($c['category'])?></td><td><?=e($c['location'])?></td><td><?=e((string)$c['source_count'])?></td><td><?=e((string)$c['total_found'])?></td><td><span class="pill"><?=e($c['status'])?></span></td><td><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="action" value="run"><input type="hidden" name="campaign_id" value="<?=$c['id']?>"><button class="btn" type="submit" <?=count($sources)===0?'disabled':''?>>Run</button></form></td></tr><?php endforeach;?><?php if(!$campaigns):?><tr><td colspan="7" class="empty">No campaigns yet.</td></tr><?php endif;?></tbody></table></div></section>
</main></body></html>
