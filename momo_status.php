<?php
// Activer l'affichage des erreurs pour le debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';
require_once 'token_manager.php';
require_once 'utils.php';
loadEnv(__DIR__ . '/.env');

$referenceId = $_GET['id'] ?? '';
if (!$referenceId) die("Aucun ID fourni");

$accessToken = getAccessToken();

$options = [
    'http' => [
        'header'  => [
            "Authorization: Bearer $accessToken",
            "X-Target-Environment: " . $_ENV['TARGET_ENV'],
            "Ocp-Apim-Subscription-Key: " . $_ENV['PRIMARY_KEY']
        ],
        'method'  => 'GET'
    ]
];

$context = stream_context_create($options);
$url = $_ENV['BASE_URL'] . "/collection/v1_0/requesttopay/$referenceId";
$response = file_get_contents($url, false, $context);

if ($response === FALSE) die("Erreur lors de la vérification du paiement");

$data = json_decode($response, true);
$status = strtoupper($data['status'] ?? 'UNKNOWN');

// Mettre à jour la table payments
$stmt = $pdo->prepare("UPDATE payments SET status=? WHERE reference_id=?");
$stmt->execute([$status, $referenceId]);

// Log complet pour historique
$stmtLog = $pdo->prepare("INSERT INTO transactions_log (reference_id, status, raw_response) VALUES (?, ?, ?)");
$stmtLog->execute([$referenceId, $status, json_encode($data)]);

echo "📋 Paiement : $status";
?>
