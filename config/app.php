<?php
declare(strict_types=1);

/**
 * Configuración Global de la Aplicación
 * Juris Contable - Escritorio Contable y Tributario (SENIAT / LOTTT)
 */

date_default_timezone_set('America/Caracas');

if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Juris Contable');
}

if (!defined('APP_SUBTITLE')) {
    define('APP_SUBTITLE', 'Sistema Multi-Empresa SENIAT / LOTTT');
}

if (!defined('APP_VERSION')) {
    define('APP_VERSION', '4.0.0-Integral');
}

if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/sistema_contable/public/index.php');
    $baseDir = rtrim(dirname($scriptName), '/');
    define('BASE_URL', $protocol . '://' . $host . $baseDir);
}

if (!function_exists('base_url')) {
    /**
     * Genera una URL absoluta basada en el punto de entrada public/
     */
    function base_url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        return $path === '' ? BASE_URL : BASE_URL . '/' . $path;
    }
}

if (!function_exists('route_url')) {
    /**
     * Genera una URL hacia un controlador y acción del Front Controller
     */
    function route_url(string $controller, string $action = 'index', array $params = []): string
    {
        $query = array_merge(['c' => $controller, 'a' => $action], $params);
        return BASE_URL . '/index.php?' . http_build_query($query);
    }
}

if (!function_exists('e')) {
    /**
     * Escapa cadenas para salida segura en HTML (Prevención XSS)
     */
    function e(string|int|float|null $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('formato_ves')) {
    /**
     * Formatea montos numéricos al estándar venezolano (Bs. 1.234,56)
     */
    function formato_ves(float|int|string|null $monto, bool $incluirSimbolo = true): string
    {
        $numero = is_numeric($monto) ? (float)$monto : 0.0;
        $formateado = number_format($numero, 2, ',', '.');
        return $incluirSimbolo ? 'Bs. ' . $formateado : $formateado;
    }
}

if (!function_exists('monto_en_letras_ves')) {
    /**
     * Convierte un monto numérico en Bolívares a su expresión legal en letras (Art. 106 LOTTT / Ley Cestaticket)
     */
    function monto_en_letras_ves(float|int|string|null $monto): string
    {
        $numero = abs(is_numeric($monto) ? (float)$monto : 0.0);
        $entero = (int)floor($numero);
        $centimos = (int)round(($numero - $entero) * 100);

        $unidades = ['', 'UN', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $especiales = [
            10 => 'DIEZ', 11 => 'ONCE', 12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE',
            15 => 'QUINCE', 16 => 'DIECISÉIS', 17 => 'DIECISIETE', 18 => 'DIECIOCHO', 19 => 'DIECINUEVE',
            20 => 'VEINTE', 21 => 'VEINTIÚN', 22 => 'VEINTIDÓS', 23 => 'VEINTITRÉS', 24 => 'VEINTICUATRO',
            25 => 'VEINTICINCO', 26 => 'VEINTISÉIS', 27 => 'VEINTISIETE', 28 => 'VEINTIOCHO', 29 => 'VEINTINUEVE'
        ];
        $decenas = [3 => 'TREINTA', 4 => 'CUARENTA', 5 => 'CINCUENTA', 6 => 'SESENTA', 7 => 'SETENTA', 8 => 'OCHENTA', 9 => 'NOVENTA'];
        $centenas = [
            1 => 'CIENTO', 2 => 'DOSCIENTOS', 3 => 'TRESCIENTOS', 4 => 'CUATROCIENTOS', 5 => 'QUINIENTOS',
            6 => 'SEISCIENTOS', 7 => 'SETECIENTOS', 8 => 'OCHOCIENTOS', 9 => 'NOVECIENTOS'
        ];

        $convertirGrupo = static function (int $n) use ($unidades, $especiales, $decenas, $centenas): string {
            if ($n === 0) return '';
            if ($n === 100) return 'CIEN';
            $texto = '';
            $c = intdiv($n, 100);
            $resto = $n % 100;
            if ($c > 0) {
                $texto .= $centenas[$c] . ' ';
            }
            if ($resto > 0) {
                if (isset($especiales[$resto])) {
                    $texto .= $especiales[$resto];
                } elseif ($resto < 10) {
                    $texto .= $unidades[$resto];
                } else {
                    $d = intdiv($resto, 10);
                    $u = $resto % 10;
                    $texto .= $decenas[$d] . ($u > 0 ? ' Y ' . $unidades[$u] : '');
                }
            }
            return trim($texto);
        };

        if ($entero === 0) {
            $letras = 'CERO';
        } else {
            $millones = intdiv($entero, 1000000);
            $restoMillones = $entero % 1000000;
            $miles = intdiv($restoMillones, 1000);
            $unidadesGrupo = $restoMillones % 1000;

            $partes = [];
            if ($millones === 1) {
                $partes[] = 'UN MILLÓN';
            } elseif ($millones > 1) {
                $partes[] = $convertirGrupo($millones) . ' MILLONES';
            }

            if ($miles === 1) {
                $partes[] = 'MIL';
            } elseif ($miles > 1) {
                $partes[] = $convertirGrupo($miles) . ' MIL';
            }

            if ($unidadesGrupo > 0) {
                $partes[] = $convertirGrupo($unidadesGrupo);
            }

            $letras = implode(' ', $partes);
        }

        return sprintf('%s BOLÍVARES CON %02d/100 CÉNTIMOS', $letras, $centimos);
    }
}
