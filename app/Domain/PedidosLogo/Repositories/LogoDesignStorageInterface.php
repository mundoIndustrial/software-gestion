<?php

namespace App\Domain\PedidosLogo\Repositories;

interface LogoDesignStorageInterface
{
    /**
     * Recibe una URL (Storage::url) o una ruta relativa y elimina el archivo en disk public.
     */
    public function deleteByUrl(string $url): void;
}
