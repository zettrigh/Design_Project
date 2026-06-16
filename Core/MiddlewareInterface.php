<?php

namespace Core;

/**
 * Core\MiddlewareInterface
 *
 * Contrato que deben implementar todos los middlewares de la aplicación.
 * Los middlewares se encadenan formando un pipeline que procesa
 * cada petición antes de llegar al controlador.
 *
 * Patrón de diseño: Chain of Responsibility.
 *
 * @package Core
 */
interface MiddlewareInterface
{
    /**
     * Procesa la petición y, si la validación pasa, invoca al siguiente
     * middleware o al controlador en la cadena.
     *
     * @param callable $next Callback que invoca al siguiente middleware/controlador.
     * @return void
     */
    public function handle(callable $next): void;
}
