<?php
session_start();
require_once('../config/conexao.php');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo "Você precisa estar logado para ver o carrinho.";
    exit;
}

$userId = $_SESSION['user_id'];

// Busca os itens do carrinho do usuário
$stmt = $pdo->prepare("SELECT c.id, p.nome, p.preco, c.quantidade 
                       FROM carrinho c
                       JOIN produtos p ON c.produto_id = p.id
                       WHERE c.usuario_id = :usuario_id");
$stmt->execute(['usuario_id' => $userId]);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calcula o total do carrinho
$total = 0;
foreach ($itens as $item) {
    $total += $item['preco'] * $item['quantidade'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Carrinho de Compras</title>
</head>

<body>
    <h1>Seu Carrinho</h1>
    <?php if (empty($itens)): ?>
    <p>O carrinho está vazio.</p>
    <?php else: ?>
    <table border="1">
        <tr>
            <th>Produto</th>
            <th>Preço</th>
            <th>Quantidade</th>
            <th>Total</th>
            <th>Ações</th>
        </tr>
        <?php foreach ($itens as $item): ?>
        <tr>
            <td><?php echo htmlspecialchars($item['nome']); ?></td>
            <td>R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
            <td><?php echo $item['quantidade']; ?></td>
            <td>R$ <?php echo number_format($item['preco'] * $item['quantidade'], 2, ',', '.'); ?></td>
            <td>
                <form action="atualizar_carrinho.php" method="POST" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    <input type="number" name="quantidade" value="<?php echo $item['quantidade']; ?>" min="1">
                    <button type="submit" name="acao" value="atualizar">Atualizar</button>
                </form>
                <form action="atualizar_carrinho.php" method="POST" style="display:inline;">
                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                    <button type="submit" name="acao" value="remover">Remover</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <p>Total do Carrinho: R$ <?php echo number_format($total, 2, ',', '.'); ?></p>
    <form action="../controllers/PaymentControllerPix.php" method="GET">
        <button type="submit">Finalizar Compra</button>
    </form>
    <?php endif; ?>
</body>

</html>