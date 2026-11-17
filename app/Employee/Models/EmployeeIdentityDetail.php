<?php

namespace App\Employee\Models;

use Database\Factories\EmployeeIdentityDetailFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['gender'])]
#[UseFactory(EmployeeIdentityDetailFactory::class)]
class EmployeeIdentityDetail extends Model
{
    /** @use HasFactory<EmployeeIdentityDetailFactory> */
    use HasFactory;

    protected $primaryKey = 'employee_id';

    public $incrementing = false;

    protected $hidden = ['national_id_ciphertext', 'national_id_hash'];

    public function editVersion(): string
    {
        return hash('sha256', serialize([$this->getKey(), $this->gender, $this->getRawOriginal('national_id_ciphertext')]));
    }

    protected function casts(): array
    {
        return ['national_id_ciphertext' => 'encrypted'];
    }

    public function setNationalId(#[\SensitiveParameter] string $nik): void
    {
        $this->national_id_ciphertext = $nik;
        $this->national_id_hash = hash_hmac('sha256', 'employee-nik:'.$nik, config('app.employee_nik_hash_key'));
    }
}
