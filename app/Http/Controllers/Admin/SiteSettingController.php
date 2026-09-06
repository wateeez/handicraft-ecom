<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SiteSettingController extends Controller
{
    public function index()
    {
        $settings = SiteSetting::orderBy('group')->orderBy('key')->get();
        $groupedSettings = $settings->groupBy('group');
        
        // Get individual settings for easier access in view
        $siteName = SiteSetting::where('key', 'site_name')->first();
        $navbarLogo = SiteSetting::where('key', 'navbar_logo')->first();
        $footerLogo = SiteSetting::where('key', 'footer_logo')->first();
        $favicon = SiteSetting::where('key', 'favicon')->first();
        $footerQrCode = SiteSetting::where('key', 'footer_qr_code')->first();
        $whatsappNumber = SiteSetting::where('key', 'whatsapp_number')->first();
        $whatsappMessageTemplate = SiteSetting::where('key', 'whatsapp_message_template')->first();
        $shippingPolicy = SiteSetting::where('key', 'shipping_policy')->first();
        $footerAddress = SiteSetting::where('key', 'footer_address')->first();
        $footerPhone = SiteSetting::where('key', 'footer_phone')->first();
        $footerEmail = SiteSetting::where('key', 'footer_email')->first();
        $footerHours = SiteSetting::where('key', 'footer_hours')->first();
        return view('admin.settings.index', compact(
            'groupedSettings', 
            'siteName', 
            'navbarLogo', 
            'footerLogo', 
            'favicon', 
            'footerQrCode',
            'whatsappNumber',
            'whatsappMessageTemplate',
            'shippingPolicy',
            'footerAddress',
            'footerPhone',
            'footerEmail',
            'footerHours'
        ));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'site_name' => 'nullable|string|max:255',
            'whatsapp_number'           => 'nullable|string|max:20',
            'whatsapp_message_template' => 'nullable|string|max:500',
            'shipping_policy' => 'nullable|string',
            'navbar_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'footer_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'favicon' => 'nullable|image|mimes:ico,png,jpg,jpeg,gif,svg|max:1024',
            'footer_qr_code' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            // Use "sometimes" so regex runs only when a value is actually provided
            'color_primary'   => ['sometimes', 'nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'color_secondary' => ['sometimes', 'nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'color_accent'    => ['sometimes', 'nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'color_background'=> ['sometimes', 'nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'color_text'      => ['sometimes', 'nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
        ]);

        // Update site name
        if ($request->has('site_name')) {
            SiteSetting::set('site_name', $request->site_name);
        }

        // Update WhatsApp number
        if ($request->has('whatsapp_number')) {
            SiteSetting::set('whatsapp_number', $request->whatsapp_number, 'text', 'contact');
        }

        // Update WhatsApp message template
        if ($request->has('whatsapp_message_template')) {
            SiteSetting::set('whatsapp_message_template', $request->whatsapp_message_template, 'text', 'contact');
        }

        // Update Shipping Policy
        if ($request->has('shipping_policy')) {
            SiteSetting::set('shipping_policy', $request->shipping_policy, 'textarea', 'content');
        }

        // Update Footer Contact Info
        if ($request->has('footer_address')) {
            SiteSetting::set('footer_address', $request->footer_address, 'textarea', 'contact');
        }
        if ($request->has('footer_phone')) {
            SiteSetting::set('footer_phone', $request->footer_phone, 'text', 'contact');
        }
        if ($request->has('footer_email')) {
            SiteSetting::set('footer_email', $request->footer_email, 'text', 'contact');
        }
        if ($request->has('footer_hours')) {
            SiteSetting::set('footer_hours', $request->footer_hours, 'text', 'contact');
        }

        // Update Theme Colors
        if ($request->has('color_primary')) {
            SiteSetting::set('color_primary', $request->color_primary, 'color', 'theme');
        }
        if ($request->has('color_secondary')) {
            SiteSetting::set('color_secondary', $request->color_secondary, 'color', 'theme');
        }
        if ($request->has('color_accent')) {
            SiteSetting::set('color_accent', $request->color_accent, 'color', 'theme');
        }
        if ($request->has('color_background')) {
            SiteSetting::set('color_background', $request->color_background, 'color', 'theme');
        }
        if ($request->has('color_text')) {
            SiteSetting::set('color_text', $request->color_text, 'color', 'theme');
        }

        // Handle navbar logo upload
        if ($request->hasFile('navbar_logo')) {
            $this->handleLogoUpload($request->file('navbar_logo'), 'navbar_logo');
        }

        // Handle footer logo upload
        if ($request->hasFile('footer_logo')) {
            $this->handleLogoUpload($request->file('footer_logo'), 'footer_logo');
        }

        // Handle favicon upload
        if ($request->hasFile('favicon')) {
            $this->handleLogoUpload($request->file('favicon'), 'favicon');
        }

        // Handle QR code upload
        if ($request->hasFile('footer_qr_code')) {
            $this->handleLogoUpload($request->file('footer_qr_code'), 'footer_qr_code');
        }

        // Handle logo removal
        if ($request->has('remove_navbar_logo')) {
            $this->removeLogo('navbar_logo');
        }

        if ($request->has('remove_footer_logo')) {
            $this->removeLogo('footer_logo');
        }

        if ($request->has('remove_favicon')) {
            $this->removeLogo('favicon');
        }

        if ($request->has('remove_footer_qr_code')) {
            $this->removeLogo('footer_qr_code');
        }

        SiteSetting::clearCache();

        return redirect()->route('admin.settings.index')
            ->with('success', 'Settings updated successfully!');
    }

    private function handleLogoUpload($file, $key)
    {
        // Delete old logo if exists
        $oldSetting = SiteSetting::where('key', $key)->first();
        if ($oldSetting && $oldSetting->value) {
            Storage::disk(\App\Models\Product::mediaDisk())->delete($oldSetting->value);
        }

        // Store new logo on the environment-appropriate disk.
        // Stores the relative path only (not the full URL) so that getLogoUrl()
        // can rebuild the correct URL for both local and production environments.
        $path = $file->store('logos', \App\Models\Product::mediaDisk());
        SiteSetting::set($key, $path, 'image', 'logo');
    }

    private function removeLogo($key)
    {
        $setting = SiteSetting::where('key', $key)->first();
        if ($setting && $setting->value) {
            Storage::disk(\App\Models\Product::mediaDisk())->delete($setting->value);
            $setting->update(['value' => null]);
        }
    }
}
