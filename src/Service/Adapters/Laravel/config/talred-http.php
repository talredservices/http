<?php

declare(strict_types=1);

// Keep the source defaults in one place while exposing a Talred-named config
// file for applications that install the HTTP package directly.
return require __DIR__.'/zolta-http.php';
