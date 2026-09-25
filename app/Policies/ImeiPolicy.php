<?php

namespace App\Policies;

use App\Models\Imei;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ImeiPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Almacenero']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Imei $imei): bool
    {
        return $this->accesoAlAlmacen($user, $imei);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyRole(['Administrador', 'Almacenero']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Imei $imei): bool
    {
        return $this->accesoAlAlmacen($user, $imei);
    }

    /**
     * Determine whether the user can change the estado_imei of the model
     * (venta manual, garantía, devolución, etc. hechas a mano por soporte).
     */
    public function changeState(User $user, Imei $imei): bool
    {
        return $this->accesoAlAlmacen($user, $imei);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Imei $imei): bool
    {
        return $user->hasRole('Administrador');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Imei $imei): bool
    {
        return $user->hasRole('Administrador');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Imei $imei): bool
    {
        return $user->hasRole('Administrador');
    }

    /**
     * Administrador: acceso a los IMEIs de todos los almacenes.
     * Almacenero: solo a los IMEIs del almacén que tiene asignado
     * (users.almacen_id). Antes de este fix, un Almacenero podía ver y
     * editar IMEIs de cualquier almacén de la empresa — sin relación con
     * el suyo — porque ImeiController no aplicaba ningún scoping.
     */
    private function accesoAlAlmacen(User $user, Imei $imei): bool
    {
        if ($user->hasRole('Administrador')) {
            return true;
        }

        return $user->hasRole('Almacenero')
            && $user->almacen_id !== null
            && $imei->almacen_id === $user->almacen_id;
    }
}
