<?php

use App\Http\Controllers\AccountManagerReportController;
use App\Http\Controllers\BankReconciliationTemplateController;
use App\Http\Controllers\BankStatementFileController;
use App\Http\Controllers\NotaDinasInvoiceFileController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\Front\AsetFeatureController;
use App\Http\Controllers\Front\AuthController;
use App\Http\Controllers\Front\BiayaFeatureController;
use App\Http\Controllers\Front\HomeController;
use App\Http\Controllers\Front\InvoiceController as FrontInvoiceController;
use App\Http\Controllers\Front\LaporanFeatureController;
use App\Http\Controllers\Front\PayrollFeatureController;
use App\Http\Controllers\Front\ProductCatalogController;
use App\Http\Controllers\Front\RegistrationController;
use App\Http\Controllers\FrontendDataPribadiController;
use App\Http\Controllers\InvoiceOrderController;
use App\Http\Controllers\JournalPdfController;
use App\Http\Controllers\LaporanKeuanganController;
use App\Http\Controllers\NotaDinasPdfController;
use App\Http\Controllers\PayrollSlipController;
use App\Http\Controllers\ProductDisplayController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\Profile\AdminToolsController;
use App\Http\Controllers\ProspectAppController;
use App\Http\Controllers\ProspectController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SimulasiDisplayController;
use App\Http\Controllers\SopPrintController;
use App\Http\Controllers\UserFormPdfController;
use App\Http\Controllers\LeaveApprovalController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\DocumentationController;
use App\Enums\OrderStatus;
use App\Models\DataPembayaran;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

$authNoStore = ['filament.auth', 'no-store'];
$authNoStoreThrottle = [...$authNoStore, 'throttle:60,1'];

// Bank Reconciliation Template Route
Route::get('/bank-reconciliation/template', [BankReconciliationTemplateController::class, 'downloadTemplate'])
    ->name('bank-reconciliation.template')
    ->middleware($authNoStoreThrottle);

Route::get('/brand/logo', [BrandController::class, 'logo'])->name('brand.logo');
Route::get('/brand/favicon', [BrandController::class, 'favicon'])->name('brand.favicon');
Route::get('/brand/login-image', [BrandController::class, 'loginImage'])->name('brand.login-image');

// Home route with proper method handling
Route::get('/', [HomeController::class, 'index'])->name('home');

// SIMULASI
// Rute untuk preview HTML simulasi produk
Route::get('/simulasi/{record:slug}', [SimulasiDisplayController::class, 'show'])
    ->name('simulasi.show')
    ->middleware($authNoStore);

// Rute untuk download PDF simulasi produk
Route::get('/simulasi/{record:slug}/download-pdf', [SimulasiDisplayController::class, 'downloadPdf'])
    ->name('simulasi.pdf')
    ->middleware($authNoStoreThrottle);

// Rute untuk draft kontrak simulasi produk
Route::get('/simulasi/{record:slug}/draft-kontrak', [SimulasiDisplayController::class, 'draftKontrak'])
    ->name('simulasi.draft-kontrak')
    ->middleware($authNoStoreThrottle);

// USER REGISTRATION FORM PDF
// Rute untuk generate form pendaftaran karyawan kosong (PDF)
Route::get('/hr/user-form/blank', [UserFormPdfController::class, 'generateBlankForm'])
    ->name('user-form.blank')
    ->middleware($authNoStoreThrottle);

// Rute untuk generate form pendaftaran karyawan terisi (PDF)
Route::post('/hr/user-form/filled', [UserFormPdfController::class, 'generateFilledForm'])
    ->name('user-form.filled')
    ->middleware($authNoStoreThrottle);

// Rute untuk generate form terisi dari session (GET request)
Route::get('/hr/user-form/filled-session', [UserFormPdfController::class, 'generateFilledFormFromSession'])
    ->name('user-form.filled-session')
    ->middleware($authNoStoreThrottle);

// PAYROLL SLIP GAJI
// Rute untuk download PDF slip gaji
Route::get('/payroll/{record}/slip-gaji', [PayrollSlipController::class, 'download'])
    ->name('payroll.slip-gaji.download')
    ->middleware($authNoStoreThrottle);

