<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserNameSearchTest extends TestCase
{
    public function test_it_matches_name_words_in_any_order(): void
    {
        $user = new User;
        $user->name = 'Joseph James';
        $user->middle_name = 'Labrador';
        $user->last_name = 'Alferez';

        $this->assertTrue($user->matchesNameSearch('Joseph Alferez'));
        $this->assertTrue($user->matchesNameSearch('Alferez, Labrador'));
        $this->assertFalse($user->matchesNameSearch('Joseph Calising'));
        $user->email = 'graduate@example.test';
        $this->assertTrue($user->matchesNameSearch('GRADUATE@example'));
        $this->assertTrue($user->matchesNameSearch('  '));
    }
}
