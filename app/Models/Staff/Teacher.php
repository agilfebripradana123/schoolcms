<?php

namespace App\Models\Staff;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\System\User;
use App\Models\Academic\SchoolClass;
use App\Models\Academic\ClassSubject;
class Teacher extends Model
{
    use SoftDeletes;

    protected $table = 'teachers';

    protected $primaryKey = 'id';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'teacher_code',
        'nip',
        'full_name',
        'prefix_title',
        'suffix_title',
        'phone',
        'email',
        'last_education',
        'major',
        'employment_status',
        'join_date',
        'photo',
        'is_active',
        'address',
        'gender',
        'birth_place',
        'birth_date',
        'religion',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'join_date' => 'date',
            'birth_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'teacher_id');
    }

    public function classSubjects(): HasMany
    {
        return $this->hasMany(ClassSubject::class, 'teacher_id');
    }
}
