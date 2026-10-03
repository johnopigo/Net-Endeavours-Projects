<?php
/**
 * TSDP 2027 Cohort registration handler.
 *
 * Receives the "Register for the Next Training (2027 Cohort)" form and emails
 * each entry to the TSDP Project Desk.
 *
 * To change who receives the entries, edit TSDP_RECIPIENTS below
 * (separate multiple addresses with commas).
 */

const TSDP_RECIPIENTS = 'tsdpproject@neca.org.ng';
const TSDP_FROM       = 'no-reply@tsdp.neca.org.ng';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(405, ['ok' => false, 'error' => 'Method not allowed']);
}

// Honeypot field: real visitors never fill it in.
if (!empty($_POST['website'])) {
    respond(200, ['ok' => true]);
}

$tradeAreas = [
    'Advanced Artificial Intelligence', 'AI Automation', 'Animal Husbandry', 'Auto Mechanics',
    'Auto Mechatronics', 'Automobile / Autotronics', 'Baking & Confectionery', 'Catering / Culinary',
    'Creative Media, Storytelling & Entrepreneurship Development', 'Crop Production', 'Data Analysis',
    'Domestic Electrical Installation and Maintenance', 'Electrical / Electronic Maintenance',
    'Electrical Electronics Engineering and Automation', 'Electrical Installation',
    'Fashion Design / Tailoring', 'Hospitality & Catering Craft',
    'HVAC-R / Refrigeration and Air-Conditioning', 'Information and Communication Technology (ICT)',
    'Instrumentation and Process Control', 'IT System Support and Productivity Software Applications',
    'Livestock Production', 'Logistics Operations', 'Mechanical Maintenance and Machining',
    'Mechatronics (Packaging, Brewing & Utility, Corrective Maintenance)', 'Motor Vehicle Maintenance',
    'Operational Quality', 'Packaging Operations', 'Painting and Decoration', 'Plumbing and Pipe Fitting',
    'Python Software', 'Solar Installation and Maintenance', 'Spray Painting', 'Video Editing',
    'Welding and Fabrication',
];
$locations = [
    'Lagos', 'Abuja', 'Jos', 'Bauchi', 'Kano', 'Enugu', 'Oyo', 'Nasarawa', 'Ondo', 'Benin',
    'Kaduna', 'Akwa-Ibom', 'Katsina',
];

function field(string $key, int $max): string
{
    $value = isset($_POST[$key]) ? trim((string) $_POST[$key]) : '';
    $value = preg_replace('/[\r\n\t]+/', ' ', $value);
    return function_exists('mb_substr') ? mb_substr($value, 0, $max) : substr($value, 0, $max);
}

$entry = [
    'name'             => field('name', 120),
    'phone'            => field('phone', 30),
    'email'            => field('email', 160),
    'tradeArea'        => field('tradeArea', 120),
    'trainingLocation' => field('trainingLocation', 40),
];

if ($entry['name'] === '' || $entry['phone'] === '' || !filter_var($entry['email'], FILTER_VALIDATE_EMAIL)
    || !preg_match('/^[0-9+()\s-]{7,30}$/', $entry['phone'])
    || !in_array($entry['tradeArea'], $tradeAreas, true)
    || !in_array($entry['trainingLocation'], $locations, true)) {
    respond(422, ['ok' => false, 'error' => 'Please complete all fields correctly.']);
}

$submittedAt = date('Y-m-d H:i:s');

$body = "New registration for the TSDP Next Training (2027 Cohort)\n\n"
    . "Name: {$entry['name']}\n"
    . "Phone No: {$entry['phone']}\n"
    . "Email: {$entry['email']}\n"
    . "Trade Area: {$entry['tradeArea']}\n"
    . "Training Location: {$entry['trainingLocation']}\n"
    . "Submitted: {$submittedAt}\n";
$headers = 'From: TSDP Registration <' . TSDP_FROM . ">\r\n"
    . 'Reply-To: ' . $entry['email'] . "\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n";
$subject = 'TSDP 2027 Cohort Registration - ' . $entry['name'];
$mailed = @mail(TSDP_RECIPIENTS, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);

if (!$mailed) {
    respond(500, ['ok' => false, 'error' => 'Registration could not be submitted.']);
}

respond(200, ['ok' => true]);
