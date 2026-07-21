<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\RequestOrder;
use App\Models\RequestOrderItem;
use Carbon\Carbon;

class GenerateRecurringOrders extends Command
{
    protected $signature = 'recurring:generate';
    protected $description = 'Generate recurring order instances from active templates';

    public function handle(): int
    {
        $this->info('Starting recurring orders generation...');

        // ابحث عن جميع القوالب النشطة التي تاريخها اليوم أو قبله
        $templates = RequestOrder::where('is_recurring', true)
            ->where('is_template', true)
            ->where('is_active', true)
            ->whereDate('next_occurrence', '<=', today())
            ->with('items')
            ->get();

        $this->info("Found {$templates->count()} active templates");

        foreach ($templates as $template) {
            try {
                // ✅ أنشئ نسخة جديدة (طلب عادي)
                $newOrder = RequestOrder::create([
                    'department_id' => $template->department_id,
                    'requested_by' => $template->requested_by,
                    'request_type' => $template->request_type,
                    'status' => 'pending',
                    'is_recurring' => false,  // ← النسخة ليست دورية
                    'is_template' => false,    // ← ليست قالب
                    'parent_id' => $template->id,  // ← ربط بالقالب
                    'request_frequency' => 'normal',
                ]);

                // انسخ المواد
                foreach ($template->items as $item) {
                    RequestOrderItem::create([
                        'request_order_id' => $newOrder->id,
                        'product_id' => $item->product_id,
                        'quantity' => $item->quantity,
                    ]);
                }

                // ✅ حدّث next_occurrence للقالب
                $template->update([
                    'next_occurrence' => $this->calculateNextOccurrence(
                        $template->recurring_frequency
                    ),
                ]);

                $this->info("✓ Generated order #{$newOrder->id} from template #{$template->id}");

            } catch (\Exception $e) {
                $this->error("✗ Failed to generate from template #{$template->id}: {$e->getMessage()}");
            }
        }

        $this->info('Recurring orders generation completed.');
        return Command::SUCCESS;
    }

    private function calculateNextOccurrence(string $frequency): Carbon
    {
        $now = Carbon::now();
        return match($frequency) {
            'daily' => $now->copy()->addDay(),
            'weekly' => $now->copy()->addWeek(),
            'monthly' => $now->copy()->addMonth(),
            default => $now->copy()->addWeek(),
        };
    }
}
