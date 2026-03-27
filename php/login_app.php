<?php
// Endpoint de login para a App Android
// Recebe: email, password (POST)
// Responde: JSON com success, tipo, nome, message

header('Content-Type: application/json');
include 'config.php';

$response = array('success' => false, 'message' => '');

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    $response['message'] = 'Preencha todos os campos (email, password).';
    echo json_encode($response);
    exit;
}

$stmt = $conn->prepare("SELECT id, nome, email, password, tipo_utilizador FROM utilizadores WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if ($user && password_verify($password, $user['password'])) {
    $response['success'] = true;
    $response['message'] = 'Login bem-sucedido.';
    $response['nome'] = $user['nome'];
    $response['tipo'] = $user['tipo_utilizador'];
    $response['user_id'] = $user['id'];
} else {
    $response['message'] = 'Email ou password incorretos.';
}

$stmt->close();
$conn->close();
echo json_encode($response);
?>
