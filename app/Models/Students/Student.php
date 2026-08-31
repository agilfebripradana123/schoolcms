<?php

namespace App\Models\Students;

use App\Models\Academic\Grade;
use App\Models\Academic\SchoolClass;
use App\Models\System\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    protected $table = 'students';

    protected $fillable = [
        'user_id',
        'class_id',
        'nisn',
        'nis',
        'name',
        'nik',
        'religion',
        'gender',
        'birth_place',
        'birth_date',
        'address',
        'previous_school',
        'email',
        'phone',
        'photo',
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
}
