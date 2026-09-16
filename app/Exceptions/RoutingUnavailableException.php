<?php

namespace App\Exceptions;

use Exception;

/**
 * Dilempar ketika Routing Engine gagal dihubungi, mengembalikan error,
 * atau belum dikonfigurasi (ROUTING_API_KEY kosong, dst).
 */
class RoutingUnavailableException extends Exception {}