// LEAVE APPROVAL DETAIL
// Rute untuk melihat detail persetujuan cuti
Route::get('/leave-request/{leaveRequest}/approval-detail', [LeaveApprovalController::class, 'show'])
    ->name('leave-request.approval-detail')
    ->middleware($authNoStore);

// LEAVE REQUEST FORM
Route::get('/leave/show', [LeaveRequestController::class, 'create'])
    ->name('leave.show')
    ->middleware($authNoStore);

Route::get('/leave/create', [LeaveRequestController::class, 'create'])
    ->name('leave.create')
    ->middleware($authNoStore);

Route::post('/leave', [LeaveRequestController::class, 'store'])
    ->name('leave.store')
    ->middleware($authNoStore);

Route::put('/leave/{id}', [LeaveRequestController::class, 'update'])
    ->name('leave.update')
    ->middleware($authNoStore)
    ->whereNumber('id');

Route::get('/leave/status', [LeaveRequestController::class, 'status'])
    ->name('leave.status')
    ->middleware($authNoStore);

// DOCUMENT
Route::get('/document/{record}/stream', [DocumentController::class, 'stream'])
    ->name('document.stream')
    ->middleware($authNoStoreThrottle);

// SOP PRINT ROUTES
Route::get('/sops/{id}/print', [SopPrintController::class, 'show'])
    ->name('sop.print')
    ->middleware($authNoStore)
    ->whereNumber('id');
Route::get('/sops/{id}/pdf', [SopPrintController::class, 'pdf'])
    ->name('sop.pdf')
    ->middleware($authNoStoreThrottle)
    ->whereNumber('id');

// FRONTEND FEATURES
Route::get('/features/invoice', [FrontInvoiceController::class, 'index'])->name('front.invoice');
Route::get('/features/biaya', [BiayaFeatureController::class, 'index'])->name('front.biaya_feature');
Route::get('/features/laporan', [LaporanFeatureController::class, 'index'])->name('front.laporan_feature');
Route::get('/features/aset', [AsetFeatureController::class, 'index'])->name('front.aset_feature');
// Route::get('/features/hris', [HrisFeatureController::class, 'index'])->name('front.hris_feature');
Route::get('/features/payroll', [PayrollFeatureController::class, 'index'])->name('front.payroll_feature');

// PRICING
Route::view('/harga', 'front.harga')->name('harga');

Route::get('/product', [ProductCatalogController::class, 'index'])->name('product');

Route::get('/pendaftaran', [RegistrationController::class, 'pendaftaran'])->name('pendaftaran');

// CONTACT
Route::view('/kontak', 'front.kontak')->name('kontak');

// BLOG
Route::get('/blog', [BlogController::class, 'index'])->name('blog');
Route::get('/blog/search', [BlogController::class, 'search'])->name('blog.search');
Route::get('/blog/category/{category}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/{slug}', [BlogController::class, 'show'])->name('blog.detail');

// INVOICE
Route::middleware($authNoStore)->group(function () {
    Route::get('/invoice/{order}', [InvoiceOrderController::class, 'show'])
        ->name('invoice.show');
    Route::get('/invoice/{order}/download', [InvoiceOrderController::class, 'download'])
        ->name('invoice.download')
        ->middleware('throttle:60,1');
    Route::get('/invoice/{order}/print', [InvoiceOrderController::class, 'print'])
        ->name('invoice.print')
        ->middleware('throttle:60,1');
    Route::post('/invoice/{order}/update-payment', [InvoiceOrderController::class, 'updatePayment'])
        ->name('invoice.update-payment')
        ->middleware('throttle:20,1');

    Route::get('/bank-statements/{bankStatement}/download', [BankStatementFileController::class, 'download'])
        ->name('bank-statements.download')
        ->middleware('throttle:60,1');
    Route::get('/bank-statements/{bankStatement}/reconciliation/download', [BankStatementFileController::class, 'downloadReconciliation'])
        ->name('bank-statements.reconciliation.download')
        ->middleware('throttle:60,1');

    Route::get('/journal/pdf/preview', [JournalPdfController::class, 'preview'])
        ->name('journal.pdf.preview');
    Route::get('/journal/pdf/download', [JournalPdfController::class, 'download'])
        ->name('journal.pdf.download')
        ->middleware('throttle:60,1');
});

