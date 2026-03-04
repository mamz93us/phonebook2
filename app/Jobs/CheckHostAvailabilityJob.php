<?php

namespace App\Jobs;

use App\Models\HostCheck;
use App\Models\MonitoredHost;
use App\Models\NocEvent;
use App\Services\PingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckHostAvailabilityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(PingService $pingService): void
    {
        $hosts = MonitoredHost::where('ping_enabled', true)->get();

        foreach ($hosts as $host) {
            try {
                $pingResult = $pingService->ping($host->ip, 3);

                // Store check result
                HostCheck::create([
                    'host_id' => $host->id,
                    'check_type' => 'ping',
                    'latency_ms' => $pingResult['latency'],
                    'packet_loss' => $pingResult['packet_loss'],
                    'success' => $pingResult['success'],
                ]);

                // Determine and update host status
                if ($pingResult['success']) {
                    $host->last_ping_at = now();
                    
                    // If SNMP is enabled but hasn't responded in > 3 poll intervals (e.g., 3 minutes)
                    if ($host->snmp_enabled && $host->last_snmp_at && $host->last_snmp_at->diffInMinutes(now()) > 3) {
                        $host->status = 'degraded';
                    } else {
                        $host->status = 'up';
                    }

                    // Resolve active host_down events
                    NocEvent::where('source_id', $host->id)
                        ->where('event_type', 'host_down')
                        ->where('status', 'active')
                        ->update([
                            'status' => 'resolved',
                            'resolved_at' => now(),
                        ]);

                } else {
                    $host->status = 'down';
                    
                    // Create NOC alert
                    NocEvent::firstOrCreate(
                        [
                            'source_id' => $host->id,
                            'event_type' => 'host_down',
                            'status' => 'active',
                        ],
                        [
                            'title' => "Host Down: {$host->name}",
                            'description' => "Ping check failed for {$host->ip} completely.",
                            'severity' => 'critical',
                            'detected_at' => now(),
                        ]
                    );
                }

                $host->last_checked_at = now();
                $host->save();

            } catch (\Exception $e) {
                Log::error("Ping check failed for Host ID {$host->id}: " . $e->getMessage());
                $host->update(['status' => 'down']);
            }
        }
    }
}
