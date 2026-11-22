<?php

use App\Support\CatalogPhoto;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        CatalogPhoto::shrinkStored();
    }
};
