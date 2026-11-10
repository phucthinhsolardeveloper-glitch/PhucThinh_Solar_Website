{{-- Header link to the public website, beside the Admin search box. Opens in a new tab. --}}
<a href="{{ $url }}" target="_blank" rel="noopener"
   class="pts-view-site fi-btn fi-size-sm fi-color-gray fi-btn-color-gray fi-outlined"
   style="display:inline-flex;align-items:center;gap:.4rem;margin-inline-end:.75rem;padding:.4rem .75rem;border:1px solid rgb(0 0 0 / 12%);border-radius:.5rem;font-size:.875rem;font-weight:600;white-space:nowrap;text-decoration:none"
   title="Mở website ở tab mới">
    <x-filament::icon icon="heroicon-o-globe-alt" class="h-5 w-5" />
    <span class="pts-view-site__label">Xem website</span>
</a>
<style>
    @media (max-width: 640px) { .pts-view-site__label { display: none; } .pts-view-site { padding: .4rem !important; } }
    .dark .pts-view-site { border-color: rgb(255 255 255 / 15%) !important; color: inherit; }
</style>
