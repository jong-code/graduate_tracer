<?php

namespace Tests\Unit;

use App\Models\GeneralInformation;
use Tests\TestCase;

class GeneralInformationNameTest extends TestCase
{
    public function test_it_formats_the_first_name_middle_initial_and_last_name(): void
    {
        $information = new GeneralInformation;
        $information->name = 'Jaymar';
        $information->middle_name = 'Namoc';
        $information->last_name = 'Calising';

        $this->assertSame('Jaymar N. Calising', $information->formattedName());
    }

    public function test_it_omits_the_middle_initial_when_no_middle_name_was_given(): void
    {
        $information = new GeneralInformation;
        $information->name = 'Jaymar';
        $information->middle_name = null;
        $information->last_name = 'Calising';

        $this->assertSame('Jaymar Calising', $information->formattedName());
    }
}
