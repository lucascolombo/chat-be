<?php

declare(strict_types=1);

use Slim\App;

return static function (App $app, Closure $customErrorHandler): void {
    $displayError = filter_var(
        $_SERVER['DISPLAY_ERROR_DETAILS'] ?? false,
        FILTER_VALIDATE_BOOLEAN
    );
    $errorMiddleware = $app->addErrorMiddleware($displayError, true, true);
    $errorMiddleware->setDefaultErrorHandler($customErrorHandler);
};