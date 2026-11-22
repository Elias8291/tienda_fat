<?php

namespace App\Console\Commands;

use App\Support\CatalogPhoto;
use Illuminate\Console\Command;

class ShrinkCatalogImages extends Command
{
    protected $signature = 'catalog:shrink-images';

    protected $description = 'Reduce las fotos del catálogo para que la tienda las cargue más rápido';

    public function handle(): int
    {
        $count = CatalogPhoto::shrinkStored();

        $this->info("Fotos ajustadas: {$count}.");

        return self::SUCCESS;
    }
}
