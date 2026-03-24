<?php

/**
 * Bootstrap del contenedor IoC.
 * Registra todos los bindings de la aplicación.
 */

use App\Core\Container;
use App\Config\EnvLoader;

// Servicios PIDE
use App\Services\Contracts\PideHttpClientInterface;
use App\Services\PideHttpClient;
use App\Services\Contracts\ReniecServiceInterface;
use App\Services\ReniecService;
use App\Services\Contracts\SunatServiceInterface;
use App\Services\SunatService;
use App\Services\Contracts\SunarpServiceInterface;
use App\Services\SunarpService;

// Servicios CRUD
use App\Services\Contracts\UsuarioServiceInterface;
use App\Services\UsuarioService;
use App\Services\Contracts\ModuloServiceInterface;
use App\Services\ModuloService;

$container = new Container();

// ========================================
// SINGLETONS (una sola instancia en toda la app)
// ========================================
$container->singleton(EnvLoader::class, EnvLoader::class);
$container->singleton(PideHttpClientInterface::class, PideHttpClient::class);

// ========================================
// SERVICIOS PIDE
// ========================================
$container->bind(ReniecServiceInterface::class, ReniecService::class);
$container->bind(SunatServiceInterface::class, SunatService::class);
$container->bind(SunarpServiceInterface::class, SunarpService::class);

// ========================================
// SERVICIOS CRUD
// ========================================
$container->bind(UsuarioServiceInterface::class, UsuarioService::class);
$container->bind(ModuloServiceInterface::class, ModuloService::class);

return $container;
