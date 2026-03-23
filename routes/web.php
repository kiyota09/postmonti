<?php

use App\Http\Controllers\Auth\ClientAuthController;
use App\Http\Controllers\Auth\SupplierAuthController;
use App\Http\Controllers\client\DashboardController as ClientDashboardController;
use App\Http\Controllers\client\InvoicesController;
use App\Http\Controllers\client\OrdersController;
use App\Http\Controllers\client\SupportController;
use App\Http\Controllers\crm\manager\CrmApprovalController;
use App\Http\Controllers\crm\manager\OversightController;
use App\Http\Controllers\crm\manager\StrategyController;
use App\Http\Controllers\crm\staff\CustomerprofileController;
use App\Http\Controllers\crm\staff\LeadController;
use App\Http\Controllers\crm\staff\StaffDayController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\eco\manager\BookController;
use App\Http\Controllers\eco\manager\ClientVerificationController;
use App\Http\Controllers\eco\manager\CreditController;
use App\Http\Controllers\eco\staff\CustomerController;
use App\Http\Controllers\eco\staff\OrdermngController;
use App\Http\Controllers\eco\staff\ProductsController;
use App\Http\Controllers\hrm\employee\AttendanceController;
use App\Http\Controllers\hrm\employee\HolidayController;
use App\Http\Controllers\hrm\employee\HrmstaffpayrollController;
use App\Http\Controllers\hrm\employee\InterviewController;
use App\Http\Controllers\hrm\employee\LeaveController;
use App\Http\Controllers\hrm\employee\TrainingController;
use App\Http\Controllers\hrm\manager\AnalyticsController;
use App\Http\Controllers\hrm\manager\ApplicantController;
use App\Http\Controllers\hrm\manager\ManagerEmployeeController;
use App\Http\Controllers\hrm\manager\OnboardingController;
use App\Http\Controllers\hrm\manager\PayrollController;
use App\Http\Controllers\inv\InventoryController as InvInventoryController;
use App\Http\Controllers\inv\MaterialController;
use App\Http\Controllers\inv\ProductController;
use App\Http\Controllers\pro\manager\ProcurementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\scm\employee\InboundController;
use App\Http\Controllers\scm\employee\InventoryController as ScmInventoryController;
use App\Http\Controllers\scm\employee\RecievingController;
use App\Http\Controllers\scm\employee\VerificationController;
use App\Http\Controllers\scm\manager\CloseController; // New SCM payment controller
use App\Http\Controllers\scm\manager\PaymentController; // New SCM forwarding controller
use App\Http\Controllers\scm\manager\ScmManagerController;
use App\Http\Controllers\scm\manager\VendorController; // New PRO procurement controller
use App\Http\Controllers\SUPPLIERS\SupplierDashboardController;
use App\Http\Controllers\trainee\TraineeAttendanceController;
use App\Http\Controllers\trainee\TraineePayslipController;
use App\Http\Controllers\trainee\TraineeTimeKeepingController;
use App\Http\Controllers\users\AppController;
use App\Http\Controllers\users\ClockController;
use App\Http\Controllers\users\leaveController as UserLeaveController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Core Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return inertia('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => app()->version(),
        'phpVersion' => PHP_VERSION,
    ]);
});

/*
|--------------------------------------------------------------------------
| Public Career Application Routes
|--------------------------------------------------------------------------
*/
Route::get('/apply', function () {
    return inertia('Auth/apply');
})->name('apply');

// Route handles the actual form submission from public users
Route::post('/apply/store', [ApplicantController::class, 'store'])->name('applicants.public.store');

