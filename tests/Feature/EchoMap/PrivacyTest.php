<?php

namespace Tests\Feature\EchoMap;

use App\Models\Friendship;
use App\Models\Pin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $name = 'user'): User
    {
        return User::factory()->create(['name' => $name]);
    }

    private function makePin(User $owner, string $visibility, array $overrides = []): Pin
    {
        return Pin::create(array_merge([
            'user_id'    => $owner->id,
            'title'      => 'Test pin',
            'body'       => 'Body',
            'latitude'   => 14.55,
            'longitude'  => 121.04,
            'theme'      => 'Memory',
            'visibility' => $visibility,
            'is_hidden'  => false,
        ], $overrides));
    }

    public function test_private_pins_do_not_leak_through_pins_endpoint(): void
    {
        $alice = $this->makeUser('alice');
        $bob   = $this->makeUser('bob');

        $alicePublic  = $this->makePin($alice, 'public');
        $aliceFriends = $this->makePin($alice, 'friends');
        $alicePrivate = $this->makePin($alice, 'private');

        $response = $this->actingAs($bob)->getJson('/pins');
        $response->assertOk();

        $ids = collect($response->json())->pluck('id')->all();
        $this->assertContains($alicePublic->id, $ids);
        $this->assertNotContains($aliceFriends->id, $ids);
        $this->assertNotContains($alicePrivate->id, $ids);
    }

    public function test_owner_sees_own_pins_regardless_of_visibility(): void
    {
        $alice = $this->makeUser('alice');

        $public  = $this->makePin($alice, 'public');
        $friends = $this->makePin($alice, 'friends');
        $private = $this->makePin($alice, 'private');

        $response = $this->actingAs($alice)->getJson('/pins');
        $ids = collect($response->json())->pluck('id')->all();

        $this->assertContains($public->id,  $ids);
        $this->assertContains($friends->id, $ids);
        $this->assertContains($private->id, $ids);
    }

    public function test_friends_see_friends_only_pins(): void
    {
        $alice = $this->makeUser('alice');
        $bob   = $this->makeUser('bob');

        Friendship::create([
            'sender_id'   => $alice->id,
            'receiver_id' => $bob->id,
            'status'      => 'accepted',
        ]);

        $friendsOnly = $this->makePin($alice, 'friends');

        $response = $this->actingAs($bob)->getJson('/pins');
        $ids = collect($response->json())->pluck('id')->all();

        $this->assertContains($friendsOnly->id, $ids);
    }

    public function test_pending_friend_does_not_see_friends_only_pins(): void
    {
        $alice = $this->makeUser('alice');
        $bob   = $this->makeUser('bob');

        Friendship::create([
            'sender_id'   => $alice->id,
            'receiver_id' => $bob->id,
            'status'      => 'pending',
        ]);

        $friendsOnly = $this->makePin($alice, 'friends');

        $response = $this->actingAs($bob)->getJson('/pins');
        $ids = collect($response->json())->pluck('id')->all();

        $this->assertNotContains($friendsOnly->id, $ids);
    }

    public function test_hidden_pins_are_never_returned(): void
    {
        $alice = $this->makeUser('alice');
        $pin   = $this->makePin($alice, 'public', ['is_hidden' => true]);

        $response = $this->actingAs($alice)->getJson('/pins');
        $ids = collect($response->json())->pluck('id')->all();

        $this->assertNotContains($pin->id, $ids);
    }

    public function test_stories_endpoint_only_returns_ready_stories(): void
    {
        $alice = $this->makeUser('alice');
        $this->makePin($alice, 'public');
        $this->makePin($alice, 'public');

        $response = $this->actingAs($alice)->getJson('/stories');
        $response->assertOk();
        $this->assertSame([], $response->json());
    }

    public function test_pins_endpoint_requires_authentication(): void
    {
        $this->getJson('/pins')->assertUnauthorized();
        $this->getJson('/stories')->assertUnauthorized();
    }
}