// WIDGET ROUTE
// Widget yang langsung link ke processing
Route::get('/orders/reports/customer-payments/{status}', [ReportController::class, 'customerPayments'])
    ->name('reports.customer-payments')
    ->middleware($authNoStore)
    ->whereIn('status', array_map(fn (OrderStatus $case) => $case->value, OrderStatus::cases()));

// REPORT ROUTES
// Route untuk Laporan DataPembayaran HTML
Route::get('/laporan/pembayaran/html', [ReportController::class, 'generateDataPembayaranHtmlReport'])
    ->name('data-pembayaran.html-report')
    ->middleware($authNoStore);

Route::get('/laporan/pembayaran/pdf', [ReportController::class, 'generateDataPembayaranPdfReport'])
    ->name('data-pembayaran.pdf-report')
    ->middleware($authNoStoreThrottle);

// Route untuk Laporan Pengeluaran Operasional HTML
Route::get('/laporan/expense-ops/html', [ReportController::class, 'generateExpenseOpsHtmlReport'])
    ->name('expense-ops.html-report')
    ->middleware($authNoStore);

// PRODUCT ROUTES
// Detail product
Route::get('/products/{product:slug}', [ProductDisplayController::class, 'show'])
    ->name('products.show')
    ->middleware($authNoStore);

Route::get('/products/{product}/download-pdf', [ProductDisplayController::class, 'downloadPdf'])
    ->name('products.downloadPdf')
    ->middleware($authNoStoreThrottle);

// Route for product details (preview, download, print)
Route::get('/products/{product:slug}/details/{action}', [ProductDisplayController::class, 'details'])
    ->whereIn('action', ['preview', 'download', 'print'])
    ->name('products.details')
    ->middleware($authNoStore);

// Route baru untuk ekspor detail produk ke Excel
Route::get('/products/{product}/export-excel-detail', [ProductDisplayController::class, 'exportDetailToExcel'])
    ->name('products.exportExcelDetail')
    ->middleware($authNoStoreThrottle);

// EXPENSE ROUTES
// Route untuk Laporan Pengeluaran Wedding HTML
Route::get('/laporan/expense/html', [ReportController::class, 'generateExpenseHtmlReport'])
    ->name('expense.html-report')
    ->middleware($authNoStore);

// Route untuk Laporan Pengeluaran Operasional PDF
Route::get('/laporan/expense-ops/pdf', [ReportController::class, 'generateExpenseOpsPdfReport'])
    ->name('expense-ops.pdf-report')
    ->middleware($authNoStoreThrottle);

// Route untuk Laporan Pengeluaran Wedding PDF
Route::get('/laporan/expense/pdf', [ReportController::class, 'generateExpensePdfReport'])
    ->name('expense.pdf-report')
    ->middleware($authNoStoreThrottle);

// Route untuk Laporan Net Cash Flow PDF Stream
Route::get('/laporan/net-cash-flow/pdf/stream', [ReportController::class, 'streamNetCashFlowPdf'])
    ->name('reports.net-cash-flow.pdf.stream')
    ->middleware($authNoStoreThrottle);

// RUTE DATA PRIBADI
// Route untuk menampilkan form tambah data pribadi
Route::get('/data-pribadi/tambah', [FrontendDataPribadiController::class, 'create'])
    ->name('data-pribadi.create')
    ->middleware($authNoStore);

// Route untuk menampilkan daftar data pribadi
Route::get('/data-pribadi', [FrontendDataPribadiController::class, 'index'])
    ->name('data-pribadi.index')
    ->middleware($authNoStore);

// Route untuk menyimpan data baru dari form
Route::post('/data-pribadi', [FrontendDataPribadiController::class, 'store'])
    ->name('data-pribadi.store')
    ->middleware($authNoStore);

// Route untuk halaman sukses setelah submit
Route::get('/data-pribadi/success', [FrontendDataPribadiController::class, 'success'])
    ->name('data-pribadi.success')
    ->middleware($authNoStore);

// AUTHENTICATION
Route::middleware(['guest', 'no-store'])->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('front.login');
    Route::post('/login', [AuthController::class, 'login'])->name('front.login.submit')->middleware('throttle:10,1');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('front.register');
    Route::post('/register', [AuthController::class, 'register'])->name('front.register.submit')->middleware('throttle:10,1');

    // Google Login
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');
});