/*
|--------------------------------------------------------------------------
| Authenticated User Core Routes
|--------------------------------------------------------------------------
|
| General routing that applies to any internal user who has successfully
| logged into the main portal. The DashboardController handles dispatching
| to the correct department view based on user role and position.
|
*/
Route::middleware(['auth'])->group(function () {
    // The main entry point that redirects based on user role/position
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // User Profile Management
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Unified Employee UI Routes (Standard Staff/Managers)
|--------------------------------------------------------------------------
|
| These routes power the generalized self-service portal for all standard
| employees, regardless of department, allowing them to manage their own
| attendance, leaves, and view payslips.
|
*/
Route::middleware(['auth', 'position:staff,manager'])->prefix('dashboard/employee-ui')->group(function () {

    // Main App Dashboard for Self-Service
    Route::get('/', [AppController::class, 'index'])->name('employee.ui.dashboard');

    // Time & Attendance Module
    Route::get('/clock', [ClockController::class, 'clock'])->name('employee.ui.clock');
    Route::post('/clock/toggle', [ClockController::class, 'toggle'])->name('employee.attendance.toggle');

    // Leave Management Module
    Route::get('/leave', [UserLeaveController::class, 'leave'])->name('employee.ui.leave');
    Route::post('/leave', [UserLeaveController::class, 'store'])->name('employee.leave.store');

    // Payroll & Compensation Module
    Route::get('/payslip', function () {
        return inertia('Dashboard/USERS/payslip');
    })->name('employee.ui.payslip');

});

/*
|--------------------------------------------------------------------------
| Unified Trainee Portal Routes
|--------------------------------------------------------------------------
|
| Isolated routing environment specifically for trainees. Restricts their
| access away from standard departmental operations until they are fully
| evaluated, promoted, and re-assigned by the HRM Manager.
|
*/
Route::prefix('dashboard/trainee')->middleware(['auth', 'verified', 'position:trainee'])->group(function () {

    // Unified dashboard for all trainees regardless of intended department
    Route::get('/', [DashboardController::class, 'index'])->name('trainee.dashboard');

    // Time Keeping / Clock In/Out
    Route::get('/timekeeping', [TraineeTimeKeepingController::class, 'index'])->name('trainee.timekeeping');
    Route::post('/timekeeping/clock', [TraineeTimeKeepingController::class, 'clockInOut'])->name('trainee.timekeeping.clock');

    // Attendance Records viewing
    Route::get('/attendance', [TraineeAttendanceController::class, 'index'])->name('trainee.attendance');

    // Payslips viewing for Training Allowance
    Route::get('/payslip', [TraineePayslipController::class, 'index'])->name('trainee.payslip');
    Route::get('/payslip/{payroll}', [TraineePayslipController::class, 'show'])->name('trainee.payslip.show');
});

/*
|--------------------------------------------------------------------------
| Human Resources Management (HRM) Routes
|--------------------------------------------------------------------------
|
| Extensive routing for HR operations. Includes granular control over
| recruitment, attendance oversight, payroll generation, performance grading,
| and managerial account suspensions.
|
*/
Route::prefix('dashboard/hrm')->name('hrm.')->middleware(['auth', 'verified'])->group(function () {

    // ── HRM Staff Specific Routes ───────────────────────────────────────────

    // Core employee directory view
    Route::get('/employee', [DashboardController::class, 'index'])
        ->middleware(['role:HRM', 'position:staff'])
        ->name('employee.dashboard');

    // Basic Employee CRUD updates managed by staff
    Route::post('/employee/{id}/update', [DashboardController::class, 'updateEmployee'])->name('employees.update');
    Route::delete('/employee/{id}', [DashboardController::class, 'destroy'])->name('employees.destroy');

    // Training & Evaluation Module
    Route::get('/training', [TrainingController::class, 'training'])
        ->middleware(['role:HRM', 'position:staff'])
        ->name('employee.training');

    Route::post('/training/grade/{id}', [TrainingController::class, 'submitGrade'])
        ->middleware(['role:HRM', 'position:staff'])
        ->name('training.grade');

    Route::post('/training/suggest-promotion/{id}', [TrainingController::class, 'suggestPromotion'])
        ->middleware(['role:HRM', 'position:staff'])
        ->name('training.suggest-promotion');

    // Attendance & Leave Oversight Module
    Route::get('/leave', [LeaveController::class, 'leave'])
        ->middleware(['role:HRM', 'position:staff,manager'])
        ->name('employee.leave');

    Route::patch('/leave/{leaveRequest}', [LeaveController::class, 'update'])->name('employee.leave.update');
    Route::post('/leave/manual', [LeaveController::class, 'store_manual'])->name('employee.leave.store_manual');

    Route::get('/attendance', [AttendanceController::class, 'attendance'])
        ->middleware(['role:HRM', 'position:staff,manager'])
        ->name('employee.attendance');

    Route::post('/attendance/toggle', [AttendanceController::class, 'toggle'])
        ->name('employee.attendance.toggle');

    Route::delete('/attendance/shift', [AttendanceController::class, 'destroyShift'])
        ->name('employee.attendance.destroy-shift');

    Route::post('/attendance/shift/remove-monthly', [AttendanceController::class, 'destroyMonthlyShift'])
        ->name('employee.attendance.destroy-monthly-shift');

    Route::post('/attendance/approve-shift', [AttendanceController::class, 'approveShift'])->name('employee.attendance.approve-shift');
    Route::post('/attendance/reject-shift', [AttendanceController::class, 'rejectShift'])->name('employee.attendance.reject-shift');

    Route::post('/attendance/approve-holiday', [AttendanceController::class, 'approveHoliday'])->name('employee.attendance.approve-holiday');
    Route::post('/attendance/reject-holiday', [AttendanceController::class, 'rejectHoliday'])->name('employee.attendance.reject-holiday');
    Route::post('/attendance/update-shift', [AttendanceController::class, 'updateShift'])->name('employee.attendance.update-shift');

    // Company Holidays Management
    Route::prefix('holidays')->name('employee.holidays.')->group(function () {
        Route::post('/', [HolidayController::class, 'store'])->name('store');
        Route::patch('/{holiday}', [HolidayController::class, 'update'])->name('update');
        Route::delete('/{holiday}', [HolidayController::class, 'destroy'])->name('destroy');
    });

    // Recruitment & Interview Module
    Route::get('/interview', [InterviewController::class, 'interview'])
        ->middleware(['role:HRM', 'position:staff'])
        ->name('employee.interview');

    Route::post('/InterviewController/update-status', [InterviewController::class, 'submitStatus'])
        ->middleware(['role:HRM', 'position:staff']);

    Route::post('/interview/{interview}/evaluate', [InterviewController::class, 'updateStatus'])
        ->middleware(['role:HRM', 'position:staff'])
        ->name('employee.interview.evaluate');

    Route::post('/interview/{interview}/reschedule', [InterviewController::class, 'reschedule'])
        ->middleware(['role:HRM', 'position:staff'])
        ->name('employee.interview.reschedule');

    // Payroll Staff Entry Generation
    Route::get('/hrmstaffpayroll', [HrmstaffpayrollController::class, 'hrmstaffpayroll'])
        ->middleware(['role:HRM', 'position:staff'])
        ->name('employee.hrmstaffpayroll');

    Route::post('/payroll/store', [HrmstaffpayrollController::class, 'store'])
        ->name('hrm.employee.payroll.store');

    Route::patch('/payroll/{id}/approve', [HrmstaffpayrollController::class, 'approve'])
        ->name('employee.payroll.approve');

    Route::patch('/payroll/{id}/reject', [HrmstaffpayrollController::class, 'reject'])
        ->name('employee.payroll.reject');

    Route::post('/holidays/store', [HrmstaffpayrollController::class, 'storeHoliday'])
        ->name('hrm.holidays.store');

    // ── HRM Manager Specific Routes ─────────────────────────────────────────

    Route::get('/manager', [DashboardController::class, 'index'])
        ->middleware(['role:HRM', 'position:manager'])
        ->name('manager.dashboard');

    // Promotion & Grading Confirmations
    Route::post('/manager/finalize-promotion/{id}', [DashboardController::class, 'finalizePromotion'])
        ->middleware(['role:HRM', 'position:manager'])
        ->name('manager.finalize-promotion');

    Route::post('/manager/grade-trainee/{id}', [DashboardController::class, 'gradeAndPromote'])
        ->middleware(['role:HRM', 'position:manager'])
        ->name('manager.grade-trainee');

    // Global Account Management & Suspension
    Route::post('/employees/{id}/toggle-status', [ManagerEmployeeController::class, 'toggleStatus'])
        ->middleware(['role:HRM', 'position:manager'])
        ->name('employees.toggle-status');

    Route::get('/payroll', [PayrollController::class, 'payroll'])
        ->middleware(['role:HRM', 'position:manager'])
        ->name('manager.payroll');

    Route::get('/analytics', [AnalyticsController::class, 'analytics'])
        ->middleware(['role:HRM', 'position:manager'])
        ->name('manager.analytics');

    // Shared Manager/Staff Applicant Module
    Route::controller(ApplicantController::class)->group(function () {
        Route::get('/applicants', 'index')
            ->middleware(['role:HRM', 'position:manager,staff'])
            ->name('applicants.index');

        Route::post('/applicants', 'store')
            ->middleware(['role:HRM', 'position:manager,staff'])
            ->name('applicants.store');

        Route::post('/applicants/{applicant}/schedule', 'scheduleInterview')
            ->middleware(['role:HRM', 'position:manager,staff'])
            ->name('applicants.schedule');

        Route::patch('/applicants/{applicant}/update-stage', 'updateStage')
            ->middleware(['role:HRM', 'position:manager,staff'])
            ->name('applicants.update-stage');

        Route::post('/applicants/{applicant}/create-user', 'createUser')
            ->middleware(['role:HRM', 'position:manager,staff'])
            ->name('applicants.create-user');
    });

    // Onboarding Process Tracking
    Route::controller(OnboardingController::class)->group(function () {
        Route::get('/onboarding', 'onboarding')
            ->middleware(['role:HRM', 'position:manager'])
            ->name('manager.onboarding');
    });
});

/*
|--------------------------------------------------------------------------
| Supply Chain Management (SCM) Routes
|--------------------------------------------------------------------------
|
| Routing for procurement lifecycles, vendor evaluations, inbound logistics,
| and purchase order (PO) generation. Connects internally to INV and ECO.
|
*/
Route::prefix('dashboard/scm')->name('scm.')->middleware(['auth', 'verified'])->group(function () {

    // ── SCM Manager Routes ──────────────────────────────────────────────────
    Route::middleware(['role:SCM', 'position:manager'])->group(function () {

        Route::get('/manager', [DashboardController::class, 'index'])->name('manager.dashboard');

        // Forward material requests to PRO
        Route::post('/material-requests/{id}/forward', [ScmManagerController::class, 'forwardMaterialRequest'])
            ->name('manager.material-request.forward');

        // Payment approval
        Route::get('/manager/payments', [PaymentController::class, 'index'])->name('manager.payments');
        Route::post('/payments', [PaymentController::class, 'processPayment'])->name('manager.payments.process');

        // Vendor & Supplier Tracking
        Route::prefix('vendor')->group(function () {
            Route::get('/', [VendorController::class, 'vendor'])->name('manager.vendor');
            Route::post('/register', [VendorController::class, 'register'])->name('manager.vendor.register');
            Route::get('/registrations', [VendorController::class, 'getRegistrations'])->name('manager.vendor.registrations');
            Route::get('/registrations/{id}', [VendorController::class, 'getRegistration'])->name('manager.vendor.registration.show');
            Route::post('/registrations/{id}/approve', [VendorController::class, 'approve'])->name('manager.vendor.approve');
            Route::post('/registrations/{id}/reject', [VendorController::class, 'reject'])->name('manager.vendor.reject');
            Route::post('/registrations/{id}/requirements', [VendorController::class, 'setRequirements'])->name('manager.vendor.requirements.store');
            Route::get('/registrations/{id}/requirements', [VendorController::class, 'getRequirements'])->name('manager.vendor.requirements.show');
            Route::get('/my-registration', [VendorController::class, 'getMyRegistration'])->name('manager.vendor.my-registration');
        });

        Route::get('/close', [CloseController::class, 'close'])->name('manager.close');

        // Note: Old procurement routes removed – now handled by PRO module
    });

    // ── SCM Staff Routes ────────────────────────────────────────────────────
    Route::middleware(['role:SCM', 'position:staff'])->group(function () {
        Route::get('/staff', [DashboardController::class, 'index'])->name('employee.dashboard');
        Route::get('/inbound', [InboundController::class, 'inbound'])->name('employee.inbound');
        Route::get('/inventory', [ScmInventoryController::class, 'inventory'])->name('employee.inventory');
        Route::get('/recieving', [RecievingController::class, 'recieving'])->name('employee.recieving');
        Route::get('/verification', [VerificationController::class, 'verification'])->name('employee.verification');
    });
});

/*
|--------------------------------------------------------------------------
| Financial Operations (FIN) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/fin')->name('fin.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/manager', [DashboardController::class, 'index'])
        ->middleware(['role:FIN', 'position:manager'])
        ->name('manager.dashboard');

    Route::get('/staff', [DashboardController::class, 'index'])
        ->middleware(['role:FIN', 'position:staff'])
        ->name('employee.dashboard');
});

/*
|--------------------------------------------------------------------------
| Manufacturing Plant (MAN) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/man')->name('man.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/manager', [DashboardController::class, 'index'])
        ->middleware(['role:MAN', 'position:manager'])
        ->name('manager.dashboard');

    Route::get('/staff', [DashboardController::class, 'index'])
        ->middleware(['role:MAN', 'position:staff'])
        ->name('employee.dashboard');
});

/*
|--------------------------------------------------------------------------
| Inventory & Warehousing (INV) Routes
|--------------------------------------------------------------------------
|
| Handles multi-warehouse master materials, receiving processes from SCM,
| and manufactured product master lists.
|
*/
Route::prefix('dashboard/inv')->name('inv.')->middleware(['auth', 'verified'])->group(function () {

    Route::get('/manager', [DashboardController::class, 'index'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.dashboard');

    // ── Warehouse Operations ────────────────────────────────────────────────
    Route::get('/inventory', [InvInventoryController::class, 'inventory'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.inventory');

    Route::post('/inventory/receive', [InvInventoryController::class, 'receiveDelivery'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.inventory.receive');

    Route::post('/warehouse', [InvInventoryController::class, 'storeWarehouse'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.warehouse.store');

    Route::post('/inventory/item', [InvInventoryController::class, 'storeItem'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.inventory.item.store');

    Route::delete('/inventory/item/{wmId}', [InvInventoryController::class, 'destroyItem'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.inventory.item.destroy');

    // ── Master Material Database ────────────────────────────────────────────
    Route::post('/material/procurement', [MaterialController::class, 'requestProcurement'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.material.procurement');

    Route::get('/material', [MaterialController::class, 'material'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.material');

    Route::post('/material', [MaterialController::class, 'store'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.material.store');

    Route::delete('/material/{id}', [MaterialController::class, 'destroy'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.material.destroy');

    Route::post('/material/delegate', [MaterialController::class, 'delegate'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.material.delegate');

    // ── Master Products Database ────────────────────────────────────────────
    Route::get('/product', [ProductController::class, 'product'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.product');

    Route::post('/product', [ProductController::class, 'store'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.product.store');

    Route::post('/product/{id}/update', [ProductController::class, 'update'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.product.update');

    Route::delete('/product/image/{imageId}', [ProductController::class, 'destroyImage'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.product.image.destroy');

    Route::delete('/product/{id}', [ProductController::class, 'destroy'])
        ->middleware(['role:INV', 'position:manager'])
        ->name('manager.product.destroy');

    Route::get('/staff', [DashboardController::class, 'index'])
        ->middleware(['role:INV', 'position:staff'])
        ->name('employee.dashboard');
});

/*
|--------------------------------------------------------------------------
| Order Processing (ORD) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/ord')->name('ord.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/manager', [DashboardController::class, 'index'])
        ->middleware(['role:ORD', 'position:manager'])
        ->name('manager.dashboard');

    Route::get('/staff', [DashboardController::class, 'index'])
        ->middleware(['role:ORD', 'position:staff'])
        ->name('employee.dashboard');
});

/*
|--------------------------------------------------------------------------
| Warehouse Dispatch (WAR) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/war')->name('war.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/manager', [DashboardController::class, 'index'])
        ->middleware(['role:WAR', 'position:manager'])
        ->name('manager.dashboard');

    Route::get('/staff', [DashboardController::class, 'index'])
        ->middleware(['role:WAR', 'position:staff'])
        ->name('employee.dashboard');
});

/*
|--------------------------------------------------------------------------
| Customer Relationship Management (CRM) Routes
|--------------------------------------------------------------------------
|
| Handles B2B prospect tracking, lead conversion into the ECO module,
| interaction logging, and multi-tier managerial approvals.
|
*/
Route::prefix('dashboard/crm')->name('crm.')->middleware(['auth', 'verified'])->group(function () {

    // Unified dashboard resolution point
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // CRM Lead Pipeline Operations
    Route::get('/lead', [LeadController::class, 'lead'])->name('lead');
    Route::post('/lead/store', [LeadController::class, 'store'])->name('lead.store');
    Route::patch('/lead/{id}/status', [LeadController::class, 'updateStatus'])->name('lead.status');
    Route::post('/lead/convert', [LeadController::class, 'convertToClient'])->name('lead.convert');
    Route::post('/lead/{id}/note', [LeadController::class, 'addNote'])->name('lead.add-note');
    Route::post('/lead/{id}/interview', [LeadController::class, 'scheduleInterview'])->name('lead.schedule-interview');
    Route::post('/lead/{id}/file', [LeadController::class, 'uploadApprovalFile'])->name('lead.upload-file');
    Route::post('/lead/{id}/accept', [LeadController::class, 'acceptLead'])->name('lead.accept');
    Route::post('/lead/{id}/reject', [LeadController::class, 'rejectLead'])->name('lead.reject');

    // Shared Customer Profiling & Activity Logs
    Route::get('/customerprofile/{id?}', [CustomerprofileController::class, 'customerprofile'])->name('customerprofile');
    Route::post('/interaction/store', [CustomerprofileController::class, 'storeInteraction'])->name('interaction.store');

    // Managerial Oversight & Approval Queues
    Route::get('/approvals', [CrmApprovalController::class, 'index'])
        ->middleware(['role:CRM', 'position:manager'])
        ->name('approval.queue');

    Route::post('/approvals/{id}/process', [CrmApprovalController::class, 'process'])
        ->middleware(['role:CRM', 'position:manager'])
        ->name('approval.process');

    Route::get('/oversight', [OversightController::class, 'oversight'])
        ->middleware(['role:CRM', 'position:manager'])
        ->name('oversight');

    Route::get('/strategy', [StrategyController::class, 'strategy'])
        ->middleware(['role:CRM', 'position:manager'])
        ->name('strategy');

    // Staff Level Task Views
    Route::get('/my-day', [StaffDayController::class, 'index'])
        ->middleware(['role:CRM', 'position:staff'])
        ->name('staff.day');
});

/*
|--------------------------------------------------------------------------
| E-Commerce & Account Management (ECO) Routes
|--------------------------------------------------------------------------
|
| Handles approved B2B Client profiles, their credit limits, pricing tiers
| (Book), and managing the online product catalog derived from INV.
|
*/
Route::prefix('dashboard/eco')->name('eco.')->middleware(['auth', 'verified'])->group(function () {

    // ── ECO Manager Routes ──────────────────────────────────────────────────
    Route::middleware(['role:ECO', 'position:manager'])->prefix('manager')->name('manager.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Pricing Book Tiering
        Route::get('/book', [BookController::class, 'book'])->name('book');
        Route::post('/book/store', [BookController::class, 'store'])->name('book.store');
        Route::patch('/book/{tier}/update', [BookController::class, 'update'])->name('book.update');
        Route::post('/book/apply-tier/{po}', [BookController::class, 'applyTier'])->name('book.apply-tier');

        // Credit Limit Assessment
        Route::get('/credit', [CreditController::class, 'credit'])->name('credit');
        Route::post('/credit', [CreditController::class, 'store'])->name('credit.store');
        Route::post('/credit/verify/{po}', [CreditController::class, 'verifyOrder'])->name('credit.verify');
        Route::patch('/credit/{account}/toggle', [CreditController::class, 'toggleStatus'])->name('credit.toggle-status');
        Route::delete('/credit/{account}', [CreditController::class, 'destroy'])->name('credit.destroy');

        // B2B Client Verification Setup
        Route::get('/verification', [ClientVerificationController::class, 'index'])->name('verification.index');
        Route::patch('/clients/{client}/status', [ClientVerificationController::class, 'updateStatus'])->name('clients.status.update');
    });

    // ── ECO Staff Routes ────────────────────────────────────────────────────
    Route::middleware(['role:ECO', 'position:staff'])->group(function () {
        Route::get('/staff', [DashboardController::class, 'index'])->name('employee.dashboard');

        // Product Storefront Maintenance
        Route::get('/products', [ProductsController::class, 'products'])->name('employee.products');
        Route::post('/products', [ProductsController::class, 'store'])->name('employee.products.store');
        Route::post('/products/{product}/update', [ProductsController::class, 'update'])->name('employee.products.update');
        Route::patch('/products/{product}/toggle', [ProductsController::class, 'toggleStatus'])->name('employee.products.toggle');

        // Order Verification
        Route::get('/ordermng', [OrdermngController::class, 'ordermng'])->name('employee.ordermng');
        Route::get('/customer', [CustomerController::class, 'customer'])->name('employee.customer');
    });
});

/*
|--------------------------------------------------------------------------
| Procurement Sub-System (PRO) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/pro')->name('pro.')->middleware(['auth', 'verified'])->group(function () {
    // Manager routes
    Route::middleware(['role:PRO', 'position:manager'])->prefix('manager')->name('manager.')->group(function () {
        // Main dashboard redirects to material-requests (handled by DashboardController)
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Material Requests (forwarded from SCM)
        Route::get('/material-requests', [ProcurementController::class, 'materialRequests'])->name('material-requests');
        Route::post('/material-requests/rfq', [ProcurementController::class, 'createRFQ'])->name('rfq.store');

        // Supplier Quotations
        Route::get('/supplier-quotations', [ProcurementController::class, 'supplierQuotations'])->name('supplier-quotations');
        Route::post('/quotations/{responseId}/accept', [ProcurementController::class, 'acceptQuotation'])->name('quotations.accept');
        Route::post('/quotations/{responseId}/decline', [ProcurementController::class, 'declineQuotation'])->name('quotations.decline');

        // Receipt (POs & Invoices)
        Route::get('/receipt', [ProcurementController::class, 'receipt'])->name('receipt');
        Route::post('/purchase-orders/{poId}/send', [ProcurementController::class, 'sendPurchaseOrder'])->name('purchase-orders.send');
    });

    // Staff routes (fallback)
    Route::middleware(['role:PRO', 'position:staff'])->group(function () {
        Route::get('/staff', [DashboardController::class, 'index'])->name('employee.dashboard');
    });
});

/*
|--------------------------------------------------------------------------
| Project Automation (PROJ) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/proj')->name('proj.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/manager', [DashboardController::class, 'index'])
        ->middleware(['role:PROJ', 'position:manager'])
        ->name('manager.dashboard');

    Route::get('/staff', [DashboardController::class, 'index'])
        ->middleware(['role:PROJ', 'position:staff'])
        ->name('employee.dashboard');
});

/*
|--------------------------------------------------------------------------
| IT & Systems Admin (IT) Routes
|--------------------------------------------------------------------------
*/
Route::prefix('dashboard/it')->name('it.')->middleware(['auth', 'verified'])->group(function () {
    Route::get('/manager', [DashboardController::class, 'index'])
        ->middleware(['role:IT', 'position:manager'])
        ->name('manager.dashboard');

    Route::get('/staff', [DashboardController::class, 'index'])
        ->middleware(['role:IT', 'position:staff'])
        ->name('employee.dashboard');
});

/*
|--------------------------------------------------------------------------
| B2B Client Gateway Authentication Routes
|--------------------------------------------------------------------------
|
| Public registration and login endpoints specifically for B2B Client Partners.
| These use the 'client' auth guard instead of the standard 'web' user guard.
|
*/
Route::middleware('guest:client')->group(function () {
    Route::get('client/register', [ClientAuthController::class, 'create'])->name('client.register');
    Route::post('client/register', [ClientAuthController::class, 'store'])->name('client.register.store');
    Route::get('client/login', [ClientAuthController::class, 'showLogin'])->name('client.login');
    Route::post('client/login', [ClientAuthController::class, 'login'])->name('client.login.store');
});

// Client Logout (Must be inside auth:client)
Route::post('client/logout', [ClientAuthController::class, 'logout'])
    ->middleware('auth:client')
    ->name('client.logout');

/*
|--------------------------------------------------------------------------
| Protected Client B2B Portal Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth:client')->prefix('partner')->name('client.')->group(function () {
    Route::get('/dashboard', [ClientDashboardController::class, 'index'])->name('dashboard');
    Route::get('/orders', [OrdersController::class, 'orders'])->name('orders');
    Route::post('/purchase-order', [ClientDashboardController::class, 'placeOrder'])->name('purchase-order.store');
    Route::post('/quotation/{po}/accept', [ClientDashboardController::class, 'acceptQuotation'])->name('quotation.accept');
    Route::get('/invoices', [InvoicesController::class, 'invoices'])->name('invoices');
    Route::get('/support', [SupportController::class, 'support'])->name('support');
});

/*
|--------------------------------------------------------------------------
| Vendor/Supplier Gateway Authentication Routes
|--------------------------------------------------------------------------
|
| Public registration and login endpoints for Supply Chain Vendors.
| These use the 'supplier' auth guard.
|
*/
Route::middleware('guest:supplier')->group(function () {
    Route::get('supplier/login', [SupplierAuthController::class, 'showLogin'])->name('supplier.login');
    Route::post('supplier/login', [SupplierAuthController::class, 'login'])->name('supplier.login.store');
    Route::get('supplier/register', [SupplierAuthController::class, 'create'])->name('supplier.register');
    Route::post('supplier/register', [SupplierAuthController::class, 'store'])->name('supplier.register.store');
});

// Supplier Logout MUST live outside the guest:supplier block above to work properly
Route::post('supplier/logout', [SupplierAuthController::class, 'logout'])
    ->middleware('auth:supplier')
    ->name('supplier.logout');

/*
|--------------------------------------------------------------------------
| Protected Supplier Portal Routes
|--------------------------------------------------------------------------
|
| Endpoints where vendors can view Request For Quotations (RFQs) from SCM,
| submit bids, manage purchase orders, and upload invoices.
|
*/
Route::middleware('auth:supplier')->prefix('supplier')->name('supplier.')->group(function () {
    Route::get('/dashboard', [SupplierDashboardController::class, 'index'])->name('dashboard');
    Route::post('/rfq/{id}/respond', [SupplierDashboardController::class, 'submitQuotation'])->name('rfq.respond');
    Route::get('/orders', [SupplierDashboardController::class, 'purchaseOrders'])->name('orders');
    Route::post('/orders/{id}/status', [SupplierDashboardController::class, 'updateOrderStatus'])->name('orders.update_status');
    Route::post('/orders/{id}/invoice', [SupplierDashboardController::class, 'createInvoice'])->name('orders.invoice');
});

/*
|--------------------------------------------------------------------------
| Framework Generated Auth Routes (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';
