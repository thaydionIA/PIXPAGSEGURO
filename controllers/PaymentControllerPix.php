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
        // Captura o user_id da sessão
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

        // Calcula o valor total da compra e prepara os itens para o pagamento
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

        // Busca o usuário no banco de dados
        $user = $this->usercontrollerpix->getUserById($userId);
        if (!$user) {
            echo "Usuário não encontrado.";
            return;
        }

        // Busca o endereço do usuário
        $address = $this->addressControllerpix->getAddressByUserId($userId);
        if (!$address) {
            echo "Endereço não encontrado.";
            return;
        }

        // Define o token e a URL (produção ou sandbox)
        define('TOKEN', '279c9a74-9859-45cf-a74b-c336795236940fcf1fe54a438803fd2c2d6d9e80b69b3122-2b98-4396-a7b9-23aed7fc468c');
        define('URL', 'https://sandbox.api.pagseguro.com/orders');

        // Define os campos a serem enviados ao PagSeguro
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
                        'value' => intval($total * 100) // Total convertido para centavos
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

        // Requisição de pagamento via cURL
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

        // Debug: Verificando resposta da API
        var_dump($response);

        $response = json_decode($response);

        // Verifica se o QR Code foi gerado corretamente
        if (isset($response->qr_codes[0]->links[0]->href)) {
            echo "<div id='qrcode'>";
            echo "<img src='" . $response->qr_codes[0]->links[0]->href . "' alt='Qrcode Pix'>";
            echo "</div>";
        } else {
            echo "<p>Erro ao gerar o QR Code.</p>";
        }
    }
}

// Exemplo de uso
$productcontrollerpix = new ProductControllerPix($pdo);
$usercontrollerpix = new UserControllerPix($pdo);
$addressControllerpix = new AddressControllerPix($pdo);

// Instanciando o PayController
$payController = new PayController($pdo, $productcontrollerpix, $usercontrollerpix, $addressControllerpix);

// Chamada para criar o pagamento baseado no carrinho do usuário
$payController->createPayment();
?>