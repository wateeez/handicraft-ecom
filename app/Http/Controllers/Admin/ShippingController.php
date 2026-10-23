<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ShippingZone;
use App\Models\ShippingProvider;
use App\Models\ShippingRate;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShippingController extends Controller
{
    public function index()
    {
        $zones = ShippingZone::with('rates.provider')->get();
        $providers = ShippingProvider::with('rates.zone')->get();

        return view('admin.shipping.index', compact('zones', 'providers'));
    }

    // Focused pages for better UX
    public function zonesSettings()
    {
        $zones = ShippingZone::with('rates.provider')->get();
        return view('admin.shipping.zones', compact('zones'));
    }

    public function providersSettings()
    {
        $providers = ShippingProvider::with('rates.zone')->get();
        return view('admin.shipping.providers', compact('providers'));
    }

    public function ratesSettings()
    {
        $zones = ShippingZone::with('rates.provider')->get();
        $providers = ShippingProvider::with('rates.zone')->get();
        return view('admin.shipping.index', ['zones' => $zones, 'providers' => $providers, 'defaultTab' => 'rates']);
    }

    public function providerDetails(ShippingProvider $provider)
    {
        $zones = ShippingZone::with(['rates' => function($q) use ($provider) {
            $q->where('shipping_provider_id', $provider->id);
        }, 'rates.provider'])->get();

        // Only pass the selected provider to limit clutter in the rates tab
        return response()
            ->view('admin.shipping.index', [
                'zones' => $zones,
                'providers' => collect([$provider]),
                'defaultTab' => 'rates',
                'selectedProvider' => $provider,
            ])
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    // --- Zones ---

    public function storeZone(Request $request)
    {
        $request->validate(['name' => 'required', 'countries' => 'required']);
        $countries = array_map('trim', explode(',', $request->countries));

        ShippingZone::create([
            'name' => $request->name,
            'countries' => $countries
        ]);

        return back()->with('success', 'Zone added.');
    }

    public function editZone(ShippingZone $zone)
    {
        return view('admin.shipping.edit_zone', compact('zone'));
    }

    public function updateZone(Request $request, ShippingZone $zone)
    {
        $request->validate(['name' => 'required', 'countries' => 'required']);
        $countries = array_map('trim', explode(',', $request->countries));

        $zone->update([
            'name' => $request->name,
            'countries' => $countries
        ]);

        return redirect()->route('admin.shipping.index')->with('success', 'Zone updated.');
    }

    public function destroyZone(ShippingZone $zone)
    {
        $zone->delete();
        return back()->with('success', 'Zone deleted.');
    }

    // --- Providers ---

    public function storeProvider(Request $request)
    {
        $request->validate(['name' => 'required']);
        ShippingProvider::create(['name' => $request->name]);
        return back()->with('success', 'Provider added.');
    }

    public function destroyProvider(ShippingProvider $provider)
    {
        $provider->delete();
        return back()->with('success', 'Provider deleted.');
    }

    // --- Rates ---

    public function storeRate(Request $request)
    {
        $request->validate([
            'shipping_zone_id' => 'required|exists:shipping_zones,id',
            'shipping_provider_id' => 'required|exists:shipping_providers,id',
            'min_weight' => 'required|numeric',
            'max_weight' => 'required|numeric',
            'price' => 'required|numeric',
        ]);

        ShippingRate::create($request->only(['shipping_zone_id', 'shipping_provider_id', 'min_weight', 'max_weight', 'price']));
        return back()->with('success', 'Rate added.');
    }

    public function editRate(ShippingRate $rate)
    {
        $zones = ShippingZone::all();
        $providers = ShippingProvider::all();
        return view('admin.shipping.edit_rate', compact('rate', 'zones', 'providers'));
    }

    public function updateRate(Request $request, ShippingRate $rate)
    {
        $request->validate([
            'min_weight' => 'required|numeric',
            'max_weight' => 'required|numeric',
            'price' => 'required|numeric',
        ]);

        $rate->update($request->only(['min_weight', 'max_weight', 'price']));
        return redirect()->route('admin.shipping.index')->with('success', 'Rate updated.');
    }

    public function destroyRate(ShippingRate $rate)
    {
        $rateName = "{$rate->min_weight}kg - {$rate->max_weight}kg";
        $zoneId = $rate->shipping_zone_id;
        $providerId = $rate->shipping_provider_id;
        
        \Log::info("Deleting rate: {$rateName}, Zone: {$zoneId}, Provider: {$providerId}, ID: {$rate->id}");
        
        $rate->delete();
        
        \Log::info("Rate deleted successfully");
        
        return redirect()->back()
            ->with('success', "Rate ({$rateName}) deleted successfully.")
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    // --- Bulk Import/Export ---

    public function exportRates(Request $request): StreamedResponse
    {
        $request->validate([
            'shipping_provider_id' => 'required|exists:shipping_providers,id',
        ]);

        $provider = ShippingProvider::with('rates.zone')->findOrFail($request->shipping_provider_id);
        $zones = ShippingZone::orderBy('id')->get();
        $rates = ShippingRate::where('shipping_provider_id', $provider->id)->get();

        $rateMap = [];
        foreach ($rates as $rate) {
            $key = $rate->min_weight . '|' . $rate->max_weight;
            if (!isset($rateMap[$key])) {
                $rateMap[$key] = [
                    'min_weight' => $rate->min_weight,
                    'max_weight' => $rate->max_weight,
                    'prices' => [],
                ];
            }
            $rateMap[$key]['prices'][$rate->shipping_zone_id] = $rate->price;
        }

        $sortedRanges = collect($rateMap)
            ->sortBy(function ($range) {
                return ($range['min_weight'] * 1000000) + $range['max_weight'];
            })
            ->values();

        $fileName = 'shipping_rates_' . Str::slug($provider->name) . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($zones, $sortedRanges) {
            $output = fopen('php://output', 'w');
            $header = array_merge(['min_weight', 'max_weight'], $zones->pluck('name')->toArray());
            fputcsv($output, $header);

            foreach ($sortedRanges as $range) {
                $row = [$range['min_weight'], $range['max_weight']];
                foreach ($zones as $zone) {
                    $row[] = $range['prices'][$zone->id] ?? '';
                }
                fputcsv($output, $row);
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    public function importRates(Request $request)
    {
        $request->validate([
            'shipping_provider_id' => 'required|exists:shipping_providers,id',
            'rates_file' => 'required|file|mimes:csv,txt',
        ]);

        $providerId = (int) $request->shipping_provider_id;
        $zones = ShippingZone::orderBy('id')->get();
        $zoneLookup = $zones->reduce(function ($carry, $zone) {
            $carry[strtolower(trim($zone->name))] = $zone->id;
            $carry[strtolower(str_replace(' ', '', trim($zone->name)))] = $zone->id;
            return $carry;
        }, []);
        $zoneOrder = $zones->values();

        $file = $request->file('rates_file');
        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('error', 'Unable to read the uploaded file.');
        }

        $header = fgetcsv($handle);
        if (!$header || count($header) < 3) {
            fclose($handle);
            return back()->with('error', 'Invalid CSV header. Expected min_weight, max_weight, and zone columns.');
        }

        $header = array_map('trim', $header);
        $zoneHeaders = array_slice($header, 2);
        $zoneIds = [];

        foreach ($zoneHeaders as $zoneHeader) {
            $normalized = strtolower(trim($zoneHeader));
            $normalizedNoSpace = strtolower(str_replace(' ', '', $normalized));

            if (isset($zoneLookup[$normalized])) {
                $zoneIds[] = $zoneLookup[$normalized];
                continue;
            }
            if (isset($zoneLookup[$normalizedNoSpace])) {
                $zoneIds[] = $zoneLookup[$normalizedNoSpace];
                continue;
            }

            if (is_numeric($normalized)) {
                $index = (int) $normalized - 1;
                if ($zoneOrder->has($index)) {
                    $zoneIds[] = $zoneOrder->get($index)->id;
                    continue;
                }
            }

            fclose($handle);
            return back()->with('error', 'Unknown zone in CSV header: ' . $zoneHeader);
        }

        $imported = 0;
        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 2) {
                continue;
            }

            $minWeight = trim($row[0]);
            $maxWeight = trim($row[1]);

            if ($minWeight === '' || $maxWeight === '') {
                continue;
            }

            $minWeight = (float) $minWeight;
            $maxWeight = (float) $maxWeight;

            foreach ($zoneIds as $index => $zoneId) {
                $priceIndex = $index + 2;
                if (!isset($row[$priceIndex]) || trim($row[$priceIndex]) === '') {
                    continue;
                }

                $price = (float) $row[$priceIndex];

                ShippingRate::updateOrCreate(
                    [
                        'shipping_provider_id' => $providerId,
                        'shipping_zone_id' => $zoneId,
                        'min_weight' => $minWeight,
                        'max_weight' => $maxWeight,
                    ],
                    ['price' => $price]
                );

                $imported++;
            }
        }

        fclose($handle);

        return back()->with('success', 'Bulk import completed. ' . $imported . ' rates processed.');
    }

    // --- Country-to-Zone mapping CSV ---

    /**
     * Export CSV of country → zone mapping.
     * Columns: country, zone
     */
    public function exportZonesCsv(): StreamedResponse
    {
        $zones = ShippingZone::orderBy('id')->get();
        $fileName = 'country_zone_mapping_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($zones) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['country', 'zone']);

            foreach ($zones as $zone) {
                $countries = $zone->countries ?? [];
                foreach ($countries as $country) {
                    // Ensure string
                    $countryStr = is_string($country) ? $country : strval($country);
                    fputcsv($output, [$countryStr, $zone->name]);
                }
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * Import CSV of country → zone mapping.
        * Accepts columns: country/country_name/country_code and zone/zone_name/zone_index
     * For each row: removes country from any existing zone and assigns it to the specified zone.
     */
    public function importZonesCsv(Request $request)
    {
        $request->validate([
            'zones_file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('zones_file');
        $filePath = $file->getRealPath();
        $delimiter = $this->detectCsvDelimiter($filePath);

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return back()->with('error', 'Unable to read the uploaded file.');
        }

        $header = fgetcsv($handle, 0, $delimiter);
        if (!$header || count($header) < 2) {
            fclose($handle);
            return back()->with('error', 'Invalid CSV header. Expected columns: country, zone. Ensure the file is a proper CSV.');
        }

        $header = array_map(function ($value) {
            return trim((string) $value);
        }, $header);

        // Strip UTF-8 BOM from first header cell if present
        if (isset($header[0])) {
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        }

        // Determine zone column index and name variants
        $countryIdx = null;
        $zoneIdx = null;
        foreach ($header as $i => $col) {
            $norm = strtolower($col);
            if ($countryIdx === null && in_array($norm, ['country', 'country_name', 'country_code'])) {
                $countryIdx = $i;
            }
            if ($zoneIdx === null && in_array($norm, ['zone', 'zone_name', 'zoneindex', 'zone_index'])) {
                $zoneIdx = $i;
            }
        }
        if ($countryIdx === null || $zoneIdx === null) {
            fclose($handle);
            return back()->with('error', 'CSV must include columns: country and zone');
        }

        // Allow first-time import by creating zones from CSV zone column when none exist yet.
        $this->bootstrapZonesFromCsvIfEmpty($filePath, $delimiter, $zoneIdx);

        $zones = ShippingZone::orderBy('id')->get();
        if ($zones->isEmpty()) {
            fclose($handle);
            return back()->with('error', 'Could not initialize zones from CSV.');
        }

        // Build lookup by normalized zone name and by order index
        $zoneLookupByName = $zones->reduce(function ($carry, $zone) {
            $key = strtolower(trim($zone->name));
            $carry[$key] = $zone->id;
            // Also index without spaces
            $carry[strtolower(str_replace(' ', '', $key))] = $zone->id;
            return $carry;
        }, []);
        $zoneOrder = $zones->values(); // 0-based order

        $updated = 0;
        $reassigned = 0;
        $invalidRows = 0;
        $invalidStructure = 0;
        $invalidMissingValues = 0;
        $invalidUnknownZone = 0;
        $unknownZoneSamples = [];

        // Build current countries per zone for quick removal/add
        $zoneCountries = [];
        foreach ($zones as $zone) {
            $zoneCountries[$zone->id] = collect($zone->countries ?? [])
                ->map(function ($c) { return is_string($c) ? $c : strval($c); })
                ->values()
                ->all();
        }

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            // Skip empty rows
            if (count($row) < max($countryIdx, $zoneIdx) + 1) {
                $invalidRows++;
                $invalidStructure++;
                continue;
            }

            $rawCountry = trim($row[$countryIdx] ?? '');
            $rawZone = trim($row[$zoneIdx] ?? '');

            if ($rawCountry === '' || $rawZone === '') {
                $invalidRows++;
                $invalidMissingValues++;
                continue;
            }

            // Normalize country name: title-case, preserve common acronyms
            $country = $this->normalizeCountryName($rawCountry);

            // Resolve zone id
            $zoneId = null;
            $normZone = strtolower($rawZone);
            $normZoneNoSpace = strtolower(str_replace(' ', '', $rawZone));

            if (isset($zoneLookupByName[$normZone])) {
                $zoneId = $zoneLookupByName[$normZone];
            } elseif (isset($zoneLookupByName[$normZoneNoSpace])) {
                $zoneId = $zoneLookupByName[$normZoneNoSpace];
            } elseif (is_numeric($normZone)) {
                $index = (int) $normZone - 1;
                if ($zoneOrder->has($index)) {
                    $zoneId = $zoneOrder->get($index)->id;
                }
            } elseif (preg_match('/^zone\s*[-_]?\s*(\d+)$/i', $rawZone, $matches)) {
                $index = ((int) $matches[1]) - 1;
                if ($zoneOrder->has($index)) {
                    $zoneId = $zoneOrder->get($index)->id;
                }
            }

            if (!$zoneId) {
                $invalidRows++;
                $invalidUnknownZone++;
                if (count($unknownZoneSamples) < 5) {
                    $unknownZoneSamples[] = $rawZone;
                }
                continue;
            }

            // Remove country from any zone it's currently in
            foreach ($zoneCountries as $zId => $countries) {
                if (($key = array_search($country, $countries, true)) !== false) {
                    unset($zoneCountries[$zId][$key]);
                    $zoneCountries[$zId] = array_values($zoneCountries[$zId]);
                    $reassigned++;
                }
            }

            // Add to target zone (avoid duplicates)
            if (!in_array($country, $zoneCountries[$zoneId], true)) {
                $zoneCountries[$zoneId][] = $country;
                $updated++;
            }
        }

        fclose($handle);

        // Persist changes
        foreach ($zones as $zone) {
            $zone->update(['countries' => array_values($zoneCountries[$zone->id])]);
        }

        $message = 'Zones import completed. ' . $updated . ' assigned, ' . $reassigned . ' reassignments, ' . $invalidRows . ' invalid rows.';
        if ($invalidRows > 0) {
            $message .= ' Invalid breakdown → wrong columns: ' . $invalidStructure
                . ', missing country/zone: ' . $invalidMissingValues
                . ', unknown zone: ' . $invalidUnknownZone . '.';

            if (!empty($unknownZoneSamples)) {
                $message .= ' Unknown zone samples: ' . implode(', ', array_unique($unknownZoneSamples)) . '.';
            }
        }

        return back()->with('success', $message);
    }

    private function bootstrapZonesFromCsvIfEmpty(string $filePath, string $delimiter, int $zoneIdx): void
    {
        if (ShippingZone::query()->exists()) {
            return;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return;
        }

        // Skip header
        fgetcsv($handle, 0, $delimiter);

        $maxNumericIndex = 0;
        $zoneNames = [];

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            if (!isset($row[$zoneIdx])) {
                continue;
            }

            $rawZone = trim((string) $row[$zoneIdx]);
            if ($rawZone === '') {
                continue;
            }

            if (is_numeric($rawZone)) {
                $maxNumericIndex = max($maxNumericIndex, (int) $rawZone);
                continue;
            }

            if (preg_match('/^zone\s*[-_]?\s*(\d+)$/i', $rawZone, $matches)) {
                $maxNumericIndex = max($maxNumericIndex, (int) $matches[1]);
                continue;
            }

            $normalized = strtolower(trim($rawZone));
            if (!isset($zoneNames[$normalized])) {
                $zoneNames[$normalized] = $rawZone;
            }
        }

        fclose($handle);

        for ($i = 1; $i <= $maxNumericIndex; $i++) {
            ShippingZone::create([
                'name' => 'Zone ' . $i,
                'countries' => [],
            ]);
        }

        foreach ($zoneNames as $zoneName) {
            $exists = ShippingZone::whereRaw('LOWER(name) = ?', [strtolower($zoneName)])->exists();
            if (!$exists) {
                ShippingZone::create([
                    'name' => $zoneName,
                    'countries' => [],
                ]);
            }
        }
    }

    private function detectCsvDelimiter(string $filePath): string
    {
        $firstLine = '';
        $handle = fopen($filePath, 'r');
        if ($handle) {
            $firstLine = fgets($handle) ?: '';
            fclose($handle);
        }

        if ($firstLine === '') {
            return ',';
        }

        $candidates = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $bestCount = 0;

        foreach ($candidates as $candidate) {
            $columns = str_getcsv($firstLine, $candidate);
            $count = count($columns);
            if ($count > $bestCount) {
                $bestCount = $count;
                $bestDelimiter = $candidate;
            }
        }

        return $bestDelimiter;
    }

    private function normalizeCountryName(string $name): string
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            return '';
        }

        // Normalize short alphabetic country codes (ISO-like) to uppercase, e.g. us -> US
        if (preg_match('/^[a-zA-Z]{2,3}$/', $trimmed)) {
            return strtoupper($trimmed);
        }

        return ucwords(strtolower($trimmed));
    }

    /**
     * Downloadable CSV template for country → zone mapping.
     */
    public function templateZonesCsv(): StreamedResponse
    {
        $zones = ShippingZone::orderBy('id')->pluck('name')->values();
        $fileName = 'country_zone_template.csv';

        return response()->streamDownload(function () use ($zones) {
            $output = fopen('php://output', 'w');
            // Header
            fputcsv($output, ['country', 'zone']);

            $firstZone = $zones->get(0);
            $secondZone = $zones->get(1) ?? $firstZone;

            // Optional example rows using existing zone names
            if ($firstZone) {
                fputcsv($output, ['US', $firstZone]);
                fputcsv($output, ['Germany', $secondZone]);
            } else {
                fputcsv($output, ['US', 'Existing Zone Name']);
                fputcsv($output, ['Germany', 'Existing Zone Name']);
            }

            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }

    /**
     * Downloadable CSV template for shipping rates aligned to current zones.
     * Header: min_weight, max_weight, {Zone Names...}
     */
    public function templateRatesCsv(): StreamedResponse
    {
        $zones = ShippingZone::orderBy('id')->get();
        $fileName = 'shipping_rates_template.csv';

        return response()->streamDownload(function () use ($zones) {
            $output = fopen('php://output', 'w');
            $header = array_merge(['min_weight', 'max_weight'], $zones->pluck('name')->toArray());
            fputcsv($output, $header);

            // Provide two sample rows with empty prices
            $row1 = array_merge([0, 5], array_fill(0, max(count($zones), 1), ''));
            $row2 = array_merge([5.001, 10], array_fill(0, max(count($zones), 1), ''));
            fputcsv($output, $row1);
            fputcsv($output, $row2);
            fclose($output);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
