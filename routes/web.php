<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\ProductController;
use App\Http\Controllers\Frontend\CartController;
use App\Http\Controllers\Frontend\InquiryController as FrontendInquiryController;
use App\Http\Controllers\Frontend\PageController;
use App\Http\Controllers\Frontend\RobotsController;
use App\Http\Controllers\Frontend\SitemapController;
use App\Http\Controllers\Frontend\LlmsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\InquiryController as AdminInquiryController;
use App\Http\Controllers\Admin\ShippingController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SiteSettingController;
use App\Http\Controllers\Admin\BlogController as AdminBlogController;
use App\Http\Controllers\Frontend\BlogController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\ClientController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\FinancePaymentController;
use App\Http\Controllers\Admin\DistributorPayoutController;
use App\Http\Controllers\Admin\CommissionPayoutController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Frontend
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/llms.txt', [LlmsController::class, 'index'])->name('llms');

// Blog
Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.show');
Route::get('/products', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductController::class, 'show'])->name('products.show');
Route::get('/categories/{category:slug}', [ProductController::class, 'category'])->name('categories.show');

// Static Pages
Route::get('/shipping-policy', [PageController::class, 'shippingPolicy'])->name('pages.shipping-policy');
Route::get('/about', [PageController::class, 'about'])->name('pages.about');
Route::get('/contact', [PageController::class, 'contact'])->name('pages.contact');
Route::get('/returns', [PageController::class, 'returns'])->name('pages.returns');

// Cart & Checkout
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'updateQuantity'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'removeItem'])->name('cart.remove');
Route::get('/checkout/{token?}', [CartController::class, 'checkout'])->name('checkout'); // Token for inquiries
Route::post('/checkout/calculate-shipping', [CartController::class, 'calculateShipping'])->name('checkout.calculate-shipping');
Route::post('/checkout/init-order', [CartController::class, 'initOrder'])->name('checkout.init-order');
Route::post('/checkout/submit-quote', [CartController::class, 'submitQuote'])->name('checkout.submit-quote');
Route::get('/checkout/success/{orderNumber}', [CartController::class, 'orderSuccess'])->name('checkout.success');
Route::get('/quote/success', fn() => view('frontend.cart.quote-success'))->name('quote.success');

// Inquiry
Route::post('/products/{product}/inquire', [FrontendInquiryController::class, 'store'])->name('inquiry.store');