// PROFILE ROUTES
Route::middleware($authNoStore)->group(function () {
    Route::get('/profile', [ProfileController::class, 'overview'])->name('profile');
    Route::get('/profile/show', [ProfileController::class, 'overview'])->name('profile.show');
    Route::get('/profile/overview', [ProfileController::class, 'overview'])->name('profile.overview');
    Route::get('/profile/compensation', [ProfileController::class, 'compensation'])->name('profile.compensation');
    Route::get('/profile/schedule', [ProfileController::class, 'schedule'])->name('profile.schedule');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::post('/profile/report', [ProfileController::class, 'generateReport'])->name('profile.report');
    Route::get('/profile/events', [ProfileController::class, 'getEvents'])->name('profile.events');
    Route::get('/profile/benefits', [ProfileController::class, 'getBenefits'])->name('profile.benefits');

    Route::prefix('profile/admin-tools')->middleware('super-admin')->group(function () {
        Route::get('/', [AdminToolsController::class, 'index'])->name('profile.admin-tools');
        Route::get('/users', [AdminToolsController::class, 'users'])->name('profile.admin-tools.users');
        Route::get('/roles', [AdminToolsController::class, 'roles'])->name('profile.admin-tools.roles');
        Route::get('/company', [AdminToolsController::class, 'company'])->name('profile.admin-tools.company');
        Route::get('/branding', [AdminToolsController::class, 'branding'])->name('profile.admin-tools.branding');
        Route::get('/sops', [AdminToolsController::class, 'sops'])->name('profile.admin-tools.sops');
        Route::get('/documentations', [AdminToolsController::class, 'documentations'])->name('profile.admin-tools.documentations');
        Route::get('/document-categories', [AdminToolsController::class, 'documentCategories'])->name('profile.admin-tools.document-categories');
        Route::get('/projects', [AdminToolsController::class, 'projects'])->name('profile.admin-tools.projects');
        Route::get('/projects/{order}', [AdminToolsController::class, 'project'])->name('profile.admin-tools.projects.show');
        Route::get('/projects/{order}/product', [AdminToolsController::class, 'projectProduct'])->name('profile.admin-tools.projects.product');
        Route::get('/nota-dinas', [AdminToolsController::class, 'notaDinas'])->name('profile.admin-tools.nota-dinas');
        Route::get('/nota-dinas/{notaDinas}', [AdminToolsController::class, 'notaDinasShow'])->name('profile.admin-tools.nota-dinas.show');
        Route::get('/bank-statements', [AdminToolsController::class, 'bankStatements'])->name('profile.admin-tools.bank-statements');
        Route::get('/bank-statements/guide', [AdminToolsController::class, 'bankStatementsGuide'])->name('profile.admin-tools.bank-statements.guide');
        Route::get('/bank-statements/failed', [AdminToolsController::class, 'bankStatementsFailed'])->name('profile.admin-tools.bank-statements.failed');
        Route::get('/bank-statements/reconciliation', [AdminToolsController::class, 'bankStatementsReconciliation'])->name('profile.admin-tools.bank-statements.reconciliation');
        Route::get('/bank-statements/{bankStatement}', [AdminToolsController::class, 'bankStatementShow'])->name('profile.admin-tools.bank-statements.show');
        Route::get('/nota-dinas-details/{notaDinasDetail}/invoice/view', [NotaDinasInvoiceFileController::class, 'view'])->name('profile.admin-tools.nota-dinas-details.invoice.view');
        Route::get('/help-center', [AdminToolsController::class, 'helpCenter'])->name('profile.admin-tools.help-center');
    });
    Route::get('/dashboard', function () {
        return redirect()->route('filament.admin.pages.dashboard');
    })->name('dashboard');

    // Logout
    Route::post('/logout', function () {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect('/');
    })->name('logout');
});

// Route untuk Prospect (Original)
Route::get('/prospect', [ProspectController::class, 'create'])
    ->name('prospect.form');

Route::post('/prospect', [ProspectController::class, 'store'])
    ->name('prospect.store')
    ->middleware('throttle:20,1');

