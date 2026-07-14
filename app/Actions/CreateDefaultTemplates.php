<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Colla;
use Illuminate\Support\Facades\DB;

class CreateDefaultTemplates
{
    // Totes les plantilles per defecte llegides de resources/js/boards/
    // Format: 'clau' => ['file' => 'fitxer.json', 'name' => 'Nom visible']
    // Els fitxers han d'estar en format board-editor (cx, cy, w, h, angle).
    private const TEMPLATES = [
        '3d6'      => ['file' => '3d6.json',       'name' => '3 de 6'],
        'cinc_de_6' => ['file' => 'cinc_de_6.json', 'name' => 'Cinc de 6'],
        'set_de_x'  => ['file' => 'set_de_x.json',  'name' => 'Set de X'],
    ];

    public function execute(Colla $colla): void
    {
        foreach (self::TEMPLATES as $key => $meta) {
            $this->upsertTemplate($colla, $key, $meta);
        }
    }

    private function upsertTemplate(Colla $colla, string $key, array $meta): void
    {
        $path = base_path('resources/js/boards/' . $meta['file']);
        if (! file_exists($path)) {
            return;
        }

        $positions = json_decode(file_get_contents($path), true);
        $now       = now()->toDateTimeString();

        DB::table('castle_templates')->insertOrIgnore([
            'colla_id'   => $colla->id,
            'key'        => $key,
            'name'       => $meta['name'],
            'positions'  => json_encode($positions),
            'ready'      => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
