<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }

    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function roleValue(): string
    {
        return $this->role instanceof UserRole
            ? $this->role->value
            : ($this->role ?: UserRole::Customer->value);
    }

    public function hasAnyRole(string ...$roles): bool
    {
        return in_array($this->roleValue(), $roles, true);
    }

    public function isShopOwner(): bool
    {
        return $this->roleValue() === UserRole::ShopOwner->value;
    }

    public function isShopKeeper(): bool
    {
        return $this->roleValue() === UserRole::ShopKeeper->value;
    }

    public function isCustomer(): bool
    {
        return $this->roleValue() === UserRole::Customer->value;
    }

    public function canManageItems(): bool
    {
        return $this->isShopOwner();
    }

    public function canManageCashTransactions(): bool
    {
        return $this->isShopOwner() || $this->isShopKeeper();
    }

    public function canManageStockMovements(): bool
    {
        return $this->isShopOwner() || $this->isShopKeeper();
    }

    public function canPurchaseItems(): bool
    {
        return $this->isShopOwner() || $this->isCustomer();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
}
