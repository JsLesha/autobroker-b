<?php

namespace App\Enums;

enum RoleCode: string
{
    case Admin = 'admin';
    case Management = 'management';
    case Master = 'master';
    case Finance = 'finance';
    case Logist = 'logist';
    case Office = 'office';
    case Dealer = 'dealer';
    case SubUser = 'sub_user';
    case Support = 'support';
    case Buyer = 'buyer';
    case Seller = 'seller';
    case Looking = 'looking';
    case LeadGeneration = 'lead_generation';

    public function title(): string
    {
        return match ($this) {
            self::Admin => 'Администратор',
            self::Management => 'Руководство',
            self::Master => 'Мастер',
            self::Finance => 'Мастер финансов',
            self::Logist => 'Мастер перевозки',
            self::Office => 'Офис',
            self::Dealer => 'Дилер',
            self::SubUser => 'Субпользователь',
            self::Support => 'Техподдержка',
            self::Buyer => 'Покупатель',
            self::Seller => 'Продавец',
            self::Looking => 'Looking',
            self::LeadGeneration => 'Лидогенерация',
        };
    }

    public function isAdminLike(): bool
    {
        return in_array($this, [self::Admin, self::Management], true);
    }
}
