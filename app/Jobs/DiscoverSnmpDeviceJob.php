<?php

namespace App\Jobs;

use App\Models\MonitoredHost;
use App\Models\SnmpSensor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DiscoverSnmpDeviceJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public MonitoredHost $host)
    {
    }
    public function handle(): void
    {
        if (!$this->host->snmp_enabled) {
            return;
        }

        if (!extension_loaded('snmp')) {
            Log::error("SNMP Discovery failed for {$this->host->ip}: PHP SNMP extension is not installed/enabled on this server.");
            return;
        }

        // Load specific MIB if associated
        if ($this->host->mib_id && $this->host->mib) {
            $mibPath = storage_path('app/public/' . $this->host->mib->file_path);
            if (!file_exists($mibPath)) {
                 $mibPath = storage_path('app/' . $this->host->mib->file_path);
            }
            if (file_exists($mibPath)) {
                @snmp_read_mib($mibPath);
            }
        }

        try {
            $version = match($this->host->snmp_version) {
                'v1' => \SNMP::VERSION_1,
                'v3' => \SNMP::VERSION_3,
                default => \SNMP::VERSION_2c,
            };

            $session = new \SNMP($version, $this->host->ip, $this->host->snmp_community);
            $session->exceptions_enabled = \SNMP::ERRNO_ANY;

            $sysName = $this->cleanString(@$session->get('1.3.6.1.2.1.1.5.0'));
            $sysDescr = $this->cleanString(@$session->get('1.3.6.1.2.1.1.1.0'));
            
            if ($sysName) {
                $this->host->name = $sysName;
            }

            // Always create Uptime sensor
            $this->createSensor('System Uptime', '1.3.6.1.2.1.1.3.0', 'uptime');

            // --- Vendor Detection based on sysDescr ---

            // Cisco Devices
            if (stripos($sysDescr, 'Cisco') !== false || stripos($sysDescr, 'IOS') !== false) {
                $this->host->type = 'switch';
                $this->createSensor('CPU Usage (5m)', '1.3.6.1.4.1.9.9.109.1.1.1.1.8.1', 'gauge', '%', 85, 95);
                $this->createSensor('Free Memory', '1.3.6.1.4.1.9.9.48.1.1.1.6.1', 'gauge', 'bytes');
            }
            // Printers (HP, Lexmark, etc)
            elseif (stripos($sysDescr, 'Printer') !== false || stripos($sysDescr, 'HP LaserJet') !== false) {
                $this->host->type = 'printer';
                $this->createSensor('Page Count', '1.3.6.1.2.1.43.10.2.1.4.1.1', 'counter', 'pages');
            }
            // Linux Servers
            elseif (stripos($sysDescr, 'Linux') !== false) {
                $this->host->type = 'server';
                // Load Average (1m)
                $this->createSensor('Load Average 1m', '1.3.6.1.4.1.2021.10.1.3.1', 'gauge');
                // CPU Idle % (Note: Linux net-snmp returns Idle, so 100 - idle = usage)
                // We'll just graph Idle for now
                $this->createSensor('CPU Idle', '1.3.6.1.4.1.2021.11.11.0', 'gauge', '%');
            }

            $this->host->save();

            // Fire Interface Discovery automatically
            // Fire Interface Discovery automatically (Synchronously)
            DiscoverSnmpInterfacesJob::dispatchSync($this->host);

        } catch (\Exception $e) {
            Log::error("DiscoverSnmpDeviceJob failed for {$this->host->ip}: " . $e->getMessage());
        }
    }

    protected function cleanString(?string $value): ?string
    {
        if (!$value) return null;
        // SNMP responses often include types, e.g. "STRING: switch-01"
        $value = preg_replace('/^[a-zA-Z]+:\s*/', '', $value);
        return trim(trim($value, '"'));
    }

    protected function createSensor(string $name, string $oid, string $dataType, ?string $unit = null, ?float $warn = null, ?float $crit = null): void
    {
        $this->host->snmpSensors()->firstOrCreate(
            ['oid' => $oid],
            [
                'name' => $name,
                'data_type' => $dataType,
                'unit' => $unit,
                'poll_interval' => 60,
                'warning_threshold' => $warn,
                'critical_threshold' => $crit,
                'graph_enabled' => true,
            ]
        );
    }
}
