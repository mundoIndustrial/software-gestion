<?php

namespace App\Infrastructure\Storage\PedidosLogo;

use App\Domain\PedidosLogo\Repositories\LogoDesignStorageInterface;
use Illuminate\Support\Facades\Storage;

final class LogoDesignStorage implements LogoDesignStorageInterface
{
    public function deleteByUrl(string $url): void
    {
        $relative = $url;

        if (str_starts_with($relative, '/storage/')) {
            $relative = substr($relative, strlen('/storage/'));
        }

        if ($relative !== '') {
            Storage::disk('public')->delete($relative);
        }
    }
}
