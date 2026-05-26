<?php
use App\Models\SiteSetting;

$setting = SiteSetting::where('key', 'site_name')->first();
if ($setting && $setting->value === 'LuxeStore') {
    $setting->value = 'Handicraft Nepal NP';
    $setting->save();
    echo 'Updated in DB';
} else {
    echo 'No update needed or not found';
}
