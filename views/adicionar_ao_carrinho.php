<?php
session_start();
require_once('../config/conexao.php');

// Verifica se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    echo "Você precisa estar logado para adicionar produtos ao carrinho.";
    exit;
}

$userId = $_SESSION['user_id'];
$produtoId = $_POST['produto_id'];
$quantidade = $_POST['quantidade'];

// Verifica se o produto já está no carrinho do usuário
$stmt = $pdo->prepare("SELECT * FROM carrinho WHERE usuario_id = :usuario_id AND produto_id = :produto_id");
$stmt->execute(['usuario_id' => $userId, 'produto_id' => $produtoId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if ($item) {
    // Atualiza a quantidade do produto no carrinho
    $novaQuantidade = $item['quantidade'] + $quantidade;
    $updateStmt = $pdo->prepare("UPDATE carrinho SET quantidade = :quantidade WHERE id = :id");
    $updateStmt->execute(['quantidade' => $novaQuantidade, 'id' => $item['id']]);
} else {
    // Adiciona o produto ao carrinho
    $insertStmt = $pdo->prepare("INSERT INTO carrinho (usuario_id, produto_id, quantidade) VALUES (:usuario_id, :produto_id, :quantidade)");
    $insertStmt->execute(['usuario_id' => $userId, 'produto_id' => $produtoId, 'quantidade' => $quantidade]);
}

echo "Produto adicionado ao carrinho com sucesso.";
header('Location: produtos.php'); // Redireciona de volta para a lista de produtos
exit;
?>