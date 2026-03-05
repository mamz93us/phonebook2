<?php

namespace App\Services\Snmp;

use Illuminate\Support\Facades\Storage;

class MibParser
{
    /**
     * Parse a MIB file and extract OBJECT-TYPE definitions.
     * 
     * @param string $filePath Relative path to the MIB file in local storage.
     * @return array List of discovered OIDs and their names.
     */
    public function parseObjects(string $filePath): array
    {
        if (!Storage::disk('local')->exists($filePath)) {
            return [];
        }

        $content = Storage::disk('local')->get($filePath);
        $objects = [];

        $moduleName = '';
        if (preg_match('/^([a-zA-Z0-9\-]+)\s+DEFINITIONS\s*::=\s*BEGIN/m', $content, $modMatch)) {
            $moduleName = $modMatch[1] . '::';
        }

        // Simple regex to find OBJECT-TYPE definitions
        // Format: name OBJECT-TYPE ... ::= { parent index/name }
        $pattern = '/([a-zA-Z0-9\-]+)\s+OBJECT-TYPE\s+.*?::=\s*\{\s*([a-zA-Z0-9\-\s]+)\s+\}/s';
        
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $name = $match[1];
                $oidParts = preg_split('/\s+/', trim($match[2]));
                
                // For now, we store the name and the relative OID part
                // In a real scenario, we might want to resolve the full OID, 
                // but without a full library or snmptranslate, we can at least show these.
                $objects[] = [
                    'name' => $name,
                    'oid_suffix' => $moduleName . $name, // e.g. UCM-MIB::pbxTotalCalls
                    'parent' => $oidParts[count($oidParts) - 2] ?? 'unknown',
                    'full_definition' => $match[0]
                ];
            }
        }

        return $objects;
    }
}
