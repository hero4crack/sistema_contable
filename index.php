<?php
declare(strict_types=1);

/**
 * Redireccionador Raíz hacia el Front Controller en /public
 */
$queryString = !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: public/index.php' . $queryString);
exit;
