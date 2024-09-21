<?php
session_start();
require_once('../config/conexao.php');
require_once '../controllers/userControllerPix.php';

// Inicializa o controlador de usuários
$userController = new UserControllerPix($pdo);

// Verifica se o formulário foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Busca o usuário pelo e-mail
    $user = $userController->getUserByEmail($email);

    // Verifica se o usuário foi encontrado e a senha está correta
    if ($user && password_verify($senha, $user['senha'])) {
        // Armazena o ID do usuário na sessão
        $_SESSION['user_id'] = $user['id'];
        echo "Login realizado com sucesso!";

        // Redireciona para a página de pagamento ou qualquer outra página desejada
        header('Location: produtos.php');
        exit;
    } else {
        echo "E-mail ou senha incorretos.";
    }
}
?>

<!-- Formulário de Login -->
<form method="POST" action="">
    <label for="email">E-mail:</label>
    <input type="email" id="email" name="email" required>
    <br>
    <label for="senha">Senha:</label>
    <input type="password" id="senha" name="senha" required>
    <br>
    <button type="submit">Login</button>
</form>