<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'position',
        'profile_photo_path',
        'employee_id',
        'department',
        'join_date',
        'is_active',
        // Fields for promotion suggestion logic
        'promotion_suggested',
        'suggested_at',
        'manufacturing_role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Casting new fields for proper data handling
            'promotion_suggested' => 'boolean',
            'suggested_at' => 'datetime',
        ];
    }

    /**
     * Scope for HRM department users
     */
    public function scopeHrm($query)
    {
        return $query->where('role', 'HRM');
    }

    /**
     * Scope for SCM department users
     */
    public function scopeScm($query)
    {
        return $query->where('role', 'SCM');
    }

    public function scopeFin($query)
    {
        return $query->where('role', 'FIN');
    }

    public function scopeMan($query)
    {
        return $query->where('role', 'MAN');
    }

    public function scopeInv($query)
    {
        return $query->where('role', 'INV');
    }

    public function scopeOrd($query)
    {
        return $query->where('role', 'ORD');
    }

    public function scopeWar($query)
    {
        return $query->where('role', 'WAR');
    }

    public function scopeCrm($query)
    {
        return $query->where('role', 'CRM');
    }

    public function scopeEco($query)
    {
        return $query->where('role', 'ECO');
    }

    public function traineeGrade()
    {
        return $this->hasOne(TraineeGrade::class);
    }

    public function leaveRequests(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /**
     * Get all attendance logs for the user.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(AttendanceLog::class);
    }

    /**
     * Get the most recent attendance log (used in the Controller).
     */
    public function latestAttendance(): HasOne
    {
        return $this->hasOne(AttendanceLog::class)->latestOfMany('date');
    }

    /**
     * Get the shifts assigned to the user.
     */
    public function shifts(): HasMany
    {
        return $this->hasMany(EmployeeShift::class);
    }

    /**
     * Get the current active shift (used in the Controller).
     */
    public function currentShift(): HasOne
    {
        // This helps the 'with' query in your controller find today's shift
        return $this->hasOne(EmployeeShift::class)->latestOfMany('effective_date');
    }

    public function leads()
    {
        return $this->hasMany(CrmLead::class, 'assigned_staff_id');
    }

    public function interactions()
    {
        return $this->hasMany(CrmInteraction::class);
    }

    public function performedLogs()
    {
        return $this->hasMany(AuditLog::class, 'admin_id');
    }

    public function receivedLogs()
    {
        return $this->hasMany(AuditLog::class, 'target_id');
    }

    public function auditLogs()
    {
        // The foreign key is 'target_id' based on your SQL file
        return $this->hasMany(AuditLog::class, 'target_id');
    }

    // Relationships for manufacturing
    public function fabrics()
    {
        return $this->hasMany(Fabric::class, 'operator_id');
    }

    public function dyeJobs()
    {
        return $this->hasMany(DyeJob::class, 'operator_id');
    }

    public function softenerJobs()
    {
        return $this->hasMany(SoftenerJob::class, 'operator_id');
    }

    public function squeezerJobs()
    {
        return $this->hasMany(SqueezerJob::class, 'operator_id');
    }

    public function ironJobs()
    {
        return $this->hasMany(IronJob::class, 'operator_id');
    }

    public function formJobs()
    {
        return $this->hasMany(FormJob::class, 'operator_id');
    }

    public function packages()
    {
        return $this->hasMany(Package::class, 'operator_id');
    }

    public function reportedMachineReports()
    {
        return $this->hasMany(MachineReport::class, 'reported_by');
    }

    public function resolvedMachineReports()
    {
        return $this->hasMany(MachineReport::class, 'resolved_by');
    }

    /**
     * Get appropriate dashboard path based on department and position
     */
    public function getDashboardPathAttribute(): string
    {
        // If the position is trainee, redirect to a single unified trainee dashboard
        if ($this->position === 'trainee') {
            return route('trainee.dashboard');
        }

        return match ([$this->role, $this->position]) {
            ['HRM', 'manager'] => route('hrm.manager.dashboard'),
            ['HRM', 'staff'] => route('hrm.employee.dashboard'),
            ['SCM', 'manager'] => route('scm.manager.dashboard'),
            ['SCM', 'staff'] => route('scm.employee.dashboard'),
            ['FIN', 'manager'] => route('fin.manager.dashboard'),
            ['FIN', 'staff'] => route('fin.employee.dashboard'),
            ['MAN', 'manager'] => route('man.manager.dashboard'),
            ['MAN', 'staff'] => route('man.employee.dashboard'),
            ['INV', 'manager'] => route('inv.manager.dashboard'),
            ['INV', 'staff'] => route('inv.employee.dashboard'),
            ['ORD', 'manager'] => route('ord.manager.dashboard'),
            ['ORD', 'staff'] => route('ord.employee.dashboard'),
            ['WAR', 'manager'] => route('war.manager.dashboard'),
            ['WAR', 'staff'] => route('war.employee.dashboard'),
            ['CRM', 'manager'] => route('crm.manager.dashboard'),
            ['CRM', 'staff'] => route('crm.employee.dashboard'),
            ['ECO', 'manager'] => route('eco.manager.dashboard'),
            ['ECO', 'staff'] => route('eco.employee.dashboard'),
            ['PRO', 'manager'] => route('pro.manager.dashboard'),
            ['PRO', 'staff'] => route('pro.employee.dashboard'),
            ['PROJ', 'manager'] => route('proj.manager.dashboard'),
            ['PROJ', 'staff'] => route('proj.employee.dashboard'),
            ['IT', 'manager'] => route('it.manager.dashboard'),
            ['IT', 'staff'] => route('it.employee.dashboard'),
            default => route('dashboard'),
        };
    }
}