Route::get('/prospect/success', [ProspectController::class, 'success'])
    ->name('prospect.success');

// Route untuk Prospect App Proposal PDF
Route::get('/prospect-app/{prospectApp}/proposal', [ProspectAppController::class, 'generateProposalPdf'])
    ->name('prospect-app.proposal.pdf')
    ->middleware($authNoStoreThrottle);

// Route untuk Prospect App (Frontend)
Route::get('/prospect-app', [ProspectAppController::class, 'create'])->name('prospect-app.form');
Route::post('/prospect-app', [ProspectAppController::class, 'store'])->name('prospect-app.store')->middleware('throttle:20,1');
Route::get('/prospect-app/success', [ProspectAppController::class, 'success'])->name('prospect-app.success');
Route::post('/prospect-app/check-email', [ProspectAppController::class, 'checkEmail'])->name('prospect-app.check-email')->middleware('throttle:10,1');

// Route untuk Download PDF Rekonsiliasi
Route::get('/admin/reconciliation/download-pdf', [ReconciliationController::class, 'downloadPdf'])
    ->name('reconciliation.download-pdf')
    ->middleware($authNoStoreThrottle);

if (app()->environment('local')) {
    Route::get('/debug-report', function () {
        $query = DataPembayaran::query()->with(['order', 'paymentMethod']);

        $rawCount = (clone $query)->count();

        $joinedQuery = $query
            ->join('orders', 'data_pembayarans.order_id', '=', 'orders.id')
            ->select('data_pembayarans.*');

        $joinedCount = (clone $joinedQuery)->count();
        $joinedSql = $joinedQuery->toSql();
        $data = $joinedQuery->limit(5)->get();

        return [
            'raw_count' => $rawCount,
            'joined_count' => $joinedCount,
            'sql' => $joinedSql,
            'sample_data' => $data,
        ];
    })->middleware([...$authNoStore, 'super-admin']);
}

// LAPORAN KEUANGAN PDF DOWNLOAD
Route::get('/laporan-keuangan/download-pdf', [LaporanKeuanganController::class, 'downloadPdf'])
    ->name('laporan-keuangan.download-pdf')
    ->middleware($authNoStoreThrottle);

Route::get('/laporan-keuangan/download-pdf-direct', [LaporanKeuanganController::class, 'downloadPdf'])
    ->name('laporan-keuangan.download-pdf-direct')
    ->middleware($authNoStoreThrottle);

// ACCOUNT MANAGER REPORT
Route::get('/account-manager/report/html', [AccountManagerReportController::class, 'downloadHtmlReport'])
    ->name('account-manager.report.html')
    ->middleware($authNoStore);

Route::get('/account-manager/report/pdf', [AccountManagerReportController::class, 'downloadPdfReport'])
    ->name('account-manager.report.pdf')
    ->middleware($authNoStoreThrottle);

Route::get('/account-manager/report/stream', [AccountManagerReportController::class, 'streamPdfReport'])
    ->name('account-manager.report.stream')
    ->middleware($authNoStoreThrottle);

Route::get('/account-manager/report/show', [AccountManagerReportController::class, 'showReport'])
    ->name('account-manager.report.show')
    ->middleware($authNoStore);
// NOTA DINAS ROUTES
Route::get('/nota-dinas/{notaDinas}/preview-web', [NotaDinasPdfController::class, 'previewWeb'])
    ->name('nota-dinas.preview-web')
    ->middleware($authNoStore);

Route::get('/nota-dinas/{notaDinas}/preview-pdf', [NotaDinasPdfController::class, 'previewPdf'])
    ->name('nota-dinas.preview-pdf')
    ->middleware($authNoStoreThrottle);

Route::get('/laporan/nota-dinas-details/bulan-ini', [ReportController::class, 'showNotaDinasDetailsCurrentMonth'])
    ->name('nota-dinas-details.current-month')
    ->middleware($authNoStore);

// BANK STATEMENT RECONCILIATION ROUTE
// Dihapus karena sudah menggunakan standard Filament Page di ViewReconciliation

// DOCUMENTATION (FRONTEND)
Route::get('/docs', [DocumentationController::class, 'index'])->name('docs.index');
Route::get('/docs/{slug}', [DocumentationController::class, 'index'])->name('docs.show');
