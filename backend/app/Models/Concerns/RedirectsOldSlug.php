<?php

namespace App\Models\Concerns;

use App\Domain\Routing\Redirects;

/**
 * When the slug of a public record changes, its old address keeps working through a 301.
 * The model says where it lives with publicPath(); null means it has no page of its own.
 */
trait RedirectsOldSlug
{
    /** Public address of this record; $slug asks for the address it had under another slug. */
    abstract public function publicPath(?string $slug = null): ?string;

    /** Whether pages below this one move with it (categories). */
    protected function publicPathIsSection(): bool
    {
        return false;
    }

    public static function bootRedirectsOldSlug(): void
    {
        $old = [];
        static::updating(function (self $model) use (&$old) {
            if ($model->isDirty('slug') && $model->getOriginal('slug')) {
                $old[spl_object_id($model)] = $model->publicPath((string) $model->getOriginal('slug'));
            }
        });
        static::updated(function (self $model) use (&$old) {
            $from = $old[spl_object_id($model)] ?? null;
            unset($old[spl_object_id($model)]);
            $to = $from !== null ? $model->publicPath() : null;
            if ($from !== null && $to !== null) {
                Redirects::record($from, $to, $model->publicPathIsSection());
            }
        });
    }
}
