<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Noticia;
use Illuminate\Database\Seeder;

class NoticiesSeeder extends Seeder
{
    public function run(): void
    {
        Noticia::create([
            'colla_id'     => null,
            'tag_id'       => null,
            'title'        => 'FemPinya 4 — Primera versió pública',
            'summary'      => 'Llançament de la nova plataforma de gestió de colles castelleres, construïda amb Laravel 13 i Filament 5.',
            'body'         => '<p>Benvinguts a FemPinya 4, la nova plataforma de gestió per a colles castelleres.</p>'
                . '<ul><li>Gestió de colles i castellers</li>'
                . '<li>Calendari d\'esdeveniments i assistència</li>'
                . '<li>Pissarra de pinya interactiva (Konva.js)</li>'
                . '<li>Generador de pinyes automàtic</li>'
                . '<li>Portal del membre</li>'
                . '<li>Suport multi-colla (multi-tenant)</li></ul>',
            'labels'       => ['sistema', 'general'],
            'version'      => '1.0.0',
            'published_at' => now(),
        ]);

        Noticia::create([
            'colla_id'     => null,
            'tag_id'       => null,
            'title'        => 'Generador de pinyes — concèntric per categories',
            'summary'      => 'El generador ara mostra i guarda el mateix diagrama concèntric de cercles. El que es veu és el que es guarda.',
            'body'         => '<p>El generador de pinyes ha estat revisat per garantir la coherència entre la vista prèvia i la plantilla desada:</p>'
                . '<ul><li>La vista prèvia (SVG) i la plantilla guardada utilitzen ara el mateix algorisme de disposició concèntrica</li>'
                . '<li>Les posicions s\'agrupen per categoria: agulla, tronc, contrafort, laterals, mitjans, baixos</li>'
                . '<li>Les plantilles generades es poden editar directament a la pissarra</li></ul>',
            'labels'       => ['sistema', 'pissarra'],
            'version'      => '1.0.1',
            'published_at' => now()->subDays(1),
        ]);
    }
}
