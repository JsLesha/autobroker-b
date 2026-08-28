<?php

namespace Database\Seeders;

use App\Models\StatusOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DirectorySeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'new' => 'Новый',
            'in_work' => 'В работе',
            'won' => 'Выкуплен',
            'not_won' => 'Не выкуплен',
            'on_sale' => 'На продаже',
            'archive' => 'Архив',
        ] as $code => $title) {
            StatusOrder::query()->updateOrCreate(['code' => $code], ['title' => $title]);
        }

        $this->named('status_shippings', [
            'pending' => 'Ожидание',
            'warehouse' => 'На складе',
            'loaded' => 'Погружен',
            'sailed' => 'В пути',
            'arrived' => 'Прибыл',
            'delivered' => 'Выдан клиенту',
        ]);
        $this->named('status_finances', [
            'pending' => 'Ожидание',
            'partial' => 'Частично',
            'paid' => 'Оплачен',
        ]);
        $this->named('transport_fuels', ['gasoline' => 'Бензин', 'diesel' => 'Дизель', 'hybrid' => 'Гибрид', 'electric' => 'Электро']);
        $this->named('transport_drives', ['fwd' => 'Передний', 'rwd' => 'Задний', 'awd' => 'Полный']);
        $this->named('transport_transmissions', ['at' => 'Автомат', 'mt' => 'Механика']);
        $this->named('transport_highlights', ['run_and_drive' => 'На ходу', 'starts' => 'Заводится', 'unknown' => 'Неизвестно']);
        $this->named('transport_keys', ['yes' => 'Есть', 'no' => 'Нет']);
        $this->named('transport_odometer_units', ['mi' => 'мили', 'km' => 'км']);
        $this->named('transport_run_statuses', ['actual' => 'Актуальный', 'not_actual' => 'Не актуальный']);
        $this->named('transport_sizes', ['sedan' => 'Седан', 'suv' => 'SUV', 'truck' => 'Пикап']);
        $this->named('vehicle_colors', ['white' => 'Белый', 'black' => 'Чёрный', 'silver' => 'Серебро', 'red' => 'Красный']);
        $this->named('delivery_types', ['container' => 'Контейнер', 'local' => 'Локальная']);

        DB::table('doc_fees')->updateOrInsert(['title' => 'Doc fee базовый'], ['amount' => 95, 'active' => true, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('transportation_agents')->updateOrInsert(
            ['code' => 'default'],
            ['name' => 'Брокер доставки', 'active' => true, 'created_at' => now(), 'updated_at' => now()],
        );
        DB::table('calculation_systems')->updateOrInsert(['code' => 'default'], ['title' => 'Стандарт', 'created_at' => now(), 'updated_at' => now()]);
    }

    /** @param array<string, string> $items */
    private function named(string $table, array $items): void
    {
        $hasTitle = SchemaHas::column($table, 'title');
        foreach ($items as $code => $title) {
            $row = ['code' => $code, 'created_at' => now(), 'updated_at' => now()];
            if ($hasTitle) {
                $row['title'] = $title;
            } else {
                $row['name'] = $title;
            }
            if ($table === 'transport_sizes') {
                $row['title'] = $title;
                $row['autos_count'] = 1;
            }
            DB::table($table)->updateOrInsert(['code' => $code], $row);
        }
    }
}

final class SchemaHas
{
    public static function column(string $table, string $column): bool
    {
        return \Illuminate\Support\Facades\Schema::hasColumn($table, $column);
    }
}
