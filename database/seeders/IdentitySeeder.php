<?php

namespace Database\Seeders;

use App\Enums\RoleCode;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class IdentitySeeder extends Seeder
{
    public function run(): void
    {
        foreach (RoleCode::cases() as $code) {
            Role::query()->updateOrCreate(
                ['code' => $code->value],
                ['title' => $code->title()],
            );
        }

        $groups = [
            'users' => 'Пользователи',
            'lots' => 'Лоты',
            'logistics' => 'Логистика',
            'containers' => 'Контейнеры',
            'finance' => 'Финансы лота',
            'wallets' => 'Кошельки',
            'rates' => 'Тарифы',
            'counterparties' => 'Контрагенты',
            'credentials' => 'Доступы аукционов',
            'directory' => 'Справочники',
            'prebid' => 'Prebid',
        ];

        $actions = ['read', 'create', 'update', 'delete'];

        foreach ($groups as $group => $title) {
            foreach ($actions as $action) {
                Permission::query()->updateOrCreate(
                    ['code' => $group.'.'.$action],
                    ['title' => $title.': '.$action, 'group_name' => $group],
                );
            }
        }

        $dealer = Role::query()->where('code', RoleCode::Dealer)->first();
        $office = Role::query()->where('code', RoleCode::Office)->first();
        $logist = Role::query()->where('code', RoleCode::Logist)->first();
        $finance = Role::query()->where('code', RoleCode::Finance)->first();
        $master = Role::query()->where('code', RoleCode::Master)->first();

        $this->grant($dealer, [
            'lots.read', 'lots.create', 'lots.update',
            'directory.read', 'counterparties.read', 'credentials.read',
            'prebid.read', 'prebid.create',
        ]);
        $this->grant($office, [
            'lots.read', 'lots.create', 'lots.update',
            'directory.read', 'counterparties.read', 'credentials.read',
        ]);
        $this->grant($logist, ['lots.read', 'logistics.read', 'logistics.update', 'containers.read', 'containers.create', 'containers.update']);
        $this->grant($finance, ['lots.read', 'finance.read', 'finance.update', 'wallets.read', 'wallets.update']);
        $this->grant($master, Permission::query()->pluck('code')->all());
    }

    private function grant(?Role $role, array $codes): void
    {
        if (! $role) {
            return;
        }

        $ids = Permission::query()->whereIn('code', $codes)->pluck('id');
        $role->permissions()->syncWithoutDetaching($ids);
    }
}
