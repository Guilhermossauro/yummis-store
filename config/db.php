<?php
// Configurações do Banco de Dados Local (XAMPP)
$host = 'localhost';
$db   = 'lyumios_supply'; // O banco que você acabou de criar
$user = 'root';           // Usuário padrão do XAMPP
$pass = '';               // No XAMPP, a senha do root é vazia por padrão
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, 
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
     
     // Remova ou comente a linha abaixo depois de testar!
     // echo "<!-- Conexão com o banco lyumios_supply bem-sucedida! -->"; 

} catch (\PDOException $e) {
     // Mostra o erro exato na tela para facilitar o debug local
     die("Erro ao conectar com o banco local: " . $e->getMessage());
}
?>