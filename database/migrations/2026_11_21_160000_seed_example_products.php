<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $photo = fn (string $id) => "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&q=80&w=800";

        $products = [
            ['Vestido midi satinado', 79.99, 99.99, 'Elegante vestido de tirantes con acabado satinado suave.', ['1515886657613-9f3515b0c78f', '1496747611176-843222e1e57c']],
            ['Conjunto cozy tricot', 89.99, null, 'Dos piezas cómodo y abrigador en tono rosa pastel.', ['1485230895905-ec40ba36b9bc', '1509631179647-0177331693ae']],
            ['Falda midi plisada', 54.99, null, 'Movimiento fluido con cintura elástica cómoda.', ['1583496661160-fb5886a0aaaa', '1572804013309-59a88b7e92f1']],
            ['Blazer oversized', 119.99, null, 'Corte sastre moderno ideal para cualquier ocasión.', ['1550614000-4895a10e1bfd', '1524504388940-b1c1722653e1']],
            ['Top de algodón', 29.99, null, 'Básico esencial de tacto ultra suave.', ['1525507119028-ed4c629a60a3', '1503342217505-b0a15ec3261c']],
            ['Blusa romántica', 49.99, 59.99, 'Detalles delicados en cuello y mangas abullonadas.', ['1534528741775-53994a69daeb', '1529139574466-a303027c1d8b']],
            ['Cardigan tejido', 69.99, 84.99, 'Tejido grueso con botones nacarados.', ['1434389677669-e08b4cac3105', '1469334031218-e382a71b716b']],
            ['Pantalón palazzo', 64.99, null, 'Corte de pierna ancha con caída elegante.', ['1594633312681-425c7b97ccd1', '1506629082955-511b1aa562c8']],
        ];

        $now = now();

        foreach ($products as $index => [$name, $price, $oldPrice, $description, $images]) {
            if (DB::table('products')->where('name', $name)->exists()) {
                continue;
            }

            $id = DB::table('products')->insertGetId([
                'name' => $name,
                'price' => $price,
                'old_price' => $oldPrice,
                'description' => $description,
                'active' => true,
                'position' => $index + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            foreach ($images as $position => $image) {
                DB::table('product_images')->insert([
                    'product_id' => $id,
                    'path' => $photo($image),
                    'position' => $position,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        $names = [
            'Vestido midi satinado',
            'Conjunto cozy tricot',
            'Falda midi plisada',
            'Blazer oversized',
            'Top de algodón',
            'Blusa romántica',
            'Cardigan tejido',
            'Pantalón palazzo',
        ];

        DB::table('products')->whereIn('name', $names)->delete();
    }
};
