<?php

use App\Http\Controllers\Store\CartController;
use App\Http\Controllers\Store\CheckoutController;
use App\Http\Controllers\Store\HomeController;
use App\Http\Controllers\Store\ProductController;
use App\Http\Controllers\Store\PageController as StorePageController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\Admin\AdminAccountController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MediaController as AdminMediaController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProfitController as AdminProfitController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\SeoController as AdminSeoController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\SettingsController as AdminSettingsController;
use App\Http\Controllers\Admin\TrackingController as AdminTrackingController;
use App\Http\Controllers\Tracking\TrackingEventController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', HomeController::class)->name('home');

Route::get('/category/{category:slug}', [ProductController::class, 'category'])->name('categories.show');
Route::get('/shop', [ProductController::class, 'index'])->name('products.index');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show');

Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/{product:id}', [CartController::class, 'store'])->middleware('throttle:60,1')->name('cart.store');
Route::put('/cart/{product:id}', [CartController::class, 'update'])->middleware('throttle:60,1')->name('cart.update');
Route::delete('/cart/{product:id}', [CartController::class, 'destroy'])->middleware('throttle:60,1')->name('cart.destroy');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->middleware('throttle:10,1')->name('cart.coupon');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->middleware('throttle:10,1')->name('cart.coupon.destroy');

Route::get('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:5,1')->name('checkout.store');
Route::get('/checkout/success/{orderNumber}', [CheckoutController::class, 'success'])->where('orderNumber', 'LB-[A-Z0-9]{14}')->name('checkout.success');

Route::prefix('admin')->as('admin.')->group(function (): void {
    Route::middleware('guest')->group(function (): void {
        Route::get('/login', [AdminLoginController::class, 'create'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
    });

    Route::middleware(['auth', 'admin'])->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('/logout', [AdminLoginController::class, 'destroy'])->name('logout');
        Route::get('/account', [AdminAccountController::class, 'edit'])->name('account.edit');
        Route::put('/account/password', [AdminAccountController::class, 'updatePassword'])->middleware('throttle:5,1')->name('account.password');
        Route::resource('categories', AdminCategoryController::class)->except('show');
        Route::resource('brands', AdminBrandController::class)->except('show');
        Route::resource('products', AdminProductController::class)->except('show');
        Route::resource('coupons', AdminCouponController::class)->except('show');
        Route::resource('pages', AdminPageController::class)->except('show');
        Route::get('/settings', [AdminSettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [AdminSettingsController::class, 'update'])->name('settings.update');
        Route::get('/tracking', [AdminTrackingController::class, 'index'])->name('tracking.index');
        Route::put('/tracking/settings', [AdminTrackingController::class, 'update'])->name('tracking.settings.update');
        Route::post('/tracking/reports', [AdminTrackingController::class, 'generateReport'])->middleware('throttle:3,1')->name('tracking.reports.store');
        Route::post('/tracking/deliveries/{delivery}/retry', [AdminTrackingController::class, 'retryDelivery'])->middleware('throttle:10,1')->name('tracking.deliveries.retry');
        Route::get('/tracking/export.csv', [AdminTrackingController::class, 'export'])->name('tracking.export');
        Route::get('/tracking/configuration-backup.json', [AdminTrackingController::class, 'backup'])->name('tracking.backup');
        Route::get('/media', [AdminMediaController::class, 'index'])->name('media.index');
        Route::put('/media/{media}', [AdminMediaController::class, 'update'])->name('media.update');
        Route::delete('/media/{media}', [AdminMediaController::class, 'destroy'])->name('media.destroy');
        Route::get('/profit', [AdminProfitController::class, 'index'])->name('profit.index');
        Route::get('/seo/facebook-commerce', [AdminSeoController::class, 'facebookCommerce'])->name('seo.facebook-commerce');
        Route::post('/seo/facebook-commerce/update', [AdminSeoController::class, 'updateFacebookCommerce'])->middleware('throttle:10,1')->name('seo.facebook-commerce.update');
        Route::get('/seo/sitemap', [AdminSeoController::class, 'sitemap'])->name('seo.sitemap');
        Route::post('/seo/sitemap/update', [AdminSeoController::class, 'updateSitemap'])->middleware('throttle:10,1')->name('seo.sitemap.update');
        Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::put('/orders/{order}', [AdminOrderController::class, 'update'])->name('orders.update');
    });
});

// Public first-party event receiver. It stores only allowlisted event fields;
// admin routes remain protected by the existing auth + admin middleware above.
Route::post('/tracking/events', [TrackingEventController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('tracking.events.store');

Route::get('/{page:slug}', [StorePageController::class, 'show'])->where('page', '[a-z0-9]+(?:-[a-z0-9]+)*')->name('pages.show');
