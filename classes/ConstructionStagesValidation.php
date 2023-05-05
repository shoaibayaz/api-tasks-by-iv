<?php

class ConstructionStagesValidation
{
    public array $errors = [];

    /**
     * @return bool
     */
    public function validate(): bool
    {
        if (strlen($this->name ?? '') > 255) {
            $this->errors[] = 'Name cannot be longer than 255 characters';
            return false;
        }
        if (!$this->validISO8601Date($this->startDate)) {
            $this->errors[] = 'Invalid Start Date format';
            return false;
        }
        if (!is_null($this->endDate)) {
            if (!$this->validISO8601Date($this->endDate)) {
                $this->errors[] = 'Invalid End Date format';
                return false;
            }
            $start = strtotime($this->startDate);
            $end = strtotime($this->endDate);
            if ($start >= $end) {
                $this->errors[] = 'End date should be greater than start date';
                return false;
            }
        }
        if (!empty($this->durationUnit) && !in_array($this->durationUnit, ['HOURS', 'DAYS', 'WEEKS'])) {
            $this->errors[] = 'Duration Unit is not acceptable';
            return false;
        }
        if (!empty($this->color) && !preg_match('/^#[a-f0-9]{6}$/i', $this->color)) {
            $this->errors[] = 'Color code is not valid';
            return false;
        }
        if (strlen($this->externalId ?? '') > 255) {
            $this->errors[] = 'External Id cannot be longer than 255 characters';
            return false;
        }
        if (!empty($this->status) && !in_array($this->status, ['NEW', 'PLANNED', 'DELETED'])) {
            $this->errors[] = 'Status is not acceptable';
            return false;
        }
        return true;
    }

    /**
     * @param $value
     * @return bool
     */
    protected function validISO8601Date($value): bool
    {
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z$/', $value ?? '', $parts)) {
            $time = gmmktime($parts[4], $parts[5], $parts[6], $parts[2], $parts[3], $parts[1]);

            $input_time = strtotime($value);
            if ($input_time === false) return false;

            return $input_time == $time;
        } else {
            return false;
        }
    }

    /**
     * @return void
     */
    public function calculateDuration(): void
    {
        $this->duration = null;
        if (!empty($this->startDate) && !empty($this->endDate)) {
            $startDate = strtotime($this->startDate);
            $endDate = strtotime($this->endDate);

            $diff = $endDate - $startDate;

            $this->durationUnit = $this->durationUnit ?? 'DAYS';
            $algo = match ($this->durationUnit) {
                'HOURS' => (60 * 60),
                'DAYS' => (60 * 60 * 24),
                'WEEKS' => (60 * 60 * 24 * 7),
            };

            $this->duration = round($diff / $algo);
        }
    }


}