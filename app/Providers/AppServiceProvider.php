<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use App\Models\SiteSetting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }

        // Share site settings with all views (with error handling for migrations)
        View::composer('*', function ($view) {
            try {
                // Check if the site_settings table exists before querying
                if (Schema::hasTable('site_settings')) {
                    // We use rescue() to ensure that even if one setting or the storage disk fails, 
                    // the entire site doesn't 500.
                    $navbarLogo   = rescue(fn() => SiteSetting::where('key', 'navbar_logo')->first());
                    $footerLogo   = rescue(fn() => SiteSetting::where('key', 'footer_logo')->first());
                    $favicon      = rescue(fn() => SiteSetting::where('key', 'favicon')->first());
                    $footerQrCode = rescue(fn() => SiteSetting::where('key', 'footer_qr_code')->first());

                    $view->with('siteSettings', [
                        'site_name'          => rescue(fn() => SiteSetting::get('site_name', 'Handicraft Nepal NP'), 'Handicraft Nepal NP'),
                        'navbar_logo'        => $navbarLogo,
                        'footer_logo'        => $footerLogo,
                        'favicon'            => $favicon,
                        'footer_qr_code'     => $footerQrCode,
                        'navbar_logo_url'    => rescue(fn() => $navbarLogo?->getLogoUrl()),
                        'footer_logo_url'    => rescue(fn() => $footerLogo?->getLogoUrl()),
                        'favicon_url'        => rescue(fn() => $favicon?->getLogoUrl()),
                        'footer_qr_code_url' => rescue(fn() => $footerQrCode?->getLogoUrl()),
                        'whatsapp_number'          => rescue(fn() => SiteSetting::get('whatsapp_number', '') ?: env('WHATSAPP_NUMBER', ''), ''),
                        'whatsapp_message_template' => rescue(fn() => SiteSetting::get('whatsapp_message_template', '') ?: env('WHATSAPP_MESSAGE_TEMPLATE', ''), ''),
                        'footer_address'     => rescue(fn() => SiteSetting::get('footer_address', ''), ''),
                        'footer_phone'       => rescue(fn() => SiteSetting::get('footer_phone', ''), ''),
                        'footer_email'       => rescue(fn() => SiteSetting::get('footer_email', ''), ''),
                        'footer_hours'       => rescue(fn() => SiteSetting::get('footer_hours', ''), ''),
                        // Theme Colors
                        'color_primary'      => rescue(fn() => SiteSetting::get('color_primary', '#2D4B36'), '#2D4B36'),
                        'color_secondary'    => rescue(fn() => SiteSetting::get('color_secondary', '#C5A059'), '#C5A059'),
                        'color_accent'       => rescue(fn() => SiteSetting::get('color_accent', '#1A3326'), '#1A3326'),
                        'color_background'   => rescue(fn() => SiteSetting::get('color_background', '#E8E2D2'), '#E8E2D2'),
                        'color_text'         => rescue(fn() => SiteSetting::get('color_text', '#2D4B36'), '#2D4B36'),
                    ]);
                } else {
                    throw new \Exception('Table not ready');
                }
            } catch (\Throwable $e) {
                $view->with('siteSettings', [
                    'site_name'          => 'Handicraft Nepal NP',
                    'navbar_logo'        => null,
                    'footer_logo'        => null,
                    'favicon'            => null,
                    'footer_qr_code'     => null,
                    'navbar_logo_url'    => null,
                    'footer_logo_url'    => null,
                    'favicon_url'        => null,
                    'footer_qr_code_url' => null,
                    'whatsapp_number'          => env('WHATSAPP_NUMBER', ''),
                    'whatsapp_message_template' => env('WHATSAPP_MESSAGE_TEMPLATE', ''),
                    'footer_address'     => '',
                    'footer_phone'       => '',
                    'footer_email'       => '',
                    'footer_hours'       => '',
                ]);
            }
        });
    }
}
