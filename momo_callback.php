<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'db.php';

// Lire le POST JSON envoyé par MoMo
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Vérifier si les champs existent
if(isset($data['externalId']) && isset($data['status'])) {

    $externalId = $data['externalId'];
    $status = $data['status'];

    // Mettre à jour la DB
    $stmt = $pdo->prepare("UPDATE payments SET status = ? WHERE external_id = ?");
    $stmt->execute([$status, $externalId]);

    // Optionnel : enregistrer la réponse dans un log pour debug
    file_put_contents('callback.log', $input . "\n", FILE_APPEND);

    echo "Callback reçu ✅";
} else {
    echo "Données invalides";
}
?>
