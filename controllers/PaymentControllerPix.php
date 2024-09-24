<?php
session_start();
require_once('../config/conexao.php');
require_once 'ProductControllerPix.php'; 
require_once 'userControllerPix.php'; 
require_once 'AddressControllerPix.php'; 

// Ativando a exibição de erros
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

class PayController {
    private $pdo;
    private $productcontrollerpix;
    private $usercontrollerpix;
    private $addressControllerpix;

    public function __construct($pdo, $productcontrollerpix, $usercontrollerpix, $addressControllerpix) {
        $this->pdo = $pdo;
        $this->productcontrollerpix = $productcontrollerpix;
        $this->usercontrollerpix = $usercontrollerpix;
        $this->addressControllerpix = $addressControllerpix;
    }

    public function createPayment() {
        if (!isset($_SESSION['user_id'])) {
            echo "Erro: Usuário não está logado.";
            return;
        }

        $userId = $_SESSION['user_id'];

        // Busca os itens do carrinho do usuário
        $stmt = $this->pdo->prepare("SELECT c.quantidade, p.* 
                                     FROM carrinho c
                                     JOIN produtos p ON c.produto_id = p.id
                                     WHERE c.usuario_id = :usuario_id");
        $stmt->execute(['usuario_id' => $userId]);
        $carrinho = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($carrinho)) {
            echo "Carrinho vazio. Adicione produtos antes de finalizar a compra.";
            return;
        }

        $total = 0;
        $items = [];
        foreach ($carrinho as $item) {
            $total += $item['preco'] * $item['quantidade'];
            $items[] = [
                'reference_id' => "item-{$item['id']}",
                'name' => $item['nome'],
                'quantity' => $item['quantidade'],
                'unit_amount' => intval($item['preco'] * 100), // Convertendo para centavos
            ];
        }

        $user = $this->usercontrollerpix->getUserById($userId);
        if (!$user) {
            echo "Usuário não encontrado.";
            return;
        }

        $address = $this->addressControllerpix->getAddressByUserId($userId);
        if (!$address) {
            echo "Endereço não encontrado.";
            return;
        }

        define('TOKEN', '279c9a74-9859-45cf-a74b-c336795236940fcf1fe54a438803fd2c2d6d9e80b69b3122-2b98-4396-a7b9-23aed7fc468c');
        define('URL', 'https://sandbox.api.pagseguro.com/orders');

        $data = [
            'reference_id' => "pedido-{$userId}-" . time(),
            'customer' => [
                'name' => $user['nome'], 
                'email' => $user['email'], 
                'tax_id' => $user['cpf'], 
                'phones' => [
                    [
                        'country' => '55',
                        'area' => $user['dd'], 
                        'number' => $user['telefone'], 
                        'type' => 'MOBILE'
                    ]
                ]
            ],
            'items' => $items,
            'qr_codes' => [
                [
                    'amount' => [
                        'value' => intval($total * 100)
                    ],
                    'expiration' => '2023-03-29T20:15:59-03:00'
                ]
            ],
            'shipping' => [
                'address' => [
                    'street' => $address['rua'],
                    'number' => $address['numero'],
                    'complement' => $address['complemento'],
                    'locality' => $address['bairro'],
                    'city' => $address['cidade'],
                    'region_code' => $address['estado'],
                    'country' => $address['pais'],
                    'postal_code' => $address['cep']
                ]
            ],
            'notification_urls' => [
                'https://meusite.com/notificacoes'
            ]
        ];

        $data = json_encode($data);

        $curl = curl_init(URL);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . TOKEN,
            'Content-Type: application/json'
        ]);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($curl);
        curl_close($curl);

        $response = json_decode($response);

        // Verifica se o QR Code e a chave Pix foram gerados corretamente
        if (isset($response->qr_codes[0]->links[0]->href)) {
            $qrCodeUrl = $response->qr_codes[0]->links[0]->href;
            $pixKey = $response->qr_codes[0]->text;

            echo "<div id='qrcode'>";
            echo "<img src='" . $qrCodeUrl . "' alt='Qrcode Pix'>";
            echo "</div>";
            echo "<p>Chave Pix: <strong>" . $pixKey . "</strong></p>";

            // Remover o redirecionamento imediato e sugerir redirecionamento manual
            echo "<p>Após o pagamento, <a href='login.php'>clique aqui para continuar</a>.</p>";
        } else {
            echo "<p>Erro ao gerar o QR Code.</p>";
        }
    }
}

$productcontrollerpix = new ProductControllerPix($pdo);
$usercontrollerpix = new UserControllerPix($pdo);
$addressControllerpix = new AddressControllerPix($pdo);

$payController = new PayController($pdo, $productcontrollerpix, $usercontrollerpix, $addressControllerpix);
$payController->createPayment();
?>