<?php
set_time_limit(0);

require __DIR__ . '/vendor/autoload.php';

use React\Http\HttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;

// Obtener el event loop
$loop = Loop::get();

// Logica del servidor
$server = new HttpServer(function (ServerRequestInterface $request) {
    
    // Obtener la ruta del usuario
    $method = $request->getMethod();
    $path = $request->getUri()->getPath();
    
    // Funcion para leer los archivos
    $serveFile = function($fileName, $contentType) {
        //URL
        $filePath = __DIR__ . '/public/' . $fileName;
        
        // Verificar si la ruta existe
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);

            // Si la ruta existe, retornar el contenido 
            return new Response(200, ['Content-Type' => $contentType], $content);
        }

        // Si la ruta no existe, dar un error 404
        return new Response(404, ['Content-Type' => 'text/plain'], "Archivo no encontrado");
    };

    // Ruta de inicio
    if ($path === '/' || $path === '/index.html') {
        return $serveFile('index.html', 'text/html');
    }

    // Ruta de estilos
    if ($path === '/style.css') {
        // Ajustamos la ruta si tu CSS está en public/css/style.css
        $cssPath = __DIR__ . '/public/css/style.css';
        if (file_exists($cssPath)) {
            return new Response(200, ['Content-Type' => 'text/css'], file_get_contents($cssPath));
        }
    }

    if ($path === '/contact') {
        if ($method === 'GET') {
            // Entregar el HTML
            return new Response(200, ['Content-Type' => 'text/html'], file_get_contents(__DIR__ . '/public/contact.html'));
        } 
        
        if ($method === 'POST') {
            // Recibir datos asíncronamente
            $data = json_decode((string)$request->getBody(), true);
            
            // Por ahora, simulamos una respuesta exitosa
            return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'success']));
        }
    }

    // Manejo de error cuando se busca una ruta que no existe
    return new Response(404, ['Content-Type' => 'text/plain'], "404 Not Found");
});

// Configurar el puerto y arrancar
$socket = new React\Socket\SocketServer('0.0.0.0:8080', [], $loop);
$server->listen($socket);

echo "Servidor de la Biblioteca corriendo en http://localhost:8080" . PHP_EOL;

// Inicializar el bucle
$loop->run();