<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Client;
use App\Models\CrmInteraction;
use App\Models\CrmLead;
use App\Models\INV\Material;
use App\Models\INV\Product as InvProduct;
use App\Models\INV\Warehouse;
use App\Models\INV\WarehouseMaterial;
use App\Models\PurchaseOrder;
use App\Models\SCM\MaterialRequest;
use App\Models\SCM\PurchaseInvoice;
use App\Models\TraineeGrade;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Main Entry Point for the Dashboard.
     * Routes users to their specific departmental dashboard based on Role & Position.
     *
     * @return \Inertia\Response
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = strtoupper($user->role);
        $position = strtolower($user->position);

        // Audit Logging for Security and Debugging
        Log::info('Dashboard access initialized', [
            'user_id' => $user->id,
            'role' => $role,
            'position' => $position,
            'route' => $request->fullUrl(),
            'ip_address' => $request->ip(),
        ]);

        /**
         * 1. Priority Override: Trainee Check
         * All trainees share a unified dashboard interface regardless of department.
         */
        if ($position === 'trainee') {
            return Inertia::render('Dashboard/TRAINEE/Index', [
                'user' => $user,
                'stats' => [
                    'progress' => 45,
                    'assigned_modules' => 5,
                    'days_remaining' => 12,
                ],
            ]);
        }

        /**
         * 2. Departmental Routing
         * Matches the user's role to the specific department handler.
         */
        return match ($role) {
            'HRM' => $this->handleHrmDashboard($position),
            'SCM' => $this->handleScmDashboard($position),
            'FIN' => $this->handleFinDashboard($position),
            'MAN' => $this->handleManDashboard($position),
            'INV' => $this->handleInvDashboard($position),
            'ORD' => $this->handleOrdDashboard($position),
            'WAR' => $this->handleWarDashboard($position),
            'CRM' => $this->handleCrmDashboard($position),
            'ECO' => $this->handleEcoDashboard($position),

            // New Modules Integration
            'PRO' => $this->handleProDashboard($position),
            'PROJ' => $this->handleProjDashboard($position),
            'IT' => $this->handleItDashboard($position),

            // Fallback for unidentified roles
            default => $this->renderDefaultDashboard($user),
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Human Resources Management (HRM) Handler
    |--------------------------------------------------------------------------
    */
    private function handleHrmDashboard(string $position)
    {
        if ($position === 'manager') {

            $suggestedTrainees = User::where('position', 'trainee')
                ->with('traineeGrade')
                ->orderByRaw('promotion_suggested DESC')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'department' => $user->department,
                        'employee_id' => $user->employee_id,
                        'suggested_at' => $user->suggested_at,
                        'promotion_suggested' => (bool) $user->promotion_suggested,
                        'profile_photo_url' => $user->profile_photo_path
                            ? asset('storage/'.$user->profile_photo_path)
                            : null,
                        'trainee_grade' => $user->traineeGrade ? [
                            'id' => $user->traineeGrade->id,
                            'skills_performance' => $user->traineeGrade->skills_performance,
                            'behaviour' => $user->traineeGrade->behaviour,
                            'technicals' => $user->traineeGrade->technicals,
                            'safety_awareness' => $user->traineeGrade->safety_awareness,
                            'productivity' => $user->traineeGrade->productivity,
                            'total_percentage' => $user->traineeGrade->total_percentage,
                        ] : null,
                    ];
                });

            $allEmployees = User::with(['auditLogs' => function ($query) {
                $query->orderBy('created_at', 'desc');
            }])
                ->whereIn('position', ['manager', 'staff'])
                ->orderBy('role')
                ->orderBy('position')
                ->orderBy('name')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'position' => $user->position,
                        'department' => $user->department,
                        'employee_id' => $user->employee_id,
                        'is_active' => (bool) $user->is_active,
                        'status' => $user->status ?? ($user->is_active ? 'Active' : 'Inactive'),
                        'join_date' => $user->join_date,
                        'audit_logs' => $user->auditLogs,
                        'profile_photo_url' => $user->profile_photo_path
                            ? asset('storage/'.$user->profile_photo_path)
                            : null,
                    ];
                });

            return Inertia::render('Dashboard/HRM/Manager/Index', [
                'suggestedTrainees' => $suggestedTrainees,
                'allEmployees' => $allEmployees,
                'stats' => [
                    'totalEmployees' => User::whereIn('position', ['manager', 'staff'])->count(),
                    'activeRecruitment' => 12,
                    'pendingLeaves' => 8,
                    'attendanceRate' => 95,
                    'totalTrainees' => User::where('position', 'trainee')->count(),
                ],
            ]);
        }

        $employees = User::all()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'position' => $user->position,
                'is_active' => $user->is_active,
                'department' => $user->department,
                'employee_id' => $user->employee_id,
                'profile_photo_path' => $user->profile_photo_path,
                'profile_photo_url' => $user->profile_photo_path
                    ? asset('storage/'.$user->profile_photo_path)
                    : null,
            ];
        });

        $auditLogs = AuditLog::orderBy('created_at', 'desc')->take(50)->get()->map(function ($log) {
            return [
                'id' => $log->id,
                'target_name' => $log->target_name,
                'action' => $log->action,
                'reason' => $log->reason,
                'created_at' => $log->created_at->format('M d, Y h:i A'),
            ];
        });

        return Inertia::render('Dashboard/HRM/Employee/Index', [
            'employees' => $employees,
            'auditLogs' => $auditLogs,
            'stats' => [
                'total' => User::count(),
                'present' => User::where('is_active', true)->count(),
                'on_leave' => 0,
                'assignedTasks' => 4,
                'leaveBalance' => 15,
                'trainingModules' => 2,
            ],
            'user' => Auth::user(),
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if (Auth::id() == $user->id) {
            return redirect()->back()->with('message', 'System Error: You cannot modify your own account status.');
        }

        $newStatus = $user->is_active ? 0 : 1;
        $action = $newStatus ? 'reactivate' : 'deactivate';

        $user->update([
            'is_active' => $newStatus,
            'status' => $newStatus ? 'Active' : 'Inactive',
        ]);

        AuditLog::create([
            'admin_id' => Auth::id(),
            'target_id' => $user->id,
            'target_name' => $user->name,
            'action' => $action,
            'reason' => $request->reason ?? ($newStatus ? 'Account Reactivation' : 'No reason provided'),
        ]);

        $statusText = $newStatus ? 'reactivated' : 'deactivated';

        return redirect()->back()->with('message', "Employee {$user->name} has been successfully {$statusText}.");
    }

    public function updateEmployee(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id,
            'role' => 'required|in:HRM,SCM,FIN,MAN,INV,ORD,WAR,CRM,ECO,PRO,PROJ,IT',
            'position' => 'required|in:manager,staff,trainee',
            'department' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $employee = User::findOrFail($id);

        $employee->update([
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'position' => $request->position,
            'department' => $request->department,
            'is_active' => $request->is_active,
            'status' => $request->is_active ? 'Active' : 'Inactive',
        ]);

        return redirect()->back()->with('message', "Information for {$employee->name} updated successfully.");
    }

    public function finalizePromotion($id)
    {
        $trainee = User::findOrFail($id);

        $trainee->update([
            'position' => 'staff',
            'promotion_suggested' => false,
            'suggested_at' => null,
        ]);

        AuditLog::create([
            'admin_id' => Auth::id(),
            'target_id' => $trainee->id,
            'target_name' => $trainee->name,
            'action' => 'promote',
            'reason' => 'Manager formally approved promotion from Trainee to Staff status.',
        ]);

        return redirect()->back()->with('message', "{$trainee->name} has been successfully promoted to Staff.");
    }

    public function gradeAndPromote(Request $request, $id)
    {
        $validated = $request->validate([
            'skills_performance' => 'required|integer|min:1|max:5',
            'behaviour' => 'required|integer|min:1|max:5',
            'technicals' => 'required|integer|min:1|max:5',
            'safety_awareness' => 'required|integer|min:1|max:5',
            'productivity' => 'required|integer|min:1|max:5',
        ]);

        $trainee = User::findOrFail($id);

        $totalStars = (int) $validated['skills_performance']
            + (int) $validated['behaviour']
            + (int) $validated['technicals']
            + (int) $validated['safety_awareness']
            + (int) $validated['productivity'];

        $percentage = (int) round(($totalStars / 25) * 100);

        try {
            DB::transaction(function () use ($trainee, $validated, $percentage) {

                $grade = TraineeGrade::firstOrNew(['user_id' => $trainee->id]);
                $grade->skills_performance = (int) $validated['skills_performance'];
                $grade->behaviour = (int) $validated['behaviour'];
                $grade->technicals = (int) $validated['technicals'];
                $grade->safety_awareness = (int) $validated['safety_awareness'];
                $grade->productivity = (int) $validated['productivity'];
                $grade->total_percentage = $percentage;
                $grade->save();

                if ($percentage >= 80) {
                    $trainee->position = 'staff';
                    $trainee->promotion_suggested = false;
                    $trainee->suggested_at = null;
                    $trainee->save();
                }
            });

            AuditLog::create([
                'admin_id' => Auth::id(),
                'target_id' => $trainee->id,
                'target_name' => $trainee->name,
                'action' => $percentage >= 80 ? 'promote' : 'grade',
                'reason' => $percentage >= 80
                    ? "Manager graded and auto-promoted trainee. Final score: {$percentage}%."
                    : "Manager submitted grade evaluation. Score: {$percentage}%. (Below 80% promotion threshold).",
            ]);

        } catch (\Throwable $e) {
            Log::error('Grading Transaction Failed: '.$e->getMessage());

            return redirect()->back()->withErrors([
                'grade' => 'A database error prevented the grades from saving: '.$e->getMessage(),
            ]);
        }

        if ($percentage >= 80) {
            return redirect()->back()->with(
                'message',
                "Success: {$trainee->name} scored {$percentage}% and has been automatically promoted to Staff."
            );
        }

        return redirect()->back()->with(
            'message',
            "Evaluation Saved: {$trainee->name} scored {$percentage}%. The trainee requires 80% to be promoted."
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Supply Chain Management (SCM) Handler
    |--------------------------------------------------------------------------
    */
    private function handleScmDashboard(string $position)
    {
        if ($position === 'manager') {
            // Fetch pending material requests (status = pending) that need to be forwarded to PRO
            $materialRequests = MaterialRequest::where('status', 'pending')
                ->with('material')
                ->orderByRaw("FIELD(urgency, 'High', 'Medium', 'Low')")
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(fn ($req) => [
                    'id' => $req->id,
                    'req_number' => $req->req_number,
                    'material_name' => $req->material?->name ?? $req->material_name,
                    'required_qty' => $req->required_qty,
                    'unit' => $req->material?->unit ?? $req->unit,
                    'urgency' => $req->urgency,
                ]);

            // Fetch pending invoices (unpaid)
            $invoices = PurchaseInvoice::where('status', 'unpaid')
                ->orderBy('due_date')
                ->get()
                ->map(fn ($inv) => [
                    'id' => $inv->id,
                    'invoice_number' => $inv->invoice_number,
                    'po_number' => $inv->po_number,
                    'supplier_name' => $inv->supplier_name,
                    'amount' => $inv->amount,
                    'due_date' => $inv->due_date,
                ]);

            // Fetch orders that have passed INV check and are ready for manufacturing approval
            $readyOrders = PurchaseOrder::with(['client', 'items'])
                ->whereHas('queue', function ($q) {
                    $q->where('stage', 'inv_checked')
                        ->where('inv_check_sufficient', true);
                })
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(fn ($order) => [
                    'id' => $order->id,
                    'po_number' => $order->po_number,
                    'client_name' => $order->client->company_name,
                    'total_amount' => $order->total_amount,
                    'created_at' => $order->created_at,
                ]);

            $stats = [
                'pendingMaterialRequests' => $materialRequests->count(),
                'pendingPayments' => $invoices->count(),
                'readyOrdersCount' => $readyOrders->count(),
            ];

            return Inertia::render('Dashboard/SCM/Manager/Index', [
                'stats' => $stats,
                'materialRequests' => $materialRequests,
                'invoices' => $invoices,
                'readyOrders' => $readyOrders,
            ]);
        }

        // Staff view (unchanged)
        return Inertia::render('Dashboard/SCM/Employee/Index', [
            'user' => Auth::user(),
            'stats' => [],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Finance Operations (FIN) Handler
    |--------------------------------------------------------------------------
    */
    private function handleFinDashboard(string $position)
    {
        $view = $position === 'manager' ? 'Dashboard/FIN/Manager/Index' : 'Dashboard/FIN/Employee/Index';

        return Inertia::render($view, [
            'user' => Auth::user(),
            'stats' => [
                'totalRevenue' => 0,
                'pendingInvoices' => 0,
                'overduePayments' => 0,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Manufacturing Plant (MAN) Handler
    |--------------------------------------------------------------------------
    */
    private function handleManDashboard(string $position)
    {
        $user = Auth::user();

        if ($position === 'manager') {
            return redirect()->route('man.manager.dashboard');
        }

        // For staff, redirect based on manufacturing_role
        $role = $user->manufacturing_role;

        $routes = [
            'knitting_yarn' => 'man.staff.knitting-yarn.dashboard',
            'dyeing_color' => 'man.staff.dyeing-color.dashboard',
            'dyeing_fabric_softener' => 'man.staff.dyeing-fabric-softener.dashboard',
            'dyeing_squeezer' => 'man.staff.dyeing-squeezer.dashboard',
            'dyeing_ironing' => 'man.staff.dyeing-ironing.dashboard',
            'dyeing_forming' => 'man.staff.dyeing-forming.dashboard',
            'dyeing_packaging' => 'man.staff.dyeing-packaging.dashboard',
            'maintenance_checker' => 'man.staff.maintenance-checker.dashboard',
            'checker_quality' => 'man.staff.checker-quality.dashboard',
        ];

        if (isset($routes[$role])) {
            return redirect()->route($routes[$role]);
        }

        // Fallback: render a generic staff view
        return Inertia::render('Dashboard/MAN/Employee/Index');
    }

    /*
    |--------------------------------------------------------------------------
    | Inventory Logistics (INV) Handler
    |--------------------------------------------------------------------------
    */
    private function handleInvDashboard(string $position)
    {
        $materials = Material::with('warehouseMaterials')->get();
        $totalSkus = $materials->count();
        $inStock = 0;
        $lowStock = 0;
        $outOfStock = 0;
        $totalValue = 0;

        foreach ($materials as $mat) {
            $qty = (float) $mat->warehouseMaterials->sum('quantity');
            $totalValue += $qty * (float) $mat->unit_cost;

            if ($qty <= 0) {
                $outOfStock++;
            } elseif ($qty <= $mat->reorder_point) {
                $lowStock++;
            } else {
                $inStock++;
            }
        }

        $warehouses = Warehouse::withCount([
            'warehouseMaterials as sku_count' => fn ($q) => $q->where('quantity', '>', 0),
        ])
            ->withSum('warehouseMaterials as total_units', 'quantity')
            ->orderBy('id')
            ->get()
            ->map(fn ($wh) => [
                'id' => $wh->id,
                'name' => $wh->name,
                'location' => $wh->location,
                'manager' => $wh->manager,
                'color' => $wh->color ?? 'blue',
                'skus' => (int) $wh->sku_count,
                'total_units' => (float) ($wh->total_units ?? 0),
            ])->values()->toArray();

        $totalWarehouses = count($warehouses);

        $alertItems = WarehouseMaterial::with(['material', 'warehouse'])
            ->whereHas('material')
            ->get()
            ->filter(fn ($wm) => (float) $wm->quantity <= (float) $wm->material->reorder_point)
            ->sortByDesc(fn ($wm) => $wm->quantity <= 0 ? 1 : 0)
            ->map(fn ($wm) => [
                'sku' => $wm->material->mat_id,
                'name' => $wm->material->name,
                'warehouse' => $wm->warehouse->name,
                'qty' => (float) $wm->quantity,
                'reorder' => (float) $wm->material->reorder_point,
                'type' => $wm->quantity <= 0 ? 'out' : 'low',
            ])->values()->toArray();

        $palette = [
            'Raw Material' => 'bg-blue-500',
            'Chemical' => 'bg-violet-500',
            'Accessory' => 'bg-emerald-500',
            'Packaging' => 'bg-amber-500',
            'Supplies' => 'bg-cyan-500',
        ];

        $safeTotal = $totalSkus ?: 1;

        $categoryBreakdown = $materials
            ->groupBy('category')
            ->map(fn ($group, $cat) => [
                'name' => $cat,
                'count' => $group->count(),
                'pct' => (int) round(($group->count() / $safeTotal) * 100),
                'color' => $palette[$cat] ?? 'bg-slate-400',
            ])->values()->toArray();

        $recentActivity = WarehouseMaterial::with(['material', 'warehouse'])
            ->latest('updated_at')
            ->take(10)
            ->get()
            ->map(function ($wm) {
                $qty = (float) $wm->quantity;
                $reorder = (float) ($wm->material->reorder_point ?? 0);

                if ($qty <= 0) {
                    $action = 'Out of stock flagged';
                    $color = 'red';
                } elseif ($qty <= $reorder) {
                    $action = 'Low stock alert';
                    $color = 'amber';
                } else {
                    $action = 'Stock updated';
                    $color = 'emerald';
                }

                return [
                    'time' => $wm->updated_at->diffForHumans(),
                    'action' => $action,
                    'item' => $wm->material->name,
                    'qty' => number_format($qty, 0).' '.$wm->material->unit,
                    'color' => $color,
                    'warehouse' => $wm->warehouse->name,
                ];
            })->values()->toArray();

        $totalProducts = InvProduct::count();

        if ($position === 'manager') {
            return Inertia::render('Dashboard/INV/Manager/Index', [
                'warehouses' => $warehouses,
                'alertItems' => $alertItems,
                'recentActivity' => $recentActivity,
                'categoryBreakdown' => $categoryBreakdown,
                'kpis' => [
                    'totalSkus' => $totalSkus,
                    'inStock' => $inStock,
                    'lowStock' => $lowStock,
                    'outOfStock' => $outOfStock,
                    'totalWarehouses' => $totalWarehouses,
                    'totalValue' => round($totalValue, 2),
                    'totalProducts' => $totalProducts,
                ],
            ]);
        }

        return Inertia::render('Dashboard/INV/Employee/Index', [
            'user' => Auth::user(),
            'kpis' => [
                'totalSkus' => $totalSkus,
                'inStock' => $inStock,
                'lowStock' => $lowStock,
                'outOfStock' => $outOfStock,
                'totalValue' => round($totalValue, 2),
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Order Fulfillment (ORD) Handler
    |--------------------------------------------------------------------------
    */
    private function handleOrdDashboard(string $position)
    {
        $view = $position === 'manager' ? 'Dashboard/ORD/Manager/Index' : 'Dashboard/ORD/Employee/Index';

        return Inertia::render($view, [
            'user' => Auth::user(),
            'recentOrders' => [],
            'stats' => [
                'pendingOrders' => 0,
                'completedToday' => 0,
                'totalRevenue' => 0,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Warehouse Operations (WAR) Handler
    |--------------------------------------------------------------------------
    */
    private function handleWarDashboard(string $position)
    {
        $view = $position === 'manager' ? 'Dashboard/WAR/Manager/Index' : 'Dashboard/WAR/Employee/Index';

        return Inertia::render($view, [
            'user' => Auth::user(),
            'bins' => [],
            'stats' => [
                'totalBins' => 0,
                'occupiedBins' => 0,
                'availableBins' => 0,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Relationship Management (CRM) Handler
    |--------------------------------------------------------------------------
    */
    private function handleCrmDashboard(string $position)
    {
        $user = Auth::user();

        // Base props expected by unified Index.vue
        $baseProps = [
            'user' => $user,
            'userRole' => strtoupper($user->role),
            'userPosition' => strtolower($user->position),
            'businessPartners' => [],
            'leads' => [],
            'pendingRegistrations' => [],
            'upcomingInterviews' => [],
            'pendingApprovals' => [],
        ];

        // ── CRM Manager Rendering ──
        if ($position === 'manager') {
            $totalLeads = CrmLead::count();
            $wonLeads = CrmLead::where('status', 'Closed-Won')->count();
            $conversionRate = $totalLeads > 0 ? round(($wonLeads / $totalLeads) * 100) : 0;

            $managerProps = array_merge($baseProps, [
                'stats' => [
                    'totalPipelineValue' => (float) CrmLead::whereNotIn('status', ['Closed-Won', 'Lost'])->sum('estimated_value'),
                    'activeInquiries' => CrmLead::where('status', 'Inquiry')->count(),
                    'pendingApprovals' => PurchaseOrder::whereIn('status', ['credit_review', 'tier_assignment'])->count(),
                    'conversionRate' => (int) $conversionRate,
                ],
                'dailyActivityCount' => CrmInteraction::whereDate('created_at', Carbon::today())->count(),
                'leaderboard' => User::where('role', 'CRM')
                    ->where('position', 'staff')
                    ->withCount(['leads as won_deals' => fn ($q) => $q->where('status', 'Closed-Won')])
                    ->orderBy('won_deals', 'desc')
                    ->get()
                    ->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'won_deals' => $u->won_deals,
                    ]),
                // Fetch actual data if required by Vue components
                'businessPartners' => Client::all() ?? [],
                'leads' => CrmLead::with('assignedStaff')->latest()->get() ?? [],
            ]);

            return Inertia::render('Dashboard/CRM/Index', $managerProps);
        }

        // ── CRM Staff Rendering ──
        $staffProps = array_merge($baseProps, [
            'stats' => [
                'myLeads' => $user->leads()->count(),
                'myActivities' => CrmInteraction::where('user_id', $user->id)->count(),
            ],
            'leads' => $user->leads()->latest()->get() ?? [],
        ]);

        return Inertia::render('Dashboard/CRM/Index', $staffProps);
    }

    /*
    |--------------------------------------------------------------------------
    | E-Commerce Platform (ECO) Handler
    |--------------------------------------------------------------------------
    */
    private function handleEcoDashboard(string $position)
    {
        // Redirect to appropriate ECO dashboard based on position
        if ($position === 'manager') {
            return redirect()->route('eco.manager.dashboard');
        }

        return redirect()->route('eco.employee.dashboard');
    }

    /*
    |--------------------------------------------------------------------------
    | NEW MODULE: Procurement (PRO) Handler
    |--------------------------------------------------------------------------
    */
    private function handleProDashboard(string $position)
    {
        // For manager, redirect to the material requests page
        if ($position === 'manager') {
            return redirect()->route('pro.manager.material-requests');
        }

        // Staff view (fallback)
        return Inertia::render('Dashboard/PRO/Employee/Index', [
            'user' => Auth::user(),
            'stats' => [
                'activeBids' => 0,
                'pendingContracts' => 0,
                'supplierIssues' => 0,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | NEW MODULE: Project Automation (PROJ) Handler
    |--------------------------------------------------------------------------
    */
    private function handleProjDashboard(string $position)
    {
        $view = $position === 'manager' ? 'Dashboard/PROJ/Manager/Index' : 'Dashboard/PROJ/Employee/Index';

        return Inertia::render($view, [
            'user' => Auth::user(),
            'stats' => [
                'activeProjects' => 0,
                'delayedProjects' => 0,
                'budgetUtilized' => '0%',
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | NEW MODULE: Information Technology (IT) Handler
    |--------------------------------------------------------------------------
    */
    private function handleItDashboard(string $position)
    {
        $view = $position === 'manager' ? 'Dashboard/IT/Manager/Index' : 'Dashboard/IT/Employee/Index';

        return Inertia::render($view, [
            'user' => Auth::user(),
            'stats' => [
                'activeTickets' => 0,
                'systemUptime' => '99.9%',
                'securityAlerts' => 0,
            ],
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Default Fallback Layout
    |--------------------------------------------------------------------------
    */
    private function renderDefaultDashboard($user)
    {
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_tasks' => 0,
                'pending_tasks' => 0,
                'completed_tasks' => 0,
            ],
            'user' => $user,
        ]);
    }
}
