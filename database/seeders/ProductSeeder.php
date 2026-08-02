<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Colla;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $colla = Colla::where('shortname', 'demo')->first();

        if (!$colla) {
            $this->command->warn('No hi ha colla "demo" — ProductSeeder omès.');
            return;
        }

        $this->copySeedImages();

        $products = [
            ['name' => 'Casc de canalla', 'image' => 'products/casc-de-canalla.png', 'price' => 25.00],
            ['name' => 'Faixa', 'image' => 'products/faixa.png', 'price' => 15.00],
            ['name' => 'Mocador', 'image' => 'products/mocador.jpg', 'price' => 8.00],
            ['name' => 'Samarreta', 'image' => 'products/samarreta.jpg', 'price' => 15.00],
            ['name' => 'Dessuadora', 'image' => 'products/dessuadora.png', 'price' => 30.00],
            ['name' => 'Bossa de tela', 'image' => 'products/bossa_de_tela.png', 'price' => 6.00],
            ['name' => 'Ampolla', 'image' => 'products/ampolla.png', 'price' => 12.00],
            ['name' => 'Tassa', 'image' => 'products/tassa.png', 'price' => 8.00],
            ['name' => 'Motxilla', 'image' => 'products/motxilla.png', 'price' => 20.00],
            ['name' => 'Clauer', 'image' => 'products/clauer.png', 'price' => 5.00],
            ['name' => 'Pin', 'image' => 'products/pin.png', 'price' => 3.00],
            ['name' => 'Adhesiu', 'image' => 'products/adhesiu.png', 'price' => 2.00],
            ['name' => 'Llibreta', 'image' => 'products/llibreta.png', 'price' => 7.00],
            ['name' => 'Pòster', 'image' => 'products/poster.png', 'price' => 5.00],
            ['name' => 'Posagots', 'image' => 'products/posagots.png', 'price' => 4.00],
            ['name' => 'Bossa faixa', 'image' => 'products/bossa_faixa.png', 'price' => 10.00],
            ['name' => 'Gorra', 'image' => 'products/gorra.png', 'price' => 12.00],
        ];

        foreach ($products as $data) {
            $product = Product::create([
                'colla_id'    => $colla->id,
                'name'        => $data['name'],
                'description' => null,
                'image'       => $data['image'],
                'active'      => true,
            ]);

            $product->variants()->create([
                'name'     => 'Estàndard',
                'price'    => $data['price'],
                'tax_rate' => 21,
                'stock'    => null,
                'active'   => true,
            ]);
        }

        $this->command->line('ProductSeeder: ' . count($products) . " productes creats per a la colla '{$colla->shortname}'.");
    }

    private function copySeedImages(): void
    {
        $source = database_path('seeders/assets/products');
        Storage::disk('public')->makeDirectory('products');

        foreach (File::files($source) as $file) {
            Storage::disk('public')->put(
                'products/' . $file->getFilename(),
                File::get($file->getPathname()),
            );
        }
    }
}
