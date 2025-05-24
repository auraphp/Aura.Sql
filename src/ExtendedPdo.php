<?php
/**
 *
 * This file is part of Aura for PHP.
 *
 * @license https://opensource.org/licenses/MIT MIT
 *
 */
namespace Aura\Sql;

if (PHP_VERSION_ID < 80400) {
	require_once __DIR__ . '/ExtendedPdoPHP83.php';
} else {
	require_once __DIR__ . '/ExtendedPdoPHP84.php';
}