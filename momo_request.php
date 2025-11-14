<?php
// --- Activation des erreurs pour le debug ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- Imports des fichiers ---
require_once 'db.php';
require_once 'token_manager.php';
require_once 'utils.php';

// Charger les variables d’environnement (.env)
loadEnv(__DIR__ . '/.env');

// Obtenir le token d’accès
$accessToken = getAccessToken();
if (!$accessToken) {
    die("❌ Impossible d'obtenir le token d'accès MoMo");
}

// --- Données du paiement ---
$phoneNumber = "46733123450"; // numéro test sandbox
$amount = 2000;
$currency = $_ENV['CURRENCY'] ?? 'EUR';
$referenceId = uniqid("Ref_");   // Identifiant unique pour la requête
$externalId = uniqid("Bus_");    // ID unique pour la transaction

// --- Enregistrer la requête dans la DB ---
try {
    $stmt = $pdo->prepare("
        INSERT INTO payments 
        (reference_id, external_id, phone_number, amount, currency, status, payer_message, payee_note) 
        VALUES (?, ?, ?, ?, ?, 'PENDING', ?, ?)
    ");
    $stmt->execute([
        $referenceId,
        $externalId,
        $phoneNumber,
        $amount,
        $currency,
        "Réservation bus test",
        "Paiement bus"
    ]);
} catch (PDOException $e) {
    die("❌ Erreur DB : " . $e->getMessage());
}

// --- Données à envoyer à l’API MoMo ---
$data = [
    "amount" => strval($amount),
    "currency" => $currency,
    "externalId" => $externalId,
    "payer" => [
        "partyIdType" => "MSISDN",
        "partyId" => $phoneNumber
    ],
    "payerMessage" => "Réservation bus",
    "payeeNote" => "Paiement bus"
];

// --- Envoi de la requête à MTN MoMo ---
$url = $_ENV['BASE_URL'] . "/collection/v1_0/requesttopay";
$headers = [
    "Authorization: Bearer $accessToken",
    "X-Reference-Id: $referenceId",
    "X-Target-Environment: " . $_ENV['TARGET_ENV'],
    "Ocp-Apim-Subscription-Key: " . $_ENV['PRIMARY_KEY'],
    "Content-Type: application/json",
    "X-Callback-Url: " . $_ENV['CALLBACK_URL']
];

// Utilisation de cURL (plus sûr sur InfinityFree)
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Désactivé pour sandbox

$response = curl_exec($ch);
if ($response === false) {
    die("❌ Erreur lors de la requête MoMo : " . curl_error($ch));
}
curl_close($ch);

// --- Résultat ---
echo "✅ Paiement envoyé !<br>";
echo "Référence : $referenceId<br>";
echo "Réponse API : $response";
?>
