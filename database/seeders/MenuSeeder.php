<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\MenuItem;
use Illuminate\Database\Seeder;

class MenuSeeder extends Seeder
{
    public function run(): void
    {
        $menu = [
            [
                'nome' => 'Coperto',
                'area_stampa' => 'cliente',
                'voci' => [
                    ['nome' => 'Coperto', 'prezzo' => 1.50],
                ],
            ],
            [
                'nome' => 'Bevande',
                'area_stampa' => 'cliente',
                'voci' => [
                    ['nome' => 'Acqua Naturale 1L', 'prezzo' => 2.00],
                    ['nome' => 'Acqua Gassata 1L', 'prezzo' => 2.00],
                    ['nome' => 'Vino Bianco 0,75', 'prezzo' => 7.00],
                    ['nome' => 'Vino Rosso 0,75', 'prezzo' => 7.00],
                    ['nome' => 'Coca-Cola Lattina', 'prezzo' => 2.50],
                    ['nome' => 'Fanta Lattina', 'prezzo' => 2.50],
                    ['nome' => 'Birra Piccola 300ml', 'prezzo' => 3.50],
                    ['nome' => 'Birra Media 400ml', 'prezzo' => 4.50],
                ],
            ],
            [
                'nome' => 'Antipasti',
                'area_stampa' => 'cucina',
                'voci' => [
                    ['nome' => 'Insalata di Mare', 'prezzo' => 9.00],
                    ['nome' => 'Cozze alla Marinara', 'prezzo' => 8.00],
                    ['nome' => 'Antipasto di Terra', 'prezzo' => 8.00],
                ],
            ],
            [
                'nome' => 'Primi Piatti',
                'area_stampa' => 'cucina',
                'voci' => [
                    ['nome' => 'Cacciucchetto', 'prezzo' => 18.00, 'stock_default' => 100],
                    ['nome' => 'Spaghetti Vongole e Lupino', 'prezzo' => 10.00],
                    ['nome' => 'Tortelli al Ragù', 'prezzo' => 10.00],
                    ['nome' => 'Tortelli al Pomodoro', 'prezzo' => 8.00],
                    ['nome' => 'Penne al Pomodoro o al Ragù', 'prezzo' => 6.00],
                    ['nome' => 'Penne al Ragù di Polpo e Capperi', 'prezzo' => 11.00],
                ],
            ],
            [
                'nome' => 'Secondi Piatti',
                'area_stampa' => 'cucina',
                'voci' => [
                    ['nome' => 'Frittura di Mare', 'prezzo' => 13.00, 'area_stampa' => 'cucina'],
                    ['nome' => 'Pesce alla Griglia (Orata)', 'prezzo' => 12.00, 'area_stampa' => 'griglia'],
                    ['nome' => 'Bistecca di Manzo 300/350 gr.', 'prezzo' => 15.00, 'area_stampa' => 'griglia'],
                    ['nome' => 'Grigliata Mista di Carne', 'prezzo' => 10.00, 'area_stampa' => 'griglia'],
                    ['nome' => 'Tonno alla Griglia, Cipolle Caramellate', 'prezzo' => 17.00, 'area_stampa' => 'griglia'],
                    ['nome' => 'Polpo alla Griglia su Crema di Ceci', 'prezzo' => 13.00, 'area_stampa' => 'griglia'],
                    ['nome' => 'Würstel', 'prezzo' => 3.00, 'area_stampa' => 'griglia'],
                ],
            ],
            [
                'nome' => 'Contorni',
                'area_stampa' => 'cucina',
                'voci' => [
                    ['nome' => 'Patate Fritte', 'prezzo' => 3.50],
                    ['nome' => 'Insalata Mista', 'prezzo' => 3.50],
                ],
            ],
            [
                'nome' => 'Frutta & Dolci',
                'area_stampa' => 'cliente',
                'voci' => [
                    ['nome' => 'Cocomero (Anguria)', 'prezzo' => 2.50],
                    ['nome' => 'Popone (Melone)', 'prezzo' => 2.50],
                    ['nome' => 'Panna Cotta', 'prezzo' => 3.50],
                    ['nome' => 'Crema Catalana', 'prezzo' => 3.50],
                ],
            ],
            [
                'nome' => 'Caffè',
                'area_stampa' => 'cliente',
                'voci' => [
                    ['nome' => 'Caffè Omaggio', 'prezzo' => 0.00],
                ],
            ],
        ];

        $ordinamento = 1;
        $catOrd = 1;

        foreach ($menu as $catData) {
            $categoria = Categoria::query()->create([
                'nome' => $catData['nome'],
                'area_stampa' => $catData['area_stampa'],
                'ordinamento' => $catOrd++,
            ]);

            foreach ($catData['voci'] as $voce) {
                MenuItem::query()->create([
                    'categoria_id' => $categoria->id,
                    'nome' => $voce['nome'],
                    'prezzo' => $voce['prezzo'],
                    'attivo' => true,
                    'piatto_del_giorno' => false,
                    'stock_default' => $voce['stock_default'] ?? null,
                    'area_stampa' => $voce['area_stampa'] ?? null,
                    'ordinamento' => $ordinamento++,
                ]);
            }
        }
    }
}
