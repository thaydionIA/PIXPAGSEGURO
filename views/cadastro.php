<!-- cadastro.php -->
<?php
session_start(); // Inicia a sessão para feedback
require_once('../config/conexao.php');

// Verifica se o formulário de cadastro foi submetido
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Coleta os dados do formulário
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $senha = $_POST['senha'];
    $cpf = $_POST['cpf'];
    $dd = $_POST['dd'];
    $telefone = $_POST['telefone'];
    $rua = $_POST['rua'];
    $numero = $_POST['numero'];
    $complemento = $_POST['complemento'];
    $bairro = $_POST['bairro'];
    $cidade = $_POST['cidade'];
    $estado = $_POST['estado'];
    $cep = $_POST['cep'];

    // Verifica se o e-mail já está cadastrado
    $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existingUser) {
        $erro = 'E-mail já cadastrado.';
    } else {
        // Criptografa a senha
        $senhaHash = password_hash($senha, PASSWORD_BCRYPT);

        // Insere os dados do usuário na tabela usuarios
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, email, senha, cpf, dd, telefone) VALUES (:nome, :email, :senha, :cpf, :dd, :telefone)");
        $stmt->execute([
            'nome' => $nome,
            'email' => $email,
            'senha' => $senhaHash,
            'cpf' => $cpf,
            'dd' => $dd,
            'telefone' => $telefone
        ]);

        // Obtém o ID do usuário recém-criado
        $userId = $pdo->lastInsertId();

        // Insere os dados de endereço na tabela enderecos_entrega
        $stmt = $pdo->prepare("INSERT INTO enderecos_entrega (rua, numero, complemento, bairro, cidade, estado, cep, usuario_id) VALUES (:rua, :numero, :complemento, :bairro, :cidade, :estado, :cep, :usuario_id)");
        $stmt->execute([
            'rua' => $rua,
            'numero' => $numero,
            'complemento' => $complemento,
            'bairro' => $bairro,
            'cidade' => $cidade,
            'estado' => $estado,
            'cep' => $cep,
            'usuario_id' => $userId
        ]);

        // Redireciona para a página de login com mensagem de sucesso
        $_SESSION['mensagem'] = 'Cadastro realizado com sucesso! Faça login para continuar.';
        header('Location: login.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro de Usuário</title>
</head>

<body>
    <h1>Cadastro de Usuário</h1>
    <?php if (isset($erro)) echo "<p style='color:red;'>$erro</p>"; ?>
    <form action="cadastro.php" method="POST">
        <h2>Dados Pessoais</h2>
        <label for="nome">Nome:</label>
        <input type="text" name="nome" id="nome" required>
        <br>
        <label for="email">E-mail:</label>
        <input type="email" name="email" id="email" required>
        <br>
        <label for="senha">Senha:</label>
        <input type="password" name="senha" id="senha" required>
        <br>
        <label for="cpf">CPF:</label>
        <input type="text" name="cpf" id="cpf" required>
        <br>
        <label for="dd">Código de Área (DD):</label>
        <input type="text" name="dd" id="dd" required>
        <br>
        <label for="telefone">Telefone:</label>
        <input type="text" name="telefone" id="telefone" required>
        <br>

        <h2>Endereço de Entrega</h2>
        <label for="rua">Rua:</label>
        <input type="text" name="rua" id="rua" required>
        <br>
        <label for="numero">Número:</label>
        <input type="text" name="numero" id="numero" required>
        <br>
        <label for="complemento">Complemento:</label>
        <input type="text" name="complemento" id="complemento">
        <br>
        <label for="bairro">Bairro:</label>
        <input type="text" name="bairro" id="bairro" required>
        <br>
        <label for="cidade">Cidade:</label>
        <input type="text" name="cidade" id="cidade" required>
        <br>
        <label for="estado">Estado:</label>
        <input type="text" name="estado" id="estado" required>
        <br>
        <label for="cep">CEP:</label>
        <input type="text" name="cep" id="cep" required>
        <br>

        <button type="submit">Cadastrar</button>
    </form>
</body>

</html>