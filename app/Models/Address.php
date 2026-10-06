<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    protected $table = 'address';
    public $timestamps = false;

    protected $fillable = [
        'general_information_id',
        'current_street', 'current_barangay', 'current_municipality', 'current_province',
        'permanent_street', 'permanent_barangay', 'permanent_municipality', 'permanent_province',
    ];

    public function generalInformation()
    {
        return $this->belongsTo(GeneralInformation::class);
    }

    /**
     * "street barangay, municipality, province" - the exact grouping the
     * user asked for ("(permanent_street) (permanent_barangay), (permanent_municipality),
     * (permanent_province)"), read as placeholder notation rather than
     * literal parentheses in the output. Blank pieces are simply skipped
     * rather than leaving stray commas.
     */
    public function formattedPermanentAddress(): string
    {
        return $this->formatAddress('permanent');
    }

    public function formattedCurrentAddress(): string
    {
        return $this->formatAddress('current');
    }

    private function formatAddress(string $prefix): string
    {
        $streetLine = trim(implode(' ', array_filter([
            $this->{$prefix . '_street'},
            $this->{$prefix . '_barangay'},
        ])));

        return implode(', ', array_filter([
            $streetLine,
            $this->{$prefix . '_municipality'},
            $this->{$prefix . '_province'},
        ]));
    }
}
