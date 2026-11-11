<?php

namespace App\Console\Commands;

use App\Domain\Shipping\NhatTin\NtlException;
use App\Domain\Shipping\Shipments;
use App\Models\Shipment;
use Illuminate\Console\Command;

/** Backup to the Nhất Tín webhook: asks for the journey of every parcel still on its way (scheduled every 30 minutes). */
class TrackShipmentsCommand extends Command
{
    protected $signature = 'pts:shipments-track {--limit=60 : At most this many parcels per run}';

    protected $description = 'Refresh the journey of active Nhất Tín shipments (orders follow automatically).';

    public function handle(Shipments $shipments): int
    {
        $due = Shipment::query()->whereNotNull('bill_code')->whereIn('status', Shipments::ACTIVE)
            ->where('updated_at', '<', now()->subMinutes(25))->orderBy('updated_at')->limit((int) $this->option('limit'))->get();
        $failed = 0;
        foreach ($due as $shipment) {
            try {
                $shipments->refresh($shipment);
                $shipment->touch();
            } catch (NtlException $e) {
                $failed++;
                $this->warn($shipment->bill_code.': '.$e->getMessage());
            }
        }
        $this->info('Đã cập nhật '.($due->count() - $failed).'/'.$due->count().' vận đơn.');

        return self::SUCCESS;
    }
}
