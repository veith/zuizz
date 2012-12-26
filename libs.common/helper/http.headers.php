<?php
/*
 * Header definitionen für ZU::header
 * Detailierte Information findest du unter http://de.wikipedia.org/wiki/HTTP-Statuscode
 */

$GLOBALS['ZUIZZHEADERCODES'] = array(
    100 => "Continue",
    101 => "Switching Protocols",
    102 => "Processing",
    118 => "Connection timed out",

    200 => "Ok",
    201 => "Created",
    202 => "Acepted",
    203 => "Non-Authoritative Information",
    204 => "No Content",

    300 => "Multiple Choices",
    301 => "Moved Permanently",
    303 => "See Other",
    304 => "Not Modified",
    305 => "Use Proxy",
    307 => "Temporary Redirect",

    400 => "Bad Request",
    401 => "Unauthorized",
    402 => "Payment Required",
    403 => "Forbidden",
    404 => "Not Found",
    405 => "Method Not Allowed",
    422 => "Unprocessable Entity",

    500 => "Internal Server Error",
    501 => "Not Implemented",
    502 => "Bad Gateway",
    503 => "Service Unavailable",
    504 => "Gateway Time-out",
    505 => "HTTP Version not supported",
    506 => "Variant Also Negotiates",
    507 => "Insufficient Storage",
    509 => "Bandwidth Limit Exceeded",
    510 => "Not Extended",
);