// Auth Routes
Auth::routes();

Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login.post');
Route::post('/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// Admin
Route::middleware(['auth', 'viewer.readonly'])->prefix('admin')->name('admin.')->group(function () {
    Route::middleware('permission:view_dashboard')->get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::middleware('permission:view_finance')->get('finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::middleware('permission:view_finance')->get('finance/report-summary', [FinanceController::class, 'reportSummary'])->name('finance.report-summary');
    Route::middleware('permission:view_finance')->get('finance/clients/{client}/summary', [FinanceController::class, 'clientSummary'])->name('finance.clients.summary');
    Route::middleware('permission:manage_finance')->put('finance/settings', [FinanceController::class, 'updateSettings'])->name('finance.settings.update');
    Route::middleware('permission:record_payments')->post('finance/orders/{order}/payments', [FinancePaymentController::class, 'store'])->name('finance.payments.store');
    Route::middleware('permission:confirm_payments')->post('finance/payments/{payment}/confirm', [FinancePaymentController::class, 'confirm'])->name('finance.payments.confirm');
    Route::middleware('permission:confirm_payments')->post('finance/payments/{payment}/reverse', [FinancePaymentController::class, 'reverse'])->name('finance.payments.reverse');
    Route::middleware('permission:confirm_payments')->post('finance/refunds/{refund}/confirm', [FinancePaymentController::class, 'confirmRefund'])->name('finance.refunds.confirm');
    Route::middleware('permission:manage_finance')->post('finance/distributor-payouts', [DistributorPayoutController::class, 'store'])->name('finance.distributor-payouts.store');
    Route::middleware('permission:manage_finance')->post('finance/commission-payouts', [CommissionPayoutController::class, 'store'])->name('finance.commission-payouts.store');
    Route::middleware('permission:manage_settings')->group(function () {
        Route::post('/maintenance/toggle', [DashboardController::class, 'toggleMaintenance'])->name('maintenance.toggle');
        Route::post('/verify-password', [DashboardController::class, 'verifyPassword'])->name('verify-password');
    });

    // Categories
    Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class)
        ->only(['index', 'show'])
        ->middleware('permission:view_categories,manage_categories');
    Route::resource('categories', \App\Http\Controllers\Admin\CategoryController::class)
        ->except(['index', 'show'])
        ->middleware('permission:manage_categories');

    // Sub-Categories
    Route::middleware('permission:manage_categories')->group(function () {
        Route::get('subcategories/{subcategory}/edit', [\App\Http\Controllers\Admin\CategoryController::class, 'editSubCategory'])->name('subcategories.edit');
        Route::put('subcategories/{subcategory}', [\App\Http\Controllers\Admin\CategoryController::class, 'updateSubCategory'])->name('subcategories.update');
        Route::delete('subcategories/{subcategory}', [\App\Http\Controllers\Admin\CategoryController::class, 'destroySubCategory'])->name('subcategories.destroy');
    });

    // Products
    Route::resource('products', AdminProductController::class)
        ->only(['index', 'show'])
        ->middleware('permission:view_products,manage_products');
    Route::middleware('permission:manage_products')->group(function () {
        Route::resource('products', AdminProductController::class)->except(['index', 'show']);
        Route::get('products-bulk/template-csv', [AdminProductController::class, 'templateProductsCsv'])->name('products.template');
        Route::get('products-bulk/export', [AdminProductController::class, 'exportProducts'])->name('products.export');
        Route::post('products-bulk/import', [AdminProductController::class, 'importProducts'])->name('products.import');
        Route::post('products-bulk/upload-images', [AdminProductController::class, 'bulkUploadImages'])->name('products.bulk-images');
    });

    // Inquiries
    Route::middleware('permission:view_inquiries,manage_inquiries')->group(function () {
        Route::get('inquiries', [AdminInquiryController::class, 'index'])->name('inquiries.index');
        Route::get('inquiries/{inquiry}', [AdminInquiryController::class, 'show'])->name('inquiries.show');
    });
    Route::middleware('permission:manage_inquiries')->group(function () {
        Route::post('inquiries/{inquiry}/reply', [AdminInquiryController::class, 'reply'])->name('inquiries.reply');
        Route::post('inquiries/{inquiry}/send-checkout', [AdminInquiryController::class, 'sendCheckout'])->name('inquiries.send-checkout');
    });

    // Clients
    Route::resource('clients', ClientController::class)->middleware('permission:manage_clients');

    // Orders
    Route::resource('orders', OrderController::class)
        ->only(['index', 'show'])
        ->middleware('permission:view_orders,manage_orders');
    Route::middleware('permission:manage_orders')->group(function () {
        Route::post('orders/calculate-shipping', [OrderController::class, 'calculateShipping'])->name('orders.calculate-shipping');
        Route::resource('orders', OrderController::class)->except(['index', 'show']);
        Route::post('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::post('orders/{order}/mark-paid', [OrderController::class, 'markPaid'])->name('orders.mark-paid');
        Route::post('orders/{order}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');
        Route::post('orders/{order}/generate-checkout-link', [OrderController::class, 'generateCheckoutLink'])->name('orders.generate-checkout-link');
        Route::post('orders/{order}/mark-all-returned', [OrderController::class, 'markAllReturned'])->name('orders.mark-all-returned');
        Route::post('orders/{order}/clear-all-returns', [OrderController::class, 'clearAllReturns'])->name('orders.clear-all-returns');
        Route::post('orders/{order}/items/{item}/return', [OrderController::class, 'returnItem'])->name('orders.items.return');
        Route::post('orders/{order}/items/{item}/undo-return', [OrderController::class, 'undoItemReturn'])->name('orders.items.undo-return');
    });
    Route::post('orders/merge', [OrderController::class, 'merge'])
        ->middleware('permission:merge_orders')
        ->name('orders.merge');

    // Invoices
    Route::middleware('permission:view_finance,manage_invoices')->group(function () {
        Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
        Route::get('invoices/{invoice}/download', [InvoiceController::class, 'download'])->name('invoices.download');
    });
    Route::middleware('permission:manage_invoices')->group(function () {
        Route::post('orders/{order}/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
        Route::post('invoices/{invoice}/issue', [InvoiceController::class, 'issue'])->name('invoices.issue');
    });
    Route::post('invoices/{invoice}/void', [InvoiceController::class, 'void'])
        ->middleware('permission:void_invoices')
        ->name('invoices.void');

    // Shipping
    Route::middleware('permission:view_shipping,manage_shipping')->group(function () {
        Route::get('shipping', [ShippingController::class, 'index'])->name('shipping.index');
        Route::get('shipping/zones/settings', [ShippingController::class, 'zonesSettings'])->name('shipping.zones.settings');
        Route::get('shipping/providers/settings', [ShippingController::class, 'providersSettings'])->name('shipping.providers.settings');
        Route::get('shipping/providers/{provider}', [ShippingController::class, 'providerDetails'])->name('shipping.providers.show');
        Route::get('shipping/rates/settings', [ShippingController::class, 'ratesSettings'])->name('shipping.rates.settings');
        Route::get('shipping/rates/export', [ShippingController::class, 'exportRates'])->name('shipping.rates.export');
        Route::get('shipping/rates/template-csv', [ShippingController::class, 'templateRatesCsv'])->name('shipping.rates.template');
        Route::get('shipping/zones/export-csv', [ShippingController::class, 'exportZonesCsv'])->name('shipping.zones.export');
        Route::get('shipping/zones/template-csv', [ShippingController::class, 'templateZonesCsv'])->name('shipping.zones.template');
    });

    Route::middleware('permission:manage_shipping')->group(function () {
        Route::delete('shipping/providers/{provider}', [ShippingController::class, 'destroyProvider'])->name('shipping.providers.destroy');
        Route::post('shipping/zones', [ShippingController::class, 'storeZone'])->name('shipping.zones.store');
        Route::get('shipping/zones/{zone}/edit', [ShippingController::class, 'editZone'])->name('shipping.zones.edit');
        Route::put('shipping/zones/{zone}', [ShippingController::class, 'updateZone'])->name('shipping.zones.update');
        Route::delete('shipping/zones/{zone}', [ShippingController::class, 'destroyZone'])->name('shipping.zones.destroy');
        Route::post('shipping/providers', [ShippingController::class, 'storeProvider'])->name('shipping.providers.store');
        Route::post('shipping/rates', [ShippingController::class, 'storeRate'])->name('shipping.rates.store');
        Route::post('shipping/rates/import', [ShippingController::class, 'importRates'])->name('shipping.rates.import');
        Route::get('shipping/rates/{rate}/edit', [ShippingController::class, 'editRate'])->name('shipping.rates.edit');
        Route::put('shipping/rates/{rate}', [ShippingController::class, 'updateRate'])->name('shipping.rates.update');
        Route::delete('shipping/rates/{rate}', [ShippingController::class, 'destroyRate'])->name('shipping.rates.destroy');
        Route::post('shipping/zones/import-csv', [ShippingController::class, 'importZonesCsv'])->name('shipping.zones.import');
    });

    // Site Settings (Logo Management)
    Route::middleware('permission:manage_settings')->group(function () {
        Route::get('settings', [SiteSettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [SiteSettingController::class, 'update'])->name('settings.update');
    });

    // Blog Management
    Route::resource('blog', AdminBlogController::class)->middleware('permission:manage_blog');

    // Admin User Management (permission-gated)
    Route::middleware('permission:manage_users')->group(function () {
        Route::resource('users', AdminUserController::class);
        Route::patch('users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
        Route::get('users/{user}/reset-password', [AdminUserController::class, 'showResetPasswordForm'])->name('users.reset-password');
        Route::put('users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password.update');

        // Role assignment actions
        Route::post('users/{user}/roles', [AdminUserController::class, 'assignRole'])->name('users.roles.assign');
        Route::delete('users/{user}/roles/{role}', [AdminUserController::class, 'revokeRole'])->name('users.roles.revoke');
    });

    // Role Management (permission-gated)
    Route::middleware('permission:manage_roles')->group(function () {
        Route::resource('roles', RoleController::class);
    });
});
  
// Resend mail server
Route::get('/test-resend', function () {
    $resend = \Resend::client(env('RESEND_API_KEY'));

    return $resend->emails->send([
        'from' => env('MAIL_FROM_NAME') . ' <' . env('MAIL_FROM_ADDRESS') . '>',
        'to' => ['delivered@resend.dev'],
        'subject' => 'Test Email',
        'html' => '<strong>It works!</strong>'
    ]);
});