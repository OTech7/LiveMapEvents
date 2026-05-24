<?php

namespace App\Support\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Idempotently load a relation on a model. Use to centralise the
 *
 *     if (! $model->relationLoaded($r)) { $model->loadMissing($r); }
 *
 * pattern that otherwise gets duplicated across services and policies.
 *
 * Returns the model so calls can chain.
 */
trait EnsuresRelationLoaded
{
    protected function ensureLoaded(Model $model, string $relation): Model
    {
        if (!$model->relationLoaded($relation)) {
            $model->loadMissing($relation);
        }

        return $model;
    }
}
