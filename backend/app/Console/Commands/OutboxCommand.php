<?php

namespace App\Console\Commands;

use App\Domain\Leads\CrmLeadForwarding;
use App\Domain\Outbox\Outbox;
use Illuminate\Console\Command;

/** Re-dispatches lost outbox mail and prints the queue health; exits 1 when something has failed. */
class OutboxCommand extends Command
{
    protected $signature = 'pts:outbox {--sweep : Re-dispatch messages whose job was lost}';

    protected $description = 'Show outbox and failed-job counts; optionally re-dispatch stalled mail.';

    public function handle(): int
    {
        if ($this->option('sweep')) {
            $this->info('Re-dispatched: '.Outbox::sweep());
            if (($late = CrmLeadForwarding::reconcile()) > 0) {
                $this->info('CRM queued late: '.$late);
            }
        }
        $health = Outbox::health();
        $this->table(['Chờ gửi', 'Đang thử lại', 'Thất bại', 'Job lỗi', 'CRM thất bại'], [[$health['pending'], $health['retrying'], $health['failed'], $health['failed_jobs'], $health['crm_failed']]]);

        return $health['failed'] + $health['failed_jobs'] + $health['crm_failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
