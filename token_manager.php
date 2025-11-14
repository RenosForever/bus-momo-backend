<?php
// --- Activer l'affichage des erreurs pour le debug ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'utils.php';
loadEnv(__DIR__ . '/.env');

function getAccessToken()
{
    $tokenFile = __DIR__ . '/token.json';

    // 🔹 1️⃣ Vérifier si un token valide existe déjà
    if (file_exists($tokenFile)) {
        $data = json_decode(file_get_contents($tokenFile), true);
        if ($data && isset($data['expires_at']) && $data['expires_at'] > time()) {
            return $data['access_token'];
        }
    }

    // 🔹 2️⃣ Générer un nouveau token
    $auth = base64_encode($_ENV['API_USER'] . ':' . $_ENV['API_KEY']);
    $url = $_ENV['BASE_URL'] . "/collection/token/";

    $headers = [
        "Authorization: Basic $auth",
        "Ocp-Apim-Subscription-Key: " . $_ENV['PRIMARY_KEY'],
        "Content-Type: application/json"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // ⚠️ Désactiver SSL uniquement en sandbox

    $result = curl_exec($ch);

    if ($result === false) {
        die("❌ Erreur de génération du token : " . curl_error($ch));
    }

    curl_close($ch);

    $response = json_decode($result, true);
    if (!isset($response['access_token'])) {
        die("❌ Erreur dans la réponse API : " . $result);
    }

    $accessToken = $response['access_token'];

    // 🔹 3️⃣ Sauvegarder le token avec expiration (~1h)
    file_put_contents($tokenFile, json_encode([
        'access_token' => $accessToken,
        'expires_at' => time() + 3500 // 58 minutes environ
    ]));

    return $accessToken;
}
?>
