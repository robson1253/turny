<?php
// Inclua o autoloader do Composer
require 'vendor/autoload.php';

// Verifica se a classe existe
if (class_exists('Endroid\QrCode\QrCode')) {
    echo "<h1>Classe Endroid\QrCode\QrCode encontrada!</h1>";
    
    // Pega todos os métodos da classe
    $methods = get_class_methods('Endroid\QrCode\QrCode');
    
    echo "<h2>Métodos disponíveis:</h2>";
    echo "<pre>";
    print_r($methods);
    echo "</pre>";

} else {
    echo "<h1>ERRO: Classe Endroid\QrCode\QrCode NÃO encontrada!</h1>";
    echo "<p>Verifique se a biblioteca está instalada corretamente e se o autoloader do Composer está funcionando.</p>";
}
