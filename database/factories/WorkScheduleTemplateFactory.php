<?php

namespace Database\Factories;

use App\Scheduling\Models\WorkScheduleTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkScheduleTemplate> */
class WorkScheduleTemplateFactory extends Factory
{
    protected $model = WorkScheduleTemplate::class;

    public function definition(): array
    {
        return ['name' => 'Template mingguan', 'is_active' => false];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (WorkScheduleTemplate $template): void {
            $template->days()->createMany(array_map(
                static fn (int $number): array => ['weekday_number' => $number, 'is_working_day' => false],
                array_keys(WorkScheduleTemplate::WEEKDAYS),
            ));
        });
    }
}
