<?php
declare(strict_types=1);

// Members and partners keep any number of special dates as a JSON array:
// [{ id, date: "YYYY-MM-DD", note }]. Port of utils/specialDates.js.
final class SpecialDates
{
    public static function asList($value): array
    {
        return is_array($value) && array_is_list($value) ? $value : [];
    }

    public static function build(array $entry): array
    {
        $date = Dates::parse($entry['date'] ?? null);
        if (!$date) {
            throw new HttpError(400, 'Invalid date');
        }
        $note = isset($entry['note']) && is_string($entry['note']) ? trim($entry['note']) : '';

        return [
            'id' => Crypto::uuid(),
            'date' => $date->format('Y-m-d'),
            'note' => $note !== '' ? $note : null,
        ];
    }

    // Multipart forms send the whole list as a JSON string. Returns null when the
    // field was not sent, so an update leaves the stored dates alone.
    public static function parse($raw): ?array
    {
        if ($raw === null) {
            return null;
        }

        $list = $raw;

        if (is_string($raw)) {
            if (trim($raw) === '') {
                return [];
            }
            $list = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new HttpError(400, 'Invalid special dates');
            }
        }

        $result = [];
        foreach (self::asList($list) as $entry) {
            if (!is_array($entry) || empty($entry['date']) || !Dates::parse($entry['date'])) {
                continue;
            }
            $built = self::build($entry);
            if (!empty($entry['id'])) {
                $built['id'] = (string) $entry['id'];
            }
            $result[] = $built;
        }

        return $result;
    }

    public static function update(array $list, string $dateId, array $body): ?array
    {
        foreach ($list as $index => $entry) {
            if (($entry['id'] ?? null) === $dateId) {
                $list[$index] = array_merge($entry, self::build($body), ['id' => $entry['id']]);
                return $list;
            }
        }
        return null;
    }
}
