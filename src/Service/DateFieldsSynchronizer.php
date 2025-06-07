<?php

namespace App\Service;

class DateFieldsSynchronizer
{
    public function synchronize(array $data, ?string $mainDate = null): array
    {
        if (!$mainDate && isset($data['date'])) {
            $mainDate = $data['date'];
        }

        if (!$mainDate) {
            return $data;
        }

        if (isset($data['date'])) {
            $data['date'] = $mainDate;
        }

        return $data;
    }
}
