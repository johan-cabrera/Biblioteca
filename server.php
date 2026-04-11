<?php
set_time_limit(0);

require __DIR__ . '/vendor/autoload.php';

use React\Http\HttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;
use React\MySQL\Factory;
use React\Promise\PromiseInterface;

// Configuración de la base de datos
$dbHost = getenv('MYSQL_HOST') ?: '127.0.0.1';
$dbPort = getenv('MYSQL_PORT') ?: 3306;
$dbUser = getenv('MYSQL_USER') ?: 'root';
$dbPass = getenv('MYSQL_PASS') ?: '';
$dbName = getenv('MYSQL_DB') ?: 'biblioteca_db';

$loop = Loop::get();
$factory = new Factory($loop);
$dbUrl = sprintf('%s:%s@%s:%d/%s', $dbUser, $dbPass, $dbHost, $dbPort, $dbName);
$connection = $factory->createLazyConnection($dbUrl);

function sanitizeInput(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function jsonResp(int $status, array $payload): Response {
    return new Response($status, ['Content-Type' => 'application/json'], json_encode($payload));
}

function parseJsonBody(ServerRequestInterface $request): array {
    $body = $request->getBody();
    if ($body->isSeekable()) {
        $body->rewind();
    }
    $content = (string)$body->getContents();
    $data = json_decode($content, true);
    if (!is_array($data)) {
        parse_str($content, $data);
    }
    return is_array($data) ? $data : [];
}

function sanitizeBook(array $data): array {
    return [
        'titulo' => sanitizeInput($data['titulo'] ?? ''),
        'autor' => sanitizeInput($data['autor'] ?? ''),
        'isbn' => sanitizeInput($data['isbn'] ?? ''),
        'estado' => trim(strtolower($data['estado'] ?? 'disponible')),
    ];
}

function validateBook(array $book): ?Response {
    if ($book['titulo'] === '' || $book['autor'] === '' || $book['isbn'] === '' || !in_array($book['estado'], ['disponible', 'prestado'], true)) {
        return jsonResp(400, ['error' => 'Título, autor, ISBN y estado válido son obligatorios.']);
    }
    return null;
}

// Logica del servidor
$server = new HttpServer(function (ServerRequestInterface $request) use ($connection) {
    
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

    if ($path === '/data') {
        if ($method !== 'GET') {
            return jsonResp(405, ['error' => 'Método no permitido']);
        }

        return $connection->query('SELECT id, titulo, autor, isbn, estado FROM libros ORDER BY id ASC')
            ->then(function ($result) {
                return jsonResp(200, $result->resultRows ?? []);
            }, function ($error) {
                return jsonResp(500, ['error' => 'Error en la consulta: ' . $error->getMessage()]);
            });
    }

    if (preg_match('~^/books(?:/([0-9]+))?$~', $path, $matches)) {
        $bookId = isset($matches[1]) ? (int)$matches[1] : null;

        if ($method === 'POST' && $bookId === null) {
            $book = sanitizeBook(parseJsonBody($request));
            if ($resp = validateBook($book)) {
                return $resp;
            }

            return $connection->query('INSERT INTO libros (titulo, autor, isbn, estado) VALUES (?, ?, ?, ?)', [$book['titulo'], $book['autor'], $book['isbn'], $book['estado']])
                ->then(function ($result) {
                    return jsonResp(201, ['status' => 'success', 'id' => $result->insertId]);
                }, function ($error) {
                    return jsonResp(500, ['error' => 'Error interno al insertar el libro: ' . $error->getMessage()]);
                });
        }

        if ($bookId !== null && $method === 'PUT') {
            $book = sanitizeBook(parseJsonBody($request));
            if ($resp = validateBook($book)) {
                return $resp;
            }

            return $connection->query('UPDATE libros SET titulo = ?, autor = ?, isbn = ?, estado = ? WHERE id = ?', [$book['titulo'], $book['autor'], $book['isbn'], $book['estado'], $bookId])
                ->then(function () {
                    return jsonResp(200, ['status' => 'success']);
                }, function ($error) {
                    return jsonResp(500, ['error' => 'Error interno al actualizar el libro: ' . $error->getMessage()]);
                });
        }

        if ($bookId !== null && $method === 'DELETE') {
            return $connection->query('DELETE FROM libros WHERE id = ?', [$bookId])
                ->then(function () {
                    return jsonResp(200, ['status' => 'success']);
                }, function ($error) {
                    return jsonResp(500, ['error' => 'Error interno al eliminar el libro: ' . $error->getMessage()]);
                });
        }

        return jsonResp(405, ['error' => 'Método no permitido para /books']);
    }

    if ($path === '/contact') {
        if ($method === 'GET') {
            return new Response(200, ['Content-Type' => 'text/html'], file_get_contents(__DIR__ . '/public/contact.html'));
        }

        if ($method === 'POST') {
            $data = parseJsonBody($request);
            $nombre = sanitizeInput($data['nombre'] ?? '');
            $correo = sanitizeInput($data['email'] ?? '');
            $titulo = sanitizeInput($data['titulo'] ?? '');
            $autor = sanitizeInput($data['autor'] ?? '');
            $informacion = sanitizeInput($data['mensaje'] ?? '');

            if ($nombre === '' || $correo === '' || $titulo === '') {
                return jsonResp(400, ['error' => 'Por favor completa los campos Nombre, Correo y Título del libro solicitado.']);
            }

            return $connection->query('INSERT INTO contactos (nombre_lector, correo_lector, titulo_solicitado, autor_solicitado, informacion_adicional) VALUES (?, ?, ?, ?, ?)', [$nombre, $correo, $titulo, $autor, $informacion])
                ->then(function () {
                    return jsonResp(200, ['status' => 'success']);
                }, function ($error) {
                    return jsonResp(500, ['error' => 'Error interno al guardar la solicitud: ' . $error->getMessage()]);
                });
        }
    }

    // Manejo de error cuando se busca una ruta que no existe
    return new Response(404, ['Content-Type' => 'text/plain'], "404 Not Found");
});

// Configurar el event loop y el puerto
$loop = Loop::get();
$socket = new React\Socket\SocketServer('0.0.0.0:8080', [], $loop);
$server->listen($socket);

echo "Servidor de la Biblioteca corriendo en http://localhost:8080" . PHP_EOL;

// Inicializar el bucle
$loop->run();