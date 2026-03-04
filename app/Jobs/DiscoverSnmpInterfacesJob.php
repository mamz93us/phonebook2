<?php

namespace App\Jobs;

use App\Models\MonitoredHost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DiscoverSnmpInterfacesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public MonitoredHost $host)
    {
    }

    public function handle(): void
    {
        if (!$this->host->snmp_enabled || !extension_loaded('snmp')) {
            return;
        }

        try {
            $version = match($this->host->snmp_version) {
                'v1' => \SNMP::VERSION_1,
                'v3' => \SNMP::VERSION_3,
                default => \SNMP::VERSION_2c,
            };

            $session = new \SNMP($version, $this->host->ip, $this->host->snmp_community);
            $session->oid_output_format = \SNMP_OID_OUTPUT_NUMERIC;
            $session->valueretrieval = \SNMP_VALUE_PLAIN;

            // Walk IF-MIB::ifDescr (.1.3.6.1.2.1.2.2.1.2)
            $interfaces = @$session->walk('1.3.6.1.2.1.2.2.1.2');
            
            if (!$interfaces) {
                return;
            }

            foreach ($interfaces as $oid => $descr) {
                // Determine port index
                $parts = explode('.', $oid);
                $index = end($parts);
                $cleanName = trim(trim($descr, '"'));

                // Skip loopback and null interfaces
                if (stripos($cleanName, 'loopback') !== false || stripos($cleanName, 'null') !== false) {
                    continue;
                }

                // Traffic In (ifHCInOctets) .1.3.6.1.2.1.31.1.1.1.6
                $this->createSensor(
                    $cleanName . ' - Traffic In',
                    "1.3.6.1.2.1.31.1.1.1.6.{$index}",
                    'counter',
                    'bytes/sec'
                );

                // Traffic Out (ifHCOutOctets) .1.3.6.1.2.1.31.1.1.1.10
                $this->createSensor(
                    $cleanName . ' - Traffic Out',
                    "1.3.6.1.2.1.31.1.1.1.10.{$index}",
                    'counter',
                    'bytes/sec'
                );
                
                // Port Status (ifOperStatus) .1.3.6.1.2.1.2.2.1.8
                $this->createSensor(
                    $cleanName . ' - Status',
                    "1.3.6.1.2.1.2.2.1.8.{$index}",
                    'boolean',
                    'status' // 1 = up, 2 = down
                );
            }

        } catch (\Exception $e) {
            Log::error("DiscoverSnmpInterfacesJob failed for {$this->host->ip}: " . $e->getMessage());
        }
    }

    protected function createSensor(string $name, string $oid, string $dataType, string $unit): void
    {
        $this->host->snmpSensors()->firstOrCreate(
            ['oid' => $oid],
            [
                'name' => $name,
                'data_type' => $dataType,
                'unit' => $unit,
                'poll_interval' => 60,
                'graph_enabled' => true,
            ]
        );
    }
}
