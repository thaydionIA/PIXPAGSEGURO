<?php
session_start();
require_once('../config/conexao.php');
require_once '../controllers/ProductControllerPix.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo "Você precisa estar logado para ver os produtos.";
    exit;
}

$productController = new ProductControllerPix($pdo);
$produtos = $productController->getAllProducts(); // Supondo que este método exista para buscar todos os produtos

?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Produtos</title>
</head>

<body>
    <h1>Lista de Produtos</h1>
    <ul>
        <?php foreach ($produtos as $produto): ?>
        <li>
            <h2><?php echo htmlspecialchars($produto['nome']); ?></h2>
            <p>Descrição: <?php echo htmlspecialchars($produto['descricao']); ?></p>
            <p>Preço: R$ <?php echo number_format($produto['preco'], 2, ',', '.'); ?></p>
            <form action="adicionar_ao_carrinho.php" method="POST">
                <input type="hidden" name="produto_id" value="<?php echo $produto['id']; ?>">
                <label for="quantidade">Quantidade:</label>
                <input type="number" name="quantidade" value="1" min="1">
                <button type="submit">Adicionar ao Carrinho</button>
            </form>
        </li>
        <?php endforeach; ?>
    </ul>
</body>

</html>