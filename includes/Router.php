<?php
class Router {
    private array $routes = [];

    private string $basePath = '';

    public function __construct(string $basePath = '') {
        $this->basePath = rtrim($basePath, '/');
    }

    public function add(string $method, string $path, string $handler, array $middlewares = []): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middlewares' => $middlewares
        ];
    }

    public function get(string $path, string $handler, array $middlewares = []): void {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, string $handler, array $middlewares = []): void {
        $this->add('POST', $path, $handler, $middlewares);
    }

    public function dispatch(): void {
        $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $requestMethod = $_SERVER['REQUEST_METHOD'];

        // Hapus base path dari request path jika ada
        if ($this->basePath && strpos($requestPath, $this->basePath) === 0) {
            $requestPath = substr($requestPath, strlen($this->basePath));
        }

        // Jika path kosong setelah dihapus, anggap sebagai root '/'
        if (empty($requestPath)) {
            $requestPath = '/';
        }

        // Normalisasi path jika URL diakhiri dengan slash (kecuali untuk root)
        if (strlen($requestPath) > 1) {
            $requestPath = rtrim($requestPath, '/');
        }

        foreach ($this->routes as $route) {
            // Convert route path like /users/{id} to a regex: #^/users/(?P<id>[^/]+)$#
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $regex = '#^' . $pattern . '$#';

            if (preg_match($regex, $requestPath, $matches) && $route['method'] === $requestMethod) {
                
                // Ekstrak parameter dari URL (misal: id dari /users/5/edit)
                // dan masukkan ke dalam $_GET agar bisa diakses oleh handler
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $_GET[$key] = $value;
                    }
                }

                // Jalankan middleware untuk pemeriksaan hak akses
                foreach ($route['middlewares'] as $middlewareName) {
                    // Convert snake_case (like 'log_access') to PascalCase (like 'LogAccess')
                    $className = str_replace(' ', '', ucwords(str_replace('_', ' ', $middlewareName))) . 'Middleware';
                    $filePath = PROJECT_ROOT . '/includes/middlewares/' . $className . '.php';

                    if (file_exists($filePath)) {
                        require_once $filePath;

                        // Instantiate middleware with necessary dependencies
                        if ($className === 'LogAccessMiddleware') {
                            $middlewareInstance = new $className($requestPath);
                        } elseif ($className === 'AuthMiddleware' || $className === 'GuestMiddleware') {
                            $middlewareInstance = new $className($this->basePath);
                        } else {
                            $middlewareInstance = new $className($this);
                        }

                        $middlewareInstance->handle();
                    } else {
                        $this->abort(500, "Middleware file not found: {$filePath}");
                    }
                }

                // Jika semua middleware lolos, jalankan handler (file PHP)
                $handlerPath = PROJECT_ROOT . '/' . ltrim($route['handler'], '/');
                if (file_exists($handlerPath)) {
                    require $handlerPath;
                    return;
                } else {
                    // Show the full path for easier debugging
                    $this->abort(500, "Handler file not found: {$handlerPath}");
                }
            }
        }

        $this->abort(404, "Halaman tidak ditemukan untuk path: '{$requestPath}'");
    }

    public function abort(int $code = 404, string $message = 'Not Found'): void {
        http_response_code($code);

        $viewPath = PROJECT_ROOT . "/views/errors/{$code}.php";

        if (file_exists($viewPath)) {
            // Membuat variabel $basePath dan $message tersedia untuk file view
            $basePath = $this->basePath;
            require $viewPath;
        } else {
            // Fallback jika file view spesifik tidak ditemukan, coba 404.php
            $fallbackPath = PROJECT_ROOT . "/views/errors/404.php";
            if (file_exists($fallbackPath)) {
                $basePath = $this->basePath;
                require $fallbackPath;
            } else {
                echo "<h1>{$code} - {$message}</h1>";
            }
        }
        exit;
    }
}