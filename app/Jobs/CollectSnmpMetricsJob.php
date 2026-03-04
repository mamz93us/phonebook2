<?php

namespace App\Jobs;

use App\Models\MonitoredHost;
use App\Models\SensorMetric;
use App\Models\NocEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CollectSnmpMetricsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        // Fetch hosts with SNMP enabled and not hard-down
        $hosts = MonitoredHost::with('snmpSensors')
            ->where('snmp_enabled', true)
            ->where('status', '!=', 'down')
            ->get();

        foreach ($hosts as $host) {
            if ($host->snmpSensors->isEmpty()) {
                continue;
            }

            try {
                if (!extension_loaded('snmp')) {
                    Log::warning("CollectSnmpMetricsJob: SNMP extension not loaded on server.");
                    return;
                }

                $version = match($host->snmp_version) {
                    'v1' => \SNMP::VERSION_1,
                    'v3' => \SNMP::VERSION_3,
                    default => \SNMP::VERSION_2c,
                };

                $session = new \SNMP($version, $host->ip, $host->snmp_community, 1000000, 2);
                $session->exceptions_enabled = \SNMP::ERRNO_ANY;
                
                // Track if we successfully polled at least one sensor
                $snmpSuccess = false;

                foreach ($host->snmpSensors as $sensor) {
                    try {
                        $rawResult = $session->get($sensor->oid);
                        if ($rawResult === false) continue;
                        
                        $snmpSuccess = true;
                        $parsedValue = $this->parseValue($rawResult);
                        $finalValue = $parsedValue;

                        // Calculate rate for counters using Cache
                        if ($sensor->data_type === 'counter') {
                            $cacheKey = "snmp_sensor_raw_{$sensor->id}";
                            $lastData = Cache::get($cacheKey);

                            Cache::put($cacheKey, [
                                'value' => $parsedValue,
                                'time' => now()->timestamp,
                            ], 300); // 5 minutes cache

                            $rateValue = 0;
                            if ($lastData) {
                                $diffTime = now()->timestamp - $lastData['time'];
                                if ($diffTime > 0) {
                                    $diffValue = $parsedValue - $lastData['value'];
                                    // Handle counter wrap-around (drop negative spikes)
                                    if ($diffValue < 0) {
                                        $diffValue = 0; 
                                    }
                                    $rateValue = $diffValue / $diffTime;
                                }
                            }
                            $finalValue = $rateValue;
                        }

                        // Store metrics if graph is enabled or we just need the data point
                        SensorMetric::create([
                            'sensor_id' => $sensor->id,
                            'value' => $finalValue,
                            'recorded_at' => now(),
                        ]);

                        // Threshold Alerting Logic
                        $this->checkThresholds($host, $sensor, $finalValue);

                    } catch (\Exception $e) {
                        Log::error("Failed to poll sensor {$sensor->oid} on host {$host->ip}: " . $e->getMessage());
                    }
                }

                // Update last SNMP time if successful
                if ($snmpSuccess) {
                    $host->last_snmp_at = now();
                    // Recover from degraded if applicable
                    if ($host->status === 'degraded') {
                        $host->status = 'up';
                    }
                    $host->save();
                }

            } catch (\Exception $e) {
                Log::error("SNMP session failed for host {$host->ip}: " . $e->getMessage());
                // Mark host degraded if it was up
                if ($host->status === 'up') {
                    $host->update(['status' => 'degraded']);
                }
            }
        }
    }

    protected function parseValue($value): float
    {
        if (preg_match('/(\d+(\.\d+)?)/', $value, $matches)) {
            return (float)$matches[1];
        }
        if (stripos($value, 'up') !== false) return 1;
        if (stripos($value, 'down') !== false) return 0;
        return 0;
    }

    protected function checkThresholds(MonitoredHost $host, \App\Models\SnmpSensor $sensor, float $value): void
    {
        $severity = null;
        if ($sensor->critical_threshold !== null && $value >= $sensor->critical_threshold) {
            $severity = 'critical';
        } elseif ($sensor->warning_threshold !== null && $value >= $sensor->warning_threshold) {
            $severity = 'warning';
        }

        if ($severity) {
            $sensorName = $sensor->name ?: $sensor->description ?: $sensor->oid;
            $eventTitle = "SNMP Threshold Exceeded: {$host->name} - {$sensorName}";
            $message = "Sensor value {$value} {$sensor->unit} exceeded {$severity} threshold limit.";

            // Look for existing active event
            $existingEvent = NocEvent::where('source_id', $host->id)
                ->where('event_type', 'snmp_threshold')
                ->where('status', 'active')
                ->where('title', $eventTitle)
                ->first();

            if (!$existingEvent) {
                NocEvent::create([
                    'event_type' => 'snmp_threshold',
                    'source_id' => $host->id,
                    'title' => $eventTitle,
                    'description' => $message,
                    'severity' => $severity,
                    'status' => 'active',
                    'detected_at' => now(),
                ]);
            }
        }
    }
}
