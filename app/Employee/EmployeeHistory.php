<?php

namespace App\Employee;

use App\Employee\Models\Employee;
use Illuminate\Database\Eloquent\Relations\HasMany;

enum EmployeeHistory: string
{
    case Education = 'educations';
    case Training = 'trainings';
    case Coaching = 'coachings';

    public function label(): string
    {
        return match ($this) {
            self::Education => 'Pendidikan',
            self::Training => 'Pelatihan',
            self::Coaching => 'Pembinaan',
        };
    }

    public function headingField(): string
    {
        return match ($this) {
            self::Education => 'institution_name',
            self::Training => 'title',
            self::Coaching => 'subject',
        };
    }

    /** @return array<string, array{string, string, int|null, bool}> */
    public function fields(): array
    {
        return match ($this) {
            self::Education => [
                'institution_name' => ['Nama sekolah atau perguruan tinggi', 'text', 180, true],
                'education_level' => ['Jenjang pendidikan', 'text', 50, true],
                'major' => ['Jurusan', 'text', 100, false],
                'start_year' => ['Tahun mulai', 'number', null, false],
                'graduation_year' => ['Tahun lulus', 'number', null, false],
                'certificate_number' => ['Nomor ijazah', 'text', 100, false],
            ],
            self::Training => [
                'title' => ['Nama pelatihan', 'text', 180, true],
                'provider' => ['Penyelenggara', 'text', 150, false],
                'start_date' => ['Tanggal mulai', 'date', null, false],
                'end_date' => ['Tanggal selesai', 'date', null, false],
                'certificate_number' => ['Nomor sertifikat', 'text', 100, false],
                'notes' => ['Catatan', 'textarea', 2000, false],
            ],
            self::Coaching => [
                'subject' => ['Topik pembinaan', 'text', 180, true],
                'coaching_date' => ['Tanggal pembinaan', 'date', null, true],
                'description' => ['Uraian pembinaan', 'textarea', 2000, false],
                'follow_up' => ['Tindak lanjut', 'textarea', 2000, false],
            ],
        };
    }

    public function routeName(string $action): string
    {
        return 'admin.employees.'.$this->value.'.'.$action;
    }

    public function records(Employee $employee): HasMany
    {
        return match ($this) {
            self::Education => $employee->educations(),
            self::Training => $employee->trainings(),
            self::Coaching => $employee->coachings(),
        };
    }
}
