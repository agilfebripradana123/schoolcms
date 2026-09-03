<?php

namespace App\Models\Students;

use App\Models\Academic\Grade;
use App\Models\Academic\SchoolClass;
use App\Models\Finance\Billing;
use App\Models\Finance\Payment;
use App\Models\Finance\Scholarship;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $table = 'students';

    protected $fillable = [
        'user_id','class_id','nisn','nis','name','nik','religion','gender','birth_place','birth_date',
        'address','rt','rw','hamlet','village','district','postal_code','residence_type','transportation','telephone',
        'family_card_number','birth_certificate_registration_number','skhun','previous_school','national_exam_number','diploma_serial_number',
        'special_needs','birth_order','sibling_count','weight','height','head_circumference','school_distance','latitude','longitude',
        'kps_recipient','kps_number','kip_recipient','kip_number','kip_name','kks_number','pip_eligible','pip_reason',
        'bank_name','bank_account_number','bank_account_holder','phone','email','photo',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function grades(): HasMany
    {
        return $this->hasMany(Grade::class, 'student_id');
    }

    public function parent(): HasOne
    {
        return $this->hasOne(StudentParent::class, 'student_id');
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class, 'student_id');
    }

    public function billings(): HasMany
    {
        return $this->hasMany(Billing::class, 'student_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'student_id');
    }

    public function scholarships(): HasMany
    {
        return $this->hasMany(Scholarship::class, 'student_id');
    }
}
