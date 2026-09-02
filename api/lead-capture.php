<?php
/**
 * Public lead-capture webhook.
 *
 * Accepts JSON or application/x-www-form-urlencoded POST requests from approved
 * website/forms/integrations. Protect the endpoint with LEAD_CAPTURE_KEY.
 */
require __DIR__ . '/../app.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'POST required']);
    exit;
}

$config = require __DIR__ . '/../config.php';
$expected = $config['lead_capture']['api_key'] ?? '';
$provided = $_SERVER['HTTP_X_LEAD_CAPTURE_KEY'] ?? ($_POST['api_key'] ?? '');
if (!$expected || !hash_equals($expected, (string)$provided)) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'error' => 'Invalid lead capture key']);
    exit;
}

$raw = file_get_contents('php://input');
$payload = json_decode($raw, true);
if (!is_array($payload)) $payload = $_POST;

if (!empty($payload['website_url'])) {
    echo json_encode(['ok' => true, 'message' => 'Lead accepted']);
    exit;
}

$name = trim((string)($payload['name'] ?? $payload['full_name'] ?? ''));
$email = trim((string)($payload['email'] ?? ''));
$phone = trim((string)($payload['phone'] ?? $payload['mobile'] ?? ''));
$company = trim((string)($payload['company'] ?? $payload['company_name'] ?? ''));
$source = trim((string)($payload['source'] ?? 'website')) ?: 'website';
$notes = trim((string)($payload['notes'] ?? $payload['message'] ?? ''));
$value = is_numeric($payload['value'] ?? null) ? (float)$payload['value'] : 0;
$externalId = trim((string)($payload['external_id'] ?? ''));

if ($name === '' && $email === '' && $phone === '') {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'At least name, email or phone is required']);
    exit;
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Invalid email address']);
    exit;
}

if ($externalId !== '') {
    $s = $pdo->prepare('SELECT lead_id FROM lead_capture_events WHERE external_id = ? LIMIT 1');
    $s->execute([$externalId]);
    if ($existing = $s->fetch()) {
        echo json_encode(['ok' => true, 'duplicate' => true, 'lead_id' => (int)$existing['lead_id']]);
        exit;
    }
}

$pdo->beginTransaction();
try {
    $s = $pdo->prepare('INSERT INTO leads(name,email,phone,company,source,status,value,notes,created_at,updated_at) VALUES(?,?,?,?,?,"new",?,?,NOW(),NOW())');
    $s->execute([$name ?: 'Unknown', $email ?: null, $phone ?: null, $company ?: null, $source, $value, $notes ?: null]);
    $leadId = (int)$pdo->lastInsertId();

    $activity = $pdo->prepare('INSERT INTO activities(lead_id,type,description) VALUES(?,"note",?)');
    $activity->execute([$leadId, 'Lead captured automatically from ' . $source . '.']);

    $event = $pdo->prepare('INSERT INTO lead_capture_events(external_id,lead_id,source,created_at) VALUES(?,?,?,NOW())');
    $event->execute([$externalId !== '' ? $externalId : null, $leadId, $source]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'duplicate' => false, 'lead_id' => $leadId, 'status' => 'new']);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Unable to create lead']);
}
