<?php

namespace App\Policies;

use App\Models\Caja;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CajaPolicy
{
    /**
     * Determine whether the user can view any models.
     * El scoping del listado (ver todas vs. solo las propias) lo sigue
     * resolviendo CajaController@index vía $isAdmin — aquí solo se exige
     * estar autenticado, que ya lo garantiza el middleware de la ruta.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     * Mismo criterio que CajaController::show() usaba inline: Administrador
     * ve cualquier caja, cualquier otro usuario solo la suya propia.
     */
    public function view(User $user, Caja $caja): bool
    {
        return $this->esDuenoOAdmin($user, $caja);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Caja $caja): bool
    {
        return $this->esDuenoOAdmin($user, $caja);
    }

    /**
     * Determine whether the user can close (arqueo) this caja.
     * Mismo criterio que CajaController::cerrar() usaba inline.
     */
    public function close(User $user, Caja $caja): bool
    {
        return $this->esDuenoOAdmin($user, $caja);
    }

    /**
     * Determine whether the user can register an ingreso/egreso on this caja.
     */
    public function registerMovement(User $user, Caja $caja): bool
    {
        return $this->esDuenoOAdmin($user, $caja) && $caja->estado === 'abierta';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Caja $caja): bool
    {
        return $user->hasRole('Administrador');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Caja $caja): bool
    {
        return $user->hasRole('Administrador');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Caja $caja): bool
    {
        return $user->hasRole('Administrador');
    }

    private function esDuenoOAdmin(User $user, Caja $caja): bool
    {
        return $user->hasRole('Administrador') || $caja->user_id === $user->id;
    }
}
