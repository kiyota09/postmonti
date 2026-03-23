<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Applicant extends Model
{
    /**
     * The attributes that are mass assignable.
     * Including status and street_address_line2 to ensure database persistence.
     */
    protected $fillable = [
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone_number',
        'street_address',
        'street_address_line2',
        'city',
        'state_province',
        'postal_zip_code',
        'position_applied',
        'expected_salary',
        'notice_period',
        'textile_experience',
        'status',
        'sss_file',
        'philhealth_file',
        'pagibig_file',
    ];

    protected $casts = [
        'expected_salary' => 'decimal:2',
    ];

    /**
     * Helper to get the full name of the applicant.
     */
    public function getNameAttribute()
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Accessor for SSS File URL
     */
    public function getSssFileUrlAttribute()
    {
        return $this->sss_file ? asset('storage/'.$this->sss_file) : null;
    }

    /**
     * Accessor for PhilHealth File URL
     */
    public function getPhilhealthFileUrlAttribute()
    {
        return $this->philhealth_file ? asset('storage/'.$this->philhealth_file) : null;
    }

    /**
     * Accessor for Pag-ibig File URL
     */
    public function getPagibigFileUrlAttribute()
    {
        return $this->pagibig_file ? asset('storage/'.$this->pagibig_file) : null;
    }

    /**
     * Relationship: An applicant has one interview schedule.
     */
    public function interview(): HasOne
    {
        return $this->hasOne(Interview::class);
    }
}
