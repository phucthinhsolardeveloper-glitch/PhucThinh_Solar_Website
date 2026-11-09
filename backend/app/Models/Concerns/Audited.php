<?php

namespace App\Models\Concerns;

use App\Domain\Audit\Audit;
use Illuminate\Database\Eloquent\Model;

/**
 * Records changes of the attributes listed by auditedAttributes() and every deletion of the model.
 * A model returning [] is audited on deletion only.
 */
trait Audited
{
    /** @return list<string> */
    abstract public static function auditedAttributes(): array;

    public static function bootAudited(): void
    {
        static::updated(function (Model $model) {
            $watched = static::auditedAttributes();
            $changed = array_values(array_intersect($watched, array_keys($model->getChanges())));
            if ($changed === []) {
                return;
            }
            $before = [];
            $after = [];
            foreach ($changed as $key) {
                $before[$key] = $model->getOriginal($key);
                $after[$key] = $model->getAttribute($key);
            }
            if (in_array('password', $changed, true)) {
                $before['password'] = $after['password'] = '(đã đổi)';
            }
            Audit::record('update', class_basename($model), (int) $model->getKey(), $before, $after);
        });
        static::deleted(function (Model $model) {
            $snapshot = collect($model->getAttributes())->except(['password', 'remember_token', 'body', 'blocks', 'payload'])->all();
            Audit::record('delete', class_basename($model), (int) $model->getKey(), $snapshot, null);
        });
    }
}
