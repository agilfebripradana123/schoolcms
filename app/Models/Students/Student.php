<?php

namespace App\Models\Students;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\System\User;
use App\Models\Academic\SchoolClass;
use App\Models\Academic\Grade;
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
        'gender',
        'birth_place',
        'birth_date',
        'address',
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