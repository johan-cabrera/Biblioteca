<?php
require __DIR__ . '/vendor/autoload.php';

use React\Http\HttpServer;
use React\Http\Message\Response;
use Psr\Http\Message\ServerRequestInterface;
use React\EventLoop\Loop;

// Configuración de la base de datos
$dbHost = getenv('MYSQL_HOST') ?: '127.0.0.1';
$dbPort = getenv('MYSQL_PORT') ?: 3306;
$dbUser = getenv('MYSQL_USER') ?: 'root';
$dbPass = getenv('MYSQL_PASS') ?: '';
$dbName = getenv('MYSQL_DB') ?: 'biblioteca_db';

function sanitizeInput(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Logica del servidor
$server = new HttpServer(function (ServerRequestInterface $request) use ($dbHost, $dbPort, $dbUser, $dbPass, $dbName) {
    
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
            return new Response(405, ['Content-Type' => 'application/json'], json_encode(['error' => 'Método no permitido']));
        }

        $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
        if ($mysqli->connect_errno) {
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error de conexión a la base de datos: ' . $mysqli->connect_error]));
        }

        $result = $mysqli->query('SELECT id, titulo, autor, isbn, estado FROM libros ORDER BY id ASC');
        if ($result === false) {
            $error = $mysqli->error;
            $mysqli->close();
            return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error en la consulta: ' . $error]));
        }

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        error_log('DEBUG /data rows count=' . count($rows) . ' first=' . json_encode($rows[0] ?? []));
        $result->free();
        $mysqli->close();

        return new Response(200, ['Content-Type' => 'application/json'], json_encode($rows));
    }

    if (preg_match('~^/books(?:/([0-9]+))?$~', $path, $matches)) {
        $bookId = isset($matches[1]) ? (int)$matches[1] : null;
        if ($method === 'POST' && $bookId === null) {
            $bodyStream = $request->getBody();
            if ($bodyStream->isSeekable()) {
                $bodyStream->rewind();
            }
            $body = $bodyStream->getContents();
            $data = json_decode($body, true);
            if (!is_array($data)) {
                parse_str($body, $data);
            }

            $titulo = sanitizeInput($data['titulo'] ?? '');
            $autor = sanitizeInput($data['autor'] ?? '');
            $isbn = sanitizeInput($data['isbn'] ?? '');
            $estado = trim(strtolower($data['estado'] ?? 'disponible'));
            if ($titulo === '' || $autor === '' || $isbn === '' || !in_array($estado, ['disponible', 'prestado'], true)) {
                return new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'Título, autor, ISBN y estado válido son obligatorios.']));
            }

            $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
            if ($mysqli->connect_errno) {
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error de conexión a la base de datos: ' . $mysqli->connect_error]));
            }

            $stmt = $mysqli->prepare('INSERT INTO libros (titulo, autor, isbn, estado) VALUES (?, ?, ?, ?)');
            if (!$stmt) {
                $error = $mysqli->error;
                $mysqli->close();
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno al preparar la inserción: ' . $error]));
            }
            $stmt->bind_param('ssss', $titulo, $autor, $isbn, $estado);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                $mysqli->close();
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno al insertar el libro: ' . $error]));
            }

            $insertId = $stmt->insert_id;
            $stmt->close();
            $mysqli->close();

            return new Response(201, ['Content-Type' => 'application/json'], json_encode(['status' => 'success', 'id' => $insertId]));
        }

        if ($bookId !== null && $method === 'PUT') {
            $bodyStream = $request->getBody();
            if ($bodyStream->isSeekable()) {
                $bodyStream->rewind();
            }
            $body = $bodyStream->getContents();
            $data = json_decode($body, true);
            if (!is_array($data)) {
                parse_str($body, $data);
            }

            $titulo = sanitizeInput($data['titulo'] ?? '');
            $autor = sanitizeInput($data['autor'] ?? '');
            $isbn = sanitizeInput($data['isbn'] ?? '');
            $estado = trim(strtolower($data['estado'] ?? ''));
            if ($titulo === '' || $autor === '' || $isbn === '' || !in_array($estado, ['disponible', 'prestado'], true)) {
                return new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'Título, autor, ISBN y estado válido son obligatorios.']));
            }

            $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
            if ($mysqli->connect_errno) {
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error de conexión a la base de datos: ' . $mysqli->connect_error]));
            }

            $stmt = $mysqli->prepare('UPDATE libros SET titulo = ?, autor = ?, isbn = ?, estado = ? WHERE id = ?');
            if (!$stmt) {
                $error = $mysqli->error;
                $mysqli->close();
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno al preparar la actualización: ' . $error]));
            }
            $stmt->bind_param('ssssi', $titulo, $autor, $isbn, $estado, $bookId);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                $mysqli->close();
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno al actualizar el libro: ' . $error]));
            }

            $stmt->close();
            $mysqli->close();
            return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'success']));
        }

        if ($bookId !== null && $method === 'DELETE') {
            $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
            if ($mysqli->connect_errno) {
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error de conexión a la base de datos: ' . $mysqli->connect_error]));
            }

            $stmt = $mysqli->prepare('DELETE FROM libros WHERE id = ?');
            if (!$stmt) {
                $error = $mysqli->error;
                $mysqli->close();
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno al preparar la eliminación: ' . $error]));
            }
            $stmt->bind_param('i', $bookId);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                $mysqli->close();
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno al eliminar el libro: ' . $error]));
            }

            $stmt->close();
            $mysqli->close();
            return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'success']));
        }

        return new Response(405, ['Content-Type' => 'application/json'], json_encode(['error' => 'Método no permitido para /books']));
    }

    if ($path === '/contact') {
        if ($method === 'GET') {
            // Entregar el HTML
            return new Response(200, ['Content-Type' => 'text/html'], file_get_contents(__DIR__ . '/public/contact.html'));
        }

        if ($method === 'POST') {
            $bodyStream = $request->getBody();
            if ($bodyStream->isSeekable()) {
                $bodyStream->rewind();
            }
            $body = $bodyStream->getContents();
            $data = json_decode($body, true);
            if (!is_array($data)) {
                parse_str($body, $data);
            }

            $nombre = sanitizeInput($data['nombre'] ?? '');
            $correo = sanitizeInput($data['email'] ?? '');
            $titulo = sanitizeInput($data['titulo'] ?? '');
            $autor = sanitizeInput($data['autor'] ?? '');
            $informacion = sanitizeInput($data['mensaje'] ?? '');

            if ($nombre === '' || $correo === '' || $titulo === '') {
                return new Response(400, ['Content-Type' => 'application/json'], json_encode(['error' => 'Por favor completa los campos Nombre, Correo y Título del libro solicitado.']));
            }

            $mysqli = new mysqli($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
            if ($mysqli->connect_errno) {
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error de conexión a la base de datos: ' . $mysqli->connect_error]));
            }

            $stmt = $mysqli->prepare('INSERT INTO contactos (nombre_lector, correo_lector, titulo_solicitado, autor_solicitado, informacion_adicional) VALUES (?, ?, ?, ?, ?)');
            if (!$stmt) {
                $error = $mysqli->error;
                $mysqli->close();
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno al preparar la solicitud: ' . $error]));
            }

            $stmt->bind_param('sssss', $nombre, $correo, $titulo, $autor, $informacion);
            if (!$stmt->execute()) {
                $error = $stmt->error;
                $stmt->close();
                $mysqli->close();
                return new Response(500, ['Content-Type' => 'application/json'], json_encode(['error' => 'Error interno al guardar la solicitud: ' . $error]));
            }

            $stmt->close();
            $mysqli->close();

            return new Response(200, ['Content-Type' => 'application/json'], json_encode(['status' => 'success']));
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