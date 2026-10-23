<?php
/**
 * Conexión a la base de datos (PDO + MySQL).
 *
 * Uso desde cualquier modelo o controlador:
 *
 *     require_once __DIR__ . '/conexion.php';
 *     $pdo = Conexion::obtener();
 *     $stmt = $pdo->prepare('SELECT * FROM alumnos WHERE id = :id');
 *     $stmt->execute([':id' => $id]);
 *     $alumno = $stmt->fetch();
 *
 * Los datos de conexión se leen del archivo .env que está en la raíz
 * del proyecto (fuera de front/, back/ y bd/).
 */

class Conexion
{
    private static ?PDO $pdo = null;
    private static ?array $env = null;

    /** Evita instanciar la clase: todo es estático. */
    private function __construct() {}

    /**
     * Devuelve la conexión única (singleton) a la BD.
     */
    public static function obtener(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::conectar();
            self::aplicarUsuarioDeSesion();
        }
        return self::$pdo;
    }

    /**
     * Le dice a MySQL qué usuario de la app está operando, para que los
     * triggers lo guarden en bitacora.usuario_app (variable @usuario_app).
     * Se llama sola si hay una sesión iniciada con $_SESSION['usuario_correo'];
     * también se puede llamar manualmente (por ejemplo, justo después del login).
     */
    public static function setUsuarioApp(?string $correo): void
    {
        $stmt = self::obtener()->prepare('SET @usuario_app = :correo');
        $stmt->execute([':correo' => $correo]);
    }

    /* ------------------------------------------------------------------ */

    private static function conectar(): PDO
    {
        $env = self::cargarEnv();

        $host = $env['DB_HOST'] ?? 'localhost';
        $port = $env['DB_PORT'] ?? '3306';
        $name = $env['DB_NAME'] ?? '';
        $user = $env['DB_USER'] ?? 'root';
        $pass = $env['DB_PASS'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";

        try {
            return new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // errores como excepciones
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // filas como arreglos asociativos
                PDO::ATTR_EMULATE_PREPARES   => false,                    // consultas preparadas reales
            ]);
        } catch (PDOException $e) {
            // Se registra el detalle en el log del servidor, pero al usuario
            // no se le muestran credenciales ni rutas.
            error_log('[Conexion] ' . $e->getMessage());
            throw new RuntimeException('No se pudo conectar con la base de datos.');
        }
    }

    private static function aplicarUsuarioDeSesion(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['usuario_correo'])) {
            self::setUsuarioApp($_SESSION['usuario_correo']);
        }
    }

    /**
     * Lee el .env de la raíz del proyecto (una carpeta arriba de back/).
     * Formato: CLAVE=valor, una por línea. Se ignoran líneas vacías y
     * las que empiezan con #.
     */
    private static function cargarEnv(): array
    {
        if (self::$env !== null) {
            return self::$env;
        }

        $ruta = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';

        if (!is_readable($ruta)) {
            error_log("[Conexion] No se encontró el archivo .env en: {$ruta}");
            throw new RuntimeException('Falta el archivo de configuración .env.');
        }

        $datos = [];
        $lineas = file($ruta, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lineas as $linea) {
            $linea = trim($linea);

            if ($linea === '' || $linea[0] === '#' || strpos($linea, '=') === false) {
                continue;
            }

            [$clave, $valor] = explode('=', $linea, 2);
            $clave = trim($clave);
            $valor = trim($valor);

            // Quita comillas opcionales: DB_PASS="abc" o DB_PASS='abc'
            if (strlen($valor) >= 2 && in_array($valor[0], ['"', "'"], true) && $valor[-1] === $valor[0]) {
                $valor = substr($valor, 1, -1);
            }

            $datos[$clave] = $valor;
        }

        return self::$env = $datos;
    }
}
